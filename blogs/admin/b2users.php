<?php
/**
 * This file implements the UI controller for settings management.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * @todo finish move to "new standard", i-e:
 *    1 - init params
 *    2 - perform actions
 *    3 - display error messages
 *    4 - display payload
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE
 *
 * @version $Id$
 */

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_header.php';
$AdminUI->setPath( 'users' );

param( 'action', 'string', 'list' );

// fplanque> the rule that should be consistent accross the app, is that if no object is requested then $edited_Object remains unset! use isset() !
# $edited_User = NULL; // reset/init

/*
 * Load editable objects:
 */
if( param( 'user_ID', 'integer', NULL, true, false, false ) )
{
	if( ($edited_User = $UserCache->get_by_ID( $user_ID, false )) === false )
	{	// We could not find the User to edit:
		unset( $edited_User );
		$Messages->head = T_('Cannot edit user!');
		$Messages->add( T_('Requested user does not exist any longer.'), 'error' );
		$action = 'list';
	}
	elseif( $action == 'list' )
	{
		$action = 'edit_user';
	}
}
elseif( param( 'grp_ID', 'integer', NULL, true, false, false ) )
{
	if( ($edited_Group = $GroupCache->get_by_ID( $grp_ID, false )) === false )
	{	// We could not find the User to edit:
		unset( $edited_Group );
		$Messages->head = T_('Cannot edit group!');
		$Messages->add( T_('Requested group does not exist any longer.'), 'error' );
		$action = 'list';
	}
	elseif( $action == 'list' )
	{
		$action = 'edit_group';
	}
}


$user_profile_only = 0;
// Check permission:
if( !$current_User->check_perm( 'users', 'edit', false ) )
{
	// allow profile editing/viewing only
	$user_profile_only = 1;

	if( $action != 'list' && $action != 'edit_user' && $action != 'userupdate' )
	{ // This should be prevented in the UI
		$Messages->add( 'You have no permission to edit other users or groups!', 'error' );
		$action = 'list';
	}
}

if( $demo_mode && isset($edited_User) && ( $edited_User->ID == 1 || $edited_User->login == 'demouser' ) )
{ // User may edit - but demo mode restrictions apply!
	$Messages->add( T_('You cannot edit the admin and demouser profile in demo mode!'), 'error' );

	if( strpos( $action, 'delete_' ) === 0 )
	{
		$action = 'list';
	}

	$user_profile_only = 1;
}


/*
 * Perform actions:
 */
