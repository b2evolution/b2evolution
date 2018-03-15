<?php
/**
 * This file implements the Related post list Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/widgets/_coll_item_list.widget.php' , 'coll_item_list_Widget' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class coll_related_post_list_Widget extends coll_item_list_Widget
{
	var $icon = 'list-alt';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		ComponentWidget::__construct( $db_row, 'core', 'coll_related_post_list' );
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$ItemTypeCache = & get_ItemTypeCache();
		$ItemTypeCache->clear();
		$ItemTypeCache->load_where( 'ityp_usage = "post"' ); // Load only post item types
		$item_type_cache_load_all = $ItemTypeCache->load_all; // Save original value
		$ItemTypeCache->load_all = false; // Force to don't load all item types in get_option_array() below
		$post_item_type_options =
			array(
				''  => T_('All'),
			) + $ItemTypeCache->get_option_array();
		// Revert back to original value:
		$ItemTypeCache->load_all = $item_type_cache_load_all;

		// This is derived from coll_post_list_Widget, so we DO NOT ADD ANY param here!
		$r = parent::get_param_definitions( $params );
		// We only change the defaults and hide some params.
		$r['title']['defaultvalue'] = T_('Related posts');
		$r['title_link']['no_edit'] = true;
		$r['item_type_usage']['no_edit'] = true;
		$r['featured']['no_edit'] = true;
		$r['follow_mainlist']['no_edit'] = true;
		$r['blog_ID']['no_edit'] = true;
		$r['cat_IDs']['no_edit'] = true;
		$r['item_group_by']['no_edit'] = true;
		$r['item_title_link_type']['no_edit'] = true;
		$r['disp_first_image']['no_edit'] = true;
		$r['disp_first_image']['defaultvalue'] = 'special';
		$r['item_pic_link_type']['no_edit'] = true;
		// $r['disp_excerpt']['no_edit'] = true;
		// $r['disp_teaser']['no_edit'] = true;
		$r['disp_teaser_maxwords']['no_edit'] = true;
		$r['widget_css_class']['no_edit'] = true;
		$r['widget_ID']['no_edit'] = true;

		// Allow to select what post item type to display:
		$r['item_type'] = array(
				'label' => T_('Exact post type'),
				'note' => T_('What type of items do you want to list?'),
				'type' => 'select',
				'options' => $post_item_type_options,
				'defaultvalue' => '',
			);

		return $r;
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'simple-related-posts-list-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Simple Related Posts list');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output($this->disp_params['title']);
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Simplified Item list for listing posts related to the Main list.');
	}


	/**
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		// Force some params (because this is a simplified widget):
		$params['item_type_usage'] = 'post';	// Use post types usage "post" only
		$params['follow_mainlist'] = 'tags';	// Follow tags for relation

		parent::init_display( $params );
	}

}

?>