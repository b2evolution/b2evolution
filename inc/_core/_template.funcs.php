<?php
/**
 * This file implements misc functions that handle output of the HTML page.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Template tag. Output content-type header
 *
 * @param string content-type; override for RSS feeds
 */
function header_content_type( $type = 'text/html', $charset = '#' )
{
	global $io_charset;
	global $content_type_header;

	$content_type_header = 'Content-type: '.$type;

	if( !empty($charset) )
	{
		if( $charset == '#' )
		{
			$charset = $io_charset;
		}

		$content_type_header .= '; charset='.$charset;
	}

	header( $content_type_header );
}


/**
 * This is a placeholder for future development.
 *
 * @param string content-type; override for RSS feeds
 * @param integer seconds
 * @param string charset
 * @param boolean flush already collected content from the PageCache
 */
function headers_content_mightcache( $type = 'text/html', $max_age = '#', $charset = '#', $flush_pagecache = true )
{
	global $Messages, $is_admin_page;
	global $PageCache, $Debuglog;

	header_content_type( $type, $charset );

	if( empty($max_age) || $is_admin_page || is_logged_in() || $Messages->count() )
	{	// Don't cache if no max_age given
		// + NEVER EVER allow admin pages to cache
		// + NEVER EVER allow logged in data to be cached
		// + NEVER EVER allow transactional Messages to be cached!:
		header_nocache();

		// Check server caching too, but note that this is a different caching process then caching on the client
		// It's important that this is a double security check only and server caching should be prevented before this
		// If something should not be cached on the client, it should never be cached on the server either
		if( !empty( $PageCache ) )
		{ // Abort PageCache collect
			$Debuglog->add( 'Abort server caching in headers_content_mightcache() function. This should have been prevented!' );
			$PageCache->abort_collect( $flush_pagecache );
		}
		return;
	}

	// If we are on a "normal" page, we may, under some circumstances, tell the browser it can cache the data.
	// This MAY be extremely confusing though, every time a user logs in and gets back to a screen with no evobar!
	// This cannot be enabled by default and requires admin switches.

	// For feeds, it is a little bit less confusing. We might want to have the param enabled by default in that case.

	// WARNING: extra special care needs to be taken before ever caching a blog page that might contain a form or a comment preview
	// having user details cached would be extremely bad.

	// in the meantime...
	header_nocache();
}


/**
 * Sends HTTP header to redirect to the previous location (which
 * can be given as function parameter, GET parameter (redirect_to),
 * is taken from {@link Hit::$referer} or {@link $baseurl}).
 *
 * {@link $Debuglog} and {@link $Messages} get stored in {@link $Session}, so they
 * are available after the redirect.
 *
 * NOTE: This function {@link exit() exits} the php script execution.
 *
 * @todo fp> do NOT allow $redirect_to = NULL. This leads to spaghetti code and unpredictable behavior.
 *
 * @param string Destination URL to redirect to
 * @param boolean|integer is this a permanent redirect? if true, send a 301; otherwise a 303 OR response code 301,302,303
 * @param boolean is this a redirected post display? This param may be true only if we should redirect to a post url where the post status is 'redirected'!
 */
function header_redirect( $redirect_to = NULL, $status = false, $redirected_post = false )
{
	/**
	 * put your comment there...
	 *
	 * @var Hit
	 */
	global $Hit;
	global $baseurl, $Blog, $htsrv_url_sensitive, $ReqHost, $ReqURL, $dispatcher;
	global $Session, $Debuglog, $Messages;
	global $http_response_code, $allow_redirects_to_different_domain;

	// TODO: fp> get this out to the caller, make a helper func like get_returnto_url()
	if( empty($redirect_to) )
	{ // see if there's a redirect_to request param given:
		$redirect_to = param( 'redirect_to', 'url', '' );

		if( empty($redirect_to) )
		{
			if( ! empty($Hit->referer) )
			{
				$redirect_to = $Hit->referer;
			}
			elseif( isset($Blog) && is_object($Blog) )
			{
				$redirect_to = $Blog->get('url');
			}
			else
			{
				$redirect_to = $baseurl;
			}
		}
		elseif( $redirect_to[0] == '/' )
		{ // relative URL, prepend current host:
			$redirect_to = $ReqHost.$redirect_to;
		}
	}
	// <fp

	$Debuglog->add('Preparing to redirect to: '.$redirect_to, 'request' );

	$external_redirect = true; // Start with worst case, then whitelist:

	if( $redirect_to[0] == '/' || $redirect_to[0] == '?' )
	{  // We stay on the same domain or same page:
		$external_redirect = false;
	}
	elseif( strpos($redirect_to, $dispatcher ) === 0 )
	{	// $dispatcher is DEPRECATED and pages should use $admin_url URL instead, but at least we're staying on the same site:
		$external_redirect = false;
	}
	elseif( strpos($redirect_to, $baseurl) === 0 )
	{
	 	$Debuglog->add('Redirecting within $baseurl, all is fine.', 'request' );
	 	$external_redirect = false;
	}
	elseif( strpos($redirect_to, $htsrv_url_sensitive) === 0 )
	{
	 	$Debuglog->add('Redirecting within $htsrv_url_sensitive, all is fine.', 'request' );
	 	$external_redirect = false;
	}
	elseif( !empty($Blog) && strpos($redirect_to, $Blog->gen_baseurl()) === 0 )
	{
	 	$Debuglog->add('Redirecting within current collection URL, all is fine.', 'request' );
	 	$external_redirect = false;
	}


	// Remove login and pwd parameters from URL, so that they do not trigger the login screen again (and also as global security measure):
	$redirect_to = preg_replace( '~(?<=\?|&) (login|pwd) = [^&]+ ~x', '', $redirect_to );

	if( $external_redirect == false )
	{	// blueyed> Removed "confirm(ed)?" so it doesn't do the same thing twice
		// TODO: fp> confirm should be normalized to confirmed
		$redirect_to = preg_replace( '~(?<=\?|&) (confirm(ed)?) = [^&]+ ~x', '', $redirect_to );
	}


	// Check if we're trying to redirect to an external URL:
	if( $external_redirect // Attempting external redirect
		&& ( $allow_redirects_to_different_domain != 'always' ) // Always allow redirects to different domains is not set
		&& ( ! ( ( $allow_redirects_to_different_domain == 'only_redirected_posts' ) && $redirected_post ) ) ) // This is not a 'redirected' post display request
	{ // Force header redirects into the same domain. Do not allow external URLs.
		$Messages->add( T_('A redirection to an external URL was blocked for security reasons.'), 'error' );
		syslog_insert( 'A redirection to an external URL '.$redirect_to.' was blocked for security reasons.', 'error', NULL );
		$redirect_to = $baseurl;
	}


	if( is_integer($status) )
	{
		$http_response_code = $status;
	}
	else
	{
		$http_response_code = $status ? 301 : 303;
	}
 	$Debuglog->add('***** REDIRECT TO '.$redirect_to.' (status '.$http_response_code.') *****', 'request' );

	if( ! empty($Session) )
	{	// Session is required here

		// Transfer of Debuglog to next page:
		if( $Debuglog->count('all') )
		{	// Save Debuglog into Session, so that it's available after redirect (gets loaded by Session constructor):
			$sess_Debuglogs = $Session->get('Debuglogs');
			if( empty($sess_Debuglogs) )
			{
				$sess_Debuglogs = array();
			}

			$sess_Debuglogs[] = $Debuglog;
			$Session->set( 'Debuglogs', $sess_Debuglogs, 60 /* expire in 60 seconds */ );
			// echo 'Passing Debuglog(s) to next page';
			// pre_dump( $sess_Debuglogs );
		}

		// Transfer of Messages to next page:
		if( $Messages->count() )
		{	// Set Messages into user's session, so they get restored on the next page (after redirect):
			$Session->set( 'Messages', $Messages );
		 // echo 'Passing Messages to next page';
		}

		$Session->dbsave(); // If we don't save now, we run the risk that the redirect goes faster than the PHP script shutdown.
	}

	// see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
	switch( $http_response_code )
	{
		case 301:
			// This should be a permanent move redirect!
			header_http_response( '301 Moved Permanently' );
			break;

		case 303:
			// This should be a "follow up" redirect
			// Note: Also see http://de3.php.net/manual/en/function.header.php#50588 and the other comments around
			header_http_response( '303 See Other' );
			break;

		case 302:
		default:
			header_http_response( '302 Found' );
	}

	// debug_die($redirect_to);
	if( headers_sent($filename, $line) )
	{
		debug_die( sprintf('Headers have already been sent in %s on line %d.', basename($filename), $line)
						.'<br />Cannot <a href="'.htmlspecialchars($redirect_to).'">redirect</a>.' );
	}
	header( 'Location: '.$redirect_to, true, $http_response_code ); // explictly setting the status is required for (fast)cgi
	exit(0);
}



/**
 * Sends HTTP headers to avoid caching of the page at the browser level
 * (at least without revalidating with the server to make sure whether the content has changed or not).
 *
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
 */
function header_nocache( $timestamp = NULL )
{
	global $servertimenow;
	if( empty($timestamp) )
	{
		$timestamp = $servertimenow;
	}

	header('Expires: '.gmdate('r',$timestamp));
	header('Last-Modified: '.gmdate('r',$timestamp));
	header('Cache-Control: no-cache, must-revalidate');
	header('Pragma: no-cache');
}


/**
 * This is to "force" (strongly suggest) caching.
 *
 * WARNING: use this only for STATIC content that does NOT depend on the current user.
 *
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
 */
function header_noexpire()
{
	global $servertimenow;
	header('Expires: '.gmdate('r', $servertimenow + 31536000)); // 86400*365 (1 year)
}


/**
 * Generate an etag to identify the version of the current page.
 * We use this primarily to make a difference between the same page that has been generated for anonymous users
 * and a version that has been generated for a specific user.
 *
 * A common problem without this would be that when users log out, the page cache would tell them "304 Not Modified"
 * based on the date of the cache and then the browser would show a locally cached version of the page that includes
 * the evobar.
 *
 * When a specific user logs out, the browser will send back the Etag of the logged in version it got and we will
 * be able to detect that this is not a "304 Not Modified" case -> we will send back the anonymou version of the page.
 */
function gen_current_page_etag()
{
	global $current_User, $Messages;

	if( isset($current_User) )
	{
		$etag = 'user:'.$current_User->ID;
	}
	else
	{
		$etag = 'user:anon';
	}

	if( $Messages->count() )
	{	// This case has never been observed yet, but let's forward protect us against client side cached messages
		$etag .= '-msg:'.md5($Messages->get_string('',''));
	}

	return '"'.$etag.'"';
}


/**
 * This adds teh etag header
 *
 * @param string etag MUST be "quoted"
 */
function header_etag( $etag )
{
	header( 'ETag: '.$etag );
}


/**
 * Get global title matching filter params
 *
 * Outputs the title of the category when you load the page with <code>?cat=</code>
 * Display "Archive Directory" title if it has been requested
 * Display "Latest comments" title if these have been requested
 * Display "Statistics" title if these have been requested
 * Display "User profile" title if it has been requested
 *
 * @todo single month: Respect locales datefmt
 * @todo single post: posts do no get proper checking (wether they are in the requested blog or wether their permissions match user rights,
 * thus the title sometimes gets displayed even when it should not. We need to pre-query the ItemList instead!!
 * @todo make it complete with all possible params!
 *
 * @param array params
 *        - "auto_pilot": "seo_title": Use the SEO title autopilot. (Default: "none")
 */
