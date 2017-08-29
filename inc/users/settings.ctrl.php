<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $demo_mode;

$AdminUI->set_path( 'users', 'usersettings', 'usersettings' );

$current_User->check_perm( 'users', 'view', true );

param_action();

switch ( $action )
{
	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'usersettings' );

		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		// Session settings
		$Settings->set( 'redirect_to_after_login', param( 'redirect_to_after_login', 'url', '' ) );

		$timeout_sessions = param_duration( 'timeout_sessions' );

		if( $timeout_sessions < $crumb_expires )
		{ // lower than $crumb_expires: not allowed
			param_error( 'timeout_sessions', sprintf( T_( 'You cannot set a session timeout below %d minutes.' ), floor($crumb_expires/60) ) );
		}
		elseif( $timeout_sessions < 300 )
		{ // lower than 5 minutes: not allowed
			param_error( 'timeout_sessions', sprintf( T_( 'You cannot set a session timeout below %d minutes.' ), 5 ) );
		}
		elseif( $timeout_sessions < 86400 )
		{ // lower than 1 day: notice/warning
			$Messages->add( sprintf( T_( 'Warning: your session timeout is just %d minutes. Your users may have to re-login often!' ), floor($timeout_sessions/60) ), 'note' );
		}
		$Settings->set( 'timeout_sessions', $timeout_sessions );

		// Session timeout
		$timeout_online = param_duration( 'timeout_online' );

		if( $timeout_online < 300 )
		{ // lower than 5 minutes: not allowed
			param_error( 'timeout_online', sprintf( T_( 'You cannot set an online/offline timeout below %d minutes.' ), 5 ) );
		}
		elseif( $timeout_online > 21600 )
		{ // hihger than 6 hours: notice/warning
			$Messages->add( sprintf( T_( 'You cannot set an online/offline timeout above %d hours.' ), 6 ) );
		}
		$Settings->set( 'timeout_online', $timeout_online );

		// Enable visit tracking
		param( 'enable_visit_tracking', 'integer', 0 );
		$Settings->set( 'enable_visit_tracking', $enable_visit_tracking );

		// keep old allow_avatars setting value to check if we need to invalidate pagecaches
		$old_allow_avatars = $Settings->get( 'allow_avatars' );

		// UPDATE general settings:
		param( 'allow_avatars', 'integer', 0 );
		$Settings->set( 'allow_avatars', $allow_avatars );

		param( 'uset_min_picture_size', 'integer', 0 );
		param( 'uset_nickname_editing', 'string', 'edited-user' );
		param( 'uset_firstname_editing', 'string', 'edited-user' );
		param( 'uset_lastname_editing', 'string', 'edited-user' );
		param( 'uset_location_country', 'string', 'optional' );
		param( 'uset_location_region', 'string', 'optional' );
		param( 'uset_location_subregion', 'string', 'optional' );
		param( 'uset_location_city', 'string', 'optional' );
		param( 'uset_minimum_age', 'integer', 0 );
		if( $demo_mode )
		{
			$uset_multiple_sessions = 'always';
			$Messages->add( 'Demo mode requires multiple sessions setting to be set to always.', 'note' );
		}
		else
		{
			param( 'uset_multiple_sessions', 'string', 'default-no' );
		}
		param( 'uset_emails_msgform', 'string', 'adminset' );

		if( $uset_location_city == 'required' )
		{	// If city is required - all location fields also are required
			$uset_location_country = $uset_location_region = $uset_location_subregion = 'required';
		}
		else if( $uset_location_subregion == 'required' )
		{	// If subregion is required - country & region fields also are required
			$uset_location_country = $uset_location_region = 'required';
		}
		else if( $uset_location_region == 'required' )
		{	// If region is required - country field also is required
			$uset_location_country = 'required';
		}

		$Settings->set_array( array(
									array( 'min_picture_size', $uset_min_picture_size ),
									array( 'nickname_editing', $uset_nickname_editing ),
									array( 'firstname_editing', $uset_firstname_editing ),
									array( 'lastname_editing', $uset_lastname_editing ),
									array( 'location_country', $uset_location_country ),
									array( 'location_region', $uset_location_region ),
									array( 'location_subregion', $uset_location_subregion ),
									array( 'location_city', $uset_location_city ),
									array( 'minimum_age', $uset_minimum_age ),
									array( 'multiple_sessions', $uset_multiple_sessions ),
									array( 'emails_msgform', $uset_emails_msgform ) ) );

		if( ! $Messages->has_errors() )
		{
			if( $Settings->dbupdate() )
			{
				if( $old_allow_avatars != $allow_avatars )
				{ // invalidate all PageCaches
					invalidate_pagecaches();
				}

				$Messages->add( T_('General settings updated.'), 'success' );
			}
		}

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( '?ctrl=usersettings', 303 ); // Will EXIT
		// We have EXITed already at this point!!

		break;
}


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Users'), '?ctrl=users' );
$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=usersettings' );
$AdminUI->breadcrumbpath_add( T_('Profiles'), '?ctrl=usersettings' );

// Set an url for manual page:
$AdminUI->set_page_manual_link( 'user-settings-profiles-tab' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'users/views/_settings.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>