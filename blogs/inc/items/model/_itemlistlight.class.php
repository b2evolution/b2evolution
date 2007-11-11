<?php
/**
 * This file implements the ItemListLight class.
 *
 * This object handles item/post/article lists WITHOUT FULL FUNCTIONNALITY
 * but with a LOWER MEMORY FOOTPRINT.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class('_core/model/dataobjects/_dataobjectcache.class.php');
load_class('_core/model/dataobjects/_dataobjectlist2.class.php');
load_class('items/model/_item.class.php');
load_funcs('items/model/_item.funcs.php');

/**
 * Item List Class LIGHT
 *
 * Contrary to ItemList2, we only do 1 query here and we extract only a few selected params.
 * Basically all we want is being able to generate permalinks.
 *
 * @package evocore
 */
class ItemListLight extends DataObjectList2
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

	var $group_by_cat = 0;


	/**
	 * Constructor
	 *
	 * @todo  add param for saved session filter set
	 *
	 * @param Blog
	 * @param mixed Default filter set: Do not show posts before this timestamp, can be 'now'
	 * @param mixed Default filter set: Do not show posts after this timestamp, can be 'now'
	 * @param integer|NULL Limit
	 * @param string name of cache to be used (for table prefix info)
	 * @param string prefix to differentiate page/order params when multiple Results appear one same page
	 * @param array restrictions for itemlist (position, contact, firm, ...) key: restriction name, value: ID of the restriction
	 */
	function ItemListLight(
			& $Blog,
			$timestamp_min = NULL,       // Do not show posts before this timestamp
			$timestamp_max = NULL,   		 // Do not show posts after this timestamp
			$limit = 20,
			$cache_name = 'ItemCacheLight',	 // name of cache to be used (for table prefix info)
			$param_prefix = '',
			$filterset_name = '',				// Name to be used when saving the filterset (leave empty to use default for collection)
			$restrict_to = array()			// Restrict the item list to a position, or contact, firm..... /* not used yet(?) */
		)
	{
		global $Settings;

		// Call parent constructor:
		parent::DataObjectList2( get_Cache($cache_name), $limit, $param_prefix, NULL );

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
				'cat_focus' => 'wide',					// Search in extra categories, not just main cat
				'tags' => NULL,
				'authors' => NULL,
				'assignees' => NULL,
				'author_assignee' => NULL,
				'lc' => 'all',									// Filter on requested locale
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
				'types' => '-1000',							// All types except pages
				'visibility_array' => array( 'published', 'protected', 'private' ),
				'orderby' => $this->Blog->get_setting('orderby'),
				'order' => $this->Blog->get_setting('orderdir'),
				'unit' => $this->Blog->get_setting('what_to_show'),
				'posts' => $this->limit,
				'page' => 1,
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
	 * @param boolean
	 */
	function set_filters( $filters, $memorize = true )
	{
		if( !empty( $filters ) )
		{ // Activate the filterset (fallback to default filter when a value is not set):
			$this->filters = array_merge( $this->default_filters, $filters );
		}

		// Activate preset filters if necessary:
		$this->activate_preset_filters();

		// Funky oldstyle params:
		$this->limit = $this->filters['posts']; // for compatibility with parent class
		$this->page = $this->filters['page'];


		if( $memorize )
		{	// set back the GLOBALS !!! needed for regenerate_url() :

			/*
			 * Selected filter preset:
			 */
			memorize_param( $this->param_prefix.'filter_preset', 'string', $this->default_filters['filter_preset'], $this->filters['filter_preset'] );  // List of authors to restrict to


			/*
			 * Blog & Chapters/categories restrictions:
			 */
			// Get chapters/categories (and compile those values right away)
			memorize_param( 'cat', '/^[*\-]?([0-9]+(,[0-9]+)*)?$/', $this->default_filters['cat_modifier'], $this->filters['cat_modifier'] );  // List of authors to restrict to
			memorize_param( 'catsel', 'array', $this->default_filters['cat_array'], $this->filters['cat_array'] );
			memorize_param( $this->param_prefix.'cat_focus', 'string', $this->default_filters['cat_focus'], $this->filters['cat_focus'] );  // Categories to search on
			// TEMP until we get this straight:
			// fp> this would only be used for the categories widget and setting it here overwtrites the interesting values when a post list widget is tirggered
			// fp> if we need it here we want to use a $set_globals params to this function
			// global $cat_array, $cat_modifier;
			// $cat_array = $this->default_filters['cat_array'];
			// $cat_modifier = $this->default_filters['cat_modifier'];


			/*
			 * Restrict to selected tags:
			 */
			memorize_param( $this->param_prefix.'tags', 'string', $this->default_filters['tags'], $this->filters['tags'] );


			/*
			 * Restrict to selected authors:
			 */
			memorize_param( $this->param_prefix.'author', 'string', $this->default_filters['authors'], $this->filters['authors'] );  // List of authors to restrict to

			/*
			 * Restrict to selected assignees:
			 */
			memorize_param( $this->param_prefix.'assgn', 'string', $this->default_filters['assignees'], $this->filters['assignees'] );  // List of assignees to restrict to

			/*
			 * Restrict to selected author OR assignee:
			 */
			memorize_param( $this->param_prefix.'author_assignee', 'string', $this->default_filters['author_assignee'], $this->filters['author_assignee'] );

			/*
			 * Restrict to selected locale:
			 */
			memorize_param( $this->param_prefix.'lc', 'string', $this->default_filters['lc'], $this->filters['lc'] );  // Locale to restrict to

			/*
			 * Restrict to selected statuses:
			 */
			memorize_param( $this->param_prefix.'status', 'string', $this->default_filters['statuses'], $this->filters['statuses'] );  // List of statuses to restrict to

			/*
			 * Restrict to selected item type:
			 */
			memorize_param( $this->param_prefix.'types', 'integer', $this->default_filters['types'], $this->filters['types'] );  // List of item types to restrict to

			/*
			 * Restrict by keywords
			 */
			memorize_param( $this->param_prefix.'s', 'string', $this->default_filters['keywords'], $this->filters['keywords'] );			 // Search string
			memorize_param( $this->param_prefix.'sentence', 'string', $this->default_filters['phrase'], $this->filters['phrase'] ); // Search for sentence or for words
			memorize_param( $this->param_prefix.'exact', 'integer', $this->default_filters['exact'], $this->filters['exact'] );     // Require exact match of title or contents

			/*
			 * Specific Item selection?
			 */
			memorize_param( $this->param_prefix.'m', 'integer', $this->default_filters['ymdhms'], $this->filters['ymdhms'] );          // YearMonth(Day) to display
			memorize_param( $this->param_prefix.'w', 'integer', $this->default_filters['week'], $this->filters['week'] );            // Week number
			memorize_param( $this->param_prefix.'dstart', 'integer', $this->default_filters['ymdhms_min'], $this->filters['ymdhms_min'] ); // YearMonth(Day) to start at
			memorize_param( $this->param_prefix.'dstop', 'integer', $this->default_filters['ymdhms_max'], $this->filters['ymdhms_max'] ); // YearMonth(Day) to start at

			// TODO: show_past/future should probably be wired on dstart/dstop instead on timestamps -> get timestamps out of filter perimeter
			if( is_null($this->default_filters['ts_min'])
				&& is_null($this->default_filters['ts_max'] ) )
			{	// We have not set a strict default -> we allow overridding:
				memorize_param( $this->param_prefix.'show_past', 'integer', 0, ($this->filters['ts_min'] == 'now') ? 0 : 1 );
				memorize_param( $this->param_prefix.'show_future', 'integer', 0, ($this->filters['ts_max'] == 'now') ? 0 : 1 );
			}

			/*
			 * Restrict to the statuses we want to show:
			 */
			// Note: oftentimes, $show_statuses will have been preset to a more restrictive set of values
			memorize_param( $this->param_prefix.'show_statuses', 'array', $this->default_filters['visibility_array'], $this->filters['visibility_array'] );	// Array of sharings to restrict to

			/*
			 * OLD STYLE orders:
			 */
			memorize_param( $this->param_prefix.'order', 'string', $this->default_filters['order'], $this->filters['order'] );   		// ASC or DESC
			memorize_param( $this->param_prefix.'orderby', 'string', $this->default_filters['orderby'], $this->filters['orderby'] );  // list of fields to order by (TODO: change that crap)

			/*
			 * Paging limits:
			 */
			memorize_param( $this->param_prefix.'unit', 'string', $this->default_filters['unit'], $this->filters['unit'] );    		// list unit: 'posts' or 'days'

			memorize_param( $this->param_prefix.'posts', 'integer', $this->default_filters['posts'], $this->filters['posts'] ); 			// # of units to display on the page

			// 'paged'
			memorize_param( $this->page_param, 'integer', 1, $this->filters['page'] );      // List page number in paged display
		}
	}


	/**
	 * Init filter params from Request params
	 *
	 * @param boolean do we want to use saved filters ?
	 * @return boolean true if we could apply a filterset based on Request params (either explicit or reloaded)
	 */
	function load_from_Request( $use_filters = true )
	{
		// fp> 2007-09-23> Let's always start with clean filters.
		// If we don't do this, then $this->filters will end up with filters in a different order than $this->default_filters.
		// And orders are different, then $this->is_filtered() will say it's filtered even if it's not.
		$this->filters = $this->default_filters;

		if( $use_filters )
		{
			// Do we want to restore filters or do we want to create a new filterset
			$filter_action = param( $this->param_prefix.'filter', 'string', 'save' );
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

					// Memorize global variables:
					$this->set_filters( array(), true );

					// We have applied no filterset:
					return false;
					/* BREAK */
			}

			/**
			 * Filter preset
			 */
			$this->filters['filter_preset'] = param( $this->param_prefix.'filter_preset', 'string', $this->default_filters['filter_preset'], true );

			// Activate preset default filters if necessary:
			$this->activate_preset_filters();
		}


		// fp> TODO: param( 'loc', 'string', '', true );							// Locale of the posts (all by default)


		/*
		 * Blog & Chapters/categories restrictions:
		 */
		// Get chapters/categories (and compile those values right away)
		param_compile_cat_array( /* TODO: check $this->Blog->ID == 1 ? 0 :*/ $this->Blog->ID,
								$this->default_filters['cat_modifier'], $this->default_filters['cat_array'] );

		$this->filters['cat_array'] = get_param( 'cat_array' );
		$this->filters['cat_modifier'] = get_param( 'cat_modifier' );

		$this->filters['cat_focus'] = param( $this->param_prefix.'cat_focus', 'string', $this->default_filters['cat_focus'], true );


		/*
		 * Restrict to selected tags:
		 */
		$this->filters['tags'] = param( $this->param_prefix.'tag', 'string', $this->default_filters['tags'], true );


		/*
		 * Restrict to selected authors:
		 */
		$this->filters['authors'] = param( $this->param_prefix.'author', '/^-?[0-9]+(,[0-9]+)*$/', $this->default_filters['authors'], true );      // List of authors to restrict to


		/*
		 * Restrict to selected assignees:
		 */
		$this->filters['assignees'] = param( $this->param_prefix.'assgn', '/^(-|-[0-9]+|[0-9]+)(,[0-9]+)*$/', $this->default_filters['assignees'], true );      // List of assignees to restrict to


		/*
		 * Restrict to selected author or assignee:
		 */
		$this->filters['author_assignee'] = param( $this->param_prefix.'author_assignee', '/^[0-9]+$/', $this->default_filters['author_assignee'], true );


		/*
		 * Restrict to selected locale:
		 */
		$this->filters['lc'] = param( $this->param_prefix.'lc', 'string', $this->default_filters['lc'], true );


		/*
		 * Restrict to selected statuses:
		 */
		$this->filters['statuses'] = param( $this->param_prefix.'status', '/^(-|-[0-9]+|[0-9]+)(,[0-9]+)*$/', $this->default_filters['statuses'], true );      // List of statuses to restrict to

		/*
		 * Restrict to selected types:
		 */
		$this->filters['types'] = param( $this->param_prefix.'types', '/^(-|-[0-9]+|[0-9]+)(,[0-9]+)*$/', $this->default_filters['types'], true );      // List of types to restrict to


		/*
		 * Restrict by keywords
		 */
		$this->filters['keywords'] = param( $this->param_prefix.'s', 'string', $this->default_filters['keywords'], true );         // Search string
		$this->filters['phrase'] = param( $this->param_prefix.'sentence', 'string', $this->default_filters['phrase'], true ); 		// Search for sentence or for words
		$this->filters['exact'] = param( $this->param_prefix.'exact', 'integer', $this->default_filters['exact'], true );        // Require exact match of title or contents


		/*
		 * Specific Item selection?
		 */
		$this->filters['post_ID'] = param( $this->param_prefix.'p', 'integer', $this->default_filters['post_ID'] );          // Specific post number to display
		$this->filters['post_title'] = param( $this->param_prefix.'title', 'string', $this->default_filters['post_title'] );	  // urtitle of post to display

		$this->single_post = !empty($this->filters['post_ID']) || !empty($this->filters['post_title']);


		/*
		 * If a timeframe is specified in the querystring, restrict to that timeframe:
		 */
		$this->filters['ymdhms'] = param( $this->param_prefix.'m', 'integer', $this->default_filters['ymdhms'], true );          // YearMonth(Day) to display
		$this->filters['week'] = param( $this->param_prefix.'w', 'integer', $this->default_filters['week'], true );            // Week number

		$this->filters['ymdhms_min'] = param_compact_date( $this->param_prefix.'dstart', $this->default_filters['ymdhms_min'], true, T_( 'Invalid date' ) ); // YearMonth(Day) to start at
		$this->filters['ymdhms_max'] = param_compact_date( $this->param_prefix.'dstop', $this->default_filters['ymdhms_max'], true, T_( 'Invalid date' ) ); // YearMonth(Day) to stop at


		// TODO: show_past/future should probably be wired on dstart/dstop instead on timestamps -> get timestamps out of filter perimeter
		// So far, these act as SILENT filters. They will not advertise their filtering in titles etc.
		$this->filters['ts_min'] = $this->default_filters['ts_min'];
		$this->filters['ts_max'] = $this->default_filters['ts_max'];
		if( is_null($this->default_filters['ts_min'])
			&& is_null($this->default_filters['ts_max'] ) )
		{	// We have not set a strict default -> we allow overridding:
			$show_past = param( $this->param_prefix.'show_past', 'integer', 0, true );
			$show_future = param( $this->param_prefix.'show_future', 'integer', 0, true );
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
		$this->filters['visibility_array'] = param( $this->param_prefix.'show_statuses', 'array', $this->default_filters['visibility_array']
						, true, false, true, false );	// Array of sharings to restrict to

		/*
		 * Ordering:
		 */
		$this->filters['order'] = param( $this->param_prefix.'order', '/^(ASC|asc|DESC|desc)$/', $this->default_filters['order'], true );		// ASC or DESC
		$this->filters['orderby'] = param( $this->param_prefix.'orderby', '/^([A-Za-z0-9_]+([ ,][A-Za-z0-9_]+)*)?$/', $this->default_filters['orderby'], true );   // list of fields to order by (TODO: change that crap)

		/*
		 * Paging limits:
		 */
		$this->filters['unit'] = param( $this->param_prefix.'unit', 'string', $this->default_filters['unit'], true );    		// list unit: 'posts' or 'days'

		$this->filters['posts'] = param( $this->param_prefix.'posts', 'integer', $this->default_filters['posts'], true ); 			// # of units to display on the page
		$this->limit = $this->filters['posts']; // for compatibility with parent class

		// 'paged'
		$this->filters['page'] = param( $this->page_param, 'integer', 1, true );      // List page number in paged display
		$this->page = $this->filters['page'];

		if( param_errors_detected() )
		{
			return false;
		}

		if( $this->single_post )
		{	// We have requested a specific post
			// Do not attempt to save or load any filterset:
			return true;
		}

		//echo ' Got filters from URL?:'.($this->is_filtered() ? 'YES' : 'NO');
		//pre_dump( $this->default_filters );
		//pre_dump( $this->filters );

		if( $use_filters && $filter_action == 'save' )
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

		global $Debuglog;

		$filters = $Session->get( $this->filterset_name );

		/*
		fp> 2007-09-26> even if there are no filters, we need to "set" them in order to set global variables like $show_statuses
		if( empty($filters) )
		{ // We have no saved filters:
			return false;
		}
		*/

		if( empty($filters) )
		{ // set_filters() expects array
			$filters = array();
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
	function query_init()
	{
		global $current_User;

		if( empty( $this->filters ) )
		{	// Filters have not been set before, we'll use the default filterset:
			// If there is a preset filter, we need to activate its specific defaults:
			$this->filters['filter_preset'] = param( $this->param_prefix.'filter_preset', 'string', $this->default_filters['filter_preset'], true );
			$this->activate_preset_filters();

			// Use the default filters:
			$this->set_filters( $this->default_filters );
		}


		// echo '<br />ItemListLight query';
		//pre_dump( $this->filters );

		// GENERATE THE QUERY:

		/*
		 * filtering stuff:
		 */
		$this->ItemQuery->where_chapter2( $this->Blog, $this->filters['cat_array'], $this->filters['cat_modifier'],
																			$this->filters['cat_focus'] );
		$this->ItemQuery->where_tags( $this->filters['tags'] );
		$this->ItemQuery->where_author( $this->filters['authors'] );
		$this->ItemQuery->where_assignees( $this->filters['assignees'] );
		$this->ItemQuery->where_author_assignee( $this->filters['author_assignee'] );
		$this->ItemQuery->where_locale( $this->filters['lc'] );
		$this->ItemQuery->where_statuses( $this->filters['statuses'] );
		$this->ItemQuery->where_types( $this->filters['types'] );
		$this->ItemQuery->where_keywords( $this->filters['keywords'], $this->filters['phrase'], $this->filters['exact'] );
		$this->ItemQuery->where_ID( $this->filters['post_ID'], $this->filters['post_title'] );
		$this->ItemQuery->where_datestart( $this->filters['ymdhms'], $this->filters['week'],
		                                   $this->filters['ymdhms_min'], $this->filters['ymdhms_max'],
		                                   $this->filters['ts_min'], $this->filters['ts_max'] );
		$this->ItemQuery->where_visibility( $this->filters['visibility_array'] );

		/*
		 * ORDER BY stuff:
		 */
		$order = $this->filters['order'];

		$orderby = str_replace( ' ', ',', $this->filters['orderby'] );
		$orderby_array = explode( ',', $orderby );

		// Format each order param with default column names:
		$orderby_array = preg_replace( '#^(.+)$#', $this->Cache->dbprefix.'$1 '.$order, $orderby_array );
		// walter>fp> $order_cols_to_select = $orderby_array;

		// Add an ID parameter to make sure there is no ambiguity in ordering on similar items:
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
		elseif( !empty($this->filters['ymdhms']) // no restriction if we request a month... some permalinks may point to the archive!
		  || $this->filters['unit'] == 'days'    // We are going to limit to x days: no limit
		  || $this->filters['unit'] == 'all' )	 // We want ALL results!
		{
			$this->total_rows = NULL; // unknown!
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
		else
		{
			debug_die( 'Unhandled LIMITING mode in ItemList:'.$this->filters['unit'].' (paged mode is obsolete)' );
		}


		/*
		 * Paging LIMITs:
		 */
		if( $this->single_post )   // p or title
		{ // Single post: no paging required!
		}
		elseif( !empty($this->filters['ymdhms']) )
		{ // no restriction if we request a month... some permalinks may point to the archive!
			// echo 'ARCHIVE - no limits';
		}
		elseif( $this->filters['unit'] == 'all' )
		{	// We want ALL results!
		}
		elseif( $this->filters['unit'] == 'posts' )
		{
			// TODO: dh> check if $limit is NULL!? - though it should not arrive at $page>1 then..
			// echo 'LIMIT POSTS ';
			$pgstrt = '';
			if( $this->page > 1 )
			{ // We have requested a specific page number
				$pgstrt = (intval($this->page) -1) * $this->limit. ', ';
			}
			$this->ItemQuery->LIMIT( $pgstrt.$this->limit );
		}
		elseif( $this->filters['unit'] == 'days' )
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
			debug_die( 'Unhandled LIMITING mode in ItemList:'.$this->filters['unit'].' (paged mode is obsolete)' );
	}


  /**
	 * Run Query: GET DATA ROWS *** LIGHT ***
	 *
	 * Contrary to ItemList2, we only do 1 query here and we extract only a few selected params.
	 * Basically all we want is being able to generate permalinks.
	 */
	function query()
	{
		global $DB;

		if( !is_null( $this->rows ) )
		{ // Query has already executed:
			return;
		}

		// INNIT THE QUERY:
		$this->query_init();

		// QUERY:
		$this->sql = 'SELECT DISTINCT '.$this->Cache->dbIDname.', post_datestart, post_datemodified, post_title, post_url,
									post_excerpt, post_urltitle, post_main_cat_ID, post_ptyp_ID '
									.$this->ItemQuery->get_from()
									.$this->ItemQuery->get_where()
									.$this->ItemQuery->get_group_by()
									.$this->ItemQuery->get_order_by()
									.$this->ItemQuery->get_limit();

		// echo $DB->format_query( $this->sql );

		parent::query( false, false, false, 'ItemListLight::query()' );
	}




	/**
	 * Get datetime of the last post/item
	 * @todo dh> Optimize this, if this can be said after having done {@link query()} already.
	 * @todo dh> Cache result
	 * @todo dh> Add $dateformat param
	 * @return string 'Y-m-d H:i:s' formatted; If there are no items this will be {@link $localtimenow}.
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
		 * filtering stuff:
		 */
		$lastpost_ItemQuery->where_chapter2( $this->Blog, $this->filters['cat_array'], $this->filters['cat_modifier'],
																				 $this->filters['cat_focus']  );
		$lastpost_ItemQuery->where_author( $this->filters['authors'] );
		$lastpost_ItemQuery->where_assignees( $this->filters['assignees'] );
		$lastpost_ItemQuery->where_locale( $this->filters['lc'] );
		$lastpost_ItemQuery->where_statuses( $this->filters['statuses'] );
		$lastpost_ItemQuery->where_types( $this->filters['types'] );
		$lastpost_ItemQuery->where_keywords( $this->filters['keywords'], $this->filters['phrase'], $this->filters['exact'] );
		$lastpost_ItemQuery->where_ID( $this->filters['post_ID'], $this->filters['post_title'] );
		$lastpost_ItemQuery->where_datestart( $this->filters['ymdhms'], $this->filters['week'],
		                                   $this->filters['ymdhms_min'], $this->filters['ymdhms_max'],
		                                   $this->filters['ts_min'], $this->filters['ts_max'] );
		$lastpost_ItemQuery->where_visibility( $this->filters['visibility_array'] );

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
	function get_filter_titles( $ignore = array(), $params = array() )
	{
		global $month, $post_statuses;

		$params = array_merge( array(
				'category_text' => T_('Category').': ',
				'categories_text' => T_('Categories').': ',
				// 'tag_text' => T_('Tag').': ',
				'tags_text' => T_('Tags').': ',
			), $params );

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
				$title_array[] = $Item->get('title');
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
					$title_array['cats'] = $params['categories_text'].$cat_names_string;
				}
				else
				{
					if( count($this->filters['cat_array']) > 1 )
						$title_array['cats'] = $params['categories_text'].$cat_names_string;
					else
						$title_array['cats'] = $params['category_text'].$cat_names_string;
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


		// TAGS:
		if( !empty($this->filters['tags']) )
		{
			$title_array[] = $params['tags_text'].$this->filters['tags'];
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


		// LOCALE:
		if( $this->filters['lc'] != 'all' )
		{
			$title_array[] = T_('Locale').': '.$this->filters['lc'];
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
		if( count( $this->filters['visibility_array'] ) < 5
			&& !in_array( 'visibility', $ignore ) )
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
				if( !in_array( 'hide_future', $ignore ) )
				{
					$title_array['ts_max'] = T_('Hide future');
				}
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
		elseif( $this->filters['unit'] == 'posts' || $this->filters['unit'] == 'all' )
		{ // We're going to page, so there's no real limit here...
		}
		elseif( $this->filters['unit'] == 'days' )
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
					$title_array['posts'] = sprintf( T_('Limited to %d last days'), $this->limit );
				}
			}
			else
			{ // We have a start date, we'll display x days starting from that point:
				$title_array['posts'] = sprintf( T_('Limited to %d days'), $this->limit );
			}
		}
		else
			debug_die( 'Unhandled LIMITING mode in ItemList:'.$this->filters['unit'].' (paged mode is obsolete)' );


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
	 * If the list is sorted by category...
	 *
	 * Note: this only supports one level of categories (nested cats will be flatened)
	 */
	function & get_category_group()
	{
		global $row;

		if( empty( $this->current_Obj ) )
		{	// Very first call
			// Do a normal get_next()
			parent::get_next();
		}

		if( empty( $this->current_Obj ) )
		{	// We have reached the end of the list
			return $this->current_Obj;
		}

		$this->group_by_cat = 1;

		// Memorize main cat
		$this->main_cat_ID = $this->current_Obj->main_cat_ID;

		return $this->current_Obj;
	}


	/**
	 * If the list is sorted by category...
 	 *
 	 * This is basically just a stub for backward compatibility
	 */
	function & get_item()
	{
		if( $this->group_by_cat == 1 )
		{	// This is the first call to get_item() after get_category_group()
			$this->group_by_cat = 2;
			// Return the object we already got in get_category_group():
			return $this->current_Obj;
		}

		$Item = & $this->get_next();

		if( !empty($Item) && $this->group_by_cat == 2 && $Item->main_cat_ID != $this->main_cat_ID )
		{	// We have just hit a new category!
			$this->group_by_cat == 0; // For info only.
			$r = false;
			return $r;
		}

		//pre_dump( $Item );

		return $Item;
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
	 * Template tag: Display the date if it has changed since last call
	 *
	 * Optionally also displays empty dates in between.
	 *
	 * @param array
	 */
	function date_if_changed( $params = array() )
	{
		if( $this->current_Obj->ptyp_ID == 1000 )
		{	// This is not applicable to pages
			return;
		}

		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => '<h2>',
				'after'       => '</h2>',
				'empty_day_display' => false,
				'empty_day_before' => '<h2>',
				'empty_day_after'  => '</h2>',
				'date_format' => '#',
			), $params );

		// Get a timestamp for the date WITHOUT the time:
		$current_item_date = mysql2datestamp( $this->current_Obj->issue_date );

		if( $current_item_date != $this->last_displayed_date )
		{	// Date has changed...


			if( $params['date_format'] == '#' )
			{	// No format specified, use default locale format:
				$params['date_format'] = locale_datefmt();
			}

			if( $params['empty_day_display'] && !empty($this->last_displayed_date) )
			{	// We want to display ALL dates from the previous to the current:
				while( $this->last_displayed_date < $current_item_date-86400 )
				{
					$this->last_displayed_date += 86400;	// Add one day's worth of seconds
					echo date_sprintf( $params['empty_day_before'], $this->last_displayed_date )
							.date_i18n( $params['date_format'], $this->last_displayed_date )
							.date_sprintf( $params['empty_day_after'], $this->last_displayed_date );
				}
			}

			// Display the new current date:
			echo date_sprintf( $params['before'], $this->last_displayed_date )
					.date_i18n( $params['date_format'], $current_item_date )
					.date_sprintf( $params['after'], $this->last_displayed_date );

			$this->last_displayed_date = $current_item_date;
		}
	}


	/**
	 * Template tag
	 */
	function page_links( $params = array() )
	{
		global $generating_static;

		$default_params = array(
				'block_start' => '<p class="center">',
				'block_end' => '</p>',
				'block_single' => '',
				'links_format' => '#',
				'page_url' => '', // All generated links will refer to the current page
				'prev_text' => '&lt;&lt;',
				'next_text' => '&gt;&gt;',
				'no_prev_text' => '',
				'no_next_text' => '',
				'list_prev_text' => '...',
				'list_next_text' => '...',
				'list_span' => 11,
				'scroll_list_range' => 5,
			);
	  if( !empty($generating_static) )
	  {	// When generating a static page, act as if we were currently on the blog main page:
	  	$default_params['page_url'] = $this->Blog->get('url');
		}

		// Use defaults + overrides:
		$params = array_merge( $default_params, $params );

		if( $this->total_pages <= 1 )
		{	// Single page:
			echo $params['block_single'];
			return;
		}

		if( $params['links_format'] == '#' )
		{
			$params['links_format'] = '$prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$';
		}


		echo $params['block_start'];
		echo $this->replace_vars( $params['links_format'], $params );
		echo $params['block_end'];
	}


}

/*
 * $Log$
 * Revision 1.11  2007/11/11 23:43:37  blueyed
 * Proper fix for array_merge warnings (http://forums.b2evolution.net/viewtopic.php?t=12944); Props Afwas
 *
 * Revision 1.10  2007/11/03 21:04:27  fplanque
 * skin cleanup
 *
 * Revision 1.9  2007/11/01 03:19:34  blueyed
 * Fix for array_merge in PHP5, props yettyn
 *
 * Revision 1.8  2007/10/10 09:02:36  fplanque
 * PHP5 fix
 *
 * Revision 1.7  2007/10/01 01:06:31  fplanque
 * Skin/template functions cleanup.
 *
 * Revision 1.6  2007/09/26 20:26:36  fplanque
 * improved ItemList filters
 *
 * Revision 1.5  2007/09/23 18:57:15  fplanque
 * filter handling fixes
 *
 * Revision 1.4  2007/09/19 20:03:18  yabs
 * minor bug fix ( http://forums.b2evolution.net/viewtopic.php?p=60493#60493 )
 *
 * Revision 1.3  2007/09/03 16:46:58  fplanque
 * minor
 *
 * Revision 1.2  2007/06/29 00:24:43  fplanque
 * $cat_array cleanup tentative
 *
 * Revision 1.1  2007/06/25 11:00:27  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.8  2007/06/21 00:44:37  fplanque
 * linkblog now a widget
 *
 * Revision 1.7  2007/05/27 00:35:26  fplanque
 * tag display + tag filtering
 *
 * Revision 1.6  2007/05/13 22:53:31  fplanque
 * allow feeds restricted to post excerpts
 *
 * Revision 1.5  2007/05/13 22:02:09  fplanque
 * removed bloated $object_def
 *
 * Revision 1.4  2007/03/26 14:21:30  fplanque
 * better defaults for pages implementation
 *
 * Revision 1.3  2007/03/26 12:59:18  fplanque
 * basic pages support
 *
 * Revision 1.2  2007/03/19 21:57:36  fplanque
 * ItemLists: $cat_focus and $unit extensions
 *
 * Revision 1.1  2007/03/18 03:43:19  fplanque
 * EXPERIMENTAL
 * Splitting Item/ItemLight and ItemList/ItemListLight
 * Goal: Handle Items with less footprint than with their full content
 * (will be even worse with multiple languages/revisions per Item)
 *
 * Revision 1.53  2007/03/18 01:39:54  fplanque
 * renamed _main.php to main.page.php to comply with 2.0 naming scheme.
 * (more to come)
 *
 * Revision 1.52  2007/03/12 14:02:41  waltercruz
 * Adding the columns in order by to the query to satisfy the SQL Standarts
 *
 * Revision 1.51  2007/03/03 03:37:56  fplanque
 * extended prev/next item links
 *
 * Revision 1.50  2007/03/03 01:14:12  fplanque
 * new methods for navigating through posts in single item display mode
 *
 * Revision 1.49  2007/01/26 04:49:17  fplanque
 * cleanup
 *
 * Revision 1.48  2007/01/23 09:25:40  fplanque
 * Configurable sort order.
 *
 * Revision 1.47  2007/01/20 23:05:11  blueyed
 * todos
 *
 * Revision 1.46  2007/01/19 21:48:09  blueyed
 * Fixed possible notice in preview_from_request()
 *
 * Revision 1.45  2006/12/17 23:42:38  fplanque
 * Removed special behavior of blog #1. Any blog can now aggregate any other combination of blogs.
 * Look into Advanced Settings for the aggregating blog.
 * There may be side effects and new bugs created by this. Please report them :]
 *
 * Revision 1.44  2006/12/05 00:01:15  fplanque
 * enhanced photoblog skin
 *
 * Revision 1.43  2006/12/04 18:16:50  fplanque
 * Each blog can now have its own "number of page/days to display" settings
 *
 * Revision 1.42  2006/11/28 00:33:01  blueyed
 * Removed DB::compString() (never used) and DB::get_list() (just a macro and better to have in the 4 used places directly; Cleanup/normalization; no extended regexp, when not needed!
 *
 * Revision 1.41  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.40  2006/11/17 00:19:22  blueyed
 * Switch to user locale for validating item_issue_date, because it uses T_()
 *
 * Revision 1.39  2006/11/17 00:09:15  blueyed
 * TODO: error/E_NOTICE with invalid issue date
 *
 * Revision 1.38  2006/11/12 02:13:19  blueyed
 * doc, whitespace
 *
 * Revision 1.37  2006/11/11 17:33:50  blueyed
 * doc
 *
 * Revision 1.36  2006/11/04 19:38:53  blueyed
 * Fixes for hook move
 *
 * Revision 1.35  2006/11/02 16:00:42  blueyed
 * Moved AppendItemPreviewTransact hook, so it can throw error messages
 *
 * Revision 1.34  2006/10/31 00:33:26  blueyed
 * Fixed item_issue_date for preview
 *
 * Revision 1.33  2006/10/10 17:09:39  blueyed
 * doc
 *
 * Revision 1.32  2006/10/08 22:35:01  blueyed
 * TODO: limit===NULL handling
 *
 * Revision 1.31  2006/10/05 01:17:36  blueyed
 * Removed unnecessary/doubled call to Item::update_renderers_from_Plugins()
 *
 * Revision 1.30  2006/10/05 01:06:36  blueyed
 * Removed dirty "hack"; added ItemApplyAsRenderer hook instead.
 */
?>