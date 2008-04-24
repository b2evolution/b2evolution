<?php
/**
 * This file implements general purpose functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
load_funcs('antispam/model/_antispam.funcs.php');
load_funcs('files/model/_file.funcs.php');


/**
 * Shutdown function: save HIT and update session!
 *
 * This is registered in _main.inc.php with register_shutdown_function()
 * This is called by PHP at the end of the script.
 *
 * NOTE: anything happening here will unfortunately not appear in the debug_log
 *       dh> Well, it does so for me (PHP 5.2.4).
 */
function shutdown()
{
  /**
	 * @var Hit
	 */
	global $Hit;

  /**
	 * @var Session
	 */
	global $Session;

	global $Settings;

	// fp> do we need special processing if we are in CLI mode?  probably earlier actually
	// if( ! $is_cli )

	// Save the current HIT:
	$Hit->log();

	// Update the SESSION:
	$Session->dbsave();

	// Auto pruning of old HITS, old SESSIONS and potentially MORE analytics data:
	if( $Settings->get( 'auto_prune_stats_mode' ) == 'page' )
	{ // Autopruning is requested
		load_class('sessions/model/_hitlist.class.php');
		Hitlist::dbprune(); // will prune once per day, according to Settings
	}

	// Calling debug_info() here will produce complete data but it will be after </html> hence invalid.
	// Then again, it's for debug only, so it shouldn't matter that much.
	debug_info();

	// Update the SESSION again, at the very end:
	// (e.g. "Debugslogs" may have been removed in debug_info())
	$Session->dbsave();
}


/***** Formatting functions *****/

/**
 * Format a string/content for being output
 *
 * @author fplanque
 * @todo htmlspecialchars() takes a charset argument, which we could provide ($evo_charset?)
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
			$content = htmlspecialchars( $content, ENT_QUOTES );  // Handles &, ", ', < and >
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


/*
 * autobrize(-)
 */
function autobrize($content) {
	$content = callback_on_non_matching_blocks( $content, '~<code>.+?</code>~is', 'autobrize_callback' );
	return $content;
}

/**
 * Adds <br>'s to non code blocks
 *
 * @param string $content
 * @return string content with <br>'s added
 */
function autobrize_callback( $content )
{
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
	$content = callback_on_non_matching_blocks( $content, '~<code>.+?</code>~is', 'unautobrize_callback' );
	return $content;
}

/**
 * Removes <br>'s from non code blocks
 *
 * @param string $content
 * @return string content with <br>'s removed
 */
function unautobrize_callback( $content )
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

function mysql2localedatetime_spans( $mysqlstring, $datefmt = NULL, $timefmt = NULL )
{
	if( is_null( $datefmt ) )
	{
		$datefmt = locale_datefmt();
	}
	if( is_null( $timefmt ) )
	{
		$timefmt = locale_timefmt();
	}

	return '<span class="date">'
					.mysql2date( $datefmt, $mysqlstring )
					.'</span> <span class="time">'
					.mysql2date( $timefmt, $mysqlstring )
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
					array( $istoday,
						trim(T_($weekday[$dateweekday])),
						trim(T_($weekday_abbrev[$dateweekday])),
						trim(T_($weekday_letter[$dateweekday])),
						trim(T_($month[$datemonth])),
						trim(T_($month_abbrev[$datemonth])) ),
					$r );
		}
	}

	return $r;
}


/**
 * Add given # of days to a timestamp
 *
 * @param integer timestamp
 * @param integer days
 * @return integer timestamp
 */
