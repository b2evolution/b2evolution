<?php
/**
 * This file implements the Fast Form handling class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by PROGIDISTRI - {@link http://progidistri.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * PROGIDISTRI grants François PLANQUE the right to license
 * PROGIDISTRI's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 * @author fsaya: Fabrice SAYA-GASNIER / PROGIDISTRI
 *
 * @todo We should use a general associative array parameter for functions like {@link text()},
 *       where we already have 10(!) arguments now.
 *       This should include things like 'onchange' or 'force_to' as a key. When we won't do this,
 *       it really gets messy if you want to add this like onkeyup or other special attributes.
 *       IMHO we should move everything beyond $field_class into this array.
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_widget.class.php';

/**
 * Form class
 */
class Form extends Widget
{
	var $output = true;

	/**
	 * Number of fieldsets currently defined.
	 * If greater then 0, output will be buffered.
	 * @todo Why don't we explicitely request buffering if we need it ? :/
	 * @see begin_fieldset()
	 * @see end_fieldset()
	 * @var integer
	 * @access protected
	 */
	var $_count_fieldsets = 0;

	/**
	 * Buffer for fieldsets.
	 * @see begin_fieldset()
	 * @see end_fieldset()
	 * @var array
	 * @access protected
	 */
	var $_fieldsets = array();

	/**
	 * Remember number of open tags that need to be handled in {@link end_form()}.
	 *
	 * @var array
	 */
	var $_opentags = array( 'fieldset' => 0 );

	/**
	 * @var string
	 */
	var $label_suffix = ':';

	/**
	 * Display order of <label> and <input>.
	 * @var boolean Defaults to true
	 */
	var $label_to_the_left = true;


	/**
	 * Constructor
	 *
	 * @todo Provide buffering of whole Form to be able to add onsubmit-JS to enable disabled (group) checkboxes again
	 *
	 * @param string the name of the form
	 * @param string the action to execute when the form is submitted
	 * @param string the method used to send data
	 * @param string the form layout : 'fieldset', 'table' or ''
	 */
	function Form( $form_action = '', $form_name = '', $form_method = 'post', $layout = 'fieldset', $enctype = '' )
	{
		$this->form_name = $form_name;
		$this->form_action = $form_action;
		$this->form_method = $form_method;
		$this->enctype = $enctype;
		$this->saved_layout = $layout;
		$this->switch_layout( NULL );	// "restore" saved layout.
	}

	/**
	 * @param string|NULL the form layout : 'fieldset', 'table' or ''; NULL to restore previsouly saved layout
	 */
	function switch_layout( $layout )
	{
		if( $layout == NULL )
		{ // we want to restore previous layout:
			$this->layout = $this->saved_layout;
			$this->saved_layout = NULL;
		}
		else
		{ // We want to switch to a new layout
			$this->saved_layout = $this->layout;
			$this->layout = $layout;
		}

		switch( $this->layout )
		{
			case 'table':
				$this->formstart = '<table cellspacing="0" class="fform">'."\n";
				$this->title_fmt = '<thead><tr class="formtitle"><th colspan="2"><div class="results_title">'
														.'<span style="float:right">$global_icons$</span>'
														.'$title$</div></td></tr></thead>'."\n";
				$this->fieldstart = "<tr>\n";
				$this->labelstart = '<td class="label">';
				$this->labelend = "</td>\n";
				$this->labelempty = '<td class="label">&nbsp;</td>'."\n";
				$this->inputstart = '<td class="input">';
				$this->inputend = "</td>\n";
				$this->fieldend = "</tr>\n\n";
				$this->buttonsstart = '<tr class="buttons"><td colspan="2">';
				$this->buttonsend = "</td></tr>\n";
				$this->formend = "</table>\n";
				break;

			case 'fieldset':
				$this->formstart = '';
				$this->title_fmt = '<span style="float:right">$global_icons$</span><h2>$title$</h2>'."\n";
				$this->fieldstart = "<fieldset>\n";
				$this->labelstart = '<div class="label">';
				$this->labelend = "</div>\n";
				$this->labelempty = '';
				$this->inputstart = '<div class="input">';
				$this->inputend = "</div>\n";
				$this->fieldend = "</fieldset>\n\n";
				$this->buttonsstart = '<fieldset><div class="input">';
				$this->buttonsend = "</div></fieldset>\n\n";
				$this->formend = '';
				break;

			default:
				// "none" (no layout)
				$this->formstart = '';
				$this->title_fmt = '$title$'."\n";
				$this->fieldstart = '';
				$this->labelstart = '';
				$this->labelend = "\n";
				$this->labelempty = '';
				$this->inputstart = '';
				$this->inputend = "\n";
				$this->fieldend = "\n";
				$this->buttonsstart = '';
				$this->buttonsend = "\n";
				$this->formend = '';
		}
	}


