<?php
/**
 * This file implements comment lists
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_class_dataobjectlist.php';

/**
 * Comment List Class
 *
 * @package evocore
 */
class CommentList extends DataObjectList
{
	var $blog;

	/**
	 * {@internal CommentList::CommentList(-)}}
	 *
	 * Constructor
	 */
	function CommentList(
		$blog = 1,
		$comment_types = "'comment'",
		$show_statuses = array(),							// Not used yet
		$p = '',															// Restrict to specific post
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
		$default_posts_per_page = '',
		$init_what_to_show = ''  )
	{
		global $DB;
		global $tablecomments;
		global $cache_categories;
		global $cat_array; // communication with recursive callback funcs
		global $pagenow;		// Bleh !

		// Call parent constructor:
		parent::DataObjectList( $tablecomments, 'comment_', 'comment_ID' );

		$this->blog = $blog;

		if( !empty($posts) )
			$this->posts_per_page = $posts;
		else $this->posts_per_page = $default_posts_per_page;

		$this->request = "SELECT DISTINCT T_comments.*
											FROM ((T_comments INNER JOIN T_posts ON comment_post_ID = ID) ";

		if( !empty( $p ) )
		{	// Restrict to comments on selected post
			$this->request .= ") WHERE comment_post_ID = $p AND ";
		}
		elseif( $blog > 1 )
		{	// Restrict to viewable posts/cats on current blog
			$this->request .= "INNER JOIN T_postcats ON ID = postcat_post_ID) INNER JOIN T_categories othercats ON postcat_cat_ID = othercats.cat_ID WHERE othercats.cat_blog_ID = $blog AND ";
		}
		else
		{	// This is blog 1, we don't care, we can include all comments:
			$this->request .= ') WHERE ';
		}

		$this->request .= "comment_type IN ($comment_types) ";

		/*
		 * ----------------------------------------------------
		 *  Restrict to the statuses we want to show:
		 * ----------------------------------------------------
		 */
		$this->request .= ' AND '.statuses_where_clause( $show_statuses );


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


		$this->request .= "ORDER BY $orderby";
		if( $this->posts_per_page ) $this->request .= " LIMIT $this->posts_per_page";

		// echo $this->request;

		$this->result = $DB->get_results( $this->request, ARRAY_A );

		if( $this->result_num_rows = $DB->num_rows )
		{
			foreach( $this->result as $row )
			{
				$this->Obj[] = & new Comment( $row );
			}
		}
	}


	/**
	 * Template function: display message if list is empty
	 *
	 * {@internal Comment::display_if_empty(-) }}
	 *
	 * @param string String to display if list is empty
   * @return true if empty
	 */
	function display_if_empty( $message = '' )
	{
		if( empty($message) )
		{	// Default message:
			$message = T_('No comment yet...');
		}

		return parent::display_if_empty( $message );
	}

}

?>