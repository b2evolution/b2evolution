-- phpMyAdmin SQL Dump
-- version 3.2.0.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 02, 2012 at 02:53 AM
-- Server version: 5.1.37
-- PHP Version: 5.3.0

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `4`
--

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}antispam`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}antispam` (
  `aspm_ID` bigint(11) NOT NULL AUTO_INCREMENT,
  `aspm_string` varchar(80) NOT NULL,
  `aspm_source` enum('local','reported','central') NOT NULL DEFAULT 'reported',
  PRIMARY KEY (`aspm_ID`),
  UNIQUE KEY `aspm_string` (`aspm_string`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=19 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}antispam`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}antispam` (`aspm_ID`, `aspm_string`, `aspm_source`) VALUES
(1, 'online-casino', 'reported'),
(2, 'penis-enlargement', 'reported'),
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
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}basedomains`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}basedomains` (
  `dom_ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `dom_name` varchar(250) NOT NULL DEFAULT '',
  `dom_status` enum('unknown','whitelist','blacklist') NOT NULL DEFAULT 'unknown',
  `dom_type` enum('unknown','normal','searcheng','aggregator') NOT NULL DEFAULT 'unknown',
  PRIMARY KEY (`dom_ID`),
  UNIQUE KEY `dom_name` (`dom_name`),
  KEY `dom_type` (`dom_type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}basedomains`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}basedomains` (`dom_ID`, `dom_name`, `dom_status`, `dom_type`) VALUES
(1, 'localhost', 'unknown', 'unknown');

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}bloggroups`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}bloggroups` (
  `bloggroup_blog_ID` int(11) unsigned NOT NULL DEFAULT '0',
  `bloggroup_group_ID` int(11) unsigned NOT NULL DEFAULT '0',
  `bloggroup_ismember` tinyint(4) NOT NULL DEFAULT '0',
  `bloggroup_perm_poststatuses` set('published','deprecated','protected','private','draft','redirected') NOT NULL DEFAULT '',
  `bloggroup_perm_edit` enum('no','own','lt','le','all','redirected') NOT NULL DEFAULT 'no',
  `bloggroup_perm_delpost` tinyint(4) NOT NULL DEFAULT '0',
  `bloggroup_perm_draft_cmts` tinyint(4) NOT NULL DEFAULT '0',
  `bloggroup_perm_publ_cmts` tinyint(4) NOT NULL DEFAULT '0',
  `bloggroup_perm_depr_cmts` tinyint(4) NOT NULL DEFAULT '0',
  `bloggroup_perm_cats` tinyint(4) NOT NULL DEFAULT '0',
  `bloggroup_perm_properties` tinyint(4) NOT NULL DEFAULT '0',
  `bloggroup_perm_admin` tinyint(4) NOT NULL DEFAULT '0',
  `bloggroup_perm_media_upload` tinyint(4) NOT NULL DEFAULT '0',
  `bloggroup_perm_media_browse` tinyint(4) NOT NULL DEFAULT '0',
  `bloggroup_perm_media_change` tinyint(4) NOT NULL DEFAULT '0',
  `bloggroup_perm_page` tinyint(4) NOT NULL DEFAULT '0',
  `bloggroup_perm_intro` tinyint(4) NOT NULL DEFAULT '0',
  `bloggroup_perm_podcast` tinyint(4) NOT NULL DEFAULT '0',
  `bloggroup_perm_sidebar` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`bloggroup_blog_ID`,`bloggroup_group_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}bloggroups`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}bloggroups` (`bloggroup_blog_ID`, `bloggroup_group_ID`, `bloggroup_ismember`, `bloggroup_perm_poststatuses`, `bloggroup_perm_edit`, `bloggroup_perm_delpost`, `bloggroup_perm_draft_cmts`, `bloggroup_perm_publ_cmts`, `bloggroup_perm_depr_cmts`, `bloggroup_perm_cats`, `bloggroup_perm_properties`, `bloggroup_perm_admin`, `bloggroup_perm_media_upload`, `bloggroup_perm_media_browse`, `bloggroup_perm_media_change`, `bloggroup_perm_page`, `bloggroup_perm_intro`, `bloggroup_perm_podcast`, `bloggroup_perm_sidebar`) VALUES
(1, 1, 1, 'published,deprecated,protected,private,draft', 'no', 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 0, 0, 0, 0),
(1, 2, 1, 'published,deprecated,protected,private,draft', 'no', 1, 1, 1, 1, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0),
(1, 3, 1, 'published,deprecated,protected,private,draft', 'no', 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0),
(1, 4, 1, '', 'no', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 1, 1, 'published,deprecated,protected,private,draft', 'no', 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 0, 0, 0, 0),
(2, 2, 1, 'published,deprecated,protected,private,draft', 'no', 1, 1, 1, 1, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0),
(2, 3, 1, 'published,deprecated,protected,private,draft', 'no', 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0),
(2, 4, 1, '', 'no', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3, 1, 1, 'published,deprecated,protected,private,draft', 'no', 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 0, 0, 0, 0),
(3, 2, 1, 'published,deprecated,protected,private,draft', 'no', 1, 1, 1, 1, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0),
(3, 3, 1, 'published,deprecated,protected,private,draft', 'no', 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0),
(3, 4, 1, '', 'no', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}blogs`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}blogs` (
  `blog_ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `blog_shortname` varchar(255) DEFAULT '',
  `blog_name` varchar(255) NOT NULL DEFAULT '',
  `blog_owner_user_ID` int(11) unsigned NOT NULL DEFAULT '1',
  `blog_advanced_perms` tinyint(1) NOT NULL DEFAULT '0',
  `blog_tagline` varchar(250) DEFAULT '',
  `blog_description` varchar(250) DEFAULT '',
  `blog_longdesc` text,
  `blog_locale` varchar(20) NOT NULL DEFAULT 'en-EU',
  `blog_access_type` varchar(10) NOT NULL DEFAULT 'extrapath',
  `blog_siteurl` varchar(120) NOT NULL DEFAULT '',
  `blog_urlname` varchar(255) NOT NULL DEFAULT 'urlname',
  `blog_notes` text,
  `blog_keywords` tinytext,
  `blog_allowcomments` varchar(20) NOT NULL DEFAULT 'post_by_post',
  `blog_allowtrackbacks` tinyint(1) NOT NULL DEFAULT '0',
  `blog_allowblogcss` tinyint(1) NOT NULL DEFAULT '1',
  `blog_allowusercss` tinyint(1) NOT NULL DEFAULT '1',
  `blog_skin_ID` int(10) unsigned NOT NULL DEFAULT '1',
  `blog_in_bloglist` tinyint(1) NOT NULL DEFAULT '1',
  `blog_links_blog_ID` int(11) DEFAULT NULL,
  `blog_media_location` enum('default','subdir','custom','none') NOT NULL DEFAULT 'default',
  `blog_media_subdir` varchar(255) DEFAULT NULL,
  `blog_media_fullpath` varchar(255) DEFAULT NULL,
  `blog_media_url` varchar(255) DEFAULT NULL,
  `blog_UID` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`blog_ID`),
  UNIQUE KEY `blog_urlname` (`blog_urlname`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}blogs`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}blogs` (`blog_ID`, `blog_shortname`, `blog_name`, `blog_owner_user_ID`, `blog_advanced_perms`, `blog_tagline`, `blog_description`, `blog_longdesc`, `blog_locale`, `blog_access_type`, `blog_siteurl`, `blog_urlname`, `blog_notes`, `blog_keywords`, `blog_allowcomments`, `blog_allowtrackbacks`, `blog_allowblogcss`, `blog_allowusercss`, `blog_skin_ID`, `blog_in_bloglist`, `blog_links_blog_ID`, `blog_media_location`, `blog_media_subdir`, `blog_media_fullpath`, `blog_media_url`, `blog_UID`) VALUES
(1, 'Blog A', 'Blog A Title', 1, 0, 'Tagline for Blog A', '', 'This is the long description for the blog named ''Blog A''. ', 'en-US-utf8', 'relative', 'blog1.php', 'a', NULL, NULL, 'post_by_post', 0, 1, 1, 1, 1, NULL, 'default', NULL, NULL, NULL, NULL),
(2, 'Blog B', 'Blog B Title', 1, 0, 'Tagline for Blog B', '', 'This is the long description for the blog named ''Blog B''. ', 'en-US-utf8', 'relative', 'blog2.php', 'b', NULL, NULL, 'post_by_post', 0, 1, 1, 2, 1, NULL, 'default', NULL, NULL, NULL, NULL),
(3, 'Linkblog', 'Linkblog', 1, 0, 'Some interesting links...', '', 'This is the long description for the blog named ''Linkblog''. <br />\n<br />\n<strong>The main purpose for this blog is to be included as a side item to other blogs where it will display your favorite/related links.</strong>', 'en-US-utf8', 'relative', 'blog3.php', 'links', NULL, NULL, 'post_by_post', 0, 1, 1, 3, 1, NULL, 'default', NULL, NULL, NULL, NULL),
(4, 'Photoblog', 'Photoblog', 1, 0, 'This blog shows photos...', '', 'This is the long description for the blog named ''Photoblog''. <br />\n<br />\n<strong>This is a photoblog, optimized for displaying photos.</strong>', 'en-US-utf8', 'relative', 'blog4.php', 'photos', NULL, NULL, 'post_by_post', 0, 1, 1, 4, 1, NULL, 'default', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}blogusers`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}blogusers` (
  `bloguser_blog_ID` int(11) unsigned NOT NULL DEFAULT '0',
  `bloguser_user_ID` int(11) unsigned NOT NULL DEFAULT '0',
  `bloguser_ismember` tinyint(4) NOT NULL DEFAULT '0',
  `bloguser_perm_poststatuses` set('published','deprecated','protected','private','draft','redirected') NOT NULL DEFAULT '',
  `bloguser_perm_edit` enum('no','own','lt','le','all','redirected') NOT NULL DEFAULT 'no',
  `bloguser_perm_delpost` tinyint(4) NOT NULL DEFAULT '0',
  `bloguser_perm_draft_cmts` tinyint(4) NOT NULL DEFAULT '0',
  `bloguser_perm_publ_cmts` tinyint(4) NOT NULL DEFAULT '0',
  `bloguser_perm_depr_cmts` tinyint(4) NOT NULL DEFAULT '0',
  `bloguser_perm_cats` tinyint(4) NOT NULL DEFAULT '0',
  `bloguser_perm_properties` tinyint(4) NOT NULL DEFAULT '0',
  `bloguser_perm_admin` tinyint(4) NOT NULL DEFAULT '0',
  `bloguser_perm_media_upload` tinyint(4) NOT NULL DEFAULT '0',
  `bloguser_perm_media_browse` tinyint(4) NOT NULL DEFAULT '0',
  `bloguser_perm_media_change` tinyint(4) NOT NULL DEFAULT '0',
  `bloguser_perm_page` tinyint(4) NOT NULL DEFAULT '0',
  `bloguser_perm_intro` tinyint(4) NOT NULL DEFAULT '0',
  `bloguser_perm_podcast` tinyint(4) NOT NULL DEFAULT '0',
  `bloguser_perm_sidebar` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`bloguser_blog_ID`,`bloguser_user_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}blogusers`
--


-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}categories`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}categories` (
  `cat_ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cat_parent_ID` int(10) unsigned DEFAULT NULL,
  `cat_name` varchar(255) NOT NULL,
  `cat_urlname` varchar(255) NOT NULL,
  `cat_blog_ID` int(10) unsigned NOT NULL DEFAULT '2',
  `cat_description` varchar(255) DEFAULT NULL,
  `cat_order` int(11) DEFAULT NULL,
  PRIMARY KEY (`cat_ID`),
  UNIQUE KEY `cat_urlname` (`cat_urlname`),
  KEY `cat_blog_ID` (`cat_blog_ID`),
  KEY `cat_parent_ID` (`cat_parent_ID`),
  KEY `cat_order` (`cat_order`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}categories`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}categories` (`cat_ID`, `cat_parent_ID`, `cat_name`, `cat_urlname`, `cat_blog_ID`, `cat_description`, `cat_order`) VALUES
(1, NULL, 'Welcome', 'welcome', 1, NULL, NULL),
(2, NULL, 'News', 'news', 1, NULL, NULL),
(3, NULL, 'Background', 'background', 1, NULL, NULL),
(4, NULL, 'Fun', 'fun', 1, NULL, NULL),
(5, 4, 'In real life', 'in-real-life', 1, NULL, NULL),
(6, 4, 'On the web', 'on-the-web', 1, NULL, NULL),
(7, 5, 'Sports', 'sports', 1, NULL, NULL),
(8, 5, 'Movies', 'movies', 1, NULL, NULL),
(9, 5, 'Music', 'music', 1, NULL, NULL),
(10, NULL, 'Announcements', 'announcements', 2, NULL, NULL),
(11, NULL, 'b2evolution Tips', 'b2evolution-tips', 2, NULL, NULL),
(12, NULL, 'b2evolution', 'b2evolution', 3, NULL, NULL),
(13, NULL, 'contributors', 'contributors', 3, NULL, NULL),
(14, NULL, 'Monument Valley', 'monument-valley', 4, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}coll_settings`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}coll_settings` (
  `cset_coll_ID` int(11) unsigned NOT NULL,
  `cset_name` varchar(30) NOT NULL,
  `cset_value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`cset_coll_ID`,`cset_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}coll_settings`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}coll_settings` (`cset_coll_ID`, `cset_name`, `cset_value`) VALUES
(4, 'archive_mode', 'postbypost'),
(4, 'posts_per_page', '1');

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}comments`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}comments` (
  `comment_ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `comment_post_ID` int(11) unsigned NOT NULL DEFAULT '0',
  `comment_type` enum('comment','linkback','trackback','pingback') NOT NULL DEFAULT 'comment',
  `comment_status` enum('published','deprecated','protected','private','draft','redirected') NOT NULL DEFAULT 'published',
  `comment_author_ID` int(10) unsigned DEFAULT NULL,
  `comment_author` varchar(100) DEFAULT NULL,
  `comment_author_email` varchar(255) DEFAULT NULL,
  `comment_author_url` varchar(255) DEFAULT NULL,
  `comment_author_IP` varchar(23) NOT NULL DEFAULT '',
  `comment_date` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `comment_content` text NOT NULL,
  `comment_rating` tinyint(1) DEFAULT NULL,
  `comment_featured` tinyint(1) NOT NULL DEFAULT '0',
  `comment_nofollow` tinyint(1) NOT NULL DEFAULT '1',
  `comment_karma` int(11) NOT NULL DEFAULT '0',
  `comment_spam_karma` tinyint(4) DEFAULT NULL,
  `comment_allow_msgform` tinyint(4) NOT NULL DEFAULT '0',
  `comment_secret` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`comment_ID`),
  KEY `comment_post_ID` (`comment_post_ID`),
  KEY `comment_date` (`comment_date`),
  KEY `comment_type` (`comment_type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}comments`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}comments` (`comment_ID`, `comment_post_ID`, `comment_type`, `comment_status`, `comment_author_ID`, `comment_author`, `comment_author_email`, `comment_author_url`, `comment_author_IP`, `comment_date`, `comment_content`, `comment_rating`, `comment_featured`, `comment_nofollow`, `comment_karma`, `comment_spam_karma`, `comment_allow_msgform`, `comment_secret`) VALUES
