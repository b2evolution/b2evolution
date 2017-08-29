<?php
/**
 * This file loads and initializes the blog to be displayed.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package main
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
$Collection = $Blog = & $BlogCache->get_by_ID( $blog, false, false );
if( empty( $Blog ) )
{
	require $siteskins_path.'_404_blog_not_found.main.php'; // error & exit
	// EXIT.
}


if( $debug == 2 || is_logged_in() )
{	// Allow debug info only for logged-in users OR when debug == 2:

	// Show/Hide the containers:
	$display_containers = param( 'display_containers', 'string' );
	if( $display_containers == 'show' )
	{
		$Session->set( 'display_containers_'.$blog, 1 );
	}
	elseif( $display_containers == 'hide' )
	{
		$Session->delete( 'display_containers_'.$blog );
	}

	// Show/Hide the includes:
	$display_includes = param( 'display_includes', 'string' );
	if( $display_includes == 'show' )
	{
		$Session->set( 'display_includes_'.$blog, 1 );
	}
	elseif( $display_includes == 'hide' )
	{
		$Session->delete( 'display_includes_'.$blog );
	}
}


// Init $disp
$default_disp = '-'; // '-' means we have no explicit disp request yet... this may change with extraptah info or by detecting front page later
param( 'disp', '/^[a-z0-9\-_]+$/', $default_disp, true );
$disp_detail = '';
$is_front = false;	// So far we have not detected that we are displaying the front page


/*
 * _______________________________ Locale / Charset for the Blog _________________________________
 *
	TODO: blueyed>> This should get moved as default to the locale detection in _main.inc.php,
	        as we only want to activate the I/O charset, which is probably the user's..
	        It prevents using a locale/charset in the front office, apart from the one given as default for the blog!!
fp>there is no blog defined in _main and there should not be any
blueyed> Sure, but that means we should either split it, or use the locale here only, if there's no-one given with higher priority.
*/
if( $Blog->get_setting( 'locale_source' ) == 'blog' )
{ // Activate matching locale:
	$Debuglog->add( 'Activating blog locale: '.$Blog->get( 'locale' ), 'locale' );
	locale_activate( $Blog->get( 'locale' ) );
}


// Re-Init charset handling, in case current_charset has changed:
if( init_charsets( $current_charset ) )
{
	// Reload Blog(s) (for encoding of name, tagline etc):
	$BlogCache->clear();

	$Collection = $Blog = & $BlogCache->get_by_ID( $blog );
	if( is_logged_in() )
	{ // We also need to reload the current User with the new final charset
		$UserCache = & get_UserCache();
		$UserCache->clear();
		$current_User = & $UserCache->get_by_ID( $current_User->ID );
	}
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
	$blog_baseuri_regexp = '~^'.preg_quote( $blog_baseuri_regexp, '~' ).'(\.php[0-9]?)?/(.+)$~';
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
				$disp = 'posts';
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
					$disp = 'posts';
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
								$disp = 'posts';
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
							$disp = 'posts';
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

		// Remove .html or .htm extension:
		$title = preg_replace( '/\.(html|htm)$/', '', $title );

		// Convert all special chars to -
		$title = preg_replace( '/[^A-Za-z0-9_]/', '-', $title );

		// Search item by title:
		$Item = & $ItemCache->get_by_urltitle( $title, false, false );

		if( ( !empty( $Item ) ) && ( $Item !== false ) && (! $Item->is_part_of_blog( $blog ) ) )
		{ // We have found an Item object, but it doesn't belong to the current blog!
			// Check if we want to redirect moved posts:
			if( $Settings->get( 'redirect_moved_posts' ) )
			{ // Redirect to the item current permanent url
				header_redirect( $Item->get_permanent_url(), 301 );
				// already exited
			}
			unset($Item);
		}

		if( empty($Item) && substr($title, -1) == '-' )
		{ // Try lookup by removing last invalid chars, which might have been e.g. > | "> | , | ,. | ">?!
			$Item = $ItemCache->get_by_urltitle( preg_replace( '/\-+$/', '', $title ), false, false );
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

		if( ! $title_fallback )
		{	// We were not able to fallback to anything meaningful:
			$disp = '404';
			$disp_detail = '404-post_not_found';
			$requested_404_title = $title;
		}
	}
}


