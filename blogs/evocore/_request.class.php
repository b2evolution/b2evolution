<?php
/**
 * This file implements the Request class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Request class: handles the current HTTP request
 *
 * @todo (fplanque)
 */
class Request
{
	/**
	 * @var Array of values, indexed by param name
	 */
	var $params = array();

	/**
	 * @var Array of strings, indexed by param name
	 */
	var $err_messages = array();

	/**
	 * @var Log Reference to a Log object that gets used to add (error) messages to.
	 */
	var $Messages;

	/**
	 * If true, the internal function {@link _add_message_to_Log()} will create links to IDs
	 * of the fields where the error occurred.
	 * @var boolean
	 */
	var $link_log_messages_to_field_IDs = false;


	/**
	 * Constructor.
	 */
	function Request( & $Messages )
	{
		$this->Messages = & $Messages;
	}


	/**
	 * Sets a parameter with values from the request or to provided default,
	 * except if param is already set!
	 *
	 * Also removes magic quotes if they are set automatically by PHP.
	 * Also forces type.
	 * Priority order: POST, GET, COOKIE, DEFAULT.
	 *
	 * @todo Respect $forceset (not setting $this->params[$var], when !$forceset
	 * @todo A previous set global should not be used as default ($override) (because this is "Request").
	 * @todo With register_globals on there will be no sanitizing of the param!
	 *
	 * @param string Variable to set
	 * @param string Force value type to one of:
	 * - integer
	 * - float
	 * - string
	 * - array
	 * - object
	 * - null
	 * - html (does nothing)
	 * - '' (does nothing)
	 * - '/^...$/' check regexp pattern match (string)
	 * - boolean (will force type to boolean, but you can't use 'true' as a default since it has special meaning. There is no real reason to pass booleans on a URL though. Passing 0 and 1 as integers seems to be best practice).
	 * Value type will be forced only if resulting value (probably from default then) is !== NULL
	 * @param mixed Default value or TRUE if user input required
	 * @param boolean Do we need to memorize this to regenerate the URL for this page?
	 * @param boolean Override if variable already set
	 * @param boolean Force setting of variable to default?
	 * @return mixed Final value of Variable, or false if we don't force setting and did not set
	 */
	function param( $var, $type = '', $default = '', $memorize = false, $override = false, $forceset = true )
	{
		return $this->params[$var] = param( $var, $type, $default, $memorize, $override, $forceset );
	}

	/**
	 * Sets several similar parameters at once.
	 *
	 * @param array
	 */
	function params( $vars, $type = '', $default = '', $memorize = false, $override = false, $forceset = true )
	{
		foreach( $vars as $var )
		{
			$this->param( $var, $type, $default, $memorize, $override, $forceset );
		}
	}


  /**
	 * Extend a parameter with an array of params.
	 *
	 * Will be used for author/authorsel[], etc.
	 * Note: cannot be used for catsel[], because catsel is NON-recursive
	 *
	 * @param string Variable to extend
	 * @param string Name of array Variable to use as an extension
	 * @param boolean Save non numeric prefix?
	 */
	function param_extend( $var, $var_ext_array, $save_prefix = true )
	{
		// Make sure original var exists:
		if( !isset($this->params[$var]) )
		{
			die( 'Cannot extend non existing param : '.$var );
		}
		$original_val = $this->params[$var];

		// Get extension array:
		$ext_values_array = $this->param( $var_ext_array, 'array', array(), false );
		if( empty($ext_values_array) )
		{	// No extension required:
			return $original_val;
		}

		// Handle prefix:
		$prefix = '';
		if( $save_prefix )
		{	// We might want to save a prefix:
			$prefix = substr( $original_val, 0, 1 );
			if( is_numeric( $prefix ) )
			{	// The prefix is numeric, so it's NOT a prefix
				$prefix = '';
			}
			else
			{	// We save the prefix, we must crop if off from the values:
				$original_val = substr( $original_val, 1 );
			}
		}

		// Merge values:
		$original_values_array = empty($original_val) ? array() : explode( ',', $original_val );
		$new_values = array_merge( $original_values_array, $ext_values_array );
		$new_values = array_unique( $new_values );
		$this->params[$var] = $prefix.implode( ',', $new_values );

		// Save into global var:
		global $$var;
		$$var = $this->params[$var];

		return $this->params[$var];
	}


	/**
	 * Get the value of a param.
	 *
	 * @return NULL|mixed The value of the param, if set. NULL otherwise.
	 */
	function get( $var )
	{
		return isset($this->params[$var]) ? $this->params[$var] : NULL;
	}


