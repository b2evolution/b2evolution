<?php
/**
 * This file implements the UI view for the referer stats.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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

?>
<h2><?php echo T_('Refered browser hits') ?>:</h2>
<p><?php echo T_('These are browser hits from external web pages refering to this blog') ?>.</p>
<?php
// Create result set:
$Results = & new Results( "
		 SELECT hit_ID, hit_datetime, hit_referer, dom_name, hit_blog_ID, hit_uri, hit_remote_addr, blog_shortname
			 FROM T_hitlog INNER JOIN T_basedomains ON dom_ID = hit_referer_dom_ID
					  INNER JOIN T_sessions ON hit_sess_ID = sess_ID
					  INNER JOIN T_useragents ON hit_agnt_ID = agnt_ID
					  LEFT JOIN T_blogs ON hit_blog_ID = blog_ID
		  WHERE hit_referer_type = 'referer'
			 			AND agnt_type = 'browser'"
		 .( empty($blog) ? '' : "AND hit_blog_ID = $blog "), 'lstref_', 'D' );

$Results->title = T_('Refered browser hits');

// datetime:
$Results->cols[0] = array(
		'th' => T_('Date Time'),
		'order' => 'hit_datetime',
		'td_class' => 'timestamp',
		'td' => '%mysql2localedatetime_spans( \'$hit_datetime$\' )%',
	);

// Referer:
$Results->cols[1] = array(
		'th' => T_('Referer'),
		'order' => 'dom_name',
	);
if( $current_User->check_perm( 'stats', 'edit' ) )
{
	$Results->cols[1]['td'] = '<a href="%regenerate_url( \'action\', \'action=delete&amp;hit_ID=$hit_ID$\')%" title="'
			.T_('Delete this hit!').'">'.get_icon( 'delete' ).'</a> '

			.'<a href="%regenerate_url( \'action\', \'action=changetype&amp;new_hit_type=search&amp;hit_ID=$hit_ID$\')%" title="'
			.T_('Log as a search instead')
			.'"><img src="'.$rsc_url.'icons/magnifier.png" width="14" height="13" class="middle" alt="'
			./* TRANS: Abbrev. for "move to searches" (stats) */ T_('-&gt;S')
			.'" title="'.T_('Log as a search instead').'" /></a> '

			.'<a href="$hit_referer$">$dom_name$</a>';
}
else
{
	$Results->cols[1]['td'] = '<a href="$hit_referer$">$dom_name$</a>';
}

// Antispam:
if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
{
	/**
	 * @uses get_ban_domain()
	 * @param string URL
	 * @return string Link to ban the URL
	 */
	function referer_ban_link( $uri )
	{
		return '<a href="?ctrl=antispam&amp;action=ban&amp;keyword='.rawurlencode( get_ban_domain( $uri ) )
				.'" title="'.T_('Ban this domain!').'">'.get_icon('ban').'</a>';
	}
	$Results->cols[] = array(
			'th' => /* TRANS: Abbrev. for Spam */ T_('S'),
			'td' => '%referer_ban_link( #hit_referer# )%', // we use hit_referer, because unlike dom_name it includes more subdomains, especially "www."
		);
}

// Target Blog:
if( empty($blog) )
{
	$Results->cols[] = array(
			'th' => T_('Target Blog'),
			'order' => 'hit_blog_ID',
			'td' => '$blog_shortname$',
		);
}

// Requested URI (linked to blog's baseurlroot+URI):
$Results->cols[] = array(
		'th' => T_('Requested URI'),
		'order' => 'hit_uri',
		'td' => '%stats_format_req_URI( #hit_blog_ID#, #hit_uri# )%',
	);

// Remote address (IP):
$Results->cols[] = array(
		'th' => '<span title="'.T_('Remote address').'">'.T_('IP').'</span>',
		'order' => 'hit_remote_addr',
		'td' => '% $GLOBALS[\'Plugins\']->get_trigger_event( \'FilterIpAddress\', $tmp_params = array(\'format\'=>\'htmlbody\', \'data\'=>\'$hit_remote_addr$\') ) %',
	);


// Display results:
$Results->display();

?>
<h3><?php echo T_('Top referers') ?>:</h3>

