-- phpMyAdmin SQL Dump
-- version 2.6.4-pl1-Debian-1ubuntu1.1
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Dec 16, 2005 at 12:53 AM
-- Server version: 5.0.16
-- PHP Version: 5.0.5
-- 
-- Database: `b2evolution_v-1-6`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `evo_antispam`
-- 

CREATE TABLE `evo_antispam` (
  `aspm_ID` bigint(11) NOT NULL auto_increment,
  `aspm_string` varchar(80) NOT NULL,
  `aspm_source` enum('local','reported','central') NOT NULL default 'reported',
  PRIMARY KEY  (`aspm_ID`),
  UNIQUE KEY `aspm_string` (`aspm_string`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=19 ;

-- 
-- Dumping data for table `evo_antispam`
-- 

INSERT INTO `evo_antispam` VALUES (1, 'penis-enlargement', 'reported');
INSERT INTO `evo_antispam` VALUES (2, 'online-casino', 'reported');
INSERT INTO `evo_antispam` VALUES (3, 'order-viagra', 'reported');
INSERT INTO `evo_antispam` VALUES (4, 'order-phentermine', 'reported');
INSERT INTO `evo_antispam` VALUES (5, 'order-xenical', 'reported');
INSERT INTO `evo_antispam` VALUES (6, 'order-prophecia', 'reported');
INSERT INTO `evo_antispam` VALUES (7, 'sexy-lingerie', 'reported');
INSERT INTO `evo_antispam` VALUES (8, '-porn-', 'reported');
INSERT INTO `evo_antispam` VALUES (9, '-adult-', 'reported');
INSERT INTO `evo_antispam` VALUES (10, '-tits-', 'reported');
INSERT INTO `evo_antispam` VALUES (11, 'buy-phentermine', 'reported');
INSERT INTO `evo_antispam` VALUES (12, 'order-cheap-pills', 'reported');
INSERT INTO `evo_antispam` VALUES (13, 'buy-xenadrine', 'reported');
INSERT INTO `evo_antispam` VALUES (14, 'xxx', 'reported');
INSERT INTO `evo_antispam` VALUES (15, 'paris-hilton', 'reported');
INSERT INTO `evo_antispam` VALUES (16, 'parishilton', 'reported');
INSERT INTO `evo_antispam` VALUES (17, 'camgirls', 'reported');
INSERT INTO `evo_antispam` VALUES (18, 'adult-models', 'reported');

-- --------------------------------------------------------

-- 
-- Table structure for table `evo_basedomains`
-- 

CREATE TABLE `evo_basedomains` (
  `dom_ID` int(11) unsigned NOT NULL auto_increment,
  `dom_name` varchar(250) NOT NULL default '',
  `dom_status` enum('unknown','whitelist','blacklist') NOT NULL default 'unknown',
  `dom_type` enum('unknown','normal','searcheng','aggregator') NOT NULL default 'unknown',
  PRIMARY KEY  (`dom_ID`),
  UNIQUE KEY `dom_name` (`dom_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `evo_basedomains`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `evo_bloggroups`
-- 

CREATE TABLE `evo_bloggroups` (
  `bloggroup_blog_ID` int(11) unsigned NOT NULL default '0',
  `bloggroup_group_ID` int(11) unsigned NOT NULL default '0',
  `bloggroup_ismember` tinyint(4) NOT NULL default '0',
  `bloggroup_perm_poststatuses` set('published','deprecated','protected','private','draft') NOT NULL default '',
  `bloggroup_perm_delpost` tinyint(4) NOT NULL default '0',
  `bloggroup_perm_comments` tinyint(4) NOT NULL default '0',
  `bloggroup_perm_cats` tinyint(4) NOT NULL default '0',
  `bloggroup_perm_properties` tinyint(4) NOT NULL default '0',
  `bloggroup_perm_media_upload` tinyint(4) NOT NULL default '0',
  `bloggroup_perm_media_browse` tinyint(4) NOT NULL default '0',
  `bloggroup_perm_media_change` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`bloggroup_blog_ID`,`bloggroup_group_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `evo_bloggroups`
-- 

INSERT INTO `evo_bloggroups` VALUES (2, 1, 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1, 1, 1, 1);
INSERT INTO `evo_bloggroups` VALUES (2, 2, 1, 'published,deprecated,protected,private,draft', 1, 1, 0, 0, 1, 1, 1);
INSERT INTO `evo_bloggroups` VALUES (2, 3, 1, 'published,deprecated,protected,private,draft', 0, 0, 0, 0, 1, 1, 0);
INSERT INTO `evo_bloggroups` VALUES (2, 4, 1, '', 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `evo_bloggroups` VALUES (3, 1, 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1, 1, 1, 1);
INSERT INTO `evo_bloggroups` VALUES (3, 2, 1, 'published,deprecated,protected,private,draft', 1, 1, 0, 0, 1, 1, 1);
INSERT INTO `evo_bloggroups` VALUES (3, 3, 1, 'published,deprecated,protected,private,draft', 0, 0, 0, 0, 1, 1, 0);
INSERT INTO `evo_bloggroups` VALUES (3, 4, 1, '', 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `evo_bloggroups` VALUES (4, 1, 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1, 1, 1, 1);
INSERT INTO `evo_bloggroups` VALUES (4, 2, 1, 'published,deprecated,protected,private,draft', 1, 1, 0, 0, 1, 1, 1);
INSERT INTO `evo_bloggroups` VALUES (4, 3, 1, 'published,deprecated,protected,private,draft', 0, 0, 0, 0, 1, 1, 0);
INSERT INTO `evo_bloggroups` VALUES (4, 4, 1, '', 0, 0, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

-- 
-- Table structure for table `evo_blogs`
-- 

CREATE TABLE `evo_blogs` (
  `blog_ID` int(11) unsigned NOT NULL auto_increment,
  `blog_shortname` varchar(12) default '',
  `blog_name` varchar(50) NOT NULL default '',
  `blog_tagline` varchar(250) default '',
  `blog_description` varchar(250) default '',
  `blog_longdesc` text,
  `blog_locale` varchar(20) NOT NULL default 'en-EU',
  `blog_access_type` varchar(10) NOT NULL default 'index.php',
  `blog_siteurl` varchar(120) NOT NULL default '',
  `blog_staticfilename` varchar(30) default NULL,
  `blog_stub` varchar(255) NOT NULL default 'stub',
  `blog_urlname` varchar(255) NOT NULL default 'urlname',
  `blog_notes` text,
  `blog_keywords` tinytext,
  `blog_allowcomments` varchar(20) NOT NULL default 'post_by_post',
  `blog_allowtrackbacks` tinyint(1) NOT NULL default '1',
  `blog_allowpingbacks` tinyint(1) NOT NULL default '0',
  `blog_allowblogcss` tinyint(1) NOT NULL default '1',
  `blog_allowusercss` tinyint(1) NOT NULL default '1',
  `blog_pingb2evonet` tinyint(1) NOT NULL default '0',
  `blog_pingtechnorati` tinyint(1) NOT NULL default '0',
  `blog_pingweblogs` tinyint(1) NOT NULL default '0',
  `blog_pingblodotgs` tinyint(1) NOT NULL default '0',
  `blog_default_skin` varchar(30) NOT NULL default 'custom',
  `blog_force_skin` tinyint(1) NOT NULL default '0',
  `blog_disp_bloglist` tinyint(1) NOT NULL default '1',
  `blog_in_bloglist` tinyint(1) NOT NULL default '1',
  `blog_links_blog_ID` int(11) default NULL,
  `blog_commentsexpire` int(4) NOT NULL default '0',
  `blog_media_location` enum('default','subdir','custom','none') NOT NULL default 'default',
  `blog_media_subdir` varchar(255) NOT NULL,
  `blog_media_fullpath` varchar(255) NOT NULL,
  `blog_media_url` varchar(255) NOT NULL,
  `blog_UID` varchar(20) default NULL,
  PRIMARY KEY  (`blog_ID`),
  UNIQUE KEY `blog_urlname` (`blog_urlname`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

-- 
-- Dumping data for table `evo_blogs`
-- 

INSERT INTO `evo_blogs` VALUES (1, 'Blog All', 'Blog All Title', 'Tagline for Blog All', 'Short description for Blog All', 'This is the long description for the blog named ''Blog All''. <br />\n<br />\n<strong>This blog (blog #1) is actually a very special blog! It automatically aggregates all posts from all other blogs. This allows you to easily track everything that is posted on this system. You can hide this blog from the public by unchecking ''Include in public blog list'' in the blogs admin.</strong>', 'en-EU', 'index.php', '', 'all.html', 'all', 'all', 'Notes for Blog All', 'Keywords for Blog All', 'post_by_post', 1, 0, 1, 1, 0, 0, 1, 0, 'custom', 0, 1, 1, 4, 0, 'default', '', '', '', '');
INSERT INTO `evo_blogs` VALUES (2, 'Blog A', 'Blog A Title', 'Tagline for Blog A', 'Short description for Blog A', 'This is the long description for the blog named ''Blog A''. ', 'en-EU', 'index.php', '', 'a.html', 'a', 'a', 'Notes for Blog A', 'Keywords for Blog A', 'post_by_post', 1, 0, 1, 1, 0, 0, 1, 0, 'custom', 0, 1, 1, 4, 0, 'default', '', '', '', '');
INSERT INTO `evo_blogs` VALUES (3, 'Blog B', 'Blog B Title', 'Tagline for Blog B', 'Short description for Blog B', 'This is the long description for the blog named ''Blog B''. ', 'en-EU', 'index.php', '', 'b.html', 'b', 'b', 'Notes for Blog B', 'Keywords for Blog B', 'post_by_post', 1, 0, 1, 1, 0, 0, 1, 0, 'custom', 0, 1, 1, 4, 0, 'default', '', '', '', '');
INSERT INTO `evo_blogs` VALUES (4, 'Linkblog', 'Linkblog Title', 'Tagline for Linkblog', 'Short description for Linkblog', 'This is the long description for the blog named ''Linkblog''. <br />\n<br />\n<strong>The main purpose for this blog is to be included as a side item to other blogs where it will display your favorite/related links.</strong>', 'en-EU', 'index.php', '', 'links.html', 'links', 'links', 'Notes for Linkblog', 'Keywords for Linkblog', 'post_by_post', 1, 0, 1, 1, 0, 0, 1, 0, 'custom', 0, 1, 1, 0, 0, 'default', '', '', '', '');

-- --------------------------------------------------------

-- 
-- Table structure for table `evo_blogusers`
-- 

CREATE TABLE `evo_blogusers` (
  `bloguser_blog_ID` int(11) unsigned NOT NULL default '0',
  `bloguser_user_ID` int(11) unsigned NOT NULL default '0',
  `bloguser_ismember` tinyint(4) NOT NULL default '0',
  `bloguser_perm_poststatuses` set('published','deprecated','protected','private','draft') NOT NULL default '',
  `bloguser_perm_delpost` tinyint(4) NOT NULL default '0',
  `bloguser_perm_comments` tinyint(4) NOT NULL default '0',
  `bloguser_perm_cats` tinyint(4) NOT NULL default '0',
  `bloguser_perm_properties` tinyint(4) NOT NULL default '0',
  `bloguser_perm_media_upload` tinyint(4) NOT NULL default '0',
  `bloguser_perm_media_browse` tinyint(4) NOT NULL default '0',
  `bloguser_perm_media_change` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`bloguser_blog_ID`,`bloguser_user_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `evo_blogusers`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `evo_categories`
-- 

CREATE TABLE `evo_categories` (
  `cat_ID` int(11) unsigned NOT NULL auto_increment,
  `cat_parent_ID` int(11) unsigned default NULL,
  `cat_name` tinytext NOT NULL,
  `cat_blog_ID` int(11) unsigned NOT NULL default '2',
  `cat_description` varchar(250) default NULL,
  `cat_longdesc` text,
  `cat_icon` varchar(30) default NULL,
  PRIMARY KEY  (`cat_ID`),
  KEY `cat_blog_ID` (`cat_blog_ID`),
  KEY `cat_parent_ID` (`cat_parent_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=14 ;

-- 
-- Dumping data for table `evo_categories`
-- 

INSERT INTO `evo_categories` VALUES (1, NULL, 'Announcements [A]', 2, NULL, NULL, NULL);
INSERT INTO `evo_categories` VALUES (2, NULL, 'News', 2, NULL, NULL, NULL);
INSERT INTO `evo_categories` VALUES (3, NULL, 'Background', 2, NULL, NULL, NULL);
INSERT INTO `evo_categories` VALUES (4, NULL, 'Announcements [B]', 3, NULL, NULL, NULL);
INSERT INTO `evo_categories` VALUES (5, NULL, 'Fun', 3, NULL, NULL, NULL);
INSERT INTO `evo_categories` VALUES (6, 5, 'In real life', 3, NULL, NULL, NULL);
INSERT INTO `evo_categories` VALUES (7, 5, 'On the web', 3, NULL, NULL, NULL);
INSERT INTO `evo_categories` VALUES (8, 6, 'Sports', 3, NULL, NULL, NULL);
INSERT INTO `evo_categories` VALUES (9, 6, 'Movies', 3, NULL, NULL, NULL);
INSERT INTO `evo_categories` VALUES (10, 6, 'Music', 3, NULL, NULL, NULL);
INSERT INTO `evo_categories` VALUES (11, NULL, 'b2evolution Tips', 3, NULL, NULL, NULL);
INSERT INTO `evo_categories` VALUES (12, NULL, 'b2evolution', 4, NULL, NULL, NULL);
INSERT INTO `evo_categories` VALUES (13, NULL, 'contributors', 4, NULL, NULL, NULL);

-- --------------------------------------------------------

-- 
-- Table structure for table `evo_comments`
-- 

CREATE TABLE `evo_comments` (
  `comment_ID` int(11) unsigned NOT NULL auto_increment,
  `comment_post_ID` int(11) unsigned NOT NULL default '0',
  `comment_type` enum('comment','linkback','trackback','pingback') NOT NULL default 'comment',
  `comment_status` enum('published','deprecated','protected','private','draft') NOT NULL default 'published',
  `comment_author_ID` int(10) unsigned default NULL,
  `comment_author` varchar(100) default NULL,
  `comment_author_email` varchar(100) default NULL,
  `comment_author_url` varchar(100) default NULL,
  `comment_author_IP` varchar(23) NOT NULL default '',
  `comment_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `comment_content` text NOT NULL,
  `comment_karma` int(11) NOT NULL default '0',
  PRIMARY KEY  (`comment_ID`),
  KEY `comment_post_ID` (`comment_post_ID`),
  KEY `comment_date` (`comment_date`),
  KEY `comment_type` (`comment_type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- 
-- Dumping data for table `evo_comments`
-- 

INSERT INTO `evo_comments` VALUES (1, 1, 'comment', 'published', NULL, 'miss b2', 'missb2@example.com', 'http://example.com', '127.0.0.1', '2005-12-16 00:32:39', 'Hi, this is a comment.<br />To delete a comment, just log in, and view the posts'' comments, there you will have the option to edit or delete them.', 0);

-- --------------------------------------------------------

-- 
-- Table structure for table `evo_files`
-- 

CREATE TABLE `evo_files` (
  `file_ID` int(11) unsigned NOT NULL auto_increment,
  `file_root_type` enum('absolute','user','group','collection') NOT NULL default 'absolute',
  `file_root_ID` int(11) unsigned NOT NULL default '0',
  `file_path` varchar(255) NOT NULL default '',
  `file_title` varchar(255) default NULL,
  `file_alt` varchar(255) default NULL,
  `file_desc` text,
  PRIMARY KEY  (`file_ID`),
  UNIQUE KEY `file` (`file_root_type`,`file_root_ID`,`file_path`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `evo_files`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `evo_groups`
-- 

CREATE TABLE `evo_groups` (
  `grp_ID` int(11) NOT NULL auto_increment,
  `grp_name` varchar(50) NOT NULL default '',
  `grp_perm_admin` enum('none','hidden','visible') NOT NULL default 'visible',
  `grp_perm_blogs` enum('user','viewall','editall') NOT NULL default 'user',
  `grp_perm_stats` enum('none','view','edit') NOT NULL default 'none',
  `grp_perm_spamblacklist` enum('none','view','edit') NOT NULL default 'none',
  `grp_perm_options` enum('none','view','edit') NOT NULL default 'none',
  `grp_perm_users` enum('none','view','edit') NOT NULL default 'none',
  `grp_perm_templates` tinyint(4) NOT NULL default '0',
  `grp_perm_files` enum('none','view','add','edit') NOT NULL default 'none',
  PRIMARY KEY  (`grp_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

-- 
-- Dumping data for table `evo_groups`
-- 

INSERT INTO `evo_groups` VALUES (1, 'Administrators', 'visible', 'editall', 'edit', 'edit', 'edit', 'edit', 1, 'edit');
INSERT INTO `evo_groups` VALUES (2, 'Privileged Bloggers', 'visible', 'viewall', 'view', 'edit', 'view', 'view', 0, 'add');
INSERT INTO `evo_groups` VALUES (3, 'Bloggers', 'visible', 'user', 'none', 'view', 'none', 'none', 0, 'view');
INSERT INTO `evo_groups` VALUES (4, 'Basic Users', 'none', 'user', 'none', 'none', 'none', 'none', 0, 'none');

-- --------------------------------------------------------

-- 
-- Table structure for table `evo_hitlog`
-- 

CREATE TABLE `evo_hitlog` (
  `hit_ID` int(11) NOT NULL auto_increment,
  `hit_sess_ID` int(10) unsigned default NULL,
  `hit_datetime` datetime NOT NULL,
  `hit_uri` varchar(250) default NULL,
  `hit_referer_type` enum('search','blacklist','referer','direct','spam') NOT NULL,
  `hit_referer` varchar(250) default NULL,
  `hit_referer_dom_ID` int(10) unsigned default NULL,
  `hit_blog_ID` int(11) unsigned default NULL,
  `hit_remote_addr` varchar(40) default NULL,
  PRIMARY KEY  (`hit_ID`),
  KEY `hit_datetime` (`hit_datetime`),
  KEY `hit_blog_ID` (`hit_blog_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `evo_hitlog`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `evo_links`
-- 

CREATE TABLE `evo_links` (
  `link_ID` int(11) unsigned NOT NULL auto_increment,
  `link_datecreated` datetime NOT NULL default '0000-00-00 00:00:00',
  `link_datemodified` datetime NOT NULL default '0000-00-00 00:00:00',
  `link_creator_user_ID` int(11) unsigned NOT NULL,
  `link_lastedit_user_ID` int(11) unsigned NOT NULL,
  `link_item_ID` int(11) unsigned NOT NULL,
  `link_dest_item_ID` int(11) unsigned default NULL,
  `link_file_ID` int(11) unsigned default NULL,
  `link_ltype_ID` int(11) unsigned NOT NULL default '1',
  `link_external_url` varchar(255) default NULL,
  `link_title` text,
  PRIMARY KEY  (`link_ID`),
  KEY `link_item_ID` (`link_item_ID`),
  KEY `link_dest_item_ID` (`link_dest_item_ID`),
  KEY `link_file_ID` (`link_file_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `evo_links`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `evo_locales`
-- 

CREATE TABLE `evo_locales` (
  `loc_locale` varchar(20) NOT NULL default '',
  `loc_charset` varchar(15) NOT NULL default 'iso-8859-1',
  `loc_datefmt` varchar(10) NOT NULL default 'y-m-d',
  `loc_timefmt` varchar(10) NOT NULL default 'H:i:s',
  `loc_startofweek` tinyint(3) unsigned NOT NULL default '1',
  `loc_name` varchar(40) NOT NULL default '',
  `loc_messages` varchar(20) NOT NULL default '',
  `loc_priority` tinyint(4) unsigned NOT NULL default '0',
  `loc_enabled` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`loc_locale`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='saves available locales';

-- 
-- Dumping data for table `evo_locales`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `evo_plugins`
-- 

CREATE TABLE `evo_plugins` (
  `plug_ID` int(11) unsigned NOT NULL auto_increment,
  `plug_priority` int(11) NOT NULL default '50',
  `plug_classname` varchar(40) NOT NULL default '',
  PRIMARY KEY  (`plug_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

-- 
-- Dumping data for table `evo_plugins`
-- 

INSERT INTO `evo_plugins` VALUES (1, 30, 'quicktags_plugin');
INSERT INTO `evo_plugins` VALUES (2, 70, 'auto_p_plugin');
INSERT INTO `evo_plugins` VALUES (3, 90, 'texturize_plugin');
INSERT INTO `evo_plugins` VALUES (4, 20, 'calendar_plugin');
INSERT INTO `evo_plugins` VALUES (5, 50, 'archives_plugin');
INSERT INTO `evo_plugins` VALUES (6, 60, 'categories_plugin');

-- --------------------------------------------------------

-- 
-- Table structure for table `evo_postcats`
-- 

CREATE TABLE `evo_postcats` (
  `postcat_post_ID` int(11) unsigned NOT NULL,
  `postcat_cat_ID` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`postcat_post_ID`,`postcat_cat_ID`),
  UNIQUE KEY `catpost` (`postcat_cat_ID`,`postcat_post_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `evo_postcats`
-- 

INSERT INTO `evo_postcats` VALUES (1, 1);
INSERT INTO `evo_postcats` VALUES (1, 4);
INSERT INTO `evo_postcats` VALUES (2, 1);
INSERT INTO `evo_postcats` VALUES (2, 2);
INSERT INTO `evo_postcats` VALUES (2, 3);
INSERT INTO `evo_postcats` VALUES (3, 5);
INSERT INTO `evo_postcats` VALUES (4, 13);
INSERT INTO `evo_postcats` VALUES (5, 13);
INSERT INTO `evo_postcats` VALUES (6, 13);
INSERT INTO `evo_postcats` VALUES (7, 13);
INSERT INTO `evo_postcats` VALUES (8, 13);
INSERT INTO `evo_postcats` VALUES (9, 13);
INSERT INTO `evo_postcats` VALUES (10, 13);
INSERT INTO `evo_postcats` VALUES (11, 12);
INSERT INTO `evo_postcats` VALUES (12, 12);
INSERT INTO `evo_postcats` VALUES (13, 11);
INSERT INTO `evo_postcats` VALUES (14, 11);
INSERT INTO `evo_postcats` VALUES (15, 11);
INSERT INTO `evo_postcats` VALUES (16, 11);
INSERT INTO `evo_postcats` VALUES (17, 11);
INSERT INTO `evo_postcats` VALUES (18, 3);
INSERT INTO `evo_postcats` VALUES (18, 11);
INSERT INTO `evo_postcats` VALUES (19, 3);
INSERT INTO `evo_postcats` VALUES (19, 11);
INSERT INTO `evo_postcats` VALUES (20, 3);
INSERT INTO `evo_postcats` VALUES (20, 11);
INSERT INTO `evo_postcats` VALUES (21, 1);
INSERT INTO `evo_postcats` VALUES (21, 4);
INSERT INTO `evo_postcats` VALUES (21, 11);

-- --------------------------------------------------------

-- 
-- Table structure for table `evo_posts`
-- 

CREATE TABLE `evo_posts` (
  `post_ID` int(11) unsigned NOT NULL auto_increment,
  `post_parent_ID` int(11) unsigned default NULL,
  `post_creator_user_ID` int(11) unsigned NOT NULL,
  `post_lastedit_user_ID` int(11) unsigned default NULL,
  `post_assigned_user_ID` int(11) unsigned default NULL,
  `post_datestart` datetime NOT NULL,
  `post_datedeadline` datetime default NULL,
  `post_datecreated` datetime default NULL,
  `post_datemodified` datetime NOT NULL,
  `post_status` enum('published','deprecated','protected','private','draft') NOT NULL default 'published',
  `post_pst_ID` int(11) unsigned default NULL,
  `post_ptyp_ID` int(11) unsigned default NULL,
  `post_locale` varchar(20) NOT NULL default 'en-EU',
  `post_content` text NOT NULL,
  `post_title` text NOT NULL,
  `post_urltitle` varchar(50) default NULL,
  `post_url` varchar(250) default NULL,
  `post_main_cat_ID` int(11) unsigned NOT NULL,
  `post_flags` set('pingsdone','imported') default NULL,
  `post_views` int(11) unsigned NOT NULL default '0',
  `post_wordcount` int(11) default NULL,
  `post_comments` enum('disabled','open','closed') NOT NULL default 'open',
  `post_commentsexpire` datetime default NULL,
  `post_renderers` varchar(179) NOT NULL default 'default',
  `post_priority` int(11) unsigned default NULL,
  PRIMARY KEY  (`post_ID`),
  UNIQUE KEY `post_urltitle` (`post_urltitle`),
  KEY `post_datestart` (`post_datestart`),
  KEY `post_main_cat_ID` (`post_main_cat_ID`),
  KEY `post_creator_user_ID` (`post_creator_user_ID`),
  KEY `post_status` (`post_status`),
  KEY `post_parent_ID` (`post_parent_ID`),
  KEY `post_assigned_user_ID` (`post_assigned_user_ID`),
  KEY `post_ptyp_ID` (`post_ptyp_ID`),
  KEY `post_pst_ID` (`post_pst_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=22 ;

-- 
-- Dumping data for table `evo_posts`
-- 

INSERT INTO `evo_posts` VALUES (1, NULL, 1, 1, NULL, '2005-12-16 00:30:40', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', '<p>This is the first post.</p>\n\n<p>It appears on both blog A and blog B.</p>', 'First Post', 'first_post', '', 1, 'pingsdone', 0, 14, 'open', NULL, 'default', NULL);
INSERT INTO `evo_posts` VALUES (2, NULL, 1, 1, NULL, '2005-12-16 00:30:41', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', '<p>This is the second post.</p>\n\n<p>It appears on blog A only but in multiple categories.</p>', 'Second post', 'second_post', '', 2, 'pingsdone', 0, 15, 'open', NULL, 'default', NULL);
INSERT INTO `evo_posts` VALUES (3, NULL, 1, 1, NULL, '2005-12-16 00:30:42', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', '<p>This is the third post.</p>\n\n<p>It appears on blog B only and in a single category.</p>', 'Third post', 'third_post', '', 5, 'pingsdone', 0, 16, 'open', NULL, 'default', NULL);
INSERT INTO `evo_posts` VALUES (4, NULL, 1, 1, NULL, '2005-12-16 00:30:43', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'fr-FR', 'Contrib', 'Bertrand', 'bertrand', 'http://www.epistema.com/fr/societe/weblog.php', 13, 'pingsdone', 0, 1, 'disabled', NULL, '', NULL);
INSERT INTO `evo_posts` VALUES (5, NULL, 1, 1, NULL, '2005-12-16 00:30:44', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-US', 'Contrib', 'Jeff', 'jeff', 'http://www.jeffbearer.com/', 13, 'pingsdone', 0, 1, 'disabled', NULL, '', NULL);
INSERT INTO `evo_posts` VALUES (6, NULL, 1, 1, NULL, '2005-12-16 00:30:45', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-US', 'Contrib', 'Jason', 'jason', 'http://itc.uncc.edu/blog/jwedgeco/', 13, 'pingsdone', 0, 1, 'disabled', NULL, '', NULL);
INSERT INTO `evo_posts` VALUES (7, NULL, 1, 1, NULL, '2005-12-16 00:30:46', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-US', 'Debug', 'Yabba', 'yabba', 'http://yabba.waffleson.com/', 13, 'pingsdone', 0, 1, 'disabled', NULL, '', NULL);
INSERT INTO `evo_posts` VALUES (8, NULL, 1, 1, NULL, '2005-12-16 00:30:47', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-US', 'Contrib', 'Halton', 'halton', 'http://www.squishymonkey.com/', 13, 'pingsdone', 0, 1, 'disabled', NULL, '', NULL);
INSERT INTO `evo_posts` VALUES (9, NULL, 1, 1, NULL, '2005-12-16 00:30:48', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'de-DE', 'Development', 'dAniel', 'daniel', 'http://thequod.de/', 13, 'pingsdone', 0, 1, 'disabled', NULL, '', NULL);
INSERT INTO `evo_posts` VALUES (10, NULL, 1, 1, NULL, '2005-12-16 00:30:49', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'fr-FR', 'Main dev', 'Francois', 'francois', 'http://fplanque.net/Blog/', 13, 'pingsdone', 0, 2, 'disabled', NULL, '', NULL);
INSERT INTO `evo_posts` VALUES (11, NULL, 1, 1, NULL, '2005-12-16 00:30:50', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'Project home', 'b2evolution', 'b2evolution', 'http://b2evolution.net/', 12, 'pingsdone', 0, 2, 'disabled', NULL, '', NULL);
INSERT INTO `evo_posts` VALUES (12, NULL, 1, 1, NULL, '2005-12-16 00:30:51', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'This is sample text describing the linkblog entry. In most cases however, you''ll want to leave this blank, providing just a Title and an Url for your linkblog entries (favorite/related sites).', 'This is a sample linkblog entry', 'this_is_a_sample_linkblog_entry', 'http://b2evolution.net/', 12, 'pingsdone', 0, 32, 'disabled', NULL, '', NULL);
INSERT INTO `evo_posts` VALUES (13, NULL, 1, 1, NULL, '2005-12-16 00:30:52', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'b2evolution uses old-style permalinks and feedback links by default. This is to ensure maximum compatibility with various webserver configurations.\n\nNethertheless, once you feel comfortable with b2evolution, you should try activating clean permalinks in the Settings screen... (check ''Use extra-path info'')', 'Clean Permalinks!', 'clean_permalinks', '', 11, 'pingsdone', 0, 42, 'open', NULL, 'default', NULL);
INSERT INTO `evo_posts` VALUES (14, NULL, 1, 1, NULL, '2005-12-16 00:30:53', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'In the <code>/blogs</code> folder as well as in <code>/blogs/admin</code> there are two files called [<code>sample.htaccess</code>]. You should try renaming those to [<code>.htaccess</code>].\n\nThis will optimize the way b2evolution is handled by the webserver (if you are using Apache). These files are not active by default because a few hosts would display an error right away when you try to use them. If this happens to you when you rename the files, just remove them and you''ll be fine.', 'Apache optimization...', 'apache_optimization', '', 11, 'pingsdone', 0, 81, 'open', NULL, 'default', NULL);
INSERT INTO `evo_posts` VALUES (15, NULL, 1, 1, NULL, '2005-12-16 00:30:54', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'By default, b2evolution blogs are displayed using a default skin.\n\nReaders can choose a new skin by using the skin switcher integrated in most skins.\n\nYou can change the default skin used for any blog by editing the blog parameters in the admin interface. You can also force the use of the default skin for everyone.\n\nOtherwise, you can restrict available skins by deleting some of them from the /blogs/skins folder. You can also create new skins by duplicating, renaming and customizing any existing skin folder.\n\nTo start customizing a skin, open its ''<code>_main.php</code>'' file in an editor and read the comments in there. And, of course, read the manual on evoSkins!', 'About evoSkins...', 'about_evoskins', '', 11, 'pingsdone', 0, 115, 'open', NULL, 'default', NULL);
INSERT INTO `evo_posts` VALUES (16, NULL, 1, 1, NULL, '2005-12-16 00:30:55', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'By default, all pre-installed blogs are displayed using a skin. (More on skins in another post.)\n\nThat means, blogs are accessed through ''<code>index.php</code>'', which loads default parameters from the database and then passes on the display job to a skin.\n\nAlternatively, if you don''t want to use the default DB parameters and want to, say, force a skin, a category or a specific linkblog, you can create a stub file like the provided ''<code>a_stub.php</code>'' and call your blog through this stub instead of index.php .\n\nFinally, if you need to do some very specific customizations to your blog, you may use plain templates instead of skins. In this case, call your blog through a full template, like the provided ''<code>a_noskin.php</code>''.\n\nYou will find more information in the stub/template files themselves. Open them in a text editor and read the comments in there.\n\nEither way, make sure you go to the blogs admin and set the correct access method for your blog. When using a stub or a template, you must also set its filename in the ''Stub name'' field. Otherwise, the permalinks will not function properly.', 'Skins, Stubs and Templates...', 'skins_stubs_and_templates', '', 11, 'pingsdone', 0, 192, 'open', NULL, 'default', NULL);
INSERT INTO `evo_posts` VALUES (17, NULL, 1, 1, NULL, '2005-12-16 00:30:56', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'By default, b2evolution comes with 4 blogs, named ''Blog All'', ''Blog A'', ''Blog B'' and ''Linkblog''.\n\nSome of these blogs have a special role. Read about it on the corresponding page.\n\nYou can create additional blogs or delete unwanted blogs from the blogs admin.', 'Multiple Blogs, new blogs, old blogs...', 'multiple_blogs_new_blogs_old_blogs', '', 11, 'pingsdone', 0, 44, 'open', NULL, 'default', NULL);
INSERT INTO `evo_posts` VALUES (18, NULL, 1, 1, NULL, '2005-12-16 00:30:57', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'This is page 1 of a multipage post.\n\nYou can see the other pages by clicking on the links below the text.\n\n<!--nextpage-->\n\nThis is page 2.\n\n<!--nextpage-->\n\nThis is page 3.\n\n<!--nextpage-->\n\nThis is page 4.\n\nIt is the last page.', 'This is a multipage post', 'this_is_a_multipage_post', '', 11, 'pingsdone', 0, 35, 'open', NULL, 'default', NULL);
INSERT INTO `evo_posts` VALUES (19, NULL, 1, 1, NULL, '2005-12-16 00:30:58', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'This is an extended post with no teaser. This means that you won''t see this teaser any more when you click the "more" link.\n\n<!--more--><!--noteaser-->\n\nThis is the extended text. You only see it when you have clicked the "more" link.', 'Extended post with no teaser', 'extended_post_with_no_teaser', '', 11, 'pingsdone', 0, 40, 'open', NULL, 'default', NULL);
INSERT INTO `evo_posts` VALUES (20, NULL, 1, 1, NULL, '2005-12-16 00:30:59', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'This is an extended post. This means you only see this small teaser by default and you must click on the link below to see more.\n\n<!--more-->\n\nThis is the extended text. You only see it when you have clicked the "more" link.', 'Extended post', 'extended_post', '', 11, 'pingsdone', 0, 42, 'open', NULL, 'default', NULL);
INSERT INTO `evo_posts` VALUES (21, NULL, 1, 1, NULL, '2005-12-16 00:31:00', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'Blog B contains a few posts in the ''b2evolution Tips'' category.\n\nAll these entries are designed to help you so, as EdB would say: "<em>read them all before you start hacking away!</em>" ;)\n\nIf you wish, you can delete these posts one by one after you have read them. You could also change their status to ''deprecated'' in order to visually keep track of what you have already read.', 'Important information', 'important_information', '', 11, 'pingsdone', 0, 69, 'open', NULL, 'default', NULL);

-- --------------------------------------------------------

-- 
-- Table structure for table `evo_poststatuses`
-- 

CREATE TABLE `evo_poststatuses` (
  `pst_ID` int(11) unsigned NOT NULL auto_increment,
  `pst_name` varchar(30) NOT NULL,
  PRIMARY KEY  (`pst_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `evo_poststatuses`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `evo_posttypes`
-- 

CREATE TABLE `evo_posttypes` (
  `ptyp_ID` int(11) unsigned NOT NULL auto_increment,
  `ptyp_name` varchar(30) NOT NULL,
  PRIMARY KEY  (`ptyp_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

-- 
-- Dumping data for table `evo_posttypes`
-- 

INSERT INTO `evo_posttypes` VALUES (1, 'Post');
INSERT INTO `evo_posttypes` VALUES (2, 'Link');

-- --------------------------------------------------------

-- 
-- Table structure for table `evo_sessions`
-- 

CREATE TABLE `evo_sessions` (
  `sess_ID` int(11) unsigned NOT NULL auto_increment,
  `sess_key` char(32) default NULL,
  `sess_lastseen` datetime NOT NULL,
  `sess_ipaddress` varchar(15) NOT NULL default '',
  `sess_user_ID` int(10) default NULL,
  `sess_agnt_ID` int(10) unsigned default NULL,
  `sess_data` text,
  PRIMARY KEY  (`sess_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `evo_sessions`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `evo_settings`
-- 

CREATE TABLE `evo_settings` (
  `set_name` varchar(30) NOT NULL,
  `set_value` varchar(255) default NULL,
  PRIMARY KEY  (`set_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `evo_settings`
-- 

INSERT INTO `evo_settings` VALUES ('db_version', '9000');
INSERT INTO `evo_settings` VALUES ('default_locale', 'en-EU');
INSERT INTO `evo_settings` VALUES ('newusers_grp_ID', '4');

-- --------------------------------------------------------

-- 
-- Table structure for table `evo_subscriptions`
-- 

CREATE TABLE `evo_subscriptions` (
  `sub_coll_ID` int(11) unsigned NOT NULL,
  `sub_user_ID` int(11) unsigned NOT NULL,
  `sub_items` tinyint(1) NOT NULL,
  `sub_comments` tinyint(1) NOT NULL,
  PRIMARY KEY  (`sub_coll_ID`,`sub_user_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `evo_subscriptions`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `evo_useragents`
-- 

CREATE TABLE `evo_useragents` (
  `agnt_ID` int(10) unsigned NOT NULL auto_increment,
  `agnt_signature` varchar(250) NOT NULL,
  `agnt_type` enum('rss','robot','browser','unknown') NOT NULL default 'unknown',
  PRIMARY KEY  (`agnt_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `evo_useragents`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `evo_users`
-- 

CREATE TABLE `evo_users` (
  `user_ID` int(11) unsigned NOT NULL auto_increment,
  `user_login` varchar(20) NOT NULL,
  `user_pass` char(32) NOT NULL,
  `user_firstname` varchar(50) NOT NULL,
  `user_lastname` varchar(50) NOT NULL,
  `user_nickname` varchar(50) NOT NULL,
  `user_icq` int(11) unsigned NOT NULL default '0',
  `user_email` varchar(100) NOT NULL,
  `user_url` varchar(100) NOT NULL,
  `user_ip` varchar(15) NOT NULL,
  `user_domain` varchar(200) NOT NULL,
  `user_browser` varchar(200) NOT NULL,
  `dateYMDhour` datetime NOT NULL default '0000-00-00 00:00:00',
  `user_level` int(10) unsigned NOT NULL default '0',
  `user_aim` varchar(50) NOT NULL,
  `user_msn` varchar(100) NOT NULL,
  `user_yim` varchar(50) NOT NULL,
  `user_locale` varchar(20) NOT NULL default 'en-EU',
  `user_idmode` varchar(20) NOT NULL default 'login',
  `user_notify` tinyint(1) NOT NULL default '1',
  `user_showonline` tinyint(1) NOT NULL default '1',
  `user_grp_ID` int(4) NOT NULL default '1',
  PRIMARY KEY  (`user_ID`),
  UNIQUE KEY `user_login` (`user_login`),
  KEY `user_grp_ID` (`user_grp_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

-- 
-- Dumping data for table `evo_users`
-- 

INSERT INTO `evo_users` VALUES (1, 'admin', 'abda0b8ba23a671ee4c9ca75b2eb72e8', '', '', 'admin', 0, 'postmaster@localhost', '', '127.0.0.1', 'localhost', '', '2005-12-16 00:30:38', 10, '', '', '', 'en-EU', 'login', 1, 1, 1);
INSERT INTO `evo_users` VALUES (2, 'demouser', 'abda0b8ba23a671ee4c9ca75b2eb72e8', '', '', 'Mr. Demo', 0, 'postmaster@localhost', '', '127.0.0.1', 'localhost', '', '2005-12-16 00:30:39', 0, '', '', '', 'en-EU', 'login', 1, 1, 4);

-- --------------------------------------------------------

-- 
-- Table structure for table `evo_usersettings`
-- 

CREATE TABLE `evo_usersettings` (
  `uset_user_ID` int(11) unsigned NOT NULL,
  `uset_name` varchar(30) NOT NULL,
  `uset_value` varchar(255) default NULL,
  PRIMARY KEY  (`uset_user_ID`,`uset_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `evo_usersettings`
-- 

