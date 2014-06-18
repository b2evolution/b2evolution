<?php
/**
 * This file implements the UserSettings class which handles user_ID/name/value triplets.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id: _usersettings.class.php 6135 2014-03-08 07:54:05Z manuel $
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes
 */
load_class( 'settings/model/_abstractsettings.class.php', 'AbstractSettings' );

/**
 * Class to handle the settings for users, and any user name-value pair which is not frequently used 
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

		'blogperms_layout' => 'wide',	// selected view in blog (user/group) perms

		'login_multiple_sessions' => 0, 	// disallow multiple concurrent sessions by default
		'timeout_sessions' => NULL,			// user session timeout (NULL means application default)

		'results_per_page' => 20,

		'show_evobar' => 1,
		'show_breadcrumbs' => 1,
		'show_menu' => 1,

		'last_activation_email' => NULL, // It should be the date of the last account activation email. If it is not set, and users is not activated means activation email wasn't sent.
		'last_unread_messages_reminder' => NULL, // It will be the date when the last unread message reminder email was sent
		'last_notification_email' => NULL, // It must have a 'timestamp_number' format, where the timestamp ( servertime ) is the last notification email ts, and the number is how many notification email was sent on that day
		'last_newsletter' => NULL, // It must have a 'timestamp_number' format, where the timestamp ( servertime ) is the last newsletter ts, and the number is how many newsletter was sent on that day
		'last_activation_reminder_key' => NULL, // It will be set at the first time when activation reminder email will be sent
		'activation_reminder_count' => 0, // How many activation reminder was sent since the user is not activated
		'send_activation_reminder' => 1, // Send reminder to activate my account if it is not activated
		'welcome_message_sent' => 0, // Used to know if user already received a welcome message after email activation

		// admin user notifications
		'send_cmt_moderation_reminder' => 1, // Send reminders about comments awaiting moderation
		'notify_new_user_registration' => 1, // Notify admin user when a new user has registered
		'notify_activated_account' => 1, // Notify admin user when an account has been activated by email
		'notify_closed_account' => 1, // Notify admin user when an account has been closed by the account owner
		'notify_reported_account' => 1, // Notify admin user when an account has been reported by another user
		'notify_changed_account' => 1, // Notify admin user when an account has been changed
		'notify_cronjob_error' => 1, // Notify admin user when a scheduled task ends with an error or timeout

		'account_close_ts' => NULL, // It will be the date when the account was closed. Until the account is not closed this will be NULL.
		'account_close_reason' => NULL, // It will be the reason why the account was closed. Until the account is not closed this will be NULL.

		'last_new_thread' => NULL, // It is the date when the user has created the last new thread, NULL if User has never create a new thread
		'new_thread_count' => 0, // How many new thread was created by this user TODAY!

		'show_online' => 1,     // Show if user is online or not
		'user_domain' => NULL,  // User domain
		'user_browser' => NULL, // User browser

		'email_format' => 'auto', // Email format: auto | html | text

		'admin_skin' => 'chicago',  // User default admin skin
	);

	/**
	 * The configurable default settings.
	 * Add those settings below, which default value is saved in GeneralSettings.
	 * For these option we didn't add a default value here intentionally, this way it will get the default value from general settings!
	 * All of this options must have a pair with a 'def_' prefix in GeneralSettings class.
	 * We use this array when we are reseting the default settings.
	 *
	 * @access protected
	 * @var array
	 */
	var $_configurable_defaults = array(
		'notify_messages' => 1, 	// Notify user when receives a private message
		'notify_unread_messages' => 1, // Notify user when he has unread messages more then 24 hour, and he was not notified in the last 3 days
		'notify_published_comments' => 1, // Notify user when a comment is published in an own post
		'notify_comment_moderation' => 1, // Notify when a comment is awaiting moderation and the user has right to moderate that comment
		'notify_post_moderation' => 1, // Notify when a post is awaiting moderation and the user has right to moderate that post

		'enable_PM' => 1,
		'enable_email' => 1,

		'newsletter_news' => 1, // Send news
		'newsletter_ads'  => 0, // Send ADs

		'notification_email_limit' => 3, // How many notification email is allowed per day for this user
		'newsletter_limit' => 1, // How many newsletter email is allowed per day for this user
	);


	/**
	 * Constructor
	 */
	function UserSettings()
	{ // constructor
		parent::AbstractSettings( 'T_users__usersettings', array( 'uset_user_ID', 'uset_name' ), 'uset_value', 1 );
	}


	/**
	 * Get a setting from the DB user settings table
	 *
	 * @param string name of setting
	 * @param integer User ID (by default $current_User->ID will be used)
	 */
	function get( $setting, $user_ID = NULL )
	{
		global $Settings;

		if( ! isset($user_ID) )
		{
			global $current_User;

			if( ! isset($current_User) )
			{ // no current/logged in user:
				$result = $this->get_default($setting);
				if( $result == NULL )
				{
					$result = $Settings->get( 'def_'.$setting );
				}
				return $result;
			}

			$user_ID = $current_User->ID;
		}

		$result = parent::get( $user_ID, $setting );
		if( $result == NULL )
		{
			$result = $Settings->get( 'def_'.$setting );
		}
		return $result;
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
			if( $memorize )
			{ // Memorize param
				memorize_param( $param_name, $type, $default, $value );
			}
		}

		set_param( $param_name, $value );
		return get_param($param_name);
	}


	/**
	 * Reset a user settings to the default values
	 * 
	 * @param integer user ID
	 * @param boolean set to true to save modifications
	 */
	function reset_to_defaults( $user_ID, $db_save = true )
	{
		// Remove all UserSettings where a default or configurable default exists:
		foreach( $this->_defaults as $k => $v )
		{
			$this->delete( $k, $user_ID );
		}
		foreach( $this->_configurable_defaults as $k => $v )
		{
			$this->delete( $k, $user_ID );
		}
	}
}

?>