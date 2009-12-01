<?php
/**
 * This file implements the abstract DataObject base class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
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

	var $allow_ID_insert = false;

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
	 * @param string DB field type ('string', 'number', 'date', 'dbfield' )
	 * @param mixed Pointer to value of parameter - dh> pointer? So it should be a reference? Would make sense IMHO anyway.. fp> I just wonder why it's not already a reference... :@
	 */
	function dbchange( $dbfieldname, $dbfieldtype, $valuepointer ) // TODO: dh> value by reference? see above..
	{
		// echo '<br />DB change on :'.$dbfieldname;
		$this->dbchanges[$dbfieldname]['type'] = $dbfieldtype;
		$this->dbchanges[$dbfieldname]['value'] = $valuepointer ;
	}


	/**
	 * Update the DB based on previously recorded changes
	 *
	 * @param boolean do we want to auto track the mod date?
	 * @return boolean true on success, false on failure to update, NULL if no update necessary
	 */
	function dbupdate( $auto_track_modification = true )
	{
		global $DB, $localtimenow, $current_User;

		if( $this->ID == 0 ) { debug_die( 'New object cannot be updated!' ); }

		if( count( $this->dbchanges ) == 0 )
		{
			return NULL;	// No changes!
		}

		if( $auto_track_modification )
		{ // We wnat to track modification date and author automatically:
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
		}

		$sql_changes = array();
		foreach( $this->dbchanges as $loop_dbfieldname => $loop_dbchange )
		{
			if( $loop_dbchange['type'] == 'dbfield' )
			{	// Set to dbfield only:
				$sql_changes[] = "`$loop_dbfieldname` = `".$loop_dbchange['value'].'` ';
				continue;
			}
			
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
	 * Note: DataObject does not require a matching *Cache object.
	 * Therefore it will not try to update the Cache.
	 * If something like that was needed, sth like *Cache->add() should be called.
	 * ATTENTION: Any dbinsert should typically be followed by a 303 redirect. Updating the Cache before redirect is generally not needed.
	 *
	 * @return boolean true on success
	 */
	function dbinsert()
	{
		global $DB, $localtimenow, $current_User;

		if( $this->ID != 0 && !$this->allow_ID_insert )
		{
			die( 'Existing object/object with an ID cannot be inserted!' );
		}

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


		if( !( $this->allow_ID_insert && $this->ID ) )
		{// store ID for newly created db record. Do not if allow_ID_insert is true and $this->ID is not 0

			$this->ID = $DB->insert_id;
		}
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
	 *
	 * @return boolean true on success
	 */
	function dbdelete()
	{
		global $DB, $Messages, $db_config;

		if( $this->ID == 0 ) { debug_die( 'Non persistant object cannot be deleted!' ); }

		if( count($this->delete_cascades) )
		{	// The are cascading deletes to be performed

			// Start transaction:
			$DB->begin();

			foreach( $this->delete_cascades as $restriction )
			{
				if( !isset( $db_config['aliases'][$restriction['table']] ) )
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

		return true;
	}


	/**
	 * Check existence of specified value in unique field.
	 *
	 * @param string Name of unique field
	 * @param mixed specified value
	 * @return int ID if value exists otherwise NULL/false
	 */
	function dbexists($unique_field, $value)
	{
		global $DB;

		$sql = "SELECT $this->dbIDname
						  FROM $this->dbtablename
					   WHERE $unique_field = ".$DB->quote($value)."
						   AND $this->dbIDname != $this->ID";

		return $DB->get_var( $sql );
	}


	/**
	 * Check relations for restrictions or cascades.
	 * @todo dh> Add link to affected items, e.g. items when trying to delete an attachment, where it gets used.
	 */
	function check_relations( $what, $ignore = array() )
	{
		global $DB, $Messages;

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

		$block_item_Widget = & new Widget( 'block_item' );

		$block_item_Widget->title = $confirm_title;
		$block_item_Widget->disp_template_replaced( 'block_start' );

		$this->check_relations( 'delete_cascades' );

		if( $Messages->count('restrict') )
		{	// The will be cascading deletes, issue WARNING:
			echo '<h3>'.T_('WARNING: Deleting this object will also delete:').'</h3>';
			$Messages->display( '', '', true, 'restrict', NULL, NULL, NULL );
		}

		echo '<p class="warning">'.$confirm_title.'</p>';
		echo '<p class="warning">'.T_('THIS CANNOT BE UNDONE!').'</p>';

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

		$block_item_Widget->disp_template_replaced( 'block_end' );
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
		global $Debuglog;

		$dbfield = $this->dbprefix.$parname;

		// Set value:
		// fplanque: Note: I am changing the "make NULL" test to differentiate between 0 and NULL .
		// There might be side effects. In this case it would be better to fix them before coming here.
		// i-e: transform 0 to ''
		$new_value = ($make_null && ($parvalue === '')) ? NULL : $parvalue;

		/* Tblue> Problem: All class member variables originating from the
		 *                 DB are strings (unless they were NULL in the DB,
		 *                 then they are set to NULL by the PHP MySQL
		 *                 extension).
		 *                 If we pass an integer or a double to this function,
		 *                 the corresponding member variable gets changed
		 *                 on every call, because its type is 'string' and
		 *                 we compare using the === operator. Using the
		 *                 == operator would be a bad idea, though, because
		 *                 somebody could pass a NULL value to this function.
		 *                 If the member variable then is set to 0, then
		 *                 0 equals NULL and the member variable does not
		 *                 get updated at all!
		 *                 Thus, using the === operator is correct.
		 *       Solution: If $fieldtype is 'number' and the type of the
		 *                 passed value is either integer or double, we
		 *                 convert it to a string (no data loss). The
		 *                 member variable and the passed value can then
		 *                 be correctly compared using the === operator.
		 *  fp> It would be nicer to convert numeric values to ints & floats at load time in class constructor  x=(int)$y->value  or sth.
		 * THIS IS EXPERIMENTAL! Feel free to revert if something does not
		 * work as expected.
		 */
		if( $fieldtype == 'number' && ( is_int( $new_value ) || is_float( $new_value ) ) )
		{
			settype( $new_value, 'string' );
		}

		//$Debuglog->add( $this->dbtablename.' object; $fieldtype = '.$fieldtype.'; type of $this->'.$parname.' = '.gettype( @$this->$parname ).'; type of $new_value = '.gettype( $new_value ), 'dataobjects' );
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
	 * @param string Request parameter name (NULL means to use Dataobject param name with its prefix)
	 * @param boolean true to set to NULL if empty string value
	 * @return boolean true, if value has been set/changed, false if not.
	 */
	function set_from_Request( $parname, $var = NULL, $make_null = false, $cleanup_function = NULL )
	{
		if( empty($var) )
		{
			$var = $this->dbprefix.$parname;
		}

		$value = get_param($var);

		if( !empty($cleanup_function) )
		{	//We want to apply a cleanup function
			$value = $cleanup_function($value);
			set_param($var, $value);
		}

		return $this->set( $parname, $value, $make_null );
	}


	/**
	 * Set a string parameter from a Request form value.
	 *
	 * @param string Dataobject parameter name
	 * @param boolean true to set to NULL if empty string value
	 * @param string name of function used to clean up input
	 * @param string name of fucntion used to validate input (TODO)
	 * @return boolean true, if value is required
	 */
	function set_string_from_param( $parname, $required = false, $cleanup_function = NULL, $validation_function = NULL, $error_message = NULL )
	{
		$var = $this->dbprefix.$parname;

		$value = param( $var, 'string' );

		if( !empty($cleanup_function) )
		{	// We want to apply a cleanup function:
			$GLOBALS[$var] = $value = $cleanup_function( $value );
		}

		if( $required )
		{
			param_check_not_empty( $var );
		}

		if( $validation_function != NULL )
		{
			param_validate( $var, $validation_function, $required, $error_message );
		}

		return $this->set( $parname, $value, ! $required );
	}


	/**
	 * Template function: Displays object ID.
	 */
	function ID()
	{
		echo $this->ID;
	}


	/**
	 * Generate help title text for action
	 *
	 * @param string action code: edit, delete, etc.
	 * @return string translated help string
	 */
	function get_action_title( $action )
	{
		switch( $action )
		{
			case 'edit': return T_('Edit this object...');
			case 'copy': return T_('Duplicate this object...');
			case 'delete': return T_('Delete this object!');
			default:
				return '';
		}
	}


	/**
	 * Generate requested action icon depending on perm
	 */
	function action_icon( $action, $help_texts = array() )
	{
		if( ! $this->check_perm($action, false) )
		{	// permission denied:
			return '';
		}

		return action_icon( $this->get_action_title($action), $action,
	              				regenerate_url( 'action', $this->dbIDname.'='.$this->ID.'&amp;action='.$action ) );
	}


	/**
	 * Generate requested action link depending on perm
	 */
	function action_link( $action, $link_text, $help_texts = array() )
	{
		if( ! $this->check_perm($action, false) )
		{	// permission denied:
			return '';
		}

		return '<a href="'.regenerate_url( 'action', $this->dbIDname.'='.$this->ID.'&amp;action='.$action )
						.'" title="'.$this->get_action_title($action).'">'.$link_text.'</a>';
	}


	/**
	 * Create icon with dataobject history
	 */
	function history_info_icon()
	{
		$history = array();

		$UserCache = & get_UserCache();

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
 * Revision 1.32  2009/12/01 02:04:45  fplanque
 * minor
 *
 * Revision 1.31  2009/11/30 22:57:27  blueyed
 * Add 'dbfield' dbchange type. This allows setting something to a db field. E.g. 'lastupdate=lastupdate'.
 *
 * Revision 1.30  2009/10/12 23:52:46  blueyed
 * todo
 *
 * Revision 1.29  2009/10/04 23:06:30  fplanque
 * doc
 *
 * Revision 1.28  2009/10/04 12:23:40  efy-maxim
 * validate has been renamed to param_validate function
 *
 * Revision 1.27  2009/09/30 15:15:59  efy-maxim
 * NULL check for validation function
 *
 * Revision 1.26  2009/09/29 21:07:24  blueyed
 * todo/question
 *
 * Revision 1.25  2009/09/26 12:00:42  tblue246
 * Minor/coding style
 *
 * Revision 1.24  2009/09/25 07:32:52  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.23  2009/09/24 19:48:30  efy-maxim
 * validators
 *
 * Revision 1.22  2009/09/24 09:36:04  efy-maxim
 * validation_function
 *
 * Revision 1.21  2009/09/23 02:46:40  fplanque
 * doc
 *
 * Revision 1.20  2009/09/22 07:07:24  efy-bogdan
 * user.ctrl.php cleanup
 *
 * Revision 1.19  2009/09/20 20:07:18  blueyed
 *  - DataObject::dbexists quotes always
 *  - phpdoc fixes
 *  - style fixes
 *
 * Revision 1.18  2009/09/20 18:09:41  tblue246
 * revert
 *
 * Revision 1.16  2009/09/20 12:17:15  efy-sergey
 * fixed wrong ID asigning for tables without auto_increment fields
 *
 * Revision 1.15  2009/09/19 20:49:51  fplanque
 * Cleaner way of implementing permissions.
 *
 * Revision 1.14  2009/09/11 18:34:05  fplanque
 * userfields editing module.
 * needs further cleanup but I think it works.
 *
 * Revision 1.13  2009/09/02 23:29:34  fplanque
 * doc
 *
 * Revision 1.12  2009/09/02 22:50:48  efy-maxim
 * Clean error message for currency/goal already exists
 *
 * Revision 1.11  2009/09/02 17:47:23  fplanque
 * doc/minor
 *
 * Revision 1.10  2009/08/30 02:37:14  fplanque
 * support for simple form processing
 *
 * Revision 1.9  2009/07/19 22:14:22  fplanque
 * Clean resolution of the excerpt mod date bullcrap.
 * It took 4 lines of code...
 *
 * Revision 1.8  2009/07/18 16:30:14  tblue246
 * Better solution for preventing an update of the datemodified field when the excerpt gets autogenerated.
 *
 * Revision 1.7  2009/07/18 11:09:26  tblue246
 * Use is_int() and is_float() instead of gettype() -- better performance.
 *
 * Revision 1.6  2009/07/17 23:11:11  tblue246
 * - DataObject::set_param(): Correctly decide whether to update values with $fieldtype == 'number' (see code for detailed explanation).
 * - Item class: Doc about updating excerpt without updating the datemodified field.
 * - ItemLight class: Add missing member variable.
 *
 * Revision 1.5  2009/07/17 17:50:10  tblue246
 * Item class: Prevent update of datemodified field if only the post excerpt gets updated.
 *
 * Revision 1.4  2009/03/08 23:57:40  fplanque
 * 2009
 *
 * Revision 1.3  2008/04/17 11:53:17  fplanque
 * Goal editing
 *
 * Revision 1.2  2008/01/21 09:35:24  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 10:58:56  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.26  2007/05/14 02:43:04  fplanque
 * Started renaming tables. There probably won't be a better time than 2.0.
 *
 * Revision 1.25  2007/05/13 22:02:07  fplanque
 * removed bloated $object_def
 *
 * Revision 1.24  2007/04/26 00:11:09  fplanque
 * (c) 2007
 *
 * Revision 1.23  2007/01/07 23:37:26  fplanque
 * doc cleanup
 *
 * Revision 1.22  2006/12/10 23:20:55  fplanque
 * hum...
 *
 * Revision 1.21  2006/12/10 03:04:31  blueyed
 * todo
 *
 * Revision 1.20  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>
