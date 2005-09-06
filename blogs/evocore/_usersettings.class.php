<?php
/**
 * This file implements the UserSettings class, to handle user/name/value triplets.
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
		parent::AbstractSettings( 'T_usersettings', array( 'uset_user_ID', 'uset_name' ), 'uset_value', 1 );
	}


	/**
	 * Get a setting from the DB user settings table
	 *
	 * @param string name of setting
	 * @param integer User ID (by default $current_User->ID will be used)
	 */
	function get( $setting, $user = NULL )
	{
		global $current_User;

		if( $user === NULL )
		{
			return parent::get( $current_User->ID, $setting );
		}
		else
		{
			return parent::get( $user, $setting );
		}
	}


	/**
	 * Temporarily sets a user setting ({@link updateDB()} writes it to DB)
	 *
	 * @param string name of setting
	 * @param mixed new value
	 * @param integer User ID (by default $current_User->ID will be used)
	 */
	function set( $setting, $value, $user = NULL )
	{
		global $current_User;

		if( $user === NULL )
		{
			return parent::set( $current_User->ID, $setting, $value );
		}
		else
		{
			return parent::set( $user, $setting, $value );
		}
	}


	/**
	 * Mark a setting for deletion ({@link updateDB()} writes it to DB).
	 *
	 * @param string name of setting
	 * @param integer User ID (by default $current_User->ID will be used)
	 */
	function delete( $setting, $user = NULL )
	{
		global $current_User;

		if( is_null($user) )
		{
			$user = $current_User->ID;
		}

		return parent::delete( $user, $setting );
	}
}

/*
 * $Log$
 * Revision 1.7  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.6  2005/03/15 19:19:49  fplanque
 * minor, moved/centralized some includes
 *
 * Revision 1.5  2005/02/28 09:06:34  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.4  2005/02/22 02:30:20  blueyed
 * overloaded delete()
 *
 * Revision 1.3  2005/01/06 05:20:14  blueyed
 * refactored (constructor), getDefaults()
 *
 * Revision 1.2  2004/11/08 02:23:44  blueyed
 * allow caching by column keys (e.g. user ID)
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.7  2004/10/12 17:22:29  fplanque
 * Edited code documentation.
 *
 */
?>