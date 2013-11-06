<?php
/**
 * This file display the additional tools
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author efy-asimo: Attila Simo.
 *
 * @version $Id$
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $template_action, $Messages;

$block_item_Widget = new Widget( 'block_item' );

if( !empty( $template_action ) )
{ // Execute actions in template to display a process
	$block_item_Widget->title = T_('Log');
	$block_item_Widget->disp_template_replaced( 'block_start' );

	switch( $template_action )
	{
		case 'create_sample_comments':
			// Create the comments and display a process of creating
			global $blog_ID, $num_comments, $num_posts;
			tool_create_sample_comments( $blog_ID, $num_comments, $num_posts );
			break;
	}

	$block_item_Widget->disp_template_raw( 'block_end' );

	// Display the messages from tool functions
	$Messages->display();
}

// TODO: dh> this should really be a separate permission.. ("tools", "exec") or similar!
if( $current_User->check_perm('options', 'edit') )
{ // default admin actions:
	$block_item_Widget->title = T_('Testing Tools');
	$block_item_Widget->disp_template_replaced( 'block_start' );
	echo '<ul>';
	echo '<li><a href="'.regenerate_url('action', 'action=show_create_comments&amp;'.url_crumb('tools')).'">'.T_('Create sample comments').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=show_create_posts&amp;'.url_crumb('tools')).'">'.T_('Create sample posts').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=show_create_users&amp;'.url_crumb('tools')).'">'.T_('Create sample users').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=show_create_test_hit&amp;'.url_crumb('tools')).'">'.T_('Create sample hit data').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=show_create_messages&amp;'.url_crumb('tools')).'">'.T_('Create sample messages').'</a></li>';
	echo '</ul>';
	$block_item_Widget->disp_template_raw( 'block_end' );
}


/*
 * $Log$
 * Revision 1.2  2013/11/06 08:04:55  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>