if( $Messages->count() )
{
	if( $action == 'userupdate' )
	{ // display top menu that was suppressed before
		require dirname(__FILE__).'/_menutop.php';
	}
}
else
{
	switch ($action)
	{ // actions only when editing users is allowed

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
			param( 'edited_user_ID', 'integer', true );
			if( $edited_user_ID == 0 )
			{ // we create a new user
				$edited_User = & new User();
				$edited_User->set_datecreated( $localtimenow );
			}
			else
			{ // we edit an existing user:
				$edited_User = & $UserCache->get_by_ID( $edited_user_ID );
				// We need to remember the current login in order to later update login cookie if necessary...
				$saved_login = $edited_User->login;
			}

			if( $user_profile_only && $edited_user_ID != $current_User->ID )
			{ // user is only allowed to update him/herself
				$Messages->add( T_('You are only allowed to update your own profile!'), 'error' );
				break;
			}

			$Request->param( 'edited_user_login', 'string', true );
			$Request->param_check_not_empty( 'edited_user_login', T_('You must provide a login!') );
			$edited_user_login = strtolower( $edited_user_login );

			if( !$user_profile_only )
			{ // allow changing level/group not for profile mode
				$Request->param_integer_range( 'edited_user_level', 0, 10, T_('User level must be between %d and %d.') );
				$edited_User->set( 'level', $edited_user_level );

				param( 'edited_user_grp_ID', 'integer', true );
				$edited_user_Group = $GroupCache->get_by_ID( $edited_user_grp_ID );
				$edited_User->setGroup( $edited_user_Group );
				// echo 'new group = ';
				// $edited_User->Group->disp('name');
			}

			// check if new login already exists for another user_ID
			$query = "SELECT ID
									FROM T_users
								 WHERE user_login = '$edited_user_login'
								   AND ID != $edited_user_ID";
			if( $q = $DB->get_var( $query ) )
			{
				$Request->param_error( 'edited_user_login',
					sprintf( T_('This login already exists. Do you want to <a %s>edit the existing user</a>?'), 'href="?user_ID='.$q.'"' ), 'error' );
			}

			param( 'edited_user_firstname', 'string', true );
			param( 'edited_user_lastname', 'string', true );

			$Request->param( 'edited_user_nickname', 'string', true );
			$Request->param_check_not_empty( 'edited_user_nickname', T_('Please enter a nickname (can be the same as your login).') );

			param( 'edited_user_idmode', 'string', true );
			param( 'edited_user_locale', 'string', true );

			$Request->param( 'edited_user_email', 'string', true );
			$Request->param_check_not_empty( 'edited_user_email', T_('Please enter an e-mail address.') );
			$Request->param_check_email( 'edited_user_email', true );

			$Request->param( 'edited_user_url', 'string', true );
			$Request->param_check_url( 'edited_user_url', $comments_allowed_uri_scheme );

			$Request->param( 'edited_user_icq', 'string', true );
			$Request->param_check_number( 'edited_user_icq', T_('The ICQ UIN can only be a number, no letters allowed.') );

			param( 'edited_user_aim', 'string', true );

			$Request->param( 'edited_user_msn', 'string', true );
			$Request->param_check_email( 'edited_user_msn', false );

			param( 'edited_user_yim', 'string', true );
			param( 'edited_user_notify', 'integer', 0 );
			param( 'edited_user_showonline', 'integer', 0 );

			$Request->param( 'edited_user_pass1', 'string', true );
			$Request->param( 'edited_user_pass2', 'string', true );
			$Request->param_check_passwords( 'edited_user_pass1', 'edited_user_pass2', ($edited_user_ID == 0) );

			$edited_User->set( 'login', $edited_user_login );
			$edited_User->set( 'firstname', $edited_user_firstname );
			$edited_User->set( 'lastname', $edited_user_lastname );
			$edited_User->set( 'nickname', $edited_user_nickname );
			$edited_User->set( 'idmode', $edited_user_idmode );
			$edited_User->set( 'locale', $edited_user_locale );
			$edited_User->set( 'email', $edited_user_email );
			$edited_User->set( 'url', $edited_user_url );
			$edited_User->set( 'icq', $edited_user_icq );
			$edited_User->set( 'aim', $edited_user_aim );
			$edited_User->set( 'msn', $edited_user_msn );
			$edited_User->set( 'yim', $edited_user_yim );
			$edited_User->set( 'notify', $edited_user_notify );
			$edited_User->set( 'showonline', $edited_user_showonline );


			if( $Messages->count( 'error' ) )
			{	// We have found validation errors:
				$action = 'edit_user';
			}
			else
			{ // OK, no error.
				$new_pass = '';

				if( !empty($edited_user_pass2) )
				{ // Password provided, we must encode it
					$new_pass = md5( $edited_user_pass2 );

					$edited_User->set( 'pass', $new_pass ); // set password
				}

				if( $edited_User->ID != 0 )
				{ // Commit update to the DB:
					$edited_User->dbupdate();
					$Messages->add( T_('User updated.'), 'success' );
				}
				else
				{ // Insert user into DB
					$edited_User->dbinsert();
					$Messages->add( T_('New user created.'), 'success' );
				}

				if( $edited_user_ID == $current_User->ID )
				{ // current user updates him/herself - we have to set cookies to keep him logged in

					if( !empty($new_pass) && $current_User->pass != $new_pass )
					{ // The user changed his password, update login cookie!
						setcookie( $cookie_pass, $new_pass, $cookie_expires, $cookie_path, $cookie_domain);
					}

					if( $edited_User->login != $saved_login )
					{ // The user changed his own login, update login cookie!
						setcookie( $cookie_user, $edited_User->login, $cookie_expires, $cookie_path, $cookie_domain );
					}

				}

			}
			break;


		case 'promote':
			param( 'prom', 'string', true );
			param( 'id', 'integer', true );

			$edited_user_ID = $id;

			$UserToPromote =& $UserCache->get_by_ID( $id );
			$usertopromote_level = $UserToPromote->get( 'level' );

			if( ! in_array($prom, array('up', 'down'))
					|| ($prom == 'up' && $usertopromote_level > 9)
					|| ($prom == 'down' && $usertopromote_level < 1)
				)
			{
				$Messages->add( T_('Invalid promotion.'), 'error' );
			}
			else
			{
				if( $prom == 'up' )
				{
					$sql = "UPDATE T_users SET user_level=user_level+1 WHERE ID = $id";
				}
				elseif( $prom == 'down' )
				{
					$sql = "UPDATE T_users SET user_level=user_level-1 WHERE ID = $id";
				}

				if( $DB->query( $sql ) )
				{
					$Messages->add( T_('User level changed.'), 'success' );
				}
				else
				{
					$Messages->add( sprintf( 'Couldn\'t change %s\'s level.', $UserToPromote->login ), 'error' );
				}
			}
			break;


		case 'delete_user':
			/*
			 * Delete user
			 */
			if( !isset($edited_User) )
				die( 'no User set' );

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
			$Request->param( 'edited_grp_ID', 'integer', true );
			$Request->param( 'edited_grp_name', 'string', true );

			$Request->param_check_not_empty( 'edited_grp_name', T_('You must provide a group name!') );

			// check if the group name already exists for another group
			$query = "SELECT grp_ID FROM T_groups
														 WHERE grp_name = '$edited_grp_name' AND grp_ID != $edited_grp_ID";
			if( $q = $DB->get_var( $query ) )
			{
				$Request->param_error( 'edited_grp_name',
							sprintf( T_('This group name already exists! Do you want to <a %s>edit the existing group</a>?'), 'href="?grp_ID='.$q.'"' ) );
			}

			if( $edited_grp_ID == 0 )
			{
				$edited_Group = & new Group();
			}
			else
			{
				$edited_Group = $GroupCache->get_by_ID( $edited_grp_ID );
			}

			$edited_Group->set( 'name', $edited_grp_name );

			$edited_Group->set( 'perm_blogs', param( 'edited_grp_perm_blogs', 'string', true ) );
			$edited_Group->set( 'perm_stats', param( 'edited_grp_perm_stats', 'string', true ) );
			$edited_Group->set( 'perm_spamblacklist', param( 'edited_grp_perm_spamblacklist', 'string', true ) );
			$edited_Group->set( 'perm_templates', param( 'edited_grp_perm_templates', 'integer', 0 ) );
			$edited_Group->set( 'perm_options', param( 'edited_grp_perm_options', 'string', true ) );
			$edited_Group->set( 'perm_files', param( 'edited_grp_perm_files', 'string', true ) );

			if( $edited_grp_ID != 1 )
			{ // Groups others than #1 can be prevented from logging in or editing users
				$edited_Group->set( 'perm_admin', param( 'edited_grp_perm_admin', 'string', true ) );
				$edited_Group->set( 'perm_users', param( 'edited_grp_perm_users', 'string', true ) );
			}

			if( $Messages->count( 'error' ) )
			{	// We have found validation errors:
				$action = 'edit_group';
				break;
			}

			if( $edited_grp_ID == 0 )
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
				die( 'no Group set' );

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
				if( ! $edited_Group->check_delete( sprintf( T_('Cannot delete Group &laquo;%s&raquo;'), $edited_Group->dget( 'name' ) ) ) )
				{	// There are restrictions:
					$action = 'view_group';
				}
			}
			break;
	}
}


