<?php
/**
 * This file implements general purpose functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_antispam.funcs.php';
require_once dirname(__FILE__).'/_blog.funcs.php';
require_once dirname(__FILE__).'/_item.funcs.php';
require_once dirname(__FILE__).'/_category.funcs.php';
require_once dirname(__FILE__).'/_file.funcs.php';
require_once dirname(__FILE__).'/_message.funcs.php';
require_once dirname(__FILE__).'/_onlineusers.funcs.php';
require_once dirname(__FILE__).'/_pingback.funcs.php';
require_once dirname(__FILE__).'/_ping.funcs.php';
require_once dirname(__FILE__).'/_skin.funcs.php';
require_once dirname(__FILE__).'/_trackback.funcs.php';
require_once dirname(__FILE__).'/_user.funcs.php';
if( !isset( $use_html_checker ) ) $use_html_checker = 1;
if( $use_html_checker ) require_once dirname(__FILE__).'/_htmlchecker.class.php';


/**
 * Report MySQL errors in detail.
 *
 * {@internal mysql_oops(-) }}
 *
 * @deprecated use class DB instead - not used in core anymore
 *
 * @param string The query which led to the error
 * @return boolean success?
 */
function mysql_oops( $sql_query )
{
	$error  = '<p class="error">'. T_('Oops, MySQL error!'). '</p>'
		. '<p>Your query:<br /><code>'. $sql_query. '</code></p>'
		. '<p>MySQL said:<br /><code>'. mysql_error(). ' (error '. mysql_errno(). ')</code></p>';
	die( $error );
}


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


/*
 * format_to_post(-)
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

		$checker->check( $content );
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
			$Messages->add( 'Illegal markup found: '.htmlspecialchars($matches[1]) );
		}
		// Styling restictions:
		$matches = array();
		if( $is_comment && preg_match ('#\s(style|class|id)\s*=#i', $check, $matches) )
		{
			$Messages->add( 'Unallowed CSS markup found: '.htmlspecialchars($matches[1]) );
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


/*
 * make_clickable(-)
 *
 * original function: phpBB, extended here for AIM & ICQ
 * fplanque restricted :// to http:// and mailto://
 */
