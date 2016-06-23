<?php
/**
 * This file implements the SectionCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );

/**
 * Blog Cache Class
 *
 * @package evocore
 */
class SectionCache extends DataObjectCache
{
	/**
	 * Cache available section for current user
	 * @var array
	 */
	var $cache_available = NULL;

	/**
	 * Constructor
	 *
	 * @param string Name of the order field or NULL to use name field
	 */
	function __construct()
	{
		parent::__construct( 'Section', false, 'T_section', 'sec_', 'sec_ID', 'sec_name', 'sec_order' );
	}


	/**
	 * Load to cache the sections which are available only for current user
	 *
	 * @param array Additional section IDs which should be loaded
	 */
	function load_available( $sec_IDs = NULL )
	{
		global $current_User;

		if( $this->cache_available !== NULL )
		{	// Get the available sections from cache array:
			$this->all_loaded = false;
			$this->cache = $this->cache_available;
			return;
		}

		if( is_logged_in() )
		{
			if( $current_User->check_perm( 'section', 'edit' ) )
			{	// Allow to select all sections if Current user can has a permission for this:
				$this->load_all();
			}
			else
			{	// Load only available sections:

				// Clear main cache to get only available for current user:
				$this->clear();

				global $DB;
				$where_sql = '';

				if( ! empty( $sec_IDs ) )
				{	// Load additional sections by IDs:
					$where_sql .= 'sec_ID IN ( '.$DB->quote( $sec_IDs ).' ) OR ';
				}

				// Load default section of current user group:
				$user_Group = & $current_User->get_Group();
				$where_sql .= 'sec_ID = '.$DB->quote( $user_Group->get_setting( 'perm_default_sec_ID' ) ).' OR ';

				// Load all sections where user is owner:
				$where_sql .= 'sec_owner_user_ID = '.$DB->quote( $current_User->ID );

				$this->load_where( $where_sql );
			}
		}
		else
		{
			$this->cache = array();
		}

		// Save the available sections in cache array:
		$this->cache_available = $this->cache;
	}
}