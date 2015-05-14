<?php
/**
 * This is the WordPress compatibility include.
 *
 * This includes a lot of dull template tags stubs that emulate wordpress template tags.
 * This is designed to make skin porting easier.
 * This should not be used in redistributed skins as this will NOT provide FULL b2evo functionality.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * @package evoskins
 * @deprecated This will be removed from a future version.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * WP compatibility variable - UNSUPPORTED.
 */
global $siteurl;
$siteurl = $Blog->get('url');



/**
 * WP compatibility template tag - UNSUPPORTED.
 */
function get_calendar()
{
	global $Plugins;
	// ------------------------------- START OF CALENDAR ---------------------------------
	// Call the Calendar plugin (if installed):
	$Plugins->call_by_code( 'evo_Calr', array(	// Params follow:
			'block_start' => '',
			'block_end' => '',
			'displaycaption' => true,
			'linktomontharchive' => false,
			'headerdisplay' => 'e',
			'tablestart' => '<table id="wp-calendar" class="bCalendarTable" cellspacing="0" summary="Monthly calendar with links to each day\'s posts">'."\n",
		) );
	// -------------------------------- END OF CALENDAR ----------------------------------
}


/**
 * WP compatibility template tag - UNSUPPORTED.
 */
function wp_list_cats()
{
	// --------------------------------- START OF CATEGORY LIST --------------------------------
	skin_widget( array(
			// CODE for the widget:
			'widget' => 'coll_category_list',
			// Optional display params
			'block_start' => '<div class="bSideItem">',
			'block_end' => '</div>',
			'block_title_start' => '<h3 class="sideItemTitle">',
			'block_title_end' => '</h3>',
		) );
	// ---------------------------------- END OF CATEGORY LIST ---------------------------------
}


/**
 * WP compatibility template tag - UNSUPPORTED.
 */
function wp_tag_cloud()
{
	skin_widget( array(
			// CODE for the widget:
			'widget' => 'coll_tag_cloud',
			// Optional display params
			'block_start' => '<div class="tag_cloud">',
			'block_end' => '</div>',
			'block_title_start' => '<h3 class="sideItemTitle">',
			'title' => 'Tag Cloud:',
			'block_title_end' => '</h3>',
			'max_tags' => 100,
			'min_size' => 8,
			'max_size' => 22,
		) );
}


/**
 * WP compatibility template tag - UNSUPPORTED.
 */
function get_permalink()
{
	global $Item;
	$Item->permanent_url();
}


/**
 * WP compatibility template tag - UNSUPPORTED.
 */
function the_title()
{
	global $Item;
	$Item->title( array(
		'link_type'   => 'none',
	 ) );
}


/**
 * WP compatibility template tag - UNSUPPORTED.
 */
function the_content()
{
	// ---------------------- POST CONTENT INCLUDED HERE ----------------------
	skin_include( '_item_content.inc.php', array(
			'image_size' => 'fit-400x320',
		) );
	// Note: You can customize the default item content by copying the generic
	// /skins/_item_content.inc.php file into the current skin folder.
	// -------------------------- END OF POST CONTENT -------------------------
}


/**
 * WP compatibility template tag - UNSUPPORTED.
 */
function is_home()
{
	global $is_front;
	return $is_front;
}


/**
 * WP compatibility template tag - UNSUPPORTED.
 */
function is_page()
{
	global $disp;

	return ($disp == 'page');
}


/**
 * WP compatibility template tag - UNSUPPORTED.
 */
function is_tag()
{
	global $disp_detail;

	return ($disp_detail == 'posts-tag' );
}


/**
 * WP compatibility template tag - UNSUPPORTED.
 */
function the_time( $format = '#' )
{
	global $Item;
	$Item->issue_time( array(
			'time_format' => $format,
		) );
}


/**
 * WP compatibility template tag - UNSUPPORTED.
 */
function trackback_url()
{
	global $Item;
	$Item->trackback_url();
}


/**
 * WP compatibility template tag - UNSUPPORTED.
 */
function comments_popup_link( $zero = '#', $one = '#', $more = '#' )
{
	global $Item;
	$Item->feedback_link( array(
									'type' => 'feedbacks',
									'status' => 'published',
									'link_before' => '',
									'link_after' => '',
									'link_text_zero' => $zero,
									'link_text_one' => $one,
									'link_text_more' => str_replace( '%', '%d', $more ),
									'link_title' => '#',
									'url' => '#',
								) );
}


/**
 * WP compatibility template tag - UNSUPPORTED.
 */
function comments_rss_link( $link_text = 'Comments RSS', $commentsrssfilename = 'nolongerused' )
{
	global $Item;
	$Item->feedback_feed_link( '_rss2', '', '', $link_text );
}


/**
 * WP compatibility template tag - UNSUPPORTED.
 */
function the_category( $separator )
{
	global $Item;
	$Item->categories( array(
				'before'          => ' ',
				'after'           => ' ',
				'include_main'    => true,
				'include_other'   => true,
				'include_external'=> true,
				'before_main'     => '',       // string fo display before the MAIN category,
				'after_main'      => '',       // string fo display after the MAIN category
				'before_other'    => '',       // string fo display before OTHER categories
				'after_other'     => '',       // string fo display after OTHER categories
				'before_external' => '<em>',   // string fo display before EXTERNAL categories
				'after_external'  => '</em>',  // string fo display after EXTERNAL categories,
				'separator'       => $separator,
				'link_categories' => true,
				'link_title'      => '#',
				'format'          => 'htmlbody',
			) );
}


/**
 * WP compatibility template tag - UNSUPPORTED.
 */
function the_tags( $before = 'Tags: ', $sep = ', ', $after = '' )
{
	global $Item;
	$Item->tags( array(
				'before' =>           $before,
				'after' =>            $after,
				'separator' =>        $sep,
				'links' =>            true,
			) );
}


/**
 * WP compatibility template tag - UNSUPPORTED.
 */
function edit_post_link()
{
	global $Item;
	$Item->edit_link();
}


/**
 * WP compatibility template tag - UNSUPPORTED.
 */
function comments_template()
{
	// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
	skin_include( '_item_feedback.inc.php', array(
			'before_section_title' => '<h3>',
			'after_section_title'  => '</h3>',
			'comment_list_start'  => "\n<ol>\n",
			'comment_list_end'  => "\n</ol>\n",
			'comment_start'  => '<li>',
			'comment_end'  => '</li>',
	    'preview_start'  => '<ul><li id="comment_preview">',
	    'preview_end'    => '</li></ul>',
	    'comment_template'   => '_item_comment_wp.inc.php',	// The template used for displaying individual comments (including preview)
		) );
	// Note: You can customize the default item feedback by copying the generic
	// /skins/_item_feedback.inc.php file into the current skin folder.
	// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
}


/**
 * WP compatibility template tag - UNSUPPORTED.
 */
function previous_posts_link( $link_text = 'Previous' )
{
	global $MainList;
	if( !isset($MainList) )
	{
		return;
	}
	$MainList->page_links( array(
				'block_start' => ' ',
				'block_end' => ' ',
				'block_single' => '',
				'links_format' => '$prev$',
				'prev_text' => $link_text,
			) );
}


/**
 * WP compatibility template tag - UNSUPPORTED.
 */
function next_posts_link( $link_text = 'Next' )
{
	global $MainList;
	if( !isset($MainList) )
	{
		return;
	}
	$MainList->page_links( array(
				'block_start' => ' ',
				'block_end' => ' ',
				'block_single' => '',
				'links_format' => '$next$',
				'next_text' => $link_text,
			) );
}

?>