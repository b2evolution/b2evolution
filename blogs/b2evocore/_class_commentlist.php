<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */

class CommentList
{
	var $blog;
	var $posts_per_page;
	var $request;							// SQL query string
	var $result;							// Result set
	var $result_num_rows;			// Number of rows in result set
	
	/* 
	 * CommentList::CommentList(-)
	 *
	 * Constructor
	 */
	function CommentList( 
		$blog = 1, 
		$comment_types = "'comment'",
		$show_statuses = array(),							// Not used yet					
		$p = '',															// Not used yet
		$author = '',													// Not used yet
		$order = 'DESC',											// ASC or DESC
		$orderby = '',												// list of fields to order by
		$posts = '', 													// # of comments to display on the page
		$paged = '',													// Not used yet
		$poststart = '',											// Not used yet
		$postend = '',												// Not used yet
		$s = '',															// Not used yet
		$sentence = '',												// Not used yet
		$exact = '',													// Not used yet
		$posts_per_page = '', 
		$init_what_to_show = ''  )
	{
		global $querycount;
		global $tablecomments, $tableposts, $tablecategories, $tableblogs, $tablepostcats;
		global $cache_categories;
		global $cat_array; // communication with recursive callback funcs
		global $pagenow;		// Bleh !
	
		$this->blog = $blog;
		
		if( !empty($posts) )
			$posts_per_page = $posts;
		elseif( !empty($default_posts_per_page) )
			$posts_per_page = $default_posts_per_page;
		else
			$posts_per_page = get_settings('posts_per_page');
		$this->posts_per_page = $posts_per_page;

		$this->request = "SELECT DISTINCT comment_ID, comment_post_ID, comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_content, comment_karma, comment_type, ID, post_title, blog_name, blog_siteurl, blog_stub ";
		
		$this->request .= "FROM (((($tablecomments INNER JOIN $tableposts ON comment_post_ID = ID) ";
		
		// Find main blog for each related post  TODO: use get_blogaprams_by_ID
		$this->request .= "INNER JOIN $tablecategories maincat ON post_category = maincat.cat_ID) INNER JOIN $tableblogs ON maincat.cat_blog_ID = blog_ID) ";
		
		// Restrict to viewable posts/cats on current blog
		if( $blog > 1 )
		{
			$this->request .= "INNER JOIN $tablepostcats ON ID = postcat_post_ID) INNER JOIN $tablecategories othercats ON postcat_cat_ID = othercats.cat_ID WHERE othercats.cat_blog_ID = $blog AND ";
		}
		else
		{	// This is blog 1, we don't care, we can include all comments:
			$this->request .= ') WHERE ';
		}
		
		$this->request .= "comment_type IN ($comment_types) ";
		
		// order by stuff
		if( (!empty($order)) && ((strtoupper($order) != 'ASC') && (strtoupper($order) != 'DESC'))) 
		{
			$order='DESC';
		}
	
		if(empty($orderby))
		{
			$orderby='comment_date '.$order;
		}
		else
		{
			$orderby_array = explode(' ',$orderby);
			$orderby = $orderby_array[0].' '.$order;
			if (count($orderby_array)>1) 
			{
				for($i = 1; $i < (count($orderby_array)); $i++) 
				{
					$orderby .= ', comment_'.$orderby_array[$i].' '.$order;
				}
			}
		}


		$this->request .= "ORDER BY $orderby LIMIT $posts_per_page";

		// echo $this->request;
		
		$querycount++;
	
		$this->result = mysql_query($this->request) or mysql_oops( $this->request );
	
		$this->result_num_rows = mysql_num_rows($this->result);
		// echo 'rows=',$this->result_num_rows,'<br />';
		
	}

	/*
	 * ItemList->get_num_rows(-)
	 */
	function get_num_rows()
	{
		return $this->result_num_rows;
	}
	
}

?>