<?php
/**
 * Abstract class to handle settings
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 * @author blueyed
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Class to handle the global settings
 *
 * @package evocore
 */
class AbstractSettings
{
	/**
	 * the DB table which stores the settings
	 */
	var $dbtablename;

	/**
	 * array with DB cols key names
	 */
	var $colkeynames = array();

	/**
	 * DB col name for the value
	 */
	var $colvaluename;


	/**
	 * Constructor, loads settings.
	 *
	 * @abstract
	 */
	function AbstractSettings()
	{
		global $DB;

		$result = $DB->get_results( 'SELECT '.implode( ', ', $this->colkeynames ).', '.$this->colvaluename.' FROM '.$this->dbtablename );
		
		switch( count( $this->colkeynames ) )
		{
			case 1:
				foreach( $result as $loop_row )
				{
					$this->store[$loop_row->{$this->colkeynames[0]}]->value = $loop_row->{$this->colvaluename};
					$this->store[$loop_row->{$this->colkeynames[0]}]->dbstatus = 'uptodate';
				}
				break;

			case 2:
				foreach( $result as $loop_row )
				{
					$this->store[$loop_row->{$this->colkeynames[0]}][$loop_row->{$this->colkeynames[1]}]->value = $loop_row->{$this->colvaluename};
					$this->store[$loop_row->{$this->colkeynames[0]}][$loop_row->{$this->colkeynames[1]}]->dbstatus = 'uptodate';
				}
				break;
			case 3:
				foreach( $result as $loop_row )
				{
					$this->store[$loop_row->{$this->colkeynames[0]}][$loop_row->{$this->colkeynames[1]}][$loop_row->{$this->colkeynames[2]}]->value = $loop_row->{$this->colvaluename};
					$this->store[$loop_row->{$this->colkeynames[0]}][$loop_row->{$this->colkeynames[1]}][$loop_row->{$this->colkeynames[2]}]->dbstatus = 'uptodate';
				}
				break;

		}

		#pre_dump( $this->store, 'store' );
	}


	/**
	 * get a setting from the DB settings table
	 *
	 * @abstract
	 * @params string the values for the column keys (depends on $this->colkeynames and must match its count and order)
	 */
	function get()
	{
		global $Debuglog;
		// echo 'get: '.$setting.'<br />';

		$args = func_get_args();
		
		if( count( $args ) != count( $this->colkeynames ) )
		{
			$Debuglog->add( 'Count of arguments for AbstractSettings::get() does not match $colkeyname.' );
			return false;
		}
		
		switch( count( $this->colkeynames ) )
		{
			case 1:
				if( isset($this->store[ $args[0] ]) )
				{
					return $this->store[ $args[0] ]->value;
				}
				break;
			case 2:
				if( isset($this->store[ $args[0] ][ $args[1] ]) )
				{
					return $this->store[ $args[0] ][ $args[1] ]->value;
				}
				break;
			case 3:
				if( isset($this->store[ $args[0] ][ $args[1] ][ $args[2] ]) )
				{
					return $this->store[ $args[0] ][ $args[1] ][ $args[2] ]->value;
				}
				break;
		}

		$Debuglog->add( 'AbstractSetting: queried setting ['.implode( '/', $args ).' not defined.' );
		return false;
	}


	/**
	* temporarily sets a setting ({@link updateDB()}} writes it to DB)
	 *
	 * @abstract
	 * @param array with values of table column names according to $this->colkeynames + $this->colvaluename
	 */
	function set( $pleaseset )
	{
		// pre_dump( $pleaseset, 'set_abstract' );
		
		switch( count($this->colkeynames) )
		{
			case 1:
				$atstore =& $this->store[ $pleaseset[0] ];
				break;
			case 2:
				$atstore =& $this->store[ $pleaseset[0] ][ $pleaseset[1] ];
				break;
			case 3:
				$atstore =& $this->store[ $pleaseset[0] ][ $pleaseset[1] ][ $pleaseset[2] ];
				break;
			default:
				return false;
			
		}
		
		if( isset($atstore->value) )
		{
			if( $atstore->value == $pleaseset[ count($pleaseset)-1 ] )
			{ // already set
				return false;
			}

			if( $atstore->dbstatus == 'uptodate' )
			{
				$atstore->dbstatus = 'update';
			}
			else
			{
				$atstore->dbstatus = 'insert';
			}
		}
		else
		{
			$atstore->dbstatus = 'insert';
		}

		$atstore->value = $pleaseset[ count($pleaseset)-1 ];

		// echo ' to '.$value.' <br />';
		return true;
	}


	/**
	 * commits changed settings to DB
	 * @abstract
	 */
	function updateDB()
	{
		global $DB;

		$query_insert = array();

		#pre_dump( $this->store, 'update' );
		
		switch( count($this->colkeynames) )
		{
			case 1:
				foreach( $this->store as $key => $value )
				{
					if( $value->dbstatus != 'uptodate' )
					{
						// NOTE: we could split this to use UPDATE for dbstatus=='update'. Dunno what's better for performance.
						
						$query_insert[] = "('$key', '".$DB->escape( $value->value )."')";
					}
				}
				break;
			
			case 2:
				foreach( $this->store as $key => $value )
					foreach( $value as $key2 => $value2 )
					{
						if( $value2->dbstatus != 'uptodate' )
						{
							$query_insert[] = "('$key', '$key2', '".$DB->escape( $value2->value )."')";
						}
					}
				break;

			case 3:
				foreach( $this->store as $key => $value )
					foreach( $value as $key2 => $value2 )
						foreach( $value2 as $key3 => $value3 )
						{
							if( $value3->dbstatus != 'uptodate' )
							{
								$query_insert[] = "('$key', '$key2', '$key3', '".$DB->escape( $value3->value )."')";
							}
						}
				break;
		}

		$q = false;
		if( count($query_insert) )
		{
			$query = 'REPLACE INTO '.$this->dbtablename.' ('.implode( ', ', $this->colkeynames ).', '.$this->colvaluename
								.') VALUES '.implode(', ', $query_insert);
			$q = $DB->query( $query );
			
			pre_dump( $query, 'update-query' );
		}
		
		return $q;
	}

}


?>
