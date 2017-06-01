<?php
/**
 * This file implements the Content Block Widget class.
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
class content_block_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'content_block' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'content-block-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Content Block' );
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 *
	 * @return string The block title, the first 60 characters of the block
	 *                content or an empty string.
	 */
	function get_short_desc()
	{
		return format_to_output( $this->disp_params['title'] );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display post of type "Content Block".');
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
				'title' => array(
					'label' => T_('Block title'),
					'size' => 60,
				),
				'item_ID' => array(
					'label' => T_('Item ID'),
					'type' => 'integer',
					'allow_empty' => true,
					'size' => 5,
				),
				'item_slug' => array(
					'label' => T_('Item Slug'),
					'size' => 60,
				),
			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Load params
	 */
	function load_from_Request()
	{
		parent::load_from_Request();

		if( get_param( $this->get_param_prefix().'item_ID' ) != '' && get_param( $this->get_param_prefix().'item_slug' ) != '' )
		{	// Don't allow both entered fields:
			param_error( $this->get_param_prefix().'item_ID', NULL );
			param_error( $this->get_param_prefix().'item_slug', T_('Please enter either an Item ID or an Item Slug, but not both.') );
		}
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		$this->init_display( $params );

		echo $this->disp_params['block_start'];

		$this->disp_title( $this->disp_params['title'] );

		echo $this->disp_params['block_body_start'];

		// Get item by ID or slug:
		$widget_Item = & $this->get_widget_Item();

		if( ! $widget_Item || $widget_Item->get_type_setting( 'usage' ) != 'content-block' )
		{	// Item is not found by ID and slug or it is not a content block:
			if( $widget_Item )
			{	// It is not a content block:
				$wrong_item_info = '#'.$widget_Item->ID.' '.$widget_Item->get( 'title' );
			}
			else
			{	// Item is not found:
				$widget_item_ID = intval( $this->disp_params['item_ID'] );
				$wrong_item_info = empty( $widget_item_ID ) ? '' : '#'.$widget_item_ID;
				$wrong_item_info .= empty( $this->disp_params['item_slug'] ) ? '' : ' <code>'.$this->disp_params['item_slug'].'</code>';
			}
			echo '<p class="red">'.sprintf( T_('The referenced Item (%s) is not a Content Block.'), utf8_trim( $wrong_item_info ) ).'</p>';
		}
		else
		{	// Display a content block item:
			global $Item;

			// Save current dispalying Item in temp var:
			$orig_current_Item = $Item;
			$Item = $widget_Item;

			// ---------------------- POST CONTENT INCLUDED HERE ----------------------
			skin_include( '_item_content.inc.php', array(
					'content_mode' => 'full'
				) );
			// Note: You can customize the default item content by copying the generic
			// /skins/_item_content.inc.php file into the current skin folder.
			// -------------------------- END OF POST CONTENT -------------------------

			// Restore current dispalying Item:
			$Item = $orig_current_Item;
		}

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		$widget_Item = & $this->get_widget_Item();

		return array(
				'wi_ID'        => $this->ID, // Cache each widget separately + Have the widget settings changed ?
				'set_coll_ID'  => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'item_ID'      => $widget_Item ? $widget_Item->ID : 0, // Cache each item separately + Has the Item changed?
			);
	}


	/**
	 * Get Item which is used for this widget
	 *
	 * @return object Item
	 */
	function & get_widget_Item()
	{
		$ItemCache = & get_ItemCache();

		if( ! ( $widget_Item = & $ItemCache->get_by_ID( $this->disp_params['item_ID'], false, false ) ) )
		{	// Try to get item by slug if it is not found by ID:
			$widget_Item = & $ItemCache->get_by_urltitle( trim( $this->disp_params['item_slug'] ), false, false );
		}

		return $widget_Item;
	}
}
?>