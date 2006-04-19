<?php
/**
 * This file implements Category handling functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2004 by The University of North Carolina at Charlotte as
 * contributed by Jason Edgecombe {@link http://tst.uncc.edu/team/members/jason_bio.php}.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * The University of North Carolina at Charlotte grants Francois PLANQUE the right to license
 * Jason EDGECOMBE's contributions to this file and the b2evolution project
 * under the GNU General Public License (http://www.opensource.org/licenses/gpl-license.php)
 * and the Mozilla Public License (http://www.opensource.org/licenses/mozilla1.1.php).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author jwedgeco: Jason EDGECOMBE (for hire by UNC-Charlotte)
 * @author edgester: Jason EDGECOMBE (personal contributions, not for hire)
 *
 * @todo implement CategoryCache based on LinkCache
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * cat_create(-)
 *
 * Create a new category
 * This funtion has to handle all needed DB dependencies!
 *
 * fplanque: created
 */
function cat_create(
	$cat_name,
	$cat_parent_ID,
	$cat_blog_ID = NULL)
{
	global $DB;

	if( $cat_blog_ID == NULL )
	{
		if( empty($cat_parent_ID) ) die ( 'cat_create(-) missing parameters!' );
		$parent_cat = get_the_category_by_ID($cat_parent_ID);
		$cat_blog_ID = $parent_cat['cat_blog_ID'];
	}

	$sql = "INSERT INTO T_categories( cat_parent_ID, cat_name, cat_blog_ID)
					VALUES ( $cat_parent_ID, ".$DB->quote($cat_name).", $cat_blog_ID )";
	if( ! $DB->query( $sql ) )
		return 0;

	return $DB->insert_id;
}


/*
 * cat_update(-)
 *
 * Update a category
 * This funtion has to handle all needed DB dependencies!
 *
 * fplanque: created
 */
