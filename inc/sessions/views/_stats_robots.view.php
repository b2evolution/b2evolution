<?php
/**
 * This file implements the UI view for the robot stats.
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

global $blog, $sec_ID, $admin_url, $rsc_url, $AdminUI, $agent_type_color, $Settings, $localtimenow;

// All diagarm and table columns for current page:
$diagram_columns = array(
	'robot'  => array( 'title' => T_('Robot hits'), 'link_data' => array( 'robot' ) ),
);
foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
{
	$diagram_columns[ $diagram_column_key ]['color'] = $agent_type_color[ $diagram_column_key ];
}

echo '<h2 class="page-title">'.T_('Hits from indexing robots / spiders / crawlers - Summary').get_manual_link( 'robots-hits-summary' ).'</h2>';

echo '<p class="notes">'.T_('In order to be detected, robots must be listed in /conf/_stats.php.').'</p>';

// Display panel with buttons to control a view of hits summary pages:
display_hits_summary_panel();

// Check if it is a mode to display a live data:
$hits_summary_mode = get_hits_summary_mode();
$is_live_mode = ( $hits_summary_mode == 'live' );

// Get hits data for chart and table:
$res_hits = get_hits_results_robot( $hits_summary_mode );

if( count( $res_hits ) )
{
	// Initialize params to filter by selected collection and/or group:
	$section_params = empty( $blog ) ? '' : '&blog='.$blog;
	$section_params .= empty( $sec_ID ) ? '' : '&sec_ID='.$sec_ID;

	// Display diagram for live or aggregated data:
	display_hits_diagram( 'robot', $diagram_columns, $res_hits );

	if( ! $is_live_mode )
	{	// Display diagram to compare hits:
		display_hits_filter_form( 'compare', $diagram_columns );
		$prev_res_hits = get_hits_results_robot( 'compare' );
		display_hits_diagram( 'robot', $diagram_columns, $prev_res_hits, 'cmpcanvasbarschart' );
	}

	/*
	 * Table:
	 */
	echo '<table class="grouped table table-striped table-bordered table-hover table-condensed" cellspacing="0">';
	echo '	<tr>';
	echo '		<th class="firstcol shrinkwrap">'.T_('Date').'</th>';
	foreach( $diagram_columns as $diagram_column_key => $diagram_column_data )
	{
		echo '		<th class="lastcol" style="background-color: #'.$diagram_column_data['color'].'"><a href="'.$admin_url.'?ctrl=stats&amp;tab=hits&amp;agent_type='.$diagram_column_key.'&amp;blog='.$blog.'">'.$diagram_column_data['title'].'</a></th>';
	}
	echo '	</tr>';

	$hits_total = 0;
	foreach( $res_hits as $r => $row_stats )
	{
		$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );

		// Check if current data are live and not aggregated:
		$is_live_data = true;
		if( ! $is_live_mode )
		{	// Check only for "Aggregate data":
			$time_prune_before = mktime( 0, 0, 0 ) - ( $Settings->get( 'auto_prune_stats' ) * 86400 );
			$is_live_data = $this_date >= $time_prune_before;
		}
		?>
		<tr class="<?php echo ( $r % 2 == 1 ) ? 'odd' : 'even'; ?>">
			<td class="firstcol shrinkwrap" style="text-align:right"><?php
				echo date( 'D '.locale_datefmt(), $this_date );
				if( $is_live_mode && $current_User->check_perm( 'stats', 'edit' ) )
				{	// Display a link to prune hits only for live data and if current user has a permission:
					echo action_icon( T_('Prune hits for this date!'), 'delete', $admin_url.'?ctrl=stats&amp;action=prune&amp;date='.$this_date.'&amp;show=summary&amp;blog='.$blog.'&amp;'.url_crumb( 'stats' ) );
				}
			?></td>
			<td class="lastcol right"><?php echo $is_live_data ? '<a href="'.$admin_url.'?ctrl=stats&amp;tab=hits&amp;'
				.'datestartinput='.urlencode( date( locale_datefmt() , $this_date ) ).'&amp;'
				.'datestopinput='.urlencode( date( locale_datefmt(), $this_date ) ).'&amp;blog='.$blog.'&amp;agent_type=robot">'.$row_stats['hits'].'</a>' : $row_stats['hits']; ?></td>
		</tr>
		<?php
		// Increment total hits counter:
		$hits_total += $row_stats['hits'];
	}

	// Total numbers:
	?>
		<tr class="total">
			<td class="firstcol"><?php echo T_('Total') ?></td>
			<td class="lastcol right"><?php echo $is_live_mode ? '<a href="'.$admin_url.'?ctrl=stats&amp;tab=hits&amp;blog='.$blog.'&amp;agent_type=robot">'.$hits_total.'</a>' : $hits_total; ?></td>
		</tr>
	</table>
	<?php
}


// TOP INDEXING ROBOTS
/* put this back when we have a CONCISE table of robots
// Create result set:
$SQL = new SQL();
$SQL->SELECT( 'SQL_NO_CACHE COUNT(*) AS hit_count, agnt_signature' );
$SQL->FROM( 'T_hitlog' );
$SQL->WHERE( 'hit_agent_type = "robot"' );
if( ! empty( $blog ) )
	$SQL->WHERE_and( 'hit_coll_ID = '.$blog );
$SQL->GROUP_BY( 'agnt_signature' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'SQL_NO_CACHE COUNT( DISTINCT agnt_signature )' );
$count_SQL->FROM( $SQL->get_from( '' ) );
$count_SQL->WHERE( $SQL->get_where( '' ) );

$Results = new Results( $SQL->get(), 'topidx', '-D', 20, $count_SQL->get() );

$count_SQL->SELECT( 'SQL_NO_CACHE COUNT(*)' );
$total_hit_count = $DB->get_var( $count_SQL->get() );

$Results->title = T_('Top Indexing Robots');

/**
 * Helper function to translate agnt_signature to a "human-friendly" version from {@link $user_agents}.
 * @return string
 *
function translate_user_agent( $agnt_signature )
{
	global $user_agents;

	$html_signature = htmlspecialchars( $agnt_signature );
	$format = '<span title="'.$html_signature.'">%s</span>';

	foreach( $user_agents as $curr_user_agent )
	{
		if( strpos( $agnt_signature, $curr_user_agent[1] ) !== false )
		{
			return sprintf( $format, htmlspecialchars( $curr_user_agent[2] ) );
		}
	}

	if( ( $browscap = @get_browser( $agnt_signature ) ) && $browscap->browser != 'Default Browser' )
	{
		return sprintf( $format, htmlspecialchars( $browscap->browser ) );
	}

	return $html_signature;
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
*/

?>