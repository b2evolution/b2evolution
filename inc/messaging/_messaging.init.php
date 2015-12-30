<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package messaging
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Minimum PHP version required for messaging module to function properly
 */
$required_php_version[ 'messaging' ] = '5.0';

/**
 * Minimum MYSQL version required for messaging module to function properly
 */
$required_mysql_version[ 'messaging' ] = '5.0.3';

/**
 * Aliases for table names:
 *
 * (You should not need to change them.
 *  If you want to have multiple b2evo installations in a single database you should
 *  change {@link $tableprefix} in _basic_config.php)
 */
$db_config['aliases']['T_messaging__thread'] = $tableprefix.'messaging__thread';
$db_config['aliases']['T_messaging__message'] = $tableprefix.'messaging__message';
$db_config['aliases']['T_messaging__prerendering'] = $tableprefix.'messaging__prerendering';
$db_config['aliases']['T_messaging__threadstatus'] = $tableprefix.'messaging__threadstatus';
$db_config['aliases']['T_messaging__contact'] = $tableprefix.'messaging__contact';
$db_config['aliases']['T_messaging__contact_groups'] = $tableprefix.'messaging__contact_groups';
$db_config['aliases']['T_messaging__contact_groupusers'] = $tableprefix.'messaging__contact_groupusers';

/**
 * Controller mappings.
 *
 * For each controller name, we associate a controller file to be found in /inc/ .
 * The advantage of this indirection is that it is easy to reorganize the controllers into
 * subdirectories by modules. It is also easy to deactivate some controllers if you don't
 * want to provide this functionality on a given installation.
 *
 * Note: while the controller mappings might more or less follow the menu structure, we do not merge
 * the two tables since we could, at any time, decide to make a skin with a different menu structure.
 * The controllers however would most likely remain the same.
 *
 * @global array
 */
$ctrl_mappings['messages'] = 'messaging/messages.ctrl.php';
$ctrl_mappings['threads'] = 'messaging/threads.ctrl.php';
$ctrl_mappings['contacts'] = 'messaging/contacts.ctrl.php';
$ctrl_mappings['msgsettings'] = 'messaging/msg_settings.ctrl.php';
$ctrl_mappings['abuse'] = 'messaging/abuse.ctrl.php';



/**
 * Get the MessageCache
 *
 * @return MessageCache
 */
function & get_MessageCache()
{
	global $MessageCache;

	if( ! isset( $MessageCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'messaging/model/_message.class.php', 'Message' );
		$MessageCache = new DataObjectCache( 'Message', false, 'T_messaging__message', 'msg_', 'msg_ID' );
	}

	return $MessageCache;
}


/**
 * Get the MessagePrerenderingCache
 *
 * @return MessagePrerenderingCache
 */
function & get_MessagePrerenderingCache()
{
	global $MessagePrerenderingCache;

	if( ! isset( $MessagePrerenderingCache ) )
	{ // Cache doesn't exist yet:
		$MessagePrerenderingCache = array();
	}

	return $MessagePrerenderingCache;
}


/**
 * Get the ThreadCache
 *
 * @return ThreadCache
 */
function & get_ThreadCache()
{
	global $ThreadCache;

	if( ! isset( $ThreadCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'messaging/model/_thread.class.php', 'Thread' );
		$ThreadCache = new DataObjectCache( 'Thread', false, 'T_messaging__thread', 'thrd_', 'thrd_ID', 'thrd_title' );
	}

	return $ThreadCache;
}


/**
 * messaging_Module definition
 */
class messaging_Module extends Module
{
	/**
	 * Do the initializations. Called from in _main.inc.php.
	 * This is typically where classes matching DB tables for this module are registered/loaded.
	 *
	 * Note: this should only load/register things that are going to be needed application wide,
	 * for example: for constructing menus.
	 * Anything that is needed only in a specific controller should be loaded only there.
	 * Anything that is needed only in a specific view should be loaded only there.
	 */
	function init()
	{
		$this->check_required_php_version( 'messaging' );

		load_funcs( 'messaging/model/_messaging.funcs.php' );
	}


