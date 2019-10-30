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
	 * @var array Site Menu Entries
	 */
	var $entries = NULL;

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

		// Store auto menu entries in temp var, they will be inserted in SiteMenu::dbinsert():
		$this->insert_menu_entries = param( 'menu_entries', 'array' );

		return ! param_errors_detected();
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true on success
	 */
	function dbinsert()
	{
		global $DB;

		$DB->begin();

		if( $r = parent::dbinsert() )
		{	// If Menu has been inserted successfully:
			if( ! empty( $this->insert_menu_entries ) )
			{
				$entry_sections = array();
				$root_order = 10;
				$prev_parent = NULL;
				foreach( $this->insert_menu_entries as $menu_entry_key => $menu_entry_text )
				{
					$SiteMenuEntry = new SiteMenuEntry();
					$SiteMenuEntry->set( 'menu_ID', $this->ID );
					if( $menu_entry_key == '#contact#' )
					{	// Special "Contact" entry:
						$SiteMenuEntry->set( 'text', T_('Contact') );
						$SiteMenuEntry->set( 'type', 'ownercontact' );
						$SiteMenuEntry->set( 'order', $root_order );
						$root_order += 10;
						$SiteMenuEntry->dbinsert();
					}
					elseif( preg_match( '/^([a-z]+)_(\d+)(_(\d+))?$/', $menu_entry_key, $m ) )
					{	// Section or Collection entry:
						switch( $m[1] )
						{
							case 'sec':
								// Section entry:
								$SiteMenuEntry->set( 'text', $menu_entry_text );
								$SiteMenuEntry->set( 'type', 'text' );
								$SiteMenuEntry->set( 'order', $root_order );
								$root_order += 10;
								if( $SiteMenuEntry->dbinsert() )
								{
									$entry_sections[ $m[2] ] = $SiteMenuEntry->ID;
								}
								break;
							case 'coll':
								// Collection entry:
								$SiteMenuEntry->set( 'text', $menu_entry_text );
								$SiteMenuEntry->set( 'coll_ID', $m[2] );
								$SiteMenuEntry->set( 'type', 'home' );
								if( isset( $m[4], $entry_sections[ $m[4] ] ) )
								{
									if( $prev_parent != $entry_sections[ $m[4] ] )
									{
										$sub_order = 10;
									}
									$SiteMenuEntry->set( 'parent_ID', $entry_sections[ $m[4] ] );
									$prev_parent = $entry_sections[ $m[4] ];
									$SiteMenuEntry->set( 'order', $sub_order );
									$sub_order += 10;
								}
								else
								{
									$SiteMenuEntry->set( 'order', $root_order );
									$root_order += 10;
								}
								$SiteMenuEntry->dbinsert();
								break;
						}
					}
				}
			}
		}

		if( $r )
		{	
			$DB->commit();
		}
		else
		{
			$DB->rollback();
		}

		return $r;
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


	/**
	 * Get menu entries
	 *
	 * @return array Objects of Site Menu Entries
	 */
	function get_entries()
	{
		if( $this->entries !== NULL )
		{	// Use already loaded menu entries:
			return $this->entries;
		}

		$SiteMenuEntryCache = & get_SiteMenuEntryCache();
		$SiteMenuEntryCache->clear();
		$entries_SQL = $SiteMenuEntryCache->get_SQL_object();
		$entries_SQL->WHERE( 'ment_menu_ID = '.$this->ID );
		$entries_SQL->WHERE_and( 'ment_parent_ID IS NULL' );
		$entries_SQL->ORDER_BY( 'ment_order, ment_ID' );
		$SiteMenuEntryCache->load_by_sql( $entries_SQL );

		$this->entries = $SiteMenuEntryCache->cache;

		return $this->entries;
	}
}

?>