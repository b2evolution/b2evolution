<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package messaging
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Aliases for table names:
 *
 * (You should not need to change them.
 *  If you want to have multiple b2evo installations in a single database you should
 *  change {@link $tableprefix} in _basic_config.php)
 */
$db_config['aliases']['T_messaging__thread'] = $tableprefix.'messaging__thread';
$db_config['aliases']['T_messaging__message'] = $tableprefix.'messaging__message';
$db_config['aliases']['T_messaging__threadstatus'] = $tableprefix.'messaging__threadstatus';
$db_config['aliases']['T_messaging__contact'] = $tableprefix.'messaging__contact';

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
		$MessageCache = new DataObjectCache( 'Message', false, 'T_messaging__message', 'msg_', 'msg_ID' );
	}

	return $MessageCache;
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
				$permname = 'delete';
				break;
			case 2: // Privileged Bloggers group equals 2
				$permname = 'write';
				break;
			case 3: // Bloggers group ID equals 3
				$permname = 'reply';
				break;
			default: // Other groups
				$permname = 'none';
				break;
		}

		// We can return as many default permissions as we want:
		// e.g. array ( permission_name => permission_value, ... , ... )
		return $permissions = array( 'perm_messaging' => $permname );
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
				'label' => T_('Messaging'),
				'user_func'  => 'check_messaging_user_perm',
				'group_func' => 'check_messaging_group_perm',
				'perm_block' => 'additional',
				'options'  => array(
						// format: array( radio_button_value, radio_button_label, radio_button_note )
						array( 'none', T_( 'No Access' ), '' ),
						array( 'reply', T_( 'Reply to people you have messaged with in the past' ), '' ),
						array( 'write', T_( 'Create threads, view any thread you\'re involved in & reply' ), '' ),
						array( 'delete', T_( 'Create threads, view and delete any thread you\'re involved in & reply' ) )  ) ) );
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
			case 'delete':
				// same as write but you can also delete threads you're involved in
				if( $permlevel == 'delete' )
				{ // User can ask for delete perm...
					$perm = true;
					break;
				}
			// efy-maxim> This is right location for 'reply' permission, because
			// efy-maxim> user with 'reply' permission has 'write' permission, but he has not 'delete' permission.
			// efy-maxim> But user with 'delete' or 'write' permission has no reply repmission.
			// efy-maxim> Note: 'reply' permission means only restriction of 'write' permission.
			case 'reply':
				//  reply to people you have messaged with in the past
				if( $permlevel == 'reply' && $permvalue != 'delete')
				{
					$perm = true;
					break;
				}
			// ... or for any lower priority perm... (no break)
			case 'write':
				//  you create threads, view any thread you're involved in & reply
				if( $permlevel == 'write' )
				{
					$perm = true;
					break;
				}
		}

		return $perm;
	}


	/**
	 * Build the evobar menu
	 */
	function build_evobar_menu()
	{
		global $DB;
		global $topright_Menu;
		global $admin_url;
		global $current_User;
		global $unread_messages_count;

		if( !$current_User->check_perm( 'admin', 'restricted' ) )
		{
			return;
		}

		$entries = array();

		if( $current_User->check_perm( 'perm_messaging', 'write' ) )
		{
			$entries['messaging'] = array(
				'text' => T_('Messages'),
				'href' => $admin_url.'?ctrl=threads',
				'style' => 'padding: 3px 1ex;',
			);

			// Count unread messages for current user
			$SQL = new SQL();

			$SQL->SELECT( 'COUNT(*)' );

			$SQL->FROM( 'T_messaging__threadstatus ts
							LEFT OUTER JOIN T_messaging__message mu
								ON ts.tsta_first_unread_msg_ID = mu.msg_ID
							INNER JOIN T_messaging__message mm
								ON ts.tsta_thread_ID = mm.msg_thread_ID
								AND mm.msg_datetime >= mu.msg_datetime' );

			$SQL->WHERE( 'ts.tsta_first_unread_msg_ID IS NOT NULL AND ts.tsta_user_ID = '.$current_User->ID );

			$unread_messages_count = $DB->get_var( $SQL->get() );
			if( $unread_messages_count > 0 )
			{
				$entries['messaging']['text'] = '<b>'.T_('Messages').' <span class="badge">'.$unread_messages_count.'</span></b>';
			}
		}

		$topright_Menu->insert_menu_entries_after( 'userprefs', $entries );
	}

	/**
	 * Builds the 3rd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_3()
	{
		global $dispatcher;
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

		if( $current_User->check_perm( 'perm_messaging', 'write' ) )
		{	// Permission to view messaging:
			$AdminUI->add_menu_entries( NULL, array(
						'messaging' => array(
						'text' => T_('Messaging'),
						'title' => T_('Messaging'),
						'href' => $dispatcher.'?ctrl=threads',
						'entries' => array(
								'messages' => array(
									'text' => T_('Messages'),
									'href' => '?ctrl=threads' ),
								'contacts' => array(
									'text' => T_('Contacts'),
									'href' => '?ctrl=contacts' ),
							)
					),
				) );
		}
	}
}

$messaging_Module = new messaging_Module();

/*
 * $Log$
 * Revision 1.20  2011/02/15 15:37:00  efy-asimo
 * Change access to admin permission
 *
 * Revision 1.19  2010/01/30 18:55:32  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.18  2009/10/28 14:55:11  efy-maxim
 * pluggable permissions separated by blocks in group form
 *
 * Revision 1.17  2009/10/19 07:04:20  efy-maxim
 * code formatting
 *
 * Revision 1.16  2009/10/09 05:27:55  efy-maxim
 * pluggable permission - fix comment
 *
 * Revision 1.15  2009/10/08 20:05:52  efy-maxim
 * Modular/Pluggable Permissions
 *
 * Revision 1.14  2009/09/21 03:14:35  fplanque
 * modularized a little more
 *
 * Revision 1.13  2009/09/19 11:29:05  efy-maxim
 * Refactoring
 *
 * Revision 1.12  2009/09/18 16:16:50  efy-maxim
 * comments tab in messaging module
 *
 * Revision 1.11  2009/09/18 14:22:11  efy-maxim
 * 1. 'reply' permission in group form
 * 2. functionality to store and update contacts
 * 3. fix in misc functions
 *
 * Revision 1.10  2009/09/16 15:14:47  efy-maxim
 * badge for unread message number
 *
 * Revision 1.9  2009/09/16 09:15:32  efy-maxim
 * Messaging module improvements
 *
 * Revision 1.8  2009/09/16 00:48:50  fplanque
 * getting a bit more serious with modules
 *
 * Revision 1.7  2009/09/15 20:39:00  tblue246
 * Hide "Message" button on evoBar if no sufficient permissions; style
 *
 * Revision 1.6  2009/09/15 20:05:06  efy-maxim
 * 1. Red badge for messages in the right menu
 * 2. Insert menu entries method in menu class
 *
 * Revision 1.5  2009/09/15 19:31:55  fplanque
 * Attempt to load classes & functions as late as possible, only when needed. Also not loading module specific stuff if a module is disabled (module granularity still needs to be improved)
 * PHP 4 compatible. Even better on PHP 5.
 * I may have broken a few things. Sorry. This is pretty hard to do in one swoop without any glitch.
 * Thanks for fixing or reporting if you spot issues.
 *
 * Revision 1.4  2009/09/14 07:31:43  efy-maxim
 * 1. Messaging permissions have been fully implemented
 * 2. Messaging has been added to evo bar menu
 *
 * Revision 1.3  2009/09/12 18:44:11  efy-maxim
 * Messaging module improvements
 *
 * Revision 1.2  2009/09/10 18:24:07  fplanque
 * doc
 *
 */
?>
