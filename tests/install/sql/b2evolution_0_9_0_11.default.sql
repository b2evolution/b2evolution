# phpMyAdmin SQL Dump for b2evolution 0.9.0.11

# --------------------------------------------------------

#
# Table structure for table `evo_antispam`
#

CREATE TABLE evo_antispam (
  aspm_ID bigint(11) NOT NULL auto_increment,
  aspm_string varchar(80) NOT NULL default '',
  aspm_source enum('local','reported','central') NOT NULL default 'reported',
  PRIMARY KEY  (aspm_ID),
  UNIQUE KEY aspm_string (aspm_string)
) TYPE=MyISAM AUTO_INCREMENT=19 ;

#
# Dumping data for table `evo_antispam`
#

INSERT INTO evo_antispam VALUES (1, 'penis-enlargement', 'reported');
INSERT INTO evo_antispam VALUES (2, 'online-casino', 'reported');
INSERT INTO evo_antispam VALUES (3, 'order-viagra', 'reported');
INSERT INTO evo_antispam VALUES (4, 'order-phentermine', 'reported');
INSERT INTO evo_antispam VALUES (5, 'order-xenical', 'reported');
INSERT INTO evo_antispam VALUES (6, 'order-prophecia', 'reported');
INSERT INTO evo_antispam VALUES (7, 'sexy-lingerie', 'reported');
INSERT INTO evo_antispam VALUES (8, '-porn-', 'reported');
INSERT INTO evo_antispam VALUES (9, '-adult-', 'reported');
INSERT INTO evo_antispam VALUES (10, '-tits-', 'reported');
INSERT INTO evo_antispam VALUES (11, 'buy-phentermine', 'reported');
INSERT INTO evo_antispam VALUES (12, 'order-cheap-pills', 'reported');
INSERT INTO evo_antispam VALUES (13, 'buy-xenadrine', 'reported');
INSERT INTO evo_antispam VALUES (14, 'xxx', 'reported');
INSERT INTO evo_antispam VALUES (15, 'paris-hilton', 'reported');
INSERT INTO evo_antispam VALUES (16, 'parishilton', 'reported');
INSERT INTO evo_antispam VALUES (17, 'camgirls', 'reported');
INSERT INTO evo_antispam VALUES (18, 'adult-models', 'reported');

# --------------------------------------------------------

#
# Table structure for table `evo_blogs`
#

CREATE TABLE evo_blogs (
  blog_ID int(4) NOT NULL auto_increment,
  blog_shortname varchar(12) default '',
  blog_name varchar(50) NOT NULL default '',
  blog_tagline varchar(250) default '',
  blog_description varchar(250) default '',
  blog_longdesc text,
  blog_locale varchar(20) NOT NULL default 'en-EU',
  blog_access_type varchar(10) NOT NULL default 'index.php',
  blog_siteurl varchar(120) NOT NULL default '',
  blog_staticfilename varchar(30) default NULL,
  blog_stub varchar(30) NOT NULL default 'stub',
  blog_notes text,
  blog_keywords tinytext,
  blog_allowtrackbacks tinyint(1) NOT NULL default '1',
  blog_allowpingbacks tinyint(1) NOT NULL default '1',
  blog_pingb2evonet tinyint(1) NOT NULL default '0',
  blog_pingtechnorati tinyint(1) NOT NULL default '0',
  blog_pingweblogs tinyint(1) NOT NULL default '0',
  blog_pingblodotgs tinyint(1) NOT NULL default '0',
  blog_default_skin varchar(30) NOT NULL default 'custom',
  blog_force_skin tinyint(1) NOT NULL default '0',
  blog_disp_bloglist tinyint(1) NOT NULL default '1',
  blog_in_bloglist tinyint(1) NOT NULL default '1',
  blog_links_blog_ID int(4) NOT NULL default '0',
  blog_UID varchar(20) default NULL,
  PRIMARY KEY  (blog_ID),
  UNIQUE KEY blog_stub (blog_stub)
) TYPE=MyISAM AUTO_INCREMENT=5 ;