// Check if a forced skin has been requested (used by mobile skin switcher widget):
param( 'force_skin', 'string', '' );
if( ! empty( $force_skin ) )
{ // Set the forced skin from request
	if( $force_skin == 'auto' )
	{ // Delete the forced skin from Session to use default skin
		$Session->delete( 'force_skin' );
	}
	else
	{ // Save the forced skin in Session
		$Session->set( 'force_skin', $force_skin );
	}
}
else
{ // Try to get a skin from session
	$force_skin = $Session->get( 'force_skin' );
}
if( ! empty( $force_skin ) )
{ // The forced skin is defined in request or in Session
	$skin = $Blog->get_skin_folder( $force_skin );
}


// Check if a temporary skin has been requested (used for RSS syndication for example):
param( 'tempskin', 'string', '', true );
if( !empty( $tempskin ) )
{ // This will be handled like any other skin:
	// TODO: maybe restrict that to authorized users
	$skin = $tempskin;
	if( empty( $disp ) || $disp == '-' )
	{ // Set default disp for RSS skins
		$disp = 'posts';
	}
}


// Set $disp to 'posts' when filter by categories or tags or date
param( 'catsel', 'array:integer', NULL );
param( 'cat', 'string', NULL );
param( 'tag', 'string', NULL );
param( 'm', 'string', NULL );
if( empty( $Item ) &&
		(
			! is_null( $catsel ) || // Filter by many categories
			( $disp != 'edit' && ! is_null( $cat ) ) || // Filter by one category
			! is_null( $tag ) || // Filter by tag
			! empty( $m ) // Filter by date like '201410' (urls from ?disp=arcdir)
	) )
{
	$disp = 'posts';
}
unset( $catsel );

/*
 * ____________________________ "Clean up" the request ____________________________
 *
 * Make sure that:
 * 1) disp is set to "single" if single post requested
 * 2) URL is canonical if:
 *    - some content was requested in a weird/deprecated way
 *    - or if content identifiers have changed
 * This will also detect that we are on the front page (if nothing has triggered a specific $disp)
 */
