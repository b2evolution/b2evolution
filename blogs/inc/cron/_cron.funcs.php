<?php
/**
 * This file implements cron (scheduled tasks) handling functions.
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _cron.funcs.php 7347 2014-10-01 11:52:15Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Log a message from cron.
 * @param string Message
 * @param integer Level of importance. The higher the more important.
 *        (if $quiet (number of "-q" params passed to cron_exec.php)
 *         is higher than this, the message gets skipped)
 */
function cron_log( $message, $level = 0 )
{
	global $is_web, $quiet;

	if( $quiet > $level )
	{
		return;
	}

	if( $is_web )
	{
		echo '<p>'.$message.'</p>';
	}
	else
	{
		echo "\n".$message."\n";
	}
}


/**
 * Call a cron job.
 *
 * @param string Name of the job
 * @param string Params for the job:
 *               'ctsk_ID'   - task ID
 *               'ctsk_name' - task name
 * @return string Error message
 */
function call_job( $job_name, $job_params = array() )
{
	global $DB, $inc_path, $Plugins, $admin_url;

	global $result_message, $result_status, $timestop, $time_difference;

	$error_message = '';
	$result_message = NULL;
	$result_status = 'error';

	apm_name_transaction( 'CRON/'.$job_name );

	if( preg_match( '~^plugin_(\d+)_(.*)$~', $job_name, $match ) )
	{ // Cron job provided by a plugin:
		if( ! is_object($Plugins) )
		{
			load_class( 'plugins/model/_plugins.class.php', 'Plugins' );
			$Plugins = new Plugins();
		}

		$Plugin = & $Plugins->get_by_ID( $match[1] );
		if( ! $Plugin )
		{
			$result_message = 'Plugin for controller ['.$job_name.'] could not get instantiated.';
			cron_log( $result_message, 2 );
			return $result_message;
		}

		// CALL THE PLUGIN TO HANDLE THE JOB:
		$tmp_params = array( 'ctrl' => $match[2], 'params' => $job_params );
		$sub_r = $Plugins->call_method( $Plugin->ID, 'ExecCronJob', $tmp_params );

		$error_code = (int)$sub_r['code'];
		$result_message = $sub_r['message'];
	}
	else
	{
		$controller = $inc_path.$job_name;
		if( ! is_file( $controller ) )
		{
			$result_message = 'Controller ['.$job_name.'] does not exist.';
			cron_log( $result_message, 2 );
			return $result_message;
		}

		// INCLUDE THE JOB FILE AND RUN IT:
		$error_code = require $controller;
	}

	if( is_array( $result_message ) )
	{	// If result is array (we should store it as serialized data later)
		// array keys: 'message' - Result message
		//             'table_cols' - Columns names of the table to display on the Execution details of the cron job log
		//             'table_data' - Array
		$result_message_text = $result_message['message'];
	}
	else
	{	// Result is text string
		$result_message_text = $result_message;
	}

	if( $error_code != 1 )
	{	// We got an error
		$result_status = 'error';
		$result_message_text = '[Error code: '.$error_code.' ] '.$result_message_text;
		if( is_array( $result_message ) )
		{ // If result is array
			$result_message['message'] = $result_message_text;
		}
		$cron_log_level = 2;

		$error_message = $result_message_text;
	}
	else
	{
		$result_status = 'finished';
		$cron_log_level = 1;
	}

	$timestop = time() + $time_difference;
	cron_log( 'Task finished at '.date( 'H:i:s', $timestop ).' with status: '.$result_status
		."\nMessage: $result_message_text", $cron_log_level );

	return $error_message;
}


/**
 * Get status color of sheduled job by status value
 *
 * @param string Status value
 * @return string Color value
 */
function cron_status_color( $status )
{
	$colors = array(
			'pending'  => '808080',
			'started'  => 'FFFF00',
			'finished' => '008000',
			'error'    => 'FF0000',
			'timeout'  => 'FFA500',
		);

	return isset( $colors[ $status ] ) ? '#'.$colors[ $status ] : 'none';
}


/**
 * Get the manual page link for the requested cron job, given by the controller path
 *
 * @param string job ctrl path
 * @return string NULL when the corresponding manual topic was not defined, the manual link otherwise
 */