#
# Dumping data for table `evo_blogs`
#

INSERT INTO evo_blogs VALUES (1, 'Blog All', 'Blog All Title', 'Tagline for Blog All', 'Short description for Blog All', 'This is the long description for the blog named \'Blog All\'. <br />\r\n<br />\r\n<strong>This blog (blog #1) is actually a very special blog! It automatically aggregates all posts from all other blogs. This allows you to easily track everything that is posted on this system. You can hide this blog from the public by unchecking \'Include in public blog list\' in the blogs admin.</strong>', 'en-EU', 'index.php', '', 'all.html', 'all', 'Notes for Blog All', 'Keywords for Blog All', 1, 1, 0, 0, 1, 0, 'custom', 0, 1, 1, 4, '');
INSERT INTO evo_blogs VALUES (2, 'Blog A', 'Blog A Title', 'Tagline for Blog A', 'Short description for Blog A', 'This is the long description for the blog named \'Blog A\'. ', 'en-EU', 'index.php', '', 'a.html', 'a', 'Notes for Blog A', 'Keywords for Blog A', 1, 1, 0, 0, 1, 0, 'custom', 0, 1, 1, 4, '');
INSERT INTO evo_blogs VALUES (3, 'Blog B', 'Blog B Title', 'Tagline for Blog B', 'Short description for Blog B', 'This is the long description for the blog named \'Blog B\'. ', 'en-EU', 'index.php', '', 'b.html', 'b', 'Notes for Blog B', 'Keywords for Blog B', 1, 1, 0, 0, 1, 0, 'custom', 0, 1, 1, 4, '');
INSERT INTO evo_blogs VALUES (4, 'Linkblog', 'Linkblog Title', 'Tagline for Linkblog', 'Short description for Linkblog', 'This is the long description for the blog named \'Linkblog\'. <br />\r\n<br />\r\n<strong>The main purpose for this blog is to be included as a side item to other blogs where it will display your favorite/related links.</strong>', 'en-EU', 'index.php', '', 'links.html', 'links', 'Notes for Linkblog', 'Keywords for Linkblog', 1, 1, 0, 0, 1, 0, 'custom', 0, 1, 1, 0, '');

# --------------------------------------------------------

#
# Table structure for table `evo_blogusers`
#

CREATE TABLE evo_blogusers (
  bloguser_blog_ID int(11) NOT NULL default '0',
  bloguser_user_ID int(11) NOT NULL default '0',
  bloguser_ismember tinyint(4) NOT NULL default '0',
  bloguser_perm_poststatuses set('published','deprecated','protected','private','draft') NOT NULL default '',
  bloguser_perm_delpost tinyint(4) NOT NULL default '0',
  bloguser_perm_comments tinyint(4) NOT NULL default '0',
  bloguser_perm_cats tinyint(4) NOT NULL default '0',
  bloguser_perm_properties tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (bloguser_blog_ID,bloguser_user_ID)
) TYPE=MyISAM;

#
# Dumping data for table `evo_blogusers`
#

INSERT INTO evo_blogusers VALUES (1, 1, 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1);
INSERT INTO evo_blogusers VALUES (2, 1, 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1);
INSERT INTO evo_blogusers VALUES (3, 1, 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1);
INSERT INTO evo_blogusers VALUES (4, 1, 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1);
INSERT INTO evo_blogusers VALUES (2, 2, 1, 'draft', 0, 0, 0, 0);

# --------------------------------------------------------

#
# Table structure for table `evo_categories`
#

CREATE TABLE evo_categories (
  cat_ID int(4) NOT NULL auto_increment,
  cat_parent_ID int(11) default NULL,
  cat_name tinytext NOT NULL,
  cat_blog_ID int(11) NOT NULL default '2',
  cat_description varchar(250) default NULL,
  cat_longdesc text,
  cat_icon varchar(30) default NULL,
  PRIMARY KEY  (cat_ID),
  KEY cat_blog_ID (cat_blog_ID),
  KEY cat_parent_ID (cat_parent_ID)
) TYPE=MyISAM AUTO_INCREMENT=14 ;

