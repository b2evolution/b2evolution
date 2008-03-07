<?php
/**
 * This file implements upgrading of DB tables
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package install
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

	foreach( $DB->get_results('SHOW INDEX FROM '.$table) as $row )
	{
		if( strtolower($row->Key_name) == $index_name )
		{
			return true;
		}
	}

	return false;
}


/**
 * @string Table name
 * @array Column names
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
 */
function db_add_col( $table, $col_name, $col_desc )
{
	global $DB;

	if( db_col_exists($table, $col_name) )
		return false;

	$DB->query( 'ALTER TABLE '.$table.' ADD COLUMN '.$col_name.' '.$col_desc );
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

	require_once dirname(__FILE__).'/_db_schema.inc.php';
	load_class('_core/model/db/_upgrade.funcs.php');


	echo '<p>'.T_('Checking DB schema version...').' ';
	$old_db_version = get_db_version();

	if( empty($old_db_version) )
	{
		echo '<p><strong>OOPS! b2evolution doesn\'t seem to be installed yet.</strong></p>';
		return;
	}

	echo $old_db_version, ' : ';

	if( $old_db_version < 8000 ) debug_die( T_('This version is too old!') );
	if( $old_db_version > $new_db_version ) debug_die( T_('This version is too recent! We cannot downgrade to it!') );
	echo "OK.<br />\n";


	// Try to obtain some serious time to do some serious processing (5 minutes)
	@set_time_limit( 300 );



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
			$blog_siteurl = substr( $blog_siteurl.'/', strlen( $baseurl) );
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
		$query = "INSERT INTO T_antispam(aspm_string) VALUES ".
		"('penis-enlargement'), ('online-casino'), ".
		"('order-viagra'), ('order-phentermine'), ('order-xenical'), ".
		"('order-prophecia'), ('sexy-lingerie'), ('-porn-'), ".
		"('-adult-'), ('-tits-'), ('buy-phentermine'), ".
		"('order-cheap-pills'), ('buy-xenadrine'),	('xxx'), ".
		"('paris-hilton'), ('parishilton'), ('camgirls'), ('adult-models')";
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

		set_upgrade_checkpoint( '8050' );
	}


	if( $old_db_version < 8060 )
	{ // upgrade to 0.9
		// Important check:
		$stub_list = $DB->get_col( "SELECT blog_stub
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
			$DB->query( "CREATE TABLE T_usersettings (
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
			$DB->query( "CREATE TABLE T_useragents (
										agnt_ID        INT UNSIGNED NOT NULL AUTO_INCREMENT,
										agnt_signature VARCHAR(250) NOT NULL,
										agnt_type      ENUM('rss','robot','browser','unknown') DEFAULT 'unknown' NOT NULL ,
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
	// TODO: dh> shouldn't they get localized to the app's default locale?
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
										ADD COLUMN comment_allow_msgform TINYINT NOT NULL DEFAULT \'0\' AFTER comment_spam_karma' );
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
				ALTER TABLE T_useragents ADD INDEX agnt_type ( agnt_type )' );
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

		// TODO: dh> "cID" is not that readable, is it? Should use a function instead. Also use it for cafelog upgrade then.
		$DB->query( '
      UPDATE T_categories
         SET cat_urlname = CONCAT( "c" , cat_ID )' );
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
		$DB->query( 'DELETE FROM T_usersettings
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

		install_basic_skins();

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
		$DB->query( "RENAME TABLE {$tableprefix}item__prerendering TO T_items__prerendering,
															{$tableprefix}poststatuses TO T_items__status,
															{$tableprefix}posttypes TO T_items__type,
															{$tableprefix}posts TO T_items__item" );
		echo "OK.<br />\n";

		echo "Creating Tag tables...";
		$DB->query( "CREATE TABLE T_items__tag (
		      tag_ID   int(11) unsigned not null AUTO_INCREMENT,
		      tag_name varchar(50)      not null,
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
			CHANGE COLUMN post_order post_order DOUBLE NULL,
			ADD COLUMN post_double1   DOUBLE NULL COMMENT 'Custom double value 1' AFTER post_priority,
			ADD COLUMN post_double2   DOUBLE NULL COMMENT 'Custom double value 2' AFTER post_double1,
			ADD COLUMN post_double3   DOUBLE NULL COMMENT 'Custom double value 3' AFTER post_double2,
			ADD COLUMN post_double4   DOUBLE NULL COMMENT 'Custom double value 4' AFTER post_double3,
			ADD COLUMN post_double5   DOUBLE NULL COMMENT 'Custom double value 5' AFTER post_double4,
			ADD COLUMN post_varchar1  VARCHAR(255) NULL COMMENT 'Custom varchar value 1' AFTER post_double5,
			ADD COLUMN post_varchar2  VARCHAR(255) NULL COMMENT 'Custom varchar value 2' AFTER post_varchar1,
			ADD COLUMN post_varchar3  VARCHAR(255) NULL COMMENT 'Custom varchar value 3' AFTER post_varchar2" );
		echo "OK.<br />\n";

		echo 'Upgrading hitlog table... ';
		$query = "ALTER TABLE T_hitlog
			CHANGE COLUMN hit_ID hit_ID               INT(11) UNSIGNED NOT NULL AUTO_INCREMENT";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Upgrading sessions table... ';
		$DB->query( "ALTER TABLE T_sessions
			ALTER COLUMN sess_lastseen SET DEFAULT '2000-01-01 00:00:00'?
			ADD COLUMN sess_hitcount  INT(10) UNSIGNED NOT NULL DEFAULT 1 AFTER sess_key" );
		echo "OK.<br />\n";

		echo 'Creating goal tracking table... ';
    $DB->query( "CREATE TABLE T_track__goal(
            goal_ID       int(10) unsigned NOT NULL auto_increment,
            goal_name     varchar(50) default NULL,
            PRIMARY KEY  (goal_ID)
          )" );

    $DB->query( "CREATE TABLE T_track__goalhit (
            ghit_ID int(10) unsigned NOT NULL auto_increment,
            ghit_goal_ID int(10) unsigned NOT NULL,
            ghit_sess_ID int(10) unsigned NOT NULL,
            ghit_params varchar(2000) default NULL,
            PRIMARY KEY  (ghit_ID),
            KEY ghit_goal_ID (ghit_goal_ID),
            KEY ghit_sess_ID (ghit_sess_ID)
         )" );
		echo "OK.<br />\n";

	}


	/*
	 * ADD UPGRADES HERE.
	 *
	 * ALL DB CHANGES MUST BE EXPLICITELY CARRIED OUT. DO NOT RELY ON SCHEMA UPDATES!
	 * Schema updates do not survive after several incremental changes.
	 *
	 * NOTE: every change that gets done here, should bump {@link $new_db_version} (by 100).
	 */


	// Just in case, make sure the db schema version is upto date at the end.
	if( $old_db_version != $new_db_version )
	{ // Update DB schema version to $new_db_version
		set_upgrade_checkpoint( $new_db_version );
	}


	echo 'Resetting b2evolution.net polling times... ';
	$DB->query( "
		DELETE FROM T_settings
		WHERE set_name = 'evonet_last_attempt'
			 OR set_name = 'evonet_last_update'" );
	echo "OK.<br />\n";


	// This has to be at the end because plugin install may fail if the DB schema is not current (matching Plugins class).
	// Only new default plugins will be installed, based on $old_db_version.
	// dh> NOTE: if this fails (e.g. fatal error in one of the plugins), it will not get repeated
	install_basic_plugins( $old_db_version );


	/*
	 * Check to make sure the DB schema is up to date:
	 */
	$upgrade_db_deltas = array(); // This holds changes to make, if any (just all queries)

	foreach( $schema_queries as $table => $query_info )
	{
		foreach( db_delta( $query_info[1], array('drop_column', 'drop_index') ) as $table => $queries )
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

	if( empty($upgrade_db_deltas) )
	{
		echo '<p>'.T_('The database schema is up to date.').'</p>';
	}
	else
	{
		// delta queries have to be confirmed or executed now..

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
			global $action, $locale;
			load_funcs( '_core/ui/forms/_form.class.php' );
			$Form = & new Form( NULL, '', 'post' );
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
