<?php
/**
 * This file implements Blog handling functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author gorgeb: Bertrand GORGE / EPISTEMA
 * @author sakichan: Nobuo SAKIYAMA.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_blogcache.class.php';

/**
 * blog_create(-)
 *
 * Create a new a blog
 * This funtion has to handle all needed DB dependencies!
 *
 * @todo move this to Blog object
 */
function blog_create(
	$blog_name,
	$blog_shortname,
	$blog_siteurl,
	$blog_stub,									// This will temporarily be assigned to both STUB and URLNAME
	$blog_staticfilename = '',
	$blog_tagline = '',
	$blog_description = '',
	$blog_longdesc = '',
	$blog_locale = '#',
	$blog_notes = '',
	$blog_keywords = '',
	$blog_links_blog_ID = 0,
	$blog_UID = '',
	$blog_allowcomments = 'post_by_post',
	$blog_allowtrackbacks = 1,
	$blog_allowpingbacks = 0,
	$blog_pingb2evonet = 0,
	$blog_pingtechnorati = 0,
	$blog_pingweblogs = 0,
	$blog_pingblodotgs = 0,
	$blog_disp_bloglist	= 1,
	$blog_in_bloglist = 1 )
{
	global $DB, $query, $default_locale;

	if( $blog_locale == '#' ) $blog_locale = $default_locale;

	$query = "INSERT INTO T_blogs( blog_name, blog_shortname, blog_siteurl,
						blog_stub, blog_urlname, blog_staticfilename,
						blog_tagline, blog_description, blog_longdesc, blog_locale, blog_notes, blog_keywords,
						blog_UID, blog_allowcomments, blog_allowtrackbacks, blog_allowpingbacks, blog_pingb2evonet,
						blog_pingtechnorati, blog_pingweblogs, blog_pingblodotgs, blog_disp_bloglist,
						blog_in_bloglist, blog_links_blog_ID )
	VALUES ( ";
	$query .= "'".$DB->escape($blog_name)."', ";
	$query .= "'".$DB->escape($blog_shortname)."', ";
	$query .= "'".$DB->escape($blog_siteurl)."', ";
	$query .= "'".$DB->escape($blog_stub)."', ";
	$query .= "'".$DB->escape($blog_stub)."', ";		// This one is for urlname
	$query .= "'".$DB->escape($blog_staticfilename)."', ";
	$query .= "'".$DB->escape($blog_tagline)."', ";
	$query .= "'".$DB->escape($blog_description)."', ";
	$query .= "'".$DB->escape($blog_longdesc)."', ";
	$query .= "'".$DB->escape($blog_locale)."', ";
	$query .= "'".$DB->escape($blog_notes)."', ";
	$query .= "'".$DB->escape($blog_keywords)."', ";
	$query .= "'".$DB->escape($blog_UID)."', ";
	$query .= "'".$DB->escape($blog_allowcomments) . "', "
				 . "$blog_allowtrackbacks, $blog_allowpingbacks, $blog_pingb2evonet, $blog_pingtechnorati, "
				 . "$blog_pingweblogs, $blog_pingblodotgs, $blog_disp_bloglist, $blog_in_bloglist, "
				 . "$blog_links_blog_ID)";

	if( ! ($DB->query( $query )) )
		return 0;

	return $DB->insert_id;  // blog ID
}


/**
 * Update the user permissions for edited blog
 *
 * @param int Blog ID
 */
