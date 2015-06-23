<?php
/**
 * This file implements Template tags for use withing skins.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
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

	global $Session;

	global $search_result_loaded;

	$Timer->resume( 'skin_init' );

	if( empty($disp_detail) )
	{
		$disp_detail = $disp;
	}

	$Debuglog->add('skin_init: $disp='.$disp, 'skins' );

	// This is the main template; it may be used to display very different things.
	// Do inits depending on current $disp:
	switch( $disp )
	{
		case 'front':
		case 'posts':
		case 'single':
		case 'page':
		case 'download':
		case 'feedback-popup':
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

		case 'search':
			// Searching post, comments and categories
			load_funcs( 'collections/_search.funcs.php' );

			$search_keywords = param( 's', 'string', '', true );
			$search_params = $Session->get( 'search_params' );
			$search_result = $Session->get( 'search_result' );
			$search_result_loaded = false;
			if( empty( $search_params ) || ( $search_params['search_keywords'] != $search_keywords )
				|| ( $search_params['search_blog'] != $Blog->ID ) || ( $search_result === NULL ) )
			{ // this is a new search
				$search_params = array( 'search_keywords' => $search_keywords, 'search_blog' => $Blog->ID );
				$search_result = score_search_result( $search_keywords );
				$Session->set( 'search_params', $search_params );
				$Session->set( 'search_result', $search_result );
				$search_result_loaded = true;
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
			init_plugins_js( 'blog', $Skin->get_template( 'tooltip_plugin' ) );
			init_autocomplete_usernames_js( 'blog' );
			if( $Blog->get_setting( 'allow_rating_comment_helpfulness' ) )
			{ // Load jquery UI to animate background color on change comment status or on vote
				require_js( '#jqueryUI#', 'blog' );
			}

			if( $disp == 'single' )
			{
				$seo_page_type = 'Single post page';
			}
			else
			{
				$seo_page_type = '"Page" page';
			}

			if( ! $preview )
			{ // Check if item has a goal to insert a hit into DB
				$Item->check_goal();
			}

			// Check if the post has 'redirected' status:
			if( ! $preview && $Item->status == 'redirected' && $redir == 'yes' )
			{	// $redir=no here allows to force a 'single post' URL for commenting
				// Redirect to the URL specified in the post:
				$Debuglog->add( 'Redirecting to post URL ['.$Item->url.'].' );
				header_redirect( $Item->url, true, true );
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
							if( in_array( $cat_param[1], $category_ids ) )
							{ // cat param is one of this Item categories
								$extended_url = $Item->add_navigation_param( $canonical_url, $post_navigation, $cat_param[1], '&' );
								// Set MainList navigation target to the requested category
								$MainList->nav_target = $cat_param[1];
							}
						}
						$url_resolved = is_same_url( $ReqURL, $extended_url );
					}
					if( preg_match( '|[&?]tag=([^&A-Z]+)|', $ReqURI, $tag_param ) )
					{ // A tag post navigation param is set
						$extended_url = '';
						if( ( $post_navigation == 'same_tag' ) && ( isset( $tag_param[1] ) ) )
						{ // navigatie through posts from the same tag
							$tag_names = $Item->get_tags();
							if( in_array( $tag_param[1], $tag_names ) )
							{ // tag param is one of this Item tags
								$extended_url = $Item->add_navigation_param( $canonical_url, $post_navigation, $tag_param[1], '&' );
								// Set MainList navigation target to the requested tag
								$MainList->nav_target = $tag_param[1];
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

		case 'download':
			if( empty( $Item ) )
			{ // No Item, incorrect request and incorrect state of the application, a 404 redirect should have already happened
				debug_die( 'Invalid page URL!' );
			}

			$download_link_ID = param( 'download', 'integer', 0 );

			// Check if we can allow to download the selected file
			$LinkCache = & get_LinkCache();
			if( ! (
			    ( $download_Link = & $LinkCache->get_by_ID( $download_link_ID, false, false ) ) && // Link exists in DB
			    ( $LinkItem = & $download_Link->get_LinkOwner() ) && // Link has an owner object
			    ( $LinkItem->Item && $LinkItem->Item->ID == $Item->ID ) && // Link is attached to this Item
			    ( $download_File = & $download_Link->get_File() ) && // Link has a correct File object
			    ( $download_File->exists() ) // File exists on the disk
			  ) )
			{ // Bad request, Redirect to Item permanent url
				$Messages->add( T_( 'The requested file is not available for download.' ), 'error' );
				$canonical_url = $Item->get_permanent_url( '', '', '&' );
				$Debuglog->add( 'Redirecting to canonical URL ['.$canonical_url.'].' );
				header_redirect( $canonical_url, true );
			}

			// Save the downloading Link to the global vars
			$GLOBALS['download_Link'] = & $download_Link;
			// Save global $Item to $download_Item, because $Item can be rewritten by function get_featured_Item() in some skins
			$GLOBALS['download_Item'] = & $Item;

			init_ajax_forms( 'blog' ); // auto requires jQuery

			// Initialize JavaScript to download file after X seconds
			add_js_headline( '
jQuery( document ).ready( function ()
{
	jQuery( "#download_timer_js" ).show();
} );

var b2evo_download_timer = '.intval( $Blog->get_setting( 'download_delay' ) ).';
var downloadInterval = setInterval( function()
{
	jQuery( "#download_timer" ).html( b2evo_download_timer );
	if( b2evo_download_timer == 0 )
	{ // Stop timer and download a file
		clearInterval( downloadInterval );
		jQuery( "#download_help_url" ).show();
	}
	b2evo_download_timer--;
}, 1000 );' );

			// Use meta tag to download file when JavaScript is NOT enabled
			add_headline( '<meta http-equiv="refresh" content="'.intval( $Blog->get_setting( 'download_delay' ) )
				.'; url='.$download_Link->get_download_url( array( 'type' => 'action' ) ).'" />' );

			$seo_page_type = 'Download page';

			if( $Blog->get_setting( $disp.'_noindex' ) )
			{ // We prefer robots not to index these pages:
				$robots_index = false;
			}
			break;

		case 'posts':
			init_ajax_forms( 'blog' ); // auto requires jQuery
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
							else
							{ // If the requested chapter was not found display 404 page
								$Messages->add( T_('The requested chapter was not found') );
								global $disp;
								$disp = '404';
								break;
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

					$tag = $MainList->get_active_filter('tags');
					if( $post_navigation == 'same_tag' && !empty( $tag ) )
					{ // Tag is set and post navigation should go through the same tag, set navigation target param
						$MainList->nav_target = $tag;
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
			elseif( $Blog->get_setting('front_disp') == 'posts' )
			{	// This is the default blog page only if the 'front_disp' is set to 'posts'
				$disp_detail = 'posts-default';
				$seo_page_type = 'Default page';
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
			if( $msg_Blog = & get_setting_Blog( 'msg_blog_ID' ) && $Blog->ID != $msg_Blog->ID )
			{ // Redirect to special blog for messaging actions if it is defined in general settings
				header_redirect( $msg_Blog->get( 'msgformurl', array( 'glue' => '&' ) ) );
			}

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
			if( $msg_Blog = & get_setting_Blog( 'msg_blog_ID' ) && $Blog->ID != $msg_Blog->ID )
			{ // Redirect to special blog for messaging actions if it is defined in general settings
				header_redirect( $msg_Blog->get( $disp.'url', array( 'glue' => '&' ) ) );
			}

			init_results_js( 'blog' ); // Add functions to work with Results tables
			// just in case some robot would be logged in:
			$seo_page_type = 'Messaging module';
			$robots_index = false;

			// Display messages depending on user email status
			display_user_email_status_message();
			break;

		case 'login':
			global $Plugins, $transmit_hashed_password;

			if( $login_Blog = & get_setting_Blog( 'login_blog_ID' ) && $Blog->ID != $login_Blog->ID )
			{ // Redirect to special blog for login/register actions if it is defined in general settings
				header_redirect( $login_Blog->get( 'loginurl', array( 'glue' => '&' ) ) );
			}

			$seo_page_type = 'Login form';
			$robots_index = false;
			require_js( 'functions.js', 'blog' );

			$transmit_hashed_password = (bool)$Settings->get('js_passwd_hashing') && !(bool)$Plugins->trigger_event_first_true('LoginAttemptNeedsRawPassword');
			if( $transmit_hashed_password )
			{ // Include JS for client-side password hashing:
				require_js( 'build/sha1_md5.bmin.js', 'blog' );
			}
			break;

		case 'register':
			if( is_logged_in() )
			{ // If user is logged in the register form should not be displayed. In this case redirect to the blog home page.
				$Messages->add( T_( 'You are already logged in.' ), 'note' );
				header_redirect( $Blog->gen_blogurl(), false );
			}

			if( $login_Blog = & get_setting_Blog( 'login_blog_ID' ) && $Blog->ID != $login_Blog->ID )
			{ // Redirect to special blog for login/register actions if it is defined in general settings
				header_redirect( $login_Blog->get( 'registerurl', array( 'glue' => '&' ) ) );
			}

			$seo_page_type = 'Register form';
			$robots_index = false;

			// Check invitation code if it exists and registration is enabled
			global $display_invitation;
			$display_invitation = check_invitation_code();
			break;

		case 'lostpassword':
			if( is_logged_in() )
			{ // If user is logged in the lost password form should not be displayed. In this case redirect to the blog home page.
				$Messages->add( T_( 'You are already logged in.' ), 'note' );
				header_redirect( $Blog->gen_blogurl(), false );
			}

			if( $login_Blog = & get_setting_Blog( 'login_blog_ID' ) && $Blog->ID != $login_Blog->ID )
			{ // Redirect to special blog for login/register actions if it is defined in general settings
				header_redirect( $login_Blog->get( 'lostpasswordurl', array( 'glue' => '&' ) ) );
			}

			$seo_page_type = 'Lost password form';
			$robots_index = false;
			break;

		case 'activateinfo':
			if( $login_Blog = & get_setting_Blog( 'login_blog_ID' ) && $Blog->ID != $login_Blog->ID )
			{ // Redirect to special blog for login/register actions if it is defined in general settings
				header_redirect( $login_Blog->get( 'activateinfourl', array( 'glue' => '&' ) ) );
			}
			break;

		case 'profile':
			init_userfields_js( 'blog', $Skin->get_template( 'tooltip_plugin' ) );
		case 'avatar':
			$action = param_action();
			if( $action == 'crop' && is_logged_in() )
			{ // Initialize data for crop action
				global $current_User, $cropped_File;
				$file_ID = param( 'file_ID', 'integer' );
				if( $cropped_File = $current_User->get_File_by_ID( $file_ID, $error_code ) )
				{ // Current user can crops this file
					require_js( '#jquery#', 'blog' );
					require_js( '#jcrop#', 'blog' );
					require_css( '#jcrop_css#', 'blog' );
				}
				else
				{ // Wrong file for cropping
					unset( $action );
				}
			}
		case 'pwdchange':
		case 'userprefs':
		case 'subs':
			$seo_page_type = 'Special feature page';
			if( $Blog->get_setting( 'special_noindex' ) )
			{	// We prefer robots not to index these pages:
				$robots_index = false;
			}

			// Display messages depending on user email status
			display_user_email_status_message();
			break;

		case 'users':
			$seo_page_type = 'Users list';
			$robots_index = false;
			require_js( '#jqueryUI#', 'blog' );
			require_css( '#jqueryUI_css#', 'blog' );
			init_results_js( 'blog' ); // Add functions to work with Results tables
			break;

		case 'user':
			$seo_page_type = 'User display';
			if( is_logged_in() )
			{	// Used for combo_box contacts groups
				require_js( 'form_extensions.js', 'blog' );
			}
			// Load javascript function to open popup windows fro contacts, report and etc.
			//load_funcs( 'users/model/_user.funcs.php' );
			break;

		case 'edit':
			global $current_User, $post_ID;

			// Post ID, go from $_GET when we edit a post from Front-office
			//          or from $_POST when we switch from Back-office
			$post_ID = param( 'p', 'integer', ( empty( $post_ID ) ? 0 : $post_ID ), true );

			if( !is_logged_in() )
			{ // Redirect to the login page if not logged in and allow anonymous user setting is OFF
				$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=edit' );
				$Messages->add( T_( 'You must log in to create & edit posts.' ) );
				header_redirect( get_login_url( 'cannot edit posts', $redirect_to ), 302 );
				// will have exited
			}

			if( !$current_User->check_status( 'can_edit_post' ) )
			{
				if( $current_User->check_status( 'can_be_validated' ) )
				{ // user is logged in but his/her account was not activated yet
					// Redirect to the account activation page
					$Messages->add( T_( 'You must activate your account before you can create & edit posts. <b>See below:</b>' ) );
					header_redirect( get_activate_info_url(), 302 );
					// will have exited
				}

				// Redirect to the blog url for users without messaging permission
				$Messages->add( T_('You are not allowed to create & edit posts!') );
				header_redirect( $Blog->gen_blogurl(), 302 );
			}

			// user logged in and the account was activated
			check_item_perm_edit( $post_ID );

			if( ! blog_has_cats( $Blog->ID ) )
			{ // No categories are in this blog
				$error_message = T_('Since this blog has no categories, you cannot post into it.');
				if( $current_User->check_perm( 'blog_cats', 'edit', false, $Blog->ID ) )
				{ // If current user has a permission to create a category
					global $admin_url;
					$error_message .= ' '.sprintf( T_('You must <a %s>create categories</a> first.'), 'href="'.$admin_url.'?ctrl=chapters&amp;blog='.$Blog->ID.'"');
				}
				$Messages->add( $error_message, 'error' );
				header_redirect( $Blog->gen_blogurl(), 302 );
			}

			// Require results.css to display attachments as a result table
			require_css( 'results.css' );

			init_tokeninput_js( 'blog' );
			init_datepicker_js( 'blog' );
			init_plugins_js( 'blog', $Skin->get_template( 'tooltip_plugin' ) );
			require_js( 'backoffice.js', 'blog' );
			require_js( 'extracats.js', 'blog' );
			init_autocomplete_usernames_js( 'blog' );

			init_inskin_editing();
			break;

		case 'edit_comment':
			global $current_User, $edited_Comment, $comment_Item, $Item, $comment_title, $comment_content, $display_params;

			// comment ID
			$comment_ID = param( 'c', 'integer', 0, true );

			if( !is_logged_in() )
			{ // Redirect to the login page if not logged in and allow anonymous user setting is OFF
				$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=edit_comment' );
				$Messages->add( T_( 'You must log in to edit comments.' ) );
				header_redirect( get_login_url( 'cannot edit comments', $redirect_to ), 302 );
				// will have exited
			}

			if( !$current_User->check_status( 'can_edit_comment' ) )
			{
				if( $current_User->check_status( 'can_be_validated' ) )
				{ // user is logged in but his/her account was not activated yet
					// Redirect to the account activation page
					$Messages->add( T_( 'You must activate your account before you can edit comments. <b>See below:</b>' ) );
					header_redirect( get_activate_info_url(), 302 );
					// will have exited
				}

				// Redirect to the blog url for users without messaging permission
				$Messages->add( 'You are not allowed to edit comments!' );
				header_redirect( $Blog->gen_blogurl(), 302 );
			}

			if( empty( $comment_ID ) )
			{ // Can't edit a not exisiting comment
				$Messages->add( 'Invalid comment edit URL!' );
				global $disp;
				$disp = 404;
				break;
			}

			$CommentCache = & get_CommentCache();
			$edited_Comment = $CommentCache->get_by_ID( $comment_ID );
			$comment_Item = $edited_Comment->get_Item();

			if( ! $current_User->check_perm( 'comment!CURSTATUS', 'edit', false, $edited_Comment ) )
			{ // If User has no permission to edit comments with this comment status:
				$Messages->add( 'You are not allowed to edit the previously selected comment!' );
				header_redirect( $Blog->gen_blogurl(), 302 );
			}

			$comment_title = '';
			$comment_content = htmlspecialchars_decode( $edited_Comment->content );

			// Format content for editing, if we were not already in editing...
			$Plugins_admin = & get_Plugins_admin();
			$comment_Item->load_Blog();
			$params = array( 'object_type' => 'Comment', 'object_Blog' => & $comment_Item->Blog );
			$Plugins_admin->unfilter_contents( $comment_title /* by ref */, $comment_content /* by ref */, $edited_Comment->get_renderers_validated(), $params );

			$Item = $comment_Item;

			$display_params = array();

			// Require results.css to display attachments as a result table
			require_css( 'results.css' );

			init_ratings_js( 'blog' );
			init_datepicker_js( 'blog' );
			init_plugins_js( 'blog', $Skin->get_template( 'tooltip_plugin' ) );
			init_autocomplete_usernames_js( 'blog' );
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

				$useritems_Blog = NULL;
				$user_ItemList = new ItemList2( $useritems_Blog, NULL, NULL, NULL, 'ItemCache', 'useritems_' );
				$user_ItemList->load_from_Request();
				$user_ItemList->set_filters( array(
						'authors' => $user_ID,
					), true, true );
				$user_ItemList->query();
			}
			else // $disp == 'usercomments'
			{ // Init comments list
				global $user_CommentList;

				$user_CommentList = new CommentList2( NULL, NULL, 'CommentCache', 'usercmts_' );
				$user_CommentList->load_from_Request();
				$user_CommentList->set_filters( array(
						'author_IDs' => $user_ID,
					), true, true );
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
			break;

		case 'closeaccount':
			global $current_User;
			if( ! $Settings->get( 'account_close_enabled' ) ||
			    ( is_logged_in() && $current_User->check_perm( 'users', 'edit', false ) ) ||
			    ( ! is_logged_in() && ! $Session->get( 'account_closing_success' ) ) )
			{ // If an account closing page is disabled - Display 404 page with error message
			  // Don't allow admins close own accounts from front office
			  // Don't display this message for not logged in users, except of one case to display a bye message after account closing
				global $disp;
				$disp = '404';
			}
			elseif( $Session->get( 'account_closing_success' ) )
			{ // User has closed the account
				global $account_closing_success;
				$account_closing_success = $Session->get( 'account_closing_success' );
				// Unset this temp session var to don't display the message twice
				$Session->delete( 'account_closing_success' );
				if( is_logged_in() )
				{ // log out current User
					logout();
				}
			}
			break;
	}

	$Debuglog->add('skin_init: $disp='.$disp. ' / $disp_detail='.$disp_detail.' / $seo_page_type='.$seo_page_type, 'skins' );

	// Make this switch block special only for 404 page
	switch( $disp )
	{
		case '404':
			// We have a 404 unresolved content error
			// How do we want do deal with it?
			skin_404_header();
			// This MAY or MAY not have exited -- will exit on 30x redirect, otherwise will return here.
			// Just in case some dumb robot needs extra directives on this:
			$robots_index = false;
			break;
	}

	global $Hit;
	if( $Hit->is_IE( 9, '<' ) )
	{ // IE < 9
		$Messages->add( T_('Your web browser is too old. For this site to work correctly, we recommend you use a more recent browser.'), 'note' );
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

function siteskin_init()
{
	// The following is temporary and should be moved to some SiteSkin class
	global $Settings;

	if( $Settings->get( 'site_skins_enabled' ) )
	{ // Site skins are enabled
		// Include the additional required files
		siteskin_include( '_skin_init.inc.php' );
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
 * Init some global variables used by skins
 * Note: This initializations were removed from the _main.inc.php, because it should not be part of the main init.
 */
function skin_init_global_vars()
{
	global $credit_links, $francois_links, $fplanque_links, $skin_links, $skinfaktory_links;

	$credit_links = array();
	$francois_links = array(
		'fr' => array( 'http://fplanque.net/', array( array( 78, 'Fran&ccedil;ois'),  array( 100, 'Francois') ) ),
		'' => array( 'http://fplanque.com/', array( array( 78, 'Fran&ccedil;ois'),  array( 100, 'Francois') ) )
	);
	$fplanque_links = array(
		'fr' => array( 'http://fplanque.net/', array( array( 78, 'Fran&ccedil;ois Planque'),  array( 100, 'Francois Planque') ) ),
		'' => array( 'http://fplanque.com/', array( array( 78, 'Fran&ccedil;ois Planque'),  array( 100, 'Francois Planque') ) )
	);
	$skin_links = array(
		'' => array( 'http://skinfaktory.com/', array( array( 15, 'b2evo skin'), array( 20, 'b2evo skins'), array( 35, 'b2evolution skin'), array( 40, 'b2evolution skins'), array( 55, 'Blog skin'), array( 60, 'Blog skins'), array( 75, 'Blog theme'),array( 80, 'Blog themes'), array( 95, 'Blog template'), array( 100, 'Blog templates') ) ),
	);
	$skinfaktory_links = array(
		'' => array(
			array( 73, 'http://evofactory.com/', array( array( 61, 'Evo Factory'), array( 68, 'EvoFactory'), array( 73, 'Evofactory') ) ),
			array( 100, 'http://skinfaktory.com/', array( array( 92, 'Skin Faktory'), array( 97, 'SkinFaktory'), array( 99, 'Skin Factory'), array( 100, 'SkinFactory') ) ),
		)
	);
}


/**
 * Tells if we are on the default blog page / front page.
 *
 * @return boolean
 */
function is_default_page()
{
	global $is_front;
	return $is_front;
}


/**
 * Template tag. Include a sub-template at the current position
 *
 */
function skin_include( $template_name, $params = array() )
{
	if( is_ajax_content( $template_name ) )
	{ // When we request ajax content for results table we need to hide wrapper data (header, footer & etc)
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

	if( ! empty( $disp ) && ( $disp == 'single' || $disp == 'page' ) &&
	    $template_name == '$disp$' &&
	    ! empty( $Item ) && ( $ItemType = & $Item->get_ItemType() ) )
	{ // Get template name for the current Item if it is defined by Item Type
		$item_type_template_name = $ItemType->get( 'template_name' );
		if( ! empty( $item_type_template_name ) )
		{ // The item type has a specific template for this display:
			$item_type_template_name = '_'.$item_type_template_name.'.disp.php';
			if( file_exists( $ads_current_skin_path.$item_type_template_name ) ||
			    skin_fallback_path( $item_type_template_name ) )
			{ // Use template file name of the Item Type only if it exists
				$template_name = $item_type_template_name;
			}
		}
	}

	if( $template_name == '$disp$' )
	{ // This is a special case.
		// We are going to include a template based on $disp:

		// Default display handlers:
		$disp_handlers = array(
				'disp_404'            => '_404_not_found.disp.php',
				'disp_403'            => '_403_forbidden.disp.php',
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
				'disp_front'          => '_front.disp.php',
				'disp_useritems'      => '_useritems.disp.php',
				'disp_usercomments'   => '_usercomments.disp.php',
				'disp_download'       => '_download.disp.php',
				'disp_access_denied'  => '_access_denied.disp.php',
				'disp_access_requires_login' => '_access_requires_login.disp.php',
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


	// DECIDE WHAT TO INCLUDE:
	if( $template_name[0] == '#' )
	{ // This disp mode is handled by a plugin:
		$debug_info = 'Call plugin';
		$disp_handled = 'plugin';
	}
	elseif( file_exists( $ads_current_skin_path.$template_name ) )
	{ // The skin has a customized handler, use that one instead:
		$file = $ads_current_skin_path.$template_name;
		$debug_info = '<b>Skin template</b>: '.rel_path_to_base( $file );
		$disp_handled = 'custom';
	}
	elseif( $fallback_template_path = skin_fallback_path( $template_name ) )
	{ // Use the default/fallback template:
		$file = $fallback_template_path;
		$debug_info = '<b>Fallback to</b>: '.rel_path_to_base( $file );
		$disp_handled = 'fallback';
	}
	else
	{
		$disp_handled = false;
	}

	// Do we want a visible container for DEBUG/DEV ?:
	if( strpos( $template_name, '_html_' ) !== false )
	{	// We're outside of the page body: NEVER display wrap this include with a <div>
		$display_includes = false;
	}
	else
	{	// We may wrap with a <div>:
		$display_includes = $Session->get( 'display_includes_'.$Blog->ID ) == 1;
	}
	if( $display_includes )
	{ // Wrap the include with a visible div:
		echo '<div class="dev-blocks dev-blocks--include">';
		echo '<div class="dev-blocks-name">';
		if( empty( $item_type_template_name ) )
		{ // Default template
			echo 'skin_include( <b>'.$template_name.'</b> )';
		}
		else
		{ // Custom template
			echo '<b>CUSTOM</b> skin_include( <b>'.$item_type_template_name.'</b> )';
		}
		echo ' -> '.$debug_info.'</div>';
	}

	switch( $disp_handled )
	{
		case 'plugin':
			// This disp mode is handled by a plugin:
			$plug_ID = substr( $template_name, 1 );
			$disp_params = array( 'disp' => $disp );
			$Plugins->call_method( $plug_ID, 'HandleDispMode', $disp_params );
			break;

		case 'custom':			// The skin has a customized handler, use that one instead:
		case 'fallback':		// Use the default/fallback template:
			$Debuglog->add('skin_include ('.($Item ? 'Item #'.$Item->ID : '-').'): '.$file, 'skins');
			require $file;
			break;
	}

	if( ! $disp_handled )
	{ // nothing handled the disp mode
		printf( '<div class="skin_error">Sub template [%s] not found.</div>', $template_name );
		if( !empty($current_User) && $current_User->level == 10 )
		{
			printf( '<div class="skin_error">User level 10 help info: [%s]</div>', $ads_current_skin_path.$template_name );
		}
	}

	if( $display_includes )
	{ // End of visible container:
		// echo get_icon( 'pixel', 'imgtag', array( 'class' => 'clear' ) );
		echo '</div>';
	}

	$Timer->pause( $timer_name );
}


/**
 * Get file path to fallback file depending on skin API version
 *
 * @param string Template name
 * @param integer Skin API version, NULL - to get API version of the current Skin
 * @return string|FALSE File path OR FALSE if fallback file doesn't exist
 */
function skin_fallback_path( $template_name, $skin_api_version = NULL )
{
	global $Skin, $basepath;

	if( $skin_api_version === NULL && ! empty( $Skin ) )
	{ // Get API version of the current skin
		$skin_api_version = $Skin->get_api_version();
	}

	if( $skin_api_version == 6 )
	{ // Check fallback file for v6 API skin
		$fallback_path = $basepath.'skins_fallback_v6/'.$template_name;
		if( file_exists( $fallback_path ) )
		{
			return $fallback_path;
		}
	}

	// Check fallback file for v5 API skin
	$fallback_path = $basepath.'skins_fallback_v5/'.$template_name;
	if( file_exists( $fallback_path ) )
	{
		return $fallback_path;
	}

	// No fallback file
	return false;
}


/**
 * Get file path to template file
 *
 * @param string Template name
 * @return string|FALSE File path OR FALSE if fallback file doesn't exist
 */
function skin_template_path( $template_name )
{
	global $Skin, $ads_current_skin_path;

	if( ! empty( $Skin ) && file_exists( $ads_current_skin_path.$template_name ) )
	{ // Template file exists for the current skin
		return $ads_current_skin_path.$template_name;
	}
	elseif( $fallback_path = skin_fallback_path( $template_name ) )
	{ // Falback file exists
		return $fallback_path;
	}

	return false;
}


/**
 * Template tag.
 *
 * @param string Template name
 * @param array Params
 * @param boolean force include even if sitewide header/footer not enabled
 */
function siteskin_include( $template_name, $params = array(), $force = false )
{
	global $Settings, $siteskins_path, $Blog;

	if( !$Settings->get( 'site_skins_enabled' ) && !$force )
	{ // Site skins are not enabled and we don't want to force either
		return;
	}

	if( is_ajax_content( $template_name ) )
	{ // When we request ajax content for results table we need to hide wrapper data (header, footer & etc)
		return;
	}

	// Globals that may be needed by the template:
	global $current_User, $Hit, $Session, $Settings;
	global $skin_url, $htsrv_url, $htsrv_url_sensitive;
	global $samedomain_htsrv_url, $secure_htsrv_url;
	global $credit_links, $skin_links, $francois_links, $fplanque_links, $skinfaktory_links;
	/**
	* @var Log
	*/
	global $Debuglog;
	global $Timer;

	$timer_name = 'siteskin_include('.$template_name.')';
	$Timer->resume( $timer_name );

	if( file_exists( $siteskins_path.'custom/'.$template_name ) )
	{ // Use the custom template:
		$file = $siteskins_path.'custom/'.$template_name;
		$debug_info = '<b>Custom template</b>: '.rel_path_to_base($file);
		$disp_handled = 'custom';
	}
	elseif( file_exists( $siteskins_path.$template_name ) ) // Try to include standard template only if custom template doesn't exist
	{ // Use the default/fallback template:
		$file = $siteskins_path.$template_name;
		$debug_info = '<b>Fallback to</b>: '.rel_path_to_base($file);
		$disp_handled = 'fallback';
	}
	else
	{
		$disp_handled = false;
	}


	// Do we want a visible container for DEBUG/DEV ?:
	if( strpos( $template_name, '_html_' ) !== false ||  strpos( $template_name, '_init.' ) !== false )
	{	// We're outside of the page body: NEVER display wrap this include with a <div>
		$display_includes = false;
	}
	else
	{	// We may wrap with a <div>:
		$display_includes = $Session->get( 'display_includes_'.$Blog->ID ) == 1;
	}
	if( $display_includes )
	{ // Wrap the include with a visible div:
		echo '<div class="dev-blocks dev-blocks--siteinclude">';
		echo '<div class="dev-blocks-name">siteskin_include( <b>'.$template_name.'</b> ) -> '.$debug_info.'</div>';
	}


	if($disp_handled)
	{
		$Debuglog->add('siteskin_include: '.rel_path_to_base($file), 'skins');
		require $file;
	}
	else
	{	// nothing handled the display
		printf( '<div class="skin_error">Site template [%s] not found.</div>', $template_name );
		if( !empty($current_User) && $current_User->level == 10 )
		{
			printf( '<div class="skin_error">User level 10 help info: [%s]</div>', $siteskins_path.$template_name );
		}
	}


	if( $display_includes )
	{ // End of visible container:
		// echo get_icon( 'pixel', 'imgtag', array( 'class' => 'clear' ) );
		echo '</div>';
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
	global $Blog, $disp, $disp_detail, $MainList, $Chapter, $is_front;

	$r = '';

	if( $is_front )
	{	// Use default description:
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
	global $Blog, $is_front, $disp, $MainList;

	$r = '';

	if( $is_front )
	{	// Use default keywords:
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

		$r = $Item->get_metakeywords();


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
 * Template tag
 *
 * Used to print out open graph tags
 */
function skin_opengraph_tags()
{
	global $Blog, $disp, $MainList;

	if( empty( $Blog ) || ! $Blog->get_setting( 'tags_open_graph' ) )
	{ // Open Graph tags are not allowed
		return;
	}

	// Get info for og:image tag
	$og_images = array();
	if( in_array( $disp, array( 'single', 'page' ) ) )
	{ // Use only on 'single' and 'page' disp
		$Item = & $MainList->get_by_idx( 0 );
		if( is_null( $Item ) )
		{ // This is not an object (happens on an invalid request):
			return;
		}

		$LinkOwner = new LinkItem( $Item );
		if( ! $LinkList = $LinkOwner->get_attachment_LinkList( 1000 ) )
		{ // Item has no linked files
			return;
		}

		while( $Link = & $LinkList->get_next() )
		{
			if( ! ( $File = & $Link->get_File() ) )
			{ // No File object
				global $Debuglog;
				$Debuglog->add( sprintf( 'Link ID#%d of item #%d does not have a file object!', $Link->ID, $Item->ID ), array( 'error', 'files' ) );
				continue;
			}

			if( ! $File->exists() )
			{ // File doesn't exist
				global $Debuglog;
				$Debuglog->add( sprintf( 'File linked to item #%d does not exist (%s)!', $Item->ID, $File->get_full_path() ), array( 'error', 'files' ) );
				continue;
			}

			if( $File->is_image() )
			{ // Use only image files for og:image tag
				$og_images[] = $File->get_url();
			}
		}
	}

	if( ! empty( $og_images ) )
	{ // Display meta tags for image:
		// Open Graph type tag (This tag is necessary for multiple images on facebook share button)
		echo '<meta property="og:type" content="article" />'."\n";
		foreach( $og_images as $og_image )
		{ // Open Graph image tag
			echo '<meta property="og:image" content="'.format_to_output( $og_image, 'htmlattr' )."\" />\n";
		}
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

		echo '<div class="skin_settings well">';
		$disp_params = array( 'skinshot_class' => 'coll_settings_skinshot' );
		Skin::disp_skinshot( $edited_Skin->folder, $edited_Skin->name, $disp_params );

		// Skin name
		echo '<div class="skin_setting_row">';
			echo '<label>'.T_('Skin name').':</label>';
			echo '<span>'.$edited_Skin->name.'</span>';
		echo '</div>';

		if( isset( $edited_Skin->version ) )
		{ // Skin version
			echo '<div class="skin_setting_row">';
				echo '<label>'.T_('Skin version').':</label>';
				echo '<span>'.$edited_Skin->version.'</span>';
			echo '</div>';
		}

		// Skin type
		echo '<div class="skin_setting_row">';
			echo '<label>'.T_('Skin type').':</label>';
			echo '<span>'.$edited_Skin->type.'</span>';
		echo '</div>';

		// Containers
		if( $skin_containers = $edited_Skin->get_containers() )
		{
			$container_ul = '<ul><li>'.implode( '</li><li>', $skin_containers ).'</li></ul>';
		}
		else
		{
			$container_ul = '-';
		}
		echo '<div class="skin_setting_row">';
			echo '<label>'.T_('Containers').':</label>';
			echo '<span>'.$container_ul.'</span>';
		echo '</div>';

		echo '</div>';
		echo '<div class="skin_settings_form">';

		$skin_params = $edited_Skin->get_param_definitions( $tmp_params = array( 'for_editing' => true ) );

		if( empty( $skin_params ) )
		{ // Advertise this feature!!
			echo '<p>'.T_('This skin does not provide any configurable settings.').'</p>';
		}
		else
		{
			load_funcs( 'plugins/_plugin.funcs.php' );

			// Check if skin settings contain at least one fieldset
			$skin_fieldsets_exist = false;
			foreach( $skin_params as $l_name => $l_meta )
			{
				if( isset( $l_meta['layout'] ) && $l_meta['layout'] == 'begin_fieldset' )
				{
					$skin_fieldsets_exist = true;
					break;
				}
			}

			if( ! $skin_fieldsets_exist )
			{ // Enclose all skin settings in single group if no group on the skin
				array_unshift( $skin_params, array(
						'layout' => 'begin_fieldset',
						'label'  => T_('Skin settings')
					) );
				array_push( $skin_params, array(
						'layout' => 'end_fieldset'
					) );
			}

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


/**
 * Template function to init and print out html attributes for <body> tag
 *
 * @param array Additional values for attributes
 */
function skin_body_attrs( $params = array() )
{
	$params = array_merge( array(
			'class' => NULL
		), $params );

	global $PageCache, $Blog, $disp, $disp_detail, $Item, $current_User;

	// WARNING: Caching! We're not supposed to have Session dependent stuff in here. This is for debugging only!
	global $Session;

	$classes = array();

	if( ! empty( $params['class'] ) )
	{ // Prepend additional classes from template skin
		$classes[] = $params['class'];
	}

	// Device class:
	if( ! empty( $PageCache ) )
	{ // Try to detect device only when Page Cache is defined
		if( $PageCache->is_collecting )
		{ // Page is cached now
			$classes[] = 'unknown_device page_cached';
		}
		else
		{ // Page is NOT cached now
			global $Session;
			if( $Session->is_mobile_session() )
			{ // Mobile device
				$classes[] = 'mobile_device';
			}
			elseif( $Session->is_tablet_session() )
			{ // Tablet device
				$classes[] = 'tablet_device';
			}
			else
			{ // Desktop device
				$classes[] = 'desktop_device';
			}
			$classes[] = 'page_notcached';
		}
	}

	// Blog class:
	$classes[] = 'coll_'.( empty( $Blog ) ? 'none' : $Blog->ID );

	// $disp class:
	$classes[] = 'disp_'.( empty( $disp ) ? 'none' : $disp );

	// $disp_detail class:
	$classes[] = 'detail_'.( empty( $disp_detail ) ? 'none' : $disp_detail );

	// Item class:
	$classes[] = 'item_'.( empty( $Item ) ? 'none' : $Item->ID );

	// Logged in/Anonymous class:
	$classes[] = is_logged_in() ? 'loggedin' : 'anonymous';

	// User Group class:
	$classes[] = 'usergroup_'.( ! is_logged_in() && empty( $current_User->grp_ID ) ? 'none' : $current_User->grp_ID );

	// WARNING: Caching! We're not supposed to have Session dependent stuff in here. This is for debugging only!
	if ( ! empty($Blog) )
	{
		if( $Session->get( 'display_includes_'.$Blog->ID ) ) 
		{
			$classes[] = 'dev_show_includes';
		}
		if( $Session->get( 'display_containers_'.$Blog->ID ) ) 
		{
			$classes[] = 'dev_show_containers';
		}
	}

	if( ! empty( $classes ) )
	{ // Print attr "class"
		echo ' class="'.implode( ' ', $classes ).'"';
	}
}

?>
