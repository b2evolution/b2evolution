<?php
/**
 * This file implements the UserCache class.
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
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
class UserCache extends DataObjectCache
{
	/**
	 * Cache for login -> ID
	 * @access private
	 * @var array
	 */
	var $cache_login = array();


	/**
	 * Constructor
	 *
	 * {@internal UserCache::UserCache(-) }}
	 */
	function UserCache()
	{
		parent::DataObjectCache( 'User', false, 'T_users', 'user_', 'ID' );
	}

	/* this is for debugging only:
	function & get_by_ID( $req_ID, $halt_on_error = true )
	{
		$obj = parent::get_by_ID( $req_ID, $halt_on_error );
			pre_dump($obj);
		return $obj;
	}
	*/


	/**
	 * Get a user object by login.
	 *
	 * Does not halt on error.
	 *
	 * @return false|object reference to the user object or NULL if not found
	 */
	function & get_by_login( $login )
	{
		if( !isset( $this->cache_login[$login] ) )
		{
			global $DB;
			$this->cache_login[$login] = $DB->get_var( 'SELECT ID FROM T_users
																									WHERE user_login = "'.$login.'"' );
		}

		if( is_null($this->cache_login[$login]) )
		{
			return false;
		}

		return $this->get_by_ID( $this->cache_login[$login], false );
	}

	/**
	 * Load members of a given blog
	 *
	 * {@internal DataObjectCache::load_list(-) }}
	 *
	 * @param integer blog ID to load members for
	 */
	function load_blogmembers( $blog_ID )
	{
		global $DB, $Debuglog;

		$Debuglog->add( "Loading <strong>$this->objtype(Blog #$blog_ID members)</strong> into cache" );

		$rows = $DB->get_results( 'SELECT *
																 FROM T_users INNER JOIN T_blogusers ON ID = bloguser_user_ID
																WHERE bloguser_blog_ID = '.$blog_ID.'
																	AND bloguser_ismember <> 0' );
		if( count($rows) ) foreach( $rows as $row )
		{
			$this->cache[ $row->ID ] = new User( $row ); // COPY!
			// $obj = $this->cache[ $row->$dbIDname ];
			// $obj->disp( 'name' );
		}
	}


	/**
	 * Loads cache with blog memeber, then display form option list with cache contents
	 *
	 * Optionally, also adds default choice to the cache.
	 *
	 * {@internal UserCache::blog_member_list(-) }}
	 *
	 * @param integer blog ID
	 * @param integer selected ID
	 * @param boolean provide a choice for "none" with ID 0
	 */
	function blog_member_list( $blog_ID, $default = 0, $allow_none = false, $always_load_default = false )
	{
		if( $blog_ID )
		{	// Load requested blog members:
			$this->load_blogmembers( $blog_ID );

			// Make sure current user is in list:
			if( $default && $always_load_default )
			{
				// echo '<option>getting default';
				$this->get_by_ID( $default );
			}
		}
		else
		{	// No blog specified: load ALL members:
			$this->load_all();
		}

		parent::option_list( $default, $allow_none, 'prefered_name' );
	}


	/**
	 * Display form option list with cache contents
	 *
	 * Load the cache if necessary
	 *
	 * {@internal UserCache::option_list(-) }}
	 *
	 * @param integer selected ID
	 * @param boolean provide a choice for "none" with ID 0
	 */
	function option_list( $default = 0, $allow_none = false )
	{
		parent::option_list( $default, $allow_none, 'prefered_name' );
	}

}

/*
 * $Log$
 * Revision 1.3  2004/12/29 03:15:38  blueyed
 * added get_by_login()
 *
 * Revision 1.2  2004/12/23 21:19:41  fplanque
 * no message
 *
 * Revision 1.1  2004/12/21 21:18:38  fplanque
 * Finished handling of assigning posts/items to users
 *
 */
?>