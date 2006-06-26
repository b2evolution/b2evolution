<?php
/**
 * This file implements the ItemList class 2.
 *
 * This is the object handling item/post/article lists.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../dataobjects/_dataobjectlist2.class.php';
require_once dirname(__FILE__).'/_item.class.php';
require_once dirname(__FILE__).'/_item.funcs.php';

/**
 * Item List Class 2
 *
 * This SECOND implementation will deprecate the first one when finished.
 *
 * @package evocore
 */
class ItemList2 extends DataObjectList2
{
	/**
	 * SQL object for the Query
	 */
	var $ItemQuery;

	/**
	 * Blog object this ItemList refers to
	 */
	var $Blog;

	/**
	 * list unit: 'posts' or 'days'
	 */
	var $unit;

	/**
	 * Did we request a single post?
	 */
 	var $single_post = false;

	/**
	 * Last date that has been output by date_if_changed()
	 */
	var $last_displayed_date = '';

  /**
   * Lazy filled
   * @access private
   */
  var $advertised_start_date;
  var $advertised_stop_date;
	/**
 	 * Anti infinite loops:
	 */
	var $getting_adv_start_date = false;
	var $getting_adv_stop_date = false;


	/**
	 * Constructor
	 *
	 * @todo  add param for saved session filter set
	 *
	 * @param Blog
	 * @param mixed Default filter set: Do not show posts before this timestamp, can be 'now'
	 * @param mixed Default filter set: Do not show posts after this timestamp, can be 'now'
	 * @param string name of cache to be used
	 * @param string prefix to differentiate page/order params when multiple Results appear one same page
	 * @param array restrictions for itemlist (position, contact, firm, ...) key: restriction name, value: ID of the restriction
	 */
	function ItemList2(
			& $Blog,
			$timestamp_min = NULL,       // Do not show posts before this timestamp
			$timestamp_max = NULL,   		 // Do not show posts after this timestamp
			$cache_name = 'ItemCache',	 // name of cache to be used
			$param_prefix = '',
			$filterset_name = '',				// Name to be used when saving the filterset (leave empty to use default for collection)
			$restrict_to = array()			// Restrict the item list to a position, or contact, firm.....
		)
	{
		global $Settings, $$cache_name;

		// echo '<br />Instanciating ItemList2';

		$DataObjectCache = & $$cache_name; // By ref!!

		// Call parent constructor:
		parent::DataObjectList2( $DataObjectCache, 20, $param_prefix, NULL );

		// The SQL Query object:
		$this->ItemQuery = & new ItemQuery( $this->Cache->dbtablename, $this->Cache->dbprefix, $this->Cache->dbIDname );

		$this->Blog = & $Blog;

		if( !empty( $filterset_name ) )
		{	// Set the filterset_name with the filterset_name param
			$this->filterset_name = 'ItemList_filters_'.$filterset_name;
		}
		else
		{	// Set a generic filterset_name
			$this->filterset_name = 'ItemList_filters_coll'.$this->Blog->ID;
		}

		$this->page_param = $param_prefix.'paged';
		
		$this->restrict_to = $restrict_to;

		// Initialize the default filter set:
		$this->set_default_filters( array(
				'filter_preset' => NULL,
				'ts_min' => $timestamp_min,
        'ts_max' => $timestamp_max,
        'cat_array' => array(),
        'cat_modifier' => NULL,
				'authors' => NULL,
				'assignees' => NULL,
				'author_assignee' => NULL,
				'keywords' => NULL,
        'phrase' => 'AND',
        'exact' => 0,
        'post_ID' => NULL,
        'post_title' => NULL,
        'ymdhms' => NULL,
        'week' => NULL,
        'ymdhms_min' => NULL,
        'ymdhms_max' => NULL,
        'statuses' => NULL,
				'visibility_array' => array( 'published', 'protected', 'private', 'draft', 'deprecated' ),
				'order' => 'DESC',
        'orderby' => 'datestart',
        'unit' => $Settings->get('what_to_show'),
				'posts' => $this->limit,
				'page' => 1,
				'item_type' => NULL,
			) );
	}


	/**
	 * Set default filter values we always want to use if not individually specified otherwise:
	 *
	 * @param array default filters to be merged with the class defaults
	 * @param array default filters for each preset, to be merged with general default filters if the preset is used
	 */
	function set_default_filters( $default_filters, $preset_filters = array() )
	{
		$this->default_filters = array_merge( $this->default_filters, $default_filters );
		$this->preset_filters = $preset_filters;
	}


