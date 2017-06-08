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
 * Region Class
 */
class Region extends DataObject
{
	var $ctry_ID = '';
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
		parent::__construct( 'T_regional__region', 'rgn_', 'rgn_ID' );

		if( $db_row )
		{
			$this->ID            = $db_row->rgn_ID;
			$this->ctry_ID       = $db_row->rgn_ctry_ID;
			$this->code          = $db_row->rgn_code;
			$this->name          = $db_row->rgn_name;
			$this->enabled       = $db_row->rgn_enabled;
			$this->preferred     = $db_row->rgn_preferred;
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
				array( 'table'=>'T_users', 'fk'=>'user_rgn_ID', 'msg'=>T_('%d related users') ),
				array( 'table'=>'T_regional__subregion', 'fk'=>'subrg_rgn_ID', 'msg'=>T_('%d related sub-regions') ),
				array( 'table'=>'T_regional__city', 'fk'=>'city_rgn_ID', 'msg'=>T_('%d related cities') ),
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
		param( 'rgn_ctry_ID', 'integer', true );
		param_check_number( 'rgn_ctry_ID', T_('Please select a country'), true );
		$this->set_from_Request( 'ctry_ID', 'rgn_ctry_ID', true );

		// Name
		$this->set_string_from_param( 'name', true );

		// Code
		param( 'rgn_code', 'string' );
		param_check_regexp( 'rgn_code', '#^[A-Za-z0-9]{1,6}$#', T_('Region code must be from 1 to 6 letters.') );
		$this->set_from_Request( 'code', 'rgn_code' );

		if( ! param_errors_detected() )
		{	// Check region code for duplicating:
			$existing_rgn_ID = $this->dbexists( array( 'rgn_ctry_ID', 'rgn_code' ), array( $this->get( 'ctry_ID' ), $this->get( 'code' ) ) );
			if( $existing_rgn_ID )
			{	// We have a duplicate region:
				param_error( 'rgn_code',
					sprintf( T_('This region already exists. Do you want to <a %s>edit the existing region</a>?'),
						'href="?ctrl=regions&amp;action=edit&amp;rgn_ID='.$existing_rgn_ID.'"' ) );
			}
		}

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
	 * Get region name.
	 *
	 * @return string region name
	 */
	function get_name()
	{
		return $this->name;
	}
}

?>