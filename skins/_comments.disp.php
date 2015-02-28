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
		'author_link_text' => 'name', // avatar_name | avatar_login | only_avatar | name | login | nickname | firstname | lastname | fullname | preferredname
		'display_comment_avatar'  => true,
		'comment_avatar_position' => 'before_title', // 'before_title', 'before_text'
		'comment_start'        => '<div class="bComment">',
		'comment_end'          => '</div>',
		'comment_post_before'  => '<h3 class="bTitle">',
		'comment_post_after'   => '</h3>',
		'comment_title_before' => '<div class="bCommentTitle">',
		'comment_title_after'  => '</div>',
		'comment_rating_before'=> '<div class="comment_rating">',
		'comment_rating_after' => '</div>',
		'comment_text_before'  => '<div class="bCommentText">',
		'comment_text_after'   => '</div>',
		'comment_info_before'  => '<div class="bCommentSmallPrint">',
		'comment_info_after'   => '</div>',
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
	// Load comment's Item object:
	$Comment->get_Item();
	?>
	<!-- ========== START of a COMMENT ========== -->
	<?php
	$Comment->anchor();

	echo $params['comment_start'];
	if( $Comment->status != 'published' )
	{
		$Comment->status( 'styled' );
	}
	if( $params['display_comment_avatar'] && $params['comment_avatar_position'] == 'before_title' )
	{ // Avatar before title
		$Comment->avatar();
	}

	// Post title
	echo $params['comment_post_before'];
	echo T_('In response to:').' ';
	$Comment->Item->title( array(
			'link_type' => 'permalink',
		) );
	echo $params['comment_post_after'];

	// Title
	echo $params['comment_title_before'];
	$Comment->author(
			/* before: */ '',
			/* after: */ '#',
			/* before_user: */ '',
			/* after_user: */ '#',
			/* format: */ 'htmlbody',
			/* makelink: */ true,
			/* linkt_text*/ $params['author_link_text'] );
	echo $params['comment_title_after'];

	// Text
	echo $params['comment_text_before'];
	if( $params['display_comment_avatar'] && $params['comment_avatar_position'] == 'before_text' )
	{ // Avatar before text
		$Comment->avatar();
	}
	$Comment->content();
	echo $params['comment_text_after'];

	// Info
	echo $params['comment_info_before'];

	$Comment->permanent_link( array(
			'class'    => 'permalink_right',
			'nofollow' => true,
		) );

	$Comment->date(); echo ' @ '; $Comment->time( '#short_time' );
	$Comment->edit_link( ' &middot; ' ); /* Link to backoffice for editing */
	$Comment->delete_link( ' &middot; ' ); /* Link to backoffice for deleting */

	echo $params['comment_info_after'];

	echo $params['comment_end'];
	?>
	<!-- ========== END of a COMMENT ========== -->
	<?php
}	// End of comment loop.
echo '</div>';

?>