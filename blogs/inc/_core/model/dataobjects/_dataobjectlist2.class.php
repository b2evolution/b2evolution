<?php
/**
 * This file implements the abstract DataObjectList2 base class.
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

load_class('_core/ui/results/_results.class.php', 'Results' );


/**
 * @package evocore
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
		if( empty( $this->filters ) )
		{
			return false;
		}

		return ( $this->filters != $this->default_filters );
	}


	/**
	 * Get a specific active filter
	 */
	function get_active_filter( $key )
	{
		if( isset($this->filters[$key]) )
		{
			return $this->filters[$key];
		}

		return NULL;
	}


	/**
	 * Get every active filter that is not the same as the defaults
	 */
	function get_active_filters()
	{
		$r = array();

		foreach( $this->default_filters as $key => $value )
		{
			if( !isset( $this->filters[$key] ) )
			{	// Some value has not been copied over from defaults to active or specifically set:
				if( !is_null($value)) // Note: NULL value are not copied over. that's normal.
				{	// A NON NULL value is missing
					$r[] = $key;
				}
			}
			elseif( $value != $this->filters[$key] )
			{
				$r[] = $key;
			}
		}
		return $r;
	}


	/**
	 * Show every active filter that is not the same as the defaults
	 */
	function dump_active_filters()
	{
		foreach( $this->default_filters as $key => $value )
		{
			if( !isset( $this->filters[$key] ) )
			{	// SOme value has not been copied over from defaults to active or specifically set:
				if( !is_null($value)) // Note: NULL value ar enot copied over. that's normal.
				{	// A NON NULL value is missing
					pre_dump( 'no active value for default '.$key );
				}
			}
			elseif( $value != $this->filters[$key] )
			{
				pre_dump( 'default '.$key, $value );
				pre_dump( 'active '.$key, $this->filters[$key] );
			}
		}
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
	 * @param integer number of lines displayed on one screen (null for default [20])
	 * @param string prefix to differentiate page/order params when multiple Results appear on same page
	 * @param string default ordering of columns (special syntax)
	 */
	function DataObjectList2( & $Cache, $limit = null, $param_prefix = '', $default_order = NULL )
	{
		// WARNING: we are not passing any SQL query to the Results object
		// This will make the Results object behave a little bit differently than usual:
		parent::Results( NULL, $param_prefix, $default_order, $limit, NULL, false );

		// The list objects will also be cached in this cache.
		// The Cache object may also be useful to get table information for the Items.
		$this->Cache = & $Cache;

		// Colum used for IDs
		$this->ID_col = $Cache->dbIDname;
	}


	function & get_row_by_idx( $idx )
	{
		return $this->rows[ $idx ];
	}


	/**
	 * Instantiate an object for requested row and cache it:
	 */
	function & get_by_idx( $idx )
	{
		return $this->Cache->instantiate( $this->rows[$idx] ); // pass by reference: creates $rows[$idx]!
	}


	/**
	 * Get next object in list
	 */
	function & get_next()
	{
		// echo '<br />Get next, current idx was: '.$this->current_idx.'/'.$this->result_num_rows;

		if( $this->current_idx >= $this->result_num_rows )
		{	// No more object in list
			$this->current_Obj = NULL;
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
			$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Entry') ), 'error' );
			$DB->commit();
			return false;
		}
		$order = $obj->order;

		// Get the ID of the inferior element which his order is the nearest
		$rows = $DB->get_results( 'SELECT '.$this->Cache->dbIDname
			 	.' FROM '.$this->Cache->dbtablename
				.' WHERE '.$this->Cache->dbprefix.'order < '.$order
				.' ORDER BY '.$this->Cache->dbprefix.'order DESC'
				.' LIMIT 0,1' );

		if( count( $rows ) )
		{
			// instantiate the inferior element
			$obj_inf = & $this->Cache->get_by_ID( $rows[0]->{$this->Cache->dbIDname} );

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
			$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Entry') ), 'error' );
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
			$obj_sup = & $this->Cache->get_by_ID( $rows[0]->{$this->Cache->dbIDname} );

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
 * Revision 1.13  2009/12/01 20:58:27  blueyed
 * doc, indent
 *
 * Revision 1.12  2009/11/30 22:56:09  blueyed
 * typo
 *
 * Revision 1.11  2009/09/14 10:38:23  efy-arrin
 * Include the ClassName in the load_class() with proper UpperCase
 *
 * Revision 1.10  2009/08/30 19:54:25  fplanque
 * less translation messgaes for infrequent errors
 *
 * Revision 1.9  2009/08/30 00:30:52  fplanque
 * increased modularity
 *
 * Revision 1.8  2009/03/08 23:57:40  fplanque
 * 2009
 *
 * Revision 1.7  2009/02/26 22:16:54  blueyed
 * Use load_class for classes (.class.php), and load_funcs for funcs (.funcs.php)
 *
 * Revision 1.6  2008/01/21 09:35:24  fplanque
 * (c) 2008
 *
 * Revision 1.5  2007/11/25 19:47:15  fplanque
 * cleaned up photo/media index a little bit
 *
 * Revision 1.4  2007/11/25 14:28:17  fplanque
 * additional SEO settings
 *
 * Revision 1.3  2007/09/28 09:28:36  fplanque
 * per blog advanced SEO settings
 *
 * Revision 1.2  2007/09/23 18:57:15  fplanque
 * filter handling fixes
 *
 * Revision 1.1  2007/06/25 10:58:57  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.10  2007/06/11 22:01:52  blueyed
 * doc fixes
 *
 * Revision 1.9  2007/05/26 22:21:32  blueyed
 * Made $limit for Results configurable per user
 *
 * Revision 1.8  2007/04/26 00:11:09  fplanque
 * (c) 2007
 *
 * Revision 1.7  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>