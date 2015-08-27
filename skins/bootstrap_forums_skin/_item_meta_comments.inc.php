<?php
/**
 * This is the template that displays the meta comments of the viewed post
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_forums_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


if( ! empty( $Item ) &&
    is_logged_in() &&
    $current_User->check_perm( 'meta_comment', 'view', false, $Item ) )
{ // Display the meta comments if current user can edit this post:

	// Unset a comment counter to set new for meta comments:
	global $comment_template_counter;
	$comment_template_counter = NULL;

	// ------------------ FEEDBACK INCLUDED HERE ------------------
	skin_include( '_item_feedback.inc.php', array(
		'disp_comments'         => false,
		'disp_comment_form'     => false,
		'disp_trackbacks'       => false,
		'disp_pingbacks'        => false,
		'disp_meta_comments'    => true,
		'disp_section_title'    => false,
		'disp_rating_summary'   => false,
		'disp_notification'     => false,

		'comment_post_before'   => '<h4 class="evo_comment_post_title ellipsis">',
		'comment_post_after'    => '</h4>',

		'comment_title_before'  => '<div class="panel-heading posts_panel_title_wrapper"><div class="cell1 ellipsis"><h4 class="evo_comment_title panel-title">',
		'comment_status_before' => '</h4></div>',
		'comment_title_after'   => '</div>',

		'comment_avatar_before' => '<div class="panel-body"><span class="evo_comment_avatar col-md-1 col-sm-2">',
		'comment_avatar_after'  => '</span>',
		'comment_text_before'   => '<div class="evo_comment_text col-md-11 col-sm-10">',
		'comment_text_after'    => '</div>',

		'comments_per_page'     => 20,
	) );
	// Note: You can customize the default item feedback by copying the generic
	// /skins/_item_feedback.inc.php file into the current skin folder.
}