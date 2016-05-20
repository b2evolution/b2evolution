<?php
/**
 * This file display the additional tools
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $template_action, $template_title, $Messages;

$block_item_Widget = new Widget( 'block_item' );

if( !empty( $template_action ) )
{ // Execute actions in template to display a process
	$block_item_Widget->title = empty( $template_title ) ? T_('Log') : $template_title;
	$block_item_Widget->disp_template_replaced( 'block_start' );

	switch( $template_action )
	{
		case 'test_flush':
			// Test a flush function
			tool_test_flush();
			break;

		case 'create_sample_collections':
			// Create the collections and display a process of creating:
			global $num_collections, $perm_management, $allow_access;
			tool_create_sample_collections( $num_collections, $perm_management, $allow_access );
			break;

		case 'create_sample_comments':
			// Create the comments and display a process of creating
			global $blog_ID, $num_comments, $num_posts;
			tool_create_sample_comments( $blog_ID, $num_comments, $num_posts );
			break;

		case 'create_sample_posts':
			// Create the posts and display a process of creating
			global $blog_ID, $num_posts;
			tool_create_sample_posts( $blog_ID, $num_posts );
			break;

		case 'create_sample_users':
			// Create the users and display a process of creating
			global $user_groups, $num_users, $advanced_user_perms;
			tool_create_sample_users( $user_groups, $num_users, $advanced_user_perms );
			break;

		case 'create_sample_hits':
			// Create the hits and display a process of creating
			global $days, $min_interval, $max_interval;
			tool_create_sample_hits( $days, $min_interval, $max_interval );
			break;

		case 'create_sample_messages':
			// Create the messages and display a process of creating
			global $num_loops, $num_messages, $num_words, $max_users;
			tool_create_sample_messages( $num_loops, $num_messages, $num_words, $max_users );
			break;
	}

	$block_item_Widget->disp_template_raw( 'block_end' );

	// Display the messages from tool functions
	$Messages->display();
}

// TODO: dh> this should really be a separate permission.. ("tools", "exec") or similar!
if( $current_User->check_perm('options', 'edit') )
{ // default admin actions:
	$block_item_Widget->title = T_('Testing Tools').get_manual_link( 'testing-tools' );
	$block_item_Widget->disp_template_replaced( 'block_start' );
	echo '<ul>';
	echo '<li><a href="'.regenerate_url('action', 'action=test_flush&amp;'.url_crumb('tools')).'">'.T_('Test flush').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=show_create_collections&amp;'.url_crumb('tools')).'">'.T_('Create sample collections').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=show_create_comments&amp;'.url_crumb('tools')).'">'.T_('Create sample comments').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=show_create_posts&amp;'.url_crumb('tools')).'">'.T_('Create sample posts').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=show_create_users&amp;'.url_crumb('tools')).'">'.T_('Create sample users').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=show_create_hits&amp;'.url_crumb('tools')).'">'.T_('Create sample hit data').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=show_create_messages&amp;'.url_crumb('tools')).'">'.T_('Create sample messages').'</a></li>';
	echo '</ul>';
	$block_item_Widget->disp_template_raw( 'block_end' );
}

?>