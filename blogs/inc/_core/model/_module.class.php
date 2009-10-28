<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
	 * could be used e.g. by a google_analytics plugin to add the javascript snippet
	 */
	function SkinEndHtmlBody()
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
	function check_perm( $permname, $permlevel, $permtarget, $function )
	{
		global $current_User;

		$Group = & $current_User->get_Group();
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
 */
?>