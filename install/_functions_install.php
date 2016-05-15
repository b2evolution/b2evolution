<?php
/**
 * This file implements support functions for the installer
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package install
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Open a block
 *
 * @param string Block title
 */
function block_open( $title = '' )
{
	global $block_status;
	if( isset( $block_status ) && $block_status == 'open' )
	{
		return;
	}

	$block_status = 'open';

	echo "\n".'<div class="panel panel-default">'."\n";
	if( $title != '' )
	{ // Display a title
		echo '<div class="panel-heading">'.$title.'</div>'."\n";
	}
	echo '<div class="panel-body">'."\n";
}

/**
 * Close a block
 */
function block_close()
{
	global $block_status;
	if( ! isset( $block_status ) || $block_status == 'closed' )
	{
		return;
	}
	$block_status = 'closed';
	echo '</div></div>'."\n\n";
}

/**
 * Language selector
 */
function display_locale_selector()
{
	global $locales, $default_locale, $action;

	static $selector_already_displayed = false;

	if( $selector_already_displayed )
	{
		return;
	}
	$selector_already_displayed = true;

	block_open( T_('Language / Locale') );
	?>
	<ul class="pager pull-right" style="margin:0">
		<li class="next"><a href="index.php?action=localeinfo&amp;locale=<?php echo $default_locale; ?>">More languages <span aria-hidden="true">&rarr;</span></a></li>
	</ul>

	<?php
	if( isset( $locales[ $default_locale ] ) )
	{
		$default_locale_option_title = locale_flag( $default_locale, 'w16px', 'flag', '', false ).' '.$locales[ $default_locale ]['name'];
	}
	$locale_options = '';
	foreach( $locales as $lkey => $lvalue )
	{
		$locale_options .= '<li><a href="index.php?locale='.$lkey.'">'.locale_flag( $lkey, 'w16px', 'flag', '', false ).' '.T_( $lvalue['name'] ).'</a></li>'."\n";
	}
	?>
	<div class="btn-group install-language">
		<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
			<?php echo $default_locale_option_title; ?>
			<span class="caret"></span>
		</button>
		<ul class="dropdown-menu" role="menu">
		<?php echo $locale_options; ?>
		</ul>
	</div>

	<noscript>
		<style type="text/css">.install-language{display:none;}</style>
		<form action="index.php" method="get" class="form-inline">
		<select name="locale" class="form-control">
		<?php
		foreach( $locales as $lkey => $lvalue )
		{
			echo '<option'.( $default_locale == $lkey ? ' selected="selected"' : '' ).' value="'.$lkey.'">';
			echo T_( $lvalue['name'] );
			echo '</option>';
		}
		?>
		</select>
		<input type="submit" value="<?php echo T_('Select as default language/locale'); ?>" class="btn btn-primary" />
		</form>
	</noscript>
	<?php
	block_close();
}

/**
 * Base config recap
 */
function display_base_config_recap()
{
	global $default_locale, $conf_db_user, $conf_db_password, $conf_db_name, $conf_db_host, $db_config, $tableprefix, $baseurl, $admin_email;

	static $base_config_recap_already_displayed = false;

	if( $base_config_recap_already_displayed )
	{
		return;
	}
	$base_config_recap_already_displayed = true;

	echo '<br />';
	block_open( T_('Base config recap...') );
	?>
	<p><?php printf( T_('If you don\'t see correct settings here, STOP before going any further, and <a %s>update your base configuration</a>.'), 'href="index.php?action=start&amp;locale='.$default_locale.'"' ) ?></p>

	<?php
	if( !isset($conf_db_user) ) $conf_db_user = $db_config['user'];
	if( !isset($conf_db_password) ) $conf_db_password = $db_config['password'];
	if( !isset($conf_db_name) ) $conf_db_name = $db_config['name'];
	if( !isset($conf_db_host) ) $conf_db_host = isset($db_config['host']) ? $db_config['host'] : 'localhost';

	echo '<samp>'.
	T_('MySQL Username').': '.$conf_db_user.'<br />'.
	T_('MySQL Password').': '.(($conf_db_password != 'demopass' ? T_('(Set, but not shown for security reasons)') : 'demopass') ).'<br />'.
	T_('MySQL Database name').': '.$conf_db_name.'<br />'.
	T_('MySQL Host/Server').': '.$conf_db_host.'<br />'.
	T_('MySQL tables prefix').': '.$tableprefix.'<br /><br />'.
	T_('Base URL').': '.$baseurl.'<br /><br />'.
	T_('Admin email').': '.$admin_email.
	'</samp>';

	block_close();
}


/**
 * Install new DB.
 */
function install_newdb()
{
	global $new_db_version, $admin_url, $baseurl, $install_login, $random_password, $create_sample_contents;

	/*
	 * -----------------------------------------------------------------------------------
	 * NEW DB: Create a plain new db structure + sample contents
	 * -----------------------------------------------------------------------------------
	 */
	require_once dirname(__FILE__).'/_functions_create.php';

	if( $old_db_version = get_db_version() )
	{
		echo get_install_format_text( '<p class="text-warning"><strong><evo:warning>'.T_('OOPS! It seems b2evolution is already installed!').'</evo:warning></strong></p>', 'p' );

		if( $old_db_version < $new_db_version )
		{
			echo get_install_format_text( '<p>'.sprintf( T_('Would you like to <a %s>upgrade your existing installation now</a>?'), 'href="?action=evoupgrade"' ).'</p>', 'p' );
		}

		return;
	}

	/**
	 * 1 - If current installation is local, test or intranet
	 *     Used to turn off gravatar and all ping plugins
	 *
	 * @var integer
	 */
	$local_installation = param( 'local_installation', 'integer', ( $create_sample_contents == 'all' ? intval( check_local_installation() ) : 0 ) );

	echo get_install_format_text( '<h2>'.T_('Creating b2evolution tables...').'</h2>', 'h2' );
	evo_flush();
	create_tables();

	// Update the progress bar status
	update_install_progress_bar();

	echo get_install_format_text( '<h2>'.T_('Creating minimum default data...').'</h2>', 'h2' );
	evo_flush();
	create_default_data();

	if( $create_sample_contents )
	{
		global $Settings, $install_test_features;

		echo get_install_format_text( '<h2>'.T_('Installing sample contents...').'</h2>', 'h2' );
		evo_flush();

		// We're gonna need some environment in order to create the demo contents...
		load_class( 'settings/model/_generalsettings.class.php', 'GeneralSettings' );
		load_class( 'users/model/_usersettings.class.php', 'UserSettings' );
		/**
		 * @var GeneralSettings
		 */
		$Settings = new GeneralSettings();

		/**
		 * @var UserCache
		 */
		$UserCache = & get_UserCache();
		// Create $current_User object.
		// (Assigning by reference does not work with "global" keyword (PHP 5.2.8))
		$GLOBALS['current_User'] = & $UserCache->get_by_ID( 1 );

		create_demo_contents();
	}

	evo_flush();
	create_default_email_campaigns();

	// Update the progress bar status
	update_install_progress_bar();

	track_step( 'install-success' );

	$install_result_title = T_('Installation successful!');
	$install_result_body = get_install_format_text(
		'<p><strong>'
			.sprintf( T_('Now you can <a %s>log in</a> with the following credentials:'), 'href="'.$admin_url.'"' )
		.'</strong></p>', 'p' )
		.get_install_format_text(
		'<table>'
			.'<tr><td>'.T_( 'Login' ).': &nbsp;</td><td><strong><evo:login>'.( isset( $install_login ) ? $install_login : 'admin' ).'</evo:login></strong></td></tr>', 'br' )
		.get_install_format_text(
			'<tr><td>'.T_( 'Password' ).': &nbsp;</td><td><strong><evo:password>'.$random_password.'</evo:password></strong></td></tr>'
		.'</table>', 'br' )
		.get_install_format_text(
		'<br /><p>'.T_('Note that password carefully! It is a <em>random</em> password that is given to you when you install b2evolution. If you lose it, you will have to delete the database tables and re-install anew.').'</p>', 'p' );

	// Display installation data and instructions
	echo get_install_format_text( '<h2>'.$install_result_title.'</h2>', 'h2' );
	echo $install_result_body;

	// Modal window with installation data and instructions
	display_install_result_window( $install_result_title, $install_result_body );
}


