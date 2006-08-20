<?php
/**
 * This file implements the UI view (+more :/) for the blogs permission management.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 *
 * @todo move user rights queries to object (fplanque)
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

$layout = param( 'layout', 'string', $debug ? 'all' : 'default' );  // table layout mode



$Form = & new Form( NULL, 'blogperm_checkchanges', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

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
	$keywords = $Request->param( 'keywords2', 'string', '', true );
	$Request->set_param( 'keywords1', $keywords );
}
else
{
	$keywords = $Request->param( 'keywords1', 'string', '', true );
	$Request->set_param( 'keywords2', $keywords );
}

$where_clause = '';

if( !empty( $keywords ) )
{
	$kw_array = split( ' ', $keywords );
	foreach( $kw_array as $kw )
	{
		$where_clause .= 'CONCAT( user_login, \' \', user_firstname, \' \', user_lastname, \' \', user_nickname, \' \', user_email) LIKE "%'.$DB->escape($kw).'%" AND ';
	}
}

$sql = 'SELECT user_ID, user_login, bloguser_perm_poststatuses, bloguser_ismember,
													bloguser_perm_comments, bloguser_perm_delpost, bloguser_perm_cats,
													bloguser_perm_properties, bloguser_perm_media_upload,
													bloguser_perm_media_browse, bloguser_perm_media_change
					FROM T_users LEFT JOIN T_coll_user_perms ON (
				 						user_ID = bloguser_user_ID
										AND bloguser_blog_ID = '.$edited_Blog->ID.' )
				 WHERE '.$where_clause.' 1
				 ORDER BY bloguser_ismember DESC, *, user_login, user_ID';



// Display layout selector:
// TODO: cancel event in switch layout (or it will trigger bozo validator)
echo '<div style="float:right">';
	echo T_('Layout').': ';
	echo '[<a href="?ctrl=collections&amp;action=edit&amp;tab=perm&amp;blog='.$edited_Blog->ID.'&amp;layout=default"
					onclick="switch_layout(\'default\'); return false;">Simple</a>] ';

	echo '[<a href="?ctrl=collections&amp;action=edit&amp;tab=perm&amp;blog='.$edited_Blog->ID.'&amp;layout=wide"
					onclick="switch_layout(\'wide\'); return false;">Wide</a>] ';

	if( $debug )
	{	// Debug mode = both modes are displayed:
		echo '[<a href="?ctrl=collections&amp;action=edit&amp;tab=perm&amp;blog='.$edited_Blog->ID.'&amp;layout=all"
						onclick="switch_layout(\'all\'); return false;">Debug</a>] ';
	}
echo '</div>';
// Display wide layout:
?>

<div id="userlist_wide" class="clear" style="<?php
	echo 'display:'.( ($layout == 'wide' || $layout == 'all' ) ? 'block' : 'none' ) ?>">

<?php


$Results = & new Results( $sql, 'colluser_' );

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
	global $Request;
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
						'td' => '<a href="?ctrl=users&amp;user_ID=$user_ID$">$user_login$</a>',
					);


function coll_perm_checkbox( $row, $perm, $title, $id = NULL )
{
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
	$r .= ' onclick="merge_from_wide( this, '.$row->user_ID.' );" class="checkbox"
							value="1" title="'.$title.'" />';
	return $r;
}

function coll_perm_status_checkbox( $row, $perm_status, $title )
{
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
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Published'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_status_checkbox( {row}, \'published\', \''.TS_('Permission to read protected posts').'\' )%',
						'td_class' => 'center',
					);
$Results->cols[] = array(
						'th_group' => /* TRANS: SHORT table header on TWO lines */ T_('Can post/edit with following statuses:'),
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Protected'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_status_checkbox( {row}, \'protected\', \''.TS_('Permission to post into this blog with protected status').'\' )%',
						'td_class' => 'center',
					);
