<?php
/**
 * This file implements the Item Status class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_items__status', 'pst_', 'pst_ID' );

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
	 * Update item types associated with this item status
	 */
	function update_item_types_from_Request()
	{
		global $DB;

		$allowed_values = array();
		$remove_values = array();

		// Item Types
		$item_type_IDs = param( 'item_type_IDs', 'string', true );
		$item_type_IDs = explode( ',', $item_type_IDs );

		foreach( $item_type_IDs as $loop_type_ID )
		{
			$loop_type_ID = intval( $loop_type_ID );
			$item_type = param( 'type_'.$loop_type_ID, 'integer', 0 );

			if( $item_type )
			{
				$allowed_values[] = "( $loop_type_ID, $this->ID )";
			}
			else
			{
				$remove_values[] = $loop_type_ID;
			}
		}

		if( $allowed_values )
		{
			$DB->query( 'REPLACE INTO T_items__status_type( its_ityp_ID, its_pst_ID )
					VALUES '.implode( ', ', $allowed_values ) );
		}

		if( $remove_values )
		{
			$DB->query( 'DELETE FROM T_items__status_type
					WHERE its_pst_ID = '.$this->ID.'
					AND its_ityp_ID IN ('.implode( ',', $remove_values ).')' );
		}
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