function make_clickable($text)
{
	$ret = ' '. $text;

	$ret = preg_replace("#([\n ])(http|mailto)://([^, <>{}\n\r]+)#i", "\\1<a href=\"\\2://\\3\">\\2://\\3</a>", $ret);

	$ret = preg_replace("#([\n ])aim:([^,< \n\r]+)#i", "\\1<a href=\"aim:goim?screenname=\\2\\3&message=Hello\">\\2\\3</a>", $ret);

	$ret = preg_replace("#([\n ])icq:([^,< \n\r]+)#i", "\\1<a href=\"http://wwp.icq.com/scripts/search.dll?to=\\2\\3\">\\2\\3</a>", $ret);

	$ret = preg_replace("#([\n ])www\.([a-z0-9\-]+)\.([a-z0-9\-.\~]+)((?:/[^,< \n\r]*)?)#i", "\\1<a href=\"http://www.\\2.\\3\\4\">www.\\2.\\3\\4</a>", $ret);

	$ret = preg_replace("#([\n ])([a-z0-9\-_.]+?)@([^,< \n\r]+)#i", "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $ret);

	$ret = substr($ret, 1);
	return($ret);
}


/***** // Formatting functions *****/

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
	$unixtimestamp = mktime(substr($m,11,2),substr($m,14,2),substr($m,17,2),substr($m,5,2),substr($m,8,2),substr($m,0,4));

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
function get_weekstartend($mysqlstring, $start_of_week)
{
	$my = substr($mysqlstring, 0, 4);
	$mm = substr($mysqlstring, 5, 2);
	$md = substr($mysqlstring, 8, 2);
	$day = mktime(0, 0, 0, $mm, $md, $my);
	$weekday = date('w', $day);
	$i = 86400;
	while( $weekday > $start_of_week )
	{
		$weekday = date('w', $day);
		$day = $day - 86400;
		$i = 0;
	}
	$week['start'] = $day + 86400 - $i;
	$week['end']   = $day + 691199;
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
function is_email($user_email)
{
	#$chars = "/^([a-z0-9_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,4}\$/i";
	$chars = '/^.+@[^\.].*\.[a-z]{2,}$/i';
	if( strstr($user_email, '@') && strstr($user_email, '.') )
	{
		return (bool)(preg_match($chars, $user_email));
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


function alert_error( $msg )
{ // displays a warning box with an error message (original by KYank)
	?>
	<html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
	<head>
	<script language="JavaScript">
	<!--
	alert('<?php echo str_replace( "'", "\'", $msg ) ?>');
	history.back();
	//-->
	</script>
	</head>
	<body>
	<!-- this is for non-JS browsers (actually we should never reach that code, but hey, just in case...) -->
	<?php echo $msg; ?><br />
	<a href="<?php echo $_SERVER["HTTP_REFERER"]; ?>"><?php echo T_('go back') ?></a>
	</body>
	</html>
	<?php
	exit;
}


function alert_confirm($msg)
{ // asks a question - if the user clicks Cancel then it brings them back one page
	?>
	<script language="JavaScript">
	<!--
	if (!confirm("<?php echo $msg ?>")) {
	history.back();
	}
	//-->
	</script>
	<?php
}


function redirect_js($url,$title="...") {
	?>
	<script language="JavaScript">
	<!--
	function redirect() {
	window.location = "<?php echo $url; ?>";
	}
	setTimeout("redirect();", 100);
	//-->
	</script>
	<p><?php echo T_('Redirecting you to:') ?> <strong><?php echo $title; ?></strong><br />
	<br />
	<?php printf( T_('If nothing happens, click <a %s>here</a>.'), ' href="'.$url.'"' ); ?></p>
	<?php
	exit();
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
	global $$var, $global_param_list, $Debuglog;

	// Check if already set
	// WARNING: when PHP register globals is ON, COOKIES get priority over GET and POST with this!!!
	if( !isset( $$var ) || $override )
	{
		if( isset($_POST[$var]) )
		{
			$$var = remove_magic_quotes( $_POST[$var] );
			$Debuglog->add( 'param(-): '.$var.'='.$$var.' set by POST', 'params' );
		}
		elseif( isset($_GET[$var]) )
		{
			$$var = remove_magic_quotes($_GET[$var]);
			$Debuglog->add( 'param(-): '.$var.'='.$$var.' set by GET', 'params' );
		}
		elseif( isset($_COOKIE[$var]))
		{
			$$var = remove_magic_quotes($_COOKIE[$var]);
			$Debuglog->add( 'param(-): '.$var.'='.$$var.' set by COOKIE', 'params' );
		}
		elseif( $default === true )
		{
			die( '<p class="error">'.sprintf( T_('Parameter &laquo;%s&raquo; is required!'), $var ).'</p>' );
		}
		elseif( $forceset )
		{
			$$var = $default;
			$Debuglog->add( 'param(-): '.$var.'='.$$var.' set by default', 'params' );
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

		$Debuglog->add( 'param(-): '.$var.' already set to ['.var_export($$var, true).']!', 'params' );
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
				// echo $var, '=', $$var, '<br>';
				$$var = trim( strip_tags($$var) );
				break;

			default:
				settype( $$var, $type );
				$Debuglog->add( 'param(-): '.$var.' typed to '.$type.', new value='.$$var, 'params' );
		}
	}

	if( $memorize )
	{ // Memorize this parameter
		if( !isset($global_param_list) )
		{ // Init list if necessary:
			$global_param_list = array();
		}
		$Debuglog->add( "Memorize(".count($global_param_list).") 'var' => $var, 'type' => $type, 'default' => $default <br>", 'params');
		$global_param_list[$var] = array( 'type' => $type, 'default' => (($default===true) ? NULL : $default) );
	}

	// echo $var, '(', gettype($$var), ')=', $$var, '<br />';
	return $$var;
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

	if( $ignore == '' )
		$ignore = array( );
	elseif( !is_array($ignore) )
		$ignore = explode( ',', $ignore );

	if( $set == '' )
		$set = array( );
	elseif( !is_array($set) )
		$set = array( $set );

	$params = array();
	if( isset($global_param_list) ) foreach( $global_param_list as $var => $thisparam )
	{
		$type = $thisparam['type'];
		$defval = $thisparam['default'];

		if( in_array( $var, $ignore ) )
		{ // we don't want to include that one
			$Debuglog->add( 'regenerate_url(): ignoring '.$var, 'params' );
			continue;
		}
		else
		{
			$Debuglog->add( 'regenerate_url(): recycling '.$var, 'params' );
		}

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
				// echo "var=$var, type=$type, defval=[$defval], val=[$value] \n";
				if( (!empty($value)) && ($value != $defval) )
				{ // Value exists and is not set to default value:
					// echo "adding $var \n";
					$params[] = $var.'='.$value;
				}
				// else echo "ignoring $var \n";
			}
		}
	}

	if( ! empty( $set ) )
	{
		$params = array_merge( $params, $set );
	}

	$url = empty($pagefileurl) ? $ReqPath : $pagefileurl;

	if( $basehost != $_SERVER['HTTP_HOST'] && !preg_match( '#^https?://#', $url ) )
	{
		$url = 'http'.(isset($_SERVER['HTTPS']) ? 's' : '').'://'.$_SERVER['HTTP_HOST'].( substr( $url, 0, 1 ) == '/' ? '' : '/' ).$url;
	}


	if( !empty( $params ) )
	{
		$url = url_add_param( $url, implode( '&amp;', $params ) );
	}
	$Debuglog->add( 'regenerate_url(): ['.$url.']', 'params' );
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
function pre_dump($dump, $title = '', $output = 1 )
{
	$r = "\n<pre>";
	if( $title !== '' )
	{
		$r .= $title. ': <br />';
	}
	$r .= htmlspecialchars( var_export($dump, true) )
			."</pre>\n";

	if( !$output )
	{
		return $r;
	}
	else
	{
		echo $r;
	}
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
		$Debuglog->add( 'Size of $cache_imgsize: '.count($cache_imgsize), 'memory' );
		$Debuglog->add( 'Size of $cache_File: '.count($cache_File), 'memory' );

		if( function_exists( 'memory_get_usage' ) )
		{
			$Debuglog->add( 'Memory usage: '.bytesreadable(memory_get_usage()), 'memory' );
		}

		?>
		<hr class="clear" />
		<div class="panelblock">
		<h2>Debug info</h2>

		<?php

		if( !$obhandler_debug )
		{ // don't display changing time when we want to test obhandler
			echo 'Page processing time: ', number_format(timer_stop(),3), ' seconds<br/>';
		}

		echo format_to_output(
			$Debuglog->display( array( 'container' => array(
																		'string' => '<h3>Debug messages</h3>',
																		'template' => false ),
																	'all' => array(
																		'string' => '<h4>Level [%s]:</h4>',
																		'template' => false ) ),
													'',
													false,
													array( 'error', 'all' ) ),
			'htmlbody' );
		?>
		</div>


		<div class="panelinfo">
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

	// we're sending out by default
	$sendout = true;

	if( !isset( $lastmodified ) )
	{ // default of lastmodified is now
		$lastmodified = time();
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
		#log_hit();  // log this somehow?
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
 * sends a mail, wraps PHP's mail() function.
 *
 * $current_locale will be used to set the charset
 *
 * send_mail(-)
 *
 * @param string recipient
 * @param string subject of the mail
 * @param string the message
 * @param string From address, being added to headers
 * @param array additional headers
 */
function send_mail( $to, $subject, $message, $from = '', $headers = array() )
{
	global $app_name, $app_version, $current_locale, $locales, $Debuglog;

	if( !is_array( $headers ) )
	{ // make sure $headers is an array
		$headers = array( $headers );
	}

	// Specify charset and content-type of email
	$headers[] = 'Content-Type: text/plain; charset='.$locales[ $current_locale ]['charset'];
	$headers[] = 'X-Mailer: '.$app_name.' '.$app_version.' - PHP/'.phpversion();

	// -- build headers ----
	if( !empty($from) )
	{ // from has to go into headers
		$headerstring = "From: $from\n";
	}
	else
	{
		$headerstring = '';
	}

	if( count($headers) )
	{ // add supplied headers
		$headerstring .= implode( "\n", $headers );
	}

	$Debuglog->add( "Sending mail from $from to $to - subject $subject." );

	return @mail( $to, $subject, $message, $headerstring );
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
function is_regexp( $sREGEXP )
{
	$sPREVIOUSHANDLER = Set_Error_Handler ("trapError");
	preg_match( '#'.str_replace( '#', '\#', $sREGEXP ).'#', '' );
	restore_error_handler( $sPREVIOUSHANDLER );
	return !traperror();
}


/**
 * Meant to replace error handler.
 *
 * @return integer number of errors
 */
function traperror( $reset = 1 )
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
 * @param string IMG ALT text
 * @param string TITLE text (IMG and A link)
 * @param string IMG SRC
 * @param string HREF url
 * @param integer image width
 * @param integer image height
 */
function action_icon( $alt, $title, $img, $url, $width = 13, $height = 13 )
{
	return '<a href="'.$url.'" title="'.$title.'"><img src="img/'.$img.'" width="'.$width.
						'" height="'.$height.'" class="middle" alt="'.$alt.'" title="'.$title.'" /></a> ';
}


/**
 * Get properties of an icon.
 *
 * @param string|File icon for what (special purpose or File object)
 * @param string what to return for that icon ('file', 'url', 'size' {@link imgsize()})
 * @param string additional parameter (for 'size' {@link imgsize()})
 */
function getIcon( $for, $what = 'imgtag', $param = '' )
{
	global $map_iconfiles;
	global $basepath, $admin_subdir, $baseurl;

	if( is_a( $for, 'file' ) )
	{
		$iconfile = $for->getIconPath();
	}
	elseif( isset( $map_iconfiles[$for] ) && isset( $map_iconfiles[$for]['file'] ) )
	{
		$iconfile = $map_iconfiles[$for]['file'];
	}
	else
	{
		$iconfile = false;
	}

	/** @debug quite time consuming**/
	if( $iconfile === false || !file_exists( $basepath.$iconfile ) )
	{
		return '<div class="error">[no image for '.var_export( $for, true ).'!]</div>';
		return false;
	}

	switch( $what )
	{
		case 'file':
			return $iconfile;

		case 'imgtag':
		case 'url':
			$iconurl = $baseurl.$iconfile;
			if( $what == 'url' )
			{
				return $iconurl;
			}

		case 'imgtag':
			$r = '<img class="middle" src="'.$iconurl.'" '
					.getIconSize( $iconfile, 'string' )
					#.imgsize( $admin_url.'img/'.$iconfile, 'string' ) // time consuming!
					.' alt="';

			if( is_a( $for, 'file' ) )
			{ // extension as alt-tag for file icons
				if( $for->isDir() )
				{
					$r .= /* TRANS: short for '<directory>' */ T_('&lt;dir&gt;');
				}
				$r .= $for->getExt();
			}
			elseif( isset( $map_iconfiles[$for]['alt'] ) )
			{ // alt-tag from $map_iconfiles
				$r .= $map_iconfiles[$for]['alt'];
			}
			else
			{ // $for as alt-tag
				$r .= $for;
			}

			$r .= '" />';
			break;

		default:
			echo 'unknown what: '.$what;
	}

	return $r;
}


/**
 * Get image size for an icon.
 *
 * @uses $map_iconsizes, $map_iconfiles
 * @param string Icon path relative to {@link $basepath}
 * @param string what property/format to get: 'width', 'height', 'widthxheight',
 *               'string' (as for img tags), else 'widthheight' (array)
 */
function getIconSize( $iconpath, $param = 'widthheight' )
{
	global $basepath, $map_iconsizes, $Debuglog;

	if( isset( $map_iconsizes[ $iconpath ] ) )
	{
		switch( $param )
		{
			case 'width': return $map_iconsizes[$iconpath][0];
			case 'height': return $map_iconsizes[$iconpath][1];
			case 'widthxheight': return $map_iconsizes[$iconpath][0].'x'.$map_iconsizes[$iconpath][1];
			case 'width': return $map_iconsizes[$iconpath][0];
			case 'string': return 'width="'.$map_iconsizes[$iconpath][0]
														.'" height="'.$map_iconsizes[$iconpath][1].'"';
			default: return $map_iconsizes[$iconpath];
		}
	}
	else
	{
		$Debuglog->add( 'No iconsize for ['.$iconpath.']', 'icons' );

		$map_iconsizes[ $iconpath ] = imgsize( $basepath.$iconpath );
		return getIconSize( $iconpath, $param );
	}
}


/**
 * Validate ISO date
 */
function make_valid_date( $date, $time )
{
	global $Messages;

	if( ! preg_match( '#^\d\d\d\d-\d\d-\d\d$#', $date ) )
	{
		$Messages->add( T_('Date is invalid'), 'error' );
		$date = '2000-01-01';
	}

	if( ! preg_match( '#^\d\d:\d\d:\d\d$#', $time ) )
	{
		$Messages->add( T_('Time is invalid'), 'error' );
		$time = '00:00:00';
	}

	return $date.' '.$time;
}

/*
 * $Log$
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
 * Revision 1.14  2004/11/26 19:38:35  fplanque
 * no message
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
 * Revision 1.10  2004/11/05 15:44:31  blueyed
 * no message
 *
 * Revision 1.9  2004/11/05 12:48:04  blueyed
 * Debug output beautified
 *
 * Revision 1.7  2004/11/03 00:58:02  blueyed
 * update
 *
 * Revision 1.6  2004/11/02 10:46:58  fplanque
 * no message
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