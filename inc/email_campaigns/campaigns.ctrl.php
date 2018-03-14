<?php
/**
 * This file implements the UI controller for browsing the email campaigns.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Check permission:
$current_User->check_perm( 'admin', 'normal', true );
$current_User->check_perm( 'emails', 'view', true );

load_class( 'email_campaigns/model/_emailcampaign.class.php', 'EmailCampaign' );
load_funcs( 'email_campaigns/model/_emailcampaign.funcs.php' );

param_action();
param( 'tab', 'string', 'info' );

if( param( 'ecmp_ID', 'integer', '', true ) )
{	// Load Email Campaign object:
	$EmailCampaignCache = & get_EmailCampaignCache();
	if( ( $edited_EmailCampaign = & $EmailCampaignCache->get_by_ID( $ecmp_ID, false ) ) === false )
	{	// We could not find the goal to edit:
		unset( $edited_EmailCampaign );
		forget_param( 'ecmp_ID' );
		$action = '';
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Email Campaign') ), 'error' );
	}
}

switch( $action )
{
	case 'new':
		// New Email Campaign form:

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		// Check if at least one newsletter is active:
		$NewsletterCache = & get_NewsletterCache();
		$NewsletterCache->load_where( 'enlt_active = 1' );
		if( empty( $NewsletterCache->cache ) )
		{
			$Messages->add( T_('You must create an active List before you can create a new Campaign'), 'error' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=newsletters', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

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

		$Messages->add( T_('The email campaign was created.'), 'success' );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $admin_url.'?ctrl=campaigns&action=edit&ecmp_ID='.$new_EmailCampaign->ID, 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'switchtab':
	case 'save':
		// Save Campaign:

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
		{	// Save & continue to next step:
			$tab = $current_tab;
			$redirect_tab_type = 'next';
		}

		// Redirect after saving:
		header_redirect( get_campaign_tab_url( $tab, $edited_EmailCampaign->ID, $redirect_tab_type ), 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'update_newsletter':
		// Update Newsletter of Campaign:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'campaign' );

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		$current_tab = param( 'current_tab', 'string', 'info' );

		// Update only newsletter of the edited Email Campaign:
		param( 'ecmp_enlt_ID', 'integer', NULL );
		param_string_not_empty( 'ecmp_enlt_ID', T_('Please select a list.') );
		$edited_EmailCampaign->set_from_Request( 'enlt_ID' );

		// Save changes in DB:
		$edited_EmailCampaign->dbupdate();

		// Update recipients only if newsletter has been changed:
		$edited_EmailCampaign->update_recipients( true );

		$Messages->add( T_('Campaign has been attached to a different list.'), 'success' );

		// Redirect after saving:
		header_redirect( get_campaign_tab_url( $current_tab, $edited_EmailCampaign->ID ), 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'hide_wysiwyg_warning':
	case 'show_wysiwyg_warning':
		global $UserSettings;

		// Show/hide warning when switching from markup to WYSIWYG
		$Session->assert_received_crumb( 'campaign' );

		// Check that this action request is not a CSRF hacked request:
		$UserSettings->set( 'show_wysiwyg_warning_emailcampaign', ( $action == 'show_wysiwyg_warning' ? 1: 0 ) );
		$UserSettings->dbupdate();

		// REDIRECT / EXIT
		header_redirect( $admin_url.'?ctrl=campaigns&action=edit&ecmp_ID='.$edited_EmailCampaign->ID.'&tab=compose' );
		break;

	case 'create_for_users':
	case 'update_users':
		// Select new users for campaigns, Go from controller 'users'

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'campaign' );

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		$newsletter_ID = param( 'newsletter', 'integer', 0 );
		$NewsletterCache = & get_NewsletterCache();
		if( ! ( $Newsletter = & $NewsletterCache->get_by_ID( $newsletter_ID, false, false ) ) || ! $Newsletter->get( 'active' ) )
		{	// If the selected newsletter cannot be used for email campaigns (because it doesn't exist or is not active):
			$Messages->add( T_('Selected list cannot be used for email campaign.'), 'warning' );
			header_redirect( $admin_url.'?ctrl=users&action=newsletter&filter=new&newsletter='.$newsletter_ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

		if( $action == 'create_for_users' )
		{	// Create new email campaign if admin want creates it from free users list:
			$edited_EmailCampaign = new EmailCampaign();
			$edited_EmailCampaign->set( 'enlt_ID', $Newsletter->ID );
			$edited_EmailCampaign->set( 'email_title', $Newsletter->get( 'name' ) );
			$edited_EmailCampaign->dbinsert();
			$Messages->add( T_('New email campaign has been created for the users selection.'), 'success' );
		}
		elseif( $action == 'update_users' &&
		    ( $edited_EmailCampaign || ( $edited_EmailCampaign = & get_session_EmailCampaign() ) )
		  )
		{	// If email campaign already exists in DB:
			if( $edited_EmailCampaign->get( 'enlt_ID' ) != $Newsletter->ID )
			{	// Update newsletter if it was changed on users list filtering:
				$edited_EmailCampaign->set( 'enlt_ID', $Newsletter->ID );
				$edited_EmailCampaign->dbupdate();
			}
		}

		if( ! $edited_EmailCampaign )
		{	// If campaign is not defined we cannot assign recipients, Redirect to campaigns list:
			header_redirect( $admin_url.'?ctrl=campaigns', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

		// Clear campaign ID from session:
		$Session->delete( 'edited_campaign_ID' );

		// Save recipients for edited email campaign:
		$edited_EmailCampaign->add_recipients();

		if( $action == 'update_users' )
		{	// Display a message for updating of users selection:
			$Messages->add( T_('Users selection has been updated for this email campaign.'), 'success' );
		}

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

		if( param( 'confirm', 'integer', 0 ) )
		{	// Delete from DB if confirmed:
			$msg = sprintf( T_('The email campaign "%s" has been deleted.'), $edited_EmailCampaign->dget( 'email_title' ) );
			$edited_EmailCampaign->dbdelete();
			unset( $edited_EmailCampaign );
			forget_param( 'ecmp_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=campaigns', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// Check for restrictions if not confirmed yet:
			if( ! $edited_EmailCampaign->check_delete( sprintf( T_('Cannot delete email campaign "%s"'), $edited_EmailCampaign->dget( 'email_title' ) ) ) )
			{	// There are restrictions:
				$action = 'view';
			}
		}
		break;

	case 'test':
		// Send test email
		global $track_email_image_load, $track_email_click_html, $track_email_click_plain_text;

		$track_email_image_load = param( 'track_test_email_image_load', 'boolean', 0 );
		$track_email_click_html = param( 'track_test_email_click_html', 'boolean', 0 );
		$track_email_click_plain_text = param( 'track_test_email_click_plain_text', 'boolean', 0 );

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
	case 'create_cron':
		global $track_email_image_load, $track_email_click_html, $track_email_click_plain_text;

		param( 'track_email_image_load', 'boolean', 0 );
		param( 'track_email_click_html', 'boolean', 0 );
		param( 'track_email_click_plain_text', 'boolean', 0 );

		// Send newsletter email for all users of this campaign OR create cron job to do this later:

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

		if( $Settings->get( 'email_campaign_send_mode' ) == 'cron' )
		{	// Asynchronous sending mode:

			// Create a scheduled job to send newsletters of this email campaign:
			$edited_EmailCampaign->create_cron_job();
		}
		else
		{	// Immediate sending mode:

			// Execute a sending in template to display report in real time
			$template_action = 'send_campaign';

			// Try to obtain some serious time to do some serious processing (15 minutes)
			set_max_execution_time( 900 );
			// Turn off the output buffering to do the correct work of the function flush()
			@ini_set( 'output_buffering', 'off' );
		}
		break;

	case 'view_cron':
		// Redirect to view cron job of the email campaign:

		if( ! ( $email_campaign_Cronjob = & $edited_EmailCampaign->get_Cronjob() ) )
		{	// No cron job found:
			$action = 'edit';
			$tab = param( 'current_tab', 'string' );
			break;
		}

		if( ! $current_User->check_perm( 'options', 'view' ) )
		{	// No access to view cron jobs:
			$Messages->add( T_('Sorry, you don\'t have permission to view scheduled jobs.' ), 'warning' );
			$action = 'edit';
			$tab = param( 'current_tab', 'string' );
			break;
		}

		header_redirect( $admin_url.'?ctrl=crontab&action=view&cjob_ID='.$email_campaign_Cronjob->ID, 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'queue':
		param( 'ecmp_ID', 'integer', NULL );
		param( 'user_ID', 'integer', NULL );

		queue_campaign_user( $ecmp_ID, $user_ID );

		// Set this var to display again the same form where we can review and send campaign
		$action = 'edit';
		break;

	case 'skip':
		param( 'ecmp_ID', 'integer', NULL );
		param( 'user_ID', 'integer', NULL );

		skip_campaign_user( $ecmp_ID, $user_ID );

		// Set this var to display again the same form where we can review and send campaign
		$action = 'edit';
		break;
}


// Use this switch block to init some messages for view actions
switch( $action )
{
	case 'edit':
	case 'edit_users':
		if( empty( $edited_EmailCampaign ) )
		{	// If no edited email campaign - clear action and display list of campaigns:
			$action = '';
			break;
		}

		if( $action == 'edit_users' )
		{	// Check a recipients count after redirect from users list:
			if( $edited_EmailCampaign->get_recipients_count( 'filter' ) == 0 )
			{	// No users in the filterset:
				$Messages->add( T_('No account matches the filterset. Please try to change the filters.'), 'error' );
			}

			if( $edited_EmailCampaign->get_recipients_count( 'all' ) == 0 )
			{	// No users for newsletter:
				$Messages->add( T_('No active account accepts email from this list. Please try to change the filters.'), 'note' );
			}

			$action = 'edit';
		}
		break;
}


$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Emails'), $admin_url.'?ctrl=campaigns' );
$AdminUI->breadcrumbpath_add( T_('Campaigns'), $admin_url.'?ctrl=campaigns' );

$AdminUI->display_breadcrumbpath_init();

// Set an url for manual page:
switch( $action )
{
	case 'new':
	case 'edit':
		$AdminUI->set_page_manual_link( 'creating-an-email-campaign' );
		$AdminUI->display_breadcrumbpath_add( T_('Campaigns'), $admin_url.'?ctrl=campaigns' );
		$AdminUI->display_breadcrumbpath_add( isset( $edited_EmailCampaign ) ? $edited_EmailCampaign->get( 'email_title' ) : T_('New Campaign') );
		break;
	default:
	$AdminUI->display_breadcrumbpath_add( T_('Campaigns') );
		$AdminUI->set_page_manual_link( 'email-campaigns' );
		break;
}

if( $action == 'edit' || $action == 'delete' )
{ // Build special tabs in edit mode of the campaign
	$AdminUI->set_path( 'email', 'campaigns', $tab );
	$campaign_edit_modes = get_campaign_edit_modes( $ecmp_ID );
	$AdminUI->add_menu_entries( array( 'email', 'campaigns' ), $campaign_edit_modes );
	$AdminUI->breadcrumbpath_add( T_('Edit campaign'), $admin_url.'?ctrl=campaigns&amp;action=edit&amp;ecmp_ID='.$ecmp_ID );

	if( !empty( $campaign_edit_modes[ $tab ] ) )
	{
		$AdminUI->breadcrumbpath_add( $campaign_edit_modes[ $tab ]['text'], $campaign_edit_modes[ $tab ]['href'] );
	}

	if( $tab == 'compose' )
	{	// Require colorbox js:
		require_js_helper( 'colorbox' );
		// Require Fine Uploader js and css:
		init_fineuploader_js_lang_strings();
		require_js( 'multiupload/fine-uploader.js' );
		require_css( 'fine-uploader.css' );
		// Load JS files to make the links table sortable:
		require_js( '#jquery#' );
		require_js( 'jquery/jquery.sortable.min.js' );
	}
	elseif( $tab == 'recipient' )
	{
		// Initialize date picker
		init_datepicker_js();

		// Initialize user tag input
		init_tokeninput_js();
	}
	elseif( $tab == 'info' )
	{
		init_tokeninput_js();
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

	case 'delete':
		// We need to ask for confirmation:
		$edited_EmailCampaign->confirm_delete(
				sprintf( T_('Delete email campaign "%s"?'), $edited_EmailCampaign->dget( 'email_title' ) ),
				'campaign', $action, get_memorized( 'action' ) );
		/* no break */
	case 'edit':
		// Display a form to edit email campaign:
		switch( $tab )
		{
			case 'info':
				$AdminUI->disp_view( 'email_campaigns/views/_campaigns_info.form.php' );
				break;

			case 'compose':
				if( $edited_EmailCampaign->get( 'email_text' ) == '' && !param_errors_detected() )
				{ // Set default value for HTML message
					$edited_EmailCampaign->set( 'email_text', sprintf( T_('Hello %s!'), '$firstname_and_login$' )."\r\n\r\n".T_('Here are some news...') );
				}
				$AdminUI->disp_view( 'email_campaigns/views/_campaigns_compose.form.php' );
				break;

			case 'send':
			case 'create_cron':
				$AdminUI->disp_view( 'email_campaigns/views/_campaigns_send.form.php' );
				break;

			case 'recipient':
				param( 'recipient_type', 'string', '', true );
				memorize_param( 'action', 'string', '' );
				memorize_param( 'tab', 'string', '' );
				$AdminUI->disp_view( 'email_campaigns/views/_campaigns_recipient.view.php' );
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