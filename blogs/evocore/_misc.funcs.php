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
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author cafelog (team)
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author jeffbearer: Jeff BEARER.
 * @author sakichan: Nobuo SAKIYAMA.
 * @author vegarg: Vegar BERG GULDAL.
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/***** Formatting functions *****/

/**
 * Format a string/content for being output
 *
 * {@internal format_to_output(-) }}
 *
 * @author fplanque
 * @param string raw text
 * @param string format, can be one of the following
 * - raw: do nothing
 * - htmlbody: display in HTML page body: allow full HTML
 * - entityencoded: Special mode for RSS 0.92: allow full HTML but escape it
 * - htmlhead: strips out HTML (mainly for use in Title)
 * - htmlattr: use as an attribute: strips tags and escapes quotes
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
			die( 'Output format ['.$format.'] not supported.' );
	}

	return $content;
}


/*
 * format_to_edit(-)
 */
function format_to_edit( $content, $autobr = false )
{
	if( $autobr )
	{
		// echo 'unBR:',htmlspecialchars(str_replace( ' ', '*', $content) );
		$content = unautobrize($content);
	}

	$content = htmlspecialchars($content);
	return($content);
}


/**
 * Format raw HTML input to cleaned up and validated HTML
 */
function format_to_post( $content, $autobr = 0, $is_comment = 0, $encoding = 'ISO-8859-1' )
{
	global $use_balanceTags, $use_html_checker, $use_security_checker;
	global $allowed_tags, $allowed_attribues, $uri_attrs, $allowed_uri_scheme;
	global $comments_allowed_tags, $comments_allowed_attribues, $comments_allowed_uri_scheme;

	// Replace any & that is not a character or entity reference with &amp;
	$content= preg_replace( '/&(?!#[0-9]+;|#x[0-9a-fA-F]+;|[a-zA-Z_:][a-zA-Z0-9._:-]*;)/', '&amp;', $content );

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
			$checker = & new SafeHtmlChecker( $allowed_tags, $allowed_attribues,
																			$uri_attrs, $allowed_uri_scheme, $encoding );
		}
		else
		{
			$checker = & new SafeHtmlChecker( $comments_allowed_tags, $comments_allowed_attribues,
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
 * {@internal convert_chars(-)}}
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
 * {@internal only used with _autolinks.plugin.php - move it there?
 *  NOTE: its tested in the misc.funcs.simpletest.php test case }}
 *
 * original function: phpBB, extended here for AIM & ICQ
 * fplanque restricted :// to http:// and mailto://
 */
