<?php
/**
 * This file implements the Display Item Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2017 by Francois Planque - {@link http://fplanque.com/}
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
class display_item_Widget extends ComponentWidget
{
	var $icon = 'file-text-o';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'display_item' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'display-item-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Display Item' );
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
		return T_('Display post of type "Post" or "Page".');
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
					'note' => $this->get_param_item_info( 'item_ID' ),
				),
				'item_slug' => array(
					'label' => T_('Item Slug'),
					'size' => 60,
					'note' => $this->get_param_item_info( 'item_slug' ),
				),
				'title_display' => array(
					'label' => T_('Title display' ),
					'type' => 'radio',
					'options' => array(
						array( 'item_title', T_('Item Title') ),
						array( 'custom_title', T_('Custom Title') ),
					),
					'defaultvalue' => 'item_title',
					'field_lines' => true,
				),
				'custom_title' => array(
					'label' => T_('Custom Title'),
					'size' => 60,
				),
				'content_to_display' => array(
					'label' => T_('Content to display'),
					'type' => 'radio',
					'options' => array(
						array( 'full', T_('Full post') ),
						array( 'excerpt', T_('Teaser') )
					),
					'defaultvalue' => 'full',
					'field_lines' => true
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
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		global $Collection, $Blog;

		$params = array_merge( array(
				'item_title_line_before' => '<div class="evo_post_title">',
				'item_title_line_after' => '</div>',
				'item_title_before' => '<h2>',
				'item_title_after' => '</h2>',
			), $params );

		parent::init_display( $params );

		// Get item by ID or slug:
		$widget_Item = & $this->get_widget_Item();

		if( ! $widget_Item )
		{	// No correct Item:
			return;
		}

		if( ! in_array( $widget_Item->get( 'status' ), get_inskin_statuses( $Blog->ID, 'post' ) ) )
		{	// Disable block caching for this widget because target Item is not public for current collection:
			$this->disp_params['allow_blockcache'] = 0;
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

		// Get item by ID or slug:
		$widget_Item = & $this->get_widget_Item();

		if( ! $widget_Item )
		{	// No correct Item:
			return;
		}

		echo $this->disp_params['block_start'];

		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		if( ! $widget_Item || ! in_array( $widget_Item->get_type_setting( 'usage' ), array( 'post', 'page' ) ) )
		{	// Item is not found by ID and slug or it is not a post or page:
			if( $widget_Item )
			{	// It is not a post or page:
				$wrong_item_info = '#'.$widget_Item->ID.' '.$widget_Item->get( 'title' );
			}
			else
			{	// Item is not found:
				$widget_item_ID = intval( $this->disp_params['item_ID'] );
				$wrong_item_info = empty( $widget_item_ID ) ? '' : '#'.$widget_item_ID;
				$wrong_item_info .= empty( $this->disp_params['item_slug'] ) ? '' : ' <code>'.$this->disp_params['item_slug'].'</code>';
			}
			echo '<p class="evo_param_error">'.sprintf( T_('The referenced Item (%s) is not a Post or Standalone Page.'), utf8_trim( $wrong_item_info ) ).'</p>';
		}
		elseif( ! $widget_Item->can_be_displayed() )
		{	// Current user has no permission to view item with such status:
			echo '<p class="evo_param_error">'.sprintf( T_('Post/Page "%s" cannot be included because you have no permission.'), '#'.$widget_Item->ID.' '.$widget_Item->get( 'urltitle' ) ).'</p>';
		}
		elseif( ( $widget_Blog = & $this->get_Blog() ) && (
		          ( $widget_Item->get_blog_ID() == $widget_Blog->ID ) ||
		          ( $widget_Item->get( 'creator_user_ID' ) == $widget_Blog->get( 'owner_user_ID' ) )
		      ) )
		{	// Display an item ONLY if at least one condition:
			//  - Item is in same collection as this widget,
			//  - Item has same owner as owner of this widget's collection:
			global $Item;

			// Save current dispalying Item in temp var:
			$orig_current_Item = $Item;
			$Item = $widget_Item;

			echo $this->disp_params['item_title_line_before'];
			$title_before = $this->disp_params['item_title_before'];
			$title_after = $this->disp_params['item_title_after'];

			// POST TITLE:
			$title_params = array(
					'before'    => $title_before,
					'after'     => $title_after,
					'link_type' => '#'
				);
			if( $this->disp_params['title_display'] == 'custom_title' )
			{
				$title_params['title_field'] = 'title_override';
				$title_params['title_override'] = $this->disp_params['custom_title'];
			}

			$Item->title( $title_params );
			echo $this->disp_params['item_title_line_after'];

			// ---------------------- POST CONTENT INCLUDED HERE ----------------------
			skin_include( '_item_content.inc.php', array(
					'content_mode' => $this->disp_params['content_to_display']
				) );
			// Note: You can customize the default item content by copying the generic
			// /skins/_item_content.inc.php file into the current skin folder.
			// -------------------------- END OF POST CONTENT -------------------------

			// Restore current dispalying Item:
			$Item = $orig_current_Item;
		}
		else
		{	// Display error if the requested content block item cannot be used in this place:
			echo '<p class="evo_param_error">'.sprintf( T_('Post/Page "%s" cannot be included here. It must be in the same collection or have the same owner.'), '#'.$widget_Item->ID.' '.$widget_Item->get( 'urltitle' ) ).'</p>';
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
		global $Collection, $Blog;

		$widget_Item = & $this->get_widget_Item();

		return array(
				'wi_ID'        => $this->ID, // Cache each widget separately + Have the widget settings changed ?
				'set_coll_ID'  => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'cont_coll_ID' => $widget_Item ? $widget_Item->get_blog_ID() : 0, // Has the content of the displayed blog changed ?
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