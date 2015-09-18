<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Check minimum permission:
$current_User->check_perm( 'users', 'view', true );

$AdminUI->set_path( 'users', 'usersettings', 'display' );

param_action();

switch ( $action )
{
	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'display' );

		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		// UPDATE display settings:
		param( 'use_gravatar', 'integer', 0 );
		param( 'default_gravatar', 'string', 'b2evo' );
		param( 'username_display', 'string', 'login' );
		param( 'gender_colored', 'integer', 0 );
		param( 'bubbletip', 'integer', 0 );
		param( 'bubbletip_size_admin', 'string', '' );
		param( 'bubbletip_size_front', 'string', '' );
		param( 'bubbletip_anonymous', 'integer', 0 );
		param( 'bubbletip_size_anonymous', 'string', '' );
		param( 'bubbletip_overlay' );
		param( 'allow_anonymous_user_list', 'integer', 0 );
		param( 'allow_anonymous_user_profiles', 'integer', 0 );
		param( 'user_url_loggedin', 'string', '' );
		param( 'user_url_anonymous', 'string', '' );

		$updated_settings = array(
			array( 'use_gravatar', $use_gravatar ),
			array( 'default_gravatar', $default_gravatar ),
			array( 'username_display', $username_display ),
			array( 'gender_colored', $gender_colored ),
			array( 'bubbletip', $bubbletip ),
			array( 'bubbletip_size_admin', $bubbletip_size_admin ),
			array( 'bubbletip_size_front', $bubbletip_size_front ),
			array( 'bubbletip_anonymous', $bubbletip_anonymous ),
			array( 'bubbletip_size_anonymous', $bubbletip_size_anonymous ),
			array( 'bubbletip_overlay', $bubbletip_overlay ),
			array( 'allow_anonymous_user_list', $allow_anonymous_user_list ),
			array( 'allow_anonymous_user_profiles', $allow_anonymous_user_profiles ),
			array( 'user_url_loggedin', $user_url_loggedin ),
			array( 'user_url_anonymous', $user_url_anonymous ) );

		if( $allow_anonymous_user_list || $allow_anonymous_user_profiles )
		{ // Update the user groups levels only if at least one users page is available for anonymous users
			param( 'allow_anonymous_user_level_min', 'integer', 0 );
			param( 'allow_anonymous_user_level_max', 'integer', 0 );
			param_check_interval( 'allow_anonymous_user_level_min', 'allow_anonymous_user_level_max', T_('User group level must be a number.'), T_('The minimum user group level must be lower than (or equal to) the maximum.') );
			if( ! param_has_error( 'allow_anonymous_user_level_min' ) && $allow_anonymous_user_level_min < 0 )
			{ // Limit by min user group level
				param_error( 'allow_anonymous_user_level_min', T_('Minimum user group level cannot be lower than 0.') );
			}
			if( ! param_has_error( 'allow_anonymous_user_level_max' ) && $allow_anonymous_user_level_max > 10 )
			{ // Limit by max user group level
				param_error( 'allow_anonymous_user_level_max', T_('Maximum user group level cannot be higher than 10.') );
			}

			$updated_settings[] = array( 'allow_anonymous_user_level_min', $allow_anonymous_user_level_min );
			$updated_settings[] = array( 'allow_anonymous_user_level_max', $allow_anonymous_user_level_max );
		}

		$Settings->set_array( $updated_settings );

		if( ! $Messages->has_errors() )
		{
			if( $Settings->dbupdate() )
			{
				// invalidate all PageCaches
				invalidate_pagecaches();

				$Messages->add( T_('Display settings updated.'), 'success' );
				// Redirect so that a reload doesn't write to the DB twice:
				header_redirect( '?ctrl=display', 303 ); // Will EXIT
				// We have EXITed already at this point!!
			}
		}

		break;
}


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Users'), '?ctrl=users' );
$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=usersettings' );
$AdminUI->breadcrumbpath_add( T_('Display'), '?ctrl=display' );

// Set an url for manual page:
$AdminUI->set_page_manual_link( 'user-settings-display-tab' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'users/views/_display.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>