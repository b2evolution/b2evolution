<?php
/**
 * This file implements the GeneralSettings class, which handles Name/Value pairs.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'settings/model/_abstractsettings.class.php', 'AbstractSettings' );

/**
 * Class to handle the global settings.
 *
 * @package evocore
 */
class GeneralSettings extends AbstractSettings
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
		'admin_skin' => 'chicago',

		'antispam_last_update' => '2000-01-01 00:00:00',
		'antispam_threshold_publish' => '-90',
		'antispam_threshold_delete' => '100', // do not delete by default!
		'antispam_block_spam_referers' => '0',	// By default, let spam referers go in silently (just don't log them). This is in case the blacklist is too paranoid (social media, etc.)
		'antispam_report_to_central' => '1',

		'evonet_last_update' => '1196000000',		// just around the time we implemented this ;)
		'evonet_last_attempt' => '1196000000',		// just around the time we implemented this ;)

		'log_public_hits' => '1',
		'log_admin_hits' => '0',
		'log_spam_hits' => '0',
		'auto_prune_stats_mode' => 'page',  // 'page' is the safest mode for average installs (may be "off", "page" or "cron")
		'auto_prune_stats' => '15',         // days (T_hitlog and T_sessions)
		'auto_empty_trash' => '15',         // days (How many days to keep recycled comments)

		'email_service' => 'mail', // Preferred email service: 'mail', 'smtp'
		'force_email_sending' => '0', // Force email sending

		'outbound_notifications_mode' => 'immediate', // 'immediate' is the safest mode for average installs (may be "off", "immediate" or "cron")
		'notification_sender_email' => '', // notification emails will be sent from this email. The real default value is set in the constructor.
		'notification_return_path' => '', // erroneous emails will be sent to this email address. The real default value is set in the constructor.
		'notification_sender_name' => 'b2evo mailer', // notification emails will be sent with this sender name
		'notification_short_name' => 'This site', // notification emails will use this as short site name
		'notification_long_name' => '', // notification emails will use this as long site name
		'notification_logo' => '', // notification emails will use this as url to site logo

		'fm_enable_create_dir' => '1',
		'fm_enable_create_file' => '1',
		'fm_enable_roots_blog' => '1',
		'fm_enable_roots_user' => '1',
		'fm_enable_roots_shared' => '1',
		'fm_enable_roots_skins' => '1',

		'fm_showtypes' => '0',
		'fm_showfsperms' => '0',

		'fm_default_chmod_file' => '664',
		'fm_default_chmod_dir' => '775',

		// Image options
		'exif_orientation' => '1',
		'fm_resize_enable' => '0',
		'fm_resize_width' => '2880',
		'fm_resize_height' => '1800',
		'fm_resize_quality' => '95',

		'newusers_canregister' => 'no',
		'registration_is_public' => '1',
		'quick_registration' => '0',
		'newusers_mustvalidate' => '1',
		'newusers_revalidate_emailchg' => '1',
		'validation_process' => 'easy',
		'activate_requests_limit' => '300', // Only one activation email can be sent to the same email address in the given interval ( value is in seconds )
		'newusers_findcomments' => '1',
		'after_email_validation' => 'return_to_original', // where to redirect after account activation. Values: return_to_original, or the previously set specific url
		'after_registration' => 'return_to_original', // where to redirect after new user registration. Values: return_to_original redirect_to url, or return to the previously set specific url
		'newusers_level' => '1',
		'registration_require_gender' => 'hidden',
		'registration_ask_locale' => '0',

		// Default user settings
		'def_enable_PM' => '1',
		'def_enable_email' => '0',
		'def_notify_messages' => '1',
		'def_notify_unread_messages' => '1',
		'def_notify_published_comments' => '1',
		'def_notify_comment_moderation' => '1',
		'def_notify_meta_comments' => '1',
		'def_notify_post_moderation' => '1',
		'def_newsletter_news' => '1',
		'def_newsletter_ads' => '0',
		'def_notification_email_limit' => '3',
		'def_newsletter_limit' => '1',

		'allow_avatars' => 1,
		'min_picture_size' => 160, // minimum profile picture dimensions in pixels (width and height)
		'allow_html_message' => 0, // Allow HTML in messages

		// Welcome private message
		'welcomepm_enabled' => 0,
		'welcomepm_from'    => 'admin',	// User login
		'welcomepm_title'   => 'Welcome to our community!',
		'welcomepm_message' => '',

		// Info message to reporters after account deletion
		'reportpm_enabled' => 0,
		'reportpm_from'    => 'admin',	// User login
		'reportpm_title'   => 'The user account $reportedlogin$ that you have reported has just been deleted.',
		'reportpm_message' => "You have reported the user account \$reportedlogin\$.\n\nThis is to inform you that we have just deleted this account.\n\nThank you for your help in keeping this site a friendly place!",

		'regexp_filename' => '^[a-zA-Z0-9\-_. ]+$', // TODO: accept (spaces and) special chars / do full testing on this
		'regexp_dirname' => '^[a-zA-Z0-9\-_]+$', // TODO: accept spaces and special chars / do full testing on this
		'reloadpage_timeout' => '300',
		'time_difference' => '0',
		'timeout_sessions' => '604800',             // seconds (604800 == 7 days)
		'timeout_online' => '1200',                 // seconds (1200 == 20 minutes)
		'upload_enabled' => '1',
		'upload_maxkb' => '32000',					// 32 MB
		'evocache_foldername' => '.evocache',
		'blogs_order_by' => 'order',				// blogs order in backoffice menu and other places
		'blogs_order_dir' => 'ASC',					// blogs order direction in backoffice menu and other places

		'user_minpwdlen' => '5',
		'js_passwd_hashing' => '1',					// Use JS password hashing by default
		'passwd_special' => '0',					// Do not require a special character in password by default
		'strict_logins' => 1,						// Allow only plain ACSII characters in user login

		'allow_moving_chapters' => '0',				// Do not allow moving chapters by default

		'cross_posting' => 0,						// Allow additional categories from other blogs
		'cross_posting_blog' => 0,					// Allow to choose main category from another blog
		'redirect_moved_posts' => 0,                // Allow to redirect moved posts link to the correct blog

		'subscribe_new_blogs' => 'public', // Subscribing to new blogs: 'page', 'public', 'all'

		// Site settings
		'system_lock' => 0,
		'site_code' => 'b2evo',
		'site_color' => '#ff8c0f',
		'site_footer_text' => 'Cookies are required to enable core site functionality. &copy;$year$ by $short_site_name$.',
		'site_skins_enabled' => 1, // Enables a sitewide header and footer
		'info_blog_ID' => 0, // Blog for info pages
		'general_cache_enabled' => 0,

		'newblog_cache_enabled' => 0,
		'newblog_cache_enabled_widget' => 0,

		//Default blogs skin setting
		'def_normal_skin_ID' => '1',                // Default normal skin ID
		'def_mobile_skin_ID' => '0',                // 0 means same as normal skin
		'def_tablet_skin_ID' => '0',                // 0 means same as normal skin

		// Post by Email
		'eblog_enabled' => 0,
		'eblog_method' => 'imap',
		'eblog_encrypt' => 'none',
		'eblog_server_port' => 143,
		'eblog_default_category' => 1,
		'eblog_add_imgtag' => 0,
		'eblog_body_terminator' => '___',
		'eblog_subject_prefix' => 'blog:',
		'eblog_html_tag_limit' => 1,
		'eblog_delete_emails' => 1,
		'eblog_renderers' => array('default'),

		// Returned Emails
		'repath_enabled' => 0,
		'repath_method' => 'imap',
		'repath_encrypt' => 'none',
		'repath_server_port' => 143,
		'repath_subject' => 'Returned mail:
