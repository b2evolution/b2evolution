<?php
/**
 * This file implements archive lists
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_class_dataobjectlist.php';

/**
 * Archive List Class
 *
 * @package evocore
 */
class ArchiveList extends DataObjectList
{
	var $blog;
	var $archive_mode;
	var $arc_w_last;
	
	/**
	 * Constructor
   *
   * Note: Weekly archives use mySQL's week numbering and mySQL default if applicable.
   * In mySQL < 4.0, WEEK() uses mode 0: Week starts on Sunday;
   * Value range is 0 to 53; week 1 is the first week that starts in this year
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
		$limit = '' )
	{
		global $DB, $Settings;

		$this->blog = $blog;
		$this->archive_mode = $archive_mode;

		// CONSTRUCT THE WHERE CLAUSE:

		/*
		 * ----------------------------------------------------
		 *  Restrict to the statuses we want to show:
		 * ----------------------------------------------------
		 */
		$where = ' WHERE '.statuses_where_clause( $show_statuses );
		$where_link = ' AND ';
		

		// Restrict to timestamp limits:
		if( $timestamp_min == 'now' ) $timestamp_min = time();
		if( !empty($timestamp_min) ) 
		{	// Hide posts before
			$date_min = date('Y-m-d H:i:s', $timestamp_min + ($Settings->get('time_difference') * 3600) );
			$where .= $where_link.' post_issue_date >= \''.$date_min.'\'';
			$where_link = ' AND ';
		}
		if( $timestamp_max == 'now' ) $timestamp_max = time();
		if( !empty($timestamp_max) ) 
		{	// Hide posts after
			$date_max = date('Y-m-d H:i:s', $timestamp_max + ($Settings->get('time_difference') * 3600) );
			$where .= $where_link.' post_issue_date <= \''.$date_max.'\'';
			$where_link = ' AND ';
		}
	
		// Do we need to restrict categories:
		if( $blog > 1 ) 
		{	// Blog #1 aggregates all
			$where .= $where_link.' cat_blog_ID = '.$blog;
			$where_link = ' AND ';
		}
		
		if( !empty($limit) )
		{
			$limit = ' LIMIT 0,'.$limit;
		}


		switch( $archive_mode )
		{
			case 'monthly':
				// ------------------------------ MONTHLY ARCHIVES ------------------------------------
				$this->request = 'SELECT YEAR(post_issue_date) AS year, MONTH(post_issue_date) AS month,
																	COUNT(DISTINCT postcat_post_ID) AS count
													FROM (T_posts INNER JOIN T_postcats ON ID = postcat_post_ID)
																INNER JOIN T_categories ON postcat_cat_ID = cat_ID
													'.$where.'
													GROUP BY year, month
													ORDER BY year DESC, month DESC
													'.$limit;
				break;

			case 'daily':
				// ------------------------------- DAILY ARCHIVES -------------------------------------
				$this->request = 'SELECT YEAR(post_issue_date) AS year, MONTH(post_issue_date) AS month,
																	DAYOFMONTH(post_issue_date) AS day,
																	COUNT(DISTINCT postcat_post_ID) AS count
													FROM (T_posts INNER JOIN T_postcats ON ID = postcat_post_ID)
																INNER JOIN T_categories ON postcat_cat_ID = cat_ID
													'.$where.'
													GROUP BY year, month, day
													ORDER BY year DESC, month DESC, day DESC
													'.$limit;
				break;

			case 'weekly':
				// ------------------------------- WEEKLY ARCHIVES -------------------------------------
				$this->request = 'SELECT YEAR(post_issue_date) AS year, WEEK(post_issue_date) AS week,
																	COUNT(DISTINCT postcat_post_ID) AS count
													FROM (T_posts INNER JOIN T_postcats ON ID = postcat_post_ID)
																INNER JOIN T_categories ON postcat_cat_ID = cat_ID
													'.$where.'
													GROUP BY year, week
													ORDER BY year DESC, week DESC
													'.$limit;
				break;

			case 'postbypost':
			default:
				// ----------------------------- POSY BY POST ARCHIVES --------------------------------
				$this->request = 'SELECT DISTINCT ID, post_issue_date, post_title
													FROM (T_posts INNER JOIN T_postcats ON ID = postcat_post_ID)
																INNER JOIN T_categories ON postcat_cat_ID = cat_ID
													'.$where.'
													ORDER BY post_issue_date DESC
													'.$limit;
		}

		// echo $this->request.'<br/>';

		$this->result = $DB->get_results( $this->request, ARRAY_A );;
	
		$this->result_num_rows = $DB->num_rows;
		// echo 'rows='.$this->result_num_rows.'<br/>';
		
		$this->arc_w_last = '';
	}

	/**
	 * Getting next item in archive list
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

		$arc_row = $this->result[ $this->current_idx++ ];

		switch( $this->archive_mode )
		{
			case 'monthly':
				$arc_year  = $arc_row['year'];
				$arc_month = $arc_row['month'];
				$arc_count = $arc_row['count'];
				return true;

			case 'daily':
				$arc_year  = $arc_row['year'];
				$arc_month = $arc_row['month'];
				$arc_dayofmonth = $arc_row['day'];
				$arc_count = $arc_row['count'];
				return true;

			case 'weekly':
				$arc_year  = $arc_row['year'];
				$arc_w = $arc_row['week'];
				$arc_count = $arc_row['count'];
				return true;

			case 'postbypost':
			default:
				$post_ID = $arc_row['ID'];
				$post_title = $arc_row['post_title'];
				return true;
		}
	}
}

?>