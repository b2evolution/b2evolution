<?php
/**
 * This file implements support functions for the installer
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package install
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Open a block
 */
function block_open()
{
	global $block_status;
	if( isset($block_status) && $block_status == 'open' )
	{
		return;
	}
	$block_status = 'open';
	echo '<div class="block1"><div class="block2"><div class="block3">';
}

/**
 * Close a block
 */
function block_close()
{
	global $block_status;
	if( !isset($block_status) || $block_status == 'closed' )
	{
		return;
	}
	$block_status = 'closed';
	echo '</div></div></div>';
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
	block_open();
	?>
	<h2><?php echo T_('Language / Locale')?></h2>
	<form action="index.php" method="get">
	<?php
	echo '<div class="floatright"><a href="index.php?action=localeinfo&amp;locale='.$default_locale.'">'.T_('More languages').' &raquo;</a></div>';

	locale_flag( $default_locale, 'w16px', 'flag', '', true, /* Do not rely on $baseurl/$rsc_url here: */ '../rsc/flags' );
	echo '<select name="locale" onchange="this.form.submit()">';
	foreach( $locales as $lkey => $lvalue )
	{
		echo '<option';
		if( $default_locale == $lkey ) echo ' selected="selected"';
		echo ' value="'.$lkey.'">';
		echo T_( $lvalue['name'] );
		echo '</option>';
	}
	?>
	</select>
	<noscript>
		<input type="submit" value="<?php echo T_('Select as default language/locale'); ?>" />
	</noscript>
	</form>
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

	block_open();
	?>
	<h2><?php echo T_('Base config recap...')?></h2>

	<p><?php printf( T_('If you don\'t see correct settings here, STOP before going any further, and <a %s>update your base configuration</a>.'), 'href="index.php?action=start&amp;locale='.$default_locale.'"' ) ?></p>

	<?php
	if( !isset($conf_db_user) ) $conf_db_user = $db_config['user'];
	if( !isset($conf_db_password) ) $conf_db_password = $db_config['password'];
	if( !isset($conf_db_name) ) $conf_db_name = $db_config['name'];
	if( !isset($conf_db_host) ) $conf_db_host = isset($db_config['host']) ? $db_config['host'] : 'localhost';

	echo '<pre>',
	T_('MySQL Username').': '.$conf_db_user."\n".
	T_('MySQL Password').': '.(($conf_db_password != 'demopass' ? T_('(Set, but not shown for security reasons)') : 'demopass') )."\n".
	T_('MySQL Database name').': '.$conf_db_name."\n".
	T_('MySQL Host/Server').': '.$conf_db_host."\n".
	T_('MySQL tables prefix').': '.$tableprefix."\n\n".
	T_('Base URL').': '.$baseurl."\n\n".
	T_('Admin email').': '.$admin_email.
	'</pre>';
	block_close();
}


/**
 * Install new DB.
 */
