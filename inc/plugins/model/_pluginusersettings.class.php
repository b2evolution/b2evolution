<?php
/**
 * This file implements the PluginUserSettings class, to handle plugin/user/name/value "pairs".
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
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
class PluginUserSettings extends AbstractSettings
{
	/**
	 * Constructor
	 *
	 * @param integer plugin ID where these settings are for
	 */
	function PluginUserSettings( $plugin_ID )
	{ // constructor
		parent::AbstractSettings( 'T_pluginusersettings', array( 'puset_plug_ID', 'puset_user_ID', 'puset_name' ), 'puset_value', 1 );

		$this->plugin_ID = $plugin_ID;
	}


	/**
	 * Get a setting by name for the Plugin.
	 *
	 * @param string The settings name.
	 * @param integer User ID (by default $current_User->ID will be used - make sure that it is available already in your event!)
	 * @return mixed|NULL|false False in case of error, NULL if not found, the value otherwise.
	 */
	function get( $setting, $user_ID = NULL )
	{
		if( ! isset($user_ID) )
		{
			global $current_User;
			if( ! isset($current_User) )
			{
				global $Debuglog;
				$Debuglog->add( 'No $current_User available in PluginUserSettings::get()/[ID'.$this->plugin_ID.']!', array('errors', 'plugins') );
				return false;
			}
			$user_ID = $current_User->ID;
		}
		return parent::get( $this->plugin_ID, $user_ID, $setting );
	}


	/**
	 * Set a Plugin setting. Use {@link dbupdate()} to write it to the database.
	 *
	 * @param string The settings name.
	 * @param string The settings value.
	 * @param integer User ID (by default $current_User->ID will be used - make sure that it is available already in your event!)
	 * @return boolean true, if the value has been set, false if it has not changed.
	 */
	function set( $setting, $value, $user_ID = NULL )
	{
		if( ! isset($user_ID) )
		{
			global $current_User;
			if( ! isset($current_User) )
			{
				global $Debuglog;
				$Debuglog->add( 'No $current_User available in PluginUserSettings::set()/[ID'.$this->plugin_ID.']!', array('errors', 'plugins') );
				return false;
			}
			$user_ID = $current_User->ID;
		}
		return parent::set( $this->plugin_ID, $user_ID, $setting, $value );
	}


	/**
	 * Delete a setting.
	 *
	 * Use {@link dbupdate()} to commit it to the database.
	 *
	 * @param string name of setting
	 * @param integer User ID (by default $current_User->ID will be used - make sure that it is available already in your event!)
	 */
	function delete( $setting, $user_ID = NULL )
	{
		if( ! isset($user_ID) )
		{
			global $current_User;
			if( ! isset($current_User) )
			{
				global $Debuglog;
				$Debuglog->add( 'No $current_User available in PluginUserSettings::delete()/[ID'.$this->plugin_ID.']!', array('errors', 'plugins') );
				return false;
			}
			$user_ID = $current_User->ID;
		}
		return parent::delete( $this->plugin_ID, $user_ID, $setting );
	}

}

?>