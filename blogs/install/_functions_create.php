<?php
/**
 * This file implements creation of DB tables
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
 * create_b2evo_tables(-)
 *
 * Used for fresh install + upgrade from b2
 */
function create_b2evo_tables()
{
	global $tableposts, $tableusers, $tablesettings, $tablecategories, $tablecomments, $tableblogs,
        $tablepostcats, $tablehitlog, $tableantispam;
	global $baseurl, $new_db_version;

	create_groups();

	echo "Creating table for Settings... ";
	$query = "CREATE TABLE $tablesettings ( 
		ID tinyint DEFAULT 1 NOT NULL, 
		posts_per_page int unsigned DEFAULT 7 NOT NULL, 
		what_to_show varchar(10) DEFAULT 'days' NOT NULL, 
		archive_mode varchar(10) DEFAULT 'weekly' NOT NULL, 
		time_difference tinyint DEFAULT 0 NOT NULL, 
		AutoBR tinyint DEFAULT 1 NOT NULL, 
		db_version INT DEFAULT $new_db_version NOT NULL, 
  	last_antispam_update datetime NOT NULL default '2000-01-01 00:00:00',
		pref_newusers_grp_ID int unsigned DEFAULT 4 NOT NULL,
		pref_newusers_level tinyint unsigned DEFAULT 1 NOT NULL,
		pref_newusers_canregister tinyint unsigned DEFAULT 0 NOT NULL,
		PRIMARY KEY (ID)
	)";
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";
	
	echo "Creating table for Users...";
	$query = "CREATE TABLE $tableusers ( 
		ID int(10) unsigned NOT NULL auto_increment, 
		user_login varchar(20) NOT NULL, 
		user_pass CHAR(32) NOT NULL, 
		user_firstname varchar(50) NOT NULL, 
		user_lastname varchar(50) NOT NULL, 
		user_nickname varchar(50) NOT NULL, 
		user_icq int(10) unsigned DEFAULT '0' NOT NULL, 
		user_email varchar(100) NOT NULL, 
		user_url varchar(100) NOT NULL, 
		user_ip varchar(15) NOT NULL, 
		user_domain varchar(200) NOT NULL, 
		user_browser varchar(200) NOT NULL, 
		dateYMDhour datetime DEFAULT '0000-00-00 00:00:00' NOT NULL, 
		user_level int unsigned DEFAULT 0 NOT NULL, 
		user_aim varchar(50) NOT NULL, 
		user_msn varchar(100) NOT NULL, 
		user_yim varchar(50) NOT NULL, 
		user_idmode varchar(20) NOT NULL DEFAULT 'login', 
		user_notify tinyint(1) NOT NULL default 1,
		user_grp_ID int(4) NOT NULL default 1,
		PRIMARY KEY user_ID (ID), 
		UNIQUE user_login (user_login),
	  KEY user_grp_ID (user_grp_ID)
	)";
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";
	
	echo "Creating table for Blogs...";
	$query = "CREATE TABLE $tableblogs (
		blog_ID int(4) NOT NULL auto_increment,
		blog_shortname varchar(12) NULL default '',
		blog_name varchar(50) NOT NULL default '',
		blog_tagline varchar(250) NULL default '',
		blog_description varchar(250) NULL default '',
		blog_longdesc TEXT NULL DEFAULT NULL, 
		blog_lang VARCHAR(20) NOT NULL DEFAULT 'en_US',
		blog_siteurl varchar(120) NOT NULL default '$baseurl',
		blog_filename varchar(30) NULL default 'blog.php',
		blog_staticfilename varchar(30) NULL default NULL,
		blog_stub VARCHAR(30) NULL DEFAULT 'blog.php',
		blog_roll text,
		blog_keywords tinytext,
		blog_allowtrackbacks tinyint(1) NOT NULL default 1,
		blog_allowpingbacks tinyint(1) NOT NULL default 1,
		blog_pingb2evonet tinyint(1) NOT NULL default 0,
		blog_pingtechnorati tinyint(1) NOT NULL default 0,
		blog_pingweblogs tinyint(1) NOT NULL default 0,
		blog_pingblodotgs tinyint(1) NOT NULL default 0,
		blog_default_skin VARCHAR(30) NOT NULL DEFAULT 'standard',
		blog_disp_bloglist tinyint NOT NULL DEFAULT 1,
		blog_UID VARCHAR(20),
		PRIMARY KEY  (blog_ID) 
	)";
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";

	echo "Creating table for Categories...";
	$query="CREATE TABLE $tablecategories (
		cat_ID int(4) NOT NULL auto_increment,
		cat_parent_ID int(11) default NULL,
		cat_name tinytext NOT NULL,
		cat_blog_ID int(11) NOT NULL default '2',
		cat_description VARCHAR(250) NULL DEFAULT NULL,
		cat_longdesc TEXT NULL DEFAULT NULL,
		cat_icon VARCHAR(30) NULL DEFAULT NULL,
		PRIMARY KEY cat_ID (cat_ID),
		KEY cat_blog_ID (cat_blog_ID),
		KEY cat_parent_ID (cat_parent_ID)
	)";
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";
		
	echo "Creating table for Posts...";
	$query = "CREATE TABLE $tableposts (   
		ID int(10) unsigned NOT NULL auto_increment,
		post_author int(4) NOT NULL default '0',
		post_date datetime NOT NULL default '0000-00-00 00:00:00',
		post_status enum('published','deprecated','protected','private','draft') 
									NOT NULL default 'published',
		post_lang VARCHAR(20) NOT NULL DEFAULT 'en_US',
		post_content text NOT NULL,
		post_title text NOT NULL,
		post_urltitle VARCHAR(50) NULL DEFAULT NULL,
		post_url VARCHAR(250) NULL DEFAULT NULL,
		post_category int(4) NOT NULL default '0',
		post_trackbacks TEXT NULL DEFAULT NULL,  
		post_autobr tinyint(4) NOT NULL default '1',
		post_flags SET( 'pingsdone', 'pbdone', 'tbdone', 'html', 'bbcode', 'gmcode', 
									'smartquotes', 'smileys','glossary','imported'),
		post_karma int(11) NOT NULL default '0',
		post_wordcount int(11) default NULL,
		post_comments ENUM('disabled', 'open', 'closed') NOT NULL DEFAULT 'open',
		PRIMARY KEY post_ID (ID),
		KEY post_date (post_date),
		KEY post_category (post_category),
		KEY post_author (post_author),
		KEY post_status (post_status)
	)";
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";

	echo "Creating table for Categories-to-Posts relationships...";
	$query = "CREATE TABLE $tablepostcats (
		postcat_post_ID int(11) NOT NULL default '0',
		postcat_cat_ID int(11) NOT NULL default '0',
		PRIMARY KEY postcat_pk (postcat_post_ID,postcat_cat_ID)
	)";	// We might want to add an index on cat_ID here...
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";

	echo "Creating table for Comments...";
	$query = "CREATE TABLE $tablecomments ( 
		comment_ID int(11) unsigned NOT NULL auto_increment,
		comment_post_ID int(11) NOT NULL default '0',
		comment_type enum('comment','linkback','trackback','pingback') NOT NULL default 'comment',
		comment_status ENUM('published', 'deprecated', 'protected', 'private', 'draft') DEFAULT 'published' NOT NULL,
		comment_author tinytext NOT NULL,
		comment_author_email varchar(100) NOT NULL default '',
		comment_author_url varchar(100) NOT NULL default '',
		comment_author_IP varchar(100) NOT NULL default '',
		comment_date datetime NOT NULL default '0000-00-00 00:00:00',
		comment_content text NOT NULL,
		comment_karma int(11) NOT NULL default '0',
		PRIMARY KEY comment_ID (comment_ID),
		KEY comment_post_ID (comment_post_ID),
		KEY comment_date (comment_date),
		KEY comment_type (comment_type)
	)";
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";
	
	echo "Creating table for Hit-Logs...";
	$query = "CREATE TABLE $tablehitlog (
		visitID bigint(11) NOT NULL auto_increment,
		visitTime timestamp(14) NOT NULL,
		visitURL varchar(250) default NULL,
		hit_ignore enum('no','invalid','badchar','blacklist','rss','robot','search') NOT NULL default 'no',
		referingURL varchar(250) default NULL,
		baseDomain varchar(250) default NULL,
		hit_blog_ID int(11) NOT NULL default '0',
		hit_remote_addr varchar(40) default NULL,
		hit_user_agent varchar(250) default NULL,
		PRIMARY KEY (visitID),
		KEY hit_ignore (hit_ignore),
		KEY baseDomain (baseDomain),
		KEY hit_blog_ID (hit_blog_ID),
		KEY hit_user_agent (hit_user_agent)
	)";
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";

	create_antispam();
}


