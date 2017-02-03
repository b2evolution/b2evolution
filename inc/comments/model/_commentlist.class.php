<?php
/**
 * This file implements the CommentList2 class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectlist2.class.php', 'DataObjectList2' );

/**
 * CommentList Class 2
 *
 * @package evocore
 */
/**
 * @author asimo
 *
 */
class CommentList2 extends DataObjectList2
{
	/**
	 * SQL object for the Query
	 */
	var $CommentQuery;

	/**
	 * SQL object for the ItemQuery
	 *
	 * This will be used to get those item Ids which comments should be listed
	 */
	var $ItemQuery;

	/**
	 * Blog object this CommentList refers to
	 */
	var $Blog;

	/**
	 * Constructor
	 *
	 * @param Blog This may be NULL only when must select comments from all Blog. Use NULL carefully, because it may generate very long queries!
	 * @param integer|NULL Limit
	 * @param string name of cache to be used
	 * @param string prefix to differentiate page/order params when multiple Results appear one same page
	 * @param string Name to be used when saving the filterset (leave empty to use default for collection)
	 */
	function __construct(
		$Blog,
		$limit = 1000,
		$cache_name = 'CommentCache',	// name of cache to be used
		$param_prefix = '',
		$filterset_name = ''			// Name to be used when saving the filterset (leave empty to use default for collection)
		)
	{
		global $Settings;

		// Call parent constructor:
		parent::__construct( get_Cache($cache_name), $limit, $param_prefix, NULL );

		// Set Blog. Note: It can be NULL on ?disp=usercomments
		$this->Blog = $Blog;

		// The SQL Query object:
		$this->CommentQuery = new CommentQuery(/* $this->Cache->dbtablename, $this->Cache->dbprefix, $this->Cache->dbIDname*/ );
		$this->CommentQuery->Blog = $this->Blog;

		// The Item filter SQL Query object:
		$this->ItemQuery = new ItemQuery( 'T_items__item', 'post_', 'post_ID' );
		// Blog can be NULL on ?disp=usercomments, in this case ItemQuery blog must be set to 0, which means all blog
		$this->ItemQuery->blog = empty( $this->Blog ) ? 0 : $this->Blog->ID;

		if( !empty( $filterset_name ) )
		{	// Set the filterset_name with the filterset_name param
			$this->filterset_name = 'CommentList_filters_'.$filterset_name;
		}
		else
		{	// Set a generic filterset_name
			$this->filterset_name = 'CommentList_filters_coll'.( !is_null( $this->Blog ) ? $this->Blog->ID : '0' );
		}

		$this->page_param = $param_prefix.'paged';

		// Initialize the default filter set:
		$this->set_default_filters( array(
				'filter_preset' => NULL,
				'author_IDs' => NULL,
				'author' => NULL,
				'author_email' => NULL,
				'author_url' => NULL,
				'url_match' => '=',
				'include_emptyurl' => NULL,
				'author_IP' => NULL,
				'post_ID' => NULL,
				'comment_ID' => NULL,
				'comment_ID_list' => NULL,
				'rating_toshow' => NULL,
				'rating_turn' => 'above',
				'rating_limit' => 1,
				'keywords' => NULL,
				'phrase' => 'AND',
				'exact' => 0,
				'statuses' => NULL,
				'expiry_statuses' => array( 'active' ), // Show active/expired comments
				'types' => array( 'comment','trackback','pingback' ),
				'orderby' => 'date',
				'order' => !is_null( $this->Blog ) ? $this->Blog->get_setting('comments_orderdir') : 'DESC',
				//'order' => 'DESC',
				'comments' => $this->limit,
				'page' => 1,
				'featured' => NULL,
				'timestamp_min' => NULL, // Do not show comments from posts before this timestamp
				'timestamp_max' => NULL, // Do not show comments from posts after this timestamp
				'threaded_comments' => false, // Mode to display the comment replies
				'user_perm' => NULL,
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
		$this->CommentQuery = new CommentQuery( $this->Cache->dbtablename, $this->Cache->dbprefix, $this->Cache->dbIDname );
		$this->CommentQuery->Blog = $this->Blog;
		$this->ItemQuery = new ItemQuery( 'T_items__item', 'post_', 'post_ID' );
		$this->ItemQuery->blog = empty( $this->Blog ) ? 0 : $this->Blog->ID;

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
		$this->limit = $this->filters['comments']; // for compatibility with parent class
		$this->page = $this->filters['page'];

		// asimo> memorize is always false for now, because is not fully implemented
		if( $memorize )
		{	// set back the GLOBALS !!! needed for regenerate_url() :

			/*
			 * Selected filter preset:
			 */
			memorize_param( $this->param_prefix.'filter_preset', 'string', $this->default_filters['filter_preset'], $this->filters['filter_preset'] );  // List of authors to restrict to

			/*
			 * Restrict to selected authors attribute:
			 */
			memorize_param( $this->param_prefix.'author_IDs', 'string', $this->default_filters['author_IDs'], $this->filters['author_IDs'] );  // List of authors ID to restrict to
			memorize_param( $this->param_prefix.'author', 'string', $this->default_filters['author'], $this->filters['author'] );  // List of authors ID to restrict to
			memorize_param( $this->param_prefix.'author_email', 'string', $this->default_filters['author_email'], $this->filters['author_email'] );  // List of authors email to restrict to
			memorize_param( $this->param_prefix.'author_url', 'string', $this->default_filters['author_url'], $this->filters['author_url'] );  // List of authors url to restrict to
			memorize_param( $this->param_prefix.'url_match', 'string', $this->default_filters['url_match'], $this->filters['url_match'] );  // List of authors url to restrict to
			memorize_param( $this->param_prefix.'include_emptyurl', 'string', $this->default_filters['include_emptyurl'], $this->filters['include_emptyurl'] );  // List of authors url to restrict to
			memorize_param( $this->param_prefix.'author_IP', 'string', $this->default_filters['author_IP'], $this->filters['author_IP'] );  // List of authors ip to restrict to

			/*
			 * Restrict to selected rating:
			 */
			memorize_param( $this->param_prefix.'rating_toshow', 'array', $this->default_filters['rating_toshow'], $this->filters['rating_toshow'] );  // Rating to restrict to
			memorize_param( $this->param_prefix.'rating_turn', 'string', $this->default_filters['rating_turn'], $this->filters['rating_turn'] );  // Rating to restrict to
			memorize_param( $this->param_prefix.'rating_limit', 'integer', $this->default_filters['rating_limit'], $this->filters['rating_limit'] );  // Rating to restrict to

			/*
			 * Restrict by keywords
			 */
			memorize_param( $this->param_prefix.'s', 'string', $this->default_filters['keywords'], $this->filters['keywords'] );			 // Search string
			memorize_param( $this->param_prefix.'sentence', 'string', $this->default_filters['phrase'], $this->filters['phrase'] ); // Search for sentence or for words
			memorize_param( $this->param_prefix.'exact', 'integer', $this->default_filters['exact'], $this->filters['exact'] );     // Require exact match of title or contents

			/*
			 * Restrict to selected statuses:
			 */
			memorize_param( $this->param_prefix.'show_statuses', 'array', $this->default_filters['statuses'], $this->filters['statuses'] );  // List of statuses to restrict to

			/*
			 * Restrict to not active/expired comments:
			 */
			memorize_param( $this->param_prefix.'expiry_statuses', 'array', $this->default_filters['expiry_statuses'], $this->filters['expiry_statuses'] );  // List of expiry statuses to restrict to

			/*
			 * Restrict to selected comment type:
			 */
			memorize_param( $this->param_prefix.'type', 'string', $this->default_filters['types'], $this->filters['types'] );  // List of comment types to restrict to

			/*
			 * Restrict to current User specific permission:
			 */
			memorize_param( $this->param_prefix.'user_perm', 'string', $this->default_filters['user_perm'], $this->filters['user_perm'] );  // Restrict to comments with permitted action for the current User

			/*
			 * Restrict to the statuses we want to show:
			 */
			// Note: oftentimes, $show_statuses will have been preset to a more restrictive set of values
			//memorize_param( $this->param_prefix.'show_statuses', 'array', $this->default_filters['visibility_array'], $this->filters['visibility_array'] );	// Array of sharings to restrict to

			/*
			 * OLD STYLE orders:
			 */
			memorize_param( $this->param_prefix.'order', 'string', $this->default_filters['order'], $this->filters['order'] );   		// ASC or DESC
			// This order style is OK, because sometimes the commentList is not displayed on a table so we cannot say we want to order by a specific column.
			memorize_param( $this->param_prefix.'orderby', 'string', $this->default_filters['orderby'], $this->filters['orderby'] );  // list of fields to order by (TODO: change that crap)

			/*
			 * Paging limits:
			 */
			memorize_param( $this->param_prefix.'comments', 'integer', $this->default_filters['comments'], $this->filters['comments'] ); 			// # of units to display on the page

			// 'paged'
			memorize_param( $this->page_param, 'integer', 1, $this->filters['page'] );      // List page number in paged display
		}
	}


	/**
	 * Init filter params from request params
	 *
	 * @param boolean do we want to use saved filters ?
	 * @return boolean true if we could apply a filterset based on Request params (either explicit or reloaded)
	 */
	function load_from_Request( $use_filters = true )
	{
		$this->filters = $this->default_filters;

		if( $use_filters )
		{
			// Do we want to restore filters or do we want to create a new filterset
			$filter_action = param( /*$this->param_prefix.*/'filter', 'string', 'save' );
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

		/*
		 * Restrict to selected author:
		 */
		$this->filters['author_IDs'] = param( $this->param_prefix.'author_IDs', '/^-?[0-9]+(,[0-9]+)*$/', $this->default_filters['author_IDs'], true );      // List of authors ID to restrict to
		$this->filters['author'] = param( $this->param_prefix.'author', '/^-?[0-9]+(,[0-9]+)*$/', $this->default_filters['author'], true );      // List of authors to restrict to
		$this->filters['author_email'] = param( $this->param_prefix.'author_email', 'string', $this->default_filters['author_email'], true );
		$this->filters['author_url'] = param( $this->param_prefix.'author_url', 'string', $this->default_filters['author_url'], true );
		$this->filters['url_match'] = param( $this->param_prefix.'url_match', 'string', $this->default_filters['url_match'], true );
		$this->filters['include_emptyurl'] = param( $this->param_prefix.'include_emptyurl', 'string', $this->default_filters['include_emptyurl'], true );
		$this->filters['author_IP'] = param( $this->param_prefix.'author_IP', 'string', $this->default_filters['author_IP'], true );

		/*
		 * Restrict to selected statuses:
		 */
		$this->filters['statuses'] = param( $this->param_prefix.'show_statuses', 'array:string', $this->default_filters['statuses'], true );      // List of statuses to restrict to

		/*
		 * Restrict to active/expired comments:
		 */
		$this->filters['expiry_statuses'] = param( $this->param_prefix.'expiry_statuses', 'array:string', $this->default_filters['expiry_statuses'], true );      // List of expiry statuses to restrict to

		/*
		 * Restrict to selected types:
		 */
		$this->filters['types'] = param( $this->param_prefix.'types', 'array:string', $this->default_filters['types'], true );      // List of types to restrict to

		/*
		 * Restrict to selected user perm:
		 */
		$this->filters['user_perm'] = param( $this->param_prefix.'user_perm', 'string', $this->default_filters['user_perm'], true );      // A specific user perm to restrict to

		/*
		 * Restrict by keywords
		 */
		$this->filters['keywords'] = param( $this->param_prefix.'s', 'string', $this->default_filters['keywords'], true );         // Search string
		$this->filters['phrase'] = param( $this->param_prefix.'sentence', 'string', $this->default_filters['phrase'], true ); 		// Search for sentence or for words
		$this->filters['exact'] = param( $this->param_prefix.'exact', 'integer', $this->default_filters['exact'], true );        // Require exact match of title or contents

		/*
		 * Restrict to selected rating:
		 */
		$this->filters['rating_toshow'] = param( $this->param_prefix.'rating_toshow', 'array:string', $this->default_filters['rating_toshow'], true );      // Rating to restrict to
		$this->filters['rating_turn'] = param( $this->param_prefix.'rating_turn', 'string', $this->default_filters['rating_turn'], true );      // Rating to restrict to
		$this->filters['rating_limit'] = param( $this->param_prefix.'rating_limit', 'integer', $this->default_filters['rating_limit'], true ); 	// Rating to restrict to

		// 'limit'
		$this->filters['comments'] = param( $this->param_prefix.'comments', 'integer', $this->default_filters['comments'], true ); 			// # of units to display on the page
		$this->limit = $this->filters['comments']; // for compatibility with parent class
		$this->filters['limit'] = $this->limit;

		// 'paged'
		$this->filters['page'] = param( $this->page_param, 'integer', 1, true );      // List page number in paged display
		$this->page = $this->filters['page'];

		$this->filters['order'] = param( $this->param_prefix.'order', '/^(ASC|asc|DESC|desc)$/', $this->default_filters['order'], true );   		// ASC or DESC
		// This order style is OK, because sometimes the commentList is not displayed on a table so we cannot say we want to order by a specific column. It's not a crap.
		$this->filters['orderby'] = param( $this->param_prefix.'orderby', '/^([A-Za-z0-9_]+([ ,][A-Za-z0-9_]+)*)?$/', $this->default_filters['orderby'], true );  // list of fields to order by (TODO: change that crap)

		if( $use_filters && $filter_action == 'save' )
		{
			$this->save_filterset();
		}

		return ! param_errors_detected();
	}


	/**
	 * Initialize sql query
	 *
	 * @todo count?
	 *
	 * @param boolean
	 */
	function query_init( $force_init = false )
	{
		global $DB;

		if( ! $force_init && ! empty( $this->query_is_initialized ) )
		{ // Don't initialize query because it was already done
			return;
		}

		// Save to know the query init was done
		$this->query_is_initialized = true;

		if( empty( $this->filters ) )
		{	// Filters have not been set before, we'll use the default filterset:
			// If there is a preset filter, we need to activate its specific defaults:
			$this->filters['filter_preset'] = param( $this->param_prefix.'filter_preset', 'string', $this->default_filters['filter_preset'], true );
			$this->activate_preset_filters();

			// Use the default filters:
			$this->set_filters( $this->default_filters );
		}

		// GENERATE THE QUERY:

		/*
		 * Resrict to selected blog
		 */
		// If we dont have specific comment or post ids, we have to restric to blog
		if( !is_null( $this->Blog ) &&
			( $this->filters['post_ID'] == NULL || ( ! empty($this->filters['post_ID']) && substr( $this->filters['post_ID'], 0, 1 ) == '-') ) &&
			( $this->filters['comment_ID'] == NULL || ( ! empty($this->filters['comment_ID']) && substr( $this->filters['comment_ID'], 0, 1 ) == '-') ) &&
			( $this->filters['comment_ID_list'] == NULL || ( ! empty($this->filters['comment_ID_list']) && substr( $this->filters['comment_ID_list'], 0, 1 ) == '-') ) )
		{ // restriction for blog
			$this->ItemQuery->where_chapter( $this->Blog->ID );
		}

		/*
		 * filtering stuff:
		 */
		$this->CommentQuery->where_author( $this->filters['author_IDs'] );
		$this->CommentQuery->where_author_email( $this->filters['author_email'] );
		$this->CommentQuery->where_author_url( $this->filters['author_url'], $this->filters['url_match'], $this->filters['include_emptyurl'] );
		$this->CommentQuery->where_author_IP( $this->filters['author_IP'] );
		$this->ItemQuery->where_ID( $this->filters['post_ID'] );
		$this->CommentQuery->where_ID( $this->filters['comment_ID'], $this->filters['author'] );
		$this->CommentQuery->where_ID_list( $this->filters['comment_ID_list'] );
		$this->CommentQuery->where_rating( $this->filters['rating_toshow'], $this->filters['rating_turn'], $this->filters['rating_limit'] );
		$this->CommentQuery->where_keywords( $this->filters['keywords'], $this->filters['phrase'], $this->filters['exact'] );
		$this->CommentQuery->where_statuses( $this->filters['statuses'] );
		$this->CommentQuery->where_types( $this->filters['types'] );
		$this->ItemQuery->where_datestart( '', '', '', '', $this->filters['timestamp_min'], $this->filters['timestamp_max'] );

		if( !is_null( $this->Blog ) && isset( $this->filters['user_perm'] ) )
		{ // If Blog and required user permission is set, add the corresponding restriction
			$this->CommentQuery->user_perm_restrict( $this->filters['user_perm'], $this->Blog->ID );
		}


		/*
		 * ORDER BY stuff:
		 */
		$available_sort_options = array( 'date', 'type', 'author', 'author_url', 'author_email', 'author_IP', 'spam_karma', 'status', 'item_ID' );
		$order_by = gen_order_clause( $this->filters['orderby'], $this->filters['order'], $this->Cache->dbprefix, $this->Cache->dbIDname, $available_sort_options );

		if( $this->filters['threaded_comments'] )
		{	// In mode "Threaded comments" we should get all replies in the begining of the list
			$order_by = $this->Cache->dbprefix.'in_reply_to_cmt_ID DESC, '.$order_by;
		}

		$this->CommentQuery->order_by( $order_by );

		// GET Item IDs, this way we don't have to JOIN two times the items and the categories table into the comment query
		if( isset( $this->filters['post_statuses'] ) )
		{ // Set post statuses by filters
			$post_show_statuses = $this->filters['post_statuses'];
		}
		elseif( is_admin_page() )
		{ // Allow all kind of post status ( This statuses will be filtered later by user perms )
			$post_show_statuses = get_visibility_statuses( 'keys' );
		}
		else
		{ // Allow only inskin statuses for posts
			$post_show_statuses = get_inskin_statuses( isset( $this->Blog ) ? $this->Blog->ID : NULL, 'post');
		}
		// Restrict post filters to available statuses. When blog = 0 we will check visibility statuses for each blog separately ( on the same query ).
		$this->ItemQuery->where_visibility( $post_show_statuses );
		$sql_item_IDs = 'SELECT DISTINCT post_ID'
						.$this->ItemQuery->get_from();
		if( strpos( $this->ItemQuery->get_from(), 'T_categories' ) === false &&
		    strpos( $this->ItemQuery->get_where(), 'cat_blog_ID' ) !== false )
		{ // Join categories table because it is required here for the field "cat_blog_ID"
			$sql_item_IDs .= ' INNER JOIN T_categories ON post_main_cat_ID = cat_ID ';
		}
		$sql_item_IDs .= $this->ItemQuery->get_where();
		// We use a sub-query for the list of post IDs because we do not want to pass 10000 item IDs back and forth between MySQL and PHP:
		$this->CommentQuery->WHERE_and( $this->CommentQuery->dbprefix.'item_ID IN ( '.$sql_item_IDs.' )' );

		/*
		 * Restrict to active comments by default, show expired comments only if it was requested
		 * Note: This condition makes the CommentQuery a lot slower!
		 */
		$this->CommentQuery->expiry_restrict( $this->filters['expiry_statuses'] );

		/*
		 * GET TOTAL ROW COUNT:
		 */
		$sql_count = '
				SELECT COUNT( '.$this->Cache->dbIDname.') '
					.$this->CommentQuery->get_from()
					.$this->CommentQuery->get_where();

		parent::count_total_rows( $sql_count );

		/*
		 * Page set up:
		 */
		if( $this->page > 1 )
		{ // We have requested a specific page number
			if( $this->limit > 0 )
			{
				$pgstrt = '';
				$pgstrt = (intval($this->page) -1) * $this->limit. ', ';
				$this->CommentQuery->LIMIT( $pgstrt.$this->limit );
			}
		}
		else
		{
			$this->CommentQuery->LIMIT( $this->limit );
		}
	}


	/**
	 * Run Query: GET DATA ROWS *** HEAVY ***
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
	 * Run Query: GET DATA ROWS *** HEAVY ***
	 */
	function run_query( $create_default_cols_if_needed = true, $append_limit = true, $append_order_by = true,
											$query_title = 'Results::run_query()' )
	{
		global $DB;

		if( !is_null( $this->rows ) )
		{ // Query has already executed:
			return;
		}

		// INIT THE QUERY:
		$this->query_init();

		// Results style orders:
		// $this->CommentQuery->ORDER_BY_prepend( $this->get_order_field_list() );


		// We are going to proceed in two steps (we simulate a subquery)
		// 1) we get the IDs we need
		// 2) we get all the other fields matching these IDs
		// This is more efficient than manipulating all fields at once.

		// *** STEP 1 ***
		// walter> Accordding to the standart, to DISTINCT queries, all columns used
		// in ORDER BY must appear in the query. This make que query work with PostgreSQL and
		// other databases.
		// fp> That can dramatically fatten the returned data. You must handle this in the postgres class (check that order fields are in select)
		// asimo> Note: DISTINCT was removed from the query because we should use DISTINCT only in those cases when the same field value may occur more then one times ( This is not the case )
		$step1_sql = 'SELECT '.$this->Cache->dbIDname // .', '.implode( ', ', $order_cols_to_select )
									.$this->CommentQuery->get_from()
									.$this->CommentQuery->get_where()
									.$this->CommentQuery->get_group_by()
									.$this->CommentQuery->get_order_by()
									.$this->CommentQuery->get_limit();

		// Get list of the IDs we need:
		$ID_list = implode( ',', $DB->get_col( $step1_sql, 0, 'CommentList2::Query() Step 1: Get ID list' ) );

		// *** STEP 2 ***
		$this->sql = 'SELECT *
			              FROM '.$this->Cache->dbtablename;
		if( !empty($ID_list) )
		{
			$this->sql .= ' WHERE '.$this->Cache->dbIDname.' IN ('.$ID_list.') '
										.$this->CommentQuery->get_order_by();
		}
		else
		{
			$this->sql .= ' WHERE 0';
		}

		parent::run_query( false, false, false, 'CommentList2::Query() Step 2' );
	}


	/**
	 * Generate a title for the current list, depending on its filtering params
	 *
	 * @return array List of titles to display, which are escaped for HTML display
	 */
	function get_filter_titles( $ignore = array(), $params = array() )
	{
		$title_array = array();

		if( empty ($this->filters) )
		{ // Filters have no been set before, we'll use the default filterset
			$this->set_filters( $this->default_filters );
		}

		if( isset( $this->filters['statuses'] ) )
		{
			$visibility_statuses = get_visibility_statuses( '', array( 'redirected' ) );

			$visibility_array = array();
			foreach( $this->filters['statuses'] as $status )
			{
				$visibility_array[] = $visibility_statuses[ $status ];
			}
			$title_array['statuses'] = T_('Visibility').': '.implode( ', ', $visibility_array );
		}

		if( !empty($this->filters['keywords']) )
		{
			$title_array['keywords'] = T_('Keywords').': '.$this->filters['keywords'];
		}

		return $title_array;
	}


	/**
	 * If the list is sorted by category...
 	 *
 	 * This is basically just a stub for backward compatibility
	 */
	function & get_Comment()
	{
		$Comment = & parent::get_next();

		if( empty($Comment) )
		{
			$r = false;
			return $r;
		}

		//pre_dump( $Comment );

		return $Comment;
	}


	/**
	 * Template function: display message if list is empty
	 *
	 * @return boolean true if empty
	 */
	function display_if_empty( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'msg_empty'   => T_('No comment yet...'),
			), $params );

		return parent::display_if_empty( $params );
	}


	/**
	 * Template tag
	 *
	 * Display page links (when paginated comments are enabled)
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
				'page_item_before' => '',
				'page_item_after'  => '',
				'page_current_template' => '<strong class="current_page">$page_num$</strong>',
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

		if( !is_null( $this->Blog ) && $this->Blog->get_setting( 'paged_nofollowto' ) )
		{	// We prefer robots not to follow to pages:
			$this->nofollow_pagenav = true;
		}

		echo $params['block_start'];
		echo $this->replace_vars( $params['links_format'], $params );
		echo $params['block_end'];
	}


	/**
	 * Returns values needed to make sort links for a given column
	 * This is needed because the order is not handled by the result class.
	 * Reason: Sometimes the comment list needs to be ordered without having a display table, and columns. The result class order is based on columns.
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

		// Current order:
		if( $this->filters['orderby'] == $col_order_fields || $this->param_prefix.$this->filters['orderby'] == $col_order_fields  )
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
	 * Checks if currently selected filter contains comments with trash status
	 *
	 * @param boolean set true to check if the filter contains only recycled comments, set false to check if the filter contains the recycled comments
	 * @return boolean
	 */
	function is_trashfilter( $only_trash = true )
	{
		if( ! $only_trash )
		{ // Check if statuses filter contains the 'trash' value
			return is_array( $this->filters['statuses'] ) && in_array( 'trash', $this->filters['statuses'] );
		}
		if( count( $this->filters['statuses'] ) == 1 )
		{ // Check if statuses filter contains only the 'trash' value
			return $this->filters['statuses'][0] == 'trash';
		}
		return false;
	}


	/**
	 * Auto pruning of recycled comments.
	 *
	 * It uses a general setting to store the day of the last prune, avoiding multiple prunes per day.
	 * fplanque>> Check: How much faster is this than DELETING right away with an INDEX on the date field?
	 *
	 * NOTE: do not call this directly, but only in conjuction with auto_prune_stats_mode.
	 *
	 * @return string Empty, if ok.
	 */
	static function dbprune()
	{
		/**
		 * @var DB
		 */
		global $DB;
		global $Debuglog, $Settings, $localtimenow;

		// Prune when $localtime is a NEW day (which will be the 1st request after midnight):
		$last_prune = $Settings->get( 'auto_empty_trash_done' );
		if( $last_prune >= date('Y-m-d', $localtimenow) && $last_prune <= date('Y-m-d', $localtimenow+86400) )
		{ // Already pruned today (and not more than one day in the future -- which typically never happens)
			return T_('Pruning of recycled comments has already been done today');
		}

		$time_prune_before = $localtimenow - ( $Settings->get('auto_empty_trash') * 86400 ); // 1 day = 86400 seconds

		$rows_affected = Comment::db_delete_where( 'Comment', 'comment_status = "trash"
			AND comment_last_touched_ts < '.$DB->quote( date2mysql( $time_prune_before ) ) );
		$Debuglog->add( 'CommentList2::dbprune(): autopruned '.$rows_affected.' rows from T_comments.', 'request' );

		// Optimizing tables
		$DB->query('OPTIMIZE TABLE T_comments');

		$Settings->set( 'auto_empty_trash_done', date('Y-m-d H:i:s', $localtimenow) ); // save exact datetime
		$Settings->dbupdate();

		return ''; /* ok */
	}


	/**
	 * Get next object in list
	 */
	function & get_next()
	{
		$Comment = & parent::get_next();

		if( empty( $Comment ) )
		{
			$r = false;
			return $r;
		}

		return $Comment;
	}


	/**
	 * Load data of Comments from the current page at once to cache variables.
	 * For each loading we use only single query to optimize performance.
	 * By default it loads all Comments of current list page into global $CommentCache,
	 * Other data are loaded depending on $params, see below:
	 *
	 * @param array Params:
	 *        - 'load_votes'      - use TRUE to load the votes(spam and helpful) of the current
	 *                              logged in User for all Comments of current list page.
	 *        - 'load_items_data' - use TRUE to load all Items of the current list page Comments
	 *                              into global $ItemCache and category associations for these Items.
	 *        - 'load_links'      - use TRUE to load all Links of the current list page Comments
	 *                              into global $LinkCache, also it loads Files of these Links into global $FileCache.
	 */
	function load_list_data( $params = array() )
	{
		$params = array_merge( array(
				'load_votes'      => true,
				'load_items_data' => true,
				'load_links'      => true,
			), $params );

		$page_comment_ids = $this->get_page_ID_array();
		if( empty( $page_comment_ids ) )
		{	// There are no items on this list:
			return;
		}

		// Load all comments of the current page in single query:
		$CommentCache = & get_CommentCache();
		$CommentCache->load_list( $page_comment_ids );

		if( $params['load_votes'] )
		{	// Load the vote statuses:
			$this->load_vote_statuses();
		}

		if( $params['load_links'] )
		{	// Load the links:
			$LinkCache = & get_LinkCache();
			$LinkCache->load_by_comment_list( $page_comment_ids );
		}
		

		if( $params['load_items_data'] )
		{	// Load items data:
			$comment_items_IDs = array();
			foreach( $CommentCache->cache as $Comment )
			{
				if( $Comment )
				{
					$comment_items_IDs[] = $Comment->get( 'item_ID' );
				}
			}

			if( count( $comment_items_IDs ) )
			{	// Load all items of the current page in single query:
				$ItemCache = & get_ItemCache();
				$ItemCache->load_list( $comment_items_IDs );

				// Load category associations for the items of current page:
				postcats_get_by_IDs( $comment_items_IDs );
			}
		}
	}


	/**
	 * Load the vote statuses for current user and comments of the current page list
	 */
	function load_vote_statuses()
	{
		global $current_User, $DB, $cache_comments_vote_statuses;

		if( ! is_logged_in() )
		{	// Current user must be logged in:
			return;
		}

		$page_comment_ids = $this->get_page_ID_array();
		if( empty( $page_comment_ids ) )
		{	// There are no items on this list:
			return;
		}

		if( ! is_array( $cache_comments_vote_statuses ) )
		{	// Initialize array first time:
			$cache_comments_vote_statuses = array();
		}

		$not_cached_comment_ids = array_diff( $page_comment_ids, array_keys( $cache_comments_vote_statuses ) );

		if( empty( $not_cached_comment_ids ) )
		{	// The vote statuses are loaded for all comments:
			return;
		}

		// Load the vote statuses from DB and cache into global cache array:
		$SQL = new SQL( 'Load the vote statuses for current user and comments of the current page list' );
		$SQL->SELECT( 'cmvt_cmt_ID AS ID, cmvt_spam AS spam, cmvt_helpful AS helpful' );
		$SQL->FROM( 'T_comments__votes' );
		$SQL->WHERE( 'cmvt_cmt_ID IN ( '.$DB->quote( $not_cached_comment_ids ).' )' );
		$SQL->WHERE_and( 'cmvt_user_ID = '.$DB->quote( $current_User->ID ) );
		$comments_vote_statuses = $DB->get_results( $SQL->get(), ARRAY_A, $SQL->title );

		// Load all existing votes into cache variable:
		foreach( $comments_vote_statuses as $comments_vote_status )
		{
			$vote_status_comment_ID = $comments_vote_status['ID'];
			unset( $comments_vote_status['ID'] );
			$cache_comments_vote_statuses[ $vote_status_comment_ID ] = $comments_vote_status;
		}

		// Set all unexiting votes for requested comments in order to don't repeat SQL queries later:
		foreach( $not_cached_comment_ids as $not_cached_comment_ID )
		{
			if( ! isset( $cache_comments_vote_statuses[ $not_cached_comment_ID ] ) )
			{
				$cache_comments_vote_statuses[ $not_cached_comment_ID ] = false;
			}
		}
	}
}

?>