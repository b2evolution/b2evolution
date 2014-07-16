<?php
/**
 * This file implements parameter handling functions.
 *
 * This includes:
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
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id: _param.funcs.php 7121 2014-07-15 11:05:25Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// DEBUG: (Turn switch on or off to log debug info for specified category)
$GLOBALS['debug_params'] = false;


/**
 * Sets a parameter with values from the request or to provided default,
 * except if param is already set!
 *
 * Also removes magic quotes if they are set automatically by PHP.
 * Also forces type.
 * Priority order: POST, GET, COOKIE, DEFAULT.
 *
 * @todo when bad_request_die() gets called, the GLOBAL should not be left set to the invalid value!
 * fp> Why? if the process dies anyway
 *
 * @param string Variable to set
 * @param string Force value type to one of:
 * - integer
 * - float, double
 * - string (strips (HTML-)Tags, trims whitespace)
 * - text like string but allows multiple lines
 * - array/integer (elements of array must be integer)
 * - array/string (strips (HTML-)Tags, trims whitespace of array's elements)
 * - array/array/integer (two dimensional array and the elements must be integers)
 * - array/array/string (strips (HTML-)Tags, trims whitespace of the two dimensional array's elements)
 * - html (does nothing, for now)
 * - raw (does nothing)
 * - '' (does nothing) -- DEPRECATED, use "raw" instead
 * - '/^...$/' check regexp pattern match (string)
 * - boolean (will force type to boolean, but you can't use 'true' as a default since it has special meaning. There is no real reason to pass booleans on a URL though. Passing 0 and 1 as integers seems to be best practice).
 * - url (like string but dies on illegal urls)
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
function param( $var, $type = 'raw', $default = '', $memorize = false,
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
			if( in_array( $type, array( 'array', 'array/integer', 'array/string', 'array/array/integer', 'array/array/string' ) ) && $default === '' )
			{ // Change default '' into array() (otherwise there would be a notice with settype() below)
				$default = array();
			}
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
			case 'html': // Technically does the same as "raw", but may do more in the future.
			case 'raw':
				if( ! is_scalar($GLOBALS[$var]) )
				{ // This happens if someone uses "foo[]=x" where "foo" is expected as string
					debug_die( 'param(-): <strong>'.$var.'</strong> is not scalar!' );
				}

				// do nothing
				if( isset($Debuglog) ) $Debuglog->add( 'param(-): <strong>'.$var.'</strong> as RAW Unsecure HTML', 'params' );
				break;

			case 'htmlspecialchars':
				if( ! is_scalar($GLOBALS[$var]) )
				{ // This happens if someone uses "foo[]=x" where "foo" is expected as string
					debug_die( 'param(-): <strong>'.$var.'</strong> is not scalar!' );
				}

				// convert all html to special characters:
				$GLOBALS[$var] = trim( evo_htmlspecialchars( $GLOBALS[$var] ) );
				// cross-platform newlines:
				$GLOBALS[$var] = preg_replace( "~(\r\n|\r)~", "\n", $GLOBALS[$var] );
				$Debuglog->add( 'param(-): <strong>'.$var.'</strong> as text with html special chars', 'params' );
				break;

			case 'text':
				if( ! is_scalar($GLOBALS[$var]) )
				{ // This happens if someone uses "foo[]=x" where "foo" is expected as string
					debug_die( 'param(-): <strong>'.$var.'</strong> is not scalar!' );
				}

				// strip out any html:
				$GLOBALS[$var] = trim( strip_tags($GLOBALS[$var]) );
				// cross-platform newlines:
				$GLOBALS[$var] = preg_replace( "~(\r\n|\r)~", "\n", $GLOBALS[$var] );
				$Debuglog->add( 'param(-): <strong>'.$var.'</strong> as text', 'params' );
				break;

			case 'string':
				if( ! is_scalar($GLOBALS[$var]) )
				{ // This happens if someone uses "foo[]=x" where "foo" is expected as string
					debug_die( 'param(-): <strong>'.$var.'</strong> is not scalar!' );
				}

				// echo $var, '=', $GLOBALS[$var], '<br />';
				// Make sure the string is a single line
				$GLOBALS[$var] = preg_replace( '~\r|\n~', '', $GLOBALS[$var] );
				// strip out any html:
				$GLOBALS[$var] = trim( strip_tags($GLOBALS[$var]) );

				$Debuglog->add( 'param(-): <strong>'.$var.'</strong> as string', 'params' );
				break;

			case 'url':
				if( ! is_scalar($GLOBALS[$var]) )
				{ // This happens if someone uses "foo[]=x" where "foo" is expected as string
					debug_die( 'param(-): <strong>'.$var.'</strong> is not scalar!' );
				}

				// Make sure the string is a single line
				$GLOBALS[$var] = preg_replace( '~\r|\n~', '', $GLOBALS[$var] );
				// strip out any html:
				$GLOBALS[$var] = trim( strip_tags( $GLOBALS[$var] ) );
				// Decode url:
				$GLOBALS[$var] = urldecode( $GLOBALS[$var] );

				if( ( !empty( $GLOBALS[$var] ) ) && ( ( strpos( $GLOBALS[$var], '"' ) !== false ) || ( ! preg_match( '#^(/|\?|https?://)#i', $GLOBALS[$var] ) ) ) )
				{ // We cannot accept this MISMATCH:
					bad_request_die( sprintf( T_('Illegal value received for parameter &laquo;%s&raquo;!'), $var ) );
				}

				$Debuglog->add( 'param(-): <strong>'.$var.'</strong> as url', 'params' );
				break;

			case 'array':
			case 'array/integer':
			case 'array/string':
			case 'array/array/integer':
			case 'array/array/string':
				if( ! is_array( $GLOBALS[$var] ) )
				{ // This param must be array
					debug_die( 'param(-): <strong>'.$var.'</strong> is not array!' );
				}

				// Store current array in temp var for checking and preparing
				$globals_var = $GLOBALS[$var];
				// Check if the given array type is one dimensional array
				$one_dimensional = ( ( $type == 'array' ) || ( $type == 'array/integer' ) || ( $type == 'array/string' ) );
				// Check if the given array type should contains string elements
				$contains_strings = ( ( $type == 'array/string' ) || ( $type == 'array/array/string' ) );
				if( $one_dimensional )
				{ // Convert to a two dimensional array to handle one and two dimensional arrays the same way
					$globals_var = array( $globals_var );
				}

				foreach( $globals_var as $i => $var_array )
				{
					if( ! is_array( $var_array ) )
					{ // This param must be array
						// Note: In case of one dimensional array params this will never happen
						debug_die( 'param(-): <strong>'.$var.'['.$i.']</strong> is not array!' );
					}

					if( $type == 'array' )
					{ // This param may contain any kind of elements we need to check and validate it recursively
						$globals_var[$i] = param_check_general_array( $var_array );
						break;
					}

					foreach( $var_array as $j => $var_value )
					{
						if( ! is_scalar( $var_value ) )
						{ // This happens if someone uses "foo[][]=x" where "foo[]" is expected as string
							debug_die( 'param(-): element of array <strong>'.$var.'</strong> is not scalar!' );
						}

						if( $contains_strings )
						{ // Prepare string elements of array
							// Make sure the string is a single line
							$var_value = preg_replace( '~\r|\n~', '', $var_value );
							// strip out any html:
							$globals_var[$i][$j] = trim( strip_tags( $var_value ) );
						}
					}
				}

				if( $one_dimensional )
				{ // Extract real array from temp array
					$globals_var = $globals_var[0];
				}
				// Restore current array with prepared data
				$GLOBALS[$var] = $globals_var;

				$Debuglog->add( 'param(-): <strong>'.$var.'</strong> as '.$type, 'params' );

				if( $contains_strings || $type == 'array' )
				{ // This is an array with string elements so it was processed. The array with integer elements must be checked with regexp, so we can't break in that case.
					if( $GLOBALS[$var] === array() && ( $strict_typing === false ) && $use_default )
					{ // We want to consider empty values as invalid and fall back to the default value:
						$GLOBALS[$var] = $default;
					}
					break;
				}

			default:
				if( substr( $type, 0, 1 ) == '/' )
				{	// We want to match against a REGEXP:
					if( ! is_scalar( $GLOBALS[$var] ) )
					{ // This happens if someone uses "foo[]=x" where "foo" is expected as string
						debug_die( 'param(-): <strong>'.$var.'</strong> is not scalar!' );
					}
					elseif( preg_match( $type, $GLOBALS[$var] ) )
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

							case 'array/integer':
							case 'array/array/integer':
								$type = 'array';
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
						{ // We have an empty value and we accept it
							// ok..
						}
						elseif( !empty( $regexp ) )
						{
							if( $type == 'array' )
							{ // Check format of array elements
								$globals_var = $GLOBALS[$var];
								if( $one_dimensional )
								{ // Convert to a two dimensional array to handle one and two dimensional arrays the same way
									$globals_var = array( $globals_var );
								}
								// Loop through the two dimensional array content and make sure each element is an integer and also set the type
								foreach( $globals_var as $i => $var_array )
								{
									foreach( $var_array as $j => $var_value )
									{
										if( !is_scalar( $var_value ) || !preg_match( $regexp, $var_value ) )
										{ // Value of array item does not match!
											bad_request_die( sprintf( T_('Illegal value received for parameter &laquo;%s&raquo;!'), $var ) );
										}
										// Set the type of the array elements to integer
										settype( $globals_var[$i][$j], 'integer' );
									}
								}
								if( $one_dimensional )
								{ // Extract real array from temp array
									$globals_var = $globals_var[0];
								}
								// Restore current array with prepared data
								$GLOBALS[$var] = $globals_var;
							}
							elseif( ( $type == 'boolean' ) && ( strtolower( $GLOBALS[$var] ) == 'false' ) )
							{ // 'false' string must be interpreted as boolean false value
								$GLOBALS[$var] = false;
							}
							elseif( !is_scalar( $GLOBALS[$var] ) || !preg_match( $regexp, $GLOBALS[$var] ) )
							{ // Value of scalar var does not match!
								bad_request_die( sprintf( T_('Illegal value received for parameter &laquo;%s&raquo;!'), $var ) );
							}
						}
					}

					// Change the variable type:
					settype( $GLOBALS[$var], $type );
					if( isset($Debuglog) ) $Debuglog->add( 'param(-): <strong>'.var_export($var, true).'</strong> typed to '.$type.', new value='.var_export($GLOBALS[$var], true), 'params' );
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
	if( ! isset($_POST['actionArray']) )
	{ // if actionArray is POSTed, use this instead of any "action" (which might come via GET)
		$action = param( 'action', 'string', NULL, $memorize );
	}

	if( ! isset($action) )
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
 * Get total seconds from the following fields: months, days, hours, minutes, seconds
 *
 * @param string param name
 * @return integer seconds
 */