function cron_job_manual_link( $job_ctrl )
{
	$manual_topics = array(
		'cron/jobs/_activate_account_reminder.job.php' => 'task-send-non-activated-account-reminders',
		'cron/jobs/_antispam_poll.job.php' => 'task-poll-antispam-blacklist',
		'cron/jobs/_cleanup_jobs.job.php' => 'task-cleanup-scheduled-jobs',
		'cron/jobs/_comment_moderation_reminder.job.php' => 'task-send-unmoderated-comments-reminders',
		'cron/jobs/_post_moderation_reminder.job.php' => 'task-send-unmoderated-posts-reminders',
		'cron/jobs/_comment_notifications.job.php' => 'task-send-comment-notifications',
		'cron/jobs/_decode_returned_emails.job.php' => 'task-process-return-path-inbox',
		'cron/jobs/_error_test.job.php' => 'task-error-test',
		'cron/jobs/_heavy_db_maintenance.job.php' => 'task-heavy-db-maintenance',
		'cron/jobs/_light_db_maintenance.job.php' => 'task-light-db-maintenance',
		'cron/jobs/_post_by_email.job.php' => 'task-create-post-by-email',
		'cron/jobs/_post_notifications.job.php' => 'task-send-post-notifications',
		'cron/jobs/_process_hitlog.job.php' => 'task-process-hit-log',
		'cron/jobs/_prune_hits_sessions.job.php' => 'task-prune-old-hits-and-sessions',
		'cron/jobs/_prune_page_cache.job.php' => 'task-prune-old-files-from-page-cache',
		'cron/jobs/_prune_recycled_comments.job.php' => 'task-prune-recycled-comments',
		'cron/jobs/_test.job.php' => 'task-test',
		'cron/jobs/_unread_message_reminder.job.php' => 'task-send-unread-messages-reminders',
	);

	if( isset( $manual_topics[$job_ctrl] ) )
	{ // return the corresponding manual page topic
		return get_manual_link( $manual_topics[$job_ctrl] );
	}

	// The topic was not defined
	return NULL;
}


/**
 * Detect timed out cron jobs and Send notifications
 *
 * @param array Task with error
 *             'name'
 *             'message'
 */
function detect_timeout_cron_jobs( $error_task = NULL )
{
	global $DB, $time_difference, $cron_timeout_delay, $admin_url;

	$SQL = new SQL( 'Find cron timeouts' );
	$SQL->SELECT( 'ctsk_ID, ctsk_name' );
	$SQL->FROM( 'T_cron__log' );
	$SQL->FROM_add( 'INNER JOIN T_cron__task ON ctsk_ID = clog_ctsk_ID' );
	$SQL->WHERE( 'clog_status = "started"' );
	$SQL->WHERE_and( 'clog_realstart_datetime < '.$DB->quote( date2mysql( time() + $time_difference - $cron_timeout_delay ) ) );
	$SQL->GROUP_BY( 'ctsk_ID' );
	$timeouts = $DB->get_assoc( $SQL->get(), OBJECT, $SQL->title );

	$tasks = array();

	if( count( $timeouts ) > 0 )
	{
		foreach( $timeouts as $task_ID => $task_name )
		{
			$tasks[ $task_ID ] = array(
					'name'    => $task_name,
					'message' => T_('Cron job was timed out.'),
				);
		}

		// Update timed out cron jobs
		$DB->query( 'UPDATE T_cron__log
			  SET clog_status = "timeout"
			WHERE clog_ctsk_ID IN ( '.$DB->quote( array_keys( $tasks ) ).' )', 'Detect cron timeouts.' );
	}

	if( !is_null( $error_task ) )
	{ // Send notification with error task
		$tasks[ $error_task['ID'] ] = $error_task;
	}

	if( count( $tasks ) > 0 )
	{ // Send notification email about timed out and error cron jobs to users with edit options permission
		$email_template_params = array(
				'tasks' => $tasks,
			);
		send_admin_notification( NT_('Scheduled task error'), 'scheduled_task_error_report', $email_template_params );
	}
}

?>