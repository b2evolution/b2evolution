<?php
/**
 * This file implements the AbstractSettings class designed to handle any kind of settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// DEBUG: (Turn switch on or off to log debug info for specified category)
$GLOBALS['debug_settings'] = false;


/**
 * Class to handle settings in an abstract manner (to get used with either 1, 2 or 3 DB column keys).
 *
 * Arrays and Objects automatically get serialized and unserialized
 * (in {@link AbstractSettings::get()} and {@link AbstractSettings::dbupdate()}).
 *
 * Note: I've evaluated splitting this into single classes for performance reasons, but only
 *       get() is relevant performance-wise and we could now only get rid of the switch() therein,
 *       which is not sufficient to split it into *_base + _X classes. (blueyed, 2006-08)
 *
 * @package evocore
 * @abstract
 * @see UserSettings, GeneralSettings, PluginSettings, CollectionSettings
 */
class AbstractSettings
{
	/**
	 * The DB table which stores the settings.
	 *
	 * @var string
	 * @access protected
	 */
	var $db_table_name;

	/**
	 * Array with DB column key names.
	 *
	 * @var array
	 * @access protected
	 */
	var $col_key_names = array();

	/**
	 * DB column name for the value.
	 *
	 * @var string
	 * @access protected
	 */
	var $col_value_name;


	/**
	 * The number of column keys to cache by. This are the first x keys of
	 * {@link $col_key_names}. 0 means 'load all'.
	 *
	 * @var integer
	 */
	var $cache_by_col_keys;


	/**
	 * The internal cache.
	 *
	 * false, if settings  could not be loaded or NULL if not initialized.
	 *
 	 * @access protected
	 * @var array
	 */
	var $cache = NULL;


	/**
	 * Do we have loaded everything?
	 *
	 * @var boolean
	 */
	var $all_loaded = false;


	/**
	 * Default settings.
	 *
	 * Maps last colkeyname to some default setting that will be used by
	 * {@link get()} if no value was defined (and it is set as a default).
	 *
	 * @var array
	 */
	var $_defaults = array();


	/**
	 * Constructor.
	 * @param string The name of the DB table with the settings stored.
	 * @param array List of names for the DB columns keys that reference a value.
	 * @param string The name of the DB column that holds the value.
	 * @param integer The number of column keys to cache by. This are the first x keys of {@link $col_key_names}. 0 means 'load all'.
	 */
	function __construct( $db_table_name, $col_key_names, $col_value_name, $cache_by_col_keys = 0 )
	{
		$this->db_table_name = $db_table_name;
		$this->col_key_names = $col_key_names;
		$this->col_value_name = $col_value_name;
		$this->cache_by_col_keys = $cache_by_col_keys;


		/**
		 * internal counter for the number of column keys
		 * @var integer
		 */
		$this->count_col_key_names = count( $this->col_key_names );

		if( $this->count_col_key_names > 3 || $this->count_col_key_names < 1 )
		{
			debug_die( 'Settings keycount not supported for class '.get_class() );
		}
	}


	/**
	 * Load all settings, disregarding the derived classes setting of
	 * {@link $cache_by_col_keys} - useful if you know that you want to get
	 * all user settings for example.
	 */
	function load_all()
	{
		return $this->_load();
	}


	/**
	 * Loads the settings. Not meant to be called directly, but gets called when needed.
	 *
	 * @access protected
	 * @param string First column key
	 * @param string Second column key
	 * @param string Third column key
	 * @return boolean always true
	 */
	function _load( $arg1 = NULL, $arg2 = NULL, $arg3 = NULL )
	{
		if( $this->all_loaded )
		{ // already all loaded
			return true;
		}
		global $DB;

		/**
		 * The where clause - gets filled when {@link $cache_by_col_keys} is used.
		 */
		$whereList = array();

		if( $this->cache_by_col_keys && isset($arg1) )
		{ // The number of column keys to cache by is > 0
			$testCache = $this->cache;
			$args = array( $arg1, $arg2, $arg3 );

			for( $i = 0; $i < $this->cache_by_col_keys; $i++ )
			{
				$whereList[] = $this->col_key_names[$i]." = '".$args[$i]."'";

				if( ! is_array( $testCache )
						|| is_null($args[$i])
						|| ! isset( $testCache[$args[$i]] )
						|| ! ($testCache = & $testCache[$args[$i]]) )
				{
					break;
				}
			}

			if( $i == $this->cache_by_col_keys )
			{ // already loaded!
				return true;
			}
		}
		else
		{ // We cache everything at once!
			$this->all_loaded = true;
		}


		$result = $DB->get_results( '
			SELECT '.implode( ', ', $this->col_key_names ).', '.$this->col_value_name.'
			FROM '.$this->db_table_name.(
				isset( $whereList[0] )
				? ' WHERE '.implode( ' AND ', $whereList )
				: '' ), OBJECT, 'Load settings from '.$this->db_table_name );

