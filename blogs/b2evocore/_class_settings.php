<?php
/**
 * Class to handle the global settings
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package b2evocore
 * @author blueyed
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

class Settings
{
	/**
	 * Constructor
	 *
	 * loads settings, checks db_version
	 */
	function Settings()
	{ // constructor
		global $new_db_version, $DB, $tablesettings;

		$result = $DB->get_results( "SELECT * FROM $tablesettings" );

		if( $DB->get_col_info('name', 0) == 'set_name' )
		{ // read new format only
			foreach( $result as $loop_row )
			{
				$this->{$loop_row->set_name}->value = $loop_row->set_value;
				$this->{$loop_row->set_name}->dbstatus = 'uptodate';
				$this->{$loop_row->set_name}->dbescape = false;
			}
		}

		if( !isset($this->db_version ) || $new_db_version != $this->db_version->value )
		{	// Database is not up to date:
			$error_message = 'Database schema is not up to date. You have schema version '.$this->db_version->value.', but we would need '.$new_db_version.'.';
			require dirname(__FILE__).'/_conf_error.page.php';	// error & exit
		}
	}


	/**
	 * get a setting from the DB settings table
	 * @param string name of setting
	 */
	function get( $setting )
	{
		global $Debuglog;
		// echo 'get: '.$setting.'<br />';

		if( isset($this->$setting) )
		{
			return $this->$setting->value;
		}
		else
		{
			$Debuglog->add("Setting '$setting' not defined.");
			return false;
		}
	}


	/**
	 * temporarily sets a setting (updateDB(-) writes it to DB)
	 *
	 * @param string name of setting
	 * @param mixed new value
	 * @param boolean should the value be escaped in DB?
	 */
	function set( $setting, $value, $escape = true )
	{
		// echo 'set '.$setting;
		if( isset($this->$setting->value) )
		{
			if( $this->$setting->value == $value )
			{ // already set
				return false;
			}

			if( $this->$setting->dbstatus == 'uptodate' )
			{
				$this->$setting->dbstatus = 'update';
			}
			else
			{
				$this->$setting->dbstatus = 'insert';
			}
		}
		else
		{
			$this->$setting->dbstatus = 'insert';
		}

		$this->$setting->value = $value;
		$this->$setting->dbescape = $escape;

		// echo ' to '.$value.' <br />';
		return true;
	}


	/**
	 * commits changed settings to DB
	 */
	function updateDB()
	{
		global $tablesettings, $DB;

		$queries_update = array();
		$query_insert = array();

		foreach( $this as $key => $setting )
		{
			if( $setting->dbstatus != 'uptodate' )
			{
				// NOTE: we could split this to use UPDATE for dbstatus=='update'. Dunno what's better for performance.
				$query_insert[] = "('$key', '"
					.( $setting->dbescape ? $DB->escape($setting->value) : $setting->value )
					."')";
			}
		}

		$q = false;

		if( count($query_insert) )
		{
			$query = "REPLACE INTO $tablesettings (set_name, set_value) VALUES ".implode(', ', $query_insert);
			$q = $DB->query( $query );
		}

		return $q;
	}

}


?>
