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
 *
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * @todo dh> Refactor into smaller chunks/files. We should avoid using a "huge" misc early!
 *       - _debug.funcs.php
 *       - _formatting.funcs.php
 *       - _date.funcs.php
 *       - ?
 *       NOTE: Encapsulation functions into classes would allow using autoloading (http://php.net/autoload) in PHP5..!
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
 * - text: use as plain-text, e.g. for ascii-mails
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

		case 'text':
			// use as plain-text, e.g. for ascii-mails
			$content = strip_tags( $content );
			$trans_tbl = get_html_translation_table( HTML_ENTITIES );
			$trans_tbl = array_flip( $trans_tbl );
			$content = strtr( $content, $trans_tbl );
			$content = preg_replace( '/[ \t]+/', ' ', $content);
			$content = trim($content);
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
	global $Messages;

	// Replace any & that is not a character or entity reference with &amp;
	$content = preg_replace( '/&(?!#[0-9]+;|#x[0-9a-fA-F]+;|[a-zA-Z_:][a-zA-Z0-9._:-]*;)/', '&amp;', $content );

	if( $autobr )
	{ // Auto <br />:
		// may put brs in the middle of multiline tags...
		// TODO: this may create "<br />" tags in "<UL>" (outside of <LI>) and make the HTML invalid!
		$content = autobrize($content);
	}

	if( $use_balanceTags )
	{ // Auto close open tags:
		$content = balanceTags($content, $is_comment);
	}

	if( $use_html_checker )
	{ // Check the code:
		load_class( '_misc/_htmlchecker.class.php' );

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


/**
 * Convert all non ASCII chars (except if UTF-8, GB2312 or CP1251) to &#nnnn; unicode references.
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
	if( ! in_array($evo_charset, array('utf-8', 'gb2312', 'windows-1251') ) )
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

function mysql2localetime( $mysqlstring )
{
	return mysql2date( locale_timefmt(), $mysqlstring );
}

function mysql2localedatetime( $mysqlstring )
{
	return mysql2date( locale_datefmt().' '.locale_timefmt(), $mysqlstring );
}

function mysql2localedatetime_spans( $mysqlstring )
{
	return '<span class="date">'
					.mysql2date( locale_datefmt(), $mysqlstring )
					.'</span> <span class="time">'
					.mysql2date( locale_timefmt(), $mysqlstring )
					.'</span>';
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
	global $localtimenow, $time_difference;

	if( $dateformatstring == 'isoZ' )
	{ // full ISO 8601 format
		$dateformatstring = 'Y-m-d\TH:i:s\Z';
	}

	if( $useGM )
	{ // We want a Greenwich Meridian time:
		$r = gmdate($dateformatstring, ($unixtimestamp - $time_difference));
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
	return ( strtoupper(substr(PHP_OS,0,3)) == 'WIN' );
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
 * @deprecated by xmlrpc_getpostcategories()
 */
function xmlrpc_getpostcategory($content)
{
	if (preg_match('/<category>([0-9]+?)<\/category>/is', $content, $matchcat))
	{
		return $matchcat[1];
	}

	return false;
}


/**
 * Extract categories out of "<category>" tag from $content.
 *
 * NOTE: w.bloggar sends something like "<category>00000013,00000001,00000004,</category>" to
 *       blogger.newPost.
 *
 * @return false|array
 */
function xmlrpc_getpostcategories($content)
{
	if( preg_match('~<category>(\d+\s*(,\s*\d*)*)</category>~i', $content, $match) )
	{
		$cats = preg_split('~\s*,\s*~', $match[1], -1, PREG_SPLIT_NO_EMPTY);
		foreach( $cats as $k => $v )
		{
			$cats[$k] = (int)$v;
		}
		return $cats;
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
 * @return boolean true = success, false = error
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

	while( preg_match('~<(\s*/?\w+)\s*([^>]*)>~', $text, $regex) )
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
 * Wrap pre tag around {@link var_dump()} for better debugging.
 *
 * @param,... mixed variable(s) to dump
 */
function pre_dump( $var__var__var__var__ )
{
	global $is_cli;

	#echo 'pre_dump(): '.debug_get_backtrace(); // see where a pre_dump() comes from

	$func_num_args = func_num_args();
	$count = 0;

	if( ! empty($is_cli) )
	{ // CLI, no encoding of special chars:
		$count = 0;
		foreach( func_get_args() as $lvar )
		{
			var_dump($lvar);

			$count++;
			if( $count < $func_num_args )
			{ // Put newline between arguments
				echo "\n";
			}
		}
	}
	elseif( function_exists('xdebug_var_dump') )
	{ // xdebug already does fancy displaying:
		ini_set('xdebug.var_display_max_depth', 10);
		echo "\n<div style=\"padding:1ex;border:1px solid #00f;\">\n";
		foreach( func_get_args() as $lvar )
		{
			xdebug_var_dump($lvar);

			$count++;
			if( $count < $func_num_args )
			{ // Put HR between arguments
				echo "<hr />\n";
			}
		}
		echo '</div>';
	}
	else
	{
		$orig_html_errors = ini_set('html_errors', 0); // e.g. xdebug would use fancy html, if this is on; we catch (and use) xdebug explicitly above, but just in case

		echo "\n<pre style=\"padding:1ex;border:1px solid #00f;\">\n";
		foreach( func_get_args() as $lvar )
		{
			ob_start();
			var_dump($lvar); // includes "\n"; do not use var_export() because it does not detect recursion by design
			$buffer = ob_get_contents();
			ob_end_clean();
			echo htmlspecialchars($buffer);

			$count++;
			if( $count < $func_num_args )
			{ // Put HR between arguments
				echo "<hr />\n";
			}
		}
		echo "</pre>\n";
		ini_set('html_errors', $orig_html_errors);
	}
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
	global $log_app_errors, $app_name, $is_cli;

	// Attempt to output an error header (will not work if the output buffer has already flushed once):
	// This should help preventing indexing robots from indexing the error :P
	if( ! headers_sent() )
	{
		global $io_charset;
		header('Content-type: text/html; charset='.$io_charset); // it's ok, if a previous header would be replaced;
		header('HTTP/1.0 500 Internal Server Error');
	}

	if( $is_cli )
	{ // Command line interface, e.g. in cron_exec.php:
		echo '== '.T_('An unexpected error has occured!')." ==\n";
		echo T_('If this error persits, please report it to the administrator.')."\n";
		echo T_('Additional information about this error:')."\n";
		echo strip_tags( $additional_info );
	}
	else
	{
		echo '<div style="background-color: #fdd; padding: 1ex; margin-bottom: 1ex;">';
		echo '<h3 style="color:#f00;">'.T_('An unexpected error has occured!').'</h3>';
		echo '<p>'.T_('If this error persits, please report it to the administrator.').'</p>';
		echo '<p><a href="'.$baseurl.'">'.T_('Go back to home page').'</a></p>';
		echo '</div>';

		if( ! empty( $additional_info ) )
		{
			echo '<div style="background-color: #ddd; padding: 1ex; margin-bottom: 1ex;">';
			echo '<h3>'.T_('Additional information about this error:').'</h3>';
			echo $additional_info;
			echo '</div>';
		}
	}

	if( $log_app_errors > 1 || $debug )
	{ // Prepare backtrace
		$backtrace = debug_get_backtrace();

		if( $log_app_errors > 1 || $is_cli )
		{
			$backtrace_cli = trim(strip_tags($backtrace));
		}
	}

	if( $log_app_errors )
	{ // Log error through PHP's logging facilities:
		$log_message = $app_name.' error: ';
		if( ! empty($additional_info) )
		{
			$log_message .= trim( strip_tags($additional_info) );
		}
		else
		{
			$log_message .= 'No info specified in debug_die()';
		}

		// Get file and line info:
		$file = 'Unknown';
		$line = 'Unknown';
		if( function_exists('debug_backtrace') /* PHP 4.3 */ )
		{ // get the file and line
			foreach( debug_backtrace() as $v )
			{
				if( isset($v['function']) && $v['function'] == 'debug_die' )
				{
					$file = isset($v['file']) ? $v['file'] : 'Unknown';
					$line = isset($v['line']) ? $v['line'] : 'Unknown';
					break;
				}
			}
		}
		$log_message .= ' in '.$file.' at line '.$line;

		if( $log_app_errors > 1 )
		{ // Append backtrace:
			// indent after newlines:
			$backtrace_cli = preg_replace( '~(\S)(\n)(\S)~', '$1  $2$3', $backtrace_cli );
			$log_message .= "\nBacktrace:\n".$backtrace_cli;
		}
		$log_message .= "\n".$_SERVER['REQUEST_URI'].' ('.( isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '-' ).')';

		error_log( str_replace("\n", ' / ', $log_message), 0 /* PHP's system logger */ );
	}


	// DEBUG OUTPUT:
	if( $debug )
	{
		if( $is_cli )
			echo $backtrace_cli;
		else
			echo $backtrace;

		debug_info();
	}

	// EXIT:
	if( ! $is_cli )
	{ // Attempt to keep the html valid (but it doesn't really matter anyway)
		echo '</body></html>';
	}
	die();
}


/**
 * Outputs Bad request Error message. When in debug mode it also prints a backtrace.
 *
 * This should be used when a bad user input is detected?
 *
 * @param string Message to output
 */
function bad_request_die( $additional_info = '' )
{
	global $debug, $baseurl;

	// Attempt to output an error header (will not work if the output buffer has already flushed once):
	// This should help preventing indexing robots from indexing the error :P
	if( ! headers_sent() )
	{
		global $io_charset;
		header('Content-type: text/html; charset='.$io_charset); // it's ok, if a previous header would be replaced;
		header('HTTP/1.0 400 Bad Request');
	}

	echo '<div style="background-color: #fdd; padding: 1ex; margin-bottom: 1ex;">';
	echo '<h3 style="color:#f00;">'.T_('Bad Request!').'</h3>';
	echo '<p>'.T_('The parameters of your request are invalid.').'</p>';
	echo '<p>'.T_('If you have obtained this error by clicking on a link INSIDE of this site, please report the bad link to the administrator.').'</p>';
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
 * @todo dh> add get_debug_info() which returns and supports non-html format (for debug_die())
 * @param boolean true to force output regardless of {@link $debug}
 */
function debug_info( $force = false )
{
	global $debug, $Debuglog, $DB, $obhandler_debug, $Timer, $ReqHost, $ReqPath;
	global $cache_imgsize, $cache_File;
	global $Session;

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
		// arsort( $timer_rows );
		ksort( $timer_rows );
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
			'memory_get_peak_usage' /* PHP 5.2 */ => array( 'display' => 'Memory peak usage', 'high' => 8000000 ) ) as $l_func => $l_var )
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


	// DEBUGLOG(s) FROM PREVIOUS SESSIONS, after REDIRECT(s) (with list of categories at top):
	if( isset($Session) && ($sess_Debuglogs = $Session->get('Debuglogs')) && ! empty($sess_Debuglogs) )
	{
		$count_sess_Debuglogs = count($sess_Debuglogs);
		if( $count_sess_Debuglogs > 1 )
		{ // Links to those Debuglogs:
			echo '<p>There are '.$count_sess_Debuglogs.' Debuglogs from redirected pages: ';
			for( $i = 1; $i <= $count_sess_Debuglogs; $i++ )
			{
				echo '<a href="'.$ReqHostPathQuery.'#debug_sess_debuglog_'.$i.'">#'.$i.'</a> ';
			}
			echo '</p>';
		}

		foreach( $sess_Debuglogs as $k => $sess_Debuglog )
		{
			$log_categories = array( 'error', 'note', 'all' ); // Categories to output (in that order)
			$log_cats = array_keys($sess_Debuglog->get_messages( $log_categories )); // the real list (with all replaced and only existing ones)
			$log_container_head = '<h3 id="debug_sess_debuglog_'.($k+1).'" style="color:#f00;">Debug messages from redirected page (#'.($k+1).')</h3>'
				// link to real Debuglog:
				.'<p><a href="'.$ReqHostPathQuery.'#debug_debuglog">See below for the Debuglog from the current request.</a></p>';
			$log_head_links = array();
			foreach( $log_cats as $l_cat )
			{
				$log_head_links[] .= '<a href="'.$ReqHostPathQuery.'#debug_redir_'.($k+1).'_info_cat_'.str_replace( ' ', '_', $l_cat ).'">'.$l_cat.'</a>';
			}
			$log_container_head .= implode( ' | ', $log_head_links );
			echo format_to_output(
				$sess_Debuglog->display( array(
						'container' => array( 'string' => $log_container_head, 'template' => false ),
						'all' => array( 'string' => '<h4 id="debug_redir_'.($k+1).'_info_cat_%s">%s:</h4>', 'template' => false ) ),
					'', false, $log_categories ),
				'htmlbody' );
		}

		$Session->delete( 'Debuglogs' );
	}


	// CURRENT DEBUGLOG (with list of categories at top):
	$log_categories = array( 'error', 'note', 'all' ); // Categories to output (in that order)
	$log_cats = array_keys($Debuglog->get_messages( $log_categories )); // the real list (with all replaced and only existing ones)
	$log_container_head = '<h3 id="debug_debuglog">Debug messages</h3>';
	if( ! empty($sess_Debuglogs) )
	{ // link to first sess_Debuglog:
		$log_container_head .= '<p><a href="'.$ReqHostPathQuery.'#debug_sess_debuglog_1">See above for the Debuglog(s) from before the redirect.</a></p>';
	}
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
 * Sends a mail, wrapping PHP's mail() function.
 *
 * {@link $current_locale} will be used to set the charset.
 *
 * Note: we use a single \n as line ending, though it does not comply to
 * {@link http://www.faqs.org/rfcs/rfc2822 RFC2822}, but seems to be safer,
 * because some mail transfer agents replace \n by \r\n automatically.
 *
 * @todo Unit testing with "nice addresses" This gets broken over and over again.
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

	if( is_windows() )
	{	// fplanque: Windows XP, Apache 1.3, PHP 4.4, MS SMTP : will not accept "nice" addresses.
		$to = preg_replace( '/^.*?<(.+?)>$/', '$1', $to );
		$from = preg_replace( '/^.*?<(.+?)>$/', '$1', $from );
	}

	// echo 'sending email to: ['.htmlspecialchars($to).'] from ['.htmlspecialchars($from).']';

	if( !is_array( $headers ) )
	{ // Make sure $headers is an array
		$headers = array( $headers );
	}

	// Specify charset and content-type of email
	$headers['Content-Type'] = 'text/plain; charset='.$current_charset;
	$headers['X-Mailer'] = $app_name.' '.$app_version.' - PHP/'.phpversion();
	$headers['X-Remote-Addr'] = implode( ',', get_ip_list() );

	// -- Build headers ----
	if( empty($from) )
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
	$message = convert_charset( $message, $current_charset, $evo_charset );

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
			$Debuglog->add( 'Sending mail from &laquo;'.htmlspecialchars($from).'&raquo; to &laquo;'.htmlspecialchars($to).'&raquo;, Subject &laquo;'.htmlspecialchars($subject).'&raquo; FAILED.', 'error' );
			return false;
		}
	}

	$Debuglog->add( 'Sent mail from &laquo;'.htmlspecialchars($from).'&raquo; to &laquo;'.htmlspecialchars($to).'&raquo;, Subject &laquo;'.htmlspecialchars($subject).'&raquo;.' );
	return true;
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
 * @param string icon code for {@link get_icon()}
 * @param string URL where the icon gets linked to (empty to not wrap the icon in a link)
 * @param string word to be displayed after icon
 * @param integer 1-5: weight of the icon. the icon will be displayed only if its weight is >= than the user setting threshold
 *                     Use 5, if it's a required icon - all others could get disabled by the user.
 * @param integer 1-5: weight of the word. the word will be displayed only if its weight is >= than the user setting threshold
 * @param array Additional attributes to the A tag. The values must be properly encoded for html output (e.g. quotes).
 *        It may also contain these params:
 *         - 'use_js_popup': if true, the link gets opened as JS popup. You must also pass an "id" attribute for this!
 *         - 'use_js_size': use this to override the default popup size ("500, 400")
 *         - 'class': defaults to 'action_icon', if not set; use "" to not use it
 * @return string The generated action icon link.
 */
function action_icon( $title, $icon, $url, $word = NULL, $icon_weight = 4, $word_weight = 1, $link_attribs = array() )
{
	global $UserSettings;

	$link_attribs['href'] = $url;
	$link_attribs['title'] = $title;

	if( ! isset($link_attribs['class']) )
	{
		$link_attribs['class'] = 'action_icon';
	}

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
		$popup_js = 'var win = new PopupWindow(); win.setUrl( \''.$link_attribs['href'].'\' ); win.setSize(  ); ';

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
			$popup_js .= 'win.setSize( '.$popup_size.' ); ';
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
 * @uses get_icon_info()
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
	global $basepath, $admin_subdir, $baseurl, $Debuglog,	$IconLegend;
	global $conf_path;

	if( ! function_exists('get_icon_info') )
	{
		require_once $conf_path.'_icons.php';
	}

	$icon = get_icon_info($iconKey);
	if( ! $icon || ! isset( $icon['file'] ) )
	{
		return '[no image defined for '.var_export( $iconKey, true ).'!]';
	}

	switch( $what )
	{
		case 'rollover':
			if( isset( $icon['rollover'] ) )
			{	// Image has rollover available
				return $icon['rollover'];
			}
			return false;
			/* BREAK */


		case 'file':
			return $basepath.$icon['file'];
			/* BREAK */


		case 'alt':
			if( isset( $icon['alt'] ) )
			{ // alt tag from $map_iconfiles
				return $icon['alt'];
			}
			else
			{ // fallback to $iconKey as alt-tag
				return $iconKey;
			}
			/* BREAK */


		case 'legend':
			if( isset( $icon['legend'] ) )
			{ // legend tag from $map_iconfiles
				return $icon['legend'];
			}
			else
			if( isset( $icon['alt'] ) )
			{ // alt tag from $map_iconfiles
				return $icon['alt'];
			}
			else
			{ // fallback to $iconKey as alt-tag
				return $iconKey;
			}
			/* BREAK */


		case 'class':
			if( isset($icon['class']) )
			{
				return $icon['class'];
			}
			else
			{
				return '';
			}
			/* BREAK */

		case 'url':
			return $baseurl.$icon['file'];
			/* BREAK */

		case 'size':
			if( !isset( $icon['size'] ) )
			{
				$Debuglog->add( 'No iconsize for ['.$iconKey.']', 'icons' );

				$icon['size'] = imgsize( $icon['file'] );
			}

			switch( $params['size'] )
			{
				case 'width':
					return $icon['size'][0];

				case 'height':
					return $icon['size'][1];

				case 'widthxheight':
					return $icon['size'][0].'x'.$icon['size'][1];

				case 'width':
					return $icon['size'][0];

				case 'string':
					return 'width="'.$icon['size'][0].'" height="'.$icon['size'][1].'"';

				default:
					return $icon['size'];
			}
			/* BREAK */


		case 'imgtag':
			$r = '<img src="'.$baseurl.$icon['file'].'" ';

			// Include non CSS fallbacks:
			$r .= 'border="0" align="top" ';

			// Include class (will default to "icon"):
			if( ! isset( $params['class'] ) )
			{
				if( isset($icon['class']) )
				{	// This icon has a class
					$params['class'] = $icon['class'];
				}
				else
				{
					$params['class'] = '';
				}
			}

			// Include size (optional):
			if( isset( $icon['size'] ) )
			{
				$r .= 'width="'.$icon['size'][0].'" height="'.$icon['size'][1].'" ';
			}

			// Include alt (XHTML mandatory):
			if( ! isset( $params['alt'] ) )
			{
				if( isset( $icon['alt'] ) )
				{ // alt-tag from $map_iconfiles
					$params['alt'] = $icon['alt'];
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

		case 'noimg':
			$blank_icon = get_icon_info('pixel');

			$r = '<img src="'.$baseurl.$blank_icon['file'].'" ';

			// Include non CSS fallbacks:
			$r .= 'border="0" align="top" ';

			// Include class (will default to "noicon"):
			if( ! isset( $params['class'] ) )
			{
				if( isset($icon['class']) )
				{	// This icon has a class
					$params['class'] = $icon['class'];
				}
				else
				{
					$params['class'] = 'no_icon';
				}
			}

			// Include size (optional):
			if( isset( $icon['size'] ) )
			{
				$r .= 'width="'.$icon['size'][0].'" height="'.$icon['size'][1].'" ';
			}

			// Include alt (XHTML mandatory):
			if( ! isset( $params['alt'] ) )
			{
				$params['alt'] = '';
			}

			// Add all the attributes:
			$r .= get_field_attribs_as_string( $params, false );

			// Close tag:
			$r .= '/>';

			return $r;
			/* BREAK */

	}
}


/**
 * @param string date (YYYY-MM-DD)
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
 * Get the base domain (without protocol and any subdomain) of an URL.
 *
 * Gets a max of 3 domain parts (x.y.tld)
 *
 * @param string URL
 * @return string the base domain (may become empty, if found invalid)
 */
function get_base_domain( $url )
{
	//echo '<p>'.$url;
	// Chop away the http part and the path:
	$domain = preg_replace( '~^([a-z]+://)?([^:/#]+)(.*)$~i', '\\2', $url );

	if( empty($domain) || preg_match( '~^([0-9]+\.)+$~', $domain ) )
	{	// Empty or All numeric = IP address, don't try to cut it any further
		return $domain;
	}

	//echo '<br>'.$domain;

	// Get the base domain up to 3 levels (x.y.tld):
	// NOTE: "_" is not really valid, but for Windows it is..
	// TODO: dh> this should also handle IDN "raw" domains with umlauts..
	// NOTE: \w does not always match umlauts.. it's based on setlocale().. (fp> hum, it was too good to be real :P)
	// NOTE: \w includes "_"
	if( ! preg_match( '~  ( \w (\w|-|_)* \. ){0,2}   \w (\w|-|_)*  $~ix', $domain, $matches ) )
	{
		return '';
	}
	$base_domain = $matches[0];

	// Remove any www*. prefix:
	$base_domain = preg_replace( '~^(www (\w|-|_)* \. )~xi', '', $base_domain );

	//echo '<br>'.$base_domain.'</p>';

	return $base_domain;
}


/**
 * Generate a valid key of size $length.
 *
 * @param integer length of key
 * @param string chars to use in generated key
 * @return string key
 */
function generate_random_key( $length = 32, $keychars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' )
{
	$key = '';
	$rnd_max = strlen($keychars) - 1;

	for( $i = 0; $i < $length; $i++ )
	{
		$key .= $keychars{mt_rand(0, $rnd_max)}; // get a random character out of $keychars
	}

	return $key;
}


/**
 * Generate a random password with no ambiguous chars
 *
 * @param integer length of password
 * @return string password
 */
function generate_random_passwd( $length = 8 )
{
	// fp> NOTE: do not include any characters that would make autogenerated passwords ambiguous
	// 1 (one) vs l (L) vs I (i)
	// O (letter) vs 0 (digit)

	return generate_random_key( $length, 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789' );
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
 * {@link $Debuglog} and {@link $Messages} get stored in {@link $Session}, so they
 * are available after the redirect.
 *
 * NOTE: This function {@link exit() exits} the php script execution.
 *
 * @todo fp> do NOT allow $redirect_to = NULL. This leads to spaghetti code and unpredictable behavior.
 *
 * @param string URL to redirect to (overrides detection)
 * @param boolean is this a permanent redirect? if true, send a 301; otherwise a 303
 */
function header_redirect( $redirect_to = NULL, $permanent = false )
{
	global $Hit, $baseurl, $Blog, $htsrv_url_sensitive;
	global $Session, $Debuglog, $Messages;

	// fp> get this out
	if( empty($redirect_to) )
	{ // see if there's a redirect_to request param given (where & is encoded as &amp;):
		$redirect_to = param( 'redirect_to', 'string', '' );

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
	}
	// <fp

	/* fp>why do we need this?
	if( substr($redirect_to, 0, 1) == '/' )
	{ // relative URL, prepend current host:
		global $ReqHost;

		$redirect_to = $ReqHost.$redirect_to;
	}
	*/

	if( strpos($redirect_to, $htsrv_url_sensitive) === 0 /* we're going somewhere on $htsrv_url_sensitive */
	 || strpos($redirect_to, $baseurl) === 0   /* we're going somewhere on $baseurl */ )
	{
		// Remove login and pwd parameters from URL, so that they do not trigger the login screen again:
		// Also remove "action" get param to avoid unwanted actions
		// blueyed> Removed the removing of "action" here, as it is used to trigger certain views. Instead, "confirm(ed)?" gets removed now
		// fp> which views please (important to list in order to remove asap)
		// dh> sorry, don't remember
		// TODO: fp> action should actually not be used to trigger views. This should be changed at some point.
		$redirect_to = preg_replace( '~(?<=\?|&) (login|pwd|confirm(ed)?) = [^&]+ ~x', '', $redirect_to );
	}


	$status = $permanent ? 301 : 303;
 	$Debuglog->add('Redirecting to '.$redirect_to.' (status '.$status.')');

	// Transfer of Debuglog to next page:
	if( $Debuglog->count('all') )
	{ // Save Debuglog into Session, so that it's available after redirect (gets loaded by Session constructor):
		$sess_Debuglogs = $Session->get('Debuglogs');
		if( empty($sess_Debuglogs) )
			$sess_Debuglogs = array();

		$sess_Debuglogs[] = $Debuglog;
		$Session->set( 'Debuglogs', $sess_Debuglogs, 60 /* expire in 60 seconds */ );
	}

	// Transfer of Messages to next page:
	if( $Messages->count('all') )
	{ // Set Messages into user's session, so they get restored on the next page (after redirect):
		$Session->set( 'Messages', $Messages );
	}

	$Session->dbsave(); // If we don't save now, we run the risk that the redirect goes faster than the PHP script shutdown.

 	// see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
	if( $permanent )
	{	// This should be a permanent move redirect!
		header( 'HTTP/1.1 301 Moved Permanently' );
	}
	else
	{	// This should be a "follow up" redirect
		// Note: Also see http://de3.php.net/manual/en/function.header.php#50588 and the other comments around
		header( 'HTTP/1.1 303 See Other' );
	}

	header( 'Location: '.$redirect_to, true, $status ); // explictly setting the status is required for (fast)cgi
	exit();
}


function is_create_action( $action )
{
	$action_parts = explode( '_', $action );

	switch( $action_parts[0] )
	{
		case 'new':
		case 'new_switchtab':
		case 'copy':
		case 'create':	// we return in this state after a validation error
			return true;

		case 'edit':
		case 'edit_switchtab':
		case 'update':	// we return in this state after a validation error
		case 'delete':
		// The following one's a bit far fetched, but can happen if we have no sheet display:
		case 'unlink':
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
function implode_with_and( $arr, $implode_by = ', ', $implode_last = ' &amp; ' )
{
	switch( count($arr) )
	{
		case 0:
			return '';

		case 1:
			$r = array_shift($arr);
			return $r;

		default:
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
function base_tag( $url, $target = NULL )
{
	global $base_tag_set;

	$base_tag_set = true;
	echo '<base href="'.$url.'"';

	if( !empty($target) )
	{
		echo ' target="'.$target.'"';
	}
	echo ' />';
}


/**
 * Display an array as a list:
 */
function display_list( $items, $list_start = '<ul>', $list_end = '</ul>', $item_separator = '', $item_start = '<li>', $item_end = '</li>' )
{
	if( !empty( $items ) )
	{
		echo $list_start;
		$first = true;
		foreach( $items as $item )
		{
			if( $first )
			{
				$first = false;
			}
			else
			{
				echo $item_separator;
			}
			echo $item_start;
			if( is_array( $item ) )
			{
				echo '<a href="'.$item[0].'" target="_blank">'.$item[1].'</a>';
			}
			else
			{
				echo $item;
			}
			echo $item_end;
		}
		echo $list_end;
	}
}


/**
 * Try to make $url relative to $target_url, if scheme, host, user and pass matches.
 *
 * This is useful for redirect_to params, to keep them short and avoid mod_security
 * rejecting the request as "Not Acceptable" (whole URL as param).
 *
 * @param string URL to handle
 * @param string URL where we want to make $url relative to
 * @return string
 */
function url_rel_to_same_host( $url, $target_url )
{
	$parsed_url = @parse_url( $url );
	if( ! $parsed_url )
	{ // invalid url
		return $url;
	}
	if( empty($parsed_url['scheme']) || empty($parsed_url['host']) )
	{ // no protocol or host information
		return $url;
	}

	$target_url = @parse_url( $target_url );
	if( ! $target_url )
	{ // invalid url
		return $url;
	}
	if( ! empty($target_url['scheme']) && $target_url['scheme'] != $parsed_url['scheme'] )
	{ // scheme/protocol is different
		return $url;
	}
	if( ! empty($target_url['host']) )
	{
		if( empty($target_url['scheme']) || $target_url['host'] != $parsed_url['host'] )
		{ // target has no scheme (but a host) or hosts differ
			return $url;
		}

		if( @$target_url['port'] != @$parsed_url['port'] )
			return $url;
		if( @$target_url['user'] != @$parsed_url['user'] )
			return $url;
		if( @$target_url['pass'] != @$parsed_url['pass'] )
			return $url;
	}

	// We can make the URL relative:
	$r = '';
	if( ! empty($parsed_url['path']) )
		$r .= $parsed_url['path'];
	if( ! empty($parsed_url['query']) )
		$r .= '?'.$parsed_url['query'];
	if( ! empty($parsed_url['fragment']) )
		$r .= '?'.$parsed_url['fragment'];

	return $r;
}


/**
 * Make an $url absolute according to $host, if it is not absolute yet.
 *
 * @param string URL
 * @param string Host (including protocol, e.g. 'http://example.com'); defaults to {@link $ReqHost}
 * @return string
 */
function url_absolute( $url, $host = NULL )
{
	if( preg_match( '~^\w+://~', $url ) )
	{ // URL is relative already:
		return $url;
	}

	if( empty($host) )
	{
		global $ReqHost;
		$host = $ReqHost;
	}
	return $host.$url;
}


/**
 * Make links in $s absolute.
 *
 * It searches for "src" and "href" HTML tag attributes and makes the absolute.
 *
 * @uses url_absolute()
 * @param string content
 * @param string Hostname including scheme, e.g. http://example.com; defaults to $ReqHost
 * @return string
 */
function make_rel_links_abs( $s, $host = NULL )
{
	if( empty($host) )
	{
		global $ReqHost;
		$host = $ReqHost;
	}

	$s = preg_replace_callback( '~(<[^>]+?)\b((?:src|href)\s*=\s*)(["\'])?([^\\3]+?)(\\3)~i', create_function( '$m', '
		return $m[1].$m[2].$m[3].url_absolute($m[4], "'.$host.'").$m[5];' ), $s );
	return $s;
}


/*
 * $Log$
 * Revision 1.167  2007/01/29 09:58:55  fplanque
 * enhanced toolbar - experimental
 *
 * Revision 1.166  2007/01/29 09:24:41  fplanque
 * icon stuff
 *
 * Revision 1.165  2007/01/26 20:43:03  blueyed
 * Fixed exp. app error logging
 *
 * Revision 1.164  2007/01/26 04:52:53  fplanque
 * clean comment popups (skins 2.0)
 *
 * Revision 1.163  2007/01/24 01:32:30  fplanque
 * 'zip & css' is more readable than 'zip and css'
 *
 * Revision 1.162  2007/01/23 22:23:04  fplanque
 * FIXED (!!!) disappearing help window!
 *
 * Revision 1.161  2007/01/23 21:44:43  fplanque
 * handle generic "empty"/noimg icons
 *
 * Revision 1.160  2007/01/23 05:30:21  fplanque
 * "Contact the owner"
 *
 * Revision 1.159  2007/01/20 00:38:19  blueyed
 * Added Debulog entry in header_redirect()
 *
 * Revision 1.158  2007/01/19 03:06:57  fplanque
 * Changed many little thinsg in the login procedure.
 * There may be new bugs, sorry. I tested this for several hours though.
 * More refactoring to be done.
 *
 * Revision 1.157  2007/01/14 05:41:10  blueyed
 * Send correct charset with bad_request_die()
 *
 * Revision 1.156  2006/12/19 17:21:54  blueyed
 * Fixed domain extraction if anchor (#) follows domain name directly. See http://forums.b2evolution.net/viewtopic.php?p=48672#48672
 *
 * Revision 1.155  2006/12/14 00:42:04  fplanque
 * A little bit of windows detection / normalization
 *
 * Revision 1.154  2006/12/12 23:23:30  fplanque
 * finished post editing v2.0
 *
 * Revision 1.153  2006/12/05 06:22:24  blueyed
 * Fixed blogger.newPost to accept a list of categories, as given by w.bloggar
 *
 * Revision 1.152  2006/12/02 17:48:31  blueyed
 * Fixed regexp for balanceTags(), so it does not handle HTML comments as tags
 *
 * Revision 1.151  2006/12/02 17:32:26  blueyed
 * Missing "global $Messages" in format_to_post()
 *
 * Revision 1.150  2006/11/29 20:48:46  blueyed
 * Moved url_rel_to_same_host() from _misc.funcs.php to _url.funcs.php
 *
 * Revision 1.149  2006/11/29 20:17:23  blueyed
 * Refactored generate_random_passwd()
 *
 * Revision 1.148  2006/11/28 02:52:26  fplanque
 * doc
 *
 * Revision 1.147  2006/11/26 02:30:39  fplanque
 * doc / todo
 *
 * Revision 1.146  2006/11/24 18:27:27  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.145  2006/11/24 18:06:02  blueyed
 * Handle saving of $Messages centrally in header_redirect()
 *
 * Revision 1.144  2006/11/23 23:20:11  blueyed
 * get_base_domain(): added test, whcih currently fails + notes
 *
 * Revision 1.143  2006/11/23 02:29:00  fplanque
 * fixed IDN and more IDN
 *
 * Revision 1.142  2006/11/22 18:47:59  blueyed
 * Fixed get_base_domain() again some more; added tests (2 failing ones)
 *
 * Revision 1.141  2006/11/22 01:19:34  fplanque
 * contact the admin feature
 *
 * Revision 1.140  2006/11/22 00:24:38  blueyed
 * Fixed get_base_domain() for empty URL/domain
 *
 * Revision 1.139  2006/11/21 19:18:40  fplanque
 * get_base_domain()  / get_ban_domain() may need more unit tests, especially about what to do when invalid URLs are passed.
 *
 * Revision 1.138  2006/11/19 23:43:05  blueyed
 * Optimized icon and $IconLegend handling
 *
 * Revision 1.137  2006/11/14 22:10:41  blueyed
 * Removed own bloat in debug_die(), it is an error after all. doc
 *
 * Revision 1.136  2006/11/14 21:57:19  blueyed
 * Store an array of Debuglog entries in the session, not just the last one - because we may redirect more than once, e.g. when redirecting to canonical url, after having posted a comment.
 *
 * Revision 1.135  2006/11/06 19:43:27  blueyed
 * *** empty log message ***
 *
 * Revision 1.134  2006/11/05 20:13:57  fplanque
 * minor
 *
 * Revision 1.133  2006/11/02 15:57:47  blueyed
 * Fixes for pre_dump() and add_icon()
 *
 * Revision 1.132  2006/11/02 01:34:43  blueyed
 * doc/QUESTION
 *
 * Revision 1.131  2006/11/01 19:04:35  blueyed
 * re-added htmlspecialchars() to pre_dump() and made it CLI aware
 *
 * Revision 1.130  2006/10/31 01:17:56  blueyed
 * Send contenttype/CHARSET in debug_die() if not done so before
 *
 * Revision 1.129  2006/10/31 00:32:57  blueyed
 * doc
 *
 * Revision 1.128  2006/10/26 20:23:00  blueyed
 * Handle invalid URLs gracefully in url_rel_to_same_host()
 *
 * Revision 1.127  2006/10/23 22:19:03  blueyed
 * Fixed/unified encoding of redirect_to param. Use just rawurlencode() and no funky &amp; replacements
 *
 * Revision 1.126  2006/10/16 08:39:10  blueyed
 * Merged fixes from v-1-9 branch
 *
 * Revision 1.125  2006/10/14 16:06:38  blueyed
 * Fixed anchors for "redirection-Debuglog"
 *
 * Revision 1.124  2006/10/14 02:12:01  blueyed
 * Added url_absolute(), make_rel_links_abs() + Tests; Fixed validate_url() and allow relative URLs, which get converted to absolute ones in feeds.
 *
 * Revision 1.123  2006/10/13 11:47:55  blueyed
 * Use var_dump() only in pre_dump()
 *
 * Revision 1.122  2006/10/13 11:44:45  blueyed
 * MFB: url_rel_to_same_host()
 *
 * Revision 1.121  2006/10/09 10:42:44  blueyed
 * better layout for pre_dump()
 *
 * Revision 1.120  2006/10/09 10:29:16  blueyed
 * Handling of "resource" arguments in pre_dump()
 *
 * Revision 1.119  2006/09/30 20:35:40  blueyed
 * Added "text" format to format_to_output()
 *
 * Revision 1.118  2006/09/26 11:22:47  blueyed
 * doc fix
 *
 * Revision 1.117  2006/09/26 11:21:15  blueyed
 * Fixed generate_random_passwd()
 *
 * Revision 1.116  2006/09/21 17:35:54  blueyed
 * Add failure message to "error" category of Debuglog, instead of "note".
 *
 * Revision 1.115  2006/09/10 14:21:35  blueyed
 * Added url_same_protocol() (+tests) and use it for preview URL
 *
 * Revision 1.113  2006/09/09 14:07:59  blueyed
 * TODO for severely broken get_base_domain
 *
 * Revision 1.112  2006/09/09 13:24:38  blueyed
 * doc fix
 *
 * Revision 1.111  2006/08/29 21:37:28  blueyed
 * fixed E_NOTICE
 *
 * Revision 1.110  2006/08/26 23:28:24  blueyed
 * doc
 *
 * Revision 1.109  2006/08/26 20:32:48  fplanque
 * fixed redirects
 *
 * Revision 1.108  2006/08/24 21:41:14  fplanque
 * enhanced stats
 *
 * Revision 1.107  2006/08/21 16:07:44  fplanque
 * refactoring
 *
 * Revision 1.106  2006/08/20 23:16:02  blueyed
 * generate_random_key(): use param to not use ambiguous chars.
 *
 * Revision 1.105  2006/08/20 22:25:22  fplanque
 * param_() refactoring part 2
 *
 * Revision 1.104  2006/08/20 17:17:53  fplanque
 * removed potential ambiguities in auto generated passwords
 *
 * Revision 1.103  2006/08/20 13:47:25  fplanque
 * extracted param funcs from misc
 *
 * Revision 1.102  2006/08/20 00:01:29  blueyed
 * fix: test if Session is set in debug_info() (may not during install)
 *
 * Revision 1.101  2006/08/19 10:23:18  yabs
 * correcting display_list
 *
 * Revision 1.100  2006/08/19 08:24:08  yabs
 * correcting display_list() parameters
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