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
	 * Get an object from cache by its stub
	 *
	 * Load the cache if necessary
	 *
	 * {@internal BlogCache::get_by_stub(-) }}
	 *
	 * @param string stub of object to load
	 * @param boolean false if you want to return false on error
	 */
	function get_by_stub( $req_stub, $halt_on_error = true )
	{
		global $DB, $Debuglog;

		// Load just the requested object:
		$Debuglog->add( "Loading <strong>$this->objtype($req_stub)</strong> into cache" );
		$sql = "SELECT * 
						FROM $this->dbtablename 
						WHERE blog_stub = ".$DB->quote($req_stub);
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
}
?>