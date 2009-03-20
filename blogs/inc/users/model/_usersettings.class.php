<?php
/**
 * This file implements the UserSettings class which handles user_ID/name/value triplets.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
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
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes
 */
load_class('settings/model/_abstractsettings.class.php');

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
		'action_icon_threshold' => 3,
		'action_word_threshold' => 3,
		'display_icon_legend' => 0,
		'control_form_abortions' => 1,
		'focus_on_first_input' => 0,			// TODO: fix sideeffect when pressing F5
		'pref_browse_tab' => 'full',
		'pref_edit_tab' => 'simple',

		'fm_imglistpreview' => 1,
		'fm_showdate'       => 'compact',
		'fm_allowfiltering' => 'simple',

		'blogperms_layout' => 'default',	// selected view in blog (user/group) perms

		'login_multiple_sessions' => 1, 	// allow multiple concurrent sessions? (PARAMOUNT ON DEMO SERVER)

		'results_per_page' => 20,
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

			if( ! isset($current_User) )
			{ // no current/logged in user:
				return $this->get_default($setting);
			}

			$user_ID = $current_User->ID;
		}

		return parent::get( $user_ID, $setting );
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

			if( ! isset($current_User) )
			{ // no current/logged in user:
				return false;
			}

			$user_ID = $current_User->ID;
		}

		return parent::set( $user_ID, $setting, $value );
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

			if( ! isset($current_User) )
			{ // no current/logged in user:
				return false;
			}

			$user_ID = $current_User->ID;
		}

		return parent::delete( $user_ID, $setting );
	}


	/**
	 * Get a param from Request and save it to UserSettings, or default to previously saved user setting.
	 *
	 * If the user setting was not set before (and there's no default given that gets returned), $default gets used.
	 *
	 * @todo Move this to _abstractsettings.class.php - the other Settings object can also make use of it!
	 *
	 * @param string Request param name
	 * @param string User setting name. Make sure this is unique!
	 * @param string Force value type to one of:
	 * - integer
	 * - float
	 * - string (strips (HTML-)Tags, trims whitespace)
	 * - array
	 * - object
	 * - null
	 * - html (does nothing)
	 * - '' (does nothing)
	 * - '/^...$/' check regexp pattern match (string)
	 * - boolean (will force type to boolean, but you can't use 'true' as a default since it has special meaning. There is no real reason to pass booleans on a URL though. Passing 0 and 1 as integers seems to be best practice).
	 * Value type will be forced only if resulting value (probably from default then) is !== NULL
	 * @param mixed Default value or TRUE if user input required
	 * @param boolean Do we need to memorize this to regenerate the URL for this page?
	 * @param boolean Override if variable already set
	 * @return NULL|mixed NULL, if neither a param was given nor {@link $UserSettings} knows about it.
	 */
	function param_Request( $param_name, $uset_name, $type = '', $default = '', $memorize = false, $override = false ) // we do not force setting it..
	{
		$value = param( $param_name, $type, NULL, $memorize, $override, false ); // we pass NULL here, to see if it got set at all

		if( $value !== false )
		{ // we got a value
			$this->set( $uset_name, $value );
			$this->dbupdate();
		}
		else
		{ // get the value from user settings
			$value = $this->get($uset_name);

			if( is_null($value) )
			{ // it's not saved yet and there's not default defined ($_defaults)
				$value = $default;
			}
		}

		set_param( $param_name, $value );
		return get_param($param_name);
	}
}


/*
 * $Log$
 * Revision 1.6  2009/03/20 03:38:04  fplanque
 * rollback -- http://forums.b2evolution.net/viewtopic.php?t=18269
 *
 * Revision 1.4  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.3  2008/01/21 09:35:36  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/06/30 22:10:32  fplanque
 * changed default
 *
 * Revision 1.1  2007/06/25 11:01:48  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.32  2007/05/26 22:21:32  blueyed
 * Made $limit for Results configurable per user
 *
 * Revision 1.31  2007/05/13 18:49:55  fplanque
 * made autoselect_blog() more robust under PHP4
 *
 * Revision 1.30  2007/04/26 00:11:11  fplanque
 * (c) 2007
 *
 * Revision 1.29  2007/02/25 01:39:06  fplanque
 * wording
 *
 * Revision 1.28  2007/02/21 22:21:30  blueyed
 * "Multiple sessions" user setting
 *
 * Revision 1.27  2007/01/25 03:17:00  fplanque
 * visual cleanup for average users
 * geeky stuff preserved as options
 *
 * Revision 1.26  2007/01/24 01:57:07  fplanque
 * minor
 *
 * Revision 1.25  2007/01/23 05:00:25  fplanque
 * better user defaults
 *
 * Revision 1.24  2006/12/18 03:20:22  fplanque
 * _header will always try to set $Blog.
 * controllers can use valid_blog_requested() to make sure we have one
 * controllers should call set_working_blog() to change $blog, so that it gets memorized in the user settings
 *
 * Revision 1.23  2006/12/18 01:43:26  fplanque
 * minor bugfix
 *
 * Revision 1.22  2006/11/24 18:27:25  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.21  2006/11/12 17:37:41  fplanque
 * code for "simple" is "default"
 *
 * Revision 1.20  2006/11/04 17:43:14  blueyed
 * Blog perm layout views: fixed non-JS links (ctrl param) and store selected one in UserSettings (TODO for switching by JS)
 */
?>