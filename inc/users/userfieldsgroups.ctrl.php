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

// Load Userfield class:
load_class( 'users/model/_userfieldgroup.class.php', 'UserfieldGroup' );

/**
 * @var User
 */
global $current_User;

// Check minimum permission:
$current_User->check_perm( 'users', 'view', true );

// Set options path:
$AdminUI->set_path( 'users', 'usersettings', 'userfields' );

// Get action parameter from request:
param_action();

if( param( 'ufgp_ID', 'integer', '', true) )
{// Load userfield group from cache:
	$UserfieldGroupCache = & get_UserFieldGroupCache();
	if( ($edited_UserfieldGroup = & $UserfieldGroupCache->get_by_ID( $ufgp_ID, false )) === false )
	{	// We could not find the user field to edit:
		unset( $edited_UserfieldGroup );
		forget_param( 'ufgp_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('User field group') ), 'error' );
		$action = 'nil';
	}
}


switch( $action )
{

	case 'new':
		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		if( ! isset($edited_UserfieldGroup) )
		{	// We don't have a model to use, start with blank object:
			$edited_UserfieldGroup = new UserfieldGroup();
		}
		else
		{	// Duplicate object in order no to mess with the cache:
			$edited_UserfieldGroup = duplicate( $edited_UserfieldGroup ); // PHP4/5 abstraction
			$edited_UserfieldGroup->ID = 0;
		}
		break;

	case 'edit':
		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		// Make sure we got an ufgp_ID:
		param( 'ufgp_ID', 'integer', true );
		break;

	case 'create': // Record new UserfieldGroup
	case 'create_new': // Record UserfieldGroup and create new
	case 'create_copy': // Record UserfieldGroup and create similar
		// Insert new user field group...:
		$edited_UserfieldGroup = new UserfieldGroup();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'userfieldgroup' );

		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		// load data from request
		if( $edited_UserfieldGroup->load_from_Request() )
		{	// We could load data from form without errors:

			// Insert in DB:
			$DB->begin();
			// because of manual assigning ID,
			// member function Userfield::dbexists() is overloaded for proper functionality
			$q = $edited_UserfieldGroup->dbexists();
			if($q)
			{	// We have a duplicate entry:

				param_error( 'ufgp_ID',
					sprintf( T_('This user field group already exists. Do you want to <a %s>edit the existing user field group</a>?'),
						'href="?ctrl=userfieldsgroups&amp;action=edit&amp;ufgp_ID='.$q.'"' ) );
			}
			else
			{
				$edited_UserfieldGroup->dbinsert();
				$Messages->add( T_('New User field group created.'), 'success' );
			}
			$DB->commit();

			if( empty($q) )
			{	// What next?
			switch( $action )
				{
					case 'create_copy':
						// Redirect so that a reload doesn't write to the DB twice:
						header_redirect( '?ctrl=userfieldsgroups&action=new&ufgp_ID='.$edited_UserfieldGroup->ID, 303 ); // Will EXIT
						// We have EXITed already at this point!!
						break;
					case 'create_new':
						// Redirect so that a reload doesn't write to the DB twice:
						header_redirect( '?ctrl=userfieldsgroups&action=new', 303 ); // Will EXIT
						// We have EXITed already at this point!!
						break;
					case 'create':
						// Redirect so that a reload doesn't write to the DB twice:
						header_redirect( '?ctrl=userfields', 303 ); // Will EXIT
						// We have EXITed already at this point!!
						break;
				}
			}
		}
		break;

	case 'update':
		// Edit user field form...:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'userfieldgroup' );

		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		// Make sure we got an ufgp_ID:
		param( 'ufgp_ID', 'integer', true );

		// load data from request
		if( $edited_UserfieldGroup->load_from_Request() )
		{	// We could load data from form without errors:

			// Update in DB:
			$DB->begin();

			$edited_UserfieldGroup->dbupdate();
			$Messages->add( T_('User field group updated.'), 'success' );

			$DB->commit();

			header_redirect( '?ctrl=userfields', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'delete':
		// Delete user field:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'userfieldgroup' );

		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		// Make sure we got an ufgp_ID:
		param( 'ufgp_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('User field group &laquo;%s&raquo; deleted.'), $edited_UserfieldGroup->dget('name') );
			$edited_UserfieldGroup->dbdelete();
			unset( $edited_UserfieldGroup );
			forget_param( 'ufgp_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=userfields', 303 ); // Will EXIT
			// We have EXITed already at this point!!

		}
		else
		{	// not confirmed, Check for restrictions:
			if( ! $edited_UserfieldGroup->check_delete( sprintf( T_('Cannot delete user field group &laquo;%s&raquo;'), $edited_UserfieldGroup->dget('name') ) ) )
			{	// There are restrictions:
				$action = 'view';
			}
		}
		break;

}

$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Users'), '?ctrl=users' );
$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=usersettings' );
$AdminUI->breadcrumbpath_add( T_('User fields configuration'), '?ctrl=userfields' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

$AdminUI->disp_payload_begin();

/**
 * Display payload:
 */
switch( $action )
{
	case 'nil':
		// Do nothing
		break;


	case 'delete':
		// We need to ask for confirmation:
		$edited_UserfieldGroup->confirm_delete(
				sprintf( T_('Delete user field &laquo;%s&raquo;?'), $edited_UserfieldGroup->dget('name') ),
				'userfieldgroup', $action, get_memorized( 'action' ) );
		/* no break */
	case 'new':
	case 'create':
	case 'create_new':
	case 'create_copy':
	case 'edit':
	case 'update':	// we return in this state after a validation error
		$AdminUI->disp_view( 'users/views/_userfieldsgroup.form.php' );
		break;


	default:
		// No specific request, list all user fields:
		// Cleanup context:
		forget_param( 'ufgp_ID' );
		// Display user fields list:
		$AdminUI->disp_view( 'users/views/_userfields.view.php' );
		break;

}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>