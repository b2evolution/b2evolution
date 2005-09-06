<?php
/**
 * This file implements the ItemList class.
 *
 * This is the object handling item/post/article lists.
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
 * @author fplanque: François PLANQUE.
 * @author jupiterx: Jordan RUNNING.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_dataobjectlist.class.php';
require_once dirname(__FILE__).'/_item.class.php';
require_once dirname(__FILE__).'/_item.funcs.php';

/**
 * Item List Class
 *
 * @package evocore
 *
 * @todo better use of Parent Class hierarchy... maybe make Results detect wether or not LIMIT is already set
 */
class ItemList extends DataObjectList
{
	var $objType;

	var $preview;
	/**
	 * Blog ID to restrict to. 1 means "all blogs".
	 * @var integer
	 */
	var $blog;
	/**
	 * Specific post number to display. '' means don't restrict to single post.
	 * @todo Might support list of IDs (array).
	 * @var string|integer
	 */
	var $p;
	var $unit;
	/**
	 * @var integer Number of rows in result set
	 */
	var $result_num_rows;
	var $postIDlist;
	var $postIDarray;

	var $group_by_cat;

	var $limitdate_start;     // UNIX timestamp
	var $limitdate_end;       // UNIX timestamp

	// Used in looping
	var $row_num;							// Current row
	var $row;									// Current row
	var $main_cat;						// Current main category
	var $previous_main_cat;		// Previous one
	/**
	 * @access private
	 */
	var $last_Item;

	/**
	 * @access private
	 */
	var $last_displayed_date = '';

	var $show_statuses;
	var $cat;
	var $catsel;
	var $cat_array;
	var $cat_modifier;
	var $timestamp_min;
	var $timestamp_max;

	var $dbcols;

	var $DataObjectCache;

