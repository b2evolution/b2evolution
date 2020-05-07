<?php
/**
 * This file implements the UI view for the browser hits summary.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
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
$hits_summary_mode = get_hits_summary_mode();
$is_live_mode = ( $hits_summary_mode == 'live' );

// Get hits data for chart and table:
$res_hits = get_hits_results_search_referers( $hits_summary_mode );

if( count( $res_hits ) )
{
	// Initialize params to filter by selected collection and/or group:
	$section_params = empty( $blog ) ? '' : '&blog='.$blog;
	$section_params .= empty( $sec_ID ) ? '' : '&sec_ID='.$sec_ID;

	// Display diagram for live or aggregated data:
	display_hits_diagram( 'search_referers', $diagram_columns, $res_hits );

	if( ! $is_live_mode )
	{	// Display diagram to compare hits:
		display_hits_filter_form( 'compare', $diagram_columns );
		$prev_res_hits = get_hits_results_search_referers( 'compare' );
		display_hits_diagram( 'search_referers', $diagram_columns, $prev_res_hits, 'cmpcanvasbarschart' );
	}

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
			foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
			{
				echo '<th style="background-color:#'.$diagram_column_data['color'].'">'.$diagram_column_data['title'].'</th>';
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

			$link_text = $admin_url.'?ctrl=stats&amp;tab=hits&amp;datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&amp;datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).$section_params.'&amp;agent_type=browser';
			$link_text_total_day = $admin_url.'?ctrl=stats&amp;tab=hits&amp;datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&amp;datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).$section_params.'&amp;agent_type=browser';

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
							echo action_icon( T_('Prune hits for this date!'), 'delete', url_add_param( $admin_url, 'ctrl=stats&amp;action=prune&amp;date='.$last_date.'&amp;show=summary'.$section_params.'&amp;'.url_crumb('stats') ) );
						}
					?></td><?php
					foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
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

			$link_text = $admin_url.'?ctrl=stats&amp;tab=hits&amp;datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&amp;datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).$section_params.'&amp;agent_type=browser';
			$link_text_total_day = $admin_url.'?ctrl=stats&amp;tab=hits&amp;datestartinput='.urlencode( date( locale_datefmt() , $last_date ) ).'&amp;datestopinput='.urlencode( date( locale_datefmt(), $last_date ) ).$section_params.'&amp;agent_type=browser';

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
						echo action_icon( T_('Prune hits for this date!'), 'delete', url_add_param( $admin_url, 'ctrl=stats&amp;action=prune&amp;date='.$last_date.'&amp;show=summary'.$section_params.'&amp;'.url_crumb('stats') ) );
					}
				?></td><?php
				foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
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
				?>
				<td class="lastcol right"><?php echo $is_live_data ? '<a href="'.$link_text_total_day.'">'.array_sum( $hits ).'</a>' : array_sum( $hits ); ?></td>
			</tr>
			<?php
		}

		// Total numbers:

		$link_text_total = $admin_url.'?ctrl=stats&tab=hits'.$section_params.'&agent_type=browser';
		?>

		<tr class="total">
			<td class="firstcol"><?php echo T_('Total') ?></td><?php
			foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
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
			?>
			<td class="lastcol right"><?php echo $is_live_data ? '<a href="'.$link_text_total.'">'.array_sum( $hits_total ).'</a>' : array_sum( $hits_total ); ?></td>
		</tr>

	</table>

	<?php
}

?>