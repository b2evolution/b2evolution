<?php
/**
 * This file implements sending of deferred email notifications Cron controller
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Messages, $Settings, $email_send_simulate_only;

cron_log_append( 'Retrieving queued email notifications...' );
$SQL = new SQL();
$SQL->SELECT( 'emlog_ID, emlog_to, emlog_subject, emlog_message, emlog_headers, emlog_user_ID, emlog_camp_ID, emlog_autm_ID' );
$SQL->FROM( 'T_email__log' );
$SQL->WHERE( 'emlog_result = "ready_to_send"' );
$SQL->ORDER_BY( 'emlog_timestamp ASC' );
$SQL->LIMIT( $Settings->get( 'email_notifications_chunk_size' ) );

$email_notifications = $DB->get_results( $SQL, ARRAY_A, 'Get deferred email notifications' );

cron_log_append( sprintf( 'Sending %d email notifications...', count( $email_notifications ) )."\n" );
foreach( $email_notifications as $notification )
{
	$message_data = unserialize( $notification['emlog_message'] );

	$to                = $notification['emlog_to'];
	$to_name           = $message_data === false ? $message_data['to_name'] : NULL;
	$subject           = $notification['emlog_subject'];
	$message           = $message_data === false ? $notification['emlog_message'] : $message_data;
	$from              = $message_data === false ? $message_data['from_email'] : NULL;
	$from_name         = $message_data === false ? $message_data['from_name'] : NULL;
	$headers           = parse_mail_headers( $notification['emlog_headers'] );
	$user_ID           = $notification['emlog_user_ID'];
	$email_campaign_ID = $notification['emlog_camp_ID'];
	$automation_ID     = $notification['emlog_autm_ID'];
	$mail_log_ID       = $notification['emlog_ID'];

	if( send_mail( $to, $to_name, $subject, $message, $from, $from_name, $headers, $user_ID, $email_campaign_ID, $automation_ID, $mail_log_ID ) )
	{
		$result = 'success';
	}
	else
	{
		$result = 'failed';
	}

	cron_log_append( sprintf( 'Sending email #%d: %s', $mail_log_ID, $result ) );

	// Store messages of the post sending email notifications:
	cron_log_action_end( $Messages->get_string( '', '', "\n", '' ) );
	$Messages->clear();
}

if( empty( $result_message ) )
{
	cron_log_append( T_('Done').'.' );
}

return 1; /* ok */
?>