function install_newdb()
{
	global $new_db_version, $admin_url, $random_password;

	/*
	 * -----------------------------------------------------------------------------------
	 * NEW DB: Create a plain new db structure + sample contents
	 * -----------------------------------------------------------------------------------
	 */
	require_once dirname(__FILE__).'/_functions_create.php';

	if( $old_db_version = get_db_version() )
	{
		echo '<p><strong>'.T_('OOPS! It seems b2evolution is already installed!').'</strong></p>';

		if( $old_db_version < $new_db_version )
		{
			echo '<p>'.sprintf( T_('Would you like to <a %s>upgrade your existing installation now</a>?'), 'href="?action=evoupgrade"' ).'</p>';
		}

		return;
	}

	$installer_version = param( 'installer_version', 'integer', 0 );
	if( $installer_version >= 10 )
	{
		$create_sample_contents = param( 'create_sample_contents', 'integer', 0 );
	}
	else
	{	// OLD INSTALLER call. Probably an automated script calling.
		// Let's force the sample contents since they haven't been explicitly disabled
		$create_sample_contents = 1;
	}

	/**
	 * 1 - If current installation is local, test or intranet
	 *     Used to turn off gravatar and all ping plugins
	 *
	 * @var integer
	 */
	$local_installation = param( 'local_installation', 'integer', 0 );

	echo '<h2>'.T_('Creating b2evolution tables...').'</h2>';
	evo_flush();
	create_tables();

	echo '<h2>'.T_('Creating minimum default data...').'</h2>';
	evo_flush();
	create_default_data();

	if( $create_sample_contents )
	{
		global $Settings, $test_install_all_features;

		echo '<h2>'.T_('Installing sample contents...').'</h2>';
		evo_flush();

		// We're gonna need some environment in order to create the demo contents...
		load_class( 'settings/model/_generalsettings.class.php', 'GeneralSettings' );
		load_class( 'users/model/_usersettings.class.php', 'UserSettings' );
		/**
		 * @var GeneralSettings
		 */
		$Settings = new GeneralSettings();

		if( $test_install_all_features )
		{	// Set manual ordering of categories
			$Settings->set( 'chapter_ordering', 'manual' );
			$Settings->dbupdate();
		}

		/**
		 * @var UserCache
		 */
		$UserCache = & get_UserCache();
		// Create $current_User object.
		// (Assigning by reference does not work with "global" keyword (PHP 5.2.8))
		$GLOBALS['current_User'] = & $UserCache->get_by_ID( 1 );

		create_demo_contents();
	}

	track_step( 'install-success' );
	echo '<h2>'.T_('Installation successful!').'</h2>';

	echo '<p><strong>';
	printf( T_('Now you can <a %s>log in</a> with the following credentials:'), 'href="'.$admin_url.'"' );
	echo '</strong></p>';

	echo '<table>';
	echo '<tr><td>', T_( 'Login' ), ': &nbsp;</td><td><strong><evo:password>admin</evo:password></strong></td></tr>';
	printf( '<tr><td>%s: &nbsp;</td><td><strong><evo:password>%s</evo:password></strong></td></tr>', T_( 'Password' ), $random_password );
	echo '</table>';

	echo '<p>'.T_('Note that password carefully! It is a <em>random</em> password that is given to you when you install b2evolution. If you lose it, you will have to delete the database tables and re-install anew.').'</p>';
}


/**
 * Begin install task.
 * This will offer other display methods in the future
 */
function task_begin( $title )
{
	echo $title."\n";
	evo_flush();
}


/**
 * End install task.
 * This will offer other display methods in the future
 */
