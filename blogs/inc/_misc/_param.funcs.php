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
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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
	global $global_param_list, $Debuglog, $debug, $evo_charset, $io_charset;
	// NOTE: we use $GLOBALS[$var] instead of $$var, because otherwise it would conflict with param names which are used as function params ("var", "type", "default", ..)!

	/*
	 * STEP 1 : Set the variable
	 *
	 * Check if already set
	 * WARNING: when PHP register globals is ON, COOKIES get priority over GET and POST with this!!!
	 */
	if( !isset( $GLOBALS[$var] ) || $override )
	{
		if( isset($_POST[$var]) )
		{
			$GLOBALS[$var] = remove_magic_quotes( $_POST[$var] );
			// $Debuglog->add( 'param(-): '.$var.'='.$GLOBALS[$var].' set by POST', 'params' );
		}
		elseif( isset($_GET[$var]) )
		{
			$GLOBALS[$var] = remove_magic_quotes($_GET[$var]);
			// $Debuglog->add( 'param(-): '.$var.'='.$GLOBALS[$var].' set by GET', 'params' );
		}
		elseif( isset($_COOKIE[$var]))
		{
			$GLOBALS[$var] = remove_magic_quotes($_COOKIE[$var]);
			// $Debuglog->add( 'param(-): '.$var.'='.$GLOBALS[$var].' set by COOKIE', 'params' );
		}
		elseif( $default === true )
		{
			bad_request_die( sprintf( T_('Parameter &laquo;%s&raquo; is required!'), $var ) );
		}
		elseif( $use_default )
		{	// We haven't set any value yet and we really want one: use default:
			$GLOBALS[$var] = $default;
			// echo '<br>param(-): '.$var.'='.$GLOBALS[$var].' set by default';
			// $Debuglog->add( 'param(-): '.$var.'='.$GLOBALS[$var].' set by default', 'params' );
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

		// $Debuglog->add( 'param(-): '.$var.' already set to ['.var_export($GLOBALS[$var], true).']!', 'params' );
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
				$Debuglog->add( 'param(-): <strong>'.$var.'</strong> as HTML', 'params' );
				break;

			case 'string':
				// strip out any html:
				// echo $var, '=', $GLOBALS[$var], '<br />';
				$GLOBALS[$var] = trim( strip_tags($GLOBALS[$var]) );
				$Debuglog->add( 'param(-): <strong>'.$var.'</strong> as string', 'params' );
				break;

			default:
				if( substr( $type, 0, 1 ) == '/' )
				{	// We want to match against a REGEXP:
					if( preg_match( $type, $GLOBALS[$var] ) )
					{	// Okay, match
						$Debuglog->add( 'param(-): <strong>'.$var.'</strong> matched against '.$type, 'params' );
					}
					elseif( $strict_typing == 'allow_empty' && empty($GLOBALS[$var]) )
					{	// No match but we accept empty value:
						$Debuglog->add( 'param(-): <strong>'.$var.'</strong> is empty: ok', 'params' );
					}
					elseif( $strict_typing )
					{	// We cannot accept this MISMATCH:
						bad_request_die( sprintf( T_('Illegal value received for parameter &laquo;%s&raquo;!'), $var ) );
					}
					else
					{ // Fall back to default:
						$GLOBALS[$var] = $default;
						$Debuglog->add( 'param(-): <strong>'.$var.'</strong> DID NOT match '.$type.' set to default value='.$GLOBALS[$var], 'params' );
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
						$Debuglog->add( 'param(-): <strong>'.$var.'</strong> set to NULL', 'params' );
					}

					// TODO: dh> if a var comes in as '' but has type "array" it does not get "converted" to array type (nor gets the default used!)
				}
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
						elseif( !empty( $regexp ) && !preg_match( $regexp, $GLOBALS[$var] ) )
						{	// Value does not match!
							bad_request_die( sprintf( T_('Illegal value received for parameter &laquo;%s&raquo;!'), $var ) );
						}
					}

					// Change the variable type:
					settype( $GLOBALS[$var], $type );
					$Debuglog->add( 'param(-): <strong>'.$var.'</strong> typed to '.$type.', new value='.$GLOBALS[$var], 'params' );
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
 * Memorize a parameter for automatic future use in regenerate_url()
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
		$Debuglog->add( 'init $global_param_list', 'params' );
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
 * Set the value of a param (by force! :P)
 */
function set_param( $var, $value )
{
	global $$var;
	$$var = $value;
}



function get_param()
{
	
}


/**
 * Forget a param so that is will not get included in subsequent {@link regenerate_url()} calls.
 */
function forget_param( $var )
{
	global $Debuglog, $global_param_list;

	$Debuglog->add( 'forget_param('.$var.')', 'params' );

	unset( $global_param_list[$var] );

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
function regenerate_url( $ignore = '', $set = '', $pagefileurl = '', $moredelim = '&amp;' )
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
			// $Debuglog->add( 'regenerate_url(): EXPLICIT IGNORE '.$var, 'params' );
			continue;
		}

		global $$var;
		$value = $$var;
		if( (!empty($value)) && ($value != $defval) )
		{ // Value exists and is not set to default value:
			// echo "adding $var \n";
			// $Debuglog->add( "regenerate_url(): Using var=$var, type=$type, defval=[$defval], val=[$value]", 'params' );

			if( $type === 'array' )
			{ // there is a special formatting in case of arrays
				$url_array = array();
				foreach( $value as $value )
				{
					$params[] = $var.'%5B%5D='.$value;
				}
			}
			else
			{	// not an array : normal formatting
				$params[] = $var.'='.$value;
			}
		}
		else
		{
			// $Debuglog->add( "regenerate_url(): DEFAULT ignore var=$var, type=$type, defval=[$defval], val=[$value]", 'params' );
		}
	}

	// Merge in  the params we want to force to a specifoc value:
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
			$Debuglog->add( 'regenerate_url(): Using full URL because of $base_tag_set.', 'params' );
			$url = $ReqHost.$ReqPath;
		}
		else
		{	// Use just absolute path, because there's no <base> tag used
			$url = $ReqPath;
		}
	}

	if( !empty( $params ) )
	{
		$url = url_add_param( $url, implode( $moredelim, $params ), $moredelim );
	}
	// $Debuglog->add( 'regenerate_url(): ['.$url.']', 'params' );
	return $url;
}


