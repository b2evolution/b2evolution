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
		global $admin_url;

		load_funcs( 'files/model/_image.funcs.php' );

		// Try to get collection that is used for messages on this site:
		$msg_Blog = & get_setting_Blog( 'msg_blog_ID' );

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
					'note' => T_('Leave empty for current collection.')
						.( $msg_Blog ? ' <span class="red">'.sprintf( T_('The site is <a %s>configured</a> to always use collection %s for profiles/messaging functions.'),
								'href="'.$admin_url.'?ctrl=collections&amp;tab=site_settings"',
								'<b>'.$msg_Blog->get( 'name' ).'</b>' ).'</span>' : '' ),
					'type' => 'integer',
					'allow_empty' => true,
					'size' => 5,
					'defaultvalue' => '',
					'disabled' => $msg_Blog ? 'disabled' : false,
				),
				'visibility' => array(
					'label' => T_( 'Visibility' ),
					'note' => '',
					'type' => 'radio',
					'options' => array(
							array( 'always', T_( 'Always show (cacheable)') ),
							array( 'access', T_( 'Only show if access is allowed (not cacheable)' ) ) ),
					'defaultvalue' => 'always',
					'field_lines' => true,
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
		global $current_User, $disp, $Blog;

		if( ! is_logged_in() )
		{ // Only logged in users can see this menu item
			return false;
		}

		$this->init_display( $params );

		$blog_ID = intval( $this->disp_params['blog_ID'] );
		if( $blog_ID > 0 )
		{	// Try to use collection from widget setting:
			$BlogCache = & get_BlogCache();
			$current_Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );
		}

		if( empty( $current_Blog ) )
		{	// Use current collection if collection is not defined in setting or it doesn't exist in DB:
			$current_Blog = $Blog;
		}

		if( empty( $current_Blog ) )
		{	// Don't use this widget without current collection:
			return false;
		}

		if( $this->disp_params['visibility'] == 'access' && ! $current_Blog->has_access() )
		{	// Don't use this widget because current user has no access to the collection:
			return false;
		}

		// Default link class
		$link_class = $this->disp_params['link_default_class'];

		// set allow blockcache to 0, this way make sure block cache is never allowed for menu items that can be selected
		$this->disp_params[ 'allow_blockcache' ] = 0;

		// Higlight current menu item only when it is linked to current collection and user profile page is displaying currently:
		$highlight_current = ( $current_Blog->ID == $Blog->ID && $disp == 'user' );

		echo $this->disp_params['block_start'];
		echo $this->disp_params['block_body_start'];
		echo $this->disp_params['list_start'];

		if( $highlight_current )
		{	// Use template and class to highlight current menu item:
			$link_class = $this->disp_params['link_selected_class'];
			echo $this->disp_params['item_selected_start'];
		}
		else
		{	// Use normal template:
			echo $this->disp_params['item_start'];
		}

		// Profile link:
		echo $current_User->get_identity_link( array(
				'display_bubbletip' => false,
				'thumb_size'        => $this->disp_params['profile_picture_size'],
				'link_class'        => $link_class,
				'blog_ID'           => $current_Blog->ID,
			) );
	
		if( $highlight_current )
		{	// Use template to highlight current menu item:
			echo $this->disp_params['item_selected_end'];
		}
		else
		{	// Use normal template:
			echo $this->disp_params['item_end'];
		}

		echo $this->disp_params['list_end'];
		echo $this->disp_params['block_body_end'];
		echo $this->disp_params['block_end'];

		return true;
	}
}

?>