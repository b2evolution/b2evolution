<?php
/**
 * This file implements Template tags for use withing skins.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}.
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
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// DEBUG: (Turn switch on or off to log debug info for specified category)
$GLOBALS['debug_skins'] = true;


/**
 * Template tag. Initializes internal states for the most common skin displays.
 *
 * For more specific skins, this function should not be called and
 * equivalent code should be customized within the skin.
 *
 * @param string What are we going to display. Most of the time the global $disp should be passed.
 */
function skin_init( $disp )
{
	/**
	 * @var Blog
	 */
	global $Blog;

	/**
	 * @var Item
	 */
	global $Item;

	/**
	 * @var Skin
	 */
	global $Skin;

	global $robots_index;
	global $seo_page_type;

	global $redir, $ReqURL, $ReqURI, $m, $w, $preview;

	global $Chapter;
	global $Debuglog;

	/**
	 * @var ItemList2
	 */
	global $MainList;

	/**
	 * This will give more detail when $disp == 'posts'; otherwise it will have the same content as $disp
	 * @var string
	 */
	global $disp_detail, $Settings;

	global $Timer;

	global $Messages, $PageCache;

	$Timer->resume( 'skin_init' );

	if( empty($disp_detail) )
	{
		$disp_detail = $disp;
	}

	$Debuglog->add('skin_init: '.$disp, 'skins' );

	// This is the main template; it may be used to display very different things.
	// Do inits depending on current $disp:
	switch( $disp )
	{
		case 'posts':
		case 'single':
		case 'page':
		case 'feedback-popup':
		case 'search':
			// We need to load posts for this display:

			// Note: even if we request the same post as $Item above, the following will do more restrictions (dates, etc.)
			// Init the MainList object:
			init_MainList( $Blog->get_setting('posts_per_page') );

			// Init post navigation
			$post_navigation = $Skin->get_post_navigation();
			if( empty( $post_navigation ) )
			{
				$post_navigation = $Blog->get_setting( 'post_navigation' );
			}
			break;
	}

	// SEO stuff & redirects if necessary:
	$seo_page_type = NULL;
	switch( $disp )
	{
		// CONTENT PAGES:
		case 'single':
		case 'page':
			if( ( ! $preview ) && ( empty( $Item ) ) )
			{ // No Item, incorrect request and incorrect state of the application, a 404 redirect should have already happened
				debug_die( 'Invalid page URL!' );
			}

			init_ajax_forms( 'blog' ); // auto requires jQuery
			init_ratings_js( 'blog' );
			init_voting_comment_js( 'blog' );
			init_scrollwide_js( 'blog' ); // Add jQuery Wide Scroll plugin

			if( $disp == 'single' )
			{
				$seo_page_type = 'Single post page';
			}
			else
			{
				$seo_page_type = '"Page" page';
			}

			// Check if the post has 'redirected' status:
			if( ! $preview && $Item->status == 'redirected' && $redir == 'yes' )
			{	// $redir=no here allows to force a 'single post' URL for commenting
				// Redirect to the URL specified in the post:
				$Debuglog->add( 'Redirecting to post URL ['.$Item->url.'].' );
				header_redirect( $Item->url, true );
			}

			// Check if we want to redirect to a canonical URL for the post
			// Please document encountered problems.
			if( ! $preview
					&& (( $Blog->get_setting( 'canonical_item_urls' ) && $redir == 'yes' )
								|| $Blog->get_setting( 'relcanonical_item_urls' ) ) )
			{	// We want to redirect to the Item's canonical URL:

				$canonical_url = $Item->get_permanent_url( '', '', '&' );
				if( preg_match( '|[&?](page=\d+)|', $ReqURI, $page_param ) )
				{	// A certain post page has been requested, keep only this param and discard all others:
					$canonical_url = url_add_param( $canonical_url, $page_param[1], '&' );
				}
				if( preg_match( '|[&?](mode=quote&[qcp]+=\d+)|', $ReqURI, $page_param ) )
				{	// A quote of comment/post, keep only these params and discard all others:
					$canonical_url = url_add_param( $canonical_url, $page_param[1], '&' );
				}

				if( ! is_same_url( $ReqURL, $canonical_url ) )
				{	// The requested URL does not look like the canonical URL for this post...
					// url difference was resolved
					$url_resolved = false;
					// Check if the difference is because of an allowed post navigation param
					if( preg_match( '|[&?]cat=(\d+)|', $ReqURI, $cat_param ) )
					{ // A category post navigation param is set
						$extended_url = '';
						if( ( $post_navigation == 'same_category' ) && ( isset( $cat_param[1] ) ) )
						{ // navigatie through posts from the same category
							$category_ids = postcats_get_byID( $Item->ID );
							if( in_array($cat_param[1], $category_ids ) )
							{ // cat param is one of this Item categories
								$extended_url = $Item->add_navigation_param( $canonical_url, $post_navigation, $cat_param[1], '&' );
								// Set MainList navigation target to the requested category
								$MainList->nav_target = $cat_param[1];
							}
						}
						$url_resolved = is_same_url( $ReqURL, $extended_url );
					}

					if( !$url_resolved && $Blog->get_setting( 'canonical_item_urls' ) && $redir == 'yes' && ( ! $Item->check_cross_post_nav( 'auto', $Blog->ID ) ) )
					{	// REDIRECT TO THE CANONICAL URL:
						$Debuglog->add( 'Redirecting to canonical URL ['.$canonical_url.'].' );
						header_redirect( $canonical_url, true );
					}
					else
					{	// Use rel="canoncial":
						add_headline( '<link rel="canonical" href="'.$canonical_url.'" />' );
					}
					// EXITED.
				}
			}

			if( ! $MainList->result_num_rows )
			{	// There is nothing to display for this page, don't index it!
				$robots_index = false;
			}
			break;

		case 'posts':
			init_ajax_forms( 'blog' ); // auto requires jQuery
			init_scrollwide_js( 'blog' ); // Add jQuery Wide Scroll plugin
			// fp> if we add this here, we have to exetnd the inner if()
			// init_ratings_js( 'blog' );

			// Get list of active filters:
			$active_filters = $MainList->get_active_filters();

			if( !empty($active_filters) )
			{	// The current page is being filtered...

				if( array_diff( $active_filters, array( 'page' ) ) == array() )
				{ // This is just a follow "paged" page
					$disp_detail = 'posts-next';
					$seo_page_type = 'Next page';
					if( $Blog->get_setting( 'paged_noindex' ) )
					{	// We prefer robots not to index category pages:
						$robots_index = false;
					}
				}
				elseif( array_diff( $active_filters, array( 'cat_array', 'cat_modifier', 'cat_focus', 'posts', 'page' ) ) == array() )
				{ // This is a category page
					$disp_detail = 'posts-cat';
					$seo_page_type = 'Category page';
					if( $Blog->get_setting( 'chapter_noindex' ) )
					{	// We prefer robots not to index category pages:
						$robots_index = false;
					}

					global $cat, $catsel;
					if( empty( $catsel ) && preg_match( '~^[0-9]+$~', $cat ) )
					{	// We are on a single cat page:
						// NOTE: we must have selected EXACTLY ONE CATEGORY through the cat parameter
						// BUT: - this can resolve to including children
						//      - selecting exactly one cat through catsel[] is NOT OK since not equivalent (will exclude children)
						// echo 'SINGLE CAT PAGE';
						if( ( $Blog->get_setting( 'canonical_cat_urls' ) && $redir == 'yes' )
							|| $Blog->get_setting( 'relcanonical_cat_urls' ) )
						{ // Check if the URL was canonical:
							if( !isset( $Chapter ) )
							{
								$ChapterCache = & get_ChapterCache();
								/**
								 * @var Chapter
								 */
								$Chapter = & $ChapterCache->get_by_ID( $MainList->filters['cat_array'][0], false );
							}

							if( $Chapter )
							{
								if( $Chapter->parent_ID )
								{	// This is a sub-category page (i-e: not a level 1 category)
									$disp_detail = 'posts-subcat';
								}

								$canonical_url = $Chapter->get_permanent_url( NULL, NULL, $MainList->get_active_filter('page'), NULL, '&' );
								if( ! is_same_url($ReqURL, $canonical_url) )
								{	// fp> TODO: we're going to lose the additional params, it would be better to keep them...
									// fp> what additional params actually?
									if( $Blog->get_setting( 'canonical_cat_urls' ) && $redir == 'yes' )
									{	// REDIRECT TO THE CANONICAL URL:
										header_redirect( $canonical_url, true );
									}
									else
									{	// Use rel="canonical":
										add_headline( '<link rel="canonical" href="'.$canonical_url.'" />' );
									}
								}
							}
						}

						if( $post_navigation == 'same_category' )
						{ // Category is set and post navigation should go through the same category, set navigation target param
							$MainList->nav_target = $cat;
						}
					}
				}
				elseif( array_diff( $active_filters, array( 'tags', 'posts', 'page' ) ) == array() )
				{ // This is a tag page
					$disp_detail = 'posts-tag';
					$seo_page_type = 'Tag page';
					if( $Blog->get_setting( 'tag_noindex' ) )
					{	// We prefer robots not to index tag pages:
						$robots_index = false;
					}

					if( ( $Blog->get_setting( 'canonical_tag_urls' ) && $redir == 'yes' )
							|| $Blog->get_setting( 'relcanonical_tag_urls' ) )
					{ // Check if the URL was canonical:
						$canonical_url = $Blog->gen_tag_url( $MainList->get_active_filter('tags'), $MainList->get_active_filter('page'), '&' );
						if( ! is_same_url($ReqURL, $canonical_url) )
						{
							if( $Blog->get_setting( 'canonical_tag_urls' ) && $redir == 'yes' )
							{	// REDIRECT TO THE CANONICAL URL:
								header_redirect( $canonical_url, true );
							}
							else
							{	// Use rel="canoncial":
								add_headline( '<link rel="canonical" href="'.$canonical_url.'" />' );
							}
						}
					}
				}
				elseif( array_diff( $active_filters, array( 'ymdhms', 'week', 'posts', 'page' ) ) == array() ) // fp> added 'posts' 2009-05-19; can't remember why it's not in there
				{ // This is an archive page
					// echo 'archive page';
					$disp_detail = 'posts-date';
					$seo_page_type = 'Date archive page';

					if( ($Blog->get_setting( 'canonical_archive_urls' ) && $redir == 'yes' )
							|| $Blog->get_setting( 'relcanonical_archive_urls' ) )
					{ // Check if the URL was canonical:
						$canonical_url =  $Blog->gen_archive_url( substr( $m, 0, 4 ), substr( $m, 4, 2 ), substr( $m, 6, 2 ), $w, '&', $MainList->get_active_filter('page') );
						if( ! is_same_url($ReqURL, $canonical_url) )
						{
							if( $Blog->get_setting( 'canonical_archive_urls' ) && $redir == 'yes' )
							{	// REDIRECT TO THE CANONICAL URL:
								header_redirect( $canonical_url, true );
							}
							else
							{	// Use rel="canoncial":
								add_headline( '<link rel="canonical" href="'.$canonical_url.'" />' );
							}
						}
					}

					if( $Blog->get_setting( 'archive_noindex' ) )
					{	// We prefer robots not to index archive pages:
						$robots_index = false;
					}
				}
				else
				{	// Other filtered pages:
					// pre_dump( $active_filters );
					$disp_detail = 'posts-filtered';
					$seo_page_type = 'Other filtered page';
					if( $Blog->get_setting( 'filtered_noindex' ) )
					{	// We prefer robots not to index other filtered pages:
						$robots_index = false;
					}
				}
			}
			else
			{	// This is the default blog page
				$disp_detail = 'posts-default';
				$seo_page_type = 'Default page';
				if( ($Blog->get_setting( 'canonical_homepage' ) && $redir == 'yes' )
						|| $Blog->get_setting( 'relcanonical_homepage' ) )
				{ // Check if the URL was canonical:
					$canonical_url = $Blog->gen_blogurl();
					if( ! is_same_url($ReqURL, $canonical_url) )
					{
						if( $Blog->get_setting( 'canonical_homepage' ) && $redir == 'yes' )
						{	// REDIRECT TO THE CANONICAL URL:
							header_redirect( $canonical_url, true );
						}
						else
						{	// Use rel="canoncial":
							add_headline( '<link rel="canonical" href="'.$canonical_url.'" />' );
						}
					}
				}

				if( $Blog->get_setting( 'default_noindex' ) )
				{	// We prefer robots not to index archive pages:
					$robots_index = false;
				}
			}

			break;

		case 'search':
			$seo_page_type = 'Search page';
			if( $Blog->get_setting( 'filtered_noindex' ) )
			{	// We prefer robots not to index these pages:
				$robots_index = false;
			}
			break;

		// SPECIAL FEATURE PAGES:
		case 'feedback-popup':
			$seo_page_type = 'Comment popup';
			if( $Blog->get_setting( $disp.'_noindex' ) )
			{	// We prefer robots not to index these pages:
				$robots_index = false;
			}
			break;

		case 'arcdir':
			$seo_page_type = 'Date archive directory';
			if( $Blog->get_setting( $disp.'_noindex' ) )
			{	// We prefer robots not to index these pages:
				$robots_index = false;
			}
			break;

		case 'catdir':
			$seo_page_type = 'Category directory';
			if( $Blog->get_setting( $disp.'_noindex' ) )
			{	// We prefer robots not to index these pages:
				$robots_index = false;
			}
			break;

		case 'msgform':
			init_ajax_forms( 'blog' ); // auto requires jQuery

			$seo_page_type = 'Contact form';
			if( $Blog->get_setting( $disp.'_noindex' ) )
			{	// We prefer robots not to index these pages:
				$robots_index = false;
			}
			break;

		case 'messages':
		case 'contacts':
		case 'threads':
			init_results_js( 'blog' ); // Add functions to work with Results tables
			// just in case some robot would be logged in:
			$seo_page_type = 'Messaging module';
			$robots_index = false;
			break;

		case 'login':
			global $Plugins, $transmit_hashed_password;

			$seo_page_type = 'Login form';
			$robots_index = false;
			require_js( 'functions.js', 'blog' );

			$transmit_hashed_password = (bool)$Settings->get('js_passwd_hashing') && !(bool)$Plugins->trigger_event_first_true('LoginAttemptNeedsRawPassword');
			if( $transmit_hashed_password )
			{ // Include JS for client-side password hashing:
				require_js( 'sha1_md5.js', 'blog' );
			}
			break;

		case 'register':
			if( is_logged_in() )
			{ // If user is logged in the register form should not be displayed. In this case redirect to the blog home page.
				$Messages->add( T_( 'You are already logged in.' ), 'note' );
				header_redirect( $Blog->gen_blogurl(), false );
			}
			$seo_page_type = 'Register form';
			$robots_index = false;
			break;

		case 'lostpassword':
			if( is_logged_in() )
			{ // If user is logged in the lost password form should not be displayed. In this case redirect to the blog home page.
				$Messages->add( T_( 'You are already logged in.' ), 'note' );
				header_redirect( $Blog->gen_blogurl(), false );
			}
			$seo_page_type = 'Lost password form';
			$robots_index = false;
			break;

		case 'profile':
			global $rsc_url;
			require_css( $rsc_url.'css/jquery/smoothness/jquery-ui.css' );
			init_userfields_js( 'blog' );
		case 'avatar':
		case 'pwdchange':
		case 'userprefs':
		case 'subs':
			$seo_page_type = 'Special feature page';
			if( $Blog->get_setting( 'special_noindex' ) )
			{	// We prefer robots not to index these pages:
				$robots_index = false;
			}
			break;

		case 'users':
			$seo_page_type = 'Users list';
			$robots_index = false;
			global $rsc_url;
			require_css( $rsc_url.'css/jquery/smoothness/jquery-ui.css' );
			init_results_js( 'blog' ); // Add functions to work with Results tables
			break;

		case 'user':
			$seo_page_type = 'User display';
			if( is_logged_in() )
			{	// Used for combo_box contacts groups
				require_js( 'form_extensions.js', 'blog' );
			}
			break;

		case 'edit':
			init_datepicker_js( 'blog' );
			require_js( 'admin.js', 'blog' );
			init_inskin_editing( 'blog' );
			init_plugins_js( 'blog' );
			break;

		case 'edit_comment':
			init_plugins_js( 'blog' );
			break;

		case 'useritems':
		case 'usercomments':
			global $inc_path, $display_params, $viewed_User;

			// get user_ID because we want it in redirect_to in case we need to ask for login.
			$user_ID = param( 'user_ID', 'integer', true, true );
			if( empty( $user_ID ) )
			{
				bad_request_die( sprintf( T_('Parameter &laquo;%s&raquo; is required!'), 'user_ID' ) );
			}
			// set where to redirect in case of error
			$error_redirect_to = empty( $Blog ) ? $baseurl : $Blog->gen_blogurl();

			if( !is_logged_in() )
			{ // Redirect to the login page if not logged in and allow anonymous user setting is OFF
				$Messages->add( T_('You must log in to view this user profile.') );
				header_redirect( get_login_url( 'cannot see user' ), 302 );
				// will have exited
			}

			if( is_logged_in() && ( !check_user_status( 'can_view_user', $user_ID ) ) )
			{ // user is logged in, but his/her status doesn't permit to view user profile
				if( check_user_status( 'can_be_validated' ) )
				{ // user is logged in but his/her account is not active yet
					// Redirect to the account activation page
					$Messages->add( T_('You must activate your account before you can view this user profile. <b>See below:</b>') );
					header_redirect( get_activate_info_url(), 302 );
					// will have exited
				}

				$Messages->add( T_('Your account status currently does not permit to view this user profile.') );
				header_redirect( $error_redirect_to, 302 );
				// will have exited
			}

			if( !empty( $user_ID ) )
			{
				$UserCache = & get_UserCache();
				$viewed_User = $UserCache->get_by_ID( $user_ID, false );

				if( empty( $viewed_User ) )
				{
					$Messages->add( T_('The requested user does not exist!') );
					header_redirect( $error_redirect_to );
					// will have exited
				}

				if( $viewed_User->check_status( 'is_closed' ) )
				{
					$Messages->add( T_('The requested user account is closed!') );
					header_redirect( $error_redirect_to );
					// will have exited
				}
			}

			// Require results.css to display thread query results in a table
			require_css( 'results.css' ); // Results/tables styles

			// Require functions.js to show/hide a panel with filters
			require_js( 'functions.js', 'blog' );
			// Include this file to expand/collapse the filters panel when JavaScript is disabled
			require_once $inc_path.'_filters.inc.php';

			$display_params = !empty( $Skin ) ? $Skin->get_template( 'Results' ) : NULL;

			if( $disp == 'useritems' )
			{ // Init items list
				global $user_ItemList;

				$param_prefix = 'useritems_';
				$page = param( $param_prefix.'paged', 'integer', 1 );
				$orderby = param( $param_prefix.'orderby', 'string', $Blog->get_setting('orderby') );
				$order = param( $param_prefix.'order', 'string', $Blog->get_setting('orderdir') );
				$useritems_Blog = NULL;
				$user_ItemList = new ItemList2( $useritems_Blog, NULL, NULL, NULL, 'ItemCache', $param_prefix );
				$user_ItemList->load_from_Request();
				$user_ItemList->set_filters( array(
						'page'          => $page,
						'authors'       => $user_ID,
						'orderby'       => str_replace( $param_prefix, '', $orderby ),
						'order'         => str_replace( $param_prefix, '', $order ),
					) );
				$user_ItemList->query();
			}
			else // $disp == 'usercomments'
			{ // Init comments list
				global $user_CommentList;

				$param_prefix = 'usercmts_';
				$page = param( $param_prefix.'paged', 'integer', 1 );
				$orderby = param( $param_prefix.'orderby', 'string', 'date' );
				$order = param( $param_prefix.'order', 'string', $Blog->get_setting('orderdir') );

				$user_CommentList = new CommentList2( NULL, NULL, 'CommentCache', $param_prefix );
				$user_CommentList->load_from_Request();
				$user_CommentList->set_filters( array(
						'page'          => $page,
						'author_IDs'    => $user_ID,
						'orderby'       => str_replace( $param_prefix, '', $orderby ),
						'order'         => str_replace( $param_prefix, '', $order ),
					) );
				$user_CommentList->query();
			}
			break;

		case 'comments':
			if( !$Blog->get_setting( 'comments_latest' ) )
			{ // If latest comments page is disabled - Display 404 page with error message
				$Messages->add( T_('This feature is disabled.'), 'error' );
				global $disp;
				$disp = '404';
			}
			else
			{
				break;
			}

		case '404':
			// We have a 404 unresolved content error
			// How do we want do deal with it?
			skin_404_header();
			// This MAY or MAY not have exited -- will exit on 30x redirect, otherwise will return here.
			// Just in case some dumb robot needs extra directives on this:
			$robots_index = false;
			break;
	}

	if( !empty( $_SERVER['HTTP_USER_AGENT'] ) )
	{	// Detect IE browser version
		preg_match( '/msie (\d+)/i', $_SERVER['HTTP_USER_AGENT'], $browser_ie );
		if( count( $browser_ie ) == 2 && $browser_ie[1] < 7 )
		{	// IE < 7
			require_css( 'ie6.css', 'relative' );
			$Messages->add( T_('Your web browser is too old. For this site to work correctly, we recommend you use a more recent browser.'), 'note' );
		}
	}

	// dummy var for backward compatibility with versions < 2.4.1 -- prevents "Undefined variable"
	global $global_Cache, $credit_links;
	$credit_links = $global_Cache->get( 'creds' );

	$Timer->pause( 'skin_init' );

	// Check if user is logged in with a not active account, and display an error message if required
	check_allow_disp( $disp );

	// initialize Blog enabled widgets, before displaying anything
	init_blog_widgets( $Blog->ID );

	// Initialize displaying....
	$Timer->start( 'Skin:display_init' );
	$Skin->display_init();
	$Timer->pause( 'Skin:display_init' );

	// Send default headers:
	// See comments inside of this function:
	headers_content_mightcache( 'text/html' );		// In most situations, you do NOT want to cache dynamic content!
	// Never allow Messages to be cached!
	if( $Messages->count() && ( !empty( $PageCache ) ) )
	{ // Abort PageCache collect
		$PageCache->abort_collect();
	}
}


