<?php
/**
 * This file implements upgrading of DB tables
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package install
 */
if(substr(basename($_SERVER['SCRIPT_FILENAME']),0,1)=='_')
	die("Please, do not access this page directly.");



/*
 * upgrade_b2evo_tables(-)
 */
function upgrade_b2evo_tables()
{
	global $tableposts, $tableusers, $tablesettings, $tablecategories, $tablecomments,
					$tableblogs, $tablepostcats, $tablehitlog, $tableantispam, $tablegroups, $tableblogusers;
	global $baseurl, $old_db_version, $new_db_version;
	global $Group_Admins, $Group_Priviledged, $Group_Bloggers, $Group_Users;

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
		echo "Upgrading users table... ";
		$query = "ALTER TABLE $tableusers 
							MODIFY COLUMN user_pass CHAR(32) NOT NULL";
		$q = mysql_query($query) or mysql_oops( $query );
		echo "OK.<br />\n";

		echo "Upgrading blogs table... ";
		$query = "ALTER TABLE $tableblogs 
							MODIFY COLUMN blog_lang VARCHAR(20) NOT NULL DEFAULT 'en_US',
							MODIFY COLUMN blog_longdesc TEXT NULL DEFAULT NULL";
		$q = mysql_query($query) or mysql_oops( $query );
		echo "OK.<br />\n";

		echo "Upgrading categories table... ";
		$query = "ALTER TABLE $tablecategories 
							ADD COLUMN cat_description VARCHAR(250) NULL DEFAULT NULL,
							ADD COLUMN cat_longdesc TEXT NULL DEFAULT NULL,
							ADD COLUMN cat_icon VARCHAR(30) NULL DEFAULT NULL";
		$q = mysql_query($query) or mysql_oops( $query );
		echo "OK.<br />\n";

		echo "Upgrading posts table... ";
		$query = "ALTER TABLE $tableposts 
							MODIFY COLUMN post_lang VARCHAR(20) NOT NULL DEFAULT 'en_US',
							ADD COLUMN post_urltitle VARCHAR(50) NULL DEFAULT NULL AFTER post_title,
							ADD COLUMN post_url VARCHAR(250) NULL DEFAULT NULL AFTER post_urltitle,
							ADD COLUMN post_comments ENUM('disabled', 'open', 'closed') NOT NULL DEFAULT 'open' AFTER post_wordcount";
		$q = mysql_query($query) or mysql_oops( $query );
		echo "OK.<br />\n";

		echo "Generating wordcounts... ";
		$query = "SELECT ID, post_content FROM $tableposts WHERE post_wordcount IS NULL";
		$q = mysql_query($query) or mysql_oops( $query );
		$rows_updated = 0;
		while($row = mysql_fetch_assoc($q)) 
		{
			$query_update_wordcount = "UPDATE $tableposts SET post_wordcount = " . bpost_count_words($row['post_content']) . " WHERE ID = " . $row['ID'];
			$q_update_wordcount = mysql_query($query_update_wordcount) or mysql_oops( $query_update_wordcount );
			$rows_updated++;
		}
		echo "OK. ($rows_updated rows updated)</p>\n";
	}


	if( $old_db_version < 8020 )
	{
		echo "Encoding passwords... ";
		$query = "UPDATE $tableusers 
							SET user_pass = MD5(user_pass)";
		$q = mysql_query($query) or mysql_oops( $query );
		echo "OK.<br />\n";
	}

	if( $old_db_version < 8030 )
	{
		echo "Deleting unecessary logs... ";
		$query = "DELETE FROM $tablehitlog
							WHERE hit_ignore IN ('badchar', 'blacklist')";
		$q = mysql_query($query) or mysql_oops( $query );
		echo "OK.<br />\n";

		echo "Updating blog urls... ";
		$query = "SELECT blog_ID, blog_siteurl FROM $tableblogs";
		$q = mysql_query($query) or mysql_oops( $query );
		$rows_updated = 0;
		while($row = mysql_fetch_assoc($q)) 
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
			mysql_query($query_update_blog) or mysql_oops( $query_update_wordcount );
			$rows_updated++; 
		}
		echo "OK. ($rows_updated rows updated)</p>\n";

	}

	if( $old_db_version < 8040 )
	{
		create_antispam();
		
		echo "Upgrading Settings table... ";
		$query = "ALTER TABLE $tablesettings
							ADD COLUMN last_antispam_update datetime NOT NULL default '2000-01-01 00:00:00'";
		$q = mysql_query($query) or mysql_oops( $query );
		echo "OK.<br />\n";
	}	               

	if( $old_db_version < 8050 )
	{
		echo "Upgrading blogs table... ";
		$query = "ALTER TABLE $tableblogs
							ADD COLUMN blog_allowtrackbacks tinyint(1) NOT NULL default 1,
							ADD COLUMN blog_allowpingbacks tinyint(1) NOT NULL default 1,
							ADD COLUMN blog_pingb2evonet tinyint(1) NOT NULL default 0,
							ADD COLUMN blog_pingtechnorati tinyint(1) NOT NULL default 0,
							ADD COLUMN blog_pingweblogs tinyint(1) NOT NULL default 0,
							ADD COLUMN blog_pingblodotgs tinyint(1) NOT NULL default 0,
							ADD COLUMN blog_disp_bloglist tinyint NOT NULL DEFAULT 1";
		$q = mysql_query($query) or mysql_oops( $query );
		echo "OK.<br />\n";

		// Create User Groups
		create_groups();

		echo "Creating user blog permissions... ";
		// Admin: full rights for all blogs (look 'ma, doing a natural join! :>)
		$query = "INSERT INTO $tableblogusers( bloguser_blog_ID, bloguser_user_ID, 
								bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments, 
								bloguser_perm_cats, bloguser_perm_properties)
							SELECT blog_ID, ID, 'published,deprecated,protected,private,draft', 1, 1, 1, 1
							FROM $tableusers, $tableblogs
							WHERE user_level = 10";
		$q = mysql_query($query) or mysql_oops( $query );
		
		// Normal users: basic rights for all blogs (can't stop doing joins :P)
		$query = "INSERT INTO $tableblogusers( bloguser_blog_ID, bloguser_user_ID, 
								bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments, 
								bloguser_perm_cats, bloguser_perm_properties)
							SELECT blog_ID, ID, 'published,protected,private,draft', 0, 1, 0, 0
							FROM $tableusers, $tableblogs
							WHERE user_level > 0 AND user_level < 10";
		$q = mysql_query($query) or mysql_oops( $query );
		echo "OK.<br />\n";

		echo "Upgrading users table... ";
		$query = "ALTER TABLE $tableusers
							ADD COLUMN user_notify tinyint(1) NOT NULL default 1,
							ADD COLUMN user_grp_ID int(4) NOT NULL default 1,
							MODIFY COLUMN user_idmode varchar(20) NOT NULL DEFAULT 'login', 
							ADD KEY user_grp_ID (user_grp_ID)";
		$q = mysql_query($query) or mysql_oops( $query );
		echo "OK.<br />\n";

		echo "Assigning user groups... ";

		// Default is 1, so admins are already set.

		// Basic Users:
		$query = "UPDATE $tableusers 
							SET user_grp_ID = $Group_Users->ID
							WHERE user_level = 0";
		$q = mysql_query($query) or mysql_oops( $query );

		// Bloggers:
		$query = "UPDATE $tableusers 
							SET user_grp_ID = $Group_Bloggers->ID
							WHERE user_level > 0 AND user_level < 10";
		$q = mysql_query($query) or mysql_oops( $query );

		echo "OK.<br />\n";

		echo "Upgrading settings table... ";
		$query = "ALTER TABLE $tablesettings
							DROP COLUMN time_format,
							DROP COLUMN date_format,
							ADD COLUMN pref_newusers_grp_ID int unsigned DEFAULT 4 NOT NULL,
							ADD COLUMN pref_newusers_level tinyint unsigned DEFAULT 1 NOT NULL,
							ADD COLUMN pref_newusers_canregister tinyint unsigned DEFAULT 0 NOT NULL,
							DROP KEY ID";
		$q = mysql_query($query) or mysql_oops( $query );
		echo "OK.<br />\n";
	}
		
	if( $old_db_version < 8060 )
	{
		/* 
		 * CONTRIBUTORS: If you need some more changes, put them here!
		 * Then create a new extension block, and increase db version numbers
		 * everywhere where needed in this file.
		 */
	}
	
	echo "Update DB schema version to $new_db_version... ";
	$query = "UPDATE $tablesettings SET db_version = $new_db_version WHERE ID = 1";
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";
	
}


?>