<?php
/**
 * This file implements the inactive account email reminder cron job
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $DB, $UserSettings, $Settings;

global $servertimenow, $baseurl;

if( empty( $UserSettings ) )
{ // initialize UserSettings, because in CLI mode is not initialized yet
	load_class( 'users/model/_usersettings.class.php', 'UserSettings' );
	$UserSettings = new UserSettings();
}

// Only users with "new", "emailchanged" OR "deactivated" statuses may receive activation reminders
// This will be a precondition to get less users from db, but this will be checked again with check_status() in the send_easy_validate_emails() function
$status_condition = '( user_status = "activated" OR user_status = "manualactivated" OR user_status = "autoactivated" )';

$SQL = new SQL();
$SQL->SELECT( 'T_users.*' );
$SQL->FROM( 'T_users' );
$SQL->FROM_add( 'LEFT JOIN T_users__usersettings last_sent ON last_sent.uset_user_ID = user_ID AND last_sent.uset_name = "last_inactive_status_email"' );
$SQL->FROM_add( 'LEFT JOIN T_users__usersettings notif_setting ON notif_setting.uset_user_ID = user_ID AND notif_setting.uset_name = "send_inactive_reminder"' );

// check that user status is 'new' or 'emailchanged' or 'deactivated', and send reminders only for these users.
$SQL->WHERE( $status_condition );
// check if user has an email address
$SQL->WHERE_and( 'LENGTH(TRIM(user_email)) > 0' );
// check that user email is not blocked
$SQL->WHERE_and( 'user_email NOT IN ( SELECT emadr_address FROM T_email__address WHERE '.get_mail_blocked_condition().' )' );
// check that user has not yet received an inactive email notification
$SQL->WHERE_and( 'last_sent.uset_value IS NULL' );
// check if user wants to receive inactive reminder or not
$SQL->WHERE_and( 'notif_setting.uset_value IS NULL OR notif_setting.uset_value <> '.$DB->quote( '0' ) );
// check if user last seen timestamp exceeds threshold for inactive users
$SQL->WHERE_and( 'TIMESTAMPDIFF( SECOND, IF( user_lastseen_ts IS NULL, user_created_datetime, user_lastseen_ts ), '.$DB->quote( date2mysql( $servertimenow ) ).' ) > '.$Settings->get( 'inactive_account_reminder_threshold' ) );

$UserCache = & get_UserCache();
$UserCache->clear();
// load all users to reminded into the UserCache
$UserCache->load_by_sql( $SQL );

// Send inactive reminder to every user loaded into the UserCache ( there are only not activated users )
$reminder_sent = send_inactive_user_emails( $UserCache->get_ID_array(), NULL, 'cron_job' );

cron_log_append( ( empty( $result_message ) ? '' : "\n" ).sprintf( T_( '%d account inactive reminder emails were sent!' ), $reminder_sent ) );
return 1; /* ok */
?>