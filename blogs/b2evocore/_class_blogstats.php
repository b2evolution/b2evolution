<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 *	Filename:	/b2evocore/_class_blogstats.php
 *	Created on:	12/12/03 
 *	Created by:	Travis Swicegood <travis@domain51productions.com>
 *	File Description
 *		This file contains the code for the blogstats class.  The main purpose of this class
 *		is to create information necessary for statistical information about the blog without
 *		loading everything as the ItemList class does.
 *
 *	Practical Use
 *		The first use of this class is to generate a total number of blog entries.  Searches
 *		can be limited to specific blogs and/or categories.  This number would them be used to 
 *		create random number within a range to display a random blog entry.  Example: Blog 
 *		that contains a list of quotes with a random quote being displayed each time a page 
 *		is loaded.
 *
 *	Last Modified
 *		12/12/03 - Travis Swicegood: Created file.
 */
 
class BlogStats{
	var $blog;				// Blog # (1 = all blogs)
	var $request;			// SQL query string
	var $result;			// SQL results
	var $total_posts;		// Number of total posts (loaded via $this->get_post_total())

	function BlogStats( 
		$blog = 1, 							// What blog to display (def: all blogs)
		$show_statuses = array(),			// What status to display?
//		$p = '',							// Specific post number to display
		$m = '',							// YearMonth(Day) to display
		$w = '',							// Week number
		$cat = '',							// Category(s): 1,2,3
		$catsel = array(),					// Same as above except array
		$author = '',						// List of authors to restrict to
		$posts = '', 						// # of posts to display on the page
		$poststart = '',					// Start results at this position
		$postend = '',						// End results at this position
		$s = '',							// Search string
		$sentence = '',						// Search for sentence or for words
		$exact = '',						// Require exact match of title or contents
		$init_what_to_show = '',			// Type of display (example: "posts")
		$timestamp_min = '',				// Do not show posts before this timestamp
		$timestamp_max = 'now'  )			// Do not show posts after this timestamp
	{
	//////
	//	Handle global calls
		global $querycount;										// Total number of queries
		global $tableposts, $tablepostcats, $tablecategories;	// ?
		global $cache_categories, $time_difference;				// ?
		global $cat_array; 										// communication with recursive callback funcs
	
	//////
	//	Which blog is used?
		$this->blog = $blog;

	////////
	// First let's clear some variables
		$whichcat = '';
		$whichauthor = '';
		$result = '';
		$where = '';
		$limits = '';
		$distinct = '';

		// WE ARE GOING TO CONSTRUCT THE "AND" CLOSE
		// THIS IS GOING TO LAST FOR MANY MANY LINES...
		
		// if a month is specified in the querystring, load that month
		if ($m != '') 
		{
			$m = ''.intval($m);
			$where .= ' AND YEAR(post_date)='.substr($m,0,4);
			if (strlen($m)>5)
				$where .= ' AND MONTH(post_date)='.substr($m,4,2);
			if (strlen($m)>7)
				$where .= ' AND DAYOFMONTH(post_date)='.substr($m,6,2);
			if (strlen($m)>9)
				$where .= ' AND HOUR(post_date)='.substr($m,8,2);
			if (strlen($m)>11)
				$where .= ' AND MINUTE(post_date)='.substr($m,10,2);
			if (strlen($m)>13)
				$where .= ' AND SECOND(post_date)='.substr($m,12,2);
		}
	
		// If a week number is specified
		if ($w != '') 
		{
			$w = ''.intval($w);
			$where .= ' AND WEEK(post_date,1)='.$w;
		}
	
		/*
		 * ----------------------------------------------------
		 * Search stuff:
		 * ----------------------------------------------------
		 */
		if(!empty($s)) 
		{
			$search = ' AND (';
			if ($exact)	// We want exact match of title or contents
				$n = '';
			else // The words/sentence are/is to be included in in the title or the contents
				$n = '%';
			if( ($sentence == '1') or ($sentence == 'sentence') ) 
			{ // Sentence search
				$s = trim($s);
				$search .= '(post_title LIKE \''.$n.$s.$n.'\') OR (post_content LIKE \''.$n.$s.$n.'\')';
			}
			else
			{	// Word search
				if( strtoupper( $sentence ) == 'OR' ) 
					$swords = 'OR';
				else
					$swords = 'AND';
			
				// puts spaces instead of commas
				$s = preg_replace('/, +/', '', $s);
				$s = str_replace(',', ' ', $s);
				$s = str_replace('"', ' ', $s);
				$s = trim($s);
				$s_array = explode(' ',$s);
				$join = '';
				for ( $i = 0; $i < count($s_array); $i++) 
				{
					$search .= ' '.$join.' ( (post_title LIKE \''.$n.$s_array[$i].$n.'\') OR (post_content LIKE \''.$n.$s_array[$i].$n.'\') ) ';
					$join = $swords;
				}
			} 

			$search .= ')';

			//echo $search;
		}
		else
		{
			$search = ''; 
		}
	
		/*
		 * ----------------------------------------------------
		 * Category stuff:
		 * ----------------------------------------------------
		 */
		$eq = 'IN'; // default
	
		$cat_array = array();		// this is a global var
	
		// Check for cat string (which will be handled recursively)
		if ( ! ((empty($cat)) || ($cat == 'all') || ($cat == '0')) )
		{	// specified a category string:
			$cat = str_replace(',', ' ', $cat);
			if( strstr($cat,'-') )
			{	// We want to exclude cats
				$eq = 'NOT IN';
				$cats = explode('-',$cat);
				$req_cat_array = explode(' ',$cats[1]);
			}
			else
			{	// We want to include cats
				$req_cat_array = explode(' ',$cat);
			}
	
			// Getting required sub-categories:
			// and add everything to cat array
			// ----------------- START RECURSIVE CAT LIST ----------------
			cat_query();	// make sure the caches are loaded
			foreach( $req_cat_array as $cat_ID )
			{ // run recursively through the cats
				settype( $cat_ID, 'integer' ); // make sure
				if( ! in_array( $cat_ID, $cat_array ) )
				{	// Not already in list
					$cat_array[] = $cat_ID;
					cat_children( $cache_categories, ($blog==1)?0:$blog, $cat_ID, 'cat_req_dummy', 'cat_req',
												'cat_req_dummy', 'cat_req_dummy', 1 );
				}
			}
			// ----------------- END RECURSIVE CAT LIST ----------------
		}
	
		// Add explicit selections:
		if( ! empty( $catsel ))
		{
			// echo "Explicit selections!<br />";
			$cat_array = array_merge( $cat_array, $catsel );
			array_unique( $cat_array );
		}
	
		if( empty($cat_array) )
		{
			$whichcat='';
		}
		else
		{
			$whichcat .= ' AND postcat_cat_ID '.$eq.' ('.implode(",", $cat_array).') ';
			// echo $whichcat;
		}
	
	
	
		/*
		 * ----------------------------------------------------
		 * Author stuff:
		 * ----------------------------------------------------
		 */
		if((empty($author)) || ($author == 'all')) 
		{
			$whichauthor='';
		} 
		elseif (intval($author)) 
		{
			$author = intval($author);
			if (stristr($author, '-')) 
			{
				$eq = '!=';
				$andor = 'AND';
				$author = explode('-', $author);
				$author = $author[1];
			} else {
				$eq = '=';
				$andor = 'OR';
			}
			$author_array = explode(' ', $author);
			$whichauthor .= ' AND post_author '.$eq.' '.$author_array[0];
			for ($i = 1; $i < (count($author_array)); $i = $i + 1) {
				$whichauthor .= ' '.$andor.' post_author '.$eq.' '.$author_array[$i];
			}
		}

		$where .= $search.$whichcat.$whichauthor;

	
		/*
		 * ----------------------------------------------------
		 * Limits:
		 * ----------------------------------------------------
		 */
		if( !empty($poststart) )
		// fp removed && (!$m) && (!$w) && (!$whichcat) && (!$s) 
		// fp added: when in backoffice: always page
		{
			// echo 'POSTSTART-POSTEND';
			if( $postend < $poststart )
			{
				$postend = $poststart + $posts_per_page - 1;
			}
			
			if ($what_to_show == 'posts' || $what_to_show == 'paged')
			{
				$posts = $postend - $poststart + 1;
				$limits = ' LIMIT '.($poststart-1).','.$posts;
			}
			elseif ($what_to_show == 'days')
			{
				$posts = $postend - $poststart + 1;
				$lastpostdate = get_lastpostdate( $blog, $show_statuses );
				$lastpostdate = mysql2date('Y-m-d 00:00:00',$lastpostdate);
				$lastpostdate = mysql2date('U',$lastpostdate);
				$startdate = date('Y-m-d H:i:s', ($lastpostdate - (($poststart -1) * 86400)));
				$otherdate = date('Y-m-d H:i:s', ($lastpostdate - (($postend -1) * 86400)));
				$where .= ' AND post_date > \''.$otherdate.'\' AND post_date < \''.$startdate.'\'';
			}
		}
		elseif( ($m) || ($p) ) // fp rem || ($w) || ($s) || ($whichcat) || ($author)
		{	// (no restriction if we request a month... some permalinks may point to the archive!)
			// echo 'ARCHIVE - no limits';
			$limits = '';
		}
		elseif ($what_to_show == 'posts')
		{
			// echo 'LIMIT POSTS';
			$limits = ' LIMIT '.$posts_per_page;
		}
		elseif( $what_to_show == 'paged' )
		{	
			// echo 'PAGED';
			$pgstrt = '';
			if ($paged) {
				$pgstrt = (intval($paged) -1) * $posts_per_page . ', ';
			}
			$limits = 'LIMIT '.$pgstrt.$posts_per_page;
		}
		elseif ($what_to_show == 'days')
		{
			// echo 'LIMIT DAYS';
			$lastpostdate = get_lastpostdate( $blog, $show_statuses );
			$lastpostdate = mysql2date('Y-m-d 00:00:00',$lastpostdate);
			$lastpostdate = mysql2date('U',$lastpostdate);
			$otherdate = date('Y-m-d H:i:s', ($lastpostdate - (($posts_per_page-1) * 86400)));
			$where .= ' AND post_date > \''.$otherdate.'\'';
		}
	

		/*
		 * ----------------------------------------------------
		 *  Restrict to the statuses we want to show:
		 * ----------------------------------------------------
		 */
		$where .= ' AND '.statuses_where_clause( $show_statuses );

		/*
		 * ----------------------------------------------------
		 * Time limits:
		 * ----------------------------------------------------
		 */
		if( $timestamp_min == 'now' ) 
		{
			// echo 'hide past';
			$timestamp_min = time();
		}
		if( !empty($timestamp_min) ) 
		{	// Hide posts before
			// echo 'before';
			$date_min = date('Y-m-d H:i:s', $timestamp_min + ($time_difference * 3600) );
			$where .= ' AND post_date >= \''.$date_min.'\'';
		}

		if( $timestamp_max == 'now' )
		{
			// echo 'hide future';
			$timestamp_max = time();
		}
		if( !empty($timestamp_max) ) 
		{	// Hide posts after
			// echo 'after';
			$date_max = date('Y-m-d H:i:s', $timestamp_max + ($time_difference * 3600) );
			$where .= ' AND post_date <= \''.$date_max.'\'';
		}

	
		$this->request = "SELECT COUNT( DISTINCT post_id ) as total_posts ";
	
		$this->request .= "FROM 
				($tableposts 
					INNER JOIN $tablepostcats ON ID = postcat_post_ID) 
					INNER JOIN $tablecategories ON postcat_cat_ID = cat_ID ";
		
		if( $blog == 1 )
		{	// Special case: we aggregate all cats from all blogs
			$this->request .= "WHERE 1 ";
		}
		else
		{
			$this->request .= "WHERE cat_blog_ID = $blog ";
		}
	
		
		if ($preview)
		{
			$this->request = 'SELECT 0 AS ID'; // dummy mysql query for the preview
		}
	
		//echo $this->request;
		$querycount++;
		$this->result = mysql_query($this->request) or mysql_oops( $this->request );
		
		$row = mysql_fetch_object( $this->result );
		$this->total_posts = $row->total_posts;

	}

}
?>