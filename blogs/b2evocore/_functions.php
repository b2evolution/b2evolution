<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file built upon code from original b2 - http://cafelog.com/
 */

require_once dirname(__FILE__)."/$core_dirout/$conf_subdir/_antispam.php";
require_once (dirname(__FILE__)."/_functions_cats.php");
require_once (dirname(__FILE__)."/_functions_blogs.php");
require_once (dirname(__FILE__)."/_functions_bposts.php");
require_once (dirname(__FILE__)."/_functions_users.php");
require_once (dirname(__FILE__)."/_functions_trackback.php");
require_once (dirname(__FILE__)."/_functions_pingback.php");
require_once (dirname(__FILE__)."/_functions_pings.php");
require_once (dirname(__FILE__)."/_functions_skins.php");
require_once (dirname(__FILE__).'/_functions_errors.php');
if( !isset( $use_html_checker ) ) $use_html_checker = 1;
if( $use_html_checker ) require_once (dirname(__FILE__).'/_class_htmlchecker.php');




/* functions... */

/*
 * dbconnect(-)
 */
function dbconnect()
{
	global $connexion, $dbhost, $dbusername, $dbpassword, $dbname;

	$connexion = mysql_connect($dbhost,$dbusername,$dbpassword) or die( T_('Can\'t connect to the database server. MySQL said:').'<br />'.mysql_error());

	$connexionbase = mysql_select_db( $dbname ) or die( sprintf(T_('Can\'t connect to the database %s. MySQL said:'), $base).'<br />'.mysql_error());

	return(($connexion && $connexionbase));
}


/*
 * mysql_oops(-)
 */
function mysql_oops($sql_query)
{
	$error  = '<p class="error">'.T_('Oops, MySQL error!').'</p>';
	$error .= '<p>Your query:<br /><code>'.$sql_query.'</code></p>';
	$error .= '<p>MySQL said:<br /><code>'.mysql_error().'</code></p>';
	die($error);
}




/***** Formatting functions *****/


/*
 * format_to_output(-)
 *
 * Format the content for being output
 *
 * fplanque : created
 */
function format_to_output( $content, $format = 'htmlbody' )
{
	// echo '<font color=red>format [', $content, ']to: ', $format, '</font>';
	switch( $format )
	{
		case 'raw':
			// do nothing!
			break;

		case 'formvalue':
			// convert_chars() do too much at this time,
			// so temporally commented out.
			// $content = convert_chars($content, 'html');
			$content = htmlspecialchars( $content );
			break;

		case 'xml':
			// Remove the markup:
			// echo 'xml';
			convert_bbcode($content);
			$content = strip_tags($content);

			$content = convert_chars($content, 'xml');
			break;

		case 'htmlhead':
			// Remove the markup:
			convert_bbcode($content);
			$content = strip_tags($content);

			$content = convert_chars($content, 'html');
			break;

		case 'xmlattr':
			// Remove the markup:
			convert_bbcode($content);
			$content = strip_tags($content);

			$content = convert_chars($content, 'xml');
			$content = str_replace('"', '&quot;', $content );
			break;

		case 'htmlattr':
			// Remove the markup:
			convert_bbcode($content);
			$content = strip_tags($content);

			$content = convert_chars($content, 'html');
			$content = str_replace('"', '&quot;', $content );
			break;
			
		case 'entityencoded':
			convert_bbcode($content);
			convert_gmcode($content);
			$content = make_clickable($content);
			convert_smilies($content);
			$content = convert_chars($content, 'html');
			phpcurlme( $content );
			$content = htmlspecialchars( $content );
			break;

		case 'htmlbody':
		default:
			// echo 'html';
			convert_bbcode($content);
			convert_gmcode($content);
			$content = make_clickable($content);
			convert_smilies($content);
			$content = convert_chars($content, 'html');
			phpcurlme( $content );
	}

	// echo 'formated:', $content;

	return $content;
}


/*
 * format_to_edit(-)
 */
