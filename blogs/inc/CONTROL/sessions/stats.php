<?php
/**
 * This file implements the UI controller for browsing the (hitlog) statistics.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Vegar BERG GULDAL grants Francois PLANQUE the right to license
 * Vegar BERG GULDAL's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE
 * @author vegarg: Vegar BERG GULDAL
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * The Hitlist class
 */
require_once $model_path.'sessions/_hitlist.class.php';


/**
 * Return a formatted percentage (should probably go to _misc.funcs)
 */
function percentage( $hit_count, $hit_total, $decimals = 1, $dec_point = '.' )
{
	return number_format( $hit_count * 100 / $hit_total, $decimals, $dec_point, '' ).'&nbsp;%';
}


/**
 * Helper function for "Requested URI" column
 * @param integer Blog ID
 * @return string
 */
function stats_get_blog_baseurlroot( $hit_blog_ID )
{
	global $BlogCache;
	$tmp_Blog = & $BlogCache->get_by_ID( $hit_blog_ID );
	return $tmp_Blog->get('baseurlroot');
}


$AdminUI->set_path( 'stats', param( 'tab', 'string', 'summary', true ) );
$AdminUI->title = T_('View Stats for Blog:');

param( 'action', 'string' );
param( 'blog', 'integer', 0 );

$blogListButtons = '<a href="'.regenerate_url( array('blog','page'), "blog=0" ).'" class="'.(( 0 == $blog ) ? 'CurrentBlog' : 'OtherBlog').'">'.T_('None').'</a> ';
for( $curr_blog_ID = blog_list_start();
			$curr_blog_ID != false;
			$curr_blog_ID = blog_list_next() )
{
	$blogListButtons .= '<a href="'.regenerate_url( array('blog','page'), "blog=$curr_blog_ID" ).'" class="'.(( $curr_blog_ID == $blog ) ? 'CurrentBlog' : 'OtherBlog').'">'.blog_list_iteminfo('shortname',false).'</a> ';
}


// Check permission:
$current_User->check_perm( 'stats', 'view', true );


switch( $action )
{
	case 'changetype': // Change the type of a hit
		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		param( 'hit_ID', 'integer', true );      // Required!
		param( 'new_hit_type', 'string', true ); // Required!

		Hitlist::change_type( $hit_ID, $new_hit_type );
		$Messages->add( sprintf( T_('Changed hit #%d type to: %s.'), $hit_ID, $new_hit_type), 'success' );
		break;


	case 'delete': // DELETE A HIT
		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		param( 'hit_ID', 'integer', true ); // Required!

		if( Hitlist::delete( $hit_ID ) )
		{
			$Messages->add( sprintf( T_('Deleted hit #%d.'), $hit_ID ), 'success' );
		}
		else
		{
			$Messages->add( sprintf( T_('Could not delete hit #%d.'), $hit_ID ), 'note' );
		}
		break;


	case 'prune': // PRUNE hits for a certain date
		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		param( 'date', 'integer', true ); // Required!
		if( $r = Hitlist::prune( $date ) )
		{
			$Messages->add( sprintf( /* TRANS: %s is a date */ T_('Deleted %d hits for %s.'), $r, date( locale_datefmt(), $date) ), 'success' );
		}
		else
		{
			$Messages->add( sprintf( /* TRANS: %s is a date */ T_('No hits deleted for %s.'), date( locale_datefmt(), $date) ), 'note' );
		}
		break;
}


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();


