<?php
/**
 * This file loads and initializes the blog to be displayed.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @package main
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */

if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/_main.inc.php';

load_funcs('skins/_skin.funcs.php');
load_class('items/model/_itemlist.class.php');

$Timer->start( '_blog_main.inc' );

// Getting GET or POST parameters:

/*
 * blog ID. This is a little bit special.
 * If it has been explicitely memorized already, we don't touch it.
 * Note: explicitely != auto_register_globals != stub file just setting it with $blog=x
 * Note: stub files should probably memorize the param explicitely!
 */
if( ! param_ismemorized('blog') )
{	// Not explicitely memorized yet, get param from GET or auto_register_globals OR a stub $blog = x:
	$Debuglog->add( 'blog param not memorized before _blog_main.inc', 'params' );
	// We default to 0 here because the param should always be included in regenerate_url() when present.
	// This will prevent weird indexing/search results in case the default changes after indexing.
  // On some occasions, we'll manually filter it out of regenerate_url() because we know we go through a stub for example.
	param( 'blog', 'integer', 0, true );
}

// Getting current blog info:
$BlogCache = & get_Cache( 'BlogCache' );
/**
 * @var Blog
 */
$Blog = & $BlogCache->get_by_ID( $blog, false, false );
if( empty( $Blog ) )
{
	require $skins_path.'_404_blog_not_found.main.php'; // error & exit
	// EXIT.
}


// Init $disp
param( 'disp', 'string', 'posts', true );
$disp_detail = '';


/*
 * _______________________________ Locale / Charset for the Blog _________________________________
 *
	TODO: blueyed>> This should get moved as default to the locale detection in _main.inc.php,
	        as we only want to activate the I/O charset, which is probably the user's..
	        It prevents using a locale/charset in the front office, apart from the one given as default for the blog!!
fp>there is no blog defined in _main and there should not be any
blueyed> Sure, but that means we should either split it, or use the locale here only, if there's no-one given with higher priority.
*/
// Activate matching locale:
$Debuglog->add( 'Activating blog locale: '.$Blog->get('locale'), 'locale' );
locale_activate( $Blog->get('locale') );


// Re-Init charset handling, in case current_charset has changed:
if( init_charsets( $current_charset ) )
{
  // Reload Blog(s) (for encoding of name, tagline etc):
  $BlogCache->clear();

  $Blog = & $BlogCache->get_by_ID( $blog );
}


/*
 * _____________________________ Extra path info decoding ________________________________
 *
 * This will translate extra path into 'regular' params.
 *
 * Decoding should try to work like this:
 *
 * baseurl/blog-urlname/junk/.../junk/post-title    -> points to a single post (no ending slash)
 * baseurl/blog-urlname/junk/.../junk/p142          -> points to a single post
 * baseurl/blog-urlname/2006/                       -> points to a yearly archive because of ending slash + 4 digits
 * baseurl/blog-urlname/2006/12/                    -> points to a monthly archive
 * baseurl/blog-urlname/2006/12/31/                 -> points to a daily archive
 * baseurl/blog-urlname/2006/w53/                   -> points to a weekly archive
 * baseurl/blog-urlname/junk/.../junk/chap-urlname/ -> points to a single chapter/category (because of ending slash)
 * Note: category names cannot be named like this [a-z][0-9]+
 */