(1, 1, 'comment', 'published', NULL, 'miss b2', 'missb2@example.com', 'http://example.com', '127.0.0.1', '2012-04-02 02:52:48', 'Hi, this is a comment.<br />To delete a comment, just log in, and view the posts'' comments, there you will have the option to edit or delete them.', NULL, 0, 1, 0, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}country`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}country` (
  `ctry_ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ctry_code` char(2) NOT NULL,
  `ctry_name` varchar(40) NOT NULL,
  `ctry_curr_ID` int(10) unsigned DEFAULT NULL,
  `ctry_enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ctry_ID`),
  UNIQUE KEY `ctry_code` (`ctry_code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=248 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}country`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}country` (`ctry_ID`, `ctry_code`, `ctry_name`, `ctry_curr_ID`, `ctry_enabled`) VALUES
(1, 'af', 'Afghanistan', 1, 1),
(2, 'ax', 'Aland Islands', 2, 1),
(3, 'al', 'Albania', 3, 1),
(4, 'dz', 'Algeria', 4, 1),
(5, 'as', 'American Samoa', 5, 1),
(6, 'ad', 'Andorra', 2, 1),
(7, 'ao', 'Angola', 6, 1),
(8, 'ai', 'Anguilla', 7, 1),
(9, 'aq', 'Antarctica', NULL, 1),
(10, 'ag', 'Antigua And Barbuda', 7, 1),
(11, 'ar', 'Argentina', 8, 1),
(12, 'am', 'Armenia', 9, 1),
(13, 'aw', 'Aruba', 10, 1),
(14, 'au', 'Australia', 11, 1),
(15, 'at', 'Austria', 2, 1),
(16, 'az', 'Azerbaijan', 12, 1),
(17, 'bs', 'Bahamas', 13, 1),
(18, 'bh', 'Bahrain', 14, 1),
(19, 'bd', 'Bangladesh', 15, 1),
(20, 'bb', 'Barbados', 16, 1),
(21, 'by', 'Belarus', 17, 1),
(22, 'be', 'Belgium', 2, 1),
(23, 'bz', 'Belize', 18, 1),
(24, 'bj', 'Benin', 19, 1),
(25, 'bm', 'Bermuda', 20, 1),
(26, 'bt', 'Bhutan', 62, 1),
(27, 'bo', 'Bolivia', NULL, 1),
(28, 'ba', 'Bosnia And Herzegovina', 21, 1),
(29, 'bw', 'Botswana', 22, 1),
(30, 'bv', 'Bouvet Island', 23, 1),
(31, 'br', 'Brazil', 24, 1),
(32, 'io', 'British Indian Ocean Territory', 5, 1),
(33, 'bn', 'Brunei Darussalam', 25, 1),
(34, 'bg', 'Bulgaria', 26, 1),
(35, 'bf', 'Burkina Faso', 19, 1),
(36, 'bi', 'Burundi', 27, 1),
(37, 'kh', 'Cambodia', 28, 1),
(38, 'cm', 'Cameroon', 29, 1),
(39, 'ca', 'Canada', 30, 1),
(40, 'cv', 'Cape Verde', 31, 1),
(41, 'ky', 'Cayman Islands', 32, 1),
(42, 'cf', 'Central African Republic', 29, 1),
(43, 'td', 'Chad', 29, 1),
(44, 'cl', 'Chile', 159, 1),
(45, 'cn', 'China', 33, 1),
(46, 'cx', 'Christmas Island', 11, 1),
(47, 'cc', 'Cocos Islands', 11, 1),
(48, 'co', 'Colombia', 156, 1),
(49, 'km', 'Comoros', 34, 1),
(50, 'cg', 'Congo', 29, 1),
(51, 'cd', 'Congo Republic', 35, 1),
(52, 'ck', 'Cook Islands', 36, 1),
(53, 'cr', 'Costa Rica', 37, 1),
(54, 'ci', 'Cote Divoire', 19, 1),
(55, 'hr', 'Croatia', 38, 1),
(56, 'cu', 'Cuba', 157, 1),
(57, 'cy', 'Cyprus', 2, 1),
(58, 'cz', 'Czech Republic', 39, 1),
(59, 'dk', 'Denmark', 40, 1),
(60, 'dj', 'Djibouti', 41, 1),
(61, 'dm', 'Dominica', 7, 1),
(62, 'do', 'Dominican Republic', 42, 1),
(63, 'ec', 'Ecuador', 5, 1),
(64, 'eg', 'Egypt', 43, 1),
(65, 'sv', 'El Salvador', 158, 1),
(66, 'gq', 'Equatorial Guinea', 29, 1),
(67, 'er', 'Eritrea', 44, 1),
(68, 'ee', 'Estonia', 45, 1),
(69, 'et', 'Ethiopia', 46, 1),
(70, 'fk', 'Falkland Islands (Malvinas)', 47, 1),
(71, 'fo', 'Faroe Islands', 40, 1),
(72, 'fj', 'Fiji', 48, 1),
(73, 'fi', 'Finland', 2, 1),
(74, 'fr', 'France', 2, 1),
(75, 'gf', 'French Guiana', 2, 1),
(76, 'pf', 'French Polynesia', 49, 1),
(77, 'tf', 'French Southern Territories', 2, 1),
(78, 'ga', 'Gabon', 29, 1),
(79, 'gm', 'Gambia', 50, 1),
(80, 'ge', 'Georgia', 51, 1),
(81, 'de', 'Germany', 2, 1),
(82, 'gh', 'Ghana', 52, 1),
(83, 'gi', 'Gibraltar', 53, 1),
(84, 'gr', 'Greece', 2, 1),
(85, 'gl', 'Greenland', 40, 1),
(86, 'gd', 'Grenada', 7, 1),
(87, 'gp', 'Guadeloupe', 2, 1),
(88, 'gu', 'Guam', 5, 1),
(89, 'gt', 'Guatemala', 54, 1),
(90, 'gg', 'Guernsey', 55, 1),
(91, 'gn', 'Guinea', 56, 1),
(92, 'gw', 'Guinea-bissau', 19, 1),
(93, 'gy', 'Guyana', 57, 1),
(94, 'ht', 'Haiti', 160, 1),
(95, 'hm', 'Heard Island And Mcdonald Islands', 11, 1),
(96, 'va', 'Holy See (vatican City State)', 2, 1),
(97, 'hn', 'Honduras', 58, 1),
(98, 'hk', 'Hong Kong', 59, 1),
(99, 'hu', 'Hungary', 60, 1),
(100, 'is', 'Iceland', 61, 1),
(101, 'in', 'India', 62, 1),
(102, 'id', 'Indonesia', 63, 1),
(103, 'ir', 'Iran', 64, 1),
(104, 'iq', 'Iraq', 65, 1),
(105, 'ie', 'Ireland', 2, 1),
(106, 'im', 'Isle Of Man', NULL, 1),
(107, 'il', 'Israel', 66, 1),
(108, 'it', 'Italy', 2, 1),
(109, 'jm', 'Jamaica', 67, 1),
(110, 'jp', 'Japan', 68, 1),
(111, 'je', 'Jersey', 55, 1),
(112, 'jo', 'Jordan', 69, 1),
(113, 'kz', 'Kazakhstan', 70, 1),
(114, 'ke', 'Kenya', 71, 1),
(115, 'ki', 'Kiribati', 11, 1),
(116, 'kp', 'Korea', 72, 1),
(117, 'kr', 'Korea', 73, 1),
(118, 'kw', 'Kuwait', 74, 1),
(119, 'kg', 'Kyrgyzstan', 75, 1),
(120, 'la', 'Lao', 76, 1),
(121, 'lv', 'Latvia', 77, 1),
(122, 'lb', 'Lebanon', 78, 1),
(123, 'ls', 'Lesotho', 121, 1),
(124, 'lr', 'Liberia', 79, 1),
(125, 'ly', 'Libyan Arab Jamahiriya', 80, 1),
(126, 'li', 'Liechtenstein', 81, 1),
(127, 'lt', 'Lithuania', 82, 1),
(128, 'lu', 'Luxembourg', 2, 1),
(129, 'mo', 'Macao', 83, 1),
(130, 'mk', 'Macedonia', 84, 1),
(131, 'mg', 'Madagascar', 85, 1),
(132, 'mw', 'Malawi', 86, 1),
(133, 'my', 'Malaysia', 87, 1),
(134, 'mv', 'Maldives', 88, 1),
(135, 'ml', 'Mali', 19, 1),
(136, 'mt', 'Malta', 2, 1),
(137, 'mh', 'Marshall Islands', 5, 1),
(138, 'mq', 'Martinique', 2, 1),
(139, 'mr', 'Mauritania', 89, 1),
(140, 'mu', 'Mauritius', 90, 1),
(141, 'yt', 'Mayotte', 2, 1),
(142, 'mx', 'Mexico', 161, 1),
(143, 'fm', 'Micronesia', 2, 1),
(144, 'md', 'Moldova', 91, 1),
(145, 'mc', 'Monaco', 2, 1),
(146, 'mn', 'Mongolia', 92, 1),
(147, 'me', 'Montenegro', 2, 1),
(148, 'ms', 'Montserrat', 7, 1),
(149, 'ma', 'Morocco', 93, 1),
(150, 'mz', 'Mozambique', 94, 1),
(151, 'mm', 'Myanmar', 95, 1),
(152, 'na', 'Namibia', 121, 1),
(153, 'nr', 'Nauru', 11, 1),
(154, 'np', 'Nepal', 96, 1),
(155, 'nl', 'Netherlands', 2, 1),
(156, 'an', 'Netherlands Antilles', 97, 1),
(157, 'nc', 'New Caledonia', 49, 1),
(158, 'nz', 'New Zealand', 36, 1),
(159, 'ni', 'Nicaragua', 98, 1),
(160, 'ne', 'Niger', 19, 1),
(161, 'ng', 'Nigeria', 99, 1),
(162, 'nu', 'Niue', 36, 1),
(163, 'nf', 'Norfolk Island', 11, 1),
(164, 'mp', 'Northern Mariana Islands', 5, 1),
(165, 'no', 'Norway', 23, 1),
(166, 'om', 'Oman', 100, 1),
(167, 'pk', 'Pakistan', 101, 1),
(168, 'pw', 'Palau', 5, 1),
(169, 'ps', 'Palestinian Territory', NULL, 1),
(170, 'pa', 'Panama', 162, 1),
(171, 'pg', 'Papua New Guinea', 102, 1),
(172, 'py', 'Paraguay', 103, 1),
(173, 'pe', 'Peru', 104, 1),
(174, 'ph', 'Philippines', 105, 1),
(175, 'pn', 'Pitcairn', 36, 1),
(176, 'pl', 'Poland', 106, 1),
(177, 'pt', 'Portugal', 2, 1),
(178, 'pr', 'Puerto Rico', 5, 1),
(179, 'qa', 'Qatar', 107, 1),
(180, 're', 'Reunion', 2, 1),
(181, 'ro', 'Romania', 108, 1),
(182, 'ru', 'Russian Federation', 109, 1),
(183, 'rw', 'Rwanda', 110, 1),
(184, 'bl', 'Saint Barthelemy', 2, 1),
(185, 'sh', 'Saint Helena', 111, 1),
(186, 'kn', 'Saint Kitts And Nevis', 7, 1),
(187, 'lc', 'Saint Lucia', 7, 1),
(188, 'mf', 'Saint Martin', 2, 1),
(189, 'pm', 'Saint Pierre And Miquelon', 2, 1),
(190, 'vc', 'Saint Vincent And The Grenadines', 7, 1),
(191, 'ws', 'Samoa', 112, 1),
(192, 'sm', 'San Marino', 2, 1),
(193, 'st', 'Sao Tome And Principe', 113, 1),
(194, 'sa', 'Saudi Arabia', 114, 1),
(195, 'sn', 'Senegal', 19, 1),
(196, 'rs', 'Serbia', 115, 1),
(197, 'sc', 'Seychelles', 116, 1),
(198, 'sl', 'Sierra Leone', 117, 1),
(199, 'sg', 'Singapore', 118, 1),
(200, 'sk', 'Slovakia', 2, 1),
(201, 'si', 'Slovenia', 2, 1),
(202, 'sb', 'Solomon Islands', 119, 1),
(203, 'so', 'Somalia', 120, 1),
(204, 'za', 'South Africa', 121, 1),
(205, 'gs', 'South Georgia', NULL, 1),
(206, 'es', 'Spain', 2, 1),
(207, 'lk', 'Sri Lanka', 122, 1),
(208, 'sd', 'Sudan', 123, 1),
(209, 'sr', 'Suriname', 124, 1),
(210, 'sj', 'Svalbard And Jan Mayen', 23, 1),
(211, 'sz', 'Swaziland', 125, 1),
(212, 'se', 'Sweden', 126, 1),
(213, 'ch', 'Switzerland', 81, 1),
(214, 'sy', 'Syrian Arab Republic', 127, 1),
(215, 'tw', 'Taiwan, Province Of China', 128, 1),
(216, 'tj', 'Tajikistan', 129, 1),
(217, 'tz', 'Tanzania', 130, 1),
(218, 'th', 'Thailand', 131, 1),
(219, 'tl', 'Timor-leste', 5, 1),
(220, 'tg', 'Togo', 19, 1),
(221, 'tk', 'Tokelau', 36, 1),
(222, 'to', 'Tonga', 132, 1),
(223, 'tt', 'Trinidad And Tobago', 133, 1),
(224, 'tn', 'Tunisia', 134, 1),
(225, 'tr', 'Turkey', 135, 1),
(226, 'tm', 'Turkmenistan', 136, 1),
(227, 'tc', 'Turks And Caicos Islands', 5, 1),
(228, 'tv', 'Tuvalu', 11, 1),
(229, 'ug', 'Uganda', 137, 1),
(230, 'ua', 'Ukraine', 138, 1),
(231, 'ae', 'United Arab Emirates', 139, 1),
(232, 'gb', 'United Kingdom', 55, 1),
(233, 'us', 'United States', 5, 1),
(234, 'um', 'United States Minor Outlying Islands', 5, 1),
(235, 'uy', 'Uruguay', 163, 1),
(236, 'uz', 'Uzbekistan', 140, 1),
(237, 'vu', 'Vanuatu', 141, 1),
(239, 've', 'Venezuela', 142, 1),
(240, 'vn', 'Viet Nam', 143, 1),
(241, 'vg', 'Virgin Islands, British', 5, 1),
(242, 'vi', 'Virgin Islands, U.s.', 5, 1),
(243, 'wf', 'Wallis And Futuna', 49, 1),
(244, 'eh', 'Western Sahara', 93, 1),
(245, 'ye', 'Yemen', 144, 1),
(246, 'zm', 'Zambia', 145, 1),
(247, 'zw', 'Zimbabwe', 146, 1);

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}cron__log`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}cron__log` (
  `clog_ctsk_ID` int(10) unsigned NOT NULL,
  `clog_realstart_datetime` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `clog_realstop_datetime` datetime DEFAULT NULL,
  `clog_status` enum('started','finished','error','timeout') NOT NULL DEFAULT 'started',
  `clog_messages` text,
  PRIMARY KEY (`clog_ctsk_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}cron__log`
--


-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}cron__task`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}cron__task` (
  `ctsk_ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ctsk_start_datetime` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `ctsk_repeat_after` int(10) unsigned DEFAULT NULL,
  `ctsk_name` varchar(50) NOT NULL,
  `ctsk_controller` varchar(50) NOT NULL,
  `ctsk_params` text,
  PRIMARY KEY (`ctsk_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}cron__task`
--


-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}currency`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}currency` (
  `curr_ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `curr_code` char(3) NOT NULL,
  `curr_shortcut` varchar(30) NOT NULL,
  `curr_name` varchar(40) NOT NULL,
  `curr_enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`curr_ID`),
  UNIQUE KEY `curr_code` (`curr_code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=164 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}currency`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}currency` (`curr_ID`, `curr_code`, `curr_shortcut`, `curr_name`, `curr_enabled`) VALUES
