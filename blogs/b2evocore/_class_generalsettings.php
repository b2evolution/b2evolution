<?php
/**
 * Class to handle the global settings
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
 * Class to handle the global settings
 *
 * @package evocore
 */
class GeneralSettings extends AbstractSettings
{
	/**
	 * Constructor
	 *
	 * loads settings, checks db_version
	 */
	function GeneralSettings()
	{ // constructor
		global $new_db_version, $tablesettings;

		$this->dbtablename = $tablesettings;
		$this->colkeynames = array( 'set_name' );
		$this->colvaluename = 'set_value';
		
		parent::AbstractSettings();
		
		if( $this->get( 'db_version' ) != $new_db_version )
		{	// Database is not up to date:
			$error_message = 'Database schema is not up to date. You have schema version '.$this->get( 'db_version' ).', but we would need '.$new_db_version.'.';
			require dirname(__FILE__).'/_conf_error.page.php';	// error & exit
		}
	}


	/**
	 * get a setting from the DB settings table
	 * @param string name of setting
	 */
	function get( $setting )
	{
		return parent::get( $setting );
	}


	/**
	 * temporarily sets a setting (updateDB(-) writes it to DB)
	 *
	 * @param string name of setting
	 * @param mixed new value
	 */
	function set( $setting, $value )
	{
		return parent::set( array( $setting, $value ) );
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