function get_request_title( $params = array() )
{
	global $MainList, $preview, $disp, $action, $current_User, $Blog, $admin_url;

	$r = array();

	$params = array_merge( array(
			'auto_pilot'          => 'none',
			'title_before'        => '',
			'title_after'         => '',
			'title_none'          => '',
			'title_single_disp'   => true,
			'title_single_before' => '#',
			'title_single_after'  => '#',
			'title_page_disp'     => true,
			'title_page_before'   => '#',
			'title_page_after'    => '#',
			'glue'                => ' - ',
			'format'              => 'htmlbody',
			'arcdir_text'         => T_('Archive Directory'),
			'catdir_text'         => T_('Category Directory'),
			'mediaidx_text'       => T_('Photo Index'),
			'postidx_text'        => T_('Post Index'),
			'search_text'         => T_('Search'),
			'sitemap_text'        => T_('Site Map'),
			'msgform_text'        => T_('Sending a message'),
			'messages_text'       => T_('Messages'),
			'contacts_text'       => T_('Contacts'),
			'login_text'          => /* TRANS: trailing space = verb */ T_('Login '),
			'register_text'       => T_('Register'),
			'req_validatemail'    => T_('Account activation'),
			'account_activation'  => T_('Account activation'),
			'lostpassword_text'   => T_('Lost your password?'),
			'profile_text'        => T_('User Profile'),
			'avatar_text'         => T_('Profile picture'),
			'pwdchange_text'      => T_('Password change'),
			'userprefs_text'      => T_('User preferences'),
			'user_text'           => T_('User: %s'),
			'users_text'          => T_('Users'),
			'closeaccount_text'   => T_('Close account'),
			'subs_text'           => T_('Notifications'),
			'comments_text'       => T_('Latest Comments'),
			'feedback-popup_text' => T_('Feedback'),
			'edit_text_create'    => T_('New post'),
			'edit_text_update'    => T_('Editing post'),
			'edit_text_copy'      => T_('Duplicating post'),
			'edit_comment_text'   => T_('Editing comment'),
			'front_text'          => '',		// We don't want to display a special title on the front page
			'posts_text'          => '#',
			'useritems_text'      => T_('User posts'),
			'usercomments_text'   => T_('User comments'),
			'download_head_text'  => T_('Download').' - $file_title$ - $post_title$',
			'download_body_text'  => '',
			'display_edit_links'  => true, // Display the links to advanced editing on disp=edit|edit_comment
			'edit_links_template' => array(), // Template for the links to advanced editing on disp=edit|edit_comment
		), $params );

	if( $params['auto_pilot'] == 'seo_title' )
	{	// We want to use the SEO title autopilot. Do overrides:
		$params['format'] = 'htmlhead';
		$params['title_after'] = $params['glue'].$Blog->get('name');
		$params['title_single_after'] = '';
		$params['title_page_after'] = '';
		$params['title_none'] = $Blog->dget('name','htmlhead');
	}


	$before = $params['title_before'];
	$after = $params['title_after'];

	switch( $disp )
	{
		case 'front':
			// We are requesting a front page:
			if( !empty( $params['front_text'] ) )
			{
				$r[] = $params['front_text'];
			}
			break;

		case 'arcdir':
			// We are requesting the archive directory:
			$r[] = $params['arcdir_text'];
			break;

		case 'catdir':
			// We are requesting the archive directory:
			$r[] = $params['catdir_text'];
			break;

		case 'mediaidx':
			$r[] = $params['mediaidx_text'];
			break;

		case 'postidx':
			$r[] = $params['postidx_text'];
			break;

		case 'sitemap':
			$r[] = $params['sitemap_text'];
			break;

		case 'search':
			$r[] = $params['search_text'];
			break;

		case 'comments':
			// We are requesting the latest comments:
			global $Item;
			if( isset( $Item ) )
			{
				$r[] = sprintf( $params['comments_text'] . T_(' on %s'), $Item->get('title') );
			}
			else
			{
				$r[] = $params['comments_text'];
			}
			break;

		case 'feedback-popup':
			// We are requesting the comments on a specific post:
			// Should be in first position
			$Item = & $MainList->get_by_idx( 0 );
			$r[] = sprintf( $params['feedback-popup_text'] . T_(' on %s'), $Item->get('title') );
			break;

		case 'profile':
			// We are requesting the user profile:
			$r[] = $params['profile_text'];
			break;

		case 'avatar':
			// We are requesting the user avatar:
			$r[] = $params['avatar_text'];
			break;

		case 'pwdchange':
			// We are requesting the user change password:
			$r[] = $params['pwdchange_text'];
			break;

		case 'userprefs':
			// We are requesting the user preferences:
			$r[] = $params['userprefs_text'];
			break;

		case 'subs':
			// We are requesting the subscriptions screen:
			$r[] = $params['subs_text'];
			break;

		case 'msgform':
			// We are requesting the message form:
			$r[] = $params['msgform_text'];
			break;

		case 'threads':
		case 'messages':
			// We are requesting the messages form
			$thrd_ID = param( 'thrd_ID', 'integer', 0 );
			if( empty( $thrd_ID ) )
			{
				$r[] = $params['messages_text'];
			}
			else
			{	// We get a thread title by ID
				load_class( 'messaging/model/_thread.class.php', 'Thread' );
				$ThreadCache = & get_ThreadCache();
				if( $Thread = $ThreadCache->get_by_ID( $thrd_ID, false ) )
				{	// Thread exists and we get a title
					if( $params['auto_pilot'] == 'seo_title' )
					{	// Display thread title only for tag <title>
						$r[] = $Thread->title;
					}
				}
				else
				{	// Bad request with not existing thread
					$r[] = strip_tags( $params['messages_text'] );
				}
			}
			break;

		case 'contacts':
			// We are requesting the message form:
			$r[] = $params['contacts_text'];
			break;

		case 'login':
			// We are requesting the login form:
			if( $action == 'req_validatemail' )
			{
				$r[] = $params['req_validatemail'];
			}
			else
			{
				$r[] = $params['login_text'];
			}
			break;

		case 'register':
			// We are requesting the registration form:
			$r[] = $params['register_text'];
			break;

		case 'activateinfo':
			// We are requesting the activate info form:
			$r[] = $params['account_activation'];
			break;

		case 'lostpassword':
			// We are requesting the lost password form:
			$r[] = $params['lostpassword_text'];
			break;

		case 'single':
		case 'page':
			// We are displaying a single message:
			if( $preview )
			{	// We are requesting a post preview:
				$r[] = T_('PREVIEW');
			}
			elseif( $params['title_'.$disp.'_disp'] && isset( $MainList ) )
			{
				$r = array_merge( $r, $MainList->get_filter_titles( array( 'visibility', 'hide_future' ), $params ) );
			}
			if( $params['title_'.$disp.'_before'] != '#' )
			{
				$before = $params['title_'.$disp.'_before'];
			}
			if( $params['title_'.$disp.'_after'] != '#' )
			{
				$after = $params['title_'.$disp.'_after'];
			}
			break;
	
		case 'download':
			// We are displaying a download page:
			global $download_Link;

			$download_text = ( $params['format'] == 'htmlhead' ) ? $params['download_head_text'] : $params['download_body_text'];
			if( strpos( $download_text, '$file_title$' ) !== false )
			{ // Replace a mask $file_title$ with real file name
				$download_File = & $download_Link->get_File();
				$download_text = str_replace( '$file_title$', $download_File->get_name(), $download_text );
			}
			if( strpos( $download_text, '$post_title$' ) !== false )
			{ // Replace a mask $file_title$ with real file name
				$download_text = str_replace( '$post_title$', implode( $params['glue'], $MainList->get_filter_titles( array( 'visibility', 'hide_future' ) ) ), $download_text );
			}
			$r[] = $download_text;
			break;

		case 'user':
			// We are requesting the user page:
			$user_ID = param( 'user_ID', 'integer', 0 );
			$UserCache = & get_UserCache();
			$User = & $UserCache->get_by_ID( $user_ID, false, false );
			$user_login = $User ? $User->get( 'login' ) : '';
			$r[] = sprintf( $params['user_text'], $user_login );
			break;

		case 'users':
			$r[] = $params['users_text'];
			break;

		case 'closeaccount':
			$r[] = $params['closeaccount_text'];
			break;

		case 'edit':
			$action = param_action(); // Edit post by switching into 'In skin' mode from Back-office
			$p = param( 'p', 'integer', 0 ); // Edit post from Front-office
			$post_ID = param ( 'post_ID', 'integer', 0 ); // Update the edited post( If user is redirected to edit form again with some error messages )
			$cp = param( 'cp', 'integer', 0 ); // Copy post from Front-office
			if( $action == 'edit_switchtab' || $p > 0 || $post_ID > 0 )
			{	// Edit post
				$title = $params['edit_text_update'];
			}
			else if( $cp > 0 )
			{	// Copy post
				$title = $params['edit_text_copy'];
			}
			else
			{	// Create post
				$title = $params['edit_text_create'];
			}
			if( $params['display_edit_links'] && $params['auto_pilot'] != 'seo_title' )
			{ // Add advanced edit and close icon
				$params['edit_links_template'] = array_merge( array(
						'before'              => '<span class="title_action_icons">',
						'after'               => '</span>',
						'advanced_link_class' => '',
						'close_link_class'    => '',
					), $params['edit_links_template'] );

				global $edited_Item;
				if( !empty( $edited_Item ) && $edited_Item->ID > 0 )
				{ // Set the cancel editing url as permanent url of the item
					$cancel_url = $edited_Item->get_permanent_url();
				}
				else
				{ // Set the cancel editing url to home page of the blog
					$cancel_url = $Blog->gen_blogurl();
				}

				$title .= $params['edit_links_template']['before'];
				if( $current_User->check_perm( 'admin', 'restricted' ) )
				{
					global $advanced_edit_link;
					$title .= action_icon( T_('Go to advanced edit screen'), 'edit', $advanced_edit_link['href'], ' '.T_('Advanced editing'), NULL, 3, array(
							'onclick' => $advanced_edit_link['onclick'],
							'class'   => $params['edit_links_template']['advanced_link_class'].' action_icon',
						) );
				}
				$title .= action_icon( T_('Cancel editing'), 'close', $cancel_url, ' '.T_('Cancel editing'), NULL, 3, array(
						'class' => $params['edit_links_template']['close_link_class'].' action_icon',
					) );
				$title .= $params['edit_links_template']['after'];
			}
			$r[] = $title;
			break;

		case 'edit_comment':
			global $comment_Item, $edited_Comment;
			$title = $params['edit_comment_text'];
			if( $params['display_edit_links'] && $params['auto_pilot'] != 'seo_title' )
			{ // Add advanced edit and close icon
				$params['edit_links_template'] = array_merge( array(
						'before'              => '<span class="title_action_icons">',
						'after'               => '</span>',
						'advanced_link_class' => '',
						'close_link_class'    => '',
					), $params['edit_links_template'] );

				$title .= $params['edit_links_template']['before'];
				if( $current_User->check_perm( 'admin', 'restricted' ) )
				{
					$advanced_edit_url = url_add_param( $admin_url, 'ctrl=comments&amp;action=edit&amp;blog='.$Blog->ID.'&amp;comment_ID='.$edited_Comment->ID );
					$title .= action_icon( T_('Go to advanced edit screen'), 'edit', $advanced_edit_url, ' '.T_('Advanced editing'), NULL, 3, array(
							'onclick' => 'return switch_edit_view();',
							'class'   => $params['edit_links_template']['advanced_link_class'].' action_icon',
						) );
				}
				if( empty( $comment_Item ) )
				{
					$comment_Item = & $edited_Comment->get_Item();
				}
				if( !empty( $comment_Item ) )
				{
					$title .= action_icon( T_('Cancel editing'), 'close', url_add_tail( $comment_Item->get_permanent_url(), '#c'.$edited_Comment->ID ), ' '.T_('Cancel editing'), NULL, 3, array(
							'class' => $params['edit_links_template']['close_link_class'].' action_icon',
						) );
				}
				$title .= $params['edit_links_template']['after'];
			}
			$r[] = $title;
			break;

		case 'useritems':
			// We are requesting the user items list:
			$r[] = $params['useritems_text'];
			break;

		case 'usercomments':
			// We are requesting the user comments list:
			$r[] = $params['usercomments_text'];
			break;

		case 'posts':
			// We are requesting a posts page:
			if( $params['posts_text'] != '#' )
			{
				$r[] = $params['posts_text'];
				break;
			}
			// No break if empty, Use title from default case

		default:
			if( isset( $MainList ) )
			{
				$r = array_merge( $r, $MainList->get_filter_titles( array( 'visibility', 'hide_future' ), $params ) );
			}
			break;
	}


	if( ! empty( $r ) )
	{	// We have at leats one title match:
		$r = implode( $params['glue'], $r );
		if( ! empty( $r ) )
		{	// This is in case we asked for an empty title (e-g for search)
			$r = $before.format_to_output( $r, $params['format'] ).$after;
		}
	}
	elseif( !empty( $params['title_none'] ) )
	{
		$r = $params['title_none'];
	}
	else
	{	// never return array()
		$r = '';
	}

	return $r;
}


/**
 * Display a global title matching filter params
 *
 * @param array params
 *        - "auto_pilot": "seo_title": Use the SEO title autopilot. (Default: "none")
 */
function request_title( $params = array() )
{
	$r = get_request_title( $params );

	if( !empty( $r ) )
	{ // We have something to display:
		echo $r;
	}
}


/**
 * Returns a "<base />" tag and remembers that we've used it ({@link regenerate_url()} needs this).
 *
 * @param string URL to use (this gets used as base URL for all relative links on the HTML page)
 * @return string
 */
function base_tag( $url, $target = NULL )
{
	global $base_tag_set;
	$base_tag_set = $url;
	echo '<base href="'.$url.'"';

	if( !empty($target) )
	{
		echo ' target="'.$target.'"';
	}
	echo " />\n";
}


/**
 * Robots tag
 *
 * Outputs the robots meta tag if necessary
 */
function robots_tag()
{
	global $robots_index, $robots_follow;

	if( is_null($robots_index) && is_null($robots_follow) )
	{
		return;
	}

	$r = '<meta name="robots" content="';

	if( $robots_index === false )
		$r .= 'NOINDEX';
	else
		$r .= 'INDEX';

	$r .= ',';

	if( $robots_follow === false )
		$r .= 'NOFOLLOW';
	else
		$r .= 'FOLLOW';

	$r .= '" />'."\n";

	echo $r;
}


