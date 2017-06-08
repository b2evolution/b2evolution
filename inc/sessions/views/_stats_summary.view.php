<?php
/**
 * This file implements the UI view for the general hit summary.
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

global $blog, $admin_url, $AdminUI, $agent_type_color, $hit_type_color, $Hit, $Settings, $localtimenow;

echo '<h2 class="page-title">'.T_('Global hits - Summary').get_manual_link('global_hits_summary').'</h2>';

// Display panel with buttons to control a view of hits summary pages:
display_hits_summary_panel();

// Check if it is a mode to display a live data:
$is_live_mode = ( get_hits_summary_mode() == 'live' );

// fplanque>> I don't get it, it seems that GROUP BY on the referer type ENUM fails pathetically!!
// Bug report: http://lists.mysql.com/bugs/36
// Solution : CAST to string
// TODO: I've also limited this to hit_agent_type "browser" here, according to the change for "referers" (Rev 1.6)
//       -> an RSS service that sends a referer is not a real referer (though it should be listed in the robots list)! (blueyed)
$SQL = new SQL( 'Get global hits summary ('.( $is_live_mode ? 'Live data' : 'Aggregate data' ).')' );
if( $is_live_mode )
{	// Get the live data:
	$SQL->SELECT( 'SQL_NO_CACHE COUNT( * ) AS hits, hit_agent_type, hit_type,
		EXTRACT( YEAR FROM hit_datetime ) AS year,
		EXTRACT( MONTH FROM hit_datetime ) AS month,
		EXTRACT( DAY FROM hit_datetime ) AS day' );
	$SQL->FROM( 'T_hitlog' );
	if( $blog > 0 )
	{	// Filter by collection:
		$SQL->WHERE( 'hit_coll_ID = '.$DB->quote( $blog ) );
	}

	$hits_start_date = NULL;
	$hits_end_date = date( 'Y-m-d' );
}
else
{	// Get the aggregated data:
	$SQL->SELECT( 'SUM( hagg_count ) AS hits, hagg_agent_type AS hit_agent_type, hagg_type AS hit_type,
		EXTRACT( YEAR FROM hagg_date ) AS year,
		EXTRACT( MONTH FROM hagg_date ) AS month,
		EXTRACT( DAY FROM hagg_date ) AS day' );
	$SQL->FROM( 'T_hits__aggregate' );
	if( $blog > 0 )
	{	// Filter by collection:
		$SQL->WHERE( 'hagg_coll_ID = '.$DB->quote( $blog ) );
	}
	// Filter by date:
	list( $hits_start_date, $hits_end_date ) = get_filter_aggregated_hits_dates();
	$SQL->WHERE_and( 'hagg_date >= '.$DB->quote( $hits_start_date ) );
	$SQL->WHERE_and( 'hagg_date <= '.$DB->quote( $hits_end_date ) );
}
$SQL->GROUP_BY( 'year, month, day, hit_agent_type, hit_type' );
$SQL->ORDER_BY( 'year DESC, month DESC, day DESC, hit_agent_type, hit_type' );
$res_hits = $DB->get_results( $SQL->get(), ARRAY_A, $SQL->title );


/*
 * Chart
 */
