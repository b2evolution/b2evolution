<?php
/**
 * This file implements the abstract DataObject base class.
 *
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Data Object Base Class
 *
 * This is typically an abstract class, useful only when derived.
 *
 * @package evocore
 * @version beta
 * @abstract
 */
class DataObject
{
	/**
	 * Unique ID of object in database
	 *
	 * Please use get/set functions to read or write this param
	 *
	 * @var int
	 * @access protected
	 */
	var $ID = 0;  // This will be the ID in the DB

	/**#@+
	 * @access private
	 */
	var $dbtablename;
	var $dbprefix;
	var $dbIDname;
	var $datecreated_field;
	var $datemodified_field;
	var $creator_field;
	var $lasteditor_field;
	var $dbchanges = array();
	/**#@-*/

	/**
	 * Constructor
	 *
	 * {@internal DataObject::DataObject(-) }}
	 *
	 * @param string Name of table in database
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 * @param string datetime field name
	 * @param string datetime field name
	 * @param string User ID field name
	 * @param string User ID field name
	 */
	function DataObject( $tablename, $prefix = '', $dbIDname = 'ID',
												$datecreated_field = '', $datemodified_field = '',
												$creator_field = '', $lasteditor_field = '' )
	{
		$this->dbtablename        = $tablename;
		$this->dbprefix           = $prefix;
		$this->dbIDname           = $dbIDname;
		$this->datecreated_field  = $datecreated_field;
		$this->datemodified_field = $datemodified_field;
		$this->creator_field      = $creator_field;
		$this->lasteditor_field   = $lasteditor_field;
	}

	/**
	 * Records a change that will need to be updated in the db
	 *
	 * {@internal DataObject::dbchange(-) }}
	 *
	 * @access protected
	 * @param string Name of parameter
	 * @param string DB field type ('string', 'number', 'date' )
	 * @param mixed Pointer to value of parameter
	 */
	function dbchange( $dbfieldname, $dbfieldtype, $valuepointer )
	{
		$this->dbchanges[$dbfieldname]['type'] = $dbfieldtype;
		$this->dbchanges[$dbfieldname]['value'] = $valuepointer ;
	}


	/**
	 * DataObject::dbupdate(-)
	 *
	 * Update the DB based on previously recorded changes
	 */
	function dbupdate( )
	{
		global $DB, $localtimenow, $current_User;

		if( $this->ID == 0 ) die( 'New object cannot be updated!' );

		if( count( $this->dbchanges ) == 0 )
			return;	// No changes!

		if( !empty($this->datemodified_field) )
		{	// We want to track modification date:
			$this->set_param( $this->datemodified_field, 'date', date('Y-m-d H:i:s',$localtimenow) );
		}
		if( !empty($this->lasteditor_field) )
		{	// We want to track last editor:
			$this->set_param( $this->lasteditor_field, 'number', $current_User->ID );
		}


		$sql_changes = array();
		foreach( $this->dbchanges as $loop_dbfieldname => $loop_dbchange )
		{
			// Get changed value:
			eval('$loop_value = $this->'. $loop_dbchange['value'].';');
			// Prepare matching statement:
			switch( $loop_dbchange['type'] )
			{
				case 'date':
				case 'string':
					$sql_changes[] = $loop_dbfieldname." = '".$DB->escape( $loop_value )."' ";
					break;

				default:
					$sql_changes[] = $loop_dbfieldname." = ".$DB->null($loop_value).' ';
			}
		}

		// Prepare full statement:
		$sql = "UPDATE $this->dbtablename SET ". implode( ', ', $sql_changes ). "
						 WHERE $this->dbIDname = $this->ID";
		//echo $sql;

		$DB->query($sql);

		// Reset changes in object:
		$this->dbchanges = array();
	}


