<?php
/**
 * This file implements the Auto P plugin for b2evolution
 *
 * @author WordPress team - http://sourceforge.net/project/memberlist.php?group_id=51422
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 *
 * @todo This is buggy, because it does not create newlines/paragraphs in tag blocks only.
 *       blueyed>> I've started working on it.
 */
class auto_p_plugin extends Plugin
{
	var $code = 'b2WPAutP';
	var $name = 'Auto P';
	var $priority = 70;

	var $apply_rendering = 'opt-out';
	var $short_desc;
	var $long_desc;


	/**
	 * Constructor
	 */
	function auto_p_plugin()
	{
		$this->short_desc = T_('Automatic &lt;P&gt; and &lt;BR&gt; tags');
		$this->long_desc = T_('No description available');
	}


	/**
	 * @return array
	 */
	function GetDefaultSettings()
	{
		return array(
				'br' => array( 'label' => T_('Line breaks'), 'type' => 'checkbox', 'defaultvalue' => 1, 'note' => T_('Make line breaks (&lt;br /&gt;) for single newlines.') ),
			);
	}


	/**
	 * Perform rendering
	 *
	 * @param array Associative array of parameters
	 * 							(Output format, see {@link format_to_output()})
	 * @return boolean true if we can render something for the required output format
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		// REPLACE:  But not in pre blocks.
		// TODO: handle pre tags with attributes (e.g. class)
		// TODO: also handle code blocks!
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

		// List of block elements (we want a paragraph before and after):
		$this->block_tags = $block_tags = 'table|thead|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|p|h[1-6]';

		// Space things out a little (by two lines to force a <p> before/after):
		$pee = preg_replace('!(<(?:'.$block_tags.')[^>]*>)!', "\n\n$1", $pee);
		$pee = preg_replace('!(</(?:'.$block_tags.')>)!', "$1\n\n", $pee);

		$pee = preg_replace("/(\r\n|\r)/", "\n", $pee); // cross-platform newlines

		$pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates


		// make paragraphs, including one at the end :
		$pee = preg_replace('/\n?(.+?)(?:\n\s*\n|\z)/s', "\t<p>$1</p>\n", $pee);

		// TODO: Parse html (not strict) and create paragraphs in between tags only. This should also avaoid most of the "fix all the extra Ps" below. (blueyed)
		/*
		// make paragraphs, including one at the end :
		$pee = $this->newlines_in_tags( $pee );
		*/
		#pre_dump( 'made_pees', $pee );


		// Now fix all the extra Ps...

		$pee = preg_replace('|\t<p>\s*?</p>\n|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace # dh: fixed creation of unnecessary <br />

		$pee = preg_replace('!<p>\s*(</?(?:'.$block_tags.')[^>]*>)\s*</p>!', "$1", $pee); // don't pee all over a tag

		$pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee); // problem with nested lists

		// Move outer <p> for <blockquote> inside (needed to validate!):
		$pee = preg_replace( '~<p>\s*<blockquote([^>]*)>(.*?)</blockquote></p>~is', '<blockquote$1><p>$2</p></blockquote>', $pee );

		$pee = preg_replace('!<p>\s*(</?(?:'.$block_tags.')[^>]*>)!', "$1", $pee);
		$pee = preg_replace('!(</?(?:'.$block_tags.')[^>]*>)\s*</p>!', "$1", $pee);

		if( $this->Settings->get('br') )
		{ // optionally make line breaks
			$pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee);
		}

		$pee = preg_replace('!(</?(?:table|thead|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|p|h[1-6])[^>]*>)\s*<br />!', "$1", $pee);
		$pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)>)!', '$1', $pee);

		// $content = preg_replace('!(<pre.*? >)(.*?)</pre>!ise', "'$1'.clean_pre('$2').'</pre>' ", $pee);

		// $content = preg_replace('/&([^#])(?![a-z]{1,8};)/', '&#038;$1', $pee);

		return $pee;
	}
}


/*
 * $Log$
 * Revision 1.9  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>