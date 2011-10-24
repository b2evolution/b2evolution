<?php
/**
 * This file implements upgrading of DB tables
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package install
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_funcs('_core/_param.funcs.php');


/**
 * Create a DB version checkpoint
 *
 * This is useful when the next operation might timeout or fail!
 * The checkpoint will allow to restart the script and continue where it stopped
 *
 * @param string version of DB at checkpoint
 */
function set_upgrade_checkpoint( $version )
{
	global $DB, $script_start_time, $locale;

	echo "Creating DB schema version checkpoint at $version... ";

	if( $version < 8060 )
	{
		$query = 'UPDATE T_settings SET db_version = '.$version;
	}
	else
	{
		$query = "UPDATE T_settings
								SET set_value = '$version'
								WHERE set_name = 'db_version'";
	}
	$DB->query( $query );


	$elapsed_time = time() - $script_start_time;

	echo "OK. (Elapsed upgrade time: $elapsed_time seconds)<br />\n";
	flush();

	$max_exe_time = ini_get( 'max_execution_time' );
	if( $max_exe_time && $elapsed_time > $max_exe_time - 10 )
	{ // Max exe time not disabled and we're recahing the end
		echo 'We are reaching the time limit for this script. Please click <a href="index.php?locale='.$locale.'&amp;action=evoupgrade">continue</a>...';
		// Dirty temporary solution:
		exit(0);
	}
}


/**
 * @return boolean Does a given index key name exist in DB?
 */
function db_index_exists( $table, $index_name )
{
	global $DB;

	$index_name = strtolower($index_name);

	$DB->query('SHOW INDEX FROM '.$table);
	while( $row = $DB->get_row() )
	{
		if( strtolower($row->Key_name) == $index_name )
		{
			return true;
		}
	}

	return false;
}


/**
 * @param string Table name
 * @param array Column names
 * @return boolean Does a list of given column names exist in DB?
 */
function db_cols_exist( $table, $col_names )
{
	global $DB;

	foreach( $col_names as $k => $v )
		$col_names[$k] = strtolower($v);

	foreach( $DB->get_results('SHOW COLUMNS FROM '.$table) as $row )
		if( ($key = array_search(strtolower($row->Field), $col_names)) !== false )
			unset( $col_names[$key] );

	return count($col_names) == 0;
}

/**
 * Drops a column, if it exists.
 */
function db_drop_col( $table, $col_name )
{
	global $DB;

	if( ! db_col_exists($table, $col_name) )
		return false;

	$DB->query( 'ALTER TABLE '.$table.' DROP COLUMN '.$col_name );
}

/**
 * Add a column, if it does not already exist.
 * If it exists already, a "ALTER TABLE" statement will get executed instead.
 *
 * @return boolean True if the column has been added, False if not.
 */
function db_add_col( $table, $col_name, $col_desc )
{
	global $DB;

	if( db_col_exists($table, $col_name) )
	{ // Column exists already, make sure it's the same.
		$DB->query( 'ALTER TABLE '.$table.' MODIFY COLUMN '.$col_name.' '.$col_desc );
		return false;
	}

	$DB->query( 'ALTER TABLE '.$table.' ADD COLUMN '.$col_name.' '.$col_desc );
}


/**
 * Add an INDEX. If another index with the same name already exists, it will
 * get dropped before.
 */
function db_add_index( $table, $name, $def, $type = 'INDEX' )
{
	global $DB;
	if( db_index_exists($table, $name) )
	{
		$DB->query( 'ALTER TABLE '.$table.' DROP INDEX '.$name );
	}
	$DB->query( 'ALTER TABLE '.$table.' ADD '.$type.' '.$name.' ('.$def.')' );
}


/**
 * Check if a key item value already exists on database
 */
