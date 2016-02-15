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
 * Subregion Class
 */
class Subregion extends DataObject
{
	var $ctry_ID = '';
	var $rgn_ID = '';
	var $code = '';
	var $name = '';
	var $enabled = 1;
	var $preferred = 0;

	/**
	 * Constructor
	 *
	 * @param object database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_regional__subregion', 'subrg_', 'subrg_ID' );

		if( $db_row )
		{
			$this->ID            = $db_row->subrg_ID;
			$this->rgn_ID        = $db_row->subrg_rgn_ID;
			$this->code          = $db_row->subrg_code;
			$this->name          = $db_row->subrg_name;
			$this->enabled       = $db_row->subrg_enabled;
			$this->preferred     = $db_row->subrg_preferred;

			// Load Region class
			load_class( 'regional/model/_region.class.php', 'Region' );

			$RegionCache = & get_RegionCache();
			if( ! empty( $this->rgn_ID ) )
			{
				if( ($Region = & $RegionCache->get_by_ID( $db_row->subrg_rgn_ID, false )) !== false )
				{	// Get country ID
					$this->ctry_ID       = $Region->ctry_ID;
				}
			}
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
				array( 'table'=>'T_users', 'fk'=>'user_subrg_ID', 'msg'=>T_('%d related users') ),
				array( 'table'=>'T_regional__city', 'fk'=>'city_subrg_ID', 'msg'=>T_('%d related cities') ),
			);
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Country Id
		param( 'subrg_ctry_ID', 'integer', true );
		param_check_number( 'subrg_ctry_ID', T_('Please select a country'), true );
		$this->set_from_Request( 'ctry_ID', 'subrg_ctry_ID', true );

		// Region Id
		param( 'subrg_rgn_ID', 'integer', true );
		param_check_number( 'subrg_rgn_ID', T_('Please select a region'), true );
		$this->set_from_Request( 'rgn_ID', 'subrg_rgn_ID', true );

		// Name
		$this->set_string_from_param( 'name', true );

		// Code
		param( 'subrg_code', 'string' );
		param_check_regexp( 'subrg_code', '#^[A-Za-z0-9]{1,6}$#', T_('Sub-region code must be from 1 to 6 letters.') );
		$this->set_from_Request( 'code', 'subrg_code' );

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
			case 'enabled':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Get subregion name.
	 *
	 * @return string subregion name
	 */
	function get_name()
	{
		return $this->name;
	}


	/**
	 * Check existence of specified sub-region code in subrg_code unique field.
	 *
	 * @param string Name of unique field  OR array of Names (for UNIQUE index with MULTIPLE fields)
	 * @param mixed specified value        OR array of Values (for UNIQUE index with MULTIPLE fields)
	 * @return int ID if region + sub-region code exist otherwise NULL/false
	 */
	function dbexists( $unique_fields = array( 'subrg_rgn_ID', 'subrg_code' ), $values = NULL )
	{
		if( is_null( $values ) )
		{
			$values = array( $this->rgn_ID, $this->code );
		}

		return parent::dbexists( $unique_fields, $values );
	}
}

?>