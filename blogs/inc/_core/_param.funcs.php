<?php
/**
 * This file implements parameter handling functions.
 *
 * This inlcudes:
 * - sanity checking of inputs
 * - removing PHP's stupid "magic" quotes
 * - validating specific inputs (urls, regexps...)
 * - memorizing params
 * - regenerating urls with the memorized params
 * - manually reconstructing urls
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * @author cafelog (team)
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Sets a parameter with values from the request or to provided default,
 * except if param is already set!
 *
 * Also removes magic quotes if they are set automatically by PHP.
 * Also forces type.
 * Priority order: POST, GET, COOKIE, DEFAULT.
 *
 * @param string Variable to set
 * @param string Force value type to one of:
 * - integer
 * - float, double
 * - string (strips (HTML-)Tags, trims whitespace)
 * - array	(TODO:  array/integer  , array/array/string )
 * - html (does nothing)
 * - '' (does nothing)
 * - '/^...$/' check regexp pattern match (string)
 * - boolean (will force type to boolean, but you can't use 'true' as a default since it has special meaning. There is no real reason to pass booleans on a URL though. Passing 0 and 1 as integers seems to be best practice).
 * Value type will be forced only if resulting value (probably from default then) is !== NULL
 * @param mixed Default value or TRUE if user input required
 * @param boolean Do we need to memorize this to regenerate the URL for this page?
 * @param boolean Override if variable already set
 * @param boolean Force setting of variable to default if no param is sent and var wasn't set before
 * @param mixed true will refuse illegal values,
 *              false will try to convert illegal to legal values,
 *              'allow_empty' will refuse illegal values but will always accept empty values (This helps blocking dirty spambots or borked index bots. Saves a lot of processor time by killing invalid requests)
 * @return mixed Final value of Variable, or false if we don't force setting and did not set
 */
function param( $var, $type = '', $default = '', $memorize = false,
								$override = false, $use_default = true, $strict_typing = 'allow_empty' )
{
	global $Debuglog, $debug, $evo_charset, $io_charset;
	// NOTE: we use $GLOBALS[$var] instead of $$var, because otherwise it would conflict with param names which are used as function params ("var", "type", "default", ..)!

	/*
	 * STEP 1 : Set the variable
	 *
	 * Check if already set
	 * WARNING: when PHP register globals is ON, COOKIES get priority over GET and POST with this!!!
	 *   dh> I never understood that comment.. does it refer to "variables_order" php.ini setting?
	 *		fp> I guess
	 */
	if( ! isset( $GLOBALS[$var] ) || $override )
	{
		if( isset($_POST[$var]) )
		{
			$GLOBALS[$var] = remove_magic_quotes( $_POST[$var] );
			// if( isset($Debuglog) ) $Debuglog->add( 'param(-): '.$var.'='.$GLOBALS[$var].' set by POST', 'params' );
		}
		elseif( isset($_GET[$var]) )
		{
			$GLOBALS[$var] = remove_magic_quotes($_GET[$var]);
			// if( isset($Debuglog) ) $Debuglog->add( 'param(-): '.$var.'='.$GLOBALS[$var].' set by GET', 'params' );
		}
		elseif( isset($_COOKIE[$var]))
		{
			$GLOBALS[$var] = remove_magic_quotes($_COOKIE[$var]);
			// if( isset($Debuglog) ) $Debuglog->add( 'param(-): '.$var.'='.$GLOBALS[$var].' set by COOKIE', 'params' );
		}
		elseif( $default === true )
		{
			bad_request_die( sprintf( T_('Parameter &laquo;%s&raquo; is required!'), $var ) );
		}
		elseif( $use_default )
		{	// We haven't set any value yet and we really want one: use default:
			$GLOBALS[$var] = $default;
			// echo '<br>param(-): '.$var.'='.$GLOBALS[$var].' set by default';
			// if( isset($Debuglog) ) $Debuglog->add( 'param(-): '.$var.'='.$GLOBALS[$var].' set by default', 'params' );
		}
		else
		{ // param not found! don't set the variable.
			// Won't be memorized nor type-forced!
			return false;
		}
	}
	else
	{ // Variable was already set but we need to remove the auto quotes
		$GLOBALS[$var] = remove_magic_quotes($GLOBALS[$var]);

		// if( isset($Debuglog) ) $Debuglog->add( 'param(-): '.$var.' already set to ['.var_export($GLOBALS[$var], true).']!', 'params' );
	}

	if( isset($io_charset) && ! empty($evo_charset) )
	{
		$GLOBALS[$var] = convert_charset( $GLOBALS[$var], $evo_charset, $io_charset );
	}

	/*
	 * STEP 2: make sure the data fits the expected type
	 *
	 * type will be forced even if it was set before and not overriden
	 */
	if( !empty($type) && $GLOBALS[$var] !== NULL )
	{ // Force the type
		// echo "forcing type!";
		switch( $type )
		{
			case 'html':
				// do nothing
				if( isset($Debuglog) ) $Debuglog->add( 'param(-): <strong>'.$var.'</strong> as HTML', 'params' );
				break;

			case 'string':
				// strip out any html:
				// echo $var, '=', $GLOBALS[$var], '<br />';
				if( ! is_scalar($GLOBALS[$var]) )
				{ // This happens if someone uses "foo[]=x" where "foo" is expected as string
					// TODO: dh> debug_die() instead?
					$GLOBALS[$var] = '';
					$Debuglog->add( 'param(-): <strong>'.$var.'</strong> is not scalar!', 'params' );
				}
				else
				{
					$GLOBALS[$var] = trim( strip_tags($GLOBALS[$var]) );
					// Make sure the string is a single line
					$GLOBALS[$var] = preg_replace( '¤\r|\n¤', '', $GLOBALS[$var] );
				}
				$Debuglog->add( 'param(-): <strong>'.$var.'</strong> as string', 'params' );
				break;

			default:
				if( substr( $type, 0, 1 ) == '/' )
				{	// We want to match against a REGEXP:
					if( preg_match( $type, $GLOBALS[$var] ) )
					{	// Okay, match
						if( isset($Debuglog) ) $Debuglog->add( 'param(-): <strong>'.$var.'</strong> matched against '.$type, 'params' );
					}
					elseif( $strict_typing == 'allow_empty' && empty($GLOBALS[$var]) )
					{	// No match but we accept empty value:
						if( isset($Debuglog) ) $Debuglog->add( 'param(-): <strong>'.$var.'</strong> is empty: ok', 'params' );
					}
					elseif( $strict_typing )
					{	// We cannot accept this MISMATCH:
						bad_request_die( sprintf( T_('Illegal value received for parameter &laquo;%s&raquo;!'), $var ) );
					}
					else
					{ // Fall back to default:
						$GLOBALS[$var] = $default;
						if( isset($Debuglog) ) $Debuglog->add( 'param(-): <strong>'.$var.'</strong> DID NOT match '.$type.' set to default value='.$GLOBALS[$var], 'params' );
					}

					// From now on, consider this as a string: (we need this when memorizing)
					$type = 'string';
				}
				elseif( $GLOBALS[$var] === '' )
				{ // Special handling of empty values.
					if( $strict_typing === false && $use_default )
					{	// ADDED BY FP 2006-07-06
						// We want to consider empty values as invalid and fall back to the default value:
						$GLOBALS[$var] = $default;
					}
					else
					{	// We memorize the empty value as NULL:
						// fplanque> note: there might be side effects to this, but we need
						// this to distinguish between 0 and 'no input'
						// Note: we do this after regexps because we may or may not want to allow empty strings in regexps
						$GLOBALS[$var] = NULL;
						if( isset($Debuglog) ) $Debuglog->add( 'param(-): <strong>'.$var.'</strong> set to NULL', 'params' );
					}
				}
				elseif( $GLOBALS[$var] === array() )
				{
					if( $strict_typing === false && $use_default )
					{	// ADDED BY FP 2006-09-07
						// We want to consider empty values as invalid and fall back to the default value:
						$GLOBALS[$var] = $default;
					}
				}
				// TODO: dh> if a var (e.g. from POST) comes in as '' but has type "array" it does not get "converted" to array type (nor gets the default used!)
				else
				{
					if( $strict_typing )
					{	// We want to make sure the value is valid:
						$regexp = '';
						switch( $type )
						{
							case 'boolean':
								$regexp = '/^(0|1|false|true)$/i';
								break;

							case 'integer':
								$regexp = '/^(\+|-)?[0-9]+$/';
								break;

							case 'float':
							case 'double':
								$regexp = '/^(\+|-)?[0-9]+(.[0-9]+)?$/';
								break;

							// Note: other types are not tested here.
						}
						if( $strict_typing == 'allow_empty' && empty($GLOBALS[$var]) )
						{	// We have an empty value and we accept it
							// ok..
						}
						elseif( !empty( $regexp ) && ( !is_scalar($GLOBALS[$var]) || !preg_match( $regexp, $GLOBALS[$var] ) ) )
						{	// Value does not match!
							bad_request_die( sprintf( T_('Illegal value received for parameter &laquo;%s&raquo;!'), $var ) );
						}
					}

					// Change the variable type:
					settype( $GLOBALS[$var], $type );
					if( isset($Debuglog) ) $Debuglog->add( 'param(-): <strong>'.$var.'</strong> typed to '.$type.', new value='.$GLOBALS[$var], 'params' );
				}
		}
	}


	/*
	 * STEP 3: memorize the value for later url regeneration
	 */
	if( $memorize )
	{ // Memorize this parameter
		memorize_param( $var, $type, $default );
	}

	// echo $var, '(', gettype($GLOBALS[$var]), ')=', $GLOBALS[$var], '<br />';
	return $GLOBALS[$var];
}


