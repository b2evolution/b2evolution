<?php
/**
 * This file implements the UI view (+more :/) for the management of collection permissions for each group.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;

global $admin_url;

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
$SQL->SELECT( $edited_Blog->ID.' AS blog_ID, grp_ID, grp_name, grp_usage, grp_level, bloggroup_perm_poststatuses + 0 as perm_poststatuses, bloggroup_perm_item_type, bloggroup_perm_edit, bloggroup_can_be_assignee,'
	. 'bloggroup_perm_delcmts, bloggroup_perm_recycle_owncmts, bloggroup_perm_vote_spam_cmts, bloggroup_perm_cmtstatuses + 0 as perm_cmtstatuses, bloggroup_perm_edit_cmt,'
	. 'bloggroup_perm_delpost, bloggroup_perm_edit_ts, bloggroup_perm_meta_comment, bloggroup_perm_cats,'
	. 'bloggroup_perm_properties, bloggroup_perm_admin, bloggroup_perm_media_upload,'
	. 'bloggroup_perm_media_browse, bloggroup_perm_media_change, bloggroup_perm_analytics,'
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
	$Results->global_icon( T_('Reset all filters!'), 'reset_filters', $admin_url.'?ctrl=coll_settings&amp;tab=permgroup&amp;blog='.$edited_Blog->ID, T_('Reset filters'), 3, 3, array( 'class' => 'action_icon btn-warning' ) );
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

// Initialize Results object:
colls_groups_perms_results( $Results, array(
		'type'   => 'collection',
		'object' => $edited_Blog,
	) );

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