function blog_update_user_perms( $blog )
{
	global $DB;
	// Delete old perms for this blog:
	$DB->query( 'DELETE FROM T_coll_user_perms
								WHERE bloguser_blog_ID = '.$blog );

	// Now we need a full user list:
	$inserted_values = array();
	foreach( $DB->get_col( 'SELECT user_ID FROM T_users' ) as $loop_user_ID )
	{ // Check new permissions for each user:
		// echo "getting perms for user : $loop_user_ID <br />";

		$easy_mode = param( 'blog_perm_easy_'.$loop_user_ID, 'string', 'nomember' );

		if( $easy_mode != 'nomember' && $easy_mode != 'custom' )
		{
			$easy_perms = blogperms_from_easy( $easy_mode );

			$inserted_values[] = " ( $blog, $loop_user_ID, ".$easy_perms['bloguser_ismember']
														.', "'.$easy_perms['bloguser_perm_poststatuses']
														.'", '.$easy_perms['bloguser_perm_delpost'].', '.$easy_perms['bloguser_perm_comments']
														.', '.$easy_perms['bloguser_perm_cats'].', '.$easy_perms['bloguser_perm_properties']
														.', '.$easy_perms['bloguser_perm_media_upload'].', '.$easy_perms['bloguser_perm_media_browse']
														.', '.$easy_perms['bloguser_perm_media_change'].' ) ';
		}
		else
		{
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

			$perm_media_upload = param( 'blog_perm_media_upload_'.$loop_user_ID, 'integer', 0 );
			$perm_media_browse = param( 'blog_perm_media_browse_'.$loop_user_ID, 'integer', 0 );
			$perm_media_change = param( 'blog_perm_media_change_'.$loop_user_ID, 'integer', 0 );

			// Update those permissions in DB:

			if( $ismember || count($perm_post) || $perm_delpost || $perm_comments || $perm_cats || $perm_properties
										|| $perm_media_upload || $perm_media_browse || $perm_media_change )
			{ // There are some permissions for this user:
				$ismember = 1;	// Must have this permission

				// insert new perms:
				$inserted_values[] = " ( $blog, $loop_user_ID, $ismember, '".implode(',',$perm_post)."',
																	$perm_delpost, $perm_comments, $perm_cats, $perm_properties,
																	$perm_media_upload, $perm_media_browse, $perm_media_change )";
			}
		}
	}

	// Proceed with insertions:
	if( count( $inserted_values ) )
	{
		$DB->query( "INSERT INTO T_coll_user_perms( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember,
											bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments,
											bloguser_perm_cats, bloguser_perm_properties,
											bloguser_perm_media_upload, bloguser_perm_media_browse, bloguser_perm_media_change)
									VALUES ".implode( ',', $inserted_values ) );
	}
}


/**
 * Update the group permissions for edited blog
 *
 * @param int Blog ID
 */