	/**
	 * Get default module permissions
	 *
	 * #param integer Group ID
	 * @return array
	 */
	function get_default_group_permissions( $grp_ID )
	{
		switch( $grp_ID )
		{
			case 1: // Administrators group ID equals 1
				global $test_install_all_features;
				$perm_messaging = $test_install_all_features ? 'abuse' : 'delete';
				$max_new_threads = ''; // empty = no limit
				break;
			case 2: // Moderators group equals 2
			case 3: // Editors group ID equals 3
				$perm_messaging = 'write'; // Messaging permissions
				$max_new_threads = '10'; // Maximum number of new threads per day
				break;
			case 4: // Normal users group ID equals 4
				$perm_messaging = 'write';
				$max_new_threads = '5';
				break;
			case 5:		// Misbehaving/Suspect users (group ID 5) have permission by default:
				$perm_messaging = 'write';
				$max_new_threads = '1';
				break;
			case 6:  // Spammers/restricted Users
			default: // Other groups
				$perm_messaging = 'reply';
				$max_new_threads = '1';
				break;
		}

		// We can return as many default permissions as we want:
		// e.g. array ( permission_name => permission_value, ... , ... )
		return $permissions = array( 'perm_messaging' => $perm_messaging, 'max_new_threads' => $max_new_threads );
	}


	/**
	 * Get available group permissions
	 *
	 * @return array
	 */
	function get_available_group_permissions()
	{
		// 'label' is used in the group form as label for radio buttons group
		// 'user_func' is used to check user permission. This function should be defined in module initializer.
		// 'group_func' is used to check group permission. This function should be defined in module initializer.
		// 'perm_block' group form block where this permissions will be displayed. Now available, the following blocks: additional, system
		// 'options' is permission options
		$permissions = array(
			'perm_messaging' => array(
				'label' => T_('Messages'),
				'user_func'  => 'check_messaging_user_perm',
				'group_func' => 'check_messaging_group_perm',
				'perm_block' => 'additional',
				'options'  => array(
						// format: array( radio_button_value, radio_button_label, radio_button_note )
						array( 'none', T_( 'No Access' ), '' ),
						array( 'reply', T_( 'Read & Send messages to people in contacts list only (except for blocked contacts)' ), '' ),
						array( 'write', T_( 'Read & Send messages to anyone (except for blocked contacts)' ), '' ),
						array( 'delete', T_( 'Read, Send & Delete any messages (including for blocked contacts)' ), '' ),
						array( 'abuse', T_( 'Abuse Management' ), '' )  ) ),
			'max_new_threads' => array(
				'label' => T_( 'Maximum number of new threads per day' ),
				'group_func' => 'get_group_settings',
				'perm_block' => 'additional',
				'perm_type' => 'text_input',
				'note' => T_( 'Leave empty for no limit' ),
				'maxlength' => 5 ),
		);
		// We can return as many permissions as we want.
		// In other words, one module can return many pluggable permissions.
		return $permissions;
	}


	/**
	 * Check a permission for the user. ( see 'user_func' in get_available_group_permissions() function  )
	 *
	 * @param string Requested permission level
	 * @param string Permission value
	 * @param mixed Permission target (blog ID, array of cat IDs...)
	 * @return boolean True on success (permission is granted), false if permission is not granted
	 */
	function check_messaging_user_perm( $permlevel, $permvalue, $permtarget )
	{
		global $current_User;

		if( $permtarget > 0 )
		{   // Check user permission for current thread
			$ThreadCache = & get_ThreadCache();
			$Thread = & $ThreadCache->get_by_ID( $permtarget, false );

			if( $Thread === false || ! $Thread->check_thread_recipient( $current_User->ID ) )
			{
				return false;
			}
		}

		return true;
	}


	/**
	 * Check a permission for the group. ( see 'group_func' in get_available_group_permissions() function )
	 *
	 * @param string Requested permission level
	 * @param string Permission value
	 * @param mixed Permission target (blog ID, array of cat IDs...)
	 * @return boolean True on success (permission is granted), false if permission is not granted
	 */
	function check_messaging_group_perm( $permlevel, $permvalue, $permtarget )
	{
		$perm = false;
		switch ( $permvalue )
		{
			case 'abuse':
				// Abuse Management & Read, Send & Delete any messages (able to send messages even for blocked contacts)
				if( $permlevel == 'abuse' )
				{
					$perm = true;
					break;
				}
			case 'delete':
				// Read, Send & Delete any messages (able to send messages even for blocked contacts)
				if( $permlevel == 'delete' )
				{ // User can ask for delete perm...
					$perm = true;
					break;
				}
			case 'write':
				// Read & Send messages to anyone (except for blocked contacts)
				if( $permlevel == 'write' )
				{
					$perm = true;
					break;
				}
			case 'reply':
				//  Read & Send messages to people in contacts list only (except for blocked contacts)
				if( $permlevel == 'reply' )
				{
					$perm = true;
					break;
				}
		}

		return $perm;
	}


	/**
	 * Get pluggable group settings value
	 */
	function get_group_settings( $permname, $permvalue, $permtarget )
	{
		return $permvalue;
	}


