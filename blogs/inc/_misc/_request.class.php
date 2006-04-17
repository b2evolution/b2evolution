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
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
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
	var $link_log_messages_to_field_IDs = true;


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
	 * Set the value of a param (by force! :P)
	 */
	function set_param( $var, $value )
	{
		$this->params[$var] = $value;
		$GLOBALS[$var] = $value;
	}


	/**
	 * Memorize a parameter for automatic future use in regenerate_url()
	 */
	function memorize_param( $var, $type, $default, $value = NULL )
	{
		memorize_param( $var, $type, $default, $value ); // note: will also set the global $$var if $valie is not NULL
		if( !is_null($value) )
		{
			$this->params[$var] = $value;
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
			debug_die( 'Cannot extend non existing param : '.$var );
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
 	 * Sets a time parameter with the value from the request of the var argument or of the concat of the var argument_h: var argument_mn: var argument_s ,
	 * except if param is already set!
	 *
	 * @param string Variable to set
	 * @param mixed Default value or TRUE if user input required
	 * @param boolean Do we need to memorize this to regenerate the URL for this page?
	 * @param boolean Override if variable already set
	 * @param boolean Force setting of variable to default?
	 * @return mixed Final value of Variable, or false if we don't force setting and did not set
	 */
	function param_time( $var, $default = '', $memorize = false,	$override = false, $forceset = true )
	{
		global $$var;

		if( $this->param( $var, '', $default, $memorize, $override, $forceset ) )
		{
			return $this->params[$var];
		}
		elseif ( ( $time_h = param( $var.'_h' ) ) && ( $time_mn = param( $var.'_mn' ) ) && ( $time_s = param ( $var.'_s', '', '00' ) ) )
		{
			$$var = $time_h.':'.$time_mn.':'.$time_s;
			$this->params[$var] = $$var;
			return $$var;
		}
		else
		{
			return false;
		}
	}


	/**
	 * set a parameter with the second part(X2) of the value from request ( X1-X2 )
	 *
	 * @param string Variable to set
	 *
	 */
	function param_child_select_value( $var )
	{
		global $$var;

		if( $val = param( $var, 'string' ) )
		{ // keep only the second part of val
			preg_match( '/^[0-9]+-([0-9]+)$/', $val, $res );

			if( isset( $res[1] ) )
			{ //set to the var the second part of val
				$$var = $res[1];
				$this->params[$var] = $res[1];
				return $$var;
			}
		}
		return '';
	}


	/**
	 * Check if the value is a file name
	 *
	 * @param string param name
	 * @param string error message
	 * @return boolean true if OK
	 */
	function param_isFilename( $var, $err_msg )
	{
		if( $error_filename = validate_filename( $this->params[$var] ) )
		{
			$this->param_error( $var, $error_filename );
			return false;
		}
		return true;
	}


	/**
	 * Get the action from params.
	 *
	 * If we got no "action" param, we'll check for an "actionArray" param
	 * ( <input type="submit" name="actionArray[real_action]" ...> ).
	 * And the real $action will be found in the first key...
	 * When there are multiple submit buttons, this is smarter than checking the value which is a translated string.
	 * When there is an image button, this allows to work around IE not sending the value (it only sends X & Y coords of the click).
	 *
	 * @param mixed Default to use.
	 * @return string
	 */
	function param_action( $default = '' )
	{
		#$this->param( 'action', 'string', NULL, true ); // blueyed>> is there a reason to remember? (taken from files.php)
		$action = $this->param( 'action', 'string', NULL );

		if( is_null($action) )
		{ // Check $actionArray
			$action = $this->param_arrayindex( 'actionArray', $default );

			$this->set_param( 'action', $action ); // always set "action"
		}

		return $action;
	}


	/**
	 * Get the param from an array param's first index.
	 *
	 * E.g., for "param[value]" as a submit button you can get the value with
	 *       <code>Request::param_arrayindex( 'param' )</code>.
	 *
	 * @see Request::param_action()
	 * @param string Param name
	 * @param mixed Default to use
	 * @return string
	 */
	function param_arrayindex( $param_name, $default = '' )
	{
		$array = array_keys( $this->param( $param_name, 'array', array() ) );
		$action = array_pop($array);
		if( is_string($action) )
		{
			$action = substr( strip_tags($action), 0, 50 );  // sanitize it
		}
		elseif( !empty($action) )
		{ // this is probably a numeric index from '<input name="array[]" .. />'
			debug_die( 'Invalid action!' );
		}
		else
		{
			$action = $default;
		}

		return $action;
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
	 * Checks if the param is a decimal number (no float, e.g. 3.14).
	 *
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
	 * Gets a param and makes sure it's a decimal number (no float, e.g. 3.14) in a given range.
	 *
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
	 * Checks if the param is a decimal number (no float, e.g. 3.14) in a given range.
	 *
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

		if( ! preg_match( '~^[-+]?\d+$~', $this->params[$var] ) || $this->params[$var] < $min || $this->params[$var] > $max )
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
		global $$var;

		if( empty( $this->params[$var] ) && ! $required )
		{ // empty is OK:
			return true;
		}

		if( ! preg_match( '|^\+?[\-*#/(). 0-9]+$|', $this->params[$var] ) )
		{
			$this->param_error( $var, T_('The phone number is invalid.') );
			return false;
		}
		else
		{ // Keep only 0123456789+ caracters
			$this->params[$var] = preg_replace( '#[^0-9+]#', '', $this->params[$var]);
			$$var = $this->params[$var];
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
	 *           Default: '#^(\d\d\d\d)-?(\d\d)-?(\d\d)$#' (accepts both ISO and "compact")
	 *         	 		You can also use "Named Capturing Groups" and write it like this:
	 *         	  	'#^(?P<year>\d\d\d\d)-(?P<month>\d\d)-(?P<day>\d\d)$#' (ISO).
	 *           		This allows to use for example '#^(?P<day>\d\d)-(?P<month>\d\d)-(?P<year>\d\d\d\d)$#'
	 *           		NOTE: "Named Capturing Groups" require PHP 4.3.3! If no named groups are given we'll use
	 *           		(1 == year, 2 == month, 3 == day).
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
			$func_params['date_pattern'] = '#^(\d\d\d\d)-?(\d\d)-?(\d\d)$#';
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
	 * Sets a date parameter with values from the request or to provided default,
	 * And check if param is a valid date (format wise).
	 *
	 * @param string Variable to set
	 * @param mixed Default value or TRUE if user input required
	 * @param boolean memorize ( see {@link param()} )
	 * @param string error message
	 * @param boolean 'required': Is non-empty date required? Default: true.
	 *
	 * @return string the compact date value ( yyyymmdd )
	 */
	function param_compact_date( $var, $default = '', $memorize = false, $err_msg, $required = false )
	{
		global $$var;

		$this->params[$var] = param( $var, 'string', $default, $memorize );

		if( $this->param_check_date_format( $var, $err_msg, array( 'required' => $required ) ) )
		{	// Valid DATE input format!
			// Convert to output format:
			$this->params[$var] = compact_date( $this->params[$var] );
			$$var = $this->params[$var];
			return $$var;
		}
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
		if( ! is_regexp( $this->params[$var] ) )
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
			$this->param_error_multiple( array( $var1, $var2), sprintf( T_('The minimum password length is %d characters.'), $Settings->get('user_minpwdlen') ) );
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
	 * Sets a combo parameter with values from the request,
	 * => the value of the select option and the input text value if new is selected
	 * Display an error if the new value is selected that the input text has a value
	 *
	 * @param string Variable to set
 	 * @param mixed Default value or TRUE if user input required
	 * @param boolean true: allows to select new without entring a value in the input combo text
	 * @param string error message
	 *
	 * @return string position status ID or 'new' or '' if new is seleted but not input text value
	 *
	 */
	function param_combo( $var, $default, $allow_none, $err_msg = ''  )
	{
		$this->params[$var] = param( $var, 'string', $default );

		if( $this->params[$var] == 'new' )
		{	// The new option is selected in the combo select, so we need to check if we have a value in the combo input text:
			$this->params[$var.'_combo'] = param( $var.'_combo', 'string' );

			if( empty( $this->params[$var.'_combo'] ) )
			{ // We have no value in the combo input text

				// Set request param to null
				$this->params[$var] = NULL;

				if( !$allow_none )
				{ // it's not allowed, so display error:
					$this->param_error( $var, $err_msg );
				}
			}
		}

		return $this->params[$var];
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
	 * Add an error for a variable, either to the Form's field and/or the global {@link $Messages} object.
	 *
	 * @param string param name
	 * @param string|NULL error message (by using NULL you can only add an error to the field, but not the $Message object)
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

			if( isset($err_msg) )
			{
				$this->_add_message_to_Log( $var, $err_msg, 'error' );
			}
		}
	}


	/**
	 * Add an error for multiple variables, either to the Form's field and/or the global {@link $Messages} object.
	 *
	 * @param array of param names
	 * @param string|NULL error message (by using NULL you can only add an error to the field, but not the $Message object)
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

		if( isset($err_msg) )
		{
			$this->_add_message_to_Log( $var, $err_msg, 'error' );
		}
	}


	/**
	 * This function is used by {@link param_error()} and {@link param_error_multiple()}.
	 *
	 * If {@link $link_log_messages_to_field_IDs} is true, it will link those parts of the
	 * error message that are not already links, to the html IDs of the fields with errors.
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
			$start_link = '<a href="#'.$var_id.'" onclick="var form_elem = document.getElementById(\''.$var_id.'\'); if( form_elem ) { form_elem.select(); }">';

			if( strpos( $err_msg, '<a' ) !== false )
			{ // there is at least one link in $err_msg, link those parts that are no links
				$err_msg = preg_replace( '~(\s*)(<a\s+[^>]+>[^<]*</a>\s*)~i', '</a>$1&raquo;$2'.$start_link, $err_msg );
			}

			if( substr($err_msg, 0, 4) == '</a>' )
			{ // There was a link at the beginning of $err_msg: we do not prepend an emtpy link before it
				$this->Messages->add( substr( $err_msg, 4 ).'</a>', $log_category );
			}
			else
			{
				$this->Messages->add( $start_link.$err_msg.'</a>', $log_category );
			}
		}
		else
		{
			$this->Messages->add( $err_msg, $log_category );
		}
	}


	/**
	 * Compiles the cat array from $cat (recursive + optional modifiers) and $catsel[] (non recursive)
	 * and keeps those values available for future reference
	 */
	function compile_cat_array( $restrict_to_blog = 0, $cat_default = NULL, $catsel_default = array() )
	{
		// For now, we'll also need those as globals!
		global $cat_array, $cat_modifier;

		$cat = $this->param( 'cat', '/^[*\-]?([0-9]+(,[0-9]+)*)?$/', $cat_default, true ); // List of cats to restrict to
		$catsel = $this->param( 'catsel', 'array', $catsel_default, true );  // Array of cats to restrict to

		$cat_array = array();
		$cat_modifier = '';

		compile_cat_array( $cat, $catsel, /* by ref */ $cat_array, /* by ref */ $cat_modifier, $restrict_to_blog );

		// Also memorize inside of Request:
		$this->params['cat_array'] = $cat_array;
		$this->params['cat_modifier'] = $cat_modifier;
	}

}

/*
 * $Log$
 * Revision 1.10  2006/04/17 23:44:19  blueyed
 * doc fix
 *
 * Revision 1.9  2006/04/14 19:25:32  fplanque
 * evocore merge with work app
 *
 * Revision 1.8  2006/04/11 22:09:08  blueyed
 * Fixed validation of negative integers (and also allowed "+" at the beginning)
 *
 * Revision 1.7  2006/03/26 20:25:39  blueyed
 * is_regexp: allow check with modifiers, which the Filelist now uses internally
 *
 * Revision 1.6  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.5  2006/03/12 20:51:53  blueyed
 * Moved Request::param_UserSettings() to UserSettings::param_Request()
 *
 * Revision 1.4  2006/03/12 19:52:18  blueyed
 * param_check_range(): check for numeric (no float!)
 *
 * Revision 1.3  2006/03/06 20:03:40  fplanque
 * comments
 *
 * Revision 1.2  2006/02/24 19:49:00  blueyed
 * Enhancements
 *
 * Revision 1.1  2006/02/23 21:12:18  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.33  2006/02/10 22:08:07  fplanque
 * Various small fixes
 *
 * Revision 1.32  2006/02/03 21:58:05  fplanque
 * Too many merges, too little time. I can hardly keep up. I'll try to check/debug/fine tune next week...
 *
 * Revision 1.31  2006/01/26 21:20:24  blueyed
 * Default for $link_log_messages_to_field_IDs changed to true, adding better usability for form errors.
 *
 * Revision 1.30  2006/01/20 16:40:56  blueyed
 * Cleanup
 *
 * Revision 1.29  2006/01/04 15:02:10  fplanque
 * better filtering design
 *
 * Revision 1.28  2005/12/30 20:13:40  fplanque
 * UI changes mostly (need to double check sync)
 *
 * Revision 1.27  2005/12/21 20:39:04  fplanque
 * minor
 *
 * Revision 1.26  2005/12/12 19:21:23  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.25  2005/12/10 02:48:51  blueyed
 * Added param_action()
 *
 * Revision 1.24  2005/12/08 22:49:18  blueyed
 * Typo
 *
 * Revision 1.23  2005/12/05 18:17:19  fplanque
 * Added new browsing features for the Tracker Use Case.
 *
 * Revision 1.22  2005/12/05 18:05:13  blueyed
 * Merged 1.20.2.2 from post-phoenix
 *
 * Revision 1.21  2005/12/05 16:24:09  blueyed
 * Use debug_die()
 *
 * Revision 1.20.2.2  2005/11/16 07:34:29  blueyed
 * param_check_range(): check if it's numeric!
 * _add_message_to_Log(): handle links inside of error messages
 *
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