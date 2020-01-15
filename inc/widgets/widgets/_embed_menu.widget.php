<?php
/**
 * This file implements the Embed Menu Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/widgets/_generic_menu_link.widget.php', 'generic_menu_link_Widget' );


/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class embed_menu_Widget extends generic_menu_link_Widget
{
	var $icon = 'navicon';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'embed_menu' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'embed-menu-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Embed Menu');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		$this->load_param_array();

		$SiteMenuCache = & get_SiteMenuCache();

		if( ! empty($this->param_array['menu_ID']) )
		{	// TRANS: %s is the link type, e. g. "Blog home" or "Log in form"
			return T_('Menu').': '.(
				$SiteMenu = & $SiteMenuCache->get_by_ID( $this->param_array['menu_ID'], false, false )
				? $SiteMenu->get( 'name' )
				: '<span class="red">'.T_('Not found').' #'.$this->param_array['menu_ID'].'</span>' );
		}

		return $this->get_name();
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display menu entries');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $current_User, $admin_url;

		$SiteMenuCache = & get_SiteMenuCache();
		$SiteMenuCache->load_where( 'menu_parent_ID IS NULL' );

		$r = array_merge( array(
				'title' => array(
					'label' => T_('Block title'),
					'note' => T_('Title to display in your skin.'),
					'size' => 40,
					'defaultvalue' => '',
				),
				'menu_ID' => array(
					'label' => T_('Menu to display'),
					'input_suffix' => ( is_logged_in() && $current_User->check_perm( 'options', 'edit' ) ? ' <a href="'.$admin_url.'?ctrl=menus">'.T_('Manage Menus').' &gt;&gt;</a>' : '' ),
					'type' => 'select_object',
					'object' => $SiteMenuCache,
					'defaultvalue' => '',
					'allow_empty' => false,
				),
				'display_mode' => array(
					'type' => 'select',
					'label' => T_('Display as'),
					'options' => array(
							'auto'    => T_('Auto'),
							'list'    => T_('List'),
							'buttons' => T_('Buttons'),
						),
					'note' => sprintf( T_('Auto is based on the %s param.'), '<code>inlist</code>' ),
					'defaultvalue' => 'auto',
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache":
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $current_locale;

		$this->init_display( $params );

		$SiteMenuCache = & get_SiteMenuCache();
		if( ! ( $SiteMenu = & $SiteMenuCache->get_by_ID( $this->get_param( 'menu_ID' ), false, false ) ) )
		{	// We cannot use this widget without Menu:
			$this->display_error_message( 'Not found Menu #'.$this->get_param( 'menu_ID' ) );
			return false;
		}

		// Check if the menu has a child matching the current locale:
		$localized_menus = $SiteMenu->get_localized_menus( $current_locale );
		if( ! empty( $localized_menus ) )
		{	// Use localized menu:
			$SiteMenu = & $localized_menus[0];
		}

		// Get Menu Entries:
		$menu_entries = $SiteMenu->get_entries();

		if( empty( $menu_entries ) )
		{	// Don't display if menu has no entries:
			$this->display_debug_message( 'No menu entries' );
			return false;
		}

		switch( $this->get_param( 'display_mode' ) )
		{
			case 'list':
				$this->disp_params['inlist'] = true;
				break;
			case 'buttons':
				$this->disp_params['inlist'] = false;
				break;
			default:
				$this->disp_params['inlist'] = 'auto';
		}

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];

		foreach( $menu_entries as $MenuEntry )
		{
			if( $url = $MenuEntry->get_url() )
			{	// Display a layout with menu link only if it is not restricted by some permission for current User:
				echo $this->get_layout_menu_link( $url, $MenuEntry->get_text(), $MenuEntry->is_active() );
			}
		}

		echo $this->disp_params['block_body_end'];
		echo $this->disp_params['block_end'];

		return true;
	}
}
?>