	/**
	 * Build the evobar menu
	 */
	function build_evobar_menu()
	{
		global $DB;
		global $topleft_Menu, $topright_Menu;
		global $admin_url;
		global $current_User;
		global $unread_messages_count;

		$left_entries = array();
		$right_entries = array();

		if( $current_User->check_perm( 'perm_messaging', 'reply' ) )
		{
			if( ! empty( $topleft_Menu->_menus['entries']['tools']['entries'] ) )
			{
				// TODO: this is hackish and would require a proper function call
				$topleft_Menu->_menus['entries']['tools']['disabled'] = false;

				$left_entries['messaging'] = array(
						'text' => T_('Messages').'&hellip;',
						'href' => $admin_url.'?ctrl=threads',
					);
			}

			$messages_url = get_dispctrl_url( 'threads' );
			$contacts_url = get_dispctrl_url( 'contacts' );

			if( ! empty( $messages_url ) || ! empty( $contacts_url )  )
			{
				$right_entries[] = array( 'separator' => true );
			}

			if( ! empty( $messages_url ) )
			{ // Display this menu item only when url is available to current user
				$right_entries['messages'] = array(
						'text' => T_('Messages'),
						'href' => $messages_url,
						'class' => 'evo_messages_link',
					);
			}
			if( ! empty( $contacts_url ) )
			{ // Display this menu item only when url is available to current user
				$right_entries['contacts'] = array(
						'text' => T_('Contacts'),
						'href' => $contacts_url,
						'class' => 'evo_messages_link',
					);
			}

			// Count unread messages for current user
			$unread_messages_count = get_unread_messages_count();
			if( $unread_messages_count > 0 )
			{
				$right_entries['messages']['text'] = T_('Messages').' <span class="badge badge-important">'.$unread_messages_count.'</span>';
			}
		}

		$topleft_Menu->add_menu_entries( 'tools', $left_entries );
		$topright_Menu->insert_menu_entries_after( array( 'userprefs', 'name' ), $right_entries );
	}

	/**
	 * Builds the 3rd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_3()
	{
		global $admin_url;
		/**
		 * @var User
		 */
		global $current_User;

		/**
		 * @var AdminUI_general
		 */
		global $AdminUI;

		if( !$current_User->check_perm( 'admin', 'restricted' ) )
		{
			return;
		}