<?php
// TODO: re-use $Results from above
global $res_stats, $row_stats;
refererList( 30, 'global', 0, 0, "'referer'", 'dom_name', $blog, true );
if( count( $res_stats ) )
{
	$chart [ 'chart_data' ][ 0 ][ 0 ] = "";
	$chart [ 'chart_data' ][ 1 ][ 0 ] = 'Top referers'; // Needs UTF-8

	$count = 0;
	foreach( $res_stats as $row_stats )
	{
		if( $count < 8 )
		{
			$count++;
			$chart [ 'chart_data' ][ 0 ][ $count ] = stats_basedomain( false );
		}
		else
		{
			$chart [ 'chart_data' ][ 0 ][ $count ] = 'Others'; // Needs UTF-8
		}
		$chart [ 'chart_data' ][ 1 ][ $count ] = stats_hit_count( false );
	} // End stat loop

	$chart[ 'canvas_bg' ] = array (
			'width'  => 780,
			'height' => 350,
			'color'  => 'efede0'
		);

	$chart[ 'chart_rect' ] = array (
			'x'      => 60,
			'y'      => 50,
			'width'  => 250,
			'height' => 250
		);

	$chart[ 'legend_rect' ] = array (
			'x'      => 400,
			'y'      => 70,
			'width'  => 340,
			'height' => 230,
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
					'text'     => 'Top referers',
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
			'size'    =>  15,
			// 'color'   =>  string,
			// 'alpha'   =>  int
		);

	/*$chart[ 'chart_border' ] = array (
				'color'=>"000000",
				'top_thickness'=>1,
				'bottom_thickness'=>1,
				'left_thickness'=>1,
				'right_thickness'=>1
		);*/

	$chart[ 'chart_type' ] = 'pie';

	// $chart[ 'series_color' ] = array ( "4e627c", "c89341" );

	$chart [ 'series_explode' ] =  array ( 15 );

	/*$chart[ 'axis_category' ] = array (
			'font'  =>"arial",
			'bold'  =>true,
			'size'  =>11,
			'color' =>'000000',
			'alpha' =>75,
			'orientation' => 'diagonal_up',
			// 'skip'=>2
		 );*/

	/* $chart[ 'axis_value' ] = array (	// 'font'   =>"arial",
			// 'bold'   =>true,
			'size'   => 11,
			'color'  => '000000',
			'alpha'  => 75,
			'steps'  => 4,
			'prefix' => "",
			'suffix' => "",
			'decimals'=> 0,
			'separator'=> "",
			'show_min'=> false ); */

	$chart[ 'chart_value' ] = array (
			// 'prefix'         =>  string,
			// 'suffix'         =>  " views",
			// 'decimals'       =>  int,
			// 'separator'      =>  string,
			'position'       =>  "outside",
			'hide_zero'      =>  true,
			'as_percentage'  =>  true,
			'font'           =>  "arial",
			'bold'           =>  true,
			'size'           =>  20,
			'color'          =>  "000000",
			'alpha'          =>  75
		);

	//pre_dump( $chart );
	echo '<div class="center">';
	DrawChart( $chart );
	echo '</div>';

?>
<table class="grouped" cellspacing="0">
	<?php
	$count = 0;
	foreach( $res_stats as $row_stats )
	{
		?>
		<tr <?php if( $count%2 == 1 ) echo 'class="odd"'; ?>>
			<td class="firstcol"><a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></td>
			<?php
			if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
			{ // user can ban:
				echo '<td>'.action_icon( T_('Ban this domain!'), 'ban', regenerate_url( 'ctrl,action,keyword', 'ctrl=antispam&amp;action=ban&amp;keyword='.rawurlencode( get_ban_domain($row_stats['hit_referer']) ) ) ).'</td>'; // we use hit_referer, because unlike dom_name it includes subdomains (especially 'www.')
			}
			?>
			<td class="right"><?php stats_hit_count() ?></td>
			<td class="right"><?php stats_hit_percent() ?></td>
		</tr>
		<?php
		$count++;
	}
	?>
</table>
<?php } ?>
<p><?php echo T_('Total referers') ?>: <?php stats_total_hit_count() ?></p>

<?php

/*
 * $Log$
 * Revision 1.2  2006/08/24 21:41:13  fplanque
 * enhanced stats
 *
 * Revision 1.1  2006/07/12 18:07:06  fplanque
 * splitted stats into different views
 *
 */
?>