/*
 * create_antispam(-)
 *
 * Used when creating full install and upgrading from earlier versions
 */
function create_antispam()
{
	global $tableantispam;
	
	echo "Creating table for Antispam Blackist...";
	$query = "CREATE TABLE $tableantispam (
		aspm_ID bigint(11) NOT NULL auto_increment,
		aspm_string varchar(80) NOT NULL,
		aspm_source enum( 'local','reported','central' ) NOT NULL default 'reported',
		PRIMARY KEY aspm_ID (aspm_ID),
		UNIQUE aspm_string (aspm_string)
	)";
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";
	
	echo 'Creating default blacklist entries... ';
	$query = "INSERT INTO $tableantispam(aspm_string) VALUES ".
	"('penis-enlargement'), ('online-casino'), ".
	"('order-viagra'), ('order-phentermine'), ('order-xenical'), ".
	"('order-prophecia'), ('sexy-lingerie'), ('-porn-'), ".
	"('-adult-'), ('-tits-'), ('buy-phentermine'), ".
	"('order-cheap-pills'), ('buy-xenadrine'),  ('xxx'), ".
	"('paris-hilton'), ('parishilton'), ('camgirls'), ('adult-models')";
	mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";
}


/*
 * create_groups(-)
 *
 * Create user permissions
 *
 * Used when creating full install and upgrading from earlier versions
 */
