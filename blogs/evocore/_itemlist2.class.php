<?php
/**
 * This file implements the ItemList class 2.
 *
 * This is the object handling item/post/article lists.
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
require_once dirname(__FILE__).'/_dataobjectlist2.class.php';
require_once dirname(__FILE__).'/_item.class.php';
require_once dirname(__FILE__).'/_item.funcs.php';

/**
 * Item List Class 2
 *
 * This SECOND implementation will deprecate the first one when finished.
 *
 * @package evocore
 */
class ItemList2 extends DataObjectList2
{
	/**
	 * SQL object for the Query
	 */
	var $ItemQuery;


	/**
	 * Blog object this ItemList refers to
	 */
	var $Blog;

	/**
	 * list unit: 'posts' or 'days'
	 */
	var $unit;


	/**
	 * Did we request a single post?
	 */
 	var $single_post = false;


	/**
	 * Constructor
	 *
	 * @Param Blog
	 * @param mixed Default filter set: Do not show posts before this timestamp, can be 'now'
	 * @param mixed Default filter set: Do not show posts after this timestamp, can be 'now'
	 */
	function ItemList2(
			& $Blog,
			$timestamp_min = NULL,       // Do not show posts before this timestamp
			$timestamp_max = NULL    		 // Do not show posts after this timestamp
		)
	{
		global $ItemCache, $Settings;

		// echo '<br />Instanciating ItemList2';

		// Call parent constructor:
		parent::DataObjectList2( $ItemCache, 20, '', NULL, 'paged' );

		// The SQL Query object:
		$this->ItemQuery = & new ItemQuery( $ItemCache->dbtablename, $ItemCache->dbprefix, $ItemCache->dbIDname );

		$this->Blog = & $Blog;

		// Initialize the default filter set:
		$this->default_filters['ts_min'] = $timestamp_min;
		$this->default_filters['ts_max'] = $timestamp_max;
		$this->default_filters['cat_array'] = array();
		$this->default_filters['cat_modifier'] = '';
		$this->default_filters['authors'] = '';
		$this->default_filters['keywords'] = '';
		$this->default_filters['phrase'] = 'AND';
		$this->default_filters['exact'] = 0;
		$this->default_filters['post_ID'] = NULL;
		$this->default_filters['post_title'] = NULL;
		$this->default_filters['ymdhms'] = NULL;
    $this->default_filters['week'] = NULL;
    $this->default_filters['ymdhms_min'] = NULL;
    $this->default_filters['ymdhms_max'] = NULL;
		$this->default_filters['show_statuses_array'] = array( 'published', 'protected', 'private', 'draft', 'deprecated' );
		$this->default_filters['orderby'] = $this->Cache->dbprefix.'datestart DESC';
		$this->default_filters['unit'] = $Settings->get('what_to_show');
		$this->default_filters['posts'] = $this->limit;
		$this->default_filters['page'] = 1;
		$this->page_param = 'paged';
		$this->page = 1;

		// The current filter set is the default filter set for now:
		$this->filters = $this->default_filters;
	}


	/**
	 * Init filter params from Request params
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		global $Request;

		/*
		 * Blog & Chapters/categories restrictions:
		 */
		// Get chapters/categories (and compile those values right away)
		$Request->compile_cat_array( $this->Blog->ID == 1 ? 0 : $this->Blog->ID );

		$this->filters['cat_array'] = $Request->get( 'cat_array' );
		$this->filters['cat_modifier'] = $Request->get( 'cat_modifier' );


		/*
		 * Restrict to selected authors:
		 */
		$this->filters['authors'] = $Request->param( 'author', 'string', '', true );      // List of authors to restrict to


		/*
		 * Restrict by keywords
		 */
		$this->filters['keywords'] = $Request->param( 's', 'string', '', true );         // Search string
		$this->filters['phrase'] = $Request->param( 'sentence', 'string', 'AND', true ); // Search for sentence or for words
		$this->filters['exact'] = $Request->param( 'exact', 'integer', 0, true );        // Require exact match of title or contents