(1, 'AFN', '&#x60b;', 'Afghani', 1),
(2, 'EUR', '&euro;', 'Euro', 1),
(3, 'ALL', 'Lek', 'Lek', 1),
(4, 'DZD', 'DZD', 'Algerian Dinar', 1),
(5, 'USD', '$', 'US Dollar', 1),
(6, 'AOA', 'AOA', 'Kwanza', 1),
(7, 'XCD', '$', 'East Caribbean Dollar', 1),
(8, 'ARS', '$', 'Argentine Peso', 1),
(9, 'AMD', 'AMD', 'Armenian Dram', 1),
(10, 'AWG', '&fnof;', 'Aruban Guilder', 1),
(11, 'AUD', '$', 'Australian Dollar', 1),
(12, 'AZN', '&#x43c;&#x430;&#x43d;', 'Azerbaijanian Manat', 1),
(13, 'BSD', '$', 'Bahamian Dollar', 1),
(14, 'BHD', 'BHD', 'Bahraini Dinar', 1),
(15, 'BDT', 'BDT', 'Taka', 1),
(16, 'BBD', '$', 'Barbados Dollar', 1),
(17, 'BYR', 'p.', 'Belarussian Ruble', 1),
(18, 'BZD', 'BZ$', 'Belize Dollar', 1),
(19, 'XOF', 'XOF', 'CFA Franc BCEAO', 1),
(20, 'BMD', '$', 'Bermudian Dollar', 1),
(21, 'BAM', 'KM', 'Convertible Marks', 1),
(22, 'BWP', 'P', 'Pula', 1),
(23, 'NOK', 'kr', 'Norwegian Krone', 1),
(24, 'BRL', 'R$', 'Brazilian Real', 1),
(25, 'BND', '$', 'Brunei Dollar', 1),
(26, 'BGN', '&#x43b;&#x432;', 'Bulgarian Lev', 1),
(27, 'BIF', 'BIF', 'Burundi Franc', 1),
(28, 'KHR', '&#x17db;', 'Riel', 1),
(29, 'XAF', 'XAF', 'CFA Franc BEAC', 1),
(30, 'CAD', '$', 'Canadian Dollar', 1),
(31, 'CVE', 'CVE', 'Cape Verde Escudo', 1),
(32, 'KYD', '$', 'Cayman Islands Dollar', 1),
(33, 'CNY', '&yen;', 'Yuan Renminbi', 1),
(34, 'KMF', 'KMF', 'Comoro Franc', 1),
(35, 'CDF', 'CDF', 'Congolese Franc', 1),
(36, 'NZD', '$', 'New Zealand Dollar', 1),
(37, 'CRC', '&#x20a1;', 'Costa Rican Colon', 1),
(38, 'HRK', 'kn', 'Croatian Kuna', 1),
(39, 'CZK', 'K&#x10d;', 'Czech Koruna', 1),
(40, 'DKK', 'kr', 'Danish Krone', 1),
(41, 'DJF', 'DJF', 'Djibouti Franc', 1),
(42, 'DOP', 'RD$', 'Dominican Peso', 1),
(43, 'EGP', '&pound;', 'Egyptian Pound', 1),
(44, 'ERN', 'ERN', 'Nakfa', 1),
(45, 'EEK', 'EEK', 'Kroon', 1),
(46, 'ETB', 'ETB', 'Ethiopian Birr', 1),
(47, 'FKP', '&pound;', 'Falkland Islands Pound', 1),
(48, 'FJD', '$', 'Fiji Dollar', 1),
(49, 'XPF', 'XPF', 'CFP Franc', 1),
(50, 'GMD', 'GMD', 'Dalasi', 1),
(51, 'GEL', 'GEL', 'Lari', 1),
(52, 'GHS', 'GHS', 'Cedi', 1),
(53, 'GIP', '&pound;', 'Gibraltar Pound', 1),
(54, 'GTQ', 'Q', 'Quetzal', 1),
(55, 'GBP', '&pound;', 'Pound Sterling', 1),
(56, 'GNF', 'GNF', 'Guinea Franc', 1),
(57, 'GYD', '$', 'Guyana Dollar', 1),
(58, 'HNL', 'L', 'Lempira', 1),
(59, 'HKD', '$', 'Hong Kong Dollar', 1),
(60, 'HUF', 'Ft', 'Forint', 1),
(61, 'ISK', 'kr', 'Iceland Krona', 1),
(62, 'INR', 'Rs', 'Indian Rupee', 1),
(63, 'IDR', 'Rp', 'Rupiah', 1),
(64, 'IRR', '&#xfdfc;', 'Iranian Rial', 1),
(65, 'IQD', 'IQD', 'Iraqi Dinar', 1),
(66, 'ILS', '&#x20aa;', 'New Israeli Sheqel', 1),
(67, 'JMD', 'J$', 'Jamaican Dollar', 1),
(68, 'JPY', '&yen;', 'Yen', 1),
(69, 'JOD', 'JOD', 'Jordanian Dinar', 1),
(70, 'KZT', '&#x43b;&#x432;', 'Tenge', 1),
(71, 'KES', 'KES', 'Kenyan Shilling', 1),
(72, 'KPW', '&#x20a9;', 'North Korean Won', 1),
(73, 'KRW', '&#x20a9;', 'Won', 1),
(74, 'KWD', 'KWD', 'Kuwaiti Dinar', 1),
(75, 'KGS', '&#x43b;&#x432;', 'Som', 1),
(76, 'LAK', '&#x20ad;', 'Kip', 1),
(77, 'LVL', 'Ls', 'Latvian Lats', 1),
(78, 'LBP', '&pound;', 'Lebanese Pound', 1),
(79, 'LRD', '$', 'Liberian Dollar', 1),
(80, 'LYD', 'LYD', 'Libyan Dinar', 1),
(81, 'CHF', 'CHF', 'Swiss Franc', 1),
(82, 'LTL', 'Lt', 'Lithuanian Litas', 1),
(83, 'MOP', 'MOP', 'Pataca', 1),
(84, 'MKD', '&#x434;&#x435;&#x43d;', 'Denar', 1),
(85, 'MGA', 'MGA', 'Malagasy Ariary', 1),
(86, 'MWK', 'MWK', 'Kwacha', 1),
(87, 'MYR', 'RM', 'Malaysian Ringgit', 1),
(88, 'MVR', 'MVR', 'Rufiyaa', 1),
(89, 'MRO', 'MRO', 'Ouguiya', 1),
(90, 'MUR', 'Rs', 'Mauritius Rupee', 1),
(91, 'MDL', 'MDL', 'Moldovan Leu', 1),
(92, 'MNT', '&#x20ae;', 'Tugrik', 1),
(93, 'MAD', 'MAD', 'Moroccan Dirham', 1),
(94, 'MZN', 'MT', 'Metical', 1),
(95, 'MMK', 'MMK', 'Kyat', 1),
(96, 'NPR', 'Rs', 'Nepalese Rupee', 1),
(97, 'ANG', '&fnof;', 'Netherlands Antillian Guilder', 1),
(98, 'NIO', 'C$', 'Cordoba Oro', 1),
(99, 'NGN', '&#x20a6;', 'Naira', 1),
(100, 'OMR', '&#xfdfc;', 'Rial Omani', 1),
(101, 'PKR', 'Rs', 'Pakistan Rupee', 1),
(102, 'PGK', 'PGK', 'Kina', 1),
(103, 'PYG', 'Gs', 'Guarani', 1),
(104, 'PEN', 'S/.', 'Nuevo Sol', 1),
(105, 'PHP', 'Php', 'Philippine Peso', 1),
(106, 'PLN', 'z&#x142;', 'Zloty', 1),
(107, 'QAR', '&#xfdfc;', 'Qatari Rial', 1),
(108, 'RON', 'lei', 'New Leu', 1),
(109, 'RUB', '&#x440;&#x443;&#x431;', 'Russian Ruble', 1),
(110, 'RWF', 'RWF', 'Rwanda Franc', 1),
(111, 'SHP', '&pound;', 'Saint Helena Pound', 1),
(112, 'WST', 'WST', 'Tala', 1),
(113, 'STD', 'STD', 'Dobra', 1),
(114, 'SAR', '&#xfdfc;', 'Saudi Riyal', 1),
(115, 'RSD', '&#x414;&#x438;&#x43d;.', 'Serbian Dinar', 1),
(116, 'SCR', 'Rs', 'Seychelles Rupee', 1),
(117, 'SLL', 'SLL', 'Leone', 1),
(118, 'SGD', '$', 'Singapore Dollar', 1),
(119, 'SBD', '$', 'Solomon Islands Dollar', 1),
(120, 'SOS', 'S', 'Somali Shilling', 1),
(121, 'ZAR', 'R', 'Rand', 1),
(122, 'LKR', 'Rs', 'Sri Lanka Rupee', 1),
(123, 'SDG', 'SDG', 'Sudanese Pound', 1),
(124, 'SRD', '$', 'Surinam Dollar', 1),
(125, 'SZL', 'SZL', 'Lilangeni', 1),
(126, 'SEK', 'kr', 'Swedish Krona', 1),
(127, 'SYP', '&pound;', 'Syrian Pound', 1),
(128, 'TWD', '$', 'New Taiwan Dollar', 1),
(129, 'TJS', 'TJS', 'Somoni', 1),
(130, 'TZS', 'TZS', 'Tanzanian Shilling', 1),
(131, 'THB', 'THB', 'Baht', 1),
(132, 'TOP', 'TOP', 'Pa', 1),
(133, 'TTD', 'TT$', 'Trinidad and Tobago Dollar', 1),
(134, 'TND', 'TND', 'Tunisian Dinar', 1),
(135, 'TRY', 'TL', 'Turkish Lira', 1),
(136, 'TMT', 'TMT', 'Manat', 1),
(137, 'UGX', 'UGX', 'Uganda Shilling', 1),
(138, 'UAH', '&#x20b4;', 'Hryvnia', 1),
(139, 'AED', 'AED', 'UAE Dirham', 1),
(140, 'UZS', '&#x43b;&#x432;', 'Uzbekistan Sum', 1),
(141, 'VUV', 'VUV', 'Vatu', 1),
(142, 'VEF', 'Bs', 'Bolivar Fuerte', 1),
(143, 'VND', '&#x20ab;', 'Dong', 1),
(144, 'YER', '&#xfdfc;', 'Yemeni Rial', 1),
(145, 'ZMK', 'ZMK', 'Zambian Kwacha', 1),
(146, 'ZWL', 'Z$', 'Zimbabwe Dollar', 1),
(147, 'XAU', 'XAU', 'Gold', 1),
(148, 'XBA', 'XBA', 'EURCO', 1),
(149, 'XBB', 'XBB', 'European Monetary Unit', 1),
(150, 'XBC', 'XBC', 'European Unit of Account 9', 1),
(151, 'XBD', 'XBD', 'European Unit of Account 17', 1),
(152, 'XDR', 'XDR', 'SDR', 1),
(153, 'XPD', 'XPD', 'Palladium', 1),
(154, 'XPT', 'XPT', 'Platinum', 1),
(155, 'XAG', 'XAG', 'Silver', 1),
(156, 'COP', '$', 'Colombian peso', 1),
(157, 'CUP', '$', 'Cuban peso', 1),
(158, 'SVC', 'SVC', 'Salvadoran colon', 1),
(159, 'CLP', '$', 'Chilean peso', 1),
(160, 'HTG', 'G', 'Haitian gourde', 1),
(161, 'MXN', '$', 'Mexican peso', 1),
(162, 'PAB', 'PAB', 'Panamanian balboa', 1),
(163, 'UYU', '$', 'Uruguayan peso', 1);

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}files`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}files` (
  `file_ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `file_root_type` enum('absolute','user','collection','shared','skins') NOT NULL DEFAULT 'absolute',
  `file_root_ID` int(11) unsigned NOT NULL DEFAULT '0',
  `file_path` varchar(255) NOT NULL DEFAULT '',
  `file_title` varchar(255) DEFAULT NULL,
  `file_alt` varchar(255) DEFAULT NULL,
  `file_desc` text,
  PRIMARY KEY (`file_ID`),
  UNIQUE KEY `file` (`file_root_type`,`file_root_ID`,`file_path`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}files`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}files` (`file_ID`, `file_root_type`, `file_root_ID`, `file_path`, `file_title`, `file_alt`, `file_desc`) VALUES
(1, 'user', 1, 'faceyourmanga_admin_boy.png', NULL, NULL, NULL),
(2, 'shared', 0, 'monument-valley/bus-stop-ahead.jpg', NULL, NULL, NULL),
(3, 'shared', 0, 'monument-valley/john-ford-point.jpg', NULL, NULL, NULL),
(4, 'shared', 0, 'monument-valley/monuments.jpg', NULL, NULL, NULL),
(5, 'shared', 0, 'monument-valley/monument-valley-road.jpg', NULL, NULL, NULL),
(6, 'shared', 0, 'monument-valley/monument-valley.jpg', NULL, NULL, NULL),
(7, 'shared', 0, 'logos/b2evolution_272x64.png', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}filetypes`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}filetypes` (
  `ftyp_ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ftyp_extensions` varchar(30) NOT NULL,
  `ftyp_name` varchar(30) NOT NULL,
  `ftyp_mimetype` varchar(50) NOT NULL,
  `ftyp_icon` varchar(20) DEFAULT NULL,
  `ftyp_viewtype` varchar(10) NOT NULL,
  `ftyp_allowed` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ftyp_ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=18 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}filetypes`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}filetypes` (`ftyp_ID`, `ftyp_extensions`, `ftyp_name`, `ftyp_mimetype`, `ftyp_icon`, `ftyp_viewtype`, `ftyp_allowed`) VALUES
(1, 'gif', 'GIF image', 'image/gif', 'image2.png', 'image', 1),
(2, 'png', 'PNG image', 'image/png', 'image2.png', 'image', 1),
(3, 'jpg jpeg', 'JPEG image', 'image/jpeg', 'image2.png', 'image', 1),
(4, 'txt', 'Text file', 'text/plain', 'document.png', 'text', 1),
(5, 'htm html', 'HTML file', 'text/html', 'html.png', 'browser', 0),
(6, 'pdf', 'PDF file', 'application/pdf', 'pdf.png', 'browser', 1),
(7, 'doc', 'Microsoft Word file', 'application/msword', 'doc.gif', 'external', 1),
(8, 'xls', 'Microsoft Excel file', 'application/vnd.ms-excel', 'xls.gif', 'external', 1),
(9, 'ppt', 'Powerpoint', 'application/vnd.ms-powerpoint', 'ppt.gif', 'external', 1),
(10, 'pps', 'Slideshow', 'pps', 'pps.gif', 'external', 1),
(11, 'zip', 'ZIP archive', 'application/zip', 'zip.gif', 'external', 1),
(12, 'php php3 php4 php5 php6', 'PHP script', 'application/x-httpd-php', 'php.gif', 'text', 0),
(13, 'css', 'Style sheet', 'text/css', '', 'text', 1),
(14, 'mp3', 'MPEG audio file', 'audio/mpeg', '', 'browser', 1),
(15, 'm4a', 'MPEG audio file', 'audio/x-m4a', '', 'browser', 1),
(16, 'mp4', 'MPEG video', 'video/mp4', '', 'browser', 1),
(17, 'mov', 'Quicktime video', 'video/quicktime', '', 'browser', 1);

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}global__cache`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}global__cache` (
  `cach_name` varchar(30) NOT NULL,
  `cach_cache` mediumblob,
  PRIMARY KEY (`cach_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}global__cache`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}global__cache` (`cach_name`, `cach_cache`) VALUES
