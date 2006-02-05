<?php
/**
 * This file implements the Archives plugin.
 *
 * Displays a list of post archives.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * {@internal
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package plugins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE - {@link http://fplanque.net/}
 * @author cafelog (group)
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Archives Plugin
 *
 * This plugin displays
 */
class archives_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */

	var $name = 'Archives Skin Tag';
	var $code = 'evo_Arch';
	var $priority = 50;
	var $version = 'CVS $Revision$';
	var $author = 'The b2evo Group';
	var $help_url = 'http://b2evolution.net/';


	/**
	 * Constructor
	 *
	 * {@internal archives_plugin::archives_plugin(-)}}
	 */
	function archives_plugin()
	{
		$this->short_desc = T_('This skin tag displays a list of post archives.');
		$this->long_desc = T_('Archives can be grouped monthly, daily, weekly or post by post.');

		$this->dbtable = 'T_posts';
		$this->dbprefix = 'post_';
		$this->dbIDname = 'post_ID';
	}


	/**
	 * Event handler: SkinTag
	 *
	 * {@internal archives_plugin::SkinTag(-)}}
	 *
	 * @param array Associative array of parameters. Valid keys are:
	 *                - 'block_start' : (Default: '<div class="bSideItem">')
	 *                - 'block_end' : (Default: '</div>')
	 *                - 'title' : (Default: '<h3>'.T_('Archives').'</h3>')
	 *                - 'mode' : 'monthly'|'daily'|'weekly'|'postbypost' (Default: conf.)
	 *                - 'link_type' : 'canonic'|'context' (default: canonic)
	 *                - 'context_isolation' : what params need override when changing date/range (Default: 'm,w,p,title,unit,dstart' )
	 *                - 'form' : true|false (default: false)
	 *                - 'limit' : # of archive entries to display or '' (Default: 12)
	 *                - 'more_link' : more link text (Default: 'More...')
	 *                - 'list_start' : (Default '<ul>')
	 *                - 'list_end' : (Default '</ul>')
	 *                - 'line_start' : (Default '<li>')
	 *                - 'line_end' : (Default '</li>')
	 *                - 'day_date_format' : (Default: conf.)
	 * @return boolean did we display?
	 */
	function SkinTag( $params )
	{
	 	global $Settings, $month;

		/**
		 * @todo get rid of these globals:
		 */
		global $blog, $Blog, $m;

		/**
		 * Default params:
		 */
		// This is what will enclose the block in the skin:
		if(!isset($params['block_start'])) $params['block_start'] = '<div class="bSideItem">';
		if(!isset($params['block_end'])) $params['block_end'] = "</div>\n";

		// Title:
		if(!isset($params['title']))
			$params['title'] = '<h3>'.T_('Archives').'</h3>';

		// Archive mode:
		if(!isset($params['mode']))
			$params['mode'] = $Settings->get('archive_mode');

		// Link type:
		if(!isset($params['link_type'])) $params['link_type'] = 'canonic';
		if(!isset($params['context_isolation'])) $params['context_isolation'] = 'm,w,p,title,unit,dstart';

		// Add form fields?:
		if(!isset($params['form']))
			$params['form'] = false;

		// Number of archive entries to display:
		if(!isset($params['limit'])) $params['limit'] = 12;

		// More link text:
		if(!isset($params['more_link'])) $params['more_link'] = T_('More...');

		// This is what will enclose the list:
		if(!isset($params['list_start'])) $params['list_start'] = '<ul>';
		if(!isset($params['list_end'])) $params['list_end'] = "</ul>\n";

		// This is what will separate the archive links:
		if(!isset($params['line_start'])) $params['line_start'] = '<li>';
		if(!isset($params['line_end'])) $params['line_end'] = "</li>\n";

		// Daily archive date format?
		if( (!isset($params['day_date_format'])) || ($params['day_date_format'] == '') )
		{
		 	$dateformat = locale_datefmt();
			$params['day_date_format'] = $dateformat;
		}

		$ArchiveList = & new ArchiveList( $params['mode'], $params['limit'], ($params['link_type'] == 'context'),
																			$this->dbtable, $this->dbprefix, $this->dbIDname );

		echo $params['block_start'];

		echo $params['title'];

		echo $params['list_start'];
		while( $ArchiveList->get_item( $arc_year, $arc_month, $arc_dayofmonth, $arc_w, $arc_count, $post_ID, $post_title) )
		{
			echo $params['line_start'];
			switch( $params['mode'] )
			{
				case 'monthly':
					// --------------------------------- MONTHLY ARCHIVES -------------------------------------
					$arc_m = $arc_year.zeroise($arc_month,2);

					if( $params['form'] )
					{ // We want a radio button:
						echo '<input type="radio" name="m" value="'.$arc_m.'" class="checkbox"';
						if( $m == $arc_m ) echo ' checked="checked"' ;
						echo ' /> ';
					}

					echo '<a href="';
					if( $params['link_type'] == 'context' )
					{	// We want to preserve current browsing context:
						echo regenerate_url( $params['context_isolation'], 'm='.$arc_m );
					}
					else
					{	// We want to link to the absolute canonical URL for this archive:
						archive_link( $arc_year, $arc_month );
					}
					echo '">';

					echo T_($month[zeroise($arc_month,2)]),' ',$arc_year;
					echo '</a> <span class="dimmed">('.$arc_count.')</span>';
					break;

				case 'daily':
					// --------------------------------- DAILY ARCHIVES ---------------------------------------
					$arc_m = $arc_year.zeroise($arc_month,2).zeroise($arc_dayofmonth,2);

					if( $params['form'] )
					{ // We want a radio button:
						echo '<input type="radio" name="m" value="'. $arc_m. '" class="checkbox"';
						if( $m == $arc_m ) echo ' checked="checked"' ;
						echo ' /> ';
					}

					echo '<a href="';
					if( $params['link_type'] == 'context' )
					{	// We want to preserve current browsing context:
						echo regenerate_url( $params['context_isolation'], 'm='.$arc_m );
					}
					else
					{	// We want to link to the absolute canonical URL for this archive:
						archive_link( $arc_year, $arc_month, $arc_dayofmonth );
					}
					echo '">';

					echo mysql2date($params['day_date_format'], $arc_year.'-'.zeroise($arc_month,2).'-'.zeroise($arc_dayofmonth,2).' 00:00:00');
					echo '</a> <span class="dimmed">('.$arc_count.')</span>';
					break;

				case 'weekly':
					// --------------------------------- WEEKLY ARCHIVES --------------------------------------
					echo '<a href="';
					if( $params['link_type'] == 'context' )
					{	// We want to preserve current browsing context:
						echo regenerate_url( $params['context_isolation'], 'm='.$arc_year.'&amp;w='.$arc_w );
					}
					else
					{	// We want to link to the absolute canonical URL for this archive:
						archive_link( $arc_year, '', '', $arc_w );
					}
					echo '">';
					echo $arc_year.', '.T_('week').' '.$arc_w;
					echo '</a> <span class="dimmed">('.$arc_count.')</span>';
					break;

				case 'postbypost':
				default:
					// -------------------------------- POST BY POST ARCHIVES ---------------------------------
					echo '<a href="';
					if( $params['link_type'] == 'context' )
					{	// We want to preserve current browsing context:
						echo regenerate_url( $params['context_isolation'], 'p='.$post_ID );
					}
					else
					{	// We want to link to the absolute canonical URL for this archive:
						echo get_permalink( $Blog->get('url'), $post_ID, 'id' );
					}
					echo '">';
					if ($post_title)
					{
						echo strip_tags($post_title);
					}
					else
					{
						echo $post_ID;
					}
					echo '</a>';
			}

			echo $params['line_end'];
		}

		// Display more link:
		if( !empty($params['more_link']) )
		{
			echo $params['line_start'];
			echo '<a href="';
			$Blog->disp( 'arcdirurl', 'raw' );
			echo '">'.format_to_output($params['more_link']).'</a>';
			echo $params['line_end'];
		}

		echo $params['list_end'];

		echo $params['block_end'];

		return true;
	}
}


