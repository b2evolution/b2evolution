<?php
/**
 * This file implements the AbstractSettings class designed to handle all kinds of settings. {{{
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 *
 * @version $Id$ }}}
 *
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Class to handle the global settings
 *
 * @abstract
 */
class AbstractSettings
{
	/**
	 * the DB table which stores the settings
	 * @var string
	 * @access protected
	 */
	var $dbtablename;

	/**
	 * array with DB cols key names
	 * @var array of strings
	 * @access protected
	 */
	var $colkeynames = array();

	/**
	 * DB col name for the value
	 * @var string
	 * @access protected
	 */
	var $colvaluename;


	/**
	 * the internal cache
	 * @access protected
	 */
	var $cache = false;

	/**
	 * Constructor, loads settings.
	 */
	function AbstractSettings()
	{
		global $DB;

		$result = $DB->get_results( 'SELECT '.implode( ', ', $this->colkeynames ).', '.$this->colvaluename
																.' FROM '.$this->dbtablename );

		if( !$result )
		{
			return false;
		}

		switch( count( $this->colkeynames ) )
		{
			case 1:
				foreach( $result as $loop_row )
				{
					$this->cache[$loop_row->{$this->colkeynames[0]}]->value = $loop_row->{$this->colvaluename};
					$this->cache[$loop_row->{$this->colkeynames[0]}]->dbuptodate = true;
				}
				break;

			case 2:
				foreach( $result as $loop_row )
				{
					$this->cache[$loop_row->{$this->colkeynames[0]}][$loop_row->{$this->colkeynames[1]}]->value = $loop_row->{$this->colvaluename};
					$this->cache[$loop_row->{$this->colkeynames[0]}][$loop_row->{$this->colkeynames[1]}]->dbuptodate = true;
				}
				break;

			case 3:
				foreach( $result as $loop_row )
				{
					$this->cache[$loop_row->{$this->colkeynames[0]}][$loop_row->{$this->colkeynames[1]}][$loop_row->{$this->colkeynames[2]}]->value = $loop_row->{$this->colvaluename};
					$this->cache[$loop_row->{$this->colkeynames[0]}][$loop_row->{$this->colkeynames[1]}][$loop_row->{$this->colkeynames[2]}]->uptodate = true;
				}
				break;

			default:
				die( 'Settings keycount not supported' );

		}
	}


	/**
	 * get a setting from the DB settings table
	 *
	 * @params string the values for the column keys (depends on $this->colkeynames
	 *                and must match its count and order)
	 * @return mixed value on success, false if not found or error occurred
	 */
	function get()
	{
		global $Debuglog;

		$args = func_get_args();
		// echo 'get: ['.implode(', ', $args ).']<br />';

		if( !$this->cache )
		{
			return false;
		}

		if( count( $args ) != count( $this->colkeynames ) )
		{
			$Debuglog->add( 'Count of arguments for AbstractSettings::get() does not match $colkeyname.', 'error' );
			return false;
		}

		switch( count( $this->colkeynames ) )
		{
			case 1:
				if( isset($this->cache[ $args[0] ]) )
				{
					return $this->cache[ $args[0] ]->value;
				}
				break;
			case 2:
				if( isset($this->cache[ $args[0] ][ $args[1] ]) )
				{
					return $this->cache[ $args[0] ][ $args[1] ]->value;
				}
				break;
			case 3:
				if( isset($this->cache[ $args[0] ][ $args[1] ][ $args[2] ]) )
				{
					return $this->cache[ $args[0] ][ $args[1] ][ $args[2] ]->value;
				}
				break;
			default:
				return false;
		}

		$Debuglog->add( 'AbstractSetting: info: queried setting ['.implode( ' / ', $args ).'] not defined.' );
		return false;
	}


	/**
	 * Only set the first variable (passed by reference) if we could retrieve a setting
	 *
	 * @param mixed variable to eventually set (by reference)
	 * @params string the values for the column keys (depends on $this->colkeynames
	 *                and must match its count and order)
	 * @return boolean true on success (variable was set), false if not
	 */
	function get_cond( &$toset )
	{
		$args = func_get_args();

		array_shift( $args );

		$result = call_user_func_array( array( &$this, 'get' ), $args );

		if( $result !== false )
		{
			$toset = $result;
			return true;
		}
		else
		{
			return false;
		}
	}


	/**
	 * temporarily sets a setting ({@link updateDB()}} writes it to DB)
	 *
	 * @params string the values for the column keys (depends on $this->colkeynames + $this->colvaluename
	 *                and must match order and count)
	 */
	function set()
	{
		global $Debuglog;

		$args = func_get_args();
		// echo 'get: ['.implode(', ', $args ).']<br />';

		if( count( $args ) != (count( $this->colkeynames ) + 1) )
		{
			$Debuglog->add( 'Count of arguments for AbstractSettings::set() does not match $colkeyname + 1 (colkeyvalue).', 'error' );
			return false;
		}

		switch( count($this->colkeynames) )
		{
			case 1:
				$atcache =& $this->cache[ $args[0] ];
				break;
			case 2:
				$atcache =& $this->cache[ $args[0] ][ $args[1] ];
				break;
			case 3:
				$atcache =& $this->cache[ $args[0] ][ $args[1] ][ $args[2] ];
				break;
			default:
				return false;
		}

		if( isset($atcache->value) )
		{
			if( $atcache->value == $args[ count($args)-1 ] )
			{ // already set
				return false;
			}
		}

		$atcache->value = $args[ count($args)-1 ];
		$atcache->dbuptodate = false;

		// echo ' to '.$args[ count($args)-1 ].' <br />';
		return true;
	}


	/**
	 * commits changed settings to DB
	 */
	function updateDB()
	{
		global $DB;

		$query_insert = array();

		if( !$this->cache )
		{
			return false;
		}

		#pre_dump( $this->cache, 'update' );

		switch( count($this->colkeynames) )
		{
			case 1:
				foreach( $this->cache as $key => $value )
				{
					if( !$value->dbuptodate )
					{
						$query_insert[] = "('$key', '".$DB->escape( $value->value )."')";
					}
				}
				break;

			case 2:
				foreach( $this->cache as $key => $value )
					foreach( $value as $key2 => $value2 )
					{
						if( !$value2->dbuptodate )
						{
							$query_insert[] = "('$key', '$key2', '".$DB->escape( $value2->value )."')";
						}
					}
				break;

			case 3:
				foreach( $this->cache as $key => $value )
					foreach( $value as $key2 => $value2 )
						foreach( $value2 as $key3 => $value3 )
						{
							if( !$value3->dbuptodate )
							{
								$query_insert[] = "('$key', '$key2', '$key3', '".$DB->escape( $value3->value )."')";
							}
						}
				break;

			default:
				return false;
		}

		$q = false;
		if( count($query_insert) )
		{
			$query = 'REPLACE INTO '.$this->dbtablename.' ('.implode( ', ', $this->colkeynames ).', '.$this->colvaluename
								.') VALUES '.implode(', ', $query_insert);
			$q = $DB->query( $query );
		}

		return $q;
	}

}
?>