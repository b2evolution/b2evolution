<?php
/**
 * This file is part of the AstonishMe Code plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2007 by Yabba/Scott - {@link http://astonishme.co.uk/contact/}.
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
class am_code_highlighter
{
	/**
	 * Text name of language for display
	 *
	 * This is unused whilst "Experimental" as it requires a modification of the plugin
	 * it would be used to replace the text output above the codeblock instead of ucfirst( language )
	 *
	 */
	var $language_title = 'Code';


	/**
	 * Name of language that was desired by user
	 * This can be used to make geshi simpler to implement
	 *
	 */
	var $requested_language;


	/**
	 * Boolean are we in strict mode ?
	 *
	 */
	var $strict_mode = false;


	/**
	 * Called automatically on class innit
	 *
	 * @param object $parent
	 * @return object am_code_highlighter
	 */
	function am_code_highlighter( & $parent )
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
		return $this->parent->tidy_code_output( '<span class="amc_default">'.$block.'</span>' );
	}

}

?>