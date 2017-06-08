<?php
/**
 * This file implements displaying of an Icons legend.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 *
 * @author fplanque: Francois PLANQUE.
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
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

?>