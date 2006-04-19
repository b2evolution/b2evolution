<?php
/**
 * This file implements the ItemQuery class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../../_misc/_sql.class.php';


/**
 * ItemQuery: help constructing queries on Items
 */
class ItemQuery extends SQL
{
	var $p;
	var $title;
	var $blog;
	var $cat;
	var $catsel;
	var $show_statuses;
	var $author;
	var $assignees;
	var $statuses;
	var $dstart;
	var $dstop;
	var $timestamp_min;
	var $timestamp_max;
	var $keywords;
	var $phrase;
	var $exact;


	/**
	 * Constructor.
	 *
	 * @param string Name of table in database
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 */
	function ItemQuery( $dbtablename, $dbprefix = '', $dbIDname )
	{
		$this->dbtablename = $dbtablename;
		$this->dbprefix = $dbprefix;
		$this->dbIDname = $dbIDname;

		$this->FROM( $this->dbtablename.' INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID
									INNER JOIN T_categories ON postcat_cat_ID = cat_ID ' );
	}


  /**
	 * Restrict to a specific post
	 */
	function where_ID( $p = '', $title = '' )
	{
		$r = false;

		$this->p = $p;
		$this->title = $title;

		// if a post number is specified, load that post
		if( !empty($p) )
		{
			$this->WHERE_and( $this->dbIDname.' = '. intval($p) );
			$r = true;
		}

		// if a post urltitle is specified, load that post
		if( !empty( $title ) )
		{
			global $DB;
			$this->WHERE_and( $this->dbprefix.'urltitle = '.$DB->quote($title) );
			$r = true;
		}

		return $r;
	}


  /**
	 * Restrict to specific collection/chapters (blog/categories)
	 *
	 * @todo get rid of blog #1
	 *
	 * @param integer
	 * @param string List of cats to restrict to
	 * @param array Array of cats to restrict to
	 */
	function where_chapter( $blog, $cat = '', $catsel = array() )
	{
		$blog = intval($blog);	// Extra security

		// Save for future use (permission checks..)
		$this->blog = $blog;

		if( $blog != 1 )
		{ // Not Special case where we aggregate all blogs
			$this->WHERE_and( 'cat_blog_ID = '. $blog );
		}

		$cat_array = NULL;
		$cat_modifier = NULL;

		// Compile the real category list to use:
		// TODO: allow to pass the compiled vars directly to this class
		compile_cat_array( $cat, $catsel, /* by ref */ $cat_array, /* by ref */ $cat_modifier, $blog == 1 ? 0 : $blog );

		if( ! empty($cat_array) )
		{	// We want to restict to some cats:
			if( $cat_modifier == '-' )
			{
				$eq = 'NOT IN';
			}
			else
			{
				$eq = 'IN';
			}
			$whichcat = 'postcat_cat_ID '. $eq.' ('.implode(',', $cat_array). ') ';

			// echo $whichcat;
			$this->WHERE_and( $whichcat );

   		if( $cat_modifier == '*' )
			{ // We want the categories combined! (i-e posts must be in ALL requested cats)
				$this->GROUP_BY( $this->dbIDname.' HAVING COUNT(postcat_cat_ID) = '.count($cat_array) );
			}
		}
	}


  /**
	 * Restrict to specific collection/chapters (blog/categories)
	 *
	 * @todo get rid of blog #1
	 *
	 * @param integer
	 */
	function where_chapter2( $blog_ID, $cat_array, $cat_modifier )
	{
		// Save for future use (permission checks..)
		$this->blog = $blog_ID;

		if( $blog_ID != 1 )
		{ // Not Special case where we aggregate all blogs
			$this->WHERE_and( 'cat_blog_ID = '.$blog_ID );
		}


		if( ! empty($cat_array) )
		{	// We want to restict to some cats:
			if( $cat_modifier == '-' )
			{
				$eq = 'NOT IN';
			}
			else
			{
				$eq = 'IN';
			}
			$whichcat = 'postcat_cat_ID '. $eq.' ('.implode(',', $cat_array). ') ';

			// echo $whichcat;
			$this->WHERE_and( $whichcat );

   		if( $cat_modifier == '*' )
			{ // We want the categories combined! (i-e posts must be in ALL requested cats)
				$this->GROUP_BY( $this->dbIDname.' HAVING COUNT(postcat_cat_ID) = '.count($cat_array) );
			}
		}
	}


  /**
	 * Restrict to the visibility/sharing statuses we want to show
	 *
	 * @param array Restrict to these statuses
	 */
	function where_visibility( $show_statuses )
	{
		$this->show_statuses = $show_statuses;

		if( !isset( $this->blog ) )
		{
			die( 'Status restriction requires to work with aspecific blog first.' );
		}

		$this->WHERE_and( statuses_where_clause( $show_statuses, $this->dbprefix, $this->blog ) );
	}


  /**
	 * Restrict to specific authors
	 *
	 * @param string List of authors to restrict to (must have been previously validated)
	 */
	function where_author( $author )
	{
		$this->author = $author;

		if( empty( $author ) )
		{
			return;
		}

		if( substr( $author, 0, 1 ) == '-' )
		{	// List starts with MINUS sign:
			$eq = 'NOT IN';
			$author_list = substr( $author, 1 );
		}
		else
		{
			$eq = 'IN';
			$author_list = $author;
		}

		$this->WHERE_and( $this->dbprefix.'creator_user_ID '.$eq.' ('.$author_list.')' );
	}


  /**
	 * Restrict to specific assignees
	 *
	 * @param string List of assignees to restrict to (must have been previously validated)
	 */
	function where_assignees( $assignees )
	{
		$this->assignees = $assignees;

		if( empty( $assignees ) )
		{
			return;
		}

		if( $assignees == '-' )
		{	// List is ONLY a MINUS sign (we want only those not assigned)
			$this->WHERE_and( $this->dbprefix.'assigned_user_ID IS NULL' );
		}
		elseif( substr( $assignees, 0, 1 ) == '-' )
		{	// List starts with MINUS sign:
			$this->WHERE_and( '( '.$this->dbprefix.'assigned_user_ID IS NULL
			                  OR '.$this->dbprefix.'assigned_user_ID NOT IN ('.substr( $assignees, 1 ).') )' );
		}
		else
		{
			$this->WHERE_and( $this->dbprefix.'assigned_user_ID IN ('.$assignees.')' );
		}
	}


  /**
	 * Restrict to specific assignees
	 *
	 * @param string List of assignees to restrict to (must have been previously validated)
	 */
	function where_statuses( $statuses )
	{
		$this->statuses = $statuses;

		if( empty( $statuses ) )
		{
			return;
		}

		if( $statuses == '-' )
		{	// List is ONLY a MINUS sign (we want only those not assigned)
			$this->WHERE_and( $this->dbprefix.'pst_ID IS NULL' );
		}
		elseif( substr( $statuses, 0, 1 ) == '-' )
		{	// List starts with MINUS sign:
			$this->WHERE_and( '( '.$this->dbprefix.'pst_ID IS NULL
			                  OR '.$this->dbprefix.'pst_ID NOT IN ('.substr( $statuses, 1 ).') )' );
		}
		else
		{
			$this->WHERE_and( $this->dbprefix.'pst_ID IN ('.$statuses.')' );
		}
	}


	/**
	 * Restricts to a specific date range. (despite thje 'start' in the name
	 *
	 * Priorities:
   *  -dstart and/or dstop
   *  -week + m
   *  -m
   * @todo  -dstart + x days
   * @see ItemList2::get_advertised_start_date()
	 *
 	 * @param string YYYYMMDDHHMMSS (everything after YYYY is optional) or ''
	 * @param integer week number or ''
	 * @param string YYYYMMDDHHMMSS to start at, '' for first available
	 * @param string YYYYMMDDHHMMSS to stop at
	 * @param mixed Do not show posts before this timestamp, can be 'now'
	 * @param mixed Do not show posts after this timestamp, can be 'now'
	 */
	function where_datestart( $m = '', $w = '', $dstart = '', $dstop = '', $timestamp_min = '', $timestamp_max = 'now' )
	{
		global $Settings;

		$this->m = $m;
		$this->w = $w;
		$this->dstart = $dstart;
		$this->dstop = $dstop;
		$this->timestamp_min = $timestamp_min;
		$this->timestamp_max = $timestamp_max;


		$start_is_set = false;
		$stop_is_set = false;


		// if a start date is specified in the querystring, crop anything before
		if( !empty($dstart) )
		{
			// Add trailing 0s: YYYYMMDDHHMMSS
			$dstart0 = $dstart.'00000000000000';  // TODO: this is NOT correct, should be 0101 for month

			$dstart_mysql = substr($dstart0,0,4).'-'.substr($dstart0,4,2).'-'.substr($dstart0,6,2).' '
											.substr($dstart0,8,2).':'.substr($dstart0,10,2).':'.substr($dstart0,12,2);

			$this->WHERE_and( $this->dbprefix.'datestart >= \''.$dstart_mysql.'\'' );

			$start_is_set = true;
		}


		// if a stop date is specified in the querystring, crop anything before
		if( !empty($dstop) )
		{
			switch( strlen( $dstop ) )
			{
				case '4':
					// We have only year, add one to year
					$dstop_mysql = ($dstop+1).'-01-01 00:00:00';
					break;

				case '6':
					// We have year month, add one to month
					$dstop_mysql = date("Y-m-d H:i:s ", mktime(0, 0, 0, substr($dstop,4,2)+1, 01, substr($dstop,0,4)));
					break;

				case '8':
					// We have year mounth day, add one to day
					$dstop_mysql = date("Y-m-d H:i:s ", mktime(0, 0, 0, substr($dstop,4,2), (substr($dstop,6,2) + 1 ), substr($dstop,0,4)));
					break;

				case '10':
					// We have year mounth day hour, add one to hour
					$dstop_mysql = date("Y-m-d H:i:s ", mktime( ( substr($dstop,8,2) + 1 ), 0, 0, substr($dstop,4,2), substr($dstop,6,2), substr($dstop,0,4)));
					break;

				case '12':
					// We have year mounth day hour minute, add one to minute
					$dstop_mysql = date("Y-m-d H:i:s ", mktime( substr($dstop,8,2), ( substr($dstop,8,2) + 1 ), 0, substr($dstop,4,2), substr($dstop,6,2), substr($dstop,0,4)));
					break;

				default:
					// add one to second
					$dstop_mysql = substr($dstop,0,4).'-'.substr($dstop,4,2).'-'.substr($dstop,6,2).' '
											.substr($dstop,8,2).':'.substr($dstop,10,2).':'.( substr( $dstop,12,2) + 1 );
			}

			$this->WHERE_and( $this->dbprefix.'datestart < \''.$dstop_mysql.'\'' ); // NOT <= comparator because we compare to the superior stop date

			$stop_is_set = true;
		}


		if( !$start_is_set || !$stop_is_set )
		{

			if( !is_null($w)  // Note: week # can be 0
					&& strlen($m) == 4 )
			{ // If a week number is specified (with a year)

				// Note: we use PHP to calculate week boundaries in order to handle weeks
				// that overlap 2 years properly, even when start on week is monday (which MYSQL won't handle properly)
				$start_date_for_week = get_start_date_for_week( $m, $w, locale_startofweek() );

				$this->WHERE_and( $this->dbprefix.'datestart >= "'.date('Y-m-d',$start_date_for_week).'"' );
				$this->WHERE_and( $this->dbprefix.'datestart < "'.date('Y-m-d',$start_date_for_week+604800 ).'"' ); // + 7 days

				$start_is_set = true;
				$stop_is_set = true;
			}
			elseif( !empty($m) )
			{	// We want to restrict on an interval:
				$this->WHERE_and( 'YEAR('.$this->dbprefix.'datestart)='.intval(substr($m,0,4)) );
				if( strlen($m) > 5 )
					$this->WHERE_and( 'MONTH('.$this->dbprefix.'datestart)='.intval(substr($m,4,2)) );
				if( strlen($m) > 7 )
					$this->WHERE_and( 'DAYOFMONTH('.$this->dbprefix.'datestart)='.intval(substr($m,6,2)) );
				if( strlen($m) > 9 )
					$this->WHERE_and( 'HOUR('.$this->dbprefix.'datestart)='.intval(substr($m,8,2)) );
				if( strlen($m) > 11 )
					$this->WHERE_and( 'MINUTE('.$this->dbprefix.'datestart)='.intval(substr($m,10,2)) );
				if( strlen($m) > 13 )
					$this->WHERE_and( 'SECOND('.$this->dbprefix.'datestart)='.intval(substr($m,12,2)) );

				$start_is_set = true;
				$stop_is_set = true;
			}

		}


		// TODO: start + x days
		// TODO: stop - x days


		// SILENT limits!

		// Timestamp limits:
		if( $timestamp_min == 'now' )
		{
			// echo 'hide past';
			$timestamp_min = time();
		}
		if( !empty($timestamp_min) )
		{ // Hide posts before
			// echo 'hide before '.$timestamp_min;
			$date_min = date('Y-m-d H:i:s', $timestamp_min + ($Settings->get('time_difference') * 3600) );
			$this->WHERE_and( $this->dbprefix.'datestart >= \''. $date_min.'\'' );
		}

		if( $timestamp_max == 'now' )
		{
			// echo 'hide future';
			$timestamp_max = time();
		}
		if( !empty($timestamp_max) )
		{ // Hide posts after
			// echo 'after';
			$date_max = date('Y-m-d H:i:s', $timestamp_max + ($Settings->get('time_difference') * 3600) );
			$this->WHERE_and( $this->dbprefix.'datestart <= \''. $date_max.'\'' );
		}

	}


  /**
	 * Restrict with keywords
	 *
	 * @param string Keyword search string
	 * @param mixed Search for entire phrase or for individual words
	 * @param mixed Require exact match of title or contents
	 */
	function where_keywords( $keywords, $phrase, $exact )
	{
		global $DB;

		$this->keywords = $keywords;
		$this->phrase = $phrase;
		$this->exact = $exact;

		if( empty($keywords) )
		{
			return;
		}

		$search = '';

		if( $exact )
		{	// We want exact match of title or contents
			$n = '';
		}
		else
		{ // The words/sentence are/is to be included in in the title or the contents
			$n = '%';
		}

		if( ($phrase == '1') or ($phrase == 'sentence') )
		{ // Sentence search
			$keywords = $DB->escape(trim($keywords));
			$search .= '('.$this->dbprefix.'title LIKE \''. $n. $keywords. $n. '\') OR ('.$this->dbprefix.'content LIKE \''. $n. $keywords. $n.'\')';
		}
		else
		{ // Word search
			if( strtoupper( $phrase ) == 'OR' )
				$swords = 'OR';
			else
				$swords = 'AND';

			// puts spaces instead of commas
			$keywords = preg_replace('/, +/', '', $keywords);
			$keywords = str_replace(',', ' ', $keywords);
			$keywords = str_replace('"', ' ', $keywords);
			$keywords = trim($keywords);
			$keyword_array = explode(' ',$keywords);
			$join = '';
			for ( $i = 0; $i < count($keyword_array); $i++)
			{
				$search .= ' '. $join. ' ( ('.$this->dbprefix.'title LIKE \''. $n. $DB->escape($keyword_array[$i]). $n. '\')
																OR ('.$this->dbprefix.'content LIKE \''. $n. $DB->escape($keyword_array[$i]). $n.'\') ) ';
				$join = $swords;
			}
		}

		//echo $search;
		$this->WHERE_and( $search );
	}


}

/*
 * $Log$
 * Revision 1.3  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.2  2006/03/12 23:08:59  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:58  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.12  2006/02/10 22:08:07  fplanque
 * Various small fixes
 *
 * Revision 1.11  2006/02/03 21:58:05  fplanque
 * Too many merges, too little time. I can hardly keep up. I'll try to check/debug/fine tune next week...
 *
 * Revision 1.10  2006/01/04 20:34:52  fplanque
 * allow filtering on extra statuses
 *
 * Revision 1.9  2006/01/04 19:07:48  fplanque
 * allow filtering on assignees
 *
 * Revision 1.8  2005/12/21 20:39:04  fplanque
 * minor
 *
 * Revision 1.7  2005/12/19 19:30:14  fplanque
 * minor
 *
 * Revision 1.6  2005/12/19 18:10:18  fplanque
 * Normalized the exp and tracker tabs.
 *
 * Revision 1.5  2005/12/05 18:17:19  fplanque
 * Added new browsing features for the Tracker Use Case.
 *
 * Revision 1.4  2005/09/06 19:38:29  fplanque
 * bugfixes
 *
 * Revision 1.3  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.2  2005/09/01 17:11:46  fplanque
 * no message
 *
 * Revision 1.1  2005/08/31 19:08:51  fplanque
 * Factorized Item query WHERE clause.
 * Fixed calendar contextual accuracy.
 *
 */
?>