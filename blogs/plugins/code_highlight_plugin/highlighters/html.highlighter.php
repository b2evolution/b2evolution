<?php
/**
 * This file is part of the AstonishMe Code plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
class am_html_highlighter
{
	/**
	 * Text name of language for display
	 *
	 * This is unused whilst "Experimental" as it requires a modification of the plugin
	 * it would be used to replace the text output above the codeblock instead of ucfirst( language )
	 *
	 */
	var $language_title = 'HTML';


	/**
	 * Boolean are we in strict mode ?
	 *
	 */
	var $strict_mode = false;


	/**
	 * Called automatically on class innit
	 *
	 * @param object $parent
	 * @return object am_html_highlighter
	 */
	function am_html_highlighter( & $parent )
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
						'~(&lt;\!--(.*?)--&gt;)~',
						'~(&lt;\!\[CDATA\[([\s\S]*?)]]&gt;)~',
						'~(&lt;\?(.*?)\?&gt;)~' ),
					array(
						'<span class="amc_comment">&lt;!&#8722;&#8722;$2&#8722;&#8722;&gt;</span>',
						'<span class="amc_comment">$1</span>',
						'<span class="amc_keyword">$1</span>' ),
						 $block );
		// highlight remaining tags, attributes and strings
		$block = callback_on_non_matching_blocks(  $block, '~<span([\s\S]+?)</span>~', array( $this, 'highlight_html_tags' ) );


		return $this->parent->tidy_code_output( '<span class="amc_default">'.$block.'</span>' );
	}


	/**
	 * Highlights html declarations
	 *
	 * @param string $block : 2 - the code
	 * @return string highlighted declarations
	 */
	function highlight_html_tags( $block )
	{
		$block = preg_replace_callback( '~(&lt;(.*?)&gt;)~', array( $this, 'highlight_html' ), $block );
		return '<span class="amc_default">'.$block.'</span>';
	}


	/**
	 * Highlights html tags, attributes and values
	 *
	 * @param string $block : 2 - the code
	 * @return string highlighted html code
	 */
	function highlight_html( $block )
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

?>