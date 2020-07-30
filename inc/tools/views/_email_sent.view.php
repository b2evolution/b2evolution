<?php
/**
 * This file implements the UI view for Tools > Email > Sent
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url, $UserSettings;


global $datestartinput, $datestart, $datestopinput, $datestop, $email;

if( param_date( 'datestartinput', T_('Invalid date'), false,  NULL ) !== NULL )
{ // We have a user provided localized date:
	memorize_param( 'datestart', 'string', NULL, trim( form_date( $datestartinput ) ) );
	memorize_param( 'datestartinput', 'string', NULL, empty( $datestartinput ) ? NULL : date( locale_datefmt(), strtotime( $datestartinput ) ) );
}
else
{ // We may have an automated param transmission date:
	param( 'datestart', 'string', '', true );
}
if( param_date( 'datestopinput', T_('Invalid date'), false, NULL ) !== NULL )
{ // We have a user provided localized date:
	memorize_param( 'datestop', 'string', NULL, trim( form_date( $datestopinput ) ) );
	memorize_param( 'datestopinput', 'string', NULL, empty( $datestopinput ) ? NULL : date( locale_datefmt(), strtotime( $datestopinput ) ) );
}
else
{ // We may have an automated param transmission date:
	param( 'datestop', 'string', '', true );
}
param( 'email', 'string', '', true );
$username = param( 'username', 'string', '', true );
$title = param( 'title', 'string', '', true );

// Create result set:

$SQL = new SQL();
$SQL->SELECT( 'SQL_NO_CACHE emlog_ID, emlog_timestamp, emlog_user_ID, emlog_to, emlog_result, emlog_subject, emlog_last_open_ts, emlog_last_click_ts, emlog_camp_ID, ecmp_name' );
$SQL->FROM( 'T_email__log' );
$SQL->FROM_add( 'LEFT JOIN T_email__campaign ON ecmp_ID = emlog_camp_ID' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'SQL_NO_CACHE COUNT(emlog_ID)' );
$count_SQL->FROM( 'T_email__log' );

if( !empty( $datestart ) )
{	// Filter by start date
	$SQL->WHERE_and( 'emlog_timestamp >= '.$DB->quote( $datestart.' 00:00:00' ) );
	$count_SQL->WHERE_and( 'emlog_timestamp >= '.$DB->quote($datestart.' 00:00:00' ) );
}
if( !empty( $datestop ) )
{	// Filter by end date
	$SQL->WHERE_and( 'emlog_timestamp <= '.$DB->quote( $datestop.' 23:59:59' ) );
	$count_SQL->WHERE_and( 'emlog_timestamp <= '.$DB->quote( $datestop.' 23:59:59' ) );
}
if( !empty( $email ) )
{	// Filter by email
	$email = utf8_strtolower( $email );
	$SQL->WHERE_and( 'emlog_to LIKE '.$DB->quote( '%'.$email.'%' ) );
	$count_SQL->WHERE_and( 'emlog_to LIKE '.$DB->quote( '%'.$email.'%' ) );
}
if( !empty( $username ) )
{
	$SQL->SELECT_add( ', user_login' );
	$SQL->FROM_add( 'LEFT JOIN T_users ON user_ID = emlog_user_ID' );
	$count_SQL->FROM_add( 'LEFT JOIN T_users ON user_ID = emlog_user_ID' );
	$SQL->WHERE_and( 'user_login LIKE '.$DB->quote( '%'.$username.'%' ) );
	$count_SQL->WHERE_and( 'user_login LIKE '.$DB->quote( '%'.$username.'%' ) );
}
if( !empty( $title ) )
{
	$SQL->WHERE_and( 'emlog_subject LIKE '.$DB->quote( '%'.$title.'%' ) );
	$count_SQL->WHERE_and( 'emlog_subject LIKE '.$DB->quote( '%'.$title.'%' ) );
}


$Results = new Results( $SQL->get(), 'emlog_', 'D', $UserSettings->get( 'results_per_page' ), $count_SQL->get() );

$Results->title = T_('Sent emails').get_manual_link( 'sent-emails' );

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_email_sent( & $Form )
{
	global $datestart, $datestop, $email, $username, $title;

	if( ! empty( $username ) )
	{	// Get user by login:
		$UserCache = & get_UserCache();
		$sent_filter_User = & $UserCache->get_by_login( $username );
	}
	else
	{	// No filter by owner:
		$sent_filter_User = NULL;
	}

	$Form->date_input( 'datestartinput', $datestart, T_('From date') );
	$Form->date_input( 'datestopinput', $datestop, T_('To date') );
	$Form->text_input( 'email', $email, 40, T_('Email') );
	$Form->username( 'username', $sent_filter_User, T_('Username') );
	$Form->text_input( 'title', $title, 40, T_('Title') );
}
$Results->filter_area = array(
	'callback' => 'filter_email_sent',
	);
$Results->register_filter_preset( 'all', T_('All'), $admin_url.'?ctrl=email&amp;tab=sent' );

// Initialize Results object:
emails_sent_log_results( $Results );

// Display results:
$Results->display();

?>