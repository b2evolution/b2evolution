<?php
/**
 * This file implements the ItemList class 2.
 *
 * This is the object handling item/post/article lists.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '/items/model/_itemlistlight.class.php', 'ItemListLight' );


/**
 * Item List Class 2
 *
 * This SECOND implementation will deprecate the first one when finished.
 *
 * @package evocore
 */
class ItemList2 extends ItemListLight
{
	/**
	 * @var array
	 */
	var $prevnext_Item = array();

	/**
	 * Navigate through this target items
	 * It can be an Chapter ID, a User ID or Tag name, depends from the post_navigation coll settings
	 *
	 * @var integer
	 */
	var $nav_target;

	/**
	 * Constructor
	 *
	 * @todo  add param for saved session filter set
	 *
	 * @param Blog
	 * @param mixed Default filter set: Do not show posts before this timestamp, can be 'now'
	 * @param mixed Default filter set: Do not show posts after this timestamp, can be 'now'
	 * @param integer|NULL Limit
	 * @param string name of cache to be used
	 * @param string prefix to differentiate page/order params when multiple Results appear one same page
	 * @param string Name to be used when saving the filterset (leave empty to use default for collection)
	 * @param array restrictions for itemlist (position, contact, firm, ...) key: restriction name, value: ID of the restriction
	 */
	function __construct(
			& $Blog,
			$timestamp_min = NULL,       // Do not show posts before this timestamp
			$timestamp_max = NULL,   		 // Do not show posts after this timestamp
			$limit = 20,
			$cache_name = 'ItemCache',	 // name of cache to be used
			$param_prefix = '',
			$filterset_name = ''				// Name to be used when saving the filterset (leave empty to use default for collection)
		)
	{
		global $Settings;

		// Call parent constructor:
		parent::__construct( $Blog, $timestamp_min, $timestamp_max, $limit, $cache_name, $param_prefix, $filterset_name );
	}


