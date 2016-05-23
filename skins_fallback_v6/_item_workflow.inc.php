<?php
/**
 * This is the template that displays the workflow properties of the viewed post
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $disp;

if( ( $disp == 'single' || $disp == 'page' ) &&
    ! empty( $Item ) &&
    is_logged_in() &&
    $Blog->get_setting( 'use_workflow' ) &&
    $current_User->check_perm( 'blog_can_be_assignee', 'edit', false, $Blog->ID ) &&
    $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item ) )
{ // Display workflow properties if current user can edit this post:
	$Form = new Form( get_samedomain_htsrv_url().'item_edit.php' );

	$Form->add_crumb( 'item' );
	$Form->hidden( 'blog', $Blog->ID );
	$Form->hidden( 'post_ID', $Item->ID );
	$Form->hidden( 'redirect_to', $Item->get_permanent_url() );

	$Form->begin_form( 'evo_item_workflow_form' );

	echo '<a name="workflow_panel"></a>';
	$Form->begin_fieldset( T_('Workflow properties') );

	echo '<div class="evo_item_workflow_form__fields">';

	$Form->select_input_array( 'item_priority', $Item->priority, item_priority_titles(), T_('Priority'), '', array( 'force_keys_as_values' => true ) );

	// Load current blog members into cache:
	$UserCache = & get_UserCache();
	// Load only first 21 users to know when we should display an input box instead of full users list
	$UserCache->load_blogmembers( $Blog->ID, 21, false );

	if( count( $UserCache->cache ) > 20 )
	{
		$assigned_User = & $UserCache->get_by_ID( $Item->get( 'assigned_user_ID' ), false, false );
		$Form->username( 'item_assigned_user_login', $assigned_User, T_('Assigned to'), '', 'only_assignees', array( 'size' => 10 ) );
	}
	else
	{
		$Form->select_object( 'item_assigned_user_ID', NULL, $Item, T_('Assigned to'),
												'', true, '', 'get_assigned_user_options' );
	}

	$ItemStatusCache = & get_ItemStatusCache();
	$ItemStatusCache->load_all();
	$Form->select_options( 'item_st_ID', $ItemStatusCache->get_option_list( $Item->pst_ID, true ), T_('Task status') );

	$Form->date( 'item_deadline', $Item->get('datedeadline'), T_('Deadline') );

	$Form->button( array( 'submit', 'actionArray[update_workflow]', T_('Update'), 'SaveButton' ) );

	echo '</div>';

	$Form->end_fieldset();

	$Form->end_form();
}

?>