function blog_update_group_perms( $blog )
{
	global $DB;
	// Delete old perms for this blog:
	$DB->query( 'DELETE FROM T_coll_group_perms
								WHERE bloggroup_blog_ID = '.$blog );

	// Now we need a full group list:
	$inserted_values = array();
	foreach( $DB->get_col( 'SELECT grp_ID FROM T_groups' ) as $loop_group_ID )
	{ // Check new permissions for each group:
		// echo "getting perms for group : $loop_group_ID <br />";

		$easy_mode = param( 'blog_perm_easy_'.$loop_group_ID, 'string', 'nomember' );

		if( $easy_mode != 'nomember' && $easy_mode != 'custom' )
		{
			$easy_perms = blogperms_from_easy( $easy_mode );

			$inserted_values[] = " ( $blog, $loop_group_ID, ".$easy_perms['bloguser_ismember']
														.', "'.$easy_perms['bloguser_perm_poststatuses']
														.'", '.$easy_perms['bloguser_perm_delpost'].', '.$easy_perms['bloguser_perm_comments']
														.', '.$easy_perms['bloguser_perm_cats'].', '.$easy_perms['bloguser_perm_properties']
														.', '.$easy_perms['bloguser_perm_media_upload'].', '.$easy_perms['bloguser_perm_media_browse']
														.', '.$easy_perms['bloguser_perm_media_change'].' ) ';
		}
		else
		{
			$perm_post = array();

			$ismember = param( 'blog_ismember_'.$loop_group_ID, 'integer', 0 );

			$perm_published = param( 'blog_perm_published_'.$loop_group_ID, 'string', '' );
			if( !empty($perm_published) ) $perm_post[] = 'published';

			$perm_protected = param( 'blog_perm_protected_'.$loop_group_ID, 'string', '' );
			if( !empty($perm_protected) ) $perm_post[] = 'protected';

			$perm_private = param( 'blog_perm_private_'.$loop_group_ID, 'string', '' );
			if( !empty($perm_private) ) $perm_post[] = 'private';

			$perm_draft = param( 'blog_perm_draft_'.$loop_group_ID, 'string', '' );
			if( !empty($perm_draft) ) $perm_post[] = 'draft';

			$perm_deprecated = param( 'blog_perm_deprecated_'.$loop_group_ID, 'string', '' );
			if( !empty($perm_deprecated) ) $perm_post[] = 'deprecated';

			$perm_delpost = param( 'blog_perm_delpost_'.$loop_group_ID, 'integer', 0 );
			$perm_comments = param( 'blog_perm_comments_'.$loop_group_ID, 'integer', 0 );
			$perm_cats = param( 'blog_perm_cats_'.$loop_group_ID, 'integer', 0 );
			$perm_properties = param( 'blog_perm_properties_'.$loop_group_ID, 'integer', 0 );

			$perm_media_upload = param( 'blog_perm_media_upload_'.$loop_group_ID, 'integer', 0 );
			$perm_media_browse = param( 'blog_perm_media_browse_'.$loop_group_ID, 'integer', 0 );
			$perm_media_change = param( 'blog_perm_media_change_'.$loop_group_ID, 'integer', 0 );

			// Update those permissions in DB:

			if( $ismember || count($perm_post) || $perm_delpost || $perm_comments || $perm_cats || $perm_properties
										|| $perm_media_upload || $perm_media_browse || $perm_media_change )
			{ // There are some permissions for this group:
				$ismember = 1;	// Must have this permission

				// insert new perms:
				$inserted_values[] = " ( $blog, $loop_group_ID, $ismember, '".implode(',',$perm_post)."',
																	$perm_delpost, $perm_comments, $perm_cats, $perm_properties,
																	$perm_media_upload, $perm_media_browse, $perm_media_change )";
			}
		}
	}

	// Proceed with insertions:
	if( count( $inserted_values ) )
	{
		$DB->query( "INSERT INTO T_coll_group_perms( bloggroup_blog_ID, bloggroup_group_ID, bloggroup_ismember,
											bloggroup_perm_poststatuses, bloggroup_perm_delpost, bloggroup_perm_comments,
											bloggroup_perm_cats, bloggroup_perm_properties,
											bloggroup_perm_media_upload, bloggroup_perm_media_browse, bloggroup_perm_media_change)
									VALUES ".implode( ',', $inserted_values ) );
	}
}




/**
 * Translates an given array of permissions to an "easy group".
 *
 * - nomember
 * - member
 * - editor (member+edit posts+delete+edit comments+all filemanager rights)
 * - administrator (editor+edit cats+edit blog)
 * - custom
 *
 * @param array indexed, as the result row from "SELECT * FROM T_coll_user_perms"
 * @return string one of the five groups (nomember, member, editor, admin, custom)
 */
function blogperms_get_easy( $perms, $context='user' )
{
	if( !isset($perms['blog'.$context.'_ismember']) )
	{
		return 'nomember';
	}

	if( !empty( $perms['blog'.$context.'_perm_poststatuses'] ) )
	{
		$perms_post = count( explode( ',', $perms['blog'.$context.'_perm_poststatuses'] ) );
	}
	else
	{
		$perms_post = 0;
	}


	$perms_editor =  $perms_post
									+(int)$perms['blog'.$context.'_perm_delpost']
									+(int)$perms['blog'.$context.'_perm_comments']
									+(int)$perms['blog'.$context.'_perm_media_upload']
									+(int)$perms['blog'.$context.'_perm_media_browse']
									+(int)$perms['blog'.$context.'_perm_media_change'];

	$perms_admin =   (int)$perms['blog'.$context.'_perm_properties']
									+(int)$perms['blog'.$context.'_perm_cats'];

	if( $perms_editor == 10 )
	{ // has full editor rights
		switch( $perms_admin )
		{
			case 0: return 'editor'; break;
			case 1: return 'custom'; break;
			case 2: return 'admin'; break;
		}
	}
	elseif( $perms_editor == 0 )
	{
		if( $perms_admin )
		{
			return 'custom';
		}
		else
		{
			return 'member';
		}
	}
	else
	{
		return 'custom';
	}
}


/**
 *
 * @param string "easy group": 'admin', 'editor', 'member'
 * @return array indexed, as the result row from "SELECT * FROM T_coll_user_perms"
 */
