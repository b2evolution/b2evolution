<?php
/**
 * This is the template that displays the Item workflow properties on Comment form
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Default params:
$params = array_merge( array(
		'Form'    => NULL,
		'Comment' => NULL,
	), $params );

if( empty( $params['Form'] ) || empty( $params['Form'] ) )
{	// Wrong request because no required objects:
	return;
}

if( ! $Item->can_edit_workflow() )
{	// Don't display workflow properties if current user has no permission:
	return;
}

$Form = $params['Form'];
$Comment = $params['Comment'];

if( isset( $Comment->item_workflow ) && is_array( $Comment->item_workflow ) )
{	// Load item workflow properties from session Comment on preview mode or after error in submitted comment form:
	foreach( $Comment->item_workflow as $field_key => $field_value )
	{
		$Item->set( $field_key, $field_value );
	}
}

$ItemStatusCache = & get_ItemStatusCache();
$ItemStatusCache->load_all();
$ItemTypeCache = & get_ItemTypeCache();
$current_ItemType = & $Item->get_ItemType();
$Form->select_options( 'item_st_ID', $ItemStatusCache->get_option_list( $Item->pst_ID, true, 'get_name', $current_ItemType->get_ignored_post_status() ), T_('Task status') );

// Load only first 21 users to know when we should display an input box instead of full users list:
$UserCache = & get_UserCache();
$UserCache->load_blogmembers( $Blog->ID, 21, false );
if( count( $UserCache->cache ) > 20 )
{	// Display a text input field with autocompletion if members more than 20:
	$assigned_User = & $UserCache->get_by_ID( $Item->get( 'assigned_user_ID' ), false, false );
	$Form->username( 'item_assigned_user_login', $assigned_User, T_('Assigned to'), '', 'only_assignees', array( 'size' => 10 ) );
}
else
{	// Display a select field if members less than 21:
	$Form->select_object( 'item_assigned_user_ID', NULL, $Item, T_('Assigned to'), '', true, '', 'get_assigned_user_options' );
}

$Form->select_input_array( 'item_priority', $Item->get( 'priority' ), item_priority_titles(), T_('Priority'), '', array( 'force_keys_as_values' => true ) );

if( $Blog->get_setting( 'use_deadline' ) )
{	// Display deadline fields only if it is enabled for collection:
	$Form->begin_line( T_('Deadline'), 'item_deadline' );

		$datedeadline = $Item->get( 'datedeadline' );
		$Form->date( 'item_deadline', $datedeadline, '' );

		$datedeadline_time = empty( $datedeadline ) ? '' : date( 'Y-m-d H:i', strtotime( $datedeadline ) );
		$Form->time( 'item_deadline_time', $datedeadline_time, T_('at'), 'hh:mm' );

	$Form->end_line();
}
?>