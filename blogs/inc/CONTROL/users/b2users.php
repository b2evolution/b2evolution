<?php
/**
 * This file implements the UI controller for settings management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @todo separate object inits and permission checks
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI_general
 */
global $AdminUI;
/**
 * @var Request
 */
global $Request;

$AdminUI->set_path( 'users' );

$Request->param_action( 'list' );

param( 'user_ID', 'integer', NULL );	// Note: should NOT be memorized (would kill navigation/sorting) use memorize_param() if needed
param( 'grp_ID', 'integer', NULL );		// Note: should NOT be memorized:    -- " --

/**
 * @global boolean true, if user is only allowed to edit his profile
 */
$user_profile_only = ! $current_User->check_perm( 'users', 'view' );

if( $user_profile_only )
{ // User has no permissions to view: he can only edit his profile

	if( (isset($user_ID) && $user_ID != $current_User->ID)
	 || isset($grp_ID) )
	{ // User is trying to edit something he should not: add error message (Should be prevented by UI)
		$Messages->add( T_('You have no permission to view other users or groups!'), 'error' );
	}

	// Make sure the user only edits himself:
	$user_ID = $current_User->ID;
	$grp_ID = NULL;
	if( ! in_array( $action, array( 'userupdate', 'edit_user' ) ) )
	{
		$action = 'edit_user';
	}
}

/*
 * Load editable objects and set $action (while checking permissions)
 */
if( ! is_null($user_ID) )
{ // User selected
	if( $action == 'userupdate' && $user_ID == 0 )
	{ // we create a new user
		$edited_User = new User();
		$edited_User->set_datecreated( $localtimenow );
	}
	elseif( ($edited_User = & $UserCache->get_by_ID( $user_ID, false )) === false )
	{	// We could not find the User to edit:
		unset( $edited_User );
		forget_param( 'user_ID' );
		$Messages->head = T_('Cannot edit user!');
		$Messages->add( T_('Requested user does not exist any longer.'), 'error' );
		$action = 'list';
	}
	elseif( $action == 'list' )
	{ // 'list' is default, $user_ID given
		if( $user_ID == $current_User->ID || $current_User->check_perm( 'users', 'edit' ) )
		{
			$action = 'edit_user';
		}
		else
		{
			$action = 'view_user';
		}
	}

	if( $action != 'view_user' && $action != 'list' )
	{ // check edit permissions
		if( ! $current_User->check_perm( 'users', 'edit' )
		    && $edited_User->ID != $current_User->ID )
		{ // user is only allowed to _view_ other user's profiles
			$Messages->add( T_('You have no permission to edit other users!'), 'error' );
			$action = 'view_user';
		}
		elseif( $demo_mode )
		{ // Demo mode restrictions: admin/demouser cannot be edited
			if( $edited_User->ID == 1 || $edited_User->login == 'demouser' )
			{
				$Messages->add( T_('You cannot edit the admin and demouser profile in demo mode!'), 'error' );

				if( strpos( $action, 'delete_' ) === 0 || $action == 'promote' )
				{ // Fallback to list/view action
					$action = 'list';
				}
				else
				{
					$action = 'view_user';
				}
			}
		}
	}
}
elseif( $grp_ID !== NULL )
{ // Group selected
	if( $action == 'groupupdate' && $grp_ID == 0 )
	{ // New Group:
		$edited_Group = new Group();
	}
	elseif( ($edited_Group = & $GroupCache->get_by_ID( $grp_ID, false )) === false )
	{ // We could not find the Group to edit:
		unset( $edited_Group );
		forget_param( 'grp_ID' );
		$Messages->head = T_('Cannot edit group!');
		$Messages->add( T_('Requested group does not exist any longer.'), 'error' );
		$action = 'list';
	}
	elseif( $action == 'list' )
	{ // 'list' is default, $grp_ID given
		if( $current_User->check_perm( 'users', 'edit' ) )
		{
			$action = 'edit_group';
		}
		else
		{
			$action = 'view_group';
		}
	}

	if( $action != 'view_group' && $action != 'list' )
	{ // check edit permissions
		if( !$current_User->check_perm( 'users', 'edit' ) )
		{
			$Messages->add( T_('You have no permission to edit groups!'), 'error' );
			$action = 'view_group';
		}
		elseif( $demo_mode  )
		{ // Additional checks for demo mode: no changes to admin's and demouser's group allowed
			$admin_User = & $UserCache->get_by_ID(1);
			$demo_User = & $UserCache->get_by_login('demouser');
			if( $edited_Group->ID == $admin_User->Group->ID
					|| $edited_Group->ID == $demo_User->Group->ID )
			{
				$Messages->add( T_('You cannot edit the groups of user &laquo;admin&raquo; or &laquo;demouser&raquo; in demo mode!'), 'error' );
				$action = 'view_group';
			}
		}
	}
}


