<?php
/**
 * This file is the template that includes required css files to display edit form
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $current_User;

// Post ID, go from $_GET when we edit post from Front-office
$post_ID = param( 'p', 'integer', 0, true );

if( !is_logged_in() )
{ // Redirect to the login page if not logged in and allow anonymous user setting is OFF
	$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=edit' );
	$Messages->add( T_( 'You must log in to create & edit posts.' ) );
	header_redirect( get_login_url( 'cannot edit posts', $redirect_to ), 302 );
	// will have exited
}

if( !$current_User->check_status( 'can_edit_post' ) )
{
	if( $current_User->check_status( 'can_be_validated' ) )
	{ // user is logged in but his/her account was not activated yet
		// Redirect to the account activation page
		$Messages->add( T_( 'You must activate your account before you can create & edit posts. <b>See below:</b>' ) );
		header_redirect( get_activate_info_url(), 302 );
		// will have exited
	}

	// Redirect to the blog url for users without messaging permission
	$Messages->add( 'You are not allowed to create & edit posts!' );
	header_redirect( $Blog->gen_blogurl(), 302 );
}

// user logged in and the account was activated
check_item_perm_edit( $post_ID );

// Require datapicker.css
require_css( 'ui.datepicker.css' );
// Require results.css to display attachments as a result table
require_css( 'results.css' );

init_tokeninput_js( 'blog' );
require_js( 'extracats.js', 'blog' );

require $ads_current_skin_path.'index.main.php';

/*
 * $Log$
 * Revision 1.3  2013/11/06 08:05:36  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>