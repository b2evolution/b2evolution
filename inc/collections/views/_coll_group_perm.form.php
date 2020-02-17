<?php
/**
 * This file implements the UI view (+more :/) for the management of collection permissions for each group.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
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

// Get SQL for collection group permissions:
$SQL = get_coll_group_perms_SQL( $edited_Blog, $keywords );

// Display wide layout:
?>

<div id="userlist_wide" class="clear">

<?php

$Results = new Results( $SQL->get(), 'section_' );

// Button to export user permissions into CSV file:
$Results->global_icon( T_('Export CSV'), '', $admin_url.'?ctrl=coll_settings&amp;action=export_groupperms&amp;blog='.$edited_Blog->ID.( empty( $keywords ) ? '' : '&amp;keywords='.urlencode( $keywords ) ), T_('Export CSV'), 3, 3, array( 'class' => 'action_icon btn-default' ) );

// Tell the Results class that we already have a form for this page:
$Results->Form = & $Form;

$Results->title = T_('Group permissions').get_manual_link('advanced-group-permissions');

$Results->filter_area = array(
	'submit' => 'actionArray[filter1]',
	'callback' => 'filter_collobjectlist',
	'url_ignore' => 'results_collgroup_page,keywords1,keywords2',
	);

$Results->register_filter_preset( 'all', T_('All users'), '?ctrl=coll_settings&amp;tab=permgroup&amp;blog='.$edited_Blog->ID, 'action=edit' );

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

$form_buttons = array();

// Make a hidden list of all displayed users:
$grp_IDs = array();
if( ! empty( $Results->rows ) )
{
	foreach( $Results->rows as $row )
	{
		$grp_IDs[] = $row->grp_ID;
	}
	$form_buttons[] = array( 'submit', 'actionArray[update]', T_('Save Changes!'), 'SaveButton' );
}
$Form->hidden( 'group_IDs', implode( ',', $grp_IDs) );

$Form->end_form( $form_buttons );

?>
