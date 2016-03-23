<?php
/**
 * This file implements Category handling functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2004 by The University of North Carolina at Charlotte as
 * contributed by Jason Edgecombe {@link http://tst.uncc.edu/team/members/jason_bio.php}.
 *
 * @package evocore
 *
 * @todo implement CategoryCache based on LinkCache
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Create a new category
 *
 * This funtion has to handle all needed DB dependencies!
 *
 * @param string Category name
 * @param string Category ID ('NULL' as string(!) for root)
 * @param integer|NULL Blog ID (will be taken from parent cat, if empty)
 * @param string Category description
 * @param boolean Set to true if the new object needs to be added into the ChapterCache after it was created
 * @param integer Category order
 * @param boolean Is meta category?
 */
function cat_create( $cat_name, $cat_parent_ID, $cat_blog_ID = NULL, $cat_description = NULL, $add_to_cache = false, $cat_order = NULL, $subcat_ordering = NULL, $meta = false )
{
	global $DB;

	load_class('chapters/model/_chapter.class.php', 'Chapter');

	if( ! $cat_blog_ID )
	{
		if( empty( $cat_parent_ID ) )
		{
			debug_die ( 'cat_create(-) missing parameters (cat_parent_ID)!' );
		}

		$ChapterCache = & get_ChapterCache();
		$Chapter = $ChapterCache->get_by_ID($cat_parent_ID);
		$cat_blog_ID = $Chapter->blog_ID;
	}

	if( $cat_parent_ID === 'NULL' )
	{ // fix old use case
		$cat_parent_ID = NULL;
	}

	$new_Chapter = new Chapter( NULL, $cat_blog_ID );
	$new_Chapter->set( 'name', $cat_name );
	$new_Chapter->set( 'parent_ID', $cat_parent_ID );
	if( !empty( $cat_description ) )
	{ // Set decription
		$new_Chapter->set( 'description', $cat_description );
	}
	$new_Chapter->set( 'order', $cat_order );
	$new_Chapter->set( 'subcat_ordering', $subcat_ordering );
	if( $meta )
	{ // Set this category as meta
		$new_Chapter->set( 'meta', 1 );
	}


	if( ! $new_Chapter->dbinsert() )
	{
		return 0;
	}

	if( $add_to_cache )
	{ // add new Category into the Cache
		$ChapterCache = & get_ChapterCache();
		$ChapterCache->add( $new_Chapter );
	}

	return $new_Chapter->ID;
}


/**
 * Get category name+blog_id for specified cat ID
 *
 * fplanque: reused "R. U. Serious" optimization here
 * fplanque: added blog ID stuff
 *
 * @deprecated since 3.1.0-alpha. Use ChapterCache instead.
 * @param integer category ID
 * @param boolean die() if category does not exist? (default: true)
 *
 */
function get_the_category_by_ID( $cat_ID, $die = true )
{
	// TODO: asimo> Old content was changed, but still the whole function should be removed.
	$ChapterCache = & get_ChapterCache();
	return $ChapterCache->get_by_ID( $cat_ID, $die );
}


/**
 * Get blog ID for a given cat.
 * This halts on error.
 * @return integer
 */
function get_catblog( $cat_ID )
{
	$ChapterCache = & get_ChapterCache();
	$Chapter = $ChapterCache->get_by_ID($cat_ID);

	return $Chapter->blog_ID;
}


/**
 * Get category permanent url by category ID
 *
 * @param integer category ID
 */
function get_caturl( $cat_ID )
{
	$ChapterCache = & get_ChapterCache();
	$Chapter = $ChapterCache->get_by_ID($cat_ID);

	return $Chapter->get_permanent_url();
}


/**
 * Get # of posts for each category in a blog
 *
 * @param integer Category ID
 * @param integer Blog ID
 */
function get_postcount_in_category( $cat_ID, $blog_ID = NULL )
{
	if( is_null( $blog_ID ) )
	{
		global $blog;
		$blog_ID = $blog;
	}

	global $DB, $number_of_posts_in_cat;

	if( !isset( $number_of_posts_in_cat[ (string) $blog_ID ] ) )
	{
		$SQL = new SQL( 'Get # of posts for each category in a blog' );
		$SQL->SELECT( 'cat_ID, count( postcat_post_ID ) c' );
		$SQL->FROM( 'T_categories' );
		$SQL->FROM_add( 'INNER JOIN T_postcats ON postcat_cat_ID = cat_id' );
		$SQL->FROM_add( 'INNER JOIN T_items__item ON postcat_post_ID = post_id' );
		$SQL->FROM_add( 'LEFT JOIN T_items__type ON post_ityp_ID = ityp_ID' );
		$SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $blog_ID ) );
		$SQL->WHERE_and( 'post_ityp_ID IS NULL OR ityp_usage = "post"' );
		$SQL->WHERE_and( statuses_where_clause( get_inskin_statuses( $blog_ID, 'post' ), 'post_', $blog_ID, 'blog_post!', true ) );
		$SQL->GROUP_BY( 'cat_ID' );
		$number_of_posts_in_cat[ (string) $blog_ID ] = $DB->get_assoc( $SQL->get() );
	}

	return isset( $number_of_posts_in_cat[(string) $blog_ID][$cat_ID] ) ? (int) $number_of_posts_in_cat[(string) $blog_ID][$cat_ID] : 0;
}