		/*
		 * Specific Item selection?
		 */
    $this->filters['post_ID'] = $Request->param( 'p', 'integer', NULL );          // Specific post number to display
		$this->filters['post_title'] = $Request->param( 'title', 'string', NULL );	  // urtitle of post to display

		$this->single_post = !empty($this->filters['post_ID']) || !empty($this->filters['post_title']);


		/*
		 * If a timeframe is specified in the querystring, restrict to that timeframe:
		 */
		$this->filters['ymdhms'] = $Request->param( 'm', 'integer', NULL, true );          // YearMonth(Day) to display
		$this->filters['week'] = $Request->param( 'w', 'integer', NULL, true );            // Week number
		$this->filters['ymdhms_min'] = $Request->param( 'dstart', 'integer', NULL, true ); // YearMonth(Day) to start at

		// TODO: show_past/future should probably be wired on dstart/dstop instead on timestamps -> get timestamps out of filter perimeter
		if( is_null($this->default_filters['ts_min'])
			&& is_null($this->default_filters['ts_max'] ) )
		{	// We have not set a strict default -> we allow overridding:
    	$show_past = $Request->param( 'show_past', 'integer', 0, true );
			$show_future = $Request->param( 'show_future', 'integer', 0, true );
			if( $show_past != $show_future )
			{	// There is a point in overridding:
				$this->filters['ts_min'] = ( $show_past == 0 ) ? 'now' : '';
				$this->filters['ts_max'] = ( $show_future == 0 ) ? 'now' : '';
			}
		}


		/*
		 * Restrict to the statuses we want to show:
		 */
		// Note: oftentimes, $show_statuses will have been preset to a more restrictive set of values
		$this->filters['show_statuses_array'] = $Request->param( 'show_status', 'array', $this->default_filters['show_statuses_array'], true );	// Array of sharings to restrict to



		/*
		 * OLD STYLE orders:
		 */
		$order = $Request->param( 'order', 'string', 'DESC', true );   // ASC or DESC
		$orderby = $Request->param( 'orderby', 'string', '', true );     // list of fields to order by (TODO: change that crap)

		if( (strtoupper($order) != 'ASC') && (strtoupper($order) != 'DESC') )
		{
			$order = 'DESC';
		}

		if(empty($orderby))
		{
			$this->filters['orderby'] = $this->Cache->dbprefix.'datestart '.$order;
		}
		else
		{
			$orderby_array = explode(' ',$orderby);
			$this->filters['orderby'] = $this->Cache->dbprefix.implode( ' '.$order.', '.$this->Cache->dbprefix, $orderby_array ).' '.$order;
		}



		/*
		 * Paging limits:
		 */
 		$this->filters['unit'] = $Request->param( 'unit', 'string', $this->default_filters['unit'], true );    		// list unit: 'posts' or 'days'
		$this->unit = $this->filters['unit'];	// TEMPORARy
		//echo '<br />unit='.$this->filters['unit'];

		$this->filters['posts'] = $Request->param( 'posts', 'integer', $this->default_filters['posts'], true ); 			// # of units to display on the page
		$this->limit = $this->filters['posts']; // for compatibility with parent class

		// 'paged'
		$this->filters['page'] = $Request->param( $this->page_param, 'integer', 1, true );      // List page number in paged display
		$this->page = $this->filters['page'];

 		// When in backoffice...  (to be deprecated...)
 		$this->poststart = $Request->param( 'poststart', 'integer', 0, true );   // Start results at this position
		$this->postend = $Request->param( 'postend', 'integer', 0, true );    // End results at this position



		if( $Request->validation_errors() )
		{
			return false;
		}


		if( $this->single_post )
		{	// We have requested a specific post
			// Do not attempt to save or load any filterset:
			return true;
		}


		// echo 'Is filtered? '.($this->is_filtered() ? 'yes' : 'no');

		global $Session;

		$filterset_name = 'ItemList_filters_'.$this->Blog->ID;

