<?php
/**
 * This file implements the Admin UI class.
 * Alternate admin skins should derive from this class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin-skin
 * @subpackage evo
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

require_once dirname(__FILE__).'/../'.$adminskins_dirout.'_adminUI_general.class.php';


/**
 * We'll use the default AdminUI templates etc.
 */
class AdminUI extends AdminUI_general
{

	/**
	 * Get links (to CSS files especially).
	 */
	function getHeadlinks()
	{
		global $mode;

		$r ='<link href="blue.css" rel="stylesheet" type="text/css" title="Blue" />';

		if( $mode == 'sidebar' )
		{ // Include CSS overrides for sidebar:
			$r .= '<link href="sidebar.css" rel="stylesheet" type="text/css" />';
		}

		return $r;
	}


	/**
	 * Get the top of the HTML <body>.
	 *
	 * @uses getPageHead()
	 * @return string
	 */
	function getBodyTop()
	{
		$r = '';

		if( empty($mode) )
		{ // We're not running in an special mode (bookmarklet, sidebar...)

			$r .= $this->getPageHead();

			// Display MAIN menu:
			$r .= $this->getMenu().'

			<div class="panelbody">
			';
		}

		$r .= '

		<div id="payload">
		';

		$r .= $this->getBloglistButtons( '<div id="TitleArea">', '</div>' );

		return $r;
	}


	/**
	 * Close open div(s).
	 *
	 * @return string
	 */
	function getBodyBottom()
	{
		$r = '';

		if( empty($this->mode) )
		{ // We're not running in an special mode (bookmarklet, sidebar...)
			$r .= "\n\t</div>";
		}

		return $r."</div>\n";
	}
}

?>
