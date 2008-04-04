<?php
/**
 * This file implements the GeneralSettings class, which handles Name/Value pairs.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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

load_class('settings/model/_abstractsettings.class.php');

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

		'AutoBR' => '0',			// Used for email blogging. fp> TODO: should be replaced by "email renderers/decoders/cleaners"...

		'log_public_hits' => '1',
		'log_admin_hits' => '0',
		'log_spam_hits' => '0',
		'auto_prune_stats_mode' => 'page',  // 'page' is the safest mode for average installs (may be "off", "page" or "cron")
		'auto_prune_stats' => '15',         // days (T_hitlog and T_sessions)

		'outbound_notifications_mode' => 'immediate', // 'immediate' is the safest mode for average installs (may be "off", "immediate" or "cron")

		'fm_enabled' => '1',
		'fm_enable_create_dir' => '1',
		'fm_enable_create_file' => '1',
		'fm_enable_roots_blog' => '1',
		// 'fm_enable_roots_group' => '0',  // TO DO
		'fm_enable_roots_user' => '1',
		'fm_enable_roots_skins' => '1',

		'fm_showtypes' => '0',
		'fm_showfsperms' => '0',

		'fm_default_chmod_file' => '664',
		'fm_default_chmod_dir' => '775',

		'newusers_canregister' => '0',
		'newusers_mustvalidate' => '1',
		'newusers_revalidate_emailchg' => '0',
		'newusers_level' => '1',

		'regexp_filename' => '^[a-zA-Z0-9\-_. ]+$', // TODO: accept (spaces and) special chars / do full testing on this
		'regexp_dirname' => '^[a-zA-Z0-9\-_]+$', // TODO: accept spaces and special chars / do full testing on this
		'reloadpage_timeout' => '300',
		'time_difference' => '0',
		'timeout_sessions' => '604800',     // seconds (604800 == 7 days)
		'upload_enabled' => '1',
		'upload_maxkb' => '2048',

		'user_minpwdlen' => '5',
		'js_passwd_hashing' => '1',					// Use JS password hashing by default

		'webhelp_enabled' => '1',

		'allow_moving_chapters' => '0',				// Do not allow moving chapters by default
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
		global $new_db_version, $DB;

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
	}

}



/*
 * $Log$
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