#
# Dumping data for table `evo_categories`
#

INSERT INTO evo_categories VALUES (1, NULL, 'Announcements [A]', 2, NULL, NULL, NULL);
INSERT INTO evo_categories VALUES (2, NULL, 'News', 2, NULL, NULL, NULL);
INSERT INTO evo_categories VALUES (3, NULL, 'Background', 2, NULL, NULL, NULL);
INSERT INTO evo_categories VALUES (4, NULL, 'Announcements [B]', 3, NULL, NULL, NULL);
INSERT INTO evo_categories VALUES (5, NULL, 'Fun', 3, NULL, NULL, NULL);
INSERT INTO evo_categories VALUES (6, 5, 'In real life', 3, NULL, NULL, NULL);
INSERT INTO evo_categories VALUES (7, 5, 'On the web', 3, NULL, NULL, NULL);
INSERT INTO evo_categories VALUES (8, 6, 'Sports', 3, NULL, NULL, NULL);
INSERT INTO evo_categories VALUES (9, 6, 'Movies', 3, NULL, NULL, NULL);
INSERT INTO evo_categories VALUES (10, 6, 'Music', 3, NULL, NULL, NULL);
INSERT INTO evo_categories VALUES (11, NULL, 'b2evolution Tips', 3, NULL, NULL, NULL);
INSERT INTO evo_categories VALUES (12, NULL, 'b2evolution', 4, NULL, NULL, NULL);
INSERT INTO evo_categories VALUES (13, NULL, 'contributors', 4, NULL, NULL, NULL);

# --------------------------------------------------------

#
# Table structure for table `evo_comments`
#

CREATE TABLE evo_comments (
  comment_ID int(11) unsigned NOT NULL auto_increment,
  comment_post_ID int(11) NOT NULL default '0',
  comment_type enum('comment','linkback','trackback','pingback') NOT NULL default 'comment',
  comment_status enum('published','deprecated','protected','private','draft') NOT NULL default 'published',
  comment_author_ID int(10) unsigned default NULL,
  comment_author varchar(100) default NULL,
  comment_author_email varchar(100) default NULL,
  comment_author_url varchar(100) default NULL,
  comment_author_IP varchar(23) NOT NULL default '',
  comment_date datetime NOT NULL default '0000-00-00 00:00:00',
  comment_content text NOT NULL,
  comment_karma int(11) NOT NULL default '0',
  PRIMARY KEY  (comment_ID),
  KEY comment_post_ID (comment_post_ID),
  KEY comment_date (comment_date),
  KEY comment_type (comment_type)
) TYPE=MyISAM AUTO_INCREMENT=2 ;

#
# Dumping data for table `evo_comments`
#

INSERT INTO evo_comments VALUES (1, 1, 'comment', 'published', NULL, 'miss b2', 'missb2@example.com', 'http://example.com', '127.0.0.1', '2005-02-18 20:34:57', 'Hi, this is a comment.<br />To delete a comment, just log in, and view the posts\' comments, there you will have the option to edit or delete them.', 0);

# --------------------------------------------------------

#
# Table structure for table `evo_groups`
#

CREATE TABLE evo_groups (
  grp_ID int(11) NOT NULL auto_increment,
  grp_name varchar(50) NOT NULL default '',
  grp_perm_blogs enum('user','viewall','editall') NOT NULL default 'user',
  grp_perm_stats enum('none','view','edit') NOT NULL default 'none',
  grp_perm_spamblacklist enum('none','view','edit') NOT NULL default 'none',
  grp_perm_options enum('none','view','edit') NOT NULL default 'none',
  grp_perm_users enum('none','view','edit') NOT NULL default 'none',
  grp_perm_templates tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (grp_ID)
) TYPE=MyISAM AUTO_INCREMENT=5 ;

#
# Dumping data for table `evo_groups`
#