		if( $current_User->check_perm( 'perm_messaging', 'reply' ) )
		{ // Permission to view messaging:

			// Count unread messages for current user
			$unread_messages_count = get_unread_messages_count();
			if( $unread_messages_count > 0 )
			{
				$messages_counter = ' <span class="badge badge-important">'.$unread_messages_count.'</span>';
			}
			else
			{
				$messages_counter = '';
			}

			$AdminUI->add_menu_entries( NULL, array(
						'messaging' => array(
						'text' => T_('Messages').$messages_counter,
						'title' => T_('Messages'),
						'href' => $admin_url.'?ctrl=threads',
						'entries' => get_messaging_sub_entries( true )
					),
				), 'users' );
		}
	}


	/**
	 * Get the messaging module cron jobs
	 *
	 * @see Module::get_cron_jobs()
	 */
	function get_cron_jobs()
	{
		return array(
			'send-unread-messages-reminders' => array(
				'name'   => T_('Send reminders about unread messages'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_unread_message_reminder.job.php',
				'params' => NULL,
			)
		);
	}


	/**
	 * Handle messaging module htsrv actions
	 */
	function handle_htsrv_action()
	{
		global $current_User, $Blog, $Session, $Messages, $samedomain_htsrv_url;

		// Init objects we want to work on.
		$action = param_action( true, true );
		$disp = param( 'disp', '/^[a-z0-9\-_]+$/', 'threads' );

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_'.$disp );

		// Load classes
		load_class( 'messaging/model/_thread.class.php', 'Thread' );
		load_class( 'messaging/model/_message.class.php', 'Message' );

		if( !is_logged_in() )
		{ // user must be logged in
			debug_die( 'User must be logged in to proceed with messaging updates!' );
		}

		// Check permission:
		$current_User->check_perm( 'perm_messaging', 'reply', true );

		// set where to redirect
		$redirect_to = param( 'redirect_to', 'url', NULL );
		if( empty( $redirect_to ) )
		{
			if( isset( $Blog ) )
			{
				$redirect_to = url_add_param( $Blog->gen_baseurl(), 'disp='.$disp );
			}
			else
			{
				$redirect_to = url_add_param( $baseurl, 'disp='.$disp );
			}
		}

		if( ( $disp != 'contacts' ) && ( $thrd_ID = param( 'thrd_ID', 'integer', '', true ) ) )
		{ // Load thread from cache:
			$ThreadCache = & get_ThreadCache();
			if( ( $edited_Thread = & $ThreadCache->get_by_ID( $thrd_ID, false ) ) === false )
			{ // Thread doesn't exists with this ID
				unset( $edited_Thread );
				forget_param( 'thrd_ID' );
				$Messages->add( T_('The requested thread does not exist any longer.'), 'error' );
				$action = 'nil';
			}
		}

		switch( $disp )
		{
			// threads action
			case 'threads':
				if( $action != 'create' && $action != 'preview' )
				{ // Make sure we got a thrd_ID:
					param( 'thrd_ID', 'integer', true );
				}

				switch( $action )
				{
					case 'create': // create thread
					case 'preview': // preview message
						// Stop a request from the blocked IP addresses or Domains
						antispam_block_request();

						// check if create new thread is allowed
						if( check_create_thread_limit() )
						{ // max new threads limit reached, don't allow to create new thread
							debug_die( 'Invalid request, new conversation limit already reached!' );
						}

						$creating_success = create_new_thread();
						if( !$creating_success || $action == 'preview' )
						{ // unsuccessful new thread creation OR preview mode
							global $edited_Thread, $edited_Message, $thrd_recipients, $thrd_recipients_array;

							$redirect_to .= '&action=new';
							// save new message and thread params into the Session to not lose the content
							$unsaved_message_params = array();
							$unsaved_message_params[ 'action' ] = $action;
							$unsaved_message_params[ 'subject' ] = $edited_Thread->title;
							$unsaved_message_params[ 'message' ] = $edited_Message->text;
							$unsaved_message_params[ 'message_original' ] = $edited_Message->original_text;
							$unsaved_message_params[ 'renderers' ] = $edited_Message->get_renderers_validated();
							$unsaved_message_params[ 'thrdtype' ] = param( 'thrdtype', 'string', 'individual' );  // alternative: discussion
							$unsaved_message_params[ 'thrd_recipients' ] = $thrd_recipients;
							$unsaved_message_params[ 'thrd_recipients_array' ] = $thrd_recipients_array;
							$unsaved_message_params[ 'creating_success' ] = $creating_success;
							save_message_params_to_session( $unsaved_message_params );
						}
						break;

					case 'delete': // delete thread
						// Check permission:
						$current_User->check_perm( 'perm_messaging', 'delete', true );

						$confirmed = param( 'confirmed', 'integer', 0 );
						if( $confirmed )
						{
							$msg = sprintf( T_('Thread &laquo;%s&raquo; deleted.'), $edited_Thread->dget('title') );
							$edited_Thread->dbdelete();
							unset( $edited_Thread );
							forget_param( 'thrd_ID' );
							$Messages->add( $msg, 'success' );
						}
						else
						{
							$delete_url = $samedomain_htsrv_url.'action.php?mname=messaging&thrd_ID='.$edited_Thread->ID.'&action=delete&confirmed=1&redirect_to='.$redirect_to.'&'.url_crumb( 'messaging_threads' );
							$ok_button = '<a href="'.$delete_url.'" class="btn btn-danger">'.T_( 'I am sure!' ).'</a>';
							$cancel_button = '<a href="'.$redirect_to.'" class="btn btn-default">CANCEL</a>';
							$msg = sprintf( T_( 'You are about to delete all messages in the conversation &laquo;%s&raquo;.' ), $edited_Thread->dget('title') );
							$msg .= '<br />'.T_( 'This CANNOT be undone!').'<br />'.T_( 'Are you sure?' ).'<br /><br />'.$ok_button."\t".$cancel_button;
							$Messages->add( $msg, 'error' );
						}
						break;

					case 'leave': // user wants to leave the thread
						leave_thread( $edited_Thread->ID, $current_User->ID, false );

						$Messages->add( sprintf( T_( 'You have successfuly left the &laquo;%s&raquo; conversation!' ), $edited_Thread->get( 'title' ) ), 'success' );
						break;

					case 'close': // close the thread
					case 'close_and_block': // close the thread and block contact
						leave_thread( $edited_Thread->ID, $current_User->ID, true );

						// user has closed this conversation because there was only one other user involved
						$Messages->add( sprintf( T_( 'You have successfuly closed the &laquo;%s&raquo; conversation!' ), $edited_Thread->get( 'title' ) ), 'success' );
						if( $action == 'close_and_block' )
						{ // user also wants to block contact with the other user involved in this thread
							$block_user_ID = param( 'block_ID', 'integer', true );
							$UserCache = & get_UserCache();
							$blocked_User = $UserCache->get_by_ID( $block_user_ID );

							set_contact_blocked( $block_user_ID, true );
							$Messages->add( sprintf( T_( '&laquo;%s&raquo; was blocked.' ), $blocked_User->get( 'login' ) ), 'success' );
						}
						break;
				}
				break; // break from threads action switch

			// contacts action
			case 'contacts':
				$user_ID = param( 'user_ID', 'string', true );

				if( ( $action != 'block' ) && ( $action != 'unblock' ) )
				{ // only block or unblock is valid
					debug_die( "Invalid action param" );
				}
				if( set_contact_blocked( $user_ID, ( ( $action == 'block' ) ? 1 : 0 ) ) )
				{
					if( $action == 'block' )
					{
						$Messages->add( T_('You have blocked this user from contacting you.'), 'success' );
					}
					else
					{
						$Messages->add( T_('You have unblocked this user so he can contact you again.'), 'success' );
					}
				}
				$redirect_to = str_replace( '&amp;', '&', $redirect_to );
				break;

			// messages action
			case 'messages':
				switch( $action )
				{
					case 'create': // create new message
						// Stop a request from the blocked IP addresses or Domains
						antispam_block_request();

						create_new_message( $thrd_ID );
						break;

					case 'preview': // create new message
						// Stop a request from the blocked IP addresses or Domains
						antispam_block_request();

						global $edited_Message;

						$creating_success = create_new_message( $thrd_ID, 'preview' );

						// save new message and thread params into the Session to not lose the content
						$unsaved_message_params = array();
						$unsaved_message_params[ 'action' ] = $action;
						$unsaved_message_params[ 'message' ] = $edited_Message->text;
						$unsaved_message_params[ 'message_original' ] = $edited_Message->original_text;
						$unsaved_message_params[ 'renderers' ] = $edited_Message->get_renderers_validated();
						$unsaved_message_params[ 'creating_success' ] = $creating_success;
						save_message_params_to_session( $unsaved_message_params );
						break;

					case 'delete': // delete message
						// Check permission:
						$current_User->check_perm( 'perm_messaging', 'delete', true );

						$msg_ID = param( 'msg_ID', 'integer', true );
						$MessageCache = & get_MessageCache();
						if( ($edited_Message = & $MessageCache->get_by_ID( $msg_ID, false )) === false )
						{
							$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Message') ), 'error' );
							break;
						}

						$confirmed = param( 'confirmed', 'integer', 0 );
						if( $confirmed )
						{ // delete message
							$edited_Message->dbdelete();
							unset( $edited_Message );
							$Messages->add( T_('Message deleted.'), 'success' );
						}
						else
						{
							$delete_url = $samedomain_htsrv_url.'action.php?mname=messaging&disp=messages&thrd_ID='.$thrd_ID.'&msg_ID='.$msg_ID.'&action=delete&confirmed=1';
							if( ! empty( $Blog ) )
							{ // Add blog ID to correctly redirect after deleting:
								$delete_url .= '&blog='.$Blog->ID;
							}
							$delete_url = url_add_param( $delete_url, 'redirect_to='.rawurlencode( $redirect_to ), '&' ).'&'.url_crumb( 'messaging_messages' );
							$ok_button = '<a href="'.$delete_url.'" class="btn btn-danger">'.T_('I am sure!').'</a>';
							$cancel_button = '<a href="'.$redirect_to.'" class="btn btn-default">'.T_('CANCEL').'</a>';
							$msg = T_('You are about to delete this message. ').'<br /> '.T_('This CANNOT be undone!').'<br />'.T_( 'Are you sure?' ).'<br /><br />'.$ok_button."\t".$cancel_button;
							$Messages->add( $msg, 'error' );
						}
						break;
				}
				break;
		}

		header_redirect( $redirect_to ); // Will save $Messages into Session
	}


	/**
	 * Get contacts list params
	 */
	function get_contacts_list_params()
	{
		global $module_contacts_list_params;

		if( !isset( $module_contacts_list_params ) )
		{	// Initialize this array first time
			$module_contacts_list_params = array();
		}

		$module_contacts_list_params['title_selected'] = T_('Send a message to all selected contacts');
		$module_contacts_list_params['title_group'] = T_('Send a message to all %d contacts in the &laquo;%s&raquo; group');
		$module_contacts_list_params['recipients_link'] = get_dispctrl_url( 'threads', 'action=new' );
	}
}

$messaging_Module = new messaging_Module();

?>