function db_key_exists( $table, $field_name, $field_value )
{
	global $DB;
	return $DB->get_var( '
		SELECT COUNT('.$field_name.')
		FROM '.$table.'
		WHERE '.$field_name.' = '.$field_value );
}

/**
 * Converts languages in a given table into according locales
 *
 * @param string name of the table
 * @param string name of the column where lang is stored
 * @param string name of the table's ID column
 */
function convert_lang_to_locale( $table, $columnlang, $columnID )
{
	global $DB, $locales, $default_locale;

	if( !preg_match('/[a-z]{2}-[A-Z]{2}(-.{1,14})?/', $default_locale) )
	{ // we want a valid locale
		$default_locale = 'en-EU';
	}

	echo 'Converting langs to locales for '. $table. '...<br />';

	// query given languages in $table
	$query = "SELECT $columnID, $columnlang FROM $table";
	$languagestoconvert = array();
	foreach( $DB->get_results( $query, ARRAY_A ) as $row )
	{
		// remember the ID for that locale
		$languagestoconvert[ $row[ $columnlang ] ][] = $row[ $columnID ];
	}

	foreach( $languagestoconvert as $lkey => $lIDs)
	{ // converting the languages we've found
		$converted = false;
		echo '&nbsp; Converting lang \''. $lkey. '\' '; // (with IDs: '. implode( ', ', $lIDs ). ').. ';

		if( preg_match('/[a-z]{2}-[A-Z]{2}(-.{1,14})?/', $lkey) )
		{ // Already valid
			echo 'nothing to update, already valid!<br />';
			continue;
		}

		if( (strlen($lkey) == 2) && ( substr( $default_locale, 0, 2 ) != $lkey ) )
		{ // we have an old two letter lang code to convert
			// and it doesn't match the default locale
			foreach( $locales as $newlkey => $v )
			{  // loop given locales
				if( substr($newlkey, 0, 2) == strtolower($lkey) ) # TODO: check if valid/suitable
				{  // if language matches, update
					$converted = $DB->query( "
						UPDATE $table
						   SET $columnlang = '$newlkey'
						 WHERE $columnlang = '$lkey'" );
					echo 'to locale \''. $newlkey. '\'<br />';
					break;
				}
			}
		}

		if( !$converted )
		{ // we have nothing converted yet, setting default:
			$DB->query( "UPDATE $table
											SET $columnlang = '$default_locale'
										WHERE $columnlang = '$lkey'" );
			echo 'forced to default locale \''. $default_locale. '\'<br />';
		}
	}
	echo "\n";
}  // convert_lang_to_locale(-)


/**
 * upgrade_b2evo_tables(-)
 */
function upgrade_b2evo_tables()
{
	global $db_config, $tableprefix;
	global $baseurl, $old_db_version, $new_db_version;
	global $Group_Admins, $Group_Privileged, $Group_Bloggers, $Group_Users;
	global $locales, $default_locale;
	global $DB;
	global $admin_url;

	// used for defaults, when upgrading to 1.6
	global $use_fileupload, $fileupload_allowedtypes, $fileupload_maxk, $doubleCheckReferers;

	// new DB-delta functionality
	global $schema_queries, $inc_path;

	// Load DB schema from modules
	load_db_schema();

	load_funcs('_core/model/db/_upgrade.funcs.php');


	echo '<p>'.T_('Checking DB schema version...').' ';
	$old_db_version = get_db_version();

	if( empty($old_db_version) )
	{
		echo '<p><strong>OOPS! b2evolution doesn\'t seem to be installed yet.</strong></p>';
		return;
	}

	echo $old_db_version, ' : ';

	if( $old_db_version < 8000 ) debug_die( T_('This version is too old!') );
	if( $old_db_version > $new_db_version ) debug_die( T_('This version is too recent! We cannot downgrade to the version you are trying to install...') );
	echo "OK.<br />\n";


	// Try to obtain some serious time to do some serious processing (5 minutes)
	set_max_execution_time(300);



	if( $old_db_version < 8010 )
	{
		echo 'Upgrading users table... ';
		$query = "ALTER TABLE T_users
							MODIFY COLUMN user_pass CHAR(32) NOT NULL";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Upgrading blogs table... ';
		$query = "ALTER TABLE T_blogs
							MODIFY COLUMN blog_lang VARCHAR(20) NOT NULL DEFAULT 'en_US',
							MODIFY COLUMN blog_longdesc TEXT NULL DEFAULT NULL";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Upgrading categories table... ';
		$query = "ALTER TABLE T_categories
							ADD COLUMN cat_description VARCHAR(250) NULL DEFAULT NULL,
							ADD COLUMN cat_longdesc TEXT NULL DEFAULT NULL,
							ADD COLUMN cat_icon VARCHAR(30) NULL DEFAULT NULL";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Upgrading posts table... ';
		$query = "ALTER TABLE {$tableprefix}posts
							MODIFY COLUMN post_lang VARCHAR(20) NOT NULL DEFAULT 'en_US',
							ADD COLUMN post_urltitle VARCHAR(50) NULL DEFAULT NULL AFTER post_title,
							ADD COLUMN post_url VARCHAR(250) NULL DEFAULT NULL AFTER post_urltitle,
							ADD COLUMN post_comments ENUM('disabled', 'open', 'closed') NOT NULL DEFAULT 'open' AFTER post_wordcount";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Generating wordcounts... ';
		load_funcs('items/model/_item.funcs.php');
		$query = "SELECT ID, post_content FROM {$tableprefix}posts WHERE post_wordcount IS NULL";
		$i = 0;
		foreach( $DB->get_results( $query, ARRAY_A ) as $row )
		{
			$query_update_wordcount = "UPDATE {$tableprefix}posts
																SET post_wordcount = " . bpost_count_words($row['post_content']) . "
																WHERE ID = " . $row['ID'];
			$DB->query($query_update_wordcount);
			$i++;
		}
		echo "OK. ($i rows updated)<br />\n";

		set_upgrade_checkpoint( '8010' );
	}


	if( $old_db_version < 8020 )
	{
		echo 'Encoding passwords... ';
		$query = "UPDATE T_users
							SET user_pass = MD5(user_pass)";
		$DB->query( $query );
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '8020' );
	}


	if( $old_db_version < 8030 )
	{
		echo 'Deleting unecessary logs... ';
		$query = "DELETE FROM T_hitlog
							WHERE hit_ignore = 'badchar'";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Updating blog urls... ';
		$query = "SELECT blog_ID, blog_siteurl FROM T_blogs";
		$i = 0;
		foreach( $DB->get_results( $query, ARRAY_A ) as $row )
		{
			$blog_ID = $row['blog_ID'];
			$blog_siteurl = $row['blog_siteurl'];
			// echo $blog_ID.':'.$blog_siteurl;
			if( strpos( $blog_siteurl.'/', $baseurl ) !== 0 )
			{ // If not found at position 0
				echo ' <strong>WARNING: please check blog #', $blog_ID, ' manually.</strong><br /> ';
				continue;
			}
			// crop off the baseurl:
			$blog_siteurl = evo_substr( $blog_siteurl.'/', evo_strlen($baseurl) );
			// echo ' -> ', $blog_siteurl,'<br />';

			$query_update_blog = "UPDATE T_blogs SET blog_siteurl = '$blog_siteurl' WHERE blog_ID = $blog_ID";
			// echo $query_update_blog, '<br />';
			$DB->query( $query_update_blog );
			$i++;
		}
		echo "OK. ($i rows updated)<br />\n";

		set_upgrade_checkpoint( '8030' );
	}


	if( $old_db_version < 8040 )
	{ // upgrade to 0.8.7
		echo 'Creating table for Antispam Blackist... ';
		$query = "CREATE TABLE T_antispam (
			aspm_ID bigint(11) NOT NULL auto_increment,
			aspm_string varchar(80) NOT NULL,
			aspm_source enum( 'local','reported','central' ) NOT NULL default 'reported',
			PRIMARY KEY aspm_ID (aspm_ID),
			UNIQUE aspm_string (aspm_string)
		)";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Creating default blacklist entries... ';
		// This string contains antispam information that is obfuscated because some hosting
		// companies prevent uploading PHP files containing "spam" strings.
		// pre_dump(get_antispam_query());
		$query = get_antispam_query();
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Upgrading Settings table... ';
		$query = "ALTER TABLE T_settings
							ADD COLUMN last_antispam_update datetime NOT NULL default '2000-01-01 00:00:00'";
		$DB->query( $query );
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '8040' );
	}


	if( $old_db_version < 8050 )
	{ // upgrade to 0.8.9
		echo 'Upgrading blogs table... ';
		$query = "ALTER TABLE T_blogs
							ADD COLUMN blog_allowtrackbacks tinyint(1) NOT NULL default 1,
							ADD COLUMN blog_allowpingbacks tinyint(1) NOT NULL default 0,
							ADD COLUMN blog_pingb2evonet tinyint(1) NOT NULL default 0,
							ADD COLUMN blog_pingtechnorati tinyint(1) NOT NULL default 0,
							ADD COLUMN blog_pingweblogs tinyint(1) NOT NULL default 0,
							ADD COLUMN blog_pingblodotgs tinyint(1) NOT NULL default 0,
							ADD COLUMN blog_disp_bloglist tinyint NOT NULL DEFAULT 1";
		$DB->query( $query );
		echo "OK.<br />\n";

		// Create User Groups
		global $Group_Admins, $Group_Privileged, $Group_Bloggers, $Group_Users;
		echo 'Creating table for Groups... ';
		$query = "CREATE TABLE T_groups (
			grp_ID int(11) NOT NULL auto_increment,
			grp_name varchar(50) NOT NULL default '',
			grp_perm_admin enum('none','hidden','visible') NOT NULL default 'visible',
			grp_perm_blogs enum('user','viewall','editall') NOT NULL default 'user',
			grp_perm_stats enum('none','view','edit') NOT NULL default 'none',
			grp_perm_spamblacklist enum('none','view','edit') NOT NULL default 'none',
			grp_perm_options enum('none','view','edit') NOT NULL default 'none',
			grp_perm_users enum('none','view','edit') NOT NULL default 'none',
			grp_perm_templates TINYINT NOT NULL DEFAULT 0,
			grp_perm_files enum('none','view','add','edit') NOT NULL default 'none',
			PRIMARY KEY grp_ID (grp_ID)
		)";
		$DB->query( $query );
		echo "OK.<br />\n";

		// This table needs to be created here for proper group insertion
		task_begin( 'Creating table for Group Settings... ' );
		$DB->query( "CREATE TABLE T_groups__groupsettings (
			gset_grp_ID INT(11) UNSIGNED NOT NULL,
			gset_name VARCHAR(30) NOT NULL,
			gset_value VARCHAR(255) NULL,
			PRIMARY KEY (gset_grp_ID, gset_name)
		) ENGINE = innodb" );
		task_end();

		echo 'Creating default groups... ';
		$Group_Admins = new Group(); // COPY !
		$Group_Admins->set( 'name', 'Administrators' );
		$Group_Admins->set( 'perm_admin', 'visible' );
		$Group_Admins->set( 'perm_blogs', 'editall' );
		$Group_Admins->set( 'perm_stats', 'edit' );
		$Group_Admins->set( 'perm_spamblacklist', 'edit' );
		$Group_Admins->set( 'perm_files', 'all' );
		$Group_Admins->set( 'perm_options', 'edit' );
		$Group_Admins->set( 'perm_templates', 1 );
		$Group_Admins->set( 'perm_users', 'edit' );
		$Group_Admins->dbinsert();

		$Group_Privileged = new Group(); // COPY !
		$Group_Privileged->set( 'name', 'Privileged Bloggers' );
		$Group_Privileged->set( 'perm_admin', 'visible' );
		$Group_Privileged->set( 'perm_blogs', 'viewall' );
		$Group_Privileged->set( 'perm_stats', 'view' );
		$Group_Privileged->set( 'perm_spamblacklist', 'edit' );
		$Group_Privileged->set( 'perm_files', 'add' );
		$Group_Privileged->set( 'perm_options', 'view' );
		$Group_Privileged->set( 'perm_templates', 0 );
		$Group_Privileged->set( 'perm_users', 'view' );
		$Group_Privileged->dbinsert();

		$Group_Bloggers = new Group(); // COPY !
		$Group_Bloggers->set( 'name', 'Bloggers' );
		$Group_Bloggers->set( 'perm_admin', 'visible' );
		$Group_Bloggers->set( 'perm_blogs', 'user' );
		$Group_Bloggers->set( 'perm_stats', 'none' );
		$Group_Bloggers->set( 'perm_spamblacklist', 'view' );
		$Group_Bloggers->set( 'perm_files', 'view' );
		$Group_Bloggers->set( 'perm_options', 'none' );
		$Group_Bloggers->set( 'perm_templates', 0 );
		$Group_Bloggers->set( 'perm_users', 'none' );
		$Group_Bloggers->dbinsert();

		$Group_Users = new Group(); // COPY !
		$Group_Users->set( 'name', 'Basic Users' );
		$Group_Users->set( 'perm_admin', 'none' );
		$Group_Users->set( 'perm_blogs', 'user' );
		$Group_Users->set( 'perm_stats', 'none' );
		$Group_Users->set( 'perm_spamblacklist', 'none' );
		$Group_Users->set( 'perm_files', 'none' );
		$Group_Users->set( 'perm_options', 'none' );
		$Group_Users->set( 'perm_templates', 0 );
		$Group_Users->set( 'perm_users', 'none' );
		$Group_Users->dbinsert();
		echo "OK.<br />\n";


		echo 'Creating table for Blog-User permissions... ';
		$query = "CREATE TABLE T_coll_user_perms (
			bloguser_blog_ID int(11) unsigned NOT NULL default 0,
			bloguser_user_ID int(11) unsigned NOT NULL default 0,
			bloguser_ismember tinyint NOT NULL default 0,
			bloguser_perm_poststatuses set('published','deprecated','protected','private','draft') NOT NULL default '',
			bloguser_perm_delpost tinyint NOT NULL default 0,
			bloguser_perm_comments tinyint NOT NULL default 0,
			bloguser_perm_cats tinyint NOT NULL default 0,
			bloguser_perm_properties tinyint NOT NULL default 0,
			bloguser_perm_media_upload tinyint NOT NULL default 0,
			bloguser_perm_media_browse tinyint NOT NULL default 0,
			bloguser_perm_media_change tinyint NOT NULL default 0,
			PRIMARY KEY bloguser_pk (bloguser_blog_ID,bloguser_user_ID)
		)";
		$DB->query( $query );
		echo "OK.<br />\n";
		$tablegroups_isuptodate = true;
		$tableblogusers_isuptodate = true;

		echo 'Creating user blog permissions... ';
		// Admin: full rights for all blogs (look 'ma, doing a natural join! :>)
		$query = "INSERT INTO T_coll_user_perms( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember,
								bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments,
								bloguser_perm_cats, bloguser_perm_properties)
							SELECT blog_ID, ID, 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1
							FROM T_users, T_blogs
							WHERE user_level = 10";
		$DB->query( $query );

		// Normal users: basic rights for all blogs (can't stop doing joins :P)
		$query = "INSERT INTO T_coll_user_perms( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember,
								bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments,
								bloguser_perm_cats, bloguser_perm_properties)
							SELECT blog_ID, ID, 1, 'published,protected,private,draft', 0, 1, 0, 0
							FROM T_users, T_blogs
							WHERE user_level > 0 AND user_level < 10";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Upgrading users table... ';
		$query = "ALTER TABLE T_users
							ADD COLUMN user_notify tinyint(1) NOT NULL default 1,
							ADD COLUMN user_grp_ID int(4) NOT NULL default 1,
							MODIFY COLUMN user_idmode varchar(20) NOT NULL DEFAULT 'login',
							ADD KEY user_grp_ID (user_grp_ID)";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Assigning user groups... ';

		// Default is 1, so admins are already set.

		// Basic Users:
		$query = "UPDATE T_users
							SET user_grp_ID = $Group_Users->ID
							WHERE user_level = 0";
		$DB->query( $query );

		// Bloggers:
		$query = "UPDATE T_users
							SET user_grp_ID = $Group_Bloggers->ID
							WHERE user_level > 0 AND user_level < 10";
		$DB->query( $query );

		echo "OK.<br />\n";

		echo 'Upgrading settings table... ';
		$query = "ALTER TABLE T_settings
							DROP COLUMN time_format,
							DROP COLUMN date_format,
							ADD COLUMN pref_newusers_grp_ID int unsigned DEFAULT 4 NOT NULL,
							ADD COLUMN pref_newusers_level tinyint unsigned DEFAULT 1 NOT NULL,
							ADD COLUMN pref_newusers_canregister tinyint unsigned DEFAULT 0 NOT NULL";
		$DB->query( $query );
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '8050' );
	}


	if( $old_db_version < 8060 )
	{ // upgrade to 0.9
		// Important check:
		$stub_list = $DB->get_col( "
			SELECT blog_stub
			  FROM T_blogs
			 GROUP BY blog_stub
			HAVING COUNT(*) > 1" );
		if( !empty($stub_list) )
		{
			echo '<div class="error"><p class="error">';
			printf( T_("It appears that the following blog stub names are used more than once: ['%s']" ), implode( "','", $stub_list ) );
			echo '</p><p>';
			printf( T_("I can't upgrade until you make them unique. DB field: [%s]" ), $db_config['aliases']['T_blogs'].'.blog_stub' );
			echo '</p></div>';
			return false;
		}

		// Create locales
		echo 'Creating table for Locales... ';
		$query = "CREATE TABLE T_locales (
				loc_locale varchar(20) NOT NULL default '',
				loc_charset varchar(15) NOT NULL default 'iso-8859-1',
				loc_datefmt varchar(10) NOT NULL default 'y-m-d',
				loc_timefmt varchar(10) NOT NULL default 'H:i:s',
				loc_name varchar(40) NOT NULL default '',
				loc_messages varchar(20) NOT NULL default '',
				loc_priority tinyint(4) UNSIGNED NOT NULL default '0',
				loc_enabled tinyint(4) NOT NULL default '1',
				PRIMARY KEY loc_locale( loc_locale )
			) COMMENT='saves available locales'";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Upgrading posts table... ';
		$query = "UPDATE {$tableprefix}posts
							SET post_urltitle = NULL";
		$DB->query( $query );

		$query = "ALTER TABLE {$tableprefix}posts
							CHANGE COLUMN post_date post_issue_date datetime NOT NULL default '1000-01-01 00:00:00',
							ADD COLUMN post_mod_date datetime NOT NULL default '1000-01-01 00:00:00'
										AFTER post_issue_date,
							CHANGE COLUMN post_lang post_locale varchar(20) NOT NULL default 'en-EU',
							DROP COLUMN post_url,
							CHANGE COLUMN post_trackbacks post_url varchar(250) NULL default NULL,
							MODIFY COLUMN post_flags SET( 'pingsdone', 'imported' ),
							ADD COLUMN post_renderers VARCHAR(179) NOT NULL default 'default',
							DROP INDEX post_date,
							ADD INDEX post_issue_date( post_issue_date ),
							ADD UNIQUE post_urltitle( post_urltitle )";
		$DB->query( $query );

		$query = "UPDATE {$tableprefix}posts
							SET post_mod_date = post_issue_date";
		$DB->query( $query );
		echo "OK.<br />\n";

		// convert given languages to locales
		convert_lang_to_locale( "{$tableprefix}posts", 'post_locale', 'ID' );

		echo 'Upgrading blogs table... ';
		$query = "ALTER TABLE T_blogs
							CHANGE blog_lang blog_locale varchar(20) NOT NULL default 'en-EU',
							CHANGE blog_roll blog_notes TEXT NULL,
							MODIFY COLUMN blog_default_skin VARCHAR(30) NOT NULL DEFAULT 'custom',
							DROP COLUMN blog_filename,
							ADD COLUMN blog_access_type VARCHAR(10) NOT NULL DEFAULT 'index.php' AFTER blog_locale,
							ADD COLUMN blog_force_skin tinyint(1) NOT NULL default 0 AFTER blog_default_skin,
							ADD COLUMN blog_in_bloglist tinyint(1) NOT NULL DEFAULT 1 AFTER blog_disp_bloglist,
							ADD COLUMN blog_links_blog_ID INT(4) NOT NULL DEFAULT 0,
							ADD UNIQUE KEY blog_stub (blog_stub)";
		$DB->query( $query );

		$query = "UPDATE T_blogs
							SET blog_access_type = 'stub',
									blog_default_skin = 'custom'";
		$DB->query( $query );

		echo "OK.<br />\n";

		// convert given languages to locales
		convert_lang_to_locale( 'T_blogs', 'blog_locale', 'blog_ID' );


		echo 'Converting settings table... ';

		// get old settings
		$query = 'SELECT * FROM T_settings';
		$row = $DB->get_row( $query, ARRAY_A );

		#echo 'oldrow:<br />'; pre_dump($row);
		$transform = array(
			'posts_per_page' => array(5),      // note: moved to blogsettings in 2.0
			'what_to_show' => array('posts'),  // note: moved to blogsettings in 2.0
			'archive_mode' => array('monthly'),// note: moved to blogsettings in 2.0
			'time_difference' => array(0),
			'AutoBR' => array(0),
			'last_antispam_update' => array('2000-01-01 00:00:00', 'antispam_last_update'),
			'pref_newusers_grp_ID' => array($Group_Users->ID, 'newusers_grp_ID'),
			'pref_newusers_level'  => array(1, 'newusers_level'),
			'pref_newusers_canregister' => array(0, 'newusers_canregister'),
		);

		$_trans = array();
		foreach( $transform as $oldkey => $newarr )
		{
			$newname = ( isset($newarr[1]) ? $newarr[1] : $oldkey );
			if( !isset( $row[$oldkey] ) )
			{
				echo '&nbsp;&middot;Setting '.$oldkey.' not found, using defaults.<br />';
				$_trans[ $newname ] = $newarr[0];
			}
			else
			{
				$_trans[ $newname ] = $row[$oldkey];
			}
		}

		// drop old table
		$DB->query( 'DROP TABLE IF EXISTS T_settings' );

		// create new table
		$DB->query(
			'CREATE TABLE T_settings (
				set_name VARCHAR( 30 ) NOT NULL ,
				set_value VARCHAR( 255 ) NULL ,
				PRIMARY KEY ( set_name )
			)');

		// insert defaults and use transformed settings
		create_default_settings( $_trans );

		if( !isset( $tableblogusers_isuptodate ) )
		{
			echo 'Upgrading Blog-User permissions table... ';
			$query = "ALTER TABLE T_coll_user_perms
								ADD COLUMN bloguser_ismember tinyint NOT NULL default 0 AFTER bloguser_user_ID";
			$DB->query( $query );

			// Any row that is created holds at least one permission,
			// minimum permsission is to be a member, so we add that one too, to all existing rows.
			$DB->query( "UPDATE T_coll_user_perms
											SET bloguser_ismember = 1" );
			echo "OK.<br />\n";
		}

		echo 'Upgrading Comments table... ';
		$query = "ALTER TABLE T_comments
							ADD COLUMN comment_author_ID int unsigned NULL default NULL AFTER comment_status,
							MODIFY COLUMN comment_author varchar(100) NULL,
							MODIFY COLUMN comment_author_email varchar(100) NULL,
							MODIFY COLUMN comment_author_url varchar(100) NULL,
							MODIFY COLUMN comment_author_IP varchar(23) NOT NULL default ''";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Upgrading Users table... ';
		$query = "ALTER TABLE T_users ADD user_locale VARCHAR( 20 ) DEFAULT 'en-EU' NOT NULL AFTER user_yim";
		$DB->query( $query );
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '8060' );
	}


	if( $old_db_version < 8062 )
	{ // upgrade to 0.9.0.4
		echo "Checking for extra quote escaping in posts... ";
		$query = "SELECT ID, post_title, post_content
								FROM {$tableprefix}posts
							 WHERE post_title LIKE '%\\\\\\\\\'%'
									OR post_title LIKE '%\\\\\\\\\"%'
									OR post_content LIKE '%\\\\\\\\\'%'
									OR post_content LIKE '%\\\\\\\\\"%' ";
		/* FP: the above looks overkill, but MySQL is really full of surprises...
						tested on 4.0.14-nt */
		// echo $query;
		$rows = $DB->get_results( $query, ARRAY_A );
		if( $DB->num_rows )
		{
			echo 'Updating '.$DB->num_rows.' posts... ';
			foreach( $rows as $row )
			{
				// echo '<br />'.$row['post_title'];
				$query = "UPDATE {$tableprefix}posts
									SET post_title = ".$DB->quote( stripslashes( $row['post_title'] ) ).",
											post_content = ".$DB->quote( stripslashes( $row['post_content'] ) )."
									WHERE ID = ".$row['ID'];
				// echo '<br />'.$query;
				$DB->query( $query );
			}
		}
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '8062' );
	}


	if( $old_db_version < 8064 )
	{ // upgrade to 0.9.0.6
		cleanup_comment_quotes();

		set_upgrade_checkpoint( '8064' );
	}


	if( $old_db_version < 8066 )
	{	// upgrade to 0.9.1
		echo 'Adding catpost index... ';
		$DB->query( 'ALTER TABLE T_postcats ADD UNIQUE catpost ( postcat_cat_ID, postcat_post_ID )' );
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '8066' );
	}


	if( $old_db_version < 8800 )
	{ // ---------------------------------- upgrade to 1.6 "phoenix ALPHA"

		echo 'Dropping old Hitlog table... ';
		$DB->query( 'DROP TABLE IF EXISTS T_hitlog' );
		echo "OK.<br />\n";

		// New tables:
			echo 'Creating table for active sessions... ';
			$DB->query( "CREATE TABLE T_sessions (
											sess_ID        INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
											sess_key       CHAR(32) NULL,
											sess_lastseen  DATETIME NOT NULL,
											sess_ipaddress VARCHAR(15) NOT NULL DEFAULT '',
											sess_user_ID   INT(10) DEFAULT NULL,
											sess_agnt_ID   INT UNSIGNED NULL,
											sess_data      TEXT DEFAULT NULL,
											PRIMARY KEY( sess_ID )
										)" );
			echo "OK.<br />\n";


			echo 'Creating user settings table... ';
			$DB->query( "CREATE TABLE {$tableprefix}usersettings (
											uset_user_ID INT(11) UNSIGNED NOT NULL,
											uset_name    VARCHAR( 30 ) NOT NULL,
											uset_value   VARCHAR( 255 ) NULL,
											PRIMARY KEY ( uset_user_ID, uset_name )
										)");
			echo "OK.<br />\n";


			echo 'Creating plugins table... ';
			$DB->query( "CREATE TABLE T_plugins (
											plug_ID        INT(11) UNSIGNED NOT NULL auto_increment,
											plug_priority  INT(11) NOT NULL default 50,
											plug_classname VARCHAR(40) NOT NULL default '',
											PRIMARY KEY ( plug_ID )
										)");
			echo "OK.<br />\n";


			echo 'Creating table for Post Statuses... ';
			$query="CREATE TABLE {$tableprefix}poststatuses (
											pst_ID   int(11) unsigned not null AUTO_INCREMENT,
											pst_name varchar(30)      not null,
											primary key ( pst_ID )
										)";
			$DB->query( $query );
			echo "OK.<br />\n";


			echo 'Creating table for Post Types... ';
			$query="CREATE TABLE {$tableprefix}posttypes (
											ptyp_ID   int(11) unsigned not null AUTO_INCREMENT,
											ptyp_name varchar(30)      not null,
											primary key (ptyp_ID)
										)";
			$DB->query( $query );
			echo "OK.<br />\n";


			echo 'Creating table for File Meta Data... ';
			$DB->query( "CREATE TABLE T_files (
										 file_ID        int(11) unsigned  not null AUTO_INCREMENT,
										 file_root_type enum('absolute','user','group','collection') not null default 'absolute',
										 file_root_ID   int(11) unsigned  not null default 0,
										 file_path      varchar(255)      not null default '',
										 file_title     varchar(255),
										 file_alt       varchar(255),
										 file_desc      text,
										 primary key (file_ID),
										 unique file (file_root_type, file_root_ID, file_path)
									)" );
			echo "OK.<br />\n";


			echo 'Creating table for base domains... ';
			$DB->query( "CREATE TABLE T_basedomains (
										dom_ID     INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
										dom_name   VARCHAR(250) NOT NULL DEFAULT '',
										dom_status ENUM('unknown','whitelist','blacklist') NOT NULL DEFAULT 'unknown',
										dom_type   ENUM('unknown','normal','searcheng','aggregator') NOT NULL DEFAULT 'unknown',
										PRIMARY KEY (dom_ID),
										UNIQUE dom_name (dom_name)
									)" );	// fp> the unique key was only named in version 1.9. Crap. Put the name back here to save as many souls as possible. bulk has not upgraded from 0.9 yet :/
			echo "OK.<br />\n";

		set_upgrade_checkpoint( '8820' );
	}


	if( $old_db_version < 8840 )
	{

			echo 'Creating table for user agents... ';
			$DB->query( "CREATE TABLE {$tableprefix}useragents (
										agnt_ID        INT UNSIGNED NOT NULL AUTO_INCREMENT,
										agnt_signature VARCHAR(250) NOT NULL,
										agnt_type      ENUM('rss','robot','browser','unknown') DEFAULT 'unknown' NOT NULL,
										PRIMARY KEY (agnt_ID) )" );
			echo "OK.<br />\n";


			echo 'Creating table for Hit-Logs... ';
			$query = "CREATE TABLE T_hitlog (
									hit_ID             INT(11) NOT NULL AUTO_INCREMENT,
									hit_sess_ID        INT UNSIGNED,
									hit_datetime       DATETIME NOT NULL,
									hit_uri            VARCHAR(250) DEFAULT NULL,
									hit_referer_type   ENUM('search','blacklist','referer','direct','spam') NOT NULL,
									hit_referer        VARCHAR(250) DEFAULT NULL,
									hit_referer_dom_ID INT UNSIGNED DEFAULT NULL,
									hit_blog_ID        int(11) UNSIGNED NULL DEFAULT NULL,
									hit_remote_addr    VARCHAR(40) DEFAULT NULL,
									PRIMARY KEY (hit_ID),
									INDEX hit_datetime ( hit_datetime ),
									INDEX hit_blog_ID (hit_blog_ID)
								)";
			$DB->query( $query );
			echo "OK.<br />\n";


			echo 'Creating table for subscriptions... ';
			$DB->query( "CREATE TABLE T_subscriptions (
										 sub_coll_ID     int(11) unsigned    not null,
										 sub_user_ID     int(11) unsigned    not null,
										 sub_items       tinyint(1)          not null,
										 sub_comments    tinyint(1)          not null,
										 primary key (sub_coll_ID, sub_user_ID)
										)" );
			echo "OK.<br />\n";


			echo 'Creating table for blog-group permissions... ';
			$DB->query( "CREATE TABLE T_coll_group_perms (
											bloggroup_blog_ID int(11) unsigned NOT NULL default 0,
											bloggroup_group_ID int(11) unsigned NOT NULL default 0,
											bloggroup_ismember tinyint NOT NULL default 0,
											bloggroup_perm_poststatuses set('published','deprecated','protected','private','draft') NOT NULL default '',
											bloggroup_perm_delpost tinyint NOT NULL default 0,
											bloggroup_perm_comments tinyint NOT NULL default 0,
											bloggroup_perm_cats tinyint NOT NULL default 0,
											bloggroup_perm_properties tinyint NOT NULL default 0,
											bloggroup_perm_media_upload tinyint NOT NULL default 0,
											bloggroup_perm_media_browse tinyint NOT NULL default 0,
											bloggroup_perm_media_change tinyint NOT NULL default 0,
											PRIMARY KEY bloggroup_pk (bloggroup_blog_ID,bloggroup_group_ID) )" );
			echo "OK.<br />\n";


		echo 'Upgrading blogs table... ';
		$query = "ALTER TABLE T_blogs
							MODIFY COLUMN blog_ID int(11) unsigned NOT NULL auto_increment,
							MODIFY COLUMN blog_links_blog_ID INT(11) NULL DEFAULT NULL,
							CHANGE COLUMN blog_stub blog_urlname VARCHAR(255) NOT NULL DEFAULT 'urlname',
							ADD COLUMN blog_allowcomments VARCHAR(20) NOT NULL default 'post_by_post' AFTER blog_keywords,
							ADD COLUMN blog_allowblogcss TINYINT(1) NOT NULL default 1 AFTER blog_allowpingbacks,
							ADD COLUMN blog_allowusercss TINYINT(1) NOT NULL default 1 AFTER blog_allowblogcss,
							ADD COLUMN blog_stub VARCHAR(255) NOT NULL DEFAULT 'stub' AFTER blog_staticfilename,
							ADD COLUMN blog_commentsexpire INT(4) NOT NULL DEFAULT 0 AFTER blog_links_blog_ID,
							ADD COLUMN blog_media_location ENUM( 'default', 'subdir', 'custom', 'none' ) DEFAULT 'default' NOT NULL AFTER blog_commentsexpire,
							ADD COLUMN blog_media_subdir VARCHAR( 255 ) NOT NULL AFTER blog_media_location,
							ADD COLUMN blog_media_fullpath VARCHAR( 255 ) NOT NULL AFTER blog_media_subdir,
							ADD COLUMN blog_media_url VARCHAR(255) NOT NULL AFTER blog_media_fullpath,
							DROP INDEX blog_stub,
							ADD UNIQUE blog_urlname ( blog_urlname )";
		$DB->query( $query );
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '8840' );
	}


	// sam2kb>fp: We need to make sure there are no values like "blog_a.php" in blog_urlname,
	//			after this upgrade blog URLs look like $baseurl.'blog_a.php' which might be OK in 0.x version,
	//			but this config will not work in b2evo 4. Blog URLs will be broken!
	if( $old_db_version < 8850 )
	{
		echo 'Updating relative URLs... ';
		// We need to move the slashes to the end:
		$query = "UPDATE T_blogs
								 SET blog_siteurl = CONCAT( SUBSTRING(blog_siteurl,2) , '/' )
							 WHERE blog_siteurl LIKE '/%'";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Copying urlnames to stub names... ';
		$query = 'UPDATE T_blogs
							SET blog_stub = blog_urlname';
		$DB->query( $query );
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '8850' );
	}


	if( $old_db_version < 8855 )
	{
		echo 'Upgrading posts table... ';
		$query = "ALTER TABLE {$tableprefix}posts
							DROP COLUMN post_karma,
							DROP COLUMN post_autobr,
							DROP INDEX post_author,
							DROP INDEX post_issue_date,
							DROP INDEX post_category,
							CHANGE COLUMN ID post_ID int(11) unsigned NOT NULL auto_increment,
							CHANGE COLUMN post_author	post_creator_user_ID int(11) unsigned NOT NULL,
							CHANGE COLUMN post_issue_date	post_datestart datetime NOT NULL,
							CHANGE COLUMN post_mod_date	post_datemodified datetime NOT NULL,
							CHANGE COLUMN post_category post_main_cat_ID int(11) unsigned NOT NULL,
							ADD post_parent_ID				int(11) unsigned NULL AFTER post_ID,
							ADD post_lastedit_user_ID	int(11) unsigned NULL AFTER post_creator_user_ID,
							ADD post_assigned_user_ID	int(11) unsigned NULL AFTER post_lastedit_user_ID,
							ADD post_datedeadline 		datetime NULL AFTER post_datestart,
							ADD post_datecreated			datetime NULL AFTER post_datedeadline,
							ADD post_pst_ID						int(11) unsigned NULL AFTER post_status,
							ADD post_ptyp_ID					int(11) unsigned NULL AFTER post_pst_ID,
							ADD post_views						int(11) unsigned NOT NULL DEFAULT 0 AFTER post_flags,
							ADD post_commentsexpire		datetime DEFAULT NULL AFTER post_comments,
							ADD post_priority					int(11) unsigned null,
							ADD INDEX post_creator_user_ID( post_creator_user_ID ),
							ADD INDEX post_parent_ID( post_parent_ID ),
							ADD INDEX post_assigned_user_ID( post_assigned_user_ID ),
							ADD INDEX post_datestart( post_datestart ),
							ADD INDEX post_main_cat_ID( post_main_cat_ID ),
							ADD INDEX post_ptyp_ID( post_ptyp_ID ),
							ADD INDEX post_pst_ID( post_pst_ID ) ";
		$DB->query( $query );
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '8855' );
	}


	if( $old_db_version < 8860 )
	{
		echo 'Updating post data... ';
		$query = "UPDATE {$tableprefix}posts
							SET post_lastedit_user_ID = post_creator_user_ID,
									post_datecreated = post_datestart";
		$DB->query( $query );
		echo "OK.<br />\n";


		task_begin( 'Upgrading users table... ' );
		$DB->query( 'UPDATE T_users
									  SET dateYMDhour = \'2000-01-01 00:00:00\'
									WHERE dateYMDhour = \'0000-00-00 00:00:00\'' );
		$DB->query( 'ALTER TABLE T_users
							MODIFY COLUMN dateYMDhour DATETIME NOT NULL DEFAULT \'2000-01-01 00:00:00\',
							CHANGE COLUMN ID user_ID int(11) unsigned NOT NULL auto_increment,
							MODIFY COLUMN user_icq int(11) unsigned DEFAULT 0 NOT NULL,
							ADD COLUMN user_showonline tinyint(1) NOT NULL default 1 AFTER user_notify' );
		task_end();


		set_upgrade_checkpoint( '8860' );
	}


	if( $old_db_version < 8900 )
	{

		echo 'Setting new defaults... ';
		$query = 'INSERT INTO T_settings (set_name, set_value)
							VALUES
								( "reloadpage_timeout", "300" ),
								( "upload_enabled", "'.(isset($use_fileupload) ? (int)$use_fileupload : '1').'" ),
								( "upload_allowedext", "'.(isset($fileupload_allowedtypes) ? $fileupload_allowedtypes : 'jpg gif png').'" ),
								( "upload_maxkb", "'.(isset($fileupload_maxk) ? (int)$fileupload_maxk : '96').'" )
							';
		$DB->query( $query );
		// Replace "paged" mode with "posts" // note: moved to blogsettings in 2.0
		$DB->query( 'UPDATE T_settings
										SET set_value = "posts"
									WHERE set_name = "what_to_show"
									  AND set_value = "paged"' );
		echo "OK.<br />\n";


		if( !isset( $tableblogusers_isuptodate ) )
		{	// We have created the blogusers table before and it's already clean!
			echo 'Altering table for Blog-User permissions... ';
			$DB->query( 'ALTER TABLE T_coll_user_perms
										MODIFY COLUMN bloguser_blog_ID int(11) unsigned NOT NULL default 0,
										MODIFY COLUMN bloguser_user_ID int(11) unsigned NOT NULL default 0,
										ADD COLUMN bloguser_perm_media_upload tinyint NOT NULL default 0,
										ADD COLUMN bloguser_perm_media_browse tinyint NOT NULL default 0,
										ADD COLUMN bloguser_perm_media_change tinyint NOT NULL default 0' );
			echo "OK.<br />\n";
		}


		task_begin( 'Altering comments table...' );
		$DB->query( 'UPDATE T_comments
									  SET comment_date = \'2000-01-01 00:00:00\'
									WHERE comment_date = \'0000-00-00 00:00:00\'' );
		$DB->query( 'ALTER TABLE T_comments
									MODIFY COLUMN comment_date DATETIME NOT NULL DEFAULT \'2000-01-01 00:00:00\',
									MODIFY COLUMN comment_post_ID		int(11) unsigned NOT NULL default 0' );
		task_end();

		set_upgrade_checkpoint( '8900' );
	}

	if( $old_db_version < 9000 )
	{
		echo 'Altering Posts to Categories table... ';
		$DB->query( "ALTER TABLE T_postcats
									MODIFY COLUMN postcat_post_ID int(11) unsigned NOT NULL,
									MODIFY COLUMN postcat_cat_ID int(11) unsigned NOT NULL" );
		echo "OK.<br />\n";


		echo 'Altering Categories table... ';
		$DB->query( "ALTER TABLE T_categories
									MODIFY COLUMN cat_ID int(11) unsigned NOT NULL auto_increment,
									MODIFY COLUMN cat_parent_ID int(11) unsigned NULL,
									MODIFY COLUMN cat_blog_ID int(11) unsigned NOT NULL default 2" );
		echo "OK.<br />\n";


		echo 'Altering Locales table... ';
		$DB->query( 'ALTER TABLE T_locales
									ADD loc_startofweek TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER loc_timefmt' );
		echo "OK.<br />\n";


		if( !isset( $tablegroups_isuptodate ) )
		{	// We have created the groups table before and it's already clean!
			echo 'Altering Groups table... ';
			$DB->query( "ALTER TABLE T_groups
										ADD COLUMN grp_perm_admin enum('none','hidden','visible') NOT NULL default 'visible' AFTER grp_name,
										ADD COLUMN grp_perm_files enum('none','view','add','edit') NOT NULL default 'none'" );
			echo "OK.<br />\n";
		}


		echo 'Creating table for Post Links... ';
		$DB->query( "CREATE TABLE T_links (
									link_ID               int(11) unsigned  not null AUTO_INCREMENT,
									link_datecreated      datetime          not null,
									link_datemodified     datetime          not null,
									link_creator_user_ID  int(11) unsigned  not null,
									link_lastedit_user_ID int(11) unsigned  not null,
									link_item_ID          int(11) unsigned  NOT NULL,
									link_dest_item_ID     int(11) unsigned  NULL,
									link_file_ID          int(11) unsigned  NULL,
									link_ltype_ID         int(11) unsigned  NOT NULL default 1,
									link_external_url     VARCHAR(255)      NULL,
									link_title            TEXT              NULL,
									PRIMARY KEY (link_ID),
									INDEX link_item_ID( link_item_ID ),
									INDEX link_dest_item_ID (link_dest_item_ID),
									INDEX link_file_ID (link_file_ID)
								)" );
		echo "OK.<br />\n";


		echo 'Creating default Post Types... ';
		$DB->query( "
			INSERT INTO {$tableprefix}posttypes ( ptyp_ID, ptyp_name )
			VALUES ( 1, 'Post' ),
			       ( 2, 'Link' )" );
		echo "OK.<br />\n";


		set_upgrade_checkpoint( '9000' );
	}


	if( $old_db_version < 9100 )
	{	// 1.8 ALPHA

		echo 'Creating table for plugin events... ';
		$DB->query( '
			CREATE TABLE T_pluginevents(
					pevt_plug_ID INT(11) UNSIGNED NOT NULL,
					pevt_event VARCHAR(40) NOT NULL,
					pevt_enabled TINYINT NOT NULL DEFAULT 1,
					PRIMARY KEY( pevt_plug_ID, pevt_event )
				)' );
		echo "OK.<br />\n";


		echo 'Altering Links table... ';
		$DB->query( 'ALTER TABLE T_links
		             CHANGE link_item_ID link_itm_ID INT( 11 ) UNSIGNED NOT NULL,
		             CHANGE link_dest_item_ID link_dest_itm_ID INT( 11 ) UNSIGNED NULL' );
		echo "OK.<br />\n";


		if( $old_db_version >= 9000 )
		{ // sess_agnt_ID used in Phoenix-Alpha
			echo 'Altering sessions table... ';
			$query = "
					ALTER TABLE T_sessions
					 DROP COLUMN sess_agnt_ID";
			$DB->query( $query );
			echo "OK.<br />\n";
		}

		echo 'Creating table for file types... ';
		$DB->query( '
				CREATE TABLE T_filetypes (
					ftyp_ID int(11) unsigned NOT NULL auto_increment,
					ftyp_extensions varchar(30) NOT NULL,
					ftyp_name varchar(30) NOT NULL,
					ftyp_mimetype varchar(50) NOT NULL,
					ftyp_icon varchar(20) default NULL,
					ftyp_viewtype varchar(10) NOT NULL,
					ftyp_allowed tinyint(1) NOT NULL default 0,
					PRIMARY KEY (ftyp_ID)
				)' );
		echo "OK.<br />\n";

		echo 'Creating default file types... ';
		$DB->query( "INSERT INTO T_filetypes
				(ftyp_ID, ftyp_extensions, ftyp_name, ftyp_mimetype, ftyp_icon, ftyp_viewtype, ftyp_allowed)
			VALUES
				(1, 'gif', 'GIF image', 'image/gif', 'image2.png', 'image', 1),
				(2, 'png', 'PNG image', 'image/png', 'image2.png', 'image', 1),
				(3, 'jpg jpeg', 'JPEG image', 'image/jpeg', 'image2.png', 'image', 1),
				(4, 'txt', 'Text file', 'text/plain', 'document.png', 'text', 1),
				(5, 'htm html', 'HTML file', 'text/html', 'html.png', 'browser', 0),
				(6, 'pdf', 'PDF file', 'application/pdf', 'pdf.png', 'browser', 1),
				(7, 'doc', 'Microsoft Word file', 'application/msword', 'doc.gif', 'external', 1),
				(8, 'xls', 'Microsoft Excel file', 'application/vnd.ms-excel', 'xls.gif', 'external', 1),
				(9, 'ppt', 'Powerpoint', 'application/vnd.ms-powerpoint', 'ppt.gif', 'external', 1),
				(10, 'pps', 'Slideshow', 'pps', 'pps.gif', 'external', 1),
				(11, 'zip', 'ZIP archive', 'application/zip', 'zip.gif', 'external', 1),
				(12, 'php php3 php4 php5 php6', 'PHP script', 'application/x-httpd-php', 'php.gif', 'text', 0),
				(13, 'css', 'Style sheet', 'text/css', '', 'text', 1)
			" );
		echo "OK.<br />\n";

		echo 'Giving Administrator Group edit perms on files... ';
		$DB->query( 'UPDATE T_groups
		             SET grp_perm_files = "edit"
		             WHERE grp_ID = 1' );
	 	// Later versions give 'all' on install, but we won't upgrade to that for security.
		echo "OK.<br />\n";

		echo 'Giving Administrator Group full perms on media for all blogs... ';
		$DB->query( 'UPDATE T_coll_group_perms
		             SET bloggroup_perm_media_upload = 1,
		                 bloggroup_perm_media_browse = 1,
		                 bloggroup_perm_media_change = 1
		             WHERE bloggroup_group_ID = 1' );
		echo "OK.<br />\n";


		if( $old_db_version >= 9000 )
		{ // Uninstall all ALPHA (potentially incompatible) plugins
			echo 'Uninstalling all existing plugins... ';
			$DB->query( 'DELETE FROM T_plugins WHERE 1=1' );
			echo "OK.<br />\n";
		}

		// NOTE: basic plugins get installed separatly for upgrade and install..


		set_upgrade_checkpoint( '9100' );
	}


	if( $old_db_version < 9190 ) // Note: changed from 9200, to include the block below, if DB is not yet on 1.8
	{	// 1.8 ALPHA (block #2)
		echo 'Altering Posts table... ';
		$DB->query( "ALTER TABLE {$tableprefix}posts
		             CHANGE post_comments post_comment_status ENUM('disabled', 'open', 'closed') NOT NULL DEFAULT 'open'" );
		echo "OK.<br />\n";


		set_upgrade_checkpoint( '9190' );
	}


	if( $old_db_version < 9192 )
	{ // 1.8 ALPHA (block #3) - The payload that db_delta() handled before

		// This is a fix, which broke upgrade to 1.8 (from 1.6) in MySQL strict mode (inserted after 1.8 got released!):
		if( $DB->get_row( 'SHOW COLUMNS FROM T_hitlog LIKE "hit_referer_type"' ) )
		{ // a niiiiiiiice extra check :p
			task_begin( 'Deleting all "spam" hitlog entries... ' );
			$DB->query( '
					DELETE FROM T_hitlog
					 WHERE hit_referer_type = "spam"' );
			task_end();
		}

		task_begin( 'Upgrading users table... ' );
		$DB->query( 'ALTER TABLE T_users
										CHANGE COLUMN user_firstname user_firstname varchar(50) NULL,
										CHANGE COLUMN user_lastname user_lastname varchar(50) NULL,
										CHANGE COLUMN user_nickname user_nickname varchar(50) NULL,
										CHANGE COLUMN user_icq user_icq int(11) unsigned NULL,
										CHANGE COLUMN user_email user_email varchar(255) NOT NULL,
										CHANGE COLUMN user_url user_url varchar(255) NULL,
										CHANGE COLUMN user_ip user_ip varchar(15) NULL,
										CHANGE COLUMN user_domain user_domain varchar(200) NULL,
										CHANGE COLUMN user_browser user_browser varchar(200) NULL,
										CHANGE COLUMN user_aim user_aim varchar(50) NULL,
										CHANGE COLUMN user_msn user_msn varchar(100) NULL,
										CHANGE COLUMN user_yim user_yim varchar(50) NULL,
										ADD COLUMN user_allow_msgform TINYINT NOT NULL DEFAULT \'1\' AFTER user_idmode,
										ADD COLUMN user_validated TINYINT(1) NOT NULL DEFAULT 0 AFTER user_grp_ID' );
		task_end();

		task_begin( 'Creating blog settings...' );
		$DB->query( 'CREATE TABLE T_coll_settings (
															cset_coll_ID INT(11) UNSIGNED NOT NULL,
															cset_name    VARCHAR( 30 ) NOT NULL,
															cset_value   VARCHAR( 255 ) NULL,
															PRIMARY KEY ( cset_coll_ID, cset_name )
											)' );
		task_end();
		set_upgrade_checkpoint( '9192' );
	}


	if( $old_db_version < 9195 )
	{
		task_begin( 'Upgrading posts table... ' );
		$DB->query( 'ALTER TABLE '.$tableprefix.'posts
										CHANGE COLUMN post_content post_content         text NULL,
										CHANGE COLUMN post_url post_url              		VARCHAR(255) NULL DEFAULT NULL,
										CHANGE COLUMN post_renderers post_renderers     TEXT NOT NULL' );
		task_end();

		task_begin( 'Upgrading comments table... ' );
		$DB->query( 'ALTER TABLE T_comments
										CHANGE COLUMN comment_author_email comment_author_email varchar(255) NULL,
										CHANGE COLUMN comment_author_url comment_author_url varchar(255) NULL,
										ADD COLUMN comment_spam_karma TINYINT NULL AFTER comment_karma,
										ADD COLUMN comment_allow_msgform TINYINT NOT NULL DEFAULT 0 AFTER comment_spam_karma' );
		task_end();

		set_upgrade_checkpoint( '9195' );
	}


	if( $old_db_version < 9200 )
	{
		task_begin( 'Upgrading hitlog table... ' );
		$DB->query( 'ALTER TABLE T_hitlog
										CHANGE COLUMN hit_referer_type hit_referer_type   ENUM(\'search\',\'blacklist\',\'referer\',\'direct\') NOT NULL,
										ADD COLUMN hit_agnt_ID        INT UNSIGNED NULL AFTER hit_remote_addr' );
		task_end();

		task_begin( 'Upgrading post links table... ' );
		$DB->query( 'ALTER TABLE T_links
										ADD INDEX link_itm_ID( link_itm_ID ),
										ADD INDEX link_dest_itm_ID (link_dest_itm_ID)' );
		task_end();

		task_begin( 'Upgrading plugins table... ' );
		$DB->query( 'ALTER TABLE T_plugins
										CHANGE COLUMN plug_priority plug_priority        TINYINT NOT NULL default 50,
										ADD COLUMN plug_code            VARCHAR(32) NULL AFTER plug_classname,
										ADD COLUMN plug_apply_rendering ENUM( \'stealth\', \'always\', \'opt-out\', \'opt-in\', \'lazy\', \'never\' ) NOT NULL DEFAULT \'never\' AFTER plug_code,
										ADD COLUMN plug_version         VARCHAR(42) NOT NULL default \'0\' AFTER plug_apply_rendering,
										ADD COLUMN plug_status          ENUM( \'enabled\', \'disabled\', \'needs_config\', \'broken\' ) NOT NULL AFTER plug_version,
										ADD COLUMN plug_spam_weight     TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER plug_status,
										ADD UNIQUE plug_code( plug_code ),
										ADD INDEX plug_status( plug_status )' );
		task_end();

		task_begin( 'Creating plugin settings table... ' );
		$DB->query( 'CREATE TABLE T_pluginsettings (
															pset_plug_ID INT(11) UNSIGNED NOT NULL,
															pset_name VARCHAR( 30 ) NOT NULL,
															pset_value TEXT NULL,
															PRIMARY KEY ( pset_plug_ID, pset_name )
											)' );
		task_end();

		task_begin( 'Creating plugin user settings table... ' );
		$DB->query( 'CREATE TABLE T_pluginusersettings (
															puset_plug_ID INT(11) UNSIGNED NOT NULL,
															puset_user_ID INT(11) UNSIGNED NOT NULL,
															puset_name VARCHAR( 30 ) NOT NULL,
															puset_value TEXT NULL,
															PRIMARY KEY ( puset_plug_ID, puset_user_ID, puset_name )
											)' );
		task_end();

		task_begin( 'Creating scheduled tasks table... ' );
		$DB->query( 'CREATE TABLE T_cron__task(
												 ctsk_ID              int(10) unsigned      not null AUTO_INCREMENT,
												 ctsk_start_datetime  datetime              not null,
												 ctsk_repeat_after    int(10) unsigned,
												 ctsk_name            varchar(50)           not null,
												 ctsk_controller      varchar(50)           not null,
												 ctsk_params          text,
												 primary key (ctsk_ID)
											)' );
		task_end();

		task_begin( 'Creating cron log table... ' );
		$DB->query( 'CREATE TABLE T_cron__log(
															 clog_ctsk_ID              int(10) unsigned   not null,
															 clog_realstart_datetime   datetime           not null,
															 clog_realstop_datetime    datetime,
															 clog_status               enum(\'started\',\'finished\',\'error\',\'timeout\') not null default \'started\',
															 clog_messages             text,
															 primary key (clog_ctsk_ID)
											)' );
		task_end();

		task_begin( 'Upgrading blogs table... ' );
		// blog_allowpingbacks is "DEFAULT 1" in the 0.9.0.11 dump.. - changed in 0.9.2?!
		$DB->query( 'ALTER TABLE T_blogs
										ALTER COLUMN blog_allowpingbacks SET DEFAULT 0,
    								CHANGE COLUMN blog_media_subdir blog_media_subdir VARCHAR( 255 ) NULL,
										CHANGE COLUMN blog_media_fullpath blog_media_fullpath VARCHAR( 255 ) NULL,
										CHANGE COLUMN blog_media_url blog_media_url VARCHAR( 255 ) NULL' );
		task_end();


		set_upgrade_checkpoint( '9200' ); // at 1.8 "Summer Beta" release
	}


	// ____________________________ 1.9: ____________________________

	if( $old_db_version < 9290 )
	{
		echo 'Post-fix hit_referer_type == NULL... ';
		// If you've upgraded from 1.6 to 1.8 and it did not break because of strict mode, there are now NULL values for what "spam" was:
		$DB->query( '
					DELETE FROM T_hitlog
					 WHERE hit_referer_type IS NULL' );
		echo "OK.<br />\n";

		echo 'Marking administrator accounts as validated... ';
		$DB->query( '
				UPDATE T_users
				   SET user_validated = 1
				 WHERE user_grp_ID = 1' );
		echo "OK.<br />\n";

		echo 'Converting auto_prune_stats setting... ';
		$old_auto_prune_stats = $DB->get_var( '
				SELECT set_value
				  FROM T_settings
				 WHERE set_name = "auto_prune_stats"' );
		if( ! is_null($old_auto_prune_stats) && $old_auto_prune_stats < 1 )
		{ // This means it has been disabled before, so set auto_prune_stats_mode to "off"!
			$DB->query( '
					REPLACE INTO T_settings ( set_name, set_value )
					 VALUES ( "auto_prune_stats_mode", "off" )' );
		}
		echo "OK.<br />\n";

		echo 'Converting time_difference from hours to seconds... ';
		$DB->query( 'UPDATE T_settings SET set_value = set_value*3600 WHERE set_name = "time_difference"' );
		echo "OK.<br />\n";


		echo 'Updating hitlog capabilities... ';
		$DB->query( '
				ALTER TABLE '.$tableprefix.'useragents ADD INDEX agnt_type ( agnt_type )' );
		$DB->query( '
				ALTER TABLE T_hitlog
				  CHANGE COLUMN hit_referer_type hit_referer_type ENUM(\'search\',\'blacklist\',\'referer\',\'direct\',\'self\',\'admin\') NOT NULL' );
		echo "OK.<br />\n";

		echo 'Updating plugin capabilities... ';
		$DB->query( '
				ALTER TABLE T_plugins
					MODIFY COLUMN plug_status ENUM( \'enabled\', \'disabled\', \'needs_config\', \'broken\' ) NOT NULL' );
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '9290' );
	}


	if( $old_db_version < 9300 )
	{
		// This can be so long, it needs its own checkpoint protected block in case of failure
		echo 'Updating hitlog indexes... ';
		$DB->query( '
				ALTER TABLE T_hitlog
				  ADD INDEX hit_agnt_ID        ( hit_agnt_ID ),
				  ADD INDEX hit_uri            ( hit_uri ),
				  ADD INDEX hit_referer_dom_ID ( hit_referer_dom_ID )
				' );
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '9300' );
	}


	if( $old_db_version < 9310 )
	{
		echo 'Updating basedomains... ';
		$DB->query( '
				UPDATE T_basedomains
				   SET dom_status = "unknown"' );		// someone has filled this up with junk blacklists before
		$DB->query( '
				ALTER TABLE T_basedomains  ADD INDEX dom_type (dom_type)' );
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '9310' );
	}


	if( $old_db_version < 9315 )
	{
		echo 'Altering locales table... ';
		$DB->query( "ALTER TABLE T_locales CHANGE COLUMN loc_datefmt loc_datefmt varchar(20) NOT NULL default 'y-m-d'" );
		$DB->query( "ALTER TABLE T_locales CHANGE COLUMN loc_timefmt loc_timefmt varchar(20) NOT NULL default 'H:i:s'" );
		echo "OK.<br />\n";

		echo 'Creating item prerendering cache table... ';
		$DB->query( "
				CREATE TABLE {$tableprefix}item__prerendering(
					itpr_itm_ID                   INT(11) UNSIGNED NOT NULL,
					itpr_format                   ENUM('htmlbody', 'entityencoded', 'xml', 'text') NOT NULL,
					itpr_renderers                TEXT NOT NULL,
					itpr_content_prerendered      TEXT NULL,
					itpr_datemodified             TIMESTAMP NOT NULL,
					PRIMARY KEY (itpr_itm_ID, itpr_format)
				)" );
		echo "OK.<br />\n";

		echo 'Altering plugins table... ';
		$DB->query( "ALTER TABLE T_plugins ADD COLUMN plug_name            VARCHAR(255) NULL default NULL AFTER plug_version" );
		$DB->query( "ALTER TABLE T_plugins ADD COLUMN plug_shortdesc       VARCHAR(255) NULL default NULL AFTER plug_name" );
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '9315' );
	}


	if( $old_db_version < 9320 )
	{ // Dropping hit_datetime because it's very slow on INSERT (dh)
		// This can be so long, it needs its own checkpoint protected block in case of failure
		if( db_index_exists( 'T_hitlog', 'hit_datetime' ) )
		{ // only drop, if it still exists (may have been removed manually)
			echo 'Updating hitlog indexes... ';
			$DB->query( '
					ALTER TABLE T_hitlog
						DROP INDEX hit_datetime
					' );
			echo "OK.<br />\n";
		}

		set_upgrade_checkpoint( '9320' );
	}


	if( $old_db_version < 9326 )
	{
		echo 'Removing obsolete settings... ';
		$DB->query( 'DELETE FROM T_settings WHERE set_name = "upload_allowedext"' );
		echo "OK.<br />\n";

		echo 'Updating blogs... ';
		db_drop_col( 'T_blogs', 'blog_allowpingbacks' );

		// Remove and transform obsolete fields blog_pingb2evonet, blog_pingtechnorati, blog_pingweblogs, blog_pingblodotgs
		if( db_cols_exist( 'T_blogs', array('blog_pingb2evonet', 'blog_pingtechnorati', 'blog_pingweblogs', 'blog_pingblodotgs') ) )
		{
			foreach( $DB->get_results( '
					SELECT blog_ID, blog_pingb2evonet, blog_pingtechnorati, blog_pingweblogs, blog_pingblodotgs
						FROM T_blogs' ) as $row )
			{
				$ping_plugins = $DB->get_var( 'SELECT cset_value FROM T_coll_settings WHERE cset_coll_ID = '.$row->blog_ID.' AND cset_name = "ping_plugins"' );
				$ping_plugins = explode(',', $ping_plugins);
				if( $row->blog_pingb2evonet )
				{
					$ping_plugins[] = 'ping_b2evonet';
				}
				if( $row->blog_pingtechnorati || $row->blog_pingweblogs || $row->blog_pingblodotgs )
				{ // if either one of the previous pingers was enabled, add ping-o-matic:
					$ping_plugins[] = 'ping_pingomatic';
				}

				// Insert transformed/generated ping plugins collection setting:
				$ping_plugins = array_unique($ping_plugins);
				$DB->query( 'REPLACE INTO T_coll_settings
						( cset_coll_ID, cset_name, cset_value )
						VALUES ( '.$row->blog_ID.', "ping_plugins", "'.implode( ',', $ping_plugins ).'" )' );
			}
			$DB->query( 'ALTER TABLE T_blogs
					DROP COLUMN blog_pingb2evonet,
					DROP COLUMN blog_pingtechnorati,
					DROP COLUMN blog_pingweblogs,
					DROP COLUMN blog_pingblodotgs' );
		}
		echo "OK.<br />\n";


		set_upgrade_checkpoint( '9326' );
	}


	if( $old_db_version < 9328 )
	{
		echo 'Updating posts... ';
		db_add_col( "{$tableprefix}posts", 'post_notifications_status',  'ENUM("noreq","todo","started","finished") NOT NULL DEFAULT "noreq" AFTER post_flags' );
		db_add_col( "{$tableprefix}posts", 'post_notifications_ctsk_ID', 'INT(10) unsigned NULL DEFAULT NULL AFTER post_notifications_status' );
		echo "OK.<br />\n";
		set_upgrade_checkpoint( '9328' );
	}


	if( $old_db_version < 9330 )
	{
		if( db_col_exists( "{$tableprefix}posts", 'post_flags') )
		{
			echo 'Updating post notifications... ';
			$DB->query( "
				UPDATE {$tableprefix}posts
					 SET post_notifications_status = 'finished'
				 WHERE post_flags LIKE '%pingsdone%'" );
			db_drop_col( "{$tableprefix}posts", 'post_flags' );
			echo "OK.<br />\n";
		}
		set_upgrade_checkpoint( '9330' );
	}


	if( $old_db_version < 9340 )
	{
		echo 'Removing duplicate post link indexes... ';
		if( db_index_exists( 'T_links', 'link_item_ID' ) )
		{ // only drop, if it still exists (may have been removed manually)
			$DB->query( '
					ALTER TABLE T_links
						DROP INDEX link_item_ID
					' );
		}
		if( db_index_exists( 'T_links', 'link_dest_item_ID' ) )
		{ // only drop, if it still exists (may have been removed manually)
			$DB->query( '
					ALTER TABLE T_links
						DROP INDEX link_dest_item_ID
					' );
		}
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '9340' );
	}

	// ____________________________ 1.10: ____________________________

	if( $old_db_version < 9345 )
	{
		echo 'Updating post table... ';
		$DB->query( "ALTER TABLE {$tableprefix}posts CHANGE COLUMN post_content post_content MEDIUMTEXT NULL" );
		echo "OK.<br />\n";
		set_upgrade_checkpoint( '9345' );
	}

	if( $old_db_version < 9346 )
	{
		echo 'Updating prerendering table... ';
		$DB->query( "ALTER TABLE {$tableprefix}item__prerendering CHANGE COLUMN itpr_content_prerendered itpr_content_prerendered MEDIUMTEXT NULL" );
		echo "OK.<br />\n";
		set_upgrade_checkpoint( '9346' );
	}

	if( $old_db_version < 9348 )
	{
		echo 'Updating sessions table... ';
		$DB->query( 'ALTER TABLE T_sessions CHANGE COLUMN sess_data sess_data MEDIUMBLOB DEFAULT NULL' );
		echo "OK.<br />\n";
		set_upgrade_checkpoint( '9348' );
	}

	if( $old_db_version < 9350 )
	{
		echo 'Updating hitlog table... ';
		$DB->query( 'ALTER TABLE T_hitlog CHANGE COLUMN hit_referer_type hit_referer_type   ENUM(\'search\',\'blacklist\',\'spam\',\'referer\',\'direct\',\'self\',\'admin\') NOT NULL' );
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '9350' );
	}


	// TODO: "If a user has permission to edit a blog, he should be able to put files in the media folder for that blog." - see http://forums.b2evolution.net/viewtopic.php?p=36417#36417
	/*
	// blueyed>> I've came up with the following, but it's too generic IMHO
	if( $old_db_version < 9300 )
	{
		echo 'Setting automatic media perms on blogs (members can upload)... ';
		$users = $DB->query( '
				UPDATE T_users
				   SET bloguser_perm_media_upload = 1
				 WHERE bloguser_ismember = 1' );
		echo "OK.<br />\n";
	}
	*/


	// ____________________________ 2.0: ____________________________

	if( $old_db_version < 9406 )
	{
		echo 'Updating chapter url names... ';
		$DB->query( '
			ALTER TABLE T_categories
				ADD COLUMN cat_urlname VARCHAR(255) NOT NULL' );

		// Create cat_urlname from cat_name:
		// TODO: Also use it for cafelog upgrade.
		load_funcs('locales/_charset.funcs.php');
		foreach( $DB->get_results('SELECT cat_ID, cat_name FROM T_categories') as $cat )
		{
			$cat_name = trim($cat->cat_name);
			if( strlen($cat_name) )
			{
				// TODO: dh> pass locale (useful for transliteration). From main blog?
				$cat_urlname = urltitle_validate('', $cat_name, $cat->cat_ID, false, 'cat_urlname', 'cat_ID', 'T_categories');
			}
			else
			{
				$cat_urlname = 'c'.$cat->cat_ID;
			}

			$DB->query( '
				UPDATE T_categories
					 SET cat_urlname = '.$DB->quote($cat_urlname).'
				 WHERE cat_ID = '.$cat->cat_ID );
		}

		$DB->query( '
			ALTER TABLE T_categories
				ADD UNIQUE cat_urlname ( cat_urlname )' );
		echo "OK.<br />\n";

		echo 'Updating Settings... ';
		$DB->query( '
      UPDATE T_settings
         SET set_value = "disabled"
       WHERE set_name = "links_extrapath"
         AND set_value = 0' );
		$DB->query( '
      UPDATE T_settings
         SET set_value = "ymd"
       WHERE set_name = "links_extrapath"
         AND set_value <> 0' );
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '9406' );
	}


	if( $old_db_version < 9407 )
	{
		echo 'Moving general settings to blog settings... ';
		$DB->query( 'REPLACE INTO T_coll_settings( cset_coll_ID, cset_name, cset_value )
		             SELECT blog_ID, set_name, set_value
									 FROM T_blogs, T_settings
									WHERE set_name = "posts_per_page"
									   OR set_name = "what_to_show"
									   OR set_name = "archive_mode"' );
		$DB->query( 'DELETE FROM T_settings
									WHERE set_name = "posts_per_page"
									   OR set_name = "what_to_show"
									   OR set_name = "archive_mode"' );
		echo "OK.<br />\n";

		echo 'Upgrading blogs table... ';
		$query = "ALTER TABLE T_blogs
							DROP COLUMN blog_force_skin";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Upgrading groups table... ';
		$query = "ALTER TABLE T_groups
							CHANGE COLUMN grp_perm_files grp_perm_files enum('none','view','add','edit','all') NOT NULL default 'none'";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Upgrading files table... ';
		$query = "ALTER TABLE T_files
							CHANGE COLUMN file_root_type file_root_type enum('absolute','user','group','collection','skins') not null default 'absolute'";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Updating file types... ';
		// Only change this if it's close enough to a default install (non customized)
		$DB->query( "UPDATE T_filetypes
										SET ftyp_viewtype = 'text'
									WHERE ftyp_ID = 12
										AND ftyp_extensions = 'php php3 php4 php5 php6'
										AND ftyp_mimetype ='application/x-httpd-php'
										AND ftyp_icon = 'php.gif'" );
		echo "OK.<br />\n";

		echo 'Remove obsolete user settings... ';
		$DB->query( 'DELETE FROM '.$tableprefix.'usersettings
									WHERE uset_name = "plugins_disp_avail"' );
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '9407' );
	}


	if( $old_db_version < 9408 )
	{
		echo 'Creating skins table... ';
		$DB->query( 'CREATE TABLE T_skins__skin (
              skin_ID      int(10) unsigned      NOT NULL auto_increment,
              skin_name    varchar(32)           NOT NULL,
              skin_type    enum(\'normal\',\'feed\') NOT NULL default \'normal\',
              skin_folder  varchar(32)           NOT NULL,
              PRIMARY KEY skin_ID (skin_ID),
              UNIQUE skin_folder( skin_folder ),
              KEY skin_name( skin_name )
            )' );
		echo "OK.<br />\n";

		echo 'Creating skin containers table... ';
		$DB->query( 'CREATE TABLE T_skins__container (
              sco_skin_ID   int(10) unsigned      NOT NULL,
              sco_name      varchar(40)           NOT NULL,
              PRIMARY KEY (sco_skin_ID, sco_name)
            )' );
		echo "OK.<br />\n";

		echo 'Creating widgets table... ';
		$DB->query( 'CREATE TABLE T_widget (
 						wi_ID					INT(10) UNSIGNED auto_increment,
						wi_coll_ID    INT(11) UNSIGNED NOT NULL,
						wi_sco_name   VARCHAR( 40 ) NOT NULL,
						wi_order			INT(10) UNSIGNED NOT NULL,
						wi_type       ENUM( \'core\', \'plugin\' ) NOT NULL DEFAULT \'core\',
						wi_code       VARCHAR(32) NOT NULL,
						wi_params     TEXT NULL,
						PRIMARY KEY ( wi_ID ),
						UNIQUE wi_order( wi_coll_ID, wi_sco_name, wi_order )
          )' );
		echo "OK.<br />\n";

		install_basic_skins();

		echo 'Updating blogs table... ';
		$DB->query( 'ALTER TABLE T_blogs
								 ALTER COLUMN blog_allowtrackbacks SET DEFAULT 0,
									DROP COLUMN blog_default_skin,
									 ADD COLUMN blog_owner_user_ID   int(11) unsigned NOT NULL default 1 AFTER blog_name,
									 ADD COLUMN blog_skin_ID INT(10) UNSIGNED NOT NULL DEFAULT 1 AFTER blog_allowusercss' );
		echo "OK.<br />\n";


		install_basic_widgets();

		set_upgrade_checkpoint( '9408' );
	}


	if( $old_db_version < 9409 )
	{
		// Upgrade the blog access types:
		echo 'Updating blogs access types... ';
		$DB->query( 'UPDATE T_blogs
										SET blog_access_type = "absolute"
									WHERE blog_siteurl LIKE "http://%"
									   OR blog_siteurl LIKE "https://%"' );

		$DB->query( 'UPDATE T_blogs
										SET blog_access_type = "relative",
												blog_siteurl = CONCAT( blog_siteurl, blog_stub )
									WHERE blog_access_type = "stub"' );

		db_drop_col( 'T_blogs', 'blog_stub' );

		echo "OK.<br />\n";


 		echo 'Updating columns... ';
		$DB->query( "ALTER TABLE T_groups CHANGE COLUMN grp_perm_stats grp_perm_stats enum('none','user','view','edit') NOT NULL default 'none'" );

		$DB->query( "ALTER TABLE T_coll_user_perms CHANGE COLUMN bloguser_perm_poststatuses bloguser_perm_poststatuses set('published','deprecated','protected','private','draft','redirected') NOT NULL default ''" );

		$DB->query( "ALTER TABLE T_coll_group_perms CHANGE COLUMN bloggroup_perm_poststatuses bloggroup_perm_poststatuses set('published','deprecated','protected','private','draft','redirected') NOT NULL default ''" );

		$DB->query( "ALTER TABLE {$tableprefix}posts CHANGE COLUMN post_status post_status enum('published','deprecated','protected','private','draft','redirected') NOT NULL default 'published'" );
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '9409' );
	}


	if( $old_db_version < 9410 )
	{
 		echo 'Updating columns... ';
		$DB->query( "ALTER TABLE T_comments CHANGE COLUMN comment_status comment_status ENUM('published','deprecated','protected','private','draft','redirected') DEFAULT 'published' NOT NULL" );

		$DB->query( "ALTER TABLE T_sessions CHANGE COLUMN sess_data sess_data MEDIUMBLOB DEFAULT NULL" );

		$DB->query( "ALTER TABLE T_hitlog CHANGE COLUMN hit_referer_type hit_referer_type ENUM('search','blacklist','spam','referer','direct','self','admin') NOT NULL" );

		echo "OK.<br />\n";

		set_upgrade_checkpoint( '9410' );
	}


	if( $old_db_version < 9411 )
	{
		echo 'Adding default Post Types... ';
		$DB->query( "
			REPLACE INTO {$tableprefix}posttypes ( ptyp_ID, ptyp_name )
			VALUES ( 1000, 'Page' ),
						 ( 2000, 'Reserved' ),
						 ( 3000, 'Reserved' ),
						 ( 4000, 'Reserved' ),
						 ( 5000, 'Reserved' ) " );
		echo "OK.<br />\n";
		set_upgrade_checkpoint( '9411' );
	}


	if( $old_db_version < 9412 )
	{
		echo 'Adding field for post excerpts... ';
		$DB->query( "ALTER TABLE {$tableprefix}posts ADD COLUMN post_excerpt  text NULL AFTER post_content" );
		echo "OK.<br />\n";
		set_upgrade_checkpoint( '9412' );
	}

	if( $old_db_version < 9414 )
	{
		echo "Renaming tables...";
		$DB->query( "RENAME TABLE {$tableprefix}item__prerendering TO T_items__prerendering" );
		$DB->query( "RENAME TABLE {$tableprefix}poststatuses TO T_items__status" );
		$DB->query( "RENAME TABLE {$tableprefix}posttypes TO T_items__type" );
		$DB->query( "RENAME TABLE {$tableprefix}posts TO T_items__item" );
		echo "OK.<br />\n";

		echo "Creating Tag tables...";
		$DB->query( "CREATE TABLE T_items__tag (
		      tag_ID   int(11) unsigned not null AUTO_INCREMENT,
		      tag_name varchar(50) not null,
		      primary key (tag_ID),
		      UNIQUE tag_name( tag_name )
		    )" );

		$DB->query( "CREATE TABLE T_items__itemtag (
		      itag_itm_ID int(11) unsigned NOT NULL,
		      itag_tag_ID int(11) unsigned NOT NULL,
		      PRIMARY KEY (itag_itm_ID, itag_tag_ID),
		      UNIQUE tagitem ( itag_tag_ID, itag_itm_ID )
		    )" );
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '9414' );
	}


	if( $old_db_version < 9416 )
	{
		echo "Updating blogs table...";
		$DB->query( "ALTER TABLE T_blogs
									ADD COLUMN blog_advanced_perms  TINYINT(1) NOT NULL default 0 AFTER blog_owner_user_ID,
									DROP COLUMN blog_staticfilename" );
		$DB->query( "UPDATE T_blogs
									  SET blog_advanced_perms = 1" );
		echo "OK.<br />\n";

		echo "Additionnal blog permissions...";
		$DB->query( "ALTER TABLE T_coll_user_perms
									ADD COLUMN bloguser_perm_admin tinyint NOT NULL default 0 AFTER bloguser_perm_properties,
									ADD COLUMN bloguser_perm_edit  ENUM('no','own','lt','le','all','redirected') NOT NULL default 'no' AFTER bloguser_perm_poststatuses" );

		$DB->query( "ALTER TABLE T_coll_group_perms
									ADD COLUMN bloggroup_perm_admin tinyint NOT NULL default 0 AFTER bloggroup_perm_properties,
									ADD COLUMN bloggroup_perm_edit  ENUM('no','own','lt','le','all','redirected') NOT NULL default 'no' AFTER bloggroup_perm_poststatuses" );

		// Preserve full admin perms:
		$DB->query( "UPDATE T_coll_user_perms
										SET bloguser_perm_admin = 1
									WHERE bloguser_perm_properties <> 0" );
		$DB->query( "UPDATE T_coll_group_perms
										SET bloggroup_perm_admin = 1
									WHERE bloggroup_perm_properties <> 0" );

		// Preserve full edit perms:
		$DB->query( "UPDATE T_coll_user_perms
										SET bloguser_perm_edit = 'all'" );
		$DB->query( "UPDATE T_coll_group_perms
										SET bloggroup_perm_edit = 'all'" );

		echo "OK.<br />\n";

		set_upgrade_checkpoint( '9416' );
	}


	if( $old_db_version < 9500 )
	{
		task_begin( 'Normalizing columns...' );
		$DB->query( 'ALTER TABLE T_blogs
										ALTER COLUMN blog_shortname SET DEFAULT \'\',
										ALTER COLUMN blog_tagline SET DEFAULT \'\',
										CHANGE COLUMN blog_description blog_description     varchar(250) NULL default \'\',
										ALTER COLUMN blog_siteurl SET DEFAULT \'\'' );
		task_end();

		task_begin( 'Normalizing dates...' );
		$DB->query( 'UPDATE T_users
										SET dateYMDhour = \'2000-01-01 00:00:00\'
									WHERE dateYMDhour = \'0000-00-00 00:00:00\'' );
		$DB->query( 'ALTER TABLE T_users
									MODIFY COLUMN dateYMDhour DATETIME NOT NULL DEFAULT \'2000-01-01 00:00:00\'' );
		$DB->query( 'UPDATE T_comments
										SET comment_date = \'2000-01-01 00:00:00\'
									WHERE comment_date = \'0000-00-00 00:00:00\'' );
		$DB->query( 'ALTER TABLE T_comments
									MODIFY COLUMN comment_date DATETIME NOT NULL DEFAULT \'2000-01-01 00:00:00\'' );
		task_end();

		task_begin( 'Normalizing cron jobs...' );
		$DB->query( 'UPDATE T_cron__task
										SET ctsk_controller = REPLACE(ctsk_controller, "cron/_", "cron/jobs/_" )
									WHERE ctsk_controller LIKE "cron/_%"' );
		task_end();

		task_begin( 'Extending comments table...' );
		$DB->query( 'ALTER TABLE T_comments
									ADD COLUMN comment_rating     TINYINT(1) NULL DEFAULT NULL AFTER comment_content,
									ADD COLUMN comment_featured   TINYINT(1) NOT NULL DEFAULT 0 AFTER comment_rating,
									ADD COLUMN comment_nofollow   TINYINT(1) NOT NULL DEFAULT 1 AFTER comment_featured;');
		task_end();

		set_upgrade_checkpoint( '9500' );
	}


	if( $old_db_version < 9600 )
	{	// 2.2.0
		task_begin( 'Creating global cache table...' );
		$DB->query( 'CREATE TABLE T_global__cache (
							      cach_name VARCHAR( 30 ) NOT NULL ,
							      cach_cache MEDIUMBLOB NULL ,
							      PRIMARY KEY ( cach_name )
							    )' );
		task_end();

		task_begin( 'Altering posts table...' );
		$DB->query( 'ALTER TABLE T_items__item
										MODIFY COLUMN post_datestart DATETIME NOT NULL DEFAULT \'2000-01-01 00:00:00\',
										MODIFY COLUMN post_datemodified DATETIME NOT NULL DEFAULT \'2000-01-01 00:00:00\',
										ADD COLUMN post_order    float NULL AFTER post_priority,
										ADD COLUMN post_featured tinyint(1) NOT NULL DEFAULT 0 AFTER post_order,
										ADD INDEX post_order( post_order )' );
		task_end();

		set_upgrade_checkpoint( '9600' );
	}


	if( $old_db_version < 9700 )
	{	// 2.3.2
	  echo 'Creating PodCast Post Type... ';
		$DB->query( "
			REPLACE INTO T_items__type ( ptyp_ID, ptyp_name )
			VALUES ( 2000, 'Podcast' )" );
		echo "OK.<br />\n";

		// 2.4.0
	  echo 'Adding additional group permissions... ';
		$DB->query( "
	      ALTER TABLE T_groups
					ADD COLUMN grp_perm_bypass_antispam         TINYINT(1)  NOT NULL DEFAULT 0        AFTER grp_perm_blogs,
					ADD COLUMN grp_perm_xhtmlvalidation         VARCHAR(10) NOT NULL default 'always' AFTER grp_perm_bypass_antispam,
					ADD COLUMN grp_perm_xhtmlvalidation_xmlrpc  VARCHAR(10) NOT NULL default 'always' AFTER grp_perm_xhtmlvalidation,
					ADD COLUMN grp_perm_xhtml_css_tweaks        TINYINT(1)  NOT NULL DEFAULT 0        AFTER grp_perm_xhtmlvalidation_xmlrpc,
      		ADD COLUMN grp_perm_xhtml_iframes           TINYINT(1)  NOT NULL DEFAULT 0        AFTER grp_perm_xhtml_css_tweaks,
      		ADD COLUMN grp_perm_xhtml_javascript        TINYINT(1)  NOT NULL DEFAULT 0        AFTER grp_perm_xhtml_iframes,
					ADD COLUMN grp_perm_xhtml_objects           TINYINT(1)  NOT NULL DEFAULT 0        AFTER grp_perm_xhtml_javascript " );
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '9700' );
	}


	if( $old_db_version < 9800 )
	{	// 2.5.0
		echo 'Upgrading blogs table... ';
		db_drop_col( 'T_blogs', 'blog_commentsexpire' );
		echo "OK.<br />\n";

		echo 'Upgrading items table... ';
		$DB->query( "ALTER TABLE T_items__item
			CHANGE COLUMN post_urltitle post_urltitle VARCHAR(210) NULL DEFAULT NULL,
			CHANGE COLUMN post_order    post_order DOUBLE NULL,
			ADD COLUMN post_titletag  VARCHAR(255) NULL DEFAULT NULL AFTER post_urltitle,
			ADD COLUMN post_double1   DOUBLE NULL COMMENT 'Custom double value 1' AFTER post_priority,
			ADD COLUMN post_double2   DOUBLE NULL COMMENT 'Custom double value 2' AFTER post_double1,
			ADD COLUMN post_double3   DOUBLE NULL COMMENT 'Custom double value 3' AFTER post_double2,
			ADD COLUMN post_double4   DOUBLE NULL COMMENT 'Custom double value 4' AFTER post_double3,
			ADD COLUMN post_double5   DOUBLE NULL COMMENT 'Custom double value 5' AFTER post_double4,
			ADD COLUMN post_varchar1  VARCHAR(255) NULL COMMENT 'Custom varchar value 1' AFTER post_double5,
			ADD COLUMN post_varchar2  VARCHAR(255) NULL COMMENT 'Custom varchar value 2' AFTER post_varchar1,
			ADD COLUMN post_varchar3  VARCHAR(255) NULL COMMENT 'Custom varchar value 3' AFTER post_varchar2" );
		echo "OK.<br />\n";

 		echo 'Creating keyphrase table... ';
		$query = "CREATE TABLE T_track__keyphrase (
            keyp_ID      INT UNSIGNED NOT NULL AUTO_INCREMENT,
            keyp_phrase  VARCHAR( 255 ) NOT NULL,
            PRIMARY KEY        ( keyp_ID ),
            UNIQUE keyp_phrase ( keyp_phrase )
          )";
		$DB->query( $query );
		echo "OK.<br />\n";

 		echo 'Upgrading hitlog table... ';
		$query = "ALTER TABLE T_hitlog
			 CHANGE COLUMN hit_ID hit_ID              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			 CHANGE COLUMN hit_datetime hit_datetime  DATETIME NOT NULL DEFAULT '2000-01-01 00:00:00',
			 ADD COLUMN hit_keyphrase_keyp_ID         INT UNSIGNED DEFAULT NULL AFTER hit_referer_dom_ID,
			 ADD INDEX hit_remote_addr ( hit_remote_addr ),
			 ADD INDEX hit_sess_ID        ( hit_sess_ID )";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Upgrading sessions table... ';
		$DB->query( "ALTER TABLE T_sessions
			ALTER COLUMN sess_lastseen SET DEFAULT '2000-01-01 00:00:00',
			ADD COLUMN sess_hitcount  INT(10) UNSIGNED NOT NULL DEFAULT 1 AFTER sess_key,
			ADD KEY sess_user_ID (sess_user_ID)" );
		echo "OK.<br />\n";

		echo 'Creating goal tracking table... ';
    $DB->query( "CREATE TABLE T_track__goal(
					  goal_ID int(10) unsigned NOT NULL auto_increment,
					  goal_name varchar(50) default NULL,
					  goal_key varchar(32) default NULL,
					  goal_redir_url varchar(255) default NULL,
					  goal_default_value double default NULL,
					  PRIMARY KEY (goal_ID),
					  UNIQUE KEY goal_key (goal_key)
          )" );

    $DB->query( "CREATE TABLE T_track__goalhit (
					  ghit_ID int(10) unsigned NOT NULL auto_increment,
					  ghit_goal_ID    int(10) unsigned NOT NULL,
					  ghit_hit_ID     int(10) unsigned NOT NULL,
					  ghit_params     TEXT default NULL,
					  PRIMARY KEY  (ghit_ID),
					  KEY ghit_goal_ID (ghit_goal_ID),
					  KEY ghit_hit_ID (ghit_hit_ID)
         )" );
		echo "OK.<br />\n";

		set_upgrade_checkpoint( '9800' );
	}


	if( $old_db_version < 9900 )
	{	// 3.0 part 1
		task_begin( 'Updating keyphrases in hitlog table... ' );
		flush();
		load_class( 'sessions/model/_hit.class.php', 'Hit' );
		$sql = 'SELECT SQL_NO_CACHE hit_ID, hit_referer
  		          FROM T_hitlog
   		         WHERE hit_referer_type = "search"
		           AND hit_keyphrase_keyp_ID IS NULL'; // this line just in case we crashed in the middle, so we restart where we stopped
		$rows = $DB->get_results( $sql, OBJECT, 'get all search hits' );
		foreach( $rows as $row )
		{
			$params = Hit::extract_params_from_referer( $row->hit_referer );
			if( empty( $params['keyphrase'] ) )
			{
				continue;
			}

			$DB->begin();

			$sql = 'SELECT keyp_ID
			          FROM T_track__keyphrase
			         WHERE keyp_phrase = '.$DB->quote($params['keyphrase']);
			$keyp_ID = $DB->get_var( $sql, 0, 0, 'Get keyphrase ID' );

			if( empty( $keyp_ID ) )
			{
				$sql = 'INSERT INTO T_track__keyphrase( keyp_phrase )
				        VALUES ('.$DB->quote($params['keyphrase']).')';
				$DB->query( $sql, 'Add new keyphrase' );
				$keyp_ID = $DB->insert_id;
			}

			$DB->query( 'UPDATE T_hitlog
			                SET hit_keyphrase_keyp_ID = '.$keyp_ID.'
			              WHERE hit_ID = '.$row->hit_ID, 'Update hit' );

			$DB->commit();
			echo ". \n";
		}
		task_end();

		task_begin( 'Upgrading widgets table... ' );
		$DB->query( "ALTER TABLE T_widget
			CHANGE COLUMN wi_order wi_order INT(10) NOT NULL" );
		task_end();

		task_begin( 'Upgrading Files table... ' );
		$DB->query( "ALTER TABLE T_files
								CHANGE COLUMN file_root_type file_root_type enum('absolute','user','collection','shared','skins') not null default 'absolute'" );
		task_end();

		set_upgrade_checkpoint( '9900' );
	}

	if( $old_db_version < 9910 )
	{	// 3.0 part 2

		task_begin( 'Upgrading Blogs table... ' );
		$DB->query( "ALTER TABLE T_blogs CHANGE COLUMN blog_name blog_name varchar(255) NOT NULL default ''" );
		task_end();

		task_begin( 'Adding new Post Types...' );
		$DB->query( "
			REPLACE INTO T_items__type( ptyp_ID, ptyp_name )
			VALUES ( 1500, 'Intro-Main' ),
						 ( 1520, 'Intro-Cat' ),
						 ( 1530, 'Intro-Tag' ),
						 ( 1570, 'Intro-Sub' ),
						 ( 1600, 'Intro-All' ) " );
		task_end();

		task_begin( 'Updating User table' );
		$DB->query( "ALTER TABLE T_users
									ADD COLUMN user_avatar_file_ID int(10) unsigned default NULL AFTER user_validated" );
		task_end();

		task_begin( 'Creating table for User field definitions' );
		$DB->query( "CREATE TABLE T_users__fielddefs (
				ufdf_ID int(10) unsigned NOT NULL,
				ufdf_type char(8) NOT NULL,
				ufdf_name varchar(255) collate latin1_general_ci NOT NULL,
				PRIMARY KEY  (ufdf_ID)
			)" );
		task_end();

		task_begin( 'Creating default field definitions...' );
		$DB->query( "
	    INSERT INTO T_users__fielddefs (ufdf_ID, ufdf_type, ufdf_name)
			 VALUES ( 10000, 'email',    'MSN/Live IM'),
							( 10100, 'word',     'Yahoo IM'),
							( 10200, 'word',     'AOL AIM'),
							( 10300, 'number',   'ICQ ID'),
							( 40000, 'phone',    'Skype'),
							( 50000, 'phone',    'Main phone'),
							( 50100, 'phone',    'Cell phone'),
							( 50200, 'phone',    'Office phone'),
							( 50300, 'phone',    'Home phone'),
							( 60000, 'phone',    'Office FAX'),
							( 60100, 'phone',    'Home FAX'),
							(100000, 'url',      'Website'),
							(100100, 'url',      'Blog'),
							(110000, 'url',      'Linkedin'),
							(120000, 'url',      'Twitter'),
							(130100, 'url',      'Facebook'),
							(130200, 'url',      'Myspace'),
							(140000, 'url',      'Flickr'),
							(150000, 'url',      'YouTube'),
							(160000, 'url',      'Digg'),
							(160100, 'url',      'StumbleUpon'),
							(200000, 'text',     'Role'),
							(200100, 'text',     'Company/Org.'),
							(200200, 'text',     'Division'),
							(211000, 'text',     'VAT ID'),
							(300000, 'text',     'Main address'),
							(300300, 'text',     'Home address');" );
		task_end();

		task_begin( 'Creating table for User fields...' );
		$DB->query( "CREATE TABLE T_users__fields (
				uf_ID      int(10) unsigned NOT NULL auto_increment,
			  uf_user_ID int(10) unsigned NOT NULL,
			  uf_ufdf_ID int(10) unsigned NOT NULL,
			  uf_varchar varchar(255) NOT NULL,
			  PRIMARY KEY (uf_ID)
			)" );
		task_end();

		set_upgrade_checkpoint( '9910' );
	}

	if( $old_db_version < 9920 )
	{	// 3.1
		task_begin( 'Upgrading Posts table... ' );
		// This is for old posts that may have a post type of NULL which should never happen. ptyp 1 is for regular posts
		$DB->query( "UPDATE T_items__item
										SET post_ptyp_ID = 1
									WHERE post_ptyp_ID IS NULL" );
		$DB->query( "ALTER TABLE T_items__item
							CHANGE COLUMN post_ptyp_ID post_ptyp_ID int(10) unsigned NOT NULL DEFAULT 1" );
		task_end();

		task_begin( 'Upgrading Categories table... ' );
		$DB->query( "ALTER TABLE T_categories
			CHANGE COLUMN cat_name cat_name varchar(255) NOT NULL,
			CHANGE COLUMN cat_description cat_description varchar(255) NULL DEFAULT NULL" );
		db_add_col( 'T_categories', 'cat_order', 'int(11) NULL DEFAULT NULL AFTER cat_description' );
		db_add_index( 'T_categories', 'cat_order', 'cat_order' );

		$DB->query( "UPDATE T_categories
					SET cat_order = cat_ID" );
		task_end();

		task_begin( 'Upgrading widgets table... ' );
		db_add_col( 'T_widget', 'wi_enabled', 'tinyint(1) NOT NULL DEFAULT 1 AFTER wi_order' );
		task_end();
	}
	if( $old_db_version < 9930 )
	{	// 3.1 continued
		task_begin( 'Updating item types...' );
		$DB->query( "
			REPLACE INTO T_items__type ( ptyp_ID, ptyp_name )
			VALUES ( 3000, 'Sidebar link' )" );
		echo "OK.<br />\n";
		task_end();

		task_begin( 'Updating items table...' );
		$DB->query( "ALTER TABLE T_items__item ENGINE=innodb" );	// fp> hum... this originally was a test :)
		task_end();

		task_begin( 'Creating versions table...' );
		$DB->query( "CREATE TABLE T_items__version (
	            iver_itm_ID        INT UNSIGNED NOT NULL ,
	            iver_edit_user_ID  INT UNSIGNED NOT NULL ,
	            iver_edit_datetime DATETIME NOT NULL ,
	            iver_status        ENUM('published','deprecated','protected','private','draft','redirected') NULL ,
	            iver_title         TEXT NULL ,
	            iver_content       MEDIUMTEXT NULL ,
	            INDEX iver_itm_ID ( iver_itm_ID )
	            ) ENGINE = innodb" );
		task_end();

		task_begin( 'Updating group permissions...' );
		$DB->query( "UPDATE T_groups
										SET grp_perm_xhtml_css_tweaks = 1
									WHERE grp_ID <= 3" );
		task_end();

		set_upgrade_checkpoint( '9930' );
	}

	if( $old_db_version < 9940 )
	{	// 3.2
		task_begin( 'Updating hitlog table...' );
		$DB->query( "ALTER TABLE T_hitlog ADD COLUMN hit_serprank INT UNSIGNED DEFAULT NULL AFTER hit_keyphrase_keyp_ID" );
		task_end();

		task_begin( 'Updating versions table...' );
		$DB->query( "ALTER TABLE T_items__version
								CHANGE COLUMN iver_edit_user_ID iver_edit_user_ID  INT UNSIGNED NULL" );
		task_end();
	}

	if( $old_db_version < 9950 )
	{	// 3.3
		task_begin( 'Altering Blogs table... ' );
		$DB->query( "ALTER TABLE T_blogs CHANGE COLUMN blog_shortname blog_shortname varchar(255) default ''" );
		task_end();

		task_begin( 'Altering default dates... ' );
		$DB->query( "ALTER TABLE T_links
      ALTER COLUMN link_datecreated SET DEFAULT '2000-01-01 00:00:00',
      ALTER COLUMN link_datemodified SET DEFAULT '2000-01-01 00:00:00'" );
		$DB->query( "ALTER TABLE T_cron__task
      ALTER COLUMN ctsk_start_datetime SET DEFAULT '2000-01-01 00:00:00'" );
		$DB->query( "ALTER TABLE T_cron__log
      ALTER COLUMN clog_realstart_datetime SET DEFAULT '2000-01-01 00:00:00'" );
		task_end();

 		task_begin( 'Altering Items table... ' );
		$DB->query( "ALTER TABLE T_items__item
			ADD COLUMN post_metadesc VARCHAR(255) NULL DEFAULT NULL AFTER post_titletag,
			ADD COLUMN post_metakeywords VARCHAR(255) NULL DEFAULT NULL AFTER post_metadesc,
			ADD COLUMN post_editor_code VARCHAR(32) NULL COMMENT 'Plugin code of the editor used to edit this post' AFTER post_varchar3" );
		task_end();

		task_begin( 'Forcing AutoP posts to html editor...' );
		$DB->query( 'UPDATE T_items__item
											SET post_editor_code = "html"
										WHERE post_renderers = "default"
											 OR post_renderers LIKE "%b2WPAutP%"' );
		task_end();

		set_upgrade_checkpoint( '9950' );
	}

	if( $old_db_version < 9960 )
	{	// 3.3

		echo "Renaming tables...";
		$DB->save_error_state();
		$DB->halt_on_error = false;
		$DB->show_errors = false;
		$DB->query( "ALTER TABLE {$tableprefix}users_fields RENAME TO T_users__fields" );
		$DB->restore_error_state();
		echo "OK.<br />\n";

		// fp> The following is more tricky to do with CHARACTER SET. During upgrade, we don't know what the admin actually wants.
		task_begin( 'Making sure all tables use desired storage ENGINE as specified in the b2evo schema...' );
		foreach( $schema_queries as $table_name=>$table_def )
		{
			if( $DB->query( 'SHOW TABLES LIKE \''.$table_name.'\'' )
				&& preg_match( '/\sENGINE\s*=\s*([a-z]+)/is', $table_def[1], $matches ) )
			{	// If the table exists and has an ENGINE definition:
				echo $table_name.':'.$matches[1].'<br />';
				$DB->query( "ALTER TABLE $table_name ENGINE = ".$matches[1] );
			}
		}
		task_end();

		set_upgrade_checkpoint( '9960' );
	}

	if( $old_db_version < 9970 )
	{	// 4.0 part 1

		// For create_default_currencies() and create_default_countries():
		require_once dirname(__FILE__).'/_functions_create.php';

		task_begin( 'Creating table for default currencies... ' );
		$DB->query( "CREATE TABLE T_currency (
				curr_ID int(10) unsigned NOT NULL auto_increment,
				curr_code char(3) NOT NULL,
				curr_shortcut varchar(30) NOT NULL,
				curr_name varchar(40) NOT NULL,
				PRIMARY KEY curr_ID (curr_ID),
				UNIQUE curr_code (curr_code)
			) ENGINE = innodb" );
		task_end();

		create_default_currencies();

		task_begin( 'Creating table for default countries... ' );
		$DB->query( "CREATE TABLE T_country (
				ctry_ID int(10) unsigned NOT NULL auto_increment,
				ctry_code char(2) NOT NULL,
				ctry_name varchar(40) NOT NULL,
				ctry_curr_ID int(10) unsigned,
				PRIMARY KEY ctry_ID (ctry_ID),
				UNIQUE ctry_code (ctry_code)
			) ENGINE = innodb" );
		task_end();

		create_default_countries();

		task_begin( 'Upgrading user permissions table... ' );
		$DB->query( "ALTER TABLE T_coll_user_perms
			ADD COLUMN bloguser_perm_page		tinyint NOT NULL default 0 AFTER bloguser_perm_media_change,
			ADD COLUMN bloguser_perm_intro		tinyint NOT NULL default 0 AFTER bloguser_perm_page,
			ADD COLUMN bloguser_perm_podcast	tinyint NOT NULL default 0 AFTER bloguser_perm_intro,
			ADD COLUMN bloguser_perm_sidebar	tinyint NOT NULL default 0 AFTER bloguser_perm_podcast" );
		task_end();

		task_begin( 'Upgrading group permissions table... ' );
		$DB->query( "ALTER TABLE T_coll_group_perms
			ADD COLUMN bloggroup_perm_page		tinyint NOT NULL default 0 AFTER bloggroup_perm_media_change,
			ADD COLUMN bloggroup_perm_intro		tinyint NOT NULL default 0 AFTER bloggroup_perm_page,
			ADD COLUMN bloggroup_perm_podcast	tinyint NOT NULL default 0 AFTER bloggroup_perm_intro,
			ADD COLUMN bloggroup_perm_sidebar	tinyint NOT NULL default 0 AFTER bloggroup_perm_podcast" );
		task_end();

		task_begin( 'Upgrading users table... ' );
		$DB->query( "ALTER TABLE T_users
			ADD COLUMN user_ctry_ID int(10) unsigned NULL AFTER user_avatar_file_ID" );
		task_end();

		// Creating tables for messaging module

		task_begin( 'Creating table for message threads... ' );
		$DB->query( "CREATE TABLE T_messaging__thread (
			thrd_ID int(10) unsigned NOT NULL auto_increment,
			thrd_title varchar(255) NOT NULL,
			thrd_datemodified datetime NOT NULL,
			PRIMARY KEY thrd_ID (thrd_ID)
		) ENGINE = innodb" );
		task_end();

		task_begin( 'Creating table for messagee... ' );
		$DB->query( "CREATE TABLE T_messaging__message (
			msg_ID int(10) unsigned NOT NULL auto_increment,
			msg_author_user_ID int(10) unsigned NOT NULL,
			msg_datetime datetime NOT NULL,
			msg_thread_ID int(10) unsigned NOT NULL,
			msg_text text NULL,
			PRIMARY KEY msg_ID (msg_ID)
		) ENGINE = innodb" );
		task_end();

		task_begin( 'Creating table for message thread statuses... ' );
		$DB->query( "CREATE TABLE T_messaging__threadstatus (
			tsta_thread_ID int(10) unsigned NOT NULL,
			tsta_user_ID int(10) unsigned NOT NULL,
			tsta_first_unread_msg_ID int(10) unsigned NULL,
			INDEX(tsta_user_ID)
		) ENGINE = innodb" );
		task_end();

		task_begin( 'Creating table for messaging contacts... ' );
		$DB->query( "CREATE TABLE T_messaging__contact (
			mct_from_user_ID int(10) unsigned NOT NULL,
			mct_to_user_ID int(10) unsigned NOT NULL,
			mct_blocked tinyint(1) default 0,
			mct_last_contact_datetime datetime NOT NULL,
			PRIMARY KEY mct_PK (mct_from_user_ID, mct_to_user_ID)
		) ENGINE = innodb" );
		task_end();

		task_begin( 'Upgrading skins table... ' );
		$DB->query( "ALTER TABLE T_skins__skin
						MODIFY skin_type enum('normal','feed','sitemap') NOT NULL default 'normal'" );
		task_end();

		task_begin( 'Setting skin type of sitemap skin to "sitemap"... ' );
		$DB->query( "UPDATE T_skins__skin
						SET skin_type = 'sitemap'
						WHERE skin_folder = '_sitemap'" );
		task_end();

		// Creating table for pluggable permissions

		// This table gets created during upgrade to v0.8.9 at checkpoint 8050
		task_begin( 'Creating table for Group Settings... ' );
		$DB->query( "CREATE TABLE IF NOT EXISTS T_groups__groupsettings (
			gset_grp_ID INT(11) UNSIGNED NOT NULL,
			gset_name VARCHAR(30) NOT NULL,
			gset_value VARCHAR(255) NULL,
			PRIMARY KEY (gset_grp_ID, gset_name)
		) ENGINE = innodb" );
		task_end();

		// Rename T_usersettings table to T_users__usersettings
		task_begin( 'Rename T_usersettings table to T_users__usersettings... ' );
		$DB->query( 'ALTER TABLE '.$tableprefix.'usersettings RENAME TO T_users__usersettings' );
		task_end();

		set_upgrade_checkpoint( '9970' );
	}


	if( $old_db_version < 9980 )
	{	// 4.0 part 2

		task_begin( 'Upgrading posts... ' );
		$DB->query( '
			UPDATE T_items__item
			   SET post_datestart = FROM_UNIXTIME( FLOOR(UNIX_TIMESTAMP(post_datestart)/60)*60 )
			 WHERE post_datestart > NOW()' );
		db_add_col( 'T_items__item', 'post_excerpt_autogenerated', 'TINYINT NULL DEFAULT NULL AFTER post_excerpt' );
		db_add_col( 'T_items__item', 'post_dateset', 'tinyint(1) NOT NULL DEFAULT 1 AFTER post_assigned_user_ID' );
		task_end();

		task_begin( 'Upgrading countries... ' );
		db_add_col( 'T_country', 'ctry_enabled', 'tinyint(1) NOT NULL DEFAULT 1 AFTER ctry_curr_ID' );
		task_end();


		task_begin( 'Upgrading links... ' );

		// Add link_position. Temporary allow NULL, set compatibility default, then do not allow NULL.
		// TODO: dh> actually, using "teaser" for the first link and "aftermore" for the rest would make more sense (and "aftermore" should get displayed with "no-more" posts anyway).
		//           Opinions? Could be heavy to transform this though..
		// fp> no, don't change past posts unexpectedly.
		db_add_col( 'T_links', 'link_position', "varchar(10) NULL AFTER link_title" );
		$DB->query( "UPDATE T_links SET link_position = 'teaser' WHERE link_position IS NULL" );
		db_add_col( 'T_links', 'link_position', "varchar(10) NOT NULL AFTER link_title" ); // change to NOT NULL

		// Add link_order. Temporary allow NULL, use order from ID, then do not allow NULL and add UNIQUE index.
		db_add_col( 'T_links', 'link_order', 'int(11) unsigned NULL AFTER link_position' );
		$DB->query( "UPDATE T_links SET link_order = link_ID WHERE link_order IS NULL" );
		db_add_col( 'T_links', 'link_order', 'int(11) unsigned NOT NULL AFTER link_position' ); // change to NOT NULL
		db_add_index( 'T_links', 'link_itm_ID_order', 'link_itm_ID, link_order', 'UNIQUE' );

		task_end();

		task_begin( 'Upgrading sessions... ' );
		$DB->query( "ALTER TABLE T_sessions CHANGE COLUMN sess_ipaddress sess_ipaddress VARCHAR(39) NOT NULL DEFAULT ''" );
		task_end();

		set_upgrade_checkpoint( '9980' );
	}

	if( $old_db_version < 9990 )
	{	// 4.0 part 3

		task_begin( 'Upgrading hitlog... ' );

		db_add_col( 'T_hitlog', 'hit_agent_type', "ENUM('rss','robot','browser','unknown') DEFAULT 'unknown' NOT NULL AFTER hit_remote_addr" );

		if( db_col_exists('T_hitlog', 'hit_agnt_ID') )
		{
			$DB->query( 'UPDATE T_hitlog, '.$tableprefix.'useragents
			                SET hit_agent_type = agnt_type
			              WHERE hit_agnt_ID = agnt_ID
			                AND agnt_type <> "unknown"' ); // We already have the unknown as default
			db_drop_col( 'T_hitlog', 'hit_agnt_ID' );
		}
		$DB->query( 'DROP TABLE IF EXISTS '.$tableprefix.'useragents' );

		task_end();

		set_upgrade_checkpoint( '9990' );
	}

	if( $old_db_version < 10000 )
	{	// 4.0 part 4
		// Integrate comment_secret
		task_begin( 'Extending Comment table... ' );
		db_add_col( 'T_comments', 'comment_secret', 'varchar(32) NULL default NULL' );
		task_end();

		// Create T_slug table and, Insert all slugs from T_items
		task_begin( 'Create Slugs table... ' );
		$DB->query( 'CREATE TABLE IF NOT EXISTS T_slug (
						slug_ID int(10) unsigned NOT NULL auto_increment,
						slug_title varchar(255) NOT NULL COLLATE ascii_bin,
						slug_type char(6) NOT NULL DEFAULT "item",
						slug_itm_ID int(11) unsigned,
						PRIMARY KEY slug_ID (slug_ID),
						UNIQUE	slug_title (slug_title)
					) ENGINE = innodb' );
		task_end();

		task_begin( 'Making sure all posts have a slug...' );
		// Get posts with empty urltitle:
		$sql = 'SELECT post_ID, post_title
				      FROM T_items__item
				     WHERE post_urltitle IS NULL OR post_urltitle = ""';
		$rows = $DB->get_results( $sql, OBJECT, 'Get posts with empty urltitle' );
		// Create URL titles when non existent:
		foreach( $rows as $row )
		{
			// TODO: dh> pass locale (useful for transliteration).
			$DB->query( 'UPDATE T_items__item
				              SET post_urltitle = "'.urltitle_validate( '', $row->post_title, 0 ).'"
		                WHERE post_ID = '.$row->post_ID, 'Set posts urltitle' );
		}
		task_end();

		task_begin( 'Populating Slugs table... ' );
		$DB->query( 'REPLACE INTO T_slug( slug_title, slug_type, slug_itm_ID)
		              SELECT post_urltitle, "item", post_ID
							      FROM T_items__item' );
		task_end();

		task_begin( 'Add canonical and tiny slug IDs to post table...' );
		// modify post_urltitle column -> Not allow NULL value
		db_add_col( 'T_items__item', 'post_urltitle', 'VARCHAR(210) NOT NULL' );
		db_add_col( 'T_items__item', 'post_canonical_slug_ID', 'int(10) unsigned NULL default NULL after post_urltitle' );
		db_add_col( 'T_items__item', 'post_tiny_slug_ID', 'int(10) unsigned NULL default NULL after post_canonical_slug_ID' );
		task_end();

		task_begin( 'Upgrading posts...' );
		$DB->query( 'UPDATE T_items__item, T_slug
			              SET post_canonical_slug_ID = slug_ID
			            WHERE CONVERT( post_urltitle USING ASCII ) COLLATE ascii_bin = slug_title' );
		task_end();

		task_begin( 'Adding "help" slug...' );
		if( db_key_exists( 'T_slug', 'slug_title', '"help"' ) )
		{
			echo '<strong>Warning: "help" slug already exists!</strong><br /> ';
		}
		else
		{
			$DB->query( 'INSERT INTO T_slug( slug_title, slug_type )
			             VALUES( "help", "help" )', 'Add "help" slug' );
			task_end();
		}

		// fp> Next time we should use pluggable permissions instead.
		task_begin( 'Updgrading groups: Giving Administrators Group edit perms on slugs...' );
		db_add_col( 'T_groups', 'grp_perm_slugs', "enum('none','view','edit') NOT NULL default 'none'" );
		$DB->query( 'UPDATE T_groups
		             SET grp_perm_slugs = "edit"
		             WHERE grp_ID = 1' );
		task_end();

		task_begin( 'Upgrading settings table... ');
		$DB->query( 'UPDATE T_settings
		                SET set_value = 1
		              WHERE set_name = "fm_enable_roots_user"
		                    AND set_value = 0' );
		task_end();

		// New perms for comment moderation depending on status:
		task_begin( 'Upgrading Blog-User permissions...' );
		db_add_col( 'T_coll_user_perms', 'bloguser_perm_draft_cmts', 'tinyint NOT NULL default 0 AFTER bloguser_perm_comments' );
		db_add_col( 'T_coll_user_perms', 'bloguser_perm_publ_cmts', 'tinyint NOT NULL default 0 AFTER bloguser_perm_comments' );
		db_add_col( 'T_coll_user_perms', 'bloguser_perm_depr_cmts', 'tinyint NOT NULL default 0 AFTER bloguser_perm_comments' );

		if( db_col_exists( 'T_coll_user_perms', 'bloguser_perm_comments' ) )
		{ // if user had perm_comments he now gets all 3 new perms also:
			$DB->query( 'UPDATE T_coll_user_perms
						SET bloguser_perm_draft_cmts = bloguser_perm_comments,
							bloguser_perm_publ_cmts = bloguser_perm_comments,
							bloguser_perm_depr_cmts = bloguser_perm_comments');
			db_drop_col( 'T_coll_user_perms', 'bloguser_perm_comments' );
		}
		task_end();

		task_begin( 'Upgrading Blog-Group permissions...' );
		db_add_col( 'T_coll_group_perms', 'bloggroup_perm_draft_cmts', 'tinyint NOT NULL default 0 AFTER bloggroup_perm_comments' );
		db_add_col( 'T_coll_group_perms', 'bloggroup_perm_publ_cmts', 'tinyint NOT NULL default 0 AFTER bloggroup_perm_comments' );
		db_add_col( 'T_coll_group_perms', 'bloggroup_perm_depr_cmts', 'tinyint NOT NULL default 0 AFTER bloggroup_perm_comments' );

		if( db_col_exists( 'T_coll_group_perms', 'bloggroup_perm_comments' ) )
		{ // if group had perm_comments he now gets all 3 new perms also:
			$DB->query( 'UPDATE T_coll_group_perms
						SET bloggroup_perm_draft_cmts = bloggroup_perm_comments,
							bloggroup_perm_publ_cmts = bloggroup_perm_comments,
							bloggroup_perm_depr_cmts = bloggroup_perm_comments');
			db_drop_col( 'T_coll_group_perms', 'bloggroup_perm_comments' );
		}
		task_end();

		task_begin( 'Upgrading messaging permissions...' );
		$DB->query( 'ALTER TABLE T_users ALTER COLUMN user_allow_msgform SET DEFAULT "2"' );
		$DB->query( 'UPDATE T_users
					SET user_allow_msgform = 3
					WHERE user_allow_msgform = 1');
		task_end();

		task_begin( 'Upgrading currency table...' );
		$DB->query( 'ALTER TABLE T_currency ADD COLUMN curr_enabled tinyint(1) NOT NULL DEFAULT 1 AFTER curr_name' );
		task_end();

		task_begin( 'Upgrading default blog access type for new blogs...' );
		$DB->query( 'ALTER TABLE T_blogs ALTER COLUMN blog_access_type SET DEFAULT "extrapath"' );
		task_end();

		task_begin( 'Upgrading tags table...' );
		$DB->query( 'ALTER TABLE T_items__tag CHANGE COLUMN tag_name tag_name varbinary(50) not null' );
		task_end();

		// fp> I don't understand why we need to carry this out "again" but I observed the installer barking on
		// this setting missing when upgrading from older 2.x versions. I figured it would be no big deal to do it twice...
		task_begin( 'Makin sure usersettings table is InnoDB...' );
		$DB->query( 'ALTER TABLE T_users__usersettings ENGINE=innodb' );
		task_end();

		set_upgrade_checkpoint( '10000' );
	}

	if( $old_db_version < 10100 )
	{	// 4.1
		task_begin( 'Convert group permissions to pluggable permissions...' );
		// asimo>This delete query needs just in case if this version of b2evo was used, before upgrade process call
		$DB->query( 'DELETE FROM T_groups__groupsettings
						WHERE gset_name = "perm_files" OR gset_name = "perm_options" OR gset_name = "perm_templates"' );
		// Get current permission values from groups table
		$sql = 'SELECT grp_ID, grp_perm_spamblacklist, grp_perm_slugs, grp_perm_files, grp_perm_options, grp_perm_templates
				      FROM T_groups';
		$rows = $DB->get_results( $sql, OBJECT, 'Get groups converted permissions' );
		// Insert values into groupsettings table
		foreach( $rows as $row )
		{	// "IGNORE" is needed if we already created T_groups__groupsettings during upgrade to v0.8.9 at checkpoint 8050
			$DB->query( 'INSERT IGNORE INTO T_groups__groupsettings( gset_grp_ID, gset_name, gset_value )
							VALUES( '.$row->grp_ID.', "perm_spamblacklist", "'.$row->grp_perm_spamblacklist.'" ),
								( '.$row->grp_ID.', "perm_slugs", "'.$row->grp_perm_slugs.'" ),
								( '.$row->grp_ID.', "perm_files", "'.$row->grp_perm_files.'" ),
								( '.$row->grp_ID.', "perm_options", "'.$row->grp_perm_options.'" ),
								( '.$row->grp_ID.', "perm_templates", "'.$row->grp_perm_templates.'" )' );
		}

		// Drop all converted permissin colums from groups table
		db_drop_col( 'T_groups', 'grp_perm_spamblacklist' );
		db_drop_col( 'T_groups', 'grp_perm_slugs' );
		db_drop_col( 'T_groups', 'grp_perm_files' );
		db_drop_col( 'T_groups', 'grp_perm_options' );
		db_drop_col( 'T_groups', 'grp_perm_templates' );
		task_end();

		task_begin( 'Upgrading users table, adding user gender...' );
		db_add_col( 'T_users', 'user_gender', 'char(1) NULL DEFAULT NULL AFTER user_showonline' );
		task_end();

		task_begin( 'Upgrading edit timpestamp blog-user permission...' );
		db_add_col( 'T_coll_user_perms', 'bloguser_perm_edit_ts', 'tinyint NOT NULL default 0 AFTER bloguser_perm_delpost' );
		$DB->query( 'UPDATE T_coll_user_perms, T_users
							SET bloguser_perm_edit_ts = 1
							WHERE bloguser_user_ID = user_ID  AND user_level > 4' );
		task_end();

		task_begin( 'Upgrading edit timpestamp blog-group permission...' );
		db_add_col( 'T_coll_group_perms', 'bloggroup_perm_edit_ts', 'tinyint NOT NULL default 0 AFTER bloggroup_perm_delpost' );
		$DB->query( 'UPDATE T_coll_group_perms
							SET bloggroup_perm_edit_ts = 1
							WHERE bloggroup_group_ID = 1' );
		task_end();

		task_begin( 'Upgrading comments table, add trash status...' );
		$DB->query( "ALTER TABLE T_comments MODIFY COLUMN comment_status ENUM('published','deprecated','draft', 'trash') DEFAULT 'published' NOT NULL");
		task_end();

		task_begin( 'Upgrading groups admin access permission...' );
		$sql = 'SELECT grp_ID, grp_perm_admin
					FROM T_groups';
		$rows = $DB->get_results( $sql, OBJECT, 'Get groups admin perms' );
		foreach( $rows as $row )
		{
			switch( $row->grp_perm_admin )
			{
				case 'visible':
					$value = 'normal';
					break;
				case 'hidden':
					$value = 'restricted';
					break;
				default:
					$value = 'none';
			}
			// "IGNORE" is needed if we already created T_groups__groupsettings during upgrade to v0.8.9 at checkpoint 8050
			$DB->query( 'INSERT IGNORE INTO T_groups__groupsettings( gset_grp_ID, gset_name, gset_value )
							VALUES( '.$row->grp_ID.', "perm_admin", "'.$value.'" )' );
		}
		db_drop_col( 'T_groups', 'grp_perm_admin' );
		task_end();

		task_begin( 'Upgrading users table, add users source...' );
		db_add_col( 'T_users', 'user_source', 'varchar(30) NULL' );
		task_end();

		task_begin( 'Upgrading blogs table: more granularity for comment allowing...' );
		$DB->query( 'INSERT INTO T_coll_settings( cset_coll_ID, cset_name, cset_value )
						SELECT blog_ID, "allow_comments", "never"
							FROM T_blogs
							WHERE blog_allowcomments = "never"' );
		db_drop_col( 'T_blogs', 'blog_allowcomments' );
		task_end();

		task_begin( 'UUpgrading blogs table: allow_rating fields...' );
		$DB->query( 'UPDATE T_coll_settings
						SET cset_value = "any"
						WHERE cset_value = "always" AND cset_name = "allow_rating"' );
		task_end();

		task_begin( 'Upgrading links table, add link_cmt_ID...' );
		$DB->query( 'ALTER TABLE T_links
						MODIFY COLUMN link_itm_ID int(11) unsigned NULL,
						MODIFY COLUMN link_creator_user_ID int(11) unsigned NULL,
						MODIFY COLUMN link_lastedit_user_ID int(11) unsigned NULL,
						ADD COLUMN link_cmt_ID int(11) unsigned NULL COMMENT "Used for linking files to comments (comment attachments)" AFTER link_itm_ID,
						ADD INDEX link_cmt_ID ( link_cmt_ID )' );
		task_end();

		task_begin( 'Upgrading filetypes table...' );
		// get allowed filetype ids
		$sql = 'SELECT ftyp_ID
					FROM T_filetypes
					WHERE ftyp_allowed != 0';
		$allowed_ids = implode( ',', $DB->get_col( $sql, 0, 'Get allowed filetypes' ) );

		// update table column  -- this column is about who can edit the filetype: any user, registered users or only admins.
		$DB->query( 'ALTER TABLE T_filetypes
						MODIFY COLUMN ftyp_allowed enum("any","registered","admin") NOT NULL default "admin"' );

		// update ftyp_allowed column content
		$DB->query( 'UPDATE T_filetypes
						SET ftyp_allowed = "registered"
						WHERE ftyp_ID IN ('.$allowed_ids.')' );
		$DB->query( 'UPDATE T_filetypes
						SET ftyp_allowed = "admin"
						WHERE ftyp_ID NOT IN ('.$allowed_ids.')' );
		$DB->query( 'UPDATE T_filetypes
						SET ftyp_allowed = "any"
						WHERE ftyp_extensions = "gif" OR ftyp_extensions = "png" OR ftyp_extensions LIKE "%jpg%"' );

		// Add m4v file type if not exists
		if( !db_key_exists( 'T_filetypes', 'ftyp_extensions', '"m4v"' ) )
		{
			$DB->query( 'INSERT INTO T_filetypes (ftyp_extensions, ftyp_name, ftyp_mimetype, ftyp_icon, ftyp_viewtype, ftyp_allowed)
				             VALUES ("m4v", "MPEG video file", "video/x-m4v", "", "browser", "registered")', 'Add "m4v" file type' );
		}
		task_end();

		// The AdSense plugin needs to store quite long strings of data...
		task_begin( 'Upgrading collection settings table, change cset_value type...' );
		$DB->query( 'ALTER TABLE T_coll_settings
								 MODIFY COLUMN cset_name VARCHAR(50) NOT NULL,
								 MODIFY COLUMN cset_value VARCHAR(10000) NULL' );
		task_end();

		set_upgrade_checkpoint( '10100' );
	}

	if( $old_db_version < 10200 )
	{	// 4.1b
		task_begin( 'Creating table for a specific blog post subscriptions...' );
		$DB->query( "CREATE TABLE T_items__subscriptions (
						isub_item_ID  int(11) unsigned NOT NULL,
						isub_user_ID  int(11) unsigned NOT NULL,
						isub_comments tinyint(1) NOT NULL default 0 COMMENT 'The user wants to receive notifications for new comments on this post',
						PRIMARY KEY (isub_item_ID, isub_user_ID )
					) ENGINE = innodb" );
		task_end();

		task_begin( 'Upgrading comments table, add subscription fields...' );
		db_add_col( 'T_comments', 'comment_notif_status', 'ENUM("noreq","todo","started","finished") NOT NULL DEFAULT "noreq" COMMENT "Have notifications been sent for this comment? How far are we in the process?" AFTER comment_secret' );
		db_add_col( 'T_comments', 'comment_notif_ctsk_ID', 'INT(10) unsigned NULL DEFAULT NULL COMMENT "When notifications for this comment are sent through a scheduled job, what is the job ID?" AFTER comment_notif_status' );
		task_end();

		task_begin( 'Upgrading users table...' );
		db_add_col( 'T_users', 'user_notify_moderation', 'tinyint(1) NOT NULL default 0 COMMENT "Notify me by email whenever a comment is awaiting moderation on one of my blogs" AFTER user_notify' );
		db_add_col( 'T_users', 'user_unsubscribe_key', 'varchar(32) NOT NULL default "" COMMENT "A specific key, it is used when a user wants to unsubscribe from a post comments without signing in" AFTER user_notify_moderation' );
		// Set unsubscribe keys for existing users with no unsubscribe key
		$sql = 'SELECT user_ID
							FROM T_users
						 WHERE user_unsubscribe_key = ""';
		$rows = $DB->get_results( $sql, OBJECT, 'Get users with no unsubscribe key' );
		foreach( $rows as $row )
		{
			$DB->query( 'UPDATE T_users
							SET user_unsubscribe_key = "'.generate_random_key().'"
							WHERE user_ID = '.$row->user_ID );
		}
		task_end();

		task_begin( 'Upgrading settings table... ');
		$DB->query( 'INSERT INTO T_settings (set_name, set_value)
						VALUES ( "smart_hit_count", 1 )' );
		$DB->query( 'ALTER TABLE T_coll_settings
									CHANGE COLUMN cset_value cset_value   VARCHAR( 10000 ) NULL COMMENT "The AdSense plugin wants to store very long snippets of HTML"' );
  	task_end();


		set_upgrade_checkpoint( '10200' );
	}


	if( $old_db_version < 10300 )
	{	// 4.2
		task_begin( 'Upgrading user fields...' );
		$DB->query( 'ALTER TABLE T_users__fielddefs
									ADD COLUMN ufdf_required enum("hidden","optional","recommended","require") NOT NULL default "optional"');
		$DB->query( 'UPDATE T_users__fielddefs
										SET ufdf_required = "recommended"
									WHERE ufdf_name in ("Website", "Twitter", "Facebook") ' );
		$DB->query( "REPLACE INTO T_users__fielddefs (ufdf_ID, ufdf_type, ufdf_name, ufdf_required)
			 						VALUES (400000, 'text', 'About me', 'recommended');" );
		task_end();

		task_begin( 'Moving data to user fields...' );
		$DB->query( 'INSERT INTO T_users__fields( uf_user_ID, uf_ufdf_ID, uf_varchar )
								 SELECT user_ID, 10300, user_icq
									 FROM T_users
								  WHERE user_icq IS NOT NULL AND TRIM(user_icq) <> ""' );
		$DB->query( 'INSERT INTO T_users__fields( uf_user_ID, uf_ufdf_ID, uf_varchar )
								 SELECT user_ID, 10200, user_aim
									 FROM T_users
								  WHERE user_aim IS NOT NULL AND TRIM(user_aim) <> ""' );
		$DB->query( 'INSERT INTO T_users__fields( uf_user_ID, uf_ufdf_ID, uf_varchar )
								 SELECT user_ID, 10000, user_msn
									 FROM T_users
								  WHERE user_msn IS NOT NULL AND TRIM(user_msn) <> ""' );
		$DB->query( 'INSERT INTO T_users__fields( uf_user_ID, uf_ufdf_ID, uf_varchar )
								 SELECT user_ID, 10100, user_yim
									 FROM T_users
								  WHERE user_yim IS NOT NULL AND TRIM(user_yim) <> ""' );
		task_end();

		task_begin( 'Dropping obsolete user columns...' );
		$DB->query( 'ALTER TABLE T_users
									DROP COLUMN user_icq,
									DROP COLUMN user_aim,
									DROP COLUMN user_msn,
									DROP COLUMN user_yim' );
		task_end();

		// ---

		task_begin( 'Adding new user columns...' );
		$DB->query( 'ALTER TABLE T_users
									ADD COLUMN user_postcode varchar(12) NULL AFTER user_ID,
									ADD COLUMN user_age_min int unsigned NULL AFTER user_postcode,
									ADD COLUMN user_age_max int unsigned NULL AFTER user_age_min' );
		task_end();

		task_begin( 'Upgrading item table for hide teaser...' );
		$DB->query( 'ALTER TABLE T_items__item
						ADD COLUMN post_hideteaser tinyint(1) NOT NULL DEFAULT 0 AFTER post_featured');
		$DB->query( 'UPDATE T_items__item
										SET post_hideteaser = 1
									WHERE post_content LIKE "%<!--noteaser-->%"' );
		task_end();

		task_begin( 'Creating table for a specific post settings...' );
		$DB->query( "CREATE TABLE T_items__item_settings (
						iset_item_ID  int(10) unsigned NOT NULL,
						iset_name     varchar( 50 ) NOT NULL,
						iset_value    varchar( 2000 ) NULL,
						PRIMARY KEY ( iset_item_ID, iset_name )
					) ENGINE = innodb" );
		task_end();

		task_begin( 'Adding new column to comments...' );
		$DB->query( 'ALTER TABLE T_comments
									ADD COLUMN comment_in_reply_to_cmt_ID INT(10) unsigned NULL AFTER comment_status' );
		task_end();

		task_begin( 'Create table for internal searches...' );
		$DB->query( 'CREATE TABLE T_logs__internal_searches (
						isrch_ID bigint(20) NOT NULL auto_increment,
						isrch_coll_ID bigint(20) NOT NULL,
						isrch_hit_ID bigint(20) NOT NULL,
						isrch_keywords varchar(255) NOT NULL,
						PRIMARY KEY (isrch_ID)
					) ENGINE = MyISAM' );
		task_end();

		task_begin( 'Create table for comments votes...' );
		$DB->query( 'CREATE TABLE T_comments__votes (
						cmvt_cmt_ID  int(10) unsigned NOT NULL,
						cmvt_user_ID int(10) unsigned NOT NULL,
						cmvt_helpful TINYINT(1) NULL DEFAULT NULL,
						cmvt_spam    TINYINT(1) NULL DEFAULT NULL,
						PRIMARY KEY (cmvt_cmt_ID, cmvt_user_ID),
						KEY cmvt_cmt_ID (cmvt_cmt_ID),
						KEY cmvt_user_ID (cmvt_user_ID)
					) ENGINE = innodb' );
		task_end();

		task_begin( 'Adding new comments columns...' );
		$DB->query( 'ALTER TABLE T_comments
									ADD comment_helpful_addvotes INT NOT NULL DEFAULT 0 AFTER comment_nofollow ,
									ADD comment_helpful_countvotes INT UNSIGNED NOT NULL DEFAULT 0 AFTER comment_helpful_addvotes ,
									ADD comment_spam_addvotes INT NOT NULL DEFAULT 0 AFTER comment_helpful_countvotes ,
									ADD comment_spam_countvotes INT UNSIGNED NOT NULL DEFAULT 0 AFTER comment_spam_addvotes ,
									CHANGE COLUMN comment_notif_ctsk_ID comment_notif_ctsk_ID      INT(10) unsigned NULL DEFAULT NULL COMMENT "When notifications for this comment are sent through a scheduled job, what is the job ID?"');
		task_end();

		task_begin( 'Adding new user permission for spam voting...' );
		$DB->query( 'ALTER TABLE T_coll_user_perms
									ADD bloguser_perm_vote_spam_cmts tinyint NOT NULL default 0 AFTER bloguser_perm_edit_ts' );
		task_end();

		task_begin( 'Adding new group permission for spam voting...' );
		$DB->query( 'ALTER TABLE T_coll_group_perms
									ADD bloggroup_perm_vote_spam_cmts tinyint NOT NULL default 0 AFTER bloggroup_perm_edit_ts' );
		task_end();

		$DB->query( 'ALTER TABLE T_country ADD COLUMN ctry_preferred tinyint(1) NOT NULL DEFAULT 0 AFTER ctry_enabled' );

		$DB->query( 'ALTER TABLE T_items__subscriptions CHANGE COLUMN isub_comments isub_comments   tinyint(1) NOT NULL DEFAULT 0 COMMENT "The user wants to receive notifications for new comments on this post"' );

		set_upgrade_checkpoint( '10300' );
	}


	if( $old_db_version < 10400 )
	{	// 4.2 part 2
		task_begin( 'Updating "Post by Email" settings...' );
		$DB->query( 'UPDATE T_settings SET set_name = "eblog_autobr" WHERE set_name = "AutoBR"' );
		task_end();

		if( $DB->get_var('SELECT set_value FROM T_settings WHERE set_name = "eblog_enabled"') )
		{	// eblog enabled, let's create a scheduled job for it
			task_begin( 'Creating "Post by Email" scheduled job...' );
			$start_date = form_date( date2mysql($GLOBALS['localtimenow'] + 86400), '05:00:00' ); // start tomorrow
			$DB->query( '
				INSERT INTO T_cron__task ( ctsk_start_datetime, ctsk_repeat_after, ctsk_name, ctsk_controller, ctsk_params )
				VALUES ( '.$DB->quote( $start_date ).', 86400, '.$DB->quote( T_('Create posts by email') ).', '.$DB->quote( 'cron/jobs/_post_by_email.job.php' ).', '.$DB->quote( 'N;' ).' )' );
			task_end();
		}

		$DB->query( "ALTER TABLE T_hitlog
								CHANGE COLUMN hit_referer_type  hit_referer_type ENUM(  'search',  'special',  'spam',  'referer',  'direct',  'self',  'admin' ) NOT NULL,
								ADD COLUMN hit_disp VARCHAR(30) DEFAULT NULL AFTER hit_uri,
								ADD COLUMN hit_ctrl VARCHAR(30) DEFAULT NULL AFTER hit_disp,
								ADD COLUMN hit_type	ENUM('standard','rss','admin','ajax', 'service') DEFAULT 'standard' NOT NULL AFTER hit_ctrl,
								ADD COLUMN hit_response_code INT DEFAULT NULL AFTER hit_agent_type " );

		// Update ftyp_icon column
		// Last versions used a image file name for this field,
		// but from now we should use a icon name from the file /conf/_icons.php
		$DB->query( 'UPDATE T_filetypes
						SET ftyp_icon = "file_image"
						WHERE ftyp_extensions IN ( "gif", "png", "jpg jpeg" )' );
		$DB->query( 'UPDATE T_filetypes
						SET ftyp_icon = "file_document"
						WHERE ftyp_extensions = "txt"' );
		$DB->query( 'UPDATE T_filetypes
						SET ftyp_icon = "file_www"
						WHERE ftyp_extensions = "htm html"' );
		$DB->query( 'UPDATE T_filetypes
						SET ftyp_icon = "file_pdf"
						WHERE ftyp_extensions = "pdf"' );
		$DB->query( 'UPDATE T_filetypes
						SET ftyp_icon = "file_doc"
						WHERE ftyp_extensions = "doc"' );
		$DB->query( 'UPDATE T_filetypes
						SET ftyp_icon = "file_xls"
						WHERE ftyp_extensions = "xls"' );
		$DB->query( 'UPDATE T_filetypes
						SET ftyp_icon = "file_ppt"
						WHERE ftyp_extensions = "ppt"' );
		$DB->query( 'UPDATE T_filetypes
						SET ftyp_icon = "file_pps"
						WHERE ftyp_extensions = "pps"' );
		$DB->query( 'UPDATE T_filetypes
						SET ftyp_icon = "file_zip"
						WHERE ftyp_extensions = "zip"' );
		$DB->query( 'UPDATE T_filetypes
						SET ftyp_icon = "file_php"
						WHERE ftyp_extensions = "php php3 php4 php5 php6"' );
		$DB->query( 'UPDATE T_filetypes
						SET ftyp_icon = ""
						WHERE ftyp_extensions = "css"' );
		$DB->query( 'UPDATE T_filetypes
						SET ftyp_icon = "file_sound"
						WHERE ftyp_extensions IN ( "mp3", "m4a" )' );
		$DB->query( 'UPDATE T_filetypes
						SET ftyp_icon = "file_video"
						WHERE ftyp_extensions IN ( "mp4", "mov", "m4v" )' );

		task_begin( 'Upgrading user fields...' );
		// Add new fields:
		// 		"ufdf_options" to save a values of the Option list
		// 		"ufdf_duplicated" to add a several instances
		$DB->query( 'ALTER TABLE T_users__fielddefs
						ADD ufdf_options TEXT NOT NULL AFTER ufdf_name,
						ADD ufdf_duplicated tinyint(1) NOT NULL default 0' );
		// Set default values of the field "ufdf_duplicated"
		$DB->query( 'UPDATE T_users__fielddefs
						SET ufdf_duplicated = "1"
						WHERE ufdf_ID IN ( 10000, 10100, 10200, 10300, 50100, 50200, 100000, 100100 )' );
		// Add Indexes
		$DB->query( 'ALTER TABLE T_users__fields
						ADD INDEX uf_ufdf_ID ( uf_ufdf_ID ),
						ADD INDEX uf_varchar ( uf_varchar ) ' );
		task_end();

		task_begin( 'Upgrading permissions...' );
		// Group permissions
		$DB->query( 'ALTER TABLE T_coll_group_perms
						ADD bloggroup_perm_own_cmts tinyint NOT NULL default 0 AFTER bloggroup_perm_edit_ts' );
		// Set default values for Administrators & Privileged Bloggers groups
		$DB->query( 'UPDATE T_coll_group_perms
						SET bloggroup_perm_own_cmts = "1"
						WHERE bloggroup_group_ID IN ( 1, 2 )' );
		// User permissions
		$DB->query( 'ALTER TABLE T_coll_user_perms
						ADD bloguser_perm_own_cmts tinyint NOT NULL default 0 AFTER bloguser_perm_edit_ts' );
		task_end();
	}

	/*
	 * ADD UPGRADES HERE.
	 *
	 * ALL DB CHANGES MUST BE EXPLICITLY CARRIED OUT. DO NOT RELY ON SCHEMA UPDATES!
	 * Schema updates do not survive after several incremental changes.
	 *
	 * NOTE: every change that gets done here, should bump {@link $new_db_version} (by 100).
	 */



	// Just in case, make sure the db schema version is up to date at the end.
	if( $old_db_version != $new_db_version )
	{ // Update DB schema version to $new_db_version
		set_upgrade_checkpoint( $new_db_version );
	}



	// Init Caches: (it should be possible to do this with each upgrade)
	load_funcs('tools/model/_system.funcs.php');
	// We're going to need some environment in order to init caches...
	global $Settings, $Plugins;
	if( ! is_object( $Settings ) )
	{
		load_class( 'settings/model/_generalsettings.class.php', 'GeneralSettings' );
		$Settings = new GeneralSettings();
	}
	if( ! is_object( $Plugins ) )
	{
		load_class( 'plugins/model/_plugins.class.php', 'Plugins' );
		$Plugins = new Plugins();
	}
	if( !system_init_caches() )
	{
		echo "<strong>".T_('The /cache folder could not be created/written to. b2evolution will still work but without caching, which will make it operate slower than optimal.')."</strong><br />\n";
	}

	// Create default cron jobs (this can be done at each upgrade):
	require_once dirname(__FILE__).'/_functions_create.php';
	create_default_jobs( true );

	// This has to be at the end because plugin install may fail if the DB schema is not current (matching Plugins class).
	// Only new default plugins will be installed, based on $old_db_version.
	// dh> NOTE: if this fails (e.g. fatal error in one of the plugins), it will not get repeated
	install_basic_plugins( $old_db_version );


	/*
	 * -----------------------------------------------
	 * Check to make sure the DB schema is up to date:
	 * -----------------------------------------------
	 */
	$upgrade_db_deltas = array(); // This holds changes to make, if any (just all queries)

	global $debug;

	foreach( $schema_queries as $table => $query_info )
	{	// For each table in the schema, check diffs...
		if( $debug )
		{
			echo '<br />Checking table: '.$table.': ';
		}
		$updates = db_delta( $query_info[1], array('drop_column', 'drop_index'), false, true );
		if( empty($updates) )
		{
			if( $debug ) echo 'ok';
		}
		else
		{
			if( $debug ) echo 'NEEDS UPDATE!';
			foreach( $updates as $table => $queries )
			{
				foreach( $queries as $qinfo )
				{
					foreach( $qinfo['queries'] as $query )
					{ // subqueries for this query (usually one, but may include required other queries)
						$upgrade_db_deltas[] = $query;
					}
				}
			}
		}
	}

	if( $debug )
	{
		echo '<br />';
	}

	if( empty($upgrade_db_deltas) )
	{	// no upgrades needed:
		echo '<p>'.T_('The database schema is up to date.').'</p>';
	}
	else
	{	// Upgrades are needed:

		$confirmed_db_upgrade = param('confirmed', 'integer', 0); // force confirmation
		$upgrade_db_deltas_confirm_md5 = param( 'upgrade_db_deltas_confirm_md5', 'string', '' );

		if( ! $confirmed_db_upgrade )
		{
			if( ! empty($upgrade_db_deltas_confirm_md5) )
			{ // received confirmation from form
				if( $upgrade_db_deltas_confirm_md5 != md5( implode('', $upgrade_db_deltas) ) )
				{ // unlikely to happen
					echo '<p class="error">'
						.T_('The DB schema has been changed since confirmation.')
						.'</p>';
				}
				else
				{
					$confirmed_db_upgrade = true;
				}
			}
		}

		if( ! $confirmed_db_upgrade )
		{
			global $action, $locale, $form_action;
			load_class( '_core/ui/forms/_form.class.php', 'Form' );

			if( !empty( $form_action ) )
			{
				$Form = new Form( $form_action, '', 'post' );
			}
			else
			{
				$Form = new Form( NULL, '', 'post' );
			}

			$Form->begin_form( 'fform', T_('Upgrade database') );

			$Form->begin_fieldset();
			$Form->hidden( 'upgrade_db_deltas_confirm_md5', md5(implode( '', $upgrade_db_deltas )) );
			$Form->hidden( 'action', $action );
			$Form->hidden( 'locale', $locale );


			echo '<p>'.T_('The version number is correct, but we have detected changes in the database schema. This can happen with CVS versions...').'</p>';

			echo '<p>'.T_('The following database changes will be carried out. If you are not sure what this means, it will probably be alright.').'</p>';

			echo '<ul>';
			foreach( $upgrade_db_deltas as $l_delta )
			{
				#echo '<li><code>'.nl2br($l_delta).'</code></li>';
				echo '<li><pre>'.str_replace( "\t", '  ', $l_delta ).'</pre></li>';
			}
			echo '</ul>';
			$Form->submit( array( '', T_('Upgrade database!'), 'ActionButton' ) );
			$Form->end_form();

			return false;
		}

		// Alter DB to match DB schema:
		install_make_db_schema_current( true );
	}

	return true;
}


/*
 * $Log$
 * Revision 1.431  2011/10/24 13:53:05  efy-vitalij
 * added changes to T_hitlog table
 *
 * Revision 1.430  2011/10/23 09:19:42  efy-yurybakh
 * Implement new permission for comment editing
 *
 * Revision 1.429  2011/10/22 07:38:39  efy-yurybakh
 * Add a suggestion AJAX script to userfields
 *
 * Revision 1.428  2011/10/20 12:14:55  efy-yurybakh
 * Allow/disabled multiple instances of same field
 *
 * Revision 1.427  2011/10/18 12:28:13  efy-yurybakh
 * Info fields: select lists - give list of configurable options
 *
 * Revision 1.426  2011/10/18 10:18:28  efy-yurybakh
 * proper upgrade of filetypes
 *
 * Revision 1.425  2011/10/18 00:45:31  fplanque
 * Upgrade update
 *
 * Revision 1.424  2011/10/17 22:00:30  fplanque
 * cleanup
 *
 * Revision 1.423  2011/10/17 20:14:24  sam2kb
 * Post by Email converted into internal scheduled job
 *
 * Revision 1.422  2011/09/30 11:20:21  efy-yurybakh
 * Make a big sprite with all backoffice icons
 *
 * Revision 1.421  2011/09/27 13:30:15  efy-yurybakh
 * spam vote checkbox
 *
 * Revision 1.420  2011/09/25 07:06:21  efy-yurybakh
 * Implement new permission for spam voting
 *
 * Revision 1.419  2011/09/24 06:29:14  efy-yurybakh
 * add T_comments__votes in upgrade procedure
 *
 * Revision 1.418  2011/09/24 05:32:56  sam2kb
 * i18n update
 *
 * Revision 1.417  2011/09/24 05:22:05  sam2kb
 * Successully upgrades from versions: 0.8.6, 0.9.2, 1.10.3, 2.4.x and 3.3.x
 *
 * Revision 1.416  2011/09/23 01:29:04  fplanque
 * small changes
 *
 * Revision 1.415  2011/09/20 17:36:59  fplanque
 * no message
 *
 * Revision 1.414  2011/09/19 23:23:43  fplanque
 * Db fixes
 *
 * Revision 1.413  2011/09/17 22:16:05  fplanque
 * cleanup
 *
 * Revision 1.412  2011/09/15 22:34:09  fplanque
 * cleanup
 *
 * Revision 1.410  2011/09/14 23:42:16  fplanque
 * moved icq aim yim msn to additional userfields
 *
 * Revision 1.409  2011/09/14 22:18:09  fplanque
 * Enhanced addition user info fields
 *
 * Revision 1.408  2011/09/10 00:57:23  fplanque
 * doc
 *
 * Revision 1.407  2011/09/08 05:22:40  efy-asimo
 * Remove item attending and add item settings
 *
 * Revision 1.406  2011/09/04 22:13:23  fplanque
 * copyright 2011
 *
 * Revision 1.405  2011/09/04 21:32:16  fplanque
 * minor MFB 4-1
 *
 * Revision 1.404  2011/08/29 11:55:23  efy-asimo
 * DB documentation
 *
 * Revision 1.403  2011/08/29 08:51:14  efy-james
 * Default / mandatory additional fields
 *
 * Revision 1.402  2011/08/25 13:20:23  efy-james
 * Add checkbox for no teaser
 *
 * Revision 1.401  2011/08/25 07:31:14  efy-asimo
 * DB documentation
 *
 * Revision 1.400  2011/08/25 01:02:09  fplanque
 * doc/minor
 *
 * Revision 1.399  2011/08/24 12:16:43  efy-james
 * Add checkbox for hide teaser
 *
 * Revision 1.398  2011/08/23 18:34:28  fplanque
 * fix
 *
 * Revision 1.397  2011/08/12 08:29:00  efy-asimo
 * Post view count - fix, and crazy view counting option
 *
 * Revision 1.396  2011/06/26 17:55:58  sam2kb
 * Search engine stats refactoring
 * All related params moved to /inc/sessions/model/_search_engines.php
 *
 * Revision 1.395  2011/06/15 06:29:44  sam2kb
 * Relocate "set_max_execution_time" function
 *
 * Revision 1.394  2011/05/25 14:59:34  efy-asimo
 * Post attending
 *
 * Revision 1.393  2011/05/19 17:47:07  efy-asimo
 * register for updates on a specific blog post
 *
 * Revision 1.392  2011/05/05 23:30:28  fplanque
 * DB version bump
 *
 * Revision 1.391  2011/05/04 13:06:54  efy-asimo
 * upgrade script system_init_caches() - fix
 *
 * Revision 1.390  2011/05/02 23:31:11  fplanque
 * minor
 *
 * Revision 1.389  2011/04/28 08:20:55  sam2kb
 * Changed collection settings cset_value type to TEXT
 * See: http://forums.b2evolution.net/viewtopic.php?t=22068
 *
 * Revision 1.388  2011/03/15 09:34:06  efy-asimo
 * have checkboxes for enabling caching in new blogs
 * refactorize cache create/enable/disable
 *
 * Revision 1.387  2011/03/14 14:12:40  efy-asimo
 * fix upgrade script
 *
 * Revision 1.386  2011/03/10 14:54:19  efy-asimo
 * Allow file types modification & add m4v file type
 *
 * Revision 1.385  2011/03/07 08:11:04  efy-asimo
 * Create default jobbs into the scheduler
 *
 * Revision 1.384  2011/03/03 12:47:29  efy-asimo
 * comments attachments
 *
 * Revision 1.383  2011/03/02 09:48:15  efy-asimo
 * remove comment
 *
 * Revision 1.382  2011/03/02 09:45:59  efy-asimo
 * Update collection features allow_comments, disable_comments_bypost, allow_attachments, allow_rating
 *
 * Revision 1.381  2011/02/17 14:56:38  efy-asimo
 * Add user source param
 *
 * Revision 1.380  2011/02/15 15:37:00  efy-asimo
 * Change access to admin permission
 *
 * Revision 1.379  2011/02/15 06:13:49  sam2kb
 * strlen replaced with evo_strlen to support utf-8 logins and domain names
 *
 * Revision 1.378  2011/02/14 14:13:24  efy-asimo
 * Comments trash status
 *
 * Revision 1.377  2011/02/10 23:07:21  fplanque
 * minor/doc
 *
 * Revision 1.376  2011/01/06 14:31:47  efy-asimo
 * advanced blog permissions:
 *  - add blog_edit_ts permission
 *  - make the display more compact
 *
 * Revision 1.375  2011/01/02 02:20:25  sam2kb
 * typo: explicitely => explicitly
 *
 * Revision 1.374  2010/11/24 14:55:30  efy-asimo
 * Add user gender
 *
 * Revision 1.373  2010/11/04 00:48:17  fplanque
 * no message
 *
 * Revision 1.371  2010/10/19 02:00:54  fplanque
 * MFB
 *
 * Revision 1.370  2010/10/15 13:10:09  efy-asimo
 * Convert group permissions to pluggable permissions - part1
 *
 * Revision 1.369  2010/07/26 06:52:27  efy-asimo
 * MFB v-4-0
 *
 * Revision 1.368  2010/06/01 11:33:20  efy-asimo
 * Split blog_comments advanced permission (published, deprecated, draft)
 * Use this new permissions (Antispam tool,when edit/delete comments)
 *
 * Revision 1.367  2010/05/13 05:52:00  efy-asimo
 * upgrade "'fm_enable_roots_user' => '1',"
 *
 * Revision 1.366  2010/05/02 16:10:40  fplanque
 * minor
 *
 * Revision 1.365  2010/05/02 00:09:27  blueyed
 * todo: pass locale to urltitle_validate calls
 *
 * Revision 1.364  2010/04/27 19:29:39  blueyed
 * Make inner upgrades for T_slug init more reliable.
 *
 * Revision 1.363  2010/04/23 09:39:45  efy-asimo
 * "SEO setting" for help link and Groups slugs permission implementation
 *
 * Revision 1.362  2010/04/22 10:09:36  efy-asimo
 * Creating "help" slug on install and upgrade procedure
 *
 * Revision 1.361  2010/04/20 07:00:21  efy-asimo
 * Upgrading T_items__item table (urltitle & slug_title illegal mix of collation) - fix
 *
 * Revision 1.360  2010/04/12 09:41:36  efy-asimo
 * private URL shortener - task
 *
 * Revision 1.359  2010/04/07 08:26:11  efy-asimo
 * Allow multiple slugs per post - update & fix
 *
 * Revision 1.358  2010/03/29 12:25:31  efy-asimo
 * allow multiple slugs per post
 *
 * Revision 1.357  2010/03/04 15:55:17  efy-asimo
 * integrate comment_secret into the upgrade procedure
 *
 * Revision 1.356  2010/02/26 22:15:50  fplanque
 * whitespace/doc/minor
 *
 * Revision 1.355  2010/02/12 18:22:05  efy-yury
 * add atnispam query obfuscating
 *
 * Revision 1.354  2010/02/08 17:55:33  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.353  2010/01/09 18:19:06  blueyed
 * Upgrade: make upgrade of hitlog more robust in regard to already done changes.
 *
 * Revision 1.352  2010/01/03 13:45:37  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.351  2009/12/22 08:45:44  fplanque
 * fix install
 *
 * Revision 1.350  2009/12/22 02:55:06  blueyed
 * doc/todo
 *
 * Revision 1.349  2009/12/09 22:57:46  blueyed
 * todo/note
 *
 * Revision 1.348  2009/12/09 17:36:45  blueyed
 * indent
 *
 * Revision 1.347  2009/12/08 22:38:13  fplanque
 * User agent type is now saved directly into the hits table instead of a costly lookup in user agents table
 *
 * Revision 1.346  2009/12/01 21:35:56  blueyed
 * Merge from whissip: use get_row to not fetch all rows. Use proper category urlnames on upgrade.
 *
 * Revision 1.345  2009/11/30 01:22:23  fplanque
 * fix wrong version status message rigth after upgrade
 *
 * Revision 1.344  2009/11/19 10:24:48  efy-maxim
 * maintenance module - 'Upgrade Database' button support.
 *
 * Revision 1.343  2009/10/27 23:06:43  fplanque
 * doc
 *
 * Revision 1.342  2009/10/27 21:57:43  fplanque
 * minor/doc
 *
 * Revision 1.341  2009/10/17 16:31:33  efy-maxim
 * Renamed: T_groupsettings to T_groups__groupsettings, T_usersettings to T_users__usersettings
 *
 * Revision 1.340  2009/10/17 14:49:46  fplanque
 * doc
 *
 * Revision 1.339  2009/10/11 03:31:55  blueyed
 * Upgrade fixes
 *
 * Revision 1.338  2009/10/11 03:00:11  blueyed
 * Add "position" and "order" properties to attachments.
 * Position can be "teaser" or "aftermore" for now.
 * Order defines the sorting of attachments.
 * Needs testing and refinement. Upgrade might work already, be careful!
 *
 * Revision 1.337  2009/10/10 20:17:33  tblue246
 * Minor debug output layout fix
 *
 * Revision 1.336  2009/10/10 16:34:44  blueyed
 * Fix table alias
 *
 * Revision 1.335  2009/10/08 20:05:52  efy-maxim
 * Modular/Pluggable Permissions
 *
 * Revision 1.334  2009/10/07 23:43:25  fplanque
 * doc
 *
 * Revision 1.333  2009/10/04 18:26:48  blueyed
 * Add missing DB transformations, need to get added to blocks.
 *
 * Revision 1.332  2009/09/29 13:32:30  tblue246
 * OK, no DB changes for 3.3.2, moved to 4.0
 *
 * Revision 1.331  2009/09/29 03:47:07  fplanque
 * doc
 *
 * Revision 1.330  2009/09/26 13:41:54  tblue246
 * If XML feeds are disabled for a blog, still allow accessing "sitemap" skins.
 *
 * Revision 1.329  2009/09/25 20:26:26  fplanque
 * fixes/doc
 *
 * Revision 1.328  2009/09/25 14:18:22  tblue246
 * Reverting accidental commits
 *
 * Revision 1.326  2009/09/21 03:31:23  fplanque
 * made autoupgrade more verbose in debug mode
 *
 * Revision 1.325  2009/09/20 19:52:21  blueyed
 * Make ENGINE match more lax.
 *
 * Revision 1.324  2009/09/20 19:47:07  blueyed
 * Add post_excerpt_autogenerated field.
 * "text" params get unified newlines now and "excerpt" is a text param.
 * This is required for detecting if it has been changed really.
 * If something is wrong about this, please make sure that an unchanged
 * excerpt won't update the one in DB (when posting the item form).
 *
 * Revision 1.323  2009/09/19 13:27:15  blueyed
 * Fix indent
 *
 * Revision 1.322  2009/09/18 16:01:50  fplanque
 * cleanup
 *
 * Revision 1.321  2009/09/18 14:22:11  efy-maxim
 * 1. 'reply' permission in group form
 * 2. functionality to store and update contacts
 * 3. fix in misc functions
 *
 * Revision 1.320  2009/09/17 11:34:33  efy-maxim
 * reply permission in create and upgrade functionality
 *
 * Revision 1.319  2009/09/14 18:37:07  fplanque
 * doc/cleanup/minor
 *
 * Revision 1.318  2009/09/14 14:55:19  tblue246
 * Fix upgrade process
 *
 * Revision 1.317  2009/09/14 14:10:14  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.316  2009/09/13 21:29:22  blueyed
 * MySQL query cache optimization: remove information about seconds from post_datestart and item_issue_date.
 *
 * Revision 1.315  2009/09/13 21:26:50  blueyed
 * SQL_NO_CACHE for SELECT queries using T_hitlog
 *
 * Revision 1.314  2009/09/13 15:56:11  fplanque
 * minor
 *
 * Revision 1.313  2009/09/13 12:25:34  efy-maxim
 * Messaging permissions have been added to:
 * 1. Upgrader
 * 2. Group class
 * 3. Edit Group form
 *
 * Revision 1.312  2009/09/12 18:44:11  efy-maxim
 * Messaging module improvements
 *
 * Revision 1.311  2009/09/10 18:24:07  fplanque
 * doc
 *
 * Revision 1.310  2009/09/10 13:44:57  tblue246
 * Translation fixes/update
 *
 * Revision 1.309  2009/09/10 13:10:37  efy-maxim
 * int(11) has been changed to int(10) for PKs of T_country, T_currency tables
 *
 * Revision 1.308  2009/09/10 12:13:33  efy-maxim
 * Messaging Module
 *
 * Revision 1.307  2009/09/07 23:35:51  fplanque
 * cleanup
 *
 * Revision 1.306  2009/09/07 14:42:35  efy-maxim
 * Create user_ctry_ID column in T_users table in evoupgrade module
 *
 * Revision 1.305  2009/09/05 18:49:29  tblue246
 * Bad idea was a good idea: Use function call instead of duplicate INSERT statements. Meh.
 *
 * Revision 1.302  2009/09/05 12:27:20  tblue246
 * - Fix upgrade
 * - Use create_default_currencies() and create_default_countries() instead of duplicated queries.
 *
 * Revision 1.301  2009/09/05 11:29:28  efy-maxim
 * Create default currencies and countries. Upgrade currencies and countries.
 *
 * Revision 1.300  2009/08/30 00:30:52  fplanque
 * increased modularity
 *
 * Revision 1.299  2009/07/13 00:14:07  fplanque
 * fixing default dates
 *
 * Revision 1.298  2009/07/12 23:54:10  fplanque
 * rename table
 *
 * Revision 1.297  2009/07/12 23:18:22  fplanque
 * upgrading tables to innodb
 *
 * Revision 1.296  2009/07/11 17:18:03  waltercruz
 * Fixing missing comma
 *
 * Revision 1.295  2009/07/10 20:02:10  fplanque
 * using innodb by default for most tables now.
 * enabled transactions by default.
 *
 * Revision 1.294  2009/07/07 00:34:42  fplanque
 * Remember whether or not the TinyMCE editor was last used on a per post and per blog basis.
 *
 * Revision 1.293  2009/06/20 17:19:33  leeturner2701
 * meta desc and meta keywords per blog post
 *
 * Revision 1.292  2009/06/01 16:23:32  sam2kb
 * new_db_version updated to 9950
 *
 * Revision 1.291  2009/05/31 17:04:42  sam2kb
 * blog_shortname field extended to 255 characters
 * Please change the new_db_version
 *
 * Revision 1.290  2009/05/28 12:49:48  fplanque
 * no message
 *
 * Revision 1.289  2009/05/10 00:28:51  fplanque
 * serp rank logging
 *
 * Revision 1.288  2009/03/21 22:55:15  fplanque
 * Adding TinyMCE -- lowfat version
 *
 * Revision 1.287  2009/03/13 00:57:35  fplanque
 * calling it "sidebar links"
 *
 * Revision 1.285  2009/03/08 23:57:47  fplanque
 * 2009
 *
 * Revision 1.284  2009/03/03 20:23:46  blueyed
 * Move extract_keyphrase_from_referer to Hit class. Otherwise it should get moved to hit.funcs.
 *
 * Revision 1.283  2009/02/26 22:33:22  blueyed
 * Fix messup in last commit.
 *
 * Revision 1.282  2009/02/26 22:16:54  blueyed
 * Use load_class for classes (.class.php), and load_funcs for funcs (.funcs.php)
 *
 * Revision 1.281  2009/02/25 22:03:19  blueyed
 * Upgrade: rename ptyp_ID 3000 to 'Linkroll item'
 *
 * Revision 1.280  2009/02/25 20:54:47  blueyed
 *  - db_add_col: if the column exist already, execute an ALTER statement
 *  - Add db_add_index, which will drop any existing index, and create the
 *    new one
 *  - Use db_add_col/db_add_index for latest changes, to prevent errors on
 *    HEAD installs
 *
 * Revision 1.279  2009/02/25 01:31:16  fplanque
 * upgrade stuff
 *
 * Revision 1.278  2009/02/09 19:20:32  blueyed
 * Fix E_FATAL during upgrade (bpost_count_words not defined)
 *
 * Revision 1.277  2009/02/05 21:33:34  tblue246
 * Allow the user to enable/disable widgets.
 * Todo:
 * 	* Fix CSS for the widget state bullet @ JS widget UI.
 * 	* Maybe find a better solution than modifying get_Cache() to get only enabled widgets... :/
 * 	* Buffer JS requests when toggling the state of a widget??
 *
 * Revision 1.276  2009/01/28 21:23:22  fplanque
 * Manual ordering of categories
 *
 * Revision 1.275  2009/01/28 00:59:19  blueyed
 * Fixing doc for a block that gets skipped on installs tracking CVS HEAD, again (probably)
 *
 * Revision 1.274  2009/01/27 16:48:31  fplanque
 * quick fix for NULL ptyp_IDs
 *
 * Revision 1.273  2009/01/25 19:09:32  blueyed
 * phpdoc fixes
 *
 * Revision 1.272  2009/01/23 18:32:15  fplanque
 * versioning
 *
 * Revision 1.271  2009/01/21 18:52:15  fplanque
 * fix
 *
 * Revision 1.270  2009/01/21 18:23:26  fplanque
 * Featured posts and Intro posts
 *
 * Revision 1.268  2009/01/13 23:45:59  fplanque
 * User fields proof of concept
 *
 * Revision 1.267  2009/01/13 22:51:29  fplanque
 * rollback / normalized / MFB
 *
 * Revision 1.266  2008/12/28 17:35:51  fplanque
 * increase blog name max length to 255 chars
 *
 * Revision 1.265  2008/10/06 03:36:48  fplanque
 * Added skype field
 *
 * Revision 1.264  2008/10/06 01:55:06  fplanque
 * User fields proof of concept.
 * Needs UserFieldDef and UserFieldDefCache + editing of fields.
 * Does anyone want to take if from there?
 *
 * Revision 1.263  2008/09/27 00:05:54  fplanque
 * minor/version bump
 *
 * Revision 1.262  2008/09/24 09:28:36  fplanque
 * no message
 *
 * Revision 1.261  2008/09/23 06:18:39  fplanque
 * File manager now supports a shared directory (/media/shared/global/)
 *
 * Revision 1.260  2008/09/07 07:57:58  fplanque
 * doc
 *
 * Revision 1.259  2008/07/03 09:53:37  yabs
 * widget UI
 *
 * Revision 1.258  2008/05/27 23:36:40  blueyed
 * Fix indent. Add TODOs about checkpoints.
 *
 * Revision 1.257  2008/05/26 19:30:32  fplanque
 * enhanced analytics
 *
 * Revision 1.256  2008/05/10 23:41:04  fplanque
 * keyphrase logging
 *
 * Revision 1.255  2008/04/06 19:19:30  fplanque
 * Started moving some intelligence to the Modules.
 * 1) Moved menu structure out of the AdminUI class.
 * It is part of the app structure, not the UI. Up to this point at least.
 * Note: individual Admin skins can still override the whole menu.
 * 2) Moved DB schema to the modules. This will be reused outside
 * of install for integrity checks and backup.
 * 3) cleaned up config files
 *
 * Revision 1.254  2008/03/23 23:40:42  fplanque
 * no message
 *
 * Revision 1.253  2008/03/22 19:39:28  fplanque
 * <title> tag support
 *
 * Revision 1.252  2008/03/21 16:07:03  fplanque
 * longer post slugs
 *
 * Revision 1.251  2008/03/16 19:40:52  blueyed
 * Fix renaming of tables, which failed when done in one query (User only has perms on his DB; MySQL 5.0.38-Ubuntu_0ubuntu1.1) (LP: #195612)
 *
 * Revision 1.250  2008/03/16 14:19:39  fplanque
 * no message
 *
 * Revision 1.248  2008/03/07 02:00:42  blueyed
 * doc; indent; use db_drop_col
 *
 * Revision 1.247  2008/02/19 11:11:20  fplanque
 * no message
 *
 * Revision 1.246  2008/02/10 00:58:57  fplanque
 * no message
 *
 * Revision 1.245  2008/02/09 20:14:14  fplanque
 * custom fields management
 *
 * Revision 1.244  2008/02/09 17:36:15  fplanque
 * better handling of order, including approximative comparisons
 *
 * Revision 1.243  2008/02/09 02:56:00  fplanque
 * explicit order by field
 *
 * Revision 1.242  2008/02/07 00:35:52  fplanque
 * cleaned up install
 *
 * Revision 1.241  2008/01/23 16:44:27  fplanque
 * minor
 *
 * Revision 1.240  2008/01/22 16:57:08  fplanque
 * db upgrade stuff
 *
 * Revision 1.239  2008/01/21 09:35:38  fplanque
 * (c) 2008
 *
 * Revision 1.238  2008/01/19 10:57:10  fplanque
 * Splitting XHTML checking by group and interface
 *
 * Revision 1.237  2008/01/14 07:33:32  fplanque
 * Daniel, stop putting the comments in the log! They're more useful in the code!
 *
 * Revision 1.236  2008/01/12 19:25:58  blueyed
 * - Fix install from < 0.8: Make function "cleanup_post_quotes" inline and fix table name
 * - Only check max_execution_time when > 0 (not disabled)
 *
 * Revision 1.235  2008/01/08 03:31:50  fplanque
 * podcast support
 *
 * Revision 1.234  2008/01/05 00:24:35  blueyed
 * Create same filetypes when upgrading as when installing (DRY anyone?)
 *
 * Revision 1.233  2007/11/30 01:46:12  fplanque
 * db upgrade
 *
 * Revision 1.232  2007/11/02 01:53:34  fplanque
 * comment ratings
 *
 * Revision 1.231  2007/10/11 14:02:48  fplanque
 * simplified
 *
 * Revision 1.230  2007/09/19 02:54:16  fplanque
 * bullet proof upgrade
 *
 * Revision 1.229  2007/07/03 23:21:32  blueyed
 * Fixed includes/requires in/for tests
 *
 * Revision 1.228  2007/06/25 11:02:30  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.227  2007/06/13 19:06:17  fplanque
 * debugging
 *
 * Revision 1.226  2007/06/03 02:54:18  fplanque
 * Stuff for permission maniacs (admin part only, actual perms checks to be implemented)
 * Newbies will not see this complexity since advanced perms are now disabled by default.
 *
 * Revision 1.225  2007/05/31 03:02:23  fplanque
 * Advanced perms now disabled by default (simpler interface).
 * Except when upgrading.
 * Enable advanced perms in blog settings -> features
 *
 * Revision 1.224  2007/05/29 01:17:20  fplanque
 * advanced admin blog settings are now restricted by a special permission
 *
 * Revision 1.223  2007/05/17 20:44:19  fplanque
 * fixed upgrade.
 *
 * Revision 1.222  2007/05/14 02:47:23  fplanque
 * (not so) basic Tags framework
 *
 * Revision 1.221  2007/05/13 22:04:48  fplanque
 * basic excerpt support
 *
 * Revision 1.220  2007/05/13 20:44:52  fplanque
 * more pages support
 *
 * Revision 1.219  2007/05/02 18:28:19  fplanque
 * no message
 *
 * Revision 1.218  2007/04/27 09:34:45  fplanque
 * oops
 *
 * Revision 1.217  2007/04/27 09:11:37  fplanque
 * saving "spam" referers again (instead of buggy empty referers)
 *
 * Revision 1.216  2007/04/26 00:11:09  fplanque
 * (c) 2007
 *
 * Revision 1.215  2007/04/19 01:51:53  fplanque
 * upgrade checkpoints
 *
 * Revision 1.214  2007/03/26 12:59:18  fplanque
 * basic pages support
 *
 * Revision 1.213  2007/03/25 15:18:57  fplanque
 * cleanup
 *
 * Revision 1.212  2007/03/25 15:07:38  fplanque
 * multiblog fixes
 *
 * Revision 1.211  2007/03/12 14:10:10  waltercruz
 * Changing the WHERE 1 queries to boolean (WHERE 1=1) queries to satisfy the standarts
 *
 * Revision 1.210  2007/02/21 21:33:43  fplanque
 * allow jpeg extension on new installs/upgrades
 *
 * Revision 1.209  2007/02/13 00:38:11  blueyed
 * Changed DB fields for 1.10.0: sess_data to MEDIUMTEXT (serialize() does not completely convert the binary data to text); post_content and itpr_content_prerendered to MEDIUMTEXT
 *
 * Revision 1.208  2007/02/05 00:35:44  fplanque
 * small adjustments
 *
 * Revision 1.207  2007/02/03 19:00:49  fplanque
 * unbloat
 *
 * Revision 1.205  2007/01/23 04:19:50  fplanque
 * handling of blog owners
 *
 * Revision 1.204  2007/01/15 20:54:57  fplanque
 * minor fix
 *
 * Revision 1.203  2007/01/15 03:53:24  fplanque
 * refactoring / simplified installer
 *
 * Revision 1.202  2007/01/12 02:40:26  fplanque
 * widget default params proof of concept
 * (param customization to be done)
 *
 * Revision 1.201  2007/01/08 23:45:48  fplanque
 * A little less rough widget manager...
 * (can handle multiple instances of same widget and remembers order)
 *
 * Revision 1.200  2007/01/08 21:53:51  fplanque
 * typo
 *
 * Revision 1.199  2007/01/08 02:11:56  fplanque
 * Blogs now make use of installed skins
 * next step: make use of widgets inside of skins
 *
 * Revision 1.198  2006/12/20 23:07:24  blueyed
 * Moved list of available plugins to separate sub-screen/form
 *
 * Revision 1.197  2006/12/15 23:31:22  fplanque
 * reauthorized _ in urltitles.
 * No breaking of legacy permalinks.
 * - remains the default placeholder though.
 *
 * Revision 1.196  2006/12/07 20:31:29  fplanque
 * fixed install
 *
 * Revision 1.195  2006/12/07 20:03:33  fplanque
 * Woohoo! File editing... means all skin editing.
 *
 * Revision 1.194  2006/12/07 16:06:24  fplanque
 * prepared new file editing permission
 *
 * Revision 1.193  2006/12/04 22:24:51  blueyed
 * doc
 *
 * Revision 1.192  2006/12/04 21:25:18  fplanque
 * removed user skin switching
 *
 * Revision 1.191  2006/12/04 19:41:11  fplanque
 * Each blog can now have its own "archive mode" settings
 *
 * Revision 1.190  2006/12/04 18:16:51  fplanque
 * Each blog can now have its own "number of page/days to display" settings
 *
 * Revision 1.189  2006/11/18 16:34:24  blueyed
 * Removed todo
 *
 * Revision 1.188  2006/11/18 03:58:21  fplanque
 * removed duplicate indexes on T_links
 *
 * Revision 1.187  2006/11/14 23:17:00  fplanque
 * adding stuff into the 9010 block weeks later was really evil. why do we have blocks for?
 *
 * Revision 1.186  2006/11/01 00:24:07  blueyed
 * Fixed cafelog upgrade
 *
 * Revision 1.185  2006/10/14 21:11:48  blueyed
 * Actually insert the transformed/generated ping plugins setting(s).
 *
 * Revision 1.184  2006/10/14 20:53:13  blueyed
 * Transform blog ping settings to new Plugin structure.
 *
 * Revision 1.183  2006/10/11 17:21:09  blueyed
 * Fixes
 *
 * Revision 1.182  2006/10/10 23:00:41  blueyed
 * Fixed some table names to alias; fixed plugin install procedure; installed ping plugins; moved some upgrade code to 1.9
 *
 * Revision 1.181  2006/10/06 21:03:07  blueyed
 * Removed deprecated/unused "upload_allowedext" Setting, which restricted file extensions during upload though!
 *
 * Revision 1.180  2006/10/05 02:58:44  blueyed
 * Support for skipping index dropping, if it does not exist anymore. Should not bark out then! Also do not add the last checkpoint possibly twice.
 *
 * Revision 1.179  2006/10/05 02:42:22  blueyed
 * Remove index hit_datetime, because its slow on INSERT (e.g. 1s)
 *
 * Revision 1.178  2006/10/02 19:07:32  blueyed
 * Finished upgrade for 1.9-beta
 *
 * Revision 1.177  2006/10/01 22:11:43  blueyed
 * Ping services as plugins.
 *
 * Revision 1.176  2006/10/01 00:14:58  blueyed
 * plug_classpath should not have get merged already
 */
?>