function param_duration( $var )
{
	$timeout_sessions_months = param( $var.'_months', 'integer', 0 );
	$timeout_sessions_days = param( $var.'_days', 'integer', 0 );
	$timeout_sessions_hours = param( $var.'_hours', 'integer', 0 );
	$timeout_sessions_minutes = param( $var.'_minutes', 'integer', 0 );
	$timeout_sessions_seconds = param( $var.'_seconds', 'integer', 0 );

	$timeout_sessions = ( ( ( $timeout_sessions_months*30 + $timeout_sessions_days )*24
				+ $timeout_sessions_hours )*60 + $timeout_sessions_minutes )*60 + $timeout_sessions_seconds;

	return $timeout_sessions;
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
function param_check_not_empty( $var, $err_msg = NULL, $field_err_msg = NULL )
{
	if( empty( $GLOBALS[$var] ) )
	{
		if( empty($err_msg) )
		{
			$err_msg = sprintf( T_('The field &laquo;%s&raquo; cannot be empty.'), substr( $var, strpos( $var, '_' )+1 ) );
			$field_err_msg = T_('This field cannot be empty.');
		}

		param_error( $var, $err_msg, $field_err_msg );
		return false;
	}
	return true;
}


/**
 * Checks if the param is an integer (no float, e.g. 3.14).
 *
 * @param string param name
 * @param string error message
 * @return boolean true if OK
 */
function param_check_number( $var, $err_msg, $required = false )
{
	return param_validate( $var, 'check_is_number', $required, $err_msg );
}


/**
 * Check for interval params
 *
 * @param string Min param name
 * @param string Max param name
 * @param string error message if value is NOT a number
 * @param string error message if min value greater than max
 * @return boolean true if OK
 */
function param_check_interval( $var_min, $var_max, $err_msg_number, $err_msg_compare, $required = false )
{
	$result_min = param_validate( $var_min, 'check_is_number', $required, $err_msg_number );
	$result_max = param_validate( $var_max, 'check_is_number', $required, $err_msg_number );
	$result_compare = true;

	if( $result_min && $result_max )
	{
		if( empty( $GLOBALS[$var_min] ) && !empty( $GLOBALS[$var_max] ) )
		{	// Get min value from max value
			$GLOBALS[$var_min] = $GLOBALS[$var_max];
		}
		if( !empty( $GLOBALS[$var_min] ) && empty( $GLOBALS[$var_max] ) )
		{	// Get max value from min value
			$GLOBALS[$var_max] = $GLOBALS[$var_min];
		}

		$result_compare = $GLOBALS[$var_min] <= $GLOBALS[$var_max];
		if( !$result_compare )
		{	// Min value greater than max
			param_error( $var_min, $err_msg_compare );
		}
	}

	return $result_min && $result_max && $result_compare;
}


/**
 * Checks if the param is an integer (no float, e.g. 3.14).
 *
 * @param string number to check
 * @return string error message if number is not valid
 */
function check_is_number( $number )
{
	if( !is_number( $number ) )
	{
		return T_('The number value is invalid.');
	}
}


/**
 * Checks if the param is a decimal number
 *
 * @param string param name
 * @param string error message
 * @return boolean true if OK
 */
function param_check_decimal( $var, $err_msg, $required = false )
{
	return param_validate( $var, 'check_is_decimal', $required, $err_msg );
}


/**
 * Checks if the param is a decimal number
 *
 * @param string decimal to check
 * @return string error message if decimal is not valid
 */
function check_is_decimal( $decimal )
{
	if( !is_decimal( $decimal ) )
	{
		return T_('The decimal value is invalid.');
	}
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
	return param_validate( $var, 'check_is_email', $required, NULL );
}


/**
 * Check that email address looks valid.
 *
 * @param string email address to check
 * @return string error message if address is not valid
 */
function check_is_email( $email )
{
	if( !is_email( $email ) )
	{
		return T_( 'The email address is invalid.' );
	}
}


/**
 * Check if the value is a valid login (in terms of allowed chars)
 *
 * @param string param name
 * @return boolean true if OK
 */
function param_check_valid_login( $var )
{
	global $Settings;

	if( empty( $GLOBALS[$var] ) )
	{ // empty variable is OK
		return T_('Please choose a username.' );
	}

	$check = is_valid_login($GLOBALS[$var]);

	if( ! $check || $check === 'usr' )
	{
		if( $check === 'usr' )
		{	// Special case, the login is valid however we forbid it's usage.
			$msg = T_('Logins cannot start with "usr_", this prefix is reserved for system use.');
		}
		elseif( $Settings->get('strict_logins') )
		{
			$msg = T_('Logins can only contain letters, digits and the following characters: _ .');
		}
		else
		{
			$msg = sprintf( T_('Logins cannot contain whitespace and the following characters: %s'), '\', ", >, <, @' );
		}
		param_error( $var, $msg );
		return false;
	}
	return true;
}


/**
 * @param string param name
 * @return boolean true if OK
 */
function param_check_login( $var, $required = false )
{
	return param_validate( $var, 'check_is_login', $required, NULL );
}


/**
 * Check that login is valid.
 *
 * @param string login to check
 * @return string error message if login is not valid
 */
function check_is_login( $login )
{
	if( !user_exists( $login ) )
	{
 		return sprintf( T_( 'There is no user with username &laquo;%s&raquo;.' ), $login );
	}
}


/**
 * @param string param name
 * @param string
 * @return boolean true if OK
 */
function param_check_url( $var, $context, $field_err_msg = NULL )
{
  /**
	 * @var User
	 */
	global $current_User;

	$Group = $current_User->get_Group();

	if( strpos( $var, '[' ) !== false )
	{	// Variable is array, for example 'input_name[group_name][123][]'
		// We should get a value from $GLOBALS[input_name][group_name][123][0]
		$var_array = explode( '[', $var );
		$url_value = $GLOBALS;
		foreach( $var_array as $var_item )
		{
			$var_item = str_replace( ']', '', $var_item);
			if( empty( $var_item ) )
			{	// Case for []
				$var_item = '0';
			}
			$url_value = $url_value[$var_item];
		}
	}
	else
	{	// Variable with simple name
		$url_value = $GLOBALS[$var];
	}

	if( $error_detail = validate_url( $url_value, $context, ! $Group->perm_bypass_antispam ) )
	{
		param_error( $var, /* TRANS: %s contains error details */ sprintf( T_('Supplied URL is invalid. (%s)'), $error_detail ), $field_err_msg );
		return false;
	}
	return true;
}


/**
 * Checks if the url is valid
 *
 * @param string url to check
 * @return string error message if url is not valid
 */
function check_is_url( $url )
{
	if( !is_url( $url ) )
	{
		return sprintf( T_('Please enter a valid URL, like for example: %s.'), 'http://www.b2evolution.net/' );
	}
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
		param_error( $var, $err_msg, $field_err_msg );
		return false;
	}
	return true;
}


