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

/**
 * @var AdminUI_general
 */
global $AdminUI;

$AdminUI->set_path( 'email', 'newletters' );

// Check permission:
$current_User->check_perm( 'emails', 'view', true );

load_class( 'email_campaigns/model/_newsletter.class.php', 'Newsletter' );
load_funcs( 'email_campaigns/model/_emailcampaign.funcs.php' );

param_action();

$tab = param( 'tab', 'string', 'general', true );

if( $tab == 'automations' )
{	// Check other permission for automations:
	$current_User->check_perm( 'options', 'view', true );
}

if( param( 'enlt_ID', 'integer', '', true ) )
{	// Load Newsletter object:
	$NewsletterCache = & get_NewsletterCache();
	if( ( $edited_Newsletter = & $NewsletterCache->get_by_ID( $enlt_ID, false ) ) === false )
	{	// We could not find the newsletter to edit:
		unset( $edited_Newsletter );
		forget_param( 'enlt_ID' );
		$action = '';
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('List') ), 'error' );
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
			$Messages->add( T_('List has been created.'), 'success' );

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
			$Messages->add( T_('List has been updated.'), 'success' );

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
			$msg = sprintf( T_('List "%s" has been deleted.'), $edited_Newsletter->dget( 'name' ) );
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
			if( ! $edited_Newsletter->check_delete( sprintf( T_('Cannot delete list "%s"'), $edited_Newsletter->dget( 'name' ) ) ) )
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
			T_('List has been activated.') :
			T_('List has been disactivated.') ), 'success' );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $admin_url.'?ctrl=newsletters', 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'enable':
	case 'disable':
		// Enable/Disable newsletter by default for new registered users:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'newsletter' );

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		// Make sure we got an enlt_ID:
		param( 'enlt_ID', 'integer', true );

		$def_newsletters = ( $Settings->get( 'def_newsletters' ) == '' ? array() : explode( ',', $Settings->get( 'def_newsletters' ) ) );
		$enlt_index = array_search( $edited_Newsletter->ID, $def_newsletters );

		$update_def_newsletters = false;
		if( $action == 'enable' && $enlt_index === false )
		{	// Enable newsletter:
			$def_newsletters[] = $edited_Newsletter->ID;
			$update_def_newsletters = true;
		}
		elseif( $action == 'disable' && $enlt_index !== false )
		{	// Disable newsletter:
			unset( $def_newsletters[ $enlt_index ] );
			$update_def_newsletters = true;
		}

		if( $update_def_newsletters )
		{	// Update default setting for newsletters:
			$Settings->set( 'def_newsletters', trim( implode( ',', $def_newsletters ), ',' ) );
			$Settings->dbupdate();

			$Messages->add( sprintf( ( $action == 'enable' ?
				T_('New users will be automatically subscribed to list: %s') :
				T_('New users will no longer be automatically subscribed to list: %s') ),
				'"'.$edited_Newsletter->get( 'name' ).'"' ), 'success' );
		}

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $admin_url.'?ctrl=newsletters', 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;
}


$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Emails'), $admin_url.'?ctrl=newsletters' );
$AdminUI->breadcrumbpath_add( T_('Lists'), $admin_url.'?ctrl=newsletters' );

$AdminUI->display_breadcrumbpath_init( false );

if( ! empty( $edited_Newsletter ) )
{
	$AdminUI->display_breadcrumbpath_add( T_('Lists'), $admin_url.'?ctrl=newsletters' );
	if( $edited_Newsletter->ID > 0 )
	{	// Edit newsletter
		$AdminUI->breadcrumbpath_add( $edited_Newsletter->dget( 'name' ), '?ctrl=newsletters&amp;action=edit&amp;enlt_ID='.$edited_Newsletter->ID );
		$AdminUI->display_breadcrumbpath_add( $edited_Newsletter->dget( 'name' ) );
	}
	else
	{	// New newsletter
		$AdminUI->breadcrumbpath_add( $edited_Newsletter->dget( 'name' ), '?ctrl=newsletters&amp;action=new' );
		$AdminUI->display_breadcrumbpath_add( T_('New list') );
	}
}
else
{
	$AdminUI->display_breadcrumbpath_add( T_('Lists') );
}

