<?php
/**
 * This file implements the UI controller for settings management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 *
 * @todo separate object inits and permission checks
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI_general
 */
global $AdminUI;

param( 'user_ID', 'integer', NULL );	// Note: should NOT be memorized (would kill navigation/sorting) use memorize_param() if needed

param_action( 'list' );

param( 'display_mode', 'string', 'normal' );

$tab = param( 'tab', 'string', '' );
$tab3 = param( 'tab3', 'string', '', true );

$AdminUI->set_path( 'users', $tab == 'stats' ? 'stats' : 'users', $tab3 == 'duplicates' ? 'duplicates' : 'list' );

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
			header_redirect( regenerate_url( 'ctrl,action', 'ctrl=user&action=view&user_ID='.$user_ID, '', '&' ) );
		}
		elseif( $demo_mode && ( $edited_User->ID <= 7 ) )
		{ // Demo mode restrictions: users created by install process cannot be edited
			$Messages->add( T_('You cannot edit the admin and demo users profile in demo mode!'), 'error' );

			if( strpos( $action, 'delete_' ) === 0 || $action == 'promote' )
			{ // Fallback to list/view action
				$action = 'list';
			}
			else
			{
				header_redirect( regenerate_url( 'ctrl,action', 'ctrl=user&action=view&user_ID='.$user_ID, '', '&' ) );
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

		/*
		 * We currently support only one backoffice skin, so we don't need a system for selecting the backoffice skin.
		case 'change_admin_skin':
			// Skin switch from menu
			param( 'new_admin_skin', 'string', true );
			param( 'redirect_to', 'url', '' );

			$UserSettings->set( 'admin_skin', $new_admin_skin );
			$UserSettings->dbupdate();
			$Messages->add( sprintf( T_('Admin skin changed to &laquo;%s&raquo;'), $new_admin_skin ), 'success' );

			header_redirect();
			// EXITED
			break;
		*/

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

			// Check if the user is deleted as spammer:
			$is_spammer = ( param( 'deltype', 'string', '', true ) == 'spammer' );

			$fullname = $edited_User->dget( 'fullname' );
			if( param( 'confirm', 'integer', 0 ) )
			{ // confirmed, Delete from DB:
				if ( ! empty( $fullname ) )
				{
					$msg_format = $is_spammer ? T_('Spammer &laquo;%s&raquo; [%s] deleted.') : T_('User &laquo;%s&raquo; [%s] deleted.');
					$msg = sprintf( $msg_format, $fullname, $edited_User->dget( 'login' ) );
				}
				else
				{
					$msg_format = $is_spammer ? T_('Spammer &laquo;%s&raquo; deleted.') : T_('User &laquo;%s&raquo; deleted.');
					$msg = sprintf( $msg_format, $edited_User->dget( 'login' ) );
				}

				$send_reportpm = param( 'send_reportpm', 'integer', 0 );
				$increase_spam_score = param( 'increase_spam_score', 'integer', 0 );
				if( $send_reportpm || $increase_spam_score )
				{ // Get all user IDs who reported for the deleted user:
					$report_user_IDs = get_user_reported_user_IDs( $edited_User->ID );
				}

				$deleted_user_ID = $edited_User->ID;
				$deleted_user_email = $edited_User->get( 'email' );
				$deleted_user_login = $edited_User->get( 'login' );
				if( $edited_User->dbdelete( $Messages ) !== false )
				{ // User has been deleted successfully
					unset( $edited_User );
					forget_param( 'user_ID' );
					$Messages->add( $msg, 'success' );
					syslog_insert( sprintf( 'User %s was deleted.', '[['.$deleted_user_login.']]' ), 'info', 'user', $deleted_user_ID );

					// Find other users with the same email address:
					$message_same_email_users = find_users_with_same_email( $deleted_user_ID, $deleted_user_email, T_('Note: the same email address (%s) is still in use by: %s') );
					if( $message_same_email_users !== false )
					{
						$Messages->add( $message_same_email_users, 'note' );
					}

					if( $send_reportpm )
					{ // Send an info message to users who reported this deleted user:
						user_send_report_message( $report_user_IDs, $deleted_user_login );
					}

					if( $increase_spam_score )
					{ // Increase spam fighter score for the users who reported the deleted account:
						user_increase_spam_score( $report_user_IDs );
					}
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

				// Init cascade relations: If we delete user as spammer we also should remove the comments, messages and files:
				$edited_User->init_relations( array(
					'delete_messages' => $is_spammer,
					'delete_comments' => $is_spammer,
					'delete_files'    => $is_spammer,
				) );

				if( ! $edited_User->check_delete( $msg, array(), true ) )
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

		case 'remove_report':
			// Remove one report on user:

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'users' );

			$reporter_ID = param( 'reporter_ID', 'integer', true );

			// Remove the report from DB:
			$DB->query( 'DELETE FROM T_users__reports
					WHERE urep_target_user_ID = '.$DB->quote( $edited_User->ID ).'
					  AND urep_reporter_ID = '.$DB->quote( $reporter_ID ) );

			$Messages->add( T_('The report has been removed!'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=user&user_tab=activity&user_ID='.$edited_User->ID );
			/* EXITED */
			break;

		case 'campaign':
			// Select the recipients for email campaign:

			$current_User->check_perm( 'emails', 'edit', true );

			// Memorize action param to keep newsletter mode on change filters:
			memorize_param( 'action', 'string', true, $action );

			$Messages->add( T_('Please select new recipients for this email campaign.'), 'success' );

			load_funcs( 'email_campaigns/model/_emailcampaign.funcs.php' );
			if( ! ( $edited_EmailCampaign = & get_session_EmailCampaign() ) )
			{	// Initialize Email Campaign once and store in Session:

				// ID of Email Campaign is required and should be memorized:
				param( 'ecmp_ID', 'integer', true );

				// Get Email Campaign by ID:
				$EmailCampaignCache = & get_EmailCampaignCache();
				$edited_EmailCampaign = & $EmailCampaignCache->get_by_ID( $ecmp_ID );

				// Save Email Campaign ID in Session:
				$Session->set( 'edited_campaign_ID', $edited_EmailCampaign->ID );
			}

			// Set users filter "Subscribed to":
			set_param( 'newsletter', $edited_EmailCampaign->get( 'enlt_ID' ) );
			set_param( 'filter', 'new' );
			break;

		case 'add_automation':
			// Add selected users to automation:

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'users' );

			// Check permission:
			$current_User->check_perm( 'options', 'view', true );

			param( 'autm_ID', 'integer', true );
			param( 'enlt_ID', 'integer', true );

			$AutomationCache = & get_AutomationCache();
			$Automation = & $AutomationCache->get_by_ID( $autm_ID );

			load_funcs( 'email_campaigns/model/_emailcampaign.funcs.php' );

			$added_users_num = $Automation->add_users( get_filterset_user_IDs(), array(
					'users_no_subs'   => param( 'users_no_subs', 'string', 'ignore' ),
					'users_automated' => param( 'users_automated', 'string', 'ignore' ),
					'users_new'       => param( 'users_new', 'string', 'ignore' ),
					'newsletter_IDs'  => $enlt_ID,
				) );

			$Messages->add( sprintf( T_('%d users have been added or requeued for automation "%s"'), $added_users_num, $Automation->get( 'name' ) ), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=automations&action=edit&tab=users&autm_ID='.$Automation->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
			break;

		case 'update_tags':
			// Add/Remove tag to /from selected users:

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'users' );

			// Check permission:
			$current_User->check_perm( 'users', 'edit', true );

			param( 'add_user_tags', 'string', '' );
			param( 'remove_user_tags', 'string', '' );

			load_funcs( 'email_campaigns/model/_emailcampaign.funcs.php' );

			$UserCache = & get_UserCache();
			$UserCache->clear();
			$UserCache->load_list( get_filterset_user_IDs() );

			$updated_users_num = 0;
			foreach( $UserCache->cache as $filtered_User )
			{	// Update tags of each filtered User:
				$filtered_User->add_usertags( $add_user_tags );
				$filtered_User->remove_usertags( $remove_user_tags );
				if( $filtered_User->dbupdate() )
				{
					$updated_users_num++;
				}
			}

			$Messages->add( sprintf( T_('Tags of %d users have been updated'), $updated_users_num ), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=users', 303 ); // Will EXIT
			// We have EXITed already at this point!!
			break;

		case 'merge':
			// Select user for merging and Merge:
			$merging_user_ID = param( 'merging_user_ID', 'integer', true, true );
			$selected_user_ID = param( 'selected_user_ID', 'integer' );

			if( $merging_user_ID == 1 )
			{	// User #1 cannot be deleted so we should not allow to merge it as well:
				// Don't translate because this must not occurs:
				debug_die( 'You can\'t merge User #1!' );
			}

			// Check edit permissions:
			$current_User->can_moderate_user( $merging_user_ID, true );

			if( empty( $selected_user_ID ) )
			{	// Inform to select a remaining account if it is not selected yet:
				$Messages->add( sprintf( T_('User data from account %s will be merged to the account you select below. Check a radio button and click the orange button at the bottom.'), get_user_identity_link( '', $merging_user_ID ) ), 'warning' );
			}
			else
			{	// Check edit permissions for remaining user as well:
				$current_User->can_moderate_user( $selected_user_ID, true );
			}

			// The merging process is executed in the template below by function display_users_merging_process().
			break;

		case 'delete_spammers':
			// Delete selected users as spammers:

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'users' );

			// Check permission:
			$current_User->check_perm( 'users', 'edit', true );

			$users = explode( ',', param( 'users', 'string' ) );

			// Set this param in order to delete the users as spammer:
			set_param( 'deltype', 'spammer' );

			$deleted_spam_logins = array();
			$not_deleted_spam_logins = array();
			$UserCache = & get_UserCache();
			$delspam_Messages = new Messages();
			foreach( $users as $u => $user_ID )
			{
				if( ! ( $deleted_spam_User = & $UserCache->get_by_ID( $user_ID, false, false ) ) )
				{	// Skip if user is not found in DB by requested ID:
					continue;
				}
				$deleted_spam_login = $deleted_spam_User->get( 'login' );
				// Delete user as spammer:
				if( $deleted_spam_User->dbdelete( $delspam_Messages ) )
				{	// If user has been deleted:
					$deleted_spam_logins[] = $deleted_spam_login;
				}
				else
				{	// If user cannot be deleted by some reason:
					$not_deleted_spam_logins[] = $deleted_spam_User->get_identity_link();
				}
			}

			if( count( $deleted_spam_logins ) > 0 )
			{	// Display a message if at least one spammer have been deleted:
				$Messages->add( sprintf( T_('Spammers %s have been deleted.'), implode( ', ', $deleted_spam_logins ) ), 'success' );
			}
			if( count( $not_deleted_spam_logins ) > 0 )
			{	// Display a message if at least one spammer have NOT been deleted:
				$Messages->add( sprintf( T_('Spammers %s could not been deleted.'), implode( ', ', $not_deleted_spam_logins ) ), 'error' );
			}

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=users'.( count( $not_deleted_spam_logins ) > 0 ? '&action=spammers' : '' ), 303 ); // Will EXIT
			// We have EXITed already at this point!!
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
	// Init jqPlot charts
	init_jqplot_js();

	// Set an url for manual page:
	$AdminUI->set_page_manual_link( 'user-stats' );
}
else
{	// Users list
	init_tokeninput_js();
	// Load jQuery QueryBuilder plugin files for user list filters:
	init_querybuilder_js( 'rsc_url' );

	$entries = array(
		'list' => array(
			'text' => T_('List'),
			'href' => '?ctrl=users' ),
		'duplicates' => array(
			'text' => T_('Find duplicates'),
			'href' => '?ctrl=users&amp;tab3=duplicates' ) );
	$AdminUI->add_menu_entries( array( 'users', 'users' ), $entries );

	switch( $tab3 )
	{
		case 'duplicates':
			$AdminUI->breadcrumbpath_add( T_('List'), '?ctrl=users&amp;tab3='.$tab3 );
			break;

		default:
			// Initialize user tag input

			$AdminUI->breadcrumbpath_add( T_('List'), '?ctrl=users' );
			$AdminUI->top_block = get_user_quick_search_form();
			if( $current_User->check_perm( 'users', 'moderate' ) )
			{	// Include to edit user level
				require_js( 'jquery/jquery.jeditable.js', 'rsc_url' );
			}
			load_funcs( 'regional/model/_regional.funcs.php' );

			// Set an url for manual page:
				$AdminUI->set_page_manual_link( 'users-users' );
	}

}

// Initialize date picker
init_datepicker_js();

if( $display_mode != 'js')
{
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

	case 'delete':
		$deltype = param( 'deltype', 'string', '' ); // spammer

		$AdminUI->disp_payload_begin();

		// We need to ask for confirmation:
		$fullname = $edited_User->dget( 'fullname' );
		$del_user_name = empty( $fullname ) ? $edited_User->dget( 'login' ) : '"'.$fullname.'" ['.$edited_User->dget( 'login' ).']';
		$msg = ( $deltype == 'spammer' ) ? T_('Delete SPAMMER %s?') : T_('Delete user %s?');
		$msg = sprintf( $msg, $del_user_name );

		$confirm_messages = array();
		if( $deltype == 'spammer' )
		{	// Display the notes for spammer deleting:
			$confirm_messages[] = array( T_('Note: this will also delete private messages sent/received by this user.'), 'note' );
			$confirm_messages[] = array( T_('Note: this will also delete comments made by this user.'), 'note' );
			$confirm_messages[] = array( T_('Note: this will also delete files uploaded by this user.'), 'note' );
			$confirm_messages[] = array( '<strong>'.sprintf( T_('Note: the email address %s will be banned from registering again.'), '<code>'.$edited_User->get( 'email' ).'</code>' ).'</strong>', 'note' );
		}
		else
		{	// Display the notes for standard deleting:
			$confirm_messages[] = array( T_('Note: this will <b>not</b> automatically delete private messages sent/received by this user. However, this will delete any new orphan private messages (which no longer have any existing sender or recipient).')
				.'<br /><label><input type="checkbox" name="force_delete_messages" value="1" /> '.T_('Force deleting all private messages sent/received by this user.').'</label>', 'note' );
			$confirm_messages[] = array( T_('Note: this will <b>not</b> delete comments made by this user. Instead it will transform them from member to visitor comments.')
				.'<br /><label><input type="checkbox" name="force_delete_comments" value="1" /> '.T_('Force deleting all comments made by this user.').'</label>', 'note' );
			$confirm_messages[] = array( T_('Note: this will <b>not</b> delete files uploaded by this user outside of the user root. Instead the creator ID of these files will be set to NULL.')
				.'<br /><label><input type="checkbox" name="force_delete_files" value="1" /> '.T_('Force deleting all files uploaded by this user.').'</label>', 'note' );
		}

		// Find other users with the same email address
		$message_same_email_users = find_users_with_same_email( $edited_User->ID, $edited_User->get( 'email' ), T_('Note: this user has the same email address (%s) as: %s') );
		if( $message_same_email_users !== false )
		{
			$confirm_messages[] = array( $message_same_email_users, 'note' );
		}

		// Add a checkbox on deletion form
		$delete_form_params = array();
		if( $Settings->get( 'reportpm_enabled' ) )
		{ // If this feature is enabled
			$user_count_reports = count( get_user_reported_user_IDs( $edited_User->ID ) );
			if( $user_count_reports > 0 )
			{ // If the user has been reported at least one time
				$delete_form_params['before_submit_button'] = '<p><label>'
						.'<input type="checkbox" id="send_reportpm" name="send_reportpm" value="1"'.( $deltype == 'spammer' ? ' checked="checked"' : '' ).' /> '
						.sprintf( T_('Send an info message to %s users who reported this account.'), $user_count_reports )
					.'</label></p>'
					.'<p><label>'
						.'<input type="checkbox" id="increase_spam_score" name="increase_spam_score" value="1"'.( $deltype == 'spammer' ? ' checked="checked"' : '' ).' /> '
						.sprintf( T_('Increase spam fighter score for the %s users who reported this account.'), $user_count_reports )
					.'</label></p>';
			}
		}

		$edited_User->confirm_delete( $msg, 'user', $action, get_memorized( 'action' ), $confirm_messages, $delete_form_params );

		if( $deltype == 'spammer' )
		{	// Display user activity lists:
			$user_tab = 'activity';
			$AdminUI->disp_view( 'users/views/_user_activity.view.php' );
		}
		else
		{	// Display user identity form:
			$AdminUI->disp_view( 'users/views/_user_identity.form.php' );
		}
		$AdminUI->disp_payload_end();

		// Init JS for user reporting
		echo_user_report_window();
		break;

	case 'automation':
		// Display a form to add users selection to automation:

		// Do not append Debuglog & Debug JSlog to response!
		$debug = false;
		$debug_jslog = false;

		$AdminUI->disp_payload_begin();

		load_funcs( 'email_campaigns/model/_emailcampaign.funcs.php' );
		$AdminUI->disp_view( 'users/views/_user_list_automation.form.php' );

		$AdminUI->disp_payload_end();
		break;

	case 'edit_tags':
		// Display a form to add/remove tags to/from users selection:

		// Do not append Debuglog & Debug JSlog to response!
		$debug = false;
		$debug_jslog = false;

		$AdminUI->disp_payload_begin();

		$AdminUI->disp_view( 'users/views/_user_list_tags.form.php' );

		$AdminUI->disp_payload_end();
		break;

	case 'spammers':
		memorize_param( 'action', 'string', '', $action );
		$AdminUI->disp_payload_begin();
		$AdminUI->disp_view( 'users/views/_user_list_spammers.view.php' );
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
			switch( $tab3 )
			{
				case 'duplicates':
					$AdminUI->disp_view( 'users/views/_duplicate_email_user_list.view.php' );
					break;

				default:
					$AdminUI->disp_view( 'users/views/_user_list.view.php' );
			}

		}
		$AdminUI->disp_payload_end();
}

if( $display_mode != 'js')
{
	// Display body bottom, debug info and close </html>:
	$AdminUI->disp_global_footer();
}
?>