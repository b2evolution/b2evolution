<?php
/**
 * This file is the template that includes required css files to display a user profile
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Messages;

if( ! is_logged_in() && ! $Settings->get( 'allow_anonymous_user_profiles' ) )
{	// Redirect to the login page if not logged in and allow anonymous user setting is OFF
	$redirect_to = $Blog->get( 'usersurl' );
	$Messages->add( T_( 'You must log in to view this user profile.' ) );
	header_redirect( get_login_url( 'cannot see user', $redirect_to ), 302 );
}

require $ads_current_skin_path.'index.main.php';

/*
 * $Log$
 * Revision 1.2  2011/10/17 12:14:14  efy-asimo
 * User main dispaly - update
 *
 */
?>