('creds', 0x613a333a7b693a303b613a313a7b733a303a22223b613a323a7b693a303b613a333a7b693a303b693a363b693a313b733a31393a22687474703a2f2f65766f636f72652e6e65742f223b693a323b613a333a7b693a303b613a323a7b693a303b693a333b693a313b733a31333a22504850206672616d65776f726b223b7d693a313b613a323a7b693a303b693a353b693a313b733a393a226672616d65776f726b223b7d693a323b613a323a7b693a303b693a363b693a313b733a373a2265766f436f7265223b7d7d7d693a313b613a333a7b693a303b693a3130303b693a313b733a32333a22687474703a2f2f623265766f6c7574696f6e2e6e65742f223b693a323b613a32303a7b693a303b613a323a7b693a303b693a383b693a313b733a393a226672656520626c6f67223b7d693a313b613a323a7b693a303b693a31303b693a313b733a31343a226672656520626c6f6720746f6f6c223b7d693a323b613a323a7b693a303b693a31343b693a313b733a31363a226f70656e20736f7572636520626c6f67223b7d693a333b613a323a7b693a303b693a31373b693a313b733a393a226d756c7469626c6f67223b7d693a343b613a323a7b693a303b693a31393b693a313b733a31303a226d756c74692d626c6f67223b7d693a353b613a323a7b693a303b693a32353b693a313b733a31333a22626c6f6767696e6720746f6f6c223b7d693a363b613a323a7b693a303b693a33323b693a313b733a31333a22626c6f6720736f667477617265223b7d693a373b613a323a7b693a303b693a33333b693a313b733a32353a22636f6e74656e74206d616e6167656d656e742073797374656d223b7d693a383b613a323a7b693a303b693a34323b693a313b733a31333a22626c6f6720736f667477617265223b7d693a393b613a323a7b693a303b693a34353b693a313b733a383a22626c6f67736f6674223b7d693a31303b613a323a7b693a303b693a34373b693a313b733a323a226232223b7d693a31313b613a323a7b693a303b693a36343b693a313b733a31333a22626c6f6720736f667477617265223b7d693a31323b613a323a7b693a303b693a37323b693a313b733a31343a226d756c7469706c6520626c6f6773223b7d693a31333b613a323a7b693a303b693a37343b693a313b733a31383a226672656520626c6f6720736f667477617265223b7d693a31343b613a323a7b693a303b693a37383b693a313b733a31373a22626c6f6767696e6720736f667477617265223b7d693a31353b613a323a7b693a303b693a38343b693a313b733a31313a22626c6f6720656e67696e65223b7d693a31363b613a323a7b693a303b693a38353b693a313b733a333a22434d53223b7d693a31373b613a323a7b693a303b693a38383b693a313b733a393a22626c6f6720736f6674223b7d693a31383b613a323a7b693a303b693a39373b693a313b733a393a22626c6f6720746f6f6c223b7d693a31393b613a323a7b693a303b693a3130303b693a313b733a383a22626c6f67746f6f6c223b7d7d7d7d7d693a313b613a343a7b733a353a22656e2d554b223b613a323a7b693a303b733a38393a22687474703a2f2f623265766f6c7574696f6e2e6e65742f7765622d686f7374696e672f6575726f70652f756b2d7265636f6d6d656e6465642d686f7374732d7068702d6d7973716c2d626573742d63686f696365732e706870223b693a313b613a31333a7b693a303b613a323a7b693a303b693a353b693a313b733a373a22554b20686f7374223b7d693a313b613a323a7b693a303b693a31303b693a313b733a31343a2277656220686f7374696e6720554b223b7d693a323b613a323a7b693a303b693a31343b693a313b733a31313a2277656220686f737420554b223b7d693a333b613a323a7b693a303b693a31393b693a313b733a31333a22776562686f7374696e6720554b223b7d693a343b613a323a7b693a303b693a33303b693a313b733a31303a22554b20686f7374696e67223b7d693a353b613a323a7b693a303b693a33333b693a313b733a373a22686f737420756b223b7d693a363b613a323a7b693a303b693a36323b693a313b733a383a22554b20686f737473223b7d693a373b613a323a7b693a303b693a36343b693a313b733a373a22686f7374696e67223b7d693a383b613a323a7b693a303b693a36363b693a313b733a31313a2277656220686f7374696e67223b7d693a393b613a323a7b693a303b693a37323b693a313b733a31303a22686f7374696e6720554b223b7d693a31303b613a323a7b693a303b693a38323b693a313b733a383a22686f73747320554b223b7d693a31313b613a323a7b693a303b693a39383b693a313b733a31343a22554b2077656220686f7374696e67223b7d693a31323b613a323a7b693a303b693a3130303b693a313b733a31303a22554b20776562686f7374223b7d7d7d733a353a22656e2d4742223b613a333a7b693a303b613a333a7b693a303b693a363b693a313b733a37343a22687474703a2f2f623265766f6c7574696f6e2e6e65742f7765622d686f7374696e672f7670732d686f7374696e672d7669727475616c2d707269766174652d736572766572732e706870223b693a323b613a343a7b693a303b613a323a7b693a303b693a323b693a313b733a333a22767073223b7d693a313b613a323a7b693a303b693a333b693a313b733a333a22565053223b7d693a323b613a323a7b693a303b693a343b693a313b733a31313a2256505320686f7374696e67223b7d693a333b613a323a7b693a303b693a353b693a313b733a31313a2276707320686f7374696e67223b7d7d7d693a313b613a333a7b693a303b693a383b693a313b733a35393a22687474703a2f2f623265766f6c7574696f6e2e6e65742f756b2f7765622d686f7374696e672f626573742d7765622d686f7374732d756b2e706870223b693a323b613a313a7b693a303b613a323a7b693a303b693a383b693a313b733a31343a2277656220686f7374696e6720554b223b7d7d7d693a323b613a333a7b693a303b693a3130303b693a313b733a38393a22687474703a2f2f623265766f6c7574696f6e2e6e65742f7765622d686f7374696e672f6575726f70652f756b2d7265636f6d6d656e6465642d686f7374732d7068702d6d7973716c2d626573742d63686f696365732e706870223b693a323b613a31333a7b693a303b613a323a7b693a303b693a31303b693a313b733a31343a2277656220686f7374696e6720554b223b7d693a313b613a323a7b693a303b693a31343b693a313b733a31313a2277656220686f737420554b223b7d693a323b613a323a7b693a303b693a31393b693a313b733a31333a22776562686f7374696e6720554b223b7d693a333b613a323a7b693a303b693a33303b693a313b733a31303a22554b20686f7374696e67223b7d693a343b613a323a7b693a303b693a33333b693a313b733a373a22686f737420756b223b7d693a353b613a323a7b693a303b693a34343b693a313b733a373a22554b20686f7374223b7d693a363b613a323a7b693a303b693a36323b693a313b733a383a22554b20686f737473223b7d693a373b613a323a7b693a303b693a36343b693a313b733a373a22686f7374696e67223b7d693a383b613a323a7b693a303b693a36363b693a313b733a31313a2277656220686f7374696e67223b7d693a393b613a323a7b693a303b693a37323b693a313b733a31303a22686f7374696e6720554b223b7d693a31303b613a323a7b693a303b693a38323b693a313b733a383a22686f73747320554b223b7d693a31313b613a323a7b693a303b693a39383b693a313b733a31343a22554b2077656220686f7374696e67223b7d693a31323b613a323a7b693a303b693a3130303b693a313b733a31303a22554b20776562686f7374223b7d7d7d7d733a323a226672223b613a353a7b693a303b613a333a7b693a303b693a353b693a313b733a36333a22687474703a2f2f623265766f6c7574696f6e2e6e65742f66722f686562657267656d656e742f686562657267657572732d7765622d6672616e63652e706870223b693a323b613a313a7b693a303b613a323a7b693a303b693a353b693a313b733a31383a2268266561637574653b62657267656d656e74223b7d7d7d693a313b613a333a7b693a303b693a37383b693a313b733a37313a22687474703a2f2f623265766f6c7574696f6e2e6e65742f7765622d686f7374696e672f6575726f70652f686562657267656d656e742d7765622d6672616e63652d66722e706870223b693a323b613a31303a7b693a303b613a323a7b693a303b693a31343b693a313b733a31383a2268266561637574653b62657267656d656e74223b7d693a313b613a323a7b693a303b693a31393b693a313b733a32323a2268266561637574653b62657267656d656e7420776562223b7d693a323b613a323a7b693a303b693a33303b693a313b733a31373a2268266561637574653b6265726765757273223b7d693a333b613a323a7b693a303b693a33323b693a313b733a31363a2268266561637574653b62657267657572223b7d693a343b613a323a7b693a303b693a33333b693a313b733a31353a2268266561637574653b626572676572223b7d693a353b613a323a7b693a303b693a34313b693a313b733a31363a2268266561637574653b62657267657572223b7d693a363b613a323a7b693a303b693a34353b693a313b733a32303a2268266561637574653b6265726765757220776562223b7d693a373b613a323a7b693a303b693a36303b693a313b733a393a22686562657267657572223b7d693a383b613a323a7b693a303b693a37323b693a313b733a31313a22686562657267656d656e74223b7d693a393b613a323a7b693a303b693a37383b693a313b733a31313a227365727665757220776562223b7d7d7d693a323b613a333a7b693a303b693a39313b693a313b733a37313a22687474703a2f2f623265766f6c7574696f6e2e6e65742f7765622d686f7374696e672f6275646765742d7765622d686f7374696e672d6c6f772d636f73742d6c616d702e706870223b693a323b613a363a7b693a303b613a323a7b693a303b693a38323b693a313b733a31333a22636865617020686f7374696e67223b7d693a313b613a323a7b693a303b693a38343b693a313b733a31343a2262756467657420686f7374696e67223b7d693a323b613a323a7b693a303b693a38363b693a313b733a31333a2276616c756520686f7374696e67223b7d693a333b613a323a7b693a303b693a38383b693a313b733a31383a226166666f726461626c6520686f7374696e67223b7d693a343b613a323a7b693a303b693a38393b693a313b733a31353a22706f70756c617220686f7374696e67223b7d693a353b613a323a7b693a303b693a39313b693a313b733a31363a226c6f7720636f737420686f7374696e67223b7d7d7d693a333b613a333a7b693a303b693a39373b693a313b733a36383a22687474703a2f2f623265766f6c7574696f6e2e6e65742f61626f75742f6c696e75782d6465646963617465642d736572766572732d7765622d686f7374696e672e706870223b693a323b613a333a7b693a303b613a323a7b693a303b693a39343b693a313b733a31373a226465646963617465642073657276657273223b7d693a313b613a323a7b693a303b693a39353b693a313b733a31363a2264656469636174656420736572766572223b7d693a323b613a323a7b693a303b693a39373b693a313b733a31373a2264656469636174656420686f7374696e67223b7d7d7d693a343b613a333a7b693a303b693a3130303b693a313b733a37303a22687474703a2f2f623265766f6c7574696f6e2e6e65742f7765622d686f7374696e672f7373682d686f7374696e672d7365637572652d7368656c6c2d6163636573732e706870223b693a323b613a333a7b693a303b613a323a7b693a303b693a39383b693a313b733a31353a225353482077656220686f7374696e67223b7d693a313b613a323a7b693a303b693a39393b693a313b733a31323a22736563757265207368656c6c223b7d693a323b613a323a7b693a303b693a3130303b693a313b733a31313a2253534820686f7374696e67223b7d7d7d7d733a303a22223b613a31313a7b693a303b613a333a7b693a303b693a363b693a313b733a37343a22687474703a2f2f623265766f6c7574696f6e2e6e65742f7765622d686f7374696e672f7670732d686f7374696e672d7669727475616c2d707269766174652d736572766572732e706870223b693a323b613a343a7b693a303b613a323a7b693a303b693a323b693a313b733a333a22767073223b7d693a313b613a323a7b693a303b693a333b693a313b733a333a22565053223b7d693a323b613a323a7b693a303b693a343b693a313b733a31313a2256505320686f7374696e67223b7d693a333b613a323a7b693a303b693a363b693a313b733a31313a2276707320686f7374696e67223b7d7d7d693a313b613a333a7b693a303b693a31303b693a313b733a37353a22687474703a2f2f623265766f6c7574696f6e2e6e65742f7765622d686f7374696e672f677265656e2d686f7374696e672d72656e657761626c652d656e657267792d706f7765722e706870223b693a323b613a323a7b693a303b613a323a7b693a303b693a383b693a313b733a31333a22677265656e20686f7374696e67223b7d693a313b613a323a7b693a303b693a31303b693a313b733a31373a22677265656e2077656220686f7374696e67223b7d7d7d693a323b613a333a7b693a303b693a31313b693a313b733a35353a22687474703a2f2f623265766f6c7574696f6e2e6e65742f7765622d686f7374696e672f726573656c6c65722d686f7374696e672e706870223b693a323b613a313a7b693a303b613a323a7b693a303b693a31313b693a313b733a31363a22726573656c6c657220686f7374696e67223b7d7d7d693a333b613a333a7b693a303b693a31323b693a313b733a37343a22687474703a2f2f623265766f6c7574696f6e2e6e65742f7765622d686f7374696e672f626573742d726573656c6c65722d686f7374696e672d746f702d70726f7669646572732e706870223b693a323b613a313a7b693a303b613a323a7b693a303b693a31323b693a313b733a31363a22726573656c6c657220686f7374696e67223b7d7d7d693a343b613a333a7b693a303b693a31333b693a313b733a36373a22687474703a2f2f623265766f6c7574696f6e2e6e65742f7765622d686f7374696e672f626c6f672f6e6577732f7765622d686f7374696e672d746573742d7369746573223b693a323b613a313a7b693a303b613a323a7b693a303b693a31333b693a313b733a31373a2277656220686f7374696e67207369746573223b7d7d7d693a353b613a333a7b693a303b693a31373b693a313b733a37313a22687474703a2f2f623265766f6c7574696f6e2e6e65742f7765622d686f7374696e672f746f702d7265636f6d6d656e6465642d776562686f7374696e672d706c616e732e706870223b693a323b613a333a7b693a303b613a323a7b693a303b693a31343b693a313b733a31303a22776562686f7374696e67223b7d693a313b613a323a7b693a303b693a31363b693a313b733a31393a22623265766f6c7574696f6e20686f7374696e67223b7d693a323b613a323a7b693a303b693a31373b693a313b733a31373a22686f7374696e6720636f6d70616e696573223b7d7d7d693a363b613a333a7b693a303b693a37333b693a313b733a36363a22687474703a2f2f623265766f6c7574696f6e2e6e65742f7765622d686f7374696e672f746f702d7175616c6974792d626573742d776562686f7374696e672e706870223b693a323b613a32353a7b693a303b613a323a7b693a303b693a31383b693a313b733a31393a2277656220686f7374696e6720636f6d70616e79223b7d693a313b613a323a7b693a303b693a31393b693a313b733a31393a2277656220686f7374696e672072657669657773223b7d693a323b613a323a7b693a303b693a32303b693a313b733a31383a22776562686f7374696e672072657669657773223b7d693a333b613a323a7b693a303b693a32313b693a313b733a31353a22686f7374696e672072657669657773223b7d693a343b613a323a7b693a303b693a32323b693a313b733a31333a22623265766f20686f7374696e67223b7d693a353b613a323a7b693a303b693a32373b693a313b733a31323a22626c6f6720686f7374696e67223b7d693a363b613a323a7b693a303b693a32383b693a313b733a31383a2277656220686f7374696e6720746f70203130223b7d693a373b613a323a7b693a303b693a32393b693a313b733a31343a22746f7020313020686f7374696e67223b7d693a383b613a323a7b693a303b693a33303b693a313b733a31313a22746f7020686f7374696e67223b7d693a393b613a323a7b693a303b693a33323b693a313b733a373a22686f7374696e67223b7d693a31303b613a323a7b693a303b693a33343b693a313b733a353a22686f737473223b7d693a31313b613a323a7b693a303b693a33353b693a313b733a393a22746f7020686f737473223b7d693a31323b613a323a7b693a303b693a33363b693a313b733a383a2277656220686f7374223b7d693a31333b613a323a7b693a303b693a33373b693a313b733a31303a226265737420686f737473223b7d693a31343b613a323a7b693a303b693a33393b693a313b733a31323a226265737420686f7374696e67223b7d693a31353b613a323a7b693a303b693a34323b693a313b733a31313a2250485020686f7374696e67223b7d693a31363b613a323a7b693a303b693a34333b693a313b733a31333a224d7953514c20686f7374696e67223b7d693a31373b613a323a7b693a303b693a34343b693a313b733a383a22776562686f737473223b7d693a31383b613a323a7b693a303b693a34353b693a313b733a31323a224c414d5020686f7374696e67223b7d693a31393b613a323a7b693a303b693a36323b693a313b733a31313a2277656220686f7374696e67223b7d693a32303b613a323a7b693a303b693a36343b693a313b733a373a22776562686f7374223b7d693a32313b613a323a7b693a303b693a36363b693a313b733a31303a22776562686f7374696e67223b7d693a32323b613a323a7b693a303b693a37303b693a313b733a393a2277656220686f737473223b7d693a32333b613a323a7b693a303b693a37323b693a313b733a31303a22776562686f7374696e67223b7d693a32343b613a323a7b693a303b693a37333b693a313b733a373a22686f7374696e67223b7d7d7d693a373b613a333a7b693a303b693a39313b693a313b733a37313a22687474703a2f2f623265766f6c7574696f6e2e6e65742f7765622d686f7374696e672f6275646765742d7765622d686f7374696e672d6c6f772d636f73742d6c616d702e706870223b693a323b613a31313a7b693a303b613a323a7b693a303b693a37343b693a313b733a31393a226c6f7720636f737420776562686f7374696e67223b7d693a313b613a323a7b693a303b693a37363b693a313b733a32303a226c6f7720636f73742077656220686f7374696e67223b7d693a323b613a323a7b693a303b693a37383b693a313b733a373a22686f7374696e67223b7d693a333b613a323a7b693a303b693a37393b693a313b733a32323a22626573742063686561702077656220686f7374696e67223b7d693a343b613a323a7b693a303b693a38343b693a313b733a31333a22636865617020686f7374696e67223b7d693a353b613a323a7b693a303b693a38343b693a313b733a31343a2262756467657420686f7374696e67223b7d693a363b613a323a7b693a303b693a38373b693a313b733a31373a2263686561702077656220686f7374696e67223b7d693a373b613a323a7b693a303b693a38383b693a313b733a31363a22636865617020776562686f7374696e67223b7d693a383b613a323a7b693a303b693a38393b693a313b733a31383a226166666f726461626c6520686f7374696e67223b7d693a393b613a323a7b693a303b693a39303b693a313b733a31363a226c6f7720636f737420686f7374696e67223b7d693a31303b613a323a7b693a303b693a39313b693a313b733a31353a226368656170657220686f7374696e67223b7d7d7d693a383b613a333a7b693a303b693a39323b693a313b733a35363a22687474703a2f2f623265766f6c7574696f6e2e6e65742f7765622d686f7374696e672f6465646963617465642d736572766572732e706870223b693a323b613a313a7b693a303b613a323a7b693a303b693a39323b693a313b733a31373a226465646963617465642073657276657273223b7d7d7d693a393b613a333a7b693a303b693a39383b693a313b733a36383a22687474703a2f2f623265766f6c7574696f6e2e6e65742f61626f75742f6c696e75782d6465646963617465642d736572766572732d7765622d686f7374696e672e706870223b693a323b613a343a7b693a303b613a323a7b693a303b693a39333b693a313b733a31373a226465646963617465642073657276657273223b7d693a313b613a323a7b693a303b693a39353b693a313b733a31363a2264656469636174656420736572766572223b7d693a323b613a323a7b693a303b693a39363b693a313b733a31343a226d616e6167656420736572766572223b7d693a333b613a323a7b693a303b693a39383b693a313b733a31373a2264656469636174656420686f7374696e67223b7d7d7d693a31303b613a333a7b693a303b693a3130303b693a313b733a37303a22687474703a2f2f623265766f6c7574696f6e2e6e65742f7765622d686f7374696e672f7373682d686f7374696e672d7365637572652d7368656c6c2d6163636573732e706870223b693a323b613a323a7b693a303b613a323a7b693a303b693a39393b693a313b733a31353a225353482077656220686f7374696e67223b7d693a313b613a323a7b693a303b693a3130303b693a313b733a31313a2253534820686f7374696e67223b7d7d7d7d7d693a323b613a323a7b733a323a226672223b613a333a7b693a303b613a333a7b693a303b693a33363b693a313b733a32303a22687474703a2f2f66706c616e7175652e6e65742f223b693a323b613a383a7b693a303b613a323a7b693a303b693a333b693a313b733a323a224650223b7d693a313b613a323a7b693a303b693a393b693a313b733a383a224672616e636f6973223b7d693a323b613a323a7b693a303b693a31373b693a313b733a323a226670223b7d693a333b613a323a7b693a303b693a31393b693a313b733a333a22462050223b7d693a343b613a323a7b693a303b693a32383b693a313b733a383a224672616e636f6973223b7d693a353b613a323a7b693a303b693a33323b693a313b733a31353a224672616e2663636564696c3b6f6973223b7d693a363b613a323a7b693a303b693a33333b693a313b733a303a22223b7d693a373b613a323a7b693a303b693a33363b693a313b733a31353a224672616e2663636564696c3b6f6973223b7d7d7d693a313b613a333a7b693a303b693a39303b693a313b733a35323a22687474703a2f2f623265766f6c7574696f6e2e6e65742f61626f75742f6d6f6e6574697a652d626c6f672d6d6f6e65792e706870223b693a323b613a353a7b693a303b613a323a7b693a303b693a34313b693a313b733a383a2270756220626c6f67223b7d693a313b613a323a7b693a303b693a34353b693a313b733a333a22707562223b7d693a323b613a323a7b693a303b693a37323b693a313b733a373a22616473656e7365223b7d693a333b613a323a7b693a303b693a37383b693a313b733a333a22707562223b7d693a343b613a323a7b693a303b693a39303b693a313b733a383a22626c6f6720707562223b7d7d7d693a323b613a333a7b693a303b693a3130303b693a313b733a33393a22687474703a2f2f623265766f6c7574696f6e2e6e65742f6465762f617574686f72732e68746d6c223b693a323b613a333a7b693a303b613a323a7b693a303b693a39343b693a313b733a373a22617574686f7273223b7d693a313b613a323a7b693a303b693a39383b693a313b733a373a2265766f5465616d223b7d693a323b613a323a7b693a303b693a3130303b693a313b733a343a227465616d223b7d7d7d7d733a303a22223b613a343a7b693a303b613a333a7b693a303b693a32373b693a313b733a32303a22687474703a2f2f66706c616e7175652e636f6d2f223b693a323b613a373a7b693a303b613a323a7b693a303b693a373b693a313b733a31353a224672616e2663636564696c3b6f6973223b7d693a313b613a323a7b693a303b693a31313b693a313b733a323a224650223b7d693a323b613a323a7b693a303b693a31333b693a313b733a303a22223b7d693a333b613a323a7b693a303b693a31353b693a313b733a343a22462e502e223b7d693a343b613a323a7b693a303b693a32303b693a313b733a303a22223b7d693a353b613a323a7b693a303b693a32323b693a313b733a323a226670223b7d693a363b613a323a7b693a303b693a32373b693a313b733a383a224672616e636f6973223b7d7d7d693a313b613a323a7b693a303b693a33333b693a313b733a303a22223b7d693a323b613a333a7b693a303b693a39303b693a313b733a35323a22687474703a2f2f623265766f6c7574696f6e2e6e65742f61626f75742f6d6f6e6574697a652d626c6f672d6d6f6e65792e706870223b693a323b613a383a7b693a303b613a323a7b693a303b693a33353b693a313b733a303a22223b7d693a313b613a323a7b693a303b693a33393b693a313b733a31313a226164766572746973696e67223b7d693a323b613a323a7b693a303b693a34353b693a313b733a383a22626c6f6720616473223b7d693a333b613a323a7b693a303b693a35323b693a313b733a31303a226d6f6e6574697a696e67223b7d693a343b613a323a7b693a303b693a36323b693a313b733a383a226d6f6e6574697a65223b7d693a353b613a323a7b693a303b693a36343b693a313b733a31333a226d6f6e6574697a6520626c6f67223b7d693a363b613a323a7b693a303b693a37393b693a313b733a303a22223b7d693a373b613a323a7b693a303b693a39303b693a313b733a373a22616473656e7365223b7d7d7d693a333b613a333a7b693a303b693a3130303b693a313b733a33393a22687474703a2f2f623265766f6c7574696f6e2e6e65742f6465762f617574686f72732e68746d6c223b693a323b613a333a7b693a303b613a323a7b693a303b693a39343b693a313b733a373a22617574686f7273223b7d693a313b613a323a7b693a303b693a39383b693a313b733a373a2265766f5465616d223b7d693a323b613a323a7b693a303b693a3130303b693a313b733a343a227465616d223b7d7d7d7d7d7d),
('evo_links', 0x613a313a7b733a303a22223b613a313a7b693a303b613a333a7b693a303b693a3130303b693a313b733a32333a22687474703a2f2f623265766f6c7574696f6e2e6e65742f223b693a323b613a32373a7b693a303b613a323a7b693a303b693a32383b693a313b733a32323a22706f776572656420627920623265766f6c7574696f6e223b7d693a313b613a323a7b693a303b693a35353b693a313b733a34313a22706f776572656420627920623265766f6c7574696f6e206672656520626c6f6720736f667477617265223b7d693a323b613a323a7b693a303b693a35373b693a313b733a32393a22706f7765726564206279206672656520626c6f6720736f667477617265223b7d693a333b613a323a7b693a303b693a35393b693a313b733a323a226232223b7d693a343b613a323a7b693a303b693a36303b693a313b733a393a226672656520626c6f67223b7d693a353b613a323a7b693a303b693a36333b693a313b733a31343a226672656520626c6f6720746f6f6c223b7d693a363b613a323a7b693a303b693a36363b693a313b733a31383a226672656520626c6f6720736f667477617265223b7d693a373b613a323a7b693a303b693a36373b693a313b733a32313a2266726565206f70656e20736f7572636520626c6f67223b7d693a383b613a323a7b693a303b693a36383b693a313b733a31363a226f70656e20736f7572636520626c6f67223b7d693a393b613a323a7b693a303b693a36393b693a313b733a32313a226f70656e20736f7572636520626c6f6720746f6f6c223b7d693a31303b613a323a7b693a303b693a37303b693a313b733a32353a226f70656e20736f7572636520626c6f6720736f667477617265223b7d693a31313b613a323a7b693a303b693a37323b693a313b733a393a226d756c7469626c6f67223b7d693a31323b613a323a7b693a303b693a37333b693a313b733a31363a226d756c7469626c6f6720656e67696e65223b7d693a31333b613a323a7b693a303b693a37343b693a313b733a31383a226d756c7469626c6f6720706c6174666f726d223b7d693a31343b613a323a7b693a303b693a37353b693a313b733a31303a226d756c74692d626c6f67223b7d693a31353b613a323a7b693a303b693a37363b693a313b733a31373a226d756c74692d626c6f6720656e67696e65223b7d693a31363b613a323a7b693a303b693a37373b693a313b733a31393a226d756c74692d626c6f6720706c6174666f726d223b7d693a31373b613a323a7b693a303b693a37393b693a313b733a31343a226d756c7469706c6520626c6f6773223b7d693a31383b613a323a7b693a303b693a38313b693a313b733a31313a22626c6f6720656e67696e65223b7d693a31393b613a323a7b693a303b693a38333b693a313b733a383a22626c6f67746f6f6c223b7d693a32303b613a323a7b693a303b693a38353b693a313b733a393a22626c6f6720746f6f6c223b7d693a32313b613a323a7b693a303b693a38373b693a313b733a31333a22626c6f6767696e6720746f6f6c223b7d693a32323b613a323a7b693a303b693a38383b693a313b733a31333a22626c6f6767696e6720736f6674223b7d693a32333b613a323a7b693a303b693a39313b693a313b733a31373a22626c6f6767696e6720736f667477617265223b7d693a32343b613a323a7b693a303b693a39323b693a313b733a383a22626c6f67736f6674223b7d693a32353b613a323a7b693a303b693a39343b693a313b733a393a22626c6f6720736f6674223b7d693a32363b613a323a7b693a303b693a3130303b693a313b733a31333a22626c6f6720736f667477617265223b7d7d7d7d7d),
('extra_msg', 0x733a303a22223b),
('feedhlp', 0x613a313a7b693a303b613a313a7b733a303a22223b613a313a7b693a303b613a333a7b693a303b693a3130303b693a313b733a34363a22687474703a2f2f7765627265666572656e63652e66722f323030362f30382f33302f7273735f61746f6d5f786d6c223b693a323b613a323a7b693a303b613a323a7b693a303b693a31363b693a313b733a31313a224d6f7265206f6e20525353223b7d693a313b613a323a7b693a303b693a3130303b693a313b733a31323a2257686174206973205253533f223b7d7d7d7d7d7d),
('updates', 0x613a313a7b693a303b613a343a7b733a343a226e616d65223b733a31393a22623265766f6c7574696f6e207620342e312e33223b733a31313a226465736372697074696f6e223b733a3130313a225741524e494e473a20706c6561736520646f75626c6520636865636b207468617420796f7520617265204e4f542061626f757420746f204f564552575249544520796f757220696e7374616c6c207769746820736f6d657468696e67204f4c444552212121223b733a373a2276657273696f6e223b733a31363a22342e312e332d323031322d30332d3032223b733a333a2275726c223b733a37323a22687474703a2f2f623265766f6c7574696f6e2e6e65742f646f776e6c6f6164732f623265766f6c7574696f6e2d342e312e332d737461626c652d323031322d30332d30322e7a6970223b7d7d),
('version_status_color', 0x733a333a22726564223b),
('version_status_msg', 0x733a3234343a22623265766f6c7574696f6e2076657273696f6e20342e312e332028737461626c6529206973206e6f7720617661696c61626c652e20466f722062657374203c7370616e207374796c653d22636f6c6f723a20726564223e73656375726974793c2f7370616e3e2c207765207374726f6e676c79207265636f6d6d656e64207468617420796f75207570677261646520746f20746865206c61746573742076657273696f6e2e203c61207461726765743d225f626c616e6b2220687265663d22687474703a2f2f623265766f6c7574696f6e2e6e65742f646f776e6c6f6164732f223e446f776e6c6f616420686572653c2f613e2e223b);

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}groups`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}groups` (
  `grp_ID` int(11) NOT NULL AUTO_INCREMENT,
  `grp_name` varchar(50) NOT NULL DEFAULT '',
  `grp_perm_admin` enum('none','hidden','visible') NOT NULL DEFAULT 'visible',
  `grp_perm_blogs` enum('user','viewall','editall') NOT NULL DEFAULT 'user',
  `grp_perm_bypass_antispam` tinyint(1) NOT NULL DEFAULT '0',
  `grp_perm_xhtmlvalidation` varchar(10) NOT NULL DEFAULT 'always',
  `grp_perm_xhtmlvalidation_xmlrpc` varchar(10) NOT NULL DEFAULT 'always',
  `grp_perm_xhtml_css_tweaks` tinyint(1) NOT NULL DEFAULT '0',
  `grp_perm_xhtml_iframes` tinyint(1) NOT NULL DEFAULT '0',
  `grp_perm_xhtml_javascript` tinyint(1) NOT NULL DEFAULT '0',
  `grp_perm_xhtml_objects` tinyint(1) NOT NULL DEFAULT '0',
  `grp_perm_stats` enum('none','user','view','edit') NOT NULL DEFAULT 'none',
  `grp_perm_spamblacklist` enum('none','view','edit') NOT NULL DEFAULT 'none',
  `grp_perm_slugs` enum('none','view','edit') NOT NULL DEFAULT 'none',
  `grp_perm_options` enum('none','view','edit') NOT NULL DEFAULT 'none',
  `grp_perm_users` enum('none','view','edit') NOT NULL DEFAULT 'none',
  `grp_perm_templates` tinyint(4) NOT NULL DEFAULT '0',
  `grp_perm_files` enum('none','view','add','edit','all') NOT NULL DEFAULT 'none',
  PRIMARY KEY (`grp_ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}groups`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}groups` (`grp_ID`, `grp_name`, `grp_perm_admin`, `grp_perm_blogs`, `grp_perm_bypass_antispam`, `grp_perm_xhtmlvalidation`, `grp_perm_xhtmlvalidation_xmlrpc`, `grp_perm_xhtml_css_tweaks`, `grp_perm_xhtml_iframes`, `grp_perm_xhtml_javascript`, `grp_perm_xhtml_objects`, `grp_perm_stats`, `grp_perm_spamblacklist`, `grp_perm_slugs`, `grp_perm_options`, `grp_perm_users`, `grp_perm_templates`, `grp_perm_files`) VALUES
(1, 'Administrators', 'visible', 'editall', 0, 'always', 'always', 1, 0, 0, 0, 'edit', 'edit', 'edit', 'edit', 'edit', 1, 'all'),
(2, 'Privileged Bloggers', 'visible', 'viewall', 0, 'always', 'always', 1, 0, 0, 0, 'user', 'edit', 'none', 'view', 'view', 0, 'add'),
(3, 'Bloggers', 'visible', 'user', 0, 'always', 'always', 1, 0, 0, 0, 'none', 'view', 'none', 'none', 'none', 0, 'view'),
(4, 'Basic Users', 'none', 'user', 0, 'always', 'always', 0, 0, 0, 0, 'none', 'none', 'none', 'none', 'none', 0, 'none');

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}groups__groupsettings`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}groups__groupsettings` (
  `gset_grp_ID` int(11) unsigned NOT NULL,
  `gset_name` varchar(30) NOT NULL,
  `gset_value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`gset_grp_ID`,`gset_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}groups__groupsettings`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}groups__groupsettings` (`gset_grp_ID`, `gset_name`, `gset_value`) VALUES
(1, 'perm_api', 'always'),
(1, 'perm_createblog', 'allowed'),
(1, 'perm_getblog', 'denied'),
(1, 'perm_maintenance', 'upgrade'),
(1, 'perm_messaging', 'delete'),
(2, 'perm_api', 'always'),
(2, 'perm_createblog', 'allowed'),
(2, 'perm_getblog', 'allowed'),
(2, 'perm_maintenance', 'none'),
(2, 'perm_messaging', 'write'),
(3, 'perm_api', 'always'),
(3, 'perm_createblog', 'denied'),
(3, 'perm_getblog', 'denied'),
(3, 'perm_maintenance', 'none'),
(3, 'perm_messaging', 'reply'),
(4, 'perm_api', 'never'),
(4, 'perm_createblog', 'denied'),
(4, 'perm_getblog', 'denied'),
(4, 'perm_maintenance', 'none'),
(4, 'perm_messaging', 'none');

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}hitlog`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}hitlog` (
  `hit_ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `hit_sess_ID` int(10) unsigned DEFAULT NULL,
  `hit_datetime` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `hit_uri` varchar(250) DEFAULT NULL,
  `hit_referer_type` enum('search','blacklist','spam','referer','direct','self','admin') NOT NULL,
  `hit_referer` varchar(250) DEFAULT NULL,
  `hit_referer_dom_ID` int(10) unsigned DEFAULT NULL,
  `hit_keyphrase_keyp_ID` int(10) unsigned DEFAULT NULL,
  `hit_serprank` int(10) unsigned DEFAULT NULL,
  `hit_blog_ID` int(11) unsigned DEFAULT NULL,
  `hit_remote_addr` varchar(40) DEFAULT NULL,
  `hit_agent_type` enum('rss','robot','browser','unknown') NOT NULL DEFAULT 'unknown',
  PRIMARY KEY (`hit_ID`),
  KEY `hit_blog_ID` (`hit_blog_ID`),
  KEY `hit_uri` (`hit_uri`),
  KEY `hit_referer_dom_ID` (`hit_referer_dom_ID`),
  KEY `hit_remote_addr` (`hit_remote_addr`),
  KEY `hit_sess_ID` (`hit_sess_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}hitlog`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}hitlog` (`hit_ID`, `hit_sess_ID`, `hit_datetime`, `hit_uri`, `hit_referer_type`, `hit_referer`, `hit_referer_dom_ID`, `hit_keyphrase_keyp_ID`, `hit_serprank`, `hit_blog_ID`, `hit_remote_addr`, `hit_agent_type`) VALUES
(1, 1, '2012-04-02 02:52:26', '/b2evolution/4.0/index.php', 'self', 'http://localhost/b2evolution/4.0/install/index.php?locale=en-US-utf8&confirmed=0&installer_version=10&action=newdb&create_sample_contents=1', 1, NULL, NULL, 1, '::1', 'browser'),
(2, 1, '2012-04-02 02:52:27', '/b2evolution/4.0/blog1.php', 'self', 'http://localhost/b2evolution/4.0/install/index.php?locale=en-US-utf8&confirmed=0&installer_version=10&action=newdb&create_sample_contents=1', 1, NULL, NULL, 1, '::1', 'browser'),
(3, 1, '2012-04-02 02:52:28', '/b2evolution/4.0/htsrv/getfile.php/b2evolution_272x64.png?root=shared_0&path=logos/b2evolution_272x64.png&mtime=1222178208&size=fit-400x320', 'self', 'http://localhost/b2evolution/4.0/blog1.php', 1, NULL, NULL, NULL, '::1', 'browser'),
(4, 1, '2012-04-02 02:52:28', '/b2evolution/4.0/htsrv/getfile.php/faceyourmanga_admin_boy.png?root=user_1&path=faceyourmanga_admin_boy.png&mtime=1222698642&size=fit-160x160', 'self', 'http://localhost/b2evolution/4.0/blog1.php', 1, NULL, NULL, NULL, '::1', 'browser'),
(5, 1, '2012-04-02 02:52:28', '/b2evolution/4.0/htsrv/getfile.php/monuments.jpg?root=shared_0&path=monument-valley/monuments.jpg&mtime=1222178208&size=fit-400x320', 'self', 'http://localhost/b2evolution/4.0/blog1.php', 1, NULL, NULL, NULL, '::1', 'browser'),
(6, 1, '2012-04-02 02:52:28', '/b2evolution/4.0/htsrv/getfile.php/bus-stop-ahead.jpg?root=shared_0&path=monument-valley/bus-stop-ahead.jpg&mtime=1222178208&size=fit-160x120', 'self', 'http://localhost/b2evolution/4.0/blog1.php', 1, NULL, NULL, NULL, '::1', 'browser');

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}items__item`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}items__item` (
  `post_ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `post_parent_ID` int(11) unsigned DEFAULT NULL,
  `post_creator_user_ID` int(11) unsigned NOT NULL,
  `post_lastedit_user_ID` int(11) unsigned DEFAULT NULL,
  `post_assigned_user_ID` int(11) unsigned DEFAULT NULL,
  `post_dateset` tinyint(1) NOT NULL DEFAULT '1',
  `post_datestart` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `post_datedeadline` datetime DEFAULT NULL,
  `post_datecreated` datetime DEFAULT NULL,
  `post_datemodified` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `post_status` enum('published','deprecated','protected','private','draft','redirected') NOT NULL DEFAULT 'published',
  `post_pst_ID` int(11) unsigned DEFAULT NULL,
  `post_ptyp_ID` int(10) unsigned NOT NULL DEFAULT '1',
  `post_locale` varchar(20) NOT NULL DEFAULT 'en-EU',
  `post_content` mediumtext,
  `post_excerpt` text,
  `post_excerpt_autogenerated` tinyint(1) DEFAULT NULL,
  `post_title` text NOT NULL,
  `post_urltitle` varchar(210) NOT NULL,
  `post_canonical_slug_ID` int(10) unsigned DEFAULT NULL,
  `post_tiny_slug_ID` int(10) unsigned DEFAULT NULL,
  `post_titletag` varchar(255) DEFAULT NULL,
  `post_metadesc` varchar(255) DEFAULT NULL,
  `post_metakeywords` varchar(255) DEFAULT NULL,
  `post_url` varchar(255) DEFAULT NULL,
  `post_main_cat_ID` int(11) unsigned NOT NULL,
  `post_notifications_status` enum('noreq','todo','started','finished') NOT NULL DEFAULT 'noreq',
  `post_notifications_ctsk_ID` int(10) unsigned DEFAULT NULL,
  `post_views` int(11) unsigned NOT NULL DEFAULT '0',
  `post_wordcount` int(11) DEFAULT NULL,
  `post_comment_status` enum('disabled','open','closed') NOT NULL DEFAULT 'open',
  `post_commentsexpire` datetime DEFAULT NULL,
  `post_renderers` text NOT NULL,
  `post_priority` int(11) unsigned DEFAULT NULL COMMENT 'Task priority in workflow',
  `post_featured` tinyint(1) NOT NULL DEFAULT '0',
  `post_order` double DEFAULT NULL,
  `post_double1` double DEFAULT NULL COMMENT 'Custom double value 1',
  `post_double2` double DEFAULT NULL COMMENT 'Custom double value 2',
  `post_double3` double DEFAULT NULL COMMENT 'Custom double value 3',
  `post_double4` double DEFAULT NULL COMMENT 'Custom double value 4',
  `post_double5` double DEFAULT NULL COMMENT 'Custom double value 5',
  `post_varchar1` varchar(255) DEFAULT NULL COMMENT 'Custom varchar value 1',
  `post_varchar2` varchar(255) DEFAULT NULL COMMENT 'Custom varchar value 2',
  `post_varchar3` varchar(255) DEFAULT NULL COMMENT 'Custom varchar value 3',
  `post_editor_code` varchar(32) DEFAULT NULL COMMENT 'Plugin code of the editor used to edit this post',
  PRIMARY KEY (`post_ID`),
  UNIQUE KEY `post_urltitle` (`post_urltitle`),
  KEY `post_datestart` (`post_datestart`),
  KEY `post_main_cat_ID` (`post_main_cat_ID`),
  KEY `post_creator_user_ID` (`post_creator_user_ID`),
  KEY `post_status` (`post_status`),
  KEY `post_parent_ID` (`post_parent_ID`),
  KEY `post_assigned_user_ID` (`post_assigned_user_ID`),
  KEY `post_ptyp_ID` (`post_ptyp_ID`),
  KEY `post_pst_ID` (`post_pst_ID`),
  KEY `post_order` (`post_order`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=32 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}items__item`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}items__item` (`post_ID`, `post_parent_ID`, `post_creator_user_ID`, `post_lastedit_user_ID`, `post_assigned_user_ID`, `post_dateset`, `post_datestart`, `post_datedeadline`, `post_datecreated`, `post_datemodified`, `post_status`, `post_pst_ID`, `post_ptyp_ID`, `post_locale`, `post_content`, `post_excerpt`, `post_excerpt_autogenerated`, `post_title`, `post_urltitle`, `post_canonical_slug_ID`, `post_tiny_slug_ID`, `post_titletag`, `post_metadesc`, `post_metakeywords`, `post_url`, `post_main_cat_ID`, `post_notifications_status`, `post_notifications_ctsk_ID`, `post_views`, `post_wordcount`, `post_comment_status`, `post_commentsexpire`, `post_renderers`, `post_priority`, `post_featured`, `post_order`, `post_double1`, `post_double2`, `post_double3`, `post_double4`, `post_double5`, `post_varchar1`, `post_varchar2`, `post_varchar3`, `post_editor_code`) VALUES
