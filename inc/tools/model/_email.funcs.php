<?php
/**
 * This file implements functions to work with email tools.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Get array of status titles for email address
 *
 * @return array Status titles
 */
function emadr_get_status_titles()
{
	return array(
			'unknown'     => TS_('Unknown'),
			'redemption'  => TS_('Redemption'),
			'warning'     => TS_('Warning'),
			'suspicious1' => TS_('Suspicious 1'),
			'suspicious2' => TS_('Suspicious 2'),
			'suspicious3' => TS_('Suspicious 3'),
			'prmerror'    => TS_('Permanent error'),
			'spammer'     => TS_('Spammer'),
		);
}


/**
 * Get array of status colors for email address
 *
 * @return array Status colors
 */
function emadr_get_status_colors()
{
	return array(
			''            => '808080',
			'unknown'     => '808080',
			'redemption'  => 'FF00FF',
			'warning'     => 'FFFF00',
			'suspicious1' => 'FFC800',
			'suspicious2' => 'FFA500',
			'suspicious3' => 'FF8C00',
			'prmerror'    => 'FF0000',
			'spammer'     => '990000',
		);
}


/**
 * Get array of status icons for email address
 *
 * @return array Status icons
 */
function emadr_get_status_icons()
{
	return array(
			'unknown'     => get_icon( 'bullet_white', 'imgtag', array( 'title' => emadr_get_status_title( 'unknown' ) ) ),
			'redemption'  => get_icon( 'bullet_magenta', 'imgtag', array( 'title' => emadr_get_status_title( 'redemption' ) ) ),
			'warning'     => get_icon( 'bullet_yellow', 'imgtag', array( 'title' => emadr_get_status_title( 'warning' ) ) ),
			'suspicious1' => get_icon( 'bullet_orange', 'imgtag', array( 'title' => emadr_get_status_title( 'suspicious1' ) ) ),
			'suspicious2' => get_icon( 'bullet_orange', 'imgtag', array( 'title' => emadr_get_status_title( 'suspicious2' ) ) ),
			'suspicious3' => get_icon( 'bullet_orange', 'imgtag', array( 'title' => emadr_get_status_title( 'suspicious3' ) ) ),
			'prmerror'    => get_icon( 'bullet_red', 'imgtag', array( 'title' => emadr_get_status_title( 'prmerror' ) ) ),
			'spammer'     => get_icon( 'bullet_brown', 'imgtag', array( 'title' => emadr_get_status_title( 'spammer' ) ) ),
		);
}


/**
 * Get status levels of email address
 *
 * @return array Status levels
 */
function emadr_get_status_levels()
{
	$levels = array(
			'unknown'     => 1,
			'redemption'  => 2,
			'warning'     => 3,
			'suspicious1' => 4,
			'suspicious2' => 5,
			'suspicious3' => 6,
			'prmerror'    => 7,
			'spammer'     => 8,
		);

	return $levels;
}


/**
 * Get status level of email address by status value
 *
 * @param string Status value
 * @return integer Status level
 */
function emadr_get_status_level( $status )
{
	$levels = emadr_get_status_levels();

	return isset( $levels[ $status ] ) ? $levels[ $status ] : 0;
}


/**
 * Get statuses of email address by status value which have a level less or equal then level of the given status
 *
 * @param string Status value
 * @return array Statuses
 */
function emadr_get_statuses_less_level( $status )
{
	$levels = emadr_get_status_levels();
	$current_level = emadr_get_status_level( $status );

	$statuses = array();
	foreach( $levels as $status => $level )
	{
		if( $level <= $current_level )
		{	// Add this status into array if the level is less or equal then current level
			$statuses[] = $status;
		}
	}

	return $statuses;
}


/**
 * Get status title of email address by status value
 *
 * @param string Status value
 * @return string Status title
 */
function emadr_get_status_title( $status )
{
	$statuses = emadr_get_status_titles();

	return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
}


/**
 * Get status color of email address by status value
 *
 * @param string Status value
 * @return string Color value
 */
function emadr_get_status_color( $status )
{
	if( $status == 'NULL' )
	{
		$status = '';
	}

	$colors = emadr_get_status_colors();

	return isset( $colors[ $status ] ) ? '#'.$colors[ $status ] : 'none';
}


/**
 * Get status icon of email address by status value
 *
 * @param string Status value
 * @return string Icon
 */
function emadr_get_status_icon( $status )
{
	$icons = emadr_get_status_icons();

	return isset( $icons[ $status ] ) ? $icons[ $status ] : '';
}


/**
 * Get result info of email log
 *
 * @param string Result ( 'ok', 'error', 'blocked', 'simulated', 'ready_to_send' )
 * @param boolean Params
 * @param string Latest timestamp when email was opened
 * @param string Latest timestamp when a link in the email was clicked
 * @return string Result info
 */
