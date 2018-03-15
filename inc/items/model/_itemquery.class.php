<?php
/**
 * This file implements the ItemQuery class.
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

load_class( '_core/model/db/_sql.class.php', 'SQL' );

/**
 * ItemQuery: help constructing queries on Items
 * @package evocore
 */
class ItemQuery extends SQL
{
	var $p;
	var $pl;
	var $title;
	var $blog;
	var $cat;
	var $catsel;
	var $show_statuses;
	var $tags;
	var $author;
	var $author_login;
	var $assignees;
	var $assignees_login;
	var $statuses;
	var $types;
	var $itemtype_usage;
	var $dstart;
	var $dstop;
	var $timestamp_min;
	var $timestamp_max;
	var $keywords;
	var $keyword_scope;
	var $phrase;
	var $exact;
	var $featured;
	var $flagged;

	/**
	 * A query FROM string to join other tables.
	 * It is set in case of the select queries when we need to order by custom fields.
	 *
	 * @var string
	 */
	var $orderby_from = '';

	/**
	 * Constructor.
	 *
	 * @param string Name of table in database
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 */
	function __construct( $dbtablename, $dbprefix = '', $dbIDname )
	{
		$this->dbtablename = $dbtablename;
		$this->dbprefix = $dbprefix;
		$this->dbIDname = $dbIDname;

		$this->FROM( $this->dbtablename );
	}


	/**
	 * Restrict to a specific post
	 */
	function where_ID( $p = '', $title = '' )
	{
		$r = false;

		$this->p = $p;
		$this->title = $title;

		// if a post number is specified, load that post
		if( !empty($p) )
		{
			if( substr( $this->p, 0, 1 ) == '-' )
			{	// Starts with MINUS sign:
				$eq_p = ' <> ';
				$this->p = substr( $this->p, 1 );
			}
			else
			{
				$eq_p = ' = ';
			}

			$this->WHERE_and( $this->dbIDname.$eq_p.intval($this->p) );
			$r = true;
		}

		// if a post urltitle is specified, load that post
		if( !empty( $title ) )
		{
			if( substr( $this->title, 0, 1 ) == '-' )
			{	// Starts with MINUS sign:
				$eq_title = ' <> ';
				$this->title = substr( $this->title, 1 );
			}
			else
			{
				$eq_title = ' = ';
			}

			global $DB;
			$this->WHERE_and( $this->dbprefix.'urltitle'.$eq_title.$DB->quote($this->title) );
			$r = true;
		}

		return $r;
	}


	/**
	 * Restrict to a specific list of posts
	 */
	function where_ID_list( $pl = '' )
	{
		$r = false;

		$this->pl = $pl;

		if( empty( $pl ) ) return $r; // nothing to do

		if( substr( $this->pl, 0, 1 ) == '-' )
		{	// List starts with MINUS sign:
			$eq = 'NOT IN';
			$this->pl = substr( $this->pl, 1 );
		}
		else
		{
			$eq = 'IN';
		}

		$p_ID_array = array();
		$p_id_list = explode( ',', $this->pl );
		foreach( $p_id_list as $p_id )
		{
			$p_ID_array[] = intval( $p_id );// make sure they're all numbers
		}

		$this->pl = implode( ',', $p_ID_array );

		$this->WHERE_and( $this->dbIDname.' '.$eq.'( '.$this->pl.' )' );
		$r = true;

		return $r;
	}


