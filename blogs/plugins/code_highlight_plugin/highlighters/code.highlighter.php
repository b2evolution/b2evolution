<?php
/**
 * This file is part of the AstonishMe Code plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @package plugins: AmCode Plugin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file:
 * @author Yabba: Paul Jones - {@link http://astonishme.co.uk/}
 * @author Stk: Scott Kimler - {@link http://astonishme.co.uk/}
 * }}
 *
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * @package AmCode plugin
 */

/*
 * yabs > would like this to extend an "am_highlighter" class, but not sure how to handle $this->T_()
 */
class am_code_highlighter extends code_highlight_plugin
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
	 * Boolean are we in strict mode ?
	 *
	 */
	var $strict_mode = false;


	/**
	 * Highlights code ready for displaying
	 *
	 * @param string $block - the code
	 * @return string highlighted code
	 */

	function highlight_code( $block )
	{
		return $this->tidy_code_output( '<span class="amc_default">'.$block.'</span>' );
	}

}
/**
 * $Log $
 *
 */
?>