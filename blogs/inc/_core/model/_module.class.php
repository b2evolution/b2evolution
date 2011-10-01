<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Module class (only useful if derived)
 */
class Module
{
	/**
	 * Do the initializations. Called from in _main.inc.php.
	 * This is typically where classes matching DB tables for this module are registered/loaded.
	 *
	 * Note: this should only load/register things that are going to be needed application wide,
	 * for example: for constructing menus.
	 * Anything that is needed only in a specific controller should be loaded only there.
	 * Anything that is needed only in a specific view should be loaded only there.
	 */
	function init()
	{
	}

	/**
	 * Build teh evobar menu
	 */
	function build_evobar_menu()
	{
	}


	/**
	 * Builds the 1st half of the menu. This is the one with the most important features
	 */
	function build_menu_1()
	{
	}

  /**
	 * Builds the 2nd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_2()
	{
	}


	/**
	 * Builds the 3rd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_3()
	{
	}


	/**
	 * Provide translation in the context of this module:
	 *
	 * You can override this in specific modules.
	 * Note: no fancy i18n mechanisme is provided at this point. We may add one in the future.
	 * Especially if we have our own T_() extractor and multiple POT files.
	 *
	 * @param mixed $string
	 * @param mixed $req_locale
	 * @return string
	 */
	function T_( $string, $req_locale = '' )
	{
		return T_( $string, $req_locale );
	}


	/**
	 * could be used e.g. by a google_analytics plugin to add the javascript snippet
	 */
	function SkinEndHtmlBody()
	{
	}

	/**
	 * Displays the module's collection feature settings
	 *
	 * @param array
	 * 		array['Form'] - where to display;
	 * 		array['edited_Blog'] - which blog properties should be displayed;
	 */
	function display_collection_features( $params )
	{
	}

	/**
	 * Updates the module's collection feature settings
	 *
	 * @param array
	 * 		array['edited_Blog'] - which blog properties should be updated;
	 */
	function update_collection_features( $params )
	{
	}

	/**
	 * Displays the module's collection comments settings
	 *
	 * @param array
	 * 		array['Form'] - where to display;
	 * 		array['edited_Blog'] - which blog properties should be displayed;
	 */
	function display_collection_comments( $params )
	{
	}

	/**
	 * Updates the module's collection comments settings
	 *
	 * @param array
	 * 		array['edited_Blog'] - which blog properties should be updated;
	 */
	function update_collection_comments( $params )
	{
	}

	/**
	 * Displays the module's item settings
	 *
	 * @param array
	 * 		array['Form'] - where to display;
	 * 		array['Blog'] - which blog item is it;
	 * 		array['edited_Item'] - which item is it;
	 */
	function display_item_settings( $params )
	{
	}

	/**
	 * Updates the module's collection feature settings
	 *
	 * @param array
	 * 		array['edited_Item'] - which item setting should be updated;
	 */
	function update_item_settings( $params )
	{
	}


	/**
	 * Allows the module to do something before displaying the comments for a post.
	 *
	 * @param array
	 */
	function before_comments( $params )
	{
	}


	/**
	 * Check module permission
	 *
	 * @param string Permission name
	 * @param string Requested permission level
	 * @param mixed Permission target (blog ID, array of cat IDs...)
	 * @param string function name
	 * @return boolean True on success (permission is granted), false if permission is not granted
	 *                 NULL if permission not implemented.
	 */
	function check_perm( $permname, $permlevel, $permtarget, $function, $Group = NULL )
	{
		if( ! isset( $Group ) )
		{
			global $current_User;
			$Group = & $current_User->get_Group();
		}

		$GroupSettings = & $Group->get_GroupSettings();

		if( array_key_exists( $permname, $GroupSettings->permission_modules ) )
		{	// Requested permission found in the group settings
			$Module = & $GLOBALS[$GroupSettings->permission_modules[$permname].'_Module'];
			if( method_exists( $Module, 'get_available_group_permissions' ) )
			{	// Function to get available permission exists
				$permissions = $Module->get_available_group_permissions();
				if( array_key_exists( $permname, $permissions ) )
				{	// Requested permission found in available permisssion list
					$permission = $permissions[$permname];
					if( array_key_exists( $function, $permission ) )
					{	// Function to check permission exists
						$function = $permission[$function];
						if( method_exists( $Module, $function ) )
						{	// We can call check permission function
							return $Module->{$function}( $permlevel, $GroupSettings->get( $permname, $Group->ID ), $permtarget );
						}
					}
				}
			}
		}

		// Required parameters of check permission function not found
		return NULL;
	}
}

/*
 * $Log$
 * Revision 1.12  2011/10/01 23:45:19  fplanque
 * clean factorization
 *
 * Revision 1.11  2011/09/28 12:09:52  efy-yurybakh
 * "comment was helpful" votes (new tab "comments")
 *
 * Revision 1.10  2011/09/23 22:37:09  fplanque
 * minor / doc
 *
 * Revision 1.9  2011/09/23 01:29:04  fplanque
 * small changes
 *
 */
?>