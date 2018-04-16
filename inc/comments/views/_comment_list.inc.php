<?php
/**
 * This file implements the comment list
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Comment
 */
global $Comment;
/**
 * @var CommentList
 */
global $CommentList;

global $AdminUI, $UserSettings;

// If rediret_to was not set, create new redirect
$redirect_to = param( 'redirect_to', 'url', regenerate_url( '', 'filter=restore', '', '&' ) );
$save_context = param( 'save_context', 'boolean', 'true' );
$show_comments = param( 'show_comments', 'string', 'all' );

$item_id = param( 'item_id', 'integer', 0 );
if( empty( $item_id ) )
{ // Try to get an item ID from param "p" that is used on edit post page
	$item_id = param( 'p', 'integer', 0 );
}
$currentpage = param( 'currentpage', 'integer', 1 );
$comments_number = param( 'comments_number', 'integer', 0 );

// Check if current comments list displays meta comments:
$is_meta_comments_list = ( isset( $CommentList->filters['types'] ) && in_array( 'meta', $CommentList->filters['types'] ) );

if( $item_id > 0 && ! $is_meta_comments_list && $comments_number > 0 )
{	// Allow to select ONLY normal comments(EXCLUDE meta comments) for action on item view page:
	global $blog, $admin_url, $current_User;

	$Form = new Form( $admin_url );

	$Form->begin_form();
	$Form->hidden( 'ctrl', 'items' );
	$Form->hidden( 'blog', $blog );
	$Form->hidden( 'p', $item_id );
}

if( ( $item_id != 0 ) && ( $comments_number > 0 ) )
{ // Display a pagination:
	$comment_params = array_merge( $AdminUI->get_template( 'pagination' ), array( 'page_size' => $CommentList->limit ) );
	echo_comment_pages( $item_id, $currentpage, $comments_number, $comment_params );
}

if( $item_id > 0 )
{	// Don't display additional info when we are already viewing a selected post page:
	$display_meta_title = false;
}
else
{	// Display additional info of meta comment when no post page, e.g. on "Meta discussion" tab:
	$display_meta_title = true;
}

// Check if mode "Threaded comments" is active to current filterset:
$threaded_comments_mode = ! empty( $CommentList->filters['threaded_comments'] );

if( $threaded_comments_mode )
{	// This is "Threaded comments" mode, Initialize global array to store replies:
	global $CommentReplies;
	$CommentReplies = array();

	if( ( get_param( 'reply_ID' ) > 0 ) &&
	    isset( $item_ID ) &&
	    ( $Comment = get_comment_from_session( 'preview', $comment_type ) ) &&
	    ( $Comment->item_ID == $item_ID ) )
	{	// Put a preview comment in array to display it in proper place:
		$CommentReplies[ $Comment->in_reply_to_cmt_ID ] = array( $Comment );
	}
}

while( $Comment = & $CommentList->get_next() )
{ // Loop through comments:
	if( ( $show_comments == 'draft' ) && ( $Comment->get( 'status' ) != 'draft' ) )
	{ // if show only draft comments, and current comment status isn't draft, then continue with the next comment
		continue;
	}

	if( $threaded_comments_mode && $Comment->in_reply_to_cmt_ID > 0 )
	{	// Store the comment replies in a special array:
		if( !isset( $CommentReplies[ $Comment->in_reply_to_cmt_ID ] ) )
		{
			$CommentReplies[ $Comment->in_reply_to_cmt_ID ] = array();
		}
		$CommentReplies[ $Comment->in_reply_to_cmt_ID ][] = $Comment;
		// Skip dispay a comment reply here in order to dispay it after parent comment by function display_comment_replies():
		continue;
	}

	// Display a comment:
	echo_comment( $Comment, $redirect_to, $save_context, $Comment->get_inlist_order(), $display_meta_title );

	if( $threaded_comments_mode )
	{	// Display the comment replies:
		echo_comment_replies( $Comment->ID, array(
				'redirect_to'        => $redirect_to,
				'save_context'       => $save_context,
				'display_meta_title' => $display_meta_title,
			) );
	}
} //end of the loop, don't delete

if( ( $item_id != 0 ) && ( $comments_number > 0 ) )
{ // Display a pagination:
	$comment_params = array_merge( $AdminUI->get_template( 'pagination' ), array( 'page_size' => $CommentList->limit ) );
	echo_comment_pages( $item_id, $currentpage, $comments_number, $comment_params );
}

if( $item_id > 0 && ! $is_meta_comments_list && $comments_number > 0 )
{	// Allow to select ONLY normal comments(EXCLUDE meta comments) for action on item view page:
	echo T_('With checked comments').': ';

	// Display a button to change visibility of selected comments:
	$ItemCache = & get_ItemCache();
	$Item = & $ItemCache->get_by_ID( $item_id, false, false );
	$item_status = $Item ? $Item->get( 'status' ) : '';
	$Form->hidden( 'comment_status', $item_status );
	echo_comment_status_buttons( $Form, NULL, $item_status, 'set_visibility' );
	echo_status_dropdown_button_js( 'comment' );

	if( $current_User->check_perm( 'blog_post_statuses', 'edit', true, $blog ) )
	{	// Display a button to create a post from selected comments:
		echo ' '.T_('or').' ';
		$Form->button( array( 'submit', 'actionArray[create_comments_post]', T_('Create new Post'), 'btn-warning' ) );
	}

	$Form->end_form();
}

?>