if( count( $res_hits ) )
{
	// Find the dates without hits and fill them with 0 to display on graph and table:
	$res_hits = fill_empty_hit_days( $res_hits, $hits_start_date, $hits_end_date );

	$last_date = 0;

	// This defines what hits will go where
	// This maps a 'hit_type' (from any agent type that is 'browser' or 'robot') to a column
	// OR it can also map 'hit_type'_'hit_agent_type' (concatenated with _ ) to a column
	// OR the 'unknown' column will get ANY hits from an unknown user agent (this will go to the "other" column)
	$col_mapping = array(
			'rss'              => 1, // Dark orange
			'standard_robot'   => 2, // Orange
			'standard_browser' => 3, // Yello
			'ajax'             => 4, // green
			'service'          => 5, // dark blue
			'admin'            => 6, // light blue
			'api'              => 7, // light blue
			'unknown'          => 8, // Grey - "Other column"
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

	$chart['dates'] = array();

	// Initialize the data to open an url by click on bar item:
	$chart['link_data'] = array();
	$chart['link_data']['url'] = $admin_url.'?ctrl=stats&tab=hits&datestartinput=$date$&datestopinput=$date$&blog='.$blog.'&hit_type=$param1$&agent_type=$param2$';
	$chart['link_data']['params'] = array(
			array( 'rss',      '' ),
			array( 'standard', 'robot' ),
			array( 'standard', 'browser' ),
			array( 'ajax',     '' ),
			array( 'service',  '' ),
			array( 'admin',    '' ),
			array( 'api',      '' ),
			array( '',         'unknown' )
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
			array_unshift( $chart[ 'chart_data' ][ 2 ], 0 );
			array_unshift( $chart[ 'chart_data' ][ 3 ], 0 );
			array_unshift( $chart[ 'chart_data' ][ 4 ], 0 );
			array_unshift( $chart[ 'chart_data' ][ 5 ], 0 );
			array_unshift( $chart[ 'chart_data' ][ 6 ], 0 );
			array_unshift( $chart[ 'chart_data' ][ 7 ], 0 );
			array_unshift( $chart[ 'chart_data' ][ 8 ], 0 );

			array_unshift( $chart['dates'], $last_date );
		}

		if( $row_stats['hit_agent_type'] == 'unknown' )
		{	// only those hits are calculated which hit_agent_type = unknown
			$col = $col_mapping[$row_stats['hit_agent_type']];
			$chart['chart_data'][$col][0] += $row_stats['hits'];
		}
		else
		{
			if( ! empty ( $col_mapping[$row_stats['hit_type'].'_'.$row_stats['hit_agent_type']] ) )
			{	// those hits are calculated here if hit_type = standard and hit_agent_type = browser, robot
				$col = $col_mapping[$row_stats['hit_type'].'_'.$row_stats['hit_agent_type']];
				$chart['chart_data'][$col][0] += $row_stats['hits'];
			}
			if( ! empty ( $col_mapping[$row_stats['hit_type']] ) )
			{	// those hits are calculated here which did not match either of the above rules
				$col = $col_mapping[$row_stats['hit_type']];
				$chart['chart_data'][$col][0] += $row_stats['hits'];
			}

		}
	}

	/*
	ONE COLOR for hit_type = ajax
	ONE COLOR for hit_type = service
	ONE COLOR for hit_type = rss
	ONE COLOR for hit_type = admin
	ONE COLOR for hit_type = standard AND agent_type = robots
	ONE COLOR for hit_type = standard AND agent_type <> robots
	ONE COLOR (grey) for hit_type = anything that is not above
	*/
	array_unshift( $chart[ 'chart_data' ][ 0 ], '' );
	array_unshift( $chart[ 'chart_data' ][ 1 ], T_('XML (RSS/Atom)') );
	array_unshift( $chart[ 'chart_data' ][ 2 ], T_('Standard/Robots') );
	array_unshift( $chart[ 'chart_data' ][ 3 ], T_('Standard/Browsers') );
	array_unshift( $chart[ 'chart_data' ][ 4 ], T_('Ajax') );
	array_unshift( $chart[ 'chart_data' ][ 5 ], T_('Service') );
	array_unshift( $chart[ 'chart_data' ][ 6 ], T_('Admin') );
	array_unshift( $chart[ 'chart_data' ][ 7 ], T_('API') );
	array_unshift( $chart[ 'chart_data' ][ 8 ], T_('Other') );

	$chart[ 'series_color' ] = array (
			$hit_type_color['rss'],
			$hit_type_color['standard_robot'],
			$hit_type_color['standard_browser'],
			$hit_type_color['ajax'],
			$hit_type_color['service'],
			$hit_type_color['admin'],
			$hit_type_color['api'],
			$agent_type_color['unknown'],
		);

	$chart[ 'canvas_bg' ] = array( 'width'  => '100%', 'height' => 355 );

	echo '<div class="center">';
	load_funcs('_ext/_canvascharts.php');
	CanvasBarsChart( $chart );
	echo '</div>';


	/*
	 * Table:
	 */

	$hits = array(
			'ajax'             => 0,
			'service'          => 0,
			'rss'              => 0,
			'admin'            => 0,
			'standard_robot'   => 0,
			'standard_browser' => 0,
			'api'              => 0,
			'unknown'          => 0,
		);

	$hits_total = $hits;

	$last_date = 0;


	echo '<table class="grouped table table-striped table-bordered table-hover table-condensed" cellspacing="0">';
	echo '<tr>';
	echo '<th class="firstcol">'.T_('Date').'</th>';
	echo '<th style="background-color: #'.$hit_type_color['rss'].'"><a href="?ctrl=stats&amp;tab=hits&amp;hit_type=rss&amp;blog='.$blog.'">'.T_('RSS/Atom').'</a></th>';
	echo '<th style="background-color: #'.$hit_type_color['standard_robot'].'"><a href="?ctrl=stats&amp;tab=hits&amp;hit_type=standard&amp;agent_type=robot&amp;blog='.$blog.'">'.T_('Standard/Robots').'</a></th>';
	echo '<th style="background-color: #'.$hit_type_color['standard_browser'].'"><a href="?ctrl=stats&amp;tab=hits&amp;hit_type=standard&amp;agent_type=browser&amp;blog='.$blog.'">'.T_('Standard/Browsers').'</a></th>';
	echo '<th style="background-color: #'.$hit_type_color['ajax'].'"><a href="?ctrl=stats&amp;tab=hits&amp;hit_type=ajax&amp;blog='.$blog.'">'.T_('Ajax').'</a></th>';
	echo '<th style="background-color: #'.$hit_type_color['service'].'"><a href="?ctrl=stats&amp;tab=hits&amp;hit_type=service&amp;blog='.$blog.'">'.T_('Service').'</a></th>';
	echo '<th style="background-color: #'.$hit_type_color['admin'].'"><a href="?ctrl=stats&amp;tab=hits&amp;hit_type=admin&amp;blog='.$blog.'">'.T_('Admin').'</a></th>';
	echo '<th style="background-color: #'.$hit_type_color['api'].'"><a href="?ctrl=stats&amp;tab=hits&amp;hit_type=api&amp;blog='.$blog.'">'.T_('API').'</a></th>';
	echo '<th style="background-color: #'.$agent_type_color['unknown'].'"><a href="?ctrl=stats&amp;tab=hits&amp;agent_type=unknown&amp;blog='.$blog.'">'.T_('Other').'</a></th>';
	echo '<th class="lastcol">'.T_('Total').'</th>';
	echo '</tr>';

	$count = 0;
	foreach( $res_hits as $row_stats )
	{
		$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );
		if( $last_date == 0 ) $last_date = $this_date;	// that'll be the first one

		$link_text = $admin_url.'?ctrl=stats&tab=hits&datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).'&blog='.$blog;
		$link_text_total_day = $admin_url.'?ctrl=stats&tab=hits&datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).'&blog='.$blog;


		if( $last_date != $this_date )
		{	// We just hit a new day, let's display the previous one:

			// Check if current data are live and not aggregated:
			$is_live_data = true;
			if( ! $is_live_mode )
			{	// Check only for "Aggregate data":
				$time_prune_before = mktime( 0, 0, 0 ) - ( $Settings->get( 'auto_prune_stats' ) * 86400 );
				$is_live_data = $last_date >= $time_prune_before;
			}
			?>
			<tr class="<?php echo ( $count%2 == 1 ) ? 'odd' : 'even'; ?>">
				<td class="firstcol right"><?php
					echo date( 'D '.locale_datefmt(), $last_date );
					if( $is_live_mode && $current_User->check_perm( 'stats', 'edit' ) )
					{	// Display a link to prune hits only for live data and if current user has a permission:
						echo action_icon( T_('Prune hits for this date!'), 'delete', url_add_param( $admin_url, 'ctrl=stats&amp;action=prune&amp;date='.$last_date.'&amp;show=summary&amp;blog='.$blog.'&amp;'.url_crumb('stats') ) );
					}
				?></td>
				<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text.'&hit_type=rss">'.$hits['rss'].'</a>' : $hits['rss']; ?></td>
				<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text.'&hit_type=standard&agent_type=robot">'.$hits['standard_robot'].'</a>' : $hits['standard_robot']; ?></td>
				<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text.'&hit_type=standard&agent_type=browser">'.$hits['standard_browser'].'</a>' : $hits['standard_browser']; ?></td>
				<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text.'&hit_type=ajax">'.$hits['ajax'].'</a>' : $hits['ajax']; ?></td>
				<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text.'&hit_type=service">'.$hits['service'].'</a>' : $hits['service']; ?></td>
				<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text.'&hit_type=admin">'.$hits['admin'].'</a>' : $hits['admin']; ?></td>
				<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text.'&hit_type=api">'.$hits['api'].'</a>' : $hits['api']; ?></td>
				<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text.'&agent_type=unknown">'.$hits['unknown'].'</a>' : $hits['unknown']; ?></td>
				<td class="lastcol right"><?php echo $is_live_data ? '<a href="'.$link_text_total_day.'">'.array_sum( $hits ).'</a>' : array_sum( $hits ); ?></td>
			</tr>
			<?php
				$hits = array(
					'ajax'             => 0,
					'service'          => 0,
					'rss'              => 0,
					'admin'            => 0,
					'standard_robot'   => 0,
					'standard_browser' => 0,
					'api'              => 0,
					'unknown'          => 0,
				);
				$last_date = $this_date;	// that'll be the next one
				$count ++;
		}

		// Increment hitcounter:
		if( ! empty( $col_mapping[$row_stats['hit_type'].'_'.$row_stats['hit_agent_type']] ) )
		{	// We have a column for this narrow type:
			$hits[$row_stats['hit_type'].'_'.$row_stats['hit_agent_type']] += $row_stats['hits'];
			$hits_total[$row_stats['hit_type'].'_'.$row_stats['hit_agent_type']] += $row_stats['hits'];
		}
		elseif( !empty( $col_mapping[$row_stats['hit_type']]) )
		{	// We have a column for this broad type:
			$hits[$row_stats['hit_type']] += $row_stats['hits'];
			$hits_total[$row_stats['hit_type']] += $row_stats['hits'];
		}
		else
		{ // We have no column for this hit_type, This will go to the "Other" column.
			// Note: this will never happen if all hit_types are properly defined in  $col_mapping
			$hits[$row_stats['hit_agent_type']] += $row_stats['hits'];
			$hits_total[$row_stats['hit_agent_type']] += $row_stats['hits'];
		}

	}

	if( $last_date != 0 )
	{ // We had a day pending:
		$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );

		$link_text = $admin_url.'?ctrl=stats&tab=hits&datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).'&blog='.$blog;
		$link_text_total_day = $admin_url.'?ctrl=stats&tab=hits&datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).'&blog='.$blog;

		// Check if current data are live and not aggregated:
		$is_live_data = true;
		if( ! $is_live_mode )
		{	// Check only for "Aggregate data":
			$time_prune_before = mktime( 0, 0, 0 ) - ( $Settings->get( 'auto_prune_stats' ) * 86400 );
			$is_live_data = $last_date >= $time_prune_before;
		}
		?>
			<tr class="<?php echo ( $count%2 == 1 ) ? 'odd' : 'even'; ?>">
				<td class="firstcol right"><?php
				echo date( 'D '.locale_datefmt(), $this_date );
				if( $is_live_mode && $current_User->check_perm( 'stats', 'edit' ) )
				{	// Display a link to prune hits only for live data and if current user has a permission:
					echo action_icon( T_('Prune hits for this date!'), 'delete', url_add_param( $admin_url, 'ctrl=stats&amp;action=prune&amp;date='.$last_date.'&amp;show=summary&amp;blog='.$blog.'&amp;'.url_crumb('stats') ) );
				}
				?></td>
				<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text.'&hit_type=rss">'.$hits['rss'].'</a>' : $hits['rss']; ?></td>
				<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text.'&hit_type=standard&agent_type=robot">'.$hits['standard_robot'].'</a>' : $hits['standard_robot']; ?></td>
				<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text.'&hit_type=standard&agent_type=browser">'.$hits['standard_browser'].'</a>' : $hits['standard_browser']; ?></td>
				<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text.'&hit_type=ajax">'.$hits['ajax'].'</a>' : $hits['ajax']; ?></td>
				<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text.'&hit_type=service">'.$hits['service'].'</a>' : $hits['service']; ?></td>
				<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text.'&hit_type=admin">'.$hits['admin'].'</a>' : $hits['admin']; ?></td>
				<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text.'&hit_type=api">'.$hits['api'].'</a>' : $hits['api']; ?></td>
				<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text.'&agent_type=unknown">'.$hits['unknown'].'</a>' : $hits['unknown']; ?></td>
				<td class="lastcol right"><?php echo $is_live_data ? '<a href="'.$link_text_total_day.'">'.array_sum( $hits ).'</a>' : array_sum( $hits ); ?></td>
			</tr>
		<?php
	}

	// Total numbers:
	$link_text_total = $admin_url.'?ctrl=stats&tab=hits&blog='.$blog;
	?>

	<tr class="total">
		<td class="firstcol"><?php echo T_('Total') ?></td>
		<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text_total.'&hit_type=rss">'.$hits_total['rss'].'</a>' : $hits_total['rss']; ?></td>
		<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text_total.'&hit_type=standard&agent_type=robot">'.$hits_total['standard_robot'].'</a>' : $hits_total['standard_robot']; ?></td>
		<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text_total.'&hit_type=standard&agent_type=browser">'.$hits_total['standard_browser'].'</a>' : $hits_total['standard_browser']; ?></td>
		<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text_total.'&hit_type=ajax">'.$hits_total['ajax'].'</a>' : $hits_total['ajax']; ?></td>
		<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text_total.'&hit_type=service">'.$hits_total['service'].'</a>' : $hits_total['service']; ?></td>
		<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text_total.'&hit_type=admin">'.$hits_total['admin'].'</a>' : $hits_total['admin']; ?></td>
		<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text_total.'&hit_type=api">'.$hits_total['api'].'</a>' : $hits_total['api']; ?></td>
		<td class="right"><?php echo $is_live_data ? '<a href="'.$link_text_total.'&agent_type=unknown">'.$hits_total['unknown'].'</a>' : $hits_total['unknown']; ?></td>
		<td class="lastcol right"><?php echo $is_live_data ? '<a href="'.$link_text_total.'">'.array_sum( $hits_total ).'</a>' : array_sum( $hits_total ); ?></td>
	</tr>

	</table>

	<?php
}
?>