/**
 * Begin install task.
 * This will offer other display methods in the future
 */
function task_begin( $title )
{
	echo get_install_format_text( $title."\n" );
	evo_flush();
}


/**
 * End install task.
 * This will offer other display methods in the future
 */
function task_end( $message = 'OK.' )
{
	echo get_install_format_text( $message."<br />\n", 'br' );
}


function get_db_version()
{
	global $DB;

	$DB->save_error_state();
	$DB->halt_on_error = false;
	$DB->show_errors = false;
	$DB->log_errors = false;

	$r = NULL;

	if( db_col_exists( 'T_settings', 'set_name' ) )
	{ // we have new table format (since 0.9)
		$r = $DB->get_var( 'SELECT set_value FROM T_settings WHERE set_name = "db_version"' );
	}
	else
	{
		$r = $DB->get_var( 'SELECT db_version FROM T_settings' );
	}

	$DB->restore_error_state();

	return $r;
}


/**
 * Get default locale from db
 */
function get_default_locale_from_db()
{
	global $DB;

	if( empty( $DB ) )
	{ // DB doesn't exists yet
		return NULL;
	}

	$DB->save_error_state();
	$DB->halt_on_error = false;
	$DB->show_errors = false;
	$DB->log_errors = false;

	$r = NULL;

	if( db_col_exists( 'T_settings', 'set_name' ) )
	{
		$r = $DB->get_var( 'SELECT set_value FROM T_settings WHERE set_name = "default_locale"' );
	}

	$DB->restore_error_state();

	return $r;
}


/**
 * @return boolean Does a given column name exist in DB?
 */
function db_col_exists( $table, $col_name )
{
	global $DB;

	$col_name = strtolower($col_name);

	$r = false;
	$DB->save_error_state();
	foreach( $DB->get_results('SHOW COLUMNS FROM '.$table) as $row )
	{
		if( strtolower($row->Field) == $col_name )
		{
			$r = true;
			break;
		}
	}
	$DB->restore_error_state();

	return $r;
}


/**
 * Clean up extra quotes in comments
 */
function cleanup_comment_quotes()
{
	global $DB;

	task_begin( 'Checking for extra quote escaping in comments... ' );
	$query = "SELECT comment_ID, comment_content
							FROM T_comments
						 WHERE comment_content LIKE '%\\\\\\\\\'%'
						 		OR comment_content LIKE '%\\\\\\\\\"%' ";
	/* FP: the above looks overkill, but MySQL is really full of surprises...
					tested on 4.0.14-nt */
	// echo $query;
	$rows = $DB->get_results( $query, ARRAY_A );
	if( $DB->num_rows )
	{
		echo 'Updating '.$DB->num_rows.' comments... ';
		foreach( $rows as $row )
		{
			$query = "UPDATE T_comments
								SET comment_content = ".$DB->quote( stripslashes( $row['comment_content'] ) )."
								WHERE comment_ID = ".$row['comment_ID'];
			// echo '<br />'.$query;
			$DB->query( $query );
		}
	}
	task_end();

}


/**
 * Validate install requirements.
 *
 * @return array List of errors, empty array if ok.
 */
function install_validate_requirements()
{
	$errors = array();

	return $errors;
}


/**
 * Insert default locales into T_locales.
 */
function create_default_locales()
{
	task_begin( 'Activating default locales... ' );
	locale_insert_default();
	task_end();
}


/**
 * Insert default settings into T_settings.
 *
 * It only writes those to DB, that get overridden (passed as array), or have
 * no default in {@link _generalsettings.class.php} / {@link GeneralSettings::default}.
 *
 * @param array associative array (settings name => value to use), allows
 *              overriding of defaults
 */
