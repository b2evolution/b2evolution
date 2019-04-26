<?php
/**
 * This file implements the clean up of email logs older than x months
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $DB, $servertimenow, $Settings;

/**
 * The scheduled jobs older than X days will be removed
 */
$threshold_date = date2mysql( $servertimenow - $Settings->get( 'cleanup_email_logs_threshold' ) );

$affected_rows = $DB->query( 'DELETE FROM T_email__log WHERE emlog_timestamp < '.$DB->quote( $threshold_date ) );

if( $affected_rows == 0 )
{	// No old email logs deleted
	cron_log_append( T_('No email log records deleted.') );
}
else
{
	cron_log_append( sprintf( T_('%d email log records were deleted.'), $affected_rows ), 'success' );

	// Save a number of the deleted cron email logs:
	cron_log_report_action_count( $affected_rows );
}

return 1; /* ok */

?>