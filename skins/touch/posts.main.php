<?php
/**
 * This is the main/default page template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * The main page template is used to display the blog when no specific page template is available
 * to handle the request (based on $disp).
 *
 * @package evoskins
 * @subpackage touch
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( evo_version_compare( $app_version, '4.0.0-dev' ) < 0 )
{ // Older 2.x skins work on newer 2.x b2evo versions, but newer 2.x skins may not work on older 2.x b2evo versions.
	die( 'This skin is designed for b2evolution 4.0.0 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php', array() );
// -------------------------------- END OF HEADER --------------------------------
?>


<?php
// ------------------------- BODY HEADER INCLUDED HERE --------------------------
skin_include( '_body_header.inc.php' );
// Note: You can customize the default BODY header by copying the generic
// /skins/_body_header.inc.php file into the current skin folder.
// ------------------------------- END OF HEADER --------------------------------
?>


<div id="content" class="narrowcolumn">

<?php
	// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
	messages( array(
			'block_start' => '<div class="action_messages">',
			'block_end'   => '</div>',
		) );
	// --------------------------------- END OF MESSAGES ---------------------------------
?>

<?php
	// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
	request_title( array(
			'title_before'=> '<h2>',
			'title_after' => '</h2>',
			'title_none'  => '',
			'glue'        => ' - ',
			'title_single_disp' => true,
			'format'      => 'htmlbody',
		) );
	// ------------------------------ END OF REQUEST TITLE -----------------------------
?>

<?php
// Go Grab the featured post:
if( $Item = & get_featured_Item() )
{	// We have a featured/intro post to display:
	// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
	skin_include( '_item_block.inc.php', array(
			'feature_block' => true,
			'content_mode'  => 'auto',		// 'auto' will auto select depending on $disp-detail
			'intro_mode'    => 'normal',	// Intro posts will be displayed in normal mode
			'item_class'    => 'featured_post',
			'image_size'    => 'fit-400x320',
			'Item'          => $Item,
		) );
	// ----------------------------END ITEM BLOCK  ----------------------------
}
?>

<?php
// Display message if no post:
display_if_empty();

// ---------------------- ITEM BLOCK + PREV/NEXT PAGE LINKS(POST LIST MODE) INCLUDED HERE ------------------------
items_list_block_by_page( array(
		'content_mode' => 'auto', // 'auto' will auto select depending on $disp-detail
		'image_size'   => 'fit-400x320',
		'block_start'  => '<div class="navigation ajax">',
		'block_end'    => '</div>',
		'links_format' => '$next$',
		'next_text'    => T_('Load more entries').'&hellip;',
	) );
// ---------------------------- END ITEM BLOCK + PREV/NEXT PAGE LINKS ----------------------------

?>

</div>

<?php
// ------------------------- MOBILE FOOTER INCLUDED HERE --------------------------
skin_include( '_mobile_footer.inc.php' );
// Note: You can customize the default MOBILE FOOTER footer by copying the
// _mobile_footer.inc.php file into the current skin folder.
// ----------------------------- END OF MOBILE FOOTER -----------------------------
?>


<?php
// ------------------------- BODY FOOTER INCLUDED HERE --------------------------
skin_include( '_body_footer.inc.php' );
// Note: You can customize the default BODY footer by copying the
// _body_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>


<?php
// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// Note: You can customize the default HTML footer by copying the
// _html_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>