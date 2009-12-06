<?php
/**
 * This file implements the UI controller for settings management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
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

param( 'user_ID', 'integer', NULL );	// Note: should NOT be memorized (would kill navigation/sorting) use memorize_param() if needed

param_action( 'list' );

$AdminUI->set_path( 'users', 'users' );

if( !$current_User->check_perm( 'users', 'view' ) )
{ // User has no permissions to view: he can only edit his profile

	if( isset($user_ID) && $user_ID != $current_User->ID )
	{ // User is trying to edit something he should not: add error message (Should be prevented by UI)
		$Messages->add( T_('You have no permission to view other users!'), 'error' );
	}

	// Make sure the user only edits himself:
	$user_ID = $current_User->ID;
	if( !in_array( $action, array( 'update', 'edit', 'default_settings' ) ) )
	{
		header_redirect( regenerate_url( 'ctrl,action', 'ctrl=user&amp;action=edit&amp;user_ID='.$user_ID ) );
	}
}

/*
 * Load editable objects and set $action (while checking permissions)
 */

$UserCache = & get_UserCache();

if( ! is_null($user_ID) )
{   // User selected
	if( ($edited_User = & $UserCache->get_by_ID( $user_ID, false )) === false )
	{	// We could not find the User to edit:
		unset( $edited_User );
		forget_param( 'user_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('User') ), 'error' );
		$action = 'list';
	}
	elseif( $action == 'list' )
	{ // 'list' is default, $user_ID given
		if( $user_ID == $current_User->ID || $current_User->check_perm( 'users', 'edit' ) )
		{
			$action = 'edit';
		}
		else
		{
			$action = 'view';
		}
		header_redirect( regenerate_url( 'ctrl,action', 'ctrl=user&amp;action='.$action.'&amp;user_ID='.$user_ID ) );
	}

	if( $action != 'list' )
	{ // check edit permissions
		if( ! $current_User->check_perm( 'users', 'edit' )
		    && $edited_User->ID != $current_User->ID )
		{ // user is only allowed to _view_ other user's profiles
			$Messages->add( T_('You have no permission to edit other users!'), 'error' );
			header_redirect( regenerate_url( 'ctrl,action', 'ctrl=user&amp;action=view&amp;user_ID='.$user_ID ) );
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
					header_redirect( regenerate_url( 'ctrl,action', 'ctrl=user&amp;action=view&amp;user_ID='.$user_ID ) );
				}
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
		case 'change_admin_skin':
			// Skin switch from menu
			param( 'new_admin_skin', 'string', true );
			param( 'redirect_to', 'string', '' );

			$UserSettings->set( 'admin_skin', $new_admin_skin );
			$UserSettings->dbupdate();
			$Messages->add( sprintf( T_('Admin skin changed to &laquo;%s&raquo;'), $new_admin_skin ), 'success' );

			header_redirect();
			/* EXITED */
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


		case 'delete':
			/*
			 * Delete user
			 */
			if( !isset($edited_User) )
				debug_die( 'no User set' );

			if( $edited_User->ID == $current_User->ID )
			{
				$Messages->add( T_('You can\'t delete yourself!'), 'error' );
				$action = 'view';
				break;
			}
			if( $edited_User->ID == 1 )
			{
				$Messages->add( T_('You can\'t delete User #1!'), 'error' );
				$action = 'view';
				break;
			}

			$fullname = $edited_User->dget( 'fullname' );
			if( param( 'confirm', 'integer', 0 ) )
			{ // confirmed, Delete from DB:
				if ( ! empty( $fullname ) )
				{
					$msg = sprintf( T_('User &laquo;%s&raquo; [%s] deleted.'), $fullname, $edited_User->dget( 'login' ) );
				}
				else
				{
					$msg = sprintf( T_('User &laquo;%s&raquo; deleted.'), $edited_User->dget( 'login' ) );
				}

				$edited_User->dbdelete( $Messages );
				unset($edited_User);
				forget_param('user_ID');
				$Messages->add( $msg, 'success' );
				$action = 'list';
			}
			else
			{	// not confirmed, Check for restrictions:
				memorize_param( 'user_ID', 'integer', true );
				if ( ! empty( $fullname ) )
				{
					$msg = sprintf( T_('Cannot delete User &laquo;%s&raquo; [%s]'), $fullname, $edited_User->dget( 'login' ) );
				}
				else
				{
					$msg = sprintf( T_('Cannot delete User &laquo;%s&raquo;'), $edited_User->dget( 'login' ) );
				}

				if( ! $edited_User->check_delete( $msg ) )
				{	// There are restrictions:
					$action = 'view';
				}
			}
			break;


		case 'del_settings_set':
			// Delete a set of an array type setting:
			param( 'plugin_ID', 'integer', true );
			param( 'set_path' );

			$admin_Plugins = & get_Plugins_admin();
			$admin_Plugins->restart();
			$edit_Plugin = & $admin_Plugins->get_by_ID($plugin_ID);

			load_funcs('plugins/_plugin.funcs.php');
			_set_setting_by_path( $edit_Plugin, 'UserSettings', $set_path, NULL );

			$edit_Plugin->Settings->dbupdate();

			$action = 'edit';

			break;


		case 'add_settings_set': // delegates to edit_settings
			// Add a new set to an array type setting:
			param( 'plugin_ID', 'integer', true );
			param( 'set_path', 'string', '' );

			$admin_Plugins = & get_Plugins_admin();
			$admin_Plugins->restart();
			$edit_Plugin = & $admin_Plugins->get_by_ID($plugin_ID);

			load_funcs('plugins/_plugin.funcs.php');
			_set_setting_by_path( $edit_Plugin, 'UserSettings', $set_path, array() );

			$edit_Plugin->Settings->dbupdate();

			$action = 'edit';

			break;
	}
}


// We might delegate to this action from above:
/*if( $action == 'edit' )
{
	$Plugins->trigger_event( 'PluginUserSettingsEditAction', $tmp_params = array( 'User' => & $edited_User ) );
	$Session->delete( 'core.changepwd.request_id' ); // delete the request_id for password change request (from /htsrv/login.php)
}*/


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Users'), '?ctrl=users' );


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

	case 'delete':
			// We need to ask for confirmation:
			$fullname = $edited_User->dget( 'fullname' );
			if ( ! empty( $fullname ) )
			{
				$msg = sprintf( T_('Delete user &laquo;%s&raquo; [%s]?'), $fullname, $edited_User->dget( 'login' ) );
			}
			else
			{
				$msg = sprintf( T_('Delete user &laquo;%s&raquo;?'), $edited_User->dget( 'login' ) );
			}

			$edited_User->confirm_delete( $msg, $action, get_memorized( 'action' ) );

			// Display user identity form:
			$AdminUI->disp_view( 'users/views/_user_identity.form.php' );
			break;

	case 'promote':
	default:
		// Display user list:
		// NOTE: we don't want this (potentially very long) list to be displayed again and again)
		$AdminUI->disp_payload_begin();
		$AdminUI->disp_view( 'users/views/_user_list.view.php' );
		$AdminUI->disp_payload_end();
}


// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.46  2009/12/06 22:55:19  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.45  2009/12/04 23:27:49  fplanque
 * cleanup Expires: header handling
 *
 * Revision 1.44  2009/11/21 13:31:59  efy-maxim
 * 1. users controller has been refactored to users and user controllers
 * 2. avatar tab
 * 3. jQuery to show/hide custom duration
 *
 * Revision 1.43  2009/11/12 00:46:33  fplanque
 * doc/minor/handle demo mode
 *
 * Revision 1.42  2009/10/28 13:41:57  efy-maxim
 * default multiple sessions settings
 *
 * Revision 1.41  2009/10/28 10:56:34  efy-maxim
 * param_duration
 *
 * Revision 1.40  2009/10/28 10:02:42  efy-maxim
 * rename some php files
 *
 * Revision 1.39  2009/10/27 16:43:34  efy-maxim
 * custom session timeout
 *
 * Revision 1.38  2009/10/26 12:59:36  efy-maxim
 * users management
 *
 * Revision 1.37  2009/10/25 21:33:06  efy-maxim
 * nickname setting
 *
 * Revision 1.36  2009/10/25 20:39:09  efy-maxim
 * multiple sessions
 *
 * Revision 1.35  2009/10/25 15:22:46  efy-maxim
 * user - identity, password, preferences tabs
 *
 * Revision 1.34  2009/09/26 12:00:43  tblue246
 * Minor/coding style
 *
 * Revision 1.33  2009/09/25 07:33:14  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.32  2009/09/24 20:45:47  fplanque
 * no message
 *
 * Revision 1.31  2009/09/23 19:23:02  efy-bogdan
 * Cleanup users.ctrl.php
 *
 * Revision 1.30  2009/09/23 13:32:20  efy-bogdan
 * Separate controller added for groups
 *
 * Revision 1.29  2009/09/23 07:17:14  efy-bogdan
 *  load_from_Request added to Group class
 *
 * Revision 1.28  2009/09/22 07:07:24  efy-bogdan
 * user.ctrl.php cleanup
 *
 * Revision 1.27  2009/09/19 01:04:06  fplanque
 * button to remove an avatar from an user profile
 *
 * Revision 1.26  2009/09/13 12:25:34  efy-maxim
 * Messaging permissions have been added to:
 * 1. Upgrader
 * 2. Group class
 * 3. Edit Group form
 *
 * Revision 1.25  2009/09/11 18:34:06  fplanque
 * userfields editing module.
 * needs further cleanup but I think it works.
 *
 * Revision 1.24  2009/09/07 23:35:50  fplanque
 * cleanup
 *
 * Revision 1.23  2009/09/07 14:26:49  efy-maxim
 * Country field has been added to User form (but without updater)
 *
 * Revision 1.22  2009/08/30 19:54:22  fplanque
 * less translation messgaes for infrequent errors
 *
 * Revision 1.21  2009/05/31 12:36:04  tblue246
 * minor
 *
 * Revision 1.20  2009/05/31 11:37:34  tblue246
 * doc
 *
 * Revision 1.19  2009/05/31 05:16:44  sam2kb
 * This was probably the oldest bug ;)
 *
 * Revision 1.18  2009/05/26 19:31:59  fplanque
 * Plugins can now have Settings that are specific to each blog.
 *
 * Revision 1.17  2009/03/22 17:20:32  fplanque
 * I think I forgot that earlier
 *
 * Revision 1.15  2009/03/20 03:38:04  fplanque
 * rollback -- http://forums.b2evolution.net/viewtopic.php?t=18269
 *
 * Revision 1.12  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.11  2009/02/01 16:52:29  tblue246
 * Don't display the user's full name if it isn't set when deleting users. Fixes: http://forums.b2evolution.net/viewtopic.php?t=17822
 *
 * Revision 1.10  2009/01/13 23:45:59  fplanque
 * User fields proof of concept
 *
 * Revision 1.9  2008/04/12 19:56:56  fplanque
 * bugfix
 *
 * Revision 1.8  2008/01/21 09:35:35  fplanque
 * (c) 2008
 *
 * Revision 1.7  2008/01/20 18:20:28  fplanque
 * Antispam per group setting
 *
 * Revision 1.6  2008/01/20 15:31:12  fplanque
 * configurable validation/security rules
 *
 * Revision 1.5  2008/01/19 15:45:29  fplanque
 * refactoring
 *
 * Revision 1.4  2008/01/19 10:57:11  fplanque
 * Splitting XHTML checking by group and interface
 *
 * Revision 1.3  2007/09/03 23:47:37  blueyed
 * Use singleton Plugins_admin
 *
 * Revision 1.2  2007/07/09 20:11:53  fplanque
 * admin skin switcher
 *
 * Revision 1.1  2007/06/25 11:01:44  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.51  2007/06/19 20:41:11  fplanque
 * renamed generic functions to autoform_*
 *
 * Revision 1.50  2007/06/19 18:47:27  fplanque
 * Nuked unnecessary Param (or I'm missing something badly :/)
 *
 * Revision 1.49  2007/05/26 22:21:32  blueyed
 * Made $limit for Results configurable per user
 *
 * Revision 1.48  2007/04/26 00:11:15  fplanque
 * (c) 2007
 *
 * Revision 1.47  2007/03/08 00:46:17  blueyed
 * Fixed check for disallowing demouser-group-changes in demomode
 *
 * Revision 1.46  2007/02/21 22:21:30  blueyed
 * "Multiple sessions" user setting
 *
 * Revision 1.45  2007/02/21 21:17:20  blueyed
 * When resetting values to defaults, just delete all of them (defaults).
 *
 * Revision 1.44  2006/12/06 22:30:07  fplanque
 * Fixed this use case:
 * Users cannot register themselves.
 * Admin creates users that are validated by default. (they don't have to validate)
 * Admin can invalidate a user. (his email, address actually)
 *
 * Revision 1.43  2006/12/05 02:54:37  blueyed
 * Go to user profile after resetting to defaults; fixed handling of action in case of redirecting
 *
 * Revision 1.42  2006/12/03 19:01:57  blueyed
 * doc
 *
 * Revision 1.41  2006/12/03 16:37:14  fplanque
 * doc
 *
 * Revision 1.40  2006/11/24 18:27:23  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.39  2006/11/24 18:06:02  blueyed
 * Handle saving of $Messages centrally in header_redirect()
 *
 * Revision 1.38  2006/11/15 21:14:04  blueyed
 * "Restore defaults" in user profile
 *
 * Revision 1.37  2006/11/13 20:49:52  fplanque
 * doc/cleanup :/
 *
 * Revision 1.36  2006/11/09 23:40:57  blueyed
 * Fixed Plugin UserSettings array type editing; Added jquery and use it for AJAHifying Plugin (User)Settings editing of array types
 *
 * Revision 1.35  2006/10/30 19:00:36  blueyed
 * Lazy-loading of Plugin (User)Settings for PHP5 through overloading
 */
?>
