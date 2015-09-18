<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI_general
 */
global $AdminUI;

$AdminUI->set_path( 'users', 'groups' );

param_action('list');

param( 'grp_ID', 'integer', NULL );		// Note: should NOT be memorized:    -- " --

/**
 * @global boolean true, if user is only allowed to view group
 */
$user_view_group_only = ! $current_User->check_perm( 'users', 'edit' );

if( $user_view_group_only )
{ // User has no permissions to view: he can only edit his profile

	if( isset($grp_ID) )
	{ // User is trying to edit something he should not: add error message (Should be prevented by UI)
		$Messages->add( T_('You have no permission to edit groups!'), 'warning' );
	}

	// Make sure the user only edits himself:

	//$grp_ID = NULL;
	if( ! in_array( $action, array( 'new', 'view') ) )
	{
		$action = 'view';
	}
}

/*
 * Load editable objects and set $action (while checking permissions)
 */

$UserCache  = & get_UserCache();
$GroupCache = & get_GroupCache();

if( $grp_ID !== NULL )
{ // Group selected
	if( $action == 'update' && $grp_ID == 0 )
	{ // New Group:
		$edited_Group = new Group();
	}
	elseif( ($edited_Group = & $GroupCache->get_by_ID( $grp_ID, false )) === false )
	{ // We could not find the Group to edit:
		unset( $edited_Group );
		forget_param( 'grp_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Group') ), 'error' );
		$action = 'list';
	}
	elseif( $action == 'list' )
	{ // 'list' is default, $grp_ID given
		if( $current_User->check_perm( 'users', 'edit' ) )
		{
			$action = 'edit';
		}
		else
		{
			$action = 'view';
		}
	}

	if( $action != 'view' && $action != 'list' )
	{ // check edit permissions
		if( !$current_User->check_perm( 'users', 'edit' ) )
		{
			$Messages->add( T_('You have no permission to edit groups!'), 'error' );
			$action = 'view';
		}
		elseif( $demo_mode && ( $edited_Group->ID <= 4 ) && ( $edited_Group->ID > 0 ) )
		{ // Demo mode restrictions: groups created by install process cannot be edited
			$Messages->add( T_('You cannot edit the default groups in demo mode!'), 'error' );
			$action = 'view';
		}
	}
}

switch ( $action )
{
	case 'new':
		// We want to create a new group:
		if( isset( $edited_Group ) )
		{ // We want to use a template
			$edited_Group->get_GroupSettings(); // Load all group settings
			$new_Group = $edited_Group; // Copy !
			$new_Group->set( 'ID', 0 );
			$edited_Group = & $new_Group;
		}
		else
		{ // We use an empty group:
			$edited_Group = new Group();
		}

		break;


	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'group' );

		if( empty($edited_Group) || !is_object($edited_Group) )
		{
			$Messages->add( 'No group set!' ); // Needs no translation, should be prevented by UI.
			$action = 'list';
			break;
		}

		if( $edited_Group->load_from_Request() )
		{

			// check if the group name already exists for another group
			$query = 'SELECT grp_ID FROM T_groups
			           WHERE grp_name = '.$DB->quote($edited_grp_name).'
			             AND grp_ID != '.$edited_Group->ID;
			if( $q = $DB->get_var( $query ) )
			{
				param_error( 'edited_grp_name',
					sprintf( T_('This group name already exists! Do you want to <a %s>edit the existing group</a>?'),
						'href="?ctrl=groups&amp;action=edit&amp;grp_ID='.$q.'"' ) );
			}
		}

		if( $Messages->has_errors() )
		{	// We have found validation errors:
			$action = 'edit';
			break;
		}

		if( $edited_Group->ID == 0 )
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

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( '?ctrl=groups', 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;


	case 'delete':
		/*
		 * Delete group
		 */
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'group' );

		if( !isset($edited_Group) )
		{
			debug_die( 'no Group set' );
		}

		if( $edited_Group->ID == 1 )
		{
			$Messages->add( T_('You can\'t delete Group #1!'), 'error' );
			$action = 'view';
			break;
		}
		if( $edited_Group->ID == $Settings->get('newusers_grp_ID' ) )
		{
			$Messages->add( T_('You can\'t delete the default group for new users!'), 'error' );
			$action = 'view';
			break;
		}

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('Group &laquo;%s&raquo; deleted.'), $edited_Group->dget( 'name' ) );
			$edited_Group->dbdelete();
			unset($edited_Group);
			forget_param('grp_ID');
			$Messages->add( $msg, 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=groups', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// not confirmed, Check for restrictions:
			memorize_param( 'grp_ID', 'integer', true );
			if( ! $edited_Group->check_delete( sprintf( T_('Cannot delete Group &laquo;%s&raquo;'), $edited_Group->dget( 'name' ) ) ) )
			{	// There are restrictions:
				$action = 'view';
			}
		}
		break;
}


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Users'), '?ctrl=users' );
$AdminUI->breadcrumbpath_add( T_('Groups'), '?ctrl=groups' );
if( !empty( $edited_Group ) )
{
	if( $edited_Group->ID > 0 )
	{	// Edit group
		$AdminUI->breadcrumbpath_add( $edited_Group->dget('name'), '?ctrl=groups&amp;action=edit&amp;grp_ID='.$edited_Group->ID );
	}
	else
	{	// New group
		$AdminUI->breadcrumbpath_add( $edited_Group->dget('name'), '?ctrl=groups&amp;action=new' );
	}
}
if( $action == 'list' && $current_User->check_perm( 'users', 'edit', false ) )
{ // Include to edit group level
	require_js( 'jquery/jquery.jeditable.js', 'rsc_url' );
}

// Set an url for manual page:
switch( $action )
{
	case 'new':
	case 'edit':
		$AdminUI->set_page_manual_link( 'editing-user-groups' );
		break;
	default:
		$AdminUI->set_page_manual_link( 'user-groups' );
		break;
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
switch( $action )
{
	case 'new':
	case 'edit':
		$AdminUI->disp_view( 'users/views/_group.form.php' );
		break;
	case 'nil':
		// Do nothing
		break;
	case 'delete':
			// We need to ask for confirmation:
			$edited_Group->confirm_delete(
					sprintf( T_('Delete group &laquo;%s&raquo;?'), $edited_Group->dget( 'name' ) ),
					'group', $action, get_memorized( 'action' ) );
	default:
		$AdminUI->disp_view( 'users/views/_group.view.php' );
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>