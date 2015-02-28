<?php
/**
 * This is the main/default page template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * The main page template is used to display the blog when no specific page template is available
 * to handle the request (based on $disp).
 *
 * @package evoskins
 * @subpackage pluralism
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( version_compare( $app_version, '2.4.1' ) < 0 )
{ // Older 2.x skins work on newer 2.x b2evo versions, but newer 2.x skins may not work on older 2.x b2evo versions.
	die( 'This skin is designed for b2evolution 2.4.1 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );

	// -------------------------- HTML HEADER INCLUDED HERE --------------------------
	skin_include( '_html_header.inc.php' );
	// Note: You can customize the default HTML header by copying the generic
	// /skins/_html_header.inc.php file into the current skin folder.
	// -------------------------------- END OF HEADER --------------------------------

	// ------------------------- BODY HEADER INCLUDED HERE --------------------------
	skin_include( '_body_header.inc.php' );
	// Note: You can customize the default BODY header by copying the generic
	// /skins/_body_footer.inc.php file into the current skin folder.
	// ------------------------------- END OF HEADER --------------------------------
?>

<div id="content">

<?php
	// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
	messages( array(
		'block_start' => '<div class="action_messages">',
		'block_end'   => '</div>',
	) );
	// --------------------------------- END OF MESSAGES ---------------------------------

	// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
	request_title( array(
			'title_before'      => '<h2 id="spectitle">',
			'title_after'       => '</h2>',
			'title_none'        => '',
			'glue'              => ' - ',
			'title_single_disp' => true,
			'format'            => 'htmlbody',
		) );
	// ------------------------------ END OF REQUEST TITLE -----------------------------
?>

<?php
	// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
	skin_include( '$disp$', array(
			'author_link_text' => 'preferredname',
		) );
	// Note: you can customize any of the sub templates included here by
	// copying the matching php file into your skin directory.
	// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
?>

</div>

<?php
	// ------------------------- SIDEBAR INCLUDED HERE --------------------------
	skin_include( '_sidebar.inc.php' );
	// Note: You can customize the default BODY footer by copying the
	// _body_footer.inc.php file into the current skin folder.
	// ----------------------------- END OF SIDEBAR -----------------------------

	// ------------------------- BODY FOOTER INCLUDED HERE --------------------------
	skin_include( '_body_footer.inc.php' );
	// Note: You can customize the default BODY footer by copying the
	// _body_footer.inc.php file into the current skin folder.
	// ------------------------------- END OF FOOTER --------------------------------

	// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
	skin_include( '_html_footer.inc.php' );
	// Note: You can customize the default HTML footer by copying the
	// _html_footer.inc.php file into the current skin folder.
	// ------------------------------- END OF FOOTER --------------------------------
?>
