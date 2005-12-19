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
class DataObjectList2 extends Results
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
	 * Get next object in list
	 */
	function & get_next()
	{
		// echo '<br />Get next, current idx was: '.$this->current_idx.'/'.$this->result_num_rows;

		if( $this->current_idx >= $this->result_num_rows )
		{	// No more comment in list
			$r = false;
			return $r;
		}

		if( ! is_null( $this->Cache ) )
		{ // We want to instantiate an object for the row and cache it:
			// We also keep a local ref in case we want to use it for display:
			$this->current_Obj = & $this->Cache->instantiate( $this->rows[$this->current_idx++] );
		}

		return $this->current_Obj;
	}

}

/*
 * $Log$
 * Revision 1.2  2005/12/19 19:30:14  fplanque
 * minor
 *
 * Revision 1.1  2005/12/08 13:13:33  fplanque
 * no message
 *
 */
?>