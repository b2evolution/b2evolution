<?php
/**
 * This file implements the UI view for the syndication stats.
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

global $blog, $admin_url, $rsc_url;

echo '<h2>'.T_('XML hits summary').'</h2>';
echo '<p>'.sprintf( T_('These are hits from <a %s>XML readers</a>. This includes RSS &amp; Atom readers.'), ' href="?ctrl=stats&amp;tab=useragents&amp;agnt_rss=1&amp;blog='.$blog.'"' ).'</p>';
echo '<p>'.T_('Any user agent accessing the XML feeds will be flagged as an XML reader.').'</p>';
$sql = '
	SELECT COUNT(*) AS hits, YEAR(hit_datetime) AS year,
			   MONTH(hit_datetime) AS month, DAYOFMONTH(hit_datetime) AS day
		FROM T_hitlog INNER JOIN T_useragents ON hit_agnt_ID = agnt_ID
	 WHERE agnt_type = "rss"';
if( $blog > 0 )
{
	$sql .= ' AND hit_blog_ID = '.$blog;
}
$sql .= ' GROUP BY year, month, day
					ORDER BY year DESC, month DESC, day DESC';
$res_hits = $DB->get_results( $sql, ARRAY_A, 'Get rss summary' );


/*
 * Chart
 */
if( count($res_hits) )
{
	$last_date = 0;

	$chart[ 'chart_data' ][ 0 ] = array();
	$chart[ 'chart_data' ][ 1 ] = array();

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
		}
		$chart [ 'chart_data' ][1][0] = $row_stats['hits'];
	}

	array_unshift( $chart[ 'chart_data' ][ 0 ], '' );
	array_unshift( $chart[ 'chart_data' ][ 1 ], 'XML (RSS/Atom) hits' );	// Translations need to be UTF-8

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
					'text'     => 'XML hits', // Needs UTF-8
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

}

/*
 * $Log$
 * Revision 1.3  2006/11/24 18:27:26  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.2  2006/08/24 21:41:14  fplanque
 * enhanced stats
 *
 * Revision 1.1  2006/07/12 18:07:06  fplanque
 * splitted stats into different views
 *
 */
?>