	/**
	 * Set/Activate filterset
	 *
	 * This will also set back the GLOBALS !!! needed for regenerate_url().
	 *
	 * @param array
	 */
	function set_filters( $filters )
	{
	  /**
	   * @var Request
	   */
		global $Request;

		// Activate the filterset (fallback to default filter when a value is not set):
		$this->filters = array_merge( $this->default_filters, $filters );
		
		// Activate preset filters if necessary:
		$this->activate_preset_filters();

		// set back the GLOBALS !!! needed for regenerate_url() :
	
		/*
		 * Selected filter preset:
		 */
		$Request->memorize_param( $this->param_prefix.'filter_preset', 'string', $this->default_filters['filter_preset'], $this->filters['filter_preset'] );  // List of authors to restrict to

		
		/*
		 * Blog & Chapters/categories restrictions:
		 */
		// Get chapters/categories (and compile those values right away)
 		$Request->memorize_param( 'cat', '/^[*\-]?([0-9]+(,[0-9]+)*)?$/', $this->default_filters['cat_modifier'], $this->filters['cat_modifier'] );  // List of authors to restrict to
		$Request->memorize_param( 'catsel', 'array', $this->default_filters['cat_array'], $this->filters['cat_array'] );
		// TEMP until we get this straight:
		global $cat_array, $cat_modifier;
		$cat_array = $this->default_filters['cat_array'];
		$cat_modifier = $this->default_filters['cat_modifier'];


		/*
		 * Restrict to selected authors:
		 */
		$Request->memorize_param( $this->param_prefix.'author', 'string', $this->default_filters['authors'], $this->filters['authors'] );  // List of authors to restrict to

		/*
		 * Restrict to selected assignees:
		 */
		$Request->memorize_param( $this->param_prefix.'assgn', 'string', $this->default_filters['assignees'], $this->filters['assignees'] );  // List of assignees to restrict to
		
		
		/*
		 * Restrict to selected author OR assignee:
		 */
		$Request->memorize_param( $this->param_prefix.'author_assignee', 'string', $this->default_filters['author_assignee'], $this->filters['author_assignee'] ); 

		/*
		 * Restrict to selected statuses:
		 */
		$Request->memorize_param( $this->param_prefix.'status', 'string', $this->default_filters['statuses'], $this->filters['statuses'] );  // List of statuses to restrict to

		/*
		 * Restrict to selected item type:
		 */
		$Request->memorize_param( $this->param_prefix.'item_type', 'integer', $this->default_filters['item_type'], $this->filters['item_type'] );  // List of item types to restrict to);
			
		/*
		 * Restrict by keywords
		 */
		$Request->memorize_param( $this->param_prefix.'s', 'string', $this->default_filters['keywords'], $this->filters['keywords'] );			 // Search string
		$Request->memorize_param( $this->param_prefix.'sentence', 'string', $this->default_filters['phrase'], $this->filters['phrase'] ); // Search for sentence or for words
		$Request->memorize_param( $this->param_prefix.'exact', 'integer', $this->default_filters['exact'], $this->filters['exact'] );     // Require exact match of title or contents

		/*
		 * Specific Item selection?
		 */
		$Request->memorize_param( $this->param_prefix.'m', 'integer', $this->default_filters['ymdhms'], $this->filters['ymdhms'] );          // YearMonth(Day) to display
		$Request->memorize_param( $this->param_prefix.'w', 'integer', $this->default_filters['week'], $this->filters['week'] );            // Week number
		$Request->memorize_param( $this->param_prefix.'dstart', 'integer', $this->default_filters['ymdhms_min'], $this->filters['ymdhms_min'] ); // YearMonth(Day) to start at
		$Request->memorize_param( $this->param_prefix.'dstop', 'integer', $this->default_filters['ymdhms_max'], $this->filters['ymdhms_max'] ); // YearMonth(Day) to start at

		// TODO: show_past/future should probably be wired on dstart/dstop instead on timestamps -> get timestamps out of filter perimeter
		if( is_null($this->default_filters['ts_min'])
			&& is_null($this->default_filters['ts_max'] ) )
		{	// We have not set a strict default -> we allow overridding:
    	$Request->memorize_param( $this->param_prefix.'show_past', 'integer', 0, ($this->filters['ts_min'] == 'now') ? 0 : 1 );
			$Request->memorize_param( $this->param_prefix.'show_future', 'integer', 0, ($this->filters['ts_max'] == 'now') ? 0 : 1 );
		}

    /*
		 * Restrict to the statuses we want to show:
		 */
		// Note: oftentimes, $show_statuses will have been preset to a more restrictive set of values
		$Request->memorize_param( $this->param_prefix.'show_status', 'array', $this->default_filters['visibility_array'], $this->filters['visibility_array'] );	// Array of sharings to restrict to

		/*
		 * OLD STYLE orders:
		 */
		$Request->memorize_param( $this->param_prefix.'order', 'string', $this->default_filters['order'], $this->filters['order'] );   		// ASC or DESC
		$Request->memorize_param( $this->param_prefix.'orderby', 'string', $this->default_filters['orderby'], $this->filters['orderby'] );  // list of fields to order by (TODO: change that crap)

		/*
		 * Paging limits:
		 */
 		$Request->memorize_param( $this->param_prefix.'unit', 'string', $this->default_filters['unit'], $this->filters['unit'] );    		// list unit: 'posts' or 'days'
		$this->unit = $this->filters['unit'];	// TEMPORARY

		$Request->memorize_param( $this->param_prefix.'posts', 'integer', $this->default_filters['posts'], $this->filters['posts'] ); 			// # of units to display on the page
		$this->limit = $this->filters['posts']; // for compatibility with parent class

		// 'paged'
		$Request->memorize_param( $this->page_param, 'integer', 1, $this->filters['page'] );      // List page number in paged display
		$this->page = $this->filters['page'];
	}