INSERT INTO evo_groups VALUES (1, 'Administrators', 'editall', 'edit', 'edit', 'edit', 'edit', 1);
INSERT INTO evo_groups VALUES (2, 'Priviledged Bloggers', 'viewall', 'view', 'edit', 'view', 'view', 0);
INSERT INTO evo_groups VALUES (3, 'Bloggers', 'user', 'none', 'view', 'none', 'none', 0);
INSERT INTO evo_groups VALUES (4, 'Basic Users', 'user', 'none', 'none', 'none', 'none', 0);

# --------------------------------------------------------

#
# Table structure for table `evo_hitlog`
#

CREATE TABLE evo_hitlog (
  visitID bigint(11) NOT NULL auto_increment,
  visitTime timestamp(14) NOT NULL,
  visitURL varchar(250) default NULL,
  hit_ignore enum('no','invalid','badchar','blacklist','rss','robot','search') NOT NULL default 'no',
  referingURL varchar(250) default NULL,
  baseDomain varchar(250) default NULL,
  hit_blog_ID int(11) NOT NULL default '0',
  hit_remote_addr varchar(40) default NULL,
  hit_user_agent varchar(250) default NULL,
  PRIMARY KEY  (visitID),
  KEY hit_ignore (hit_ignore),
  KEY baseDomain (baseDomain),
  KEY hit_blog_ID (hit_blog_ID),
  KEY hit_user_agent (hit_user_agent)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Dumping data for table `evo_hitlog`
#


# --------------------------------------------------------

#
# Table structure for table `evo_locales`
#

CREATE TABLE evo_locales (
  loc_locale varchar(20) NOT NULL default '',
  loc_charset varchar(15) NOT NULL default 'iso-8859-1',
  loc_datefmt varchar(10) NOT NULL default 'y-m-d',
  loc_timefmt varchar(10) NOT NULL default 'H:i:s',
  loc_name varchar(40) NOT NULL default '',
  loc_messages varchar(20) NOT NULL default '',
  loc_priority tinyint(4) unsigned NOT NULL default '0',
  loc_enabled tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (loc_locale)
) TYPE=MyISAM COMMENT='saves available locales';

#
# Dumping data for table `evo_locales`
#


# --------------------------------------------------------

#
# Table structure for table `evo_postcats`
#

CREATE TABLE evo_postcats (
  postcat_post_ID int(11) NOT NULL default '0',
  postcat_cat_ID int(11) NOT NULL default '0',
  PRIMARY KEY  (postcat_post_ID,postcat_cat_ID)
) TYPE=MyISAM;

#
# Dumping data for table `evo_postcats`
#

INSERT INTO evo_postcats VALUES (1, 1);
INSERT INTO evo_postcats VALUES (1, 4);
INSERT INTO evo_postcats VALUES (2, 1);
INSERT INTO evo_postcats VALUES (2, 2);
INSERT INTO evo_postcats VALUES (2, 3);
INSERT INTO evo_postcats VALUES (3, 5);
INSERT INTO evo_postcats VALUES (4, 13);
INSERT INTO evo_postcats VALUES (5, 13);
INSERT INTO evo_postcats VALUES (6, 13);
INSERT INTO evo_postcats VALUES (7, 13);
INSERT INTO evo_postcats VALUES (8, 13);
INSERT INTO evo_postcats VALUES (9, 13);
INSERT INTO evo_postcats VALUES (10, 13);
INSERT INTO evo_postcats VALUES (11, 12);
INSERT INTO evo_postcats VALUES (12, 12);
INSERT INTO evo_postcats VALUES (13, 11);
INSERT INTO evo_postcats VALUES (14, 11);
INSERT INTO evo_postcats VALUES (15, 11);
INSERT INTO evo_postcats VALUES (16, 11);
INSERT INTO evo_postcats VALUES (17, 11);
INSERT INTO evo_postcats VALUES (18, 3);
INSERT INTO evo_postcats VALUES (18, 11);
INSERT INTO evo_postcats VALUES (19, 3);
INSERT INTO evo_postcats VALUES (19, 11);
INSERT INTO evo_postcats VALUES (20, 3);
INSERT INTO evo_postcats VALUES (20, 11);
INSERT INTO evo_postcats VALUES (21, 1);
INSERT INTO evo_postcats VALUES (21, 4);
INSERT INTO evo_postcats VALUES (21, 11);

# --------------------------------------------------------

#
# Table structure for table `evo_posts`
#

CREATE TABLE evo_posts (
  ID int(10) unsigned NOT NULL auto_increment,
  post_author int(4) NOT NULL default '0',
  post_issue_date datetime NOT NULL default '0000-00-00 00:00:00',
  post_mod_date datetime NOT NULL default '0000-00-00 00:00:00',
  post_status enum('published','deprecated','protected','private','draft') NOT NULL default 'published',
  post_locale varchar(20) NOT NULL default 'en-EU',
  post_content text NOT NULL,
  post_title text NOT NULL,
  post_urltitle varchar(50) default NULL,
  post_url varchar(250) default NULL,
  post_category int(4) NOT NULL default '0',
  post_autobr tinyint(4) NOT NULL default '1',
  post_flags set('pingsdone','imported') default NULL,
  post_karma int(11) NOT NULL default '0',
  post_wordcount int(11) default NULL,
  post_comments enum('disabled','open','closed') NOT NULL default 'open',
  post_renderers varchar(179) NOT NULL default 'default',
  PRIMARY KEY  (ID),
  UNIQUE KEY post_urltitle (post_urltitle),
  KEY post_issue_date (post_issue_date),
  KEY post_category (post_category),
  KEY post_author (post_author),
  KEY post_status (post_status)
) TYPE=MyISAM AUTO_INCREMENT=22 ;

#
# Dumping data for table `evo_posts`
#

INSERT INTO evo_posts VALUES (1, 1, '2005-02-18 20:32:56', '1970-01-01 01:00:00', 'published', 'en-EU', '<p>This is the first post.</p>\r\n\r\n<p>It appears on both blog A and blog B.</p>', 'First Post', 'first_post', '', 1, 0, 'pingsdone', 0, 14, 'open', 'default');
INSERT INTO evo_posts VALUES (2, 1, '2005-02-18 20:32:57', '1970-01-01 01:00:00', 'published', 'en-EU', '<p>This is the second post.</p>\r\n\r\n<p>It appears on blog A only but in multiple categories.</p>', 'Second post', 'second_post', '', 2, 0, 'pingsdone', 0, 15, 'open', 'default');
INSERT INTO evo_posts VALUES (3, 1, '2005-02-18 20:32:58', '1970-01-01 01:00:00', 'published', 'en-EU', '<p>This is the third post.</p>\r\n\r\n<p>It appears on blog B only and in a single category.</p>', 'Third post', 'third_post', '', 5, 0, 'pingsdone', 0, 16, 'open', 'default');
INSERT INTO evo_posts VALUES (4, 1, '2005-02-18 20:32:59', '1970-01-01 01:00:00', 'published', 'nl-NL', 'Testing', 'Topanga', 'topanga', 'http://www.tenderfeelings.be/', 13, 0, 'pingsdone', 0, 1, 'disabled', '');
INSERT INTO evo_posts VALUES (5, 1, '2005-02-18 20:33:00', '1970-01-01 01:00:00', 'published', 'en-US', 'Hosting', 'Travis Swicegood', 'travis_swicegood', 'http://www.fromthecrossroads.ws/', 13, 0, 'pingsdone', 0, 1, 'disabled', '');
INSERT INTO evo_posts VALUES (6, 1, '2005-02-18 20:33:01', '1970-01-01 01:00:00', 'published', 'en-UK', 'Hosting', 'Welby', 'welby', 'http://www.wheely-bin.co.uk/', 13, 0, 'pingsdone', 0, 1, 'disabled', '');
INSERT INTO evo_posts VALUES (7, 1, '2005-02-18 20:33:02', '1970-01-01 01:00:00', 'published', 'en-UK', 'Testing', 'Graham', 'graham', 'http://tin-men.net/', 13, 0, 'pingsdone', 0, 1, 'disabled', '');
INSERT INTO evo_posts VALUES (8, 1, '2005-02-18 20:33:03', '1970-01-01 01:00:00', 'published', 'en-UK', 'Support', 'Isaac', 'isaac', 'http://isaacschlueter.com/', 13, 0, 'pingsdone', 0, 1, 'disabled', '');
INSERT INTO evo_posts VALUES (9, 1, '2005-02-18 20:33:04', '1970-01-01 01:00:00', 'published', 'de-DE', 'Development', 'dAniel', 'daniel', 'http://thequod.de/', 13, 0, 'pingsdone', 0, 1, 'disabled', '');
INSERT INTO evo_posts VALUES (10, 1, '2005-02-18 20:33:05', '1970-01-01 01:00:00', 'published', 'fr-FR', 'Main dev', 'François', 'francois', 'http://fplanque.net/Blog/', 13, 0, 'pingsdone', 0, 2, 'disabled', '');
INSERT INTO evo_posts VALUES (11, 1, '2005-02-18 20:33:06', '1970-01-01 01:00:00', 'published', 'en-EU', 'Project home', 'b2evolution', 'b2evolution', 'http://b2evolution.net/', 12, 0, 'pingsdone', 0, 2, 'disabled', '');
INSERT INTO evo_posts VALUES (12, 1, '2005-02-18 20:33:07', '1970-01-01 01:00:00', 'published', 'en-EU', 'This is sample text describing the linkblog entry. In most cases however, you\'ll want to leave this blank, providing just a Title and an Url for your linkblog entries (favorite/related sites).', 'This is a sample linkblog entry', 'this_is_a_sample_linkblog_entry', 'http://b2evolution.net/', 12, 0, 'pingsdone', 0, 32, 'disabled', '');
INSERT INTO evo_posts VALUES (13, 1, '2005-02-18 20:33:08', '1970-01-01 01:00:00', 'published', 'en-EU', 'b2evolution uses old-style permalinks and feedback links by default. This is to ensure maximum compatibility with various webserver configurations.\r\n\r\nNethertheless, once you feel comfortable with b2evolution, you should try activating clean permalinks in the Settings screen... (check \'Use extra-path info\')', 'Clean Permalinks!', 'clean_permalinks', '', 11, 0, 'pingsdone', 0, 42, 'open', 'default');
INSERT INTO evo_posts VALUES (14, 1, '2005-02-18 20:33:09', '1970-01-01 01:00:00', 'published', 'en-EU', 'In the <code>/blogs</code> folder as well as in <code>/blogs/admin</code> there are two files called [<code>sample.htaccess</code>]. You should try renaming those to [<code>.htaccess</code>].\r\n\r\nThis will optimize the way b2evolution is handled by the webserver (if you are using Apache). These files are not active by default because a few hosts would display an error right away when you try to use them. If this happens to you when you rename the files, just remove them and you\'ll be fine.', 'Apache optimization...', 'apache_optimization', '', 11, 0, 'pingsdone', 0, 81, 'open', 'default');
INSERT INTO evo_posts VALUES (15, 1, '2005-02-18 20:33:10', '1970-01-01 01:00:00', 'published', 'en-EU', 'By default, b2evolution blogs are displayed using a default skin.\r\n\r\nReaders can choose a new skin by using the skin switcher integrated in most skins.\r\n\r\nYou can change the default skin used for any blog by editing the blog parameters in the admin interface. You can also force the use of the default skin for everyone.\r\n\r\nOtherwise, you can restrict available skins by deleting some of them from the /blogs/skins folder. You can also create new skins by duplicating, renaming and customizing any existing skin folder.\r\n\r\nTo start customizing a skin, open its \'<code>_main.php</code>\' file in an editor and read the comments in there. And, of course, read the manual on evoSkins!', 'About evoSkins...', 'about_evoskins', '', 11, 0, 'pingsdone', 0, 115, 'open', 'default');
INSERT INTO evo_posts VALUES (16, 1, '2005-02-18 20:33:11', '1970-01-01 01:00:00', 'published', 'en-EU', 'By default, all pre-installed blogs are displayed using a skin. (More on skins in another post.)\r\n\r\nThat means, blogs are accessed through \'<code>index.php</code>\', which loads default parameters from the database and then passes on the display job to a skin.\r\n\r\nAlternatively, if you don\'t want to use the default DB parameters and want to, say, force a skin, a category or a specific linkblog, you can create a stub file like the provided \'<code>a_stub.php</code>\' and call your blog through this stub instead of index.php .\r\n\r\nFinally, if you need to do some very specific customizations to your blog, you may use plain templates instead of skins. In this case, call your blog through a full template, like the provided \'<code>a_noskin.php</code>\'.\r\n\r\nYou will find more information in the stub/template files themselves. Open them in a text editor and read the comments in there.\r\n\r\nEither way, make sure you go to the blogs admin and set the correct access method for your blog. When using a stub or a template, you must also set its filename in the \'Stub name\' field. Otherwise, the permalinks will not function properly.', 'Skins, Stubs and Templates...', 'skins_stubs_and_templates', '', 11, 0, 'pingsdone', 0, 192, 'open', 'default');
INSERT INTO evo_posts VALUES (17, 1, '2005-02-18 20:33:12', '1970-01-01 01:00:00', 'published', 'en-EU', 'By default, b2evolution comes with 4 blogs, named \'Blog All\', \'Blog A\', \'Blog B\' and \'Linkblog\'.\r\n\r\nSome of these blogs have a special role. Read about it on the corresponding page.\r\n\r\nYou can create additional blogs or delete unwanted blogs from the blogs admin.', 'Multiple Blogs, new blogs, old blogs...', 'multiple_blogs_new_blogs_old_blogs', '', 11, 0, 'pingsdone', 0, 44, 'open', 'default');
INSERT INTO evo_posts VALUES (18, 1, '2005-02-18 20:33:13', '1970-01-01 01:00:00', 'published', 'en-EU', 'This is page 1 of a multipage post.\r\n\r\nYou can see the other pages by cliking on the links below the text.\r\n\r\n<!--nextpage-->\r\n\r\nThis is page 2.\r\n\r\n<!--nextpage-->\r\n\r\nThis is page 3.\r\n\r\n<!--nextpage-->\r\n\r\nThis is page 4.\r\n\r\nIt is the last page.', 'This is a multipage post', 'this_is_a_multipage_post', '', 11, 0, 'pingsdone', 0, 35, 'open', 'default');
INSERT INTO evo_posts VALUES (19, 1, '2005-02-18 20:33:14', '1970-01-01 01:00:00', 'published', 'en-EU', 'This is an extended post with no teaser. This means that you won\'t see this teaser any more when you click the "more" link.\r\n\r\n<!--more--><!--noteaser-->\r\n\r\nThis is the extended text. You only see it when you have clicked the "more" link.', 'Extended post with no teaser', 'extended_post_with_no_teaser', '', 11, 0, 'pingsdone', 0, 40, 'open', 'default');
INSERT INTO evo_posts VALUES (20, 1, '2005-02-18 20:33:15', '1970-01-01 01:00:00', 'published', 'en-EU', 'This is an extended post. This means you only see this small teaser by default and you must click on the link below to see more.\r\n\r\n<!--more-->\r\n\r\nThis is the extended text. You only see it when you have clicked the "more" link.', 'Extended post', 'extended_post', '', 11, 0, 'pingsdone', 0, 42, 'open', 'default');
INSERT INTO evo_posts VALUES (21, 1, '2005-02-18 20:33:16', '1970-01-01 01:00:00', 'published', 'en-EU', 'Blog B contains a few posts in the \'b2evolution Tips\' category.\r\n\r\nAll these entries are designed to help you so, as EdB would say: "<em>read them all before you start hacking away!</em>" ;)\r\n\r\nIf you wish, you can delete these posts one by one after you have read them. You could also change their status to \'deprecated\' in order to visually keep track of what you have already read.', 'Important information', 'important_information', '', 11, 0, 'pingsdone', 0, 69, 'open', 'default');

# --------------------------------------------------------

#
# Table structure for table `evo_settings`
#

CREATE TABLE evo_settings (
  set_name varchar(30) NOT NULL default '',
  set_value varchar(255) default NULL,
  PRIMARY KEY  (set_name)
) TYPE=MyISAM;

#
# Dumping data for table `evo_settings`
#

INSERT INTO evo_settings VALUES ('db_version', '8064');
INSERT INTO evo_settings VALUES ('default_locale', 'en-EU');
INSERT INTO evo_settings VALUES ('posts_per_page', '5');
INSERT INTO evo_settings VALUES ('what_to_show', 'paged');
INSERT INTO evo_settings VALUES ('archive_mode', 'monthly');
INSERT INTO evo_settings VALUES ('time_difference', '0');
INSERT INTO evo_settings VALUES ('autoBR', '1');
INSERT INTO evo_settings VALUES ('antispam_last_update', '2000-01-01 00:00:00');
INSERT INTO evo_settings VALUES ('newusers_grp_ID', '4');
INSERT INTO evo_settings VALUES ('newusers_level', '1');
INSERT INTO evo_settings VALUES ('newusers_canregister', '0');
INSERT INTO evo_settings VALUES ('links_extrapath', '0');
INSERT INTO evo_settings VALUES ('permalink_type', 'urltitle');
INSERT INTO evo_settings VALUES ('user_minpwdlen', '5');

# --------------------------------------------------------

#
# Table structure for table `evo_users`
#

CREATE TABLE evo_users (
  ID int(10) unsigned NOT NULL auto_increment,
  user_login varchar(20) NOT NULL default '',
  user_pass varchar(32) NOT NULL default '',
  user_firstname varchar(50) NOT NULL default '',
  user_lastname varchar(50) NOT NULL default '',
  user_nickname varchar(50) NOT NULL default '',
  user_icq int(10) unsigned NOT NULL default '0',
  user_email varchar(100) NOT NULL default '',
  user_url varchar(100) NOT NULL default '',
  user_ip varchar(15) NOT NULL default '',
  user_domain varchar(200) NOT NULL default '',
  user_browser varchar(200) NOT NULL default '',
  dateYMDhour datetime NOT NULL default '0000-00-00 00:00:00',
  user_level int(10) unsigned NOT NULL default '0',
  user_aim varchar(50) NOT NULL default '',
  user_msn varchar(100) NOT NULL default '',
  user_yim varchar(50) NOT NULL default '',
  user_locale varchar(20) NOT NULL default 'en-EU',
  user_idmode varchar(20) NOT NULL default 'login',
  user_notify tinyint(1) NOT NULL default '1',
  user_grp_ID int(4) NOT NULL default '1',
  PRIMARY KEY  (ID),
  UNIQUE KEY user_login (user_login),
  KEY user_grp_ID (user_grp_ID)
) TYPE=MyISAM AUTO_INCREMENT=3 ;

#
# Dumping data for table `evo_users`
#

INSERT INTO evo_users VALUES (1, 'admin', 'f89eb60b7545bcd1efa63094dee28829', '', '', 'admin', 0, 'postmaster@localhost', '', '127.0.0.1', 'localhost', '', '2005-02-18 20:33:17', 10, '', '', '', 'en-EU', 'login', 1, 1);
INSERT INTO evo_users VALUES (2, 'demouser', 'f89eb60b7545bcd1efa63094dee28829', '', '', 'Mr. Demo', 0, 'postmaster@localhost', '', '127.0.0.1', 'localhost', '', '2005-02-18 20:33:18', 0, '', '', '', 'en-EU', 'login', 1, 4);