function make_clickable( $text, $moredelim = '&amp;' )
{
	$text = preg_replace( array( '#(^|\s)(https?|mailto)://(([^<>{}\s,]|,(?!\s))+)#i',
																'#(^|\s)aim:([^,<\s]+)#i',
																'#(^|\s)icq:(\d+)#i',
																'#(^|\s)www\.([a-z0-9\-]+)\.([a-z0-9\-.\~]+)((?:/[^,<\s]*)?)#i',
																'#(^|\s)([a-z0-9\-_.]+?)@([^,<\s]+)#i', ),
												array( '$1<a href="$2://$3">$2://$3</a>',
																'$1<a href="aim:goim?screenname=$2$3'.$moredelim.'message='.urlencode(T_('Hello')).'">$2$3</a>',
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
 * Format a MYSQL date to current locale date format.
 *
 * {@internal mysql2localedate(-)}}
 *
 * @param string MYSQL date YYYY-MM-DD HH:MM:SS
 */
function mysql2localedate( $mysqlstring )
{
	return mysql2date( locale_datefmt(), $mysqlstring );
}


/**
 * Format a MYSQL date.
 *
 * {@internal mysql2date(-)}}
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
 * {@internal date_i18n(-)}}
 *
 * @param string enhanced format string
 * @param integer UNIX timestamp
 * @param boolean true to use GM time
 */
function date_i18n( $dateformatstring, $unixtimestamp, $useGM = false )
{
	global $month, $month_abbrev, $weekday, $weekday_abbrev, $weekday_letter;
	global $Settings;

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
			'l': weekday
			'D': weekday abbrev
			'e': weekday letter
			'F': month
			'M': month abbrev
		*/

		#echo $dateformatstring, '<br />';

		// protect special symbols, that date() would need proper locale set for
		$protected_dateformatstring = preg_replace( '/(?<!\\\)([lDeFM])/',
																								'@@@\\\$1@@@',
																								$dateformatstring );

		#echo $protected_dateformatstring, '<br />';

		$r = date( $protected_dateformatstring, $unixtimestamp );

		if( $protected_dateformatstring != $dateformatstring )
		{ // we had special symbols, replace them

			$datemonth = date('m', $unixtimestamp);
			$dateweekday = date('w', $unixtimestamp);

			// replace special symbols
			$r = str_replace( array(  '@@@l@@@',
																'@@@D@@@',
																'@@@e@@@',
																'@@@F@@@',
																'@@@M@@@',
															),
												array(  T_($weekday[$dateweekday]),
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
 * Get start and end day of a week, based on week number and start-of-week
 *
 * Used by Calendar
 *
 * {@internal get_weekstartend(-)}}
 */
function get_weekstartend( $mysqlstring, $startOfWeek )
{
	$my = substr($mysqlstring, 0, 4);
	$mm = substr($mysqlstring, 5, 2);
	$md = substr($mysqlstring, 8, 2);
	$day = mktime(0, 0, 0, $mm, $md, $my);
	$weekday = date('w', $day);
	$i = 86400;
	while( $weekday <> $startOfWeek )
	{
		$weekday = date('w', $day);
		$day = $day - 86400;
		$i = 0;
	}
	$week['start'] = $day + 86400 - $i;
	$week['end']   = $day + 691199;

	#pre_dump( 'weekstartend: '.$mysqlstring, date( 'Y-m-d', $week['start'] ), date( 'Y-m-d', $week['end'] ) );

	return( $week );
}


function antispambot($emailaddy, $mailto = 0) {
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
 * Check that email address looks valid
 */
function is_email( $email )
{
	#$chars = "/^([a-z0-9_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,4}\$/i";
	$chars = '/^.+@[^\.].*\.[a-z]{2,}$/i';

	if( strpos( $email, '@' ) !== false && strpos( $email, '.' ) !== false )
	{
		return (bool)(preg_match($chars, $email));
	}
	else
	{
		return false;
	}
}


/**
 * Are we running on a Windows server?
 */
function is_windows()
{
	return isset( $_SERVER['WINDIR'] );
}


// functions to count the page generation time (from phpBB2)
// ( or just any time between timer_start() and timer_stop() )

function timer_start() {
		global $timestart;
		$mtime = microtime();
		$mtime = explode(" ",$mtime);
		$mtime = $mtime[1] + $mtime[0];
		$timestart = $mtime;
		return true;
	}

function timer_stop($display=0,$precision=3) { //if called like timer_stop(1), will echo $timetotal
		global $timestart,$timeend;
		$mtime = microtime();
		$mtime = explode(" ",$mtime);
		$mtime = $mtime[1] + $mtime[0];
		$timeend = $mtime;
		$timetotal = $timeend-$timestart;
		if ($display)
			echo number_format($timetotal,$precision);
		return($timetotal);
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


/*
 * xmlrpc_displayresult(-)
 *
 * fplanque: created
 */
function xmlrpc_displayresult( $result, $log = '', $display = true )
{
	if( ! $result )
	{
		if( $display ) echo T_('No response!'),"<br />\n";
		return false;
	}
	elseif( $result->faultCode() )
	{ // We got a remote error:
		if( $display ) echo T_('Remote error'), ': ', $result->faultString(), ' (', $result->faultCode(), ")<br />\n";
		debug_fwrite($log, $result->faultCode().' -- '.$result->faultString());
		return false;
	}

	// We'll display the response:
	$val = $result->value();
	$value = xmlrpc_decode_recurse($result->value());
	if (is_array($value))
	{
		$value_arr = '';
		foreach($value as $blah)
		{
			$value_arr .= ' ['.$blah.'] ';
		}
		if( $display ) echo T_('Response'), ': ', $value_arr, "<br />\n";
		debug_fwrite($log, $value_arr);
	}
	else
	{
		if( $display ) echo T_('Response'), ': ', $value ,"<br />\n";
		debug_fwrite($log, $value);
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
	$newtext = str_replace("< !--","<!--",$newtext);
	$newtext = str_replace("<    !--","< !--",$newtext);

	return $newtext;
}


/**
 * Clean up the mess PHP has created with its funky quoting everything!
 *
 * {@internal remove_magic_quotes(-)}}
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
 * {@internal param(-) }}
 *
 * @author fplanque
 * @param string Variable to set
 * @param string Force value type to one of:
 * - boolean
 * - integer
 * - float
 * - string
 * - array
 * - object
 * - null
 * - html (does nothing)
 * - '' (does nothing)
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
	global $$var, $global_param_list, $Debuglog, $debug;

	// Check if already set
	// WARNING: when PHP register globals is ON, COOKIES get priority over GET and POST with this!!!
	if( !isset( $$var ) || $override )
	{
		if( isset($_POST[$var]) )
		{
			$$var = remove_magic_quotes( $_POST[$var] );
			// $Debuglog->add( 'param(-): '.$var.'='.$$var.' set by POST', 'params' );
		}
		elseif( isset($_GET[$var]) )
		{
			$$var = remove_magic_quotes($_GET[$var]);
			// $Debuglog->add( 'param(-): '.$var.'='.$$var.' set by GET', 'params' );
		}
		elseif( isset($_COOKIE[$var]))
		{
			$$var = remove_magic_quotes($_COOKIE[$var]);
			// $Debuglog->add( 'param(-): '.$var.'='.$$var.' set by COOKIE', 'params' );
		}
		elseif( $default === true )
		{
			die( '<p class="error">'.sprintf( T_('Parameter &laquo;%s&raquo; is required!'), $var ).'</p>' );
		}
		elseif( $forceset )
		{
			$$var = $default;
			// $Debuglog->add( 'param(-): '.$var.'='.$$var.' set by default', 'params' );
		}
		else
		{ // param not found! don't set the variable.
			// Won't be memorized nor type-forced!
			return false;
		}
	}
	else
	{ // Variable was already set but we need to remove the auto quotes
		$$var = remove_magic_quotes($$var);

		// $Debuglog->add( 'param(-): '.$var.' already set to ['.var_export($$var, true).']!', 'params' );
	}

	// type will be forced even if it was set before and not overriden
	if( !empty($type) && $$var !== NULL )
	{ // Force the type
		// echo "forcing type!";
		switch( $type )
		{
			case 'html':
				// do nothing
				break;

			case 'string':
				// echo $var, '=', $$var, '<br />';
				$$var = trim( strip_tags($$var) );
				break;

			default:
				settype( $$var, $type );
				$Debuglog->add( 'param(-): '.$var.' typed to '.$type.', new value='.$$var, 'params' );
		}
	}

	if( $memorize )
	{ // Memorize this parameter
		memorize_param( $var, $type, $default );
	}

	// echo $var, '(', gettype($$var), ')=', $$var, '<br />';
	return $$var;
}


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
		$$var = $value;
	}
}

/**
 * Forget a param so that is will not get included in subsequent {@see regenerate_url()} calls
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
 * @param mixed string or array of params to ignore
 * @param mixed string or array of params to set
 * @param mixed string
 */
function regenerate_url( $ignore = '', $set = '', $pagefileurl = '' )
{
	global $Debuglog, $global_param_list, $ReqPath, $basehost;

	if( empty($ignore) )
	{
		$ignore = array();
	}
	elseif( !is_array($ignore) )
	{
		$ignore = explode( ',', $ignore );
	}

	if( empty($set) )
	{
		$set = array();
	}
	elseif( !is_array($set) )
	{
		$set = array( $set );
	}

	$params = array();
	if( isset($global_param_list) ) foreach( $global_param_list as $var => $thisparam )
	{	// For each saved param...
		$type = $thisparam['type'];
		$defval = $thisparam['default'];

		// Check if the param needs to be ignored:
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
		{ // we don't want to include that one
			// $Debuglog->add( 'regenerate_url(): ignoring '.$var, 'params' );
			continue;
		}

		// else
		// {
		//	$Debuglog->add( 'regenerate_url(): recycling '.$var, 'params' );
		// }

		// Special cases:
		switch( $var )
		{
			case 'catsel':
			{
				global $catsel;
				if( (! empty($catsel)) && (strpos( $cat, '-' ) === false) )
				{ // It's worthwhile retransmitting the catsels
					foreach( $catsel as $value )
					{
						$params[] = 'catsel%5B%5D='.$value;
					}
				}
				break;
			}

			case 'show_status':
			{
				global $show_status;
				if( ! empty($show_status) )
				{
					foreach( $show_status as $value )
					{
						$params[] = 'show_status%5B%5D='.$value;
					}
				}
				break;
			}

			default:
			{
				global $$var;
				$value = $$var;
				// $Debuglog->add( "var=$var, type=$type, defval=[$defval], val=[$value]", 'params' );
				if( (!empty($value)) && ($value != $defval) )
				{ // Value exists and is not set to default value:
					// echo "adding $var \n";
					if( $type === 'array' )
					{ //there is a special formatting in case of arrays
						$url_array = array();
						$i = 0;
						foreach( $value as $value )
						{
							$params[] = $var.'['.$i.']='.$value;
							$i++;
						}
					}
					else
					{	// no array : normal formatting
						$params[] = $var.'='.$value;
					}
				}
				// else echo "ignoring $var \n";
			}
		}
	}

	if( !empty( $set ) )
	{
		$params = array_merge( $params, $set );
	}

	$url = empty($pagefileurl) ? $ReqPath : $pagefileurl;

	/*
   * fplanque: who added this? what for? why prevent relative paths?
   *
	if( $basehost != $_SERVER['HTTP_HOST'] && !preg_match( '#^https?://#', $url ) )
	{
		$url = 'http'.(isset($_SERVER['HTTPS']) ? 's' : '').'://'.$_SERVER['HTTP_HOST'].( substr( $url, 0, 1 ) == '/' ? '' : '/' ).$url;
	}
	*/

	if( !empty( $params ) )
	{
		$url = url_add_param( $url, implode( '&amp;', $params ) );
	}
	// $Debuglog->add( 'regenerate_url(): ['.$url.']', 'params' );
	return $url;
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
 * Checks allowed URI schemes and URL ban list
 * URL can be empty
 *
 * fplanque: 0.8.5: changed return values
 * vegarg: 0.8.6.2: switched to MySQL antispam list
 *
 * {@internal validate_url(-) }}
 *
 * @param string Url to validate
 * @param array Allowed URI schemes (see /conf/_formatting.php)
 * @return mixed false or error message
 */
function validate_url( $url, & $allowed_uri_scheme )
{
	global $debug;

	if( empty($url) )
	{ // Empty URL, no problem
		return false;
	}

	if( ! preg_match('/^([a-zA-Z][a-zA-Z0-9+-.]*):[0-9]*/', $url, $matches) )
	{ // Cannot find URI scheme
		return T_('Invalid URL');
	}

	$scheme = strtolower($matches[1]);
	if(!in_array( $scheme, $allowed_uri_scheme ))
	{ // Scheme not allowed
		return T_('URI scheme not allowed');
	}

	// Search for blocked URLs:
	if( $block = antispam_url($url) )
	{
		if( $debug ) return 'Url refused. Debug info: blacklisted word: ['.$block.']';
		return T_('URL not allowed');
	}

	return false;		// OK
}


/**
 * Wrap pre tag around var_dump() for better debugging
 *
 * {@internal pre_dump(-) }}
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
}


/**
 * Outputs debug info. (Typically at the end of the page)
 *
 * {@internal debug_info(-) }}
 *
 * @param boolean true to force output
 */
function debug_info( $force = false )
{
	global $debug;
	global $Debuglog;
	global $DB;
	global $obhandler_debug;
	global $cache_imgsize, $cache_File;

	if( $debug || $force )
	{
		?>

		<hr class="clear" />
		<div class="panelblock">

		<h2>Debug info</h2>

		<?php

		$Debuglog->add( 'Len of serialized $cache_imgsize: '.strlen(serialize($cache_imgsize)), 'memory' );
		$Debuglog->add( 'Len of serialized $cache_File: '.strlen(serialize($cache_File)), 'memory' );

		if( function_exists( 'memory_get_usage' ) )
		{
			$Debuglog->add( 'Memory usage: '.bytesreadable(memory_get_usage()), 'memory' );
		}

		if( !$obhandler_debug )
		{ // don't display changing time when we want to test obhandler
			echo 'Page processing time: ', number_format(timer_stop(),3), ' seconds<br/>';
		}

		echo format_to_output(
			$Debuglog->display( array( 'container' => array(
																		'string' => '<h3>Debug messages</h3>',
																		'template' => false ),
																	'all' => array(
																		'string' => '<h4>%s:</h4>',
																		'template' => false ) ),
													'',
													false,
													array( 'error', 'note', 'all' ) ),
			'htmlbody' );
		?>
		<div class="log_container">
		<h3>DB</h3>

		<?php

		if( !isset($DB) )
		{
			echo 'No DB object.';
		}
		else
		{
			echo 'DB queries: ', $DB->num_queries, '<br />';

			$DB->dump_queries();
		}

		?>
		</div>
		</div>

		<?php
	}
}


/**
 * Output Buffer handler.
 *
 * It will be set in /blogs/evocore/_main.inc.php and handle the output.
 * It strips every line and generates a md5-ETag, which is checked against the one eventually
 * being sent by the browser.
 *
 * {@internal obhandler(-) }}
 *
 * @param string output given by PHP
*/
function obhandler( $output )
{
	global $lastmodified, $use_gzipcompression, $use_etags;
	global $servertimenow;

	// we're sending out by default
	$sendout = true;

	if( !isset( $lastmodified ) )
	{ // default of lastmodified is now
		$lastmodified = $servertimenow;
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
 * @param string Recipient, either email only or in "Name <example@example.com>" format
 * @param string Subject of the mail
 * @param string The message text
 * @param string From address, being added to headers
 * @param array Additional headers ( headername => value )
 */
function send_mail( $to, $subject, $message, $from = '', $headers = array() )
{
	global $debug, $app_name, $app_version, $current_locale, $locales, $Debuglog;

	if( !is_array( $headers ) )
	{ // Make sure $headers is an array
		$headers = array( $headers );
	}

	// Specify charset and content-type of email
	$headers['Content-Type'] = 'text/plain; charset='.$locales[ $current_locale ]['charset'];
	$headers['X-Mailer'] = $app_name.' '.$app_version.' - PHP/'.phpversion();

	// -- Build headers ----
	if( !empty($from) )
	{ // From has to go into headers
		$headerstring = "From: $from\n";
	}
	else
	{
		$headerstring = '';
	}

	if( preg_match( '#^.*?\s*<(.*?)>$#', $to, $match ) )
	{ // Handle "Name <example@example.com>" format
		$to = $match[1]; // email address only!

		if( !isset( $headers['To'] ) )
		{ // Add it as To-header, if none given in headers array.
			$headers['To'] = $match[0];
		}
	}

	reset( $headers );
	while( list( $lKey, $lValue ) = each( $headers ) )
	{ // Add additional headers
		$headerstring .= $lKey.': '.$lValue."\n";
	}

	$message = str_replace( array( "\r\n", "\r" ), "\n", $message );

	if( $debug > 1 )
	{	// We agree to die for debugging...
		if( ! mail( $to, $subject, $message, $headerstring ) )
		{
			die("Sending mail from &laquo;$from&raquo; to &laquo;$to&raquo;, Subject &laquo;$subject&raquo; FAILED.");
		}
	}
	else
	{	// Soft debugging only....
		if( ! @mail( $to, $subject, $message, $headerstring ) )
		{
			$Debuglog->add( "Sending mail from &laquo;$from&raquo; to &laquo;$to&raquo;, Subject &laquo;$subject&raquo; FAILED." );
			return false;
		}
	}

	$Debuglog->add( "Sent mail from &laquo;$from&raquo; to &laquo;$to&raquo;, Subject &laquo;$subject&raquo;." );
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
 * Create IMG tag for an action icon
 *
 * @param string TITLE text (IMG and A link)
 * @param string icon code {@see $$map_iconfiles}
 * @param string icon code for {@see get_icon()}
 */
function action_icon( $title, $icon, $url, $word = '' )
{
	$r = '<a href="'.$url.'" title="'.$title.'"';
	if( get_icon( $icon, 'rollover' ) )
		$r .= ' class="rollover"';
	{
	}
	$r .= '>'.get_icon( $icon, 'imgtag', array( 'title'=>$title ) );
	if( !empty( $word) )
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
 * @param string icon for what?
 * @param string what to return for that icon ('file', 'url', 'size' {@link imgsize()})
 * @param array additional params ( 'class' => class name when getting 'imgtag',
																		'size' => param for 'size',
																		'title' => title attribute for imgtag)
 */
function get_icon( $iconKey, $what = 'imgtag', $params = NULL )
{
	global $map_iconfiles, $basepath, $admin_subdir, $baseurl, $Debuglog;

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
	 * fplanque>> removed <div> tags because they make it even harder to debug :/
	 */
	if( $iconfile === false )
	{
		return '[no image defined for '.var_export( $iconKey, true ).'!]';
		return false;
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

			$r = '<img '
						.'class="';
			if( isset( $params['class'] ) )
			{
				$r .= $params['class'];
			}
			elseif( isset($map_iconfiles[$iconKey]['class']) )
			{	// This icon has a rollover
				$r .= $map_iconfiles[$iconKey]['class'];
			}
			else
			{
				$r .= 'middle';
			}
			$r .=	'" '
						.'src="'.$iconurl.'" '
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
 * in this order. '' is used when no IP could be detected.
 *
 * @param boolean True, to get only the first IP (probably REMOTE_ADDR)
 * @return array|string Depends on first param.
 */
function getIpList( $firstOnly = false )
{
	$r = array();

	if( isset( $_SERVER['REMOTE_ADDR'] ) )
	{
		$r[] = $_SERVER['REMOTE_ADDR'];
	}

	if( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] !== 'unknown' )
	{ // IP(s) behind Proxy
		foreach( explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] ) as $lIP )
		{
			if( $lIP != 'unknown' )
			{
				$r[] = trim($lIP);
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
 * Generate a random password, respecting the minimal password length.
 *
 * @return string
 */
function getRandomPassword( $length = NULL )
{
	if( is_null($length) )
	{
		global $Settings;

		$length = isset($Settings) // not set during install
							? $Settings->get( 'user_minpwdlen' )
							: 6;
	}

	return substr( md5( uniqid( rand(), true ) ), 0, $length );
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
 * can be given as function parameter, GET parameter (redirecto_to),
 * is taken from {@link Hit::referer} or {@link $baseurl})
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

	if( strpos($location, $baseurl) === 0 // we're somewhere on $baseurl
			&& preg_match( '#^(.*?)([?&])action=\w+(&(amp;)?)?(.*)$#', $location, $match ) )
	{ // remove "action" get param to avoid unwanted actions
		$location = $match[1];

		if( !empty($match[5]) )
		{
			$location .= $match[2].$match[5];
		}
	}

	header('Refresh:0;url='.$location);

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
			return false;

		default:
			die( 'Unhandled action in form' );
	}
}


/*
 * $Log$
 * Revision 1.71  2005/06/10 18:25:44  fplanque
 * refactoring
 *
 * Revision 1.70  2005/06/03 15:12:33  fplanque
 * error/info message cleanup
 *
 * Revision 1.69  2005/06/02 18:50:52  fplanque
 * no message
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
 * Revision 1.64  2005/04/26 18:19:25  fplanque
 * no message
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