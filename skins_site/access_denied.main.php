<?php
/**
 * This file is the template that includes required css files to display an access denied
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package siteskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $disp;

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_html_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------


// ---------------------------- TOOLBAR INCLUDED HERE ----------------------------
require skin_fallback_path( '_toolbar.inc.php' );
// ------------------------------- END OF TOOLBAR --------------------------------
echo "\n";
if( show_toolbar() )
{
	echo '<div id="skin_wrapper" class="skin_wrapper_loggedin">';
}
else
{
	echo '<div id="skin_wrapper" class="skin_wrapper_anonymous">';
}
echo "\n";


// ---------------------------- SITE BODY HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE BODY HEADER --------------------------------


// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
messages( array(
	'block_start' => '<div class="action_messages">',
	'block_end'   => '</div>',
) );
// --------------------------------- END OF MESSAGES ---------------------------------


// ---------------------------- SITE BODY FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
siteskin_include( '_site_body_footer.inc.php' );
// ------------------------------- END OF SITE BODY FOOTER --------------------------------


echo '</div>';// End of skin_wrapper


// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_html_footer.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------

?>