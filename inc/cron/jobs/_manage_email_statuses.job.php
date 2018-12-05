<?php
/**
 * This file implements the managing of email address statuses
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $Settings, $DB, $servertimenow;

$email_addresses_num = $DB->query( 'UPDATE T_email__address
	  SET emadr_status = "unknown"
	WHERE emadr_status IN ( "warning", "suspicious1", "suspicious2", "suspicious3" )
	  AND ( emadr_last_error_ts IS NULL OR emadr_last_error_ts <= '.$DB->quote( date2mysql( $servertimenow - $Settings->get( 'manage_email_statuses_min_delay' ) ) ).' )
	  AND emadr_sent_last_returnerror >= '.intval( $Settings->get( 'manage_email_statuses_min_sends' ) ),
	'Update Warning and Suspicious email addresses to Unknown status' );

if( $email_addresses_num == 0 )
{	// No updated email addresses:
	cron_log_append( sprintf( T_('No email addresses found which may be updated to %s status.'), '<code>'.T_('Unknown').'</code>' ) );
}
else
{	// If at least one email address is updated:
	cron_log_action_end( sprintf( T_('%d email addresses have been updated to the status: %s.'), $email_addresses_num, '<code>'.T_('Unknown').'</code>' ) );
}

return 1; // success

?>