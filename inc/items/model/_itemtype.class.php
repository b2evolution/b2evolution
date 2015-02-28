<?php
/**
 * This file implements the Item Type class.
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
 * ItemType Class
 *
 * @package evocore
 */
class ItemType extends DataObject
{
	var $name;


	/**
	 * Constructor
	 *
	 *
	 * @param table Database row
	 */
	function ItemType( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_items__type', 'ptyp_', 'ptyp_ID' );

		// Allow inseting specific IDs
		$this->allow_ID_insert = true;

		if( $db_row != NULL )
		{
			$this->ID      		 = $db_row->ptyp_ID 		;
			$this->name  			 = $db_row->ptyp_name 	;
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
				array( 'table'=>'T_items__item', 'fk'=>'post_ptyp_ID', 'msg'=>T_('%d related items') ), // "Lignes de visit reports"
			);
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// get new ID
		if( param( 'new_ptyp_ID', 'string', NULL ) !== NULL )
		{
			param_check_number( 'new_ptyp_ID', T_('ID must be a number.'), true );
			$this->set_from_Request( 'ID', 'new_ptyp_ID' );
		}

		// Name
		param_string_not_empty( 'ptyp_name', T_('Please enter a name.') );
		$this->set_from_Request( 'name' );

		return ! param_errors_detected();
	}

	/**
	 * Get the name of the ItemType
	 * @return string
	 */
	function get_name()
	{
		return $this->name;
	}

	/**
	 * Check existence of specified item type ID in ptyp_ID unique field.
	 *
	 * @return int ID if item type exists otherwise NULL/false
	 */
	function dbexists()
	{
		global $DB;

		$sql = "SELECT $this->dbIDname
						  FROM $this->dbtablename
					   WHERE $this->dbIDname = $this->ID";

		return $DB->get_var( $sql );
	}

	/**
	 *  Returns array, which determinate the lower and upper limit of protected ID's
	 *
	 *  @return array
	 */
	function get_reserved_ids()
	{
		return array( 1000, 5000 );
	}
}

?>