<?php
/**
 * This file implements the abstract DataObject base class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 *
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

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
	 * Relations that may restrict deletion.
	 */
	var $delete_restrictions = array();

	/**
	 * Relations that will cascade deletion.
	 */
	var $delete_cascades = array();


	/**
	 * Constructor
	 *
	 * @param string Name of table in database
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 * @param string datetime field name
	 * @param string datetime field name
	 * @param string User ID field name
	 * @param string User ID field name
	 */
	function DataObject( $tablename, $prefix = '', $dbIDname = 'ID', $datecreated_field = '', $datemodified_field = '', $creator_field = '', $lasteditor_field = '' )
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
	 * @access protected
	 * @param string Name of parameter
	 * @param string DB field type ('string', 'number', 'date' )
	 * @param mixed Pointer to value of parameter
	 */
	function dbchange( $dbfieldname, $dbfieldtype, $valuepointer )
	{
		//echo '<br />DB change on :'.$dbfieldname;
		$this->dbchanges[$dbfieldname]['type'] = $dbfieldtype;
		$this->dbchanges[$dbfieldname]['value'] = $valuepointer ;
	}


	/**
	 * Update the DB based on previously recorded changes
	 *
	 * @return boolean|NULL true on success, false on failure to update, NULL if no update necessary
	 */
	function dbupdate()
	{
		global $DB, $localtimenow, $current_User;

		if( $this->ID == 0 ) { debug_die( 'New object cannot be updated!' ); }

		if( count( $this->dbchanges ) == 0 )
		{
			return NULL;	// No changes!
		}

		if( !empty($this->datemodified_field) )
		{	// We want to track modification date:
			$this->set_param( $this->datemodified_field, 'date', date('Y-m-d H:i:s',$localtimenow) );
		}
		if( !empty($this->lasteditor_field) && is_object($current_User) )
		{	// We want to track last editor:
			// TODO: the current_User is not necessarily the last editor. Item::dbupdate() gets called after incrementing the view for example!
			// fplanque: this should be handled by set() deciding wether the setting changes the last editor or not
			$this->set_param( $this->lasteditor_field, 'number', $current_User->ID );
		}


		$sql_changes = array();
		foreach( $this->dbchanges as $loop_dbfieldname => $loop_dbchange )
		{
			// Get changed value (we use eval() to allow constructs like $loop_dbchange['value'] = 'Group->get(\'ID\')'):
			eval( '$loop_value = $this->'.$loop_dbchange['value'].';' );
			// Prepare matching statement:
			if( is_null($loop_value) )
			{
				$sql_changes[] = $loop_dbfieldname.' = NULL ';
			}
			else
			{
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
		}

		// Prepare full statement:
		$sql = "UPDATE $this->dbtablename SET ". implode( ', ', $sql_changes ). "
						 WHERE $this->dbIDname = $this->ID";
		//echo $sql;

		if( ! $DB->query( $sql, 'DataObject::dbupdate()' ) )
		{
			return false;
		}

		// Reset changes in object:
		$this->dbchanges = array();

		return true;
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true on success
	 */
	function dbinsert()
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
				$this->set_param( $this->creator_field, 'number', $current_User->ID );
			}
		}
		if( !empty($this->lasteditor_field) )
		{	// We want to track last editor:
			if( empty($this->lastedit_user_ID) )
			{	// No editor assigned yet, use current user:
				$this->set_param( $this->lasteditor_field, 'number', $current_User->ID );
			}
		}

		$sql_fields = array();
		$sql_values = array();
		foreach( $this->dbchanges as $loop_dbfieldname => $loop_dbchange )
		{
			// Get changed value (we use eval() to allow constructs like $loop_dbchange['value'] = 'Group->get(\'ID\')'):
			eval( '$loop_value = $this->'. $loop_dbchange['value'].';' );
			// Prepare matching statement:
			$sql_fields[] = $loop_dbfieldname;
			if( is_null($loop_value) )
			{
				$sql_values[] = 'NULL';
			}
			else
			{
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
		}

		// Prepare full statement:
		$sql = "INSERT INTO {$this->dbtablename} (". implode( ', ', $sql_fields ). ") VALUES (". implode( ', ', $sql_values ). ")";
		// echo $sql;

		if( ! $DB->query( $sql, 'DataObject::dbinsert()' ) )
		{
			return false;
		}

		// store ID for newly created db record
		$this->ID = $DB->insert_id;

		// Reset changes in object:
		$this->dbchanges = array();

		return true;
	}


	/**
	 * Inserts or Updates depending on object state.
	 *
	 * @uses dbinsert()
	 * @uses dbupdate()
	 * @return boolean true on success, false on failure
	 */
	function dbsave()
	{
		if( $this->ID == 0 )
		{	// Object not serialized yet, let's insert!
			// echo 'INSERT';
			return $this->dbinsert();
		}
		else
		{	// Object already serialized, let's update!
			// echo 'UPDATE';
			return $this->dbupdate();
		}
	}


	/**
	 * Delete object from DB.
	 */
	function dbdelete()
	{
		global $DB, $Messages, $EvoConfig;

		if( $this->ID == 0 ) { debug_die( 'Non persistant object cannot be deleted!' ); }

		if( count($this->delete_cascades) )
		{	// The are cascading deletes to be performed

			// Start transaction:
			$DB->begin();

			foreach( $this->delete_cascades as $restriction )
			{
				if( !isset( $EvoConfig->DB['aliases'][$restriction['table']] ) )
				{	// We have no declaration for this table, we consider we don't deal with this table in this app:
					continue;
				}

				$DB->query( '
					DELETE FROM '.$restriction['table'].'
					WHERE '.$restriction['fk'].' = '.$this->ID,
					'Cascaded delete' );
			}
		}

		// Delete this (main/parent) object:
		$DB->query( "
			DELETE FROM $this->dbtablename
			WHERE $this->dbIDname = $this->ID",
			'Main delete' );

		if( count($this->delete_cascades) )
		{	// There were cascading deletes

			// End transaction:
			$DB->commit();
		}

		// Just in case... remember this object has been deleted from DB!
		$this->ID = 0;
	}


	/**
	 * Check relations for restrictions or cascades
	 */
	function check_relations( $what, $ignore = array() )
	{
		global $DB, $Messages, $EvoConfig;

		foreach( $this->$what as $restriction )
		{
			if( !in_array( $restriction['fk'], $ignore ) )
			{
				$count = $DB->get_var(
					'SELECT COUNT(*)
					   FROM '.$restriction['table'].'
					  WHERE '.$restriction['fk'].' = '.$this->ID,
					0, 0, 'restriction/cascade check' );
				if( $count )
				{
					$Messages->add( sprintf( $restriction['msg'], $count ), 'restrict' );
				}
			}
		}
	}


	/**
	 * Check relations for restrictions before deleting
	 *
	 * @param string
	 * @param array list of foreign keys to ignore
	 * @return boolean true if no restriction prevents deletion
	 */
	function check_delete( $restrict_title, $ignore = array() )
	{
		global $Messages;

		// Check restrictions:
		$this->check_relations( 'delete_restrictions', $ignore );

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
	 *
	 * @param string Title for confirmation
	 * @param string "action" param value to use (hidden field)
	 * @param array Hidden keys (apart from "action")
	 * @param string most of the time we don't need a cancel action since we'll want to return to the default display
	 */
	function confirm_delete( $confirm_title, $delete_action, $hiddens, $cancel_action = NULL )
	{
		global $Messages;

		// No restrictions, ask for confirmation:
		echo '<div class="panelinfo">';
		echo '<h2>'.$confirm_title.'</h2>';

		$this->check_relations( 'delete_cascades' );

		if( $Messages->count('restrict') )
		{	// The will be cascading deletes, issue WARNING:
			echo '<h3>'.T_('WARNING: Deleting this object will also delete:').'</h3>';
			$Messages->display( '', '', true, 'restrict', NULL, NULL, NULL );
		}

		echo '<h3>'.T_('THIS CANNOT BE UNDONE!').'</h3>';

		$Form = & new Form( '', 'form_confirm', 'get', '' );

		$Form->begin_form( 'inline' );
			$Form->hiddens_by_key( $hiddens );
			$Form->hidden( 'action', $delete_action );
			$Form->hidden( 'confirm', 1 );
			$Form->button( array( 'submit', '', T_('I am sure!'), 'DeleteButton' ) );
		$Form->end_form();

		$Form = & new Form( '', 'form_cancel', 'get', '' );

		$Form->begin_form( 'inline' );
			$Form->hiddens_by_key( $hiddens );
			if( !empty( $cancel_action ) )
			{
				$Form->hidden( 'action', $cancel_action );
			}
			$Form->button( array( 'submit', '', T_('CANCEL'), 'CancelButton' ) );
		$Form->end_form();

		echo '</div>';
		return true;
	}


	/**
	 * Get a member param by its name
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
	 * @return boolean true, if a value has been set; false if it has not changed
	 */
	function set( $parname, $parvalue, $make_null = false )
	{
		return $this->set_param( $parname, 'string', $parvalue, $make_null );
	}


	/**
	 * Set param value.
	 *
	 * @param string Name of parameter
	 * @param string DB field type ('string', 'number', 'date' )
	 * @param mixed Value of parameter
	 * @param boolean true to set to NULL if empty string value
	 * @return boolean true, if value has been set/changed, false if not.
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
		// fplanque: Note: I am changing the "make NULL" test to differentiate between 0 and NULL .
		// There might be side effects. In this case it would be better to fix them before coming here.
		// i-e: transform 0 to ''
		$new_value = ($make_null && ($parvalue === '')) ? NULL : $parvalue;

/* >old
		if( !isset($this->$parname) )
		{	// This property has never been set before, set it to NULL now in order for tests to work:
			$this->$parname = NULL;
		}


		/* blueyed>
		TODO: there's a bug here: you cannot use set_param('foo', 'number', 0), if the $parname member
		      has not been set before or is null!!
		      What about just:
		      ( isset($this->$parname) && $this->$parname === $new_value )
		      This would also eliminate the isset() check from above.
		      IIRC you've once said here that '===' would be too expensive and I would misuse the DataObjects,
		      but IMHO what we have now is not much faster and buggy anyway..
			fp> okay let's give it a try...
		if( (!is_null($new_value) && $this->$parname == $new_value)
			|| (is_null($this->$parname) && is_null($new_value)) )
<old */
		if( (isset($this->$parname) && $this->$parname === $new_value)
			|| ( ! isset($this->$parname) && ! isset($new_value) ) )
		{	// Value has not changed (we need 2 tests, for NULL and for NOT NULL value pairs)
			$Debuglog->add( $this->dbtablename.' object, already set to same value: '.$parname.'/'.$dbfield.' = '.var_export( @$this->$parname, true ), 'dataobjects' );
			// echo '<br />'.$this->dbtablename.' object, already set to same value: '.$parname.'/'.$dbfield.' = '.$this->$parname;

			return false;
		}
		else
		{
			// Set the value in the object:
			// echo '<br/>'.$this->dbtablename.' object, setting param '.$parname.'/'.$dbfield.' to '.$new_value.(is_null($new_value)?' NULL':'').' (was:'.$this->$parname.(is_null($this->$parname)?' NULL':'').')';
			$this->$parname = $new_value;
			$Debuglog->add( $this->dbtablename.' object, setting param '.$parname.'/'.$dbfield.' to '.$this->$parname, 'dataobjects' );

			// Remember change for later db update:
			$this->dbchange( $dbfield, $fieldtype, $parname );

			return true;
		}
	}


	/**
	 * Set a parameter from a Request form value.
	 *
	 * @param string Dataobject parameter name
	 * @param string|NULL Request parameter name (NULL means to use Dataobject param name with its prefix)
	 * @param boolean true to set to NULL if empty string value
	 * @return boolean true, if value has been set/changed, false if not.
	 */
	function set_from_Request( $parname, $var = NULL, $make_null = false )
	{
		global $Request;

		if( empty($var) )
		{
			$var = $this->dbprefix.$parname;
		}

		return $this->set( $parname, $Request->get($var), $make_null );
	}


	/**
	 * Template function: Displays object ID.
	 */
	function ID()
	{
		echo $this->ID;
	}

	/**
	 * Create icon with dataobject history
	 */
	function history_info_icon()
	{
		global $UserCache;

		$history = array();

		// HANDLE CREATOR STUFF
		if( !empty($this->creator_field) && !empty($this->{$this->creator_field}) )
		{	// We have a creator:
			$creator_User = & $UserCache->get_by_ID( $this->{$this->creator_field} );

			if( !empty($this->datecreated_field) && !empty($this->{$this->datecreated_field}) )
			{	// We also have a create date:
				$history[0] = sprintf( T_('Created on %s by %s'), mysql2localedate( $this->{$this->datecreated_field} ),
					$creator_User->dget('preferredname') );
			}
			else
			{	// We only have a cretaor:
				$history[0] = sprintf( T_('Created by %s'), $creator_User->dget('preferredname') );
			}
		}
		elseif( !empty($this->datecreated_field) && !empty($this->{$this->datecreated_field}) )
		{	// We only have a create date:
			$history[0] = sprintf( T_('Created on %s'), mysql2localedate( $this->{$this->datecreated_field} ) );
		}

		// HANDLE LAST UPDATE STUFF
		if( !empty($this->lasteditor_field) && !empty($this->{$this->lasteditor_field}) )
		{	// We have a creator:
			$creator_User = & $UserCache->get_by_ID( $this->{$this->lasteditor_field} );

			if( !empty($this->datemodified_field) && !empty($this->{$this->datemodified_field}) )
			{	// We also have a create date:
				$history[1] = sprintf( T_('Last mod on %s by %s'), mysql2localedate( $this->{$this->datemodified_field} ),
					$creator_User->dget('preferredname') );
			}
			else
			{	// We only have a cretaor:
				$history[1] = sprintf( T_('Last mod by %s'), $creator_User->dget('preferredname') );
			}
		}
		elseif( !empty($this->datemodified_field) && !empty($this->{$this->datemodified_field}) )
		{	// We only have a create date:
			$history[1] = sprintf( T_('Last mod on %s'), mysql2localedate( $this->{$this->datemodified_field} ) );
		}

		return get_icon( 'history', $what = 'imgtag', array( 'title'=>implode( ' - ', $history ) ), true );
	}
}



