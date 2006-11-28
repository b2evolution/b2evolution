<?php
/**
 * Display icon legend
 *
 * @package evocore
 */
class IconLegend
{
	/**
	 * @var array List of used icon names
	 */
	var $icons = array();


	/**
	 * Constructor
	 * @return IconLegend
	 */
	function IconLegend()
	{
	}

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