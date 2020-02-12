<?php
/**
 * This file implements the UI view for the Goal Hit list.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $sec_ID, $admin_url, $Settings;

// All diagarm and table columns for current page:
$diagram_columns = array();

// Display panel with buttons to control a view of goal hits pages:
display_hits_summary_panel();

// Check if it is a mode to display a live data:
$hits_summary_mode = get_hits_summary_mode();
$is_live_mode = ( $hits_summary_mode == 'live' );

// Get goal hits data for chart and table:
$res_hits = get_hits_results_goal( $hits_summary_mode );

$final = param( 'final', 'integer', 0, true );
$goal_name = param( 'goal_name', 'string', NULL, true );
$goal_cat = param( 'goal_cat', 'integer', 0, true );

$hitgroup_array = array();
foreach( $res_hits as $goal_data )
{
	$goal_date = $goal_data['year'].'-'.str_repeat( '0', 2 - strlen( $goal_data['month'] ) ).$goal_data['month'].'-'.str_repeat( '0', 2 - strlen( $goal_data['day'] ) ).$goal_data['day'];
	$hitgroup_array[ $goal_date ][ $goal_data['goal_ID'] ] = $goal_data['hits'];
}

// Get list of all goals
$SQL = new SQL( 'Get list of all goals' );
$SQL->SELECT( 'goal_ID, goal_name, gcat_color' );
$SQL->FROM( 'T_track__goal' );
$SQL->FROM_add( 'LEFT JOIN T_track__goalcat ON gcat_ID = goal_gcat_ID' );
if( ! empty( $final ) )
{	// We want to filter on final goals only:
	$SQL->WHERE_and( 'goal_redir_url IS NULL' );
}
if( ! empty( $goal_name ) ) // TODO: allow combine
{ // We want to filter on the goal name:
	$SQL->WHERE_and( 'goal_name LIKE '.$DB->quote( $goal_name.'%' ) );
}
if( ! empty( $goal_cat ) )
{ // We want to filter on the goal category:
	$SQL->WHERE_and( 'goal_gcat_ID = '.$DB->quote( $goal_cat ) );
}
$SQL->ORDER_BY( 'goal_name' );
$goal_rows = $DB->get_results( $SQL );


// Initialize params to filter by selected collection and/or group:
$section_params = empty( $blog ) ? '' : '&blog='.$blog;
$section_params .= empty( $sec_ID ) ? '' : '&sec_ID='.$sec_ID;

foreach( $goal_rows as $goal_row )
{
	$diagram_columns[ $goal_row->goal_ID ] = array(
			'title'     => $goal_row->goal_name,
			'color'     => ltrim( $goal_row->gcat_color, '#' ),
			'link_data' => array( $goal_row->goal_name )
		);
}

/*
 * Chart
 */
if( count( $goal_rows ) && count( $res_hits ) )
{
	// Display diagram for live or aggregated data:
	display_hits_diagram( 'goal', $diagram_columns, $res_hits );

	if( ! $is_live_mode )
	{	// Display diagram to compare hits:
		display_hits_filter_form( 'compare', $diagram_columns );
		$prev_res_hits = get_hits_results_goal( 'compare' );
		display_hits_diagram( 'goal', $diagram_columns, $prev_res_hits, 'cmpcanvasbarschart' );
	}
}

/*
 * Table:
 */
$Table = new Table( NULL, 'ghs_' );

$Table->title = T_('Goal hit summary').get_manual_link( 'goal-stats' );

$Table->cols = array(
	array( 'th' => T_('Date') )
);
foreach( $goal_rows as $goal_row )
{ // For each named goal, display name:
	$Table->cols[] = array(
			'th' => $goal_row->goal_name,
			// dirty hack to set background color for column header
			'th_class' => '" style="background-color:'.$goal_row->gcat_color,
			'td_class' => 'right',
		);
}
$Table->cols[] = array(
		'th' => T_('Total'),
		'td_class' => 'right',
	);




/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_goal_hitsummary( & $Form )
{
	$Form->checkbox_basic_input( 'final', get_param('final'), T_('Final') );
	$Form->text_input( 'goal_name', get_param('goal_name'), 20, T_('Goal names starting with'), '', array( 'maxlength'=>50 ) );

	$GoalCategoryCache = & get_GoalCategoryCache( NT_('All') );
	$GoalCategoryCache->load_all();
	$Form->select_input_object( 'goal_cat', get_param('goal_cat'), $GoalCategoryCache, T_('Goal category'), array( 'allow_none' => true ) );
}
$Table->filter_area = array(
	'callback' => 'filter_goal_hitsummary',
	'url_ignore' => 'final,goal_name',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=goals&amp;tab3=stats'.$section_params ),
		'final' => array( T_('Final'), '?ctrl=goals&amp;tab3=stats&amp;final=1'.$section_params ),
		)
	);


