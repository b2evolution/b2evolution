<?php
/**
 * Blog Calendar
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Calendar 
 *
 * @package evocore
 */
class Calendar
{
	var $blog;
	var $year, $month;
	var $specific;					// WE ASKED FOR A SPECIFIC MONTH
	
	var $mode;  						// 'month' or 'year'
	
	var $where;
	var $request;						// SQL query string
	var $result;						// Result set
	var $result_num_rows;		// Number of rows in result set

	var $displaycaption;
	var $monthformat;
	var $monthstart;
	var $monthend;
	var $linktomontharchive;
	/**
	 * Where to do the navigation
	 * 
	 * 'caption' or 'tfoot';
	 *
	 * @var string
	 */
	var $navigation = 'caption';	

	var $tablestart;
	var $tableend;

	var $rowstart;
	var $rowend;

	var $headerdisplay;
	var $headerrowstart;
	var $headerrowend;
	var $headercellstart;
	var $headercellend;

	var $cellstart;
	var $cellend;

	var $emptycellstart;
	var $emptycellend;

	var $emptycellcontent;

	var $searchframe;

	var $browseyears;
	
	/*
	 * Calendar::Calendar(-)
	 *
	 * Constructor
	 */
	function Calendar(
		$blog = 1,
		$m = '',
		$show_statuses = array(),
		$timestamp_min = '',		// Do not show posts before this timestamp
		$timestamp_max = 'now'  )	// Do not show posts after this timestamp
	{
		global $Settings;
		
		$this->blog = $blog;

		// Find out which month to display:
		if( empty($m) )
		{
			$this->year = date('Y');
			$this->month = date('m');
			$this->mode = 'month';
		}
		else
		{
			$this->specific = true;
			$this->year = substr($m, 0, 4);
			if (strlen($m) < 6)
			{ // no month provided
				$this->mode = 'year';
				if( $this->year == date('Y') )
				{ // we display current year, month gets current
					$this->month = date('m');
				}
				else
				{ // highlight no month, when not current year
					$this->month = '';
				}
			}
			else
			{
				$this->month = substr($m, 4, 2);
				$this->mode = 'month';
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

		$this->where = $where;


		// Default styling:
		$this->displaycaption = 1;	// set this to 0 if you don't want to display the month name
		$this->monthformat = 'F Y';
		$this->linktomontharchive = true;  // month displayed as link to month' archive

		$this->tablestart = '<table class="bCalendarTable" cellspacing="0" summary="Monthly calendar with links to each day\'s posts">'."\n";
		$this->tableend = '</table>';

		$this->monthstart = '<caption class="bCalendarCaption">';
		$this->monthend = "</caption>\n";

		$this->rowstart = '<tr class="bCalendarRow">' . "\n";
		$this->rowend = "</tr>\n";

		$this->headerdisplay = 'D';	 // D => 'Fri'; e => 'F', l (lowercase l) => 'Friday'
		// These codes are twisted because they're the same as for date formats.
		// set this to 0 or '' if you don't want to display the "Mon Tue Wed..." header
		
		$this->headerrowstart = '<thead><tr class="bCalendarRow">' . "\n";
		$this->headerrowend = "</tr></thead>\n";
		$this->headercellstart = '<th class="bCalendarHeaderCell" abbr="[abbr]" scope="col" title="[abbr]">';	// please leave [abbr] there !
		$this->headercellend = "</th>\n";

		$this->cellstart = '<td class="bCalendarCell">';
		$this->cellend = "</td>\n";

		$this->emptycellstart = '<td class="bCalendarEmptyCell">';
		$this->emptycellend = "</td>\n";
		$this->emptycellcontent = '&nbsp;';

		$this->linkpostcellstart = '<td class="bCalendarLinkPost">';
		$this->linkposttodaycellstart = '<td class="bCalendarLinkPostToday">';
		$this->todaycellstart = '<td id="bCalendarToday">';
		$this->todaycellstartpost = '<td id="bCalendarToday" class="bCalendarLinkPost">';

		$this->searchframe = 12;	// How many month will we search back for a post before we give up

		$this->browseyears = ($this->mode == 'year');  // browsing years from Calendar's caption
		
		/**#@+
		 * Display number of posts with days/months
		 *
		 * - set to '' (empty) to disable
		 * - %d gets replaced with the number of posts on that day/month
		 */
		$this->postcount_month_cell = '';                           // in table cell (behind day)
		$this->postcount_month_cell_one = '';                       //  -- " -- [for single post]
		$this->postcount_month_atitle = T_('%d posts'); 						// in archive links title tag
		$this->postcount_month_atitle_one = T_('1 post');  					//  -- " -- [for single post]
		#$this->postcount_year_cell = ' (%d)';                      // in table cell (behind abbr of month)
		$this->postcount_year_cell   = '';
		$this->postcount_year_cell_one   = '';
		$this->postcount_year_atitle = T_('%d posts'); 							// in archive links title tag
		$this->postcount_year_atitle_one = T_('1 post'); 						// in archive links title tag
		/**#@-*/
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

	/**
	 * Calendar->display(-)
	 *
	 * display the calendar.
	 *
	 * @param string
	 * @param string
	 */
	function display( $file = '', $params = '' )	// Page to use for links
	{
		global $DB;
		global $tableposts, $tablepostcats, $tablecategories;
		global $weekday, $weekday_abbrev, $weekday_letter, $month, $month_abbrev;
		global $start_of_week;
		global $Settings;

		if( $this->mode == 'month' )
		{
			$end_of_week = (($start_of_week + 7) % 7);
	
			// Find a month with posts
			$searchmonth = $this->month;
			$searchyear = $this->year;
			for( $i = 0; $i < $this->searchframe; $i++ )
			{
				$arc_sql = "SELECT COUNT(DISTINCT ID), YEAR(post_issue_date), MONTH(post_issue_date), DAYOFMONTH(post_issue_date) AS myday".
						" FROM ($tableposts INNER JOIN $tablepostcats ON ID = postcat_post_ID)".
						" INNER JOIN $tablecategories ON postcat_cat_ID = cat_ID".
						" WHERE MONTH(post_issue_date) = '$searchmonth' AND YEAR(post_issue_date) = '$searchyear' ".$this->where.
						" GROUP BY myday".
						" ORDER BY post_issue_date DESC";
				$arc_result = $DB->get_results( $arc_sql, ARRAY_A );
				
				if( $DB->num_rows > 0 )
				{	// OK we have a month with posts!
					foreach( $arc_result as $arc_row )
					{
						$daysinmonthwithposts[ $arc_row['myday'] ] = $arc_row['COUNT(DISTINCT ID)'];
					}
					$this->month = $searchmonth;
					$this->year = $searchyear;
					break; // Don't search any further!
				}
				elseif ($this->specific)
				{	// No post, but we asked for a specific month to be displayed
					break; // Don't search any further!
				}
				else
				{	// No, post, let's search in previous month!
					$searchmonth = zeroise(intval($searchmonth)-1,2);
					if ($searchmonth == '00') {
						$searchmonth = '12';
						$searchyear = ''.(intval($searchyear)-1);
					}
				}
			}
	
			// echo $this->month,'.',$this->year;
	
			$daysinmonth = intval(date('t', mktime(0, 0, 0, $this->month, 1, $this->year)));
			// echo 'days in month=', $daysinmonth;
			$datestartofmonth = $this->year.'-'.$this->month.'-01';
			$dateendofmonth = $this->year.'-'.$this->month.'-'.$daysinmonth;
	
			// caution: offset bug inside
			$calendarblah = get_weekstartend($datestartofmonth, $start_of_week);
			if (mysql2date('w', $datestartofmonth) == $start_of_week) {
				$calendarfirst = $calendarblah['start'] + 1 + 3600;     // adjust for daylight savings time
			} else {
				$calendarfirst = $calendarblah['end'] - 604799 + 3600;  // adjust for daylight savings time
			}
			//echo 'calendarfirst=', $calendarfirst;
	
			$calendarblah = get_weekstartend($dateendofmonth, $end_of_week);
			if (mysql2date('w', $dateendofmonth) == $end_of_week) {
				$calendarlast = $calendarblah['start'] + 1;
			} else {
				$calendarlast = $calendarblah['end'] + 10000;
			}
			//echo 'calendarlast=', $calendarlast;
	
			// here the offset bug is corrected
			if ((intval(date('d', $calendarfirst)) > 1) && (intval(date('m', $calendarfirst)) == intval($this->month))) {
				$calendarfirst = $calendarfirst - 604800;
			}
	
			// Create links to previous/next month
			$previous_month_link = '<a href="'.
				archive_link( ($this->month > 1) ? $this->year : ($this->year - 1),	($this->month > 1) ? ($this->month - 1) : 12, '', '', false, $file, $params )
				.'" title="'.T_('previous month').'">&lt;</a>';
	
			$next_month_link = '<a href="'.
				archive_link( ($this->month < 12) ? $this->year : ($this->year + 1), ($this->month < 12) ? ($this->month + 1) : 1, '', '', false, $file, $params )
				.'" title="'.T_('next month').'">&gt;</a>';
		}
		else
		{ // mode is 'year'
			// Find months with posts
			$arc_sql = "SELECT COUNT(DISTINCT ID), MONTH(post_issue_date) AS mymonth ".
						"FROM ($tableposts INNER JOIN $tablepostcats ON ID = postcat_post_ID) ".
						"INNER JOIN $tablecategories ON postcat_cat_ID = cat_ID ".
						"WHERE YEAR(post_issue_date) = '$this->year' ".$this->where.
						" GROUP BY mymonth".
						" ORDER BY post_issue_date DESC";
	
			$arc_result = $DB->get_results( $arc_sql, ARRAY_A );
			
			if( $DB->num_rows > 0 )
			{	// OK we have a month with posts!
				foreach( $arc_result as $arc_row )
				{
					$monthswithposts[ $arc_row['mymonth'] ] = $arc_row['COUNT(DISTINCT ID)'];
				}
			}
		}
		
		if( $this->browseyears )
		{ // create links to previous/next year
			$previous_year_link = '<a href="'.
				archive_link( $this->year - 1, ($this->mode == 'month') ? $this->month : '', '', '', false, $file, $params )
				.'" title="'.T_('previous year').'">&lt;&lt;</a>&nbsp;&nbsp;';
			$next_year_link = '&nbsp;&nbsp;<a href="'.
				archive_link( $this->year + 1, ($this->mode == 'month') ? $this->month : '', '', '', false, $file, $params )
				.'" title="'.T_('next year').'">&gt;&gt;</a>';
		}


		// ** display everything **

		echo $this->tablestart;

		// CAPTION :

		if( $this->displaycaption )
		{	// caption:
			echo $this->monthstart;
			
			if( $this->navigation == 'caption' )
			{	// Link to previous year:
				echo isset( $previous_year_link ) ? $previous_year_link : '';
			}
			
			if( $this->mode == 'month' )
			{	// MONTH CAPTION:

				if( $this->navigation == 'caption' )
				{	// Link to previous month:
					echo $previous_month_link.'&nbsp;&nbsp;';
				}

				if( $this->linktomontharchive )
				{	// chosen month with link to archives
					echo '<a href="'.archive_link( $this->year, $this->month, '', '', false, $file, $params ).'" title="'.T_('go to month\'s archive').'">';
				}

				echo date_i18n($this->monthformat, mktime(0, 0, 0, $this->month, 1, $this->year));

				if( $this->linktomontharchive )
				{	// close link to month archive
					echo '</a>';
				}
			
				if( $this->navigation == 'caption' )
				{	// Link to next month:
					echo '&nbsp;&nbsp;'.$next_month_link;
				}
			}
			else
			{	// YEAR CAPTION:
				echo date_i18n('Y', mktime(0, 0, 0, 1, 1, $this->year)); // display year
			}
			
			if( $this->navigation == 'caption' )
			{	// Link to next year:
				echo isset( $next_year_link ) ? $next_year_link : '';
			}
			
			echo $this->monthend;
		}

		// HEADER :

		if( !empty($this->headerdisplay) && ($this->mode == 'month') )
		{	// Weekdays:
			echo $this->headerrowstart;

			for ($i = $start_of_week; $i < ($start_of_week + 7); $i = $i + 1)
			{
				echo str_replace('[abbr]', T_($weekday[($i % 7)]), $this->headercellstart);
				switch( $this->headerdisplay )
				{
					case 'e':
						// e => 'F'
						echo T_($weekday_letter[($i % 7)]);
						break;
						
					case 'l':
						// l (lowercase l) => 'Friday'
						echo T_($weekday[($i % 7)]);
						break;
			
					default:	// Backward compatibility: any non emty value will display this
						// D => 'Fri'
						echo T_($weekday_abbrev[($i % 7)]);
				}

				echo $this->headercellend;
			}
			
			echo $this->headerrowend;
		}

		if( $this->navigation == 'tfoot' )
		{	// We want to display navigation in the table footer:
			// TODO: YEAR MODE support
			echo "<tfoot>\n";
			echo "<tr>\n";
			echo '<td colspan="'.(($this->mode == 'month') ? '3' : '2' ).'" id="prev">';
			echo isset( $previous_year_link ) ? $previous_year_link : '';
			echo isset( $previous_month_link ) ? $previous_month_link : '';
			echo "</td>\n";
			if( $this->mode == 'month' ) echo '<td class="pad">&nbsp;</td>'."\n";
			echo '<td colspan="'.(($this->mode == 'month') ? '3' : '2' ).'" id="next">';
			echo isset( $next_month_link ) ? $next_month_link : '';
			echo isset( $next_year_link ) ? $next_year_link : '';
			echo "</td>\n";
			echo "</tr>\n";
			echo "</tfoot>\n";
		}

		// REAL TABLE DATA :

		echo $this->rowstart;

		if( $this->mode == 'year' )
		{
			for ($i = 1; $i < 13; $i = $i + 1)
			{
				if( isset($monthswithposts[ $i ]) )
				{
					if( $this->month == $i )
					{
						echo $this->todaycellstartpost;
					}
					else
					{
						echo $this->linkpostcellstart;
					}
					echo '<a href="';
					archive_link( $this->year, $i, '', '', true, $file, $params );
					echo '"';
					if( $monthswithposts[ $i ] > 1 && !empty($this->postcount_year_atitle) )
					{ // display postcount
						echo ' title="'.sprintf($this->postcount_year_atitle, $monthswithposts[ $i ]).'"';
					}
					elseif( !empty($this->postcount_year_atitle_one) )
					{ // display postcount for one post
						echo ' title="'.sprintf($this->postcount_year_atitle_one, 1).'"';
					}
					echo '>';
				}
				elseif( $this->month == $i )
				{ // current month
					echo $this->todaycellstart;
				}
				else
				{
					echo $this->cellstart;
				}
				echo T_($month_abbrev[ zeroise($i, 2) ]);
				
				if( isset($monthswithposts[ $i ]) )
				{ // close anchor and show how many posts we have for this month
					if( $monthswithposts[ $i ] > 1 && !empty($this->postcount_year_cell) )
					{ // display postcount
						printf($this->postcount_year_cell, $monthswithposts[ $i ]);
					}
					elseif( !empty($this->postcount_year_cell_one) )
					{ // display postcount for one post
						printf($this->postcount_year_cell_one, 1);
					}
					echo '</a>';
				}
				echo $this->cellend;
				if( $i == 4 || $i == 8 )
				{ // new row
					echo $this->rowend.$this->rowstart;
				}
			}
		}
		else // mode == 'month'
		{	
			$newrow = 0;
			$j = 0;
			$k = 1;
			
			for($i = $calendarfirst; $i < ($calendarlast + 86400); $i = $i + 86400)
			{	// loop day by day (86400 seconds = 24 hours)
				if ($newrow == 1)
				{	// We need to start a new row:
					if( $k > $daysinmonth )
					{	// Last day already displayed!
						break;
					}
					echo $this->rowend;
					echo $this->rowstart;
					$newrow = 0;
				}
	
				if (date('m', $i) != $this->month)
				{	// empty cell
					echo $this->emptycellstart;
					echo $this->emptycellcontent;
					echo $this->emptycellend;
				}
				else
				{	// This day is in this month
					$k = $k + 1;
					$calendartoday = (date('Ymd',$i) == date('Ymd', (time() + ($Settings->get('time_difference') * 3600))));
	
					if( isset($daysinmonthwithposts[ date('j', $i) ]) )
					{
						if( $calendartoday )
						{
							echo $this->todaycellstartpost;
						}
						else
						{
							echo $this->linkpostcellstart;
						}
						echo '<a href="';
						archive_link( $this->year, $this->month, date('d',$i), '', true, $file, $params );
						echo '"';
						if( $daysinmonthwithposts[ date('j', $i) ] > 1 && !empty($this->postcount_month_atitle) )
						{ // display postcount
							echo ' title="'.sprintf($this->postcount_month_atitle, $daysinmonthwithposts[ date('j', $i) ]).'"';
						}
						elseif( !empty($this->postcount_month_atitle_one) )
						{ // display postcount for one post
							echo ' title="'.sprintf($this->postcount_month_atitle_one, 1).'"';
						}
						echo '>';
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
					if( isset($daysinmonthwithposts[ date('j', $i) ]) )
					{
						if( $daysinmonthwithposts[ date('j', $i) ] > 1 && !empty($this->postcount_month_cell) )
						{ // display postcount
							printf($this->postcount_month_cell, $daysinmonthwithposts[ date('j', $i) ]);
						}
						elseif( !empty($this->postcount_month_cell_one) )
						{ // display postcount for one post
							printf($this->postcount_month_cell_one, 1);
						}
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
			} // loop day by day
		} // mode == 'month'
		
		echo $this->rowend;
		
		echo $this->tableend;
	}  // display(-)

}

?>