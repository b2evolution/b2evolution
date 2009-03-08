<?php
/**
 * This file implements the abstract DataObjectList base class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
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

load_class('_core/ui/results/_results.class.php');

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
	function DataObjectList( $tablename, $prefix = '', $dbIDname = 'ID', $objType = 'Item', $sql = NULL,
														$limit = 20, $param_prefix = '', $default_order = NULL )
	{
		$this->dbtablename = $tablename;
		$this->dbprefix = $prefix;
		$this->dbIDname = $dbIDname;
		$this->objType = $objType;

		if( !is_null( $sql ) )
		{	// We have an SQL query to execute:
			parent::Results( $sql, $param_prefix, $default_order, $limit );
		}
		else
		{	// TODO: do we want to autogenerate a query here???
			// Temporary...
			parent::Results( $sql, $param_prefix, $default_order, $limit );
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

/*
 * $Log$
 * Revision 1.4  2009/03/08 23:57:40  fplanque
 * 2009
 *
 * Revision 1.3  2009/02/26 22:16:54  blueyed
 * Use load_class for classes (.class.php), and load_funcs for funcs (.funcs.php)
 *
 * Revision 1.2  2008/01/21 09:35:24  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 10:58:56  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.6  2007/04/26 00:11:09  fplanque
 * (c) 2007
 *
 * Revision 1.5  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>