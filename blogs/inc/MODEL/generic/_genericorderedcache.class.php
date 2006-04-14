<?php
/**
 * This file implements the Generic Ordered Cache class.
 *
 * @copyright (c)2004-2005 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * @package gsbcore
 *
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../dataobjects/_dataobjectcache.class.php';

/**
 * GenericOrderedCache Class
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
			$Messages->head = T_('Cannot edit entry!');
			$Messages->add( T_('Requested entry does not exist any longer.'), 'error' );
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
			$Messages->head = T_('Cannot edit entry!');
			$Messages->add( T_('Requested entry does not exist any longer.'), 'error' );
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