<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */

/*
 * blog_create(-)
 *
 * Create a new a blog
 * This funtion has to handle all needed DB dependencies!
 *
 * fplanque: created
 */
function blog_create( 
	$blog_name,
	$blog_shortname,
	$blog_siteurl,
	$blog_filename,
	$blog_stub,
	$blog_staticfilename = '',
	$blog_tagline = '',
	$blog_description = '',
	$blog_longdesc = '',
	$blog_lang = 'en',
	$blog_roll = '',
	$blog_keywords = '',
	$blog_UID = '' )
{
	global $tableblogs, $query, $querycount;

	$query = "INSERT INTO $tableblogs( blog_name, blog_shortname, blog_siteurl, blog_filename, ".
						"blog_stub, blog_staticfilename, ".
						"blog_tagline, blog_description, blog_longdesc, blog_lang, blog_roll, blog_keywords,".
						"blog_UID ) VALUES ( ";
	$query .= "'".addslashes($blog_name)."', ";
	$query .= "'".addslashes($blog_shortname)."', ";
	$query .= "'$blog_siteurl', ";
	$query .= "'$blog_filename', ";
	$query .= "'$blog_stub', ";
	$query .= "'$blog_staticfilename', ";
	$query .= "'".addslashes($blog_tagline)."', ";
	$query .= "'".addslashes($blog_description)."', ";
	$query .= "'".addslashes($blog_longdesc)."', ";
	$query .= "'$blog_lang', ";
	$query .= "'".addslashes($blog_roll)."', ";
	$query .= "'".addslashes($blog_keywords)."', ";
	$query .= "'$blog_UID' )";
	$querycount++;
	$result = mysql_query($query);

	if( !$result ) return 0;

	return mysql_insert_id();  // blog ID
}


/*
 * blog_update(-)
 *
 * Update a blog
 * This funtion has to handle all needed DB dependencies!
 *
 * fplanque: created
 */
function blog_update( 
	$blog_ID,
	$blog_name,
	$blog_shortname,
	$blog_siteurl,
	$blog_filename,
	$blog_stub,
	$blog_staticfilename = '',
	$blog_tagline = '',
	$blog_description = '',
	$blog_longdesc = '',
	$blog_lang = 'en',
	$blog_roll = '',
	$blog_keywords = '',
	$blog_UID = '' )
{
	global $tableblogs, $query, $querycount;

	$query = "UPDATE $tableblogs SET ";
	$query .= " blog_name = '".addslashes($blog_name)."', ";
	$query .= " blog_shortname = '".addslashes($blog_shortname)."', ";
	$query .= " blog_siteurl = '$blog_siteurl', ";
	$query .= " blog_filename = '$blog_filename', ";
	$query .= " blog_staticfilename = '$blog_staticfilename', ";
	$query .= " blog_stub = '$blog_stub', ";
	$query .= " blog_tagline = '".addslashes($blog_tagline)."', ";
	$query .= " blog_description = '".addslashes($blog_description)."', ";
	$query .= " blog_longdesc = '".addslashes($blog_longdesc)."', ";
	$query .= " blog_lang = '$blog_lang', ";
	$query .= " blog_roll = '".addslashes($blog_roll)."', ";
	$query .= " blog_keywords = '".addslashes($blog_keywords)."', ";
	$query .= " blog_UID = '$blog_UID' ";
	$query .= "WHERE blog_ID= $blog_ID";
	$querycount++;
	$result = mysql_query($query);
	if( !$result ) return 0;

	return 1;	// success
}


/*
 * get_blogparams(-)
 *
 * Get current blog info
 * fplanque: added
 */
function get_blogparams()
{	
	global $tableblogs, $blog, $blogparams, $querycount, $blogname, $blogdescription, $siteurl, $blogfilename, $blogstaticfilename;
	if( !isset($blog) ) die("No blog set!");
	$blogparams = get_blogparams_by_ID( $blog );
	// override those deprecated default settings:
	$blogname = $blogparams->blog_name;												// deprecated
	$blogdescription = $blogparams->blog_description;					// deprecated
	$siteurl = $blogparams->blog_siteurl;											// deprecated
	$blogfilename = $blogparams->blog_filename;								// deprecated
	$blogstaticfilename = $blogparams->blog_staticfilename;		// deprecated
}

/*
 * get_bloginfo(-)
 */