	/**
	 * Start an input field.
	 *
	 * A field is a fielset containing a label div and an input div.
	 *
	 * @param string The name of the field
	 * @param string The field label
	 * @return The generated HTML
	 */
	function begin_field( $field_name = '', $field_label = '' )
	{
		// Remember these, to make them available to get_label() for !$label_to_the_left
		$this->field_name = $field_name;
		$this->field_label = $field_label;

		$r = $this->fieldstart;

		if( $this->label_to_the_left )
		{
			$r .= $this->get_label();
		}

		$r .= $this->inputstart;

		return $r;
	}


	/**
	 * End an input field.
	 *
	 * A field is a fielset containing a label div and an input div.
	 *
	 * @param string Field's note to display.
	 * @param string Format of the field's note (%s gets replaced with the note).
	 * @return The generated HTML
	 */
	function end_field( $field_note = NULL, $field_note_format = ' <span class="notes">%s</span>' )
	{
		$field_note = empty($field_note) ? '' : sprintf( $field_note_format, $field_note );

		if( $this->label_to_the_left )
		{ // label displayed in begin_field()
			$r = $field_note.$this->inputend;
		}
		else
		{ // label to the right:
			$r = $this->get_label().$field_note;
		}

		$r .= $this->fieldend;

		return $r;
	}


	/**
	 * Begin fieldset (with legend).
	 *
	 * @deprecated by ::fieldset but all functionality is not available
	 * @author blueyed
	 * @see end_fieldset()
	 * @param string The fieldset legend
	 * @param string fieldname of a checkbox that controls .disable of all
	 *               elements in the fieldset group (checkbox and text only for now).
	 */
	function begin_fieldset( $legend, $disableBy = NULL )
	{
		$r = "\n<fieldset>\n";
		if( !empty($legend) )
		{
			$r .= "\t<legend>$legend</legend>\n";
		}

		$this->_count_fieldsets++;
		$this->_fieldsets[$this->_count_fieldsets]['html'] = $r;

		$this->_fieldsets[$this->_count_fieldsets]['disableBy'] = $disableBy;
		$this->_fieldsets[$this->_count_fieldsets]['disableTags'] = array();
	}


