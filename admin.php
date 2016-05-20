<?php
/**
 * This is the main dispatcher for the admin interface, a.k.a. The Back-Office.
 *
 * ---------------------------------------------------------------------------------------------------------------
 * IF YOU ARE READING THIS IN YOUR WEB BROWSER, IT MEANS THAT YOU DID NOT LOAD THIS FILE THROUGH A PHP WEB SERVER. 
 * TO GET STARTED, GO TO THIS PAGE: http://b2evolution.net/man/getting-started
 * ---------------------------------------------------------------------------------------------------------------
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package main
 */


/**
 * Do the MAIN initializations:
 */
require_once dirname(__FILE__).'/conf/_config.php';


/**
 * @global boolean Is this an admin page? Use {@link is_admin_page()} to query it, because it may change.
 */
$is_admin_page = true;

// user must be logged in and his/her account must be validated before access to admin
$login_required = true;
$validate_required = true;
require_once $inc_path.'_main.inc.php';


// Check global permission:
if( ! $current_User->check_perm( 'admin', 'restricted' ) )
{	// No permission to access admin...
	// asimo> This should always denied access, but we insert a hack to create a temporary solution
	// We do allow comments and items actions, if the redirect is set to the front office! This way users without admin access may use the comments, and items controls.
	$test_ctrl = param( 'ctrl', '/^[a-z0-9_]+$/', '', false );
	$test_redirect_to = param( 'redirect_to', 'url', '', false );
	$test_action = param_action();
	// asimo> If we also would like to allow publish, deprecate and delete item/comment actions for users without admin access, we must uncomment the commented part below.
	if( ( ( $test_ctrl !== 'comments' ) && ( $test_ctrl !== 'items' ) )
		|| empty( $test_redirect_to ) || ( strpos( $test_redirect_to, $admin_url ) === 0 )
		|| empty( $test_action ) || !in_array( $test_action, array( 'update', 'publish'/*, 'deprecate', 'delete'*/ ) ) )
	{
		require $adminskins_path.'_access_denied.main.php';
	}
}

// Check user email is validated to make sure users can never has access to admin without a validated email address
if( !$current_User->check_status( 'can_access_admin' ) )
{
	if( $current_User->check_status( 'can_be_validated' ) )
	{ // redirect back to the login page
		$action = 'req_validatemail';
		require $htsrv_path.'login.php';
	}
	else
	{ // show access denied
		require $adminskins_path.'_access_denied.main.php';
	}
}

// Check that the request doesn't exceed the post max size
// This is required because another way not even the $ctrl param can be initialized and the request may freeze
check_post_max_size_exceeded();

/*
 * Get the blog from param, defaulting to the last selected one for this user:
 * we need it for quite a few of the menu urls
 */
if( isset($collections_Module) )
{
	$user_selected_blog = (int)$UserSettings->get('selected_blog');
	$BlogCache = & get_BlogCache();
	if( param( 'blog', 'integer', NULL, true ) === NULL      // We got no explicit blog choice (not even '0' for 'no blog'):
		|| ($blog > 0 && ! ($Blog = & $BlogCache->get_by_ID( $blog, false, false )) )) // or we requested a nonexistent blog
	{ // Try the memorized blog from the previous action:
		$blog = $user_selected_blog;
		if( ! ( $Blog = & $BlogCache->get_by_ID( $blog, false, false ) ) )
		{ // That one doesn't exist either...
			$blog = 0;
			// Unset $Blog because otherwise isset( $Blog ) returns true and it may cause issues later
			unset( $Blog );
		}
	}
	elseif( $blog != $user_selected_blog )
	{ // We have selected a new & valid blog. Update UserSettings for selected blog:
		set_working_blog( $blog );
	}
}

// bookmarklet, upload (upload actually means sth like: select img for post):
param( 'mode', 'string', '', true );


/*
 * Get the Admin skin
 * TODO: Allow setting through GET param (dropdown in backoffice), respecting a checkbox "Use different setting on each computer" (if cookie_state handling is ready)
 */
