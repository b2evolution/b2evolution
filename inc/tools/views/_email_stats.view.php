<?php
/**
 * This file implements the UI view for the email statistics.
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

$email_status_colors = array(
		'opened'        => '5CB85C',
		'ok'            => '000000',
		'blocked'       => 'FF0000',
		'error'         => 'FF6600',
		'ready_to_send' => 'FFF000',
		'simulated'     => 'BBBBBB',
	);

echo '<h2 class="page-title">'.T_('Email statistics').get_manual_link('email_summary').'</h2>';

$start_date = date( 'Y-m-d', mktime( 0, 0, 0, date( 'm' ), date( 'd' ) - 29 ) ); // Date of 30 days ago
$end_date = date( 'Y-m-d', mktime( 0, 0, 0, date( 'm' ), date( 'd' ) ) ); // Today

$SQL = new SQL( 'Get email statistics' );

$SQL->SELECT( 'EXTRACT( YEAR FROM emlog_timestamp ) AS year,
		EXTRACT( MONTH FROM emlog_timestamp ) AS month,
		EXTRACT( DAY FROM emlog_timestamp ) AS day,
		CASE
			WHEN emlog_result = "ok" AND ( emlog_last_open_ts IS NOT NULL OR emlog_last_click_ts IS NOT NULL ) THEN "opened"
			WHEN emlog_result = "simulated" AND ( emlog_last_open_ts IS NOT NULL OR emlog_last_click_ts IS NOT NULL ) THEN "opened"
			ELSE emlog_result END AS email_status,
		COUNT( * ) AS email_count' );
$SQL->FROM( 'T_email__log' );
$SQL->WHERE( 'DATE( emlog_timestamp ) >='.$DB->quote( $start_date ) );
$SQL->WHERE_and( 'DATE( emlog_timestamp ) <='.$DB->quote( $end_date ) );
$SQL->GROUP_BY( 'year, month, day, email_status' );
$SQL->ORDER_BY( 'year DESC, month DESC, day DESC, email_status' );

$res_counts = $DB->get_results( $SQL, ARRAY_A );


/**
 * Chart
 */
if( $res_counts )
{
	// Find the dates without emails and fill them with 0 to display on graph and table:
	$res_counts = fill_empty_days( $res_counts, array( 'email_count' => 0 ), $start_date, $end_date );

	$last_date = 0;

	$col_mapping = array(
			'opened'        => 1,
			'ok'            => 2,
			'blocked'       => 3,
			'error'         => 4,
			'ready_to_send' => 5,
			'simulated'     => 6,
		);

	$chart['chart_data'][0] = array();
	$chart['chart_data'][1] = array();
	$chart['chart_data'][2] = array();
	$chart['chart_data'][3] = array();
	$chart['chart_data'][4] = array();
	$chart['chart_data'][5] = array();
	$chart['chart_data'][6] = array();

	$chart['dates'] = array();

	$count = 0;
	foreach( $res_counts as $row_stats )
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
			array_unshift( $chart['dates'], $last_date );
		}

		$col = $col_mapping[$row_stats['email_status']];
		$chart['chart_data'][$col][0] += $row_stats['email_count'];
	}

	array_unshift( $chart[ 'chart_data' ][ 0 ], '' );
	array_unshift( $chart[ 'chart_data' ][ 1 ], T_('Opened') );
	array_unshift( $chart[ 'chart_data' ][ 2 ], T_('Sent') );
	array_unshift( $chart[ 'chart_data' ][ 3 ], T_('Blocked') );
	array_unshift( $chart[ 'chart_data' ][ 4 ], T_('Error') );
	array_unshift( $chart[ 'chart_data' ][ 5 ], T_('Ready to send') );
	array_unshift( $chart[ 'chart_data' ][ 6 ], T_('Simulated') );





	$chart[ 'series_color' ] = array (
		$email_status_colors['opened'],
		$email_status_colors['ok'],
		$email_status_colors['blocked'],
		$email_status_colors['error'],
		$email_status_colors['ready_to_send'],
		$email_status_colors['simulated'],
	);

	$chart[ 'canvas_bg' ] = array( 'width'  => '100%', 'height' => 355 );

	echo '<div class="center">';
	load_funcs('_ext/_canvascharts.php');
	CanvasBarsChart( $chart );
	echo '</div>';
}