	/**
	 * We want to preview a single post, we are going to fake a lot of things...
	 */
	function preview_from_request()
	{
		if( ! is_logged_in() )
		{ // dh> only logged in user's can preview. Alternatively we need those checks where $current_User gets used below.
			return;
		}

		global $current_User, $DB, $localtimenow, $Blog, $Plugins;

		$post_ID = param( 'post_ID', 'integer', 0 );

		// Get Item by ID or create new Item object:
		$ItemCache = & get_ItemCache();
		if( ! ( $Item = & $ItemCache->get_by_ID( $post_ID, false, false ) ) )
		{	// Initialize new creating Item:
			$Item = new Item();
		}

		param( 'item_typ_ID', 'integer', true );

		$Item->status = param( 'post_status', 'string', NULL ); // 'published' or 'draft' or ...
		// We know we can use at least one status,
		// but we need to make sure the requested/default one is ok:
		$Item->status = $Blog->get_allowed_item_status( $Item->status );

		// Check if new category was started to create. If yes then set up parameters for next page:
		check_categories_nosave( $post_category, $post_extracats, $Item );

		// Set main category to avoid error with new creating Item:
		$Item->set( 'main_cat_ID', $post_category );

		// Switch to current user's locale to display errors:
		locale_temp_switch( $current_User->locale );

		// Set Item params from request:
		$Item->load_from_Request();

		if( isset( $Item->previous_status ) )
		{	// Restrict Item status by Collection access restriction AND by CURRENT USER write perm:
			// (ONLY if current request is updating item status)
			$Item->restrict_status( true );
		}

		locale_restore_previous();

		// Initialize SQL query to preview Item:
		$post_fields = $DB->get_col( 'SHOW COLUMNS FROM T_items__item', 0, 'Get all item columns to init SQL query for preview the creating/editing Item' );
		$sql_post_fields = array();
		$post_dbprefix_length = strlen( $Item->dbprefix );
		foreach( $post_fields as $post_field )
		{
			switch( $post_field )
			{
				case 'post_lastedit_user_ID':
					$post_field_value = $current_User->ID;
					break;

				case 'post_datemodified':
				case 'post_last_touched_ts':
				case 'post_contents_last_updated_ts':
					$post_field_value = date2mysql( $localtimenow );
					break;

				default:
					$post_field_name = substr( $post_field, $post_dbprefix_length );
					$post_field_value = isset( $Item->$post_field_name ) ? $Item->$post_field_name : NULL;
					break;
			}
			$sql_post_fields[] = "\n".$DB->quote( $post_field_value ).' AS '.$post_field;
		}
		// Create a fake SQL query(to initialize the previewing Item) like "SELECT 123 AS post_ID, 'Post Title text' AS post_title, ...":
		$this->sql = 'SELECT '.implode( ', ', $sql_post_fields );

		$this->total_rows = 1;
		$this->total_pages = 1;
		$this->page = 1;

		// Skip the function of this class and call it of the parent because we have already initialized SQL query above in this function:
		DataObjectList2::run_query( false, false, false, 'ItemList2::preview_from_request() PREVIEW QUERY' );

		// Trigger plugin event, allowing to manipulate or validate the item before it gets previewed
		$Plugins->trigger_event( 'AppendItemPreviewTransact', array( 'Item' => & $Item ) );

		// little funky fix for IEwin, rawk on that code
		global $Hit;
		if( ($Hit->is_winIE()) && (!isset($IEWin_bookmarklet_fix)) )
		{ // QUESTION: Is this still needed? What about $IEWin_bookmarklet_fix? (blueyed)
			$Item->content = preg_replace('/\%u([0-9A-F]{4,4})/e', "'&#'.base_convert('\\1',16,10). ';'", $Item->content);
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

		// Check the number of totla rows after it was initialized in the query_init() function
		if( isset( $this->total_rows ) && ( intval( $this->total_rows ) === 0 ) )
		{ // Count query was already executed and returned 0
			return;
		}

		// asimo> This must be removed if the option to 'always move the null values in the end of the result' will be implemented
		$select_temp_order = '';
		if( !empty( $this->ItemQuery->order_by ) && strpos( $this->ItemQuery->order_by, 'post_order' ) !== false )
		{	// Move the items with NULL order to the end of the list
			$select_temp_order = ', IF( post_order IS NULL, 999999999, post_order ) AS temp_order';
			$this->ItemQuery->ORDER_BY( str_replace( 'post_order', 'temp_order', $this->ItemQuery->get_order_by( '' ) ) );
		}

		// Results style orders:
		// $this->ItemQuery->ORDER_BY_prepend( $this->get_order_field_list() );


		// We are going to proceed in two steps (we simulate a subquery)
		// 1) we get the IDs we need
		// 2) we get all the other fields matching these IDs
		// This is more efficient than manipulating all fields at once.

		// *** STEP 1 ***
		// walter> Accordding to the standart, to DISTINCT queries, all columns used
		// in ORDER BY must appear in the query. This make que query work with PostgreSQL and
		// other databases.
		// fp> That can dramatically fatten the returned data. You must handle this in the postgres class (check that order fields are in select)
		$step1_sql = 'SELECT DISTINCT '.$this->Cache->dbIDname // .', '.implode( ', ', $order_cols_to_select )
									.$select_temp_order
									.$this->ItemQuery->get_from()
									.$this->ItemQuery->get_orderby_from( ' ' )
									.$this->ItemQuery->get_where()
									.$this->ItemQuery->get_group_by()
									.$this->ItemQuery->get_order_by()
									.$this->ItemQuery->get_limit();

		// echo DB::format_query( $step1_sql );

		// Get list of the IDs we need:
		$ID_list = implode( ',', $DB->get_col( $step1_sql, 0, ( empty( $this->query_title_prefix ) ? '' : $this->query_title_prefix.' - ' ).'ItemList2::Query() Step 1: Get ID list' ) );

		// *** STEP 2 ***
		$this->sql = 'SELECT *'.$select_temp_order.'
			              FROM '.$this->Cache->dbtablename.$this->ItemQuery->get_orderby_from( ' ' );

		if( isset( $this->filters['orderby'] ) && $this->filters['orderby'] == 'numviews' )
		{ // special case for order by number of views
			//$this->sql .= ' LEFT JOIN ( SELECT itud_item_ID, COUNT(*) AS '.$this->Cache->dbprefix.'numviews FROM T_items__user_data GROUP BY itud_item_ID ) AS numviews
			//		ON '.$this->Cache->dbIDname.' = numviews.itud_item_ID';
		}

		if( !empty($ID_list) )
		{
			$this->sql .= ' WHERE '.$this->Cache->dbIDname.' IN ('.$ID_list.') '
										.$this->ItemQuery->get_order_by();
		}
		else
		{
			$this->sql .= ' WHERE 0';
		}

		//echo DB::format_query( $this->sql );

		// Skip the function of first parent and call it of main parent because we have already initialized SQL query above in this function:
		DataObjectList2::run_query( false, false, false, 'ItemList2::Query() Step 2' );
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

		$Item = & parent::get_next();

		if( !empty($Item) && $this->group_by_cat == 2 && $Item->main_cat_ID != $this->main_cat_ID )
		{	// We have just hit a new category!
			$this->group_by_cat == 0; // For info only.
			$r = false;
			return $r;
		}

		return $Item;
	}


	/**
	 * Get all tags used in current ItemList
	 *
	 * @todo caching in case of multiple calls
	 *
	 * @return array
	 */
	function get_all_tags()
	{
		$all_tags = array();

		for( $i=0; $i<$this->result_num_rows; $i++ )
		{
			/**
			 * @var Item
			 */
			$l_Item = & $this->get_by_idx( $i );
			$l_tags = $l_Item->get_tags();
			$all_tags = array_merge( $all_tags, $l_tags );
		}

		// Keep each tag only once:
		$all_tags = array_unique( $all_tags );

		return $all_tags;
	}



	/**
	 * Returns values needed to make sort links for a given column
	 * Needed because the order is not handled by the result class.
	 * Reason: Sometimes the item list needs to be ordered without having a display table, and columns. The result class order is based on columns.
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
		if( $this->filters['orderby'] == $col_order_fields || $this->param_prefix.$this->filters['orderby'] == $col_order_fields )
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
	 * Link to previous and next link in collection
	 */
	function prevnext_item_links( $params )
	{
		$params = array_merge( array(
									'template' => '$prev$$separator$$next$',
									'prev_start' => '',
									'prev_text' => '&laquo; $title$',
									'prev_end' => '',
									'prev_no_item' => '',
									'prev_class' => '',
									'separator' => '',
									'next_start' => '',
									'next_text' => '$title$ &raquo;',
									'next_end' => '',
									'next_no_item' => '',
									'next_class' => '',
									'target_blog' => '',
									'post_navigation' => $this->Blog->get_setting( 'post_navigation' ),
									'itemtype_usage' => 'post', // Include only post with type usage "post"
									'featured' => NULL,
								), $params );

		$current_Item = & $this->get_by_idx(0);
		// Note: current_Item may be null when User doesn't have permission
		if( $current_Item )
		{ // current Item is available, init navigation target
			switch( $params['post_navigation'] )
			{
				case 'same_category': // sometimes requires the 'cat' param because a post may belong to multiple categories
					if( empty( $this->nav_target ) )
					{
						$this->nav_target = $current_Item->main_cat_ID;
					}
					$this->filters['cat_array'][] = $this->nav_target;
					// Note: If there will be other navigation type ( like tag ) with params, those filters must be removed.
					break;

				case 'same_author': // This doesn't require extra param because a post always has only one author
					$this->filters['authors'] = $current_Item->creator_user_ID;
					// reset cat filters because only the authors are important
					$this->filters['cat_array'] = array();
					break;

				case 'same_tag': // sometimes requires the 'tag' param because a post may belong to multiple tags
					if( empty( $this->nav_target ) )
					{
						$tags = $current_Item->get_tags();
						if( count( $tags ) > 0 )
						{
							$this->nav_target = $tags[0];
						}
					}
					if( !empty( $this->nav_target ) )
					{
						$this->filters['tags'] = $this->nav_target;
					}
					// reset cat filters because only the tags are important
					$this->filters['cat_array'] = array();
					break;

				default:
					break;
			}
		}

		$prev = $this->prev_item_link( $params['prev_start'], $params['prev_end'], $params[ 'prev_text' ], $params[ 'prev_no_item' ], false, $params[ 'target_blog'], $params['prev_class'], $params['itemtype_usage'], $params['featured'], $params['post_navigation'] );
		$next = $this->next_item_link( $params['next_start'], $params['next_end'], $params[ 'next_text' ], $params[ 'next_no_item' ], false, $params[ 'target_blog'], $params['next_class'], $params['itemtype_usage'], $params['featured'], $params['post_navigation'] );

		if( empty( $prev ) || empty( $next ) )
		{	// Use separator text only when prev & next are not empty
			$params['separator'] = '';
		}

		$output = str_replace( '$prev$', $prev, $params['template'] );
		$output = str_replace( '$next$', $next, $output );
		$output = str_replace( '$separator$', $params['separator'], $output );

		if( !empty( $output ) )
		{	// we have some output, lets wrap it
			echo( $params['block_start'] );
			echo $output;
			echo( $params['block_end'] );
		}
	}


	/**
	 * Skip to previous
	 */
	function prev_item_link( $before = '', $after = '', $text = '&laquo; $title$', $no_item = '', $display = true, $target_blog = '', $class = '', $itemtype_usage = '', $featured = NULL, $post_navigation = NULL )
	{
		/**
		 * @var Item
		 */
		$prev_Item = & $this->get_prevnext_Item( 'prev', $itemtype_usage, $featured, $post_navigation );

		if( !is_null($prev_Item) )
		{
			$output = $before;
			$output .= $prev_Item->get_permanent_link( $text, '', $class, $target_blog, $post_navigation, $this->nav_target );
			$output .= $after;
		}
		else
		{
			$output = $no_item;
		}
		if( $display ) echo $output;
		return $output;
	}


	/**
	 * Skip to next
	 */
	function next_item_link( $before = '', $after = '', $text = '$title$ &raquo;', $no_item = '', $display = true, $target_blog = '', $class = '', $itemtype_usage = '', $featured = true, $post_navigation = NULL )
	{
		/**
		 * @var Item
		 */
		$next_Item = & $this->get_prevnext_Item( 'next', $itemtype_usage, $featured, $post_navigation );

		if( !is_null($next_Item) )
		{
			$output = $before;
			$output .= $next_Item->get_permanent_link( $text, '', $class, $target_blog, $post_navigation, $this->nav_target );
			$output .= $after;
		}
		else
		{
			$output = $no_item;
		}
		if( $display ) echo $output;
		return $output;
	}


	/**
	 * Generate the permalink for the previous item in collection.
	 *
	 * Note: Each item has an unique permalink at any given time.
	 * Some admin settings may however change the permalinks for previous items.
	 * Note: This actually only returns the URL, to get a real link, use {@link ItemList::prev_item_link()}
	 *
	 * @param string single, archive, subchap
	 * @param string base url to use
	 * @param string glue between url params
	 */
	function get_prev_item_url( $permalink_type = '', $blogurl = '', $glue = '&amp;' )
	{
		/**
		 * @var Item
		 */
		$prev_Item = & $this->get_prevnext_Item( 'prev' );

		if( !is_null($prev_Item) )
		{
			return $prev_Item->get_permanent_url( $permalink_type, $blogurl, $glue );
		}
		return '';
	}


	/**
	 * Generate the permalink for the next item in collection.
	 *
	 * Note: Each item has an unique permalink at any given time.
	 * Some admin settings may however change the permalinks for previous items.
	 * Note: This actually only returns the URL, to get a real link, use {@link ItemList::next_item_link()}
	 *
	 * @param string single, archive, subchap
	 * @param string base url to use
	 * @param string glue between url params
	 */
	function get_next_item_url( $permalink_type = '', $blogurl = '', $glue = '&amp;' )
	{
		/**
		 * @var Item
		 */
		$next_Item = & $this->get_prevnext_Item( 'next' );

		if( !is_null($next_Item) )
		{
			return $next_Item->get_permanent_url( $permalink_type, $blogurl, $glue );
		}
		return '';
	}


	/**
	 * Skip to previous/next Item
	 *
	 * If several items share the same spot (like same issue datetime) then they'll get all skipped at once.
	 *
	 * @param string prev | next  (relative to the current sort order)
	 */
	function & get_prevnext_Item( $direction = 'next', $itemtype_usage = '', $featured = NULL, $post_navigation = 'same_blog' )
	{
		global $DB, $ItemCache;

		if( ! $this->single_post )
		{	// We are not on a single post:
			$r = NULL;
			return $r;
		}

		/**
		 * @var Item
		 */
		$current_Item = $this->get_by_idx(0);

		if( is_null($current_Item) )
		{	// This happens if we are on a single post that we do not actually have permission to view
			$r = NULL;
			return $r;
		}

		if( $current_Item->get_type_setting( 'usage' ) != 'post' )
		{	// We are not on a REGULAR post -- we cannot navigate:
			$r = NULL;
			return $r;
		}

		if( !empty( $this->prevnext_Item[$direction][$post_navigation] ) )
		{
			return $this->prevnext_Item[$direction][$post_navigation];
		}

		$next_Query = new ItemQuery( $this->Cache->dbtablename, $this->Cache->dbprefix, $this->Cache->dbIDname );

		// GENERATE THE QUERY:

		/*
		 * filtering stuff:
		 */
		$next_Query->where_chapter2( $this->Blog, $this->filters['cat_array'], $this->filters['cat_modifier'],
																 $this->filters['cat_focus'], $this->filters['coll_IDs'] );
		$next_Query->where_author( $this->filters['authors'] );
		$next_Query->where_author_logins( $this->filters['authors_login'] );
		$next_Query->where_assignees( $this->filters['assignees'] );
		$next_Query->where_assignees_logins( $this->filters['assignees_login'] );
		$next_Query->where_author_assignee( $this->filters['author_assignee'] );
		$next_Query->where_locale( $this->filters['lc'] );
		$next_Query->where_statuses( $this->filters['statuses'] );
		// itemtype_usage param is kept only for the case when some custom types should be displayed
		$next_Query->where_itemtype_usage( ! empty( $itemtype_usage ) ? $itemtype_usage : $this->filters['itemtype_usage'] );
		$next_Query->where_keywords( $this->filters['keywords'], $this->filters['phrase'], $this->filters['exact'] );
		// $next_Query->where_ID( $this->filters['post_ID'], $this->filters['post_title'] );
		$next_Query->where_datestart( $this->filters['ymdhms'], $this->filters['week'],
		                                   $this->filters['ymdhms_min'], $this->filters['ymdhms_max'],
		                                   $this->filters['ts_min'], $this->filters['ts_max'] );
		$next_Query->where_visibility( $this->filters['visibility_array'] );
		$next_Query->where_featured( $featured );
		$next_Query->where_tags( $this->filters['tags'] );
		$next_Query->where_flagged( $this->filters['flagged'] );

		/*
		 * ORDER BY stuff:
		 */
		$orderdir = str_replace( ' ', ',',  $this->filters['order'] );
		$orderdir = explode( ',', $orderdir );
		foreach( $orderdir as $index => $order )
		{ // Set the corresponding order by direction depending the original value and the next/prev request
			$orderdir[$index] = ( ( $direction == 'next' && $orderdir[$index] == 'DESC' )
				|| ( $direction == 'prev' && $orderdir[$index] == 'ASC' ) ) ? 'DESC' : 'ASC';
		}
		$orderdir = implode( ',', $orderdir );

		// Init and set the order by condition and the addition required tables ( in case of custom fields )
		$order_clause = $next_Query->gen_order_clause( $this->filters['orderby'], $orderdir, $this->Cache->dbprefix, $this->Cache->dbIDname );
		$next_Query->order_by( $order_clause );
		// Add additionally required tables because of the ordering ( ordering by cutsom fields )
		$next_Query->FROM_add( $next_Query->get_orderby_from() );

		// LIMIT to 1 single result
		$next_Query->LIMIT( '1' );

		// fp> TODO: I think some additional limits need to come back here (for timespans)


		/*
		 * Position right after the current element depending on current sorting params
		 *
		 * If there are several items on the same issuedatetime for example, we'll then differentiate on post ID
		 * WARNING: you cannot combine criterias with AND here; you need stuf like a>a0 OR (a=a0 AND b>b0)
		 */

		$orderby_fields = explode( ',', $order_clause );
		$where_condition = '';
		$condition_separator = '';
		$equal_condition = '';
		// Loop through each order by field and set a where condition corresponding to the next/prev and order directions
		foreach( $orderby_fields as $orderby )
		{
			if( strpos( $orderby, 'CASE WHEN' ) !== false )
			{ // This handles those cases when the order fields may be null values and we want all null values at the end of the list
				continue;
			}
			if( strpos( $orderby, 'DESC' ) )
			{ // Descending order
				$operator = ' < ';
				// It is the field name in a format how it must be compared in the query
				$compare_field = substr( $orderby, 0, -5 );
			}
			else
			{ // Ascending order
				$operator = ' > ';
				// It is the field name in a format how it must be compared in the query
				$compare_field = substr( $orderby, 0, -4 );
			}
			$compare_field = trim( $compare_field );
			if( ( $table_separator = strpos( $compare_field, '.' ) ) )
			{ // This is a custom field from the item settings table
				// The field name must be get from a string like 'custom_[varchar | double]_fieldname_table.iset_value'
				$field_name_position = $table_separator - 6 /* the length of '_table' */ - strlen( $compare_field );
				$compare_field_name = substr( $compare_field, 0, $field_name_position );
				$compare_field_name = substr( $compare_field_name, strrpos( $compare_field_name, '_' ) + 1 );
				$post_field_name = 'custom_'.$compare_field_name;
			}
			elseif( $compare_field == 'RAND()' )
			{ // Random order
				$post_field_name = 'RAND';
			}
			else
			{ // Normal post field ( not custom and not special field RAND )
				// It is the field name in a format how should be requestd from the item
				$post_field_name = substr( $compare_field, strlen( $this->Cache->dbprefix ) );
			}

			if( $condition_separator !== '' )
			{ // Concatenate the conditions
				$equal_condition .= ' AND ';
				$where_condition .= $condition_separator.'( '.$equal_condition;
			}

			// Set condition corresponding to the order by field name
			switch( $post_field_name )
			{
				case 'numviews':
					// we need to get the number of members who has viewed the post
					$numviews = get_item_numviews( $current_Item );
					$where_condition .= $this->Cache->dbprefix.$post_field_name
																	.$operator
																	.$DB->quote($numviews)
																	.' OR ( '
																		.$this->Cache->dbprefix.$post_field_name
																		.' = '
																		.$DB->quote($numviews)
																		.' AND '
																		.$this->Cache->dbIDname
																		.$operator
																		.$current_Item->ID
																	.')';
					break;

				case 'datestart':
					// special var name:
					$post_field_name = 'issue_date';
				case 'ID':
				case 'title':
				case 'ityp_ID':
				case 'datecreated':
				case 'datemodified':
				case 'last_touched_ts':
				case 'contents_last_updated_ts':
				case 'urltitle':
				case 'priority':
					$where_condition .= $compare_field.$operator.$DB->quote( $current_Item->{$post_field_name} );
					$equal_condition .= $compare_field.' = '.$DB->quote( $current_Item->{$post_field_name} );
					break;

				case 'order':
					// We have to integrate a rounding error margin
					$order_field_value = $current_Item->order;

					// asimo> If we would like to order the null values into the end of the result, then we must check the current direction
					// asimo> In that case NULL values should be allowed only if the direction is 'NEXT' no matter what is the current $operator value

					if( is_null( $order_field_value ) )
					{ // current Item has NULL order
						$where_condition .= ( $operator == ' > ' ? $compare_field.' IS NOT NULL' : 'FALSE' );
						$equal_condition .= $compare_field.' IS NULL';
					}
					else
					{
						if( $operator == ' > ' )
						{
							$where_condition .= '( '.$compare_field.' IS NOT NULL AND '
												.$compare_field.$operator.( $order_field_value + 0.000000001 ).' )';
						}
						else
						{
							$where_condition .= '( ( '.$compare_field.' IS NULL ) OR '
												.$compare_field.$operator.( $order_field_value - 0.000000001 ).' )';
						}
						$equal_condition .= '( ( '.$compare_field.' <= '.( $order_field_value + 0.000000001 ).' )
											 AND ( '.$compare_field.' >= '.( $order_field_value - 0.000000001 ).' ) )';
					}
					break;

				case 'RAND':
					// Random order. Don't show current item again.
					$where_condition .= $this->Cache->dbprefix.'ID <> '.$current_Item->ID;
					// There can't be two equal
					$equal_condition .= 'FALSE';
					break;

				default:
					if( strpos( $post_field_name, 'custom_' ) === 0 )
					{
						// asimo> If we would like to order the null values into the end of the result, then we must check the current direction
						// asimo> In that case NULL values should be allowed only if the direction is 'NEXT' no matter what is the current $operator value

						$custom_field_value = $current_Item->get_custom_field_value( $compare_field_name );
						if( ( $custom_field_value === false ) || ( $custom_field_value === NULL ) )
						{ // This custom field is not set for the current Item
							$where_condition .= ( $operator == ' > ' ? $compare_field.' IS NOT NULL' : 'FALSE' );
							$equal_condition .= $compare_field.' IS NULL';
						}
						else
						{
							$where_condition .= ( $operator == ' < ' ) ? '( ' : '';
							$where_condition .= '( '.$compare_field.' IS NOT NULL AND '
												.$compare_field.$operator.$DB->quote( $custom_field_value )
												.' )';
							// All null values are < than a not null value
							$where_condition .= ( $operator == ' < ' ) ? ' OR ( '.$compare_field.' IS NULL ) )' : '';
							$equal_condition .= $compare_field.' = '.$DB->quote( $custom_field_value );
						}
						break;
					}
					echo 'WARNING: unhandled sorting: '.htmlspecialchars( $post_field_name );
			}

			if( $condition_separator == '' )
			{ // The first where condition was set, we need to set an 'OR' separator in case if we will have further conditions
				$condition_separator = ' OR ';
			}
			else
			{ // More than one WHERE condition was added, the last one always needs a ')'
				$where_condition .= ' )';
			}
		}
		// Add conditions to get result only from the next/prev items
		$next_Query->WHERE_and( $where_condition );

		// GET DATA ROWS:


		// We are going to proceed in two steps (we simulate a subquery)
		// 1) we get the IDs we need
		// 2) we get all the other fields matching these IDs
		// This is more efficient than manipulating all fields at once.

		// Step 1:
		$step1_sql = 'SELECT DISTINCT '.$this->Cache->dbIDname
									.$next_Query->get_from()
									.$next_Query->get_where()
									.$next_Query->get_group_by()
									.$next_Query->get_order_by()
									.$next_Query->get_limit();

		//echo DB::format_query( $step1_sql );

		// Get list of the IDs we need:
		$next_ID = $DB->get_var( $step1_sql, 0, 0, ( empty( $this->query_title_prefix ) ? '' : $this->query_title_prefix.' - ' ).'Get ID of next item' );

		//pre_dump( $next_ID );

		// Step 2: get the item (may be NULL):
		$this->prevnext_Item[$direction][$post_navigation] = & $ItemCache->get_by_ID( $next_ID, true, false );

		return $this->prevnext_Item[$direction][$post_navigation];

	}


