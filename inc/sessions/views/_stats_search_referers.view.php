<?php
/**
 * This file implements the UI view for the browser hits summary.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url, $AdminUI, $referer_type_color, $Hit, $Settings, $localtimenow;

// All diagarm and table columns for current page:
$diagram_columns = array(
	'search'  => array( 'title' => T_('Referring searches'), 'link_data' => array( 'search' ) ),
	'referer' => array( 'title' => T_('Referers'),           'link_data' => array( 'referer' ) ),
);
foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
{
	$diagram_columns[ $diagram_column_key ]['color'] = $referer_type_color[ $diagram_column_key ];
}

echo '<h2 class="page-title">'.T_('Hits from search and referers - Summary').get_manual_link( 'search-referers-hits-summary' ).'</h2>';

// Display panel with buttons to control a view of hits summary pages:
display_hits_summary_panel( $diagram_columns );

// Filter diagram columns by seleated types:
$diagram_columns = get_filtered_hits_diagram_columns( 'search_referers', $diagram_columns );

// Check if it is a mode to display a live data:
$is_live_mode = ( get_hits_summary_mode() == 'live' );

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
$SQL = new SQL( 'Get hits summary from web browsers ('.( $is_live_mode ? 'Live data' : 'Aggregate data' ).')' );
$sessions_SQL = new SQL( 'Get sessions summary from web browsers ('.( $is_live_mode ? 'Live data' : 'Aggregate data' ).')' );
if( $is_live_mode )
{	// Get the live data:
	$SQL->SELECT( 'SQL_NO_CACHE COUNT( * ) AS hits, hit_referer_type AS referer_type, hit_type,
		GROUP_CONCAT( DISTINCT hit_sess_ID SEPARATOR "," ) AS sessions,
		EXTRACT( YEAR FROM hit_datetime ) AS year,
		EXTRACT( MONTH FROM hit_datetime ) AS month,
		EXTRACT( DAY FROM hit_datetime ) AS day' );
	$SQL->FROM( 'T_hitlog' );
	$SQL->WHERE( 'hit_agent_type = "browser"' );

	$sessions_SQL->SELECT( 'SQL_NO_CACHE DATE( hit_datetime ) AS hit_date, COUNT( DISTINCT hit_sess_ID )' );
	$sessions_SQL->FROM( 'T_hitlog' );
	$sessions_SQL->WHERE( 'hit_agent_type = "browser"' );

	if( $blog > 0 )
	{	// Filter by collection:
		$SQL->WHERE_and( 'hit_coll_ID = '.$DB->quote( $blog ) );
		$sessions_SQL->WHERE_and( 'hit_coll_ID = '.$DB->quote( $blog ) );
	}

	$hits_start_date = NULL;
	$hits_end_date = date( 'Y-m-d' );
}
else
{	// Get the aggregated data:
	$SQL->SELECT( 'SUM( hagg_count ) AS hits, hagg_referer_type AS referer_type, hagg_type AS hit_type,
		"" AS sessions,
		EXTRACT( YEAR FROM hagg_date ) AS year,
		EXTRACT( MONTH FROM hagg_date ) AS month,
		EXTRACT( DAY FROM hagg_date ) AS day' );
	$SQL->FROM( 'T_hits__aggregate' );
	$SQL->WHERE( 'hagg_agent_type = "browser"' );
	// Filter by date:
	list( $hits_start_date, $hits_end_date ) = get_filter_aggregated_hits_dates();
	$SQL->WHERE_and( 'hagg_date >= '.$DB->quote( $hits_start_date ) );
	$SQL->WHERE_and( 'hagg_date <= '.$DB->quote( $hits_end_date ) );

	$sessions_SQL->SELECT( 'hags_date AS hit_date, hags_count_browser' );
	$sessions_SQL->FROM( 'T_hits__aggregate_sessions' );

	if( $blog > 0 )
	{	// Filter by collection:
		$SQL->WHERE_and( 'hagg_coll_ID = '.$DB->quote( $blog ) );
		$sessions_SQL->WHERE( 'hags_coll_ID = '.$DB->quote( $blog ) );
	}
	else
	{	// Get ALL aggregated sessions:
		$sessions_SQL->WHERE( 'hags_coll_ID = 0' );
	}
	// Filter by date:
	$sessions_SQL->WHERE_and( 'hags_date >= '.$DB->quote( $hits_start_date ) );
	$sessions_SQL->WHERE_and( 'hags_date <= '.$DB->quote( $hits_end_date ) );
}
$SQL->GROUP_BY( 'year, month, day, referer_type, hit_type' );
$SQL->ORDER_BY( 'year DESC, month DESC, day DESC, referer_type, hit_type' );
$sessions_SQL->GROUP_BY( 'hit_date' );
$sessions_SQL->ORDER_BY( 'hit_date DESC' );

$res_hits = $DB->get_results( $SQL, ARRAY_A );
$sessions = $DB->get_assoc( $sessions_SQL );

/*
 * Chart
 */
