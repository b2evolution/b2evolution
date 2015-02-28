<?php
/**
 * This file implements the Generic Ordered Cache class.
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

load_class( 'generic/model/_genericcache.class.php', 'GenericCache' );

/**
 * GenericOrderedCache Class
 * @package evocore
 */
class GenericOrderedCache extends GenericCache
{
	/**
	 * Constructor
	 */
	function GenericOrderedCache( $objtype, $load_all, $tablename, $prefix = '', $dbIDname = 'ID', $name_field = NULL )
	{
		parent::GenericCache( $objtype, $load_all, $tablename, $prefix, $dbIDname, $name_field );
	}


	/**
	 * Move up the element order in database
	 *
	 * @param integer id element
	 * @return unknown
	 */
	function move_up_by_ID( $id )
	{
		global $DB, $Messages, $result_fadeout;

		$DB->begin();

		if( ($obj_sup = & $this->get_by_ID( $id )) === false )
		{
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Entry') ), 'error' );
			$DB->commit();
			return false;
		}
		$order = $obj_sup->order;

		// Get the ID of the inferior element which his order is the nearest
		$rows = $DB->get_results( 'SELECT '.$this->dbIDname
														 	.' FROM '.$this->dbtablename
														 .' WHERE '.$this->dbprefix.'order < '.$order
													.' ORDER BY '.$this->dbprefix.'order DESC
														 		LIMIT 0,1' );

		if( count( $rows ) )
		{
			// instantiate the inferior element
			$obj_inf = & $this->get_by_ID( $rows[0]->{$this->dbIDname} );

			// Update element order
			$obj_sup->set( 'order', $obj_inf->order );
			$obj_sup->dbupdate();

			// Update inferior element order
			$obj_inf->set( 'order', $order );
			$obj_inf->dbupdate();

			// EXPERIMENTAL FOR FADEOUT RESULT
			$result_fadeout[$this->dbIDname][] = $id;
			$result_fadeout[$this->dbIDname][] = $obj_inf->ID;
		}
		else
		{
			$Messages->add( T_('This element is already at the top.'), 'error' );
		}
		$DB->commit();
	}


	/**
	 * Move down the element order in database
	 *
	 * @param integer id element
	 * @return unknown
	 */
	function move_down_by_ID( $id )
	{
		global $DB, $Messages, $result_fadeout;

		$DB->begin();

		if( ($obj_inf = & $this->get_by_ID( $id )) === false )
		{
			$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Entry') ), 'error' );
			$DB->commit();
			return false;
		}
		$order = $obj_inf->order;

		// Get the ID of the inferior element which his order is the nearest
		$rows = $DB->get_results( 'SELECT '.$this->dbIDname
														 	.' FROM '.$this->dbtablename
														 .' WHERE '.$this->dbprefix.'order > '.$order
													.' ORDER BY '.$this->dbprefix.'order ASC
														 		LIMIT 0,1' );

		if( count( $rows ) )
		{
			// instantiate the inferior element
			$obj_sup = & $this->get_by_ID( $rows[0]->{$this->dbIDname} );

			//  Update element order
			$obj_inf->set( 'order', $obj_sup->order );
			$obj_inf->dbupdate();

			// Update inferior element order
			$obj_sup->set( 'order', $order );
			$obj_sup->dbupdate();

			// EXPERIMENTAL FOR FADEOUT RESULT
			$result_fadeout[$this->dbIDname][] = $id;
			$result_fadeout[$this->dbIDname][] = $obj_sup->ID;
		}
		else
		{
			$Messages->add( T_('This element is already at the bottom.'), 'error' );
		}
		$DB->commit();
	}

}

?>