<?php
/**
 * This file implements the UI controller for settings management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id: users.ctrl.php 6911 2014-06-17 15:35:37Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI_general
 */
global $AdminUI;

param( 'user_ID', 'integer', NULL );	// Note: should NOT be memorized (would kill navigation/sorting) use memorize_param() if needed

param_action( 'list' );

$tab = param( 'tab', 'string', '' );

$AdminUI->set_path( 'users', $tab == 'stats' ? 'stats' : 'users' );

if( !$current_User->check_perm( 'users', 'view' ) )
{ // User has no permissions to view: he can only edit his profile

	if( isset($user_ID) && $user_ID != $current_User->ID )
	{ // User is trying to edit something he should not: add error message (Should be prevented by UI)
		$Messages->add( T_('You have no permission to view other users!'), 'error' );
	}

	// Make sure the user only edits himself:
	$user_ID = $current_User->ID;
	if( !in_array( $action, array( 'update', 'edit', 'default_settings', 'change_admin_skin' ) ) )
	{
		header_redirect( regenerate_url( 'ctrl,action', 'ctrl=user&action=edit&user_ID='.$user_ID, '', '&' ) );
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
		header_redirect( regenerate_url( 'ctrl,action', 'ctrl=user&action='.$action.'&user_ID='.$user_ID, '', '&' ) );
	}

	if( $action != 'list' )
	{ // check edit permissions
		if( ! $current_User->check_perm( 'users', 'edit' )
		    && $edited_User->ID != $current_User->ID )
		{ // user is only allowed to _view_ other user's profiles
			$Messages->add( T_('You have no permission to edit other users!'), 'error' );
			header_redirect( regenerate_url( 'ctrl,action', 'ctrl=user&amp;action=view&amp;user_ID='.$user_ID ) );
		}
		elseif( $demo_mode && $edited_User->ID <= 3 )
		{ // Demo mode restrictions: users created by install process cannot be edited
			$Messages->add( T_('You cannot edit the admin and demo users profile in demo mode!'), 'error' );

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

/*
 * Perform actions, if there were no errors:
 */
if( !$Messages->has_errors() )
{ // no errors
	switch( $action )
	{
		case 'change_admin_skin':
			// Skin switch from menu
			param( 'new_admin_skin', 'string', true );
			param( 'redirect_to', 'url', '' );

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

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

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

			if( param( 'deltype', 'string', '', true ) == 'spammer' )
			{ // If we delete user as spammer we also should remove the comments and the messages
				$edited_User->delete_cascades = array_merge( $edited_User->delete_cascades, array(
						array( 'table'=>'T_comments', 'fk'=>'comment_author_user_ID', 'msg'=>T_('%d comments by this user') ),
						array( 'table'=>'T_messaging__message', 'fk'=>'msg_author_user_ID', 'msg'=>T_('%d private messages sent by this user') ),
					) );
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

				$deleted_user_ID = $edited_User->ID;
				$deleted_user_email = $edited_User->get( 'email' );
				$edited_User->dbdelete( $Messages );
				unset($edited_User);
				forget_param('user_ID');
				$Messages->add( $msg, 'success' );

				// Find other users with the same email address
				$message_same_email_users = find_users_with_same_email( $deleted_user_ID, $deleted_user_email, T_('Note: the same email address (%s) is still in use by: %s') );
				if( $message_same_email_users !== false )
				{
					$Messages->add( $message_same_email_users, 'note' );
				}

				$action = 'list';
				// Redirect so that a reload doesn't write to the DB twice:
				header_redirect( '?ctrl=users', 303 ); // Will EXIT
				// We have EXITed already at this point!!
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
				{ // There are restrictions:
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

		case 'search':
			// Quick search

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			param( 'user_search', 'string', '' );
			set_param( 'keywords', $user_search );
			set_param( 'filter', 'new' );

			load_class( 'users/model/_userlist.class.php', 'UserList' );
			$UserList = new UserList( 'admin', $UserSettings->get('results_per_page'), 'users_', array( 'join_city' => false ) );
			$UserList->load_from_Request();
			// Make query to get a count of users
			$UserList->query();

			if( $UserList->get_total_rows() == 1 )
			{	// If we find only one user by quick search we do a redirect to user's edit page
				$User = $UserList->rows[0];
				if( !empty( $User ) )
				{
					header_redirect( '?ctrl=user&user_tab=profile&user_ID='.$User->user_ID );
				}
			}

			// Unset the filter to avoid the step 1 in the function $UserList->query() on the users list
			set_param( 'filter', '' );

			break;

		case 'remove_sender_customization':
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'users' );

			// Check required permission
			$current_User->check_perm( 'users', 'edit', true );

			// get the type of the removable sender customization
			$type = param( 'type', 'string', true );

			// Set remove custom settings query
			$remove_query = 'DELETE FROM T_users__usersettings WHERE uset_name = "%s" AND uset_value != %s';
			if( $type == 'sender_email' )
			{ // Remove custom sender emails
				$DB->query( sprintf( $remove_query, 'notification_sender_email', $DB->quote( $Settings->get( 'notification_sender_email' ) ) ) );
			}
			elseif( $type == 'sender_name' )
			{ // Remove custom sender names
				$DB->query( sprintf( $remove_query, 'notification_sender_name', $DB->quote( $Settings->get( 'notification_sender_name' ) ) ) );
			}
			else
			{ // The customization param is not valid
				debug_die('Invalid remove sender customization action!');
			}

			$Messages->add( T_('Customizations have been removed!' ), 'success' );
			$redirect_to = param( 'redirect_to', 'url', regenerate_url( 'action' ) );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $redirect_to );
			/* EXITED */
			break;
	}
}

// Used for autocomplete user fields in filter "Specific criteria" or to highlight user level cell on change
require_js( '#jqueryUI#' );
require_css( '#jqueryUI_css#' );

// We might delegate to this action from above:
/*if( $action == 'edit' )
{
	$Plugins->trigger_event( 'PluginUserSettingsEditAction', $tmp_params = array( 'User' => & $edited_User ) );
	$Session->delete( 'core.changepwd.request_id' ); // delete the request_id for password change request (from /htsrv/login.php)
}*/


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Users'), '?ctrl=users' );
if( $tab == 'stats' )
{	// Users stats
	$AdminUI->breadcrumbpath_add( T_('Stats'), '?ctrl=users&amp;tab=stats' );
}
else
{	// Users list
	$AdminUI->breadcrumbpath_add( T_('List'), '?ctrl=users' );
	$AdminUI->top_block = get_user_quick_search_form();
	if( $current_User->check_perm( 'users', 'edit', false ) )
	{	// Include to edit user level
		require_js( 'jquery/jquery.jeditable.js', 'rsc_url' );
	}
	load_funcs( 'regional/model/_regional.funcs.php' );
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

	case 'delete':
		$deltype = param( 'deltype', 'string', '' ); // spammer

		$AdminUI->disp_payload_begin();

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

		$confirm_messages = array();
		if( $deltype != 'spammer' )
		{ // Display this note for standard deleting
			$confirm_messages[] = array( T_('Note: this will not automatically delete private messages sent/received by this user. However, this will delete any new orphan private messages (which no longer have any existing sender or recipient).'), 'note' );
			$confirm_messages[] = array( T_('Note: this will not delete comments made by this user. Instead it will transform them from member to visitor comments.'), 'note' );
		}

		// Find other users with the same email address
		$message_same_email_users = find_users_with_same_email( $edited_User->ID, $edited_User->get( 'email' ), T_('Note: this user has the same email address (%s) as: %s') );
		if( $message_same_email_users !== false )
		{
			$confirm_messages[] = array( $message_same_email_users, 'note' );
		}

		$edited_User->confirm_delete( $msg, 'user', $action, get_memorized( 'action' ), $confirm_messages );

		// Display user identity form:
		$AdminUI->disp_view( 'users/views/_user_identity.form.php' );
		$AdminUI->disp_payload_end();
		break;

	case 'promote':
	default:
		// Display user list:
		// NOTE: we don't want this (potentially very long) list to be displayed again and again)
		$AdminUI->disp_payload_begin();
		if( $tab == 'stats' )
		{
			$AdminUI->disp_view( 'users/views/_user_stats.view.php' );
		}
		else
		{
			$AdminUI->disp_view( 'users/views/_user_list.view.php' );
		}
		$AdminUI->disp_payload_end();
}


// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>