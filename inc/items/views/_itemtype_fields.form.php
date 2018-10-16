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

load_class( 'items/model/_itemtype.class.php', 'ItemType' );

global $edited_Itemtype, $custom_fields;

$Form = new Form( NULL, 'itemtype_select_fields' );

$Form->begin_form( 'fform' );

$source_custom_fields = $edited_Itemtype->get_custom_fields();

$custom_field_type_titles = array(
		'double'   => T_('Numeric'),
		'computed' => T_('Computed'),
		'varchar'  => T_('String'),
		'text'     => T_('Text'),
		'html'     => 'HTML',
		'url'      => T_('URL'),
		'image'    => T_('Image'),
		'separator'=> T_('Separator'),
	);

$custom_field_options = array();
foreach( $source_custom_fields as $source_custom_field )
{
	$source_custom_field_data = array();
	foreach( $source_custom_field as $col_key => $col_value )
	{
		if( ! in_array( $col_key, array( 'ID', 'ityp_ID' ) ) )
		{
			$source_custom_field_data['data-'.$col_key] = ( $col_value === NULL ? '' : $col_value );
		}
	}
	$custom_field_options[] = array( 'custom_field', $source_custom_field['name'],
		'<b>'.$source_custom_field['label'].'</b> '.
		'<code>'.$source_custom_field['name'].'</code> '.
		'('.$custom_field_type_titles[ $source_custom_field['type'] ].')'.
		'<input type="hidden" name="cf_data"'.get_field_attribs_as_string( $source_custom_field_data ).' />',
		! in_array( $source_custom_field['name'], $custom_fields ), // check automatically only fields which is not added on the requested form yet
		false,
		( $source_custom_field['public'] ? T_('Public') : T_('Private') ) );
}

$Form->checklist( $custom_field_options, '', T_('Select fields'), false, false, array(
		'input_prefix' =>
			'<input type="button" class="btn btn-default btn-xs" value="'.T_('Check all').'" onclick="jQuery( this ).closest( \'form\' ).find( \'input[type=checkbox]\' ).prop( \'checked\', true )" /> '.
			'<input type="button" class="btn btn-default btn-xs" value="'.T_('Uncheck all').'" onclick="jQuery( this ).closest( \'form\' ).find( \'input[type=checkbox]\' ).prop( \'checked\', false )" /> '.
			'<input type="button" class="btn btn-default btn-xs" value="'.T_('Reverse').'" onclick="jQuery( this ).closest( \'form\' ).find( \'input[type=checkbox]\' ).each( function() { jQuery( this ).prop( \'checked\', ! jQuery( this ).prop( \'checked\' ) ) } );"  />'
) );

$Form->end_form( array( array( 'submit', 'actionArray[select_custom_fields]', T_('Add fields now!'), 'SaveButton' ) ) );
?>