/**
 * Archive List Class
 *
 * @package evocore
 */
class ArchiveList extends Results
{
	var $archive_mode;
	var $arc_w_last;

	/**
	 * Constructor
	 *
	 * Note: Weekly archives use MySQL's week numbering and MySQL default if applicable.
	 * In MySQL < 4.0.14, WEEK() always uses mode 0: Week starts on Sunday;
	 * Value range is 0 to 53; week 1 is the first week that starts in this year.
	 *
	 * @link http://dev.mysql.com/doc/mysql/en/date-and-time-functions.html
	 *
	 * @todo categories combined with 'ALL' are not supported (will output too many archives,
	 * some of which will resolve to no results). We need subqueries to support this efficiently.
	 *
	 * @param string
	 * @param integer
	 * @param boolean
	 */
	function ArchiveList(
		$archive_mode = 'monthly',
		$limit = 100,
		$preserve_context = false,
		$dbtable = 'T_posts',
		$dbprefix = 'post_',
		$dbIDname = 'ID' )
	{
		global $DB, $Settings;
		global $blog, $cat, $catsel;
		global $show_statuses;
		global $author, $assgn, $status;
		global $timestamp_min, $timestamp_max;
		global $s, $sentence, $exact;

		$this->dbtable = $dbtable;
		$this->dbprefix = $dbprefix;
		$this->dbIDname = $dbIDname;
		$this->archive_mode = $archive_mode;


		/*
		 * WE ARE GOING TO CONSTRUCT THE WHERE CLOSE...
		 */
		$this->ItemQuery = & new ItemQuery( $this->dbtable, $this->dbprefix, $this->dbIDname ); // TEMPORARY OBJ

		// - - Select a specific Item:
		// $this->ItemQuery->where_ID( $p, $title );

		if( $preserve_context )
		{	// We want to preserve the current context:
			// * - - Restrict to selected blog/categories:
			$this->ItemQuery->where_chapter( $blog, $cat, $catsel );

			// * Restrict to the statuses we want to show:
			$this->ItemQuery->where_visibility( $show_statuses );

			// Restrict to selected authors:
			$this->ItemQuery->where_author( $author );

			// Restrict to selected assignees:
			$this->ItemQuery->where_assignees( $assgn );

			// Restrict to selected satuses:
			$this->ItemQuery->where_statuses( $status );

			// - - - + * * timestamp restrictions:
			$this->ItemQuery->where_datestart( '', '', '', '', $timestamp_min, $timestamp_max );

			// Keyword search stuff:
			$this->ItemQuery->where_keywords( $s, $sentence, $exact );
		}
		else
		{	// We want to preserve only the minimal context:
			// * - - Restrict to selected blog/categories:
			$this->ItemQuery->where_chapter( $blog, '', array() );

			// * Restrict to the statuses we want to show:
			$this->ItemQuery->where_visibility( $show_statuses );

			// - - - + * * timestamp restrictions:
			$this->ItemQuery->where_datestart( '', '', '', '', $timestamp_min, $timestamp_max );
		}


		$this->from = $this->ItemQuery->get_from();
		$this->where = $this->ItemQuery->get_where();
		$this->group_by = $this->ItemQuery->get_group_by();

		switch( $this->archive_mode )
		{
			case 'monthly':
				// ------------------------------ MONTHLY ARCHIVES ------------------------------------
				$sql = 'SELECT YEAR('.$this->dbprefix.'datestart) AS year, MONTH('.$this->dbprefix.'datestart) AS month,
																	COUNT(DISTINCT postcat_post_ID) AS count '
													.$this->from
													.$this->where.'
													GROUP BY year, month
													ORDER BY year DESC, month DESC';
				break;

			case 'daily':
				// ------------------------------- DAILY ARCHIVES -------------------------------------
				$sql = 'SELECT YEAR('.$this->dbprefix.'datestart) AS year, MONTH('.$this->dbprefix.'datestart) AS month,
																	DAYOFMONTH('.$this->dbprefix.'datestart) AS day,
																	COUNT(DISTINCT postcat_post_ID) AS count '
													.$this->from
													.$this->where.'
													GROUP BY year, month, day
													ORDER BY year DESC, month DESC, day DESC';
				break;

			case 'weekly':
				// ------------------------------- WEEKLY ARCHIVES -------------------------------------
				$sql = 'SELECT YEAR('.$this->dbprefix.'datestart) AS year, '.
															$DB->week( $this->dbprefix.'datestart', locale_startofweek() ).' AS week,
															COUNT(DISTINCT postcat_post_ID) AS count '
													.$this->from
													.$this->where.'
													GROUP BY year, week
													ORDER BY year DESC, week DESC';
				break;

			case 'postbypost':
			default:
				// ----------------------------- POSY BY POST ARCHIVES --------------------------------
				$sql = 'SELECT DISTINCT '.$this->dbIDname.', '.$this->dbprefix.'datestart, '.$this->dbprefix.'title '
													.$this->from
													.$this->where
													.$this->group_by.'
													ORDER BY '.$this->dbprefix.'datestart DESC';
		}

		parent::Results( $sql, 'archivelist_', '', $limit );

		$this->restart();
	}