(1, NULL, 1, 1, NULL, 1, '2012-04-02 02:50:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US-utf8', '<p>This is the first post.</p>\n\n<p>It appears in a single category.</p>', 'This is the first post.\n\nIt appears in a single category.', 1, 'First Post', 'first-post', 2, 3, NULL, NULL, NULL, '', 1, 'noreq', NULL, 0, 11, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, NULL, 1, 1, NULL, 1, '2012-04-02 02:50:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US-utf8', '<p>This is the second post.</p>\n\n<p>It appears in multiple categories.</p>', 'This is the second post.\n\nIt appears in multiple categories.', 1, 'Second post', 'second-post', 4, 5, NULL, NULL, NULL, '', 2, 'noreq', NULL, 0, 10, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, NULL, 1, 1, NULL, 1, '2012-04-02 02:50:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US', 'In the middle of nowhere: a school bus stop where you wouldn''t really expect it!', 'In the middle of nowhere: a school bus stop where you wouldn''t really expect it!', 1, 'Bus Stop Ahead', 'bus-stop-ahead', 6, 7, NULL, NULL, NULL, 'http://fplanque.com/photo/monument-valley', 14, 'noreq', NULL, 0, 15, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, NULL, 1, 1, NULL, 1, '2012-04-02 02:50:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US', 'Does this scene look familiar? You''ve probably seen it in a couple of John Ford westerns!', 'Does this scene look familiar? You''ve probably seen it in a couple of John Ford westerns!', 1, 'John Ford Point', 'john-ford-point', 8, 9, NULL, NULL, NULL, 'http://fplanque.com/photo/monument-valley', 14, 'noreq', NULL, 0, 16, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, NULL, 1, 1, NULL, 1, '2012-04-02 02:50:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US', 'This is one of the most famous views in Monument Valley. I like to frame it with the dirt road in order to give a better idea of the size of those things!', 'This is one of the most famous views in Monument Valley. I like to frame it with the dirt road in order to give a better idea of the size of those things!', 1, 'Monuments', 'monuments', 10, 11, NULL, NULL, NULL, 'http://fplanque.com/photo/monument-valley', 14, 'noreq', NULL, 0, 33, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, NULL, 1, 1, NULL, 1, '2012-04-02 02:50:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US', 'This gives a pretty good idea of the Monuments you''re about to drive into...', 'This gives a pretty good idea of the Monuments you''re about to drive into...', 1, 'Road to Monument Valley', 'road-to-monument-valley', 12, 13, NULL, NULL, NULL, 'http://fplanque.com/photo/monument-valley', 14, 'noreq', NULL, 0, 14, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, NULL, 1, 1, NULL, 1, '2012-04-02 02:50:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US', 'This is a short photo album demo. Use the arrows to navigate between photos. Click on "Index" to see a thumbnail index.', 'This is a short photo album demo. Use the arrows to navigate between photos. Click on "Index" to see a thumbnail index.', 1, 'Monument Valley', 'monument-valley', 14, 15, NULL, NULL, NULL, 'http://fplanque.com/photo/monument-valley', 14, 'noreq', NULL, 0, 22, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, NULL, 1, 1, NULL, 1, '2012-04-02 02:51:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US', '', NULL, NULL, 'Danny', 'danny', 16, 17, NULL, NULL, NULL, 'http://personman.com/', 13, 'noreq', NULL, 0, 0, 'disabled', NULL, '', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, NULL, 1, 1, NULL, 1, '2012-04-02 02:50:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'de-DE', '', NULL, NULL, 'Daniel', 'daniel', 18, 19, NULL, NULL, NULL, 'http://daniel.hahler.de/', 13, 'noreq', NULL, 0, 0, 'disabled', NULL, '', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, NULL, 1, 1, NULL, 1, '2012-04-02 02:50:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'fr-FR', '', NULL, NULL, 'Francois', 'francois', 20, 21, NULL, NULL, NULL, 'http://fplanque.com/', 13, 'noreq', NULL, 0, 0, 'disabled', NULL, '', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, NULL, 1, 1, NULL, 1, '2012-04-02 02:50:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'de-DE', '', NULL, NULL, 'Tilman', 'tilman', 22, 23, NULL, NULL, NULL, 'http://ax86.net/', 13, 'noreq', NULL, 0, 0, 'disabled', NULL, '', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, NULL, 1, 1, NULL, 1, '2012-04-02 02:50:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US', '', NULL, NULL, 'Blog news', 'blog-news', 24, 25, NULL, NULL, NULL, 'http://b2evolution.net/news.php', 12, 'noreq', NULL, 0, 0, 'disabled', NULL, '', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, NULL, 1, 1, NULL, 1, '2012-04-02 02:50:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US', '', NULL, NULL, 'Web hosting', 'web-hosting', 26, 27, NULL, NULL, NULL, 'http://b2evolution.net/web-hosting/blog/', 12, 'noreq', NULL, 0, 0, 'disabled', NULL, '', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, NULL, 1, 1, NULL, 1, '2012-04-02 02:50:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US', '', NULL, NULL, 'Manual', 'manual', 28, 29, NULL, NULL, NULL, 'http://manual.b2evolution.net/', 12, 'noreq', NULL, 0, 0, 'disabled', NULL, '', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, NULL, 1, 1, NULL, 1, '2012-04-02 02:50:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US', '', NULL, NULL, 'Support', 'support', 30, 31, NULL, NULL, NULL, 'http://forums.b2evolution.net/', 12, 'noreq', NULL, 0, 0, 'disabled', NULL, '', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, NULL, 1, 1, NULL, 1, '2012-04-02 02:50:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1000, 'en-US-utf8', '<p>This blog is powered by b2evolution.</p>\n\n<p>You are currently looking at an info page about Blog B.</p>\n\n<p>Info pages are very much like regular posts, except that they do not appear in the regular flow of posts. They appear as info pages in the sidebar instead.</p>\n\n<p>If needed, an evoskin can format info pages differently from regular posts.</p>', 'This blog is powered by b2evolution.\n\nYou are currently looking at an info page about Blog B.\n\nInfo pages are very much like regular posts, except that they do not appear in the regular flow of posts. They appear as info pages in the sidebar instead.\n\nI&hellip;', 1, 'About Blog B', 'about-blog-b', 32, 33, NULL, NULL, NULL, '', 10, 'noreq', NULL, 0, 58, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, NULL, 1, 1, NULL, 1, '2012-04-02 02:50:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1000, 'en-US-utf8', '<p>This blog is powered by b2evolution.</p>\n\n<p>You are currently looking at an info page about Blog A.</p>\n\n<p>Info pages are very much like regular posts, except that they do not appear in the regular flow of posts. They appear as info pages in the sidebar instead.</p>\n\n<p>If needed, an evoskin can format info pages differently from regular posts.</p>', 'This blog is powered by b2evolution.\n\nYou are currently looking at an info page about Blog A.\n\nInfo pages are very much like regular posts, except that they do not appear in the regular flow of posts. They appear as info pages in the sidebar instead.\n\nI&hellip;', 1, 'About Blog A', 'about-blog-a', 34, 35, NULL, NULL, NULL, '', 1, 'noreq', NULL, 0, 58, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, NULL, 1, 1, NULL, 1, '2012-04-02 02:50:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1000, 'en-US-utf8', '<p>This blog platform is powered by b2evolution.</p>\n\n<p>You are currently looking at an info page about this system. It is cross-posted among the demo blogs. Thus, this page will be linked on each of these blogs.</p>\n\n<p>Info pages are very much like regular posts, except that they do not appear in the regular flow of posts. They appear as info pages in the sidebar instead.</p>\n\n<p>If needed, an evoskin can format info pages differently from regular posts.</p>', 'This blog platform is powered by b2evolution.\n\nYou are currently looking at an info page about this system. It is cross-posted among the demo blogs. Thus, this page will be linked on each of these blogs.\n\nInfo pages are very much like regular posts, exc&hellip;', 1, 'About this system', 'about-this-system', 36, 37, NULL, NULL, NULL, '', 1, 'noreq', NULL, 0, 77, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, NULL, 1, 1, NULL, 1, '2011-04-03 02:51:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1500, 'en-US-utf8', 'This is the main intro post. It appears on the homepage only.', 'This is the main intro post. It appears on the homepage only.', 1, 'Main Intro post', 'main-intro-post', 38, 39, NULL, NULL, NULL, '', 11, 'noreq', NULL, 0, 12, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(20, NULL, 1, 1, NULL, 1, '2011-04-03 02:51:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1520, 'en-US-utf8', 'This uses post type "Intro-Cat" and is attached to the desired Category(ies).', 'This uses post type "Intro-Cat" and is attached to the desired Category(ies).', 1, 'b2evolution tips category &ndash; Sub Intro post', 'b2evolution-tips-category-n-sub', 40, 41, NULL, NULL, NULL, '', 11, 'noreq', NULL, 0, 12, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(21, NULL, 1, 1, NULL, 1, '2011-04-03 02:51:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1530, 'en-US-utf8', 'This uses post type "Intro-Tag" and is tagged with the desired Tag(s).', 'This uses post type "Intro-Tag" and is tagged with the desired Tag(s).', 1, 'Widgets tag &ndash; Sub Intro post', 'widgets-tag-n-sub-intro', 42, 43, NULL, NULL, NULL, '', 11, 'noreq', NULL, 0, 12, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(22, NULL, 1, 1, NULL, 1, '2012-04-02 02:51:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US-utf8', '<p>This is a demo of a featured post.</p>\n\n<p>It will be featured whenever we have no specific "Intro" post to display for the current request. To see it in action, try displaying the "Announcements" category.</p>\n\n<p>Also note that when the post is featured, it does not appear in the regular post flow.</p>', 'This is a demo of a featured post.\n\nIt will be featured whenever we have no specific "Intro" post to display for the current request. To see it in action, try displaying the "Announcements" category.\n\nAlso note that when the post is featured, it does no&hellip;', 1, 'Featured post', 'featured-post', 44, 45, NULL, NULL, NULL, '', 11, 'noreq', NULL, 0, 52, 'open', NULL, 'default', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(23, NULL, 1, 1, NULL, 1, '2012-04-02 02:51:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US-utf8', '<p>b2evolution comes with an <code>.htaccess</code> file destined to optimize the way b2evolution is handled by your webseerver (if you are using Apache). In some circumstances, that file may not be automatically activated at setup. Please se the man page about <a href="http://manual.b2evolution.net/Tricky_stuff">Tricky Stuff</a> for more information.</p>\n\n<p>For further optimization, please review the manual page about <a href="http://manual.b2evolution.net/Performance_optimization">Performance optimization</a>. Depending on your current configuration and on what your <a href="http://b2evolution.net/web-hosting/">web hosting</a> company allows you to do, you may increase the speed of b2evolution by up to a factor of 10!</p>', 'b2evolution comes with an .htaccess file destined to optimize the way b2evolution is handled by your webseerver (if you are using Apache). In some circumstances, that file may not be automatically activated at setup. Please se the man page about Tricky&hellip;', 1, 'Apache optimization...', 'apache-optimization', 46, 47, NULL, NULL, NULL, '', 11, 'noreq', NULL, 0, 85, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(24, NULL, 1, 1, NULL, 1, '2012-04-02 02:51:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US-utf8', '<p>By default, blogs are displayed using an evoskin. (More on skins in another post.)</p>\n\n<p>This means, blogs are accessed through ''<code>index.php</code>'', which loads default parameters from the database and then passes on the display job to a skin.</p>\n\n<p>Alternatively, if you don''t want to use the default DB parameters and want to, say, force a skin, a category or a specific linkblog, you can create a stub file like the provided ''<code>a_stub.php</code>'' and call your blog through this stub instead of index.php .</p>\n\n<p>Finally, if you need to do some very specific customizations to your blog, you may use plain templates instead of skins. In this case, call your blog through a full template, like the provided ''<code>a_noskin.php</code>''.</p>\n\n<p>If you want to integrate a b2evolution blog into a complex website, you''ll probably want to do it by copy/pasting code from <code>a_noskin.php</code> into a page of your website.</p>\n\n<p>You will find more information in the stub/template files themselves. Open them in a text editor and read the comments in there.</p>\n\n<p>Either way, make sure you go to the blogs admin and set the correct access method/URL for your blog. Otherwise, the permalinks will not function properly.</p>', 'By default, blogs are displayed using an evoskin. (More on skins in another post.)\n\nThis means, blogs are accessed through ''index.php'', which loads default parameters from the database and then passes on the display job to a skin.\n\nAlternatively, if you&hellip;', 1, 'Skins, Stubs, Templates &amp; website integration...', 'skins-stubs-templates-website-integration', 48, 49, NULL, NULL, NULL, '', 11, 'noreq', NULL, 0, 194, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(25, NULL, 1, 1, NULL, 1, '2012-04-02 02:51:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US-utf8', '<p>b2evolution blogs are installed with a default selection of Widgets. For example, the sidebar of this blog includes widgets like a calendar, a search field, a list of categories, a list of XML feeds, etc.</p>\n\n<p>You can add, remove and reorder widgets from the Blog Settings tab in the admin interface.</p>\n\n<p>Note: in order to be displayed, widgets are placed in containers. Each container appears in a specific place in an evoskin. If you change your blog skin, the new skin may not use the same containers as the previous one. Make sure you place your widgets in containers that exist in the specific skin you are using.</p>', 'b2evolution blogs are installed with a default selection of Widgets. For example, the sidebar of this blog includes widgets like a calendar, a search field, a list of categories, a list of XML feeds, etc.\n\nYou can add, remove and reorder widgets from th&hellip;', 1, 'About widgets...', 'about-widgets', 50, 51, NULL, NULL, NULL, '', 11, 'noreq', NULL, 0, 108, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(26, NULL, 1, 1, NULL, 1, '2012-04-02 02:51:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US-utf8', '<p>By default, b2evolution blogs are displayed using an evoskin.</p>\n\n<p>You can change the skin used by any blog by editing the blog settings in the admin interface.</p>\n\n<p>You can download additional skins from the <a href="http://skins.b2evolution.net/" target="_blank">skin site</a>. To install them, unzip them in the /blogs/skins directory, then go to General Settings &gt; Skins in the admin interface and click on "Install new".</p>\n\n<p>You can also create your own skins by duplicating, renaming and customizing any existing skin folder from the /blogs/skins directory.</p>\n\n<p>To start customizing a skin, open its "<code>index.main.php</code>" file in an editor and read the comments in there. Note: you can also edit skins in the "Files" tab of the admin interface.</p>\n\n<p>And, of course, read the <a href="http://manual.b2evolution.net/Skins_2.0" target="_blank">manual on skins</a>!</p>', 'By default, b2evolution blogs are displayed using an evoskin.\n\nYou can change the skin used by any blog by editing the blog settings in the admin interface.\n\nYou can download additional skins from the skin site. To install them, unzip them in the /blogs&hellip;', 1, 'About skins...', 'about-skins', 52, 53, NULL, NULL, NULL, '', 11, 'noreq', NULL, 0, 121, 'open', NULL, 'default', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(27, NULL, 1, 1, NULL, 1, '2012-04-02 02:51:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US-utf8', '<p>This post has an image attached to it. The image is automatically resized to fit the current blog skin. You can zoom in by clicking on the thumbnail.</p>\n\n<p>Check out the photoblog (accessible through the links at the top) to see a completely different skin focused more on the photos than on the blog text.</p>', 'This post has an image attached to it. The image is automatically resized to fit the current blog skin. You can zoom in by clicking on the thumbnail.\n\nCheck out the photoblog (accessible through the links at the top) to see a completely different skin f&hellip;', 1, 'Image post', 'image-post', 54, 55, NULL, NULL, NULL, '', 3, 'noreq', NULL, 0, 55, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(28, NULL, 1, 1, NULL, 1, '2012-04-02 02:51:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US-utf8', '<p>This is page 1 of a multipage post.</p>\n\n<p>You can see the other pages by clicking on the links below the text.</p>\n\n<!--nextpage-->\n\n<p>This is page 2.</p>\n\n<!--nextpage-->\n\n<p>This is page 3.</p>\n\n<!--nextpage-->\n\n<p>This is page 4.</p>\n\n<p>It is the last page.</p>', 'This is page 1 of a multipage post.\n\nYou can see the other pages by clicking on the links below the text.\n\n\n\nThis is page 2.\n\n\n\nThis is page 3.\n\n\n\nThis is page 4.\n\nIt is the last page.', 1, 'This is a multipage post', 'this-is-a-multipage-post', 56, 57, NULL, NULL, NULL, '', 3, 'noreq', NULL, 0, 35, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(29, NULL, 1, 1, NULL, 1, '2012-04-02 02:51:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US-utf8', '<p>This is an extended post with no teaser. This means that you won''t see this teaser any more when you click the "more" link.</p>\n\n<!--more--><!--noteaser-->\n\n<p>This is the extended text. You only see it when you have clicked the "more" link.</p>', 'This is an extended post with no teaser. This means that you won''t see this teaser any more when you click the "more" link.\n\n\n\nThis is the extended text. You only see it when you have clicked the "more" link.', 1, 'Extended post with no teaser', 'extended-post-with-no-teaser', 58, 59, NULL, NULL, NULL, '', 3, 'noreq', NULL, 0, 40, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(30, NULL, 1, 1, NULL, 1, '2012-04-02 02:51:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US-utf8', '<p>This is an extended post. This means you only see this small teaser by default and you must click on the link below to see more.</p>\n\n<!--more-->\n\n<p>This is the extended text. You only see it when you have clicked the "more" link.</p>', 'This is an extended post. This means you only see this small teaser by default and you must click on the link below to see more.\n\n\n\nThis is the extended text. You only see it when you have clicked the "more" link.', 1, 'Extended post', 'extended-post', 60, 61, NULL, NULL, NULL, '', 3, 'noreq', NULL, 0, 42, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(31, NULL, 1, 1, NULL, 1, '2012-04-02 02:51:00', NULL, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 'published', NULL, 1, 'en-US-utf8', '<p>Four blogs have been created with sample contents:</p>\n\n<ul>\n	<li><strong>Blog A</strong>: You are currently looking at it. It contains a few sample posts, using simple features of b2evolution.</li>\n	<li><strong>Blog B</strong>: You can access it from a link at the top of the page. It contains information about more advanced features.</li>\n	<li><strong>Linkblog</strong>: By default, the linkblog is included as a "Blogroll" in the sidebar of both Blog A &amp; Blog B.</li>\n	<li><strong>Photoblog</strong>: This blog is an example of how you can use b2evolution to showcase photos, with one photo per page as well as a thumbnail index.</li>\n</ul>\n\n<p>You can add new blogs, delete unwanted blogs and customize existing blogs (title, sidebar, blog skin, widgets, etc.) from the Blog Settings tab in the admin interface.</p>', 'Four blogs have been created with sample contents:\n\n\n	Blog A: You are currently looking at it. It contains a few sample posts, using simple features of b2evolution.\n	Blog B: You can access it from a link at the top of the page. It contains information a&hellip;', 1, 'Welcome to b2evolution!', 'welcome-to-b2evolution', 62, 63, NULL, NULL, NULL, '', 1, 'noreq', NULL, 1, 122, 'open', NULL, 'default', 3, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}items__itemtag`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}items__itemtag` (
  `itag_itm_ID` int(11) unsigned NOT NULL,
  `itag_tag_ID` int(11) unsigned NOT NULL,
  PRIMARY KEY (`itag_itm_ID`,`itag_tag_ID`),
  UNIQUE KEY `tagitem` (`itag_tag_ID`,`itag_itm_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}items__itemtag`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}items__itemtag` (`itag_itm_ID`, `itag_tag_ID`) VALUES
(21, 1),
(24, 2),
(25, 1),
(26, 2);

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}items__prerendering`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}items__prerendering` (
  `itpr_itm_ID` int(11) unsigned NOT NULL,
  `itpr_format` enum('htmlbody','entityencoded','xml','text') NOT NULL,
  `itpr_renderers` text NOT NULL,
  `itpr_content_prerendered` mediumtext,
  `itpr_datemodified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`itpr_itm_ID`,`itpr_format`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}items__prerendering`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}items__prerendering` (`itpr_itm_ID`, `itpr_format`, `itpr_renderers`, `itpr_content_prerendered`, `itpr_datemodified`) VALUES
