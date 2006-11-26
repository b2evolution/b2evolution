<?php
/**
 * This file implements the UI view for the browser hits summary.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url;


echo '<h2>'.T_('Browser hits summary').'</h2>';


echo '<p>'.sprintf( T_('This page only includes hits identified as made by a <a %s>web browser</a>.'), ' href="?ctrl=stats&amp;tab=useragents&amp;agnt_browser=1&amp;blog='.$blog.'"' ).'</p>';


// fplanque>> I don't get it, it seems that GROUP BY on the referer type ENUM fails pathetically!!
// Bug report: http://lists.mysql.com/bugs/36
// Solution : CAST to string
// TODO: I've also limited this to agnt_type "browser" here, according to the change for "referers" (Rev 1.6)
//       -> an RSS service that sends a referer is not a real referer (though it should be listed in the robots list)! (blueyed)
$sql = '
	SELECT COUNT(*) AS hits, CONCAT(hit_referer_type) AS referer_type, YEAR(hit_datetime) AS year,
			   MONTH(hit_datetime) AS month, DAYOFMONTH(hit_datetime) AS day
		FROM T_hitlog INNER JOIN T_useragents ON hit_agnt_ID = agnt_ID
	 WHERE agnt_type = "browser"';
if( $blog > 0 )
{
	$sql .= ' AND hit_blog_ID = '.$blog;
}
$sql .= ' GROUP BY year, month, day, referer_type
					ORDER BY year DESC, month DESC, day DESC, referer_type';
$res_hits = $DB->get_results( $sql, ARRAY_A, 'Get hit summary' );


/*
 * Chart
 */
