<?php
/**
 * This file implements the UI controller for Cron table.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: cronjobs.ctrl.php 7948 2015-01-12 10:35:50Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_funcs( 'cron/_cron.funcs.php' );

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

$AdminUI->set_path( 'options', 'cron' );

param( 'action', 'string', 'list' );

// We want to remember these params from page to page:
param( 'ctst_pending', 'integer', 0, true );
param( 'ctst_started', 'integer', 0, true );
param( 'ctst_timeout', 'integer', 0, true );
param( 'ctst_error', 'integer', 0, true );
param( 'ctst_finished', 'integer', 0, true );
param( 'results_crontab_order', 'string', '-D', true );
param( 'results_crontab_page', 'integer', 1, true );


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

// fp> The if  below was the point where THE LINE WAS CROSSED!
// This is bloated here. This has to go into the action handling block (and maybe a function)
// THIS IS NO LONGER CONTROLLER INITIALIZATION. THIS IS ACTION EXECUTION!
// dh> ok. Moved the other param inits above. Ok? I don't think it should be an extra function..

// Init names and params for "static" available jobs and ask Plugins about their jobs:
if( in_array( $action, array( 'new', 'create', 'edit', 'update', 'copy' ) ) )
{
	// NOTE: keys starting with "plugin_" are reserved for jobs provided by Plugins
	$cron_job_names = array(
			'test' => T_('Basic test job'),
			'error' => T_('Error test job'),
			'anstispam_poll' => T_('Poll the antispam blacklist'),
			'prune_hits_sessions' => T_('Prune old hits & sessions (includes OPTIMIZE)'),
			'prune_page_cache' => T_('Prune old files from page cache'),
			'post_by_email' => T_('Create posts by email'),
			'process_hitlog' => T_('Extract info from hit log'),
			'unread_message_reminder' => T_( 'Send reminders about unread messages' ),
			'activate_account_reminder' => T_( 'Send reminders about non-activated accounts' ),
			'comment_moderation_reminder' => T_( 'Send reminders about comments awaiting moderation' ),
			'post_moderation_reminder' => T_( 'Send reminders about posts awaiting moderation' ),
			'return_path' => T_('Process the return path inbox'),
			'light_db_maintenance' => T_('Light DB maintenance (ANALYZE)'),
			'heavy_db_maintenance' => T_('Heavy DB maintenance (CHECK & OPTIMIZE)'),
			'prune_recycled_comments' => T_('Prune recycled comments'),
			// post notifications, not user schedulable
			// comment notifications, not user schedulable
		);
	$cron_job_params = array(
			'test' => array(
				'ctrl' => 'cron/jobs/_test.job.php',
				'params' => NULL ),
			'error' => array(
				'ctrl' => 'cron/jobs/_error_test.job.php',
				'params' => NULL ),
			'anstispam_poll' => array(
				'ctrl' => 'cron/jobs/_antispam_poll.job.php',
				'params' => NULL ),
			'prune_hits_sessions' => array(
				'ctrl' => 'cron/jobs/_prune_hits_sessions.job.php',
				'params' => NULL ),
			'prune_page_cache' => array(
				'ctrl' => 'cron/jobs/_prune_page_cache.job.php',
				'params' => NULL ),
			'post_by_email' => array(
				'ctrl' => 'cron/jobs/_post_by_email.job.php',
				'params' => NULL ),
			'process_hitlog' => array(
				'ctrl' => 'cron/jobs/_process_hitlog.job.php',
				'params' => NULL ),
			'unread_message_reminder' => array(
				'ctrl' => 'cron/jobs/_unread_message_reminder.job.php',
				'params' => NULL ),
			'activate_account_reminder' => array(
				'ctrl' => 'cron/jobs/_activate_account_reminder.job.php',
				'params' => NULL ),
			'comment_moderation_reminder' => array(
				'ctrl' => 'cron/jobs/_comment_moderation_reminder.job.php',
				'params' => NULL ),
			'post_moderation_reminder' => array(
				'ctrl' => 'cron/jobs/_post_moderation_reminder.job.php',
				'params' => NULL ),
			'return_path' => array(
				'ctrl' => 'cron/jobs/_decode_returned_emails.job.php',
				'params' => NULL ),
			'light_db_maintenance' => array(
				'ctrl' => 'cron/jobs/_light_db_maintenance.job.php',
				'params' => NULL ),
			'heavy_db_maintenance' => array(
				'ctrl' => 'cron/jobs/_heavy_db_maintenance.job.php',
				'params' => NULL ),
			'prune_recycled_comments' => array(
				'ctrl' => 'cron/jobs/_prune_recycled_comments.job.php',
				'params' => NULL ),
			// post notifications, not user schedulable
		);

	// Get additional jobs from Plugins:
	foreach( $Plugins->trigger_collect( 'GetCronJobs' ) as $plug_ID => $jobs )
	{
		if( ! is_array($jobs) )
		{
			$Debuglog->add( sprintf('GetCronJobs() for plugin #%d did not return array. Ignoring its jobs.', $plug_ID), array('plugins', 'error') );
			continue;
		}
		foreach( $jobs as $job )
		{
			// Validate params from plugin:
			if( ! isset($job['params']) )
			{
				$job['params'] = NULL;
			}
			if( ! is_array($job) || ! isset($job['ctrl'], $job['name']) )
			{
				$Debuglog->add( sprintf('GetCronJobs() for plugin #%d did return invalid job. Ignoring.', $plug_ID), array('plugins', 'error') );
				continue;
			}
			if( isset($job['params']) && ! is_array($job['params']) )
			{
				$Debuglog->add( sprintf('GetCronJobs() for plugin #%d did return invalid job params (not an array). Ignoring.', $plug_ID), array('plugins', 'error') );
				continue;
			}
			$ctrl_id = 'plugin_'.$plug_ID.'_'.$job['ctrl'];

			$cron_job_names[$ctrl_id] = $job['name'];
			$cron_job_params[$ctrl_id] = array(
					'ctrl' => $ctrl_id,
					'params' => $job['params'],
				);
		}
	}
}


switch( $action )
{
	case 'new':
		// Check that we have permission to edit options:
		$current_User->check_perm( 'options', 'edit', true, NULL );

		load_class( 'cron/model/_cronjob.class.php', 'Cronjob' );
		$edited_Cronjob = new Cronjob();
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
		{	// It is a copy action, we should save the fields "controller" & "params"
			$ctsk_controller = $edited_Cronjob->get( 'controller' );
			$ctsk_params = $edited_Cronjob->get( 'params' );
		}

		// CREATE OBJECT:
		load_class( '/cron/model/_cronjob.class.php', 'Cronjob' );
		$edited_Cronjob = new Cronjob();

		if( $edited_Cronjob->load_from_Request( $cron_job_names, $cron_job_params ) )
		{	// We could load data from form without errors:

			if( !empty( $ctsk_controller ) )
			{	// Save controller field from copied object
				$edited_Cronjob->set( 'controller', $ctsk_controller );
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

		if( $edited_Cronjob->load_from_Request( $cron_job_names, $cron_job_params ) )
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
		// Detect timed out tasks:
		detect_timeout_cron_jobs();

		break;
}


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('System'), '?ctrl=system' );
$AdminUI->breadcrumbpath_add( T_('Scheduler'), '?ctrl=crontab' );

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
		$AdminUI->disp_view( 'cron/views/_cronjob_list.view.php' );
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>
