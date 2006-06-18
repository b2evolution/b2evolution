<?php
/**
 * This file implements general purpose functions.
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
 *
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * @todo Refactor into smaller chunks/files. We should avoid using a "huge" misc early!
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author cafelog (team)
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author jeffbearer: Jeff BEARER.
 * @author sakichan: Nobuo SAKIYAMA.
 * @author vegarg: Vegar BERG GULDAL.
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Dependencies
 */
global $model_path;
require_once $model_path.'antispam/_antispam.funcs.php';
require_once $model_path.'files/_file.funcs.php';


/***** Formatting functions *****/

/**
 * Format a string/content for being output
 *
 * @author fplanque
 * @param string raw text
 * @param string format, can be one of the following
 * - raw: do nothing
 * - htmlbody: display in HTML page body: allow full HTML
 * - entityencoded: Special mode for RSS 0.92: allow full HTML but escape it
 * - htmlhead: strips out HTML (mainly for use in Title)
 * - htmlattr: use as an attribute: escapes quotes, strip tags
 * - formvalue: use as a form value: escapes quotes and < > but leaves code alone
 * - xml: use in an XML file: strip HTML tags
 * - xmlattr: use as an attribute: strips tags and escapes quotes
 * @return string formatted text
 */
function format_to_output( $content, $format = 'htmlbody' )
{
	global $Plugins;

	switch( $format )
	{
		case 'raw':
			// do nothing!
			break;

		case 'htmlbody':
			// display in HTML page body: allow full HTML
			$content = convert_chars($content, 'html');
			break;

		case 'urlencoded':
			// Encode string to be passed as part of an URL
			$content = rawurlencode( $content );
			break;

		case 'entityencoded':
			// Special mode for RSS 0.92: apply renders and allow full HTML but escape it
			$content = convert_chars($content, 'html');
			$content = htmlspecialchars( $content );
			break;

		case 'htmlhead':
			// strips out HTML (mainly for use in Title)
			$content = strip_tags($content);
			$content = convert_chars($content, 'html');
			break;

		case 'htmlattr':
			// use as an attribute: strips tags and escapes quotes
			$content = strip_tags($content);
			$content = convert_chars($content, 'html');
			$content = str_replace('"', '&quot;', $content );
			$content = str_replace("'", '&#039;', $content );
			break;

		case 'formvalue':
			// use as a form value: escapes &, quotes and < > but leaves code alone
			$content = htmlspecialchars( $content );           // Handles &, ", < and >
			$content = str_replace("'", '&#039;', $content );  // Handles '
			break;

		case 'xml':
			// use in an XML file: strip HTML tags
			$content = strip_tags($content);
			$content = convert_chars($content, 'xml');
			break;

		case 'xmlattr':
			// use as an attribute: strips tags and escapes quotes
			$content = strip_tags($content);
			$content = convert_chars($content, 'xml');
			$content = str_replace('"', '&quot;', $content );
			$content = str_replace("'", '&#039;', $content );
			break;

		default:
			debug_die( 'Output format ['.$format.'] not supported.' );
	}

	return $content;
}


/**
 * Format raw HTML input to cleaned up and validated HTML.
 *
 * @param string The content to format
 * @param integer Create automated <br /> tags? (Deprecated??!)
 * @param integer Is this a comment? (Used for balanceTags(), SafeHtmlChecker()'s URI scheme, styling restrictions)
 * @param string Encoding (used for SafeHtmlChecker() only!); defaults to $io_charset
 * @return string
 */
function format_to_post( $content, $autobr = 0, $is_comment = 0, $encoding = NULL )
{
	global $use_balanceTags, $use_html_checker, $use_security_checker;
	global $allowed_tags, $allowed_attributes, $uri_attrs, $allowed_uri_scheme;
	global $comments_allowed_tags, $comments_allowed_attributes, $comments_allowed_uri_scheme;
	global $io_charset;

	// Replace any & that is not a character or entity reference with &amp;
	$content = preg_replace( '/&(?!#[0-9]+;|#x[0-9a-fA-F]+;|[a-zA-Z_:][a-zA-Z0-9._:-]*;)/', '&amp;', $content );

	if( $autobr )
	{ // Auto <br />:
		// may put brs in the middle of multiline tags...
		$content = autobrize($content);
	}

	if( $use_balanceTags )
	{ // Auto close open tags:
		$content = balanceTags($content, $is_comment);
	}

	if( $use_html_checker )
	{ // Check the code:
		if( empty($encoding) )
		{
			$encoding = $io_charset;
		}
		if( ! $is_comment )
		{
			$checker = & new SafeHtmlChecker( $allowed_tags, $allowed_attributes,
					$uri_attrs, $allowed_uri_scheme, $encoding );
		}
		else
		{
			$checker = & new SafeHtmlChecker( $comments_allowed_tags, $comments_allowed_attributes,
					$uri_attrs, $comments_allowed_uri_scheme, $encoding );
		}

		$checker->check( $content ); // TODO: see if we need to use convert_chars( $content, 'html' )
	}

	if( !isset( $use_security_checker ) ) $use_security_checker = 1;
	if( $use_security_checker )
	{
		// Security checking:
		$check = $content;
		// Open comments or '<![CDATA[' are dangerous
		$check = str_replace('<!', '<', $check);
		// # # are delimiters
		// i modifier at the end means caseless
		$matches = array();
		// onclick= etc...
		if( preg_match ('#\s(on[a-z]+)\s*=#i', $check, $matches)
			// action=, background=, cite=, classid=, codebase=, data=, href=, longdesc=, profile=, src=
			// usemap=
			|| preg_match ('#=["\'\s]*(javascript|vbscript|about):#i', $check, $matches)
			|| preg_match ('#\<\/?\s*(frame|iframe|applet|object)#i', $check, $matches) )
		{
			$Messages->add( T_('Illegal markup found: ').htmlspecialchars($matches[1]), 'error' );
		}
		// Styling restictions:
		$matches = array();
		if( $is_comment && preg_match ('#\s(style|class|id)\s*=#i', $check, $matches) )
		{
			$Messages->add( T_('Unallowed CSS markup found: ').htmlspecialchars($matches[1]), 'error' );
		}
	}
	return($content);
}


/*
 * autobrize(-)
 */
function autobrize($content) {
	$content = preg_replace("/<br>\n/", "\n", $content);
	$content = preg_replace("/<br \/>\n/", "\n", $content);
	$content = preg_replace("/(\015\012)|(\015)|(\012)/", "<br />\n", $content);
	return($content);
	}

/*
 * unautobrize(-)
 */
function unautobrize($content)
{
	$content = preg_replace("/<br>\n/", "\n", $content);   //for PHP versions before 4.0.5
	$content = preg_replace("/<br \/>\n/", "\n", $content);
	return($content);
}

/*
 * zeroise(-)
 */
function zeroise($number, $threshold)
{ // function to add leading zeros when necessary
	$l = strlen($number);
	if ($l < $threshold)
		for ($i = 0; $i < ($threshold - $l); $i = $i + 1) { $number='0'.$number;	}
	return($number);
}


/*
 * Convert all non ASCII chars (except if UTF-8 or GB2312) to &#nnnn; unicode references.
 * Also convert entities to &#nnnn; unicode references if output is not HTML (eg XML)
 *
 * Preserves < > and quotes.
 *
 * fplanque: simplified
 * sakichan: pregs instead of loop
 */
function convert_chars( $content, $flag='html' )
{
	global $b2_htmltrans, $b2_htmltranswinuni, $evo_charset;

	// Convert highbyte non ASCII/UTF-8 chars to urefs:
	if( $evo_charset != 'utf-8' && $evo_charset != 'gb2312' )
	{ // This is a single byte charset
		$content = preg_replace_callback(
			'/[\x80-\xff]/',
			create_function( '$j', 'return "&#".ord($j[0]).";";' ),
			$content);
	}

	// Convert Windows CP1252 => Unicode (valid HTML)
	// TODO: should this go to input conversions instead (?)
	$content = strtr( $content, $b2_htmltranswinuni );

	if( $flag == 'html' )
	{ // we can use entities
		// Convert & chars that are not used in an entity
		$content = preg_replace('/&(?![#A-Za-z0-9]{2,20};)/', '&amp;', $content);
	}
	else
	{ // unicode, xml...
		// Convert & chars that are not used in an entity
		$content = preg_replace('/&(?![#A-Za-z0-9]{2,20};)/', '&#38;', $content);

		// Convert HTML entities to urefs:
		$content = strtr($content, $b2_htmltrans);
	}

	return( $content );
}


/**
 * Split $text into blocks by using $pattern and call $callback on the non-matching blocks.
 *
 * The non-matching block's text is the first param to $callback and additionally $params gets passed.
 *
 * This gets used to make links clickable or replace smilies.
 *
 * E.g., to replace only in non-HTML tags, call it like:
 * <code>callback_on_non_matching_blocks( $text, '~<[^>]*>~s', 'your_callback' );</code>
 *
 * {@internal This function gets tested in misc.funcs.simpletest.php.}}
 *
 * @param string Text to handle
 * @param string Regular expression pattern that defines blocks to exclude.
 * @param callback Function name or object/method array to use as callback.
 *               Each non-matching block gets passed as first param, additional params may be
 *               passed with $params.
 * @param array Of additional ("static") params to $callback.
 * @return string
 */
