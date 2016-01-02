<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
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
	 * Get what demo collections are installed by the module
	 *
	 * @return array Collections, example:
	 *                 array(
	 *                   'blog_key' => T_('Blog title'),
	 *                   'blog_other_key' => T_('Blog other title')
	 *                 )
	 */
	function get_demo_collections()
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
	 * @param object user's Group - can't be NULL
	 * @return boolean True on success (permission is granted), false if permission is not granted
	 *                 NULL if permission not implemented.
	 */
	static function check_perm( $permname, $permlevel, $permtarget, $function, $Group )
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
	 * Get module specific cron jobs
	 *
	 * NOTE: keys starting with "plugin_" are reserved for jobs provided by Plugins
	 *
	 * @return array(
	 *  job_key => array(
	 *  	'name' => name_value,    // string - the display name of the cron job
	 *  	'help' => help_config,   // string - The manual topic or a manual URL. Set '#' to use the default manual topic ( 'task-'+job_key ).
	 *  	'ctrl' => ctrl_path,     // string - the path of the cronjob ctrl file
	 *  	'params' => job_params ) // array/NULL - the cron job params if there is any or NULL otherwise
	 *  )
	 */
	function get_cron_jobs()
	{
		return array();
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