<?php
/**
 * This is the main/default page template for the "manual" skin.
 *
 * This skin only uses one single template which includes most of its features.
 * It will also rely on default includes for specific dispays (like the comment form).
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * The main page template is used to display the blog when no specific page template is available
 * to handle the request (based on $disp).
 *
 * @package evoskins
 * @subpackage manual
 *
 * @version $Id: catdir.main.php 7044 2014-07-02 08:55:10Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( version_compare( $app_version, '5.0' ) < 0 )
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
	die( 'This skin is designed for b2evolution 5.0 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}


// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );

// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php' );
// Note: You can customize the default HTML header by copying the generic
// /skins/_html_header.inc.php file into the current skin folder.
// -------------------------------- END OF HTML HEADER ---------------------------

// -------------------------- BODY HEADER INCLUDED HERE --------------------------
skin_include( '_body_header.inc.php' );
// Note: You can customize the default BODY header by copying the generic
// /skins/_body_header.inc.php file into the current skin folder.
// -------------------------------- END OF BODY HEADER ---------------------------

// -------------------------- LEFT NAVIGATION BAR INCLUDED HERE ------------------
skin_include( '_left_navigation_bar.inc.php' );
// -------------------------------- END OF LEFT NAVIGATION BAR -------------------
?>

<!-- =================================== START OF MAIN AREA =================================== -->
<div class="bPosts">

	<?php
		// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
		messages( array(
				'block_start' => '<div class="action_messages">',
				'block_end'   => '</div>',
			) );
		// --------------------------------- END OF MESSAGES ---------------------------------
	?>

	<?php
		// ------------------------ TITLE FOR THE CURRENT REQUEST ------------------------
		request_title( array(
				'title_before'      => '<h1 class="page_title">',
				'title_after'       => '</h1>',
				'title_single_disp' => false,
				'title_page_disp'   => false,
				'format'            => 'htmlbody',
				'category_text'     => '',
				'categories_text'   => '',
				'catdir_text'       => ''
			) );
		// ----------------------------- END OF REQUEST TITLE ----------------------------
	?>

	<?php
		// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
		echo '<h2 class="table_contents">'.T_('Table of contents').'</h2>';
		$Skin->display_chapters( array(
				'display_blog_title' => false,
				'display_children'   => true,
				'class_selected'     => ''
			) );
		// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
	?>
</div>
<?php
// -------------------------- BODY FOOTER INCLUDED HERE --------------------------
skin_include( '_body_footer.inc.php' );
// Note: You can customize the default BODY footer by copying the generic
// /skins/_body_footer.inc.php file into the current skin folder.
// -------------------------------- END OF BODY FOOTER ---------------------------

// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// Note: You can customize the default HTML footer by copying the
// _html_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>
