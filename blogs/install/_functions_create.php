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

	echo "<p>Creating the necessary tables in the database...</p>";

	
	echo "Creating table for Groups...<br />\n";
	create_groups();

	echo "<p>Creating table for Settings...<br />\n";
	$query = "CREATE TABLE $tablesettings ( 
		ID tinyint DEFAULT 1 NOT NULL, 
		posts_per_page int unsigned DEFAULT 7 NOT NULL, 
		what_to_show varchar DEFAULT 'days' NOT NULL, 
		archive_mode varchar DEFAULT 'weekly' NOT NULL, 
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
	
	echo "Creating table for Users...<br />\n";
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
		user_level int(2) unsigned DEFAULT '0' NOT NULL, 
		user_aim varchar(50) NOT NULL, 
		user_msn varchar(100) NOT NULL, 
		user_yim varchar(50) NOT NULL, 
		user_idmode varchar(20) NOT NULL DEFAULT 'login', 
		user_notify tinyint(1) NOT NULL default 1,
		user_grp_ID int(4) NOT NULL default 1,
		PRIMARY KEY (ID), 
		UNIQUE (user_login),
	  KEY user_grp_ID (user_grp_ID)
	)";
	$q = mysql_query($query) or mysql_oops( $query );
	

	echo "Creating table for Blogs...<br />\n";
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


	echo "Creating table for Categories...<br />\n";
	$query="CREATE TABLE $tablecategories (
		cat_ID int(4) NOT NULL auto_increment,
		cat_parent_ID int(11) default NULL,
		cat_name tinytext NOT NULL,
		cat_blog_ID int(11) NOT NULL default '2',
		cat_description VARCHAR(250) NULL DEFAULT NULL,
		cat_longdesc TEXT NULL DEFAULT NULL,
		cat_icon VARCHAR(30) NULL DEFAULT NULL,
		PRIMARY KEY  (cat_ID),
		KEY cat_blog_ID (cat_blog_ID),
		KEY cat_parent_ID (cat_parent_ID)
	)";
	$q = mysql_query($query) or mysql_oops( $query );
		
		
	echo "Creating table for Posts...<br />\n";
	$query = "CREATE TABLE $tableposts (   
		ID int(10) unsigned NOT NULL auto_increment,
		post_author int(4) NOT NULL default '0',
		post_date datetime NOT NULL default '0000-00-00 00:00:00',
		post_status enum('published','deprecated','protected','private','draft') NOT NULL default 'published',
		post_lang VARCHAR(20) NOT NULL DEFAULT 'en_US',
		post_content text NOT NULL,
		post_title text NOT NULL,
		post_urltitle VARCHAR(50) NULL DEFAULT NULL,
		post_url VARCHAR(250) NULL DEFAULT NULL,
		post_category int(4) NOT NULL default '0',
		post_trackbacks TEXT NULL DEFAULT NULL,  
		post_autobr tinyint(4) NOT NULL default '1',
		post_flags SET('pingsdone','pbdone','tbdone','html','bbcode','gmcode','smartquotes','smileys','glossary','imported'),
		post_karma int(11) NOT NULL default '0',
		post_wordcount int(11) default NULL,
		post_comments ENUM('disabled', 'open', 'closed') NOT NULL DEFAULT 'open',
		PRIMARY KEY (ID),
		KEY post_date (post_date),
		KEY post_category (post_category),
		KEY post_author (post_author),
		KEY post_status (post_status)
	)";
	$q = mysql_query($query) or mysql_oops( $query );


	echo "Creating table for Categories-to-Posts relationships...<br />\n";
	$query = "CREATE TABLE $tablepostcats (
		postcat_post_ID int(11) NOT NULL default '0',
		postcat_cat_ID int(11) NOT NULL default '0',
		PRIMARY KEY  (postcat_post_ID,postcat_cat_ID)
	)";
	$q = mysql_query($query) or mysql_oops( $query );


	echo "Creating table for Comments...<br />\n";
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
		PRIMARY KEY  (comment_ID),
		KEY comment_post_ID (comment_post_ID),
		KEY comment_date (comment_date),
		KEY comment_type (comment_type)
	 )";
	$q = mysql_query($query) or mysql_oops( $query );
	
	
	echo "Creating table for Hit-Logs...<br />\n";
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
	 
	echo "Creating table for Anti-Spam Ban List...</p>\n";
	create_antispam();

	echo "<p>All tables created successfully.</p>\n";
}