function emlog_result_info( $result, $params = array(), $last_open = NULL, $last_click = NULL )
{
	$params = array_merge( array(
			'display_icon'  => true,	// Display icon
			'display_text'  => true,	// Display text
			'link_blocked'  => false, // TRUE - to display 'Blocked' as link to go to page with Blocked email adresses with filter by email
			'email'         => '',    // Email address to filter
		), $params );

	$result_info = '';

	switch( $result )
	{
		case 'ok':
			if( $params['display_icon'] )
			{
				if( empty( $last_open ) && empty( $last_click ) )
				{
					$result_info .= get_icon( 'bullet_black', 'imgtag', array( 'alt' => T_('Sent') ) );
				}
				else
				{
					$result_info .= get_icon( 'bullet_green', 'imgtag', array( 'alt' => T_('Opened') ) );
				}
			}
			if( $params['display_text'] )
			{
				if( empty( $last_open ) && empty( $last_click ) )
				{
					$result_info .= ' '.T_('Sent');
				}
				else
				{
					$result_info .= ' './* TRANS: Email was already opened */ T_('Opened');
				}
			}
			break;

		case 'error':
			if( $params['display_icon'] )
			{
				$result_info .= get_icon( 'bullet_orange', 'imgtag', array( 'alt' => T_('Error') ) );
			}
			if( $params['display_text'] )
			{
				$result_info .= ' '.T_('Error');
			}
			break;

		case 'blocked':
			if( $params['display_icon'] )
			{
				$result_info .= get_icon( 'bullet_red', 'imgtag', array( 'alt' => T_('Blocked') ) );
			}
			if( $params['display_text'] )
			{
				if( $params['link_blocked'] && !empty( $params['email'] ) )
				{	// Create a link for email address
					global $admin_url;
					$result_info .= ' <a href="'.$admin_url.'?ctrl=email&amp;email='.$params['email'].'&amp;all_statuses=1">'.T_('Blocked').'</a>';
				}
				else
				{	// Display only text
					$result_info .= ' '.T_('Blocked');
				}
			}
			break;

		case 'simulated':
			if( $params['display_icon'] )
			{
				if( empty( $last_open ) && empty( $last_click ) )
				{
					$result_info .= get_icon( 'bullet_gray', 'imgtag', array( 'alt' => T_('Simulated') ) );
				}
				else
				{
					$result_info .= get_icon( 'bullet_green', 'imgtag', array( 'alt' => T_('Opened') ) );
				}
			}
			if( $params['display_text'] )
			{
				if( empty( $last_open ) && empty( $last_click ) )
				{
					$result_info .= ' '.T_('Simulated');
				}
				else
				{
					$result_info .= ' './* TRANS: Email was already opened */ T_('Opened');
				}
			}
			break;

		case 'ready_to_send':
			if( $params['display_icon'] )
			{
				$result_info .= get_icon( 'bullet_empty', 'imgtag', array( 'alt' => T_('Ready to send') ) );
			}
			if( $params['display_text'] )
			{
				$result_info .= ' '.T_('Ready to send');
			}
			break;
	}

	return $result_info;
}


/**
 * Add a mail log
 *
 * @param integer User ID
 * @param string To (email address)
 * @param string Subject
 * @param string Message
 * @param string Headers
 * @param string Result type ( 'ok', 'error', 'blocked', 'simulated' )
 * @param string Key for email tracking
 * @param integer Email Campaign ID
 * @param integer Automation ID
 */
