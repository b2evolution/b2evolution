<?php
/**
 * This file implements the blog object
 *
 * @package b2evolution {@link http://b2evolution.net}
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/ }
 *
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */
require_once dirname(__FILE__).'/_class_dataobject.php';

/**
 * Blog
 *
 * Blog object with params
 */
class Blog extends DataObject
{

	/** 
	 * Constructor
	 *
	 * {@internal Blog::Blog(-) }}
	 *
	 * @param object DB row
	 */
	function Blog( $db_row = NULL )
	{
		global $tableblogs;
		
		// Call parent constructor:
		parent::DataObject( $tableblogs, 'blog_', 'blog_ID' );
	
		if( $db_row == NULL )
		{
			// echo 'Creating blank blog';
		}
		else
		{
			// echo 'Instanciating existing blog';
		}
	}	
	
	/** 
	 * Delete a blog and dependencies from database
	 *
	 * Deleted dependencies:
	 *		- Categories
	 *		- Posts
	 *		- Comments
	 *
	 * {@internal Blog::dbdelete(-) }}
	 *
	 * @todo unfinished!
	 */
	function dbdelete()
	{
		// Delete comments
		$sql="DELETE FROM $tablecomments INNER JOIN $tableposts 
									ON comment_post_ID = ID
					 WHERE ";
		$result=mysql_query($sql) or mysql_oops( $sql );
		$querycount++;

		// Delete postcats

		// Delete posts
		$sql="DELETE FROM $tableposts INNER JOIN $tablecategories 
					 WHERE post_author = ";
		$result=mysql_query($sql) or mysql_oops( $sql );
		$querycount++;
		
		// Delete categories
		$sql="DELETE FROM  
					 WHERE cat_blog_ID = $this->ID";
		$result=mysql_query($sql) or mysql_oops( $sql );
		$querycount++;

		// Delete blogusers		
		
		// Delete hitlogs
	
		// Delete main object:
		return parent::dbdelete();
	}
	
}
?>