/**
 * Get the param from an array param's first index instead of the value.
 *
 * E.g., for "param[value]" as a submit button you can get the value with
 *       <code>Request::param_arrayindex( 'param' )</code>.
 *
 * @see param_action()
 * @param string Param name
 * @param mixed Default to use
 * @return string
 */
function param_arrayindex( $param_name, $default = '' )
{
	$array = array_keys( param( $param_name, 'array', array() ) );
	$value = array_pop( $array );
	if( is_string($value) )
	{
		$value = substr( strip_tags($value), 0, 50 );  // sanitize it
	}
	elseif( !empty($value) )
	{ // this is probably a numeric index from '<input name="array[]" .. />'
		debug_die( 'Invalid array param!' );
	}
	else
	{
		$value = $default;
	}

	return $value;
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
function param_action( $default = '', $memorize = false )
{
	$action = param( 'action', 'string', NULL, $memorize );

	if( is_null($action) )
	{ // Check $actionArray
		$action = param_arrayindex( 'actionArray', $default );

		set_param( 'action', $action ); // always set "action"
	}

	return $action;
}


/**
 * Get a param from cookie.
 *
 * {@internal This is just a wrapper around {@link param()} which unsets and
 *  restores GET and POST. IMHO this is less hackish, at least performance
 *  wise then using a $sources param for param()}}
 *
 * @uses param()
 * @see param()
 */
function param_cookie($var, $type = '', $default = '', $memorize = false,
		$override = false, $use_default = true, $strict_typing = 'allow_empty')
{
	$save_GET = $_GET;
	$save_POST = $_POST;

	unset( $_GET, $_POST );

	$r = param( $var, $type, $default, $memorize, $override, $use_default, $strict_typing );

	$_GET = $save_GET;
	$_POST = $save_POST;

	return $r;
}


/**
 * @param string param name
 * @param string error message
 * @param string|NULL error message for form field ($err_msg gets used if === NULL).
 * @return boolean true if OK
 */
function param_string_not_empty( $var, $err_msg, $field_err_msg = NULL )
{
	param( $var, 'string', true );
	return param_check_not_empty( $var, $err_msg, $field_err_msg );
}


/**
 * @param string param name
 * @param string error message
 * @param string|NULL error message for form field ($err_msg gets used if === NULL).
 * @return boolean true if OK
 */
function param_check_not_empty( $var, $err_msg, $field_err_msg = NULL )
{
	if( empty( $GLOBALS[$var] ) )
	{
		param_error( $var, $err_msg, $field_err_msg );
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
	if( empty( $GLOBALS[$var] ) && ! $required )
	{ // empty is OK:
		return true;
	}

	if( ! preg_match( '#^[0-9]+$#', $GLOBALS[$var] ) )
	{
		param_error( $var, $err_msg );
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
 * @param string error message (gets printf'ed with $min and $max)
 * @return boolean true if OK
 */
function param_integer_range( $var, $min, $max, $err_msg, $required = true )
{
	param( $var, 'integer', $required ? true : '' );
	return param_check_range( $var, $min, $max, $err_msg, $required );
}


/**
 * Checks if the param is a decimal number (no float, e.g. 3.14) in a given range.
 *
 * @param string param name
 * @param integer min value
 * @param integer max value
 * @param string error message (gets printf'ed with $min and $max)
 * @param boolean Is the param required?
 * @return boolean true if OK
 */
function param_check_range( $var, $min, $max, $err_msg, $required = true )
{
	if( empty( $GLOBALS[$var] ) && ! $required )
	{ // empty is OK:
		return true;
	}

	if( ! preg_match( '~^[-+]?\d+$~', $GLOBALS[$var] ) || $GLOBALS[$var] < $min || $GLOBALS[$var] > $max )
	{
		param_error( $var, sprintf( $err_msg, $min, $max ) );
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
	if( empty( $GLOBALS[$var] ) && ! $required )
	{ // empty is OK:
		return true;
	}

	if( !is_email( $GLOBALS[$var] ) )
	{
		param_error( $var, T_('The email address is invalid.') );
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
	if( $error_detail = validate_url( $GLOBALS[$var], $uri_scheme ) )
	{
		param_error( $var, sprintf( T_('Supplied URL is invalid. (%s)'), $error_detail ) );
		return false;
	}
	return true;
}


/**
 * Check if the value is a file name
 *
 * @param string param name
 * @param string error message
 * @return boolean true if OK
 */
function param_check_filename( $var, $err_msg )
{
	if( $error_filename = validate_filename( $GLOBALS[$var] ) )
	{
		param_error( $var, $error_filename );
		return false;
	}
	return true;
}


/**
 * Check if the value of a param is a regular expression (syntax).
 *
 * @param string param name
 * @param string error message
 * @param string|NULL error message for form field ($err_msg gets used if === NULL).
 * @return boolean true if OK
 */
function param_check_isregexp( $var, $err_msg, $field_err_msg = NULL )
{
	if( ! is_regexp( $GLOBALS[$var] ) )
	{
		param_error( $var, $field_err_msg );
		return false;
	}
	return true;
}


/**
 * Sets a date parameter by converting locale date (if valid) to ISO date.
 *
 * If the date is not valid, it is set to the param unchanged (unconverted).
 *
 * @param string param name
 * @param string error message
 * @param boolean Is a non-empty date required?
 * @param string Default (in the format of $date_format)
 * @param string|NULL date format (php format), defaults to {@link locale_datefmt()}
 */
function param_date( $var, $err_msg, $required, $default = '', $date_format = NULL )
{
	param( $var, 'string', $default );

	$iso_date = param_check_date( $var, $err_msg, $required, $date_format );

	if( $iso_date )
	{
		set_param( $var, $iso_date );
	}

	return $GLOBALS[$var];
}


/**
 * Check if param is an ISO date.
 *
 * NOTE: for tokens like e.g. "D" (abbr. weekday), T_() gets used and it uses the current locale!
 *
 * @param string param name
 * @param string error message
 * @param boolean Is a non-empty date required?
 * @param string date format (php format)
 * @return boolean|string false if not OK, ISO date if OK
 */
function param_check_date( $var, $err_msg, $required = false, $date_format = NULL )
{
	if( empty( $GLOBALS[$var] ) )
	{ // empty is OK if not required:
		if( $required )
		{
			param_error( $var, $err_msg );
			return false;
		}
		return '';
	}

	if( empty( $date_format ) )
	{	// Use locale date format:
		$date_format = locale_datefmt();
	}

	// Convert PHP date format to regexp pattern:
	$date_regexp = '~'.preg_replace_callback( '~(\\\)?(\w)~', create_function( '$m', '
		if( $m[1] == "\\\" ) return $m[2]; // escaped
		switch( $m[2] )
		{
			case "d": return "([0-3]\\d)"; // day, 01-31
			case "j": return "([1-3]?\\d)"; // day, 1-31
			case "l": return "(".str_replace("~", "\~", implode("|", array_map("trim", array_map("T_", $GLOBALS["weekday"])))).")";
			case "D": return "(".str_replace("~", "\~", implode("|", array_map("trim", array_map("T_", $GLOBALS["weekday_abbrev"])))).")";
			case "e": // b2evo extension!
				return "(".str_replace("~", "\~", implode("|", array_map("trim", array_map("T_", $GLOBALS["weekday_letter"])))).")";
				case "S": return "(st|nd|rd|th)"; // english suffix for day

			case "m": return "([0-1]\\d)"; // month, 01-12
			case "n": return "(1?\\d)"; // month, 1-12
			case "F": return "(".str_replace("~", "\~", implode("|", array_map("trim", array_map("T_", $GLOBALS["month"])))).")"; //  A full textual representation of a month, such as January or March
			case "M": return "(".str_replace("~", "\~", implode("|", array_map("trim", array_map("T_", $GLOBALS["month_abbrev"])))).")";

			case "y": return "(\\d\\d)"; // year, 00-99
			case "Y": return "(\\d{4})"; // year, XXXX
			default:
				return $m[0];
		}' ), $date_format ).'~i'; // case-insensitive?
	// Allow additional spaces, e.g. "03  May 2007" when format is "d F Y":
	$date_regexp = preg_replace( '~ +~', '\s+', $date_regexp );
	// echo $date_format.'...'.$date_regexp;

	// Check that the numbers match the date pattern:
	if( preg_match( $date_regexp, $GLOBALS[$var], $numbers ) )
	{	// Date does match pattern:
		//pre_dump( $numbers );

		// Get all date pattern parts. We should get 3 parts!:
		preg_match_all( '/(?<!\\\\)[A-Za-z]/', $date_format, $parts ); // "(?<!\\\\)" means that the letter is not escaped with "\"
		//pre_dump( $parts );

		foreach( $parts[0] as $position => $part )
		{
			switch( $part )
			{
				case 'd':
				case 'j':
					$day = $numbers[$position+1];
					break;

				case 'm':
				case 'n':
					$month = $numbers[$position+1];
					break;
				case 'F': // full month name
					$month = array_search( strtolower($numbers[$position+1]), array_map('strtolower', array_map('trim', array_map('T_', $GLOBALS['month']))) );
					break;
				case 'M':
					$month = array_search( strtolower($numbers[$position+1]), array_map('strtolower', array_map('trim', array_map('T_', $GLOBALS['month_abbrev']))) );
					break;

				case 'y':
				case 'Y':
					$year = $numbers[$position+1];
					if( $year < 50 )
					{
						$year = 2000 + $year;
					}
					elseif( $year < 100 )
					{
						$year = 1900 + $year;
					}
					break;
			}
		}

		if( checkdate( $month, $day, $year ) )
		{ // all clean! :)

			// We convert the value to ISO:
			$iso_date = substr( '0'.$year, -4 ).'-'.substr( '0'.$month, -2 ).'-'.substr( '0'.$day, -2 );

			return $iso_date;
		}
	}

	// Date did not pass all tests:

	param_error( $var, $err_msg );

	return false;
}


/**
 * Sets a date parameter with values from the request or to provided default,
 * And check we have a compact date (numbers only) ( used for URL filtering )
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

	param( $var, 'string', $default, $memorize );

	if( preg_match( '#^[0-9]{4,}$#', $$var ) )
	{	// Valid compact date, all good.
		return $$var;
	}

	// We do not have a compact date, try normal date matching:
	$iso_date = param_check_date( $var, $err_msg, $required );

	if( $iso_date )
	{
		set_param( $var, compact_date( $iso_date ) );
		return $$var;
	}

	// Nothing valid found....
	return '';
}


/**
 * Sets a time parameter with the value from the request of the var argument
 * or of the concat of the var argument_h: var argument_mn: var argument_s ,
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

	$got_time = false;

	if( param( $var, 'string', $default, $memorize, $override, $forceset ) )
	{ // Got a time from text field:
		if( preg_match( '/^(\d\d):(\d\d)(:(\d\d))?$/', $$var, $matches ) )
		{
			$time_h = $matches[1];
			$time_mn = $matches[2];
			$time_s = empty( $matches[4] ) ? 0 : $matches[4];
			$got_time = true;
		}
	}
	elseif( ( $time_h = param( $var.'_h', 'integer', -1 ) ) != -1
				&& ( $time_mn = param( $var.'_mn', 'integer', -1 ) ) != -1 )
	{	// Got a time from selects:
		$time_s = param( $var.'_s', 'integer', 0 );
		$$var = substr('0'.$time_h,-2).':'.substr('0'.$time_mn,-2).':'.substr('0'.$time_s,-2);
		$got_time = true;
	}

	if( $got_time )
	{ // We got a time...
		// Check if ranges are correct:
		if( $time_h >= 0 && $time_h <= 23
			&& $time_mn >= 0 && $time_mn <= 59
			&& $time_s >= 0 && $time_s <= 59 )
		{
			// Time is correct
			return $$var;
		}
	}

	param_error( $var, T_('Please enter a valid time.') );

	return false;
}


/**
 * Extend a LIST parameter with an ARRAY param.
 *
 * Will be used for author/authorsel[], etc.
 * Note: cannot be used for catsel[], because catsel is NON-recursive.
 * @see param_compile_cat_array()
 *
 * @param string Variable to extend
 * @param string Name of array Variable to use as an extension
 * @param boolean Save non numeric prefix?  ( 1 char -- can be used as a modifier, e-g: - + * )
 */
function param_extend_list( $var, $var_ext_array, $save_prefix = true )
{
	// Make sure original var exists:
	if( !isset($GLOBALS[$var]) )
	{
		debug_die( 'Cannot extend non existing param : '.$var );
	}
	$original_val = $GLOBALS[$var];

	// Get extension array:
	$ext_values_array = param( $var_ext_array, 'array', array(), false );
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
	if( empty($original_val) )
	{
		$original_values_array = array();
	}
	else
	{
		$original_values_array = explode( ',', $original_val );
	}
	$new_values = array_merge( $original_values_array, $ext_values_array );
	$new_values = array_unique( $new_values );
	$GLOBALS[$var] = $prefix.implode( ',', $new_values );


	return $GLOBALS[$var];
}


/**
 * Compiles the cat array from $cat (recursive + optional modifiers) and $catsel[] (non recursive)
 * and keeps those values available for future reference (category widget)
 */
function param_compile_cat_array( $restrict_to_blog = 0, $cat_default = NULL, $catsel_default = array() )
{
	// For now, we'll need those as globals!
	// fp> this is used for the categories widget
	// fp> we want might to use a $set_globals params to compile_cat_array()
	global $cat_array, $cat_modifier;

	$cat = param( 'cat', '/^[*\-]?([0-9]+(,[0-9]+)*)?$/', $cat_default, true ); // List of cats to restrict to
	$catsel = param( 'catsel', 'array', $catsel_default, true );  // Array of cats to restrict to

	$cat_array = array();
	$cat_modifier = '';

	compile_cat_array( $cat, $catsel, /* by ref */ $cat_array, /* by ref */ $cat_modifier, $restrict_to_blog );
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
		if( !empty( $GLOBALS[$var] ) )
		{ // Okay, we got at least one:
			return true;
		}
	}

	// Error!
	param_error_multiple( $vars, $err_msg, $field_err_msg );
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
	param( $var, 'string', $default );

	if( $GLOBALS[$var] == 'new' )
	{	// The new option is selected in the combo select, so we need to check if we have a value in the combo input text:
		$GLOBALS[$var.'_combo'] = param( $var.'_combo', 'string' );

		if( empty( $GLOBALS[$var.'_combo'] ) )
		{ // We have no value in the combo input text

			// Set request param to null
			$GLOBALS[$var] = NULL;

			if( !$allow_none )
			{ // it's not allowed, so display error:
				param_error( $var, $err_msg );
			}
		}
	}

	return $GLOBALS[$var];
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
			return $$var;
		}
	}
	return '';
}


/**
 * @param string param name
 * @return boolean true if OK
 */
function param_check_phone( $var, $required = false )
{
	global $$var;

	if( empty( $$var ) && ! $required )
	{ // empty is OK:
		return true;
	}

	if( ! preg_match( '|^\+?[\-*#/(). 0-9]+$|', $$var ) )
	{
		param_error( $var, T_('The phone number is invalid.') );
		return false;
	}
	else
	{ // Keep only 0123456789+ caracters
		$$var = preg_replace( '#[^0-9+]#', '', $$var );
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

	$pass1 = $GLOBALS[$var1];
	$pass2 = $GLOBALS[$var2];

	if( empty($pass1) && empty($pass2) && ! $required )
	{ // empty is OK:
		return true;
	}

	if( empty($pass1) )
	{
		param_error( $var1, T_('Please enter your password twice.') );
		return false;
	}
	if( empty($pass2) )
	{
		param_error( $var2, T_('Please enter your password twice.') );
		return false;
	}

	// checking the password has been typed twice the same:
	if( $pass1 != $pass2 )
	{
		param_error_multiple( array( $var1, $var2), T_('You typed two different passwords.') );
		return false;
	}

	if( strlen($pass1) < $Settings->get('user_minpwdlen') )
	{
		param_error_multiple( array( $var1, $var2), sprintf( T_('The minimum password length is %d characters.'), $Settings->get('user_minpwdlen') ) );
		return false;
	}

	return true;
}


/**
 * Check if there have been validation errors
 *
 * We play it safe here and check for all kind of errors, not just those from this particular class.
 *
 * @return integer
 */
function param_errors_detected()
{
	global $Messages;

	return $Messages->count('error');
}


/**
 * Tell if there is an error on given field.
 */
function param_has_error( $var )
{
	global $param_input_err_messages;

	return isset( $param_input_err_messages[$var] );
}


/**
 * Get error message for a param
 *
 * @return string
 */
function param_get_error_msg( $var )
{
	global $param_input_err_messages;

	if( empty( $param_input_err_messages[$var] ) )
	{
		return '';
	}

	return $param_input_err_messages[$var];
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
	global $param_input_err_messages;

	if( ! isset( $param_input_err_messages[$var] ) )
	{ // We haven't already recorded an error for this field:
		if( $field_err_msg === NULL )
		{
			$field_err_msg = $err_msg;
		}
		$param_input_err_messages[$var] = $field_err_msg;

		if( isset($err_msg) )
		{
			param_add_message_to_Log( $var, $err_msg, 'error' );
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
	global $param_input_err_messages;

	if( $field_err_msg === NULL )
	{
		$field_err_msg = $err_msg;
	}

	foreach( $vars as $var )
	{
		if( ! isset( $param_input_err_messages[$var] ) )
		{ // We haven't already recorded an error for this field:
			$param_input_err_messages[$var] = $field_err_msg;
		}
	}

	if( isset($err_msg) )
	{
		param_add_message_to_Log( $var, $err_msg, 'error' );
	}
}


/**
 * This function is used by {@link param_error()} and {@link param_error_multiple()}.
 *
 * If {@link $link_param_err_messages_to_field_IDs} is true, it will link those parts of the
 * error message that are not already links, to the html IDs of the fields with errors.
 *
 * @param string param name
 * @param string error message
 */
function param_add_message_to_Log( $var, $err_msg, $log_category = 'error' )
{
	global $link_param_err_messages_to_field_IDs;
	global $Messages;

	if( !empty($link_param_err_messages_to_field_IDs) )
	{
		$var_id = Form::get_valid_id($var);
		$start_link = '<a href="#'.$var_id.'" onclick="var form_elem = document.getElementById(\''.$var_id.'\'); if( form_elem ) { if(form_elem.select) { form_elem.select(); } else if(form_elem.focus) { form_elem.focus(); } }">'; // "SELECT" does not have .select()

		if( strpos( $err_msg, '<a' ) !== false )
		{ // there is at least one link in $err_msg, link those parts that are no links
			$err_msg = preg_replace( '~(\s*)(<a\s+[^>]+>[^<]*</a>\s*)~i', '</a>$1&raquo;$2'.$start_link, $err_msg );
		}

		if( substr($err_msg, 0, 4) == '</a>' )
		{ // There was a link at the beginning of $err_msg: we do not prepend an emtpy link before it
			$Messages->add( substr( $err_msg, 4 ).'</a>', $log_category );
		}
		else
		{
			$Messages->add( $start_link.$err_msg.'</a>', $log_category );
		}
	}
	else
	{
		$Messages->add( $err_msg, $log_category );
	}
}



/**
 * Set a param (global) & Memorize it for automatic future use in regenerate_url()
 *
 * @param string Variable to memorize
 * @param string Type of the variable
 * @param mixed Default value to compare to when regenerating url
 * @param mixed Value to set
 */
function memorize_param( $var, $type, $default, $value = NULL )
{
	global $Debuglog, $global_param_list, $$var;

	if( !isset($global_param_list) )
	{ // Init list if necessary:
		if( isset($Debuglog) ) $Debuglog->add( 'init $global_param_list', 'params' );
		$global_param_list = array();
	}

	$Debuglog->add( "memorize_param: $var $type default=$default"
			.(is_null($value) ? '' : " value=$value"), 'params');

	$global_param_list[$var] = array( 'type' => $type, 'default' => (($default===true) ? NULL : $default) );

	if( !is_null( $value ) )
	{	// We want to set the variable too.
		set_param( $var, $value );
	}
}


/**
 * Forget a param so that is will not get included in subsequent {@link regenerate_url()} calls.
 * @param string Param name
 */
function forget_param( $var )
{
	global $Debuglog, $global_param_list;

	$Debuglog->add( 'forget_param('.$var.')', 'params' );

	unset( $global_param_list[$var] );
}


/**
 * Has the param already been memorized?
 */
function param_ismemorized( $var )
{
	global $global_param_list;

	return isset($global_param_list[$var]);
}


/**
 * Set the value of a param (by force! :P)
 *
 * Same as setting a global, except you don't need a global declaration in your function.
 */
function set_param( $var, $value )
{
	$GLOBALS[$var] = $value;
}



/**
 * Get the value of a param.
 *
 * @return NULL|mixed The value of the param, if set. NULL otherwise.
 */
function get_param( $var )
{
	if( ! isset($GLOBALS[$var]) )
	{
		return NULL;
	}

	return $GLOBALS[$var];
}


/**
 * Construct an array of memorized params which are not in the ignore list
 *
 * @param mixed string or array of ignore params
 */
function get_memorized( $ignore = '' )
{
	global $global_param_list;

	$memo = array();

	// Transform ignore params into an array:
	if( empty ( $ignore ) )
	{
		$ignore = array();
	}
	elseif( !is_array($ignore) )
	{
		$ignore = explode( ',', $ignore );
	}

	// Loop on memorize params
	if( isset($global_param_list) )
	{
		foreach( $global_param_list as $var => $thisparam )
		{
			if( !in_array( $var, $ignore ) )
			{
				global $$var;
				$value = $$var;
				$memo[$var] = $$var;
			}
		}
	}
	return $memo;
}


/**
 * Regenerate current URL from parameters
 * This may clean it up
 * But it is also useful when generating static pages: you cannot rely on $_REQUEST[]
 *
 * @param mixed|string (delimited by commas) or array of params to ignore (can be regexps in /.../)
 * @param array|string Param(s) to set
 * @param mixed|string Alternative URL we want to point to if not the current URL (may be absolute if BASE tag gets used)
 * @param string Delimiter to use for multiple params (typically '&amp;' or '&')
 */
function regenerate_url( $ignore = '', $set = '', $pagefileurl = '', $glue = '&amp;' )
{
	global $Debuglog, $global_param_list, $ReqHost, $ReqPath;
	global $base_tag_set;

	// Transform ignore param into an array:
	if( empty($ignore) )
	{
		$ignore = array();
	}
	elseif( !is_array($ignore) )
	{
		$ignore = explode( ',', $ignore );
	}

	// Construct array of all params that have been memorized:
	// (Note: we only include values if they differ from the default and they are not in the ignore list)
	$params = array();
	if( isset($global_param_list) ) foreach( $global_param_list as $var => $thisparam )
	{	// For each saved param...
		$type = $thisparam['type'];
		$defval = $thisparam['default'];

		// Check if the param should to be ignored:
		$skip = false;
		foreach( $ignore as $ignore_pattern )
		{
			if( $ignore_pattern[0] == '/' )
			{ // regexp:
				if( preg_match( $ignore_pattern, $var ) )
				{	// Skip this param!
					$skip = true;
					break;
				}
			}
			else
			{
				if( $var == $ignore_pattern )
				{	// Skip this param!
					$skip = true;
					break;
				}
			}
		}
		if( $skip )
		{ // we don't want to include that param
			// if( isset($Debuglog) ) $Debuglog->add( 'regenerate_url(): EXPLICIT IGNORE '.$var, 'params' );
			continue;
		}

		$value = $GLOBALS[$var];
		if( $value != $defval )
		{ // Value is not set to default value:
			// Note: sometimes we will want to include an empty value, especially blog=0 ! In that case we set the default for blog to -1.
			// echo "adding $var \n";
			// if( isset($Debuglog) ) $Debuglog->add( "regenerate_url(): Using var=$var, type=$type, defval=[$defval], val=[$value]", 'params' );

			if( $type === 'array' )
			{ // there is a special formatting in case of arrays
				$url_array = array();
				foreach( $value as $value )
				{
					$params[] = $var.'%5B%5D='.rawurlencode($value);
				}
			}
			else
			{	// not an array : normal formatting
				$params[] = $var.'='.rawurlencode($value);
			}
		}
		else
		{
			// if( isset($Debuglog) ) $Debuglog->add( "regenerate_url(): DEFAULT ignore var=$var, type=$type, defval=[$defval], val=[$value]", 'params' );
		}
	}

	// Merge in the params we want to force to a specific value:
	if( !empty( $set ) )
	{	// We got some forced params:
		// Transform set param into an array:
		if( !is_array($set) )
		{
			$set = array( $set );
		}
		// Merge them in:
		$params = array_merge( $params, $set );
	}

	// Construct URL:
	if( ! empty($pagefileurl) )
	{
		$url = $pagefileurl;
	}
	else
	{
		if( ! empty($base_tag_set) )
		{
			if( isset($Debuglog) ) $Debuglog->add( 'regenerate_url(): Using full URL because of $base_tag_set.', 'params' );
			$url = $ReqHost.$ReqPath;
		}
		else
		{	// Use just absolute path, because there's no <base> tag used
			$url = $ReqPath;
		}
	}

	if( !empty( $params ) )
	{
		$url = url_add_param( $url, implode( $glue, $params ), $glue );
	}
	// if( isset($Debuglog) ) $Debuglog->add( 'regenerate_url(): ['.$url.']', 'params' );
	return $url;
}


/**
 * Add param(s) at the end of an URL, using either "?" or "&amp;" depending on existing url
 *
 * @param string existing url
 * @param string params to add
 * @param string delimiter to use for more params
 */
function url_add_param( $url, $param, $glue = '&amp;' )
{
	if( empty($param) )
	{
		return $url;
	}

	if( ($anchor_pos = strpos($url, '#')) !== false )
	{ // There's an "#anchor" in the URL
		$anchor = substr($url, $anchor_pos);
		$url = substr($url, 0, $anchor_pos);
	}
	else
	{ // URL without "#anchor"
		$anchor = '';
	}

	if( strpos( $url, '?' ) !== false )
	{ // There are already params in the URL
		return $url.$glue.$param.$anchor;
	}


	// These are the first params
	return $url.'?'.$param.$anchor;
}


/**
 * Add a tail (starting with "/") at the end of an URL before any params (starting with "?")
 *
 * @param string existing url
 * @param string tail to add
 */
function url_add_tail( $url, $tail )
{
	$parts = explode( '?', $url );
	if( substr($parts[0], -1) == '/' )
	{
		$parts[0] = substr($parts[0], 0, -1);
	}
	if( isset($parts[1]) )
	{
		return $parts[0].$tail.'?'.$parts[1];
	}

	return $parts[0].$tail;
}


/**
 * Check the validity of a given URL
 *
 * Checks allowed URI schemes and URL ban list.
 * URL can be empty.
 *
 * Note: We have a problem when trying to "antispam" a keyword which is already blacklisted
 * If that keyword appears in the URL... then the next page has a bad referer! :/
 *
 * {@internal This function gets tested in misc.funcs.simpletest.php.}}
 *
 * @param string Url to validate
 * @param array Allowed URI schemes (see /conf/_formatting.php)
 * @param boolean Must the URL be absolute?
 * @param boolean Return verbose error message? (Should get only used in the backoffice)
 * @return mixed false (which means OK) or error message
 */
function validate_url( $url, & $allowed_uri_scheme, $absolute = false, $verbose = false )
{
	global $Debuglog;

	if( empty($url) )
	{ // Empty URL, no problem
		return false;
	}

	// Validate URL structure
	// fp> NOTE: I made this much more laxist than it used to be.
	// fp> If it turns out I blocked something that was previously allowed, it's a mistake.
	//
	if( preg_match( '~^\w+:~', $url ) )
	{ // there's a scheme and therefor an absolute URL:
		if( substr($url, 0, 6) == 'mailto' )
		{ // mailto:link
			preg_match( '~^(mailto):(.*?)(\?.*)?$~', $url, $match );
			if( ! $match )
			{
				return $verbose
					? sprintf( T_('Invalid email link: %s.'), htmlspecialchars($url) )
					: T_('Invalid email link.');
			}
      elseif( ! is_email($match[2]) )
			{
				return $verbose
					? sprintf( T_('Supplied email address (%s) is invalid.'), htmlspecialchars($match[2]) )
					: T_('Invalid email address.');
			}
		}
		elseif( ! preg_match('~^           # start
			([a-z][a-z0-9+.\-]*)             # scheme
			://                              # authority absolute URLs only
			(\w+(:\w+)?@)?                   # username or username and password (optional)
			[a-z0-9]([a-z0-9.\-])*           # Don t allow anything too funky like entities
			(:[0-9]+)?                       # optional port specification
			~ix', $url, $match) )
		{ // Cannot validate URL structure
			$Debuglog->add( 'URL &laquo;'.$url.'&raquo; does not match url pattern!', 'error' );
			return $verbose
				? sprintf( T_('Invalid URL format (%s).'), htmlspecialchars($url) )
				: T_('Invalid URL format.');
		}

		$scheme = strtolower($match[1]);
		if( !in_array( $scheme, $allowed_uri_scheme ) )
		{ // Scheme not allowed
			$Debuglog->add( 'URI scheme &laquo;'.$scheme.'&raquo; not allowed!', 'error' );
			return $verbose
				? sprintf( T_('URI scheme "%s" not allowed.'), htmlspecialchars($scheme) )
				: T_('URI scheme not allowed.');
		}

		// Search for blocked URLs:
		if( $block = antispam_check($url) )
		{
			return $verbose
				? sprintf( T_('URL "%s" not allowed: blacklisted word "%s".'), htmlspecialchars($url), $block )
				: T_('URL not allowed');
		}
	}
	else
	{ // URL is relative..
		if( $absolute )
		{
			return $verbose ? sprintf( T_('URL "%s" must be absolute.'), htmlspecialchars($url) ) : T_('URL must be absolute.');
		}

		$char = substr($url, 0, 1);
		if( $char != '/' && $char != '#' )
		{ // must start with a slash or hash (for HTML anchors to the same page)
			return $verbose
				? sprintf( T_('URL "%s" must be a full path starting with "/" or an anchor starting with "#".'), htmlspecialchars($url) )
				: T_('URL must be a full path starting with "/" or an anchor starting with "#".');
		}
	}

	return false; // OK
}


/**
 * Checks if a given regular expression is valid.
 *
 * It changes the error_handler and restores it.
 *
 * @author plenque at hotmail dot com {@link http://php.net/manual/en/function.preg-match.php}
 * @param string the regular expression to test
 * @param boolean does the regular expression includes delimiters (and optionally modifiers)?
 * @return boolean
 */
function is_regexp( $reg_exp, $includes_delim = false )
{
	$sPREVIOUSHANDLER = set_error_handler( '_trapError' );
	if( ! $includes_delim )
	{
		$reg_exp = '#'.str_replace( '#', '\#', $reg_exp ).'#';
	}
	preg_match( $reg_exp, '' );
	restore_error_handler( $sPREVIOUSHANDLER );

	return !_traperror();
}


/**
 * Meant to replace error handler temporarily.
 *
 * @return integer number of errors
 */
function _trapError( $reset = 1 )
{
	static $iERRORES;

	if( !func_num_args() )
	{
		$iRETORNO = $iERRORES;
		$iERRORES = 0;
		return $iRETORNO;
	}
	else
	{
		$iERRORES++;
	}
}


/*
 * Clean up the mess PHP has created with its funky quoting everything!
 */
if( get_magic_quotes_gpc() )
{ // That stupid PHP behaviour consisting of adding slashes everywhere is unfortunately on

	if( in_array( strtolower(ini_get('magic_quotes_sybase')), array('on', '1', 'true', 'yes') ) )
	{ // overrides "magic_quotes_gpc" and only replaces single quotes with themselves ( "'" => "''" )
		/**
		 * @ignore
		 */
		function remove_magic_quotes( $mixed )
		{
			if( is_array( $mixed ) )
			{
				foreach($mixed as $k => $v)
				{
					$mixed[$k] = remove_magic_quotes( $v );
				}
			}
			elseif( is_string($mixed) )
			{
				// echo 'Removing slashes ';
				$mixed = str_replace( '\'\'', '\'', $mixed );
			}
			return $mixed;
		}
	}
	else
	{
		/**
		 * Remove quotes from input.
		 * This handles magic_quotes_gpc and magic_quotes_sybase PHP settings/variants.
		 *
		 * NOTE: you should not use it directly, but one of the param-functions!
		 *
		 * @param mixed string or array (function is recursive)
		 * @return mixed Value, with magic quotes removed
		 */
		function remove_magic_quotes( $mixed )
		{
			if( is_array( $mixed ) )
			{
				foreach($mixed as $k => $v)
				{
					$mixed[$k] = remove_magic_quotes( $v );
				}
			}
			elseif( is_string($mixed) )
			{
				// echo 'Removing slashes ';
				$mixed = stripslashes( $mixed );
			}
			return $mixed;
		}
	}
}
else
{
	/**
	 * @ignore
	 */
	function remove_magic_quotes( $mixed )
	{
		return $mixed;
	}
}


/*
 * $Log$
 * Revision 1.3  2007/09/04 19:48:33  fplanque
 * small fixes
 *
 * Revision 1.2  2007/06/29 00:24:43  fplanque
 * $cat_array cleanup tentative
 *
 * Revision 1.1  2007/06/25 10:58:53  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.40  2007/06/17 23:39:59  blueyed
 * Fixed remove_magic_quotes()
 *
 * Revision 1.39  2007/05/28 01:33:22  fplanque
 * permissions/fixes
 *
 * Revision 1.38  2007/05/20 01:02:32  fplanque
 * magic quotes fix
 *
 * Revision 1.37  2007/05/13 18:36:52  blueyed
 * param_check_date(): Allow multiple whitespaces
 *
 * Revision 1.36  2007/05/09 00:54:44  fplanque
 * Attempt to normalize all URLs before adding params
 *
 * Revision 1.35  2007/04/26 00:11:08  fplanque
 * (c) 2007
 *
 * Revision 1.34  2007/03/11 19:12:08  blueyed
 * Escape input in verbose validate_url()
 *
 * Revision 1.33  2007/03/02 01:36:51  fplanque
 * small fixes
 *
 * Revision 1.32  2007/02/28 23:33:19  blueyed
 * doc
 *
 * Revision 1.31  2007/02/26 03:41:16  fplanque
 * doc
 *
 * Revision 1.30  2007/02/25 18:29:55  blueyed
 * MFB: Support "S" (ordinal date suffix) in date format (calendar and validator)
 *
 * Revision 1.29  2007/02/12 17:59:52  blueyed
 * $verbose param for validate_url() - not used yet anywhere, but useful IMHO, e.g. in the backoffice.
 *
 * Revision 1.28  2007/01/26 00:21:23  blueyed
 * Fixed possible E_NOTICE; TODO
 *
 * Revision 1.27  2006/12/28 18:31:30  fplanque
 * prevent impersonating of blog in multiblog situation
 *
 * Revision 1.26  2006/12/22 00:25:48  blueyed
 * Added $absolute param to url_validate()
 *
 * Revision 1.25  2006/12/18 16:16:12  blueyed
 * Fixed E_NOTICE if we're expecting e.g. "integer" but receive "array"
 *
 * Revision 1.24  2006/11/27 21:10:23  fplanque
 * doc
 *
 * Revision 1.23  2006/11/26 23:33:10  blueyed
 * doc
 *
 * Revision 1.22  2006/11/26 02:30:39  fplanque
 * doc / todo
 *
 * Revision 1.21  2006/11/24 18:27:27  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.20  2006/11/17 00:20:44  blueyed
 * doc
 *
 * Revision 1.19  2006/11/14 17:37:22  blueyed
 * doc
 *
 * Revision 1.18  2006/11/14 00:21:58  blueyed
 * debug info for "relative" urls in validate_url()
 *
 * Revision 1.17  2006/11/09 22:56:57  blueyed
 * todo
 *
 * Revision 1.16  2006/11/02 16:31:53  blueyed
 * MFB
 *
 * Revision 1.15  2006/10/26 09:28:58  blueyed
 * - Made param funcs independent from $Debuglog global
 * - Made url_add_param() respect anchors/fragments
 *
 * Revision 1.14  2006/10/23 21:16:00  blueyed
 * MFB: Fix for encoding in regenerate_url()
 *
 * Revision 1.13  2006/10/17 17:27:07  blueyed
 * Allow "#anchor" as valid URL
 *
 * Revision 1.12  2006/10/14 02:12:01  blueyed
 * Added url_absolute(), make_rel_links_abs() + Tests; Fixed validate_url() and allow relative URLs, which get converted to absolute ones in feeds.
 */
?>
