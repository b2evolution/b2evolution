<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package polls
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Minimum PHP version required for messaging module to function properly
 */
$required_php_version[ 'polls' ] = '5.0';

/**
 * Minimum MYSQL version required for messaging module to function properly
 */
$required_mysql_version[ 'polls' ] = '5.0.3';

/**
 * Aliases for table names:
 *
 * (You should not need to change them.
 *  If you want to have multiple b2evo installations in a single database you should
 *  change {@link $tableprefix} in _basic_config.php)
 */
$db_config['aliases'] = array_merge( $db_config['aliases'], array(
		'T_polls__question' => $tableprefix.'polls__question',
		'T_polls__option'   => $tableprefix.'polls__option',
		'T_polls__answer'   => $tableprefix.'polls__answer',
	) );

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
$ctrl_mappings['polls'] = 'polls/polls.ctrl.php';


/**
 * Get the PollCache
 *
 * @return PollCache
 */
function & get_PollCache()
{
	global $PollCache;

	if( ! isset( $PollCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'polls/model/_poll.class.php', 'Poll' );
		$PollCache = new DataObjectCache( 'Poll', false, 'T_polls__question', 'pqst_', 'pqst_ID', 'pqst_question_text' );
	}

	return $PollCache;
}


/**
 * Get the PollOptionCache
 *
 * @return PollOptionCache
 */
function & get_PollOptionCache()
{
	global $PollOptionCache;

	if( ! isset( $PollOptionCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'polls/model/_poll_option.class.php', 'PollOption' );
		$PollOptionCache = new DataObjectCache( 'PollOption', false, 'T_polls__option', 'popt_', 'popt_ID', 'popt_option_text', 'popt_order' );
	}

	return $PollOptionCache;
}


/**
 * polls_Module definition
 */
class polls_Module extends Module
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
		$this->check_required_php_version( 'polls' );
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
			case 1:
				// Administrators (group ID 1) have full permission by default:
				$permpolls = 'edit';
				break;

			case 2:
			case 3:
				// Moderators (group ID 2) & Editors (group ID 3) have permission by default:
				$permpolls = 'create';
				break;

			case 4:
				// Normal Users (group ID 4) have permission by default:
				$permpolls = 'none';
				break;

			// case 5: // Misbehaving/Suspect users (group ID 5) have permission by default:
			// case 6: // Spammers/restricted Users
			default:
				// Other groups have no permission by default
				$permpolls = 'none';
				break;
		}

		// We can return as many default permissions as we want:
		// e.g. array ( permission_name => permission_value, ... , ... )
		return $permissions = array(
			'perm_polls' => $permpolls,
		 );
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
			'perm_polls' => array(
				'label'      => T_('Polls'),
				'user_func'  => 'check_poll_user_perm',
				'group_func' => 'check_poll_group_perm',
				'perm_block' => 'additional',
				'options'    => array(
						// format: array( radio_button_value, radio_button_label, radio_button_note )
						array( 'none', T_('No Access') ),
						array( 'create', T_('Create & Edit owned polls only') ),
						array( 'view', T_('Create & Edit owned polls + View all') ),
						array( 'edit', T_('Full Access') )
					),
				'perm_type' => 'radiobox',
				'field_lines' => true,
				),
		);

		// We can return as many permissions as we want.
		// In other words, one module can return many pluggable permissions.
		return $permissions;
	}

	/**
	 * Check an user permission for the poll. ( see 'user_func' in get_available_group_permissions() function  )
	 *
	 * @param string Requested permission level
	 * @param string Permission value, this is the value on the database
	 * @param mixed Permission target (blog ID, array of cat IDs...)
	 * @return boolean True on success (permission is granted), false if permission is not granted
	 */
	function check_poll_user_perm( $permlevel, $permvalue, $permtarget )
	{
		return true;
	}

	/**
	 * Check a group permission for the poll. ( see 'group_func' in get_available_group_permissions() function )
	 *
	 * @param string Requested permission level
	 * @param string Permission value
	 * @param mixed Permission target (blog ID, array of cat IDs...)
	 * @return boolean True on success (permission is granted), false if permission is not granted
	 */
	function check_poll_group_perm( $permlevel, $permvalue, $permtarget )
	{
		$perm = false;

		switch ( $permvalue )
		{
			case 'edit':
				// Users has edit perms
				if( $permlevel == 'edit' )
				{
					$perm = true;
					break;
				}

			case 'view':
				// Users has view perms
				if( $permlevel == 'view' )
				{
					$perm = true;
					break;
				}

			case 'create':
				// Users has a create permisson:
				if( $permlevel == 'create' )
				{
					$perm = true;
					break;
				}
		}

		if( ! $perm && is_logged_in() && ! empty( $permtarget )
		    && ( $permlevel == 'edit' || $permlevel == 'view' ) )
		{	// If this perm level is still not allowed, check if current user is owner of the requested Poll:
			global $current_User;
			if( $current_User->ID == $permtarget->owner_user_ID )
			{	// Current user is owner
				$perm = true;
			}
		}

		return $perm;
	}


	/**
	 * Handle messaging module htsrv actions
	 */
	function handle_htsrv_action()
	{
		global $Session, $Messages;

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'polls' );

		if( ! is_logged_in() )
		{	// User must be logged in
			debug_die( 'User must be logged in to vote!' );
		}

		// Load classes:
		load_class( 'polls/model/_poll.class.php', 'Poll' );
		load_class( 'polls/model/_poll_option.class.php', 'PollOption' );

		$action = param_action();

		switch( $action )
		{
			case 'vote':
				// Vote on poll:
				$poll_ID = param( 'poll_ID', 'integer', true );
				$poll_option_ID = param( 'poll_answer', 'integer', 0 );

				if( empty( $poll_option_ID ) )
				{	// The poll option must be selected:
					$Messages->add( T_('Please select an answer for the poll.'), 'error' );
					break;
				}

				// Check if the requested poll is correct:
				$PollCache = & get_PollCache();
				$Poll = & $PollCache->get_by_ID( $poll_ID, false, false );
				if( ! $Poll )
				{	// The requested poll doesn't exist in DB:
					$Messages->add( 'Wrong poll request!', 'error' );
					break;
				}

				// Check if the requested poll option is correct:
				$PollOptionCache = & get_PollOptionCache();
				$PollOption = & $PollOptionCache->get_by_ID( $poll_option_ID, false, false );
				if( ! $PollOption || $PollOption->pqst_ID != $Poll->ID )
				{	// The requested poll option doesn't exist in DB:
					$Messages->add( 'Wrong poll request!', 'error' );
					break;
				}

				// Vote on the poll by current User:
				if( $PollOption->vote() )
				{	// Successful voting:
					$Messages->add( T_('Your vote has been cast.'), 'success' );
				}
				break;
		}
	}


	/**
	 * Builds the 2nd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_2()
	{
		global $admin_url, $current_User, $AdminUI;

		if( ! $current_User->check_perm( 'admin', 'restricted' ) )
		{	// User must has an access to back-office:
			return;
		}

		if( $current_User->check_perm( 'polls', 'create' ) )
		{	// User has an access at least to view and edit own polls:
			$AdminUI->add_menu_entries( array( 'site' ), array(
				'polls' => array(
					'text' => T_('Polls'),
					'href' => $admin_url.'?ctrl=polls' ),
				) );
		}
	}
}

$polls_Module = new polls_Module();

?>