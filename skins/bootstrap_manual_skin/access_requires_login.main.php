<?php
/**
 * This file is the template that displays "login required" for non logged-in users.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * @package evoskins
 * @subpackage bootstrap_manual
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $app_version, $disp, $Skin, $hide_widget_container;

if( version_compare( $app_version, '6.4' ) < 0 )
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
	die( 'This skin is designed for b2evolution 6.4 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}


// Hide the widget containers depending on skin settings
$hide_widget_container = array(
		'header'   => ! $Skin->is_visible_container( 'header' ),
		'page_top' => ! $Skin->is_visible_container( 'page_top' ),
		'menu'     => ! $Skin->is_visible_container( 'menu' ),
		'sidebar'  => ! $Skin->is_visible_container( 'sidebar' ),
		'sidebar2' => ! $Skin->is_visible_container( 'sidebar2' ),
		'footer'   => ! $Skin->is_visible_container( 'footer' ),
	);

global $bootstrap_manual_posts_text;

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php', array() );
// -------------------------------- END OF HEADER --------------------------------


// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------

// -------------------------- BODY HEADER INCLUDED HERE --------------------------
skin_include( '_body_header.inc.php' );
// Note: You can customize the default BODY header by copying the generic
// /skins/_body_header.inc.php file into the current skin folder.
// -------------------------------- END OF BODY HEADER ---------------------------
?>


<?php
	// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
	skin_include( '$disp$', $Skin->get_template( 'disp_params' ) );
	// Note: you can customize any of the sub templates included here by
	// copying the matching php file into your skin directory.
	// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
?>


<?php
// -------------------------- BODY FOOTER INCLUDED HERE --------------------------
skin_include( '_body_footer.inc.php' );
// Note: You can customize the default BODY footer by copying the generic
// /skins/_body_footer.inc.php file into the current skin folder.
// -------------------------------- END OF BODY FOOTER ---------------------------

// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
siteskin_include( '_site_body_footer.inc.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------


// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// ------------------------------- END OF FOOTER --------------------------------
?>