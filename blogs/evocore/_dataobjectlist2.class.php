<?php
/**
 * This file implements the abstract DataObjectList2 base class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes
 */
require_once dirname(__FILE__).'/_results.class.php';


/**
 *
 */
class FilteredResults extends Results
{
	/**
	 * Default filter set (used if no specific params are passed)
	 */
	var $default_filters = array();

	/**
	 * Current filter set (depending on user input)
	 */
	var $filters = array();


	/**
	 * Check if the Result set is filtered or not
	 */
	function is_filtered()
	{
		return ( $this->filters != $this->default_filters );
	}
}



/**
 * Data Object List Base Class 2
 *
 * This is typically an abstract class, useful only when derived.
 * Holds DataObjects in an array and allows walking through...
 *
 * This SECOND implementation will deprecate the first one when finished.
 *
 * @package evocore
 * @version beta
 * @abstract
 */
class DataObjectList2 extends FilteredResults
{


	/**
	 * Constructor
	 *
	 * If provided, executes SQL query via parent Results object
	 *
	 * @param DataObjectCache
	 * @param integer number of lines displayed on one screen
	 * @param string prefix to differentiate page/order params when multiple Results appear one same page
	 * @param string default ordering of columns (special syntax)
	 */
	function DataObjectList2( & $Cache, $limit = 20, $param_prefix = '', $default_order = NULL )
	{
		// WARNING: we are not passing any SQL query to the Results object
		// This will make the Results object behave a little buit differently than usual:
		parent::Results( NULL, $param_prefix, $default_order, $limit, NULL, false );

		// The list objects will also be cached in this cache.
		// Tje Cache object may also be useful to get table information for the Items.
		$this->Cache = & $Cache;

		// Colum used for IDs
		$this->ID_col = $Cache->dbIDname;
	}


	/**
	 * Instantiate an object for requested row and cache it:
	 */
	function & get_by_idx( $idx )
	{
		return $this->Cache->instantiate( $this->rows[$idx] );
	}


	/**
	 * Get next object in list
	 */
	function & get_next()
	{
		// echo '<br />Get next, current idx was: '.$this->current_idx.'/'.$this->result_num_rows;

		if( $this->current_idx >= $this->result_num_rows )
		{	// No more comment in list
			$r = false; // TODO: try with NULL
			return $r;
		}

		// We also keep a local ref in case we want to use it for display:
		$this->current_Obj = & $this->get_by_idx( $this->current_idx );
		$this->next_idx();

		return $this->current_Obj;
	}


	/**
	 * Display a global title matching filter params
	 *
	 * @todo implement $order
	 *
	 * @param string prefix to display if a title is generated
	 * @param string suffix to display if a title is generated
	 * @param string glue to use if multiple title elements are generated
	 * @param string comma separated list of titles inthe order we would like to display them
	 * @param string format to output, default 'htmlbody'
	 */
	function get_filter_title( $prefix = ' ', $suffix = '', $glue = ' - ', $order = NULL, $format = 'htmlbody' )
	{
		$title_array = $this->get_filter_titles();

  	if( empty( $title_array ) )
  	{
			return '';
		}

		// We have something to display:
		$r = implode( $glue, $title_array );
		$r = $prefix.format_to_output( $r, $format ).$suffix;
		return $r;
	}
	
	
	/**
	 * Move up the element order in database
	 *
	 * @param integer id element
	 * @return unknown
	 */
	function move_up( $id )
	{
		global $DB, $Messages, $result_fadeout;
		
		$DB->begin();
		
		if( ($obj = & $this->Cache->get_by_ID( $id )) === false )
		{
			$Messages->head = T_('Cannot edit entry!');
			$Messages->add( T_('Requested entry does not exist any longer.'), 'error' );
			$DB->commit();
			return false;
		}
		$order = $obj->order;
		
		// Get the ID of the inferior element which his order is the nearest   	
		$rows = $DB->get_results( 'SELECT '.$this->Cache->dbIDname
														 	.' FROM '.$this->Cache->dbtablename
														 .' WHERE '.$this->Cache->dbprefix.'order < '.$order  
													.' ORDER BY '.$this->Cache->dbprefix.'order DESC 
														 		LIMIT 0,1' );
		
		if( count( $rows ) )
		{
			// instantiate the inferior element
			$obj_inf = & $this->Cache->get_by_ID( $rows[0]->act_ID );
			
			//  Update element order
			$obj->set( 'order', $obj_inf->order );
			$obj->dbupdate();
			
			// Update inferior element order
			$obj_inf->set( 'order', $order );
			$obj_inf->dbupdate();
			
			// EXPERIMENTAL FOR FADEOUT RESULT
			$result_fadeout[$this->Cache->dbIDname][] = $id;
			$result_fadeout[$this->Cache->dbIDname][] = $obj_inf->ID;
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
	function move_down( $id )
	{
		global $DB, $Messages, $result_fadeout;
		
		$DB->begin();
		
		if( ($obj = & $this->Cache->get_by_ID( $id )) === false )
		{
			$Messages->head = T_('Cannot edit entry!');
			$Messages->add( T_('Requested entry does not exist any longer.'), 'error' );
			$DB->commit();
			return false;
		}
		$order = $obj->order;
		
		// Get the ID of the inferior element which his order is the nearest   	
		$rows = $DB->get_results( 'SELECT '.$this->Cache->dbIDname
														 	.' FROM '.$this->Cache->dbtablename
														 .' WHERE '.$this->Cache->dbprefix.'order > '.$order  
													.' ORDER BY '.$this->Cache->dbprefix.'order ASC 
														 		LIMIT 0,1' );
		
		if( count( $rows ) )
		{
			// instantiate the inferior element
			$obj_sup = & $this->Cache->get_by_ID( $rows[0]->act_ID );
			
			//  Update element order
			$obj->set( 'order', $obj_sup->order );
			$obj->dbupdate();
			
			// Update inferior element order
			$obj_sup->set( 'order', $order );
			$obj_sup->dbupdate();
			
			// EXPERIMENTAL FOR FADEOUT RESULT
			$result_fadeout[$this->Cache->dbIDname][] = $id;
			$result_fadeout[$this->Cache->dbIDname][] = $obj_sup->ID;
		}
		else 
		{
			$Messages->add( T_('This element is already at the bottom.'), 'error' ); 
		}	
		$DB->commit();
	}
}

/*
 * $Log$
 * Revision 1.7  2006/02/03 21:58:05  fplanque
 * Too many merges, too little time. I can hardly keep up. I'll try to check/debug/fine tune next week...
 *
 * Revision 1.6  2006/01/04 15:03:52  fplanque
 * enhanced list sorting capabilities
 *
 * Revision 1.5  2005/12/30 20:13:39  fplanque
 * UI changes mostly (need to double check sync)
 *
 * Revision 1.4  2005/12/20 19:23:40  fplanque
 * implemented filter comparison/detection
 *
 * Revision 1.3  2005/12/20 18:12:50  fplanque
 * enhanced filtering/titling framework
 *
 * Revision 1.2  2005/12/19 19:30:14  fplanque
 * minor
 *
 * Revision 1.1  2005/12/08 13:13:33  fplanque
 * no message
 *
 */
?>