function blogperms_from_easy( $easy_group )
{
	$r = array(
		'bloguser_ismember' => 0,
		'bloguser_perm_poststatuses' => '',
		'bloguser_perm_delpost' => 0,
		'bloguser_perm_comments' => 0,
		'bloguser_perm_media_upload' => 0,
		'bloguser_perm_media_browse' => 0,
		'bloguser_perm_media_change' => 0,
		'bloguser_perm_properties' => 0,
		'bloguser_perm_cats' => 0
	);

	switch( $easy_group )
	{
		case 'admin':
			$r['bloguser_perm_properties'] = 1;
			$r['bloguser_perm_cats'] = 1;

		case 'editor':
			$r['bloguser_perm_poststatuses'] = 'deprecated,draft,private,protected,published';
			$r['bloguser_perm_delpost'] = 1;
			$r['bloguser_perm_comments'] = 1;
			$r['bloguser_perm_media_upload'] = 1;
			$r['bloguser_perm_media_browse'] = 1;
			$r['bloguser_perm_media_change'] = 1;

		case 'member':
			$r['bloguser_ismember'] = 1;
			break;

		default:
			return false;
	}
	return $r;
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
			$current_Blog = & $BlogCache->get_by_ID($blog);
	}
	else
	{
		$current_Blog = & $BlogCache->get_by_ID($this_blogparams->blog_ID);
	}

	return $current_Blog->get( $show );
}


/**
 * Get blog params for specified ID
 *
 * @todo on a heavy multiblog system, cache them one by one...
 * @deprecated deprecated by {@link Blog_get_by_ID()}
 *
 * @param integer Blog ID
 */
function get_blogparams_by_ID( $blog_ID )
{
	global $cache_blogs;

	if( $blog_ID < 1 ) die( 'No blog is selected!' );

	if( empty($cache_blogs[$blog_ID]) )
	{
		blog_load_cache();
	}
	if( !isset( $cache_blogs[$blog_ID] ) ) die( T_('Requested blog does not exist!') );
	return $cache_blogs[ $blog_ID ];
}


/**
 * Get Blog for specified ID
 *
 * @todo on a heavy multiblog system, cache them one by one...
 * @todo move over to BlogCache?!
 *
 * @param integer ID of Blog we want
 */
function Blog_get_by_ID( $blog_ID )
{
	global $cache_blogs;

	if( $blog_ID < 1 ) die( 'No blog is selected!' );

	if( empty($cache_blogs[$blog_ID]) )
	{
		blog_load_cache();
	}
	if( !isset( $cache_blogs[$blog_ID] ) ) die( T_('Requested blog does not exist!') );

	return new Blog( $cache_blogs[$blog_ID] ); // COPY !
}


/**
 * blog_load_cache(-)
 */
