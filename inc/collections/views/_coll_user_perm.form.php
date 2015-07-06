<?php
/**
 * This file implements the UI view (+more :/) for the blogs permission management.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
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
global $UserSettings;
global $rsc_url, $htsrv_url, $admin_url;

global $Blog, $permission_to_change_admin;

$permission_to_change_admin = $current_User->check_perm( 'blog_admin', 'edit', false, $Blog->ID );

// Javascript:
echo '
<script type="text/javascript">var htsrv_url = "'.$htsrv_url.'";</script>';
require_js( 'collectionperms.js', 'rsc_url', false, true );

$Form = new Form( NULL, 'blogperm_checkchanges', 'post' );
$Form->formclass = 'form-inline';

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'tab', 'perm' );
$Form->hidden( 'blog', $edited_Blog->ID );

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
$SQL->SELECT( 'user_ID, user_login, user_level, bloguser_perm_poststatuses + 0 as perm_poststatuses, bloguser_perm_item_type, bloguser_perm_edit, bloguser_ismember, bloguser_can_be_assignee,'
	. 'bloguser_perm_delcmts, bloguser_perm_recycle_owncmts, bloguser_perm_vote_spam_cmts, bloguser_perm_cmtstatuses + 0 as perm_cmtstatuses, bloguser_perm_edit_cmt,'
	. 'bloguser_perm_delpost, bloguser_perm_edit_ts, bloguser_perm_cats,'
	. 'bloguser_perm_properties, bloguser_perm_admin, bloguser_perm_media_upload,'
	. 'bloguser_perm_media_browse, bloguser_perm_media_change' );
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

// Display wide layout:
?>

<div id="userlist_wide" class="clear">

<?php

$Results = new Results( $SQL->get(), 'colluser_' );

// Tell the Results class that we already have a form for this page:
$Results->Form = & $Form;

if( ! empty( $keywords ) )
{ // Display a button to reset the filters
	$Results->global_icon( T_('Reset all filters!'), 'reset_filters', $admin_url.'?ctrl=coll_settings&amp;tab=perm&amp;blog='.$Blog->ID, T_('Reset filters'), 3, 3, array( 'class' => 'action_icon btn-warning' ) );
}

$Results->title = T_('User permissions').get_manual_link('advanced-user-permissions');

$Results->filter_area = array(
	'submit' => 'actionArray[filter1]',
	'callback' => 'filter_collobjectlist',
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
						'td' => '~conditional( #bloguser_ismember#, \''.format_to_output( TS_('Members'), 'htmlattr' ).'\', \''.format_to_output( TS_('Non members'), 'htmlattr' ).'\' )~',
					);

/*
 * Colmun definitions:
 */
$Results->cols[] = array(
						'th' => T_('Login'),
						'order' => 'user_login',
						'td' => '%get_user_identity_link( #user_login#, NULL, "profile", "avatar_login" )%',
					);

