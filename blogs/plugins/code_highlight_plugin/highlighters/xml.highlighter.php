<?php
/**
 * This file is part of the AstonishMe Code plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2005-2007 by Yabba/Scott - {@link http://astonishme.co.uk/contact/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Yabba/Scott grant Francois PLANQUE the right to license
 * Yabba's/Scott's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package plugins
 *
 * @author Yabba: Paul Jones - {@link http://astonishme.co.uk/}
 * @author Stk: Scott Kimler - {@link http://astonishme.co.uk/}
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @package plugins
 */
class am_xml_highlighter
{
	/**
	 * Text name of language for display
	 *
	 * This is unused whilst "Experimental" as it requires a modification of the plugin
	 * it would be used to replace the text output above the codeblock instead of ucfirst( language )
	 *
	 */
	var $language_title = 'XML';


	/**
	 * Boolean are we in strict mode ?
	 *
	 */
	var $strict_mode = false;


	/**
	 * Called automatically on class innit
	 *
	 * @param object $parent
	 * @return object am_xml_highlighter
	 */
	function am_xml_highlighter( & $parent )
	{
		$this->parent = & $parent;
		return $this;
	}


	/**
	 * Highlights code ready for displaying
	 *
	 * @param string $block - the code
	 * @return string highlighted code
	 */
	function highlight_code( $block )
	{
		// highlight all < ?xml - ? >, CDATA and comment blocks
		$block = preg_replace( array(
						'¤(&lt;\!--(.*?)--&gt;)¤',
						'¤(&lt;\!\[CDATA\[([\s\S]*?)]]&gt;)¤',
						'¤(&lt;\?(.*?)\?&gt;)¤' ),
					array(
						'<span class="amc_comment">&lt;!&#8722;&#8722;$2&#8722;&#8722;&gt;</span>',
						'<span class="amc_comment">$1</span>',
						'<span class="amc_keyword">$1</span>' ),
						 $block );
		// highlight remaining tags, attributes and strings
		$block = callback_on_non_matching_blocks(  $block, '¤<span([\s\S]+?)</span>¤', array( $this, 'highlight_xml_tags' ) );


		return $this->parent->tidy_code_output( '<span class="amc_default">'.$block.'</span>' );
	}


	/**
	 * Highlights xml declarations
	 *
	 * @param string $block : 2 - the code
	 * @return string highlighted declarations
	 */
	function highlight_xml_tags( $block )
	{
		$block = preg_replace_callback( '¤(&lt;(.*?)&gt;)¤', array( $this, 'highlight_xml' ), $block );
		return '<span class="amc_default">'.$block.'</span>';
	}


	/**
	 * Highlights xml tags, attributes and values
	 *
	 * @param string $block : 2 - the code
	 * @return string highlighted xml code
	 */
	function highlight_xml( $block )
	{
		$block[2] = preg_replace(
				array( '#^([^\s]+?)(\s)#','#(\s)([^\s]+?)=#i', '#(["\'])([^\1]+?)\1#' ),
				array( '$1</span><default>$2', '$1<attrib>$2</span>=', '<string>$1$2$1</span>' ),
				$block[2] );

		return '<span class="amc_keyword">&lt;'.str_replace(
				array( '<default>', '<attrib>', '<string>' ),
				array( '<span class="amc_default">', '<span class="amc_attribute">', '<span class="amc_string">' ),
				$block[2] ).'&gt;</span>';
	}


}


/*
 * $Log$
 * Revision 1.9  2009/03/08 23:57:52  fplanque
 * 2009
 *
 * Revision 1.8  2009/01/25 23:13:55  blueyed
 * Fix CVS log section, which is not phpdoc
 *
 * Revision 1.7  2008/01/21 09:35:42  fplanque
 * (c) 2008
 *
 * Revision 1.6  2007/06/26 02:40:54  fplanque
 * security checks
 *
 * Revision 1.5  2007/06/20 21:33:23  blueyed
 * fixed doc
 *
 * Revision 1.4  2007/06/20 19:16:36  blueyed
 * Fixed doc
 *
 * Revision 1.3  2007/06/17 13:28:22  blueyed
 * Fixed doc
 *
 * Revision 1.2  2007/05/04 20:43:09  fplanque
 * MFB
 *
 * Revision 1.1.2.6  2007/04/23 12:00:36  yabs
 * removed "extend Plugins"
 *
 * Revision 1.1.2.5  2007/04/21 08:43:37  yabs
 * minor docs and code
 *
 * Revision 1.1.2.4  2007/04/21 07:40:36  yabs
 * added in highlighting for comments, cdata & xml declarations
 *
 * Revision 1.1.2.3  2007/04/20 12:02:25  yabs
 * Added in some highlighting for attributes, tags and strings
 *
 *
 */
?>