/**
 * Get # of comments for each category in a blog
 *
 * @param integer Category ID
 * @param integer Blog ID
 */
function get_commentcount_in_category( $cat_ID, $blog_ID = NULL )
{
	if( is_null( $blog_ID ) )
	{
		global $blog;
		$blog_ID = $blog;
	}

	global $DB, $number_of_comments_in_cat;

	if( !isset( $number_of_comments_in_cat[(string) $blog_ID] ) )
	{
		$SQL = new SQL();
		$SQL->SELECT( 'cat_ID, COUNT( comment_ID ) c' );
		$SQL->FROM( 'T_comments' );
		$SQL->FROM_add( 'LEFT JOIN T_postcats ON comment_item_ID = postcat_post_ID' );
		$SQL->FROM_add( 'LEFT JOIN T_categories ON postcat_cat_ID = cat_id' );
		$SQL->FROM_add( 'LEFT JOIN T_items__item ON comment_item_ID = post_id' );
		$SQL->FROM_add( 'LEFT JOIN T_items__type ON post_ityp_ID = ityp_ID' );
		$SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $blog_ID ) );
		$SQL->WHERE_and( 'comment_type IN ( "comment", "trackback", "pingback" )' );
		$SQL->WHERE_and( statuses_where_clause( get_inskin_statuses( $blog_ID, 'comment' ), 'comment_', $blog_ID, 'blog_comment!', true ) );
		// add where condition to show only those posts commetns which are visible for the current User
		$SQL->WHERE_and( statuses_where_clause( get_inskin_statuses( $blog_ID, 'post' ), 'post_', $blog_ID, 'blog_post!', true ) );
		// Get content post types, Exclide pages, intros, sidebar links and ads
		$SQL->WHERE_and( 'post_ityp_ID IS NULL OR ityp_usage = "post"' );
		$SQL->GROUP_BY( 'cat_ID' );

		$number_of_comments_in_cat[(string) $blog_ID] = $DB->get_assoc( $SQL->get() );
	}

	return isset( $number_of_comments_in_cat[(string) $blog_ID][$cat_ID] ) ? (int) $number_of_comments_in_cat[(string) $blog_ID][$cat_ID] : 0;
}


/**
 * Load category associations for requested items
 *
 * @param array Item IDs
 * @return array Item categories IDs
 */
function postcats_get_by_IDs( $item_IDs )
{
	global $DB, $cache_postcats;

	if( ! is_array( $cache_postcats ) )
	{	// Initialize cache array first time:
		$cache_postcats = array();
	}

	$not_cached_item_IDs = array_diff( $item_IDs, array_keys( $cache_postcats ) );

	if( empty( $not_cached_item_IDs ) )
	{	// The category associations are loaded for all requested items:
		return;
	}

	// Load the category associations from DB and cache into global cache array:
	$SQL = new SQL( 'Load the category associations for items' );
	$SQL->SELECT( 'postcat_post_ID AS post_ID, postcat_cat_ID AS cat_ID' );
	$SQL->FROM( 'T_postcats' );
	$SQL->WHERE( 'postcat_post_ID IN ( '.$DB->quote( $not_cached_item_IDs ).' )' );
	$SQL->ORDER_BY( 'postcat_post_ID, postcat_cat_ID' );

	$items_postcats = $DB->get_results( $SQL->get(), OBJECT, $SQL->title );

	foreach( $items_postcats as $item_postcats )
	{
		if( ! isset( $cache_postcats[ $item_postcats->post_ID ] ) )
		{
			$cache_postcats[ $item_postcats->post_ID ] = array();
		}

		$cache_postcats[ $item_postcats->post_ID ][] = $item_postcats->cat_ID;
	}

	// Set all unexiting associations for requested items in order to don't repeat SQL queries later:
	foreach( $not_cached_item_IDs as $not_cached_item_ID )
	{
		if( ! isset( $cache_postcats[ $not_cached_item_ID ] ) )
		{
			$cache_postcats[ $not_cached_item_ID ] = array();
		}
	}
}


/**
 * Get category associations with given item
 *
 * sam2kb> TODO: Cache item cat IDs into Item::categories property instead of global $cache_postcats
 *
 * @param integer Item ID
 * @return array Item categories IDs
 */
