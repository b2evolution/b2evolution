<?php
/**
 * This file implements the PluginUserSettings class, to handle plugin/user/name/value "pairs".
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 *
 * @version $Id$
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes
 */
require_once dirname(__FILE__).'/_abstractsettings.class.php';

/**
 * Class to handle settings for plugins
 *
 * @package evocore
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
			if( ! is_object($current_User) )
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
			if( ! is_object($current_User) )
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
			if( ! is_object($current_User) )
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

/*
 * $Log$
 * Revision 1.4  2006/03/24 01:12:26  blueyed
 * Catch cases where $current_User is not set (yet) and no user_ID is given and add debuglog entries.
 *
 * Revision 1.3  2006/03/12 23:08:59  fplanque
 * doc cleanup
 *
 * Revision 1.2  2006/03/11 02:02:00  blueyed
 * Normalized t_pluginusersettings
 *
 * Revision 1.1  2006/02/27 16:57:12  blueyed
 * PluginUserSettings - allows a plugin to store user related settings
 *
 * Revision 1.2  2006/02/24 22:09:00  blueyed
 * Plugin enhancements
 *
 * Revision 1.1  2006/02/23 21:11:58  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.3  2005/12/22 23:13:40  blueyed
 * Plugins' API changed and handling optimized
 *
 * Revision 1.2  2005/12/08 22:32:19  blueyed
 * Merged from post-phoenix; Added/fixed delete() (has to be derived to allow using it without plug_ID)
 *
 * Revision 1.1.2.2  2005/12/06 21:56:21  blueyed
 * Get PluginSettings straight (removing $default_keys).
 *
 * Revision 1.1.2.1  2005/11/16 22:45:32  blueyed
 * DNS Blacklist antispam plugin; T_pluginsettings; Backoffice editing for plugins settings; $Plugin->Settings; MERGE from HEAD;
 *
 *
 */
?>