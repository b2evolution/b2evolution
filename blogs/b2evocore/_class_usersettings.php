<?php
/**
 * Class to handle the user settings/preferences
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 * @author blueyed
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes
 */
require_once( dirname(__FILE__).'/_class_abstractsettings.php' );

/**
 * Class to handle the global settings
 *
 * @package evocore
 */
class UserSettings extends AbstractSettings
{
	/**
	 * Constructor
	 *
	 * loads settings, checks db_version
	 */
	function UserSettings()
	{ // constructor
		global $new_db_version, $tableuserprefs;

		$this->dbtablename = $tableuserprefs;
		$this->colkeynames = array( 'upref_user_ID', 'upref_name' );
		$this->colvaluename = 'upref_value';
		
		parent::AbstractSettings();
	}


	/**
	 * get a setting from the DB settings table
	 * @param string name of setting
	 * @param integer User ID (by default $current_User->ID will be used)
	 */
	function get( $setting, $user = '#' )
	{
		global $current_User;
		if( $user == '#' )
			return parent::get( $current_User->ID, $setting );
		else
			return parent::get( $user, $setting );
	}


	/**
	 * temporarily sets a setting (updateDB(-) writes it to DB)
	 *
	 * @param string name of setting
	 * @param mixed new value
	 * @param integer User ID (by default $current_User->ID will be used)
	 */
	function set( $setting, $value, $user = '#' )
	{
		global $current_User;
		if( $user == '#' )
			return parent::set( array( $current_User->ID, $setting, $value ) );
		else
			return parent::set( array( $user, $setting, $value ) );
	}


	/**
	 * commits changed settings to DB
	 */
	function updateDB()
	{
		return parent::updateDB();
	}

}
?>
