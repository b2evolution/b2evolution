<?php
/**
 * This file implements Fast Form handling functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * @deprecated All those functions should be handled by the {@link Form Form class}.
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Start an field group.
 *
 * Fieldset with legend.
 *
 * @deprecated Deprecated by (@link Form::begin_fieldset())
 */
function form_groupstart( $title = '' )
{
	echo "<fieldset>\n"
				.'<legend>'.$title."</legend>\n";
}


/**
 * End an field group.
 *
 * Fieldset with legend.
 *
 * @deprecated Deprecated by (@link Form::end_fieldset())
 */
function form_groupend( $title = '' )
{
	echo "</fieldset>\n";
}


/**
 * Start an input field.
 *
 * A field is a fielset containing a label div and an input div.
 *
 * @deprecated Deprecated by (@link Form::begin_field())
 */
function form_fieldstart( $field_name, $field_label )
{
	return "<fieldset>\n"
			.'<div class="label"><label for="'.$field_name.'">'.$field_label.":</label></div>\n"
			.'<div class="input">';
}

/**
 * End an input field.
 *
 * A field is a fieldset containing a label div and an input div.
 *
 * @deprecated Deprecated by (@link Form::end_field())
 */
function form_fieldend()
{
	return "</div>\n</fieldset>\n\n";
}

/**
 * Start an info field.
 *
 * An info field is a fielset containing a label div and an info div.
 *
 * @deprecated Deprecated by (@link Form::info_field())
 */
function form_infostart( $field_label )
{
	return "<fieldset>\n".
					'<div class="label">'.$field_label.":</div>\n".
					'<div class="info">';
}

/**
 * End an info field.
 *
 * An info field is a fielset containing a label div and an info div.
 *
 * @deprecated Deprecated by (@link Form::info_field())
 */
function form_infoend()
{
	return "</div>\n</fieldset>\n\n";
}


/**
 * Builds a text (or password) input field.
 *
 * @deprecated Deprecated by (@link Form::text_input())
 *
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
function form_text( $field_name, $field_value, $field_size, $field_label, $field_note = '',
										$field_maxlength = 0 , $field_class = '', $inputtype = 'text', $output = true  )
{
	if( $field_maxlength == 0 )
		$field_maxlength = $field_size;

	// question: is it necessary to enclose each field in a fieldset.
	// fplanque> YES, for CSS
	// shouldn't there be a fieldset for a set of field (i.e. all fields
	// in the form)?
	// fplanque>> Create a new 'simple' layout if this is what you want

	$r = "<fieldset>\n"
			.'<div class="label"><label for="'.$field_name.'">'.$field_label.":</label></div>\n"
			.'<div class="input"><input type="'.$inputtype.'" name="'.$field_name
			.'" id="'.$field_name.'" size="'.$field_size.'" maxlength="'.$field_maxlength
			.'" value="'.format_to_output($field_value, 'formvalue').'"';
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
 * form_text_tr(-)
 *
 * @deprecated Deprecated by (@link Form::text_input())
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


/**
 * Builds a hidden field.
 *
 * @deprecated Deprecated by (@link Form::button_input())
 *
 * @param string name
 * @param string value
 * @return string
 */
function form_hidden( $field_name, $field_value, $display = true )
{
	$r = '<input type="hidden" name="'.$field_name
				.'" value="'.format_to_output($field_value, 'formvalue').'" />'."\n";
	if( $display )
	{
		echo $r;
		return true;
	}

	return $r;
}


/**
 * Build a text area.
 *
 * @deprecated Deprecated by (@link Form::textarea_input())
 *
 * @param string
 * @param string
 * @param integer
 * @param string
 * @param string
 * @param integer
 * @param string
 */
function form_textarea( $field_name, $field_value, $field_rows, $field_label,
												$field_note = '', $field_cols = 50 , $field_class = '' )
{
	global $rsc_url;

	echo '<fieldset>';
	echo '  <div class="label"><label for="', $field_name, '">', $field_label, ':</label></div>';
	echo '  <div class="input"><fieldset class="input"><img src="'.$rsc_url.'img/blank.gif" width="1" height="1" alt="" /><textarea name="', $field_name, '" id="', $field_name, '" rows="', $field_rows, '"  cols="', $field_cols, '"';
	if( !empty($field_class) )
	{
		echo ' class="', $field_class,'"';
	}
	echo '>'.$field_value.'</textarea></fieldset>';
	echo '  <span class="notes">', $field_note, '</span></div>';
	echo "</fieldset>\n\n";
}