	/**
	 * @param string param name
	 * @param string error message
	 * @param string|NULL error message for form field ($err_msg gets used if === NULL).
	 * @return boolean true if OK
	 */
	function param_string_not_empty( $var, $err_msg, $field_err_msg = NULL )
	{
		$this->param( $var, 'string', true );
		return $this->param_check_not_empty( $var, $err_msg, $field_err_msg );
	}


	/**
	 * @param string param name
	 * @param string error message
	 * @param string|NULL error message for form field ($err_msg gets used if === NULL).
	 * @return boolean true if OK
	 */
	function param_check_not_empty( $var, $err_msg, $field_err_msg = NULL )
	{
		if( empty( $this->params[$var] ) )
		{
			$this->param_error( $var, $err_msg, $field_err_msg );
			return false;
		}
		return true;
	}


	/**
	 * @param string param name
	 * @param string error message
	 * @return boolean true if OK
	 */
	function param_check_number( $var, $err_msg, $required = false )
	{
		if( empty( $this->params[$var] ) && ! $required )
		{ // empty is OK:
			return true;
		}

		if( ! preg_match( '#^[0-9]+$#', $this->params[$var] ) )
		{
			$this->param_error( $var, $err_msg );
			return false;
		}
		return true;
	}


	/**
	 * @param string param name
	 * @param integer min value
	 * @param integer max value
	 * @param string error message
	 * @return boolean true if OK
	 */
	function param_integer_range( $var, $min, $max, $err_msg, $required = true )
	{
		$this->param( $var, 'integer', $required ? true : '' );
		return $this->param_check_range( $var, $min, $max, $err_msg, $required );
	}


	/**
	 * @param string param name
	 * @param integer min value
	 * @param integer max value
	 * @param string error message
	 * @return boolean true if OK
	 */
	function param_check_range( $var, $min, $max, $err_msg, $required = true )
	{
		if( empty( $this->params[$var] ) && ! $required )
		{ // empty is OK:
			return true;
		}

		if( $this->params[$var] < $min || $this->params[$var] > $max )
		{
			$this->param_error( $var, sprintf( $err_msg, $min, $max ) );
			return false;
		}
		return true;
	}


	/**
	 * @param string param name
	 * @return boolean true if OK
	 */
	function param_check_email( $var, $required = false )
	{
		if( empty( $this->params[$var] ) && ! $required )
		{ // empty is OK:
			return true;
		}

		if( !is_email( $this->params[$var] ) )
		{
			$this->param_error( $var, T_('The email address is invalid.') );
			return false;
		}
		return true;
	}


	/**
	 * @param string param name
	 * @return boolean true if OK
	 */
	function param_check_phone( $var, $required = false )
	{
		if( empty( $this->params[$var] ) && ! $required )
		{ // empty is OK:
			return true;
		}

		if( ! preg_match( '|^\+?[\-*#/(). 0-9]+$|', $this->params[$var] ) )
		{
			$this->param_error( $var, T_('The phone number is invalid.') );
			return false;
		}
		return true;
	}


	/**
	 * @param string param name
	 * @param string error message
	 * @return boolean true if OK
	 */
	function param_check_url( $var, & $uri_scheme )
	{
		if( $error_detail = validate_url( $this->params[$var], $uri_scheme ) )
		{
			$this->param_error( $var, sprintf( T_('Supplied URL is invalid. (%s)'), $error_detail ) );
			return false;
		}
		return true;
	}



	/**
	 * Check if param is an ISO date
	 *
	 * @deprecated by param_check_date_format()
	 * @param string param name
	 * @param string error message
	 * @param boolean Is a non-empty date required?
	 * @return boolean true if OK
	 */
	function param_check_date( $var, $err_msg, $required = false )
	{
		return (bool)$this->param_check_date_format( $var, $err_msg, array( 'required' => $required ) );
	}


