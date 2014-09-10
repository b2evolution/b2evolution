<?php
/**
 * This file is the template that makes the required initalization before display activateinfo
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 *
 * @version $Id: activateinfo.main.php 7043 2014-07-02 08:35:45Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Messages, $Settings, $Session, $current_User;

if( !is_logged_in() )
{ // Redirect to the login page for anonymous users
	$Messages->add( T_( 'You must log in before you can activate your account.' ) );
	header_redirect( get_login_url('cannot see messages'), 302 );
	// will have exited
}

if( !$current_User->check_status( 'can_be_validated' ) )
{ // don't display activateinfo screen
	$after_email_validation = $Settings->get( 'after_email_validation' );
	if( $after_email_validation == 'return_to_original' )
	{ // we want to return to original page after account activation
		// check if Session 'validatemail.redirect_to' param is still set
		$redirect_to = $Session->get( 'core.validatemail.redirect_to' );
		if( empty( $redirect_to ) )
		{ // Session param is empty try to get general redirect_to param
			$redirect_to = param( 'redirect_to', 'url', '' );
		}
		else
		{ // cleanup validateemail.redirect_to param from session
			$Session->delete('core.validatemail.redirect_to');
		}
	}
	else
	{ // go to after email validation url which is set in the user general settings form
		$redirect_to = $after_email_validation;
	}
	if( empty( $redirect_to ) || preg_match( '#disp=activateinfo#', $redirect_to ) )
	{ // redirect_to is pointing to the activate info display or is empty
	  // redirect to referer page
		$redirect_to = '';
	}

	if( $current_User->check_status( 'is_validated' ) )
	{
		$Messages->add( T_( 'Your account has already been activated.' ) );
	}
	header_redirect( $redirect_to, 302 );
	// will have exited
}

// user is logged in but his/her account is not active yet, this is the case whe we have to dispaly the activatinfo screen
require $ads_current_skin_path.'index.main.php';

?>