<?php
/**
 * Blog Cache Class
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
require_once dirname(__FILE__).'/_class_dataobjectcache.php';

/**
 * Blog Cache Class
 *
 * @package evocore
 */
class BlogCache extends DataObjectCache
{
	/**
	 * Constructor
	 *
	 * {@internal BlogCache::BlogCache(-) }}
	 */
	function BlogCache()
	{
		global $tableblogs;

		parent::DataObjectCache( 'Blog', false, $tableblogs, 'blog_', 'blog_ID' );
	}



	/**
	 * Get an object from cache by its url ("siteurl")
	 *
	 * Load the cache if necessary
	 *
	 * {@internal BlogCache::get_by_url(-) }}
	 *
	 * @param string URL of object to load
	 * @param boolean false if you want to return false on error
	 * @todo use cache
	 */
	function get_by_url( $req_url, $halt_on_error = true )
	{
		global $DB, $Debuglog;

		// Load just the requested object:
		$Debuglog->add( "Loading <strong>$this->objtype($req_url)</strong> into cache" );
		$sql = "SELECT *
						FROM $this->dbtablename
						WHERE blog_siteurl = ".$DB->quote($req_url);
		$row = $DB->get_row( $sql );
		if( empty( $row ) )
		{	// Requested object does not exist
			if( $halt_on_error ) die( "Requested $this->objtype does not exist!" );
			return false;
		}

		$dbIDname = $this->dbIDname;
		$objtype = $this->objtype;
		$this->cache[ $row->$dbIDname ] = new $objtype( $row ); // COPY!

		return $this->cache[ $row->$dbIDname ];
	}


	/**
	 * Get an object from cache by its URL name
	 *
	 * Load the cache if necessary
	 *
	 * {@internal BlogCache::get_by_urlname(-) }}
	 *
	 * @param string URL name of object to load
	 * @param boolean false if you want to return false on error
	 * @todo use cache
	 */
	function get_by_urlname( $req_urlname, $halt_on_error = true )
	{
		global $DB, $Debuglog;

		// Load just the requested object:
		$Debuglog->add( "Loading <strong>$this->objtype($req_urlname)</strong> into cache" );
		$sql = "SELECT *
						FROM $this->dbtablename
						WHERE blog_urlname = ".$DB->quote($req_urlname);
		$row = $DB->get_row( $sql );
		if( empty( $row ) )
		{	// Requested object does not exist
			if( $halt_on_error ) die( "Requested $this->objtype does not exist!" );
			return false;
		}

		$dbIDname = $this->dbIDname;
		$objtype = $this->objtype;
		$this->cache[ $row->$dbIDname ] = new $objtype( $row ); // COPY!

		return $this->cache[ $row->$dbIDname ];
	}


	/**
	 * load blogs of a user
	 *
	 * @param string criterion: 'member'
	 * @param integer user ID
	 * @return array the blog IDs
	 */
	function load_user_blogs( $criterion = 'member', $user_ID )
	{
		global $DB, $Debuglog;

		$Debuglog->add( "Loading <strong>$this->objtype(criterion: $criterion)</strong> into cache" );

		switch( $criterion )
		{
			case 'member':
				$where = 'bloguser_user_ID = '.$user_ID;
				break;

			case 'browse':
				$where = 'bloguser_user_ID = '.$user_ID
									.' AND bloguser_perm_media_browse = 1';
				break;
		}

		$bloglist = $DB->get_col( 'SELECT bloguser_blog_ID
																FROM T_blogusers
																WHERE '.$where );

		$this->load_list( implode( ',', $bloglist ) );

		return $bloglist;
	}
}
?>