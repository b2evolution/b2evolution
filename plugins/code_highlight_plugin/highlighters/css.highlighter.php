<?php
/**
 * This file is part of the AstonishMe Code plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2009 by Yabba/Scott - {@link http://astonishme.co.uk/contact/}.
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
 * {@internal Below is a list of authors who have contributed to design/coding of this file:
 * @author Yabba: Paul Jones - {@link http://astonishme.co.uk/}
 * @author Stk: Scott Kimler - {@link http://astonishme.co.uk/}
 * }}
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @package AmCode plugin
 */

class am_css_highlighter
{
	/**
	 * Text name of language for display
	 *
	 * This is unused whilst "Experimental" as it requires a modification of the plugin
	 * it would be used to replace the text output above the codeblock instead of ucfirst( language )
	 *
	 */
	var $language_title = 'CSS';


	/**
	 * Boolean are we in strict mode ?
	 *
	 */
	var $strict_mode = false;


	/**
	 * Called automatically on class innit
	 *
	 * @param object $parent
	 * @return object am_css_highlighter
	 */
	function am_css_highlighter( & $parent )
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

		$block = preg_replace(
					array(
						'~(/\*(.+?)\*/)~s', // highlight comments
						'~(@import([^;]+?);)~i', // highlight includes
					),
					array(
						'<span class="amc_comment">$1</span>',
						'<span class="amc_keyword">$1</span>'
					),
						 $block );
		// highlight remaining css
		$block = callback_on_non_matching_blocks(  $block, '~<span([\s\S]+?)</span>~', array( $this, 'highlight_css' ) );

		return $this->parent->tidy_code_output( '<span class="amc_default">'.$block.'</span>' );
	}


	/**
	 * Highlights css
	 *
	 * @param string $block : 2 - the code
	 * @return string highlighted css
	 */
	function highlight_css( $block )
	{
		// highlight all tag/class names and id's
		$block = callback_on_non_matching_blocks( $block, '#\{.+?}#s', array( $this, 'highlight_names' ) );
		$block = callback_on_non_matching_blocks(  $block, '~<span([\s\S]+?)</span>~', array( $this, 'highlight_rest' ) );
		return '<span class="amc_default">'.$block.'</span>';
	}

	function highlight_rest( $block )
	{
		// highlight all css declarations and values
		$block = preg_replace_callback(
						array(
							'#(\{.+?)(})#s',
							'#(\{.+?)$#s',
							'#^([^\{]+?)$#s',
						), array( $this, 'highlight_declarations' ), $block );
		return $block;
	}


	/**
	 * Highlights css class, id & tag names
	 *
	 * @param string $block : 2 - the code
	 * @return string highlighted names
	 */
	function highlight_names( $block )
	{
		$block = preg_replace( array(
						'#\.([\w:]+)#', // highlight classes
						'~#([\w:]+)~', // highlight ID's
					),
					array(
						'<span class="amc_class">.$1</span>',
						'<span class="amc_id">#$1</span>',
					),
						$block );
		return '<span class="amc_tags">'.$block.'</span>'; // highlight tag names
	}


	/**
	 * Highlights css declarations and values
	 *
	 * @param string $block : 2 - the code
	 * @return string highlighted css
	 */
	function highlight_declarations( $block )
	{
		$block[1] = preg_replace( '#([^:;]+?):([^;]+?(;|$))#',
					'<span class="amc_attribute">$1</span>:<span class="amc_string">$2</span>',
					$block[1] );
		return '<span class="amc_default">'.$block[1].'</span>'.( empty( $block[2] ) ? '' : $block[2] );
	}

}

?>