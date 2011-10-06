<?php
/**
 * This file loads and initializes the blog to be displayed.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
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
load_class( 'items/model/_itemlist.class.php', 'ItemList' );

// fp> A lot of time (like 40ms) will be consumed the first time a new Blog object is created.
// This happens within _BLOG_MAIN bloc in non logged in mode and is pretty much perturbing.
// Trying to create a dummy Blog below move the delay out.
// The constructor doesn't consume any time at all.
// This is very strange. Is it because of recursive class loading that happens when instanciating a Blog?
//$dummy = new Blog();
$Timer->start( '_BLOG_MAIN.inc' );


/*
 * blog ID. This is a little bit special.
 *
 * In most cases $blog should be set by a stub file and the param() call below will just check that it's an integer.
 *
 * Note we do NOT memorize the param as we don't want it in regenerate_url() calls.
 * Whenever we do, index.php will already have called param() with memorize=true
 *
 * In some cases $blog will not have been set before and it will be set with the param() call below.
 * Currently, this only happens with the old /xmlsrv/ RSS stubs.
 */
param( 'blog', 'integer', '', false );

// Getting current blog info:
$BlogCache = & get_BlogCache();
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
	$blog_baseuri = substr( $Blog->gen_baseurl(), strlen( $Blog->get_baseurl_root() ) );
	$Debuglog->add( 'blog_baseuri: "'.$blog_baseuri.'"', 'params' );

	// Remove trailer:
	$blog_baseuri_regexp = preg_replace( '~(\.php[0-9]?)?/?$~', '', $blog_baseuri );
	// Read possibilities in order to get a broad match:
	$blog_baseuri_regexp = '~^'.preg_quote( $blog_baseuri_regexp ).'(\.php[0-9]?)?/(.+)$~';
	// pre_dump( '', 'blog_baseuri_regexp: "', $blog_baseuri_regexp );

	if( preg_match( $blog_baseuri_regexp, $ReqPath, $matches ) )
	{ // We have extra path info
		$path_string = $matches[2];

		$Debuglog->add( 'Extra path info found! path_string=' . $path_string , 'params' );
		// echo "path=[$path_string]<br />";

		// Replace encoded ";" and ":" with regular chars (used for tags)
		// TODO: dh> why not urldecode it altogether? fp> would prolly make sense but requires testing -- note: check with tags (move urldecode from tags up here)
		// TODO: PHP5: use str_ireplace
		$path_string = str_replace(
			array('%3b', '%3B', '%3a', '%3A'),
			array(';', ';', ':', ':'),
			$path_string );

		// Slice the path:
		$path_elements = preg_split( '~/~', $path_string, 20, PREG_SPLIT_NO_EMPTY );
		// pre_dump( '', $path_elements, $pagenow );

		// PREVENT index.php or blog1.php etc from being considered as a slug later on.
		if( isset( $path_elements[0] ) && $path_elements[0] == $pagenow )
		{ // Ignore element that is the current PHP file name (ideally this URL will later be redirected to a canonical URL without any .php file in the URL)
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
			// TODO: dh> add plugin hook here, which would allow to handle path elements (name spaces in clean URLs), and to override internal functionality (e.g. handle tags in a different way).
			// Is this a tag ("prefix-only" mode)?
			if( $Blog->get_setting('tag_links') == 'prefix-only'
				&& count($path_elements) == 2
				&& $path_elements[0] == $Blog->get_setting('tag_prefix')
				&& isset($path_elements[1]) )
			{
				$tag = strip_tags(urldecode($path_elements[1]));

				// # of posts per page for tag page:
				if( ! $posts = $Blog->get_setting( 'tag_posts_per_page' ) )
				{ // use blog default
					$posts = $Blog->get_setting( 'posts_per_page' );
				}
			}
			else
			{
				// Does the pathinfo end with a / or a ; ?
				$last_char = substr( $path_string, -1 );
				$last_part = $path_elements[count( $path_elements )-1];
				$last_len  = strlen( $last_part );
				if( ( $last_char == '-' && ( ! $tags_dash_fix || $last_len != 40 ) ) || $last_char == ':'|| $last_char == ';' )
				{	// - : or ; -> We'll consider this to be a tag page
					$tag = substr( $last_part, 0, -1 );
					$tag = urldecode($tag);
					$tag = strip_tags($tag);	// security
					// pre_dump( $tag );

					// # of posts per page:
					if( ! $posts = $Blog->get_setting( 'tag_posts_per_page' ) )
					{ // use blog default
						$posts = $Blog->get_setting( 'posts_per_page' );
					}
				}
				elseif( ( $tags_dash_fix && $last_char == '-' && $last_len == 40 ) || $last_char != '/' )
				{	// NO ENDING SLASH or ends with a dash, is 40 chars long and $tags_dash_fix is true
					// -> We'll consider this to be a ref to a post.
					$Debuglog->add( 'We consider this o be a ref to a post - last char: '.$last_char, 'params' );

					// Set a lot of defaults as if we had received a complex URL:
					$m = '';
					$more = 1; // Display the extended entries' text
					$c = 1;    // Display comments
					$tb = 1;   // Display trackbacks
					$pb = 1;   // Display pingbacks

					if( preg_match( '#^p([0-9]+)$#', $last_part, $req_post ) )
					{ // The last param is of the form p000
						// echo 'post number';
						$p = $req_post[1];		// Post to display
					}
					else
					{ // Last param is a string, we'll consider this to be a post urltitle
						$title = $last_part;
						// echo 'post title : ', $title;
					}
				}
				else
				{	// ENDING SLASH -> we are looking for a daterange OR a chapter:
					$Debuglog->add( 'Last part: '.$last_part , 'params' );
					// echo $last_part;
					if( preg_match( '|^w?[0-9]+$|', $last_part ) )
					{ // Last part is a number or a "week" number:
						$i=0;
						$Debuglog->add( 'Last part is a number or a "week" number: '.$path_elements[$i] , 'params' );
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
					elseif( preg_match( '|^[A-Za-z0-9\-_]+$|', $last_part ) )	// UNDERSCORES for catching OLD URLS!!!
					{	// We are pointing to a chapter/category:
						$ChapterCache = & get_ChapterCache();
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



// Front page detection & selection should probably occur here.



/*
 * ____________________________ Get specific Item if requested ____________________________
 */
if( !empty($p) || !empty($title) )
{ // We are going to display a single post
	$title = rawurldecode($title);
	// Make sure the single post we're requesting (still) exists:
	$ItemCache = & get_ItemCache();
	if( !empty($p) )
	{	// Get from post ID:
		$Item = & $ItemCache->get_by_ID( $p, false );
	}
	else
	{	// Get from post title:
		$orig_title = $title;
		$title = preg_replace( '/[^A-Za-z0-9_]/', '-', $title );
		$Item = & $ItemCache->get_by_urltitle( $title, false );

		if( ( !empty( $Item ) ) && ( $Item !== false ) && (! $Item->is_part_of_blog( $blog ) ) )
		{ // we have found an Item object, but it doesn't belong to the current blog
			unset($Item);
		}

		if( empty($Item) && substr($title, -1) == '-' )
		{ // Try lookup by removing last invalid char, which might have been e.g. ">"
			$Item = $ItemCache->get_by_urltitle(substr($title, 0, -1), false, false);
		}
	}
	if( empty( $Item ) )
	{	// Post doesn't exist!

		// fp> TODO: ->viewing_allowed() for draft, private, protected and deprecated...

		$title_fallback = false;
		$tag_fallback = ( $tags_dash_fix && substr( $orig_title, -1 ) == '-' && strlen( $orig_title ) == 40 );

		if( ! $tag_fallback && !empty($title) && empty($already_looked_into_chapters) )
		{	// Let's try to fall back to a category/chapter...
			$ChapterCache = & get_ChapterCache();
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
			if( $tag_fallback )
			{
				$title = substr( $orig_title, 0, -1 );
			}
			if( $Blog->get_tag_post_count( $title ) )
			{ // We could match a tag from the extra path:
				$tag = $title;
				$title_fallback = true;
				$title = NULL;
			}
		}

		if( ! $title_fallback )
		{	// Let's try to fall back to a help slug...
			$SlugCache = & get_SlugCache();
			$Slug = & $SlugCache->get_by_name( $title, false, false );
			if( ! empty($Slug) && $Slug->get( 'type' ) == 'help' )
			{ // We could match a help slug from the extra path:
				$disp = 'help';
				$title_fallback = true;
				$title = NULL;
			}
		}

/*	fp> The following is alternative code for filtering out index.php or blog1.php but this should not be needed
				since I have re-enabled (a new version of) $pagenow removal earlier in this file.
		if( ! $title_fallback && $orig_title == $pagenow )
		{	// We are actually searching for something named after the PHP filename!
			// Example: blog1.php
			// Consider this as a request for the homepage:
			// pre_dump( '', $title, $orig_title, $pagenow, $disp );
			// Already set: $disp = 'posts';
			$title_fallback = true;
			$title = NULL;
		}
*/

		if( ! $title_fallback )
		{	// We were not able to fallback to anything meaningful:
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
	// if( in_array( $Item->ptyp_ID, array( 1000, 1500, 1520, 1530, 1570 ) ) ) // pages and intros
	if( $Item->ptyp_ID == 1000 )
	{
		$disp = 'page';
	}
	else
	{
		$disp = 'single';
	}

	// fp> note: the redirecting code that was here moved to skin_init() with the other redirecting code.
	// That feels more consistent and may also allow some skins to handle redirects differently (framing?)
	// I hope I didn't screw that up... but it felt like the historical reasons for this to be here no longer applied.
}


/*
 * ______________________ DETERMINE WHICH SKIN TO USE FOR DISPLAY _______________________
 */

// Check if a temporary skin has been requested (used for RSS syndication for example):
param( 'tempskin', 'string', '', true );
if( !empty( $tempskin ) )
{ // This will be handled like any other skin:
	// TODO: maybe restrict that to authorized users
	$skin = $tempskin;
}

if( isset( $skin ) )
{	// A skin has been requested by folder_name (url or stub):

	// Check validity of requested skin name:
	if( preg_match( '~([^-A-Za-z0-9._]|\.\.)~', $skin ) )
	{
		debug_die( 'The requested skin name is invalid.' );
	}

	$SkinCache = & get_SkinCache();
	$Skin = & $SkinCache->new_obj( NULL, $skin );

	if( $Skin->type == 'feed' )
	{	// Check if we actually allow the display of the feed; last chance to revert to 404 displayed in default skin.
		// Note: Skins with the type "feed" can always be accessed, even when they're not installed.
		if( $Blog->get_setting('feed_content') == 'none' )
		{ // We don't want to provide feeds; revert to 404!
			unset( $skin );
			unset( $Skin );
			$disp = '404';
			$disp_detail = '404-feeds-disabled';
		}
	}
	elseif( $Skin->type == 'sitemap' )
	{	// Check if we actually allow the display of sitemaps.
		// Note: Skins with the type "sitemap" can always be accessed, even when they're not installed.
		if( ! $Blog->get_setting('enable_sitemaps') )
		{ // We don't want to show this sitemap, revert to error 404:
			unset( $skin );
			unset( $Skin );
			$disp = '404';
			$disp_detail = '404-sitemaps-disabled';
		}
	}
	elseif( skin_exists( $skin ) && ! skin_installed( $skin ) )
	{	// The requested skin is not a feed skin and exists in the file system, but isn't installed:
		debug_die( sprintf( T_( 'The skin [%s] is not installed on this system.' ), htmlspecialchars( $skin ) ) );
	}
	elseif( ! empty( $tempskin ) )
	{ // By definition, we want to see the temporary skin (if we don't use feedburner... )
		$redir = 'no';
	}
}

if( !isset( $skin ) && !empty($Blog->skin_ID) )	// Note: if $skin is set to '', then we want to do a "no skin" display
{ // Use default skin from the database
	$SkinCache = & get_SkinCache();
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


$Timer->pause( '_BLOG_MAIN.inc');


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
	$Timer->resume( 'SKIN DISPLAY' );

	$Debuglog->add('Selected skin: '.$skin, 'skins' );

	// Instantiate PageCache:
	$Timer->resume( 'PageCache' );
	load_class( '_core/model/_pagecache.class.php', 'PageCache' );
	$PageCache = new PageCache( $Blog );
	// Check for cached content & Start caching if needed
	// Note: there are some redirects inside the skins themselves for canonical URLs,
	// If we have a cache hit, the redirect won't take place until the cache expires -- probably ok.
	// If we start collecting and a redirect happens, the collecting will just be lost and that's what we want.
	if( ! $PageCache->check() )
	{	// Cache miss, we have to generate:
		$Timer->pause( 'PageCache' );

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
					'postidx'        => 'postidx.main.php',
					'posts'          => 'posts.main.php',
					'profile'        => 'profile.main.php',
					'search'         => 'search.main.php',
					'single'         => 'single.main.php',
					'sitemap'        => 'sitemap.main.php',
					'subs'           => 'subs.main.php',
					'threads'        => 'threads.main.php',
					'messages'       => 'messages.main.php',
					'contacts'       => 'contacts.main.php',
					'users'          => 'users.main.php',
					// All others will default to index.main.php
				);

			if( !empty($disp_handlers[$disp]) )
			{
				if( file_exists( $disp_handler = $ads_current_skin_path.$disp_handlers[$disp] ) )
				{	// The skin has a customized page handler for this display:
					$Debuglog->add('blog_main: include '.rel_path_to_base($disp_handler).' (custom to this skin)', 'skins' );
					require $disp_handler;
				}
				elseif( file_exists( $disp_handler = $skins_path.$disp_handlers[$disp] ) )
				{	// Skins have a general page handler for this display:
					$Debuglog->add('blog_main: include '.rel_path_to_base($disp_handler).' (for CSS include -- added in v 4.1)', 'skins' );
					require $disp_handler;
				}
				elseif( $disp_handlers[$disp] == 'posts.main.php' && file_exists( $disp_handler = $ads_current_skin_path.'items.main.php' ) )
				{	// Compatibility with skins < 2.2.0
					$Debuglog->add('blog_main: include '.rel_path_to_base($disp_handler).' (compat with skins < 2.2.0)', 'skins' );
					require $disp_handler;
				}
				elseif( $disp_handlers[$disp] == 'comments.main.php' && file_exists( $disp_handler = $ads_current_skin_path.'latestcom.tpl.php' ) )
				{	// Compatibility with skins < 2.2.0
					$Debuglog->add('blog_main: include '.rel_path_to_base($disp_handler).' (compat with skins < 2.2.0)', 'skins' );
					require $disp_handler;
				}
				elseif( $disp_handlers[$disp] == 'feedback_popup.main.php' && file_exists( $disp_handler = $ads_current_skin_path.'feedback_popup.tpl.php' ) )
				{	// Compatibility with skins < 2.2.0
					$Debuglog->add('blog_main: include '.rel_path_to_base($disp_handler).' (compat with skins < 2.2.0)', 'skins' );
					require $disp_handler;
				}
				else
				{	// Use the default handler from the skins dir:
					$Debuglog->add('blog_main: include '.rel_path_to_base($ads_current_skin_path.'index.main.php').' (default handler)', 'skins' );
					require $ads_current_skin_path.'index.main.php';
				}
			}
			else
			{	// Use the default handler from the skins dir:
				$Debuglog->add('blog_main: include '.rel_path_to_base($ads_current_skin_path.'index.main.php').' (default index handler)', 'skins' );
				require $ads_current_skin_path.'index.main.php';
			}
		}

		// Save collected cached data if needed:
		$PageCache->end_collect();
	}
	$Timer->pause( 'PageCache' );

	$Timer->pause( 'SKIN DISPLAY' );

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
 * Revision 1.176  2011/10/06 11:49:47  efy-yurybakh
 * Replace all timestamp_min & timestamp_max with Blog's methods
 *
 * Revision 1.175  2011/09/30 12:24:56  efy-yurybakh
 * User directory
 *
 * Revision 1.174  2011/09/17 02:31:59  fplanque
 * Unless I screwed up with merges, this update is for making all included files in a blog use the same domain as that blog.
 *
 * Revision 1.173  2011/09/07 00:28:26  sam2kb
 * Replace non-ASCII character in regular expressions with ~
 *
 * Revision 1.172  2011/09/04 22:13:13  fplanque
 * copyright 2011
 *
 * Revision 1.171  2011/08/11 09:05:09  efy-asimo
 * Messaging in front office
 *
 * Revision 1.170  2011/02/10 23:07:21  fplanque
 * minor/doc
 *
 * Revision 1.169  2010/09/29 14:32:29  efy-asimo
 * doc and small modificaitons
 *
 * Revision 1.165.2.7  2010/09/28 20:35:18  fplanque
 * minor/doc
 *
 * Revision 1.165.2.6  2010/09/21 14:37:41  efy-asimo
 * Requesting a post in the wrong blog - fix
 *
 * Revision 1.165.2.5  2010/07/15 18:15:51  fplanque
 * Better pagenow handling
 *
 * Revision 1.165.2.4  2010/07/14 23:12:13  fplanque
 * fixed homepage url resolving for index.php & blogx.php
 *
 * Revision 1.165.2.3  2010/07/14 16:42:16  fplanque
 * doc
 *
 * Revision 1.165.2.2  2010/07/14 09:05:18  efy-asimo
 * todo modifications
 *
 * Revision 1.165.2.1  2010/07/06 00:47:42  fplanque
 * minor/doc
 *
 * Revision 1.165  2010/04/22 18:59:09  blueyed
 * Add halt_on_empty param to get_by_urltitle and use it. Bug: did when looking up single char bad URLs.
 *
 * Revision 1.164  2010/04/13 13:50:51  efy-asimo
 * slugs help radio optoin - slugs task
 *
 * Revision 1.163  2010/02/08 17:51:18  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.162  2010/01/13 23:39:17  fplanque
 * doc
 *
 * Revision 1.161  2009/12/22 23:13:37  fplanque
 * Skins v4, step 1:
 * Added new disp modes
 * Hooks for plugin disp modes
 * Enhanced menu widgets (BIG TIME! :)
 *
 * Revision 1.160  2009/12/20 22:12:20  fplanque
 * doc
 *
 * Revision 1.159  2009/12/12 02:16:17  blueyed
 * doc
 *
 * Revision 1.158  2009/11/30 00:22:04  fplanque
 * clean up debug info
 * show more timers in view of block caching
 *
 * Revision 1.157  2009/11/15 19:33:05  tblue246
 * bugfix/testing
 *
 * Revision 1.156  2009/11/15 19:05:45  fplanque
 * no message
 *
 * Revision 1.155  2009/10/29 20:44:30  blueyed
 * urldecode title and check if it can be found with the last invalid char removed.
 *
 * Revision 1.154  2009/10/17 15:23:04  tblue246
 * doc/correction
 *
 * Revision 1.153  2009/10/17 15:16:56  tblue246
 * doc
 *
 * Revision 1.152  2009/10/17 15:01:23  fplanque
 * doc
 *
 * Revision 1.151  2009/10/15 00:45:00  blueyed
 * doc/reply
 *
 * Revision 1.150  2009/10/10 21:12:12  tblue246
 * Doc
 *
 * Revision 1.149  2009/09/29 16:56:07  tblue246
 * Added setting to disable sitemaps skins
 *
 * Revision 1.148  2009/09/29 15:39:16  tblue246
 * doc
 *
 * Revision 1.147  2009/09/29 03:47:07  fplanque
 * doc
 *
 * Revision 1.146  2009/09/26 13:41:54  tblue246
 * If XML feeds are disabled for a blog, still allow accessing "sitemap" skins.
 *
 * Revision 1.145  2009/09/26 12:00:42  tblue246
 * Minor/coding style
 *
 * Revision 1.144  2009/09/25 07:32:52  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.143  2009/09/24 00:32:28  blueyed
 * Add some timers. skin_display is taking too long - obviously.
 *
 * Revision 1.142  2009/09/20 23:51:24  blueyed
 * whitespace
 *
 * Revision 1.141  2009/09/14 14:00:35  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.140  2009/07/30 20:22:04  blueyed
 * Debuglog: log selected skin
 *
 * Revision 1.139  2009/06/29 02:14:04  fplanque
 * no message
 *
 * Revision 1.138  2009/06/07 14:22:20  yabs
 * minor bug fix
 *
 * Revision 1.137  2009/05/31 14:03:31  tblue246
 * Fix feed redirection (external feed provider). Fixes http://forums.b2evolution.net/viewtopic.php?t=18824
 *
 * Revision 1.136  2009/05/28 22:44:37  blueyed
 * Add info about the included skin display handler to debuglog.
 *
 * Revision 1.135  2009/05/23 20:20:18  fplanque
 * Skins can now have a _skin.class.php file to override default Skin behaviour. Currently only the default name but can/will be extended.
 *
 * Revision 1.134  2009/05/20 12:58:14  fplanque
 * Homepage: option to 301 redirect to canonical homepage.
 * Option to support rel="canonical" instead of or when 301 redirect cannot be used.
 *
 * Revision 1.133  2009/05/20 12:56:34  fplanque
 * made tga-prefix-only  mode matching more strict
 *
 * Revision 1.132  2009/04/23 19:50:35  blueyed
 * TODO
 *
 * Revision 1.131  2009/04/22 22:46:33  blueyed
 * Add support for rel=tag in tag URLs. This adds a new tag_links mode 'prefix-only', which requires a prefix (default: tag) and uses no suffix (dash/colon/semicolon). Also adds more JS juice and cleans up/normalized previously existing JS. Not much tested, but implemented as discussed on ML.
 *
 * Revision 1.130  2009/04/22 20:11:42  blueyed
 * Use preg_split instead of split and manually removing empty slots/parts. Less code and faster in benchmark.
 *
 * Revision 1.129  2009/03/24 21:03:56  fplanque
 * Surely enough, everytime someone touches the resolver, they break it!
 * Stop playing with it!
 *
 * Revision 1.128  2009/03/23 12:19:19  tblue246
 * (temp)skin param: Allow plugin-provided skins
 *
 * Revision 1.127  2009/03/20 03:55:50  fplanque
 * (explain to me why it's better to have the fix enabled by default)
 *
 * Revision 1.126  2009/03/19 16:08:07  tblue246
 * minor
 *
 * Revision 1.125  2009/03/19 15:38:47  tblue246
 * Ugly bug fix for workaround
 *
 * Revision 1.124  2009/03/19 15:05:05  tblue246
 * Trying to fix the post URL dash issue.
 *
 * Revision 1.123  2009/03/17 14:01:20  waltercruz
 * rollbaking my broken commit
 *
 * Revision 1.121  2009/03/15 21:01:28  tblue246
 * minor
 *
 * Revision 1.120  2009/03/15 18:46:37  fplanque
 * please don't do whitespace edits
 *
 * Revision 1.119  2009/03/15 12:32:59  tblue246
 * minor
 *
 * Revision 1.118  2009/03/14 19:22:29  fplanque
 * minor
 *
 * Revision 1.117  2009/03/13 21:43:12  blueyed
 * fix doc
 *
 * Revision 1.115  2009/03/13 14:22:40  tblue246
 * (temp)skin param: Only allow the use of installed skins.
 *
 * Revision 1.112  2009/03/13 00:56:55  fplanque
 * Better handling of blog param for cleaner URLs.
 * A lot of testing was involved but I have tested many versions before reducing it to the max.
 * I hope this final version has no side effects.
 *
 * Revision 1.111  2009/03/08 23:57:38  fplanque
 * 2009
 *
 * Revision 1.110  2009/01/21 20:33:49  fplanque
 * different display between featured and intro posts
 *
 * Revision 1.109  2008/12/31 00:25:26  fplanque
 * bugfix
 *
 * Revision 1.108  2008/12/20 22:36:33  blueyed
 * Add is_same_url() to compare URLs without taking case of urlencoded parts into account. This is required to prevent infinite redirects in the handling of canonical URLs.
 *
 * Revision 1.107  2008/10/14 23:18:50  blueyed
 * Replace encoded ':' for tags in $path_string, too (and the lowercase encoded version)
 *
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