	/**
	 * Check if param is a valid date (format wise).
	 *
	 * @param string param name
	 * @param string error message
	 * @param array|boolean Additional params:
	 *        - 'required': Is non-empty date required? Default: true.
	 *        - 'date_pattern': Pattern for the date, using named capturing groups ('day', 'month', 'year').
	 *           Default: '#^(\d\d\d\d)-(\d\d)-(\d\d)$#' (ISO)
	 *           You can also use "Named Capturing Groups" and write it like this:
	 *           '#^(?P<year>\d\d\d\d)-(?P<month>\d\d)-(?P<day>\d\d)$#' (ISO).
	 *           This allows to use for example '#^(?P<day>\d\d)-(?P<month>\d\d)-(?P<year>\d\d\d\d)$#'
	 *           NOTE: "Named Capturing Groups" require PHP 4.3.3! If no named groups are given we'll use
	 *           (1 == year, 2 == month, 3 == day).
	 *        - 'field_err_msg': Error for the form field
	 * @return boolean|array true if empty, but OK. False if not valid. Matching array if ok.
	 */
	function param_check_date_format( $var, $err_msg, $func_params = array() )
	{
		$required = ( !isset($func_params['required']) || $func_params['required'] );

		if( empty( $this->params[$var] ) && ! $required )
		{ // empty is OK:
			return true;
		}

		if( !isset($func_params['date_pattern']) )
		{ // ISO by default
			//$func_params['date_pattern'] = '#^(?P<year>\d\d\d\d)-(?P<month>[01]?\d)-(?P<day>[0123]?\d)$#'; // This may be useful, when taking care of leading zeros afterwards again, eg date('Y-m-d').
			//$func_params['date_pattern'] = '#^(?P<year>\d\d\d\d)-(?P<month>[01]\d)-(?P<day>[0123]\d)$#';
			//$func_params['date_pattern'] = '#^(?P<year>\d\d\d\d)-(?P<month>\d\d)-(?P<day>\d\d)$#';
			$func_params['date_pattern'] = '#^(\d\d\d\d)-(\d\d)-(\d\d)$#';
		}

		if( preg_match( $func_params['date_pattern'], $this->params[$var], $match ) )
		{
			if( isset( $match['month'], $match['day'], $match['year'] ) )
			{ // extended/preferred format (PHP >= 4.3.3)
				if( checkdate( $match['month'], $match['day'], $match['year'] ) )
				{ // all clean! :)
					return $match;
				}
			}
			elseif( isset( $match[1], $match[2], $match[3] ) )
			{ // Fallback to numeric format ( 1 == year, 2 == month, 3 == day )
				if( checkdate( $match[2], $match[3], $match[1] ) )
				{ // all clean! :)
					return $match;
				}
			}
		}

		if( !isset($func_params['field_err_msg']) )
		{
			$func_params['field_err_msg'] = NULL;
		}

		$this->param_error( $var, $err_msg, $func_params['field_err_msg'] );
		return false;
	}


	/**
	 * Check if the value of a param is a regular expression (syntax).
	 *
	 * @param string param name
	 * @param string error message
	 * @param string|NULL error message for form field ($err_msg gets used if === NULL).
	 * @return boolean true if OK
	 */
	function param_check_regexp( $var, $err_msg, $field_err_msg = NULL )
	{
		if( ! isRegexp( $this->params[$var] ) )
		{
			$this->param_error( $var, $field_err_msg );
			return false;
		}
		return true;
	}


	/**
	 * @param string param name
	 * @param string param name
	 * @param boolean Is a password required? (non-empty)
	 * @return boolean true if OK
	 */
	function param_check_passwords( $var1, $var2, $required = false )
	{
		global $Settings;

		$pass1 = $this->params[$var1];
		$pass2 = $this->params[$var2];

		if( empty($pass1) && empty($pass2) && ! $required )
		{ // empty is OK:
			return true;
		}

		if( empty($pass1) )
		{
			$this->param_error( $var1, T_('Please enter your password twice.') );
			return false;
		}
		if( empty($pass2) )
		{
			$this->param_error( $var2, T_('Please enter your password twice.') );
			return false;
		}

		// checking the password has been typed twice the same:
		if( $pass1 != $pass2 )
		{
			$this->param_error_multiple( array( $var1, $var2), T_('You typed two different passwords.') );
			return false;
		}

		if( strlen($pass1) < $Settings->get('user_minpwdlen') )
		{
			$this->param_error_multiple( array( $var1, $var2), sprintf( T_('The mimimum password length is %d characters.'), $Settings->get('user_minpwdlen') ) );
			return false;
		}

		return true;
	}


	/**
	 * @param array of param names
	 * @param string error message
	 * @param string|NULL error message for form field ($err_msg gets used if === NULL).
	 * @return boolean true if OK
	 */
	function params_check_at_least_one( $vars, $err_msg, $field_err_msg = NULL )
	{
		foreach( $vars as $var )
		{
			if( !empty( $this->params[$var] ) )
			{ // Okay, we got at least one:
				return true;
			}
		}

		// Error!
		$this->param_error_multiple( $vars, $err_msg, $field_err_msg );
		return false;
	}


