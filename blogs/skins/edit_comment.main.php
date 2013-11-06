<?php
/**
 * This file is the template that includes required css files to display comment edit form
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

// comment ID
$comment_ID = param( 'c', 'integer', 0, true );

if( !is_logged_in() )
{ // Redirect to the login page if not logged in and allow anonymous user setting is OFF
	$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=edit_comment' );
	$Messages->add( T_( 'You must log in to edit comments.' ) );
	header_redirect( get_login_url( 'cannot edit comments', $redirect_to ), 302 );
	// will have exited
}

if( !$current_User->check_status( 'can_edit_comment' ) )
{
	if( $current_User->check_status( 'can_be_validated' ) )
	{ // user is logged in but his/her account was not activated yet
		// Redirect to the account activation page
		$Messages->add( T_( 'You must activate your account before you can edit comments. <b>See below:</b>' ) );
		header_redirect( get_activate_info_url(), 302 );
		// will have exited
	}

	// Redirect to the blog url for users without messaging permission
	$Messages->add( 'You are not allowed to edit comments!' );
	header_redirect( $Blog->gen_blogurl(), 302 );
}

if( empty( $comment_ID ) )
{ // Can't edit a not exisiting comment
	$Messages->add( 'Invalid comment edit URL!' );
	header_redirect( $Blog->gen_blogurl(), 302 );
}

$CommentCache = & get_CommentCache();
$edited_Comment = & $CommentCache->get_by_ID( $comment_ID );
$comment_Item = & $edited_Comment->get_Item();

if( ! $current_User->check_perm( 'comment!CURSTATUS', 'edit', false, $edited_Comment ) )
{ // If User has no permission to edit comments with this comment status:
	$Messages->add( 'You are not allowed to edit the previously selected comment!' );
	header_redirect( $Blog->gen_blogurl(), 302 );
}

$comment_title = '';
$comment_content = htmlspecialchars_decode( $edited_Comment->content );

// Format content for editing, if we were not already in editing...
$Plugins_admin = & get_Plugins_admin();
$comment_Item->load_Blog();
$params = array( 'object_type' => 'Comment', 'object_Blog' => & $comment_Item->Blog );
$Plugins_admin->unfilter_contents( $comment_title /* by ref */, $comment_content /* by ref */, $comment_Item->get_renderers_validated(), $params );

// Require datapicker.css
require_css( 'ui.datepicker.css' );
// Require results.css to display attachments as a result table
require_css( 'results.css' );

$Item = & $comment_Item;
init_ratings_js( 'blog' );
init_datepicker_js( 'blog' );

$display_params = array();

require $ads_current_skin_path.'index.main.php';
?>