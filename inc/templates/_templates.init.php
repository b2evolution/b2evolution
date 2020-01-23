<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2020 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package templates
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Minimum PHP version required for template module to function properly
 */
$required_php_version['templates'] = '5.6';

/**
 * Minimum MYSQL version required for template module to function properly
 */
$required_mysql_version['templates'] = '5.1';

/**
 * Aliases for table names:
 *
 * (You should not need to change them.
 *  If you want to have multiple b2evo installations in a single database you should
 *  change {@link $tableprefix} in _basic_config.php)
 */
$db_config['aliases'] = array_merge( $db_config['aliases'], array(
		'T_templates'  => $tableprefix.'templates',
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
$ctrl_mappings['templates'] = 'templates/templates.ctrl.php';


/**
 * Get the SiteMenuCache
 *
 * @return SiteMenuCache
 */
function & get_TemplateCache()
{
	global $TemplateCache;

	if( ! isset( $TemplateCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'templates/model/_template.class.php', 'Template' );
		$TemplateCache = new DataObjectCache( 'Template', false, 'T_templates', 'tpl_', 'tpl_ID', 'tpl_name' );
	}

	return $TemplateCache;
}


/**
 * menus_Module definition
 */
class templates_Module extends Module
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
		$this->check_required_php_version( 'templates' );
		load_funcs( 'templates/model/_template.funcs.php' );
	}


	/**
	 * Builds the 2nd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_2()
	{
        global $admin_url, $current_User, $AdminUI;

		if( ! $current_User->check_perm( 'admin', 'restricted' ) )
		{	// User must has an access to back-office:
			return;
		}

		if( $current_User->check_perm( 'options', 'view' ) )
		{	// User has an access to view system settings:
			$AdminUI->add_menu_entries( array( 'collections', 'settings' ), array(
				'templates' => array(
					'text' => T_('Templates'),
					'href' => $admin_url.'?ctrl=templates' ),
				), 'types' );
		}
	}
}

$templates_Module = new templates_Module();

?>