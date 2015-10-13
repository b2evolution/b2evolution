<?php
/**
 * This file implements the Item Status class.
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
 * ItemStatus Class
 *
 * @package evocore
 */
class ItemStatus extends DataObject
{
	var $name;

	/**
	 * Constructor
	 *
	 *
	 * @param table Database row
	 */
	function ItemStatus( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_items__status', 'pst_', 'pst_ID' );

		// Allow inseting specific IDs
		$this->allow_ID_insert = true;

		if( $db_row != NULL )
		{
			$this->ID   = $db_row->pst_ID;
			$this->name = $db_row->pst_name;
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
				array( 'table' => 'T_items__item', 'fk' => 'post_pst_ID', 'msg' => T_('%d related items') ),
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
		param_string_not_empty( 'pst_name', T_('Please enter a name.') );
		$this->set_from_Request( 'name' );

		return ! param_errors_detected();
	}

	/**
	 * Get the name of the Item Status
	 * @return string
	 */
	function get_name()
	{
		return $this->name;
	}
}

?>