if( ! isset( $resolve_extra_path ) ) { $resolve_extra_path = true; }
if( $resolve_extra_path )
{
	// Check and Remove blog base URI from ReqPath:
	$blog_baseuri = substr( $Blog->gen_baseurl(), strlen( $Blog->get('baseurlroot') ) );
	$Debuglog->add( 'blog_baseuri: "'.$blog_baseuri.'"', 'params' );

	// Remove trailer:
	$blog_baseuri_regexp = preg_replace( '¤(\.php[0-9]?)?/?$¤', '', $blog_baseuri );
	// Readd possibilities in order to get a broad match:
	$blog_baseuri_regexp = '¤^'.preg_quote( $blog_baseuri_regexp ).'(\.php[0-9]?)?/(.+)$¤';
	// pre_dump( 'blog_baseuri_regexp: "', $blog_baseuri_regexp );

	if( preg_match( $blog_baseuri_regexp, $ReqPath, $matches ) )
	{ // We have extra path info
		$path_string = $matches[2];

		$Debuglog->add( 'Extra path info found! path_string=' . $path_string , 'params' );
		// echo "path=[$path_string]<br />";

		// Replace encoded ; with regular ; (used for tags)
		$path_string = str_replace( '%3b', ';', $path_string );

		// Slice the path:
		$path_split = explode( '/', $path_string, 20 );

		// Remove empty slots:
		$path_elements = array();
		foreach( $path_split as $path_element )
		{
			if( !empty( $path_element ) )
			{
				$path_elements[] = $path_element;
			}
		}
		// pre_dump( '',$path_elements );

		if( isset( $path_elements[0] ) && preg_match( '#.*\.php[0-9]?$#', $path_elements[0] ) )
		{ // Ignore element ending with .php (fp: note: may be just '.php')
			array_shift( $path_elements );
			$Debuglog->add( 'Ignoring *.php in extra path info' , 'params' );
		}

		if( isset( $path_elements[0] )
				&& ( $path_elements[0] == $Blog->stub
						|| $path_elements[0] == $Blog->urlname ) )
		{ // Ignore stub file (if it ends with .php it should already have been filtered out above)
			array_shift( $path_elements );
			$Debuglog->add( 'Ignoring stub filename OR blog urlname in extra path info' , 'params' );
		}
		// pre_dump( $path_elements );

		// Do we still have extra path info to decode?
		if( count($path_elements) )
		{
			// Does the pathinfo end with a / or a ; ?
			$last_char = substr( $path_string, -1 );
			if( $last_char == '-' || $last_char == ':'|| $last_char == ';' )
			{	// - : or ; -> We'll consider this to be a tag page
				$last_part = $path_elements[count($path_elements)-1];
				$tag = substr( $last_part, 0, strlen($last_part)-1 );
				$tag = urldecode($tag);
				$tag = strip_tags($tag);	// security
				// pre_dump( $tag );

				// # of posts per page:
 				if( ! $posts = $Blog->get_setting( 'tag_posts_per_page' ) )
				{ // use blog default
					$posts = $Blog->get_setting( 'posts_per_page' );
				}
			}
			elseif( $last_char != '/' )
			{ // NO ENDING SLASH -> We'll consider this to be a ref to a post:
				// Set a lot of defaults as if we had received a complex URL:
				$m = '';
				$more = 1; // Display the extended entries' text
				$c = 1;    // Display comments
				$tb = 1;   // Display trackbacks
				$pb = 1;   // Display pingbacks

				$path_element = $path_elements[count($path_elements)-1];
				if( preg_match( "#^p([0-9]+)$#", $path_element, $req_post ) )
				{ // The last param is of the form p000
					// echo 'post number';
					$p = $req_post[1];		// Post to display
				}
				else
				{ // Last param is a string, we'll consider this to be a post urltitle
					$title = $path_element;
					// echo 'post title : ', $title;
				}
			}
			else
			{	// ENDING SLASH -> we are looking for a daterange OR a chapter:

				$last_part = $path_elements[count($path_elements)-1];
				// echo $last_part;
				if( preg_match( '|^w?[0-9]+$|', $last_part ) )
				{ // Last part is a number or a "week" number:
					$i=0;
					// echo $path_elements[$i];
					if( isset( $path_elements[$i] ) )
					{
						if( is_numeric( $path_elements[$i] ) )
						{ // We'll consider this to be the year
							$m = $path_elements[$i++];
							$Debuglog->add( 'Setting year from extra path info. $m=' . $m , 'params' );

							// Also use the prefered posts per page for archives (may be NULL, in which case the blog default will be used later on)
							if( ! $posts = $Blog->get_setting( 'archive_posts_per_page' ) )
							{ // use blog default
								$posts = $Blog->get_setting( 'posts_per_page' );
							}

							if( isset( $path_elements[$i] ) && is_numeric( $path_elements[$i] ) )
							{ // We'll consider this to be the month
								$m .= $path_elements[$i++];
								$Debuglog->add( 'Setting month from extra path info. $m=' . $m , 'params' );

								if( isset( $path_elements[$i] ) && is_numeric( $path_elements[$i] ) )
								{ // We'll consider this to be the day
									$m .= $path_elements[$i++];
									$Debuglog->add( 'Setting day from extra path info. $m=' . $m , 'params' );
								}
							}
							elseif( isset( $path_elements[$i] ) && substr( $path_elements[$i], 0, 1 ) == 'w' )
							{ // We consider this a week number
								$w = substr( $path_elements[$i], 1, 2 );
							}
						}
						else
						{	// We did not get a number/year...
							$disp = '404';
							$disp_detail = '404-malformed_url-missing_year';
						}
					}
				}
				elseif( preg_match( '|^[A-Za-z0-9\-]+$|', $last_part ) )
				{	// We are pointing to a chapter/category:
					$ChapterCache = & get_Cache( 'ChapterCache' );
					/**
					 * @var Chapter
					 */
					$Chapter = & $ChapterCache->get_by_urlname( $last_part, false );
					if( empty( $Chapter ) )
					{	// We could not match a chapter...
						// We are going to consider this to be a post title with a misplaced trailing slash.
						// That happens when upgrading from WP for example.
						$title = $last_part; // Will be sought later
						$already_looked_into_chapters = true;
					}
					else
					{	// We could match a chapter from the extra path:
						$cat = $Chapter->ID;
						// Also use the prefered posts per page for a cat
						if( ! $posts = $Blog->get_setting( 'chapter_posts_per_page' ) )
						{ // use blog default
							$posts = $Blog->get_setting( 'posts_per_page' );
						}
			    }
				}
				else
				{	// We did not get anything we can decode...
					// echo 'neither number nor cat';
					$disp = '404';
					$disp_detail = '404-malformed_url-bad_char';
				}
			}
		}

	}
}


