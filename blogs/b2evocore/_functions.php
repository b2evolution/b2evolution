<?php
/**
 * General purpose functions
 * 
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package b2evocore
 * @author This file built upon code from original b2 - http://cafelog.com/
 */
require_once( dirname(__FILE__). '/_functions_cats.php' );
require_once( dirname(__FILE__). '/_functions_blogs.php' );
require_once( dirname(__FILE__). '/_functions_bposts.php' );
require_once( dirname(__FILE__). '/_functions_users.php' );
require_once( dirname(__FILE__). '/_functions_trackback.php' );
require_once( dirname(__FILE__). '/_functions_pingback.php' );
require_once( dirname(__FILE__). '/_functions_pings.php' );
require_once( dirname(__FILE__). '/_functions_skins.php' );
require_once( dirname(__FILE__). '/_functions_errors.php' );
require_once( dirname(__FILE__). '/_functions_antispam.php' );
if( !isset( $use_html_checker ) ) $use_html_checker = 1;
if( $use_html_checker ) require_once( dirname(__FILE__). '/_class_htmlchecker.php' );


/* functions... */

/**
 * Connect to MySQL database
 *
 * {@internal dbconnect(-) }}
 *
 * @deprecated
 */
function dbconnect()
{
	global $connexion;

	$connexion = mysql_connect( DB_HOST, DB_USER, DB_PASSWORD )
		or die( T_('Can\'t connect to the database server. MySQL said:'). '<br />'. mysql_error());

	$connexionbase = mysql_select_db( DB_NAME )
		or die( sprintf(T_('Can\'t connect to the database %s. MySQL said:'), DB_NAME ). '<br />'. mysql_error());

	return(($connexion && $connexionbase));
}


/**
 * Report MySQL errors in detail.
 *
 * {@internal mysql_oops(-) }}
 *
 * @param string The query which led to the error
 *
 * @return boolean success?
 */
