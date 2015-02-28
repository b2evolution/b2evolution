<?php
/**
 * This file implements the generic ordered class.
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

load_class('generic/model/_genericelement.class.php', 'GenericElement' );

/**
 * User property;
 *
 * Generic Ordered of users with specific permissions.
 *
 * @package evocore
 */
class GenericOrdered extends GenericElement
{
	// Order object
	var $order;


	/**
	 * Constructor
	 *
	 * @param string Name of table in database
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 * @param object DB row
	 */
	function GenericOrdered( $tablename, $prefix = '', $dbIDname = 'ID', $db_row = NULL )
	{
		global $Debuglog;

		// Call parent constructor:
		parent::GenericElement( $tablename, $prefix, $dbIDname, $db_row );

		if( $db_row != NULL )
		{
			$this->order = $db_row->{$prefix.'order'};
		}

		$Debuglog->add( "Created element <strong>$this->name</strong>", 'dataobjects' );
	}


	/**
	 * Set param value
	 *
	 * By default, all values will be considered strings
	 *
	 * {@internal Contact::set(-)}}
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
 			case 'order':
				return $this->set_param( $parname, 'number', $parvalue, $make_null );

			case 'name':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Insert object into DB based on previously recorded changes
	 */
	function dbinsert()
	{
		global $DB;

		$DB->begin();

		if( $max_order = $DB->get_var( 'SELECT MAX('.$this->dbprefix.'order)
																			FROM '.$this->dbtablename ) )
		{	// The new element order must be the lastest
			$max_order++;
		}
		else
		{ // There are no elements in the database yet, so his order is set to 1.
			$max_order = 1;
		}

		// Set Object order:
		$this->set( 'order', $max_order );

		parent::dbinsert();

		$DB->commit();
	}

}

?>