/**
 * Output a link to current blog.
 *
 * We need this function because if no Blog is currently active (some admin pages or site pages)
 * then we'll go to the general home.
 */
function blog_home_link( $before = '', $after = '', $blog_text = 'Blog', $home_text = 'Home' )
{
	global $Blog, $baseurl;

	if( !empty( $Blog ) )
	{
		echo $before.'<a href="'.$Blog->get( 'url' ).'">'.$blog_text.'</a>'.$after;
	}
	elseif( !empty($home_text) )
	{
		echo $before.'<a href="'.$baseurl.'">'.$home_text.'</a>'.$after;
	}
}


/**
 * Get library url of JS or CSS file by file name or alias
 *
 * @param string File or Alias name
 * @param boolean|string 'relative' or true (relative to <base>) or 'rsc_url' (relative to $rsc_url) or 'blog' (relative to current blog URL -- may be subdomain or custom domain)
 * @param string 'js' or 'css' or 'build'
 * @return string URL
 * @param string version number to append at the end of requested url to avoid getting an old version from the cache
 */
function get_require_url( $lib_file, $relative_to = 'rsc_url', $subfolder = 'js', $version = '#' )
{
	global $library_local_urls, $library_cdn_urls, $use_cdns, $debug, $rsc_url;
	global $Blog, $baseurl, $assets_baseurl;

	// Check if we have a public CDN we want to use for this library file:
	if( $use_cdns && ! empty( $library_cdn_urls[ $lib_file ] ) )
	{ // Rewrite local urls with public CDN urls if they are defined in _advanced.php
		$library_local_urls[ $lib_file ] = $library_cdn_urls[ $lib_file ];
		// Don't append version for global CDN urls
		$version = NULL;
	}

	if( ! empty( $library_local_urls[ $lib_file ] ) )
	{ // We are requesting an alias
		if( $debug && ! empty( $library_local_urls[ $lib_file ][1] ) )
		{ // Load JS file for debug mode (optional)
			$lib_file = $library_local_urls[ $lib_file ][1];
		}
		else
		{ // Load JS file for production mode
			$lib_file = $library_local_urls[ $lib_file ][0];
		}

		if( $relative_to === 'relative' || $relative_to === true )
		{ // Aliases cannot be relative to <base>, make it relative to $rsc_url
			$relative_to = 'rsc_url';
		}
	}

	if( $relative_to === 'relative' || $relative_to === true )
	{ // Make the file relative to current page <base>:
		$lib_url = $lib_file;
	}
	elseif( preg_match('~^(https?:)?//~', $lib_file ) )
	{ // It's already an absolute url, keep it as is:
		$lib_url = $lib_file;
	}
	elseif( $relative_to === 'blog' && ! empty( $Blog ) )
	{ // Get the file from $rsc_uri relative to the current blog's domain (may be a subdomain or a custom domain):
		if( $assets_baseurl !== $baseurl )
		{ // We are using a specific domain, don't try to load from blog specific domain
			$lib_url = $rsc_url.$subfolder.'/'.$lib_file;
		}
		else
		{
			$lib_url = $Blog->get_local_rsc_url().$subfolder.'/'.$lib_file;
		}
	}
	else
	{ // Get the file from $rsc_url:
		$lib_url = $rsc_url.$subfolder.'/'.$lib_file;
	}

	if( ! empty( $version ) )
	{ // Be sure to get a fresh copy of this CSS file after application upgrades:
		if( $version == '#' )
		{
			global $app_version_long;
			$version = $app_version_long;
		}
		$lib_url = url_add_param( $lib_url, 'v='.$version );
	}

	return $lib_url;
}

/**
 * Memorize that a specific javascript file will be required by the current page.
 * All requested files will be included in the page head only once (when headlines is called)
 *
 * Accepts absolute urls, filenames relative to the rsc/js directory and certain aliases, like 'jquery' and 'jquery_debug'
 * If 'jquery' is used and $debug is set to true, the 'jquery_debug' is automatically swapped in.
 * Any javascript added to the page is also added to the $required_js array, which is then checked to prevent adding the same code twice
 *
 * @param string alias, url or filename (relative to rsc/js) for javascript file
 * @param boolean|string Is the file's path relative to the base path/url?
 * @param boolean TRUE to add attribute "async" to load javascript asynchronously
 * @param boolean TRUE to print script tag on the page, FALSE to store in array to print then inside <head>
 */
function require_js( $js_file, $relative_to = 'rsc_url', $async = false, $output = false )
{
	static $required_js;
	global $dequeued_headlines;

	if( isset( $dequeued_headlines[ $js_file ] ) )
	{ // Don't require this file if it was dequeued before this request
		return;
	}

	if( is_admin_page() && ( $relative_to == 'blog' ) )
	{ // Make sure we never use resource url relative to any blog url in case of an admin page ( important in case of multi-domain installations )
		$relative_to = 'rsc_url';
	}

	if( in_array( $js_file, array( '#jqueryUI#', 'communication.js', 'functions.js' ) ) )
	{ // Dependency : ensure jQuery is loaded
		require_js( '#jquery#', $relative_to, $async, $output );
	}

	// Get library url of JS file by alias name
	$js_url = get_require_url( $js_file, $relative_to, 'js' );

	// Add to headlines, if not done already:
	if( empty( $required_js ) || ! in_array( strtolower( $js_url ), $required_js ) )
	{
		$required_js[] = strtolower( $js_url );

		$script_tag = '<script type="text/javascript"';
		$script_tag .= $async ? ' async' : '';
		$script_tag .= ' src="'.$js_url.'">';
		$script_tag .= '</script>';

		if( $output )
		{ // Print script tag right here
			echo $script_tag;
		}
		else
		{ // Add script tag to <head>
			add_headline( $script_tag, $js_file );
		}
	}

	/* Yura: Don't require this plugin when it is already concatenated in jquery.bundle.js
	 * But we should don't forget it for CDN jQuery file and when js code uses deprecated things of jQuery
	if( $js_file == '#jquery#' )
	{ // Dependency : The plugin restores deprecated features and behaviors so that older code will still run properly on jQuery 1.9 and later
		require_js( '#jquery_migrate#', $relative_to, $async, $output );
	}
	 */
}


/**
 * Memorize that a specific css that file will be required by the current page.
 * All requested files will be included in the page head only once (when headlines is called)
 *
 * Accepts absolute urls, filenames relative to the rsc/css directory.
 * Set $relative_to_base to TRUE to prevent this function from adding on the rsc_path
 *
 * @param string alias, url or filename (relative to rsc/css) for CSS file
 * @param boolean|string 'relative' or true (relative to <base>) or 'rsc_url' (relative to $rsc_url) or 'blog' (relative to current blog URL -- may be subdomain or custom domain)
 * @param string title.  The title for the link tag
 * @param string media.  ie, 'print'
 * @param string version number to append at the end of requested url to avoid getting an old version from the cache
 * @param boolean TRUE to print script tag on the page, FALSE to store in array to print then inside <head>
 */
function require_css( $css_file, $relative_to = 'rsc_url', $title = NULL, $media = NULL, $version = '#', $output = false )
{
	static $required_css;
	global $dequeued_headlines;

	if( isset( $dequeued_headlines[ $css_file ] ) )
	{ // Don't require this file if it was dequeued before this request
		return;
	}

	// Which subfolder do we want to use in case of absolute paths? (doesn't appy to 'relative')
	$subfolder = 'css';
	if( $relative_to == 'rsc_url' || $relative_to == 'blog' )
	{
		if( preg_match( '/\.(bundle|bmin|min)\.css$/', $css_file ) )
		{
			$subfolder = 'build';
		}
	}

	// Get library url of CSS file by alias name
	$css_url = get_require_url( $css_file, $relative_to, $subfolder, $version );

	// Add to headlines, if not done already:
	if( empty( $required_css ) || ! in_array( strtolower( $css_url ), $required_css ) )
	{
		$required_css[] = strtolower( $css_url );

		$stylesheet_tag = '<link type="text/css" rel="stylesheet"';
		$stylesheet_tag .= empty( $title ) ? '' : ' title="'.$title.'"';
		$stylesheet_tag .= empty( $media ) ? '' : ' media="'.$media.'"';
		$stylesheet_tag .= ' href="'.$css_url.'" />';

		if( $output )
		{ // Print stylesheet tag right here
			echo $stylesheet_tag;
		}
		else
		{ // Add stylesheet tag to <head>
			add_headline( $stylesheet_tag, $css_file );
		}
	}
}


/**
 * Dequeue a file from $headlines array by file name or alias
 *
 * @param string alias, url or filename (relative to rsc/js) for javascript file
 */
function dequeue( $file_name )
{
	global $headlines, $dequeued_headlines;

	if( ! is_array( $dequeued_headlines ) )
	{ // Initialize array firs time
		$dequeued_headlines = array();
	}

	// Store each dequeued file in order to don't require this next time
	$dequeued_headlines[ $file_name ] = true;

	if( isset( $headlines[ $file_name ] ) )
	{ // Dequeue this file
		unset( $headlines[ $file_name ] );
	}
}


/**
 * Memorize that a specific js helper will be required by the current page.
 * This allows to require JS + SS + do init.
 *
 * All requested helpers will be included in the page head only once (when headlines is called)
 * Requested helpers should add their required translation strings and any other settings
 *
 * @param string helper, name of the required helper
 */
function require_js_helper( $helper = '', $relative_to = 'rsc_url' )
{
	static $helpers;

	if( empty( $helpers ) || !in_array( $helper, $helpers ) )
	{ // Helper not already added, add the helper:

		switch( $helper )
		{
			case 'helper' :
				// main helper object required
				global $debug;
				require_js( '#jquery#', $relative_to ); // dependency
				require_js( 'helper.js', $relative_to );
				add_js_headline('jQuery(document).ready(function()
				{
					b2evoHelper.Init({
						debug:'.( $debug ? 'true' : 'false' ).'
					});
				});');
				break;

			case 'communications' :
				// communications object required
				require_js_helper('helper', $relative_to ); // dependency

				global $dispatcher;
				require_js( 'communication.js', $relative_to );
				add_js_headline('jQuery(document).ready(function()
				{
					b2evoCommunications.Init({
						dispatcher:"'.$dispatcher.'"
					});
				});' );
				// add translation strings
				T_('Update cancelled', NULL, array( 'for_helper' => true ) );
				T_('Update paused', NULL, array( 'for_helper' => true ) );
				T_('Changes pending', NULL, array( 'for_helper' => true ) );
				T_('Saving changes', NULL, array( 'for_helper' => true ) );
				break;

			case 'colorbox':
				// Colorbox: a lightweight Lightbox alternative -- allows zooming on images and slideshows in groups of images
				// Added by fplanque - (MIT License) - http://colorpowered.com/colorbox/

				global $b2evo_icons_type, $blog;
				$blog_param = empty( $blog ) ? '' : '&blog='.$blog;
				// Colorbox params to display a voting panel
				$colorbox_voting_params = '{displayVoting: true,
					votingUrl: "'.get_secure_htsrv_url().'anon_async.php?action=voting&vote_type=link&b2evo_icons_type='.$b2evo_icons_type.$blog_param.'",
					minWidth: 305}';
				// Colorbox params without voting panel
				$colorbox_no_voting_params = '{minWidth: 255}';

				// Initialize js variables b2evo_colorbox_params* that are used in async loaded colorbox file
				if( is_logged_in() )
				{ // User is logged in
					// All unknown images have a voting panel
					$colorbox_params_other = 'var b2evo_colorbox_params_other = '.$colorbox_voting_params;
					if( is_admin_page() )
					{ // Display a voting panel for all images in backoffice
						$colorbox_params_post = 'var b2evo_colorbox_params_post = '.$colorbox_voting_params;
						$colorbox_params_cmnt = 'var b2evo_colorbox_params_cmnt = '.$colorbox_voting_params;
						$colorbox_params_user = 'var b2evo_colorbox_params_user = '.$colorbox_voting_params;
					}
					else
					{ // Display a voting panel depending on skin settings
						global $Skin;
						if( ! empty( $Skin ) )
						{
							$colorbox_params_post = 'var b2evo_colorbox_params_post = '.( $Skin->get_setting( 'colorbox_vote_post' ) ? $colorbox_voting_params : $colorbox_no_voting_params );
							$colorbox_params_cmnt = 'var b2evo_colorbox_params_cmnt = '.( $Skin->get_setting( 'colorbox_vote_comment' ) ? $colorbox_voting_params : $colorbox_no_voting_params );
							$colorbox_params_user = 'var b2evo_colorbox_params_user = '.( $Skin->get_setting( 'colorbox_vote_user' ) ? $colorbox_voting_params : $colorbox_no_voting_params );
						}
					}
				}
				if( ! isset( $colorbox_params_post ) )
				{ // Don't display a voting panel for all images if user is NOT logged in OR for case when $Skin is not defined
					$colorbox_params_other = 'var b2evo_colorbox_params_other = '.$colorbox_no_voting_params;
					$colorbox_params_post = 'var b2evo_colorbox_params_post = '.$colorbox_no_voting_params;
					$colorbox_params_cmnt = 'var b2evo_colorbox_params_cmnt = '.$colorbox_no_voting_params;
					$colorbox_params_user = 'var b2evo_colorbox_params_user = '.$colorbox_no_voting_params;
				}

				require_js( '#jquery#', $relative_to );
				// Initialize the colorbox settings:
				add_js_headline(
					// General settings:
					'var b2evo_colorbox_params = {
						maxWidth: jQuery( window ).width() > 480 ? "95%" : "100%",
						maxHeight: jQuery( window ).height() > 480 ? "90%" : "100%",
						slideshow: true,
						slideshowAuto: false
					};
					'// For post images
					.$colorbox_params_post.';
					b2evo_colorbox_params_post = jQuery.extend( {}, b2evo_colorbox_params, b2evo_colorbox_params_post );
					'// For comment images
					.$colorbox_params_cmnt.';
					b2evo_colorbox_params_cmnt = jQuery.extend( {}, b2evo_colorbox_params, b2evo_colorbox_params_cmnt );
					'// For user images
					.$colorbox_params_user.';
					b2evo_colorbox_params_user = jQuery.extend( {}, b2evo_colorbox_params, b2evo_colorbox_params_user );
					'// For all other images
					.$colorbox_params_other.';
					b2evo_colorbox_params = jQuery.extend( {}, b2evo_colorbox_params, b2evo_colorbox_params_other );' );
				// TODO: translation strings for colorbox buttons

				require_js( 'build/colorbox.bmin.js', $relative_to, true );
				require_css( 'colorbox/colorbox.css', $relative_to );
				break;
		}
		// add to list of loaded helpers
		$helpers[] = $helper;
	}
}