/**
 * Initialize skin for AJAX request
 *
 * @param string Skin name
 * @param string What are we going to display. Most of the time the global $disp should be passed.
 */
function skin_init_ajax( $skin_name, $disp )
{
	if( is_ajax_content() )
	{	// AJAX request
		if( empty( $skin_name ) )
		{	// Don't initialize without skin name
			return false;
		}

		global $ads_current_skin_path, $skins_path;

		// Init path for current skin
		$ads_current_skin_path = $skins_path.$skin_name.'/';

		// This is the main template; it may be used to display very different things.
		// Do inits depending on current $disp:
		skin_init( $disp );
	}

	return true;
}


/**
 * Tells if we are on the default blog page
 *
 * @return boolean
 */
function is_default_page()
{
	global $disp_detail;
	return ($disp_detail == 'posts-default' );
}


/**
 * Template tag. Include a sub-template at the current position
 *
 */
function skin_include( $template_name, $params = array() )
{
	if( is_ajax_content( $template_name ) )
	{	// When we request ajax content for results table we need to hide wrapper data (header, footer & etc)
		return;
	}

	global $skins_path, $ads_current_skin_path, $disp;

	// Globals that may be needed by the template:
	global $Blog, $MainList, $Item;
	global $Plugins, $Skin;
	global $current_User, $Hit, $Session, $Settings;
	global $skin_url, $htsrv_url, $htsrv_url_sensitive;
	global $samedomain_htsrv_url, $secure_htsrv_url;
	global $credit_links, $skin_links, $francois_links, $fplanque_links, $skinfaktory_links;
	/**
	* @var Log
	*/
	global $Debuglog;
	global $Timer;

	$timer_name = 'skin_include('.$template_name.')';
	$Timer->resume( $timer_name );

	if( $template_name == '$disp$' )
	{ // This is a special case.
		// We are going to include a template based on $disp:

		// Default display handlers:
		$disp_handlers = array(
				'disp_404'            => '_404_not_found.disp.php',
				'disp_arcdir'         => '_arcdir.disp.php',
				'disp_catdir'         => '_catdir.disp.php',
				'disp_comments'       => '_comments.disp.php',
				'disp_feedback-popup' => '_feedback_popup.disp.php',
				'disp_help'           => '_help.disp.php',
				'disp_login'          => '_login.disp.php',
				'disp_register'       => '_register.disp.php',
				'disp_activateinfo'   => '_activateinfo.disp.php',
				'disp_lostpassword'   => '_lostpassword.disp.php',
				'disp_mediaidx'       => '_mediaidx.disp.php',
				'disp_msgform'        => '_msgform.disp.php',
				'disp_threads'        => '_threads.disp.php',
				'disp_contacts'       => '_threads.disp.php',
				'disp_messages'       => '_messages.disp.php',
				'disp_page'           => '_page.disp.php',
				'disp_postidx'        => '_postidx.disp.php',
				'disp_posts'          => '_posts.disp.php',
				'disp_profile'        => '_profile.disp.php',
				'disp_avatar'         => '_profile.disp.php',
				'disp_pwdchange'      => '_profile.disp.php',
				'disp_userprefs'      => '_profile.disp.php',
				'disp_subs'           => '_profile.disp.php',
				'disp_search'         => '_search.disp.php',
				'disp_single'         => '_single.disp.php',
				'disp_sitemap'        => '_sitemap.disp.php',
				'disp_user'           => '_user.disp.php',
				'disp_users'          => '_users.disp.php',
				'disp_edit'           => '_edit.disp.php',
				'disp_edit_comment'   => '_edit_comment.disp.php',
				'disp_closeaccount'   => '_closeaccount.disp.php',
				'disp_module_form'    => '_module_form.disp.php',
				'disp_useritems'      => '_useritems.disp.php',
				'disp_usercomments'   => '_usercomments.disp.php',
			);

		// Add plugin disp handlers:
		if( $disp_Plugins = $Plugins->get_list_by_event( 'GetHandledDispModes' ) )
		{
			foreach( $disp_Plugins as $disp_Plugin )
			{ // Go through whole list of plugins providing disps
				if( $plugin_modes = $Plugins->call_method( $disp_Plugin->ID, 'GetHandledDispModes', $disp_handlers ) )
				{ // plugin handles some custom disp modes
					foreach( $plugin_modes as $plugin_mode )
					{
						$disp_handlers[$plugin_mode] = '#'.$disp_Plugin->ID;
					}
				}
			}
		}

		// Allow skin overrides as well as additional disp modes (This can be used in the famou shopping cart scenario...)
		$disp_handlers = array_merge( $disp_handlers, $params );

		if( !isset( $disp_handlers['disp_'.$disp] ) )
		{
			global $Messages;
			$Messages->add( sprintf( 'Unhandled disp type [%s]', htmlspecialchars( $disp ) ) );
			$Messages->display();
			$Timer->pause( $timer_name );
			$disp = '404';
		}

		$template_name = $disp_handlers['disp_'.$disp];

		if( empty( $template_name ) )
		{	// The caller asked not to display this handler
			$Timer->pause( $timer_name );
			return;
		}

	}

	$disp_handled = false;

	if( $template_name[0] == '#' )
	{	// This disp mode is handled by a plugin:
		$plug_ID = substr( $template_name, 1 );
		$disp_params = array( 'disp' => $disp );
		$Plugins->call_method( $plug_ID, 'HandleDispMode', $disp_params );
		$disp_handled = true;
	}

	elseif( file_exists( $ads_current_skin_path.$template_name ) )
	{	// The skin has a customized handler, use that one instead:

		$file = $ads_current_skin_path.$template_name;
		$Debuglog->add('skin_include ('.($Item ? 'Item #'.$Item->ID : '-').'): '.rel_path_to_base($file), 'skins');
		require $file;
		$disp_handled = true;
	}

	elseif( file_exists( $skins_path.$template_name ) )
	{	// Use the default template:
		global $Debuglog;
		$file = $skins_path.$template_name;
		$Debuglog->add('skin_include ('.($Item ? 'Item #'.$Item->ID : '-').'): '.rel_path_to_base($file), 'skins');
		require $file;
		$disp_handled = true;
	}

	if( ! $disp_handled )
	{ // nothing handled the disp mode
		printf( '<div class="skin_error">Sub template [%s] not found.</div>', $template_name );
		if( !empty($current_User) && $current_User->level == 10 )
		{
			printf( '<div class="skin_error">User level 10 help info: [%s]</div>', $ads_current_skin_path.$template_name );
		}
	}

	$Timer->pause( $timer_name );
}


