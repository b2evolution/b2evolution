<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class('_core/model/dataobjects/_dataobject.class.php');

/**
 * Currency Class
 * 
 */
class Currency extends DataObject
{
	var $code = '';	
	var $shortcut = '';
	var $name = '';

	/**
	 * Constructor
	 *
	 * @param db_row database row
	 */
	function Currency( $db_row = NULL )
	{

		// Call parent constructor:
		parent::DataObject( 'T_currency', 'curr_', 'curr_ID' );

		$this->delete_restrictions = array();	

  		$this->delete_cascades = array();

 		if( $db_row != NULL )
		{
			$this->ID            = $db_row->curr_ID;
			$this->code          = $db_row->curr_code;
			$this->shortcut      = $db_row->curr_shortcut;
			$this->name          = $db_row->curr_name;			
		}		
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Code
		$this->set_string_from_param( 'code', true );

		// Shortcut
		$this->set_string_from_param( 'shortcut', true );
		
		// Name
		$this->set_string_from_param( 'name', true );		

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
			case 'shortcut':
			case 'name':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}
}

?>