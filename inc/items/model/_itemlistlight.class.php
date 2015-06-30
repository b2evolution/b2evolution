<?php
/**
 * This file implements the ItemListLight class.
 *
 * This object handles item/post/article lists WITHOUT FULL FUNCTIONNALITY
 * but with a LOWER MEMORY FOOTPRINT.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );
load_class( '_core/model/dataobjects/_dataobjectlist2.class.php', 'DataObjectList2' );
load_class( 'items/model/_item.class.php', 'Item' );
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
			$filterset_name = ''				// Name to be used when saving the filterset (leave empty to use default for collection)
		)
	{
		global $Settings, $posttypes_specialtypes;

		// Call parent constructor:
		parent::DataObjectList2( get_Cache($cache_name), $limit, $param_prefix, NULL );

		// asimo> The ItemQuery init was moved into the query_init() method
		// The SQL Query object:
		// $this->ItemQuery = new ItemQuery( $this->Cache->dbtablename, $this->Cache->dbprefix, $this->Cache->dbIDname );

		$this->Blog = & $Blog;

		if( !empty( $filterset_name ) )
		{	// Set the filterset_name with the filterset_name param
			$this->filterset_name = 'ItemList_filters_'.$filterset_name;
		}
		else
		{	// Set a generic filterset_name
			$this->filterset_name = 'ItemList_filters_coll'.( !is_null( $this->Blog ) ? $this->Blog->ID : '0' );
		}

		$this->page_param = $param_prefix.'paged';

		// Initialize the default filter set:
		$this->set_default_filters( array(
				'filter_preset' => NULL,
				'ts_min' => $timestamp_min,
				'ts_max' => $timestamp_max,
				'ts_created_max' => NULL,
				'coll_IDs' => NULL, // empty: current blog only; "*": all blogs; "1,2,3": blog IDs separated by comma; "-": current blog only and exclude the aggregated blogs
				'cat_array' => array(),
				'cat_modifier' => NULL,
				'cat_focus' => 'wide',					// Search in extra categories, not just main cat
				'tags' => NULL,
				'authors' => NULL,
				'authors_login' => NULL,
				'assignees' => NULL,
				'assignees_login' => NULL,
				'author_assignee' => NULL,
				'lc' => 'all',									// Filter on requested locale
				'keywords' => NULL,
				'phrase' => 'AND',
				'exact' => 0,
				'post_ID' => NULL,
				'post_ID_list' => NULL,
				'post_title' => NULL,
				'ymdhms' => NULL,
				'week' => NULL,
				'ymdhms_min' => NULL,
				'ymdhms_max' => NULL,
				'statuses' => NULL,
				'types' => '-'.implode(',',$posttypes_specialtypes),	// Keep content post types, Exclide pages, intros, sidebar links and ads
				'visibility_array' => get_inskin_statuses( is_null( $this->Blog ) ? NULL : $this->Blog->ID, 'post' ),
				'orderby' => !is_null( $this->Blog ) ? $this->Blog->get_setting('orderby') : 'datestart',
				'order' => !is_null( $this->Blog ) ? $this->Blog->get_setting('orderdir') : 'DESC',
				'unit' => !is_null( $this->Blog ) ? $this->Blog->get_setting('what_to_show'): 'posts',
				'posts' => $this->limit,
				'page' => 1,
				'featured' => NULL,
			) );
	}


	/**
	 * Reset the query -- EXPERIMENTAL
	 *
	 * Useful to requery with a slighlty moidified filterset
	 */
	function reset()
	{
		// The SQL Query object:
		$this->ItemQuery = new ItemQuery( $this->Cache->dbtablename, $this->Cache->dbprefix, $this->Cache->dbIDname );

		parent::reset();
	}


	/**
	 * Set/Activate filterset
	 *
	 * This will also set back the GLOBALS !!! needed for regenerate_url().
	 *
	 * @param array Filters
	 * @param boolean TRUE to memorize the filter params
	 * @param boolean TRUE to use filters from previous request (from array $this->filters if it was defined before)
	 */
	function set_filters( $filters, $memorize = true, $use_previous_filters = false )
	{
		if( !empty( $filters ) )
		{ // Activate the filterset (fallback to default filter when a value is not set):
			if( $use_previous_filters )
			{ // If $this->filters were activated before(e.g. on load from request), they can be saved here
				$this->filters = array_merge( $this->default_filters, $this->filters, $filters );
			}
			else
			{ // Don't use the filters from previous request
				$this->filters = array_merge( $this->default_filters, $filters );
			}
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
			if( isset( $this->filters['cat_modifier'] ) )
			{ // Update cat param with the cat modifier only if it was set explicitly, otherwise it may overwrite the global $cat variable
				memorize_param( 'cat', '/^[*\-]?([0-9]+(,[0-9]+)*)?$/', $this->default_filters['cat_modifier'], $this->filters['cat_modifier'] );  // Category modifier
			}
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
			// List of authors users IDs to restrict to
			memorize_param( $this->param_prefix.'author', 'string', $this->default_filters['authors'], $this->filters['authors'] );
			// List of authors users logins to restrict to
			memorize_param( $this->param_prefix.'author_login', 'string', $this->default_filters['authors_login'], $this->filters['authors_login'] );

			/*
			 * Restrict to selected assignees:
			 */
			// List of assignees users IDs to restrict to
			memorize_param( $this->param_prefix.'assgn', 'string', $this->default_filters['assignees'], $this->filters['assignees'] );
			// List of assignees users logins to restrict to
			memorize_param( $this->param_prefix.'assgn_login', 'string', $this->default_filters['assignees_login'], $this->filters['assignees_login'] );

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
			 * Restrict to selected post type:
			 */
			memorize_param( $this->param_prefix.'types', 'integer', $this->default_filters['types'], $this->filters['types'] );  // List of post types to restrict to

			/*
			 * Restrict by keywords
			 */
			memorize_param( $this->param_prefix.'s', 'string', $this->default_filters['keywords'], $this->filters['keywords'] );			 // Search string
			memorize_param( $this->param_prefix.'sentence', 'string', $this->default_filters['phrase'], $this->filters['phrase'] ); // Search for sentence or for words
			memorize_param( $this->param_prefix.'exact', 'integer', $this->default_filters['exact'], $this->filters['exact'] );     // Require exact match of title or contents

			/*
			 * Specific Item selection?
			 */
			memorize_param( $this->param_prefix.'m', '/^\d{4}(0[1-9]|1[0-2])?(?(1)(0[1-9]|[12][0-9]|3[01])?)(?(2)([01][0-9]|2[0-3])?)(?(3)([0-5][0-9]){0,2})$/', $this->default_filters['ymdhms'], $this->filters['ymdhms'] );          // YearMonth(Day) to display
			memorize_param( $this->param_prefix.'w', '/^(0?[0-9]|[1-4][0-9]|5[0-3])$/', $this->default_filters['week'], $this->filters['week'] );            // Week number
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
			// This order style is OK, because sometimes the commentList is not displayed on a table so we cannot say we want to order by a specific column. It's not a crap.
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
			$filter_action = param( /*$this->param_prefix.*/'filter', 'string', 'save' );
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
		param_compile_cat_array( /* TODO: check $this->Blog->ID == 1 ? 0 :*/ !is_null( $this->Blog ) ? $this->Blog->ID : 0,
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
		// List of authors users IDs to restrict to
		$this->filters['authors'] = param( $this->param_prefix.'author', '/^-?[0-9]+(,[0-9]+)*$/', $this->default_filters['authors'], true );
		// List of authors users logins to restrict to
		$this->filters['authors_login'] = param( $this->param_prefix.'author_login', '/^-?[A-Za-z0-9_\.]+(,[A-Za-z0-9_\.]+)*$/', $this->default_filters['authors_login'], true );


		/*
		 * Restrict to selected assignees:
		 */
		// List of assignees users IDs to restrict to
		$this->filters['assignees'] = param( $this->param_prefix.'assgn', '/^(-|-[0-9]+|[0-9]+)(,[0-9]+)*$/', $this->default_filters['assignees'], true );
		// List of assignees users logins to restrict to
		$this->filters['assignees_login'] = param( $this->param_prefix.'assgn_login', '/^(-|-[A-Za-z0-9_\.]+|[A-Za-z0-9_\.]+)(,[A-Za-z0-9_\.]+)*$/', $this->default_filters['assignees_login'], true );


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


		/*
		 * multiple Item selection ?
		 */
		$this->filters['post_ID_list'] = param( $this->param_prefix.'pl', 'string', $this->default_filters['post_ID_list'] );  // Specific list of post numbers to display


		$this->single_post = !empty($this->filters['post_ID']) || !empty($this->filters['post_title']);


		/*
		 * If a timeframe is specified in the querystring, restrict to that timeframe:
		 */
		$this->filters['ymdhms'] = param( $this->param_prefix.'m', '/^\d{4}(0[1-9]|1[0-2])?(?(1)(0[1-9]|[12][0-9]|3[01])?)(?(2)([01][0-9]|2[0-3])?)(?(3)([0-5][0-9]){0,2})$/', $this->default_filters['ymdhms'], true ); // YearMonth(Day) to display
		$this->filters['week'] = param( $this->param_prefix.'w', '/^(0?[0-9]|[1-4][0-9]|5[0-3])$/', $this->default_filters['week'], true ); // Week number (0?0-53)

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
		$this->filters['visibility_array'] = param( $this->param_prefix.'show_statuses', 'array:string', $this->default_filters['visibility_array']
						, true, false, true, false );	// Array of sharings to restrict to

		/*
		 * Ordering:
		 */
		$this->filters['order'] = param( $this->param_prefix.'order', '/^(ASC|asc|DESC|desc)$/', $this->default_filters['order'], true );		// ASC or DESC
		// This order style is OK, because sometimes the commentList is not displayed on a table so we cannot say we want to order by a specific column. It's not a crap.
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
	 *
	 *
	 * @todo count?
	 */
	function query_init()
	{
		global $current_User;

		// Call reset to init the ItemQuery
		// This way avoid adding the same conditions twice if the ItemQuery was already initialized
		$this->reset();

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
		if( !is_null( $this->Blog ) )
		{ // Get the posts only for current Blog
			$this->ItemQuery->where_chapter2( $this->Blog, $this->filters['cat_array'], $this->filters['cat_modifier'],
																			$this->filters['cat_focus'], $this->filters['coll_IDs'] );
		}
		else // $this->Blog == NULL
		{ // If we want to get the posts from all blogs
			// Save for future use (permission checks..)
			$this->ItemQuery->blog = 0;
			$this->ItemQuery->Blog = $this->Blog;
		}
		$this->ItemQuery->where_tags( $this->filters['tags'] );
		$this->ItemQuery->where_author( $this->filters['authors'] );
		$this->ItemQuery->where_author_logins( $this->filters['authors_login'] );
		$this->ItemQuery->where_assignees( $this->filters['assignees'] );
		$this->ItemQuery->where_assignees_logins( $this->filters['assignees_login'] );
		$this->ItemQuery->where_author_assignee( $this->filters['author_assignee'] );
		$this->ItemQuery->where_locale( $this->filters['lc'] );
		$this->ItemQuery->where_statuses( $this->filters['statuses'] );
		$this->ItemQuery->where_types( $this->filters['types'] );
		$this->ItemQuery->where_keywords( $this->filters['keywords'], $this->filters['phrase'], $this->filters['exact'] );
		$this->ItemQuery->where_ID( $this->filters['post_ID'], $this->filters['post_title'] );
		$this->ItemQuery->where_ID_list( $this->filters['post_ID_list'] );
		$this->ItemQuery->where_datestart( $this->filters['ymdhms'], $this->filters['week'],
		                                   $this->filters['ymdhms_min'], $this->filters['ymdhms_max'],
		                                   $this->filters['ts_min'], $this->filters['ts_max'] );
		$this->ItemQuery->where_datecreated( $this->filters['ts_created_max'] );
		$this->ItemQuery->where_visibility( $this->filters['visibility_array'], $this->filters['coll_IDs'] );
		$this->ItemQuery->where_featured( $this->filters['featured'] );


		/*
		 * ORDER BY stuff:
		 */
		if( $this->filters['post_ID_list'] && $this->filters['orderby'] == 'ID_list' )
		{
			$order_by = 'FIELD('.$this->Cache->dbIDname.', '.$this->filters['post_ID_list'].')';
		}
		elseif( $this->filters['orderby'] == 'ID_list' )
		{	// Use blog setting here because 'orderby' might be set to 'ID_list' as default filter
			$this->filters['orderby'] = $this->Blog->get_setting('orderby');
		}

		if( empty($order_by) )
		{
			$available_fields = array_keys( get_available_sort_options() );
			// Extend general list to allow order posts by these fields as well for some special cases
			$available_fields[] = 'creator_user_ID';
			$available_fields[] = 'assigned_user_ID';
			$available_fields[] = 'pst_ID';
			$available_fields[] = 'datedeadline';
			$available_fields[] = 'T_categories.cat_name';
			$available_fields[] = 'T_categories.cat_order';
			$order_by = gen_order_clause( $this->filters['orderby'], $this->filters['order'], $this->Cache->dbprefix, $this->Cache->dbIDname, $available_fields );
		}

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
		/*
		elseif( !empty($this->filters['ymdhms']) // no restriction if we request a month... some permalinks may point to the archive!
		*/
		elseif( $this->filters['unit'] == 'days'  // We are going to limit to x days: no limit
		     || $this->filters['unit'] == 'all' ) // We want ALL results!
		{
			$this->total_rows = NULL; // unknown!
			$this->total_pages = 1;
			$this->page = 1;
		}
		elseif( $this->filters['unit'] == 'posts' )
		{ // Calculate a count of the posts
			if( $this->ItemQuery->get_group_by() == '' )
			{ // SQL query without GROUP BY clause
				$sql_count = 'SELECT COUNT( DISTINCT '.$this->Cache->dbIDname.' )'
					.$this->ItemQuery->get_from()
					.$this->ItemQuery->get_where()
					.$this->ItemQuery->get_limit();
			}
			else
			{ // SQL query with GROUP BY clause, Summarize a count of each grouped result
				$sql_count = 'SELECT SUM( cnt_tbl.cnt ) FROM (
						SELECT COUNT( DISTINCT '.$this->Cache->dbIDname.' ) AS cnt '
						.$this->ItemQuery->get_from()
						.$this->ItemQuery->get_where()
						.$this->ItemQuery->get_group_by()
						.$this->ItemQuery->get_limit().'
					) AS cnt_tbl ';
			}

			parent::count_total_rows( $sql_count );
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
		/*
			fp> 2007-11-25 : a very high post count can now be configured in the admin for this. Default is 100.
			elseif( !empty($this->filters['ymdhms']) )
			{ // no restriction if we request a month... some permalinks may point to the archive!
				// echo 'ARCHIVE - no limits';
			}
		*/
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

		// Check the number of totla rows after it was initialized in the query_init() function
		if( isset( $this->total_rows ) && ( intval( $this->total_rows ) === 0 ) )
		{ // Count query was already executed and returned 0
			return;
		}

		// QUERY:
		$this->sql = 'SELECT DISTINCT '.$this->Cache->dbIDname.', post_datestart, post_datemodified, post_title, post_url,
									post_excerpt, post_urltitle, post_canonical_slug_ID, post_tiny_slug_ID, post_main_cat_ID, post_ityp_ID '
									.$this->ItemQuery->get_from()
									.$this->ItemQuery->get_where()
									.$this->ItemQuery->get_group_by()
									.$this->ItemQuery->get_order_by()
									.$this->ItemQuery->get_limit();

		// echo DB::format_query( $this->sql );

		parent::query( false, false, false, 'ItemListLight::query()' );
	}




	/**
	 * Get datetime of the last post/item
	 * @todo dh> Optimize this, if this can be said after having done {@link query()} already.
	 * @todo dh> Cache result
	 * @param string Date format (see {@link date()})
	 * @return string 'Y-m-d H:i:s' formatted; If there are no items this will be {@link $localtimenow}.
	 */
	function get_lastpostdate($dateformat = 'Y-m-d H:i:s')
	{
		global $localtimenow, $DB;

		if( empty( $this->filters ) )
		{	// Filters have no been set before, we'll use the default filterset:
			// echo ' Query:Setting default filterset ';
			$this->set_filters( $this->default_filters );
		}

		// GENERATE THE QUERY:

		// The SQL Query object:
		$lastpost_ItemQuery = new ItemQuery( $this->Cache->dbtablename, $this->Cache->dbprefix, $this->Cache->dbIDname );

		/*
		 * filtering stuff:
		 */
		$lastpost_ItemQuery->where_chapter2( $this->Blog, $this->filters['cat_array'], $this->filters['cat_modifier'],
																				 $this->filters['cat_focus'], $this->filters['coll_IDs'] );
		$lastpost_ItemQuery->where_author( $this->filters['authors'] );
		$lastpost_ItemQuery->where_author_logins( $this->filters['authors_login'] );
		$lastpost_ItemQuery->where_assignees( $this->filters['assignees'] );
		$lastpost_ItemQuery->where_assignees_logins( $this->filters['assignees_login'] );
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
			$lastpostdate = date($dateformat, $localtimenow);
		}
		elseif( $dateformat != 'Y-m-d H:i:s' )
		{
			$lastpostdate = date($dateformat, strtotime($lastpostdate));
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
	 * @return array List of titles to display, which are escaped for HTML display
	 *               (dh> only checked this for 'authors'/?authors=, where the output was not escaped)
	 */
	function get_filter_titles( $ignore = array(), $params = array() )
	{
		global $month, $disp_detail;

		$params = array_merge( array(
				'category_text'       => T_('Category').': ',
				'categories_text'     => T_('Categories').': ',
				'categories_nor_text' => T_('All but '),
				'tag_text'            => T_('Tag').': ',
				'tags_text'           => T_('Tags').': ',
				'author_text'         => T_('Author').': ',
				'authors_text'        => T_('Authors').': ',
				'authors_nor_text'    => T_('All authors except').': ',
				'visibility_text'     => T_('Visibility').': ',
				'keyword_text'        => T_('Keyword').': ',
				'keywords_text'       => T_('Keywords').': ',
				'keywords_exact_text' => T_('Exact match').' ',
				'status_text'         => T_('Status').': ',
				'statuses_text'       => T_('Statuses').': ',
				'archives_text'       => T_('Archives for').': ',
				'assignes_text'       => T_('Assigned to').': ',
				'group_mask'          => '$group_title$$filter_items$', // $group_title$, $filter_items$
				'filter_mask'         => '"$filter_name$"', // $group_title$, $filter_name$, $clear_icon$
				'filter_mask_nogroup' => '"$filter_name$"', // $filter_name$, $clear_icon$
				'before_items'        => '',
				'after_items'         => '',
				'separator_and'       => ' '.T_('and').' ',
				'separator_or'        => ' '.T_('or').' ',
				'separator_nor'       => ' '.T_('or').' ',
				'separator_comma'     => ', ',
				'display_category'    => true,
				'display_archive'     => true,
				'display_keyword'     => true,
				'display_tag'         => true,
				'display_author'      => true,
				'display_assignee'    => true,
				'display_locale'      => true,
				'display_status'      => true,
				'display_visibility'  => true,
				'display_time'        => true,
				'display_limit'       => true,
			), $params );

		if( empty( $this->filters ) )
		{ // Filters have no been set before, we'll use the default filterset:
			// echo ' setting default filterset ';
			$this->set_filters( $this->default_filters );
		}

		$title_array = array();

		if( $this->single_post )
		{ // We have requested a specific post:
			// Should be in first position
			$Item = & $this->get_by_idx( 0 );

			if( is_null( $Item ) )
			{
				$title_array[] = T_('Invalid request');
			}
			else
			{
				$title_array[] = $Item->get_titletag();
			}
			return $title_array;
		}

		// Check if the filter mask has an icon to clear the filter item
		$clear_icon = ( strpos( $params['filter_mask'], '$clear_icon$' ) !== false );

		$filter_classes = array( 'green' );
		$filter_class_i = 0;
		if( strpos( $params['filter_mask'], '$filter_class$' ) !== false )
		{ // Initialize array with available classes for filter items
			$filter_classes = array( 'green', 'yellow', 'orange', 'red', 'magenta', 'blue' );
		}


		// CATEGORIES:
		if( $params['display_category'] )
		{
			if( ! empty( $this->filters['cat_array'] ) )
			{ // We have requested specific categories...
				$cat_names = array();
				$ChapterCache = & get_ChapterCache();
				$catsel_param = get_param( 'catsel' );
				foreach( $this->filters['cat_array'] as $cat_ID )
				{
					if( ( $tmp_Chapter = & $ChapterCache->get_by_ID( $cat_ID, false ) ) !== false )
					{ // It is almost never meaningful to die over an invalid cat when generating title
						$cat_clear_url = regenerate_url( ( empty( $catsel_param ) ? 'cat=' : 'catsel=' ).$cat_ID );
						if( $disp_detail == 'posts-subcat' || $disp_detail == 'posts-cat' )
						{ // Remove category url from $ReqPath when we use the cat url instead of cat ID
							$cat_clear_url = str_replace( '/'.$tmp_Chapter->get_url_path(), '', $cat_clear_url );
						}
						$cat_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', $cat_clear_url ) : '';
						$cat_names[] = str_replace( array( '$group_title$', '$filter_name$', '$clear_icon$', '$filter_class$' ),
							array( $params['category_text'], $tmp_Chapter->name, $cat_clear_icon, $filter_classes[ $filter_class_i ] ),
							$params['filter_mask'] );
					}
				}
				$filter_class_i++;
				if( $this->filters['cat_modifier'] == '*' )
				{ // Categories with "AND" condition
					$cat_names_string = implode( $params['separator_and'], $cat_names );
				}
				elseif( $this->filters['cat_modifier'] == '-' )
				{ // Categories with "NOR" condition
					$cat_names_string = implode( $params['separator_nor'], $cat_names );
				}
				else
				{ // Categories with "OR" condition
					$cat_names_string = implode( $params['separator_or'], $cat_names );
				}
				if( ! empty( $cat_names_string ) )
				{
					if( $this->filters['cat_modifier'] == '-' )
					{ // Categories with "NOR" condition
						$cat_names_string = $params['categories_nor_text'].$cat_names_string;
						$params['category_text'] = $params['categories_text'];
					}
					$title_array['cats'] = str_replace( array( '$group_title$', '$filter_items$' ),
						( count( $this->filters['cat_array'] ) > 1 ?
							array( $params['categories_text'], $params['before_items'].$cat_names_string.$params['after_items'] ) :
							array( $params['category_text'], $cat_names_string ) ),
						$params['group_mask'] );
				}
			}
		}


		// ARCHIVE TIMESLOT:
		if( $params['display_archive'] )
		{
			if( ! empty( $this->filters['ymdhms'] ) )
			{ // We have asked for a specific timeframe:

				$my_year = substr( $this->filters['ymdhms'], 0, 4 );

				if( strlen( $this->filters['ymdhms'] ) > 4 )
				{ // We have requested a month too:
					$my_month = T_( $month[ substr( $this->filters['ymdhms'], 4, 2 ) ] );
				}
				else
				{
					$my_month = '';
				}

				// Requested a day?
				$my_day = substr( $this->filters['ymdhms'], 6, 2 );

				$arch = $my_month.' '.$my_year;

				if( ! empty( $my_day ) )
				{ // We also want to display a day
					$arch .= ', '.$my_day;
				}

				if( ! empty( $this->filters['week'] ) || ( $this->filters['week'] === 0 ) ) // Note: week # can be 0
				{ // We also want to display a week number
					$arch .= ', '.T_('week').' '.$this->filters['week'];
				}

				$filter_class_i = ( $filter_class_i > count( $filter_classes ) - 1 ) ? 0 : $filter_class_i;
				$arch_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'m' ) ) : '';
				$arch = str_replace( array( '$group_title$', '$filter_name$', '$clear_icon$', '$filter_class$' ),
					array( $params['archives_text'], $arch, $arch_clear_icon, $filter_classes[ $filter_class_i ] ),
					$params['filter_mask'] );
				$title_array['ymdhms'] = str_replace( array( '$group_title$', '$filter_items$' ),
					array( $params['archives_text'], $arch ),
					$params['group_mask'] );
				$filter_class_i++;
			}
		}


		// KEYWORDS:
		if( $params['display_keyword'] )
		{
			if( ! empty( $this->filters['keywords'] ) )
			{
				if( $this->filters['phrase'] == 'OR' || $this->filters['phrase'] == 'AND' )
				{ // Search by each keyword
					$keywords = trim( preg_replace( '/("|, *)/', ' ', $this->filters['keywords'] ) );
					$keywords = explode( ' ', $keywords );
				}
				else
				{ // Exact match (Single keyword)
					$keywords = array( $this->filters['keywords'] );
				}

				$filter_class_i = ( $filter_class_i > count( $filter_classes ) - 1 ) ? 0 : $filter_class_i;
				$keyword_names = array();
				foreach( $keywords as $keyword )
				{
					$word_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'s='.$keyword ) ) : '';
					$keyword_names[] = str_replace( array( '$group_title$', '$filter_name$', '$clear_icon$', '$filter_class$' ),
						array( $params['keyword_text'], $keyword, $word_clear_icon, $filter_classes[ $filter_class_i ] ),
						$params['filter_mask'] );
				}
				$filter_class_i++;
				$keywords = ( $this->filters['exact'] ? $params['keywords_exact_text'] : '' )
					.implode( ( $this->filters['phrase'] == 'OR' ? $params['separator_or'] : $params['separator_and'] ), $keyword_names );

				$title_array[] = str_replace( array( '$group_title$', '$filter_items$' ),
					( count( $keyword_names ) > 1 ?
						array( $params['keywords_text'], $params['before_items'].$keywords.$params['after_items'] ) :
						array( $params['keyword_text'], $keywords ) ),
					$params['group_mask'] );
			}
		}


		// TAGS:
		if( $params['display_tag'] )
		{
			if( !empty($this->filters['tags']) )
			{
				$tags = explode( ',', $this->filters['tags'] );
				$tag_names = array();
				$filter_class_i = ( $filter_class_i > count( $filter_classes ) - 1 ) ? 0 : $filter_class_i;
				foreach( $tags as $tag )
				{
					$tag_clear_url = regenerate_url( $this->param_prefix.'tag='.$tag );
					if( $disp_detail == 'posts-tag' )
					{ // Remove tag url from $ReqPath when we use tag url instead of tag ID
						$tag_clear_url = str_replace( '/'.$tag.':', '', $tag_clear_url );
					}
					$tag_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', $tag_clear_url ) : '';
					$tag_names[] = str_replace( array( '$group_title$', '$filter_name$', '$clear_icon$', '$filter_class$' ),
						array( $params['tag_text'], $tag, $tag_clear_icon, $filter_classes[ $filter_class_i ] ),
						$params['filter_mask'] );
				}
				$filter_class_i++;
				$tags = implode( $params['separator_comma'], $tag_names );
				$title_array[] = str_replace( array( '$group_title$', '$filter_items$' ),
					( count( $tag_names ) > 1 ? 
						array( $params['tags_text'], $params['before_items'].$tags.$params['after_items'] ) :
						array( $params['tag_text'], $tags ) ),
					$params['group_mask'] );
			}
		}


		// AUTHORS:
		if( $params['display_author'] )
		{
			if( ! empty( $this->filters['authors'] ) || ! empty( $this->filters['authors_login'] ) )
			{
				$authors = trim( $this->filters['authors'].','.get_users_IDs_by_logins( $this->filters['authors_login'] ), ',' );
				$exclude_authors = false;
				if( substr( $authors, 0, 1 ) == '-' )
				{ // Authors are excluded
					$authors = substr( $authors, 1 );
					$exclude_authors = true;
				}
				$authors = preg_split( '~\s*,\s*~', $authors, -1, PREG_SPLIT_NO_EMPTY );
				$author_names = array();
				if( $authors )
				{
					$UserCache = & get_UserCache();
					$filter_class_i = ( $filter_class_i > count( $filter_classes ) - 1 ) ? 0 : $filter_class_i;
					foreach( $authors as $author_ID )
					{
						if( $tmp_User = $UserCache->get_by_ID( $author_ID, false, false ) )
						{
							$user_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'author='.$author_ID ) ) : '';
							$author_names[] = str_replace( array( '$group_title$', '$filter_name$', '$clear_icon$', '$filter_class$' ),
								array( $params['author_text'], $tmp_User->get( 'login' ), $user_clear_icon, $filter_classes[ $filter_class_i ] ),
								$params['filter_mask'] );
						}
					}
					$filter_class_i++;
				}
				if( count( $author_names ) > 0 )
				{ // Display info of filter by authors
					if( $exclude_authors )
					{ // Exclude authors
						$author_names_string = $params['authors_nor_text'].implode( $params['separator_nor'], $author_names );
					}
					else
					{ // Filter by authors
						$author_names_string = implode( $params['separator_comma'], $author_names );
					}

					$title_array[] = str_replace( array( '$group_title$', '$filter_items$' ),
						( count( $author_names ) > 1 ?
							array( $params['authors_text'], $params['before_items'].$author_names_string.$params['after_items'] ) :
							array( $params['author_text'], $author_names_string ) ),
						$params['group_mask'] );
				}
			}
		}


		// ASSIGNEES:
		if( $params['display_assignee'] )
		{
			if( ! empty( $this->filters['assignees'] ) || ! empty( $this->filters['assignees_login'] ) )
			{
				$filter_class_i = ( $filter_class_i > count( $filter_classes ) - 1 ) ? 0 : $filter_class_i;
				if( $this->filters['assignees'] == '-' )
				{
					$user_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'assgn' ) ) : '';
					$title_array[] = str_replace( array( '$filter_name$', '$clear_icon$', '$filter_class$' ),
						array( T_('Not assigned'), $user_clear_icon, $filter_classes[ $filter_class_i ] ),
						$params['filter_mask_nogroup'] );
				}
				else
				{
					$assignees = trim( $this->filters['assignees'].','.get_users_IDs_by_logins( $this->filters['assignees_login'] ), ',' );
					$assignees = preg_split( '~\s*,\s*~', $assignees, -1, PREG_SPLIT_NO_EMPTY );
					$assignees_names = array();
					if( $assignees )
					{
						$UserCache = & get_UserCache();
						foreach( $assignees as $user_ID )
						{
							if( $tmp_User = & $UserCache->get_by_ID( $user_ID, false, false ) )
							{
								$user_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'assgn='.$user_ID ) ) : '';
								$assignees_names[] = str_replace( array( '$group_title$', '$filter_name$', '$clear_icon$', '$filter_class$' ),
									array( $params['assignes_text'], $tmp_User->get_identity_link( array( 'link_text' => 'name' ) ), $user_clear_icon, $filter_classes[ $filter_class_i ] ),
									$params['filter_mask'] );
							}
						}
					}

					$title_array[] = str_replace( array( '$group_title$', '$filter_items$' ),
						( count( $assignees_names ) > 1 ?
							array( $params['assignes_text'], $params['before_items'].implode( $params['separator_comma'], $assignees_names ).$params['after_items'] ) :
							array( $params['assignes_text'], implode( $params['separator_comma'], $assignees_names ) ) ),
						$params['group_mask'] );
				}
				$filter_class_i++;
			}
		}


		// LOCALE:
		if( $params['display_locale'] )
		{
			if( $this->filters['lc'] != 'all' )
			{
				$filter_class_i = ( $filter_class_i > count( $filter_classes ) - 1 ) ? 0 : $filter_class_i;
				$user_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'lc' ) ) : '';
				$loc = str_replace( array( '$group_title$', '$filter_name$', '$clear_icon$', '$filter_class$' ),
					array( T_('Locale').': ', $this->filters['lc'], $user_clear_icon, $filter_classes[ $filter_class_i ] ),
					$params['filter_mask'] );
				$title_array[] = str_replace( array( '$group_title$', '$filter_items$' ),
					array( T_('Locale').': ', $loc ),
					$params['group_mask'] );
				$filter_class_i++;
			}
		}


		// EXTRA STATUSES:
		if( $params['display_status'] )
		{
			if( !empty($this->filters['statuses']) )
			{
				$filter_class_i = ( $filter_class_i > count( $filter_classes ) - 1 ) ? 0 : $filter_class_i;
				if( $this->filters['statuses'] == '-' )
				{
					$status_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'status=-' ) ) : '';
					$title_array[] = str_replace( array( '$filter_name$', '$clear_icon$', '$filter_class$' ),
						array( T_('Without status'), $status_clear_icon, $filter_classes[ $filter_class_i ] ),
						$params['filter_mask_nogroup'] );
				}
				else
				{
					$status_IDs = explode( ',', $this->filters['statuses'] );
					$ItemStatusCache = & get_ItemStatusCache();
					$statuses = array();
					foreach( $status_IDs as $status_ID )
					{
						if( $ItemStatus = & $ItemStatusCache->get_by_ID( $status_ID ) )
						{
							$status_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'status='.$status_ID ) ) : '';
							$statuses[] = str_replace( array( '$group_title$', '$filter_name$', '$clear_icon$', '$filter_class$' ),
								array( $params['status_text'], $ItemStatus->get_name(), $status_clear_icon, $filter_classes[ $filter_class_i ] ),
								$params['filter_mask'] );
						}
					}
					$title_array[] = str_replace( array( '$group_title$', '$filter_items$' ),
						( ( count( $statuses ) > 1 ) ?
							array( $params['statuses_text'], $params['before_items'].implode( $params['separator_comma'], $statuses ).$params['after_items'] ):
							array( $params['status_text'], implode( $params['separator_comma'], $statuses ) ) ),
						$params['group_mask'] );
				}
				$filter_class_i++;
			}
		}


		// VISIBILITY (SHOW STATUSES):
		if( $params['display_visibility'] )
		{
			if( !in_array( 'visibility', $ignore ) )
			{
				$post_statuses = get_visibility_statuses();
				if( count( $this->filters['visibility_array'] ) != count( $post_statuses ) )
				{ // Display it only when visibility filter is changed
					$status_titles = array();
					$filter_class_i = ( $filter_class_i > count( $filter_classes ) - 1 ) ? 0 : $filter_class_i;
					foreach( $this->filters['visibility_array'] as $status )
					{
						$vis_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'show_statuses='.$status ) ) : '';
						$status_titles[] = str_replace( array( '$group_title$', '$filter_name$', '$clear_icon$', '$filter_class$' ),
							array( $params['visibility_text'], $post_statuses[ $status ], $vis_clear_icon, $filter_classes[ $filter_class_i ] ),
							$params['filter_mask'] );
					}
					$filter_class_i++;
					$title_array[] = str_replace( array( '$group_title$', '$filter_items$' ),
						( count( $status_titles ) > 1 ? 
							array( $params['visibility_text'], $params['before_items'].implode( $params['separator_comma'], $status_titles ).$params['after_items'] ) :
							array( $params['visibility_text'], implode( $params['separator_comma'], $status_titles ) ) ),
						$params['group_mask'] );
				}
			}
		}


		if( $params['display_time'] )
		{
			// START AT:
			if( ! empty( $this->filters['ymdhms_min'] ) || ! empty( $this->filters['ts_min'] ) )
			{
				$filter_class_i = ( $filter_class_i > count( $filter_classes ) - 1 ) ? 0 : $filter_class_i;
				if( ! empty( $this->filters['ymdhms_min'] ) )
				{
					$time_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'dstart' ) ) : '';
					$title_array['ts_min'] = str_replace( array( '$group_title$', '$filter_name$', '$clear_icon$', '$filter_class$' ),
						array( T_('Start at').': ', date2mysql( $this->filters['ymdhms_min'] ), $time_clear_icon, $filter_classes[ $filter_class_i ] ),
						$params['filter_mask'] );
				}
				else
				{
					if( $this->filters['ts_min'] == 'now' )
					{
						$time_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'show_future' ) ) : '';
						$title_array['ts_min'] = str_replace( array( '$filter_name$', '$clear_icon$', '$filter_class$' ),
							array( T_('Hide past'), $time_clear_icon, $filter_classes[ $filter_class_i ] ),
							$params['filter_mask_nogroup'] );
					}
					else
					{
						$time_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'show_future' ) ) : '';
						$title_array['ts_min'] = str_replace( array( '$group_title$', '$filter_name$', '$clear_icon$', '$filter_class$' ),
							array( T_('Start at').': ', date2mysql( $this->filters['ts_min'] ), $time_clear_icon, $filter_classes[ $filter_class_i ] ),
							$params['filter_mask'] );
					}
				}
				$filter_class_i++;
			}


			// STOP AT:
			if( ! empty( $this->filters['ymdhms_max'] ) || ! empty( $this->filters['ts_max'] ) )
			{
				$filter_class_i = ( $filter_class_i > count( $filter_classes ) - 1 ) ? 0 : $filter_class_i;
				if( ! empty( $this->filters['ymdhms_max'] ) )
				{
					$time_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'dstop' ) ) : '';
					$title_array['ts_max'] = str_replace( array( '$group_title$', '$filter_name$', '$clear_icon$', '$filter_class$' ),
						array( T_('Stop at').': ', date2mysql( $this->filters['ymdhms_max'] ), $time_clear_icon, $filter_classes[ $filter_class_i ] ),
						$params['filter_mask'] );
				}
				else
				{
					if( $this->filters['ts_max'] == 'now' )
					{
						if( ! in_array( 'hide_future', $ignore ) )
						{
							$time_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'show_past' ) ) : '';
							$title_array['ts_max'] = str_replace( array( '$filter_name$', '$clear_icon$', '$filter_class$' ),
								array( T_('Hide future'), $time_clear_icon, $filter_classes[ $filter_class_i ] ),
								$params['filter_mask_nogroup'] );
						}
					}
					else
					{
						$time_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'show_past' ) ) : '';
						$title_array['ts_max'] = str_replace( array( '$group_title$', '$filter_name$', '$clear_icon$', '$filter_class$' ),
							array( T_('Stop at').': ', date2mysql( $this->filters['ts_max'] ), $time_clear_icon, $filter_classes[ $filter_class_i ] ),
							$params['filter_mask'] );
					}
				}
				$filter_class_i++;
			}
		}


		// LIMIT TO:
		if( $params['display_limit'] )
		{
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
				$filter_class_i = ( $filter_class_i > count( $filter_classes ) - 1 ) ? 0 : $filter_class_i;
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
						$unit_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'unit' ) ) : '';
						$title_array['posts'] = str_replace( array( '$filter_name$', '$clear_icon$', '$filter_class$' ),
							array( sprintf( T_('Limited to last %d days'), $this->limit ), $unit_clear_icon, $filter_classes[ $filter_class_i ] ),
							$params['filter_mask_nogroup'] );
					}
				}
				else
				{ // We have a start date, we'll display x days starting from that point:
					$unit_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'unit' ) ) : '';
					$title_array['posts'] = str_replace( array( '$filter_name$', '$clear_icon$', '$filter_class$' ),
						array( sprintf( T_('Limited to %d days'), $this->limit ), $unit_clear_icon, $filter_classes[ $filter_class_i ] ),
						$params['filter_mask_nogroup'] );
				}
				$filter_class_i++;
			}
			else
			{
				debug_die( 'Unhandled LIMITING mode in ItemList:'.$this->filters['unit'].' (paged mode is obsolete)' );
			}
		}

		return $title_array;
	}


	/**
	 * Return total number of posts
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
		if( $this->current_Obj->ityp_ID == 1000 )
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

		// Use defaults + overrides:
		$params = array_merge( $default_params, $params );

		if( $this->total_pages <= 1 || $this->page > $this->total_pages )
		{	// Single page:
			echo $params['block_single'];
			return;
		}

		if( $params['links_format'] == '#' )
		{
			$params['links_format'] = '$prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$';
		}

		if( $this->Blog->get_setting( 'paged_nofollowto' ) )
		{	// We prefer robots not to follow to pages:
			$this->nofollow_pagenav = true;
		}

		echo $params['block_start'];
		echo $this->replace_vars( $params['links_format'], $params );
		echo $params['block_end'];
	}


}

?>
