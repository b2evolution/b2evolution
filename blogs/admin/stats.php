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
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * In addition, as a special exception, the copyright holders gives permission to link
 * the code of this program with the PHP/SWF Charts library by maani.us (or with
 * modified versions of this library that use the same license as PHP/SWF Charts library
 * by maani.us), and distribute linked combinations including the two. You must obey the
 * GNU General Public License in all respects for all of the code used other than the
 * PHP/SWF Charts library by maani.us. If you modify this file, you may extend this
 * exception to your version of the file, but you are not obligated to do so. If you do
 * not wish to do so, delete this exception statement from your version.
 * }}
 *
 * {@internal
 * Vegar BERG GULDAL grants Francois PLANQUE the right to license
 * Vegar BERG GULDAL's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: François PLANQUE
 * @author vegarg: Vegar BERG GULDAL
 *
 * @version $Id$
 */

/**
 * Includes:
 */
require_once( dirname(__FILE__).'/_header.php' );

/**
 * The Hitlist class
 */
require_once( dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_hitlist.class.php' );


/**
 * Return a formatted percentage (should probably go to _misc.funcs)
 */
function percentage( $hit_count, $hit_total, $decimals = 1, $dec_point = '.' )
{
	return number_format( $hit_count * 100 / $hit_total, $decimals, $dec_point, '' ).'&nbsp;%';
}


$AdminUI->setPath( 'stats', param( 'tab', 'string', 'summary', true ) );
$AdminUI->title = T_('View Stats for Blog:');

param( 'action', 'string' );
param( 'blog', 'integer', 0 );

$blogListButtons = '<a href="'.regenerate_url( array('blog','page'), "blog=0" ).'" class="'.(( 0 == $blog ) ? 'CurrentBlog' : 'OtherBlog').'">'.T_('None').'</a> ';
for( $curr_blog_ID = blog_list_start('stub');
			$curr_blog_ID != false;
			$curr_blog_ID = blog_list_next('stub') )
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
		?>
		<div class="panelinfo">
			<p><?php printf( T_('Changing hit #%d type to: %s'), $hit_ID, $new_hit_type) ?></p>
			<?php
			Hitlist::change_type( $hit_ID, $new_hit_type );
			?>
		</div>
		<?php
		break;


	case 'delete': // DELETE A HIT
		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		param( 'hit_ID', 'integer', true ); // Required!

		if( Hitlist::delete( $hit_ID ) )
		{
			$Messages->add( sprintf( T_('Deleted hit #%d...'), $hit_ID ), 'note' );
		}
		else
		{
			$Messages->add( sprintf( T_('Could not delete hit #%d...'), $hit_ID ), 'note' );
		}
		break;


	case 'prune': // PRUNE hits for a certain date
		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		param( 'date', 'integer', true ); // Required!
		if( $r = Hitlist::prune( $date ) )
		{
			$Messages->add( sprintf( T_('Deleted %d hits for %s.'), $r, date( locale_datefmt(), $date) ), 'note' );
		}
		else
		{
			$Messages->add( sprintf( T_('No hits deleted for %s.'), date( locale_datefmt(), $date) ), 'note' );
		}
		break;
}


require( dirname(__FILE__).'/_menutop.php' );


// Begin payload block:
$AdminUI->dispPayloadBegin();


switch( $AdminUI->getPath(1) )
{
	case 'summary':
		?>
		<h2><?php echo T_('Summary') ?>:</h2>

		<?php
		// fplanque>> I don't get it, it seems that GROUP BY on the referer type ENUM fails pathetically!!
		// Bug report: http://lists.mysql.com/bugs/36
		// Solution : CAST to string
		$sql = 'SELECT COUNT(*) AS hits, CONCAT(hit_referer_type) AS referer_type, YEAR(hit_datetime) AS year,
										MONTH(hit_datetime) AS month, DAYOFMONTH(hit_datetime) AS day
							FROM T_hitlog ';
		if( $blog > 0 )
		{
			$sql .= ' WHERE hit_blog_ID = '.$blog;
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
														'spam' => 5,
													);

			$chart[ 'chart_data' ][ 0 ] = array();
			$chart[ 'chart_data' ][ 1 ] = array();
			$chart[ 'chart_data' ][ 2 ] = array();
			$chart[ 'chart_data' ][ 3 ] = array();
			$chart[ 'chart_data' ][ 4 ] = array();
			$chart[ 'chart_data' ][ 5 ] = array();
													
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
				}
				$col = $col_mapping[$row_stats['referer_type']];
				$chart [ 'chart_data' ][$col][0] = $row_stats['hits'];
			}

			array_unshift( $chart[ 'chart_data' ][ 0 ], '' );
			array_unshift( $chart[ 'chart_data' ][ 1 ], 'Direct Accesses' );	// Translations need to be UTF-8
			array_unshift( $chart[ 'chart_data' ][ 2 ], 'Referers' );
			array_unshift( $chart[ 'chart_data' ][ 3 ], 'Refering Searches' );
			array_unshift( $chart[ 'chart_data' ][ 4 ], 'Blacklisted' );
			array_unshift( $chart[ 'chart_data' ][ 5 ], 'Spam' );

			$chart[ 'canvas_bg' ] = array (		'width'  => 780,
																				'height' => 400,
																				'color'  => 'efede0'
																		);

			$chart[ 'chart_rect' ] = array (	'x'      => 50,
																				'y'      => 50,
																				'width'  => 700,
																				'height' => 250
																		);

			$chart[ 'legend_rect' ] = array ( 'x'      => 50,
																				'y'      => 365,
																				'width'  => 700,
																				'height' => 8,
																				'margin' => 6
																		);

			$chart[ 'draw_text' ] = array (
																			array ( 'color'    => '9e9286',
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
																							'v_align'  => "bottom" )
																			);

			$chart[ 'chart_bg' ] = array (		'positive_color' => "ffffff",
																				// 'negative_color'  =>  string,
																				'positive_alpha' => 20,
																				// 'negative_alpha'  =>  int
																		);

			$chart [ 'legend_bg' ] = array (  'bg_color'          =>  "ffffff",
																				'bg_alpha'          =>  20,
																				// 'border_color'      =>  "000000",
																				// 'border_alpha'      =>  100,
																				// 'border_thickness'  =>  1
																			);

			$chart [ 'legend_label' ] = array(// 'layout'  =>  "horizontal",
																				// 'font'    =>  string,
																				// 'bold'    =>  boolean,
																				'size'    =>  10,
																				// 'color'   =>  string,
																				// 'alpha'   =>  int
																				);

			$chart[ 'chart_border' ] = array ('color'=>"000000",
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

			$chart[ 'axis_value' ] = array (	// 'font'   =>"arial",
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
			$hits = array();
			$hits['direct'] = 0;
			$hits['referer'] = 0;
			$hits['search'] = 0;
			$hits['blacklist'] = 0;
			$hits['spam'] = 0;
			$last_date = 0;
			?>
			<table class="grouped" cellspacing="0">
				<tr>
					<th class="firstcol"><?php echo T_('Date') ?></th>
					<th><?php echo T_('Direct Accesses') ?></th>
					<th><?php echo T_('Referers') ?></th>
					<th><?php echo T_('Refering Searches') ?></th>
					<th><?php echo T_('Blacklisted') ?></th>
					<th><?php echo T_('Spam') ?></th>
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
								{ ?>
									<a href="stats.php?action=prune&amp;date=<?php echo $last_date ?>&amp;show=summary&amp;blog=<?php echo $blog ?>" title="<?php echo T_('Prune this date!') ?>"><img src="img/xross.gif" width="13" height="13" class="middle" alt="<?php echo /* TRANS: Abbrev. for Prune (stats) */ T_('Prune') ?>"  title="<?php echo T_('Prune hits for this date!') ?>" /></a>
								<?php
								}
								echo date( locale_datefmt(), $last_date ) ?>
							</td>
							<td class="right"><?php echo $hits['direct'] ?></td>
							<td class="right"><?php echo $hits['referer'] ?></td>
							<td class="right"><?php echo $hits['search'] ?></td>
							<td class="right"><?php echo $hits['blacklist'] ?></td>
							<td class="right"><?php echo $hits['spam'] ?></td>
							<td class="right"><?php echo array_sum($hits) ?></td>
						</tr>
						<?php
							$hits['direct'] = 0;
							$hits['referer'] = 0;
							$hits['search'] = 0;
							$hits['blacklist'] = 0;
							$hits['spam'] = 0;
							$last_date = $this_date;	// that'll be the next one
							$count ++;
					}
					$hits[$row_stats['referer_type']] = $row_stats['hits'];
				}

				if( $last_date != 0 )
				{ // We had a day pending:
					?>
					<tr <?php if( $count%2 == 1 ) echo 'class="odd"'; ?>>
						<td class="firstcol"><?php if( $current_User->check_perm( 'stats', 'edit' ) )
							{ ?>
							<a href="stats.php?action=prune&amp;date=<?php echo $this_date ?>&amp;show=summary&amp;blog=<?php echo $blog ?>" title="<?php echo T_('Prune hits for this date!') ?>"><img src="img/xross.gif" width="13" height="13" class="middle" alt="<?php echo /* TRANS: Abbrev. for Prune (stats) */ T_('Prune') ?>"  title="<?php echo T_('Prune hits for this date!') ?>" /></a>
							<?php
							}
							echo date( locale_datefmt(), $this_date ) ?>
						</td>
						<td class="right"><?php echo $hits['direct'] ?></td>
						<td class="right"><?php echo $hits['referer'] ?></td>
						<td class="right"><?php echo $hits['search'] ?></td>
						<td class="right"><?php echo $hits['blacklist'] ?></td>
						<td class="right"><?php echo $hits['spam'] ?></td>
						<td class="right"><?php echo array_sum($hits) ?></td>
					</tr>
					<?php
				}
				?>
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
			$Results = & new Results( "SELECT hit_ID, hit_datetime, hit_referer,
																			dom_name, hit_blog_ID, hit_uri, blog_shortname
																FROM T_hitlog INNER JOIN T_basedomains ON dom_ID = hit_referer_dom_ID
																			LEFT JOIN T_blogs ON hit_blog_ID = blog_ID
																WHERE hit_referer_type = 'referer' "
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
				$Results->cols[1]['td'] = '<a href="%regenerate_url( \'action\', \'action=delete&amp;hit_ID=$hit_ID$\')%" title="'.
																	T_('Delete this hit!').
																	'"><img src="img/xross.gif" width="13" height="13" class="middle" alt="'.
																	/* TRANS: Abbrev. for Delete (stats) */ T_('Del').
																	'" title="'.T_('Delete this hit!').'" /></a> '.

																	'<a href="%regenerate_url( \'action\', \'action=changetype&amp;new_hit_type=search&amp;hit_ID=$hit_ID$\')%" title="'.
																	T_('Log as a search instead').
																	'"><img src="img/magnifier.png" width="14" height="13" class="middle" alt="'.
																	/* TRANS: Abbrev. for "move to searches" (stats) */ T_('-&gt;S').
																	'" title="'.T_('Log as a search instead').'" /></a> '.

																	'<a href="$hit_referer$">$dom_name$</a>';
			}
			else
			{
				$Results->cols[1]['td'] = '<a href="$hit_referer$">$dom_name$</a>';
			}

			// Antispam:
			if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
			{
				$Results->cols[] = array(
										'th' => /* TRANS: Abbrev. for Spam */ T_('S'),
										'td' => '<a href="antispam.php?action=ban&amp;keyword=%urlencode( \'$dom_name$\' )%" title="'
											.T_('Ban this domain!').'"><img src="img/noicon.gif" class="middle" alt="'
											./* TRANS: Abbrev. */ T_('Ban').'" title="'.T_('Ban this domain!').'" /></a>',
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

			// Requested URI:
			$Results->cols[] = array(
									'th' => T_('Requested URI'),
									'order' => 'hit_uri',
									'td' => '<a href="$hit_uri$">$hit_uri$</a>',
								);


			// Display results:
			$Results->display();

			?>
			<h3><?php echo T_('Top referers') ?>:</h3>

			<?php
			refererList( 30, 'global', 0, 0, "'no'", 'dom_name', $blog, true );
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

				$chart[ 'canvas_bg' ] = array (		'width'  => 780,
																					'height' => 350,
																					'color'  => 'efede0'
																			);

				$chart[ 'chart_rect' ] = array (	'x'      => 60,
																					'y'      => 50,
																					'width'  => 250,
																					'height' => 250
																			);

				$chart[ 'legend_rect' ] = array ( 'x'      => 400,
																					'y'      => 70,
																					'width'  => 340,
																					'height' => 230,
																					'margin' => 6
																			);

				$chart[ 'draw_text' ] = array (
																				array ( 'color'    => '9e9286',
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
																								'v_align'  => "bottom" )
																				);

				$chart[ 'chart_bg' ] = array (		'positive_color' => "ffffff",
																					// 'negative_color'  =>  string,
																					'positive_alpha' => 20,
																					// 'negative_alpha'  =>  int
																			);

				$chart [ 'legend_bg' ] = array (  'bg_color'          =>  "ffffff",
																					'bg_alpha'          =>  20,
																					// 'border_color'      =>  "000000",
																					// 'border_alpha'      =>  100,
																					// 'border_thickness'  =>  1
																			);

				$chart [ 'legend_label' ] = array(// 'layout'  =>  "horizontal",
																					// 'font'    =>  string,
																					// 'bold'    =>  boolean,
																					'size'    =>  15,
																					// 'color'   =>  string,
																					// 'alpha'   =>  int
																			 );

				/*$chart[ 'chart_border' ] = array ('color'=>"000000",
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
						<?php if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
						{ ?>
						<td><a href="antispam.php?action=ban&amp;keyword=<?php echo urlencode( stats_basedomain(false) ) ?>" title="<?php echo T_('Ban this domain!') ?>"><img src="img/noicon.gif" class="middle" alt="<?php echo /* TRANS: Abbrev. */ T_('Ban') ?>" title="<?php echo T_('Ban this domain!') ?>" /></a></td>
						<?php } ?>
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
			$Results = & new Results( "SELECT hit_ID, hit_datetime, hit_referer,
																			dom_name, hit_blog_ID, hit_uri, blog_shortname
																FROM T_hitlog INNER JOIN T_basedomains ON dom_ID = hit_referer_dom_ID
																			LEFT JOIN T_blogs ON hit_blog_ID = blog_ID
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
				$Results->cols[1]['td'] = '<a href="%regenerate_url( \'action\', \'action=delete&amp;hit_ID=$hit_ID$\')%" title="'.
																	T_('Delete this hit!').
																	'"><img src="img/xross.gif" width="13" height="13" class="middle" alt="'.
																	/* TRANS: Abbrev. for Delete (stats) */ T_('Del').
																	'" title="'.T_('Delete this hit!').'" /></a> '.

																	'<a href="$hit_referer$">$dom_name$</a>';
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

			// Requested URI:
			$Results->cols[] = array(
									'th' => T_('Requested URI'),
									'order' => 'hit_uri',
									'td' => '<a href="$hit_uri$">$hit_uri$</a>',
								);


			// Display results:
			$Results->display();
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
			<?php } ?>

			<h3><?php echo T_('Top Indexing Robots') ?>:</h3>
			<p><?php echo T_('These are hits from automated robots like search engines\' indexing robots. (Robots must be listed in /conf/_stats.php)') ?></p>
			<?php
			refererList(20,'global',0,0,"'robot'",'agnt_signature',$blog,true,true);
			if( count( $res_stats ) )
			{
				?>
				<table class="grouped" cellspacing="0">
					<?php
					$count = 0;
					foreach( $res_stats as $row_stats )
					{
						?>
						<tr>
							<td class="firstcol"><?php stats_referer('<a href="', '">') ?><?php stats_user_agent( true ) ?><?php stats_referer('', '</a>', false) ?></td>
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
			break;

			
		case 'syndication':
			?>
			<h2><?php echo T_('Top Aggregators') ?>:</h2>
			<p><?php echo T_('These are hits from RSS news aggregators. (Aggregators must be listed in /conf/_stats.php)') ?></p>
			<?php
			$total_hit_count = $DB->get_var( "SELECT COUNT(*) AS hit_count
																				FROM T_useragents INNER JOIN T_hitlog ON hit_agnt_ID = agnt_ID
																				WHERE agnt_type = 'rss' ".
																				( empty($blog) ? '' : "AND hit_blog_ID = $blog " ), 0, 0, 'Get total hit count' );
			
			
			echo '<p>'.T_('Total RSS hits').': '.$total_hit_count.'</p>';
			
			// Create result set:
			$Results = & new Results( "SELECT agnt_signature, COUNT(*) AS hit_count
																FROM T_useragents INNER JOIN T_hitlog ON hit_agnt_ID = agnt_ID
																WHERE agnt_type = 'rss' ".
																( empty($blog) ? '' : "AND hit_blog_ID = $blog " ).'
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
			$Results = & new Results( "SELECT hit_ID, hit_datetime, hit_blog_ID, hit_uri, blog_shortname
																FROM T_hitlog INNER JOIN T_useragents ON hit_agnt_ID = agnt_ID
																			LEFT JOIN T_blogs ON hit_blog_ID = blog_ID
																WHERE hit_referer_type = 'direct'
																  AND agnt_type = 'browser'"
																	.( empty($blog) ? '' : "AND hit_blog_ID = $blog "), 'lstref_', 'D' );

			$Results->title = T_('Last referers');

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
									'td'=>' <a href="%regenerate_url( \'action\', \'action=delete&amp;hit_ID=$hit_ID$\')%" title="'.
																	T_('Delete this hit!').
																	'"><img src="img/xross.gif" width="13" height="13" class="middle" alt="'.
																	/* TRANS: Abbrev. for Delete (stats) */ T_('Del').
																	'" title="'.T_('Delete this hit!').'" /></a>',
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

			// Requested URI:
			$Results->cols[] = array(
									'th' => T_('Requested URI'),
									'order' => 'hit_uri',
									'td' => '<a href="$hit_uri$">$hit_uri$</a>',
								);


			// Display results:
			$Results->display();

			break;


		case 'useragents':
			?>
			<h2><?php echo T_('Top User Agents') ?>:</h2>
			<?php
			$total_hit_count = $DB->get_var( "SELECT COUNT(*) AS hit_count
																				FROM T_useragents INNER JOIN T_hitlog ON hit_agnt_ID = agnt_ID
																				WHERE agnt_type <> 'rss' ".
																				( empty($blog) ? '' : "AND hit_blog_ID = $blog " ), 0, 0, 'Get total hit count' );
			
			
			echo '<p>'.T_('Total hits').': '.$total_hit_count.'</p>';
			
			// Create result set:
			$Results = & new Results( "SELECT agnt_signature, COUNT(*) AS hit_count
																FROM T_useragents INNER JOIN T_hitlog ON hit_agnt_ID = agnt_ID
																WHERE agnt_type <> 'rss' ".
																( empty($blog) ? '' : "AND hit_blog_ID = $blog " ).'
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
$AdminUI->dispPayloadEnd();

require dirname(__FILE__).'/_footer.php';

/*
 * $Log$
 * Revision 1.6  2005/10/14 21:00:08  fplanque
 * Stats & antispam have obviously been modified with ZERO testing.
 * Fixed a sh**load of bugs...
 *
 */ 
?>