<?php
/**
 * This file implements the UI view for the general hit summary.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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

global $blog, $admin_url, $AdminUI;


echo '<h2>'.T_('Global hits summary').'</h2>';

echo '<p class="notes">'.sprintf( T_('This page includes all recorded hits, split down by <a %s>user agent</a> type.'), ' href="?ctrl=stats&tab=useragents&blog='.$blog.'"' ).'</p>';


// fplanque>> I don't get it, it seems that GROUP BY on the referer type ENUM fails pathetically!!
// Bug report: http://lists.mysql.com/bugs/36
// Solution : CAST to string
// TODO: I've also limited this to agnt_type "browser" here, according to the change for "referers" (Rev 1.6)
//       -> an RSS service that sends a referer is not a real referer (though it should be listed in the robots list)! (blueyed)
$sql = '
	SELECT COUNT(*) AS hits, agnt_type, EXTRACT(YEAR FROM hit_datetime) AS year,
			   EXTRACT(MONTH FROM hit_datetime) AS month, EXTRACT(DAY FROM hit_datetime) AS day
		FROM T_hitlog INNER JOIN T_useragents ON hit_agnt_ID = agnt_ID';
if( $blog > 0 )
{
	$sql .= ' WHERE hit_blog_ID = '.$blog;
}
$sql .= ' GROUP BY year, month, day, agnt_type
					ORDER BY year DESC, month DESC, day DESC, agnt_type';
$res_hits = $DB->get_results( $sql, ARRAY_A, 'Get hit summary' );


/*
 * Chart
 */
