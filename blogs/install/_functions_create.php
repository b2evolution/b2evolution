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
	global $stub_all, $stub_a, $stub_b, $stub_roll;
	global $timestamp, $admin_email;
	global $Group_Admins, $Group_Priviledged, $Group_Bloggers, $Group_Users;

	echo "Creating sample blogs... ";
	
	$blog_all_ID = blog_create( 'All Blogs', 'All', '', $stub_all.'.php', $stub_all.'.php', $stub_all.'.html', 'Tagline for All', 'All blogs on this system.', NULL, $default_language,  "This is the blogroll for the \'all blogs\' blog aggregation.", 'all blogs keywords', '' ) or mysql_oops( $query );

	$blog_a_ID =	blog_create( 'Demo Blog A', 'Blog A', '', $stub_a.'.php', $stub_a.'.php', $stub_a.'.html', 'Tagline for A', 'This is demo blog A', 'This is description for demo blog A. It has index #2 in the database.', $default_language, 'This is the blogroll for Blog A...', 'blog A keywords', '' ) or mysql_oops( $query );
	
	$blog_b_ID = blog_create( 'Demo Blog B', 'Blog B', '', $stub_b.'.php', $stub_b.'.php', $stub_b.'.html', 'Tagline for B', 'This is demo blog B', 'This is description for demo blog B. It has index #3 in the database.', $default_language, 'This is the blogroll for Blog B...', 'blog B keywords', '') or mysql_oops( $query );

	$blog_roll_ID = blog_create( 'Demo Blogroll', 'Blogroll', '', $stub_roll.'.php', $stub_roll.'.php', $stub_roll.'.html', 'Tagline for Blogroll', 'This is the demo blogroll', 'This is description for blogroll. It has index #4 in the database.', $default_language, 'This is the blogroll for the blogroll... pretty funky huh? :))', 'blogroll keywords', '') or mysql_oops( $query );

	echo "OK.<br />\n";
	
	echo 'Creating sample categories...';
	
	// Create categories for blog A
	$cat_ann_a = cat_create( "Announcements [A]", 'NULL', 2 )  or mysql_oops( $query );
	$cat_news = cat_create( "News", 'NULL', 2 )  or mysql_oops( $query );
	$cat_bg = cat_create( "Background", 'NULL', 2 )  or mysql_oops( $query );
	
	// Create categories for blog B
	$cat_ann_b = cat_create( "Announcements [B]", 'NULL', 3 )  or mysql_oops( $query );
	$cat_fun = cat_create( "Fun", 'NULL', 3 )  or mysql_oops( $query );
	$cat_life = cat_create( "In real life", $cat_fun, 3 )  or mysql_oops( $query );
	$cat_web = cat_create( "On the web", $cat_fun, 3 )  or mysql_oops( $query );
	$cat_sports = cat_create( "Sports", $cat_life, 3 )  or mysql_oops( $query );
	$cat_movies = cat_create( "Movies", $cat_life, 3 )  or mysql_oops( $query );
	$cat_music = cat_create( "Music", $cat_life, 3 )  or mysql_oops( $query );
	$cat_b2evo = cat_create( "b2evolution", 'NULL', 3 )  or mysql_oops( $query );

	// Create categories for blogroll
	$cat_blogroll_b2evo = cat_create( "b2evolution", 'NULL', 4 )  or mysql_oops( $query );
	$cat_blogroll_contrib = cat_create( "contributors", 'NULL', 4 )  or mysql_oops( $query );
	
	echo "OK.<br />\n";

	echo 'Creating sample posts...';

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, 'First Post', '<p>This is the first post.</p>
	
	<p>It appears on both blog A and blog B.</p>', $now, $cat_ann_a, array( $cat_ann_b ) ) or mysql_oops( $query );
	
	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, 'Second post', '<p>This is the second post.</p>
	
	<p>It appears on blog A only but in multiple categories.</p>', $now, $cat_news, array( $cat_ann_a, $cat_bg ) ) or mysql_oops( $query );
	
	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, 'Third post', '<p>This is the third post.</p>
	
	<p>It appears on blog B only and in a single category.</p>', $now, $cat_fun ) or mysql_oops( $query );
	
	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, "Matrix Reloaded", "<p>Wait until the end of the super long end credits!</p>
	
	<p>If you're patient enough, you'll a get preview of the next episode...</p>
	
	<p>Though... it's just the same anyway! :>></p>", $now, $cat_movies ) or mysql_oops( $query );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, "Clean Permalinks! :idea:", "<p>b2evolution uses old-style permalinks and feedback links by default. This is to ensure maximum compatibility with various webserver configurations. Nethertheless, if you feel comfortable, you should try activating clean permalinks in the /conf/_advanced.php file...</p>", $now, $cat_b2evo ) or mysql_oops( $query );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, "Clean Skin! :idea:", "<p>By default, b2evolution blogs are displayed in the \'standard\' skin.</p>

<p>Readers can choose a new skin by using the skin switcher integrated in most skins.</p>		

<p>You can restrict available skins by deleting some of them from the /blogs/skins folder. You can also change the default skin or force a specific skin. <strong>Actually, you should change the default skin and delete the standard skin, as this one has navigation links at the top that are only good for the sake of the demo. These would be a nonsense on production servers!</strong> Read the manual on evoSkins!</p>", $now, $cat_b2evo ) or mysql_oops( $query );
	
	// POPULATE THE BLOGROLL:
	populate_blogroll( $now, $cat_blogroll_b2evo, $cat_blogroll_contrib );

	echo "OK.<br />\n";
	

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
	VALUES ( 1, 3, 'paged', 'monthly', '0', '1', $new_db_version, '2000-01-01 00:00:00', ".$Group_Users->get('ID')." )";
	$q = mysql_query($query) or mysql_oops( $query );

	echo "OK.<br />\n";

}

?>