switch( $AdminUI->get_path(1) )
{
	case 'summary':
		?>
		<h2><?php echo T_('Summary') ?>:</h2>

		<?php
		// fplanque>> I don't get it, it seems that GROUP BY on the referer type ENUM fails pathetically!!
		// Bug report: http://lists.mysql.com/bugs/36
		// Solution : CAST to string
		// TODO: I've also limited this to agnt_type "browser" here, according to the change for "referers" (Rev 1.6)
		//       -> an RSS service that sends a referer is not a real referer (though he should be listed in the robots list)! (blueyed)
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
					'direct' => 1,
					'referer' => 2,
					'search' => 3,
					'blacklist' => 4,
				);

			$chart[ 'chart_data' ][ 0 ] = array();
			$chart[ 'chart_data' ][ 1 ] = array();
			$chart[ 'chart_data' ][ 2 ] = array();
			$chart[ 'chart_data' ][ 3 ] = array();
			$chart[ 'chart_data' ][ 4 ] = array();

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
				}
				$col = $col_mapping[$row_stats['referer_type']];
				$chart [ 'chart_data' ][$col][0] = $row_stats['hits'];
			}

			array_unshift( $chart[ 'chart_data' ][ 0 ], '' );
			array_unshift( $chart[ 'chart_data' ][ 1 ], 'Direct Accesses' );	// Translations need to be UTF-8
			array_unshift( $chart[ 'chart_data' ][ 2 ], 'Referers' );
			array_unshift( $chart[ 'chart_data' ][ 3 ], 'Refering Searches' );
			array_unshift( $chart[ 'chart_data' ][ 4 ], 'Blacklisted' );

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
							'text'     => 'Access summary', // Needs UTF-8
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
				'blacklist' => 0,
			);
			$hits_total = array(
				'direct' => 0,
				'referer' => 0,
				'search' => 0,
				'blacklist' => 0,
			);

			$last_date = 0;
			?>
			<table class="grouped" cellspacing="0">
				<tr>
					<th class="firstcol"><?php echo T_('Date') ?></th>
					<th><?php echo T_('Direct Accesses') ?></th>
					<th><?php echo T_('Referers') ?></th>
					<th><?php echo T_('Refering Searches') ?></th>
					<th><?php
					// TODO: should be renamed for more clarity (because this is not Spam)
					echo T_('Blacklisted') ?></th>
					<th><?php echo T_('Total') ?></th>
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
						<tr <?php if( $count%2 == 1 ) echo 'class="odd"'; ?>>
							<td class="firstcol"><?php if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
								{
									echo action_icon( T_('Prune hits for this date!'), 'delete', url_add_param( $admin_url, 'ctrl=stats&amp;action=prune&amp;date='.$last_date.'&amp;show=summary&amp;blog='.$blog ) );
								}
								echo date( locale_datefmt(), $last_date ) ?>
							</td>
							<td class="right"><?php echo $hits['direct'] ?></td>
							<td class="right"><?php echo $hits['referer'] ?></td>
							<td class="right"><?php echo $hits['search'] ?></td>
							<td class="right"><?php echo $hits['blacklist'] ?></td>
							<td class="right"><?php echo array_sum($hits) ?></td>
						</tr>
						<?php
							$hits = array(
								'direct' => 0,
								'referer' => 0,
								'search' => 0,
								'blacklist' => 0,
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
					<tr <?php if( $count%2 == 1 ) echo 'class="odd"'; ?>>
						<td class="firstcol"><?php if( $current_User->check_perm( 'stats', 'edit' ) )
							{
								echo action_icon( T_('Prune hits for this date!'), 'delete', url_add_param( $admin_url, 'ctrl=stats&amp;action=prune&amp;date='.$last_date.'&amp;show=summary&amp;blog='.$blog ) );
							}
							echo date( locale_datefmt(), $this_date ) ?>
						</td>
						<td class="right"><?php echo $hits['direct'] ?></td>
						<td class="right"><?php echo $hits['referer'] ?></td>
						<td class="right"><?php echo $hits['search'] ?></td>
						<td class="right"><?php echo $hits['blacklist'] ?></td>
						<td class="right"><?php echo array_sum($hits) ?></td>
					</tr>
					<?php
				}

				// Total numbers:
				?>

				<tr class="totals">
				<td class="firstcol"></td>
				<td class="right"><?php echo $hits_total['direct'] ?></td>
				<td class="right"><?php echo $hits_total['referer'] ?></td>
				<td class="right"><?php echo $hits_total['search'] ?></td>
				<td class="right"><?php echo $hits_total['blacklist'] ?></td>
				<td class="right"><?php echo array_sum($hits_total) ?></td>
				</tr>

			</table>
			<?php
		}
		break;


		case 'referers':
			?>
			<h2><?php echo T_('Last referers') ?>:</h2>
			<p><?php echo T_('These are hits from external web pages refering to this blog') ?>.</p>
			<?php
			// Create result set:
			$Results = & new Results( "
					SELECT hit_ID, hit_datetime, hit_referer, dom_name, hit_blog_ID, hit_uri, hit_remote_addr, blog_shortname
					  FROM T_hitlog INNER JOIN T_basedomains
					    ON dom_ID = hit_referer_dom_ID INNER JOIN T_sessions
					    ON hit_sess_ID = sess_ID LEFT JOIN T_blogs
					    ON hit_blog_ID = blog_ID LEFT JOIN T_useragents
					    ON hit_agnt_ID = agnt_ID
					 WHERE hit_referer_type = 'referer'
					   AND agnt_type = 'browser'"
					 .( empty($blog) ? '' : "AND hit_blog_ID = $blog "), 'lstref_', 'D' );

			$Results->title = T_('Last referers');

			// datetime:
			$Results->cols[0] = array(
					'th' => T_('Date Time'),
					'order' => 'hit_datetime',
					'td' => '%mysql2localedatetime( \'$hit_datetime$\' )%',
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
				function referer_ban_link( $dom_name )
				{
					return '<a href="?ctrl=antispam&amp;action=ban&amp;keyword='.rawurlencode( get_ban_domain( $dom_name ) )
							.'" title="'.T_('Ban this domain!').'">'.get_icon('ban').'</a>';
				}
				$Results->cols[] = array(
						'th' => /* TRANS: Abbrev. for Spam */ T_('S'),
						'td' => '%referer_ban_link( #dom_name# )%',
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
					'td' => '<a href="%stats_get_blog_baseurlroot(#hit_blog_ID#)%$hit_uri$">$hit_uri$</a>',
				);

			// Remote address (IP):
			$Results->cols[] = array(
					'th' => '<span title="'.T_('Remote address').'">'.T_('IP').'</span>',
					'order' => 'hit_remote_addr',
					'td' => '% $GLOBALS[\'Plugins\']->get_trigger_event( \'DisplayIpAddress\', $tmp_params = array(\'format\'=>\'htmlbody\', \'data\'=>\'$hit_remote_addr$\') ) %',
				);


			// Display results:
			$Results->display();

			?>
			<h3><?php echo T_('Top referers') ?>:</h3>

			<?php
			// TODO: re-use $Results from above
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
						$chart [ 'chart_data' ][ 0 ][ $count ] = 'Others';
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

				$chart [ 'chart_value' ] = array (
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
							echo '<td>'.action_icon( T_('Ban this domain!'), 'ban', regenerate_url( 'action,keyword', 'action=ban&amp;keyword='.rawurlencode( get_ban_domain(stats_basedomain(false)) ) ) ).'</td>';
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
		break;


		case 'refsearches':
			?>
			<h2><?php echo T_('Last refering searches') ?>:</h2>
			<p><?php echo T_('These are hits from people who came to this blog system through a search engine. (Search engines must be listed in /conf/_stats.php)') ?></p>
			<?php
			// Create result set:
			$Results = & new Results( "
					SELECT hit_ID, hit_datetime, hit_referer, dom_name, hit_blog_ID, hit_uri, hit_remote_addr, blog_shortname
					  FROM T_hitlog INNER JOIN T_basedomains
					    ON dom_ID = hit_referer_dom_ID LEFT JOIN T_blogs
					    ON hit_blog_ID = blog_ID
					 WHERE hit_referer_type = 'search' "
					.( empty($blog) ? '' : "AND hit_blog_ID = $blog " ), 'lstsrch', 'D' );

			$Results->title = T_('Last refering searches');

			// datetime:
			$Results->cols[0] = array(
					'th' => T_('Date Time'),
					'order' => 'hit_datetime',
					'td' => '%mysql2localedatetime( \'$hit_datetime$\' )%',
				);

			// Referer:
			$Results->cols[1] = array(
					'th' => T_('Referer'),
					'order' => 'dom_name',
				);
			if( $current_User->check_perm( 'stats', 'edit' ) )
			{
				$Results->cols[1]['td'] = '<a href="%regenerate_url( \'action\', \'action=delete&amp;hit_ID=$hit_ID$\')%" title="'
						.T_('Delete this hit!').'">'.get_icon('delete').'</a> '
						.'<a href="$hit_referer$">$dom_name$</a>';
			}
			else
			{
				$Results->cols[1]['td'] = '<a href="$hit_referer$">$dom_name$</a>';
			}

			// Keywords:
			$Results->cols[] = array(
					'th' => T_('Search keywords'),
					'td' => '%stats_search_keywords( #hit_referer# )%',
				);

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
					'td' => '<a href="%stats_get_blog_baseurlroot(#hit_blog_ID#)%$hit_uri$">$hit_uri$</a>',
				);

			// Remote address (IP):
			$Results->cols[] = array(
					'th' => '<span title="'.T_('Remote address').'">'.T_('IP').'</span>',
					'order' => 'hit_remote_addr',
					'td' => '% $GLOBALS[\'Plugins\']->get_trigger_event( \'DisplayIpAddress\', $tmp_params = array(\'format\'=>\'htmlbody\', \'data\'=>\'$hit_remote_addr$\') ) %',
				);

			// Display results:
			$Results->display();


			// TOP REFERING SEARCH ENGINES
			?>

			<h3><?php echo T_('Top refering search engines') ?>:</h3>

			<?php
			refererList(20,'global',0,0,"'search'",'dom_name',$blog,true);
			if( count( $res_stats ) )
			{
				?>
				<table class="grouped" cellspacing="0">
					<?php
					$count = 0;
					foreach( $res_stats as $row_stats )
					{
						?>
						<tr <?php if( $count%2 == 1 ) echo 'class="odd"'; ?>>
							<td class="firstcol"><a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></td>
							<td class="right"><?php stats_hit_count() ?></td>
							<td class="right"><?php stats_hit_percent() ?></td>
						</tr>
					<?php
					$count++;
					}
					?>
				</table>
			<?php
			}


			// TOP INDEXING ROBOTS
			?>
			<h3><?php echo T_('Top Indexing Robots') ?>:</h3>
			<p><?php echo T_('These are hits from automated robots like search engines\' indexing robots. (Robots must be listed in /conf/_stats.php)') ?></p>
			<?php
			// Create result set:
			$Results = & new Results( "
					SELECT COUNT(*) AS hit_count, hit_referer, agnt_signature, hit_blog_ID, blog_shortname
					  FROM T_hitlog INNER JOIN T_useragents
					    ON hit_agnt_ID = agnt_ID LEFT JOIN T_blogs
					    ON hit_blog_ID = blog_ID
					 WHERE agnt_type = 'robot' "
					.( empty($blog) ? '' : "AND hit_blog_ID = $blog " ).'
					 GROUP BY agnt_signature', 'topidx', 'D', 20 );
					 #'SELECT COUNT(*) FROM T_hitlog );

			$total_hit_count = $DB->get_var( "
					SELECT COUNT(*) as hit_count
					  FROM T_hitlog INNER JOIN T_useragents
					    ON hit_agnt_ID = agnt_ID LEFT JOIN T_blogs
					    ON hit_blog_ID = blog_ID
					 WHERE agnt_type = 'robot' "
					.( empty($blog) ? '' : "AND hit_blog_ID = $blog " ) );

			$Results->title = T_('Top Indexing Robots');

			/**
			 * Helper function to translate agnt_signature to a "human-friendly" version from {@link $user_agents}.
			 * @return string
			 */
			function translate_user_agent( $agnt_signature )
			{
				global $user_agents;

				foreach ($user_agents as $curr_user_agent)
				{
					if (stristr($agnt_signature, $curr_user_agent[1]))
					{
						return '<span title="'.htmlspecialchars($agnt_signature).'">'.htmlspecialchars($curr_user_agent[2]).'</span>';
					}
				}

				return htmlspecialchars($agnt_signature);
			}

			// User agent:
			$Results->cols[] = array(
					'th' => T_('Robot'),
					'order' => 'hit_referer',
					'td' =>
						// If hit_referer is not empty, start a link
						'¤( strlen(trim(\'$hit_referer$\')) ? \'<a href="$hit_referer$">\' : \'\' )¤'
						.'%translate_user_agent(\'$agnt_signature$\')%'
						.'¤( strlen(trim(\'$hit_referer$\')) ? \'</a>\' : \'\' )¤',
				);

			// Hit count:
			$Results->cols[] = array(
					'th' => T_('Hit count'),
					'order' => 'hit_count',
					'td' => '$hit_count$',
				);

			// Hit %
			$Results->cols[] = array(
					'th' => T_('Hit %'),
					'order' => 'hit_count',
					'td' => '%percentage( #hit_count#, '.$total_hit_count.' )%',
				);

			// Target Blog:
			if( empty($blog) )
			{
				$Results->cols[] = array(
						'th' => T_('Target Blog'),
						'order' => 'hit_blog_ID',
						'td' => '$blog_shortname$',
					);
			}

			// Display results:
			$Results->display();

			break;


		case 'syndication':
			?>
			<h2><?php echo T_('Top Aggregators') ?>:</h2>
			<p><?php echo T_('These are hits from RSS news aggregators. (Aggregators get detected by accessing the feeds)') ?></p>
			<?php
			$total_hit_count = $DB->get_var( "
				SELECT COUNT(*) AS hit_count
				  FROM T_useragents INNER JOIN T_hitlog
				    ON agnt_ID = hit_agnt_ID
				 WHERE agnt_type = 'rss' "
					.( empty($blog) ? '' : "AND hit_blog_ID = $blog " ), 0, 0, 'Get total hit count' );


			echo '<p>'.T_('Total RSS hits').': '.$total_hit_count.'</p>';

			// Create result set:
			$Results = & new Results( "
				SELECT agnt_signature, COUNT(*) AS hit_count
				  FROM T_useragents INNER JOIN T_hitlog
				    ON agnt_ID = hit_agnt_ID
				 WHERE agnt_type = 'rss' "
					.( empty($blog) ? '' : "AND hit_blog_ID = $blog " ).'
				 GROUP BY agnt_ID ', 'topagg_', '--D' );

			$Results->title = T_('Top Aggregators');

			$Results->cols[] = array(
					'th' => T_('Agent signature'),
					'order' => 'agnt_signature',
					'td' => '²agnt_signature²',
				);

			$Results->cols[] = array(
					'th' => T_('Hit count'),
					'order' => 'hit_count',
					'td' => '$hit_count$',
				);

			$Results->cols[] = array(
					'th' => T_('Hit %'),
					'order' => 'hit_count',
					'td' => '%percentage( #hit_count#, '.$total_hit_count.' )%',
				);

			// Display results:
			$Results->display();

			break;


		case 'other':
			?>
			<h2><?php echo T_('Last direct accesses') ?>:</h2>
			<p><?php echo T_('These are hits from people who came to this blog system by direct access (either by typing the URL directly, or using a bookmark. Invalid (too short) referers are also listed here.)') ?></p>
			<?php
			// Create result set:
			$Results = & new Results( "
				SELECT hit_ID, hit_datetime, hit_blog_ID, hit_uri, hit_remote_addr, blog_shortname
				  FROM T_hitlog INNER JOIN T_useragents
				    ON hit_agnt_ID = agnt_ID
				  LEFT JOIN T_blogs ON hit_blog_ID = blog_ID
				 WHERE hit_referer_type = 'direct'
				   AND agnt_type = 'browser'"
				  .( empty($blog) ? '' : "AND hit_blog_ID = $blog "), 'lstref_', 'D' );

			$Results->title = T_('Last direct accesses');

			// datetime:
			$Results->cols[] = array(
					'th' => T_('Date Time'),
					'order' => 'hit_datetime',
					'td' => '%mysql2localedatetime( \'$hit_datetime$\' )%',
				);

			// Referer:
			if( $current_User->check_perm( 'stats', 'edit' ) )
			{
				$Results->cols[] = array(
						'th' => /* TRANS: Abbrev. for Delete (stats) */ T_('Del'),
						'td' => ' <a href="%regenerate_url( \'action\', \'action=delete&amp;hit_ID=$hit_ID$\')%" title="'
						       .T_('Delete this hit!').'">'.get_icon('delete').'</a>',
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
					'td' => '<a href="%stats_get_blog_baseurlroot(#hit_blog_ID#)%$hit_uri$">$hit_uri$</a>',
				);

			// Remote address (IP):
			$Results->cols[] = array(
					'th' => '<span title="'.T_('Remote address').'">'.T_('IP').'</span>',
					'order' => 'hit_remote_addr',
					'td' => '% $GLOBALS[\'Plugins\']->get_trigger_event( \'DisplayIpAddress\', $tmp_params = array(\'format\'=>\'htmlbody\', \'data\'=>\'$hit_remote_addr$\') ) %',
				);

			// Display results:
			$Results->display();

			break;


		case 'useragents':
			?>
			<h2><?php echo T_('Top User Agents') ?>:</h2>
			<?php
			$total_hit_count = $DB->get_var( "
				SELECT COUNT(*) AS hit_count
				  FROM T_useragents INNER JOIN T_hitlog
				    ON agnt_ID = hit_agnt_ID
				 WHERE agnt_type <> 'rss' "
				  .( empty($blog) ? '' : "AND hit_blog_ID = $blog " ), 0, 0, 'Get total hit count' );


			echo '<p>'.T_('Total hits').': '.$total_hit_count.'</p>';

			// Create result set:
			$Results = & new Results( "
				SELECT agnt_signature, COUNT(*) AS hit_count
				  FROM T_useragents INNER JOIN T_hitlog
				    ON agnt_ID = hit_agnt_ID
				 WHERE agnt_type <> 'rss' "
				  .( empty($blog) ? '' : "AND hit_blog_ID = $blog " ).'
				 GROUP BY agnt_ID ', 'topua_', '--D' );

			$Results->title = T_('Top User Agents');

			$Results->cols[] = array(
									'th' => T_('Agent signature'),
									'order' => 'agnt_signature',
									'td' => '²agnt_signature²',
								);

			$Results->cols[] = array(
									'th' => T_('Hit count'),
									'order' => 'hit_count',
									'td' => '$hit_count$',
								);

			$Results->cols[] = array(
									'th' => T_('Hit %'),
									'order' => 'hit_count',
									'td' => '%percentage( #hit_count#, '.$total_hit_count.' )%',
								);

			// Display results:
			$Results->display();

			break;
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.11  2006/04/27 20:10:34  fplanque
 * changed banning of domains. Suggest a prefix by default.
 *
 * Revision 1.10  2006/04/25 00:19:25  blueyed
 * Also only count "browser" hits as referers in summary; added row with total numbers
 *
 * Revision 1.9  2006/04/20 19:14:03  blueyed
 * Link "Requested URI" columns to blog's baseurlroot+URI
 *
 * Revision 1.8  2006/04/20 17:59:01  blueyed
 * Removed "spam" from hit_referer_type (DB) and summary stats
 *
 * Revision 1.7  2006/04/19 20:03:04  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.6  2006/04/19 17:20:07  blueyed
 * Prefix "ban" domains with "://"; do only count browser type hits as referer (not "rss"!); Whitespace!
 *
 * Revision 1.5  2006/03/17 20:48:16  blueyed
 * Do not restrict to "stub" type blogs
 *
 * Revision 1.4  2006/03/12 23:08:56  fplanque
 * doc cleanup
 *
 * Revision 1.3  2006/03/02 20:05:29  blueyed
 * Fixed/polished stats (linking T_useragents to T_hitlog, not T_sessions again). I've done this the other way around before, but it wasn't my idea.. :p
 *
 * Revision 1.2  2006/03/01 22:17:00  blueyed
 * Fixed table title
 *
 * Revision 1.1  2006/02/23 21:11:56  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.12  2005/12/12 19:21:20  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.11  2005/12/03 12:35:02  blueyed
 * Fix displaying of Message when changing hit type to search. Closes: http://dev.b2evolution.net/todo.php/2005/12/02/changin_hit_type_to_search
 *
 * Revision 1.10  2005/11/23 23:14:50  blueyed
 * minor (translation)
 *
 * Revision 1.9  2005/11/05 01:53:53  blueyed
 * Linked useragent to a session rather than a hit;
 * SQL: moved T_hitlog.hit_agnt_ID to T_sessions.sess_agnt_ID
 *
 * Revision 1.8  2005/10/31 05:51:05  blueyed
 * Use rawurlencode() instead of urlencode()
 *
 * Revision 1.7  2005/10/28 20:08:46  blueyed
 * Normalized AdminUI
 *
 * Revision 1.6  2005/10/14 21:00:08  fplanque
 * Stats & antispam have obviously been modified with ZERO testing.
 * Fixed a sh**load of bugs...
 *
 */
?>