function create_groups()
{
	global $tablegroups, $tableblogusers, $Group_Admins, $Group_Priviledged, $Group_Bloggers, $Group_Users;
	
	echo 'Creating table for Groups... ';
	$query = "CREATE TABLE $tablegroups (
		grp_ID int(11) NOT NULL auto_increment,
  	grp_name varchar(50) NOT NULL default '',
  	grp_perm_blogs enum('user','viewall','editall') NOT NULL default 'user',
  	grp_perm_stats enum('none','view','edit') NOT NULL default 'none',
  	grp_perm_spamblacklist enum('none','view','edit') NOT NULL default 'none',
  	grp_perm_options enum('none','view','edit') NOT NULL default 'none',
  	grp_perm_users enum('none','view','edit') NOT NULL default 'none',
		grp_perm_templates TINYINT NOT NULL DEFAULT 0,
	  PRIMARY KEY grp_ID (grp_ID)
	)";
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";
	
	echo 'Creating default groups... ';
	$Group_Admins = new Group();
	$Group_Admins->set( 'name', 'Administrators' );
	$Group_Admins->set( 'perm_blogs', 'editall' );
	$Group_Admins->set( 'perm_stats', 'edit' );
	$Group_Admins->set( 'perm_spamblacklist', 'edit' );
	$Group_Admins->set( 'perm_options', 'edit' );
	$Group_Admins->set( 'perm_templates', 1 );
	$Group_Admins->set( 'perm_users', 'edit' );
	$Group_Admins->dbinsert();

	$Group_Priviledged = new Group();
	$Group_Priviledged->set( 'name', 'Priviledged Bloggers' );
	$Group_Priviledged->set( 'perm_blogs', 'viewall' );
	$Group_Priviledged->set( 'perm_stats', 'view' );
	$Group_Priviledged->set( 'perm_spamblacklist', 'edit' );
	$Group_Priviledged->set( 'perm_options', 'view' );
	$Group_Priviledged->set( 'perm_templates', 0 );
	$Group_Priviledged->set( 'perm_users', 'view' );
	$Group_Priviledged->dbinsert();

	$Group_Bloggers = new Group();
	$Group_Bloggers->set( 'name', 'Bloggers' );
	$Group_Bloggers->set( 'perm_blogs', 'user' );
	$Group_Bloggers->set( 'perm_stats', 'none' );
	$Group_Bloggers->set( 'perm_spamblacklist', 'view' );
	$Group_Bloggers->set( 'perm_options', 'none' );
	$Group_Bloggers->set( 'perm_templates', 0 );
	$Group_Bloggers->set( 'perm_users', 'none' );
	$Group_Bloggers->dbinsert();

	$Group_Users = new Group();
	$Group_Users->set( 'perm_blogs', 'user' );
	$Group_Users->set( 'name', 'Basic Users' );
	$Group_Users->set( 'perm_stats', 'none' );
	$Group_Users->set( 'perm_spamblacklist', 'none' );
	$Group_Users->set( 'perm_options', 'none' );
	$Group_Users->set( 'perm_templates', 0 );
	$Group_Users->set( 'perm_users', 'none' );
	$Group_Users->dbinsert();
	echo "OK.<br />\n";

	echo 'Creating table for Blog-User permissions... ';
	$query = "CREATE TABLE $tableblogusers (
		bloguser_blog_ID int NOT NULL default 0,
		bloguser_user_ID int NOT NULL default 0,
		bloguser_perm_poststatuses set('published','deprecated','protected','private','draft') NOT NULL default '',
		bloguser_perm_delpost tinyint NOT NULL default 0,
		bloguser_perm_comments tinyint NOT NULL default 0,
		bloguser_perm_cats tinyint NOT NULL default 0,
		bloguser_perm_properties tinyint NOT NULL default 0,
		PRIMARY KEY bloguser_pk (bloguser_blog_ID,bloguser_user_ID)
	)";
	$q = mysql_query($query) or mysql_oops( $query );
	echo "OK.<br />\n";
	
}

