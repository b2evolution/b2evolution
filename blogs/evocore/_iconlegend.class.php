<?php
/**
 * Display icon legend
 */
class IconLegend 
{
	
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
	 * @param icon legend
	 */
	function add_icon( $icon, $legend)
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
		global $map_iconfiles;
		
		if( !empty( $this->icons ) )
		{	// There are some icons to display
			$r = '<div id="icon_legend">';
			// Loop on all icons
			foreach( $this->icons as $icon )
			{
				$r .= '<span class="legend_element">'
							.get_icon( $icon) . ' ';
				
				if( isset( $map_iconfiles[$icon]['legend'] ) )
				{ // Icon has a legend
					$r .= $map_iconfiles[$icon]['legend'] . ' ';		
				}
				else 
				{ // Icon has no legend so we use the alt
					$r .= $map_iconfiles[$icon]['alt'] . ' ';	
				}
				$r .= '</span>';
			}
			
			$r .= '</div>';
			// Display icon legende
			echo $r;
		}
	}
	
	/**
	 * 
	 */
	function reset()
	{
	
	}
} 
?>