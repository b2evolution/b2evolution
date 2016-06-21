<?php
/**
 * This file implements the UI view for the syndication stats.
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

global $blog, $cgrp_ID, $admin_url, $rsc_url, $AdminUI, $agent_type_color;

echo '<h2 class="page-title">'.T_('Hits from RSS/Atom feed readers - Summary').get_manual_link( 'feed-hits-summary' ).'</h2>';

echo '<p class="notes">'.T_('Any user agent accessing the XML feeds will be flagged as an XML reader.').'</p>';

$SQL = new SQL( 'Get RSS/Atom feed readers hits summary' );
$SQL->SELECT( 'SQL_NO_CACHE COUNT(*) AS hits, EXTRACT(YEAR FROM hit_datetime) AS year,
	EXTRACT(MONTH FROM hit_datetime) AS month, EXTRACT(DAY FROM hit_datetime) AS day' );
$SQL->FROM( 'T_hitlog' );
$SQL->WHERE( 'hit_type = "rss"' );
if( ! empty( $cgrp_ID ) )
{	// Filter by collection group:
	$SQL->FROM_add( 'LEFT JOIN T_blogs ON hit_coll_ID = blog_ID' );
	$SQL->WHERE_and( 'blog_cgrp_ID = '.$cgrp_ID );
}
if( $blog > 0 )
{	// Filter by collection:
	$SQL->WHERE_and( 'hit_coll_ID = ' . $blog );
}
$SQL->GROUP_BY( 'year, month, day' );
$SQL->ORDER_BY( 'year DESC, month DESC, day DESC' );
$res_hits = $DB->get_results( $SQL->get(), ARRAY_A, $SQL->title );


/*
 * Chart
 */
if( count($res_hits) )
{
	// Initialize params to filter by selected collection and/or group:
	$coll_group_params = empty( $blog ) ? '' : '&blog='.$blog;
	$coll_group_params .= empty( $cgrp_ID ) ? '' : '&cgrp_ID='.$cgrp_ID;

	$last_date = 0;

	$chart[ 'chart_data' ][ 0 ] = array();
	$chart[ 'chart_data' ][ 1 ] = array();

	$chart['dates'] = array();

	// Initialize the data to open an url by click on bar item
	$chart['link_data'] = array();
	$chart['link_data']['url'] = $admin_url.'?ctrl=stats&tab=hits&datestartinput=$date$&datestopinput=$date$'.$coll_group_params.'&hit_type=$param1$';
	$chart['link_data']['params'] = array(
			array( 'rss' )
		);

	$count = 0;
	foreach( $res_hits as $row_stats )
	{
		$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );
		if( $last_date != $this_date )
		{ // We just hit a new day, let's display the previous one:
			$last_date = $this_date;	// that'll be the next one
			$count ++;
			array_unshift( $chart[ 'chart_data' ][ 0 ], date( 'D '.locale_datefmt(), $last_date ) );
			array_unshift( $chart[ 'chart_data' ][ 1 ], 0 );

			array_unshift( $chart['dates'], $last_date );
		}
		$chart [ 'chart_data' ][1][0] = $row_stats['hits'];
	}

	array_unshift( $chart[ 'chart_data' ][ 0 ], '' );
	array_unshift( $chart[ 'chart_data' ][ 1 ], T_('XML (RSS/Atom) hits') );	// Translations need to be UTF-8

	$chart[ 'series_color' ] = array (
			$agent_type_color['rss'],
		);

	$chart[ 'canvas_bg' ] = array( 'width'  => 780, 'height' => 355 );

	echo '<div class="center">';
	load_funcs('_ext/_canvascharts.php');
	CanvasBarsChart( $chart );
	echo '</div>';


	/*
	 * Table:
	 */
	echo '<table class="grouped table table-striped table-bordered table-hover table-condensed" cellspacing="0">';
	echo '	<tr>';
	echo '		<th class="firstcol shrinkwrap">'.T_('Date').'</th>';
	echo '		<th class="lastcol" style="background-color: #'.$agent_type_color['rss'].'"><a href="'.$admin_url.'?ctrl=stats&amp;tab=hits&amp;hit_type=rss&amp;blog='.$blog.'">'.T_('XML (RSS/Atom) hits').'</a></th>';
	echo '	</tr>';

	$hits_total = 0;
	foreach( $res_hits as $r => $row_stats )
	{
		$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );
		?>
		<tr class="<?php echo ( $r % 2 == 1 ) ? 'odd' : 'even'; ?>">
			<td class="firstcol shrinkwrap" style="text-align:right"><?php
				echo date( 'D '.locale_datefmt(), $this_date );
				if( $current_User->check_perm( 'stats', 'edit' ) )
				{
					echo action_icon( T_('Prune hits for this date!'), 'delete', $admin_url.'?ctrl=stats&amp;action=prune&amp;date='.$this_date.'&amp;show=summary&amp;blog='.$blog.'&amp;'.url_crumb( 'stats' ) );
				}
			?></td>
			<td class="lastcol right"><a href="<?php echo $admin_url.'?ctrl=stats&amp;tab=hits&amp;'
				.'datestartinput='.urlencode( date( locale_datefmt() , $this_date ) ).'&amp;'
				.'datestopinput='.urlencode( date( locale_datefmt(), $this_date ) ).'&amp;blog='.$blog.'&amp;hit_type=rss'; ?>"><?php echo $row_stats['hits']; ?></a></td>
		</tr>
		<?php
		// Increment total hits counter:
		$hits_total += $row_stats['hits'];
	}

	// Total numbers:
	?>
		<tr class="total">
			<td class="firstcol"><?php echo T_('Total') ?></td>
			<td class="lastcol right"><a href="<?php echo $admin_url.'?ctrl=stats&amp;tab=hits&amp;blog='.$blog.'&amp;hit_type=rss'; ?>"><?php echo $hits_total; ?></a></td>
		</tr>
	</table>
	<?php
}
?>