/**
 * Template tag. Output HTML base tag to current skin.
 *
 * This is needed for relative css and img includes.
 */
function skin_base_tag()
{
	global $skins_url, $skin, $Blog, $disp;

	if( ! empty( $Blog ) )
	{	// We are displaying a blog:
		if( ! empty( $skin ) )
		{	// We are using a skin:
			$base_href = $Blog->get_local_skins_url().$skin.'/';
		}
		else
		{ // No skin used:
			$base_href = $Blog->gen_baseurl();
		}
	}
	else
	{	// We are displaying a general page that is not specific to a blog:
		global $baseurl;
		$base_href = $baseurl;
	}

	$target = NULL;
	if( !empty($disp) && strpos( $disp, '-popup' ) )
	{	// We are (normally) displaying in a popup window, we need most links to open a new window!
		$target = '_blank';
	}

	base_tag( $base_href, $target );
}


/**
 * Template tag
 *
 * Note for future mods: we do NOT want to repeat identical content on multiple pages.
 */
function skin_description_tag()
{
	global $Blog, $disp, $disp_detail, $MainList, $Chapter;

	$r = '';

	if( is_default_page() )
	{
		if( ! empty( $Blog ) )
		{	// Description for the blog:
			$r = $Blog->get( 'shortdesc' );
		}
	}
	elseif( $disp_detail == 'posts-cat' || $disp_detail == 'posts-subcat' )
	{
		if( $Blog->get_setting( 'categories_meta_description' ) && ( ! empty( $Chapter ) ) )
		{
			$r = $Chapter->get( 'description' );
		}
	}
	elseif( in_array( $disp, array( 'single', 'page' ) ) )
	{	// custom desc for the current single post:
		$Item = & $MainList->get_by_idx( 0 );
		if( is_null( $Item ) )
		{	// This is not an object (happens on an invalid request):
			return;
		}

		$r = $Item->get_metadesc();

		if( empty( $r )&& $Blog->get_setting( 'excerpts_meta_description' ) )
		{	// Fall back to excerpt for the current single post:
			// Replace line breaks with single space
			$r = preg_replace( '|[\r\n]+|', ' ', $Item->get('excerpt') );
		}
	}

	if( !empty($r) )
	{
		echo '<meta name="description" content="'.format_to_output( $r, 'htmlattr' )."\" />\n";
	}
}


