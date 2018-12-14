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

global $blog, $sec_ID, $admin_url, $AdminUI, $referer_type_color, $Hit, $Settings, $localtimenow;

// All diagarm and table columns for current page:
$diagram_columns = array(
	'search'  => array( 'title' => T_('Referring searches'), 'link_data' => array( 'search',  '' ) ),
	'referer' => array( 'title' => T_('Referers'),           'link_data' => array( 'referer', '' ) ),
	'direct'  => array( 'title' => T_('Direct accesses'),    'link_data' => array( 'direct',  '' ) ),
	'self'    => array( 'title' => T_('Self referred'),      'link_data' => array( 'self',    '' ) ),
	'ajax'    => array( 'title' => T_('Ajax'),               'link_data' => array( '',        'ajax' ) ),
	'special' => array( 'title' => T_('Special referrers'),  'link_data' => array( 'special', '' ) ),
	'spam'    => array( 'title' => T_('Referer spam'),       'link_data' => array( 'spam',    '' ) ),
	'admin'   => array( 'title' => T_('Admin'),              'link_data' => array( '',        'admin' ) ),
	'session' => array( 'title' => T_('Sessions'),           'link_data' => false ),
);
foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
{
	$diagram_columns[ $diagram_column_key ]['color'] = $referer_type_color[ $diagram_column_key ];
}

echo '<h2 class="page-title">'.T_('Hits from web browsers - Summary').get_manual_link('browser_hits_summary').'</h2>';

// Display panel with buttons to control a view of hits summary pages:
display_hits_summary_panel( $diagram_columns );

// Filter diagram columns by seleated types:
$diagram_columns = get_filtered_hits_diagram_columns( 'browser', $diagram_columns );

// Check if it is a mode to display a live data:
$hits_summary_mode = get_hits_summary_mode();
$is_live_mode = ( $hits_summary_mode == 'live' );

// Get hits and session data for chart and table:
list( $res_hits, $sessions ) = get_hits_results_browser( $hits_summary_mode );

if( count( $res_hits ) )
{
	// Initialize params to filter by selected collection and/or group:
	$section_params = empty( $blog ) ? '' : '&blog='.$blog;
	$section_params .= empty( $sec_ID ) ? '' : '&sec_ID='.$sec_ID;

	// Display diagram for live or aggregated data:
	display_hits_diagram( 'browser', $diagram_columns, array( $res_hits, $sessions ) );

	if( ! $is_live_mode )
	{	// Display diagram to compare hits:
		display_hits_filter_form( 'compare', $diagram_columns );
		list( $prev_res_hits, $prev_sessions ) = get_hits_results_browser( 'compare' );
		display_hits_diagram( 'browser', $diagram_columns, array( $prev_res_hits, $prev_sessions ), 'cmpcanvasbarschart' );
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
								$diagram_col_url_params .= empty( $diagram_column_data['link_data'][1] ) ? '' : '&amp;hit_type='.$diagram_column_data['link_data'][1];
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
			$hit_key = in_array( $row_stats['hit_type'], array( 'ajax', 'admin' ) ) ? $row_stats['hit_type'] : $row_stats['referer_type'];
			if( isset( $hits[ $hit_key ] ) )
			{
				$hits[ $hit_key ] += $row_stats['hits'];
				$hits_total[ $hit_key ] += $row_stats['hits'];
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
							$diagram_col_url_params .= empty( $diagram_column_data['link_data'][1] ) ? '' : '&amp;hit_type='.$diagram_column_data['link_data'][1];
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

		$link_text_total = $admin_url.'?ctrl=stats&tab=hits'.$section_params.'&agent_type=browser';
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
						$diagram_col_url_params .= empty( $diagram_column_data['link_data'][1] ) ? '' : '&amp;hit_type='.$diagram_column_data['link_data'][1];
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