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
 
class Settings
{
	/**
	 * loads settings, checks db_version
	 */
	function Settings()
	{ // constructor
		global $new_db_version, $DB, $tablesettings;
		
		$sql = "SELECT set_name, set_value FROM $tablesettings";
		
		$q = $DB->get_results( $sql );
		
		foreach( $q as $loop_q )
		{
			$this->{$loop_q->set_name}->value = $loop_q->set_value;
			$this->{$loop_q->set_name}->dbstatus = 'uptodate';
			$this->{$loop_q->set_name}->dbescape = false;
		}
		
		if( isset($this->db_version ) )
		{
			if( $new_db_version != $this->db_version->value )
			{
				die( T_('Your b2evolution database does not match the script version.') );
			}
		}
		else
		{ // hope that it will fit
			$this->set( 'db_version', $new_db_version );
			debug_log( 'Note: new db_version set!' );
		}
		
	}
	
	function get( $setting )
	{
		// echo 'get: '.$setting.'<br />';
		
		if( isset($this->$setting) )
		{
			return $this->$setting->value;
		}
		else
		{
			debug_log("Setting '$setting' not defined.");
			return false;
		}
	}
	
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