	/**
	 * Count the number of rows of the SQL result
	 *
	 * These queries are complex enough for us not to have to rewrite them:
	 */
	function count_total_rows()
	{
		global $DB;

		switch( $this->archive_mode )
		{
			case 'monthly':
				// ------------------------------ MONTHLY ARCHIVES ------------------------------------
				$sql_count = 'SELECT COUNT( DISTINCT YEAR('.$this->dbprefix.'datestart), MONTH('.$this->dbprefix.'datestart) ) '
													.$this->from
													.$this->where;
				break;

			case 'daily':
				// ------------------------------- DAILY ARCHIVES -------------------------------------
				$sql_count = 'SELECT COUNT( DISTINCT YEAR('.$this->dbprefix.'datestart), MONTH('.$this->dbprefix.'datestart),
																	DAYOFMONTH('.$this->dbprefix.'datestart) ) '
													.$this->from
													.$this->where;
				break;

			case 'weekly':
				// ------------------------------- WEEKLY ARCHIVES -------------------------------------
				$sql_count = 'SELECT COUNT( DISTINCT YEAR('.$this->dbprefix.'datestart), '
													.$DB->week( $this->dbprefix.'datestart', locale_startofweek() ).' ) '
													.$this->from
													.$this->where;
				break;

			case 'postbypost':
			default:
				// ----------------------------- POSY BY POST ARCHIVES --------------------------------
				$sql_count = 'SELECT COUNT( DISTINCT '.$this->dbIDname.' ) '
													.$this->from
													.$this->where
													.$this->group_by;
		}

