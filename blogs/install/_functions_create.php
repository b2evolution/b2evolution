<?php
/**
 * This file implements creation of DB tables
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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

load_class( 'users/model/_group.class.php' );
load_funcs( 'collections/model/_category.funcs.php' );

/**
 * Used for fresh install
 */
function create_tables()
{
	global $inc_path;

	// Load DB schema from modules
	load_db_schema();

	load_funcs('_core/model/db/_upgrade.funcs.php');

	// Alter DB to match DB schema:
	install_make_db_schema_current( true );
}


/**
 * Insert all default data:
 */
function create_default_data()
{
	global $Group_Admins, $Group_Privileged, $Group_Bloggers, $Group_Users;
	global $DB, $locales, $current_locale;

	// Inserting sample data triggers events: instead of checking if $Plugins is an object there, just use a fake one..
	load_class('plugins/model/_plugins_admin_no_db.class.php');
	global $Plugins;
	$Plugins = new Plugins_admin_no_DB(); // COPY

	// added in 0.8.7
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


	// added in 0.8.9
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
	$Group_Admins->set( 'perm_xhtml_css_tweaks', 1 );
	$Group_Admins->dbinsert();

	$Group_Privileged = new Group(); // COPY !
	$Group_Privileged->set( 'name', 'Privileged Bloggers' );
	$Group_Privileged->set( 'perm_admin', 'visible' );
	$Group_Privileged->set( 'perm_blogs', 'viewall' );
	$Group_Privileged->set( 'perm_stats', 'user' );
	$Group_Privileged->set( 'perm_spamblacklist', 'edit' );
	$Group_Privileged->set( 'perm_files', 'add' );
	$Group_Privileged->set( 'perm_options', 'view' );
	$Group_Privileged->set( 'perm_templates', 0 );
	$Group_Privileged->set( 'perm_users', 'view' );
	$Group_Privileged->set( 'perm_xhtml_css_tweaks', 1 );
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
	$Group_Bloggers->set( 'perm_xhtml_css_tweaks', 1 );
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

	echo 'Creating user field definitions... ';
	// fp> Anyone, please add anything you can think of. It's better to start with a large list that update it progressively.
	$DB->query( "
    INSERT INTO T_users__fielddefs (ufdf_ID, ufdf_type, ufdf_name)
		 VALUES ( 10000, 'email',    'MSN/Live IM'),
						( 10100, 'word',     'Yahoo IM'),
						( 10200, 'word',     'AOL AIM'),
						( 10300, 'number',   'ICQ ID'),
						( 40000, 'phone',    'Skype'),
						( 50000, 'phone',    'Main phone'),
						( 50100, 'phone',    'Cell phone'),
						( 50200, 'phone',    'Office phone'),
						( 50300, 'phone',    'Home phone'),
						( 60000, 'phone',    'Office FAX'),
						( 60100, 'phone',    'Home FAX'),
						(100000, 'url',      'Website'),
						(100100, 'url',      'Blog'),
						(110000, 'url',      'Linkedin'),
						(120000, 'url',      'Twitter'),
						(130100, 'url',      'Facebook'),
						(130200, 'url',      'Myspace'),
						(140000, 'url',      'Flickr'),
						(150000, 'url',      'YouTube'),
						(160000, 'url',      'Digg'),
						(160100, 'url',      'StumbleUpon'),
						(200000, 'text',     'Role'),
						(200100, 'text',     'Organization'),
						(200200, 'text',     'Division'),
						(211000, 'text',     'VAT ID'),
						(300000, 'text',     'Main address'),
						(300300, 'text',     'Home address');" );
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


	// added in Phoenix-Alpha
	echo 'Creating default Post Types... ';
	$DB->query( "
		INSERT INTO T_items__type ( ptyp_ID, ptyp_name )
		VALUES ( 1, 'Post' ),
					 ( 1000, 'Page' ),
					 ( 1500, 'Intro-Main' ),
					 ( 1520, 'Intro-Cat' ),
					 ( 1530, 'Intro-Tag' ),
					 ( 1570, 'Intro-Sub' ),
					 ( 1600, 'Intro-All' ),
					 ( 2000, 'Podcast' ),
					 ( 3000, 'Sidebar link' ),
					 ( 4000, 'Reserved' ),
					 ( 5000, 'Reserved' ) " );
	echo "OK.<br />\n";


	// added in Phoenix-Beta
	echo 'Creating default file types... ';
	// Contribs: feel free to add more types here...
	// TODO: dh> shouldn't they get localized to the app's default locale?
	$DB->query( "INSERT INTO T_filetypes
			(ftyp_ID, ftyp_extensions, ftyp_name, ftyp_mimetype, ftyp_icon, ftyp_viewtype, ftyp_allowed)
		VALUES
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
			(17, 'mov', 'Quicktime video', 'video/quicktime', '', 'browser', 1)
		" );
	echo "OK.<br />\n";

	if ( ! empty( $current_locale ) && ! $locales[$current_locale]['enabled'] )
	{	// The chosen locale is not enabled, make sure the user sees his new system localized.
		echo 'Activating selected default locale... ';
		$DB->query( 'INSERT INTO T_locales '
				   .'( loc_locale, loc_charset, loc_datefmt, loc_timefmt, '
				   .'loc_startofweek, loc_name, loc_messages, loc_priority, '
				   .'loc_enabled ) '
				   .'VALUES ( '.$DB->quote( $current_locale ).', '
				   .$DB->quote( $locales[$current_locale]['charset'] ).', '
				   .$DB->quote( $locales[$current_locale]['datefmt'] ).', '
				   .$DB->quote( $locales[$current_locale]['timefmt'] ).', '
				   .$DB->quote( $locales[$current_locale]['startofweek'] ).', '
				   .$DB->quote( $locales[$current_locale]['name'] ).', '
				   .$DB->quote( $locales[$current_locale]['messages'] ).', '
				   .$DB->quote( $locales[$current_locale]['priority'] ).', '
				   .' 1)' );
		echo 'OK.<br />', "\n";
	}

	create_default_settings();

	install_basic_skins();

	install_basic_plugins();

	return true;
}


/**
 * Create a new a blog
 * This funtion has to handle all needed DB dependencies!
 *
 * @todo move this to Blog object (only half done here)
 */
function create_blog(
	$blog_name,
	$blog_shortname,
	$blog_urlname,
	$blog_tagline = '',
	$blog_longdesc = '',
	$blog_skin_ID = 1,
	$kind = 'std' ) // standard blog; notorious variation: "photo"
{
	global $default_locale;

 	$Blog = & new Blog( NULL );

	$Blog->init_by_kind( $kind, $blog_name, $blog_shortname, $blog_urlname );

	$Blog->set( 'tagline', $blog_tagline );
	$Blog->set( 'longdesc', $blog_longdesc );
	$Blog->set( 'locale', $default_locale );
	$Blog->set( 'skin_ID', $blog_skin_ID );

	$Blog->dbinsert();

	$Blog->set( 'access_type', 'relative' );
	$Blog->set( 'siteurl', 'blog'.$Blog->ID.'.php' );

	$Blog->dbupdate();

	return $Blog->ID;
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

  /**
   * @var FileRootCache
   */
	global $FileRootCache;

	load_class( 'collections/model/_blog.class.php' );
	load_class( 'files/model/_file.class.php' );
	load_class( 'files/model/_filetype.class.php' );
	load_class( 'items/model/_link.class.php' );

	task_begin('Assigning avatar to Admin... ');
	$edit_File = & new File( 'user', 1, 'faceyourmanga_admin_boy.png' );
	// Load meta data AND MAKE SURE IT IS CREATED IN DB:
	$edit_File->load_meta( true );
	$UserCache = & get_Cache( 'UserCache' );
	$User_Admin = & $UserCache->get_by_ID( 1 );
	$User_Admin->set( 'avatar_file_ID', $edit_File->ID );
	$User_Admin->dbupdate();
	task_end();

	task_begin('Creating demo blogger user... ');
	$User_Blogger = & new User();
	$User_Blogger->set( 'login', 'ablogger' );
	$User_Blogger->set( 'pass', md5($random_password) ); // random
	$User_Blogger->set( 'nickname', 'Blogger A' );
	$User_Blogger->set_email( $admin_email );
	$User_Blogger->set( 'validated', 1 ); // assume it's validated
	$User_Blogger->set( 'ip', '127.0.0.1' );
	$User_Blogger->set( 'domain', 'localhost' );
	$User_Blogger->set( 'level', 1 );
	$User_Blogger->set( 'locale', $default_locale );
	$User_Blogger->set_datecreated( $timestamp++ );
	$User_Blogger->set_Group( $Group_Bloggers );
	$User_Blogger->dbinsert();
	task_end();

	task_begin('Creating demo user... ');
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
	task_end();

	global $default_locale, $query, $timestamp;
	global $blog_all_ID, $blog_a_ID, $blog_b_ID, $blog_linkblog_ID;

	$default_blog_longdesc = T_("This is the long description for the blog named '%s'. %s");

	echo "Creating default blogs... ";

	$blog_shortname = 'Blog A';
	$blog_a_long = sprintf( T_('%s Title'), $blog_shortname );
	$blog_stub = 'a';
	$blog_a_ID = create_blog(
		$blog_a_long,
		$blog_shortname,
		$blog_stub,
		sprintf( T_('Tagline for %s'), $blog_shortname ),
		sprintf( $default_blog_longdesc, $blog_shortname, '' ),
		1, 'std' ); // Skin ID

	$blog_shortname = 'Blog B';
	$blog_stub = 'b';
	$blog_b_ID = create_blog(
		sprintf( T_('%s Title'), $blog_shortname ),
		$blog_shortname,
		$blog_stub,
		sprintf( T_('Tagline for %s'), $blog_shortname ),
		sprintf( $default_blog_longdesc, $blog_shortname, '' ),
		2, 'std' ); // Skin ID

	$blog_shortname = 'Linkblog';
	$blog_stub = 'links';
	$blog_more_longdesc = '<br />
<br />
<strong>'.T_("The main purpose for this blog is to be included as a side item to other blogs where it will display your favorite/related links.").'</strong>';
	$blog_linkblog_ID = create_blog(
		'Linkblog',
		$blog_shortname,
		$blog_stub,
		T_('Some interesting links...'),
		sprintf( $default_blog_longdesc, $blog_shortname, $blog_more_longdesc ),
		3, 'std' ); // SKin ID

	$blog_shortname = 'Photoblog';
	$blog_stub = 'photos';
	$blog_more_longdesc = '<br />
<br />
<strong>'.T_("This is a photoblog, optimized for displaying photos.").'</strong>';
	$blog_photoblog_ID = create_blog(
		'Photoblog',
		$blog_shortname,
		$blog_stub,
		T_('This blog shows photos...'),
		sprintf( $default_blog_longdesc, $blog_shortname, $blog_more_longdesc ),
		4, 'photo' ); // SKin ID

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

	// Create categories for photoblog
	$cat_photo_album = cat_create( 'Monument Valley', 'NULL', $blog_photoblog_ID );

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


	// PHOTOBLOG:
  /**
   * @var FileRootCache
   */
	// $FileRootCache = & get_Cache( 'FileRootCache' );
	// $FileRoot = & $FileRootCache->get_by_type_and_ID( 'collection', $blog_photoblog_ID, true );

	// Insert a post into photoblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_('Bus Stop Ahead'), 'In the middle of nowhere: a school bus stop where you wouldn\'t really expect it!',
					 $now, $cat_photo_album, array(), 'published','en-US', '', 'http://fplanque.com/photo/monument-valley' );
	$edit_File = & new File( 'shared', 0, 'monument-valley/bus-stop-ahead.jpg' );
	$edit_File->link_to_Item( $edited_Item );

	// Insert a post into photoblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_('John Ford Point'), 'Does this scene look familiar? You\'ve probably seen it in a couple of John Ford westerns!',
					 $now, $cat_photo_album, array(), 'published','en-US', '', 'http://fplanque.com/photo/monument-valley' );
	$edit_File = & new File( 'shared', 0, 'monument-valley/john-ford-point.jpg' );
	$edit_File->link_to_Item( $edited_Item );

	// Insert a post into photoblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_('Monuments'), 'This is one of the most famous views in Monument Valley. I like to frame it with the dirt road in order to give a better idea of the size of those things!',
					 $now, $cat_photo_album, array(), 'published','en-US', '', 'http://fplanque.com/photo/monument-valley' );
	$edit_File = & new File( 'shared', 0, 'monument-valley/monuments.jpg' );
	$edit_File->link_to_Item( $edited_Item );

	// Insert a post into photoblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_('Road to Monument Valley'), 'This gives a pretty good idea of the Monuments you\'re about to drive into...',
					 $now, $cat_photo_album, array(), 'published','en-US', '', 'http://fplanque.com/photo/monument-valley' );
	$edit_File = & new File( 'shared', 0, 'monument-valley/monument-valley-road.jpg' );
	$edit_File->link_to_Item( $edited_Item );

	// Insert a post into photoblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_('Monument Valley'), T_('This is a short photo album demo. Use the arrows to navigate between photos. Click on "Index" to see a thumbnail index.'),
					 $now, $cat_photo_album, array(), 'published','en-US', '', 'http://fplanque.com/photo/monument-valley' );
	$edit_File = & new File( 'shared', 0, 'monument-valley/monument-valley.jpg' );
	$edit_File->link_to_Item( $edited_Item );


	// POPULATE THE LINKBLOG:

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'Danny', '', $now, $cat_linkblog_contrib, array(), 'published',	'en-US', '', 'http://personman.com/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'Daniel', '', $now, $cat_linkblog_contrib, array(), 'published',	'de-DE', '', 'http://daniel.hahler.de/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'Yabba', '', $now, $cat_linkblog_contrib, array(), 'published',	'en-UK', '', 'http://www.innervisions.org.uk/babbles/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'Francois', '', $now, $cat_linkblog_contrib, array(), 'published',	 'fr-FR', '', 'http://fplanque.com/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'Blog news', '', $now, $cat_linkblog_b2evo, array(), 'published',	'en-US', '', 'http://b2evolution.net/news.php', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'Web hosting', '', $now, $cat_linkblog_b2evo, array(), 'published',	'en-US', '', 'http://b2evolution.net/web-hosting/blog/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'Manual', '', $now, $cat_linkblog_b2evo, array(), 'published',	'en-US', '', 'http://manual.b2evolution.net/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'Support', '', $now, $cat_linkblog_b2evo, array(), 'published',	'en-US', '', 'http://forums.b2evolution.net/', 'disabled', array() );



 	$info_page = T_("This blog is powered by b2evolution.

You are currently looking at an info page about %s.

Info pages are very much like regular posts, except that they do not appear in the regular flow of posts. They appear as info pages in the sidebar instead.

If needed, a skin can format info pages differently from regular posts.");

	// Insert a PAGE:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("About Blog B"), sprintf( $info_page, T_('Blog B') ), $now, $cat_ann_b,
		array(), 'published', '#', '', '', 'open', array('default'), 1000 );

	// Insert a PAGE:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("About Blog A"), sprintf( $info_page, T_('Blog A') ), $now, $cat_ann_a,
		array(), 'published', '#', '', '', 'open', array('default'), 1000 );

	// Insert a PAGE:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("About this system"), T_("This blog platform is powered by b2evolution.