function date_add_days( $timestamp, $days )
{
	return mktime( date('H',$timestamp), date('m',$timestamp), date('s',$timestamp),
								date('m',$timestamp), date('d',$timestamp)+$days, date('Y',$timestamp)  );
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
 * Get start and end day of a week, based on day f the week and start-of-week
 *
 * Used by Calendar
 *
 * @param date
 * @param integer 0 for Sunday, 1 for Monday
 */
function get_weekstartend( $date, $startOfWeek )
{
	while( date('w', $date) <> $startOfWeek )
	{
		// echo '<br />'.date('Y-m-d H:i:s w', $date).' - '.$startOfWeek;
		// mktime is needed so calculations work for DST enabling. Example: March 30th 2008, start of week 0 sunday
		$date = mktime( 0, 0, 0, date('m',$date), date('d',$date)-1, date('Y',$date) );
	}
	// echo '<br />'.date('Y-m-d H:i:s w', $date).' = '.$startOfWeek;
	$week['start'] = $date;
	$week['end']   = $date + 604800; // 7 days

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
 *
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
	$cats = array();

	if( preg_match('~<category>(\d+\s*(,\s*\d*)*)</category>~i', $content, $match) )
	{
		$cats = preg_split('~\s*,\s*~', $match[1], -1, PREG_SPLIT_NO_EMPTY);
		foreach( $cats as $k => $v )
		{
			$cats[$k] = (int)$v;
		}
	}

	return $cats;
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

	return $value;
}


/**
 * Log the XML-RPC call Result into LOG object
 *
 * @param object XMLRPC response object
 * @param Log object to add messages to
 * @return boolean true = success, false = error
 */
function xmlrpc_logresult( $result, & $message_Log, $log_payload = true )
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

	if( $log_payload )
	{
		$message_Log->add( T_('Response').': '.$out, 'success' );
	}

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
 * Wrap pre tag around {@link var_dump()} for better debugging.
 *
 * @param $var__var__var__var__,... mixed variable(s) to dump
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

		// no limits:
		$old_var_display_max_children = ini_set('xdebug.var_display_max_children', -1); // default: 128
		$old_var_display_max_data = ini_set('xdebug.var_display_max_data', -1); // max string length; default: 512
		$old_var_display_max_depth = ini_set('xdebug.var_display_max_depth', -1); // default: 3

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

		// restore xdebug settings:
		ini_set('xdebug.var_display_max_children', $old_var_display_max_children);
		ini_set('xdebug.var_display_max_data', $old_var_display_max_data);
		ini_set('xdebug.var_display_max_depth', $old_var_display_max_depth);
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
	if( ! function_exists( 'debug_backtrace' ) ) // PHP 4.3.0
	{
		return 'Function debug_backtrace() is not available!';
	}

	$r = '';

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

	$r .= '<div style="padding:1ex; margin-bottom:1ex; text-align:left; color:#000; background-color:#ddf;">
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

			$call = "<strong>\n";
			if( isset($l_trace['class']) )
			{
				$call .= $l_trace['class'];
			}
			if( isset($l_trace['type']) )
			{
				$call .= $l_trace['type'];
			}
			$call .= $l_trace['function']."( </strong>\n";
			if( $args )
			{
				$call .= ' '.implode( ', ', $args ).' ';
			}
			$call .='<strong>)</strong>';

			$r .= $call."<br />\n";

			$r .= '<strong>';
			if( isset($l_trace['file']) )
			{
				$r .= "File: </strong> ".$l_trace['file'];
			}
			else
			{
				$r .= '[runtime created function]</strong>';
			}
			if( isset($l_trace['line']) )
			{
				$r .= ' on line '.$l_trace['line'];
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
		$log_message .= "\nREQUEST_URI:  ".( isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '-' );
		$log_message .= "\nHTTP_REFERER: ".( isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '-' );

		error_log( str_replace("\n", ' / ', $log_message), 0 /* PHP's system logger */ );
	}


	// DEBUG OUTPUT:
	if( $debug )
	{
		if( $is_cli )
			echo $backtrace_cli;
		else
			echo $backtrace;
	}

	// EXIT:
	if( ! $is_cli )
	{ // Attempt to keep the html valid (but it doesn't really matter anyway)
		echo '</body></html>';
	}

	die(1);	// Error code 1. Note: This will still call the shutdown function.
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
	}

	// Attempt to keep the html valid (but it doesn't really matter anyway)
	echo '</body></html>';

	die(2); // Error code 2. Note: this will still call the shutdown function.
}


/**
 * Outputs debug info, according to {@link $debug} or $force param. This gets called typically at the end of the page.
 *
 * @todo dh> add get_debug_info() which returns and supports non-html format (for debug_die())
 * @param boolean true to force output regardless of {@link $debug}
 */