	/**
	 * Constructor
	 *
	 * @param integer Blog ID to query
	 * @param array Restrict to these statuses
	 * @param mixed Specific post number to display
	 * @param mixed YearMonth(Day) to display
	 * @param mixed Number of Week to display. Note: uses MySQL's week numbering and MySQL default if applicable.
   * In MySQL < 4.0, WEEK() uses mode 0: Week starts on Sunday;
   * Value range is 0 to 53; week 1 is the first week that starts in this year
	 * @param mixed List of cats to restrict to
	 * @param array Array of cats to restrict to
	 * @param mixed List of authors to restrict to
	 * @param string sort order can be either ASC or DESC
	 * @param string space separated list of fields to order by. Possible list elements are:
	 *               author issue_date mod_date status locale content title urltitle url ctageory
	 *               wordcount comments
	 * @param mixed # of posts to display on the page
	 * @param mixed List page number in paged display
	 * @param mixed Start results at this position
	 * @param mixed End results at this position
	 * @param string Search string
	 * @param mixed Search for entire phrase or for individual words
	 * @param mixed Require exact match of title or contents
	 * @param boolean Is this preview
	 * @param string 'posts' or 'days'
	 * @param mixed Do not show posts before this timestamp, can be 'now'
	 * @param mixed Do not show posts after this timestamp, can be 'now'
	 * @param string urltitle of post to display
	 * @param string YearMonth(Day) to start at, '' for first available
	 * @param string name of cache to be used
	 */
	function ItemList(
		$blog = 1,                  // Blog to query
		$show_statuses = array(),   // Restrict to these statuses
		$p = '',                    // Specific post number to display
		$m = '',                    // YearMonth(Day) to display
		$w = -1,                    // Number of Week to display
		$cat = '',                  // List of cats to restrict to
		$catsel = array(),          // Array of cats to restrict to
		$author = '',               // List of authors to restrict to
		$order = '',                // ASC or DESC
		$orderby = '',              // list of fields to order by
		$posts_per_page = '',       // # of posts to display on the page
		$page_number = '',          // List page number in paged display
		$poststart = '',            // Start results at this position
		$postend = '',              // End results at this position
		$keywords = '',             // Keyword search string
		$phrase = '',               // Search for entire phrase or for individual words
		$exact = '',                // Require exact match of title or contents
		$preview = 0,               // Is this preview?
		$unit = '',                 // 'posts' or 'days'
		$timestamp_min = '',        // Do not show posts before this timestamp
		$timestamp_max = 'now',     // Do not show posts after this timestamp
		$title = '',                // urltitle of post to display
		$dstart = '',               // YearMonth(Day) to start at, '' for first available
		$cache_name = '#' )
	{
		global $DB, $object_def;
		global $Settings;

		if( $cache_name == '#' )
		{ // Let's use the default cache:
			$cache_name = 'ItemCache';
		}

		global $$cache_name;

		$this->cache_name = $cache_name;
		$this->DataObjectCache = & $$cache_name; // By ref!!

		// Call parent constructor:
		parent::DataObjectList( $this->DataObjectCache->dbtablename, $this->DataObjectCache->dbprefix,
														$this->DataObjectCache->dbIDname, $this->DataObjectCache->objtype );

		$this->preview = $preview;
		$this->blog = $blog;
		$this->p = $p;

		$this->show_statuses = $show_statuses;
		$this->cat = $cat;
		$this->catsel = $catsel;
		$this->timestamp_min = $timestamp_min;
		$this->timestamp_max = $timestamp_max;

		if( empty($posts_per_page) )
		{ // Nothing specified, use default number of posts per page:
			$posts_per_page = $Settings->get('posts_per_page');
		}
		$this->posts_per_page = $posts_per_page;

		if( empty($unit) )
		{ // Nothing specified, use default display type:
			$unit = $Settings->get('what_to_show');
		}
		$this->unit = $unit;

		// First let's clear some variables
		$result = '';
		$where = '';
		$limits = '';
		$distinct = '';

		/*
		 * WE ARE GOING TO CONSTRUCT THE WHERE CLOSE...
		 */

		$this->ItemQuery = new ItemQuery( $this->DataObjectCache->dbtablename, $this->DataObjectCache->dbprefix,
																			$this->DataObjectCache->dbIDname );	// COPY!!

		// Select a specific Item:
		$this->ItemQuery->where_ID( $p, $title );

		// Restrict to selected blog/categories:
		$this->ItemQuery->where_chapter( $blog, $cat, $catsel );

		// Restrict to the statuses we want to show:
		$this->ItemQuery->where_status( $show_statuses );

		// Restrict to selected authors:
		$this->ItemQuery->where_author( $author );

		// if a month is specified in the querystring, load that month:
		$this->ItemQuery->where_datestart( $m, $w, $dstart, '', $timestamp_min, $timestamp_max );

		// Keyword search stuff:
		$this->ItemQuery->where_keywords( $keywords, $phrase, $exact );


		/*
		 * ----------------------------------------------------
		 * order by stuff
		 * ----------------------------------------------------
		 */
		if( (!empty($order)) && ((strtoupper($order) != 'ASC') && (strtoupper($order) != 'DESC')))
		{
			$order='DESC';
		}

		if(empty($orderby))
		{
			$orderby = 'datestart '. $order;
		}
		else
		{
			$orderby_array = explode(' ',$orderby);
			$orderby = $orderby_array[0]. ' '. $order;
			if (count($orderby_array)>1)
			{
				for($i = 1; $i < (count($orderby_array)); $i++)
				{
					$orderby .= ', '.$this->dbprefix.$orderby_array[$i]. ' '. $order;
				}
			}
		}


		/*
		 * ----------------------------------------------------
		 * Paging limits:
		 * ----------------------------------------------------
		 */
		if( !empty($p) || !empty($title) )
		{ // Single post: no paging required!
			$limits = '';
		}
		elseif( !empty($poststart) )
		{ // When in backoffice...  (to be deprecated...)
			// echo 'POSTSTART-POSTEND ';
			if( $postend < $poststart )
			{
				$postend = $poststart + $posts_per_page - 1;
			}

			if( $unit == 'posts' )
			{
				$posts = $postend - $poststart + 1;
				$limits = ' LIMIT '. ($poststart-1). ','. $posts;
			}
			elseif( $unit == 'days' )
			{
				$posts = $postend - $poststart + 1;
				// echo 'days=',$posts;
				$lastpostdate = $this->get_lastpostdate();
				$lastpostdate = mysql2date('Y-m-d 23:59:59',$lastpostdate);
				// echo $lastpostdate;
				$lastpostdate = mysql2timestamp( $lastpostdate );
				$this->limitdate_end = $lastpostdate - (($poststart -1) * 86400);
				$this->limitdate_start = $lastpostdate+1 - (($postend) * 86400);
				$where .= ' AND '.$this->dbprefix.'datestart >= \''. date( 'Y-m-d H:i:s', $this->limitdate_start )
									.'\' AND '.$this->dbprefix.'datestart <= \''. date('Y-m-d H:i:s', $this->limitdate_end) . '\'';
			}
		}
		elseif( !empty($m) )
		{ // no restriction if we request a month... some permalinks may point to the archive!
			// echo 'ARCHIVE - no limits';
			$limits = '';
		}
		elseif( $unit == 'posts' )
		{
			// echo 'LIMIT POSTS ';
			$pgstrt = '';
			if( $page_number )
			{ // We have requested a specific page number
				$pgstrt = (intval($page_number) -1) * $posts_per_page. ', ';
			}
			$limits = 'LIMIT '. $pgstrt.$posts_per_page;
		}
		elseif( $unit == 'days' )
		{ // We are going to limit to x days:
			// echo 'LIMIT DAYS ';
			if( empty( $dstart ) )
			{ // We have no start date, we'll display the last x days:
				if( !empty($keywords) || !empty($cat) || !empty($author) )
				{ // We are in DAYS mode but we can't restrict on these! (TODO: ?)
					$limits = '';
				}
				else
				{ // We are going to limit to LAST x days:
					$lastpostdate = $this->get_lastpostdate();
					$lastpostdate = mysql2date('Y-m-d 00:00:00',$lastpostdate);
					$lastpostdate = mysql2date('U',$lastpostdate);
					// go back x days
					$otherdate = date('Y-m-d H:i:s', ($lastpostdate - (($posts_per_page-1) * 86400)));
					$where .= ' AND '.$this->dbprefix.'datestart > \''. $otherdate.'\'';
				}
			}
			else
			{ // We have a start date, we'll display x days starting from that point:
				// $dstart_mysql has been calculated earlier
				$dstart_ts = mysql2timestamp( $dstart_mysql );
				// go forward x days
				$enddate_ts = date('Y-m-d H:i:s', ($dstart_ts + ($posts_per_page * 86400)));
				$where .= ' AND '.$this->dbprefix.'datestart < \''. $enddate_ts.'\'';
			}
		}
		else
			die( 'Unhandled LIMITING mode in ItemList (paged mode is obsolete)' );


		// FINALIZE WHERE CLAUSE:
		if( !empty($this->ItemQuery->where) )
		{
			$where = 'WHERE '.$this->ItemQuery->where.$where;
		}
		else
		{
			$where = 'WHERE 1 '.$where;
		}


		/*
		 * RUN QUERY NOW:
		 */
		if ($preview)
		{ // PREVIEW MODE:
			$this->sql = $this->preview_request();
		}
		else
		{	// NORMAL MODE:

			// We are going to proceed in two steps (we simulate a subquery)
			// 1) we get the IDs we need
			// 2) we get all the other fields matching these IDs
			// This is more efficient than manipulating all fields at once.
			$step1_sql = 'SELECT DISTINCT ID
											FROM '.$this->dbtablename.' INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID
													INNER JOIN T_categories ON postcat_cat_ID = cat_ID ';
			$step1_sql .= $where;
			if( !empty($this->ItemQuery->group_by) )
			{
				$step1_sql .= ' GROUP BY '.$this->ItemQuery->group_by;
			}
			$step1_sql .= ' ORDER BY '.$this->dbprefix.$orderby;
			$step1_sql .= ' '.$limits;

			// Get list of the IDs we need:
			$ID_list = $DB->get_list( $step1_sql, 0, 'Get ID list for Item List (Main|Lastpostdate) Query' );

			$this->sql = 'SELECT *
											FROM '.$this->dbtablename;
			if( !empty($ID_list) )
			{
				$this->sql .= ' WHERE ID IN ('.$ID_list.')
										    ORDER BY '.$this->dbprefix.$orderby;
			}
			else
			{
				$this->sql .= ' WHERE 0';
			}

			$this->count_request = 'SELECT COUNT(ID)
                                FROM '.$this->dbtablename.' INNER JOIN T_postcats ON '.$this->dbIDname.' = postcat_post_ID
																		INNER JOIN T_categories ON postcat_cat_ID = cat_ID
															'.$where;
		}

		//echo $this->sql;
		$this->rows = $DB->get_results( $this->sql, OBJECT, 'Get data for Item List (Main|Lastpostdate) Query' );

		$this->result_num_rows = $DB->num_rows;
		// echo $this->result_num_rows, ' items';

		// Prebuild and cache objects:
		$this->postIDlist = "";
		$this->postIDarray = array();
		$i = 0;
		foreach( $this->rows as $row )
		{
			// Prebuild object:
			$this->Obj[$i] = new $this->objType( $row, $this->dbtablename, $this->dbprefix, $this->dbIDname ); // COPY!!

			// To avoid potential future waste, cache this object:
			$this->DataObjectCache->add( $this->Obj[$i] );

			// Make a list of posts for future queries!
			array_unshift( $this->postIDarray, $row->{$this->dbIDname} );	// new row at beginning (fplanque>>why?)

			$i++;
		}


		if( !empty($this->postIDarray) )
		{
			$this->postIDlist = implode( ',', $this->postIDarray );
		}

		// Initialize loop stuff:
		$this->restart();
	}