		// echo $sql_count;

		$this->total_rows = $this->DB->get_var( $sql_count ); //count total rows

		// echo 'total rows='.$this->total_rows;
	}


	/**
	 * Rewind resultset
	 *
	 * {@internal DataObjectList::restart(-) }}
	 */
	function restart()
	{
		// Make sure query has executed at least once:
		$this->query( $this->sql );

		$this->current_idx = 0;
		$this->arc_w_last = '';
	}

	/**
	 * Getting next item in archive list
	 *
	 * WARNING: these are *NOT* Item objects!
	 *
	 * {@internal ArchiveList->get_item(-)}}
	 */
	function get_item( & $arc_year, & $arc_month, & $arc_dayofmonth, & $arc_w, & $arc_count, & $post_ID, & $post_title )
	{
		// echo 'getting next item<br />';

		if( $this->current_idx >= $this->result_num_rows )
		{	// No more entry
			return false;
		}

		$arc_row = $this->rows[ $this->current_idx++ ];

		switch( $this->archive_mode )
		{
			case 'monthly':
				$arc_year  = $arc_row->year;
				$arc_month = $arc_row->month;
				$arc_count = $arc_row->count;
				return true;

			case 'daily':
				$arc_year  = $arc_row->year;
				$arc_month = $arc_row->month;
				$arc_dayofmonth = $arc_row->day;
				$arc_count = $arc_row->count;
				return true;

			case 'weekly':
				$arc_year  = $arc_row->year;
				$arc_w = $arc_row->week;
				$arc_count = $arc_row->count;
				return true;

			case 'postbypost':
			default:
				$post_ID = $arc_row->post_ID;
				$post_title = $arc_row->{$this->dbprefix.'title'};
				return true;
		}
	}
}


/*
 * $Log$
 * Revision 1.18  2006/02/05 19:04:49  blueyed
 * doc fixes
 *
 * Revision 1.17  2006/02/05 14:07:18  blueyed
 * Fixed 'postbypost' archive mode.
 *
 * Revision 1.16  2006/01/04 20:34:51  fplanque
 * allow filtering on extra statuses
 *
 * Revision 1.15  2005/12/22 23:13:40  blueyed
 * Plugins' API changed and handling optimized
 *
 * Revision 1.14  2005/12/12 19:22:04  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.13  2005/11/01 17:47:37  yabs
 * minor corrections to postbypost
 *
 * Revision 1.12  2005/10/03 18:10:08  fplanque
 * renamed post_ID field
 *
 * Revision 1.11  2005/09/14 19:23:45  fplanque
 * doc
 *
 * Revision 1.10  2005/09/06 19:38:29  fplanque
 * bugfixes
 *
 * Revision 1.9  2005/09/06 17:14:12  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.8  2005/09/01 17:11:46  fplanque
 * no message
 *
 *
 * Merged in the contents of _archivelist.class.php; history below:
 *
 * Revision 1.11  2005/06/10 18:25:43  fplanque
 * refactoring
 *
 * Revision 1.10  2005/05/24 15:26:52  fplanque
 * cleanup
 *
 * Revision 1.9  2005/03/08 20:32:07  fplanque
 * small fixes; slightly enhanced WEEK() handling
 *
 * Revision 1.8  2005/03/07 17:36:10  fplanque
 * made more generic
 *
 * Revision 1.7  2005/02/28 09:06:32  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.6  2005/01/03 15:17:52  fplanque
 * no message
 *
 * Revision 1.5  2004/12/27 18:37:58  fplanque
 * changed class inheritence
 *
 * Changed parent to Results!!
 *
 * Revision 1.4  2004/12/13 21:29:58  fplanque
 * refactoring
 *
 * Revision 1.3  2004/11/09 00:25:11  blueyed
 * minor translation changes (+MySQL spelling :/)
 *
 * Revision 1.2  2004/10/14 18:31:24  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.19  2004/10/11 19:02:04  fplanque
 * Edited code documentation.
 *
 */
?>