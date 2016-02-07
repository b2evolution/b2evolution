<?php
/**
 * This file implements the post type cache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );

/**
 * ItemTypeCache Class
 *
 * @package evocore
 */
class ItemTypeCache extends DataObjectCache
{
	/**
	 * Post type cache for each collection
	 */
	var $col_cache = array();

	/**
	 * Default post type for each collection
	 */
	var $col_default = array();


	/**
	 * Constructor
	 *
	 * @param table Database row
	 */
	function __construct()
	{
		// Call parent constructor:
		parent::__construct( 'ItemType', true, 'T_items__type', 'ityp_', 'ityp_ID', 'ityp_name', 'ityp_ID' );
	}


	/**
	 * Returns a form option list which only contains post types that can
	 * be used by the current user (and in the current blog's context).
	 *
	 * The user cannot use any post type IDs listed in the {@see $posttypes_reserved_IDs}
	 * array; to use the "Page", "Intro-*", "Podcast" and "Sidebar link"
	 * post types, the current blog must grant the blog_page, blog_intro,
	 * blog_podcast and blog_sidebar permission, respectively (see blog
	 * user/group permissions).
	 * 
	 * @deprecated
	 * 
	 * @param integer The selected ID.
	 * @param boolean Provide a choice for "none" with ID ''
	 * @param string  Callback method name.
	 * @return string
	 */
	function get_option_list_usable_only( $default = 0, $allow_none = false, $method = 'get_name' )
	{
		return $this->get_option_list( $default, $allow_none, $method );
	}


	/**
	 * For use by Universal Item List widget
	 *
	 * @param array IDs to ignore.
	 * @return array
	 */
	function get_option_array( $ignore_IDs = array() )
	{
		return $this->get_option_array_worker( 'get_name', $ignore_IDs );
	}


	/**
	 * For use by Universal Item List widget and item type edit form
	 *
	 * @return array
	 */
	function get_usage_option_array()
	{
		return array(
				T_('In content flow') => array(
						'post' => T_('Post')
					),
				T_('Out of content flow') => array(
						'page'    => T_('Page'),
						'special' => T_('Special'),
					),
				T_('Intros') => array(
						'intro-front' => T_('Intro-Front'),
						'intro-main'  => T_('Intro-Main'),
						'intro-cat'   => T_('Intro-Cat'),
						'intro-tag'   => T_('Intro-Tag'),
						'intro-sub'   => T_('Intro-Sub'),
						'intro-all'   => T_('Intro-All'),
					),
			);
	}
}

?>