<?php
/**
 * This file implements comments
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package b2evocore
 */
require_once dirname(__FILE__).'/_class_dataobject.php';

/**
 * Comment Class
 */
class Comment extends DataObject
{
	var	$post_ID;
	var	$type;
	var	$status;
	var	$author;
	var	$author_email;
	var	$author_url;
	var	$author_ip;
	var	$date;
	var	$content;
	var	$karma;
	// Extra vars:
	var $post_title;
	var $blog_ID;
	var $blogparams;
	var $blog_name;

	/* 
	 * Comment::Comment(-)
	 *
	 * Constructor
	 */
	function Comment( $db_row = NULL )
	{
		global $tablecomments;
		
		// Call parent constructor:
		parent::DataObject( $tablecomments, 'comment_', 'comment_ID' );
	
		if( $db_row == NULL )
		{
			echo "null comment";
		}
		else
		{
			$this->ID = $db_row['comment_ID'];
			$this->post_ID = $db_row['comment_post_ID'];
			// echo 'post_ID=',$this->post_ID;
			$this->type = $db_row['comment_type'];
			$this->status = $db_row['comment_status'];
			$this->author = $db_row['comment_author'];
			$this->author_email = $db_row['comment_author_email'];
			$url = trim( $db_row['comment_author_url'] );
			$url = preg_replace('#&([^amp\;])#is', '&amp;$1', $url);	// Escape &
			$this->author_url = (!stristr($url, '://')) ? 'http://'.$url : $url;
			$this->author_ip = $db_row['comment_author_IP'];
			$this->date = $db_row['comment_date'];
			$this->content = $db_row['comment_content'];
			$this->karma = $db_row['comment_karma'];
			// Extra vars:
			if( isset( $db_row['blog_ID'] ) )
			{
				$this->blog_ID = $db_row['blog_ID'];
				$this->post_title = $db_row['post_title'];
				$this->blogparams = get_blogparams_by_ID($this->blog_ID);
				$this->blog_name = $db_row['blog_name'];
			}
		}
	}	
	
	/* 
	 * Comment::set(-)
	 *
	 * Set param value
	 */
	function set( $parname, $parvalue )
	{
		switch( $parname )
		{
			case 'post_ID':
			case 'karma':
				parent::set_param( $parname, 'int', $parvalue );
			break;
			
			default:
				parent::set_param( $parname, 'string', $parvalue );
		}
	}

	/** 
	 * Get a member param by its name
	 *
	 * {@internal Comment::get(-) }}
	 *
	 * @param mixed Name of parameter
	 * @return mixed Value of parameter
	 */
	function get( $parname )
	{
		switch( $parname )
		{
			case 'post_link':
				// Link to original post:
				return gen_permalink( get_bloginfo( 'blogurl', $this->blogparams ), 
															$this->post_ID, 'id', 'single' );

			case 'permalink':
				// Permament link to comment:
				$post_permalink = gen_permalink( get_bloginfo( 'blogurl', $this->blogparams ), 
															$this->post_ID, 'id', 'single' );
				return $post_permalink.'#c'.$this->ID;
		}
		// Default:		
		return $this->$parname;	
	}


	/** 
	 * Template function: display anchor for permalinks to refer to
	 *
	 * {@internal Comment::anchor(-) }}
	 */
	function anchor() 
	{
		echo '<a name="c'.$this->ID.'"></a>';
	}


	/** 
	 * Template function: display author of comment
	 *
	 * {@internal Comment::author(-) }}
	 *
	 * @param string Output format, see {@link format_to_output()}
	 * @param boolean true for link, false if you want NO html link
	 */
	function author( $format = 'htmlbody', $makelink = false ) 
	{
		if( strlen( $this->author_url ) <= 10 ) $makelink = false;
		
		if( $makelink ) echo '<a href="'.$this->author_url.'">';
		$this->disp( 'author', $format );
		if( $makelink ) echo '</a>';
	}


