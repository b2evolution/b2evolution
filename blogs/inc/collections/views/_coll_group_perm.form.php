<?php
/**
 * This file implements the UI view (+more :/) for the blogs permission management.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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

// Javascript:
echo '
<script type="text/javascript">var htsrv_url = "'.$htsrv_url.'";</script>
<script type="text/javascript" src="'.$rsc_url.'js/collectionperms.js"></script>';

$Form = new Form( NULL, 'blogperm_checkchanges', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'tab', 'permgroup' );
$Form->hidden( 'blog', $edited_Blog->ID );

$Form->begin_fieldset( T_('Group permissions').get_manual_link('group_permissions') );


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
$SQL->SELECT( 'grp_ID, grp_name, bloggroup_perm_poststatuses + 0 as perm_poststatuses, bloggroup_perm_edit, bloggroup_ismember,'
	. 'bloggroup_perm_delcmts, bloggroup_perm_recycle_owncmts, bloggroup_perm_vote_spam_cmts, bloggroup_perm_cmtstatuses + 0 as perm_cmtstatuses, bloggroup_perm_edit_cmt,'
	. 'bloggroup_perm_delpost, bloggroup_perm_edit_ts, bloggroup_perm_cats,'
	. 'bloggroup_perm_properties, bloggroup_perm_admin, bloggroup_perm_media_upload,'
	. 'bloggroup_perm_media_browse, bloggroup_perm_media_change, bloggroup_perm_page,'
	. 'bloggroup_perm_intro, bloggroup_perm_podcast, bloggroup_perm_sidebar' );
$SQL->FROM( 'T_groups LEFT JOIN T_coll_group_perms ON
			( grp_ID = bloggroup_group_ID AND bloggroup_blog_ID = '.$edited_Blog->ID.' )' );
$SQL->ORDER_BY( 'bloggroup_ismember DESC, *, grp_name, grp_ID' );

if( !empty( $keywords ) )
{
	$SQL->add_search_field( 'grp_name' );
	$SQL->WHERE_keywords( $keywords, 'AND' );
}

// Display wide layout:
?>

<div id="userlist_wide" class="clear">

<?php

$Results = new Results( $SQL->get(), 'collgroup_' );

// Tell the Results class that we already have a form for this page:
$Results->Form = & $Form;

$Results->title = T_('Group permissions');

$Results->filter_area = array(
	'submit' => 'actionArray[filter1]',
	'callback' => 'filter_collobjectlist',
	'url_ignore' => 'results_collgroup_page,keywords1,keywords2',
	'presets' => array(
		'all' => array( T_('All users'), regenerate_url( 'action,results_collgroup_page,keywords1,keywords2', 'action=edit' ) ),
		)
	);

/*
 * Grouping params:
 */
$Results->group_by = 'bloggroup_ismember';
$Results->ID_col = 'grp_ID';

/*
 * Group columns:
 */
$Results->grp_cols[] = array(
						'td_colspan' => 0,  // nb_cols
						'td' => '~conditional( #bloggroup_ismember#, \''.TS_('Members').'\', \''.TS_('Non members').'\' )~',
					);

/*
 * Colmun definitions:
 */
$Results->cols[] = array(
						'th' => T_('ID'),
						'order' => 'grp_ID',
						'td' => '$grp_ID$',
						'th_class' => 'shrinkwrap',
						'td_class' => 'right',
					);

$Results->cols[] = array(
						'th' => T_('Group'),
						'order' => 'grp_name',
						'td' => '<a href="?ctrl=users&amp;grp_ID=$grp_ID$">$grp_name$</a>',
					);

