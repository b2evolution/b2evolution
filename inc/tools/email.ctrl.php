<?php
/**
 * This file implements the UI controller for browsing the email tools.
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
 * @var User
 */
global $current_User;

global $DB;

// Check permission:
$current_User->check_perm( 'emails', 'view', true );

load_class( 'tools/model/_emailaddress.class.php', 'EmailAddress' );
load_funcs('tools/model/_email.funcs.php');

param_action();

$tab = param( 'tab', 'string', 'addresses', true );
$tab2 = param( 'tab2', 'string', '', true );
$tab3 = param( 'tab3', 'string', '', true );

param( 'action', 'string' );

if( $tab == 'addresses' )
{	// Email addresses
	if( param( 'emadr_ID', 'integer', '', true) )
	{	// Load Email Address object
		$EmailAddressCache = & get_EmailAddressCache();
		if( ( $edited_EmailAddress = & $EmailAddressCache->get_by_ID( $emadr_ID, false ) ) === false )
		{	// We could not find the goal to edit:
			unset( $edited_EmailAddress );
			forget_param( 'emadr_ID' );
			$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Email Address') ), 'error' );
		}
	}
}

switch( $action )
{
	case 'settings': // Update the email settings
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'emailsettings' );

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		switch( $tab3 )
		{
			case 'envelope':
				/* Email envelope: */

				// Sender email address
				$sender_email = param( 'notification_sender_email', 'string', '' );
				param_check_email( 'notification_sender_email', true );
				$Settings->set( 'notification_sender_email',  $sender_email );

				// Sender name
				$sender_name = param( 'notification_sender_name', 'string', '' );
				param_check_not_empty( 'notification_sender_name' );
				$Settings->set( 'notification_sender_name',  $sender_name );

				// Return path
				$return_path = param( 'notification_return_path', 'string', '' );
				param_check_email( 'notification_return_path', true );
				$Settings->set( 'notification_return_path', $return_path );

				// Site short name
				$short_name = param( 'notification_short_name', 'string', '' );
				param_check_not_empty( 'notification_short_name' );
				$Settings->set( 'notification_short_name',  $short_name );

				// Site long name
				$Settings->set( 'notification_long_name',  param( 'notification_long_name', 'string', '' ) );

				// Site logo url
				$Settings->set( 'notification_logo',  param( 'notification_logo', 'string', '' ) );
				break;

			case 'renderers':
				// Update email renderers settings:
				load_funcs('plugins/_plugin.funcs.php');

				$Plugins->restart();
				while( $loop_Plugin = & $Plugins->get_next() )
				{
					$tmp_params = array( 'for_editing' => true );
					$pluginsettings = $loop_Plugin->get_email_setting_definitions( $tmp_params );
					if( empty( $pluginsettings ) )
					{
						continue;
					}

					// Loop through settings for this plugin:
					foreach( $pluginsettings as $set_name => $set_meta )
					{
						autoform_set_param_from_request( $set_name, $set_meta, $loop_Plugin, 'EmailSettings' );
					}

					// Let the plugin handle custom fields:
					// We use call_method to keep track of this call, although calling the plugins PluginSettingsUpdateAction method directly _might_ work, too.
					$tmp_params = array();
					$ok_to_update = $Plugins->call_method( $loop_Plugin->ID, 'PluginSettingsUpdateAction', $tmp_params );

					if( $ok_to_update === false )
					{	// The plugin has said they should not get updated, Rollback settings:
						$loop_Plugin->Settings->reset();
					}
					else
					{	// Update message settings of the Plugin:
						$loop_Plugin->Settings->dbupdate();
					}
				}

				$Messages->add( T_('Settings updated.'), 'success' );

				// Redirect so that a reload doesn't write to the DB twice:
				header_redirect( '?ctrl=email&tab=settings&tab3='.$tab3, 303 ); // Will EXIT
				// We have EXITed already at this point!!
				break;

			case 'settings':
				// Settings to decode the returned emails:
				param( 'repath_enabled', 'boolean', 0 );
				$Settings->set( 'repath_enabled', $repath_enabled );

				param( 'repath_method', 'string', true );
				$Settings->set( 'repath_method', strtolower( $repath_method ) );

				param( 'repath_server_host', 'string', true );
				$Settings->set( 'repath_server_host', utf8_strtolower( $repath_server_host ) );

				param( 'repath_server_port', 'integer', true );
				$Settings->set( 'repath_server_port', $repath_server_port );

				param( 'repath_encrypt', 'string', true );
				$Settings->set( 'repath_encrypt', $repath_encrypt );

				param( 'repath_novalidatecert', 'boolean', 0 );
				$Settings->set( 'repath_novalidatecert', $repath_novalidatecert );

				param( 'repath_username', 'string', true );
				$Settings->set( 'repath_username', $repath_username );

				param( 'repath_password', 'string', true );
				$Settings->set( 'repath_password', $repath_password );

				param( 'repath_imap_folder', 'string', true );
				$Settings->set( 'repath_imap_folder', $repath_imap_folder );

				param( 'repath_ignore_read', 'boolean', 0 );
				$Settings->set( 'repath_ignore_read', $repath_ignore_read );

				param( 'repath_delete_emails', 'boolean', 0 );
				$Settings->set( 'repath_delete_emails', $repath_delete_emails );

				param( 'repath_subject', 'text', true );
				$Settings->set( 'repath_subject', $repath_subject );

				param( 'repath_body_terminator', 'text', true );
				$Settings->set( 'repath_body_terminator', $repath_body_terminator );

				param( 'repath_errtype', 'text', true );
				if( strlen( $repath_errtype ) > 5000 )
				{	// Crop the value by max available size
					$Messages->add( T_('Maximum length of the field "Error message decoding configuration" is 5000 symbols, the big value will be cropped.'), 'note' );
					$repath_errtype = substr( $repath_errtype, 0, 5000 );
				}
				$Settings->set( 'repath_errtype', $repath_errtype );
				break;

			case 'smtp':
				// SMTP gateway settings:

				// Preferred email service
				$Settings->set( 'email_service', param( 'email_service', 'string', 'mail' ) );

				// Force email sending
				$Settings->set( 'force_email_sending', param( 'force_email_sending', 'integer', 0 ) );

				$old_smtp_enabled = $Settings->get( 'smtp_enabled' );

				// Enabled
				$Settings->set( 'smtp_enabled', param( 'smtp_enabled', 'boolean', 0 ) );

				// SMTP Host
				$Settings->set( 'smtp_server_host', param( 'smtp_server_host', 'string', '' ) );

				// Port Number
				$Settings->set( 'smtp_server_port', param( 'smtp_server_port', 'integer' ) );

				// Encryption Method
				$Settings->set( 'smtp_server_security', param( 'smtp_server_security', 'string', '' ) );

				// Accept certificate
				$Settings->set( 'smtp_server_novalidatecert', param( 'smtp_server_novalidatecert', 'boolean', 0 ) );

				// SMTP Username
				$Settings->set( 'smtp_server_username', param( 'smtp_server_username', 'string', '' ) );

				// SMTP Password
				$Settings->set( 'smtp_server_password', param( 'smtp_server_password', 'string', '' ) );

				// Check if we really can use SMTP mailer
				if( $Settings->get( 'smtp_enabled' ) && ( $smtp_error = check_smtp_mailer() ) !== true )
				{ // No available to use SMTP gateway
					$Settings->set( 'smtp_enabled', 0 );
					$Messages->add( $smtp_error, 'warning' );
				}

				// Save info about SMTP enabling/disabling
				if( ! $old_smtp_enabled && $Settings->get( 'smtp_enabled' ) )
				{ // Enabled
					$syslog_message = T_( 'SMTP enabled.' );
				}
				elseif( $old_smtp_enabled && ! $Settings->get( 'smtp_enabled' ) )
				{ // Disabled
					$syslog_message = T_( 'SMTP disabled.' )
						.( ! empty( $smtp_error ) && is_string( $smtp_error ) ? ' '.sprintf( T_( 'Reason: %s' ), $smtp_error ) : '' );
				}
				break;

			case 'other':
				/* Campaign sending: */

				// Sending:
				$Settings->set( 'email_campaign_send_mode', param( 'email_campaign_send_mode', 'string', 'immediate' ) );

				// Chunk Size:
				$Settings->set( 'email_campaign_chunk_size', param( 'email_campaign_chunk_size', 'integer', 0 ) );

				// Delay between chunks:
				$Settings->set( 'email_campaign_cron_repeat', param_duration( 'email_campaign_cron_repeat' ) );
				break;

			default:
				// Invalid tab3
				break 2;
		}

		if( ! $Messages->has_errors() )
		{  
			if( $Settings->dbupdate() )
			{
				if( ! empty( $syslog_message ) )
				{ // Log system info into DB
					syslog_insert( $syslog_message, 'info', NULL );
				}
				$Messages->add( T_('Settings updated.'), 'success' );

				if( $tab3 == 'smtp' && $Settings->get( 'smtp_enabled' ) )
				{ // Check if connection is available
					global $smtp_connection_result;
					// Test SMTP connection
					$smtp_messages = smtp_connection_test();
					// Init this var to display a result on the page
					$smtp_test_output = is_array( $smtp_messages ) ? implode( "<br />\n", $smtp_messages ) : '';
					if( $smtp_connection_result )
					{ // Success
						$Messages->add( T_('The connection with this SMTP server has been tested successfully.'), 'success' );
					}
					else
					{ // Error
						$Messages->add( T_('The connection with this SMTP server has failed.'), 'error' );
					}
					// Don't redirect here in order to display a result($smtp_test_output) of SMTP connection above settings form
				}
				else
				{ // Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=email&tab='.$tab.'&tab2='.$tab2.'&tab3='.$tab3, 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}
			}
		}
		break;

	case 'test_1':
	case 'test_2':
	case 'test_3':
		// Test a tool that decodes the returned emails

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'emailsettings' );

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		load_funcs( 'cron/model/_decode_returned_emails.funcs.php');
		load_class( '_ext/mime_parser/rfc822_addresses.php', 'rfc822_addresses_class' );
		load_class( '_ext/mime_parser/mime_parser.php', 'mime_parser_class' );

		if( isset($GLOBALS['files_Module']) )
		{
			load_funcs( 'files/model/_file.funcs.php');
		}

		global $dre_messages;

		switch( $action )
		{
			case 'test_1':
				if( $mbox = dre_connect( false, true ) )
				{	// Close opened connection
					imap_close( $mbox );
				}
				break;

			case 'test_2':
				if( $mbox = dre_connect() )
				{
					// Read messages from server
					dre_msg('Reading messages from server');
					$imap_obj = imap_check( $mbox );
					dre_msg('Found '.$imap_obj->Nmsgs.' messages');

					if( $imap_obj->Nmsgs > 0 )
					{	// We will read only 1 message from server in test mode
						dre_process_messages( $mbox, 1 );
					}
					else
					{
						dre_msg( T_('There are no messages in the mailbox') );
					}
					imap_close( $mbox );
				}
				break;

				case 'test_3':
					param( 'test_error_message', 'raw', '' );
					if( !empty( $test_error_message ) )
					{	// Simulate a message processing
						dre_simulate_message( $test_error_message );
						$repath_test_output = implode( "<br />\n", $dre_messages );
					}
					break;
		}

		$Messages->clear(); // Clear all messages

		if( !empty( $dre_messages ) )
		{	// We will display the output in a scrollable fieldset
			$repath_test_output = implode( "<br />\n", $dre_messages );
		}
		break;

	case 'test_smtp':
		// Test connection to SMTP server by Swift Mailer

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'emailsettings' );

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		// Test SMTP connection
		$smtp_messages = smtp_connection_test();

		// Init this var to display a result on the page
		$smtp_test_output = is_array( $smtp_messages ) ? implode( "<br />\n", $smtp_messages ) : '';
		break;

	case 'test_email':
		// Test email sending by SMTP gateway:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'emailsettings' );

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		// Test email sending:
		$smtp_messages = smtp_email_sending_test();

		// Initialize this var to display a result on the page:
		$smtp_test_output = is_array( $smtp_messages ) ? implode( "<br />\n", $smtp_messages ) : '';
		break;

	case 'blocked_new':
		// Init Email Address to show on the form
		$edited_EmailAddress = new EmailAddress();
		break;

	case 'blocked_save':
		// Update Email Address...

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'email_blocked' );

		$action = 'blocked_edit';
		if( !isset( $edited_EmailAddress ) )
		{	// Create a new address
			$edited_EmailAddress = new EmailAddress();
			$action = 'blocked_new';
		}

		// load data from request
		if( $edited_EmailAddress->load_from_Request() )
		{	// We could load data from form without errors:
			// Save Email Address in DB:
			$edited_EmailAddress->dbsave();
			$Messages->add( T_('The email address was updated.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=email', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'blocked_delete':
		// Delete Email Address...

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'email_blocked' );

		// Make sure we got an emadr_ID:
		param( 'emadr_ID', 'integer', true );

		if( $edited_EmailAddress->dbdelete() )
		{
			$Messages->add( T_('The email address was deleted.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=email', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;
}

$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Emails'), '?ctrl=campaigns' );

switch( $tab )
{
	case 'sent':
		$AdminUI->breadcrumbpath_add( T_('Sent'), '?ctrl=email&amp;tab='.$tab );

		$tab3 = 'log';

		$emlog_ID = param( 'emlog_ID', 'integer', 0 );
		if( empty( $emlog_ID ) )
		{ // Initialize date picker on list page
			init_datepicker_js();
		}

		// Set an url for manual page:
		$AdminUI->set_page_manual_link( 'sent-emails' );
		break;

	case 'addresses':
		$AdminUI->breadcrumbpath_add( T_('Addresses'), '?ctrl=email&amp;tab='.$tab );
		if( !isset( $edited_EmailAddress ) )
		{ // List page with email addresses
			// Init js to edit status field
			require_js( 'jquery/jquery.jeditable.js', 'rsc_url' );
		}

		// Set an url for manual page:
		$AdminUI->set_page_manual_link( 'email-addresses' );
		break;

	case 'return':
		$AdminUI->breadcrumbpath_add( T_('Returned'), '?ctrl=email&amp;tab='.$tab );
		if( empty( $emret_ID ) )
		{ // Initialize date picker on list page
			init_datepicker_js();
		}

		if( empty( $tab3 ) )
		{	// Set default tab3 for first tab:
			$tab3 = 'log';
		}

		switch( $tab3 )
		{
			case 'log':
				$AdminUI->breadcrumbpath_add( T_('Return Log'), '?ctrl=email&amp;tab=settings&amp;tab3='.$tab3 );

				// Set an url for manual page:
				$AdminUI->set_page_manual_link( 'email-returned' );
				break;

			case 'settings':
				$AdminUI->breadcrumbpath_add( T_('POP/IMAP Settings'), '?ctrl=email&amp;tab=settings&amp;tab3='.$tab3 );

				if( $Settings->get( 'repath_enabled' ) )
				{ // If the decoding the returned emails is enabled
					$repath_cron_SQL = new SQL();
					$repath_cron_SQL->SELECT( 'ctsk_ID' );
					$repath_cron_SQL->FROM( 'T_cron__task' );
					$repath_cron_SQL->WHERE( 'ctsk_key = "process-return-path-inbox"' );
					$repath_cron = $DB->get_var( $repath_cron_SQL->get() );
					if( empty( $repath_cron ) )
					{ // Display a warning if cron job "Process the return path inbox" doesn't exist:
						$repath_warning = T_('There is no scheduled job configured to process your Return Path inbox.');
						if( $current_User->check_perm( 'options', 'edit' ) )
						{ // Suggest a link to create a job if current user has a permission:
							$repath_warning .= ' '.sprintf( T_('<a %s>Click here</a> to create such a job.'), ' href="'.$admin_url.'?ctrl=crontab&amp;action=new&amp;cjob_type=process-return-path-inbox"' );
						}
						$Messages->add( $repath_warning, 'warning' );
					}
				}

				// Set an url for manual page:
				$AdminUI->set_page_manual_link( 'return-path-configuration' );
				break;

			case 'test':
				// Check permission:
				$current_User->check_perm( 'emails', 'edit', true );

				$AdminUI->breadcrumbpath_add( T_('Test'), '?ctrl=email&amp;tab=settings&amp;tab3='.$tab3 );

				// Set an url for manual page:
				$AdminUI->set_page_manual_link( 'return-path-configuration' );
				break;
		}
		break;

	case 'settings':
		$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=email&amp;tab='.$tab );

		if( $tab2 == 'sent' )
		{	// The settings are opened from from tab "Sent"
			$orig_tab = $tab;
			$tab = $tab2;
		}

		if( empty( $tab3 ) )
		{	// Default tab3 for this case:
			$tab3 = 'renderers';
		}

		switch( $tab3 )
		{
			case 'envelope':
				$AdminUI->breadcrumbpath_add( T_('Envelope'), '?ctrl=email&amp;tab=settings&amp;tab3='.$tab3 );

				// Set an url for manual page:
				$AdminUI->set_page_manual_link( 'email-notification-settings' );
				break;

			case 'smtp':
				$AdminUI->breadcrumbpath_add( T_('SMTP gateway'), '?ctrl=email&amp;tab=settings&amp;tab3='.$tab3 );

				// Set an url for manual page:
				$AdminUI->set_page_manual_link( 'smtp-gateway-settings' );

				if( $Settings->get( 'email_service' ) == 'smtp' && ! $Settings->get( 'smtp_enabled' ) )
				{	// Display this error when primary email service is SMTP but it is not enabled:
					$Messages->add( T_('Your external SMTP Server is not enabled.'), 'error' );
				}
				break;

			case 'other':
				$AdminUI->breadcrumbpath_add( T_('Other'), '?ctrl=email&amp;tab=settings&amp;tab3='.$tab3 );

				// Set an url for manual page:
				$AdminUI->set_page_manual_link( 'email-other-settings' );
				break;

			case 'renderers':
			default:
				$AdminUI->breadcrumbpath_add( T_('Renderers'), '?ctrl=email&amp;tab=settings&amp;tab3='.$tab3 );

				// Set an url for manual page:
				$AdminUI->set_page_manual_link( 'email-renderers-settings' );
				break;
		}

		break;
}

$AdminUI->set_path( 'email', $tab, $tab3 );
if( ! empty( $orig_tab ) )
{	// Restore original tab:
	$tab = $orig_tab;
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

evo_flush();

switch( $tab )
{
	case 'sent':
		if( $emlog_ID > 0 )
		{	// Display a details of selected email log
			$MailLog = $DB->get_row( '
				SELECT *
				  FROM T_email__log
				 WHERE emlog_ID = '.$DB->quote( $emlog_ID ) );
			if( $MailLog )
			{	// The mail log exists with selected ID
				$AdminUI->disp_view( 'tools/views/_email_sent_details.view.php' );
				break;
			}
		}
		// Display a list of email logs:
		$AdminUI->disp_view( 'tools/views/_email_sent.view.php' );
		break;

	case 'addresses':
		if( isset( $edited_EmailAddress ) )
		{	// Display form to create/edit an email address
			$AdminUI->disp_view( 'tools/views/_email_address.form.php' );
			break;
		}
		// Display a list of email logs:
		$AdminUI->disp_view( 'tools/views/_email_address.view.php' );
		break;

	case 'return':
		load_funcs('cron/model/_decode_returned_emails.funcs.php');

		switch( $tab3 )
		{
			case 'settings':
				// Display a settings of the returned emails:
				$AdminUI->disp_view( 'tools/views/_email_return_settings.form.php' );
				break;

			case 'test':
				// Display a form to test a returned email:
				$AdminUI->disp_view( 'tools/views/_email_return_test.form.php' );
				break;

			case 'log':
			default:
				// Display a list of the returnted emails:
				$emret_ID = param( 'emret_ID', 'integer', 0 );
				if( $emret_ID > 0 )
				{	// Display a details of selected email
					$MailReturn = $DB->get_row( '
						SELECT *
							FROM T_email__returns
						 WHERE emret_ID = '.$DB->quote( $emret_ID ) );
					if( $MailReturn )
					{	// The returned email exists with selected ID
						$AdminUI->disp_view( 'tools/views/_email_return_details.view.php' );
						break;
					}
				}
				// Display a list of email logs:
				$AdminUI->disp_view( 'tools/views/_email_return.view.php' );
				break;
		}
		break;

	case 'settings':
		switch( $tab3 )
		{
			case 'envelope':
				$AdminUI->disp_view( 'tools/views/_email_settings.form.php' );
				break;

			case 'smtp':
				$AdminUI->disp_view( 'tools/views/_email_smtp.form.php' );
				break;

			case 'other':
				$AdminUI->disp_view( 'tools/views/_email_other.form.php' );
				break;

			case 'renderers':
			default:
				$AdminUI->disp_view( 'tools/views/_email_renderers.form.php' );
		}
		break;

}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>