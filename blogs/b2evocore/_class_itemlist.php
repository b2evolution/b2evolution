<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file built upon code from original b2 - http://cafelog.com/
 */

function cat_req( $parent_cat_ID, $level )
{
	global $cat_array;
	// echo "[$parent_cat_ID] ";
	if( ! in_array( $parent_cat_ID, $cat_array ) )
	{	// Not already visited
		$cat_array[] = $parent_cat_ID;
	}
	else
	{
		// echo "STOP! ALREADY VISITED THIS ONE!";
		return -1;		// STOP going through that branch
	}
}

function cat_req_dummy() {}


class ItemList
{
	var $preview;
	var $blog;
	var $p;
	var $posts_per_page, $what_to_show;
	var $request;							// SQL query string
	var $result;							// Result set
	var $result_num_rows;			// Number of rows in result set
	var $result_rows;					// Array of rows
	var $postIDlist;
	var $postIDarray;

	var $total_num_posts;			// Total number of posts
	var $max_paged;						// Max page number for paged display

	var $group_by_cat;	
	
	// Used in looping
	var	$row_num;							// Current row
	var	$row;									// Current row
	var $main_cat;						// Current main category
	var $previous_main_cat;		// Previous one		
	
	/* 
	 * ItemList::ItemList(-)
	 *
	 * Constructor
	 */
	function ItemList( 
		$blog = 1, 
		$show_statuses = array(),					
		$p = '',															// Specific post number to display
		$m = '',															// YearMonth(Day) to display
		$w = '',															// Week number
		$cat = '',														// List of cats to restrict to
		$catsel = array(),										// Array of cats to restrict to
		$author = '',													// List of authors to restrict to
		$order = '',													// ASC or DESC
		$orderby = '',												// list of fields to order by
		$posts = '', 													// # of posts to display on the page
		$paged = '',													// List page number in paged display
		$poststart = '',											// Start results at this position
		$postend = '',												// End results at this position
		$s = '',															// Search string
		$sentence = '',												// Search for sentence or for words
		$exact = '',													// Require exact match of title or contents
		$preview = '',												// Is this preview
		$default_posts_per_page = '', 
		$init_what_to_show = '',
		$timestamp_min = '',									// Do not show posts before this timestamp
		$timestamp_max = 'now'  )							// Do not show posts after this timestamp
	{
		global $querycount;
		global $tableposts, $tablepostcats, $tablecategories;
		global $cache_categories, $time_difference;
		global $cat_array; // communication with recursive callback funcs
			
		$this->preview = $preview;
		$this->blog = $blog;
		$this->p = $p;

		if( !empty($posts) )
			$posts_per_page = $posts;
		elseif( !empty($default_posts_per_page) )
			$posts_per_page = $default_posts_per_page;
		else
			$posts_per_page = get_settings('posts_per_page');
		$this->posts_per_page = $posts_per_page;

		$what_to_show = (empty($init_what_to_show)) ? get_settings('what_to_show') : $init_what_to_show;
		$this->what_to_show = $what_to_show;

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
	
		// if a post number is specified, load that post
		if (($p != '') && ($p != 'all')) 
		{
			$p = intval($p);
			$where .= ' AND ID = '.$p;
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
		 * order by stuff
		 * ----------------------------------------------------
		 */
		if( (!empty($order)) && ((strtoupper($order) != 'ASC') && (strtoupper($order) != 'DESC'))) 
		{
			$order='DESC';
		}
	
		if(empty($orderby))
		{
			$orderby='date '.$order;
		}
		else
		{
			$orderby_array = explode(' ',$orderby);
			$orderby = $orderby_array[0].' '.$order;
			if (count($orderby_array)>1) 
			{
				for($i = 1; $i < (count($orderby_array)); $i++) 
				{
					$orderby .= ', post_'.$orderby_array[$i].' '.$order;
				}
			}
		}
	

		/*
		 * ----------------------------------------------------
		 * Limits:
		 * ----------------------------------------------------
		 */
		if( ($m) || ($p) ) // fp rem || ($w) || ($s) || ($whichcat) || ($author)
		{	// (no restriction if we request a month... some permalinks may point to the archive!)
			// echo 'ARCHIVE - no limits';
			$limits = '';
		}
		elseif( !empty($postend) && ($postend >= $poststart) )
		// fp removed && (!$m) && (!$w) && (!$whichcat) && (!$s) 
		{
			// echo 'POSTSTART-POSTEND';
			if ($what_to_show == 'posts' || ($what_to_show == 'paged' && (!$paged)))
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
/*		else
		{
			echo 'DEFAULT - NO LIMIT';
		}
*/	

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

	
		$this->request = "SELECT DISTINCT ID, post_author, post_date, post_status, post_lang, post_content, post_title, post_trackbacks, post_category, post_autobr, post_flags, post_wordcount, post_karma ";
	
		$this->request .= "FROM ($tableposts INNER JOIN $tablepostcats ON ID = postcat_post_ID) INNER JOIN $tablecategories ON postcat_cat_ID = cat_ID ";
		
		if( $blog == 1 )
		{	// Special case: we aggregate all cats from all blogs
			$this->request .= "WHERE 1 ";
		}
		else
		{
			$this->request .= "WHERE cat_blog_ID = $blog ";
		}
	
		$this->request .= $where." ORDER BY post_$orderby $limits";
		// echo $where;
		
		if ($preview)
		{
			$this->request = 'SELECT 0 AS ID'; // dummy mysql query for the preview
		}
	
		// echo $this->request;
		$querycount++;
		$this->result = mysql_query($this->request) or mysql_oops( $this->request );
	
		$this->result_num_rows = mysql_num_rows($this->result);
		// echo 'rows=',$this->result_num_rows,'<br />';
	
		// Make a list of posts for future queries!
		// Also make arrays...
		$this->postIDlist = "";
		$this->postIDarray = array();
		$this->result_rows = array();
		while( $myrow = mysql_fetch_object($this->result) )
		{
			$this->result_rows[] = $myrow;
			// echo "post:".$myrow["ID"]." blog:".$myrow["cat_blog_ID"]."<br />";
			array_unshift( $this->postIDarray, $myrow->ID );	// new row at beginning
		}
		if( !empty($this->postIDarray) )
		{
			$this->postIDlist = implode( ',', $this->postIDarray );
			// rewind resultset:
			// mysql_data_seek ($this->result, 0) or die( "Could not rewind resultset" );
		}
		// echo "postlist:".$postIDlist;
	
		// Initialize loop stuff:
		$this->restart();	
	}

	/*
	 * ItemList->restart(-)
	 */
	function restart()
	{
		// Set variables for future:
		global $previousday;		// Should be a member var
		$previousday = '';		
		$this->row = NULL;
		$this->row_num = 0;
		$this->main_cat = '';
		$this->group_by_cat = false;
	}


	/*
	 * ItemList->get_num_rows(-)
	 */
	function get_num_rows()
	{
		return $this->result_num_rows;
	}
	
	/*
	 * ItemList->get_max_paged(-)
	 *
	 * return maximum page number for paged display
	 */
	function get_max_paged()
	{
		if( empty($this->max_paged) )
		{	// Not already cached:
			$this->calc_max();
		}
		//echo 'max paged= ', $this->max_paged;
		return $this->max_paged;
	}


	/*
	 * ItemList->get_total_num_posts(-)
	 *
	 * return total number of posts
	 */
	function get_total_num_posts()
	{
		if( empty($this->total_num_posts) )
		{	// Not already cached:
			$this->calc_max();
		}
		return $this->total_num_posts;
	}


	/*
	 * Private ItemList->calc_max(-)
	 */
	function calc_max()
	{
		$nxt_request = $this->request;
		if( $pos = strpos(strtoupper($this->request), 'LIMIT')) 
		{
			$nxt_request = substr($this->request, 0, $pos);
		}
		$nxt_result = mysql_query($nxt_request) or mysql_oops( $nxt_request );
		$this->total_num_posts = mysql_num_rows($nxt_result);
		$this->max_paged = intval( ($this->total_num_posts-1) / max($this->posts_per_page, $this->result_num_rows)) +1;
		if( $this->max_paged < 1 ) $this->max_paged =1;
	}


	/*
	 * ItemList->get_category_group(-)
	 */
	function get_category_group()
	{
		global $row;
		
		$this->group_by_cat = true;

		if( $this->row_num > $this->result_num_rows )
		{	// We are at the the end!
			// echo 'END';
			return false;
		}

		if( $this->row_num == 0 )
		{	// We need to initialize
 			$this->row = & $this->result_rows[0];
			$row = $this->row;
 			$this->row_num = 1;
			$this->get_postdata();
		}
		
		// Memorize main cat
		$this->main_cat = $this->row->post_category;
		
		// Go back now so that the fetch row doesn't skip one!
		$this->row_num --;

		return $this->row;
	}


	/*
	 * ItemList->get_item(-)
	 */
	function get_item( )
	{
		global $row;
		if( $this->row_num >= $this->result_num_rows )
		{	// We would pass the end!
			$this->row_num++;
			return false;
		}
		$this->row = & $this->result_rows[$this->row_num];
		$row = $this->row;
		// echo '<p>accessing row['.$this->row_num.']:',$this->row->post_title,'</p>';
		$this->row_num++;
		$this->get_postdata();


		if(	$this->group_by_cat && ($this->main_cat != $this->row->post_category) )
		{	// Category change
			// echo '<p>CAT CHANGE!</p>';
			return false;
		}
		
		return $this->row;
	}


	/*
	 * ItemList->get_postdata(-) 
	 *
	 * Init postdata
	 */
	function get_postdata() 
	{
		global $id, $postdata, $authordata, $day, $page, $pages, $multipage, $more, $numpages;
		global $pagenow;

		if(!$this->preview) 
		{	// This is not preview:
			//	echo 'REAL POST';
			$row = & $this->row;
			$id = $row->ID;
			// echo 'starting ',$row->post_title;
			$postdata = array (
				'ID' => $row->ID, 
				'Author_ID' => $row->post_author,
				'Date' => $row->post_date,
				'Status' => $row->post_status, 
				'Lang' =>  $row->post_lang, 
				'Content' => $row->post_content,
				'Title' => $row->post_title,
				'Url' => $row->post_trackbacks,
				'Category' => $row->post_category,
				'AutoBR' => $row->post_autobr, 
				'Flags' => explode( ',', $row->post_flags ),
				'Wordcount' => $row->post_wordcount,
				'Karma' => $row->post_karma // this isn't used yet 
				);
		} 
		else
		{	// We are in preview mode!
			//	echo 'PREVIEW';
			// we need globals for the param function
			global $preview_userid, $preview_date, $post_status, $post_lang, $content, $post_title, $post_url, $post_category, $post_autobr, $edit_date, $aa, $mm, $jj, $hh, $mn, $ss, $user_level, $localtimenow;
	
			$id = 0;
			param( 'preview_userid', 'integer', true );
			param( 'post_status', 'string', true );
			param( 'post_lang', 'string', true );
			param( 'content', 'html', true );
			param( 'post_title', 'html', true );
			param( 'post_url', 'string', true );
			param( 'post_category', 'integer', true );
			param( 'post_autobr', 'integer', 0 );
	
			$post_title = format_to_post( $post_title, 0 ); 
			$content = format_to_post( $content, $post_autobr ); 

			param( 'edit_date', 'integer', 0 );
			if (($user_level > 4) && $edit_date) 
			{	// We use user date
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
			}
			else
			{	// We use current time
				$post_date = date('Y-m-d H:i:s', $localtimenow);
			}

	
			if( $errcontent = errors_display( 'Invalid post, please correct these errors:', '', false ) )
			{
				$content = $errcontent;
			}
			
			// little funky fix for IEwin, rawk on that code
			global $is_winIE;
			if (($is_winIE) && (!isset($IEWin_bookmarklet_fix))) 
			{
				$content =  preg_replace('/\%u([0-9A-F]{4,4})/e',  "'&#'.base_convert('\\1',16,10).';'", $content);
			}

			$postdata = array (
				'ID' => 0, 
				'Author_ID' => $preview_userid,
				'Date' => $post_date,
				'Status' => $post_status,
				'Lang' =>  $post_lang,
				'Content' => $content,
				'Title' => $post_title,
				'Url' => $post_url,
				'Category' => $post_category,
				'AutoBR' => $post_autobr,
				'Flags' => array(),
				'Wordcount' => bpost_count_words( $content ),
				'Karma' => 0 // this isn't used yet
				);
		}

		// echo ' title: ',$postdata['Title'];

		$authordata = get_userdata($postdata['Author_ID']);
		$day = mysql2date('d.m.y',$postdata['Date']);
		$currentmonth = mysql2date('m',$postdata['Date']);
		$numpages=1;
		if (!$page)
			$page=1;
		if (isset($p))
			$more=1;
		$content = $postdata['Content'];
		if (preg_match('/<!--nextpage-->/', $postdata['Content'])) 
		{
			if ($page > 1)
				$more=1;
			$multipage=1;
			$content=stripslashes($postdata['Content']);
			$content = str_replace("\n<!--nextpage-->\n", '<!--nextpage-->', $content);
			$content = str_replace("\n<!--nextpage-->", '<!--nextpage-->', $content);
			$content = str_replace("<!--nextpage-->\n", '<!--nextpage-->', $content);
			$pages=explode('<!--nextpage-->', $content);
			$numpages=count($pages);
		}
		else 
		{
			$pages[0]=stripslashes($postdata['Content']);
			$multipage=0;
		}
		return true;
	}


}

?>
