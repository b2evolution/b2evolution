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

#b2 version
$b2_version = '0.8.2-dev1';



# on which page are we ?
$PHP_SELF = $HTTP_SERVER_VARS['PHP_SELF'];
$pagenow = explode('/', $PHP_SELF);
$pagenow = trim($pagenow[(sizeof($pagenow)-1)]);
$pagenow = explode('?', $pagenow);
$pagenow = $pagenow[0];
if (($querystring_start == '/') && ($pagenow != 'b2edit.php')) {
	$pagenow = $siteurl.'/'.$blogfilename;
}

# browser detection
$is_lynx = 0; $is_gecko = 0; $is_winIE = 0; $is_macIE = 0; $is_opera = 0; $is_NS4 = 0;
if (!isset($HTTP_USER_AGENT)) {
	$HTTP_USER_AGENT = $HTTP_SERVER_VARS['HTTP_USER_AGENT'];
}
if (preg_match('/Lynx/', $HTTP_USER_AGENT)) {
	$is_lynx = 1;
} elseif (preg_match('/Gecko/', $HTTP_USER_AGENT)) {
	$is_gecko = 1;
} elseif ((preg_match('/MSIE/', $HTTP_USER_AGENT)) && (preg_match('/Win/', $HTTP_USER_AGENT))) {
	$is_winIE = 1;
} elseif ((preg_match('/MSIE/', $HTTP_USER_AGENT)) && (preg_match('/Mac/', $HTTP_USER_AGENT))) {
	$is_macIE = 1;
} elseif (preg_match('/Opera/', $HTTP_USER_AGENT)) {
	$is_opera = 1;
} elseif ((preg_match('/Nav/', $HTTP_USER_AGENT) ) || (preg_match('/Mozilla\/4\./', $HTTP_USER_AGENT))) {
	$is_NS4 = 1;
}
$is_IE    = (($is_macIE) || ($is_winIE));

# browser-specific javascript corrections
$b2_macIE_correction['in'] = array(
	'/\%uFFD4/', '/\%uFFD5/', '/\%uFFD2/', '/\%uFFD3/',
	'/\%uFFA5/', '/\%uFFD0/', '/\%uFFD1/', '/\%uFFBD/',
	'/\%uFF83%uFFC0/', '/\%uFF83%uFFC1/', '/\%uFF83%uFFC6/', '/\%uFF83%uFFC9/',
	'/\%uFFB9/', '/\%uFF81%uFF8C/', '/\%uFF81%uFF8D/', '/\%uFF81%uFFDA/',
	'/\%uFFDB/'
);
$b2_macIE_correction['out'] = array(
	'&lsquo;', '&rsquo;', '&ldquo;', '&rdquo;',
	'&bull;', '&ndash;', '&mdash;', '&Omega;',
	'&beta;', '&gamma;', '&theta;', '&lambda;',
	'&pi;', '&prime;', '&Prime;', '&ang;',
	'&euro;'
);
$b2_gecko_correction['in'] = array(
	'/\‘/', '/\’/', '/\“/', '/\”/',
	'/\•/', '/\–/', '/\—/', '/\Ω/',
	'/\β/', '/\γ/', '/\θ/', '/\λ/',
	'/\π/', '/\′/', '/\″/', '/\�/',
	'/\€/', '/\ /'
);
$b2_gecko_correction['out'] = array(
	'&8216;', '&rsquo;', '&ldquo;', '&rdquo;',
	'&bull;', '&ndash;', '&mdash;', '&Omega;',
	'&beta;', '&gamma;', '&theta;', '&lambda;',
	'&pi;', '&prime;', '&Prime;', '&ang;',
	'&euro;', '&#8201;'
);

# server detection
$is_Apache = strstr($HTTP_SERVER_VARS['SERVER_SOFTWARE'], 'Apache') ? 1 : 0;
$is_IIS = strstr($HTTP_SERVER_VARS['SERVER_SOFTWARE'], 'Microsoft-IIS') ? 1 : 0;


# sorts the smilies' array by length
# this is important if you want :)) to superseede :) for example
if (!function_exists('smiliescmp')) 
{
	function smiliescmp ($a, $b) 
	{
	   if (strlen($a) == strlen($b)) 
		 {
		  return strcmp($a, $b);
	   }
	   return (strlen($a) > strlen($b)) ? -1 : 1;
	}
}
uksort($b2smiliestrans, 'smiliescmp');
// arsort($b2smiliestrans);


# generates smilies' search & replace arrays
foreach($b2smiliestrans as $smiley => $img) 
{
	$b2_smiliessearch[] = $smiley;

	$smiley_masked = '';
	for ($i = 0; $i < strlen($smiley); $i++ ) 
	{
		// fplanque: with the #160s we prevent recurrent replacing... bleh :(
		// fplanque changed $smiley_masked .= substr($smiley, $i, 1).chr(160);
		// better way: (added benefit: handles ' and " escaping for alt attribute!... needed in :') )
		$smiley_masked .=  '&#'.ord(substr($smiley, $i, 1)).';';
	}
	
	// fplanque: added class='middle'
	$b2_smiliesreplace[] = "<img src='$smilies_directory/$img' alt='$smiley_masked' class='middle' />";
}

?>