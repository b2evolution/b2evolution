<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */

/*
 * form_text(-)
 */
function form_text( $field_name, $field_value, $field_size, $field_label, $field_note = '', $field_maxlength = 0 , $field_class = '' )
{
	if( $field_maxlength == 0 ) 
		$field_maxlength = $field_size;

	echo '<fieldset>';
	echo '  <div class="label"><label for="', $field_name, '">', $field_label, ':</label></div>';
	echo '  <div class="input"><input type="text" name="', $field_name, '" id="', $field_name, '" size="', $field_size, '" maxlength="', $field_maxlength, '" value="', format_to_output($field_value, 'formvalue'),'"';
	if( !empty($field_class) ) 
	{ 
		echo ' class="', $field_class,'"'; 
	} 
	echo '/>';
	echo '  <span class="notes">', $field_note, '</span></div>';
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
	echo '/>';
	echo '  <span class="notes">', $field_note, '</span></div>';
	echo "</fieldset>\n\n";
}
?>