$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Is<br />member'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'ismember\', \''.TS_('Permission to read members posts').'\', \'checkallspan_state_$grp_ID$\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => T_('Post statuses'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'published\', \''.TS_('Permission to post into this blog with published status').'\', \'post\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'community\', \''.TS_('Permission to post into this blog with community status').'\', \'post\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'protected\', \''.TS_('Permission to post into this blog with members status').'\', \'post\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'review\', \''.TS_('Permission to post into this blog with review status').'\', \'post\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'private\', \''.TS_('Permission to post into this blog with private status').'\', \'post\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'draft\', \''.TS_('Permission to post into this blog with draft status').'\', \'post\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'deprecated\', \''.TS_('Permission to post into this blog with deprecated status').'\', \'post\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'redirected\', \''.TS_('Permission to post into this blog with redirected status').'\', \'post\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => T_('Post types'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_page\', \''.TS_('Permission to create pages').'\' )%'.
								'%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_intro\', \''.TS_('Permission to create intro posts (Intro-* post types)').'\' )%'.
								'%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_podcast\', \''.TS_('Permission to create podcast episodes').'\' )%'.
								'%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_sidebar\', \''.TS_('Permission to create sidebar links').'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Edit posts<br />/user level'),
						'th_class' => 'checkright',
						'default_dir' => 'D',
						'td' => '%coll_perm_edit( {row}, \'bloggroup_\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Delete<br />posts'),
						'th_class' => 'checkright',
						'order' => 'bloggroup_perm_delpost',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_delpost\', \''.TS_('Permission to delete posts in this blog').'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Edit<br />TS'),
						'th_class' => 'checkright',
						'order' => 'bloggroup_perm_edit_ts',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_edit_ts\', \''.TS_('Permission to edit timestamp on posts and comments in this blog').'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Comment<br />statuses'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'published\', \''.TS_('Permission to comment into this blog with published status').'\', \'comment\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'community\', \''.TS_('Permission to comment into this blog with community status').'\', \'comment\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'protected\', \''.TS_('Permission to comment into this blog with members status').'\', \'comment\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'review\', \''.TS_('Permission to comment into this blog with review status').'\', \'comment\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'private\', \''.TS_('Permission to comment into this blog with private status').'\', \'comment\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'draft\', \''.TS_('Permission to comment into this blog with draft status').'\', \'comment\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'deprecated\', \''.TS_('Permission to comment into this blog with deprecated status').'\', \'comment\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Edit cmts<br />/user level'),
						'th_class' => 'checkright',
						'default_dir' => 'D',
						'td' => '%coll_perm_edit_cmt( {row}, \'bloggroup_\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Delete<br />cmts'),
						'th_class' => 'checkright',
						'order' => 'bloggroup_perm_delcmts',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_delcmts\', \''.TS_('Permission to delete comments on this blog').'\' )%&nbsp;'.
								'%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_recycle_owncmts\', \''.TS_('Permission to recycle comments on their own posts').'\' )%&nbsp;'.
								'%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_vote_spam_cmts\', \''.TS_('Permission to give a spam vote on any comment').'\' )%&nbsp;',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => T_('Edit blog settings'),
						'th' => T_('Cats'),
						'th_class' => 'checkright',
						'order' => 'bloggroup_perm_cats',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_cats\', \''.TS_('Permission to edit categories for this blog').'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => T_('Edit blog settings'),
						'th' => /* TRANS: Short for blog features */  T_('Feat.'),
						'th_class' => 'checkright',
						'order' => 'bloggroup_perm_properties',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_properties\', \''.TS_('Permission to edit blog features').'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => T_('Edit blog settings'),
						'th' => /* TRANS: Short for advanced */  T_('Adv.'),
						'th_class' => 'checkright',
						'order' => 'bloggroup_perm_admin',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_admin\', \''.TS_('Permission to edit advanced/administrative blog properties').'\' )%',
						'td_class' => 'center',
					);

// Media Directory:
$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Media<br />Dir'),
						'th_class' => 'checkright',
						'order' => 'bloggroup_perm_media_upload',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_media_upload\', \''.TS_('Permission to upload into blog\'s media folder').'\' )%'.
								'%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_media_browse\', \''.TS_('Permission to browse blog\'s media folder').'\' )%'.
								'%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_media_change\', \''.TS_('Permission to change the blog\'s media folder content').'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => '&nbsp;',
						'td' => '%perm_check_all( {row}, \'bloggroup_\' )%',
						'td_class' => 'center',
					);

$Results->display();

echo '</div>';

// Permission note:
// fp> TODO: link
echo '<p class="note center">'.T_('Note: General group permissions may further restrict or extend any media folder permissions defined here.').'</p>';

$Form->end_fieldset();

// Make a hidden list of all displayed users:
$grp_IDs = array();
foreach( $Results->rows as $row )
{
	$grp_IDs[] = $row->grp_ID;
}
$Form->hidden( 'group_IDs', implode( ',', $grp_IDs) );

$Form->end_form( array( array( 'submit', 'actionArray[update]', T_('Update'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

/*
 * $Log$
 * Revision 1.25  2013/11/06 08:03:58  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>