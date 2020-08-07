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
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
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
	function __construct(
			& $Blog,
			$timestamp_min = NULL,       // Do not show posts before this timestamp
			$timestamp_max = NULL,   		 // Do not show posts after this timestamp
			$limit = 20,
			$cache_name = 'ItemCacheLight',	 // name of cache to be used (for table prefix info)
			$param_prefix = '',
			$filterset_name = ''				// Name to be used when saving the filterset (leave empty to use default for collection)
		)
	{
		global $Settings;

		// Call parent constructor:
		parent::__construct( get_Cache($cache_name), $limit, $param_prefix, NULL );

		// asimo> The ItemQuery init was moved into the query_init() method
		// The SQL Query object:
		// $this->ItemQuery = new ItemQuery( $this->Cache->dbtablename, $this->Cache->dbprefix, $this->Cache->dbIDname );

		$this->Blog = & $Blog;

		if( !empty( $filterset_name ) )
		{	// Set the filterset_name with the filterset_name param
			$this->filterset_name = 'ItemList_filters_'.preg_replace( '#[^a-z0-9\-_]#i', '', $filterset_name );
		}
		else
		{	// Set a generic filterset_name
			$this->filterset_name = 'ItemList_filters_coll'.( !is_null( $this->Blog ) ? $this->Blog->ID : '0' );
		}

		$this->page_param = $this->param_prefix.'paged';

		// Initialize the default filter set:
		$this->set_default_filters( array(
				'filter_preset' => NULL,
				'flagged' => false,
				'mustread' => false, // true/1/'all' - All(Read and Unread) items with "must read" flag, 'unread' - Items which are not read by current User yet, 'read' - Items which are already read by current User, false - Don't match items with "must read" flag
				'ts_min' => $timestamp_min,
				'ts_max' => $timestamp_max,
				'ts_created_max' => NULL,
				'coll_IDs' => NULL, // empty: current blog only; "*": all blogs; "1,2,3": blog IDs separated by comma; "-": current blog only and exclude the aggregated blogs
				'cat_single' => NULL, 	// If we requested a "single category" page, ID of the top category
				'cat_array' => array(),
				'cat_modifier' => NULL,
				'cat_focus' => 'wide',					// Search in extra categories, not just main cat
				'tags' => NULL,
				'tags_operator' => 'OR',
				'authors' => NULL,
				'authors_login' => NULL,
				'assignees' => NULL,
				'assignees_login' => NULL,
				'author_assignee' => NULL,
				'involves' => NULL,
				'involves_login' => NULL,
				'lc' => 'all',									// Filter on requested locale
				'keywords' => NULL,
				'keyword_scope' => 'title,content', // What fields are used for searching: 'title', 'content'
				'phrase' => 'AND', // 'OR', 'AND', 'sentence'(or '1')
				'exact' => 0,
				'post_ID' => NULL,
				'post_ID_list' => NULL,
				'post_title' => NULL,
				'ymdhms' => NULL,
				'week' => NULL,
				'ymdhms_min' => NULL,
				'ymdhms_max' => NULL,
				'statuses' => NULL,
				'statuses_array' => NULL,
				'types' => NULL, // Filter by item type IDs (separated by comma)
				'itemtype_usage' => 'post', // Filter by item type usage (separated by comma): post, page, intro-front, intro-main, intro-cat, intro-tag, intro-sub, intro-all, special
				'visibility_array' => get_inskin_statuses( is_null( $this->Blog ) ? NULL : $this->Blog->ID, 'post' ),
				'orderby' => get_blog_order( $this->Blog, 'field' ),
				'order' => get_blog_order( $this->Blog, 'dir' ),
				'unit' => !is_null( $this->Blog ) ? $this->Blog->get_setting('what_to_show'): 'posts',
				'posts' => $this->limit,
				'page' => 1,
				'featured' => NULL,
				'renderers' => NULL,
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
		{	// Activate the filterset (fallback to default filter when a value is not set):
			if( $use_previous_filters )
			{	// If $this->filters were activated before(e.g. on load from request), they can be saved here
				$this->filters = array_merge( $this->default_filters, $this->filters, $filters );
			}
			else
			{	// Don't use the filters from previous request
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
			{	// Update cat param with the cat modifier only if it was set explicitly, otherwise it may overwrite the global $cat variable
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
			 * Restrict to selected involves:
			 */
			// List of involved user IDs to restrict to
			memorize_param( $this->param_prefix.'involves', 'string', $this->default_filters['involves'], $this->filters['involves'] );
			// List of involved user logins to restrict to
			memorize_param( $this->param_prefix.'involves_login', 'string', $this->default_filters['involves_login'], $this->filters['involves_login'] );

			/*
			 * Restrict to selected locale:
			 */
			memorize_param( $this->param_prefix.'lc', 'string', $this->default_filters['lc'], $this->filters['lc'] );  // Locale to restrict to

			/*
			 * Restrict to selected statuses:
			 */
			memorize_param( $this->param_prefix.'status', 'string', $this->default_filters['statuses'], $this->filters['statuses'] );  // List of statuses to restrict to
			memorize_param( $this->param_prefix.'statuses', 'array:string', $this->default_filters['statuses_array'], $this->filters['statuses_array'] );  // Array of statuses to restrict to

			/*
			 * Restrict to selected post type:
			 */
			memorize_param( $this->param_prefix.'types', 'integer', $this->default_filters['types'], $this->filters['types'] );  // List of post types to restrict to

			/*
			 * Restrict to selected post type usage:
			 */
			memorize_param( $this->param_prefix.'itemtype_usage', 'string', $this->default_filters['itemtype_usage'], $this->filters['itemtype_usage'] );  // List of post types usage to restrict to

			/*
			 * Restrict by keywords
			 */
			memorize_param( $this->param_prefix.'s', 'string', $this->default_filters['keywords'], $this->filters['keywords'] );			 // Search string
			memorize_param( $this->param_prefix.'scope', 'string', $this->default_filters['keyword_scope'], $this->filters['keyword_scope'] ); // Scope of search string
			memorize_param( $this->param_prefix.'sentence', 'string', $this->default_filters['phrase'], $this->filters['phrase'] ); // Search for sentence or for words
			memorize_param( $this->param_prefix.'exact', 'integer', $this->default_filters['exact'], $this->filters['exact'] );     // Require exact match of title or contents

			/*
			 * Specific Item selection?
			 */
			memorize_param( $this->param_prefix.'m', '/^\d{4}(0[1-9]|1[0-2])?(?(1)(0[1-9]|[12][0-9]|3[01])?)(?(2)([01][0-9]|2[0-3])?)(?(3)([0-5][0-9]){0,2})$/', $this->default_filters['ymdhms'], $this->filters['ymdhms'] );          // YearMonth(Day) to display
			memorize_param( $this->param_prefix.'w', '/^(0?[0-9]|[1-4][0-9]|5[0-3])$/', $this->default_filters['week'], $this->filters['week'] );            // Week number
			memorize_param( $this->param_prefix.'dstart', 'integer', $this->default_filters['ymdhms_min'], $this->filters['ymdhms_min'] ); // YearMonth(Day) to start at
			memorize_param( $this->param_prefix.'dstop', 'integer', $this->default_filters['ymdhms_max'], $this->filters['ymdhms_max'] ); // YearMonth(Day) to start at

			/*
			 * Restrict by flagged items:
			 */
			memorize_param( $this->param_prefix.'flagged', 'integer', $this->default_filters['flagged'], $this->filters['flagged'] );

			/*
			 * Restrict by "must read" items:
			 */
			memorize_param( $this->param_prefix.'mustread', 'string', $this->default_filters['mustread'], $this->filters['mustread'] );

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
			 * Restrict to selected renderer plugins:
			 */
			memorize_param( $this->param_prefix.'renderers', 'array:string', $this->default_filters['renderers'], $this->filters['renderers'] );

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
		$cat = param( 'cat', '/^[*\-\|]?([0-9]+(,[0-9]+)*)?$/', $this->default_filters['cat_modifier'], true ); // List of cats to restrict to
		$catsel = param( 'catsel', 'array:integer', $this->default_filters['cat_array'], true );  // Array of cats to restrict to

		if( ( empty( $catsel ) || // 'catsel' multicats filter is not defined
		      ( is_array( $catsel ) && count( $catsel ) == 1 ) // 'catsel' filter is used for single cat, e.g. when skin config 'cat_array_mode' = 'parent'
		    ) && preg_match( '~^[0-9]+$~', $cat ) ) // 'cat' filter is ID of category and NOT modifier for 'catsel' multicats
		{	// We are on a single cat page: (equivalent to $disp_detail == 'posts-topcat')
			// NOTE: we must have selected EXACTLY ONE CATEGORY through the cat parameter
			// BUT: - this can resolve to including children
			//      - selecting exactly one cat through catsel[] is NOT OK since not equivalent (will exclude children)
			// Record this "single cat":
			$this->filters['cat_single'] = $cat;
		}

		// Get chapters/categories (and compile those values right away)
		param_compile_cat_array( ( is_null( $this->Blog ) ? 0 : $this->Blog->ID ), $this->default_filters['cat_modifier'], $this->default_filters['cat_array'] );

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
		 * Restrict to selected involves:
		 */
		// List of involved user IDs to restrict to
		$this->filters['involves'] = param( $this->param_prefix.'involves', '/^-?[0-9]+(,[0-9]+)*$/', $this->default_filters['involves'], true );
		// List of involved user logins to restrict to
		$this->filters['involves_login'] = param( $this->param_prefix.'involves_login', '/^-?[A-Za-z0-9_\.]+(,[A-Za-z0-9_\.]+)*$/', $this->default_filters['involves_login'], true );


		/*
		 * Restrict to selected locale:
		 */
		$this->filters['lc'] = param( $this->param_prefix.'lc', 'string', $this->default_filters['lc'], true );


		/*
		 * Restrict to selected statuses:
		 */
		$this->filters['statuses'] = param( $this->param_prefix.'status', '/^(-|-[0-9]+|[0-9]+)(,[0-9]+)*$/', $this->default_filters['statuses'], true );      // List of statuses to restrict to
		$this->filters['statuses_array'] = param( $this->param_prefix.'statuses', 'array:string', $this->default_filters['statuses_array'], true ); // Array of statuses to restrict to

		/*
		 * Restrict to selected types:
		 */
		$this->filters['types'] = param( $this->param_prefix.'types', '/^(-|-[0-9]+|[0-9]+)(,[0-9]+)*$/', $this->default_filters['types'], true );      // List of types to restrict to

		/*
		 * Restrict to selected types usage:
		 */
		$this->filters['itemtype_usage'] = param( $this->param_prefix.'itemtype_usage', 'string', $this->default_filters['itemtype_usage'], true ); // List of types usage to restrict to

		/*
		 * Restrict by keywords
		 */
		$this->filters['keywords'] = param( $this->param_prefix.'s', 'string', $this->default_filters['keywords'], true );         // Search string
		$this->filters['keyword_scope'] = param( $this->param_prefix.'scope', 'string', $this->default_filters['keyword_scope'], true ); // Scope of search string
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


		/*
		 * Restrict by flagged items:
		 */
		$this->filters['flagged'] = param( $this->param_prefix.'flagged', 'integer', $this->default_filters['flagged'], true );


		/*
		 * Restrict by "must read" items:
		 */
		$this->filters['mustread'] = param( $this->param_prefix.'mustread', 'string', $this->default_filters['mustread'], true );


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
		 * Restrict to selected renderer plugins:
		 */
		$this->filters['renderers'] = param( $this->param_prefix.'renderers', 'array:string', $this->default_filters['renderers'], true );

		/*
		 * Ordering:
		 */
		$this->filters['order'] = param( $this->param_prefix.'order', '/^(asc|desc)([ ,](asc|desc))*$/i', $this->default_filters['order'], true );		// ASC or DESC
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
	 * Generate search query based on set filters and obtain count of results
	 */
	function query_init()
	{
		// Call reset to init the ItemQuery
		// This prevents from adding the same conditions twice if the ItemQuery was already initialized
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
		 * Filtering stuff:
		 */
		if( !is_null( $this->Blog ) )
		{	// Get the posts only for current Blog
			$this->ItemQuery->where_chapter2( $this->Blog, $this->filters['cat_array'], $this->filters['cat_modifier'],
																			$this->filters['cat_focus'], $this->filters['coll_IDs'] );
		}
		else // $this->Blog == NULL
		{	// If we want to get the posts from all blogs
			// Save for future use (permission checks..)
			$this->ItemQuery->blog = 0;
			$this->ItemQuery->Blog = $this->Blog;
		}

		$this->ItemQuery->where_tags( $this->filters['tags'], $this->filters['tags_operator'] );
		$this->ItemQuery->where_author( $this->filters['authors'] );
		$this->ItemQuery->where_author_logins( $this->filters['authors_login'] );
		$this->ItemQuery->where_assignees( $this->filters['assignees'] );
		$this->ItemQuery->where_assignees_logins( $this->filters['assignees_login'] );
		$this->ItemQuery->where_author_assignee( $this->filters['author_assignee'] );
		$this->ItemQuery->where_involves( $this->filters['involves'] );
		$this->ItemQuery->where_involves_logins( $this->filters['involves_login'] );
		$this->ItemQuery->where_locale( $this->filters['lc'] );
		$this->ItemQuery->where_statuses( $this->filters['statuses'] );
		$this->ItemQuery->where_statuses_array( $this->filters['statuses_array'] );
		$this->ItemQuery->where_types( $this->filters['types'] );
		$this->ItemQuery->where_itemtype_usage( $this->filters['itemtype_usage'] );
		$this->ItemQuery->where_keywords( $this->filters['keywords'], $this->filters['phrase'], $this->filters['exact'], $this->filters['keyword_scope'] );
		$this->ItemQuery->where_ID( $this->filters['post_ID'], $this->filters['post_title'] );
		$this->ItemQuery->where_ID_list( $this->filters['post_ID_list'] );
		$this->ItemQuery->where_datestart( $this->filters['ymdhms'], $this->filters['week'],
		                                   $this->filters['ymdhms_min'], $this->filters['ymdhms_max'],
		                                   $this->filters['ts_min'], $this->filters['ts_max'] );
		$this->ItemQuery->where_datecreated( $this->filters['ts_created_max'] );
		$this->ItemQuery->where_visibility( $this->filters['visibility_array'], $this->filters['coll_IDs'] );
		$this->ItemQuery->where_featured( $this->filters['featured'] );
		$this->ItemQuery->where_flagged( $this->filters['flagged'] );
		if( ! $this->single_post )
		{	// Restrict with locale visibility by current navigation locale ONLY for not single page:
			$this->ItemQuery->where_locale_visibility();
		}
		$this->ItemQuery->where_mustread( $this->filters['mustread'] );
		$this->ItemQuery->where_renderers( $this->filters['renderers'] );


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

		if( isset( $this->filters['orderby'] ) && $this->filters['orderby'] == 'numviews' )
		{	// Order by number of views
			//$this->ItemQuery->FROM_add( 'LEFT JOIN ( SELECT itud_item_ID, COUNT(*) AS '.$this->Cache->dbprefix.'numviews FROM T_items__user_data GROUP BY itud_item_ID ) AS numviews
			//		ON '.$this->Cache->dbIDname.' = numviews.itud_item_ID' );
		}

		if( empty($order_by) )
		{
			$order_by = $this->ItemQuery->gen_order_clause( $this->filters['orderby'], $this->filters['order'], $this->Cache->dbprefix, $this->Cache->dbIDname );
		}

		$this->ItemQuery->order_by( $order_by );



		/*
		 * GET TOTAL ROW COUNT:
		 */
		if( $this->single_post )   // p or title
		{	// Single post: no paging required!
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
		{	// Calculate a count of the posts
			if( $this->ItemQuery->get_group_by() == '' )
			{	// SQL query without GROUP BY clause
				$sql_count = 'SELECT COUNT( DISTINCT '.$this->Cache->dbIDname.' )'
					.$this->ItemQuery->get_from()
					.$this->ItemQuery->get_where()
					.$this->ItemQuery->get_limit();
			}
			else
			{	// SQL query with GROUP BY clause, Summarize a count of each grouped result
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
		{	// Single post: no paging required!
		}
		/*
			fp> 2007-11-25 : a very high post count can now be configured in the admin for this. Default is 100.
			elseif( !empty($this->filters['ymdhms']) )
			{	// no restriction if we request a month... some permalinks may point to the archive!
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
			{	// We have requested a specific page number
				$pgstrt = (intval($this->page) -1) * $this->limit. ', ';
			}
			$this->ItemQuery->LIMIT( $pgstrt.$this->limit );
		}
		elseif( $this->filters['unit'] == 'days' )
		{	// We are going to limit to x days:
			// echo 'LIMIT DAYS ';
			if( empty( $this->filters['ymdhms_min'] ) )
			{	// We have no start date, we'll display the last x days:
				if( !empty($this->filters['keywords'])
					|| !empty($this->filters['cat_array'])
					|| !empty($this->filters['authors']) )
				{	// We are in DAYS mode but we can't restrict on these! (TODO: ?)
					$limits = '';
				}
				else
				{	// We are going to limit to LAST x days:
					$lastpostdate = $this->get_lastpostdate();
					$lastpostdate = mysql2date('Y-m-d 00:00:00',$lastpostdate);
					$lastpostdate = mysql2date('U',$lastpostdate);
					// go back x days
					$otherdate = date('Y-m-d H:i:s', ($lastpostdate - (($this->limit-1) * 86400)));
					$this->ItemQuery->WHERE_and( $this->Cache->dbprefix.'datestart > \''. $otherdate.'\'' );
				}
			}
			else
			{	// We have a start date, we'll display x days starting from that point:
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
	 *
	 * We need this query() stub in order to call it from restart() and still
	 * let derivative classes override it
	 *
	 * @deprecated Use new function run_query()
	 */
	function query( $create_default_cols_if_needed = true, $append_limit = true, $append_order_by = true )
	{
		$this->run_query( $create_default_cols_if_needed, $append_limit, $append_order_by );
	}


	/**
	 * Run Query: GET DATA ROWS *** LIGHT ***
	 *
	 * Contrary to ItemList2, we only do 1 query here and we extract only a few selected params.
	 * Basically all we want is being able to generate permalinks.
	 */
	function run_query( $create_default_cols_if_needed = true, $append_limit = true, $append_order_by = true,
											$query_title = 'Results::run_query()' )
	{
		global $DB;

		if( !is_null( $this->rows ) )
		{	// Query has already executed:
			return;
		}

		// INNIT THE QUERY:
		$this->query_init();

		// Check the number of totla rows after it was initialized in the query_init() function
		if( isset( $this->total_rows ) && ( intval( $this->total_rows ) === 0 ) )
		{	// Count query was already executed and returned 0
			return;
		}

		// QUERY:
		$this->ItemQuery->SELECT( 'DISTINCT '.$this->Cache->dbIDname.', post_datestart, post_datemodified, post_title, post_short_title, post_url,' );
		$this->ItemQuery->SELECT_add( 'post_excerpt, post_urltitle, post_canonical_slug_ID, post_tiny_slug_ID, post_main_cat_ID, post_ityp_ID, post_single_view' );
		if( ! preg_match( '/'.preg_quote( 'T_postcats' ).'( AS ([^\s]+))?/i', $this->ItemQuery->get_from(), $match_postcats_alias ) )
		{	// If categories table is not joined yet we should use it for column postcat_cat_ID
			$this->ItemQuery->FROM_add( 'INNER JOIN T_postcats ON '.$this->Cache->dbIDname.' = postcat_post_ID' );
		}
		$this->ItemQuery->FROM_add( $this->ItemQuery->get_orderby_from() );
		// Use the custom alias(probably "postcatsorders") of the table T_postcats if it is used in the FROM clause,
		// and use default alias T_postcats if there is no defined alias:
		$table_postcats_alias = empty( $match_postcats_alias[2] ) ? 'T_postcats' : $match_postcats_alias[2];
		$this->ItemQuery->SELECT_add( ', '.$table_postcats_alias.'.postcat_cat_ID' );
		if( $this->ItemQuery->get_group_by() == '' )
		{	// Group by item ID only if another grouping is not used currently:
			$this->ItemQuery->GROUP_BY( $this->Cache->dbIDname );
		}
		$this->sql = $this->ItemQuery->get();

		// echo DB::format_query( $this->sql );

		parent::run_query( false, false, false, 'ItemListLight::query()' );
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
		$lastpost_ItemQuery->where_involves( $this->filters['involves'] );
		$lastpost_ItemQuery->where_involves_logins( $this->filters['involves_login'] );
		$lastpost_ItemQuery->where_locale( $this->filters['lc'] );
		$lastpost_ItemQuery->where_statuses( $this->filters['statuses'] );
		$lastpost_ItemQuery->where_statuses_array( $this->filters['statuses_array'] );
		$lastpost_ItemQuery->where_types( $this->filters['types'] );
		$lastpost_ItemQuery->where_itemtype_usage( $this->filters['itemtype_usage'] );
		$lastpost_ItemQuery->where_keywords( $this->filters['keywords'], $this->filters['phrase'], $this->filters['exact'], $this->filters['keyword_scope'] );
		$lastpost_ItemQuery->where_ID( $this->filters['post_ID'], $this->filters['post_title'] );
		$lastpost_ItemQuery->where_datestart( $this->filters['ymdhms'], $this->filters['week'],
		                                   $this->filters['ymdhms_min'], $this->filters['ymdhms_max'],
		                                   $this->filters['ts_min'], $this->filters['ts_max'] );
		$lastpost_ItemQuery->where_visibility( $this->filters['visibility_array'] );
		$lastpost_ItemQuery->where_locale_visibility();
		$lastpost_ItemQuery->where_renderers( $this->filters['renderers'] );

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
		global $month, $disp_detail, $Blog;

		$params = array_merge( array(
				'display_category'    => true,
				'category_text'       => T_('Category').': ',
				'categories_text'     => T_('Categories').': ',
				'categories_nor_text' => T_('All but '),
				'categories_display'  => 'toplevel', 	// 'full' | 'toplevel'

				'display_tag'         => true,
				'tag_text'            => /* TRANS: noun */ T_('Tag').': ',
				'tags_text'           => /* TRANS: noun */ T_('Tags').': ',

				'display_author'      => true,
				'author_text'         => T_('Author').': ',
				'authors_text'        => T_('Authors').': ',
				'authors_nor_text'    => T_('All authors except').': ',

				'display_visibility'  => true,
				'visibility_text'     => T_('Visibility').': ',

				'display_keyword'     => true,
				'keyword_text'        => T_('Keyword').': ',
				'keywords_text'       => T_('Keywords').': ',
				'keywords_exact_text' => T_('Exact match').' ',

				'display_status'      => true,
				'status_text'         => T_('Status').': ',
				'statuses_text'       => T_('Statuses').': ',
				'statuses_nor_text'   => T_('All but '),

				'display_itemtype'    => true,
				'type_text'           => T_('Item Type').': ',
				'types_text'          => T_('Item Types').': ',

				'display_archive'     => true,
				'archives_text'       => T_('Archives for').': ',

				'display_assignee'    => true,
				'assignes_text'       => T_('Assigned to').': ',

				'display_involves'    => true,
				'involves_text'       => T_('Involves').': ',
				'involves_nor_text'   => T_('All involves except').': ',

				'display_locale'      => true,
				'display_time'        => true,
				'display_limit'       => true,
				'display_flagged'     => true,
				'display_mustread'    => true,

				'display_renderer'    => true,
				'renderer_text'       => T_('Renderer').': ',
				'renderers_text'      => T_('Renderers').': ',

				'group_mask'          => '$group_title$$filter_items$', // $group_title$, $filter_items$
				'filter_mask'         => '"$filter_name$"', // $group_title$, $filter_name$, $clear_icon$
				'filter_mask_nogroup' => '"$filter_name$"', // $filter_name$, $clear_icon$

				'before_items'        => '',
				'after_items'         => '',

				'separator_and'       => ' '.T_('and').' ',
				'separator_or'        => ' '.T_('or').' ',
				'separator_nor'       => ' '.T_('or').' ',
				'separator_comma'     => ', ',
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
		{	// Initialize array with available classes for filter items
			$filter_classes = array( 'green', 'yellow', 'orange', 'red', 'magenta', 'blue' );
		}


		// CATEGORIES:
		if( $params['display_category'] )
		{
			$catlist = NULL;
			if( $params['categories_display'] == 'toplevel' // We'd like to minimize display if possible
				&& !empty($this->filters['cat_single']) )	// ... AND we are on a "single cat" page
			{	// We want to show only the top cat (and not its children)
				$catlist = array($this->filters['cat_single']);

			}
			elseif( ! empty( $this->filters['cat_array'] ) )
			{	// We have requested specific categories...
				$catlist = $this->filters['cat_array'];
			}

			if( !empty($catlist))
			{	// We want to show some category names:
				$cat_names = array();
				$ChapterCache = & get_ChapterCache();
				$catsel_param = get_param( 'catsel' );
				foreach( $catlist as $cat_ID )
				{
					if( ( $tmp_Chapter = & $ChapterCache->get_by_ID( $cat_ID, false ) ) !== false )
					{	// It is almost never meaningful to die over an invalid cat when generating title
						$cat_clear_url = regenerate_url( ( empty( $catsel_param ) ? 'cat=' : 'catsel=' ).$cat_ID );
						if( in_array( $disp_detail, array( 'posts-cat', 'posts-topcat-intro', 'posts-topcat-nointro', 'posts-subcat-intro', 'posts-subcat-nointro' ) ) )
						{	// Remove category url from $ReqPath when we use the cat url instead of cat ID
							$cat_clear_url = str_replace( '/'.$tmp_Chapter->get_url_path(), '/', $cat_clear_url );
						}
						$cat_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', $cat_clear_url ) : '';
						$cat_names[] = str_replace( array( '$group_title$', '$filter_name$', '$clear_icon$', '$filter_class$' ),
							array( $params['category_text'], $tmp_Chapter->name, $cat_clear_icon, $filter_classes[ $filter_class_i ] ),
							$params['filter_mask'] );
					}
				}
				$filter_class_i++;
				if( $this->filters['cat_modifier'] == '*' )
				{	// Categories with "AND" condition
					$cat_names_string = implode( $params['separator_and'], $cat_names );
				}
				elseif( $this->filters['cat_modifier'] == '-' )
				{	// Categories with "NOR" condition
					$cat_names_string = implode( $params['separator_nor'], $cat_names );
				}
				else
				{	// Categories with "OR" condition
					$cat_names_string = implode( $params['separator_or'], $cat_names );
				}
				if( ! empty( $cat_names_string ) )
				{
					if( $this->filters['cat_modifier'] == '-' )
					{	// Categories with "NOR" condition
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
			{	// We have asked for a specific timeframe:

				$my_year = substr( $this->filters['ymdhms'], 0, 4 );

				if( strlen( $this->filters['ymdhms'] ) > 4 )
				{	// We have requested a month too:
					$my_month = substr( $this->filters['ymdhms'], 4, 2 );
					$my_month_string = T_( $month[ $my_month ] );
				}
				else
				{
					$my_month = NULL;
					$my_month_string = '';
				}

				// Requested a day?
				$my_day = substr( $this->filters['ymdhms'], 6, 2 );

				$arch = $my_month_string.' '.$my_year;

				if( ! empty( $my_day ) )
				{	// We also want to display a day
					$arch .= ', '.$my_day;
					$arch = date( locale_extdatefmt(), strtotime( implode( '-', array( $my_year, $my_month, $my_day ) ) ) );
				}

				if( ! empty( $this->filters['week'] ) || ( $this->filters['week'] === 0 ) ) // Note: week # can be 0
				{	// We also want to display a week number
					$arch .= ', '.T_('week').' '.$this->filters['week'];
				}

				$filter_class_i = ( $filter_class_i > count( $filter_classes ) - 1 ) ? 0 : $filter_class_i;
				$archive_clear_url = regenerate_url( $this->param_prefix.'m' );
				if( $disp_detail == 'posts-date' )
				{	// Remove archive url from $ReqPath when we use archive url instead of tag ID:
					$current_archive_url = $Blog->gen_archive_url( $my_year, ( empty( $my_month ) ? NULL : $my_month ), ( empty( $my_day ) ? NULL : $my_day ), ( empty( $this->filters['week'] ) ? NULL : $this->filters['week'] ) );
					$archive_clear_url = preg_replace( '#^'.preg_quote( $current_archive_url, '#' ).'#', $Blog->get( 'url' ), $archive_clear_url );
				}
				$arch_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', $archive_clear_url ) : '';
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
				{	// Search by each keyword
					$keywords = trim( preg_replace( '/("|, *)/', ' ', $this->filters['keywords'] ) );
					$keywords = explode( ' ', $keywords );
				}
				else
				{	// Exact match (Single keyword)
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
					if( $disp_detail == 'posts-tag-intro' || $disp_detail == 'posts-tag-nointro' )
					{	// Remove tag url from $ReqPath when we use tag url instead of tag ID
						$tag_clear_url = str_replace( '/'.$tag.':', '/', $tag_clear_url );
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
				{	// Authors are excluded
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
				{	// Display info of filter by authors
					if( $exclude_authors )
					{	// Exclude authors
						$author_names_string = $params['authors_nor_text'].implode( $params['separator_nor'], $author_names );
					}
					else
					{	// Filter by authors
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


		// INVOLVES:
		if( $params['display_involves'] )
		{
			if( ! empty( $this->filters['involves'] ) || ! empty( $this->filters['involves_login'] ) )
			{
				$involves = trim( $this->filters['involves'].','.get_users_IDs_by_logins( $this->filters['involves_login'] ), ',' );
				$exclude_involves = false;
				if( substr( $involves, 0, 1 ) == '-' )
				{	// Authors are excluded
					$involves = substr( $involves, 1 );
					$exclude_involves = true;
				}
				$involves = preg_split( '~\s*,\s*~', $involves, -1, PREG_SPLIT_NO_EMPTY );
				$involves_names = array();
				if( $involves )
				{
					$UserCache = & get_UserCache();
					$filter_class_i = ( $filter_class_i > count( $filter_classes ) - 1 ) ? 0 : $filter_class_i;
					foreach( $involves as $involves_ID )
					{
						if( $tmp_User = $UserCache->get_by_ID( $involves_ID, false, false ) )
						{
							$user_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'involves='.$involves_ID ) ) : '';
							$involves_names[] = str_replace( array( '$group_title$', '$filter_name$', '$clear_icon$', '$filter_class$' ),
								array( $params['involves_text'], $tmp_User->get( 'login' ), $user_clear_icon, $filter_classes[ $filter_class_i ] ),
								$params['filter_mask'] );
						}
					}
					$filter_class_i++;
				}
				if( count( $involves_names ) > 0 )
				{	// Display info of filter by involves
					if( $exclude_involves )
					{	// Exclude involves
						$involves_names_string = $params['involves_nor_text'].implode( $params['separator_nor'], $involves_names );
					}
					else
					{	// Filter by involves
						$involves_names_string = implode( $params['separator_comma'], $involves_names );
					}

					$title_array[] = str_replace( array( '$group_title$', '$filter_items$' ),
						array( $params['involves_text'], $params['before_items'].$involves_names_string.$params['after_items'] ),
						$params['group_mask'] );
				}
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


		// EXTRA(WORKFLOW/TASK) STATUSES:
		if( $params['display_status'] )
		{
			if( ! empty( $this->filters['statuses'] ) || ! empty( $this->filters['statuses_array'] ) )
			{
				$filter_class_i = ( $filter_class_i > count( $filter_classes ) - 1 ) ? 0 : $filter_class_i;
				if( isset( $this->filters['statuses_array'] ) &&
				    is_array( $this->filters['statuses_array'] ) &&
				    ! empty( $this->filters['statuses_array'] ) )
				{	// Filter by array of statuses is used currently:
					$filter_statuses = $this->filters['statuses_array'];
					$filter_status_param = $this->param_prefix.'statuses';
					$task_status_separator = $params['separator_or'];
					$task_status_prefix = '';
				}
				elseif( ! empty( $this->filters['statuses'] ) )
				{	// Filter by list/string of statuses is used currently:
					$filter_statuses = explode( ',', $this->filters['statuses'] );
					$filter_status_param = $this->param_prefix.'status';
					if( strlen( $filter_statuses[0] ) > 1 &&
					    substr( $filter_statuses[0], 0, 1 ) == '-' )
					{	// Filter to exclude by statuses:
						$filter_statuses[0] = substr( $filter_statuses[0], 1 );
						$task_status_separator = $params['separator_nor'];
						$task_status_prefix = $params['statuses_nor_text'];
						$params['status_text'] = $params['statuses_text'];
					}
					else
					{	// Filter to include by statuses:
						$task_status_separator = $params['separator_or'];
						$task_status_prefix = '';
					}
				}
				else
				{	// No filters by status:
					$filter_statuses = array();
				}
				$ItemStatusCache = & get_ItemStatusCache();
				$task_status_titles = array();
				foreach( $filter_statuses as $filter_status )
				{
					if( $filter_status == '-' )
					{	// Without status:
						$status_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $filter_status_param.'=-' ) ) : '';
						$task_status_titles[] = str_replace( array( '$filter_name$', '$clear_icon$', '$filter_class$' ),
							array( T_('Without status'), $status_clear_icon, $filter_classes[ $filter_class_i ] ),
							$params['filter_mask_nogroup'] );
					}
					elseif( $ItemStatus = & $ItemStatusCache->get_by_ID( $filter_status, false, false ) )
					{	// Specific status:
						$status_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $filter_status_param.'='.$ItemStatus->ID ) ) : '';
						$task_status_titles[] = str_replace( array( '$group_title$', '$filter_name$', '$clear_icon$', '$filter_class$' ),
							array( $params['status_text'], $ItemStatus->get_name(), $status_clear_icon, $filter_classes[ $filter_class_i ] ),
							$params['filter_mask'] );
					}
				}
				if( count( $task_status_titles ) > 0 )
				{
					$task_status_titles_string = $task_status_prefix.implode( $task_status_separator, $task_status_titles );
					$title_array['task_statuses'] = str_replace( array( '$group_title$', '$filter_items$' ),
						( count( $task_status_titles ) > 1 ?
							array( $params['statuses_text'], $params['before_items'].$task_status_titles_string.$params['after_items'] ) :
							array( $params['status_text'], $task_status_titles_string ) ),
						$params['group_mask'] );
					$filter_class_i++;
				}
			}
		}


		// VISIBILITY (SHOW STATUSES):
		if( $params['display_visibility'] )
		{
			if( !in_array( 'visibility', $ignore ) )
			{
				$post_statuses = get_visibility_statuses();
				if( count( $this->filters['visibility_array'] ) != count( $post_statuses ) )
				{	// Display it only when visibility filter is changed
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

		// ITEM TYPE:
		if( $params['display_itemtype'] )
		{
			$item_type_IDs = $this->filters['types'];

			if( !empty( $item_type_IDs ) && !in_array( 'itemtype', $ignore ) )
			{	// We want to show some Item Type names:
				$filter_class_i = ( $filter_class_i > count( $filter_classes ) - 1 ) ? 0 : $filter_class_i;
				$type_names = array();
				$ItemTypeCache = & get_ItemTypeCache();
				$invert = false;
				if( substr( $item_type_IDs, 0, 1) == '-' )
				{
					$invert = true;
					$item_type_IDs = substr( $item_type_IDs, 1 );
				}
				$item_type_IDs = explode(',', $this->filters['types']);
				$ItemTypeCache->load_list( $item_type_IDs, $invert );
				$item_type_IDs = $ItemTypeCache->get_ID_array();
				foreach( $item_type_IDs as $item_type_ID )
				{
					if( ( $tmp_ItemType = & $ItemTypeCache->get_by_ID( $item_type_ID, false, false ) ) !== false )
					{
						$type_clear_url = regenerate_url( $this->param_prefix.'types='.$item_type_ID );
						$type_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', $type_clear_url ) : '';
						$type_names[] = str_replace( array( '$group_title$', '$filter_name$', '$clear_icon$', '$filter_class$' ),
							array( $params['type_text'], $tmp_ItemType->name, $type_clear_icon, $filter_classes[ $filter_class_i ] ),
							$params['filter_mask'] );
					}
				}
				$filter_class_i++;
				$type_name_string = implode( $params['separator_and'], $type_names );
				$title_array[] = str_replace( array( '$group_title$', '$filter_items$' ),
					( count( $type_names ) > 1 ?
						array( $params['types_text'], $params['before_items'].$type_name_string.$params['after_items'] ) :
						array( $params['type_text'], $type_name_string ) ),
					$params['group_mask'] );
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
						if( ! in_array( 'hide_past', $ignore ) && ( $this->filters['ts_min'] != $this->default_filters['ts_min'] ) )
						{
							$time_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'show_future' ) ) : '';
							$title_array['ts_min'] = str_replace( array( '$filter_name$', '$clear_icon$', '$filter_class$' ),
								array( T_('Hide past'), $time_clear_icon, $filter_classes[ $filter_class_i ] ),
								$params['filter_mask_nogroup'] );
						}
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
						if( ! in_array( 'hide_future', $ignore ) && ( $this->filters['ts_max'] != $this->default_filters['ts_max'] ) )
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
			{	// Single post: no paging required!
			}
			elseif( !empty($this->filters['ymdhms']) )
			{	// no restriction if we request a month... some permalinks may point to the archive!
			}
			elseif( $this->filters['unit'] == 'posts' || $this->filters['unit'] == 'all' )
			{	// We're going to page, so there's no real limit here...
			}
			elseif( $this->filters['unit'] == 'days' )
			{	// We are going to limit to x days:
				// echo 'LIMIT DAYS ';
				$filter_class_i = ( $filter_class_i > count( $filter_classes ) - 1 ) ? 0 : $filter_class_i;
				if( empty( $this->filters['ymdhms_min'] ) )
				{	// We have no start date, we'll display the last x days:
					if( !empty($this->filters['keywords'])
						|| !empty($this->filters['cat_array'])
						|| !empty($this->filters['authors']) )
					{	// We are in DAYS mode but we can't restrict on these! (TODO: ?)
					}
					else
					{	// We are going to limit to LAST x days:
						// TODO: rename 'posts' to 'limit'
						$unit_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'unit' ) ) : '';
						$title_array['posts'] = str_replace( array( '$filter_name$', '$clear_icon$', '$filter_class$' ),
							array( sprintf( T_('Limited to last %d days'), $this->limit ), $unit_clear_icon, $filter_classes[ $filter_class_i ] ),
							$params['filter_mask_nogroup'] );
					}
				}
				else
				{	// We have a start date, we'll display x days starting from that point:
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


		// FLAGGED:
		if( $params['display_flagged'] )
		{
			if( ! empty( $this->filters['flagged'] ) )
			{	// Display when only flagged items:
				$filter_class_i = ( $filter_class_i > count( $filter_classes ) - 1 ) ? 0 : $filter_class_i;
				$unit_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'flagged' ) ) : '';
				$title_array['flagged'] = str_replace( array( '$filter_name$', '$clear_icon$', '$filter_class$' ),
					array( T_('Flagged'), $unit_clear_icon, $filter_classes[ $filter_class_i ] ),
					$params['filter_mask_nogroup'] );
				$filter_class_i++;
			}
		}


		// MUST READ:
		if( $params['display_mustread'] )
		{
			if( ! empty( $this->filters['mustread'] ) )
			{	// Display when only "must read" items:
				$filter_class_i = ( $filter_class_i > count( $filter_classes ) - 1 ) ? 0 : $filter_class_i;
				$unit_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'mustread' ) ) : '';
				$title_array['mustread'] = str_replace( array( '$filter_name$', '$clear_icon$', '$filter_class$' ),
					array( T_('Must read'), $unit_clear_icon, $filter_classes[ $filter_class_i ] ),
					$params['filter_mask_nogroup'] );
				$filter_class_i++;
			}
		}


		// RENDERERS:
		if( $params['display_renderer'] &&
		    ! empty( $this->filters['renderers'] ) )
		{
			global $Plugins;
			$filter_class_i = ( $filter_class_i > count( $filter_classes ) - 1 ) ? 0 : $filter_class_i;
			$task_renderer_titles = array();
			foreach( $this->filters['renderers'] as $renderer_plugin_code )
			{
				if( $renderer_Plugin = & $Plugins->get_by_code( $renderer_plugin_code, false, false ) )
				{
					$renderer_clear_icon = $clear_icon ? action_icon( T_('Remove this filter'), 'remove', regenerate_url( $this->param_prefix.'renderers='.$renderer_Plugin->code ) ) : '';
					$task_renderer_titles[] = str_replace( array( '$group_title$', '$filter_name$', '$clear_icon$', '$filter_class$' ),
						array( $params['renderer_text'], $renderer_Plugin->name, $renderer_clear_icon, $filter_classes[ $filter_class_i ] ),
						$params['filter_mask'] );
				}
			}
			if( count( $task_renderer_titles ) > 0 )
			{
				$task_renderer_titles_string = implode( $params['separator_or'], $task_renderer_titles );
				$title_array['task_renderers'] = str_replace( array( '$group_title$', '$filter_items$' ),
					( count( $task_renderer_titles ) > 1 ?
						array( $params['renderers_text'], $params['before_items'].$task_renderer_titles_string.$params['after_items'] ) :
						array( $params['renderer_text'], $task_renderer_titles_string ) ),
					$params['group_mask'] );
				$filter_class_i++;
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
				{	// We want to go to the end of the month:
					$m = $this->filters['ymdhms'];
					$this->advertised_stop_date = mktime( 0, 0, 0, substr($m,4,2)+1, 0, substr($m,0,4) ); // 0th day of next mont = last day of month
				}
				elseif( strlen( $this->filters['ymdhms'] ) == 4 )
				{	// We want to go to the end of the year:
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
		if( $this->current_Obj->get_type_setting( 'usage' ) == 'page' )
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


	/**
	 * Display items list depending on provided parameters
	 *
	 * @param array Parameters
	 * @return true|string TRUE on success displaying, String - error message on fail
	 */
	function display_list( $params )
	{
		global $Item, $cat;

		$params = array_merge( array(
				'template' => NULL,
				'highlight_current' => true,
			), $params );

		if( ! empty( $params['template'] ) )
		{	// DISPLAY with Quick TEMPLATE:

			// Check if template exists:
			$TemplateCache = & get_TemplateCache();
			if( ! ( $widget_Template = $TemplateCache->get_by_code( $params['template'], false, false ) ) )
			{
				return sprintf( 'Template not found: %s', '<code>'.$params['template'].'</code>' );
			}

			// Render MASTER quick template:
			// In theory, this should not display anything.
			// Instead, this should set variables to define sub-templates (and potentially additional variables)
			echo render_template_code( $params['template'], /* BY REF */ $params );

			// Check if requested sub-template exists:
			if( empty( $params['item_template'] ) )
			{	// Display error when no template for listing
				return sprintf( 'Missing %s param', '<code>item_template</code>' );
			}
			elseif( ! ( $item_Template = & $TemplateCache->get_by_code( $params['item_template'], false, false ) ) )
			{	// Display error when no or wrong template for listing
				return sprintf( 'Template is not found: %s for listing an item', '<code>'.$params['item_template'].'</code>' );
			}

			// Display list of Items:
			if( isset( $params['before_list'] ) )
			{
				echo $params['before_list'];
			}

			// ONLY SUPPORTING Plain list: (not grouped by category) for now
			// TODO: maybe support group by category. Use case???

			$item_template = $params['item_template'];

			if( ! empty( $params['highlight_current'] ) )
			{	// Use template for active Item only when requested to highlight currently active Item:
				$active_item_template = empty( $params['active_item_template'] ) ? $item_template : $params['active_item_template'];
				if( $active_item_template == $item_template ||
				    ! ( $active_item_Template = & $TemplateCache->get_by_code( $active_item_template, false, false ) ) )
				{	// If active item template is not found in DB then use normal item template instead:
					$active_item_template = $item_template;
				}
				// Highlight currently active Item ony when templates are different:
				$highlight_current_item = ( $active_item_template != $item_template );
			}
			else
			{	// Don't highlight currently active Item because it is not requested:
				$highlight_current_item = false;
			}

			$crossposted_item_template = empty( $params['crossposted_item_template'] ) ? $item_template : $params['crossposted_item_template'];
			if( $crossposted_item_template == $item_template ||
			    ! ( $crossposted_item_Template = & $TemplateCache->get_by_code( $crossposted_item_template, false, false ) ) )
			{	// If crossposted item template is not found in DB then use normal item template instead:
				$crossposted_item_template = $item_template;
			}

			$this->restart();
			while( $row_Item = & $this->get_item() )
			{
				if( ! empty( $params['switch_param_code'] ) )
				{	// Start wrapper to make each item block switchable:
					echo '<div data-display-condition="'.$params['switch_param_code'].'='.$row_Item->get( 'urltitle' ).'"'
						// Hide not active item on page loading:
						.( $params['active_item_slug'] == $row_Item->get( 'urltitle' ) ? '' : ' style="display:none"' ).'>';
				}

				if( $highlight_current_item &&
				    ! empty( $Item ) &&
				    $row_Item->ID == $Item->ID )
				{	// Use different template for currently active Item:
					$row_item_template = $active_item_template;
				}
				elseif( ! empty( $cat ) &&
				        $row_Item->main_cat_ID != $cat &&
				        in_array( $cat, $row_Item->get( 'extra_cat_IDs' ) ) )
				{	// Use different template for crossposted Item:
					$row_item_template = $crossposted_item_template;
				}
				else
				{	// Use normal template to not active Item:
					$row_item_template = $item_template;
				}

				// Render Item by quick template:
				echo render_template_code( $row_item_template, $params, array( 'Item' => $row_Item ) );

				if( ! empty( $params['switch_param_code'] ) )
				{	// End of switchable item block:
					echo '</div>';
				}
			}

			// TODO: maybe support $params['page'] & $params['pagination'] . Use case?

			if( isset( $params['after_list'] ) )
			{
				echo $params['after_list'];
			}

			return true;
		}

		// DISPLAY with "AUTOMATIC" template:

		// Load functions for widget layout:
		load_funcs( 'widgets/_widgets.funcs.php' );

		// Start to capture display content here in order to be able to detect if the whole widget must not be displayed
		ob_start();
		// This variable used to display widget. Will be set to true when content is displayed
		$content_is_displayed = false;

		if( $params['item_group_by'] == 'chapter' )
		{	// List grouped by chapter/category:

			$items_map_by_chapter = array();
			$chapters_of_loaded_items = array();
			$group_by_blogs = false;
			$prev_chapter_blog_ID = NULL;

			$this->restart();
			while( $iterator_Item = & $this->get_item() )
			{	// Display contents of the Item depending on widget params:
				$Chapter = & $iterator_Item->get_main_Chapter();
				if( ! isset( $items_map_by_chapter[$Chapter->ID] ) )
				{
					$items_map_by_chapter[$Chapter->ID] = array();
					$chapters_of_loaded_items[] = $Chapter;
				}
				$items_map_by_chapter[$Chapter->ID][] = $iterator_Item;
				// Group by blogs if there are chapters from multiple blogs
				if( ! $group_by_blogs && ( $Chapter->blog_ID != $prev_chapter_blog_ID ) )
				{	// group by blogs is not decided yet
					$group_by_blogs = ( $prev_chapter_blog_ID != NULL );
					$prev_chapter_blog_ID = $Chapter->blog_ID;
				}
			}

			usort( $chapters_of_loaded_items, 'Chapter::compare_chapters' );
			$displayed_blog_ID = NULL;

			if( $group_by_blogs && isset( $params['collist_start'] ) )
			{	// Start list of blogs
				echo $params['collist_start'];
			}
			else
			{	// Display list start, all chapters are in the same group ( not grouped by blogs )
				echo get_widget_layout_start( $params );
			}

			$item_index = 0;
			foreach( $chapters_of_loaded_items as $Chapter )
			{
				if( $group_by_blogs && $displayed_blog_ID != $Chapter->blog_ID )
				{
					$Chapter->get_Blog();
					if( $displayed_blog_ID != NULL )
					{	// Display the end of the previous blog's chapter list
						echo get_widget_layout_end( $item_index, $params );
					}
					echo $params['coll_start'].$Chapter->Blog->get('shortname'). $params['coll_end'];
					// Display start of blog's chapter list
					echo get_widget_layout_start( $params );
					$displayed_blog_ID = $Chapter->blog_ID;
				}
				// -------------
				$content_is_displayed = $this->display_list_chapter( $Chapter, $items_map_by_chapter, $item_index, $params ) || $content_is_displayed;
				// -------------
			}

			if( $content_is_displayed )
			{	// End of a chapter list - if some content was displayed this is always required
				echo get_widget_layout_end( $item_index, $params );
			}

			if( $group_by_blogs && isset( $params['collist_end'] ) )
			{	// End of blog list
				echo $params['collist_end'];
			}

		}
		else
		{	// Plain list: (not grouped by category)

			echo get_widget_layout_start( $params );

			$item_index = 0;
			$this->restart();
			while( $Item = & $this->get_item() )
			{
				// -------------
				// DISPLAY CONTENT of the Item depending on widget params:
				$content_is_displayed = $this->display_list_item_contents( $Item, false, $item_index, $params ) || $content_is_displayed;
				// -------------
			}

			if( isset( $params['page'] ) )
			{
				if( empty( $params['pagination'] ) )
				{
					$params['pagination'] = array();
				}
				$this->page_links( $params['pagination'] );
			}

			echo get_widget_layout_end( $item_index, $params );
		}

		if( $content_is_displayed )
		{	// Some content is displayed, Print out widget
			ob_end_flush();
		}
		else
		{	// No content, Don't display widget
			ob_end_clean();
		}

		return true;
	}


	/**
	 * Display a chapter with all of its loaded items
	 *
	 * @param Chapter
	 * @param array Items map by Chapter
	 * @param integer Item index
	 * @return boolean true if content was displayed, false otherwise
	 */
	function display_list_chapter( $Chapter, & $items_map_by_chapter, & $item_index, $params = array() )
	{
		$content_is_displayed = false;

		if( isset( $items_map_by_chapter[$Chapter->ID] ) && ( count( $items_map_by_chapter[$Chapter->ID] ) > 0 ) )
		{	// Display Chapter only if it has some items:
			echo get_widget_layout_item_start( 0, false, '', $params );
			$Chapter->get_Blog();
			echo '<a href="'.$Chapter->get_permanent_url().'">'.$Chapter->get('name').'</a>';

			echo $params['group_start'];

			$item_index = 0;
			foreach( $items_map_by_chapter[$Chapter->ID] as $iterator_Item )
			{	// Display contents of the Item depending on widget params:
				$content_is_displayed = $this->display_list_item_contents( $iterator_Item, true, $item_index, $params ) || $content_is_displayed;
			}

			// Close category group:
			echo $params['group_end'];
			echo get_widget_layout_item_end( 0, false, '', $params );
		}

		return $content_is_displayed;
	}


	/**
	 * Support function for above
	 *
	 * @param Item
	 * @param boolean set to true if Items are displayed grouped by chapters, false otherwise
	 * @param integer Item index
	 * @return boolean TRUE - if content is displayed
	 */
	function display_list_item_contents( & $disp_Item, $chapter_mode = false, & $item_index, $params = array() )
	{
		global $disp, $Item;

		// INIT:

		// Set this var to TRUE when some content(title, excerpt or picture) is displayed
		$content_is_displayed = false;

		// Set a 'group_' prefix for param keys if the items are grouped by chapters
		$disp_param_prefix = $chapter_mode ? 'group_' : '';

		// Is this the current item?
		if( ! empty( $params['highlight_current'] ) && ! empty( $Item ) && $disp_Item->ID == $Item->ID )
		{	// The current page is currently displaying the Item this link is pointing to
			// Let's display it as selected
			$link_class = $params['link_selected_class'];
		}
		else
		{	// Default link class
			$link_class = $params['link_default_class'];
		}

		$item_is_selected = ( $link_class == $params['link_selected_class'] );

		// DISPLAY START:

		// Start of Item block (Grid / flow / RWD)
		echo get_widget_layout_item_start( $item_index, $item_is_selected, $disp_param_prefix, $params );

		// DISPLAY CATEGORY:

		if( $params['disp_cat'] != 'no' )
		{	// Display categories:
			$disp_Item->categories( array(
					'before'           => $params['item_categories_before'],
					'after'            => $params['item_categories_after'],
					'separator'        => $params['item_categories_separator'],
					'include_main'     => true,
					'include_other'    => ( $params['disp_cat'] == 'all' ),
					'include_external' => ( $params['disp_cat'] == 'all' ),
					'link_categories'  => true,
				) );
		}

		// SPECIAL FIRST IMAGE:

		if( $params['disp_first_image'] == 'special' )
		{	// If we should display first picture before title then get "Cover" images and order them at top:
			$cover_image_params = array(
					'restrict_to_image_position' => 'cover,background,teaser,teaserperm,teaserlink,aftermore,inline',
					// Sort the attachments to get firstly "Cover", then "Teaser", and "After more" as last order
					'links_sql_select'  => ', CASE '
							.'WHEN link_position = "cover"      THEN "1" '
							.'WHEN link_position = "teaser"     THEN "2" '
							.'WHEN link_position = "teaserperm" THEN "3" '
							.'WHEN link_position = "teaserlink" THEN "4" '
							.'WHEN link_position = "aftermore"  THEN "5" '
							.'WHEN link_position = "inline"     THEN "6" '
							// .'ELSE "99999999"' // Use this line only if you want to put the other position types at the end
						.'END AS position_order',
					'links_sql_orderby' => 'position_order, link_order',
				);
		}
		else
		{
			$cover_image_params = array();
		}

		if( $params['attached_pics'] != 'none' && $params['disp_first_image'] == 'special' )
		{	// We want to display first image separately before the title
			// Display before/after even if there is no image so we can use it as a placeholder.
			$this->display_list_images( array_merge( $params, array(
					'before'      => $params['item_first_image_before'],
					'after'       => $params['item_first_image_after'],
					'placeholder' => $params['item_first_image_placeholder'],
					'Item'        => $disp_Item,
					'start'       => 1,
					'limit'       => 1,
				), $cover_image_params ),
				$content_is_displayed );
		}

		// DISPLAY ITEM TITLE:

		if( $params['disp_title'] )
		{	// Display title
			$disp_Item->title( array(
					'before'     => $params['disp_only_title'] ? $params['item_title_single_before'] : $params['item_title_before'],
					'after'      => $params['disp_only_title'] ? $params['item_title_single_after'] : $params['item_title_after'],
					'link_type'  => $params['item_title_link_type'],
					'link_class' => $link_class,
				) );
			$content_is_displayed = true;
		}

		// DISPLAY EXCERPT:

		if( $params['disp_excerpt'] )
		{	// Display excerpt
			$excerpt = $disp_Item->get_excerpt();

			$item_permanent_url = $disp_Item->get_permanent_url();
			if( ! $params['disp_teaser'] && $item_permanent_url !== false )
			{	// only display if there is no teaser to display
				$excerpt .= ' <a href="'.$item_permanent_url.'" class="'.$params['item_readmore_class'].'">'.$params['item_readmore_text'].'</a>';
			}

			if( !empty($excerpt) )
			{	// Note: Excerpts are plain text -- no html (at least for now)
				echo $params['item_excerpt_before'].$excerpt.$params['item_excerpt_after'];
				$content_is_displayed = true;
			}
		}

		// DISPLAY TEASER:

		if( $params['disp_teaser'] )
		{	// we want to show some or all of the post content
			$content = $disp_Item->get_content_teaser( 1, false, 'htmlbody' );

			if( $words = $params['disp_teaser_maxwords'] )
			{	// limit number of words:

				$content = strmaxwords( $content, $words, array(
						'continued_link'  => $disp_Item->get_permanent_url(),
						'continued_text'  => $params['item_readmore_text'],
						'continued_class' => $params['item_readmore_class'],
						'always_continue' => true, // Because Item::has_content_parts() is not optimized, we cannot be sure if the content has been cut because of max words or becaus eof [teaserbreak], so in doubt, we display a read more link all the time. Additionally: if there are images "after more", we also need the "more "link.
					 ) );
			}

			echo $params['item_content_before'].$content.$params['item_content_after'];
			$content_is_displayed = true;
		}

		// DISPLAY PICTURES:

		if( $params['attached_pics'] == 'all' ||
		   ( $params['attached_pics'] == 'first' && $params['disp_first_image'] == 'normal' ) ||
			 ( $params['attached_pics'] == 'category' && $params['disp_first_image'] == 'normal' ) )
		{	// Display attached pictures
			if( $params['attached_pics'] == 'first' || $params['attached_pics'] == 'category' )
			{	// Display only one first image:
				$picture_limit = 1;
			}
			else
			{
				$max_pics = intval( $params['max_pics'] );
				if( $max_pics > 0 )
				{	// Limit images after title with widget param:
					$picture_limit = $max_pics;
					if( $params['disp_first_image'] == 'special' )
					{	// If first image is already displayed before title, then we should skip this first to get next images:
						$picture_limit += 1;
					}
				}
				else
				{	// Don't limit the images:
					$picture_limit = 1000;
				}
			}
			$this->display_list_images( array_merge( $params, array(
					'before' => $params['item_images_before'],
					'after'  => $params['item_images_after'],
					'Item'   => $disp_Item,
					'start'  => ( $params['disp_first_image'] == 'special' ? 2 : 1 ), // Skip first image if it is displayed on top
					'limit'  => $picture_limit,
				), $cover_image_params ),
				$content_is_displayed );
		}

		++$item_index;

		// End of Item block (Grid / flow / RWD)
		echo get_widget_layout_item_end( $item_index, $item_is_selected, $disp_param_prefix, $params );

		return $content_is_displayed;
	}


	/**
	 * Display images of the selected item
	 *
	 * @todo Not sure if it makes sense that this reads attachment linklist directly
	 *
	 * @param array Params
	 * @param boolean Changed by reference when content is displayed
	 */
	function display_list_images( $params = array(), & $content_is_displayed )
	{
		$params = array_merge( array(
				'before'                     => '',
				'after'                      => '',
				'placeholder'                => '',
				'Item'                       => NULL,
				'start'                      => 1,
				'limit'                      => 1,
				'restrict_to_image_position' => 'teaser,teaserperm,teaserlink,aftermore,inline',
				'links_sql_select'           => '',
				'links_sql_orderby'          => 'link_order',
			), $params );

		$links_params = array(
				'sql_select_add' => $params['links_sql_select'],
				'sql_order_by'   => $params['links_sql_orderby']
			);

		$disp_Item = & $params['Item'];
		switch( $params[ 'item_pic_link_type' ] )
		{	// Set url for picture link
			case 'none':
				$pic_url = NULL;
				break;

			case 'permalink':
				$pic_url = $disp_Item->get_permanent_url();
				break;

			case 'linkto_url':
				$pic_url = $disp_Item->url;
				break;

			case 'auto':
			default:
				$pic_url = ( empty( $disp_Item->url ) ? $disp_Item->get_permanent_url() : $disp_Item->url );
				break;
		}

		if( $params['attached_pics'] != 'category' )
		{
			// Get list of ALL attached files:
			$LinkOwner = new LinkItem( $disp_Item );

			$images = '';

			if( $LinkList = $LinkOwner->get_attachment_LinkList( $params['limit'], $params['restrict_to_image_position'], 'image', $links_params ) )
			{	// Get list of attached files
				$image_num = 1;
				while( $Link = & $LinkList->get_next() )
				{
					if( ( $File = & $Link->get_File() ) && $File->is_image() )
					{	// Get only images
						if( $image_num < $params['start'] )
						{	// Skip these first images
							$image_num++;
							continue;
						}

						// Print attached picture
						$images .= $File->get_tag( '', '', '', '', $params['thumb_size'], $pic_url, '', '', '', '', '', '' );

						$content_is_displayed = true;

						$image_num++;
					}
				}
			}
		}

		$display_placeholder = true;
		if( ! empty( $images ) )
		{	// Print out images only when at least one exists:
			echo $params['before'];
			echo $images;
			echo $params['after'];
			$display_placeholder = false;
		}
		elseif( $params['limit'] == 1 )
		{	// First picture is empty, fallback to category picture:
			if( $main_Chapter = & $disp_Item->get_main_Chapter() )
			{	// If item has a main chapter:
				$main_chapter_image_tag = $main_Chapter->get_image_tag( array(
						'before'  => $params['before'],
						'after'   => $params['after'],
						'size'    => $params['thumb_size'],
						'link_to' => $pic_url,
					) );
				if( ! empty( $main_chapter_image_tag ) )
				{	// If main chapter has a correct image file:
					echo $main_chapter_image_tag;
					$display_placeholder = false;
				}
			}
		}

		if( $display_placeholder )
		{	// Display placeholder if no images:
			// Replace mask $item_permaurl$ with the item permanent URL:
			echo str_replace( '$item_permaurl$', $disp_Item->get_permanent_url(), $params['placeholder'] );
		}
	}
}
?>