function mysql_oops($sql_query)
{
	$error  = '<p class="error">'. T_('Oops, MySQL error!'). '</p>'
		. '<p>Your query:<br /><code>'. $sql_query. '</code></p>'
		. '<p>MySQL said:<br /><code>'. mysql_error(). ' (error '. mysql_errno(). ')</code></p>';
	die($error);
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
	global $Renderer;

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
			// use as a form value: escapes quotes and < > but leaves code alone
			$content = htmlspecialchars( $content );
			$content = str_replace('"', '&quot;', $content );
			$content = str_replace("'", '&#039;', $content );
			$content = str_replace('<', '&lt;', $content );
			$content = str_replace(">", '&gt;', $content );
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
function format_to_post( $content, $autobr = 0, $is_comment = 0 )
{
	global $use_balanceTags, $use_html_checker, $use_security_checker;
	global $allowed_tags, $allowed_attribues, $uri_attrs, $allowed_uri_scheme;
	global $comments_allowed_tags, $comments_allowed_attribues, $comments_uri_attrs, $comments_allowed_uri_scheme;

	// Replace any & that is not a character or entity reference with &amp;
	$content= preg_replace( '/&(?!#[0-9]+;|#x[0-9a-fA-F]+;|[a-zA-Z_:][a-zA-Z0-9._:-]*;)/', '&amp;', $content );

	if( $autobr )
	{ // Auto <br />:
		// may put brs in the middle of multiline tags...
		$content = autobrize($content);
	}

	if( $use_balanceTags )
	{	// Auto close open tags:
		$content = balanceTags($content, $is_comment);
	}

	if( $use_html_checker )
	{	// Check the code:
		if( ! $is_comment )
		{
			$checker = & new SafeHtmlChecker( $allowed_tags, $allowed_attribues,
																			$uri_attrs, $allowed_uri_scheme );
		}
		else
		{
			$checker = & new SafeHtmlChecker( $comments_allowed_tags, $comments_allowed_attribues,
																			$comments_uri_attrs, $comments_allowed_uri_scheme );
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
			errors_add( 'Illegal markup found: '.htmlspecialchars($matches[1]) );
		}
		// Styling restictions:
		$matches = array();
		if( $is_comment && preg_match ('#\s(style|class|id)\s*=#i', $check, $matches) )
		{
			errors_add( 'Unallowed CSS markup found: '.htmlspecialchars($matches[1]) );
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

	// $content = preg_replace("/<title>(.+?)<\/title>/","",$content);
	// $content = preg_replace("/<category>(.+?)<\/category>/","",$content);

	// Convert highbyte non ASCII/UTF-8 chars to urefs:
	if (locale_charset(false) != 'utf-8')
	{	// This is a single byte charset
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
	{	// unicode, xml...
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

/*
 * mysql2date(-)
 *
 * with enhanced format string
 */
function mysql2date( $dateformatstring, $mysqlstring, $useGM = false )
{
	$m = $mysqlstring;
	if( empty($m) )	return false;

	// Get a timestamp:
	$unixtimestamp = mktime(substr($m,11,2),substr($m,14,2),substr($m,17,2),substr($m,5,2),substr($m,8,2),substr($m,0,4));

	return date_i18n( $dateformatstring, $unixtimestamp, $useGM );
}


/*
 * date_i18n(-)
 *
 * date internationalization: same as date() formatting but with i18n support
 */
function date_i18n( $dateformatstring, $unixtimestamp, $useGM = false )
{
	global $month, $month_abbrev, $weekday, $weekday_abbrev;

	$datemonth = date('m', $unixtimestamp);
	$dateweekday = date('w', $unixtimestamp);

	if( $dateformatstring == 'isoZ' )
	{ // full ISO 8601 format
		$dateformatstring = 'Y-m-d\TH:i:s\Z';
	}

	if( $useGM )
	{ // We want a Greenwich Meridian time:
		$j = gmdate($dateformatstring, $unixtimestamp - (get_settings('time_difference') * 3600));
	}
	else
	{	// We want default timezone time:
		$dateformatstring = ' '.$dateformatstring; // will be removed later

		// echo $dateformatstring, '<br />';

		// weekday:
		$dateformatstring = preg_replace("/([^\\\])l/", '\\1@@@\\l@@@', $dateformatstring);
		// weekday abbrev:
		$dateformatstring = preg_replace("/([^\\\])D/", '\\1@@@\\D@@@', $dateformatstring);
		// month:
		$dateformatstring = preg_replace("/([^\\\])F/", '\\1@@@\\F@@@', $dateformatstring);
		// month abbrev:
		$dateformatstring = preg_replace("/([^\\\])M/", '\\1@@@\\M@@@', $dateformatstring);

		$dateformatstring = substr($dateformatstring, 1, strlen($dateformatstring)-1);

		// echo $dateformatstring, '<br />';

		$j = date($dateformatstring, $unixtimestamp);

		// weekday:
		$j = str_replace( '@@@l@@@', T_($weekday[$dateweekday]), $j);
		// weekday abbrev:
		$j = str_replace( '@@@D@@@', T_($weekday_abbrev[$dateweekday]), $j);
		// month:
		$j = str_replace( '@@@F@@@', T_($month[$datemonth]), $j);
		// month abbrev:
		$j = str_replace( '@@@M@@@', T_($month_abbrev[$datemonth]), $j);
	}

	return $j;
}


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


/*
 * is_email(-)
 *
 * Check that email address looks valid
 */
function is_email($user_email) {
	#$chars = "/^([a-z0-9_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,4}\$/i";
	$chars = '/^.+@[^\.].*\.[a-z]{2,}$/i';
	if(strstr($user_email, '@') && strstr($user_email, '.')) {
		return (bool)(preg_match($chars, $user_email));
	} else {
		return false;
	}
}


/**
 * Get setting from DB (cached)
 *
 * {@internal get_settings(-) }}
 *
 * @param string setting to retrieve
 */
function get_settings( $setting )
{
	global $DB, $tablesettings, $cache_settings;

	if( empty($cache_settings) || !isset($cache_settings->$setting) )
	{
		$sql = "SELECT set_name, set_value FROM $tablesettings";
		
		$q = $DB->get_results( $sql );
		
		foreach( $q as $loop_q )
		{
			$cache_settings->{$loop_q->set_name} = $loop_q->set_value;
		}
	}
	if( isset($cache_settings->$setting) )
	{
		return $cache_settings->$setting;
	}
	else
	{
		debug_log("Setting '$setting' not defined.");
		return false;
	}
}


/**
 * overrides settings that have been read from DB
 *
 * @param string setting name
 * @param mixed setting value
 */
function set_settings( $setting, $value )
{
	global $cache_settings;

	$cache_settings->$setting = $value;

	return true;
}


/**
 * changes settings into DB
 *
 * @param string setting name
 * @param mixed setting value
 */
function change_settings( $setting, $value, $escape = true )
{
	global $cache_settings, $tablesettings, $DB;
	
	// change the cached settings
	$cache_settings->$setting = $value;
	
	if( $escape )
	{
		$DB->escape($value);
	}
	$query = "UPDATE $tablesettings SET set_value = '".$value."' WHERE set_name = '$setting'";
	
	return $DB->query( $query );
}


function alert_error( $msg )
{ // displays a warning box with an error message (original by KYank)
	?>
	<html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
	<head>
	<script language="JavaScript">
	<!--
	alert("<?php echo $msg ?>");
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


function xmlrpc_getposttitle($content) {
	global $post_default_title;
	if (preg_match('/<title>(.+?)<\/title>/is', $content, $matchtitle)) {
		$post_title = $matchtitle[0];
		$post_title = preg_replace('/<title>/si', '', $post_title);
		$post_title = preg_replace('/<\/title>/si', '', $post_title);
	} else {
		$post_title = $post_default_title;
	}
	return($post_title);
}


function xmlrpc_getpostcategory($content) {
	global $post_default_category;
	if (preg_match('/<category>(.+?)<\/category>/is', $content, $matchcat)) {
		$post_category = $matchcat[0];
		$post_category = preg_replace('/<category>/si', '', $post_category);
		$post_category = preg_replace('/<\/category>/si', '', $post_category);

	} else {
		$post_category = $post_default_category;
	}
	return($post_category);
}

/*
 * xmlrpc_removepostdata(-)
 */
function xmlrpc_removepostdata($content)
{
	$content = preg_replace('/<title>(.+?)<\/title>/si', '', $content);
	$content = preg_replace('/<category>(.+?)<\/category>/si', '', $content);
	$content = trim($content);
	return($content);
}


/*
 * xmlrpc_displayresult(-)
 *
 * fplanque: created
 */
function xmlrpc_displayresult( $result, $log = '' )
{
	if( ! $result )
	{
		echo T_('No response!'),"<br />\n";
		return false;
	}
	elseif( $result->faultCode() )
	{	// We got a remote error:
		echo T_('Remote error'), ': ', $result->faultString(), ' (', $result->faultCode(), ")<br />\n";
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
		echo T_('Response'), ': ', $value_arr, "<br />\n";
		debug_fwrite($log, $value_arr);
	}
	else
	{
		echo T_('Response'), ': ', $value ,"<br />\n";
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

function debug_fwrite($fp, $string) {
	global $debug;
	if ($debug == 1) {
		fwrite($fp, $string);
	}
}

function debug_fclose($fp) {
	global $debug;
	if ($debug == 1) {
		fclose($fp);
	}
}



/*
 balanceTags

 Balances Tags of string using a modified stack.

 @param text      Text to be balanced
 @return          Returns balanced text
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
	{	// That stupid PHP behaviour consisting of adding slashes everywhere is unfortunately on
		if( is_array( $mixed ) )
		{
			foreach($mixed as $k => $v)
			{
				$mixed[$k] = remove_magic_quotes( $v );
			}
		}
		else
		{
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
 * @param mixed Default value or TRUE if user input required
 * @param boolean Do we need to memorize this to regenerate the URL for this page?
 * @param boolean Override if variable already set
 * @param boolean Force setting of variable to default?
 * @return mixed Final value of Variable, or false if we don't force setting and and did not set
 *
 * @todo add option to override what's already set. DONE.
 */
function param(	$var, $type = '',	$default = '', $memorize = false, $override = false, $forceset = true )
{
	global $$var;
	global $global_param_list;

	// Check if already set
	// WARNING: when PHP register globals is ON, COOKIES get priority over GET and POST with this!!!
	if( !isset( $$var ) || $override )
	{
		if( isset($_POST[$var]) )
		{
			$$var = remove_magic_quotes( $_POST[$var] );
			// echo "$var=".$$var." set by POST!<br/>";
		}
		elseif( isset($_GET["$var"]) )
		{
			$$var = remove_magic_quotes($_GET[$var]);
			// echo "$var=".$$var." set by GET!<br/>";
		}
		elseif( isset($_COOKIE[$var]))
		{
			$$var = remove_magic_quotes($_COOKIE[$var]);
			// echo "$var=".$$var." set by COOKIE!<br/>";
		}
		elseif( $default === true )
		{
			die( '<p class="error">'.sprintf( T_('Parameter %s is required!'), $var ).'</p>' );
		}
		elseif( $forceset )
		{
			$$var = $default;
			// echo "$var=".$$var." set to default<br/>";
		}
		else
		{ // don't set the variable
			return false;
		}
	}
	else
	{	// Variable was already set but we need to remove the auto quotes
		$$var = remove_magic_quotes($$var);
		// echo $var, ' already set';
		/*	if($var == 'post_extracats' )
			{ echo "$var=".$$var." was already set! count = ", count($$var),"<br/>";
				foreach( $$var as $tes )
				{
					echo '<br>value=', $tes;

				}
			} */
	}

	// type will be forced even if it was set before and not overriden
	if( !empty($type) )
	{	// Force the type
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
		}
	}

	if( $memorize )
	{	// Memorize this parameter
		if( !isset($global_param_list) ) $global_param_list = array();
		$thisparam = array( 'var' => $var, 'type' => $type, 'default' => $default );
		$global_param_list[] = $thisparam;
	}

	// echo $var, '(', gettype($$var), ')=', $$var, '<br />';
	return $$var;
}


/*
 * regenerate_url(-)
 *
 * Regenerate current URL from parameters
 * This may clean it up
 * But it is also useful when generating static pages: you cannot rely on $_REQUEST[]
 *
 * fplanque: created
 */
function regenerate_url( $ignore = '', $set = '', $pagefileurl='' )
{
	global $global_param_list;

	if( $ignore == '' )
		$ignore = array( );
	elseif( !is_array($ignore) )
		$ignore = array( $ignore );

	if( $set == '' )
		$set = array( );
	elseif( !is_array($set) )
		$set = array( $set );

	$params = array();
	foreach( $global_param_list as $thisparam )
	{
		$var = $thisparam['var'];
		$type = $thisparam['type'];
		$defval = $thisparam['default'];

		if( in_array( $var, $ignore ) )
		{	// we don't want to include that one
			continue;
		}

		// Special cases:
		switch( $var )
		{
			case 'catsel':
			{
				global $catsel;
				if( (! empty($catsel)) && (strpos( $cat, '-' ) === false) )
				{	// It's worthwhile retransmitting the catsels
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
				if( !empty($value) && ($value != $defval) )
				{ // Value exists and is not set to default value:
					$params[] = $var.'='.$value;
				}
			}
		}
	}


	if( ! empty( $set ) )
	{
		$params = array_merge( $params, $set );
	}

	$url = (empty($pagefileurl)) ? get_bloginfo('url') : $pagefileurl;

	if( ! empty( $params ) )
	{
		$url .= '?'.implode( '&amp;', $params );
	}

	return $url;
}


/*
 * get_path(-)
 */
function get_path( $which='' )
{
	global $core_subdir, $skins_subdir, $basepath;

	if( !isset($basepath) )
	{	// Determine the basepath:
		$current_folder = str_replace( '\\', '/', dirname(__FILE__) );
		$last_pos = 0;
		while( $pos = strpos( $current_folder, $core_subdir, $last_pos ) )
		{	// make sure we use the last occurrence
			$basepath = substr( $current_folder, 0, $pos-1 );
			$last_pos = $pos+1;
		}
	}

	switch( $which )
	{
		case 'skins':
			return $basepath.'/'.$skins_subdir;

	}

	return $basepath;
}

/*
 * autoquote(-)
 */
function autoquote( & $string )
{
	if( strpos( $string, "'" ) !== 0 )
	{	// no quote at position 0
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
	{	// Empty URL, no problem
		return false;
	}

	if( ! preg_match('/^([a-zA-Z][a-zA-Z0-9+-.]*):[0-9]*/', $url, $matches) )
	{	// Cannot find URI scheme
		return T_('Invalid URL');
	}

	$scheme = strtolower($matches[1]);
	if(!in_array( $scheme, $allowed_uri_scheme ))
	{	// Scheme not allowed
		return T_('URI scheme not allowed');
	}

	// Search for blocked URLs:
	if( $block = antispam_url($url) )
	{
		if( $debug ) return 'Url contains blaclisted word: ['.$block.']';
		return T_('URL not allowed');
	}

	return false;		// OK
}


/**
 * wrap pre around var_dump(), better debuggin'
 *
 * {@internal pre_dump(-) }}
 *
 * @author blueyed
 *
 * @param mixed variable to dump
 * @param string title to display
 */
function pre_dump($dump, $title = '')
{
	echo '<pre>';
	if( $title != '' )
	{
		echo $title. ': <br />';
	}
	var_dump($dump);
	echo '</pre>';
}


$debug_messages = array();
/**
 * Log a debug string to be displayed later. (Typically at the end of the page)
 *
 * {@internal debug_log(-) }}
 *
 * @param boolean true to force output
 */
function debug_log( $message )
{
	global $debug_messages;

	$debug_messages[] = $message;
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
	global $querycount;
	global $debug_messages;
	global $DB;

	if( $debug || $force )
	{
		echo '<hr style="clear: both;" /><h2>Debug info</h2>';

		echo 'Page processing time: ', number_format(timer_stop(),3), ' seconds<br/>';

		if( count( $debug_messages ) )
		{
			echo '<h3>Debug messages</h3><ul>';
			foreach( $debug_messages as $message )
			{
				echo '<li>', format_to_output( $message, 'htmlbody' ), '</li>';
			}
			echo '</ul>';
		}

		echo '<h3>DB</h3>';

		echo 'Old style queries: ', $querycount, '<br />';
		echo 'DB queries: ', $DB->num_queries, '<br />';

		$DB->dump_queries();
	}
}

/**
 * Output Buffer handler.
 *
 * It will be set in /blogs/evocore/_main.php and handle the output.
 * It strips every line and generates a md5-ETag, which is checked against the one eventually
 * being sent by the browser.
 *
 * @author blueyed
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


	// strip each line
	$output = explode("\n", $output);
	$out = '';
	foreach ($output as $v)
		$out .= trim($v) . "\n";


	if( isset( $use_etags ) )
	{	// Generating ETAG

		// prefix with PUB or AUT.
		if( is_logged_in() )
			$ETag = '"AUT';    // A private page
		else $ETag = '"PUB'; // and public one

		$ETag .= md5( $out );
		header( 'ETag: '. $ETag . '"' );

		// decide to send out or not
		if( isset($_SERVER['HTTP_IF_NONE_MATCH'])
				&& $_SERVER['HTTP_IF_NONE_MATCH'] === $ETag )
		{ // check ETag
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


	header( 'Content-Length: '. strlen($out) );
	return $out;

	/* {{{ additional things we could do in this handler
		- We could have a global $lastmodified that would be checked against according
			header and throw a 304 then, too.
		- global var that reflects "No caching!", if set


		code excerpts below:

		header ("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header ("Pragma: no-cache"); // HTTP/1.0

		header('Cache-Control: max-age=3600, must-revalidate');

		# get LastModified from db entries
		if ($site->subMenu[0] == 'links'){
			$sql = 'SELECT UNIX_TIMESTAMP(dt) FROM tq_links WHERE submenu="links&' .
								$site->subMenu[1] . '" AND unix_timestamp(dt)-' . $lastModified . '>0 ORDER BY dt DESC';
			$result = $site->dbquery($sql, 'get_lm_linkdb', true);
			if (mysql_numrows($result) > 0) $lastModified = mysql_result($result, 0);
		}

		# check and react on "If-Modified-Since" header
		if(isset($headers["If-Modified-Since"]) && $_SERVER['REQUEST_METHOD'] != 'POST') {
			$arraySince = explode(";", $headers["If-Modified-Since"]);
			$since = strtotime($arraySince[0]);
			if ($since >= $lastModified) $refresh = false;
		}

		header('Expires: ' . $header_expires);
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', $lastModified));
		//header("Cache-Control: max-age=86400, must-revalidate");
		//header("Cache-Control: max-age=10, must-revalidate");
		}}}*/
}

?>
