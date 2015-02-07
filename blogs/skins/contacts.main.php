<?php
/**
 * This file is the template that includes required css files to display contacts
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 *
 * @version $Id: contacts.main.php 8020 2015-01-19 08:18:22Z yura $
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

if( ( $action != 'report_user' && $action != 'remove_report' ) && ( !$current_User->check_perm( 'perm_messaging', 'reply' ) ) )
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
		{	// Add user to contacts
			if( create_contacts_user( $user_ID ) )
			{	// Add user to the group
				$group_ID = param( 'group_ID', 'string', '' );
				if( $result = create_contacts_group_users( $group_ID, $user_ID, 'group_ID_combo' ) )
				{	// User has been added to the group
					$Messages->add( sprintf( T_('User has been added to the &laquo;%s&raquo; group.'), $result['group_name'] ), 'success' );
				}
				else
				{	// User has been added ONLY to the contacts list
					$Messages->add( 'User has been added to your contacts.', 'success' );
				}
			}
			header_redirect( url_add_param( $Blog->gen_blogurl(), 'disp=user&user_ID='.$user_ID, '&' ) );
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
		{	// Remove user from selected group
			if( remove_contacts_group_user( $group_ID, $user_ID ) )
			{	// User has been removed from the group
				if( $view == 'contacts' )
				{	// Redirect to the contacts list
					header_redirect( url_add_param( $Blog->gen_blogurl(), 'disp=contacts', '&' ) );
				}
				else
				{	// Redirect to the user profile page
					header_redirect( url_add_param( $Blog->gen_blogurl(), 'disp=user&user_ID='.$user_ID, '&' ) );
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
			$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=contacts', '&' );

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

			$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=contacts&g='.$group_ID, '&' );
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

			$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=contacts', '&' );
			if( $item_ID > 0 )
			{
				$redirect_to = url_add_param( $redirect_to, 'item_ID='.$item_ID, '&' );
			}

			$Messages->add( T_('The group has been deleted.'), 'success' );
			header_redirect( $redirect_to );
		}
		break;

	case 'report_user': // Report a user
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_contacts' );

		if( !$current_User->check_status( 'can_report_user' ) )
		{ // current User status doesn't allow user reporting
			// Redirect to the account activation page
			$Messages->add( T_( 'You must activate your account before you can report another user. <b>See below:</b>' ) );
			header_redirect( get_activate_info_url(), 302 );
			// will have exited
		}

		$report_status = param( 'report_user_status', 'string', '' );
		$report_info = param( 'report_info_content', 'text', '' );
		$user_ID = param( 'user_ID', 'integer', 0 );

		if( get_report_status_text( $report_status ) == '' )
		{ // A report status is incorrect
			$Messages->add( T_('Please select the correct report reason!'), 'error' );
		}

		if( ! param_errors_detected() )
		{
			// add report and block contact ( it will be blocked if was already on this user contact list )
			add_report_from( $user_ID, $report_status, $report_info );
			$blocked_message = '';
			if( $current_User->check_perm( 'perm_messaging', 'reply' ) )
			{ // user has messaging permission, set/add this user as blocked contact
				$contact_status = check_contact( $user_ID );
				if( $contact_status == NULL )
				{ // contact doesn't exists yet, create as blocked contact
					create_contacts_user( $user_ID, true );
					$blocked_message = ' '.T_('You have also blocked this user from contacting you in the future.');
				}
				elseif( $contact_status )
				{ // contact exists and it's not blocked, set as blocked
					set_contact_blocked( $user_ID, 1 );
					$blocked_message = ' '.T_('You have also blocked this user from contacting you in the future.');
				}
			}
			$Messages->add( T_('The user was reported.').$blocked_message, 'success' );
		}

		header_redirect( url_add_param( $Blog->gen_blogurl(), 'disp=user&user_ID='.$user_ID ) );
		break;

	case 'remove_report': // Remove current User report from the given user
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_contacts' );

		$user_ID = param( 'user_ID', 'integer', 0 );

		remove_report_from( $user_ID );
		$unblocked_message = '';
		if( set_contact_blocked( $user_ID, 0 ) )
		{ // the user was unblocked
			$unblocked_message = ' '.T_('You have also unblocked this user. He will be able to contact you again in the future.');
		}
		$Messages->add( T_('The report was removed.').$unblocked_message, 'success' );
		header_redirect( url_add_param( $Blog->gen_blogurl(), 'disp=user&user_ID='.$user_ID ) );
		break;
}

modules_call_method( 'switch_contacts_actions', array( 'action' => $action ) );


// var bgxy_expand is used by toggle_filter_area() and toggle_clickopen()
// var htsrv_url is used for AJAX callbacks
add_js_headline( "// Paths used by JS functions:
		var bgxy_expand = '".get_icon( 'expand', 'xy' )."';
		var bgxy_collapse = '".get_icon( 'collapse', 'xy' )."';" );

// Require results.css to display contact query results in a table
require_css( 'results.css' ); // Results/tables styles

require_js( 'form_extensions.js', 'blog' ); // Used for combo_box

// Require functions.js to show/hide a panel with filters
require_js( 'functions.js', 'blog' );
// Include this file to expand/collapse the filters panel when JavaScript is disabled
require_once $inc_path.'_filters.inc.php';

require $ads_current_skin_path.'index.main.php';

?>