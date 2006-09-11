<?php
/**
 * This file implements the BlogCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../dataobjects/_dataobjectcache.class.php';

/**
 * Blog Cache Class
 *
 * @package evocore
 */
class BlogCache extends DataObjectCache
{
	/**
	 * @var array Cache by absolute siteurl
	 */
	var $cache_siteurl_abs = array();

	/**
	 * @var array Cache by urlname
	 */
	var $cache_urlname = array();

	/**
	 * Constructor
	 */
	function BlogCache()
	{
		parent::DataObjectCache( 'Blog', false, 'T_blogs', 'blog_', 'blog_ID' );
	}


	/**
	 * Add object to cache, handling our own indices.
	 *
	 * @param Blog
	 * @return boolean True on add, false if already existing.
	 */
	function add( & $Blog )
	{
		if( ! empty($Blog->siteurl) && preg_match( '~^https?://~', $Blog->siteurl ) )
		{ // absolute siteurl
			$this->cache_siteurl_abs[ $Blog->siteurl ] = & $Blog;
		}

		$this->cache_urlname[ $Blog->urlname ] = & $Blog;

		return parent::add( $Blog );
	}


	/**
	 * Get an object from cache by its url ("siteurl") or based on access_type == 'stub'.
	 *
	 * Load the cache if necessary
	 *
	 * This gets used in /index.php to detect blogs according to the requested HostWithPath
	 *
	 * @param string URL of object to load (this should the whole requested URL/path, e.g. "http://mr.example.com/permalink")
	 * @param boolean false if you want to return false on error
	 * @return Blog|false A Blog object on success, false on failure (may also halt!)
	 */
	function & get_by_url( $req_url, $halt_on_error = true )
	{
		global $DB, $Debuglog, $baseurl;

		foreach( array_keys($this->cache_siteurl_abs) as $siteurl_abs )
		{
			if( strpos( $req_url, $siteurl ) === 0 )
			{ // found in cache
				return $this->cache_siteurl_abs[$siteurl_abs];
			}
		}

		// Load just the requested object:
		$Debuglog->add( "Loading <strong>$this->objtype($req_url)</strong> into cache", 'dataobjects' );

		$req_url_wo_proto = substr( $req_url, strpos( $req_url, '://' ) ); // req_url without protocol, so it matches http and https below

		// fp> TODO: protocol detection feels bloated. Why not used a MYSQL REGEXP ??
		// TODO: we should have an extra DB column that either defines type of blog_siteurl OR split blog_siteurl into blog_siteurl_abs and blog_siteurl_rel (where blog_siteurl_rel could be "blog_sitepath")
		$sql = "
				SELECT *
				  FROM $this->dbtablename
				 WHERE (
				  ( blog_siteurl REGEXP '^https?://'
				    AND ( ".$DB->quote('http'.$req_url_wo_proto)." LIKE CONCAT( blog_siteurl, '%' )
				          OR ".$DB->quote('https'.$req_url_wo_proto)." LIKE CONCAT( blog_siteurl, '%' ) ) ) ";

		// Match stubs like "http://base/url/STUB?param=1" on $baseurl
		if( preg_match( "#^$baseurl([^/?]+)#", $req_url, $match ) )
		{
			$sql .= "\n OR ( blog_access_type = 'stub' AND blog_stub = '".$match[1]."' )";
		}

		$sql .= ' ) ';

		$row = $DB->get_row( $sql, OBJECT, 0, 'Blog::get_by_url()' );

		if( empty( $row ) )
		{ // Requested object does not exist
			if( $halt_on_error ) debug_die( "Requested $this->objtype does not exist!" );

			$r = false;
			return $r; // we return by reference!
		}

		$Blog = new Blog( $row );
		$this->add( $Blog );

		return $Blog;
	}


	/**
	 * Get an object from cache by its URL name.
	 *
	 * Load the object into cache, if necessary.
	 *
	 * @param string URL name of object to load
	 * @param boolean false if you want to return false on error
	 * @return Blog|false A Blog object on success, false on failure (may also halt!)
	 */
	function & get_by_urlname( $req_urlname, $halt_on_error = true )
	{
		global $DB, $Debuglog;

		if( isset($this->cache_urlname[$req_urlname]) )
		{
			return $this->cache_urlname[$req_urlname];
		}

		// Load just the requested object:
		$Debuglog->add( "Loading <strong>$this->objtype($req_urlname)</strong> into cache", 'dataobjects' );
		$sql = "
				SELECT *
				  FROM $this->dbtablename
				 WHERE blog_urlname = ".$DB->quote($req_urlname);
		$row = $DB->get_row( $sql );

		if( empty( $row ) )
		{ // Requested object does not exist
			if( $halt_on_error ) debug_die( "Requested $this->objtype does not exist!" );
			$r = false;
			return $r;
		}

		$Blog = new Blog( $row );
		$this->add( $Blog );

		return $Blog;
	}


	/**
	 * Load blogs of a user.
	 *
	 * @todo make a UNION query when we upgrade to MySQL 4
	 * @todo Use cache!
	 *
	 * @param string criterion: 'member' (default), 'browse'
	 * @param integer user ID
	 * @return array The blog IDs
	 */
	function load_user_blogs( $criterion = 'member', $user_ID )
	{
		global $DB, $Debuglog;

		$Debuglog->add( "Loading <strong>$this->objtype(criterion: $criterion)</strong> into cache", 'dataobjects' );

		$UserCache = & get_Cache( 'UserCache' );
		$for_User = & $UserCache->get_by_ID( $user_ID );

		if( !$for_User )
		{
			debug_die( 'load_user_blogs(): User with ID '.$user_ID.' not found!' );
		}

		$where_user = 'WHERE bloguser_user_ID = '.$user_ID;
		$where_group = 'WHERE bloggroup_group_ID = '.$for_User->Group->get('ID');

		if( $criterion == 'browse' )
		{
			$where_user .= ' AND bloguser_perm_media_browse = 1';
			$where_group .= ' AND bloggroup_perm_media_browse = 1';
		}

		$bloglist_user = $DB->get_col(
			'SELECT bloguser_blog_ID
			   FROM T_coll_user_perms
			'.$where_user, 0, 'Get user blog list (T_coll_user_perms)' );

		$bloglist_group = $DB->get_col(
			'SELECT bloggroup_blog_ID
			   FROM T_coll_group_perms
			'.$where_group, 0, 'Get user blog list (T_coll_group_perms)' );

		$bloglist = array_unique( array_merge( $bloglist_user, $bloglist_group ) );

		$this->load_list( implode( ',', $bloglist ) );

		return $bloglist;
	}


	/**
	 * Returns form option list with cache contents
	 *
	 * Loads the whole cache!
	 *
	 * @param integer selected ID
	 * @param boolean provide a choice for "none" with ID 0
	 */
	function get_option_list( $default = 0, $allow_none = false, $method = 'get_name' )
	{
		// We force a full load!
		$this->load_all();

		return parent::get_option_list( $default, $allow_none, $method );
	}
}

/*
 * $Log$
 * Revision 1.12  2006/09/11 22:06:08  blueyed
 * Cleaned up option_list callback handling
 *
 * Revision 1.11  2006/08/21 16:07:43  fplanque
 * refactoring
 *
 * Revision 1.10  2006/08/19 07:56:30  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.9  2006/07/06 19:26:30  fplanque
 * question?
 *
 * Revision 1.8  2006/06/05 15:26:12  blueyed
 * get_by_url: detect regardless of protocol (http or https)
 *
 * Revision 1.7  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.6  2006/03/18 14:35:47  blueyed
 * todo
 *
 * Revision 1.5  2006/03/17 21:28:40  fplanque
 * no message
 *
 * Revision 1.4  2006/03/17 21:13:13  blueyed
 * Improved caching
 *
 * Revision 1.3  2006/03/16 23:25:50  blueyed
 * Fixed BlogCache::get_by_url(), so "siteurl" type blogs can finally get used.
 *
 * Revision 1.2  2006/03/12 23:08:58  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:57  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.19  2006/01/16 21:22:56  blueyed
 * Fix return by reference.
 */
?>