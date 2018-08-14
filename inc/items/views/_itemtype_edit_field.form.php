<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2018 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$Form = new Form( NULL, 'itemtype_edit_field' );

$Form->begin_form( 'fform' );

$Form->hidden( 'itcf_ID', get_param( 'itcf_ID' ) );

// Order:
$Form->text( 'itcf_order', get_param( 'itcf_order' ), 6, T_('Order'), '', 11 );

// Title:
$Form->text( 'itcf_label', get_param( 'itcf_label' ), 120, T_('Title'), '', 255 );

// Name:
$Form->text( 'itcf_name', get_param( 'itcf_name' ), 60, T_('Name'), '', 255 );

// Type:
$Form->info( T_('Type'), get_item_type_field_type_title( get_param( 'itcf_type' ) ) );

// Format:
switch( get_param( 'itcf_type' ) )
{
	case 'double':
	case 'computed':
	case 'separator':
		$Form->text( 'itcf_format', get_param( 'itcf_format' ), 60, T_('Format'), '', 2000 );
		break;
	case 'image':
		global $thumbnail_sizes;
		$Form->select_input_array( 'itcf_format', get_param( 'itcf_format' ), array_keys( $thumbnail_sizes ), T_('Format') );
		break;
}

// Formula:
if( get_param( 'itcf_type' ) == 'computed' )
{
	$Form->text( 'itcf_formula', get_param( 'itcf_formula' ), 100, T_('Formula'), '', 2000 );
}

// Note:
$Form->text( 'itcf_note', get_param( 'itcf_note' ), 60, T_('Note'), '', 255 );

// Public:
$Form->checkbox( 'itcf_public', get_param( 'itcf_public' ), T_('Public') );

// Cell class:
$Form->text( 'itcf_cell_class', get_param( 'itcf_cell_class' ), 60, T_('Cell class'), sprintf( T_('Enter class names such as %s etc. (Separate with space)'), '<code>left</code> <code>center</code> <code>right</code> <code>red</code>' ), 255 );

// Link:
if( ! in_array( get_param( 'itcf_type' ), array( 'text', 'html', 'separator' ) ) )
{
	$Form->select_input_array( 'itcf_link', get_param( 'itcf_link' ), get_item_type_field_linkto_options( get_param( 'itcf_type' ) ), T_('Link'), '', array( 'force_keys_as_values' => true ) );

	// Link class:
	$Form->text( 'itcf_link_class', get_param( 'itcf_link_class' ), 60, T_('Link class'), sprintf( T_('Enter class names such as %s etc. (Separate with space)'), '<code>btn btn-sm btn-info</code>' ), 255 );
}

// Highlight options:
if( get_param( 'itcf_type' ) != 'separator' )
{
	$Form->select_input_array( 'itcf_line_highlight', get_param( 'itcf_line_highlight' ), get_item_type_field_highlight_options( 'line' ), T_('Line highlight'), '', array( 'force_keys_as_values' => true ) );
	$Form->select_input_array( 'itcf_green_highlight', get_param( 'itcf_green_highlight' ), get_item_type_field_highlight_options( 'green' ), T_('Green highlight'), '', array( 'force_keys_as_values' => true ) );
	$Form->select_input_array( 'itcf_red_highlight', get_param( 'itcf_red_highlight' ), get_item_type_field_highlight_options( 'red' ), T_('Red highlight'), '', array( 'force_keys_as_values' => true ) );
}

// Description:
$Form->textarea( 'itcf_description', get_param( 'itcf_description' ), 3, T_('Description') );

$Form->end_form( array( array( 'submit', 'actionArray[select_custom_fields]', T_('Update'), 'SaveButton' ) ) );
?>