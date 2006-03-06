<?php
/**
 * This file implements general purpose functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
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
 * @param string Encoding (used for SafeHtmlChecker())
 * @return string
 */
function format_to_post( $content, $autobr = 0, $is_comment = 0, $encoding = 'ISO-8859-1' )
{
	global $use_balanceTags, $use_html_checker, $use_security_checker;
	global $allowed_tags, $allowed_attributes, $uri_attrs, $allowed_uri_scheme;
	global $comments_allowed_tags, $comments_allowed_attributes, $comments_allowed_uri_scheme;

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
 * Convert all non ASCII chars (except if UTF-8) to &#nnnn; unicode references.
 * Also convert entities to &#nnnn; unicode references if output is not HTML (eg XML)
 *
 * Preserves < > and quotes.
 *
 * fplanque: simplified
 * sakichan: pregs instead of loop
 */
function convert_chars( $content, $flag='html' )
{
	global $b2_htmltrans, $b2_htmltranswinuni;

	// Convert highbyte non ASCII/UTF-8 chars to urefs:
	if( (locale_charset(false) != 'utf-8') && (locale_charset(false) != 'gb2312') )
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
 * Make links clickable in a given text.
 *
 * {@internal NOTE: its tested in the misc.funcs.simpletest.php test case }}
 *
 * @todo IMHO it would be better to use "\b" (word boundary) to match the beginning of links..
 *
 * original function: phpBB, extended here for AIM & ICQ
 * fplanque restricted :// to http:// and mailto://
 */
function make_clickable( $text, $moredelim = '&amp;' )
{
	$text = preg_replace(
		array( '#(^|[\s>])(https?|mailto)://([^<>{}\s]+[^.,<>{}\s])#i',
			'#(^|[\s>])aim:([^,<\s]+)#i',
			'#(^|[\s>])icq:(\d+)#i',
			'#(^|[\s>])www\.([a-z0-9\-]+)\.([a-z0-9\-.\~]+)((?:/[^<\s]*)?[^.,\s])#i',
			'#(^|[\s>])([a-z0-9\-_.]+?)@([^,<\s]+)#i', ),
		array( '$1<a href="$2://$3">$2://$3</a>',
			'$1<a href="aim:goim?screenname=$2$3'.$moredelim.'message='.rawurlencode(T_('Hello')).'">$2$3</a>',
			'$1<a href="http://wwp.icq.com/scripts/search.dll?to=$2">$2</a>',
			'$1<a href="http://www.$2.$3$4">www.$2.$3$4</a>',
			'$1<a href="mailto:$2@$3">$2@$3</a>', ),
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
		$protected_dateformatstring = preg_replace( '/(?<!\\\)([blDeFM])/',
																								'@@@\\\$1@@@',
																								$dateformatstring );

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
	$first_week_start_date = $new_years_date + $days_to_new_week * 86400;
	// echo '<br> 1stweeks starts on '.date( locale_datefmt(), $first_week_start_date );

	// We add the number of requested weeks:
	$date = $first_week_start_date + ($week-1) * 604800;
	// echo '<br> week '.$week.' starts on '.date( locale_datefmt(), $date );

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


// fp>> WHAT IS THAT???
function antispambot($emailaddy, $mailto = 0)
{
	$emailNOSPAMaddy = '';
	srand ((float) microtime() * 1000000);
	for ($i = 0; $i < strlen($emailaddy); $i = $i + 1) {
		$j = floor(rand(0, 1 + $mailto));
		if ($j == 0) {
			$emailNOSPAMaddy .= '&#' . ord( substr( $emailaddy, $i, 1 ) ). ';';
		} elseif ($j == 1) {
			$emailNOSPAMaddy .= substr($emailaddy, $i, 1);
		} elseif ($j == 2) {
			$emailNOSPAMaddy .= '%' . zeroise( dechex( ord( substr( $emailaddy, $i, 1 ) ) ), 2 );
		}
	}
	$emailNOSPAMaddy = str_replace('@', '&#64;', $emailNOSPAMaddy);
	return $emailNOSPAMaddy;
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

	$message_Log->add( T_('Response').': '.$out, 'note' );

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
	global $global_param_list, $Debuglog, $debug;
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
			debug_die( '<p class="error">'.sprintf( T_('Parameter &laquo;%s&raquo; is required!'), $var ).'</p>' );
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
 * regenerate_url(-)
 *
 * Regenerate current URL from parameters
 * This may clean it up
 * But it is also useful when generating static pages: you cannot rely on $_REQUEST[]
 *
 * @param mixed string (delimited by commas) or array of params to ignore (can be regexps in /.../)
 * @param mixed string or array of param(s) to set
 * @param mixed string Alternative URL we want to point to if not the current $ReqPath
 */
function regenerate_url( $ignore = '', $set = '', $pagefileurl = '' )
{
	global $Debuglog, $global_param_list, $ReqPath, $basehost;

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
	$url = empty($pagefileurl) ? $ReqPath : $pagefileurl;
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

	// minimum length: http://az.fr/
	// TODO: fails on "http://blogs" (without trailing slash)  fp>> yes, "blogs" is not a valid domain name, allowing this could cause all sorts of unexpected problems
	if( strlen($url) < 13 )
	{ // URL too short!
		$Debuglog->add( 'URL &laquo;'.$url.';&raquo; is too short!', 'error' );
		return T_('Invalid URL');
	}

	// Validate URL structure
	// NOTE: this causes the most problems with this function!
	// fp>> we should probably go back to a very laxist scheme here... :(
	// blueyed>> yes, seems so.
	/* Remaining problems with this one are:
	 *  - no spaces in URL allowed (must be written as %20)
	 *  - umlauts in domains/url
	 */
	if( ! preg_match('~^                # start
		(?:
			(?: ([a-z][a-z0-9+.\-]*):[0-9]*       # scheme
				//                                # authority absolute URLs only
			)|(mailto):
		)
		[a-z0-9]([a-z0-9\~+.\-_,:;/\\\\*=@]|(%\d+))* # Don t allow anything too funky like entities
		([?#][a-z0-9\~+.\-_,:;/\\\\%&=!?#*\ \[\]]*)?
		$~ix', $url, $matches) )
	{ // Cannot validate URL structure
		$Debuglog->add( 'URL &laquo;'.$url.'&raquo; does not match url pattern!', 'error' );
		return T_('Invalid URL');
	}

	$scheme = empty( $matches[1] ) ? strtolower($matches[2]) : strtolower($matches[1]);
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
 * @param mixed variable to dump
 * @param string title to display
 */
function pre_dump( $vars )
{
	echo '<pre style="padding:1ex;border:1px solid #00f;">';
	foreach( func_get_args() as $lvar )
	{
		echo htmlspecialchars( var_export( $lvar, true ) ).'<br />';
	}
	echo '</pre>';
	#echo debug_get_backtrace();
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

		$r .= '<div style="padding:1ex; text-align:left; font-family:monospace; color:#000; background-color:#ddf"><h3>Backtrace:</h3>'."\n";
		if( $count_backtrace )
		{
			$r .= '<ol>';

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
 * Outputs last words. When in debug mode it also prints a backtrace.
 *
 * After this, it prints by default '</body></html>' to keep the output
 * probably valid.
 *
 * @param string Message to output
 * @param boolean|NULL If set it overrides the setting of {@link $debug} to
 *                     decide if we want a backtrace and whole debug_info.
 * @param string This gets output at the very end (after backtrace and last words)
 * @param string Include function backtrace if outputting debug_info()?
 *               (used in DB class when we already have the backtrace for the mysql error)
 */
function debug_die( $last_words = '', $force = NULL, $very_last = '</body></html>', $include_backtrace = true )
{
	global $debug;

	echo $last_words;

	if( ( isset($force) && $force ) || ( !isset($force) && $debug ) )
	{
		if( $include_backtrace )
		{
			echo debug_get_backtrace();
		}
		debug_info();
	}

	die( $very_last );
}


/**
 * Outputs debug info. (Typically at the end of the page)
 *
 * @param boolean true to force output
 */
function debug_info( $force = false )
{
	global $debug, $Debuglog, $DB, $obhandler_debug, $Timer, $ReqURI;
	global $cache_imgsize, $cache_File;

	if( $debug || $force )
	{
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
				$percent_l_cat = $time_page > 0 ? number_format( 100/$time_page * $l_time, 2 ) : 0;

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
				echo '<a href="'.format_to_output($ReqURI).'#evo_debug_queries">Database queries: '.$DB->num_queries.'.</a><br />';
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
		$log_cats = array_keys($Debuglog->getMessages( $log_categories )); // the real list (with all replaced and only existing ones)
		$log_container_head = '<h3>Debug messages</h3>';
		$log_head_links = array();
		foreach( $log_cats as $l_cat )
		{
			$log_head_links[] .= '<a href="'.$ReqURI.'#debug_info_cat_'.str_replace( ' ', '_', $l_cat ).'">'.$l_cat.'</a>';
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
}


/**
 * Output Buffer handler.
 *
 * It will be set in /blogs/evocore/_main.inc.php and handle the output.
 * It strips every line and generates a md5-ETag, which is checked against the one eventually
 * being sent by the browser.
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

	// trim each line
	$output = explode("\n", $output);
	$out = '';
	foreach ($output as $v)
	{
		$out .= trim($v)."\n";
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
	if( $use_gzipcompression
			&& isset($_SERVER['HTTP_ACCEPT_ENCODING'])
			&& strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') )
	{
		$out = gzencode($out);
		header( 'Content-Encoding: gzip' );
	}


	header( 'Content-Length: '.strlen($out) );
	return $out;
}


/**
 * Add param(s) at the end of an URL, using either ? or &amp; depending on exiting url
 *
 * url_add_param(-)
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
 * Add a tail (starting with /) at the end of an URL before any params (starting with ?)
 *
 * url_add_tail(-)
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
 * @param array Additional headers ( headername => value ). Take care of injection!
 */
function send_mail( $to, $subject, $message, $from = '', $headers = array() )
{
	global $debug, $app_name, $app_version, $current_locale, $locales, $Debuglog;

	$NL = "\n";

	if( !is_array( $headers ) )
	{ // Make sure $headers is an array
		$headers = array( $headers );
	}

	// Specify charset and content-type of email
	$headers['Content-Type'] = 'text/plain; charset='.$locales[ $current_locale ]['charset'];
	$headers['X-Mailer'] = $app_name.' '.$app_version.' - PHP/'.phpversion();
	$headers['X-Remote-Addr'] = implode( ',', get_ip_list() );

	// -- Build headers ----
	$from = trim($from);
	if( !empty($from) )
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

		$headerstring = "From: $from$NL";
	}
	else
	{
		$headerstring = '';
	}

	reset( $headers );
	while( list( $lKey, $lValue ) = each( $headers ) )
	{ // Add additional headers
		$headerstring .= $lKey.': '.$lValue.$NL;
	}

	$message = str_replace( array( "\r\n", "\r" ), $NL, $message );

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
 * @return boolean
 */
function isRegexp( $RegExp )
{
	$sPREVIOUSHANDLER = set_error_handler( '_trapError' );
	preg_match( '#'.str_replace( '#', '\#', $RegExp ).'#', '' );
	restore_error_handler( $sPREVIOUSHANDLER );

	return !_traperror();
}


/**
 * Meant to replace error handler.
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
 * if first parameter evaluates to true printf() gets called using the first parameter
 * as args and the second parameter as print-pattern
 *
 * @param mixed variable to test and print eventually
 * @param string printf-pattern to use (including %s etc to refer to the first param
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
 * @param array Additional attributes to the A tag. It may also contain these params:
 *              'use_js_popup': if true, the link gets opened as JS popup. You must also pass an "id" attribute for this!
 * @return string The generated action icon link.
 */
function action_icon( $title, $icon, $url, $word = NULL, $link_attribs = array() )
{
	/*
	// Fails when the same icon gets re-used (Results class)..
	static $count_generated = 0;

	if( ! isset($link_attribs['id']) )
	{
		$link_attribs['id'] = 'action_icon_'.$count_generated++;
	}
	*/

	$link_attribs['href'] = $url;
	$link_attribs['title'] = $url;

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
	if( isset($link_attribs['use_js_popup']) )
	{
		$popup_js = 'var win = new PopupWindow(); win.autoHide(); win.setUrl( \''.$link_attribs['href'].'\' ); win.setSize( 500, 400 ); win.showPopup(\''.$link_attribs['id'].'\'); return false;';
		if( empty( $link_attribs['onclick'] ) )
		{
			$link_attribs['onclick'] = $popup_js;
		}
		else
		{
			$link_attribs['onclick'] .= $popup_js;
		}
		unset($link_attribs['use_js_popup']);
	}

	// NOTE: We do not use format_to_output with get_field_attribs_as_string() here, because it interferes with the Results class (eval() fails on entitied quotes..) (blueyed)
	$r = '<a '.get_field_attribs_as_string( $link_attribs, false ).'>'.get_icon( $icon, 'imgtag', array( 'title'=>$title ), true );
	if( !empty($word) )
	{
		$r .= $word;
	}
	$r .= '</a> ';

	return $r;
}


/**
 * Get properties of an icon.
 *
 * Note: to get a file type icon, use {@link File::get_icon()} instead.
 *
 * @uses $map_iconfiles
 * @param string icon for what? (key)
 * @param string what to return for that icon ('imgtag', 'alt', 'file', 'url', 'size' {@link imgsize()})
 * @param array additional params ( 'class' => class name when getting 'imgtag',
																		'size' => param for 'size',
																		'title' => title attribute for imgtag)
 * @param boolean true to include this icon into the legend at the bottom of the page
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
		$iconfile = false;
	}

	/**
	 * debug quite time consuming
	 */
	if( $iconfile === false )
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

		case 'file':
			return $basepath.$iconfile;

		case 'alt':
			if( isset( $map_iconfiles[$iconKey]['alt'] ) )
			{ // alt-tag from $map_iconfiles
				return $map_iconfiles[$iconKey]['alt'];
			}
			else
			{ // $iconKey as alt-tag
				return $iconKey;
			}

		case 'class':
			if( isset($map_iconfiles[$iconKey]['class']) )
			{
				return $map_iconfiles[$iconKey]['class'];
			}
			else
			{
				return 'middle';
			}

		case 'imgtag':
			$params['size'] = 'string';
		case 'size':
			if( !isset( $map_iconfiles[$iconKey]['size'] ) )
			{
				$Debuglog->add( 'No iconsize for ['.$iconKey.']', 'icons' );

				$map_iconfiles[$iconKey]['size'] = imgsize( $iconfile );
			}

			switch( $params['size'] )
			{
				case 'width':
					$size = $map_iconfiles[$iconKey]['size'][0];
					break;
				case 'height':
					$size = $map_iconfiles[$iconKey]['size'][1];
					break;
				case 'widthxheight':
					$size = $map_iconfiles[$iconKey]['size'][0].'x'.$map_iconfiles[$iconKey]['size'][1];
					break;
				case 'width':
					$size = $map_iconfiles[$iconKey]['size'][0];
					break;
				case 'string':
					$size = 'width="'.$map_iconfiles[$iconKey]['size'][0].'" height="'.$map_iconfiles[$iconKey]['size'][1].'"';
					break;
				default:
					$size = $map_iconfiles[$iconKey]['size'];
			}

			if( $what == 'size' )
			{
				return $size;
			}

		case 'url':
			$iconurl = $baseurl.$iconfile;
			if( $what == 'url' )
			{
				return $iconurl;
			}

			$r = '<img class="';
			if( isset( $params['class'] ) )
			{
				$r .= $params['class'];
			}
			elseif( isset($map_iconfiles[$iconKey]['class']) )
			{	// This icon has a class
				$r .= $map_iconfiles[$iconKey]['class'];
			}
			else
			{
				$r .= 'middle';
			}
			$r .= '" src="'.$iconurl.'" '
				.$size
				.( isset( $params['title'] ) ? ' title="'.$params['title'].'"' : '' )
				.' alt="';

			if( isset( $params['alt'] ) )
			{
				$r .= $params['alt'];
			}
			elseif( isset( $map_iconfiles[$iconKey]['alt'] ) )
			{ // alt-tag from $map_iconfiles
				$r .= $map_iconfiles[$iconKey]['alt'];
			}
			else
			{ // $iconKey as alt-tag
				$r .= $iconKey;
			}

			$r .= '" />';
			break;
	}

	if( $include_in_legend && isset( $IconLegend ) )
	{ // This icon should be included into the legend:
		$IconLegend->add_icon( $iconKey );
	}

	return $r;
}


/**
 * Validate ISO date
 *
 * @param string date
 * @param string time
 * @param boolean is date required ?
 * @param boolean is time required ?
 */
function make_valid_date( $date, $time = '', $req_date = true, $req_time = true )
{
	global $Messages;

	if( ! empty($date) )
	{	// A date is provided:
		if( ! preg_match( '#^\d\d\d\d-\d\d-\d\d$#', $date ) )
		{
			$Messages->add( T_('Date is invalid'), 'error' );
			$date = '2000-01-01';
		}
	}
	elseif( $req_date )
	{	// No date but it was required!
		$Messages->add( T_('Date is required'), 'error' );
		$date = '2000-01-01';
	}

	if( ! empty($time) )
	{	// A time is provided:
		if( ! preg_match( '#^\d\d:\d\d:\d\d$#', $time ) )
		{
			$Messages->add( T_('Time is invalid'), 'error' );
			$time = '00:00:00';
		}
	}
	elseif( $req_time )
	{	// No time but it was required!
		$Messages->add( T_('Time is required'), 'error' );
		$time = '00:00:00';
	}


	return $date.(empty($time) ? '' : ' '.$time );
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
 * Get the base domain (without "www.") of an URL.
 *
 * @param string URL
 * @return string the base domain
 */
function getBaseDomain( $url )
{
	$baseDomain = preg_replace("/http:\/\//i", "", $url);
	$baseDomain = preg_replace("/^www\./i", "", $baseDomain);
	$baseDomain = preg_replace("/\/.*/i", "", $baseDomain);

	return $baseDomain;
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
 */
function header_redirect( $redirectTo = NULL )
{
	global $Hit, $baseurl;

	if( is_null($redirectTo) )
	{
		$redirectTo = param( 'redirect_to', 'string', $Hit->referer );
	}

	$location = empty($redirectTo) ? $baseurl : $redirectTo;

	$location = str_replace('&amp;', '&', $location);

	if( strpos($location, $baseurl) === 0 /* we're somewhere on $baseurl */ )
	{
		// Remove login and pwd parameters from URL, so that they do not trigger the login screen again:
		// Also remove "action" get param to avoid unwanted actions
		$location = preg_replace( '~(?<=\?|&amp;|&) (login|pwd|action) = [^&]+ (&(amp;)?|\?)?~x', '', $location );
	}

	#header('Refresh:0;url='.$location);
	#exit();
	// fplanque> Note: I am not sure using this is cacheing safe: header('Location: '.$location);
	// Current "Refresh" version works fine.
	// Please provide link to relevant material before changing it.
	// blueyed>> The above method fails when you redirect after a POST to the same URL.
	//   Regarding http://de3.php.net/manual/en/function.header.php#50588 and the other comments
	//   around, I'd suggest:
	header( 'HTTP/1.1 303 See Other' ); // see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
	header( 'Location: '.$location );
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
function format_phone( $phone )
{

	if( ( $indic = substr( $phone, 0, 3 ) ) == '+33'  && strlen( $phone ) == 12 )
	{ // French number (+33x.xx.xx.xx.xx), so we can format it (xx.xx.xx.xx.xx):
		$phone_formated = format_french_phone( '0'.substr( $phone, 3, strlen( $phone)-3 ) );
	}
	elseif ( substr( $phone, 0 , 1 ) != '+' && strlen( $phone ) == 10  )
	{ // French number, so we can format it (xx.xx.xx.xx.xx):
		$phone_formated = format_french_phone( $phone );
	}
	else
	{ // unknown format, so don't change it:
		$phone_formated = $phone;
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
	global $admin_url, $ReqPath;

	// Note: on IIS you can receive 'off' in the HTTPS field!! :[
	$ReqHostPath = ( (isset($_SERVER['HTTPS']) && ( $_SERVER['HTTPS'] != 'off' ) ) ?'https://':'http://').$_SERVER['HTTP_HOST'].$ReqPath;

	return ( strpos( $ReqHostPath, $admin_url ) === 0 );
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


/*
 * $Log$
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
 *
 * Revision 1.165  2005/12/30 20:13:40  fplanque
 * UI changes mostly (need to double check sync)
 *
 * Revision 1.164  2005/12/21 20:39:04  fplanque
 * minor
 *
 * Revision 1.162  2005/12/12 19:21:22  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.161  2005/12/12 01:18:04  blueyed
 * Counter for $Timer; ignore absolute times below 0.005s; Fix for Timer::resume().
 *
 * Revision 1.160  2005/12/11 19:32:41  blueyed
 * Fixed make_clickable() for dots at the end of URLs.
 *
 * Revision 1.159  2005/12/11 19:19:53  blueyed
 * Fixed strange Parse error.
 *
 * Revision 1.158  2005/12/11 12:46:41  blueyed
 * Fix $log_head_links for front office (where we have <base>).
 *
 * Revision 1.157  2005/12/08 22:30:04  blueyed
 * Added 'alt' and 'class' to get_icon(); doc
 *
 * Revision 1.156  2005/12/06 22:08:26  blueyed
 * Fix validate_url() to allow "=" also before any "?" or "#". Fixes: http://forums.b2evolution.net/viewtopic.php?p=29817
 *
 * Revision 1.155  2005/12/05 12:15:32  fplanque
 * bugfix
 *
 * Revision 1.154  2005/12/01 19:32:15  blueyed
 * send_mail(): add X-Remote-Addr header to mails
 *
 * Revision 1.153  2005/12/01 19:29:50  blueyed
 * Renamed getIpList() to get_ip_list()
 *
 * Revision 1.152  2005/11/30 19:53:05  blueyed
 * Display a list of Debuglog categories with links to the categories messages html ID.
 *
 * Revision 1.151  2005/11/30 12:53:12  blueyed
 * make_clickable(): handle links that follow ">"..
 *
 * Revision 1.150  2005/11/24 08:44:01  blueyed
 * Debuglog
 *
 * Revision 1.149  2005/11/23 01:17:36  blueyed
 * valid html
 *
 * Revision 1.148  2005/11/22 16:56:31  blueyed
 * validate_url(): allow URLs that start with a digit after '//' (read: IP addresses).
 *
 * Revision 1.147  2005/11/21 18:17:26  blueyed
 * debug_die(): also display debug_info() on $debug (or $force param)
 *
 * Revision 1.146  2005/11/19 03:43:51  blueyed
 * html fix in debug_info()
 *
 * Revision 1.144  2005/11/18 18:32:42  fplanque
 * Fixed xmlrpc logging insanity
 * (object should have been passed by reference but you can't pass NULL by ref)
 * And the code was geeky/unreadable anyway.
 *
 * Revision 1.142  2005/11/17 17:19:38  blueyed
 * Ignore timers below 0.5% of total time
 *
 * Revision 1.141  2005/11/17 01:17:38  blueyed
 * Replaced main/sql-query times with dynamic timer table in debug_info()
 *
 * Revision 1.140  2005/11/16 01:47:09  blueyed
 * action_icon(): Add whitespace between icon and word.
 *
 * Revision 1.139  2005/11/16 00:58:03  blueyed
 * Tightened getIpList()
 *
 * Revision 1.138  2005/11/10 01:09:47  blueyed
 * header_redirect(): remove "user" and "login" params (additionally to "action") from location where we redirect to if it's on $baseurl.
 *
 * Revision 1.137  2005/11/09 02:54:42  blueyed
 * Moved inclusion of _file.funcs.php to _misc.funcs.php, because at least bytesreable() gets used in debug_info()
 *
 * Revision 1.136  2005/11/08 19:22:21  blueyed
 * Fixed link to #evo_debug_queries (using $ReqURI)
 *
 * Revision 1.135  2005/11/07 02:13:22  blueyed
 * Cleaned up Sessions and extended Widget etc
 *
 * Revision 1.134  2005/11/06 11:36:57  yabs
 * correcting windows farce
 *
 * Revision 1.133  2005/11/06 03:19:12  blueyed
 * Do not use third parameter for header(), as it requires PHP 4.3 and is not necessary.
 *
 * Revision 1.132  2005/11/05 08:11:00  blueyed
 * debug_info(): link to query log from number of queries at the top
 *
 * Revision 1.131  2005/11/05 07:22:09  blueyed
 * Display number of DB queries at the top of debug_info()
 *
 * Revision 1.130  2005/11/04 16:24:35  blueyed
 * Use $localtimenow instead of $servertimenow.
 *
 * Revision 1.129  2005/11/03 18:23:44  fplanque
 * minor
 *
 * Revision 1.128  2005/11/02 20:11:19  fplanque
 * "containing entropy"
 *
 * Revision 1.127  2005/11/02 13:05:51  halton
 * changed online help url back to /redirect.  added workaround for null $Blog object in regenerate_url
 *
 * Revision 1.126  2005/11/02 06:52:19  marian
 * changed regenerate_url to support multiple domains
 *
 * Revision 1.125  2005/11/01 23:57:24  blueyed
 * Changed header_redirect() to use HTTP 303 / Location, which allows to reload the same page after POSTing without being prompted for the browser's "page requires POSTed data" dialog.
 *
 * Revision 1.124  2005/10/31 11:50:46  halton
 * updated online help with subtle icon
 *
 * Revision 1.123  2005/10/31 08:19:07  blueyed
 * Refactored getRandomPassword() and Session::generate_key() into generate_random_key()
 *
 * Revision 1.122  2005/10/31 06:50:33  blueyed
 * send_mail(): Add X-b2evo to notice about email header injection fix
 *
 * Revision 1.121  2005/10/31 05:51:06  blueyed
 * Use rawurlencode() instead of urlencode()
 *
 * Revision 1.120  2005/10/31 02:20:49  blueyed
 * Added memory usage info to the top of debug_info()
 *
 * Revision 1.119  2005/10/30 11:16:43  marian
 * rollback of regenerate_url
 * fixing the form-problem in skins/_feedback.php
 *
 * Revision 1.117  2005/10/30 05:28:30  halton
 * updated get_web_help_link code to point to server
 *
 * Revision 1.116  2005/10/30 03:51:24  blueyed
 * Refactored showhide-JS functionality.
 * Moved showhide() from the features form to functions.js, and renamed to toggle_display_by_id();
 * Moved web_help_link() to get_web_help_link() in _misc.funcs.php, doc
 *
 * Revision 1.115  2005/10/28 20:26:43  blueyed
 * Handle failed update of antispam strings correctly.
 *
 * Revision 1.114  2005/10/27 15:25:03  fplanque
 * Normalization; doc; comments.
 *
 * Revision 1.113  2005/10/26 22:32:58  blueyed
 * debug_die(): added $very_last parameter that defaults to '</body></html>'
 *
 * Revision 1.112  2005/10/26 22:22:44  blueyed
 * debug_die(): $last_words is optional; doc
 *
 * Revision 1.111  2005/10/26 11:30:42  blueyed
 * Made $ignore_from case insensitive; slightly changed behaviour of $limit_to_last (only limit if numeric)
 *
 * Revision 1.110  2005/10/23 18:19:42  blueyed
 * Indent of make_clickable()
 *
 * Revision 1.109  2005/10/23 14:56:32  blueyed
 * is_email(): added $format parameter (defaults to 'single'). Formatted fixed email_pattern for rfc2822. Added test.
 *
 * Revision 1.108  2005/10/19 19:40:22  marian
 * small fix for pattern matching with validate_url
 *
 * Revision 1.107  2005/10/18 14:03:55  marian
 * changed pattern delimiter due to problems with PHP on Windows Machines
 *
 * Revision 1.106  2005/10/18 02:27:13  blueyed
 * Fixes to debug_get_backtrace()
 *
 * Revision 1.105  2005/10/18 02:04:21  blueyed
 * Tightened is_email(), allowing RFC2822 format, which includes "name <email@example.com>"; send_mail(): fix $from after injection fix, enhanced debugging
 *
 * Revision 1.104  2005/10/16 09:03:57  marian
 * Changed delimiter for preg_match because the old one did not work in the Windows environment.
 *
 * Revision 1.103  2005/10/14 21:00:08  fplanque
 * Stats & antispam have obviously been modified with ZERO testing.
 * Fixed a sh**load of bugs...
 *
 * Revision 1.102  2005/10/13 22:30:59  blueyed
 * Added debug_get_backtrace() and debug_die()
 *
 * Revision 1.101  2005/10/13 20:11:05  blueyed
 * Fixed send_mail()! added a funky regexp to validate emails according to rfc2822 (not activated).
 *
 * Revision 1.100  2005/10/12 21:14:17  fplanque
 * bugfixes
 *
 * Revision 1.99  2005/10/12 18:24:37  fplanque
 * bugfixes
 *
 * Revision 1.98  2005/10/11 20:36:38  fplanque
 * minor changes
 *
 * Revision 1.97  2005/10/11 18:29:56  fplanque
 * It is perfectly valid to have <strong> inside of <code>.
 * Make a plugin for [code]...[/code] if you want to escape tags arbitrarily.
 *
 * Revision 1.96  2005/10/09 23:22:09  blueyed
 * format_to_post(): Use preg_replace_callback() to avoid using stripslashes() in replacement function. Also fix regexp.
 *
 * Revision 1.95  2005/10/09 20:21:52  blueyed
 * format_to_output(): Automagically replace '<' and '>' in <code> and <pre> blocks.
 *
 * Revision 1.94  2005/10/09 19:31:15  blueyed
 * Spelling (*allowed_attribues => *allowed_attributes)
 *
 * Revision 1.93  2005/10/07 19:34:06  fplanque
 * javascript injection!!! damn it!!
 *
 * Revision 1.92  2005/10/06 21:04:33  blueyed
 * Made validate_url() more verbose - thanks to stk (http://forums.b2evolution.net/viewtopic.php?p=26984#26984)
 *
 * Revision 1.89  2005/09/20 23:23:56  blueyed
 * Added colorization of query durations (graph bar).
 *
 * Revision 1.88  2005/09/07 17:40:22  fplanque
 * enhanced antispam
 *
 * Revision 1.87  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.86  2005/09/02 21:31:34  fplanque
 * enhanced query debugging features
 *
 * Revision 1.85  2005/08/25 16:06:45  fplanque
 * Isolated compilation of categories to use in an ItemList.
 * This was one of the oldest bugs on the list! :>
 *
 * Revision 1.84  2005/08/24 18:43:09  fplanque
 * Removed public stats to prevent spamfests.
 * Added context browsing to Archives plugin.
 *
 * Revision 1.82  2005/08/22 18:05:46  fplanque
 * rollback of code plugin. This should be posted on plugins.b2evolution.net.
 * This should also be documented a little more.
 * Some other plugins will be removed too.
 *
 * Revision 1.79  2005/08/18 15:06:18  fplanque
 * got rid of format_to_edit(). This functionnality is being taken care of by the Form class.
 *
 * Revision 1.77  2005/08/08 22:50:42  blueyed
 * refactored xmlrpc_displayresult() to "display into" Log object.
 *
 * Revision 1.76  2005/08/08 18:30:50  fplanque
 * allow inserting of files as IMG or A HREFs from the filemanager
 *
 * Revision 1.75  2005/08/04 17:22:15  fplanque
 * better fix for "no linkblog": allow storage of NULL value.
 *
 * Revision 1.73  2005/07/26 18:57:34  fplanque
 * changed handling of empty params. We do need to differentiate between empty input ''=>NULL and 0=>0 in some situations!
 *
 * Revision 1.72  2005/07/12 23:05:36  blueyed
 * Added Timer class with categories 'main' and 'sql_queries' for now.
 *
 * Revision 1.71  2005/06/10 18:25:44  fplanque
 * refactoring
 *
 * Revision 1.70  2005/06/03 15:12:33  fplanque
 * error/info message cleanup
 *
 * Revision 1.68  2005/05/24 15:26:53  fplanque
 * cleanup
 *
 * Revision 1.67  2005/05/11 13:21:38  fplanque
 * allow disabling of mediua dir for specific blogs
 *
 * Revision 1.66  2005/04/28 20:44:20  fplanque
 * normalizing, doc
 *
 * Revision 1.65  2005/04/27 19:05:46  fplanque
 * normalizing, cleanup, documentaion
 *
 * Revision 1.62  2005/04/06 19:11:01  fplanque
 * refactored Results class:
 * all col params are now passed through a 2 dimensional table which allows easier parametering of large tables with optional columns
 *
 * Revision 1.61  2005/04/06 13:33:29  fplanque
 * minor changes
 *
 * Revision 1.60  2005/03/21 18:54:27  fplanque
 * results/table/form layout refactoring
 *
 * Revision 1.59  2005/03/15 19:19:48  fplanque
 * minor, moved/centralized some includes
 *
 * Revision 1.58  2005/03/14 20:22:20  fplanque
 * refactoring, some cacheing optimization
 *
 * Revision 1.57  2005/03/13 18:50:34  blueyed
 * use $servertimenow/$localtimenow
 *
 * Revision 1.56  2005/03/02 16:01:00  fplanque
 * comment
 *
 * Revision 1.55  2005/03/02 15:31:54  fplanque
 * minor cleanup
 *
 * Revision 1.54  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.53  2005/02/28 01:32:32  blueyed
 * Hitlog refactoring, part uno.
 *
 * Revision 1.52  2005/02/27 20:30:07  blueyed
 * added header_redirect()
 *
 * Revision 1.51  2005/02/24 17:02:23  blueyed
 * fixed getRandomPassword() for installation
 *
 * Revision 1.50  2005/02/23 23:11:54  blueyed
 * fixed autolinks for commata
 *
 * Revision 1.49  2005/02/23 22:47:08  blueyed
 * deprecated mysql_oops()
 *
 * Revision 1.48  2005/02/23 19:31:59  blueyed
 * get_weekstartend() fixed
 *
 * Revision 1.47  2005/02/23 04:26:18  blueyed
 * moved global $start_of_week into $locales properties
 *
 * Revision 1.46  2005/02/22 02:53:02  blueyed
 * typecasting also gives a notice.. better to have it anyway
 *
 * Revision 1.45  2005/02/22 02:44:50  blueyed
 * E_NOTICE fix for param()
 *
 * Revision 1.44  2005/02/20 23:09:04  blueyed
 * header_nocache() added, Debuglog-output: display 'note' after 'error'
 *
 * Revision 1.43  2005/02/19 22:41:32  blueyed
 * getRandomPassword() added; Debuglog enhanced for send_mail()
 *
 * Revision 1.42  2005/02/18 19:16:15  fplanque
 * started relation restriction/cascading handling
 *
 * Revision 1.40  2005/02/15 22:05:09  blueyed
 * Started moving obsolete functions to _obsolete092.php..
 *
 * Revision 1.39  2005/02/10 21:18:57  blueyed
 * getIpList() fixed
 *
 * Revision 1.38  2005/02/09 21:43:32  blueyed
 * introduced getIpList()
 *
 * Revision 1.36  2005/02/02 01:10:08  blueyed
 * added @ to mail() again
 *
 * Revision 1.35  2005/02/02 00:55:53  blueyed
 * send_mail() fixed/enhanced
 *
 * Revision 1.34  2005/01/28 19:28:03  fplanque
 * enhanced UI widgets
 *
 * Revision 1.33  2005/01/15 20:20:51  blueyed
 * $map_iconsizes merged with $map_iconfiles, removed obsolete getIconSize() (functionality moved to get_icon())
 *
 * Revision 1.32  2005/01/15 17:30:08  blueyed
 * regexp_fileman moved to $Settings
 *
 * Revision 1.31  2005/01/13 19:53:50  fplanque
 * Refactoring... mostly by Fabrice... not fully checked :/
 *
 * Revision 1.30  2005/01/09 23:41:54  blueyed
 * fixed format_to_post()
 *
 * Revision 1.29  2005/01/09 05:34:08  blueyed
 * emphasize pre_dump()
 *
 * Revision 1.28  2005/01/06 15:45:36  blueyed
 * Fixes..
 *
 * Revision 1.27  2005/01/06 10:15:46  blueyed
 * FM upload and refactoring
 *
 * Revision 1.26  2005/01/05 19:14:51  fplanque
 * rollback
 *
 * Revision 1.25  2005/01/05 02:52:37  blueyed
 * explicit $GLOBAL[] for param()
 *
 * Revision 1.24  2005/01/03 12:33:07  fplanque
 * extended datetime hadling
 *
 * Revision 1.23  2005/01/03 06:18:31  blueyed
 * changed pre_dump() syntax
 *
 * Revision 1.22  2004/12/30 23:58:41  blueyed
 * <br> -> <br />
 *
 * Revision 1.21  2004/12/30 22:56:58  blueyed
 * doc
 *
 * Revision 1.19  2004/12/27 18:37:58  fplanque
 * changed class inheritence
 *
 * Revision 1.17  2004/12/17 20:41:14  fplanque
 * cleanup
 *
 * Revision 1.16  2004/12/15 01:01:33  blueyed
 * improved date_i18n's performance, especially when no special strings are used
 *
 * Revision 1.15  2004/12/14 18:32:15  fplanque
 * quick optimizations
 *
 * Revision 1.13  2004/11/22 17:48:20  fplanque
 * skin cosmetics
 *
 * Revision 1.12  2004/11/15 18:57:05  fplanque
 * cosmetics
 *
 * Revision 1.11  2004/11/10 22:46:44  blueyed
 * translation adjustments
 *
 * Revision 1.9  2004/11/05 12:48:04  blueyed
 * Debug output beautified
 *
 * Revision 1.7  2004/11/03 00:58:02  blueyed
 * update
 *
 * Revision 1.5  2004/10/21 00:14:44  blueyed
 * moved
 *
 * Revision 1.4  2004/10/18 18:34:51  fplanque
 * modified date functions
 *
 * Revision 1.3  2004/10/15 15:38:52  fplanque
 * added action_icon()
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.134  2004/10/12 17:22:29  fplanque
 * Edited code documentation.
 *
 * Revision 1.92  2004/5/28 17:21:42  jeffbearer
 * added function include for the who's online functions
 *
 * Revision : 1.27  2004/2/8 23:20:27  vegarg
 * Bugfix in the security checker. (contrib by topolino)
 *
 * Revision 1.24  2004/1/16 14:15:55  vegarg
 * Modified validate_url(), switched to MySQL antispam table.
 *
 * Revision 1.7  2003/8/29 18:25:51  sakichan
 * SECURITY: XSS vulnerability fix.
 *
 * Revision 1.1.1.1.2.1  2003/8/31 6:23:31  sakichan
 * Security fixes for various XSS vulnerability and SQL injection vulnerability
 */
?>