function format_to_edit( $content, $autobr = false )
{
	$content = stripslashes($content);
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
function format_to_post($content, $autobr=0, $is_comment=0)
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
			$checker = new SafeHtmlChecker( $allowed_tags, $allowed_attribues,
																			$uri_attrs, $allowed_uri_scheme );
		}
		else
		{
			$checker = new SafeHtmlChecker( $comments_allowed_tags, $comments_allowed_attribues,
																			$comments_uri_attrs, $comments_allowed_uri_scheme );
		}
		
		$checker->check(stripslashes($content));
	}

	if( !isset( $use_security_checker ) ) $use_security_checker = 1;
	if( $use_security_checker )
	{
		// Security checking:
		$check = stripslashes($content);
		// Open comments or '<![CDATA[' are dangerous
		$check = str_replace('<!', '<', $check);
		// # # are delimiters
		// i modifier at the end means caseless
		$matches = array();
		// onclick= etc...
		if( preg_match ('#\s(on[a-z]+)\s*=#i', $check, $matches)
			// action=, background=, cite=, classid=, codebase=, data=, href=, longdesc=, profile=, src=
			// usemap=
			|| preg_match ('#=["\'\s]*(javascript|vbscript|about):#i', $matches)
			|| preg_match ('#\<\/?\s*(frame|iframe|applet|object)#i', $matches) )
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

	// $content = addslashes($content);
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
function zeroise($number,$threshold) { // function to add leading zeros when necessary
	$l=strlen($number);
	if ($l<$threshold)
		for ($i=0; $i<($threshold-$l); $i=$i+1) { $number='0'.$number;	}
	return($number);
	}

/*
 * backslashit(-)
 */
function backslashit($string) {
	$string = preg_replace('/([a-z])/i', '\\\\\1', $string);
	return $string;
}



/*
 * convert_chars(-)
 *
 * Convert special chars
 *
 * fplanque: simplified
 * sakichan: pregs instead of loop
 */
function convert_chars( $content, $flag='html' )
{
	$newcontent = "";

	global $b2_htmltrans, $b2_htmltranswinuni;

	$content = preg_replace("/<title>(.+?)<\/title>/","",$content);
	$content = preg_replace("/<category>(.+?)<\/category>/","",$content);

	$content = strtr($content, $b2_htmltrans);

	if (locale_charset(false) == 'iso-8859-1') 
	{
 		$content = preg_replace_callback(
 			'/[\x80-\xff]/',
 			create_function( '$j', 'return "&#".ord($j[0]).";";' ),
 			$content);
 	}
 
 	if ($flag == "html") 
	{
 		$newcontent = preg_replace('/&(?!#)/', '&amp;', $content);
 	}
 	else 
	{	// unicode, xml...
 		$newcontent = preg_replace('/&(?!#)/', '&#38;', $content);
 	}

	// now converting: Windows CP1252 => Unicode (valid HTML)
	// (if you've ever pasted text from MSWord, you'll understand)
	$newcontent = strtr($newcontent, $b2_htmltranswinuni);

	// you can delete these 2 lines if you don't like <br /> and <hr />
	$newcontent = str_replace("<br>","<br />",$newcontent);
	$newcontent = str_replace("<hr>","<hr />",$newcontent);

	return($newcontent);
}

/*
 * convert_bbcode(-)
 */
function convert_bbcode( & $content)
{
	global $b2_bbcode, $use_bbcode;
	if ($use_bbcode) {
		$content = preg_replace($b2_bbcode["in"], $b2_bbcode["out"], $content);
	}
	$content = convert_bbcode_email($content);
}

function convert_bbcode_email($content)
{
	$bbcode_email["in"] = array(
		'#\[email](.+?)\[/email]#eis',
		'#\[email=(.+?)](.+?)\[/email]#eis'
	);
	$bbcode_email["out"] = array(
		"'<a href=\"mailto:'.antispambot('\\1').'\">'.antispambot('\\1').'</a>'",		// E-mail
		"'<a href=\"mailto:'.antispambot('\\1').'\">\\2</a>'"
	);

	$content = preg_replace($bbcode_email["in"], $bbcode_email["out"], $content);
	return ($content);
}

/*
 * convert_gmcode(-)
 */
function convert_gmcode( & $content)
{
	global $b2_gmcode, $use_gmcode;
	if ($use_gmcode) {
		$content = preg_replace($b2_gmcode["in"], $b2_gmcode["out"], $content);
	}
}



/* sorts the smilies' array by length
  this is important if you want :)) to superseede :) for example
*/
function smiliescmp($a, $b)
{
	if(($diff = strlen($b) - strlen($a)) == 0)
	{
		return strcmp($a, $b);
	}
	return $diff;
}

/*
 * convert_smilies(-)
 */
function convert_smilies( & $content)
{
	global $smilies_directory, $use_smilies;
	global $b2smilies, $b2_smiliessearch, $b2_smiliesreplace;
	if ($use_smilies)
	{
		if( ! isset( $b2_smiliessearch ) )
		{	// We haven't prepared the smilies yet
			$b2_smiliessearch = array();
			$tmpsmilies = $b2smilies;
			uksort($tmpsmilies, 'smiliescmp');

			foreach($tmpsmilies as $smiley => $img) 
			{
				$b2_smiliessearch[] = $smiley;
				$smiley_masked = '';
				for ($i = 0; $i < strlen($smiley); $i++ ) 
				{
					/* fplanque: with the #160s we prevent recurrent replacing... bleh :(
						 fplanque changed $smiley_masked .= substr($smiley, $i, 1).chr(160);
						 better way: (added benefit: handles ' and " escaping for alt attribute!... needed in :') )
					*/
					$smiley_masked .=  '&#'.ord(substr($smiley, $i, 1)).';';
				}
				
				$b2_smiliesreplace[] = "<img src='$smilies_directory/$img' alt='$smiley_masked' class='middle' />";
			}
		}

		// REPLACE: 
		$content = str_replace($b2_smiliessearch, $b2_smiliesreplace, $content);
	}
}


/*
 * make_clickable(-)
 *
 * original function: phpBB, extended here for AIM & ICQ
 * fplanque restricted :// to http:// and mailto://
 */
function make_clickable($text)
{
	global $use_autolink;

	if( ! $use_autolink )
	{
		return $text;
	}

	$ret = " " . $text;

	$ret = preg_replace("#([\n ])(http|mailto)://([^, <>{}\n\r]+)#i", "\\1<a href=\"\\2://\\3\">\\2://\\3</a>", $ret);

	$ret = preg_replace("#([\n ])aim:([^,< \n\r]+)#i", "\\1<a href=\"aim:goim?screenname=\\2\\3&message=Hello\">\\2\\3</a>", $ret);

	$ret = preg_replace("#([\n ])icq:([^,< \n\r]+)#i", "\\1<a href=\"http://wwp.icq.com/scripts/search.dll?to=\\2\\3\">\\2\\3</a>", $ret);

	$ret = preg_replace("#([\n ])www\.([a-z0-9\-]+)\.([a-z0-9\-.\~]+)((?:/[^,< \n\r]*)?)#i", "\\1<a href=\"http://www.\\2.\\3\\4\">www.\\2.\\3\\4</a>", $ret);

	$ret = preg_replace("#([\n ])([a-z0-9\-_.]+?)@([^,< \n\r]+)#i", "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $ret);

	$ret = substr($ret, 1);
	return($ret);
}


/*
 * phpcurlme(-)
 *
 * by Matt - http://www.photomatt.net/scripts/phpcurlme
 */
function phpcurlme( & $string, $language = 'en')
{
	global $use_smartquotes;
	if( $use_smartquotes )
	{
    // This should take care of the single quotes
    $string = preg_replace("/'([dmst])([ .,?!\)\/<])/i","&#8217;$1$2",$string);
    $string = preg_replace("/'([lrv])([el])([ .,?!\)\/<])/i","&#8217;$1$2$3",$string);
    $string = preg_replace("/([^=])(\s+)'([^ >])?(.*?)([^=])'(\s*)([^>&])/S","$1$2&#8216;$3$4$5&#8217;$6$7",$string);

    // time for the doubles
    $string = preg_replace('/([^=])(\s+)"([^ >])?(.*?)([^=])"(\s*)([^>&])/S',"$1$2&#8220;$3$4$5&#8221;$6$7",$string);
    // multi-paragraph
    $string = preg_replace('/<p>"(.*)<\/p>/U',"<p>&#8220;$1</p>",$string);

    // not a quote, but whatever
    $string = str_replace('---','&#8212;',$string);
    $string = str_replace('--','&#8211;',$string);
  }
}



/***** // Formatting functions *****/

/*
 * mysql2date(-)
 *
 * with enhanced format string
 */
function mysql2date($dateformatstring, $mysqlstring, $useGM = false)
{
	global $month, $weekday;
	global $time_difference;
	$m = $mysqlstring;
	if (empty($m))
	{
		return false;
	}
	// Get a timestamp:
	$i = mktime(substr($m,11,2),substr($m,14,2),substr($m,17,2),substr($m,5,2),substr($m,8,2),substr($m,0,4));

	if( $useGM )
	{ // We want a Greenwich Meridian time:
		$j = gmdate($dateformatstring, $i - ($time_difference * 3600));
	}
	else
	{	// We want default timezone time:
		$j = date_i18n($dateformatstring, $i);
	}
	#		echo $i." ".$mysqlstring;
	return $j;
}


/*
 * date_i18n(-)
 *
 * date internationalization: same as date() formatting but with i18n support
 */
function date_i18n( $dateformatstring, $unixtimestamp ) 
{
	global $month, $month_abbrev, $weekday, $weekday_abbrev;

	$datemonth = date('m', $unixtimestamp);
	$dateweekday = date('w', $unixtimestamp);

	$dateformatstring = ' '.$dateformatstring;

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

	return $j;
}


function get_weekstartend($mysqlstring, $start_of_week)
{
	$my = substr($mysqlstring,0,4);
	$mm = substr($mysqlstring,8,2);
	$md = substr($mysqlstring,5,2);
	$day = mktime(0,0,0, $md, $mm, $my);
	$weekday = date('w',$day);
	$i = 86400;
	while ($weekday > $start_of_week) {
		$weekday = date('w',$day);
		$day = $day - 86400;
		$i = 0;
	}
	$week['start'] = $day + 86400 - $i;
	$week['end']   = $day + 691199;
	return ($week);
}


function antispambot($emailaddy, $mailto=0) {
	$emailNOSPAMaddy = '';
	srand ((float) microtime() * 1000000);
	for ($i = 0; $i < strlen($emailaddy); $i = $i + 1) {
		$j = floor(rand(0, 1+$mailto));
		if ($j==0) {
			$emailNOSPAMaddy .= '&#'.ord(substr($emailaddy,$i,1)).';';
		} elseif ($j==1) {
			$emailNOSPAMaddy .= substr($emailaddy,$i,1);
		} elseif ($j==2) {
			$emailNOSPAMaddy .= '%'.zeroise(dechex(ord(substr($emailaddy, $i, 1))), 2);
		}
	}
	$emailNOSPAMaddy = str_replace('@','&#64;',$emailNOSPAMaddy);
	return $emailNOSPAMaddy;
}


/*
 * is_email(-)
 *
 * Check that email address looks valid
 */
function is_email($user_email) {
	$chars = "/^([a-z0-9_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,4}\$/i";
	if(strstr($user_email, '@') && strstr($user_email, '.')) {
		if (preg_match($chars, $user_email)) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}


/*
 * get_settings(-)
 */
function get_settings($setting)
{
	global $tablesettings,$querycount,$cache_settings,$use_cache;
	if ((empty($cache_settings)) OR (!$use_cache))
	{
		$sql = "SELECT * FROM $tablesettings";
		$result = mysql_query($sql) or mysql_oops( $sql );
		$querycount++;
		$myrow = mysql_fetch_object($result);
		$cache_settings = $myrow;
	}
	else
	{
		$myrow = $cache_settings;
	}
	return($myrow->$setting);
}





function alert_error($msg) 
{ // displays a warning box with an error message (original by KYank)
	global $default_language;
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
 * fplanque: created
 */
function xmlrpc_displayresult( $result, $log = '' )
{
	if ($result)
	{
		if (!$result->value())
		{
			echo $result->faultCode().' -- '.$result->faultString()."<br />\n";
			debug_fwrite($log, $result->faultCode().' -- '.$result->faultString());
			return false;
		}
		else
		{
			$value = xmlrpc_decode($result->value());
			if (is_array($value))
			{
				$value_arr = '';
				foreach($value as $blah)
				{
					$value_arr .= $blah.' |||| ';
				}
				echo "Response: $value_arr <br />\n";
				debug_fwrite($log, $value_arr);
			}
			else
			{
				echo "Response: $value <br />\n";
				debug_fwrite($log, $value);
			}
		}
	}
	else
	{
		echo "No response<br />\n";
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


/*
 * remove_magic_quotes(-)
 */
function remove_magic_quotes( $mixed ) 
{
	if( get_magic_quotes_gpc() )
	{
		if( is_array($mixed) ) 
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


/*
 * param(-)
 *
 * Sets a parameter with values from the request or default
 * except if it's already set!
 *
 * fplanque: created
 * removes magic quotes if they are set automatically by PHP
 * TODO: add option to override what's already set.
 */
function param(
	$var, 					// Variable to set
	$type='',				// Force to boolean"integer"float"string"array"object"null" (since PHP 4.2.0) html
	$default='',  	// Default value or TRUE if user input required
	$memorize=false	// Do we need to memorize this to regenerate the URL
)
{
	global $$var;
	global $global_param_list;

	// Check if already set
	// WARNING: when PHP register globals is ON, COOKIES get priority og GET and POST with this!!!
	if( !isset( $$var ) )
	{
		if( isset($_POST[$var]))
		{
			$$var = remove_magic_quotes($_POST[$var]);
			// echo "$var=".$$var." set by POST!<br/>";
		}
		elseif( isset($_GET["$var"]))
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
			die( sprintf( T_('Parameter %s is required!'), $var ) );
		}
		else
		{
			$$var = $default;
			// echo "$var=".$$var." set to default<br/>";
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


/*
 * validate_url(-)
 *
 * fplanque: 0.8.5: changed return values
 * vegarg: switched to MySQL antispam list
 */
function validate_url( $url, & $allowed_uri_scheme )
{
	global $tableblacklist, $querycount;

	if( empty($url) ) 
	{	// Empty URL, no problem
		return false;		
	}
	
	if( ! preg_match('/^([a-zA-Z][a-zA-Z0-9+-.]*):/', $url, $matches) )
	{	// Cannot find URI scheme
		return T_('Invalid URL'); 
	}

	$scheme = strtolower($matches[1]);
	if(!in_array( $scheme, $allowed_uri_scheme )) 
	{	// Scheme not allowed
		return T_('URI scheme not allowed');
	}

	// Search for blocked URLs:
	$query = "SELECT * FROM $tableantispam";
	$querycount++;
	$q = mysql_query( $query ) or mysql_oops( $query );
	$block_urls = array();
	while( list($id,$tmp) = mysql_fetch_row($q) )
	{
		$block_urls[] = $tmp;
	}
	foreach ($block_urls as $block)
	{
		if( strpos($url, $block) !== false)
		{
			return T_('URL not allowed');
		}
	}

	return false;		// OK
}
?>
