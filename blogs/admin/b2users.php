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
$admin_tab = 'users';
$admin_pagetitle = T_('User management');

param( 'action', 'string' );
param( 'user', 'integer', 0 );
param( 'group', 'integer', 0 );

// show the top menu
require(dirname(__FILE__). '/_menutop.php');
require(dirname(__FILE__). '/_menutop_end.php');


switch ($action)
{
	case 'userupdate':
		/*
		 * Update user:
		 */
		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		param( 'edited_user_ID', 'integer', true );
		$edited_User = & new User( get_userdata( $edited_user_ID ) );

		param( 'edited_user_grp_ID', 'integer', true );
		$edited_user_Group = $GroupCache->get_by_ID( $edited_user_grp_ID );
		$edited_User->setGroup( $edited_user_Group );
		// echo 'new group = ';
		// $edited_User->Group->disp('name');

		// Commit update to the DB:
		$edited_User->dbupdate();	
		// Commit changes in cache:
		// not ready: $UserCache->add( $edited_Group );
		unset( $cache_userdata ); // until better

		// remember, to display the forms
		$user = $edited_user_ID;
		
		echo '<div class="panelinfo">' . T_('User updated.') . '</div>';
		break;


	case 'promote':
		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		param( 'prom', 'string', true );
		param( 'id', 'integer', true );

		$user_data = get_userdata( $id );
		$usertopromote_level = get_user_info( 'level', $user_data );
		
		if( ! in_array($prom, array('up', 'down'))
				|| ($prom == 'up' && $usertopromote_level > 9)
				|| ($prom == 'down' && $usertopromote_level < 1)
			)
		{
			echo '<div class="panelinfo"><span class="error">' . T_('Invalid promotion.') . '</span></div>';
		}
		else
		{
	
			if( $prom == 'up' )
			{
				$sql = "UPDATE $tableusers SET user_level=user_level+1 WHERE ID = $id";
			}
			elseif( $prom == 'down' )
			{
				$sql = "UPDATE $tableusers SET user_level=user_level-1 WHERE ID = $id";
			}
			$result = $DB->query( $sql );
			
			if( $result )
				echo '<div class="panelinfo">User promoted.</div>';
			else
				echo '<div class="panelinfo"><span class="error">' . sprintf(T_('Couldn\'t change %d\'s level.', $id));
			
		}
		break;


	case 'delete':
		/*
		 * Delete user
		 */
		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		param( 'id', 'integer', true );

		if( $id == $current_User->ID )
			die( 'You can\'t delete yourself!' );

		if( $id == 1 )
			die( 'You can\'t delete Uszr #1!' );

		$user_data = get_userdata($id);
		$edited_User = & new User( $user_data );

		// Delete from DB:
		echo '<div class="panelinfo">
						<h3>Deleting User...</h3>';
		$edited_User->dbdelete( true );
		echo '</div>';

		break;


	case 'groupupdate':
		/*
		 * Update group:
		 */
		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		param( 'edited_grp_ID', 'integer', true );
		$edited_Group = $GroupCache->get_by_ID( $edited_grp_ID );

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

		param( 'edited_grp_perm_templates', 'integer', 0 );
		$edited_Group->set( 'perm_templates', $edited_grp_perm_templates );

		if( $edited_grp_ID != 1 )
		{	// Groups others than #1 can be prevented from editing users
			param( 'edited_grp_perm_users', 'string', true );
			$edited_Group->set( 'perm_users', $edited_grp_perm_users );
		}

		// Commit update to the DB:
		$edited_Group->dbupdate();	
		// Commit changes in cache:
		$GroupCache->add( $edited_Group );

		// remember to display the forms
		$group = $edited_grp_ID;

		echo '<div class="panelinfo">' . T_('Group updated.') . '</div>';
		break;

}

if( $current_User->check_perm( 'users', 'view', false ) )
{
	if( ($group != 0) )
	{ // view group
		// Check permission:
		$current_User->check_perm( 'users', 'view', true );
		
		$edited_Group = $GroupCache->get_by_ID( $group );
		require(dirname(__FILE__). '/_users_groupform.php');
	}
		
	if( $user != 0 )
	{ // view user
		// Check permission:
		$current_User->check_perm( 'users', 'view', true );

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