function mail_log( $user_ID, $to, $subject, $message, $headers, $result, $email_key = NULL, $email_campaign_ID = NULL, $automation_ID = NULL )
{
	global $DB, $servertimenow, $localtimenow;

	/**
	 * @var integer|NULL This global var stores ID of the last inserted mail log
	 */
// TODO fp>erwin: why do we need a global below? Why don't we just return $DB->insert_id; ?
// erwin>fp: this function is called within send_mail() and in EmailCampaign::send_all_emails(), the global is used to update the T_email__campaign_send values after the send_mail() call
	global $mail_log_insert_ID;
	$mail_log_insert_ID = NULL;

	if( empty( $user_ID ) )
	{
		$user_ID = NULL;
	}

	$to = utf8_strtolower( $to );

	// Insert mail log
	$DB->query( 'INSERT INTO T_email__log
		( emlog_key, emlog_timestamp, emlog_user_ID, emlog_to, emlog_result, emlog_subject, emlog_message, emlog_headers, emlog_camp_ID, emlog_autm_ID )
		VALUES
		( '.( empty( $email_key ) ? generate_random_key() : $DB->quote( $email_key ) ).',
			'.$DB->quote( date2mysql( $localtimenow ) ).',
		  '.$DB->quote( $user_ID ).',
		  '.$DB->quote( $to ).',
		  '.$DB->quote( $result ).',
		  '.$DB->quote( utf8_substr( $subject, 0, 255 ) ).',
		  '.$DB->quote( $message ).',
		  '.$DB->quote( $headers ).',
		  '.$DB->quote( $email_campaign_ID ).',
		  '.$DB->quote( $automation_ID ).' )' );

	// Store ID of new inserted mail log
	$mail_log_insert_ID = $DB->insert_id;

	if( $result == 'ok' )
	{ // Save a report about sending of this message in the table T_email__address
		// The mail sending is susccess. Update last sent date and increase a counter
		$DB->query( 'INSERT INTO T_email__address ( emadr_address, emadr_sent_count, emadr_sent_last_returnerror, emadr_last_sent_ts )
			VALUES( '.$DB->quote( $to ).', 1, 1, '.$DB->quote( date2mysql( $servertimenow ) ).' )
			ON DUPLICATE KEY UPDATE
			    emadr_sent_count = emadr_sent_count + 1,
			    emadr_sent_last_returnerror = emadr_sent_last_returnerror + 1,
			    emadr_last_sent_ts = '.$DB->quote( date( 'Y-m-d H:i:s', $servertimenow ) ) );
	}
}


function update_mail_log( $email_ID, $result, $message )
{
	global $DB, $servertimenow, $localtimenow;
	$valid_results = array( 'ok', 'error', 'blocked', 'simulated', 'ready_to_send' );
	if( ! in_array( $result, $valid_results ) )
	{
		debug_die( 'Invalid email log result!' );
	}

	$DB->query( 'UPDATE T_email__log
			SET emlog_result = '.$DB->quote( $result )
			.', emlog_message = '.$DB->quote( $message )
			.', emlog_timestamp = '.$DB->quote( date2mysql( $localtimenow ) )
			.' WHERE emlog_ID = '.$DB->quote( $email_ID ) );

	if( $result == 'ok' )
	{ // Save a report about sending of this message in the table T_email__address
		// The mail sending was a success. Update last sent date and increase a counter
		$to = $DB->get_var( 'SELECT emlog_to from T_email__log WHERE emlog_ID = '.$DB->quote( $email_ID ) );

		$DB->query( 'INSERT INTO T_email__address ( emadr_address, emadr_sent_count, emadr_sent_last_returnerror, emadr_last_sent_ts )
			VALUES( '.$DB->quote( $to ).', 1, 1, '.$DB->quote( date2mysql( $servertimenow ) ).' )
			ON DUPLICATE KEY UPDATE
					emadr_sent_count = emadr_sent_count + 1,
					emadr_sent_last_returnerror = emadr_sent_last_returnerror + 1,
					emadr_last_sent_ts = '.$DB->quote( date( 'Y-m-d H:i:s', $servertimenow ) ) );
	}
}


/**
 * Update time field of mail log row and related tables like email campaign and newsletters
 *
 * @param string Type: 'open', 'click'
 * @param integer Email log ID
 * @param integer Email log key
 */
function update_mail_log_time( $type, $emlog_ID, $emlog_key )
{
	global $DB, $localtimenow;

	switch( $type )
	{
		case 'open':
			$log_time_field = 'emlog_last_open_ts';
			$campaign_time_field = 'csnd_last_open_ts';
			$newsletter_time_field = 'enls_last_open_ts';
			break;

		case 'click':
			$log_time_field = 'emlog_last_click_ts';
			$campaign_time_field = 'csnd_last_click_ts';
			$newsletter_time_field = 'enls_last_click_ts';
			break;

		default:
			debug_die( 'Invalid mail log time type "'.$type.'"' );
	}

	// Update last time for email log:
	$r = $DB->query( 'UPDATE T_email__log
		  SET '.$log_time_field.' = '.$DB->quote( date2mysql( $localtimenow ) ).'
		WHERE emlog_ID = '.$DB->quote( $emlog_ID ).'
		  AND emlog_key = '.$DB->quote( $emlog_key ) );

	if( $r )
	{	// Update last time for email campaign per user:
		$DB->query( 'UPDATE T_email__campaign_send
			  SET '.$campaign_time_field.' = '.$DB->quote( date2mysql( $localtimenow ) ).'
			WHERE csnd_emlog_ID = '.$DB->quote( $emlog_ID ) );

		// Update last time for user subscriptions of all automation newsletters:
		$DB->query( 'UPDATE T_email__newsletter_subscription
			INNER JOIN T_automation__newsletter ON aunl_enlt_ID = enls_enlt_ID AND enls_subscribed = 1
			INNER JOIN T_email__log ON aunl_autm_ID = emlog_autm_ID AND enls_user_ID = emlog_user_ID
			  SET '.$newsletter_time_field.' = '.$DB->quote( date2mysql( $localtimenow ) ).'
			WHERE emlog_ID = '.$DB->quote( $emlog_ID ).'
			  AND enls_last_sent_manual_ts IS NOT NULL' );// When user really received an email from the Newsletter(to avoid subscriptions after email was sent)
	}
}


/**
 * Load the blocked emails from DB in cache
 *
 * @param array User IDs
 * @param array Blocked statuses to know what emails are blocked to send
 *     'unknown'     - Unknown
 *     'warning'     - Warning
 *     'suspicious1' - Suspicious 1
 *     'suspicious2' - Suspicious 2
 *     'suspicious3' - Suspicious 3
 *     'prmerror'    - Permanent error
 *     'spammer'     - Spammer
 */
function load_blocked_emails( $user_IDs, $blocked_statuses = array() )
{
	global $DB, $cache_mail_is_blocked_status;

	if( empty( $user_IDs ) )
	{ // No users, Exit here
		return;
	}

	if( !isset( $cache_mail_is_blocked_status ) )
	{ // Init array first time
		$cache_mail_is_blocked_status = array();
	}

	$status_filter_name = implode( '_', $blocked_statuses );
	if( !isset( $cache_mail_is_blocked_status[ $status_filter_name ] ) )
	{ // Init subarray for each filter by statuses
		$cache_mail_is_blocked_status[ $status_filter_name ] = array();
	}

	$SQL = new SQL();
	$SQL->SELECT( 'user_email, emadr_ID' );
	$SQL->FROM( 'T_users' );
	$SQL->FROM_add( 'LEFT JOIN T_email__address
		 ON user_email = emadr_address
		AND '.get_mail_blocked_condition( true, $blocked_statuses ) );
	$SQL->WHERE( 'user_ID IN ( '.$DB->quote( $user_IDs ).' )' );
	$blocked_emails = $DB->get_assoc( $SQL->get() );

	foreach( $blocked_emails as $email => $email_blocked_ID )
	{ // The blocked email has TRUE value; Trust emails - FALSE
		$cache_mail_is_blocked_status[ $status_filter_name ][ $email ] = (boolean) $email_blocked_ID;
	}
}


/**
 * Check if the email address is blocked
 *
 * @param string Email address
 * @param array Blocked statuses to know what emails are blocked to send
 *     'unknown'     - Unknown
 *     'warning'     - Warning
 *     'suspicious1' - Suspicious 1
 *     'suspicious2' - Suspicious 2
 *     'suspicious3' - Suspicious 3
 *     'prmerror'    - Permanent error
 *     'spammer'     - Spammer
 * @return boolean TRUE
 */
function mail_is_blocked( $email, $blocked_statuses = array() )
{
	global $cache_mail_is_blocked_status;

	if( !isset( $cache_mail_is_blocked_status ) )
	{ // Init array first time
		$cache_mail_is_blocked_status = array();
	}

	$status_filter_name = implode( '_', $blocked_statuses );
	if( !isset( $cache_mail_is_blocked_status[ $status_filter_name ] ) )
	{ // Init subarray for each filter by statuses
		$cache_mail_is_blocked_status[ $status_filter_name ] = array();
	}

	if( !isset( $cache_mail_is_blocked_status[ $status_filter_name ][ $email ] ) )
	{ // If we check status of this email first time - get it from DB and store in cache
		global $DB;
		$SQL = new SQL( 'Check if email address is blocked' );
		$SQL->SELECT( 'emadr_ID' );
		$SQL->FROM( 'T_email__address' );
		$SQL->WHERE( 'emadr_address = '.$DB->quote( utf8_strtolower( $email ) ) );
		$SQL->WHERE_and( get_mail_blocked_condition( true, $blocked_statuses ) );
		$cache_mail_is_blocked_status[ $status_filter_name ][ $email ] = (boolean) $DB->get_var( $SQL );
	}

	// Get email block status from cache variable
	return $cache_mail_is_blocked_status[ $status_filter_name ][ $email ];
}


/**
 * Get where conditino to check if a mail is blocked or not
 *
 * @param boolean set true for blocked emails and false for not blocked emails
 * @param array Blocked statuses to know what emails are blocked to send
 *     'unknown'     - Unknown
 *     'warning'     - Warning
 *     'suspicious1' - Suspicious 1
 *     'suspicious2' - Suspicious 2
 *     'suspicious3' - Suspicious 3
 *     'prmerror'    - Permanent error
 *     'spammer'     - Spammer
 * @return string the where condition
 */
function get_mail_blocked_condition( $is_blocked = true, $blocked_statuses = array() )
{
	global $DB;

	if( empty( $blocked_statuses ) )
	{	// Default the blocked statuses
		$blocked_statuses = array( 'prmerror', 'spammer' );
	}

	$operator = $is_blocked ? 'IN' : 'NOT IN';
	return 'emadr_status '.$operator.' ( '.$DB->quote( $blocked_statuses ).' )';
}


/**
 * Memorize the blocked emails in cache array in order to display the message
 * @see blocked_emails_display()
 *
 * @param string Email address
 */
function blocked_emails_memorize( $email )
{
	global $current_User, $cache_blocked_emails;

	if( empty( $email ) )
	{ // Empty email, Exit here
		return;
	}

	if( is_logged_in() && $current_User->check_perm( 'users', 'view' ) )
	{ // User has permissions to view other users
		if( mail_is_blocked( $email ) )
		{ // Check if the email address is blocked
			if( isset( $cache_blocked_emails[ $email ] ) )
			{ // Icrease a count of blocked email
				$cache_blocked_emails[ $email ]++;
			}
			else
			{
				$cache_blocked_emails[ $email ] = 1;
			}
		}
	}
}


/**
 * Display the blocked emails from cache array
 */
function blocked_emails_display()
{
	global $Messages, $cache_blocked_emails;

	if( !empty( $cache_blocked_emails ) && is_array( $cache_blocked_emails ) )
	{ // Display the messages about the blocked emails (grouped by email)
		foreach( $cache_blocked_emails as $blocked_email => $blocked_emails_count )
		{
			$Messages->add( sprintf( T_('We could not send %d email to %s because this address is blocked.'), $blocked_emails_count, $blocked_email ) );
		}
	}
}


/**
 * Parse message of mail if content is in multipart or HTML format
 *
 * @param string Headers
 * @param string Message
 * @return array|boolean Mail data or FALSE when message has only Plain Text content
 */
function mail_log_parse_message( $headers, $message )
{
	preg_match( '/Content-Type: ([^;]+);/i', $headers, $header_matches );

	if( empty( $header_matches[1] ) )
	{ // Incorrect headers, Exit here
		return false;
	}

	$data = array();

	if( $header_matches[1] == 'text/html' )
	{ // Message has one content in HTML format
		$data['html'] = array(
				'type'       => $header_matches[0],
				'content'    => mail_log_parse_html_data( 'content', $message ),
				'head_style' => mail_log_parse_html_data( 'head_style', $message ),
				'body_style' => mail_log_parse_html_data( 'body_style', $message ),
				'body_class' => mail_log_parse_html_data( 'body_class', $message ),
			);

		return $data;
	}
	elseif( $header_matches[1] != 'multipart/mixed' )
	{ // Message content is not multipart
		return false;
	}

	preg_match( '/Content-Type: multipart\/alternative; boundary="([^;]+)"/i', $message, $boundary_matches );

	if( empty( $boundary_matches ) || empty( $boundary_matches[1] ) )
	{ // No found boundary delimiter of message contents
		return false;
	}

	$boundary_delimiter = '--'.$boundary_matches[1];

	$contents = explode( $boundary_delimiter, $message );
	unset( $contents[0] );
	unset( $contents[3] );

	foreach( $contents as $content )
	{
		preg_match( '/(Content-Type: ([^;]+);(.+)){1}\n([\s\S\n]+)/i', $content, $type_matches );

		switch( $type_matches[2] )
		{
			case 'text/html':
				// Get data of Plain Text content
				$data['html'] = array(
						'type'       => $type_matches[1],
						'content'    => mail_log_parse_html_data( 'content', $type_matches[4] ),
						'head_style' => mail_log_parse_html_data( 'head_style', $type_matches[4] ),
						'body_style' => mail_log_parse_html_data( 'body_style', $type_matches[4] ),
						'body_class' => mail_log_parse_html_data( 'body_class', $type_matches[4] ),
					);
				break;

			case 'text/plain':
				// Get data of HTML content
				$data['text'] = array(
						'type'    => $type_matches[1],
						'content' => $type_matches[4],
					);
				break;
		}
	}

	return $data;
}


/**
 * Extract the parts from html body message of mail
 *
 * @param string Data type: 'content', 'head_style', 'body_style', 'body_class'
 * @param string Message
 * @return string
 */
function mail_log_parse_html_data( $type, $message )
{
	switch( $type )
	{
		case 'content':
			// Get <body> content of html email message
			return preg_replace( '#.+<body[^>]*>(.+)</body>.+#is', '$1', $message );
			break;

		case 'head_style':
			// Get <style> content of html email message
			return preg_replace( '#.+<style[^>]*>(.+)</style>.+#is', '$1', $message );
			break;

		case 'body_style':
		case 'body_class':
			// Get class|style of <body> content of html email message
			$regexp_attr = str_replace( 'body_', '', $type );
			preg_match( '#.+<body[^>]*('.$regexp_attr.'="([^"]+)")[^>]*>.+#is', $message, $body_attrs_match );
			return empty( $body_attrs_match[2] ) ? '' : $body_attrs_match[2];
			break;
	}
}


/**
 * Check if SMTP Swift Mailer is available on this system
 *
 * @return boolean|string TRUE on success, Error message about why we cannot use SMTP
 */
function check_smtp_mailer()
{
	global $Settings;

	if( ! $Settings->get( 'smtp_enabled' ) )
	{ // Swift Mailer is not enabled
		return T_( 'SMTP gateway is not enabled.' );
	}

	$smtp_server_host = $Settings->get( 'smtp_server_host' );
	$smtp_server_port = $Settings->get( 'smtp_server_port' );
	if( empty( $smtp_server_host ) || empty( $smtp_server_port ) )
	{ // These settings must be defined
		return T_( 'SMTP Host and Port Number must be defined to enable SMTP gateway.' );
	}

	$smtp_server_security = $Settings->get( 'smtp_server_security' );
	if( $smtp_server_security == 'ssl' || $smtp_server_security == 'tls' )
	{ // Check if enabled encryption method is enabled in this system
		$available_transports = stream_get_transports();
		$method_is_available = false;
		foreach( $available_transports as $available_transport )
		{
			if( preg_match( '#^'.$smtp_server_security.'#i', $available_transport ) )
			{ // Check if first symbols are match, because transport can be "ssl", "sslv2" or "sslv3"
				$method_is_available = true;
				break;
			}
		}
		if( ! $method_is_available )
		{ // Stop the checking here because encryption method is not available
			return sprintf( T_( 'Encryption Method %s must be available on this system in order to enable SMTP gateway.' ), '<b>'.strtoupper( $smtp_server_security ).'</b>' );
		}
	}

	// SMTP can be used in this system
	return true;
}


/**
 * Test SMTP connection by Swift Transport
 *
 * @param object Swift Transport
 * @return boolean|string TRUE on success OR Error message
 */
function test_smtp_transport( & $Swift_SmtpTransport )
{
	try
	{ // Try to intialize a connection by SMTP transport
		$Swift_SmtpTransport->start();
		return true;
	}
	catch( Swift_TransportException $Swift_TransportException )
	{ // Error connection
		$message = $Swift_TransportException->getMessage();
		// Replace invalid symbols with '?'
		return preg_replace( '/[^\x20-\x7F]/', '?', $message );
	}
}


/**
 * Get SMTP Swift Transport
 *
 * @return object Swift_SmtpTransport object
 */
function & get_Swift_SmtpTransport()
{
	global $Settings;

	// Load Swift Mailer functions:
	load_funcs( '_ext/swift/swift_required.php' );

	$smtp_server_host = $Settings->get( 'smtp_server_host' );
	$smtp_server_port = $Settings->get( 'smtp_server_port' );
	$smtp_server_security = $Settings->get( 'smtp_server_security' );
	$smtp_server_username = $Settings->get( 'smtp_server_username' );
	$smtp_server_password = $Settings->get( 'smtp_server_password' );

	// Create the Transport:
	$Swift_SmtpTransport = Swift_SmtpTransport::newInstance( $smtp_server_host, $smtp_server_port );
	if( $smtp_server_security == 'ssl' || $smtp_server_security == 'tls' )
	{	// Set encryption:
		$Swift_SmtpTransport->setEncryption( $smtp_server_security );

		if( $Settings->get( 'smtp_server_novalidatecert' ) )
		{	// Do not validate the certificate from the TLS/SSL server:
			$options = array( 'ssl' => array( 'verify_peer' => false, 'verify_peer_name' => false ) );
			$Swift_SmtpTransport->setStreamOptions( $options );
		}
	}
	if( ! empty( $smtp_server_username ) )
	{	// Set username if it is defined:
		$Swift_SmtpTransport->setUsername( $smtp_server_username );
	}
	if( ! empty( $smtp_server_password ) )
	{	// Set password if it is defined:
		$Swift_SmtpTransport->setPassword( $smtp_server_password );
	}

	return $Swift_SmtpTransport;
}


/**
 * Get SMTP Swift Mailer
 *
 * @return object Swift_Mailer object
 */
function & get_Swift_Mailer()
{
	// Create Transport
	$Swift_SmtpTransport = & get_Swift_SmtpTransport();

	// Test a connection
	$connection_result = test_smtp_transport( $Swift_SmtpTransport );

	if( $connection_result === true )
	{ // Create the Mailer using the created Transport
		$Swift_Mailer = Swift_Mailer::newInstance( $Swift_SmtpTransport );
	}
	else
	{ // Some errors on SMTP connection
		$Swift_Mailer = NULL;
	}

	return $Swift_Mailer;
}


/**
 * Send mail by default mail() function or by SMTP Swift Mailer
 *
 * @param string Receiver, or receivers of the mail
 * @param string Subject of the email
 * @param string|array Message OR Array: 'full', 'html', 'text'
 * @param array Email headers
 * @param string Additional flags as command line options
 */
function evo_mail( $to, $subject, $message, $headers = array(), $additional_parameters = '' )
{
	global $Settings;

	$message_data = $message;
	if( is_array( $message_data ) && isset( $message_data['full'] ) )
	{ // If content is multipart
		$message = $message_data['full'];
	}

	switch( $Settings->get( 'email_service' ) )
	{
		case 'smtp':
			// SMTP sending is preferred:
			$result = evo_mail_smtp( $to, $subject, $message_data, $headers );
			if( ! $result && $Settings->get( 'force_email_sending' ) )
			{	// SMTP sending failed, Fallback to sending email by php "mail" function:
				$result = evo_mail_php( $to, $subject, $message, $headers, $additional_parameters );
			}
			break;

		case 'mail':
		default:
			// PHP "mail" function is preferred:
			$result = evo_mail_php( $to, $subject, $message, $headers, $additional_parameters );
			if( ! $result && $Settings->get( 'force_email_sending' ) )
			{	// "mail" function failed, Fallback to sending email by SMTP Swift Mailer:
				$result = evo_mail_smtp( $to, $subject, $message_data, $headers );
			}
			break;
	}

	return $result;
}


/**
 * Send mail by PHP mail() function
 *
 * @param string Receiver, or receivers of the mail
 * @param string Subject of the email
 * @param string|array Message OR Array: 'charset', 'full', 'html', 'text'
 * @param array Email headers
 * @param string Additional flags as command line options
 * @return boolean TRUE on success
 */
function evo_mail_php( $to, $subject, $message, $headers = array(), $additional_parameters = '' )
{
	global $php_mail_sending_log;

	$php_mail_sending_log = '';

	$headers_string = get_mail_headers( $headers );

	if( ! empty( $headers_string ) )
	{	// Write headers to log:
		$php_mail_sending_log .= PHP_EOL.'Headers:'.PHP_EOL.$headers_string;
	}

	if( ! empty( $additional_parameters ) )
	{	// Write additional params to log:
		$php_mail_sending_log .= PHP_EOL.'Additional parameters: '.$additional_parameters.PHP_EOL;
	}

	// Send email by PHP:
	$result = @mail( $to, $subject, $message, $headers_string, $additional_parameters );

	if( ! $result )
	{	// Store error log in global var:
		$php_last_error = error_get_last();
		if( isset( $php_last_error['message'] ) )
		{
			$php_mail_sending_log .= $php_last_error['message'];
		}
	}

	return $result;
}


/**
 * Send mail by SMTP Swift Mailer
 *
 * @param string Receiver, or receivers of the mail
 * @param string Subject of the email
 * @param string|array Message OR Array: 'charset', 'full', 'html', 'text'
 * @param array Email headers
 * @return boolean TRUE on success
 */
function evo_mail_smtp( $to, $subject, $message, $headers = array() )
{
	global $smtp_mail_sending_log;

	// Check if we can use SMTP Swift Mailer
	if( check_smtp_mailer() === true && ( $Swift_Mailer = & get_Swift_Mailer() ) )
	{ // Use Swift Mailer to send emails using SMTP

		// Register Swift plugin "ArrayLogger":
		$Swift_Plugins_Loggers_ArrayLogger = new Swift_Plugins_Loggers_ArrayLogger();
		$Swift_Mailer->registerPlugin( new Swift_Plugins_LoggerPlugin( $Swift_Plugins_Loggers_ArrayLogger ) );

		// Get charset from content type part
		$charset = ( isset( $headers['Content-Type'] ) && preg_match( '#charset=(.+)$#i', $headers['Content-Type'], $charset ) ) ? $charset[1] : NULL;

		// Create a Swift_Message object
		$Swift_Message = Swift_Message::newInstance();
		// Subject:
		$Swift_Message->setSubject( $subject );
		// To:
		if( empty( $message['to_email'] ) )
		{ // Use only email address
			$Swift_Message->setTo( $to );
		}
		else
		{ // Use address with name
			$Swift_Message->setTo( $message['to_email'], $message['to_name'] );
		}
		// Body:
		if( isset( $headers['Content-Type'] ) && preg_match( '#^[^;]+#i', $headers['Content-Type'], $content_type ) )
		{
			switch( $content_type[0] )
			{
				case 'multipart/mixed':
					// MULTIPLE:
					if( is_array( $message ) )
					{ // Body is multiple
						$Swift_Message->setBody( $message['html'], 'text/html', $message['charset'] );
						$Swift_Message->addPart( $message['text'], 'text/plain', $message['charset'] );
						break;
					}
					else
					{ // Unknown case, Send email with text/plain format
						$content_type[0] = 'text/plain';
					}

				case 'text/html':
					// HTML:
				case 'text/plain':
					// TEXT:
					$Swift_Message->setBody( $message['full'], $content_type, $charset );
					break;

				default:
					// Unknown content type
					$Swift_Message->setBody( $message['full'], null, $charset );
					break;
			}
		}
		else
		{ // Unknown content type
			$Swift_Message->setBody( $message['full'], null, $charset );
		}
		// From:
		if( ! empty( $message['from_email'] ) )
		{ // Use address with name
			$Swift_Message->setFrom( $message['from_email'], $message['from_name'] );
		}
		elseif( ! empty( $headers['From'] ) )
		{ // Use only email address
			$Swift_Message->setFrom( $headers['From'] );
		}
		if( ! empty( $headers['Reply-To'] ) )
		{ // Reply-To:
			$Swift_Message->setReplyTo( $headers['Reply-To'] );
		}
		if( ! empty( $headers['Return-Path'] ) )
		{ // Return-Path:
			$Swift_Message->setReturnPath( $headers['Return-Path'] );
		}
		if( ! empty( $headers['Date'] ) )
		{ // Date:
			$Swift_Message->setDate( is_string( $headers['Date'] ) ? strtotime( $headers['Date'] ) : $headers['Date'] );
		}

		// Send the message by SMTP transport:
		$r = $Swift_Mailer->send( $Swift_Message );

		// Save SMTP log to global cache variable:
		if( empty( $smtp_mail_sending_log ) )
		{
			$smtp_mail_sending_log = '';
		}

		if( ! empty( $headers ) )
		{	// Write headers to log:
			$headers_string = get_mail_headers( $headers );
			$smtp_mail_sending_log .= PHP_EOL.'Headers:'.PHP_EOL.$headers_string;
		}

		$recipients = array();
		foreach( $Swift_Message->getTo() as $recipient_address => $recipient_name )
		{
			$recipients[] = '"'.$recipient_name.' <'.$recipient_address.'>"';
		}
		$smtp_mail_sending_log .= PHP_EOL.implode( ', ', $recipients ).': '.$Swift_Plugins_Loggers_ArrayLogger->dump();

		return $r;
	}

	// No email was sent
	return false;
}


/**
 * Get headers string for mail functions
 *
 * @param array Headers array
 * @return string Headers string
 */
function get_mail_headers( $headers, $nl = "\r\n" )
{
	// Convert headers array to string format:
	$headers_string = '';
	foreach( $headers as $h_key => $h_value )
	{
		$headers_string .= $h_key.': '.$h_value.$nl;
	}

	return $headers_string;
}


/**
 * Test connection to SMTP server by Swift Mailer
 *
 * @return array Result messages
 */
function smtp_connection_test()
{
	global $smtp_connection_result;

	$test_mail_messages = array();
	$smtp_connection_result = true;

	// Check if Swift Mailer is enabled
	$check_smtp_result = check_smtp_mailer();
	$message = T_('Check SMTP settings...').' ';
	if( $check_smtp_result === true )
	{	// Success:
		$test_mail_messages[] = '<b>'.$message.'</b><b class="green">OK</b>';
		syslog_insert( $message.' OK', 'info', NULL );
	}
	else
	{	// Error:
		$test_mail_messages[] = '<b>'.$message.'</b>'.$check_smtp_result.' <b class="red">'.T_('Failed').'</b>';
		syslog_insert( $message.$check_smtp_result.' '.T_('Failed'), 'warning', NULL );
		$smtp_connection_result = false;
		return $test_mail_messages;// EXIT
	}

	// Create SMTP transport:
	$Swift_SmtpTransport = & get_Swift_SmtpTransport();

	// Register Swift plugin "ArrayLogger":
	$Swift_Plugins_Loggers_ArrayLogger = new Swift_Plugins_Loggers_ArrayLogger();
	$Swift_SmtpTransport->registerPlugin( new Swift_Plugins_LoggerPlugin( $Swift_Plugins_Loggers_ArrayLogger ) );

	// Test SMTP connection:
	$connection_result = test_smtp_transport( $Swift_SmtpTransport );

	// Get log of the connection:
	$smtp_mail_connection_log = PHP_EOL.$Swift_Plugins_Loggers_ArrayLogger->dump();

	$smtp_message = T_('Test SMTP connection...').' ';

	// Set SMTP log text to display on the testing page:
	$smtp_mail_sending_log_html = '<b>'.$smtp_message.'</b>'
		.( empty( $smtp_mail_connection_log ) ? ' ' : nl2br( format_to_output( $smtp_mail_connection_log, 'htmlspecialchars' ) ).'<br />' );

	if( $connection_result === true )
	{ // Success
		$test_mail_messages[] = $smtp_mail_sending_log_html.'<b class="green">OK</b>';
		syslog_insert( $smtp_message.$smtp_mail_connection_log.' OK', 'info', NULL );
	}
	else
	{ // Error
		$test_mail_messages[] = $smtp_mail_sending_log_html.'<b class="red">'.T_('Failed').'</b>';
		syslog_insert( $smtp_message.$smtp_mail_connection_log.' '.T_('Failed'), 'warning', NULL );
		$smtp_connection_result = false;
	}

	return $test_mail_messages;
}


/**
 * Test email sending by SMTP gateway
 *
 * @return array Result messages
 */
function smtp_email_sending_test()
{
	global $smtp_connection_result, $Settings, $current_User, $smtp_mail_sending_log;

	$smtp_connection_result = true;

	// Firstly try to SMTP connect:
	$test_mail_messages = smtp_connection_test();

	if( ! $smtp_connection_result )
	{	// Errors on SMTP connections:
		return $test_mail_messages;
		// Exit here.
	}

	$smtp_message = sprintf( T_( 'Attempting to send a text email to "%s" via external SMTP server...' ), $current_User->get( 'email' ) ).' ';

	// Force temporary to use ONLY SMTP sending:
	$email_service = $Settings->get( 'email_service' );
	$Settings->set( 'email_service', 'smtp' );
	// DON'T force to send email by php "mail":
	$force_email_sending = $Settings->get( 'force_email_sending' );
	$Settings->set( 'force_email_sending', false );

	// Send test email:
	$sending_result = send_mail( $current_User->get( 'email' ), $current_User->get( 'login' ), 'Test SMTP email sending', 'Hello, this is a test.' );

	// Set SMTP log text to display on the testing page:
	$smtp_mail_sending_log_html = '<b>'.$smtp_message.'</b>'
		.( empty( $smtp_mail_sending_log ) ? ' ' : nl2br( format_to_output( $smtp_mail_sending_log, 'htmlspecialchars' ) ).'<br />' );

	if( $sending_result === true )
	{	// Success:
		$test_mail_messages[] = $smtp_mail_sending_log_html.'<b class="green">OK</b>';
		syslog_insert( $smtp_message.$smtp_mail_sending_log.' OK', 'info', NULL );
	}
	else
	{	// Error:
		$test_mail_messages[] = $smtp_mail_sending_log_html.'<b class="red">'.T_('Failed').'</b>';
		syslog_insert( $smtp_message.$smtp_mail_sending_log.' '.T_('Failed'), 'warning', NULL );
		$smtp_connection_result = false;
	}

	// Revert temporary changed settings:
	$Settings->set( 'email_service', $email_service );
	$Settings->set( 'force_email_sending', $force_email_sending );

	return $test_mail_messages;
}


/**
 * Test email sending by PHP
 *
 * @return array Result messages
 */
function php_email_sending_test()
{
	global $Settings, $current_User, $php_mail_sending_log;

	$mail_message = sprintf( T_( 'Attempting to send a text email to "%s" via PHP...' ), $current_User->get( 'email' ) ).' ';

	// Force temporary to use ONLY PHP mail sending:
	$email_service = $Settings->get( 'email_service' );
	$Settings->set( 'email_service', 'mail' );
	// DON'T force to send email by SMTP:
	$force_email_sending = $Settings->get( 'force_email_sending' );
	$Settings->set( 'force_email_sending', false );

	// Send test email:
	$sending_result = send_mail( $current_User->get( 'email' ), $current_User->get( 'login' ), 'Test PHP email sending', 'Hello, this is a test.' );

	// Set SMTP log text to display on the testing page:
	$php_mail_sending_log_html = '<b>'.$mail_message.'</b>'
		.( empty( $php_mail_sending_log ) ? ' ' : nl2br( format_to_output( $php_mail_sending_log, 'htmlspecialchars' ) ).'<br />' );

	if( $sending_result === true )
	{	// Success:
		$test_mail_messages[] = $php_mail_sending_log_html.'<b class="green">OK</b>';
		syslog_insert( $mail_message.$php_mail_sending_log.' OK', 'info', NULL );
	}
	else
	{	// Error:
		$test_mail_messages[] = $php_mail_sending_log_html.'<b class="red">'.T_('Failed').'</b>';
		syslog_insert( $mail_message.$php_mail_sending_log.' '.T_('Failed'), 'warning', NULL );
		$smtp_connection_result = false;
	}

	// Revert temporary changed settings:
	$Settings->set( 'email_service', $email_service );
	$Settings->set( 'force_email_sending', $force_email_sending );

	return $test_mail_messages;
}


/**
 * Adds email tracking to message string
 *
 * @param string Message
 * @param string Email key
 * @return string Message with email tracking
 */
function add_email_tracking( $message, $email_ID, $email_key, $params = array() )
{
	global $rsc_url;

	$params = array_merge( array(
			'content_type' => 'auto',
			'image_load' => true,
			'link_click_html' => true,
			'link_click_text' => true,
			'template_parts' => array(
					'header' => 0,
					'footer' => 0,
				),
			'default_template_tag' => NULL
		), $params );

	load_class( 'tools/model/_emailtrackinghelper.class.php', 'EmailTrackingHelper' );

	if( empty( $email_ID ) )
	{
		debug_die( 'No email ID specified.' );
	}

	if( empty( $email_key ) )
	{
		debug_die( 'No email key specified.' );
	}

	if( $params['content_type'] == 'auto' )
	{
		if( is_html( $message ) )
		{
			$content_type = 'html';
		}
		else
		{
			$content_type = 'text';
		}
	}
	else
	{
		$content_type = $params['content_type'];
	}

	$template_message = $message;
	$template_parts = array();
	foreach( $params['template_parts'] as $part => $tag )
	{
		$re = '~\$template-content-'.$part.'-start\$.*?\$template-content-'.$part.'-end\$~s';
		preg_match_all( $re, $message, $matches, PREG_SET_ORDER );
		foreach( $matches as $match )
		{
			$key = '#'.rand();
			$template_parts[$key] = array(
				'message' => $match[0],
				'part' => $part,
				'tag' => $tag );

			$count = 1;
			$message = str_replace( $match[0], '$template-part-'.$key.'$', $message, $count );
		}
	}

	switch( $content_type )
	{
		case 'text':
			// Add link click tracking
			if( $params['link_click_text'] )
			{
				$re = '#(\$secret_content_start\$|\b)\s*(https?://[^,\s()<>]+(?:\([\w\d]+\)|(?:[^,[:punct:]\s]?|/)))(\$secret_content_end\$)?#i';
				$callback = new EmailTrackingHelper( 'link', $email_ID, $email_key, 'plain_text', $params['default_template_tag'] );
				$message = preg_replace_callback( $re, array( $callback, 'callback' ), $message );
			}
			break;

		case 'html':
			if( $params['image_load'] )
			{
				// Add email open tracking to first image
				$re = '/(<img\b.+\bsrc=")([^"]*?)(")/iU';
				$callback = new EmailTrackingHelper( 'img', $email_ID, $email_key, 'html' );
				$message = preg_replace_callback( $re, array( $callback, 'callback' ), $message, 1 );

				/*
				// Add web beacon
				$callback = new EmailTrackingHelper( 'img', $email_ID, $email_key );
				$message .= "\n".'<img src="'.$callback->get_passthrough_url().$rsc_url.'img/blank.gif" />';
				*/
			}

			if( $params['link_click_html'] )
			{
				// Add link click tracking
				$re = '/(<a\b.+\bhref=")([^"]*?)(")/iU';
				$callback = new EmailTrackingHelper( 'link', $email_ID, $email_key, 'html', $params['default_template_tag'] );
				$message = preg_replace_callback( $re, array( $callback, 'callback' ), $message );
			}
			break;

		default:
			debug_die( 'Invalid content type' );
	}

	foreach( $template_parts as $key => $row )
	{
		switch( $content_type )
		{
			case 'text':
				// Add link click tracking
				if( $params['link_click_text'] )
				{
					$re = '#(\$secret_content_start\$|\b)\s*(https?://[^,\s()<>]+(?:\([\w\d]+\)|(?:[^,[:punct:]\s]?|/)))(\$secret_content_end\$)?#i';
					$callback = new EmailTrackingHelper( 'link', $email_ID, $email_key, 'plain_text', $row['tag'] );
					$template_parts[$key]['message'] = preg_replace_callback( $re, array( $callback, 'callback' ), $template_parts[$key]['message'] );
				}
				break;

			case 'html':
				if( $params['image_load'] )
				{
					// Add email open tracking to first image
					$re = '/(<img\b.+\bsrc=")([^"]*?)(")/iU';
					$callback = new EmailTrackingHelper( 'img', $email_ID, $email_key, 'html' );
					$template_parts[$key]['message'] = preg_replace_callback( $re, array( $callback, 'callback' ), $template_parts[$key]['message'], 1 );
				}

				if( $params['link_click_html'] )
				{
					// Add link click tracking
					$re = '/(<a\b.+\bhref=")([^"]*?)(")/iU';
					$callback = new EmailTrackingHelper( 'link', $email_ID, $email_key, 'html', $row['tag'] );
					$template_parts[$key]['message'] = preg_replace_callback( $re, array( $callback, 'callback' ), $template_parts[$key]['message'] );
				}
				break;

			default:
				debug_die( 'Invalid content type' );
		}
		$count = 1;
		$message = str_replace( '$template-part-'.$key.'$', $template_parts[$key]['message'], $message, $count );
	}

	return $message;

}
?>