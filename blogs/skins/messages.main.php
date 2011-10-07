<?php
/**
 * This file is the template that includes required css files to display messages
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

// The messaging module can only be used by logged in users:
if( ! is_logged_in() )
{ // Redirect to the login page for anonymous users
	header_redirect( get_login_url(), 302 );
	// will have exited
}

if( !$current_User->check_perm( 'perm_messaging', 'reply' ) )
{ // Redirect to the blog url for users without messaging permission
	$Messages->add( 'You are not allowed to view Messages!' );
	header_redirect( $Blog->gen_blogurl(), 302 );
	// will have exited
}

add_js_headline( "// Paths used by JS functions:
		var bgxy_expand = '".get_icon( 'expand', 'xy' )."';
		var bgxy_collapse = '".get_icon( 'collapse', 'xy' )."';
		var htsrv_url = '$htsrv_url';" );

// Require results.css to display message query results in a table
require_css( 'results.css' );

require $ads_current_skin_path.'index.main.php';

/*
 * $Log$
 * Revision 1.4  2011/10/07 05:43:45  efy-asimo
 * Check messaging availability before display
 *
 * Revision 1.3  2011/10/02 12:38:33  efy-yurybakh
 * fix sprite icons
 *
 * Revision 1.2  2011/09/04 22:13:24  fplanque
 * copyright 2011
 *
 * Revision 1.1  2011/08/11 09:05:10  efy-asimo
 * Messaging in front office
 *
 */
?>