	/**
	 * Init filter params from Request params
	 *
	 * @return boolean true if we could apply a filterset based on Request params (either explicit or reloaded)
	 */
	function load_from_Request()
	{
	  /**
	   * @var Request
	   */
		global $Request;


		// Do we want to restore filters or do we want to create a new filterset
		$filter_action = $Request->param( $this->param_prefix.'filter', 'string', 'save' );
		// echo ' filter action: ['.$filter_action.'] ';
		switch( $filter_action )
		{
			case 'restore':
				return $this->restore_filterset();
				/* BREAK */

			case 'reset':
				// We want to reset the memorized filterset:
				global $Session;
				$Session->delete( $this->filterset_name );
				// We have applied no filterset:
				return false;
				/* BREAK */
		}
		

		/**
		 * Filter preset
		 */
		$this->filters['filter_preset'] = $Request->param( $this->param_prefix.'filter_preset', 'string', $this->default_filters['filter_preset'], true );

		// Activate preset default filters if necessary:
		$this->activate_preset_filters();
			

		/*
		 * Blog & Chapters/categories restrictions:
		 */
		// Get chapters/categories (and compile those values right away)
		$Request->compile_cat_array( $this->Blog->ID == 1 ? 0 : $this->Blog->ID,
								$this->default_filters['cat_modifier'], $this->default_filters['cat_array'] );

		$this->filters['cat_array'] = $Request->get( 'cat_array' );
		$this->filters['cat_modifier'] = $Request->get( 'cat_modifier' );


		/*
		 * Restrict to selected authors:
		 */
		$this->filters['authors'] = $Request->param( $this->param_prefix.'author', '/^-?[0-9]+(,[0-9]+)*$/', $this->default_filters['authors'], true );      // List of authors to restrict to


		/*
		 * Restrict to selected assignees:
		 */
		$this->filters['assignees'] = $Request->param( $this->param_prefix.'assgn', '/^(-|-[0-9]+|[0-9]+)(,[0-9]+)*$/', $this->default_filters['assignees'], true );      // List of assignees to restrict to

		
		/*
		 * Restrict to selected author or assignee:
		 */
		$this->filters['author_assignee'] = $Request->param( $this->param_prefix.'author_assignee', '/^[0-9]+$/', $this->default_filters['author_assignee'], true ); 


		/*
		 * Restrict to selected statuses:
		 */
		$this->filters['statuses'] = $Request->param( $this->param_prefix.'status', '/^(-|-[0-9]+|[0-9]+)(,[0-9]+)*$/', $this->default_filters['statuses'], true );      // List of statuses to restrict to


		/*
		 * Restrict by keywords
		 */
		$this->filters['keywords'] = $Request->param( $this->param_prefix.'s', 'string', $this->default_filters['keywords'], true );         // Search string
		$this->filters['phrase'] = $Request->param( $this->param_prefix.'sentence', 'string', $this->default_filters['phrase'], true ); // Search for sentence or for words
		$this->filters['exact'] = $Request->param( $this->param_prefix.'exact', 'integer', $this->default_filters['exact'], true );        // Require exact match of title or contents


		/*
		 * Specific Item selection?
		 */
    $this->filters['post_ID'] = $Request->param( $this->param_prefix.'p', 'integer', $this->default_filters['post_ID'] );          // Specific post number to display
		$this->filters['post_title'] = $Request->param( $this->param_prefix.'title', 'string', $this->default_filters['post_title'] );	  // urtitle of post to display

		$this->single_post = !empty($this->filters['post_ID']) || !empty($this->filters['post_title']);


		/*
		 * If a timeframe is specified in the querystring, restrict to that timeframe:
		 */
		$this->filters['ymdhms'] = $Request->param( $this->param_prefix.'m', 'integer', $this->default_filters['ymdhms'], true );          // YearMonth(Day) to display
		$this->filters['week'] = $Request->param( $this->param_prefix.'w', 'integer', $this->default_filters['week'], true );            // Week number

		$this->filters['ymdhms_min'] = $Request->param_compact_date( $this->param_prefix.'dstart', $this->default_filters['ymdhms_min'], true, T_( 'Invalid date' ) ); // YearMonth(Day) to start at
		$this->filters['ymdhms_max'] = $Request->param_compact_date( $this->param_prefix.'dstop', $this->default_filters['ymdhms_max'], true, T_( 'Invalid date' ) ); // YearMonth(Day) to stop at


		// TODO: show_past/future should probably be wired on dstart/dstop instead on timestamps -> get timestamps out of filter perimeter
		// So far, these act as SILENT filters. They will not advertise their filtering in titles etc.
		$this->filters['ts_min'] = $this->default_filters['ts_min'];
		$this->filters['ts_max'] = $this->default_filters['ts_max'];
		if( is_null($this->default_filters['ts_min'])
			&& is_null($this->default_filters['ts_max'] ) )
		{	// We have not set a strict default -> we allow overridding:
    	$show_past = $Request->param( $this->param_prefix.'show_past', 'integer', 0, true );
			$show_future = $Request->param( $this->param_prefix.'show_future', 'integer', 0, true );
			if( $show_past != $show_future )
			{	// There is a point in overridding:
				$this->filters['ts_min'] = ( $show_past == 0 ) ? 'now' : '';
				$this->filters['ts_max'] = ( $show_future == 0 ) ? 'now' : '';
			}
		}


		/*
		 * Restrict to the statuses we want to show:
		 */
		// Note: oftentimes, $show_statuses will have been preset to a more restrictive set of values
		$this->filters['visibility_array'] = $Request->param( $this->param_prefix.'show_status', 'array', $this->default_filters['visibility_array'], true );	// Array of sharings to restrict to


		/*
		 * Ordering:
		 */
		$this->filters['order'] = $Request->param( $this->param_prefix.'order', '/^(ASC|asc|DESC|desc)$/', $this->default_filters['order'], true );		// ASC or DESC
		$this->filters['orderby'] = $Request->param( $this->param_prefix.'orderby', '/^([A-Za-z0-9_]+([ ,][A-Za-z0-9_]+)*)?$/', $this->default_filters['orderby'], true );   // list of fields to order by (TODO: change that crap)


		/*
		 * Paging limits:
		 */
 		$this->filters['unit'] = $Request->param( $this->param_prefix.'unit', 'string', $this->default_filters['unit'], true );    		// list unit: 'posts' or 'days'
		$this->unit = $this->filters['unit'];	// TEMPORARY
		// echo '<br />unit='.$this->filters['unit'];

		$this->filters['posts'] = $Request->param( $this->param_prefix.'posts', 'integer', $this->default_filters['posts'], true ); 			// # of units to display on the page
		$this->limit = $this->filters['posts']; // for compatibility with parent class

		// 'paged'
		$this->filters['page'] = $Request->param( $this->page_param, 'integer', 1, true );      // List page number in paged display
		$this->page = $this->filters['page'];

		// Item type
		$this->filters['item_type'] = $Request->param( $this->param_prefix.'item_type', 'integer', $this->default_filters['item_type'], true );  // List of item types to restrict to);
			
		


		if( $Request->validation_errors() )
		{
			return false;
		}


		if( $this->single_post )
		{	// We have requested a specific post
			// Do not attempt to save or load any filterset:
			return true;
		}

		// echo ' Got filters from URL?:'.($this->is_filtered() ? 'YES' : 'NO');
		//pre_dump( $this->default_filters );
		//pre_dump( $this->filters );

		if( $filter_action == 'save' )
		{
			$this->save_filterset();
		}

		return true;
	}
	
	
	/**
	 * Activate preset default filters if necessary
	 *
	 */
	function activate_preset_filters()
	{
		$filter_preset = $this->filters['filter_preset'];

		if( empty( $filter_preset ) )
		{ // No filter preset, there are no additional defaults to use:
			return;
		}	

		// Override general defaults with the specific defaults for the preset:
		$this->default_filters = array_merge( $this->default_filters, $this->preset_filters[$filter_preset] );

		// Save the name of the preset in order for is_filtered() to work properly:
		$this->default_filters['filter_preset'] = $this->filters['filter_preset'];
	}
	

  /**
   * Save current filterset to session.
   */
	function save_filterset()
	{
    /**
  	 * @var Session
  	 */
		global $Session, $Debuglog;

		$Debuglog->add( 'Saving filterset <strong>'.$this->filterset_name.'</strong>', 'filters' );

		$Session->set( $this->filterset_name, $this->filters );
	}


  /**
   * Load previously saved filterset from session.
   *
   * @return boolean true if we could restore something
   */
	function restore_filterset()
	{
	  /**
	   * @var Session
	   */
		global $Session;
	  /**
	   * @var Request
	   */
		global $Request;

		global $Debuglog;

		$filters = $Session->get( $this->filterset_name );

		if( empty($filters) )
		{ // We have no saved filters:
			return false;
		}

		$Debuglog->add( 'Restoring filterset <strong>'.$this->filterset_name.'</strong>', 'filters' );

		// Restore filters:
		$this->set_filters( $filters );

		return true;
	}


