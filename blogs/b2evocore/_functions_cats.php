<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
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
	global $tablecategories, $query, $querycount;
	
	if( $cat_blog_ID == NULL )
	{
		if( empty($cat_parent_ID) ) die ( 'cat_create(-) missing parameters!' );
		$parent_cat = get_the_category_by_ID($cat_parent_ID);
		$cat_blog_ID = $parent_cat['cat_blog_ID']; 
	}

	$query="INSERT INTO $tablecategories( cat_parent_ID, cat_name, cat_blog_ID) VALUES ( $cat_parent_ID, '$cat_name', $cat_blog_ID )";
	$querycount++;
	$result = mysql_query($query);
	if( !$result ) return 0;
	$cat_ID = mysql_insert_id();

	return $cat_ID;
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
	$cat_parent_ID = NULL )
{
	global $tablecategories, $query, $querycount;

	$query = "UPDATE $tablecategories SET cat_name='$cat_name'";
	if( ! empty( $cat_parent_ID ) ) $query .= ", cat_parent_ID = $cat_parent_ID";
	$query .= " WHERE cat_ID=$cat_ID";

	$querycount++;
	$result = mysql_query($query);
	if( !$result ) return 0;

	return 1;	// success
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
	global $tablecategories, $tableposts, $tablepostcats, $query, $querycount, $cache_categories, $cache_postcats;

	// TODO: START TRANSACTION

	// TODO: check there are no subcats
	$query="SELECT COUNT(*) AS child_count FROM $tablecategories WHERE cat_parent_ID = $cat_ID";

	$querycount++;
	$result = mysql_query($query);
	if( !$result ) return 0;
	
	$row = mysql_fetch_object( $result );
	
	$child_count = $row->child_count;
	if( $child_count != 0 ) return T_("Cannot delete if there are sub-categories!");
	
	// TODO: find parent
	$query="SELECT cat_parent_ID, cat_blog_ID FROM $tablecategories WHERE cat_ID = $cat_ID";

	$querycount++;
	$result = mysql_query($query);
	if( !$result ) return 0;
	
	$row = mysql_fetch_object( $result );
	
	if( empty( $row ) ) return 1; // Success: category already deleted!!
	
	$remap_cat_ID = $row->cat_parent_ID;
	$cat_blog_ID = $row->cat_blog_ID;


	// Get the list of posts in this category
	$query = "SELECT ID FROM $tableposts WHERE post_category = $cat_ID";

	$querycount++;
	$result = mysql_query($query);
	if( !$result ) return 0;

	$IDarray = array();
	while( $row = mysql_fetch_array($result) ) 
	{
		$IDarray[] = $row["ID"];
	}


	if( ! $remap_cat_ID )
	{	// No parent, find another cat in same blog
		$query="SELECT cat_ID FROM $tablecategories WHERE cat_blog_ID = $cat_blog_ID AND cat_ID != $cat_ID ORDER BY cat_ID";
	
		$querycount++;
		$result = mysql_query($query);
		if( !$result ) return 0;
		
		$row = mysql_fetch_object( $result );
		if( !empty( $row ) )
		{	// We found another cat, ok:
			$remap_cat_ID = $row->cat_ID;
		}
		elseif( empty($IDarray) )
		{ // this was the last cat
			// But there are no posts inside
			$remap_cat_ID = NULL;	// Okay we are deleting without remap!
		}
		else
		{
			return T_("Cannot delete last category if there are posts inside!");
		}		
		
	}

	//  --------------- PROCEED WITH DELETING ------------

	// First delete assoc to this cat when it's an extra cat
	if( !empty($IDarray) )
	{	
		$IDlist = " AND postcat_post_ID NOT IN (".implode( ',', $IDarray ).") ";
	}
	else
		$IDList = '';

	// delete when not main cat
	$query = "DELETE FROM $tablepostcats WHERE postcat_cat_ID = $cat_ID ".$IDlist;

	$querycount++;
	$result = mysql_query($query);
	if( !$result ) return 0;



	// Now take care of the main cats (these need to be remapped, we cannot delete them!)
	if( $remap_cat_ID )
	{	// We are moving posts to parent or other category

		// remap the posts to new category:
		$query = "UPDATE $tableposts SET post_category = $remap_cat_ID WHERE post_category = $cat_ID";

		$querycount++;
		$result = mysql_query($query);
		if( !$result ) return 0;
	
	
		// Before remapping the extracats we need to get rid of mappings that would become duplicates
		// We remove every mapping to the old cat where a mapping to the new cat already exists
		$query = "SELECT DISTINCT postcat_post_ID FROM $tablepostcats WHERE postcat_cat_ID = $remap_cat_ID";
	
		$querycount++;
		$result = mysql_query($query);
		if( !$result ) return 0;
	
		$IDarray = array();
		while( $row = mysql_fetch_array($result) ) 
		{
			$IDarray[] = $row["postcat_post_ID"];
		}
		if( !empty($IDarray) )
		{
			$IDlist = implode( ',', $IDarray );
	
			$query = "DELETE FROM $tablepostcats WHERE postcat_cat_ID = $cat_ID AND postcat_post_ID IN ($IDlist)";
		
			$querycount++;
			$result = mysql_query($query);
			if( !$result ) return 0;
		}
	
	
		// remap the remaining extracats
		$query = "UPDATE $tablepostcats SET postcat_cat_ID = $remap_cat_ID WHERE postcat_cat_ID = $cat_ID";
	
		$querycount++;
		$result = mysql_query($query);
		if( !$result ) return 0;

	}
	
	// do the actual deletion of the cat
	$query="DELETE FROM $tablecategories WHERE cat_ID = $cat_ID";

	$querycount++;
	$result = mysql_query($query);
	if( !$result ) return 0;

	// TODO: END TRANSACTION
	
	// If we had a cache we'd better forget it!
	// TODO: reset other caches!
	unset( $cache_categories );
	unset( $cache_postcats );
	
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
	global $tablecategories, $tablepostcats, $tableposts, $querycount, $cache_categories;
	global $show_statuses, $timestamp_min, $timestamp_max;
	global $time_difference;
	if( !isset($cache_categories)) 
	{
		$query="SELECT cat_ID, cat_parent_ID, cat_name, cat_blog_ID ".
				"FROM $tablecategories ".
				"ORDER BY cat_name"; 
		$result=mysql_query($query) or mysql_oops( $query ); 
		$querycount++; 
		while( $myrow = mysql_fetch_array($result) ) 
		{ 
			$this_cat['cat_name'] = stripslashes($myrow['cat_name']);
			$this_cat['cat_blog_ID'] = $myrow['cat_blog_ID'];
			$this_cat['cat_parent_ID'] = $myrow['cat_parent_ID'];
			$this_cat['cat_postcount'] = 0;					// Will be increased later
			$this_cat['cat_children'] = array();
			$cache_categories[$myrow['cat_ID']] = $this_cat;
			// echo 'just cached:',$myrow['cat_ID'],':',$cache_categories[$myrow['cat_ID']]['cat_name'].'<br />';
		} 

		// Reveal children:
		if( ! empty( $cache_categories ) )
		{
			foreach( $cache_categories as $icat_ID => $i_cat )
			{
				if( $icat_ID )
				{
					$cache_categories[$i_cat['cat_parent_ID']]['cat_children'][] = $icat_ID;
				}
			}
		}

		
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
			$where .= $where_link.' post_date >= \''.$date_min.'\'';
			$where_link = ' AND ';
		}
		if( $timestamp_max == 'now' ) $timestamp_max = time();
		if( !empty($timestamp_max) ) 
		{	// Hide posts after
			$date_max = date('Y-m-d H:i:s', $timestamp_max + ($time_difference * 3600) );
			$where .= $where_link.' post_date <= \''.$date_max.'\'';
			$where_link = ' AND ';
		}
	
		$query="SELECT postcat_cat_ID AS cat_ID, COUNT(*) AS cat_postcount ".
				"FROM $tablepostcats INNER JOIN $tableposts ON postcat_post_ID = ID ".
				$where.
				" GROUP BY cat_ID"; 
		$result=mysql_query($query) or mysql_oops( $query ); 
		$querycount++; 
		while ( $myrow = mysql_fetch_array($result)) 
		{ 
			$cache_categories[$myrow['cat_ID']]['cat_postcount'] = $myrow['cat_postcount'];
		} 

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
	global $tablepostcats, $querycount, $cache_postcats, $postIDlist, $preview;

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
		$query = "SELECT postcat_post_ID, postcat_cat_ID FROM $tablepostcats WHERE postcat_post_ID IN ($postIDlist) ORDER BY postcat_post_ID, postcat_cat_ID"; 
		$querycount++; 
		$result = mysql_query($query) or mysql_oops( $query ); 
		while( $myrow = mysql_fetch_array($result) ) 
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
	global $tablepostcats, $querycount ;
	
	//echo "looking up cats for post $post_ID ";

	$query = "SELECT postcat_cat_ID FROM $tablepostcats WHERE postcat_post_ID = $post_ID ORDER BY postcat_cat_ID"; 
	$querycount++; 
	$result = mysql_query($query) or mysql_oops( $query ); 
	$catIDarray = array();
	while( $myrow = mysql_fetch_array($result) ) 
	{ 
		//echo "cat: ". $myrow['postcat_cat_ID'];
		$catIDarray[] = $myrow['postcat_cat_ID'];
	} 
	return $catIDarray;
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


/* 
 * single_cat_title(-)
 *
 * Display currently filtered cat name
 * This tag is out of the b2 loop.
 * It outputs the title of the category when you load the page with ?cat= 
 * (see 'Usage' section for explanation).
 *  When the weblog page is loaded without ?cat=, this tag doesn't display anything. 
 * Generally, you could use it like this:
 *  <title><?php bloginfo('name') ?><?php single_cat_title() ?></title>
 *
 * fplanque: multiple category support (so it's not really 'single' anymore!)
 * 0.8.3: changed defaults
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



/*
 * the_category(-) 
 *
 * echoes the main category name
 * the name of the main category the post belongs to. 
 * you can as an admin add categories, and rename them if needed. 
 * default category is 'General', you can rename it too.
 */
function the_category( $format = 'htmlbody' ) 
{
	$category = get_the_category();
	echo format_to_output($category, $format);
}


/*
 * the_categories(-) 
 *
 * lists all the category names
 *
 * fplanque: created
 * fplanque: 0.8.3: changed defaults
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
			$cat_name = '<a href="'.$curr_blogparams->blog_siteurl.'/'.$curr_blogparams->blog_stub.'?cat='.$cat_ID.'" title="'.$link_title.'">'.$cat_name.'</a>';
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

/*
 * dropdown_cats(-)
 *
 * this is a special tag, meant to be used in the template, but outside of the b2 loop.
 * it will display a list of <option name="x">category-name</option>, 
 * where x is the number of the category and category-name is the name of it.
 *
 * TODO: this needs to be broken up
 */
function dropdown_cats($optionall = 1, $all = 'All') 
{
	global $cat, $tablecategories, $querycount;
	$query="SELECT * FROM $tablecategories";
	$result=mysql_query($query);
	$querycount++;
	echo '<select name="cat" class="postform">';
	if( $optionall ) 
	{
		echo "\t<option value=\"all\">$all</option>\n";
	}
	while($row = mysql_fetch_object($result)) 
	{
		echo "\t<option value=\"".$row->cat_ID."\"";
		if ($row->cat_ID == $cat)
			echo ' selected="selected"';
		echo '>'.stripslashes($row->cat_name)."</option>\n";
	}
	echo "</select>\n";
}


/* 
 * list_cats(-)
 *
 * out of the b2 loop
 * category list
 * this is a special tag, meant to be used in the template, but outside of the b2 loop.
 * it will display a list of the categories, with links to them. 
 *
 * fplanque: maybe this should go out and work a little like b2archives.php?
 */
function list_cats($optionall = 1, 	// Display a link to All cats
	$all = 'All', $sort_column = 'ID', $sort_order = 'asc', $file = 'blah') 
{
	global $tablecategories,$querycount,$blogfilename, $blog; 

	$file = ($file == 'blah') ? $blogfilename : $file;
	$sort_column = 'cat_'.$sort_column;

	if( $blog > 1 )	
	{	// We need to restrict to valid catgories:
		$where = "WHERE cat_blog_ID = $blog";
	}
	else 
	{
		$where = ""; 
	} 

	$query="SELECT * FROM $tablecategories $where ORDER BY $sort_column $sort_order";
	$querycount++;
	$result=mysql_query($query) or mysql_oops($query);

	if (intval($optionall) == 1) 
	{
		echo "\t<li><a href=\"".$file.'?cat=all">'.$all."</a></li>\n";
	}

	while($row = mysql_fetch_object($result)) 
	{
		$cat_name = $row->cat_name;
		echo "\t<li><a href=\"".$file.'?cat='.$row->cat_ID.'">';
		echo format_to_output($cat_name,'htmlbody');
		echo "</a></li>\n";
	}
}



/*
 * dropdown_categories(-)
 */
function dropdown_categories($blog_ID=1, $restrict=false) 
{
	global $postdata,$tablecategories,$mode,$querycount;
	global $cat; // fplanque: added
	$query="SELECT * FROM $tablecategories";
	if( $blog_ID > 1  || $restrict )
	{ // fplanque added 
		$query .= " WHERE cat_blog_ID = $blog_ID";
	}
	$query .= " ORDER BY cat_name";	// fplanque added
	$result=mysql_query($query) or mysql_oops( $query );
	$querycount++;
	$width = ($mode=="sidebar") ? "100%" : "170px";
	echo '<select name="post_category" style="width:'.$width.';" tabindex="2" id="category">';
	while($row = mysql_fetch_object($result)) 
	{
		echo "<option value=\"".$row->cat_ID."\"";
		// fplanque: changed if ($row->cat_ID == $postdata["Category"])
		if (($row->cat_ID == $cat) or ($row->cat_ID == $postdata["Category"]))
			echo ' selected="selected"';
		echo ">".$row->cat_name."</option>";
	}
	echo "</select>";
}


?>