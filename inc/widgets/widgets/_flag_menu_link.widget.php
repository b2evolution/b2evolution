<?php
/**
 * This file implements the flag_menu_link_Widget class.
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
class flag_menu_link_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'flag_menu_link' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'flagged-items-menu-link-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Flagged Items Menu Link');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( $this->disp_params['link_text'] );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return $this->get_name();
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
				'link_text' => array(
					'label' => T_('Link text'),
					'note' => T_('Text to use for the link (leave empty for default).'),
					'type' => 'text',
					'size' => 20,
					'defaultvalue' => '',
				),
				'blog_ID' => array(
					'label' => T_('Collection ID'),
					'note' => T_('Leave empty for current collection.'),
					'type' => 'integer',
					'allow_empty' => true,
					'size' => 5,
					'defaultvalue' => '',
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
				'show_badge' => array(
					'label' => T_( 'Show Badge' ),
					'note' => T_('Show a badge with the count of flagged items.'),
					'type' => 'checkbox',
					'defaultvalue' => true,
				),
				'hide_empty' => array(
					'label' => T_( 'Hide if empty' ),
					'note' => T_('Check to hide this menu if the list is empty.'),
					'type' => 'checkbox',
					'defaultvalue' => false,
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache" because this widget uses the selected items:
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

		// Disable "allow blockcache" because this widget uses the selected items:
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
		{	// Only logged in user can flag items:
			return false;
		}

		$this->init_display( $params );

		$blog_ID = intval( $this->disp_params['blog_ID'] );
		if( $blog_ID > 0 )
		{	// Try to use blog from widget setting:
			$BlogCache = & get_BlogCache();
			$current_Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );
		}

		if( empty( $current_Blog ) )
		{	// Blog is not defined in setting or it doesn't exist in DB:
			global $Collection, $Blog;
			// Use current blog:
			$current_Blog = & $Blog;
		}

		if( empty( $current_Blog ) )
		{	// Don't use this widget without current collection:
			return false;
		}

		if( $this->disp_params['visibility'] == 'access' && ! $current_Blog->has_access() )
		{	// Don't use this widget because current user has no access to the collection:
			return false;
		}

		if( $this->disp_params['hide_empty'] && $current_User->get_flagged_items_count() == 0 )
		{	// Hide this menu if current user has no flagged posts yet:
			return false;
		}

		$url = $current_Blog->get( 'flaggedurl' );
		$text = empty( $this->disp_params['link_text'] ) ? T_('Flagged Items') : $this->disp_params['link_text'];

		// Higlight current menu item only when it is linked to current collection and flagged items page is displaying currently:
		$highlight_current = ( $current_Blog->ID == $Blog->ID && $disp == 'flagged' );

		$badge = '';
		if( $this->disp_params['show_badge'] )
		{	// Show badge with count of flagged items:
			$flagged_items_count = $current_User->get_flagged_items_count();
			if( $flagged_items_count > 0 )
			{	// If at least one flagged item:
				$badge = ' <span class="badge badge-warning">'.$flagged_items_count.'</span>';
			}
		}

		echo $this->disp_params['block_start'];
		echo $this->disp_params['block_body_start'];
		echo $this->disp_params['list_start'];

		if( $highlight_current )
		{	// Use template and class to highlight current menu item:
			$link_class = $this->disp_params['link_selected_class'];
			echo $this->disp_params['item_selected_start'];
		}
		else
		{	// Use normal template and class:
			$link_class = $this->disp_params['link_default_class'];
			echo $this->disp_params['item_start'];
		}
		echo '<a href="'.$url.'" class="'.$link_class.'">'.$text.$badge.'</a>';
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