	/**
	 * Dummy mysql query for the preview
	 *
	 * @return string SQL query
	 */
	function preview_request()
	{
		// we need globals for the param function
		global $preview_userid, $preview_date, $post_status, $post_locale, $content,
						$post_title, $post_url, $post_category, $post_views, $edit_date,
						$aa, $mm, $jj, $hh, $mn, $ss, $renderers;
		global $DB, $localtimenow, $Messages;

		$id = 0;
		param( 'preview_userid', 'integer', true );
		param( 'post_status', 'string', true );
		param( 'post_locale', 'string', true );
		param( 'content', 'html', true );
		param( 'post_title', 'html', true );
		param( 'post_url', 'string', true );
		param( 'post_category', 'integer', true );
		param( 'post_views', 'integer', 0 );
		param( 'renderers', 'array', array() );

		$post_title = format_to_post( $post_title, 0 );
		$content = format_to_post( $content );
		$post_renderers = implode( '.', $renderers );

		param( 'aa', 'integer', 2000 );
		param( 'mm', 'integer', 1 );
		param( 'jj', 'integer', 1 );
		param( 'hh', 'integer', 20 );
		param( 'mn', 'integer', 30 );
		param( 'ss', 'integer', 0 );
		$jj = ($jj > 31) ? 31 : $jj;
		$hh = ($hh > 23) ? $hh - 24 : $hh;
		$mn = ($mn > 59) ? $mn - 60 : $mn;
		$ss = ($ss > 59) ? $ss - 60 : $ss;
		$post_date = date('Y-m-d H:i:s', mktime( $hh, $mn, $ss, $mm, $jj, $aa ) );


		if( $errcontent = $Messages->display( T_('Invalid post, please correct these errors:'), '', false ) )
		{
			$content = $errcontent;
		}

		// little funky fix for IEwin, rawk on that code
		global $Hit;
		if( ($Hit->is_winIE) && (!isset($IEWin_bookmarklet_fix)) )
		{ // QUESTION: Is this still needed? What about $IEWin_bookmarklet_fix? (blueyed)
			$content = preg_replace('/\%u([0-9A-F]{4,4})/e', "'&#'.base_convert('\\1',16,10). ';'", $content);
		}

		/*
			TODO: new post params not recognized! (Produces notices in preview)

			post form param          Add to query
			------------------------------------------------
			item_assigned_user_ID => (post_)assigned_user_ID
			item_priority         => (post_)priority
			item_deadline         => (post_)datedeadline

			???                   => (post_)ptyp_ID
			???                   => (post_)pst_ID
		*/

		return "SELECT
										0 AS ID,
										$preview_userid AS ".$this->dbprefix."creator_user_ID,
										'$post_date' AS ".$this->dbprefix."datestart,
										'$post_date' AS ".$this->dbprefix."datemodified,
										'".$DB->escape($post_status)."' AS ".$this->dbprefix."status,
										'".$DB->escape($post_locale)."' AS ".$this->dbprefix."locale,
										'".$DB->escape($content)."' AS ".$this->dbprefix."content,
										'".$DB->escape($post_title)."' AS ".$this->dbprefix."title,
										NULL AS ".$this->dbprefix."urltitle,
										'".$DB->escape($post_url)."' AS ".$this->dbprefix."url,
										$post_category AS ".$this->dbprefix."main_cat_ID,
										$post_views AS ".$this->dbprefix."views,
										'' AS ".$this->dbprefix."flags,
										".bpost_count_words( $content )." AS ".$this->dbprefix."wordcount,
										'open' AS ".$this->dbprefix."comments,
										'".$DB->escape( $post_renderers )."' AS ".$this->dbprefix.'renderers';
	}