function get_bloginfo( $show='', $this_blogparams = '' )
{
	global $blog, $xmlsrvurl, $admin_email;

	if( empty( $this_blogparams ) )
	{	// We want the global blog on the page
		 $this_blogparams = get_blogparams_by_ID( $blog );
	}

	switch($show) 
	{
		case "ID":
			$output = $this_blogparams->blog_ID;
			break;
		case "siteurl":
			$output = $this_blogparams->blog_siteurl;
			break;
		case "filename":
			$output = $this_blogparams->blog_filename;
			break;
		case "staticfilename":
			$output = $this_blogparams->blog_staticfilename;
			break;
		case "stub":
			$output = $this_blogparams->blog_stub;
			break;
		case "blogurl":
		case 'link':			// RSS wording
		case "url":
			$output = $this_blogparams->blog_siteurl."/".$this_blogparams->blog_stub;
			break;
		case "dynurl":
			$output = $this_blogparams->blog_siteurl."/".$this_blogparams->blog_filename;
			break;
		case "staticurl":
			$output = $this_blogparams->blog_siteurl."/".$this_blogparams->blog_staticfilename;
			break;
		case "blogstatsurl":
			$output = $this_blogparams->blog_siteurl."/".$this_blogparams->blog_stub.
								'?disp=stats';
			break;
		case "lastcommentsurl":
			$output = $this_blogparams->blog_siteurl."/".$this_blogparams->blog_stub.
								'?disp=comments';
			break;
		case "tagline":
			$output = $this_blogparams->blog_tagline;
			break;
		case "description":			// RSS wording
		case "shortdesc":
			$output = $this_blogparams->blog_description;
			break;
		case "keywords":
			$output = $this_blogparams->blog_keywords;
			break;
		case "longdesc":
			$output = $this_blogparams->blog_longdesc;
			break;
		case "blogroll":
			$output = $this_blogparams->blog_roll;
			break;
		case "lang":
			$output = $this_blogparams->blog_lang;
			break;
		case "rdf_url":
			$output = $xmlsrvurl.'/rdf.php?blog='.$this_blogparams->blog_ID;
			break;
		case "rss_url":
			$output = $xmlsrvurl.'/rss.php?blog='.$this_blogparams->blog_ID;
			break;
		case "rss2_url":
			$output = $xmlsrvurl.'/rss2.php?blog='.$this_blogparams->blog_ID;
			break;
		case "pingback_url":
			$output = $xmlsrvurl.'/xmlrpc.php';
			break;
		case "admin_email":
			$output = $admin_email;
			break;
		case "shortname":
			$output =  $this_blogparams->blog_shortname;
			break;
		case "default_skin":
			$output =  $this_blogparams->blog_default_skin;
			break;
		case "name":
		default:
			$output =  $this_blogparams->blog_name;
			break;
	}
	return trim($output);
}



/*
 * get_blogparams_by_ID(-) 
 *
 * Get blog params for specified ID
 *
 * fplanque: created
 * TODO: on a heaby multiblog system, cache them one by one...
 */
function get_blogparams_by_ID($blog_ID) 
{
	global $tableblogs, $cache_blogs, $use_cache, $querycount;
	if ((empty($cache_blogs[$blog_ID])) OR (!$use_cache)) 
	{
		blog_load_cache();
	}
	if( !isset( $cache_blogs[$blog_ID] ) ) die( T_('Requested blog does not exist!') );
	return $cache_blogs[$blog_ID];
}


/*
 * blog_load_cache(-)
 */
function blog_load_cache()
{
	global $tableblogs, $cache_blogs, $use_cache, $querycount;
	if (empty($cache_blogs)) 
	{
		$query = "SELECT * FROM $tableblogs ORDER BY blog_ID";
		$result = mysql_query($query) or mysql_oops( $query ); 
		$querycount++; 
		while( $this_blog = mysql_fetch_object($result) ) 
		{ 
			$cache_blogs[$this_blog->blog_ID] = $this_blog;
			//echo 'just cached:'.$cache_blogs[$this_blog->blog_ID]->blog_name.'('.$this_blog->blog_ID.')<br />';
		} 
	}
}



/***** 
 * About-the-blog tags 
 * Note: these tags go anywhere in the template 
 *****/

/*
 * bloginfo(-)
 */
function bloginfo( $show='', $format = 'raw', $display = true ) 
{
	$content = get_bloginfo($show);
	$content = format_to_output( $content, $format );
	if( $display )
		echo $content;
	else
		return $content;
}



/*
 * blog_list_start(-)
 * 
 * Start blog iterator
 *
 * fplanque: created
 */
function blog_list_start( $need='' )
{
	global $cache_blogs, $curr_blogparams, $curr_blog_ID;
	
	blog_load_cache();
	// echo "nb blogs=", count($cache_blogs );

	$curr_blogparams = reset( $cache_blogs );
	if( $curr_blogparams === false )
		return false;	// No blog!

	if( (!empty($need)) && (!get_bloginfo($need, $curr_blogparams )) )
	{	// We need the blog to have a specific criteria that is not met, search on...
		return blog_list_next();		// This can be recursive
	}

	$curr_blog_ID = $curr_blogparams->blog_ID;
	//echo "blogID=", $curr_blog_ID;
	return $curr_blog_ID;
}


/*
 * blog_list_next(-)
 * 
 * Next blog iteration
 *
 * fplanque: created
 */
function blog_list_next( $need='' )
{
	global $cache_blogs, $curr_blogparams, $curr_blog_ID;
	
	$curr_blogparams = next( $cache_blogs );
	if( $curr_blogparams === false )
		return false;	// No more blog!

	// echo 'need: ', $need, ' info:',get_bloginfo($need, $curr_blogparams );

	if( (!empty($need)) && (!get_bloginfo($need, $curr_blogparams )) )
	{	// We need the blog to have a specific criteria that is not met, search on...
		return blog_list_next( $need );		// This can be recursive
	}

	$curr_blog_ID = $curr_blogparams->blog_ID;
	// echo "blogID=", $curr_blog_ID;
	return $curr_blog_ID;
}


/*
 * blog_list_iteminfo(-)
 * 
 * Display info about item
 *
 * fplanque: created
 */
function blog_list_iteminfo( $what, $show = 'raw' )
{
	global $curr_blogparams;

	$raw_info = get_bloginfo( $what, $curr_blogparams );
	
	if( $show )
	{
		echo format_to_output( $raw_info, $show );
	}
	
	return $raw_info;
}

?>
