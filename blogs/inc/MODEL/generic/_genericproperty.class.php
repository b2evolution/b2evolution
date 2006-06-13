<?php
/**
 * This file implements the generic property class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once $inc_path.'MODEL/generic/_genericelement.class.php';


/**
 * User property;
 *
 * Generic Property of users with specific permissions.
 *
 * @package evocore
 */
class GenericProperty extends GenericElement
{
	// External ID
	var $ext_ID;


	/**
	 * Constructor
	 *
	 * @param string Name of table in database
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 * @param object DB row
	 */
	function GenericProperty( $tablename, $prefix = '', $dbIDname = 'ID', $db_row = NULL )
	{
		global $Debuglog;

		// Call parent constructor:
		parent::GenericElement( $tablename, $prefix, $dbIDname, $db_row );

		if( $db_row != NULL )
		{
			$ext_IDfield = $prefix.'ext_ID';
			$this->ext_ID = $db_row->$ext_IDfield;
		}

		$Debuglog->add( "Created property <strong>$this->name</strong>", 'dataobjects' );
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		global $Request;

		parent::load_from_Request();

		$Request->param( $this->dbprefix.'ext_ID', 'string', '' );
		$this->set_from_Request( 'ext_ID' );

		return ! $Request->validation_errors();
	}


	/**
	 * Set param value
	 *
	 * By default, all values will be considered strings
	 *
	 * {@internal Contact::set(-)}}
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 */
	function set( $parname, $parvalue )
	{
		switch( $parname )
		{
 			case 'ext_ID':
				$this->set_param( $parname, 'string', $parvalue, true );
				break;

			case 'name':
			default:
				$this->set_param( $parname, 'string', $parvalue );
		}
	}


	/**
	 * TODO
	 *
	 */
	function disp_form()
	{
		global $ctrl, $action, $edited_name_maxlen;

		// Determine if we are creating or updating...
		$creating = is_create_action( $action );

		$Form = & new Form( NULL, 'form' );

		$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

		$Form->begin_form( 'fform', $creating ?  T_('New element') : T_('Element') );

		$Form->hidden( 'action', $creating ? 'create' : 'update' );

		$Form->hidden( 'ctrl', $ctrl );

		$Form->hiddens_by_key( get_memorized( 'action, ctrl' ) );

		$Form->text_input( $this->dbprefix.'name', $this->name, $edited_name_maxlen, T_('name'), array( 'required' => true ) );

		$Form->text_input( $this->dbprefix.'ext_ID', $this->ext_ID, 25, T_('External ID') );

		if( ! $creating ) $Form->hidden( $this->dbIDname, $this->ID );

		if( $creating )
		{
			$Form->end_form( array( array( 'submit', 'submit', T_('Record'), 'SaveButton' ),
															array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
		}
		else
		{
			$Form->end_form( array( array( 'submit', 'submit', T_('Update'), 'SaveButton' ),
															array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
		}
	}


	/**
	 * Update the DB based on previously recorded changes
	 */
	function dbupdate( )
	{
		global $DB, $Request;

		$DB->begin();

		if( $this->ext_ID )
		{ // Check if ext_ID is unique:
			$sql = "SELECT $this->dbIDname
							  FROM $this->dbtablename
							 WHERE {$this->dbprefix}ext_ID = ".$DB->quote( $this->ext_ID )."
								 AND $this->dbIDname != $this->ID";


			if( $q = $DB->get_var( $sql ) )
			{
				$Request->param_error( $this->dbprefix.'ext_ID', 'The external ID is already used!' );
				$DB->commit();
				return false;
			}
		}

		$r = parent::dbupdate();

		$DB->commit();

		return $r;
	}


	/**
	 * Insert object into DB based on previously recorded changes
	 */
	function dbinsert()
	{
		global $DB, $Request;

		$DB->begin();

		if( $this->ext_ID )
		{ // Check if ext_ID is unique:
			$sql = "SELECT $this->dbIDname
							  FROM $this->dbtablename
							 WHERE {$this->dbprefix}ext_ID = ".$DB->quote( $this->ext_ID );

			if( $q = $DB->get_var( $sql ) )
			{
				$Request->param_error( $this->dbprefix.'ext_ID', 'The external ID is already used!' );
				$DB->commit();
				return false;
			}
		}

		$r = parent::dbinsert();

		$DB->commit();

		return $r;
	}

}

?>