	/**
	 * {@internal ItemList::get_lastpostdate(-)}}
	 */
	function get_lastpostdate()
	{
		global $localtimenow, $postdata;

		// echo 'getting last post date';
		$LastPostList = & new ItemList( $this->blog, $this->show_statuses, '', '', '', $this->cat, $this->catsel,
																		 '', 'DESC', 'datestart', 1, '','', '', '', '', '', '', 'posts',
																		 $this->timestamp_min, $this->timestamp_max, '', '',
																		 $this->cache_name );

		if( $LastItem = $LastPostList->get_item() )
		{
			// echo 'we have a last item';
			$last_postdata = $LastPostList->get_postdata();	// will set $postdata;
			$lastpostdate = $postdata['Date'];
		}
		else
		{
			// echo 'we have no last item';
			$lastpostdate = date('Y-m-d H:i:s', $localtimenow);
		}
		// echo $lastpostdate;
		return($lastpostdate);
	}


	/*
	 * ItemList->restart(-)
	 */
	function restart()
	{
		// Set variables for future:
		global $previousday;		// Should be a member var
		$previousday = '';
		$this->row_num = 0;
		$this->main_cat = '';
		$this->group_by_cat = false;
	}


	/*
	 * ItemList->get_max_paged(-)
	 *
	 * return maximum page number for paged display
	 */
	function get_max_paged()
	{
		if( empty($this->total_pages) )
		{ // Not already cached:
			$this->calc_max();
		}
		//echo 'max paged= ', $this->total_pages;
		return $this->total_pages;
	}


