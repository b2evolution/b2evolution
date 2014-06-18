<?php
/**
 * This file implements the UI view for the robot stats.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * @version $Id: _stats_robots.view.php 6225 2014-03-16 10:01:05Z attila $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * View funcs
 */
require_once dirname(__FILE__).'/_stats_view.funcs.php';


global $blog, $admin_url, $rsc_url, $AdminUI, $agent_type_color;

echo '<h2>'.T_('Hits from indexing robots / spiders / crawlers - Summary').get_manual_link( 'robots-hits-summary' ).'</h2>';

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

	$count = 0;
	foreach( $res_hits as $row_stats )
	{
		$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );
		if( $last_date != $this_date )
		{ // We just hit a new day, let's display the previous one:
				$last_date = $this_date;	// that'll be the next one
				$count ++;
				array_unshift( $chart[ 'chart_data' ][ 0 ], date( locale_datefmt(), $last_date ) );
				array_unshift( $chart[ 'chart_data' ][ 1 ], 0 );
		}
		$chart [ 'chart_data' ][1][0] = $row_stats['hits'];
	}

	array_unshift( $chart[ 'chart_data' ][ 0 ], '' );
	array_unshift( $chart[ 'chart_data' ][ 1 ], 'Robot hits' );	// Translations need to be UTF-8

	// Include common chart properties:
	require dirname(__FILE__).'/inc/_bar_chart.inc.php';

	$chart[ 'series_color' ] = array (
			$agent_type_color['robot'],
		);


	echo '<div class="center">';
	load_funcs('_ext/_swfcharts.php');
	DrawChart( $chart );
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

	$html_signature = evo_htmlspecialchars( $agnt_signature );
	$format = '<span title="'.$html_signature.'">%s</span>';

	foreach ($user_agents as $curr_user_agent)
	{
		if( strpos($agnt_signature, $curr_user_agent[1]) !== false )
		{
			return sprintf( $format, evo_htmlspecialchars($curr_user_agent[2]) );
		}
	}

	if( ( $browscap = @get_browser( $agnt_signature ) ) && $browscap->browser != 'Default Browser' )
	{
		return sprintf( $format, evo_htmlspecialchars( $browscap->browser ) );
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