	/**
	 *
	 *
	 * @todo count?
	 */
	function query()
	{
		global $DB, $Request, $current_User;

		if( !is_null( $this->rows ) )
		{ // Query has already executed:
			return;
		}


		if( empty( $this->filters ) )
		{	// Filters have not been set before, we'll use the default filterset:
			// If there is a preset filter, we need to activate its specific defaults:
			$this->filters['filter_preset'] = $Request->param( $this->param_prefix.'filter_preset', 'string', $this->default_filters['filter_preset'], true );
			$this->activate_preset_filters();

			// Use the default filters:
			$this->set_filters( $this->default_filters );
		}


		// echo '<br />ItemList2 query';

		// GENERATE THE QUERY:

		/*
		 * filetring stuff:
		 */
		$this->ItemQuery->where_chapter2( $this->Blog->ID, $this->filters['cat_array'], $this->filters['cat_modifier'] );

		$this->ItemQuery->where_author( $this->filters['authors'] );

		$this->ItemQuery->where_assignees( $this->filters['assignees'] );

		$this->ItemQuery->where_author_assignee( $this->filters['author_assignee'] );
		
		$this->ItemQuery->where_statuses( $this->filters['statuses'] );

		$this->ItemQuery->where_keywords( $this->filters['keywords'], $this->filters['phrase'], $this->filters['exact'] );

		$this->ItemQuery->where_ID( $this->filters['post_ID'], $this->filters['post_title'] );

		$this->ItemQuery->where_datestart( $this->filters['ymdhms'], $this->filters['week'],
		                                   $this->filters['ymdhms_min'], $this->filters['ymdhms_max'],
		                                   $this->filters['ts_min'], $this->filters['ts_max'] );

		$this->ItemQuery->where_visibility( $this->filters['visibility_array'] );

		/**
		 * Restrict to an item type
		 */
		if( !empty( $this->filters['item_type'] ) )
		{
			$this->ItemQuery->where_and( 'post_ptyp_ID = '.$this->filters['item_type'] );
		}

		/*
		 * order by stuff:
		 */
		$order = $this->filters['order'];

		$orderby = str_replace( ' ', ',', $this->filters['orderby'] );
		$orderby_array = explode( ',', $orderby );

		// Format each order param with default colum names:
		$orderby_array = preg_replace( '#^(.+)$#', $this->Cache->dbprefix.'$1 '.$order, $orderby_array );

 		// Add a parameter to make sure there is no ambiguity in ordering on similar items:
		$orderby_array[] = $this->Cache->dbIDname.' '.$order;

		$order_by = implode( ', ', $orderby_array );


		$this->ItemQuery->order_by( $order_by );



		/*
		 * GET TOTAL ROW COUNT:
		 */
		if( $this->single_post )   // p or title
		{ // Single post: no paging required!
			$this->total_rows = 1;
			$this->total_pages = 1;
			$this->page = 1;
		}
		elseif( !empty($this->filters['ymdhms']) )
		{ // no restriction if we request a month... some permalinks may point to the archive!
			// echo 'ARCHIVE - no limits';
			// $this->total_rows = 1; // TODO: unknown, check...
			$this->total_pages = 1;
			$this->page = 1;
		}
		elseif( $this->filters['unit'] == 'posts' )
		{
			/*
			 * TODO: The result is incorrect when using AND on categories
			 * We would need to use a HAVING close and thyen COUNT, which would be a subquery
			 * This is nto compatible with mysql 3.23
			 * We need fallback code.
			 */
			$sql_count = '
				SELECT COUNT( DISTINCT '.$this->Cache->dbIDname.') '
					.$this->ItemQuery->get_from()
					.$this->ItemQuery->get_where();

			//echo $DB->format_query( $sql_count );

			parent::count_total_rows( $sql_count );
			//echo '<br />'.$this->total_rows;
		}
		elseif( $this->unit == 'days' )
		{ // We are going to limit to x days:
			// $this->total_rows = 1; // TODO: unknown, check...
			$this->total_pages = 1;
			$this->page = 1;
		}
		else
			debug_die( 'Unhandled LIMITING mode in ItemList:'.$this->unit.' (paged mode is obsolete)' );

			

		/*
		 * Paging limits:
		 */
		if( $this->single_post )   // p or title
		{ // Single post: no paging required!
		}
		elseif( !empty($this->filters['ymdhms']) )
		{ // no restriction if we request a month... some permalinks may point to the archive!
			// echo 'ARCHIVE - no limits';
		}
		elseif( $this->filters['unit'] == 'posts' )
		{
			// echo 'LIMIT POSTS ';
			$pgstrt = '';
			if( $this->page > 1 )
			{ // We have requested a specific page number
				$pgstrt = (intval($this->page) -1) * $this->limit. ', ';
			}
			$this->ItemQuery->LIMIT( $pgstrt.$this->limit );
		}
		elseif( $this->unit == 'days' )
		{ // We are going to limit to x days:
			// echo 'LIMIT DAYS ';
			if( empty( $this->filters['ymdhms_min'] ) )
			{ // We have no start date, we'll display the last x days:
				if( !empty($this->filters['keywords'])
					|| !empty($this->filters['cat_array'])
					|| !empty($this->filters['authors']) )
				{ // We are in DAYS mode but we can't restrict on these! (TODO: ?)
					$limits = '';
				}
				else
				{ // We are going to limit to LAST x days:
					$lastpostdate = $this->get_lastpostdate();
					$lastpostdate = mysql2date('Y-m-d 00:00:00',$lastpostdate);
					$lastpostdate = mysql2date('U',$lastpostdate);
					// go back x days
					$otherdate = date('Y-m-d H:i:s', ($lastpostdate - (($this->limit-1) * 86400)));
					$this->ItemQuery->WHERE_and( $this->Cache->dbprefix.'datestart > \''. $otherdate.'\'' );
				}
			}
			else
			{ // We have a start date, we'll display x days starting from that point:
				// $dstart_mysql has been calculated earlier

				// TODO: this is redundant with previous dstart processing:
				// Add trailing 0s: YYYYMMDDHHMMSS
				$dstart0 = $this->filters['ymdhms_min'].'00000000000000';

				$dstart_mysql = substr($dstart0,0,4).'-'.substr($dstart0,4,2).'-'.substr($dstart0,6,2).' '
												.substr($dstart0,8,2).':'.substr($dstart0,10,2).':'.substr($dstart0,12,2);
				$dstart_ts = mysql2timestamp( $dstart_mysql );
				// go forward x days
				$enddate_ts = date('Y-m-d H:i:s', ($dstart_ts + ($this->limit * 86400)));
				$this->ItemQuery->WHERE_and( $this->Cache->dbprefix.'datestart < \''. $enddate_ts.'\'' );
			}
		}
		else
			debug_die( 'Unhandled LIMITING mode in ItemList:'.$this->unit.' (paged mode is obsolete)' );


		// GET DATA ROWS:


  	// Results style orders:
		// $this->ItemQuery->ORDER_BY_prepend( $this->get_order_field_list() );


		// We are going to proceed in two steps (we simulate a subquery)
		// 1) we get the IDs we need
		// 2) we get all the other fields matching these IDs
		// This is more efficient than manipulating all fields at once.

		// Step 1:
		$step1_sql = 'SELECT DISTINCT '.$this->Cache->dbIDname
									.$this->ItemQuery->get_from()
									.$this->ItemQuery->get_where()
									.$this->ItemQuery->get_group_by()
									.$this->ItemQuery->get_order_by()
									.$this->ItemQuery->get_limit();

		// echo $DB->format_query( $step1_sql );

		// Get list of the IDs we need:
		$ID_list = $DB->get_list( $step1_sql, 0, 'Get ID list for ItemList2 (Main|Lastpostdate) Query' );

		// Step 2:
		$this->sql = 'SELECT *
			              FROM '.$this->Cache->dbtablename;
		if( !empty($ID_list) )
		{
			$this->sql .= ' WHERE '.$this->Cache->dbIDname.' IN ('.$ID_list.') '
										.$this->ItemQuery->get_order_by();
		}
		else
		{
			$this->sql .= ' WHERE 0';
		}

		//echo $DB->format_query( $this->sql );
    if (is_null($this->order_field_list))
      $this->order_field_list = '';  //smpdawg - This prevents the extra field name from being added to the ORDER BY clause that was happening on the 'Post List' and 'Tracker' tabs.

		parent::query( $this->sql, false, false );
	}