/**
 * Template tag
 *
 * Note for future mods: we do NOT want to repeat identical content on multiple pages.
 */
function skin_keywords_tag()
{
	global $Blog, $disp, $MainList;

	$r = '';

	if( is_default_page() )
	{
		if( !empty($Blog) )
		{
			$r = $Blog->get('keywords');
		}
	}
	elseif( in_array( $disp, array( 'single', 'page' ) ) )
	{	// custom keywords for the current single post:
		$Item = & $MainList->get_by_idx( 0 );
		if( is_null( $Item ) )
		{	// This is not an object (happens on an invalid request):
			return;
		}

		$r = $Item->get_custom_headers();


		if( empty( $r ) && $Blog->get_setting( 'tags_meta_keywords' ) )
		{	// Fall back to tags for the current single post:
			$r = implode( ', ', $Item->get_tags() );
		}

	}

	if( !empty($r) )
	{
		echo '<meta name="keywords" content="'.format_to_output( $r, 'htmlattr' )."\" />\n";
	}
}


/**
 * Sends the desired HTTP response header in case of a "404".
 */
function skin_404_header()
{
	global $Blog;

	// We have a 404 unresolved content error
	// How do we want do deal with it?
	switch( $resp_code = $Blog->get_setting( '404_response' ) )
	{
		case '404':
			header_http_response('404 Not Found');
			break;

		case '410':
			header_http_response('410 Gone');
			break;

		case '301':
		case '302':
		case '303':
			// Redirect to home page:
			header_redirect( $Blog->get('url'), intval($resp_code) );
			// THIS WILL EXIT!
			break;

		default:
			// Will result in a 200 OK
	}
}


