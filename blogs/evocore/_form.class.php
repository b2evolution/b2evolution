<?php
/**
 * This file implements the Fast Form handling class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

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
	 * @var string
	 */
	var $label_suffix = ':';


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
	function Form( $form_action='', $form_name='', $form_method='post', $layout = 'fieldset' )
	{
		$this->form_name = $form_name;
		$this->form_action = $form_action;
		$this->form_method = $form_method;
		$this->layout = $layout;

		switch( $this->layout )
		{
			case 'table':
				$this->formstart = '<table cellspacing="0">'."\n";
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
				$this->title_fmt = '$title$'."\n";
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
	 * @param string the name of the field
	 * @param string the field label
	 * @return the generated HTML
	 */
	function begin_field( $field_name = '', $field_label = '' )
	{
		$r = $this->fieldstart;

		if( !empty($field_label) )
		{
			// $this->empty_label = true;	// Memorize this
			$r .= $this->labelstart
						.'<label for="'.$field_name.'">'.$field_label.$this->label_suffix.'</label>'
						.$this->labelend;
		}
		else
		{	// Empty label:
			// $this->empty_label = false;	// Memorize this
			$r .= $this->labelempty;
		}

		$r .= $this->inputstart;

		return $r;
	}


	/**
	 * End an input field.
	 *
	 * A field is a fielset containing a label div and an input div.
	 *
	 * @return the generated HTML
	 */
	function end_field()
	{
		return $this->inputend
					.$this->fieldend;
	}


	/**
	 * Begin fieldset (with legend).
	 *
	 * @deprecated by ::fieldset but all functionality is not available
	 * @author blueyed
	 * @see {@link end_fieldset()}
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
	 * Note: please use {@link Form::password()} for password fields
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
											$field_maxlength = 0 , $field_class = '', $inputtype = 'text' )
	{
		if( $field_maxlength == 0 )
			$field_maxlength = $field_size;

		$r = $this->begin_field( $field_name, $field_label )
				.'<input type="'.$inputtype.'" name="'.$field_name
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
	 * Builds a password input field.
	 *
	 * Calls the text() method with a 'password' parameter
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
		$this->text( $field_name, $field_value, $field_size, $field_label, $field_note = '',
											$field_maxlength = 0 , $field_class = '', 'password' );
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
		global $month, $weekday_letter, $start_of_week;

		$field_size = strlen( $date_format );

		// Get date part of datetime:
		$field_value = substr( $field_value, 0, 10 );

		$r = $this->begin_field( $field_name, $field_label )
				.'<script language="JavaScript">
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
				.' cal_'.$field_name.'.setWeekStartDay('.$start_of_week.');
						cal_'.$field_name.".setTodayText('".T_('Today')."');
						// -->
					</script>\n"
				.'<input type="text" name="'.$field_name.'" id="'.$field_name.'"
					size="'.$field_size.'" maxlength="'.$field_size.'" value="'.format_to_output($field_value, 'formvalue').'"'
				." />\n"
				.'<a href="#" onClick="cal_'.$field_name.'.select(document.forms[0].'.$field_name.",'anchor_".$field_name."', '".$date_format."' );"
				.' return false;" name="anchor_'.$field_name.'" ID="anchor_'.$field_name.'">'.T_('Select').'</a>'
				.' <span class="notes">('.$date_format.')</span>'
				.$this->end_field();

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
	 * Builds a checkbox field
	 *
	 * @param string the name of the checkbox
	 * @param boolean initial value
	 * @param string label
	 * @param string note
	 * @param string CSS class
	 * @param a boolean indicating if the checkbox must be checked by default
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function checkbox( $field_name, $field_value, $field_label, $field_note = '',
											$field_class = '', $field_checked = 0 )
	{
		$r = $this->begin_field( $field_name, $field_label )
				.'<input type="checkbox" class="checkbox" name="'.$field_name.'" id="'.$field_name.'" value="1"';
		if( $field_value )
		{
			$r .= ' checked="checked"';
		}
		if( !empty($field_class) )
		{
			$r .= ' class="'.$field_class.'"';
		}
		if( !empty($field_id) )
		{
			$r .= ' id="'.$field_id.'"';
		}
		if( $field_checked )
		{
			$r .= ' checked="checked" ';
		}
		$r .= " />\n"
				.'<span class="notes">'.$field_note."</span>\n"
				.$this->end_field();

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
	 $ @param string title to display on top of the form
	 * @param string the class to use for the form tag
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function begin_form( $form_class = NULL, $form_title = '' )
	{
		$r = "\n\n".'<form name="'.$this->form_name.'" id="'.$this->form_name
					.'" method="'.$this->form_method
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
	 * Ends the form field
	 *
	 * @param array optional array to display the buttons before the end of the form
	 * @return mixed true (if output) or the generated HTML if not outputting
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
	 * @param string the title of the fieldset to display in the 'legend' tags
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function fieldset( $title, $class='fieldset' )
	{
		switch( $this->layout )
		{
			case 'table':
				$r = '<tr ';
				if( $class != '' )
				{ //there is a class option to display in the fieldset tag
					$r .= 'class="'.$class.'" ';
				}
				$r .= '><td colspan="2">'."\n";

				if( $title != '' )
				{ // there is a legend tag to display
					$r .= $title;
				}

				$r .= "</td></tr>\n";
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
	 *
	 * @param array a two-dimension array containinj the parameters of the input tag
	 * @param boolean initial value
	 * @param string name
	 * @param string label
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function checklist( $options, $field_name, $field_label )
	{
		$r = $this->begin_field( $field_name, $field_label );
		foreach( $options as $option )
		{ //loop to construct the list of 'input' tags
			$r .= "\t".'<input type="checkbox" name="'.$option[0].'" value="'.$option[1].'" ';
			if( $option[3] )
			{ //the checkbox has to be checked by default
				$r .= ' checked="checked" ';
			}
			if( isset( $option[4] ) && $option[4] )
			{ // the checkbox has to be disabled
				$r .= ' disabled="disabled" ';
			}
			$r .= ' class="checkbox" />'.$option[2]."<br />\n";
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
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function select(
		$field_name,
		$field_value,
		$field_list_callback,
		$field_label,
		$field_note = '',
		$field_class = '' )
	{
		$r = $this->begin_field( $field_name, $field_label )
					."\n".'<select name="'.$field_name.'" id="'.$field_name.'"';
		if( !empty($field_class) )
		{
			$r.= ' class="'.$field_class.'"';
		}
		$r .= ">\n".$field_list_callback( $field_value )
					."</select>\n"
					.'<span class="notes">'.$field_note.'</span>';

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
		global $img_url;

		$r = $this->begin_field( $field_name, $field_label );

		$r .= '<img src="'.$img_url.'blank.gif" width="1" height="1" alt="" />';
		$r .= '<textarea name="'.$field_name.'" id="'.$field_name.'" rows="'.$field_rows
					.'" cols="'.$field_cols.'"';
		if( !empty($field_class) )
		{
			$r .= ' class="'.$field_class.'"';
		}
		$r .= '>'.$field_value.'</textarea>';
		$r .= '<img src="'.$img_url.'blank.gif" width="1" height="1" alt="" />';

		if( !empty($field_note) )
		{
			$r .= '  <span class="notes">'.$field_note.'</span>';
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
	 * Builds an info field.
	 *
	 * @param string the field label
	 * @param string the field info
	 * @return mixed true (if output) or the generated HTML if not outputting
	 * An info field is a fieldset containing a label div and an info div.
	 */
	function info( $field_label, $field_info, $field_note = NULL )
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

		$r .= $field_info;

		if( !empty($field_note) )
		{
			$r .= ' <small class="notes">'.$field_note.'</small>';
		}

		$r .= $this->inputend
				.$this->fieldend;

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
		{	// there are not only hidden buttons : additional tags
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
			$r .= ' name="'.$options[1].'" ';
		}
		else
		{
			$r .= ' name="submit" ';
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
		{ //a name has been specified
			$r .= ' style="'.$options[5].'" ';
		}
		else
		{
			$r .= ' name="submit" ';
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
	 * @param array an array containing the elements of the input tags
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
	 * Builds a submit input tag
	 *
	 * the array must contain :
	 *  - the name (optional)
	 *  - the value (optional)
	 *  - the class (optional)
	 *  - the onclick attribute (optional)
	 *  - the style (optional)
	 *
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
		{// construction of the option array for the button method
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
		$field_notes = '' )
	{
		$r = $this->begin_field( $field_name, $field_label );

		foreach( $field_options as $loop_field_option )
		{
			if( $field_lines ) $r .= "<div>\n";

			$r .= '<label class="radiooption"><input type="radio" class="radio" name="'.$field_name.'" value="'.format_to_output( $loop_field_option[0], 'formvalue' ).'"';
			if( $field_value == $loop_field_option[0] )
			{	// Current selection:
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
		if( !empty( $field_notes ) )
		{
			$r .= '<div><span class="notes">'.$field_notes.'</span></div>';
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
	 * Display a select field and populate it with a cache object.
	 *
	 * @param string field name
	 * @param string default field value
	 * @param DataObjectCache Cache containing values for list
	 * @param string field label to be display before the field
	 * @param string note to be displayed after the field
	 * @param boolean allow to select [none] in list
	 * @param string CSS class for select
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
		$field_object_callback = 'option_list_return' )
	{
		$r = $this->begin_field( $field_name, $field_label )
					."\n".'<select name="'.$field_name.'" id="'.$field_name.'"';

		if( !empty($field_class) )
		{
			$r .= ' class="'.$field_class.'"';
		}
		$r .= '>';
		$r .= $field_object->$field_object_callback( $field_value, $allow_none )
			 		."</select>\n"
					.'<span class="notes">'.$field_note.'</span>';

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
	 * Display a select field and populate it with a cache object.
	 *
	 * @param string field name
	 * @param string string containing options
	 * @param string field label to be display before the field
	 * @param string note to be displayed after the field
	 * @param boolean allow to select [none] in list
	 * @param string CSS class for select
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function select_options(
		$field_name,
		& $field_options,
		$field_label,
		$field_note = NULL,
		$field_class = NULL )
	{
		$r = $this->begin_field( $field_name, $field_label )
					."\n".'<select name="'.$field_name.'" id="'.$field_name.'"';

		if( !empty($field_class) )
		{
			$r .= ' class="'.$field_class.'"';
		}
		$r .= '>'
					.$field_options
					."</select>\n";

		if( !empty( $field_note ) )
		{
			$r .= ' <span class="notes">'.$field_note.'</span>';
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


}

?>