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
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
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
			'title_before'=> '<h2 id="spectitle">',
			'title_after' => '</h2>',
			'title_none'  => '',
			'glue'        => ' - ',
			'title_single_disp' => true,
			'format'      => 'htmlbody',
		) );
	// ------------------------------ END OF REQUEST TITLE -----------------------------

	// Go Grab the featured post:
	if( $Item = & get_featured_Item() )
	{	// We have a featured/intro post to display:
		// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
		skin_include( '_item_block.inc.php', array(
				'feature_block' => true,
				'content_mode' => 'auto',		// 'auto' will auto select depending on $disp-detail
				'intro_mode'   => 'normal',	// Intro posts will be displayed in normal mode
				'item_class'   => 'featured_post',
				'image_size'	 =>	'fit-400x320',
			) );
		// ----------------------------END ITEM BLOCK  ----------------------------
	}

	// Display message if no post:
	display_if_empty();

	echo '<div id="styled_content_block">'; // Beginning of posts display
	while( $Item = & mainlist_get_item() )
	{	// For each blog post:
		// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
		skin_include( '_item_block.inc.php', array(
				'content_mode' => 'auto',		// 'auto' will auto select depending on $disp-detail
				'image_size'	 =>	'fit-400x320',
			) );
		// ----------------------------END ITEM BLOCK  ----------------------------
	}
	echo '</div>'; // End of posts display

	// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
	mainlist_page_links( array(
			'block_start' => '<div class="navigation">',
			'block_end'   => '</div>',
   		    'prev_text'   => '&lt;&lt;',
   		    'next_text'   => '&gt;&gt;',
		) );
	// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
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