function debug_info( $force = false )
{
	global $debug, $debug_done, $Debuglog, $DB, $obhandler_debug, $Timer, $ReqHost, $ReqPath;
	global $cache_imgsize, $cache_File;
	global $Session;
	global $db_config, $tableprefix;
  /**
	 * @var Hit
	 */
	global $Hit;

	if( !$force )
	{
		if( !empty($debug_done))
		{ // Already displayed!
			return;
		}

		if( empty($debug) )
		{ // No debug output desired:
			return;
		}

		if( $debug < 2 && ( empty($Hit) || $Hit->agent_type != 'browser' ) )
		{	// Don't display if it's not a browser (very needed for proper RSS display btw)
			// ($Hit is empty e.g. during install)
			return;
		}
	}
	//Make sure debug output only happens once:
	$debug_done = true;

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

	if($db_config)
	{
		echo '<pre>',
		T_('DB Username').': '.$db_config['user']."\n".
		T_('DB Database').': '.$db_config['name']."\n".
		T_('DB Host').': '.$db_config['host']."\n".
		T_('DB tables prefix').': '.$tableprefix."\n".
		T_('DB connection charset').': '.$db_config['connection_charset']."\n".
		'</pre>';
	}

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
 * Prevent email header injection.
 */
function mail_sanitize_header_string( $header_str, $close_brace = false )
{
	// Prevent injection! (remove everything after (and including) \n or \r)
	$header_str = preg_replace( '~(\r|\n).*$~s', '', trim($header_str) );

	if( $close_brace && strpos( $header_str, '<' ) !== false && strpos( $header_str, '>' ) === false )
	{ // We have probably stripped the '>' at the end!
		$header_str .= '>';
	}

	return $header_str;
}

/**
 * Encode to RFC 1342 "Representation of Non-ASCII Text in Internet Message Headers"
 *
 * @param string
 * @param string 'Q' for Quoted printable, 'B' for base64
 */
function mail_encode_header_string( $header_str, $mode = 'Q' )
{
	global $evo_charset;

	/* blueyed way that requires mbstring  (did not work for Alex RU)
	if( function_exists('mb_encode_mimeheader') )
	{ // encode subject
		$subject = mb_encode_mimeheader( $subject, mb_internal_encoding(), 'B', $NL );
	}
	*/

	if( preg_match( '¤[^a-z0-9!*+\-/ ]¤i', $header_str ) )
	{	// If the string actually needs some encoding
		if( $mode == 'Q' )
		{	// Quoted printable is best for reading with old/text terminal mail reading/debugging stuff:
			$header_str = preg_replace( '¤([^a-z0-9!*+\-/ ])¤ie', 'sprintf("=%02x", ord(StripSlashes("\\1")))', $header_str );
			$header_str = str_replace( ' ', '_', $header_str );

			$header_str = "=?$evo_charset?Q?".$header_str."?=";
		}
		else
		{ // Base 64 -- Alex RU way:
			$header_str = "=?$evo_charset?B?".base64_encode( $header_str )."?=";
		}
	}

	return $header_str;
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
 * @param string Recipient email address. (Caould be multiple comma-separated addresses.)
 * @param string Recipient name. (Only use if sending to a single address)
 * @param string Subject of the mail
 * @param string The message text
 * @param string From address, being added to headers (we'll prevent injections);
 *               see {@link http://securephp.damonkohler.com/index.php/Email_Injection}.
 *               Defaults to {@link $notify_from} if NULL.
 * @param string From name.
 * @param array Additional headers ( headername => value ). Take care of injection!
 * @return boolean True if mail could be sent (not necessarily delivered!), false if not - (return value of {@link mail()})
 */
function send_mail( $to, $to_name, $subject, $message, $from = NULL, $from_name = NULL, $headers = array() )
{
	global $debug, $app_name, $app_version, $current_locale, $current_charset, $evo_charset, $locales, $Debuglog, $notify_from;

	$NL = "\n";

	if( !is_array( $headers ) )
	{ // Make sure $headers is an array
		$headers = array( $headers );
	}

	if( empty($from) )
	{
		$from = $notify_from;
	}

	if( ! is_windows() )
	{	// fplanque: Windows XP, Apache 1.3, PHP 4.4, MS SMTP : will not accept "nice" addresses.
		if( !empty( $to_name ) )
		{
			$to = '"'.mail_encode_header_string($to_name).'" <'.$to.'>';
		}
		if( !empty( $from_name ) )
		{
			$from = '"'.mail_encode_header_string($from_name).'" <'.$from.'>';
		}
	}

	$from = mail_sanitize_header_string( $from, true );
	// From has to go into headers
	$headers['From'] = $from;

	// echo 'sending email to: ['.htmlspecialchars($to).'] from ['.htmlspecialchars($from).']';

	// sam2k way that doe snot require mbstring:
	$subject = mail_encode_header_string($subject);

	$message = str_replace( array( "\r\n", "\r" ), $NL, $message );

	// Convert encoding of message (from internal encoding to the one of the message):
	// fp> why do we actually convert to $current_charset?
	$message = convert_charset( $message, $current_charset, $evo_charset );
	// Specify charset and content-type of email
	$headers['Content-Type'] = 'text/plain; charset='.$current_charset;


	// ADDITIONAL HEADERS:
	$headers['X-Mailer'] = 'b2evolution '.$app_version.' - PHP/'.phpversion();
	$headers['X-Remote-Addr'] = implode( ',', get_ip_list() );


	// COMPACT HEADERS:
	$headerstring = '';
	reset( $headers );
	while( list( $lKey, $lValue ) = each( $headers ) )
	{ // Add additional headers
		$headerstring .= $lKey.': '.$lValue.$NL;
	}

	// SEND MESSAGE:
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
 * @param string word to be displayed after icon (if no icon gets displayed, $title will be used instead!)
 * @param integer 1-5: weight of the icon. The icon will be displayed only if its weight is >= than the user setting threshold.
 *                     Use 5, if it's a required icon - all others could get disabled by the user. (Default: 4)
 * @param integer 1-5: weight of the word. The word will be displayed only if its weight is >= than the user setting threshold.
 *                     (Default: 1)
 * @param array Additional attributes to the A tag. The values must be properly encoded for html output (e.g. quotes).
 *        It may also contain these params:
 *         - 'use_js_popup': if true, the link gets opened as JS popup. You must also pass an "id" attribute for this!
 *         - 'use_js_size': use this to override the default popup size ("500, 400")
 *         - 'class': defaults to 'action_icon', if not set; use "" to not use it
 * @return string The generated action icon link.
 */
function action_icon( $title, $icon, $url, $word = NULL, $icon_weight = NULL, $word_weight = NULL, $link_attribs = array() )
{
	global $UserSettings;

	$link_attribs['href'] = $url;
	$link_attribs['title'] = $title;

	if( is_null($icon_weight) )
	{
		$icon_weight = 4;
	}
	if( is_null($word_weight) )
	{
		$word_weight = 1;
	}

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
	global $admin_subdir, $Debuglog, $IconLegend, $use_strict;
	global $conf_path;
	global $rsc_path, $rsc_url;

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
			return $rsc_path.$icon['file'];
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
			return $rsc_url.$icon['file'];
			/* BREAK */

		case 'size':
			if( !isset( $icon['size'] ) )
			{
				$Debuglog->add( 'No iconsize for ['.$iconKey.']', 'icons' );

				$icon['size'] = imgsize( $rsc_path.$icon['file'] );
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
			$r = '<img src="'.$rsc_url.$icon['file'].'" ';

			if( !$use_strict )
			{	// Include non CSS fallbacks - transitional only:
				$r .= 'border="0" align="top" ';
			}

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

			$r = '<img src="'.$rsc_url.$blank_icon['file'].'" ';

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
	$html = "<a id='$link_id' href='#' onclick='return toggle_display_by_id(\"$link_id\", \"$target_id\", \""
		.jsspecialchars( $text_when_displayed ).'", "'.jsspecialchars( $text_when_hidden ).'")\'>'
		.( $display_hidden ? $text_when_hidden : $text_when_displayed )
		.'</a>';

	return $html;
}


/**
 * Escape a string to be used in Javascript.
 *
 * @param string
 * @return string
 */
function jsspecialchars($s)
{
	$r = str_replace(
		array(  '\\', '"', "'" ),
		array( '\\\\', '\"', "\'" ),
		$s );
	return htmlspecialchars($r, ENT_QUOTES);
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
function get_manual_link( $topic )
{
	global $Settings, $current_locale, $app_shortname, $app_version;

	if( $Settings->get('webhelp_enabled') )
	{
		$manual_url = 'http://manual.b2evolution.net/redirect/'.str_replace(" ","_",strtolower($topic)).'?lang='.$current_locale.'&amp;app='.$app_shortname.'&amp;version='.$app_version;

		$webhelp_link = action_icon( T_('Open relevant page in online manual'), 'manual', $manual_url, T_('Manual'), 5, 1, array( 'target' => '_blank' ) );

		return ' '.$webhelp_link;
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
 * @todo dh> I don't think using entities/HTML as default for $implode_last is sane!
 *           Use "&" instead and make sure that the output for HTML is HTML compliant..
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
 * Display an array as a list:
 *
 * @param array
 * @param string
 * @param string
 * @param string
 * @param string
 * @param string
 */
function display_list( $items, $list_start = '<ul>', $list_end = '</ul>', $item_separator = '',
												$item_start = '<li>', $item_end = '</li>', $force_hash = NULL, $max_items = NULL )
{
	if( !is_null($max_items) && $max_items < 1 )
	{
		return;
	}

	if( !empty( $items ) )
	{
		echo $list_start;
		$count = 0;
		$first = true;

		foreach( $items as $item )
		{	// For each list item:

			$link = resolve_link_params( $item, $force_hash );
			if( empty( $link ) )
			{
				continue;
			}

			$count++;
			if( $count>1 )
			{
				echo $item_separator;
			}
			echo $item_start.$link.$item_end;

			if( !is_null($max_items) && $count >= $max_items )
			{
				break;
			}
		}
		echo $list_end;
	}
}


/**
 * Credits stuff.
 */
function display_param_link( $params )
{
	echo resolve_link_params( $params );
}


/**
 * Resolve a link based on params (credits stuff)
 *
 * @param array
 * @param integer
 * @param array
 * @return string
 */
function resolve_link_params( $item, $force_hash = NULL, $params = array() )
{
	global $current_locale;

	// echo 'resolve link ';

	if( is_array( $item ) )
	{
		if( isset( $item[0] ) )
		{	// Older format, which displays the same thing for all locales:
			return generate_link_from_params( $item, $params );
		}
		else
		{	// First get the right locale:
			// echo $current_locale;
			foreach( $item as $l_locale => $loc_item )
			{
				if( $l_locale == substr( $current_locale, 0, strlen($l_locale) ) )
				{	// We found a matching locale:
					//echo "[$l_locale/$current_locale]";
					if( is_array( $loc_item[0] ) )
					{	// Randomize:
						$loc_item = hash_link_params( $loc_item, $force_hash );
					}

					return generate_link_from_params( $loc_item, $params );
				}
			}
			// No match found!
			return '';
		}
	}

	// Super old format:
	return $item;
}


/**
 * Get a link line, based url hash combined with probability percentage in first column
 *
 * @param array of arrays
 * @param display for a specific hash key
 */
function hash_link_params( $link_array, $force_hash = NULL )
{
	global $ReqHost, $ReqPath, $ReqURI;

	static $hash;

	if( !is_null($force_hash) )
	{
		$hash = $force_hash;
	}
	elseif( !isset($hash) )
	{
		$key = $ReqHost.$ReqPath;

		global $Blog;
		if( !empty($Blog) && strpos( $Blog->get_setting('single_links'), 'param_' ) === 0 )
		{	// We are on a blog that doesn't even have clean URLs for posts
			$key .= $ReqURI;
		}

		$hash = 0;
		for( $i=0; $i<strlen($key); $i++ )
		{
			$hash += ord($key[$i]);
		}
		$hash = $hash % 100 + 1;

		// $hash = rand( 1, 100 );
		global $debug, $Debuglog;
		if( $debug )
		{
			$Debuglog->add( 'Hash key: '.$hash, 'vars' );
		}
	}
	//	echo "[$hash] ";

	foreach( $link_array as $link_params )
	{
		// echo '<br>'.$hash.'-'.$link_params[ 0 ];
		if( $hash <= $link_params[ 0 ] )
		{	// select this link!
			// pre_dump( $link_params );
			array_shift( $link_params );
			return $link_params;
		}
	}
	// somehow no match, return 1st element:
	$link_params = $link_array[0];
	array_shift( $link_params );
	return $link_params;
}


/**
 * Generate a link from params (credits stuff)
 *
 * @param array
 * @param array
 */
function generate_link_from_params( $link_params, $params = array() )
{
	$url = $link_params[0];
	if( empty( $url ) )
	{
		return '';
	}

	// Make sure we are not missing any param:
	$params = array_merge( array(
			'type'        => 'link',
			'img_url'     => '',
			'img_width'   => '',
			'img_height'  => '',
			'title'       => '',
		), $params );

	$text = $link_params[1];
	if( is_array($text) )
	{
		$text = hash_link_params( $text );
		$text = $text[0];
	}
	if( empty( $text ) )
	{
		return '';
	}

	if( $params['type'] == 'img' )
	{
		return '<a href="'.$url.'" target="_blank" title="'.$params['title'].'"><img src="'.$params['img_url'].'" alt="'
						.$text.'" title="'.$params['title'].'" width="'.$params['img_width'].'" height="'.$params['img_height']
						.'" border="0" /></a>';
	}

	return '<a href="'.$url.'" target="_blank">'.$text.'</a>';
}


/**
 * Call a method for all modules in a row
 */
function modules_call_method( $method_name )
{
	global $modules;

	foreach( $modules as $module )
	{
		$Module = & $GLOBALS[$module.'_Module'];
		$Module->{$method_name}();
	}
}


/*
 * $Log$
 * Revision 1.36  2008/04/24 01:56:07  fplanque
 * Goal hit summary
 *
 * Revision 1.35  2008/04/17 11:50:21  fplanque
 * I feel stupid :P
 *
 * Revision 1.34  2008/04/16 13:59:47  fplanque
 * oops
 *
 * Revision 1.33  2008/04/16 13:47:55  fplanque
 * better encoding of emails
 *
 * Revision 1.32  2008/04/13 15:15:59  fplanque
 * attempt to fix email headers for non latin charsets
 *
 * Revision 1.31  2008/04/06 19:19:29  fplanque
 * Started moving some intelligence to the Modules.
 * 1) Moved menu structure out of the AdminUI class.
 * It is part of the app structure, not the UI. Up to this point at least.
 * Note: individual Admin skins can still override the whole menu.
 * 2) Moved DB schema to the modules. This will be reused outside
 * of install for integrity checks and backup.
 * 3) cleaned up config files
 *
 * Revision 1.30  2008/04/04 16:02:13  fplanque
 * uncool feature about limiting credits
 *
 * Revision 1.29  2008/03/31 21:13:47  fplanque
 * Reverted übergeekyness
 *
 * Revision 1.28  2008/03/30 23:03:40  blueyed
 * action_icon: doc, provide default for $icon_weight and $word_weight through "null"
 *
 * Revision 1.27  2008/03/30 14:56:50  fplanque
 * DST fix
 *
 * Revision 1.26  2008/03/24 03:10:12  blueyed
 * - shutdown(): update $Session at the very end
 * - debug_info(): Test if $Hit is defined
 *
 * Revision 1.25  2008/03/21 10:25:09  yabs
 * modified autobr to respect code blocks
 *
 * Revision 1.24  2008/03/16 14:19:38  fplanque
 * no message
 *
 * Revision 1.22  2008/03/06 22:41:19  blueyed
 * MFB: doc
 *
 * Revision 1.21  2008/02/19 11:11:17  fplanque
 * no message
 *
 * Revision 1.20  2008/01/21 09:35:23  fplanque
 * (c) 2008
 *
 * Revision 1.19  2008/01/18 15:53:42  fplanque
 * Ninja refactoring
 *
 * Revision 1.18  2008/01/12 02:13:44  fplanque
 * XML-RPC debugging
 *
 * Revision 1.17  2008/01/12 00:53:27  fplanque
 * fix tests
 *
 * Revision 1.16  2008/01/05 02:25:23  fplanque
 * refact
 *
 * Revision 1.15  2007/12/29 18:55:32  fplanque
 * better antispam banning screen
 *
 * Revision 1.14  2007/12/23 19:43:58  fplanque
 * trans fat reduction :p
 *
 * Revision 1.13  2007/11/28 16:38:21  fplanque
 * minor
 *
 * Revision 1.12  2007/11/23 14:54:31  fplanque
 * no message
 *
 * Revision 1.11  2007/11/22 22:53:14  blueyed
 * get_icon_info(): relative to $rsc_url/$rsc_path (instead of $rsc_subdir)
 *
 * Revision 1.10  2007/11/22 13:24:46  fplanque
 * no message
 *
 * Revision 1.9  2007/11/22 12:16:47  blueyed
 * format_to_output(): use ENT_QUOTES for htmlspecialchars (format=formvalue)
 *
 * Revision 1.8  2007/11/08 17:46:45  blueyed
 * doc
 *
 * Revision 1.7  2007/11/03 21:04:25  fplanque
 * skin cleanup
 *
 * Revision 1.6  2007/10/25 18:29:41  blueyed
 * PasteFromBranch: Fixed url_absolute for '//foo/bar'
 *
 * Revision 1.5  2007/09/22 19:23:56  fplanque
 * various fixes & enhancements
 *
 * Revision 1.4  2007/09/12 21:00:30  fplanque
 * UI improvements
 *
 * Revision 1.3  2007/09/08 18:38:08  fplanque
 * MFB
 *
 * Revision 1.2  2007/09/04 14:56:20  fplanque
 * antispam cleanup
 *
 * Revision 1.1  2007/06/25 10:58:52  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.180  2007/06/19 23:15:08  blueyed
 * doc fixes
 *
 * Revision 1.179  2007/06/16 19:54:39  blueyed
 * doc/(-fixes)
 *
 * Revision 1.178  2007/06/05 17:00:02  blueyed
 * MFB v-1-10: Consistent logging of HTTP_REFERER/REQUEST_URI; fixed possible E_NOTICE
 *
 * Revision 1.177  2007/05/23 23:07:53  blueyed
 * Display DB info (user, database, host, tableprefix, charset) in debug_info()
 *
 * Revision 1.176  2007/05/13 20:49:34  fplanque
 * removed hack that did inconsistent action upon the HTML and the Security checkers.
 *
 * Revision 1.175  2007/05/13 18:31:23  blueyed
 * Trim special date param replacements in date_i18n(). Fixes http://forums.b2evolution.net/viewtopic.php?p=55213#55213.
 *
 * Revision 1.174  2007/05/12 10:13:25  yabs
 * secuirty checker uses setting for allowing id/style in comments
 * amended get_icon to respect $use_strict
 *
 * Revision 1.173  2007/04/26 00:11:08  fplanque
 * (c) 2007
 *
 * Revision 1.172  2007/04/25 18:47:42  fplanque
 * MFB 1.10: groovy links
 *
 * Revision 1.171  2007/04/19 20:36:42  blueyed
 * Fixed possible E_NOTICE with REQUEST_URI/cron
 *
 * Revision 1.170  2007/02/16 11:23:02  blueyed
 * No limits for xdebug_var_dump() in pre_dump()
 *
 * Revision 1.169  2007/02/11 02:10:35  blueyed
 * Minor improvements to debug_get_backtrace()
 *
 * Revision 1.168  2007/02/06 13:47:25  blueyed
 * Fixed escaping in get_link_showhide(); added jsspecialchars(); see http://forums.b2evolution.net/viewtopic.php?p=50564
 *
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
 */
?>
