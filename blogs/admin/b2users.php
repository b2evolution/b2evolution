<?php
/**
 * Groups/Users editing
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
require_once( dirname(__FILE__). '/_header.php' );
$title = T_('User management');

param( 'action', 'string' );
param( 'user', 'int', 0 );
param( 'group', 'int', 0 );

require(dirname(__FILE__). '/_menutop.php');
require(dirname(__FILE__). '/_menutop_end.php');


switch ($action)
{
	case 'useredit': // old-style
	break;

	case 'userupdate':
		/*
		 * Update user:
		 */
		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		param( 'edited_user_ID', 'integer', true );
		$edited_User = & new User( get_userdata( $edited_user_ID ) );

		param( 'edited_user_grp_ID', 'integer', true );
		$edited_user_Group = Group_get_by_ID( $edited_user_grp_ID );
		$edited_User->setGroup( $edited_user_Group );
		// echo 'new group = ';
		// $edited_User->Group->disp('name');

		$edited_User->dbupdate();	// Commit update to the DB

		#header('Location: b2users.php');
		#exit();
		
		// remember to display the forms
		$user = $edited_user_ID;
		
		echo '<div class="panelblock">User updated.</div>';
		break;


	case 'promote':
		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		param( 'prom', 'string', true );
		param( 'id', 'integer', true );

		if( empty($prom) )
		{
			header('Location: b2users.php');
		}

		$user_data = get_userdata( $id );
		$usertopromote_level = get_user_info( 'level', $user_data );

		if( $prom == 'up' )
		{
			$sql = "UPDATE $tableusers SET user_level=user_level+1 WHERE ID = $id";
		}
		elseif( $prom == 'down' )
		{
			$sql = "UPDATE $tableusers SET user_level=user_level-1 WHERE ID = $id";
		}
		$result = mysql_query($sql) or die("Couldn't change $id's level.");

		#header('Location: b2users.php' . ( ( $user != 0 )? '?user='. $user : '') );
		#exit();
		echo '<div class="panelblock">User promoted.</div>';
		break;


	case 'delete':
		/*
		 * Delete user
		 */
		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		param( 'id', 'integer', true );

		$user_data = get_userdata($id);
		$edited_User = & new User( $user_data );

		// Delete from DB:
		$edited_User->dbdelete();

		#header('Location: b2users.php');
		#exit();
		
		echo '<div class="panelblock">User deleted.</div>';
		break;


	case 'groupedit': // old-style
	break;


	case 'groupupdate':
		/*
		 * Update group:
		 */
		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		param( 'edited_grp_ID', 'integer', true );
		$edited_Group = Group_get_by_ID( $edited_grp_ID );

		param( 'edited_grp_name', 'string', true );
		$edited_Group->set( 'name', $edited_grp_name );

		param( 'edited_grp_perm_blogs', 'string', true );
		$edited_Group->set( 'perm_blogs', $edited_grp_perm_blogs );

		param( 'edited_grp_perm_stats', 'string', true );
		$edited_Group->set( 'perm_stats', $edited_grp_perm_stats );

		param( 'edited_grp_perm_spamblacklist', 'string', true );
		$edited_Group->set( 'perm_spamblacklist', $edited_grp_perm_spamblacklist );

		param( 'edited_grp_perm_options', 'string', true );
		$edited_Group->set( 'perm_options', $edited_grp_perm_options );

		param( 'edited_grp_perm_templates', 'int', 0 );
		$edited_Group->set( 'perm_templates', $edited_grp_perm_templates );

		if( $edited_grp_ID != 1 )
		{	// Groups others than #1 can be prevented from editing users
			param( 'edited_grp_perm_users', 'string', true );
			$edited_Group->set( 'perm_users', $edited_grp_perm_users );
		}

		$edited_Group->dbupdate();	// Commit update to the DB

		// remember to display the forms
		$group = $edited_grp_ID;

		#header('Location: b2users.php');
		#exit();
		echo '<div class="panelblock">Group updated.</div>';
		break;


	default:
		#require( dirname(__FILE__). '/_menutop.php');
		#require( dirname(__FILE__). '/_menutop_end.php');

}

if( $current_User->check_perm( 'users', 'view', false ) )
{
	if( ($group != 0) ){
		/*
		 * View group:
		 */
		// Check permission:
		$current_User->check_perm( 'users', 'view', true );
		
		#param( 'grp_ID', 'integer', true );
		$edited_Group = Group_get_by_ID( $group );
		#require(dirname(__FILE__). '/_menutop.php');
		#require(dirname(__FILE__). '/_menutop_end.php');
		require(dirname(__FILE__). '/_users_groupform.php');
	}
		
	/*
	 * View user, if we have a 'user' param
	 */
	if( $user != 0 )
	{
			// Check permission:
			$current_User->check_perm( 'users', 'view', true );
	
			#param( 'user', 'integer', true );
			$edited_User = & new User( get_userdata($user) );
			require(dirname(__FILE__). '/_users_form.php');
	}
}

// Check permission:
if( $current_User->check_perm( 'users', 'view', false ) ){
	// Display user list:
	require( dirname(__FILE__). '/_users_list.php' );
}
require( dirname(__FILE__). '/_footer.php' );
?>