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

$Form->switch_layout( 'linespan' );

$Form->switch_template_parts( array(
		'fieldstart' => '<div class="form-group comment-workflow-form" $ID$>',
	) );

$Form->begin_line( T_('Workflow') );

	$form_params = array(
			'hide_label'  => true,
		);
	$Item->display_workflow_field( 'status', $Form, $form_params );

	$form_params = array(
			'hide_label'  => true,
			'placeholder' => 'Assignee',
		);
	$Item->display_workflow_field( 'user', $Form, $form_params );

	$form_params = array(
		'hide_label'  => true,
	);
	$Item->display_workflow_field( 'priority', $Form, $form_params );

	$form_params = array(
		'hide_label'  => true,
	);
	$Item->display_workflow_field( 'deadline', $Form, $form_params );
	
$Form->end_line();

$Form->switch_layout( NULL );

if( $Comment->is_meta() )
{	// Display inputs of custom fields which are allowed to be updated with internal comment:
	$custom_fields = $Item->get_custom_fields_defs();

	if( isset( $Comment->item_custom_fields ) && is_array( $Comment->item_custom_fields ) )
	{	// Load item custom fields from session Comment on preview mode or after error in submitted comment form:
		foreach( $Comment->item_custom_fields as $field_key => $field_value )
		{
			if( isset( $Item->custom_fields[ $field_key ] ) )
			{	// Update value if custom field really exists for the Item:
				$Item->custom_fields[ $field_key ]['value'] = $field_value;
			}
		}
	}

	foreach( $custom_fields as $custom_field )
	{
		if( $custom_field['meta'] )
		{
			display_editable_custom_field( $custom_field['name'], $Form, $Item );
		}
	}
}
?>
