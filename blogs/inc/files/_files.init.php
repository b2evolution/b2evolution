<?php
/**
 * This is the init file for the files module
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * adsense_Module definition
 */
class files_Module
{
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

		if( $Settings->get( 'fm_enabled' ) && $current_User->check_perm( 'files', 'view', false, $blog ? $blog : NULL ) )
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
		global $blog, $loc_transinfo, $ctrl, $dispatcher;
		/**
		 * @var User
		 */
		global $current_User;
		global $Blog;
		/**
		 * @var AdminUI_general
		 */
		global $AdminUI;

		if( $current_User->check_perm( 'options', 'view' ) )
		{	// Permission to view settings:
			$AdminUI->add_menu_entries( 'options', array(
				'files' => array(
					'text' => T_('Files'),
					'href' => $dispatcher.'?ctrl=fileset' ),
				'filetypes' => array(
					'text' => T_('File types'),
					'href' => $dispatcher.'?ctrl=filetypes' ),
			) );
		}

	}
}

$files_Module = & new files_Module();


/*
 * $Log$
 * Revision 1.3  2009/08/30 12:31:44  tblue246
 * Fixed CVS keywords
 *
 * Revision 1.1  2009/08/30 00:30:52  fplanque
 * increased modularity
 *
 */
?>
