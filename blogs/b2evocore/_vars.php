<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file sets various arrays and variables for use in b2evolution
 * This file built upon code from original b2 - http://cafelog.com/
 */

$b2_version = '0.8.5';

// Activate gettext:
if( ($use_l10n==1) && function_exists( 'bindtextdomain' ) )
{	// We are going to use GETTEXT
	// Specify location of translation tables :
	bindtextdomain( 'messages', dirname(__FILE__).'/../locales'); 
	// Choose domain: (name of the .mo files)
	textdomain( 'messages' );
}
// Choose lang and set default locale:
locale_activate( $default_locale );


// on which page are we ?
$PHP_SELF = $_SERVER['PHP_SELF'];
$pagenow = explode('/', $PHP_SELF);
$pagenow = trim($pagenow[(sizeof($pagenow)-1)]);
$pagenow = explode('?', $pagenow);
$pagenow = $pagenow[0];

// browser detection
$is_lynx = 0; $is_gecko = 0; $is_winIE = 0; $is_macIE = 0; $is_opera = 0; $is_NS4 = 0;
if (!isset($HTTP_USER_AGENT)) {
	$HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];
}
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
$is_IE = (($is_macIE) || ($is_winIE));

// browser-specific javascript corrections
$b2_macIE_correction['in'] = array(
	'/\%uFFD4/', '/\%uFFD5/', '/\%uFFD2/', '/\%uFFD3/',
	'/\%uFFA5/', '/\%uFFD0/', '/\%uFFD1/', '/\%uFFBD/',
	'/\%uFF83%uFFC0/', '/\%uFF83%uFFC1/', '/\%uFF83%uFFC6/', '/\%uFF83%uFFC9/',
	'/\%uFFB9/', '/\%uFF81%uFF8C/', '/\%uFF81%uFF8D/', '/\%uFF81%uFFDA/',
	'/\%uFFDB/'
);
$b2_macIE_correction['out'] = array(
	'&lsquo;', '&rsquo;', '&ldquo;', '&rdquo;',
	'&bull;',  '&ndash;', '&mdash;', '&Omega;',
	'&beta;',  '&gamma;', '&theta;', '&lambda;',
	'&pi;',    '&prime;', '&Prime;', '&ang;',
	'&euro;'
);
$b2_gecko_correction['in'] = array(
	'/\‘/', '/\’/', '/\“/', '/\”/',
	'/\•/', '/\–/', '/\—/', '/\Ω/',
	'/\β/',  '/\γ/',  '/\θ/',  '/\λ/',
	'/\π/',  '/\′/', '/\″/', '/\�/',
	'/\€/', '/\ /'
);
$b2_gecko_correction['out'] = array(
	'&8216;', '&rsquo;', '&ldquo;', '&rdquo;',
	'&bull;', '&ndash;', '&mdash;', '&Omega;',
	'&beta;', '&gamma;', '&theta;', '&lambda;',
	'&pi;',   '&prime;', '&Prime;', '&ang;',
	'&euro;', '&#8201;'
);

// server detection
$is_Apache	= strpos($HTTP_SERVER_VARS['SERVER_SOFTWARE'], 'Apache') !== false ? 1 : 0;
$is_IIS		= strpos($HTTP_SERVER_VARS['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false ? 1 : 0;

// let's deactivate quicktags on IE Mac and Lynx, because they don't work there.
if (($is_macIE) || ($is_lynx))
	$use_quicktags=0;

/* sorts the smilies' array by length
  this is important if you want :)) to superseede :) for example
*/
if (!function_exists('smiliescmp')) 
{
	function smiliescmp($a, $b)
	{
		if(($diff = strlen($b) - strlen($a)) == 0)
		{
			return strcmp($a, $b);
		}
		return $diff;
	}
}
	
uksort($b2smiliestrans, 'smiliescmp');

// generates smilies' search & replace arrays
foreach($b2smiliestrans as $smiley => $img) 
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
	
	// fplanque: added class='middle'
	$b2_smiliesreplace[] = "<img src='$smilies_directory/$img' alt='$smiley_masked' class='middle' />";
}

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

?>
