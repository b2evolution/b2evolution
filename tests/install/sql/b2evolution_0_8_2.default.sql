-- phpMyAdmin SQL Dump
-- version 2.7.0-pl2-Debian-1
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Feb 24, 2006 at 07:02 PM
-- Server version: 5.0.18
-- PHP Version: 5.1.2
-- 
-- Database: `b2_082`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `evo_blogs`
-- 

CREATE TABLE `evo_blogs` (
  `blog_ID` int(4) NOT NULL auto_increment,
  `blog_shortname` varchar(12) default '',
  `blog_name` varchar(50) NOT NULL default '',
  `blog_tagline` varchar(250) default '',
  `blog_description` varchar(250) default '',
  `blog_longdesc` tinytext,
  `blog_lang` varchar(12) NOT NULL default 'en',
  `blog_siteurl` varchar(120) NOT NULL default '',
  `blog_filename` varchar(30) default 'blog.php',
  `blog_staticfilename` varchar(30) default NULL,
  `blog_stub` varchar(30) default 'blog.php',
  `blog_roll` text,
  `blog_keywords` tinytext,
  `blog_default_skin` varchar(30) NOT NULL default 'standard',
  `blog_UID` varchar(20) default NULL,
  PRIMARY KEY  (`blog_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

-- 
-- Dumping data for table `evo_blogs`
-- 

INSERT INTO `evo_blogs` VALUES (1, 'All', 'All Blogs', 'Tagline for All', 'All blogs on this system.', '', 'fr', 'http://localhost/blogs', 'blog_all.php', 'blog_all.html', 'blog_all.php', 'This is the blogroll for the ''all blogs'' blog aggregation.', 'all blogs keywords', 'standard', '');
INSERT INTO `evo_blogs` VALUES (2, 'Blog A', 'Demo Blog A', 'Tagline for A', 'This is demo blog A', 'This is description for demo blog A. It has index #2 in the database.', 'fr', 'http://localhost/blogs', 'blog_a.php', 'blog_a.html', 'blog_a.php', 'This is the blogroll for Blog A...', 'blog A keywords', 'standard', '');
INSERT INTO `evo_blogs` VALUES (3, 'Blog B', 'Demo Blog B', 'Tagline for B', 'This is demo blog B', 'This is description for demo blog B. It has index #3 in the database.', 'fr', 'http://localhost/blogs', 'blog_b.php', 'blog_b.html', 'blog_b.php', 'This is the blogroll for Blog B...', 'blog B keywords', 'standard', '');
INSERT INTO `evo_blogs` VALUES (4, 'Blogroll', 'Demo Blogroll', 'Tagline for Blogroll', 'This is the demo blogroll', 'This is description for blogroll. It has index #4 in the database.', 'fr', 'http://localhost/blogs', 'blog_roll.php', 'blog_roll.html', 'blog_roll.php', 'This is the blogroll for the blogroll... pretty funky huh? :))', 'blogroll keywords', 'standard', '');

-- --------------------------------------------------------

-- 
-- Table structure for table `evo_categories`
-- 

CREATE TABLE `evo_categories` (
  `cat_ID` int(4) NOT NULL auto_increment,
  `cat_parent_ID` int(11) default NULL,
  `cat_name` tinytext NOT NULL,
  `cat_blog_ID` int(11) NOT NULL default '2',
  PRIMARY KEY  (`cat_ID`),
  KEY `cat_blog_ID` (`cat_blog_ID`),
  KEY `cat_parent_ID` (`cat_parent_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=14 ;

-- 
-- Dumping data for table `evo_categories`
-- 

INSERT INTO `evo_categories` VALUES (1, NULL, 'Announcements [A]', 2);
INSERT INTO `evo_categories` VALUES (2, NULL, 'News', 2);
INSERT INTO `evo_categories` VALUES (3, NULL, 'Background', 2);
INSERT INTO `evo_categories` VALUES (4, NULL, 'Announcements [B]', 3);
INSERT INTO `evo_categories` VALUES (5, NULL, 'Fun', 3);
INSERT INTO `evo_categories` VALUES (6, 5, 'In real life', 3);
INSERT INTO `evo_categories` VALUES (7, 5, 'On the web', 3);
INSERT INTO `evo_categories` VALUES (8, 6, 'Sports', 3);
INSERT INTO `evo_categories` VALUES (9, 6, 'Movies', 3);
INSERT INTO `evo_categories` VALUES (10, 6, 'Music', 3);
INSERT INTO `evo_categories` VALUES (11, NULL, 'b2evolution', 3);
INSERT INTO `evo_categories` VALUES (12, NULL, 'b2evolution', 4);
INSERT INTO `evo_categories` VALUES (13, NULL, 'contributors', 4);

-- --------------------------------------------------------

-- 
-- Table structure for table `evo_comments`
-- 

CREATE TABLE `evo_comments` (
  `comment_ID` int(11) unsigned NOT NULL auto_increment,
  `comment_post_ID` int(11) NOT NULL default '0',
  `comment_type` enum('comment','linkback','trackback','pingback') NOT NULL default 'comment',
  `comment_status` enum('published','deprecated','protected','private','draft') NOT NULL default 'published',
  `comment_author` tinytext NOT NULL,
  `comment_author_email` varchar(100) NOT NULL default '',
  `comment_author_url` varchar(100) NOT NULL default '',
  `comment_author_IP` varchar(100) NOT NULL default '',
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

INSERT INTO `evo_comments` VALUES (1, 1, 'comment', 'published', 'miss b2', 'missb2@example.com', 'http://example.com', '127.0.0.1', '2006-02-24 18:52:00', 'Hi, this is a comment.<br />To delete a comment, just log in, and view the posts'' comments, there you will have the option to edit or delete them.', 0);

-- --------------------------------------------------------

-- 
-- Table structure for table `evo_hitlog`
-- 

CREATE TABLE `evo_hitlog` (
  `visitID` bigint(11) NOT NULL auto_increment,
  `visitTime` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `visitURL` varchar(250) default NULL,
  `hit_ignore` enum('no','invalid','badchar','blacklist','rss','robot','search') NOT NULL default 'no',
  `referingURL` varchar(250) default NULL,
  `baseDomain` varchar(250) default NULL,
  `hit_blog_ID` int(11) NOT NULL default '0',
  `hit_remote_addr` varchar(40) default NULL,
  `hit_user_agent` varchar(250) default NULL,
  PRIMARY KEY  (`visitID`),
  KEY `hit_ignore` (`hit_ignore`),
  KEY `baseDomain` (`baseDomain`),
  KEY `hit_blog_ID` (`hit_blog_ID`),
  KEY `hit_user_agent` (`hit_user_agent`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `evo_hitlog`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `evo_postcats`
-- 

CREATE TABLE `evo_postcats` (
  `postcat_post_ID` int(11) NOT NULL default '0',
  `postcat_cat_ID` int(11) NOT NULL default '0',
  PRIMARY KEY  (`postcat_post_ID`,`postcat_cat_ID`)
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
INSERT INTO `evo_postcats` VALUES (4, 9);
INSERT INTO `evo_postcats` VALUES (5, 11);
INSERT INTO `evo_postcats` VALUES (6, 11);
INSERT INTO `evo_postcats` VALUES (7, 13);
INSERT INTO `evo_postcats` VALUES (8, 13);
INSERT INTO `evo_postcats` VALUES (9, 13);
INSERT INTO `evo_postcats` VALUES (10, 13);
INSERT INTO `evo_postcats` VALUES (11, 13);
INSERT INTO `evo_postcats` VALUES (12, 13);
INSERT INTO `evo_postcats` VALUES (13, 13);
INSERT INTO `evo_postcats` VALUES (14, 12);
INSERT INTO `evo_postcats` VALUES (15, 12);

-- --------------------------------------------------------

-- 
-- Table structure for table `evo_posts`
-- 

CREATE TABLE `evo_posts` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `post_author` int(4) NOT NULL default '0',
  `post_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `post_status` enum('published','deprecated','protected','private','draft') NOT NULL default 'published',
  `post_lang` varchar(12) default NULL,
  `post_content` text NOT NULL,
  `post_title` text NOT NULL,
  `post_category` int(4) NOT NULL default '0',
  `post_trackbacks` text,
  `post_autobr` tinyint(4) NOT NULL default '1',
  `post_flags` set('pingsdone','pbdone','tbdone','html','bbcode','gmcode','smartquotes','smileys','glossary','imported') default NULL,
  `post_karma` int(11) NOT NULL default '0',
  `post_wordcount` int(11) default NULL,
  PRIMARY KEY  (`ID`),
  KEY `post_date` (`post_date`),
  KEY `post_category` (`post_category`),
  KEY `post_author` (`post_author`),
  KEY `post_status` (`post_status`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=16 ;

-- 
-- Dumping data for table `evo_posts`
-- 

INSERT INTO `evo_posts` VALUES (1, 1, '2006-02-24 18:52:00', 'published', 'en', '<p>This is the first post.</p>\r\n		\r\n		<p>It appears on both blog A and blog B.</p>', 'First Post', 1, '', 0, 'pingsdone,html,bbcode,gmcode,smileys', 0, NULL);
INSERT INTO `evo_posts` VALUES (2, 1, '2006-02-24 18:52:01', 'published', 'en', '<p>This is the second post.</p>\r\n		\r\n		<p>It appears on blog A only but in multiple categories.</p>', 'Second post', 2, '', 0, 'pingsdone,html,bbcode,gmcode,smileys', 0, NULL);
INSERT INTO `evo_posts` VALUES (3, 1, '2006-02-24 18:52:02', 'published', 'en', '<p>This is the third post.</p>\r\n		\r\n		<p>It appears on blog B only and in a single category.</p>', 'Third post', 5, '', 0, 'pingsdone,html,bbcode,gmcode,smileys', 0, NULL);
INSERT INTO `evo_posts` VALUES (4, 1, '2006-02-24 18:52:03', 'published', 'en', '<p>Wait until the end of the super long end credits!</p>\r\n		\r\n		<p>If you''re patient enough, you''ll a get preview of the next episode...</p>\r\n		\r\n		<p>Though... it''s just the same anyway! :>></p>', 'Matrix Reloaded', 9, '', 0, 'pingsdone,html,bbcode,gmcode,smileys', 0, NULL);
INSERT INTO `evo_posts` VALUES (5, 1, '2006-02-24 18:52:04', 'published', 'en', '<p>b2evolution uses old-style permalinks and feedback links by default. This is to ensure maximum compatibility with various webserver configurations. Nethertheless, if you feel comfortable, you should try activating clean permalinks in the /conf/b2evo_advanced.php file...</p>', 'Clean Permalinks! :idea:', 11, '', 0, 'pingsdone,html,bbcode,gmcode,smileys', 0, NULL);
INSERT INTO `evo_posts` VALUES (6, 1, '2006-02-24 18:52:05', 'published', 'en', '<p>By default, b2evolution blogs are displayed in the \\''standard\\'' skin.</p>\r\n\r\n<p>Readers can choose a new skin by using the skin switcher integrated in most skins.</p>		\r\n\r\n<p>You can restrict available skins by deleting some of them from the /blogs/skins folder. You can also change the default skin or force a specific skin. <strong>Actually, you should change the default skin and delete the standard skin, as this one has navigation links at the top that are only good for the sake of the demo. These would be a nonsense on production servers!</strong> Read the manual on evoSkins!</p>', 'Clean Skin! :idea:', 11, '', 0, 'pingsdone,html,bbcode,gmcode,smileys', 0, NULL);
INSERT INTO `evo_posts` VALUES (7, 1, '2006-02-24 18:52:06', 'published', 'en', 'Main dev', 'François', 13, 'http://fplanque.net/Blog/', 0, 'pingsdone,html,bbcode,gmcode,smileys', 0, NULL);
INSERT INTO `evo_posts` VALUES (8, 1, '2006-02-24 18:52:07', 'published', 'en', 'Testing', 'Candle', 13, 'http://www.candles-weblog.us/', 0, 'pingsdone,html,bbcode,gmcode,smileys', 0, NULL);
INSERT INTO `evo_posts` VALUES (9, 1, '2006-02-24 18:52:08', 'published', 'en', 'Hacks, Testing', 'Ron', 13, 'http://www.rononline.nl/', 0, 'pingsdone,html,bbcode,gmcode,smileys', 0, NULL);
INSERT INTO `evo_posts` VALUES (10, 1, '2006-02-24 18:52:09', 'published', 'en', 'evoSkins.org, Testing', 'Sabrina', 13, 'http://lifeisadiaper.com/', 0, 'pingsdone,html,bbcode,gmcode,smileys', 0, NULL);
INSERT INTO `evo_posts` VALUES (11, 1, '2006-02-24 18:52:10', 'published', 'en', 'Testing', 'Graham', 13, 'http://www.teenangst.co.uk/', 0, 'pingsdone,html,bbcode,gmcode,smileys', 0, NULL);
INSERT INTO `evo_posts` VALUES (12, 1, '2006-02-24 18:52:11', 'published', 'en', 'Testing', 'Topanga', 13, 'http://www.tenderfeelings.be/', 0, 'pingsdone,html,bbcode,gmcode,smileys', 0, NULL);
INSERT INTO `evo_posts` VALUES (13, 1, '2006-02-24 18:52:12', 'published', 'en', 'Hosting', 'Brian', 13, 'http://www.memenethosting.com/', 0, 'pingsdone,html,bbcode,gmcode,smileys', 0, NULL);
INSERT INTO `evo_posts` VALUES (14, 1, '2006-02-24 18:52:13', 'published', 'en', 'get more skins!', 'evoSkins.org', 12, 'http://www.evoskins.org/', 0, 'pingsdone,html,bbcode,gmcode,smileys', 0, NULL);
INSERT INTO `evo_posts` VALUES (15, 1, '2006-02-24 18:52:14', 'published', 'en', 'Project home', 'b2evolution', 12, 'http://b2evolution.net/', 0, 'pingsdone,html,bbcode,gmcode,smileys', 0, NULL);

-- --------------------------------------------------------

-- 
-- Table structure for table `evo_settings`
-- 

CREATE TABLE `evo_settings` (
  `ID` tinyint(3) NOT NULL default '1',
  `posts_per_page` int(4) unsigned NOT NULL default '7',
  `what_to_show` varchar(5) NOT NULL default 'days',
  `archive_mode` varchar(10) NOT NULL default 'weekly',
  `time_difference` tinyint(4) NOT NULL default '0',
  `AutoBR` tinyint(1) NOT NULL default '1',
  `time_format` varchar(20) NOT NULL default 'H:i:s',
  `date_format` varchar(20) NOT NULL default 'Y/m/d',
  `db_version` int(11) NOT NULL default '8000',
  PRIMARY KEY  (`ID`),
  KEY `ID` (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `evo_settings`
-- 

INSERT INTO `evo_settings` VALUES (1, 3, 'paged', 'monthly', 0, 1, 'H:i:s', 'd.m.y', 8000);

-- --------------------------------------------------------

-- 
-- Table structure for table `evo_users`
-- 

CREATE TABLE `evo_users` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `user_login` varchar(20) NOT NULL,
  `user_pass` varchar(20) NOT NULL,
  `user_firstname` varchar(50) NOT NULL,
  `user_lastname` varchar(50) NOT NULL,
  `user_nickname` varchar(50) NOT NULL,
  `user_icq` int(10) unsigned NOT NULL default '0',
  `user_email` varchar(100) NOT NULL,
  `user_url` varchar(100) NOT NULL,
  `user_ip` varchar(15) NOT NULL,
  `user_domain` varchar(200) NOT NULL,
  `user_browser` varchar(200) NOT NULL,
  `dateYMDhour` datetime NOT NULL default '0000-00-00 00:00:00',
  `user_level` int(2) unsigned NOT NULL default '0',
  `user_aim` varchar(50) NOT NULL,
  `user_msn` varchar(100) NOT NULL,
  `user_yim` varchar(50) NOT NULL,
  `user_idmode` varchar(20) NOT NULL,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `ID` (`ID`),
  UNIQUE KEY `user_login` (`user_login`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- 
-- Dumping data for table `evo_users`
-- 

INSERT INTO `evo_users` VALUES (1, 'admin', '9cd9c1', '', '', 'admin', 0, 'postmaster@localhost', '', '127.0.0.1', '127.0.0.1', '', '2000-00-00 00:00:01', 10, '', '', '', 'nickname');
