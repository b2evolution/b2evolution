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

class Comment extends DataObject
{
	var	$post_ID;
	var	$type;
	var	$status;
	var	$author;
	var	$author_email;
	var	$author_url;
	var	$author_IP;
	var	$date;
	var	$content;
	var	$karma;

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
			$this->author_url = $db_row['comment_author_url'];
			$this->author_IP = $db_row['comment_author_IP'];
			$this->date = $db_row['comment_date'];
			$this->content = $db_row['comment_content'];
			$this->karma = $db_row['comment_karma'];
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

	
}
?>
