<?php
/**
 * This file implements the Generic Category class.
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
 * Generic Category Class
 *
 * @package gsbcore
 */
class GenericCategory extends GenericElement
{
	var $parent_ID;
	// To display parent name in form
	var $parent_name;

	// Category chidrens list
	var $chidren = array();

	/**
	 * Constructor
	 *
	 * @param table Database row
	 */
	function GenericCategory( $tablename, $prefix = '', $dbIDname = 'ID', $db_row = NULL )
	{
		global $Debuglog;

		// Call parent constructor:
		parent::GenericElement( $tablename, $prefix, $dbIDname, $db_row );

		if( $db_row != NULL )
		{
			$parentIDfield = $prefix.'parent_ID';
			$this->parent_ID = $db_row->$parentIDfield;
		}
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_request()
	{


		parent::load_from_Request();

		if( param( $this->dbprefix.'parent_ID', 'integer', NULL ) )
		{
			param_check_number( $this->dbprefix.'parent_ID', T_('Parent ID must be a number') );
			$this->set_from_Request( 'parent_ID' );
		}

		return ! param_errors_detected();
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
 			case 'parent_ID':
				$this->set_param( $parname, 'string', $parvalue, true );
				break;

			case 'name':
			default:
				$this->set_param( $parname, 'string', $parvalue );
		}
	}


	/**
	 *
	 */
	function add_children( & $GenericCategory )
	{
		$this->children[] = & $GenericCategory;
	}


	/**
	 * Enter description here...
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

		if( $action == 'new' )
		{	// Display parent generic category name for the new generic category
			$Form->info( T_('Add to'), $this->parent_name );
			$Form->hidden( $this->dbprefix.'parent_ID', $this->parent_ID );
		}
		elseif ( $creating )
		{
			$Form->info( T_('Add to'), T_('Root') );
		}

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

}
?>