/**
 * Memorize that a specific translation will be required by the current page.
 * All requested translations will be included in the page body only once (when footerlines is called)
 *
 * @param string string, untranslated string
 * @param string translation, translated string
 */
function add_js_translation( $string, $translation )
{
	global $js_translations;
	if( $string != $translation )
	{ // it's translated
		$js_translations[ $string ] = $translation;
	}
}


/**
 * Add a headline, which then gets output in the HTML HEAD section.
 * If you want to include CSS or JavaScript files, please use
 * {@link require_css()} and {@link require_js()} instead.
 * This avoids duplicates and allows caching/concatenating those files
 * later (not implemented yet)
 *
 * @param string HTML tag like <script></script> or <link />
 * @param string File name (used to index)
 */
function add_headline( $headline, $file_name = NULL )
{
	global $headlines, $dequeued_headlines;

	if( is_null( $file_name ) )
	{ // Use auto index if file name is not defined
		$headlines[] = $headline;
	}
	else
	{ // Try to add headline with file name to array
		if( isset( $dequeued_headlines[ $file_name ] ) )
		{ // Don't require this file if it was dequeued before this request
			return;
		}
		// Use file name as key index in $headline array
		$headlines[ $file_name ] = $headline;
	}
}


/**
 * Add a Javascript headline.
 * This is an extra function, to provide consistent wrapping and allow to bundle it
 * (i.e. create a bundle with all required JS files and these inline code snippets,
 *  in the correct order).
 * @param string Javascript
 */
function add_js_headline($headline)
{
	add_headline("<script type=\"text/javascript\">\n\t/* <![CDATA[ */\n\t\t"
		.$headline."\n\t/* ]]> */\n\t</script>");
}


/**
 * Add a CSS headline.
 * This is an extra function, to provide consistent wrapping and allow to bundle it
 * (i.e. create a bundle with all required JS files and these inline code snippets,
 *  in the correct order).
 * @param string CSS
 */
function add_css_headline($headline)
{
	add_headline("<style type=\"text/css\">\n\t".$headline."\n\t</style>");
}


/**
 * Registers all the javascripts needed by the toolbar menu
 *
 * @deprecated because #evo_toolbar doesn't use js anymore, only css is enough
 */
function add_js_for_toolbar( $relative_to = 'rsc_url' )
{
	return true;
}


/**
 * Registers headlines required by AJAX forms, but only if javascript forms are enabled in blog settings.
 */
function init_ajax_forms( $relative_to = 'blog' )
{
	global $Blog;

	if( !empty($Blog) && $Blog->get_setting('ajax_form_enabled') )
	{
		require_js( 'communication.js', $relative_to );
	}
}


/**
 * Registers headlines required by comments forms
 */
function init_ratings_js( $relative_to = 'blog', $force_init = false )
{
	global $Item;

	// fp> Note, the following test is good for $disp == 'single', not for 'posts'
	if( $force_init || ( !empty($Item) && $Item->can_rate() ) )
	{
		require_js( '#jquery#', $relative_to ); // dependency
		require_js( 'jquery/jquery.raty.min.js', $relative_to );
	}
}


/**
 * Registers headlines required to a bubbletip above user login.
 *
 * @param string alias, url or filename (relative to rsc/css, rsc/js) for JS/CSS files
 * @param string Library: 'bubbletip', 'popover'
 */
function init_bubbletip_js( $relative_to = 'rsc_url', $library = 'bubbletip' )
{
	if( ! check_setting( 'bubbletip' ) )
	{ // If setting "bubbletip" is OFF for current case
		return;
	}

	require_js( '#jquery#', $relative_to );

	switch( $library )
	{
		case 'popover':
			// Use popover library of bootstrap
			require_js( 'build/popover.bmin.js', $relative_to, true );
			break;

		case 'bubbletip':
		default:
			// Use bubbletip plugin of jQuery
			require_js( 'jquery/jquery.bubbletip.min.js', $relative_to );
			require_js( 'build/bubbletip.bmin.js', $relative_to, true );
			require_css( 'jquery/jquery.bubbletip.css', $relative_to );
			break;
	}
}


/**
 * Registers headlines required to display a bubbletip to the right of user multi-field.
 *
 * @param string alias, url or filename (relative to rsc/css, rsc/js) for JS/CSS files
 * @param string Library: 'bubbletip', 'popover'
 */
function init_userfields_js( $relative_to = 'rsc_url', $library = 'bubbletip' )
{
	// Load to autocomplete user fields with list type
	require_js( '#jqueryUI#', $relative_to );
	require_css( '#jqueryUI_css#', $relative_to );

	switch( $library )
	{
		case 'popover':
			// Use popover library of bootstrap
			require_js( 'build/popover.bmin.js', $relative_to, true );
			break;

		case 'bubbletip':
		default:
			// Use bubbletip plugin of jQuery
			require_js( 'jquery/jquery.bubbletip.min.js', $relative_to );
			require_js( 'build/bubbletip.bmin.js', $relative_to, true );
			require_css( 'jquery/jquery.bubbletip.css', $relative_to );
			break;
	}
}


/**
 * Registers headlines required to display a bubbletip to the right of plugin help icon.
 *
 * @param string alias, url or filename (relative to rsc/css, rsc/js) for JS/CSS files
 * @param string Library: 'bubbletip', 'popover'
 */
function init_plugins_js( $relative_to = 'rsc_url', $library = 'bubbletip' )
{
	require_js( '#jquery#', $relative_to );

	switch( $library )
	{
		case 'popover':
			// Use popover library of bootstrap
			require_js( 'build/popover.bmin.js', $relative_to, true );
			break;

		case 'bubbletip':
		default:
			// Use bubbletip plugin of jQuery
			require_js( 'jquery/jquery.bubbletip.min.js', $relative_to );
			require_js( 'build/bubbletip.bmin.js', $relative_to, true );
			require_css( 'jquery/jquery.bubbletip.css', $relative_to );
			break;
	}
}


/**
 * Registers headlines for initialization of datepicker inputs
 */
function init_datepicker_js( $relative_to = 'rsc_url' )
{
	require_js( '#jqueryUI#', $relative_to );
	require_css( '#jqueryUI_css#', $relative_to );

	$datefmt = locale_datefmt();
	$datefmt = str_replace( array( 'd', 'j', 'm', 'Y' ), array( 'dd', 'd', 'mm', 'yy' ), $datefmt );
	add_js_headline( 'jQuery(document).ready( function(){
		var monthNames = ["'.T_('January').'","'.T_('February').'", "'.T_('March').'",
						  "'.T_('April').'", "'.T_('May').'", "'.T_('June').'",
						  "'.T_('July').'", "'.T_('August').'", "'.T_('September').'",
						  "'.T_('October').'", "'.T_('November').'", "'.T_('December').'"];

		var dayNamesMin = ["'.T_('Sun').'", "'.T_('Mon').'", "'.T_('Tue').'",
						  "'.T_('Wed').'", "'.T_('Thu').'", "'.T_('Fri').'", "'.T_('Sat').'"];

		var docHead = document.getElementsByTagName("head")[0];
		for (i=0;i<dayNamesMin.length;i++)
			dayNamesMin[i] = dayNamesMin[i].substr(0, 2)

		jQuery(".form_date_input").datepicker({
			dateFormat: "'.$datefmt.'",
			monthNames: monthNames,
			dayNamesMin: dayNamesMin,
			firstDay: '.locale_startofweek().'
		})
	})' );
}


/**
 * Registers headlines for initialization of jQuery Tokeninput plugin
 */
function init_tokeninput_js( $relative_to = 'rsc_url' )
{
	require_js( '#jquery#', $relative_to ); // dependency
	require_js( 'jquery/jquery.tokeninput.js', $relative_to );
	require_css( 'jquery/jquery.token-input-facebook.css', $relative_to );
}


/**
 * Registers headlines for initialization of functions to work with Results tables
 */
function init_results_js( $relative_to = 'rsc_url' )
{
	require_js( '#jquery#', $relative_to ); // dependency
	require_js( 'results.js', $relative_to );
}


/**
 * Registers headlines for initialization of functions to work with Results tables
 */
