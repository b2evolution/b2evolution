<?php
/**
 * This is the 404 page template for the "bootstrap_manual" skin.
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
 * @subpackage bootstrap_manual
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( version_compare( $app_version, '6.4' ) < 0 )
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
	die( 'This skin is designed for b2evolution 6.4 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}


if( ! empty( $requested_404_title ) )
{ // Initialize a prefilled search form
	set_param( 's', str_replace( '-', ' ', $requested_404_title ) );
	set_param( 'sentence', 'OR' );
	set_param( 'title', '' ); // Empty this param to exclude a filter by post_urltitle
}

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( ! empty( $requested_404_title ) ? 'search' : $disp );


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
	// --------------------- 404 CONTENT TEMPLATE INCLUDED HERE ----------------------
	echo '<div class="error_404">';

	echo '<h1>404 Not Found</h1>';

	echo '<p>'.T_('The manual page you are requesting doesn\'t seem to exist (yet).').'</p>';

	$post_title = '';
	$post_urltitle = '';
	if( ! empty( $requested_404_title ) )
	{ // Set title & urltitle for new post
		$post_title = str_replace( ' ', '%20', ucwords( str_replace( '-', ' ', $requested_404_title ) ) );
		$post_urltitle = $requested_404_title;
	}

	// Button to create a new page
	$write_new_post_url = $Blog->get_write_item_url( 0, $post_title, $post_urltitle );
	if( ! empty( $write_new_post_url ) )
	{ // Display button to write a new post
		echo '<a href="'.$write_new_post_url.'" class="roundbutton roundbutton_text_noicon">'.T_('Create this page now').'</a>';
	}

	echo '<p>'.T_('You can search the manual below.').'</p>';

	echo '</div>';

	if( ! empty( $requested_404_title ) )
	{ // Initialize a prefilled search form
		skin_include( '_search.disp.php', $Skin->get_template( 'disp_params' ) );
		// Note: You can customize the default search by copying the generic
		// /skins/_search.disp.php file into the current skin folder.
	}
	else
	{ // Display a search form with TOC
		echo '<div class="error_additional_content">';
		// --------------------------------- START OF SEARCH FORM --------------------------------
		// Call the coll_search_form widget:
		skin_widget( array(
				// CODE for the widget:
				'widget' => 'coll_search_form',
				// Optional display params:
				'block_start'          => '',
				'block_end'            => '',
				'title'                => T_('Search this manual:'),
				'disp_search_options'  => 0,
				'search_class'         => 'extended_search_form',
				'block_title_start'    => '<h3>',
				'block_title_end'      => '</h3>',
				'search_class'         => 'compact_search_form',
				'search_input_before'  => '<div class="input-group">',
				'search_input_after'   => '',
				'search_submit_before' => '<span class="input-group-btn">',
				'search_submit_after'  => '</span></div>',
			) );
		// ---------------------------------- END OF SEARCH FORM ---------------------------------

		echo '<p>'.T_('or you can browse the table of contents below:').'</p>';

		// --------------------------------- START OF CONTENT HIERARCHY --------------------------------
		echo '<h2 class="table_contents">'.T_('Table of contents').'</h2>';
		skin_widget( array(
				// CODE for the widget:
				'widget' => 'content_hierarchy',
				// Optional display params
				'display_blog_title'   => false,
				'open_children_levels' => 20,
				'class_selected'       => '',
				'item_before_opened'   => get_icon( 'collapse' ),
				'item_before_closed'   => get_icon( 'expand' ),
				'item_before_post'     => get_icon( 'post' ),
			) );
		// ---------------------------------- END OF CONTENT HIERARCHY ---------------------------------

		echo '</div>';
	}
	// ----------------- END OF 404 CONTENT TEMPLATE INCLUDED HERE -------------------
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
// Note: You can customize the default HTML footer by copying the
// _html_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>