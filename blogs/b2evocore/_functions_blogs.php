<?php
/**
 * Blog handling functions
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_class_blogcache.php';

/**
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
	$blog_stub,
	$blog_staticfilename = '',
	$blog_tagline = '',
	$blog_description = '',
	$blog_longdesc = '',
	$blog_locale = '#',
	$blog_notes = '',
	$blog_keywords = '',
	$blog_links_blog_ID = 0,
	$blog_UID = '',
	$blog_allowtrackbacks = 1,
	$blog_allowpingbacks = 1,
	$blog_pingb2evonet = 0,
	$blog_pingtechnorati = 0,
	$blog_pingweblogs = 1,
	$blog_pingblodotgs = 0,
	$blog_disp_bloglist	= 1,
	$blog_in_bloglist = 1 )
{
	global $DB, $tableblogs, $query, $querycount, $default_locale;

	if( $blog_locale == '#' ) $blog_locale = $default_locale;

	$query = "INSERT INTO $tableblogs( blog_name, blog_shortname, blog_siteurl,
						blog_stub, blog_staticfilename,
						blog_tagline, blog_description, blog_longdesc, blog_locale, blog_notes, blog_keywords,
						blog_UID, blog_allowtrackbacks, blog_allowpingbacks, blog_pingb2evonet,
						blog_pingtechnorati, blog_pingweblogs, blog_pingblodotgs, blog_disp_bloglist,
						blog_in_bloglist, blog_links_blog_ID )
	VALUES ( ";
	$query .= "'".$DB->escape($blog_name)."', ";
	$query .= "'".$DB->escape($blog_shortname)."', ";
	$query .= "'".$DB->escape($blog_siteurl)."', ";
	$query .= "'".$DB->escape($blog_stub)."', ";
	$query .= "'".$DB->escape($blog_staticfilename)."', ";
	$query .= "'".$DB->escape($blog_tagline)."', ";
	$query .= "'".$DB->escape($blog_description)."', ";
	$query .= "'".$DB->escape($blog_longdesc)."', ";
	$query .= "'".$DB->escape($blog_locale)."', ";
	$query .= "'".$DB->escape($blog_notes)."', ";
	$query .= "'".$DB->escape($blog_keywords)."', ";
	$query .= "'".$DB->escape($blog_UID)."',
	$blog_allowtrackbacks, $blog_allowpingbacks,
	$blog_pingb2evonet, $blog_pingtechnorati, $blog_pingweblogs, $blog_pingblodotgs,
	$blog_disp_bloglist, $blog_in_bloglist, $blog_links_blog_ID )	";
	if( ! ($DB->query( $query )) )
		return 0;

	return $DB->insert_id;  // blog ID
}




/**
 * Update the user permissions for edited blog
 *
 * {@internal blog_update_user_perms(-) }}
 *
 * @param int Blog ID
 */
function blog_update_user_perms( $blog )
{
	global $DB, $tableblogusers, $tableusers;

	// Delete old perms for thos blog:
	$DB->query( "DELETE FROM $tableblogusers
								WHERE bloguser_blog_ID = $blog" );

	// Now we need a full user list:
	$user_IDs = $DB->get_col( "SELECT ID FROM $tableusers" );

	$inserted_values = array();
	if( count( $user_IDs ) ) foreach( $user_IDs as $loop_user_ID )
	{	// Check new permissions for each user:
		// echo "getting perms for user : $loop_user_ID <br />";

		$perm_post = array();

		$ismember = param( 'blog_ismember_'.$loop_user_ID, 'integer', 0 );

		$perm_published = param( 'blog_perm_published_'.$loop_user_ID, 'string', '' );
		if( !empty($perm_published) ) $perm_post[] = 'published';

		$perm_protected = param( 'blog_perm_protected_'.$loop_user_ID, 'string', '' );
		if( !empty($perm_protected) ) $perm_post[] = 'protected';

		$perm_private = param( 'blog_perm_private_'.$loop_user_ID, 'string', '' );
		if( !empty($perm_private) ) $perm_post[] = 'private';

		$perm_draft = param( 'blog_perm_draft_'.$loop_user_ID, 'string', '' );
		if( !empty($perm_draft) ) $perm_post[] = 'draft';

		$perm_deprecated = param( 'blog_perm_deprecated_'.$loop_user_ID, 'string', '' );
		if( !empty($perm_deprecated) ) $perm_post[] = 'deprecated';

		$perm_delpost = param( 'blog_perm_delpost_'.$loop_user_ID, 'integer', 0 );
		$perm_comments = param( 'blog_perm_comments_'.$loop_user_ID, 'integer', 0 );
		$perm_cats = param( 'blog_perm_cats_'.$loop_user_ID, 'integer', 0 );
		$perm_properties = param( 'blog_perm_properties_'.$loop_user_ID, 'integer', 0 );

		// Update those permissions in DB:

		if( $ismember || count($perm_post) || $perm_delpost || $perm_comments || $perm_cats || $perm_properties )
		{	// There are some permissions for this user:
			$ismember = 1;	// Must have this permission

			// insert new perms:
			$inserted_values[] = " ( $blog, $loop_user_ID, $ismember, '".implode(',',$perm_post)."',
																$perm_delpost, $perm_comments, $perm_cats, $perm_properties )";
		}
	}

	// Proceed insertions:
	if( count( $inserted_values ) )
	{
		$DB->query( "INSERT INTO $tableblogusers( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember,
											bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments,
											bloguser_perm_cats, bloguser_perm_properties )
									VALUES ".implode( ',', $inserted_values ) );
	}
}