/**
 * Template tag. Output content-type header
 * For backward compatibility
 *
 * @see skin_content_meta()
 *
 * @param string content-type; override for RSS feeds
 */
function skin_content_header( $type = 'text/html' )
{
	header_content_type( $type );
}


/**
 * Template tag. Output content-type http_equiv meta tag
 *
 * @see skin_content_header()
 *
 * @param string content-type; override for RSS feeds
 */
function skin_content_meta( $type = 'text/html' )
{
	global $io_charset;

	echo '<meta http-equiv="Content-Type" content="'.$type.'; charset='.$io_charset.'" />'."\n";
}


/**
 * Template tag. Display a Widget.
 *
 * This load the widget class, instantiates it, and displays it.
 *
 * @param array
 */
function skin_widget( $params )
{
	global $inc_path;

	if( empty( $params['widget'] ) )
	{
		echo 'No widget code provided!';
		return false;
	}

	$widget_code = $params['widget'];
	unset( $params['widget'] );

	if( ! file_exists( $inc_path.'widgets/widgets/_'.$widget_code.'.widget.php' ) )
	{	// For some reason, that widget doesn't seem to exist... (any more?)
		echo "Invalid widget code provided [$widget_code]!";
		return false;
	}
	require_once $inc_path.'widgets/widgets/_'.$widget_code.'.widget.php';

	$widget_classname = $widget_code.'_Widget';

	/**
	 * @var ComponentWidget
	 */
	$Widget = new $widget_classname();	// COPY !!

	return $Widget->display( $params );
}


