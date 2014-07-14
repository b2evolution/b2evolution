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
 * @version $Id: posts.main.php 7091 2014-07-09 05:47:57Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( version_compare( $app_version, '5.0' ) < 0 )
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
	die( 'This skin is designed for b2evolution 5.0 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}

global $cat, $tag, $MainList, $Skin;

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

		if( !empty( $cat ) )
		{ // Display breadcrumbs if some category is selected
			$Skin->display_breadcrumbs( $cat );
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
		global $Item, $Settings;

		if( $Settings->get( 'chapter_ordering' ) == 'manual' &&
		    $Blog->get_setting( 'orderby' ) == 'order' &&
		    $Blog->get_setting( 'orderdir' ) == 'ASC' )
		{	// Items & categories are ordered by manual field 'order'
			// In this mode we should show them in one merged list ordered by field 'order'
			$chapters_items_mode = 'order';
			$main_items_unit = 'all'; // Remove paging for items of main category, Show all items on one page
		}
		else
		{	// Standard mode for all other cases
			$chapters_items_mode = 'std';
			$main_items_unit = $Blog->get_setting('what_to_show');
		}


		// Init MainList
		$MainList = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max(), $Blog->get_setting('posts_per_page') );
		$MainList->load_from_Request();
		$MainList->set_filters( array(
				'cat_array'    => array( $cat ), // Limit only by selected cat (exclude posts from child categories)
				'cat_modifier' => NULL,
				'cat_focus'    => 'main',
				'page'         => param( 'paged', 'integer', 1, true, true ),
				'unit'         => $main_items_unit,
			) );
		$MainList->query();
		$MainList->nav_target = $cat; // set navigation target, we are always navigating through category in this skin

		// Go Grab the featured post:
		$intro_Item = get_featured_Item(); // $intro_Item is used below for comments form
		$Item = $intro_Item;

		if( empty( $Item ) || $Item->get( 'title' ) == '' )
		{ // Display chapter title only if intro post has no title
			$ChapterCache = & get_ChapterCache();
			if( $curr_Chapter = & $ChapterCache->get_by_ID( $cat, false ) )
			{ // Display category title
				echo '<div class="bTitle linked">';

				echo '<h1 class="page_title">'.$curr_Chapter->get( 'name' ).'</h1>';
				echo '<div class="roundbutton_group">';
				echo $curr_Chapter->get_edit_link( array(
						'text'          => get_icon( 'edit' ).' '.T_('Edit Cat'),
						'class'         => 'roundbutton roundbutton_text',
						'redirect_page' => 'front',
					) );

				// Button to create a new page
				$write_new_intro_url = $Blog->get_write_item_url( $cat, '', '', 'intro-cat' );
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
		}

		if( !empty( $Item ) )
		{	// We have a featured/intro post to display:
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

		$sub_chapters = $Skin->get_chapters( $cat );

		if( $chapters_items_mode != 'order' && count( $sub_chapters ) > 0 )
		{	// Display subchapters
	?>
	<h4><?php echo T_('Subchapters'); ?>:</h4>
	<ul class="chapters_list">
	<?php
			foreach( $sub_chapters as $sub_Chapter )
			{	// Loop through categories:
				skin_include( '_cat_list.inc.php', array(
						'Chapter' => $sub_Chapter,
					) );
			}	// End of categories loop.
	?>
		</ul>
	<?php
		}

		// ---------------------------------- START OF POSTS ------------------------------------

		// Display the posts ONLY from MAIN category
	?>
	<h4 style="margin-top:20px"><?php echo T_('Pages in this chapter'); ?>:</h4>
	<?php
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
		$prev_item_order = 0;
		while( $Item = & mainlist_get_item() )
		{	// For each blog post, do everything below up to the closing curly brace "}"

			if( $chapters_items_mode == 'order' )
			{	// In this mode we display the chapters inside a posts list
				foreach( $sub_chapters as $s => $sub_Chapter )
				{	// Loop through categories to find for current order
					if( ( $sub_Chapter->get( 'order' ) <= $Item->get( 'order' ) && $sub_Chapter->get( 'order' ) > $prev_item_order ) ||
						  /* This condition is needed for NULL order: */
						  ( $Item->get( 'order' ) == 0 && $sub_Chapter->get( 'order' ) >= $Item->get( 'order' ) ) )
					{	// Display chapter
						skin_include( '_cat_list.inc.php', array(
								'Chapter' => $sub_Chapter,
								'after_title' => '</h3><div class="clear"></div>',
							) );
						// Remove this chapter from array to avoid the duplicates
						unset( $sub_chapters[ $s ] );
					}
				}

				// Save current post order for next iteration
				$prev_item_order = $Item->get( 'order' );
			}

			// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
			skin_include( '_item_list.inc.php', array() );
			// ----------------------------END ITEM BLOCK  ----------------------------
		}

		if( $chapters_items_mode == 'order' )
		{
			foreach( $sub_chapters as $s => $sub_Chapter )
			{	// Loop through rest categories that have order more than last item
				skin_include( '_cat_list.inc.php', array(
						'Chapter' => $sub_Chapter,
						'after_title' => '</h3><div class="clear"></div>',
					) );
				// Remove this chapter from array to avoid the duplicates
				unset( $sub_chapters[ $s ] );
			}
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


		// Init MainList for posts ONLY from EXTRA categories
		$MainList = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max(), $Blog->get_setting('posts_per_page'), 'ItemCache', 'extra_' );
		$MainList->load_from_Request();
		$MainList->set_filters( array(
				'cat_array'    => array( $cat ), // Limit only by selected cat (exclude posts from child categories)
				'cat_modifier' => NULL,
				'cat_focus'    => 'extra',
				'page'         => param( 'extra_paged', 'integer', 1, true, true ),
			) );
		$MainList->query();
		$MainList->nav_target = $cat; // set navigation target, we are always navigating through category in this skin

		if( isset( $MainList ) && $MainList->result_num_rows > 0 )
		{
	?>
	<h4 style="margin-top:20px"><?php echo T_('See also'); ?>:</h4>
	<?php
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
					'before_title'   => '<h3><i>',
					'after_title'    => '</i></h3>',
					'before_content' => '<div class="excerpt"><i>',
					'after_content'  => '</i></div>',
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
		}

		// ---------------------------------- END OF POSTS ------------------------------------


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
					'before_content' => '<div class="excerpt">',
					'after_content'  => '</div>',
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