// Set an url for manual page:
switch( $action )
{
	case 'new':
	case 'edit':
		if( $edited_Newsletter->ID > 0 )
		{ // Add menu level 3 entries:
			$AdminUI->add_menu_entries( array( 'email', 'newsletters' ), array(
					'general' => array(
						'text' => T_('General'),
						'href' => $admin_url.'?ctrl=newsletters&amp;action=edit&amp;tab=general&amp;enlt_ID='.$edited_Newsletter->ID ),
					'campaigns' => array(
						'text' => T_('Campaigns'),
						'href' => $admin_url.'?ctrl=newsletters&amp;action=edit&amp;tab=campaigns&amp;enlt_ID='.$edited_Newsletter->ID ),
					'subscribers' => array(
						'text' => T_('Subscribers'),
						'href' => $admin_url.'?ctrl=newsletters&amp;action=edit&amp;tab=subscribers&amp;enlt_ID='.$edited_Newsletter->ID )
				) );
			if( $current_User->check_perm( 'options', 'view' ) )
			{	// If current user has a permissions to view options:
				$AdminUI->add_menu_entries( array( 'email', 'newsletters' ), array(
						'automations' => array(
							'text' => T_('Automations'),
							'href' => $admin_url.'?ctrl=newsletters&amp;action=edit&amp;tab=automations&amp;enlt_ID='.$edited_Newsletter->ID ),
					), 'campaigns' );
			}
		}

		switch( $tab )
		{
			case 'campaigns':
				$AdminUI->set_page_manual_link( 'email-list-campaigns' );
				$AdminUI->set_path( 'email', 'newsletters', 'campaigns' );
				break;

			case 'automations':
				$AdminUI->set_page_manual_link( 'email-list-automations' );
				$AdminUI->set_path( 'email', 'newsletters', 'automations' );
				break;

			case 'subscribers':
				// Initialize date picker for _newsletters_subscribers.view.php
				init_datepicker_js();
				// Initialize user tag input
				init_tokeninput_js();
				$AdminUI->set_page_manual_link( 'email-list-subscribers' );
				$AdminUI->set_path( 'email', 'newsletters', 'subscribers' );
				break;

			default:
			case 'general':
				$AdminUI->set_page_manual_link( 'editing-an-email-list' );
				$AdminUI->set_path( 'email', 'newsletters', 'general' );
		}
		break;

	default:
		$AdminUI->set_page_manual_link( 'email-lists' );
		$AdminUI->set_path( 'email', 'newsletters' );
		break;
}

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
				sprintf( T_('Delete list "%s"?'), $edited_Newsletter->dget( 'name' ) ),
				'newsletter', $action, get_memorized( 'action' ) );
		/* no break */
	case 'new':
	case 'edit':
		memorize_param( 'action', 'string', '' );

		switch( $tab )
		{
			case 'campaigns':
				$AdminUI->disp_view( 'email_campaigns/views/_newsletters_campaign.view.php' );
				break;

			case 'automations':
				load_funcs( 'automations/model/_automation.funcs.php' );
				// Display automations tied to this Newsletter:
				automation_results_block( array(
						'enlt_ID'               => $edited_Newsletter->ID,
						'results_title'         => T_('Automations').get_manual_link( 'email-list-automations' ),
						'results_prefix'        => 'enltautm_',
					) );
				break;

			case 'subscribers':
				$AdminUI->disp_view( 'email_campaigns/views/_newsletters_subscriber.view.php' );
				break;

			case 'general':
			default:
				$AdminUI->disp_view( 'email_campaigns/views/_newsletters.form.php' );
		}
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