		switch( $this->count_col_key_names )
		{
			case 1:
				if( ! $result )
				{ // Remember that we've tried it
					$this->cache[ $arg1 ] = NULL;
				}
				else foreach( $result as $loop_row )
				{
					$this->cache[$loop_row->{$this->col_key_names[0]}] = new stdClass();

					$this->cache[$loop_row->{$this->col_key_names[0]}]->value = $loop_row->{$this->col_value_name};
					$this->cache[$loop_row->{$this->col_key_names[0]}]->dbUptodate = true;
					$this->cache[$loop_row->{$this->col_key_names[0]}]->dbRemove = false;
				}
				break;

			case 2:
				if( ! $result )
				{ // Remember that we've tried it
					$this->cache[ $arg1 ][ $arg2 ] = NULL;
				}
				else foreach( $result as $loop_row )
				{
					$this->cache[$loop_row->{$this->col_key_names[0]}][$loop_row->{$this->col_key_names[1]}] = new stdClass();

					$this->cache[$loop_row->{$this->col_key_names[0]}][$loop_row->{$this->col_key_names[1]}]->value = $loop_row->{$this->col_value_name};
					$this->cache[$loop_row->{$this->col_key_names[0]}][$loop_row->{$this->col_key_names[1]}]->dbUptodate = true;
					$this->cache[$loop_row->{$this->col_key_names[0]}][$loop_row->{$this->col_key_names[1]}]->dbRemove = false;
				}
				break;

			case 3:
				if( ! $result )
				{ // Remember that we've tried it
					$this->cache[ $arg1 ][ $arg2 ][ $arg3 ] = NULL;
				}
				else foreach( $result as $loop_row )
				{
					$this->cache[$loop_row->{$this->col_key_names[0]}][$loop_row->{$this->col_key_names[1]}][$loop_row->{$this->col_key_names[2]}] = new stdClass();

					$this->cache[$loop_row->{$this->col_key_names[0]}][$loop_row->{$this->col_key_names[1]}][$loop_row->{$this->col_key_names[2]}]->value = $loop_row->{$this->col_value_name};
					$this->cache[$loop_row->{$this->col_key_names[0]}][$loop_row->{$this->col_key_names[1]}][$loop_row->{$this->col_key_names[2]}]->dbUptodate = true;
					$this->cache[$loop_row->{$this->col_key_names[0]}][$loop_row->{$this->col_key_names[1]}][$loop_row->{$this->col_key_names[2]}]->dbRemove = false;
				}
				break;
		}

