<?php
/**
 * This file implements the Generic Cache class.
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

load_class( '_core/model/dataobjects/_dataobjectcache.class.php' ,'DataObjectCache' );

/**
 * GenericCache Class
 * @package evocore
 */
class GenericCache extends DataObjectCache
{
	/**
	 * Constructor
	 */
	function GenericCache( $objtype, $load_all, $tablename, $prefix = '', $dbIDname = 'ID', $name_field = NULL, $order_by = '', $allow_none_text = NULL, $allow_none_value = '', $select = '' )
	{
		parent::DataObjectCache( $objtype, $load_all, $tablename, $prefix, $dbIDname, $name_field, $order_by, $allow_none_text, $allow_none_value, $select );
	}


	/**
	 * Instanciate a new object within this cache
	 *
	 * @param object|NULL
	 */
	function & new_obj( $row = NULL )
	{
		$objtype = $this->objtype;

		// Instantiate a custom object
		$obj = new $objtype( $this->dbtablename, $this->dbprefix, $this->dbIDname, $row ); // Copy

		return $obj;
	}
}

?>