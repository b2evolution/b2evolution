<?php
/**
 * This is the init file for the files module
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Aliases for table names:
 *
 * (You should not need to change them.
 *  If you want to have multiple b2evo installations in a single database you should
 *  change {@link $tableprefix} in _basic_config.php)
 */
$db_config['aliases'] = array_merge( $db_config['aliases'], array(
		'T_files'               => $tableprefix.'files',
		'T_filetypes'           => $tableprefix.'filetypes',
	) );

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
$ctrl_mappings = array_merge( $ctrl_mappings, array(
		'files'        => 'files/files.ctrl.php',
		'fileset'      => 'files/file_settings.ctrl.php',
		'filetypes'    => 'files/file_types.ctrl.php',
	) );



/**
 * Get the FileCache
 *
 * @return FileCache
 */
function & get_FileCache()
{
	global $FileCache;

	if( ! isset( $FileCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'files/model/_filecache.class.php', 'FileCache' );
		$FileCache = new FileCache(); // COPY (FUNC)
	}

	return $FileCache;
}

/**
 * Get the FileRootCache
 *
 * @return FileRootCache
 */
function & get_FileRootCache()
{
	global $Plugins, $FileRootCache;

	if( ! isset( $FileRootCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'files/model/_filerootcache.class.php', 'FileRootCache' );
		$Plugins->get_object_from_cacheplugin_or_create( 'FileRootCache' );
	}

	return $FileRootCache;
}

/**
 * Get the FiletypeCache
 *
 * @return FiletypeCache
 */
function & get_FiletypeCache()
{
	global $Plugins;
	global $FiletypeCache;

	if( ! isset( $FiletypeCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'files/model/_filetypecache.class.php', 'FiletypeCache' );
		$Plugins->get_object_from_cacheplugin_or_create( 'FiletypeCache' );
	}

	return $FiletypeCache;
}

/**
 * adsense_Module definition
 */
