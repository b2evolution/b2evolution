<?php
/**
 * Groups/Users editing
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
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
require( dirname(__FILE__).'/_menutop.php' );
require( dirname(__FILE__).'/_menutop_end.php' );


$errors = array();

// Check permission:
if( !$current_User->check_perm( 'users', 'edit', false ) )
{
	errors_add( T_('You have no permission to edit!') );
}
else
switch ($action)
{
	case 'newuser':
		param( 'template', 'integer', -1 );
		
		if( $template > -1 )
		{ // we use a template
			$edited_User = & new User( get_userdata($template) );
			$edited_User->set('ID', 0);
		}
		else
		{ // we use an empty user
			$edited_User = & new User();
		}
		
		break;


	case 'newgroup':
		param( 'template', 'integer', -1 );
		
		if( $template > -1 )
		{ // we use a template
			$edited_Group = $GroupCache->get_by_ID( $template );
			$edited_Group->set('ID', 0);
		}
		else
		{ // we use an empty user
			$edited_Group = & new Group();
		}
		
		break;


	case 'userupdate':
		param( 'edited_user_ID', 'integer', true );
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
		
		$query = "SELECT ID FROM $tableusers WHERE user_login = '$edited_user_login' AND ID != $edited_user_ID";
		$q = $DB->get_var( $query );
		
		if( $q !== NULL )
		{
			errors_add( sprintf( T_('The login already exists. Please <a %s>edit this login</a> instead of overwriting it this way.'), 'href="?user='.$q.'"' ));
		}
		
		if( $edited_user_ID == 0 )
		{ // we create a new user
			$edited_User = & new User();
			$edited_User->set_datecreated( $localtimenow );
		}
		else
		{
			$edited_User = & new User( get_userdata( $edited_user_ID ) );
		}
		
		$edited_User->set( 'login', $edited_user_login );
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
		if( $edited_user_pass1 != '' || $edited_user_ID == 0 )
		{ // update password, explicit for new users
			if( $edited_user_pass1 != $edited_user_pass2 )
			{
				errors_add( T_('You typed two different passwords.') );
			}
			else
			{
				if( strlen($edited_user_pass2) < $Settings->get('user_minpwdlen') )
				{
					errors_add( sprintf( T_('The mimimum password length is %d characters.'), $Settings->get('user_minpwdlen')) );
				}
				else
				{ // set password
					$edited_User->set( 'pass', md5( $edited_user_pass2 ) );
				}
			}
		}
		
		param( 'edited_user_grp_ID', 'integer', true );
		if( $edited_user_grp_ID > 0 )
		{
			$edited_user_Group = $GroupCache->get_by_ID( $edited_user_grp_ID );
		}
		else $edited_user_Group = & new Group();
		
		$edited_User->setGroup( $edited_user_Group );
		// echo 'new group = ';
		// $edited_User->Group->disp('name');
		
		if( count($errors) )
		{
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
		}

		break;


	case 'promote':
		param( 'prom', 'string', true );
		param( 'id', 'integer', true );

		$user_data = get_userdata( $id );
		$usertopromote_level = get_user_info( 'level', $user_data );
		
		if( ! in_array($prom, array('up', 'down'))
				|| ($prom == 'up' && $usertopromote_level > 9)
				|| ($prom == 'down' && $usertopromote_level < 1)
			)
		{
			echo '<div class="panelinfo"><p class="error">' . T_('Invalid promotion.') . '</p></div>';
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
				echo '<div class="panelinfo"><p>'.T_('User level changed.');
			else
				echo '<div class="panelinfo"><p class="error">' . sprintf( T_('Couldn\'t change %s\'s level.'), $user_data['user_login'] );
			echo '</p></div>';
			
			// reset cache
			$cache_userdata[ $id ] = '';
			
		}
		break;


	case 'deleteuser':
		/*
		 * Delete user
		 */
		param( 'id', 'integer', true );
		param( 'confirm', 'integer', 0 );
			
		if( $id == $current_User->ID )
		{
			errors_add( T_('You can\'t delete yourself!') );
		}
		if( $id == 1 )
		{
			errors_add( T_('You can\'t delete User #1!') );
		}
		
		if( $errors )
		{
			break;
		}

		$user_data = get_userdata( $id );
		$deleted_User = & new User( $user_data );

		if( !$confirm )
		{?>
		<div class="panelinfo">
			<h3><?php printf( T_('Delete User %s?'), $deleted_User->get( 'firstname' ).' '.$deleted_User->get( 'lastname' ).' ['.$deleted_User->get( 'login' ).']' )?></h3>

			<p><?php echo T_('Warning').': '.T_('deleting an user also deletes all posts made by this user.') ?></p>

			<p><?php echo T_('THIS CANNOT BE UNDONE!') ?></p>

			<p>
				<form action="b2users.php" method="get" class="inline">
					<input type="hidden" name="action" value="deleteuser" />
					<input type="hidden" name="id" value="<?php $deleted_User->ID() ?>" />
					<input type="hidden" name="confirm" value="1" />
					
					<input type="submit" value="<?php echo T_('I am sure!') ?>" class="search" />
				</form>
				<form action="b2users.php" method="get" class="inline">
					<input type="submit" value="<?php echo T_('CANCEL') ?>" class="search" />
				</form>
			</p>

		</div>
	<?php
		}
		else
		{ // confirmed
			// Delete from DB:
			echo '<div class="panelinfo"><h3>'.T_('Deleting User...').'</h3>';
			$deleted_User->dbdelete( true );
			echo '</div>';
		}
			
		break;


	case 'deletegroup':
		/*
		 * Delete group
		 */
		param( 'id', 'integer', true );
		param( 'confirm', 'integer', 0 );
			
		if( $id == 1 )
		{
			errors_add( T_('You can\'t delete Group #1!') );
		}
		if( $id == $Settings->get('newusers_grp_ID' ) )
		{
			errors_add( T_('You can\'t delete the default group for new users!') );
		}
		
		if( $errors )
		{
			break;
		}

		$del_Group = $GroupCache->get_by_ID( $id );

		if( !$confirm )
		{?>
		<div class="panelinfo">
			<h3><?php printf( T_('Delete Group [%s]?'), $del_Group->get( 'name' ) )?></h3>

			<p><?php echo T_('THIS CANNOT BE UNDONE!') ?></p>

			<p>
				<form action="b2users.php" method="get" class="inline">
					<input type="hidden" name="action" value="deletegroup" />
					<input type="hidden" name="id" value="<?php $del_Group->ID() ?>" />
					<input type="hidden" name="confirm" value="1" />
					
					<input type="submit" value="<?php echo T_('I am sure!') ?>" class="search" />
				</form>
				<form action="b2users.php" method="get" class="inline">
					<input type="submit" value="<?php echo T_('CANCEL') ?>" class="search" />
				</form>
			</p>

		</div>
	<?php
		}
		else
		{ // confirmed
			// Delete from DB:
			$del_Group->dbdelete( true );
			echo '<div class="panelinfo"><p>'.T_('Group deleted...').'</p></div>';
		}
		
		break;


	case 'groupupdate':
		param( 'edited_grp_ID', 'integer', true );
		param( 'edited_grp_oldname', 'string', true );
		param( 'edited_grp_name', 'string', true );
		
		// check if the group name already exists for another group
		$query = "SELECT grp_ID FROM $tablegroups WHERE grp_name = '$edited_grp_name' AND grp_ID != $edited_grp_ID";
		$q = $DB->get_var( $query );
		
		if( $q !== NULL )
		{
			errors_add( sprintf( T_('The group name already exists. Please <a %s>edit this group</a> instead of overwriting it this way.'), 'href="?group='.$q.'"' ));
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

		if( count($errors) )
		{
			break;
		}
		
		if( $edited_grp_ID == 0 )
		{	// Insert into the DB:
			$edited_Group->dbinsert();
		}
		else
		{ // Commit update to the DB:
			$edited_Group->dbupdate();
		}
		// Commit changes in cache:
		$GroupCache->add( $edited_Group );

		// remember to display the forms
		$group = $edited_grp_ID;

		echo '<div class="panelinfo"><p>' . T_('Group updated.') . '</p></div>';
		break;
}


