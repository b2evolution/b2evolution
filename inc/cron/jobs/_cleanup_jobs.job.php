<?php
/**
 * This file implements the Clean up scheduled jobs older than x days
 *
 * @author fplanque: Francois PLANQUE
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $DB, $result_message, $servertimenow, $cleanup_jobs_threshold;

/**
 * The scheduled jobs older than X days will be removed
 */
$days_in_seconds = $cleanup_jobs_threshold * 86400; // x days * 'seconds in one day'

/**
 * The scheduled jobs with this status will be removed
 */
$status = 'finished';


// Get IDs of jobs to delete them from DB
$SQL = new SQL( 'Get the scheduled jobs older than 45 days' );
$SQL->SELECT( 'clog_ctsk_ID' );
$SQL->FROM( 'T_cron__log' );
$SQL->WHERE( 'clog_status = '.$DB->quote( $status ) );
$SQL->WHERE_and( 'clog_realstart_datetime < '.$DB->quote( date2mysql( $servertimenow - $days_in_seconds ) ) );
$jobs = $DB->get_col( $SQL->get() );


if( count( $jobs ) == 0 )
{	// No old jobs
	$result_message = T_('No scheduled jobs found');
}
else
{
	// Delete jobs
	$DB->query( 'DELETE FROM T_cron__task
		WHERE ctsk_ID IN ( '.$DB->quote( $jobs ).' )' );

	// Delete the logs of jobs
	$DB->query( 'DELETE FROM T_cron__log
		WHERE clog_ctsk_ID IN ( '.$DB->quote( $jobs ).' )' );

	$result_message = sprintf( T_('%s scheduled jobs were deleted.'), count( $jobs ) );
}

return 1; /* ok */

?>