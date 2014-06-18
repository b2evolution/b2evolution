<?php
/**
 * This file implements the UI controller for browsing the email campaigns.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Vegar BERG GULDAL grants Francois PLANQUE the right to license
 * Vegar BERG GULDAL's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: campaigns.ctrl.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Check permission:
$current_User->check_perm( 'emails', 'view', true );

load_class( 'email_campaigns/model/_emailcampaign.class.php', 'EmailCampaign' );
load_funcs( 'email_campaigns/model/_emailcampaign.funcs.php' );

param_action();
param( 'tab', 'string', 'info' );

if( param( 'ecmp_ID', 'integer', '', true) )
{ // Load Email Campaign object
	$EmailCampaignCache = & get_EmailCampaignCache();
	if( ( $edited_EmailCampaign = & $EmailCampaignCache->get_by_ID( $ecmp_ID, false ) ) === false )
	{ // We could not find the goal to edit:
		unset( $edited_EmailCampaign );
		forget_param( 'ecmp_ID' );
		$action = '';
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Email Campaign') ), 'error' );
	}
}

switch( $action )
{
	case 'add':
		// Add Email Campaign...

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'campaign' );

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		$new_EmailCampaign = new EmailCampaign();

		if( ! $new_EmailCampaign->load_from_Request() )
		{ // We could not load data from form with errors:
			$action = 'new';
			break;
		}

		// Save Email Campaign in DB:
		$new_EmailCampaign->dbinsert();
		$Session->set( 'edited_campaign_ID', $new_EmailCampaign->ID );

		$Messages->add( T_('The email campaign was created, please select the users.'), 'success' );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $admin_url.'?ctrl=users', 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'switchtab':
	case 'save':
		// Save Campaign

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'campaign' );

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		$current_tab = param( 'current_tab', 'string', 'info' );

		if( ! $edited_EmailCampaign->load_from_Request() )
		{ // We could not load data from form with errors:
			$action = 'edit';
			$tab = $current_tab;
			break;
		}

		// Save changes in DB:
		if( $edited_EmailCampaign->dbupdate() === true )
		{
			$Messages->add( T_('The email campaign was updated.'), 'success' );
		}

		// Redirect so that a reload doesn't write to the DB twice:
		$redirect_tab_type = 'current';
		if( $action == 'save' )
		{
			$tab = $current_tab;
			$redirect_tab_type = 'next';
		}
		header_redirect( get_campaign_tab_url( $tab, $edited_EmailCampaign->ID, $redirect_tab_type ), 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'change_users':
		$Session->set( 'edited_campaign_ID', $edited_EmailCampaign->ID );

		$Messages->add( T_('Please select new users for email campaign.'), 'success' );

		// Redirect to select users:
		header_redirect( '?ctrl=users', 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'users':
		// Select new users for campaigns, Go from controller 'users'

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'campaign' );

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		// Initialize email campaign from users list page
		$edited_campaign_ID = $Session->get( 'edited_campaign_ID' );
		if( !empty( $edited_campaign_ID ) )
		{ // Get Email Campaign by ID from Session
			$EmailCampaignCache = & get_EmailCampaignCache();
			$edited_EmailCampaign = & $EmailCampaignCache->get_by_ID( $edited_campaign_ID, false, false );
		}

		if( empty( $edited_EmailCampaign ) )
		{ // Create new email campaign if it didn't create before
			$edited_EmailCampaign = new EmailCampaign();
			$edited_EmailCampaign->set( 'name', 'New campaign' );
			$edited_EmailCampaign->dbinsert();
		}

		// Clear campaign ID from session
		$Session->delete( 'edited_campaign_ID' );

		// Save users for edited email campaign
		$edited_EmailCampaign->add_users();

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $admin_url.'?ctrl=campaigns&action=edit_users&ecmp_ID='.$edited_EmailCampaign->ID, 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'delete':
		// Delete Email Campaign...

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'campaign' );

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		// Make sure we got an ecmp_ID:
		param( 'ecmp_ID', 'integer', true );

		if( $edited_EmailCampaign->dbdelete() )
		{
			$Messages->add( T_('The email campaign was deleted.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=campaigns', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'extract_html':
		// Extract text from HTML

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'campaign' );

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		$email_html = $edited_EmailCampaign->get( 'email_html' );

		// Convert HTML to Plain Text
		$email_text = str_replace(
			array( "\n", "\r", '</p><p>', '<p>',  '</p>', '<br>', '<br />', '<br/>' ),
			array( '',   '',   "\n\n",    "\n\n", "\n\n", "\n",   "\n",     "\n" ),
			$email_html );
		$email_text = strip_tags( $email_text );

		$edited_EmailCampaign->set( 'email_text', $email_text );

		$action = 'edit';
		$tab = param( 'current_tab', 'string' );
		break;

	case 'test':
		// Send test email

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'campaign' );

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		// Test email address
		param( 'test_email_address', 'string', '' );
		param_string_not_empty( 'test_email_address', T_('Please enter test email address') );
		param_check_email( 'test_email_address', T_('Test email address is incorrect') );
		// Store test email address in Session
		$Session->set( 'test_campaign_email', $test_email_address );
		$Session->dbsave();

		// Check campaign before sending
		$edited_EmailCampaign->check( true, 'test' );

		$action = 'edit';
		$tab = param( 'current_tab', 'string' );

		if( param_errors_detected() )
		{ // Some errors were detected in the campaign's fields or test email address
			break;
		}

		// Send one test email
		if( ! $edited_EmailCampaign->send_email( $current_User->ID, $test_email_address, 'test' ) )
		{ // Sending is failed
			$Messages->add( T_('Sorry, the test email could not be sent.')
				.'<br />'.T_('Possible reason: the PHP mail() function may have been disabled on the server.'), 'error' );
			break;
		}

		$Messages->add( sprintf( T_('One test email was sent to email address: %s'), $test_email_address ), 'success' );

		// Redirect so that a reload doesn't send email twice:
		header_redirect( get_campaign_tab_url( 'send', $edited_EmailCampaign->ID ), 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'send':
		// Send newsletter email for all users of this campaign

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'campaign' );

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		// Check campaign before sending
		$edited_EmailCampaign->check();

		// Set this var to display again the same form where we can review and send campaign
		$action = 'edit';
		$tab = param( 'current_tab', 'string' );

		if( param_errors_detected() )
		{ // The campaign has some empty fields
			break;
		}

		// Execute a sending in template to display report in real time
		$template_action = 'send_campaign';

		// Try to obtain some serious time to do some serious processing (15 minutes)
		set_max_execution_time( 900 );
		// Turn off the output buffering to do the correct work of the function flush()
		@ini_set( 'output_buffering', 'off' );
		break;
}


// Use this switch block to init some messages for view actions
switch( $action )
{
	case 'edit':
	case 'edit_users':
		if( empty( $ecmp_ID ) )
		{ // If empty ID - clear action and display list of campaigns
			$action = '';
			break;
		}

		if( $action == 'edit_users' )
		{ // Get users info for newsletter only when redirect from user list
			$users_numbers = get_newsletter_users_numbers();

			if( $users_numbers['all'] == 0 )
			{ // No users in the filterset, Redirect to users list
				$Messages->add( T_('No found accounts in filterset. Please try to change the filter of users list.'), 'error' );
			}

			if( $users_numbers['newsletter'] == 0 )
			{ // No users for newsletter
				$Messages->add( T_('No found active accounts which accept newsletter email. Please try to change the filter of users list.'), 'note' );
			}

			$action = 'edit';
		}
		break;
}


$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Emails'), $admin_url.'?ctrl=campaigns' );
$AdminUI->breadcrumbpath_add( T_('Campaigns'), $admin_url.'?ctrl=campaigns' );

if( $action == 'edit' )
{ // Build special tabs in edit mode of the campaign
	$AdminUI->set_path( 'email', $tab );
	$AdminUI->clear_menu_entries( 'email' );
	$campaign_edit_modes = get_campaign_edit_modes( $ecmp_ID );
	$AdminUI->add_menu_entries( 'email', $campaign_edit_modes );
	$AdminUI->breadcrumbpath_add( T_('Edit campaign'), $admin_url.'?ctrl=campaigns&amp;action=edit&amp;ecmp_ID='.$ecmp_ID );

	if( !empty( $campaign_edit_modes[ $tab ] ) )
	{
		$AdminUI->breadcrumbpath_add( $campaign_edit_modes[ $tab ]['text'], $campaign_edit_modes[ $tab ]['href'] );
	}
}
else
{ // List of campaigns
	$AdminUI->set_path( 'email', 'campaigns' );
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
	case 'new':
		// Display a form of new email campaign:
		$AdminUI->disp_view( 'email_campaigns/views/_campaigns_new.form.php' );
		break;

	case 'edit':
		// Display a form to edit email campaign:
		switch( $tab )
		{
			case 'info':
				$AdminUI->disp_view( 'email_campaigns/views/_campaigns_info.form.php' );
				break;

			case 'html':
				if( $edited_EmailCampaign->get( 'email_html' ) == '' && !param_errors_detected() )
				{ // Set default value for HTML message
					$edited_EmailCampaign->set( 'email_html', '<p>This is our newsletter...</p>' );
				}
				$AdminUI->disp_view( 'email_campaigns/views/_campaigns_html.form.php' );
				break;

			case 'text':
				$AdminUI->disp_view( 'email_campaigns/views/_campaigns_text.form.php' );
				break;

			case 'send':
				$AdminUI->disp_view( 'email_campaigns/views/_campaigns_send.form.php' );
				break;
		}
		break;

	default:
		// Display a list of email campaigns:
		$AdminUI->disp_view( 'email_campaigns/views/_campaigns.view.php' );
		break;
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>