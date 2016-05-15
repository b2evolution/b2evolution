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
 * City Class
 */
class City extends DataObject
{
	var $ctry_ID = '';
	var $rgn_ID = '';
	var $subrg_ID = '';
	var $postcode = '';
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
		parent::__construct( 'T_regional__city', 'city_', 'city_ID' );

		if( $db_row )
		{
			$this->ID            = $db_row->city_ID;
			$this->ctry_ID       = $db_row->city_ctry_ID;
			$this->rgn_ID        = $db_row->city_rgn_ID;
			$this->subrg_ID      = $db_row->city_subrg_ID;
			$this->postcode      = $db_row->city_postcode;
			$this->name          = $db_row->city_name;
			$this->enabled       = $db_row->city_enabled;
			$this->preferred     = $db_row->city_preferred;
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
				array( 'table'=>'T_users', 'fk'=>'user_city_ID', 'msg'=>T_('%d related users') ),
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
		param( 'city_ctry_ID', 'integer', true );
		param_check_number( 'city_ctry_ID', T_('Please select a country'), true );
		$this->set_from_Request( 'ctry_ID', 'city_ctry_ID', true );

		// Region Id
		$this->set_string_from_param( 'rgn_ID' );

		// Subregion Id
		$this->set_string_from_param( 'subrg_ID' );

		// Name
		$this->set_string_from_param( 'name', true );

		// Code
		param( 'city_postcode', 'string' );
		param_check_regexp( 'city_postcode', '#^[A-Za-z0-9]{1,12}$#', T_('City code must be from 1 to 12 letters.') );
		$this->set_from_Request( 'postcode', 'city_postcode' );

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
			case 'postcode':
			case 'name':
			case 'enabled':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Get city name.
	 *
	 * @return string city name
	 */
	function get_name()
	{
		return $this->name;
	}


	/**
	 * Get postcode.
	 *
	 * @return string postcode
	 */
	function get_postcode()
	{
		return $this->postcode;
	}
}

?>