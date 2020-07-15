<?php
/**
 * This file implements the Generic menu link Widget class as parent for all menu link widgets.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
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
	// Enable additional params for classes of Link/Button:
	var $allow_link_css_params = true;

	/**
	 * Get a layout for menu link
	 *
	 * @param string Link URL
	 * @param string Link text
	 * @param boolean Is active menu link?
	 * @param string Link template, possible masks: $link_url$, $link_class$, $link_text$
	 * @return string
	 */
	function get_layout_menu_link( $link_url, $link_text, $is_active_link, $link_template = NULL )
	{
		if( $link_template === NULL )
		{	// Use default template:
			$link_template = '<a href="$link_url$" class="$link_class$">$link_text$</a>';
		}

		switch( $this->get_display_mode() )
		{
			case 'buttons':
				// "out-of list" button display:
				$item_end = '';
				break;

			case 'tabs':
				// Tabs display mode:
				$item_end = ( $is_active_link ? $this->disp_params['tab_selected_end'] : $this->disp_params['tab_end'] );
				break;

			default:
				// Classic menu link display:
				$item_end = ( $is_active_link ? $this->disp_params['item_selected_end'] : $this->disp_params['item_end'] );
		}

		$r = $this->get_menu_link_item_start( $is_active_link );

		// Get a link/button/tab from template:
		$r .= str_replace(
			array( '$link_url$', '$link_class$', '$link_text$' ),
			array( $link_url, $this->get_link_class( $is_active_link ), $link_text ),
			$link_template );

		$r .= $item_end;

		return $r;
	}


	/**
	 * Get html layout for menu wrappers depending on current display mode
	 *
	 * @param string Type: 'start' or 'end' of the wrapper
	 * @return string
	 */
	function get_layout_menu_wrapper( $type )
	{
		switch( $this->get_display_mode() )
		{
			case 'buttons':
				// "out-of list" button display:
				return $type == 'start'
					? $this->disp_params['button_group_start']
					: $this->disp_params['button_group_end'];

			case 'tabs':
				// Tabs display mode:
				return $type == 'start'
					? $this->disp_params['tabs_start']
					: $this->disp_params['tabs_end'];

			default:
				// Classic menu link display:
				return $type == 'start'
					? $this->disp_params['list_start']
					: $this->disp_params['list_end'];
		}
	}


	/**
	 * Get html layout for item link depending on current display mode
	 *
	 * @param boolean Is active link?
	 * @return string
	 */
	function get_menu_link_item_start( $is_active_link )
	{
		switch( $this->get_display_mode() )
		{
			case 'buttons':
				// Buttons:
				return '';

			case 'tabs':
				// Tabs:
				return ( $is_active_link ? $this->disp_params['tab_selected_start'] : $this->disp_params['tab_start'] );

			default:
				// List:
				return ( $is_active_link ? $this->disp_params['item_selected_start'] : $this->disp_params['item_start'] );
		}
	}


	/**
	 * Get link class depending on current display mode
	 *
	 * @param boolean Is active link?
	 * @return string
	 */
	function get_link_class( $is_active_link )
	{
		switch( $this->get_display_mode() )
		{
			case 'buttons':
				// Buttons:
				if( $is_active_link )
				{	// Class for active button:
					$link_class = empty( $this->disp_params['widget_active_link_class'] ) ? $this->disp_params['button_selected_class'] : $this->disp_params['widget_active_link_class'];
				}
				else
				{	// Class for normal(not active) button:
					$link_class = empty( $this->disp_params['widget_link_class'] ) ? $this->disp_params['button_default_class'] : $this->disp_params['widget_link_class'];
				}
				break;

			case 'tabs':
				// Tabs:
				if( $is_active_link )
				{	// Class for active tab:
					$link_class = $this->disp_params['tab_selected_class'].( empty( $this->disp_params['widget_active_link_class'] ) ? '' : ' '.$this->disp_params['widget_active_link_class'] );
				}
				else
				{	// Class for normal(not active) tab:
					$link_class = $this->disp_params['tab_default_class'].( empty( $this->disp_params['widget_link_class'] ) ? '' : ' '.$this->disp_params['widget_link_class'] );
				}
				break;

			default:
				// List:
				if( $is_active_link )
				{	// Class for active link:
					$link_class = $this->disp_params['link_selected_class'].( empty( $this->disp_params['widget_active_link_class'] ) ? '' : ' '.$this->disp_params['widget_active_link_class'] );
				}
				else
				{	// Class for normal(not active) link:
					$link_class = $this->disp_params['link_default_class'].( empty( $this->disp_params['widget_link_class'] ) ? '' : ' '.$this->disp_params['widget_link_class'] );
				}
				break;
		}

		if( ! empty( $this->disp_params['link_type'] ) )
		{	// Append class per link type:
			$link_class .= ' evo_widget_'.$this->code.'_'.$this->disp_params['link_type'];
		}

		return trim( $link_class );
	}


	/**
	 * Get display mode
	 *
	 * @return string Display mode: 'list', 'buttons', 'tabs'
	 */
	function get_display_mode()
	{
		if( isset( $this->disp_params['display_mode'] ) &&
		    in_array( $this->disp_params['display_mode'], array( 'list', 'buttons', 'tabs' ) ) )
		{	// Use provided display mode:
			return $this->disp_params['display_mode'];
		}

		// Get auto display mode:

		// Are we displaying a link in a list or a standalone button?
		// "Menu" Containers are 'inlist'. Some sub-containers will also be 'inlist' (displaying a local menu).
		// fp> Maybe this should be moved up to container level? 
		$inlist = isset( $this->disp_params['inlist'] ) ? $this->disp_params['inlist'] : ( isset( $this->disp_params['display_mode'] ) ? $this->disp_params['display_mode'] : false );
		if( $inlist === 'auto' )
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

		return $inlist ? 'list' : 'buttons';
	}


	/**
	 * Get a layout for standalone menu link
	 *
	 * @param string Link URL
	 * @param string Link text
	 * @param boolean Is active menu link?
	 * @param string Link template, possible masks: $link_url$, $link_class$, $link_text$
	 * @return string
	 */
	function get_layout_standalone_menu_link( $link_url, $link_text, $is_active_link, $link_template = NULL )
	{
		$r = $this->disp_params['block_start'];
		$r .= $this->disp_params['block_body_start'];

		$r .= $this->get_layout_menu_wrapper( 'start' );
		$r .= $this->get_layout_menu_link( $link_url, $link_text, $is_active_link, $link_template );
		$r .= $this->get_layout_menu_wrapper( 'end' );

		$r .= $this->disp_params['block_body_end'];
		$r .= $this->disp_params['block_end'];

		return $r;
	}


	/**
	 * Display debug message e-g on designer mode when we need to show widget when nothing to display currently
	 *
	 * @param string Message
	 */
	function display_debug_message( $message = NULL )
	{
		if( $this->mode == 'designer' )
		{	// Display message on designer mode:
			if( $message === NULL )
			{	// Set default message:
				$message = 'Hidden';
				if( ! empty( $this->disp_params['link_type'] ) )
				{
					$message .= '('.$this->disp_params['link_type'].')';
				}
			}

			echo $this->get_layout_standalone_menu_link( '#', $message, false );
		}
	}
}