<?php
/**
 * This file implements the PluginSettings class, to handle plugin/name/value triplets.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
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
class PluginSettings extends AbstractSettings
{
	/**
	 * Constructor
	 *
	 * @param integer plugin ID where these settings are for
	 */
	function PluginSettings( $plugin_ID )
	{ // constructor
		parent::AbstractSettings( 'T_pluginsettings', array( 'pset_plug_ID', 'pset_name' ), 'pset_value', 1 );

		$this->plugin_ID = $plugin_ID;
	}


	/**
	 * Get a setting by name for the Plugin.
	 *
	 * @param string The settings name.
	 * @return mixed|NULL|false False in case of error, NULL if not found, the value otherwise.
	 */
	function get( $setting )
	{
		return parent::get( $this->plugin_ID, $setting );
	}


	/**
	 * Set a Plugin setting. Use {@link dbupdate()} to write it to the database.
	 *
	 * @param string The settings name.
	 * @param string The settings value.
	 * @return boolean true, if the value has been set, false if it has not changed.
	 */
	function set( $setting, $value )
	{
		return parent::set( $this->plugin_ID, $setting, $value );
	}


	/**
	 * Delete a setting.
	 *
	 * Use {@link dbupdate()} to commit it to the database.
	 *
	 * @param string name of setting
	 */
	function delete( $setting )
	{
		return parent::delete( $this->plugin_ID, $setting );
	}

}

/*
 * $Log$
 * Revision 1.4  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.3  2006/03/12 23:08:59  fplanque
 * doc cleanup
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