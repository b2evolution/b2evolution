<?php
/**
 * This file implements the UI view for the browser hits summary.
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
 * @version $Id: _stats_browserhits.view.php 6479 2014-04-16 07:18:54Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url, $AdminUI, $referer_type_color, $hit_type_color;

echo '<h2>'.T_('Hits from web browsers - Summary').get_manual_link('browser_hits_summary').'</h2>';

// fplanque>> I don't get it, it seems that GROUP BY on the referer type ENUM fails pathetically!!
// Bug report: http://lists.mysql.com/bugs/36
// Solution : CAST to string
// waltercruz >> MySQL sorts ENUM columns according to the order in which the enumeration
// members were listed in the column specification, not the lexical order. Solution: CAST to string using using CONCAT
// or CAST (but CAST only works from MySQL 4.0.2)
// References:
// http://dev.mysql.com/doc/refman/5.0/en/enum.html
// http://dev.mysql.com/doc/refman/4.1/en/cast-functions.html
// TODO: I've also limited this to agent_type "browser" here, according to the change for "referers" (Rev 1.6)
//       -> an RSS service that sends a referer is not a real referer (though it should be listed in the robots list)! (blueyed)
$sql = '
	SELECT SQL_NO_CACHE COUNT(*) AS hits, CONCAT(hit_referer_type) AS referer_type, hit_type,  EXTRACT(YEAR FROM hit_datetime) AS year,
			   EXTRACT(MONTH FROM hit_datetime) AS month, EXTRACT(DAY FROM hit_datetime) AS day
		FROM T_hitlog
	 WHERE hit_agent_type = "browser"';

if( $blog > 0 )
{
	$sql .= ' AND hit_coll_ID = '.$blog;
}
$sql .= ' GROUP BY year, month, day, referer_type, hit_type
					ORDER BY year DESC, month DESC, day DESC, referer_type, hit_type';
$res_hits = $DB->get_results( $sql, ARRAY_A, 'Get hit summary' );

/*
 * Chart
 */
