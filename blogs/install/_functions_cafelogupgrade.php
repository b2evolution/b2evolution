<?php
/**
 * This file implements upgrading from cafelog/b2
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
 * upgrade_cafelog_tables(-)
 */
function upgrade_cafelog_tables()
{
	global $tableposts, $tableusers, $tablesettings, $tablecategories, $tablecomments,
					$tableblogs, $tablepostcats, $tablehitlog, $tableantispam, $tablegroups, $tableblogusers;
	global $baseurl, $old_db_version, $new_db_version;
	global $default_locale;
	global $oldtableposts, $oldtableusers, $oldtablesettings, $oldtablecategories, $oldtablecomments;
	global $timestamp, $admin_email;
	global $Group_Admins, $Group_Priviledged, $Group_Bloggers, $Group_Users;
	global $blog_all_ID, $blog_a_ID, $blog_b_ID, $blog_roll_ID;
	global $cat_ann_a, $cat_news, $cat_bg, $cat_ann_b, $cat_fun, $cat_life, $cat_web, $cat_sports, $cat_movies, $cat_music, $cat_b2evo, $cat_blogroll_b2evo, $cat_blogroll_contrib;

	// Create blogs:
	create_default_blogs( 'Blog A (Upg)', 'Blog A (Cafelog Upgrade)', T_("This blog holds all your posts upgraded from Cafelog. This blog is named '%s'. It has index #%d in the database. By default it is accessed through a stub file called '<code>%s</code>'. %s"), false );	


	echo "Copying Cafelog settings... ";	
	// forcing paged mode because this works so much better !!!
	// You can always change it back in the options if you don't like it.
	$query = "INSERT INTO $tablesettings( ID, posts_per_page, what_to_show, archive_mode, time_difference, AutoBR, db_version, last_antispam_update) SELECT ID, 5, 'paged', archive_mode, time_difference, AutoBR, $new_db_version, '2000-01-01 00:00:00' FROM $oldtablesettings";
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";
	
	echo "Copying Cafelog users... ";
	$query = "INSERT INTO $tableusers( ID, user_login, user_pass, user_firstname, user_lastname,
							user_nickname, user_icq, user_email, user_url, user_ip, user_domain, user_browser,
							dateYMDhour, user_level,	user_aim, user_msn, user_yim, user_idmode ) 
						SELECT ID, user_login, MD5(user_pass), user_firstname, user_lastname, user_nickname,
							user_icq, user_email, user_url, user_ip, user_domain, user_browser, dateYMDhour,
							user_level,	user_aim, user_msn, user_yim, user_idmode 
						FROM $oldtableusers";
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";

	echo "Setting groups...";
	$query = "UPDATE $tableusers
							 SET user_grp_ID = ".$Group_Users->get('ID')."
						 WHERE user_level = 0";
	$q = mysql_query($query) or mysql_oops( $query );

	$query = "UPDATE $tableusers
							 SET user_grp_ID = ".$Group_Bloggers->get('ID')."
						 WHERE user_level > 0 and user_level < 10 ";
	$q = mysql_query($query) or mysql_oops( $query );

	echo "OK.<br />\n";

	echo "Creating user blog permissions... ";
	// Admin for upgraded blog:
	$query = "INSERT INTO $tableblogusers( bloguser_blog_ID, bloguser_user_ID, 
							bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments, 
							bloguser_perm_cats, bloguser_perm_properties)
						SELECT $blog_a_ID, ID, 'published,deprecated,protected,private,draft', 1, 1, 1, 1
						FROM $oldtableusers
						WHERE user_level = 10";
	$q = mysql_query($query) or mysql_oops( $query );

	// Admin for blog #1:
	$query = "INSERT INTO $tableblogusers( bloguser_blog_ID, bloguser_user_ID, 
							bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments, 
							bloguser_perm_cats, bloguser_perm_properties)
						SELECT $blog_all_ID, ID, 'published,deprecated,protected,private,draft', 1, 1, 1, 1
						FROM $oldtableusers
						WHERE user_level = 10";
	$q = mysql_query($query) or mysql_oops( $query );

	// Admin for blog B:
	$query = "INSERT INTO $tableblogusers( bloguser_blog_ID, bloguser_user_ID, 
							bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments, 
							bloguser_perm_cats, bloguser_perm_properties)
						SELECT $blog_b_ID, ID, 'published,deprecated,protected,private,draft', 1, 1, 1, 1
						FROM $oldtableusers
						WHERE user_level = 10";
	$q = mysql_query($query) or mysql_oops( $query );

	// Admin for blog roll:
	$query = "INSERT INTO $tableblogusers( bloguser_blog_ID, bloguser_user_ID, 
							bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments, 
							bloguser_perm_cats, bloguser_perm_properties)
						SELECT $blog_roll_ID, ID, 'published,deprecated,protected,private,draft', 1, 1, 1, 1
						FROM $oldtableusers
						WHERE user_level = 10";
	$q = mysql_query($query) or mysql_oops( $query );

	// Normal users for upgraded blog:
	$query = "INSERT INTO $tableblogusers( bloguser_blog_ID, bloguser_user_ID, 
							bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments, 
							bloguser_perm_cats, bloguser_perm_properties)
						SELECT $blog_a_ID, ID, 'published,protected,private,draft', 0, 1, 0, 0
						FROM $oldtableusers
						WHERE user_level > 0 AND user_level < 10";
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";
	
	echo "Copying Cafelog categories... ";
	$query = "INSERT INTO $tablecategories( cat_ID, cat_parent_ID, cat_name, cat_blog_ID ) SELECT DISTINCT cat_ID, NULL, cat_name, 2 FROM $oldtablecategories";
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";

	
	echo "Copying Cafelog posts... ";
	$query = "INSERT INTO $tableposts( ID, post_author, post_issue_date, post_mod_date, post_status, post_locale, post_content,post_title, post_category, post_autobr, post_flags, post_karma)  
	SELECT ID, post_author, post_date, post_date, 'published', '$default_locale', post_content, post_title, post_category, 1, 'pingsdone,html,imported', post_karma FROM $oldtableposts";
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";

	echo "Generating wordcounts... ";
	$query = "SELECT ID, post_content FROM $tableposts";
	$q = mysql_query($query) or mysql_oops( $query );
	
	$rows_updated = 0;
	
	while($row = mysql_fetch_assoc($q)) 
	{
		$query_update_wordcount = "UPDATE $tableposts SET post_wordcount = " . bpost_count_words($row['post_content']) . " WHERE ID = " . $row['ID'];
		$q_update_wordcount = mysql_query($query_update_wordcount) or mysql_oops( $query_update_wordcount );
		
		$rows_updated++;
	}
	
	echo "OK. ($rows_updated rows updated)<br />\n";

	echo "Generating postcats... ";
	$query = "INSERT INTO $tablepostcats( postcat_post_ID, postcat_cat_ID ) SELECT ID, post_category FROM $tableposts";		
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";
	
	echo "Copying Cafelog comments... ";
	$query = "INSERT INTO $tablecomments( comment_ID, comment_post_ID, comment_type, comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_content, comment_karma ) SELECT comment_ID, comment_post_ID, 'comment', comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_content, comment_karma FROM $oldtablecomments";
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";

	echo "Qualifying comments... Trackback... ";
	$query = "UPDATE $tablecomments SET comment_type = 'trackback' WHERE comment_content LIKE '<trackback />%'";		
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";	

	echo "Qualifying comments... Pingback... ";
	$query = "UPDATE $tablecomments SET comment_type = 'pingback' WHERE comment_content LIKE '<pingback />%'";		
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";	
	
	
	create_default_categories( false /* not for A */ );

	// POPULATE THE BLOGROLL:
	populate_blogroll( $now, $cat_blogroll_b2evo, $cat_blogroll_contrib );

	// Create other default contents:
	create_default_contents( false /* not for A */ );

}

?>