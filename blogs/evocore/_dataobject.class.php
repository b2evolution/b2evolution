<?php
/**
 * This file implements the abstract DataObject base class.
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
	var $delete_restrictions = array();
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
	 * {@internal DataObject::dbchange(-)}}
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
	 * Update the DB based on previously recorded changes
	 *
	 * {@internal DataObject::dbupdate(-)}}
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
	 * Insert object into DB based on previously recorded changes
	 *
	 * {@internal DataObject::dbinsert(-)}}
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
			if( empty($this->creator_user_ID) )
			{	// No creator assigned yet, use current user:
				$this->creator_user_ID = $current_User->ID;
			}
			$this->set_param( $this->creator_field, 'number', $this->creator_user_ID );
		}
		if( !empty($this->lasteditor_field) )
		{	// We want to track last editor:
			if( empty($this->lastedit_user_ID) )
			{	// No editor assigned yet, use current user:
				$this->lastedit_user_ID = $current_User->ID;
			}
			$this->set_param( $this->lasteditor_field, 'number', $this->lastedit_user_ID );
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
	 * Inserts or Updates depending on object state
	 */
	function dbsave()
	{
		if( $this->ID == 0 )
		{	// Object not serialized yet, let's insert!
			$this->dbinsert();
		}
		else
		{	// Object already serialized, let's update!
			$this->dbupdate();
		}
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
	 * Check relations before deleting
	 *
	 * @return boolean true if no restriction prevents deletion
	 */
	function check_delete( $restrict_title )
	{
		global $DB, $Messages, $db_aliases;

		foreach( $this->delete_restrictions as $restriction )
		{
			if( !isset( $db_aliases[$restriction['table']] ) )
			{	// We have no declaration for this table, we consider we don't deal with this table in this app:
				continue;
			}

			$count = $DB->get_var( 'SELECT COUNT(*)
																FROM '.$restriction['table'].'
															 WHERE '.$restriction['fk'].' = '.$this->ID );
			if( $count )
			{
				$Messages->add( sprintf( $restriction['msg'], $count ), 'restrict' );
			}
		}

		if( $Messages->count('restrict') )
		{	// There are restrictions:
			$Messages->head = array(
					'container' => $restrict_title,
					'restrict' => T_('The following relations prevent deletion:')
				);
			$Messages->foot =	T_('Please delete related objects before you proceed.');
			return false;	// Can't delete
		}

		return true;	// can delete
	}


	/**
	 * Displays form to confirm deletion of this object
	 */
	function confirm_delete( $confirm_title, $delete_action, $hiddens )
	{
		// No restrictions, ask for confirmation:
		echo '<div class="panelinfo">';
		echo '<h2>'.$confirm_title.'</h2>';
		echo '<h3>'.T_('THIS CANNOT BE UNDONE!').'</h3>';

		$Form = & new Form( '', '', 'get', '' );

		$Form->begin_form( 'inline' );
			$Form->hiddens( $hiddens );
			$Form->hidden( 'action', $delete_action );
			$Form->hidden( 'confirm', 1 );
			$Form->button( array( 'submit', '', T_('I am sure!'), 'DeleteButton' ) );
		$Form->end_form();

		$Form->begin_form( 'inline' );
			$Form->hiddens( $hiddens );
			$Form->button( array( 'submit', '', T_('CANCEL'), 'CancelButton' ) );
		$Form->end_form();

		echo '</div>';
		return true;
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
		global $Debuglog, $object_def;

		// Dereference db name for this param:
		// ATTENTION: the object defs are not yet available for all dataobjects
		if( isset($this->objtype) && isset($object_def[$this->objtype]['db_cols'][$parname]) )
		{
			$dbfield = $object_def[$this->objtype]['db_cols'][$parname];
		}
		else
		{	// definition not available: we assume that the fieldname is the same, with the dbprefix prepended:
			$dbfield = $this->dbprefix.$parname;
		}

		// Set value:
		$this->$parname = ($make_null && empty($parvalue)) ? NULL : $parvalue;
		$Debuglog->add( $this->dbtablename.' object, setting param '.$parname.'/'.$dbfield.' to '.$this->$parname );

		// Remember change for later db update:
		$this->dbchange( $dbfield, $fieldtype, $parname );
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
}


/**
 * {@internal object_history(-)}}
 */
function object_history( $pos_lastedit_user_ID, $pos_datemodified )
{
	global $UserCache;
	if( !empty( $pos_lastedit_user_ID ) )
	{
		$User = & $UserCache->get_by_ID( $pos_lastedit_user_ID );

		$modified = sprintf( T_('Last modified on %s by %s'), mysql2localedate( $pos_datemodified ), $User->dget('preferedname') );

		return '<img src="img/clock.png" width="17" height="17" class="middle" alt="'.$modified.
														'" title="'.$modified.'" /> ';
	}
}


/*
 * $Log$
 * Revision 1.12  2005/02/17 19:36:23  fplanque
 * no message
 *
 * Revision 1.11  2005/01/12 20:22:51  fplanque
 * started file/dataobject linking
 *
 * Revision 1.10  2005/01/03 15:17:52  fplanque
 * no message
 *
 * Revision 1.9  2004/12/21 21:18:38  fplanque
 * Finished handling of assigning posts/items to users
 *
 * Revision 1.8  2004/12/20 19:49:24  fplanque
 * cleanup & factoring
 *
 * Revision 1.7  2004/12/15 20:50:34  fplanque
 * heavy refactoring
 * suppressed $use_cache and $sleep_after_edit
 * code cleanup
 *
 * Revision 1.6  2004/12/14 21:01:06  fplanque
 * minor fixes
 *
 * Revision 1.4  2004/11/22 17:48:20  fplanque
 * skin cosmetics
 *
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
?>