		if( $this->is_filtered() )
		{	// We have an active filter:
			// Let's memorize that filter into the session:
			// echo 'saving filterset';
			$Session->set( $filterset_name, $this->filters );
		}
		else
		{	// No requested filter set, let's see if we can load a previously used filter set:
			$filters = $Session->get( $filterset_name );
			if( !empty($filters) )
			{ // We have filters:
				// echo 'restoring filterset';

				// Restore filters:
				$this->filters = $filters;

				// Set back the GLOBALS !!! needed for regenerate_url.

				/*
				 * Blog & Chapters/categories restrictions:
				 */
				// Get chapters/categories (and compile those values right away)
				$Request->set_param( 'cat_array', $this->filters['cat_array'] );
				$Request->set_param( 'cat_modifier', $this->filters['cat_modifier'] );
 				$Request->set_param( 'cat', $this->filters['cat_modifier'].implode(',',$this->filters['cat_array']) );  // List of authors to restrict to

				/*
				 * Restrict to selected authors:
				 */
				$Request->set_param( 'author', $this->filters['authors'] );  // List of authors to restrict to

				/*
				 * Restrict by keywords
				 */
				$Request->set_param( 's', $this->filters['keywords'] );			 // Search string
				$Request->set_param( 'sentence', $this->filters['phrase'] ); // Search for sentence or for words
				$Request->set_param( 'exact', $this->filters['exact'] );     // Require exact match of title or contents

				/*
				 * Specific Item selection?
				 */
				$Request->set_param( 'm', $this->filters['ymdhms'] );          // YearMonth(Day) to display
				$Request->set_param( 'w', $this->filters['week'] );            // Week number
				$Request->set_param( 'dstart', $this->filters['ymdhms_min'] ); // YearMonth(Day) to start at

				// TODO: show_past/future should probably be wired on dstart/dstop instead on timestamps -> get timestamps out of filter perimeter
				if( is_null($this->default_filters['ts_min'])
					&& is_null($this->default_filters['ts_max'] ) )
				{	// We have not set a strict default -> we allow overridding:
    			$Request->set_param( 'show_past', ($this->filters['ts_min'] == 'now') ? 0 : 1 );
					$Request->set_param( 'show_future', ($this->filters['ts_max'] == 'now') ? 0 : 1 );
				}

     		/*
				 * Restrict to the statuses we want to show:
				 */
				// Note: oftentimes, $show_statuses will have been preset to a more restrictive set of values
				$Request->set_param( 'show_status', $this->filters['show_statuses_array'] );	// Array of sharings to restrict to

				/*
				 * OLD STYLE orders:
				 */
				// TODO!

				/*
				 * Paging limits:
				 */
 				$Request->set_param( 'unit', $this->filters['unit'] );    		// list unit: 'posts' or 'days'
				$this->unit = $this->filters['unit'];	// TEMPORARy

				$Request->set_param( 'posts', $this->filters['posts'] ); 			// # of units to display on the page
				$this->limit = $this->filters['posts']; // for compatibility with parent class

				// 'paged'
				$Request->set_param( $this->page_param, $this->filters['page'] );      // List page number in paged display
				$this->page = $this->filters['page'];
			}
		}


		// pre_dump( $this->default_filters );
		// pre_dump( $this->filters );


