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
 * @subpackage bootstrap_manual
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $cat, $tag, $MainList;


if( isset( $tag ) )
{	// Display posts list for selected tag:

	// Go Grab the featured post:
	$intro_Item = & get_featured_Item(); // $intro_Item is used below for comments form

	if( ! empty( $intro_Item ) )
	{ // We have a featured/intro post to display:
		$Item = $intro_Item;
		echo '<div class="evo_content_block">'; // Beginning of posts display
		// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
		skin_include( '_item_block.inc.php', array_merge( array(
				'feature_block'     => true,
				'content_mode'      => 'auto',		// 'auto' will auto select depending on $disp-detail
				'intro_mode'        => 'normal',	// Intro posts will be displayed in normal mode
				'item_class'        => 'well evo_post evo_content_block',
				'disp_notification' => false,
				'item_link_type'    => 'none',
				'Item'              => $Item,
			), $Skin->get_template( 'disp_params' ) ) );
		// ----------------------------END ITEM BLOCK  ----------------------------
		echo '</div>'; // End of posts display
	}

	// Display message if no post:
	display_if_empty();

	if( isset( $MainList ) && !empty( $MainList ) )
	{
		// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
		widget_container( 'item_list', array_merge( $params['pagination'], array(
				// The following params will be used as defaults for widgets included in this container:
				'container_display_if_empty' => false, // If no widget, don't display container at all
				// This will enclose each widget in a block:
				'block_start' => '<div class="evo_widget $wi_class$">',
				'block_end'   => '</div>',
			) ) );
		// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------

		// --------------------------------- START OF POSTS -------------------------------------
		// Display lists of the posts
		echo '<ul class="posts_list">';
		while( $Item = & mainlist_get_item() )
		{
			skin_include( '_item_list.inc.php' );
		}
		echo '</ul>';
		// ---------------------------------- END OF POSTS ------------------------------------

		// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
		mainlist_page_links( $params['pagination'] );
		// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
	}

	if( ! empty( $intro_Item ) )
	{
		global $c, $ReqURI;
		$c = 1; // Display comments

		echo '<div class="evo_content_block">'; // Beginning of posts display
		// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
		skin_include( '_item_feedback.inc.php', array_merge( array(
				'disp_comments'        => true,
				'disp_comment_form'    => true,
				'disp_trackbacks'      => false,
				'disp_trackback_url'   => false,
				'disp_pingbacks'       => false,
				'disp_webmentions'     => false,
				'disp_meta_comments'   => false,
				'before_section_title' => '<h3 class="evo_comment__list_title">',
				'after_section_title'  => '</h3>',
				'Item'                 => $intro_Item,
				'form_title_text'      => T_('Comment form'),
				'comments_title_text'  => T_('Comments on this tag'),
				'form_comment_redirect_to' => $ReqURI,
			), $Skin->get_template( 'disp_params' ) ) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.
		// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
		echo '</div>'; // End of posts display
	}
}
elseif( !empty( $cat ) && ( $cat > 0 ) )
{ // Display Category's page
	global $Item;

	$ChapterCache = & get_ChapterCache();
	// Load blog's categories
	$ChapterCache->reveal_children( $Blog->ID );
	$curr_Chapter = & $ChapterCache->get_by_ID( $cat, false );

	// This will initialize $FeaturedList that will be used by widgets below and without moving the cursor:
	$intro_Item = & get_featured_Item( 'posts', NULL, true ); // $intro_Item is used below for comments form

	// ------------------------- "Chapter Main Area" CONTAINER EMBEDDED HERE --------------------------
	// Display container and contents:
	widget_container( 'chapter_main_area', array(
		// The following params will be used as defaults for widgets included in this container:
			'container_display_if_empty' => false, // If no widget, don't display container at all
			'block_start'       => '<div class="evo_widget $wi_class$">',
			'block_end'         => '</div>',
			'block_title_start' => '<h2 class="page-header">',
			'block_title_end'   => '</h2>',
			'intro_class'       => 'well evo_post evo_content_block',
			'featured_class'    => 'featurepost',
			'item_mask'         => '<li><a href="$url$">$title$</a></li>',
			'item_active_mask'  => '<li class="active">$title$</li>',

			// Template params for "Breadcrumb Path" widget:
			'widget_breadcrumb_path_before' => '<nav><ol class="breadcrumb">',
			'widget_breadcrumb_path_after' => '</ol></nav>',

		) );
	// ----------------------------- END OF "Chapter Main Area" CONTAINER -----------------------------

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
		echo '<div class="'.button_class( 'group' ).'" style="margin:15px 0">';
		if( ! empty( $create_new_chapter_url ) )
		{ // Display button to write a new post
			echo '<a href="'.$create_new_chapter_url.'" class="'.button_class( 'text' ).'">'.get_icon( 'add' ).' '.T_('Add a sub-chapter here').'</a>';
		}
		if( ! empty( $write_new_post_url ) )
		{ // Display button to write a new post
			echo '<a href="'.$write_new_post_url.'" class="'.button_class( 'text' ).'">'.get_icon( 'add' ).' '.T_('Add a page here').'</a>';
		}
		echo '</div>';
	}

	if( ! empty( $intro_Item ) )
	{
		global $c, $ReqURI;
		$c = 1; // Display comments

		echo '<div class="evo_content_block">'; // Beginning of posts display
		// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
		skin_include( '_item_feedback.inc.php', array_merge( array(
				'disp_comments'        => true,
				'disp_comment_form'    => true,
				'disp_trackbacks'      => false,
				'disp_trackback_url'   => false,
				'disp_pingbacks'       => false,
				'disp_webmentions'     => false,
				'disp_meta_comments'   => false,
				'before_section_title' => '<h3 class="evo_comment__list_title">',
				'after_section_title'  => '</h3>',
				'Item'                 => $intro_Item,
				'form_title_text'      => T_('Comment form'),
				'comments_title_text'  => T_('Comments on this chapter'),
				'form_comment_redirect_to' => $ReqURI,
			), $Skin->get_template( 'disp_params' ) ) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.
		// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
		echo '</div>'; // End of posts display
	}

} // End of Category's page
else
{ // Display the latest posts:
	// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
	widget_container( 'item_list', array_merge( $params['pagination'], array(
			// The following params will be used as defaults for widgets included in this container:
			'container_display_if_empty' => false, // If no widget, don't display container at all
			// This will enclose each widget in a block:
			'block_start' => '<div class="evo_widget $wi_class$">',
			'block_end'   => '</div>',
		) ) );
	// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
?>
<ul class="posts_list">
<?php
	while( $Item = & mainlist_get_item() )
	{	// For each blog post, do everything below up to the closing curly brace "}"
		// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
		skin_include( '_item_list.inc.php' );
		// ----------------------------END ITEM BLOCK  ----------------------------
	}
?>
</ul>
<?php
	// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
	mainlist_page_links( $params['pagination'] );
	// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
} // End of List of the latest posts

?>