if( count($errors) )
{
	echo '<div class="panelinfo">';
	errors_display(
		(isset( $edited_user_ID ) ?
			(($edited_user_ID == 0) ? T_('The user was not created:') : T_('The user was not updated:'))
		: (isset( $edited_grp_ID) ?
			(($edited_grp_ID == 0) ? T_('The group was not created:') : T_('The group was not updated:'))
			: '')
		), '');
	echo '</div>';
}


if( $current_User->check_perm( 'users', 'view', false ) )
{
	// get the userlist
	$request = "SELECT $tableusers.*, grp_ID, grp_name
							FROM $tableusers RIGHT JOIN $tablegroups ON user_grp_ID = grp_ID
							ORDER BY grp_name, user_login";
	$userlist = $DB->get_results( $request, ARRAY_A );


	if( ($group != 0) || in_array($action, array( 'newgroup', 'groupupdate' ))  )
	{ // display group form
		if( !isset($edited_Group) )
		{
			$edited_Group = $GroupCache->get_by_ID( $group );
		}
		require(dirname(__FILE__). '/_users_groupform.php');
	}


	if( $user != 0 || in_array($action, array( 'newuser', 'userupdate' )) )
	{ // display user form
		if( !isset($edited_User) )
		{
			$edited_User = & new User( get_userdata($user) );
		}
		
		require(dirname(__FILE__). '/_users_form.php');
	}


	// Display user list:
	require( dirname(__FILE__). '/_users_list.php' );
}

require( dirname(__FILE__). '/_footer.php' );
?>