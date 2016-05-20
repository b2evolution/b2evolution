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
 * Country Class
 */
class Country extends DataObject
{
	var $code = '';
	var $name = '';
	var $curr_ID = '';
	var $enabled = 1;
	var $preferred = 0;
	var $status = '';
	var $block_count = 0;

	/**
	 * Constructor
	 *
	 * @param object database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_regional__country', 'ctry_', 'ctry_ID' );

		if( $db_row )
		{
			$this->ID          = $db_row->ctry_ID;
			$this->code        = $db_row->ctry_code;
			$this->name        = $db_row->ctry_name;
			$this->curr_ID     = $db_row->ctry_curr_ID;
			$this->enabled     = $db_row->ctry_enabled;
			$this->preferred   = $db_row->ctry_preferred;
			$this->status      = $db_row->ctry_status;
			$this->block_count = $db_row->ctry_block_count;
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
				array( 'table'=>'T_users', 'fk'=>'user_ctry_ID', 'msg'=>T_('%d related users') ),
				array( 'table'=>'T_regional__region', 'fk'=>'rgn_ctry_ID', 'msg'=>T_('%d related regions') ),
				array( 'table'=>'T_regional__city', 'fk'=>'city_ctry_ID', 'msg'=>T_('%d related cities') ),
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
		$this->set_string_from_param( 'name', true );

		// Code
		param( 'ctry_code', 'string' );
		param_check_regexp( 'ctry_code', '#^[A-Za-z]{2}$#', T_('Country code must be 2 letters.') );
		$this->set_from_Request( 'code', 'ctry_code' );

		// Currency Id
		param( 'ctry_curr_ID', 'integer' );
		param_check_number( 'ctry_curr_ID', T_('Please select a currency') );
		$this->set_from_Request( 'curr_ID', 'ctry_curr_ID', true );

		return ! param_errors_detected();
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
		switch( $parname )
		{
			case 'code':
				$parvalue = strtolower($parvalue);
			case 'name':
			case 'curr_ID':
			case 'enabled':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Get country name.
	 *
	 * @return string currency code
	 */
	function get_name()
	{
		return $this->name;
	}


	/**
	 * Check existence of specified country code in ctry_code unique field.
	 *
	 * @param string Name of unique field  OR array of Names (for UNIQUE index with MULTIPLE fields)
	 * @param mixed specified value        OR array of Values (for UNIQUE index with MULTIPLE fields)
	 * @return int ID if country code exists otherwise NULL/false
	 */
	function dbexists( $unique_fields = 'ctry_code', $values = NULL )
	{
		if( is_null( $values ) )
		{
			$values = $this->code;
		}

		return parent::dbexists( $unique_fields, $values );
	}
}

?>
