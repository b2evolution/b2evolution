<?php
/**
 * This file is the template that includes required css files to display in-skin login form
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 *
 * @version $Id: login.main.php 7043 2014-07-02 08:35:45Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $current_User;

if( is_logged_in() )
{ // User is already logged in
	if( $current_User->check_status( 'can_be_validated' ) )
	{ // account is not active yet, redirect to the account activation page
		$Messages->add( T_( 'You are logged in but your account is not activated. You will find instructions about activating your account below:' ) );
		header_redirect( get_activate_info_url(), 302 );
		// will have exited
	}

	// User is already logged in, redirect to "redirect_to" page
	$Messages->add( T_( 'You are already logged in.' ), 'note' );
	$redirect_to = param( 'redirect_to', 'url', NULL );
	if( empty( $redirect_to ) )
	{ // If empty redirect to referer page
		$redirect_to = '';
	}
	header_redirect( $redirect_to, 302 );
	// will have exited
}

require $ads_current_skin_path.'index.main.php';

?>