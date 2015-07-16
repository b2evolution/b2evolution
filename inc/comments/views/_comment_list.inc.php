<?php
/**
 * This file implements the comment list
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
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
$redirect_to = rawurlencode( $redirect_to );
$save_context = param( 'save_context', 'boolean', 'true' );
$show_comments = param( 'show_comments', 'string', 'all' );

$item_id = param( 'item_id', 'integer', 0 );
if( empty( $item_id ) )
{ // Try to get an item ID from param "p" that is used on edit post page
	$item_id = param( 'p', 'integer', 0 );
}
$currentpage = param( 'currentpage', 'integer', 0 );
$comments_number = param( 'comments_number', 'integer', 0 );

if( ( $item_id != 0 ) && ( $comments_number > 0 ) )
{ // Display a pagination:
	$comment_params = array_merge( $AdminUI->get_template( 'pagination' ), array( 'page_size' => $CommentList->limit ) );
	echo_comment_pages( $item_id, $currentpage, $comments_number, $comment_params );
}

// Calculate index of first comment:
$comment_index = $comments_number - ( $CommentList->limit * ( $currentpage - 1 ) );

while( $Comment = & $CommentList->get_next() )
{ // Loop through comments:
	if( ( $show_comments == 'draft' ) && ( $Comment->get( 'status' ) != 'draft' ) )
	{ // if show only draft comments, and current comment status isn't draft, then continue with the next comment
		continue;
	}
	echo_comment( $Comment->ID, $redirect_to, $save_context, $comment_index );
	$comment_index--;
} //end of the loop, don't delete

if( ( $item_id != 0 ) && ( $comments_number > 0 ) )
{ // Display a pagination:
	$comment_params = array_merge( $AdminUI->get_template( 'pagination' ), array( 'page_size' => $CommentList->limit ) );
	echo_comment_pages( $item_id, $currentpage, $comments_number, $comment_params );
}

?>