/*
 * create_antispam(-)
 *
 * Used when creating full install and upgrading from earlier versions
 */
function create_antispam()
{
	global $tableantispam;
	
	$query = "CREATE TABLE $tableantispam (
		aspm_ID bigint(11) NOT NULL auto_increment,
		aspm_string varchar(80) NOT NULL,
		aspm_source enum( 'local','reported','central' ) NOT NULL default 'reported',
		PRIMARY KEY (aspm_ID),
		UNIQUE aspm_string (aspm_string)
	)";
	$q = mysql_query($query) or mysql_oops( $query );
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
	global $tablegroups, $tableblogusers, $Group_Admins, $Group_Bloggers, $Group_Users;
	
	$query = "CREATE TABLE $tablegroups (
		grp_ID int(11) NOT NULL auto_increment,
  	grp_name varchar(50) NOT NULL default '',
  	grp_perm_blogs enum('user','viewall','editall') NOT NULL default 'user',
  	grp_perm_stats enum('none','view','edit') NOT NULL default 'none',
  	grp_perm_spamblacklist enum('none','view','edit') NOT NULL default 'none',
  	grp_perm_options enum('none','view','edit') NOT NULL default 'none',
  	grp_perm_users enum('none','view','edit') NOT NULL default 'none',
		grp_perm_templates TINYINT NOT NULL DEFAULT 0,
	  PRIMARY KEY (grp_ID)
	)";
	$q = mysql_query($query) or mysql_oops( $query );
	
	echo 'Populating...';

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


	$query = "CREATE TABLE $tableblogusers (
		bloguser_blog_ID int NOT NULL default 0,
		bloguser_user_ID int NOT NULL default 0,
		bloguser_perm_poststatuses set('published','deprecated','protected','private','draft') NOT NULL default '',
		bloguser_perm_delpost tinyint NOT NULL default 0,
		bloguser_perm_comments tinyint NOT NULL default 0,
		bloguser_perm_cats tinyint NOT NULL default 0,
		bloguser_perm_properties tinyint NOT NULL default 0,
		PRIMARY KEY (bloguser_blog_ID,bloguser_user_ID)
	)";
	$q = mysql_query($query) or mysql_oops( $query );
	
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
	bpost_create( 1, 'Swirlee', 'Development', $now, $cat_blogroll_contrib, array(), 'published',  'en', '', 0, true, 'http://swirlee.org/' ) or mysql_oops( $query );

	// Insert a post into blogroll:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, 'Jason', 'Hosting', $now, $cat_blogroll_contrib, array(), 'published',  'en', '', 0, true, 'http://www.thejasonmurphyshow.com/' ) or mysql_oops( $query );

	// Insert a post into blogroll:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, 'Travis', 'Hosting, Development', $now, $cat_blogroll_contrib, array(), 'published',  'en', '', 0, true, 'http://www.fromthecrossroads.ws/' ) or mysql_oops( $query );

	// Insert a post into blogroll:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, 'Sakichan', 'Development, Testing', $now, $cat_blogroll_contrib, array(), 'published',  'en', '', 0, true, 'http://blog.sakichan.org/ja/' ) or mysql_oops( $query );

	// Insert a post into blogroll:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, 'François', 'Main dev', $now, $cat_blogroll_contrib, array(), 'published',  'en', '', 0, true, 'http://fplanque.net/Blog/' ) or mysql_oops( $query );

	// Insert a post into blogroll:
	$now = date('Y-m-d H:i:s',$timestamp++);
	bpost_create( 1, 'b2evolution', 'Project home', $now, $cat_blogroll_b2evo, array(), 'published',  'en', '', 0, true, 'http://b2evolution.net/' ) or mysql_oops( $query );

}

/*
 * populate_antispam(-)
 */
function populate_antispam()
{
	global $tableantispam;
	
	$query = "INSERT INTO $tableantispam(aspm_string) VALUES ".
	"('penis-enlargement'), ('online-casino'), ".
	"('order-viagra'), ('order-phentermine'), ('order-xenical'), ".
	"('order-prophecia'), ('sexy-lingerie'), ('-porn-'), ".
	"('-adult-'), ('-tits-'), ('buy-phentermine'), ".
	"('order-cheap-pills'), ('buy-xenadrine'),  ('xxx'), ".
	"('paris-hilton'), ('parishilton'), ('camgirls'), ('adult-models')";

	mysql_query($query) or mysql_oops( $query );
	
}


?>