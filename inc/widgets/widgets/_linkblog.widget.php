<?php
/**
 * This file implements the linkblog_Widget class.
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
 * linkblog_widget class
 *
 * This widget displays another blog as a linkblog.
 *
 * @package evocore
 */
class linkblog_Widget extends coll_item_list_Widget
{
	/**
	 * Constructor
	 */
	function linkblog_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'linkblog' );
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
		$r['title']['defaultvalue'] = T_('Linkblog');
		$r['title_link']['no_edit'] = true;
		$r['follow_mainlist']['no_edit'] = true;
		$r['cat_IDs']['no_edit'] = true;
		$r['item_group_by']['defaultvalue'] = 'chapter';
		$r['item_title_link_type']['no_edit'] = true;
		$r['item_title_link_type']['defaultvalue'] = 'auto';
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
		return get_manual_url( 'simple-linkblog-links-list-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Simple Linkblog Links list');
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
		return T_('Simplified Item list for listing links from another blog.');
	}


	/**
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		global $Blog;

		// Force some params (because this is a simplified widget):
		if( $this->disp_params['blog_ID'] == 0 )
		{	// We want to try and use previous defaults:
			if( !empty( $this->disp_params['linkblog_ID'] ) )
			{
				$params['blog_ID'] = $this->disp_params['linkblog_ID'];
			}
			else
			{ // Recycle the previous value from deprecated links_blog_ID param. We will eventually drop that field from the database.
				$params['blog_ID'] = $Blog->get('links_blog_ID');
			}
		}

		parent::init_display( $params );
	}

}

?>