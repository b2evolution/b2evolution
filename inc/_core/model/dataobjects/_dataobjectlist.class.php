<?php
/**
 * This file implements the abstract DataObjectList base class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class('_core/ui/results/_results.class.php', 'Results' );

/**
 * Data Object List Base Class
 *
 * This is typically an abstract class, useful only when derived.
 * Holds DataObjects in an array and allows walking through...
 *
 * @package evocore
 * @version beta
 * @abstract
 */
class DataObjectList extends Results
{

	/**
	 * The following should probably be obsoleted by Results::Cache
	 */
	var	$dbtablename;
	var $dbprefix;
	var $dbIDname;

	/**
	 * Class name of objects handled in this list
	 */
	var $objType;

	/**
	 * Object array
	 */
	var $Obj = array();


	/**
	 * Constructor
	 *
	 * If provided, executes SQL query via parent Results object
	 *
	 * @param string Name of table in database
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 * @param string Name of Class for objects within this list
	 * @param string SQL query
	 * @param integer number of lines displayed on one screen
	 * @param string prefix to differentiate page/order params when multiple Results appear one same page
	 * @param string default ordering of columns (special syntax)
	 */
	function __construct( $tablename, $prefix = '', $dbIDname = 'ID', $objType = 'Item', $sql = NULL,
														$limit = 20, $param_prefix = '', $default_order = NULL )
	{
		$this->dbtablename = $tablename;
		$this->dbprefix = $prefix;
		$this->dbIDname = $dbIDname;
		$this->objType = $objType;

		if( !is_null( $sql ) )
		{	// We have an SQL query to execute:
			parent::__construct( $sql, $param_prefix, $default_order, $limit );
		}
		else
		{	// TODO: do we want to autogenerate a query here???
			// Temporary...
			parent::__construct( $sql, $param_prefix, $default_order, $limit );
		}
	}


	/**
	 * Get next object in list
	 *
	 * @return DataObject
	 */
	function & get_next()
	{
		if( $this->current_idx >= $this->result_num_rows )
		{	// No more comment in list
			$r = false;
			return $r;
		}
		return $this->Obj[$this->current_idx++];
	}

}

?>