	/**
	 * Get date of the last post/item
	 */
	function get_lastpostdate()
	{
		global $localtimenow, $DB;


		if( empty( $this->filters ) )
		{	// Filters have no been set before, we'll use the default filterset:
			// echo ' Query:Setting default filterset ';
			$this->set_filters( $this->default_filters );
		}

		// GENERATE THE QUERY:

		// The SQL Query object:
		$lastpost_ItemQuery = & new ItemQuery( $this->Cache->dbtablename, $this->Cache->dbprefix, $this->Cache->dbIDname );


		/*
		 * filetring stuff:
		 */
		$lastpost_ItemQuery->where_chapter2( $this->Blog->ID, $this->filters['cat_array'], $this->filters['cat_modifier'] );

		$lastpost_ItemQuery->where_author( $this->filters['authors'] );

		$lastpost_ItemQuery->where_assignees( $this->filters['assignees'] );

		$lastpost_ItemQuery->where_statuses( $this->filters['statuses'] );

		$lastpost_ItemQuery->where_keywords( $this->filters['keywords'], $this->filters['phrase'], $this->filters['exact'] );

		$lastpost_ItemQuery->where_ID( $this->filters['post_ID'], $this->filters['post_title'] );

		$lastpost_ItemQuery->where_datestart( $this->filters['ymdhms'], $this->filters['week'],
		                                   $this->filters['ymdhms_min'], $this->filters['ymdhms_max'],
		                                   $this->filters['ts_min'], $this->filters['ts_max'] );

		$lastpost_ItemQuery->where_visibility( $this->filters['visibility_array'] );

		/**
		 * Restrict to an item type
		 * @todo method of ItemQuery
		 */
		if( !empty( $this->filters['item_type'] ) )
		{
			$lastpost_ItemQuery->where_and( 'post_ptyp_ID = '.$this->filters['item_type'] );
		}


		/*
		 * order by stuff:
		 * LAST POST FIRST!!! (That's the whole point!)
		 */
		$lastpost_ItemQuery->order_by( $this->Cache->dbprefix.'datestart DESC' );


		/*
		 * Paging limits:
		 * ONLY THE LAST POST!!!
		 */
		$lastpost_ItemQuery->LIMIT( '1' );


		// Select the datestart:
		$lastpost_ItemQuery->select( $this->Cache->dbprefix.'datestart' );


		$lastpostdate = $DB->get_var( $lastpost_ItemQuery->get(), 0, 0, 'Get last post date' );

		if( empty( $lastpostdate ) )
		{
			// echo 'we have no last item';
			$lastpostdate = date('Y-m-d H:i:s', $localtimenow);
		}

		// echo $lastpostdate;

		return $lastpostdate;
	}


