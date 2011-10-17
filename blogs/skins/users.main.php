<?php
/**
 * This file is the template that includes required css files to display users
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

global $htsrv_url, $Messages;

if( ! is_logged_in() && ! $Settings->get( 'allow_anonymous_user_list' ) )
{	// Redirect to the login page if not logged in and allow anonymous user setting is OFF
	$redirect_to = $Blog->get( 'usersurl' );
	$Messages->add( T_( 'You must log in to view the user directory.' ) );
	header_redirect( get_login_url( 'cannot see users', $redirect_to ), 302 );
}

// var bgxy_expand is used by toggle_filter_area() and toggle_clickopen()
// var htsrv_url is used for AJAX callbacks
add_js_headline( "// Paths used by JS functions:
		var bgxy_expand = '".get_icon( 'expand', 'xy' )."';
		var bgxy_collapse = '".get_icon( 'collapse', 'xy' )."';
		var htsrv_url = '$htsrv_url';" );

// Require results.css to display thread query results in a table
require_css( 'results.css' );

require $ads_current_skin_path.'index.main.php';

/*
 * $Log$
 * Revision 1.9  2011/10/17 22:00:30  fplanque
 * cleanup
 *
 * Revision 1.8  2011/10/16 20:34:16  fplanque
 * To whomever forgot to check in this file: please check that this version is correct.
 *
 * Revision 1.7  2011/10/13 17:40:53  fplanque
 * no message
 *
 * Revision 1.6  2011/10/11 06:38:50  efy-asimo
 * Add corresponding error messages when login required
 *
 * Revision 1.5  2011/10/10 20:46:39  fplanque
 * registration source tracking
 *
 * Revision 1.4  2011/10/07 06:12:22  efy-asimo
 * Check users availability before display
 *
 * Revision 1.3  2011/10/07 02:55:37  fplanque
 * doc
 *
 * Revision 1.2  2011/10/02 12:38:33  efy-yurybakh
 * fix sprite icons
 *
 * Revision 1.1  2011/09/30 12:24:56  efy-yurybakh
 * User directory
 */
?>
