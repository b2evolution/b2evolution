<?php
/**
 * This file implements the ArchiveList class designed to handle Item archive lists.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: François PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_dataobjectlist.class.php';

/**
 * Archive List Class
 *
 * @package evocore
 */
class ArchiveList extends Results
{
	var $blog;
	var $archive_mode;
	var $arc_w_last;

	/**
	 * Constructor
   *
   * Note: Weekly archives use MySQL's week numbering and MySQL default if applicable.
   * In MySQL < 4.0.14, WEEK() always uses mode 0: Week starts on Sunday;
   * Value range is 0 to 53; week 1 is the first week that starts in this year
   * {@see http://dev.mysql.com/doc/mysql/en/date-and-time-functions.html}
   *
	 * {@internal ArchiveList::ArchiveList(-)}}
	 *
   * @param integer
   * @param string
   * @param array
   * @param mixed
   * @param mixed
   * @param integer
	 */
	function ArchiveList(
		$blog = 1,
		$archive_mode = 'monthly',
		$show_statuses = array(),
		$timestamp_min = '',									// Do not show posts before this timestamp
		$timestamp_max = 'now',								// Do not show posts after this timestamp
		$limit = 100,
		$dbtable = 'T_posts',
 		$dbprefix = 'post_',
		$dbIDname = 'ID' )

	{
		global $DB, $Settings;

		$this->blog = $blog;
		$this->dbtable = $dbtable;
		$this->dbprefix = $dbprefix;
		$this->dbIDname = $dbIDname;
		$this->archive_mode = $archive_mode;


		// CONSTRUCT THE FROM CLAUSE:
		$this->from = '	FROM ('.$this->dbtable.' INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID)
																INNER JOIN T_categories ON postcat_cat_ID = cat_ID ';


		// CONSTRUCT THE WHERE CLAUSE:

		/*
		 * ----------------------------------------------------
		 *  Restrict to the statuses we want to show:
		 * ----------------------------------------------------
		 */
		$where = ' WHERE '.statuses_where_clause( $show_statuses, $dbprefix );
		$where_link = ' AND ';


		// Restrict to timestamp limits:
		if( $timestamp_min == 'now' ) $timestamp_min = time();
		if( !empty($timestamp_min) )
		{	// Hide posts before
			$date_min = date('Y-m-d H:i:s', $timestamp_min + ($Settings->get('time_difference') * 3600) );
			$where .= $where_link.' '.$dbprefix.'datestart >= \''.$date_min.'\'';
			$where_link = ' AND ';
		}
		if( $timestamp_max == 'now' ) $timestamp_max = time();
		if( !empty($timestamp_max) )
		{	// Hide posts after
			$date_max = date('Y-m-d H:i:s', $timestamp_max + ($Settings->get('time_difference') * 3600) );
			$where .= $where_link.' '.$dbprefix.'datestart <= \''.$date_max.'\'';
			$where_link = ' AND ';
		}

		// Do we need to restrict categories:
		if( $blog > 1 )
		{	// Blog #1 aggregates all
			$where .= $where_link.' cat_blog_ID = '.$blog;
			$where_link = ' AND ';
		}

		$this->where = $where;

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
															COUNT(DISTINCT postcat_'.$this->dbprefix.'ID) AS count '
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
													.$this->where.'
													ORDER BY '.$this->dbprefix.'datestart DESC';
		}

		parent::Results( $sql, $limit, 'archivelist_', 1 );

		$this->restart();
	}


	/**
	 * Count the number of rows of the SQL result
	 *
	 * These queries are complex enough for us not to have to rewrite them:
	 */
	function count_total_rows()
	{
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
													.$this->where;
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
				$post_ID = $arc_row->ID;
				$post_title = $arc_row[$this->dbprefix->title];
				return true;
		}
	}
}

/*
 * $Log$
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