/*
 * populate_blogroll(-)
 */
function populate_blogroll( & $now, $cat_blogroll_b2evo, $cat_blogroll_contrib)
{
	global $timestamp;

	echo "Creating default blogroll entries... ";
	
	// Insert a post into blogroll:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, 'Graham', 'Testing', $now, $cat_blogroll_contrib, array(), 'published',  'en', '', 0, true, 'http://tin-men.net/' ) or mysql_oops( $query );

	// Insert a post into blogroll:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, 'Ron', 'Hacks, Testing', $now, $cat_blogroll_contrib, array(), 'published',  'en', '', 0, true, 'http://www.rononline.nl/' ) or mysql_oops( $query );

	// Insert a post into blogroll:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, 'Topanga', 'Testing', $now, $cat_blogroll_contrib, array(), 'published',  'en', '', 0, true, 'http://www.tenderfeelings.be/' ) or mysql_oops( $query );

	// Insert a post into blogroll:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, 'Travis', 'Hosting, Development', $now, $cat_blogroll_contrib, array(), 'published',  'en', '', 0, true, 'http://www.fromthecrossroads.ws/' ) or mysql_oops( $query );

	// Insert a post into blogroll:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, 'François', 'Main dev', $now, $cat_blogroll_contrib, array(), 'published',  'en', '', 0, true, 'http://fplanque.net/Blog/' ) or mysql_oops( $query );

	// Insert a post into blogroll:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, 'b2evolution', 'Project home', $now, $cat_blogroll_b2evo, array(), 'published',  'en', '', 0, true, 'http://b2evolution.net/' ) or mysql_oops( $query );

	// Insert a post into blogroll:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, T_('This is a sample blogroll entry'), T_("This is sample text describing the blogroll entry. In most cases however, you'll want to leave this blank, providing just a Title and an Url for your blogroll entries (favorite/related sites)."), $now, $cat_blogroll_b2evo, array(), 'published',  'en', '', 0, true, 'http://b2evolution.net/' ) or mysql_oops( $query );

	echo "OK.<br />\n";

}


/** 
 * Create default blogs
 *
 * This is called for fresh installs and cafelog upgrade
 *
 * {@internal create_default_blogs(-) }}
 *
 */
