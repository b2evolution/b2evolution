<?php
/**
 * This file implements the Auto P plugin for b2evolution
 *
 * @author WordPress team - http://sourceforge.net/project/memberlist.php?group_id=51422
 *
 * @package plugins
 */
require_once dirname(__FILE__).'/../renderer.class.php';

class auto_p_Rendererplugin extends RendererPlugin
{
	var $code = 'b2WPAutP';
	var $name = 'Auto P';
	var $short_desc = 'Automatic &lt;P&gt; and &lt;BR&gt; tags';
	var $long_desc = 'No description available';

	var $br = true; 	// optionally make line breaks

	/**
	 * Perform rendering
	 *
	 * {@internal auto_p_Rendererplugin::render(-)}} 
	 */
	function render( & $content )
	{
		$pee = $content . "\n"; // just to make things a little easier, pad the end
		$pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);
		$pee = preg_replace('!(<(?:table|thead|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|p|h[1-6])[^>]*>)!', "\n$1", $pee); // Space things out a little
		$pee = preg_replace('!(</(?:table|thead|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|p|h[1-6])>)!', "$1\n", $pee); // Space things out a little
		$pee = preg_replace("/(\r\n|\r)/", "\n", $pee); // cross-platform newlines 
		$pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
		$pee = preg_replace('/\n?(.+?)(?:\n\s*\n|\z)/s', "\t<p>$1</p>\n", $pee); // make paragraphs, including one at the end 
		$pee = preg_replace('|<p>\s*?</p>|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace 
			$pee = preg_replace('!<p>\s*(</?(?:table|thead|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|p|h[1-6])[^>]*>)\s*</p>!', "$1", $pee); // don't pee all over a tag
		$pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee); // problem with nested lists
		$pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
		$pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
		$pee = preg_replace('!<p>\s*(</?(?:table|thead|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|p|h[1-6])[^>]*>)!', "$1", $pee);
		$pee = preg_replace('!(</?(?:table|thead|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|p|h[1-6])[^>]*>)\s*</p>!', "$1", $pee); 
		if ($this->br) $pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee); // optionally make line breaks
		$pee = preg_replace('!(</?(?:table|thead|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|p|h[1-6])[^>]*>)\s*<br />!', "$1", $pee);
		$pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)>)!', '$1', $pee);
		$pee = preg_replace('!(<pre.*?>)(.*?)</pre>!ise', " stripslashes('$1') .  clean_pre('$2')  . '</pre>' ", $pee);
		$content = preg_replace('/&([^#])(?![a-z]{1,8};)/', '&#038;$1', $pee);
	}
}

// Register the plugin:
$this->register( new auto_p_Rendererplugin() );

?>