if( count($res_hits) )
{
	$last_date = 0;

	$col_mapping = array(
			'rss' => 1,
			'robot' => 2,
			'browser' => 3,
			'unknown' => 4,
		);

	$chart[ 'chart_data' ][ 0 ] = array();
	$chart[ 'chart_data' ][ 1 ] = array();
	$chart[ 'chart_data' ][ 2 ] = array();
	$chart[ 'chart_data' ][ 3 ] = array();
	$chart[ 'chart_data' ][ 4 ] = array();

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
				array_unshift( $chart[ 'chart_data' ][ 2 ], 0 );
				array_unshift( $chart[ 'chart_data' ][ 3 ], 0 );
				array_unshift( $chart[ 'chart_data' ][ 4 ], 0 );
		}
		$col = $col_mapping[$row_stats['agnt_type']];
		$chart['chart_data'][$col][0] = $row_stats['hits'];
	}

	array_unshift( $chart[ 'chart_data' ][ 0 ], '' );
	array_unshift( $chart[ 'chart_data' ][ 1 ], 'XML (RSS/Atom)' );
	array_unshift( $chart[ 'chart_data' ][ 2 ], 'Robots' );
	array_unshift( $chart[ 'chart_data' ][ 3 ], 'Browsers' );	// Translations need to be UTF-8
	array_unshift( $chart[ 'chart_data' ][ 4 ], 'Unknown' );

	// Include common chart properties:
	require dirname(__FILE__).'/inc/_bar_chart.inc.php';

	$chart[ 'series_color' ] = array (
			'ff6600',
			'ff9900',
			'ffcc00',
			'cccccc',
		);


	echo '<div class="center">';
	DrawChart( $chart );
	echo '</div>';


	/*
	 * Table:
	 */
	$hits = array(
		'browser' => 0,
		'robot' => 0,
		'rss' => 0,
		'unknown' => 0,
	);
	$hits_total = $hits;

	$last_date = 0;

	?>

	<table class="grouped" cellspacing="0">
		<tr>
			<th class="firstcol"><?php echo T_('Date') ?></th>
			<th style="background-color: #ff6600"><?php echo T_('RSS/Atom') ?></th>
			<th style="background-color: #ff9900"><?php echo T_('Robots') ?></th>
			<th style="background-color: #ffcc00"><?php echo T_('Browsers') ?></th>
			<th style="background-color: #cccccc"><?php echo T_('Unknown') ?></th>
			<th class="lastcol"><?php echo T_('Total') ?></th>
		</tr>
		<?php
		$count = 0;
		foreach( $res_hits as $row_stats )
		{
			$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );
			if( $last_date == 0 ) $last_date = $this_date;	// that'll be the first one
			if( $last_date != $this_date )
			{ // We just hit a new day, let's display the previous one:
				?>
				<tr class="<?php echo ( $count%2 == 1 ) ? 'odd' : 'even'; ?>">
					<td class="firstcol"><?php if( $current_User->check_perm( 'stats', 'edit' ) )
						{
							echo action_icon( T_('Prune hits for this date!'), 'delete', url_add_param( $admin_url, 'ctrl=stats&amp;action=prune&amp;date='.$last_date.'&amp;show=summary&amp;blog='.$blog ) );
						}
						echo date( locale_datefmt(), $last_date ) ?>
					</td>
					<td class="right"><?php echo $hits['rss'] ?></td>
					<td class="right"><?php echo $hits['robot'] ?></td>
					<td class="right"><?php echo $hits['browser'] ?></td>
					<td class="right"><?php echo $hits['unknown'] ?></td>
					<td class="lastcol right"><?php echo array_sum($hits) ?></td>
				</tr>
				<?php
					$hits = array(
						'browser' => 0,
						'robot' => 0,
						'rss' => 0,
						'unknown' => 0,
					);
					$last_date = $this_date;	// that'll be the next one
					$count ++;
			}

			// Increment hitcounter:
			$hits[$row_stats['agnt_type']] = $row_stats['hits'];
			$hits_total[$row_stats['agnt_type']] += $row_stats['hits'];
		}

		if( $last_date != 0 )
		{ // We had a day pending:
			?>
				<tr class="<?php echo ( $count%2 == 1 ) ? 'odd' : 'even'; ?>">
				<td class="firstcol"><?php if( $current_User->check_perm( 'stats', 'edit' ) )
					{
						echo action_icon( T_('Prune hits for this date!'), 'delete', url_add_param( $admin_url, 'ctrl=stats&amp;action=prune&amp;date='.$last_date.'&amp;show=summary&amp;blog='.$blog ) );
					}
					echo date( locale_datefmt(), $this_date ) ?>
				</td>
				<td class="right"><?php echo $hits['rss'] ?></td>
				<td class="right"><?php echo $hits['robot'] ?></td>
				<td class="right"><?php echo $hits['browser'] ?></td>
				<td class="right"><?php echo $hits['unknown'] ?></td>
				<td class="lastcol right"><?php echo array_sum($hits) ?></td>
			</tr>
			<?php
		}

		// Total numbers:
		?>

		<tr class="total">
		<td class="firstcol"><?php echo T_('Total') ?></td>
		<td class="right"><?php echo $hits_total['rss'] ?></td>
		<td class="right"><?php echo $hits_total['robot'] ?></td>
		<td class="right"><?php echo $hits_total['browser'] ?></td>
		<td class="right"><?php echo $hits_total['unknown'] ?></td>
		<td class="lastcol right"><?php echo array_sum($hits_total) ?></td>
		</tr>

	</table>

	<img src="<?php global $rsc_url; echo $rsc_url ?>img/blank.gif" width="1" height="1" /><!-- for IE -->
	<?php
}


/*
 * $Log$
 * Revision 1.7  2008/02/14 05:45:38  fplanque
 * cleaned up stats
 *
 * Revision 1.6  2008/02/14 02:19:53  fplanque
 * cleaned up stats
 *
 * Revision 1.5  2008/01/21 18:16:33  personman2
 * Different chart bg colors for each admin skin
 *
 * Revision 1.4  2008/01/21 09:35:34  fplanque
 * (c) 2008
 *
 * Revision 1.3  2008/01/05 17:17:36  blueyed
 * Fix output of rsc_url
 *
 * Revision 1.2  2007/09/03 19:36:06  fplanque
 * chicago admin skin
 *
 * Revision 1.1  2007/06/25 11:01:07  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.8  2007/04/26 00:11:13  fplanque
 * (c) 2007
 *
 * Revision 1.7  2007/02/10 17:57:16  waltercruz
 * Changing the MySQL date functions to the standart ones
 *
 * Revision 1.6  2006/11/26 01:42:10  fplanque
 * doc
 */
?>