function callback_on_non_matching_blocks( $text, $pattern, $callback, $params = array() )
{
	if( preg_match_all( $pattern, $text, $matches, PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER ) )
	{ // $pattern matches, call the callback method on each non-matching block
		$pos = 0;
		$new_r = '';

		foreach( $matches[0] as $l_matching )
		{
			$pos_match = $l_matching[1];
			$non_match = substr( $text, $pos, ($pos_match - $pos) );

			// Callback:
			$callback_params = $params;
			array_unshift( $callback_params, $non_match );
			$new_r .= call_user_func_array( $callback, $callback_params );

			$new_r .= $l_matching[0];
			$pos += strlen($non_match)+strlen($l_matching[0]);
		}

		// Callback:
		$callback_params = $params;
		array_unshift( $callback_params, substr( $text, $pos ) );
		#pre_dump( $matches, $callback_params );
		$new_r .= call_user_func_array( $callback, $callback_params );

		return $new_r;
	}

	$callback_params = $params;
	array_unshift( $callback_params, $text );
	return call_user_func_array( $callback, $callback_params );
}


/**
 * Make links clickable in a given text.
 *
 * It replaces only text which is not between <a> tags already.
 *
 * @uses callback_on_non_matching_blocks()
 *
 * {@internal This function gets tested in misc.funcs.simpletest.php.}}
 *
 * @return string
 */
function make_clickable( $text, $moredelim = '&amp;' )
{
	$text = callback_on_non_matching_blocks( $text, '~<a[^>]*("[^"]"|\'[^\']\')?[^>]*>.*?</a>~is', 'make_clickable_callback', array( $moredelim ) );

	return $text;
}


/**
 * Callback function for {@link make_clickable()}.
 *
 * @todo IMHO it would be better to use "\b" (word boundary) to match the beginning of links..
 *
 * original function: phpBB, extended here for AIM & ICQ
 * fplanque restricted :// to http:// and mailto://
 * Fixed to not include trailing dot and comma.
 *
 * @return string The clickable text.
 */
function make_clickable_callback( & $text, $moredelim = '&amp;' )
{
	$pattern_domain = '([a-z0-9\-]+\.[a-z0-9\-.\~]+)'; // a domain name (not very strict)
	$text = preg_replace(
		array( '#(^|[\s>])(https?|mailto)://([^<>{}\s]+[^.,<>{}\s])#i',
			'#(^|[\s>])aim:([^,<\s]+)#i',
			'#(^|[\s>])icq:(\d+)#i',
			'#(^|[\s>])www\.'.$pattern_domain.'((?:/[^<\s]*)?[^.,\s])#i',
			'#(^|[\s>])([a-z0-9\-_.]+?)@'.$pattern_domain.'([^.,<\s]+)#i', ),
		array( '$1<a href="$2://$3">$2://$3</a>',
			'$1<a href="aim:goim?screenname=$2$3'.$moredelim.'message='.rawurlencode(T_('Hello')).'">$2$3</a>',
			'$1<a href="http://wwp.icq.com/scripts/search.dll?to=$2">$2</a>',
			'$1<a href="http://www.$2$3$4">www.$2$3$4</a>',
			'$1<a href="mailto:$2@$3$4">$2@$3$4</a>', ),
		$text );

	return $text;
}


/***** // Formatting functions *****/


function date2mysql( $ts )
{
	return date( 'Y-m-d H:i:s', $ts );
}

/**
 * Convert a MYSQL date to a UNIX timestamp
 */
function mysql2timestamp( $m )
{
	return mktime(substr($m,11,2),substr($m,14,2),substr($m,17,2),substr($m,5,2),substr($m,8,2),substr($m,0,4));
}

/**
 * Convert a MYSQL date -- WITHOUT the time -- to a UNIX timestamp
 */
function mysql2datestamp( $m )
{
	return mktime( 0, 0, 0, substr($m,5,2), substr($m,8,2), substr($m,0,4) );
}

/**
 * Format a MYSQL date to current locale date format.
 *
 * @param string MYSQL date YYYY-MM-DD HH:MM:SS
 */
function mysql2localedate( $mysqlstring )
{
	return mysql2date( locale_datefmt(), $mysqlstring );
}

function mysql2localedatetime( $mysqlstring )
{
	return mysql2date( locale_datefmt().' '.locale_timefmt(), $mysqlstring );
}


/**
 * Format a MYSQL date.
 *
 * @param string enhanced format string
 * @param string MYSQL date YYYY-MM-DD HH:MM:SS
 * @param boolean true to use GM time
 */
function mysql2date( $dateformatstring, $mysqlstring, $useGM = false )
{
	$m = $mysqlstring;
	if( empty($m) || ($m == '0000-00-00 00:00:00' ))
		return false;

	// Get a timestamp:
	$unixtimestamp = mysql2timestamp( $m );

	return date_i18n( $dateformatstring, $unixtimestamp, $useGM );
}


/**
 * Date internationalization: same as date() formatting but with i18n support
 *
 * @param string enhanced format string
 * @param integer UNIX timestamp
 * @param boolean true to use GM time
 */
function date_i18n( $dateformatstring, $unixtimestamp, $useGM = false )
{
	global $month, $month_abbrev, $weekday, $weekday_abbrev, $weekday_letter;
	global $Settings, $localtimenow;

	if( $dateformatstring == 'isoZ' )
	{ // full ISO 8601 format
		$dateformatstring = 'Y-m-d\TH:i:s\Z';
	}

	if( $useGM )
	{ // We want a Greenwich Meridian time:
		$r = gmdate($dateformatstring, $unixtimestamp - ($Settings->get('time_difference') * 3600));
	}
	else
	{ // We want default timezone time:

		/*
		Special symbols:
			'b': wether it's today (1) or not (0)
			'l': weekday
			'D': weekday abbrev
			'e': weekday letter
			'F': month
			'M': month abbrev
		*/

		#echo $dateformatstring, '<br />';

		// protect special symbols, that date() would need proper locale set for
		$protected_dateformatstring = preg_replace( '/(?<!\\\)([blDeFM])/', '@@@\\\$1@@@', $dateformatstring );

		#echo $protected_dateformatstring, '<br />';

		$r = date( $protected_dateformatstring, $unixtimestamp );

		if( $protected_dateformatstring != $dateformatstring )
		{ // we had special symbols, replace them

			$istoday = ( date('Ymd',$unixtimestamp) == date('Ymd',$localtimenow) ) ? '1' : '0';
			$datemonth = date('m', $unixtimestamp);
			$dateweekday = date('w', $unixtimestamp);

			// replace special symbols
			$r = str_replace( array(
						'@@@b@@@',
						'@@@l@@@',
						'@@@D@@@',
						'@@@e@@@',
						'@@@F@@@',
						'@@@M@@@',
						),
					array(  $istoday,
						T_($weekday[$dateweekday]),
						T_($weekday_abbrev[$dateweekday]),
						T_($weekday_letter[$dateweekday]),
						T_($month[$datemonth]),
						T_($month_abbrev[$datemonth]) ),
					$r );
		}
	}

	return $r;
}


/**
 * Format dates into a string in a way similar to sprintf()
 */
function date_sprintf( $string, $timestamp )
{
	global $date_sprintf_timestamp;
	$date_sprintf_timestamp = $timestamp;

	return preg_replace_callback( '/%\{(.*?)\}/', 'date_sprintf_callback', $string );
}
function date_sprintf_callback( $matches )
{
	global $date_sprintf_timestamp;

	return date_i18n( $matches[1], $date_sprintf_timestamp );
}



/**
 *
 * @param integer year
 * @param integer month (0-53)
 * @param integer 0 for sunday, 1 for monday
 */
function get_start_date_for_week( $year, $week, $startofweek )
{
	$new_years_date = mktime( 0, 0, 0, 1, 1, $year );
	$weekday = date('w', $new_years_date);
	// echo '<br> 1st day is a: '.$weekday;

	// How many days until start of week:
	$days_to_new_week = (7 - $weekday + $startofweek) % 7;
	// echo '<br> days to new week: '.$days_to_new_week;

	// We now add the required number of days to find the 1st sunday/monday in the year:
	//$first_week_start_date = $new_years_date + $days_to_new_week * 86400;
	//echo '<br> 1st week starts on '.date( 'Y-m-d H:i:s', $first_week_start_date );

	// We add the number of requested weeks:
	// This will fail when passing to Daylight Savings Time: $date = $first_week_start_date + (($week-1) * 604800);
	$date = mktime( 0, 0, 0, 1, $days_to_new_week + 1 + ($week-1) * 7, $year );
	// echo '<br> week '.$week.' starts on '.date( 'Y-m-d H:i:s', $date );

	return $date;
}



/**
 * Get start and end day of a week, based on week number and start-of-week
 *
 * Used by Calendar
 *
 * fp>> I'd really like someone to comment the magic of that thing...
 *
 * @param date
 * @param integer 0 for Sunday, 1 for Monday
 */
