<?php
/**
 * This file implements the riight sidebar for the post browsing screen.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

echo '<div class="browse_side_item">';

	echo '<h2>'.$Blog->dget( 'name', 'htmlbody' ).'</h2>';

	// ---------- CALENDAR ----------
	$Calendar = & new Calendar( $blog, ( empty($calendar) ? $m : $calendar ), '',
															$timestamp_min, $timestamp_max, $dbtable, $dbprefix, $dbIDname );
	$Calendar->set( 'browseyears', 1 );  // allow browsing years in the calendar's caption
	$Calendar->set( 'navigation', 'tfoot' );
	$Calendar->display( $pagenow, 'tab='.$tab.'&amp;blog='.$blog );

	if( isset( $Blog ) && ( $Blog->get( 'notes' ) ) )
	{ // We might use this file outside of a blog...
		echo '<h3>'.T_('Notes').'</h3>';
		$Blog->disp( 'notes', 'htmlbody' );
	}

echo '</div>';

echo '<div class="browse_side_item">';

	$Form = & new Form( $pagenow, 'searchform', 'get', 'none' );

	$Form->begin_form( '' );

		$Form->submit( array( 'submit', T_('Search'), 'search', '', 'float:right' ) );

		echo '<h3>'.T_('Search').'</h3>';

		$Form->hidden( 'tab', $tab );
		$Form->hidden( 'blog', $blog );

		$Form->fieldset( T_('Posts to show') );
		?>
		<div>

		<input type="checkbox" name="show_past" value="1" id="ts_min" class="checkbox" <?php if( $show_past ) echo 'checked="checked" '?> />
		<label for="ts_min"><?php echo T_('Past') ?></label><br />

		<input type="checkbox" name="show_future" value="1" id="ts_max" class="checkbox" <?php if( $show_future ) echo 'checked="checked" '?> />
		<label for="ts_max"><?php echo T_('Future') ?></label>

		</div>

		<div>

		<input type="checkbox" name="show_status[]" value="published" id="sh_published" class="checkbox" <?php if( in_array( "published", $show_status ) ) echo 'checked="checked" '?> />
		<label for="sh_published"><?php echo T_('Published') ?> <span class="notes">(<?php echo T_('Public') ?>)</span></label><br />

		<input type="checkbox" name="show_status[]" value="protected" id="sh_protected" class="checkbox" <?php if( in_array( "protected", $show_status ) ) echo 'checked="checked" '?> />
		<label for="sh_protected"><?php echo T_('Protected') ?> <span class="notes">(<?php echo T_('Members only') ?>)</span></label><br />

		<input type="checkbox" name="show_status[]" value="private" id="sh_private" class="checkbox" <?php if( in_array( "private", $show_status ) ) echo 'checked="checked" '?> />
		<label for="sh_private"><?php echo T_('Private') ?> <span class="notes">(<?php echo T_('You only') ?>)</span></label><br />

		<input type="checkbox" name="show_status[]" value="draft" id="sh_draft" class="checkbox" <?php if( in_array( "draft", $show_status ) ) echo 'checked="checked" '?> />
		<label for="sh_draft"><?php echo T_('Draft') ?> <span class="notes">(<?php echo T_('Not published!') ?>)</span></label><br />

		<input type="checkbox" name="show_status[]" value="deprecated" id="sh_deprecated" class="checkbox" <?php if( in_array( "deprecated", $show_status ) ) echo 'checked="checked" '?> />
		<label for="sh_deprecated"><?php echo T_('Deprecated') ?> <span class="notes">(<?php echo T_('Not published!') ?>)</span></label><br />

	 	</div>

		<?php
		$Form->fieldset_end();


		$Form->fieldset( T_('Title / Text contains'), 'Text' );

		echo $Form->inputstart;
		?>
		<div><input type="text" name="s" size="20" value="<?php echo htmlspecialchars($s) ?>" class="SearchField" /></div>
		<?php
		echo $Form->inputend;
		// echo T_('Words').' : ';
		?>

		<input type="radio" name="sentence" value="AND" id="sentAND" class="checkbox" <?php if( $sentence=='AND' ) echo 'checked="checked" '?> />
		<label for="sentAND"><?php echo T_('AND') ?></label>
		<input type="radio" name="sentence" value="OR" id="sentOR" class="checkbox" <?php if( $sentence=='OR' ) echo 'checked="checked" '?> />
		<label for="sentOR"><?php echo T_('OR') ?></label>
		<input type="radio" name="sentence" value="sentence" class="checkbox" id="sentence" <?php if( $sentence=='sentence' ) echo 'checked="checked" '?> />
		<label for="sentence"><?php echo T_('Entire phrase') ?></label>
		<?php
		$Form->fieldset_end();

		$Form->fieldset( 'Archives', T_('Archives') );
		?>
		<ul>
		<?php
		// this is what will separate your archive links
		$archive_line_start = '<li>';
		$archive_line_end = '</li>';
		// this is what will separate dates on weekly archive links
		$archive_week_separator = ' - ';

		$dateformat = locale_datefmt();
		$archive_day_date_format = $dateformat;
		$archive_week_start_date_format = $dateformat;
		$archive_week_end_date_format   = $dateformat;

		$arc_link_start = $pagenow.'?tab='.$tab.'&amp;blog='.$blog.'&amp;';

		$ArchiveList = & new ArchiveList( $blog, $Settings->get('archive_mode'), $show_statuses,	$timestamp_min,
																			$timestamp_max, 36, $dbtable, $dbprefix, $dbIDname );
		while( $ArchiveList->get_item( $arc_year, $arc_month, $arc_dayofmonth, $arc_w, $arc_count, $post_ID, $post_title) )
		{
			echo $archive_line_start;
			switch( $Settings->get('archive_mode') )
			{
				case 'monthly':
					// --------------------------------- MONTHLY ARCHIVES ---------------------------------
					$arc_m = $arc_year.zeroise($arc_month,2);
					echo '<input type="radio" name="m" value="'. $arc_m. '" class="checkbox"';
					if( $m == $arc_m ) echo ' checked="checked"' ;
					echo ' /> ';
					echo '<a href="'. $arc_link_start. 'm='. $arc_m. '">';
					echo T_($month[zeroise($arc_month,2)]), ' ', $arc_year;
					echo "</a> <span class=\"notes\">($arc_count)</span>";
					break;

				case 'daily':
					// --------------------------------- DAILY ARCHIVES -----------------------------------
					$arc_m = $arc_year.zeroise($arc_month,2).zeroise($arc_dayofmonth,2);
					echo '<input type="radio" name="m" value="'. $arc_m. '" class="checkbox"';
					if( $m == $arc_m ) echo ' checked="checked"' ;
					echo ' /> ';
					echo '<a href="'. $arc_link_start. 'm='. $arc_m. '">';
					echo mysql2date($archive_day_date_format, $arc_year. '-'. zeroise($arc_month,2). '-'. zeroise($arc_dayofmonth,2). ' 00:00:00');
					echo "</a> <span class=\"notes\">($arc_count)</span>";
					break;

				case 'weekly':
					// --------------------------------- WEEKLY ARCHIVES ---------------------------------
					echo '<a href="'. $arc_link_start. 'm='. $arc_year. '&amp;w='. $arc_w. '">';
					echo $arc_year.', '.T_('week').' '.$arc_w;
					echo "</a> <span class=\"notes\">($arc_count)</span>";
				break;

				case 'postbypost':
				default:
					// ------------------------------- POSY BY POST ARCHIVES -----------------------------
					echo '<a href="'. $arc_link_start. 'p='. $post_ID. '">';
					if ($post_title) {
						echo strip_tags($post_title);
					} else {
						echo $post_ID;
					}
					echo '</a>';
			}

			echo $archive_line_end."\n";
		}
		?>

		</ul>
		<?php
			$Form->fieldset_end();
		?>

		<fieldset title="Categories">
			<legend><?php echo T_('Categories') ?></legend>
			<ul>
			<?php
			$cat_line_start = '<li>';
			$cat_line_end = '</li>';
			$cat_group_start = '<ul>';
			$cat_group_end = '</ul>';
			# When multiple blogs are listed on same page:
			$cat_blog_start = '<li><strong>';
			$cat_blog_end = '</strong></li>';


			// ----------------- START RECURSIVE CAT LIST ----------------
			cat_query( true );	// make sure the caches are loaded
			if( ! isset( $cat_array ) ) $cat_array = array();
			function cat_list_before_first( $parent_cat_ID, $level )
			{ // callback to start sublist
				global $cat_group_start;
				$r = '';
				if( $level > 0 ) $r .= "\n".$cat_group_start."\n";
				return $r;
			}
			function cat_list_before_each( $cat_ID, $level )
			{ // callback to display sublist element
				global $tab, $blog, $cat_array, $cat_line_start, $pagenow;
				$cat = get_the_category_by_ID( $cat_ID );
				$r = $cat_line_start;
				$r .= '<label><input type="checkbox" name="catsel[]" value="'.$cat_ID.'" class="checkbox"';
				if( in_array( $cat_ID, $cat_array ) )
				{ // This category is in the current selection
					$r .= ' checked="checked"';
				}
				$r .= ' /> ';
				$r .= '<a href="'.$pagenow.'?tab='.$tab.'&amp;blog='.$blog.'&amp;cat='.$cat_ID.'">'.$cat['cat_name']
							.'</a> <span class="notes">('.$cat['cat_postcount'].')</span>';
				if( in_array( $cat_ID, $cat_array ) )
				{ // This category is in the current selection
					$r .= "*";
				}
				$r .= '</label>';
				return $r;
			}
			function cat_list_after_each( $cat_ID, $level )
			{ // callback to display sublist element
				global $cat_line_end;
				return $cat_line_end."\n";
			}
			function cat_list_after_last( $parent_cat_ID, $level )
			{ // callback to end sublist
				global $cat_group_end;
				$r = '';
				if( $level > 0 ) $r .= $cat_group_end."\n";
				return $r;
			}

			if( $blog > 1 )
			{ // We want to display cats for one blog
				echo cat_children( $cache_categories, $blog, NULL, 'cat_list_before_first', 'cat_list_before_each', 'cat_list_after_each', 'cat_list_after_last', 0 );
			}
			else
			{ // We want to display cats for all blogs
				for( $curr_blog_ID=blog_list_start('stub');
							$curr_blog_ID!=false;
							 $curr_blog_ID=blog_list_next('stub') )
				{

					echo $cat_blog_start;
					?>
					<a href="<?php blog_list_iteminfo('blogurl') ?>"><?php blog_list_iteminfo('name') ?></a>
					<?php
					echo $cat_blog_end;

					// run recursively through the cats
					echo cat_children( $cache_categories, $curr_blog_ID, NULL, 'cat_list_before_first', 'cat_list_before_each', 'cat_list_after_each', 'cat_list_after_last', 1 );
				}
			}
			// ----------------- END RECURSIVE CAT LIST ----------------
			?>
			</ul>
		</fieldset>
		<?php

		$Form->submit( array( 'submit', T_('Search'), 'search' ) );
		$Form->button( array( 'button', '', T_('Reset'), 'search', 'document.location.href='.$pagenow.'?blog='.$blog.';' ) );

	$Form->end_form();

echo '</div>';

/*
 * $Log$
 * Revision 1.5  2005/08/17 18:23:47  fplanque
 * minor changes
 *
 * Revision 1.4  2005/08/03 21:05:01  fplanque
 * cosmetic cleanup
 *
 * Revision 1.3  2005/08/02 18:15:59  fplanque
 * cosmetic enhancements
 *
 * Revision 1.2  2005/05/09 19:06:53  fplanque
 * bugfixes + global access permission
 *
 * Revision 1.1  2005/03/14 19:54:42  fplanque
 * no message
 *
 */
?>