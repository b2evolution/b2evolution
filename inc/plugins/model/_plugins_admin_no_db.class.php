<?php
/**
 * This file implements the {@link Plugins_admin_no_DB} class, which gets used for administrative
 * handling of the {@link Plugin Plugins}, but without database.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2006 by Daniel HAHLER - {@link http://daniel.hahler.de/}.
 *
 * @package plugins
 *
 * @author blueyed: Daniel HAHLER
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_class( 'plugins/model/_plugins_admin.class.php', 'Plugins_admin' );


/**
 * A sub-class of {@link Plugins_admin} which will not load any DB info (i-e: Plugins and Events).
 *
 * This is useful for displaying a list of available plugins which can be installed.
 * This is also useful during installation in order to have a global $Plugins object that does not interfere with the installation process.
 *
 * {@internal This is probably quicker and cleaner than using a member boolean in {@link Plugins_admin} itself.}}
 *
 * @package plugins
 */
class Plugins_admin_no_DB extends Plugins_admin
{
	/**
	 * No-operation.
	 */
	function load_plugins_table()
	{
	}

	/**
	 * No-operation.
	 */
	function load_events()
	{
	}
}

?>