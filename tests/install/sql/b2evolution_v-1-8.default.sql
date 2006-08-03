-- phpMyAdmin SQL Dump
-- version 2.8.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 03, 2006 at 01:16 AM
-- Server version: 5.0.22
-- PHP Version: 5.1.5-dev
--
-- Database: `xxx`
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=19 ;

--
-- Dumping data for table `evo_antispam`
--

INSERT INTO `evo_antispam` (`aspm_ID`, `aspm_string`, `aspm_source`) VALUES (1, 'penis-enlargement', 'reported'),
(2, 'online-casino', 'reported'),
(3, 'order-viagra', 'reported'),
(4, 'order-phentermine', 'reported'),
(5, 'order-xenical', 'reported'),
(6, 'order-prophecia', 'reported'),
(7, 'sexy-lingerie', 'reported'),
(8, '-porn-', 'reported'),
(9, '-adult-', 'reported'),
(10, '-tits-', 'reported'),
(11, 'buy-phentermine', 'reported'),
(12, 'order-cheap-pills', 'reported'),
(13, 'buy-xenadrine', 'reported'),
(14, 'xxx', 'reported'),
(15, 'paris-hilton', 'reported'),
(16, 'parishilton', 'reported'),
(17, 'camgirls', 'reported'),
(18, 'adult-models', 'reported');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `evo_bloggroups`
--

