<?php
/**
 * This file implements the Widget class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Widget class which provides an interface to widget methods for other classes.
 *
 * It provides a method {@link replace_vars()} that can be used to replace object properties in given strings.
 * You can also register global action icons.
 *
 * @package evocore
 * @abstract
 */
class Widget
{
	/**
	 * Title of the widget (to be displayed)
	 */
	var $title;

	/**
	 * List of registered global action icons that get substituted through '$global_icons$'.
	 * @see global_icon()
	 */
	var $global_icons = array();


	/**
	 * Registers a global action icon
	 *
	 * @param string TITLE text (IMG and A link)
	 * @param string icon code, see {@link $map_iconfiles}
	 * @param string icon code for {@link get_icon()}
	 * @param integer 1-5: weight of the icon. the icon will be displayed only if its weight is >= than the user setting threshold
	 * @param integer 1-5: weight of the word. the word will be displayed only if its weight is >= than the user setting threshold
	 * @param array Additional attributes to the A tag. See {@link action_icon()}.
	 */
	function global_icon( $title, $icon, $url, $word = '', $icon_weight = 3, $word_weight = 2, $link_attribs = array( 'class'=>'action_icon' ) )
	{
		$this->global_icons[] = array(
			'title' => $title,
			'icon'  => $icon,
			'url'   => $url,
			'word'  => $word,
			'icon_weight'  => $icon_weight,
			'word_weight'  => $word_weight,
			'link_attribs' => $link_attribs );
	}


	/**
	 * Replaces $vars$ with appropriate values.
	 *
	 * You can give an alternative string to display, if the substituted variable
	 * is empty, like:
	 * <code>$vars "Display if empty"$</code>
	 *
	 * @param string template
	 * @param array optional params that are put into {@link $this->replace_params}
	 *              to be accessible by derived replace_callback() methods
	 * @return string The substituted string
	 */
	function replace_vars( $template, $replace_params = NULL )
	{
		$this->replace_params = & $replace_params;
		return preg_replace_callback(
			'~\$([a-z_]+)(?:\s+"([^"]*)")?\$~', # pattern
			array( $this, 'replace_callback_wrapper' ), # callback
			$template );
	}


	/**
	 * This is an additional wrapper to {@link replace_vars()} that allows to react
	 * on the return value of it.
	 *
	 * Used by replace_callback()
	 *
	 * @param array {@link preg_match() preg match}
	 * @return string
	 */
	function replace_callback_wrapper( $match )
	{
		$r = $this->replace_callback( $match );

		if( empty($r) && !empty($match[2]) )
		{
			return $match[2]; // "display if empty"
		}
		return $r;
	}


	/**
	 * Callback function used to replace only necessary values in template.
	 *
	 * This gets used by {@link replace_vars()} to replace $vars$.
	 *
	 * @param array {@link preg_match() preg match}. Index 1 is the template variable.
	 * @return string to be substituted
	 */
	function replace_callback( $matches )
	{
		//echo $matches[1];
		switch( $matches[1] )
		{
			case 'global_icons' :
				// Icons for the whole result set:
				return $this->gen_global_icons();

			case 'title':
				// Results title:
				return $this->title;

			default:
				return $matches[1];
		}
	}


	/**
	 * Generate img tags for registered icons, through {@link global_icon()}.
	 *
	 * This is used by the default callback to replace '$global_icons$'.
	 */
	function gen_global_icons()
	{
		$r = '';

		foreach( $this->global_icons as $icon_params )
		{
			$r .= action_icon( $icon_params['title'], $icon_params['icon'], $icon_params['url'], $icon_params['word'],
						$icon_params['icon_weight'], $icon_params['word_weight'], $icon_params['link_attribs'] );
		}

		return $r;
	}

}

?>