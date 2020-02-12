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

// Create result set:

$SQL = new SQL();
$SQL->SELECT( 'SQL_NO_CACHE emret_ID, emret_timestamp, emret_address, emret_errormsg, emret_errtype' );
$SQL->FROM( 'T_email__returns' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'SQL_NO_CACHE COUNT(emret_ID)' );
$count_SQL->FROM( 'T_email__returns' );

if( !empty( $datestart ) )
{	// Filter by start date
	$SQL->WHERE_and( 'emret_timestamp >= '.$DB->quote( $datestart.' 00:00:00' ) );
	$count_SQL->WHERE_and( 'emret_timestamp >= '.$DB->quote( $datestart.' 00:00:00' ) );
}
if( !empty( $datestop ) )
{	// Filter by end date
	$SQL->WHERE_and( 'emret_timestamp <= '.$DB->quote( $datestop.' 23:59:59' ) );
	$count_SQL->WHERE_and( 'emret_timestamp <= '.$DB->quote( $datestop.' 23:59:59' ) );
}
if( !empty( $email ) )
{	// Filter by email
	$email = utf8_strtolower( $email );
	$SQL->WHERE_and( 'emret_address LIKE '.$DB->quote( '%'.$email.'%' ) );
	$count_SQL->WHERE_and( 'emret_address LIKE '.$DB->quote( '%'.$email.'%' ) );
}


$Results = new Results( $SQL->get(), 'emret_', 'D', $UserSettings->get( 'results_per_page' ), $count_SQL->get() );

$Results->title = T_('Returned emails').get_manual_link( 'email-returned' );

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_email_return( & $Form )
{
	global $datestart, $datestop, $email;

	$Form->date_input( 'datestartinput', $datestart, T_('From date') );
	$Form->date_input( 'datestopinput', $datestop, T_('To date') );
	$Form->text_input( 'email', $email, 40, T_('Email') );
}
$Results->filter_area = array(
	'callback' => 'filter_email_return',
	'presets' => array(
		'all' => array( T_('All'), $admin_url.'?ctrl=email&amp;tab=return'),
		)
	);

// Initialize Results object:
email_returns_results( $Results );

// Display results:
$Results->display();

?>