<?php
/**
 * This file implements the abstract DataObjectList base class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @author fplanque: François PLANQUE
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Data Object List Base Class
 *
 * This is typically an abstract class, useful only when derived.
 *
 * @package evocore
 * @version beta
 * @abstract
 */
class DataObjectList
{
	/**#@+
	 * @access private
	 */
	var	$dbtablename;
	var $dbprefix;
	var $dbIDname;
	var $posts_per_page = 15;			
	/** 
	 * SQL query string
	 */
	var $request;
	/**
	 * DB Result set (array)
	 */
	var $result;
	/**
	 * Number of rows in result set. Typically equal to $posts_per_page, once loaded.
	 */
	var $result_num_rows = 0;
	/**
	 * Object array
	 */
	var $Obj = array();
	/**
	 * Current object idx in array:
	 */
	var $current_idx = 0;
	/**#@-*/

	/** 
	 * Constructor
	 *
	 * {@internal DataObjectList::DataObjectList(-) }}
	 *
	 * @param string Name of table in database
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 */
	function DataObjectList( $tablename, $prefix = '', $dbIDname = 'ID' )
	{
		$this->dbtablename = $tablename;
		$this->dbprefix = $prefix;
		$this->dbIDname = $dbIDname;
	}	

	/**
	 * Get nummber of rows available for display
	 *
	 * {@internal DataObjectList::get_num_rows(-) }}
	 *
	 * @return integer
	 */
	function get_num_rows()
	{
		return $this->result_num_rows;
	}

	/** 
	 * Get next comment in list
	 *
	 * {@internal CommentList::get_next(-) }}
	 */
	function get_next()
	{
		if( $this->current_idx >= $this->result_num_rows )
		{	// No more comment in list
			return false;
		}
		return  $this->Obj[$this->current_idx++];
	}

	/**
	 * Rewind resultset
	 *
	 * {@internal DataObjectList::restart(-) }}
	 */
	function restart()
	{
		$this->current_idx = 0;
	}
	
	
	/**
	 * Template function: display message if list is empty
	 *
	 * {@internal DataObjectList::display_if_empty(-) }}
	 *
	 * @param string String to display if list is empty
   * @return true if empty
	 */
	function display_if_empty( $message = '' )
	{
		if( empty($message) ) 
		{	// Default message:
			$message = T_('Sorry, there is nothing to display...');
		}

		if( $this->result_num_rows == 0 )
		{
			echo $message;
      return true;
		}
    return false;
	}
	
}

/*
 * $Log$
 * Revision 1.12  2004/10/12 10:27:18  fplanque
 * Edited code documentation.
 *
 */
?>