function blog_load_cache()
{
	global $DB, $cache_blogs;
	if( empty($cache_blogs) )
	{
		$cache_blogs = array();

		foreach( $DB->get_results( "SELECT * FROM T_blogs ORDER BY blog_ID" ) as $this_blog )
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


/**
 * Start blog iterator
 *
 * blog_list_start(-)
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
		return blog_list_next( $need );		// This can be recursive
	}

	$curr_blog_ID = $curr_blogparams->blog_ID;
	//echo "blogID=", $curr_blog_ID;
	return $curr_blog_ID;
}


/**
 * Next blog iteration
 *
 * blog_list_next(-)
 */
function blog_list_next( $need='' )
{
	global $cache_blogs, $curr_blogparams, $curr_blog_ID;

	$curr_blogparams = next( $cache_blogs );
	if( $curr_blogparams === false )
		return false; // No more blog!

	// echo 'need: ', $need, ' info:',get_bloginfo($need, $curr_blogparams );

	if( (!empty($need)) && (!get_bloginfo($need, $curr_blogparams )) )
	{ // We need the blog to have a specific criteria that is not met, search on...
		return blog_list_next( $need );		// This can be recursive
	}

	$curr_blog_ID = $curr_blogparams->blog_ID;
	// echo "blogID=", $curr_blog_ID;
	return $curr_blog_ID;
}


/**
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


/**
 * Check permissions on a given blog (by ID) and autoselect an appropriate blog
 * if necessary.
 *
 * @param integer Pre-selected blog (usually blog GET param)
 * @param string Permission name that must be given to the {@link $current_User} object.
 * @param string Permission level that must be given to the {@link $current_User} object.
 * @return integer The selected blog (0 means failure).
 */
function autoselect_blog( $selectedBlog, $permname, $permlevel = 'any' )
{
	global $current_User;
	global $default_to_blog;

	if( $selectedBlog )
	{ // a blog is selected
		if( !$current_User->check_perm( $permname, $permlevel, false, $selectedBlog ) )
		{ // invalid blog
			$selectedBlog = 0;
		}
	}

	if( !$selectedBlog )
	{ // No blog is selected so far (or selection was invalid)...
		if( $current_User->check_perm( $permname, $permlevel, false, $default_to_blog ) )
		{ // Default blog is a valid choice
			$selectedBlog = $default_to_blog;
		}
		else
		{ // Let's try to find another one:
			for( $curr_blog_ID = blog_list_start();
						$curr_blog_ID != false;
						$curr_blog_ID = blog_list_next() )
			{
				// not good for demouser>edit_cats: if( $current_User->check_perm( 'blog_ismember', 1, false, $curr_blog_ID ) )
				if( $current_User->check_perm( $permname, $permlevel, false, $curr_blog_ID ) )
				{ // Current user is a member of this blog... let's select it:
					$selectedBlog = $curr_blog_ID;
					break;
				}
			}
		}
	}

	return $selectedBlog;
}

/*
 * $Log$
 * Revision 1.3  2006/04/04 21:57:35  blueyed
 * Do not ping weblogs.com by default (for default/demo blogs)
 *
 * Revision 1.2  2006/03/12 23:08:58  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:57  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.22  2005/12/16 13:35:59  fplanque
 * no message
 *
 * Revision 1.21  2005/12/12 19:44:09  fplanque
 * Use cached objects by reference instead of copying them!!
 *
 * Revision 1.20  2005/12/12 19:21:21  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.19  2005/11/22 20:03:24  fplanque
 * no message
 *
 * Revision 1.18  2005/11/22 19:40:10  fplanque
 * bugfix
 *
 * Revision 1.17  2005/10/03 17:26:44  fplanque
 * synched upgrade with fresh DB;
 * renamed user_ID field
 *
 * Revision 1.16  2005/09/07 17:40:22  fplanque
 * enhanced antispam
 *
 * Revision 1.15  2005/09/06 17:13:54  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.14  2005/08/21 16:20:13  halton
 * Added group based blogging permissions (new tab under blog). Required schema change
 *
 * Revision 1.13  2005/05/25 17:13:33  fplanque
 * implemented email notifications on new comments/trackbacks
 *
 * Revision 1.12  2005/05/24 18:46:26  fplanque
 * implemented blog email subscriptions (part 1)
 *
 * Revision 1.11  2005/03/09 19:23:33  blueyed
 * doc
 *
 * Revision 1.10  2005/03/08 13:24:42  fplanque
 * minor
 *
 * Revision 1.9  2005/03/07 00:06:18  blueyed
 * added autoselect_blog
 *
 * Revision 1.8  2005/02/28 09:06:32  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.7  2005/02/08 19:29:24  blueyed
 * improved $DB's get_col() handling
 *
 * Revision 1.6  2005/02/08 04:45:02  blueyed
 * improved $DB get_results() handling
 *
 * Revision 1.5  2004/12/17 20:38:52  fplanque
 * started extending item/post capabilities (extra status, type)
 *
 * Revision 1.4  2004/12/15 20:50:34  fplanque
 * heavy refactoring
 * suppressed $use_cache and $sleep_after_edit
 * code cleanup
 *
 * Revision 1.3  2004/10/16 01:31:22  blueyed
 * documentation changes
 *
 * Revision 1.2  2004/10/14 18:31:24  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.55  2004/10/12 17:22:29  fplanque
 * Edited code documentation.
 *
 * Revision 1.53  2004/10/6 9:37:31  gorgeb
 * Added allowcomments, a per blog setting taking three values : always, post_by_post, never.
 *
 * Revision 1.1.1.1.2.1  2003/8/31 6:23:31  sakichan
 * Security fixes for various XSS vulnerability and SQL injection vulnerability
 */
?>