if( $stats || $disp == 'stats' )
{	// This used to be a spamfest...
	require $siteskins_path.'_410_stats_gone.main.php'; // error & exit
	// EXIT.
}
elseif( !empty($preview) )
{	// Preview
	$disp = 'single';
	// Consider this as an admin hit!
	$Hit->hit_type = 'admin';
}
elseif( ( $disp == 'visits' ) && ( ( $Settings->get( 'enable_visit_tracking' ) != 1 ) || ! is_logged_in() ) )
{ // Check if visit tracking is enabled and the user is logged in before allowing profile visit display
	$disp = '403';
	$disp_detail = '403-visit-tracking-disabled';
}
elseif( $disp == '-' && !empty($Item) )
{ // We have not requested a specific disp but we have identified a specific post to be displayed
	// We are going to display a single post
	if( in_array( $Item->get_type_setting( 'usage' ), array( 'special', 'content-block' ) ) )
	{	// Display 404 page for all "Content Blocks" and "Special" items intead of normal single page:
		$disp = '404';
	}
	elseif( preg_match( '|[&?](download=\d+)|', $ReqURI ) )
	{
		$disp = 'download';

		// erhsatingin> Is this the right place to increment the download count?
		$link_ID = param( 'download', 'integer', false);
		$LinkCache = & get_LinkCache();
		if( ( $download_Link = & $LinkCache->get_by_ID( $link_ID, false, false ) ) && // Link exists in DB
				( $download_File = & $download_Link->get_File() ) && // Link has a correct File object
				( $download_File->exists() ) // File exists on the disk
			)
		{
			$download_File->increment_download_count();
		}
	}
	elseif( $Item->get_type_setting( 'usage' ) == 'page' )
	{
		$disp = 'page';
	}
	else
	{
		$disp = 'single';
	}
}
elseif( $disp == '-' )
{ // No specific request of any kind...
	// We consider this to be the home page:
	$disp = $Blog->get_setting('front_disp');
	// Note: the above is where we MIGHT in fact set $disp = 'front';

	$is_front = true; // we have detected that we are displaying the front page

	// Do we need to handle the canoncial url?
	if( ( $Blog->get_setting( 'canonical_homepage' ) && $redir == 'yes' )
			|| $Blog->get_setting( 'relcanonical_homepage' ) )
	{ // Check if the URL was canonical:
		$canonical_url = $Blog->gen_blogurl();
		if( ! is_same_url( $ReqURL, $canonical_url, $Blog->get( 'http_protocol' ) != 'always_redirect' ) )
		{	// We are not on the canonicial blog url:
			if( $Blog->get_setting( 'canonical_homepage' ) && $redir == 'yes' )
			{	// REDIRECT TO THE CANONICAL URL:
				header_redirect( $canonical_url, (empty( $display_containers ) && empty( $display_includes )) ? 301 : 303 );
			}
			else
			{	// Use link rel="canoncial":
				add_headline( '<link rel="canonical" href="'.$canonical_url.'" />' );
			}
		}
	}

	if( $disp == 'single' )
	{	// We must find first item from disp=posts and display it on front page:
		if( $Item = & $Blog->get_first_mainlist_Item() )
		{	// The item is found, Use it:
			set_param( 'p', $Item->ID );
			$c = 1; // Display comments
		}

		if( empty( $Item ) )
		{	// If item is not found, display 404 page with below error message:
			$Messages->add( sprintf( T_('Front page is set to display first post but there is nothing to display.'), $p ), 'error' );
			$disp = '404';
		}
	}

	if( $disp == 'page' )
	{ // Specific page is displayed on front page
		set_param( 'p', $Blog->get_setting('front_post_ID') );
		$c = 1; // Display comments

		$ItemCache = & get_ItemCache();
		$Item = & $ItemCache->get_by_ID( $p, false );

		if( empty($Item) )
		{
			$Messages->add( sprintf( T_('Front page is set to display page ID=%d but it does not exist.'), $p ), 'error' );
			$disp = '404';
		}
	}

}
elseif( ( ( $disp == 'page' ) || ( $disp == 'single' ) ) && empty( $Item ) )
{ // 'page' and 'single' are not valid display params if $Item is not set
	// Note: The 'preview' action is the only one exception, but that is handled above in this if statement
	$disp = '404';
	$disp_detail = '404-post_not_found';
}

param( 'user_ID', 'integer', NULL );
if( ( $disp == 'user' ) && isset( $user_ID ) && isset( $current_User ) && ( $user_ID != $current_User->ID ) && ( $Settings->get( 'enable_visit_tracking') == 1 ) )
{ // add or increment to user profile visit
	add_user_profile_visit( $user_ID, $current_User->ID );
}


if( $disp == 'terms' )
{	// Display a page of terms & conditions:
	$terms_item_ID = intval( $Settings->get( 'site_terms' ) );
	if( $Settings->get( 'site_terms_enabled' ) && $terms_item_ID  > 0 )
	{	// Only if item ID is defined for terms page:
		set_param( 'p', $terms_item_ID );
		$c = 0; // Don't display comments

		$ItemCache = & get_ItemCache();
		$Item = & $ItemCache->get_by_ID( $p, false );

		if( is_logged_in() && $UserSettings->get( 'terms_accepted', $current_User->ID ) )
		{	// Display the message if current user already accepted the terms:
			$Messages->add( T_('You already accepted these terms.'), 'success' );
		}

		// Don't redirect to permanent url of the page:
		$redir = 'no';
	}
}