	/**
	 * Load data of Items from the current page at once to cache variables.
	 * For each loading we use only single query to optimize performance.
	 * By default it loads all Items of current list page into global $ItemCache,
	 * Other data are loaded depending on $params, see below:
	 *
	 * @param array Params:
	 *        - 'load_user_data' - use TRUE to load all data from table T_users__postreadstatus(dates of last read
	 *                             post and comments) of the current logged in User for all Items of current list page.
	 *                             (ONLY when a tracking unread content is enabled for the collection)
	 *        - 'load_postcats'  - use TRUE to load all category associations for all Items of current list page.
	 */
	function load_list_data( $params = array() )
	{
		$params = array_merge( array(
				'load_user_data' => true,
				'load_postcats'  => true,
			), $params );

		$page_post_ids = $this->get_page_ID_array();
		if( empty( $page_post_ids ) )
		{	// There are no items on this list:
			return;
		}

		// Load all items of the current page in single query:
		$ItemCache = & get_ItemCache();
		$ItemCache->load_list( $page_post_ids );

		if( $params['load_user_data'] )
		{	// Load the user data for items:
			$this->load_user_data_for_items();
		}

		if( $params['load_postcats'] )
		{	// Load category associations for the items of current page:
			postcats_get_by_IDs( $page_post_ids );
		}
	}


	/**
	 * Load user data (posts/comments read statuses) for current User for each post of the current ItemList page
	 *
	 * @deprecated Use new function load_user_data_for_items() instead
	 */
	function load_content_read_statuses()
	{
		$this->load_user_data_for_items();
	}


	/**
	 * Load user data (posts/comments read statuses) for current User for each post of the current ItemList page
	 */
	function load_user_data_for_items()
	{
		if( !$this->Blog->get_setting( 'track_unread_content' ) )
		{ // tracking unread content in this blog is turned off
			return;
		}

		$page_post_ids = $this->get_page_ID_array();
		if( empty( $page_post_ids ) )
		{ // There are no items on this list
			return;
		}

		// Delegate query:
		load_user_data_for_items( $page_post_ids );
	}
}

?>