function get_weekstartend( $date, $startOfWeek )
{
	$weekday = date('w', $date);
	$i = 86400;
	while( $weekday <> $startOfWeek )
	{
		$weekday = date('w', $date);
		$date = $date - 86400;
		$i = 0;
	}
	$week['start'] = $date + 86400 - $i;
	$week['end']   = $date + 604800; // 691199;

	// pre_dump( 'weekstartend: ', date( 'Y-m-d', $week['start'] ), date( 'Y-m-d', $week['end'] ) );

	return( $week );
}


/**
 * Check that email address looks valid.
 *
 * @param string email address to check
 * @param string Format to use ('simple', 'rfc')
 *    'simple':
 *      Single email address.
 *    'rfc':
 *      Full email address, may include name (RFC2822)
 *      - example@example.org
 *      - Me <example@example.org>
 *      - "Me" <example@example.org>
 * @param boolean Return the match or boolean
 *
 * @return bool|array Either true/false or the match (see {@link $return_match})
 */
function is_email( $email, $format = 'simple', $return_match = false )
{
	#$chars = "/^([a-z0-9_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,4}\$/i";

	switch( $format )
	{
		case 'rfc':
		case 'rfc2822':
			/**
			 * Regexp pattern converted from: http://www.regexlib.com/REDetails.aspx?regexp_id=711
			 * Extended to allow escaped quotes.
			 */
			$pattern_email = '/^
				(
					(?>[a-zA-Z\d!\#$%&\'*+\-\/=?^_`{|}~]+\x20*
						|"( \\\" | (?=[\x01-\x7f])[^"\\\] | \\[\x01-\x7f] )*"\x20*)* # Name
					(<)
				)?
				(
					(?!\.)(?>\.?[a-zA-Z\d!\#$%&\'*+\-\/=?^_`{|}~]+)+
					|"( \\\" | (?=[\x01-\x7f])[^"\\\] | \\[\x01-\x7f] )* " # quoted mailbox name
				)
				@
				(
					((?!-)[a-zA-Z\d\-]+(?<!-)\.)+[a-zA-Z]{2,}
					|
					\[(
						( (?(?<!\[)\.)(25[0-5] | 2[0-4]\d | [01]?\d?\d) ){4}
						|
						[a-zA-Z\d\-]*[a-zA-Z\d]:( (?=[\x01-\x7f])[^\\\[\]] | \\[\x01-\x7f] )+
					)\]
				)
				(?(3)>) # match ">" if it was there
				$/x';
			break;

		case 'simple':
		default:
			$pattern_email = '/^\S+@[^\.\s]\S*\.[a-z]{2,}$/i';
			break;
	}

	if( strpos( $email, '@' ) !== false && strpos( $email, '.' ) !== false )
	{
		if( $return_match )
		{
			preg_match( $pattern_email, $email, $match );
			return $match;
		}
		else
		{
			return (bool)preg_match( $pattern_email, $email );
		}
	}
	else
	{
		return $return_match ? array() : false;
	}
}


/**
 * Are we running on a Windows server?
 */
function is_windows()
{
	// gotta love microdoft
	return ( isset( $_SERVER['WINDIR'] ) || isset( $_SERVER['windir'] ) );
}


function xmlrpc_getposttitle($content)
{
	global $post_default_title;
	if (preg_match('/<title>(.+?)<\/title>/is', $content, $matchtitle))
	{
		$post_title = $matchtitle[1];
	}
	else
	{
		$post_title = $post_default_title;
	}
	return($post_title);
}


/**
 * Also used by post by mail
 */
function xmlrpc_getpostcategory($content)
{
	if (preg_match('/<category>([0-9]+?)<\/category>/is', $content, $matchcat))
	{
		return $matchcat[1];
	}

	return false;
}


/*
 * xmlrpc_removepostdata(-)
 */
function xmlrpc_removepostdata($content)
{
	$content = preg_replace('/<title>(.*?)<\/title>/si', '', $content);
	$content = preg_replace('/<category>(.*?)<\/category>/si', '', $content);
	$content = trim($content);
	return($content);
}


/**
 * Echo the XML-RPC call Result and optionally log into file
 *
 * @param object XMLRPC response object
 * @param boolean true to echo
 * @param mixed File resource or == '' for no file logging.
 */
function xmlrpc_displayresult( $result, $display = true, $log = '' )
{
	if( ! $result )
	{ // We got no response:
		if( $display ) echo T_('No response!')."<br />\n";
		return false;
	}

	if( $result->faultCode() )
	{ // We got a remote error:
		if( $display ) echo T_('Remote error'), ': ', $result->faultString(), ' (', $result->faultCode(), ")<br />\n";
		debug_fwrite($log, $result->faultCode().' -- '.$result->faultString());
		return false;
	}

	// We'll display the response:
	$val = $result->value();
	$value = xmlrpc_decode_recurse($result->value());

	if( is_array($value) )
	{
		$out = '';
		foreach($value as $l_value)
		{
			$out .= ' ['.$l_value.'] ';
		}
	}
	else
	{
		$out = $value;
	}

	debug_fwrite($log, $out);

	if( $display ) echo T_('Response').': '.$out."<br />\n";

	return true;
}


/**
 * Log the XML-RPC call Result into LOG object
 *
 * @param object XMLRPC response object
 * @param Log object to add messages to
 */
function xmlrpc_logresult( $result, & $message_Log )
{
	if( ! $result )
	{ // We got no response:
		$message_Log->add( T_('No response!'), 'error' );
		return false;
	}

	if( $result->faultCode() )
	{ // We got a remote error:
		$message_Log->add( T_('Remote error').': '.$result->faultString().' ('.$result->faultCode().')', 'error' );
		return false;
	}

	// We got a response:
	$val = $result->value();
	$value = xmlrpc_decode_recurse($result->value());

	if( is_array($value) )
	{
		$out = '';
		foreach($value as $l_value)
		{
			$out .= ' ['.$l_value.'] ';
		}
	}
	else
	{
		$out = $value;
	}

	$message_Log->add( T_('Response').': '.$out, 'success' );

	return true;
}



function debug_fopen($filename, $mode) {
	global $debug;
	if ($debug == 1 && ( !empty($filename) ) )
	{
		$fp = fopen($filename, $mode);
		return $fp;
	} else {
		return false;
	}
}

function debug_fwrite($fp, $string)
{
	global $debug;
	if( $debug && $fp )
	{
		fwrite($fp, $string);
	}
}

function debug_fclose($fp)
{
	global $debug;
	if( $debug && $fp )
	{
		fclose($fp);
	}
}



/**
 balanceTags

 Balances Tags of string using a modified stack.

 @param string    Text to be balanced
 @return string   Returns balanced text
 @author          Leonard Lin (leonard@acm.org)
 @version         v1.1
 @date            November 4, 2001
 @license         GPL v2.0
 @notes
 @changelog
             1.2  ***TODO*** Make better - change loop condition to $text
             1.1  Fixed handling of append/stack pop order of end text
                  Added Cleaning Hooks
             1.0  First Version
*/
function balanceTags($text)
{
	$tagstack = array();
	$stacksize = 0;
	$tagqueue = '';
	$newtext = '';

	# b2 bug fix for comments - in case you REALLY meant to type '< !--'
	$text = str_replace('< !--', '<    !--', $text);

	# b2 bug fix for LOVE <3 (and other situations with '<' before a number)
	$text = preg_replace('#<([0-9]{1})#', '&lt;$1', $text);

	while( preg_match("/<(\/?\w*)\s*([^>]*)>/", $text, $regex) )
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
			if($stacksize <= 0) {
				$tag = '';
				//or close to be safe $tag = '/' . $tag;
			}
			// if stacktop value = tag close value then pop
			else if ($tagstack[$stacksize - 1] == $tag) { // found closing tag
				$tag = '</' . $tag . '>'; // Close Tag
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

			// Push if not img or br or hr
			if($tag != 'br' && $tag != 'img' && $tag != 'hr') {
				$stacksize = array_push ($tagstack, $tag);
			}

			// Attributes
			// $attributes = $regex[2];
			$attributes = $regex[2];
			if($attributes) {
				$attributes = ' '.$attributes;
			}

			$tag = '<'.$tag.$attributes.'>';
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
	$newtext = str_replace( '< !--', '<'.'!--', $newtext ); // the concatenation is needed to work around some strange parse error in PHP 4.3.1
	$newtext = str_replace( '<    !--', '< !--', $newtext );

	return $newtext;
}


/**
 * Clean up the mess PHP has created with its funky quoting everything!
 *
 * @param mixed string or array (function is recursive)
 */
function remove_magic_quotes( $mixed )
{
	if( get_magic_quotes_gpc() )
	{ // That stupid PHP behaviour consisting of adding slashes everywhere is unfortunately on
		if( is_array( $mixed ) )
		{
			foreach($mixed as $k => $v)
			{
				$mixed[$k] = remove_magic_quotes( $v );
			}
		}
		else
		{
			// echo 'Removing slashes ';
			$mixed = stripslashes( $mixed );
		}
	}
	return $mixed;
}


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
 * - float
 * - string (strips (HTML-)Tags, trims whitespace)
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
function param( $var, $type = '', $default = '', $memorize = false,
								$override = false, $forceset = true )
{
	global $global_param_list, $Debuglog, $debug, $evo_charset, $io_charset;
	// NOTE: we use $GLOBALS[$var] instead of $$var, because otherwise it would conflict with param names which are used as function params ("var", "type", "default", ..)!

	// Check if already set
	// WARNING: when PHP register globals is ON, COOKIES get priority over GET and POST with this!!!
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
			debug_die( sprintf( T_('Parameter &laquo;%s&raquo; is required!'), $var ) );
		}
		elseif( $forceset )
		{
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

	if( isset($io_charset) && $io_charset != $evo_charset )
	{ // the INPUT/OUTPUT charset differs from the internal one (this also means mb_convert_encoding is available)
		mb_convert_variables( $evo_charset, $io_charset, $GLOBALS[$var] );
	}

	// type will be forced even if it was set before and not overriden
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
				// echo $var, '=', $GLOBALS[$var], '<br />';
				$GLOBALS[$var] = trim( strip_tags($GLOBALS[$var]) );
				$Debuglog->add( 'param(-): <strong>'.$var.'</strong> as string', 'params' );
				break;

			default:
				if( substr( $type, 0, 1 ) == '/' )
				{	// We want to match against a regexp:
					if( preg_match( $type, $GLOBALS[$var] ) )
					{	// Okay, match
						$Debuglog->add( 'param(-): <strong>'.$var.'</strong> matched against '.$type, 'params' );
					}
					else
					{
						$GLOBALS[$var] = $default;
						$Debuglog->add( 'param(-): <strong>'.$var.'</strong> DID NOT match '.$type.' set to default value='.$GLOBALS[$var], 'params' );
					}
					// From now on, consider this as a string: (we need this when memorizing)
					$type = 'string';
				}
				elseif( $GLOBALS[$var] === '' )
				{
					// fplanque> note: there might be side effects to this, but we need
					// this to distinguish between 0 and 'no input'
					// Note: we do this after regexps because we may or may not want to allow empty string sin regexps
					$GLOBALS[$var] = NULL;
					$Debuglog->add( 'param(-): <strong>'.$var.'</strong> set to NULL', 'params' );
				}
				else
				{
					settype( $GLOBALS[$var], $type );
					$Debuglog->add( 'param(-): <strong>'.$var.'</strong> typed to '.$type.', new value='.$GLOBALS[$var], 'params' );
				}
		}
	}

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
 * Regenerate current URL from parameters
 * This may clean it up
 * But it is also useful when generating static pages: you cannot rely on $_REQUEST[]
 *
 * @param mixed string (delimited by commas) or array of params to ignore (can be regexps in /.../)
 * @param mixed string or array of param(s) to set
 * @param mixed string Alternative URL we want to point to if not the current URL (may be absolute if BASE tag gets used)
 */
function regenerate_url( $ignore = '', $set = '', $pagefileurl = '' )
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
		$url = url_add_param( $url, implode( '&amp;', $params ) );
	}
	// $Debuglog->add( 'regenerate_url(): ['.$url.']', 'params' );
	return $url;
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
 * get_path(-)
 */
