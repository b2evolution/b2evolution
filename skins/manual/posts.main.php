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

global $cat, $tag, $MainList;

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );

$catdir_text = T_('Posts');
if( !empty( $cat ) )
{	// Init the <title> for categories page
	$ChapterCache = & get_ChapterCache();
	if( $Chapter = & $ChapterCache->get_by_ID( $cat, false ) )
	{
		$catdir_text = $Chapter->get( 'name' );
	}
}

// Init star rating for intro posts
init_ratings_js( 'blog', true );

// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php', array(
		'posts_text' => $catdir_text,
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

		// Display breadcrumbs if some category is selected
		skin_widget( array(
				// CODE for the widget:
				'widget' => 'breadcrumb_path',
				// Optional display params
				'block_start' => '<div class="breadcrumbs">',
				'block_end'   => '</div>',
			) );
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
				'posts_text'        => ''
			) );
		// ----------------------------- END OF REQUEST TITLE ----------------------------
	?>

	<?php
	if( isset( $tag ) )
	{	// Display posts list for selected tag

		// Display message if no post:
		display_if_empty();

		if( isset( $MainList ) && !empty( $MainList ) )
		{
			// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
			mainlist_page_links( array(
					'block_start' => '<p class="center"><strong>',
					'block_end' => '</strong></p>',
					'prev_text' => '&lt;&lt;',
					'next_text' => '&gt;&gt;',
				) );
			// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------

			// --------------------------------- START OF POSTS -------------------------------------
			// Display lists of the posts
			echo '<h4 style="margin-top:20px">'.T_('Pages in this chapter:').'</h4>';
			echo '<ul class="posts_list">';
			while( $Item = & mainlist_get_item() )
			{
				skin_include( '_item_list.inc.php' );
			}
			echo '</ul>';
			// ---------------------------------- END OF POSTS ------------------------------------

			// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
			mainlist_page_links( array(
					'block_start' => '<p class="center"><strong>',
					'block_end' => '</strong></p>',
					'prev_text' => '&lt;&lt;',
					'next_text' => '&gt;&gt;',
				) );
			// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
		}
	}
	elseif( !empty( $cat ) && ( $cat > 0 ) )
	{	// Display Category's page
		global $Item;

		$ChapterCache = & get_ChapterCache();
		// Load blog's categories
		$ChapterCache->reveal_children( $Blog->ID );
		$curr_Chapter = & $ChapterCache->get_by_ID( $cat, false );

		// Go Grab the featured post:
		$intro_Item = get_featured_Item(); // $intro_Item is used below for comments form

		if( empty( $intro_Item ) || $intro_Item->get( 'title' ) == '' )
		{ // Display chapter title only if intro post has no title
			// Display category title
			echo '<div class="bTitle linked">';

			echo '<h1 class="page_title">'.$curr_Chapter->get( 'name' ).'</h1>';
			echo '<div class="roundbutton_group">';
			echo $curr_Chapter->get_edit_link( array(
					'text'          => get_icon( 'edit' ).' '.T_('Edit Cat'),
					'class'         => 'roundbutton roundbutton_text',
					'redirect_page' => 'front',
				) );

			// Button to create a new page
			$write_new_intro_url = $Blog->get_write_item_url( $cat, '', '', 1520 );
			if( !empty( $write_new_intro_url ) )
			{ // Display button to write a new intro
				echo '<a href="'.$write_new_intro_url.'" class="roundbutton roundbutton_text">'
						.get_icon( 'add' ).' '
						.T_('Add Intro')
					.'</a>';
			}
			echo '</div>';

			echo '<div class="clear"></div></div>';
		}

		if( !empty( $intro_Item ) )
		{	// We have a featured/intro post to display:
			$Item = $intro_Item;
			// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
			skin_include( '_item_block.inc.php', array(
					'feature_block'     => true,
					'content_mode'      => 'auto',		// 'auto' will auto select depending on $disp-detail
					'intro_mode'        => 'normal',	// Intro posts will be displayed in normal mode
					'item_class'        => 'featured_post',
					'image_size'        => 'fit-640x480',
					'disp_comments'     => false,
					'disp_comment_form' => false,
					'disp_notification' => false,
					'item_link_type'    => 'none',
				) );
			// ----------------------------END ITEM BLOCK  ----------------------------
		}

		$callbacks = array(
			'line'  => 'cat_inskin_display',
			'posts' => 'item_inskin_display'
		);

		// Display subcategories and posts
		echo '<ul class="chapters_list posts_list">';

		$ChapterCache->iterate_through_category_children( $curr_Chapter, $callbacks, false, array( 'sorted' => true ) );

		echo '</ul>';

		// Button to create a new sub-chapter
		$create_new_chapter_url = $Blog->get_create_chapter_url( $cat );
		// Button to create a new page
		$write_new_post_url = $Blog->get_write_item_url( $cat );
		if( ! empty( $create_new_chapter_url ) || ! empty( $write_new_post_url ) )
		{
			echo '<div class="roundbutton_group" style="margin:15px 0">';
			if( ! empty( $create_new_chapter_url ) )
			{ // Display button to write a new post
				echo '<a href="'.$create_new_chapter_url.'" class="roundbutton roundbutton_text">'.get_icon( 'add' ).' '.T_('Add a sub-chapter here').'</a>';
			}
			if( ! empty( $write_new_post_url ) )
			{ // Display button to write a new post
				echo '<a href="'.$write_new_post_url.'" class="roundbutton roundbutton_text">'.get_icon( 'add' ).' '.T_('Add a page here').'</a>';
			}
			echo '</div>';
		}

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

	} // End of Category's page
	else
	{ // Display the latest posts:
		// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
		mainlist_page_links( array(
				'block_start' => '<div class="navigation_top"><div class="navigation">'.T_('Page').': ',
				'block_end' => '</div></div>',
				'prev_text' => T_('Previous'),
				'next_text' => T_('Next'),
			) );
		// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
	?>
	<ul class="posts_list">
	<?php
		while( $Item = & mainlist_get_item() )
		{	// For each blog post, do everything below up to the closing curly brace "}"
			// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
			skin_include( '_item_list.inc.php', array(
					'before_title'   => '<h3>',
					'after_title'    => '</h3>',
				) );
			// ----------------------------END ITEM BLOCK  ----------------------------
		}
	?>
	</ul>
	<?php
		// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
		mainlist_page_links( array(
				'block_start' => '<div class="navigation">'.T_('Page').': ',
				'block_end' => '</div>',
				'prev_text' => T_('Previous'),
				'next_text' => T_('Next'),
			) );
		// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
	} // End of List of the latest posts
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