function task_end()
{
	echo "OK.<br />\n";
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
	global $Group_Admins, $Group_Privileged, $Group_Bloggers, $Group_Users, $Group_Suspect, $Group_Spam;
	global $test_install_all_features, $create_sample_contents, $local_installation;

	$defaults = array(
		'db_version' => $new_db_version,
		'default_locale' => $default_locale,
		'newusers_grp_ID' => $Group_Users->ID,
		'default_blog_ID' => 1,
		'evocache_foldername' => '_evocache',
	);
	if( $test_install_all_features )
	{
		$defaults['gender_colored'] = 1;
		$defaults['newusers_canregister'] = 1;
		$defaults['registration_require_country'] = 1;
		$defaults['registration_require_gender'] = 'required';
		$defaults['location_country'] = 'required';
		$defaults['location_region'] = 'required';
		$defaults['location_subregion'] = 'required';
		$defaults['location_city'] = 'required';
	}
	if( $create_sample_contents )
	{
		$defaults['info_blog_ID'] = '3';
	}
	if( !empty( $Group_Suspect ) )
	{ // Set default antispam suspicious group
		$defaults['antispam_suspicious_group'] = $Group_Suspect->ID;
	}
	$antispam_trust_groups = array();
	if( !empty( $Group_Admins ) )
	{
		$antispam_trust_groups[] = $Group_Admins->ID;
	}
	if( !empty( $Group_Privileged ) )
	{
		$antispam_trust_groups[] = $Group_Privileged->ID;
	}
	if( !empty( $Group_Bloggers ) )
	{
		$antispam_trust_groups[] = $Group_Bloggers->ID;
	}
	if( !empty( $Group_Spam ) )
	{
		$antispam_trust_groups[] = $Group_Spam->ID;
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

	// Note: Skin #1 will we used by Blog A
	skin_install( 'evopress' );

	// Note: Skin #2 will we used by Blog B
	skin_install( 'evocamp' );

	// Note: Skin #3 will we used by Linkblog
	skin_install( 'miami_blue' );

	// Note: Skin #4 will we used by Photos
	skin_install( 'photoalbums' );

	// Note: Skin #5 will we used by Forums
	skin_install( 'pureforums' );

	// Note: Skin #6 will we used by Manual
	skin_install( 'manual' );

	skin_install( 'asevo' );
	skin_install( 'bootstrap' );
	skin_install( 'custom' );
	skin_install( 'dating_mood' );
	skin_install( 'forums' );
	skin_install( 'glossyblue' );
	skin_install( 'intense' );
	skin_install( 'natural_pink' );
	skin_install( 'nifty_corners' );
	skin_install( 'photoblog' );
	skin_install( 'pixelgreen' );
	skin_install( 'pluralism' );
	skin_install( 'terrafirma' );
	if( $install_mobile_skins )
	{
		skin_install( 'touch' );
	}
	skin_install( 'vastitude' );
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
	global $Plugins_admin, $test_install_all_features;

	$Plugins_admin = & get_Plugins_admin();

	// Create global $Plugins instance, which is required during installation of basic plugins,
	// not only for the ones getting installed, but also during e.g. count_regs(), which instantiates
	// each plugin (which may then use (User)Settings in PluginInit (through Plugin::__get)).
	$GLOBALS['Plugins'] = & $Plugins_admin;

	if( $old_db_version < 9100 )
	{
		// Toolbars:
		install_plugin( 'quicktags_plugin' );
		install_plugin( 'shortcodes_plugin' );
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
		install_plugin( 'smilies_plugin' );
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

		if( $test_install_all_features )
		{
			install_plugin( 'google_maps_plugin' );
		}
	}

	if( $old_db_version < 11000 )
	{ // Upgrade to 5.0.0-alpha-4
		install_plugin( 'captcha_qstn_plugin' );
	}

	if( $old_db_version < 11100 )
	{ // Upgrade to 5.0.1-alpha-5
		install_plugin( 'escapecode_plugin' );
		install_plugin( 'bbcode_plugin', $test_install_all_features );
		install_plugin( 'star_plugin', $test_install_all_features );
		install_plugin( 'prism_plugin', $test_install_all_features );
		install_plugin( 'code_highlight_plugin', $test_install_all_features );
		install_plugin( 'markdown_plugin' );
		install_plugin( 'infodots_plugin', $test_install_all_features );
		install_plugin( 'widescroll_plugin' );
	}
}


/**
 * Install plugin
 *
 * @param string Plugin name
 * @param boolean TRUE - to activate plugin
 * @return true on success
 */
function install_plugin( $plugin, $activate = true )
{
	/**
	 * @var Plugins_admin
	 */
	global $Plugins_admin;

	task_begin( 'Installing plugin: '.$plugin.'... ' );
	$edit_Plugin = & $Plugins_admin->install( $plugin, 'broken' ); // "broken" by default, gets adjusted later
	if( ! is_a( $edit_Plugin, 'Plugin' ) )
	{
		echo $edit_Plugin."<br />\n";
		return false;
	}

	load_funcs('plugins/_plugin.funcs.php');
	install_plugin_db_schema_action( $edit_Plugin, true );

	if( $activate )
	{	// Try to enable plugin:
		$enable_return = $edit_Plugin->BeforeEnable();
		if( $enable_return !== true )
		{
			$Plugins_admin->set_Plugin_status( $edit_Plugin, 'disabled' ); // does not unregister it
			echo $enable_return."<br />\n";
			return false;
		}

		$Plugins_admin->set_Plugin_status( $edit_Plugin, 'enabled' );
	}
	else
	{	// Set plugin status as disable
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
								add constraint FK_post_ptyp_ID
											foreign key (post_ptyp_ID)
											references T_items__type (ptyp_ID)
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
 * Loads the b2evo database scheme.
 *
 * This gets updated through {@link db_delta()} which generates the queries needed to get
 * to this scheme.
 *
 * Please see {@link db_delta()} for things to take care of.
 */
function load_db_schema()
{
	global $schema_queries;
	global $modules, $inc_path;

	global $db_storage_charset, $DB;
	if( empty($db_storage_charset) )
	{	// If no specific charset has been requested for datstorage, use the one of the current connection (optimize for speed - no conversions)
		$db_storage_charset = $DB->connection_charset;
	}
	//pre_dump( 'db_storage_charset', $db_storage_charset );

	// Load modules:
	foreach( $modules as $module )
	{
		echo 'Loading module: '.$module.'/model/_'.$module.'.install.php<br />';
		require_once $inc_path.$module.'/model/_'.$module.'.install.php';
	}

}


/**
 * Install htaccess: Check if it works with the webserver, then install it for real.
 *
 * @return boolean TRUE if no errors
 */
function install_htaccess( $upgrade = false )
{
	echo '<p>Preparing to install .htaccess ... ';

	$server = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '';
	if( !empty($server) && preg_match('~(Nginx|Lighttpd|Microsoft-IIS)~i', $server) )
	{	// Skip installation if this is not an Apache server
		echo '<br /><b>.htaccess is not needed (not an Apache server)</b></p>';
		return true;
	}

	$error_message = do_install_htaccess( $upgrade );

	if( $error_message )
	{
		$htignore = param( 'htignore', 'integer', 0 );

		echo 'ERROR!<br/><b>'.$error_message.'</b>';

		if( $htignore )
		{	// Ignore errors with .htaccess file
			return true;
		}
		else
		{	// Some errors are existing with .htaccess file, Display a link to ignore the errors and continue instalation
			echo '<p style="text-align:center;font-size:150%;font-weight:bold;margin-top:10px"><a href="'.$_SERVER['REQUEST_URI'].'&htignore=1">'.T_('Continue installation &raquo;').'</a></p>';
			return false;
		}
	}
	echo '</p>';

	return true;
}

/**
 * This does the actual file manipulations for installing .htaccess
 * This will verify that the provided sample.htaccess does not crash apache in a test folder before installing it for real.
 *
 * @param boolean are we upgrading (vs installing)?
 * @return mixed
 */
function do_install_htaccess( $upgrade = false )
{
	global $baseurl;
	global $basepath;

	if( @file_exists($basepath.'.htaccess') )
	{
		if( $upgrade )
		{
			echo T_('Already installed.');
			return ''; // all is well :)
		}

		if( @file_exists( $basepath.'sample.htaccess' ) )
		{
			$content_htaccess = trim( file_get_contents( $basepath.'.htaccess' ) );
			$content_sample_htaccess = trim( file_get_contents( $basepath.'sample.htaccess' ) );

			if( $content_htaccess != $content_sample_htaccess )
			{	// The .htaccess file has content that different from a sample file
				$error_message = '<p class="red">'.T_('There is already a file called .htaccess at the blog root. If you don\'t specifically need this file, it is recommended that you delete it or rename it to old.htaccess before you continue. This will allow b2evolution to create a new .htaccess file that is optimized for best results.').'</p>';
				$error_message .= T_('Here are the contents of the current .htaccess file:');
				$error_message .= '<div style="overflow:auto"><pre>'.evo_htmlspecialchars( $content_htaccess ).'</pre></div><br />';
				$error_message .= sprintf( T_('Again, we recommend you remove this file before continuing. If you chose to keep it, b2evolution will probably still work, but for optimization you should follow <a %s>these instructions</a>.'), 'href="'.get_manual_url( 'htaccess-file' ).'" target="_blank"' );
				return $error_message;
			}
			else
			{
				echo T_('Already installed.');
				return '';
			}
		}
	}

	// Make sure we have a sample file to start with:
	if( ! @file_exists( $basepath.'sample.htaccess' ) )
	{
		return 'Cannot find file [ sample.htaccess ] in your base url folder.';
	}

	// Try to copy that file to the test folder:
	if( ! @copy( $basepath.'sample.htaccess', $basepath.'install/test/.htaccess' ) )
	{
		return 'Failed to copy files!';
	}

	// Make sure .htaccess does not crash in the test folder:
	load_funcs('_core/_url.funcs.php');
	$info = array();
	if( ! $remote_page = fetch_remote_page( $baseurl.'install/test/', $info ) )
	{
		return $info['error'];
	}
	if( substr( $remote_page, 0, 16 ) != 'Test successful.' )
	{
		return 'install/test/index.html was not found as expected.';
	}

	// Now we consider it's safe, copy .htaccess to its real location:
	if( ! @copy( $basepath.'sample.htaccess', $basepath.'.htaccess' ) )
	{
		return 'Test was successful, but failed to copy .htaccess into baseurl directory!';
	}

	echo 'Install successful.';
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
	// echo 'Tracking '.$current_step;
	echo '<img src="http://b2evolution.net/htsrv/track.php?key='.$current_step.'" alt="" />';
}



?>