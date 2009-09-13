<?php
/**
 * This file implements the UI view for the robot stats.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * View funcs
 */
require_once dirname(__FILE__).'/_stats_view.funcs.php';


global $blog, $admin_url, $rsc_url, $AdminUI;

echo '<h2>'.T_('Robot hits').'</h2>';
echo '<p class="notes">'.sprintf( T_('This page only includes hits identified as made by <a %s>indexing robots</a> a.k.a. web crawlers.'), ' href="?ctrl=stats&amp;tab=useragents&amp;agnt_robot=1&amp;blog='.$blog.'"' ).'</p>';
echo '<p class="notes">'.T_('In order to be detected, robots must be listed in /conf/_stats.php.').'</p>';

$sql = "
	SELECT SQL_NO_CACHE COUNT(*) AS hits, EXTRACT(YEAR FROM hit_datetime) AS year,
			   EXTRACT(MONTH FROM hit_datetime) AS month, EXTRACT(DAY FROM hit_datetime) AS day
		FROM T_hitlog INNER JOIN T_useragents ON hit_agnt_ID = agnt_ID
	 WHERE agnt_type = 'robot'";
if( $blog > 0 )
{
	$sql .= ' AND hit_blog_ID = '.$blog;
}
$sql .= ' GROUP BY year, month, day
					ORDER BY year DESC, month DESC, day DESC';
$res_hits = $DB->get_results( $sql, ARRAY_A, 'Get robot summary' );


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
			'ff9900',
		);


	echo '<div class="center">';
	load_funcs('_ext/_swfcharts.php');
	DrawChart( $chart );
	echo '</div>';

}



// TOP INDEXING ROBOTS

// Create result set:
$sql = "SELECT SQL_NO_CACHE COUNT(*) AS hit_count, agnt_signature
					FROM T_hitlog INNER JOIN T_useragents ON hit_agnt_ID = agnt_ID
				 WHERE agnt_type = 'robot' "
				 			.( empty($blog) ? '' : 'AND hit_blog_ID = '.$blog ).'
				 GROUP BY agnt_signature';

$count_sql = "SELECT SQL_NO_CACHE COUNT( DISTINCT agnt_signature )
								FROM T_hitlog INNER JOIN T_useragents ON hit_agnt_ID = agnt_ID
							 WHERE agnt_type = 'robot' "
							 .( empty($blog) ? '' : 'AND hit_blog_ID = '.$blog );

$Results = & new Results( $sql, 'topidx', '-D', 20, $count_sql );

$total_hit_count = $DB->get_var( "
		SELECT SQL_NO_CACHE COUNT(*)
			FROM T_hitlog INNER JOIN T_useragents ON hit_agnt_ID = agnt_ID
		 WHERE agnt_type = 'robot' "
		.( empty($blog) ? '' : 'AND hit_blog_ID = '.$blog ) );

$Results->title = T_('Top Indexing Robots');

/**
 * Helper function to translate agnt_signature to a "human-friendly" version from {@link $user_agents}.
 * @return string
 */
function translate_user_agent( $agnt_signature )
{
	global $user_agents;

	foreach ($user_agents as $curr_user_agent)
	{
		if (stristr($agnt_signature, $curr_user_agent[1]))
		{
			return '<span title="'.htmlspecialchars($agnt_signature).'">'.htmlspecialchars($curr_user_agent[2]).'</span>';
		}
	}

	return htmlspecialchars($agnt_signature);
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


/*
 * $Log$
 * Revision 1.8  2009/09/13 21:26:50  blueyed
 * SQL_NO_CACHE for SELECT queries using T_hitlog
 *
 * Revision 1.7  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.6  2009/02/27 22:57:26  blueyed
 * Use load_funcs for swfcharts, and especially only include it when needed (in the stats controllers only, not main.inc)
 *
 * Revision 1.5  2008/02/14 05:45:38  fplanque
 * cleaned up stats
 *
 * Revision 1.4  2008/02/14 02:19:53  fplanque
 * cleaned up stats
 *
 * Revision 1.3  2008/01/21 18:16:33  personman2
 * Different chart bg colors for each admin skin
 *
 * Revision 1.2  2008/01/21 09:35:34  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:01:06  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.7  2007/04/26 00:11:13  fplanque
 * (c) 2007
 *
 * Revision 1.6  2007/03/20 09:53:26  fplanque
 * Letting boggers view their own stats.
 * + Letthing admins view the aggregate by default.
 *
 * Revision 1.5  2007/02/10 17:55:25  waltercruz
 * Changing double quotes to single quotes and the MySQL date functions to the standart ones
 *
 * Revision 1.4  2006/11/26 23:40:34  blueyed
 * trans
 *
 * Revision 1.3  2006/11/26 01:42:10  fplanque
 * doc
 */
?>