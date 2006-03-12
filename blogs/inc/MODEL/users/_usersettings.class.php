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
require_once dirname(__FILE__).'/../settings/_abstractsettings.class.php';

/**
 * Class to handle the settings for users
 *
 * @package evocore
 */
class UserSettings extends AbstractSettings
{
	/**
	 * The default settings to use, when a setting is not given
	 * in the database.
	 *
	 * @todo Allow overriding from /conf/_config_TEST.php?
	 * @access protected
	 * @var array
	 */
	var $_defaults = array(
		'pref_browse_tab' => 'posts',
	);


	/**
	 * Constructor
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
	function get( $setting, $user_ID = NULL )
	{
		if( ! isset($user_ID) )
		{
			global $current_User;

			return parent::get( $current_User->ID, $setting );
		}
		else
		{
			return parent::get( $user_ID, $setting );
		}
	}


	/**
	 * Temporarily sets a user setting ({@link dbupdate()} writes it to DB)
	 *
	 * @param string name of setting
	 * @param mixed new value
	 * @param integer User ID (by default $current_User->ID will be used)
	 */
	function set( $setting, $value, $user_ID = NULL )
	{
		if( ! isset($user_ID) )
		{
			global $current_User;
			return parent::set( $current_User->ID, $setting, $value );
		}
		else
		{
			return parent::set( $user_ID, $setting, $value );
		}
	}


	/**
	 * Mark a setting for deletion ({@link dbupdate()} writes it to DB).
	 *
	 * @param string name of setting
	 * @param integer User ID (by default $current_User->ID will be used)
	 */
	function delete( $setting, $user_ID = NULL )
	{
		if( ! isset($user_ID) )
		{
			global $current_User;

			$user_ID = $current_User->ID;
		}

		return parent::delete( $user_ID, $setting );
	}


	/**
	 * Get a param, in co-operation with {@link $Request}.
	 *
	 * If the param is given (through {@link $Request}), it will get updated in
	 * here, otherwise the user's setting gets used.
	 *
	 * @param string Param and user setting name. Make sure this is unique.
	 * @param mixed,... The same params as to {@link Request::param()}.
	 *        You probably want to provide the third (absolutely) one ($default) as NULL, so it falls back
	 *        to {@link $UserSettings} and not the default you give.
	 *        Note: we use NULL as $default here, because of functionality.
	 * @return NULL|mixed NULL, if neither a param was given nor {@link $UserSettings} knows about it.
	 */
	function param_Request( $var, $type = '', $default = NULL, $memorize = false, $override = false, $forceset = true )
	{
		global $Request;

		$value = $Request->param( $var, $type, $default, $memorize, $override, $forceset );

		if( isset($value) )
		{
			$this->set( $var, $value );
			$this->dbupdate();

			return $value;
		}
		else
		{
			$Request->set_param( $var, $this->get($var) );

			return $Request->get($var);
		}
	}
}


/*
 * $Log$
 * Revision 1.3  2006/03/12 20:51:53  blueyed
 * Moved Request::param_UserSettings() to UserSettings::param_Request()
 *
 * Revision 1.2  2006/02/27 16:43:09  blueyed
 * Normalized
 *
 * Revision 1.1  2006/02/23 21:11:58  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.11  2005/12/19 17:39:56  fplanque
 * Remember prefered browing tab for each user.
 *
 * Revision 1.10  2005/12/12 19:21:23  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.9  2005/11/16 22:40:48  blueyed
 * doc
 *
 * Revision 1.8  2005/10/28 02:37:37  blueyed
 * Normalized AbstractSettings API
 *
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