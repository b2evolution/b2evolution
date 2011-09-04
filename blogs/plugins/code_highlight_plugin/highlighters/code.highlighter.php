<?php
/**
 * This file is part of the AstonishMe Code plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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


/*
 * $Log$
 * Revision 1.12  2011/09/04 22:13:23  fplanque
 * copyright 2011
 *
 * Revision 1.11  2010/02/08 17:56:01  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.10  2009/03/08 23:57:49  fplanque
 * 2009
 *
 * Revision 1.9  2009/01/25 23:13:55  blueyed
 * Fix CVS log section, which is not phpdoc
 *
 * Revision 1.8  2008/01/21 09:35:42  fplanque
 * (c) 2008
 *
 * Revision 1.7  2007/07/03 10:44:23  yabs
 * minor change
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
 * Revision 1.2  2007/05/04 20:43:08  fplanque
 * MFB
 *
 * Revision 1.1.2.3  2007/04/23 11:59:58  yabs
 * removed "extend Plugins"
 *
 *
 */
?>
