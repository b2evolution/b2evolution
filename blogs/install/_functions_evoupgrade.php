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
if( substr(basename($_SERVER['SCRIPT_FILENAME']), 0, 1) == '_' )
	die( 'Please, do not access this page directly.' );



/*
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

	// Check DB version:
	check_db_version();
	if( $old_db_version == $new_db_version )
	{
		echo '<p>The database schema is already up to date. There is nothing to do.</p>';
		echo '<p>You can <a href="../admin/b2edit.php">log in</a> with your usual b2 username and password.</p>';
		return;
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
		echo "OK. (".$count($q)." rows updated)<br />\n";
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
				echo ' <strong>WARNING: please check blog #', $blog_ID, ' manually.</strong> ';
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

		echo 'Creating user blog permissions... ';
		// Admin: full rights for all blogs (look 'ma, doing a natural join! :>)
		$query = "INSERT INTO $tableblogusers( bloguser_blog_ID, bloguser_user_ID,
								bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments,
								bloguser_perm_cats, bloguser_perm_properties)
							SELECT blog_ID, ID, 'published,deprecated,protected,private,draft', 1, 1, 1, 1
							FROM $tableusers, $tableblogs
							WHERE user_level = 10";
		$DB->query( $query );

		// Normal users: basic rights for all blogs (can't stop doing joins :P)
		$query = "INSERT INTO $tableblogusers( bloguser_blog_ID, bloguser_user_ID,
								bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments,
								bloguser_perm_cats, bloguser_perm_properties)
							SELECT blog_ID, ID, 'published,protected,private,draft', 0, 1, 0, 0
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
	{	// upgrade to 0.8.9+CVS
		
		create_locales();

		/**
		 *
		 * converts languages in a given table into according locales
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
				$default_locale = 'en-US';
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
				if( strlen($lkey) == 2 )
				{ // we have an old two letter lang code to convert
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
				{ // we have nothing converted yet
					if( !preg_match('/[a-z]{2}-[A-Z]{2}(-.{1,14})?/', $lkey) )
					{ // no valid locale in DB, setting default.
						$DB->query( "UPDATE $table 
														SET $columnlang = '$default_locale' 
													WHERE $columnlang = '$lkey'" );
						echo 'forced to default locale \''. $default_locale. '\'<br />';
						
					} else echo 'nothing to update, already valid!<br />';
				}
			}
			echo "\n";
		}  // convert_lang_to_locale(-)
		
		echo 'Upgrading posts table... ';
		$query = "ALTER TABLE $tableposts
							CHANGE COLUMN post_date post_issue_date datetime NOT NULL default '0000-00-00 00:00:00',
							ADD COLUMN post_mod_date datetime NOT NULL default '0000-00-00 00:00:00' 
										AFTER post_issue_date,
							CHANGE COLUMN post_lang post_locale varchar(20) NOT NULL default 'en-US',
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
							CHANGE blog_lang blog_locale varchar(20) NOT NULL default 'en-US'";
		$DB->query( $query );
		echo "OK.<br />\n";

		// convert given languages to locales
		convert_lang_to_locale( $tableblogs, 'blog_locale', 'blog_ID' );
		
		echo 'Upgrading settings table... ';
		$query = "ALTER TABLE $tablesettings
							ADD COLUMN default_locale VARCHAR( 20 ) DEFAULT 'en-US' NOT NULL AFTER ID,
							ADD COLUMN pref_links_extrapath tinyint unsigned DEFAULT 0 NOT NULL,
							ADD COLUMN pref_permalink_type ENUM( 'urltitle', 'pid', 'archive#id', 'archive#title' ) NOT NULL DEFAULT 'urltitle'";
		$DB->query( $query );
		echo "OK.<br />\n";


	}


	if( $old_db_version < 8070 )
	{
		/*
		 * CONTRIBUTORS: If you need some more changes, put them here!
		 * Then create a new extension block, and increase db version numbers
		 * everywhere where needed in this file.
		 */
		
	}
	
	echo "Update DB schema version to $new_db_version... ";
	$query = "UPDATE $tablesettings SET db_version = $new_db_version WHERE ID = 1";
	$DB->query( $query );
	echo "OK.<br />\n";

}


?>