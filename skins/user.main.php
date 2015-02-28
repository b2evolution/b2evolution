<?php
/**
 * This file is the template that includes required css files to display a user profile
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Messages;

// get user_ID because we want it in redirect_to in case we need to ask for login.
param( 'user_ID', 'integer', '', true );
// set where to redirect in case of error
$error_redirect_to = ( empty( $Blog) ? $baseurl : $Blog->gen_blogurl() );

if( !is_logged_in() )
{ // Redirect to the login page if not logged in and allow anonymous user setting is OFF
	$user_available_by_group_level = true;
	if( ! empty( $user_ID ) )
	{
		$UserCache = & get_UserCache();
		if( $User = & $UserCache->get_by_ID( $user_ID, false ) )
		{ // If user exists we can check if the anonymous users have an access to view the user by group level limitation
			$User->get_Group();
			$user_available_by_group_level = $User->Group->level >= $Settings->get('allow_anonymous_user_level_min') && $User->Group->level <= $Settings->get('allow_anonymous_user_level_max');
		}
	}

	if( ! $Settings->get( 'allow_anonymous_user_profiles' ) || ! $user_available_by_group_level || empty( $user_ID ) )
	{ // If this user is not available for anonymous users
		$Messages->add( T_('You must log in to view this user profile.') );
		header_redirect( get_login_url('cannot see user'), 302 );
		// will have exited
	}
}

if( is_logged_in() && ( !check_user_status( 'can_view_user', $user_ID ) ) )
{ // user is logged in, but his/her status doesn't permit to view user profile
	if( check_user_status('can_be_validated') )
	{ // user is logged in but his/her account is not active yet
		// Redirect to the account activation page
		$Messages->add( T_('You must activate your account before you can view this user profile. <b>See below:</b>') );
		header_redirect( get_activate_info_url(), 302 );
		// will have exited
	}

	$Messages->add( T_('Your account status currently does not permit to view this user profile.') );
	header_redirect( $error_redirect_to, 302 );
	// will have exited
}

if( !empty($user_ID) )
{
	$UserCache = & get_UserCache();
	$User = & $UserCache->get_by_ID( $user_ID, false );

	if( empty( $User ) )
	{
		$Messages->add( T_('The requested user does not exist!') );
		header_redirect( $error_redirect_to );
		// will have exited
	}

	if( $User->check_status('is_closed') )
	{
		$Messages->add( T_('The requested user account is closed!') );
		header_redirect( $error_redirect_to );
		// will have exited
	}

	if( has_cross_country_restriction( 'any' ) )
	{
		if( empty( $current_User->ctry_ID  ) )
		{ // Current User country is not set
			$Messages->add( T_('Please specify your country before attempting to contact other users.') );
			header_redirect( get_user_profile_url() );
			// will have exited
		}

		if( has_cross_country_restriction( 'users', 'profile' ) && ( $current_User->ctry_ID !== $User->ctry_ID ) )
		{ // Current user country is different then edited user country and cross country user browsing is not enabled.
			$Messages->add( T_('You don\'t have permission to view this user profile.') );
			header_redirect( url_add_param( $error_redirect_to, 'disp=403', '&' ) );
			// will have exited
		}
	}
}

load_class( 'users/model/_userlist.class.php', 'UserList' );

// Initialize users list from session cache in order to display prev/next links
$UserList = new UserList();
$UserList->memorize = false;
$UserList->load_from_Request();

require $ads_current_skin_path.'index.main.php';

?>