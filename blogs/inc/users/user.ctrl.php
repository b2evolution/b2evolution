<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI_general
 */
global $AdminUI;

param( 'user_tab', 'string', '', true );
if( empty($user_tab) )
{
	$user_tab = 'profile';
}

$AdminUI->set_path( 'users', 'users' );

param_action();

param( 'user_ID', 'integer', NULL );	// Note: should NOT be memorized (would kill navigation/sorting) use memorize_param() if needed
param( 'redirect_to', 'url', NULL );

param( 'display_mode', 'string', 'normal' );

/**
 * @global boolean true, if user is only allowed to edit his profile
 */
$user_profile_only = ! $current_User->check_perm( 'users', 'view' );

if( $user_profile_only )
{ // User has no permissions to view: he can only edit his profile

	if( isset($user_ID) && $user_ID != $current_User->ID )
	{ // User is trying to edit something he should not: add error message (Should be prevented by UI)
		$Messages->add( T_('You have no permission to view other users!'), 'error' );
	}

	// Make sure the user only edits himself:
	$user_ID = $current_User->ID;
	if( ! in_array( $action, array( 'update', 'update_avatar', 'upload_avatar', 'edit', 'default_settings' ) ) )
	{
		$action = 'edit';
	}
}

/*
 * Load editable objects and set $action (while checking permissions)
 */

$UserCache = & get_UserCache();