Mail delivery failed:
Undelivered Mail Returned to Sender
Delivery Status Notification (Failure)
delayed 24 hours
delayed 48 hours
delayed 72 hours
failure notice
Undeliverable:',
		'repath_body_terminator' => '---------- Forwarded message ----------
------ This is a copy of the message, including all the headers. ------
----- Transcript of session follows -----
Received: from
--- Below this line is a copy of the message.',
		'repath_errtype' => 'C ^101
C ^111
T ^421
T ^431
T ^432
T ^441
T ^450
T ^452
C ^500
C ^501
C ^502
C ^503
C ^504
T ^512
T ^523
P ^510
P ^511
S ^541
S ^550[ \-]5\.4\.1
P ^550([ \-]5\.1\.1)?
C ^551
S DNSBL
S Blacklist
S reputation
S client host .* blocked
S verification failed
S verify failed
S policy reasons
S not authorized
S refused
S spammer
S DYN:T1
S (CON:B1)
T currently suspended
T temporarily disabled
T unrouteable address
T Mailbox is inactive
T over quota
T account has been disabled
T timeout exceeded
T quota exceeded
T Delivery attempts will continue
P is unavailable
P not available
P user doesn\'t have a .+ account
P invalid recipient
P does not like recipient
P not our customer
P no such user
P addressee unknown
P address rejected
P permanent delivery error
P permanent error
P mailbox unavailable
P no mailbox here by that name
P mailbox has been blocked
C relay denied
C relaying denied
C message size exceeds',

		'general_xmlrpc' => 1,
		'xmlrpc_default_title' => '',				// default title for posts created throgh blogger api

		'nickname_editing'   => 'edited-user',		// "never" - Never allow; "default-no" - Let users decide, default to "no" for new users; "default-yes" - Let users decide, default to "yes" for new users; "always" - Always allow
		'firstname_editing'  => 'edited-user',		// "edited-user" - Can be edited by user; "edited-admin" - Can be edited by admins only, "hidden" - Hidden for all
		'lastname_editing'   => 'edited-user',		// "edited-user" - Can be edited by user; "edited-admin" - Can be edited by admins only, "hidden" - Hidden for all
		'location_country'   => 'optional', // Editing mode of country for user:   "optional" | "required" | "hidden"
		'location_region'    => 'optional', // Editing mode of region for user:    "optional" | "required" | "hidden"
		'location_subregion' => 'optional', // Editing mode of subregion for user: "optional" | "required" | "hidden"
		'location_city'      => 'optional', // Editing mode of city for user:      "optional" | "required" | "hidden"
		'minimum_age'        => '13', // Minimum age for user forms
		'multiple_sessions'  => 'userset_default_no', // multiple sessions settings -- overriden for demo mode in contructor
		'emails_msgform'     => 'adminset', // Receiving emails through a message form is allowed: "never" | "adminset" | "userset"

	// Display options:
		'use_gravatar' => 1, // Use gravatar if a user has not uploaded a profile picture
		'default_gravatar' => 'b2evo', // Gravatar type: 'b2evo', '', 'identicon', 'monsterid', 'wavatar', 'retro'
		'username_display' => 'login', // What to display as user name: 'login' - Usernames/Logins or 'name' - 'Friendly names'
		'bubbletip' => 1, // Display bubletips in the Back-office
		'bubbletip_size_admin' => 'fit-160x160', // Avatar size in the bubbletip in the Back-office
		'bubbletip_size_front' => 'fit-160x160', // Avatar size in the bubbletip in the Front-office
		'bubbletip_anonymous' => 1, // Display bubbletips in Front-office for anonymous users
		'bubbletip_size_anonymous' => 'fit-160x160-blur-18', // Avatar size in the bubbletip in the Front-office for anonymous users
		'bubbletip_overlay' => "Log in to\r\nsee this\r\nimage",// Overlay text on the profile image for anonymous users
		'allow_anonymous_user_list' => 1, // Allow anonymous users to see user list (disp=users)
		'allow_anonymous_user_profiles' => 0, // Allow anonymous users to see the user display ( disp=user )
		'allow_anonymous_user_level_min' => 0, // Min value of user group level to display for anonymous users
		'allow_anonymous_user_level_max' => 10, // Max value of user group level to display for anonymous users
		'user_url_loggedin' => 'page', // Link an user url to 'page' or 'url' for logged-in users
		'user_url_anonymous' => 'page', // Link an user url to 'page' or 'url' for anonymous users

		// Account closing options:
		'account_close_enabled' => 1, // Allow users to close their account themselves
		'account_close_intro'   => "We are sorry to see you leave.\n\nWe value your feedback. Please be so kind and tell us in a few words why you are leaving us. This will help us to improve the site for the future.",
		'account_close_reasons' => "I don't need this account any more.\nI do not like this site.", // Reasons to close an account, separated by new line
		'account_close_byemsg'  => 'Your account has now been closed. If you ever want to log in again, you will need to create a new account.',

	// Back-end settings, these can't be modified by the users:
		'last_invalidation_timestamp' => 0,
	);


	/**
	 * Constructor.
	 *
	 * This loads the general settings and checks db_version.
	 *
	 * It will also turn off error-reporting/halting of the {@link $DB DB object}
	 * temporarily to present a more decent error message if tables do not exist yet.
	 *
	 * Because the {@link $DB DB object} itself creates a connection when it gets
	 * created "Error selecting database" occurs before we can check for it here.
	 */
	function GeneralSettings()
	{
		global $new_db_version, $DB, $demo_mode, $instance_name, $basehost;

		$save_DB_show_errors = $DB->show_errors;
		$save_DB_halt_on_error = $DB->halt_on_error;
		$DB->halt_on_error = false;
		$DB->show_errors = false;

		// Init through the abstract constructor. This should be the first DB connection.
		parent::AbstractSettings( 'T_settings', array( 'set_name' ), 'set_value', 0 );

		// check DB version:
		if( $this->get( 'db_version' ) != $new_db_version )
		{ // Database is not up to date:
			if( $DB->last_error )
			{
				$error_title = 'The database is not installed yet!';
				$error_message = '<p>MySQL error:</p>'.$DB->last_error;
			}
			else
			{
				$error_title = 'Database schema is not up to date!';
				$error_message = '<p>Database schema is not up to date!</p>'
					.'<p>You have schema version &laquo;'.(integer)$this->get( 'db_version' ).'&raquo;, '
					.'but we would need &laquo;'.(integer)$new_db_version.'&raquo;.</p>';
			}
			global $adminskins_path;
			require $adminskins_path.'conf_error.main.php'; // error & exit
		}

		$DB->halt_on_error = $save_DB_halt_on_error;
		$DB->show_errors = $save_DB_show_errors;


		if( $demo_mode )
		{ // Demo mode requires to allow multiple concurrent sessions:
			$this->_defaults['multiple_sessions'] = 'always';
		}

		// set those defaults which needs some global variables
		$this->_defaults['notification_sender_email'] = $instance_name.'-noreply@'.preg_replace( '/^www\./i', '', $basehost );
		$this->_defaults['notification_return_path'] = $instance_name.'-return@'.preg_replace( '/^www\./i', '', $basehost );
	}


	/**
	 * Get a 32-byte string that can be used as salt for public keys.
	 *
	 * @return string
	 */
	function get_public_key_salt()
	{
		$public_key_salt = $this->get( 'public_key_salt' );
		if( empty($public_key_salt) )
		{
			$public_key_salt = generate_random_key(32);
			$this->set( 'public_key_salt', $public_key_salt );
			$this->dbupdate();
		}
		return $public_key_salt;
	}


	/**
	 * Get a member param by its name
	 *
	 * @param mixed Name of parameter
	 * @param boolean true to return param's real value
	 * @return mixed Value of parameter
	 */
	function get( $parname, $real_value = false )
	{
		if( $real_value )
		{
			return parent::get( $parname );
		}

		switch($parname)
		{
			case 'allow_avatars':
				return ( parent::get( $parname ) && isset($GLOBALS['files_Module']) );
				break;

			case 'upload_enabled':
				return ( parent::get( $parname ) && isset($GLOBALS['files_Module']) );
				break;

			default:
				return parent::get( $parname );
		}
	}

}

?>