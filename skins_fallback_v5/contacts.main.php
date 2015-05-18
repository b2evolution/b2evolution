<?php
/**
 * This file is the template that includes required css files to display contacts
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $htsrv_url, $Messages, $current_User;

if( !is_logged_in() )
{ // Redirect to the login page for anonymous users
	$Messages->add( T_( 'You must log in to manage your contacts.' ) );
	header_redirect( get_login_url('cannot see contacts'), 302 );
	// will have exited
}

if( !$current_User->check_status( 'can_view_contacts' ) )
{ // user is logged in, but his status doesn't allow to view contacts
	if( $current_User->check_status( 'can_be_validated' ) )
	{ // user is logged in but his/her account was not activated yet
		// Redirect to the account activation page
		$Messages->add( T_( 'You must activate your account before you can manage your contacts. <b>See below:</b>' ) );
		header_redirect( get_activate_info_url(), 302 );
		// will have exited
	}

	// Redirect to the blog url for users without messaging permission
	$Messages->add( 'You are not allowed to view Contacts!' );
	$blogurl = $Blog->gen_blogurl();
	// If it was a front page request or the front page is set to display 'contacts' then we must not redirect to the front page because it is forbidden for the current User
	$redirect_to = ( is_front_page() || ( $Blog->get_setting( 'front_disp' ) == 'contacts' ) ) ? url_add_param( $blogurl, 'disp=403', '&' ) : $blogurl;
	header_redirect( $redirect_to, 302 );
}

if( has_cross_country_restriction( 'any' ) && empty( $current_User->ctry_ID ) )
{ // User may browse/contact other users only from the same country
	$Messages->add( T_('Please specify your country before attempting to contact other users.') );
	header_redirect( get_user_profile_url() );
}

// Get action parameter from request:
param_action();

if( ! $current_User->check_perm( 'perm_messaging', 'reply' ) )
{ // Redirect to the blog url for users without messaging permission
	$Messages->add( 'You are not allowed to view Contacts!' );
	$blogurl = $Blog->gen_blogurl();
	// If it was a front page request or the front page is set to display 'contacts' then we must not redirect to the front page because it is forbidden for the current User
	$redirect_to = ( is_front_page() || ( $Blog->get_setting( 'front_disp' ) == 'contacts' ) ) ? url_add_param( $blogurl, 'disp=403', '&' ) : $blogurl;
	header_redirect( $redirect_to, 302 );
	// will have exited
}

switch( $action )
{
	case 'add_user': // Add user to contacts list
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_contacts' );

		$user_ID = param( 'user_ID', 'integer', 0 );
		if( $user_ID > 0 )
		{ // Add user to contacts
			if( create_contacts_user( $user_ID ) )
			{ // Add user to the group
				$group_ID = param( 'group_ID', 'string', '' );
				if( $result = create_contacts_group_users( $group_ID, $user_ID, 'group_ID_combo' ) )
				{ // User has been added to the group
					$Messages->add( sprintf( T_('User has been added to the &laquo;%s&raquo; group.'), $result['group_name'] ), 'success' );
				}
				else
				{ // User has been added ONLY to the contacts list
					$Messages->add( 'User has been added to your contacts.', 'success' );
				}
			}
			header_redirect( $Blog->get( 'userurl', array( 'url_suffix' => 'user_ID='.$user_ID, 'glue' => '&' ) ) );
		}
		break;

	case 'unblock': // Unblock user
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_contacts' );

		$user_ID = param( 'user_ID', 'integer', 0 );
		if( $user_ID > 0 )
		{
			set_contact_blocked( $user_ID, 0 );
			$Messages->add( T_('Contact was unblocked.'), 'success' );
		}
		break;

	case 'remove_user': // Remove user from contacts group
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_contacts' );

		$view = param( 'view', 'string', 'profile' );
		$user_ID = param( 'user_ID', 'integer', 0 );
		$group_ID = param( 'group_ID', 'integer', 0 );
		if( $user_ID > 0 && $group_ID > 0 )
		{ // Remove user from selected group
			if( remove_contacts_group_user( $group_ID, $user_ID ) )
			{ // User has been removed from the group
				if( $view == 'contacts' )
				{ // Redirect to the contacts list
					header_redirect( $Blog->get( 'contactsurl', array( 'glue' => '&' ) ) );
				}
				else
				{ // Redirect to the user profile page
					header_redirect( $Blog->get( 'userurl', array( 'url_suffix' => 'user_ID='.$user_ID, 'glue' => '&' ) ) );
				}
			}
		}
		break;

	case 'add_group': // Add users to the group
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_contacts' );

		$group = param( 'group', 'string', '' );
		$users = param( 'users', 'string', '' );

		if( $result = create_contacts_group_users( $group, $users ) )
		{	// Users have been added to the group
			$Messages->add( sprintf( T_('%d contacts have been added to the &laquo;%s&raquo; group.'), $result['count_users'], $result['group_name'] ), 'success' );
			$redirect_to = $Blog->get( 'contactsurl', array( 'glue' => '&' ) );

			$item_ID = param( 'item_ID', 'integer', 0 );
			if( $item_ID > 0 )
			{
				$redirect_to = url_add_param( $redirect_to, 'item_ID='.$item_ID, '&' );
			}
			header_redirect( $redirect_to );
		}
		break;

	case 'rename_group': // Rename the group
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_contacts' );

		$group_ID = param( 'group_ID', 'integer', true );

		if( rename_contacts_group( $group_ID ) )
		{
			$item_ID = param( 'item_ID', 'integer', 0 );

			$redirect_to = url_add_param( $Blog->get( 'contactsurl', array( 'glue' => '&' ) ), 'g='.$group_ID, '&' );
			if( $item_ID > 0 )
			{
				$redirect_to = url_add_param( $redirect_to, 'item_ID='.$item_ID, '&' );
			}

			$Messages->add( T_('The group has been renamed.'), 'success' );
			header_redirect( $redirect_to );
		}
		break;

	case 'delete_group': // Delete the group
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_contacts' );

		$group_ID = param( 'group_ID', 'integer', true );

		if( delete_contacts_group( $group_ID ) )
		{
			$item_ID = param( 'item_ID', 'integer', 0 );

			$redirect_to = $Blog->get( 'contactsurl', array( 'glue' => '&' ) );
			if( $item_ID > 0 )
			{
				$redirect_to = url_add_param( $redirect_to, 'item_ID='.$item_ID, '&' );
			}

			$Messages->add( T_('The group has been deleted.'), 'success' );
			header_redirect( $redirect_to );
		}
		break;
}

modules_call_method( 'switch_contacts_actions', array( 'action' => $action ) );

// Require results.css to display contact query results in a table
require_css( 'results.css' ); // Results/tables styles

require_js( 'form_extensions.js', 'blog' ); // Used for combo_box

// Require functions.js to show/hide a panel with filters
require_js( 'functions.js', 'blog' );
// Include this file to expand/collapse the filters panel when JavaScript is disabled
require_once $inc_path.'_filters.inc.php';

require $ads_current_skin_path.'index.main.php';

?>