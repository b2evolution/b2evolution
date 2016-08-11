<?php
/**
 * This file implements the UI view (+more :/) for the blogs permission management.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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
global $rsc_url, $admin_url;

global $Blog, $permission_to_change_admin;

$permission_to_change_admin = $current_User->check_perm( 'blog_admin', 'edit', false, $Blog->ID );

// Javascript:
echo '
<script type="text/javascript">var htsrv_url = "'.get_htsrv_url().'";</script>';
require_js( 'collectionperms.js', 'rsc_url', false, true );

$Form = new Form( NULL, 'blogperm_checkchanges', 'post' );
$Form->formclass = 'form-inline';

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'tab', 'permgroup' );
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
$SQL->SELECT( 'grp_ID, grp_name, grp_usage, grp_level, bloggroup_perm_poststatuses + 0 as perm_poststatuses, bloggroup_perm_item_type, bloggroup_perm_edit, bloggroup_can_be_assignee,'
	. 'bloggroup_perm_delcmts, bloggroup_perm_recycle_owncmts, bloggroup_perm_vote_spam_cmts, bloggroup_perm_cmtstatuses + 0 as perm_cmtstatuses, bloggroup_perm_edit_cmt,'
	. 'bloggroup_perm_delpost, bloggroup_perm_edit_ts, bloggroup_perm_meta_comment, bloggroup_perm_cats,'
	. 'bloggroup_perm_properties, bloggroup_perm_admin, bloggroup_perm_media_upload,'
	. 'bloggroup_perm_media_browse, bloggroup_perm_media_change,'
	. 'IF( ( grp_perm_blogs = "viewall" OR grp_perm_blogs = "editall" ), 1, bloggroup_ismember ) AS bloggroup_ismember' );
$SQL->FROM( 'T_groups' );
$SQL->FROM_add( 'LEFT JOIN T_coll_group_perms ON ( grp_ID = bloggroup_group_ID AND bloggroup_blog_ID = '.$edited_Blog->ID.' )' );
$SQL->ORDER_BY( 'bloggroup_ismember DESC, *, grp_name, grp_ID' );

if( !empty( $keywords ) )
{
	$SQL->add_search_field( 'grp_name' );
	$SQL->WHERE_kw_search( $keywords, 'AND' );
}

// Display wide layout:
?>

<div id="userlist_wide" class="clear">

<?php

$Results = new Results( $SQL->get(), 'collgroup_' );

if( ! empty( $keywords ) )
{ // Display a button to reset the filters
	$Results->global_icon( T_('Reset all filters!'), 'reset_filters', $admin_url.'?ctrl=coll_settings&amp;tab=permgroup&amp;blog='.$Blog->ID, T_('Reset filters'), 3, 3, array( 'class' => 'action_icon btn-warning' ) );
}

// Tell the Results class that we already have a form for this page:
$Results->Form = & $Form;

$Results->title = T_('Group permissions').get_manual_link('advanced-group-permissions');

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
						'td' => '~conditional( #bloggroup_ismember#, \''.format_to_output( T_('Members'), 'htmlattr' ).'\', \''.format_to_output( T_('Non members'), 'htmlattr' ).'\' )~',
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

function grp_perm_row_name( $grp_ID, $grp_name, $grp_usage )
{
	global $admin_url;

	if( $grp_usage == 'primary' )
	{	// Primary group
		$grp_class = 'label-primary';
		$grp_title = T_('Primary Group');
	}
	else
	{	// Secondary group
		$grp_class = 'label-info';
		$grp_title = T_('Secondary Group');
	}

	return '<a href="'.$admin_url.'?ctrl=users&amp;filter=new&amp;'.( $grp_usage == 'primary' ? 'group' : 'group2' ).'='.$grp_ID
			.'" title="'.format_to_output( $grp_title, 'htmlattr' ).'" class="label '.$grp_class.'">'
			.get_icon( 'contacts', 'imgtag', array( 'style' => 'top:1px;position:relative' ) ).' '.$grp_name
		.'</a>';
}
$Results->cols[] = array(
						'th' => T_('Group'),
						'order' => 'grp_name',
						'td' => '%grp_perm_row_name( #grp_ID#, #grp_name#, #grp_usage# )%',
					);

$Results->cols[] = array(
						'th' => /* TRANS: Group Level */ T_('L'),
						'order' => 'grp_level',
						'td' => '$grp_level$',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ sprintf( T_('Member of<br />%s'), $Blog->get( 'shortname' ) ),
						'th_class' => 'checkright',
						'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'ismember\', \''.format_to_output( T_('Permission to read members posts'), 'htmlattr' ).'\', \'checkallspan_state_$grp_ID$\' )%'.
						( $edited_Blog->get_setting( 'use_workflow' ) ? '%coll_perm_checkbox( {row}, \'bloggroup_\', \'can_be_assignee\', \''.format_to_output( T_('Items can be assigned to members of this group'), 'htmlattr' ).'\', \'checkallspan_state_$grp_ID$\' )%' : '' ),
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => T_('Permissions on Posts'),
						'th' => T_('Post Statuses'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'published\', \''.format_to_output( T_('Permission to post into this blog with published status'), 'htmlattr' ).'\', \'post\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'community\', \''.format_to_output( T_('Permission to post into this blog with community status'), 'htmlattr' ).'\', \'post\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'protected\', \''.format_to_output( T_('Permission to post into this blog with members status'), 'htmlattr' ).'\', \'post\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'private\', \''.format_to_output( T_('Permission to post into this blog with private status'), 'htmlattr' ).'\', \'post\' )%'.
								'<span style="display: inline-block; min-width: 5px;"></span>'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'review\', \''.format_to_output( T_('Permission to post into this blog with review status'), 'htmlattr' ).'\', \'post\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'draft\', \''.format_to_output( T_('Permission to post into this blog with draft status'), 'htmlattr' ).'\', \'post\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'deprecated\', \''.format_to_output( T_('Permission to post into this blog with deprecated status'), 'htmlattr' ).'\', \'post\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'redirected\', \''.format_to_output( T_('Permission to post into this blog with redirected status'), 'htmlattr' ).'\', \'post\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => T_('Permissions on Posts'),
						'th' => T_('Post Types'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_item_type( {row}, \'bloggroup_\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => T_('Permissions on Posts'),
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Edit posts<br />/user level'),
						'th_class' => 'checkright',
						'default_dir' => 'D',
						'td' => '%coll_perm_edit( {row}, \'bloggroup_\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => T_('Permissions on Posts'),
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Delete<br />posts'),
						'th_class' => 'checkright',
						'order' => 'bloggroup_perm_delpost',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_delpost\', \''.format_to_output( T_('Permission to delete posts in this blog'), 'htmlattr' ).'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => T_('Permissions on Posts'),
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Edit<br />TS'),
						'th_class' => 'checkright',
						'order' => 'bloggroup_perm_edit_ts',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_edit_ts\', \''.format_to_output( T_('Permission to edit timestamp on posts and comments in this blog'), 'htmlattr' ).'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => T_('Permissions on Comments'),
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Comment<br />statuses'),
						'th_class' => 'checkright',
						'td' => '%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'published\', \''.format_to_output( T_('Permission to comment into this blog with published status'), 'htmlattr' ).'\', \'comment\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'community\', \''.format_to_output( T_('Permission to comment into this blog with community status'), 'htmlattr' ).'\', \'comment\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'protected\', \''.format_to_output( T_('Permission to comment into this blog with members status'), 'htmlattr' ).'\', \'comment\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'private\', \''.format_to_output( T_('Permission to comment into this blog with private status'), 'htmlattr' ).'\', \'comment\' )%'.
								'<span style="display: inline-block; min-width: 5px;"></span>'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'review\', \''.format_to_output( T_('Permission to comment into this blog with review status'), 'htmlattr' ).'\', \'comment\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'draft\', \''.format_to_output( T_('Permission to comment into this blog with draft status'), 'htmlattr' ).'\', \'comment\' )%'.
								'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'deprecated\', \''.format_to_output( T_('Permission to comment into this blog with deprecated status'), 'htmlattr' ).'\', \'comment\' )%'.
								'<span style="display: inline-block; min-width: 5px;"></span>'.
								'%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_meta_comment\', \''.format_to_output( T_('Permission to post meta comments into this blog'), 'htmlattr' ).'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => T_('Permissions on Comments'),
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Edit cmts<br />/user level'),
						'th_class' => 'checkright',
						'default_dir' => 'D',
						'td' => '%coll_perm_edit_cmt( {row}, \'bloggroup_\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => T_('Permissions on Comments'),
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Delete<br />cmts'),
						'th_class' => 'checkright',
						'order' => 'bloggroup_perm_delcmts',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_delcmts\', \''.format_to_output( T_('Permission to delete comments on this blog'), 'htmlattr' ).'\' )%&nbsp;'.
								'%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_recycle_owncmts\', \''.format_to_output( T_('Permission to recycle comments on their own posts'), 'htmlattr' ).'\' )%&nbsp;'.
								'%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_vote_spam_cmts\', \''.format_to_output( T_('Permission to give a spam vote on any comment'), 'htmlattr' ).'\' )%&nbsp;',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => T_('Perms on Coll.'),
						'th' => T_('Cats'),
						'th_title' => T_('Categories'),
						'th_class' => 'checkright',
						'order' => 'bloggroup_perm_cats',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_cats\', \''.format_to_output( T_('Permission to edit categories for this blog'), 'htmlattr' ).'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => T_('Perms on Coll.'),
						'th' => /* TRANS: Short for blog features */  T_('Feat.'),
						'th_title' => T_('Features'),
						'th_class' => 'checkright',
						'order' => 'bloggroup_perm_properties',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_properties\', \''.format_to_output( T_('Permission to edit blog features'), 'htmlattr' ).'\' )%',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th_group' => T_('Perms on Coll.'),
						'th' => get_admin_badge( 'coll', '#', T_('Coll.<br />Admin'), T_('Check this to give Collection Admin permission.') ),
						'th_title' => T_('Advanced/Administrative blog properties'),
						'th_class' => 'checkright',
						'order' => 'bloggroup_perm_admin',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_admin\', \''.format_to_output( T_('Permission to edit advanced/administrative blog properties'), 'htmlattr' ).'\' )%',
						'td_class' => 'center',
					);

// Media Directory:
$Results->cols[] = array(
						'th' => /* TRANS: SHORT table header on TWO lines */ T_('Media<br />Dir'),
						'th_class' => 'checkright',
						'order' => 'bloggroup_perm_media_upload',
						'default_dir' => 'D',
						'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_media_upload\', \''.format_to_output( T_('Permission to upload into blog\'s media folder'), 'htmlattr' ).'\' )%'.
								'%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_media_browse\', \''.format_to_output( T_('Permission to browse blog\'s media folder'), 'htmlattr' ).'\' )%'.
								'%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_media_change\', \''.format_to_output( T_('Permission to change the blog\'s media folder content'), 'htmlattr' ).'\' )%',
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

// Make a hidden list of all displayed users:
$grp_IDs = array();
foreach( $Results->rows as $row )
{
	$grp_IDs[] = $row->grp_ID;
}
$Form->hidden( 'group_IDs', implode( ',', $grp_IDs) );

$Form->end_form( array( array( 'submit', 'actionArray[update]', T_('Save Changes!'), 'SaveButton' ) ) );

?>
