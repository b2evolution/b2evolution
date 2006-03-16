<?php
/**
 * This file implements the BlogCache class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
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
	 * Constructor
	 */
	function BlogCache()
	{
		parent::DataObjectCache( 'Blog', false, 'T_blogs', 'blog_', 'blog_ID' );
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

		// Load just the requested object:
		$Debuglog->add( "Loading <strong>$this->objtype($req_url)</strong> into cache", 'dataobjects' );

		$sql = "SELECT *
		          FROM $this->dbtablename
		         WHERE
		         ( blog_siteurl != '' AND ".$DB->quote($req_url)." LIKE CONCAT( blog_siteurl, '%' ) ";

		$parsedUrl = parse_url( $req_url );

		// Match stubs like "http://base/url/STUB?param=1"
		if( preg_match( "#^$baseurl([^/?]+)#", $req_url, $match ) )
		{
			$sql .= "\n OR ( blog_access_type = 'stub' AND blog_stub = '".$match[1]."' )";
		}

		$sql .= ' ) ';

		$row = $DB->get_row( $sql, OBJECT, 0, 'Blog::get_by_url()' );

		if( empty( $row ) )
		{ // Requested object does not exist
			if( $halt_on_error ) die( "Requested $this->objtype does not exist!" );

			$r = false;
			return $r; // we return by reference!
		}

		$Blog = new Blog( $row );
		$this->add( $Blog );

		return $Blog;
	}


	/**
	 * Get an object from cache by its URL name
	 *
	 * Load the cache if necessary
	 *
	 * @todo use cache
	 *
	 * @param string URL name of object to load
	 * @param boolean false if you want to return false on error
	 * @return Blog|false A Blog object on success, false on failure (may also halt!)
	 */
	function & get_by_urlname( $req_urlname, $halt_on_error = true )
	{
		global $DB, $Debuglog;

		// Load just the requested object:
		$Debuglog->add( "Loading <strong>$this->objtype($req_urlname)</strong> into cache", 'dataobjects' );
		$sql = "SELECT *
						FROM $this->dbtablename
						WHERE blog_urlname = ".$DB->quote($req_urlname);
		$row = $DB->get_row( $sql );
		if( empty( $row ) )
		{ // Requested object does not exist
			if( $halt_on_error ) die( "Requested $this->objtype does not exist!" );
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
		global $DB, $Debuglog, $UserCache;

		$Debuglog->add( "Loading <strong>$this->objtype(criterion: $criterion)</strong> into cache", 'dataobjects' );

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
	 * Display form option list with cache contents
	 *
	 * Loads the whole cache!!!
	 *
	 * @todo is it good to load all entries here? check usage! (Default blog/linkblog selecrtion, MT plugin...)
	 * @param integer selected ID
	 * @param boolean provide a choice for "none" with ID 0
	 */
	function option_list( $default = 0, $allow_none = false )
	{
		// We force a full load!
		$this->load_all();

		parent::option_list( $default, $allow_none, 'name' );
	}


	/**
	 * Returns form option list with cache contents
	 *
	 * Load the cache if necessary
	 *
	 * @param integer selected ID
	 * @param boolean provide a choice for "none" with ID 0
	 */
	function option_list_return( $default = 0, $allow_none = false, $method = 'name_return' )
	{
		// We force a full load!
		$this->load_all();

		return parent::option_list_return( $default, $allow_none, $method );
	}
}

/*
 * $Log$
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
 *
 * Revision 1.18  2005/12/12 19:21:21  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.17  2005/11/26 07:35:20  blueyed
 * load_user_blogs(): return unique list! This fixes the blog being two times in the root list if the user has permission through his group and user.
 *
 * Revision 1.16  2005/11/24 08:43:11  blueyed
 * doc
 *
 * Revision 1.15  2005/11/22 23:46:10  blueyed
 * load_user_blogs(): we have to consider T_coll_group_perms also!
 *
 * Revision 1.14  2005/09/06 17:13:54  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.13  2005/07/13 23:44:18  blueyed
 * Fixed notice with not returning reference (since PHP 4.4.0?).
 *
 * Revision 1.12  2005/05/25 17:13:33  fplanque
 * implemented email notifications on new comments/trackbacks
 *
 * Revision 1.11  2005/05/16 15:17:12  fplanque
 * minor
 *
 * Revision 1.10  2005/05/12 18:39:24  fplanque
 * storing multi homed/relative pathnames for file meta data
 *
 * Revision 1.9  2005/05/11 13:21:38  fplanque
 * allow disabling of mediua dir for specific blogs
 *
 * Revision 1.8  2005/03/16 19:58:23  fplanque
 * small AdminUI cleanup tasks
 *
 * Revision 1.7  2005/03/02 17:07:33  blueyed
 * no message
 *
 * Revision 1.6  2005/02/28 09:06:32  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.5  2005/01/04 23:45:47  fplanque
 * bugfix
 *
 * Revision 1.4  2004/12/21 21:22:46  fplanque
 * factoring/cleanup
 *
 * Revision 1.3  2004/12/15 20:50:34  fplanque
 * heavy refactoring
 * suppressed $use_cache and $sleep_after_edit
 * code cleanup
 *
 * Revision 1.2  2004/10/14 18:31:24  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.12  2004/10/11 19:02:04  fplanque
 * Edited code documentation.
 *
 */
?>
