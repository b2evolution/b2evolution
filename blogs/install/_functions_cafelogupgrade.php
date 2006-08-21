<?php
/**
 * This file implements upgrading from cafelog/b2
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package install
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 */
function upgrade_cafelog_tables()
{
	global $baseurl, $old_db_version, $new_db_version;
	global $default_locale;
	global $oldtableposts, $oldtableusers, $oldtablesettings, $oldtablecategories, $oldtablecomments;
	global $timestamp, $admin_email;
	global $Group_Admins, $Group_Privileged, $Group_Bloggers, $Group_Users;
	global $blog_all_ID, $blog_a_ID, $blog_b_ID, $blog_linkblog_ID;
	global $cat_ann_a, $cat_news, $cat_bg, $cat_ann_b, $cat_fun, $cat_life, $cat_web, $cat_sports, $cat_movies, $cat_music, $cat_b2evo, $cat_linkblog_b2evo, $cat_linkblog_contrib;
	global $DB;

	// Create blogs:
	create_default_blogs( 'Blog A (Upg)', 'Blog A (Cafelog Upgrade)', T_("This blog holds all your posts upgraded from Cafelog. This blog is named '%s'. %s"), false );


	echo 'Copying Cafelog settings... ';
	// forcing paged mode because this works so much better !!!
	// You can always change it back in the options if you don't like it.

	$query = "SELECT archive_mode, time_difference, AutoBR FROM $oldtablesettings";
	$q = $DB->get_row( $query );

	create_default_settings( array( // overrides:
		'archive_mode' => $q->archive_mode,
		'time_difference' => ( $q->time_difference*3600 ),
		'AutoBR' => $q->AutoBR,
		) );

	echo 'Copying Cafelog users... ';
	$query = "INSERT INTO T_users( user_ID, user_login, user_pass, user_firstname, user_lastname,
							user_nickname, user_icq, user_email, user_url, user_ip, user_domain, user_browser,
							dateYMDhour, user_level,	user_aim, user_msn, user_yim, user_idmode )
						SELECT ID, user_login, MD5(user_pass), user_firstname, user_lastname, user_nickname,
							user_icq, user_email, user_url, user_ip, user_domain, user_browser, dateYMDhour,
							user_level,	user_aim, user_msn, user_yim, user_idmode
						FROM $oldtableusers";
	$DB->query( $query );
	echo "OK.<br />\n";

	echo 'Make sure user #1 is at level 10... ';
	$query = "UPDATE T_users
							 SET user_level = 10
						 WHERE user_ID = 1";
	$DB->query( $query );
	echo "OK.<br />\n";

	echo 'Setting groups... ';
	$query = "UPDATE T_users
							 SET user_grp_ID = ".$Group_Users->get('ID')."
						 WHERE user_level = 0";
	$DB->query( $query );

	$query = "UPDATE T_users
							 SET user_grp_ID = ".$Group_Bloggers->get('ID')."
						 WHERE user_level > 0 and user_level < 10 ";
	$DB->query( $query );

  // Level 10 users get default group (1)

	echo "OK.<br />\n";

	echo 'Creating user blog permissions... ';
	// Admin for all blogs:
	$query = "INSERT INTO T_coll_user_perms( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember,
							bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments,
							bloguser_perm_cats, bloguser_perm_properties)
						SELECT blog_ID, ID, 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1
						FROM $oldtableusers, T_blogs
						WHERE user_level = 10";
	$DB->query( $query );

	// Normal users for upgraded blog:
	$query = "INSERT INTO T_coll_user_perms( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember,
							bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments,
							bloguser_perm_cats, bloguser_perm_properties)
						SELECT $blog_a_ID, ID, 1, 'published,protected,private,draft', 0, 1, 0, 0
						FROM $oldtableusers
						WHERE user_level > 0 AND user_level < 10";
	$DB->query( $query );
	echo "OK.<br />\n";

	echo 'Copying Cafelog categories... ';
	$query = "INSERT INTO T_categories( cat_ID, cat_parent_ID, cat_name, cat_blog_ID ) SELECT DISTINCT cat_ID, NULL, cat_name, 2 FROM $oldtablecategories";
	$DB->query( $query );
	echo "OK.<br />\n";


	echo 'Copying Cafelog posts... ';
	$query = "INSERT INTO T_posts( post_ID, post_creator_user_ID, post_datestart, post_datemodified, post_status, post_locale, post_content, post_title, post_main_cat_ID, post_notifications_status )
	SELECT ID, post_author, post_date, post_date, 'published', '$default_locale', post_content, post_title, post_category, 'finished' FROM $oldtableposts";
	$DB->query( $query );
	echo "OK.<br />\n";

	echo 'Generating wordcounts... ';
	$query = "SELECT post_ID, post_content FROM T_posts";
	$i = 0;
	foreach( $DB->get_results( $query, ARRAY_A ) as $row )
	{
		$query_update_wordcount = "UPDATE T_posts
															SET post_wordcount = " . bpost_count_words($row['post_content']) . "
															WHERE post_ID = " . $row['post_ID'];
		$DB->query($query_update_wordcount);
		$i++;
	}
	echo "OK. ($i rows updated)<br />\n";

	echo 'Generating postcats... ';
	$query = "INSERT INTO T_postcats( postcat_post_ID, postcat_cat_ID ) SELECT post_ID, post_main_cat_ID FROM T_posts";
	$DB->query( $query );
	echo "OK.<br />\n";

	echo 'Copying Cafelog comments... ';
	$query = "INSERT INTO T_comments( comment_ID, comment_post_ID, comment_type, comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_content, comment_karma ) SELECT comment_ID, comment_post_ID, 'comment', comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_content, comment_karma FROM $oldtablecomments";
	$DB->query( $query );
	echo "OK.<br />\n";

	echo 'Qualifying comments... Trackback... ';
	$query = "UPDATE T_comments SET comment_type = 'trackback' WHERE comment_content LIKE '<trackback />%'";
	$DB->query( $query );
	echo "OK.<br />\n";

	echo 'Qualifying comments... Pingback... ';
	$query = "UPDATE T_comments SET comment_type = 'pingback' WHERE comment_content LIKE '<pingback />%'";
	$DB->query( $query );
	echo "OK.<br />\n";


	create_default_categories( false /* not for A */ );

	// POPULATE THE LINKBLOG:
	populate_linkblog( $now, $cat_linkblog_b2evo, $cat_linkblog_contrib );

	// Create other default contents:
	create_default_contents( false /* not for A */ );


  // Cleanup extra quotes in comments:
  cleanup_post_quotes();
	cleanup_comment_quotes();

}

/*
 * $Log$
 * Revision 1.42  2006/08/21 16:07:44  fplanque
 * refactoring
 *
 * Revision 1.41  2006/07/04 17:32:30  fplanque
 * no message
 *
 * Revision 1.40  2006/07/02 21:53:31  blueyed
 * time difference as seconds instead of hours; validate user#1 on upgrade; bumped new_db_version to 9300.
 *
 * Revision 1.39  2006/03/12 23:09:26  fplanque
 * doc cleanup
 *
 * Revision 1.38  2005/12/30 18:08:24  fplanque
 * no message
 *
 */
?>