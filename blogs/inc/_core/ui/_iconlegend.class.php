<?php
/**
 * This file implements displaying of an Icons legend.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
 * PROGIDISTRI grants Francois PLANQUE the right to license
 * PROGIDISTRI's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * @author fplanque: Francois PLANQUE.
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Display icon legend.
 *
 * Use {@link get_IconLegend()} to get the instance.
 *
 * @package evocore
 */
class IconLegend
{
	/**
	 * List of used icon names
	 * @var array
	 */
	var $icons = array();


	/**
	 * Add an icon with his legend to the icons array
	 *
	 * @param string name of the icon
	 */
	function add_icon( $icon )
	{
		if( !in_array( $icon, $this->icons ) )
		{
			$this->icons[] = $icon;
		}
	}


	/**
	 * Display the icon legend
	 */
	function display_legend()
	{
		if( empty( $this->icons ) )
		{
			return;
		}

		// There are some icons to display:
		echo '<div id="icon_legend">'.T_('Legend').': ';

		// Loop on all map array of filenames for icons to display icons list in the same order:
		foreach( $this->icons as $icon )
		{
			$icon_info = get_icon_info($icon);
			if( ! $icon_info )
			{
				continue;
			}

			echo '<span class="legend_element">'.get_icon( $icon ).' ';

			if( isset( $icon_info['legend'] ) )
			{ // Icon has a legend:
				echo $icon_info['legend'] . ' ';
			}
			else
			{ // Icon has no legend so we use the alt:
				echo $icon_info['alt'] . ' ';
			}

			echo '</span>';
		}

		echo '</div>';
	}


	/**
	 * Reset icons array
	 */
	function reset()
	{
		$this->icons[] = array();
	}
}

/*
 * $Log$
 * Revision 1.6  2011/09/04 22:13:13  fplanque
 * copyright 2011
 *
 * Revision 1.5  2010/02/08 17:51:57  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.4  2009/03/08 23:57:41  fplanque
 * 2009
 *
 * Revision 1.3  2009/02/19 03:54:44  blueyed
 * Optimize: move instantiation of $IconLegend (and $UserSettings query) out of main.inc.php, into get_IconLegend. TODO: test if it works with PHP4, or if it needs assignment by reference. Will do so on the test server.
 *
 * Revision 1.2  2008/01/21 09:35:24  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 10:59:01  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.9  2007/04/26 00:11:08  fplanque
 * (c) 2007
 *
 * Revision 1.8  2006/12/07 23:13:13  fplanque
 * @var needs to have only one argument: the variable type
 * Otherwise, I can't code!
 *
 * Revision 1.7  2006/11/30 22:34:15  fplanque
 * bleh
 *
 * Revision 1.6  2006/11/28 01:02:53  fplanque
 * minor
 *
 * Revision 1.5  2006/11/26 02:30:39  fplanque
 * doc / todo
 *
 * Revision 1.4  2006/11/26 01:42:10  fplanque
 * doc
 *
 */
?>