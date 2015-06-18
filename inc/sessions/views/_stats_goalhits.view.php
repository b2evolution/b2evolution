<?php
/**
 * This file implements the UI view for the Goal Hit list.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url, $rsc_url;
global $Session, $UserSettings;

/**
 * View funcs
 */
require_once dirname(__FILE__).'/_stats_view.funcs.php';

global $datestartinput, $datestart, $datestopinput, $datestop;

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

$exclude = param( 'exclude', 'integer', 0, true );
$sess_ID = param( 'sess_ID', 'integer', NULL, true );
$goal_name = param( 'goal_name', 'string', NULL, true );
$goal_cat = param( 'goal_cat', 'integer', 0, true );

if( param_errors_detected() )
{
	$sql = 'SELECT 0 AS count';
	$sql_count = 0;
}
else
{
	// Create result set:
	$SQL = new SQL();
	$SQL->SELECT( 'hit_ID, sess_ID, hit_datetime, hit_referer_type, hit_uri, hit_coll_ID, hit_referer, hit_remote_addr,
									user_login, hit_agent_type, dom_name, goal_name, keyp_phrase, gcat_color, ghit_params' );
	$SQL->FROM( 'T_track__goalhit LEFT JOIN T_hitlog ON ghit_hit_ID = hit_ID
									LEFT JOIN T_basedomains ON dom_ID = hit_referer_dom_ID
									LEFT JOIN T_track__keyphrase ON hit_keyphrase_keyp_ID = keyp_ID
									LEFT JOIN T_sessions ON hit_sess_ID = sess_ID
									LEFT JOIN T_users ON sess_user_ID = user_ID
									LEFT JOIN T_track__goal ON ghit_goal_ID = goal_ID
									LEFT JOIN T_track__goalcat ON gcat_ID = goal_gcat_ID' );

	$SQL_count = new SQL();
	$SQL_count->SELECT( 'COUNT(ghit_ID)' );
	$SQL_count->FROM( 'T_track__goalhit LEFT JOIN T_hitlog ON ghit_hit_ID = hit_ID' );

	if( !empty($datestart) )
	{
		$SQL->WHERE_and( 'hit_datetime >= '.$DB->quote($datestart.' 00:00:00') );
		$SQL_count->WHERE_and( 'hit_datetime >= '.$DB->quote($datestart.' 00:00:00') );
	}
	if( !empty($datestop) )
	{
		$SQL->WHERE_and( 'hit_datetime <= '.$DB->quote($datestop.' 23:59:59') );
		$SQL_count->WHERE_and( 'hit_datetime <= '.$DB->quote($datestop.' 23:59:59') );
	}

	if( !empty($sess_ID) )
	{	// We want to filter on the session ID:
		$operator = ($exclude ? ' <> ' : ' = ' );
		$SQL->WHERE_and( 'hit_sess_ID'.$operator.$sess_ID );
		$SQL_count->FROM_add( 'LEFT JOIN T_sessions ON hit_sess_ID = sess_ID' );
		$SQL_count->WHERE_and( 'hit_sess_ID'.$operator.$sess_ID );
	}

	if( ! empty( $goal_name ) || ! empty( $goal_cat ) )
	{
		$SQL_count->FROM_add( 'LEFT JOIN T_track__goal ON ghit_goal_ID = goal_ID' );
		if( ! empty( $goal_name ) ) // TODO: allow combine
		{ // We want to filter on the goal name:
			$operator = ($exclude ? ' NOT LIKE ' : ' LIKE ' );
			$SQL->WHERE_and( 'goal_name'.$operator.$DB->quote($goal_name.'%') );
			$SQL_count->WHERE_and( 'goal_name'.$operator.$DB->quote($goal_name.'%') );
		}

		if( ! empty( $goal_cat ) )
		{ // We want to filter on the goal category:
			$operator = ($exclude ? ' != ' : ' = ' );
			$SQL->WHERE_and( 'goal_gcat_ID'.$operator.$DB->quote( $goal_cat ) );
			$SQL_count->WHERE_and( 'goal_gcat_ID'.$operator.$DB->quote( $goal_cat ) );
		}
	}

	$sql = $SQL->get();
	$sql_count = $SQL_count->get();
}

$Results = new Results( $sql, 'ghits_', '--D', $UserSettings->get( 'results_per_page' ), $sql_count );

$Results->title = T_('Recent goal hits').get_manual_link( 'goal-hits' );

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_goal_hits( & $Form )
{
	global $datestart, $datestop;

	$Form->date_input( 'datestartinput', $datestart, T_('From') );
	$Form->date_input( 'datestopinput', $datestop, T_('to') );

	$Form->checkbox_basic_input( 'exclude', get_param('exclude'), T_('Exclude').' &rarr; ' );
	$Form->text_input( 'sess_ID', get_param('sess_ID'), 15, T_('Session ID'), '', array( 'maxlength'=>20 ) );
	$Form->text_input( 'goal_name', get_param('goal_name'), 20, T_('Goal names starting with'), '', array( 'maxlength'=>50 ) );

	$GoalCategoryCache = & get_GoalCategoryCache( NT_('All') );
	$GoalCategoryCache->load_all();
	$Form->select_input_object( 'goal_cat', get_param('goal_cat'), $GoalCategoryCache, T_('Goal category'), array( 'allow_none' => true ) );
}
$Results->filter_area = array(
	'callback' => 'filter_goal_hits',
	'url_ignore' => 'results_hits_page,exclude,sess_ID,goal_name,datestartinput,datestart,datestopinput,datestop',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=stats&amp;tab=goals&amp;tab3=hits&amp;blog='.$blog ),
		'all_but_curr' => array( T_('All but current session'), '?ctrl=stats&amp;tab=goals&amp;tab3=hits&amp;blog='.$blog.'&amp;sess_ID='.$Session->ID.'&amp;exclude=1' ),
		)
	);

$Results->cols[] = array(
		'th' => T_('Session'),
		'order' => 'hit_sess_ID',
		'td_class' => 'right',
		'td' => '<a href="?ctrl=stats&amp;tab=hits&amp;blog=0&amp;sess_ID=$sess_ID$">$sess_ID$</a>',
	);

$Results->cols[] = array(
		'th' => T_('User'),
		'order' => 'user_login',
		'td' => '%stat_session_login( #user_login# )%',
	);

$Results->cols[] = array(
		'th' => T_('Date Time'),
		'order' => 'ghit_ID',
		'default_dir' => 'D',
		'td_class' => 'timestamp',
		'td' => '%mysql2localedatetime_spans( #hit_datetime#, "M-d" )%',
 	);

$Results->cols[] = array(
		'th' => T_('Type'),
		'order' => 'hit_referer_type',
		'td' => '$hit_referer_type$',
	);

$Results->cols[] = array(
		'th' => T_('U.A.'),
		'order' => 'hit_agent_type',
		'td' => '$hit_agent_type$',
	);

$Results->cols[] = array(
		'th' => T_('Referer'),
		'order' => 'dom_name',
		'td_class' => 'nowrap',
		'td' => '<a href="$hit_referer$">$dom_name$</a>',
	);

// Keywords:
$Results->cols[] = array(
		'th' => T_('Search keywords'),
		'order' => 'keyp_phrase',
		'td' => '%stats_search_keywords( #keyp_phrase# )%',
	);

$Results->cols[] = array(
		'th' => T_('Goal'),
		'order' => 'goal_name',
		'default_dir' => 'D',
		'td' => '$goal_name$',
		'extra' => array( 'style' => 'color:#gcat_color#' )
	);

$Results->cols[] = array(
		'th' => T_('Extra params'),
		'order' => 'ghit_params',
		'default_dir' => 'D',
		'td' => '%stats_goal_hit_extra_params( #ghit_params# )%',
	);

// Display results:
$Results->display();

?>