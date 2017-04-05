<?php
/**
 * This is the template that displays user's comments
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $user_ID, $viewed_User, $display_params, $user_CommentList;

// Default params:
$params = array_merge( array(
		'user_commentlist_title'      => T_('Comments posted by %s'),
		'user_commentlist_no_results' => T_('User has not posted any comment yet'),
		'user_commentlist_col_post'   => T_('Comment on').':',
	), $params );


$user_CommentList->title = sprintf( $params['user_commentlist_title'], $viewed_User->get_identity_link( array( 'link_text' => 'auto' ) ) );
$user_CommentList->no_results_text = $params['user_commentlist_no_results'];

// Initialize Results object
comments_results( $user_CommentList, array(
		'field_prefix'       => $user_CommentList->param_prefix,
		'display_permalink'  => false,
		'display_item'       => true,
		'display_status'     => true,
		'display_kind'       => false,
		'display_spam'       => false,
		'display_author'     => false,
		'display_url'        => false,
		'display_email'      => false,
		'display_ip'         => false,
		'display_visibility' => false,
		'display_actions'    => false,
		'col_post'           => $params['user_commentlist_col_post'],
	) );

$user_CommentList->display( $display_params );

?>