/**
 * Display page header:
 */
require dirname(__FILE__).'/_menutop.php';


/*
 * Display payload:
 */
if( ! $current_User->check_perm( 'users', 'view', false ) )
{ // User is NOT allowed to view users
	// TODO: move this check upward
	if( isset( $edited_User ) && ( $edited_User->ID != $current_User->ID ) )
	{ // another user requested -> error-note
		Log::display( '', '', T_('You are not allowed to view other users.'), 'error' );
		$action = 'nil';
	}
	else
	{ // display only current user's form
		$edited_User = & $current_User;
		require dirname(__FILE__).'/_users_form.php';
	}
}
else
{ // User is allowed to view users/groups:

	// display appropriate payload:
	switch( $action )
	{
		case 'nil':
			// Display NO payload!
			break;


		case 'delete_user':
			// We need to ask for confirmation:
			$hiddens = array( array( 'user_ID', $edited_User->ID ) );
			$edited_User->confirm_delete(
					sprintf( T_('Delete user &laquo;%s&raquo; [%s]?'), $edited_User->dget( 'fullname' ), $edited_User->dget( 'login' ) ),
					$action, $hiddens );
		case 'new_user':
		case 'view_user':
		case 'edit_user':
			// Display user form:
			require dirname(__FILE__).'/_users_form.php';
			break;


		case 'delete_group':
			// We need to ask for confirmation:
			$hiddens = array( array( 'grp_ID', $edited_Group->ID ) );
			$edited_Group->confirm_delete(
					sprintf( T_('Delete group &laquo;%s&raquo;?'), $edited_Group->dget( 'name' ) ),
					$action, $hiddens );
		case 'new_group':
		case 'edit_group':
		case 'view_group':
			// Display group form:
			require dirname(__FILE__).'/_users_groupform.php';
			break;


		default:
			// Users list:
			// fplanque>> note: we don't want this (potentially very long) list to be displayed again and again)
			if( $current_User->check_perm( 'users', 'view', false ) )
			{ // Display user list:
				// Begin payload block:
				$AdminUI->dispPayloadBegin();
				require dirname(__FILE__).'/_users_list.php';
				// End payload block:
				$AdminUI->dispPayloadEnd();
			}
	}
}

