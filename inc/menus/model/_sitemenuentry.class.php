<?php
/**
 * This file implements the Site Menu class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );


/**
 * Menu Entry Class
 *
 * @package evocore
 */
class SiteMenuEntry extends DataObject
{
	var $menu_ID;
	var $parent_ID;
	var $order;
	var $text;
	var $type;
	var $coll_logo_size;
	var $coll_ID;
	var $url;
	var $visibility = 'always';
	var $highlight;

	/**
	 * Category children list
	 */
	var $children = array();
	var $children_sorted = false;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_menus__entry', 'ment_', 'ment_ID' );

		if( $db_row != NULL )
		{	// Get menu entry data from DB:
			$this->ID = $db_row->ment_ID;
			$this->menu_ID = $db_row->ment_menu_ID;
			$this->parent_ID = $db_row->ment_parent_ID;
			$this->order = $db_row->ment_order;
			$this->text = $db_row->ment_text;
			$this->type = $db_row->ment_type;
			$this->coll_logo_size = $db_row->ment_coll_logo_size;
			$this->coll_ID = $db_row->ment_coll_ID;
			$this->url = $db_row->ment_url;
			$this->visibility = $db_row->ment_visibility;
			$this->highlight = $db_row->ment_highlight;
		}
	}


	/**
	 * Get delete cascade settings
	 *
	 * @return array
	 */
	static function get_delete_cascades()
	{
		return array(
				array( 'table' => 'T_menus__entry', 'fk' => 'ment_menu_ID', 'msg' => T_('%d menu entries') ),
			);
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Menu:
		param( 'ment_menu_ID', 'integer' );
		param_check_not_empty( 'ment_menu_ID', T_('Please select menu!') );
		$this->set_from_Request( 'menu_ID' );

		// Parent:
		param( 'ment_parent_ID', 'integer', NULL );
		$this->set_from_Request( 'parent_ID', NULL, true );

		// Order:
		param( 'ment_order', 'integer', NULL );
		$this->set_from_Request( 'order', NULL, true );

		// Text:
		param( 'ment_text', 'string' );
		param_check_not_empty( 'ment_text', T_('Please enter a text for the menu entry.') );
		$this->set_from_Request( 'text' );

		// Type:
		param( 'ment_type', 'string' );
		$this->set_from_Request( 'type' );

		// Collection logo size:
		param( 'ment_coll_logo_size', 'string' );
		$this->set_from_Request( 'coll_logo_size' );

		// Collection ID:
		param( 'ment_coll_ID', 'integer', NULL );
		$this->set_from_Request( 'coll_ID', NULL, true );

		// URL:
		param( 'ment_url', 'url' );
		$this->set_from_Request( 'url' );

		// Visibility:
		param( 'ment_visibility', 'string' );
		$this->set_from_Request( 'visibility' );

		// Highlight:
		param( 'ment_highlight', 'integer', 0 );
		$this->set_from_Request( 'highlight' );


		return ! param_errors_detected();
	}


	/**
	 * Get name of Menu Entry
	 *
	 * @return string Menu Entry
	 */
	function get_name()
	{
		return $this->get( 'text' );
	}


	/**
	 * Add a child
	 *
	 * @param object SiteMenuEntry
	 */
	function add_child_entry( & $SiteMenuEntry )
	{
		if( !isset( $this->children[ $SiteMenuEntry->ID ] ) )
		{	// Add only if it was not added yet:
			$this->children[ $SiteMenuEntry->ID ] = & $SiteMenuEntry;
		}
	}


	/**
	 * Sort chapter childen
	 */
	function sort_children()
	{
		if( $this->children_sorted )
		{ // Category children list is already sorted
			return;
		}

		// Sort children list
		uasort( $this->children, array( 'SiteMenuEntryCache','compare_site_menu_entries' ) );
	}
}

?>