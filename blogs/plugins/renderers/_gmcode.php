<?php
/**
 * This file implements the GMcode plugin for b2evolution
 *
 * GreyMatter style formatting
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */

// printf( '%x', ord( '/' ) );

#GreyMatter formatting search and replace arrays
$b2_gmcode['in'] = array(
													'#\*\*(.+?)\*\*#s',		// **bold**
													'#\x5c\x5c(.+?)\x5c\x5c#s',		// \\italic\\
													'#\x2f\x2f(.+?)\x2f\x2f#',		// //italic//
													'#__(.+?)__#s'		// __underline__
												);

$b2_gmcode['out'] = array(
													'<strong>$1</strong>',
													'<em>$1</em>',
													'<em>$1</em>',
													'<span style="text-decoration:underline">$1</span>'
												);

/*
 * convert_gmcode(-)
 */
function convert_gmcode( & $content)
{
	global $b2_gmcode;
	
	$content = preg_replace($b2_gmcode["in"], $b2_gmcode["out"], $content);
}


?>
