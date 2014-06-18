<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id: _module.class.php 6135 2014-03-08 07:54:05Z manuel $
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
	 * Upgrade the module's tables in b2evo database
	 */
	function upgrade_b2evo_tables()
	{
	}

	/**
	 * Create the module's own demo content
	 */
	function create_demo_contents()
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
	 * Update Item after insert
	 *
	 * @param array
	 * 		array['edited_Item'] - which item setting should be updated;
	 */
	function update_item_after_insert( $params )
	{
	}

	/**
	 * Get "where" clause for item statuses
	 *
	 * @param array
	 * 		array['statuses'] - which items statuses are used to show
	 */
	function get_item_statuses_where_clause( $params )
	{
	}

	/**
	 * Call method at the end of constructor of class Item
	 *
	 * @param array
	 * 		array['Item'] - which item setting should be updated;
	 */
	function constructor_item( $params )
	{
	}


	/**
	 * Update thread after creating new object "Thread"
	 *
	 * @param array
	 * 		array['Thread'] - which thread setting should be updated;
	 */
	function update_new_thread( $params )
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
	 * $param object user's Group - can't be NULL
	 * @return boolean True on success (permission is granted), false if permission is not granted
	 *                 NULL if permission not implemented.
	 */
	function check_perm( $permname, $permlevel, $permtarget, $function, $Group )
	{
		if( empty( $Group ) )
		{ // group must be set
			return NULL;
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


	/**
	 * Get contacts list params
	 */
	function get_contacts_list_params()
	{
	}


	/**
	 * Switch actions for contacts
	 *
	 * @param array
	 */
	function switch_contacts_actions( $params = array() )
	{
	}


	/**
	 * Check Minimum PHP version required for the module
	 * 
	 * @param string module name/id
	 */
	function check_required_php_version( $module )
	{
		global $app_name, $app_version, $required_php_version;

		$php_version = phpversion();
		if( version_compare( $php_version, $required_php_version[ $module ], '<' ) )
		{
			$error_message = sprintf( 'You cannot use %1s module of %2$s %3$s on this server because it requires PHP version %4$s or higher. You are running version %5$s.',
								$module, $app_name, $app_version, $required_php_version[ $module ], $php_version );

			die('<h1>Insufficient Requirements</h1><p>'.$error_message.'</p>');
		}
	}
}

?>