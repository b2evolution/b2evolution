<?php
/**
 * This file implements the Fast Form handling class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );



/**
 * Form class
 */
class Form
{
	var	$output = true;

	/**
	 * Constructor
	 */
	function Form( $layout = 'fieldset' )
	{
		switch( $layout )
		{
			case 'table':
				$this->fieldstart = "<tr>\n";
				$this->labelstart = '<td class="label">';
				$this->labelend = "</td>\n";
				$this->inputstart = '<td class="input">';
				$this->inputend = "</td>\n";
				$this->fieldend = "</tr>\n\n";
				break;

			case 'fieldset':
				$this->fieldstart = "<fieldset>\n";
				$this->labelstart = '<div class="label">';
				$this->labelend = "</div>\n";
				$this->inputstart = '<div class="input">';
				$this->inputend = "</div>\n";
				$this->fieldend = "</fieldset>\n\n";
				break;

			default:
				// "none" (no layout)
				$this->fieldstart = '';
				$this->labelstart = '';
				$this->labelend = "\n";
				$this->inputstart = '';
				$this->inputend = "\n";
				$this->fieldend = "\n";
		}
	}


  /**
	 * Start an input field.
	 *
	 * A field is a fielset containing a label div and an input div.
	 */
	function begin_field( $field_name, $field_label )
	{
		return $this->fieldstart
					.$this->labelstart
					.'<label for="'.$field_name.'">'.$field_label.':</label>'
					.$this->labelend
					.$this->inputstart;
	}

  /**
	 * End an input field.
	 *
	 * A field is a fielset containing a label div and an input div.
	 */
	function end_field()
	{
		return $this->inputend
					.$this->fieldend;
	}

	/**
	 * Builds a text (or password) input field.
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
				.'	cal_'.$field_name.'.setDayHeaders( '
							."'".T_($weekday_letter[0])."',"
							."'".T_($weekday_letter[1])."',"
							."'".T_($weekday_letter[2])."',"
							."'".T_($weekday_letter[3])."',"
							."'".T_($weekday_letter[4])."',"
							."'".T_($weekday_letter[5])."',"
							."'".T_($weekday_letter[6])."' );\n"
				.'	cal_'.$field_name.'.setWeekStartDay('.$start_of_week.');
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
	 * @param boolean to output (default)  or not
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function checkbox( $field_name, $field_value, $field_label, $field_note = '', $field_class = '' )
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

}
