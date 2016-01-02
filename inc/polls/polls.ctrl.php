<?php
/**
 * This file implements the polls control.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'polls/model/_poll.class.php', 'Poll' );
load_class( 'polls/model/_poll_option.class.php', 'PollOption' );


// Check minimum permission:
$current_User->check_perm( 'polls', 'create', true );

$AdminUI->set_path( 'site', 'polls' );

param_action( 'list', true );

if( param( 'pqst_ID', 'integer', '', true ) )
{	// Load poll:
	$PollCache = & get_PollCache();
	if( ( $edited_Poll = & $PollCache->get_by_ID( $pqst_ID, false ) ) === false )
	{	// We could not find the poll to edit:
		unset( $edited_Poll );
		forget_param( 'pqst_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Poll') ), 'error' );
		$action = 'nil';
	}
}

if( param( 'popt_ID', 'integer', '', true ) )
{	// Load poll option:
	$PollOptionCache = & get_PollOptionCache();
	if( ( $edited_PollOption = & $PollOptionCache->get_by_ID( $popt_ID, false ) ) === false )
	{	// We could not find the poll option to edit:
		unset( $edited_PollOption );
		forget_param( 'popt_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Poll option') ), 'error' );
		$action = 'nil';
	}
}

switch( $action )
{
	case 'new':
		// Check permission:
		$current_User->check_perm( 'polls', 'create', true );

		$edited_Poll = new Poll();
		break;

	case 'edit':
		// Check permission:
		$current_User->check_perm( 'polls', 'view', true, $edited_Poll );
		break;
 
	case 'create':
		// Create new poll:
		$edited_Poll = new Poll();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'poll' );

		// Check that current user has permission to create polls:
		$current_User->check_perm( 'polls', 'create', true );

		// load data from request
		if( $edited_Poll->load_from_Request() )
		{	// We could load data from form without errors:
			// Insert in DB:
			$edited_Poll->dbinsert();
			$Messages->add( T_('New poll has been created.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=polls', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'new';
		break;

	case 'update':
		// Update poll:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'poll' );

		// Check that current user has permission to edit the poll:
		$current_User->check_perm( 'polls', 'edit', true );

		// Make sure we got an pqst_ID:
		param( 'pqst_ID', 'integer', true );

		// load data from request:
		if( $edited_Poll->load_from_Request() )
		{	// We could load data from form without errors:
			// Update poll in DB:
			$edited_Poll->dbupdate();
			$Messages->add( T_('Poll has been updated.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=polls', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'edit';
		break;

	case 'delete':
		// Delete poll:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'poll' );

		// Check that current user has permission to edit polls:
		$current_User->check_perm( 'polls', 'edit', true, $edited_Poll );

		// Make sure we got an pqst_ID:
		param( 'pqst_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{	// confirmed, Delete from DB:
			$msg = sprintf( T_('Poll "%s" has been deleted.'), '<b>'.$edited_Poll->get_name().'</b>' );
			$edited_Poll->dbdelete();
			unset( $edited_Poll );
			forget_param( 'pqst_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( regenerate_url( 'action', '', '', '&' ), 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	// ---------------- ACTIONS FOR POLL OPTIONS: ---------------- //

	case 'new_option':
		// Check permission:
		$current_User->check_perm( 'polls', 'edit', true, $edited_Poll );

		$edited_PollOption = new PollOption();
		break;

	case 'edit_option':
		// Check permission:
		$current_User->check_perm( 'polls', 'edit', true, $edited_Poll );
		break;
 
	case 'create_option':
		// Create new poll option:
		$edited_PollOption = new PollOption();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'poll' );

		// Check that current user has permission to create polls:
		$current_User->check_perm( 'polls', 'edit', true, $edited_Poll );

		// load data from request
		if( $edited_PollOption->load_from_Request( $edited_Poll->ID ) )
		{	// We could load data from form without errors:
			// Insert in DB:
			$edited_PollOption->dbinsert();
			$Messages->add( T_('New poll option has been created.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=polls&action=edit&pqst_ID='.$edited_Poll->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'new_option';
		break;

	case 'update_option':
		// Update poll option:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'poll' );

		// Check that current user has permission to edit the poll:
		$current_User->check_perm( 'polls', 'edit', true, $edited_Poll );

		// Make sure we got an pqst_ID:
		param( 'popt_ID', 'integer', true );

		// load data from request:
		if( $edited_PollOption->load_from_Request() )
		{	// We could load data from form without errors:
			// Update poll in DB:
			$edited_PollOption->dbupdate();
			$Messages->add( T_('Poll option has been updated.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=polls&action=edit&pqst_ID='.$edited_Poll->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'edit_option';
		break;

	case 'delete_option':
		// Delete poll option:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'poll' );

		// Check that current user has permission to edit polls:
		$current_User->check_perm( 'polls', 'edit', true, $edited_Poll );

		// Make sure we got an pqst_ID:
		param( 'popt_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{	// confirmed, Delete from DB:
			$msg = sprintf( T_('Poll option "%s" has been deleted.'), '<b>'.$edited_PollOption->get_name().'</b>' );
			$edited_PollOption->dbdelete();
			unset( $edited_PollOption );
			forget_param( 'popt_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=polls&action=edit&pqst_ID='.$edited_Poll->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;
}


$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Site'), $admin_url.'?ctrl=dashboard' );
$AdminUI->breadcrumbpath_add( T_('Polls'), $admin_url.'?ctrl=polls' );

if( $action == 'new' || $action == 'edit' )
{
	$AdminUI->set_page_manual_link( 'poll-form' );
}
else
{
	$AdminUI->set_page_manual_link( 'polls-list' );
}

// Set an url for manual page:
switch( $action )
{
	case 'new_option':
	case 'create_option':
	case 'edit_option':
	case 'update_option':
		$AdminUI->set_page_manual_link( 'poll-option-form' );
		break;
	case 'delete_option':
	case 'new':
	case 'create':
	case 'edit':
	case 'update':
		$AdminUI->set_page_manual_link( 'poll-form' );
		break;
	default:
		$AdminUI->set_page_manual_link( 'polls-list' );
		break;
}

// Init JS to autcomplete the user logins
init_autocomplete_login_js( 'rsc_url', $AdminUI->get_template( 'autocomplete_plugin' ) );

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

	case 'delete_option':
		// We need to ask for confirmation:
		$edited_PollOption->confirm_delete(
				sprintf( T_('Delete poll option "%s"?'), '<b>'.$edited_PollOption->get_name().'</b>' ),
				'poll', $action, array_merge( get_memorized( 'action' ), array( 'action' => 'edit' ) ) );
		// NO BREAK
	case 'new':
	case 'edit':
		// Display poll form:
		$AdminUI->disp_view( 'polls/views/_poll.form.php' );
		break;

	case 'delete':
		// We need to ask for confirmation:
		$edited_Poll->confirm_delete(
				sprintf( T_('Delete poll "%s"?'), '<b>'.$edited_Poll->get_name().'</b>' ),
				'poll', $action, get_memorized( 'action' ) );
		// NO BREAK
	case 'list':
		// list polls:
		$AdminUI->disp_view( 'polls/views/_polls.view.php' );
		break;

	case 'new_option':
	case 'edit_option':
		// Display poll option form:
		$AdminUI->disp_view( 'polls/views/_poll_option.form.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>