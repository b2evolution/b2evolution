<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file built upon code from original b2 - http://cafelog.com/
 */

/*
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
	global $DB, $tablecategories;
	
	if( $cat_blog_ID == NULL )
	{
		if( empty($cat_parent_ID) ) die ( 'cat_create(-) missing parameters!' );
		$parent_cat = get_the_category_by_ID($cat_parent_ID);
		$cat_blog_ID = $parent_cat['cat_blog_ID']; 
	}

	$sql = "INSERT INTO $tablecategories( cat_parent_ID, cat_name, cat_blog_ID) 
					VALUES ( $cat_parent_ID, '$cat_name', $cat_blog_ID )";
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
	$cat_parent_ID = 0 )
{
	global $tablecategories, $query, $querycount;

	$query = "UPDATE $tablecategories SET cat_name='$cat_name'";
	if( $cat_parent_ID == 0 ) $cat_parent_ID = 'NULL';
	$query .= ", cat_parent_ID = $cat_parent_ID";
	$query .= " WHERE cat_ID=$cat_ID";

	return $DB->query( $query );
}


/*
 * cat_delete(-)
 *
 * Delete a category
 * This funtion has to handle all needed DB dependencies!
 *
 * fplanque: created
 * TODO: A LOT !!!!!
 */
function cat_delete( $cat_ID )
{
	global $DB, $tablecategories, $tableposts, $tablepostcats, $query, $cache_categories, $cache_postcats;

	// TODO: START TRANSACTION

	// check there are no subcats
	$sql = "SELECT COUNT(*) 
					FROM $tablecategories 
					WHERE cat_parent_ID = $cat_ID";
	$child_count = $DB->get_var( $sql );
	if( $child_count != 0 ) return T_("Cannot delete if there are sub-categories!");
	
	// find parent
	$sql = "SELECT cat_parent_ID, cat_blog_ID
					FROM $tablecategories 
					WHERE cat_ID = $cat_ID";
	if( ! ($row = $DB->get_row( $sql )) )
		return 1; // Success: category already deleted!!
	
	$remap_cat_ID = $row->cat_parent_ID;
	$cat_blog_ID = $row->cat_blog_ID;

	// Get the list of posts in this category
	$sql = "SELECT ID 
					FROM $tableposts 
					WHERE post_category = $cat_ID";
	$IDarray = $DB->get_col( $sql );

	if( ! $remap_cat_ID )
	{	// No parent, find another cat in same blog
		$sql = "SELECT cat_ID 
						FROM $tablecategories 
						WHERE cat_blog_ID = $cat_blog_ID 
							AND cat_ID != $cat_ID 
						ORDER BY cat_ID
						LIMIT 0, 1";
		$remap_cat_ID = $DB->get_var( $sql );	
		echo "remap to: $remap_cat_ID<br />";
		// May be NULL if this was the last cat (But there are no posts inside)

		if( ($remap_cat_ID == NULL) && (! empty($IDarray)) )
		{
			return T_("Cannot delete last category if there are posts inside!");
		}		
	}

	//  --------------- PROCEED WITH DELETING ------------

	// First delete assoc to this cat when it's an extra cat
	$sql = "DELETE FROM $tablepostcats 
						WHERE postcat_cat_ID = $cat_ID ";

	// TODO: check why this block is here:
	if( !empty($IDarray) )
	{	
		$IDlist = " AND postcat_post_ID NOT IN (".implode( ',', $IDarray ).") ";
	}
	else
		$IDList = '';
 
	$DB->query( $sql );


	// Now take care of the main cats (these need to be remapped, we cannot delete them!)
	if( $remap_cat_ID )
	{	// We are moving posts to parent or other category

		// remap the posts to new category:
		$sql = "UPDATE $tableposts 
							SET post_category = $remap_cat_ID 
							WHERE post_category = $cat_ID";
		$DB->query( $sql );

		// Before remapping the extracats we need to get rid of mappings that would become duplicates
		// We remove every mapping to the old cat where a mapping to the new cat already exists
		$sql = "SELECT DISTINCT postcat_post_ID 
						FROM $tablepostcats 
						WHERE postcat_cat_ID = $remap_cat_ID";
		$IDarray = $DB->get_col( $sql );

		if( !empty($IDarray) )
		{
			$IDlist = implode( ',', $IDarray );
	
			$sql = "DELETE FROM $tablepostcats 
							WHERE postcat_cat_ID = $cat_ID 
							AND postcat_post_ID IN ($IDlist)";
			$DB->query( $sql );
		}
	
	
		// remap the remaining extracats
		$sql = "UPDATE $tablepostcats 
						SET postcat_cat_ID = $remap_cat_ID 
						WHERE postcat_cat_ID = $cat_ID";
		$DB->query( $sql );
	}
	
	// do the actual deletion of the cat
	$sql = "DELETE FROM $tablecategories 
					WHERE cat_ID = $cat_ID";
	$DB->query( $sql );

	// TODO: END TRANSACTION
	
	// If we had a cache we'd better forget it!
	// TODO: reset other caches!
	unset( $GLOBALS['cache_categories'] );
	unset( $GLOBALS['cache_postcats'] );

	
	return 1; // success
}