/**
 * Display a select field and populate it with a callback function.
 *
 * @deprecated Deprecated by (@link Form::select_input())
 *
 * @param string field name
 * @param string default field value
 * @param callback callback function
 * @param string field label to be display before the field
 * @param string note to be displayed after the field
 * @param string CSS class for select
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

	// call the callback function:
	$field_list_callback( $field_value );

	echo '  </select>';
	echo '  <span class="notes">', $field_note, '</span></div>';
	echo "</fieldset>\n\n";
}


/**
 * Display a select field and populate it with a cache object.
 *
 * @deprecated Deprecated by (@link Form::select_object())
 *
 * @param string field name
 * @param string default field value
 * @param DataObjectCache Cache containing values for list
 * @param string field label to be display before the field
 * @param string note to be displayed after the field
 * @param boolean allow to select [none] in list
 * @param string CSS class for select
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
		echo ' class="'.$field_class.'"';
	}
	echo '>';
	$field_object->option_list( $field_value, $allow_none );
	echo '  </select>';
	echo '  <span class="notes">'.$field_note.'</span></div>';
	echo "</fieldset>\n\n";
}


/**
 * Generate set of radio options.
 *
 * @deprecated Deprecated by (@link Form::radio_input())
 *
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
 *
 * @deprecated Deprecated by (@link Form::checkbox())
 *
 * @param string the name of the checkbox
 * @param boolean initial value
 * @param string label
 * @param string note
 * @param string CSS class
 * @param boolean to output (default)  or not
 * @return mixed true (if output) or the generated HTML if not outputting
 */
function form_checkbox( $field_name, $field_value, $field_label, $field_note = '',
												$field_class = '', $output = true )
{
	$r = "<fieldset>\n"
			.'<div class="label"><label for="'.$field_name.'">'.$field_label.":</label></div>\n"
			.'<div class="input"><input type="checkbox" class="checkbox" name="'.$field_name.'" id="'
			.$field_name.'" value="1"';
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


/**
 * form_checkbox_tr(-)
 *
 * @deprecated Deprecated by (@link Form::checkbox_input())
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


/**
 * form_info(-)
 *
 * @deprecated Deprecated by (@link Form::info_field())
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


/**
 * form_info_tr(-)
 *
 * @deprecated Deprecated by (@link Form::info_field())
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
 * Builds a form header and puts GET params of $action into hidden form inputs
 *
 * @deprecated Deprecated by (@link Form::begin_form())
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
	{ // These need to be wrapped in a div to validate xhtml strict
		echo '<div>';
		foreach( $getparams as $param)
		{
			$param = explode( '=', $param );
			if( isset($param[1]) )
			{
				echo '<input type="hidden" name="'.$param[0].'" value="'.$param[1].'" />';
			}
		}
		// close the div
		echo '</div>';
	}
}

/**
 *
 * @deprecated Deprecated by (@link Form::end_form())
 */
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

/*
 * $Log$
 * Revision 1.3  2006/03/18 19:17:54  blueyed
 * Removed remaining use of $img_url
 *
 * Revision 1.2  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:12:18  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.17  2005/12/12 19:21:22  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.16  2005/11/01 16:29:48  blueyed
 * Updated info about deprecation. This whole file is deprecated.
 *
 * Revision 1.15  2005/10/31 23:20:45  fplanque
 * keeping things straight...
 *
 * Revision 1.14  2005/10/30 10:44:23  marian
 * changes regarding multi-domain
 * question about fieldset for each form-field
 *
 * Revision 1.13  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.12  2005/09/05 10:58:15  yabs
 * minor changes - correcting validation errors
 *
 * Revision 1.11  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.10  2005/02/08 01:41:25  blueyed
 * fixed deprecated form_select()
 *
 * Revision 1.9  2005/01/13 19:53:50  fplanque
 * Refactoring... mostly by Fabrice... not fully checked :/
 *
 * Revision 1.8  2005/01/10 02:14:28  blueyed
 * form_hidden(): allow return-only
 *
 * Revision 1.7  2004/12/21 21:22:46  fplanque
 * factoring/cleanup
 *
 * Revision 1.6  2004/11/26 19:38:35  fplanque
 * no message
 *
 * Revision 1.5  2004/11/15 18:57:05  fplanque
 * cosmetics
 *
 * Revision 1.4  2004/10/21 18:34:02  fplanque
 * minor changes
 *
 * Revision 1.3  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.2  2004/10/14 16:28:41  fplanque
 * minor changes
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.39  2004/10/12 17:22:30  fplanque
 * Edited code documentation.
 *
 */
?>