function create_default_settings( $override = array() )
{
	global $DB, $new_db_version, $default_locale;
	global $admins_Group, $moderators_Group, $editors_Group, $users_Group, $suspect_Group, $spam_Group;
	global $install_test_features, $create_sample_contents, $install_site_color, $local_installation;

	$defaults = array(
		'db_version' => $new_db_version,
		'default_locale' => $default_locale,
		'newusers_grp_ID' => $users_Group->ID,
		'evocache_foldername' => '_evocache',
		'newusers_canregister' => 'yes',
		'registration_is_public' => 1,
		'quick_registration' => 1,
	);
	if( $install_test_features )
	{
		$defaults['gender_colored'] = 1;
		echo_install_log( 'TEST FEATURE: Enabling colored gender usernames by default' );
		$defaults['registration_require_country'] = 1;
		$defaults['registration_require_gender'] = 'required';
		echo_install_log( 'TEST FEATURE: Making country and gender required on registration' );
		$defaults['location_country'] = 'required';
		$defaults['location_region'] = 'required';
		$defaults['location_subregion'] = 'required';
		$defaults['location_city'] = 'required';
		echo_install_log( 'TEST FEATURE: Making country, region, sub-region and city required by default' );
	}
	if( !empty( $install_site_color ) )
	{ // Set default site color
		$defaults['site_color'] = $install_site_color;
	}
	if( !empty( $suspect_Group ) )
	{ // Set default antispam suspicious group
		$defaults['antispam_suspicious_group'] = $suspect_Group->ID;
	}
	$antispam_trust_groups = array();
	if( !empty( $admins_Group ) )
	{
		$antispam_trust_groups[] = $admins_Group->ID;
	}
	if( !empty( $moderators_Group ) )
	{
		$antispam_trust_groups[] = $moderators_Group->ID;
	}
	if( !empty( $editors_Group ) )
	{
		$antispam_trust_groups[] = $editors_Group->ID;
	}
	if( !empty( $spam_Group ) )
	{
		$antispam_trust_groups[] = $spam_Group->ID;
	}
	if( count( $antispam_trust_groups ) > 0 )
	{ // Set default antispam trust group
		$defaults['antispam_trust_groups'] = implode( ',', $antispam_trust_groups );
	}
	if( $local_installation )
	{ // Current installation is local
		// Turn off gravatar and use 'Default gravatars' = 'Gravatar'
		$defaults['use_gravatar'] = 0;
		$defaults['default_gravatar'] = '';
	}

	$settings = array_merge( array_keys($defaults), array_keys($override) );
	$settings = array_unique( $settings );
	$insertvalues = array();
	foreach( $settings as $name )
	{
		if( isset($override[$name]) )
		{
			$insertvalues[] = '('.$DB->quote($name).', '.$DB->quote($override[$name]).')';
		}
		else
		{
			$insertvalues[] = '('.$DB->quote($name).', '.$DB->quote($defaults[$name]).')';
		}
	}

	task_begin( 'Creating default settings'.( count($override) ? ' (with '.count($override).' existing values)' : '' ).'... ' );
	$DB->query(
		"INSERT INTO T_settings (set_name, set_value)
		VALUES ".implode( ', ', $insertvalues ) );
	task_end();
}


/**
 * Install basic skins.
 */
function install_basic_skins( $install_mobile_skins = true )
{
	load_funcs( 'skins/_skin.funcs.php' );

	task_begin( 'Installing default skins... ' );

	// Note: Skin #1 will we used by Blog A and Blog B
	// Install this skin firstly in order to use it by default for all collections with wrong skin ID on upgrade process:
	skin_install( 'bootstrap_blog_skin' );

	// Note: Skin #2 will we used by Home
	skin_install( 'bootstrap_main_skin' );

	// Note: Skin #3 will we used by Photos
	skin_install( 'bootstrap_gallery_skin' );

	// Note: Skin #4 will we used by Forums
	skin_install( 'bootstrap_forums_skin' );

	// Note: Skin #5 will we used by Manual
	skin_install( 'bootstrap_manual_skin' );

	// skin_install( 'asevo' );
	// skin_install( 'dating_mood' );
	// skin_install( 'evopress' );
	// skin_install( 'photoalbums' );
	// skin_install( 'photoblog' );
	// skin_install( 'pureforums' );
	if( $install_mobile_skins )
	{
		skin_install( 'touch' );
	}
	skin_install( '_atom' );
	skin_install( '_rss2' );

	task_end();
}


/**
 * Install basic plugins.
 *
 * This gets called separately on fresh installs.
 *
 * {@internal
 * NOTE: this won't call the "AfterInstall" method on the plugin nor install its DB schema.
 *       This get done in the plugins controller, on manually installing a plugin.
 *
 * If you change the plugins here, please also adjust {@link InstallUnitTestCase::basic_plugins}.
 * }}
 *
 * @param integer Old DB version, so that only new plugins gets installed
 */
function install_basic_plugins( $old_db_version = 0 )
{
	/**
	 * @var Plugins_admin
	 */
	global $Plugins_admin, $install_test_features;

	$Plugins_admin = & get_Plugins_admin();

	// Create global $Plugins instance, which is required during installation of basic plugins,
	// not only for the ones getting installed, but also during e.g. count_regs(), which instantiates
	// each plugin (which may then use (User)Settings in PluginInit (through Plugin::__get)).
	$GLOBALS['Plugins'] = & $Plugins_admin;

	if( $old_db_version < 9100 )
	{
		// Toolbars:
		install_plugin( 'quicktags_plugin' );
		// Renderers:
		install_plugin( 'auto_p_plugin' );
		install_plugin( 'autolinks_plugin' );
		install_plugin( 'texturize_plugin' );

		// SkinTags:
		install_plugin( 'calendar_plugin' );
		install_plugin( 'archives_plugin' );
	}

	if( $old_db_version < 9290 )
	{
		if( $install_test_features )
		{
			echo_install_log( 'TEST FEATURE: Installing plugin "Smilies"' );
			install_plugin( 'smilies_plugin' );
		}
		install_plugin( 'videoplug_plugin' );
	}

	if( $old_db_version < 9330 )
	{ // Upgrade to 1.9-beta
		install_plugin( 'ping_b2evonet_plugin' );
		install_plugin( 'ping_pingomatic_plugin' );
	}

	if( $old_db_version < 9930 )
	{ // Upgrade to 3.1.0
		install_plugin( 'tinymce_plugin' );
	}

	if( $old_db_version < 9940 )
	{ // Upgrade to 3.2.0
		install_plugin( 'twitter_plugin' );
	}

	if( $old_db_version < 10300 )
	{ // Upgrade to 5.0.0
		install_plugin( 'flowplayer_plugin' );

		if( $install_test_features )
		{
			echo_install_log( 'TEST FEATURE: Installing plugin "Google Maps"' );
			install_plugin( 'google_maps_plugin' );
		}
	}

	if( $old_db_version < 11000 )
	{ // Upgrade to 5.0.0-alpha-4
		if( $install_test_features )
		{
			$captcha_qstn_plugin_settings = array(
					'questions' => T_('What is the color of the sky? blue|grey|gray|dark')."\r\n".
												 T_('What animal is Bugs Bunny? rabbit|a rabbit')."\r\n".
												 T_('What color is a carrot? orange|yellow')."\r\n".
												 T_('What color is a tomato? red')
				);
			echo_install_log( 'TEST FEATURE: Creating sample questions for plugin "Captcha questions"' );
		}
		else
		{
			$captcha_qstn_plugin_settings = array();
		}
		install_plugin( 'captcha_qstn_plugin', true, $captcha_qstn_plugin_settings );
	}

	if( $old_db_version < 11100 )
	{ // Upgrade to 5.0.0-alpha-5
		// antispam
		install_plugin( 'basic_antispam_plugin' );
		install_plugin( 'geoip_plugin' );
		// files
		install_plugin( 'html5_mediaelementjs_plugin' );
		install_plugin( 'html5_videojs_plugin' );
		install_plugin( 'watermark_plugin', $install_test_features );
		if( $install_test_features )
		{
			echo_install_log( 'TEST FEATURE: Activating plugin "Watermark"' );
		}
		// ping
		install_plugin( 'generic_ping_plugin' );
		// rendering
		install_plugin( 'escapecode_plugin' );
		install_plugin( 'bbcode_plugin', $install_test_features );
		if( $install_test_features )
		{
			echo_install_log( 'TEST FEATURE: Activating plugin "BB code"' );
		}
		install_plugin( 'star_plugin', $install_test_features );
		if( $install_test_features )
		{
			echo_install_log( 'TEST FEATURE: Activating plugin "Star renderer"' );
		}
		install_plugin( 'prism_plugin', $install_test_features );
		if( $install_test_features )
		{
			echo_install_log( 'TEST FEATURE: Activating plugin "Prism"' );
		}
		install_plugin( 'code_highlight_plugin', $install_test_features );
		if( $install_test_features )
		{
			echo_install_log( 'TEST FEATURE: Activating plugin "Code highlight"' );
		}
		install_plugin( 'gmcode_plugin' );
		install_plugin( 'wacko_plugin' );
		install_plugin( 'shortlinks_plugin' );
		install_plugin( 'wikitables_plugin' );
		install_plugin( 'markdown_plugin' );
		install_plugin( 'infodots_plugin', $install_test_features );
		if( $install_test_features )
		{
			echo_install_log( 'TEST FEATURE: Activating plugin "Info dots renderer"' );
		}
		install_plugin( 'widescroll_plugin' );
		// widget
		install_plugin( 'facebook_plugin' );
		install_plugin( 'whosonline_plugin' );
		// Unclassified
		install_plugin( 'bookmarklet_plugin' );
	}

	if( $old_db_version < 11200 )
	{ // Upgrade to 5.1.3-stable
		install_plugin( 'shortcodes_plugin' );
	}

	if( $old_db_version < 11490 )
	{ // Upgrade to 6.7.1-stable
		install_plugin( 'adjust_headings_plugin', $install_test_features );
		if( $install_test_features )
		{
			echo_install_log( 'TEST FEATURE: Activating plugin "Adjust headings"' );
		}
		install_plugin( 'cookie_consent_plugin', false );
	}
}


/**
 * Install plugin
 *
 * @param string Plugin name
 * @param boolean TRUE - to activate plugin
 * @param array Plugin settings
 * @return true on success
 */
function install_plugin( $plugin, $activate = true, $settings = array() )
{
	/**
	 * @var Plugins_admin
	 */
	global $Plugins_admin;

	task_begin( 'Installing plugin: '.$plugin.'... ' );
	$edit_Plugin = & $Plugins_admin->install( $plugin, 'broken' ); // "broken" by default, gets adjusted later
	if( ! ( $edit_Plugin instanceof Plugin ) )
	{ // Broken plugin
		echo get_install_format_text( '<span class="text-danger"><evo:error>'.$edit_Plugin.'</evo:error></span><br />'."\n", 'br' );
		return false;
	}

	load_funcs('plugins/_plugin.funcs.php');
	install_plugin_db_schema_action( $edit_Plugin, true );

	if( ! empty( $settings ) )
	{ // Set plugin settings
		foreach( $settings as $setting_name => $setting_value )
		{
			$edit_Plugin->Settings->set( $setting_name, $setting_value );
		}
		$edit_Plugin->Settings->dbupdate();
	}

	if( $activate )
	{ // Try to enable plugin:
		$enable_return = $edit_Plugin->BeforeEnable();
		if( $enable_return !== true )
		{ // Warning on enable a plugin
			echo get_install_format_text( '<span class="text-warning"><evo:warning>'.$enable_return.'</evo:warning></span><br />'."\n", 'br' );

			// Set plugin status to "needs_config" to mark the plugin as incomplete for using:
			$Plugins_admin->set_Plugin_status( $edit_Plugin, 'needs_config' );

			return false;
		}

		$Plugins_admin->set_Plugin_status( $edit_Plugin, 'enabled' );
	}
	else
	{ // Set plugin status as disable
		$Plugins_admin->set_Plugin_status( $edit_Plugin, 'disabled' );
	}

	task_end();
	return true;
}


/**
 * Install basic widgets.
 */
function install_basic_widgets( $old_db_version = 0 )
{
	/**
	* @var DB
	*/
	global $DB;

	load_funcs( 'widgets/_widgets.funcs.php' );

	if( $old_db_version < 11010 )
	{
		$blog_ids = $DB->get_assoc( 'SELECT blog_ID, "std" FROM T_blogs' );
	}
	else
	{
		$blog_ids = $DB->get_assoc( 'SELECT blog_ID, blog_type FROM T_blogs' );
	}

	foreach( $blog_ids as $blog_id => $blog_type )
	{
		task_begin( 'Installing default widgets for blog #'.$blog_id.'... ' );
		insert_basic_widgets( $blog_id, true, $blog_type );
		task_end();
	}

}



function advanced_properties()
{
	/*
// file_path needs to be case sensitive on unix
// Note: it should be ok on windows too if we take care of updating the db on case renames
ALTER TABLE `T_files` CHANGE `file_path` `file_path` VARCHAR( 255 ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL
or
ALTER TABLE `T_files` CHANGE `file_path` `file_path` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
	*/
}


/**
 * Create relations
 *
 * @todo NOT UP TO DATE AT ALL :( -- update field names before activating this
 */
function create_relations()
{
	global $DB;

	task_begin( 'Creating relations... ' );

	$DB->query( 'alter table T_coll_user_perms
								add constraint FK_bloguser_blog_ID
											foreign key (bloguser_blog_ID)
											references T_blogs (blog_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_coll_user_perms
								add constraint FK_bloguser_user_ID
											foreign key (bloguser_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_categories
								add constraint FK_cat_blog_ID
											foreign key (cat_blog_ID)
											references T_blogs (blog_ID)
											on delete restrict
											on update restrict,
								add constraint FK_cat_parent_ID
											foreign key (cat_parent_ID)
											references T_categories (cat_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_comments
								add constraint FK_comment_item_ID
											foreign key (comment_item_ID)
											references T_items__item (post_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_postcats
								add constraint FK_postcat_cat_ID
											foreign key (postcat_cat_ID)
											references T_categories (cat_ID)
											on delete restrict
											on update restrict,
								add constraint FK_postcat_post_ID
											foreign key (postcat_post_ID)
											references T_items__item (post_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_items__item
								add constraint FK_post_assigned_user_ID
											foreign key (post_assigned_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict,
								add constraint FK_post_lastedit_user_ID
											foreign key (post_lastedit_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict,
								add constraint FK_post_creator_user_ID
											foreign key (post_creator_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict,
								add constraint FK_post_main_cat_ID
											foreign key (post_main_cat_ID)
											references T_categories (cat_ID)
											on delete restrict
											on update restrict,
								add constraint FK_post_parent_ID
											foreign key (post_parent_ID)
											references T_items__item (post_ID)
											on delete restrict
											on update restrict,
								add constraint FK_post_pst_ID
											foreign key (post_pst_ID)
											references T_items__status (pst_ID)
											on delete restrict
											on update restrict,
								add constraint FK_post_ityp_ID
											foreign key (post_ityp_ID)
											references T_items__type (ityp_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_links
								add constraint FK_link_creator_user_ID
											foreign key (link_creator_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict' );
	$DB->query( 'alter table T_links
								add constraint FK_link_lastedit_user_ID
											foreign key (link_lastedit_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict' );
	$DB->query( 'alter table T_links
								add constraint FK_link_file_ID
											foreign key (link_file_ID)
											references T_files (file_ID)
											on delete restrict
											on update restrict' );
	$DB->query( 'alter table T_links
								add constraint FK_link_itm_ID
											foreign key (link_itm_ID)
											references T_items__item (post_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_pluginsettings
	              add constraint FK_pset_plug_ID
	                    foreign key (pset_plug_ID)
	                    references T_plugins (plug_ID)
	                    on delete restrict
	                    on update restrict' );

	$DB->query( 'alter table T_pluginusersettings
	              add constraint FK_puset_plug_ID
	                    foreign key (puset_plug_ID)
	                    references T_plugins (plug_ID)
	                    on delete restrict
	                    on update restrict' );

	$DB->query( 'alter table T_pluginevents
	              add constraint FK_pevt_plug_ID
	                    foreign key (pevt_plug_ID)
	                    references T_plugins (plug_ID)
	                    on delete restrict
	                    on update restrict' );

	$DB->query( 'alter table T_users
								add constraint FK_user_grp_ID
											foreign key (user_grp_ID)
											references T_groups (grp_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_users__usersettings
								add constraint FK_uset_user_ID
											foreign key (uset_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_subscriptions
								add constraint FK_sub_coll_ID
											foreign key (sub_coll_ID)
											references T_blogs (blog_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_subscriptions
								add constraint FK_sub_user_ID
											foreign key (sub_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_slug
								add constraint FK_slug_itm_ID
											foreign key (slug_itm_ID)
											references T_items__item (post_ID)
											on delete restrict
											on update restrict' );

	task_end();
}


/**
 * Install htaccess: Check if it works with the webserver, then install it for real.
 *
 * @param boolean are we upgrading (vs installing)?
 * @param boolean Force copying htaccess
 * @return boolean TRUE if no errors
 */
function install_htaccess( $upgrade = false, $force_htaccess = false )
{
	echo get_install_format_text( '<p>'.T_('Preparing to install <code>/.htaccess</code> in the base folder...').' (Force='.($force_htaccess?'yes':'no').')<br />', 'p-start-br' );

	if( ! $force_htaccess )
	{	// Check if we run apache...
		$server = isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : '';
		if( ! empty( $server ) && preg_match( '~(Nginx|Lighttpd|Microsoft-IIS)~i', $server ) )
		{ // Skip installation if this is not an Apache server
			echo get_install_format_text( '<br /><b class="text-warning"><evo:warning>'.T_('.htaccess is not needed because your web server is not Apache. WARNING: you will need to configure your web server manually.').'</evo:warning></b></p>', 'p-end' );
			return true;
		}
	}

	$error_message = do_install_htaccess( $upgrade, $force_htaccess );

	if( $error_message )
	{

		echo get_install_format_text( '<span class="text-danger"><evo:error>'.T_('ERROR!').'<br /><b>'.$error_message.'</b></evo:error></span>' );

		// Do we want to ignore the error?
		$htignore = param( 'htignore', 'integer', 0 );
		if( $htignore )
		{ // Ignore error with .htaccess file and continue install...
			return true;
		}
		else
		{ // Some errors are existing with .htaccess file, Display a link to ignore the errors and continue instalation
			echo get_install_format_text( '<ul class="pager"><li><a href="'.( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '' ).'&htignore=1" style="font-size:150%;font-weight:bold;">'.T_('Continue installation').' <span aria-hidden="true">&rarr;</span></a></li></ul>', 'li' );
			return false;
		}
	}
	echo get_install_format_text( '</p>', 'p-end' );

	return true;
}

/**
 * This does the actual file manipulations for installing .htaccess
 * This will verify that the provided sample.htaccess does not crash apache in a test folder before installing it for real.
 *
 * @param boolean are we upgrading (vs installing)?
 * @param boolean Force copying htaccess
 * @return mixed
 */
function do_install_htaccess( $upgrade = false, $force_htaccess = false )
{
	global $baseurl;
	global $basepath;

	// Make sure we have a sample file to start with:
	if( ! @file_exists( $basepath.'sample.htaccess' ) )
	{
		return T_('Cannot find file [ sample.htaccess ] in your base url folder.');
	}

	if( !$force_htaccess )
	{ // We're not forcing install, we want to check if it's supported:

		if( @file_exists( $basepath.'.htaccess' ) )
		{
			if( @file_exists( $basepath.'sample.htaccess' ) )
			{
				$content_htaccess = trim( file_get_contents( $basepath.'.htaccess' ) );
				$content_sample_htaccess = trim( file_get_contents( $basepath.'sample.htaccess' ) );

				if( $content_htaccess != $content_sample_htaccess )
				{ // The .htaccess file has content that different from a sample file
					if( $upgrade )
					{
						echo get_install_format_text( '<span class="text-warning"><evo:warning>'.T_('<code>.htaccess</code> is already installed BUT DOES NOT match <code>sample.htaccess</code>. Please check the differences manually.').'</evo:warning></span>' );
						return '';
					}
					else
					{
						echo get_install_format_text( '<p class="text-danger"><evo:error>'.T_('There is already a file called .htaccess at the blog root. If you don\'t specifically need this file, it is recommended that you delete it or rename it to old.htaccess before you continue. This will allow b2evolution to create a new .htaccess file that is optimized for best results.').'</evo:error></p>', 'p' );
						echo T_('Here are the contents of the current .htaccess file:');
						echo get_install_format_text( '<div style="overflow:auto"><pre>'.htmlspecialchars( $content_htaccess ).'</pre></div><br />', 'code' );
						return get_install_format_text( sprintf( T_('Again, we recommend you remove this file before continuing. If you chose to keep it, b2evolution will probably still work, but for optimization you should follow <a %s>these instructions</a>.'), 'href="'.get_manual_url( 'htaccess-file' ).'" target="_blank"' ) );
					}
				}
				else
				{	// Installed file is the same as recommended:
					echo get_install_format_text( '<span class="text-info">'.T_('<code>.htaccess</code> is already installed and matches <code>sample.htaccess</code>.').'</span>' );
					return '';
				}
			}
		}

		// Try to copy that file to the test folder:
		if( ! @copy( $basepath.'sample.htaccess', $basepath.'install/test/.htaccess' ) )
		{
			return T_('Failed to copy sample.htaccess to test folder!');
		}

		// Make sure .htaccess does not crash in the test folder:
		load_funcs('_core/_url.funcs.php');
		$fetch_test_url = $baseurl.'install/test/';
		$info = array();
		echo get_install_format_text( T_('Verifying .htaccess support works by fetching:').' <code>'.$fetch_test_url.'</code>... ' );
		if( ! $remote_page = fetch_remote_page( $fetch_test_url, $info ) )
		{
			return $info['error'];
		}
		if( substr( $remote_page, 0, 16 ) != 'Test successful.' )
		{
			return sprintf( T_('%s was not found as expected.'), $baseurl.'install/test/index.html' );
		}
	}

	// Now we consider it's safe, copy .htaccess to its real location:
	if( ! @copy( $basepath.'sample.htaccess', $basepath.'.htaccess' ) )
	{
		return T_('Test was successful, but failed to copy .htaccess into baseurl directory!');
	}

	echo get_install_format_text( '<span class="text-success"><evo:success>'.T_('.htaccess installation successful!').'</evo:success></span>' );
	return '';
}


/**
 * Return antispam SQL query.
 * This is obfuscated because some hosting companies prevent uploading PHP files
 * containing "spam" strings.
 *
 * @return string;
 */
function get_antispam_query()
{
	//used base64_encode() for getting this code
	$r = base64_decode('SU5TRVJUIElOVE8gVF9hbnRpc3BhbShhc3BtX3N0cmluZykgVkFMVUVTICgnb25saW5lLWNhc2lubycpLCAoJ3BlbmlzLWVubGFyZ2VtZW50JyksICgnb3JkZXItdmlhZ3JhJyksICgnb3JkZXItcGhlbnRlcm1pbmUnKSwgKCdvcmRlci14ZW5pY2FsJyksICgnb3JkZXItcHJvcGhlY2lhJyksICgnc2V4eS1saW5nZXJpZScpLCAoJy1wb3JuLScpLCAoJy1hZHVsdC0nKSwgKCctdGl0cy0nKSwgKCdidXktcGhlbnRlcm1pbmUnKSwgKCdvcmRlci1jaGVhcC1waWxscycpLCAoJ2J1eS14ZW5hZHJpbmUnKSwgKCdwYXJpcy1oaWx0b24nKSwgKCdwYXJpc2hpbHRvbicpLCAoJ2NhbWdpcmxzJyksICgnYWR1bHQtbW9kZWxzJyk=');
	// pre_dump($r);
	return $r;
}

/**
 * We use the following tracking to determine the installer reliability (% of failed vs successful installs).
 */
function track_step( $current_step )
{
	global $display;

	if( ! empty( $display ) && $display == 'cli' )
	{	// Don't display HTML on CLI mode:
		return;
	}

	// echo 'Tracking '.$current_step;
	echo '<div style="display:none">'
			.'<img src="http://b2evolution.net/htsrv/track.php?key='.$current_step.'" alt="" />'
		.'</div>';
}


/**
 * Display a link to back to install menu
 */
function display_install_back_link()
{
	global $default_locale;

	echo get_install_format_text( '<ul class="pager">'
			.'<li class="previous"><a href="index.php?locale='.$default_locale.'"><span aria-hidden="true">&larr;</span> '.T_('Back to install menu').'</a></li>'
		.'</ul>', 'p' );
}


/**
 * Display a progress bar to start an animation of a process
 *
 * @param string Title (Not visible on screen, it is used ONLY for screen readers)
 * @param integer|NULL A number of the steps
 */
function start_install_progress_bar( $title, $steps = NULL )
{
	global $install_progress_bar_counter, $install_progress_bar_total, $display;

	if( ! empty( $display ) && $display != 'normal' )
	{ // Exit here, because we can use progress bar on normal mode (Hide on compact mode)
		return;
	}

	if( $steps !== NULL )
	{ // Progress bar with steps
		$install_progress_bar_total = $steps;
		$install_progress_bar_counter = 0;
		$bar_width = '0%';
	}
	else
	{ // Progress bar has no steps for update
		$bar_width = '100%';
	}

	echo '<div class="progress">'
			.'<div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width:'.$bar_width.'">'
				.'<span class="sr-only">'.$title.'</span>'
			.'</div>'
		.'</div>';
	if( $steps !== NULL )
	{ // Use this fix to keep the progress animated forever when JavaScript is disabled
		echo '<noscript>'
				.'<style type="text/css">.progress-bar{width:100% !important}</style>'
			.'</noscript>';
		// Don't use the striped animation when we have a real progress indication
		echo '<script type="text/javascript">'
			.'jQuery( ".progress-bar.active.progress-bar-striped" ).removeClass( "active progress-bar-striped" );'
		.'</script>';
	}
}


/**
 * Print JavaScript to stop the animation of the progress bar
 */
function stop_install_progress_bar()
{
	global $display;

	if( ! empty( $display ) && $display != 'normal' )
	{ // Exit here, because we can use progress bar on normal mode (Hide on compact mode)
		return;
	}

	echo '<script type="text/javascript">'
		.'jQuery( ".progress-bar" ).css( "width", "100%" ).removeClass( "active progress-bar-striped" );'
		.'setTimeout( function() { jQuery( ".progress-bar" ).addClass( "progress-bar-success" ); }, 600 );'
	.'</script>';
}


/**
 * Print JavaScript to update the progress bar status
 */
function update_install_progress_bar()
{
	global $install_progress_bar_counter, $install_progress_bar_total, $display;

	if( ! empty( $display ) && $display != 'normal' )
	{ // Exit here, because we can use progress bar on normal mode (Hide on compact mode)
		return;
	}

	if( empty( $install_progress_bar_total ) )
	{ // No a number of the steps, Exit here
		return;
	}

	// This is next step
	$install_progress_bar_counter++;

	$bar_width = ceil( $install_progress_bar_counter / $install_progress_bar_total * 100 );
	if( $bar_width > 100 )
	{ // Limit by 100%
		$bar_width = 100;
	}

	echo '<script type="text/javascript">'
		.'jQuery( ".progress-bar" ).css( "width", "'.$bar_width.'%" );'
	.'</script>';
}


/**
 * Calculate a number of the steps for current installation
 *
 * @return integer
 */
function get_install_steps_count()
{
	global $allow_install_test_features, $allow_evodb_reset;

	$steps = 0;

	// After Deleting b2evolution tables:
	if( $allow_evodb_reset >= 2 || ( $allow_install_test_features && $allow_evodb_reset >= 1 ) )
	{ // Allow to quick delete before new installation only when these two settings are enabled in config files
		$delete_contents = param( 'delete_contents', 'integer', 0 );

		if( $delete_contents )
		{ // A quick deletion is requested before new installation
			$steps++;
		}
	}

	// After Checking files:
	$steps++;

	// After Loading all modules:
	$steps++;

	// After Creating all DB tables:
	$steps++;

	// Before install default skins:
	$steps++;

	// Installing sample contents:
	$create_sample_contents = param( 'create_sample_contents', 'string', '' );

	if( $create_sample_contents )
	{
		// After Creating default sample contents(users, and probably blogs and categories):
		$steps++;

		if( $create_sample_contents == 'all' )
		{ // Array contains which collections should be installed
			$install_collection_home =   1;
			$install_collection_bloga =  1;
			$install_collection_blogb =  1;
			$install_collection_photos = 1;
			$install_collection_forums = 1;
			$install_collection_manual = 1;
		}
		else
		{ // Array contains which collections should be installed
			$collections = param( 'collections', 'array:string', array() );
			$install_collection_home = in_array( 'home', $collections );
			$install_collection_bloga = in_array( 'a', $collections );
			$install_collection_blogb = in_array( 'b', $collections );
			$install_collection_photos = in_array( 'photos', $collections );
			$install_collection_forums = in_array( 'forums', $collections );
			$install_collection_manual = in_array( 'manual', $collections );
		}

		if( $install_collection_home )
		{ // After installing of the blog "Home"
			$steps++;
		}
		if( $install_collection_bloga )
		{ // After installing of the blog "Blog A"
			$steps++;
		}
		if( $install_collection_blogb )
		{ // After installing of the blog "Blog B"
			$steps++;
		}
		if( $install_collection_photos )
		{ // After installing of the blog "Photos"
			$steps++;
		}
		if( $install_collection_forums )
		{ // After installing of the blog "Forums"
			$steps++;
		}
		if( $install_collection_manual )
		{ // After installing of the blog "Manual"
			$steps++;
		}
	}

	// Last step before successful message:
	$steps++;

	return $steps;
}


/**
 * Calculate a number of the steps for current upgrading
 *
 * @return integer
 */
function get_upgrade_steps_count()
{
	global $new_db_version;

	$steps = 0;

	// After Checking files:
	$steps++;

	// After Loading all modules:
	$steps++;

	// Calculate the upgrade blocks:
	$old_db_version = get_db_version();
	if( $new_db_version > $old_db_version )
	{	// Only when DB must be updated really:
		$upgrade_file_name = dirname( __FILE__ ).'/_functions_evoupgrade.php';
		if( @file_exists( $upgrade_file_name ) )
		{	// If file exists we can parse to know how much the upgrade blocks will be executed:
			$upgrade_file_content = file_get_contents( $upgrade_file_name );
			// Find DB versions in the upgrade blocks like:
			//      if( $old_db_version < 11430 )
			//      if( upg_task_start( 11440, 'Upgrading base domains table...' ) )
			if( preg_match_all( '#if\(\s*(\$old_db_version\s*<|upg_task_start\()\s*(\d+)#i', $upgrade_file_content, $version_matches ) )
			{
				foreach( $version_matches[2] as $version )
				{
					if( $old_db_version < $version && $new_db_version != $old_db_version )
					{	// Only these new blocks will be executed:
						$steps++;
					}
				}
			}
		}
	}

	// Before Starting to check DB:
	$steps++;

	// Last step before successful message:
	$steps++;

	return $steps;
}


/**
 * Display the messages on install pages
 *
 * @param string|array Messages
 * @param string Message type: 'error', 'warning', 'success', 'info'
 */
function display_install_messages( $messages, $type = 'error' )
{
	if( empty( $messages ) )
	{ // No messages
		return;
	}

	if( is_string( $messages ) )
	{
		$messages = array( $messages );
	}

	switch( $type )
	{
		case 'error':
			$before_message = '<evo:error>';
			$after_message = '</evo:error>';
			break;

		case 'warning':
			$before_message = '<evo:warning>';
			$after_message = '</evo:warning>';
			break;

		case 'success':
			$before_message = '<evo:success>';
			$after_message = '</evo:success>';
			break;

		case 'info':
			$before_message = '<evo:note>';
			$after_message = '</evo:note>';
			break;

		default:
			$before_message = '';
			$after_message = '';
			break;
	}

	if( $type == 'error' )
	{ // Fix to correct bootstrap class name "alert-danger"
		$type = 'danger';
	}

	$r = '';
	foreach( $messages as $message )
	{
		$r .= '<div class="alert alert-'.$type.'" role="alert">'.$before_message.$message.$after_message.'</div>'."\n";
	}

	echo get_install_format_text($r);
}


/**
 * Print JavaScript to control button on install page
 */
function echo_install_button_js()
{
	global $app_name;
?>
<script type="text/javascript">
jQuery( document ).ready( function()
{
	jQuery( '#install_button' ).click( function()
	{
		if( jQuery( '#deletedb' ).is( ':checked' ) )
		{
			if( confirm( '<?php printf( /* TRANS: %s gets replaced by app name, usually "b2evolution" */ TS_( 'Are you sure you want to delete your existing %s tables?\nDo you have a backup?' ), $app_name ); ?>' ) )
			{
				jQuery( 'input[name=confirmed]' ).val( 1 );
				return true;
			}
			else
			{
				return false;
			}
		}
	} );

	function update_install_button_info()
	{
		switch( jQuery( 'input[type=radio][name=action]:checked' ).val() )
		{
			case 'menu-install':
				var btn_title = '<?php echo TS_('Next').' &raquo;'; ?>';
				var btn_class = 'btn-success';
				break;

			case 'evoupgrade':
				var btn_title = '<?php echo TS_('UPGRADE!'); ?>';
				var btn_class = 'btn-warning';
				break;

			case 'deletedb':
				var btn_title = '<?php echo TS_('DELETE ALL!'); ?>';
				var btn_class = 'btn-danger';
				break;

			case 'start':
				var btn_title = '<?php echo TS_('Change config').' &raquo;'; ?>';
				var btn_class = 'btn-primary';
				break;

			case 'utf8check':
				var btn_title = '<?php echo TS_('CHECK DB!'); ?>';
				var btn_class = 'btn-primary';
				break;

			default:
				return true;
		}

		jQuery( '#install_button' )
			.html( btn_title )
			.attr( 'class', 'btn btn-lg ' + btn_class );
	}

	jQuery( 'input[type=radio][name=action]' ).click( function()
	{
		update_install_button_info();
	} );

	update_install_button_info();
} );
</script>
<?php
}


/**
 * Check if current installation is local
 *
 * @return boolean
 */
function check_local_installation()
{
	global $basehost;

	return php_sapi_name() != 'cli' && // NOT php CLI mode
		( $basehost == 'localhost' ||
			( isset( $_SERVER['SERVER_ADDR'] ) && (
				$_SERVER['SERVER_ADDR'] == '127.0.0.1' ||
				$_SERVER['SERVER_ADDR'] == '::1' ) // IPv6 address of 127.0.0.1
			) ||
			( isset( $_SERVER['REMOTE_ADDR'] ) && (
				$_SERVER['REMOTE_ADDR'] == '127.0.0.1' ||
				$_SERVER['REMOTE_ADDR'] == '::1' )
			) ||
			( isset( $_SERVER['HTTP_HOST'] ) && (
				$_SERVER['HTTP_HOST'] == '127.0.0.1' ||
				$_SERVER['HTTP_HOST'] == '::1' )
			) ||
			( isset( $_SERVER['SERVER_NAME'] ) && (
				$_SERVER['SERVER_NAME'] == '127.0.0.1' ||
				$_SERVER['SERVER_NAME'] == '::1' )
			)
		);
}


/**
 * Display modal window after install process with some data
 *
 * @param string Title
 * @param string Body
 */
function display_install_result_window( $title, $body )
{
	global $baseurl, $admin_url, $display;

	if( ! empty( $display ) && $display != 'normal' )
	{ // Exit here, because we can use the modal window ONLY on normal mode (Hide on compact mode)
		return;
	}

	// Remove a link to log in from body text:
	$body = preg_replace( '~</?a[^>]*>~i', '', $body );

	// Modal window with info:
	echo '<div class="modal modal-success fade" id="evo_modal__install" tabindex="-1" role="dialog" aria-labelledby="evo_modal__label_install" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="evo_modal__label_install">'.$title.'</h4>
				</div>
				<div class="modal-body">'.$body.'</div>
				<div class="modal-footer" style="text-align:center">
					<a href="'.$baseurl.'" class="btn btn-primary">'.T_('Go to Front-office').'</a>
					<a href="'.$admin_url.'" class="btn btn-default">'.T_('Go to Back-office').'</a>
				</div>
			</div>
		</div>
	</div>';

	// JavaScript to open modal window with info:
	echo '<script type="text/javascript">'
		.'setTimeout( function() { jQuery( "#evo_modal__install" ).modal(); }, 1000 );'
	.'</script>';
}


/**
 * Check request for quick installation
 * Update basic config file from url params
 *
 * @return boolean|string TRUE on success, OR error messages
 */
function check_quick_install_request()
{
	global $config_is_done, $db_config, $install_login, $install_password, $Messages;

	$admin_login = param( 'admin_login', 'string', '' );
	$admin_password = param( 'admin_password', 'string', '' );

	if( ! empty( $admin_login ) )
	{ // Try to use an admin login from request:
		if( param_check_valid_login( 'admin_login' ) )
		{ // We can use this login name
			$install_login = $admin_login;
		}
		else
		{ // Display an error message for impossible login name:
			$Messages->add( sprintf( 'ERROR: Impossible to use "%s" for the admin account. Using "admin" instead.', $admin_login ) );
		}
	}

	if( ! empty( $admin_password ) && param_check_passwords( 'admin_password', 'admin_password', false, 5 ) )
	{ // Set admin password from request:
		$install_password = $admin_password;
	}

	if( $config_is_done )
	{ // Config is already done, Don't use url params to update basic config file
		return true;
	}

	// DB Access:
	$db_user = param( 'db_user', 'string', '' );
	$db_password = param( 'db_password', 'raw', '' );
	$db_name = param( 'db_name', 'string', '' );
	$db_host = param( 'db_host', 'string', '' );
	$db_tableprefix = param( 'db_tableprefix', 'string', '' );

	// Admin e-mail:
	global $admin_email;
	$default_admin_email = $admin_email;
	$conf_admin_email = param( 'admin_email', 'string', '', false, true );

	if( ! empty( $conf_admin_email ) ||
	    ! empty( $db_user ) || ! empty( $db_password ) || ! empty( $db_name ) || ! empty( $db_host ) ||
	    ! empty( $db_tableprefix ) )
	{ // Try to update basic config file ONLY when at least one of these params are defined

		if( empty( $conf_admin_email ) || ! param_check_email( 'admin_email' ) )
		{ // Use default admin email if it is empty from request
			$conf_admin_email = $default_admin_email;
		}

		// Base URL:
		global $baseurl, $admin_url, $dispatcher, $tableprefix;
		$baseurl = param( 'baseurl', 'string', '', false, true );
		if( empty( $baseurl ) || ! preg_match( '~https?://~', $baseurl ) )
		{ // Try to autogenerate base url if it is empty or wrong from request:
			$baseurl = 'http://'.( isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : 'yourserver.com' );
			if( isset( $_SERVER['SERVER_PORT'] ) && ( $_SERVER['SERVER_PORT'] != '80' ) )
				$baseurl .= ':'.$_SERVER['SERVER_PORT'];
			list( $ReqPath, $ReqURI ) = get_ReqURI();
			$baseurl .= preg_replace( '#/install(/(index.php)?)?$#', '', $ReqPath ).'/';
		}
		// Add a slash at the end if it is missed:
		$baseurl = trim( $baseurl, '/' ).'/';
		// Update $admin_url to new value because it depends on $baseurl:
		$admin_url = $baseurl.$dispatcher;

		// Update $tableprefix to new from request:
		$tableprefix = $db_tableprefix;

		// Try to create basic config file:
		$basic_config_params = array(
				'db_user'        => $db_user,
				'db_password'    => $db_password,
				'db_name'        => $db_name,
				'db_host'        => $db_host,
				'db_tableprefix' => $db_tableprefix,
				'baseurl'        => $baseurl,
				'admin_email'    => $conf_admin_email,
				'print_messages' => false,
				'quick_install'  => true,
			);
		if( update_basic_config_file( $basic_config_params ) )
		{ // Basic config file has been created successfully
			// Update DB config to connect to DB correctly
			$db_config['user'] = $db_user;
			$db_config['password'] = $db_password;
			$db_config['name'] = $db_name;
			$db_config['host'] = $db_host;
		}
		else
		{ // Failed on createing of basic config file
			return false;
		}
	}

	// Revert config admin email to original value:
	$admin_email = $default_admin_email;

	return true;
}


/**
 * Update file /conf/_basic_config.php
 *
 * @param string Current action, updated by reference
 * @param array Params
 * @return boolean TRUE on success
 */
function update_basic_config_file( $params = array() )
{
	global $DB, $db_config, $evo_charset, $conf_path, $default_locale;

	// These global params should be rewritten by this function on success result
	global $baseurl, $admin_email, $config_is_done, $action;

	$params = array_merge( array(
			'db_user'        => '',
			'db_password'    => '',
			'db_name'        => '',
			'db_host'        => '',
			'db_tableprefix' => '',
			'baseurl'        => '',
			'admin_email'    => '',
			'print_messages' => true, // TRUE - to print out all messages on screen, FALSE - to return
			'quick_install'  => false, // TRUE - to mark this call as request to quick installation
		), $params );

	if( ! $params['print_messages'] )
	{ // Start to get all messages instead of printing on screen:
		ob_start();
		// Use this global var to store all messages
		global $basic_config_file_result_messages;
	}

	// Connect to DB:
	$DB = new DB( array(
			'user'     => $params['db_user'],
			'password' => $params['db_password'],
			'name'     => $params['db_name'],
			'host'     => $params['db_host'],
			'aliases'          => $db_config['aliases'],
			'connection_charset' => empty( $db_config['connection_charset'] ) ? DB::php_to_mysql_charmap( $evo_charset ) : $db_config['connection_charset'],
			'halt_on_error'      => false
		) );

	if( $DB->error )
	{ // restart conf
		display_install_messages( T_('It seems that the database config settings you entered don\'t work. Please check them carefully and try again...') );
		$action = 'start';
	}
	else
	{
		$conf_template_filepath = $conf_path.'_basic_config.template.php';
		$conf_filepath = $conf_path.'_basic_config.php';

		// Read original:
		$file_loaded = @file( $conf_template_filepath );

		if( empty( $file_loaded ) )
		{ // This should actually never happen, just in case...
			display_install_messages( sprintf( T_('Could not load original conf file [%s]. Is it missing?'), $conf_filepath ) );
			if( ! $params['print_messages'] )
			{ // Return all messages instead of printing on screen
				$basic_config_file_result_messages = ob_get_clean();
			}
			return false;
		}

		// File loaded...
		$conf = implode( '', $file_loaded );
		// Update conf:
		$conf = preg_replace(
			array(
				'#\$db_config\s*=\s*array\(
					\s*[\'"]user[\'"]\s*=>\s*[\'"].*?[\'"],     ([^\n\r]*\r?\n)
					\s*[\'"]password[\'"]\s*=>\s*[\'"].*?[\'"], ([^\n\r]*\r?\n)
					\s*[\'"]name[\'"]\s*=>\s*[\'"].*?[\'"],     ([^\n\r]*\r?\n)
					\s*[\'"]host[\'"]\s*=>\s*[\'"].*?[\'"],     ([^\n\r]*\r?\n)
					#ixs',
				"#tableprefix\s*=\s*'.*?';#",
				"#baseurl\s*=\s*'.*?';#",
				"#admin_email\s*=\s*'.*?';#",
				"#config_is_done\s*=.*?;#",
			),
			array(
				"\$db_config = array(\n"
					."\t'user'     => '".str_replace( array( "'", "\$" ), array( "\'", "\\$" ), $params['db_user'] )."',\$1"
					."\t'password' => '".str_replace( array( "'", "\$" ), array( "\'", "\\$" ), $params['db_password'] )."',\$2"
					."\t'name'     => '".str_replace( array( "'", "\$" ), array( "\'", "\\$" ), $params['db_name'] )."',\$3"
					."\t'host'     => '".str_replace( array( "'", "\$" ), array( "\'", "\\$" ), $params['db_host'] )."',\$4",
				"tableprefix = '".str_replace( "'", "\'", $params['db_tableprefix'] )."';",
				"baseurl = '".str_replace( "'", "\'", $params['baseurl'] )."';",
				"admin_email = '".str_replace( "'", "\'", $params['admin_email'] )."';",
				'config_is_done = 1;',
			), $conf );

		// Write new contents:
		if( save_to_file( $conf, $conf_filepath, 'w' ) )
		{
			display_install_messages( sprintf( T_('Your configuration file <code>%s</code> has been successfully created.').'</p>', $conf_filepath ), 'success' );

			$tableprefix = $params['db_tableprefix'];
			$baseurl = $params['baseurl'];
			$admin_email = $params['admin_email'];
			$config_is_done = 1;
			if( ! $params['quick_install'] )
			{ // Switch to menu only on standard installation:
				$action = 'menu';
			}
		}
		else
		{
			?>
			<h1><?php echo T_('Config file update') ?></h1>
			<p><strong><?php printf( T_('We cannot automatically create or update your config file [%s]!'), $conf_filepath ); ?></strong></p>
			<p><?php echo T_('There are two ways to deal with this:') ?></p>
			<ul>
				<li><strong><?php echo T_('You can allow the installer to create the config file by changing permissions for the /conf directory:') ?></strong>
					<ol>
						<li><?php printf( T_('Make sure there is no existing and potentially locked configuration file named <code>%s</code>. If so, please delete it.'), $conf_filepath ); ?></li>
						<li><?php printf( T_('<code>chmod 777 %s</code>. If needed, see the <a %s>online manual about permissions</a>.'), $conf_path, 'href="'.get_manual_url( 'directory-and-file-permissions' ).'" target="_blank"' ); ?></li>
						<li><?php echo T_('Come back to this page and refresh/reload.') ?></li>
					</ol>
					<br />
				</li>
				<li><strong><?php echo T_('Alternatively, you can update the config file manually:') ?></strong>
					<ol>
						<li><?php echo T_('Create a new text file with a text editor.') ?></li>
						<li><?php echo T_('Copy the contents from the box below.') ?></li>
						<li><?php echo T_('Paste them into your local text editor. <strong>ATTENTION: make sure there is ABSOLUTELY NO WHITESPACE after the final <code>?&gt;</code> in the file.</strong> Any space, tab, newline or blank line at the end of the conf file may prevent cookies from being set when you try to log in later.') ?></li>
						<li><?php echo T_('Save the file locally under the name <code>_basic_config.php</code>') ?></li>
						<li><?php echo T_('Upload the file to your server, into the <code>/_conf</code> folder.') ?></li>
						<li><?php printf( T_('<a %s>Call the installer from scratch</a>.'), 'href="index.php?locale='.$default_locale.'"') ?></li>
					</ol>
				</li>
			</ul>
			<p><?php echo T_('This is how your _basic_config.php should look like:') ?></p>
			<blockquote>
			<pre><?php
				echo htmlspecialchars( $conf );
			?></pre>
			</blockquote>
			<?php

			if( ! $params['print_messages'] )
			{ // Return all messages instead of printing on screen
				$basic_config_file_result_messages = ob_get_clean();
			}

			return false;
		}
	}

	if( ! $params['print_messages'] )
	{ // Return all messages instead of printing on screen
		$basic_config_file_result_messages = ob_get_clean();
	}

	return true;
}


/**
 * Print out log text on screen
 *
 * @param string Log text
 * @param string Log type: 'warning', 'note', 'success', 'danger'
 */
function echo_install_log( $text, $type = 'warning' )
{
	echo '<p class="alert alert-'.$type.'">'.$text.'</p>';
}
?>