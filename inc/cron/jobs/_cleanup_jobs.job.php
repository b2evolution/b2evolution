<?php
/**
 * This file implements the Clean up scheduled jobs older than x days
 *
 * @author fplanque: Francois PLANQUE
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $DB, $servertimenow, $Settings;

/**
 * The scheduled jobs older than X days will be removed
 */
$success_days_in_seconds = $Settings->get( 'cleanup_jobs_threshold' ) * 86400; // x days * 'seconds in one day'
$failed_days_in_seconds = $Settings->get( 'cleanup_jobs_threshold_failed' ) * 86400; // x days * 'seconds in one day'


// Get IDs of jobs to delete them from DB
$SQL = new SQL( 'Get the scheduled jobs older than 45 days' );
$SQL->SELECT( 'clog_ctsk_ID' );
$SQL->FROM( 'T_cron__log' );
$SQL->WHERE( '( clog_status = "finished" AND clog_realstart_datetime < '.$DB->quote( date2mysql( $servertimenow - $success_days_in_seconds ) ).' )' );
$SQL->WHERE_or( 'clog_status != "finished" AND clog_realstart_datetime < '.$DB->quote( date2mysql( $servertimenow - $failed_days_in_seconds ) ) );
$jobs = $DB->get_col( $SQL );


if( count( $jobs ) == 0 )
{	// No old jobs
	cron_log_append( T_('No scheduled jobs found'), 'warning' );
}
else
{
	// Delete jobs
	$DB->query( 'DELETE FROM T_cron__task
		WHERE ctsk_ID IN ( '.$DB->quote( $jobs ).' )' );

	// Delete the logs of jobs
	$deleted_logs_num = $DB->query( 'DELETE FROM T_cron__log
		WHERE clog_ctsk_ID IN ( '.$DB->quote( $jobs ).' )' );

	cron_log_append( sprintf( T_('%s scheduled jobs were deleted.'), $deleted_logs_num ) );

	// Save a number of the deleted cron job logs:
	cron_log_report_action_count( $deleted_logs_num );
}

return 1; /* ok */

?>