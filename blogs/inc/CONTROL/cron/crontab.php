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
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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

$cron_job_names = array(
		'test' => T_('Basic test job'),
		'error' => T_('Error test job'),
		'anstispam_poll' => T_('Poll the antispam blacklist'),
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
	);


$AdminUI->set_path( 'cron' );

param( 'action', 'string', 'list' );

// We want to remember these params from page to page:
param( 'ctst_pending', 'integer', 0, true );
param( 'ctst_started', 'integer', 0, true );
param( 'ctst_timeout', 'integer', 0, true );
param( 'ctst_error', 'integer', 0, true );
param( 'ctst_finished', 'integer', 0, true );
param( 'results_crontab_order', 'string', '-A', true );
param( 'results_crontab_page', 'integer', 1, true );


switch( $action )
{
	case 'new':
		// Check that we have permission to edit options:
		$current_User->check_perm( 'options', 'edit', true, NULL );
		break;

	case 'create':
		// Check that we have permission to edit options:
		$current_User->check_perm( 'options', 'edit', true, NULL );

		// TODO: Use Cronjob object

		$cjob_type = $Request->param( 'cjob_type', 'string', true );
		if( !isset( $cron_job_params[$cjob_type] ) )
		{
			$Request->param_error( 'cjob_type', T_('Invalid job type') );
		}
		$Request->param_date( 'cjob_date', T_('Please enter a valid date.'), true );
		$Request->param_time( 'cjob_time' );
		$cjob_datetime = form_date( $Request->get( 'cjob_date' ), $Request->get( 'cjob_time' ) );

		// duration -> end date (deadline)
		$cjob_repeat_after_days = $Request->param( 'cjob_repeat_after_days', 'integer', 0 );
		$cjob_repeat_after_hours = $Request->param( 'cjob_repeat_after_hours', 'integer', 0 );
		$cjob_repeat_after_minutes = $Request->param( 'cjob_repeat_after_minutes', 'integer', 0 );
		$cjob_repeat_after = ( ( ($cjob_repeat_after_days*24) + $cjob_repeat_after_hours )*60 + $cjob_repeat_after_minutes)*60; // seconds
		if( $cjob_repeat_after == 0 )
		{
			$cjob_repeat_after = NULL;
		}

		if( ! $Request->validation_errors() )
		{	// No errors
      $sql = 'INSERT INTO T_cron__task( ctsk_start_datetime, ctsk_repeat_after, ctsk_name, ctsk_controller, ctsk_params )
							VALUES( '.$DB->quote($cjob_datetime).', '.$DB->null($cjob_repeat_after).' , '.$DB->quote($cron_job_names[$cjob_type]).', '
												.$DB->quote($cron_job_params[$cjob_type]['ctrl']).', '.$DB->quote(serialize($cron_job_params[$cjob_type]['params'])).' )';
			$DB->query( $sql, 'Insert test task' );

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

			$Messages->add(  sprintf( T_('Scheduled task #%d deleted.'), $ctsk_ID ), 'success' );
		}

		forget_param( 'ctsk_ID' );
		$action = 'list';
		break;


	case 'view':
		$cjob_ID = $Request->param( 'cjob_ID', 'integer', true );

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
		$AdminUI->disp_view( 'cron/_cronjob.sheet.php' );
		break;

	default:
		// Display VIEW:
		$AdminUI->disp_view( 'cron/_crontab.list.php' );
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();
?>