<?php
/**
 * This file implements the TemplateCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package templates
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );
load_class( 'templates/model/_template.class.php', 'Template' );

/**
 * Blog Cache Class
 *
 * @package templates
 */
class TemplateCache extends DataObjectCache
{
	/**
	 * Cache by template code
	 *
	 * @var cache_by_code array
	 */
	var $cache_by_code;
	
	var $loaded_contexts = array();


	/**
	 * Constructor
	 *
	 * @param string Name of the order field or NULL to use name field
	 */
	function __construct()
	{
		parent::__construct( 'Template', false, 'T_templates', 'tpl_', 'tpl_ID', 'tpl_name' );
	}


	/**
	 * Add a template to the cache
	 *
	 * @param object Template
	 * @return boolean true if it was added false otherwise
	 */
	function add( $Template )
	{
		$code = $Template->get( 'code' );
		$this->cache_by_code[$code] = & $Template;

		return parent::add( $Template );
	}


	/**
	 * Get Template by given code
	 *
	 * @param string Code of Template
	 * @param boolean true if function should die on error
	 * @param boolean true if function should die on empty/null
	 * @return object|NULL|boolean Reference on cached Template, NULL - if request with empty code, FALSE - if requested Template does not exist
	 */
	function & get_by_code( $code, $halt_on_error = true, $halt_on_empty = true )
	{
		global $DB, $Debuglog;

		if( empty( $code ) )
		{	// Don't allow request with empty code:
			if( $halt_on_empty )
			{
				debug_die( "Requested $this->objtype from $this->dbtablename without code!" );
			}
			$r = NULL;
			return $r;
		}

		if( isset( $this->cache_by_code[ $code ] ) )
		{	// Get Template from cache by code:
			$Debuglog->add( "Accessing <strong>$this->objtype($code)</strong> from cache by code", 'dataobjects' );
			return $this->cache_by_code[ $code ];
		}

		// Load just the requested Template:
		$Debuglog->add( "Loading <strong>$this->objtype($code)</strong>", 'dataobjects' );
		$SQL = $this->get_SQL_object();
		$SQL->WHERE_and( 'tpl_code = '.$DB->quote( $code ) );

		if( $db_row = $DB->get_row( $SQL->get(), OBJECT, 0, 'DataObjectCache::get_by_code()' ) )
		{
			$resolved_ID = $db_row->{$this->dbIDname};
			$Debuglog->add( 'success; ID = '.$resolved_ID, 'dataobjects' );
			if( ! isset( $this->cache[$resolved_ID] ) )
			{	// Object is not already in cache:
				$Debuglog->add( 'Adding to cache...', 'dataobjects' );
				//$Obj = new $this->objtype( $row ); // COPY !!
				if( ! $this->add( $this->new_obj( $db_row ) ) )
				{	// could not add
					$Debuglog->add( 'Could not add() object to cache!', 'dataobjects' );
				}
			}
			if( ! isset( $this->cache_by_code[ $code ] ) )
			{	// Add object in cache by code:
				$this->cache_by_code[ $code ] = $this->new_obj( $db_row );
			}
		}

		if( empty( $this->cache_by_code[ $code ] ) )
		{	// Object does not exist by requested code:
			$Debuglog->add( 'Could not get DataObject by code.', 'dataobjects' );
			if( $halt_on_error )
			{
				debug_die( "Requested $this->objtype does not exist!" );
			}
			$this->cache_by_code[ $code ] = false;
		}

		return $this->cache_by_code[ $code ];
	}


	/**
	 * Returns option array with cache contents
	 *
	 * Load the cache if necessary
	 *
	 * @param string Callback method name
	 * @param array IDs to ignore.
	 * @return string
	 */
	function get_code_option_array( $method = 'get_name', $ignore_IDs = array() )
	{
		if( ! $this->all_loaded && $this->load_all )
		{ // We have not loaded all items so far, but we're allowed to.
			if ( empty( $ignore_IDs ) )
			{	// just load all items
				$this->load_all();
			}
			else
			{	// only load those items not listed in $ignore_IDs
				$this->load_list( $ignore_IDs, true );
			}
		}

		$r = array();

		foreach( $this->cache as $loop_Obj )
		{
			if( in_array( $loop_Obj->code, $ignore_IDs ) )
			{	// Ignore this ID
				continue;
			}

			$r[$loop_Obj->code] = $loop_Obj->$method();
		}

		return $r;
	}


	/**
	 * Load templates for a given context
	 * 
	 * @param string Comma-separated list of contexts to load
	 */
	function load_by_context( $context )
	{
		global $DB;

		if( empty( $context ) )
		{	// Nothing to load:
			return;
		}

		$context = array_map( 'trim', explode( ',', $context ) );

		$context_already_loaded = array_intersect( array_keys( $this->loaded_contexts ), $context );
		if( $this->all_loaded || ( count( $context_already_loaded ) == count( $context ) ) )
		{	// Already loaded
			return false;
		}

		$context_to_load = array_diff( $context, $this->loaded_contexts );
	
		$SQL = new SQL( 'Get templates with context: '.implode( ', ', $context_to_load ) );
		$SQL->SELECT( '*' );
		$SQL->FROM( 'T_templates' );
		$SQL->WHERE( 'tpl_translates_tpl_ID IS NULL' );
		$SQL->WHERE_and( 'tpl_context IN ('.$DB->quote( $context_to_load ).')' );
		$SQL->ORDER_BY( 'tpl_name, tpl_code' );

		$this->load_by_sql( $SQL );

		foreach( $context_to_load as $loop_context )
		{
			$this->loaded_contexts[$loop_context] = true;
		}

		return true;
	}
}
