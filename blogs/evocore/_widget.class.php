<?php
/**
 * This file implements the Widget class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


/**
 * Widget class
 */
class Widget
{

	var $global_icons = array();


	/**
	 * Registers a global action icon
	 *
	 * @param string TITLE text (IMG and A link)
	 * @param string icon code {@see $$map_iconfiles}
	 * @param string icon code for {@see getIcon()}
	 */
	function global_icon( $title, $icon, $url )
	{
		$this->global_icons[] = array( 'title' => $title,
																	 'icon'  => $icon,
																	 'url'   => $url );
	}


	/**
	 * Replaces $vars$ with appropriate values
	 */
	function replace_vars( $template )
	{
		return preg_replace_callback( '#\$([a-z_]+)\$#', array( $this, 'callback' ), $template );
	}


 	/**
	 * Callback function used to replace only necessary values in template
	 *
	 * @param array preg matches
	 * @return string to be substituted
	 */
	function callback( $matches )
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

				default :
					return $matches[1];
			}
	}


	/**
	 * Generate img tags for icons
	 */
	function gen_global_icons()
	{
		$r = '';

		foreach( $this->global_icons as $icon_params )
		{
			$r .= action_icon( $icon_params['title'], $icon_params['icon'], $icon_params['url'] );
		}

		return $r;
	}

}

?>