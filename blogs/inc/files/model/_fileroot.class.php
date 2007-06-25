<?php
/**
 * This file implements the FileRoot class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
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
	 * Will fail if non existent User or Blog is requested.
	 * But specific access permissions on (threfore existence of) this User or Blog should have been tested before anyway.
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
				$this->name = $User->get( 'preferredname' ).' ('. /* TRANS: short for "user" */ T_('u').')';
				$this->ads_path = $User->get_media_dir( $create );
				$this->ads_url = $User->get_media_url();
				return;

			case 'collection':
				$BlogCache = & get_Cache( 'BlogCache' );
				/**
				 * @var Blog
				 */
				$Blog = & $BlogCache->get_by_ID( $root_in_type_ID );
				$this->name = $Blog->get( 'shortname' ).' ('. /* TRANS: short for "blog" */ T_('b').')';
				$this->ads_path = $Blog->get_media_dir( $create );
				$this->ads_url = $Blog->get_media_url();
				return;

    	case 'skins':
    		// fp> some stuff here should go out of here... but I don't know where to put it yet. I'll see after the Skin refactoring.
    		global $Settings, $Debuglog;
    		/**
    		 * @var User
    		 */
    		global $current_User;
     		if( ! $Settings->get( 'fm_enable_roots_skins' ) )
				{ // Skins root is disabled:
					$Debuglog->add( 'Attempt to access skins dir, but this feature is globally disabled', 'files' );
				}
				elseif( ! $current_User->check_perm( 'templates' ) )
				{	// No perm to access templates:
					$Debuglog->add( 'Attempt to access skins dir, but no permission', 'files' );
				}
				else
				{
					global $skins_path, $skins_url;
					$this->name = T_('Skins');
					$this->ads_path = $skins_path;
					$this->ads_url = $skins_url;
				}
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
			case 'skins':
				return $root_type.'_'.$root_in_type_ID;
		}

		debug_die( "Root_type=$root_type not supported" );
	}
}


/*
 * $Log$
 * Revision 1.1  2007/06/25 10:59:56  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.14  2007/04/26 00:11:10  fplanque
 * (c) 2007
 *
 * Revision 1.13  2007/02/11 15:16:49  fplanque
 * doc
 *
 * Revision 1.11  2006/12/10 03:04:16  blueyed
 * TRANS note for "u" and "b"
 *
 * Revision 1.10  2006/12/08 01:53:18  fplanque
 * Added missing skin access switch
 *
 * Revision 1.9  2006/12/07 15:23:42  fplanque
 * filemanager enhanced, refactored, extended to skins directory
 *
 * Revision 1.8  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>