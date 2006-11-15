<?php
/**
 * This file implements the AbstractSettings class designed to handle any kind of settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

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
	 * @var array of strings
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
	 * @access protected
	 * @var array|NULL|false Contains the loaded settings or false, if settings
	 *                       could not be loaded or NULL if not initialized.
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
	function AbstractSettings( $db_table_name, $col_key_names, $col_value_name, $cache_by_col_keys = 0 )
	{
		$this->db_table_name = $db_table_name;
		$this->col_key_names = $col_key_names;
		$this->col_value_name = $col_value_name;
		$this->cache_by_col_keys = $cache_by_col_keys;


		/**
		 * @var integer internal counter for the number of column keys
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
	 * Loads the settings. Not meant to be called directly, but gets called
	 * when needed.
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
		{
			$testCache = $this->cache;
			$args = array( $arg1, $arg2, $arg3 );

			for( $i = 0; $i < $this->cache_by_col_keys; $i++ )
			{
				$whereList[] = $this->col_key_names[$i].' = "'.$args[$i].'"';

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
		{ // we're about to load everything
			$this->all_loaded = true;
		}


		$result = $DB->get_results( '
			SELECT '.implode( ', ', $this->col_key_names ).', '.$this->col_value_name.'
			FROM '.$this->db_table_name.(
				isset( $whereList[0] )
				? ' WHERE '.implode( ' AND ', $whereList )
				: '' ) );

		switch( $this->count_col_key_names )
		{
			case 1:
				if( ! $result )
				{ // Remember that we've tried it
					$this->cache[ $arg1 ] = NULL;
				}
				else foreach( $result as $loop_row )
				{
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
	function get( $col_key1, $col_key2 = NULL, $col_key3 = NULL )
	{
		global $debug;

		if( $debug )
		{
			global $Debuglog, $Timer;
			$this_class = get_class($this);
			$Timer->resume('abstractsettings_'.$this_class.'_get');
		}

		switch( $this->count_col_key_names )
		{
			case 1:
				$this->_load( $col_key1 );

				if( isset($this->cache[ $col_key1 ]->unserialized) )
				{	// The value has been unserialized before:
					$r = $this->cache[ $col_key1 ]->value;
				}
				elseif( isset($this->cache[ $col_key1 ]->value) )
				{	// First attempt to access the value, we need to unserialize it:
					// Try to unserialize setting (once) - this is as fast as checking an array of values that should get unserialized
					if( ($r = @unserialize($this->cache[ $col_key1 ]->value)) !== false )
					{
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
			$Timer->pause('abstractsettings_'.$this_class.'_get');
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
	function set()
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

		$atCache->dbRemove = false;

		if( isset($atCache->value) )
		{
			if( $atCache->value == $value )
			{ // already set
				$Debuglog->add( $debugMsg.' Already set to the same value.', 'settings' );
				return false;
			}
		}

		$atCache->value = $value;
		$atCache->dbUptodate = false;
		$atCache->unserialized = true;

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
			call_user_func_array( array( & $this, 'delete' ), $lDel );
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

}


/*
 * $Log$
 * Revision 1.17  2006/11/15 21:04:46  blueyed
 * - Fixed removing setting after delete() and get() (from defaults) (When getting value from default do not reset $dbRemove property)
 * - Opt: default settings are already unserialized
 *
 * Revision 1.16  2006/11/15 20:18:50  blueyed
 * Fixed AbstractSettings::delete(): unset properties in cache
 *
 * Revision 1.15  2006/11/04 01:35:02  blueyed
 * Fixed unserializing of array()
 *
 * Revision 1.14  2006/09/10 14:15:28  blueyed
 * Fixed tests
 *
 * Revision 1.13  2006/09/06 20:45:34  fplanque
 * ItemList2 fixes
 *
 * Revision 1.12  2006/08/04 23:27:03  blueyed
 * Fixed getting default values.
 *
 * Revision 1.11  2006/08/02 22:05:37  blueyed
 * Optimized performance of (Abstract)Settings, especially get().
 *
 * Revision 1.10  2006/05/19 18:15:05  blueyed
 * Merged from v-1-8 branch
 *
 * Revision 1.9.2.1  2006/05/19 15:06:24  fplanque
 * dirty sync
 *
 * Revision 1.9  2006/05/12 21:53:38  blueyed
 * Fixes, cleanup, translation for plugins
 *
 * Revision 1.8  2006/05/02 22:17:10  blueyed
 * use global DB object instead of property, so it does not get serialized with the object
 *
 * Revision 1.7  2006/04/20 17:30:53  blueyed
 * doc
 *
 * Revision 1.6  2006/04/20 16:31:30  fplanque
 * comment moderation (finished for 1.8)
 *
 * Revision 1.5  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.4  2006/03/12 23:08:59  fplanque
 * doc cleanup
 *
 * Revision 1.3  2006/03/11 15:49:48  blueyed
 * Allow a plugin to not update his settings at all.
 *
 * Revision 1.2  2006/02/24 15:09:31  blueyed
 * Decent support for serialized settings through get() and dbupdate().
 *
 * Revision 1.1  2006/02/23 21:11:58  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.30  2006/01/26 20:27:45  blueyed
 * minor
 *
 * Revision 1.28  2006/01/22 22:41:12  blueyed
 * Added get_unserialized().
 *
 * Revision 1.27  2005/12/30 04:34:04  blueyed
 * Small performance enhancements.
 *
 * Revision 1.26  2005/12/19 17:39:56  fplanque
 * Remember prefered browing tab for each user.
 *
 * Revision 1.25  2005/12/12 19:21:21  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.24  2005/12/08 22:26:31  blueyed
 * added restore_defaults(), use debug_die()
 *
 * Revision 1.23  2005/12/07 18:04:17  blueyed
 * Normalization
 *
 * Revision 1.19  2005/11/01 23:03:22  blueyed
 * Fix notice on rare occasion (a setting was remembered as not existing [set to NULL in cache], not changed/set and dbupdate()) called
 *
 * Revision 1.18  2005/10/28 02:37:37  blueyed
 * Normalized AbstractSettings API
 *
 * Revision 1.17  2005/09/06 17:13:54  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.16  2005/07/12 22:54:14  blueyed
 * Fixed get_cond(): respect NULL and false return value of get()
 *
 * Revision 1.15  2005/06/06 17:59:39  fplanque
 * user dialog enhancements
 *
 * Revision 1.14  2005/05/04 19:40:41  fplanque
 * cleaned up file settings a little bit
 *
 * Revision 1.13  2005/04/28 20:44:19  fplanque
 * normalizing, doc
 *
 * Revision 1.12  2005/02/28 09:06:31  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.11  2005/01/10 20:29:26  blueyed
 * Defaults / Refactored AbstractSettings
 *
 * Revision 1.10  2005/01/06 11:35:00  blueyed
 * Debuglog changed
 *
 * Revision 1.9  2005/01/06 05:20:14  blueyed
 * refactored (constructor), getDefaults()
 *
 * Revision 1.8  2005/01/03 06:23:47  blueyed
 * minor refactoring
 *
 * Revision 1.7  2004/12/30 14:29:42  fplanque
 * comments
 *
 * Revision 1.5  2004/11/08 02:48:26  blueyed
 * doc updated
 *
 * Revision 1.4  2004/11/08 02:23:44  blueyed
 * allow caching by column keys (e.g. user ID)
 *
 * Revision 1.3  2004/10/23 21:32:42  blueyed
 * documentation, return value
 *
 * Revision 1.2  2004/10/16 01:31:22  blueyed
 * documentation changes
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.13  2004/10/11 19:02:04  fplanque
 * Edited code documentation.
 *
 */
?>