<?php
/**
 * This file implements upgrading of DB tables
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package install
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * converts languages in a given table into according locales
 *
 * {@internal convert_lang_to_locale(-)}}
 *
 * @author blueyed
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
	$rows = $DB->get_results( $query, ARRAY_A );
	$languagestoconvert = array();
	if( count( $rows ) ) foreach( $rows as $row )
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
	global $tableposts, $tableusers, $tablesettings, $tablecategories, $tablecomments,
					$tableblogs, $tablepostcats, $tablehitlog, $tableantispam, $tablegroups, $tableblogusers;
	global $baseurl, $old_db_version, $new_db_version;
	global $Group_Admins, $Group_Priviledged, $Group_Bloggers, $Group_Users;
	global $locales, $default_locale;
	global $DB;
	global $admin_url;

	// Check DB version:
	check_db_version();
	if( $old_db_version == $new_db_version )
	{
		echo '<p>'.T_('The database schema is already up to date. There is nothing to do.').'</p>';
		printf( '<p>'.T_('Now you can <a %s>log in</a> with your usual %s username and password.').'</p>', 'href="'.$admin_url.'/"', 'b2evolution' );
		return false;
	}


	if( $old_db_version < 8010 )
	{
		echo 'Upgrading users table... ';
		$query = "ALTER TABLE $tableusers
							MODIFY COLUMN user_pass CHAR(32) NOT NULL";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Upgrading blogs table... ';
		$query = "ALTER TABLE $tableblogs
							MODIFY COLUMN blog_lang VARCHAR(20) NOT NULL DEFAULT 'en_US',
							MODIFY COLUMN blog_longdesc TEXT NULL DEFAULT NULL";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Upgrading categories table... ';
		$query = "ALTER TABLE $tablecategories
							ADD COLUMN cat_description VARCHAR(250) NULL DEFAULT NULL,
							ADD COLUMN cat_longdesc TEXT NULL DEFAULT NULL,
							ADD COLUMN cat_icon VARCHAR(30) NULL DEFAULT NULL";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Upgrading posts table... ';
		$query = "ALTER TABLE $tableposts
							MODIFY COLUMN post_lang VARCHAR(20) NOT NULL DEFAULT 'en_US',
							ADD COLUMN post_urltitle VARCHAR(50) NULL DEFAULT NULL AFTER post_title,
							ADD COLUMN post_url VARCHAR(250) NULL DEFAULT NULL AFTER post_urltitle,
							ADD COLUMN post_comments ENUM('disabled', 'open', 'closed') NOT NULL DEFAULT 'open' AFTER post_wordcount";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Generating wordcounts... ';
		$query = "SELECT ID, post_content FROM $tableposts WHERE post_wordcount IS NULL";
		$q = $DB->get_results( $query, ARRAY_A );
		if( count( $q ) ) foreach( $q as $row )
		{
			$query_update_wordcount = "UPDATE $tableposts
																SET post_wordcount = " . bpost_count_words($row['post_content']) . "
																WHERE ID = " . $row['ID'];
			$DB->query($query_update_wordcount);
		}
		echo "OK. (".count($q)." rows updated)<br />\n";
	}


	if( $old_db_version < 8020 )
	{
		echo 'Encoding passwords... ';
		$query = "UPDATE $tableusers
							SET user_pass = MD5(user_pass)";
		$DB->query( $query );
		echo "OK.<br />\n";
	}


	if( $old_db_version < 8030 )
	{
		echo 'Deleting unecessary logs... ';
		$query = "DELETE FROM $tablehitlog
							WHERE hit_ignore IN ('badchar', 'blacklist')";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Updating blog urls... ';
		$query = "SELECT blog_ID, blog_siteurl FROM $tableblogs";
		$q = $DB->query( $query );
		if( count( $q ) ) foreach( $q as $row )
		{
			$blog_ID = $row['blog_ID'];
			$blog_siteurl = $row['blog_siteurl'];
			// echo $blog_siteurl;
			if( strpos( $blog_siteurl, $baseurl ) !== 0 )
			{	// If not found at position 0
				echo ' <strong>WARNING: please check blog #', $blog_ID, ' manually.</strong><br /> ';
				continue;
			}
			// crop off the baseurl:
			$blog_siteurl = substr( $blog_siteurl, strlen( $baseurl) );
			// echo ' -> ', $blog_siteurl,'<br />';

			$query_update_blog = "UPDATE $tableblogs SET blog_siteurl = '$blog_siteurl' WHERE blog_ID = $blog_ID";
			// echo $query_update_blog, '<br>';
			$DB->query( $query_update_blog );
		}
		echo "OK. (".count($rows_updated)." rows updated)</p>\n";

	}


	if( $old_db_version < 8040 )
	{ // upgarde to 0.8.7
		create_antispam();

		echo 'Upgrading Settings table... ';
		$query = "ALTER TABLE $tablesettings
							ADD COLUMN last_antispam_update datetime NOT NULL default '2000-01-01 00:00:00'";
		$DB->query( $query );
		echo "OK.<br />\n";
	}


	if( $old_db_version < 8050 )
	{ // upgrade to 0.8.9
		echo 'Upgrading blogs table... ';
		$query = "ALTER TABLE $tableblogs
							ADD COLUMN blog_allowtrackbacks tinyint(1) NOT NULL default 1,
							ADD COLUMN blog_allowpingbacks tinyint(1) NOT NULL default 1,
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
		$query = "INSERT INTO $tableblogusers( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember,
								bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments,
								bloguser_perm_cats, bloguser_perm_properties)
							SELECT blog_ID, ID, 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1
							FROM $tableusers, $tableblogs
							WHERE user_level = 10";
		$DB->query( $query );

		// Normal users: basic rights for all blogs (can't stop doing joins :P)
		$query = "INSERT INTO $tableblogusers( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember,
								bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments,
								bloguser_perm_cats, bloguser_perm_properties)
							SELECT blog_ID, ID, 1, 'published,protected,private,draft', 0, 1, 0, 0
							FROM $tableusers, $tableblogs
							WHERE user_level > 0 AND user_level < 10";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Upgrading users table... ';
		$query = "ALTER TABLE $tableusers
							ADD COLUMN user_notify tinyint(1) NOT NULL default 1,
							ADD COLUMN user_grp_ID int(4) NOT NULL default 1,
							MODIFY COLUMN user_idmode varchar(20) NOT NULL DEFAULT 'login',
							ADD KEY user_grp_ID (user_grp_ID)";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Assigning user groups... ';

		// Default is 1, so admins are already set.

		// Basic Users:
		$query = "UPDATE $tableusers
							SET user_grp_ID = $Group_Users->ID
							WHERE user_level = 0";
		$DB->query( $query );

		// Bloggers:
		$query = "UPDATE $tableusers
							SET user_grp_ID = $Group_Bloggers->ID
							WHERE user_level > 0 AND user_level < 10";
		$DB->query( $query );

		echo "OK.<br />\n";

		echo 'Upgrading settings table... ';
		$query = "ALTER TABLE $tablesettings
							DROP COLUMN time_format,
							DROP COLUMN date_format,
							ADD COLUMN pref_newusers_grp_ID int unsigned DEFAULT 4 NOT NULL,
							ADD COLUMN pref_newusers_level tinyint unsigned DEFAULT 1 NOT NULL,
							ADD COLUMN pref_newusers_canregister tinyint unsigned DEFAULT 0 NOT NULL";
		$DB->query( $query );
		echo "OK.<br />\n";
	}


	if( $old_db_version < 8060 )
	{	// --------------------------------------------
		// upgrade to 0.9
		// --------------------------------------------

		// Important check:
		$stub_list = $DB->get_col( "SELECT blog_stub
																	FROM $tableblogs
																	GROUP BY blog_stub
																	HAVING COUNT(*) > 1" );
		if( !empty($stub_list) )
		{
			echo '<div class="error"><p class="error">';
			printf( T_("It appears that the following blog stub names are used more than once: ['%s']" ), implode( "','", $stub_list ) );
			echo '</p><p>';
			printf( T_("I can't upgrade until you make them unique. DB field: [%s]" ), $tableblogs.'.blog_stub' );
			echo '</p></div>';
			return false;
		}

		// Create locales
		create_locales();

		echo 'Upgrading posts table... ';
		$query = "UPDATE $tableposts
							SET post_urltitle = NULL";
		$DB->query( $query );

		$query = "ALTER TABLE $tableposts
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

		$query = "UPDATE $tableposts
							SET post_mod_date = post_issue_date";
		$DB->query( $query );
		echo "OK.<br />\n";

		// convert given languages to locales
		convert_lang_to_locale( $tableposts, 'post_locale', 'ID' );

		echo 'Upgrading blogs table... ';
		$query = "ALTER TABLE $tableblogs
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

		$query = "UPDATE $tableblogs
							SET blog_access_type = 'stub',
									blog_default_skin = 'custom'";
		$DB->query( $query );

		echo "OK.<br />\n";

		// convert given languages to locales
		convert_lang_to_locale( $tableblogs, 'blog_locale', 'blog_ID' );

		echo 'Converting settings table... ';

		// get old settings
		$query = "SELECT * FROM $tablesettings";
		$row = $DB->get_row( $query, ARRAY_A );

		$transform = array(
			'posts_per_page' => array(7),
			'what_to_show' => array('days'),
			'archive_mode' => array('weekly'),
			'time_difference' => array(0),
			'AutoBR' => array(1),
			'last_antispam_update' => array('2000-01-01 00:00:00', 'antispam_last_update'),
			'pref_newusers_grp_ID' => array(4, 'newusers_grp_ID'),
			'pref_newusers_level'  => array(1, 'newusers_level'),
			'pref_newusers_canregister' => array(0, 'newusers_canregister'),
		);

		$query = "INSERT INTO $tablesettings (set_name, set_value) VALUES ";

		foreach( $transform as $oldkey => $newarr )
		{
			$newname = (isset($newarr[1])) ? $newarr[1] : $oldkey;
			if( !isset( $row[$oldkey] ) )
			{
				echo '&nbsp;&middot;Setting '.$oldkey.' not found, using defaults.<br />';
				$trans[ $newname ] = $newarr[0];
			}
			else
			{
				$trans[ $newname ] = $row[$oldkey];
			}
		}

		$query .= "
			( 'db_version', '$new_db_version' ),
			( 'default_locale', 'en-EU' ),
			( 'links_extrapath', '0' ),
			( 'permalink_type', 'urltitle' ),
			( 'user_minpwdlen', '5' )
			";

		foreach( $trans as $name => $value )
		{
			$query .= ", ('$name', '".$DB->escape($value)."')";
		}

		// drop old table
		$DB->query( "DROP TABLE IF EXISTS $tablesettings");

		// create new table
		$DB->query( "CREATE TABLE $tablesettings (
								set_name VARCHAR( 30 ) NOT NULL ,
								set_value VARCHAR( 255 ) NULL ,
								PRIMARY KEY ( set_name )
								)");

		// write new settings
		$DB->query( $query );
		echo "OK.<br />\n";

		if( !isset( $tableblogusers_isuptodate ) )
		{
			echo 'Upgrading Blog-User permissions table... ';
			$query = "ALTER TABLE $tableblogusers
								ADD COLUMN bloguser_ismember tinyint NOT NULL default 0 AFTER bloguser_user_ID";
			$DB->query( $query );

			// Any row that is created holds at least one permission,
			// minimum permsission is to be a member, so we add that one too, to all existing rows.
			$DB->query( "UPDATE $tableblogusers
											SET bloguser_ismember = 1" );
			echo "OK.<br />\n";
		}

		echo 'Upgrading Comments table... ';
		$query = "ALTER TABLE $tablecomments
							ADD COLUMN comment_author_ID int unsigned NULL default NULL AFTER comment_status,
							MODIFY COLUMN comment_author varchar(100) NULL,
							MODIFY COLUMN comment_author_email varchar(100) NULL,
							MODIFY COLUMN comment_author_url varchar(100) NULL,
							MODIFY COLUMN comment_author_IP varchar(23) NOT NULL default ''";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Upgrading Users table... ';
		$query = "ALTER TABLE $tableusers ADD user_locale VARCHAR( 20 ) DEFAULT 'en-EU' NOT NULL AFTER user_yim";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo "Creating DB schema version checkpoint at 8060... ";
		// This is because the next operation might timeout!
		// The checkpoint will allow to restart the script and continue where it stopped
		$DB->query( "UPDATE $tablesettings
										SET set_value = '8060'
									WHERE set_name = 'db_version'" );
		echo "OK.<br />\n";
	}

	if( $old_db_version < 8062 )
	{ // --------------------------------------------
		// upgrade to 0.9.0.4
		// --------------------------------------------
		echo "Checking for extra quote escaping in posts... ";
		$query = "SELECT ID, post_title, post_content
								FROM $tableposts
							 WHERE post_title LIKE '%\\\\\\\\\'%'
							 		OR post_title LIKE '%\\\\\\\\\"%'
							 		OR post_content LIKE '%\\\\\\\\\'%'
							 		OR post_content LIKE '%\\\\\\\\\"%' ";
		/* FP: the above looks overkill, but mySQL is really full of surprises...
						tested on 4.0.14-nt */
		// echo $query;
		$rows = $DB->get_results( $query, ARRAY_A );
		if( $DB->num_rows )
		{
			echo 'Updating '.$DB->num_rows.' posts... ';
			foreach( $rows as $row )
			{
				// echo '<br />'.$row['post_title'];
				$query = "UPDATE $tableposts
									SET post_title = ".$DB->quote( stripslashes( $row['post_title'] ) ).",
											post_content = ".$DB->quote( stripslashes( $row['post_content'] ) )."
									WHERE ID = ".$row['ID'];
				// echo '<br />'.$query;
				$DB->query( $query );
			}
		}
		echo "OK.<br />\n";

		echo "Creating DB schema version checkpoint at 8062... ";
		// This is because the next operation might timeout!
		// The checkpoint will allow to restart the script and continue where it stopped
		$DB->query( "UPDATE $tablesettings
										SET set_value = '8062'
									WHERE set_name = 'db_version'" );
		echo "OK.<br />\n";
	}

	if( $old_db_version < 8064 )
	{ // --------------------------------------------
		// upgrade to 0.9.0.6
		// --------------------------------------------
		echo "Checking for extra quote escaping in comments... ";
		$query = "SELECT comment_ID, comment_content
								FROM $tablecomments
							 WHERE comment_content LIKE '%\\\\\\\\\'%'
							 		OR comment_content LIKE '%\\\\\\\\\"%' ";
		/* FP: the above looks overkill, but mySQL is really full of surprises...
						tested on 4.0.14-nt */
		// echo $query;
		$rows = $DB->get_results( $query, ARRAY_A );
		if( $DB->num_rows )
		{
			echo 'Updating '.$DB->num_rows.' comments... ';
			foreach( $rows as $row )
			{
				$query = "UPDATE $tablecomments
									SET comment_content = ".$DB->quote( stripslashes( $row['comment_content'] ) )."
									WHERE comment_ID = ".$row['comment_ID'];
				// echo '<br />'.$query;
				$DB->query( $query );
			}
		}
		echo "OK.<br />\n";
	}

	if( $old_db_version < 8070 )
	{ // --------------------------------------------
		// upgrade to 0.9.1
		// --------------------------------------------

		echo 'Upgrading blogs table... ';
		$query = "ALTER TABLE EVO_blogs
							ADD blog_commentsexpire INT(4) NOT NULL DEFAULT 0,
							ADD blog_media_location ENUM( 'default', 'subdir', 'custom' ) DEFAULT 'default' NOT NULL AFTER blog_commentsexpire,
							ADD blog_media_subdir VARCHAR( 255 ) NOT NULL AFTER blog_media_location,
							ADD blog_media_fullpath VARCHAR( 255 ) NOT NULL AFTER blog_media_subdir,
							ADD blog_media_url VARCHAR(255) NOT NULL AFTER blog_media_fullpath,
							CHANGE COLUMN blog_stub blog_urlname VARCHAR(255) NOT NULL DEFAULT 'urlname',
							ADD blog_stub VARCHAR(255) NOT NULL DEFAULT 'stub' AFTER blog_urlname,
							DROP INDEX blog_stub,
							ADD UNIQUE blog_urlname ( blog_urlname )";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Copying urlnames to stub names... ';
		$query = 'UPDATE EVO_blogs
							SET blog_stub = blog_urlname';
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Upgrading posts table... ';
		$query = "ALTER TABLE EVO_posts
							ADD post_views INT(4) NOT NULL DEFAULT '0' AFTER post_flags,
							ADD post_commentsexpire DATETIME DEFAULT NULL AFTER post_comments";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Upgrading users table... ';
		$query = "ALTER TABLE EVO_users
							ADD COLUMN user_showonline tinyint(1) NOT NULL default 1 AFTER user_notify,
							ADD COLUMN user_upload_ufolder tinyint(1) NOT NULL default 0 AFTER user_showonline";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Setting new defaults... ';
		$query = "INSERT INTO EVO_settings (set_name, set_value)
							VALUES ( 'reloadpage_timeout', '300' )";
		$DB->query( $query );
		echo "OK.<br />\n";

		echo 'Altering table for Blog-User permissions... ';
		$DB->query( 'ALTER TABLE EVO_blogusers
									ADD COLUMN bloguser_perm_upload tinyint NOT NULL default 0' );
		echo "OK.<br />\n";

		// New tables:
		create_b2evo_tables_091();
	}


	if( $old_db_version < 8080 )
	{
		/*
		 * CONTRIBUTORS: If you need some more changes, put them here!
		 * Then create a new extension block, and increase db version numbers
		 * everywhere where needed in this file.
		 */

	}

	echo "Update DB schema version to $new_db_version... ";
	$DB->query( "UPDATE $tablesettings
									SET set_value = '$new_db_version'
								WHERE set_name = 'db_version'" );
	echo "OK.<br />\n";

	return true;
}


?>