function create_default_blogs( $blog_a_short = 'Blog A', $blog_a_long = 'Demo Blog A', $blog_a_longdesc = '#' )
{
	global $default_language, $query, $timestamp;
	global $stub_all, $stub_a, $stub_b, $stub_roll;
	global $blog_all_ID, $blog_a_ID, $blog_b_ID, $blog_roll_ID;

	$default_blog_longdesc = T_("This is a demo blog named '%s'. It has index #%d in the database. By default it is accessed through a stub file called '<code>%s</code>'. %s");

	$default_more_longdesc = T_("<br />
<br />
You can edit this file to change the default skin used for this blog. You can also rename this file to a better name; but make sure you update the new name in the blogs admin.<br />
<br />
If you don't want to use skins, use the provided '<code>%s</code>' file instead.");


	echo "Creating default blogs... ";
	
	$blog_shortname = 'Blog All';
	$blog_stub = $stub_all;
	$blog_more_longdesc = sprintf( $default_more_longdesc, 'noskin_all.php')."<br />
<br />
<strong>".T_("Note: Blog #1 is a very special blog! It automatically aggregates all posts from all other blogs. This allows you to easily track everything that is posted on this system. You can hide this blog from the public by clearing it's 'Stub Urlname' in the blogs admin.")."</strong>";
	$blog_ID = 1;
	$blog_all_ID =	blog_create( 'Demo '.$blog_shortname, $blog_shortname, '', $blog_stub.'.php', $blog_stub.'.php', $blog_stub.'.html', 'Tagline for Demo '.$blog_shortname, 'This is Demo '.$blog_shortname, sprintf( $default_blog_longdesc, $blog_shortname, $blog_ID, $blog_stub.'.php', $blog_more_longdesc ), $default_language, '', $blog_shortname.' keywords', '' ) or mysql_oops( $query );

	$blog_shortname = $blog_a_short;
	$blog_stub = $stub_a;
	$blog_more_longdesc = sprintf( $default_more_longdesc, 'noskin_a.php');
	$blog_ID = 2;
	$blog_a_ID =	blog_create( $blog_a_long, $blog_shortname, '', $blog_stub.'.php', $blog_stub.'.php', $blog_stub.'.html', 'Tagline for Demo '.$blog_shortname, 'This is Demo '.$blog_shortname, sprintf( 
(($blog_a_longdesc == '#') ? $default_blog_longdesc : $blog_a_longdesc), $blog_shortname, $blog_ID, $blog_stub.'.php', $blog_more_longdesc ), $default_language, '', $blog_shortname.' keywords', '' ) or mysql_oops( $query );

	$blog_shortname = 'Blog B';
	$blog_stub = $stub_b;
	$blog_more_longdesc = sprintf( $default_more_longdesc, 'noskin_b.php');
	$blog_ID = 3;
	$blog_b_ID =	blog_create( 'Demo '.$blog_shortname, $blog_shortname, '', $blog_stub.'.php', $blog_stub.'.php', $blog_stub.'.html', 'Tagline for Demo '.$blog_shortname, 'This is Demo '.$blog_shortname, sprintf( $default_blog_longdesc, $blog_shortname, $blog_ID, $blog_stub.'.php', $blog_more_longdesc ), $default_language, '', $blog_shortname.' keywords', '' ) or mysql_oops( $query );
	
	$blog_shortname = 'Blogroll';
	$blog_stub = $stub_roll;
	$blog_more_longdesc = '<br />
<br />
<strong>'.T_("However, the main purpose for this blog is to be included as a side item to other blogs where it will display your favorite/related links. This is commonly referred to as a 'Blogroll'.").'</strong>';
	$blog_ID = 4;
	$blog_roll_ID =	blog_create( 'Demo '.$blog_shortname, $blog_shortname, '', $blog_stub.'.php', $blog_stub.'.php', $blog_stub.'.html', 'Tagline for Demo '.$blog_shortname, 'This is Demo '.$blog_shortname, sprintf( $default_blog_longdesc, $blog_shortname, $blog_ID, $blog_stub.'.php', $blog_more_longdesc ), $default_language, '', $blog_shortname.' keywords', '' ) or mysql_oops( $query );

	echo "OK.<br />\n";

}

/** 
 * Create default categories
 *
 * This is called for fresh installs and cafelog upgrade
 *
 * {@internal create_default_categories(-) }}
 *
 */
function create_default_categories( $populate_blog_a = true )
{
	global $default_language, $query, $timestamp;
	global $cat_ann_a, $cat_news, $cat_bg, $cat_ann_b, $cat_fun, $cat_life, $cat_web, $cat_sports, $cat_movies, $cat_music, $cat_b2evo, $cat_blogroll_b2evo, $cat_blogroll_contrib;

	echo 'Creating sample categories...';
	
	if( $populate_blog_a )
	{
		// Create categories for blog A
		$cat_ann_a = cat_create( "Announcements [A]", 'NULL', 2 )  or mysql_oops( $query );
		$cat_news = cat_create( "News", 'NULL', 2 )  or mysql_oops( $query );
		$cat_bg = cat_create( "Background", 'NULL', 2 )  or mysql_oops( $query );
	}
		
	// Create categories for blog B
	$cat_ann_b = cat_create( "Announcements [B]", 'NULL', 3 )  or mysql_oops( $query );
	$cat_fun = cat_create( "Fun", 'NULL', 3 )  or mysql_oops( $query );
	$cat_life = cat_create( "In real life", $cat_fun, 3 )  or mysql_oops( $query );
	$cat_web = cat_create( "On the web", $cat_fun, 3 )  or mysql_oops( $query );
	$cat_sports = cat_create( "Sports", $cat_life, 3 )  or mysql_oops( $query );
	$cat_movies = cat_create( "Movies", $cat_life, 3 )  or mysql_oops( $query );
	$cat_music = cat_create( "Music", $cat_life, 3 )  or mysql_oops( $query );
	$cat_b2evo = cat_create( "b2evolution Tips", 'NULL', 3 )  or mysql_oops( $query );

	// Create categories for blogroll
	$cat_blogroll_b2evo = cat_create( "b2evolution", 'NULL', 4 )  or mysql_oops( $query );
	$cat_blogroll_contrib = cat_create( "contributors", 'NULL', 4 )  or mysql_oops( $query );
	
	echo "OK.<br />\n";

}


/** 
 * Create default contents
 *
 * This is called for fresh installs and cafelog upgrade
 *
 * {@internal create_default_contents(-) }}
 *
 */
function create_default_contents( $populate_blog_a = true )
{
	global $default_language, $query, $timestamp;
	global $cat_ann_a, $cat_news, $cat_bg, $cat_ann_b, $cat_fun, $cat_life, $cat_web, $cat_sports, $cat_movies, $cat_music, $cat_b2evo, $cat_blogroll_b2evo, $cat_blogroll_contrib;

	echo 'Creating sample posts... ';

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, T_("Matrix Reloaded"), T_("<p>[This is yet another sample post to demonstrate sub categories]</p>

<p>Wait until the end of the super long end credits!</p>

<p>If you're patient enough, you'll a get preview of the next episode...</p>

<p>Though... it's just the same anyway! :>></p>"), $now, $cat_movies ) or mysql_oops( $query );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, T_("Clean Permalinks! :idea:"), T_("<p>b2evolution uses old-style permalinks and feedback links by default. This is to ensure maximum compatibility with various webserver configurations.</p>

<p>Nethertheless, once you feel comfortable with b2evolution, you should try activating clean permalinks in the /conf/_advanced.php file... (<code>\$use_extra_path_info = 1;</code>)</p>"), $now, $cat_b2evo ) or mysql_oops( $query );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, T_("Apache optimization... :idea:"), T_("<p>In the <code>/blogs</code> folder as well as in <code>/blogs/admin</code> there are two files called [<code>sample.htaccess</code>]. You should try renaming those to [<code>.htaccess</code>].</p>

<p>This will optimize the way b2evolution is handled by the webserver (if you are using Apache). These files are not active by default because a few hosts would display an error right away when you try to use them. If this happens to you when you rename the files, just remove them and you'll be fine.</p>"), $now, $cat_b2evo ) or mysql_oops( $query );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, T_("About evoSkins... :idea:"), T_("<p>By default, b2evolution blogs are displayed using a default skin.</p>

<p>Readers can choose a new skin by using the skin switcher integrated in most skins.</p>

<p>You can change the default skin used for any blog by editing the parameters you will find in the stub file, for example <code>blog_b.php</code>. Of course, that is unless you have switched to using templates (like <code>noskin_b.php</code>) instead of stub files (like <code>blog_b.php</code>) + skins.</p>

<p>You can restrict available skins by deleting some of them from the /blogs/skins folder. You can also create new skins by duplicating, renaming and customizing any existing skin folder. Read the manual on evoSkins!</p>"), $now, $cat_b2evo ) or mysql_oops( $query );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, T_("Skins *or* Templates ?"), T_("<p>By default, all pre-installed blogs use skins, not templates. (More on skins in another post.)</p>

<p>That means, blogs are accessed through 'stub' files called something like '<code>blog_b.php</code>'. These files set a few parameters such as the blog to display and the default skin to use, then they pass on the display job to a skin. You can edit the stub files to change the default skin used as well as other parameters. </p>

<p>Alternatively, if you wish to use plain templates instead of skins, use the provided '<code>noskin_*.php</code>' files instead of the stub files. For example, for Blog B, the template file would be '<code>noskin_b.php</code>'. Just replace '<code>blog_b.php</code>' with '<code>noskin_b.php</code>' in the blogs admin.</p>

<!--more-->

<p>Either way, you can rename the stub/template files (<code>blog_*.php</code> or <code>noskin_*.php</code>) to better names, including to '<code>index.php</code>' for your main blog; but once again, make sure you update the new names in the blogs admin.</p>

<p>Finally, if you don't use templates, you can delete all <code>noskin_*.php</code> files if you want. Also, if you don't use skins, you can delete all <code>blog_*.php</code> files as well as the subfolders in the /skins folder. But, do not delete the whole skins folder, even templates need some files in there!</p>"), $now, $cat_b2evo ) or mysql_oops( $query );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, T_("Multiple Blogs, new blogs, old blogs..."), T_("<p>By default, b2evolution comes with 4 blogs, named 'Blog All', 'Blog A', 'Blog B' and 'Blogroll'.</p>

<p>Some of these blogs have a special role. Read about it on the corresponding page.</p>

<p>You can create additionnal blogs from the blogs admin.</p>

<!--more-->

<p>Some people have also asked for a feature to delete blogs. We're working on it, but we certainly do not recommend you go hacking your mySQL database by hand. It's the best way to leave a lot of junk in there. In the meantime, there are two things you can do with your old blogs:</p>

<ul>
  <li>Recycle them: delete the posts, change the name, also rename the access stub file if you wish. This is better with the database than deleting a blog and creating a new one.</li>
  <li>Hide them: go to the blogs admin and clear the 'Stub URLname'. Your blog won't appear publicly no more. You can also remove all user permissions on that blog to hide it from backoffice users (except the admin). Finally, you can delete the stub file for this blog to make it complete ;)</li>
</ul>"), $now, $cat_b2evo ) or mysql_oops( $query );

	
	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, T_("File permissions!"), T_("<p><strong>This is pretty important. Make sure you read this in order to have a fully functionning installation!</strong></p>

<ul>

  <li>In order to create new blogs from the admin interface or to generate static pages for your blogs, the <code>/blogs</code> folder needs to be writable by the PHP process on the server. You may need to do a chmod on this folder. If you don't know exactly try a chmod 777. You can do this either with an FTP program, a Unix shell or a web file manager like the one built into cPanel. If your server is running Windows you probably don't need to change anything.</li>

  <li>In order to use the template editor for the custom skin, your <code>/skins/custom</code> folder needs to be writable by the PHP process on the server. Same as above.</li>

  <li>This isn't necessary but ensures your security: do a chmod 644 on all files in the <code>/blogs/conf</code> directory, so no-one can overwrite them except you. If your server is running Windows, then set the file to 'read-only'.</li>

</ul>"), $now, $cat_b2evo ) or mysql_oops( $query );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, T_("Important information :idea:"), T_("<p>Blog B contains a few posts in the 'b2evolution Tips' category.</p>

<p>All these entries are designed to help you so, as EdB would say: \"read them all before you start hacking away!\" ;)</p>

<p>If you wish, you can delete these posts one by one after you have read them. You could also change their status to 'deprecated' in order to visually keep track of what you have already read.</p>"), $now, $cat_b2evo, ( $populate_blog_a ? array( $cat_ann_a , $cat_ann_b ) : array ( $cat_ann_b ) ) ) or mysql_oops( $query );

	echo "OK.<br />\n";

}


/*
 * populate_main_tables(-)
 *
 * This is called only for fresh installs
 */
function populate_main_tables()
{
	global $tableposts, $tableusers, $tablesettings, $tablecategories, $tablecomments, $tableblogs,
        $tablepostcats, $tablehitlog, $tableantispam, $tableblogusers;
	global $baseurl, $new_db_version;
	global $random_password, $default_language, $query;
	global $timestamp, $admin_email;
	global $Group_Admins, $Group_Priviledged, $Group_Bloggers, $Group_Users;
	global $blog_all_ID, $blog_a_ID, $blog_b_ID, $blog_roll_ID;
	global $cat_ann_a, $cat_news, $cat_bg, $cat_ann_b, $cat_fun, $cat_life, $cat_web, $cat_sports, $cat_movies, $cat_music, $cat_b2evo, $cat_blogroll_b2evo, $cat_blogroll_contrib;

	create_default_blogs();
	
	create_default_categories();
		
	echo 'Creating sample posts for blog A...';

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, T_('First Post'), T_('<p>This is the first post.</p>

<p>It appears on both blog A and blog B.</p>'), $now, $cat_ann_a, array( $cat_ann_b ) ) or mysql_oops( $query );
	
	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, T_('Second post'), T_('<p>This is the second post.</p>

<p>It appears on blog A only but in multiple categories.</p>'), $now, $cat_news, array( $cat_ann_a, $cat_bg ) ) or mysql_oops( $query );
	
	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, T_('Third post'), T_('<p>This is the third post.</p>

<p>It appears on blog B only and in a single category.</p>'), $now, $cat_fun ) or mysql_oops( $query );
	
	echo "OK.<br />\n";
	
	// POPULATE THE BLOGROLL:
	populate_blogroll( $now, $cat_blogroll_b2evo, $cat_blogroll_contrib );

	create_default_contents();


	echo 'Creating sample comments... ';

	$now = date('Y-m-d H:i:s');
	$query = "INSERT INTO $tablecomments (comment_ID, comment_post_ID, comment_type, comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_content, comment_karma)
	VALUES (1, 1, 'comment', 'miss b2', 'missb2@example.com', 'http://example.com', '127.0.0.1', '$now', 'Hi, this is a comment.<br />To delete a comment, just log in, and view the posts\' comments, there you will have the option to edit or delete them.', 0)";
	$q = mysql_query($query) or mysql_oops( $query );

	echo "OK.<br />\n";

	echo 'Creating default users... ';
	
	// USERS !
	$User_Admin = new User();
	$User_Admin->set( 'login', 'admin' );
	$random_password = substr(md5(uniqid(microtime())),0,6);
	$User_Admin->set( 'pass', md5($random_password) );	// random
	$User_Admin->set( 'nickname', 'admin' );
	$User_Admin->set( 'email', $admin_email );
	$User_Admin->set( 'ip', '127.0.0.1' );
	$User_Admin->set( 'domain', 'localhost' );
	$User_Admin->set( 'level', 10 );
	$User_Admin->setGroup( $Group_Admins );
	$User_Admin->dbinsert();

	$User_Demo = new User();
	$User_Demo->set( 'login', 'demouser' );
	$User_Demo->set( 'pass', md5($random_password) );	// random
	$User_Demo->set( 'nickname', 'Mr. Demo' );
	$User_Demo->set( 'email', $admin_email );
	$User_Demo->set( 'ip', '127.0.0.1' );
	$User_Demo->set( 'domain', 'localhost' );
	$User_Demo->set( 'level', 0 );
	$User_Demo->setGroup( $Group_Users );
	$User_Demo->dbinsert();

	echo "OK.<br />\n";

	echo "Creating user blog permissions... ";
	// Admin for blog A:
	$query = "INSERT INTO $tableblogusers( bloguser_blog_ID, bloguser_user_ID, 
							bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments, 
							bloguser_perm_cats, bloguser_perm_properties)
						VALUES 
							( $blog_all_ID, ".$User_Admin->get('ID').", 
							'published,deprecated,protected,private,draft', 1, 1, 1, 1 ),
							( $blog_a_ID, ".$User_Admin->get('ID').", 
							'published,deprecated,protected,private,draft', 1, 1, 1, 1 ),
							( $blog_b_ID, ".$User_Admin->get('ID').", 
							'published,deprecated,protected,private,draft', 1, 1, 1, 1 ),
							( $blog_roll_ID, ".$User_Admin->get('ID').", 
							'published,deprecated,protected,private,draft', 1, 1, 1, 1 ),
							( $blog_a_ID, ".$User_Demo->get('ID').", 
							'draft', 0, 0, 0, 0 )";
	$q = mysql_query($query) or mysql_oops( $query );

	echo "OK.<br />\n";
	
	
	echo 'Creating default settings... ';

	// SETTINGS!
	$query = "INSERT INTO $tablesettings ( ID, posts_per_page, what_to_show, archive_mode, time_difference, AutoBR, db_version, last_antispam_update, pref_newusers_grp_ID ) 
	VALUES ( 1, 5, 'paged', 'monthly', '0', '1', $new_db_version, '2000-01-01 00:00:00', ".$Group_Users->get('ID')." )";
	$q = mysql_query($query) or mysql_oops( $query );

	echo "OK.<br />\n";

}

?>