	/**
	 * Restrict to specific collection/chapters (blog/categories)
	 *
	 * @param integer
	 * @param string List of cats to restrict to
	 * @param array Array of cats to restrict to
	 */
	function where_chapter( $blog, $cat = '', $catsel = array() )
	{
		global $cat_array; // this is required for the cat_req() callback in compile_cat_array()

		$blog = intval($blog);	// Extra security

		// Save for future use (permission checks..)
		$this->blog = $blog;

		$this->FROM_add( 'INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID
											INNER JOIN T_categories ON postcat_cat_ID = cat_ID' );

		$BlogCache = & get_BlogCache();
		$current_Blog = $BlogCache->get_by_ID( $blog );

		$this->WHERE_and( $current_Blog->get_sql_where_aggregate_coll_IDs('cat_blog_ID') );


		$cat_array = NULL;
		$cat_modifier = NULL;

		// Compile the real category list to use:
		// TODO: allow to pass the compiled vars directly to this class
		compile_cat_array( $cat, $catsel, /* by ref */ $cat_array, /* by ref */ $cat_modifier, /* TODO $blog == 1 ? 0 : */ $blog );

		if( ! empty($cat_array) )
		{	// We want to restict to some cats:
			global $DB;

			if( $cat_modifier == '-' )
			{
				$eq = 'NOT IN';
			}
			else
			{
				$eq = 'IN';
			}
			$whichcat = 'postcat_cat_ID '. $eq.' ('.$DB->quote( $cat_array ). ') ';

			// echo $whichcat;
			$this->WHERE_and( $whichcat );

			if( $cat_modifier == '*' )
			{ // We want the categories combined! (i-e posts must be in ALL requested cats)
				$this->GROUP_BY( $this->dbIDname.' HAVING COUNT(postcat_cat_ID) = '.count($cat_array) );
			}
		}
	}


	/**
	 * Restrict to specific collection/chapters (blog/categories)
	 *
	 * @param object Blog
	 * @param array Categories IDs
	 * @param string Use '-' to exclude the categories
	 * @param string 'wide' to search in extra cats too
	 *               'main' for main cat only
	 *               'extra' for extra cats only
	 * @param string Collection IDs
	 */
	function where_chapter2( & $Blog, $cat_array, $cat_modifier, $cat_focus = 'wide', $coll_IDs = NULL )
	{
		// Save for future use (permission checks..)
		$this->blog = $Blog->ID;
		$this->Blog = $Blog;
		$this->cat_array = $cat_array;
		$this->cat_modifier = $cat_modifier;

		if( ! empty( $cat_array ) && ( $cat_focus == 'wide' || $cat_focus == 'extra' ) )
		{	// Select extra categories if we want filter by several categories:
			$sql_join_categories = ( $cat_focus == 'extra' ) ? ' AND post_main_cat_ID != cat_ID' : '';
			$this->FROM_add( 'INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID' );
			$this->FROM_add( 'INNER JOIN T_categories ON postcat_cat_ID = cat_ID'.$sql_join_categories );
			// fp> we try to restrict as close as possible to the posts but I don't know if it matters
			$cat_ID_field = 'postcat_cat_ID';
		}
		elseif( get_allow_cross_posting() >= 1 )
		{	// Select extra categories if cross posting is enabled:
			$this->FROM_add( 'INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID' );
			$this->FROM_add( 'INNER JOIN T_categories ON postcat_cat_ID = cat_ID' );
			$cat_ID_field = 'postcat_cat_ID';
		}
		else
		{	// Select only main categories:
			$this->FROM_add( 'INNER JOIN T_categories ON post_main_cat_ID = cat_ID' );
			$cat_ID_field = 'post_main_cat_ID';
		}

		if( ! empty( $coll_IDs ) )
		{ // Force to aggregate the collection IDs from current param and not from blog setting
			$this->WHERE_and( $Blog->get_sql_where_aggregate_coll_IDs( 'cat_blog_ID', $coll_IDs ) );
		}
		elseif( $cat_focus == 'main' )
		{ // We are requesting a narrow search
			$this->WHERE_and( 'cat_blog_ID = '.$Blog->ID );
		}
		else
		{ // Aggregate the collections IDs from blog setting
			$this->WHERE_and( $Blog->get_sql_where_aggregate_coll_IDs( 'cat_blog_ID' ) );
		}


		if( ! empty($cat_array) )
		{	// We want to restict to some cats:
			global $DB;

			if( $cat_modifier == '-' )
			{
				$eq = 'NOT IN';
			}
			else
			{
				$eq = 'IN';
			}
			$whichcat = $cat_ID_field.' '.$eq.' ('.$DB->quote( $cat_array ). ') ';

			// echo $whichcat;
			$this->WHERE_and( $whichcat );

			if( $cat_modifier == '*' )
			{ // We want the categories combined! (i-e posts must be in ALL requested cats)
				$this->GROUP_BY( $this->dbIDname.' HAVING COUNT('.$cat_ID_field.') = '.count($cat_array) );
			}
		}
	}


	/**
	 * Restrict to the visibility/sharing statuses we want to show
	 *
	 * @param array Restrict to these statuses
	 * @param string What blogs should be used to check the status permissions:
	 *               - Empty string to use current blog setting
	 *               - A list separated by ','
	 *               - Value '*' to use all blogs
	 *               - Value '-' to use only current blog without aggregated blog
	 */
	function where_visibility( $show_statuses, $aggregate_coll_IDs = NULL )
	{
		$this->show_statuses = $show_statuses;

		if( ! isset( $this->blog ) )
		{
			debug_die( 'Status restriction requires to work with a specific blog first.' );
		}

		if( empty( $aggregate_coll_IDs ) && ! empty( $this->blog ) )
		{ // Blog IDs are not defined, Use them depending on current collection setting
			// NOTE! collection can be 0, for example, on disp=usercomments|useritems where we display data from all collections
			$BlogCache = & get_BlogCache();
			$Collection = $Blog = & $BlogCache->get_by_ID( $this->blog );
			$aggregate_coll_IDs = $Blog->get_setting( 'aggregate_coll_IDs' );
		}

		$blog_IDs = array();
		if( empty( $aggregate_coll_IDs ) || $aggregate_coll_IDs == '-' )
		{ // Get status restriction only for current blog
			$this->WHERE_and( statuses_where_clause( $show_statuses, $this->dbprefix, $this->blog, 'blog_post!', true, $this->author ) );
			return; // Exit here, because we don't need to check the permissions for multiple blogs
		}

		// Check status permission for multiple blogs
		if( $aggregate_coll_IDs == '*' )
		{ // Get the status restrictions for all blogs
			// Load all collections in single query, because otherwise we may have too many queries (1 query for each collection) later:
			// fp> TODO: PERF: we probably want to remove this later when we restrict the use of '*'
			$BlogCache = & get_BlogCache();
			$BlogCache->load_all();
			$blog_IDs = $BlogCache->get_ID_array();
		}
		else
		{ // Get the status restrictions for several blogs
			$blog_IDs = explode( ',', $aggregate_coll_IDs );
		}

		$status_coll_clauses = array();
		foreach( $blog_IDs as $blog_ID )
		{	// Check status permission for each blog separately:
			$statuses_where_clause = statuses_where_clause( $show_statuses, $this->dbprefix, $blog_ID, 'blog_post!', true, $this->author );
			if( ! isset( $status_coll_clauses[ $statuses_where_clause ] ) )
			{	// Initialize array item for each different status condition:
				$status_coll_clauses[ $statuses_where_clause ] = array();
			}
			// Group collections by same status condition:
			$status_coll_clauses[ $statuses_where_clause ][] = $blog_ID;
		}
		$status_restrictions = array();
		foreach( $status_coll_clauses as $status_coll_clause => $status_coll_IDs )
		{	// Initialize status permission restriction for each grouped condition that is formed above:
			$status_restrictions[] = 'cat_blog_ID IN ( '.implode( ',', $status_coll_IDs ).' ) AND '.$status_coll_clause;
		}

		$this->WHERE_and( '( '.implode( ' ) OR ( ', $status_restrictions ).' )' );
	}


	/**
	 * Restrict to the featured/non featured posts if requested
	 *
	 * @param boolean|NULL Restrict to featured
	 */
	function where_featured( $featured = NULL )
	{
		$this->featured = $featured;

		if( is_null( $this->featured ) )
		{ // no restriction
			return;
		}
		elseif( !empty( $this->featured ) )
		{ // restrict to featured
			$this->WHERE_and( $this->dbprefix.'featured <> 0' );
		}
		else
		{ // restrict to NON featured
			$this->WHERE_and( $this->dbprefix.'featured = 0' );
		}
	}


	/**
	 * Restrict to specific tags
	 *
	 * @param string List of tags to restrict to
	 */
	function where_tags( $tags )
	{
		global $DB;

		$this->tags = $tags;

		if( empty( $tags ) )
		{
			return;
		}

		$tags = explode( ',', $tags );

		$this->FROM_add( 'INNER JOIN T_items__itemtag ON post_ID = itag_itm_ID
								INNER JOIN T_items__tag ON (itag_tag_ID = tag_ID AND tag_name IN ('.$DB->quote($tags).') )' );
	}


	/**
	 * Restrict to specific authors by users IDs
	 *
	 * @param string List of authors IDs to restrict to (must have been previously validated)
	 */
	function where_author( $author_IDs )
	{
		$this->author = $author_IDs;

		if( empty( $this->author ) )
		{
			return;
		}

		if( substr( $this->author, 0, 1 ) == '-' )
		{ // Exclude the users IF a list starts with MINUS sign:
			$eq = 'NOT IN';
			$users_IDs = substr( $this->author, 1 );
		}
		else
		{ // Include the users:
			$eq = 'IN';
			$users_IDs = $this->author;
		}

		$this->WHERE_and( $this->dbprefix.'creator_user_ID '.$eq.' ( '.$users_IDs.' )' );
	}


	/**
	 * Restrict to specific authors by users logins
	 *
	 * @param string List of authors logins to restrict to (must have been previously validated)
	 */
	function where_author_logins( $author_logins )
	{
		$this->author_login = $author_logins;

		if( empty( $this->author_login ) )
		{
			return;
		}

		if( substr( $this->author_login, 0, 1 ) == '-' )
		{ // Exclude the users IF a list starts with MINUS sign:
			$eq = 'NOT IN';
			$users_IDs = get_users_IDs_by_logins( substr( $this->author_login, 1 ) );
		}
		else
		{ // Include the users:
			$eq = 'IN';
			$users_IDs = get_users_IDs_by_logins( $this->author_login );
		}

		if( ! empty( $users_IDs ) )
		{
			$this->WHERE_and( $this->dbprefix.'creator_user_ID '.$eq.' ( '.$users_IDs.' )' );
		}
	}


	/**
	 * Restrict to specific assignees by users IDs
	 *
	 * @param string List of assignees IDs to restrict to (must have been previously validated)
	 * @param string List of assignees logins to restrict to (must have been previously validated)
	 */
	function where_assignees( $assignees, $assignees_logins = '' )
	{
		$this->assignees = $assignees;

		if( empty( $this->assignees ) )
		{
			return;
		}

		if( $this->assignees == '-' )
		{	// List is ONLY a MINUS sign (we want only those not assigned)
			$this->WHERE_and( $this->dbprefix.'assigned_user_ID IS NULL' );
		}
		elseif( substr( $this->assignees, 0, 1 ) == '-' )
		{	// List starts with MINUS sign:
			$this->WHERE_and( '( '.$this->dbprefix.'assigned_user_ID IS NULL
			                  OR '.$this->dbprefix.'assigned_user_ID NOT IN ('.substr( $this->assignees, 1 ).') )' );
		}
		else
		{
			$this->WHERE_and( $this->dbprefix.'assigned_user_ID IN ('.$this->assignees.')' );
		}
	}


	/**
	 * Restrict to specific assignees by users logins
	 *
	 * @param string List of assignees logins to restrict to (must have been previously validated)
	 */
	function where_assignees_logins( $assignees_logins )
	{
		$this->assignees_logins = $assignees_logins;

		if( empty( $this->assignees_logins ) )
		{
			return;
		}

		if( $this->assignees_logins == '-' )
		{	// List is ONLY a MINUS sign (we want only those not assigned)
			$this->WHERE_and( $this->dbprefix.'assigned_user_ID IS NULL' );
		}
		elseif( substr( $this->assignees_logins, 0, 1 ) == '-' )
		{	// List starts with MINUS sign:
			$this->WHERE_and( '( '.$this->dbprefix.'assigned_user_ID IS NULL
			                  OR '.$this->dbprefix.'assigned_user_ID NOT IN ('.get_users_IDs_by_logins( substr( $this->assignees_logins, 1 ) ).') )' );
		}
		else
		{
			$this->WHERE_and( $this->dbprefix.'assigned_user_ID IN ('.get_users_IDs_by_logins( $this->assignees_logins ).')' );
		}
	}


	/**
	 * Restrict to specific assignee or author
	 *
	 * @param integer assignee or author to restrict to (must have been previously validated)
	 */
	function where_author_assignee( $author_assignee )
	{
		$this->author_assignee = $author_assignee;

		if( empty( $author_assignee ) )
		{
			return;
		}

		$this->WHERE_and( '( '.$this->dbprefix.'creator_user_ID = '. $author_assignee.' OR '.
											$this->dbprefix.'assigned_user_ID = '.$author_assignee.' )' );
	}


	/**
	 * Restrict to specific locale
	 *
	 * @param string locale to restrict to ('all' if you don't want to restrict)
	 */
	function where_locale( $locale )
	{
		global $DB;

		if( $locale == 'all' )
		{
			return;
		}

		$this->WHERE_and( $this->dbprefix.'locale LIKE '.$DB->quote($locale.'%') );
	}


	/**
	 * Restrict to specific (exetnded) statuses
	 *
	 * @param string List of assignees to restrict to (must have been previously validated)
	 */
	function where_statuses( $statuses )
	{
		$this->statuses = $statuses;

		if( empty( $statuses ) )
		{
			return;
		}

		if( $statuses == '-' )
		{	// List is ONLY a MINUS sign (we want only those not assigned)
			$this->WHERE_and( $this->dbprefix.'pst_ID IS NULL' );
		}
		elseif( substr( $statuses, 0, 1 ) == '-' )
		{	// List starts with MINUS sign:
			$this->WHERE_and( '( '.$this->dbprefix.'pst_ID IS NULL
			                  OR '.$this->dbprefix.'pst_ID NOT IN ('.substr( $statuses, 1 ).') )' );
		}
		else
		{
			$this->WHERE_and( $this->dbprefix.'pst_ID IN ('.$statuses.')' );
		}
	}


	/**
	 * Restrict to specific post types
	 *
	 * @param string List of types to restrict to (must have been previously validated)
	 */
	function where_types( $types )
	{
		$this->types = $types;

		if( empty( $types ) )
		{
			return;
		}

		if( $types == '-' )
		{	// List is ONLY a MINUS sign (we want only those not assigned)
			$this->WHERE_and( $this->dbprefix.'ityp_ID IS NULL' );
		}
		elseif( substr( $types, 0, 1 ) == '-' )
		{	// List starts with MINUS sign:
			$this->WHERE_and( '( '.$this->dbprefix.'ityp_ID IS NULL
			                  OR '.$this->dbprefix.'ityp_ID NOT IN ('.substr( $types, 1 ).') )' );
		}
		else
		{
			$this->WHERE_and( $this->dbprefix.'ityp_ID IN ('.$types.')' );
		}
	}


	/**
	 * Restrict to specific post types usage
	 *
	 * @param string List of types usage to restrict to (must have been previously validated):
	 *               Allowed values: post, page, intro-front, intro-main, intro-cat, intro-tag, intro-sub, intro-all, special,
	 *                               *featured* - to get also featured posts
	 */
	function where_itemtype_usage( $itemtype_usage )
	{
		global $DB;

		$this->itemtype_usage = $itemtype_usage;

		if( empty( $itemtype_usage ) )
		{
			return;
		}

		$featured_sql_where = '';
		$not_featured_sql_where = '';
		if( strpos( $itemtype_usage, '*featured*' ) !== false )
		{	// Get also featured posts:
			$itemtype_usage = preg_replace( '#,?\*featured\*,?#', '', $itemtype_usage );
			$featured_sql_where .= ' OR post_featured = 1';
			$not_featured_sql_where .= ' AND post_featured != 1';
		}

		$this->FROM_add( 'LEFT JOIN T_items__type ON ityp_ID = '.$this->dbprefix.'ityp_ID' );

		if( $itemtype_usage == '-' )
		{	// List is ONLY a MINUS sign (we want only those not assigned)
			$this->WHERE_and( $this->dbprefix.'ityp_ID IS NULL'.$not_featured_sql_where );
		}
		elseif( substr( $itemtype_usage, 0, 1 ) == '-' )
		{	// List starts with MINUS sign:
			$itemtype_usage = explode( ',', substr( $itemtype_usage, 1 ) );
			$this->WHERE_and( '( '.$this->dbprefix.'ityp_ID IS NULL
			                  OR ( ityp_usage NOT IN ( '.$DB->quote( $itemtype_usage ).' )'.$not_featured_sql_where.' ) )' );
		}
		else
		{
			$itemtype_usage = explode( ',', $itemtype_usage );
			$this->WHERE_and( '( ityp_usage IN ( '.$DB->quote( $itemtype_usage ).' )'.$featured_sql_where.' )' );
		}
	}


	/**
	 * Restricts the datestart param to a specific date range.
	 *
	 * Start date gets restricted to minutes only (to make the query more
	 * cachable).
	 *
	 * Priorities:
	 *  -dstart and/or dstop
	 *  -week + m
	 *  -m
	 * @todo  -dstart + x days
	 * @see ItemList2::get_advertised_start_date()
	 *
	 * @param string YYYYMMDDHHMMSS (everything after YYYY is optional) or ''
	 * @param integer week number or ''
	 * @param string YYYYMMDDHHMMSS to start at, '' for first available
	 * @param string YYYYMMDDHHMMSS to stop at
	 * @param mixed Do not show posts before this timestamp, can be 'now'
	 * @param mixed Do not show posts after this timestamp, can be 'now'
	 */
	function where_datestart( $m = '', $w = '', $dstart = '', $dstop = '', $timestamp_min = '', $timestamp_max = 'now' )
	{
		global $time_difference, $DB;

		$this->m = $m;
		$this->w = $w;
		$this->dstart = $dstart;
		$this->dstop = $dstop;
		$this->timestamp_min = $timestamp_min;
		$this->timestamp_max = $timestamp_max;


		$start_is_set = false;
		$stop_is_set = false;


		// if a start date is specified in the querystring, crop anything before
		if( !empty($dstart) )
		{
			// Add trailing 0s: YYYYMMDDHHMMSS
			$dstart0 = $dstart.'00000000000000';  // TODO: this is NOT correct, should be 0101 for month

			// Start date in MySQL format: seconds get omitted (rounded to lower to minute for caching purposes)
			$dstart_mysql = substr($dstart0,0,4).'-'.substr($dstart0,4,2).'-'.substr($dstart0,6,2).' '
											.substr($dstart0,8,2).':'.substr($dstart0,10,2);

			$this->WHERE_and( $this->dbprefix.'datestart >= '.$DB->quote( $dstart_mysql ).'
													OR ( '.$this->dbprefix.'datedeadline IS NULL AND '.$this->dbprefix.'datestart >= '.$DB->quote( $dstart_mysql ).' )' );

			$start_is_set = true;
		}


		// if a stop date is specified in the querystring, crop anything before
		if( !empty($dstop) )
		{
			switch( strlen( $dstop ) )
			{
				case '4':
					// We have only year, add one to year
					$dstop_mysql = ($dstop+1).'-01-01 00:00:00';
					break;

				case '6':
					// We have year month, add one to month
					$dstop_mysql = date("Y-m-d H:i:s ", mktime(0, 0, 0, substr($dstop,4,2)+1, 01, substr($dstop,0,4)));
					break;

				case '8':
					// We have year mounth day, add one to day
					$dstop_mysql = date("Y-m-d H:i:s ", mktime(0, 0, 0, substr($dstop,4,2), (substr($dstop,6,2) + 1 ), substr($dstop,0,4)));
					break;

				case '10':
					// We have year mounth day hour, add one to hour
					$dstop_mysql = date("Y-m-d H:i:s ", mktime( ( substr($dstop,8,2) + 1 ), 0, 0, substr($dstop,4,2), substr($dstop,6,2), substr($dstop,0,4)));
					break;

				case '12':
					// We have year mounth day hour minute, add one to minute
					$dstop_mysql = date("Y-m-d H:i:s ", mktime( substr($dstop,8,2), ( substr($dstop,8,2) + 1 ), 0, substr($dstop,4,2), substr($dstop,6,2), substr($dstop,0,4)));
					break;

				default:
					// add one to second
					// Stop date in MySQL format: seconds get omitted (rounded to lower to minute for caching purposes)
					$dstop_mysql = substr($dstop,0,4).'-'.substr($dstop,4,2).'-'.substr($dstop,6,2).' '
											.substr($dstop,8,2).':'.substr($dstop,10,2);
			}

			$this->WHERE_and( $this->dbprefix.'datestart < '.$DB->quote( $dstop_mysql ) ); // NOT <= comparator because we compare to the superior stop date

			$stop_is_set = true;
		}


		if( !$start_is_set || !$stop_is_set )
		{

			if( !is_null($w)  // Note: week # can be 0
					&& strlen($m) == 4 )
			{ // If a week number is specified (with a year)

				// Note: we use PHP to calculate week boundaries in order to handle weeks
				// that overlap 2 years properly, even when start on week is monday (which MYSQL won't handle properly)
				$start_date_for_week = get_start_date_for_week( $m, $w, locale_startofweek() );

				$this->WHERE_and( $this->dbprefix."datestart >= '".date('Y-m-d',$start_date_for_week)."'" );
				$this->WHERE_and( $this->dbprefix."datestart < '".date('Y-m-d',$start_date_for_week+604800 )."'" ); // + 7 days

				$start_is_set = true;
				$stop_is_set = true;
			}
			elseif( !empty($m) )
			{	// We want to restrict on an interval:
				$this->WHERE_and( 'EXTRACT(YEAR FROM '.$this->dbprefix.'datestart)='.intval(substr($m,0,4)) );
				if( strlen($m) > 5 )
					$this->WHERE_and( 'EXTRACT(MONTH FROM '.$this->dbprefix.'datestart)='.intval(substr($m,4,2)) );
				if( strlen($m) > 7 )
					$this->WHERE_and( 'EXTRACT(DAY FROM '.$this->dbprefix.'datestart)='.intval(substr($m,6,2)) );
				if( strlen($m) > 9 )
					$this->WHERE_and( 'EXTRACT(HOUR FROM '.$this->dbprefix.'datestart)='.intval(substr($m,8,2)) );
				if( strlen($m) > 11 )
					$this->WHERE_and( 'EXTRACT(MINUTE FROM '.$this->dbprefix.'datestart)='.intval(substr($m,10,2)) );
				if( strlen($m) > 13 )
					$this->WHERE_and( 'EXTRACT(SECOND FROM '.$this->dbprefix.'datestart)='.intval(substr($m,12,2)) );

				$start_is_set = true;
				$stop_is_set = true;
			}

		}


		// TODO: start + x days
		// TODO: stop - x days


		// SILENT limits!

		// Timestamp limits:
		if( $timestamp_min == 'now' )
		{
			// echo 'hide past';
			$timestamp_min = time();
		}
		if( !empty($timestamp_min) )
		{ // Hide posts before
			// echo 'hide before '.$timestamp_min;
			$date_min = remove_seconds( $timestamp_min + $time_difference );
			$this->WHERE_and( $this->dbprefix.'datestart >= '.$DB->quote( $date_min ) );
		}

		if( $timestamp_max == 'now' )
		{
			// echo 'hide future';
			$timestamp_max = time();
		}
		if( !empty($timestamp_max) )
		{ // Hide posts after
			// echo 'after';
			$date_max = remove_seconds( $timestamp_max + $time_difference );
			$this->WHERE_and( $this->dbprefix.'datestart <= '.$DB->quote( $date_max ) );
		}

	}


	/**
	 * Restricts creation date to a specific date range.
	 *
 	 * @param mixed Do not show posts CREATED after this timestamp
	 */
	function where_datecreated( $timestamp_created_max = 'now' )
	{
		global $time_difference, $DB;

		if( !empty($timestamp_created_max) )
		{ // Hide posts after
			// echo 'after';
			$date_max = date('Y-m-d H:i:s', $timestamp_created_max + $time_difference );
			$this->WHERE_and( $this->dbprefix.'datecreated <= '.$DB->quote( $date_max ) );
		}

	}


	/**
	 * Restrict with keywords
	 *
	 * @param string Keyword search string
	 * @param string Search for entire phrase or for individual words: 'OR', 'AND', 'sentence'(or '1')
	 * @param string Require exact match of title or contents — does NOT apply to tags which are always an EXACT match
	 * @param string Scope of keyword search string: 'title', 'content', 'tags', 'excerpt', 'titletag', 'metadesc', 'metakeywords'
	 */
	function where_keywords( $keywords, $phrase, $exact, $keyword_scope = 'title,content' )
	{
		global $DB;

		$this->keywords = utf8_trim( $keywords );
		$this->keyword_scope = $keyword_scope;
		$this->phrase = $phrase;
		$this->exact = $exact;

		if( empty( $keywords ) )
		{ // Nothing to search, Exit here:
			return;
		}

		$search_sql = array();

		// Determine what fields should be used in search:
		$search_fields = array();
		$keyword_scopes = explode( ',', $keyword_scope );
		foreach( $keyword_scopes as $keyword_scope )
		{
			switch( $keyword_scope )
			{
				case 'title':
					$search_fields[] = $this->dbprefix.'title';
					break;

				case 'content':
					$search_fields[] = $this->dbprefix.'content';
					break;

				case 'tags':
					$this->FROM_add( 'LEFT JOIN T_items__itemtag ON post_ID = itag_itm_ID' );
					$this->FROM_add( 'LEFT JOIN T_items__tag ON itag_tag_ID = tag_ID' );
					$this->GROUP_BY( 'post_ID' );
					// Tags are always an EXACT match:
					$search_sql[] = 'tag_name = '.$DB->quote( $keywords );
					break;

				case 'excerpt':
					$search_fields[] = $this->dbprefix.'excerpt';
					break;

				case 'titletag':
					$search_fields[] = $this->dbprefix.'titletag';
					break;

				case 'metadesc':
					$this->FROM_add( 'LEFT JOIN T_items__item_settings AS is_md ON post_ID = is_md.iset_item_ID AND is_md.iset_name = "metadesc"' );
					$search_fields[] = 'is_md.iset_value';
					break;

				case 'metakeywords':
					$this->FROM_add( 'LEFT JOIN T_items__item_settings AS is_mk ON post_ID = is_mk.iset_item_ID AND is_mk.iset_name = "metakeywords"' );
					$search_fields[] = 'is_mk.iset_value';
					break;

				// TODO: add more.
			}
		}

		if( empty( $search_fields ) && empty( $search_sql ) )
		{	// No correct fields to search, Exit here:
			return;
		}

		// Set sql operator depending on parameter:
		if( in_array( strtolower( $phrase ), array( 'or', '1', 'sentence' ) ) )
		{
			$operator_sql = 'OR';
		}
		else
		{
			$operator_sql =  'AND';
		}

		if( ! empty( $search_fields ) )
		{	// Do search if at least one field is requested:

			if( $exact )
			{	// We want exact match of each search field:
				$mask = '';
			}
			else
			{	// The words/sentence are/is to be included in the each search field:
				$mask = '%';
			}

			if( $phrase == '1' || $phrase == 'sentence' )
			{	// Sentence search:
				foreach( $search_fields as $search_field )
				{
					$search_sql[] = $search_field.' LIKE '.$DB->quote( $mask.$keywords.$mask );
				}
			}
			else
			{	// Word search:

				// Put spaces instead of commas:
				$keywords = preg_replace( '/, +/', ',', $keywords );
				$keywords = utf8_trim( str_replace( array( ',', '"' ), ' ', $keywords ) );

				// Split by each word:
				$keywords = preg_split( '/\s+/', $keywords );

				foreach( $keywords as $keyword )
				{
					$search_field_sql = array();
					foreach( $search_fields as $search_field )
					{
						$search_field_sql[] = $search_field.' LIKE '.$DB->quote( $mask.$keyword.$mask );
					}
					$search_sql[] = '( '.implode( ' OR ', $search_field_sql ).' )';
				}
			}
		}

		// Concat the searches:
		$search_sql = '( '.implode( ' '.$operator_sql.' ', $search_sql ).' )';

		$this->WHERE_and( $search_sql );
	}


	/**
	 * Restrict to the flagged items
	 *
	 * @param boolean TRUE - Restrict to flagged items, FALSE - Don't restrict/Get all items
	 */
	function where_flagged( $flagged = false )
	{
		global $current_User;

		$this->flagged = $flagged;

		if( ! $this->flagged )
		{	// Don't restrict if it is not requested or if user is not logged in:
			return;
		}

		// Get items which are flagged by current user:
		$this->FROM_add( 'INNER JOIN T_items__user_data ON '.$this->dbIDname.' = itud_item_ID
			AND itud_flagged_item = 1
			AND itud_user_ID = '.( is_logged_in() ? $current_User->ID : '-1' ) );
	}


	/**
	 * Generate order by clause
	 *
	 * @param $order_by
	 * @param $order_dir
	 */
	function gen_order_clause( $order_by, $order_dir, $dbprefix, $dbIDname )
	{
		global $DB;

		$order_by = str_replace( ' ', ',', $order_by );
		$orderby_array = explode( ',', $order_by );

		if( in_array( 'numviews', $orderby_array ) )
		{	// Special case for numviews:
			$this->orderby_from .= ' LEFT JOIN ( SELECT itud_item_ID, COUNT(*) AS post_numviews FROM T_items__user_data GROUP BY itud_item_ID ) AS numviews
				ON post_ID = numviews.itud_item_ID ';
		}

		// asimo> $nullable_fields may be used if we would like to order the null values into the end of the result
		// asimo> It would move the null values into the end no matter what kind of direction are we sorting
		// Set of sort options which are nullable
		// $nullable_fields = array( 'order', 'priority' );

		$available_fields = get_available_sort_options( $this->blog, false, true );
		// Custom sort options
		$custom_sort_fields = $available_fields['custom'];

		foreach( $custom_sort_fields as $key => $field_name )
		{
			$table_alias = $key.'_table';
			$field_value = $table_alias.'.iset_value';
			if( strpos( $key, 'custom_double' ) === 0 )
			{ // Double values should be compared as numbers and not like strings
				$field_value .= '+0';
			}
			if( in_array( $key, $orderby_array ) )
			{
				if( empty( $this->orderby_from ) )
				{
					$this->orderby_from .= ' ';
				}
				// $nullable_fields[$key] = $field_value;
				$this->orderby_from .= 'LEFT JOIN T_items__item_settings as '.$table_alias.' ON post_ID = '.$table_alias.'.iset_item_ID AND '
						.$table_alias.'.iset_name = (
							SELECT CONCAT( "custom_", itcf_type, "_", itcf_ID )
							FROM T_items__type_custom_field WHERE itcf_name = '.$DB->quote( $field_name ).' AND itcf_ityp_ID = post_ityp_ID )';
				$order_by = str_replace( $key, $field_value, $order_by );
			}
			$custom_sort_fields[$key] = $field_value;
		}

		$available_fields = array_merge( array_keys( $available_fields['general'] ), array_values( $custom_sort_fields ) );
		// Extend general list to allow order posts by these fields as well for some special cases
		$available_fields[] = 'creator_user_ID';
		$available_fields[] = 'assigned_user_ID';
		$available_fields[] = 'pst_ID';
		$available_fields[] = 'datedeadline';
		$available_fields[] = 'ityp_ID';
		$available_fields[] = 'status';
		$available_fields[] = 'T_categories.cat_name';
		$available_fields[] = 'T_categories.cat_order';

		$order_clause = gen_order_clause( $order_by, $order_dir, $dbprefix, $dbIDname, $available_fields );

		// asimo> The following commented code parts handles the nullable fields order, to move them NULL values into the end of the result
		// asimo> !!!Do NOT remove!!!
//		$orderby_fields = explode( ',', $order_clause );
//		foreach( $orderby_array as $index => $orderby_field )
//		{
//			$field_name = NULL;
//			$additional_clause = 0;
//			if( in_array( $orderby_field, $nullable_fields ) )
//			{ // This is an item nullable field
//				$field_name = $dbprefix.$orderby_field;
//			}
//			elseif( strpos( $orderby_field, 'custom_' ) === 0 )
//			{ // This is an item custom field which are always nullable
//				$field_name = $nullable_fields[$orderby_field];
//			}
//
//			if( empty( $field_name ) || ( strpos( $order_clause, $field_name ) === false ) )
//			{ // The field is not nullable or it is not present in the final order clause
//				continue;
//			}
//
//			// Insert 'order null values into the end' order clause
//			array_splice( $orderby_fields, $index + $additional_clause, 0, array( ' (CASE WHEN '.$field_name.' IS NULL then 1 ELSE 0 END)' ) );
//			$additional_clause++;
//		}
//		$order_clause = implode( ',', $orderby_fields );

		if( strpos( $this->itemtype_usage, '*featured*' ) !== false )
		{	// If we get featured posts together with other post types(like intro) then we should order featured posts below not featured posts:
			$order_clause = trim( 'post_featured, '.$order_clause, ', ' );
		}

		return $order_clause;
	}


	/**
	 * Get additional FROM clause if it is required because of custom order_by fields
	 *
	 * @param return before the FROM clause
	 * @return string the FROM clause to JOIN the custom fields tables
	 */
	function get_orderby_from( $prefix = '' )
	{
		return $prefix.$this->orderby_from;
	}
}

?>