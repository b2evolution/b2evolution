<?php
/**
 * This file implements the UI view (+more :/) for the blogs permission management.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 *
 * @todo move user rights queries to object (fplanque)
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;
/**
 * @var User
 */
global $current_User;

global $debug;
global $UserSettings;
global $rsc_url, $htsrv_url;

global $Blog, $permission_to_change_admin;

$permission_to_change_admin = $current_User->check_perm( 'blog_admin', 'edit', false, $Blog->ID );

$layout = $UserSettings->param_Request( 'layout', 'blogperms_layout', 'string', 'default' );  // table layout mode

$layout = $UserSettings->param_Request( 'layout', 'blogperms_layout', 'string', $debug ? 'all' : 'default' );  // table layout mode


// Javascript:
echo '
<script type="text/javascript">var htsrv_url = "'.$htsrv_url.'";</script>
<script type="text/javascript" src="'.$rsc_url.'js/collectionperms.js"></script>';

$Form = new Form( NULL, 'blogperm_checkchanges', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'tab', 'perm' );
$Form->hidden( 'blog', $edited_Blog->ID );
$Form->hidden( 'layout', $layout );

$Form->begin_fieldset( T_('User permissions') );


/*
 * Query user list:
 */
if( get_param('action') == 'filter2' )
{
	$keywords = param( 'keywords2', 'string', '', true );
	set_param( 'keywords1', $keywords );
}
else
{
	$keywords = param( 'keywords1', 'string', '', true );
	set_param( 'keywords2', $keywords );
}

$SQL = new SQL();
$SQL->SELECT( 'user_ID, user_login, user_level, bloguser_perm_poststatuses, bloguser_perm_edit, bloguser_ismember,'
	. 'bloguser_perm_comments, bloguser_perm_delpost, bloguser_perm_cats,'
	. 'bloguser_perm_properties, bloguser_perm_admin, bloguser_perm_media_upload,'
	. 'bloguser_perm_media_browse, bloguser_perm_media_change, bloguser_perm_page,'
	. 'bloguser_perm_intro, bloguser_perm_podcast, bloguser_perm_sidebar' );
$SQL->FROM( 'T_users LEFT JOIN T_coll_user_perms ON (
				 						user_ID = bloguser_user_ID
										AND bloguser_blog_ID = '.$edited_Blog->ID.' )' );
$SQL->ORDER_BY( 'bloguser_ismember DESC, *, user_login, user_ID' );

if( !empty( $keywords ) )
{
	$SQL->add_search_field( 'user_login' );
	$SQL->add_search_field( 'user_firstname' );
	$SQL->add_search_field( 'user_lastname' );
	$SQL->add_search_field( 'user_nickname' );
	$SQL->add_search_field( 'user_email' );
	$SQL->WHERE_keywords( $keywords, 'AND' );
}


// Display layout selector:
// TODO: cancel event in switch layout (or it will trigger bozo validator)
echo '<div style="float:right">';
	echo T_('Layout').': ';
	echo '[<a href="?ctrl=coll_settings&amp;action=edit&amp;tab=perm&amp;blog='.$edited_Blog->ID.'&amp;layout=default"
					onclick="blogperms_switch_layout(\'default\'); return false;">'.T_('Simple').'</a>] ';

	echo '[<a href="?ctrl=coll_settings&amp;action=edit&amp;tab=perm&amp;blog='.$edited_Blog->ID.'&amp;layout=wide"
					onclick="blogperms_switch_layout(\'wide\'); return false;">'.T_('Advanced').'</a>] ';

	if( $debug )
	{	// Debug mode = both modes are displayed:
		echo '[<a href="?ctrl=coll_settings&amp;action=edit&amp;tab=perm&amp;blog='.$edited_Blog->ID.'&amp;layout=all"
						onclick="blogperms_switch_layout(\'all\'); return false;">Debug</a>] ';
	}
echo '</div>';
// Display wide layout:
?>

<div id="userlist_wide" class="clear" style="<?php
	echo 'display:'.( ($layout == 'wide' || $layout == 'all' ) ? 'block' : 'none' ) ?>">

<?php


$Results = new Results( $SQL->get(), 'colluser_' );

// Tell the Results class that we already have a form for this page:
$Results->Form = & $Form;


$Results->title = T_('User permissions');