if( count($res_hits) )
{
	$last_date = 0;

	$col_mapping = array(
			'search' => 1,
			'referer' => 2,
			'direct' => 3,
			'self' => 4,
			'blacklist' => 5,
			'admin' => 6,
		);

	$chart[ 'chart_data' ][ 0 ] = array();
	$chart[ 'chart_data' ][ 1 ] = array();
	$chart[ 'chart_data' ][ 2 ] = array();
	$chart[ 'chart_data' ][ 3 ] = array();
	$chart[ 'chart_data' ][ 4 ] = array();
	$chart[ 'chart_data' ][ 5 ] = array();
	$chart[ 'chart_data' ][ 6 ] = array();

	$count = 0;
	foreach( $res_hits as $row_stats )
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
		$col = $col_mapping[$row_stats['referer_type']];
		$chart [ 'chart_data' ][$col][0] = $row_stats['hits'];
	}

	array_unshift( $chart[ 'chart_data' ][ 0 ], '' );
	array_unshift( $chart[ 'chart_data' ][ 1 ], 'Refering searches' );
	array_unshift( $chart[ 'chart_data' ][ 2 ], 'Referers' );
	array_unshift( $chart[ 'chart_data' ][ 3 ], 'Direct accesses' );	// Translations need to be UTF-8
	array_unshift( $chart[ 'chart_data' ][ 4 ], 'Self referred' );
	array_unshift( $chart[ 'chart_data' ][ 5 ], 'Special referrers' );
	array_unshift( $chart[ 'chart_data' ][ 6 ], 'Admin' );

	$chart[ 'canvas_bg' ] = array (
			'width'  => 780,
			'height' => 400,
			'color'  => 'efede0'
		);

	$chart[ 'chart_rect' ] = array (
			'x'      => 50,
			'y'      => 50,
			'width'  => 700,
			'height' => 250
		);

	$chart[ 'legend_rect' ] = array (
			'x'      => 50,
			'y'      => 365,
			'width'  => 700,
			'height' => 8,
			'margin' => 6
		);

	$chart[ 'draw_text' ] = array (
			array (
					'color'    => '9e9286',
					'alpha'    => 75,
					'font'     => "arial",
					'rotation' => 0,
					'bold'     => true,
					'size'     => 42,
					'x'        => 50,
					'y'        => 6,
					'width'    => 700,
					'height'   => 50,
					'text'     => 'Browser hits', // Needs UTF-8
					'h_align'  => "right",
					'v_align'  => "bottom" ),
			);

	$chart[ 'chart_bg' ] = array (
			'positive_color' => "ffffff",
			// 'negative_color'  =>  string,
			'positive_alpha' => 20,
			// 'negative_alpha'  =>  int
		);

	$chart [ 'legend_bg' ] = array (
			'bg_color'          =>  "ffffff",
			'bg_alpha'          =>  20,
			// 'border_color'      =>  "000000",
			// 'border_alpha'      =>  100,
			// 'border_thickness'  =>  1
		);

	$chart [ 'legend_label' ] = array(
			// 'layout'  =>  "horizontal",
			// 'font'    =>  string,
			// 'bold'    =>  boolean,
			'size'    =>  10,
			// 'color'   =>  string,
			// 'alpha'   =>  int
		);

	$chart[ 'chart_border' ] = array (
			'color'=>"000000",
			'top_thickness'=>1,
			'bottom_thickness'=>1,
			'left_thickness'=>1,
			'right_thickness'=>1
		);

	$chart[ 'chart_type' ] = 'stacked column';

	// $chart[ 'series_color' ] = array ( "4e627c", "c89341" );

	$chart[ 'series_gap' ] = array ( 'set_gap'=>0, 'bar_gap'=>0 );


	$chart[ 'axis_category' ] = array (
			'font'  =>"arial",
			'bold'  =>true,
			'size'  =>11,
			'color' =>'000000',
			'alpha' =>75,
			'orientation' => 'diagonal_up',
			// 'skip'=>2
		);

	$chart[ 'axis_value' ] = array (
			// 'font'   =>"arial",
			// 'bold'   =>true,
			'size'   => 11,
			'color'  => '000000',
			'alpha'  => 75,
			'steps'  => 4,
			'prefix' => "",
			'suffix' => "",
			'decimals'=> 0,
			'separator'=> "",
			'show_min'=> false );

	$chart [ 'chart_value' ] = array (
			// 'prefix'         =>  string,
			// 'suffix'         =>  " views",
			// 'decimals'       =>  int,
			// 'separator'      =>  string,
			'position'       =>  "cursor",
			'hide_zero'      =>  true,
			// 'as_percentage'  =>  boolean,
			'font'           =>  "arial",
			'bold'           =>  true,
			'size'           =>  20,
			'color'          =>  "ffffff",
			'alpha'          =>  75
		);

	echo '<div class="center">';
	DrawChart( $chart );
	echo '</div>';


	/*
	 * Table:
	 */
	$hits = array(
		'direct' => 0,
		'referer' => 0,
		'search' => 0,
		'self' => 0,
		'blacklist' => 0,
		'admin' => 0,
	);
	$hits_total = $hits;

	$last_date = 0;

	?>

	<table class="grouped" cellspacing="0">
		<tr>
			<th class="firstcol"><?php echo T_('Date') ?></th>
			<th><?php echo T_('Refering searches') ?></th>
			<th><?php echo T_('Referers') ?></th>
			<th><?php echo T_('Direct accesses') ?></th>
			<th><?php echo T_('Self referred') ?></th>
			<th><?php	echo T_('Special referrers') ?></th>
			<th><?php echo T_('Admin') ?></th>
			<th class="lastcol"><?php echo T_('Total') ?></th>
		</tr>
		<?php
		$count = 0;
		foreach( $res_hits as $row_stats )
		{
			$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );
			if( $last_date == 0 ) $last_date = $this_date;	// that'll be the first one
			if( $last_date != $this_date )
			{ // We just hit a new day, let's display the previous one:
				?>
				<tr class="<?php echo ( $count%2 == 1 ) ? 'odd' : 'even'; ?>">
					<td class="firstcol"><?php if( $current_User->check_perm( 'stats', 'edit' ) )
						{
							echo action_icon( T_('Prune hits for this date!'), 'delete', url_add_param( $admin_url, 'ctrl=stats&amp;action=prune&amp;date='.$last_date.'&amp;show=summary&amp;blog='.$blog ) );
						}
						echo date( locale_datefmt(), $last_date ) ?>
					</td>
					<td class="right"><?php echo $hits['search'] ?></td>
					<td class="right"><?php echo $hits['referer'] ?></td>
					<td class="right"><?php echo $hits['direct'] ?></td>
					<td class="right"><?php echo $hits['self'] ?></td>
					<td class="right"><?php echo $hits['blacklist'] ?></td>
					<td class="right"><?php echo $hits['admin'] ?></td>
					<td class="lastcol right"><?php echo array_sum($hits) ?></td>
				</tr>
				<?php
					$hits = array(
						'direct' => 0,
						'referer' => 0,
						'search' => 0,
						'self' => 0,
						'blacklist' => 0,
						'admin' => 0,
					);
					$last_date = $this_date;	// that'll be the next one
					$count ++;
			}

			// Increment hitcounter:
			$hits[$row_stats['referer_type']] = $row_stats['hits'];
			$hits_total[$row_stats['referer_type']] += $row_stats['hits'];
		}

		if( $last_date != 0 )
		{ // We had a day pending:
			?>
				<tr class="<?php echo ( $count%2 == 1 ) ? 'odd' : 'even'; ?>">
				<td class="firstcol"><?php if( $current_User->check_perm( 'stats', 'edit' ) )
					{
						echo action_icon( T_('Prune hits for this date!'), 'delete', url_add_param( $admin_url, 'ctrl=stats&amp;action=prune&amp;date='.$last_date.'&amp;show=summary&amp;blog='.$blog ) );
					}
					echo date( locale_datefmt(), $this_date ) ?>
				</td>
				<td class="right"><?php echo $hits['search'] ?></td>
				<td class="right"><?php echo $hits['referer'] ?></td>
				<td class="right"><?php echo $hits['direct'] ?></td>
				<td class="right"><?php echo $hits['self'] ?></td>
				<td class="right"><?php echo $hits['blacklist'] ?></td>
				<td class="right"><?php echo $hits['admin'] ?></td>
				<td class="lastcol right"><?php echo array_sum($hits) ?></td>
			</tr>
			<?php
		}

		// Total numbers:
		?>

		<tr class="total">
		<td class="firstcol"><?php echo T_('Total') ?></td>
		<td class="right"><?php echo $hits_total['search'] ?></td>
		<td class="right"><?php echo $hits_total['referer'] ?></td>
		<td class="right"><?php echo $hits_total['direct'] ?></td>
		<td class="right"><?php echo $hits_total['self'] ?></td>
		<td class="right"><?php echo $hits_total['blacklist'] ?></td>
		<td class="right"><?php echo $hits_total['admin'] ?></td>
		<td class="lastcol right"><?php echo array_sum($hits_total) ?></td>
		</tr>

	</table>
	<?php
}

/*
 * $Log$
 * Revision 1.4  2006/11/26 01:42:10  fplanque
 * doc
 *
 */
?>