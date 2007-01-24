<?php
/**
 * This file implements creation of DB tables
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004 by Vegar BERG GULDAL - {@link http://funky-m.com/}
 * Parts of this file are copyright (c)2005 by Jason EDGECOMBE
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Vegar BERG GULDAL grants Francois PLANQUE the right to license
 * Vegar BERG GULDAL's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Jason EDGECOMBE grants Francois PLANQUE the right to license
 * Jason EDGECOMBE's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Matt FOLLETT grants Francois PLANQUE the right to license
 * Matt FOLLETT contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package install
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author vegarg: Vegar BERG GULDAL.
 * @author edgester: Jason EDGECOMBE.
 * @author mfollett: Matt Follett.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Used for fresh install
 */
function create_tables()
{
	global $inc_path;

	require_once dirname(__FILE__).'/_db_schema.inc.php';
	require_once $inc_path.'_misc/_upgrade.funcs.php';

	// Alter DB to match DB schema:
	install_make_db_schema_current( true );
}


/**
 * Insert all default data:
 */
function create_default_data()
{
	global $Group_Admins, $Group_Privileged, $Group_Bloggers, $Group_Users;
	global $DB;

	// Inserting sample data triggers events: instead of checking if $Plugins is an object there, just use a fake one..
	load_class('_misc/_plugins_admin_no_db.class.php');
	global $Plugins;
	$Plugins = new Plugins_admin_no_DB(); // COPY

	// upgrade to 0.8.7
	echo 'Creating default blacklist entries... ';
	$query = "INSERT INTO T_antispam(aspm_string) VALUES ".
	"('online-casino'), ('penis-enlargement'), ".
	"('order-viagra'), ('order-phentermine'), ('order-xenical'), ".
	"('order-prophecia'), ('sexy-lingerie'), ('-porn-'), ".
	"('-adult-'), ('-tits-'), ('buy-phentermine'), ".
	"('order-cheap-pills'), ('buy-xenadrine'),	('xxx'), ".
	"('paris-hilton'), ('parishilton'), ('camgirls'), ('adult-models')";
	$DB->query( $query );
	echo "OK.<br />\n";


	// upgrade to 0.8.9
	echo 'Creating default groups... ';
	$Group_Admins = new Group(); // COPY !
	$Group_Admins->set( 'name', 'Administrators' );
	$Group_Admins->set( 'perm_admin', 'visible' );
	$Group_Admins->set( 'perm_blogs', 'editall' );
	$Group_Admins->set( 'perm_stats', 'edit' );
	$Group_Admins->set( 'perm_spamblacklist', 'edit' );
	$Group_Admins->set( 'perm_files', 'all' );
	$Group_Admins->set( 'perm_options', 'edit' );
	$Group_Admins->set( 'perm_templates', 1 );
	$Group_Admins->set( 'perm_users', 'edit' );
	$Group_Admins->dbinsert();

	$Group_Privileged = new Group(); // COPY !
	$Group_Privileged->set( 'name', 'Privileged Bloggers' );
	$Group_Privileged->set( 'perm_admin', 'visible' );
	$Group_Privileged->set( 'perm_blogs', 'viewall' );
	$Group_Privileged->set( 'perm_stats', 'view' );
	$Group_Privileged->set( 'perm_spamblacklist', 'edit' );
	$Group_Privileged->set( 'perm_files', 'add' );
	$Group_Privileged->set( 'perm_options', 'view' );
	$Group_Privileged->set( 'perm_templates', 0 );
	$Group_Privileged->set( 'perm_users', 'view' );
	$Group_Privileged->dbinsert();

	$Group_Bloggers = new Group(); // COPY !
	$Group_Bloggers->set( 'name', 'Bloggers' );
	$Group_Bloggers->set( 'perm_admin', 'visible' );
	$Group_Bloggers->set( 'perm_blogs', 'user' );
	$Group_Bloggers->set( 'perm_stats', 'none' );
	$Group_Bloggers->set( 'perm_spamblacklist', 'view' );
	$Group_Bloggers->set( 'perm_files', 'view' );
	$Group_Bloggers->set( 'perm_options', 'none' );
	$Group_Bloggers->set( 'perm_templates', 0 );
	$Group_Bloggers->set( 'perm_users', 'none' );
	$Group_Bloggers->dbinsert();

	$Group_Users = new Group(); // COPY !
	$Group_Users->set( 'name', 'Basic Users' );
	$Group_Users->set( 'perm_admin', 'none' );
	$Group_Users->set( 'perm_blogs', 'user' );
	$Group_Users->set( 'perm_stats', 'none' );
	$Group_Users->set( 'perm_spamblacklist', 'none' );
	$Group_Users->set( 'perm_files', 'none' );
	$Group_Users->set( 'perm_options', 'none' );
	$Group_Users->set( 'perm_templates', 0 );
	$Group_Users->set( 'perm_users', 'none' );
	$Group_Users->dbinsert();
	echo "OK.<br />\n";


	echo 'Creating admin user... ';

	global $timestamp, $admin_email, $default_locale, $install_password;
	global $random_password;

	$User_Admin = & new User();
	$User_Admin->set( 'login', 'admin' );
	if( !isset( $install_password ) )
	{
		$random_password = generate_random_passwd(); // no ambiguous chars
	}
	else
	{
		$random_password = $install_password;
	}
	$User_Admin->set( 'pass', md5($random_password) );	// random
	$User_Admin->set( 'nickname', 'admin' );
	$User_Admin->set_email( $admin_email );
	$User_Admin->set( 'validated', 1 ); // assume it's validated
	$User_Admin->set( 'ip', '127.0.0.1' );
	$User_Admin->set( 'domain', 'localhost' );
	$User_Admin->set( 'level', 10 );
	$User_Admin->set( 'locale', $default_locale );
	$User_Admin->set_datecreated( $timestamp++ );
	// Note: NEVER use database time (may be out of sync + no TZ control)
	$User_Admin->set_Group( $Group_Admins );
	$User_Admin->dbinsert();
	echo "OK.<br />\n";


	// Upgrade to Phoenix-Alpha
	echo 'Creating default Post Types... ';
	$DB->query( "
		INSERT INTO T_itemtypes ( ptyp_ID, ptyp_name )
		VALUES ( 1, 'Post' ),
					 ( 2, 'Link' )" );
	echo "OK.<br />\n";


	// Upgrade to Phoenix-Beta
	echo 'Creating default file types... ';
	// Contribs: feel free to add more types here...
	// TODO: dh> shouldn't they get localized to the app's default locale?
	$DB->query( "INSERT INTO T_filetypes
			(ftyp_ID, ftyp_extensions, ftyp_name, ftyp_mimetype, ftyp_icon, ftyp_viewtype, ftyp_allowed)
		VALUES
			(1, 'gif', 'GIF image', 'image/gif', 'image2.png', 'image', 1),
			(2, 'png', 'PNG image', 'image/png', 'image2.png', 'image', 1),
			(3, 'jpg', 'JPEG image', 'image/jpeg', 'image2.png', 'image', 1),
			(4, 'txt', 'Text file', 'text/plain', 'document.png', 'text', 1),
			(5, 'htm html', 'HTML file', 'text/html', 'html.png', 'browser', 0),
			(6, 'pdf', 'PDF file', 'application/pdf', 'pdf.png', 'browser', 1),
			(7, 'doc', 'Microsoft Word file', 'application/msword', 'doc.gif', 'external', 1),
			(8, 'xls', 'Microsoft Excel file', 'application/vnd.ms-excel', 'xls.gif', 'external', 1),
			(9, 'ppt', 'Powerpoint', 'application/vnd.ms-powerpoint', 'ppt.gif', 'external', 1),
			(10, 'pps', 'Slideshow', 'pps', 'pps.gif', 'external', 1),
			(11, 'zip', 'ZIP archive', 'application/zip', 'zip.gif', 'external', 1),
			(12, 'php php3 php4 php5 php6', 'PHP script', 'application/x-httpd-php', 'php.gif', 'text', 0),
			(13, 'css', 'Style sheet', 'text/css', '', 'text', 1)
		" );
	echo "OK.<br />\n";


	create_default_settings();

	install_basic_skins();

	install_basic_plugins();

	return true;
}


/**
 * Create a new a blog
 * This funtion has to handle all needed DB dependencies!
 *
 * @todo move this to Blog object
 */
function create_blog(
	$blog_name,
	$blog_shortname,
	$blog_stub,									// This will temporarily be assigned to both STUB and URLNAME
	$blog_staticfilename = '',
	$blog_tagline = '',
	$blog_longdesc = '',
	$blog_links_blog_ID = 0 )
{
	global $DB, $default_locale;

	$query = "INSERT INTO T_blogs( blog_name, blog_shortname, blog_siteurl,
						blog_stub, blog_urlname, blog_staticfilename,
						blog_tagline, blog_longdesc, blog_locale,
						blog_allowcomments, blog_allowtrackbacks, blog_disp_bloglist,
						blog_in_bloglist, blog_links_blog_ID )
	VALUES ( ";
	$query .= "'".$DB->escape($blog_name)."', ";
	$query .= "'".$DB->escape($blog_shortname)."', ";
	$query .= "'', ";
	$query .= "'".$DB->escape($blog_stub)."', ";
	$query .= "'".$DB->escape($blog_stub)."', ";		// This one is for urlname
	$query .= "'".$DB->escape($blog_staticfilename)."', ";
	$query .= "'".$DB->escape($blog_tagline)."', ";
	$query .= "'".$DB->escape($blog_longdesc)."', ";
	$query .= "'".$DB->escape($default_locale)."', ";
	$query .= "'post_by_post', 0, 1, 1, $blog_links_blog_ID )";

	if( ! ($DB->query( $query )) )
		return false;

	return $DB->insert_id;  // blog ID
}


/**
 * This is called only for fresh installs and fills the tables with
 * demo/tutorial things.
 */
function create_demo_contents()
{
	global $baseurl, $new_db_version;
	global $random_password, $query;
	global $timestamp, $admin_email;
	global $Group_Admins, $Group_Privileged, $Group_Bloggers, $Group_Users;
	global $blog_all_ID, $blog_a_ID, $blog_b_ID, $blog_linkblog_ID;
	global $DB;
	global $default_locale, $install_password;
	global $Plugins;

	echo 'Creating demo user... ';
	$User_Demo = & new User();
	$User_Demo->set( 'login', 'demouser' );
	$User_Demo->set( 'pass', md5($random_password) ); // random
	$User_Demo->set( 'nickname', 'Mr. Demo' );
	$User_Demo->set_email( $admin_email );
	$User_Demo->set( 'validated', 1 ); // assume it's validated
	$User_Demo->set( 'ip', '127.0.0.1' );
	$User_Demo->set( 'domain', 'localhost' );
	$User_Demo->set( 'level', 0 );
	$User_Demo->set( 'locale', $default_locale );
	$User_Demo->set_datecreated( $timestamp++ );
	$User_Demo->set_Group( $Group_Users );
	$User_Demo->dbinsert();
	echo "OK.<br />\n";


	global $default_locale, $query, $timestamp;
	global $blog_all_ID, $blog_a_ID, $blog_b_ID, $blog_linkblog_ID;

	$default_blog_longdesc = T_("This is the long description for the blog named '%s'. %s");

	echo "Creating default blogs... ";

	/*
	$blog_shortname = 'Blog All';
	$blog_stub = 'all';
	$blog_more_longdesc = "<br />
<br />
<strong>".T_("This blog (blog #1) is actually a very special blog! It automatically aggregates all posts from all other blogs. This allows you to easily track everything that is posted on this system. You can hide this blog from the public by unchecking 'Include in public blog list' in the blogs admin.")."</strong>";
	$blog_all_ID = create_blog(
		sprintf( T_('%s Title'), $blog_shortname ),
		$blog_shortname,
		$blog_stub,
		$blog_stub.'.html',
		sprintf( T_('Tagline for %s'), $blog_shortname ),
		sprintf( $default_blog_longdesc, $blog_shortname, $blog_more_longdesc ),
		4 );
	*/

	$blog_shortname = 'Blog A';
	$blog_a_long = sprintf( T_('%s Title'), $blog_shortname );
	$blog_stub = 'a';
	$blog_a_ID = create_blog(
		$blog_a_long,
		$blog_shortname,
		$blog_stub,
		$blog_stub.'.html',
		sprintf( T_('Tagline for %s'), $blog_shortname ),
		sprintf( $default_blog_longdesc, $blog_shortname, '' ),
		3 );	// !!! Linkblofg ID

	$blog_shortname = 'Blog B';
	$blog_stub = 'b';
	$blog_b_ID = create_blog(
		sprintf( T_('%s Title'), $blog_shortname ),
		$blog_shortname,
		$blog_stub,
		$blog_stub.'.html',
		sprintf( T_('Tagline for %s'), $blog_shortname ),
		sprintf( $default_blog_longdesc, $blog_shortname, '' ),
		3 );	// !!! Linkblofg ID

	$blog_shortname = 'Linkblog';
	$blog_stub = 'links';
	$blog_more_longdesc = '<br />
<br />
<strong>'.T_("The main purpose for this blog is to be included as a side item to other blogs where it will display your favorite/related links.").'</strong>';
	$blog_linkblog_ID = create_blog(
		sprintf( T_('%s Title'), $blog_shortname ),
		$blog_shortname,
		$blog_stub,
		$blog_stub.'.html',
		sprintf( T_('Tagline for %s'), $blog_shortname ),
		sprintf( $default_blog_longdesc, $blog_shortname, $blog_more_longdesc ),
		0 /* no Link blog */ );

	echo "OK.<br />\n";


	global $query, $timestamp;

	echo 'Creating sample categories... ';

	// Create categories for blog A
	$cat_ann_a = cat_create( 'Welcome', 'NULL', $blog_a_ID );
	$cat_news = cat_create( 'News', 'NULL', $blog_a_ID );
	$cat_bg = cat_create( 'Background', 'NULL', $blog_a_ID );
	$cat_fun = cat_create( 'Fun', 'NULL', $blog_a_ID );
	$cat_life = cat_create( 'In real life', $cat_fun, $blog_a_ID );
	$cat_web = cat_create( 'On the web', $cat_fun, $blog_a_ID );
	$cat_sports = cat_create( 'Sports', $cat_life, $blog_a_ID );
	$cat_movies = cat_create( 'Movies', $cat_life, $blog_a_ID );
	$cat_music = cat_create( 'Music', $cat_life, $blog_a_ID );

	// Create categories for blog B
	$cat_ann_b = cat_create( 'Announcements', 'NULL', $blog_b_ID );
	$cat_b2evo = cat_create( 'b2evolution Tips', 'NULL', $blog_b_ID );

	// Create categories for linkblog
	$cat_linkblog_b2evo = cat_create( 'b2evolution', 'NULL', $blog_linkblog_ID );
	$cat_linkblog_contrib = cat_create( 'contributors', 'NULL', $blog_linkblog_ID );

	echo "OK.<br />\n";


	echo 'Creating sample posts... ';

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_('First Post'), T_('<p>This is the first post.</p>

<p>It appears in a single category.</p>'), $now, $cat_ann_a );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_('Second post'), T_('<p>This is the second post.</p>

<p>It appears in multiple categories.</p>'), $now, $cat_news, array( $cat_ann_a ) );



	// POPULATE THE LINKBLOG:

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'Danny', '', $now, $cat_linkblog_contrib, array(), 'published',	'en-US', '', 'http://brendoman.com/dbc', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'Yabba', '', $now, $cat_linkblog_contrib, array(), 'published',	'en-UK', '', 'http://www.innervisions.org.uk/babbles/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'Halton', '', $now, $cat_linkblog_contrib, array(), 'published',	'en-US', '', 'http://browsermonkey.com/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'Topanga', '', $now, $cat_linkblog_contrib, array(), 'published',	'en-US', '', 'http://www.tenderfeelings.be', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'EdB', '', $now, $cat_linkblog_contrib, array(), 'published',	'en-US', '', 'http://wonderwinds.com/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'dAniel', '', $now, $cat_linkblog_contrib, array(), 'published',	'de-DE', '', 'http://daniel.hahler.de/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'Francois', '', $now, $cat_linkblog_contrib, array(), 'published',	 'fr-FR', '', 'http://fplanque.com/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'b2evolution home', '', $now, $cat_linkblog_b2evo, array(), 'published',	'en-EU', '', 'http://b2evolution.net/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'User manual', '', $now, $cat_linkblog_b2evo, array(), 'published',	'en-EU', '', 'http://manual.b2evolution.net/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'Support forums', '', $now, $cat_linkblog_b2evo, array(), 'published',	'en-EU', '', 'http://forums.b2evolution.net/', 'disabled', array() );


	global $query, $timestamp;

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("Clean Permalinks!"), T_("b2evolution uses old-style permalinks and feedback links by default. This is to ensure maximum compatibility with various webserver configurations.

Nethertheless, once you feel comfortable with b2evolution, you should try activating clean permalinks in the Global Settings &gt; Link options in the admin interface."), $now, $cat_b2evo );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("Apache optimization..."), T_("In the <code>/blogs</code> folder there is a file called [<code>sample.htaccess</code>]. You should try renaming it to [<code>.htaccess</code>].

This will optimize the way b2evolution is handled by the webserver (if you are using Apache). This file is not active by default because a few hosts would display an error right away when you try to use it. If this happens to you when you rename the file, just remove it and you'll be fine."), $now, $cat_b2evo );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("Skins, Stubs, Templates &amp; website integration..."), T_("By default, blogs are displayed using a skin. (More on skins in another post.)

This means, blogs are accessed through '<code>index.php</code>', which loads default parameters from the database and then passes on the display job to a skin.

Alternatively, if you don't want to use the default DB parameters and want to, say, force a skin, a category or a specific linkblog, you can create a stub file like the provided '<code>a_stub.php</code>' and call your blog through this stub instead of index.php .

Finally, if you need to do some very specific customizations to your blog, you may use plain templates instead of skins. In this case, call your blog through a full template, like the provided '<code>a_noskin.php</code>'.

If you want to integrate a b2evolution blog into a complex website, you'll probably want to do it by copy/pasting code from <code>a_noskin.php</code> into a page of your website.

You will find more information in the stub/template files themselves. Open them in a text editor and read the comments in there.

Either way, make sure you go to the blogs admin and set the correct access method for your blog. When using a stub or a template, you must also set its filename in the 'Stub name' field. Otherwise, the permalinks will not function properly."), $now, $cat_b2evo );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("About widgets..."), T_('b2evolution blogs are installed with a default selection of Widgets. For example, the sidebar of this blog includes widgets like a calendar, a search field, a list of categories, a list of XML feeds, etc.

You can add, remove and reorder widgets from the Blog Settings tab in the admin interface.

Note: to be displayed widgets are placed in containers. Each container appears in a specific place on a skin. If you change the skin of your blog, the new skin may not use the same containers as the previous one. Make sure you place your widgets in containers that exist in the specific skin you are using.'), $now, $cat_b2evo );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("About skins..."), T_('By default, b2evolution blogs are displayed using a skin.

You can change the skin used by any blog by editing the blog settings in the admin interface.

You can download additional skins from the <a href="http://skins.b2evolution.net/" traget="_blank">skin site</a>. To install them, unzip them in the /blogs/skins directory, then go to General Settings &gt; Skins in the admin interface and click on "Install new".

You can also create your own skins by duplicating, renaming and customizing any existing skin folder from the /blogs/skins directory.

To start customizing a skin, open its "<code>_main.php</code>" file in an editor and read the comments in there. And, of course, read the manual on skins!'), $now, $cat_b2evo );


	// Create newbie posts:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_('This is a multipage post'), T_('This is page 1 of a multipage post.

You can see the other pages by clicking on the links below the text.

<!--nextpage-->

This is page 2.

<!--nextpage-->

This is page 3.

<!--nextpage-->

This is page 4.

It is the last page.'), $now, $cat_bg );


	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_('Extended post with no teaser'), T_('This is an extended post with no teaser. This means that you won\'t see this teaser any more when you click the "more" link.

<!--more--><!--noteaser-->

This is the extended text. You only see it when you have clicked the "more" link.'), $now, $cat_bg );


	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_('Extended post'), T_('This is an extended post. This means you only see this small teaser by default and you must click on the link below to see more.

<!--more-->

This is the extended text. You only see it when you have clicked the "more" link.'), $now, $cat_bg );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("Welcome to b2evolution!"), T_("Three blogs have been created with sample contents:
<ul>
	<li><strong>Blog A</strong>: You are currently looking at it. It contains a few sample posts, using simple features of b2evolution.</li>
	<li><strong>Blog B</strong>: You can access it from a link at the top of the page. It contains information about more advanced features.</li>
	<li><strong>Linkblog</strong>: The linkblog is included by default in the sidebar of both Blog A &amp; Blog B.</li>
</ul>

You can add new blogs, delete unwanted blogs and customize existing blogs (title, sidebar, skin, widgets, etc.) from the Blog Settings tab in the admin interface."), $now, $cat_ann_a );

	echo "OK.<br />\n";



	echo 'Creating sample comments... ';

	$now = date('Y-m-d H:i:s');
	$query = "INSERT INTO T_comments( comment_post_ID, comment_type, comment_author,
																				comment_author_email, comment_author_url, comment_author_IP,
																				comment_date, comment_content, comment_karma)
						VALUES( 1, 'comment', 'miss b2', 'missb2@example.com', 'http://example.com', '127.0.0.1',
									 '$now', '".
									 $DB->escape(T_('Hi, this is a comment.<br />To delete a comment, just log in, and view the posts\' comments, there you will have the option to edit or delete them.')). "', 0)";
	$DB->query( $query );

	echo "OK.<br />\n";


	echo 'Creating default group/blog permissions... ';
	// Admin for blog A:
	$query = "
		INSERT INTO T_coll_group_perms( bloggroup_blog_ID, bloggroup_group_ID, bloggroup_ismember,
			bloggroup_perm_poststatuses, bloggroup_perm_delpost, bloggroup_perm_comments,
			bloggroup_perm_cats, bloggroup_perm_properties,
			bloggroup_perm_media_upload, bloggroup_perm_media_browse, bloggroup_perm_media_change )
		VALUES
			( $blog_a_ID, ".$Group_Admins->ID.", 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1, 1, 1, 1 ),
			( $blog_a_ID, ".$Group_Privileged->ID.", 1, 'published,deprecated,protected,private,draft', 1, 1, 0, 0, 1, 1, 1 ),
			( $blog_a_ID, ".$Group_Bloggers->ID.", 1, 'published,deprecated,protected,private,draft', 0, 0, 0, 0, 1, 1, 0 ),
			( $blog_a_ID, ".$Group_Users->ID.", 1, '', 0, 0, 0, 0, 0, 0, 0 ),
			( $blog_b_ID, ".$Group_Admins->ID.", 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1, 1, 1, 1 ),
			( $blog_b_ID, ".$Group_Privileged->ID.", 1, 'published,deprecated,protected,private,draft', 1, 1, 0, 0, 1, 1, 1 ),
			( $blog_b_ID, ".$Group_Bloggers->ID.", 1, 'published,deprecated,protected,private,draft', 0, 0, 0, 0, 1, 1, 0 ),
			( $blog_b_ID, ".$Group_Users->ID.", 1, '', 0, 0, 0, 0, 0, 0, 0 ),
			( $blog_linkblog_ID, ".$Group_Admins->ID.", 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1, 1, 1, 1 ),
			( $blog_linkblog_ID, ".$Group_Privileged->ID.", 1, 'published,deprecated,protected,private,draft', 1, 1, 0, 0, 1, 1, 1 ),
			( $blog_linkblog_ID, ".$Group_Bloggers->ID.", 1, 'published,deprecated,protected,private,draft', 0, 0, 0, 0, 1, 1, 0 ),
			( $blog_linkblog_ID, ".$Group_Users->ID.", 1, '', 0, 0, 0, 0, 0, 0, 0 )";
	$DB->query( $query );
	echo "OK.<br />\n";

	/*
	// Note: we don't really need this any longer, but we might use it for a better default setup later...
	echo 'Creating default user/blog permissions... ';
	// Admin for blog A:
	$query = "INSERT INTO T_coll_user_perms( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember,
							bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments,
							bloguser_perm_cats, bloguser_perm_properties,
							bloguser_perm_media_upload, bloguser_perm_media_browse, bloguser_perm_media_change )
						VALUES
							( $blog_a_ID, ".$User_Demo->ID.", 1,
							'published,deprecated,protected,private,draft', 1, 1, 0, 0, 1, 1, 1 )";
	$DB->query( $query );
	echo "OK.<br />\n";
	*/


	install_basic_widgets();

}


/*
 * $Log$
 * Revision 1.216  2007/01/24 13:47:28  fplanque
 * cleaned up file types
 *
 * Revision 1.215  2007/01/20 16:08:36  blueyed
 * fixed grammar
 *
 * Revision 1.214  2007/01/15 19:10:29  fplanque
 * install refactoring
 *
 * Revision 1.213  2007/01/15 17:00:42  fplanque
 * cleaned up default contents
 *
 * Revision 1.212  2007/01/15 03:53:24  fplanque
 * refactoring / simplified installer
 *
 * Revision 1.211  2007/01/14 01:32:14  fplanque
 * more widgets supported! :)
 *
 * Revision 1.210  2007/01/12 02:40:26  fplanque
 * widget default params proof of concept
 * (param customization to be done)
 *
 * Revision 1.209  2007/01/08 02:11:56  fplanque
 * Blogs now make use of installed skins
 * next step: make use of widgets inside of skins
 *
 * Revision 1.208  2006/12/17 23:42:39  fplanque
 * Removed special behavior of blog #1. Any blog can now aggregate any other combination of blogs.
 * Look into Advanced Settings for the aggregating blog.
 * There may be side effects and new bugs created by this. Please report them :]
 *
 * Revision 1.207  2006/12/12 20:26:12  blueyed
 * Fixed sample post about sample.htaccess in (obsolete/removed) "admin" folder. See http://forums.b2evolution.net/viewtopic.php?p=48204#48204
 *
 * Revision 1.206  2006/12/07 20:31:29  fplanque
 * fixed install
 *
 * Revision 1.205  2006/12/07 16:06:24  fplanque
 * prepared new file editing permission
 *
 * Revision 1.204  2006/12/04 22:25:20  blueyed
 * Do not output "Installing default plugins... " always
 *
 * Revision 1.203  2006/11/30 06:04:12  blueyed
 * Moved Plugins::install() and sort() galore to Plugins_admin
 *
 * Revision 1.202  2006/10/10 23:00:41  blueyed
 * Fixed some table names to alias; fixed plugin install procedure; installed ping plugins; moved some upgrade code to 1.9
 *
 * Revision 1.201  2006/10/06 21:52:52  blueyed
 * Enable upload for new "css" type
 *
 * Revision 1.200  2006/10/06 21:03:07  blueyed
 * Removed deprecated/unused "upload_allowedext" Setting, which restricted file extensions during upload though!
 */
?>