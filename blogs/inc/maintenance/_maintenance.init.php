<?php

if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Controller mappings.
 *
 * For each controller name, we associate a controller file to be found in /inc/ .
 * The advantage of this indirection is that it is easy to reorganize the controllers into
 * subdirectories by modules. It is also easy to deactivate some controllers if you don't
 * want to provide this functionality on a given installation.
 *
 * Note: while the controller mappings might more or less follow the menu structure, we do not merge
 * the two tables since we could, at any time, decide to make a skin with a different menu structure.
 * The controllers however would most likely remain the same.
 *
 * @global array
 */
$ctrl_mappings['backup'] = 'maintenance/backup.ctrl.php';
$ctrl_mappings['upgrade'] = 'maintenance/upgrade.ctrl.php';


/**
 * maintenance_Module definition
 */
class maintenance_Module extends Module
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
		load_funcs( 'maintenance/model/_maintenance.funcs.php' );
	}


	/**
	 * Get default module permissions
	 *
	 * @param integer Group ID
	 * @return array
	 */
	function get_default_group_permissions( $grp_ID )
	{
		switch( $grp_ID )
		{
			case 1: // Administrators group ID equals 1
				$permname = 'upgrade';
				break;
			case 2: // Privileged Bloggers group equals 2
				$permname = 'none';
				break;
			case 3: // Bloggers group ID equals 3
				$permname = 'none';
				break;
			default: // Other groups
				$permname = 'none';
				break;
		}

		// We can return as many default permissions as we want:
		// e.g. array ( permission_name => permission_value, ... , ... )
		return $permissions = array( 'perm_maintenance' => $permname );
	}


	/**
	 * Get available group permissions
	 *
	 * @return array
	 */
	function get_available_group_permissions()
	{
		// Create permission options which will be used also to create radio buttons of the group form
		// e.g. array ( radio_button_value, radio_button_label, radio_button_note )
		$none_option 	= array( 'none', T_( 'No maintenance permission' ), '' );
		$backup_option 	= array( 'backup', T_( 'Users can create backups' ), '' );
		$upgrade_option 	= array( 'upgrade', T_( 'Users can create backups & upgrade the app' ), '' );

		// Create permission and set permission options to it.
		// 'label' is used in the group form as label for radio buttons group
		// 'user_func' is used to check user permission. This function should be defined in module initializer.
		// 'group_func' is used to check group permission. This function should be defined in module initializer.
		// 'available' is permission options
		$permissions = array( 'perm_maintenance' => array(	'label'      => T_('Maintenance'),
															'user_func'  => 'check_maintenance_user_perm',
															'group_func' => 'check_maintenance_group_perm',
															'available'  => array(	$none_option,
																					$backup_option,
																					$upgrade_option  ) ) );
		// We can return as many permissions as we want.
		// In other words, one module can return many pluggable permissions.
		return $permissions;
	}


	/**
	 * Check a permission for the user. ( see 'user_func' in get_available_group_permissions() function  )
	 *
	 * @param string Requested permission level
	 * @param string Permission value
	 * @param mixed Permission target (blog ID, array of cat IDs...)
	 * @return boolean True on success (permission is granted), false if permission is not granted
	 */
	function check_maintenance_user_perm( $permlevel, $permvalue, $permtarget )
	{
		return true;
	}


	/**
	 * Check a permission for the group. ( see 'group_func' in get_available_group_permissions() function )
	 *
	 * @param string Requested permission level
	 * @param string Permission value
	 * @param mixed Permission target (blog ID, array of cat IDs...)
	 * @return boolean True on success (permission is granted), false if permission is not granted
	 */
	function check_maintenance_group_perm( $permlevel, $permvalue, $permtarget )
	{
		$perm = false;
		switch ( $permvalue )
		{
			case 'upgrade':
				// Users can create backups & upgrade the app.
				if( $permlevel == 'upgrade' )
				{ // User can ask for delete perm...
					$perm = true;
					break;
				}

			case 'backup':
				//  Users can create backups
				if( $permlevel == 'backup' )
				{
					$perm = true;
					break;
				}
		}

		return $perm;
	}


	/**
	 * Builds the 3rd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_3()
	{
		global $AdminUI, $current_User;

		if( $current_User->check_perm( 'perm_maintenance', 'backup' ) )
		{
			// Display Backup tab in Tools menu
			$AdminUI->add_menu_entries( 'tools', array(
									'backup' => array(
									'text' => T_('Backup'),
									'href' => '?ctrl=backup'	),
							) );
		}

		if( $current_User->check_perm( 'perm_maintenance', 'upgrade' ) )
		{
			// Display Updates tab in Tools menu
			$AdminUI->add_menu_entries( 'tools', array(
									'upgrade' => array(
									'text' => T_('Check for updates'),
									'href' => '?ctrl=upgrade'	),
							) );
		}
	}
}

$maintenance_Module = & new maintenance_Module();


/*
 * $Log$
 * Revision 1.1  2009/10/18 20:15:51  efy-maxim
 * 1. backup, upgrade have been moved to maintenance module
 * 2. maintenance module permissions
 *
 */
?>