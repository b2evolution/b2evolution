<?php
/**
 * This file implements the BBcode plugin for b2evolution
 *
 * BB style formatting, like [b]bold[/b]
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */

// # BBcode search and replace arrays #

$b2_bbcode['in'] = array(
	'#\[b](.+?)\[/b]#is',		// Formatting tags
	'#\[i](.+?)\[/i]#is',
	'#\[u](.+?)\[/u]#is',
	'#\[s](.+?)\[/s]#is',
	'!\[color=(#?[A-Za-z0-9]+?)](.+?)\[/color]!is',
	'#\[size=([0-9]+?)](.+?)\[/size]#is',
	'#\[font=([A-Za-z0-9 ;\-]+?)](.+?)\[/font]#is',
// The following are dangerous, until we security check resulting code.
//	'#\[img](.+?)\[/img]#is',		// Image
//	'#\[url](.+?)\[/url]#is',		// URL
//	'#\[url=(.+?)](.+?)\[/url]#is',
//	'#\[email](.+?)\[/email]#eis',		// E-mail
//	'#\[email=(.+?)](.+?)\[/email]#eis'
);
$b2_bbcode['out'] = array(
	'<strong>$1</strong>',		// Formatting tags
	'<em>$1</em>',
	'<span style="text-decoration:underline">$1</span>',
	'<span style="text-decoration:line-through">$1</span>',
	'<span style="color:$1">$2</span>',
	'<span style="font-size:$1px">$2</span>',
	'<span style="font-family:$1">$2</span>',
//	'<img src="$1" alt="" />',		// Image
//	'<a href="$1">$1</a>',		// URL
//	'<a href="$1" title="$2">$2</a>',
//	'<a href=\"mailto:$1\">$1</a>',		// E-mail
//	'<a href="mailto:$1">$2</a>'
);


/*
 * convert_bbcode(-)
 */
function convert_bbcode( & $content)
{
	global $b2_bbcode;
	$content = preg_replace($b2_bbcode["in"], $b2_bbcode["out"], $content);
}

?>