require dirname(__FILE__).'/_footer.php';

/*
 * $Log$
 * Revision 1.98  2005/08/11 19:41:10  fplanque
 * no message
 *
 * Revision 1.97  2005/08/10 21:14:34  blueyed
 * Enhanced $demo_mode (user editing); layout fixes; some function names normalized
 *
 * Revision 1.96  2005/08/01 14:51:46  fplanque
 * Fixed: updating an user was not displaying changes right away (there's still an issue with locale changing though)
 *
 * Revision 1.95  2005/06/06 17:59:38  fplanque
 * user dialog enhancements
 *
 * Revision 1.94  2005/06/03 20:14:38  fplanque
 * started input validation framework
 *
 * Revision 1.93  2005/06/03 15:12:31  fplanque
 * error/info message cleanup
 *
 * Revision 1.92  2005/06/02 18:50:52  fplanque
 * no message
 *
 * Revision 1.91  2005/05/26 19:11:09  fplanque
 * no message
 *
 * Revision 1.90  2005/05/10 18:40:07  fplanque
 * normalizing
 *
 * Revision 1.89  2005/05/09 19:06:54  fplanque
 * bugfixes + global access permission
 *
 * Revision 1.88  2005/05/09 16:09:38  fplanque
 * implemented file manager permissions through Groups
 *
 * Revision 1.87  2005/05/04 18:16:55  fplanque
 * Normalizing
 *
 * Revision 1.86  2005/04/06 13:33:29  fplanque
 * minor changes
 *
 * Revision 1.85  2005/03/22 19:17:31  fplanque
 * cleaned up some nonsense...
 *
 * Revision 1.84  2005/03/22 16:36:01  fplanque
 * refactoring, standardization
 * fixed group creation bug
 *
 * Revision 1.83  2005/03/22 15:08:03  fplanque
 * "standardized" to switch($action)
 *
 * Revision 1.82  2005/03/21 18:57:24  fplanque
 * user management refactoring (towards new evocore coding guidelines)
 * WARNING: some pre-existing bugs have not been fixed here
 *
 */
?>