/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_colluserlist( & $Form )
{
	static $count = 0;

	$count++;
	$Form->switch_layout( 'blockspan' );
	// TODO: javascript update other input fields (for other layouts):
	$Form->text( 'keywords'.$count, get_param('keywords'.$count), 20, T_('Keywords'), T_('Separate with space'), 50 );
	$Form->switch_layout( NULL ); // Restor previously saved
}
$Results->filter_area = array(
	'submit' => 'actionArray[filter1]',
	'callback' => 'filter_colluserlist',
	'url_ignore' => 'results_colluser_page,keywords1,keywords2',
	'presets' => array(
		'all' => array( T_('All users'), regenerate_url( 'action,results_colluser_page,keywords1,keywords2', 'action=edit' ) ),
		)
	);



/*
 * Grouping params:
 */
$Results->group_by = 'bloguser_ismember';
$Results->ID_col = 'user_ID';


/*
 * Group columns:
 */
$Results->grp_cols[] = array(
						'td_colspan' => 0,  // nb_cols
						'td' => '¤conditional( #bloguser_ismember#, \''.TS_('Members').'\', \''.TS_('Non members').'\' )¤',
					);


/*
 * Colmun definitions:
 */
$Results->cols[] = array(
						'th' => T_('Login'),
						'order' => 'user_login',
						'td' => '<a href="?ctrl=user&amp;user_tab=identity&amp;user_ID=$user_ID$">$user_login$</a>',
					);

$Results->cols[] = array(
						'th' => /* TRANS: User Level */ T_('L'),
						'order' => 'user_level',
						'td' => '$user_level$',
						'td_class' => 'center',
					);


function coll_perm_checkbox( $row, $perm, $title, $id = NULL )
{
	global $permission_to_change_admin;

 	$r = '<input type="checkbox"';
	if( !empty($id) )
	{
		$r .= ' id="'.$id.'"';
	}
	$r .= ' name="blog_'.$perm.'_'.$row->user_ID.'"';
	if( !empty( $row->{'bloguser_'.$perm} ) )
	{
	 	$r .= ' checked="checked"';
	}
	if( ! $permission_to_change_admin
			&& ($row->bloguser_perm_admin || $perm == 'perm_admin' ) )
	{ // No permission to touch nOR create admins
	 	$r .= ' disabled="disabled"';
	}
	$r .= ' onclick="merge_from_wide( this, '.$row->user_ID.' );" class="checkbox"
							value="1" title="'.$title.'" />';
	return $r;
}

function coll_perm_status_checkbox( $row, $perm_status, $title )
{
	global $permission_to_change_admin;

	if( ! isset( $row->statuses_array ) )
	{	// NOTE: we are writing directly into the DB result array here, it's a little harsh :/
		// TODO: make all these perms booleans in the DB:
		$row->statuses_array = isset($row->bloguser_perm_poststatuses)
											? explode( ',', $row->bloguser_perm_poststatuses )
											: array();
	}

	// pre_dump($row->statuses_array);

	$r = '<input type="checkbox"';
	if( !empty($id) )
	{
		$r .= ' id="'.$id.'"';
	}
	$r .= ' name="blog_perm_'.$perm_status.'_'.$row->user_ID.'"';
	if( in_array($perm_status, $row->statuses_array) )
	{
	 	$r .= ' checked="checked"';
	}
	if( ! $permission_to_change_admin && $row->bloguser_perm_admin )
	{
	 	$r .= ' disabled="disabled"';
	}
	$r .= ' onclick="merge_from_wide( this, '.$row->user_ID.' );" class="checkbox"
							value="1" title="'.$title.'" />';
	return $r;
}

$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Is<br />member'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_checkbox( {row}, \'ismember\', \''.TS_('Permission to read protected posts').'\', \'checkallspan_state_$user_ID$\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => /* TRANS: SHORT table header on TWO lines */ T_('Can post/edit with following statuses:'),
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Publ'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_status_checkbox( {row}, \'published\', \''.TS_('Permission to post into this blog with published status').'\' )%',
						'td_class' => 'center',
					);
$Results->cols[] = array(
						'th_group' => /* TRANS: SHORT table header on TWO lines */ T_('Can post/edit with following statuses:'),
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Prot'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_status_checkbox( {row}, \'protected\', \''.TS_('Permission to post into this blog with protected status').'\' )%',
						'td_class' => 'center',
					);
$Results->cols[] = array(
						'th_group' => /* TRANS: SHORT table header on TWO lines */ T_('Can post/edit with following statuses:'),
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Priv'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_status_checkbox( {row}, \'private\', \''.TS_('Permission to post into this blog with private status').'\' )%',
						'td_class' => 'center',
					);
$Results->cols[] = array(
						'th_group' => /* TRANS: SHORT table header on TWO lines */ T_('Can post/edit with following statuses:'),
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Drft'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_status_checkbox( {row}, \'draft\', \''.TS_('Permission to post into this blog with draft status').'\' )%',
						'td_class' => 'center',
					);
