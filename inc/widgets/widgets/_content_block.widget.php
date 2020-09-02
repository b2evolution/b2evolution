<?php
/**
 * This file implements the Content Block Widget class.
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
class content_block_Widget extends ComponentWidget
{
	var $icon = 'file-text-o';

	var $widget_Item = NULL;

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
		return T_('Content Block');
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
	 * Get a clean description to display in the widget list.
	 * @return string
	 */
	function get_desc_for_list()
	{
		$short_desc = $this->get_short_desc();

		$r = $this->get_icon()
			.' <strong>'.( empty( $short_desc ) ? $this->get_name() : $short_desc ).'</strong>';

		if( $widget_Item = & $this->get_widget_Item() )
		{	// Display a title of widget Item if it is defined:
			$r .= ' ('.$widget_Item->dget( 'title' ).')';
		}
		elseif( ! empty( $short_desc ) )
		{	// Display a widget name:
			$r .= ' ('.$this->get_name().')';
		}

		return $r;
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

		// Get available templates:
		$context = 'content_block';
		$TemplateCache = & get_TemplateCache();
		$TemplateCache->load_by_context( $context );
		$template_options = $TemplateCache->get_code_option_array();
		$template_input_suffix = ( check_user_perm( 'options', 'edit' ) ? '&nbsp;'
				.action_icon( '', 'edit', $admin_url.'?ctrl=templates&amp;context='.$context, NULL, NULL, NULL,
				array( 'onclick' => 'return b2template_list_highlight( this )', 'target' => '_blank' ),
				array( 'title' => T_('Manage templates').'...' ) ) : '' );

		$ItemTypeCache = & get_ItemTypeCache();
		$ItemTypeCache->clear();
		$ItemTypeCache->load_where( 'ityp_usage = "content-block"' ); // Load only post item types
		$item_type_cache_load_all = $ItemTypeCache->load_all; // Save original value
		$ItemTypeCache->load_all = false; // Force to don't load all item types in get_option_array() below
		$post_item_type_options =
			array(
				''  => T_('All content blocks'),
			) + $ItemTypeCache->get_option_array();
		// Revert back to original value:
		$ItemTypeCache->load_all = $item_type_cache_load_all;

		$default_select_type = 'item';
		$current_select_type = $this->get_param( 'select_type', $default_select_type );

		$r = array_merge( array(
				'title' => array(
					'label' => T_('Block title'),
					'size' => 60,
				),
				'template' => array(
					'label' => T_('Template'),
					'type' => 'select',
					'options' => $template_options,
					'defaultvalue' => 'cblock_clearfix',
					'input_suffix' => $template_input_suffix,
					'class' => 'evo_template_select',
				),
				'select_type' => array(
					'label' => T_('Select content block'),
					'note' => '',
					'type' => 'radio',
					'options' => array(
							array( 'item', T_('By Item ID or Slug') ),
							array( 'random', T_('Randomly') ) ),
					'defaultvalue' => $default_select_type,
					'field_lines' => true,
				),
				'item_ID' => array(
					'label' => T_('Item ID'),
					'type' => 'integer',
					'allow_empty' => true,
					'size' => 13,
					'valid_range' => array(
						'min' => 1,
						'max' => 4294967295,
					),
					'note' => $this->get_param_item_info( 'item_ID' ),
					'hide' => ( $current_select_type != 'item' ),
				),
				'item_slug' => array(
					'label' => T_('Item Slug'),
					'size' => 60,
					'note' => $this->get_param_item_info( 'item_slug' ),
					'hide' => ( $current_select_type != 'item' ),
				),
				'item_type_ID' => array(
					'label' => T_('Exact Item Type'),
					'type' => 'select',
					'options' => $post_item_type_options,
					'defaultvalue' => '',
					'hide' => ( $current_select_type != 'random' ),
				),
				'coll_ID' => array(
					'label' => T_('From Collection ID'),
					'note' => T_('Leave empty for current collection.'),
					'type' => 'integer',
					'allow_empty' => true,
					'size' => 5,
					'valid_range' => array(
						'min' => 1,
						'max' => 4294967295,
					),
					'hide' => ( $current_select_type != 'random' ),
				),
			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Get JavaScript code which helps to edit widget form
	 *
	 * @return string
	 */
	function get_edit_form_javascript()
	{
		return 'jQuery( "[name='.$this->get_param_prefix().'select_type]" ).click( function()
		{
			var select_type_value = jQuery( this ).val();
			// Hide/Show Item ID and Slug:
			jQuery( "#ffield_'.$this->get_param_prefix().'item_ID, #ffield_'.$this->get_param_prefix().'item_slug" ).toggle( select_type_value == "item" );
			// Hide/Show Exact Item Type:
			jQuery( "#ffield_'.$this->get_param_prefix().'item_type_ID, #ffield_'.$this->get_param_prefix().'coll_ID" ).toggle( select_type_value == "random" );
		} );';
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
		parent::init_display( $params );

		if( $this->get_param( 'select_type' ) == 'random' )
		{	// Disable block caching for this widget when items are displayed randomly:
			$this->disp_params['allow_blockcache'] = 0;
		}

		$widget_Item = & $this->get_widget_Item();

		if( $widget_Item && ! in_array( $widget_Item->get( 'status' ), get_inskin_statuses( $widget_Item->get_blog_ID(), 'post' ) ) )
		{	// Disable block caching for this widget because target Item is not public for its collection:
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
		global $Item;

		$this->init_display( $params );

		$TemplateCache = & get_TemplateCache();

		if( ! empty( $this->disp_params['template'] ) &&
		    ! ( $cat_Template = & $TemplateCache->get_by_code( $this->disp_params['template'], false, false ) ) )
		{	// Display error when no or wrong template:
			$this->display_error_message( sprintf( 'Template is not found: %s', '<code>'.$this->disp_params['template'].'</code>' ) );
			return false;
		}

		// Get item by ID or slug:
		$widget_Item = & $this->get_widget_Item();

		if( ! $widget_Item && $this->get_param( 'select_type' ) == 'random' )
		{	// If no item found ramdomly:
			$this->display_error_message( T_('No Item is found randomly.') );
			return false;
		}

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
			$this->display_error_message( sprintf( T_('The referenced Item (%s) is not a Content Block.'), utf8_trim( $wrong_item_info ) ) );
			return false;
		}

		if( ! $widget_Item->can_be_displayed() )
		{	// Current user has no permission to view item with such status:
			$this->display_error_message( sprintf( T_('Content block "%s" cannot be included because you have no permission.'), '#'.$widget_Item->ID.' '.$widget_Item->get( 'urltitle' ) ) );
			return false;
		}

		// Display a content block Item if at least one condition is true:
		$content_block_is_allowed = false;
		if( isset( $Item ) && ( $Item instanceof Item ) )
		{	// 1. Content block Item has same owner as owner of the current Item:
			$content_block_is_allowed = $widget_Item->get( 'creator_user_ID' ) == $Item->get( 'creator_user_ID' );
		}
		$content_block_is_allowed = $content_block_is_allowed ||
			// 2. Content block Item is in same collection as this widget:
			( ( $widget_Blog = & $this->get_Blog() ) && $widget_Item->get_blog_ID() == $widget_Blog->ID ) ||
			// 3. Content block Item has same owner as owner of this widget's collection:
			( ( $widget_Blog = & $this->get_Blog() ) && $widget_Item->get( 'creator_user_ID' ) == $widget_Blog->get( 'owner_user_ID' ) ) ||
			// 4. Content block Item from collection for shared content blocks:
			( ( $info_Blog = & get_setting_Blog( 'info_blog_ID' ) ) && $widget_Item->get_blog_ID() == $info_Blog->ID );

		if( ! $content_block_is_allowed )
		{	// Display error if the requested content block item cannot be used in this place:
			if( isset( $Item ) && ( $Item instanceof Item ) )
			{	// For page with current Item:
				$this->display_error_message( sprintf(
					T_('Content block #%d %s (Coll #%d) (Owner: %s) cannot be included here. It must be in the same collection as this Widget (Coll #%d) or the info pages collection (Coll #%d)').'; '.
					T_('in any other case, it must have the same owner as the current Item (Item #%d) of the page (Owner: %s) or the same owner as the current Item\'s collection (Owner: %s).'),
						$widget_Item->ID, '<code>'.$widget_Item->get( 'urltitle' ).'</code>', // Content block #%d %s
						$widget_Item->get_blog_ID(), // (Coll #%d)
						get_user_identity_link( NULL, $widget_Item->get( 'creator_user_ID' ) ), // (Owner: %s)
						$widget_Blog->ID, // as this Widget (Coll #%d)
						( $info_Blog = & get_setting_Blog( 'info_blog_ID' ) ) ? $info_Blog->ID : 0, // the info pages collection (Coll #%d)
						$Item->ID, get_user_identity_link( NULL, $Item->get( 'creator_user_ID' ) ), // the current Item (Item #%d) (Owner: %s)
						$Item->get_Blog() ? get_user_identity_link( NULL, $Item->get_Blog()->get( 'owner_user_ID' ) ) : '<code>'.T_('No collection found').'</code>' // the current Item\'s collection (Owner: %s)
					) );
			}
			else
			{	// For page without current Item:
				$this->display_error_message( sprintf(
					T_('Content block #%d %s (Coll #%d) (Owner: %s) cannot be included here. It must be in the same collection as this Widget (Coll #%d) or the info pages collection (Coll #%d)').'. '.
					T_('In any other case, it must have the same owner as the current collection (Owner: %s). Note: this page has no current Item, so we cannot check for "same owner as current Item".'),
						$widget_Item->ID, '<code>'.$widget_Item->get( 'urltitle' ).'</code>', // Content block #%d %s
						$widget_Item->get_blog_ID(), // (Coll #%d)
						get_user_identity_link( NULL, $widget_Item->get( 'creator_user_ID' ) ), // (Owner: %s)
						$widget_Blog->ID, // as this Widget (Coll #%d)
						( $info_Blog = & get_setting_Blog( 'info_blog_ID' ) ) ? $info_Blog->ID : 0, // the info pages collection (Coll #%d)
						$widget_Blog ? get_user_identity_link( NULL, $widget_Blog->get( 'owner_user_ID' ) ) : '<code>'.T_('No collection found').'</code>' // the current collection (Owner: %s)
					) );
			}
			return false;
		}

		echo $this->disp_params['block_start'];

		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		echo $widget_Item->get_content_block( array_merge( $params, array( 'template_code' => $this->disp_params['template'] ) ) );

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
				'template_code'=> $this->get_param( 'template' ), // Has the Template changed?
			);
	}


	/**
	 * Get Item which is used for this widget
	 *
	 * @return object Item
	 */
	function & get_widget_Item()
	{
		if( $this->widget_Item === NULL )
		{	// Get widget Item once:
			$ItemCache = & get_ItemCache();

			switch( $this->get_param( 'select_type' ) )
			{
				case 'random':
					// Get Item randomly:
					$BlogCache = & get_BlogCache();
					if( ! ( $widget_Blog = & $BlogCache->get_by_ID( $this->get_param( 'coll_ID' ), false, false ) ) )
					{	// Use current Collection if not defined in widget settings:
						global $Collection, $Blog;
						$widget_Blog = $Blog;
					}

					// Use ItemList in order to get only available items by visibility for current User:
					$ItemList = new ItemList2( $widget_Blog, $widget_Blog->get_timestamp_min(), $widget_Blog->get_timestamp_max(), 1, 'ItemCache', $this->code.'_' );
					// Set additional debug info prefix for SQL queries to know what widget executes it:
					$ItemList->query_title_prefix = get_class( $this );

					// Set filters:
					$filters = array(
						'itemtype_usage' => 'content-block',
						'orderby' => 'RAND',
					);
					$item_type_ID = intval( $this->get_param( 'item_type_ID' ) );
					if( ! empty( $item_type_ID ) )
					{	// Filter by Exact Item Type:
						$filters['types'] = $item_type_ID;
					}
					$ItemList->set_filters( $filters, false ); // we don't want to memorize these params

					// Run the query:
					$ItemList->query();

					// Try to get an Item from filtered list:
					$this->widget_Item = & $ItemList->get_item();
					break;

				default:
					// Get Item by ID or fallback by slug:
					if( ! ( $this->widget_Item = & $ItemCache->get_by_ID( $this->disp_params['item_ID'], false, false ) ) )
					{	// Try to get item by slug if it is not found by ID:
						$this->widget_Item = & $ItemCache->get_by_urltitle( trim( $this->disp_params['item_slug'] ), false, false );
					}
			}

			if( $this->widget_Item === NULL )
			{	// Set false to don't call this twice:
				$this->widget_Item = false;
			}
		}

		return $this->widget_Item;
	}
}
?>
