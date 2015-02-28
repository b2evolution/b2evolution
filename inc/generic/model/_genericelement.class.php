<?php
/**
 * This file implements the Generic Element class, which manages user groups.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * User Element
 *
 * Generic Element of users with specific permissions.
 *
 * @package evocore
 */
class GenericElement extends DataObject
{
	/**
	 * Name of Generic Element
	 *
	 * @var string
	 * @access protected
	 */
	var $name;


	/**
	 * Constructor
	 *
	 * @param string Name of table in database
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 * @param object DB row
	 */
	function GenericElement( $tablename, $prefix = '', $dbIDname = 'ID', $db_row = NULL )
	{
		global $Debuglog;

		// Call parent constructor:
		parent::DataObject( $tablename, $prefix, $dbIDname );

		if( $db_row != NULL )
		{
			// echo 'Instanciating existing group';
			$this->ID = $db_row->$dbIDname;
			$this->name = $db_row->{$prefix.'name'};
		}

		$Debuglog->add( "Created element <strong>$this->name</strong>", 'dataobjects' );
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		param_string_not_empty( $this->dbprefix.'name', T_('Please enter a name.') );
		$this->set_from_Request( 'name' );

		return ! param_errors_detected();
	}


	/**
	 * TODO
	 *
	 */
	function disp_form()
	{
		global $ctrl, $action, $edited_name_maxlen, $form_below_list;

		// Determine if we are creating or updating...
		$creating = is_create_action( $action );

		$Form = new Form( NULL, 'form' );

		if( !$form_below_list )
		{ // We need to display a link to cancel editing:
			$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );
		}

		$Form->begin_form( 'fform', $creating ?  T_('New element') : T_('Element') );

		$Form->add_crumb( 'element' );
		$Form->hidden( 'action', $creating ? 'create' : 'update' );
		$Form->hidden( 'ctrl', $ctrl );
		$Form->hiddens_by_key( get_memorized( 'action, ctrl' ) );

		$Form->text_input( $this->dbprefix.'name', $this->name, $edited_name_maxlen, T_('name'), '', array( 'required' => true ) );

		if( ! $creating ) $Form->hidden( $this->dbIDname, $this->ID );

		$Form->end_form( array( array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Update') ), 'SaveButton' ) ) );
	}


	/**
	 * Template function: return name of item
	 *
	 * @param string Output format, see {@link format_to_output()}
	 * @return string
	 */
	function get_name( $format = 'htmlbody' )
	{
		return $this->dget( 'name', $format );
	}

}

?>