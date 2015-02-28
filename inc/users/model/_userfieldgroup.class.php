<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 *
 * @version _userfieldgroup.class.php,v 1.5 2009/09/16 18:11:51 fplanque Exp
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Userfield Class
 *
 * @package evocore
 */
class UserfieldGroup extends DataObject
{
	var $name = '';
	var $order = '';

	/**
	 * Constructor
	 *
	 * @param object Database row
	 */
	function UserfieldGroup( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_users__fieldgroups', 'ufgp_', 'ufgp_ID' );

		if( $db_row != NULL )
		{
			$this->ID   = $db_row->ufgp_ID;
			$this->name = $db_row->ufgp_name;
			$this->order = $db_row->ufgp_order;
		}
		else
		{	// Create a new user field group:
		}
	}


	/**
	 * Get delete restriction settings
	 *
	 * @return array
	 */
	static function get_delete_restrictions()
	{
		return array(
				array( 'table'=>'T_users__fielddefs', 'fk'=>'ufdf_ufgp_ID', 'msg'=>T_('%d user fields in this group') ),
			);
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Name
		param_string_not_empty( 'ufgp_name', T_('Please enter a group name.') );
		$this->set_from_Request( 'name' );

		// Order
		param_string_not_empty( 'ufgp_order', T_('Please enter an order number.') );
		$this->set_from_Request( 'order' );

		return ! param_errors_detected();
	}


	/**
	 * Set param value
	 *
	 * By default, all values will be considered strings
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 */
	function set( $parname, $parvalue )
	{
		$this->set_param( $parname, 'string', $parvalue );
	}


	/**
	 * Get user field group name.
	 *
	 * @return string user field group name
	 */
	function get_name()
	{
		return $this->name;
	}


	/**
	 * Check existence of specified user field group ID in ufgp_ID unique field.
	 *
	 * @todo dh> Two returns here!!
	 * @return int ID if user field group exists otherwise NULL/false
	 */
	function dbexists()
	{
		global $DB;

		$sql = "SELECT $this->dbIDname
						  FROM $this->dbtablename
					   WHERE $this->dbIDname = $this->ID";

		return $DB->get_var( $sql );

		return parent::dbexists('ufgp_ID', $this->ID);
	}
}


/*
 * _userfieldgroup.class.php,v
 *
 */
?>