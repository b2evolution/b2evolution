<?php
/**
 * This file implements upgrading from Manywhere Miniblog
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package install
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * upgrade_cafelog_tables(-)
 */
function upgrade_miniblog_tables()
{
	global $baseurl, $old_db_version, $new_db_version;
	global $default_locale;
	global $timestamp, $admin_email;
	global $Group_Admins, $Group_Priviledged, $Group_Bloggers, $Group_Users;
	global $blog_all_ID, $blog_a_ID, $blog_b_ID, $blog_linkblog_ID;
	global $cat_ann_a, $cat_news, $cat_bg, $cat_ann_b, $cat_fun, $cat_life, $cat_web, $cat_sports, $cat_movies, $cat_music, $cat_b2evo, $cat_linkblog_b2evo, $cat_linkblog_contrib;
	global $DB;
	global $install_password, $random_password;

	// Create blogs:
	create_default_blogs( 'Blog A (Upg)', 'Blog A (Miniblog Upgrade)', T_("This blog holds all your posts upgraded from Miniblog. This blog is named '%s'. %s"), false );


	create_default_settings();


	echo 'Copying Miniblog users... ';
	if( !isset( $install_password ) )
	{
		$random_password = substr(md5(uniqid(microtime())),0,6);
	}
	else
	{
		$random_password = $install_password;
	}
	// Admins:
	$query = "INSERT INTO T_users( ID, user_login, user_pass, user_firstname, user_lastname,
							user_nickname, user_email, user_url, user_level, user_locale, user_idmode, user_notify, user_grp_ID )
						SELECT id, email, '".md5($random_password)."', '', '', name, email, homePage, 10, '$default_locale', 'nickname', 1, $Group_Admins->ID
						FROM user
						WHERE role = 'admin'";
	$DB->query( $query );
	// Bloggers:
	$query = "INSERT INTO T_users( ID, user_login, user_pass, user_firstname, user_lastname,
							user_nickname, user_email, user_url, user_level, user_locale, user_idmode, user_notify, user_grp_ID )
						SELECT id, email, '".md5($random_password)."', '', '', name, email, homePage, 1, '$default_locale', 'nickname', 1, $Group_Bloggers->ID
						FROM user
						WHERE role = 'user'";
	$DB->query( $query );
	// Regular users:
	$query = "INSERT INTO T_users( ID, user_login, user_pass, user_firstname, user_lastname,
							user_nickname, user_email, user_url, user_level, user_locale, user_idmode, user_notify, user_grp_ID )
						SELECT id, email, '".md5($random_password)."', '', '', name, email, homePage, 1, '$default_locale', 'nickname', 1, $Group_Users->ID
						FROM user
						WHERE role <> 'admin' and role <> 'user'";
	$DB->query( $query );
	echo "OK.<br />\n";

	echo 'Make sure user #1 is at level 10... ';
	$query = 'UPDATE T_users
						SET user_level = 10
						WHERE ID = 1';
	$DB->query( $query );
	echo "OK.<br />\n";

	echo 'Creating user blog permissions... ';
	// Admin for all blogs:
	$query = "INSERT INTO T_blogusers( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember,
							bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments,
							bloguser_perm_cats, bloguser_perm_properties)
						SELECT blog_ID, ID, 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1
						FROM T_users, T_blogs
						WHERE user_level = 10";
	$DB->query( $query );

	// Normal users for upgraded blog:
	$query = "INSERT INTO T_blogusers( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember,
							bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments,
							bloguser_perm_cats, bloguser_perm_properties)
						SELECT $blog_a_ID, ID, 1, 'published,protected,private,draft', 0, 1, 0, 0
						FROM T_users
						WHERE user_level > 0 AND user_level < 10";
	$DB->query( $query );
	echo "OK.<br />\n";

	echo 'Creating a default category... ';
	$cat_imported = cat_create( 'Imported', 'NULL', $blog_a_ID );
	echo "OK.<br />\n";

	echo 'Copying Miniblog posts... ';
	$query = "INSERT INTO T_posts( ID, post_creator_user_ID, post_datestart, post_datemodified, post_status, post_locale, post_content, post_title, post_main_cat_ID, post_flags, post_comments )
						SELECT id, authorId, created, modified, 'published', '$default_locale', content, title, $cat_imported, 'pingsdone,imported', 'open'
						FROM miniblog
						WHERE parentId = 0";
	$DB->query( $query );
	echo "OK.<br />\n";

	echo 'Generating wordcounts... ';
	$query = 'SELECT ID, post_content FROM T_posts';
	$q = $DB->get_results( $query, ARRAY_A );
	if( count( $q ) ) foreach( $q as $row )
	{
		$query_update_wordcount = "UPDATE T_posts
															SET post_wordcount = " . bpost_count_words($row['post_content']) . "
															WHERE ID = " . $row['ID'];
		$DB->query($query_update_wordcount);
	}
	echo "OK. (".count($q)." rows updated)<br />\n";

	echo 'Generating postcats... ';
	$query = "INSERT INTO T_postcats( postcat_post_ID, postcat_cat_ID )
						SELECT ID, post_main_cat_ID FROM T_posts";
	$DB->query( $query );
	echo "OK.<br />\n";

	echo 'Copying Miniblog comments... ';
	$query = "SELECT id, parentId, created, content
						FROM miniblog
						WHERE parentId > 1";
	$rows = $DB->get_results( $query );
	foreach( $rows as $row )
	{
		$matches = array();

		// Extract author's name:
		if( preg_match( '#<p class="posted">Posted by (<a .*?>)?(.*?)<#', $row->content, $matches ) )
			$author = $matches[2];
		else
			$author = '';

		// Extract author's email:
		if( preg_match( '#<p class="posted">Posted by <a href="mailto:(.*?)"#', $row->content, $matches ) )
			$author_email = $matches[1];
		else
			$author_email = '';

		// Extract author's url:
		if( preg_match( '# class="postedLink">(http://.*?)</a>#', $row->content, $matches ) )
			$author_url = $matches[1];
		else
			$author_url = '';

		// Trim the content:
		if( preg_match( '#^(.*?)<p class="posted">Posted by#s', $row->content, $matches ) )
			$content = $matches[1];
		else
			$content = $row->content;

		$query = "INSERT INTO T_comments( comment_ID, comment_post_ID, comment_type, comment_date, comment_content, comment_author, comment_author_email, comment_author_url )
							VALUES ( $row->id, $row->parentId, 'comment', ".$DB->quote($row->created).", ".$DB->quote($content).", ".$DB->quote($author).", ".$DB->quote($author_email).", ".$DB->quote($author_url)." )";
		$DB->query( $query );
	}
	echo "OK.<br />\n";


	create_default_categories( false /* not for A */ );

	// POPULATE THE LINKBLOG:
	populate_linkblog( $now, $cat_linkblog_b2evo, $cat_linkblog_contrib );

	// Create other default contents:
	create_default_contents( false /* not for A */ );

}

?>