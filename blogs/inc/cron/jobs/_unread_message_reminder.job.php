<?php
/**
 * This file implements the unread messages email reminder cron job
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $DB, $UserSettings, $Settings;

global $servertimenow, $htsrv_url;

$one_day_ago = date2mysql( $servertimenow - 86400/* 60*60*24 = 24 hour*/ );
$three_days_ago = date2mysql( $servertimenow - 259200/* 60*60*24*3 = 72 hour*/ );

if (empty($UserSettings))
{ // initialize UserSettings
	load_class( 'users/model/_usersettings.class.php', 'UserSettings' );
	$UserSettings = new UserSettings();
}

if (empty($Settings))
{ // initialize GeneralSettings
	load_class( 'settings/model/_generalsettings.class.php', 'GeneralSettings' );
	$Settings = new GeneralSettings();
}

// set condition to check if user wants to recive unread messages reminders
$notify_condition = 'notif_setting.uset_value <> '.$DB->quote( '0' );
if( $Settings->get( 'def_notify_unread_messages' ) )
{ // by default everybody should be notified, so notify those users who didn't explicitly set to not receive notifications
	$notify_condition .= ' OR notif_setting.uset_value IS NULL';
}

// Find user who have unread messages created more than 24 hours ago, and have not been reminded in the last 72 hours
// We needs to send reminder for these users, but the message must include all unread threads even if the last unread messages was created less then 24 hours
$query = 'SELECT DISTINCT user_ID
		FROM T_users
			INNER JOIN T_messaging__threadstatus ON tsta_user_ID = user_ID
			INNER JOIN T_messaging__message ON tsta_first_unread_msg_ID = msg_ID
			LEFT JOIN T_users__usersettings last_sent ON last_sent.uset_user_ID = user_ID AND last_sent.uset_name = "last_unread_messages_reminder"
			LEFT JOIN T_users__usersettings notif_setting ON notif_setting.uset_user_ID = user_ID AND notif_setting.uset_name = "notify_unread_messages"
		WHERE ( msg_datetime < '.$DB->quote( $one_day_ago ).' )
			AND ( last_sent.uset_value IS NULL OR last_sent.uset_value < '.$DB->quote( $three_days_ago ).' )
			AND ( '.$notify_condition.' )
			AND ( LENGTH(TRIM(user_email)) > 0 )
			AND ( user_email NOT IN ( SELECT emblk_address FROM T_email__blocked WHERE '.get_mail_blocked_condition().' ) )';
$users_to_remind = $DB->get_col( $query, 0, 'Find users who need to be reminded' );

if( empty( $users_to_remind ) )
{ // There is no user to remind
	$result_message = T_( 'It was not necessary to send any reminder!' );
	return 1;
}

// Load required functions ( we need to load here, because in CLI mode it is not loaded )
load_funcs( '_core/_url.funcs.php' );

// Get all those user threads and their recipients where the corresponding users have unread messages
$unread_threads = get_users_unread_threads( $users_to_remind, NULL, 'array' );

// load all required users into the cache
$UserCache = & get_UserCache();
$UserCache->load_list( $users_to_remind );

// Construct message subject and body:
$subject = T_( 'You have unread messages!' );

list( $threads_link ) = get_messages_link_to();

$reminder_sent = 0;
foreach( $users_to_remind as $user_ID )
{
	// send reminder email
	$email_template_params = array(
			'unread_threads' => $unread_threads[$user_ID],
			'threads_link' => $threads_link,
		);
	if( send_mail_to_User( $user_ID, $subject, 'unread_message_reminder', $email_template_params ) )
	{ // Update users last unread message reminder timestamp
		$UserSettings->set( 'last_unread_messages_reminder', date2mysql( $servertimenow ), $user_ID );
		// save UserSettings after each email, because the cron task mail fail and users won't be updated!
		$UserSettings->dbupdate();
		$reminder_sent++;
	}
}

$result_message = sprintf( T_( '%d reminder emails were sent!' ), $reminder_sent );
return 1; /* ok */
?>