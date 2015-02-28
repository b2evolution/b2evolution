<?php
/**
 * This file implements the UI view for the users statistics.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $dispatcher;

/*** Email statistics ***/

$SQL = new SQL();
$SQL->SELECT( 'dom_name,
	COUNT( IF( user_status = \'new\', 1, NULL ) ) AS cnt_new,
	COUNT( IF( user_status = \'activated\', 1, NULL ) ) AS cnt_activated,
	COUNT( IF( user_status = \'autoactivated\', 1, NULL ) ) AS cnt_autoactivated,
	COUNT( IF( user_status = \'emailchanged\', 1, NULL ) ) AS cnt_emailchanged,
	COUNT( IF( user_status = \'deactivated\', 1, NULL ) ) AS cnt_deactivated,
	COUNT( IF( user_status = \'failedactivation\', 1, NULL ) ) AS cnt_failedactivation,
	COUNT( IF( user_status = \'closed\', 1, NULL ) ) AS cnt_closed,
	( COUNT( IF( user_status IN( \'activated\', \'autoactivated\' ), 1, NULL ) ) / COUNT( * ) ) AS percent' );
$SQL->FROM( 'T_basedomains' );
$SQL->FROM_add( 'LEFT JOIN T_users ON user_email_dom_ID = dom_ID' );
$SQL->WHERE( 'dom_type = \'email\'' );
$SQL->GROUP_BY( 'dom_ID' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'COUNT( dom_ID )' );
$count_SQL->FROM( 'T_basedomains' );
$count_SQL->WHERE( 'dom_type = \'email\'' );

// Create result set:
$Results = new Results( $SQL->get(), 'dom_', '--D', NULL, $count_SQL->get() );

$Results->title = T_('Email statistics');

$Results->cols[] = array(
		'th' => T_('Email domain'),
		'td' => '$dom_name$',
		'order' => 'dom_name',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
	);

$Results->cols[] = array(
		'th' => T_('# New'),
		'td' => '$cnt_new$',
		'order' => 'cnt_new',
		'default_dir' => 'D',
	);

$Results->cols[] = array(
		'th' => T_('# Activated'),
		'td' => '$cnt_activated$',
		'order' => 'cnt_activated',
		'default_dir' => 'D',
	);

$Results->cols[] = array(
		'th' => T_('# Autoactivated'),
		'td' => '$cnt_autoactivated$',
		'order' => 'cnt_autoactivated',
		'default_dir' => 'D',
	);

$Results->cols[] = array(
		'th' => T_('# Email changed'),
		'td' => '$cnt_emailchanged$',
		'order' => 'cnt_emailchanged',
		'default_dir' => 'D',
	);

$Results->cols[] = array(
		'th' => T_('# Deactivated'),
		'td' => '$cnt_deactivated$',
		'order' => 'cnt_deactivated',
		'default_dir' => 'D',
	);

$Results->cols[] = array(
		'th' => T_('# Failed Activation'),
		'td' => '$cnt_failedactivation$',
		'order' => 'cnt_failedactivation',
		'default_dir' => 'D',
	);

$Results->cols[] = array(
		'th' => T_('# Closed'),
		'td' => '$cnt_closed$',
		'order' => 'cnt_closed',
		'default_dir' => 'D',
	);

/**
 * Format percent value
 *
 * @param float Percent
 * @return string Formatted percent
 */
function stats_active_percent( $percent )
{
	return floor( $percent * 100 ).' %';
}
$Results->cols[] = array(
		'th' => /* xgettext:no-php-format */ T_('% of active users'),
		'td' => '%stats_active_percent( #percent# )%',
		'order' => 'percent',
		'default_dir' => 'D',
	);

$Results->display( array(
		'page_url' => $dispatcher.'?ctrl=users&amp;tab=stats'
	) );


// ---- The data for donut chart: BEGIN ---- //
global $user_gender_color;

$donut_chart = array();

// Canvas size:
$donut_chart['width'] = 280;
$donut_chart['height'] = 280;

// Colors:
$donut_chart['series_color'] = array();
$donut_chart['series_color'][0] = array(); // Outer donut
$donut_chart['series_color'][1] = $user_gender_color; // Middle donut
$donut_chart['series_color'][2] = array(); // Inner donut
$c = 0;
foreach( $user_gender_color as $color )
{
	$donut_chart['series_color'][0][] = $color;
	// Use white(transparent) color for users without photos
	$donut_chart['series_color'][0][] = 'FFF';
	if( $c % 3 == 0 )
	{ // The inner donut uses only 3 main colors
		$donut_chart['series_color'][2][] = $color;
	}
	$c++;
}

// Legend titles:
$donut_chart['legend_numrows'] = 9;
$donut_chart['legends'] = array(
	T_('Women / Active / With Photo'),
	T_('Women / Active / No Photo'),
	T_('Women / Inactive / With Photo'),
	T_('Women / Inactive / No Photo'),
	T_('Women / Closed / With Photo'),
	T_('Women / Closed / No Photo'),
	T_('Men / Active / With Photo'),
	T_('Men / Active / No Photo'),
	T_('Men / Inactive / With Photo'),
	T_('Men / Inactive / No Photo'),
	T_('Men / Closed / With Photo'),
	T_('Men / Closed / No Photo'),
	T_('Unknown / Active / With Photo'),
	T_('Unknown / Active / No Photo'),
	T_('Unknown / Inactive / With Photo'),
	T_('Unknown / Inactive / No Photo'),
	T_('Unknown / Closed / With Photo'),
	T_('Unknown / Closed / No Photo') );

// Data:
$donut_chart['data'] = array();
$donut_chart['data'][0] = array();
$donut_chart['data'][1] = array();
$donut_chart['data'][2] = array();

/* 
 * Test data array:
 *   F - Female, M - Male, G - No gender
 *   a - Active, i - Inactive, c - closed
 *   p - with photo, n - without photo
 * 
 *
$donut_chart['data'][0] = array( 'Fap' => 1, 'Fan' => 4, 'Fip' => 1, 'Fin' => 2, 'Fcp' => 2, 'Fcn' => 1,
	                               'Map' => 3, 'Man' => 1, 'Mip' => 2, 'Min' => 1, 'Mcp' => 0, 'Mcn' => 1,
	                               'Gap' => 1, 'Gan' => 0, 'Gip' => 2, 'Gin' => 0, 'Gcp' => 1, 'Gcn' => 3 );
$donut_chart['data'][1] = array( 'Fa' => 5, 'Fi' => 3, 'Fc' => 3,
	                               'Ma' => 4, 'Mi' => 3, 'Mc' => 1,
	                               'Ga' => 1, 'Gi' => 2, 'Gc' => 4 );
$donut_chart['data'][2] = array( 'F' => 11, 'M' => 8, 'G' => 7 );*/

// Get users data for donut charts from DB
$donut_SQL = new SQL();
$donut_SQL->SELECT( 'IF( user_gender IN ( "M", "F" ), user_gender, "G" ) AS gender, '
	.'IF( user_status IN( "activated", "autoactivated" ), "a", IF( user_status = "closed", "c", "i" ) ) AS active, '
	.'IF( user_avatar_file_ID IS NOT NULL, "p", "n" ) AS photo, '
	.'COUNT( user_ID ) AS cnt' );
$donut_SQL->FROM( 'T_users' );
$donut_SQL->GROUP_BY( 'gender, active, photo' );
$donut_data = $DB->get_results( $donut_SQL->get(), ARRAY_A );

// Go through these shcemes to build/init a correct data for donut charts
$scheme_gender = array( 'F', 'M', 'G' );
$scheme_active = array( 'a', 'i', 'c' );
$scheme_photos = array( 'p', 'n' );
foreach( $scheme_gender as $gender )
{ // Genders
	$donut_chart['data'][2][ $gender ] = 0;
	foreach( $scheme_active as $active )
	{ // Actives
		$donut_chart['data'][1][ $gender.$active ] = 0;
		foreach( $scheme_photos as $photo )
		{ // Photos
			$donut_chart['data'][0][ $gender.$active.$photo ] = 0;
		}
	}
}

// Insert the data from DB to the donut chart data
foreach( $donut_data as $donut_data_row )
{
	foreach( $donut_chart['data'][0] as $gap => $value )
	{ // Gender + Active + Photo
		if( $donut_data_row['gender'].$donut_data_row['active'].$donut_data_row['photo'] == $gap )
		{
			$donut_chart['data'][0][ $gap ] += $donut_data_row['cnt'];
		}
	}
	foreach( $donut_chart['data'][1] as $ga => $value )
	{ // Gender + Active
		if( $donut_data_row['gender'].$donut_data_row['active'] == $ga )
		{
			$donut_chart['data'][1][ $ga ] += $donut_data_row['cnt'];
		}
	}
	foreach( $donut_chart['data'][2] as $g => $value )
	{ // Gender
		if( $donut_data_row['gender'] == $g )
		{
			$donut_chart['data'][2][ $g ] += $donut_data_row['cnt'];
		}
	}
}

load_funcs('_ext/_canvascharts.php');
echo '<div style="width:690px;margin:auto;">';
CanvasDonutChart( $donut_chart );
echo '</div>';
// ---- The data for donut chart: END ---- //


/*** Gender statistics ***/

// Get total values
$SQL = new SQL();
$SQL->SELECT( 'COUNT( * )' );
$SQL->FROM( 'T_users' );
// Active users
$SQL->WHERE( 'user_status IN( \'activated\', \'autoactivated\' )' );
$total_cnt_active = $DB->get_var( $SQL->get() );

// Not active users
$SQL->WHERE( 'user_status NOT IN( \'activated\', \'autoactivated\', \'closed\' )' );
$total_cnt_notactive = $DB->get_var( $SQL->get() );

// Closed users
$SQL->WHERE( 'user_status = \'closed\'' );
$total_cnt_closed = $DB->get_var( $SQL->get() );

// Users with pictures
$SQL->WHERE( 'user_avatar_file_ID IS NOT NULL' );
$total_cnt_pictured = $DB->get_var( $SQL->get() );

// Get all records
$SQL = new SQL();
$SQL->SELECT( 'IF( user_gender IN ( "M", "F" ), user_gender, "A" ) AS gender_sign,
	COUNT( IF( user_status IN( \'activated\', \'autoactivated\' ), 1, NULL ) ) AS cnt_active,
	COUNT( IF( user_status IN( \'activated\', \'autoactivated\', \'closed\' ), NULL, 1 ) ) AS cnt_notactive,
	COUNT( IF( user_status = \'closed\', 1, NULL ) ) AS cnt_closed,
	COUNT( IF( user_avatar_file_ID IS NOT NULL, 1, NULL ) ) AS cnt_pictured' );
$SQL->FROM( 'T_users' );
$SQL->GROUP_BY( 'gender_sign' );

// Create result set:
$Results = new Results( $SQL->get(), 'gender_', 'D', NULL, 3 );

$Results->title = T_('Gender statistics');

/**
 * Get formatted data with current value and percentage
 *
 * @param integer Cell value
 * @param integer Total of the column
 * @return string Value (Percent)
 */
function stats_gender_value( $value, $total )
{
	$percent = $total == 0 ? 0 : floor( $value / $total * 100 );

	return $value.' ('.$percent.'%)';
}

/**
 * Convert gender value from DB short name to full readable name
 *
 * @param string Gender value
 * @return string Gender name
 */
function stats_gender_name( $gender )
{
	switch( $gender )
	{
		case 'F':
			return T_('Women');
			break;
		case 'M':
			return T_('Men');
			break;
		default:
			return T_('Unknown');
			break;
	}
}
$Results->cols[] = array(
		'th' => T_('Gender'),
		'td' => '%stats_gender_name( #gender_sign# )%',
		'order' => 'gender_sign',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'total' => '<strong>'.T_('Total').'</strong>',
	);

$Results->cols[] = array(
		'th' => T_('# Active'),
		'td' => '%stats_gender_value( #cnt_active#, '.$total_cnt_active.' )%',
		'order' => 'cnt_active',
		'default_dir' => 'D',
		'total' => $total_cnt_active,
	);

$Results->cols[] = array(
		'th' => T_('# Not active'),
		'td' => '%stats_gender_value( #cnt_notactive#, '.$total_cnt_notactive.' )%',
		'order' => 'cnt_notactive',
		'default_dir' => 'D',
		'total' => $total_cnt_notactive,
	);

$Results->cols[] = array(
		'th' => T_('# Closed'),
		'td' => '%stats_gender_value( #cnt_closed#, '.$total_cnt_closed.' )%',
		'order' => 'cnt_closed',
		'default_dir' => 'D',
		'total' => $total_cnt_closed,
	);

$Results->cols[] = array(
		'th' => T_('# With profile picture'),
		'td' => '%stats_gender_value( #cnt_pictured#, '.$total_cnt_pictured.' )%',
		'order' => 'cnt_pictured',
		'default_dir' => 'D',
		'total' => $total_cnt_pictured,
	);

echo '<br />';
$Results->display( array(
		'page_url' => $dispatcher.'?ctrl=users&amp;tab=stats'
	) );


/*** Graph of registrations per day ***/

echo '<h2>'.T_('# registrations per day').'</h2>';

global $AdminUI, $user_gender_color;

$SQL = new SQL();
$SQL->SELECT( 'SQL_NO_CACHE COUNT(*) AS users,
		CONCAT( IF( user_gender IN ( "M", "F" ), user_gender, "G" ), "_", IF( user_status IN ( "activated", "autoactivated" ), "active", ( IF( user_status = "closed", "closed", "notactive" ) ) ) ) AS user_gender_status,
		EXTRACT(YEAR FROM user_created_datetime) AS year,
		EXTRACT(MONTH FROM user_created_datetime) AS month,
		EXTRACT(DAY FROM user_created_datetime) AS day' );
$SQL->FROM( 'T_users' );
$SQL->WHERE( 'user_created_datetime >= '.$DB->quote( date( 'Y-m-d H:i:s', mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ) - 1, date( 'd' ), date( 'Y' ) ) ) ) );
$SQL->GROUP_BY( 'year, month, day, user_gender_status' );
$SQL->ORDER_BY( 'year DESC, month DESC, day DESC, user_gender_status' );
$res_users = $DB->get_results( $SQL->get(), ARRAY_A, 'Get user summary' );

/*
 * Chart
 */
if( count( $res_users ) )
{
	$last_date = 0;

/*
ONE COLOR for Active Female
ONE COLOR for Inactive Female
ONE COLOR for Closed Female
ONE COLOR for Active Male
ONE COLOR for Inactive Male
ONE COLOR for Closed Male
ONE COLOR for Active No gender
ONE COLOR for Inactive No gender
ONE COLOR for Closed No gender
*/
	$col_mapping = array(
			'F_active'    => 1,
			'F_notactive' => 2,
			'F_closed'    => 3,
			'M_active'    => 4,
			'M_notactive' => 5,
			'M_closed'    => 6,
			'G_active'    => 7,
			'G_notactive' => 8,
			'G_closed'    => 9,
		);

	for( $i = 0; $i <= 9; $i++ )
	{
		$chart[ 'chart_data' ][ $i ] = array();
	}

	$chart['dates'] = array();
	$chart['legend_numrows'] = 3;

	$count = 0;
	foreach( $res_users as $row_stats )
	{
		$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );
		if( $last_date != $this_date )
		{ // We just hit a new day, let's display the previous one:
			$last_date = $this_date;	// that'll be the next one
			$count ++;
			array_unshift( $chart[ 'chart_data' ][ 0 ], date( 'D '.locale_datefmt(), $last_date ) );
			for( $i = 1; $i <= 9; $i++ )
			{
				array_unshift( $chart[ 'chart_data' ][ $i ], 0 );
			}

			array_unshift( $chart['dates'], $last_date );
		}
		if( ! empty ( $col_mapping[ $row_stats['user_gender_status'] ] ) )
		{	// those users are calculated here
			$col = $col_mapping[ $row_stats['user_gender_status'] ];
			$chart['chart_data'][$col][0] = $row_stats['users'];
		}
	}

/*
ONE COLOR for user_gender = F AND status IN ( activated, autoactivated )
ONE COLOR for user_gender = F AND status NOT IN ( activated, autoactivated )
ONE COLOR for user_gender = M AND status IN ( activated, autoactivated )
ONE COLOR for user_gender = M AND status NOT IN ( activated, autoactivated )
ONE COLOR for user_gender = NULL AND status IN ( activated, autoactivated )
ONE COLOR for user_gender = NULL AND status NOT IN ( activated, autoactivated )
*/
	array_unshift( $chart[ 'chart_data' ][ 0 ], '' );
	array_unshift( $chart[ 'chart_data' ][ 1 ], T_('Women (Active)') );
	array_unshift( $chart[ 'chart_data' ][ 2 ], T_('Women (Inactive)') );
	array_unshift( $chart[ 'chart_data' ][ 3 ], T_('Women (Closed)') );
	array_unshift( $chart[ 'chart_data' ][ 4 ], T_('Men (Active)') );
	array_unshift( $chart[ 'chart_data' ][ 5 ], T_('Men (Inactive)') );
	array_unshift( $chart[ 'chart_data' ][ 6 ], T_('Men (Closed)') );
	array_unshift( $chart[ 'chart_data' ][ 7 ], T_('Unknown (Active)') );
	array_unshift( $chart[ 'chart_data' ][ 8 ], T_('Unknown (Inactive)') );
	array_unshift( $chart[ 'chart_data' ][ 9 ], T_('Unknown (Closed)') );

	$chart[ 'series_color' ] = $user_gender_color;

	$chart[ 'canvas_bg' ] = array( 'width'  => 780, 'height' => 355 );

	echo '<div class="center" style="margin-bottom:70px">';
	CanvasBarsChart( $chart );
	echo '</div>';
}

?>