<?php
/**
 * This file implements the FileRoot class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
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
	 * @param boolean Create the directory, if it does not exist yet?
	 */
	function FileRoot( $root_type, $root_in_type_ID, $create = true )
	{
		// Store type:
		$this->type = $root_type;
		// Store ID in type:
		$this->in_type_ID = $root_in_type_ID;
		// Generate unique ID:
		$this->ID = FileRoot::gen_ID( $root_type, $root_in_type_ID );

		switch( $root_type )
		{
			case 'user':
				$UserCache = & get_Cache( 'UserCache' );
				$User = & $UserCache->get_by_ID( $root_in_type_ID );
				$this->name = $User->get( 'preferredname' );
				$this->ads_path = $User->get_media_dir( $create );
				$this->ads_url = $User->get_media_url();
				return;

			case 'collection':
				$BlogCache = & get_Cache( 'BlogCache' );
				$Blog = & $BlogCache->get_by_ID( $root_in_type_ID );
				$this->name = $Blog->get( 'shortname' );
				$this->ads_path = $Blog->get_media_dir( $create );
				$this->ads_url = $Blog->get_media_url();
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
 * Revision 1.8  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.7  2006/09/30 16:55:58  blueyed
 * $create param for media dir handling, which allows to just get the dir, without creating it.
 *
 * Revision 1.6  2006/08/19 07:56:30  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.5  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.4  2006/04/13 00:29:32  blueyed
 * cleanup
 *
 * Revision 1.3  2006/03/16 19:26:04  fplanque
 * Fixed & simplified media dirs out of web root.
 *
 * Revision 1.2  2006/03/12 23:08:58  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:57  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
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