/**
 * Display a container
 *
 * @param string
 * @param array
 */
function skin_container( $sco_name, $params = array() )
{
	global $Skin;

	$Skin->container( $sco_name, $params );
}


/**
 * Install a skin
 *
 * @todo do not install if skin doesn't exist. Important for upgrade. Need to NOT fail if ZERO skins installed though :/
 *
 * @param string Skin folder
 * @return Skin
 */
function & skin_install( $skin_folder )
{
	$SkinCache = & get_SkinCache();
	$Skin = & $SkinCache->new_obj( NULL, $skin_folder );

	$Skin->install();

	return $Skin;
}


/**
 * Checks if a skin is provided by a plugin.
 *
 * Used by front-end.
 *
 * @uses Plugin::GetProvidedSkins()
 * @return false|integer False in case no plugin provides the skin or ID of the first plugin that provides it.
 */
function skin_provided_by_plugin( $name )
{
	static $plugin_skins;
	if( ! isset($plugin_skins) || ! isset($plugin_skins[$name]) )
	{
		global $Plugins;

		$plugin_r = $Plugins->trigger_event_first_return('GetProvidedSkins', NULL, array('in_array'=>$name));
		if( $plugin_r )
		{
			$plugin_skins[$name] = $plugin_r['plugin_ID'];
		}
		else
		{
			$plugin_skins[$name] = false;
		}
	}

	return $plugin_skins[$name];
}