// Check if terms & conditions should be accepted by current user:
if( is_logged_in() && // Only for logged in users
    ! in_array( $disp, array( 'terms', 'help', 'msgform', 'activateinfo' ) ) && // Allow these pages
    $Settings->get( 'site_terms_enabled' ) && // Terms must be enabled
    ! $UserSettings->get( 'terms_accepted', $current_User->ID ) ) // If it was not accepted yet
{	// Current user didn't accept the terms yet:

	// Get ID of page with terms & conditions from global settings:
	$terms_page_ID = intval( $Settings->get( 'site_terms' ) );

	$ItemCache = & get_ItemCache();
	if( $terms_page_ID &&
	    $terms_Item = & $ItemCache->get_by_ID( $terms_page_ID, false, false ) &&
	    $terms_item_Blog = & $terms_Item->get_Blog() )
	{	// Redirect to view page with terms & conditions if it is defined correctly in settings:
		$Messages->add( T_('You need to accept the following before you can enter this site.'), 'note' );
		header_redirect( $terms_item_Blog->get( 'termsurl', array(
				'url_suffix' => 'redirect_to='.rawurlencode( $ReqURI ),
				'glue'       => '&',
			) ), 303 );
		// EXIT HERE
	}
}



/*
 * ______________________ DETERMINE WHICH SKIN TO USE FOR DISPLAY _______________________
 */

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
		if( ( $disp == 'posts' && $Blog->get_setting('feed_content') == 'none' ) ||
		    ( $disp == 'comments' && $Blog->get_setting('comment_feed_content') == 'none' ) )
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

