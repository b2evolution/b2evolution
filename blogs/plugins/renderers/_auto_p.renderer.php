<?php
/**
 * This file implements the Auto P plugin for b2evolution
 *
 * @author WordPress team - http://sourceforge.net/project/memberlist.php?group_id=51422
 *
 * @package plugins
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../renderer.class.php';

/**
 * @package plugins
 */
class auto_p_Rendererplugin extends RendererPlugin
{
	var $code = 'b2WPAutP';
	var $name = 'Auto P';
	var $priority = 70;
	
	var $apply_when = 'opt-out';
	var $apply_to_html = true; 
	var $apply_to_xml = false; 
	var $short_desc;
	var $long_desc;

	var $br = true; 	// optionally make line breaks


	/**
	 * Constructor
	 *
	 * {@internal auto_p_Rendererplugin::auto_p_Rendererplugin(-)}}
	 */
	function auto_p_Rendererplugin()
	{
		$this->short_desc = T_('Automatic &lt;P&gt; and &lt;BR&gt; tags');
		$this->long_desc = T_('No description available');
	}


	/**
	 * Perform rendering
	 *
	 * {@internal auto_p_Rendererplugin::render(-)}} 
	 *
	 * @param string content to render (by reference) / rendered content
	 * @param string Output format, see {@link format_to_output()}
	 * @return boolean true if we can render something for the required output format
	 */
	function render( & $content, $format )
	{
		if( ! parent::render( $content, $format ) )
		{	// We cannot render the required format
			return false;
		}

		// REPLACE:  But not in code blocks.
		if( strpos( $content , '<pre>' ) !== false )
		{ // If there are code tags run this substitution
			$content_parts = preg_split("/<\/?pre>/", $content);
			$content = '';
			for ( $x = 0 ; $x < count( $content_parts ) ; $x++ )
			{
				if ( ( $x % 2 ) == 0 )
				{ // If x is even then it's not code and replace any smiles
					$content .= $this->autop( $content_parts[$x] );
				}
				else
				{ // If x is odd don't replace smiles. and put code tags back in.
					$content .= '<pre>' . $content_parts[$x] . '</pre>';
				}
			}
		}
		else
		{ // No code blocks, replace on the whole thing
			$content = $this->autop( $content );
		}
		return true;
	}

	function autop( $pee )
	{	
		$pee = $pee. "\n"; // just to make things a little easier, pad the end
		
		$pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);	// Change double BRs to double newlines
		
		// Space things out a little:
		$pee = preg_replace('!(<(?:table|thead|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|p|h[1-6])[^>]*>)!', "\n$1", $pee); 
		$pee = preg_replace('!(</(?:table|thead|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|p|h[1-6])>)!', "$1\n", $pee);
				
		$pee = preg_replace("/(\r\n|\r)/", "\n", $pee); // cross-platform newlines 

		$pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates

		// make paragraphs, including one at the end :
		$pee = preg_replace('/\n?(.+?)(?:\n\s*\n|\z)/s', "\t<p>$1</p>\n", $pee); 

		// Now fix all the extra Ps...
		
		$pee = preg_replace('|\t<p>\s*?</p>\n|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace # dh: fixed creation of unnecessary <br />

		$pee = preg_replace('!<p>\s*(</?(?:table|thead|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|p|h[1-6])[^>]*>)\s*</p>!', "$1", $pee); // don't pee all over a tag
		
		$pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee); // problem with nested lists
		
		$pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
		$pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
		
		$pee = preg_replace('!<p>\s*(</?(?:table|thead|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|p|h[1-6])[^>]*>)!', "$1", $pee);
		$pee = preg_replace('!(</?(?:table|thead|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|p|h[1-6])[^>]*>)\s*</p>!', "$1", $pee); 
		
		if ($this->br) $pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee); // optionally make line breaks
		
		$pee = preg_replace('!(</?(?:table|thead|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|p|h[1-6])[^>]*>)\s*<br />!', "$1", $pee);
		$pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)>)!', '$1', $pee);
		
		// $content = preg_replace('!(<pre.*? >)(.*?)</pre>!ise', "'$1'.clean_pre('$2').'</pre>' ", $pee);
		
		// $content = preg_replace('/&([^#])(?![a-z]{1,8};)/', '&#038;$1', $pee);
		
		return $pee;
	}
}

// Register the plugin:
$this->register( new auto_p_Rendererplugin() );

?>