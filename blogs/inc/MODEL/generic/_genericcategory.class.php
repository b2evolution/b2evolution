<?php
/**
 * This file implements the Generic Category class.
 *
 * @copyright (c)2004-2005 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * @package gsbcore
 *
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../dataobjects/_dataobject.class.php';

/**
 * Generic Category Class
 *
 * @package gsbcore
 */
class GenericCategory extends GenericProperty
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
		parent::GenericProperty( $tablename, $prefix, $dbIDname, $db_row );

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
		global $Request;
	
		parent::load_from_Request();

		if( $Request->param( $this->dbprefix.'parent_ID', 'integer', NULL ) )
		{
			$Request->param_check_number( $this->dbprefix.'parent_ID', T_('Parent ID must be a number') );
			$this->set_from_Request( 'parent_ID' );
		}
		
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
		
		$Form->text_input( $this->dbprefix.'name', $this->name, $edited_name_maxlen, T_('name') );
		
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