(27, 'htmlbody', 'b2evALnk.evo_videoplug', '<p>This post has an image attached to it. The image is automatically resized to fit the current blog skin. You can zoom in by clicking on the thumbnail.</p>\n\n<p>Check out the photoblog (accessible through the links at the top) to see a completely different skin focused more on the photos than on the blog text.</p>', '2012-04-02 02:52:28'),
(28, 'htmlbody', 'b2evALnk.evo_videoplug', '<p>This is page 1 of a multipage post.</p>\n\n<p>You can see the other pages by clicking on the links below the text.</p>\n\n<!--nextpage-->\n\n<p>This is page 2.</p>\n\n<!--nextpage-->\n\n<p>This is page 3.</p>\n\n<!--nextpage-->\n\n<p>This is page 4.</p>\n\n<p>It is the last page.</p>', '2012-04-02 02:52:28'),
(29, 'htmlbody', 'b2evALnk.evo_videoplug', '<p>This is an extended post with no teaser. This means that you won''t see this teaser any more when you click the "more" link.</p>\n\n<!--more--><!--noteaser-->\n\n<p>This is the extended text. You only see it when you have clicked the "more" link.</p>', '2012-04-02 02:52:28'),
(30, 'htmlbody', 'b2evALnk.evo_videoplug', '<p>This is an extended post. This means you only see this small teaser by default and you must click on the link below to see more.</p>\n\n<!--more-->\n\n<p>This is the extended text. You only see it when you have clicked the "more" link.</p>', '2012-04-02 02:52:28'),
(31, 'htmlbody', 'b2evALnk.evo_videoplug', '<p>Four blogs have been created with sample contents:</p>\n\n<ul>\n	<li><strong>Blog A</strong>: You are currently looking at it. It contains a few sample posts, using simple features of b2evolution.</li>\n	<li><strong>Blog B</strong>: You can access it from a link at the top of the page. It contains information about more advanced features.</li>\n	<li><strong>Linkblog</strong>: By default, the linkblog is included as a "Blogroll" in the sidebar of both Blog A &amp; Blog B.</li>\n	<li><strong>Photoblog</strong>: This blog is an example of how you can use b2evolution to showcase photos, with one photo per page as well as a thumbnail index.</li>\n</ul>\n\n<p>You can add new blogs, delete unwanted blogs and customize existing blogs (title, sidebar, blog skin, widgets, etc.) from the Blog Settings tab in the admin interface.</p>', '2012-04-02 02:52:27');

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}items__status`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}items__status` (
  `pst_ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pst_name` varchar(30) NOT NULL,
  PRIMARY KEY (`pst_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}items__status`
--


-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}items__tag`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}items__tag` (
  `tag_ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tag_name` varbinary(50) NOT NULL,
  PRIMARY KEY (`tag_ID`),
  UNIQUE KEY `tag_name` (`tag_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}items__tag`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}items__tag` (`tag_ID`, `tag_name`) VALUES
(2, 'skins'),
(1, 'widgets');

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}items__type`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}items__type` (
  `ptyp_ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ptyp_name` varchar(30) NOT NULL,
  PRIMARY KEY (`ptyp_ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5001 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}items__type`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}items__type` (`ptyp_ID`, `ptyp_name`) VALUES