	/**
	 * Template function: display last mod date (datetime) of Item
	 *
	 * {@internal ItemList::mod_date(-) }}
	 *
	 * @param string date/time format: leave empty to use locale default date format
	 * @param boolean true if you want GMT
	 */
	function mod_date( $format = '', $useGM = false )
	{
		$mod_date_timestamp = 0;
		foreach( $this->rows as $loop_row )
		{ // Go through whole list
			$m = $loop_row->post_datemodified;
			$loop_mod_date = mktime(substr($m,11,2),substr($m,14,2),substr($m,17,2),substr($m,5,2),substr($m,8,2),substr($m,0,4));
			if( $loop_mod_date > $mod_date_timestamp )
				$mod_date_timestamp = $loop_mod_date;
		}

		if( empty($format) )
			echo date_i18n( locale_datefmt(), $mod_date_timestamp, $useGM );
		else
			echo date_i18n( $format, $mod_date_timestamp, $useGM );
	}


	/*
	 * ItemList->get_total_num_posts(-)
	 *
	 * return total number of posts
	 */
	function get_total_num_posts()
	{
		if( empty($this->total_rows) )
		{ // Not already cached:
			$this->calc_max();
		}
		return $this->total_rows;
	}


	/*
	 * Private ItemList->calc_max(-)
	 *
	 * @todo use COUNT(*)
	 */
	function calc_max()
	{
		global $DB;

		if( $this->preview )
			return 1;	// 1 row in preview mode

		$this->total_rows = $DB->get_var( $this->count_request, 0, 0, 'Get result count' );

		$this->total_pages = intval( ($this->total_rows-1) / max($this->posts_per_page, $this->result_num_rows)) +1;
		if( $this->total_pages < 1 )
			$this->total_pages = 1;
	}