global $AdminUI;
$results_template= $AdminUI->get_template( 'Results' );

echo $results_template['before'];

$Table->display_init();

// TITLE / COLUMN HEADERS:
$Table->display_head();

if( empty( $hitgroup_array ) )
{ // No records
	$Table->total_pages = 0;
}

// START OF LIST/TABLE:
$Table->display_list_start();

if( $Table->total_pages > 0 )
{ // Display table

	// DISPLAY COLUMN HEADERS:
	$Table->display_col_headers();

	// BODY START:
	$Table->display_body_start();

	$goal_total = array();
	foreach( $hitgroup_array as $day => $hitday_array )
	{
		// Check if current data are live and not aggregated:
		$is_live_data = true;
		if( ! $is_live_mode )
		{	// Check only for "Aggregate data":
			$time_prune_before = mktime( 0, 0, 0 ) - ( $Settings->get( 'auto_prune_stats' ) * 86400 );
			$is_live_data = strtotime( $day ) >= $time_prune_before;
		}

		$Table->display_line_start();

		$Table->display_col_start();
		echo $day;
		if( $is_live_mode && $current_User->check_perm( 'stats', 'edit' ) )
		{	// Display a link to prune goal hits only for live data and if current user has a permission:
			echo action_icon( T_('Prune goal hits for this date!'), 'delete', url_add_param( $admin_url, 'ctrl=goals&amp;action=prune&amp;date='.strtotime( $day ).'&amp;blog='.$blog.'&amp;'.url_crumb( 'goals' ) ) );
		}
		$Table->display_col_end();

		$date_param = rawurlencode( date( locale_datefmt(), strtotime( $day ) ) );

		$line_total = 0;
		foreach( $goal_rows as $goal_row )
		{ // For each named goal, display count:
			if( ! isset( $goal_total[ $goal_row->goal_ID ] ) )
			{
				$goal_total[ $goal_row->goal_ID ] = 0;
			}
			$Table->display_col_start();
			if( isset( $hitday_array[ $goal_row->goal_ID ] ) )
			{
				if( $is_live_data )
				{
					echo '<a href="'.$admin_url.'?blog='.$blog.'&amp;ctrl=stats&amp;tab=goals&amp;tab3=hits&amp;datestartinput='.$date_param.'&amp;datestopinput='.$date_param.$section_params.'&amp;goal_name='.rawurlencode( $goal_row->goal_name ).'">'.$hitday_array[ $goal_row->goal_ID ].'</a>';
				}
				else
				{
					echo $hitday_array[ $goal_row->goal_ID ];
				}
				$line_total += $hitday_array[ $goal_row->goal_ID ];
				$goal_total[ $goal_row->goal_ID ] += $hitday_array[ $goal_row->goal_ID ];
			}
			else
			{
				echo '&nbsp;';
			}
			$Table->display_col_end();
		}

		$Table->display_col_start();
		echo $line_total;
		$Table->display_col_end();

		$Table->display_line_end();
	}

	// Totals row:
	echo $Table->params['total_line_start'];

	echo str_replace( '$class$', '', $Table->params['total_col_start_first'] );
	echo T_('Total');
	echo $Table->params['total_col_end'];

	$all_total = 0;
	foreach( $goal_rows as $goal_row )
	{ // For each named goal, display total of count:
		echo str_replace( '$class_attrib$', 'class="right"', $Table->params['total_col_start'] );
		if( ! empty( $goal_total[ $goal_row->goal_ID ] ) )
		{
			if( $is_live_data )
			{
				echo '<a href="?blog=0&amp;ctrl=stats&amp;tab=goals&amp;tab3=hits&amp;goal_name='.rawurlencode( $goal_row->goal_name ).'">'.$goal_total[ $goal_row->goal_ID ].'</a>';
			}
			else
			{
				echo $goal_total[ $goal_row->goal_ID ];
			}
			$all_total += $goal_total[ $goal_row->goal_ID ];
		}
		else
		{
			echo '&nbsp;';
		}
		echo $Table->params['total_col_end'];
	}

	echo str_replace( '$class$', 'right', $Table->params['total_col_start_last'] );
	echo $all_total;
	echo $Table->params['total_col_end'];

	echo $this->params['total_line_end'];

	// BODY END:
	$Table->display_body_end();
}

$Table->display_list_end();

echo $results_template['after'];

?>