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

global $htsrv_url;

if( ! is_logged_in() && ! $Settings->get( 'allow_anonymous_user_list' ) )
{	// Redirect to the login page if not logged in and allow anonymous user setting is OFF
	$redirect_to = $Blog->get( 'usersurl' );
	header_redirect( get_login_url( $redirect_to ), 302 );
}

add_js_headline( "// Paths used by JS functions:
		var bgxy_expand = '".get_icon( 'expand', 'xy' )."';
		var bgxy_collapse = '".get_icon( 'collapse', 'xy' )."';
		var htsrv_url = '$htsrv_url';" );

// Require results.css to display thread query results in a table
require_css( 'results.css' );

require $ads_current_skin_path.'index.main.php';

/*
 * $Log$
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