if( count( $res_hits ) )
{
	// Find the dates without hits and fill them with 0 to display on graph and table:
	$res_hits = fill_empty_hit_days( $res_hits, $hits_start_date, $hits_end_date );

	$last_date = 0;

	// Initialize the data to open an url by click on bar item:
	$chart['link_data'] = array();
	$chart['link_data']['url'] = $admin_url.'?ctrl=stats&tab=hits&datestartinput=$date$&datestopinput=$date$&blog='.$blog.'&agent_type=browser&referer_type=$param1$';
	$chart['link_data']['params'] = array();

	$col_mapping = array();
	$col_num = 1;
	$chart[ 'chart_data' ][ 0 ] = array();
	foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
	{
		$chart[ 'chart_data' ][ $col_num ] = array();
		if( $diagram_column_data['link_data'] !== false )
		{
			$chart['link_data']['params'][] = $diagram_column_data['link_data'];
		}
		$col_mapping[ $diagram_column_key ] = $col_num++;
	}

	$chart['dates'] = array();

	if( isset( $diagram_columns['session'] ) )
	{	// Draw last data as line only for Sessions:
		$chart['draw_last_line'] = true;
	}

	$count = 0;
	foreach( $res_hits as $row_stats )
	{
		$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );
		if( $last_date != $this_date )
		{ // We just hit a new day, let's display the previous one:
			$last_date = $this_date;	// that'll be the next one
			$count ++;
			array_unshift( $chart[ 'chart_data' ][ 0 ], date( 'D '.locale_datefmt(), $last_date ) );
			$col_num = 1;
			foreach( $diagram_columns as $diagram_column_data )
			{
				array_unshift( $chart[ 'chart_data' ][ $col_num++ ], 0 );
			}

			array_unshift( $chart['dates'], $last_date );
		}

		if( isset( $col_mapping[ $row_stats['referer_type'] ] ) )
		{
			$chart['chart_data'][ $col_mapping[ $row_stats['referer_type'] ] ][0] += $row_stats['hits'];
		}

		if( isset( $col_mapping['session'] ) )
		{	// Store a count of sessions:
			$chart['chart_data'][ $col_mapping['session'] ][0] = ( isset( $sessions[ date( 'Y-m-d', $this_date ) ] ) ? $sessions[ date( 'Y-m-d', $this_date ) ] : 0 );
		}
	}

	// Initialize titles and colors for diagram columns:
	array_unshift( $chart[ 'chart_data' ][ 0 ], '' );
	$col_num = 1;
	$chart['series_color'] = array();
	foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
	{
		$chart['series_color'][ $col_num ] = $diagram_column_data['color'];
		array_unshift( $chart[ 'chart_data' ][ $col_num++ ], $diagram_column_data['title'] );
	}

	$chart[ 'canvas_bg' ] = array( 'width'  => '100%', 'height' => 355 );

	echo '<div class="center">';
	load_funcs('_ext/_canvascharts.php');
	CanvasBarsChart( $chart );
	echo '</div>';


	/*
	 * Table:
	 */
	$hits_clear = array_fill_keys( array_keys( $diagram_columns ), 0 );
	$hits = $hits_clear;
	$hits_total = $hits_clear;

	$last_date = 0;

	?>

	<table class="grouped table table-striped table-bordered table-hover table-condensed" cellspacing="0">
		<tr>
			<th class="firstcol shrinkwrap"><?php echo T_('Date') ?></th>
			<?php
			if( isset( $diagram_columns['session'] ) )
			{
				echo '<th style="background-color:#'.$diagram_columns['session']['color'].'">'.$diagram_columns['session']['title'].'</th>';
			}
			foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
			{
				if( $diagram_column_key != 'session' )
				{
					echo '<th style="background-color:#'.$diagram_column_data['color'].'">'.$diagram_column_data['title'].'</th>';
				}
			}
			?>
			<th class="lastcol"><?php echo T_('Total') ?></th>
		</tr>
		<?php
		$count = 0;
		foreach( $res_hits as $row_stats )
		{
			$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );

			if( $last_date == 0 ) $last_date = $this_date;	// that'll be the first one

			$link_text = $admin_url.'?ctrl=stats&amp;tab=hits&amp;datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&amp;datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).'&amp;blog='.$blog.'&amp;agent_type=browser';
			$link_text_total_day = $admin_url.'?ctrl=stats&amp;tab=hits&amp;datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&amp;datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).'&amp;blog='.$blog.'&amp;agent_type=browser';

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
					<td class="firstcol right nowrap"><?php
						echo date( 'D '.locale_datefmt(), $last_date );
						if( $is_live_mode && $current_User->check_perm( 'stats', 'edit' ) )
						{	// Display a link to prune hits only for live data and if current user has a permission:
							echo action_icon( T_('Prune hits for this date!'), 'delete', url_add_param( $admin_url, 'ctrl=stats&amp;action=prune&amp;date='.$last_date.'&amp;show=summary&amp;blog='.$blog.'&amp;'.url_crumb('stats') ) );
						}
					?></td><?php
					if( isset( $diagram_columns['session'] ) )
					{
						echo '<td class="right">'.( isset( $sessions[ date( 'Y-m-d', $last_date ) ] ) ? $sessions[ date( 'Y-m-d', $last_date ) ] : 0 ).'</td>';
					}
					foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
					{
						if( $diagram_column_key != 'session' )
						{
							echo '<td class="right">';
							if( $is_live_data )
							{
								$diagram_col_url_params = empty( $diagram_column_data['link_data'][0] ) ? '' : '&amp;referer_type='.$diagram_column_data['link_data'][0];
								echo '<a href="'.$link_text.$diagram_col_url_params.'">'.$hits[ $diagram_column_key ].'</a>';
							}
							else
							{
								echo $hits[ $diagram_column_key ];
							}
							echo '</td>';
						}
					}
					?>
					<td class="lastcol right"><?php echo $is_live_data ? '<a href="'.$link_text_total_day.'">'.array_sum( $hits ).'</a>' : array_sum( $hits ); ?></td>
				</tr>
				<?php
					$hits = $hits_clear;
					$last_date = $this_date;	// that'll be the next one
					$count++;
			}

			// Increment hitcounter:
			if( isset( $hits[ $row_stats['referer_type'] ] ) )
			{
				$hits[ $row_stats['referer_type'] ] += $row_stats['hits'];
				$hits_total[ $row_stats['referer_type'] ] += $row_stats['hits'];
			}
		}

		if( $last_date != 0 )
		{ // We had a day pending:
			$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );

			$link_text = $admin_url.'?ctrl=stats&amp;tab=hits&amp;datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&amp;datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).'&amp;blog='.$blog.'&amp;agent_type=browser';
			$link_text_total_day = $admin_url.'?ctrl=stats&amp;tab=hits&amp;datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&amp;datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).'&amp;blog='.$blog.'&amp;agent_type=browser';

			// Check if current data are live and not aggregated:
			$is_live_data = true;
			if( ! $is_live_mode )
			{	// Check only for "Aggregate data":
				$time_prune_before = mktime( 0, 0, 0 ) - ( $Settings->get( 'auto_prune_stats' ) * 86400 );
				$is_live_data = $last_date >= $time_prune_before;
			}
			?>
				<tr class="<?php echo ( $count%2 == 1 ) ? 'odd' : 'even'; ?>">
				<td class="firstcol right nowrap"><?php
					echo date( 'D '.locale_datefmt(), $this_date );
					if( $is_live_mode && $current_User->check_perm( 'stats', 'edit' ) )
					{	// Display a link to prune hits only for live data and if current user has a permission:
						echo action_icon( T_('Prune hits for this date!'), 'delete', url_add_param( $admin_url, 'ctrl=stats&amp;action=prune&amp;date='.$last_date.'&amp;show=summary&amp;blog='.$blog.'&amp;'.url_crumb('stats') ) );
					}
				?></td><?php
				if( isset( $diagram_columns['session'] ) )
				{
					echo '<td class="right">'.( isset( $sessions[ date( 'Y-m-d', $last_date ) ] ) ? $sessions[ date( 'Y-m-d', $last_date ) ] : 0 ).'</td>';
				}
				foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
				{
					if( $diagram_column_key != 'session' )
					{
						echo '<td class="right">';
						if( $is_live_data )
						{
							$diagram_col_url_params = empty( $diagram_column_data['link_data'][0] ) ? '' : '&amp;referer_type='.$diagram_column_data['link_data'][0];
							echo '<a href="'.$link_text.$diagram_col_url_params.'">'.$hits[ $diagram_column_key ].'</a>';
						}
						else
						{
							echo $hits[ $diagram_column_key ];
						}
						echo '</td>';
					}
				}
				?>
				<td class="lastcol right"><?php echo $is_live_data ? '<a href="'.$link_text_total_day.'">'.array_sum( $hits ).'</a>' : array_sum( $hits ); ?></td>
			</tr>
			<?php
		}

		// Total numbers:

		$link_text_total = $admin_url.'?ctrl=stats&tab=hits&blog='.$blog.'&agent_type=browser';
		?>

		<tr class="total">
			<td class="firstcol"><?php echo T_('Total') ?></td><?php
			if( isset( $diagram_columns['session'] ) )
			{
				echo '<td class="right">'.array_sum( $sessions ).'</td>';
			}
			foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
			{
				if( $diagram_column_key != 'session' )
				{
					echo '<td class="right">';
					if( $is_live_data )
					{
						$diagram_col_url_params = empty( $diagram_column_data['link_data'][0] ) ? '' : '&amp;referer_type='.$diagram_column_data['link_data'][0];
						echo '<a href="'.$link_text_total.$diagram_col_url_params.'">'.$hits_total[ $diagram_column_key ].'</a>';
					}
					else
					{
						echo $hits_total[ $diagram_column_key ];
					}
					echo '</td>';
				}
			}
			?>
			<td class="lastcol right"><?php echo $is_live_data ? '<a href="'.$link_text_total.'">'.array_sum( $hits_total ).'</a>' : array_sum( $hits_total ); ?></td>
		</tr>

	</table>

	<?php
}

?>