/*
 * ____________________________ Query params ____________________________
 *
 * Note: if the params have been set by the extra-path-info above, param() will not touch them.
 */
param( 'p', 'integer', '', true );              // Specific post number to display
param( 'title', 'string', '', true );						// urtitle of post to display
param( 'redir', 'string', 'yes', false );				// Do we allow redirection to canonical URL? (allows to force a 'single post' URL for commenting)
param( 'preview', 'integer', 0, true );         // Is this preview ?
param( 'stats', 'integer', 0 );									// Deprecated but might still be used by spambots

// In case these were not set by the stub:
if( !isset($timestamp_min) ) $timestamp_min = '';
if( !isset($timestamp_max) ) $timestamp_max = '';


/*
 * ____________________________ Get specific Item if requested ____________________________
 */
if( !empty($p) || !empty($title) )
{ // We are going to display a single post
	// Make sure the single post we're requesting (still) exists:
	$ItemCache = & get_Cache( 'ItemCache' );
	if( !empty($p) )
	{	// Get from post ID:
		$Item = & $ItemCache->get_by_ID( $p, false );
	}
	else
	{	// Get from post title:
		$title = preg_replace( '/[^A-Za-z0-9_]/', '-', $title );
		$Item = & $ItemCache->get_by_urltitle( $title, false );
	}
	if( empty( $Item ) )
	{	// Post doesn't exist!

		// fp> TODO: ->viewing_allowed() for draft, private, protected and deprecated...

		$title_fallback = false;

		if( !empty($title) && empty($already_looked_into_chapters) )
		{	// Let's try to fall back to a category/chapter...
			$ChapterCache = & get_Cache( 'ChapterCache' );
			/**
			 * @var Chapter
			 */
			$Chapter = & $ChapterCache->get_by_urlname( $title, false );
			if( !empty( $Chapter ) )
			{	// We could match a chapter from the extra path:
				$cat = $Chapter->ID;
				$title_fallback = true;
				$title = NULL;
				// Also use the prefered posts per page for a cat
				if( ! $posts = $Blog->get_setting( 'chapter_posts_per_page' ) )
				{ // use blog default
					$posts = $Blog->get_setting( 'posts_per_page' );
				}
			}
		}

		if( !empty($title) )
		{	// Let's try to fall back to a tag...
			if( $Blog->get_tag_post_count( $title ) )
			{ // We could match a tag from the extra path:
				$tag = $title;
				$title_fallback = true;
				$title = NULL;
			}
		}

		if( ! $title_fallback )
		{	// We were not able to fallback to anythign meaningful:
			$disp = '404';
			$disp_detail = '404-post_not_found';
		}
	}
}


/*
 * ____________________________ "Clean up" the request ____________________________
 *
 * Make sure that:
 * 1) disp is set to "single" if single post requested
 * 2) URL is canonical if:
 *    - some content was requested in a weird/deprecated way
 *    - or if content identifiers have changed
 */
