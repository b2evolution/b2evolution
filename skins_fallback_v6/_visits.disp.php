<?php
/**
 * This is the template that displays the profile visits form.
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display a feedback, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=visits
 * Note: don't code this URL by hand, use the template functions to generate it!
 *
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2017 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Collection, $Blog, $Session, $Messages, $inc_path;
global $action, $user_profile_only, $current_User, $edited_User, $form_action;
global $user_ID;

if( ! is_logged_in() )
{ // must be logged in!
	echo '<p class="error">'.T_( 'You are not logged in.' ).'</p>';
	return;
}

$UserCache = & get_UserCache();
if( empty( $user_ID ) )
{
	$user_ID = $current_User->ID;
}

$viewed_User = & $UserCache->get_by_ID( $user_ID );
$view_perm = $current_User->ID == $viewed_User->ID || $current_User->check_perm( 'users', 'view', false, $viewed_User );

// Check if admin, moderator or user with 'view details' permission
if( ! $view_perm )
{
	echo '<p class="error">'.T_( 'You have no permission to view other users!' ).'</p>';
	return;
}


$user_profile_only = true;
// check if there is unsaved User object stored in Session
$viewed_User = $Session->get( 'core.unsaved_User' );
if( $edited_User == NULL )
{ // edited_User is the current_User
	$edited_User = $current_User;
}
else
{ // unsaved user exists, delete it from Session
	$Session->delete( 'core.unsaved_User' );
	if( $edited_User->ID != $current_User->ID )
	{ // edited user ID must be the same as current User
		debug_die( 'Inconsistent state, you are allowed to edit only your profile' );
	}
}



// Display form
require $inc_path.'users/views/_user_profile_visits.view.php';

?>