/*
 * get_the_category_by_ID(-) 
 *
 * Get category name+blog_id for specified cat ID
 *
 * fplanque: reused "R. U. Serious" optimization here
 * fplanque: added blog ID stuff
 * TODO: move. dis is not a template tag
 */
function get_the_category_by_ID($cat_ID) 
{
	global $id,$tablecategories,$querycount,$cache_categories,$use_cache;
	if ((empty($cache_categories[$cat_ID])) OR (!$use_cache)) 
	{
		cat_load_cache();
	}
	if( !isset( $cache_categories[$cat_ID] ) ) die( sprintf( T_('Requested category %s does not exist!'),  $cat_ID ) );
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


/*
 * cat_load_cache()
 *
 * Load cache for category definitions.
 *
 * TODO: replace LEFT JOIN with UNION when switching to mySQL 4
 * This will prevent empty cats from displaying "(1)" as postcount.
 * TODO: get post counts out of here!
 */
function cat_load_cache()
{
	global $DB, $tablecategories, $tablepostcats, $tableposts, $cache_categories;
	global $show_statuses, $timestamp_min, $timestamp_max;
	global $time_difference;
	if( !isset($cache_categories)) 
	{
		// echo "loading CAT cache";
		$sql = "SELECT cat_ID, cat_parent_ID, cat_name, cat_blog_ID
						FROM $tablecategories 
						ORDER BY cat_name"; 
		$rows = $DB->get_results( $sql, ARRAY_A );
		if( count( $rows ) ) foreach( $rows as $myrow )
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
					{	// If the parent exists!
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
	
		// ------------------------------
		// Add post counts:
		// ------------------------------
		
		// CONSTRUCT THE WHERE CLAUSE:

		/*
		 * ----------------------------------------------------
		 *  Restrict to the statuses we want to show:
		 * ----------------------------------------------------
		 */
		$where = ' WHERE '.statuses_where_clause( $show_statuses );
		$where_link = ' AND ';

		// Restrict to timestamp limits:
		if( $timestamp_min == 'now' ) $timestamp_min = time();
		if( !empty($timestamp_min) ) 
		{	// Hide posts before
			$date_min = date('Y-m-d H:i:s', $timestamp_min + ($time_difference * 3600) );
			$where .= $where_link.' post_issue_date >= \''.$date_min.'\'';
			$where_link = ' AND ';
		}
		if( $timestamp_max == 'now' ) $timestamp_max = time();
		if( !empty($timestamp_max) ) 
		{	// Hide posts after
			$date_max = date('Y-m-d H:i:s', $timestamp_max + ($time_difference * 3600) );
			$where .= $where_link.' post_issue_date <= \''.$date_max.'\'';
			$where_link = ' AND ';
		}
	
		$sql = "SELECT postcat_cat_ID AS cat_ID, COUNT(*) AS cat_postcount 
						FROM $tablepostcats INNER JOIN $tableposts ON postcat_post_ID = ID
						$where
						GROUP BY cat_ID"; 
		$rows = $DB->get_results( $sql, ARRAY_A );
		if( count( $rows ) ) foreach( $rows as $myrow )
		{ 
			$cat_ID = $myrow['cat_ID'];
			if( !isset($cache_categories[$cat_ID]) )
				echo '<p>*** WARNING: There are ', $myrow['cat_postcount'], ' posts attached to inexistant category #', $cat_ID, '. You must fix the database! ***</p>';
			// echo 'Postcount for cat #', $cat_ID, ' is ', $myrow['cat_postcount'], '<br />';
			$cache_categories[$cat_ID]['cat_postcount'] = $myrow['cat_postcount'];
		} 

		// echo 'Number of cats=', count($cache_categories);

	}
}


/*
 * cat_load_postcats_cache(-)
 *
 * Load cache for category associations with current posts
 *
 * fplanque: created
 *
 * TODO: put this into main post query when mySQL 4.0 commonly available
 */
function cat_load_postcats_cache()
{
	global $DB, $tablepostcats, $cache_postcats, $postIDlist, $preview;

	if( isset($cache_postcats) )
	{	// already done!
		return;
	}
	
	if( $preview ) 
	{	// Preview mode
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
						FROM $tablepostcats 
						WHERE postcat_post_ID IN ($postIDlist) 
						ORDER BY postcat_post_ID, postcat_cat_ID"; 
		$rows = $DB->get_results( $sql, ARRAY_A );
		if( count( $rows ) ) foreach( $rows as $myrow )
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
	global $DB, $tablepostcats;
	
	//echo "looking up cats for post $post_ID ";

	$sql = "SELECT postcat_cat_ID 
					FROM $tablepostcats 
					WHERE postcat_post_ID = $post_ID 
					ORDER BY postcat_cat_ID"; 
	return $DB->get_col( $sql );
}


/*
 * cat_children(-)
 *
 * Taking a recursive walk in the category park...
 *
 * fplanque: created
 */
function cat_children( $ccats, 	// PHP requires this stupid cloning of the cache_categories array in order to be able to perform foreach on it
	$blog_ID, $parent_ID, 
	$callback_before_first, $callback_before_each, $callback_after_each, $callback_after_last, // Callback functions
	$level = 0 )	// Caller nesting level, just to keep track of how far we go :)
{
	// echo 'Number of cats=', count($ccats);
	if( ! empty( $ccats ) ) // this can happen if there are no cats at all!
	{	
		$child_count = 0;
		foreach( $ccats as $icat_ID => $i_cat )
		{
			if( $icat_ID && (($blog_ID == 0) || ($i_cat['cat_blog_ID'] == $blog_ID)) && ($i_cat['cat_parent_ID'] == $parent_ID) )
			{ // this cat is in the blog and is a child of the parent
				if( $child_count++ == 0 )
				{	// this is the first child
						$callback_before_first( $parent_ID, $level );
				}
				if( $callback_before_each( $icat_ID, $level ) ) continue;
				cat_children( $ccats, $blog_ID, $icat_ID, $callback_before_first, $callback_before_each,
											$callback_after_each, $callback_after_last, $level+1 );
				$callback_after_each( $icat_ID, $level );
			}
		}
		if( $child_count )
		{	// There have been children
			$callback_after_last( $parent_ID, $level );
		}
	}
}


/*
 * blog_has_cats(-)
 *
 * Does a given bog has categories?
 *
 * fplanque: created
 */
function blog_has_cats( $blog_ID )
{
	global $cache_categories;
	
	cat_load_cache();

	foreach( $cache_categories as $icat_ID => $i_cat )
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





/*
 * cat_query(-)
 *
 * Query for the cats 
 * 
 */
function cat_query( ) 
{
	// global $cache_categories; // $cache_blogs, 
	
	blog_load_cache();
	cat_load_cache();
}


/** 
 * Display currently filtered categories names
 *
 * This tag is out of the b2 loop.
 * It outputs the title of the category when you load the page with <code>?cat=</code>
 * When the weblog page is loaded without ?cat=, this tag doesn't display anything. 
 * Generally, you could use this as a page title.
 *
 * fplanque: multiple category support (so it's not really 'single' anymore!)
 *
 * {@internal single_cat_title(-) }}
 *
 * @param string Prefix to be displayed if something is going to be displayed
 * @param mixed Output format, see {@link format_to_output()} or false to 
 *								return value instead of displaying it
 */
function single_cat_title( $prefix = '#', $display = 'htmlbody' ) 
{
	if( $prefix == '#' ) $prefix = ' '.T_('Category').': ';

	global $cat, $cat_array;
	if( !empty($cat_array) ) 
	{	// We have requested specific categories...
		$cat_names = array();
		foreach( $cat_array as $cat_ID )
		{
			$my_cat = get_the_category_by_ID($cat_ID);
			$cat_names[] = $my_cat['cat_name'];
		}
		$cat_names_string = implode( ", ", $cat_names );
		if( !empty( $cat_names_string ) )
		{
			if( strstr($cat,'-') ) 
			{
				$cat_names_string = 'All but '.$cat_names_string;
			}
			if ($display)
				echo format_to_output( $prefix.$cat_names_string, $display );
			else
				return $cat_names_string;
		}
	}
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

	if( $link_title == '#' ) 
	{	/* TRANS: When the categories for a specific post are displayed, the user can click
				on these cats to browse them, this is the default href title displayed there */
		$link_title = T_('Browse category');
	}

	$main_cat_ID = $postdata['Category'];
	// echo "main cat ID: $main_cat_ID<br />";
	cat_load_postcats_cache();
	$categoryIDs = $cache_postcats[$id];
	
	if( !isset($categoryIDs) )
	{	// Can happen in preview mode
		return;
	}

	$categoryNames = array();
	foreach( $categoryIDs as $cat_ID )
	{
		$cat = get_the_category_by_ID($cat_ID);
		$cat_name = format_to_output( $cat["cat_name"], $format_each );
		
		if( $link_title && !$preview)
		{	// we want to display links
			$curr_blogparams = get_blogparams_by_ID( $cat['cat_blog_ID'] );
			$cat_name = '<a href="'.get_bloginfo('blogurl', $curr_blogparams).'?cat='.$cat_ID.'" title="'.$link_title.'">'.$cat_name.'</a>';
		}

		if( $cat_ID == $main_cat_ID )
		{	// We are displaying the main cat!
			if( $before_main == 'hide' )
			{	// ignore main cat !!!
				continue;
			}
			$cat_name = $before_main.$cat_name.$after_main;
		}
		elseif( $cat['cat_blog_ID'] == $blog )
		{ // We are displaying another cat in the same blog
			if( $before_other == 'hide' )
			{	// ignore main cat !!!
				continue;
			}
			$cat_name = $before_other.$cat_name.$after_other;
		}
		else
		{	// We are displaying an external cat (in another blog)
			if( $before_external == 'hide' )
			{	// ignore main cat !!!
				continue;
			}
			$cat_name = $before_external.$cat_name.$after_external;
		}

		$categoryNames[] = $cat_name;
	}
	echo format_to_output( implode( $separator, $categoryNames ), $format_list );
}



/*
 * the_category_ID(-)
 *
 * echoes the main category ID for current post
 * The ID (number) of the category the post belongs to. 
 * This is static data that you can use, for example to associate a category to an image, or a css style.
 */
function the_category_ID() 
{
	global $id,$postdata;	
	echo $postdata['Category'];
}

/*
 * the_categories_IDs(-)
 *
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
	{	// Can happen in preview mode
		return;
	}
	
	echo implode( ',', $categoryIDs );
	
}


/* 
 * the_category_head(-)
 */
function the_category_head($before='',$after='') 
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


?>