<?php
/**
 * This file sets various arrays and variables for use in b2evolution.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 * @author This file built upon code from original b2 - http://cafelog.com/
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

$b2_version = '0.9.2-CVS';
$new_db_version = 8070;				// next time: 8080

// Investigation for following code by Isaac - http://isaac.beigetower.org/
// $debug = true;
if( isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI']) )
{	// Warning: on some IIS installs it it set but empty!
	// Besides, use of explode is not very efficient so other methods are preferred.
	$Debuglog->add( 'vars: Getting ReqURI from REQUEST_URI', 'hit' );
	$ReqURI = $_SERVER['REQUEST_URI'];
	// Remove params from reqURI:
	$ReqPath = explode( '?', $ReqURI, 2 );
	$ReqPath = $ReqPath[0];
}
elseif( isset($_SERVER['URL']) )
{ // ISAPI
	$Debuglog->add( 'vars: Getting ReqPath from URL', 'hit' );
	$ReqPath = $_SERVER['URL'];
	$ReqURI = isset($_SERVER['QUERY_STRING']) && !empty( $_SERVER['QUERY_STRING'] ) ? ($ReqPath.'?'.$_SERVER['QUERY_STRING']) : $ReqPath;
}
elseif( isset($_SERVER['PATH_INFO']) )
{ // CGI/FastCGI
	$Debuglog->add( 'vars: Getting ReqPath from PATH_INFO', 'hit' );
	$ReqPath = $_SERVER['PATH_INFO'];
	$ReqURI = isset($_SERVER['QUERY_STRING']) && !empty( $_SERVER['QUERY_STRING'] ) ? ($ReqPath.'?'.$_SERVER['QUERY_STRING']) : $ReqPath;
}
elseif( isset($_SERVER['SCRIPT_NAME']) )
{ // Some Odd Win2k Stuff
	$Debuglog->add( 'vars: Getting ReqPath from SCRIPT_NAME', 'hit' );
	$ReqPath = $_SERVER['SCRIPT_NAME'];
	$ReqURI = isset($_SERVER['QUERY_STRING']) && !empty( $_SERVER['QUERY_STRING'] ) ? ($ReqPath.'?'.$_SERVER['QUERY_STRING']) : $ReqPath;
}
elseif( isset($_SERVER['PHP_SELF']) )
{ // The Old Stand-By
	$Debuglog->add( 'vars: Getting ReqPath from PHP_SELF', 'hit' );
	$ReqPath = $_SERVER['PHP_SELF'];
	$ReqURI = isset($_SERVER['QUERY_STRING']) && !empty( $_SERVER['QUERY_STRING'] ) ? ($ReqPath.'?'.$_SERVER['QUERY_STRING']) : $ReqPath;
}
else
{
	$ReqPath = false;
	$ReqURI = false;
	?>
	<p><span class="error">Warning: $ReqPath could not be set. Probably an odd IIS problem.</span><br />
	Go to your <a href="<?php echo $baseurl.$install_subdir ?>phpinfo.php">phpinfo page</a>,
	look for occurences of <code><?php
	// take the baseurlroot out..
	echo preg_replace('#^'.$baseurlroot.'#', '', $baseurl.$install_subdir )
	?>/phpinfo.php</code> and copy all lines
	containing this to the <a href="http://forums.b2evolution.net">forum</a>. Also specify what webserver
	you're running on.
	<br />
	(If you have deleted your install folder &ndash; what is recommened after successful setup &ndash;
	you have to upload it again before doing this).
	</p>
	<?php
}

$Debuglog->add( 'vars: HTTP Host: '.$_SERVER['HTTP_HOST'], 'hit' );
$Debuglog->add( 'vars: Request URI: '.$ReqURI, 'hit' );
$Debuglog->add( 'vars: Request Path: '.$ReqPath, 'hit' );


// on which page are we ?
$pagenow = explode( '/', $_SERVER['PHP_SELF'] );
$pagenow = trim( $pagenow[(sizeof($pagenow) - 1)] );
$pagenow = explode( '?', $pagenow );
$pagenow = $pagenow[0];

// So far, we did not include the javascript for popupups
$b2commentsjavascript = false;

// browser detection
$is_lynx = 0; $is_gecko = 0; $is_winIE = 0; $is_macIE = 0; $is_opera = 0; $is_NS4 = 0;
if( !isset($HTTP_USER_AGENT) )
{
	if( isset($_SERVER['HTTP_USER_AGENT']) )
		$HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];
	else
		$HTTP_USER_AGENT = '';
}
if( $HTTP_USER_AGENT != '' )
{
	if(strpos($HTTP_USER_AGENT, 'Lynx') !== false)
	{
		$is_lynx = 1;
	}
	elseif(strpos($HTTP_USER_AGENT, 'Gecko') !== false)
	{
		$is_gecko = 1;
	}
	elseif(strpos($HTTP_USER_AGENT, 'MSIE') !== false && strpos($HTTP_USER_AGENT, 'Win') !== false)
	{
		$is_winIE = 1;
	}
	elseif(strpos($HTTP_USER_AGENT, 'MSIE') !== false && strpos($HTTP_USER_AGENT, 'Mac') !== false)
	{
		$is_macIE = 1;
	}
	elseif(strpos($HTTP_USER_AGENT, 'Opera') !== false)
	{
		$is_opera = 1;
	}
	elseif(strpos($HTTP_USER_AGENT, 'Nav') !== false || preg_match('/Mozilla\/4\./', $HTTP_USER_AGENT))
	{
		$is_NS4 = 1;
	}

	if ($HTTP_USER_AGENT != strip_tags($HTTP_USER_AGENT))
	{ // then they have tried something funky,
		// putting HTML or PHP into the HTTP_USER_AGENT
		$Debuglog->add( 'setting vars: '.T_('bad char in User Agent'), 'hit');
		$HTTP_USER_AGENT = T_('bad char in User Agent');
	}

}
$is_IE = (($is_macIE) || ($is_winIE));
// $Debuglog->add( 'setting vars: '. "User Agent: ".$HTTP_USER_AGENT);


// server detection
$is_Apache = strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false ? 1 : 0;
$is_IIS    = strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false ? 1 : 0;


// the weekdays and the months..
$weekday[0] = NT_('Sunday');
$weekday[1] = NT_('Monday');
$weekday[2] = NT_('Tuesday');
$weekday[3] = NT_('Wednesday');
$weekday[4] = NT_('Thursday');
$weekday[5] = NT_('Friday');
$weekday[6] = NT_('Saturday');

// the weekdays short form (typically 3 letters)
// TRANS: abbrev. for Sunday
$weekday_abbrev[0] = NT_('Sun');
// TRANS: abbrev. for Monday
$weekday_abbrev[1] = NT_('Mon');
// TRANS: abbrev. for Tuesday
$weekday_abbrev[2] = NT_('Tue');
// TRANS: abbrev. for Wednesday
$weekday_abbrev[3] = NT_('Wed');
// TRANS: abbrev. for Thursday
$weekday_abbrev[4] = NT_('Thu');
// TRANS: abbrev. for Friday
$weekday_abbrev[5] = NT_('Fri');
// TRANS: abbrev. for Saturday
$weekday_abbrev[6] = NT_('Sat');

// the weekdays even shorter form (typically 1 letter)
// TRANS: abbrev. for Sunday
$weekday_letter[0] = NT_(' S ');
// TRANS: abbrev. for Monday
$weekday_letter[1] = NT_(' M ');
// TRANS: abbrev. for Tuesday
$weekday_letter[2] = NT_(' T ');
// TRANS: abbrev. for Wednesday
$weekday_letter[3] = NT_(' W ');
// TRANS: abbrev. for Thursday
$weekday_letter[4] = NT_(' T  ');
// TRANS: abbrev. for Friday
$weekday_letter[5] = NT_(' F ');
// TRANS: abbrev. for Saturday
$weekday_letter[6] = NT_(' S  ');

// the months
$month['01'] = NT_('January');
$month['02'] = NT_('February');
$month['03'] = NT_('March');
$month['04'] = NT_('April');
// TRANS: space at the end only to differentiate from short form. You don't need to keep it in the translation.
$month['05'] = NT_('May ');
$month['06'] = NT_('June');
$month['07'] = NT_('July');
$month['08'] = NT_('August');
$month['09'] = NT_('September');
$month['10'] = NT_('October');
$month['11'] = NT_('November');
$month['12'] = NT_('December');

// the months short form (typically 3 letters)
// TRANS: abbrev. for January
$month_abbrev['01'] = NT_('Jan');
// TRANS: abbrev. for February
$month_abbrev['02'] = NT_('Feb');
// TRANS: abbrev. for March
$month_abbrev['03'] = NT_('Mar');
// TRANS: abbrev. for April
$month_abbrev['04'] = NT_('Apr');
// TRANS: abbrev. for May
$month_abbrev['05'] = NT_('May');
// TRANS: abbrev. for June
$month_abbrev['06'] = NT_('Jun');
// TRANS: abbrev. for July
$month_abbrev['07'] = NT_('Jul');
// TRANS: abbrev. for August
$month_abbrev['08'] = NT_('Aug');
// TRANS: abbrev. for September
$month_abbrev['09'] = NT_('Sep');
// TRANS: abbrev. for October
$month_abbrev['10'] = NT_('Oct');
// TRANS: abbrev. for November
$month_abbrev['11'] = NT_('Nov');
// TRANS: abbrev. for December
$month_abbrev['12'] = NT_('Dec');

// the post statuses:
$post_statuses = array (
	'published' => NT_('Published'),
	'deprecated' => NT_('Deprecated'),
	'protected' => NT_('Protected'),
	'private' => NT_('Private'),
	'draft' => NT_('Draft'),
);

// the antispam sources:
$aspm_sources = array (
	'local' => NT_('Local'),
	'reported' => NT_('Reported'),
	'central' => NT_('Central'),
);

?>