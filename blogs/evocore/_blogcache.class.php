<?php
/**
 * This file implements the BlogCache class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

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
	function & get_by_url( $req_url, $halt_on_error = true )
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
	function & get_by_urlname( $req_urlname, $halt_on_error = true )
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

/*
 * $Log$
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