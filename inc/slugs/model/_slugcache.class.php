<?php
/**
 * This file implements the SlugCache class.
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

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );

load_class( 'slugs/model/_slug.class.php', 'Slug' );

/**
 * Slug Cache Class
 *
 * @package evocore
 */
class SlugCache extends DataObjectCache
{
	/**
	 * Object array by title
	 */
	var $cache_title = array();

	/**
	 * Constructor
	 *
	 * @param string Name of DataObject class we are caching
	 * @param boolean true if it's OK to just load all items!
	 * @param string Name of table in database
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 * @param string Name of the name field (including prefix)
	 * @param string Name of the order field or NULL to use name field
	 * @param string The text that gets used for the "None" option in the objects options list (Default: NT_('None')).
	 *               !!! NOTE !!! Do NOT use T_() for this value, Use only NT_()
	 * @param mixed  The value that gets used for the "None" option in the objects options list.
	 * @param string Additional part for SELECT clause of sql query
	 */
	function __construct( $objtype = 'Slug', $load_all = false, $tablename = 'T_slug', $prefix = 'slug_', $dbIDname = 'slug_ID', $name_field = 'slug_title', $order_by = 'slug_title', $allow_none_text = NULL, $allow_none_value = '', $select = '' )
	{
		parent::__construct( $objtype, $load_all, $tablename, $prefix, $dbIDname, $name_field, $order_by, $allow_none_text, $allow_none_value, $select );
	}

	/**
	 * Add a dataobject to the cache by title
	 *
	 * @param object Object to add in cache
	 * @return boolean TRUE on adding, FALSE on wrong object or if it is already in cache
	 */
	function add( $Obj )
	{
		$r = parent::add( $Obj );

		if( isset( $this->cache[ $Obj->ID ] ) && ! isset( $this->cache_title[ $Obj->title ] ) )
		{	// Also cache object by title:
			$this->cache_title[ $Obj->title ] = $Obj;
			$r = true;
		}

		return $r;
	}


	/**
	 * Get an object from cache by name
	 *
	 * Load the cache if necessary (all at once if allowed).
	 *
	 * @param integer ID of object to load
	 * @param boolean true if function should die on error
	 * @param boolean true if function should die on empty/null
	 * @return object|NULL|boolean Reference on cached object, NULL - if request with empty name, FALSE - if requested object does not exist
	 */
	function & get_by_name( $req_name, $halt_on_error = true, $halt_on_empty = true )
	{
		if( isset( $this->cache_title[ $req_name ] ) )
		{	// Get slug from cache by title:
			return $this->cache_title[ $req_name ];
		}

		$r = parent::get_by_name( $req_name, $halt_on_error, $halt_on_empty );

		return $r;
	}


	/**
	 * Load objects of Item or Chapter by slug names depending on slug type
	 *
	 * @param array Slug titles
	 * @param array Restrict to load object only with types; possible values: 'cat', 'item'
	 */
	function load_objects_by_slugs( $slugs, $object_types = array() )
	{
		global $DB, $Debuglog;

		if( empty( $slugs ) || empty( $object_types ) )
		{	// Nothing to load:
			return;
		}

		$Debuglog->add( 'Loading <strong>'.$this->objtype.' ('.implode( ',', $slugs ).' )</strong> into cache', 'dataobjects' );

		// Load slugs by titles in cache:
		$loaded_slugs = $this->load_where( 'slug_title IN ( '.$DB->quote( $slugs ).' )' );

		// Load objects(Categories/Items) into cache by requested slug titles:
		foreach( $object_types as $object_type )
		{
			switch( $object_type )
			{
				case 'cat':
					$slug_object_cache = & get_ChapterCache();
					$slug_object_ID_field = 'slug_cat_ID';
					break;

				case 'item':
					$slug_object_cache = & get_ItemCache();
					$slug_object_ID_field = 'slug_itm_ID';
					break;

				default:
					debug_die( 'Unhandled slug object type "'.$object_type.'" in '.get_class().'->'.__FUNCTION__.'()' );
					break;
			}

			$SQL = new SQL( 'Load slug objects with type "'.$object_type.'" into cache '.get_class( $slug_object_cache ).' by '.get_class().'->'.__FUNCTION__.'()' );
			$SQL->SELECT( '*' );
			$SQL->FROM( $slug_object_cache->dbtablename );
			$SQL->FROM_add( 'INNER JOIN T_slug ON '.$slug_object_ID_field.' = '.$slug_object_cache->dbIDname );
			$SQL->WHERE( 'slug_type = '.$DB->quote( $object_type ) );
			$SQL->WHERE_and( 'slug_title IN ( '.$DB->quote( $slugs ).' )' );
			$r = $slug_object_cache->load_by_sql( $SQL );

			$Debuglog->add( 'Load "'.$object_type.'" objects into cache by '.get_class().'->'.__FUNCTION__.'()', 'dataobjects' );
		}
	}


	/**
	 * Get Item/Chapter by slug title
	 *
	 * @param string Slug title
	 * @param string|boolean Object type: 'cat', 'item', 'help' or false to don't restrict by type
	 * @param boolean true if function should die on error
	 * @param boolean true if function should die on empty/null
	 * @return object|NULL|boolean Reference on cached object, NULL - if request with empty name, FALSE - if requested object does not exist
	 */
	function & get_object_by_slug( $slug_title, $restrict_object_type = false, $halt_on_error = true, $halt_on_empty = true )
	{
		$Slug = & $this->get_by_name( $slug_title, $halt_on_error, $halt_on_empty );

		if( $restrict_object_type !== false && $Slug && $Slug->get( 'type' ) != $restrict_object_type )
		{	// Restrict by object type if slug exists in DB with requested title but with another object type
			if( $halt_on_error )
			{	// Die on error:
				debug_die( 'Requested '.$this->objtype.'( '.$slug_title.' ) does not exist with type "'.$restrict_object_type.'"!' );
			}
			// Return false because slug is not found for current request:
			$r = false;
			return $r;
		}

		if( $Slug )
		{
			switch( $Slug->get( 'type' ) )
			{
				case 'cat':
					// Get Chapter object:
					$ChapterCache = & get_ChapterCache();
					$Chapter = & $ChapterCache->get_by_ID( $Slug->get( 'cat_ID' ), $halt_on_error, $halt_on_empty );
					return $Chapter;

				case 'item':
					// Get Item object:
					$ItemCache = & get_ItemCache();
					$Item = & $ItemCache->get_by_ID( $Slug->get( 'itm_ID' ), $halt_on_error, $halt_on_empty );
					return $Item;
			}
		}

		$r = false;
		return $r;
	}
}
?>