<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 *
 * Modified 2004 by Hans Reinders - http://hansreinders.com/
 *
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */

class Calendar
{
	var $blog;
	var $year, $month;
	var $specific;			// WE ASKED FOR A SPECIFIC MONTH

	var $where;
	var $request;			// SQL query string
	var $result;			// Result set
	var $result_num_rows;		// Number of rows in result set

	var $monthdisplay;
	var $monthformat;
	var $monthstart;
	var $monthend;

	var $tablestart;
	var $tableend;

	var $rowstart;
	var $rowend;

	var $headerdisplay;
	var $headercellstart;
	var $headercellend;

	var $cellstart;
	var $cellend;

	var $emptycellstart;
	var $emptycellend;

	var $emptycellcontent;

	var $searchframe;

	/*
	 * Calendar::Calendar(-)
	 *
	 * Constructor
	 */
	function Calendar(
		$blog = 1,
		$m= '',
		$show_statuses = array(),
		$timestamp_min = '',		// Do not show posts before this timestamp
		$timestamp_max = 'now'  )	// Do not show posts after this timestamp
	{
		$this->blog = $blog;

		// Find out which month to display:
		if( empty($m) )
		{
			$this->year = date('Y');
			$this->month = date('m');
		}
		else
		{
			$this->specific = true;
			$this->year = substr($m,0,4);
			if (strlen($m) < 6)
			{
				$this->month = '01';
			} else {
				$this->month = substr($m,4,2);
			}
		}

		// CONSTRUCT THE WHERE CLAUSE:

		/*
		 * ----------------------------------------------------
		 *  Restrict to the statuses we want to show:
		 * ----------------------------------------------------
		 */
		$where = ' AND '.statuses_where_clause( $show_statuses );
		$where_link = ' AND ';

		// Restrict to timestamp limits:
		if( $timestamp_min == 'now' ) $timestamp_min = time();
		if( !empty($timestamp_min) )
		{	// Hide posts before
			$date_min = date('Y-m-d H:i:s', $timestamp_min + (get_settings('time_difference') * 3600) );
			$where .= $where_link.' post_issue_date >= \''.$date_min.'\'';
			$where_link = ' AND ';
		}
		if( $timestamp_max == 'now' ) $timestamp_max = time();
		if( !empty($timestamp_max) )
		{	// Hide posts after
			$date_max = date('Y-m-d H:i:s', $timestamp_max + (get_settings('time_difference') * 3600) );
			$where .= $where_link.' post_issue_date <= \''.$date_max.'\'';
			$where_link = ' AND ';
		}

		// Do we need to restrict categories:
		if( $blog > 1 )
		{	// Blog #1 aggregates all
			$where .= $where_link.' cat_blog_ID = '.$blog;
			$where_link = ' AND ';
		}

		$this->where = $where;


		// Default styling:
		$this->monthdisplay = 1;	// set this to 0 if you don't want to display the month name
		$this->monthformat = 'F Y';

		$this->tablestart = '<table class="bCalendarTable" summary="Monthly calendar with links to each day\'s posts">'."\n";
		$this->tableend = '</table>';

		$this->monthstart = '<caption class="bCalendarMonth">';
		$this->monthend = "</caption>\n";

		$this->rowstart = '<tr class="bCalendarRow">' . "\n";
		$this->rowend = "</tr>\n";

		$this->headerdisplay = 1;	// set this to 0 if you don't want to display the "Mon Tue Wed..." header
		$this->headercellstart = '<th class="bCalendarHeaderCell" abbr="[abbr]">';	// please leave [abbr] there !
		$this->headercellend = "</th>\n";

		$this->cellstart = '<td class="bCalendarCell">';
		$this->cellend = "</td>\n";

		$this->emptycellstart = '<td class="bCalendarEmptyCell">';
		$this->emptycellend = "</td>\n";
		$this->emptycellcontent = '&nbsp;';

		$this->linkpostcellstart = '<td class="bCalendarLinkPost">';
		$this->todaycellstart = '<td class="bCalendarToday">';

		$this->searchframe = 12;	// How many month will we search back for a post before we give up

	}

