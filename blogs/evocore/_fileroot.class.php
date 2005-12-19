<?php
/**
 * This file implements the FileRoot class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * This class provides info about a File Root.
 *
 * A FileRoot describes a directory available for media file storage, under access permission.
 *
 * @package evocore
 */
class FileRoot
{
	/**
	 * Type: 'user', 'group' or 'collection'.
	 *
	 * Note: group is not implemented yet. Absolute will probably be deprecated.
	 */
	var $type;

	/**
	 * ID of user, group or collection.
	 */
	var $in_type_ID;

	/**
	 * Unique Root ID constructed from type and in_type_ID
	 * @var string
	 */
	var $ID;

	/**
	 * Name of the root
	 */
	var $name;

	/**
	 * Absolute path, ending with slash
	 */
	var $ads_path;

	/**
	 * Absolute URL, ending with slash
	 */
	var $ads_url;


	/**
	 * Constructor
	 *
	 * @param string Root type: 'user', 'group' or 'collection'
	 * @param integer ID of the user, the group or the collection the file belongs to...
	 */
	function FileRoot( $root_type, $root_in_type_ID )
	{
		global $UserCache, $BlogCache;

		// Store type:
		$this->type = $root_type;
		// Store ID in type:
		$this->in_type_ID = $root_in_type_ID;
		// Generate unique ID:
		$this->ID = FileRoot::gen_ID( $root_type, $root_in_type_ID );

		switch( $root_type )
		{
			case 'user':
				$User = & $UserCache->get_by_ID( $root_in_type_ID );
				$this->name = $User->get( 'preferredname' );
				$this->ads_path = $User->getMediaDir();
				$this->ads_url = $User->getMediaUrl();
				return;

			case 'collection':
				$Blog = & $BlogCache->get_by_ID( $root_in_type_ID );
				$this->name = $Blog->get( 'shortname' );
				$this->ads_path = $Blog->get( 'mediadir' );
				$this->ads_url = $Blog->get( 'mediaurl' );
				return;
		}

		debug_die( "Root_type=$root_type not supported" );
	}


	/**
	 * @static
	 */
	function gen_ID( $root_type, $root_in_type_ID )
	{
		switch( $root_type )
		{
			case 'user':
			case 'collection':
				return $root_type.'_'.$root_in_type_ID;
		}

		debug_die( "Root_type=$root_type not supported" );
	}
}


/*
 * $Log$
 * Revision 1.7  2005/12/19 16:42:03  fplanque
 * minor
 *
 * Revision 1.6  2005/12/12 19:21:22  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.5  2005/11/26 16:30:22  blueyed
 * Fix assigning $this->in_type_ID!
 *
 * Revision 1.4  2005/11/24 20:38:43  blueyed
 * doc
 *
 * Revision 1.3.2.1  2005/11/16 23:01:00  blueyed
 * use debug_die()
 *
 * Revision 1.3  2005/09/29 15:07:30  fplanque
 * spelling
 *
 * Revision 1.2  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.1  2005/07/29 17:56:18  fplanque
 * Added functionality to locate files when they're attached to a post.
 * permission checking remains to be done.
 *
 */
?>