$blog_skin_ID = $Blog->get_skin_ID();
if( !isset( $skin ) && !empty( $blog_skin_ID ) )	// Note: if $skin is set to '', then we want to do a "no skin" display
{ // Use default skin from the database
	$SkinCache = & get_SkinCache();
	$Skin = & $SkinCache->get_by_ID( $blog_skin_ID );
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

/*
 * _______________ Name request/transaction for performance logging ________________
 *
 * A this point, we know what the request/transaction is about and we can give it
 * a meaning ful name.
 */
$request_transaction_name = $Blog->shortname;

if( !empty($disp_detail) )
{
	$request_transaction_name .= ':'.$disp_detail;
}
elseif( !empty($disp) )
{
	$request_transaction_name .= ':'.$disp;
}

$Debuglog->add( 'Transaction name: '.$request_transaction_name, 'request' );
// Let's name the transaction for proper APM reporting:
apm_name_transaction( $request_transaction_name );


$Timer->pause( '_BLOG_MAIN.inc');
// LOG with APM:
$Timer->log_duration( '_BLOG_MAIN.inc' );


// Init global vars which may be required for any skin
skin_init_global_vars();


// Check if current user has acces to this blog
$Blog->check_access();


/*
 * _______________________________ READY TO DISPLAY _______________________________
 *
 * At this point $skin holds the name of the skin we want to use, or '' for no skin!
 */


// Trigger plugin event:
// fp> TODO: please doc with example of what this can be used for
$Plugins->trigger_event( 'BeforeBlogDisplay', array( 'skin' => $skin ) );

if( !empty( $skin ) )
{ // We want to display with a skin now:
	$Timer->resume( 'SKIN DISPLAY' );

	$Debuglog->add( 'Selected skin: '.$skin, 'skins' );

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

		if( $skin_provided_by_plugin = skin_provided_by_plugin( $skin ) )
		{
			$tmp_params = array( 'skin' => $skin );
			$Plugins->call_method( $skin_provided_by_plugin, 'DisplaySkin', $tmp_params );
		}
		else
		{
			// Path for the current skin:
			$ads_current_skin_path = $skins_path.$skin.'/';

			$disp_handlers = array(
					'403'                   => '403_forbidden.main.php',
					'404'                   => '404_not_found.main.php',
					'access_denied'         => 'access_denied.main.php',
					'access_requires_login' => 'access_requires_login.main.php',
					'activateinfo'          => 'activateinfo.main.php',
					'arcdir'                => 'arcdir.main.php',
					'catdir'                => 'catdir.main.php',
					'closeaccount'          => 'closeaccount.main.php',
					'comments'              => 'comments.main.php',
					'contacts'              => 'contacts.main.php',
					'download'              => 'download.main.php',
					'edit'                  => 'edit.main.php',
					'edit_comment'          => 'edit_comment.main.php',
					'feedback-popup'        => 'feedback_popup.main.php',
					'flagged'               => 'flagged.main.php',
					'front'                 => 'front.main.php',
					'help'                  => 'help.main.php',
					'login'                 => 'login.main.php',
					'lostpassword'          => 'lostpassword.main.php',
					'mediaidx'              => 'mediaidx.main.php',
					'messages'              => 'messages.main.php',
					'module_form'           => 'module_form.main.php',
					'msgform'               => 'msgform.main.php',
					'page'                  => 'page.main.php',
					'postidx'               => 'postidx.main.php',
					'posts'                 => 'posts.main.php',
					'profile'               => 'profile.main.php',
					'avatar'                => 'avatar.main.php',
					'pwdchange'             => 'pwdchange.main.php',
					'userprefs'             => 'userprefs.main.php',
					'subs'                  => 'subs.main.php',
					'visits'                => 'visits.main.php',
					'register'              => 'register.main.php',
					'search'                => 'search.main.php',
					'single'                => 'single.main.php',
					'sitemap'               => 'sitemap.main.php',
					'tags'                  => 'tags.main.php',
					'terms'                 => 'terms.main.php',
					'threads'               => 'threads.main.php',
					'contacts'              => 'contacts.main.php',
					'user'                  => 'user.main.php',
					'useritems'             => 'useritems.main.php',
					'usercomments'          => 'usercomments.main.php',
					'users'                 => 'users.main.php',
					// All others will default to index.main.php
				);

			// Handle custom templates defined by the Item Type:
			if( ! empty( $disp ) && ( $disp == 'single' || $disp == 'page' ) &&
			    ! empty( $Item ) && ( $ItemType = & $Item->get_ItemType() ) && $ItemType->get( 'template_name' ) != '' )
			{ // Get template name for the current Item if it is defined by its Item Type:
				$disp_handler_custom = $ItemType->get( 'template_name' ).'.main.php';

				if( file_exists( $disp_handler = $ads_current_skin_path.$disp_handler_custom ) )
				{ // Custom template is found in skin folder:
					$disp_handler_custom_found = true;
					$Debuglog->add('blog_main: include '.rel_path_to_base( $disp_handler ).' (custom for item type)', 'skins' );
				}
				else
				{ // Custom template not found:
					$disp_handler = NULL;
				}
			}

			if( empty( $disp_handler ) )
			{ // Set $disp_handler only if it is not defined above:
				if( ! empty( $disp_handlers[ $disp ] ) )
				{
					if( file_exists( $disp_handler = $ads_current_skin_path.$disp_handlers[ $disp ] ) )
					{ // The current skin has a customized page handler for this disp:
						$Debuglog->add('blog_main: include '.rel_path_to_base( $disp_handler ).' (custom to this skin)', 'skins' );
					}
					else
					{ // Fallback to the default "index" handler from the current skin dir:
						$disp_handler = $ads_current_skin_path.'index.main.php';
						$Debuglog->add('blog_main: include '.rel_path_to_base( $disp_handler ).' (default handler)', 'skins' );
					}
				}
				else
				{ // Use the default handler from the skins dir:
					$disp_handler = $ads_current_skin_path.'index.main.php';
					$Debuglog->add('blog_main: include '.rel_path_to_base( $disp_handler ).' (default index handler)', 'skins' );
				}
			}

			// CALL THE MAIN TEMPLATE NOW:
			require $disp_handler;
		}

		// Save collected cached data if needed:
		$PageCache->end_collect();
	}
	$Timer->pause( 'PageCache' );

	$Timer->pause( 'SKIN DISPLAY' );
	// LOG with APM:
	$Timer->log_duration( 'SKIN DISPLAY' );

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

?>