	/*
	 * Calendar->set(-)
	 *
	 * set a variable
	 */
	function set( $var, $value )
	{
		$this->$var = $value;
	}

	/*
	 * Calendar->display(-)
	 *
	 * display the calendar
	 */
	function display( $file='', $params='' )	// Page to use for links
	{
		global $DB;
		global $tableposts, $tablepostcats, $tablecategories;
		global $weekday, $weekday_abbrev;
		global $start_of_week;
		global $calendar_caption_linktomontharchive, $calendar_caption_switchyears;

		$end_of_week = (($start_of_week + 7) % 7);

		// Find a month with posts
		$daysinmonthwithposts = '';
		for( $i=0; $i < $this->searchframe; $i++ )
		{
			$arc_sql="SELECT DISTINCT YEAR(post_issue_date), MONTH(post_issue_date), DAYOFMONTH(post_issue_date)".
					"FROM ($tableposts INNER JOIN $tablepostcats ON ID = postcat_post_ID) ".
					"INNER JOIN $tablecategories ON postcat_cat_ID = cat_ID ".
					"WHERE MONTH(post_issue_date) = '$this->month' AND YEAR(post_issue_date) = '$this->year' ".$this->where.
					" ORDER BY post_issue_date DESC";

			$arc_result = $DB->get_results( $arc_sql, ARRAY_A );

			if( $DB->num_rows > 0 )
			{	// OK we have a month with posts!
				$daysinmonthwithposts = '-';
				foreach( $arc_result as $arc_row )
				{
					$daysinmonthwithposts .= $arc_row["DAYOFMONTH(post_issue_date)"].'-';
				}
				break; // Don't search any further!
			}
			elseif ($this->specific)
			{	// No post, but we asked for a specific month to be displayed
				break; // Don't search any further!
			}
			else
			{	// No, post, let's search in previous month!
				$this->month = zeroise(intval($this->month)-1,2);
				if ($this->month == '00') {
					$this->month = '12';
					$this->year = ''.(intval($this->year)-1);
				}
			}
		}

		// echo $this->month,'.',$this->year;

		$daysinmonth = intval(date('t', mktime(0,0,0,$this->month,1,$this->year)));
		// echo 'days in month=', $daysinmonth;
		$datestartofmonth = $this->year.'-'.$this->month.'-01';
		$dateendofmonth = $this->year.'-'.$this->month.'-'.$daysinmonth;

		// caution: offset bug inside
		$calendarblah = get_weekstartend($datestartofmonth, $start_of_week);
		if (mysql2date('w', $datestartofmonth) == $start_of_week) {
			$calendarfirst = $calendarblah['start']+1+3600;	//	adjust for daylight savings time
		} else {
			$calendarfirst = $calendarblah['end']-604799+3600;	//	adjust for daylight savings time
		}
		//echo 'calendarfirst=', $calendarfirst;

		$calendarblah = get_weekstartend($dateendofmonth, $end_of_week);
		if (mysql2date('w', $dateendofmonth) == $end_of_week) {
			$calendarlast = $calendarblah['start']+1;
		} else {
			$calendarlast = $calendarblah['end']+10000;
		}
		//echo 'calendarlast=', $calendarlast;

		$beforethismonth = zeroise(intval($this->month)-1,2);
		$afterthismonth = zeroise(intval($this->month)-1,2);

		// here the offset bug is corrected
		if ((intval(date('d', $calendarfirst)) > 1) && (intval(date('m', $calendarfirst)) == intval($this->month))) {
			$calendarfirst = $calendarfirst - 604800;
		}


		// Create links to previous/next month
		$previous_month_link = '<a href="'.
			archive_link( ($this->month > 1) ? $this->year : ($this->year - 1),	($this->month > 1) ? ($this->month - 1) : 12, '', '', false, $file, $params )
			.'">&lt;</a>&nbsp;&nbsp;';

		$next_month_link = '&nbsp;&nbsp;<a href="'.
			archive_link( ($this->month < 12) ? $this->year : ($this->year + 1), ($this->month < 12) ? ($this->month + 1) : 1, '', '', false, $file, $params )
			.'">&gt;</a>';
		
		if( $calendar_caption_switchyears )
		{ // create links to previous/next year
			$previous_year_link = '<a href="'.
				archive_link( $this->year - 1, $this->month, '', '', false, $file, $params )
				.'">&lt;&lt;</a>&nbsp;&nbsp;';
			$next_year_link = '&nbsp;&nbsp;<a href="'.
				archive_link( $this->year + 1, $this->month, '', '', false, $file, $params )
				.'">&gt;&gt;</a>';
		}


		// ** display everything **

		echo $this->tablestart;

		if( $this->monthdisplay )
		{	// caption:
			echo $this->monthstart;
			
			echo isset( $previous_year_link ) ? $previous_year_link : '';
			echo $previous_month_link;

			if( $calendar_caption_linktomontharchive )
			{	// chosen month with link to archives
				echo '<a href="'.archive_link( $this->year, $this->month, '', '', false, $file, $params ).'">';
			}
			echo date_i18n($this->monthformat, mktime(0, 0, 0, $this->month, 1, $this->year));
			if( $calendar_caption_linktomontharchive )
			{	// close link to month archive
				echo '</a>';
			}
			
			echo $next_month_link;
			echo isset( $next_year_link ) ? $next_year_link : '';
			
			echo $this->monthend;
		}

		if( $this->headerdisplay )
		{	// Weekdays:
			echo $this->rowstart;

			for ($i = $start_of_week; $i<($start_of_week+7); $i = $i + 1)
			{
				echo str_replace('[abbr]', T_($weekday[($i % 7)]), $this->headercellstart);
				echo T_($weekday_abbrev[($i % 7)]);
				echo $this->headercellend;
			}

			echo $this->rowend;
		}

		echo $this->rowstart;

		$newrow = 0;
		$j = 0;
		$k = 1;
		for($i = $calendarfirst; $i<($calendarlast+86400); $i = $i + 86400)
		{	// loop day by day (86400 seconds = 24 hours)

			if ($newrow == 1)
			{	// We need to start a new row:
				if ($k > $daysinmonth)
				{	// Last day already displayed!
					break;
				}
				echo $this->rowend;
				echo $this->rowstart;
				$newrow = 0;
			}

			if (date('m',$i) != $this->month)
			{	// empty cell
				echo $this->emptycellstart;
				echo $this->emptycellcontent;
				echo $this->emptycellend;
			}
			else
			{	// This day is in this month
				$k = $k + 1;
				$calendarblah = '-'.date('j',$i).'-';
				$calendarthereisapost = ereg($calendarblah, $daysinmonthwithposts);
				$calendartoday = (date('Ymd',$i) == date('Ymd', (time() + (get_settings('time_difference') * 3600))));

				if ($calendarthereisapost)
				{
					echo $this->linkpostcellstart;
					echo '<a href="';
					archive_link( $this->year, $this->month, date('d',$i), '', true, $file, $params );
					echo '">';
				}
				elseif ($calendartoday)
				{
					echo $this->todaycellstart;
				}
				else
				{
					echo $this->cellstart;
				}
				echo date('j',$i);
				if ($calendarthereisapost)
				{
					echo '</a>';
				}
				echo $this->cellend;
			}
			$j = $j + 1;
			if ($j == 7)
			{	// This was the last day of week, we need to start a new row:
				$j = 0;
				$newrow = 1;
			}
		}

		echo $this->rowend;
		echo $this->tableend;

	}

}

?>