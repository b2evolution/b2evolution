<?php
/**
 * This file implements the PluginSettings class, to handle plugin/name/value triplets.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
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