function init_voting_comment_js( $relative_to = 'rsc_url' )
{
	global $Blog, $b2evo_icons_type;

	if( empty( $Blog ) || ! is_logged_in( false ) || ! $Blog->get_setting('allow_rating_comment_helpfulness') )
	{	// If User is not logged OR Users cannot vote
		return false;
	}

	require_js( '#jquery#', $relative_to ); // dependency
	require_js( 'voting.js', $relative_to );
	add_js_headline( '
	jQuery( document ).ready( function()
	{
		var comment_voting_url = "'.get_secure_htsrv_url().'anon_async.php?action=voting&vote_type=comment&b2evo_icons_type='.$b2evo_icons_type.'";
		jQuery( "span[id^=vote_helpful_]" ).each( function()
		{
			init_voting_bar( jQuery( this ), comment_voting_url, jQuery( this ).find( "#votingID" ).val(), false );
		} );
	} );
	' );
}


/**
 * Registers headlines for initialization of colorpicker inputs
 */
function init_colorpicker_js( $relative_to = 'rsc_url' )
{
	// Inititialize bubbletip plugin
	global $Skin, $AdminUI;
	if( ! empty( $AdminUI ) )
	{ // Get library of tooltip for current back-office skin
		$tooltip_plugin = $AdminUI->get_template( 'tooltip_plugin' );
	}
	elseif( ! empty( $Skin ) )
	{ // Get library of tooltip for current front-office skin
		$tooltip_plugin = $Skin->get_template( 'tooltip_plugin' );
	}
	else
	{ // Use bubbletip library by default for unknown skins
		$tooltip_plugin = 'bubbletip';
	}
	init_bubbletip_js( $relative_to, $tooltip_plugin );

	// Initialize farbastic colorpicker
	require_js( '#jquery#', $relative_to );
	require_js( 'jquery/jquery.farbtastic.min.js', $relative_to );
	require_css( 'jquery/farbtastic/farbtastic.css', $relative_to );
}


/**
 * Registers headlines required to autocomplete the user logins
 *
 * @param string alias, url or filename (relative to rsc/css, rsc/js) for JS/CSS files
 * @param string Library: 'hintbox', 'typeahead'
 */
function init_autocomplete_login_js( $relative_to = 'rsc_url', $library = 'hintbox' )
{
	global $blog;

	require_js( '#jquery#', $relative_to ); // dependency

	switch( $library )
	{
		case 'typeahead':
			// Use typeahead library of bootstrap
			add_js_headline( 'jQuery( document ).ready( function()
			{
				jQuery( "input.autocomplete_login" ).typeahead( null,
				{
					displayKey: "login",
					source: function ( query, cb )
					{
						jQuery.ajax(
						{
							url: "'.get_secure_htsrv_url().'async.php?action=get_login_list",
							type: "post",
							data: { q: query, data_type: "json" },
							dataType: "JSON",
							success: function( logins )
							{
								var json = new Array();
								for( var l in logins )
								{
									json.push( { login: logins[ l ] } );
								}
								cb( json );
							}
						} );
					}
				} );
				'
				// Don't submit a form by Enter when user is editing the owner fields
				.get_prevent_key_enter_js( 'input.autocomplete_login' ).'
			} );' );
			break;

		case 'hintbox':
		default:
			// Use hintbox plugin of jQuery

			// Add jQuery hintbox (autocompletion).
			// Form 'username' field requires the following JS and CSS.
			// fp> TODO: think about a way to bundle this with other JS on the page -- maybe always load hintbox in the backoffice
			//     dh> Handle it via http://www.appelsiini.net/projects/lazyload ?
			// dh> TODO: should probably also get ported to use jquery.ui.autocomplete (or its successor)
			require_css( 'jquery/jquery.hintbox.css', $relative_to );
			require_js( 'jquery/jquery.hintbox.min.js', $relative_to );
			add_js_headline( 'jQuery( document ).on( "focus", "input.autocomplete_login", function()
			{
				var ajax_params = "";
				if( jQuery( this ).hasClass( "only_assignees" ) )
				{
					ajax_params = "&user_type=assignees&blog='.$blog.'";
				}
				jQuery( this ).hintbox(
				{
					url: "'.get_secure_htsrv_url().'async.php?action=get_login_list" + ajax_params,
					matchHint: true,
					autoDimentions: true
				} );
				'
				// Don't submit a form by Enter when user is editing the owner fields
				.get_prevent_key_enter_js( 'input.autocomplete_login' ).'
			} );' );
			break;
	}
}


/**
 * Registers headlines required to jqPlot charts
 *
 * @param string alias, url or filename (relative to rsc/css, rsc/js) for JS/CSS files
 */
function init_jqplot_js( $relative_to = 'rsc_url' )
{
	require_js( '#jquery#', $relative_to ); // dependency
	require_js( '#jqplot#', $relative_to );
	require_js( '#jqplot_barRenderer#', $relative_to );
	require_js( '#jqplot_canvasAxisTickRenderer#', $relative_to );
	require_js( '#jqplot_canvasTextRenderer#', $relative_to );
	require_js( '#jqplot_categoryAxisRenderer#', $relative_to );
	require_js( '#jqplot_enhancedLegendRenderer#', $relative_to );
	require_js( '#jqplot_highlighter#', $relative_to );
	require_js( '#jqplot_canvasOverlay#', $relative_to );
	require_js( '#jqplot_donutRenderer#', $relative_to );
	require_css( '#jqplot_css#', $relative_to );
	require_css( 'jquery/jquery.jqplot.b2evo.css', $relative_to );
}


/**
 * Outputs the collected HTML HEAD lines.
 * @see add_headline()
 * @return string
 */
function include_headlines()
{
	global $headlines;

	if( $headlines )
	{
		echo "\n\t<!-- headlines: -->\n\t".implode( "\n\t", $headlines );
		echo "\n\n";
	}
}


/**
 * Outputs the collected translation lines before </body>
 *
 * yabs > Should this be expanded to similar functionality to headlines?
 *
 * @see add_js_translation()
 */
function include_footerlines()
{
	global $js_translations;
	if( empty( $js_translations ) )
	{ // nothing to do
		return;
	}
	$r = '';

	foreach( $js_translations as $string => $translation )
	{ // output each translation
		if( $string != $translation )
		{ // this is translated
			$r .= '<div><span class="b2evo_t_string">'.$string.'</span><span class="b2evo_translation">'.$translation.'</span></div>'."\n";
		}
	}
	if( $r )
	{ // we have some translations
		echo '<div id="b2evo_translations" style="display:none;">'."\n";
		echo $r;
		echo '</div>'."\n";
	}
}


/**
 * Template tag.
 */
function app_version()
{
	global $app_version;
	echo $app_version;
}


/**
 * Displays an empty or a full bullet based on boolean
 *
 * @param boolean true for full bullet, false for empty bullet
 */
function bullet( $bool )
{
	if( $bool )
		return get_icon( 'bullet_full', 'imgtag' );

	return get_icon( 'bullet_empty', 'imgtag' );
}




/**
 * Stub: Links to previous and next post in single post mode
 */
function item_prevnext_links( $params = array() )
{
	global $disp;

	if( $disp == 'download' )
	{ // Don't display the links on download page
		return;
	}

	global $MainList;

	$params = array_merge( array( 'target_blog' => 'auto' ), $params );

	if( isset( $MainList ) )
	{
		$MainList->prevnext_item_links( $params );
	}
}

/**
 * Stub: Links to previous and next user in single user mode
 */
function user_prevnext_links( $params = array() )
{
	global $UserList, $AdminUI, $Skin;

	$params = array_merge( array(
			'template'     => '$prev$$back$$next$',
			'block_start'  => '<table class="prevnext_user"><tr>',
			'prev_start'   => '<td width="33%">',
			'prev_end'     => '</td>',
			'prev_no_user' => '<td width="33%">&nbsp;</td>',
			'back_start'   => '<td width="33%" class="back_users_list">',
			'back_end'     => '</td>',
			'next_start'   => '<td width="33%" class="right">',
			'next_end'     => '</td>',
			'next_no_user' => '<td width="33%">&nbsp;</td>',
			'block_end'    => '</tr></table>',
			'user_tab'     => 'profile',
		), $params );

	if( !empty( $AdminUI ) )
	{ // Set template from AdminUI
		$user_navigation = $AdminUI->get_template( 'user_navigation' );
	}
	elseif( !empty( $Skin ) )
	{ // Set template from Skin
		$user_navigation = $Skin->get_template( 'user_navigation' );
	}
	if( !empty( $user_navigation ) && is_array( $user_navigation ) )
	{
		$params = array_merge( $params, $user_navigation );
	}

	if( isset($UserList) )
	{
		$UserList->prevnext_user_links( $params );
	}
}


/**
 * Stub
 */
function messages( $params = array() )
{
	global $Messages;

	if( isset( $params['has_errors'] ) )
	{
		$params['has_errors'] = $Messages->has_errors();
	}
	$Messages->disp( $params['block_start'], $params['block_end'] );
}


/**
 * Stub: Links to list pages:
 *
 * @param array Params
 * @param object ItemList2, NULL to use global $MainList
 */
function mainlist_page_links( $params = array(), $ItemList2 = NULL )
{
	if( is_null( $ItemList2 ) )
	{
		global $MainList;
		$ItemList2 = $MainList;
	}

	if( ! empty( $ItemList2 ) )
	{
		$ItemList2->page_links( $params );
	}
}


/**
 * Stub
 *
 * Sets $Item ion global scope
 *
 * @return Item
 */
function & mainlist_get_item()
{
	global $MainList, $featured_displayed_item_IDs;

	if( isset( $MainList ) )
	{
		$Item = & $MainList->get_item();

		if( $Item && in_array( $Item->ID, $featured_displayed_item_IDs ) )
		{ // This post was already displayed as a Featured post, let's skip it and get the next one:
			$Item = & mainlist_get_item();
		}
	}
	else
	{
		$Item = NULL;
	}

	// Make this available globally:
	$GLOBALS['Item'] = & $Item;

	return $Item;
}


/**
 * Stub
 *
 * @return boolean true if empty MainList
 */
function display_if_empty( $params = array() )
{
	global $MainList, $featured_displayed_item_IDs;

	if( isset( $MainList ) && empty( $featured_displayed_item_IDs ) )
	{
		return $MainList->display_if_empty( $params );
	}

	return NULL;
}


/**
 * Template tag for credits
 *
 * Note: You can limit (and even disable) the number of links being displayed here though the Admin interface:
 * Blog Settings > Advanced > Software credits
 *
 * @param array
 */
function credits( $params = array() )
{
	/**
	 * @var AbstractSettings
	 */
	global $global_Cache;
	global $Blog;

	// Make sure we are not missing any param:
	$params = array_merge( array(
			'list_start'  => ' ',
			'list_end'    => ' ',
			'item_start'  => ' ',
			'item_end'    => ' ',
			'separator'   => ',',
			'after_item'  => '#',
		), $params );


	$cred_links = $global_Cache->get( 'creds' );
	if( empty( $cred_links ) )
	{	// Use basic default:
		$cred_links = unserialize('a:2:{i:0;a:2:{i:0;s:24:"http://b2evolution.net/r";i:1;s:3:"CMS";}i:1;a:2:{i:0;s:36:"http://b2evolution.net/web-hosting/r";i:1;s:19:"quality web hosting";}}');
	}

	$max_credits = (empty($Blog) ? NULL : $Blog->get_setting( 'max_footer_credits' ));

	display_list( $cred_links, $params['list_start'], $params['list_end'], $params['separator'], $params['item_start'], $params['item_end'], NULL, $max_credits );

	return $max_credits;	
}


/**
 * Get rating as 5 stars
 *
 * @param integer Number of stars
 * @param string Class name
 * @return string Template for star rating
 */
function get_star_rating( $stars, $class = 'not-used-any-more' )
{
	if( is_null( $stars ) )
	{
		return;
	}

	$average = ceil( ( $stars ) / 5 * 100 );

	return '<div class="star_rating"><div style="width:'.$average.'%">'.$stars.' stars</div></div>';
}


/**
 * Display rating as 5 stars
 *
 * @param integer Number of stars
 * @param string Class name
 */
function star_rating( $stars, $class = 'not-used-any-more' )
{
	echo get_star_rating( $stars, $class );
}


/**
 * Display "powered by b2evolution" logo
 */
function powered_by( $params = array() )
{
	/**
	 * @var AbstractSettings
	 */
	global $global_Cache;

	global $rsc_uri;

	// Make sure we are not missing any param:
	$params = array_merge( array(
			'block_start' => '<div class="powered_by">',
			'block_end'   => '</div>',
			'img_url'     => '$rsc$img/powered-by-b2evolution-120t.gif',
			'img_width'   => '',
			'img_height'  => '',
		), $params );

	echo $params['block_start'];

	$img_url = str_replace( '$rsc$', $rsc_uri, $params['img_url'] );

	$evo_links = $global_Cache->get( 'evo_links' );
	if( empty( $evo_links ) )
	{	// Use basic default:
		$evo_links = unserialize('a:1:{s:0:"";a:1:{i:0;a:3:{i:0;i:100;i:1;s:23:"http://b2evolution.net/";i:2;a:2:{i:0;a:2:{i:0;i:55;i:1;s:26:"powered by b2evolution CMS";}i:1;a:2:{i:0;i:100;i:1;s:29:"powered by an open-source CMS";}}}}}');
	}

	echo resolve_link_params( $evo_links, NULL, array(
			'type'        => 'img',
			'img_url'     => $img_url,
			'img_width'   => $params['img_width'],
			'img_height'  => $params['img_height'],
			'title'       => 'b2evolution CMS',
		) );

	echo $params['block_end'];
}



/**
 * DEPRECATED
 */
function bloginfo( $what )
{
	global $Blog;
	$Blog->disp( $what );
}

/**
 * Display allowed tags for comments
 * (Mainly provided for WP compatibility. Not recommended for use)
 *
 * @param string format
 */
function comment_allowed_tags( $format = 'htmlbody' )
{
	global $comment_allowed_tags;

	echo format_to_output( $comment_allowed_tags, $format );
}

/**
 * DEPRECATED
 */
function link_pages()
{
	echo '<!-- link_pages() is DEPRECATED -->';
}


/**
 * Return a formatted percentage (should probably go to _misc.funcs)
 */
function percentage( $hit_count, $hit_total, $decimals = 1, $dec_point = '.' )
{
	$percentage = $hit_total > 0 ? $hit_count * 100 / $hit_total : 0;
	return number_format( $percentage, $decimals, $dec_point, '' ).'&nbsp;%';
}

function addup_percentage( $hit_count, $hit_total, $decimals = 1, $dec_point = '.' )
{
	static $addup = 0;

	$addup += $hit_count;
	return number_format( $addup * 100 / $hit_total, $decimals, $dec_point, '' ).'&nbsp;%';
}


/**
 * Check if the array given as the first param contains recursion
 *
 * @param array what to check
 * @param array contains object which were already seen
 * @return boolean true if contains recursion false otherwise
 */
function is_recursive( /*array*/ & $array, /*array*/ & $alreadySeen = array() )
{
    static $uniqueObject;
    if( !$uniqueObject )
    {
        $uniqueObject = new stdClass;
    }

    // Set main array as already seen
    $alreadySeen[] = & $array;

    foreach( $array as & $item )
    { // for each item in array
        if( !is_array( $item ) )
        { // if not array, we don't have to check it
            continue;
        }

        // put the unique object into the end of the array
        $item[] = $uniqueObject;
        $recursionDetected = false;
        foreach( $alreadySeen as $candidate )
        {
            if( end( $candidate ) === $uniqueObject )
            { // In the end of an already scanned array is the same unique Obect, this means that recursion was detected
                $recursionDetected = true;
                break;
            }
        }

        array_pop( $item );

        if( $recursionDetected || is_recursive( $item, $alreadySeen ) )
        { // Check until recursion detected or there are not more arrays
            return true;
        }
    }

    return false;
}


/**
 * Display a form (like comment or contact form) through an ajax call
 *
 * @param array params
 */
function display_ajax_form( $params )
{
	global $rsc_url, $samedomain_htsrv_url, $ajax_form_number;

	if( is_recursive( $params ) )
	{ // The params array contains recursion, don't try to encode, display error message instead
		// We don't use translation because this situation should not really happen ( Probably it happesn with some wrong skin )
		echo '<p style="color:red;font-weight:bold">'.T_( 'This section can\'t be displayed because wrong params were created by the skin.' ).'</p>';
		return;
	}

	if( empty( $ajax_form_number ) )
	{ // Set number for ajax form to use unique ID for each new form
		$ajax_form_number = 0;
	}
	$ajax_form_number++;

	echo '<div id="ajax_form_number_'.$ajax_form_number.'" class="section_requires_javascript">';

	// Needs json_encode function to create json type params
	$json_params = evo_json_encode( $params );
	$ajax_loader = '<p class="ajax-loader"><span class="loader_img loader_ajax_form" title="'.T_('Loading...').'"></span><br />'.T_( 'Form is loading...' ).'</p>';
	?>
	<script type="text/javascript">
		// display loader gif until the ajax call returns
		document.write( <?php echo "'".$ajax_loader."'"; ?> );

		var ajax_form_offset_<?php echo $ajax_form_number; ?> = jQuery('#ajax_form_number_<?php echo $ajax_form_number; ?>').offset().top;
		var request_sent_<?php echo $ajax_form_number; ?> = false;

		function get_form_<?php echo $ajax_form_number; ?>()
		{
			jQuery.ajax({
				url: '<?php echo $samedomain_htsrv_url; ?>anon_async.php',
				type: 'POST',
				data: <?php echo $json_params; ?>,
				success: function(result)
					{
						jQuery('#ajax_form_number_<?php echo $ajax_form_number; ?>').html( ajax_debug_clear( result ) );
					}
			});
		}

		function check_and_show_<?php echo $ajax_form_number; ?>()
		{
			var window_scrollTop = jQuery(window).scrollTop();
			var window_height = jQuery(window).height();
			// check if the ajax form is visible, or if it will be visible soon ( 20 pixel )
			if( window_scrollTop >= ajax_form_offset_<?php echo $ajax_form_number; ?> - window_height - 20 )
			{
				if( !request_sent_<?php echo $ajax_form_number; ?> )
				{
					request_sent_<?php echo $ajax_form_number; ?> = true;
					// get the form
					get_form_<?php echo $ajax_form_number; ?>();
				}
			}
		}

		jQuery(window).scroll(function() {
			check_and_show_<?php echo $ajax_form_number; ?>();
		});

		jQuery(document).ready( function() {
			check_and_show_<?php echo $ajax_form_number; ?>();
		});

		jQuery(window).resize( function() {
			check_and_show_<?php echo $ajax_form_number; ?>();
		});
	</script>
	<noscript>
		<?php echo '<p>'.T_( 'This section can only be displayed by javascript enabled browsers.' ).'</p>'; ?>
	</noscript>
	<?php
	echo '</div>';
}


/**
 * Display login form
 *
 * @param array params
 */
function display_login_form( $params )
{
	global $Settings, $Plugins, $Session, $Blog, $blog, $dummy_fields;
	global $secure_htsrv_url, $admin_url, $baseurl, $ReqHost, $redirect_to;

	$params = array_merge( array(
			'form_before' => '',
			'form_after' => '',
			'form_action' => '',
			'form_name' => 'login_form',
			'form_title' => '',
			'form_layout' => '',
			'form_class' => 'bComment',
			'source' => 'inskin login form',
			'inskin' => true,
			'inskin_urls' => true, // Use urls of front-end
			'login_required' => true,
			'validate_required' => NULL,
			'redirect_to' => '',
			'return_to' => '',
			'login' => '',
			'action' => '',
			'reqID' => '',
			'sessID' => '',
			'transmit_hashed_password' => false,
			'display_abort_link'  => true,
			'abort_link_position' => 'above_form', // 'above_form', 'form_title'
			'abort_link_text'     => T_('Abort login!'),
			'display_reg_link'    => false, // Display registration link after login button
		), $params );

	$inskin = $params['inskin'];
	$login = $params['login'];
	$redirect_to = $params['redirect_to'];
	$return_to = $params['return_to'];
	$links = array();
	$form_links = array();

	if( $params['display_abort_link']
		&& empty( $params['login_required'] )
		&& $params['action'] != 'req_validatemail'
		&& strpos( $return_to, $admin_url ) !== 0
		&& strpos( $ReqHost.$return_to, $admin_url ) !== 0 )
	{ // No login required, allow to pass through
		// TODO: dh> validate return_to param?!
		// check if return_to url requires logged in user
		if( empty( $return_to ) || require_login( $return_to, true ) )
		{ // logged in user require for return_to url
			if( !empty( $blog ) )
			{ // blog is set
				if( empty( $Blog ) )
				{
					$BlogCache = & get_BlogCache();
					$Blog = $BlogCache->get_by_ID( $blog, false );
				}
				// set abort url to Blog url
				$abort_url = $Blog->gen_blogurl();
			}
			else
			{ // set abort login url to base url
				$abort_url = $baseurl;
			}
		}
		else
		{ // logged in user isn't required for return_to url, set abort url to return_to
			$abort_url = $return_to;
		}
		// Gets displayed as link to the location on the login form if no login is required
		$abort_link = '<a href="'.htmlspecialchars( url_rel_to_same_host( $abort_url, $ReqHost ) ).'">'.$params['abort_link_text'].'</a>';
		if( $params['abort_link_position'] == 'above_form' )
		{ // Display an abort link under login form
			$links[] = $abort_link;
		}
		elseif( $params['abort_link_position'] == 'form_title' )
		{ // Display an abort link in form title block
			$form_links[] = $abort_link;
		}
	}

	if( ! $inskin && is_logged_in() )
	{ // if we arrive here, but are logged in, provide an option to logout (e.g. during the email validation procedure)
		$links[] = get_user_logout_link();
	}

	if( count( $links ) )
	{
		echo '<div class="evo_form__login_links">'
				.'<div class="floatright">'.implode( $links, ' &middot; ' ).'</div>'
				.'<div class="clear"></div>'
			.'</div>';
	}

	$form_links = count( $form_links ) ? '<span class="pull-right">'.implode( ' ', $form_links ).'</span>' : '';
	echo str_replace( '$form_links$', $form_links, $params['form_before'] );

	$Form = new Form( $params['form_action'] , $params['form_name'], 'post', $params['form_layout'] );

	$Form->begin_form( $params['form_class'] );

	$Form->add_crumb( 'loginform' );
	$source = param( 'source', 'string', $params['source'].' login form' );
	$Form->hidden( 'source', $source );
	$Form->hidden( 'redirect_to', $redirect_to );
	$Form->hidden( 'return_to', $return_to );
	if( $inskin || $params['inskin_urls'] )
	{ // inskin login form
		$Form->hidden( 'inskin', true );
		$separator = '<br />';
	}
	else
	{ // standard login form

		if( ! empty( $params['form_title'] ) )
		{
			echo '<h4>'.$params['form_title'].'</h4>';
		}

		$Form->hidden( 'validate_required', $params[ 'validate_required' ] );
		if( isset( $params[ 'action' ],  $params[ 'reqID' ], $params[ 'sessID' ] ) &&  $params[ 'action' ] == 'validatemail' )
		{ // the user clicked the link from the "validate your account" email, but has not been logged in; pass on the relevant data:
			$Form->hidden( 'action', 'validatemail' );
			$Form->hidden( 'reqID', $params[ 'reqID' ] );
			$Form->hidden( 'sessID', $params[ 'sessID' ] );
		}
		$separator = '';
	}

	// check if should transmit hashed password
	if( $params[ 'transmit_hashed_password' ] )
	{ // used by JS-password encryption/hashing:
		$pwd_salt = $Session->get('core.pwd_salt');
		if( empty($pwd_salt) )
		{ // Do not regenerate if already set because we want to reuse the previous salt on login screen reloads
			// fp> Question: the comment implies that the salt is reset even on failed login attemps. Why that? I would only have reset it on successful login. Do experts recommend it this way?
			// but if you kill the session you get a new salt anyway, so it's no big deal.
			// At that point, why not reset the salt at every reload? (it may be good to keep it, but I think the reason should be documented here)
			$pwd_salt = generate_random_key(64);
			$Session->set( 'core.pwd_salt', $pwd_salt, 86400 /* expire in 1 day */ );
			$Session->dbsave(); // save now, in case there's an error later, and not saving it would prevent the user from logging in.
		}
		$Form->hidden( 'pwd_salt', $pwd_salt );
		// Add container for the hashed password hidden inputs
		echo '<div id="pwd_hashed_container"></div>'; // gets filled by JS
	}

	if( $inskin )
	{
		$Form->begin_field();
		$Form->text_input( $dummy_fields[ 'login' ], $params[ 'login' ], 18, T_('Login'), $separator.T_('Enter your username (or email address).'),
					array( 'maxlength' => 255, 'class' => 'input_text', 'required' => true ) );
		$Form->end_field();
	}
	else
	{
		$Form->text_input( $dummy_fields[ 'login' ], $params[ 'login' ], 18, '', '',
					array( 'maxlength' => 255, 'class' => 'input_text', 'input_required' => 'required', 'placeholder' => T_('Username (or email address)') ) );
	}

	$lost_password_url = get_lostpassword_url( $redirect_to, '&amp;', $return_to );
	if( ! empty( $login ) )
	{
		$lost_password_url = url_add_param( $lost_password_url, $dummy_fields['login'].'='.rawurlencode( $login ) );
	}
	$pwd_note = '<a href="'.$lost_password_url.'">'.T_('Lost your password?').'</a>';

	if( $inskin )
	{
		$Form->begin_field();
		$Form->password_input( $dummy_fields[ 'pwd' ], '', 18, T_('Password'), array( 'note' => $pwd_note, 'maxlength' => 70, 'class' => 'input_text', 'required' => true ) );
		$Form->end_field();
	}
	else
	{
		$Form->password_input( $dummy_fields[ 'pwd' ], '', 18, '', array( 'placeholder' => T_('Password'), 'note' => $pwd_note, 'maxlength' => 70, 'class' => 'input_text', 'input_required' => 'required' ) );
	}

	// Allow a plugin to add fields/payload
	$Plugins->trigger_event( 'DisplayLoginFormFieldset', array( 'Form' => & $Form ) );

	// Display registration link after login button
	$register_link = $params['display_reg_link'] ? get_user_register_link( '', '', T_('Register').' &raquo;', '#', true /*disp_when_logged_in*/, $redirect_to, $source, 'btn btn-primary btn-lg pull-right' ) : '';

	// Submit button(s):
	$submit_buttons = array( array( 'name' => 'login_action[login]', 'value' => T_('Log in!'), 'class' => 'btn-success btn-lg', 'input_suffix' => $register_link ) );

	$Form->buttons_input( $submit_buttons );

	if( $inskin )
	{
		$before_register_link = '<div class="login_actions" style="text-align:right; margin: 1em 0 1ex"><strong>';
		$after_register_link = '</strong></div>';
		user_register_link( $before_register_link, $after_register_link, T_('No account yet? Register here').' &raquo;', '#', true /*disp_when_logged_in*/, $redirect_to, $source );
	}
	else
	{
		// Passthrough REQUEST data (when login is required after having POSTed something)
		// (Exclusion of 'login_action', 'login', and 'action' has been removed. This should get handled via detection in Form (included_input_field_names),
		//  and "action" is protected via crumbs)
		$Form->hiddens_by_key( remove_magic_quotes( $_REQUEST ), array( 'pwd_hashed' ) );
	}

	$Form->end_form();

	echo $params['form_after'];

	display_login_js_handler( $params );
}


/**
 * Display the login form js part, to get the user salt and encrypt the password
 *
 * @param array params
 */
function display_login_js_handler( $params )
{
	global $Blog, $dummy_fields, $Session;

	$params = array_merge( array( 'get_widget_login_hidden_fields' => false ), $params );

?>
	<script type="text/javascript">
	var requestSent = false;
	var login = document.getElementById("<?php echo $dummy_fields[ 'login' ]; ?>");
	if( login.value.length > 0 )
	{ // Focus on the password field:
		document.getElementById("<?php echo $dummy_fields[ 'pwd' ]; ?>").focus();
	}
	else
	{ // Focus on the login field:
		login.focus();
	}

	function processSubmit(e) {
		if (e.preventDefault) e.preventDefault();
		if( requestSent )
		{ // A submit request was already sent, do not send another
			return;
		}

		requestSent = true;
		var form = document.getElementById("login_form");
		var username = form.<?php echo $dummy_fields[ 'login' ]; ?>.value;
		var get_widget_login_hidden_fields = <?php echo $params['get_widget_login_hidden_fields'] ? 'true' : 'false'; ?>;
		var sessionid = '<?php echo $Session->ID; ?>';

		if( !form.<?php echo $dummy_fields[ 'pwd' ]; ?> || !form.pwd_salt || typeof hex_sha1 == "undefined" && typeof hex_md5 == "undefined" ) {
			return true;
		}

		jQuery.ajax({
			type: 'POST',
			url: '<?php echo get_samedomain_htsrv_url(); ?>anon_async.php',
			data: {
				'blogid': '<?php echo $Blog->ID; ?>',
				'<?php echo $dummy_fields[ 'login' ]; ?>': username,
				'action': 'get_user_salt',
				'get_widget_login_hidden_fields': get_widget_login_hidden_fields,
				'crumb_loginsalt': '<?php echo get_crumb('loginsalt'); ?>',
			},
			success: function(result) {
				var pwd_container = jQuery('#pwd_hashed_container');
				var parsed_result;

				try {
					parsed_result = JSON.parse(result);
				} catch( e ) {
					pwd_container.html( result );
					return;
				}

				var raw_password = form.<?php echo $dummy_fields[ 'pwd' ]; ?>.value;
				var salts = parsed_result['salts'];

				if( get_widget_login_hidden_fields )
				{
					form.crumb_loginform.value = parsed_result['crumb'];
					form.pwd_salt.value = parsed_result['pwd_salt'];
					sessionid = parsed_result['session_id'];
				}

				for( var index in salts ) {
					var pwd_hashed = hex_sha1( hex_md5( salts[index] + raw_password ) + form.pwd_salt.value );
					pwd_container.append( '<input type="hidden" value="' + pwd_hashed + '" name="pwd_hashed[]">' );
				}

				form.<?php echo $dummy_fields[ 'pwd' ]; ?>.value = 'padding_padding_padding_padding_padding_padding_hashed_' + sessionid; /* to detect cookie problems */
				// (paddings to make it look like encryption on screen. When the string changes to just one more or one less *, it looks like the browser is changing the password on the fly)

				// Append the correct login action as hidden input field
				pwd_container.append( '<input type="hidden" value="1" name="login_action[login]">' );
				form.submit();
			}
		});

	    // You must return false to prevent the default form behavior
	    return false;
	}

	<?php
	if( $params[ 'transmit_hashed_password' ] )
	{ // Hash the password onsubmit and clear the original pwd field
		// TODO: dh> it would be nice to disable the clicked/used submit button. That's how it has been when the submit was attached to the submit button(s)
		?>
		// Set login form submit handler
		jQuery( '#login_form' ).bind( 'submit', processSubmit );
		<?php
	}
	?>

	</script>
<?php
}


/**
 * Display lost password form
 *
 * @param string Login value
 * @param array login form hidden params
 * @param array Params
 */
function display_lostpassword_form( $login, $hidden_params, $params = array() )
{
	global $secure_htsrv_url, $dummy_fields, $redirect_to, $Session;

	$params = array_merge( array(
			'form_before'     => '',
			'form_after'      => '',
			'form_action'     => $secure_htsrv_url.'login.php',
			'form_name'       => 'lostpass_form',
			'form_class'      => 'fform',
			'form_template'   => NULL,
			'inskin'          => true,
			'inskin_urls'     => true,
			'abort_link_text' => '',
		), $params );

	if( param( 'field_error', 'integer', 0 ) )
	{ // Mark login field as error because it was on page before redirection
		param_error( $dummy_fields['login'], '', '' );
	}

	$login_url = get_login_url( get_param( 'source' ), $redirect_to );

	$form_links = array();
	if( ! empty( $params['abort_link_text'] ) )
	{ // A link to "close" the window
		$form_links[] = '<a href="'.$login_url.'">'.$params['abort_link_text'].'</a>';
	}

	$form_links = count( $form_links ) ? '<span class="pull-right">'.implode( ' ', $form_links ).'</span>' : '';
	echo str_replace( '$form_links$', $form_links, $params['form_before'] );

	$Form = new Form( $params['form_action'], $params['form_name'], 'post', 'fieldset' );

	if( ! empty( $params['form_template'] ) )
	{ // Switch layout to template from array
		$params['form_template']['formstart'] = str_replace( '$form_links$', $form_links, $params['form_template']['formstart'] );

		$Form->switch_template_parts( $params['form_template'] );
	}

	$Form->begin_form( $params['form_class'] );

	// Display hidden fields
	$Form->add_crumb( 'lostpassform' );
	$Form->hidden( 'action', 'retrievepassword' );
	foreach( $hidden_params as $key => $value )
	{
		$Form->hidden( $key, $value );
	}

	$Form->begin_fieldset();

	if( $params['inskin'] )
	{
		$Form->text( $dummy_fields[ 'login' ], $login, 30, T_('Login'), '', 255, 'input_text' );
	}
	else
	{
		$Form->text_input( $dummy_fields[ 'login' ], $login, 30, '', '', array( 'maxlength' => 255, 'placeholder' => T_('Username (or email address)'), 'input_required' => 'required' ) );
	}

	$Form->buttons_input( array( array( /* TRANS: Text for submit button to request an activation link by email */ 'value' => T_('Send me a recovery email!'), 'class' => 'btn-primary btn-lg' ) ) );

	echo '<b>'.T_('How to recover your password:').'</b>';
	echo '<ol>';
	echo '<li>'.T_('Please enter you login (or email address) above.').'</li>';
	echo '<li>'.T_('An email will be sent to your registered email address immediately.').'</li>';
	echo '<li>'.T_('As soon as you receive the email, click on the link therein to change your password.').'</li>';
	echo '<li>'.T_('Your browser will open a page where you can chose a new password.').'</li>';
	echo '</ol>';
	echo '<p class="red"><strong>'.T_('Important: for security reasons, you must do steps 1 and 4 on the same computer and same web browser. Do not close your browser in between.').'</strong></p>';

	$login_link = '<a href="'.$login_url.'" class="floatleft">'.'&laquo; '.T_('Back to login form').'</a>';

	if( $params['inskin'] )
	{
		echo '<div class="login_actions" style="text-align:right; margin: 1em 0 1ex"><strong>';
		echo $login_link;
		echo '</strong></div>';
	}

	$Form->end_fieldset();

	$Form->end_form();

	echo $params['form_after'];

	if( ! $params['inskin'] )
	{
		echo '<div class="evo_form__login_links">';
		echo $login_link;
		echo '<div class="clear"></div>';
		echo '</div>';
	}
}


/**
 * Display user activate info form content
 *
 * @param Object activateinfo Form
 */
function display_activateinfo( $params )
{
	global $current_User, $Settings, $UserSettings, $Plugins;
	global $secure_htsrv_url, $rsc_path, $rsc_url, $dummy_fields;

	if( !is_logged_in() )
	{ // if this happens, it means the code is not correct somewhere before this
		debug_die( "You must log in to see this page." );
	}

	$params = array_merge( array(
			'use_form_wrapper' => true,
			'form_before'      => '',
			'form_after'       => '',
			'form_action'      => $secure_htsrv_url.'login.php',
			'form_name'        => 'form_validatemail',
			'form_class'       => 'fform',
			'form_layout'      => 'fieldset',
			'form_template'    => NULL,
			'form_title'       => '',
			'inskin'           => false,
		), $params );

	// init force request new email address param
	$force_request = param( 'force_request', 'boolean', false );

	// get last activation email timestamp from User Settings
	$last_activation_email_date = $UserSettings->get( 'last_activation_email', $current_User->ID );

	if( $force_request || empty( $last_activation_email_date ) )
	{ // notification email was not sent yet, or user needs another one ( forced request )
		echo $params['use_form_wrapper'] ? $params['form_before'] : '';

		$Form = new Form( $params[ 'form_action' ], $params[ 'form_name' ], 'post', $params[ 'form_layout' ] );

		if( ! empty( $params['form_template'] ) )
		{ // Switch layout to template from array
			$Form->switch_template_parts( $params['form_template'] );
		}

		$Form->begin_form( $params[ 'form_class' ] );

		$Form->add_crumb( 'validateform' );
		$Form->hidden( 'action', 'req_validatemail');
		$Form->hidden( 'redirect_to', $params[ 'redirect_to' ] );
		if( $params[ 'inskin' ] )
		{
			$Form->hidden( 'inskin', $params[ 'inskin' ] );
			$Form->hidden( 'blog', $params[ 'blog' ] );
		}
		else
		{ // Form title in standard form
			echo '<h4>'.$params['form_title'].'</h4>';
		}
		$Form->hidden( 'req_validatemail_submit', 1 ); // to know if the form has been submitted

		$Form->begin_fieldset();

		echo '<ol>';
		echo '<li>'.T_('Please confirm your email address below:').'</li>';
		echo '</ol>';

		// set email text input content only if this is not a forced request. This way the user may have bigger chance to write a correct email address.
		$user_email = ( $force_request ? '' : $current_User->email );
		// fp> note: 45 is the max length for evopress skin.
		$Form->text_input( $dummy_fields[ 'email' ], $user_email, 42, T_('Your email'), '', array( 'maxlength' => 255, 'class' => 'input_text', 'required' => true, 'input_required' => 'required' ) );
		$Form->end_fieldset();

		// Submit button:
		$submit_button = array( array( 'name'=>'submit', 'value'=>T_('Send me a new activation email now!'), 'class'=>'btn-primary btn-lg' ) );

		$Form->buttons_input($submit_button);

		if( !$params[ 'inskin' ] )
		{
			$Plugins->trigger_event( 'DisplayValidateAccountFormFieldset', array( 'Form' => & $Form ) );
		}

		$Form->end_form();

		echo $params['use_form_wrapper'] ? $params['form_after'] : '';

		return;
	}

	// get notification email from general Settings
	$notification_email = $Settings->get( 'notification_sender_email' );
	// convert date to timestamp
	$last_activation_email_ts = mysql2timestamp( $last_activation_email_date );
	// get difference between local time and server time
	$time_difference = $Settings->get('time_difference');
	// get last activation email local date and time
	$last_email_date = date( locale_datefmt(), $last_activation_email_ts + $time_difference );
	$last_email_time = date( locale_shorttimefmt(), $last_activation_email_ts + $time_difference );
	$user_email = $current_User->email;

	echo $params['form_before'];

	if( ! $params['inskin'] )
	{
		echo '<div class="'.$params['form_class'].'">';
	}

	echo '<ol start="1" class="expanded">';
	$instruction =  sprintf( T_('Open your email account for %s and find a message we sent you on %s at %s with the following title:'), $user_email, $last_email_date, $last_email_time );
	echo '<li>'.$instruction.'<br /><b>'.sprintf( T_('Activate your account: %s'), $current_User->login ).'</b>';
	$request_validation_url = 'href="'.regenerate_url( '', 'force_request=1&validate_required=true&redirect_to='.$params[ 'redirect_to' ] ).'"';
	echo '<p>'.sprintf( T_('NOTE: If you don\'t find it, check your "Junk", "Spam" or "Unsolicited email" folders. If you really can\'t find it, <a %s>request a new activation email</a>.'), $request_validation_url ).'</p></li>';
	echo '<li>'.sprintf( T_('Add us (%s) to your contacts to make sure you receive future email notifications, especially when someone sends you a private message.'), '<b><span class="nowrap">'.$notification_email.'</span></b>').'</li>';
	echo '<li><b class="red">'.T_('Click on the activation link in the email.').'</b>';
	echo '<p>'.T_('If this does not work, please copy/paste that link into the address bar of your browser.').'</p>';
	echo '<p>'.sprintf( T_('If you need assistance, please send an email to %s'), '<b><a href="mailto:"'.$notification_email.'"><span class="nowrap">'.$notification_email.'</span></a></b>' ).'</p></li>';
	echo '</ol>';

	if( ( strpos( $user_email, '@hotmail.' ) || strpos( $user_email, '@live.' ) || strpos( $user_email, '@msn.' ) )
		&& file_exists( $rsc_path.'img/login_help/hotmail-validation.png' ) )
	{ // The user is on hotmail and we have a help screen to show him: (needs to be localized and include correct site name)
		echo '<div class="center" style="margin: 2em auto"><img src="'.$rsc_url.'img/login_help/hotmail-validation.png" /></div>';
	}
	elseif( ( strpos( $user_email, '@gmail.com' ) || strpos( $user_email, '@googlemail.com' ) )
		&& file_exists( $rsc_path.'img/login_help/gmail-validation.png' ) )
	{ // The user is on hotmail and we have a help screen to show him: (needs to be localized and include correct site name)
		echo '<div class="center" style="margin: 2em auto"><img src="'.$rsc_url.'img/login_help/gmail-validation.png" /></div>';
	}

	if( ! $params['inskin'] )
	{
		echo '</div>';
	}

	echo $params['form_after'];

	if( $current_User->grp_ID == 1 )
	{ // allow admin users to validate themselves by a single click:
		global $Session, $redirect_to;

		if( empty( $redirect_to ) )
		{ // Set where to redirect
			$redirect_to = regenerate_url();
		}

		echo $params['use_form_wrapper'] ? $params['form_before'] : '';

		$Form = new Form( $secure_htsrv_url.'login.php', 'form_validatemail', 'post', 'fieldset' );

		if( ! empty( $params['form_template'] ) )
		{ // Switch layout to template from array
			$Form->switch_template_parts( $params['form_template'] );
		}

		$Form->begin_form( 'evo_form__login' );

		$Form->add_crumb( 'validateform' );
		$Form->hidden( 'action', 'validatemail' );
		$Form->hidden( 'redirect_to', url_rel_to_same_host( $redirect_to, $secure_htsrv_url ) );
		$Form->hidden( 'reqID', 1 );
		$Form->hidden( 'sessID', $Session->ID );

		echo '<p>'.sprintf( T_('Since you are an admin user, you can activate your account (%s) by a single click.' ), $current_User->email ).'</p>';
		// TODO: the form submit value is too wide (in Konqueror and most probably in IE!)
		$Form->end_form( array( array(
				'name'  => 'form_validatemail_admin_submit',
				'value' => T_('Activate my account!'),
				'class' => 'ActionButton btn btn-primary'
			) ) ); // display hidden fields etc

		echo $params['use_form_wrapper'] ? $params['form_after'] : '';
	}

	echo '<div class="evo_form__login_links floatright">';
	user_logout_link();
	echo '</div>';
}


/*
 * Display javascript password strength indicator bar
 *
 * @param array Params
 */
function display_password_indicator( $params = array() )
{
	global $Blog, $rsc_url, $disp, $dummy_fields;

	$params = array_merge( array(
			'pass1-id'    => $dummy_fields[ 'pass1' ],
			'pass2-id'    => $dummy_fields[ 'pass2' ],
			'login-id'    => $dummy_fields[ 'login' ],
			'email-id'    => $dummy_fields[ 'email' ],
			'field-width' => 140,
			'disp-status' => 1,
			'disp-time'   => 0,
			'blacklist'   => "'b2evo','b2evolution'", // Identify the password as "weak" if it includes any of these words
		), $params );

	echo "<script type='text/javascript'>
	// Load password strength estimation library
	(function(){var a;a=function(){var a,b;b=document.createElement('script');b.src='".$rsc_url."js/zxcvbn.js';b.type='text/javascript';b.async=!0;a=document.getElementsByTagName('script')[0];return a.parentNode.insertBefore(b,a)};null!=window.attachEvent?window.attachEvent('onload',a):window.addEventListener('load',a,!1)}).call(this);

	// Call 'passcheck' function when document is loaded
	if( document.addEventListener )
	{
		document.addEventListener( 'DOMContentLoaded', passcheck, false );
	}
	else
	{
		window.attachEvent( 'onload', passcheck );
	}

	function passcheck()
	{
		var pass1input = jQuery( 'input#".$params['pass1-id']."' );
		if( pass1input.length == 0 ) {
			return; // password field not found
		}

		var pass2input = jQuery( 'input#".$params['pass2-id']."' );
		if( pass2input.length != 0 ) {
			pass2input.css( 'width', '".($params['field-width'] - 2)."px' ); // Set fixed length
		}

		// Prepair password field
		pass1input.css( 'width', '".($params['field-width'] - 2)."px' ); // Set fixed length
		pass1input.attr( 'onkeyup', 'return passinfo(this);' ); // Add onkeyup attribute
		pass1input.parent().append( \"<div id='p-container'><div id='p-result'></div><div id='p-status'></div><div id='p-time'></div></div>\" );

		jQuery( 'head' ).append( '<style>' +
				'#p-container { position: relative; margin-top: 4px; height:5px; border: 1px solid #CCC; font-size: 84%; line-height:normal; color: #999; background: #FFF } ' +
				'#p-result { height:5px } ' +
				'#p-status { position:absolute; width: 100px; top:-5px; left:".($params['field-width']+8)."px } ' +
				'#p-time { position:absolute; width: 400px } ' +
			'</style>'
		);
		jQuery( '#p-container' ).css( 'width', pass1input.outerWidth() - 2 );
		var pass1input_marginleft = parseInt( pass1input.css( 'margin-left' ) );
		if( pass1input_marginleft > 0 )
		{
			jQuery( '#p-container' ).css( 'margin-left', pass1input_marginleft + 'px' );
		}
	}

	function passinfo(el)
	{
		var presult = document.getElementById('p-result');
		var pstatus = document.getElementById('p-status');
		var ptime = document.getElementById('p-time');

		var vlogin = '';
		var login = document.getElementById('".$params['login-id']."');
		if( login != null && login.value != '' ) { vlogin = login.value; }

		var vemail = '';
		var email = document.getElementById('".$params['email-id']."');
		if( email != null && email.value != '' ) { vemail = email.value; }

		// Check the password
		var passcheck = zxcvbn(el.value, [vlogin, vemail, ".$params['blacklist']."]);

		var bar_color = 'red';
		var bar_status = '".format_to_output( T_('Very weak'), 'htmlattr' )."';

		if( el.value.length == 0 ) {
			presult.style.display = 'none';
			pstatus.style.display = 'none';
			ptime.style.display = 'none';
		} else {
			presult.style.display = 'block';
			pstatus.style.display = 'block';
			ptime.style.display = 'block';
		}

		switch(passcheck.score) {
			case 1:
				bar_color = '#F88158';
				bar_status = '".format_to_output( TS_('Weak'), 'htmlattr' )."';
				break;
			case 2:
				bar_color = '#FBB917';
				bar_status = '".format_to_output( TS_('So-so'), 'htmlattr' )."';
				break;
			case 3:
				bar_color = '#8BB381';
				bar_status = '".format_to_output( TS_('Good'), 'htmlattr' )."';
				break;
			case 4:
				bar_color = '#59E817';
				bar_status = '".format_to_output( TS_('Great!'), 'htmlattr' )."';
				break;
		}

		presult.style.width = (passcheck.score * 20 + 20)+'%';
		presult.style.background = bar_color;

		if( ".$params['disp-status']." ) {
			pstatus.innerHTML = bar_status;
		}
		if( ".$params['disp-time']." ) {
			document.getElementById('p-time').innerHTML = '".TS_('Estimated crack time').": ' + passcheck.crack_time_display;
		}
	}

	jQuery( 'input#".$params[ 'pass1-id' ].", input#".$params[ 'pass2-id' ]."' ).keyup( function()
	{	// Validate passwords
		if( jQuery( 'input#".$params[ 'pass2-id' ]."' ).val() != jQuery( 'input#".$params[ 'pass1-id' ]."' ).val() )
		{	// Passwords are different
			jQuery( '#pass2_status' ).html( '".get_icon( 'xross' )." ".TS_('The second password is different from the first.')."' );
		}
		else
		{
			jQuery( '#pass2_status' ).html( '' );
		}
	} );
</script>";
}


/*
 * Display javascript login validator
 *
 * @param array Params
 */
function display_login_validator( $params = array() )
{
	global $rsc_url, $dummy_fields;

	$params = array_merge( array(
			'login-id' => $dummy_fields[ 'login' ],
		), $params );

	echo '<script type="text/javascript">
	var login_icon_load = \'<img src="'.$rsc_url.'img/ajax-loader.gif" alt="'.TS_('Loading...').'" title="'.TS_('Loading...').'" style="margin:2px 0 0 5px" align="top" />\';
	var login_icon_available = \''.get_icon( 'allowback', 'imgtag', array( 'title' => TS_('This username is available.') ) ).'\';
	var login_icon_exists = \''.get_icon( 'xross', 'imgtag', array( 'title' => TS_('This username is already in use. Please choose another one.') ) ).'\';

	var login_text_empty = \''.TS_('Choose an username.').'\';
	var login_text_available = \''.TS_('This username is available.').'\';
	var login_text_exists = \''.TS_('This username is already in use. Please choose another one.').'\';

	jQuery( "#register_form input#'.$params[ 'login-id' ].'" ).change( function()
	{	// Validate if username is available
		var note_Obj = jQuery( this ).next().next();
		if( jQuery( this ).val() == "" )
		{	// Login is empty
			jQuery( "#login_status" ).html( "" );
			note_Obj.html( login_text_empty ).attr( "class", "notes" );
		}
		else
		{	// Validate login
			jQuery( "#login_status" ).html( login_icon_load );
			jQuery.ajax( {
				type: "POST",
				url: "'.get_samedomain_htsrv_url().'anon_async.php",
				data: "action=validate_login&login=" + jQuery( this ).val(),
				success: function( result )
				{
					result = ajax_debug_clear( result );
					if( result == "exists" )
					{	// Login already exists
						jQuery( "#login_status" ).html( login_icon_exists );
						note_Obj.html( login_text_exists ).attr( "class", "notes red" );
					}
					else if( result == "available" )
					{	// Login is available
						jQuery( "#login_status" ).html( login_icon_available );
						note_Obj.html( login_text_available ).attr( "class", "notes green" );
					}
					else
					{	// Errors
						jQuery( "#login_status" ).html( login_icon_exists );
						note_Obj.html( result ).attr( "class", "notes red" );
					}
				}
			} );
		}
	} );
</script>';
}


/*
 * Display javascript to quick edit field by AJAX
 * Used to edit fields such as 'order' by one click on value in table list
 *
 * @param array Params
 */
function init_field_editor_js( $params = array() )
{
	// Make sure we are not missing any param:
	$params = array_merge( array(
			'field_prefix' => 'order-',
			'action_url'   => '',
			'question'     => TS_("Do you want discard your changes for this order field?"),
			'relative_to'  => 'rsc_url',
		), $params );

	require_js( '#jquery#', $params['relative_to'] ); // dependency

	add_js_headline( 'jQuery( document ).on( "click", "[id^='.$params['field_prefix'].']", function()
{
	if( jQuery( this ).find( "input" ).length > 0 )
	{ // This order field is already editing now
		return false;
	}

	// Create <input> to edit order field
	var input = document.createElement( "input" )
	var $input = jQuery( input );
	$input.val( jQuery( this ).html() );
	$input.css( {
		width: jQuery( this ).width() - 2,
		height: jQuery( this ).height() - 2,
		padding: "0",
		"text-align": "center"
	} );

	// Save current value
	jQuery( this ).attr( "rel", jQuery( this ).html() );

	// Replace statis value with <input>
	jQuery( this ).html( "" ).append( $input );
	$input.focus();

	// Bind events for <input>
	$input.bind( "keydown", function( e )
	{
		var key = e.keyCode;
		var parent_obj = jQuery( this ).parent();
		if( key == 27 )
		{ // "Esc" key
			parent_obj.html( parent_obj.attr( "rel" ) );
		}
		else if( key == 13 )
		{ // "Enter" key
			results_ajax_load( jQuery( this ), "'.$params['action_url'].'" + parent_obj.attr( "id" ) + "&new_value=" + jQuery( this ).val() );
		}
	} );

	$input.bind( "blur", function()
	{
		var revert_changes = false;

		var parent_obj = jQuery( this ).parent();
		if( parent_obj.attr( "rel" ) != jQuery( this ).val() )
		{ // Value was changed, ask about saving
			if( confirm( "'.$params['question'].'" ) )
			{
				revert_changes = true;
			}
		}
		else
		{
			revert_changes = true;
		}

		if( revert_changes )
		{ // Revert the changed value
			parent_obj.html( parent_obj.attr( "rel" ) );
		}
	} );

	return false;
} );' );
}


/**
 * Registers headlines for initialization of functions to autocomplete usernames in textarea
 */
function init_autocomplete_usernames_js( $relative_to = 'rsc_url' )
{
	if( is_admin_page() )
	{ // Check to enable it in back-office
		global $Blog;
		if( empty( $Blog ) || ! $Blog->get_setting( 'autocomplete_usernames' ) )
		{ // Blog setting doesn't allow to autocomplete usernames
			return;
		}
	}
	else
	{ // Check to enable it in front-office
		global $Item, $Skin, $disp;
		if( ! empty( $Skin ) && ! $Skin->get_setting( 'autocomplete_usernames' ) )
		{ // Skin disables to autocomplete usernames
			return;
		}
		if( $disp != 'edit' && $disp != 'edit_comment' && ( empty( $Item ) || ! $Item->can_comment( NULL ) ) )
		{ // It is not the edit post/comment form and No form to comment of this post
			return;
		}
	}

	require_js( '#jquery#', $relative_to );
	require_js( 'build/textcomplete.bmin.js', $relative_to );
	require_css( 'jquery/jquery.textcomplete.css', $relative_to );
}


/**
 * Get JS code to prevent event of the key "Enter" for selected elements,
 * Used to stop form submitting by enter on some input fields
 *
 * @param string Selection for jQuery selector
 */
function get_prevent_key_enter_js( $jquery_selection )
{
	if( empty( $jquery_selection ) )
	{ // jQuery selection must be filled
		return '';
	}

	return 'jQuery( "'.$jquery_selection.'" ).keypress( function( e ) { if( e.keyCode == 13 ) return false; } );';
}


/**
 * Initialize CSS and JS to use font-awesome icons
 *
 * @param string Icons type:
 *               - 'fontawesome' - Use only font-awesome icons
 *               - 'fontawesome-glyphicons' - Use font-awesome icons as a priority over the glyphicons
 */
function init_fontawesome_icons( $icons_type = 'fontawesome' )
{
	global $b2evo_icons_type;

	// Use font-awesome icons, @see get_icon()
	$b2evo_icons_type = $icons_type;

	// Load main CSS file of font-awesome icons
	require_css( '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css' );
}

?>