	/**
	 * {@internal ItemList::get_category_group()}}
	 */
	function get_category_group()
	{
		global $row;

		$this->group_by_cat = true;

		if( ($this->row_num > $this->result_num_rows) || ($this->result_num_rows == 0) )
		{ // We are at the the end!
			// echo 'END';
			return false;
		}

		if( $this->row_num == 0 )
		{ // We need to initialize
			$this->row = & $this->rows[0];
			$row = $this->row;
			$this->get_postdata();
			$this->row_num = 1;
		}

		// Memorize main cat
		$this->main_cat = $this->row->post_main_cat_ID;

		// Go back now so that the fetch row doesn't skip one!
		$this->row_num --;

		#already done in get_postdata: $this->last_Item = new Item( $this->row ); // COPY !
		return $this->last_Item;
	}


	/**
	 * {@internal ItemList::get_item()}}
	 */
	function get_item( )
	{
		global $row;

		if( $this->row_num >= $this->result_num_rows )
		{ // We would pass the end!
			$this->row_num++;
			return false;
		}
		$this->row = & $this->rows[$this->row_num];
		$row = $this->row;
		// echo '<p>accessing row['. $this->row_num. ']:',$this->row->post_title,'</p>';
		$this->get_postdata();
		$this->row_num++;

		if( $this->group_by_cat && ($this->main_cat != $this->row->post_main_cat_ID) )
		{ // Category change
			// echo '<p>CAT CHANGE!</p>';
			return false;
		}

		#already done in get_postdata: $this->last_Item = new Item( $this->row ); // COPY !
		return $this->last_Item;
	}


	/**
	 * Init postdata
	 *
	 * {@internal ItemList::get_postdata(-)}}
	 *
	 * @todo we might want to move object instanciation upward rigth after the request is executed
	 *
	 */
	function get_postdata()
	{
		global $id, $postdata, $day, $page, $pages, $multipage, $more, $numpages;
		global $pagenow, $current_User;

		$this->last_Item = & $this->Obj[ $this->row_num ];

		$id = $this->last_Item->ID;
		// echo 'starting ',$current_Item->title;
		$postdata = array (
				'ID'         => $this->last_Item->ID,
				'Author_ID'  => $this->last_Item->Author->ID,
				'Date'       => $this->last_Item->issue_date,
				'Status'     => $this->last_Item->status,
				'Locale'     => $this->last_Item->locale,
				'Content'    => $this->last_Item->content,
				'Title'      => $this->last_Item->title,
				'Url'        => $this->last_Item->url,
				'Category'   => $this->last_Item->main_cat_ID,
				'Flags'      => explode( ',', $this->last_Item->flags ),
				'Wordcount'  => $this->last_Item->wordcount,
				'views'      => $this->last_Item->views,
				'comments'   => $this->last_Item->comments
			);

		$day = mysql2date('d.m.y',$postdata['Date']);
		$currentmonth = mysql2date('m',$postdata['Date']);
		$numpages = 1;
		if( !$page )
			$page = 1;
		if( isset($p) )
			$more = 1;
		$content = $postdata['Content'];
		if( preg_match('/<!--nextpage-->/', $postdata['Content']) )
		{
			if( $page > 1 )
				$more = 1;
			$multipage = 1;
			$content = $postdata['Content'];
			$content = str_replace("\n<!--nextpage-->\n", '<!--nextpage-->', $content);
			$content = str_replace("\n<!--nextpage-->", '<!--nextpage-->', $content);
			$content = str_replace("<!--nextpage-->\n", '<!--nextpage-->', $content);
			$pages = explode('<!--nextpage-->', $content);
			$numpages = count($pages);
		}
		else
		{
			$pages[0] = $postdata['Content'];
			$multipage = 0;
		}
	}


	/**
	 * Template function: Display the date if it has changed since last call
	 *
	 * {@internal ItemList::date_if_changed(-) }}
	 *
	 * @param string string to display before the date (if changed)
	 * @param string string to display after the date (if changed)
	 * @param string date/time format: leave empty to use locale default time format
	 */
	function date_if_changed( $before='<h2>', $after='</h2>', $format='' )
	{
		$current_item_date = $this->last_Item->get( 'issue_date' );
		if($format=='')
		{
			$current_item_date = mysql2date( locale_datefmt(), $current_item_date );
		}
		else
		{
			$current_item_date = mysql2date( $format, $current_item_date );
		}

		if( $current_item_date != $this->last_displayed_date )
		{
			$this->last_displayed_date = $current_item_date;

			echo $before;
			echo $current_item_date;
			echo $after;
		}
	}