if( $stats || $disp == 'stats' )
{	// This used to be a spamfest...
	require $skins_path.'_410_stats_gone.main.php'; // error & exit
	// EXIT.
}
elseif( !empty($preview) )
{	// Preview
	$disp = 'single';
	// Consider this as an admin hit!
	$Hit->referer_type = 'admin';
}
elseif( $disp == 'posts' && !empty($Item) )
{ // We are going to display a single post
	if( $Item->ptyp_ID == 1000 )
	{
		$disp = 'page';
	}
	else
	{
		$disp = 'single';
	}

	if( $redir == 'yes' )
	{ // $redir=no here allows to force a 'single post' URL for commenting

		// Check if the post has 'redirected' status:
		if( $Item->status == 'redirected' )
		{	// Redirect to the URL specified in the post:
			$Debuglog->add( 'Redirecting to post URL ['.$Item->url.'].' );
			header_redirect( $Item->url, true );
		}

		// Check if we want to redirect to a canonical URL for the post
		// Please document encountered problems.
		if( $Blog->get_setting( 'canonical_item_urls' ) && $redir == 'yes' )
		{	// We want to redirect to the Item's canonical URL:

			$canonical_url = $Item->get_permanent_url( '', '', '&' );

			// fp> why are we cropping params?
			$requested_crop = preg_replace( '¤\?.*$¤', '', $ReqHost.$ReqURI );
			$canonical_crop = preg_replace( '¤\?.*$¤', '', $canonical_url );
			// pre_dump( '', $requested_crop, $canonical_crop );

			if( $requested_crop != $canonical_crop )
			{	// The requested URL does not look like the canonical URL for this post,
				// REDIRECT TO THE CANONICAL URL:
				// fp> TODO: we might be losing additional params, it would be better to keep them... (but we have redir=no for that)
				$Debuglog->add( 'Redirecting to canonical URL ['.$canonical_url.'].' );
				header_redirect( $canonical_url, true );
				// EXITED.
			}
		}
	}
}


/*
 * ______________________ DETERMINE WHICH SKIN TO USE FOR DISPLAY _______________________
 */

// Check if a temporary skin has been requested (used for RSS syndication for example):
param( 'tempskin', 'string', '', true );
if( !empty( $tempskin ) )
{ // This will be handled like any other skin:
	$skin = $tempskin;
}

if( isset( $skin ) )
{	// A skin has been requested by folder_name (url or stub):

	// Check validity of requested skin name:
	if( preg_match( '~([^-A-Za-z0-9._]|\.\.)~', $skin ) )
	{
		debug_die( 'The requested skin name is invalid.' );
	}

	// TODO: access to uninstalled skins for preview only for authorized users only)
	// TODO: in other cases, load the installed version
	// TODO: take care about *all* RSS feeds
	load_class( 'skins/model/_skin.class.php' );
	$Skin = & new Skin( NULL, $skin );

	if( $Skin->type == 'feed' )
	{	// Check if we actually allow the display of the feed; last chance to revert to 404 displayed in default skin
		if( $Blog->get_setting('feed_content') == 'none' )
		{ // We don't want to provide feeds; revert to 404!
			unset( $skin );
			unset( $Skin );
			$disp = '404';
			$disp_detail = '404-feeds-disabled';
		}
	}
}

if( !isset( $skin ) )	// Note: if $skin is set to '', then we want to do a "no skin" display
{ // Use default skin from the database
	$SkinCache = & get_cache( 'SkinCache' );

	$Skin = & $SkinCache->get_by_ID( $Blog->skin_ID );

	$skin = $Skin->folder;
}

// Because a lot of bloggers will delete skins, we have to make this fool proof with extra checking:
if( !empty( $skin ) && !skin_exists( $skin ) )
{ // We want to use a skin, but it doesn't exist!
	$err_msg = sprintf( T_('The skin [%s] set for blog [%s] does not exist. It must be properly set in the <a %s>blog properties</a> or properly overriden in a stub file.'),
		htmlspecialchars($skin),
		$Blog->dget('shortname'),
		'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=skin&amp;blog='.$Blog->ID.'"' );
	debug_die( $err_msg );
}


$Timer->pause( '_blog_main.inc');


/*
 * _______________________________ READY TO DISPLAY _______________________________
 *
 * At this point $skin holds the name of the skin we want to use, or '' for no skin!
 */


// Trigger plugin event:
// fp> TODO: please doc with example of what this can be used for
$Plugins->trigger_event( 'BeforeBlogDisplay', array('skin'=>$skin) );


