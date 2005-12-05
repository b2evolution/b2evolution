<?php
/**
 * This file implements the ItemQuery class.
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
require_once dirname(__FILE__).'/_sql.class.php';


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
		$this->p = $p;
		$this->title = $title;

		// if a post number is specified, load that post
		if( !empty($p) && ($p != 'all') )
		{
			$this->WHERE_and( $this->dbIDname.' = '. intval($p) );
		}

		// if a post urltitle is specified, load that post
		if( !empty( $title ) )
		{
			global $DB;
			$this->WHERE_and( $this->dbprefix.'urltitle = '.$DB->quote($title) );
		}
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
	 * Restrict to the statuses we want to show
	 *
	 * @param array Restrict to these statuses
	 */
	function where_status( $show_statuses )
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
	 * @param string List of authors to restrict to
	 */
	function where_author( $author = '' )
	{
		$this->author = $author;

		if( empty($author) || ($author == 'all'))
		{
			return;
		}

		if( substr($author, 0, 1 ) == '-' )
		{	// List starts with MINUS sign:
			$eq = 'NOT IN';
			$author_list = substr( $author, 1 );
		}
		else
		{
			$eq = 'IN';
			$author_list = $author;
		}
		// Check that the string is valid (digits and comas only)
		if( preg_match( '#^[0-9]+(,[0-9]+)*$#', $author_list ) )
		{	// Okay, there is no sql injection risk
			$this->WHERE_and( $this->dbprefix.'creator_user_ID '.$eq.' ('.$author_list.')' );
		}
	}


	/**
	 * Restricts to a specific start date
	 *
	 * @param string YYYYMMDDHHMMSS (everything after YYYY is optional) or ''
	 * @param integer week number or ''
	 * @param string YYYYMMDDHHMMSS to start at, '' for first available
	 * @param string NOT IMPLEMENTED
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

		if( !empty($m) )
		{
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
		}


		if( !empty($w) && ($w>=0) ) // Note: week # can be 0
		{ // If a week number is specified
			global $DB;

			$this->WHERE_and( $DB->week( $this->dbprefix.'datestart', locale_startofweek() ).'='.intval($w) );
		}


		// if a start date is specified in the querystring, crop anything before
		if( !empty($dstart) )
		{
			// Add trailing 0s: YYYYMMDDHHMMSS
			$dstart0 = $dstart.'00000000000000';

			$dstart_mysql = substr($dstart0,0,4).'-'.substr($dstart0,4,2).'-'.substr($dstart0,6,2).' '
											.substr($dstart0,8,2).':'.substr($dstart0,10,2).':'.substr($dstart0,12,2);

			$this->WHERE_and( $this->dbprefix.'datestart >= \''.$dstart_mysql.'\'' );
		}


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