if( ! is_null($user_ID) )
{ // User selected
	if( $action == 'update' && $user_ID == 0 )
	{ // we create a new user
		$edited_User = new User();
		$edited_User->set_datecreated( $localtimenow );
	}
	elseif( ($edited_User = & $UserCache->get_by_ID( $user_ID, false )) === false )
	{	// We could not find the User to edit:
		unset( $edited_User );
		forget_param( 'user_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('User') ), 'error' );
		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( '?ctrl=users', 303 ); // Will EXIT
		// We have EXITed already at this point!!
	}

	if( $action != 'view' && $action != 'report_user' && $action != 'remove_report' )
	{ // check edit permissions
		if( ! $current_User->check_perm( 'users', 'edit' )
		    && $edited_User->ID != $current_User->ID )
		{ // user is only allowed to _view_ other user's profiles
			$Messages->add( T_('You have no permission to edit other users!'), 'error' );
			$action = 'view';
		}
		elseif( $demo_mode && ( $edited_User->ID <= 3 ) && ( $edited_User->ID > 0 ) )
		{ // Demo mode restrictions: users created by install process cannot be edited
			$Messages->add( T_('You cannot edit the admin and demo users profile in demo mode!'), 'error' );

			if( strpos( $action, 'delete_' ) === 0 || $action == 'promote' )
			{   // Fallback to list/view action
				header_redirect( regenerate_url( 'ctrl,action', 'ctrl=users&amp;action=list' ) );
			}
			else
			{
				$action = 'view';
			}
		}
	}
}
elseif( $action != 'new' )
{ // user ID is not set, edit the current user
	$user_ID = $current_User->ID;
	$edited_User = $current_User;
}

/*
 * Perform actions, if there were no errors:
 */
if( !$Messages->has_errors() )
{ // no errors
	switch( $action )
	{
		case 'new':
			// We want to create a new user:
			if( isset( $edited_User ) )
			{ // We want to use a template
				$new_User = $edited_User; // Copy !
				$new_User->set( 'ID', 0 );
				$edited_User = & $new_User;
			}
			else
			{ // We use an empty user:
				$edited_User = new User();
			}
			break;

		case 'remove_avatar':
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			if( empty($edited_User) || !is_object($edited_User) )
			{
				$Messages->add( 'No user set!' ); // Needs no translation, should be prevented by UI.
				$action = 'list';
				break;
			}

			if( !$edited_User->remove_avatar() )
			{ // could not remove the avatar
				$action = 'view';
				break;
			}

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=user&user_tab=avatar&user_ID='.$edited_User->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
			break;

		case 'delete_avatar':
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			if( empty($edited_User) || !is_object($edited_User) )
			{
				$Messages->add( 'No user set!' ); // Needs no translation, should be prevented by UI.
				$action = 'list';
				break;
			}
			$file_ID = param( 'file_ID', 'integer', NULL );

			$result = $edited_User->delete_avatar( $file_ID );
			if( $result !== true )
			{
				$action = $result;
				break;
			}
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=user&user_tab=avatar&user_ID='.$edited_User->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
			break;

		case 'upload_avatar':
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			if( empty($edited_User) || !is_object($edited_User) )
			{
				$Messages->add( 'No user set!' ); // Needs no translation, should be prevented by UI.
				$action = 'list';
				break;
			}

			$result = $edited_User->update_avatar_from_upload();
			if( $result !== true )
			{
				$action = $result;
				break;
			}
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=user&user_tab=avatar&user_ID='.$edited_User->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
			break;

		case 'update_avatar':
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			if( empty($edited_User) || !is_object($edited_User) )
			{
				$Messages->add( 'No user set!' ); // Needs no translation, should be prevented by UI.
				$action = 'list';
				break;
			}
			$file_ID = param( 'file_ID', 'integer', NULL );

			$result = $edited_User->update_avatar( $file_ID );
			if( $result !== true )
			{
				$action = $result;
				break;
			}
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=user&user_tab=avatar&user_ID='.$edited_User->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
			break;

		case 'rotate_avatar_90_left':
		case 'rotate_avatar_180':
		case 'rotate_avatar_90_right':
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			if( empty($edited_User) || !is_object($edited_User) )
			{
				$Messages->add( 'No user set!' ); // Needs no translation, should be prevented by UI.
				$action = 'list';
				break;
			}
			$file_ID = param( 'file_ID', 'integer', NULL );

			switch( $action )
			{
				case 'rotate_avatar_90_left':
					$degrees = 90;
					break;
				case 'rotate_avatar_180':
					$degrees = 180;
					break;
				case 'rotate_avatar_90_right':
					$degrees = 270;
					break;
			}

			$result = $edited_User->rotate_avatar( $file_ID, $degrees );
			if( $result !== true )
			{
				switch( $result )
				{
					case 'only_own_profile':
						$action = 'view';
						break;

					case 'wrong_file':
					case 'other_user':
					case 'rotate_error':
					default:
						$action = 'edit';
						break;
				}
				break;
			}
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=user&user_tab=avatar&user_ID='.$edited_User->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
			break;

		case 'update':
		case 'add_field':
		case 'subscribe':
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Update existing user OR create new user:
			if( empty($edited_User) || !is_object($edited_User) )
			{
				$Messages->add( 'No user set!' ); // Needs no translation, should be prevented by UI.
				$action = 'list';
				break;
			}

			// if new user is true then it will redirect to user list after user has been created
			$is_new_user = $edited_User->ID == 0 ? true : false;

			$result = $edited_User->update_from_request( $is_new_user );
			if( $result !== true )
			{
				$action = $result;
				break;
			}

			if( param( 'advanced_form', 'boolean', false ) )
			{
				$current_admin_skin = param( 'current_admin_skin', 'string' );
				if( ( $current_admin_skin == $UserSettings->get( 'admin_skin', $current_User->ID ) ) &&
					( $current_admin_skin == $UserSettings->get( 'admin_skin', $edited_User->ID ) ) )
				{ // Save Admin skin display settings if admin skin wasn't changed, and
					// edited user admin skin is the same as current user admin skin
					$AdminUI->set_skin_settings( $edited_User->ID );
				}

				if( $UserSettings->dbupdate() )
				{
					$Messages->add( T_('User feature settings have been changed.'), 'success');
				}

				// PluginUserSettings
				load_funcs('plugins/_plugin.funcs.php');

				$any_plugin_settings_updated = false;
				$Plugins->restart();
				while( $loop_Plugin = & $Plugins->get_next() )
				{
					$pluginusersettings = $loop_Plugin->GetDefaultUserSettings( $tmp_params = array('for_editing'=>true) );
					if( empty($pluginusersettings) )
					{
						continue;
					}

					// Loop through settings for this plugin:
					foreach( $pluginusersettings as $set_name => $set_meta )
					{
						autoform_set_param_from_request( $set_name, $set_meta, $loop_Plugin, 'UserSettings', $edited_User );
					}

					// Let the plugin handle custom fields:
					$ok_to_update = $Plugins->call_method( $loop_Plugin->ID, 'PluginUserSettingsUpdateAction', $tmp_params = array(
						'User' => & $edited_User, 'action' => 'save' ) );

					if( $ok_to_update === false )
					{
						$loop_Plugin->UserSettings->reset();
					}
					elseif( $loop_Plugin->UserSettings->dbupdate() )
					{
						$any_plugin_settings_updated = true;
					}
				}

				if( $any_plugin_settings_updated )
				{
					$Messages->add( T_('Usersettings of Plugins have been updated.'), 'success' );
				}
			}

			if( $is_new_user )
			{ // New user is created

				// Reset the filters in order to the new user can be seen
				load_class( 'users/model/_userlist.class.php', 'UserList' );
				$UserList = new UserList( 'admin' );
				$UserList->refresh_query = true;
				$UserList->query();

				header_redirect( regenerate_url( 'ctrl,action', 'ctrl=users&amp;action=list', '', '&' ), 303 );
			}
			else
			{ // The user is updated
				if( ( $user_tab == 'admin' ) && ( $edited_User->ID == $current_User->ID ) )
				{ // an admin user has edited his own admin preferences
					if( $current_User->check_status( 'is_closed' ) )
					{ // an admin user has changed his own status to closed, logout the user
						logout();
						header_redirect( $baseurl, 303 );
						// will have exited
					}
					if( $current_User->grp_ID != 1 )
					{ // admin user has changed his own group, change user_tab for redirect
						$user_tab = 'profile';
					}
				}
				header_redirect( regenerate_url( '', 'user_ID='.$edited_User->ID.'&action=edit&user_tab='.$user_tab, '', '&' ), 303 );
			}
			break;

		case 'default_settings':
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			$reload_page = false; // We set it to true, if a setting changes that needs a page reload (locale, admin skin, ..)

			// Admin skin:
			$cur_admin_skin = $UserSettings->get('admin_skin');

			$UserSettings->delete( 'admin_skin', $edited_User->ID );
			if( $cur_admin_skin
					&& $UserSettings->get('admin_skin', $edited_User->ID ) != $cur_admin_skin
					&& ($edited_User->ID == $current_User->ID) )
			{ // admin_skin has changed:
				$reload_page = true;
			}

			// Reset user settings to defaults:
			$UserSettings->reset_to_defaults( $edited_User->ID, false );

			// Update user settings:
			if( $UserSettings->dbupdate() ) $Messages->add( T_('User feature settings have been changed.'), 'success');

			// PluginUserSettings
			$any_plugin_settings_updated = false;
			$Plugins->restart();
			while( $loop_Plugin = & $Plugins->get_next() )
			{
				$pluginusersettings = $loop_Plugin->GetDefaultUserSettings( $tmp_params = array('for_editing'=>true) );

				if( empty($pluginusersettings) )
				{
					continue;
				}

				foreach( $pluginusersettings as $k => $l_meta )
				{
					if( isset($l_meta['layout']) || ! empty($l_meta['no_edit']) )
					{ // a layout "setting" or not for editing
						continue;
					}

					$loop_Plugin->UserSettings->delete($k, $edited_User->ID);
				}

				// Let the plugin handle custom fields:
				$ok_to_update = $Plugins->call_method( $loop_Plugin->ID, 'PluginUserSettingsUpdateAction', $tmp_params = array(
					'User' => & $edited_User, 'action' => 'reset' ) );

				if( $ok_to_update === false )
				{
					$loop_Plugin->UserSettings->reset();
				}
				elseif( $loop_Plugin->UserSettings->dbupdate() )
				{
					$any_plugin_settings_updated = true;
				}
			}
			if( $any_plugin_settings_updated )
			{
				$Messages->add( T_('Usersettings of Plugins have been updated.'), 'success' );
			}

			// Always display the profile again:
			$action = 'edit';

			if( $reload_page )
			{ // reload the current page through header redirection:
				header_redirect( regenerate_url( '', 'user_ID='.$edited_User->ID.'&action='.$action, '', '&' ) ); // will save $Messages into Session
			}
			break;

		case 'refresh_regional':
			// Refresh a regions, sub-regions & cities (when JavaScript is disabled)

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			$edited_User->ctry_ID = param( 'edited_user_ctry_ID', 'integer', 0 );
			$edited_User->rgn_ID = param( 'edited_user_rgn_ID', 'integer', 0 );
			$edited_User->subrg_ID = param( 'edited_user_subrg_ID', 'integer', 0 );
			break;

		case 'delete_all_blogs':
			// Delete all blogs of edited user recursively

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Check edit permissions:
			$current_User->check_perm( 'users', 'edit', true );

			if( param( 'confirm', 'integer', 0 ) )
			{	// confirmed
				if( $edited_User->delete_blogs() )
				{	// The blogs were deleted successfully
					$Messages->add( T_('All blogs of the user were deleted.'), 'success' );

					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=user&user_tab=activity&user_ID='.$user_ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}
			}
			break;

		case 'delete_all_posts_created':
			// Delete all posts created by the user

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Check edit permissions:
			$current_User->check_perm( 'users', 'edit', true );

			if( param( 'confirm', 'integer', 0 ) )
			{	// confirmed
				if( $edited_User->delete_posts( 'created' ) )
				{	// The posts were deleted successfully
					$Messages->add( T_('The posts created by the user were deleted.'), 'success' );

					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=user&user_tab=activity&user_ID='.$user_ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}
			}
			break;

		case 'delete_all_posts_edited':
			// Delete all posts edited by the user

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Check edit permissions:
			$current_User->check_perm( 'users', 'edit', true );

			if( param( 'confirm', 'integer', 0 ) )
			{	// confirmed
				if( $edited_User->delete_posts( 'edited' ) )
				{	// The posts were deleted successfully
					$Messages->add( T_('The posts edited by the user were deleted.'), 'success' );

					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=user&user_tab=activity&user_ID='.$user_ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}
			}
			break;

		case 'delete_all_comments':
			// Delete all comments posted by the user

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Check edit permissions:
			$current_User->check_perm( 'users', 'edit', true );

			if( param( 'confirm', 'integer', 0 ) )
			{	// confirmed
				if( $edited_User->delete_comments() )
				{	// The posts were deleted successfully
					$Messages->add( T_('The comments posted by the user were deleted.'), 'success' );

					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=user&user_tab=activity&user_ID='.$user_ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}
			}
			break;

		case 'delete_all_messages':
			// Delete all messages posted by the user

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Check edit permissions:
			$current_User->check_perm( 'users', 'edit', true );

			if( param( 'confirm', 'integer', 0 ) )
			{	// confirmed
				if( $edited_User->delete_messages() )
				{	// The messages were deleted successfully
					$Messages->add( T_('The private messages sent by the user were deleted.'), 'success' );

					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=user&user_tab=activity&user_ID='.$user_ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}
			}
			break;

		case 'delete_all_userdata':
			// Delete user and all his contributions

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Check edit permissions:
			$current_User->check_perm( 'users', 'edit', true );

			if( $edited_User->ID == $current_User->ID || $edited_User->ID == 1 )
			{	// Don't delete a logged in user
				break;
			}

			if( param( 'confirm', 'integer', 0 ) )
			{	// confirmed
				$user_login = $edited_User->dget( 'login' );

				if( $edited_User->delete_messages() &&
				    $edited_User->delete_comments() &&
				    $edited_User->delete_posts( 'created|edited' ) &&
				    $edited_User->delete_blogs() &&
				    $edited_User->dbdelete( $Messages ) )
				{	// User and all his contributions were deleted successfully
					$Messages->add( sprintf( T_('The user &laquo;%s&raquo; and all his contributions were deleted.'), $user_login ), 'success' );

					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=users', 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}
			}
			break;

		case 'report_user': // Report a user
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			if( !$current_User->check_status( 'can_report_user' ) )
			{ // current User status doesn't allow user reporting
				// Redirect to the account activation page
				$Messages->add( T_( 'You must activate your account before you can report another user. <b>See below:</b>' ), 'error' );
				header_redirect( get_activate_info_url(), 302 );
				// will have exited
			}

			$report_status = param( 'report_user_status', 'string', '' );
			$report_info = param( 'report_info_content', 'text', '' );
			$user_ID = param( 'user_ID', 'integer', 0 );

			if( get_report_status_text( $report_status ) == '' )
			{ // A report status is incorrect
				$Messages->add( T_('Please select the correct report reason!'), 'error' );
				$user_tab = 'report';
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
				$Messages->add( T_('The user was repoted.').$blocked_message, 'success' );
			}

			header_redirect( $admin_url.'?ctrl=user&user_tab='.$user_tab.'&user_ID='.$user_ID );
			break;

	case 'remove_report': // Remove current User report from the given user
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'user' );

		$user_ID = param( 'user_ID', 'integer', 0 );

		remove_report_from( $user_ID );
		$unblocked_message = '';
		if( set_contact_blocked( $user_ID, 0 ) )
		{ // the user was unblocked
			$unblocked_message = ' '.T_('You have also unblocked this user. He will be able to contact you again in the future.');
		}
		$Messages->add( T_('The report was removed.').$unblocked_message, 'success' );
		header_redirect( $admin_url.'?ctrl=user&user_tab='.$user_tab.'&user_ID='.$user_ID );
		break;
	}
}

if( $display_mode != 'js')
{
	// Display a form to quick search users
	$AdminUI->top_block = get_user_quick_search_form();

	// require colorbox js
	require_js_helper( 'colorbox', 'rsc_url' );

	$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
	$AdminUI->breadcrumbpath_add( T_('Users'), '?ctrl=users' );
	if( $action == 'new' )
	{
		$AdminUI->breadcrumbpath_add( $edited_User->login, '?ctrl=user&amp;user_ID='.$edited_User->ID );
	}
	else
	{
		$AdminUI->breadcrumbpath_add( $edited_User->get_colored_login(), '?ctrl=user&amp;user_ID='.$edited_User->ID );
	}

	switch( $user_tab )
	{
		case 'profile':
			$AdminUI->breadcrumbpath_add( T_('Profile'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
			require_css( $rsc_url.'css/jquery/smoothness/jquery-ui.css' );
			init_userfields_js();
			break;
		case 'avatar':
			if( isset($GLOBALS['files_Module']) )
			{
				$AdminUI->breadcrumbpath_add( T_('Profile picture'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
			}
			break;
		case 'pwdchange':
			$AdminUI->breadcrumbpath_add( T_('Change password'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
			break;
		case 'userprefs':
			$AdminUI->breadcrumbpath_add( T_('Preferences'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
			break;
		case 'subs':
			$AdminUI->breadcrumbpath_add( T_('Notifications'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
			break;
		case 'advanced':
			$AdminUI->breadcrumbpath_add( T_('Advanced'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
			break;
		case 'admin':
			$AdminUI->breadcrumbpath_add( T_('Admin'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
			load_funcs('tools/model/_email.funcs.php');
			break;
		case 'sessions':
			$AdminUI->breadcrumbpath_add( T_('Sessions'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
			break;
		case 'activity':
			$AdminUI->breadcrumbpath_add( $current_User->ID == $edited_User->ID ? T_('My Activity') : T_('User Activity'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
			require_css( $rsc_url.'css/blog_base.css' ); // Default styles for the blog navigation
			break;
	}

	// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
	$AdminUI->disp_html_head();

	// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
	$AdminUI->disp_body_top();
}

/*
 * Display appropriate payload:
 */
switch( $action )
{
	case 'nil':
		// Display NO payload!
		break;

	case 'new':
	case 'view':
	case 'edit':
	default:
		load_class( 'users/model/_userlist.class.php', 'UserList' );
		// Initialize users list from session cache in order to display prev/next links
		$UserList = new UserList( 'admin' );
		$UserList->memorize = false;
		$UserList->load_from_Request();

		switch( $user_tab )
		{
			case 'profile':
				// Display user identity form:
				$AdminUI->disp_payload_begin();
				$AdminUI->disp_view( 'users/views/_user_identity.form.php' );
				$AdminUI->disp_payload_end();
				break;
			case 'avatar':
				// Display user avatar form:
				if( $Settings->get('allow_avatars') )
				{
					$AdminUI->disp_payload_begin();
					$AdminUI->disp_view( 'users/views/_user_avatar.form.php' );
					$AdminUI->disp_payload_end();
				}
				break;
			case 'pwdchange':
				// Display user password form:
				$AdminUI->disp_payload_begin();
				$AdminUI->disp_view( 'users/views/_user_password.form.php' );
				$AdminUI->disp_payload_end();
				break;
			case 'userprefs':
				// Display user preferences form:
				$AdminUI->disp_payload_begin();
				$AdminUI->disp_view( 'users/views/_user_preferences.form.php' );
				$AdminUI->disp_payload_end();
				break;
			case 'subs':
				// Display user subscriptions form:
				$AdminUI->disp_payload_begin();
				$AdminUI->disp_view( 'users/views/_user_subscriptions.form.php' );
				$AdminUI->disp_payload_end();
				break;
			case 'advanced':
				// Display user advanced form:
				$AdminUI->disp_view( 'users/views/_user_advanced.form.php' );
				break;
			case 'admin':
				// Display user admin form:
				$AdminUI->disp_view( 'users/views/_user_admin.form.php' );
				break;
			case 'sessions':
				// Display user admin form:
				$AdminUI->disp_view( 'sessions/views/_stats_sessions_list.view.php' );
				break;
			case 'activity':
				// Display user activity lists:
				$AdminUI->disp_payload_begin();

				if( in_array( $action, array( 'delete_all_blogs', 'delete_all_posts_created', 'delete_all_posts_edited', 'delete_all_comments', 'delete_all_messages', 'delete_all_userdata' ) ) )
				{	// We need to ask for confirmation before delete:
					param( 'user_ID', 'integer', 0 , true ); // Memorize user_ID
					// Create Data Object to user only one method confirm_delete()
					$DataObject = new DataObject( '' );
					switch( $action )
					{
						case 'delete_all_blogs':
							$deleted_blogs_count = count( $edited_User->get_deleted_blogs() );
							if( $deleted_blogs_count > 0 )
							{	// Display a confirm message if curent user can delete at least one blog of the edited user
								$confirm_message = sprintf( T_('Delete %d blogs of the user?'), $deleted_blogs_count );
							}
							break;

						case 'delete_all_posts_created':
							$deleted_posts_created_count = count( $edited_User->get_deleted_posts( 'created' ) );
							if( $deleted_posts_created_count > 0 )
							{	// Display a confirm message if curent user can delete at least one post created by the edited user
								$confirm_message = sprintf( T_('Delete %d posts created by the user?'), $deleted_posts_created_count );
							}
							break;

						case 'delete_all_posts_edited':
							$deleted_posts_edited_count = count( $edited_User->get_deleted_posts( 'edited' ) );
							if( $deleted_posts_edited_count > 0 )
							{	// Display a confirm message if curent user can delete at least one post created by the edited user
								$confirm_message = sprintf( T_('Delete %d posts edited by the user?'), $deleted_posts_edited_count );
							}
							break;

						case 'delete_all_comments':
							$deleted_comments_count = count( $edited_User->get_deleted_comments() );
							if( $deleted_comments_count > 0 )
							{	// Display a confirm message if curent user can delete at least one comment posted by the edited user
								$confirm_message = sprintf( T_('Delete %d comments posted by the user?'), $deleted_comments_count );
							}
							break;

						case 'delete_all_messages':
							$messages_count = $edited_User->get_num_messages();
							if( $messages_count > 0 && $current_User->check_perm( 'perm_messaging', 'abuse' ) )
							{	// Display a confirm message if curent user can delete the messages sent by the edited user
								$confirm_message = sprintf( T_('Delete %d private messages sent by the user?'), $messages_count );
							}
							break;

						case 'delete_all_userdata':
							if(  $current_User->ID != $edited_User->ID && $edited_User->ID != 1 )
							{	// User can NOT delete admin and own account
								$confirm_message = T_('Delete user and all his contributions?');
							}
							break;
					}
					if( !empty( $confirm_message ) )
					{	// Displays form to confirm deletion
						$DataObject->confirm_delete( $confirm_message, 'user', $action, get_memorized( 'action' ) );
					}
				}

				$AdminUI->disp_view( 'users/views/_user_activity.view.php' );
				$AdminUI->disp_payload_end();
				break;

			case 'report':
				if( $display_mode == 'js')
				{ // Do not append Debuglog & Debug JSlog to response!
					$debug = false;
					$debug_jslog = false;
				}

				if( $display_mode != 'js')
				{
					$AdminUI->disp_payload_begin();
				}
				$user_tab = param( 'user_tab_from', 'string', 'profile' );
				$AdminUI->disp_view( 'users/views/_user_report.form.php' );
				if( $display_mode != 'js')
				{
					$AdminUI->disp_payload_end();
				}
				break;
		}

		break;
}

if( $display_mode != 'js')
{
	// Init JS for user reporting
	echo_user_report_js();

	// Display body bottom, debug info and close </html>:
	$AdminUI->disp_global_footer();
}
?>