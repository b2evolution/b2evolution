<?php
/**
 * -----------------------------------------------------------------------------------------
 * This file provides a skeleton to create a new {@link http://b2evolution.net/ b2evolution}
 * plugin quickly.
 * See also:
 *  - {@link http://manual.b2evolution.net/CreatingPlugin}
 *  - {@link http://doc.b2evolution.net/stable/plugins/Plugin.html}
 * (Delete this first paragraph, of course)
 * -----------------------------------------------------------------------------------------
 *
 * This file implements the Foo Plugin for {@link http://b2evolution.net/}.
 *
 * @copyright (c)2010 by Your NAME - {@link http://example.com/}.
 *
 * @license GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *
 * @package plugins
 *
 * @author Your NAME
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Foo Plugin
 *
 * Your description
 *
 * @package plugins
 */
class pluginname_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */
	/**
	 * Human readable plugin name.
	 */
	var $name = 'Plugin Name';
	/**
	 * Code, if this is a renderer or pingback plugin.
	 */
	var $code = '';
	var $priority = 50;
	var $version = '0.1-dev';
	var $author = 'http://example.com/';
	var $help_url = '';

	var $apply_rendering = 'opt-in';


	/**
	 * Init: This gets called after a plugin has been registered/instantiated.
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = $this->T_('Short description');
		$this->long_desc = $this->T_('Longer description. You may also remove this.');
	}


	/**
	 * Define settings that the plugin uses/provides.
	 */
	function GetDefaultSettings()
	{
		return array();
	}


	/**
	 * Define user settings that the plugin uses/provides.
	 */
	function GetDefaultUserSettings()
	{
		return array();
	}


	// If you use hooks, that are not present in b2evo 1.8, you should also add
	// a GetDependencies() function and require the b2evo version your Plugin needs.
	// See http://doc.b2evolution.net/stable/plugins/Plugin.html#methodGetDependencies


	// Add the methods to hook into here...
	// See http://doc.b2evolution.net/stable/plugins/Plugin.html
}
?>
