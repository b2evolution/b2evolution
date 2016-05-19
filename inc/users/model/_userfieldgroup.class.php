<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * UserfieldGroup Class
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
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_users__fieldgroups', 'ufgp_', 'ufgp_ID' );

		if( $db_row != NULL )
		{
			$this->ID   = $db_row->ufgp_ID;
			$this->name = $db_row->ufgp_name;
			$this->order = $db_row->ufgp_order;
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
		if( param( 'ufgp_order', 'integer' ) !== 0 ) // Allow zero value
		{
			param_check_not_empty( 'ufgp_order', T_('Please enter an order number.') );
		}
		$this->set( 'order', param( 'ufgp_order', 'integer' ) );

		return ! param_errors_detected();
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
}
?>