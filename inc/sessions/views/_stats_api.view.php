<?php
/**
 * This file implements the UI view for the API hits summary.
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

global $blog, $admin_url, $AdminUI, $referer_type_color, $hit_type_color, $Hit;

echo '<h2 class="page-title">'.T_('Hits from API - Summary').get_manual_link( 'api-hits-summary' ).'</h2>';

// Display buttons to toggle between type of hits summary data(Live or Aggregate):
display_hits_summary_toggler();

// Check if it is a mode to display a live data:
$is_live_mode = ( get_hits_summary_mode() == 'live' );

$SQL = new SQL( 'Get API hits summary ('.( $is_live_mode ? 'Live data' : 'Aggregate data' ).')' );
if( $is_live_mode )
{	// Get the live data:
	$SQL->SELECT( 'SQL_NO_CACHE COUNT( * ) AS hits, hit_referer_type AS referer_type,
		GROUP_CONCAT( DISTINCT hit_sess_ID SEPARATOR "," ) AS sessions,
		EXTRACT( YEAR FROM hit_datetime ) AS year,
		EXTRACT( MONTH FROM hit_datetime ) AS month,
		EXTRACT( DAY FROM hit_datetime ) AS day' );
	$SQL->FROM( 'T_hitlog' );
	$SQL->WHERE( 'hit_type = "api"' );
	if( $blog > 0 )
	{	// Filter by collection:
		$SQL->WHERE_and( 'hit_coll_ID = '.$DB->quote( $blog ) );
	}
}
else
{	// Get the aggregated data:
	$SQL->SELECT( 'SUM( hagg_count ) AS hits, hagg_referer_type AS referer_type,
		"" AS sessions,
		EXTRACT( YEAR FROM hagg_date ) AS year,
		EXTRACT( MONTH FROM hagg_date ) AS month,
		EXTRACT( DAY FROM hagg_date ) AS day' );
	$SQL->FROM( 'T_hits__aggregate' );
	$SQL->WHERE( 'hagg_type = "api"' );
	if( $blog > 0 )
	{	// Filter by collection:
		$SQL->WHERE_and( 'hagg_coll_ID = '.$DB->quote( $blog ) );
	}
}
$SQL->GROUP_BY( 'year, month, day, referer_type' );
$SQL->ORDER_BY( 'year DESC, month DESC, day DESC, referer_type' );
$res_hits = $DB->get_results( $SQL->get(), ARRAY_A, $SQL->title );

/*
 * Chart
 */