$Results->cols[] = array(
						'th' => /* TRANS: User Level */ T_('L'),
						'order' => 'user_level',
						'td' => '$user_level$',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Is<br />member'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_checkbox( {row}, \'bloguser_\', \'ismember\', \''.format_to_output( TS_('Permission to read members posts'), 'htmlattr' ).'\', \'checkallspan_state_$user_ID$\' )%'.
						( $edited_Blog->get_setting( 'use_workflow' ) ? '%coll_perm_checkbox( {row}, \'bloguser_\', \'can_be_assignee\', \''.format_to_output( TS_('Items can be assigned to this user'), 'htmlattr' ).'\', \'checkallspan_state_$user_ID$\' )%' : '' ),
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => T_('Post Statuses'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_status_checkbox( {row}, \'bloguser_\', \'published\', \''.format_to_output( TS_('Permission to post into this blog with published status'), 'htmlattr' ).'\', \'post\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloguser_\', \'community\', \''.format_to_output( TS_('Permission to post into this blog with community status'), 'htmlattr' ).'\', \'post\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloguser_\', \'protected\', \''.format_to_output( TS_('Permission to post into this blog with members status'), 'htmlattr' ).'\', \'post\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloguser_\', \'private\', \''.format_to_output( TS_('Permission to post into this blog with private status'), 'htmlattr' ).'\', \'post\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloguser_\', \'review\', \''.format_to_output( TS_('Permission to post into this blog with review status'), 'htmlattr' ).'\', \'post\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloguser_\', \'draft\', \''.format_to_output( TS_('Permission to post into this blog with draft status'), 'htmlattr' ).'\', \'post\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloguser_\', \'deprecated\', \''.format_to_output( TS_('Permission to post into this blog with deprecated status'), 'htmlattr' ).'\', \'post\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloguser_\', \'redirected\', \''.format_to_output( TS_('Permission to post into this blog with redirected status'), 'htmlattr' ).'\', \'post\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => T_('Post Types'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_item_type( {row}, \'bloguser_\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Edit posts<br />/user level'),
						'th_class' => 'checkright',
						'default_dir' => 'D',
						'td' => '%coll_perm_edit( {row}, \'bloguser_\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Delete<br />posts'),
						'th_class' => 'checkright',
						'order' => 'bloguser_perm_delpost',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloguser_\', \'perm_delpost\', \''.format_to_output( TS_('Permission to delete posts in this blog'), 'htmlattr' ).'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Edit<br />TS'),
						'th_class' => 'checkright',
						'order' => 'bloguser_perm_edit_ts',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloguser_\', \'perm_edit_ts\', \''.format_to_output( TS_('Permission to edit timestamp on posts and comments in this blog'), 'htmlattr' ).'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => T_('Comment<br />statuses'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_status_checkbox( {row}, \'bloguser_\', \'published\', \''.format_to_output( TS_('Permission to comment into this blog with published status'), 'htmlattr' ).'\', \'comment\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloguser_\', \'community\', \''.format_to_output( TS_('Permission to comment into this blog with community status'), 'htmlattr' ).'\', \'comment\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloguser_\', \'protected\', \''.format_to_output( TS_('Permission to comment into this blog with members status'), 'htmlattr' ).'\', \'comment\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloguser_\', \'private\', \''.format_to_output( TS_('Permission to comment into this blog with private status'), 'htmlattr' ).'\', \'comment\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloguser_\', \'review\', \''.format_to_output( TS_('Permission to comment into this blog with review status'), 'htmlattr' ).'\', \'comment\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloguser_\', \'draft\', \''.format_to_output( TS_('Permission to comment into this blog with draft status'), 'htmlattr' ).'\', \'comment\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloguser_\', \'deprecated\', \''.format_to_output( TS_('Permission to comment into this blog with deprecated status'), 'htmlattr' ).'\', \'comment\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Edit cmts<br />/user level'),
						'th_class' => 'checkright',
						'default_dir' => 'D',
						'td' => '%coll_perm_edit_cmt( {row}, \'bloguser_\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Delete<br />commts'),
						'th_class' => 'checkright',
						'order' => 'bloguser_perm_delcmts',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloguser_\', \'perm_delcmts\', \''.format_to_output( TS_('Permission to delete comments on this blog'), 'htmlattr' ).'\' )%&nbsp;'.
								'%coll_perm_checkbox( {row}, \'bloguser_\', \'perm_recycle_owncmts\', \''.format_to_output( TS_('Permission to recycle comments on their own posts'), 'htmlattr' ).'\' )%&nbsp;'.
								'%coll_perm_checkbox( {row}, \'bloguser_\', \'perm_vote_spam_cmts\', \''.format_to_output( TS_('Permission to give a spam vote on any comment'), 'htmlattr' ).'\' )%&nbsp;',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => T_('Edit blog settings'),
						'th' => T_('Cats'),
						'th_title' => T_('Categories'),
						'th_class' => 'checkright',
						'order' => 'bloguser_perm_cats',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloguser_\', \'perm_cats\', \''.format_to_output( TS_('Permission to edit categories for this blog'), 'htmlattr' ).'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => T_('Edit blog settings'),
						'th' => /* TRANS: Short for blog features */  T_('Feat.'),
						'th_title' => T_('Features'),
						'th_class' => 'checkright',
						'order' => 'bloguser_perm_properties',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloguser_\', \'perm_properties\', \''.format_to_output( TS_('Permission to edit blog features'), 'htmlattr' ).'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => T_('Edit blog settings'),
						'th' => /* TRANS: Short for advanced */  T_('Adv.'),
						'th_title' => T_('Advanced/Administrative blog properties'),
						'th_class' => 'checkright',
						'order' => 'bloguser_perm_admin',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloguser_\', \'perm_admin\', \''.format_to_output( TS_('Permission to edit advanced/administrative blog properties'), 'htmlattr' ).'\' )%',
						'td_class' => 'center',
					);

// Media Directory:
$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Media<br />Dir'),
						'th_class' => 'checkright',
						'order' => 'bloguser_perm_media_upload',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloguser_\', \'perm_media_upload\', \''.format_to_output( TS_('Permission to upload into blog\'s media folder'), 'htmlattr' ).'\' )%'.
								'%coll_perm_checkbox( {row}, \'bloguser_\', \'perm_media_browse\', \''.format_to_output( TS_('Permission to browse blog\'s media folder'), 'htmlattr' ).'\' )%'.
								'%coll_perm_checkbox( {row}, \'bloguser_\', \'perm_media_change\', \''.format_to_output( TS_('Permission to change the blog\'s media folder content'), 'htmlattr' ).'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => '&nbsp;',
						'td' => '%perm_check_all( {row}, \'bloguser_\' )%',
						'td_class' => 'center',
					);

$Results->display();

echo '</div>';

// Permission note:
// fp> TODO: link
echo '<p class="note center">'.T_('Note: General group permissions may further restrict or extend any media folder permissions defined here.').'</p>';

// Make a hidden list of all displayed users:
$user_IDs = array();
foreach( $Results->rows as $row )
{
	$user_IDs[] = $row->user_ID;
}
$Form->hidden( 'user_IDs', implode( ',', $user_IDs) );

$Form->end_form( array( array( 'submit', 'actionArray[update]', T_('Save Changes!'), 'SaveButton' ) ) );

?>