	/**
	 * DataObject::dbinsert(-)
	 *
	 * Insert object into DB based on previously recorded changes
	 */
	function dbinsert( )
	{
		global $DB, $localtimenow, $current_User;

		if( $this->ID != 0 ) die( 'Existing object cannot be inserted!' );

		if( !empty($this->datecreated_field) )
		{	// We want to track creation date:
			$this->set_param( $this->datecreated_field, 'date', date('Y-m-d H:i:s',$localtimenow) );
		}
		if( !empty($this->datemodified_field) )
		{	// We want to track modification date:
			$this->set_param( $this->datemodified_field, 'date', date('Y-m-d H:i:s',$localtimenow) );
		}
		if( !empty($this->creator_field) )
		{	// We want to track creator:
			$this->set_param( $this->creator_field, 'number', $current_User->ID );
		}
		if( !empty($this->lasteditor_field) )
		{	// We want to track last editor:
			$this->set_param( $this->lasteditor_field, 'number', $current_User->ID );
		}


		$sql_fields = array();
		$sql_values = array();
		foreach( $this->dbchanges as $loop_dbfieldname => $loop_dbchange )
		{
			// Get changed value:
			eval('$loop_value = $this->'. $loop_dbchange['value'].';');
			// Prepare matching statement:
			$sql_fields[] = $loop_dbfieldname;
			switch( $loop_dbchange['type'] )
			{
				case 'date':
				case 'string':
					$sql_values[] = $DB->quote( $loop_value );
					break;

				default:
					$sql_values[] = $DB->null( $loop_value );
			}
		}

		// Prepare full statement:
		$sql = "INSERT INTO {$this->dbtablename} (". implode( ', ', $sql_fields ). ") VALUES (". implode( ', ', $sql_values ). ")";
		// echo $sql;

		$DB->query($sql);

		// store ID for newly created db record
		$this->ID = $DB->insert_id;

		// Reset changes in object:
		$this->dbchanges = array();
	}


	/**
	 * Delete object from DB
	 *
	 * {@internal DataObject::dbdelete(-)}}
	 */
	function dbdelete( )
	{
		global $DB;

		if( $this->ID == 0 ) die( 'Non persistant object cannot be deleted!' );

		$query = "DELETE FROM $this->dbtablename
							WHERE $this->dbIDname = $this->ID";
		$DB->query($query);
	}


	/**
	 * Get a member param by its name
	 *
	 * {@internal DataObject::get(-) }}
	 *
	 * @param mixed Name of parameter
	 * @return mixed Value of parameter
	 */
	function get( $parname )
	{
		return $this->$parname;
	}


	/**
	 * Get a ready-to-display member param by its name
	 *
	 * Same as disp but don't echo
	 *
	 * {@internal DataObject::dget(-) }}
	 *
	 * @param string Name of parameter
	 * @param string Output format, see {@link format_to_output()}
	 */
	function dget( $parname, $format = 'htmlbody' )
	{
		// Note: we call get again because of derived objects specific handlers !
		return format_to_output( $this->get($parname), $format );
	}


	/**
	 * Display a member param by its name
	 *
	 * {@internal DataObject::disp(-) }}
	 *
	 * @param string Name of parameter
	 * @param string Output format, see {@link format_to_output()}
	 */
	function disp( $parname, $format = 'htmlbody' )
	{
		// Note: we call get again because of derived objects specific handlers !
		echo format_to_output( $this->get($parname), $format );
	}

	/**
	 * Set param value
	 *
	 * By default, all values will be considered strings
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 * @param boolean true to set to NULL if empty value
	 */
	function set( $parname, $parvalue, $make_null = false )
	{
		$this->set_param( $parname, 'string', $parvalue, $make_null );
	}

	/**
	 * Set param value
	 *
	 * {@internal DataObject::set_param(-) }}
	 *
	 * @param string Name of parameter
	 * @param string DB field type ('string', 'number', 'date' )
	 * @param mixed Value of parameter
	 * @param boolean true to set to NULL if empty value
	 */
	function set_param( $parname, $fieldtype, $parvalue, $make_null = false )
	{
		global $Debuglog;

		// Set value:
		$this->$parname = ($make_null && empty($parvalue)) ? NULL : $parvalue;
		$Debuglog->add( $this->dbtablename.' object, setting param '.$parname.' to '.$this->$parname );

		// Remember change for later db update:
		$this->dbchange( $this->dbprefix. $parname , $fieldtype, $parname );
	}

	/**
	 * Template function: Displays object ID
	 *
	 * {@internal DataObject::ID(-) }}
	 */
	function ID()
	{
		echo $this->ID;
	}

/*
 * $Log$
 * Revision 1.3  2004/11/15 18:57:05  fplanque
 * cosmetics
 *
 * Revision 1.2  2004/10/21 18:33:39  fplanque
 * NULL handling
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.23  2004/10/11 19:12:51  fplanque
 * Edited code documentation.
 *
 */
}
?>