/**
 * Checks if a skin exists. This can either be a regular skin directory
 * or can be in the list {@link Plugin::GetProvidedSkins()}.
 *
 * Used by front-end.
 *
 * @param skin name (directory name)
 * @return boolean true is exists, false if not
 */
function skin_exists( $name, $filename = 'index.main.php' )
{
	global $skins_path;

	if( skin_file_exists( $name, $filename ) )
	{
		return true;
	}

	// Check list provided by plugins:
	if( skin_provided_by_plugin($name) )
	{
		return true;
	}

	return false;
}


/**
 * Checks if a specific file exists for a skin.
 *
 * @param skin name (directory name)
 * @param file name
 * @return boolean true is exists, false if not
 */
function skin_file_exists( $name, $filename = 'index.main.php' )
{
	global $skins_path;

	if( is_readable( $skins_path.$name.'/'.$filename ) )
	{
		return true;
	}

	return false;
}


/**
 * Check if a skin is installed.
 *
 * This can either be a regular skin or a skin provided by a plugin.
 *
 * @param Skin name (directory name)
 * @return boolean True if the skin is installed, false otherwise.
 */
function skin_installed( $name )
{
	$SkinCache = & get_SkinCache();

	if( skin_provided_by_plugin( $name ) || $SkinCache->get_by_folder( $name, false ) )
	{
		return true;
	}

	return false;
}