/**
 * Table
 */
$email_count = array(
		'ready_to_send' => 0,
		'ok'            => 0,
		'simulated'     => 0,
		'opened'        => 0,
		'error'         => 0,
		'blocked'       => 0,
	);

$total_count = $email_count;

$last_date = 0;

echo '<table class="grouped table table-striped table-bordered table-hover table-condensed" cellspacing="0">';
echo '<tr>';
echo '<th class="firstcol">'.T_('Date').'</th>';
echo '<th style="background-color: #'.$email_status_colors['opened'].'">'.T_('Opened').'</th>';
echo '<th style="color: #FFFFFF; background-color: #'.$email_status_colors['ok'].'">'.T_('Sent').'</th>';
echo '<th style="background-color: #'.$email_status_colors['blocked'].'">'.T_('Blocked').'</th>';
echo '<th style="background-color: #'.$email_status_colors['error'].'">'.T_('Error').'</th>';
echo '<th style="background-color: #'.$email_status_colors['ready_to_send'].'">'.T_('Ready to send').'</th>';
echo '<th style="background-color: #'.$email_status_colors['simulated'].'">'.T_('Simulated').'</th>';
echo '<th class="lastcol">'.T_('Total').'</th>';
echo '</tr>';

$count = 0;
foreach( $res_counts as $row_stats )
{
	$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );
	if( $last_date == 0 ) $last_date = $this_date;	// that'll be the first one

	if( $last_date != $this_date )
	{	// We just hit a new day, let's display the previous one:
		?>
		<tr class="<?php echo ( $count%2 == 1 ) ? 'odd' : 'even'; ?>">
			<td class="firstcol right"><?php
				echo date( 'D '.locale_datefmt(), $last_date );
			?></td>
			<td class="right"><?php echo $email_count['opened']; ?></td>
			<td class="right"><?php echo $email_count['ok']; ?></td>
			<td class="right"><?php echo $email_count['blocked']; ?></td>
			<td class="right"><?php echo $email_count['error']; ?></td>
			<td class="right"><?php echo $email_count['ready_to_send']; ?></td>
			<td class="right"><?php echo $email_count['simulated']; ?></td>
			<td class="lastcol right"><?php echo array_sum( $email_count ); ?></td>
		</tr>
		<?php
			$email_count = array(
					'ready_to_send' => 0,
					'ok'            => 0,
					'simulated'     => 0,
					'opened'        => 0,
					'error'         => 0,
					'blocked'       => 0,
				);
			$last_date = $this_date;	// that'll be the next one
			$count ++;
	}

	// Increment hitcounter:
	$email_count[$row_stats['email_status']] += $row_stats['email_count'];
	$total_count[$row_stats['email_status']] += $row_stats['email_count'];
}

if( $last_date != 0 )
{ // We had a day pending:
	$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );

	?>
		<tr class="<?php echo ( $count%2 == 1 ) ? 'odd' : 'even'; ?>">
			<td class="firstcol right"><?php
			echo date( 'D '.locale_datefmt(), $this_date );
			?></td>
			<td class="right"><?php echo $email_count['opened']; ?></td>
			<td class="right"><?php echo $email_count['ok']; ?></td>
			<td class="right"><?php echo $email_count['blocked']; ?></td>
			<td class="right"><?php echo $email_count['error']; ?></td>
			<td class="right"><?php echo $email_count['ready_to_send']; ?></td>
			<td class="right"><?php echo $email_count['simulated']; ?></td>
			<td class="lastcol right"><?php echo array_sum( $email_count ); ?></td>
		</tr>
	<?php
}

// Total numbers:
?>

<tr class="total">
	<td class="firstcol"><?php echo T_('Total') ?></td>
	<td class="right"><?php echo $total_count['opened']; ?></td>
	<td class="right"><?php echo $total_count['ok']; ?></td>
	<td class="right"><?php echo $total_count['blocked']; ?></td>
	<td class="right"><?php echo $total_count['error']; ?></td>
	<td class="right"><?php echo $total_count['ready_to_send']; ?></td>
	<td class="right"><?php echo $total_count['simulated']; ?></td>
	<td class="lastcol right"><?php echo array_sum( $total_count ); ?></td>
</tr>

</table>