(1, 'Post'),
(1000, 'Page'),
(1500, 'Intro-Main'),
(1520, 'Intro-Cat'),
(1530, 'Intro-Tag'),
(1570, 'Intro-Sub'),
(1600, 'Intro-All'),
(2000, 'Podcast'),
(3000, 'Sidebar link'),
(4000, 'Reserved'),
(5000, 'Reserved');

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}items__version`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}items__version` (
  `iver_itm_ID` int(10) unsigned NOT NULL,
  `iver_edit_user_ID` int(10) unsigned DEFAULT NULL,
  `iver_edit_datetime` datetime NOT NULL,
  `iver_status` enum('published','deprecated','protected','private','draft','redirected') DEFAULT NULL,
  `iver_title` text,
  `iver_content` mediumtext,
  KEY `iver_itm_ID` (`iver_itm_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}items__version`
--


-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}links`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}links` (
  `link_ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `link_datecreated` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `link_datemodified` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `link_creator_user_ID` int(11) unsigned NOT NULL,
  `link_lastedit_user_ID` int(11) unsigned NOT NULL,
  `link_itm_ID` int(11) unsigned NOT NULL,
  `link_dest_itm_ID` int(11) unsigned DEFAULT NULL,
  `link_file_ID` int(11) unsigned DEFAULT NULL,
  `link_ltype_ID` int(11) unsigned NOT NULL DEFAULT '1',
  `link_external_url` varchar(255) DEFAULT NULL,
  `link_title` text,
  `link_position` varchar(10) NOT NULL,
  `link_order` int(11) unsigned NOT NULL,
  PRIMARY KEY (`link_ID`),
  UNIQUE KEY `link_itm_ID_order` (`link_itm_ID`,`link_order`),
  KEY `link_itm_ID` (`link_itm_ID`),
  KEY `link_dest_itm_ID` (`link_dest_itm_ID`),
  KEY `link_file_ID` (`link_file_ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}links`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}links` (`link_ID`, `link_datecreated`, `link_datemodified`, `link_creator_user_ID`, `link_lastedit_user_ID`, `link_itm_ID`, `link_dest_itm_ID`, `link_file_ID`, `link_ltype_ID`, `link_external_url`, `link_title`, `link_position`, `link_order`) VALUES
(1, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 1, 1, 3, NULL, 2, 1, NULL, NULL, 'teaser', 1),
(2, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 1, 1, 4, NULL, 3, 1, NULL, NULL, 'teaser', 1),
(3, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 1, 1, 5, NULL, 4, 1, NULL, NULL, 'teaser', 1),
(4, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 1, 1, 6, NULL, 5, 1, NULL, NULL, 'teaser', 1),
(5, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 1, 1, 7, NULL, 6, 1, NULL, NULL, 'teaser', 1),
(6, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 1, 1, 18, NULL, 7, 1, NULL, NULL, 'teaser', 1),
(7, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 1, 1, 27, NULL, 4, 1, NULL, NULL, 'teaser', 1),
(8, '2012-04-02 02:52:39', '2012-04-02 02:52:39', 1, 1, 31, NULL, 7, 1, NULL, NULL, 'teaser', 1);

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}locales`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}locales` (
  `loc_locale` varchar(20) NOT NULL DEFAULT '',
  `loc_charset` varchar(15) NOT NULL DEFAULT 'iso-8859-1',
  `loc_datefmt` varchar(20) NOT NULL DEFAULT 'y-m-d',
  `loc_timefmt` varchar(20) NOT NULL DEFAULT 'H:i:s',
  `loc_startofweek` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `loc_name` varchar(40) NOT NULL DEFAULT '',
  `loc_messages` varchar(20) NOT NULL DEFAULT '',
  `loc_priority` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `loc_enabled` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`loc_locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='saves available locales';

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}locales`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}locales` (`loc_locale`, `loc_charset`, `loc_datefmt`, `loc_timefmt`, `loc_startofweek`, `loc_name`, `loc_messages`, `loc_priority`, `loc_enabled`) VALUES
('en-US-utf8', 'utf-8', 'm/d/y', 'h:i:s a', 0, 'English (US) utf8', 'en_US', 9, 1);

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}messaging__contact`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}messaging__contact` (
  `mct_from_user_ID` int(10) unsigned NOT NULL,
  `mct_to_user_ID` int(10) unsigned NOT NULL,
  `mct_blocked` tinyint(1) DEFAULT '0',
  `mct_last_contact_datetime` datetime NOT NULL,
  PRIMARY KEY (`mct_from_user_ID`,`mct_to_user_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}messaging__contact`
--


-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}messaging__message`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}messaging__message` (
  `msg_ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `msg_author_user_ID` int(10) unsigned NOT NULL,
  `msg_datetime` datetime NOT NULL,
  `msg_thread_ID` int(10) unsigned NOT NULL,
  `msg_text` text,
  PRIMARY KEY (`msg_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}messaging__message`
--


-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}messaging__thread`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}messaging__thread` (
  `thrd_ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `thrd_title` varchar(255) NOT NULL,
  `thrd_datemodified` datetime NOT NULL,
  PRIMARY KEY (`thrd_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}messaging__thread`
--


-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}messaging__threadstatus`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}messaging__threadstatus` (
  `tsta_thread_ID` int(10) unsigned NOT NULL,
  `tsta_user_ID` int(10) unsigned NOT NULL,
  `tsta_first_unread_msg_ID` int(10) unsigned DEFAULT NULL,
  KEY `tsta_user_ID` (`tsta_user_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}messaging__threadstatus`
--


-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}pluginevents`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}pluginevents` (
  `pevt_plug_ID` int(11) unsigned NOT NULL,
  `pevt_event` varchar(40) NOT NULL,
  `pevt_enabled` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`pevt_plug_ID`,`pevt_event`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}pluginevents`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}pluginevents` (`pevt_plug_ID`, `pevt_event`, `pevt_enabled`) VALUES
(1, 'AdminDisplayToolbar', 1),
(2, 'RenderItemAsHtml', 1),
(3, 'RenderItemAsHtml', 1),
(4, 'RenderItemAsHtml', 1),
(4, 'RenderItemAsXml', 1),
(5, 'AdminDisplayToolbar', 1),
(5, 'DisplayCommentToolbar', 1),
(5, 'FilterCommentContent', 1),
(5, 'RenderItemAsHtml', 1),
(6, 'AdminDisplayToolbar', 1),
(6, 'RenderItemAsHtml', 1),
(6, 'RenderItemAsXml', 1),
(7, 'SkinTag', 1),
(8, 'SkinTag', 1),
(9, 'ItemSendPing', 1),
(10, 'ItemSendPing', 1),
(11, 'AdminDisplayEditorButton', 1),
(12, 'ItemSendPing', 1);

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}plugins`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}plugins` (
  `plug_ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `plug_priority` tinyint(4) NOT NULL DEFAULT '50',
  `plug_classname` varchar(40) NOT NULL DEFAULT '',
  `plug_code` varchar(32) DEFAULT NULL,
  `plug_apply_rendering` enum('stealth','always','opt-out','opt-in','lazy','never') NOT NULL DEFAULT 'never',
  `plug_version` varchar(42) NOT NULL DEFAULT '0',
  `plug_name` varchar(255) DEFAULT NULL,
  `plug_shortdesc` varchar(255) DEFAULT NULL,
  `plug_status` enum('enabled','disabled','needs_config','broken') NOT NULL,
  `plug_spam_weight` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`plug_ID`),
  UNIQUE KEY `plug_code` (`plug_code`),
  KEY `plug_status` (`plug_status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=13 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}plugins`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}plugins` (`plug_ID`, `plug_priority`, `plug_classname`, `plug_code`, `plug_apply_rendering`, `plug_version`, `plug_name`, `plug_shortdesc`, `plug_status`, `plug_spam_weight`) VALUES
(1, 30, 'quicktags_plugin', 'b2evQTag', 'never', '2.4.1', NULL, NULL, 'enabled', 1),
(2, 70, 'auto_p_plugin', 'b2WPAutP', 'opt-in', '3.3', NULL, NULL, 'enabled', 1),
(3, 60, 'autolinks_plugin', 'b2evALnk', 'opt-out', '3.3.2', NULL, NULL, 'enabled', 1),
(4, 90, 'texturize_plugin', 'b2WPTxrz', 'opt-in', '2.2-dev', NULL, NULL, 'enabled', 1),
(5, 15, 'smilies_plugin', 'b2evSmil', 'opt-in', '4.0.0-dev', NULL, NULL, 'enabled', 1),
(6, 65, 'videoplug_plugin', 'evo_videoplug', 'opt-out', '2.2', NULL, NULL, 'enabled', 1),
(7, 20, 'calendar_plugin', 'evo_Calr', 'never', '3.0', NULL, NULL, 'enabled', 1),
(8, 50, 'archives_plugin', 'evo_Arch', 'never', '3.2', NULL, NULL, 'enabled', 1),
(9, 50, 'ping_b2evonet_plugin', 'ping_b2evonet', 'never', '2.4.2.1', NULL, NULL, 'enabled', 1),
(10, 50, 'ping_pingomatic_plugin', 'ping_pingomatic', 'never', '1.9-dev', NULL, NULL, 'enabled', 1),
(11, 10, 'tinymce_plugin', 'evo_TinyMCE', 'never', '3.3.0', NULL, NULL, 'enabled', 1),
(12, 50, 'twitter_plugin', 'evo_twitter', 'never', '3.2', NULL, NULL, 'enabled', 1);

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}pluginsettings`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}pluginsettings` (
  `pset_plug_ID` int(11) unsigned NOT NULL,
  `pset_name` varchar(30) NOT NULL,
  `pset_value` text,
  PRIMARY KEY (`pset_plug_ID`,`pset_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}pluginsettings`
--


-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}pluginusersettings`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}pluginusersettings` (
  `puset_plug_ID` int(11) unsigned NOT NULL,
  `puset_user_ID` int(11) unsigned NOT NULL,
  `puset_name` varchar(30) NOT NULL,
  `puset_value` text,
  PRIMARY KEY (`puset_plug_ID`,`puset_user_ID`,`puset_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}pluginusersettings`
--


-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}postcats`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}postcats` (
  `postcat_post_ID` int(11) unsigned NOT NULL,
  `postcat_cat_ID` int(11) unsigned NOT NULL,
  PRIMARY KEY (`postcat_post_ID`,`postcat_cat_ID`),
  UNIQUE KEY `catpost` (`postcat_cat_ID`,`postcat_post_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}postcats`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}postcats` (`postcat_post_ID`, `postcat_cat_ID`) VALUES
(1, 1),
(2, 1),
(2, 2),
(3, 14),
(4, 14),
(5, 14),
(6, 14),
(7, 14),
(8, 13),
(9, 13),
(10, 13),
(11, 13),
(12, 12),
(13, 12),
(14, 12),
(15, 12),
(16, 10),
(17, 1),
(18, 1),
(18, 10),
(18, 12),
(19, 11),
(20, 11),
(21, 11),
(22, 10),
(22, 11),
(23, 10),
(23, 11),
(24, 11),
(25, 11),
(26, 11),
(27, 3),
(28, 3),
(29, 3),
(30, 3),
(31, 1);

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}sessions`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}sessions` (
  `sess_ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sess_key` char(32) DEFAULT NULL,
  `sess_hitcount` int(10) unsigned NOT NULL DEFAULT '1',
  `sess_lastseen` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `sess_ipaddress` varchar(39) NOT NULL DEFAULT '',
  `sess_user_ID` int(10) DEFAULT NULL,
  `sess_data` mediumblob,
  PRIMARY KEY (`sess_ID`),
  KEY `sess_user_ID` (`sess_user_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}sessions`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}sessions` (`sess_ID`, `sess_key`, `sess_hitcount`, `sess_lastseen`, `sess_ipaddress`, `sess_user_ID`, `sess_data`) VALUES
(1, 'WUXjIKdPhPEytm8nhZF0wvm27XbfpLZ7', 6, '2012-04-02 02:52:52', '::1', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}settings`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}settings` (
  `set_name` varchar(30) NOT NULL,
  `set_value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`set_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}settings`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}settings` (`set_name`, `set_value`) VALUES
('auto_prune_stats_done', '2012-04-02 02:52:50'),
('db_version', '10000'),
('default_blog_ID', '1'),
('default_locale', 'en-US-utf8'),
('evocache_foldername', '_evocache'),
('evonet_last_attempt', '1333349570'),
('evonet_last_update', '1333349570'),
('evonet_last_version_checked', 'b2evo b2evolution 4.0.5 2011-03-23'),
('newusers_grp_ID', '4'),
('tinyurl', 'aD0');

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}skins__container`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}skins__container` (
  `sco_skin_ID` int(10) unsigned NOT NULL,
  `sco_name` varchar(40) NOT NULL,
  PRIMARY KEY (`sco_skin_ID`,`sco_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}skins__container`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}skins__container` (`sco_skin_ID`, `sco_name`) VALUES
(1, 'Header'),
(1, 'Menu'),
(1, 'Page Top'),
(1, 'Sidebar'),
(2, 'Header'),
(2, 'Menu'),
(2, 'Page Top'),
(2, 'Sidebar'),
(2, 'Sidebar 2'),
(3, 'Header'),
(3, 'Menu'),
(3, 'Page Top'),
(3, 'Sidebar'),
(4, 'Menu'),
(4, 'Page Top'),
(5, 'Header'),
(5, 'Menu'),
(5, 'Page Top'),
(5, 'Sidebar'),
(6, 'Footer'),
(6, 'Header'),
(6, 'Menu'),
(6, 'Page Top'),
(6, 'Sidebar'),
(7, 'Footer'),
(7, 'Header'),
(7, 'Menu'),
(7, 'Page Top'),
(7, 'Sidebar'),
(8, 'Header'),
(8, 'Menu'),
(8, 'Page Top'),
(8, 'Sidebar'),
(8, 'Sidebar 2'),
(9, 'Menu'),
(9, 'Page Top'),
(9, 'Sidebar'),
(10, 'Header'),
(10, 'Menu'),
(10, 'Page Top'),
(10, 'Sidebar'),
(11, 'Header'),
(11, 'Menu'),
(11, 'Page Top'),
(11, 'Sidebar'),
(12, 'Footer'),
(12, 'Header'),
(12, 'Menu'),
(12, 'Page Top'),
(12, 'Sidebar'),
(13, 'Header'),
(13, 'Menu'),
(13, 'Page Top'),
(13, 'Sidebar'),
(14, 'Footer'),
(14, 'Header'),
(14, 'Menu'),
(14, 'Page Top'),
(14, 'Sidebar'),
(15, 'Footer'),
(15, 'Header'),
(15, 'Menu'),
(15, 'Page Top'),
(15, 'Sidebar');

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}skins__skin`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}skins__skin` (
  `skin_ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `skin_name` varchar(32) NOT NULL,
  `skin_type` enum('normal','feed','sitemap') NOT NULL DEFAULT 'normal',
  `skin_folder` varchar(32) NOT NULL,
  PRIMARY KEY (`skin_ID`),
  UNIQUE KEY `skin_folder` (`skin_folder`),
  KEY `skin_name` (`skin_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=18 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}skins__skin`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}skins__skin` (`skin_ID`, `skin_name`, `skin_type`, `skin_folder`) VALUES
(1, 'evoPress', 'normal', 'evopress'),
(2, 'evocamp', 'normal', 'evocamp'),
(3, 'miami_blue', 'normal', 'miami_blue'),
(4, 'photoblog', 'normal', 'photoblog'),
(5, 'asevo', 'normal', 'asevo'),
(6, 'Custom', 'normal', 'custom'),
(7, 'Dating Mood', 'normal', 'dating_mood'),
(8, 'glossyblue', 'normal', 'glossyblue'),
(9, 'Intense', 'normal', 'intense'),
(10, 'Natural Pink', 'normal', 'natural_pink'),
(11, 'nifty_corners', 'normal', 'nifty_corners'),
(12, 'pixelgreen', 'normal', 'pixelgreen'),
(13, 'Pluralism', 'normal', 'pluralism'),
(14, 'terrafirma', 'normal', 'terrafirma'),
(15, 'vastitude', 'normal', 'vastitude'),
(16, 'Atom', 'feed', '_atom'),
(17, 'RSS 2.0', 'feed', '_rss2');

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}slug`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}slug` (
  `slug_ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug_title` varchar(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `slug_type` char(6) NOT NULL DEFAULT 'item',
  `slug_itm_ID` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`slug_ID`),
  UNIQUE KEY `slug_title` (`slug_title`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=64 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}slug`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}slug` (`slug_ID`, `slug_title`, `slug_type`, `slug_itm_ID`) VALUES
(1, 'help', 'help', NULL),
(2, 'first-post', 'item', 1),
(3, 'aA0', 'item', 1),
(4, 'second-post', 'item', 2),
(5, 'aA1', 'item', 2),
(6, 'bus-stop-ahead', 'item', 3),
(7, 'aA2', 'item', 3),
(8, 'john-ford-point', 'item', 4),
(9, 'aA3', 'item', 4),
(10, 'monuments', 'item', 5),
(11, 'aA4', 'item', 5),
(12, 'road-to-monument-valley', 'item', 6),
(13, 'aA5', 'item', 6),
(14, 'monument-valley', 'item', 7),
(15, 'aA6', 'item', 7),
(16, 'danny', 'item', 8),
(17, 'aA7', 'item', 8),
(18, 'daniel', 'item', 9),
(19, 'aA8', 'item', 9),
(20, 'francois', 'item', 10),
(21, 'aA9', 'item', 10),
(22, 'tilman', 'item', 11),
(23, 'aB0', 'item', 11),
(24, 'blog-news', 'item', 12),
(25, 'aB1', 'item', 12),
(26, 'web-hosting', 'item', 13),
(27, 'aB2', 'item', 13),
(28, 'manual', 'item', 14),
(29, 'aB3', 'item', 14),
(30, 'support', 'item', 15),
(31, 'aB4', 'item', 15),
(32, 'about-blog-b', 'item', 16),
(33, 'aB5', 'item', 16),
(34, 'about-blog-a', 'item', 17),
(35, 'aB6', 'item', 17),
(36, 'about-this-system', 'item', 18),
(37, 'aB7', 'item', 18),
(38, 'main-intro-post', 'item', 19),
(39, 'aB8', 'item', 19),
(40, 'b2evolution-tips-category-n-sub', 'item', 20),
(41, 'aB9', 'item', 20),
(42, 'widgets-tag-n-sub-intro', 'item', 21),
(43, 'aC0', 'item', 21),
(44, 'featured-post', 'item', 22),
(45, 'aC1', 'item', 22),
(46, 'apache-optimization', 'item', 23),
(47, 'aC2', 'item', 23),
(48, 'skins-stubs-templates-website-integration', 'item', 24),
(49, 'aC3', 'item', 24),
(50, 'about-widgets', 'item', 25),
(51, 'aC4', 'item', 25),
(52, 'about-skins', 'item', 26),
(53, 'aC5', 'item', 26),
(54, 'image-post', 'item', 27),
(55, 'aC6', 'item', 27),
(56, 'this-is-a-multipage-post', 'item', 28),
(57, 'aC7', 'item', 28),
(58, 'extended-post-with-no-teaser', 'item', 29),
(59, 'aC8', 'item', 29),
(60, 'extended-post', 'item', 30),
(61, 'aC9', 'item', 30),
(62, 'welcome-to-b2evolution', 'item', 31),
(63, 'aD0', 'item', 31);

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}subscriptions`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}subscriptions` (
  `sub_coll_ID` int(11) unsigned NOT NULL,
  `sub_user_ID` int(11) unsigned NOT NULL,
  `sub_items` tinyint(1) NOT NULL,
  `sub_comments` tinyint(1) NOT NULL,
  PRIMARY KEY (`sub_coll_ID`,`sub_user_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}subscriptions`
--


-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}track__goal`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}track__goal` (
  `goal_ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `goal_name` varchar(50) DEFAULT NULL,
  `goal_key` varchar(32) DEFAULT NULL,
  `goal_redir_url` varchar(255) DEFAULT NULL,
  `goal_default_value` double DEFAULT NULL,
  PRIMARY KEY (`goal_ID`),
  UNIQUE KEY `goal_key` (`goal_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}track__goal`
--


-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}track__goalhit`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}track__goalhit` (
  `ghit_ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ghit_goal_ID` int(10) unsigned NOT NULL,
  `ghit_hit_ID` int(10) unsigned NOT NULL,
  `ghit_params` text,
  PRIMARY KEY (`ghit_ID`),
  KEY `ghit_goal_ID` (`ghit_goal_ID`),
  KEY `ghit_hit_ID` (`ghit_hit_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}track__goalhit`
--


-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}track__keyphrase`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}track__keyphrase` (
  `keyp_ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `keyp_phrase` varchar(255) NOT NULL,
  PRIMARY KEY (`keyp_ID`),
  UNIQUE KEY `keyp_phrase` (`keyp_phrase`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}track__keyphrase`
--


-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}users`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}users` (
  `user_ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_login` varchar(20) NOT NULL,
  `user_pass` char(32) NOT NULL,
  `user_firstname` varchar(50) DEFAULT NULL,
  `user_lastname` varchar(50) DEFAULT NULL,
  `user_nickname` varchar(50) DEFAULT NULL,
  `user_icq` int(11) unsigned DEFAULT NULL,
  `user_email` varchar(255) NOT NULL,
  `user_url` varchar(255) DEFAULT NULL,
  `user_ip` varchar(15) DEFAULT NULL,
  `user_domain` varchar(200) DEFAULT NULL,
  `user_browser` varchar(200) DEFAULT NULL,
  `dateYMDhour` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `user_level` int(10) unsigned NOT NULL DEFAULT '0',
  `user_aim` varchar(50) DEFAULT NULL,
  `user_msn` varchar(100) DEFAULT NULL,
  `user_yim` varchar(50) DEFAULT NULL,
  `user_locale` varchar(20) NOT NULL DEFAULT 'en-EU',
  `user_idmode` varchar(20) NOT NULL DEFAULT 'login',
  `user_allow_msgform` tinyint(4) NOT NULL DEFAULT '2',
  `user_notify` tinyint(1) NOT NULL DEFAULT '0',
  `user_showonline` tinyint(1) NOT NULL DEFAULT '1',
  `user_grp_ID` int(4) NOT NULL DEFAULT '1',
  `user_validated` tinyint(1) NOT NULL DEFAULT '0',
  `user_avatar_file_ID` int(10) unsigned DEFAULT NULL,
  `user_ctry_ID` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`user_ID`),
  UNIQUE KEY `user_login` (`user_login`),
  KEY `user_grp_ID` (`user_grp_ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}users`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}users` (`user_ID`, `user_login`, `user_pass`, `user_firstname`, `user_lastname`, `user_nickname`, `user_icq`, `user_email`, `user_url`, `user_ip`, `user_domain`, `user_browser`, `dateYMDhour`, `user_level`, `user_aim`, `user_msn`, `user_yim`, `user_locale`, `user_idmode`, `user_allow_msgform`, `user_notify`, `user_showonline`, `user_grp_ID`, `user_validated`, `user_avatar_file_ID`, `user_ctry_ID`) VALUES