/**
 * Display a blog skin setting fieldset which can be normal, mobile or tablet ( used on _coll_skin_settings.form.php )
 *
 * @param object Form
 * @param integer skin ID
 * @param array display params
 */
function display_skin_fieldset( & $Form, $skin_ID, $display_params )
{
	$Form->begin_fieldset( $display_params[ 'fieldset_title' ].get_manual_link('blog_skin_settings').' '.$display_params[ 'fieldset_links' ] );

	if( !$skin_ID )
	{ // The skin ID is empty use the same as normal skin ID
		echo '<div style="font-weight:bold;padding:0.5ex;">'.T_('Same as normal skin.').'</div>';
	}
	else
	{
		$SkinCache = & get_SkinCache();
		$edited_Skin = $SkinCache->get_by_ID( $skin_ID );

		echo '<div style="float:left;width:30%">';
		$disp_params = array( 'skinshot_class' => 'coll_settings_skinshot' );
		Skin::disp_skinshot( $edited_Skin->folder, $edited_Skin->name, $disp_params );

		$Form->info( T_('Skin name'), $edited_Skin->name );

		if( isset($edited_Skin->version) )
		{
				$Form->info( T_('Skin version'), $edited_Skin->version );
		}

		$Form->info( T_('Skin type'), $edited_Skin->type );

		if( $skin_containers = $edited_Skin->get_containers() )
		{
			$container_ul = '<ul><li>'.implode( '</li><li>', $skin_containers ).'</li></ul>';
		}
		else
		{
			$container_ul = '-';
		}
		$Form->info( T_('Containers'), $container_ul );

		echo '</div>';
		echo '<div style="margin-left:30%">';

		$skin_params = $edited_Skin->get_param_definitions( $tmp_params = array('for_editing'=>true) );

		if( empty($skin_params) )
		{	// Advertise this feature!!
			echo '<p>'.T_('This skin does not provide any configurable settings.').'</p>';
		}
		else
		{
			load_funcs( 'plugins/_plugin.funcs.php' );

			// Loop through all widget params:
			foreach( $skin_params as $l_name => $l_meta )
			{
				// Display field:
				autoform_display_field( $l_name, $l_meta, $Form, 'Skin', $edited_Skin );
			}
		}

		echo '</div>';
	}

	$Form->end_fieldset();
}

?>