function postcats_get_byID( $post_ID )
{
	global $DB, $cache_postcats;

	if( ! is_array( $cache_postcats ) )
	{	// Initialize cache array first time:
		$cache_postcats = array();
	}

	if( ! isset( $cache_postcats[ $post_ID ] ) )
	{	// Get data from DB if it is not still in cache:
		$SQL = new SQL( 'Get category associations with given item #'.$post_ID );
		$SQL->SELECT( 'postcat_cat_ID' );
		$SQL->FROM( 'T_postcats' );
		$SQL->WHERE( 'postcat_post_ID = '.$DB->quote( $post_ID ) );
		$SQL->ORDER_BY( 'postcat_cat_ID' );

		$cache_postcats[ $post_ID ] = $DB->get_col( $SQL->get(), 0, $SQL->title );
	}

	return $cache_postcats[ $post_ID ];
}


/**
 * Does a given blog have categories?
 *
 * @param integer Blog ID
 * @return boolean
 */
function blog_has_cats( $blog_ID )
{
	$ChapterCache = & get_ChapterCache();
	return $ChapterCache->has_chapters_in_subset( $blog_ID );
}


/**
 * Compiles the cat array from $cat (recursive + optional modifiers) and $catsel[] (non recursive)
 *
 * @param string
 * @param array
 * @param array by ref, will be modified
 * @param string by ref, will be modified
 * @param integer blog number to restrict to
 */
function compile_cat_array( $cat, $catsel, & $cat_array, & $cat_modifier, $restrict_to_blog = 0  )
{
	// echo '$cat='.$cat;
	// pre_dump( $catsel );
	// echo '$restrict_to_blog'.$restrict_to_blog;

	$cat_array = array();
	$cat_modifier = '';

	// Check for cat string (which will be handled recursively)
	if( $cat != 'all' && !empty($cat) )
	{ // specified a category string:
		$cat_modifier = substr($cat, 0, 1 );
		// echo 'cats['.$first_char.']';
		if( ( $cat_modifier == '*' ) ||
		    ( $cat_modifier == '-' ) ||
		    ( $cat_modifier == '|' ) )
		{
			$cat = substr( $cat, 1 );
		}
		else
		{
			$cat_modifier = '';
		}

		if( strlen( $cat ) )
		{	// There are some values to explode...
			$req_cat_array = explode(',', $cat);

			// Getting required sub-categories:
			// and add everything to cat array
			// ----------------- START RECURSIVE CAT LIST ----------------
			$ChapterCache = & get_ChapterCache();
			if( $restrict_to_blog > 0 )
			{ // Load all Chapters from the given blog
				$ChapterCache->reveal_children( $restrict_to_blog, true );
			}
			else
			{ // Load all chapters
				$ChapterCache->reveal_children( NULL, true );
			}

			foreach( $req_cat_array as $cat_ID )
			{ // run recursively through the cats
				$current_Chapter = $ChapterCache->get_by_ID( $cat_ID, false );
				if( empty( $current_Chapter ) )
				{ // The requested Chapter doesn't exists in the given context
					continue;
				}
				if( ! in_array( $cat_ID, $cat_array ) )
				{ // Not already in list
					$cat_array[] = $cat_ID;
					$ChapterCache->iterate_through_category_children( $current_Chapter, array( 'line' => 'cat_req' ), true, array( 'sorted' => true ) );
				}
			}
			// ----------------- END RECURSIVE CAT LIST ----------------
		}
	}

	// Add explicit selections:
	if( ! empty( $catsel ))
	{
		// echo "Explicit selections!<br />";
		$cat_array = array_merge( $cat_array, $catsel );
		$cat_array = array_unique( $cat_array );
	}

	// echo '$cat_modifier='.$cat_modifier;
	// pre_dump( $cat_array );

}


/**
 * Callback used in compile_cat_array()
 */
function cat_req( $Chapter, $level )
{
	global $cat_array;

	if( ! in_array( $Chapter->ID, $cat_array ) )
	{ // Not already visited
		$cat_array[] = $Chapter->ID;
	}
}


/**
 * Get global cross posting settings -- (cross posting = 1 post in multiple blogs)
 *
 * @return int
 * 		0 - cross posting disabled
 * 		1 - cross posting enabled for extra categories
 * 		2 - cross posting enabled for main categories
 * 		3 - cross posting enabled for main and extracats
 */
function get_allow_cross_posting()
{
	global $Settings;
	return $Settings->get( 'cross_posting' ) + ( 2 *  $Settings->get( 'cross_posting_blogs' ) );
}


/**
 * In-skin display of a Chapter.
 * It is a wrapper around the skin '_cat_list.inc.php' file.
 *
 * @param Object Chapter
 */
function cat_inskin_display( $Chapter )
{
	skin_include( '_cat_list.inc.php', array(
					'Chapter' => $Chapter,
				) );
}

?>