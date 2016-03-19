<?php
/**
 * This file implements the UI view for the Goal Hit list.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url;

$final = param( 'final', 'integer', 0, true );
$goal_name = param( 'goal_name', 'string', NULL, true );
$goal_cat = param( 'goal_cat', 'integer', 0, true );

// Get all goal hits:
$SQL = new SQL();
$SQL->SELECT( 'DATE_FORMAT( hit_datetime, "%Y-%m-%d" ) as day, ghit_goal_ID, COUNT(ghit_ID) as count' );
$SQL->FROM( 'T_track__goalhit' );
$SQL->FROM_add( 'INNER JOIN T_hitlog ON ghit_hit_ID = hit_ID' );
$SQL->GROUP_BY( 'day DESC, ghit_goal_ID' );
$hitgroup_rows = $DB->get_results( $SQL->get(), OBJECT, 'Get hits by day and goal' );

$hitgroup_array = array();
foreach( $hitgroup_rows as $hitgroup_row )
{
	$hitgroup_array[ $hitgroup_row->day ][ $hitgroup_row->ghit_goal_ID ] = $hitgroup_row->count;
}

// Get list of all goals
$SQL = new SQL();
$SQL->SELECT( 'goal_ID, goal_name, gcat_color' );
$SQL->FROM( 'T_track__goal' );
$SQL->FROM_add( 'LEFT JOIN T_track__goalcat ON gcat_ID = goal_gcat_ID' );
if( !empty($final) )
{	// We want to filter on final goals only:
	$SQL->WHERE_and( 'goal_redir_url IS NULL' );
}
if( !empty($goal_name) ) // TODO: allow combine
{ // We want to filter on the goal name:
	$SQL->WHERE_and( 'goal_name LIKE '.$DB->quote($goal_name.'%') );
}
if( ! empty( $goal_cat ) )
{ // We want to filter on the goal category:
	$SQL->WHERE_and( 'goal_gcat_ID = '.$DB->quote( $goal_cat ) );
}
$SQL->ORDER_BY( 'goal_name' );
$goal_rows = $DB->get_results( $SQL->get(), OBJECT, 'Get list of all goals' );


/*
 * Chart
 */
if( count( $goal_rows ) && count( $hitgroup_array ) )
{
	$chart = array();

	$chart['chart_data'] = array();
	$chart['chart_data'][0] = array();

	// Initialize the data to open an url by click on bar item
	$chart['link_data'] = array();
	$chart['link_data']['url'] = $admin_url.'?ctrl=stats&tab=goals&tab3=hits&blog='.$blog.'&datestartinput=$date$&datestopinput=$date$&goal_name=$param1$';
	$chart['link_data']['params'] = array();

	// Column mapping and colors
	$col_mapping = array();
	$chart['series_color'] = array();
	foreach( $goal_rows as $g => $goal_row )
	{
		$col_mapping[ $goal_row->goal_ID ] = $g+1;
		$chart['series_color'][ $g ] = str_replace( '#', '', $goal_row->gcat_color );
		$chart['chart_data'][ $g+1 ] = array();
		$chart['link_data']['params'][ $g+1 ] = array( $goal_row->goal_name );
	}

	// A hit count for each date and goal
	$chart['dates'] = array();
	foreach( $hitgroup_array as $day => $goal_data )
	{
		$day_time = strtotime( $day );
		foreach( $goal_rows as $g => $goal_row )
		{ // A hit count for the goal and date
			array_unshift( $chart['chart_data'][ $col_mapping[ $goal_row->goal_ID ] ], ( isset( $goal_data[ $goal_row->goal_ID ] ) ? $goal_data[ $goal_row->goal_ID ]: 0 ) );
		}
		// Date value
		array_unshift( $chart['dates'], $day_time );
		// Date title
		array_unshift( $chart['chart_data'][0], date( 'D '.locale_datefmt(), $day_time ) );
	}
	array_unshift( $chart['chart_data'][0], '' );

	// Goal names
	foreach( $goal_rows as $g => $goal_row )
	{
		array_unshift( $chart['chart_data'][ $g+1 ], $goal_row->goal_name );
	}

	// Chart params
	$chart['canvas_bg'] = array( 'width' => 780, 'height' => 355 );

	// Print out chart
	echo '<div class="center">';
	load_funcs('_ext/_canvascharts.php');
	CanvasBarsChart( $chart );
	echo '</div>';
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
		'all' => array( T_('All'), '?ctrl=goals&amp;tab3=stats&amp;blog='.$blog ),
		'final' => array( T_('Final'), '?ctrl=goals&amp;tab3=stats&amp;final=1&amp;blog='.$blog ),
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
		$Table->display_line_start();

		$Table->display_col_start();
		echo $day;
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
				echo '<a href="'.$admin_url.'?blog='.$blog.'&amp;ctrl=stats&amp;tab=goals&amp;tab3=hits&amp;datestartinput='.$date_param.'&amp;datestopinput='.$date_param.'&amp;goal_name='.rawurlencode( $goal_row->goal_name ).'">'.$hitday_array[ $goal_row->goal_ID ].'</a>';
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
			echo '<a href="?blog=0&amp;ctrl=stats&amp;tab=goals&amp;tab3=hits&amp;goal_name='.rawurlencode( $goal_row->goal_name ).'">'.$goal_total[ $goal_row->goal_ID ].'</a>';
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