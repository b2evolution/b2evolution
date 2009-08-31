<?php
/**
 * This file implements the FileRoot class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
		/**
		 * @var User
		 */
		global $current_User;
		global $Messages;
 		global $Settings, $Debuglog;

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
				$this->name = $User->get( 'preferredname' ); //.' ('. /* TRANS: short for "user" */ T_('u').')';
				$this->ads_path = $User->get_media_dir( $create );
				$this->ads_url = $User->get_media_url();
				return;

			case 'collection':
				$BlogCache = & get_Cache( 'BlogCache' );
				/**
				 * @var Blog
				 */
				$Blog = & $BlogCache->get_by_ID( $root_in_type_ID );
				$this->name = $Blog->get( 'shortname' ); //.' ('. /* TRANS: short for "blog" */ T_('b').')';
				$this->ads_path = $Blog->get_media_dir( $create );
				$this->ads_url = $Blog->get_media_url();
				return;

			case 'shared':
				// fp> TODO: handle multiple shared directories
				global $media_path, $media_url;
				$rds_shared_subdir = 'shared/global/';
				$ads_shared_dir = $media_path.$rds_shared_subdir;

				if( ! $Settings->get( 'fm_enable_roots_shared' ) )
				{ // Shared dir is disabled:
					$Debuglog->add( 'Attempt to access shared dir, but this feature is globally disabled', 'files' );
				}
				elseif( ! is_dir( $ads_shared_dir ) )
				{
					// Create shared directory if it doesn't exist yet:
					if( (!is_admin_page()) || (!mkdir_r( $ads_shared_dir )) )
					{
     				$Messages->add( sprintf( T_('The directory &laquo;%s&raquo; does not exist.'), $rds_shared_subdir ).get_manual_link('directory_creation_error'), 'error' );
					}
				}
				else
				{
					$this->name = T_('Shared');
					$this->ads_path = $ads_shared_dir;
					$this->ads_url = $media_url.'shared/global/';
				}
				return;

    	case 'skins':
    		// fp> some stuff here should go out of here... but I don't know where to put it yet. I'll see after the Skin refactoring.
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


	function get_typegroupname()
	{
		switch( $this->type )
		{
			case 'user':
				return NT_('User roots');

			case 'collection':
				return NT_('Blog roots');

			default:
				return NT_('Special roots');
		}
	}

	/**
	 * @static
	 */
	function gen_ID( $root_type, $root_in_type_ID )
	{
		switch( $root_type )
		{
			case 'user':
			case 'shared':
			case 'collection':
			case 'skins':
				return $root_type.'_'.$root_in_type_ID;
		}

		debug_die( "Root_type=$root_type not supported" );
	}

}


/*
 * $Log$
 * Revision 1.8  2009/08/31 16:56:10  fplanque
 * if-conditions did not seem right
 *
 * Revision 1.7  2009/08/22 15:27:38  tblue246
 * - FileRoot::FileRoot():
 * 	- Only try to create shared dir if enabled.
 * - Hit::extract_serprank_from_referer():
 * 	- Do not explode() $ref string, but use a (dynamically generated) RegExp instead. Tested and should work.
 *
 * Revision 1.6  2009/08/17 05:50:33  sam2kb
 * Create shared/global/  directories if not exist yet
 * See http://forums.b2evolution.net/viewtopic.php?t=19411
 *
 * Revision 1.5  2009/03/08 23:57:43  fplanque
 * 2009
 *
 * Revision 1.4  2008/09/23 06:18:37  fplanque
 * File manager now supports a shared directory (/media/shared/global/)
 *
 * Revision 1.3  2008/01/21 09:35:29  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/11/01 04:31:25  fplanque
 * Better root browsing (roots are groupes by type + only one root is shown at a time)
 *
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
