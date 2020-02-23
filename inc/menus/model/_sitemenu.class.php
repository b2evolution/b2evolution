<?php
/**
 * This file implements the Site Menu class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
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
	var $parent_ID;

	/**
	 * @var array Site Menu Entries
	 */
	var $entries = NULL;

	/**
	 * @var integer Child menu count
	 */
	var $count_child_menus = NULL;

	/**
	 * @var array Localized child menus
	 */
	var $localized_menus = NULL;

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
			$this->parent_ID = $db_row->menu_parent_ID;
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
				array( 'table' => 'T_menus__menu', 'fk' => 'menu_parent_ID', 'msg' => T_('%d child menus') ),
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

		// Parent Menu:
		$menu_parent_ID = param( 'menu_parent_ID', 'integer', NULL );
		if( isset( $menu_parent_ID ) && $this->has_child_menus() )
		{	// Display error message if we want make the meta category from category with posts
			global $Messages;
			$Messages->add( sprintf( T_('This menu cannot become a child of another because it has %d children itself.'), $this->count_child_menus ) );
		}
		$this->set_from_Request( 'parent_ID' );

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
	 * Duplicate menu
	 * 
	 * @return boolean True if duplication was successfull, false otherwise
	 */
	function duplicate()
	{
		global $DB;

		$DB->begin();

		$duplicated_menu_ID = $this->ID;
		$this->ID = 0;

		// Fields that should not be duplicated must be included in the array below:
		$skipped_fields = array( 'ID' );

		// Get all fields of the duplicated menu:
		$source_fields_SQL = new SQL( 'Get all fields of the duplicated menu #'.$duplicated_menu_ID );
		$source_fields_SQL->SELECT( '*' );
		$source_fields_SQL->FROM( 'T_menus__menu' );
		$source_fields_SQL->WHERE( 'menu_ID = '.$DB->quote( $duplicated_menu_ID ) );
		$source_fields = $DB->get_row( $source_fields_SQL, ARRAY_A );

		// Use field values of duplicated collection by default:
		foreach( $source_fields as $source_field_name => $source_field_value )
		{
			// Cut prefix "menu_" of each field:
			$source_field_name = substr( $source_field_name, 5 );
			if( in_array( $source_field_name, $skipped_fields ) )
			{ // Do not duplicate skipped fields
				continue;
			}
			if( isset( $this->$source_field_name ) )
			{	// Unset current value in order to assign new below, especially to update this in array $this->dbchanges:
				unset( $this->$source_field_name );
			}
			$this->set( $source_field_name, $source_field_value );
		}

		// Call this firstly to find all possible errors before inserting:
		// Also to set new values from submitted form:
		if( ! $this->load_from_Request() )
		{	// Error on handle new values from form:
			$this->ID = $duplicated_menu_ID;
			$DB->rollback();
			return false;
		}

		// Try insert new collection in DB:
		if( ! $this->dbinsert() )
		{	// Error on insert collection in DB:
			$this->ID = $duplicated_menu_ID;
			$DB->rollback();
			return false;
		}

		// Copy all menu entries linked to the menu:
		$menu_entry_fields = array( 'ment_ID', 'ment_menu_ID', 'ment_parent_ID', 'ment_order', 'ment_text', 'ment_type',
				'ment_coll_logo_size', 'ment_coll_ID', 'ment_item_ID', 'ment_url', 'ment_visibility', 'ment_highlight' );

		$menu_entries_SQL = 'SELECT '.implode( ', ', $menu_entry_fields ).'
							 FROM T_menus__entry
							 WHERE ment_menu_ID = '.$DB->quote( $duplicated_menu_ID ).'
							 ORDER BY ment_parent_ID ASC, ment_order ASC';

		$menu_entries = $DB->get_results( $menu_entries_SQL, ARRAY_A );

		$entries = array();
		foreach( $menu_entries as $menu_entry )
		{
			$loop_SiteMenuEntry = new SiteMenuEntry();
			foreach( $menu_entry_fields as $entry_field )
			{
				if( $entry_field == 'ment_ID')
				{
					continue;
				}
				elseif( $entry_field == 'ment_menu_ID' )
				{
					$loop_SiteMenuEntry->set( 'menu_ID', $this->ID );
				}
				elseif( $entry_field == 'ment_parent_ID' && ! empty( $menu_entry['ment_parent_ID'] ) && isset( $entries[$menu_entry['ment_parent_ID']] ) )
				{
					$loop_SiteMenuEntry->set( 'parent_ID', $entries[$menu_entry['ment_parent_ID']]);
				}
				else
				{
					$property = substr( $entry_field, 5 );
					$loop_SiteMenuEntry->set( $property, $menu_entry[$entry_field] );
				}
			}
			$loop_SiteMenuEntry->dbinsert();
			$entries[$menu_entry['ment_ID']] = $loop_SiteMenuEntry->ID;
		}

		// Duplication is successful, commit all above changes:
		$DB->commit();

		// Commit changes in cache:
		$SiteMenuCache = & get_SiteMenuCache();
		$SiteMenuCache->add( $this );

		return true;
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


	/**
	 * Get max order of menu entries
	 *
	 * @param integer Parent Menu Entry ID
	 * @return integer
	 */
	function get_max_order( $parent_ID = NULL )
	{
		if( empty( $this->ID ) )
		{
			return 0;
		}

		global $DB;
		$SQL = new SQL( 'Get max order of entries in Menu #'.$this->ID.' for parent Menu Entry #'.intval( $parent_ID ) );
		$SQL->SELECT( 'MAX( ment_order )' );
		$SQL->FROM( 'T_menus__entry' );
		$SQL->WHERE( 'ment_menu_ID = '.$DB->quote( $this->ID ) );
		$SQL->WHERE_and( 'ment_parent_ID '.( empty( $parent_ID ) ? 'IS NULL' : '= '.$DB->quote( $parent_ID ) ) );

		return intval( $DB->get_var( $SQL ) );
	}


	/**
	 * Get localized child menus
	 * 
	 * @param string Locale
	 * @return array Array of SiteMenu objects
	 */
	function get_localized_menus( $locale )
	{
		global $DB;

		if( ! isset( $this->localized_menus[$locale] ) )
		{
			$SiteMenuCache = & get_SiteMenuCache();
			$SiteMenuCache->clear( true );
			$where = 'menu_parent_ID = '.$DB->quote( $this->ID ).' AND menu_locale = '.$DB->quote( $locale );
			$this->localized_menus[$locale] = $SiteMenuCache->load_where( $where );
		}

		return $this->localized_menus[$locale];
	}


	/**
	 * Check if this menu has at least one post
	 *
	 * @return boolean
	 */
	function has_child_menus()
	{
		global $DB;

		if( $this->ID == 0 )
		{	// New menu has no child menus:
			return false;
		}

		if( !isset( $this->count_child_menus ) )
		{
			$SQL = new SQL( 'Check if menu has child menus' );
			$SQL->SELECT( 'COUNT( menu_parent_ID )' );
			$SQL->FROM( 'T_menus__menu' );
			$SQL->WHERE( 'menu_parent_ID = '.$DB->quote( $this->ID ) );
			$this->count_child_menus = $DB->get_var( $SQL );
		}

		return ( $this->count_child_menus > 0 );
	}
}

?>
