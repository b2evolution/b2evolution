<?php
/**
 * This file implements the Generic menu link Widget class as parent for all menu link widgets.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );


/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class generic_menu_link_Widget extends ComponentWidget
{
	/**
	 * Get a layout for menu link
	 *
	 * @param string Link URL
	 * @param string Link text
	 * @param boolean Is active menu link?
	 * @param string Link template, possible masks: $link_url$, $link_class$, $link_text$
	 * @return string
	 */
	function get_layout_menu_link( $link_url, $link_text, $is_active_link, $link_template = '<a href="$link_url$" class="$link_class$">$link_text$</a>' )
	{
		$r = $this->disp_params['block_start'];
		$r .= $this->disp_params['block_body_start'];

		// Are we displaying a link in a list or a standalone button?
		// "Menu" Containers are 'inlist'. Some sub-containers will also be 'inlist' (displaying a local menu).
		// fp> Maybe this should be moved up to container level? 
		$inlist = $this->disp_params['inlist'];
		if( $inlist == 'auto' )
		{
			if( empty( $this->disp_params['list_start'] ) )
			{	// We're not starting a list. This means (very high probability) that we are already in a list:
				$inlist = true;
			}
			else
			{	// We have no override for list start. This means (very high probability) that we are displaying a standalone link -> we want a button for this widget
				$inlist = false;
			}
		}

		if( $inlist )
		{	// Classic menu link display:

			// It's debatable whether of not we want 'list_start' here but it doesn't hurt to keep it (will be empty under typical circumstances):
			$r .= $this->disp_params['list_start'];

			if( $is_active_link )
			{	// Use template and class to highlight current menu item:
				$r .= $this->disp_params['item_selected_start'];
				$link_class = $this->disp_params['link_selected_class'];
			}
			else
			{	// Use normal template:
				$r .= $this->disp_params['item_start'];
				$link_class = $this->disp_params['link_default_class'];
			}

			// Get a link from template:
			$r .= str_replace(
				array( '$link_url$', '$link_class$', '$link_text$' ),
				array( $link_url, $link_class, $link_text ),
				$link_template );

			if( $is_active_link )
			{	// Use template to highlight current menu item:
				$r .= $this->disp_params['item_selected_end'];
			}
			else
			{	// Use normal template:
				$r .= $this->disp_params['item_end'];
			}

			$r .= $this->disp_params['list_end'];
		}
		else
		{	// "out-of list" button display:

			if( $is_active_link )
			{	// Use template and class to highlight current menu item:
				$button_class = $this->disp_params['button_selected_class'];
			}
			else
			{	// Use normal template:
				$button_class = $this->disp_params['button_default_class'];
			}

			// Get a button from template:
			$r .= str_replace(
				array( '$link_url$', '$link_class$', '$link_text$' ),
				array( $link_url, $button_class, $link_text ),
				$link_template );
		}

		$r .= $this->disp_params['block_body_end'];
		$r .= $this->disp_params['block_end'];

		return $r;
	}
}