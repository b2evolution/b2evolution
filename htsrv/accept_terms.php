<?php
/**
 * This file acceprt terms & conditions for current user
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package htsrv
 */


/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

param( 'blog', 'integer', 0 );
param( 'redirect_to', 'url', '' );
if( empty( $redirect_to ) )
{	// If a redirect param was not defined on submitted form then redirect to site url:
	$redirect_to = $baseurl;
}

// Activate the blog locale because all params were introduced with that locale
activate_blog_locale( $blog );

/**
 * Basic security checks:
 */
if( ! is_logged_in() )
{	// must be logged in!
	bad_request_die( T_( 'You are not logged in.' ) );
}

if( $current_User->must_accept_terms() )
{	// Update settings of current user to mark as terms accepted:
	$UserSettings->set( 'terms_accepted', '1', $current_User->ID );
	$UserSettings->dbupdate();
}

// Redirect to requested page before viewing terms & conditions:
header_redirect( $redirect_to );
// EXITED
?>