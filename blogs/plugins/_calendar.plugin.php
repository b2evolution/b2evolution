<?php
/**
 * This file implements the Calendar plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * @author hansreinders: Hans REINDERS
 * @author cafelog (team)
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Calendar Plugin
 *
 * This plugin displays
 */
class calendar_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */

	var $name = 'Calendar Skin Tag';
	var $code = 'evo_Calr';
	var $priority = 20;
	var $version = '1.9-dev';
	var $author = 'The b2evo Group';



	/**
	 * Init
	 */
	function PluginInit()
	{
		$this->short_desc = T_('This skin tag displays a navigable calendar.');
		$this->long_desc = T_('Days containing posts are highlighted.');

		$this->dbtable = 'T_posts';
		$this->dbprefix = 'post_';
		$this->dbIDname = 'post_ID';
	}


 	/**
	 * Event handler: SkinTag
	 *
	 * @param array Associative array of parameters. Valid keys are:
	 *                - 'block_start' : (Default: '<div class="bSideItem">')
	 *                - 'block_end' : (Default: '</div>')
	 *                - 'title' : (Default: '<h3>'.T_('Calendar').'</h3>')
	 *                - 'displaycaption'
	 *                - 'monthformat'
	 *                - 'linktomontharchive'
	 *                - 'tablestart'
	 *                - 'tableend'
	 *                - 'monthstart'
	 *                - 'monthend'
	 *                - 'rowstart'
	 *                - 'rowend'
	 *                - 'headerdisplay'
	 *                - 'headerrowstart'
	 *                - 'headerrowend'
	 *                - 'headercellstart'
	 *                - 'headercellend'
	 *                - 'cellstart'
	 *                - 'cellend'
	 *                - 'linkpostcellstart'
	 *                - 'linkposttodaycellstart'
	 *                - 'todaycellstart'
	 *                - 'todaycellstartpost'
	 *                - 'navigation' : Where do we want to have the navigation arrows? (Default: 'tfoot')
	 *                - 'browseyears' : boolean  Do we want arrows to move one year at a time?
	 *                - 'postcount_month_cell'
	 *                - 'postcount_month_cell_one'
	 *                - 'postcount_month_atitle'
	 *                - 'postcount_month_atitle_one'
	 *                - 'postcount_year_cell'
	 *                - 'postcount_year_cell_one'
	 *                - 'postcount_year_atitle'
	 *                - 'postcount_year_atitle_one'
	 *                - 'link_type' : 'canonic'|'context' (default: canonic)
	 * @return boolean did we display?
	 */
	function SkinTag( $params )
	{
	 	global $Settings, $month;
		global $blog, $cat, $catsel;
	 	global $show_statuses;
	 	global $author, $assgn, $status;
	 	global $m, $w, $dstart, $timestamp_min, $timestamp_max;
	 	global $s, $sentence, $exact;

		/**
		 * Default params:
		 */
		// This is what will enclose the block in the skin:
		if(!isset($params['block_start'])) $params['block_start'] = '<div class="bSideItem">';
		if(!isset($params['block_end'])) $params['block_end'] = "</div>\n";

		// Title:
		if(!isset($params['title']))
			$params['title'] = '<h3>'.T_('Calendar').'</h3>';


		$Calendar = & new Calendar( $m );

		// TODO: automate with a table inside of Calendatr object. Table should also contain descriptions and default values to display in help screen.
		if( isset($params['displaycaption']) ) $Calendar->set( 'displaycaption', $params['displaycaption'] );
		if( isset($params['monthformat']) ) $Calendar->set( 'monthformat', $params['monthformat'] );
		if( isset($params['linktomontharchive']) ) $Calendar->set( 'linktomontharchive', $params['linktomontharchive'] );
		if( isset($params['tablestart']) ) $Calendar->set( 'tablestart', $params['tablestart'] );
		if( isset($params['tableend']) ) $Calendar->set( 'tableend', $params['tableend'] );
		if( isset($params['monthstart']) ) $Calendar->set( 'monthstart', $params['monthstart'] );
		if( isset($params['monthend']) ) $Calendar->set( 'monthend', $params['monthend'] );
		if( isset($params['rowstart']) ) $Calendar->set( 'rowstart', $params['rowstart'] );
		if( isset($params['rowend']) ) $Calendar->set( 'rowend', $params['rowend'] );
		if( isset($params['headerdisplay']) ) $Calendar->set( 'headerdisplay', $params['headerdisplay'] );
		if( isset($params['headerrowstart']) ) $Calendar->set( 'headerrowstart', $params['headerrowstart'] );
		if( isset($params['headerrowend']) ) $Calendar->set( 'headerrowend', $params['headerrowend'] );
		if( isset($params['headercellstart']) ) $Calendar->set( 'headercellstart', $params['headercellstart'] );
		if( isset($params['headercellend']) ) $Calendar->set( 'headercellend', $params['headercellend'] );
		if( isset($params['cellstart']) ) $Calendar->set( 'cellstart', $params['cellstart'] );
		if( isset($params['cellend']) ) $Calendar->set( 'cellend', $params['cellend'] );
		if( isset($params['emptycellstart']) ) $Calendar->set( 'emptycellstart', $params['emptycellstart'] );
		if( isset($params['emptycellend']) ) $Calendar->set( 'emptycellend', $params['emptycellend'] );
		if( isset($params['emptycellcontent']) ) $Calendar->set( 'emptycellcontent', $params['emptycellcontent'] );
		if( isset($params['linkpostcellstart']) ) $Calendar->set( 'linkpostcellstart', $params['linkpostcellstart'] );
		if( isset($params['linkposttodaycellstart']) ) $Calendar->set( 'linkposttodaycellstart', $params['linkposttodaycellstart'] );
		if( isset($params['todaycellstart']) ) $Calendar->set( 'todaycellstart', $params['todaycellstart'] );
		if( isset($params['todaycellstartpost']) ) $Calendar->set( 'todaycellstartpost', $params['todaycellstartpost'] );
		if( isset($params['navigation']) ) $Calendar->set( 'navigation', $params['navigation'] );
		if( isset($params['browseyears']) ) $Calendar->set( 'browseyears', $params['browseyears'] );
		if( isset($params['postcount_month_cell']) ) $Calendar->set( 'postcount_month_cell', $params['postcount_month_cell'] );
		if( isset($params['postcount_month_cell_one']) ) $Calendar->set( 'postcount_month_cell_one', $params['postcount_month_cell_one'] );
		if( isset($params['postcount_month_atitle']) ) $Calendar->set( 'postcount_month_atitle', $params['postcount_month_atitle'] );
		if( isset($params['postcount_month_atitle_one']) ) $Calendar->set( 'postcount_month_atitle_one', $params['postcount_month_atitle_one'] );
		if( isset($params['postcount_year_cell']) ) $Calendar->set( 'postcount_year_cell', $params['postcount_year_cell'] );
		if( isset($params['postcount_year_cell_one']) ) $Calendar->set( 'postcount_year_cell_one', $params['postcount_year_cell_one'] );
		if( isset($params['postcount_year_atitle']) ) $Calendar->set( 'postcount_year_atitle', $params['postcount_year_atitle'] );
		if( isset($params['postcount_year_atitle_one']) ) $Calendar->set( 'postcount_year_atitle_one', $params['postcount_year_atitle_one'] );
		// Link type:
		if( isset($params['link_type']) ) $Calendar->set( 'link_type', $params['link_type'] );
		if( isset($params['context_isolation']) ) $Calendar->set( 'context_isolation', $params['context_isolation'] );

		echo $params['block_start'];

		echo $params['title'];

		// CONSTRUCT THE WHERE CLAUSE:

		// - - Select a specific Item:
		// $this->ItemQuery->where_ID( $p, $title );

		if( $Calendar->link_type == 'context' )
		{	// We want to preserve the current context:
			// * - - Restrict to selected blog/categories:
			$Calendar->ItemQuery->where_chapter( $blog, $cat, $catsel );

			// * Restrict to the statuses we want to show:
			$Calendar->ItemQuery->where_visibility( $show_statuses );

			// Restrict to selected authors:
			$Calendar->ItemQuery->where_author( $author );

			// Restrict to selected assignees:
			$Calendar->ItemQuery->where_assignees( $assgn );

			// Restrict to selected satuses:
			$Calendar->ItemQuery->where_statuses( $status );

			// - - - + * * if a month is specified in the querystring, load that month:
			$Calendar->ItemQuery->where_datestart( /* NO m */'', /* NO w */'', $dstart, '', $timestamp_min, $timestamp_max );

			// Keyword search stuff:
			$Calendar->ItemQuery->where_keywords( $s, $sentence, $exact );
		}
		else
		{	// We want to preserve only the minimal context:
			// * - - Restrict to selected blog/categories:
			$Calendar->ItemQuery->where_chapter( $blog, '', array() );

			// * Restrict to the statuses we want to show:
			$Calendar->ItemQuery->where_visibility( $show_statuses );

			// - - - + * * if a month is specified in the querystring, load that month:
			$Calendar->ItemQuery->where_datestart( /* NO m */'', /* NO w */'', '', '', $timestamp_min, $timestamp_max );
		}

		// DISPLAY:
		$Calendar->display( );

 		echo $params['block_end'];

		return true;
	}
}


