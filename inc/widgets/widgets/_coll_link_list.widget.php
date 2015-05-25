<?php
/**
 * This file implements the Link list Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/widgets/_coll_item_list.widget.php', 'coll_item_list_Widget' );

/**
 * links_widget class
 *
 * This widget displays the links from a blog, from the posts with post_type = Link, without using a linkblog.
 *
 * @package evocore
 */
class coll_link_list_Widget extends coll_item_list_Widget
{
	/**
	 * Constructor
	 */
	function coll_link_list_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_link_list' );
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		// This is derived from coll_post_list_Widget, so we DO NOT ADD ANY param here!
		$r = parent::get_param_definitions( $params );
		// We only change the defaults and hide some params.
		$r['title']['defaultvalue'] = T_('Links');
		$r['title_link']['no_edit'] = true;
		$r['item_type']['no_edit'] = true;
		$r['follow_mainlist']['no_edit'] = true;
		$r['blog_ID']['no_edit'] = true;
		$r['cat_IDs']['no_edit'] = true;
		$r['item_title_link_type']['no_edit'] = true;
		$r['disp_excerpt']['no_edit'] = true;
		$r['disp_teaser']['no_edit'] = true;
		$r['disp_teaser_maxwords']['no_edit'] = true;
		$r['widget_css_class']['no_edit'] = true;
		$r['widget_ID']['no_edit'] = true;

		return $r;
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'simple-sidebar-links-list-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Simple Sidebar Links list');
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
		return T_('Simplified Item list for listing Sidebar links.');
	}


	/**
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		// Force some params (because this is a simplified widget):
		$params['item_type'] = '3000';	// Use post types 3000 (sidebar links) only

		parent::init_display( $params );
	}

}

?>