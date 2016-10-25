<?php
/**
 * This file implements the UI controller for browsing the newsletters.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Check permission:
$current_User->check_perm( 'emails', 'view', true );

load_class( 'email_campaigns/model/_newsletter.class.php', 'Newsletter' );

param_action();

if( param( 'enlt_ID', 'integer', '', true ) )
{	// Load Newsletter object:
	$NewsletterCache = & get_NewsletterCache();
	if( ( $edited_Newsletter = & $NewsletterCache->get_by_ID( $enlt_ID, false ) ) === false )
	{	// We could not find the newsletter to edit:
		unset( $edited_Newsletter );
		forget_param( 'enlt_ID' );
		$action = '';
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Newsletter') ), 'error' );
	}
}

switch( $action )
{
	case 'new':
		// New Newsletter:

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		$edited_Newsletter = new Newsletter();
		break;

	case 'create':
		// Create newsletter:
		$edited_Newsletter = new Newsletter();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'newsletter' );

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		// Load data from request:
		if( $edited_Newsletter->load_from_Request() )
		{	// We could load data from form without errors:

			// Insert in DB:
			$edited_Newsletter->dbinsert();
			$Messages->add( T_('Newsletter has been created.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=newsletters', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

		$action = 'new';
		break;

	case 'update':
		// Update newsletter:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'newsletter' );

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		// Make sure we got an enlt_ID:
		param( 'enlt_ID', 'integer', true );

		// load data from request
		if( $edited_Newsletter->load_from_Request() )
		{	// We could load data from form without errors:

			// Update in DB:
			$edited_Newsletter->dbupdate();
			$Messages->add( T_('Newsletter has been updated.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=newsletters', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

		$action = 'edit';
		break;

	case 'delete':
		// Delete newsletter:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'newsletter' );

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		// Make sure we got an enlt_ID:
		param( 'enlt_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('Newsletter "%s" has been deleted.'), $edited_Newsletter->dget( 'name' ) );
			$edited_Newsletter->dbdelete();
			unset( $edited_Newsletter );
			forget_param( 'enlt_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=newsletters', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// not confirmed, Check for restrictions:
			if( ! $edited_Newsletter->check_delete( sprintf( T_('Cannot delete newsletter "%s"'), $edited_Newsletter->dget( 'name' ) ) ) )
			{	// There are restrictions:
				$action = 'view';
			}
		}
		break;

	case 'activate':
	case 'disactivate':
		// Activate/Disactivate newsletter:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'newsletter' );

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		// Make sure we got an enlt_ID:
		param( 'enlt_ID', 'integer', true );

		$edited_Newsletter->set( 'active', ( $action == 'activate' ? 1 : 0 ) );
		$edited_Newsletter->dbupdate();

		$Messages->add( ( $action == 'activate' ?
			T_('Newsletter has been activated.') :
			T_('Newsletter has been disactivated.') ), 'success' );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $admin_url.'?ctrl=newsletters', 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;
}


$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Emails'), $admin_url.'?ctrl=newsletters' );
$AdminUI->breadcrumbpath_add( T_('Newsletters'), $admin_url.'?ctrl=newsletters' );

// Set an url for manual page:
switch( $action )
{
	case 'new':
	case 'edit':
		$AdminUI->set_page_manual_link( 'editing-an-email-newsletter' );
		break;
	default:
		$AdminUI->set_page_manual_link( 'email-newsletters' );
		break;
}

$AdminUI->set_path( 'email', 'newsletters' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

evo_flush();

switch( $action )
{
	case 'delete':
		// We need to ask for confirmation:
		$edited_Newsletter->confirm_delete(
				sprintf( T_('Delete newsletter "%s"?'), $edited_Newsletter->dget( 'name' ) ),
				'newsletter', $action, get_memorized( 'action' ) );
		/* no break */
	case 'new':
	case 'edit':
		// Display a form of new/edited newsletter:
		$AdminUI->disp_view( 'email_campaigns/views/_newsletters.form.php' );
		break;

	default:
		// Display a list of newsletters:
		$AdminUI->disp_view( 'email_campaigns/views/_newsletters.view.php' );
		break;
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>