if( count($res_hits) )
{
	$last_date = 0;

	$col_mapping = array(
			'search'  => 1,
			'referer' => 2,
			'direct'  => 3,
			'self'    => 4,
			'ajax'    => 5,
			'special' => 6,
			'spam'    => 7,
			'admin'   => 8,
		);

	$chart[ 'chart_data' ][ 0 ] = array();
	$chart[ 'chart_data' ][ 1 ] = array();
	$chart[ 'chart_data' ][ 2 ] = array();
	$chart[ 'chart_data' ][ 3 ] = array();
	$chart[ 'chart_data' ][ 4 ] = array();
	$chart[ 'chart_data' ][ 5 ] = array();
	$chart[ 'chart_data' ][ 6 ] = array();
	$chart[ 'chart_data' ][ 7 ] = array();
	$chart[ 'chart_data' ][ 8 ] = array();

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
				array_unshift( $chart[ 'chart_data' ][ 5 ], 0 );
				array_unshift( $chart[ 'chart_data' ][ 6 ], 0 );
				array_unshift( $chart[ 'chart_data' ][ 7 ], 0 );
				array_unshift( $chart[ 'chart_data' ][ 8 ], 0 );
		}

		if ( $row_stats['hit_type'] == 'ajax' )
		{	// hit_type = ajax is the highest priority. If hit_type = ajax, then hit gets only to this column.
			$col = $col_mapping['ajax'];
			$chart [ 'chart_data' ][$col][0] += $row_stats['hits'];
		}
		else
		{
			if ( $row_stats['hit_type'] == 'admin' )
			{	// if hit_type = admin, then hits get only to this column.
				$col = $col_mapping['admin'];
				$chart [ 'chart_data' ][$col][0] += $row_stats['hits'];
			}
			else
			{	// all other hits come to this column
				$col = $col_mapping[$row_stats['referer_type']];
				$chart [ 'chart_data' ][$col][0] += $row_stats['hits'];
			}
		}


	}

	array_unshift( $chart[ 'chart_data' ][ 0 ], '' );
	array_unshift( $chart[ 'chart_data' ][ 1 ], 'Refering searches' );
	array_unshift( $chart[ 'chart_data' ][ 2 ], 'Referers' );
	array_unshift( $chart[ 'chart_data' ][ 3 ], 'Direct accesses' );	// Translations need to be UTF-8
	array_unshift( $chart[ 'chart_data' ][ 4 ], 'Self referred' );
	array_unshift( $chart[ 'chart_data' ][ 5 ], 'Ajax' );
	array_unshift( $chart[ 'chart_data' ][ 6 ], 'Special referrers' );
	array_unshift( $chart[ 'chart_data' ][ 7 ], 'Referer spam' );
	array_unshift( $chart[ 'chart_data' ][ 8 ], 'Admin' );

	// Include common chart properties:
	require dirname(__FILE__).'/inc/_bar_chart.inc.php';


	$chart[ 'series_color' ] = array (
			$referer_type_color['search'],
			$referer_type_color['referer'],
			$referer_type_color['direct'],
			$referer_type_color['self'],
			$hit_type_color['ajax'],
			$referer_type_color['special'],
			$referer_type_color['spam'],
			$referer_type_color['admin'],
		);

	echo '<div class="center">';
	load_funcs('_ext/_swfcharts.php');
	DrawChart( $chart );
	echo '</div>';


	/*
	 * Table:
	 */
	$hits = array(
		'direct' => 0,
		'referer' => 0,
		'search' => 0,
		'self' => 0,
		'ajax' => 0,
		'special' => 0,
		'spam' => 0,
		'admin' => 0,
	);
	$hits_total = $hits;

	$last_date = 0;

	?>

	<table class="grouped table table-striped table-bordered table-hover table-condensed" cellspacing="0">
		<tr>
			<th class="firstcol"><?php echo T_('Date') ?></th>
			<th style="background-color: #<?php echo $referer_type_color['search'] ?>"><?php echo T_('Refering searches') ?></th>
			<th style="background-color: #<?php echo $referer_type_color['referer'] ?>"><?php echo T_('Referers') ?></th>
			<th style="background-color: #<?php echo $referer_type_color['direct'] ?>"><?php echo T_('Direct accesses') ?></th>
			<th style="background-color: #<?php echo $referer_type_color['self'] ?>"><?php echo T_('Self referred') ?></th>
			<th style="background-color: #<?php echo $hit_type_color['ajax'] ?>"><?php echo T_('Ajax') ?></th>
			<th style="background-color: #<?php echo $referer_type_color['special'] ?>"><?php echo T_('Special referrers') ?></th>
			<th style="background-color: #<?php echo $referer_type_color['spam'] ?>"><?php echo T_('Referer spam') ?></th>
			<th style="background-color: #<?php echo $referer_type_color['admin'] ?>"><?php echo T_('Admin') ?></th>
			<th class="lastcol"><?php echo T_('Total') ?></th>
		</tr>
		<?php
		$count = 0;
		foreach( $res_hits as $row_stats )
		{
			$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );

			if( $last_date == 0 ) $last_date = $this_date;	// that'll be the first one

			$link_text = $admin_url.'?ctrl=stats&tab=hits&datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).'&blog='.$blog.'&agent_type=browser';
			$link_text_total_day = $admin_url.'?ctrl=stats&tab=hits&datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).'&blog='.$blog.'&agent_type=browser';

			if( $last_date != $this_date )
			{ // We just hit a new day, let's display the previous one:
				?>
				<tr class="<?php echo ( $count%2 == 1 ) ? 'odd' : 'even'; ?>">
					<td class="firstcol"><?php if( $current_User->check_perm( 'stats', 'edit' ) )
						{
							echo action_icon( T_('Prune hits for this date!'), 'delete', url_add_param( $admin_url, 'ctrl=stats&amp;action=prune&amp;date='.$last_date.'&amp;show=summary&amp;blog='.$blog.'&amp;'.url_crumb('stats') ) );
						}
						echo date( locale_datefmt(), $last_date ) ?>
					</td>
				<td class="right"><a href="<?php echo $link_text.'&referer_type=search'?>"><?php echo $hits['search'] ?></a></td>
				<td class="right"><a href="<?php echo $link_text.'&referer_type=referer'?>"><?php echo $hits['referer'] ?></a></td>
				<td class="right"><a href="<?php echo $link_text.'&referer_type=direct'?>"><?php echo $hits['direct'] ?></a></td>
				<td class="right"><a href="<?php echo $link_text.'&referer_type=self'?>"><?php echo $hits['self'] ?></a></td>
				<td class="right"><a href="<?php echo $link_text.'&hit_type=ajax'?>"><?php echo $hits['ajax'] ?></a></td>
				<td class="right"><a href="<?php echo $link_text.'&referer_type=special'?>"><?php echo $hits['special'] ?></a></td>
				<td class="right"><a href="<?php echo $link_text.'&referer_type=spam'?>"><?php echo $hits['spam'] ?></a></td>
				<td class="right"><a href="<?php echo $link_text.'&hit_type=admin'?>"><?php echo $hits['admin'] ?></a></td>
				<td class="lastcol right"><a href="<?php echo $link_text_total_day ?>"><?php echo array_sum($hits) ?></a></td>
				</tr>
				<?php
					$hits = array(
						'direct' => 0,
						'referer' => 0,
						'search' => 0,
						'self' => 0,
						'ajax' => 0,
						'special' => 0,
						'spam' => 0,
						'admin' => 0,
					);
					$last_date = $this_date;	// that'll be the next one
					$count ++;
			}

			// Increment hitcounter:

			if ( $row_stats['hit_type'] == 'ajax' )
			{
				$hits['ajax'] += $row_stats['hits'];
				$hits_total['ajax'] += $row_stats['hits'];
			}
			else
			{
				if ( $row_stats['hit_type'] == 'admin' )
				{
					$hits['admin'] += $row_stats['hits'];
					$hits_total['admin'] += $row_stats['hits'];
				}
				else
				{
					$hits[$row_stats['referer_type']] += $row_stats['hits'];
					$hits_total[$row_stats['referer_type']] += $row_stats['hits'];
				}
			}

		}

		if( $last_date != 0 )
		{ // We had a day pending:
			$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );

			$link_text = $admin_url.'?ctrl=stats&tab=hits&datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).'&blog='.$blog.'&agent_type=browser';
			$link_text_total_day = $admin_url.'?ctrl=stats&tab=hits&datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).'&blog='.$blog.'&agent_type=browser';
			?>
				<tr class="<?php echo ( $count%2 == 1 ) ? 'odd' : 'even'; ?>">
				<td class="firstcol"><?php if( $current_User->check_perm( 'stats', 'edit' ) )
					{
						echo action_icon( T_('Prune hits for this date!'), 'delete', url_add_param( $admin_url, 'ctrl=stats&amp;action=prune&amp;date='.$last_date.'&amp;show=summary&amp;blog='.$blog.'&amp;'.url_crumb('stats') ) );
					}
					echo date( locale_datefmt(), $this_date ) ?>
				</td>
				<td class="right"><a href="<?php echo $link_text.'&referer_type=search'?>"><?php echo $hits['search'] ?></a></td>
				<td class="right"><a href="<?php echo $link_text.'&referer_type=referer'?>"><?php echo $hits['referer'] ?></a></td>
				<td class="right"><a href="<?php echo $link_text.'&referer_type=direct'?>"><?php echo $hits['direct'] ?></a></td>
				<td class="right"><a href="<?php echo $link_text.'&referer_type=self'?>"><?php echo $hits['self'] ?></a></td>
				<td class="right"><a href="<?php echo $link_text.'&hit_type=ajax'?>"><?php echo $hits['ajax'] ?></a></td>
				<td class="right"><a href="<?php echo $link_text.'&referer_type=special'?>"><?php echo $hits['special'] ?></a></td>
				<td class="right"><a href="<?php echo $link_text.'&referer_type=spam'?>"><?php echo $hits['spam'] ?></a></td>
				<td class="right"><a href="<?php echo $link_text.'&hit_type=admin'?>"><?php echo $hits['admin'] ?></a></td>
				<td class="lastcol right"><a href="<?php echo $link_text_total_day ?>"><?php echo array_sum($hits) ?></a></td>
			</tr>
			<?php
		}

		// Total numbers:

		$link_text_total = $admin_url.'?ctrl=stats&tab=hits&blog='.$blog.'&agent_type=browser';
		?>

		<tr class="total">
		<td class="firstcol"><?php echo T_('Total') ?></td>
		<td class="right"><a href="<?php echo $link_text_total.'&referer_type=search'?>"><?php echo $hits_total['search'] ?></a></td>
		<td class="right"><a href="<?php echo $link_text_total.'&referer_type=referer'?>"><?php echo $hits_total['referer'] ?></a></td>
		<td class="right"><a href="<?php echo $link_text_total.'&referer_type=direct'?>"><?php echo $hits_total['direct'] ?></a></td>
		<td class="right"><a href="<?php echo $link_text_total.'&referer_type=self'?>"><?php echo $hits_total['self'] ?></a></td>
		<td class="right"><a href="<?php echo $link_text_total.'&hit_type=ajax'?>"><?php echo $hits_total['ajax'] ?></a></td>
		<td class="right"><a href="<?php echo $link_text_total.'&referer_type=special'?>"><?php echo $hits_total['special'] ?></a></td>
		<td class="right"><a href="<?php echo $link_text_total.'&referer_type=spam'?>"><?php echo $hits_total['spam'] ?></a></td>
		<td class="right"><a href="<?php echo $link_text_total.'&hit_type=admin'?>"><?php echo $hits_total['admin'] ?></a></td>
		<td class="lastcol right"><a href="<?php echo $link_text_total?>"><?php echo array_sum($hits_total) ?></a></td>
		</tr>

	</table>

	<?php
}

?>