INSERT INTO `evo_bloggroups` (`bloggroup_blog_ID`, `bloggroup_group_ID`, `bloggroup_ismember`, `bloggroup_perm_poststatuses`, `bloggroup_perm_delpost`, `bloggroup_perm_comments`, `bloggroup_perm_cats`, `bloggroup_perm_properties`, `bloggroup_perm_media_upload`, `bloggroup_perm_media_browse`, `bloggroup_perm_media_change`) VALUES (2, 1, 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1, 1, 1, 1),
(2, 2, 1, 'published,deprecated,protected,private,draft', 1, 1, 0, 0, 1, 1, 1),
(2, 3, 1, 'published,deprecated,protected,private,draft', 0, 0, 0, 0, 1, 1, 0),
(2, 4, 1, '', 0, 0, 0, 0, 0, 0, 0),
(3, 1, 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1, 1, 1, 1),
(3, 2, 1, 'published,deprecated,protected,private,draft', 1, 1, 0, 0, 1, 1, 1),
(3, 3, 1, 'published,deprecated,protected,private,draft', 0, 0, 0, 0, 1, 1, 0),
(3, 4, 1, '', 0, 0, 0, 0, 0, 0, 0),
(4, 1, 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1, 1, 1, 1),
(4, 2, 1, 'published,deprecated,protected,private,draft', 1, 1, 0, 0, 1, 1, 1),
(4, 3, 1, 'published,deprecated,protected,private,draft', 0, 0, 0, 0, 1, 1, 0),
(4, 4, 1, '', 0, 0, 0, 0, 0, 0, 0);

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
  `blog_media_subdir` varchar(255) default NULL,
  `blog_media_fullpath` varchar(255) default NULL,
  `blog_media_url` varchar(255) default NULL,
  `blog_UID` varchar(20) default NULL,
  PRIMARY KEY  (`blog_ID`),
  UNIQUE KEY `blog_urlname` (`blog_urlname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `evo_blogs`
--

INSERT INTO `evo_blogs` (`blog_ID`, `blog_shortname`, `blog_name`, `blog_tagline`, `blog_description`, `blog_longdesc`, `blog_locale`, `blog_access_type`, `blog_siteurl`, `blog_staticfilename`, `blog_stub`, `blog_urlname`, `blog_notes`, `blog_keywords`, `blog_allowcomments`, `blog_allowtrackbacks`, `blog_allowpingbacks`, `blog_allowblogcss`, `blog_allowusercss`, `blog_pingb2evonet`, `blog_pingtechnorati`, `blog_pingweblogs`, `blog_pingblodotgs`, `blog_default_skin`, `blog_force_skin`, `blog_disp_bloglist`, `blog_in_bloglist`, `blog_links_blog_ID`, `blog_commentsexpire`, `blog_media_location`, `blog_media_subdir`, `blog_media_fullpath`, `blog_media_url`, `blog_UID`) VALUES (1, 'Blog All', 'Blog All Title', 'Tagline for Blog All', 'Short description for Blog All', 'This is the long description for the blog named ''Blog All''. <br />\r\n<br />\r\n<strong>This blog (blog #1) is actually a very special blog! It automatically aggregates all posts from all other blogs. This allows you to easily track everything that is posted on this system. You can hide this blog from the public by unchecking ''Include in public blog list'' in the blogs admin.</strong>', 'en-EU', 'index.php', '', 'all.html', 'all', 'all', 'Notes for Blog All', 'Keywords for Blog All', 'post_by_post', 1, 0, 1, 1, 0, 0, 1, 0, 'custom', 0, 1, 1, 4, 0, 'default', NULL, NULL, NULL, ''),
(2, 'Blog A', 'Blog A Title', 'Tagline for Blog A', 'Short description for Blog A', 'This is the long description for the blog named ''Blog A''. ', 'en-EU', 'index.php', '', 'a.html', 'a', 'a', 'Notes for Blog A', 'Keywords for Blog A', 'post_by_post', 1, 0, 1, 1, 0, 0, 1, 0, 'custom', 0, 1, 1, 4, 0, 'default', NULL, NULL, NULL, ''),
(3, 'Blog B', 'Blog B Title', 'Tagline for Blog B', 'Short description for Blog B', 'This is the long description for the blog named ''Blog B''. ', 'en-EU', 'index.php', '', 'b.html', 'b', 'b', 'Notes for Blog B', 'Keywords for Blog B', 'post_by_post', 1, 0, 1, 1, 0, 0, 1, 0, 'custom', 0, 1, 1, 4, 0, 'default', NULL, NULL, NULL, ''),
(4, 'Linkblog', 'Linkblog Title', 'Tagline for Linkblog', 'Short description for Linkblog', 'This is the long description for the blog named ''Linkblog''. <br />\r\n<br />\r\n<strong>The main purpose for this blog is to be included as a side item to other blogs where it will display your favorite/related links.</strong>', 'en-EU', 'index.php', '', 'links.html', 'links', 'links', 'Notes for Linkblog', 'Keywords for Linkblog', 'post_by_post', 1, 0, 1, 1, 0, 0, 1, 0, 'custom', 0, 1, 1, 0, 0, 'default', NULL, NULL, NULL, '');

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
  PRIMARY KEY  (`bloguser_blog_ID`,`bloguser_user_ID`),
  KEY `FK_bloguser_user_ID` (`bloguser_user_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=14 ;

--
-- Dumping data for table `evo_categories`
--

INSERT INTO `evo_categories` (`cat_ID`, `cat_parent_ID`, `cat_name`, `cat_blog_ID`, `cat_description`, `cat_longdesc`, `cat_icon`) VALUES (1, NULL, 'Announcements [A]', 2, NULL, NULL, NULL),
(2, NULL, 'News', 2, NULL, NULL, NULL),
(3, NULL, 'Background', 2, NULL, NULL, NULL),
(4, NULL, 'Announcements [B]', 3, NULL, NULL, NULL),
(5, NULL, 'Fun', 3, NULL, NULL, NULL),
(6, 5, 'In real life', 3, NULL, NULL, NULL),
(7, 5, 'On the web', 3, NULL, NULL, NULL),
(8, 6, 'Sports', 3, NULL, NULL, NULL),
(9, 6, 'Movies', 3, NULL, NULL, NULL),
(10, 6, 'Music', 3, NULL, NULL, NULL),
(11, NULL, 'b2evolution Tips', 3, NULL, NULL, NULL),
(12, NULL, 'b2evolution', 4, NULL, NULL, NULL),
(13, NULL, 'contributors', 4, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `evo_coll_settings`
--

CREATE TABLE `evo_coll_settings` (
  `cset_coll_ID` int(11) unsigned NOT NULL,
  `cset_name` varchar(30) NOT NULL,
  `cset_value` varchar(255) default NULL,
  PRIMARY KEY  (`cset_coll_ID`,`cset_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `evo_coll_settings`
--


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
  `comment_author_email` varchar(255) default NULL,
  `comment_author_url` varchar(255) default NULL,
  `comment_author_IP` varchar(23) NOT NULL default '',
  `comment_date` datetime NOT NULL,
  `comment_content` text NOT NULL,
  `comment_karma` int(11) NOT NULL default '0',
  `comment_spam_karma` tinyint(4) default NULL,
  `comment_allow_msgform` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`comment_ID`),
  KEY `comment_post_ID` (`comment_post_ID`),
  KEY `comment_date` (`comment_date`),
  KEY `comment_type` (`comment_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `evo_comments`
--

INSERT INTO `evo_comments` (`comment_ID`, `comment_post_ID`, `comment_type`, `comment_status`, `comment_author_ID`, `comment_author`, `comment_author_email`, `comment_author_url`, `comment_author_IP`, `comment_date`, `comment_content`, `comment_karma`, `comment_spam_karma`, `comment_allow_msgform`) VALUES (1, 1, 'comment', 'published', NULL, 'miss b2', 'missb2@example.com', 'http://example.com', '127.0.0.1', '2006-08-03 01:15:40', 'Hi, this is a comment.<br />To delete a comment, just log in, and view the posts'' comments, there you will have the option to edit or delete them.', 0, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `evo_cron__log`
--

CREATE TABLE `evo_cron__log` (
  `clog_ctsk_ID` int(10) unsigned NOT NULL,
  `clog_realstart_datetime` datetime NOT NULL,
  `clog_realstop_datetime` datetime default NULL,
  `clog_status` enum('started','finished','error','timeout') NOT NULL default 'started',
  `clog_messages` text,
  PRIMARY KEY  (`clog_ctsk_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `evo_cron__log`
--


-- --------------------------------------------------------

--
-- Table structure for table `evo_cron__task`
--

CREATE TABLE `evo_cron__task` (
  `ctsk_ID` int(10) unsigned NOT NULL auto_increment,
  `ctsk_start_datetime` datetime NOT NULL,
  `ctsk_repeat_after` int(10) unsigned default NULL,
  `ctsk_name` varchar(50) NOT NULL,
  `ctsk_controller` varchar(50) NOT NULL,
  `ctsk_params` text,
  PRIMARY KEY  (`ctsk_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `evo_cron__task`
--


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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `evo_files`
--


-- --------------------------------------------------------

--
-- Table structure for table `evo_filetypes`
--

CREATE TABLE `evo_filetypes` (
  `ftyp_ID` int(11) unsigned NOT NULL auto_increment,
  `ftyp_extensions` varchar(30) NOT NULL,
  `ftyp_name` varchar(30) NOT NULL,
  `ftyp_mimetype` varchar(50) NOT NULL,
  `ftyp_icon` varchar(20) default NULL,
  `ftyp_viewtype` varchar(10) NOT NULL,
  `ftyp_allowed` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`ftyp_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=13 ;

--
-- Dumping data for table `evo_filetypes`
--

INSERT INTO `evo_filetypes` (`ftyp_ID`, `ftyp_extensions`, `ftyp_name`, `ftyp_mimetype`, `ftyp_icon`, `ftyp_viewtype`, `ftyp_allowed`) VALUES (1, 'gif', 'GIF image', 'image/gif', 'image2.png', 'image', 1),
(2, 'png', 'PNG image', 'image/png', 'image2.png', 'image', 1),
(3, 'jpg', 'JPEG image', 'image/jpeg', 'image2.png', 'image', 1),
(4, 'txt', 'Text file', 'text/plain', 'document.png', 'text', 1),
(5, 'htm html', 'HTML file', 'text/html', 'html.png', 'browser', 0),
(6, 'pdf', 'PDF file', 'application/pdf', 'pdf.png', 'browser', 1),
(7, 'doc', 'Microsoft Word file', 'application/msword', 'doc.gif', 'external', 1),
(8, 'xls', 'Microsoft Excel file', 'application/vnd.ms-excel', 'xls.gif', 'external', 1),
(9, 'ppt', 'Powerpoint', 'application/vnd.ms-powerpoint', 'ppt.gif', 'external', 1),
(10, 'pps', 'Powerpoint slideshow', 'pps', 'pps.gif', 'external', 1),
(11, 'zip', 'Zip archive', 'application/zip', 'zip.gif', 'external', 1),
(12, 'php php3 php4 php5 php6', 'Php files', 'application/x-httpd-php', 'php.gif', 'download', 0);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `evo_groups`
--

INSERT INTO `evo_groups` (`grp_ID`, `grp_name`, `grp_perm_admin`, `grp_perm_blogs`, `grp_perm_stats`, `grp_perm_spamblacklist`, `grp_perm_options`, `grp_perm_users`, `grp_perm_templates`, `grp_perm_files`) VALUES (1, 'Administrators', 'visible', 'editall', 'edit', 'edit', 'edit', 'edit', 1, 'edit'),
(2, 'Privileged Bloggers', 'visible', 'viewall', 'view', 'edit', 'view', 'view', 0, 'add'),
(3, 'Bloggers', 'visible', 'user', 'none', 'view', 'none', 'none', 0, 'view'),
(4, 'Basic Users', 'none', 'user', 'none', 'none', 'none', 'none', 0, 'none');

-- --------------------------------------------------------

--
-- Table structure for table `evo_hitlog`
--

CREATE TABLE `evo_hitlog` (
  `hit_ID` int(11) NOT NULL auto_increment,
  `hit_sess_ID` int(10) unsigned default NULL,
  `hit_datetime` datetime NOT NULL,
  `hit_uri` varchar(250) default NULL,
  `hit_referer_type` enum('search','blacklist','referer','direct') NOT NULL,
  `hit_referer` varchar(250) default NULL,
  `hit_referer_dom_ID` int(10) unsigned default NULL,
  `hit_blog_ID` int(11) unsigned default NULL,
  `hit_remote_addr` varchar(40) default NULL,
  `hit_agnt_ID` int(10) unsigned default NULL,
  PRIMARY KEY  (`hit_ID`),
  KEY `hit_datetime` (`hit_datetime`),
  KEY `hit_blog_ID` (`hit_blog_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `evo_hitlog`
--


-- --------------------------------------------------------

--
-- Table structure for table `evo_links`
--

CREATE TABLE `evo_links` (
  `link_ID` int(11) unsigned NOT NULL auto_increment,
  `link_datecreated` datetime NOT NULL,
  `link_datemodified` datetime NOT NULL,
  `link_creator_user_ID` int(11) unsigned NOT NULL,
  `link_lastedit_user_ID` int(11) unsigned NOT NULL,
  `link_itm_ID` int(11) unsigned NOT NULL,
  `link_dest_itm_ID` int(11) unsigned default NULL,
  `link_file_ID` int(11) unsigned default NULL,
  `link_ltype_ID` int(11) unsigned NOT NULL default '1',
  `link_external_url` varchar(255) default NULL,
  `link_title` text,
  PRIMARY KEY  (`link_ID`),
  KEY `link_itm_ID` (`link_itm_ID`),
  KEY `link_dest_itm_ID` (`link_dest_itm_ID`),
  KEY `link_file_ID` (`link_file_ID`),
  KEY `FK_link_creator_user_ID` (`link_creator_user_ID`),
  KEY `FK_link_lastedit_user_ID` (`link_lastedit_user_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='saves available locales';

--
-- Dumping data for table `evo_locales`
--


-- --------------------------------------------------------

--
-- Table structure for table `evo_pluginevents`
--

CREATE TABLE `evo_pluginevents` (
  `pevt_plug_ID` int(11) unsigned NOT NULL,
  `pevt_event` varchar(40) NOT NULL,
  `pevt_enabled` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`pevt_plug_ID`,`pevt_event`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `evo_pluginevents`
--

INSERT INTO `evo_pluginevents` (`pevt_plug_ID`, `pevt_event`, `pevt_enabled`) VALUES (1, 'AdminDisplayToolbar', 1),
(2, 'RenderItemAsHtml', 1),
(3, 'RenderItemAsHtml', 1),
(4, 'RenderItemAsHtml', 1),
(4, 'RenderItemAsXml', 1),
(5, 'SkinTag', 1),
(6, 'SkinTag', 1),
(7, 'SkinTag', 1);

-- --------------------------------------------------------

--
-- Table structure for table `evo_plugins`
--

CREATE TABLE `evo_plugins` (
  `plug_ID` int(11) unsigned NOT NULL auto_increment,
  `plug_priority` tinyint(4) NOT NULL default '50',
  `plug_classname` varchar(40) NOT NULL default '',
  `plug_code` varchar(32) default NULL,
  `plug_apply_rendering` enum('stealth','always','opt-out','opt-in','lazy','never') NOT NULL default 'never',
  `plug_version` varchar(42) NOT NULL default '0',
  `plug_status` enum('enabled','disabled','needs_config','broken') NOT NULL,
  `plug_spam_weight` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`plug_ID`),
  UNIQUE KEY `plug_code` (`plug_code`),
  KEY `plug_status` (`plug_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

--
-- Dumping data for table `evo_plugins`
--

INSERT INTO `evo_plugins` (`plug_ID`, `plug_priority`, `plug_classname`, `plug_code`, `plug_apply_rendering`, `plug_version`, `plug_status`, `plug_spam_weight`) VALUES (1, 30, 'quicktags_plugin', 'b2evQTag', 'never', '1.8', 'enabled', 1),
(2, 70, 'auto_p_plugin', 'b2WPAutP', 'opt-out', '1.8', 'enabled', 1),
(3, 60, 'autolinks_plugin', 'b2evALnk', 'opt-out', '1.8', 'enabled', 1),
(4, 90, 'texturize_plugin', 'b2WPTxrz', 'opt-in', '1.8', 'enabled', 1),
(5, 20, 'calendar_plugin', 'evo_Calr', 'never', '1.8', 'enabled', 1),
(6, 50, 'archives_plugin', 'evo_Arch', 'never', '1.8', 'enabled', 1),
(7, 60, 'categories_plugin', 'evo_Cats', 'never', '1.8', 'enabled', 1);

-- --------------------------------------------------------

--
-- Table structure for table `evo_pluginsettings`
--

CREATE TABLE `evo_pluginsettings` (
  `pset_plug_ID` int(11) unsigned NOT NULL,
  `pset_name` varchar(30) NOT NULL,
  `pset_value` text,
  PRIMARY KEY  (`pset_plug_ID`,`pset_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `evo_pluginsettings`
--


-- --------------------------------------------------------

--
-- Table structure for table `evo_pluginusersettings`
--

CREATE TABLE `evo_pluginusersettings` (
  `puset_plug_ID` int(11) unsigned NOT NULL,
  `puset_user_ID` int(11) unsigned NOT NULL,
  `puset_name` varchar(30) NOT NULL,
  `puset_value` text,
  PRIMARY KEY  (`puset_plug_ID`,`puset_user_ID`,`puset_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `evo_pluginusersettings`
--


-- --------------------------------------------------------

--
-- Table structure for table `evo_postcats`
--

CREATE TABLE `evo_postcats` (
  `postcat_post_ID` int(11) unsigned NOT NULL,
  `postcat_cat_ID` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`postcat_post_ID`,`postcat_cat_ID`),
  UNIQUE KEY `catpost` (`postcat_cat_ID`,`postcat_post_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `evo_postcats`
--

INSERT INTO `evo_postcats` (`postcat_post_ID`, `postcat_cat_ID`) VALUES (1, 1),
(1, 4),
(2, 1),
(2, 2),
(2, 3),
(3, 5),
(4, 13),
(5, 13),
(6, 13),
(7, 13),
(8, 13),
(9, 13),
(10, 13),
(11, 13),
(12, 13),
(13, 12),
(14, 12),
(15, 11),
(16, 11),
(17, 11),
(18, 11),
(19, 11),
(20, 3),
(20, 11),
(21, 3),
(21, 11),
(22, 3),
(22, 11),
(23, 1),
(23, 4),
(23, 11);

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
  `post_content` text,
  `post_title` text NOT NULL,
  `post_urltitle` varchar(50) default NULL,
  `post_url` varchar(255) default NULL,
  `post_main_cat_ID` int(11) unsigned NOT NULL,
  `post_flags` set('pingsdone','imported') default NULL,
  `post_views` int(11) unsigned NOT NULL default '0',
  `post_wordcount` int(11) default NULL,
  `post_comment_status` enum('disabled','open','closed') NOT NULL default 'open',
  `post_commentsexpire` datetime default NULL,
  `post_renderers` text NOT NULL,
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
  KEY `post_pst_ID` (`post_pst_ID`),
  KEY `FK_post_lastedit_user_ID` (`post_lastedit_user_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=24 ;

--
-- Dumping data for table `evo_posts`
--

INSERT INTO `evo_posts` (`post_ID`, `post_parent_ID`, `post_creator_user_ID`, `post_lastedit_user_ID`, `post_assigned_user_ID`, `post_datestart`, `post_datedeadline`, `post_datecreated`, `post_datemodified`, `post_status`, `post_pst_ID`, `post_ptyp_ID`, `post_locale`, `post_content`, `post_title`, `post_urltitle`, `post_url`, `post_main_cat_ID`, `post_flags`, `post_views`, `post_wordcount`, `post_comment_status`, `post_commentsexpire`, `post_renderers`, `post_priority`) VALUES (1, NULL, 1, 1, NULL, '2006-08-03 01:13:34', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', '<p>This is the first post.</p>\r\n\r\n<p>It appears on both blog A and blog B.</p>', 'First Post', 'first_post', '', 1, 'pingsdone', 0, 14, 'open', NULL, 'default', 3),
(2, NULL, 1, 1, NULL, '2006-08-03 01:13:35', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', '<p>This is the second post.</p>\r\n\r\n<p>It appears on blog A only but in multiple categories.</p>', 'Second post', 'second_post', '', 2, 'pingsdone', 0, 15, 'open', NULL, 'default', 3),
(3, NULL, 1, 1, NULL, '2006-08-03 01:13:36', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', '<p>This is the third post.</p>\r\n\r\n<p>It appears on blog B only and in a single category.</p>', 'Third post', 'third_post', '', 5, 'pingsdone', 0, 16, 'open', NULL, 'default', 3),
(4, NULL, 1, 1, NULL, '2006-08-03 01:13:37', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-US', '', 'Travis', 'travis', 'http://www.travisswicegood.com/', 13, 'pingsdone', 0, NULL, 'disabled', NULL, '', 3),
(5, NULL, 1, 1, NULL, '2006-08-03 01:13:38', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-US', '', 'Nate', 'nate', 'http://www.loganelementary.com', 13, 'pingsdone', 0, NULL, 'disabled', NULL, '', 3),
(6, NULL, 1, 1, NULL, '2006-08-03 01:13:39', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-US', '', 'Danny', 'danny', 'http://brendoman.com/dbc', 13, 'pingsdone', 0, NULL, 'disabled', NULL, '', 3),
(7, NULL, 1, 1, NULL, '2006-08-03 01:13:40', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-UK', '', 'Yabba', 'yabba', 'http://www.innervisions.org.uk/babbles/', 13, 'pingsdone', 0, NULL, 'disabled', NULL, '', 3),
(8, NULL, 1, 1, NULL, '2006-08-03 01:13:41', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-US', '', 'Halton', 'halton', 'http://www.squishymonkey.com/', 13, 'pingsdone', 0, NULL, 'disabled', NULL, '', 3),
(9, NULL, 1, 1, NULL, '2006-08-03 01:13:42', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-US', '', 'Topanga', 'topanga', 'http://www.tenderfeelings.be', 13, 'pingsdone', 0, NULL, 'disabled', NULL, '', 3),
(10, NULL, 1, 1, NULL, '2006-08-03 01:13:43', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-US', '', 'EdB', 'edb', 'http://wonderwinds.com/', 13, 'pingsdone', 0, NULL, 'disabled', NULL, '', 3),
(11, NULL, 1, 1, NULL, '2006-08-03 01:13:44', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'de-DE', '', 'dAniel', 'daniel', 'http://daniel.hahler.de/', 13, 'pingsdone', 0, NULL, 'disabled', NULL, '', 3),
(12, NULL, 1, 1, NULL, '2006-08-03 01:13:45', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'fr-FR', '', 'Francois', 'francois', 'http://fplanque.net/', 13, 'pingsdone', 0, NULL, 'disabled', NULL, '', 3),
(13, NULL, 1, 1, NULL, '2006-08-03 01:13:46', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'Project home', 'b2evolution', 'b2evolution', 'http://b2evolution.net/', 12, 'pingsdone', 0, 2, 'disabled', NULL, '', 3),
(14, NULL, 1, 1, NULL, '2006-08-03 01:13:47', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'This is sample text describing the linkblog entry. In most cases however, you''ll want to leave this blank, providing just a Title and an Url for your linkblog entries (favorite/related sites).', 'This is a sample linkblog entry', 'this_is_a_sample_linkblog_entry', 'http://b2evolution.net/', 12, 'pingsdone', 0, 32, 'disabled', NULL, '', 3),
(15, NULL, 1, 1, NULL, '2006-08-03 01:13:48', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'b2evolution uses old-style permalinks and feedback links by default. This is to ensure maximum compatibility with various webserver configurations.\r\n\r\nNethertheless, once you feel comfortable with b2evolution, you should try activating clean permalinks in the Settings screen... (check ''Use extra-path info'')', 'Clean Permalinks!', 'clean_permalinks', '', 11, 'pingsdone', 0, 42, 'open', NULL, 'default', 3),
(16, NULL, 1, 1, NULL, '2006-08-03 01:13:49', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'In the <code>/blogs</code> folder as well as in <code>/blogs/admin</code> there are two files called [<code>sample.htaccess</code>]. You should try renaming those to [<code>.htaccess</code>].\r\n\r\nThis will optimize the way b2evolution is handled by the webserver (if you are using Apache). These files are not active by default because a few hosts would display an error right away when you try to use them. If this happens to you when you rename the files, just remove them and you''ll be fine.', 'Apache optimization...', 'apache_optimization', '', 11, 'pingsdone', 0, 81, 'open', NULL, 'default', 3),
(17, NULL, 1, 1, NULL, '2006-08-03 01:13:50', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'By default, b2evolution blogs are displayed using a default skin.\r\n\r\nReaders can choose a new skin by using the skin switcher integrated in most skins.\r\n\r\nYou can change the default skin used for any blog by editing the blog parameters in the admin interface. You can also force the use of the default skin for everyone.\r\n\r\nOtherwise, you can restrict available skins by deleting some of them from the /blogs/skins folder. You can also create new skins by duplicating, renaming and customizing any existing skin folder.\r\n\r\nTo start customizing a skin, open its ''<code>_main.php</code>'' file in an editor and read the comments in there. And, of course, read the manual on evoSkins!', 'About evoSkins...', 'about_evoskins', '', 11, 'pingsdone', 0, 116, 'open', NULL, 'default', 3),
(18, NULL, 1, 1, NULL, '2006-08-03 01:13:51', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'By default, all pre-installed blogs are displayed using a skin. (More on skins in another post.)\r\n\r\nThat means, blogs are accessed through ''<code>index.php</code>'', which loads default parameters from the database and then passes on the display job to a skin.\r\n\r\nAlternatively, if you don''t want to use the default DB parameters and want to, say, force a skin, a category or a specific linkblog, you can create a stub file like the provided ''<code>a_stub.php</code>'' and call your blog through this stub instead of index.php .\r\n\r\nFinally, if you need to do some very specific customizations to your blog, you may use plain templates instead of skins. In this case, call your blog through a full template, like the provided ''<code>a_noskin.php</code>''.\r\n\r\nYou will find more information in the stub/template files themselves. Open them in a text editor and read the comments in there.\r\n\r\nEither way, make sure you go to the blogs admin and set the correct access method for your blog. When using a stub or a template, you must also set its filename in the ''Stub name'' field. Otherwise, the permalinks will not function properly.', 'Skins, Stubs and Templates...', 'skins_stubs_and_templates', '', 11, 'pingsdone', 0, 192, 'open', NULL, 'default', 3),
(19, NULL, 1, 1, NULL, '2006-08-03 01:13:52', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'By default, b2evolution comes with 4 blogs, named ''Blog All'', ''Blog A'', ''Blog B'' and ''Linkblog''.\r\n\r\nSome of these blogs have a special role. Read about it on the corresponding page.\r\n\r\nYou can create additional blogs or delete unwanted blogs from the blogs admin.', 'Multiple Blogs, new blogs, old blogs...', 'multiple_blogs_new_blogs_old_blogs', '', 11, 'pingsdone', 0, 44, 'open', NULL, 'default', 3),
(20, NULL, 1, 1, NULL, '2006-08-03 01:13:53', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'This is page 1 of a multipage post.\r\n\r\nYou can see the other pages by clicking on the links below the text.\r\n\r\n<!--nextpage-->\r\n\r\nThis is page 2.\r\n\r\n<!--nextpage-->\r\n\r\nThis is page 3.\r\n\r\n<!--nextpage-->\r\n\r\nThis is page 4.\r\n\r\nIt is the last page.', 'This is a multipage post', 'this_is_a_multipage_post', '', 11, 'pingsdone', 0, 35, 'open', NULL, 'default', 3),
(21, NULL, 1, 1, NULL, '2006-08-03 01:13:54', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'This is an extended post with no teaser. This means that you won''t see this teaser any more when you click the "more" link.\r\n\r\n<!--more--><!--noteaser-->\r\n\r\nThis is the extended text. You only see it when you have clicked the "more" link.', 'Extended post with no teaser', 'extended_post_with_no_teaser', '', 11, 'pingsdone', 0, 40, 'open', NULL, 'default', 3),
(22, NULL, 1, 1, NULL, '2006-08-03 01:13:55', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'This is an extended post. This means you only see this small teaser by default and you must click on the link below to see more.\r\n\r\n<!--more-->\r\n\r\nThis is the extended text. You only see it when you have clicked the "more" link.', 'Extended post', 'extended_post', '', 11, 'pingsdone', 0, 42, 'open', NULL, 'default', 3),
(23, NULL, 1, 1, NULL, '2006-08-03 01:13:56', NULL, '1970-01-01 01:00:00', '1970-01-01 01:00:00', 'published', NULL, 1, 'en-EU', 'Blog B contains a few posts in the ''b2evolution Tips'' category.\r\n\r\nAll these entries are designed to help you so, as EdB would say: "<em>read them all before you start hacking away!</em>" ;)\r\n\r\nIf you wish, you can delete these posts one by one after you have read them. You could also change their status to ''deprecated'' in order to visually keep track of what you have already read.', 'Important information', 'important_information', '', 11, 'pingsdone', 0, 69, 'open', NULL, 'default', 3);

-- --------------------------------------------------------

--
-- Table structure for table `evo_poststatuses`
--

CREATE TABLE `evo_poststatuses` (
  `pst_ID` int(11) unsigned NOT NULL auto_increment,
  `pst_name` varchar(30) NOT NULL,
  PRIMARY KEY  (`pst_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `evo_posttypes`
--

INSERT INTO `evo_posttypes` (`ptyp_ID`, `ptyp_name`) VALUES (1, 'Post'),
(2, 'Link');

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
  `sess_data` text,
  PRIMARY KEY  (`sess_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `evo_settings`
--

INSERT INTO `evo_settings` (`set_name`, `set_value`) VALUES ('db_version', '9200'),
('default_locale', 'en-EU'),
('newusers_grp_ID', '4');

-- --------------------------------------------------------

--
-- Table structure for table `evo_subscriptions`
--

CREATE TABLE `evo_subscriptions` (
  `sub_coll_ID` int(11) unsigned NOT NULL,
  `sub_user_ID` int(11) unsigned NOT NULL,
  `sub_items` tinyint(1) NOT NULL,
  `sub_comments` tinyint(1) NOT NULL,
  PRIMARY KEY  (`sub_coll_ID`,`sub_user_ID`),
  KEY `FK_sub_user_ID` (`sub_user_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
  `user_firstname` varchar(50) default NULL,
  `user_lastname` varchar(50) default NULL,
  `user_nickname` varchar(50) default NULL,
  `user_icq` int(11) unsigned default NULL,
  `user_email` varchar(255) NOT NULL,
  `user_url` varchar(255) default NULL,
  `user_ip` varchar(15) default NULL,
  `user_domain` varchar(200) default NULL,
  `user_browser` varchar(200) default NULL,
  `dateYMDhour` datetime NOT NULL,
  `user_level` int(10) unsigned NOT NULL default '0',
  `user_aim` varchar(50) default NULL,
  `user_msn` varchar(100) default NULL,
  `user_yim` varchar(50) default NULL,
  `user_locale` varchar(20) NOT NULL default 'en-EU',
  `user_idmode` varchar(20) NOT NULL default 'login',
  `user_allow_msgform` tinyint(4) NOT NULL default '1',
  `user_notify` tinyint(1) NOT NULL default '1',
  `user_showonline` tinyint(1) NOT NULL default '1',
  `user_grp_ID` int(4) NOT NULL default '1',
  `user_validated` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`user_ID`),
  UNIQUE KEY `user_login` (`user_login`),
  KEY `user_grp_ID` (`user_grp_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `evo_users`
--

INSERT INTO `evo_users` (`user_ID`, `user_login`, `user_pass`, `user_firstname`, `user_lastname`, `user_nickname`, `user_icq`, `user_email`, `user_url`, `user_ip`, `user_domain`, `user_browser`, `dateYMDhour`, `user_level`, `user_aim`, `user_msn`, `user_yim`, `user_locale`, `user_idmode`, `user_allow_msgform`, `user_notify`, `user_showonline`, `user_grp_ID`, `user_validated`) VALUES (1, 'admin', '4c7a34d25eff9121c49658dbceadf694', NULL, NULL, 'admin', NULL, 'b2demo_stable@codeprobe.de', NULL, '127.0.0.1', 'localhost', NULL, '2006-08-03 01:13:32', 10, NULL, NULL, NULL, 'en-EU', 'login', 1, 1, 1, 1, 1),
(2, 'demouser', '4c7a34d25eff9121c49658dbceadf694', NULL, NULL, 'Mr. Demo', NULL, 'b2demo_stable@codeprobe.de', NULL, '127.0.0.1', 'localhost', NULL, '2006-08-03 01:13:33', 0, NULL, NULL, NULL, 'en-EU', 'login', 1, 1, 1, 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `evo_usersettings`
--

CREATE TABLE `evo_usersettings` (
  `uset_user_ID` int(11) unsigned NOT NULL,
  `uset_name` varchar(30) NOT NULL,
  `uset_value` varchar(255) default NULL,
  PRIMARY KEY  (`uset_user_ID`,`uset_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `evo_usersettings`
--


--
-- Constraints for dumped tables
--

--
-- Constraints for table `evo_blogusers`
--
ALTER TABLE `evo_blogusers`
  ADD CONSTRAINT `FK_bloguser_user_ID` FOREIGN KEY (`bloguser_user_ID`) REFERENCES `evo_users` (`user_ID`),
  ADD CONSTRAINT `FK_bloguser_blog_ID` FOREIGN KEY (`bloguser_blog_ID`) REFERENCES `evo_blogs` (`blog_ID`);

--
-- Constraints for table `evo_categories`
--
ALTER TABLE `evo_categories`
  ADD CONSTRAINT `FK_cat_blog_ID` FOREIGN KEY (`cat_blog_ID`) REFERENCES `evo_blogs` (`blog_ID`),
  ADD CONSTRAINT `FK_cat_parent_ID` FOREIGN KEY (`cat_parent_ID`) REFERENCES `evo_categories` (`cat_ID`);

--
-- Constraints for table `evo_comments`
--
ALTER TABLE `evo_comments`
  ADD CONSTRAINT `FK_comment_post_ID` FOREIGN KEY (`comment_post_ID`) REFERENCES `evo_posts` (`post_ID`);

--
-- Constraints for table `evo_links`
--
ALTER TABLE `evo_links`
  ADD CONSTRAINT `FK_link_itm_ID` FOREIGN KEY (`link_itm_ID`) REFERENCES `evo_posts` (`post_ID`),
  ADD CONSTRAINT `FK_link_creator_user_ID` FOREIGN KEY (`link_creator_user_ID`) REFERENCES `evo_users` (`user_ID`),
  ADD CONSTRAINT `FK_link_dest_itm_ID` FOREIGN KEY (`link_dest_itm_ID`) REFERENCES `evo_posts` (`post_ID`),
  ADD CONSTRAINT `FK_link_file_ID` FOREIGN KEY (`link_file_ID`) REFERENCES `evo_files` (`file_ID`),
  ADD CONSTRAINT `FK_link_lastedit_user_ID` FOREIGN KEY (`link_lastedit_user_ID`) REFERENCES `evo_users` (`user_ID`);

--
-- Constraints for table `evo_pluginevents`
--
ALTER TABLE `evo_pluginevents`
  ADD CONSTRAINT `FK_pevt_plug_ID` FOREIGN KEY (`pevt_plug_ID`) REFERENCES `evo_plugins` (`plug_ID`);

--
-- Constraints for table `evo_pluginsettings`
--
ALTER TABLE `evo_pluginsettings`
  ADD CONSTRAINT `FK_pset_plug_ID` FOREIGN KEY (`pset_plug_ID`) REFERENCES `evo_plugins` (`plug_ID`);

--
-- Constraints for table `evo_pluginusersettings`
--
ALTER TABLE `evo_pluginusersettings`
  ADD CONSTRAINT `FK_puset_plug_ID` FOREIGN KEY (`puset_plug_ID`) REFERENCES `evo_plugins` (`plug_ID`);

--
-- Constraints for table `evo_postcats`
--
ALTER TABLE `evo_postcats`
  ADD CONSTRAINT `FK_postcat_cat_ID` FOREIGN KEY (`postcat_cat_ID`) REFERENCES `evo_categories` (`cat_ID`),
  ADD CONSTRAINT `FK_postcat_post_ID` FOREIGN KEY (`postcat_post_ID`) REFERENCES `evo_posts` (`post_ID`);

--
-- Constraints for table `evo_posts`
--
ALTER TABLE `evo_posts`
  ADD CONSTRAINT `FK_post_assigned_user_ID` FOREIGN KEY (`post_assigned_user_ID`) REFERENCES `evo_users` (`user_ID`),
  ADD CONSTRAINT `FK_post_lastedit_user_ID` FOREIGN KEY (`post_lastedit_user_ID`) REFERENCES `evo_users` (`user_ID`),
  ADD CONSTRAINT `FK_post_creator_user_ID` FOREIGN KEY (`post_creator_user_ID`) REFERENCES `evo_users` (`user_ID`),
  ADD CONSTRAINT `FK_post_main_cat_ID` FOREIGN KEY (`post_main_cat_ID`) REFERENCES `evo_categories` (`cat_ID`),
  ADD CONSTRAINT `FK_post_parent_ID` FOREIGN KEY (`post_parent_ID`) REFERENCES `evo_posts` (`post_ID`),
  ADD CONSTRAINT `FK_post_pst_ID` FOREIGN KEY (`post_pst_ID`) REFERENCES `evo_poststatuses` (`pst_ID`),
  ADD CONSTRAINT `FK_post_ptyp_ID` FOREIGN KEY (`post_ptyp_ID`) REFERENCES `evo_posttypes` (`ptyp_ID`);

--
-- Constraints for table `evo_subscriptions`
--
ALTER TABLE `evo_subscriptions`
  ADD CONSTRAINT `FK_sub_user_ID` FOREIGN KEY (`sub_user_ID`) REFERENCES `evo_users` (`user_ID`),
  ADD CONSTRAINT `FK_sub_coll_ID` FOREIGN KEY (`sub_coll_ID`) REFERENCES `evo_blogs` (`blog_ID`);

--
-- Constraints for table `evo_users`
--
ALTER TABLE `evo_users`
  ADD CONSTRAINT `FK_user_grp_ID` FOREIGN KEY (`user_grp_ID`) REFERENCES `evo_groups` (`grp_ID`);

--
-- Constraints for table `evo_usersettings`
--
ALTER TABLE `evo_usersettings`
  ADD CONSTRAINT `FK_uset_user_ID` FOREIGN KEY (`uset_user_ID`) REFERENCES `evo_users` (`user_ID`);
