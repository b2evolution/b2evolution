# phpMyAdmin MySQL-Dump
# version 2.3.3pl1
# http://www.phpmyadmin.net/ (download page)
#
# Host: localhost
# Generation Time: Oct 30, 2006 at 10:59 PM
# Server version: 3.23.57
# PHP Version: 4.3.0
# Database : `dh4384`
# --------------------------------------------------------

#
# Table structure for table `b2categories`
#

CREATE TABLE b2categories (
  cat_ID int(4) NOT NULL auto_increment,
  cat_name tinytext NOT NULL,
  KEY cat_ID (cat_ID)
) TYPE=MyISAM;

#
# Dumping data for table `b2categories`
#

INSERT INTO b2categories VALUES (1, 'General Design');
INSERT INTO b2categories VALUES (2, 'Furniture');
# --------------------------------------------------------

#
# Table structure for table `b2comments`
#

CREATE TABLE b2comments (
  comment_ID int(11) unsigned NOT NULL auto_increment,
  comment_post_ID int(11) NOT NULL default '0',
  comment_author tinytext NOT NULL,
  comment_author_email varchar(100) NOT NULL default '',
  comment_author_url varchar(100) NOT NULL default '',
  comment_author_IP varchar(100) NOT NULL default '',
  comment_date datetime NOT NULL default '1000-01-01 00:00:00',
  comment_content text NOT NULL,
  comment_karma int(11) NOT NULL default '0',
  PRIMARY KEY  (comment_ID)
) TYPE=MyISAM;

#
# Dumping data for table `b2comments`
#

INSERT INTO b2comments VALUES (1, 1, 'Patrick', 'foo@example.com', 'http://url', '67.184.182.166', '2005-07-09 18:02:19', 'Hello, Just a fan of the website from Chicago. Good luck on your new move.', 0);
# --------------------------------------------------------

#
# Table structure for table `b2posts`
#

CREATE TABLE b2posts (
  ID int(10) unsigned NOT NULL auto_increment,
  post_author int(4) NOT NULL default '0',
  post_date datetime NOT NULL default '1000-01-01 00:00:00',
  post_content text NOT NULL,
  post_title text NOT NULL,
  post_category int(4) NOT NULL default '0',
  post_karma int(11) NOT NULL default '0',
  PRIMARY KEY  (ID),
  UNIQUE KEY ID (ID)
) TYPE=MyISAM;

#
# Dumping data for table `b2posts`
#

INSERT INTO b2posts VALUES (1, 1, '2004-04-02 04:32:08', '<a href=\\"http://thetanknyc.com/bent/\\">Bent</a>, a \\"week long exploration of the art of circuit bending\\" is primed to begin on April 3 in New York.  For the layman, circuit bending involves re-purposing the electronic soundmakers in toys and consumer electronics for use in custom musical instruments.  Sort of guerilla design with toys.<br />\n<img src=\\"http://thetanknyc.com/bent/images/3eyephoton.jpg\\" border=\\"0\\" alt=\\"eye-rearanger\\" />', 'Re-Hashed Toy Symphony', 9, 0);
INSERT INTO b2posts VALUES (2, 1, '2004-04-02 04:32:56', 'McDonalds has announced that it will launch a new line of <a href=\\"http://news.bbc.co.uk/1/hi/business/3567529.stm\\">children\\\'s clothing</a> in Europe and North america.<br />\n<img src=\\"http://newsimg.bbc.co.uk/media/images/39963000/jpg/_39963441_mckids203.jpg\\" border=\\"0\\" alt=\\"McKid\\" />', 'McChic Delux', 7, 0);
INSERT INTO b2posts VALUES (3, 1, '2004-04-02 04:34:26', 'According to the BBC, Sprint\\\'s new <a href=\\"http://news.bbc.co.uk/1/hi/world/americas/3575159.stm\\">corporate headquarters</a> in Kansas are designed for the cardio-fitness of it\\\'s employees.  The part about the intentionally small elevators is fabulous.  Next thing you know, Ford will release a concept based on the <a href=\\"http://www.whatacharacter.com/a-f/f--page2.htm\\">Flintstones car</a>.  Foot power!!!<br />\n', 'Slimming Architecture', 6, 0);
# --------------------------------------------------------

#
# Table structure for table `b2settings`
#

CREATE TABLE b2settings (
  ID tinyint(3) NOT NULL default '1',
  posts_per_page int(4) unsigned NOT NULL default '7',
  what_to_show varchar(5) NOT NULL default 'days',
  archive_mode varchar(10) NOT NULL default 'weekly',
  time_difference tinyint(4) NOT NULL default '0',
  AutoBR tinyint(1) NOT NULL default '1',
  time_format varchar(20) NOT NULL default 'H:i:s',
  date_format varchar(20) NOT NULL default 'Y/m/d',
  PRIMARY KEY  (ID),
  KEY ID (ID)
) TYPE=MyISAM;

#
# Dumping data for table `b2settings`
#

INSERT INTO b2settings VALUES (1, 15, 'paged', 'monthly', -6, 1, 'H:i:s', 'd.m.y');
# --------------------------------------------------------

#
# Table structure for table `b2users`
#

CREATE TABLE b2users (
  ID int(10) unsigned NOT NULL auto_increment,
  user_login varchar(20) NOT NULL default '',
  user_pass varchar(20) NOT NULL default '',
  user_firstname varchar(50) NOT NULL default '',
  user_lastname varchar(50) NOT NULL default '',
  user_nickname varchar(50) NOT NULL default '',
  user_icq int(10) unsigned NOT NULL default '0',
  user_email varchar(100) NOT NULL default '',
  user_url varchar(100) NOT NULL default '',
  user_ip varchar(15) NOT NULL default '',
  user_domain varchar(200) NOT NULL default '',
  user_browser varchar(200) NOT NULL default '',
  dateYMDhour datetime NOT NULL default '1000-01-01 00:00:00',
  user_level int(2) unsigned NOT NULL default '0',
  user_aim varchar(50) NOT NULL default '',
  user_msn varchar(100) NOT NULL default '',
  user_yim varchar(50) NOT NULL default '',
  user_idmode varchar(20) NOT NULL default '',
  PRIMARY KEY  (ID),
  UNIQUE KEY user_login (user_login),
  UNIQUE KEY ID (ID)
) TYPE=MyISAM;

#
# Dumping data for table `b2users`
#

INSERT INTO b2users VALUES (1, 'admin', 'password', 'First', 'Last', 'Nick', 0, 'foo@example.com', 'http://daniel.hahler.de/', '127.0.0.1', '127.0.0.1', '', '1000-01-01 00:00:00', 10, '', '', '', 'nickname');
INSERT INTO b2users VALUES (2, 'user1', 'password', 'Elliott', '', 'epm', 0, 'bar@example.com', 'http://www.hahler.de/', '127.0.0.1', '127.0.0.1', 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)', '2004-04-20 18:05:42', 8, '', '', '', 'firstname');