	/** 
	 * Template function: display comment's author's IP
	 *
	 * {@internal Comment::author_ip(-) }}
	 * 
	 * @param string String to display before IP, if IP exists
	 * @param string String to display after IP, if IP exists
	 */
	function author_ip( $before='', $after='' ) 
	{
		if( !empty( $this->author_ip ) )
		{
			echo $before;
			echo $this->author_ip;
			echo $after;
		}
	}


	/** 
	 * Template function: display link to comment author's provided email
	 *
	 * {@internal Comment::author_email(-) }}
	 *
	 * @param string String to display for link: leave empty to display email
	 * @param string String to display before email, if email exists
	 * @param string String to display after email, if email exists
	 * @param boolean false if you want NO html link
	 */
	function author_email( $linktext='', $before='', $after='', $makelink = true ) 
	{
		if( strlen( $this->author_email ) > 5 )
		{	// If email exists:
			echo $before;
			if( $makelink ) echo '<a href="mailto:'.$this->author_email.'">';
			echo ($linktext != '') ? $linktext : $this->author_email;
			if( $makelink ) echo '</a>';
			echo $after;
		}
	}


	/** 
	 * Template function: display link to comment author's provided URL
	 *
	 * {@internal Comment::author_url(-) }}
	 *
	 * @param string String to display for link: leave empty to display URL
	 * @param string String to display before link, if link exists
	 * @param string String to display after link, if link exists
	 * @param boolean false if you want NO html link
	 * @return boolean true if URL has been displayed
	 */
	function author_url( $linktext='', $before='', $after='', $makelink = true ) 
	{
		if( strlen( $this->author_url ) > 10 )
		{	// If URL exists:
			echo $before;
			if( $makelink ) echo '<a href="'.$this->author_url.'">';
			echo ($linktext != '') ? $linktext : $this->author_url;
			if( $makelink ) echo '</a>';
			echo $after;
			return true;
		}
		
		return false;
	}


	/** 
	 * Template function: display comment's original post's title
	 *
	 * {@internal Comment::post_title(-) }}
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function post_title( $format = 'htmlbody' ) 
	{
		$this->disp( 'post_title', $format );
	}

	/** 
	 * Template function: display link to comment's original post
	 *
	 * {@internal Comment::post_link(-) }}
	 */
	function post_link() 
	{
		$this->disp( 'post_link', 'raw' );
	}

	/** 
	 * Template function: display permalink to this comment
	 *
	 * {@internal Comment::permalink(-) }}
	 */
	function permalink() 
	{
		$this->disp( 'permalink', 'raw' );
	}

	/** 
	 * Template function: display content of comment
	 *
	 * {@internal Comment::content(-) }}
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function content( $format = 'htmlbody' ) 
	{
		global $use_textile;
	
		$comment = $this->content;
		$comment = str_replace('<trackback />', '', $comment);
		$comment = str_replace('<pingback />', '', $comment);
	
		if( $use_textile ) $comment = textile( $comment );
	
		$comment = format_to_output( $comment, $format );
		echo $comment;
	}

	/** 
	 * Template function: display date (datetime) of comment
	 *
	 * {@internal Comment::date(-) }}
	 *
	 * @param string date/time format: leave empty to use locale default date format
	 * @param boolean true if you want GMT
	 */
	function date( $format='', $useGM = false )
	{
		if( empty($format) ) 
			echo mysql2date( locale_datefmt(), $this->date, $useGM);
		else
			echo mysql2date( $format, $this->date, $useGM);
	}

	/** 
	 * Template function: display time (datetime) of comment
	 *
	 * {@internal Comment::time(-) }}
	 *
	 * @param string date/time format: leave empty to use locale default time format
	 * @param boolean true if you want GMT
	 */
	function time( $format='', $useGM = false )
	{
		if( empty($format) ) 
			echo mysql2date( locale_timefmt(), $this->date, $useGM );
		else
			echo mysql2date( $format, $this->date, $useGM );
	}

}
?>