/*
 * Perform actions, if there were no errors:
 */
if( !$Messages->count('error') )
{ // no errors
	switch( $action )
	{
		case 'new_user':
			// We want to create a new user:
			if( isset( $edited_User ) )
			{ // We want to use a template
				$new_User = $edited_User; // Copy !
				$new_User->set( 'ID', 0 );
				$edited_User = & $new_User;
			}
			else
			{ // We use an empty user:
				$edited_User = & new User();
			}
			break;


		case 'userupdate':
			// Update existing user OR create new user:
			if( empty($edited_User) || !is_object($edited_User) )
			{
				$Messages->add( 'No user set!' ); // Needs no translation, should be prevented by UI.
				$action = 'list';
				break;
			}

			$reload_page = false; // We set it to true, if a setting changes that needs a page reload (locale, admin skin, ..)

			if( !$current_User->check_perm( 'users', 'edit' ) && $edited_User->ID != $current_User->ID )
			{ // user is only allowed to update him/herself
				$Messages->add( T_('You are only allowed to update your own profile!'), 'error' );
				$action = 'view_user';
				break;
			}

			$Request->param( 'edited_user_login', 'string' );
			$Request->param_check_not_empty( 'edited_user_login', T_('You must provide a login!') );
			// We want all logins to be lowercase to guarantee uniqueness regardless of the database case handling for UNIQUE indexes:
			$edited_user_login = strtolower( $edited_user_login );

			if( $current_User->check_perm( 'users', 'edit' ) )
			{ // changing level/group is allowed (not in profile mode)
				$Request->param_integer_range( 'edited_user_level', 0, 10, T_('User level must be between %d and %d.') );
				$edited_User->set( 'level', $edited_user_level );

				$Request->param( 'edited_user_validated', 'integer', 0 );
				$edited_User->set( 'validated', $edited_user_validated );

				param( 'edited_user_grp_ID', 'integer', true );
				$edited_user_Group = $GroupCache->get_by_ID( $edited_user_grp_ID );
				$edited_User->set_Group( $edited_user_Group );
				// echo 'new group = ';
				// $edited_User->Group->disp('name');
			}

			// check if new login already exists for another user_ID
			$query = '
				SELECT user_ID
				  FROM T_users
				 WHERE user_login = '.$DB->quote($edited_user_login).'
				   AND user_ID != '.$edited_User->ID;
			if( $q = $DB->get_var( $query ) )
			{
				$Request->param_error( 'edited_user_login',
					sprintf( T_('This login already exists. Do you want to <a %s>edit the existing user</a>?'),
						'href="?ctrl=users&amp;user_ID='.$q.'"' ) );
			}

			$Request->param( 'edited_user_firstname', 'string', true );
			$Request->param( 'edited_user_lastname', 'string', true );

			$Request->param( 'edited_user_nickname', 'string', true );
			$Request->param_check_not_empty( 'edited_user_nickname', T_('Please enter a nickname (can be the same as your login).') );

			$Request->param( 'edited_user_idmode', 'string', true );
			$Request->param( 'edited_user_locale', 'string', true );

			$Request->param( 'edited_user_email', 'string', true );
			$Request->param_check_not_empty( 'edited_user_email', T_('Please enter an e-mail address.') );
			$Request->param_check_email( 'edited_user_email', true );

			$Request->param( 'edited_user_url', 'string', true );
			$Request->param_check_url( 'edited_user_url', $comments_allowed_uri_scheme );

			$Request->param( 'edited_user_icq', 'string', true );
			$Request->param_check_number( 'edited_user_icq', T_('The ICQ UIN can only be a number, no letters allowed.') );

			$Request->param( 'edited_user_aim', 'string', true );

			$Request->param( 'edited_user_msn', 'string', true );
			$Request->param_check_email( 'edited_user_msn', false );

			$Request->param( 'edited_user_yim', 'string', true );
			$Request->param( 'edited_user_allow_msgform', 'integer', 0 );
			$Request->param( 'edited_user_notify', 'integer', 0 );
			$Request->param( 'edited_user_showonline', 'integer', 0 );

			$Request->param( 'edited_user_pass1', 'string', true );
			$Request->param( 'edited_user_pass2', 'string', true );
			if( ! $Request->param_check_passwords( 'edited_user_pass1', 'edited_user_pass2', ($edited_User->ID == 0) ) ) // required for new users
			{ // passwords not the same or empty: empty them for the form
				$edited_user_pass1 = '';
				$edited_user_pass2 = '';
			}

			$edited_User->set( 'login', $edited_user_login );
			$edited_User->set( 'firstname', $edited_user_firstname );
			$edited_User->set( 'lastname', $edited_user_lastname );
			$edited_User->set( 'nickname', $edited_user_nickname );
			$edited_User->set( 'idmode', $edited_user_idmode );
			if( $edited_User->set( 'locale', $edited_user_locale ) && $edited_User->ID == $current_User->ID )
			{ // locale value has changed for the current user
				$reload_page = true;
			}
			$edited_User->set( 'email', $edited_user_email );
			$edited_User->set( 'url', $edited_user_url );
			$edited_User->set( 'icq', $edited_user_icq );
			$edited_User->set( 'aim', $edited_user_aim );
			$edited_User->set( 'msn', $edited_user_msn );
			$edited_User->set( 'yim', $edited_user_yim );
			$edited_User->set( 'allow_msgform', $edited_user_allow_msgform );
			$edited_User->set( 'notify', $edited_user_notify );
			$edited_User->set( 'showonline', $edited_user_showonline );

			// Features
			$Request->param( 'edited_user_admin_skin', 'string', true );
			$Request->param_integer_range( 'edited_user_action_icon_threshold', 1, 5, T_('The threshold must be between 1 and 5.') );
			$Request->param_integer_range( 'edited_user_action_word_threshold', 1, 5, T_('The threshold must be between 1 and 5.') );
			$Request->param( 'edited_user_legend', 'integer', 0 );
			$Request->param( 'edited_user_bozo', 'integer', 0 );
			$Request->param( 'edited_user_focusonfirst', 'integer', 0 );

			if( $Messages->count( 'error' ) )
			{	// We have found validation errors:
				$action = 'edit_user';
				break;
			}

			// OK, no error.
			$new_pass = '';

			if( !empty($edited_user_pass2) )
			{ // Password provided, we must encode it
				$new_pass = md5( $edited_user_pass2 );

				$edited_User->set( 'pass', $new_pass ); // set password
			}

			if( $edited_User->ID != 0 )
			{ // Commit update to the DB:
				$update_r = $edited_User->dbupdate();

				if( $edited_User->ID == $current_User->ID )
				{ // User updates his profile:
					if( $update_r )
					{
						$Messages->add( T_('Your profile has been updated.'), 'success' );
					}
					else
					{
						$Messages->add( T_('Your profile has not been changed.'), 'note' );
					}
				}
				else
				{
					$Messages->add( T_('User updated.'), 'success' );
				}
			}
			else
			{ // Insert user into DB
				$edited_User->dbinsert();
				$Messages->add( T_('New user created.'), 'success' );
			}

			// Now that the User exists in the DB and has an ID, update the settings:

			if( $UserSettings->set( 'admin_skin', $edited_user_admin_skin, $edited_User->ID )
					&& ($edited_User->ID == $current_User->ID) )
			{ // admin_skin has changed or was set the first time for the current user
				$reload_page = true;
			}

			// Action icon params:
			$UserSettings->set( 'action_icon_threshold', $edited_user_action_icon_threshold, $edited_User->ID );
			$UserSettings->set( 'action_word_threshold', $edited_user_action_word_threshold, $edited_User->ID );
			$UserSettings->set( 'display_icon_legend', $edited_user_legend, $edited_User->ID );

			// Set bozo validador activation
			$UserSettings->set( 'control_form_abortions', $edited_user_bozo, $edited_User->ID );

			// Focus on first
			$UserSettings->set( 'focus_on_first_input', $edited_user_focusonfirst, $edited_User->ID );

			// Update user settings:
			$UserSettings->dbupdate();

			// PluginUserSettings
			$any_plugin_settings_updated = false;
			$Plugins->restart();
			while( $loop_Plugin = & $Plugins->get_next() )
			{
				$pluginusersettings = $loop_Plugin->GetDefaultUserSettings();

				if( empty($pluginusersettings) )
				{
					continue;
				}

				global $inc_path;
				require_once $inc_path.'_misc/_plugin.funcs.php';

				set_Settings_for_Plugin_from_Request( $loop_Plugin, $Plugins, 'UserSettings', $edited_User );

				// Let the plugin handle custom fields:
				$ok_to_update = $Plugins->call_method( $loop_Plugin->ID, 'PluginUserSettingsUpdateAction', $tmp_params = array( 'User' => & $edited_User ) );

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

			if( $reload_page )
			{ // save Messages and reload the current page through header redirection
				$Session->set( 'Messages', $Messages );
				header_redirect( regenerate_url( 'action' ) );
			}

			if( $user_profile_only )
			{
				$action = 'edit_user';
			}
			break;


		case 'promote':
			param( 'prom', 'string', true );

			if( !isset($edited_User)
			    || ! in_array( $prom, array('up', 'down') )
			    || ( $prom == 'up' && $edited_User->get('level') > 9 )
			    || ( $prom == 'down' && $edited_User->get('level') < 1 )
			  )
			{
				$Messages->add( T_('Invalid promotion.'), 'error' );
			}
			else
			{
				$sql = '
					UPDATE T_users
					   SET user_level = user_level '.( $prom == 'up' ? '+' : '-' ).' 1
					 WHERE user_ID = '.$edited_User->ID;

				if( $DB->query( $sql ) )
				{
					$Messages->add( T_('User level changed.'), 'success' );
				}
				else
				{
					$Messages->add( sprintf( 'Couldn\'t change %s\'s level.', $edited_User->login ), 'error' );
				}
			}
			break;


		case 'delete_user':
			/*
			 * Delete user
			 */
			if( !isset($edited_User) )
				debug_die( 'no User set' );

			if( $edited_User->ID == $current_User->ID )
			{
				$Messages->add( T_('You can\'t delete yourself!'), 'error' );
				$action = 'view_user';
				break;
			}
			if( $edited_User->ID == 1 )
			{
				$Messages->add( T_('You can\'t delete User #1!'), 'error' );
				$action = 'view_user';
				break;
			}

			if( param( 'confirm', 'integer', 0 ) )
			{ // confirmed, Delete from DB:
				$msg = sprintf( T_('User &laquo;%s&raquo; [%s] deleted.'), $edited_User->dget( 'fullname' ), $edited_User->dget( 'login' ) );
				$edited_User->dbdelete( $Messages );
				unset($edited_User);
				forget_param('user_ID');
				$Messages->add( $msg, 'success' );
				$action = 'list';
			}
			else
			{	// not confirmed, Check for restrictions:
				memorize_param( 'user_ID', 'integer', true );
				if( ! $edited_User->check_delete( sprintf( T_('Cannot delete User &laquo;%s&raquo; [%s]'), $edited_User->dget( 'fullname' ), $edited_User->dget( 'login' ) ) ) )
				{	// There are restrictions:
					$action = 'view_user';
				}
			}
			break;


		// ---- GROUPS --------------------------------------------------------------------------------------

		case 'new_group':
			// We want to create a new group:
			if( isset( $edited_Group ) )
			{ // We want to use a template
				$new_Group = $edited_Group; // Copy !
				$new_Group->set( 'ID', 0 );
				$edited_Group = & $new_Group;
			}
			else
			{ // We use an empty group:
				$edited_Group = & new Group();
			}
			break;


		case 'groupupdate':
			if( empty($edited_Group) || !is_object($edited_Group) )
			{
				$Messages->add( 'No group set!' ); // Needs no translation, should be prevented by UI.
				$action = 'list';
				break;
			}
			$Request->param( 'edited_grp_name', 'string' );

			$Request->param_check_not_empty( 'edited_grp_name', T_('You must provide a group name!') );

			// check if the group name already exists for another group
			$query = 'SELECT grp_ID FROM T_groups
			           WHERE grp_name = '.$DB->quote($edited_grp_name).'
			             AND grp_ID != '.$edited_Group->ID;
			if( $q = $DB->get_var( $query ) )
			{
				$Request->param_error( 'edited_grp_name',
					sprintf( T_('This group name already exists! Do you want to <a %s>edit the existing group</a>?'),
						'href="?ctrl=users&amp;grp_ID='.$q.'"' ) );
			}

			$edited_Group->set( 'name', $edited_grp_name );

			$edited_Group->set( 'perm_blogs', param( 'edited_grp_perm_blogs', 'string', true ) );
			$edited_Group->set( 'perm_stats', param( 'edited_grp_perm_stats', 'string', true ) );
			$edited_Group->set( 'perm_spamblacklist', param( 'edited_grp_perm_spamblacklist', 'string', true ) );
			$edited_Group->set( 'perm_templates', param( 'edited_grp_perm_templates', 'integer', 0 ) );
			$edited_Group->set( 'perm_options', param( 'edited_grp_perm_options', 'string', true ) );
			$edited_Group->set( 'perm_files', param( 'edited_grp_perm_files', 'string', true ) );

			if( $edited_Group->ID != 1 )
			{ // Groups others than #1 can be prevented from logging in or editing users
				$edited_Group->set( 'perm_admin', param( 'edited_grp_perm_admin', 'string', true ) );
				$edited_Group->set( 'perm_users', param( 'edited_grp_perm_users', 'string', true ) );
			}

			if( $Messages->count( 'error' ) )
			{	// We have found validation errors:
				$action = 'edit_group';
				break;
			}

			if( $edited_Group->ID == 0 )
			{ // Insert into the DB:
				$edited_Group->dbinsert();
				$Messages->add( T_('New group created.'), 'success' );
			}
			else
			{ // Commit update to the DB:
				$edited_Group->dbupdate();
				$Messages->add( T_('Group updated.'), 'success' );
			}
			// Commit changes in cache:
			$GroupCache->add( $edited_Group );
			break;


		case 'delete_group':
			/*
			 * Delete group
			 */
			if( !isset($edited_Group) )
				debug_die( 'no Group set' );

			if( $edited_Group->ID == 1 )
			{
				$Messages->add( T_('You can\'t delete Group #1!'), 'error' );
				$action = 'view_group';
				break;
			}
			if( $edited_Group->ID == $Settings->get('newusers_grp_ID' ) )
			{
				$Messages->add( T_('You can\'t delete the default group for new users!'), 'error' );
				$action = 'view_group';
				break;
			}

			if( param( 'confirm', 'integer', 0 ) )
			{ // confirmed, Delete from DB:
				$msg = sprintf( T_('Group &laquo;%s&raquo; deleted.'), $edited_Group->dget( 'name' ) );
				$edited_Group->dbdelete( $Messages );
				unset($edited_Group);
				forget_param('grp_ID');
				$Messages->add( $msg, 'success' );
				$action = 'list';
			}
			else
			{	// not confirmed, Check for restrictions:
				memorize_param( 'grp_ID', 'integer', true );
				if( ! $edited_Group->check_delete( sprintf( T_('Cannot delete Group &laquo;%s&raquo;'), $edited_Group->dget( 'name' ) ) ) )
				{	// There are restrictions:
					$action = 'view_group';
				}
			}
			break;
	}
}


// We might delegate to this action from above:
if( $action == 'edit_user' )
{
	$Plugins->trigger_event( 'PluginUserSettingsEditAction', $tmp_params = array( 'User' => & $edited_User ) );

	$Session->delete( 'core.changepwd.request_id' ); // delete the request_id for password change request (from /htsrv/login.php)
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


		case 'delete_user':
			// We need to ask for confirmation:
			$edited_User->confirm_delete(
					sprintf( T_('Delete user &laquo;%s&raquo; [%s]?'), $edited_User->dget( 'fullname' ), $edited_User->dget( 'login' ) ),
					$action, get_memorized( 'action' ) );
		case 'new_user':
		case 'view_user':
		case 'edit_user':
			// Display user form:
			$AdminUI->disp_view( 'users/_users_form.php' );
			break;


		case 'delete_group':
			// We need to ask for confirmation:
			$edited_Group->confirm_delete(
					sprintf( T_('Delete group &laquo;%s&raquo;?'), $edited_Group->dget( 'name' ) ),
					$action, get_memorized( 'action' ) );
		case 'new_group':
		case 'edit_group':
		case 'view_group':
			// Display group form:
			$AdminUI->disp_view( 'users/_users_groupform.php' );
			break;


	case 'promote':
	default:
		// Display user list:
		// NOTE: we don't want this (potentially very long) list to be displayed again and again)
		$AdminUI->disp_payload_begin();
		$AdminUI->disp_view( 'users/_users_list.php' );
		$AdminUI->disp_payload_end();
}


// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.24  2006/06/25 17:42:46  fplanque
 * better use of Results class (mainly for filtering)
 *
 * Revision 1.23  2006/06/19 20:59:37  fplanque
 * noone should die anonymously...
 *
 * Revision 1.22  2006/06/18 01:14:03  blueyed
 * lazy instantiate user's group; normalisation
 *
 * Revision 1.21  2006/06/13 21:30:00  blueyed
 * MFB; fixed PluginUserSettings for new users
 *
 * Revision 1.19.2.1  2006/06/12 20:00:34  fplanque
 * one too many massive syncs...
 *
 * Revision 1.20  2006/05/30 19:57:03  blueyed
 * Fixed saving of User Settings for new users.
 *
 * Revision 1.19  2006/05/02 05:58:40  blueyed
 * fix
 *
 * Revision 1.18  2006/04/27 21:50:40  blueyed
 * Allow editing/viewing of "validated" property
 *
 * Revision 1.17  2006/04/22 02:36:38  blueyed
 * Validate users on registration through email link (+cleanup around it)
 *
 * Revision 1.16  2006/04/19 20:13:49  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.15  2006/04/14 19:25:32  fplanque
 * evocore merge with work app
 *
 * Revision 1.14  2006/04/11 21:22:25  fplanque
 * partial cleanup
 *
 * Revision 1.13  2006/04/04 22:12:33  blueyed
 * Fixed setting usersettings for other users
 *
 * Revision 1.12  2006/04/04 21:40:21  blueyed
 * Fix profile-editing for users that have no "view-users" permission.
 *
 * Revision 1.11  2006/03/21 16:51:24  blueyed
 * More personal message, when updating own profile.
 *
 * Revision 1.10  2006/03/20 22:36:05  blueyed
 * Fixed deleting of users and groups.
 *
 * Revision 1.9  2006/03/19 17:54:26  blueyed
 * Opt-out for email through message form.
 *
 * Revision 1.8  2006/03/14 22:56:28  blueyed
 * Fix the URL we're redirecting to..
 *
 * Revision 1.7  2006/03/12 23:08:57  fplanque
 * doc cleanup
 *
 * Revision 1.6  2006/03/11 15:49:48  blueyed
 * Allow a plugin to not update his settings at all.
 *
 * Revision 1.5  2006/03/05 23:53:10  blueyed
 * Fixed Password-Change-Request (bound to $Session now)
 *
 * Revision 1.4  2006/03/03 20:10:21  blueyed
 * doc
 *
 * Revision 1.3  2006/03/01 01:07:43  blueyed
 * Plugin(s) polishing
 *
 * Revision 1.2  2006/02/27 16:57:12  blueyed
 * PluginUserSettings - allows a plugin to store user related settings
 *
 * Revision 1.1  2006/02/23 21:11:56  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.129  2006/02/10 22:33:19  fplanque
 * logins should be lowercase
 *
 * Revision 1.128  2006/02/09 00:55:35  blueyed
 * Store user's login MixedCase in DB, the same as with /htsrv/register.php.
 *
 * Revision 1.127  2006/02/03 21:58:04  fplanque
 * Too many merges, too little time. I can hardly keep up. I'll try to check/debug/fine tune next week...
 *
 * Revision 1.126  2006/01/27 17:50:36  blueyed
 * *** empty log message ***
 *
 * Revision 1.125  2006/01/10 21:09:30  fplanque
 * I think the ICQ NULL is better enforced in User::set()
 *
 * Revision 1.124  2006/01/10 19:58:20  blueyed
 * Type-Fix for ICQ param (cannot be updated as '' in SQL strict mode)
 *
 * Revision 1.123  2005/12/23 19:05:39  blueyed
 * minor
 *
 * Revision 1.122  2005/12/21 20:38:18  fplanque
 * Session refactoring/doc
 *
 * Revision 1.121  2005/12/14 19:31:24  fplanque
 * bugfix
 *
 * Revision 1.120  2005/12/13 14:30:09  fplanque
 * no message
 *
 * Revision 1.119  2005/12/12 19:21:20  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.118  2005/12/08 22:26:07  blueyed
 * Fix E_STRICT (assigning "new" by reference is deprecated)
 *
 * Revision 1.117  2005/12/08 22:23:44  blueyed
 * Merged 1-2-3-4 scheme from post-phoenix
 *
 * Revision 1.116  2005/11/24 20:34:56  blueyed
 * doc
 *
 * Revision 1.115  2005/11/16 06:33:48  blueyed
 * Fix SQL injection; fix Request param error
 *
 * Revision 1.114  2005/11/16 04:16:53  blueyed
 * Made action "promote" make use of $edited_User; fixed possible SQL injection
 *
 * Revision 1.113  2005/11/16 01:52:35  blueyed
 * Do not allow level editing for admin/demouser in $demo_mode
 *
 * Revision 1.111.2.1  2005/11/14 15:48:30  blueyed
 * Removed TODO, doc
 *
 * Revision 1.111  2005/11/04 13:52:56  blueyed
 * Reload page for changed locale, so that the new setting applies. Also fixed setting admin_skin for another user.
 *
 * Revision 1.110  2005/11/02 20:11:19  fplanque
 * "containing entropy"
 *
 * Revision 1.109  2005/11/01 23:50:55  blueyed
 * UI to set the admin_skin for a user. If the user changes his own profile, we reload the page and save $Messages before, so he gets his "User updated" note.. :)
 *
 * Revision 1.108  2005/10/31 23:20:45  fplanque
 * keeping things straight...
 *
 * Revision 1.107  2005/10/31 06:13:02  blueyed
 * Finally merged my work on $Session in.
 *
 * Revision 1.106  2005/10/31 00:21:27  blueyed
 * Made links like "?action=" more explicit by refering to the page (.php file) they link to. This fixes a problem reported by a user. I could not reproduce it, but it was browser independent. He used a mobile card with a laptop (t-online, no wlan).
 *
 * Revision 1.105  2005/10/28 20:08:46  blueyed
 * Normalized AdminUI
 *
 * Revision 1.104  2005/10/20 16:35:18  halton
 * added search / filtering to user list
 *
 * Revision 1.103  2005/10/05 11:22:48  yabs
 * minor changes - correcting ID to user_ID
 *
 * Revision 1.102  2005/10/03 17:26:43  fplanque
 * synched upgrade with fresh DB;
 * renamed user_ID field
 */
?>