	/**
	 * Generate a title for the current list, depending on its filtering params
	 *
	 * @todo cleanup some displays
	 * @todo implement HMS part of YMDHMS
	 *
	 * @return array
   */
  function get_filter_titles()
  {
		global $month, $post_statuses;


		if( empty( $this->filters ) )
		{	// Filters have no been set before, we'll use the default filterset:
			// echo ' setting default filterset ';
			$this->set_filters( $this->default_filters );
		}


  	$title_array = array();


  	if( $this->single_post )
		{	// We have requested a specific post:
			// Should be in first position
			$Item = & $this->get_by_idx( 0 );

			if( is_null($Item) )
			{
				$title_array[] = T_('Invalid request');
			}
			else
			{
				$title_array[] = T_('Post details').': '.$Item->get('title');
			}
			return $title_array;
		}


		// CATEGORIES:
  	if( !empty($this->filters['cat_array']) )
  	{ // We have requested specific categories...
			$cat_names = array();
			foreach( $this->filters['cat_array'] as $cat_ID )
			{
				if( ($my_cat = get_the_category_by_ID( $cat_ID, false ) ) !== false )
				{ // It is almost never meaningful to die over an invalid cat when generating title
					$cat_names[] = $my_cat['cat_name'];
				}
			}
			if( $this->filters['cat_modifier'] == '*' )
			{
				$cat_names_string = implode( ' + ', $cat_names );
			}
			else
			{
				$cat_names_string = implode( ', ', $cat_names );
			}
			if( !empty( $cat_names_string ) )
			{
				if( $this->filters['cat_modifier'] == '-' )
				{
					$cat_names_string = T_('All but ').' '.$cat_names_string;
					$title_array['cats'] = T_('Categories').': '.$cat_names_string;
				}
				else
				{
					if( count($this->filters['cat_array']) > 1 )
						$title_array['cats'] = T_('Categories').': '.$cat_names_string;
					else
						$title_array['cats'] = T_('Category').': '.$cat_names_string;
				}
			}
		}


		// ARCHIVE TIMESLOT:
		if( !empty($this->filters['ymdhms']) )
		{	// We have asked for a specific timeframe:

			$my_year = substr($this->filters['ymdhms'],0,4);

			if( strlen($this->filters['ymdhms']) > 4 )
			{ // We have requested a month too:
				$my_month = T_($month[substr($this->filters['ymdhms'],4,2)]);
			}
			else
			{
				$my_month = '';
			}

			// Requested a day?
			$my_day = substr($this->filters['ymdhms'],6,2);

			$arch = T_('Archives for').': '.$my_month.' '.$my_year;

			if( !empty( $my_day ) )
			{	// We also want to display a day
				$arch .= ", $my_day";
			}

			if( !empty($this->filters['week']) || ($this->filters['week'] === 0) ) // Note: week # can be 0
			{	// We also want to display a week number
				$arch .= ', '.T_('week').' '.$this->filters['week'];
			}

			$title_array['ymdhms'] = $arch;
		}


 		// KEYWORDS:
		if( !empty($this->filters['keywords']) )
		{
			$title_array['keywords'] = T_('Keyword(s)').': '.$this->filters['keywords'];
		}


		// AUTHORS:
		if( !empty($this->filters['authors']) )
		{
			$title_array[] = T_('Author(s)').': '.$this->filters['authors'];
		}


		// ASSIGNEES:
		if( !empty($this->filters['assignees']) )
		{
			if( $this->filters['assignees'] == '-' )
			{
				$title_array[] = T_('Not assigned');
			}
			else
			{
				$title_array[] = T_('Assigned to').': '.$this->filters['assignees'];
			}
		}


		// EXTRA STATUSES:
		if( !empty($this->filters['statuses']) )
		{
			if( $this->filters['statuses'] == '-' )
			{
				$title_array[] = T_('Without status');
			}
			else
			{
				$title_array[] = T_('Status(es)').': '.$this->filters['statuses'];
			}
		}


		// SHOW STATUSES
		if( count( $this->filters['visibility_array'] ) < 5 ) // TEMP
		{
			$status_titles = array();
			foreach( $this->filters['visibility_array'] as $status )
			{
				$status_titles[] = T_( $post_statuses[$status] );
			}
			$title_array[] = T_('Visibility').': '.implode( ', ', $status_titles );
		}


		// START AT
		if( !empty($this->filters['ymdhms_min'] ) )
		{
			$title_array['ymdhms_min'] = T_('Start at').': '.$this->filters['ymdhms_min'] ;
		}
		if( !empty($this->filters['ts_min'] ) )
		{
			if( $this->filters['ts_min'] == 'now' )
			{
				$title_array['ts_min'] = T_('Hide past');
			}
			else
			{
				$title_array['ts_min'] = T_('Start at').': '.$this->filters['ts_min'];
			}
		}


		// STOP AT
		if( !empty($this->filters['ymdhms_max'] ) )
		{
			$title_array['ymdhms_max'] = T_('Stop at').': '.$this->filters['ymdhms_max'];
		}
		if( !empty($this->filters['ts_max'] ) )
		{
			if( $this->filters['ts_max'] == 'now' )
			{
				$title_array['ts_max'] = T_('Hide future');
			}
			else
			{
				$title_array['ts_max'] = T_('Stop at').': '.$this->filters['ts_max'];
			}
		}


		// LIMIT TO
		if( $this->single_post )   // p or title
		{ // Single post: no paging required!
		}
		elseif( !empty($this->filters['ymdhms']) )
		{ // no restriction if we request a month... some permalinks may point to the archive!
		}
		elseif( $this->filters['unit'] == 'posts' )
		{ // We're going to page, so there's no real limit here...
		}
		elseif( $this->unit == 'days' )
		{ // We are going to limit to x days:
			// echo 'LIMIT DAYS ';
			if( empty( $this->filters['ymdhms_min'] ) )
			{ // We have no start date, we'll display the last x days:
				if( !empty($this->filters['keywords'])
					|| !empty($this->filters['cat_array'])
					|| !empty($this->filters['authors']) )
				{ // We are in DAYS mode but we can't restrict on these! (TODO: ?)
				}
				else
				{ // We are going to limit to LAST x days:
					// TODO: rename 'posts' to 'limit'
					$title_array['posts'] = sprintf( T_('Limit to %d last days'), $this->limit );
				}
			}
			else
			{ // We have a start date, we'll display x days starting from that point:
				$title_array['posts'] = sprintf( T_('Limit to %d days'), $this->limit );
			}
		}
		else
			debug_die( 'Unhandled LIMITING mode in ItemList:'.$this->unit.' (paged mode is obsolete)' );


		return $title_array;
	}


	/**
	 * return total number of posts
	 *
	 * This is basically just a stub for backward compatibility
	 *
	 * @deprecated
	 */
	function get_total_num_posts()
	{
		return $this->total_rows;
	}


	/**
	 * This is basically just a stub for backward compatibility
	 *
	 * @deprecated
	 */
	function & get_item()
	{
		$Item = & parent::get_next();
		return $Item;
	}