	/**
	 * Template function: display message if list is empty
	 *
	 * {@internal ItemList::display_if_empty(-) }}
	 *
	 * @param string String to display if list is empty
   * @return true if empty
	 */
	function display_if_empty( $message = '' )
	{
		if( empty($message) )
		{ // Default message:
			$message = T_('Sorry, there is no post to display...');
		}

		return parent::display_if_empty( $message );
	}
}

/*
 * $Log$
 * Revision 1.32  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.31  2005/09/02 23:37:10  fplanque
 * Optimized ItemList querying
 *
 * Revision 1.30  2005/08/31 19:08:51  fplanque
 * Factorized Item query WHERE clause.
 * Fixed calendar contextual accuracy.
 *
 * Revision 1.29  2005/08/25 19:02:10  fplanque
 * categories plugin phase 2
 *
 * Revision 1.28  2005/08/25 16:06:45  fplanque
 * Isolated compilation of categories to use in an ItemList.
 * This was one of the oldest bugs on the list! :>
 *
 * Revision 1.27  2005/08/24 14:02:33  fplanque
 * minor changes
 *
 * Revision 1.26  2005/08/17 21:01:34  fplanque
 * Selection of multiple authors with (-) option.
 * Selection of multiple categories with (-) and (*) options.
 *
 * Revision 1.25  2005/06/22 14:51:43  blueyed
 * doc; use $this->blog after copying from $blog param
 *
 * Revision 1.24  2005/03/14 20:22:19  fplanque
 * refactoring, some cacheing optimization
 *
 * Revision 1.23  2005/03/10 16:07:20  fplanque
 * cleaned up paging
 * added dstart param
 *
 * Revision 1.22  2005/03/09 20:29:40  fplanque
 * added 'unit' param to allow choice between displaying x days or x posts
 * deprecated 'paged' mode (ultimately, everything should be pageable)
 *
 * Revision 1.21  2005/03/08 20:32:07  fplanque
 * small fixes; slightly enhanced WEEK() handling
 *
 * Revision 1.20  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.19  2005/02/28 01:32:32  blueyed
 * Hitlog refactoring, part uno.
 *
 * Revision 1.18  2005/02/19 18:20:47  blueyed
 * obsolete functions removed
 *
 * Revision 1.17  2005/02/10 23:51:43  blueyed
 * added preview-fix todo
 *
 * Revision 1.16  2005/02/08 04:45:02  blueyed
 * improved $DB get_results() handling
 *
 * Revision 1.15  2005/01/06 19:40:13  fplanque
 * SQL injection fix
 *
 * Revision 1.14  2005/01/03 15:17:52  fplanque
 * no message
 *
 * Revision 1.13  2004/12/27 18:37:58  fplanque
 * changed class inheritence
 *
 * Revision 1.11  2004/12/20 19:49:24  fplanque
 * cleanup & factoring
 *
 * Revision 1.10  2004/12/17 20:38:52  fplanque
 * started extending item/post capabilities (extra status, type)
 *
 * Revision 1.9  2004/12/15 20:50:34  fplanque
 * heavy refactoring
 * suppressed $use_cache and $sleep_after_edit
 * code cleanup
 *
 * Revision 1.8  2004/12/14 21:01:06  fplanque
 * minor fixes
 *
 * Revision 1.7  2004/12/14 18:32:15  fplanque
 * quick optimizations
 *
 * Revision 1.6  2004/12/13 21:29:58  fplanque
 * refactoring
 *
 * Revision 1.5  2004/12/10 19:45:55  fplanque
 * refactoring
 *
 * Revision 1.4  2004/12/09 21:21:20  fplanque
 * introduced foreign key support
 *
 * Revision 1.3  2004/11/09 00:25:12  blueyed
 * minor translation changes (+MySQL spelling :/)
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.62  2004/10/12 16:12:18  fplanque
 * Edited code documentation.
 *
 * Revision 1.4  2003/8/22 22:12:30  jupiterx
 * Added wordcount functionality
 */
?>