	/**
	 * Check if there have been validation errors
	 *
	 * We play it safe here and check for all kind of errors, not just those from this particlar class.
	 *
	 * @return integer
	 */
	function validation_errors()
	{
		return $this->Messages->count('error');
	}


	/**
	 *
	 * @param string param name
	 * @param string error message
	 * @param string|NULL error message for form field ($err_msg gets used if === NULL).
	 */
	function param_error( $var, $err_msg, $field_err_msg = NULL )
	{
		if( ! isset( $this->err_messages[$var] ) )
		{ // We haven't already recorded an error for this field:
			if( $field_err_msg === NULL )
			{
				$field_err_msg = $err_msg;
			}
			$this->err_messages[$var] = $field_err_msg;

			$this->_add_message_to_Log( $var, $err_msg, 'error' );
		}
	}


	/**
	 *
	 * @param array of param names
	 * @param string error message
	 * @param string|NULL error message for form fields ($err_msg gets used if === NULL).
	 */
	function param_error_multiple( $vars, $err_msg, $field_err_msg = NULL )
	{
		if( $field_err_msg === NULL )
		{
			$field_err_msg = $err_msg;
		}

		foreach( $vars as $var )
		{
			if( ! isset( $this->err_messages[$var] ) )
			{ // We haven't already recorded an error for this field:
				$this->err_messages[$var] = $field_err_msg;
			}
		}

		$this->_add_message_to_Log( $var, $err_msg, 'error' );
	}


	/**
	 * This function is used by {@link param_error()} and {@link param_error_multiple()}.
	 *
	 * @access protected
	 *
	 * @param string param name
	 * @param string error message
	 */
	function _add_message_to_Log( $var, $err_msg, $log_category = 'error' )
	{
		if( $this->link_log_messages_to_field_IDs )
		{
			$var_id = Form::get_valid_id($var);
			$this->Messages->add( '<a href="#'.$var_id.'" onclick="var form_elem = document.getElementById(\''.$var_id.'\'); if( form_elem ) { form_elem.select(); }">'.$err_msg.'</a>', $log_category );
		}
		else
		{
			$this->Messages->add( $err_msg, $log_category );
		}
	}
}

/*
 * $Log$
 * Revision 1.20  2005/10/29 18:23:25  blueyed
 * Rollback changed default of $override; added todos.
 *
 * Revision 1.19  2005/10/28 22:24:22  blueyed
 * DEfault to override for param(), because it handles requests and should not respect a previously set global with the same name. I know that this is bad, because the global param() function will override this global, but this is a design flaw, because the Request object should not handle globals.
 *
 * Revision 1.18  2005/10/18 01:57:13  blueyed
 * param_check_date_format(): use failsafe pattern for PHP < 4.3.3; doc
 *
 * Revision 1.17  2005/10/17 19:35:57  fplanque
 * no message
 *
 * Revision 1.16  2005/10/17 12:43:38  marian
 * changed my "bugfix" back due to a too old PHP-Version that I used while testing. Sorry about that
 *
 * Revision 1.15  2005/10/16 18:02:12  marian
 * bugfix in param_check_date_format
 *
 * Revision 1.14  2005/10/13 22:00:48  blueyed
 * Added $field_msg parameter where appropriate; doc
 *
 * Revision 1.13  2005/10/12 18:24:37  fplanque
 * bugfixes
 *
 * Revision 1.12  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.11  2005/08/25 16:06:45  fplanque
 * Isolated compilation of categories to use in an ItemList.
 * This was one of the oldest bugs on the list! :>
 *
 * Revision 1.10  2005/08/18 16:15:12  blueyed
 * Added param_check_date_format(), which allows better refining of date checks if needed later
 *
 * Revision 1.8  2005/08/10 13:19:49  blueyed
 * param(): only set/remember param if it has been set (which must not be the case for !$forceset) [forgotten with the last commit]
 *
 * Revision 1.7  2005/08/10 13:18:03  blueyed
 * Added property $link_log_messages_to_field_IDs;
 * get(): explicitly return NULL if param is not set;
 * added optional $field_msg param to param_error()/param_error_multiple();
 * doc
 *
 * Revision 1.6  2005/06/10 18:25:44  fplanque
 * refactoring
 *
 * Revision 1.5  2005/06/06 17:59:39  fplanque
 * user dialog enhancements
 *
 * Revision 1.4  2005/06/03 20:14:39  fplanque
 * started input validation framework
 *
 */
?>