  /**
   * Returns values needed to make sort links for a given column
   *
   * Returns an array containing the following values:
   *  - current_order : 'ASC', 'DESC' or ''
   *  - order_asc : url needed to order in ascending order
   *  - order_desc
   *  - order_toggle : url needed to toggle sort order
   *
   * @param integer column to sort
   * @return array
   */
	function get_col_sort_values( $col_idx )
	{
		$col_order_fields = $this->cols[$col_idx]['order'];

		// pre_dump( $col_order_fields, $this->filters['orderby'], $this->filters['order'] );

		// Current order:
		if( $this->filters['orderby'] == $col_order_fields )
		{
			$col_sort_values['current_order'] = $this->filters['order'];
		}
		else
		{
			$col_sort_values['current_order'] = '';
		}


		// Generate sort values to use for sorting on the current column:
		$col_sort_values['order_asc'] = regenerate_url( array($this->param_prefix.'order',$this->param_prefix.'orderby'),
																			$this->param_prefix.'order=ASC&amp;'.$this->param_prefix.'orderby='.$col_order_fields );
		$col_sort_values['order_desc'] = regenerate_url(  array($this->param_prefix.'order',$this->param_prefix.'orderby'),
																			$this->param_prefix.'order=DESC&amp;'.$this->param_prefix.'orderby='.$col_order_fields );

		if( !$col_sort_values['current_order'] && isset( $this->cols[$col_idx]['default_dir'] ) )
		{	// There is no current order on this column and a default order direction is set for it
			// So set a default order direction for it

			if( $this->cols[$col_idx]['default_dir'] == 'A' )
			{	// The default order direction is A, so set its toogle  order to the order_asc
				$col_sort_values['order_toggle'] = $col_sort_values['order_asc'];
			}
			else
			{ // The default order direction is A, so set its toogle order to the order_desc
				$col_sort_values['order_toggle'] = $col_sort_values['order_desc'];
			}
		}
		elseif( $col_sort_values['current_order'] == 'ASC' )
		{	// There is an ASC current order on this column, so set its toogle order to the order_desc
			$col_sort_values['order_toggle'] = $col_sort_values['order_desc'];
		}
		else
		{ // There is a DESC or NO current order on this column,  so set its toogle order to the order_asc
			$col_sort_values['order_toggle'] = $col_sort_values['order_asc'];
		}

		// pre_dump( $col_sort_values );

		return $col_sort_values;
	}


  /**
   * Get the adverstised start date (does not include timestamp_min)
   *
   * Note: there is a priority order in the params to determine the start date:
   *  -dstart
   *  -week + m
   *  -m
   *  -dstop - x days
   * @see ItemQuery::where_datestart()
   */
	function get_advertised_start_date()
	{
		if( $this->getting_adv_start_date )
		{	// We would be entering an infinite loop, stop now:
			// We cannot determine a start date, save an empty string (to differentiate from NULL)
			$this->advertised_start_date = '';

			// Reset anti infinite loop:
			$this->getting_adv_start_date = false;

			return $this->advertised_start_date;
		}

		// Anti infinite loop:
		$this->getting_adv_start_date = true;


		if( is_null( $this->advertised_start_date ) )
		{	// We haven't determined the start date yet:

			if( !empty( $this->filters['ymdhms_min'] ) )
			{	// We have requested start date (8 digits)
				$m = $this->filters['ymdhms_min'];
				$this->advertised_start_date = mktime( 0, 0, 0, substr($m,4,2), substr($m,6,2), substr($m,0,4) );
			}
			elseif( !is_null($this->filters['week']) 		// note: 0 is a valid week number
						&& !empty( $this->filters['ymdhms'] ) )
			{	// we want to restrict on a specific week
				$this->advertised_start_date = get_start_date_for_week( substr($this->filters['ymdhms'],0,4), $this->filters['week'], locale_startofweek() );
			}
			elseif( strlen( $this->filters['ymdhms'] ) >= 4 )
			{	// We have requested an interval
				$m = $this->filters['ymdhms'].'0101';
				$this->advertised_start_date = mktime( 0, 0, 0, substr($m,4,2), substr($m,6,2), substr($m,0,4) );
			}
			elseif( $this->filters['unit'] == 'days'
						&& ($stop_date = $this->get_advertised_stop_date()) != '' )
			{	// We want to restrict on a specific number of days after the start date:
				$this->advertised_start_date = $stop_date - ($this->limit-1) * 86400;
			}
			else
			{	// We cannot determine a start date, save an empty string (to differentiate from NULL)
				$this->advertised_start_date = '';
			}

		}

		// Reset anti infinite loop:
		$this->getting_adv_start_date = false;

		return $this->advertised_start_date;
	}


  /**
   * Get the adverstised stop date (does not include timestamp_max)
   *
   * Note: there is a priority order in the params to determine the stop date.
   *  -dstop
   *  -week + m
   *  -m
   *  -dstart + x days
   */
	function get_advertised_stop_date()
	{
		if( $this->getting_adv_stop_date )
		{	// We would be entering an infinite loop, stop now:
			// We cannot determine a stop date, save an empty string (to differentiate from NULL)
			$this->advertised_stop_date = '';

			// Reset anti infinite loop:
			$this->getting_adv_stop_date = false;

			return $this->advertised_stop_date;
		}

		// Anti infinite loop:
		$this->getting_adv_stop_date = true;


		if( is_null( $this->advertised_stop_date ) )
		{	// We haven't determined the stop date yet:

			if( !empty( $this->filters['ymdhms_max'] ) )
			{	// We have requested an end date (8 digits)
				$m = $this->filters['ymdhms_max'];
				$this->advertised_stop_date = mktime( 0, 0, 0, substr($m,4,2), substr($m,6,2), substr($m,0,4) );
			}
			elseif( !is_null($this->filters['week']) 		// note: 0 is a valid week number
						&& !empty( $this->filters['ymdhms'] ) )
			{	// we want to restrict on a specific week
				$this->advertised_stop_date = get_start_date_for_week( substr($this->filters['ymdhms'],0,4), $this->filters['week'], locale_startofweek() );
				$this->advertised_stop_date += 518400; // + 6 days
			}
			elseif( !empty( $this->filters['ymdhms'] ) )
			{	// We want to restrict on an interval:
				if( strlen( $this->filters['ymdhms'] ) >= 8 )
				{	// We have requested a day interval
					$m = $this->filters['ymdhms'];
					$this->advertised_stop_date = mktime( 0, 0, 0, substr($m,4,2), substr($m,6,2), substr($m,0,4) );
				}
				elseif( strlen( $this->filters['ymdhms'] ) == 6 )
				{ // We want to go to the end of the month:
					$m = $this->filters['ymdhms'];
					$this->advertised_stop_date = mktime( 0, 0, 0, substr($m,4,2)+1, 0, substr($m,0,4) ); // 0th day of next mont = last day of month
				}
				elseif( strlen( $this->filters['ymdhms'] ) == 4 )
				{ // We want to go to the end of the year:
					$m = $this->filters['ymdhms'];
					$this->advertised_stop_date = mktime( 0, 0, 0, 12, 31, substr($m,0,4) );
				}
			}
			elseif( $this->filters['unit'] == 'days'
						&& ($start_date = $this->get_advertised_start_date()) != '' )
			{	// We want to restrict on a specific number of days after the start date:
				$this->advertised_stop_date = $start_date + ($this->limit-1) * 86400;
			}
			else
			{	// We cannot determine a stop date, save an empty string (to differentiate from NULL)
				$this->advertised_stop_date = '';
			}

		}

		// Reset anti infinite loop:
		$this->getting_adv_stop_date = false;

		return $this->advertised_stop_date;
	}