$admin_skin = $UserSettings->get( 'admin_skin' );
$admin_skin_path = $adminskins_path.'%s/_adminUI.class.php';

if( ! $admin_skin || ! file_exists( sprintf( $admin_skin_path, $admin_skin ) ) )
{ // there's no skin for the user
	if( !$admin_skin )
	{
		$Debuglog->add( 'The user has no admin skin set.', 'skins' );
	}
	else
	{
		$Debuglog->add( 'The admin skin ['.$admin_skin.'] set by the user does not exist.', 'skins' );
	}

	$admin_skin = $Settings->get( 'admin_skin' );

	if( !$admin_skin || !file_exists( sprintf( $admin_skin_path, $admin_skin ) ) )
	{ // even the default skin does not exist!
		if( !$admin_skin )
		{
			$Debuglog->add( 'There is no default admin skin set!', 'skins' );
		}
		else
		{
			$Debuglog->add( 'The default admin skin ['.$admin_skin.'] does not exist!', array('skin','error') );
		}

		// Get the first one available one:
		$admin_skin_dirs = get_admin_skins();

		if( $admin_skin_dirs === false )
		{
			$Debuglog->add( 'No admin skin found! Check that the path '.$adminskins_path.' exists.', array('skin','error') );
		}
		elseif( empty($admin_skin_dirs) )
		{ // No admin skin directories found
			$Debuglog->add( 'No admin skin found! Check that there are skins in '.$adminskins_path.'.', array('skin','error') );
		}
		else
		{
			$admin_skin = array_shift($admin_skin_dirs);
			$Debuglog->add( 'Falling back to first available skin.', 'skins' );
		}
	}
}
if( ! $admin_skin )
{
	$Debuglog->display( 'No admin skin available!', '', true, 'skins' );
	die(1);
}

$Debuglog->add( 'Using admin skin &laquo;'.$admin_skin.'&raquo;', 'skins' );

/**
 * Load the AdminUI class for the skin.
 */
require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
/**
 * This is the Admin UI object which handles the UI for the backoffice.
 *
 * @global AdminUI
 */
$AdminUI = new AdminUI();


/*
 * Pass over to controller...
 */

// Get requested controller and memorize it:
param( 'ctrl', '/^[a-z0-9_]+$/', $default_ctrl, true );

if( empty( $dont_request_controller ) || !$dont_request_controller )
{	// Don't request the controller if we want initialize only the admin configs above (Used on AJAX refreshing of results table)

	// Redirect old-style URLs (e.g. /admin/plugins.php), if they come here because the webserver maps "/admin/" to "/admin.php"
	// NOTE: this is just meant as a transformation from pre-1.8 to 1.8!
	if( ! empty( $_SERVER['PATH_INFO'] ) && $_SERVER['PATH_INFO'] != $_SERVER['PHP_SELF'] ) // the "!= PHP_SELF" check seems needed by IIS..
	{
		// Try to find the appropriate controller (ctrl) setting
		foreach( $ctrl_mappings as $k => $v )
		{
			if( preg_match( '~'.preg_quote( $_SERVER['PATH_INFO'], '~' ).'$~', $v ) )
			{
				$ctrl = $k;
				break;
			}
		}

		// Sanitize QUERY_STRING
		if( ! empty( $_SERVER['QUERY_STRING'] ) )
		{
			$query_string = explode( '&', $_SERVER['QUERY_STRING'] );
			foreach( $query_string as $k => $v )
			{
				$query_string[$k] = strip_tags($v);
			}
			$query_string = '&'.implode( '&', $query_string );
		}
		else
		{
			$query_string = '';
		}

		header_redirect( url_add_param( $admin_url, 'ctrl='.$ctrl.$query_string, '&' ), true );
		exit(0);
	}


	// Check matching controller file:
	if( !isset($ctrl_mappings[$ctrl]) )
	{
		debug_die( 'The requested controller ['.$ctrl.'] does not exist.' );
	}

	// Call the requested controller:
	require $inc_path.$ctrl_mappings[$ctrl];
}

?>