<?php
/**
 * This file implements Fast Form handling functions.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * builds an text (or password) input field.
 *
 * {@internal form_text(-)}}
 * @param string the name of the input field
 * @param string initial value
 * @param integer size of the input field
 * @param string label displayed in front of the field
 * @param string note displayed with field
 * @param integer max length of the value (if 0 field_size will be used!)
 * @param string the CSS class to use
 * @param string input type (only 'text' or 'password' makes sense)
 * @param boolean display (default) or return
 * @return mixed true (if output) or the generated HTML if not outputting
 */
function form_text( $field_name, $field_value, $field_size, $field_label, $field_note = '', $field_maxlength = 0 , $field_class = '', $inputtype = 'text', $output = true  )
{
	if( $field_maxlength == 0 )
		$field_maxlength = $field_size;

	$r = "<fieldset>\n"
			.'<div class="label"><label for="'.$field_name.'">'.$field_label.":</label></div>\n"
			.'<div class="input"><input type="'.$inputtype.'" name="'.$field_name.'" id="'.$field_name.'" size="'.$field_size.'" maxlength="'.$field_maxlength.'" value="'.format_to_output($field_value, 'formvalue').'"';
	if( !empty($field_class) )
	{
		$r .= ' class="'.$field_class.'"';
	}
	$r .= " />\n";

	if( !empty( $field_note ) )
	{
		$r .= '<span class="notes">'.$field_note.'</span>';
	}

	$r .= "</div>\n</fieldset>\n\n";

	if( $output )
	{
		echo $r;
		return true;
	}
	else
	{
		return $r;
	}
}

/**
 *
 *
 * form_textarea(-)
 */
function form_textarea( $field_name, $field_value, $field_rows, $field_label, $field_note = '', $field_cols = 50 , $field_class = '' )
{
	global $img_url;

	echo '<fieldset>';
	echo '  <div class="label"><label for="', $field_name, '">', $field_label, ':</label></div>';
	echo '  <div class="input"><fieldset class="input"><img src="'.$img_url.'blank.gif" width="1" height="1" alt="" /><textarea name="', $field_name, '" id="', $field_name, '" rows="', $field_rows, '"  cols="', $field_cols, '"';
	if( !empty($field_class) )
	{
		echo ' class="', $field_class,'"';
	}
	echo '>'.$field_value.'</textarea></fieldset>';
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


/**
 * Generate set of radio options.
 *
 * {@internal form_radio(-)}}
 * @param string the name of the radio options
 * @param string the checked option
 * @param array of arrays the radio options (0: value, 1: label, 2: notes, 3: additional HTML [input field, ..], 4: attribs for <input tag> )
 * @param string label
 * @param boolean options on seperate lines (DIVs)
 * @param string notes
 */
function form_radio(
	$field_name,
	$field_value,
	$field_options,
	$field_label,
	$field_lines = false,
	$field_notes = '' )
{
	echo '<fieldset class="setting">';
	echo '  <div class="label">'.format_to_output($field_label).':</div>';
	echo '  <div class="input"><fieldset class="input">';
	foreach( $field_options as $loop_field_option )
	{
		if( $field_lines ) echo "<div>\n";
		echo '<label class="radiooption"><input type="radio" class="radio" name="'.$field_name.'" value="'.format_to_output( $loop_field_option[0], 'formvalue' ).'"';
		if( $field_value == $loop_field_option[0] )
		{
			echo ' checked="checked"';
		}
		if( !empty( $loop_field_option[4] ) )
			echo ' '.$loop_field_option[4];
		echo ' /> ', $loop_field_option[1], '</label>';
		if( !empty( $loop_field_option[2] ) )
		{ // notes for radio option
			echo '<span class="notes">', $loop_field_option[2], '</span>';
		}
		if( !empty( $loop_field_option[3] ) )
		{ // optional text for radio option (like additional fieldsets or input boxes)
			echo $loop_field_option[3];
		}
		if( $field_lines ) echo "</div>\n";
	}
	if( !empty( $field_notes ) )
	{
		echo '<div><span class="notes">'.$field_notes.'</span></div>';
	}
	echo '  </fieldset></div>';
	echo "</fieldset>\n\n";
}


/**
 * form_checkbox(-)
 * @param string the name of the checkbox
 * @param boolean initial value
 * @param string label
 * @param string note
 * @param string CSS class
 * @param boolean to output (default)  or not
 * @return mixed true (if output) or the generated HTML if not outputting
 */
function form_checkbox( $field_name, $field_value, $field_label, $field_note = '', $field_class = '', $output = true )
{
	$r = "<fieldset>\n"
			.'<div class="label"><label for="'.$field_name.'">'.$field_label.":</label></div>\n"
			.'<div class="input"><input type="checkbox" class="checkbox" name="'.$field_name.'" id="'.$field_name.'" value="1"';
	if( $field_value )
	{
		$r .= ' checked="checked"';
	}
	if( !empty($field_class) )
	{
		$r .= ' class="'.$field_class.'"';
	}
	$r .= " />\n"
				.'<span class="notes">'.$field_note."</span></div>\n"
				."</fieldset>\n\n";

	if( $output )
	{
		echo $r;
		return true;
	}
	else
	{
		return $r;
	}
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
	echo '  <div class="info">', $field_info;
	if( !empty($field_note) )
	{
		echo '&nbsp; <small class="notes">', $field_note, '</small>';
	}
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


function form_submit( $submit_attribs = '' )
{
	?>
	<fieldset class="submit">
		<fieldset>
			<div class="input">
				<input type="submit" name="submit" value="<?php echo T_('Save !') ?>" class="SaveButton" <?php echo $submit_attribs; ?> />
				<input type="reset" value="<?php echo T_('Reset') ?>" class="ResetButton" />
			</div>
		</fieldset>
	</fieldset>
	<?php
}
?>