  /**
   * Make sure date displaying starts at the beginning of the current filter interval
   *
   * Note: we're talking about strict dates (no times involved)
   */
  function set_start_date( )
	{
		$start_date = $this->get_advertised_start_date();

		if( !empty( $start_date ) )
		{	// Memorize the last displayed as the day BEFORE the one we're going to display
			//echo ' start at='.date( locale_datefmt(), $start_date );
			$this->last_displayed_date = $start_date - 86400;
		}
	}


	/**
	 * Template function: display potentially remaining empty days until the end of the filter interval
	 *
	 * @param string string to display before the date (if changed)
	 * @param string string to display after the date (if changed)
	 * @param string date/time format: leave empty to use locale default time format
	 */
	function dates_to_end( $before_empty_day = '<h2>', $after_empty_day = '</h2>', $format = '' )
	{
		$stop_date = $this->get_advertised_stop_date();

		if( !is_null( $stop_date ) )
		{	// There is a stop date, we want to display days:
			//echo ' - stop at='.date( locale_datefmt(), $stop_date );
			//echo ' - last displayed='.date( locale_datefmt(), $this->last_displayed_date );
			while( $this->last_displayed_date < $stop_date )
			{
				$this->last_displayed_date += 86400;	// Add one day's worth of seconds
				echo date_sprintf( $before_empty_day, $this->last_displayed_date )
						.date_i18n( $format, $this->last_displayed_date )
						.date_sprintf( $after_empty_day, $this->last_displayed_date );
			}
		}
	}


	/**
	 * Template function: Display the date if it has changed since last call
	 *
	 * Optionally also displays empty dates in between.
	 *
	 * @param string string to display before the date (if changed)
	 * @param string string to display after the date (if changed)
	 * @param string date/time format: leave empty to use locale default time format
	 * @param string|NULL string to display before any empty dates. Set to NULL in order not to display empty dates.
	 * @param string|NULL string to display after any empty dates.
	 */
	function date_if_changed( $before = '<h2>', $after = '</h2>', $format = '',
														$before_empty_day = NULL, $after_empty_day = NULL )
	{
		if( empty($format) )
		{	// No format specified, use default locale format:
			$format =	locale_datefmt();
		}

		// Get a timestamp for the date WITHOUT the time:
		$current_item_date = mysql2datestamp( $this->current_Obj->issue_date );

		if( $current_item_date != $this->last_displayed_date )
		{	// Date has changed...

			if( !empty($before_empty_day) && !empty($this->last_displayed_date) )
			{	// We want to display ALL dates from the previous to the current:
				while( $this->last_displayed_date < $current_item_date-86400 )
				{
					$this->last_displayed_date += 86400;	// Add one day's worth of seconds
					echo date_sprintf( $before_empty_day, $this->last_displayed_date )
							.date_i18n( $format, $this->last_displayed_date )
							.date_sprintf( $after_empty_day, $this->last_displayed_date );
				}
			}

			// Display the new current date:
			echo date_sprintf( $before, $current_item_date )
					.date_i18n( $format, $current_item_date )
					.date_sprintf( $after, $current_item_date );
			$this->last_displayed_date = $current_item_date;
		}
	}
}

/*
 * $Log$
 * Revision 1.11  2006/06/26 01:29:55  smpdawg
 * Fixed error when a user picked the Browse/Post List or Browse/Tracker tabs in the admin area.
 *
 * Revision 1.10  2006/06/19 20:59:37  fplanque
 * noone should die anonymously...
 *
 * Revision 1.9  2006/06/19 16:53:58  fplanque
 * better filter presets
 *
 * Revision 1.8  2006/06/13 21:49:15  blueyed
 * Merged from 1.8 branch
 *
 * Revision 1.7.2.2  2006/06/13 18:27:50  fplanque
 * fixes
 *
 * Revision 1.7.2.1  2006/06/12 20:00:38  fplanque
 * one too many massive syncs...
 *
 * Revision 1.7  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.6  2006/04/14 19:25:32  fplanque
 * evocore merge with work app
 *
 * Revision 1.5  2006/04/06 21:11:53  fplanque
 * Fixed deadlock issue.
 * --
 * Styles: there are default markups in the template functions in order to allow for easy understanding
 * of how they work. Adding skin CSS styles into there is beyond the purpose.
 * The params are MEANT to be USED in skins. Please do.
 * (This may apply to other functions in the app which have too much default styling).
 *
 * Revision 1.4  2006/04/04 21:48:36  blueyed
 * Add "bItemListDate" class to default, to allow styling
 *
 * Revision 1.3  2006/03/13 19:44:35  fplanque
 * no message
 *
 * Revision 1.2  2006/03/12 23:08:59  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:58  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.17  2006/02/15 04:07:16  blueyed
 * minor merge
 *
 * Revision 1.16  2006/02/14 21:56:51  fplanque
 * implemented missing get_lastpostdate()
 *
 * Revision 1.15  2006/02/03 21:58:05  fplanque
 * Too many merges, too little time. I can hardly keep up. I'll try to check/debug/fine tune next week...
 *
 * Revision 1.14  2006/01/11 18:57:05  fplanque
 * bugfix
 *
 * Revision 1.13  2006/01/10 21:00:09  fplanque
 * minor / fixed internal sync issues @ progidistri
 *
 * Revision 1.12  2006/01/09 17:21:06  fplanque
 * no message
 *
 * Revision 1.11  2006/01/04 20:34:52  fplanque
 * allow filtering on extra statuses
 *
 * Revision 1.10  2006/01/04 19:18:15  fplanque
 * allow filtering on assignees
 *
 * Revision 1.9  2006/01/04 19:07:48  fplanque
 * allow filtering on assignees
 *
 * Revision 1.8  2006/01/04 15:02:10  fplanque
 * better filtering design
 *
 * Revision 1.7  2005/12/30 20:13:40  fplanque
 * UI changes mostly (need to double check sync)
 *
 * Revision 1.6  2005/12/22 15:53:37  fplanque
 * Splitted display and display init
 *
 * Revision 1.5  2005/12/21 20:42:28  fplanque
 * filterset saving & restore if no filters are set.
 * Note: reset all is broken.
 * poststart/postend navigation in experimental tab is broken too.
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