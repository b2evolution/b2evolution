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
		$this->current_Obj = & $this->get_by_idx( $this->current_idx++ );

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
}

/*
 * $Log$
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