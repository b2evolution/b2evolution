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
 * Menu Class
 *
 * @package evocore
 */
class SiteMenu extends DataObject
{
	var $name;
	var $locale;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_menus__menu', 'menu_', 'menu_ID' );

		if( $db_row != NULL )
		{	// Get menu data from DB:
			$this->ID = $db_row->menu_ID;
			$this->name = $db_row->menu_name;
			$this->locale = $db_row->menu_locale;
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
		// Name:
		param( 'menu_name', 'string' );
		param_check_not_empty( 'menu_name', T_('Please enter a name for the menu.') );
		$this->set_from_Request( 'name' );

		// Locale:
		param( 'menu_locale', 'string' );
		$this->set_from_Request( 'locale' );

		return ! param_errors_detected();
	}


	/**
	 * Get name of Menu Entry
	 *
	 * @return string Menu Entry
	 */
	function get_name()
	{
		return $this->get( 'name' );
	}
}

?>