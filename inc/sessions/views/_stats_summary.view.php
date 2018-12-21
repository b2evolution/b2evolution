<?php
/**
 * This file implements the UI view for the general hit summary.
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

global $blog, $admin_url, $AdminUI, $hit_type_color, $Hit, $Settings, $localtimenow;

// All diagarm and table columns for current page:
$diagram_columns = array(
	'rss'              => array( 'title' => T_('XML (RSS/Atom)'),    'link_data' => array( 'rss',      '' ) ),
	'standard_robot'   => array( 'title' => T_('Standard/Robots'),   'link_data' => array( 'standard', 'robot' ) ),
	'standard_browser' => array( 'title' => T_('Standard/Browsers'), 'link_data' => array( 'standard', 'browser' ) ),
	'ajax'             => array( 'title' => T_('Ajax'),              'link_data' => array( 'ajax',     '' ) ),
	'service'          => array( 'title' => T_('Service'),           'link_data' => array( 'service',  '' ) ),
	'admin'            => array( 'title' => T_('Admin'),             'link_data' => array( 'admin',    '' ) ),
	'api'              => array( 'title' => T_('API'),               'link_data' => array( 'api',      '' ) ),
	'unknown'          => array( 'title' => T_('Other'),             'link_data' => array( '',         'unknown' ) ),
);
foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
{
	$diagram_columns[ $diagram_column_key ]['color'] = $hit_type_color[ $diagram_column_key ];
}

echo '<h2 class="page-title">'.T_('Global hits - Summary').get_manual_link('global_hits_summary').'</h2>';

// Display panel with buttons to control a view of hits summary pages:
display_hits_summary_panel( $diagram_columns );

// Filter diagram columns by seleated types:
$diagram_columns = get_filtered_hits_diagram_columns( 'global', $diagram_columns );

// Check if it is a mode to display a live data:
$hits_summary_mode = get_hits_summary_mode();
$is_live_mode = ( $hits_summary_mode == 'live' );

// Get hits data for chart and table:
$res_hits = get_hits_results_global( $hits_summary_mode );

if( count( $res_hits ) )
{
	// Display diagram for live or aggregated data:
	display_hits_diagram( 'global', $diagram_columns, $res_hits );

	if( ! $is_live_mode )
	{	// Display diagram to compare hits:
		display_hits_filter_form( 'compare', $diagram_columns );
		$prev_res_hits = get_hits_results_global( 'compare' );
		display_hits_diagram( 'global', $diagram_columns, $prev_res_hits, 'cmpcanvasbarschart' );
	}

	/*
	 * Table:
	 */

	$hits_clear = array_fill_keys( array_keys( $diagram_columns ), 0 );
	$hits = $hits_clear;
	$hits_total = $hits_clear;

	$last_date = 0;

	echo '<table class="grouped table table-striped table-bordered table-hover table-condensed" cellspacing="0">';
	echo '<tr>';
	echo '<th class="firstcol shrinkwrap">'.T_('Date').'</th>';
	foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
	{
		$diagram_col_url_params = empty( $diagram_column_data['link_data'][0] ) ? '' : '&amp;hit_type='.$diagram_column_data['link_data'][0];
		$diagram_col_url_params .= empty( $diagram_column_data['link_data'][1] ) ? '' : '&amp;agent_type='.$diagram_column_data['link_data'][1];
		echo '<th style="background-color:#'.$diagram_column_data['color'].'"><a href="'.$admin_url.'?ctrl=stats&amp;tab=hits'.$diagram_col_url_params.'&amp;blog='.$blog.'">'.$diagram_column_data['title'].'</a></th>';
	}
	echo '<th class="lastcol">'.T_('Total').'</th>';
	echo '</tr>';

	$count = 0;
	foreach( $res_hits as $row_stats )
	{
		$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );
		if( $last_date == 0 ) $last_date = $this_date;	// that'll be the first one

		$link_text = $admin_url.'?ctrl=stats&amp;tab=hits&amp;datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&amp;datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).'&amp;blog='.$blog;
		$link_text_total_day = $admin_url.'?ctrl=stats&amp;tab=hits&amp;datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&amp;datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).'&amp;blog='.$blog;


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
				foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
				{
					echo '<td class="right">';
					if( $is_live_data )
					{
						$diagram_col_url_params = empty( $diagram_column_data['link_data'][0] ) ? '' : '&amp;hit_type='.$diagram_column_data['link_data'][0];
						$diagram_col_url_params .= empty( $diagram_column_data['link_data'][1] ) ? '' : '&amp;agent_type='.$diagram_column_data['link_data'][1];
						echo '<a href="'.$link_text.$diagram_col_url_params.'">'.$hits[ $diagram_column_key ].'</a>';
					}
					else
					{
						echo $hits[ $diagram_column_key ];
					}
					echo '</td>';
				}
				?>
				<td class="lastcol right"><?php echo $is_live_data ? '<a href="'.$link_text_total_day.'">'.array_sum( $hits ).'</a>' : array_sum( $hits ); ?></td>
			</tr>
			<?php
				$hits = $hits_clear;
				$last_date = $this_date;	// that'll be the next one
				$count ++;
		}

		// Increment hitcounter:
		if( $row_stats['hit_agent_type'] == 'unknown' )
		{	// We have a column for unknown agent type:
			$hit_key = $row_stats['hit_agent_type'];
		}
		elseif( isset( $hits[$row_stats['hit_type'].'_'.$row_stats['hit_agent_type']] ) )
		{	// We have a column for this narrow type:
			$hit_key = $row_stats['hit_type'].'_'.$row_stats['hit_agent_type'];
		}
		elseif( isset( $hits[$row_stats['hit_type']] ) )
		{	// We have a column for this broad type:
			$hit_key = $row_stats['hit_type'];
		}
		else
		{
			$hit_key = NULL;
		}

		if( isset( $hits[ $hit_key ] ) )
		{
			$hits[ $hit_key ] += $row_stats['hits'];
			$hits_total[ $hit_key ] += $row_stats['hits'];
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
				<td class="firstcol right nowrap"><?php
				echo date( 'D '.locale_datefmt(), $this_date );
				if( $is_live_mode && $current_User->check_perm( 'stats', 'edit' ) )
				{	// Display a link to prune hits only for live data and if current user has a permission:
					echo action_icon( T_('Prune hits for this date!'), 'delete', url_add_param( $admin_url, 'ctrl=stats&amp;action=prune&amp;date='.$last_date.'&amp;show=summary&amp;blog='.$blog.'&amp;'.url_crumb('stats') ) );
				}
				?></td><?php
				foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
				{
					echo '<td class="right">';
					if( $is_live_data )
					{
						$diagram_col_url_params = empty( $diagram_column_data['link_data'][0] ) ? '' : '&amp;hit_type='.$diagram_column_data['link_data'][0];
						$diagram_col_url_params .= empty( $diagram_column_data['link_data'][1] ) ? '' : '&amp;agent_type='.$diagram_column_data['link_data'][1];
						echo '<a href="'.$link_text.$diagram_col_url_params.'">'.$hits[ $diagram_column_key ].'</a>';
					}
					else
					{
						echo $hits[ $diagram_column_key ];
					}
					echo '</td>';
				}
				?>
				<td class="lastcol right"><?php echo $is_live_data ? '<a href="'.$link_text_total_day.'">'.array_sum( $hits ).'</a>' : array_sum( $hits ); ?></td>
			</tr>
		<?php
	}

	// Total numbers:
	$link_text_total = $admin_url.'?ctrl=stats&tab=hits&blog='.$blog;
	?>

	<tr class="total">
		<td class="firstcol"><?php echo T_('Total') ?></td><?php
		foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
		{
			echo '<td class="right">';
			if( $is_live_data )
			{
				$diagram_col_url_params = empty( $diagram_column_data['link_data'][0] ) ? '' : '&amp;hit_type='.$diagram_column_data['link_data'][0];
				$diagram_col_url_params .= empty( $diagram_column_data['link_data'][1] ) ? '' : '&amp;agent_type='.$diagram_column_data['link_data'][1];
				echo '<a href="'.$link_text_total.$diagram_col_url_params.'">'.$hits_total[ $diagram_column_key ].'</a>';
			}
			else
			{
				echo $hits_total[ $diagram_column_key ];
			}
			echo '</td>';
		}
		?>
		<td class="lastcol right"><?php echo $is_live_data ? '<a href="'.$link_text_total.'">'.array_sum( $hits_total ).'</a>' : array_sum( $hits_total ); ?></td>
	</tr>

	</table>

	<?php
}
?>