		return true;
	}


	/**
	 * Get a setting from the DB settings table.
	 *
	 * @uses get_default()
	 * @param string First column key
	 * @param string Second column key
	 * @param string Third column key
	 * @return string|false|NULL value as string on success; NULL if not found; false in case of error
	 */
	function getx( $col_key1, $col_key2 = NULL, $col_key3 = NULL )
	{
		global $debug;

		if( $debug )
		{
			global $Debuglog, $Timer;
			$this_class = get_class($this);
			$Timer->resume('abstractsettings_'.$this_class.'_get', false );
		}

		switch( $this->count_col_key_names )
		{
			case 1:
				$this->_load( $col_key1 );
				if( !empty($this->cache[ $col_key1 ]->unserialized) )
				{	// The value has been unserialized before:
					$r = $this->cache[ $col_key1 ]->value;
				}
				elseif( isset($this->cache[ $col_key1 ]->value) )
				{	// First attempt to access the value, we need to unserialize it:
					// Try to unserialize setting (once) - this is as fast as checking an array of values that should get unserialized
					if( ($r = @unserialize($this->cache[ $col_key1 ]->value)) !== false )
					{
						$this->cache[ $col_key1 ] = new stdClass();
						$this->cache[ $col_key1 ]->value = $r;
					}
					else
					{
						$r = $this->cache[ $col_key1 ]->value;
					}
					$this->cache[ $col_key1 ]->unserialized = true;
				}
				else
				{	// The value is not in the cache, we use the default:
					$r = $this->get_default( $col_key1 );
					$this->cache[ $col_key1 ] = new stdClass();
					$this->cache[ $col_key1 ]->value = $r; // remember in cache
					$this->cache[ $col_key1 ]->dbUptodate = true;
					$this->cache[ $col_key1 ]->unserialized = true;
					$from_default = true; // for debug
				}
				break;

			case 2:
				$this->_load( $col_key1, $col_key2 );

				if( isset($this->cache[ $col_key1 ][ $col_key2 ]->unserialized) )
				{
					$r = $this->cache[ $col_key1 ][ $col_key2 ]->value;
				}
				elseif( isset($this->cache[ $col_key1 ][ $col_key2 ]->value) )
				{
					// Try to unserialize setting (once) - this is as fast as checking an array of values that should get unserialized
					if( ($r = @unserialize($this->cache[ $col_key1 ][ $col_key2 ]->value)) !== false )
					{
						$this->cache[ $col_key1 ][ $col_key2 ] = new stdClass();
						$this->cache[ $col_key1 ][ $col_key2 ]->value = $r;
					}
					else
					{
						$r = $this->cache[ $col_key1 ][ $col_key2 ]->value;
					}
					$this->cache[ $col_key1 ][ $col_key2 ]->unserialized = true;
				}
				else
				{
					$r = $this->get_default( $col_key2 );
					$this->cache[ $col_key1 ][ $col_key2 ] = new stdClass();
					$this->cache[ $col_key1 ][ $col_key2 ]->value = $r; // remember in cache
					$this->cache[ $col_key1 ][ $col_key2 ]->dbUptodate = true;
					$this->cache[ $col_key1 ][ $col_key2 ]->unserialized = true;
					$from_default = true; // for debug
				}
				break;

			case 3:
				$this->_load( $col_key1, $col_key2, $col_key3 );

				if( isset($this->cache[ $col_key1 ][ $col_key2 ][ $col_key3 ]->unserialized) )
				{
					$r = $this->cache[ $col_key1 ][ $col_key2 ][ $col_key3 ]->value;
				}
				elseif( isset($this->cache[ $col_key1 ][ $col_key2 ][ $col_key3 ]->value) )
				{
					// Try to unserialize setting (once) - this is as fast as checking an array of values that should get unserialized
					if( ($r = @unserialize($this->cache[ $col_key1 ][ $col_key2 ][ $col_key3 ]->value)) !== false )
					{
						$this->cache[ $col_key1 ][ $col_key2 ][ $col_key3 ] = new stdClass();
						$this->cache[ $col_key1 ][ $col_key2 ][ $col_key3 ]->value = $r;
					}
					else
					{
						$r = $this->cache[ $col_key1 ][ $col_key2 ][ $col_key3 ]->value;
					}
					$this->cache[ $col_key1 ][ $col_key2 ][ $col_key3 ]->unserialized = true;
				}
				else
				{
					$r = $this->get_default( $col_key3 );
					$this->cache[ $col_key1 ][ $col_key2 ][ $col_key3 ] = new stdClass();
					$this->cache[ $col_key1 ][ $col_key2 ][ $col_key3 ]->value = $r; // remember in cache
					$this->cache[ $col_key1 ][ $col_key2 ][ $col_key3 ]->dbUptodate = true;
					$this->cache[ $col_key1 ][ $col_key2 ][ $col_key3 ]->unserialized = true;
					$from_default = true; // for debug
				}
				break;
		}

		if( $debug )
		{
			$Debuglog->add( $this_class.'::get( '.$col_key1.'/'.$col_key2.'/'.$col_key3.' ): '
				.( isset($from_default) ? '[DEFAULT]: ' : '' )
				.var_export( $r, true ), 'settings' );
			$Timer->pause('abstractsettings_'.$this_class.'_get', false );
		}

		return $r;
	}


	/**
	 * Get the default for the last key of {@link $col_key_names}
	 *
	 * @param string The last column key
	 * @return NULL|mixed NULL if no default is set, otherwise the value (should be string).
	 */
	function get_default( $last_key )
	{
		if( isset($this->_defaults[ $last_key ]) )
		{
			return $this->_defaults[ $last_key ];
		}

		return NULL;
	}


	/**
	 * Only set the first variable (passed by reference) if we could retrieve a
	 * setting.
	 *
	 * @param mixed variable to set maybe (by reference)
	 * @param string the values for the column keys (depends on $this->col_key_names
	 *               and must match its count and order)
	 * @return boolean true on success (variable was set), false if not
	 */
	function get_cond( & $toset )
	{
		$args = func_get_args();
		array_shift( $args );

		$result = call_user_func_array( array( & $this, 'get' ), $args );

		if( $result !== NULL && $result !== false )
		{ // No error and value retrieved
			$toset = $result;
			return true;
		}
		else
		{
			return false;
		}
	}


	/**
	 * Temporarily sets a setting ({@link dbupdate()} writes it to DB).
	 *
	 * @param string $args,... the values for the {@link $col_key_names column keys}
	 *                         and {@link $col_value_name column value}. Must match order and count!
	 * @return boolean true, if the value has been set, false if it has not changed.
	 */
	function setx()
	{
		global $Debuglog;

		$args = func_get_args();
		$value = array_pop($args);

		call_user_func_array( array(&$this, '_load'), $args );

		$debugMsg = get_class($this).'::set( '.implode(', ', $args ).' ): ';

		switch( $this->count_col_key_names )
		{
			case 1:
				$atCache = & $this->cache[ $args[0] ];
				break;

			case 2:
				$atCache = & $this->cache[ $args[0] ][ $args[1] ];
				break;

			case 3:
				$atCache = & $this->cache[ $args[0] ][ $args[1] ][ $args[2] ];
				break;

			default:
				return false;
		}

		if( ! is_object($atCache) )
		{	// PHP 5.4 fix for "Warning: Creating default object from empty value"
			$atCache = new stdClass();
		}

		$atCache->dbRemove = false;

		if( isset($atCache->value) )
		{
			if( ( is_array( $value ) && serialize(  $value ) === $atCache->value ) ||
			    ( ! is_array( $value ) && $atCache->value === (string)$value ) )
			{ // already set
				$Debuglog->add( $debugMsg.' Already set to the same value.', 'settings' );
				return false;
			}
		}

		$atCache->value = $value;
		$atCache->dbUptodate = false;
		$atCache->unserialized = false; // We haven't tried to unserialize the value yet

		$Debuglog->add( $debugMsg.' SET!', 'settings' );

		return true;
	}


	/**
	 * Set an array of values.
	 *
	 * @param array Array of parameters for {@link set()}
	 */
	function set_array( $array )
	{
		foreach( $array as $lSet )
		{
			call_user_func_array( array( & $this, 'set' ), $lSet );
		}
	}


	/**
	 * Remove a setting.
	 *
	 * @param array List of {@link $col_key_names}
	 * @return boolean
	 */
	function delete( $args )
	{
		$args = func_get_args();

		switch( $this->count_col_key_names )
		{
			case 1:
				$atCache = & $this->cache[ $args[0] ];
				break;

			case 2:
				$atCache = & $this->cache[ $args[0] ][ $args[1] ];
				break;

			case 3:
				$atCache = & $this->cache[ $args[0] ][ $args[1] ][ $args[2] ];
				break;

			default:
				return false;
		}

		if( ! is_object($atCache) )
		{	// PHP 5.4 fix for "Warning: Creating default object from empty value"
			$atCache = new stdClass();
		}

		$atCache->dbRemove = true;
		unset($atCache->unserialized);
		unset($atCache->value);

		return true;
	}


	/**
	 * Delete an array of values.
	 *
	 * @param array Array of parameters for {@link delete()}
	 */
	function delete_array( $array )
	{
		foreach( $array as $lDel )
		{
			call_user_func_array( array( & $this, 'delete' ), array($lDel) );
		}
	}


	/**
	 * Delete values for {@link $_defaults default settings} in DB.
	 *
	 * This will use the default settings on the next {@link get()}
	 * again.
	 *
	 * @return boolean true, if settings have been updated; false otherwise
	 */
	function restore_defaults()
	{
		$this->delete_array( array_keys( $this->_defaults ) );

		return $this->dbupdate();
	}


	/**
	 * Commit changed settings to DB.
	 *
	 * @return boolean true, if settings have been updated; false otherwise
	 */
	function dbupdate()
	{
		if( empty($this->cache) )
		{
			return false;
		}

		global $DB;

		$query_insert = array();
		$query_where_delete = array();

		switch( $this->count_col_key_names )
		{
			case 1:
				foreach( $this->cache as $key => $value )
				{
					if( $value === NULL )
					{ // Remembered as not existing
						continue;
					}
					if( ! empty($value->dbRemove) )
					{
						$query_where_delete[] = "{$this->col_key_names[0]} = '$key'";
						unset( $this->cache[$key] );
					}
					elseif( isset($value->dbUptodate) && !$value->dbUptodate )
					{
						$value = $value->value;
						if( is_array( $value ) || is_object( $value ) )
						{
							$value = serialize($value);
						}
						$query_insert[] = "('$key', '".$DB->escape( $value )."')";
						$this->cache[$key]->dbUptodate = true;
					}
				}
				break;

			case 2:
				foreach( $this->cache as $key => $value )
				{
					foreach( $value as $key2 => $value2 )
					{
						if( $value2 === NULL )
						{ // Remembered as not existing
							continue;
						}
						if( ! empty($value2->dbRemove) )
						{
							$query_where_delete[] = "{$this->col_key_names[0]} = '$key' AND {$this->col_key_names[1]} = '$key2'";
							unset( $this->cache[$key][$key2] );
						}
						elseif( isset($value2->dbUptodate) && !$value2->dbUptodate )
						{
							$value2 = $value2->value;
							if( is_array( $value2 ) || is_object( $value2 ) )
							{
								$value2 = serialize($value2);
							}
							$query_insert[] = "('$key', '$key2', '".$DB->escape( $value2 )."')";
							$this->cache[$key][$key2]->dbUptodate = true;
						}
					}
				}
				break;

			case 3:
				foreach( $this->cache as $key => $value )
				{
					foreach( $value as $key2 => $value2 )
					{
						foreach( $value2 as $key3 => $value3 )
						{
							if( $value3 === NULL )
							{ // Remembered as not existing
								continue;
							}
							if( ! empty($value3->dbRemove) )
							{
								$query_where_delete[] = "{$this->col_key_names[0]} = '$key' AND {$this->col_key_names[1]} = '$key2' AND {$this->col_key_names[2]} = '$key3'";
								unset( $this->cache[$key][$key2][$key3] );
							}
							elseif( isset($value3->dbUptodate) && !$value3->dbUptodate )
							{
								$value3 = $value3->value;
								if( is_array($value3) || is_object($value3) )
								{
									$value3 = serialize($value3);
								}
								$query_insert[] = "('$key', '$key2', '$key3', '".$DB->escape( $value3 )."')";
								$this->cache[$key][$key2][$key3]->dbUptodate = true;
							}
						}
					}
				}
				break;

			default:
				return false;
		}


		$r = false;

		if( ! empty($query_where_delete) )
		{
			$query = 'DELETE FROM '.$this->db_table_name." WHERE\n(".implode( ")\nOR (", $query_where_delete ).')';
			$r = (boolean)$DB->query( $query );
		}


		if( ! empty($query_insert) )
		{
			$query = 'REPLACE INTO '.$this->db_table_name.' ('.implode( ', ', $this->col_key_names ).', '.$this->col_value_name
								.') VALUES '.implode(', ', $query_insert);
			$r = $DB->query( $query ) || $r;
		}

		return $r;
	}


	/**
	 * Reset cache (includes settings to be written to DB).
	 *
	 * This is useful, to rollback settings that have been made, e.g. when a Plugin
	 * decides that his settings should not get updated.
	 */
	function reset()
	{
		$this->cache = NULL;
		$this->all_loaded = false;
	}


	/**
	 * Get a param from Request and save it to Settings, or default to previously saved user setting.
	 *
	 * If the setting was not set before (and there's no default given that gets returned), $default gets used.
	 *
	 * @param string Request param name
	 * @param string setting name. Make sure this is unique!
	 * @param string Force value type to one of:
	 * - integer
	 * - float
	 * - string (strips (HTML-)Tags, trims whitespace)
	 * - array
	 * - object
	 * - null
	 * - html (does nothing)
	 * - '' (does nothing)
	 * - '/^...$/' check regexp pattern match (string)
	 * - boolean (will force type to boolean, but you can't use 'true' as a default since it has special meaning. There is no real reason to pass booleans on a URL though. Passing 0 and 1 as integers seems to be best practice).
	 * Value type will be forced only if resulting value (probably from default then) is !== NULL
	 * @param mixed Default value or TRUE
	 * @param boolean Do we need to memorize this to regenerate the URL for this page?
	 * @param boolean Override if variable already set
	 * @return NULL|mixed NULL, if neither a param was given nor knows about it.
	 */
	function param_Request( $param_name, $set_name, $type = '', $default = '', $memorize = false, $override = false ) // we do not force setting it..
	{
		$value = param( $param_name, $type, NULL, $memorize, $override, false ); // we pass NULL here, to see if it got set at all

		if( $value !== false )
		{ // we got a value
			$this->set( $set_name, $value );
			$this->dbupdate();
		}
		else
		{ // get the value from user settings
			$value = $this->get($set_name);

			if( is_null($value) )
			{ // it's not saved yet and there's not default defined ($_defaults)
				$value = $default;
			}
		}

		set_param( $param_name, $value );
		return get_param($param_name);
	}
}

?>