You are currently looking at an info page about this system. It is cross-posted among the 3 demo blogs. Thus, this page will be linked on each of these blogs.

Info pages are very much like regular posts, except that they do not appear in the regular flow of posts. They appear as info pages in the sidebar instead.

If needed, a skin can format info pages differently from regular posts."), $now, $cat_ann_a,
		array( $cat_ann_a, $cat_ann_b, $cat_linkblog_b2evo ), 'published', '#', '', '', 'open', array('default'), 1000 );
	$edit_File = & new File( 'shared', 0, 'logos/b2evolution8.png' );
	$edit_File->link_to_Item( $edited_Item );


	/*
	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("Default Intro post"), T_("This uses post type \"Intro-All\"."),
												$now, $cat_b2evo, array( $cat_ann_b ), 'published', '#', '', '', 'open', array('default'), 1600 );
	*/

	// Insert a post:
	$now = date('Y-m-d H:i:s', ($timestamp++ - 31536000) ); // A year ago
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("Main Intro post"), T_("This is the main intro post. It appears on the homepage only."),
												$now, $cat_b2evo, array(), 'published', '#', '', '', 'open', array('default'), 1500 );

	// Insert a post:
	$now = date('Y-m-d H:i:s', ($timestamp++ - 31536000) ); // A year ago
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("b2evolution tips category &ndash; Sub Intro post"), T_("This uses post type \"Intro-Cat\" and is attached to the desired Category(ies)."),
												$now, $cat_b2evo, array(), 'published', '#', '', '', 'open', array('default'), 1520 );

	// Insert a post:
	$now = date('Y-m-d H:i:s', ($timestamp++ - 31536000) ); // A year ago
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("Widgets tag &ndash; Sub Intro post"), T_("This uses post type \"Intro-Tag\" and is tagged with the desired Tag(s)."),
												$now, $cat_b2evo, array(), 'published', '#', '', '', 'open', array('default'), 1530 );
	$edited_Item->set_tags_from_string( 'widgets' );
	//$edited_Item->dbsave();
	$edited_Item->insert_update_tags( 'update' );

	// Insert a post:
	// TODO: move to Blog A
	$now = date('Y-m-d H:i:s', $timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("Featured post"), T_("This is a demo of a featured post.

It will be featured whenever we have no specific \"Intro\" post to display for the current request. To see it in action, try displaying the \"Announcements\" category.

Also note that when the post is featured, it does not appear in the regular post flow."),
	$now, $cat_b2evo, array( $cat_ann_b ) );
	$edited_Item->set( 'featured', 1 );
	$edited_Item->dbsave();

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("Apache optimization..."), T_("In the <code>/blogs</code> folder there is a file called [<code>sample.htaccess</code>]. You should try renaming it to [<code>.htaccess</code>].

This will optimize the way b2evolution is handled by the webserver (if you are using Apache). This file is not active by default because a few hosts would display an error right away when you try to use it. If this happens to you when you rename the file, just remove it and you'll be fine."),
												$now, $cat_b2evo, array( $cat_ann_b ) );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("Skins, Stubs, Templates &amp; website integration..."), T_("By default, blogs are displayed using a skin. (More on skins in another post.)

This means, blogs are accessed through '<code>index.php</code>', which loads default parameters from the database and then passes on the display job to a skin.

Alternatively, if you don't want to use the default DB parameters and want to, say, force a skin, a category or a specific linkblog, you can create a stub file like the provided '<code>a_stub.php</code>' and call your blog through this stub instead of index.php .

Finally, if you need to do some very specific customizations to your blog, you may use plain templates instead of skins. In this case, call your blog through a full template, like the provided '<code>a_noskin.php</code>'.

If you want to integrate a b2evolution blog into a complex website, you'll probably want to do it by copy/pasting code from <code>a_noskin.php</code> into a page of your website.

You will find more information in the stub/template files themselves. Open them in a text editor and read the comments in there.

Either way, make sure you go to the blogs admin and set the correct access method/URL for your blog. Otherwise, the permalinks will not function properly."), $now, $cat_b2evo );
	$edited_Item->set_tags_from_string( 'skins' );
	//$edited_Item->dbsave();
	$edited_Item->insert_update_tags( 'update' );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("About widgets..."), T_('b2evolution blogs are installed with a default selection of Widgets. For example, the sidebar of this blog includes widgets like a calendar, a search field, a list of categories, a list of XML feeds, etc.

You can add, remove and reorder widgets from the Blog Settings tab in the admin interface.

Note: to be displayed widgets are placed in containers. Each container appears in a specific place on a skin. If you change the skin of your blog, the new skin may not use the same containers as the previous one. Make sure you place your widgets in containers that exist in the specific skin you are using.'), $now, $cat_b2evo );
	$edited_Item->set_tags_from_string( 'widgets' );
	//$edited_Item->dbsave();
	$edited_Item->insert_update_tags( 'update' );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("About skins..."), T_('By default, b2evolution blogs are displayed using a skin.

You can change the skin used by any blog by editing the blog settings in the admin interface.

You can download additional skins from the <a href="http://skins.b2evolution.net/" target="_blank">skin site</a>. To install them, unzip them in the /blogs/skins directory, then go to General Settings &gt; Skins in the admin interface and click on "Install new".

You can also create your own skins by duplicating, renaming and customizing any existing skin folder from the /blogs/skins directory.

To start customizing a skin, open its "<code>index.main.php</code>" file in an editor and read the comments in there. Note: you can also edit skins in the "Files" tab of the admin interface.

And, of course, read the <a href="http://manual.b2evolution.net/Skins_2.0" target="_blank">manual on skins</a>!'), $now, $cat_b2evo );
	$edited_Item->set_tags_from_string( 'skins' );
	$edited_Item->set( 'featured', 1 );
	$edited_Item->dbsave();
	// $edited_Item->insert_update_tags( 'update' );


	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_('Image post'), T_('<p>This post has an image attached to it. The image is automatically resized to fit the current skin. You can zoom in by clicking on the thumbnail.</p>

<p>Check out the photoblog (accessible through the links at the top) to see a completely different skin focused more on the photos than on the blog text.</p>'), $now, $cat_bg );
	$edit_File = & new File( 'shared', 0, 'monument-valley/monuments.jpg' );
	$edit_File->link_to_Item( $edited_Item );


	// Insert a post:
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


	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_('Extended post with no teaser'), T_('This is an extended post with no teaser. This means that you won\'t see this teaser any more when you click the "more" link.

<!--more--><!--noteaser-->

This is the extended text. You only see it when you have clicked the "more" link.'), $now, $cat_bg );


	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_('Extended post'), T_('This is an extended post. This means you only see this small teaser by default and you must click on the link below to see more.

<!--more-->

This is the extended text. You only see it when you have clicked the "more" link.'), $now, $cat_bg );


	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("Welcome to b2evolution!"), T_("Four blogs have been created with sample contents:
<ul>
	<li><strong>Blog A</strong>: You are currently looking at it. It contains a few sample posts, using simple features of b2evolution.</li>
	<li><strong>Blog B</strong>: You can access it from a link at the top of the page. It contains information about more advanced features.</li>
	<li><strong>Linkblog</strong>: By default, the linkblog is included as a \"Blogroll\" in the sidebar of both Blog A &amp; Blog B.</li>
	<li><strong>Photoblog</strong>: This blog is an example of how you can use b2evolution to showcase photos, with one photo per page as well as a thumbnail index.</li>
</ul>

You can add new blogs, delete unwanted blogs and customize existing blogs (title, sidebar, skin, widgets, etc.) from the Blog Settings tab in the admin interface."), $now, $cat_ann_a );
	$edit_File = & new File( 'shared', 0, 'logos/b2evolution8.png' );
	$edit_File->link_to_Item( $edited_Item );




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
 * Revision 1.261  2009/05/18 03:59:39  fplanque
 * minor/doc
 *
 * Revision 1.260  2009/04/14 15:44:41  tblue246
 * Make sure the locale chosen at install time is enabled, so the user sees his new system in his language.
 *
 * Revision 1.259  2009/03/21 22:55:15  fplanque
 * Adding TinyMCE -- lowfat version
 *
 * Revision 1.258  2009/03/13 00:57:35  fplanque
 * calling it "sidebar links"
 *
 * Revision 1.257  2009/03/08 23:57:47  fplanque
 * 2009
 *
 * Revision 1.256  2009/02/28 18:45:11  fplanque
 * quick cleanup of the installer
 *
 * Revision 1.255  2009/02/26 22:33:22  blueyed
 * Fix messup in last commit.
 *
 * Revision 1.254  2009/02/26 22:16:54  blueyed
 * Use load_class for classes (.class.php), and load_funcs for funcs (.funcs.php)
 *
 * Revision 1.253  2009/01/21 18:23:26  fplanque
 * Featured posts and Intro posts
 *
 * Revision 1.252  2009/01/19 21:40:58  fplanque
 * Featured post proof of concept
 *
 * Revision 1.251  2009/01/13 23:45:59  fplanque
 * User fields proof of concept
 *
 * Revision 1.250  2008/10/06 03:36:48  fplanque
 * Added skype field
 *
 * Revision 1.249  2008/10/06 01:55:06  fplanque
 * User fields proof of concept.
 * Needs UserFieldDef and UserFieldDefCache + editing of fields.
 * Does anyone want to take if from there?
 *
 * Revision 1.248  2008/09/29 08:30:38  fplanque
 * Avatar support
 *
 * Revision 1.247  2008/09/23 07:56:47  fplanque
 * Demo blog now uses shared files folder for demo media + more images in demo posts
 *
 * Revision 1.246  2008/09/23 05:26:55  fplanque
 * MFB
 *
 * Revision 1.245  2008/09/15 11:01:10  fplanque
 * Installer now creates a demo photoblog
 *
 * Revision 1.244  2008/09/10 17:23:56  blueyed
 * Default linkblog: 'dAniel' => 'Daniel'
 *
 * Revision 1.243  2008/05/06 23:37:36  fplanque
 * MFB
 *
 * Revision 1.242  2008/04/06 19:19:30  fplanque
 * Started moving some intelligence to the Modules.
 * 1) Moved menu structure out of the AdminUI class.
 * It is part of the app structure, not the UI. Up to this point at least.
 * Note: individual Admin skins can still override the whole menu.
 * 2) Moved DB schema to the modules. This will be reused outside
 * of install for integrity checks and backup.
 * 3) cleaned up config files
 *
 * Revision 1.241  2008/03/16 14:19:38  fplanque
 * no message
 *
 * Revision 1.239  2008/01/21 09:35:37  fplanque
 * (c) 2008
 *
 * Revision 1.238  2008/01/17 18:10:11  fplanque
 * deprecated linkblog_ID blog param
 *
 * Revision 1.237  2008/01/17 17:43:53  fplanque
 * cleaner urls by default
 *
 * Revision 1.236  2008/01/08 19:35:00  personman2
 * Adding missing commas to install function
 *
 * Revision 1.235  2008/01/08 03:31:50  fplanque
 * podcast support
 *
 * Revision 1.234  2008/01/07 03:00:52  fplanque
 * minor
 *
 * Revision 1.233  2008/01/05 17:18:30  blueyed
 * fix doc
 *
 * Revision 1.232  2007/12/28 21:34:52  fplanque
 * no message
 *
 * Revision 1.231  2007/12/27 23:56:06  fplanque
 * Better out of the box experience
 *
 * Revision 1.230  2007/07/04 21:10:25  blueyed
 * More test include fixes
 *
 * Revision 1.229  2007/06/27 02:23:24  fplanque
 * new default template for skins named index.main.php
 *
 * Revision 1.228  2007/06/25 11:02:29  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.227  2007/05/28 01:35:23  fplanque
 * fixed static page generation
 *
 * Revision 1.226  2007/05/14 02:43:06  fplanque
 * Started renaming tables. There probably won't be a better time than 2.0.
 *
 * Revision 1.225  2007/05/13 20:44:52  fplanque
 * more pages support
 *
 * Revision 1.224  2007/05/08 00:54:31  fplanque
 * public blog list as a widget
 *
 * Revision 1.223  2007/05/07 18:59:45  fplanque
 * renamed skin .page.php files to .tpl.php
 *
 * Revision 1.222  2007/04/26 00:11:09  fplanque
 * (c) 2007
 *
 * Revision 1.221  2007/03/26 12:59:18  fplanque
 * basic pages support
 *
 * Revision 1.220  2007/03/25 13:20:52  fplanque
 * cleaned up blog base urls
 * needs extensive testing...
 *
 * Revision 1.219  2007/03/20 09:53:26  fplanque
 * Letting boggers view their own stats.
 * + Letthing admins view the aggregate by default.
 *
 * Revision 1.218  2007/03/18 01:39:54  fplanque
 * renamed _main.php to main.page.php to comply with 2.0 naming scheme.
 * (more to come)
 *
 * Revision 1.217  2007/02/21 21:33:43  fplanque
 * allow jpeg extension on new installs/upgrades
 *
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