/**
 * Calendar
 *
 * @package evocore
 */
class Calendar
{
	var $year, $month;

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


	var $link_type;
	var $context_isolation;


	/**
	 * Calendar::Calendar(-)
	 *
	 * Constructor
	 *
	 * @param string Month ('YYYYMM'), year ('YYYY'), current ('')
	 */
	function Calendar( $m = '' )
	{
		global $Settings, $localtimenow;

		$this->dbtable = 'T_posts';
		$this->dbprefix = 'post_';
		$this->dbIDname = 'post_ID';

		// OBJECT THAT WILL BE USED TO CONSTRUCT THE WHERE CLAUSE:
		$this->ItemQuery = new ItemQuery( $this->dbtable, $this->dbprefix, $this->dbIDname );	// COPY!!

		$localyearnow = date( 'Y', $localtimenow );
		$localmonthnow = date( 'm', $localtimenow );

		// Find out which month to display:
		if( empty($m) )
		{ // Current month (monthly)
			$this->year = $localyearnow;
			$this->month = $localmonthnow;
			$this->mode = 'month';

			$this->todayIsVisible = true;
		}
		else
		{	// We have requested a specific date
			$this->year = substr($m, 0, 4);
			if (strlen($m) < 6)
			{ // no month provided
				$this->mode = 'year';

				if( $this->year == $localyearnow )
				{ // we display current year, month gets current
					$this->month = $localmonthnow;
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

			$this->todayIsVisible = ( $this->month == $localmonthnow && $this->year == $localyearnow );
		}


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

		// Where do we want to have the navigation arrows?
		$this->navigation = 'tfoot';
		// Do we want to check if there are posts behind the navigation arrows?
		// WARNING: this will slow things down...
		// TODO: $this->check_navigation = false;
		// Do we want arrows to move one year at a time?
		$this->browseyears = true;

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
		$this->postcount_year_cell   = '';
		$this->postcount_year_cell_one   = '';
		$this->postcount_year_atitle = T_('%d posts'); 							// in archive links title tag
		$this->postcount_year_atitle_one = T_('1 post'); 						// in archive links title tag
		/**#@-*/

		// Link type:
		$this->link_type = 'canonic';
		$this->context_isolation = 'm,w,p,title,unit,dstart';

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
	 */
	function display()
	{
		global $DB;
		global $weekday, $weekday_abbrev, $weekday_letter, $month, $month_abbrev;
		global $Settings;

		if( $this->mode == 'month' )
		{
			$end_of_week = ((locale_startofweek() + 7) % 7);

			// fplanque>> note: I am removing the searchframe thing because 1) I don't think it's of any use
			// and 2) it's brutally inefficient! If someone needs this it should be implemented with A SINGLE
			// QUERY which gets the last available post (BTW, I think there is already a function for that somwhere)

			$arc_sql = 'SELECT COUNT(DISTINCT '.$this->dbIDname.') AS item_count,
													YEAR('.$this->dbprefix.'datestart), MONTH('.$this->dbprefix.'datestart),
													DAYOFMONTH('.$this->dbprefix.'datestart) AS myday
									FROM ('.$this->dbtable.' INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID)
										INNER JOIN T_categories ON postcat_cat_ID = cat_ID
									WHERE YEAR('.$this->dbprefix.'datestart) = "'.$this->year.'"
										AND MONTH('.$this->dbprefix.'datestart) = "'.$this->month.'"
										'.$this->ItemQuery->get_where( ' AND ' ).'
									GROUP BY myday '.$this->ItemQuery->get_group_by( ', ' ).'
									ORDER BY '.$this->dbprefix.'datestart DESC';
			// echo $arc_sql;
			// echo $this->ItemQuery->where;
			$arc_result = $DB->get_results( $arc_sql, ARRAY_A );

			foreach( $arc_result as $arc_row )
			{
				if( !isset( $daysinmonthwithposts[ $arc_row['myday'] ] ) )
				{
					$daysinmonthwithposts[ $arc_row['myday'] ] = 0;
				}
				// The '+' situation actually only happens when we have a complex GROUP BY above
				// (multiple categories wcombined with "ALL")
				$daysinmonthwithposts[ $arc_row['myday'] ] += $arc_row['item_count'];
			}

			$daysinmonth = intval(date('t', mktime(0, 0, 0, $this->month, 1, $this->year)));
			// echo 'days in month=', $daysinmonth;


			// caution: offset bug inside (??)
			$datestartofmonth = mktime(0, 0, 0, $this->month, 1, $this->year );
			// echo date( locale_datefmt(), $datestartofmonth );
			$calendarblah = get_weekstartend( $datestartofmonth, locale_startofweek() );
			$calendarfirst = $calendarblah['start'];
/*			if(date('w', $datestartofmonth) == locale_startofweek())
			{
				$calendarfirst = $calendarblah['start'] + 1; // ???
			}
			else
			{
				$calendarfirst = $calendarblah['end'] - 604799; // ???
			}
*/
//			pre_dump( 'calendarfirst', date('Y-m-d', $calendarfirst) );



			$dateendofmonth = mktime(0, 0, 0, $this->month, $daysinmonth, $this->year);
			// echo date( locale_datefmt(), $dateendofmonth );
			$calendarblah = get_weekstartend( $dateendofmonth, locale_startofweek() );
			$calendarlast = $calendarblah['end'];

/*			if(date('w', $dateendofmonth) == $end_of_week)
			{
				$calendarlast = $calendarblah['start'] + 1;
			}
			else
			{
				$calendarlast = $calendarblah['end'] + 10000;
			}
*/
//			pre_dump( 'calendarlast', date('Y-m-d', $calendarlast) );


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
										.$this->ItemQuery->get_where( ' AND ' ).'
									GROUP BY mymonth '.$this->ItemQuery->get_group_by( ', ' ).'
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
					echo '<a href="'.$this->archive_link( $this->year, $this->month, '', '' ).'" title="'.T_('go to month\'s archive').'">';
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
							.$this->archive_link( date('Y'), ( $this->mode == 'month' ? date('m') : '' ), '', '' )
							.'">'.T_('Current')
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
			{	// For each month:
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
					echo '<a href="'.$this->archive_link( $this->year, $i, '', '' ).'"';
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
		{	// Display current month:
			$newrow = 0;
			$j = 0;
			$k = 1;

			for( $i = $calendarfirst; $i <= $calendarlast; $i = $i + 86400 )
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
					$calendartoday = (date('Ymd',$i) == date('Ymd', (time() + $Settings->get('time_difference'))));

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
						echo '<a href="'.$this->archive_link( $this->year, $this->month, date('d',$i), '' ).'"';
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
	 * Create a link to archive, using either URL params or extra path info.
	 *
	 * Can make contextual links.
	 *
	 * @param string year
	 * @param string month
	 * @param string day
	 * @param string week
	 */
	function archive_link( $year, $month, $day = '', $week = '' )
	{
		if( $this->link_type == 'context' )
		{	// We want to preserve context:
			$url_params = 'm='.$year;
			if( !empty( $month ) )
			{
				$url_params .= zeroise($month,2);
				if( !empty( $day ) )
				{
					$url_params .= zeroise($day,2);
				}
			}
			elseif( $week !== '' )  // Note: week # can be 0 !
			{
				$url_params .= '&amp;w='.$week;
			}
			return regenerate_url( $this->context_isolation, $url_params );
		}
		else
		{	// We want a canonic link:
			return archive_link( $year, $month, $day, $week, false );
		}
	}


	/**
	 * Get links to navigate between month / year.
	 *
	 * @todo fplanque>> I think there's a query-waste-fest going on inside here!
	 * @todo fplanque>> Poor factorization.
	 *
	 * @param string 'prev' / 'next'
	 * @return array
	 */
	function getNavLinks( $direction )
	{
		global $DB;

		$r = array();

		// WE NEED SPECIAL QUERY PARAMS WHEN MOVING THOUGH MONTHS ( NO dstart especially! )
		$nav_ItemQuery = & new ItemQuery( $this->dbtable, $this->dbprefix, $this->dbIDname );	// TEMP object
		// Restrict to selected blog/categories:
		$nav_ItemQuery->where_chapter( $this->ItemQuery->blog, $this->ItemQuery->cat, $this->ItemQuery->catsel );
		// Restrict to the statuses we want to show:
		$nav_ItemQuery->where_visibility( $this->ItemQuery->show_statuses );
		// Restrict to selected authors:
		$nav_ItemQuery->where_author( $this->ItemQuery->author );
		// if a month is specified in the querystring, load that month:
		$nav_ItemQuery->where_datestart( /* NO m */'', /* NO w */'', /* NO dstart */'', '', $this->ItemQuery->timestamp_min, $this->ItemQuery->timestamp_max );
		// Keyword search stuff:
		$nav_ItemQuery->where_keywords( $this->ItemQuery->keywords, $this->ItemQuery->phrase, $this->ItemQuery->exact );

		switch( $direction )
		{
			case 'prev':
				if( $this->browseyears )
				{	// We want arrows to move one year at a time
					if( $row = $DB->get_row(
							'SELECT YEAR('.$this->dbprefix.'datestart) AS year,
											MONTH('.$this->dbprefix.'datestart) AS month
								FROM ('.$this->dbtable.' INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID)
									INNER JOIN T_categories ON postcat_cat_ID = cat_ID
								WHERE YEAR('.$this->dbprefix.'datestart) < '.$this->year.'
								'.$nav_ItemQuery->get_where( ' AND ' )
								.$nav_ItemQuery->get_group_by( ' GROUP BY ' ).'
								ORDER BY YEAR('.$this->dbprefix.'datestart) DESC, ABS( '.intval($this->month).' - MONTH('.$this->dbprefix.'datestart) ) ASC
								LIMIT 1', OBJECT, 0, 'Calendar: find prev year with posts' )
						)
					{
						$r[] = '<a href="'
										.$this->archive_link( $row->year, ($this->mode == 'month') ? $row->month : '', '', '' )
										.'" title="'.sprintf(
																	( $this->mode == 'month'
																			? /* Calendar link title to a month in a previous year with posts */ T_('Previous year (%04d-%02d)')
																			: /* Calendar link title to a previous year with posts */ T_('Previous year (%04d)') ),
																	$row->year, $row->month )
										.'">&lt;&lt;</a>';
					}
 					else $r[] = '';
				}
				else $r[] = '';

				if( $this->mode == 'month' )
				{ // We are browsing months, we'll display arrows to move one month at a time:
					if( $row = $DB->get_row(
							'SELECT MONTH('.$this->dbprefix.'datestart) AS month,
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
							'.$nav_ItemQuery->get_where( ' AND ' )
							 .$nav_ItemQuery->get_group_by( ' GROUP BY ' ).'
							ORDER BY YEAR('.$this->dbprefix.'datestart) DESC, MONTH('.$this->dbprefix.'datestart) DESC
							LIMIT 1',
							OBJECT,
							0,
							'Calendar: Find prev month with posts' )
						)
					{
						$r[] = '<a href="'
										.$this->archive_link( $row->year, $row->month, '', '' )
										.'" title="'.sprintf( T_('Previous month (%04d-%02d)'), $row->year, $row->month ).'">&lt;</a>';
					}
					else $r[] = '';
				}
				else $r[] = '';
				break;


			case 'next':
				$r[] = '';

				if( $this->mode == 'month' )
				{ // We are browsing months, we'll display arrows to move one month at a time:
					if( $row = $DB->get_row( 'SELECT MONTH('.$this->dbprefix.'datestart) AS month,
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
                     										'.$nav_ItemQuery->get_where( ' AND ' )
 																				 .$nav_ItemQuery->get_group_by( ' GROUP BY ' ).'
																				ORDER BY YEAR('.$this->dbprefix.'datestart), MONTH('.$this->dbprefix.'datestart) ASC
																				LIMIT 1',
																				OBJECT,
																				0,
																				'Calendar: Find next month with posts' )
						)
					{
						$r[] = '<a href="'
										.$this->archive_link( $row->year, $row->month, '', '' )
										.'" title="'.sprintf( T_('Next month (%04d-%02d)'), $row->year, $row->month ).'">&gt;</a>';
					}
					else $r[] = '';
				}
				else $r[] = '';

				if( $this->browseyears )
				{ // We want arrows to move one year at a time
					if( $row = $DB->get_row( 'SELECT YEAR('.$this->dbprefix.'datestart) AS year,
																							MONTH('.$this->dbprefix.'datestart) AS month
																				FROM ('.$this->dbtable.' INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID)
																					INNER JOIN T_categories ON postcat_cat_ID = cat_ID
																				WHERE YEAR('.$this->dbprefix.'datestart) > '.$this->year.'
                     										'.$nav_ItemQuery->get_where( ' AND ' )
																				 .$nav_ItemQuery->get_group_by( ' GROUP BY ' ).'
																				ORDER BY YEAR('.$this->dbprefix.'datestart) ASC, ABS( '.intval($this->month).' - MONTH('.$this->dbprefix.'datestart) ) ASC
																				LIMIT 1', OBJECT, 0, 'Calendar: find next year with posts' )
						)
					{
						$r[] = '<a href="'
										.$this->archive_link( $row->year, ($this->mode == 'month') ? $row->month : '', '', '' )
										.'" title="'.sprintf(
																	( $this->mode == 'month'
																			? /* Calendar link title to a month in a following year with posts */ T_('Next year (%04d-%02d)')
																			: /* Calendar link title to a following year with posts */ T_('Next year (%04d)') ),
																	$row->year, $row->month )
										.'">&gt;&gt;</a>';
					}
					else $r[] = '';
				}
				else $r[] = '';
				break;
		}

		return $r;
	}

}

/*
 * $Log$
 * Revision 1.21  2006/07/07 21:26:49  blueyed
 * Bumped to 1.9-dev
 *
 * Revision 1.20  2006/07/02 21:53:31  blueyed
 * time difference as seconds instead of hours; validate user#1 on upgrade; bumped new_db_version to 9300.
 *
 * Revision 1.19  2006/06/16 21:30:57  fplanque
 * Started clean numbering of plugin versions (feel free do add dots...)
 *
 * Revision 1.18  2006/05/30 20:25:35  blueyed
 * typo
 *
 * Revision 1.17  2006/05/30 19:39:55  fplanque
 * plugin cleanup
 *
 * Revision 1.16  2006/04/19 20:14:03  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.15  2006/03/17 18:08:37  blueyed
 * removed false todo
 *
 * Revision 1.13  2006/03/12 23:09:27  fplanque
 * doc cleanup
 *
 * Revision 1.12  2006/02/03 21:58:05  fplanque
 * Too many merges, too little time. I can hardly keep up. I'll try to check/debug/fine tune next week...
 *
 * Revision 1.11  2006/01/04 20:34:51  fplanque
 * allow filtering on extra statuses
 *
 * Revision 1.10  2005/12/12 19:22:04  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.9  2005/10/03 18:10:08  fplanque
 * renamed post_ID field
 *
 * Revision 1.8  2005/09/06 19:38:29  fplanque
 * bugfixes
 *
 * Revision 1.7  2005/09/06 17:14:12  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.6  2005/09/01 17:11:46  fplanque
 * no message
 *
 *
 * Merged in _calendar.class.php; history below:
 *
 * Revision 1.18  2005/08/31 19:08:51  fplanque
 * Factorized Item query WHERE clause.
 * Fixed calendar contextual accuracy.
 *
 * Revision 1.17  2005/08/26 18:41:31  fplanque
 * bugfix
 *
 * Revision 1.16  2005/08/26 17:52:02  fplanque
 * abstraction
 *
 * Revision 1.15  2005/08/26 16:15:08  fplanque
 * made the whole calendar contextual (wow am I happy about this functionality! :)
 *
 * Revision 1.14  2005/08/25 11:02:11  fplanque
 * moved calendar to a skintag plugin
 *
 * Revision 1.13  2005/05/09 19:07:03  fplanque
 * bugfixes + global access permission
 *
 * Revision 1.12  2005/04/27 19:05:46  fplanque
 * normalizing, cleanup, documentaion
 *
 * Revision 1.10  2005/03/18 01:40:50  blueyed
 * link to prev month fixed
 *
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