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
 * Currency Class
 */
class Currency extends DataObject
{
	var $code = '';
	var $shortcut = '';
	var $name = '';
	var $enabled = 1;

	/**
	 * Constructor
	 *
	 * @param object database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_regional__currency', 'curr_', 'curr_ID' );

		if( $db_row )
		{
			$this->ID            = $db_row->curr_ID;
			$this->code          = $db_row->curr_code;
			$this->shortcut      = $db_row->curr_shortcut;
			$this->name          = $db_row->curr_name;
			$this->enabled       = $db_row->curr_enabled;
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
				array( 'table'=>'T_regional__country', 'fk'=>'ctry_curr_ID', 'msg'=>T_('%d related countries') ),
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

		// Shortcut
		$this->set_string_from_param( 'shortcut', true );

		// Code
		param( 'curr_code', 'string' );
		param_check_regexp( 'curr_code', '#^[A-Za-z]{3}$#', T_('Currency code must be 3 letters.') );
		$this->set_from_Request( 'code', 'curr_code', true  );

		if( ! param_errors_detected() )
		{	// Check currency code for duplicating:
			$existing_curr_ID = $this->dbexists( 'curr_code', $this->get( 'code' ) );
			if( $existing_curr_ID )
			{	// We have a duplicate currency:
				param_error( 'curr_code',
					sprintf( T_('This currency already exists. Do you want to <a %s>edit the existing currency</a>?'),
						'href="?ctrl=currencies&amp;action=edit&amp;curr_ID='.$existing_curr_ID.'"' ) );
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
				$parvalue = strtoupper($parvalue);
			case 'shortcut':
			case 'name':
			case 'enabled':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Get currency unique name (code).
	 *
	 * @return string currency code
	 */
	function get_name()
	{
		return $this->code;
	}


	/**
	 * Get link to Countries, where this Currencie is used
	 * Use when try to delete a currencie
	 *
	 * @param array restriction array
	 * @return string link to currency's countries
	 */
	function get_restriction_link( $restriction )
	{
		global $DB, $admin_url;

		if( $restriction['fk'] != 'ctry_curr_ID' )
		{ // currency restriction exists only for countries
			debug_die( 'Restriction does not exists' );
		}

		// link to country object
		$link = '<a href="'.$admin_url.'?ctrl=countries&action=edit&ctry_ID=%d">%s</a>';
		// set sql to get country ID and name
		$objectID_query = 'SELECT ctry_ID, ctry_name'
						.' FROM '.$restriction['table']
						.' WHERE '.$restriction['fk'].' = '.$this->ID;

		$result_link = '';
		$query_result = $DB->get_results( $objectID_query );
		foreach( $query_result as $row )
		{
			$result_link .= '<br/>'.sprintf( $link, $row->ctry_ID, $row->ctry_name );
		}

		$result_link = sprintf( $restriction['msg'].$result_link, count($query_result) );
		return $result_link;
	}
}

?>