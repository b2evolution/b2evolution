<?php
/**
 * This file implements the UI view for the users statistics.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: _user_stats.view.php 879 2012-02-22 13:20:54Z yura $
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


/*** Gender statistics ***/

// Get total values
$SQL = new SQL();
$SQL->SELECT( 'COUNT( * )' );
$SQL->FROM( 'T_users' );
// Active users
$SQL->WHERE( 'user_status IN( \'activated\', \'autoactivated\' )' );
$total_cnt_active = $DB->get_var( $SQL->get() );

// Not active users
$SQL->WHERE( 'user_status NOT IN( \'activated\', \'autoactivated\' )' );
$total_cnt_notactive = $DB->get_var( $SQL->get() );

// Users with pictures
$SQL->WHERE( 'user_avatar_file_ID IS NOT NULL' );
$total_cnt_pictured = $DB->get_var( $SQL->get() );

// Get all records
$SQL = new SQL();
$SQL->SELECT( 'IF( user_gender IN ( "M", "F" ), user_gender, "A" ) AS gender_sign,
	COUNT( IF( user_status IN( \'activated\', \'autoactivated\' ), 1, NULL ) ) AS cnt_active,
	COUNT( IF( user_status IN( \'activated\', \'autoactivated\' ), NULL, 1 ) ) AS cnt_notactive,
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
		CONCAT( IF( user_gender IN ( "M", "F" ), user_gender, "G" ), "_", IF( user_status IN ( "activated", "autoactivated" ), "active", "notactive" ) ) AS user_gender_status,
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
ONE COLOR for user_gender = F AND status IN ( activated, autoactivated )
ONE COLOR for user_gender = F AND status NOT IN ( activated, autoactivated )
ONE COLOR for user_gender = M AND status IN ( activated, autoactivated )
ONE COLOR for user_gender = M AND status NOT IN ( activated, autoactivated )
ONE COLOR for user_gender = NULL AND status IN ( activated, autoactivated )
ONE COLOR for user_gender = NULL AND status NOT IN ( activated, autoactivated )
*/
	$col_mapping = array(
			'F_active'    => 1,
			'F_notactive' => 2,
			'M_active'    => 3,
			'M_notactive' => 4,
			'G_active'    => 5,
			'G_notactive' => 6,
		);

	$chart[ 'chart_data' ][ 0 ] = array();
	$chart[ 'chart_data' ][ 1 ] = array();
	$chart[ 'chart_data' ][ 2 ] = array();
	$chart[ 'chart_data' ][ 3 ] = array();
	$chart[ 'chart_data' ][ 4 ] = array();
	$chart[ 'chart_data' ][ 5 ] = array();
	$chart[ 'chart_data' ][ 6 ] = array();

	$count = 0;
	foreach( $res_users as $row_stats )
	{
		$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );
		if( $last_date != $this_date )
		{ // We just hit a new day, let's display the previous one:
				$last_date = $this_date;	// that'll be the next one
				$count ++;
				array_unshift( $chart[ 'chart_data' ][ 0 ], date( locale_datefmt(), $last_date ) );
				array_unshift( $chart[ 'chart_data' ][ 1 ], 0 );
				array_unshift( $chart[ 'chart_data' ][ 2 ], 0 );
				array_unshift( $chart[ 'chart_data' ][ 3 ], 0 );
				array_unshift( $chart[ 'chart_data' ][ 4 ], 0 );
				array_unshift( $chart[ 'chart_data' ][ 5 ], 0 );
				array_unshift( $chart[ 'chart_data' ][ 6 ], 0 );
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
	array_unshift( $chart[ 'chart_data' ][ 1 ], 'Women (Active)' );
	array_unshift( $chart[ 'chart_data' ][ 2 ], 'Women (Inactive)' );
	array_unshift( $chart[ 'chart_data' ][ 3 ], 'Men (Active)' );
	array_unshift( $chart[ 'chart_data' ][ 4 ], 'Men (Inactive)' );
	array_unshift( $chart[ 'chart_data' ][ 5 ], 'Unknown (Active)' );
	array_unshift( $chart[ 'chart_data' ][ 6 ], 'Unknown (Inactive)' );

	// Include common chart properties:
	require dirname(__FILE__).'/inc/_bar_chart.inc.php';

	$chart[ 'series_color' ] = array (
			$user_gender_color['women_active'],
			$user_gender_color['women_notactive'],
			$user_gender_color['men_active'],
			$user_gender_color['men_notactive'],
			$user_gender_color['nogender_active'],
			$user_gender_color['nogender_notactive'],
		);


	echo '<div class="center">';
	load_funcs('_ext/_swfcharts.php');
	DrawChart( $chart );
	echo '</div>';
}

?>