function get_path( $which = '' )
{
	global $core_subdir, $skins_subdir, $basepath;

	switch( $which )
	{
		case 'skins':
			return $basepath.$skins_subdir;
	}

	return $basepath;
}


/*
 * autoquote(-)
 */
function autoquote( & $string )
{
	if( strpos( $string, "'" ) !== 0 )
	{ // no quote at position 0
		$string = "'".$string."'";
	}
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
 * Wrap pre tag around var_dump() for better debugging
 *
 * @param, ... mixed variable(s) to dump
 */
function pre_dump( $var__var__var__var__ )
{
	#echo 'pre_dump(): '.debug_get_backtrace();
	echo '<pre style="padding:1ex;border:1px solid #00f;">';
	foreach( func_get_args() as $lvar )
	{
		echo htmlspecialchars( var_export( $lvar, true ) ).'<br />';
	}
	echo '</pre>';
}


/**
 * Get a function trace from {@link debug_backtrace()} as html table.
 *
 * Adopted from {@link http://us2.php.net/manual/de/function.debug-backtrace.php#47644}.
 *
 * @param integer|NULL Get the last x entries from the stack (after $ignore_from is applied). Anything non-numeric means "all".
 * @param array After a key/value pair matches a stack entry, this and the rest is ignored.
 *              For example, array('class' => 'DB') would exclude everything after the stack
 *              "enters" class DB and everything that got called afterwards.
 *              You can also give an array of arrays which means that every condition in one of the given array must match.
 * @param integer Number of stack entries to include, after $ignore_from matches.
 * @return string HTML table
 */
function debug_get_backtrace( $limit_to_last = NULL, $ignore_from = array( 'function' => 'debug_get_backtrace' ), $offset_ignore_from = 0 )
{
	$r = '';

	if( function_exists( 'debug_backtrace' ) ) // PHP 4.3.0
	{
		$backtrace = debug_backtrace();
		$count_ignored = 0; // remember how many have been ignored
		$limited = false;   // remember if we have limited to $limit_to_last

		if( $ignore_from )
		{	// we want to ignore from a certain point
			$trace_length = 0;
			$break_because_of_offset = false;

			for( $i = count($backtrace); $i > 0; $i-- )
			{	// Search the backtrace from behind (first call).
				$l_stack = & $backtrace[$i-1];

				if( $break_because_of_offset && $offset_ignore_from < 1 )
				{ // we've respected the offset, but need to break now
					break; // ignore from here
				}

				foreach( $ignore_from as $l_ignore_key => $l_ignore_value )
				{	// Check if we want to ignore from here
					if( is_array($l_ignore_value) )
					{	// It's an array - all must match
						foreach( $l_ignore_value as $l_ignore_mult_key => $l_ignore_mult_val )
						{
							if( !isset($l_stack[$l_ignore_mult_key]) /* not set with this stack entry */
								|| strcasecmp($l_stack[$l_ignore_mult_key], $l_ignore_mult_val) /* not this value (case-insensitive) */ )
							{
								continue 2; // next ignore setting, because not all match.
							}
						}
						if( $offset_ignore_from-- > 0 )
						{
							$break_because_of_offset = true;
							break;
						}
						break 2; // ignore from here
					}
					elseif( isset($l_stack[$l_ignore_key])
						&& !strcasecmp($l_stack[$l_ignore_key], $l_ignore_value) /* is equal case-insensitive */ )
					{
						if( $offset_ignore_from-- > 0 )
						{
							$break_because_of_offset = true;
							break;
						}
						break 2; // ignore from here
					}
				}
				$trace_length++;
			}

			$count_ignored = count($backtrace) - $trace_length;

			$backtrace = array_slice( $backtrace, 0-$trace_length ); // cut off ignored ones
		}

		$count_backtrace = count($backtrace);
		if( is_numeric($limit_to_last) && $limit_to_last < $count_backtrace )
		{	// we want to limit to a maximum number
			$limited = true;
			$backtrace = array_slice( $backtrace, 0, $limit_to_last );
			$count_backtrace = $limit_to_last;
		}

		$r .= '<div style="padding:1ex; margin-bottom:1ex; text-align:left; color:#000; background-color:#ddf">
						<h3>Backtrace:</h3>'."\n";
		if( $count_backtrace )
		{
			$r .= '<ol style="font-family:monospace;">';

			$i = 0;
			foreach( $backtrace as $l_trace )
			{
				if( ++$i == $count_backtrace )
				{
					$r .= '<li style="padding:0.5ex 0;">';
				}
				else
				{
					$r .= '<li style="padding:0.5ex 0; border-bottom:1px solid #77d;">';
				}
				$args = array();
				if( isset($l_trace['args']) && is_array( $l_trace['args'] ) )
				{	// Prepare args:
					foreach( $l_trace['args'] as $l_arg )
					{
						$l_arg_type = gettype($l_arg);
						switch( $l_arg_type )
						{
							case 'integer':
							case 'double':
								$args[] = $l_arg;
								break;
							case 'string':
								$args[] = '"'.htmlspecialchars(str_replace("\n", '', substr($l_arg, 0, 64))).((strlen($l_arg) > 64) ? '...' : '').'"';
								break;
							case 'array':
								$args[] = 'Array('.count($l_arg).')';
								break;
							case 'object':
								$args[] = 'Object('.get_class($l_arg).')';
								break;
							case 'resource':
								$args[] = 'Resource('.strstr($l_arg, '#').')';
								break;
							case 'boolean':
								$args[] = $l_arg ? 'true' : 'false';
								break;
							default:
								$args[] = $l_arg_type;
						}
					}
				}

				$call = '<strong>';
				if( isset($l_trace['class']) )
				{
					$call .= $l_trace['class'];
				}
				if( isset($l_trace['type']) )
				{
					$call .= $l_trace['type'];
				}
				$call .= $l_trace['function'].'(</strong>';
				if( $args )
				{
					$call .= ' '.implode( ', ', $args ).' ';
				}
				$call .='<strong>)</strong>';

				$r .= $call."<br />\n";

				$r .= '<strong>';
				if( isset($l_trace['file']) )
				{
					$r .= 'File: </strong> '.$l_trace['file'];
				}
				else
				{
					$r .= '[runtime created function]</strong>';
				}
				if( isset($l_trace['line']) )
				{
					$r .= ':'.$l_trace['line'];
				}

				$r .= "</li>\n";
			}
			$r .= '</ol>';
		}
		else
		{
			$r .= '<p>No backtrace available.</p>';
		}

		// Extra notes, might be to much, but explains why we stopped at some point. Feel free to comment it out or remove it.
		$notes = array();
		if( $count_ignored )
		{
			$notes[] = 'Ignored last: '.$count_ignored;
		}
		if( $limited )
		{
			$notes[] = 'Limited to'.( $count_ignored ? ' remaining' : '' ).': '.$limit_to_last;
		}
		if( $notes )
		{
			$r .= '<p class="small">'.implode( ' - ', $notes ).'</p>';
		}

		$r .= "</div>\n";
	}

	return $r;
}


/**
 * Outputs Unexpected Error message. When in debug mode it also prints a backtrace.
 *
 * This should be used instead of die() everywhere.
 * This should NOT be used instead of exit() anywhere.
 * Dying means the application has encontered and unexpected situation,
 * i-e: something that should never occur during normal operation.
 * Examples: database broken, user changed URL by hand...
 *
 * @param string Message to output
 */
function debug_die( $additional_info = '' )
{
	global $debug, $baseurl;

	// Attempt to output an error header (will not work if there is too much content already out):
	// This should help preventing indexing robots from indexing the error :P
	@header('HTTP/1.0 500 Internal Server Error');

	echo '<div style="background-color: #fdd; padding: 1ex; margin-bottom: 1ex;">';
	echo '<h3 style="color:#f00;">'.T_('An unexpected error has occured!').'</h3>';
	echo '<p>'.T_('If this error persits, please report it to the administrator.').'</p>';
	echo '<p><a href="'.$baseurl.'">'.T_('Go back to home page').'</a></p>';
	echo '</div>';

	if( !empty( $additional_info ) )
	{
		echo '<div style="background-color: #ddd; padding: 1ex; margin-bottom: 1ex;">';
		echo '<h3>'.T_('Additional information about this error:').'</h3>';
		echo $additional_info;
		echo '</div>';
	}

	if( $debug )
	{
		echo debug_get_backtrace();
		debug_info();
	}

	// Attempt to keep the html valid (but it doesn't really matter anyway)
	die( '</body></html>' );
}


/**
 * Outputs debug info, according to {@link $debug} or $force param. This gets called typically at the end of the page.
 *
 * @param boolean true to force output
 */
function debug_info( $force = false )
{
	global $debug, $Debuglog, $DB, $obhandler_debug, $Timer, $ReqHost, $ReqPath;
	global $cache_imgsize, $cache_File;

	if( ! $debug && ! $force )
	{ // No debug output:
		return;
	}

	$ReqHostPathQuery = $ReqHost.$ReqPath.( empty( $_SERVER['QUERY_STRING'] ) ? '' : '?'.$_SERVER['QUERY_STRING'] );
	echo '<div class="debug"><h2>Debug info</h2>';

	$Debuglog->add( 'Len of serialized $cache_imgsize: '.strlen(serialize($cache_imgsize)), 'memory' );
	$Debuglog->add( 'Len of serialized $cache_File: '.strlen(serialize($cache_File)), 'memory' );

	if( !$obhandler_debug )
	{ // don't display changing items when we want to test obhandler

		// Timer table:
		$time_page = $Timer->get_duration( 'total' );
		$timer_rows = array();
		foreach( $Timer->get_categories() as $l_cat )
		{
			if( $l_cat == 'sql_query' )
			{
				continue;
			}
			$timer_rows[ $l_cat ] = $Timer->get_duration( $l_cat );
		}
		arsort( $timer_rows );
		echo '<table><thead>'
			.'<tr><th colspan="4" class="center">Timers</th></tr>'
			.'<tr><th>Category</th><th>Time</th><th>%</th><th>Count</th></tr>'
			.'</thead><tbody>';

		$table_rows_ignore_perhaps = array();
		foreach( $timer_rows as $l_cat => $l_time )
		{
			$percent_l_cat = $time_page > 0 ? number_format( 100/$time_page * $l_time, 2 ) : '0';

			$row = "\n<tr>"
				.'<td>'.$l_cat.'</td>'
				.'<td class="right">'.$l_time.'</td>'
				.'<td class="right">'.$percent_l_cat.'%</td>'
				.'<td class="right">'.$Timer->get_count( $l_cat ).'</td></tr>';

			if( $l_time < 0.005 )
			{
				$table_rows_ignore_perhaps[] = $row;
			}
			else
			{
				echo $row;
			}
		}
		$count_ignored = count($table_rows_ignore_perhaps);
		if( $count_ignored > 5 )
		{
			echo '<tr><td colspan="4" class="center"> + '.$count_ignored.' &lt; 0.005s </td></tr>';
		}
		else
		{
			echo implode( "\n", $table_rows_ignore_perhaps );
		}
		echo '</tbody>';
		echo '</table>';

		if( isset($DB) )
		{
			echo '<a href="'.$ReqHostPathQuery.'#evo_debug_queries">Database queries: '.$DB->num_queries.'.</a><br />';
		}

		foreach( array( // note: 8MB is default for memory_limit and is reported as 8388608 bytes
			'memory_get_usage' => array( 'display' => 'Memory usage', 'high' => 8000000 ),
			'xdebug_peak_memory_usage' => array( 'display' => 'Memory peak usage', 'high' => 8000000 ) ) as $l_func => $l_var )
		{
			if( function_exists( $l_func ) )
			{
				$_usage = $l_func();
				if( $_usage > $l_var['high'] ) echo '<span style="color:red; font-weight:bold">';
				echo $l_var['display'].': '.bytesreadable( $_usage );
				if( $_usage > $l_var['high'] ) echo '</span>';
				echo '<br />';
			}
		}
	}


	// DEBUGLOG (with list of categories at top):
	$log_categories = array( 'error', 'note', 'all' ); // Categories to output (in that order)
	$log_cats = array_keys($Debuglog->get_messages( $log_categories )); // the real list (with all replaced and only existing ones)
	$log_container_head = '<h3>Debug messages</h3>';
	$log_head_links = array();
	foreach( $log_cats as $l_cat )
	{
		$log_head_links[] .= '<a href="'.$ReqHostPathQuery.'#debug_info_cat_'.str_replace( ' ', '_', $l_cat ).'">'.$l_cat.'</a>';
	}
	$log_container_head .= implode( ' | ', $log_head_links );
	echo format_to_output(
		$Debuglog->display( array(
				'container' => array( 'string' => $log_container_head, 'template' => false ),
				'all' => array( 'string' => '<h4 id="debug_info_cat_%s">%s:</h4>', 'template' => false ) ),
			'', false, $log_categories ),
		'htmlbody' );


	echo '<h3 id="evo_debug_queries">DB</h3>';

	if( !isset($DB) )
	{
		echo 'No DB object.';
	}
	else
	{
		$DB->dump_queries();
	}
	echo '</div>';
}


/**
 * Output Buffer handler.
 *
 * It will be set in /inc/_main.inc.php and handle the output (if enabled).
 * It generates a md5-ETag, which is checked against the one that may have
 * been sent by the browser, allowing us to just send a "304 Not modified" response.
 *
 * @param string output given by PHP
*/
function obhandler( $output )
{
	global $lastmodified, $use_gzipcompression, $use_etags;
	global $localtimenow;

	// we're sending out by default
	$sendout = true;

	if( !isset( $lastmodified ) )
	{ // default of lastmodified is now
		$lastmodified = $localtimenow;
	}

	if( $use_etags )
	{ // Generating ETAG

		// prefix with PUB (public page) or AUT (private page).
		$ETag = is_logged_in() ? '"AUT' : '"PUB';
		$ETag .= md5( $out ).'"';
		header( 'ETag: '.$ETag );

		// decide to send out or not
		if( isset($_SERVER['HTTP_IF_NONE_MATCH'])
				&& stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) === $ETag )
		{ // client has this page already
			$sendout = false;
		}
	}

	if( !$sendout )
	{  // send 304 and die
		header( 'Content-Length: 0' );
		header( $_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified' );
		#$Hit->log();  // TODO: log this somehow?
		die;
	};


	// Send Last-Modified -----------------
	// We should perhaps make this the central point for this.
	// Also handle Cache-Control and Pragma here (with global vars).

	// header( 'Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', $lastmodified) );


	// GZIP encoding
	if( $use_gzipcompression && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') )
	{ // requested/accepted by browser:
		$out = gzencode($out);
		header( 'Content-Encoding: gzip' );
	}


	header( 'Content-Length: '.strlen($out) );
	return $out;
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
 * Sends a mail, wrapping PHP's mail() function.
 *
 * {@link $current_locale} will be used to set the charset.
 *
 * Note: we use a single \n as line ending, though it does not comply to
 * {@link http://www.faqs.org/rfcs/rfc2822 RFC2822}, but seems to be safer,
 * because some mail transfer agents replace \n by \r\n automatically.
 *
 * @param string Recipient, either email only or in "Name <example@example.com>" format (RFC2822).
 *               Can be multiple comma-separated addresses.
 * @param string Subject of the mail
 * @param string The message text
 * @param string From address, being added to headers (we'll prevent injections);
 *               see {@link http://securephp.damonkohler.com/index.php/Email_Injection}.
 *               Might be just an email address or of the same form as {@link $to}.
 *               {@link $notify_from} gets used as default (if NULL).
 * @param array Additional headers ( headername => value ). Take care of injection!
 * @return boolean True if mail could be sent (not necessarily delivered!), false if not - (return value of {@link mail()})
 */
function send_mail( $to, $subject, $message, $from = NULL, $headers = array() )
{
	global $debug, $app_name, $app_version, $current_locale, $current_charset, $evo_charset, $locales, $Debuglog, $notify_from;

	$NL = "\n";

	if( !is_array( $headers ) )
	{ // Make sure $headers is an array
		$headers = array( $headers );
	}

	// Specify charset and content-type of email
	$headers['Content-Type'] = 'text/plain; charset='.$current_charset;
	$headers['X-Mailer'] = $app_name.' '.$app_version.' - PHP/'.phpversion();
	$headers['X-Remote-Addr'] = implode( ',', get_ip_list() );

	// -- Build headers ----
	if( $from === NULL )
	{
		$from = $notify_from;
	}
	else
	{
		$from = trim($from);
	}

	if( ! empty($from) )
	{ // From has to go into headers
		$from_save = preg_replace( '~(\r|\n).*$~s', '', $from ); // Prevent injection! (remove everything after (and including) \n or \r)

		if( $from != $from_save )
		{
			if( strpos( $from_save, '<' ) !== false && !strpos( $from_save, '>' ) )
			{ // We have probably stripped the '>' at the end!
				$from_save .= '>';
			}

			// Add X-b2evo notification mail header about this
			$headers['X-b2evo'] = 'Fixed email header injection (From)';
			$Debuglog->add( 'Detected email injection! Fixed &laquo;'.htmlspecialchars($from).'&raquo; to &laquo;'.htmlspecialchars($from_save).'&raquo;.', 'security' );

			$from = $from_save;
		}

		$headers['From'] = $from;
	}

	$headerstring = '';
	reset( $headers );
	while( list( $lKey, $lValue ) = each( $headers ) )
	{ // Add additional headers
		$headerstring .= $lKey.': '.$lValue.$NL;
	}

	if( function_exists('mb_encode_mimeheader') )
	{ // encode subject
		$subject = mb_encode_mimeheader( $subject, mb_internal_encoding(), 'B', $NL );
	}

	$message = str_replace( array( "\r\n", "\r" ), $NL, $message );

	// Convert encoding of message (from internal encoding to the one of the message):
	if( $evo_charset != $current_charset && ! empty($evo_charset) && function_exists('mb_convert_encoding') )
	{
		mb_convert_variables( $current_charset, $evo_charset, $message );
	}

	if( $debug > 1 )
	{	// We agree to die for debugging...
		if( ! mail( $to, $subject, $message, $headerstring ) )
		{
			debug_die( 'Sending mail from &laquo;'.htmlspecialchars($from).'&raquo; to &laquo;'.htmlspecialchars($to).'&raquo;, Subject &laquo;'.htmlspecialchars($subject).'&raquo; FAILED.' );
		}
	}
	else
	{	// Soft debugging only....
		if( ! @mail( $to, $subject, $message, $headerstring ) )
		{
			$Debuglog->add( 'Sending mail from &laquo;'.htmlspecialchars($from).'&raquo; to &laquo;'.htmlspecialchars($to).'&raquo;, Subject &laquo;'.htmlspecialchars($subject).'&raquo; FAILED.' );
			return false;
		}
	}

	$Debuglog->add( 'Sent mail from &laquo;'.htmlspecialchars($from).'&raquo; to &laquo;'.htmlspecialchars($to).'&raquo;, Subject &laquo;'.htmlspecialchars($subject).'&raquo;.' );
	return true;
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
 * If first parameter evaluates to true printf() gets called using the first parameter
 * as args and the second parameter as print-pattern
 *
 * @param mixed variable to test and output if it's true or $disp_none is given
 * @param string printf-pattern to use (%s gets replaced by $var)
 * @param string printf-pattern to use, if $var is numeric and > 1 (%s gets replaced by $var)
 * @param string printf-pattern to use if $var evaluates to false (%s gets replaced by $var)
 */
function disp_cond( $var, $disp_one, $disp_more = NULL, $disp_none = NULL )
{
	if( is_numeric($var) && $var > 1 )
	{
		printf( ( $disp_more === NULL ? $disp_one : $disp_more ), $var );
		return true;
	}
	elseif( $var )
	{
		printf( $disp_one, $var );
		return true;
	}
	else
	{
		if( $disp_none !== NULL )
		{
			printf( $disp_none, $var );
			return false;
		}
	}
}


/**
 * Create IMG tag for an action icon.
 *
 * @param string TITLE text (IMG and A link)
 * @param string icon code, see {@link $map_iconfiles}
 * @param string icon code for {@link get_icon()}
 * @param string word to be displayed after icon
 * @param integer 1-5: weight of the icon. the icon will be displayed only if its weight is >= than the user setting threshold
 * @param integer 1-5: weight of the word. the word will be displayed only if its weight is >= than the user setting threshold
 * @param array Additional attributes to the A tag. It may also contain these params:
 *              'use_js_popup': if true, the link gets opened as JS popup. You must also pass an "id" attribute for this!
 *              'use_js_size': use this to override the default popup size ("500, 400")
 * @return string The generated action icon link.
 */
function action_icon( $title, $icon, $url, $word = NULL, $icon_weight = 4, $word_weight = 1, $link_attribs = array( 'class'=>'action_icon' ) )
{
	global $UserSettings;

	$link_attribs['href'] = $url;
	$link_attribs['title'] = $title;

	if( get_icon( $icon, 'rollover' ) )
	{
		if( empty($link_attribs['class']) )
		{
			$link_attribs['class'] = 'rollover';
		}
		else
		{
			$link_attribs['class'] .= ' rollover';
		}
	}

	// "use_js_popup": open link in a JS popup
	if( ! empty($link_attribs['use_js_popup']) )
	{
		$popup_js = 'var win = new PopupWindow(); win.autoHide(); win.setUrl( \''.$link_attribs['href'].'\' ); win.setSize(  ); ';

		if( isset($link_attribs['use_js_size']) )
		{
			if( ! empty($link_attribs['use_js_size']) )
			{
				$popup_size = $link_attribs['use_js_size'];
			}
		}
		else
		{
			$popup_size = '500, 400';
		}
		if( isset($popup_size) )
		{
			$popup_js .= 'win.setSize( '.$link_attribs['use_js_size'].' ); ';
		}
		$popup_js .= 'win.showPopup(\''.$link_attribs['id'].'\'); return false;';

		if( empty( $link_attribs['onclick'] ) )
		{
			$link_attribs['onclick'] = $popup_js;
		}
		else
		{
			$link_attribs['onclick'] .= $popup_js;
		}
		unset($link_attribs['use_js_popup']);
		unset($link_attribs['use_js_size']);
	}

	// NOTE: We do not use format_to_output with get_field_attribs_as_string() here, because it interferes with the Results class (eval() fails on entitied quotes..) (blueyed)
	$r = '<a '.get_field_attribs_as_string( $link_attribs, false ).'>';

	$display_icon = ($icon_weight >= $UserSettings->get('action_icon_threshold'));
	$display_word = ($word_weight >= $UserSettings->get('action_word_threshold'));

	if( $display_icon || ! $display_word )
	{	// We MUST display an action icon in order to make the user happy:
		// OR we default to icon because the user doesn't want the word either!!

		$r .= get_icon( $icon, 'imgtag', array( 'title'=>$title ), true );
	}

	if( $display_word )
	{	// We MUST display an action word in order to make the user happy:

		if( $display_icon )
		{ // We already have an icon, display a SHORT word:
			if( !empty($word) )
			{	// We have provided a short word:
				$r .= $word;
			}
			else
			{	// We fall back to alt:
				$r .= get_icon( $icon, 'legend' );
			}
		}
		else
		{	// No icon display, let's display a LONG word/text:
			$r .= trim( $title, ' .!' );
		}
	}

	$r .= '</a>';

	return $r;
}


/**
 * Get properties of an icon.
 *
 * Note: to get a file type icon, use {@link File::get_icon()} instead.
 *
 * @uses $map_iconfiles
 * @param string icon for what? (key)
 * @param string what to return for that icon ('imgtag', 'alt', 'legend', 'file', 'url', 'size' {@link imgsize()})
 * @param array additional params (
 *              'class' => class name when getting 'imgtag',
 *              'size' => param for 'size',
 *              'title' => title attribute for 'imgtag')
 * @param boolean true to include this icon into the legend at the bottom of the page (works for 'imgtag' only)
 */
function get_icon( $iconKey, $what = 'imgtag', $params = NULL, $include_in_legend = false )
{
	global $map_iconfiles, $basepath, $admin_subdir, $baseurl, $Debuglog,	$IconLegend;

	if( isset( $map_iconfiles[$iconKey] ) && isset( $map_iconfiles[$iconKey]['file'] ) )
	{
		$iconfile = $map_iconfiles[$iconKey]['file'];
	}
	else
	{
		return '[no image defined for '.var_export( $iconKey, true ).'!]';
	}

	switch( $what )
	{
		case 'rollover':
			if( isset( $map_iconfiles[$iconKey]['rollover'] ) )
			{	// Image has rollover available
				return $map_iconfiles[$iconKey]['rollover'];
			}
			return false;
			/* BREAK */


		case 'file':
			return $basepath.$iconfile;
			/* BREAK */


		case 'alt':
			if( isset( $map_iconfiles[$iconKey]['alt'] ) )
			{ // alt tag from $map_iconfiles
				return $map_iconfiles[$iconKey]['alt'];
			}
			else
			{ // fallback to $iconKey as alt-tag
				return $iconKey;
			}
			/* BREAK */


		case 'legend':
			if( isset( $map_iconfiles[$iconKey]['legend'] ) )
			{ // legend tag from $map_iconfiles
				return $map_iconfiles[$iconKey]['legend'];
			}
			else
			if( isset( $map_iconfiles[$iconKey]['alt'] ) )
			{ // alt tag from $map_iconfiles
				return $map_iconfiles[$iconKey]['alt'];
			}
			else
			{ // fallback to $iconKey as alt-tag
				return $iconKey;
			}
			/* BREAK */


		case 'class':
			if( isset($map_iconfiles[$iconKey]['class']) )
			{
				return $map_iconfiles[$iconKey]['class'];
			}
			else
			{
				return 'middle';
			}
			/* BREAK */

		case 'url':
			return $baseurl.$iconfile;
			/* BREAK */

		case 'size':
			if( !isset( $map_iconfiles[$iconKey]['size'] ) )
			{
				$Debuglog->add( 'No iconsize for ['.$iconKey.']', 'icons' );

				$map_iconfiles[$iconKey]['size'] = imgsize( $iconfile );
			}

			switch( $params['size'] )
			{
				case 'width':
					return $map_iconfiles[$iconKey]['size'][0];

				case 'height':
					return $map_iconfiles[$iconKey]['size'][1];

				case 'widthxheight':
					return $map_iconfiles[$iconKey]['size'][0].'x'.$map_iconfiles[$iconKey]['size'][1];

				case 'width':
					return $map_iconfiles[$iconKey]['size'][0];

				case 'string':
					return 'width="'.$map_iconfiles[$iconKey]['size'][0].'" height="'.$map_iconfiles[$iconKey]['size'][1].'"';

				default:
					return $map_iconfiles[$iconKey]['size'];
			}
			/* BREAK */


		case 'imgtag':
			$r = '<img src="'.$baseurl.$iconfile.'" ';

			// Include non CSS fallbacks:
			$r .= 'border="0" align="top" ';

			// Include class (will default to "middle"):
			if( ! isset( $params['class'] ) )
			{
				if( isset($map_iconfiles[$iconKey]['class']) )
				{	// This icon has a class
					$params['class'] = $map_iconfiles[$iconKey]['class'];
				}
				else
				{
					$params['class'] = 'middle';
				}
			}

			// Include size (optional):
			if( isset( $map_iconfiles[$iconKey]['size'] ) )
			{
				$r .= 'width="'.$map_iconfiles[$iconKey]['size'][0].'" height="'.$map_iconfiles[$iconKey]['size'][1].'" ';
			}

			// Include alt (XHTML mandatory):
			if( ! isset( $params['alt'] ) )
			{
				if( isset( $map_iconfiles[$iconKey]['alt'] ) )
				{ // alt-tag from $map_iconfiles
					$params['alt'] = $map_iconfiles[$iconKey]['alt'];
				}
				else
				{ // $iconKey as alt-tag
					$params['alt'] = $iconKey;
				}
			}

			// Add all the attributes:
			$r .= get_field_attribs_as_string( $params, false );

			// Close tag:
			$r .= '/>';


			if( $include_in_legend && isset( $IconLegend ) )
			{ // This icon should be included into the legend:
				$IconLegend->add_icon( $iconKey );
			}

			return $r;
			/* BREAK */
	}
}


/**
 * @param string date
 * @param string time
 */
function form_date( $date, $time = '' )
{
	return substr( $date.'          ', 0, 10 ).' '.$time;
}


/**
 * Displays an empty or a full bullet based on boolean
 *
 * @param boolean true for full bullet, false for empty bullet
 */
function bullet( $bool )
{
	if( $bool )
		return get_icon( 'bullet_full', 'imgtag' );

	return get_icon( 'bullet_empty', 'imgtag' );
}


/**
 * Get list of client IP addresses from REMOTE_ADDR and HTTP_X_FORWARDED_FOR,
 * in this order. '' is used when no IP could be found.
 *
 * @param boolean True, to get only the first IP (probably REMOTE_ADDR)
 * @return array|string Depends on first param.
 */
function get_ip_list( $firstOnly = false )
{
	$r = array();

	if( !empty( $_SERVER['REMOTE_ADDR'] ) )
	{
		foreach( explode( ',', $_SERVER['REMOTE_ADDR'] ) as $l_ip )
		{
			$l_ip = trim($l_ip);
			if( !empty($l_ip) )
			{
				$r[] = $l_ip;
			}
		}
	}

	if( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) )
	{ // IP(s) behind Proxy - this can be easily forged!
		foreach( explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] ) as $l_ip )
		{
			$l_ip = trim($l_ip);
			if( !empty($l_ip) && $l_ip != 'unknown' )
			{
				$r[] = $l_ip;
			}
		}
	}

	if( !isset( $r[0] ) )
	{ // No IP found.
		$r[] = '';
	}

	return $firstOnly ? $r[0] : $r;
}


/**
 * Get the base domain (without protocol and "www.") of an URL.
 *
 * We only strip "www." here (and not any/all subdomain(s))!
 *
 * @param string URL
 * @return string the base domain
 */
function get_base_domain( $url )
{
	$base_domain = preg_replace( '~^[a-z]+://~i', '', $url );
	$base_domain = preg_replace( '/^www\./i', '', $base_domain );
	$base_domain = preg_replace( '~(:[1-9]+)?/.*~i', '', $base_domain );

	return $base_domain;
}


/**
 * Generate a valid key of size $length.
 *
 * @param integer length of key (defaults to minimal password length)
 * @return string key
 */
function generate_random_key( $length = NULL )
{
	static $keychars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

	if( is_null($length) )
	{
		global $Settings;

		$length = isset($Settings) // not set during install
			? $Settings->get( 'user_minpwdlen' )
			: 6;
	}

	$key = '';

	for( $i = 0; $i < $length; $i++ )
	{
		$key .= $keychars{mt_rand(0, 61 )}; // get a random character out of $keychars
	}

	return $key;
}


/**
 * Sends HTTP headers to avoid caching of the page.
 */
function header_nocache()
{
	header('Expires: Tue, 25 Mar 2003 05:00:00 GMT');
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: no-cache, must-revalidate');
	header('Pragma: no-cache');
}



/**
 * Sends HTTP header to redirect to the previous location (which
 * can be given as function parameter, GET parameter (redirect_to),
 * is taken from {@link Hit::referer} or {@link $baseurl}).
 *
 * NOTE: This function {@link exit() exits} the php script execution.
 *
 * @param string Override detection of where we redirect to.
 */
function header_redirect( $redirect_to = NULL )
{
	global $Hit, $baseurl, $Blog, $htsrv_url, $Request;

	if( empty($redirect_to) )
	{
		$redirect_to = $Request->param( 'redirect_to', 'string', '' );
	}

	if( empty($redirect_to) )
	{
		if( ! empty($Hit->referer) )
		{
			$redirect_to = $Hit->referer;
		}
		elseif( isset($Blog) && is_object($Blog) )
		{
			$redirect_to = $Blog->get('url');
		}
		else
		{
			$redirect_to = $baseurl;
		}
	}

	$redirect_to = str_replace('&amp;', '&', $redirect_to);

	if( strpos($redirect_to, $htsrv_url) === 0 /* we're going somewhere on $htsrv_url */
	 || strpos($redirect_to, $baseurl) === 0   /* we're going somewhere on $baseurl */ )
	{
		// Remove login and pwd parameters from URL, so that they do not trigger the login screen again:
		// Also remove "action" get param to avoid unwanted actions
		$redirect_to = preg_replace( '~(?<=\?|&amp;|&) (login|pwd|action) = [^&]+ (&(amp;)?|\?)?~x', '', $redirect_to );
	}

	#header('Refresh:0;url='.$redirect_to);
	#exit();
	// fplanque> Note: I am not sure using this is cacheing safe: header('Location: '.$redirect_to);
	// Current "Refresh" version works fine.
	// Please provide link to relevant material before changing it.
	// blueyed>> The above method fails when you redirect after a POST to the same URL.
	//   Regarding http://de3.php.net/manual/en/function.header.php#50588 and the other comments
	//   around, I'd suggest:
	header( 'HTTP/1.1 303 See Other' ); // see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
	header( 'Location: '.$redirect_to );
	exit();
}


function is_create_action( $action )
{
	$action_parts = explode( '_', $action );

	switch( $action_parts[0] )
	{
		case 'new':
		case 'copy':
		case 'create':	// we return in this state after a validation error
			return true;

		case 'edit':
		case 'update':	// we return in this state after a validation error
		case 'delete':
		// The following one's a bit far fetched, but can happen if we have no sheet display:
		case 'delete_link':
		case 'view':
			return false;

		default:
			debug_die( 'Unhandled action in form: '.strip_tags($action_parts[0]) );
	}
}


/**
 * Generate a link that toggles display of an element on clicking.
 *
 * @todo Provide functionality to make those links accessible without JS (using GET parameter)
 * @uses toggle_display_by_id() (JS)
 * @param string ID (html) of the link
 * @param string ID (html) of the target to toggle displaying
 * @return string
 */
function get_link_showhide( $link_id, $target_id, $text_when_displayed, $text_when_hidden, $display_hidden = true )
{
	$html = "<a id='$link_id' href='#' onclick='return toggle_display_by_id(\"$link_id\",\"$target_id\",\"".str_replace( '"', '\"', $text_when_displayed ).'","'.str_replace( '"', '\"', $text_when_hidden ).'")\'>'
		.( $display_hidden ? $text_when_hidden : $text_when_displayed )
		.'</a>';

	return $html;
}


/**
 * Compact a date in a number keeping only integer value of the string
 *
 * @param string date
 */
function compact_date( $date )
{
 	return preg_replace( '#[^0-9]#', '', $date );
}


/**
 * Decompact a date in a date format ( Y-m-d h:m:s )
 *
 * @param string date
 */
function decompact_date( $date )
{
	$date0 = $date;

	return  substr($date0,0,4).'-'.substr($date0,4,2).'-'.substr($date0,6,2).' '
								.substr($date0,8,2).':'.substr($date0,10,2).':'.substr($date0,12,2);
}

/**
 * Check the format of the phone number param and
 * format it in a french number if it is.
 *
 * @param string phone number
 */
function format_phone( $phone, $hide_country_dialing_code_if_same_as_locale = true )
{
	global $CountryCache;

	$dialing_code = NULL;

	if( substr( $phone, 0, 1 ) == '+' )
	{	// We have a dialing code in the phone, so we extract it:
		$dialing_code = $CountryCache->extract_country_dialing_code( substr( $phone, 1 ) );
	}

	if( !is_null( $dialing_code ) && ( locale_dialing_code() == $dialing_code )
			&& $hide_country_dialing_code_if_same_as_locale )
	{	// The phone dialing code is same as locale and we want to hide it in this case
		if( ( strlen( $phone ) - strlen( $dialing_code ) ) == 10 )
		{	// We can format it like a french phone number ( 0x.xx.xx.xx.xx )
			$phone_formated = format_french_phone( '0'.substr( $phone, strlen( $dialing_code )+1 ) );
		}
		else
		{ // ( 0xxxxxxxxxxxxxx )
			$phone_formated = '0'.substr( $phone, strlen( $dialing_code )+1 );
		}

	}
	elseif( !is_null( $dialing_code ) )
	{	// Phone has a dialing code
		if( ( strlen( $phone ) - strlen( $dialing_code ) ) == 10 )
		{ // We can format it like a french phone number with the dialing code ( +dialing x.xx.xx.xx.xx )
			$phone_formated = '+'.$dialing_code.format_french_phone( ' '.substr( $phone, strlen( $dialing_code )+1 ) );
		}
		else
		{ // ( +dialing  xxxxxxxxxxx )
			$phone_formated = '+'.$dialing_code.' '.substr( $phone, strlen( $dialing_code )+1 );
		}
	}
	else
	{
		if( strlen( $phone ) == 10 )
		{ //  We can format it like a french phone number ( xx.xx.xx.xx.xx )
			$phone_formated = format_french_phone( $phone );
		}
		else
		{	// We don't format phone: TODO generic format phone ( xxxxxxxxxxxxxxxx )
			$phone_formated = $phone;
		}
	}

	return $phone_formated;
}


/**
 * Format a string in a french phone number
 *
 * @param string phone number
 */
function format_french_phone( $phone )
{
	return substr($phone, 0 , 2).'.'.substr($phone, 2, 2).'.'.substr($phone, 4, 2)
					.'.'.substr($phone, 6, 2).'.'.substr($phone, 8, 2);
}


/**
 * Generate a link to a online help resource.
 * testing the concept of online help (aka webhelp).
 * this function should be relocated somewhere better if it is taken onboard by the project
 *
 * @todo replace [?] with icon,
 * @todo write url suffix dynamically based on topic and language
 *
 * QUESTION: launch new window with javascript maybe?
 * @param string Topic
 *        The topic should be in a format like [\w]+(/[\w]+)*, e.g features/online_help.
 * @return string
 */
function get_web_help_link( $topic )
{
	global $Settings, $current_locale, $app_shortname, $app_version;

	if( $Settings->get('webhelp_enabled') )
	{
		$webhelp_link = ' <a target="_blank" href="http://manual.b2evolution.net/redirect/'.str_replace(" ","_",strtolower($topic))
							.'?lang='.$current_locale.'&amp;app='.$app_shortname.'&amp;version='.$app_version.'">' . get_icon('webhelp') . '</a>';

//		$webhelp_link = ' <a target="_blank" href="http://manual.b2evolution.net/redirect/'.$topic
//			.'?lang='.$current_locale.'&amp;app='.$app_shortname.'&amp;version='.$app_version.'">[?]</a>';

		return $webhelp_link;
	}
	else
	{
		return '';
	}
}


/**
 * Build a string out of $field_attribs, with each attribute
 * prefixed by a space character.
 *
 * @param array Array of field attributes.
 * @param boolean Use format_to_output() for the attributes?
 * @return string
 */
function get_field_attribs_as_string( $field_attribs, $format_to_output = true )
{
	$r = '';

	if( empty( $field_attribs ) )
	{ // TODO: This extra check should not be needed, if $field_attribs is an array! (blueyed)
		return $r;
	}

	foreach( $field_attribs as $l_attr => $l_value )
	{
		if( $l_value === '' || $l_value === NULL )
		{ // don't generate empty attributes (it may be NULL if we pass 'value' => NULL as field_param for example, because isset() does not match it!)
			continue;
		}

		if( $format_to_output )
		{
			if( $l_attr == 'value' )
			{
				$r .= ' '.$l_attr.'="'.format_to_output( $l_value, 'formvalue' ).'"';
			}
			else
			{
				// TODO: this uses strip_tags et al! Shouldn't we just use "formvalue" always? (E.g. it kills "for( var i=0; i<a; i++ )..." (in an onclick attr) from "<a" on. The workaround is to use spaces ("i < a"), but I was confused first)
				$r .= ' '.$l_attr.'="'.format_to_output( $l_value, 'htmlattr' ).'"';
			}
		}
		else
		{
			$r .= ' '.$l_attr.'="'.$l_value.'"';
		}
	}

	return $r;
}


/**
 * Is the current page an admin/backoffice page?
 *
 * @return boolean
 */
function is_admin_page()
{
	global $is_admin_page;

	return isset($is_admin_page) && $is_admin_page === true; // check for type also, because of register_globals!
}


/**
 * Implode array( 'x', 'y', 'z' ) to something like 'x, y and z'. Useful for displaying list to the end user.
 *
 * If there's one element in the table, it is returned.
 * If there are at least two elements, the last one is concatenated using $implode_last, while the ones before are imploded using $implode_by.
 *
 * @todo Support for locales that have a different kind of enumeration?!
 * @return string
 */
function implode_with_and( $arr, $implode_by = ', ', $implode_last = NULL )
{
	switch( count($arr) )
	{
		case 0:
			return '';

		case 1:
			$r = array_shift($arr);
			return $r;

		default:
			if( ! isset($implode_last) )
			{
				$implode_last = /* TRANS: Used to append the last element of an enumeration of at least two strings */ T_(' and ');
			}

			$r = implode( $implode_by, array_slice( $arr, 0, -1 ) )
			    .$implode_last.array_pop( $arr );
			return $r;
	}
}


/**
 * Returns a "<base />" tag and remembers that we've used it ({@link regenerate_url()} needs this).
 *
 * @param string URL to use (this gets used as base URL for all relative links on the HTML page)
 * @return string
 */
function base_tag( $url )
{
	global $base_tag_set;

	$base_tag_set = true;
	echo '<base href="'.$url.'" />';
}


/**
 * This gets used as a {@link unserialize()} callback function, which is
 * responsible to load the requested class.
 *
 * @todo Once we require PHP5, we should think about using this as __autoload function.
 *
 * Currently, this just gets used by the {@link Session} class and includes the
 * {@link Comment} class and its dependencies.
 *
 * @return boolean True, if the required class could be loaded; false, if not
 */
function unserialize_callback( $classname )
{
	global $model_path, $object_def;

	switch( strtolower($classname) )
	{
		case 'blog':
			require_once $model_path.'collections/_blog.class.php';
			return true;

		case 'collectionsettings':
			require_once $model_path.'collections/_collsettings.class.php';
			return true;

		case 'comment':
			require_once $model_path.'comments/_comment.class.php';
			return true;

		case 'item':
			require_once $model_path.'items/_item.class.php';
			return true;

		case 'group':
			require_once $model_path.'users/_group.class.php';
			return true;

		case 'user':
			require_once $model_path.'users/_user.class.php';
			return true;
	}

	return false;
}



/*
 nolog */
?>