		return true;
	}


	/**
	 *
	 * Note: so far, the query should not be run without a prior call to load_from_request()
	 * Otherwise filtering will not be complete 'not even values given in the constructor will work)
	 *
	 * @todo count?
	 */
	function query()
	{
		global $DB;

		if( !is_null( $this->rows ) )
		{ // Query has already executed:
			return;
		}

		// echo '<br />ItemList2 query';

		// GENERATE THE QUERY:

		/*
		 * filetring stuff:
		 */
		$this->ItemQuery->where_chapter2( $this->Blog->ID, $this->filters['cat_array'], $this->filters['cat_modifier'] );

		$this->ItemQuery->where_author( $this->filters['authors'] );

		$this->ItemQuery->where_keywords( $this->filters['keywords'], $this->filters['phrase'], $this->filters['exact'] );

		$this->ItemQuery->where_ID( $this->filters['post_ID'], $this->filters['post_title'] );

		$this->ItemQuery->where_datestart( $this->filters['ymdhms'], $this->filters['week'],
		                                   $this->filters['ymdhms_min'], $this->filters['ymdhms_max'],
		                                   $this->filters['ts_min'], $this->filters['ts_max'] );

		$this->ItemQuery->where_status( $this->filters['show_statuses_array'] );


		/*
		 * order by stuff:
		 */
		// Memorize requested order list:
		$this->ItemQuery->order_by( $this->filters['orderby'] );


		/*
		 * Paging limits:
		 */
		if( $this->single_post )   // p or title
		{ // Single post: no paging required!
		}
		elseif( !empty($this->filters['poststart']) )
		{ // When in backoffice...  (to be deprecated...)
			// echo 'POSTSTART-POSTEND ';
			$poststart = $this->poststart;
			$postend = $this->poststart;
			if( $postend < $poststart )
			{
				$postend = $poststart + $this->limit - 1;
			}

			if( $this->filters['unit'] == 'posts' )
			{
				$posts = $postend - $poststart + 1;
				$this->ItemQuery->LIMIT( ($poststart-1).', '.$posts );
			}
			elseif( $this->filters['unit'] == 'days' )
			{
				$posts = $postend - $poststart + 1;
				// echo 'days=',$posts;
				$lastpostdate = $this->get_lastpostdate();
				$lastpostdate = mysql2date('Y-m-d 23:59:59',$lastpostdate);
				// echo $lastpostdate;
				$lastpostdate = mysql2timestamp( $lastpostdate );
				$this->limitdate_end = $lastpostdate - (($poststart -1) * 86400);
				$this->limitdate_start = $lastpostdate+1 - (($postend) * 86400);
				$this->ItemQuery->WHERE_and( $this->dbprefix.'datestart >= \''. date( 'Y-m-d H:i:s', $this->limitdate_start )
									.'\' AND '.$this->dbprefix.'datestart <= \''. date('Y-m-d H:i:s', $this->limitdate_end) . '\'' );
			}
		}
		elseif( !empty($this->filters['ymdhms']) )
		{ // no restriction if we request a month... some permalinks may point to the archive!
			// echo 'ARCHIVE - no limits';
		}
		elseif( $this->filters['unit'] == 'posts' )
		{
			// echo 'LIMIT POSTS ';
			$pgstrt = '';
			if( $this->page > 1 )
			{ // We have requested a specific page number
				$pgstrt = (intval($this->page) -1) * $this->limit. ', ';
			}
			$this->ItemQuery->LIMIT( $pgstrt.$this->limit );
		}
		elseif( $this->unit == 'days' )
		{ // We are going to limit to x days:
			// echo 'LIMIT DAYS ';
			if( empty( $this->filters['ymdhms_min'] ) )
			{ // We have no start date, we'll display the last x days:
				if( !empty($this->filters['keywords'])
					|| !empty($this->filters['cat_array'])
					|| !empty($this->filters['authors']) )
				{ // We are in DAYS mode but we can't restrict on these! (TODO: ?)
					$limits = '';
				}
				else
				{ // We are going to limit to LAST x days:
					$lastpostdate = $this->get_lastpostdate();
					$lastpostdate = mysql2date('Y-m-d 00:00:00',$lastpostdate);
					$lastpostdate = mysql2date('U',$lastpostdate);
					// go back x days
					$otherdate = date('Y-m-d H:i:s', ($lastpostdate - (($this->limit-1) * 86400)));
					$this->ItemQuery->WHERE_and( $this->dbprefix.'datestart > \''. $otherdate.'\'' );
				}
			}
			else
			{ // We have a start date, we'll display x days starting from that point:
				// $dstart_mysql has been calculated earlier

				// TODO: this is redundant with previous dstart processing:
				// Add trailing 0s: YYYYMMDDHHMMSS
				$dstart0 = $this->filters['ymdhms_min'].'00000000000000';

				$dstart_mysql = substr($dstart0,0,4).'-'.substr($dstart0,4,2).'-'.substr($dstart0,6,2).' '
												.substr($dstart0,8,2).':'.substr($dstart0,10,2).':'.substr($dstart0,12,2);
				$dstart_ts = mysql2timestamp( $dstart_mysql );
				// go forward x days
				$enddate_ts = date('Y-m-d H:i:s', ($dstart_ts + ($this->limit * 86400)));
				$this->ItemQuery->WHERE_and( $this->dbprefix.'datestart < \''. $enddate_ts.'\'' );
			}
		}
		else
			die( 'Unhandled LIMITING mode in ItemList (paged mode is obsolete)' );



		// GET TOTAL ROW COUNT:
		/*
		 * TODO: The result is incorrect when using AND on categories
		 * We would need to use a HAVING close and thyen COUNT, which would be a subquery
		 * This is nto compatible with mysql 3.23
		 * We need fallback code.
		 */
		$sql_count = '
			SELECT COUNT( DISTINCT '.$this->Cache->dbIDname.') '
				.$this->ItemQuery->get_from()
				.$this->ItemQuery->get_where();

		//echo $DB->format_query( $sql_count );

		parent::count_total_rows( $sql_count );
		//echo '<br />'.$this->total_rows;


		// GET DATA ROWS:


  	// New style orders:
		$this->ItemQuery->ORDER_BY_prepend( $this->get_order_field_list() );


		// We are going to proceed in two steps (we simulate a subquery)
		// 1) we get the IDs we need
		// 2) we get all the other fields matching these IDs
		// This is more efficient than manipulating all fields at once.

		// Step 1:
		$step1_sql = 'SELECT DISTINCT '.$this->Cache->dbIDname
									.$this->ItemQuery->get_from()
									.$this->ItemQuery->get_where()
									.$this->ItemQuery->get_group_by()
									.$this->ItemQuery->get_order_by()
									.$this->ItemQuery->get_limit();

		// echo $DB->format_query( $step1_sql );

		// Get list of the IDs we need:
		$ID_list = $DB->get_list( $step1_sql, 0, 'Get ID list for ItemList2 (Main|Lastpostdate) Query' );

		// Step 2:
		$this->sql = 'SELECT *
			              FROM '.$this->Cache->dbtablename;
		if( !empty($ID_list) )
		{
			$this->sql .= ' WHERE '.$this->Cache->dbIDname.' IN ('.$ID_list.') '
										.$this->ItemQuery->get_order_by();
		}
		else
		{
			$this->sql .= ' WHERE 0';
		}

		//echo $DB->format_query( $this->sql );

		parent::query( $this->sql, false, false );
	}


	/**
	 * Generate a title for the current list, depending on its filtering params
	 *
	 * @todo cleanup some displays
	 * @todo implement HMS part of YMDHMS
	 *
	 * @return array
   */
  function get_filter_titles()
  {
		global $month, $post_statuses;

  	$title_array = array();


  	if( $this->single_post )
		{	// We have requested a specific post:
			// Should be in first position
			$Item = & $this->get_by_idx( 0 );

			if( is_null($Item) )
			{
				$title_array[] = T_('Invalid request');
			}
			else
			{
				$title_array[] = T_('Post details').': '.$Item->get('title');
			}
			return $title_array;
		}


		// CATEGORIES:
  	if( !empty($this->filters['cat_array']) )
  	{ // We have requested specific categories...
			$cat_names = array();
			foreach( $this->filters['cat_array'] as $cat_ID )
			{
				if( ($my_cat = get_the_category_by_ID( $cat_ID, false ) ) !== false )
				{ // It is almost never meaningful to die over an invalid cat when generating title
					$cat_names[] = $my_cat['cat_name'];
				}
			}
			if( $this->filters['cat_modifier'] == '*' )
			{
				$cat_names_string = implode( ' + ', $cat_names );
			}
			else
			{
				$cat_names_string = implode( ', ', $cat_names );
			}
			if( !empty( $cat_names_string ) )
			{
				if( $this->filters['cat_modifier'] == '-' )
				{
					$cat_names_string = T_('All but ').$cat_names_string;
					$title_array['cats'] = T_('Categories').': '.$cat_names_string;
				}
				else
				{
					if( count($this->filters['cat_array']) > 1 )
						$title_array['cats'] = T_('Categories').': '.$cat_names_string;
					else
						$title_array['cats'] = T_('Category').': '.$cat_names_string;
				}
			}
		}


		// ARCHIVE TIMESLOT:
		if( !empty($this->filters['ymdhms']) )
		{	// We have asked for a specific timeframe:

			$my_year = substr($this->filters['ymdhms'],0,4);

			if( strlen($this->filters['ymdhms']) > 4 )
			{ // We have requested a month too:
				$my_month = T_($month[substr($this->filters['ymdhms'],4,2)]);
			}
			else
			{
				$my_month = '';
			}

			// Requested a day?
			$my_day = substr($this->filters['ymdhms'],6,2);

			$arch = T_('Archives for').': '.$my_month.' '.$my_year;

			if( !empty( $my_day ) )
			{	// We also want to display a day
				$arch .= ", $my_day";
			}

			if( !empty($this->filters['week']) || ($this->filters['week'] === 0) ) // Note: week # can be 0
			{	// We also want to display a week number
				$arch .= ', '.T_('week').' '.$this->filters['week'];
			}

			$title_array['ymdhms'] = $arch;
		}


 		// KEYWORDS:
		if( !empty($this->filters['keywords']) )
		{
			$title_array['keywords'] = T_('Keyword(s)').': '.$this->filters['keywords'];
		}


		// AUTHORS:
		if( !empty($this->filters['authors']) )
		{
			$title_array[] = T_('Author(s)').': '.$this->filters['authors'];
		}


		// SHOW STATUSES
		if( count( $this->filters['show_statuses_array'] ) < 5 ) // TEMP
		{
			$status_titles = array();
			foreach( $this->filters['show_statuses_array'] as $status )
			{
				$status_titles[] = T_( $post_statuses[$status] );
			}
			$title_array[] = T_('Visibility').': '.implode( ', ', $status_titles );
		}


		// START AT
		if( !empty($this->filters['ymdhms_min'] ) )
		{
			$title_array['ymdhms_min'] = T_('Start at').': '.$this->filters['ymdhms_min'] ;
		}
		if( !empty($this->filters['ts_min'] ) )
		{
			if( $this->filters['ts_min'] == 'now' )
			{
				$title_array['ts_min'] = T_('Hide past');
			}
			else
			{
				$title_array['ts_min'] = T_('Start at').': '.$this->filters['ts_min'];
			}
		}


		// STOP AT
		if( !empty($this->filters['ymdhms_max'] ) )
		{
			$title_array['ymdhms_max'] = T_('Stop at').': '.$this->filters['ymdhms_max'];
		}
		if( !empty($this->filters['ts_max'] ) )
		{
			if( $this->filters['ts_max'] == 'now' )
			{
				$title_array['ts_max'] = T_('Hide future');
			}
			else
			{
				$title_array['ts_max'] = T_('Stop at').': '.$this->filters['ts_max'];
			}
		}


		return $title_array;
	}


	/**
	 * return total number of posts
	 *
	 * This is basically just a stub for backward compatibility
	 *
	 * @deprecated
	 */
	function get_total_num_posts()
	{
		return $this->total_rows;
	}


	/**
	 * This is basically just a stub for backward compatibility
	 *
	 * @deprecated
	 */
	function & get_item()
	{
		$Item = & parent::get_next();
		return $Item;
	}

}

/*
 * $Log$
 * Revision 1.5  2005/12/21 20:42:28  fplanque
 * filterset saving & restore if no filters are set.
 * Note: reset all is broken.
 * poststart/postend navigation in experimental tab is broken too.
 *
 * Revision 1.4  2005/12/20 19:23:40  fplanque
 * implemented filter comparison/detection
 *
 * Revision 1.3  2005/12/20 18:12:50  fplanque
 * enhanced filtering/titling framework
 *
 * Revision 1.2  2005/12/19 19:30:14  fplanque
 * minor
 *
 * Revision 1.1  2005/12/08 13:13:33  fplanque
 * no message
 *
 */
?>