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
	var $shortname;
	var $name;
	var $tagline;
	var $shortdesc;	// description
	var $longdesc;
	var $locale;
	var $siteurl;
	var $filename;
	var $staticfilename;
	var $stub;
	var $blogroll;
	var $keywords;
	var $allowtrackbacks = 0;
	var $allowpingbacks = 0;
	var $pingb2evonet = 0;
	var $pingtechnorati = 0;
	var $pingweblogs = 0;
	var $pingblodotgs = 0;
	var $default_skin;
	var $disp_bloglist = 0;
	var $UID;

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
			$this->ID = $db_row->blog_ID;
			$this->shortname = $db_row->blog_shortname;
			$this->name = $db_row->blog_name;
			$this->tagline = $db_row->blog_tagline;
			$this->shortdesc = $db_row->blog_description;	// description
			$this->longdesc = $db_row->blog_longdesc;
			$this->locale = $db_row->blog_locale;
			$this->siteurl = $db_row->blog_siteurl;
			$this->filename = $db_row->blog_filename;
			$this->staticfilename = $db_row->blog_staticfilename;
			$this->stub = $db_row->blog_stub;
			$this->blogroll = $db_row->blog_roll;
			$this->keywords = $db_row->blog_keywords;
			$this->allowtrackbacks = $db_row->blog_allowtrackbacks;
			$this->allowpingbacks = $db_row->blog_allowpingbacks;
			$this->pingb2evonet = $db_row->blog_pingb2evonet;
			$this->pingtechnorati = $db_row->blog_pingtechnorati;
			$this->pingweblogs = $db_row->blog_pingweblogs;
			$this->pingblodotgs = $db_row->blog_pingblodotgs;
			$this->default_skin = $db_row->blog_default_skin;
			$this->disp_bloglist = $db_row->blog_disp_bloglist;
			$this->UID = $db_row->blog_UID;
		}
	}	


	/** 
	 * Get a param
	 *
	 * {@internal User::get(-)}}
	 */
	function get( $parname )
	{
		global $xmlsrv_url, $admin_email, $baseurl;

		switch( $parname )
		{
			case 'subdir':
				return $this->siteurl;
	
			case 'blogurl':
			case 'link':			// RSS wording
			case 'url':
				return $baseurl.$this->siteurl.'/'.$this->stub;
			
			case 'dynurl':
				return $baseurl.$this->siteurl.'/'.$this->filename;
			
			case 'staticurl':
				return $baseurl.$this->siteurl.'/'.$this->staticfilename;

			case 'baseurl':
				return $baseurl.$this->siteurl.'/';
			
			case 'blogstatsurl':
				return $baseurl.$this->siteurl.'/'.$this->stub.'?disp=stats';
			
			case 'lastcommentsurl':
				return $baseurl.$this->siteurl.'/'.$this->stub.'?disp=comments';
			
			case 'arcdirurl':
				return $baseurl.$this->siteurl.'/'.$this->stub.'?disp=arcdir';
			
			case 'description':			// RSS wording
			case 'shortdesc':
					return $this->shortdesc;
				break;
			
			case 'rdf_url':
				return $xmlsrv_url.'/rdf.php?blog='.$this->ID;

			case 'rss_url':
				return $xmlsrv_url.'/rss.php?blog='.$this->ID;
			
			case 'rss2_url':
				return $xmlsrv_url.'/rss2.php?blog='.$this->ID;
			
			case 'atom_url':
				return $xmlsrv_url.'/atom.php?blog='.$this->ID;
			
			case 'comments_rdf_url':
				return $xmlsrv_url.'/rdf.comments.php?blog='.$this->ID;
			
			case 'comments_rss_url':
				return $xmlsrv_url.'/rss.comments.php?blog='.$this->ID;
			
			case 'comments_rss2_url':
				return $xmlsrv_url.'/rss2.comments.php?blog='.$this->ID;
			
			case 'comments_atom_url':
				return $xmlsrv_url.'/atom.comments.php?blog='.$this->ID;
			
			case 'pingback_url':
				return $xmlsrv_url.'/xmlrpc.php';
			
			case 'admin_email':
				return $admin_email;
						
			default:
				// All other params:
				return parent::get( $parname );
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