/*
 * $Log$
 * Revision 1.11  2006/04/24 20:31:15  blueyed
 * doc fixes
 *
 * Revision 1.10  2006/04/20 12:15:32  fplanque
 * no message
 *
 * Revision 1.9  2006/04/20 00:07:21  blueyed
 * Fixed E_NOTICE
 *
 * Revision 1.8  2006/04/19 22:04:59  blueyed
 * Fixed check for if value has changed
 *
 * Revision 1.7  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.6  2006/04/18 00:02:47  blueyed
 * doc
 *
 * Revision 1.5  2006/04/14 19:25:32  fplanque
 * evocore merge with work app
 *
 * Revision 1.4  2006/04/12 15:16:54  fplanque
 * partial cleanup
 *
 * Revision 1.3  2006/03/28 22:22:10  blueyed
 * todo for bug
 *
 * Revision 1.2  2006/03/12 23:08:58  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:57  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.41  2006/02/03 21:58:05  fplanque
 * Too many merges, too little time. I can hardly keep up. I'll try to check/debug/fine tune next week...
 *
 * Revision 1.40  2006/02/01 23:32:32  blueyed
 * *** empty log message ***
 *
 * Revision 1.36  2006/01/22 22:44:28  blueyed
 * Added AfterDataObject* hooks.
 *
 * Revision 1.34  2006/01/12 18:22:58  fplanque
 * fix tentative for integer vs '' vs NULL vs 0
 *
 * Revision 1.33  2005/12/30 20:13:39  fplanque
 * UI changes mostly (need to double check sync)
 *
 * Revision 1.32  2005/12/12 19:21:21  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.30  2005/11/28 07:39:43  blueyed
 * doc, normalization
 *
 * Revision 1.28  2005/11/09 03:28:55  blueyed
 * BUG: on dbupdate() it should not set the current_User as last editor!; minor other stuff
 *
 * Revision 1.27  2005/11/04 13:50:57  blueyed
 * Dataobject::set_param() / set(): return true if a value has been set and false if it did not change. It will not get considered for dbchange() then, too.
 *
 * Revision 1.26  2005/10/31 23:20:45  fplanque
 * keeping things straight...
 *
 * Revision 1.25  2005/10/31 02:27:31  blueyed
 * Comments; normalizing
 *
 * Revision 1.24  2005/09/29 15:07:30  fplanque
 * spelling
 *
 * Revision 1.23  2005/09/26 23:09:10  blueyed
 * Use $EvoConfig->DB for $DB parameters.
 *
 * Revision 1.22  2005/09/06 17:13:54  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.21  2005/07/26 18:57:34  fplanque
 * changed handling of empty params. We do need to differentiate between empty input ''=>NULL and 0=>0 in some situations!
 *
 * Revision 1.20  2005/06/13 19:20:54  fplanque
 * fix
 *
 * Revision 1.19  2005/06/10 18:25:43  fplanque
 * refactoring
 *
 * Revision 1.18  2005/05/25 17:13:33  fplanque
 * implemented email notifications on new comments/trackbacks
 *
 * Revision 1.17  2005/05/17 19:26:07  fplanque
 * FM: copy / move debugging
 *
 * Revision 1.16  2005/05/16 15:17:12  fplanque
 * minor
 *
 * Revision 1.15  2005/04/19 18:04:37  fplanque
 * implemented nested transactions for MySQL
 *
 * Revision 1.14  2005/02/28 09:06:32  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.13  2005/02/18 19:16:15  fplanque
 * started relation restriction/cascading handling
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
