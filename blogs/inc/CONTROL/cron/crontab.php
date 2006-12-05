<?php
/**
 * This file implements the UI controller for Cron table.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

$AdminUI->set_path( 'tools', 'cron' );

param( 'action', 'string', 'list' );

// We want to remember these params from page to page:
param( 'ctst_pending', 'integer', 0, true );
param( 'ctst_started', 'integer', 0, true );
param( 'ctst_timeout', 'integer', 0, true );
param( 'ctst_error', 'integer', 0, true );
param( 'ctst_finished', 'integer', 0, true );
param( 'results_crontab_order', 'string', '-A', true );
param( 'results_crontab_page', 'integer', 1, true );


// fp> The if  below was the point where THE LINE WAS CROSSED!
// This is bloated here. This has to go into the action handling block (and maybe a function)
// THIS IS NO LONGER CONTROLLER INITIALIZATION. THIS IS ACTION EXECUTION!
// dh> ok. Moved the other param inits above. Ok? I don't think it should be an extra function..

// Init names and params for "static" available jobs and ask Plugins about their jobs:
if( $action == 'new' || $action == 'create' )
{
	// NOTE: keys starting with "plugin_" are reserved for jobs provided by Plugins
	$cron_job_names = array(
			'test' => T_('Basic test job'),
			'error' => T_('Error test job'),
			'anstispam_poll' => T_('Poll the antispam blacklist'),
			'prune_hits_sessions' => T_('Prune old hits & sessions'),
			// post notifications, not user schedulable
		);
	$cron_job_params = array(
			'test' => array(
				'ctrl' => 'cron/_test.job.php',
				'params' => NULL ),
			'error' => array(
				'ctrl' => 'cron/_error_test.job.php',
				'params' => NULL ),
			'anstispam_poll' => array(
				'ctrl' => 'cron/_antispam_poll.job.php',
				'params' => NULL ),
			'prune_hits_sessions' => array(
				'ctrl' => 'cron/_prune_hits_sessions.job.php',
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
		break;

	case 'create':
		// Check that we have permission to edit options:
		$current_User->check_perm( 'options', 'edit', true, NULL );

		// CREATE OBJECT:
		load_class( '/MODEL/cron/_cronjob.class.php' );
		$edited_Cronjob = & new Cronjob();

		$cjob_type = param( 'cjob_type', 'string', true );
		if( !isset( $cron_job_params[$cjob_type] ) )
		{
			param_error( 'cjob_type', T_('Invalid job type') );
		}

		// start datetime:
		param_date( 'cjob_date', T_('Please enter a valid date.'), true );
		param_time( 'cjob_time' );
		$edited_Cronjob->set( 'start_datetime', form_date( get_param( 'cjob_date' ), get_param( 'cjob_time' ) ) );

		// repeat after:
		$cjob_repeat_after_days = param( 'cjob_repeat_after_days', 'integer', 0 );
		$cjob_repeat_after_hours = param( 'cjob_repeat_after_hours', 'integer', 0 );
		$cjob_repeat_after_minutes = param( 'cjob_repeat_after_minutes', 'integer', 0 );
		$cjob_repeat_after = ( ( ($cjob_repeat_after_days*24) + $cjob_repeat_after_hours )*60 + $cjob_repeat_after_minutes)*60; // seconds
		if( $cjob_repeat_after == 0 )
		{
			$cjob_repeat_after = NULL;
		}
		$edited_Cronjob->set( 'repeat_after', $cjob_repeat_after );

		// name:
		$edited_Cronjob->set( 'name', $cron_job_names[$cjob_type] );

		// controller:
		$edited_Cronjob->set( 'controller', $cron_job_params[$cjob_type]['ctrl'] );

		// params:
		$edited_Cronjob->set( 'params', $cron_job_params[$cjob_type]['params'] );

		if( ! param_errors_detected() )
		{	// No errors

			// Save to DB:
			$edited_Cronjob->dbinsert();

			$Messages->add( T_('New job has been scheduled.'), 'success' );

			$action = 'list';
		}
		break;

	case 'delete':
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

		forget_param( 'ctsk_ID' );
		$action = 'list';
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
		$sql = ' UPDATE T_cron__log
								SET clog_status = "timeout"
							WHERE clog_status = "started"
										AND clog_realstart_datetime < '.$DB->quote( date2mysql( time() + $time_difference - $cron_timeout_delay ) );
		$DB->query( $sql, 'Detect cron timeouts.' );

		break;
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
		// Display VIEW:
		$AdminUI->disp_view( 'cron/_cronjob.form.php' );
		break;

	case 'view':
		// Display VIEW:
		$AdminUI->disp_view( 'cron/_cronjob.sheet.php' ); // uses $cjob_row
		break;

	default:
		// Display VIEW:
		$AdminUI->disp_view( 'cron/_crontab.list.php' );
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.15  2006/12/05 04:27:49  fplanque
 * moved scheduler to Tools (temporary until UI redesign)
 *
 * Revision 1.14  2006/11/26 01:42:08  fplanque
 * doc
 *
 */
?>