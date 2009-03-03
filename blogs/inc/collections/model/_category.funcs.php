<?php
/**
 * This file implements Category handling functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2004 by The University of North Carolina at Charlotte as
 * contributed by Jason Edgecombe {@link http://tst.uncc.edu/team/members/jason_bio.php}.
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
 * Create a new category
 *
 * This funtion has to handle all needed DB dependencies!
 *
 * @param string Category name
 * @param string Category ID ('NULL' as string(!) for root)
 * @param integer|NULL Blog ID (will be taken from parent cat, if empty)
 */
function cat_create(
	$cat_name,
	$cat_parent_ID,
	$cat_blog_ID = NULL)
{
	global $DB;

	if( $cat_blog_ID == NULL )
	{
		if( empty($cat_parent_ID) ) debug_die ( 'cat_create(-) missing parameters!' );
		$parent_cat = get_the_category_by_ID($cat_parent_ID);
		$cat_blog_ID = $parent_cat['cat_blog_ID'];
	}

	// Dirty temporary fix:
	$cat_urlname = preg_replace( '/[^a-z0-9]/', '-', strtolower($cat_name) );

	$sql = "INSERT INTO T_categories( cat_parent_ID, cat_name, cat_blog_ID, cat_urlname )
					VALUES ( $cat_parent_ID, ".$DB->quote($cat_name).", $cat_blog_ID, ".$DB->quote($cat_urlname)." )";
	if( ! $DB->query( $sql ) )
		return 0;

	return $DB->insert_id;
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
			debug_die( sprintf( T_('Requested category %s does not exist!'),  $cat_ID ) );
		}
		else return false;
	}
	return $cache_categories[$cat_ID];
}



/**
 * Get blog ID for a given cat.
 * This halts on error.
 * @return integer
 */
function get_catblog( $cat_ID )
{
	$cat = get_the_category_by_ID( $cat_ID );
	return $cat['cat_blog_ID'];
}


/**
 * Load cache for category definitions.
 *
 * Warning: this loads all categories for ALL blogs
 */
function cat_load_cache()
{
	global $DB, $cache_categories;
	global $timestamp_min, $timestamp_max;
	global $Settings;
	global $Timer;

	if( isset($cache_categories))
	{
		return;
	}

	$Timer->resume( 'cat_load_cache' );

	// echo "loading CAT cache";
	$sql = 'SELECT cat_ID, cat_parent_ID, cat_name, cat_blog_ID
					FROM T_categories';
	if( $Settings->get('chapter_ordering') == 'manual' )
	{	// Manual order
		$sql .= ' ORDER BY cat_order';
	}
	else
	{	// Alphabetic order
		$sql .= ' ORDER BY cat_name';
	}


	foreach( $DB->get_results( $sql, ARRAY_A, 'loading CAT cache' ) as $myrow )
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
					echo( "Category #$icat_ID is orphan of non existant parent #$cat_parent_ID!<br />" );
				}
			}
		}
	}

	// echo 'Number of cats=', count($cache_categories);

	$Timer->pause( 'cat_load_cache' );
}


/**
 * Load cache for category associations with current posts
 *
 * @todo put this into main post query when MySQL 4.0 commonly available
 * @todo dh> why is this limited to the _global_ $postIDlist?!
 *           Really ridiculous, trying to get a list of category names for an Item (which is not in $postIDlist for example.. :/)
 * fp> This is legacy from a quick b2/cafelog hack. This will de deprecated.
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


/**
 * Get category associations with given post
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
			|| ! ( /* TODO: check ($blog_ID == 0) || */ ($i_cat['cat_blog_ID'] == $blog_ID))
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
 * Does a given blog have categories?
 *
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
			cat_load_cache();	// make sure the caches are loaded
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


/**
 * Callback used in compile_cat_array()
 */
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

/**
 * Callback used in compile_cat_array()
 */
function cat_req_dummy()
{
}

/*
 * $Log$
 * Revision 1.4  2009/03/03 20:34:52  blueyed
 * doc
 *
 * Revision 1.3  2009/01/28 21:23:22  fplanque
 * Manual ordering of categories
 *
 * Revision 1.2  2008/01/21 09:35:26  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 10:59:32  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.29  2007/05/28 01:33:22  fplanque
 * permissions/fixes
 *
 * Revision 1.28  2007/05/09 00:58:55  fplanque
 * massive cleanup of old functions
 *
 * Revision 1.27  2007/04/26 00:11:06  fplanque
 * (c) 2007
 *
 * Revision 1.26  2007/03/24 20:41:16  fplanque
 * Refactored a lot of the link junk.
 * Made options blog specific.
 * Some junk still needs to be cleaned out. Will do asap.
 *
 * Revision 1.25  2006/12/17 23:42:38  fplanque
 * Removed special behavior of blog #1. Any blog can now aggregate any other combination of blogs.
 * Look into Advanced Settings for the aggregating blog.
 * There may be side effects and new bugs created by this. Please report them :]
 *
 * Revision 1.24  2006/11/26 02:30:39  fplanque
 * doc / todo
 *
 * Revision 1.23  2006/11/24 18:27:23  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.22  2006/11/22 20:38:17  blueyed
 * todo
 */
?>