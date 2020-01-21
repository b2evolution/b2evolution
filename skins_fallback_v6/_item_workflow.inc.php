<?php
/**
 * This is the template that displays the workflow properties of the viewed post
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $disp;

if( ( $disp == 'single' || $disp == 'page' ) &&
    isset( $Item ) && $Item->ID > 0 &&
    ! $Item->can_meta_comment() && // If user can write internal comment then we display the workflow form in the internal comment form instead of here
    $Item->can_edit_workflow() )
{ // Display workflow properties if current user can edit at least one workflow property:
	$Form = new Form( get_htsrv_url().'item_edit.php' );

	$Form->add_crumb( 'item' );
	$Form->hidden( 'blog', $Blog->ID );
	$Form->hidden( 'post_ID', $Item->ID );
	$Form->hidden( 'redirect_to', $Item->get_permanent_url() );

	$Form->begin_form( 'evo_item_workflow_form' );

	echo '<a name="workflow_panel"></a>';
	$Form->begin_fieldset( T_('Workflow properties') );

	echo '<div class="evo_item_workflow_form__fields">';

	$Item->display_workflow_field( 'status', $Form );

	$Item->display_workflow_field( 'user', $Form );

	$Item->display_workflow_field( 'priority', $Form );

	$Item->display_workflow_field( 'deadline', $Form );

	$Form->button( array( 'submit', 'actionArray[update_workflow]', T_('Update'), 'SaveButton' ) );

	echo '</div>';

	$Form->end_fieldset();

	$Form->end_form();
}

?>