function cat_update(
	$cat_ID,
	$cat_name,
	$cat_parent_ID = 0,
	$cat_blog_ID = '' )
{
	global $DB;

	if( $cat_parent_ID == 0 ) $cat_parent_ID = 'NULL';

	return $DB->query( "UPDATE T_categories
												SET cat_name = ".$DB->quote($cat_name).",
														cat_parent_ID = $cat_parent_ID ".
														(!empty($cat_blog_ID) ? ", cat_blog_ID = $cat_blog_ID" : '')."
											WHERE cat_ID = $cat_ID" );
}


/**
 * Delete a category
 *
 * This funtion has to handle all needed DB dependencies!
 *
 * @todo BUG: it is possible to create loops and lose branch in some situations :(
 */
function cat_delete( $cat_ID )
{
	global $DB, $query, $cache_categories, $cache_postcats;

	$DB->begin();

	// check there are no subcats
	$sql = "SELECT COUNT(*)
					FROM T_categories
					WHERE cat_parent_ID = $cat_ID";
	$child_count = $DB->get_var( $sql );
	if( $child_count != 0 ) return T_("Cannot delete if there are sub-categories!");

	// find parent
	$sql = "SELECT cat_parent_ID, cat_blog_ID
					FROM T_categories
					WHERE cat_ID = $cat_ID";
	if( ! ($row = $DB->get_row( $sql )) )
	{
  	$DB->rollback(); // done nothing anyway...
		return 1; // Success: category already deleted!!
	}

	$remap_cat_ID = $row->cat_parent_ID;
	$cat_blog_ID = $row->cat_blog_ID;

	// Get the list of posts in this category
	$sql = "SELECT post_ID
					  FROM T_posts
					 WHERE post_main_cat_ID = $cat_ID";
	$IDarray = $DB->get_col( $sql );

	if( ! $remap_cat_ID )
	{ // No parent, find another cat in same blog
		$sql = "SELECT cat_ID
						FROM T_categories
						WHERE cat_blog_ID = $cat_blog_ID
							AND cat_ID != $cat_ID
						ORDER BY cat_ID
						LIMIT 0, 1";
		$remap_cat_ID = $DB->get_var( $sql );
		// echo "remap to: $remap_cat_ID<br />";
		// May be NULL if this was the last cat (But there are no posts inside)

		if( !$remap_cat_ID && !empty($IDarray) )
		{
	  	$DB->rollback(); // done nothing anyway...
			return T_("Cannot delete last category if there are posts inside!");
		}
	}

	//  --------------- PROCEED WITH DELETING ------------

	// First delete assoc to this cat when it's an extra cat
	$sql = "DELETE FROM T_postcats
						WHERE postcat_cat_ID = $cat_ID ";
	if( !empty($IDarray) )
	{
		$sql .= " AND postcat_post_ID NOT IN (".implode( ',', $IDarray ).") ";
	}

	$DB->query( $sql );


	// Now take care of the main cats (these need to be remapped, we cannot delete them!)
	if( $remap_cat_ID )
	{ // We are moving posts to parent or other category

		// remap the posts to new category:
		// Get the list of posts in this category
		$sql = "UPDATE T_posts
							 SET post_main_cat_ID = $remap_cat_ID
						 WHERE post_main_cat_ID = $cat_ID";
		$DB->query( $sql );

		// Before remapping the extracats we need to get rid of mappings that would become duplicates
		// We remove every mapping to the old cat where a mapping to the new cat already exists
		$sql = "SELECT DISTINCT postcat_post_ID
						FROM T_postcats
						WHERE postcat_cat_ID = $remap_cat_ID";
		$IDarray = $DB->get_col( $sql );

		if( !empty($IDarray) )
		{
			$IDlist = implode( ',', $IDarray );

			$sql = "DELETE FROM T_postcats
							WHERE postcat_cat_ID = $cat_ID
							AND postcat_post_ID IN ($IDlist)";
			$DB->query( $sql );
		}


		// remap the remaining extracats
		$sql = "UPDATE T_postcats
						SET postcat_cat_ID = $remap_cat_ID
						WHERE postcat_cat_ID = $cat_ID";
		$DB->query( $sql );
	}

	// do the actual deletion of the cat
	$sql = "DELETE FROM T_categories
					WHERE cat_ID = $cat_ID";
	$DB->query( $sql );

	$DB->commit();

	// If we had a cache we'd better forget it!
	// TODO: reset other caches!
	unset( $GLOBALS['cache_categories'] );
	unset( $GLOBALS['cache_postcats'] );

	return 1; // success
}




/**
 * get_the_category_by_ID(-)
 *
 * Get category name+blog_id for specified cat ID
 *
 * fplanque: reused "R. U. Serious" optimization here
 * fplanque: added blog ID stuff
 * TODO: move. dis is not a template tag
 *
 * @param integer category ID
 * @param boolean die() if category does not exist? (default: true)
 *
 */
function get_the_category_by_ID( $cat_ID, $die = true )
{
	global $cache_categories;
	if( empty($cache_categories[$cat_ID]) )
	{
		cat_load_cache( 'none' );
	}
	if( !isset( $cache_categories[$cat_ID] ) )
	{
		if( $die )
		{
			die( sprintf( T_('Requested category %s does not exist!'),  $cat_ID ) );
		}
		else return false;
	}
	return $cache_categories[$cat_ID];
}


/*
 * get_the_category(-)
 *
 * Get category name for current post
 *
 * fplanque: simplified
 *
 * @deprecated
 */
function get_the_category()
{
	global $postdata;
	$cat = get_the_category_by_ID( $postdata['Category'] );
	return $cat['cat_name'];
}



/*
 * get_catblog(-)
 *
 * Get blog for a given cat
 * fplanque: added
 */
function get_catblog( $cat_ID )
{
	$cat = get_the_category_by_ID( $cat_ID );
	return $cat['cat_blog_ID'];
}


/*
 * get_catparent(-)
 *
 * Get parent category for a given cat
 * fplanque: added
 */
function get_catparent( $cat_ID )
{
	$cat = get_the_category_by_ID( $cat_ID );
	return $cat['cat_parent_ID'];
}

/*
 * get_catname(-)
 *
 * Get name for a given cat
 * fplanque: reduced to the max
 */
function get_catname($cat_ID)
{
	$cat = get_the_category_by_ID( $cat_ID );
	return $cat['cat_name'];
}


/**
 * Load cache for category definitions.
 *
 * TODO: replace LEFT JOIN with UNION when switching to MySQL 4
 * This will prevent empty cats from displaying "(1)" as postcount.
 *
 * @param string 'none'|'context'|'canonic'
 */
function cat_load_cache( $cat_load_postcounts = 'none', $dbtable_items = 'T_posts', $dbprefix_items = 'post_',
													$dbIDname_items = 'post_ID' )
{
	global $DB, $cache_categories;
	global $show_statuses, $timestamp_min, $timestamp_max;
	global $Settings;

	if( !isset($cache_categories))
	{
		// echo "loading CAT cache";
		$sql = "SELECT cat_ID, cat_parent_ID, cat_name, cat_blog_ID
						FROM T_categories
						ORDER BY cat_name";

		foreach( $DB->get_results( $sql, ARRAY_A ) as $myrow )
		{
			$this_cat['cat_name'] = $myrow['cat_name'];
			$this_cat['cat_blog_ID'] = $myrow['cat_blog_ID'];
			$this_cat['cat_parent_ID'] = $myrow['cat_parent_ID'];
			$this_cat['cat_postcount'] = 0;					// Will be increased later
			$this_cat['cat_children'] = array();
			$cache_categories[$myrow['cat_ID']] = $this_cat;
			// echo 'just cached:',$myrow['cat_ID'],':',$cache_categories[$myrow['cat_ID']]['cat_name'], ' parent:',$cache_categories[$myrow['cat_ID']]['cat_parent_ID'],'<br />';
		}

		// echo 'Number of cats=', count($cache_categories);

		// Reveal children:
		if( ! empty( $cache_categories ) )
		{
			foreach( $cache_categories as $icat_ID => $i_cat )
			{
				// echo '<br>handling cat ', $icat_ID, ' ', $i_cat['cat_name'];
				$cat_parent_ID = $i_cat['cat_parent_ID'];
				if( $cat_parent_ID )
				{
					if( isset( $cache_categories[$cat_parent_ID] ) )
					{ // If the parent exists!
						$cache_categories[$cat_parent_ID]['cat_children'][] = $icat_ID;
					}
					else
					{
						echo( "Catgeory #$icat_ID is oprhan of non existant parent #$cat_parent_ID!<br />" );
					}
				}
			}
		}

		// echo 'Number of cats=', count($cache_categories);
	}

	cat_load_postcounts( $cat_load_postcounts, $dbtable_items, $dbprefix_items, $dbIDname_items );
}


/**
 * Load the post counts
 *
 * @param string 'context'|'canonic'
 */
function cat_load_postcounts( $cat_load_postcounts = 'canonic', $dbtable = 'T_posts', $dbprefix = 'post_', $dbIDname = 'post_ID' )
{
	global $DB, $cache_categories;
	global $blog, $show_statuses, $author, $assgn, $status;
	global $m, $w, $dstart, $timestamp_min, $timestamp_max;
	global $s, $sentence, $exact;
	global $cat_postcounts_loaded;
	global $Settings;

	if( $cat_load_postcounts == 'none' )
	{
		return;
	}

	if( !isset($cat_postcounts_loaded) && $blog > 0 )
	{ // Postcounts are not loaded and we have a blog for which to load the counts:

		/*
		 * WE ARE GOING TO CONSTRUCT THE WHERE CLOSE...
		 */

		$ItemQuery = & new ItemQuery( $dbtable, $dbprefix, $dbIDname ); // TEMPORARY OBJ

		// - - Select a specific Item:
		// $ItemQuery->where_ID( $p, $title );
		if( $cat_load_postcounts == 'context' )
		{	// We want to preserve the current context:
			// - - - Restrict to selected blog/categories:
			$ItemQuery->where_chapter( $blog, '', array() );

			// * Restrict to the statuses we want to show:
			$ItemQuery->where_visibility( $show_statuses );

			// Restrict to selected authors:
			$ItemQuery->where_author( $author );

			// Restrict to selected assignees:
			$ItemQuery->where_assignees( $assgn );

			// Restrict to selected satuses:
			$ItemQuery->where_statuses( $status );

			// - - - + * * timestamp restrictions:
			$ItemQuery->where_datestart( $m, $w, $dstart, '', $timestamp_min, $timestamp_max );

			// Keyword search stuff:
			$ItemQuery->where_keywords( $s, $sentence, $exact );
		}
		else
		{	// We want to preserve only the minimal context:
			// - - - Restrict to selected blog/categories:
			$ItemQuery->where_chapter( $blog, '', array() );

			// * Restrict to the statuses we want to show:
			$ItemQuery->where_visibility( $show_statuses );

			// - - - + * * timestamp restrictions:
			$ItemQuery->where_datestart( '', '', '', '', $timestamp_min, $timestamp_max );
		}

		$sql = 'SELECT postcat_cat_ID AS cat_ID, COUNT(*) AS cat_postcount'
						// OLD: FROM T_postcats INNER JOIN $dbtable ON postcat_post_ID = $dbIDname
						// fplanque>> note: there was no restriction to current blog!!
						.$ItemQuery->get_from()
						.$ItemQuery->get_where()
						.$ItemQuery->get_group_by()."
						GROUP BY cat_ID";

		foreach( $DB->get_results( $sql, ARRAY_A, 'Load postcounts' ) as $myrow )
		{
			$cat_ID = $myrow['cat_ID'];
			if( !isset($cache_categories[$cat_ID]) )
				echo '<p>*** WARNING: There are '.$myrow['cat_postcount'].' posts attached to non existant category #'.$cat_ID.'. You must fix the database! ***</p>';
			// echo 'Postcount for cat #', $cat_ID, ' is ', $myrow['cat_postcount'], '<br />';
			$cache_categories[$cat_ID]['cat_postcount'] = $myrow['cat_postcount'];
		}

		// echo 'Number of cats=', count($cache_categories);
		$cat_postcounts_loaded = true;
	}

}


/**
 * Load cache for category associations with current posts
 *
 * @todo put this into main post query when MySQL 4.0 commonly available
 */
function cat_load_postcats_cache()
{
	global $DB, $cache_postcats, $postIDlist, $preview;

	if( isset($cache_postcats) )
	{ // already done!
		return;
	}

	if( $preview )
	{ // Preview mode
		global $extracats, $post_category;
		param( 'extracats', 'array', array() );
		if( !in_array( $post_category, $extracats ) )
			$extracats[] = $post_category;
		$cache_postcats[0] = $extracats;
		return;
	}

	if( !empty($postIDlist) )
	{
		$sql = "SELECT postcat_post_ID, postcat_cat_ID
						FROM T_postcats
						WHERE postcat_post_ID IN ($postIDlist)
						ORDER BY postcat_post_ID, postcat_cat_ID";

		foreach( $DB->get_results( $sql, ARRAY_A ) as $myrow )
		{
			$postcat_post_ID = $myrow["postcat_post_ID"];
			if( ! isset( $cache_postcats[$postcat_post_ID] ) )
			{
				 $cache_postcats[$postcat_post_ID] = array();
			}
			$cache_postcats[$postcat_post_ID][] = $myrow["postcat_cat_ID"];
			// echo "just cached: post=$postcat_post_ID  cat=".$myrow["postcat_cat_ID"]."<br />";
		}
	}
}


/*
 * postcats_get_byID(-)
 *
 * Get category associations with given post
 *
 * fplanque: created
 */
function postcats_get_byID( $post_ID )
{
	global $DB;

	//echo "looking up cats for post $post_ID ";

	$sql = "SELECT postcat_cat_ID
					FROM T_postcats
					WHERE postcat_post_ID = $post_ID
					ORDER BY postcat_cat_ID";
	return $DB->get_col( $sql );
}


/**
 * Taking a recursive walk in the category park...
 *
 * @param array PHP requires this stupid cloning of the cache_categories array in order to be able to perform foreach on it
 * @param integer
 * @param integer
 * @param string|array Callback for first category
 * @param string|array Callback before each category
 * @param string|array Callback after each category
 * @param string|array Callback after last category
 * @param integer Caller nesting level, just to keep track of how far we go :)
 * @return string
 */
function cat_children( $ccats, $blog_ID, $parent_ID, $callback_before_first, $callback_before_each, $callback_after_each, $callback_after_last, $level = 0, $root_call = true )
{
	static $total_count = 0;

	$r = '';

	// echo 'Number of cats=', count($ccats);
	if( empty( $ccats ) )
	{ // this can happen if there are no cats at all!
		return '';
	}

	if( $root_call )
	{ // Init:
		$total_count = 0;
	}

	$child_count = 0;
	foreach( $ccats as $icat_ID => $i_cat )
	{
		if( empty( $icat_ID )
			|| ! (($blog_ID == 0) || ($i_cat['cat_blog_ID'] == $blog_ID))
			|| ! ($i_cat['cat_parent_ID'] == $parent_ID) )
		{ // this cat is not in the blog and or is not a child of the parent
			continue;
		}

		// this cat is in the blog and is a child of the parent...
		$total_count++;


		// "before first":
		if( $child_count++ == 0 )
		{ // this is the first child
			if( is_array( $callback_before_first ) )
			{ // object callback:
				$r .= $callback_before_first[0]->{$callback_before_first[1]}( $parent_ID, $level, $total_count, 1 );
			}
			else
				$r .= $callback_before_first( $parent_ID, $level, $total_count, 1 );
		}

		// "before each":
		if( is_array( $callback_before_each ) )
		{ // object callback:
			$r2 = $callback_before_each[0]->{$callback_before_each[1]}( $icat_ID, $level, $total_count, $child_count );
		}
		else
			$r2 = $callback_before_each( $icat_ID, $level, $total_count, $child_count );
		if( $r2 === true )
		{	// callback function has requested that we stop recursing for this branch
			continue;
		}
		$r .= $r2;

		// Recursion:
		$r .= cat_children( $ccats, $blog_ID, $icat_ID, $callback_before_first, $callback_before_each, $callback_after_each, $callback_after_last, $level+1, false );

		// "after each":
		if( is_array( $callback_after_each ) )
		{ // object callback:
			$r .= $callback_after_each[0]->{$callback_after_each[1]}( $icat_ID, $level, $total_count, $child_count );
		}
		else
		{
			$r .= $callback_after_each( $icat_ID, $level, $total_count, $child_count );
		}
	}
	if( $child_count )
	{ // There have been children
		if( is_array( $callback_after_last ) )
			$r .= $callback_after_last[0]->{$callback_after_last[1]}( $parent_ID, $level, $total_count, $child_count );
		else
			$r .= $callback_after_last( $parent_ID, $level, $total_count, $child_count );
	}

	return $r;
}


/**
 * Does a given bog have categories?
 * @param integer Blog ID
 * @return boolean
 */
function blog_has_cats( $blog_ID )
{
	global $cache_categories;

	cat_load_cache( 'none' );

	if( count($cache_categories) ) foreach( $cache_categories as $icat_ID => $i_cat )
	{
		if( $icat_ID && $i_cat['cat_blog_ID'] == $blog_ID )
		{ // this cat is in the blog
			return true;
		}
	}

	return false;
}


/*
 * Functions to be called from the template
 */

/**
 * Query for the cats
 *
 * @param string 'none'|'context'|'canonic'
 */
function cat_query( $load_postcounts = 'none', $dbtable_items = 'T_posts', $dbprefix_items = 'post_',
										$dbIDname_items = 'post_ID' )
{
	// global $cache_categories; // $cache_blogs,
	global $blog;
	if( $blog != 0 ) blog_load_cache();
	cat_load_cache( $load_postcounts, $dbtable_items, $dbprefix_items, $dbIDname_items );
}


/**
 * the_category(-)
 *
 * echoes the main category name
 * the name of the main category the post belongs to.
 * you can as an admin add categories, and rename them if needed.
 * default category is 'General', you can rename it too.
 *
 * @deprecated deprecated by {@link Item::main_category()}
 */
function the_category( $format = 'htmlbody' )
{
	$category = get_the_category();
	echo format_to_output($category, $format);
}


/**
 * the_categories(-)
 *
 * lists all the category names
 *
 * fplanque: created
 * fplanque: 0.8.3: changed defaults
 *
 * @deprecated deprecated by {@link Item::categories()}
 */
function the_categories( $link_title = '#',				// false if you want no links
	$before_main='<strong>', $after_main='</strong>', // 'hide' to ignore main cat
	$before_other='', $after_other='', 								// 'hide' to ignore other cats
	$before_external='<em>', $after_external='</em>',	// 'hide' to ignore external cats (other blogs)
	$separator = ', ',
	$format_each = 'raw',
	$format_list = 'htmlbody'
 )
{
	global $id, $postdata, $blog, $blogfilename, $cache_postcats, $preview;
	global $BlogCache;

	if( $link_title == '#' )
	{ /* TRANS: When the categories for a specific post are displayed, the user can click
				on these cats to browse them, this is the default href title displayed there */
		$link_title = T_('Browse category');
	}

	$main_cat_ID = $postdata['Category'];
	// echo "main cat ID: $main_cat_ID<br />";
	cat_load_postcats_cache();
	$categoryIDs = $cache_postcats[$id];

	if( !isset($categoryIDs) )
	{ // Can happen in preview mode
		return;
	}

	$categoryNames = array();
	foreach( $categoryIDs as $cat_ID )
	{
		$cat = get_the_category_by_ID($cat_ID);
		$cat_name = format_to_output( $cat["cat_name"], $format_each );

		if( $link_title && !$preview)
		{ // we want to display links
			$lBlog = & $BlogCache->get_by_ID( $cat['cat_blog_ID'] );
			$cat_name = '<a href="'.url_add_param( $lBlog->get('blogurl'), 'cat='.$cat_ID ).'" title="'.$link_title.'">'.$cat_name.'</a>';
		}

		if( $cat_ID == $main_cat_ID )
		{ // We are displaying the main cat!
			if( $before_main == 'hide' )
			{ // ignore main cat !!!
				continue;
			}
			$cat_name = $before_main.$cat_name.$after_main;
		}
		elseif( $cat['cat_blog_ID'] == $blog )
		{ // We are displaying another cat in the same blog
			if( $before_other == 'hide' )
			{ // ignore main cat !!!
				continue;
			}
			$cat_name = $before_other.$cat_name.$after_other;
		}
		else
		{ // We are displaying an external cat (in another blog)
			if( $before_external == 'hide' )
			{ // ignore main cat !!!
				continue;
			}
			$cat_name = $before_external.$cat_name.$after_external;
		}

		$categoryNames[] = $cat_name;
	}
	echo format_to_output( implode( $separator, $categoryNames ), $format_list );
}


/**
 * Echoes the main category ID for current post
 *
 * The ID (number) of the category the post belongs to.
 * This is static data that you can use, for example to associate a category to an image, or a css style.
 */
function the_category_ID()
{
	global $id,$postdata;
	echo $postdata['Category'];
}


/**
 * lists the category IDs for current post
 *
 * fplanque: created
 */
function the_categories_IDs()
{
	global $id, $blogfilename, $cache_postcats;

	cat_load_postcats_cache();
	$categoryIDs = $cache_postcats[$id];

	if( !isset($categoryIDs) )
	{ // Can happen in preview mode
		return;
	}

	echo implode( ',', $categoryIDs );

}


/*
 * the_category_head(-)
 */
function the_category_head( $before='', $after='' )
{
	global $id, $postdata, $currentcat, $previouscat, $newday;
	$currentcat = $postdata['Category'];
	if ($currentcat != $previouscat)
	{
		echo $before;
		$cat = get_the_category_by_ID($currentcat);	// fplanque
		echo $cat['cat_name'];
		echo $after;
		$previouscat = $currentcat;
	}
}


/**
 * Copy the catagory structure from one blog to another
 * The four cat_copy_* functions after blog_copy_cats are required by blog_copy_cats()
 */
function blog_copy_cats($srcblog, $destblog)
 {
	global $BlogCache, $edited_Blog, $cache_categories, $cat_parents;

	$edited_Blog = & $BlogCache->get_by_ID( $destblog );

	// ----------------- START RECURSIVE CAT LIST ----------------
	cat_query( 'none' );	// make sure the caches are loaded
	$cat_parents[0]='NULL';

	// run recursively through the cats
	echo "<ul>\n";
	cat_children( $cache_categories, $srcblog, NULL, 'cat_copy_before_first', 'cat_copy_before_each', 'cat_copy_after_each', 'cat_copy_after_last', 0 );
	echo "</ul>\n";
	// ----------------- END RECURSIVE CAT LIST ----------------
}

/**
 * callback to start sublist
 */
function cat_copy_before_first( $parent_cat_ID, $level )
{ // callback to start sublist
}

/**
 * callback to display sublist element
 */
function cat_copy_before_each( $cat_ID, $level )
{ // callback to display sublist element
	global $cat_parents, $edited_Blog;
	$cat = get_the_category_by_ID( $cat_ID );
	echo '<li>';
	echo ' <strong>Copying '.$cat['cat_name'].'</strong> level: ' . $level;
	$cat_parents[$level+1]=cat_create( $cat['cat_name'], $cat_parents[$level] , $edited_Blog->ID);
}

/**
 * callback to display sublist element
 */
function cat_copy_after_each( $cat_ID, $level )
{ // callback to display sublist element
	echo "</li>\n";
}

/**
 * callback to end sublist
 */
function cat_copy_after_last( $parent_cat_ID, $level )
{ // callback to end sublist
}

/**
 * Compiles the cat array from $cat (recursive + optional modifiers) and $catsel[] (non recursive)
 *
 * @param strinf
 * @param array
 * @param array by ref, will be modified
 * @param string by ref, will be modified
 * @param integer blog number to restrict to
 */
function compile_cat_array( $cat, $catsel, & $cat_array, & $cat_modifier, $restrict_to_blog = 0  )
{
	global $cache_categories;

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
		if( ($cat_modifier == '*')
			|| ($cat_modifier == '-') )
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
			cat_query( 'none' );	// make sure the caches are loaded
			foreach( $req_cat_array as $cat_ID )
			{ // run recursively through the cats
				if( ! in_array( $cat_ID, $cat_array ) )
				{ // Not already in list
					$cat_array[] = $cat_ID;
					cat_children( $cache_categories, $restrict_to_blog, $cat_ID, 'cat_req_dummy', 'cat_req',
												'cat_req_dummy', 'cat_req_dummy', 1 );
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



function cat_req( $parent_cat_ID, $level )
{
	global $cat_array;
	// echo "[$parent_cat_ID] ";
	if( ! in_array( $parent_cat_ID, $cat_array ) )
	{ // Not already visited
		$cat_array[] = $parent_cat_ID;
	}
	else
	{
		// echo "STOP! ALREADY VISITED THIS ONE!";
		return -1;		// STOP going through that branch
	}
}


function cat_req_dummy() {}


/*
 * $Log$
 * Revision 1.5  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.4  2006/04/11 21:22:25  fplanque
 * partial cleanup
 *
 * Revision 1.3  2006/04/06 13:49:49  blueyed
 * Background "striping" for "Categories" fieldset
 *
 * Revision 1.2  2006/03/12 23:08:58  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:57  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.30  2006/01/04 20:35:14  fplanque
 * no message
 *
 * Revision 1.29  2005/12/30 20:13:39  fplanque
 * UI changes mostly (need to double check sync)
 *
 * Revision 1.28  2005/12/12 19:44:09  fplanque
 * Use cached objects by reference instead of copying them!!
 *
 * Revision 1.27  2005/12/12 19:21:21  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.26  2005/10/03 18:10:07  fplanque
 * renamed post_ID field
 *
 * Revision 1.25  2005/09/06 19:38:29  fplanque
 * bugfixes
 *
 * Revision 1.24  2005/09/06 17:13:54  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.23  2005/09/03 00:21:48  fplanque
 * bugfixes
 *
 * Revision 1.22  2005/09/02 21:46:40  fplanque
 * doc
 *
 * Revision 1.21  2005/09/01 17:11:46  fplanque
 * no message
 *
 * Revision 1.20  2005/08/25 17:45:19  fplanque
 * started categories plugin
 *
 * Revision 1.19  2005/08/25 16:06:45  fplanque
 * Isolated compilation of categories to use in an ItemList.
 * This was one of the oldest bugs on the list! :>
 *
 * Revision 1.18  2005/06/06 17:59:39  fplanque
 * user dialog enhancements
 *
 * Revision 1.17  2005/05/10 18:35:38  fplanque
 * refactored/normalized category handling
 * (though there's still a lot to do before this gets as clean as desired...)
 *
 * Revision 1.16  2005/03/14 20:22:19  fplanque
 * refactoring, some cacheing optimization
 *
 * Revision 1.15  2005/03/09 14:54:26  fplanque
 * refactored *_title() galore to requested_title()
 *
 * Revision 1.14  2005/03/07 17:36:11  fplanque
 * made more generic
 *
 * Revision 1.13  2005/03/02 17:07:33  blueyed
 * no message
 *
 * Revision 1.12  2005/02/28 09:06:32  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.11  2005/02/15 22:05:06  blueyed
 * Started moving obsolete functions to _obsolete092.php..
 *
 * Revision 1.10  2005/02/08 04:45:02  blueyed
 * improved $DB get_results() handling
 *
 * Revision 1.9  2005/02/08 04:07:46  blueyed
 * fixed results from DB::get_var()
 *
 * Revision 1.8  2005/01/25 15:07:19  fplanque
 * cleanup
 *
 * Revision 1.7  2004/12/15 20:50:34  fplanque
 * heavy refactoring
 * suppressed $use_cache and $sleep_after_edit
 * code cleanup
 *
 * Revision 1.6  2004/12/13 21:29:13  fplanque
 * refactoring
 *
 * Revision 1.5  2004/12/06 21:45:24  jwedgeco
 * Added header info and granted Francois PLANQUE the right to relicense under the Mozilla Public License.
 *
 * Revision 1.4  2004/11/30 21:51:34  jwedgeco
 * when copying a blog, categories are copied as well.
 *
 * Revision 1.3  2004/11/09 00:25:11  blueyed
 * minor translation changes (+MySQL spelling :/)
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.39  2004/10/12 17:22:29  fplanque
 * Edited code documentation.
 *
 */
?>