(1, 'admin', '50d334f45e88e853ee0d68092e08de75', NULL, NULL, 'admin', NULL, 'postmaster@localhost', NULL, '127.0.0.1', 'localhost', NULL, '2012-04-02 02:50:39', 10, NULL, NULL, NULL, 'en-US-utf8', 'login', 3, 0, 1, 1, 1, 1, NULL),
(2, 'ablogger', '50d334f45e88e853ee0d68092e08de75', NULL, NULL, 'Blogger A', NULL, 'postmaster@localhost', NULL, '127.0.0.1', 'localhost', NULL, '2012-04-02 02:50:40', 1, NULL, NULL, NULL, 'en-US-utf8', 'login', 3, 0, 1, 3, 1, NULL, NULL),
(3, 'demouser', '50d334f45e88e853ee0d68092e08de75', NULL, NULL, 'Mr. Demo', NULL, 'postmaster@localhost', NULL, '127.0.0.1', 'localhost', NULL, '2012-04-02 02:50:41', 0, NULL, NULL, NULL, 'en-US-utf8', 'login', 2, 0, 1, 4, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}users__fielddefs`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}users__fielddefs` (
  `ufdf_ID` int(10) unsigned NOT NULL,
  `ufdf_type` char(8) NOT NULL,
  `ufdf_name` varchar(255) NOT NULL,
  PRIMARY KEY (`ufdf_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}users__fielddefs`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}users__fielddefs` (`ufdf_ID`, `ufdf_type`, `ufdf_name`) VALUES
(10000, 'email', 'MSN/Live IM'),
(10100, 'word', 'Yahoo IM'),
(10200, 'word', 'AOL AIM'),
(10300, 'number', 'ICQ ID'),
(40000, 'phone', 'Skype'),
(50000, 'phone', 'Main phone'),
(50100, 'phone', 'Cell phone'),
(50200, 'phone', 'Office phone'),
(50300, 'phone', 'Home phone'),
(60000, 'phone', 'Office FAX'),
(60100, 'phone', 'Home FAX'),
(100000, 'url', 'Website'),
(100100, 'url', 'Blog'),
(110000, 'url', 'Linkedin'),
(120000, 'url', 'Twitter'),
(130100, 'url', 'Facebook'),
(130200, 'url', 'Myspace'),
(140000, 'url', 'Flickr'),
(150000, 'url', 'YouTube'),
(160000, 'url', 'Digg'),
(160100, 'url', 'StumbleUpon'),
(200000, 'text', 'Role'),
(200100, 'text', 'Organization'),
(200200, 'text', 'Division'),
(211000, 'text', 'VAT ID'),
(300000, 'text', 'Main address'),
(300300, 'text', 'Home address');

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}users__fields`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}users__fields` (
  `uf_ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uf_user_ID` int(10) unsigned NOT NULL,
  `uf_ufdf_ID` int(10) unsigned NOT NULL,
  `uf_varchar` varchar(255) NOT NULL,
  PRIMARY KEY (`uf_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}users__fields`
--


-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}users__usersettings`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}users__usersettings` (
  `uset_user_ID` int(11) unsigned NOT NULL,
  `uset_name` varchar(30) NOT NULL,
  `uset_value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`uset_user_ID`,`uset_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}users__usersettings`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}users__usersettings` (`uset_user_ID`, `uset_name`, `uset_value`) VALUES
(1, 'login_multiple_sessions', '1');

-- --------------------------------------------------------

--
-- Table structure for table `{{{EVO_TABLE_PREFIX}}}widget`
--

CREATE TABLE IF NOT EXISTS `{{{EVO_TABLE_PREFIX}}}widget` (
  `wi_ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wi_coll_ID` int(11) unsigned NOT NULL,
  `wi_sco_name` varchar(40) NOT NULL,
  `wi_order` int(10) NOT NULL,
  `wi_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `wi_type` enum('core','plugin') NOT NULL DEFAULT 'core',
  `wi_code` varchar(32) NOT NULL,
  `wi_params` text,
  PRIMARY KEY (`wi_ID`),
  UNIQUE KEY `wi_order` (`wi_coll_ID`,`wi_sco_name`,`wi_order`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=85 ;

--
-- Dumping data for table `{{{EVO_TABLE_PREFIX}}}widget`
--

INSERT INTO `{{{EVO_TABLE_PREFIX}}}widget` (`wi_ID`, `wi_coll_ID`, `wi_sco_name`, `wi_order`, `wi_enabled`, `wi_type`, `wi_code`, `wi_params`) VALUES
(1, 1, 'Page Top', 1, 1, 'core', 'colls_list_public', NULL),
(2, 1, 'Header', 1, 1, 'core', 'coll_title', NULL),
(3, 1, 'Header', 2, 1, 'core', 'coll_tagline', NULL),
(4, 1, 'Menu', 1, 1, 'core', 'menu_link', 'a:1:{s:9:"link_type";s:4:"home";}'),
(5, 1, 'Menu', 2, 1, 'core', 'coll_page_list', NULL),
(6, 1, 'Menu', 3, 1, 'core', 'menu_link', 'a:1:{s:9:"link_type";s:12:"ownercontact";}'),
(7, 1, 'Menu', 4, 1, 'core', 'menu_link', 'a:1:{s:9:"link_type";s:5:"login";}'),
(8, 1, 'Menu Top', 1, 1, 'core', 'coll_search_form', NULL),
(9, 1, 'Sidebar', 10, 1, 'core', 'coll_avatar', NULL),
(10, 1, 'Sidebar', 30, 1, 'core', 'coll_title', NULL),
(11, 1, 'Sidebar', 40, 1, 'core', 'coll_longdesc', NULL),
(12, 1, 'Sidebar', 50, 1, 'core', 'coll_common_links', NULL),
(13, 1, 'Sidebar', 60, 1, 'core', 'coll_search_form', NULL),
(14, 1, 'Sidebar', 70, 1, 'core', 'coll_category_list', NULL),
(15, 1, 'Sidebar', 80, 1, 'core', 'coll_media_index', 'a:11:{s:5:"title";s:12:"Random photo";s:10:"thumb_size";s:11:"fit-160x120";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"1";s:5:"limit";s:1:"1";s:8:"order_by";s:4:"RAND";s:9:"order_dir";s:3:"ASC";s:7:"blog_ID";s:1:"4";s:11:"widget_name";s:12:"Random photo";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}'),
(16, 1, 'Sidebar', 90, 1, 'core', 'linkblog', 'a:1:{s:7:"blog_ID";i:3;}'),
(17, 1, 'Sidebar', 100, 1, 'core', 'coll_xml_feeds', NULL),
(18, 1, 'Sidebar 2', 1, 1, 'core', 'coll_post_list', NULL),
(19, 1, 'Sidebar 2', 2, 1, 'core', 'coll_comment_list', NULL),
(20, 1, 'Sidebar 2', 3, 1, 'core', 'coll_media_index', 'a:11:{s:5:"title";s:13:"Recent photos";s:10:"thumb_size";s:10:"crop-80x80";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"3";s:5:"limit";s:1:"9";s:8:"order_by";s:9:"datestart";s:9:"order_dir";s:4:"DESC";s:7:"blog_ID";s:1:"4";s:11:"widget_name";s:11:"Photo index";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}'),
(21, 1, 'Sidebar 2', 4, 1, 'core', 'free_html', 'a:5:{s:5:"title";s:9:"Sidebar 2";s:7:"content";s:162:"This is the "Sidebar 2" container. You can place any widget you like in here. In the evo toolbar at the top of this page, select "Customize", then "Blog Widgets".";s:11:"widget_name";s:9:"Free HTML";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}'),
(22, 2, 'Page Top', 1, 1, 'core', 'colls_list_public', NULL),
(23, 2, 'Header', 1, 1, 'core', 'coll_title', NULL),
(24, 2, 'Header', 2, 1, 'core', 'coll_tagline', NULL),
(25, 2, 'Menu', 1, 1, 'core', 'menu_link', 'a:1:{s:9:"link_type";s:4:"home";}'),
(26, 2, 'Menu', 2, 1, 'core', 'coll_page_list', NULL),
(27, 2, 'Menu', 3, 1, 'core', 'menu_link', 'a:1:{s:9:"link_type";s:12:"ownercontact";}'),
(28, 2, 'Menu', 4, 1, 'core', 'menu_link', 'a:1:{s:9:"link_type";s:5:"login";}'),
(29, 2, 'Menu Top', 1, 1, 'core', 'coll_search_form', NULL),
(30, 2, 'Sidebar', 10, 1, 'core', 'coll_avatar', NULL),
(31, 2, 'Sidebar', 20, 1, 'plugin', 'evo_Calr', NULL),
(32, 2, 'Sidebar', 30, 1, 'core', 'coll_title', NULL),
(33, 2, 'Sidebar', 40, 1, 'core', 'coll_longdesc', NULL),
(34, 2, 'Sidebar', 50, 1, 'core', 'coll_common_links', NULL),
(35, 2, 'Sidebar', 60, 1, 'core', 'coll_search_form', NULL),
(36, 2, 'Sidebar', 70, 1, 'core', 'coll_category_list', NULL),
(37, 2, 'Sidebar', 90, 1, 'core', 'linkblog', 'a:1:{s:7:"blog_ID";i:3;}'),
(38, 2, 'Sidebar', 100, 1, 'core', 'coll_xml_feeds', NULL),
(39, 2, 'Sidebar 2', 1, 1, 'core', 'coll_post_list', NULL),
(40, 2, 'Sidebar 2', 2, 1, 'core', 'coll_comment_list', NULL),
(41, 2, 'Sidebar 2', 3, 1, 'core', 'coll_media_index', 'a:11:{s:5:"title";s:13:"Recent photos";s:10:"thumb_size";s:10:"crop-80x80";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"3";s:5:"limit";s:1:"9";s:8:"order_by";s:9:"datestart";s:9:"order_dir";s:4:"DESC";s:7:"blog_ID";s:1:"4";s:11:"widget_name";s:11:"Photo index";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}'),
(42, 2, 'Sidebar 2', 4, 1, 'core', 'free_html', 'a:5:{s:5:"title";s:9:"Sidebar 2";s:7:"content";s:162:"This is the "Sidebar 2" container. You can place any widget you like in here. In the evo toolbar at the top of this page, select "Customize", then "Blog Widgets".";s:11:"widget_name";s:9:"Free HTML";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}'),
(43, 3, 'Page Top', 1, 1, 'core', 'colls_list_public', NULL),
(44, 3, 'Header', 1, 1, 'core', 'coll_title', NULL),
(45, 3, 'Header', 2, 1, 'core', 'coll_tagline', NULL),
(46, 3, 'Menu', 1, 1, 'core', 'menu_link', 'a:1:{s:9:"link_type";s:4:"home";}'),
(47, 3, 'Menu', 2, 1, 'core', 'coll_page_list', NULL),
(48, 3, 'Menu', 3, 1, 'core', 'menu_link', 'a:1:{s:9:"link_type";s:12:"ownercontact";}'),
(49, 3, 'Menu', 4, 1, 'core', 'menu_link', 'a:1:{s:9:"link_type";s:5:"login";}'),
(50, 3, 'Menu Top', 1, 1, 'core', 'coll_search_form', NULL),
(51, 3, 'Sidebar', 10, 1, 'core', 'coll_avatar', NULL),
(52, 3, 'Sidebar', 20, 1, 'plugin', 'evo_Calr', NULL),
(53, 3, 'Sidebar', 30, 1, 'core', 'coll_title', NULL),
(54, 3, 'Sidebar', 40, 1, 'core', 'coll_longdesc', NULL),
(55, 3, 'Sidebar', 50, 1, 'core', 'coll_common_links', NULL),
(56, 3, 'Sidebar', 60, 1, 'core', 'coll_search_form', NULL),
(57, 3, 'Sidebar', 70, 1, 'core', 'coll_category_list', NULL),
(58, 3, 'Sidebar', 80, 1, 'core', 'coll_media_index', 'a:11:{s:5:"title";s:12:"Random photo";s:10:"thumb_size";s:11:"fit-160x120";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"1";s:5:"limit";s:1:"1";s:8:"order_by";s:4:"RAND";s:9:"order_dir";s:3:"ASC";s:7:"blog_ID";s:1:"4";s:11:"widget_name";s:12:"Random photo";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}'),
(59, 3, 'Sidebar', 100, 1, 'core', 'coll_xml_feeds', NULL),
(60, 3, 'Sidebar 2', 1, 1, 'core', 'coll_post_list', NULL),
(61, 3, 'Sidebar 2', 2, 1, 'core', 'coll_comment_list', NULL),
(62, 3, 'Sidebar 2', 3, 1, 'core', 'coll_media_index', 'a:11:{s:5:"title";s:13:"Recent photos";s:10:"thumb_size";s:10:"crop-80x80";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"3";s:5:"limit";s:1:"9";s:8:"order_by";s:9:"datestart";s:9:"order_dir";s:4:"DESC";s:7:"blog_ID";s:1:"4";s:11:"widget_name";s:11:"Photo index";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}'),
(63, 3, 'Sidebar 2', 4, 1, 'core', 'free_html', 'a:5:{s:5:"title";s:9:"Sidebar 2";s:7:"content";s:162:"This is the "Sidebar 2" container. You can place any widget you like in here. In the evo toolbar at the top of this page, select "Customize", then "Blog Widgets".";s:11:"widget_name";s:9:"Free HTML";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}'),
(64, 4, 'Page Top', 1, 1, 'core', 'colls_list_public', NULL),
(65, 4, 'Header', 1, 1, 'core', 'coll_title', NULL),
(66, 4, 'Header', 2, 1, 'core', 'coll_tagline', NULL),
(67, 4, 'Menu', 1, 1, 'core', 'menu_link', 'a:1:{s:9:"link_type";s:4:"home";}'),
(68, 4, 'Menu', 2, 1, 'core', 'coll_page_list', NULL),
(69, 4, 'Menu', 3, 1, 'core', 'menu_link', 'a:1:{s:9:"link_type";s:12:"ownercontact";}'),
(70, 4, 'Menu', 4, 1, 'core', 'menu_link', 'a:1:{s:9:"link_type";s:5:"login";}'),
(71, 4, 'Menu Top', 1, 1, 'core', 'coll_search_form', NULL),
(72, 4, 'Sidebar', 10, 1, 'core', 'coll_avatar', NULL),
(73, 4, 'Sidebar', 20, 1, 'plugin', 'evo_Calr', NULL),
(74, 4, 'Sidebar', 30, 1, 'core', 'coll_title', NULL),
(75, 4, 'Sidebar', 40, 1, 'core', 'coll_longdesc', NULL),
(76, 4, 'Sidebar', 50, 1, 'core', 'coll_common_links', NULL),
(77, 4, 'Sidebar', 60, 1, 'core', 'coll_search_form', NULL),
(78, 4, 'Sidebar', 70, 1, 'core', 'coll_category_list', NULL),
(79, 4, 'Sidebar', 80, 1, 'core', 'coll_media_index', 'a:11:{s:5:"title";s:12:"Random photo";s:10:"thumb_size";s:11:"fit-160x120";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"1";s:5:"limit";s:1:"1";s:8:"order_by";s:4:"RAND";s:9:"order_dir";s:3:"ASC";s:7:"blog_ID";s:1:"4";s:11:"widget_name";s:12:"Random photo";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}'),
(80, 4, 'Sidebar', 100, 1, 'core', 'coll_xml_feeds', NULL),
(81, 4, 'Sidebar 2', 1, 1, 'core', 'coll_post_list', NULL),
(82, 4, 'Sidebar 2', 2, 1, 'core', 'coll_comment_list', NULL),
(83, 4, 'Sidebar 2', 3, 1, 'core', 'coll_media_index', 'a:11:{s:5:"title";s:13:"Recent photos";s:10:"thumb_size";s:10:"crop-80x80";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"3";s:5:"limit";s:1:"9";s:8:"order_by";s:9:"datestart";s:9:"order_dir";s:4:"DESC";s:7:"blog_ID";s:1:"4";s:11:"widget_name";s:11:"Photo index";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}'),
(84, 4, 'Sidebar 2', 4, 1, 'core', 'free_html', 'a:5:{s:5:"title";s:9:"Sidebar 2";s:7:"content";s:162:"This is the "Sidebar 2" container. You can place any widget you like in here. In the evo toolbar at the top of this page, select "Customize", then "Blog Widgets".";s:11:"widget_name";s:9:"Free HTML";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