if( !empty( $skin ) )
{ // We want to display with a skin now:

	// Instanciate PageCache:
	load_class( '_core/model/_pagecache.class.php' );
	$PageCache = & new PageCache( $Blog );
	// Check for cached content & Start caching if needed
	// Note: there are some redirects inside the skins themselves for canonical URLs,
	// If we have a cache hit, the redirect won't take place until the cache expires -- probably ok.
	// If we start collecting and a redirect happens, the collecting will just be lost and that's what we want.
	if( ! $PageCache->check() )
	{	// Cache miss, we have to generate:

		if( $skin_provided_by_plugin = skin_provided_by_plugin($skin) )
		{
			$Plugins->call_method( $skin_provided_by_plugin, 'DisplaySkin', $tmp_params = array('skin'=>$skin) );
		}
		else
		{
			// Path for the current skin:
			$ads_current_skin_path = $skins_path.$skin.'/';

			$disp_handlers = array(
					'404'            => '404_not_found.main.php',
					'arcdir'         => 'arcdir.main.php',
					'catdir'         => 'catdir.main.php',
					'comments'       => 'comments.main.php',
					'feedback-popup' => 'feedback_popup.main.php',
					'mediaidx'       => 'mediaidx.main.php',
					'msgform'        => 'msgform.main.php',
					'page'           => 'page.main.php',
					'posts'          => 'posts.main.php',
					'profile'        => 'profile.main.php',
					'single'         => 'single.main.php',
					'subs'           => 'subs.main.php',
					// All others will default to index.main.php
				);

			if( !empty($disp_handlers[$disp]) )
			{
				if( file_exists( $disp_handler = $ads_current_skin_path.$disp_handlers[$disp] ) )
				{	// The skin has a customized page handler for this display:
					require $disp_handler;
				}
				elseif( $disp_handlers[$disp] == 'posts.main.php' && file_exists( $disp_handler = $ads_current_skin_path.'items.main.php' ) )
				{	// Compatibility with skins < 2.2.0
					require $disp_handler;
				}
				elseif( $disp_handlers[$disp] == 'comments.main.php' && file_exists( $disp_handler = $ads_current_skin_path.'latestcom.tpl.php' ) )
				{	// Compatibility with skins < 2.2.0
					require $disp_handler;
				}
				elseif( $disp_handlers[$disp] == 'feedback_popup.main.php' && file_exists( $disp_handler = $ads_current_skin_path.'feedback_popup.tpl.php' ) )
				{	// Compatibility with skins < 2.2.0
					require $disp_handler;
				}
				else
				{	// Use the default handler from the skins dir:
					require $ads_current_skin_path.'index.main.php';
				}
			}
			else
			{	// Use the default handler from the skins dir:
				require $ads_current_skin_path.'index.main.php';
			}
		}

		// Save collected cached data if needed:
		$PageCache->end_collect();
	}

	// We probably don't want to return to the caller if we have displayed a skin...
	// That is useful if the caller implements a custom display but we still use skins for RSS/ Atom etc..
	exit(0);
}
else
{	// We don't use a skin. Hopefully the caller will do some displaying.
	// Set a few vars with default values, just in case...
	$ads_current_skin_path = $htsrv_path;

	// We'll just return to the caller now... (if we have not used a skin, the caller should do the display after this)
}


/*
 * $Log$
 * Revision 1.106  2008/09/28 08:06:04  fplanque
 * Refactoring / extended page level caching
 *
 * Revision 1.105  2008/09/27 08:14:02  fplanque
 * page level caching
 *
 * Revision 1.104  2008/09/09 06:03:29  fplanque
 * More tag URL options
 * Enhanced URL resolution for categories and tags
 *
 * Revision 1.103  2008/05/26 19:22:00  fplanque
 * fixes
 *
 * Revision 1.102  2008/05/06 23:37:06  fplanque
 * revert
 *
 * Revision 1.99  2008/03/30 23:04:23  fplanque
 * fix
 *
 * Revision 1.98  2008/03/23 23:40:42  fplanque
 * no message
 *
 * Revision 1.97  2008/03/21 19:42:44  fplanque
 * enhanced 404 handling
 *
 * Revision 1.96  2008/03/21 17:41:56  fplanque
 * custom 404 pages
 *
 * Revision 1.95  2008/02/19 11:11:16  fplanque
 * no message
 *
 * Revision 1.94  2008/02/11 23:48:14  fplanque
 * tag URL decoding fux
 *
 * Revision 1.93  2008/01/21 09:35:23  fplanque
 * (c) 2008
 *
 * Revision 1.92  2008/01/07 02:53:26  fplanque
 * cleaner tag urls
 *
 * Revision 1.91  2008/01/04 23:18:11  blueyed
 * Use preg_match instead of ereg; Move skin-does-not-exist debug_die further down
 *
 * Revision 1.90  2007/11/29 19:29:22  fplanque
 * normalized skin filenames
 *
 * Revision 1.89  2007/11/25 14:28:17  fplanque
 * additional SEO settings
 *
 * Revision 1.88  2007/10/06 21:26:16  fplanque
 * WP url decoding compatibility + cleanup
 *
 * Revision 1.87  2007/09/28 09:28:36  fplanque
 * per blog advanced SEO settings
 *
 * Revision 1.86  2007/09/10 15:35:23  fplanque
 * .php in blog url fix
 *
 * Revision 1.85  2007/07/13 23:47:36  fplanque
 * New start pages!
 */
?>