if( count( $res_hits ) )
{
	$last_date = 0;

	$col_mapping = array(
			'search'  => 1,
			'referer' => 2,
			'direct'  => 3,
			'self'    => 4,
			'special' => 5,
			'spam'    => 6,
			'session' => 7,
		);

	$chart[ 'chart_data' ][ 0 ] = array();
	$chart[ 'chart_data' ][ 1 ] = array();
	$chart[ 'chart_data' ][ 2 ] = array();
	$chart[ 'chart_data' ][ 3 ] = array();
	$chart[ 'chart_data' ][ 4 ] = array();
	$chart[ 'chart_data' ][ 5 ] = array();
	$chart[ 'chart_data' ][ 6 ] = array();
	$chart[ 'chart_data' ][ 7 ] = array();

	$chart['dates'] = array();

	// Draw last data as line
	$chart['draw_last_line'] = true;

	if( $is_live_mode )
	{	// Initialize the data to open an url by click on bar item:
		$chart['link_data'] = array();
		$chart['link_data']['url'] = $admin_url.'?ctrl=stats&tab=hits&datestartinput=$date$&datestopinput=$date$&blog='.$blog.'&referer_type=$param1$&hit_type=api';
		$chart['link_data']['params'] = array(
				array( 'search' ),
				array( 'referer' ),
				array( 'direct' ),
				array( 'self' ),
				array( 'special' ),
				array( 'spam' ),
			);
	}

	$count = 0;
	$sessions = array();
	foreach( $res_hits as $row_stats )
	{
		$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );
		if( $last_date != $this_date )
		{	// We just hit a new day, let's display the previous one:
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

			array_unshift( $chart['dates'], $last_date );
		}

		$col = $col_mapping[ $row_stats['referer_type'] ];
		$chart['chart_data'][ $col ][0] += $row_stats['hits'];


		if( ! isset( $sessions[ $this_date ] ) )
		{	// Initialize array to count sessions for each date:
			$sessions[ $this_date ] = array();
		}
		$row_sessions = explode( ',', $row_stats['sessions'] );
		foreach( $row_sessions as $row_session )
		{
			if( ! in_array( $row_session, $sessions[ $this_date ] ) )
			{	// Count only unique session IDs:
				$sessions[ $this_date ][] = $row_session;
			}
		}

		// Store a count of sessions:
		$col = $col_mapping['session'];
		$chart['chart_data'][ $col ][0] = count( $sessions[ $this_date ] );
	}

	array_unshift( $chart[ 'chart_data' ][ 0 ], '' );
	array_unshift( $chart[ 'chart_data' ][ 1 ], T_('Referring searches') );
	array_unshift( $chart[ 'chart_data' ][ 2 ], T_('Referers') );
	array_unshift( $chart[ 'chart_data' ][ 3 ], T_('Direct accesses') );	// Translations need to be UTF-8
	array_unshift( $chart[ 'chart_data' ][ 4 ], T_('Self referred') );
	array_unshift( $chart[ 'chart_data' ][ 5 ], T_('Special referrers') );
	array_unshift( $chart[ 'chart_data' ][ 6 ], T_('Referer spam') );
	array_unshift( $chart[ 'chart_data' ][ 7 ], T_('Sessions') );

	$chart[ 'series_color' ] = array(
			$referer_type_color['search'],
			$referer_type_color['referer'],
			$referer_type_color['direct'],
			$referer_type_color['self'],
			$referer_type_color['special'],
			$referer_type_color['spam'],
			$referer_type_color['session'],
		);

	$chart[ 'canvas_bg' ] = array( 'width'  => 780, 'height' => 355 );

	echo '<div class="center">';
	load_funcs('_ext/_canvascharts.php');
	CanvasBarsChart( $chart );
	echo '</div>';


	/*
	 * Table:
	 */
	$hits = array(
		'direct'  => 0,
		'referer' => 0,
		'search'  => 0,
		'self'    => 0,
		'special' => 0,
		'spam'    => 0,
	);
	$hits_total = $hits;

	$last_date = 0;

	?>

	<table class="grouped table table-striped table-bordered table-hover table-condensed" cellspacing="0">
		<tr>
			<th class="firstcol"><?php echo T_('Date') ?></th>
			<th style="background-color: #<?php echo $referer_type_color['session'] ?>"><?php echo T_('Sessions') ?></th>
			<th style="background-color: #<?php echo $referer_type_color['search'] ?>"><?php echo T_('Referring searches') ?></th>
			<th style="background-color: #<?php echo $referer_type_color['referer'] ?>"><?php echo T_('Referers') ?></th>
			<th style="background-color: #<?php echo $referer_type_color['direct'] ?>"><?php echo T_('Direct accesses') ?></th>
			<th style="background-color: #<?php echo $referer_type_color['self'] ?>"><?php echo T_('Self referred') ?></th>
			<th style="background-color: #<?php echo $referer_type_color['special'] ?>"><?php echo T_('Special referrers') ?></th>
			<th style="background-color: #<?php echo $referer_type_color['spam'] ?>"><?php echo T_('Referer spam') ?></th>
			<th class="lastcol"><?php echo T_('Total') ?></th>
		</tr>
		<?php
		$count = 0;
		foreach( $res_hits as $row_stats )
		{
			$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );

			if( $last_date == 0 ) $last_date = $this_date;	// that'll be the first one

			$link_text = $admin_url.'?ctrl=stats&tab=hits&datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).'&blog='.$blog.'&hit_type=api';
			$link_text_total_day = $admin_url.'?ctrl=stats&tab=hits&datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).'&blog='.$blog.'&hit_type=api';

			if( $last_date != $this_date )
			{	// We just hit a new day, let's display the previous one:
				?>
				<tr class="<?php echo ( $count%2 == 1 ) ? 'odd' : 'even'; ?>">
					<td class="firstcol right"><?php
						echo date( 'D '.locale_datefmt(), $last_date );
						if( $is_live_mode && $current_User->check_perm( 'stats', 'edit' ) )
						{	// Display a link to prune hits only for live data and if current user has a permission:
							echo action_icon( T_('Prune hits for this date!'), 'delete', url_add_param( $admin_url, 'ctrl=stats&amp;action=prune&amp;date='.$last_date.'&amp;show=summary&amp;blog='.$blog.'&amp;'.url_crumb('stats') ) );
						}
					?></td>
					<td class="right"><?php echo count( $sessions[ $last_date ] ); ?></td>
					<td class="right"><?php echo $is_live_mode ? '<a href="'.$link_text.'&referer_type=search">'.$hits['search'].'</a>' : $hits['search']; ?></td>
					<td class="right"><?php echo $is_live_mode ? '<a href="'.$link_text.'&referer_type=referer">'.$hits['referer'].'</a>' : $hits['referer']; ?></td>
					<td class="right"><?php echo $is_live_mode ? '<a href="'.$link_text.'&referer_type=direct">'.$hits['direct'].'</a>' : $hits['direct']; ?></td>
					<td class="right"><?php echo $is_live_mode ? '<a href="'.$link_text.'&referer_type=self">'.$hits['self'].'</a>' : $hits['self']; ?></td>
					<td class="right"><?php echo $is_live_mode ? '<a href="'.$link_text.'&referer_type=special">'.$hits['special'].'</a>' : $hits['special']; ?></td>
					<td class="right"><?php echo $is_live_mode ? '<a href="'.$link_text.'&referer_type=spam">'.$hits['spam'].'</a>' : $hits['spam']; ?></td>
					<td class="lastcol right"><?php echo $is_live_mode ? '<a href="'.$link_text_total_day.'">'.array_sum( $hits ).'</a>' : array_sum( $hits ); ?></td>
				</tr>
				<?php
					$hits = array(
						'direct'  => 0,
						'referer' => 0,
						'search'  => 0,
						'self'    => 0,
						'special' => 0,
						'spam'    => 0,
					);
					$last_date = $this_date;	// that'll be the next one
					$count ++;
			}

			// Increment hitcounter:

			$hits[$row_stats['referer_type']] += $row_stats['hits'];
			$hits_total[$row_stats['referer_type']] += $row_stats['hits'];
		}

		if( $last_date != 0 )
		{	// We had a day pending:
			$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );

			$link_text = $admin_url.'?ctrl=stats&tab=hits&datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).'&blog='.$blog.'&hit_type=api';
			$link_text_total_day = $admin_url.'?ctrl=stats&tab=hits&datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).'&blog='.$blog.'&hit_type=api';
			?>
				<tr class="<?php echo ( $count%2 == 1 ) ? 'odd' : 'even'; ?>">
				<td class="firstcol right"><?php
					echo date( 'D '.locale_datefmt(), $this_date );
					if( $is_live_mode && $current_User->check_perm( 'stats', 'edit' ) )
					{	// Display a link to prune hits only for live data and if current user has a permission:
						echo action_icon( T_('Prune hits for this date!'), 'delete', url_add_param( $admin_url, 'ctrl=stats&amp;action=prune&amp;date='.$last_date.'&amp;show=summary&amp;blog='.$blog.'&amp;'.url_crumb('stats') ) );
					}
				?></td>
				<td class="right"><?php echo count( $sessions[ $last_date ] ); ?></td>
				<td class="right"><?php echo $is_live_mode ? '<a href="'.$link_text.'&referer_type=search">'.$hits['search'].'</a>' : $hits['search']; ?></td>
				<td class="right"><?php echo $is_live_mode ? '<a href="'.$link_text.'&referer_type=referer">'.$hits['referer'].'</a>' : $hits['referer']; ?></td>
				<td class="right"><?php echo $is_live_mode ? '<a href="'.$link_text.'&referer_type=direct">'.$hits['direct'].'</a>' : $hits['direct']; ?></td>
				<td class="right"><?php echo $is_live_mode ? '<a href="'.$link_text.'&referer_type=self">'.$hits['self'].'</a>' : $hits['self']; ?></td>
				<td class="right"><?php echo $is_live_mode ? '<a href="'.$link_text.'&referer_type=special">'.$hits['special'].'</a>' : $hits['special']; ?></td>
				<td class="right"><?php echo $is_live_mode ? '<a href="'.$link_text.'&referer_type=spam">'.$hits['spam'].'</a>' : $hits['spam']; ?></td>
				<td class="lastcol right"><?php echo $is_live_mode ? '<a href="'.$link_text_total_day.'">'.array_sum( $hits ).'</a>' : array_sum( $hits ); ?></td>
			</tr>
			<?php
		}

		// Total numbers:

		$link_text_total = $admin_url.'?ctrl=stats&tab=hits&blog='.$blog.'&hit_type=api';

		// Count total unique sessions for all dates:
		$total_sessions = array();
		foreach( $sessions as $date_sessions )
		{
			foreach( $date_sessions as $session_ID )
			{
				if( ! in_array( $session_ID, $total_sessions ) )
				{	// Count only unique session IDs:
					$total_sessions[] = $session_ID;
				}
			}
		}
		?>

		<tr class="total">
			<td class="firstcol"><?php echo T_('Total') ?></td>
			<td class="right"><?php echo count( $total_sessions ); ?></td>
			<td class="right"><?php echo $is_live_mode ? '<a href="'.$link_text_total.'&referer_type=search">'.$hits_total['search'].'</a>' : $hits_total['search']; ?></td>
			<td class="right"><?php echo $is_live_mode ? '<a href="'.$link_text_total.'&referer_type=referer">'.$hits_total['referer'].'</a>' : $hits_total['referer']; ?></td>
			<td class="right"><?php echo $is_live_mode ? '<a href="'.$link_text_total.'&referer_type=direct">'.$hits_total['direct'].'</a>' : $hits_total['direct']; ?></td>
			<td class="right"><?php echo $is_live_mode ? '<a href="'.$link_text_total.'&referer_type=self">'.$hits_total['self'].'</a>' : $hits_total['self']; ?></td>
			<td class="right"><?php echo $is_live_mode ? '<a href="'.$link_text_total.'&referer_type=special">'.$hits_total['special'].'</a>' : $hits_total['special']; ?></td>
			<td class="right"><?php echo $is_live_mode ? '<a href="'.$link_text_total.'&referer_type=spam">'.$hits_total['spam'].'</a>' : $hits_total['spam']; ?></td>
			<td class="lastcol right"><?php echo $is_live_mode ? '<a href="'.$link_text_total.'">'.array_sum( $hits_total ).'</a>' : array_sum( $hits_total ); ?></td>
		</tr>

	</table>

	<?php
}

?>