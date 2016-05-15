<?php
/**
 * This file implements the unread messages email reminder cron job
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $DB, $UserSettings, $Settings;

global $servertimenow, $htsrv_url, $unread_message_reminder_delay, $unread_messsage_reminder_threshold;

// New unread messages reminder may be sent to a user if it has at least one unread message which is older then the given threshold date
$threshold_date = date2mysql( $servertimenow - $unread_messsage_reminder_threshold );
// New unread messages reminder should be sent to a user if the last one was sent at least x days ago, where x depends from the configuration
// Get the minimum delay value from the configuration array
$minimum_delay = array_values( $unread_message_reminder_delay );
$minimum_delay = array_shift( $minimum_delay );
// Get the datetime which corresponds to the minimum delay value.
// If the last unread message reminder for a specific user is after this datetime, then notification must not be send to that user now.
$reminder_threshold = date2mysql( $servertimenow - ( $minimum_delay * 86400 ) );

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
$notify_condition = '( notif_setting.uset_value IS NOT NULL AND notif_setting.uset_value <> '.$DB->quote( '0' ).' )';
if( $Settings->get( 'def_notify_unread_messages' ) )
{ // by default everybody should be notified, so notify those users who didn't explicitly set to not receive notifications
	$notify_condition .= ' OR notif_setting.uset_value IS NULL';
}

// Find user who have unread messages created more than x ( = configured threshold value ) hours ago, and have not been reminded in the last y ( = configured reminder delay for this user ) hours
// We needs to send reminder for these users, but the message must include all unread threads even if the last unread messages was created less then x hours
$query = 'SELECT DISTINCT user_ID, last_sent.uset_value
		FROM T_users
			INNER JOIN T_messaging__threadstatus ON tsta_user_ID = user_ID
			INNER JOIN T_messaging__message ON tsta_first_unread_msg_ID = msg_ID
			LEFT JOIN T_users__usersettings last_sent ON last_sent.uset_user_ID = user_ID AND last_sent.uset_name = "last_unread_messages_reminder"
			LEFT JOIN T_users__usersettings notif_setting ON notif_setting.uset_user_ID = user_ID AND notif_setting.uset_name = "notify_unread_messages"
		WHERE ( msg_datetime < '.$DB->quote( $threshold_date ).' )
			AND ( last_sent.uset_value IS NULL OR last_sent.uset_value < '.$DB->quote( $reminder_threshold ).' )
			AND ( '.$notify_condition.' )
			AND ( LENGTH(TRIM(user_email)) > 0 )
			AND ( user_email NOT IN ( SELECT emadr_address FROM T_email__address WHERE '.get_mail_blocked_condition().' ) )';
$users_to_remind = $DB->get_assoc( $query, 0, 'Find users who need to be reminded' );

if( empty( $users_to_remind ) )
{ // There is no user to remind
	$result_message = T_( 'It was not necessary to send any reminder!' );
	return 1;
}

// load all required users into the cache
$UserCache = & get_UserCache();
$users_to_remind_ids = array_keys( $users_to_remind );
$UserCache->load_list( $users_to_remind_ids );

// Filter out those users which wasn't logged in since many days and shouldn't get reminder email in every three days
$index = -1;
foreach( $users_to_remind as $user_ID => $last_reminder_ts )
{
	$index++;
	if( empty( $last_reminder_ts ) )
	{ // Reminder was not sent yet, we don't need to check anything else, because in this case reminder must be sent
		continue;
	}
	$notify_User = $UserCache->get_by_ID( $user_ID );
	// Set timestamps
	$last_reminder_ts = strtotime( $last_reminder_ts );
	$lastseen_ts = strtotime( $notify_User->get( 'lastseen_ts' ) );
	// Set days
	$days_since_lastseen = floor(( $servertimenow - $lastseen_ts )/(60*60*24));
	$days_since_reminder = floor(( $servertimenow - $last_reminder_ts )/(60*60*24));
	// Go through reminder delay values defined in the advanced config file, and pick the corresponding delay for this User
	$dont_send_reminder = true;
	foreach( $unread_message_reminder_delay as $lastseen => $delay )
	{
		if( $days_since_lastseen < $lastseen )
		{ // We have found the correct delay value, we must check if we should send reminder
			$dont_send_reminder = false;
			break;
		}
	}
	if( $dont_send_reminder || ( $days_since_reminder < $delay ) )
	{ // Don't send a new reminder, because the user was not logged in since a very long time or we have already sent a reminder in the last x days ( x = delay value )
		unset( $users_to_remind_ids[$index] );
	}
}

if( empty( $users_to_remind_ids ) )
{ // There is no user to remind after we have filtered out those ussers who haven't logged in since a long time
	$result_message = T_( 'It was not necessary to send any reminder!' );
	return 1;
}

// Set TRUE to use gender settings from back office
global $is_admin_page;
$is_admin_page = true;

// Get all those user threads and their recipients where the corresponding users have unread messages:
$unread_threads = get_users_unread_threads( $users_to_remind_ids, NULL, 'array', 'html', 'http:' );

// Get unread thread urls
list( $threads_link ) = get_messages_link_to();

$reminder_sent = 0;
foreach( $users_to_remind_ids as $user_ID )
{
	// send reminder email
	$email_template_params = array(
			'unread_threads' => $unread_threads[$user_ID],
			'threads_link' => $threads_link,
		);
	$notify_User = $UserCache->get_by_ID( $user_ID );
	// Change locale here to localize the email subject and content
	locale_temp_switch( $notify_User->get( 'locale' ) );
	if( send_mail_to_User( $user_ID, T_( 'You have unread messages!' ), 'private_messages_unread_reminder', $email_template_params ) )
	{ // Update users last unread message reminder timestamp
		$UserSettings->set( 'last_unread_messages_reminder', date2mysql( $servertimenow ), $user_ID );
		// save UserSettings after each email, because the cron task mail fail and users won't be updated!
		$UserSettings->dbupdate();
		$reminder_sent++;
	}
	locale_restore_previous();
}

$result_message = sprintf( T_( '%d reminder emails were sent!' ), $reminder_sent );
return 1; /* ok */
?>