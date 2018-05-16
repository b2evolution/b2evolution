<?php
/**
 * This file implements the UI controller for Cron table.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_funcs( 'cron/_cron.funcs.php' );

// Check minimum permission:
$current_User->check_perm( 'admin', 'normal', true );
$current_User->check_perm( 'options', 'view', true );

$AdminUI->set_path( 'options', 'cron', 'list' );

param( 'action', 'string', 'list' );
param( 'tab', 'string', 'list' );

if( param( 'ctsk_ID', 'integer', '', true) )
{// Load cronjob from cache:
	$CronjobCache = & get_CronjobCache();
	if( ( $edited_Cronjob = & $CronjobCache->get_by_ID( $ctsk_ID, false ) ) === false )
	{
		unset( $edited_Cronjob );
		forget_param( 'ctsk_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Scheduled job') ), 'error' );
		$action = 'list';
	}
}

switch( $action )
{
	case 'new':
		// Check that we have permission to edit options:
		$current_User->check_perm( 'options', 'edit', true, NULL );

		load_class( 'cron/model/_cronjob.class.php', 'Cronjob' );
		$edited_Cronjob = new Cronjob();

		// Get this param to preselect job type by url param:
		param( 'cjob_type', 'string' );
		break;

	case 'edit':
	case 'copy':
		// Check that we have permission to edit options:
		$current_User->check_perm( 'options', 'edit', true, NULL );

		if( ( $action == 'edit' && $edited_Cronjob->get_status() != 'pending' ) ||
		    ( $action == 'copy' && $edited_Cronjob->get_status() != 'error' ) )
		{	// Don't edit cron jobs with not "pending" status
			header_redirect( '?ctrl=crontab', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

		if( $action == 'copy' )
		{	// Reset time to now for copied cron job
			global $localtimenow;
			$edited_Cronjob->start_timestamp = $localtimenow;
		}

		break;

	case 'create':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'crontask' );

		// Check that we have permission to edit options:
		$current_User->check_perm( 'options', 'edit', true, NULL );

		if( !empty( $edited_Cronjob ) )
		{ // It is a copy action, we should save the fields "key" & "params"
			$ctsk_key = $edited_Cronjob->get( 'key' );
			$ctsk_params = $edited_Cronjob->get( 'params' );
		}

		// CREATE OBJECT:
		load_class( '/cron/model/_cronjob.class.php', 'Cronjob' );
		$edited_Cronjob = new Cronjob();

		if( $edited_Cronjob->load_from_Request() )
		{	// We could load data from form without errors:

			if( !empty( $ctsk_key ) )
			{	// Save controller field from copied object
				$edited_Cronjob->set( 'key', $ctsk_key );
			}

			if( !empty( $ctsk_params ) )
			{	// Save params field from copied object
				$edited_Cronjob->set( 'params', $ctsk_params );
			}

			// Save to DB:
			$edited_Cronjob->dbinsert();

			$Messages->add( T_('New job has been scheduled.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=crontab', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'crontask' );

		// Check that we have permission to edit options:
		$current_User->check_perm( 'options', 'edit', true, NULL );

		if( $edited_Cronjob->load_from_Request() )
		{	// We could load data from form without errors:

			if( $edited_Cronjob->dbupdate() )
			{	// The job was updated successfully
				$Messages->add( T_('The scheduled job has been updated.'), 'success' );
			}
			else
			{	// Errors on updating, probably this job has not "pending" status
				$Messages->add( T_('This scheduled job can not be updated.'), 'error' );
			}

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=crontab', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;


	case 'delete':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'crontask' );

		// Make sure we got an ord_ID:
		param( 'ctsk_ID', 'integer', true );

		// Check that we have permission to edit options:
		$current_User->check_perm( 'options', 'edit', true, NULL );

		// TODO: prevent deletion of running tasks.
		$DB->begin();

		$tsk_status =	$DB->get_var(
			'SELECT clog_status
				 FROM T_cron__log
				WHERE clog_ctsk_ID= '.$ctsk_ID,
			0, 0, 'Check that task is not running' );

		if( $tsk_status == 'started' )
		{
			$DB->rollback();

			$Messages->add(  sprintf( T_('Job #%d is currently running. It cannot be deleted.'), $ctsk_ID ), 'error' );
		}
		else
		{
			// Delete task:
			$DB->query( 'DELETE FROM T_cron__task
										WHERE ctsk_ID = '.$ctsk_ID );

			// Delete log (if exists):
			$DB->query( 'DELETE FROM T_cron__log
										WHERE clog_ctsk_ID = '.$ctsk_ID );

			$DB->commit();

			$Messages->add(  sprintf( T_('Scheduled job #%d deleted.'), $ctsk_ID ), 'success' );
		}

		//forget_param( 'ctsk_ID' );
		//$action = 'list';
		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( regenerate_url( 'action,ctsk_ID', '', '', '&' ), 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'settings':
		// Update settings of cron jobs:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'cronsettings' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		$cron_jobs = get_cron_jobs_config( 'name' );
		foreach( $cron_jobs as $cron_job_key => $cron_job_name )
		{
			// Max execution time:
			$Settings->set( 'cjob_timeout_'.$cron_job_key, param_duration( 'cjob_timeout_'.$cron_job_key ) );

			// Additional settings per cron job:
			switch( $cron_job_key )
			{
				case 'send-email-campaign':
					// Send a chunk of x emails for the campaign:
					if( $current_User->check_perm( 'emails', 'edit' ) )
					{	// Allow to edit email cron setting "Chunk Size" only if user has a permission:
						$Settings->set( 'email_campaign_chunk_size', param( 'email_campaign_chunk_size', 'integer', 0 ) );
					}

					// Delay between chunks:
					$Settings->set( 'email_campaign_cron_repeat', param_duration( 'email_campaign_cron_repeat' ) );

					// Delay between chunks in case all remaining recipients have reached max # of emails for the current day:
					$Settings->set( 'email_campaign_cron_limited', param_duration( 'email_campaign_cron_limited' ) );
					break;

				case 'prune-old-hits-and-sessions':
					// Prune old hits & sessions (includes OPTIMIZE):
					param( 'auto_prune_stats', 'integer', $Settings->get_default( 'auto_prune_stats' ), false, false, true, false );
					$Settings->set( 'auto_prune_stats', get_param( 'auto_prune_stats' ) );
					break;

				case 'prune-recycled-comments':
					// Prune recycled comments:
					param( 'auto_empty_trash', 'integer', $Settings->get_default( 'auto_empty_trash' ), false, false, true, false );
					$Settings->set( 'auto_empty_trash', get_param( 'auto_empty_trash' ) );
					break;

				case 'cleanup-scheduled-jobs':
					// Clean up scheduled jobs older than a threshold:
					$Settings->set( 'cleanup_jobs_threshold', param( 'cleanup_jobs_threshold', 'integer', 0 ) );
					break;

				case 'send-non-activated-account-reminders':
					// Send reminders about non-activated accounts:
					$Settings->set( 'activate_account_reminder_threshold', param_duration( 'activate_account_reminder_threshold' ) );
					// Account activation reminder settings:
					$reminder_config = array();
					$reminder_config_num = param( 'activate_account_reminder_config_num', 'integer', 0 );
					for( $c = 0; $c <= $reminder_config_num; $c++ )
					{
						$reminder_config_value = param_duration( 'activate_account_reminder_config_'.$c );
						if( $reminder_config_value > 0 )
						{	// Store only a selected reminder:
							$reminder_config[ $c ] = $reminder_config_value;
						}
					}
					if( count( $reminder_config ) < 2 )
					{	// If no reminder has been selected:
						param_error( 'activate_account_reminder_config_0', T_('Please select at least one reminder for account activation reminder after subscription.') );
					}
					if( ! isset( $reminder_config[ $reminder_config_num ] ) )
					{	// If "Mark as failed" is not selected:
						param_error( 'activate_account_reminder_config_'.$reminder_config_num, T_('Please select account activation reminder threshold to mark as failed after subscription.') );
					}
					$Settings->set( 'activate_account_reminder_config', implode( ',', $reminder_config ) );
					break;

				case 'send-unmoderated-comments-reminders':
					// Send reminders about comments awaiting moderation:
					$Settings->set( 'comment_moderation_reminder_threshold', param_duration( 'comment_moderation_reminder_threshold' ) );
					break;

				case 'send-unmoderated-posts-reminders':
					// Send reminders about posts awaiting moderation:
					$Settings->set( 'post_moderation_reminder_threshold', param_duration( 'post_moderation_reminder_threshold' ) );
					break;

				case 'send-unread-messages-reminders':
					// Send reminders about unread messages:
					$Settings->set( 'unread_message_reminder_threshold', param_duration( 'unread_message_reminder_threshold' ) );
					// Unread private messages reminder settings:
					$reminder_delay = array();
					$i = 1;
					$prev_reminder_delay_day = 0;
					$prev_reminder_delay_spacing = 0;
					for( $d = 1; $d <= 10; $d++ )
					{
						$reminder_delay_day = param( 'unread_message_reminder_delay_day_'.$d, 'integer', 0 );
						$reminder_delay_spacing = param( 'unread_message_reminder_delay_spacing_'.$d, 'integer', 0 );
						if( $reminder_delay_day > 0 || $reminder_delay_spacing > 0 )
						{	// Store only a filled reminder:
							if( empty( $reminder_delay_day ) )
							{	// If one field is not filled:
								param_error( 'unread_message_reminder_delay_day_'.$i, sprintf( T_('Please fill both fields of the unread private messages reminder #%d.'), $i ) );
								$reminder_delay_day = 0;
							}
							elseif( $prev_reminder_delay_day >= $reminder_delay_day )
							{	// If current value is less than previous:
								param_error( 'unread_message_reminder_delay_day_'.$i, T_('The values of the unread private messages reminder must be ascending.') );
							}
							if( empty( $reminder_delay_spacing ) )
							{	// If one field is not filled:
								param_error( 'unread_message_reminder_delay_spacing_'.$i, sprintf( T_('Please fill both fields of the unread private messages reminder #%d.'), $i ) );
								$reminder_delay_spacing = 0;
							}
							elseif( $prev_reminder_delay_spacing >= $reminder_delay_spacing )
							{	// If current value is less than previous:
								param_error( 'unread_message_reminder_delay_spacing_'.$i, T_('The values of the unread private messages reminder must be ascending.') );
							}
							$reminder_delay[] = $reminder_delay_day.':'.$reminder_delay_spacing;
							$prev_reminder_delay_day = $reminder_delay_day;
							$prev_reminder_delay_spacing = $reminder_delay_spacing;
							$i++;
						}
					}
					if( empty( $reminder_delay ) )
					{	// If no reminder has been selected:
						param_error( 'unread_message_reminder_delay_day_1', T_('Please select at least one reminder for unread private messages.') );
						// Set one empty reminder in order to display all 10 reminders on the error form:
						$reminder_delay[] = '0:0';
					}
					$Settings->set( 'unread_message_reminder_delay', implode( ',', $reminder_delay ) );
					break;
			}
		}

		if( param_errors_detected() )
		{	// Don't store settings if errors:
			break;
		}

		// Update settings:
		$Settings->dbupdate();

		$Messages->add( T_('Scheduler settings have been updated.'), 'success' );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $admin_url.'?ctrl=crontab&tab='.$tab, 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;


	case 'view':
		$cjob_ID = param( 'cjob_ID', 'integer', true );

		$sql =  'SELECT *
							 FROM T_cron__task LEFT JOIN T_cron__log ON ctsk_ID = clog_ctsk_ID
							WHERE ctsk_ID = '.$cjob_ID;
		$cjob_row = $DB->get_row( $sql, OBJECT, 0, 'Get cron job and log' );
		if( empty( $cjob_row ) )
		{
			$Messages->add( sprintf( T_('Job #%d does not exist any longer.'), $cjob_ID ), 'error' );
			$action = 'list';
		}
		break;

	case 'list':
		if( $tab == 'list' )
		{	// Detect timed out tasks:
			detect_timeout_cron_jobs();
		}

		break;
}


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('System'), $admin_url.'?ctrl=system' );
$AdminUI->breadcrumbpath_add( T_('Scheduler'), $admin_url.'?ctrl=crontab' );

// Set an url for manual page:
switch( $action )
{
	case 'new':
	case 'create':
	case 'edit':
	case 'update':
	case 'copy':
		$AdminUI->set_page_manual_link( 'scheduled-job-form' );
		break;
	case 'view':
		$AdminUI->set_page_manual_link( 'scheduled-job-info' );
		break;
	default:
		switch( $tab )
		{
			case 'settings':
				$AdminUI->set_path( 'options', 'cron', 'settings' );
				$AdminUI->set_page_manual_link( 'scheduled-jobs-settings' );
				break;
			case 'test':
				$AdminUI->set_path( 'options', 'cron', 'test' );
				$AdminUI->set_page_manual_link( 'scheduled-jobs-test' );
				break;
			default:
				$AdminUI->set_page_manual_link( 'scheduled-jobs-list' );
		}
		break;
}

if( in_array( $action, array( 'new', 'create', 'edit', 'update', 'copy', 'list' ) ) )
{ // Initialize date picker for cronjob.form.php
	init_datepicker_js();
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

switch( $action )
{
	case 'new':
	case 'create':
	case 'edit':
	case 'update':
	case 'copy':
		// Display VIEW:
		$AdminUI->disp_view( 'cron/views/_cronjob.form.php' );
		break;

	case 'view':
		// Display VIEW:
		$AdminUI->disp_view( 'cron/views/_cronjob.view.php' ); // uses $cjob_row
		break;

	default:
		// Display VIEW:
		switch( $tab )
		{
			case 'settings':
				$AdminUI->disp_view( 'cron/views/_cronjob_settings.form.php' );
				break;
			case 'test':
				// Require this template without function $AdminUI->disp_view() in order to keep all global vars which are used by cron_exec.php:
				require $inc_path.'cron/views/_cronjob_test.view.php';
				break;
			default:
				$AdminUI->disp_view( 'cron/views/_cronjob_list.view.php' );
		}
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>