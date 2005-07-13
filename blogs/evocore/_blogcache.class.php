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
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: François PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_dataobjectcache.class.php';

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
		parent::DataObjectCache( 'Blog', false, 'T_blogs', 'blog_', 'blog_ID' );
	}



	/**
	 * Get an object from cache by its url ("siteurl") or based on access_type == 'stub'.
	 *
	 * Load the cache if necessary
	 *
	 * @todo use cache
	 * @todo check/enhance for other domains than $baseurl
	 *
	 * @param string URL of object to load
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
							( blog_siteurl = ".$DB->quote($req_url);

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
			return false;
		}

		$Blog = new Blog( $row );
		$this->add( $Blog );

		return $Blog;
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

		$Debuglog->add( "Loading <strong>$this->objtype(criterion: $criterion)</strong> into cache", 'dataobjects' );

		switch( $criterion )
		{
			case 'member':
				$where = 'WHERE bloguser_user_ID = '.$user_ID;
				break;

			case 'browse':
				$where = 'WHERE bloguser_user_ID = '.$user_ID
									.' AND bloguser_perm_media_browse = 1';
				break;
		}
		$bloglist = $DB->get_col( 'SELECT bloguser_blog_ID
																FROM T_coll_user_perms
																'.$where, 0, 'Get user blog list' );

		$this->load_list( implode( ',', $bloglist ) );

		return $bloglist;
	}


	/**
	 * Display form option list with cache contents
	 *
	 * Loads the whole cache!!!
	 *
	 * {@internal BlogCache::option_list(-) }}
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
	 * {@internal DataObjectCache::option_list_return(-) }}
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