/**
 * get_bloginfo(-)
 *
 * @deprecated deprecated by Blog::get() This is now a dirty stub
 */
function get_bloginfo( $show = '', $this_blogparams = '' )
{
	global $Blog, $blog, $BlogCache;

	if( empty( $this_blogparams ) )
	{ // We want the global blog on the page
		if( isset( $Blog ) )
			$current_Blog = & $Blog;
		else
			$current_Blog = $BlogCache->get_by_ID($blog);
	}
	else
	{
		$current_Blog = $BlogCache->get_by_ID($this_blogparams->blog_ID);
	}

	return $current_Blog->get( $show );
}



/**
 * Get blog params for specified ID
 *
 * {@internal get_blogparams_by_ID(-)}}
 *
 * @todo on a heavy multiblog system, cache them one by one...
 * @deprecated deprecated by {@link Blog_get_by_ID()}
 *
 * @param integer Blog ID
 */
function get_blogparams_by_ID( $blog_ID )
{
	global $tableblogs, $cache_blogs, $use_cache, $querycount;

	if( $blog_ID < 1 ) die( 'No blog is selected!' );

	if( (empty($cache_blogs[$blog_ID])) OR (!$use_cache) )
	{
		blog_load_cache();
	}
	if( !isset( $cache_blogs[$blog_ID] ) ) die( T_('Requested blog does not exist!') );
	return $cache_blogs[ $blog_ID ];
}

/**
 * Get Blog for specified ID
 *
 * {@internal Blog_get_by_ID(-)}}
 *
 * @todo on a heavy multiblog system, cache them one by one...
 *
 * @param integer ID of Blog we want
 */
function Blog_get_by_ID( $blog_ID )
{
	global $tableblogs, $cache_blogs, $use_cache, $querycount;

	if( $blog_ID < 1 ) die( 'No blog is selected!' );

	if ((empty($cache_blogs[$blog_ID])) OR (!$use_cache))
	{
		blog_load_cache();
	}
	if( !isset( $cache_blogs[$blog_ID] ) ) die( T_('Requested blog does not exist!') );

	return new Blog( $cache_blogs[$blog_ID] ); // COPY !
}


/*
 * blog_load_cache(-)
 */
function blog_load_cache()
{
	global $DB, $tableblogs, $cache_blogs, $use_cache;
	if( empty($cache_blogs) || !$use_cache )
	{
		$cache_blogs = array();
		$query = "SELECT * FROM $tableblogs ORDER BY blog_ID";
		$result = $DB->get_results( $query );
		if( count( $result ) ) foreach( $result as $this_blog )
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

/**
 * bloginfo(-)
 *
 * Template tag
 *
 * @deprecated deprecated by {@link Blog:disp()}
 */
function bloginfo( $show='', $format = 'raw', $display = true, $this_blogparams = '' )
{
	$content = get_bloginfo( $show, $this_blogparams );
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
function blog_list_start( $need = '' )
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