class files_Module extends Module
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
		load_class( 'files/model/_file.class.php', 'File' );
		load_class( 'files/model/_filetype.class.php', 'FileType' );
		load_class( 'files/model/_filetypecache.class.php', 'FileTypeCache' );
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
				$permfiles = 'all';
				$permshared = 'edit';
				break;
			case 2: // Privileged Bloggers group equals 2
				$permfiles = 'add';
				$permshared = 'add';
				break;
			case 3: // Bloggers group ID equals 3
				$permfiles = 'view';
				$permshared = 'view';
				break;
			default: // Other groups
				$permfiles = 'none';
				$permshared = 'none';
				break;
		}

		// We can return as many default permissions as we want:
		// e.g. array ( permission_name => permission_value, ... , ... )
		return $permissions = array( 'perm_files' => $permfiles, 'perm_shared_root' => $permshared );
	}

	/**
	 * Get available group permissions
	 *
	 * @return array
	 */
	function get_available_group_permissions()
	{
		global $current_User;
		// fp> todo perm check
		$filetypes_linkstart = '<a href="?ctrl=filetypes" title="'.T_('Edit locked file types...').'">';
		$filetypes_linkend = '</a>';
		$filetypes_allowed = '';
		$filetypes_not_allowed = '';
		if( isset( $current_User ) && $current_User->check_perm( 'options', 'edit' ) ) {
			$filetypes_allowed = $filetypes_linkstart.get_icon('file_allowed').$filetypes_linkend;
			$filetypes_not_allowed = $filetypes_linkstart.get_icon('file_not_allowed').$filetypes_linkend;
		}
		// 'label' is used in the group form as label for radio buttons group
		// 'user_func' is used to check user permission. This function should be defined in module initializer.
		// 'group_func' is used to check group permission. This function should be defined in module initializer.
		// 'perm_block' group form block where this permissions will be displayed. Now available, the following blocks: additional, system
		// 'options' is permission options
		$permissions = array(
			'perm_files' => array(
				'label' => T_('Files'),
				'user_func'  => 'check_files_user_perm',
				'group_func' => 'check_files_group_perm',
				'perm_block' => 'additional',
				'options'  => array(
						// format: array( radio_button_value, radio_button_label, radio_button_note )
						array( 'none', T_('No Access') ),
						array( 'view', T_('View files for all allowed roots') ),
						array( 'add', T_('Add/Upload files to allowed roots') ),
						array( 'edit', sprintf( T_('Edit %sunlocked files'), $filetypes_linkstart.get_icon('file_allowed').$filetypes_linkend ) ),
						array( 'all', sprintf( T_('Edit all files, including %slocked ones'), $filetypes_linkstart.get_icon('file_not_allowed').$filetypes_linkend ), T_('Needed for editing PHP files in skins.') ),
					),
				'perm_type' => 'radiobox',
				'field_lines' => true,
				'field_note' => T_('This setting will further restrict any media file permissions on specific blogs.'),
				),
			'perm_shared_root' => array(
				'label' => T_('Access to shared root'),
				'user_func'  => 'check_sharedroot_user_perm',
				'group_func' => 'check_sharedroot_group_perm',
				'perm_block' => 'additional',
				'options'  => array(
						// format: array( radio_button_value, radio_button_label, radio_button_note )
						array( 'none', T_('No Access') ),
						array( 'view', T_('Read only') ),
						array( 'add', T_('Add/Upload') ),
						array( 'edit', T_('Edit') ),
					),
				'perm_type' => 'radiobox',
				'field_lines' => false,
				),
		);
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
	function check_files_user_perm( $permlevel, $permvalue, $permtarget )
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
	function check_files_group_perm( $permlevel, $permvalue, $permtarget )
	{
		$perm = false;
		switch ( $permvalue )
		{
			case 'all':
				global $demo_mode;
				if( ( ! $demo_mode ) && ( $permlevel == 'all' ) )
				{ // All permissions granted
					$perm = true;
					break;
				}

			case 'edit':
				// User can ask for normal edit perm...
				if( $permlevel == 'edit' )
				{
					$perm = true;
					break;
				}

			case 'add':
				// User can ask for add perm...
				if( $permlevel == 'add' )
				{
					$perm = true;
					break;
				}

			case 'view':
				// User can ask for view perm...
				if( $permlevel == 'view' )
				{
					$perm = true;
					break;
				}
		}

		if( $perm && isset($permtarget) && ( is_a( $permtarget, 'FileRoot' ) ) )
		{
			global $current_User;
			switch( $permtarget->type )
			{
				case 'shared':
					return $current_User->check_perm( 'shared_root', $permlevel );
				case 'user':
					return $permtarget->in_type_ID == $current_User->ID;
			}
		}

		return $perm;
	}

	function check_sharedroot_group_perm( $permlevel, $permvalue, $permtarget )
	{
		$perm = false;
		switch ( $permvalue )
		{
			case 'edit':
				if( $permlevel == 'edit' )
				{ // User can ask for normal edit perm...
					$perm = true;
					break;
				}

			case 'add':
				// User can ask for normal add perm...
				if( $permlevel == 'add' )
				{
					$perm = true;
					break;
				}

			case 'view':
				// User can ask for normal view perm...
				if( $permlevel == 'view' )
				{
					$perm = true;
					break;
				}
		}
		return $perm;
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
		global $blog, $dispatcher;
		/**
		 * @var User
		 */
		global $current_User;
		global $Blog;
		global $Settings;
		/**
		 * @var AdminUI_general
		 */
		global $AdminUI;

		if( !$current_User->check_perm( 'admin', 'restricted' ) )
		{
			return;
		}

		if( $current_User->check_perm( 'files', 'view', false, $blog ? $blog : NULL ) )
		{	// FM enabled and permission to view files:
			$AdminUI->add_menu_entries( NULL, array(
						'files' => array(
							'text' => T_('Files'),
							'title' => T_('File management'),
							'href' => $dispatcher.'?ctrl=files',
							// Controller may add subtabs
						),
					) );
		}

	}

	/**
	 * Builds the 3rd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_3()
	{
	}
}

$files_Module = new files_Module();


/*
 * $Log$
 * Revision 1.15  2011/09/04 22:13:15  fplanque
 * copyright 2011
 *
 * Revision 1.14  2011/04/05 12:41:39  efy-asimo
 * file perms check and file delete - fix
 *
 * Revision 1.13  2011/02/15 15:37:00  efy-asimo
 * Change access to admin permission
 *
 * Revision 1.12  2011/01/18 16:23:02  efy-asimo
 * add shared_root perm and refactor file perms - part1
 *
 * Revision 1.11  2010/10/19 02:00:54  fplanque
 * MFB
 *
 * Revision 1.9.2.3  2010/10/19 01:04:48  fplanque
 * doc
 *
 * Revision 1.3  2009/08/30 12:31:44  tblue246
 * Fixed CVS keywords
 *
 * Revision 1.1  2009/08/30 00:30:52  fplanque
 * increased modularity
 *
 */
?>
