<?php
/**
 * This file implements the account activation email reminder cron job
 *
 * @author attila: Attila Simo
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $DB, $UserSettings, $Settings;

global $servertimenow, $baseurl, $secure_htsrv_url;

if( $Settings->get( 'validation_process' ) != 'easy' )
{
	$result_message = sprintf( T_( 'With secure activation process sending reminder emails is not permitted!' ) );
	return 2; /* error */
}

if( empty( $UserSettings ) )
{ // initialize UserSettings, because in CLI mode is not initialized yet
	load_class( 'users/model/_usersettings.class.php', 'UserSettings' );
	$UserSettings = new UserSettings();
}

// Load required functions ( we need to load here, because in CLI mode it is not loaded )
load_funcs( '_core/_url.funcs.php' );

// We want to send an activation reminder after +1, +2.5 and +7 days after the new user was registered,
// or the user status was changed to new, deactivated or emailchanged status
$one_day_ago = date2mysql( $servertimenow - 86400/* 60*60*24 = 24 hour*/ );
$one_and_half_days_ago = date2mysql( $servertimenow - 129600/* 60*60*24*1.5 = 60 hour*/ );
$four_and_half_days_ago = date2mysql( $servertimenow - 388800/* 60*60*24*4.5 = 4.5 days*/ );
$seven_days_ago = date2mysql( $servertimenow - 604800/* 60*60*24*7 = 7 days*/ );
// Only users with "new", "emailchanged" OR "deactivated" statuses may receive activation reminders
// This will be a precondition to get less users from db, but this will be checked again with check_status() in the send_easy_validate_emails() function
$status_condition = '( user_status = "new" OR user_status = "emailchanged" OR user_status = "deactivated" )';

$SQL = new SQL();
$SQL->SELECT( 'T_users.*' );
$SQL->FROM( 'T_users' );
// join UserSettings
$SQL->FROM_add( 'LEFT JOIN T_users__usersettings last_sent ON last_sent.uset_user_ID = user_ID AND last_sent.uset_name = "last_activation_email"' );
$SQL->FROM_add( 'LEFT JOIN T_users__usersettings notif_setting ON notif_setting.uset_user_ID = user_ID AND notif_setting.uset_name = "send_activation_reminder"' );
$SQL->FROM_add( 'LEFT JOIN T_users__usersettings reminder_sent ON reminder_sent.uset_user_ID = user_ID AND reminder_sent.uset_name = "activation_reminder_count"' );
// check that user status is 'new' or 'emailchanged' or 'deactivated', and send reminders only for these users.
$SQL->WHERE( $status_condition );
// check if user has an email address
$SQL->WHERE_and( 'LENGTH(TRIM(user_email)) > 0' );
// check that user email is not blocked
$SQL->WHERE_and( 'user_email NOT IN ( SELECT emblk_address FROM T_email__blocked WHERE '.get_mail_blocked_condition().' )' );
// check that user was created more then a day ago!
$SQL->WHERE_and( 'user_created_datetime < '.$DB->quote( $one_day_ago ) );
// check how many reminders was sent to the user and when => send reminders only if required
$SQL->WHERE_and( '(
	( ( last_sent.uset_value IS NULL OR last_sent.uset_value < '.$DB->quote( $one_day_ago ).' ) AND ( reminder_sent.uset_value IS NULL OR reminder_sent.uset_value = "0" ) )
	OR ( last_sent.uset_value < '.$DB->quote( $one_and_half_days_ago ).' AND reminder_sent.uset_value = "1" )
	OR ( last_sent.uset_value < '.$DB->quote( $four_and_half_days_ago ).' AND reminder_sent.uset_value = "2" ) )' );
// check if user wants to recevice activation reminder or not
$SQL->WHERE_and( 'notif_setting.uset_value IS NULL OR notif_setting.uset_value <> '.$DB->quote( '0' ) );

$UserCache = & get_UserCache();
$UserCache->clear();
// load all users to reminded into the UserCache
$UserCache->load_by_sql( $SQL );

// Send activation reminder to every user loaded into the UserCache ( there are only not activated users )
$reminder_sent = send_easy_validate_emails( $UserCache->get_ID_array() );

// Set failed activation status for all users who didn't receive activation reminder or account validation email in the last seven days,
// and user was created more then a week, and have received at least one activation email.
$DB->query( 'UPDATE T_users
		LEFT JOIN T_users__usersettings ON uset_user_ID = user_ID
		SET user_status = "failedactivation"
		WHERE ( uset_name = "last_activation_email" AND uset_value IS NOT NULL AND uset_value < '.$DB->quote( $seven_days_ago ).' )
			AND ( user_created_datetime < '.$DB->quote( $seven_days_ago ).' ) AND '.$status_condition );

$result_message = sprintf( T_( '%d account activation reminder emails were sent!' ), $reminder_sent );
return 1; /* ok */
?>