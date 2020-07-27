<?php
/**
 * This file implements the PluginGroupSettings class, to handle plugin/group/name/value "pairs".
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package plugins
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'settings/model/_abstractsettings.class.php', 'AbstractSettings' );

/**
 * Class to handle settings for plugins
 *
 * @package plugins
 */
class PluginGroupSettings extends AbstractSettings
{
	/**
	 * Constructor
	 *
	 * @param integer plugin ID where these settings are for
	 */
	function __construct( $plugin_ID )
	{ // constructor
		parent::__construct( 'T_plugingroupsettings', array( 'pgset_plug_ID', 'pgset_grp_ID', 'pgset_name' ), 'pgset_value', 1 );

		$this->plugin_ID = $plugin_ID;
	}


	/**
	 * Get a setting by name for the Plugin.
	 *
	 * @param string The settings name.
	 * @param integer Group ID (by default $current_User->grp_ID will be used - make sure that it is available already in your event!)
	 * @return mixed|NULL|false False in case of error, NULL if not found, the value otherwise.
	 */
	function get( $setting, $group_ID = NULL )
	{
		if( ! isset( $group_ID ) )
		{
			global $current_User;
			if( ! isset( $current_User ) )
			{
				global $Debuglog;
				$Debuglog->add( 'No $current_User available in PluginGroupSettings::get()/[ID'.$this->plugin_ID.']!', array( 'errors', 'plugins' ) );
				return false;
			}
			$group_ID = $current_User->grp_ID;
		}

		return parent::getx( $this->plugin_ID, $group_ID, $setting );
	}


	/**
	 * Set a Plugin setting. Use {@link dbupdate()} to write it to the database.
	 *
	 * @param string The settings name.
	 * @param string The settings value.
	 * @param integer Group ID (by default $current_User->grp_ID will be used - make sure that it is available already in your event!)
	 * @return boolean true, if the value has been set, false if it has not changed.
	 */
	function set( $setting, $value, $group_ID = NULL )
	{
		if( ! isset( $group_ID ) )
		{
			global $current_User;
			if( ! isset( $current_User ) )
			{
				global $Debuglog;
				$Debuglog->add( 'No $current_User available in PluginGroupSettings::set()/[ID'.$this->plugin_ID.']!', array( 'errors', 'plugins' ) );
				return false;
			}
			$group_ID = $current_User->grp_ID;
		}

		return parent::setx( $this->plugin_ID, $group_ID, $setting, $value );
	}


	/**
	 * Delete a setting.
	 *
	 * Use {@link dbupdate()} to commit it to the database.
	 *
	 * @param string name of setting
	 * @param integer Group ID (by default $current_User->grp_ID will be used - make sure that it is available already in your event!)
	 */
	function delete( $setting, $group_ID = NULL )
	{
		if( ! isset( $group_ID ) )
		{
			global $current_User;
			if( ! isset( $current_User ) )
			{
				global $Debuglog;
				$Debuglog->add( 'No $current_User available in PluginGroupSettings::delete()/[ID'.$this->plugin_ID.']!', array( 'errors', 'plugins' ) );
				return false;
			}
			$group_ID = $current_User->grp_ID;
		}

		return parent::delete( $this->plugin_ID, $group_ID, $setting );
	}
}
?>