	/**
	 * End a fieldset (and output/return it).
	 *
	 * This handles
	 * @deprecated by ::fieldset_end but all functionality is not available
	 * @author blueyed
	 */
	function end_fieldset()
	{
		$fieldset = array_pop( $this->_fieldsets );
		$this->_count_fieldsets--;

		$r = $fieldset['html'];

		// Create onclick-JS to control the groups' elements
		$onclick = '';
		foreach( $fieldset['disableTags'] as $lFieldName )
		{
			$onclick .= $this->form_name.".$lFieldName.disabled = !this.checked; ";
		}
		if( !empty($onclick) )
		{
			$onclick = ' onclick="'.$onclick.'"';
		}

		$r = str_replace( '%disableByOnclick%', $onclick, $r )
					."\n</fieldset>\n";

		if( $this->output )
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
	 * Builds a text (or password) input field.
	 *
	 * Note: please use {@link Form::password_input()} for password fields.
	 *
	 * @param string The name of the input field. This gets used for id also, if no id given in $field_params.
	 * @param string Initial value
	 * @param integer Size of the input field
	 * @param string Label displayed with the field (in front by default, see {@link $label_to_the_left}).
	 * @param array Extended attributes/params.
	 *                 - 'maxlength': if not given or === 0 $field_size gets used
	 *                 - 'class': the CSS class to use for the <input> element
	 *                 - 'type': 'text', 'password' (defaults to 'text')
	 *                 - 'force_to': 'UpperCase' (JS onchange handler)
	 *                 - NOTE: any other attributes will be used as is (onchange, onkeyup, id, ..).
	 * @return true|string true (if output) or the generated HTML if not outputting
	 */
	function text_input( $field_name, $field_value, $field_size, $field_label, $field_params = array() )
	{
		global $Request;

		if( !isset($field_params['maxlength']) || $field_params['maxlength'] === 0 )
		{ // maxlength defaults to size
			$field_params['maxlength'] = $field_size;
		}

		$field_params['class'] = isset( $extra_attribs['class'] ) ? $extra_attribs['class'] : '';

		$field_note = isset($field_params['note']) ? $field_params['note'] : NULL;
		unset($field_params['note']);

		if( !isset($field_params['type']) )
		{ // type defaults to "text"
			$field_params['type'] = 'text';
		}

		if( isset($ext_attr['force_to']) && $ext_attr['force_to'] == 'UpperCase' )
		{ // Force input to uppercase (at front of onchange event)
			$field_params['onchange'] = 'this.value = this.value.toUpperCase();'
				.( empty($field_params['onchange']) ? '' : ' '.$field_params['onchange'] );
		}

		if( !isset($field_params['id']) )
		{
			$field_params['id'] = $this->get_valid_id($field_name);
		}

		// Error handling:
		if( isset($Request->err_messages[$field_name]) )
		{ // There is an error message for this field:
			$field_params['class'] = isset( $field_params['class'] )
				? $field_params['class'].' field_error'
				: 'field_error';

			$field_note .= ' <span class="field_error">'.$Request->err_messages[$field_name].'</span>';
		}

		$r = $this->begin_field( $field_name, $field_label )
				.'<input name="'.$field_name
				.'" value="'.format_to_output($field_value, 'formvalue')
				.'" size="'.$field_size
				.'"';

		foreach( $field_params as $l_attr => $l_value )
		{
			if( $l_value !== false )
			{ // skip values that are very equal to false.
				$r .= ' '.$l_attr.'="'.format_to_output( $l_value, 'htmlattr' ).'"';
			}
		}

		$r .= " />\n";

		$r .= $this->end_field( $field_note );

		if( $this->output )
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
	 * Builds a password input field.
	 *
	 * Calls the text_input() method with type == 'password'.
	 *
	 * @param string The name of the input field. This gets used for id also, if no id given in $field_params.
	 * @param string Initial value
	 * @param integer Size of the input field
	 * @param string Label displayed in front of the field
	 * @param string Note displayed with field
	 * @param integer Max length of the value (if 0 field_size will be used!)
	 * @param string Extended attributes, see {@link text_input()}.
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function password_input( $field_name, $field_value, $field_size, $field_label, $field_params = array() )
	{
		$field_params['type'] = 'password';

		return $this->text_input( $field_name, $field_value, $field_size, $field_label, $field_params );
	}


	/**
	 * Builds a text (or password) input field.
	 *
	 * Note: please use {@link Form::password()} for password fields
	 *
	 * @deprecated Deprecated by text_input().
	 *
	 * @param string the name of the input field
	 * @param string initial value
	 * @param integer size of the input field
	 * @param string label displayed in front of the field
	 * @param string note displayed with field
	 * @param integer max length of the value (if 0 field_size will be used!)
	 * @param string the CSS class to use
	 * @param string input type (only 'text' or 'password' makes sense)
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function text( $field_name, $field_value, $field_size, $field_label, $field_note = '',
											$field_maxlength = 0 , $field_class = '', $inputtype = 'text', $force_to = '' )
	{
		$field_params = array();

		if( $field_note !== '' )
		{
			$field_params['note'] = $field_note;
		}
		if( $field_maxlength !== 0 )
		{
			$field_params['maxlength'] = $field_maxlength;
		}
		if( $field_class !== '' )
		{
			$field_params['class'] = $field_class;
		}
		if( $inputtype !== 'text' )
		{
			$field_params['type'] = $inputtype;
		}
		if( $force_to !== '' )
		{
			$field_params['force_to'] = $force_to;
		}

		return $this->text_input( $field_name, $field_value, $field_size, $field_label, $field_params );
	}


	/**
	 * Builds a password input field.
	 *
	 * Calls the text() method with a 'password' parameter.
	 *
	 * @deprecated Deprecated by password_input(). Not used in the core anymore!
	 *
	 * @param string the name of the input field
	 * @param string initial value
	 * @param integer size of the input field
	 * @param string label displayed in front of the field
	 * @param string note displayed with field
	 * @param integer max length of the value (if 0 field_size will be used!)
	 * @param string the CSS class to use
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function password( $field_name, $field_value, $field_size, $field_label, $field_note = '',
											$field_maxlength = 0 , $field_class = '' )
	{
		$field_params = array( 'type' => 'password' );

		if( !empty($field_note) )
		{
			$field_params['note'] = $field_note;
		}
		if( $field_maxlength !== 0 )
		{
			$field_params['maxlength'] = $field_maxlength;
		}
		if( !empty($field_class) )
		{
			$field_params['class'] = $field_class;
		}

		return $this->text_input( $field_name, $field_value, $field_size, $field_label, $field_params );
	}


	/**
	 * Builds a date input field.
	 *
	 * @param string the name of the input field
	 * @param string initial value (ISO datetime)
	 * @param string label displayed in front of the field
	 * @param string date format
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function date( $field_name, $field_value, $field_label, $date_format = 'yyyy-MM-dd' )
	{
		global $month, $weekday_letter;
		global $Request;
		if( isset($Request->err_messages[$field_name]) )
		{ // There is an error message for this field:
			$field_class = 'field_error';
			$field_note = '<span class="field_error">'.$Request->err_messages[$field_name].'</span>';
		}

		$field_size = strlen( $date_format );

		// Get date part of datetime:
		$field_value = substr( $field_value, 0, 10 );

		$r = $this->begin_field( $field_name, $field_label )
				.'<script type="text/javascript">
						<!--
						var cal_'.$field_name.' = new CalendarPopup();
						cal_'.$field_name.'.showYearNavigation();
						cal_'.$field_name.'.showNavigationDropdowns();
						// cal_'.$field_name.'.showYearNavigationInput();
						cal_'.$field_name.'.setMonthNames( '
							."'".T_($month['01'])."',"
							."'".T_($month['02'])."',"
							."'".T_($month['03'])."',"
							."'".T_($month['04'])."',"
							."'".T_($month['05'])."',"
							."'".T_($month['06'])."',"
							."'".T_($month['07'])."',"
							."'".T_($month['08'])."',"
							."'".T_($month['09'])."',"
							."'".T_($month['10'])."',"
							."'".T_($month['11'])."',"
							."'".T_($month['12'])."');\n"
				.' cal_'.$field_name.'.setDayHeaders( '
							."'".T_($weekday_letter[0])."',"
							."'".T_($weekday_letter[1])."',"
							."'".T_($weekday_letter[2])."',"
							."'".T_($weekday_letter[3])."',"
							."'".T_($weekday_letter[4])."',"
							."'".T_($weekday_letter[5])."',"
							."'".T_($weekday_letter[6])."' );\n"
				.' cal_'.$field_name.'.setWeekStartDay('.locale_startofweek().');
						cal_'.$field_name.".setTodayText('".TS_('Today')."');
						// -->
					</script>\n"
				.'<input type="text" name="'.$field_name.'" id="'.$this->get_valid_id($field_name).'"
					size="'.$field_size.'" maxlength="'.$field_size.'" value="'.format_to_output($field_value, 'formvalue').'"';
		if( isset( $field_class ) )
		{
			$r .= ' class="'.$field_class.'"';
		}
		$r .= " />\n"
				.'<a href="#" onclick="cal_'.$field_name.'.select('.$this->form_name.'.'.$field_name.",'anchor_".$field_name."', '".$date_format."');"
				.' return false;" name="anchor_'.$field_name.'" id="anchor_'.$this->get_valid_id($field_name).'">'.T_('Select').'</a>';

		$field_note = empty($field_note) ? '('.$date_format.')' : '('.$date_format.') '.$field_note;

		$r .= $this->end_field( $field_note );

		if( $this->output )
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
	 * Builds a time input field.
	 *
	 * @param string the name of the input field
	 * @param string initial value (ISO datetime)
	 * @param string label displayed in front of the field
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function time( $field_name, $field_value, $field_label, $field_format = 'hh:mm:ss' )
	{
		$field_size = strlen($field_format);

		// Get time part of datetime:
		$field_value = substr( $field_value, 11, 8 );

		return $this->text( $field_name, $field_value, $field_size, $field_label,
											'('.$field_format.')', $field_size );
	}


	/**
	 * Builds a duration input field.
	 *
	 * @param string the name of the input field
	 * @param string initial value (seconds)
	 * @param string label displayed in front of the field
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function duration( $field_prefix, $duration, $field_label )
	{

		$r = $this->begin_field( $field_prefix, $field_label );

		$days = floor( $duration / 86400 ); // 24 hours
		$r .= "\n".'<select name="'.$field_prefix.'_days" id="'.$this->get_valid_id($field_prefix).'_days">';
		$r .= '<option value="0"'.( 0 == $days ? ' selected="selected"' : '' ).">---</option>\n";
		for( $i = 1; $i <= 30; $i++ )
		{
			$r .= '<option value="'.$i.'"'.( $i == $days ? ' selected="selected"' : '' ).'>'.$i."</option>\n";
		}
		$r .= '</select>'.T_('days')."\n";

		$hours = floor( $duration / 3600 ) % 24;
		$r .= "\n".'<select name="'.$field_prefix.'_hours" id="'.$this->get_valid_id($field_prefix).'_hours">';
		$r .= '<option value="0"'.( 0 == $hours ? ' selected="selected"' : '' ).">---</option>\n";
		for( $i = 1; $i <= 24; $i++ )
		{
			$r .= '<option value="'.$i.'"'.( $i == $hours ? ' selected="selected"' : '' ).'>'.$i."</option>\n";
		}
		$r .= '</select>'.T_('hours')."\n";

		$minutes = floor( $duration / 60 ) % 60;
		$r .= "\n".'<select name="'.$field_prefix.'_minutes" id="'.$this->get_valid_id($field_prefix).'_minutes">';
		$r .= '<option value="0"'.( ($minutes<15) ? ' selected="selected"' : '' ).">00</option>\n";
		$r .= '<option value="15"'.( ($minutes>=15 && $minutes<30) ? ' selected="selected"' : '' ).">15</option>\n";
		$r .= '<option value="30"'.( ($minutes>=30 && $minutes<45) ? ' selected="selected"' : '' ).">30</option>\n";
		$r .= '<option value="45"'.( ($minutes>=45) ? ' selected="selected"' : '' ).">45</option>\n";
		$r .= '</select>'.T_('minutes')."\n";

		$r .= $this->end_field();

		if( $this->output )
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
	 * Build a select to choose a weekday.
	 *
	 * @return true|string
	 */
	function dayOfWeek( $field_name, $field_value, $field_label, $field_note = NULL, $field_class = NULL )
	{
		global $weekday_abbrev;

		$field_options = '';

		foreach( $weekday_abbrev as $lNumber => $lWeekday )
		{
			$field_options .= '<option';

			if( $field_value == $lNumber )
			{
				$field_options .= ' selected="selected"';
			}
			$field_options .= ' value="'.$lNumber.'">'.T_($lWeekday).'</option>';
		}

		return $this->select_options( $field_name, $field_options, $field_label, $field_note, $field_class );
	}


	/**
	 * Builds a checkbox field
	 *
	 * @param string the name of the checkbox
	 * @param boolean indicating if the checkbox must be checked
	 * @param string label
	 * @param string note
	 * @param string CSS class
	 * @param string value to use
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function checkbox( $field_name, $field_checked, $field_label, $field_note = '',
											$field_class = '', $field_value = 1 )
	{
		$r = $this->begin_field( $field_name, $field_label )
				.'<input type="checkbox" class="checkbox"'
				.' value="'.format_to_output($field_value,'formvalue').'"';

		if( !empty($field_class) )
		{
			$r .= ' class="'.$field_class.'"';
		}
		if( !empty($field_name) )
		{
			$r .= 'name="'.$field_name.'" id="'.$this->get_valid_id($field_name).'"';
		}
		if( $field_checked )
		{
			$r .= ' checked="checked" ';
		}
		$r .= " />\n".$this->end_field( $field_note );

		if( $this->output )
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
	 * Returns 'disabled="disabled"' if the boolean param is false.
	 *
	 * @static
	 * @return string 'disabled="disabled"', ''
	 */
	function disabled( $boolean )
	{
		return $boolean ? 'disabled="disabled"' : '';
	}


	/**
	 * Builds the form field
	 *
	 * @param string the class to use for the form tag
	 * @param string title to display on top of the form
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function begin_form( $form_class = NULL, $form_title = '' )
	{
		$r = "\n\n"
					.'<form'
					.( !empty( $this->form_name ) ? ' name="'.$this->form_name.'"' : '' )
					.( !empty( $this->form_name ) ? ' id="'.$this->get_valid_id($this->form_name).'"' : '' )
					.( !empty( $this->enctype ) ? ' enctype="'.$this->enctype.'"' : '' )
					.' method="'.$this->form_method
					.'" action="'.$this->form_action.'"'
					.( !empty( $form_class ) ? ' class="'.$form_class : '' )
					.'">'."\n"
					.$this->formstart;

		if( !empty($form_title) )
		{
			$this->title = $form_title;

			$r .= $this->replace_vars( $this->title_fmt );
		}

		if( $this->output )
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
	 * Ends the form and optionally displays buttons.
	 *
	 * @param array Optional array to display the buttons before the end of the form, see {@link buttons()}
	 * @return true|string true (if output) or the generated HTML if not outputting.
	 */
	function end_form( $buttons = array() )
	{
		$r = '';
		if( !empty( $buttons ) )
		{
			$output = $this->output;
			$this->output = 0;

			$r .= $this->buttons( $buttons );

			$this->output = $output;
		}

		while( $this->_opentags['fieldset']-- )
		{
			$r .= "\n</fieldset>\n";
		}

		$r .= $this->formend
					."\n</form>\n\n";

		if( $this->output )
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
	 * Builds the fieldset tag
	 *
	 * @param string the title of the fieldset
	 * @param string the class of the fieldset
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function fieldset( $title = '', $class = 'fieldset' )
	{
		switch( $this->layout )
		{
			case 'table':
				$r = '<tr ';
				if( $class != '' )
				{ //there is a class option to display in the fieldset tag
					$r .= 'class="'.$class.'" ';
				}
				$r .= '><th colspan="2">'."\n";

				if( $title != '' )
				{ // there is a legend tag to display
					$r .= $title;
				}

				$r .= "</th></tr>\n";
				break;

			default:
				$r = '<fieldset ';
				if( $class != '' )
				{ //there is a class option to display in the fieldset tag
					$r .= 'class="'.$class.'" ';
				}
				$r .= '>'."\n";

				if( $title != '' )
				{ // there is a legend tag to display
					$r .= '<legend>'.$title."</legend>\n";
				}

				$this->_opentags['fieldset']++;
		}


		if( $this->output )
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
	 * Ends the fieldset tag
	 *
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function fieldset_end()
	{
		switch( $this->layout )
		{
			case 'table':
				$r = '';
				break;

			default:
				$r = "</fieldset>\n";
				$this->_opentags['fieldset']--;
		}

		if( $this->output )
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
	 * Builds a checkbox list
	 *
	 * the two-dimension array must indicate, for each checkbox:
	 *  - the name,
	 *  - the value,
	 *  - the comment to put between <input> and <br />
	 *  - a boolean indicating whether the box must be checked or not
	 *  - an optional boolean indicating whether the box is disabled or not
	 *  - an optional note
	 *
	 * @param array a two-dimensional array containing the parameters of the input tag
	 * @param string name
	 * @param string label
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function checklist( $options, $field_name, $field_label )
	{
		global $Request;

		$r = $this->begin_field( $field_name, $field_label );
		foreach( $options as $option )
		{ //loop to construct the list of 'input' tags

			$loop_field_name = $option[0];
			if( substr( $loop_field_name, -2 ) == '[]' )
			{
				$error_name = substr( $loop_field_name, 0, strlen($loop_field_name)-2 );
			}
			else
			{
				$error_name = $loop_field_name;
			}
			$loop_field_note = isset($option[5]) ? $option[5] : '';

			if( isset($Request->err_messages[$error_name]) )
			{ // There is an error message for this field:
				$after_field = '</span>';
				$loop_field_note .= ' <span class="field_error">'.$Request->err_messages[$error_name].'</span>';
				$r .= '<span class="checkbox_error">';
			}
			else
			{
				$after_field = '';
			}

			$r .= "\t".'<input type="checkbox" name="'.$loop_field_name.'" value="'.$option[1].'" ';
			if( $option[3] )
			{ //the checkbox has to be checked by default
				$r .= ' checked="checked" ';
			}
			if( isset( $option[4] ) && $option[4] )
			{ // the checkbox has to be disabled
				$r .= ' disabled="disabled" ';
			}
			$r .= ' class="checkbox" />';

			$r .= $after_field;

			$r .= $option[2];
			if( !empty($loop_field_note) )
			{ // We want to display a note:
				$r .= ' <span class="notes">'.$loop_field_note.'</span>';
			}
			$r .= "<br />\n";
		}

		$r .= $this->end_field();

		if( $this->output )
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
	 * Display a select field and populate it with a callback function.
	 *
	 * @param string field name
	 * @param string default field value
	 * @param callback callback function
	 * @param string field label to be display before the field
	 * @param string note to be displayed after the field
	 * @param string CSS class for select
	 * @param string Javascript to add for onchange event (trailing ";").
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function select(
		$field_name,
		$field_value,
		$field_list_callback,
		$field_label,
		$field_note = '',
		$field_class = '',
		$field_onchange = NULL )
	{
		$field_options = call_user_func( $field_list_callback, $field_value );

		return $this->select_options( $field_name, $field_options, $field_label, $field_note, $field_class, $field_onchange );
	}


	/**
	 * Display a select field and populate it with a cache object.
	 *
	 * @todo Refactor to put $field_onchange after $field_class(?)
	 *
	 * @param string field name
	 * @param string default field value
	 * @param DataObjectCache Cache containing values for list
	 * @param string field label to be display before the field
	 * @param string note to be displayed after the field
	 * @param boolean allow to select [none] in list
	 * @param string CSS class for select
	 * @param string Object's callback method name.
	 * @param string Javascript to add for onchange event (trailing ";").
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function select_object(
		$field_name,
		$field_value,
		& $field_object,
		$field_label,
		$field_note = '',
		$allow_none = false,
		$field_class = '',
		$field_object_callback = 'option_list_return',
		$field_onchange = NULL )
	{
		$field_options = $field_object->$field_object_callback( $field_value, $allow_none );

		return $this->select_options( $field_name, $field_options, $field_label, $field_note, $field_class, $field_onchange );
	}


	/**
	 * Display a select field and populate it with a cache object.
	 *
	 * @param string field name
	 * @param string string containing options
	 * @param string field label to be display before the field
	 * @param string note to be displayed after the field
	 * @param boolean allow to select [none] in list
	 * @param string CSS class for select
	 * @param string Javascript to add for onchange event (trailing ";").
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function select_options(
		$field_name,
		& $field_options,
		$field_label,
		$field_note = NULL,
		$field_class = NULL,
		$field_onchange = NULL )
	{
		global $Request;
		if( isset($Request->err_messages[$field_name]) )
		{ // There is an error message for this field:
			$field_class .= ' field_error';
			$field_note .= ' <span class="field_error">'.$Request->err_messages[$field_name].'</span>';
		}

		$r = $this->begin_field( $field_name, $field_label )
					."\n<select";

		if( !empty($field_name) )
		{
			$r .= ' name="'.$field_name.'" id="'.$this->get_valid_id($field_name).'"';
		}
		if( !empty($field_class) )
		{
			$r .= ' class="'.$field_class.'"';
		}
		if( !empty( $field_onchange ) )
		{
			$r .= ' onchange="'.format_to_output( $field_onchange, 'htmlattr' ).'"';
		}
		$r .= '>'
					.$field_options
					."</select>\n";

		$r .= $this->end_field( $field_note );

		if( $this->output )
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
	 * This is a stub for {@link select_options()} which builds the required list
	 * of <option> elements from a given list of options ($field_options) and
	 * the selected value ($field_value).
	 *
	 * @param string field name
	 * @param array Options. If an associative key (string) is used, this gets the value attribute.
	 * @param mixed The selected value - if it's NULL, use the global variable named $field_name
	 * @param string field label to be display before the field
	 * @param string note to be displayed after the field
	 * @param boolean allow to select [none] in list
	 * @param string CSS class for select
	 * @param string Javascript to add for onchange event (trailing ";").
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function select_array(
		$field_name,
		$field_options,
		$field_value = NULL,
		$field_label = NULL,
		$field_note = NULL,
		$field_class = NULL,
		$field_onchange = NULL )
	{
		if( NULL === $field_value )
		{
			$field_value = isset( $GLOBALS[$field_name] ) ? $GLOBALS[$field_name] : NULL;
		}

		$options_list = '';

		foreach( $field_options as $l_key => $l_option )
		{
			$value = is_string($l_key) ? $l_key : $l_option;

			$options_list .= '<option value="'.format_to_output($value, 'formvalue').'"';

			if( $l_option == $field_value )
			{
				$options_list .= ' selected="selected"';
			}

			$options_list .= '>'.format_to_output($l_option).'</option>';
		}

		return $this->select_options( $field_name, $options_list, $field_label, $field_note, $field_class, $field_onchange );
	}


	/**
	 * Build a text area.
	 *
	 * @param string
	 * @param string
	 * @param integer
	 * @param string
	 * @param string
	 * @param integer
	 * @param string
	 */
	function textarea( $field_name, $field_value, $field_rows, $field_label,
												$field_note = '', $field_cols = 50 , $field_class = '' )
	{
		global $img_url, $Request;
		if( isset($Request->err_messages[$field_name]) )
		{ // There is an error message for this field:
			$field_class .= ' field_error';
			$field_note .= ' <span class="field_error">'.$Request->err_messages[$field_name].'</span>';
		}

		$r = $this->begin_field( $field_name, $field_label );

		// NOTE: The following pixel is needed to avoid the dity IE textarea expansion bug
		// see http://fplanque.net/2003/Articles/iecsstextarea/index.html
		$r .= '<img src="'.$img_url.'blank.gif" width="1" height="1" alt="" />';

		$r .= '<textarea';
		if( !empty($field_name) )
		{
			$r .= ' name="'.$field_name.'" id="'.$this->get_valid_id($field_name).'"';
		}
		if( !empty($field_class) )
		{
			$r .= ' class="'.$field_class.'"';
		}
		$r .= ' rows="'.$field_rows.'" cols="'.$field_cols.'">'
					.format_to_output( $field_value, 'formvalue' )
					.'</textarea>'
					// NOTE: this one is for compensating the previous pixel in case of center aligns.
					.'<img src="'.$img_url.'blank.gif" width="1" height="1" alt="" />';

		$r .= $this->end_field( $field_note, '<br/><span class="notes">%s</span>' );

		if( $this->output )
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
	 * Builds an info field.
	 * An info field is a fieldset containing a label div and an info div.
	 *
	 * {@internal
	 * NOTE: we don't use {@link begin_field()} here, because the label is meant
	 * to be always on the left and this avoids fiddling with the <label> tag.
	 * }}
	 *
	 * @param string the field label
	 * @param string the field info
	 * @param string see {@see format_to_output()}
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function info( $field_label, $field_info, $field_note = NULL, $format = 'htmlbody' )
	{
		$r = $this->fieldstart;

		if( !empty($field_label) )
		{
			$r .= $this->labelstart
						.$field_label.$this->label_suffix
						.$this->labelend;
		}
		else
		{ // Empty label:
			$r = $this->labelempty;
		}

		$r .= $this->inputstart;

		// PAYLOAD:
		$r .= format_to_output( $field_info, $format );

		// end field (Label always to the left!)
		$old_label_to_the_left = $this->label_to_the_left;
		$this->label_to_the_left = true;
		$r .= $this->end_field( $field_note, ' <small class="notes">%s</small>' );
		$this->label_to_the_left = $old_label_to_the_left;

		if( $this->output )
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
	 * Builds a button list
	 *
	 * the two-dimension array must contain :
	 *  - the button type
	 *  - the name (optional)
	 *  - the value (optional)
	 *  - the class (optional)
	 *  - the onclick attribute (optional)
	 *
	 * @param array a two-dimension array containing the elements of the input tags
	 * @param boolean to select or not the default display
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function buttons( $options = '' )
	{
		$r = '';
		$hidden = 1; // boolean that tests if the buttons are all hidden

		if( !empty( $options ) )
		{
			foreach( $options as $options )
			{
				if( $options[0] != 'hidden' )
				{ //test if the current button is hidden and sets $hidden to 0 if not
					$hidden = 0;
				}
				$output = $this->output;
				$this->output = 0;
				$r .= $this->button( $options ); //call to the button method to build input tags
				$this->output = $output;
			}
		}
		/*
		else
		{
			$r .= "\t\t\t".'<input type="submit" value="'.T_('Save !').'" class="SaveButton"/>'."\n";
			$r .= "\t\t\t".'<input type="reset" value="'.T_('Reset').'" class="ResetButton"/>'."\n";
		}*/

		if( ! $hidden )
		{ // there are not only hidden buttons : additional tags
			$r = $this->buttonsstart.$r.$this->buttonsend;
		}

		if( $this->output )
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
	 * Builds a button
	 *
	 * the array must contain :
	 *  - the button type
	 *  - the name (optional)
	 *  - the value (optional)
	 *  - the class (optional)
	 *  - the onclick attribute (optional)
	 *  - the style (optional)
	 *
	 * @param array a two-dimension array containing the elements of the input tags
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function button( $options )
	{

		$r = "\t\t\t".'<input type="';

		if( !empty($options[0]) )
		{ //a type has been specified
			$r .= $options[0].'" ';
		}
		else
		{ //set default type
			$r .= 'submit" ';
		}

		if( !empty($options[1]) )
		{ //a name has been specified
			$r .= ' id="'.$this->get_valid_id($options[1]).'" ';
			$r .= ' name="'.$options[1].'" ';
		}

		if( !empty($options[2]) )
		{ //a value has been specified
			$r .= ' value="'.$options[2].'" ';
		}

		if( !empty($options[3]) )
		{ //a class has been specified
			$r .= ' class="'.$options[3].'" ';
		}

		if( !empty($options[4]) )
		{ //an onclick action has been specified
			$r .= ' onclick="'.$options[4].'" ';
		}

		if( !empty($options[5]) )
		{ // style supplied
			$r .= ' style="'.$options[5].'" ';
		}
		$r .= " />\n";

		if( $this->output )
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
	 * Builds an hidden input tag
	 *
	 * the array must contain :
	 *  - the name (optional)
	 *  - the value (optional)
	 *  - the class (optional)
	 *  - the onclick attribute (optional)
	 *  - the style (optional)
	 *
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function hidden( $field_name, $field_value )
	{
		$r = '<input type="hidden" name="'.$field_name.'" value="'.$field_value.'" />';

		if( $this->output )
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
	 */
	function hiddens( $hiddens )
	{
		$r = '';

		foreach( $hiddens as $hidden )
		{
			$r .= '<input type="hidden" name="'.$hidden[0].'" value="'.$hidden[1].'" />';
		}

		if( $this->output )
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
	 * Builds a submit input tag
	 *
	 * the array must contain :
	 *  - the name (optional)
	 *  - the value (optional)
	 *  - the class (optional)
	 *  - the onclick attribute (optional)
	 *  - the style (optional)
	 *
	 * @todo inconsistent parameters!
	 * @todo Use <div class="input"> for layout == 'fieldset' (property).
	 * @param array an array containing the elements of the input tags
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function submit( $options )
	{
		$hidden_fields = array();
		$i = 1;
		$r = '';

		$submit_fields[0] = '';

		foreach( $options as $option )
		{ // construction of the option array for the button method
			$submit_fields[$i] = $option;
			$i++;
		}

		$output = $this->output;
		$this->output = 0;
		$r .= $this->button( $submit_fields ); //call to the button method to build input tags
		$this->output = $output;

		if( $this->output )
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
	 * Generate set of radio options.
	 *
	 * {@internal form_radio(-)}}
	 * @param string the name of the radio options
	 * @param string the checked option
	 * @param array of arrays the radio options (0: value, 1: label, 2: notes, 3: additional HTML [input field, ..], 4: attribs for <input tag> )
	 * @param string label
	 * @param boolean options on seperate lines (DIVs)
	 * @param string notes
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function radio(
		$field_name,
		$field_value,
		$field_options,
		$field_label,
		$field_lines = false,
		$field_note = '' )
	{
		$r = $this->begin_field( $field_name, $field_label );

		foreach( $field_options as $loop_field_option )
		{
			if( $field_lines ) $r .= "<div>\n";

			$r .= '<label class="radiooption"><input type="radio" class="radio" name="'.$field_name.'" value="'.format_to_output( $loop_field_option[0], 'formvalue' ).'"';
			if( $field_value == $loop_field_option[0] )
			{ // Current selection:
				$r .= ' checked="checked"';
			}
			if( !empty( $loop_field_option[4] ) )
				$r .= ' '.$loop_field_option[4];
			$r .= ' /> '.$loop_field_option[1].'</label>';
			if( !empty( $loop_field_option[2] ) )
			{ // notes for radio option
				$r .= '<span class="notes">'.$loop_field_option[2].'</span>';
			}
			if( !empty( $loop_field_option[3] ) )
			{ // optional text for radio option (like additional fieldsets or input boxes)
				$r .= $loop_field_option[3];
			}

			if( $field_lines ) $r .= "</div>\n";
		}

		$r .= $this->end_field( $field_note, '<div><span class="notes">%s</span></div>' );

		if( $this->output )
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
	 * Convert a given string (e.g. fieldname) to a valid HTML id.
	 *
	 * @return string
	 */
	function get_valid_id( $id )
	{
		return str_replace( array( '[', ']' ), '_', $id );
	}


	/**
	 * Get the label of a field. This is used by {@link begin_field()} or {@link end_field()},
	 * according to {@link $label_to_the_left}
	 *
	 * @access protected
	 * @return string
	 */
	function get_label()
	{
		$r = '';

		if( !empty($this->field_label) )
		{
			// $this->empty_label = true; // Memorize this
			$r .= $this->labelstart
						.'<label for="'.$this->field_name.'">'.$this->field_label.$this->label_suffix.'</label>'
						.$this->labelend;
		}
		else
		{ // Empty label:
			// $this->empty_label = false; // Memorize this
			$r .= $this->labelempty;
		}

		return $r;
	}
}

?>