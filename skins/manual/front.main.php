<?php
/**
 * This is the main/default page template for the "manual" skin.
 *
 * This skin only uses one single template which includes most of its features.
 * It will also rely on default includes for specific dispays (like the comment form).
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * The main page template is used to display the blog when no specific page template is available
 * to handle the request (based on $disp).
 *
 * @package evoskins
 * @subpackage manual
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( version_compare( $app_version, '5.0' ) < 0 )
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
	die( 'This skin is designed for b2evolution 5.0 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}


// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );

// Init star rating for intro posts
init_ratings_js( 'blog', true );

// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php', array(
		'front_text'  => '',
	) );
// Note: You can customize the default HTML header by copying the generic
// /skins/_html_header.inc.php file into the current skin folder.
// -------------------------------- END OF HTML HEADER ---------------------------


// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------


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

		if( ! empty( $cat ) )
		{ // Display breadcrumbs if some category is selected
			skin_widget( array(
					// CODE for the widget:
					'widget' => 'breadcrumb_path',
					// Optional display params
					'block_start' => '<div class="breadcrumbs">',
					'block_end'   => '</div>',
				) );
		}
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
				'catdir_text'       => '',
				'front_text'        => '',
			) );
		// ----------------------------- END OF REQUEST TITLE ----------------------------
	?>

	<?php
	// Home page, display full categories list

	// Go Grab the featured post:
	$intro_Item = & get_featured_Item( 'front' ); // $intro_Item is used below for comments form
	$Item = $intro_Item;
	if( !empty( $Item ) )
	{	// We have a featured/intro post to display:
		// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
		skin_include( '_item_block.inc.php', array(
				'feature_block'     => true,
				'content_mode'      => 'auto',		// 'auto' will auto select depending on $disp-detail
				'intro_mode'        => 'normal',	// Intro posts will be displayed in normal mode
				'item_class'        => 'featured_post',
				'image_size'        => 'fit-640x480',
				'disp_comment_form' => false,
				'item_link_type'    => 'none',
			) );
		// ----------------------------END ITEM BLOCK  ----------------------------
	}

	// --------------------------------- START OF CONTENT HIERARCHY --------------------------------
	echo '<h2 class="table_contents">'.T_('Table of contents').'</h2>';
	skin_widget( array(
			// CODE for the widget:
			'widget' => 'content_hierarchy',
			// Optional display params
			'display_blog_title'   => false,
			'open_children_levels' => 20,
			'class_selected'       => ''
		) );
	// ---------------------------------- END OF CONTENT HIERARCHY ---------------------------------

	if( !empty( $intro_Item ) )
	{
		global $c, $ReqURI;
		$c = 1; // Display comments

		// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
		skin_include( '_item_feedback.inc.php', array(
				'before_section_title' => '<h2 class="comments_list_title">',
				'after_section_title'  => '</h2>',
				'form_title_start'     => '<h3 class="comments_form_title">',
				'form_title_end'       => '</h3>',
				'Item'                 => $intro_Item,
				'form_title_text'      => T_('Comment form'),
				'comments_title_text'  => T_('Comments on this chapter'),
				'form_comment_redirect_to' => $ReqURI,
			) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.
		// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
	}
	?>
</div>
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
// Note: You can customize the default HTML footer by copying the
// _html_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>