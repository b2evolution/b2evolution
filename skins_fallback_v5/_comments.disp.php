<?php
/**
 * This is the template that displays the links to the latest comments for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display a feedback, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=comments
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Default params:
$params = array_merge( array(
		'comment_template'        => '_item_comment.inc.php',	// The template used for displaying individual comments (including preview)
		'author_link_text'        => 'name', // avatar_name | avatar_login | only_avatar | name | login | nickname | firstname | lastname | fullname | preferredname
		'link_to'                 => 'userurl>userpage', // 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
		'display_comment_avatar'  => true,
		'comment_avatar_position' => 'before_title', // 'before_title', 'before_text'
		'comment_start'           => '<div class="bComment">',
		'comment_end'             => '</div>',
		'comment_post_display'    => true,	// We want to display the title of the post we're referring to
		'comment_post_before'     => '<h3 class="bTitle">',
		'comment_post_after'      => '</h3>',
		'comment_title_before'    => '<div class="bCommentTitle">',
		'comment_title_after'     => '</div>',
		'comment_avatar_before'   => '<div class="evo_comment_avatar">',	// New in v6
		'comment_avatar_after'    => '</div>',
		'comment_rating_before'   => '<div class="comment_rating">',
		'comment_rating_after'    => '</div>',
		'comment_text_before'     => '<div class="bCommentText">',
		'comment_text_after'      => '</div>',
		'comment_info_before'     => '<div class="bCommentSmallPrint">',
		'comment_info_after'      => '</div>',
		'comment_image_size'      => 'fit-400x320',
	), $params );


$CommentList = new CommentList2( $Blog );

// Filter list:
$CommentList->set_filters( array(
		'types' => array( 'comment', 'trackback', 'pingback' ),
		'statuses' => get_inskin_statuses( $Blog->ID, 'comment' ),
		'order' => 'DESC',
		'comments' => 50,
		// fp> I don't think it's necessary to add a restriction here. (use case?)
		// 'timestamp_min' => $Blog->get_timestamp_min(),
		// 'timestamp_max' => $Blog->get_timestamp_max(),
	) );

// Get ready for display (runs the query):
$CommentList->display_init();

$CommentList->display_if_empty();

echo '<div id="styled_content_block">';
while( $Comment = & $CommentList->get_next() )
{ // Loop through comments:
	?>
	<!-- ========== START of a COMMENT ========== -->
	<?php
			// ------------------ COMMENT INCLUDED HERE ------------------
			skin_include( $params['comment_template'], array(
					'Comment'               => & $Comment,
					'comment_start'         => $params['comment_start'],
					'comment_end'           => $params['comment_end'],
					'comment_post_display'  => $params['comment_post_display'],
					'comment_post_before'   => $params['comment_post_before'],
					'comment_post_after'    => $params['comment_post_after'],
					'comment_title_before'  => $params['comment_title_before'],
					'comment_title_after'   => $params['comment_title_after'],
					'comment_avatar_before' => $params['comment_avatar_before'],
					'comment_avatar_after'  => $params['comment_avatar_after'],
					'comment_rating_before' => $params['comment_rating_before'],
					'comment_rating_after'  => $params['comment_rating_after'],
					'comment_text_before'   => $params['comment_text_before'],
					'comment_text_after'    => $params['comment_text_after'],
					'comment_info_before'   => $params['comment_info_before'],
					'comment_info_after'    => $params['comment_info_after'],
					'author_link_text'      => $params['author_link_text'],
					'link_to'               => $params['link_to'],		// 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
					'author_link_text'      => $params['author_link_text'],
					'image_size'            => $params['comment_image_size'],
				) );
			// Note: You can customize the default item comment by copying the generic
			// /skins/_item_comment.inc.php file into the current skin folder.
			// ---------------------- END OF COMMENT ---------------------

	?>
	<!-- ========== END of a COMMENT ========== -->
	<?php
}	// End of comment loop.
echo '</div>';
?>