<?php
/**
 * Data Object List Base Class
 * 
 * "data objects by fplanque" :P
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package b2evocore
 */

/**
 * Data Object List Base Class
 *
 * This is typically an abstract class, useful only when derived.
 *
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
	 */
	function display_if_empty( $message = 'Sorry, there is nothing to display...' )
	{
		if( $this->result_num_rows == 0 )
		{
			echo $message;
		}
	}
	
}
?>
