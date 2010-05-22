<?php
/**
 * This file implements the GeneralSettings class, which handles Name/Value pairs.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
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

		'outbound_notifications_mode' => 'immediate', // 'immediate' is the safest mode for average installs (may be "off", "immediate" or "cron")

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

		'newusers_canregister' => '0',
		'newusers_mustvalidate' => '1',
		'newusers_revalidate_emailchg' => '0',
		'newusers_level' => '1',

		'allow_avatars' => 1,

		'regexp_filename' => '^[a-zA-Z0-9\-_. ]+$', // TODO: accept (spaces and) special chars / do full testing on this
		'regexp_dirname' => '^[a-zA-Z0-9\-_]+$', // TODO: accept spaces and special chars / do full testing on this
		'reloadpage_timeout' => '300',
		'time_difference' => '0',
		'timeout_sessions' => '604800',     // seconds (604800 == 7 days)
		'upload_enabled' => '1',
		'upload_maxkb' => '10000',					// 10 MB
		'evocache_foldername' => '.evocache',

		'user_minpwdlen' => '5',
		'js_passwd_hashing' => '1',					// Use JS password hashing by default

		'webhelp_enabled' => '1',

		'allow_moving_chapters' => '0',				// Do not allow moving chapters by default
		'chapter_ordering' => 'alpha',

		'cross_posting' => 0,						// Allow additional categories from other blogs
		'cross_posting_blog' => 0,					// Allow to choose main category from another blog

		'general_cache_enabled' => 0,

		'eblog_enabled' => 0,						// blog by email
		'eblog_method' => 'pop3',					// blog by email
		'eblog_encrypt' => 'none',					// blog by email
		'eblog_server_port' => 110,					// blog by email
		'eblog_default_category' => 1,				// blog by email
		'AutoBR' => 0,								// Used for email blogging. fp> TODO: should be replaced by "email renderers/decoders/cleaners"...
		'eblog_add_imgtag' => 1,					// blog by email
		'eblog_body_terminator' => '___',			// blog by email
		'eblog_subject_prefix' => 'blog:',			// blog by email
		'general_xmlrpc' => 1,
		'xmlrpc_default_title' => '',		//default title for posts created throgh blogger api

		'nickname_editing' => 'edited-user',		// "never" - Never allow; "default-no" - Let users decide, default to "no" for new users; "default-yes" - Let users decide, default to "yes" for new users; "always" - Always allow
		'multiple_sessions' => 'userset_default_no', // multiple sessions settings -- overriden for demo mode in contructor
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
		global $new_db_version, $DB, $demo_mode;

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
				$error_message = '<p>MySQL error:</p>'.$DB->last_error;
			}
			else
			{
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

}



/*
 * $Log$
 * Revision 1.31  2010/05/22 12:22:49  efy-asimo
 * move $allow_cross_posting in the backoffice
 *
 * Revision 1.30  2010/03/28 17:27:15  fplanque
 * changed upload default max kb
 *
 * Revision 1.29  2010/03/12 10:52:56  efy-asimo
 * Set EvoCache  folder names - task
 *
 * Revision 1.28  2010/02/08 17:53:55  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.27  2009/12/12 19:14:13  fplanque
 * made avatars optional + fixes on img props
 *
 * Revision 1.26  2009/12/06 22:55:21  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.25  2009/11/12 00:46:34  fplanque
 * doc/minor/handle demo mode
 *
 * Revision 1.24  2009/10/28 13:41:56  efy-maxim
 * default multiple sessions settings
 *
 * Revision 1.23  2009/10/26 12:59:36  efy-maxim
 * users management
 *
 * Revision 1.22  2009/10/25 19:24:51  efy-maxim
 * multiple_sessions param
 *
 * Revision 1.21  2009/10/25 19:20:30  efy-maxim
 * users settings
 *
 * Revision 1.20  2009/09/14 13:42:12  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.19  2009/09/02 13:47:32  waltercruz
 * Setting the default title fot posts created through blogger API
 *
 * Revision 1.18  2009/08/31 15:56:39  waltercruz
 * Adding setting to enable/disable xmlrc
 *
 * Revision 1.17  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.16  2009/02/22 16:55:27  blueyed
 * Add GeneralSettings::get_public_key_salt
 *
 * Revision 1.15  2009/01/28 21:23:23  fplanque
 * Manual ordering of categories
 *
 * Revision 1.14  2008/10/06 18:11:58  tblue246
 * Further blog by email fixes
 *
 * Revision 1.13  2008/10/06 11:02:27  tblue246
 * Blog by mail now supports POP3 & IMAP, SSL & TLS
 *
 * Revision 1.12  2008/10/05 10:55:46  tblue246
 * Blog by mail: We've only one working method => removed the drop-down box and added automatical change to pop3a.
 * The default value for this setting was in the wrong file, moved.
 *
 * Revision 1.11  2008/09/28 08:06:07  fplanque
 * Refactoring / extended page level caching
 *
 * Revision 1.10  2008/09/23 06:18:38  fplanque
 * File manager now supports a shared directory (/media/shared/global/)
 *
 * Revision 1.9  2008/04/04 17:02:22  fplanque
 * cleanup of global settings
 *
 * Revision 1.8  2008/02/19 11:11:19  fplanque
 * no message
 *
 * Revision 1.7  2008/01/21 09:35:34  fplanque
 * (c) 2008
 *
 * Revision 1.6  2007/11/28 17:29:44  fplanque
 * Support for getting updates from b2evolution.net
 *
 * Revision 1.5  2007/11/03 04:57:51  fplanque
 * no message
 *
 * Revision 1.4  2007/11/01 19:50:39  fplanque
 * minor
 *
 * Revision 1.3  2007/09/03 16:44:28  fplanque
 * chicago admin skin
 *
 * Revision 1.2  2007/07/01 18:47:11  fplanque
 * fixes
 *
 * Revision 1.1  2007/06/25 11:01:21  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.33  2007/05/26 21:30:54  blueyed
 * Allow spaces in filenames by default
 *
 * Revision 1.32  2007/04/26 00:11:02  fplanque
 * (c) 2007
 *
 * Revision 1.31  2007/04/10 17:55:09  fplanque
 * minor
 *
 * Revision 1.30  2007/03/25 13:20:52  fplanque
 * cleaned up blog base urls
 * needs extensive testing...
 *
 * Revision 1.29  2007/03/24 20:41:16  fplanque
 * Refactored a lot of the link junk.
 * Made options blog specific.
 * Some junk still needs to be cleaned out. Will do asap.
 *
 * Revision 1.28  2006/12/15 22:54:14  fplanque
 * allow disabling of password hashing
 *
 * Revision 1.27  2006/12/11 00:32:26  fplanque
 * allow_moving_chapters stting moved to UI
 * chapters are now called categories in the UI
 *
 * Revision 1.26  2006/12/07 15:23:42  fplanque
 * filemanager enhanced, refactored, extended to skins directory
 *
 * Revision 1.25  2006/12/07 00:55:52  fplanque
 * reorganized some settings
 *
 * Revision 1.24  2006/12/04 19:41:11  fplanque
 * Each blog can now have its own "archive mode" settings
 *
 * Revision 1.23  2006/12/04 18:16:50  fplanque
 * Each blog can now have its own "number of page/days to display" settings
 *
 * Revision 1.22  2006/11/24 18:27:25  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.21  2006/11/09 23:11:44  blueyed
 * Made chmod settings editable
 *
 * Revision 1.20  2006/10/06 21:03:06  blueyed
 * Removed deprecated/unused "upload_allowedext" Setting, which restricted file extensions during upload though!
 */
?>
