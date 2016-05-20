<?php
/**
 * This file implements the profile_menu_link_Widget class.
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
class profile_menu_link_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'profile_menu_link' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'my-profile-menu-link-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('My Profile Menu link');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return $this->get_name();
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Link to current user profile, including profile picture');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		load_funcs( 'files/model/_image.funcs.php' );

		$r = array_merge( array(
				'profile_picture_size' => array(
					'label' => T_('Profile picture size'),
					'note' => '',
					'type' => 'select',
					'options' => get_available_thumb_sizes(),
					'defaultvalue' => 'crop-top-15x15',
				),
				'blog_ID' => array(
					'label' => T_('Collection ID'),
					'note' => T_('Leave empty for current collection.'),
					'type' => 'integer',
					'allow_empty' => true,
					'size' => 5,
					'defaultvalue' => '',
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{ // Disable "allow blockcache" because this widget uses the selected items
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		parent::init_display( $params );

		// Disable "allow blockcache" because this widget uses the selected items
		$this->disp_params['allow_blockcache'] = 0;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $current_User, $disp;

		if( ! is_logged_in() )
		{ // Only logged in users can see this menu item
			return false;
		}

		$this->init_display( $params );

		// Default link class
		$link_class = $this->disp_params['link_default_class'];

		// set allow blockcache to 0, this way make sure block cache is never allowed for menu items that can be selected
		$this->disp_params[ 'allow_blockcache' ] = 0;

		if( $disp == 'user' )
		{ // The current page is currently displaying the user profile:
			// Let's display it as selected
			$link_class = $this->disp_params['link_selected_class'];
		}

		echo $this->disp_params['block_start'];
		echo $this->disp_params['block_body_start'];
		echo $this->disp_params['list_start'];

		if( $link_class == $this->disp_params['link_selected_class'] )
		{
			echo $this->disp_params['item_selected_start'];
		}
		else
		{
			echo $this->disp_params['item_start'];
		}

		// Profile link:
		echo $current_User->get_identity_link( array(
				'display_bubbletip' => false,
				'thumb_size'        => $this->disp_params['profile_picture_size'],
				'link_class'        => $link_class,
				'blog_ID'           => intval( $this->disp_params['blog_ID'] ),
			) );
	
		if( $link_class == $this->disp_params['link_selected_class'] )
		{
			echo $this->disp_params['item_selected_end'];
		}
		else
		{
			echo $this->disp_params['item_end'];
		}

		echo $this->disp_params['list_end'];
		echo $this->disp_params['block_body_end'];
		echo $this->disp_params['block_end'];

		return true;
	}
}

?>