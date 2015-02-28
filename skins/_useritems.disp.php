<?php
/**
 * This is the template that displays user's items
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $user_ID, $viewed_User, $display_params, $user_ItemList;

// Default params:
$params = array_merge( array(
		'user_itemlist_title'      => T_('Posts created by %s'),
		'user_itemlist_no_results' => T_('User has not created any posts'),
	), $params );


$user_ItemList->title = sprintf( $params['user_itemlist_title'], $viewed_User->get_identity_link( array( 'link_text' => 'name' ) ) );
$user_ItemList->no_results_text = $params['user_itemlist_no_results'];

// Initialize Results object
items_results( $user_ItemList, array(
		'field_prefix'       => $user_ItemList->param_prefix,
		'display_permalink'  => false,
		'display_title_flag' => false,
		'display_ord'        => false,
		'display_history'    => false,
		'display_blog'       => false,
		'display_author'     => false,
		'display_visibility_actions' => false,
		'display_actions'    => false,
	) );

$user_ItemList->display( $display_params );

?>