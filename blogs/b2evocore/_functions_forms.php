<?php
/**
 * Fast Form handling
 * 
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * form_text(-)
 */
function form_text( $field_name, $field_value, $field_size, $field_label, $field_note = '', $field_maxlength = 0 , $field_class = '', $inputtype = 'text' )
{
	if( $field_maxlength == 0 )
		$field_maxlength = $field_size;

	echo '<fieldset>';
	echo '  <div class="label"><label for="', $field_name, '">', $field_label, ':</label></div>';
	echo '  <div class="input"><input type="', $inputtype, '" name="', $field_name, '" id="', $field_name, '" size="', $field_size, '" maxlength="', $field_maxlength, '" value="', format_to_output($field_value, 'formvalue'),'"';
	if( !empty($field_class) )
	{
		echo ' class="', $field_class,'"';
	}
	echo '/>';
	echo '  <span class="notes">', $field_note, '</span></div>';
	echo "</fieldset>\n\n";
}


/*
 * form_text_tr(-)
 */
function form_text_tr( $field_name, $field_value, $field_size, $field_label, $field_note = '', $field_maxlength = 0 , $field_class = '' )
{
	if( $field_maxlength == 0 )
		$field_maxlength = $field_size;

	echo '<tr>';
	echo '  <td align="right"><label for="', $field_name, '"><strong>', $field_label, ':</strong></label></td>';
	echo '  <td><input type="text" name="', $field_name, '" id="', $field_name, '" size="', $field_size, '" maxlength="', $field_maxlength, '" value="', format_to_output($field_value, 'formvalue'),'"';
	if( !empty($field_class) )
	{
		echo ' class="', $field_class,'"';
	}
	echo '/>';
	echo '  <small>', $field_note, '</small></td>';
	echo "</tr>\n\n";
}


/*
 * form_select(-)
 */
function form_select(
	$field_name,
	$field_value,
	$field_list_callback,
	$field_label,
	$field_note = '',
	$field_class = '' )
{
	echo '<fieldset>';
	echo '  <div class="label"><label for="', $field_name, '">', $field_label, (($field_label != '') ? ':' : ''), '</label></div>';
	echo '  <div class="input"><select name="', $field_name, '" id="', $field_name, '"';
	if( !empty($field_class) )
	{
		echo ' class="', $field_class,'"';
	}
	echo '>';
	eval( $field_list_callback( $field_value ) );
	echo '  </select>';
	echo '  <span class="notes">', $field_note, '</span></div>';
	echo "</fieldset>\n\n";
}


/*
 * form_select_object(-)
 *
 * same as select but on cache object
 */
function form_select_object(
	$field_name,
	$field_value,
	& $field_object,
	$field_label,
	$field_note = '',
	$allow_none = false,
	$field_class = '' )
{
	echo '<fieldset>';
	echo '  <div class="label"><label for="', $field_name, '">', $field_label, ':</label></div>';
	echo '  <div class="input"><select name="', $field_name, '" id="', $field_name, '"';
	if( !empty($field_class) )
	{
		echo ' class="', $field_class,'"';
	}
	echo '>';
	$field_object->option_list( $field_value, $allow_none );
	echo '  </select>';
	echo '  <span class="notes">', $field_note, '</span></div>';
	echo "</fieldset>\n\n";
}


/*
 * form_radio(-)
 */
function form_radio(
	$field_name,
	$field_value,
	$field_options,
	$field_label,
	$field_lines = false )
{
	echo '<fieldset>';
	echo '  <div class="label"><label for="', $field_name, '">', $field_label, ':</label></div>';
	echo '  <div class="input">';
	foreach( $field_options as $loop_field_option )
	{
		if( $field_lines ) echo "<div>\n";
		echo '<label class="radiooption"><input type="radio" name="', $field_name, '" value="', $loop_field_option[0], '"';
		if( $field_value == $loop_field_option[0] )
		{
			echo ' checked="checked"';
		}
		echo ' /> ', $loop_field_option[1], '</label>';
		if( isset( $loop_field_option[2] ) )
			echo '<span class="notes">', $loop_field_option[2], '</span>';
		if( $field_lines ) echo "</div>\n";
	}
	echo "</fieldset>\n\n";
}


/*
 * form_checkbox(-)
 */
function form_checkbox( $field_name, $field_value, $field_label, $field_note = '', $field_class = '' )
{
	echo '<fieldset>';
	echo '  <div class="label"><label for="', $field_name, '">', $field_label, ':</label></div>';
	echo '  <div class="input"><input type="checkbox" name="', $field_name, '" id="', $field_name, '" value="1"';
	if( $field_value )
	{
		echo ' checked="checked"';
	}
	if( !empty($field_class) )
	{
		echo ' class="', $field_class,'"';
	}
	echo ' />';
	echo '  <span class="notes">', $field_note, '</span></div>';
	echo "</fieldset>\n\n";
}


/*
 * form_checkbox_tr(-)
 */
function form_checkbox_tr( $field_name, $field_value, $field_label, $field_note = '', $field_class = '' )
{
	echo '<tr>';
	echo '  <td align="right"><label for="', $field_name, '"><strong>', $field_label, ':</label></strong></td>';
	echo '  <td><input type="checkbox" name="', $field_name, '" id="', $field_name, '" value="1"';
	if( $field_value )
	{
		echo ' checked="checked"';
	}
	if( !empty($field_class) )
	{
		echo ' class="', $field_class,'"';
	}
	echo ' />';
	echo '  <small class="notes">', $field_note, '</small></td>';
	echo "</tr>\n\n";
}


/*
 * form_info(-)
 */
function form_info( $field_label, $field_info, $field_note = '' )
{
	echo '<fieldset>';
	echo '  <div class="label">', $field_label, ':</div>';
	echo '  <div class="input" style="padding-top: .6ex;">', $field_info;
	if( !empty($field_note) )	echo '&nbsp; <small class="notes">', $field_note, '</small>';
	echo '</div>';
	echo "</fieldset>\n\n";
}


/*
 * form_info_tr(-)
 */
function form_info_tr( $field_label, $field_info, $field_note = '' )
{
	echo '<tr>';
	echo '  <td align="right"><strong>', $field_label, ':</strong></td>';
	echo '  <td>', $field_info;
	
	if( !empty($field_note) )	echo ' <td class="small">', $field_note, '</td>';
	
	echo "</td></tr>\n\n";
}


/**
 * creates a form header and puts GET params of $action into hidden form inputs
 *
 * {@internal form_formstart(-)}}
 */
function form_formstart( $action, $class = '', $name = '', $method = 'get', $id = '' )
{
	if( $method == 'get' )
	{
		$action = explode( '?', $action );
		if( isset($action[1]) )
		{ // we have GET params in $action
			$getparams = preg_split( '/&amp;|&/i', $action[1], -1, PREG_SPLIT_NO_EMPTY );
		}
		$action = $action[0];
	}
	
	echo '<form action="'.$action.'" method="'.$method.'"';
	
	if( !empty($name) ) echo ' name="'.$name.'"';
	if( !empty($id) ) echo ' id="'.$id.'"';
	if( !empty($class) ) echo ' class="'.$class.'"';
	
	echo '>';

	if( isset($getparams) )
	{
		foreach( $getparams as $param)
		{
			$param = explode( '=', $param );
			if( isset($param[1]) )
			{
				echo '<input type="hidden" name="'.$param[0].'" value="'.$param[1].'" />';
			}
		}
	}
	
}
?>