$Results->cols[] = array(
						'th_group' => /* TRANS: SHORT table header on TWO lines */ T_('Can post/edit with following statuses:'),
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Depr'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_status_checkbox( {row}, \'deprecated\', \''.TS_('Permission to post into this blog with deprecated status').'\' )%',
						'td_class' => 'center',
					);
$Results->cols[] = array(
						'th_group' => /* TRANS: SHORT table header on TWO lines */ T_('Can post/edit with following statuses:'),
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Redr'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_status_checkbox( {row}, \'redirected\', \''.TS_('Permission to post into this blog with redirected status').'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => /* TRANS: SHORT table header on TWO lines */ T_('Can use special post types:'),
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Page'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_checkbox( {row}, \'perm_page\', \''.TS_('Permission to create pages').'\' )%',
						'td_class' => 'center',
					);
$Results->cols[] = array(
						'th_group' => /* TRANS: SHORT table header on TWO lines */ T_('Can use special post types:'),
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Intro'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_checkbox( {row}, \'perm_intro\', \''.TS_('Permission to create intro posts (Intro-* post types)').'\' )%',
						'td_class' => 'center',
					);
$Results->cols[] = array(
						'th_group' => /* TRANS: SHORT table header on TWO lines */ T_('Can use special post types:'),
						'th' => /* TRANS: SHORT table header on TWO lines; short for "Podcast" */ T_('Podc'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_checkbox( {row}, \'perm_podcast\', \''.TS_('Permission to create podcast episodes').'\' )%',
						'td_class' => 'center',
					);
$Results->cols[] = array(
						'th_group' => /* TRANS: SHORT table header on TWO lines */ T_('Can use special post types:'),
						'th' => /* TRANS: SHORT table header on TWO lines; short for "Sidebar", meaning Sidebar links */ T_('Sideb'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_checkbox( {row}, \'perm_sidebar\', \''.TS_('Permission to create sidebar links').'\' )%',
						'td_class' => 'center',
					);

function coll_perm_edit( $row )
{
	global $permission_to_change_admin;

	$r = '<select id="blog_perm_edit_'.$row->user_ID.'" name="blog_perm_edit_'.$row->user_ID.'"
					onclick="merge_from_wide( this, '.$row->user_ID.' );"';
	if( ! $permission_to_change_admin && $row->bloguser_perm_admin )
	{
	 	$r .= ' disabled="disabled"';
	}
	$r .= ' >';
	$r .= '<option value="no" '.( $row->bloguser_perm_edit == 'no' ? 'selected="selected"' : '' ).'>No editing</option>';
	$r .= '<option value="own" '.( $row->bloguser_perm_edit == 'own' ? 'selected="selected"' : '' ).'>Own posts</option>';
	$r .= '<option value="lt" '.( $row->bloguser_perm_edit == 'lt' ? 'selected="selected"' : '' ).'>&lt; own level</option>';
	$r .= '<option value="le" '.( $row->bloguser_perm_edit == 'le' ? 'selected="selected"' : '' ).'>&le; own level</option>';
	$r .= '<option value="all" '.( $row->bloguser_perm_edit == 'all' ? 'selected="selected"' : '' ).'>All posts</option>';
	$r .= '</select>';
	return $r;
}
$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Edit posts<br />/user level'),
						'th_class' => 'checkright',
						'default_dir' => 'D',
						'td' => '%coll_perm_edit( {row} )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Delete<br />posts'),
						'th_class' => 'checkright',
						'order' => 'bloguser_perm_delpost',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'perm_delpost\', \''.TS_('Permission to delete posts in this blog').'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Edit<br />comts'),
						'th_class' => 'checkright',
						'order' => 'bloguser_perm_comments',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'perm_comments\', \''.TS_('Permission to edit comments in this blog').'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => T_('Edit blog settings'),
						'th' => T_('Cats'),
						'th_class' => 'checkright',
						'order' => 'bloguser_perm_cats',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'perm_cats\', \''.TS_('Permission to edit categories for this blog').'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => T_('Edit blog settings'),
						'th' => /* TRANS: Short for blog features */  T_('Feat.'),
						'th_class' => 'checkright',
						'order' => 'bloguser_perm_properties',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'perm_properties\', \''.TS_('Permission to edit blog features').'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => T_('Edit blog settings'),
						'th' => /* TRANS: Short for advanced */  T_('Adv.'),
						'th_class' => 'checkright',
						'order' => 'bloguser_perm_admin',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'perm_admin\', \''.TS_('Permission to edit advanced/administrative blog properties').'\' )%',
						'td_class' => 'center',
					);

// Media Directory:
$Results->cols[] = array(
						'th_group' => T_('Media directory'),
						'th' => T_('Upload'),
						'th_class' => 'checkright',
						'order' => 'bloguser_perm_media_upload',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'perm_media_upload\', \''.TS_('Permission to upload into blog\'s media folder').'\' )%',
						'td_class' => 'center',
					);
$Results->cols[] = array(
						'th_group' => T_('Media directory'),
						'th' => T_('Read'),
						'th_class' => 'checkright',
						'order' => 'bloguser_perm_media_browse',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'perm_media_browse\', \''.TS_('Permission to browse blog\'s media folder').'\' )%',
						'td_class' => 'center',
					);
$Results->cols[] = array(
						'th_group' => /* TRANS: SHORT table header on TWO lines */ T_('Media directory'),
						'th' => T_('Write'),
						'th_class' => 'checkright',
						'order' => 'bloguser_perm_media_change',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'perm_media_change\', \''.TS_('Permission to change the blog\'s media folder content').'\' )%',
						'td_class' => 'center',
					);

function perm_check_all( $row )
{
	global $permission_to_change_admin;

	if( ! $permission_to_change_admin && $row->bloguser_perm_admin )
	{
	 	return '&nbsp;';
	}

	return '<a href="javascript:toggleall_wide(document.getElementById(\'blogperm_checkchanges\'), '.$row->user_ID.' );merge_from_wide( document.getElementById(\'blogperm_checkchanges\'), '.$row->user_ID.' ); setcheckallspan('.$row->user_ID.');" title="'.TS_('(un)selects all checkboxes using Javascript').'">
							<span id="checkallspan_'.$row->user_ID.'">'.TS_('(un)check all').'</span>
						</a>';
}
$Results->cols[] = array(
						'th' => '&nbsp;',
						'td' => '%perm_check_all( {row} )%',
						'td_class' => 'center',
					);



// Display WIDE:
$Results->display();

echo '</div>';


// Display simple layout:
?>
<div id="userlist_default" class="clear" style="<?php
	echo 'display:'.( ($layout == 'default' || $layout == 'all' ) ? 'block' : 'none' ) ?>">

<?php


// Change filter definitions for simple layout:

$Results->filter_area = array(
	'submit' => 'actionArray[filter2]',
	'callback' => 'filter_colluserlist',
	'url_ignore' => 'action,results_colluser_page,keywords1,keywords2',
	'presets' => array(
		'all' => array( T_('All users'), regenerate_url( 'action,results_colluser_page,keywords1,keywords2', 'action=edit' ) ),
		)
	);


// Change column definitions for simple layout:

$Results->cols = array(); // RESET!

$Results->cols[] = array(
						'th' => T_('Login'),
						'order' => 'user_login',
						'td' => '<a href="?ctrl=user&amp;user_tab=identity&amp;user_ID=$user_ID$">$user_login$</a>',
					);


function simple_coll_perm_radios( $row )
{
	global $permission_to_change_admin;

	$r = '';
	$user_easy_group = blogperms_get_easy2( $row );
	foreach( array(
								array( 'nomember', T_('Not Member') ),
								array( 'member', T_('Member') ),
								array( 'contrib', T_('Contributor') ),
								array( 'editor', T_('Publisher') ),
								array( 'moderator', T_('Moderator') ),
								array( 'owner',  T_('Owner') ),
								array( 'admin',  T_('Admin') ),
								array( 'custom',  T_('Custom') )
							) as $lkey => $easy_group )
	{
		$r .= '<input type="radio" id="blog_perm_easy_'.$row->user_ID.'_'.$lkey.'" name="blog_perm_easy_'.$row->user_ID.'" value="'.$easy_group[0].'"';
		if( $easy_group[0] == $user_easy_group )
		{
			$r .= ' checked="checked"';
		}
		if( ! $permission_to_change_admin
				&& ( $row->bloguser_perm_admin || $easy_group[0] == 'admin' ) )
		{ // No permission to touch nOR create admins
	 		$r .= ' disabled="disabled"';
		}
		$r .= ' onclick="merge_from_easy( this, '.$row->user_ID.' )" class="radio" />
		<label for="blog_perm_easy_'.$row->user_ID.'_'.$lkey.'">'.$easy_group[1].'</label> ';
	}

	return $r;
}
$Results->cols[] = array(
						'th' => T_('Role'),
						'td' => '%simple_coll_perm_radios( {row} )%',
					);


// Display SIMPLE:
$Results->display();


echo '</div>';

// Permission note:
// fp> TODO: link
echo '<p class="note center">'.T_('Note: General group permissions may further restrict or extend any media folder permissions defined here.').'</p>';

$Form->end_fieldset();


// Make a hidden list of all displayed users:
$user_IDs = array();
foreach( $Results->rows as $row )
{
	$user_IDs[] = $row->user_ID;
}
$Form->hidden( 'user_IDs', implode( ',', $user_IDs) );

$Form->end_form( array( array( 'submit', 'actionArray[update]', T_('Update'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );


/*
 * $Log$
 * Revision 1.15  2010/02/08 17:52:09  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.14  2010/01/30 18:55:22  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.13  2010/01/03 13:45:36  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.12  2009/11/21 13:31:58  efy-maxim
 * 1. users controller has been refactored to users and user controllers
 * 2. avatar tab
 * 3. jQuery to show/hide custom duration
 *
 * Revision 1.11  2009/09/25 20:26:26  fplanque
 * fixes/doc
 *
 * Revision 1.10  2009/09/25 14:18:22  tblue246
 * Reverting accidental commits
 *
 * Revision 1.8  2009/09/25 13:07:49  efy-vyacheslav
 * Using the SQL class to prepare queries
 *
 * Revision 1.7  2009/09/12 00:21:03  fplanque
 * search cleanup
 *
 * Revision 1.6  2009/08/31 17:21:32  fplanque
 * minor
 *
 * Revision 1.5  2009/08/29 12:23:56  tblue246
 * - SECURITY:
 * 	- Implemented checking of previously (mostly) ignored blog_media_(browse|upload|change) permissions.
 * 	- files.ctrl.php: Removed redundant calls to User::check_perm().
 * 	- XML-RPC APIs: Added missing permission checks.
 * 	- items.ctrl.php: Check permission to edit item with current status (also checks user levels) for update actions.
 * - XML-RPC client: Re-added check for zlib support (removed by update).
 * - XML-RPC APIs: Corrected method signatures (return type).
 * - Localization:
 * 	- Fixed wrong permission description in blog user/group permissions screen.
 * 	- Removed wrong TRANS comment
 * 	- de-DE: Fixed bad translation strings (double quotes + HTML attribute = mess).
 * - File upload:
 * 	- Suppress warnings generated by move_uploaded_file().
 * 	- File browser: Hide link to upload screen if no upload permission.
 * - Further code optimizations.
 *
 * Revision 1.4  2009/08/22 20:31:01  tblue246
 * New feature: Post type permissions
 *
 * Revision 1.3  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.2  2008/01/21 09:35:27  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 10:59:38  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.25  2007/06/12 23:51:16  fplanque
 * non admins can no longer create blog admins
 *
 * Revision 1.24  2007/06/12 23:16:04  fplanque
 * non admins can no longer change admin blog perms
 *
 * Revision 1.23  2007/06/03 02:54:18  fplanque
 * Stuff for permission maniacs (admin part only, actual perms checks to be implemented)
 * Newbies will not see this complexity since advanced perms are now disabled by default.
 *
 * Revision 1.22  2007/05/31 03:49:24  fplanque
 * editing perm concept demo
 *
 * Revision 1.21  2007/05/29 01:17:20  fplanque
 * advanced admin blog settings are now restricted by a special permission
 *
 * Revision 1.20  2007/04/26 00:11:05  fplanque
 * (c) 2007
 *
 * Revision 1.19  2007/03/11 22:48:19  fplanque
 * handling of permission to redirect posts
 *
 * Revision 1.18  2007/01/23 04:20:30  fplanque
 * wording
 *
 * Revision 1.17  2006/12/03 19:00:30  blueyed
 * Moved collection perm JavaScript to the views, as per todo
 *
 * Revision 1.16  2006/11/18 17:57:17  blueyed
 * blogperms_switch_layout() moved/renamed
 *
 * Revision 1.15  2006/11/04 17:38:24  blueyed
 * Blog perm layout views: fixed non-JS links (ctrl param) and store selected one in UserSettings (TODO for switching by JS)
 *
 * Revision 1.14  2006/11/04 17:19:39  blueyed
 * Blog perms view links: Changed "Wide" to "Advanced" and localized it together with "Simple". See http://forums.b2evolution.net/viewtopic.php?t=9654
 *
 * Revision 1.13  2006/11/03 18:22:26  fplanque
 * no message
 *
 * Revision 1.12  2006/10/14 04:34:26  blueyed
 * Proper escaping; fixes E_FATAL in Results eval()
 *
 * Revision 1.11  2006/10/11 17:21:09  blueyed
 * Fixes
 */
?>