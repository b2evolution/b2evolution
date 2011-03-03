<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI_general
 */
global $AdminUI;

param( 'user_tab', 'string' );
if( empty($user_tab) )
{
	$user_tab = 'identity';
}

$AdminUI->set_path( 'users', $user_tab );

param_action();

param( 'user_ID', 'integer', NULL );	// Note: should NOT be memorized (would kill navigation/sorting) use memorize_param() if needed

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
	if( ! in_array( $action, array( 'update', 'update_avatar', 'edit', 'default_settings' ) ) )
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
		$action = 'list';
	}

	if( $action != 'view' )
	{ // check edit permissions
		if( ! $current_User->check_perm( 'users', 'edit' )
		    && $edited_User->ID != $current_User->ID )
		{ // user is only allowed to _view_ other user's profiles
			$Messages->add( T_('You have no permission to edit other users!'), 'error' );
			$action = 'view';
		}
		elseif( $demo_mode )
		{ // Demo mode restrictions: admin/demouser cannot be edited
			if( $edited_User->ID == 1 || $edited_User->login == 'demouser' )
			{
				$Messages->add( T_('You cannot edit the admin and demouser profile in demo mode!'), 'error' );

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

			// Determine if the user must validate before using the system:
			$edited_User->set( 'validated', ! $Settings->get('newusers_mustvalidate') );
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

			if( !$current_User->check_perm( 'users', 'edit' ) && $edited_User->ID != $current_User->ID )
			{ // user is only allowed to update him/herself
				$Messages->add( T_('You are only allowed to update your own profile!'), 'error' );
				$action = 'view';
				break;
			}

			$edited_User->set( 'avatar_file_ID', NULL, true );

			$edited_User->dbupdate();

			$Messages->add( T_('Avatar has been removed.'), 'success' );

			header_redirect( '?ctrl=user&user_tab=avatar&user_ID='.$edited_User->ID, 303 ); // will save $Messages into Session
			/* EXITED */
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

			if( !$current_User->check_perm( 'users', 'edit' ) && $edited_User->ID != $current_User->ID )
			{ // user is only allowed to update him/herself
				$Messages->add( T_('You are only allowed to update your own profile!'), 'error' );
				$action = 'view';
				break;
			}

			// process upload
			$FileRootCache = & get_FileRootCache();
			$root = FileRoot::gen_ID( 'user', $edited_User->ID );
			$result = process_upload( $root, 'profile_pictures', true, false, true, false );
			if( empty( $result ) )
			{
				$Messages->add( T_( 'You don\'t have permission to selected user file rott.' ), 'error' );
			}
			else
			{
				$uploadedFiles = $result['uploadedFiles'];
				if( !empty( $uploadedFiles ) )
				{ // upload was successful
					$File = $uploadedFiles[0];
					if( $File->is_image() )
					{ // set uploaded image as avatar
						$edited_User->set( 'avatar_file_ID', $File->ID, true );
						$edited_User->dbupdate();
						$Messages->add( T_('Avatar has been set successfull.'), 'success' );
						$action = 'avatar';
						break;
					}
					else
					{ // uploaded file is not an image, delete the file
						$Messages->add( T_( 'You can only set an image file to avatar!' ) );
						$File->unlink();
					}
				}

				$failedFiles = $result['failedFiles'];
				if( !empty( $failedFiles ) )
				{
					$Messages->add( $failedFiles[0] );
				}
			}

			$action = 'avatar';
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

			if( !$current_User->check_perm( 'users', 'edit' ) && $edited_User->ID != $current_User->ID )
			{ // user is only allowed to update him/herself
				$Messages->add( T_('You are only allowed to update your own profile!'), 'error' );
				$action = 'view';
				break;
			}

			$file_ID = param( 'file_ID', 'integer', NULL );
			if( $file_ID == NULL )
			{
				$Messages->add( T_('Could not change the avatar!'), 'error' );
				$action = 'view';
				break;
			}

			$edited_User->set( 'avatar_file_ID', $file_ID, true );

			$edited_User->dbupdate();

			$Messages->add( T_('Avatar has been set successfull.'), 'success' );
			$action = 'avatar';
			break;

		case 'update':
			// Update existing user OR create new user:
			if( empty($edited_User) || !is_object($edited_User) )
			{
				$Messages->add( 'No user set!' ); // Needs no translation, should be prevented by UI.
				$action = 'list';
				break;
			}

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			//$reload_page = false; // We set it to true, if a setting changes that needs a page reload (locale, admin skin, ..)

			if( !$current_User->check_perm( 'users', 'edit' ) && $edited_User->ID != $current_User->ID )
			{ // user is only allowed to update him/herself
				$Messages->add( T_('You are only allowed to update your own profile!'), 'error' );
				$action = 'view';
				break;
			}

			// if new user is true then it will redirect to user list after user has been created
			$is_new_user = $edited_User->ID == 0 ? true : false;

			// memorize user old login and root path, before update
			$edited_user_old_login = $edited_User->login;
			$edited_user_root_path = NULL;
			$FileRootCache = & get_FileRootCache();
			if( !$is_new_user )
			{
				$user_FileRoot = & $FileRootCache->get_by_type_and_ID( 'user', $edited_User->ID );
				if( $user_FileRoot && file_exists( $user_FileRoot->ads_path ) )
				{
					$edited_user_root_path = $user_FileRoot->ads_path;
				}
			}

			// load data from request
			if( !$edited_User->load_from_Request() )
			{	// We have found validation errors:
				$action = 'edit';
				break;
			}

			// Update user
			$DB->begin();

			$is_password_form = param( 'password_form', 'boolean', false );
			if( $edited_User->dbsave() )
			{
				$update_success = true;
				if( $is_new_user )
				{
					$Messages->add( T_('New user has been created.'), 'success' );
				}
				elseif( $is_password_form )
				{
					$Messages->add( T_('Password has been changed.'), 'success' );
				}
				else
				{
					if( $edited_user_old_login != $edited_User->login && $edited_user_root_path != NULL )
					{ // user login changed and user has a root directory (another way $edited_user_root_path value would be NULL)
						$FileRootCache->clear();
						$user_FileRoot = & $FileRootCache->get_by_type_and_ID( 'user', $edited_User->ID );
						if( $user_FileRoot )
						{ // user FilerRooot exists, rename user root folder
							if( ! @rename( $edited_user_root_path, $user_FileRoot->ads_path ) )
							{ // unsuccessful folder rename
								$Messages->add( sprintf( T_('You cannot choose the new login "%s" (cannot rename user fileroot)'), $edited_User->login), 'error' );
								$update_success = false;
							}
						}
					}
					if( $update_success )
					{
						$Messages->add( T_('Profile has been updated.'), 'success' );
					}
				}

				if( $update_success )
				{
					$DB->commit();
				}
				else
				{
					$DB->rollback();
				}
			}
			else
			{
				$DB->rollback();
				$Messages->add( 'New user creation error', 'error' );
			}

			// Update user settings:
			if( param( 'preferences_form', 'boolean', false ) )
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
			{
				header_redirect( regenerate_url( 'ctrl,action', 'ctrl=users&amp;action=list', '', '&' ), 303 );
			}
			else
			{
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

			// Remove all UserSettings where a default exists:
			foreach( $UserSettings->_defaults as $k => $v )
			{
				$UserSettings->delete( $k, $edited_User->ID );
			}

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
	}
}


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Users'), '?ctrl=users' );
$AdminUI->breadcrumbpath_add( $edited_User->login, '?ctrl=user&amp;user_ID='.$edited_User->ID );
switch( $user_tab )
{
	case 'identity':
		$AdminUI->breadcrumbpath_add( T_('Identity'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
		break;
	case 'avatar':
		if( isset($GLOBALS['files_Module']) )
		{
			$AdminUI->breadcrumbpath_add( T_('Avatar'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
		}
		break;
	case 'password':
		$AdminUI->breadcrumbpath_add( T_('Change password'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
		break;
	case 'preferences':
		$AdminUI->breadcrumbpath_add( T_('Preferences'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
		break;
	case 'blogs':
		$AdminUI->breadcrumbpath_add( T_('Personal blogs'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
		break;
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

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

		switch( $user_tab )
		{
			case 'identity':
				// Display user identity form:
				$AdminUI->disp_view( 'users/views/_user_identity.form.php' );
				break;
			case 'avatar':
				// Display user avatar form:
				if( $Settings->get('allow_avatars') )
				{
					$AdminUI->disp_view( 'users/views/_user_avatar.form.php' );
				}
				break;
			case 'password':
				// Display user password form:
				$AdminUI->disp_view( 'users/views/_user_password.form.php' );
				break;
			case 'preferences':
				// Display user preferences form:
				$AdminUI->disp_view( 'users/views/_user_preferences.form.php' );
				break;
			case 'blogs':
				// Display user blog list:
				$AdminUI->disp_view( 'users/views/_user_coll_list.view.php' );
				break;
		}

		break;
}


// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.26  2011/03/03 14:31:57  efy-asimo
 * use user.ctrl for avatar upload
 * create File object in the db if an avatar file is already on the user's profile picture folder
 *
 * Revision 1.25  2011/01/18 16:23:03  efy-asimo
 * add shared_root perm and refactor file perms - part1
 *
 * Revision 1.24  2011/01/11 09:31:34  efy-asimo
 * rename user private file root when changing the login of the user
 *
 * Revision 1.23  2010/11/25 15:16:35  efy-asimo
 * refactor $Messages
 *
 * Revision 1.22  2010/11/22 13:44:33  efy-asimo
 * Admin skin preferences update
 *
 * Revision 1.21  2010/11/18 13:56:06  efy-asimo
 * admin skin preferences
 *
 * Revision 1.20  2010/11/04 18:29:46  sam2kb
 * View personal blogs in user profile
 *
 * Revision 1.19  2010/11/03 19:44:15  sam2kb
 * Increased modularity - files_Module
 * Todo:
 * - split core functions from _file.funcs.php
 * - check mtimport.ctrl.php and wpimport.ctrl.php
 * - do not create demo Photoblog and posts with images (Blog A)
 *
 * Revision 1.18  2010/10/05 12:53:46  efy-asimo
 * Move twitter_unlink into twitter_plugin
 *
 * Revision 1.17  2010/10/01 13:56:32  efy-asimo
 * twitter plugin save contact and fix
 *
 * Revision 1.16  2010/09/29 13:19:02  efy-asimo
 * Twitter user unlink, and twitter config params move to plugin
 *
 * Revision 1.15  2010/09/16 14:12:24  efy-asimo
 * New avatar upload
 *
 * Revision 1.14  2010/07/02 08:14:19  efy-asimo
 * Messaging redirect modification and "new user get a new blog" fix
 *
 * Revision 1.13  2010/06/24 08:54:06  efy-asimo
 * PHP 4 compatibility
 *
 * Revision 1.12  2010/05/02 00:09:27  blueyed
 * todo: pass locale to urltitle_validate calls
 *
 * Revision 1.11  2010/04/08 10:35:23  efy-asimo
 * Allow users to create a new blog for themselves - task
 *
 * Revision 1.10  2010/01/30 18:55:35  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.9  2010/01/17 16:15:24  sam2kb
 * Localization clean-up
 *
 * Revision 1.8  2010/01/10 23:24:09  fplanque
 * crumbs...
 *
 * Revision 1.7  2010/01/03 17:45:21  fplanque
 * crumbs & stuff
 *
 * Revision 1.6  2009/12/12 19:14:06  fplanque
 * made avatars optional + fixes on img props
 *
 * Revision 1.5  2009/12/06 22:55:18  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.4  2009/12/01 01:52:08  fplanque
 * Fixed issue with Debuglog in case of redirect -- Thanks @blueyed for help.
 *
 * Revision 1.3  2009/11/30 22:42:44  blueyed
 * Fix updating user preferences. This might break something else, please review.
 *
 * Revision 1.2  2009/11/21 13:35:00  efy-maxim
 * log
 *
 */
?>