/**
 * Add param(s) at the end of an URL, using either "?" or "&amp;" depending on existing url
 *
 * @param string existing url
 * @param string params to add
 * @param string delimiter to use for more params
 */
function url_add_param( $url, $param, $moredelim = '&amp;' )
{
	if( empty($param) )
	{
		return $url;
	}

	if( strpos( $url, '?' ) !== false )
	{ // There are already params in the URL
		return $url.$moredelim.$param;
	}

	// These are the first params
	return $url.'?'.$param;
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
 * We do not allow relative URLs on purpose because they make no sense in RSS feeds.
 * blueyed>> we could always make them absolute again in the RSS feeds.. I think the extra
 *           processing involved there is worth the benefit of having relative links.
 * fp> ok, but we have to develop both simultaneously.
 *
 * {@internal This function gets tested in misc.funcs.simpletest.php.}}
 *
 * @param string Url to validate
 * @param array Allowed URI schemes (see /conf/_formatting.php)
 * @return mixed false (which means OK) or error message
 */
function validate_url( $url, & $allowed_uri_scheme )
{
	global $debug, $Debuglog;

	if( empty($url) )
	{ // Empty URL, no problem
		return false;
	}

	// minimum length: http://az.fr
	if( strlen($url) < 12 )
	{ // URL too short!
		$Debuglog->add( 'URL &laquo;'.$url.';&raquo; is too short!', 'error' );
		return T_('Invalid URL');
	}

	// Validate URL structure
	// fp> NOTE: I made this much more laxist than it used to be.
	// fp> If it turns out I blocked something that was previously allowed, it's a mistake.
	//
	// Things still not validated correctly:
	//   - umlauts in domains/url, e.g. http://läu.de/
	//   - "mailto:" links
	if( ! preg_match('~^               # start
		([a-z][a-z0-9+.\-]*)             # scheme
		://                              # authority absolute URLs only
		(\w+(:\w+)?@)?                   # username or username and password (optional)
		[a-z0-9]([a-z0-9.\-])*           # Don t allow anything too funky like entities
		(:[0-9]+)?                       # optional port specification
		~ix', $url, $matches) )
	{ // Cannot validate URL structure
		$Debuglog->add( 'URL &laquo;'.$url.'&raquo; does not match url pattern!', 'error' );
		return T_('Invalid URL');
	}

	$scheme = strtolower($matches[1]);
	if( !in_array( $scheme, $allowed_uri_scheme ) )
	{ // Scheme not allowed
		$Debuglog->add( 'URL scheme &laquo;'.$scheme.'&raquo; not allowed!', 'error' );
		return T_('URI scheme not allowed');
	}

	// Search for blocked URLs:
	if( $block = antispam_check($url) )
	{
		if( $debug ) return 'Url refused. Debug info: blacklisted word: ['.$block.']';
		return T_('URL not allowed');
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



/**
 * Clean up the mess PHP has created with its funky quoting everything!
 *
 * @param mixed string or array (function is recursive)
 * @return mixed
 */
if( ini_get('magic_quotes_sybase') ) // overrides "magic_quotes_gpc" and only replaces single quotes with themselves ( "'" => "''" )
{
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
elseif( ini_get('magic_quotes_gpc') )
{ // That stupid PHP behaviour consisting of adding slashes everywhere is unfortunately on
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
 * Revision 1.1  2006/08/20 13:47:25  fplanque
 * extracted param funcs from misc
 *
 * Revision 1.99  2006/08/19 07:56:31  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.98  2006/08/18 21:02:15  fplanque
 * minor
 *
 * Revision 1.97  2006/08/16 23:50:17  fplanque
 * moved credits to correct place
 *
 * Revision 1.96  2006/08/07 23:49:52  blueyed
 * Display Debuglog object stored in session (after redirect) separately.
 *
 * Revision 1.95  2006/08/07 22:29:33  fplanque
 * minor / doc
 *
 * Revision 1.94  2006/08/07 16:49:35  fplanque
 * doc
 *
 * Revision 1.93  2006/08/07 00:09:45  blueyed
 * marked bug
 *
 * Revision 1.92  2006/08/05 17:21:01  blueyed
 * Fixed header_redirect handling: do not replace &amp; with & generally, but only when taken from request params.
 *
 * Revision 1.91  2006/07/31 19:46:18  blueyed
 * Only save Debuglog in Session, if it's not empty (what it will be mostly)
 *
 * Revision 1.90  2006/07/31 15:39:06  blueyed
 * Save Debuglog into Session before redirect and load it from there, if available.
 *
 * Revision 1.89  2006/07/30 13:50:39  blueyed
 * Added $log_app_errors setting.
 *
 * Revision 1.88  2006/07/26 20:13:40  blueyed
 * Do not strip "action" param on redirect_to when redirecting. Instead, strip "confirm" and "confirmed" (as a security measure).
 *
 * Revision 1.87  2006/07/25 18:49:59  fplanque
 * no message
 *
 * Revision 1.86  2006/07/24 01:06:37  blueyed
 * comment
 *
 * Revision 1.85  2006/07/24 00:05:44  fplanque
 * cleaned up skins
 *
 * Revision 1.84  2006/07/23 22:35:48  blueyed
 * doc
 *
 * Revision 1.83  2006/07/23 21:58:14  fplanque
 * cleanup
 *
 * Revision 1.82  2006/07/19 19:55:12  blueyed
 * Fixed charset handling (especially windows-1251)
 *
 * Revision 1.81  2006/07/08 22:33:43  blueyed
 * Integrated "simple edit form".
 *
 * Revision 1.80  2006/07/08 14:13:01  blueyed
 * Added server error/warning logging to debug_die()
 *
 * Revision 1.79  2006/07/07 18:15:48  fplanque
 * fixes
 *
 * Revision 1.78  2006/07/06 18:50:42  fplanque
 * cleanup
 *
 * Revision 1.77  2006/07/03 22:01:23  blueyed
 * Support empty url in action_icon() (=> no A tag)
 *
 * Revision 1.76  2006/07/02 21:53:31  blueyed
 * time difference as seconds instead of hours; validate user#1 on upgrade; bumped new_db_version to 9300.
 *
 * Revision 1.75  2006/07/02 21:32:09  blueyed
 * minor
 *
 * Revision 1.74  2006/06/30 22:58:13  blueyed
 * Abstracted charset conversation, not much tested.
 *
 * Revision 1.73  2006/06/27 11:28:33  blueyed
 * Fixed/optimized remove_magic_quotes()
 *
 * Revision 1.72  2006/06/26 23:10:24  fplanque
 * minor / doc
 *
 * Revision 1.71  2006/06/25 23:42:47  blueyed
 * merge error(?)
 *
 * Revision 1.70  2006/06/25 23:34:15  blueyed
 * wording pt2
 *
 * Revision 1.69  2006/06/25 23:23:38  blueyed
 * wording
 *
 * Revision 1.68  2006/06/23 19:41:20  fplanque
 * no message
 *
 * Revision 1.67  2006/06/22 22:30:04  blueyed
 * htsrv url for password related scripts (login, register and profile update)
 *
 * Revision 1.66  2006/06/22 18:37:47  fplanque
 * fixes
 *
 * Revision 1.65  2006/06/19 21:06:55  blueyed
 * Moved ETag- and GZip-support into transport optimizer plugin.
 *
 * Revision 1.64  2006/06/19 20:59:38  fplanque
 * noone should die anonymously...
 *
 * Revision 1.63  2006/06/19 16:52:09  fplanque
 * better param() function
 *
 * Revision 1.59  2006/06/14 17:24:14  fplanque
 * A little better debug_die()... useful for bozos.
 * Removed bloated trace on error param from DB class. KISS (Keep It Simple Stupid)
 *
 * Revision 1.58  2006/06/13 22:07:34  blueyed
 * Merged from 1.8 branch
 *
 * Revision 1.54.2.3  2006/06/12 20:00:41  fplanque
 * one too many massive syncs...
 *
 * Revision 1.57  2006/06/05 23:15:00  blueyed
 * cleaned up plugin help links
 *
 * Revision 1.56  2006/05/29 19:28:44  fplanque
 * no message
 *
 * Revision 1.55  2006/05/19 18:15:05  blueyed
 * Merged from v-1-8 branch
 *
 * Revision 1.54.2.1  2006/05/19 15:06:25  fplanque
 * dirty sync
 *
 * Revision 1.54  2006/05/12 21:53:38  blueyed
 * Fixes, cleanup, translation for plugins
 *
 * Revision 1.53  2006/05/04 10:12:20  blueyed
 * Normalization/doc
 *
 * Revision 1.52  2006/05/04 01:08:20  blueyed
 * Normalization/doc fix
 *
 * Revision 1.51  2006/05/04 01:05:37  blueyed
 * Fix for PHP4
 *
 * Revision 1.50  2006/05/03 01:53:43  blueyed
 * Encode subject in mails correctly (if mbstrings is available)
 *
 * Revision 1.49  2006/05/02 22:25:28  blueyed
 * Comment preview for frontoffice.
 *
 * Revision 1.48  2006/04/30 18:29:33  blueyed
 * Fixed validate_url() for user/pass; more explicit match
 *
 * Revision 1.47  2006/04/29 01:24:05  blueyed
 * More decent charset support;
 * unresolved issues include:
 *  - front office still forces the blog's locale/charset!
 *  - if there's content in utf8, it cannot get displayed with an I/O charset of latin1
 *
 * Revision 1.46  2006/04/28 16:06:05  blueyed
 * Fixed encoding for format_to_post
 *
 * Revision 1.45  2006/04/27 20:10:34  fplanque
 * changed banning of domains. Suggest a prefix by default.
 *
 * Revision 1.44  2006/04/24 20:14:00  blueyed
 * doc
 *
 * Revision 1.43  2006/04/24 19:14:19  blueyed
 * Added test for callback_on_non_matching_blocks()
 *
 * Revision 1.42  2006/04/24 15:43:36  fplanque
 * no message
 *
 * Revision 1.41  2006/04/22 16:42:12  blueyed
 * Fixes for make_clickable
 *
 * Revision 1.40  2006/04/22 16:30:02  blueyed
 * cleanup
 *
 * Revision 1.39  2006/04/22 02:29:26  blueyed
 * minor
 *
 * Revision 1.38  2006/04/21 16:55:29  blueyed
 * doc, polished header_redirect()
 *
 * Revision 1.37  2006/04/20 22:24:08  blueyed
 * plugin hooks cleanup
 *
 * Revision 1.36  2006/04/20 22:12:49  blueyed
 * todo
 *
 * Revision 1.35  2006/04/20 16:26:16  fplanque
 * minor
 *
 * Revision 1.34  2006/04/20 14:33:46  blueyed
 * todo
 *
 * Revision 1.33  2006/04/19 22:26:25  blueyed
 * cleanup/polish
 *
 * Revision 1.32  2006/04/19 20:14:03  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.31  2006/04/17 23:59:04  blueyed
 * re-fix, cleanup
 *
 * Revision 1.30  2006/04/14 19:16:07  fplanque
 * icon cleanup
 *
 * Revision 1.29  2006/04/11 15:58:59  fplanque
 * made validate_url() more laxist because there's always a legitimate use for a funky char in a query string
 * (might need to be even more laxist...) but I'd like to make sure people don't type in just anything
 *
 * Revision 1.28  2006/04/10 09:41:14  blueyed
 * validate_url: todo; allow "%" in general
 *
 * Revision 1.27  2006/04/06 18:02:07  blueyed
 * Fixed get_base_domain() for links with protocol != "http" (esp. https)
 *
 * Revision 1.26  2006/04/05 19:16:35  blueyed
 * Refactored/cleaned up help link handling: defaults to online-manual-pages now.
 *
 * Revision 1.25  2006/03/26 20:25:39  blueyed
 * is_regexp: allow check with modifiers, which the Filelist now uses internally
 *
 * Revision 1.24  2006/03/25 00:02:00  blueyed
 * Do not use reqhostpath, but reqhost and reqpath
 *
 * Revision 1.23  2006/03/24 19:40:49  blueyed
 * Only use absolute URLs if necessary because of used <base/> tag. Added base_tag()/skin_base_tag(); deprecated skinbase()
 *
 * Revision 1.22  2006/03/19 16:56:04  blueyed
 * Better defaults for header_redirect()
 *
 * Revision 1.21  2006/03/19 00:08:21  blueyed
 * Default to $notify_from for send_mail()
 *
 * Revision 1.20  2006/03/17 21:28:40  fplanque
 * no message
 *
 * Revision 1.19  2006/03/17 18:49:00  blueyed
 * Log hits to the backoffice always as referer_type "blacklist"
 *
 * Revision 1.18  2006/03/17 17:36:27  blueyed
 * Fixed debug_info() anchors one more time; general review
 *
 * Revision 1.17  2006/03/17 00:07:51  blueyed
 * Fixes for blog-siteurl support
 *
 * Revision 1.16  2006/03/15 19:31:27  blueyed
 * whitespace
 *
 * Revision 1.14  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.13  2006/03/12 17:28:53  blueyed
 * charset cleanup
 *
 * Revision 1.9  2006/03/09 20:40:40  fplanque
 * cleanup
 *
 * Revision 1.8  2006/03/09 15:17:47  fplanque
 * cleaned up get_img() which was one of these insane 'kabelsalat'
 *
 * Revision 1.7  2006/03/06 20:03:40  fplanque
 * comments
 *
 * Revision 1.6  2006/03/06 11:01:55  blueyed
 * doc
 *
 * Revision 1.5  2006/02/28 20:52:54  blueyed
 * fix
 *
 * Revision 1.4  2006/02/27 20:55:50  blueyed
 * JS help links fixed
 *
 * Revision 1.1  2006/02/23 21:12:18  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.183  2006/02/14 20:11:38  blueyed
 * added implode_with_and()
 *
 * Revision 1.182  2006/02/13 15:40:37  blueyed
 * param(): use $GLOBALS instead of $$var again, but this time with a good reason.
 *
 * Revision 1.180  2006/02/06 20:05:30  fplanque
 * minor
 *
 * Revision 1.179  2006/02/05 19:04:48  blueyed
 * doc fixes
 *
 * Revision 1.178  2006/02/05 01:58:40  blueyed
 * is_email() re-added pattern delimiter..
 *
 * Revision 1.177  2006/02/03 21:58:05  fplanque
 * Too many merges, too little time. I can hardly keep up. I'll try to check/debug/fine tune next week...
 *
 * Revision 1.173  2006/01/25 19:19:17  blueyed
 * Fixes for blogurl handling. Thanks to BenFranske for pointing out the biggest issue (http://forums.b2evolution.net/viewtopic.php?t=6844)
 *
 * Revision 1.172  2006/01/22 14:25:05  blueyed
 * debug_info(): enhanced, small fix
 *
 * Revision 1.171  2006/01/22 14:23:47  blueyed
 * Added is_admin_page()
 *
 * Revision 1.170  2006/01/20 00:04:21  blueyed
 * debug_die(): $include_backtrace param
 *
 * Revision 1.169  2006/01/15 18:36:26  blueyed
 * Just another fix to validate_url()
 *
 * Revision 1.168  2006/01/15 17:40:55  blueyed
 * Moved Form::get_field_params_as_string() to function get_field_attribs_as_string() and minor fixes.
 *
 * Revision 1.167  2006/01/11 23:39:19  blueyed
 * Enhanced backtrace-debugging for queries
 *
 * Revision 1.166  2006/01/04 15:02:10  fplanque
 * better filtering design
 */
?>