$Results->cols[] = array(
						'th_group' => /* TRANS: SHORT table header on TWO lines */ T_('Can post/edit with following statuses:'),
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Private'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_status_checkbox( {row}, \'private\', \''.TS_('Permission to post into this blog with private status').'\' )%',
						'td_class' => 'center',
					);
$Results->cols[] = array(
						'th_group' => /* TRANS: SHORT table header on TWO lines */ T_('Can post/edit with following statuses:'),
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Draft'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_status_checkbox( {row}, \'draft\', \''.TS_('Permission to post into this blog with draft status').'\' )%',
						'td_class' => 'center',
					);
$Results->cols[] = array(
						'th_group' => /* TRANS: SHORT table header on TWO lines */ T_('Can post/edit with following statuses:'),
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Deprecated'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_status_checkbox( {row}, \'deprecated\', \''.TS_('Permission to post into this blog with deprecated status').'\' )%',
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
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Edit<br />cats'),
						'th_class' => 'checkright',
						'order' => 'bloguser_perm_cats',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'perm_cats\', \''.TS_('Permission to edit categories for this blog').'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Edit<br />blog'),
						'th_class' => 'checkright',
						'order' => 'bloguser_perm_properties',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'perm_properties\', \''.TS_('Permission to edit blog properties').'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => /* TRANS: SHORT table header on TWO lines */ T_('Media directory'),
						'th' => T_('Upload'),
						'th_class' => 'checkright',
						'order' => 'bloguser_perm_media_upload',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'perm_media_upload\', \''.TS_('Permission to upload into blog\'s media folder').'\' )%',
						'td_class' => 'center',
					);
$Results->cols[] = array(
						'th_group' => /* TRANS: SHORT table header on TWO lines */ T_('Media directory'),
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

$Results->cols[] = array(
						'th' => '',
						'td' => '<a href="javascript:toggleall_wide(document.blogperm_checkchanges, $user_ID$ );merge_from_wide( document.blogperm_checkchanges, $user_ID$ ); setcheckallspan( $user_ID$ );" title="'.TS_('(un)selects all checkboxes using Javascript').'">
							<span id="checkallspan_$user_ID$">'.TS_('(un)check all').'</span>
						</a>',
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
						'td' => '<a href="?ctrl=users&amp;user_ID=$user_ID$">$user_login$</a>',
					);


function simple_coll_perm_radios( $row )
{
	$r = '';
	$user_easy_group = blogperms_get_easy2( $row );
	foreach( array(
								array( 'nomember', T_('Not Member') ),
								array( 'member', T_('Member') ),
								array( 'editor', T_('Editor') ),
								array( 'admin',  T_('Admin') ),
								array( 'custom',  T_('Custom') )
							) as $lkey => $easy_group )
	{
		$r .= '<input type="radio" id="blog_perm_easy_'.$row->user_ID.'_'.$lkey.'" name="blog_perm_easy_'.$row->user_ID.'" value="'.$easy_group[0].'"';
		if( $easy_group[0] == $user_easy_group )
		{
			$r .= ' checked="checked"';
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
// fp> WHAT??? can you give a permission when the global setting id OFF ????
// why is it talk about GROUPs when we are in the user perms tab?
echo '<p class="note center">'.T_('User group permissions override the media file permissions here!').'</p>';

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
 * Revision 1.6  2006/08/20 20:12:33  fplanque
 * param_() refactoring part 1
 *
 * Revision 1.5  2006/08/20 19:39:52  blueyed
 * usability: Note about perms
 *
 * Revision 1.4  2006/07/16 16:44:41  blueyed
 * Fixed td_colspan for results (typo+handling of "0")
 *
 * Revision 1.3  2006/07/03 21:04:49  fplanque
 * translation cleanup
 *
 * Revision 1.2  2006/06/25 21:15:03  fplanque
 * Heavy refactoring of the user blog perms so it stays manageable with a large number of users...
 *
 */
?>