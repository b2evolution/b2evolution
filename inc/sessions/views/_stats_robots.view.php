<?php
/**
 * This file implements the UI view for the robot stats.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * View funcs
 */
require_once dirname(__FILE__).'/_stats_view.funcs.php';


global $blog, $admin_url, $rsc_url, $AdminUI, $agent_type_color;

echo '<h2 class="page-title">'.T_('Hits from indexing robots / spiders / crawlers - Summary').get_manual_link( 'robots-hits-summary' ).'</h2>';

echo '<p class="notes">'.T_('In order to be detected, robots must be listed in /conf/_stats.php.').'</p>';

$SQL = new SQL();
$SQL->SELECT( 'SQL_NO_CACHE COUNT(*) AS hits, EXTRACT(YEAR FROM hit_datetime) AS year,'
	. 'EXTRACT(MONTH FROM hit_datetime) AS month, EXTRACT(DAY FROM hit_datetime) AS day' );
$SQL->FROM( 'T_hitlog' );
$SQL->WHERE( 'hit_agent_type = "robot"' );
if( $blog > 0 )
{
	$SQL->WHERE_and( 'hit_coll_ID = ' . $blog );
}
$SQL->GROUP_BY( 'year, month, day' );
$SQL->ORDER_BY( 'year DESC, month DESC, day DESC' );
$res_hits = $DB->get_results( $SQL->get(), ARRAY_A, 'Get robot summary' );


/*
 * Chart
 */
if( count($res_hits) )
{
	$last_date = 0;

	$chart[ 'chart_data' ][ 0 ] = array();
	$chart[ 'chart_data' ][ 1 ] = array();

	$chart['dates'] = array();

	// Initialize the data to open an url by click on bar item
	$chart['link_data'] = array();
	$chart['link_data']['url'] = $admin_url.'?ctrl=stats&tab=hits&datestartinput=$date$&datestopinput=$date$&blog='.$blog.'&agent_type=$param1$';
	$chart['link_data']['params'] = array(
			array( 'robot' )
		);

	$count = 0;
	foreach( $res_hits as $row_stats )
	{
		$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );
		if( $last_date != $this_date )
		{ // We just hit a new day, let's display the previous one:
			$last_date = $this_date;	// that'll be the next one
			$count ++;
			array_unshift( $chart[ 'chart_data' ][ 0 ], date( 'D '.locale_datefmt(), $last_date ) );
			array_unshift( $chart[ 'chart_data' ][ 1 ], 0 );

			array_unshift( $chart['dates'], $last_date );
		}
		$chart [ 'chart_data' ][1][0] = $row_stats['hits'];
	}

	array_unshift( $chart[ 'chart_data' ][ 0 ], '' );
	array_unshift( $chart[ 'chart_data' ][ 1 ], T_('Robot hits') );	// Translations need to be UTF-8

	$chart[ 'series_color' ] = array (
			$agent_type_color['robot'],
		);

	$chart[ 'canvas_bg' ] = array( 'width'  => 780, 'height' => 355 );

	echo '<div class="center">';
	load_funcs('_ext/_canvascharts.php');
	CanvasBarsChart( $chart );
	echo '</div>';

}



// TOP INDEXING ROBOTS
/* put this back when we have a CONCISE table of robots
// Create result set:
$SQL = new SQL();
$SQL->SELECT( 'SQL_NO_CACHE COUNT(*) AS hit_count, agnt_signature' );
$SQL->FROM( 'T_hitlog' );
$SQL->WHERE( 'hit_agent_type = "robot"' );
if( ! empty( $blog ) )
	$SQL->WHERE_and( 'hit_coll_ID = ' . $blog );
$SQL->GROUP_BY( 'agnt_signature' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'SQL_NO_CACHE COUNT( DISTINCT agnt_signature )' );
$count_SQL->FROM( $SQL->get_from( '' ) );
$count_SQL->WHERE( $SQL->get_where( '' ) );

$Results = new Results( $SQL->get(), 'topidx', '-D', 20, $count_SQL->get() );

$count_SQL->SELECT( 'SQL_NO_CACHE COUNT(*)' );
$total_hit_count = $DB->get_var( $count_SQL->get() );

$Results->title = T_('Top Indexing Robots');

/**
 * Helper function to translate agnt_signature to a "human-friendly" version from {@link $user_agents}.
 * @return string
 *
function translate_user_agent( $agnt_signature )
{
	global $user_agents;

	$html_signature = htmlspecialchars( $agnt_signature );
	$format = '<span title="'.$html_signature.'">%s</span>';

	foreach ($user_agents as $curr_user_agent)
	{
		if( strpos($agnt_signature, $curr_user_agent[1]) !== false )
		{
			return sprintf( $format, htmlspecialchars($curr_user_agent[2]) );
		}
	}

	if( ( $browscap = @get_browser( $agnt_signature ) ) && $browscap->browser != 'Default Browser' )
	{
		return sprintf( $format, htmlspecialchars( $browscap->browser ) );
	}

	return $html_signature;
}

// User agent:
$Results->cols[] = array(
		'th' => T_('Robot'),
		'order' => 'agnt_signature',
		'td' => '%translate_user_agent(\'$agnt_signature$\')%',
	);

// Hit count:
$Results->cols[] = array(
		'th' => T_('Hit count'),
		'order' => 'hit_count',
		'td_class' => 'right',
		'td' => '$hit_count$',
	);

// Hit %
$Results->cols[] = array(
		'th' => T_('Hit %'),
		'order' => 'hit_count',
		'td_class' => 'right',
		'td' => '%percentage( #hit_count#, '.$total_hit_count.' )%',
	);

// Display results:
$Results->display();
*/

?>