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
	case 'newtemplate':
		$edited_User = & new User();
		
		$query = "SELECT MAX(ID) FROM $tableusers";
		#$edited_User->set('ID', $DB->get_var( $query ) + 1);
	
		break;


	case 'userupdate':
		/*
		 * Update user:
		 */
		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );
		
		$errors = array();
		
		param( 'edited_user_ID', 'integer', true );
		// remember, to display the form; we take care for new created users later
		$user = $edited_user_ID;
		param( 'edited_user_oldlogin', 'string', true );
		param( 'edited_user_login', 'string', true );
		
		if( empty($edited_user_login) )
		{
			errors_add( T_('You must provide an unique Login!') );
		}
		
		param( 'edited_user_level', 'integer', true );
		if( $edited_user_level < 0 || $edited_user_level > 10 )
		{
			errors_add( sprintf( T_('User level must be between %d and %d.'), 0, 10 ) );
		}
		
		if( $edited_user_oldlogin != $edited_user_login
				|| $edited_user_ID == 0)
		{ // we create a new user
			#echo 'Login changed!';
			
			$query = "SELECT ID FROM $tableusers WHERE user_login = '$edited_user_login'";
			$q = $DB->get_var( $query );
			
			if( $q !== NULL )
			{
				errors_add( sprintf( T_('You renamed the Login to some already existing. Please <a href="%s">edit this login</a> instead of overwriting it this way.'), '?user='.$q ));
			}
			
			$edited_User = & new User();
			$edited_User->set( 'login', $edited_user_login );
			$edited_User->set_datecreated( time() );
		}
		else
		{
			$edited_User = & new User( get_userdata( $edited_user_ID ) );
		}
		

		param( 'edited_user_firstname', 'string', true );
		$edited_User->set( 'firstname', $edited_user_firstname );
		param( 'edited_user_lastname', 'string', true );
		$edited_User->set( 'lastname', $edited_user_lastname );
		param( 'edited_user_nickname', 'string', true );
		$edited_User->set( 'nickname', $edited_user_nickname );
		param( 'edited_user_idmode', 'string', true );
		$edited_User->set( 'idmode', $edited_user_idmode );
		param( 'edited_user_locale', 'string', true );
		$edited_User->set( 'locale', $edited_user_locale );
		param( 'edited_user_email', 'string', true );
		$edited_User->set( 'email', $edited_user_email );
		param( 'edited_user_url', 'string', true );
		$edited_User->set( 'url', $edited_user_url );
		param( 'edited_user_icq', 'string', true );
		$edited_User->set( 'icq', $edited_user_icq );
		param( 'edited_user_aim', 'string', true );
		$edited_User->set( 'aim', $edited_user_aim );
		param( 'edited_user_msn', 'string', true );
		$edited_User->set( 'msn', $edited_user_msn );
		param( 'edited_user_yim', 'string', true );
		$edited_User->set( 'yim', $edited_user_yim );
		param( 'edited_user_notify', 'integer', 0 );
		$edited_User->set( 'notify', $edited_user_notify );
		$edited_User->set( 'level', $edited_user_level );
		
		param( 'edited_user_pass1', 'string', true );
		param( 'edited_user_pass2', 'string', true );
		if( $edited_user_pass1 != '' )
		{
			if( $edited_user_pass1 != $edited_user_pass2 )
			{
				errors_add( T_('you typed two different passwords. Go back to correct that.') );
			}
			else
			{
				$edited_User->set( 'pass', md5( $edited_user_pass2 ) );
			}
		}
		
		param( 'edited_user_grp_ID', 'integer', true );
		$edited_user_Group = $GroupCache->get_by_ID( $edited_user_grp_ID );
		$edited_User->setGroup( $edited_user_Group );
		// echo 'new group = ';
		// $edited_User->Group->disp('name');
		
		if( count($errors) )
		{
			echo '<div class="panelinfo">';
			errors_display('', '');
			echo '</div>';
			break;	
		}
		
		if( $edited_User->get('ID') != 0 )
		{	// Commit update to the DB:
			$edited_User->dbupdate();	
			echo '<div class="panelinfo"><p>' . T_('User updated.') . '</p></div>';
			// Commit changes in cache:
			// not ready: $UserCache->add( $edited_Group );
			unset( $cache_userdata ); // until better
		}
		else
		{ // Insert user into DB
			$edited_User->dbinsert();
			echo '<div class="panelinfo"><p>' . T_('New user created.') . '</p></div>';
			$user = $edited_User->get('ID');
		}

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
			die( 'You can\'t delete User #1!' );

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
		
	if( $user != 0 || $action == 'newtemplate' )
	{ // view user
		// Check permission:
		$current_User->check_perm( 'users', 'view', true );

		if( $action != 'newtemplate' )
		{
			$edited_User = & new User( get_userdata($user) );
		}
		
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