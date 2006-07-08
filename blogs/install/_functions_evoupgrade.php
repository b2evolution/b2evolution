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
		echo '<p>'.T_('The version number is correct, but we have detected changes in the database schema. This can happen with CVS versions...').'</p>';
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
		create_antispam();

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
							CHANGE COLUMN post_date post_issue_date datetime NOT NULL default '0000-00-00 00:00:00',
							ADD COLUMN post_mod_date datetime NOT NULL default '0000-00-00 00:00:00'
										AFTER post_issue_date,
							CHANGE COLUMN post_lang post_locale varchar(20) NOT NULL default 'en-EU',
							DROP COLUMN post_url,
							CHANGE COLUMN post_trackbacks post_url varchar(250) NULL default NULL,
							MODIFY COLUMN post_flags SET( 'pingsdone', 'imported'),
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
	{ // ---------------------------------- upgrade to 0.9.2 a.k.a 1.6 "phoenix ALPHA"

		echo 'Dropping old Hitlog table... ';
		$DB->query( 'DROP TABLE IF EXISTS T_hitlog' );
		echo "OK.<br />\n";

		// New tables:
		// removed by blueyed:create_b2evo_tables_phoenix();

		echo 'Creating plugins table... ';
		$DB->query( "CREATE TABLE T_plugins (
										plug_ID        INT(11) UNSIGNED NOT NULL auto_increment,
										plug_priority  INT(11) NOT NULL default 50,
										plug_classname VARCHAR(40) NOT NULL default '',
										PRIMARY KEY ( plug_ID )
									)");
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


		set_upgrade_checkpoint( '9000' );
	}


	if( $old_db_version < 9100 )
	{	// 1.8 ALPHA (only column renames/drops):

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

		set_upgrade_checkpoint( '9100' );
	}

	if( $old_db_version < 9200 )
	{	// 1.8 ALPHA (block #2)
		echo 'Altering Posts table... ';
		$DB->query( "ALTER TABLE T_posts
		             CHANGE post_comments post_comment_status ENUM('disabled', 'open', 'closed') NOT NULL DEFAULT 'open'" );
		echo "OK.<br />\n";


		set_upgrade_checkpoint( '9200' );
	}

	if( $old_db_version < 9300 )
	{
		global $Settings;
		echo 'Converting time_difference from hours to seconds... ';
		$DB->query( 'UPDATE T_settings SET set_value = set_value*3600 WHERE set_name = "time_difference"' );
		echo "OK.<br />\n";
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


	if( $old_db_version < 9400 )
	{
		/*
		 * TODO: the following paragraph needs to be rephrased probably. I've not understand it before anyway.. :p
		 *       Please read through all the new comments/explanations and ask/rephrase where it's not clear. (blueyed)
		 *
		 * CONTRIBUTORS: If you need changes and we haven't started a block for next release yet, put them here!
		 * Then create a new extension block, and increase db version numbers everywhere where needed in this file.
		 */

		/*
		 * Only DB table column renames should go here.
		 *
		 * It gets a bit tricky with them if you cannot say using $old_db_version when the column
		 * has been created with the original name: you probably have to use "SHOW COLUMNS FROM table"
		 * to see if it (the original name) is there.
		 *
		 * Then put the generated ALTER COLUMN query here, before the schema upgrade is done (below).
		 *
		 * This is because we cannot detect a column rename easily.
		 *
		 * See below for "normal" DB upgrade.
		 */
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


	/*
	 * Since $new_db_version == 9100 (Phoenix-Beta) we alter the existing tables to match our
	 * scheme here. (Except for renaming table column names - see above).
	 *
	 * It is easy:
	 * - To change DB table layout, alter $schema_queries in /install/_db_schema.inc.php.
	 *
	 * - To insert default data, add it to the corresponding block in
	 *   install_insert_default_data() (/install/_db_schema.inc.php).
	 */

	// Alter DB to match DB schema:
	// TODO: This could be made interactive! Like "the following queries have to be done" and a possibility to abort
	install_make_db_schema_current( true );


	if( $old_db_version < 9300 )
	{ // This has to go here, because it uses fields, that are created through install_make_db_schema_current():
// TODO: this will FAIL eventually because there will be an upgrade checkpoint created earlier. It would be better to manually add the field and do everything above.
// Problem is: at which version exactly?
// dh> I don't get it.. the upgrade checkpoint gets created below...
//     IMHO it only adds complexity to have two db_version pointers.
//     Manually adding the DB field before calling install_make_db_schema_current() seems to be the cleanest solution,
//     so this extra block here is not needed.
// Alternative: having 2 db_version pointers, one for pre-processing and one for post-processing.
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
	}


	// Insert default values, but only those since Phoenix-Alpha:
	// TODO: cleanup/move previous upgrade instructions (data inserts) from above to install_insert_default_data()?!
	$db_version_ge_8999 = ( $old_db_version >= 8999 ? $old_db_version : 8999 );
	install_insert_default_data( $db_version_ge_8999 );


	// Update DB schema version to $new_db_version
	set_upgrade_checkpoint( $new_db_version );


	if( $old_db_version < 9100 )
	{ // Create (EXPERIMENTAL) relations, only if upgrading to Phoenix-Beta:
		// TODO: this should/could get handled by db_delta(), by adding it to the "normal" DB schema, if requested.
		create_b2evo_relations(); // EXPERIMENTAL!

		install_basic_plugins();
	}

	return true;
}


/*
 * $Log$
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