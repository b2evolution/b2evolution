<?php
/**
 * This file implements the UI view for the syndication stats.
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
 * @version $Id: _stats_syndication.view.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url, $rsc_url, $AdminUI, $agent_type_color;

echo '<h2>'.T_('Hits from RSS/Atom feed readers - Summary').get_manual_link( 'feed-hits-summary' ).'</h2>';

echo '<p class="notes">'.T_('Any user agent accessing the XML feeds will be flagged as an XML reader.').'</p>';

$sql = "
	SELECT SQL_NO_CACHE COUNT(*) AS hits, EXTRACT(YEAR FROM hit_datetime) AS year,
			   EXTRACT(MONTH FROM hit_datetime) AS month, EXTRACT(DAY FROM hit_datetime) AS day
		FROM T_hitlog
	 WHERE hit_type = 'rss'";
if( $blog > 0 )
{
	$sql .= ' AND hit_coll_ID = '.$blog;
}
$sql .= ' GROUP BY year, month, day
					ORDER BY year DESC, month DESC, day DESC';
$res_hits = $DB->get_results( $sql, ARRAY_A, 'Get rss summary' );


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
	array_unshift( $chart[ 'chart_data' ][ 1 ], 'XML (RSS/Atom) hits' );	// Translations need to be UTF-8

	// Include common chart properties:
	require dirname(__FILE__).'/inc/_bar_chart.inc.php';

	$chart[ 'series_color' ] = array (
			$agent_type_color['rss'],
		);

	echo '<div class="center">';
	load_funcs('_ext/_swfcharts.php');
	DrawChart( $chart );
	echo '</div>';

}

?>