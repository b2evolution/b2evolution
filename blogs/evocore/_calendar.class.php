<?php
/**
 * This file implements the Calendar class designed to report blog posts on a calendar.
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
 * @author hansreinders: Hans REINDERS
 * @author cafelog (team)
 *
 * @todo Optimize queries! (cache)
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

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
	var $navigation = 'tfoot';

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

	/**
	 * Do we want to browse years in the caption? True by default for mode == year,
	 * false for mode == month (gets set in constructor).
	 * @var boolean
	 */
	var $browseyears;

	/**
	 * @var boolean Is today in the displayed frame?
	 * @access protected
	 */
	var $todayIsVisible;


	/**
	 * Calendar::Calendar(-)
	 *
	 * Constructor
	 *
	 * @param int Blog ID
	 * @param string Month ('YYYYMM'), year ('YYYY'), current ('')
	 * @param array
	 * @param string Do not show posts _before_ this timestamp.
	 * @param string Do not show posts _after_ this timestamp.
	 * @param string DB table name to use.
	 * @param string Column name prefix.
	 * @param string Name of the DB ID.
	 */
	function Calendar(
		$blog = 1,
		$m = '',
		$show_statuses = array(),
		$timestamp_min = '',
		$timestamp_max = 'now',
		$dbtable = 'T_posts',
		$dbprefix = 'post_',
		$dbIDname = 'ID' )
	{
		global $Settings;

		$this->blog = $blog;
		$this->dbtable = $dbtable;
		$this->dbprefix = $dbprefix;
		$this->dbIDname = $dbIDname;

		// Find out which month to display:
		if( empty($m) )
		{ // Current month (monthly)
			$this->year = date('Y');
			$this->month = date('m');
			$this->mode = 'month';

			$this->todayIsVisible = true;
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

			$this->todayIsVisible = ( $this->month == date('m') && $this->year == date('Y') );
		}

		// CONSTRUCT THE WHERE CLAUSE:
		/*
		 * ----------------------------------------------------
		 *  Restrict to the statuses we want to show:
		 * ----------------------------------------------------
		 */
		/**
		 * @var array Used to narrow posts (status, blog)
		 */
		$this->where_narrow = ' AND '.statuses_where_clause( $show_statuses, $dbprefix );

		// Do we need to restrict categories:
		if( $blog > 1 )
		{ // Blog #1 aggregates all
			$this->where_narrow .= ' AND cat_blog_ID = '.$blog;
		}


		$where_time = array();

		// Restrict to timestamp limits:
		if( $timestamp_min == 'now' ) $timestamp_min = time();
		if( !empty($timestamp_min) )
		{ // Hide posts before
			$date_min = date('Y-m-d H:i:s', $timestamp_min + ($Settings->get('time_difference') * 3600) );
			$where_time[] = $dbprefix.'datestart >= \''.$date_min.'\'';
		}
		if( $timestamp_max == 'now' ) $timestamp_max = time();
		if( !empty($timestamp_max) )
		{ // Hide posts after
			$date_max = date('Y-m-d H:i:s', $timestamp_max + ($Settings->get('time_difference') * 3600) );
			$where_time[] = $dbprefix.'datestart <= \''.$date_max.'\'';
		}
		$this->where_time = $where_time ? ' AND '.implode($where_time) : '';


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

		if( is_null( $this->browseyears ) )
		{
			$this->browseyears = ($this->mode == 'year');  // browsing years from Calendar's navigation
		}

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
	 * Display the calendar.
	 *
	 * @todo If a specific day (mode == month) or month (mode == year) is selected, apply another class (default to some border)
	 *
	 * @uses archive_link()
	 * @param string file to use for links
	 * @param string GET params for file
	 */
	function display( $file = '', $params = '' )
	{
		global $DB;
		global $weekday, $weekday_abbrev, $weekday_letter, $month, $month_abbrev;
		global $Settings;

		$this->file = $file;
		$this->params = $params;

		if( $this->mode == 'month' )
		{
			$end_of_week = ((locale_startofweek() + 7) % 7);

			// Find a month with posts
			$searchmonth = $this->month;
			$searchyear = $this->year;
			for( $i = 0; $i < $this->searchframe; $i++ )
			{
				$arc_sql = 'SELECT COUNT(DISTINCT '.$this->dbIDname.') AS item_count,
														YEAR('.$this->dbprefix.'datestart), MONTH('.$this->dbprefix.'datestart),
														DAYOFMONTH('.$this->dbprefix.'datestart) AS myday
										FROM ('.$this->dbtable.' INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID)
											INNER JOIN T_categories ON postcat_cat_ID = cat_ID
										WHERE MONTH('.$this->dbprefix.'datestart) = "'.$searchmonth.'"
											AND YEAR('.$this->dbprefix.'datestart) = "'.$searchyear.'" '
											.$this->where_narrow
											.$this->where_time.'
										GROUP BY myday
										ORDER BY '.$this->dbprefix.'datestart DESC';
				$arc_result = $DB->get_results( $arc_sql, ARRAY_A );

				if( $DB->num_rows > 0 )
				{ // OK we have a month with posts!
					foreach( $arc_result as $arc_row )
					{
						$daysinmonthwithposts[ $arc_row['myday'] ] = $arc_row['item_count'];
					}
					$this->month = $searchmonth;
					$this->year = $searchyear;
					break; // Don't search any further!
				}
				elseif ($this->specific)
				{ // No post, but we asked for a specific month to be displayed
					break; // Don't search any further!
				}
				else
				{ // No, post, let's search in previous month!
					$searchmonth = zeroise(intval($searchmonth)-1,2);
					if ($searchmonth == '00')
					{ // handle year change
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
			$calendarblah = get_weekstartend($datestartofmonth, locale_startofweek());
			if (mysql2date('w', $datestartofmonth) == locale_startofweek())
			{
				$calendarfirst = $calendarblah['start'] + 1 + 3600;     // adjust for daylight savings time
			}
			else
			{
				$calendarfirst = $calendarblah['end'] - 604799 + 3600;  // adjust for daylight savings time
			}
			#pre_dump( 'calendarfirst', date('Y-m-d', $calendarfirst) );

			$calendarblah = get_weekstartend($dateendofmonth, $end_of_week);
			if (mysql2date('w', $dateendofmonth) == $end_of_week) {
				$calendarlast = $calendarblah['start'] + 1;
			} else {
				$calendarlast = $calendarblah['end'] + 10000;
			}
			#pre_dump( 'calendarlast', date('Y-m-d', $calendarlast) );

			// here the offset bug is corrected
			if( (intval(date('d', $calendarfirst)) > 1) && (intval(date('m', $calendarfirst)) == intval($this->month)) )
			{
				#pre_dump( 'with offset bug', date('Y-m-d', $calendarfirst) );
				$calendarfirst = $calendarfirst - 604800;
				#pre_dump( 'without offset bug', date('Y-m-d', $calendarfirst) );
			}
		}
		else
		{ // mode is 'year'
			// Find months with posts
			$arc_sql = 'SELECT COUNT(DISTINCT '.$this->dbIDname.') AS item_count, MONTH('.$this->dbprefix.'datestart) AS mymonth
									FROM ('.$this->dbtable.' INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID)
										INNER JOIN T_categories ON postcat_cat_ID = cat_ID
									WHERE YEAR('.$this->dbprefix.'datestart) = "'.$this->year.'" '
										.$this->where_narrow
										.$this->where_time.'
									GROUP BY mymonth
									ORDER BY '.$this->dbprefix.'datestart DESC';

			$arc_result = $DB->get_results( $arc_sql, ARRAY_A );

			if( $DB->num_rows > 0 )
			{ // OK we have a month with posts!
				foreach( $arc_result as $arc_row )
				{
					$monthswithposts[ $arc_row['mymonth'] ] = $arc_row['item_count'];
				}
			}
		}


		// ** display everything **

		echo $this->tablestart;

		// CAPTION :

		if( $this->displaycaption )
		{ // caption:
			echo $this->monthstart;

			if( $this->navigation == 'caption' )
			{
				echo implode( '&nbsp;', $this->getNavLinks( 'prev' ) );
			}

			if( $this->mode == 'month' )
			{ // MONTH CAPTION:
				if( $this->linktomontharchive )
				{ // chosen month with link to archives
					echo '<a href="'.archive_link( $this->year, $this->month, '', '', false, $this->file, $this->params ).'" title="'.T_('go to month\'s archive').'">';
				}

				echo date_i18n($this->monthformat, mktime(0, 0, 0, $this->month, 1, $this->year));

				if( $this->linktomontharchive )
				{ // close link to month archive
					echo '</a>';
				}
			}
			else
			{ // YEAR CAPTION:
				echo date_i18n('Y', mktime(0, 0, 0, 1, 1, $this->year)); // display year
			}

			if( $this->navigation == 'caption' )
			{
				echo implode( '&nbsp;', $this->getNavLinks( 'next' ) );
			}

			echo $this->monthend;
		}

		// HEADER :

		if( !empty($this->headerdisplay) && ($this->mode == 'month') )
		{ // Weekdays:
			echo $this->headerrowstart;

			for( $i = locale_startofweek(), $j = $i + 7; $i < $j; $i = $i + 1)
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
		{ // We want to display navigation in the table footer:
			echo "<tfoot>\n";
			echo "<tr>\n";
			echo '<td colspan="'.( ( $this->mode == 'month' ? 2 : 1 ) + (int)$this->todayIsVisible ).'" id="prev">';
			echo implode( '&nbsp;', $this->getNavLinks( 'prev' ) );
			echo "</td>\n";

			if( $this->todayIsVisible )
			{
				if( $this->mode == 'month' )
				{
					echo '<td class="pad">&nbsp;</td>'."\n";
				}
			}
			else
			{
				echo '<td colspan="'.( $this->mode == 'month' ? '3' : '2' ).'" class="center"><a href="'
							.archive_link( date('Y'), ( $this->mode == 'month' ? date('m') : '' ), '', '', false, $this->file, $this->params )
							.'">'.T_('Today') // TODO: not really "Today", but where today is included.. better name? title attrib..
							.'</a></td>';
			}
			echo '<td colspan="'.( ( $this->mode == 'month' ? 2 : 1 ) + (int)$this->todayIsVisible ).'" id="next">';
			echo implode( '&nbsp;', $this->getNavLinks( 'next' ) );
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
					archive_link( $this->year, $i, '', '', true, $this->file, $this->params );
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

			for( $i = $calendarfirst; $i < ($calendarlast + 86400); $i = $i + 86400 )
			{ // loop day by day (86400 seconds = 24 hours)
				if ($newrow == 1)
				{ // We need to start a new row:
					if( $k > $daysinmonth )
					{ // Last day already displayed!
						break;
					}
					echo $this->rowend;
					echo $this->rowstart;
					$newrow = 0;
				}

				if (date('m', $i) != $this->month)
				{ // empty cell
					echo $this->emptycellstart;
					echo $this->emptycellcontent;
					echo $this->emptycellend;
				}
				else
				{ // This day is in this month
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
						archive_link( $this->year, $this->month, date('d',$i), '', true, $this->file, $this->params );
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
				{ // This was the last day of week, we need to start a new row:
					$j = 0;
					$newrow = 1;
				}
			} // loop day by day
		} // mode == 'month'

		echo $this->rowend;

		echo $this->tableend;
	}  // display(-)



	/**
	 * Get links to navigate between month / year.
	 *
	 * @param string 'prev' / 'next'
	 * @return array
	 */
	function getNavLinks( $direction )
	{
		global $DB;

		$r = array();

		switch( $direction )
		{
			case 'prev':
				if( $this->browseyears
						&& ( $row = $DB->get_row( 'SELECT YEAR('.$this->dbprefix.'datestart) AS year,
																							MONTH('.$this->dbprefix.'datestart) AS month
																				FROM ('.$this->dbtable.' INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID)
																					INNER JOIN T_categories ON postcat_cat_ID = cat_ID
																				WHERE YEAR('.$this->dbprefix.'datestart) < '.$this->year.'
																				'.$this->where_narrow.'
																				ORDER BY YEAR('.$this->dbprefix.'datestart) DESC, ABS( '.intval($this->month).' - MONTH('.$this->dbprefix.'datestart) ) ASC
																				LIMIT 1', OBJECT, 0, 'Calendar: find prev year with posts' ) )
					)
				{
					$r[] = '<a href="'
									.archive_link( $row->year, ($this->mode == 'month') ? $row->month : '', '', '', false, $this->file, $this->params )
									.'" title="'.sprintf(
																( $this->mode == 'month'
																		? /* Calendar link title to a month in a previous year with posts */ T_('Previous year (%04d-%02d)')
																		: /* Calendar link title to a previous year with posts */ T_('Previous year (%04d)') ),
																$row->year, $row->month )
									.'">&lt;&lt;</a>';
				}
				else
				{
					$r[] = '';
				}

				if( $this->mode == 'month'
						&& ( $row = $DB->get_row( 'SELECT MONTH('.$this->dbprefix.'datestart) AS month,
																								YEAR('.$this->dbprefix.'datestart) AS year
																				FROM ('.$this->dbtable.' INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID)
																					INNER JOIN T_categories ON postcat_cat_ID = cat_ID
																				WHERE
																				(
																					YEAR('.$this->dbprefix.'datestart) < '.($this->year).'
																					OR ( YEAR('.$this->dbprefix.'datestart) = '.($this->year).'
																								AND MONTH('.$this->dbprefix.'datestart) < '.($this->month).'
																							)
																				)
																				'.$this->where_narrow.'
																				ORDER BY YEAR('.$this->dbprefix.'datestart) DESC, MONTH('.$this->dbprefix.'datestart) ASC
																				LIMIT 1',
																				OBJECT,
																				0,
																				'Calendar: Find next month with posts' ) ) )
				{
					$r[] = '<a href="'
									.archive_link( $row->year, $row->month, '', '', false, $this->file, $this->params )
									.'" title="'.sprintf( T_('Previous month (%04d-%02d)'), $row->year, $row->month ).'">&lt;</a>';
				}
				else
				{
					$r[] = '';
				}
				break;


			case 'next':
				if( $this->mode == 'month'
						&& ( $row = $DB->get_row( 'SELECT MONTH('.$this->dbprefix.'datestart) AS month,
																								YEAR('.$this->dbprefix.'datestart) AS year
																				FROM ('.$this->dbtable.' INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID)
																					INNER JOIN T_categories ON postcat_cat_ID = cat_ID
																				WHERE
																				(
																					YEAR('.$this->dbprefix.'datestart) > '.($this->year).'
																					OR ( YEAR('.$this->dbprefix.'datestart) = '.($this->year).'
																								AND MONTH('.$this->dbprefix.'datestart) > '.($this->month).'
																							)
																				)
																				'.$this->where_narrow.'
																				ORDER BY YEAR('.$this->dbprefix.'datestart), MONTH('.$this->dbprefix.'datestart) ASC
																				LIMIT 1',
																				OBJECT,
																				0,
																				'Calendar: Find next month with posts' ) ) )
				{
					$r[] = '<a href="'
									.archive_link( $row->year, $row->month, '', '', false, $this->file, $this->params )
									.'" title="'.sprintf( T_('Next month (%04d-%02d)'), $row->year, $row->month ).'">&gt;</a>';
				}
				else
				{
					$r[] = '';
				}

				if( $this->browseyears
						&& ( $row = $DB->get_row( 'SELECT YEAR('.$this->dbprefix.'datestart) AS year,
																							MONTH('.$this->dbprefix.'datestart) AS month
																				FROM ('.$this->dbtable.' INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID)
																					INNER JOIN T_categories ON postcat_cat_ID = cat_ID
																				WHERE YEAR('.$this->dbprefix.'datestart) > '.$this->year.'
																				'.$this->where_narrow.'
																				ORDER BY YEAR('.$this->dbprefix.'datestart) ASC, ABS( '.intval($this->month).' - MONTH('.$this->dbprefix.'datestart) ) ASC
																				LIMIT 1', OBJECT, 0, 'Calendar: find next year with posts' ) )
					)
				{
					$r[] = '<a href="'
									.archive_link( $row->year, ($this->mode == 'month') ? $row->month : '', '', '', false, $this->file, $this->params )
									.'" title="'.sprintf(
																( $this->mode == 'month'
																		? /* Calendar link title to a month in a following year with posts */ T_('Next year (%04d-%02d)')
																		: /* Calendar link title to a following year with posts */ T_('Next year (%04d)') ),
																$row->year, $row->month )
									.'">&gt;&gt;</a>';
				}
				else
				{
					$r[] = '';
				}

				break;
		}

		return $r;
	}

}

/*
 * $Log$
 * Revision 1.9  2005/03/18 00:29:32  blueyed
 * navigation: only link to month/year with posts
 *
 * Revision 1.8  2005/03/07 17:08:20  fplanque
 * made more generic
 *
 * Revision 1.7  2005/02/28 09:06:32  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.6  2005/02/23 19:31:58  blueyed
 * get_weekstartend() fixed
 *
 * Revision 1.5  2005/02/23 04:26:18  blueyed
 * moved global $start_of_week into $locales properties
 *
 * Revision 1.4  2005/02/12 03:58:44  blueyed
 * default to $navigation = 'tfoot', fixed queries that find posts in month or on day, refactored navigation link generation
 *
 * Revision 1.3  2004/12/13 21:29:58  fplanque
 * refactoring
 *
 * Revision 1.2  2004/10/14 18:31:24  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.34  2004/10/11 19:02:04  fplanque
 * Edited code documentation.
 *
 * Revision 1.6  2004/1/15 20:49:14  hansreinders
 * Add more flexibility to calendar
 */
?>