/**
 * Check if the value of a param MATCHES a regular expression (syntax).
 *
 * @param string param name
 * @param string regexp
 * @param string error message
 * @param string|NULL error message for form field ($err_msg gets used if === NULL).
 * @return boolean true if OK
 */
function param_check_regexp( $var, $regexp, $err_msg, $field_err_msg = NULL, $required = true )
{
	if( empty( $GLOBALS[$var] ) && ! $required )
	{ // empty variable is OK
		return true;
	}

	if( ! preg_match( $regexp, $GLOBALS[$var] ) )
	{
		param_error( $var, $err_msg, $field_err_msg );
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
 * @param string date format (php format), defaults to {@link locale_datefmt()}
 */
function param_date( $var, $err_msg, $required, $default = '', $date_format = NULL )
{
	param( $var, 'string', $default );

	$iso_date = param_check_date( $var, $err_msg, $required, $date_format );

	if( $iso_date )
	{
		set_param( $var, $iso_date );
	}

	return $iso_date;
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
	$date_regexp = '~^'.preg_replace_callback( '~(\\\)?(\w)~', create_function( '$m', '
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
		}' ), $date_format ).'$~i'; // case-insensitive?
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
		$day = null;
		$month = null;
		$year = null;

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
 * Checks if the param is a color
 *
 * @param string param name
 * @param string error message
 * @return boolean true if OK
 */
function param_check_color( $var, $err_msg, $required = false )
{
	return param_validate( $var, 'check_is_color', $required, $err_msg );
}


/**
 * Checks if the param is a decimal number
 *
 * @param string decimal to check
 * @return string error message if decimal is not valid
 */
function check_is_color( $color )
{
	if( ! is_color( $color ) )
	{
		return T_('The color value is invalid.');
	}
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

	$cat = param( 'cat', '/^[*\-\|]?([0-9]+(,[0-9]+)*)?$/', $cat_default, true ); // List of cats to restrict to
	$catsel = param( 'catsel', 'array/integer', $catsel_default, true );  // Array of cats to restrict to

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
 * Checks if the phone number is valid
 *
 * @param string phone number to check
 * @return string error message if phone number is not valid
 */
function check_is_phone( $phone )
{
	if( !is_phone( $phone ) )
	{
		return sprintf( T_('Please enter a valid phone number like for example: %s.'), '+1 401-555-1234' );
	}
}


/**
 * @param string param name
 * @param string param name
 * @param boolean Is a password required? (non-empty)
 * @param integer Minimum password length
 * @param array Params
 * @return boolean true if OK
 */
function param_check_passwords( $var1, $var2, $required = false, $min_length = 6, $params = array() )
{
	$params = array_merge( array(
			'msg_pass_new'   => T_('Please enter your new password.'),
			'msg_pass_twice' => T_('Please enter your new password twice.'),
			'msg_pass_diff'  => T_('You typed two different passwords.'),
			'msg_pass_min'   => T_('The minimum password length is %d characters.'),
		), $params );

	$pass1 = get_param($var1);
	$pass2 = get_param($var2);

	if( ! strlen($pass1) && ! strlen($pass2) && ! $required )
	{ // empty is OK:
		return true;
	}

	if( ! strlen($pass1) )
	{
		param_error( $var1, $params['msg_pass_new'] );
		param_error( $var2, $params['msg_pass_twice'] );
		return false;
	}
	if( ! strlen($pass2) )
	{
		param_error( $var2, $params['msg_pass_twice'] );
		return false;
	}

	// checking the password has been typed twice the same:
	if( $pass1 != $pass2 )
	{
		param_error_multiple( array( $var1, $var2 ), $params['msg_pass_diff'] );
		return false;
	}

	if( evo_strlen($pass1) < $min_length )
	{
		param_error_multiple( array( $var1, $var2 ), sprintf( $params['msg_pass_min'], $min_length ) );
		return false;
	}

	return true;
}


/**
 * Checks if the word is valid
 *
 * @param string word to check
 * @return string error message if word is not valid
 */
function check_is_word( $word )
{
	if( !is_word( $word ) )
	{
		return T_('This field should be a single word; A-Z only.');
	}
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

	return $Messages->has_errors();
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

	$Debuglog->add( 'memorize_param: '.var_export($var, true).' '.var_export($type, true).' default='.var_export($default, true).
				( is_null($value) ? '' : ' value='.var_export($value, true) ), 'params' );

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
 *
 * @param string Param name
 * @param mixed Value
 * @return mixed Value
 */
function set_param( $var, $value )
{
	return $GLOBALS[$var] = $value;
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
			// echo 'regenerate_url(): EXPLICIT IGNORE '.$var;
			// $Debuglog->add( 'regenerate_url(): EXPLICIT IGNORE '.$var, 'params' );
			continue;
		}

		$value = $GLOBALS[$var];
		if( $value != $defval )
		{ // Value is not set to default value:
			// Note: sometimes we will want to include an empty value, especially blog=0 ! In that case we set the default for blog to -1.
			// echo "adding $var \n";
			// $Debuglog->add( "regenerate_url(): Using var=$var, type=$type, defval=[$defval], val=[$value]", 'params' );
			// echo "regenerate_url(): Using var=$var, type=$type, defval=[$defval], val=[$value]";

			$params[] = get_param_urlencoded($var, $value, $glue);
		}
		else // if( $var == 's' )
		{
			// $Debuglog->add( "regenerate_url(): DEFAULT ignore var=$var, type=$type, defval=[$defval], val=[$value]", 'params' );
			// echo "regenerate_url(): DEFAULT ignore var=$var, type=$type, defval=[$defval], val=[$value]";
		}
	}

	// Merge in the params we want to force to a specific value:
	if( !empty( $set ) )
	{	// We got some forced params:
		// Transform set param into an array:
		if( !is_array($set) )
		{
			if( $set[0] == '?' )
			{ // Remove leading question mark, e.g. from QUERY_STRING
				$set = substr($set, 1);
			}
			$set = preg_split( '~&(amp;)?~', $set, NULL, PREG_SPLIT_NO_EMPTY );
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
 * Get URL param, urlencoded.
 * This handles arrays, recursively.
 * @return string
 */
function get_param_urlencoded($var, $value, $glue = '&amp;')
{
	if( is_array($value) )
	{ // there is a special formatting in case of arrays
		$r = array();
		$keep_keys = array_diff( array_keys($value), array_keys(array_values($value)) );
		foreach( $value as $key => $value )
		{
			$r[] = get_param_urlencoded($var.'['.($keep_keys ? $key : '').']', $value, $glue);
		}
		return implode($glue, $r);
	}
	else
	{ // not an array : normal formatting
		return rawurlencode($var).'='.rawurlencode($value);
	}
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





/**
 * Sets an HTML parameter and checks for sanitized code.
 *
 * WARNING: this does *NOT* (necessarilly) make the HTML code safe.
 * It only checks on it and produces error messages.
 * It is NOT (necessarily) safe to use the output.
 *
 * @todo dh> Not implemented?!
 *
 * @param string Variable to set
 * @param mixed Default value or TRUE if user input required
 * @param boolean memorize ( see {@link param()} )
 * @param string error message
 *
 * @return string
 */
function param_html( $var, $default = '', $memorize = false, $err_msg )
{

}


/**
 * Checks for sanitized code.
 *
 * WARNING: this does *NOT* (necessarilly) make the HTML code safe.
 * It only checks on it and produces error messages.
 * It is NOT (necessarily) safe to use the output.
 *
 * @param string param name
 * @param string error message
 * @param string field error message
 * @param string the context of this function call
 * @return boolean|string
 */
function param_check_html( $var, $err_msg = '#', $field_err_msg = '#', $context = 'posting' )
{
	global $Messages, $evo_charset;

	// The param was already converted to the $evo_charset in the param() function, we need to use that charset!
	$altered_html = check_html_sanity( $GLOBALS[$var], $context, NULL, $evo_charset );

 	if( $altered_html === false )
	{	// We have errors, do not keep sanitization attemps:
		if( $err_msg == '#' )
		{
			$err_msg = T_('Invalid XHTML.');
		}
		if( $field_err_msg == '#' )
		{
			$field_err_msg = T_('Invalid XHTML.');
		}

		param_error( $var, $err_msg, $field_err_msg );
		return false;
	}

	// Keep the altered HTML (balanced tags, etc.) - NOT necessarily safe if loose checking has been allowed.
	$GLOBALS[$var] = $altered_html;

	return $altered_html;
}


/**
 * @param string param name
 * @param boolean required
 * @return booelan true if OK
 */
function param_check_gender( $var, $required = false )
{
	if( empty( $GLOBALS[$var] ) )
	{	// empty is OK if not required:
		global $current_User;
		if( $required )
		{
			if( $current_User->check_perm( 'users', 'edit' ) )
			{	// Display a note message if user can edit all users
				param_add_message_to_Log( $var, T_('Please select a gender.'), 'note' );
				return true;
			}
			else
			{	// Display an error message
				param_error( $var, T_( 'Please select a gender.' ) );
				return false;
			}
		}
		return true;
	}

	$gender_value = $GLOBALS[$var];
	if( ( $gender_value != 'M' ) && ( $gender_value != 'F' ) )
	{
		param_error( $var, 'Gender value is invalid' );
		return false;
	}

	return true;
}


/**
 * Check html sanity of the not numeric and not object content in a general 'array' param. Handles the array recursivley.
 *
 * @param array the value of an 'array' type param
 * @return array the validated/modified content
 */
function param_check_general_array( $param_value )
{
	if( is_array( $param_value ) )
	{ // This param is an array, validate all of the elements
		foreach( $param_value as $i => $var_value )
		{ // Check array content recursively
			$param_value[$i] = param_check_general_array( $var_value );
		}
		return $param_value;
	}

	if( ! ( is_numeric( $param_value ) || is_object( $param_value ) ) )
	{ // This value is not numeric and not an object, we consider it as string
		// Check the content html sanity
		$param_value = check_html_sanity( $param_value, 'general_array_params' );
	}

	return $param_value;
}


/**
 * DEPRECATED Stub for plugin compatibility:
 */
function format_to_post( $content, $is_comment = 0, $encoding = NULL )
{
	global $current_User;

	$ret = check_html_sanity( $content, ( $is_comment ? 'commenting' : 'posting' ), $current_User, $encoding );
	if( $ret === false )
	{	// ERROR
		return $content;
	}

	// return altered content
	return $ret;
}


/**
 * Check raw HTML input for different levels of sanity including:
 * - XHTML validation
 * - Javascript injection
 * - antispam
 *
 * Also cleans up the content on some levels:
 * - trimming
 * - balancing tags
 *
 * WARNING: this does *NOT* (necessarilly) make the HTML code safe.
 * It only checks on it and produces error messages.
 * It is NOT (necessarily) safe to use the output.
 *
 * @param string The content to format
 * @param string
 * @param User User (used for "posting" and "xmlrpc_posting" context). Default: $current_User
 * @param string Encoding (used for XHTML_Validator only!); defaults to $io_charset
 * @return boolean|string
 */
function check_html_sanity( $content, $context = 'posting', $User = NULL, $encoding = NULL )
{
	global $use_balanceTags, $admin_url;
	global $io_charset, $use_xhtmlvalidation_for_comments, $comment_allowed_tags, $comments_allow_css_tweaks;
	global $Messages;

	if( empty($User) )
	{
		/**
		 * @var User
		 */
		global $current_User;
		$User = $current_User;
	}

	// Add error messages
	$verbose = true;

	switch( $context )
	{
		case 'posting':
		case 'xmlrpc_posting':
			$Group = $User->get_Group();
			if( $context == 'posting' )
			{
				$xhtmlvalidation  = ($Group->perm_xhtmlvalidation == 'always');
			}
			else
			{
				$xhtmlvalidation  = ($Group->perm_xhtmlvalidation_xmlrpc == 'always');
			}
			$allow_css_tweaks = $Group->perm_xhtml_css_tweaks;
			$allow_javascript = $Group->perm_xhtml_javascript;
			$allow_iframes    = $Group->perm_xhtml_iframes;
			$allow_objects    = $Group->perm_xhtml_objects;
			$bypass_antispam  = $Group->perm_bypass_antispam;
			break;

		case 'commenting':
			$xhtmlvalidation  = $use_xhtmlvalidation_for_comments;
			$allow_css_tweaks = $comments_allow_css_tweaks;
			$allow_javascript = false;
			$allow_iframes    = false;
			$allow_objects    = false;
			// fp> I don't know if it makes sense to bypass antispam in commenting context if the user has that kind of permissions.
			// If so, then we also need to bypass in several other places.
			$bypass_antispam  = false;
			break;

		case 'general_array_params':
			$xhtmlvalidation  = false;
			$allow_css_tweaks = true;
			$allow_javascript = false;
			$allow_iframes    = false;
			$allow_objects    = false;
			$bypass_antispam  = false;
			// Do not add error messages in this context
			$verbose = false;
			break;

		case 'head_extension':
			$xhtmlvalidation  = true;
			// We disable everything else, because the XMHTML validator will set explicit rules for the 'head_extension' context
			$allow_css_tweaks = false;
			$allow_javascript = false;
			$allow_iframes    = false;
			$allow_objects    = false;
			$bypass_antispam  = false;
			// Do not add error messages in this context
			$verbose = false;
			break;

		default:
			debug_die( 'unknown context: '.$context );
	}

	$error = false;

	// Replace any & that is not a character or entity reference with &amp;
	$content = preg_replace( '/&(?!#[0-9]+;|#x[0-9a-fA-F]+;|[a-zA-Z_:][a-zA-Z0-9._:-]*;)/', '&amp;', $content );

	// ANTISPAM check:
	$error = ( ( ! $bypass_antispam ) && ( $block = antispam_check($content) ) );
	if( $error && $verbose )
	{ // Add error message
		if( $context == 'xmlrpc_posting' )
		{
			$errmsg = ($context == 'commenting')
				? T_('Illegal content found (spam?)')
				: sprintf( T_('Illegal content found: blacklisted word "%s".'), $block );
		}
		else
		{
			$errmsg = ($context == 'commenting')
				? T_('Illegal content found (spam?).')
				: sprintf( T_('Illegal content found: blacklisted word &laquo;%s&raquo;.'), evo_htmlspecialchars($block) );
		}

		$Messages->add(	$errmsg, 'error' );
	}

	$content = trim( $content );

	if( $use_balanceTags )
	{ // Auto close open tags:
		$content = balance_tags( $content );
	}

	if( $xhtmlvalidation )
	{ // We want to validate XHTML:
		load_class( 'xhtml_validator/_xhtml_validator.class.php', 'XHTML_Validator' );

		$XHTML_Validator = new XHTML_Validator( $context, $allow_css_tweaks, $allow_iframes, $allow_javascript, $allow_objects, $encoding );

		if( ! $XHTML_Validator->check( $content ) ) // TODO: see if we need to use convert_chars( $content, 'html' )
		{
			$error = true;
		}
	}
	else
	{	// We do not WANT to validate XHTML, fall back to basic security checking:
		// This is only as strong as its regexps can parse xhtml. This is significantly inferior to the XHTML checker above.
		// The only advantage of this checker is that it can check for a little security without requiring VALID XHTML.

		if( $context == 'commenting' )
		{	// DEPRECATED but still...
			// echo 'allowed tags:',evo_htmlspecialchars($comment_allowed_tags);
			$content = strip_tags( $content, $comment_allowed_tags );
		}

		// Security checking:
		$check = $content;
		// Open comments or '<![CDATA[' are dangerous
		$check = str_replace('<!', '<', $check);
		// # # are delimiters
		// i modifier at the end means caseless

		// CHECK Styling restictions:
		$css_tweaks_error = ( ( ! $allow_css_tweaks ) && preg_match( '#\s((style|class|id)\s*=)#i', $check, $matches ) );
		if( $css_tweaks_error && $verbose )
		{
			$Messages->add( T_('Illegal CSS markup found: ').evo_htmlspecialchars($matches[1]), 'error' );
		}

		// CHECK JAVASCRIPT:
		$javascript_error = ( ( ! $allow_javascript )
			&& ( preg_match( '~( < \s* //? \s* (script|noscript) )~xi', $check, $matches )
				|| preg_match( '#\s((on[a-z]+)\s*=)#i', $check, $matches )
				// action=, background=, cite=, classid=, codebase=, data=, href=, longdesc=, profile=, src=, usemap=
				|| preg_match( '#=["\'\s]*((javascript|vbscript|about):)#i', $check, $matches ) ) );
		if( $javascript_error && $verbose )
		{
			$Messages->add( T_('Illegal javascript markup found: ').evo_htmlspecialchars($matches[1]), 'error' );
		}

		// CHECK IFRAMES:
		$iframe_error = ( ( ! $allow_iframes ) && preg_match( '~( < \s* //? \s* (frame|iframe) )~xi', $check, $matches ) );
		if( $iframe_error && $verbose )
		{
			$Messages->add( T_('Illegal frame markup found: ').evo_htmlspecialchars($matches[1]), 'error' );
		}

		// CHECK OBJECTS:
		$object_error = ( ( ! $allow_objects ) && preg_match( '~( < \s* //? \s* (applet|object|param|embed) )~xi', $check, $matches ) );
		if( $object_error && $verbose )
		{
			$Messages->add( T_('Illegal object markup found: ').evo_htmlspecialchars($matches[1]), 'error' );
		}

		// Set the final error value based on all of the results
		$error = $error || $css_tweaks_error || $javascript_error || $iframe_error || $object_error;
	}

	if( $error )
	{
		if( $verbose && ( ! empty( $User ) )
			&& ( ! empty( $Group ) ) // This one will basically prevent this case from happening when commenting
			&& $User->check_perm( 'users', 'edit', false ) )
		{
			$Messages->add( sprintf( T_('(Note: To get rid of the above validation warnings, you can deactivate unwanted validation rules in your <a %s>Group settings</a>.)'),
										'href="'.$admin_url.'?ctrl=groups&amp;grp_ID='.$Group->ID.'"' ), 'error' );
		}
		return false;
	}

	// Return sanitized content
	return $content;
}


/**
 * Balances Tags of string using a modified stack.
 *
 * @param string HTML to be balanced
 * @return string Balanced HTML
 */
function balance_tags( $text )
{
	$tagstack = array();
	$stacksize = 0;
	$tagqueue = '';
	$newtext = '';

	# b2 bug fix for comments - in case you REALLY meant to type '< !--'
	// $text = str_replace('< !--', '<    !--', $text);

	// escape any < that does not look like a tag, i-e: that is not followed by a letter like in <a> or a / like in </a>:
	// (also not escaping comments like <!-- )
	$text = preg_replace('#<([^a-z/!]{1})#i', '&lt;$1', $text);

	while( preg_match('~<(\s*/?\w+)\s*(.*?)/?>~s', $text, $regex) )
	{
		$newtext = $newtext . $tagqueue;

		$i = strpos($text,$regex[0]);
		$l = strlen($tagqueue) + strlen($regex[0]);

		// clear the shifter
		$tagqueue = '';

		// Pop or Push
		if( substr($regex[1],0,1) == '/' )
		{ // End Tag
			$tag = strtolower(substr($regex[1],1));

			// if too many closing tags
			if($stacksize <= 0)
			{
				$tag = '';
				//or close to be safe $tag = '/' . $tag;
			}
			// if stacktop value = tag close value then pop
			else if ($tagstack[$stacksize - 1] == $tag)
			{ // found closing tag
				$tag = '</'.$tag.'>'; // Close Tag
				// Pop
				array_pop ($tagstack);
				$stacksize--;
			} else { // closing tag not at top, search for it
				for ($j=$stacksize-1;$j>=0;$j--) {
					if ($tagstack[$j] == $tag) {
					// add tag to tagqueue
						for ($k=$stacksize-1;$k>=$j;$k--){
							$tagqueue .= '</' . array_pop ($tagstack) . '>';
							$stacksize--;
						}
						break;
					}
				}
				$tag = '';
			}
		}
		else
		{ // Begin Tag
			$tag = strtolower($regex[1]);

			// Tag Cleaning

			// Push if not img, br, hr, param or input
			if($tag != 'br' && $tag != 'img' && $tag != 'hr' && $tag != 'param' && $tag != 'input')
			{
				$stacksize = array_push ($tagstack, $tag);
				$closing = '>';
			}
			else
			{
				$closing = ' />';
			}
			// Attributes
			// $attributes = $regex[2];
			$attributes = $regex[2];
			if($attributes)
			{
				$attributes = ' '.trim($attributes);
			}

			$tag = '<'.$tag.$attributes.$closing;
		}

		$newtext .= substr($text,0,$i) . $tag;
		$text = substr($text,$i+$l);
	}

	// Clear Tag Queue
	$newtext = $newtext . $tagqueue;

	// Add Remaining text
	$newtext .= $text;

	// Empty Stack
	while($x = array_pop($tagstack)) {
		$newtext = $newtext . '</' . $x . '>'; // Add remaining tags to close
	}

	# b2 fix for the bug with HTML comments
	// $newtext = str_replace( '< !--', '<'.'!--', $newtext ); // the concatenation is needed to work around some strange parse error in PHP 4.3.1
	// $newtext = str_replace( '<    !--', '< !--', $newtext );

	return $newtext;
}


/**
 * Check if a parameter is set or not
 *
 * Used to decide, if a numeric parameter value is NULL because it isn't set, or because it's set to NULL
 *
 * @param string parameter name
 * @return boolean true if parameter is set
 */
function isset_param( $var )
{
	return isset($_POST[$var]) || isset($_GET[$var]);
}

?>