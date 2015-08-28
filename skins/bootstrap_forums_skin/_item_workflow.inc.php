<?php
/**
 * This is the template that displays the workflow properties of the viewed post
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_forums_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


if( ! empty( $Item ) &&
    is_logged_in() &&
    $Blog->get_setting( 'use_workflow' ) &&
    $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item ) )
{ // Display workflow properties if current user can edit this post:
	$Form = new Form();

	$Form->switch_layout( 'blockspan' );

	$Form->begin_form();

	$Form->switch_layout( NULL );

	$Form->begin_fieldset( T_('Workflow properties') );

	$Form->switch_layout( 'blockspan' );

	$Form->info( T_('Priority'), '<div class="task_priority_edit" rel="'.$Item->ID.'">'.item_td_task_cell( 'priority', $Item ).'</div>' );

	$Form->info( T_('Assigned to'), '<div class="task_assigned_edit" rel="'.$Item->ID.'">'.item_td_task_cell( 'assigned', $Item ).'</div>' );

	$Form->info( T_('Priority'), '<div class="task_status_edit" rel="'.$Item->ID.'">'.item_td_task_cell( 'status', $Item ).'</div>' );

	$Form->switch_layout( NULL );
	
	$Form->end_fieldset();

	$Form->switch_layout( 'blockspan' );

	$Form->end_form();

	// Print JS to edit a task priority
	echo_editable_column_js( array(
		'column_selector' => '.task_priority_edit',
		'ajax_url'        => get_secure_htsrv_url().'anon_async.php?action=item_task_edit&field=priority&'.url_crumb( 'itemtask' ),
		'options'         => item_priority_titles(),
		'new_field_name'  => 'new_priority',
		'ID_value'        => 'jQuery( this ).attr( "rel" )',
		'ID_name'         => 'post_ID' ) );

	// Print JS to edit a task assigned
	// Load current blog members into cache:
	$UserCache = & get_UserCache();
	// Load only first 21 users to know when we should display an input box instead of full users list
	$UserCache->load_blogmembers( $Blog->ID, 21, false );
	// Init this array only for <select> when we have less than 21 users, otherwise we use <input> field with autocomplete feature
	$field_type = count( $UserCache->cache ) < 21 ? 'select' : 'text';

	$task_assignees = array( 0 => T_('No user') );
	if( $field_type == 'select' )
	{
		foreach( $UserCache->cache as $User )
		{
			$task_assignees[ $User->ID ] = $User->login;
		}
	}
	echo_editable_column_js( array(
		'column_selector' => '.task_assigned_edit',
		'ajax_url'        => get_secure_htsrv_url().'anon_async.php?action=item_task_edit&field=assigned&'.url_crumb( 'itemtask' ),
		'options'         => $task_assignees,
		'new_field_name'  => $field_type == 'select' ? 'new_assigned_ID' : 'new_assigned_login',
		'ID_value'        => 'jQuery( this ).attr( "rel" )',
		'ID_name'         => 'post_ID',
		'field_type'      => $field_type,
		'field_class'     => 'autocomplete_login only_assignees',
		'null_text'       => TS_('No user') ) );

	// Print JS to edit a task status
	$ItemStatusCache = & get_ItemStatusCache();
	$ItemStatusCache->load_all();
	$task_statuses = array( 0 => T_('No status') );
	foreach( $ItemStatusCache->cache as $ItemStatus )
	{
		// Add '_' to don't break a sorting by name on jeditable:
		$task_statuses[ '_'.$ItemStatus->ID ] = $ItemStatus->name;
	}
	echo_editable_column_js( array(
		'column_selector' => '.task_status_edit',
		'ajax_url'        => get_secure_htsrv_url().'anon_async.php?action=item_task_edit&field=status&'.url_crumb( 'itemtask' ),
		'options'         => $task_statuses,
		'new_field_name'  => 'new_status',
		'ID_value'        => 'jQuery( this ).attr( "rel" )',
		'ID_name'         => 'post_ID' ) );
}

?>