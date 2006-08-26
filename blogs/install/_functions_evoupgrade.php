<?php
/**
 * This file implements upgrading of DB tables
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
	global $DB;

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

	echo "OK.<br />\n";
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
					$converted = $DB->query( "UPDATE $table
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
	global $db_config;
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
	require_once $inc_path.'_misc/_upgrade.funcs.php';


	// Check DB version:
	check_db_version();

	if( $old_db_version == $new_db_version )
	{ // Probably no need to update, but check current DB schema first
		$db_schema_needs_update = false;
		foreach( $schema_queries as $table => $query_info )
		{
			if( db_delta( $query_info[1], array('drop_column', 'drop_index') ) )
			{
				$db_schema_needs_update = true;
				break;
			}
		}

		if( ! $db_schema_needs_update )
		{
			echo '<p>'.T_('The database schema is already up to date. There is nothing to do.').'</p>';
			printf( '<p>'.T_('Now you can <a %s>log in</a> with your usual %s username and password.').'</p>', 'href="'.$admin_url.'"', 'b2evolution' );
			return false;
		}

		// We come here, if $old_db_version and $new_db_version are the same, but the schema needs upgrade (_db_schema.inc.php has changed)..
		// We'll upgrade to the new schema below (at the end)..
	}


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
		$query = "ALTER TABLE T_posts
							MODIFY COLUMN post_lang VARCHAR(20) NOT NULL DEFAULT 'en_US',
							ADD COLUMN post_urltitle VARCHAR(50) NULL DEFAULT NULL AFTER post_title,
							ADD COLUMN post_url VARCHAR(250) NULL DEFAULT NULL AFTER post_urltitle,
							ADD COLUMN post_comments ENUM('disabled', 'open', 'closed') NOT NULL DEFAULT 'open' AFTER post_wordcount";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Generating wordcounts... ';
		$query = "SELECT ID, post_content FROM T_posts WHERE post_wordcount IS NULL";
		$i = 0;
		foreach( $DB->get_results( $query, ARRAY_A ) as $row )
		{
			$query_update_wordcount = "UPDATE T_posts
																SET post_wordcount = " . bpost_count_words($row['post_content']) . "
																WHERE ID = " . $row['ID'];
			$DB->query($query_update_wordcount);
			$i++;
		}
		echo "OK. ($i rows updated)<br />\n";
	}


	if( $old_db_version < 8020 )
	{
		echo 'Encoding passwords... ';
		$query = "UPDATE T_users
							SET user_pass = MD5(user_pass)";
		$DB->query( $query );
		echo "OK.<br />\n";
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
		create_groups();
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
		$Group_Admins->set( 'perm_files', 'edit' );
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
	{{{ // upgrade to 0.9
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
		$query = "UPDATE T_posts
							SET post_urltitle = NULL";
		$DB->query( $query );

		$query = "ALTER TABLE T_posts
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

		$query = "UPDATE T_posts
							SET post_mod_date = post_issue_date";
		$DB->query( $query );
		echo "OK.<br />\n";

		// convert given languages to locales
		convert_lang_to_locale( 'T_posts', 'post_locale', 'ID' );

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
			'posts_per_page' => array(5),
			'what_to_show' => array('posts'),
			'archive_mode' => array('monthly'),
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
	}}}


	if( $old_db_version < 8062 )
	{ // upgrade to 0.9.0.4
		cleanup_post_quotes();

		set_upgrade_checkpoint( '8062' );
	}


	if( $old_db_version < 8064 )
	{ // upgrade to 0.9.0.6
		cleanup_comment_quotes();
	}


	if( $old_db_version < 8066 )
	{	// upgrade to 0.9.1
		echo 'Adding catpost index... ';
		$DB->query( 'ALTER TABLE T_postcats ADD UNIQUE catpost ( postcat_cat_ID, postcat_post_ID )' );
		echo "OK.<br />\n";
	}


	if( $old_db_version < 9000 )
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
			$query="CREATE TABLE T_itemstatuses (
											pst_ID   int(11) unsigned not null AUTO_INCREMENT,
											pst_name varchar(30)      not null,
											primary key ( pst_ID )
										)";
			$DB->query( $query );
			echo "OK.<br />\n";


			echo 'Creating table for Post Types... ';
			$query="CREATE TABLE T_itemtypes (
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
								)"; // TODO: more indexes?
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


		echo 'Upgrading posts table... ';
		$query = "ALTER TABLE T_posts
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

		echo 'Updating post data... ';
		$query = 'UPDATE T_posts
							SET post_lastedit_user_ID = post_creator_user_ID,
									post_datecreated = post_datestart';
		$DB->query( $query );
		echo "OK.<br />\n";


		echo 'Upgrading users table... ';
		$query = 'ALTER TABLE T_users
							CHANGE COLUMN ID user_ID int(11) unsigned NOT NULL auto_increment,
							MODIFY COLUMN user_icq int(11) unsigned DEFAULT 0 NOT NULL,
							ADD COLUMN user_showonline tinyint(1) NOT NULL default 1 AFTER user_notify';
		$DB->query( $query );
		echo "OK.<br />\n";


		echo 'Setting new defaults... ';
		$query = 'INSERT INTO T_settings (set_name, set_value)
							VALUES
								( "reloadpage_timeout", "300" ),
								( "upload_enabled", "'.(isset($use_fileupload) ? (int)$use_fileupload : '1').'" ),
								( "upload_allowedext", "'.(isset($fileupload_allowedtypes) ? $fileupload_allowedtypes : 'jpg gif png').'" ),
								( "upload_maxkb", "'.(isset($fileupload_maxk) ? (int)$fileupload_maxk : '96').'" )
							';
		$DB->query( $query );
		// Replace "paged" mode with "posts"
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


		echo 'Altering comments table... ';
		$DB->query( "ALTER TABLE T_comments
									MODIFY COLUMN comment_post_ID		int(11) unsigned NOT NULL default '0'" );
		echo "OK.<br />\n";


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
			INSERT INTO T_itemtypes ( ptyp_ID, ptyp_name )
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
		// Contribs: feel free to add more types here... (and in the block for new installs (create_b2evo_tables()))
		$DB->query( "INSERT INTO T_filetypes VALUES
				(1, 'gif', 'GIF image', 'image/gif', 'image2.png', 'image', 1),
				(2, 'png', 'PNG image', 'image/png', 'image2.png', 'image', 1),
				(3, 'jpg', 'JPEG image', 'image/jpeg', 'image2.png', 'image', 1),
				(4, 'txt', 'Text file', 'text/plain', 'document.png', 'text', 1),
				(5, 'htm html', 'HTML file', 'text/html', 'html.png', 'browser', 0),
				(6, 'pdf', 'PDF file', 'application/pdf', 'pdf.png', 'browser', 1),
				(7, 'doc', 'Microsoft Word file', 'application/msword', 'doc.gif', 'external', 1),
				(8, 'xls', 'Microsoft Excel file', 'application/vnd.ms-excel', 'xls.gif', 'external', 1),
				(9, 'ppt', 'Powerpoint', 'application/vnd.ms-powerpoint', 'ppt.gif', 'external', 1),
				(10, 'pps', 'Powerpoint slideshow', 'pps', 'pps.gif', 'external', 1),
				(11, 'zip', 'Zip archive', 'application/zip', 'zip.gif', 'external', 1),
				(12, 'php php3 php4 php5 php6', 'Php files', 'application/x-httpd-php', 'php.gif', 'download', 0)
			" );
		echo "OK.<br />\n";

		echo 'Giving Administrator Group edit perms on files... ';
		$DB->query( 'UPDATE T_groups
		             SET grp_perm_files = "edit"
		             WHERE grp_ID = 1' );
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
			$DB->query( 'DELETE FROM T_plugins WHERE 1' );
			echo "OK.<br />\n";
		}

		// NOTE: basic plugins get installed separatly for upgrade and install..


		set_upgrade_checkpoint( '9100' );
	}

	if( $old_db_version < 9190 ) // Note: changed from 9200, to include the block below, if DB is not yet on 1.8
	{	// 1.8 ALPHA (block #2)
		echo 'Altering Posts table... ';
		$DB->query( "ALTER TABLE T_posts
		             CHANGE post_comments post_comment_status ENUM('disabled', 'open', 'closed') NOT NULL DEFAULT 'open'" );
		echo "OK.<br />\n";


		set_upgrade_checkpoint( '9190' );
	}

	if( $old_db_version < 9200 )
	{ // 1.8 ALPHA (block #3) - The payload that db_delta() handled before

		// This is a fix, which broke upgrade to 1.8 (from 1.6) in MySQL strict mode (inserted after 1.8 got released!):
		if( $DB->get_row( 'SHOW COLUMNS FROM T_hitlog LIKE "hit_referer_type"' ) )
		{ // a niiiiiiiice extra check :p
			echo 'Deleting all "spam" hitlog entries... ';
			$DB->query( '
					DELETE FROM T_hitlog
					 WHERE hit_referer_type = "spam"' );
			echo "OK.<br />\n";
		}

		// TODO: change to "regular" output schema
		foreach( array (
				0 => 'ALTER TABLE T_users CHANGE COLUMN user_firstname user_firstname varchar(50) NULL',
				1 => 'ALTER TABLE T_users CHANGE COLUMN user_lastname user_lastname varchar(50) NULL',
				2 => 'ALTER TABLE T_users CHANGE COLUMN user_nickname user_nickname varchar(50) NULL',
				3 => 'ALTER TABLE T_users CHANGE COLUMN user_icq user_icq int(11) unsigned NULL',
				4 => 'ALTER TABLE T_users CHANGE COLUMN user_email user_email varchar(255) NOT NULL',
				5 => 'ALTER TABLE T_users CHANGE COLUMN user_url user_url varchar(255) NULL',
				6 => 'ALTER TABLE T_users CHANGE COLUMN user_ip user_ip varchar(15) NULL',
				7 => 'ALTER TABLE T_users CHANGE COLUMN user_domain user_domain varchar(200) NULL',
				8 => 'ALTER TABLE T_users CHANGE COLUMN user_browser user_browser varchar(200) NULL',
				9 => 'ALTER TABLE T_users CHANGE COLUMN user_aim user_aim varchar(50) NULL',
				10 => 'ALTER TABLE T_users CHANGE COLUMN user_msn user_msn varchar(100) NULL',
				11 => 'ALTER TABLE T_users CHANGE COLUMN user_yim user_yim varchar(50) NULL',
				12 => 'ALTER TABLE T_users ADD COLUMN user_allow_msgform TINYINT NOT NULL DEFAULT \'1\' AFTER user_idmode',
				13 => 'ALTER TABLE T_users ADD COLUMN user_validated TINYINT(1) NOT NULL DEFAULT 0 AFTER user_grp_ID',
				14 => 'ALTER TABLE T_blogs CHANGE COLUMN blog_media_subdir blog_media_subdir VARCHAR( 255 ) NULL',
				15 => 'ALTER TABLE T_blogs CHANGE COLUMN blog_media_fullpath blog_media_fullpath VARCHAR( 255 ) NULL',
				16 => 'ALTER TABLE T_blogs CHANGE COLUMN blog_media_url blog_media_url VARCHAR( 255 ) NULL',
				17 => 'CREATE TABLE T_coll_settings (
															cset_coll_ID INT(11) UNSIGNED NOT NULL,
															cset_name    VARCHAR( 30 ) NOT NULL,
															cset_value   VARCHAR( 255 ) NULL,
															PRIMARY KEY ( cset_coll_ID, cset_name )
											)',
				18 => 'ALTER TABLE T_posts CHANGE COLUMN post_content post_content          text NULL',
				19 => 'ALTER TABLE T_posts CHANGE COLUMN post_url post_url              VARCHAR(255) NULL DEFAULT NULL',
				20 => 'ALTER TABLE T_posts CHANGE COLUMN post_renderers post_renderers        TEXT NOT NULL',
				21 => 'ALTER TABLE T_comments CHANGE COLUMN comment_author_email comment_author_email varchar(255) NULL',
				22 => 'ALTER TABLE T_comments CHANGE COLUMN comment_author_url comment_author_url varchar(255) NULL',
				23 => 'ALTER TABLE T_comments ADD COLUMN comment_spam_karma TINYINT NULL AFTER comment_karma',
				24 => 'ALTER TABLE T_comments ADD COLUMN comment_allow_msgform TINYINT NOT NULL DEFAULT \'0\' AFTER comment_spam_karma',
				25 => 'ALTER TABLE T_hitlog CHANGE COLUMN hit_referer_type hit_referer_type   ENUM(\'search\',\'blacklist\',\'referer\',\'direct\') NOT NULL',
				26 => 'ALTER TABLE T_hitlog ADD COLUMN hit_agnt_ID        INT UNSIGNED NULL AFTER hit_remote_addr',
				27 => 'ALTER TABLE T_links ADD INDEX link_itm_ID( link_itm_ID )',
				28 => 'ALTER TABLE T_links ADD INDEX link_dest_itm_ID (link_dest_itm_ID)',
				30 => 'ALTER TABLE T_plugins CHANGE COLUMN plug_priority plug_priority        TINYINT NOT NULL default 50',
				31 => 'ALTER TABLE T_plugins ADD COLUMN plug_code            VARCHAR(32) NULL AFTER plug_classname',
				32 => 'ALTER TABLE T_plugins ADD COLUMN plug_apply_rendering ENUM( \'stealth\', \'always\', \'opt-out\', \'opt-in\', \'lazy\', \'never\' ) NOT NULL DEFAULT \'never\' AFTER plug_code',
				33 => 'ALTER TABLE T_plugins ADD COLUMN plug_version         VARCHAR(42) NOT NULL default \'0\' AFTER plug_apply_rendering',
				34 => 'ALTER TABLE T_plugins ADD COLUMN plug_status          ENUM( \'enabled\', \'disabled\', \'needs_config\', \'broken\' ) NOT NULL AFTER plug_version',
				35 => 'ALTER TABLE T_plugins ADD COLUMN plug_spam_weight     TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER plug_status',
				36 => 'ALTER TABLE T_plugins ADD UNIQUE plug_code( plug_code )',
				37 => 'ALTER TABLE T_plugins ADD INDEX plug_status( plug_status )',
				38 => 'CREATE TABLE T_pluginsettings (
															pset_plug_ID INT(11) UNSIGNED NOT NULL,
															pset_name VARCHAR( 30 ) NOT NULL,
															pset_value TEXT NULL,
															PRIMARY KEY ( pset_plug_ID, pset_name )
											)',
				39 => 'CREATE TABLE T_pluginusersettings (
															puset_plug_ID INT(11) UNSIGNED NOT NULL,
															puset_user_ID INT(11) UNSIGNED NOT NULL,
															puset_name VARCHAR( 30 ) NOT NULL,
															puset_value TEXT NULL,
															PRIMARY KEY ( puset_plug_ID, puset_user_ID, puset_name )
											)',
				41 => 'CREATE TABLE T_cron__task(
												 ctsk_ID              int(10) unsigned      not null AUTO_INCREMENT,
												 ctsk_start_datetime  datetime              not null,
												 ctsk_repeat_after    int(10) unsigned,
												 ctsk_name            varchar(50)           not null,
												 ctsk_controller      varchar(50)           not null,
												 ctsk_params          text,
												 primary key (ctsk_ID)
											)',
				42 => 'CREATE TABLE T_cron__log(
															 clog_ctsk_ID              int(10) unsigned   not null,
															 clog_realstart_datetime   datetime           not null,
															 clog_realstop_datetime    datetime,
															 clog_status               enum(\'started\',\'finished\',\'error\',\'timeout\') not null default \'started\',
															 clog_messages             text,
															 primary key (clog_ctsk_ID)
											)',

				// This is "DEFAULT 1" in the 0.9.0.11 dump.. - changed in 0.9.2?!
				43 => 'ALTER TABLE evo_blogs ALTER COLUMN blog_allowpingbacks SET DEFAULT 0',

			) as $query )
		{
			$DB->query($query);
		}

		set_upgrade_checkpoint( '9200' ); // at 1.8 "Summer Beta" release
	}


	// 1.9:
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
					MODIFY COLUMN plug_status ENUM( \'enabled\', \'disabled\', \'needs_config\', \'broken\' ) NOT NULL,
					ADD COLUMN plug_classpath VARCHAR(255) NULL default NULL AFTER plug_classname' );
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



	if( $old_db_version < 9404 )
	{
		echo 'Updating blogs... ';
		$DB->query( '
				ALTER TABLE T_blogs
							DROP COLUMN blog_allowpingbacks' );
		echo "OK.<br />\n";

		echo 'Updating posts... ';
		$DB->query( '
			ALTER TABLE T_posts
				ADD COLUMN post_notifications_status   ENUM("noreq","todo","started","finished") NOT NULL DEFAULT "noreq" AFTER post_flags,
				ADD COLUMN post_notifications_ctsk_ID  INT(10) unsigned NULL DEFAULT NULL AFTER post_notifications_status' );
		$DB->query( '
			UPDATE T_posts
			   SET post_notifications_status = "finished"
			 WHERE post_flags LIKE "%pingsdone%"' );
		$DB->query( '
			ALTER TABLE T_posts
				DROP COLUMN post_flags' );
		echo "OK.<br />\n";

	}


	if( $old_db_version < 9405 )
	{
		echo 'Updating URL titles... ';
		$DB->query( '
      UPDATE T_posts
         SET post_urltitle = REPLACE( post_urltitle, "_", "-" )' );
		echo "OK.<br />\n";
	}


	/*
	 * NOTE: every change that gets done here, should bump {@link $new_db_version} (by 100),
	 *       to avoid the following:
	 *   - You go to /install/
	 *   - the (e.g.) column-rename above does not get caught, because $old_db_version is the same as $new_db_version (which has not changed)
	 *   - Below, a new DB column gets added by db_delta(), because it's missing
	 *   - The data from the old column does not get copied
	 *   - The old column does not get removed
	 */



	if( $old_db_version != $new_db_version )
	{
		// Update DB schema version to $new_db_version
		set_upgrade_checkpoint( $new_db_version );
	}


	// This block has to be at the end because plugin install may fail if the DB schema is not current.
	if( $old_db_version < 9100 )
	{
		install_basic_plugins();
	}


	// Check DB schema:
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

	if( ! empty($upgrade_db_deltas) )
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
			require_once $inc_path.'_misc/_form.class.php';
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
 * Revision 1.169  2006/08/26 20:30:42  fplanque
 * made URL titles Google friendly
 *
 * Revision 1.168  2006/08/26 19:56:38  blueyed
 * Fixed SQL syntax error and whitespace
 *
 * Revision 1.167  2006/08/26 16:33:02  fplanque
 * enhanced stats
 *
 * Revision 1.166  2006/08/24 21:41:14  fplanque
 * enhanced stats
 *
 * Revision 1.165  2006/08/21 16:07:45  fplanque
 * refactoring
 *
 * Revision 1.164  2006/08/19 07:56:31  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.163  2006/08/17 20:10:23  fplanque
 * fix syntax error
 *
 * Revision 1.162  2006/08/15 21:54:57  blueyed
 * ok.
 *
 * Revision 1.161  2006/08/14 20:19:52  fplanque
 * no message
 *
 * Revision 1.160  2006/08/09 21:30:56  fplanque
 * doc
 *
 * Revision 1.157  2006/08/04 22:13:23  blueyed
 * Finished de-abstraction
 *
 * Revision 1.156  2006/08/03 01:55:24  blueyed
 * Fixed upgrade procedure according to "the plan" (as told by Francois by email).
 *
 * Revision 1.155  2006/07/08 13:33:54  blueyed
 * Autovalidate admin group instead of primary admin user only.
 * Also delegate to req_validatemail action on failure directly instead of providing a link.
 *
 * Revision 1.154  2006/07/08 02:13:38  blueyed
 * Understood the new auto_prune_modes and added conversion of previous "off" value (0).
 *
 * Revision 1.153  2006/07/05 20:07:07  blueyed
 * discussion
 *
 * Revision 1.152  2006/07/05 18:26:01  fplanque
 * no message
 *
 * Revision 1.151  2006/07/04 17:32:30  fplanque
 * no message
 *
 * Revision 1.150  2006/07/03 19:27:48  blueyed
 * Fixed install (user_validated)
 *
 * Revision 1.149  2006/07/02 21:53:31  blueyed
 * time difference as seconds instead of hours; validate user#1 on upgrade; bumped new_db_version to 9300.
 *
 * Revision 1.148  2006/06/22 18:37:47  fplanque
 * fixes
 *
 * Revision 1.146  2006/05/30 21:53:06  blueyed
 * Replaced $EvoConfig->DB with $db_config
 *
 * Revision 1.145  2006/05/17 23:35:42  blueyed
 * cleanup
 *
 * Revision 1.144  2006/05/12 21:34:16  blueyed
 * todo (last commit was lost)
 *
 * Revision 1.143  2006/04/29 17:37:48  blueyed
 * Added basic_antispam_plugin; Moved double-check-referers there; added check, if trackback links to us
 *
 * Revision 1.142  2006/04/27 19:08:15  blueyed
 * todo
 *
 * Revision 1.141  2006/04/20 15:57:44  blueyed
 * Bumped $db_version to 9200
 *
 * Revision 1.140  2006/04/19 15:56:02  blueyed
 * Renamed T_posts.post_comments to T_posts.post_comment_status (DB column rename!);
 * and Item::comments to Item::comment_status (Item API change)
 *
 * Revision 1.139  2006/04/11 22:39:50  blueyed
 * Fixed installation of basic plugins, though again more complicated (IMHO)
 *
 * Revision 1.138  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 * Revision 1.137  2006/04/10 09:27:04  blueyed
 * Fix adding default itemtypes when upgrading from 0.9.x; cleaned up plugins install
 *
 * Revision 1.136  2006/04/06 08:51:34  blueyed
 * Set upgrade checkpoint
 *
 * Revision 1.135  2006/03/12 23:09:26  fplanque
 * doc cleanup
 *
 * Revision 1.134  2006/03/09 20:40:41  fplanque
 * cleanup
 *
 * Revision 1.133  2006/03/07 19:30:23  fplanque
 * comments
 *
 * Revision 1.132  2006/03/06 23:14:23  blueyed
 * Moved _db_schema.inc.php to /install/ folder
 *
 * Revision 1.131  2006/03/06 21:21:59  blueyed
 * doc
 *
 * Revision 1.129  2006/03/04 20:43:29  blueyed
 * Fixed dropping sess_agnt_ID
 *
 * Revision 1.128  2006/03/02 20:05:29  blueyed
 * Fixed/polished stats (linking T_useragents to T_hitlog, not T_sessions again). I've done this the other way around before, but it wasn't my idea.. :p
 *
 * Revision 1.127  2006/02/24 19:59:29  blueyed
 * New install/upgrade, which makes use of db_delta()
 *
 * Revision 1.126  2006/02/13 20:20:10  fplanque
 * minor / cleanup
 *
 * Revision 1.124  2006/02/11 18:53:57  fplanque
 * most people don't have relations installed
 *
 * Revision 1.123  2006/02/11 01:08:20  blueyed
 * Oh what fun it is to drop some "e".
 *
 * Revision 1.122  2006/02/10 22:05:07  fplanque
 * Normalized itm links
 *
 * Revision 1.121  2006/02/03 17:35:17  blueyed
 * post_renderers as TEXT
 *
 * Revision 1.120  2006/01/26 22:43:58  blueyed
 * Added comment_spam_karma field
 *
 * Revision 1.119  2006/01/06 18:58:09  blueyed
 * Renamed Plugin::apply_when to $apply_rendering; added T_plugins.plug_apply_rendering and use it to find Plugins which should apply for rendering in Plugins::validate_list().
 *
 * Revision 1.117  2006/01/06 00:11:47  blueyed
 * Fix potential SQL error when upgrading from < 0.9 to Phoenix
 *
 * Revision 1.115  2005/12/29 20:20:02  blueyed
 * Renamed T_plugin_settings to T_pluginsettings
 *
 * Revision 1.114  2005/12/22 23:13:40  blueyed
 * Plugins' API changed and handling optimized
 *
 * Revision 1.113  2005/12/19 17:39:56  fplanque
 * Remember prefered browing tab for each user.
 *
 * Revision 1.112  2005/12/14 19:36:16  fplanque
 * Enhanced file management
 *
 * Revision 1.111  2005/12/12 19:22:03  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.110  2005/10/31 01:35:47  blueyed
 * Upgrade to 0.9: adjusted defaults
 *
 * Revision 1.109  2005/10/28 22:33:54  blueyed
 * Removed not used globals for upgrade to 1.6
 *
 * Revision 1.107  2005/10/03 18:10:08  fplanque
 * renamed post_ID field
 *
 * Revision 1.106  2005/10/03 17:26:44  fplanque
 * synched upgrade with fresh DB;
 * renamed user_ID field
 *
 * Revision 1.105  2005/10/03 16:30:42  fplanque
 * fixed hitlog upgrade because daniel didn't do it :((
 *
 */
?>