<?php
/**
 * This file implements creation of DB tables
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004 by Vegar BERG GULDAL - {@link http://funky-m.com/}
 * Parts of this file are copyright (c)2005 by Jason EDGECOMBE
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
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
 * Create b2 tables.
 *
 * Used for fresh install + upgrade from b2
 */
function create_b2evo_tables()
{
	global $inc_path;

	require_once $inc_path.'_misc/_db_schema.inc.php';
	require_once $inc_path.'_misc/_upgrade.funcs.php';

	// Alter DB to match DB schema:
	install_make_db_schema_current( true );

	// Insert all default data:
	install_insert_default_data(0);

	// Create relations:
	create_b2evo_relations();

	return true;
}


/**
 * Used only when upgrading to 0.8.7 or later.
 *
 * @deprecated Table layout gets handled by {@link db_delta()} and defaults are present in {@link install_insert_default_data()}.
 *
 */
function create_antispam()
{
	global $DB;

	echo 'Creating table for Antispam Blackist... ';
	$query = "CREATE TABLE T_antispam (
		aspm_ID bigint(11) NOT NULL auto_increment,
		aspm_string varchar(80) NOT NULL,
		aspm_source enum( 'local','reported','central' ) NOT NULL default 'reported',
		PRIMARY KEY aspm_ID (aspm_ID),
		UNIQUE aspm_string (aspm_string)
	)";
	$DB->query( $query );
	echo "OK.<br />\n";

	echo 'Creating default blacklist entries... ';
	$query = "INSERT INTO T_antispam(aspm_string) VALUES ".
	"('penis-enlargement'), ('online-casino'), ".
	"('order-viagra'), ('order-phentermine'), ('order-xenical'), ".
	"('order-prophecia'), ('sexy-lingerie'), ('-porn-'), ".
	"('-adult-'), ('-tits-'), ('buy-phentermine'), ".
	"('order-cheap-pills'), ('buy-xenadrine'),	('xxx'), ".
	"('paris-hilton'), ('parishilton'), ('camgirls'), ('adult-models')";
	$DB->query( $query );
	echo "OK.<br />\n";
}


/**
 * Create user permissions
 *
 * Used when creating full install and upgrading from earlier versions
 */
function create_groups()
{
	global $Group_Admins, $Group_Privileged, $Group_Bloggers, $Group_Users;
	global $DB;

	echo 'Creating table for Groups... ';
	$query = "CREATE TABLE T_groups (
		grp_ID int(11) NOT NULL auto_increment,
		grp_name varchar(50) NOT NULL default '',
		grp_perm_admin enum('none','hidden','visible') NOT NULL default 'visible',
		grp_perm_blogs enum('user','viewall','editall') NOT NULL default 'user',
		grp_perm_stats enum('none','view','edit') NOT NULL default 'none',
		grp_perm_spamblacklist enum('none','view','edit') NOT NULL default 'none',
		grp_perm_options enum('none','view','edit') NOT NULL default 'none',
		grp_perm_users enum('none','view','edit') NOT NULL default 'none',
		grp_perm_templates TINYINT NOT NULL DEFAULT 0,
		grp_perm_files enum('none','view','add','edit') NOT NULL default 'none',
		PRIMARY KEY grp_ID (grp_ID)
	)";
	$DB->query( $query );
	echo "OK.<br />\n";

	echo 'Creating default groups... ';
	$Group_Admins = new Group(); // COPY !
	$Group_Admins->set( 'name', 'Administrators' );
	$Group_Admins->set( 'perm_admin', 'visible' );
	$Group_Admins->set( 'perm_blogs', 'editall' );
	$Group_Admins->set( 'perm_stats', 'edit' );
	$Group_Admins->set( 'perm_spamblacklist', 'edit' );
	$Group_Admins->set( 'perm_files', 'edit' );
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


	echo 'Creating table for Blog-User permissions... ';
	$query = "CREATE TABLE T_coll_user_perms (
		bloguser_blog_ID int(11) unsigned NOT NULL default 0,
		bloguser_user_ID int(11) unsigned NOT NULL default 0,
		bloguser_ismember tinyint NOT NULL default 0,
		bloguser_perm_poststatuses set('published','deprecated','protected','private','draft') NOT NULL default '',
		bloguser_perm_delpost tinyint NOT NULL default 0,
		bloguser_perm_comments tinyint NOT NULL default 0,
		bloguser_perm_cats tinyint NOT NULL default 0,
		bloguser_perm_properties tinyint NOT NULL default 0,
		bloguser_perm_media_upload tinyint NOT NULL default 0,
		bloguser_perm_media_browse tinyint NOT NULL default 0,
		bloguser_perm_media_change tinyint NOT NULL default 0,
		PRIMARY KEY bloguser_pk (bloguser_blog_ID,bloguser_user_ID)
	)";
	$DB->query( $query );
	echo "OK.<br />\n";

}


/**
 * Populate the linkblog with contributors to the release...
 */
function populate_linkblog( & $now, $cat_linkblog_b2evo, $cat_linkblog_contrib)
{
	global $timestamp, $default_locale;

	echo 'Creating default linkblog entries... ';

	// Unknown status...

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'Bertrand', 'Contrib', $now, $cat_linkblog_contrib, array(), 'published',	'fr-FR', '', 0, true, '', 'http://www.epistema.com/fr/societe/weblog.php', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'Jeff', 'Contrib', $now, $cat_linkblog_contrib, array(), 'published',	'en-US', '', 0, true, '', 'http://www.jeffbearer.com/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'Jason', 'Contrib', $now, $cat_linkblog_contrib, array(), 'published',	'en-US', '', 0, true, '', 'http://itc.uncc.edu/blog/jwedgeco/', 'disabled', array() );

	// Active! :

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'Yabba', 'Debug', $now, $cat_linkblog_contrib, array(), 'published',	'en-US', '', 0, true, '', 'http://yabba.waffleson.com/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'Halton', 'Contrib', $now, $cat_linkblog_contrib, array(), 'published',	'en-US', '', 0, true, '', 'http://www.squishymonkey.com/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'dAniel', 'Development', $now, $cat_linkblog_contrib, array(), 'published',	'de-DE', '', 0, true, '', 'http://thequod.de/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'Francois', 'Main dev', $now, $cat_linkblog_contrib, array(), 'published',	 'fr-FR', '', 0, true, '', 'http://fplanque.net/Blog/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, 'b2evolution', 'Project home', $now, $cat_linkblog_b2evo, array(), 'published',	'en-EU', '', 0, true, '', 'http://b2evolution.net/', 'disabled', array() );

	// Insert a post into linkblog:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_('This is a sample linkblog entry'), T_("This is sample text describing the linkblog entry. In most cases however, you'll want to leave this blank, providing just a Title and an Url for your linkblog entries (favorite/related sites)."), $now, $cat_linkblog_b2evo, array(), 'published',	$default_locale, '', 0, true, '', 'http://b2evolution.net/', 'disabled', array() );

	echo "OK.<br />\n";
}


/**
 * Create default blogs.
 *
 * This is called for fresh installs and cafelog upgrade.
 *
 * @param string
 * @param string
 * @param string
 */
function create_default_blogs( $blog_a_short = 'Blog A', $blog_a_long = '#', $blog_a_longdesc = '#' )
{
	global $default_locale, $query, $timestamp;
	global $blog_all_ID, $blog_a_ID, $blog_b_ID, $blog_linkblog_ID;

	$default_blog_longdesc = T_("This is the long description for the blog named '%s'. %s");

	echo "Creating default blogs... ";

	$blog_shortname = 'Blog All';
	$blog_stub = 'all';
	$blog_more_longdesc = "<br />
<br />
<strong>".T_("This blog (blog #1) is actually a very special blog! It automatically aggregates all posts from all other blogs. This allows you to easily track everything that is posted on this system. You can hide this blog from the public by unchecking 'Include in public blog list' in the blogs admin.")."</strong>";
	$blog_all_ID = blog_create(
		sprintf( T_('%s Title'), $blog_shortname ),
		$blog_shortname,
		'',
		$blog_stub,
		$blog_stub.'.html',
		sprintf( T_('Tagline for %s'), $blog_shortname ),
		sprintf( T_('Short description for %s'), $blog_shortname ),
		sprintf( $default_blog_longdesc, $blog_shortname, $blog_more_longdesc ),
		$default_locale,
		sprintf( T_('Notes for %s'), $blog_shortname ),
		sprintf( T_('Keywords for %s'), $blog_shortname ),
		4 );

	$blog_shortname = $blog_a_short;
	if( $blog_a_long == '#' ) $blog_a_long = sprintf( T_('%s Title'), $blog_shortname );
	$blog_stub = 'a';
	$blog_a_ID = blog_create(
		$blog_a_long,
		$blog_shortname,
		'',
		$blog_stub,
		$blog_stub.'.html',
		sprintf( T_('Tagline for %s'), $blog_shortname ),
		sprintf( T_('Short description for %s'), $blog_shortname ),
		sprintf( (($blog_a_longdesc == '#') ? $default_blog_longdesc : $blog_a_longdesc), $blog_shortname, '' ),
		$default_locale,
		sprintf( T_('Notes for %s'), $blog_shortname ),
		sprintf( T_('Keywords for %s'), $blog_shortname ),
		4 );

	$blog_shortname = 'Blog B';
	$blog_stub = 'b';
	$blog_b_ID = blog_create(
		sprintf( T_('%s Title'), $blog_shortname ),
		$blog_shortname,
		'',
		$blog_stub,
		$blog_stub.'.html',
		sprintf( T_('Tagline for %s'), $blog_shortname ),
		sprintf( T_('Short description for %s'), $blog_shortname ),
		sprintf( $default_blog_longdesc, $blog_shortname, '' ),
		$default_locale,
		sprintf( T_('Notes for %s'), $blog_shortname ),
		sprintf( T_('Keywords for %s'), $blog_shortname ),
		4 );

	$blog_shortname = 'Linkblog';
	$blog_stub = 'links';
	$blog_more_longdesc = '<br />
<br />
<strong>'.T_("The main purpose for this blog is to be included as a side item to other blogs where it will display your favorite/related links.").'</strong>';
	$blog_linkblog_ID = blog_create(
		sprintf( T_('%s Title'), $blog_shortname ),
		$blog_shortname,
		'',
		$blog_stub,
		$blog_stub.'.html',
		sprintf( T_('Tagline for %s'), $blog_shortname ),
		sprintf( T_('Short description for %s'), $blog_shortname ),
		sprintf( $default_blog_longdesc, $blog_shortname, $blog_more_longdesc ),
		$default_locale,
		sprintf( T_('Notes for %s'), $blog_shortname ),
		sprintf( T_('Keywords for %s'), $blog_shortname ),
		0 /* no Link blog */ );

	echo "OK.<br />\n";
}


/**
 * Create default categories.
 *
 * This is called for fresh installs and cafelog upgrade.
 *
 * @param boolean
 */
function create_default_categories( $populate_blog_a = true )
{
	global $query, $timestamp;
	global $cat_ann_a, $cat_news, $cat_bg, $cat_ann_b, $cat_fun, $cat_life, $cat_web, $cat_sports, $cat_movies, $cat_music, $cat_b2evo, $cat_linkblog_b2evo, $cat_linkblog_contrib;

	echo 'Creating sample categories... ';

	if( $populate_blog_a )
	{
		// Create categories for blog A
		$cat_ann_a = cat_create( 'Announcements [A]', 'NULL', 2 );
		$cat_news = cat_create( 'News', 'NULL', 2 );
		$cat_bg = cat_create( 'Background', 'NULL', 2 );
	}

	// Create categories for blog B
	$cat_ann_b = cat_create( 'Announcements [B]', 'NULL', 3 );
	$cat_fun = cat_create( 'Fun', 'NULL', 3 );
	$cat_life = cat_create( 'In real life', $cat_fun, 3 );
	$cat_web = cat_create( 'On the web', $cat_fun, 3 );
	$cat_sports = cat_create( 'Sports', $cat_life, 3 );
	$cat_movies = cat_create( 'Movies', $cat_life, 3 );
	$cat_music = cat_create( 'Music', $cat_life, 3 );
	$cat_b2evo = cat_create( 'b2evolution Tips', 'NULL', 3 );

	// Create categories for linkblog
	$cat_linkblog_b2evo = cat_create( 'b2evolution', 'NULL', 4 );
	$cat_linkblog_contrib = cat_create( 'contributors', 'NULL', 4 );

	echo "OK.<br />\n";
}


/**
 * Create default contents.
 *
 * This is called for fresh installs and cafelog upgrade.
 *
 * @param boolean
 */
function create_default_contents( $populate_blog_a = true )
{
	global $query, $timestamp;
	global $cat_ann_a, $cat_news, $cat_bg, $cat_ann_b, $cat_fun, $cat_life, $cat_web, $cat_sports, $cat_movies, $cat_music, $cat_b2evo, $cat_linkblog_b2evo, $cat_linkblog_contrib;

	echo 'Creating sample posts... ';

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("Clean Permalinks!"), T_("b2evolution uses old-style permalinks and feedback links by default. This is to ensure maximum compatibility with various webserver configurations.

Nethertheless, once you feel comfortable with b2evolution, you should try activating clean permalinks in the Settings screen... (check 'Use extra-path info')"), $now, $cat_b2evo );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("Apache optimization..."), T_("In the <code>/blogs</code> folder as well as in <code>/blogs/admin</code> there are two files called [<code>sample.htaccess</code>]. You should try renaming those to [<code>.htaccess</code>].

This will optimize the way b2evolution is handled by the webserver (if you are using Apache). These files are not active by default because a few hosts would display an error right away when you try to use them. If this happens to you when you rename the files, just remove them and you'll be fine."), $now, $cat_b2evo );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("About evoSkins..."), T_("By default, b2evolution blogs are displayed using a default skin.

Readers can choose a new skin by using the skin switcher integrated in most skins.

You can change the default skin used for any blog by editing the blog parameters in the admin interface. You can also force the use of the default skin for everyone.

Otherwise, you can restrict available skins by deleting some of them from the /blogs/skins folder. You can also create new skins by duplicating, renaming and customizing any existing skin folder.

To start customizing a skin, open its '<code>_main.php</code>' file in an editor and read the comments in there. And, of course, read the manual on evoSkins!"), $now, $cat_b2evo );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("Skins, Stubs and Templates..."), T_("By default, all pre-installed blogs are displayed using a skin. (More on skins in another post.)

That means, blogs are accessed through '<code>index.php</code>', which loads default parameters from the database and then passes on the display job to a skin.

Alternatively, if you don't want to use the default DB parameters and want to, say, force a skin, a category or a specific linkblog, you can create a stub file like the provided '<code>a_stub.php</code>' and call your blog through this stub instead of index.php .

Finally, if you need to do some very specific customizations to your blog, you may use plain templates instead of skins. In this case, call your blog through a full template, like the provided '<code>a_noskin.php</code>'.

You will find more information in the stub/template files themselves. Open them in a text editor and read the comments in there.

Either way, make sure you go to the blogs admin and set the correct access method for your blog. When using a stub or a template, you must also set its filename in the 'Stub name' field. Otherwise, the permalinks will not function properly."), $now, $cat_b2evo );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("Multiple Blogs, new blogs, old blogs..."),
								T_("By default, b2evolution comes with 4 blogs, named 'Blog All', 'Blog A', 'Blog B' and 'Linkblog'.

Some of these blogs have a special role. Read about it on the corresponding page.

You can create additional blogs or delete unwanted blogs from the blogs admin."), $now, $cat_b2evo );


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

It is the last page.'), $now, $cat_b2evo, ( $populate_blog_a ? array( $cat_bg , $cat_b2evo ) : array ( $cat_b2evo ) ) );


	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_('Extended post with no teaser'), T_('This is an extended post with no teaser. This means that you won\'t see this teaser any more when you click the "more" link.

<!--more--><!--noteaser-->

This is the extended text. You only see it when you have clicked the "more" link.'), $now, $cat_b2evo, ( $populate_blog_a ? array( $cat_bg , $cat_b2evo ) : array ( $cat_b2evo ) ) );


	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_('Extended post'), T_('This is an extended post. This means you only see this small teaser by default and you must click on the link below to see more.

<!--more-->

This is the extended text. You only see it when you have clicked the "more" link.'), $now, $cat_b2evo, ( $populate_blog_a ? array( $cat_bg , $cat_b2evo ) : array ( $cat_b2evo ) ) );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_("Important information"), T_("Blog B contains a few posts in the 'b2evolution Tips' category.

All these entries are designed to help you so, as EdB would say: \"<em>read them all before you start hacking away!</em>\" ;)

If you wish, you can delete these posts one by one after you have read them. You could also change their status to 'deprecated' in order to visually keep track of what you have already read."), $now, $cat_b2evo, ( $populate_blog_a ? array( $cat_ann_a , $cat_ann_b ) : array ( $cat_ann_b ) ) );

	echo "OK.<br />\n";

}


/**
 * Insert default settings into T_settings.
 *
 * It only writes those to DB, that get overridden (passed as array), or have
 * no default in {@link _generalsettings.class.php} / {@link GeneralSettings::default}.
 *
 * @param array associative array (settings name => value to use), allows
 *              overriding of defaults
 */
function create_default_settings( $override = array() )
{
	global $DB, $new_db_version, $default_locale, $Group_Users;

	$defaults = array(
		'db_version' => $new_db_version,
		'default_locale' => $default_locale,
		'newusers_grp_ID' => $Group_Users->get('ID'),
	);

	$settings = array_merge( array_keys($defaults), array_keys($override) );
	$settings = array_unique( $settings );
	$insertvalues = array();
	foreach( $settings as $name )
	{
		if( isset($override[$name]) )
		{
			$insertvalues[] = '('.$DB->quote($name).', '.$DB->quote($override[$name]).')';
		}
		else
		{
			$insertvalues[] = '('.$DB->quote($name).', '.$DB->quote($defaults[$name]).')';
		}
	}

	echo 'Creating default settings'.( count($override) ? ' (with '.count($override).' existing values)' : '' ).'... ';
	$DB->query(
		"INSERT INTO T_settings (set_name, set_value)
		VALUES ".implode( ', ', $insertvalues ) );
	echo "OK.<br />\n";
}


/**
 * This is called only for fresh installs and fills the tables with
 * demo/tutorial things.
 */
function populate_main_tables()
{
	global $baseurl, $new_db_version;
	global $random_password, $query;
	global $timestamp, $admin_email;
	global $Group_Admins, $Group_Privileged, $Group_Bloggers, $Group_Users;
	global $blog_all_ID, $blog_a_ID, $blog_b_ID, $blog_linkblog_ID;
	global $cat_ann_a, $cat_news, $cat_bg, $cat_ann_b, $cat_fun, $cat_life, $cat_web, $cat_sports, $cat_movies, $cat_music, $cat_b2evo, $cat_linkblog_b2evo, $cat_linkblog_contrib;
	global $DB;
	global $default_locale, $install_password;

	create_default_blogs();

	create_default_categories();

	echo 'Creating default users... ';

	// USERS !
	$User_Admin = & new User();
	$User_Admin->set( 'login', 'admin' );
	if( !isset( $install_password ) )
	{
		$random_password = generate_random_key();
	}
	else
	{
		$random_password = $install_password;
	}
	$User_Admin->set( 'pass', md5($random_password) );	// random
	$User_Admin->set( 'nickname', 'admin' );
	$User_Admin->set( 'email', $admin_email );
	$User_Admin->set( 'ip', '127.0.0.1' );
	$User_Admin->set( 'domain', 'localhost' );
	$User_Admin->set( 'level', 10 );
	$User_Admin->set( 'locale', $default_locale );
	$User_Admin->set_datecreated( $timestamp++ );
	// Note: NEVER use database time (may be out of sync + no TZ control)
	$User_Admin->setGroup( $Group_Admins );
	$User_Admin->dbinsert();

	$User_Demo = & new User();
	$User_Demo->set( 'login', 'demouser' );
	$User_Demo->set( 'pass', md5($random_password) ); // random
	$User_Demo->set( 'nickname', 'Mr. Demo' );
	$User_Demo->set( 'email', $admin_email );
	$User_Demo->set( 'ip', '127.0.0.1' );
	$User_Demo->set( 'domain', 'localhost' );
	$User_Demo->set( 'level', 0 );
	$User_Demo->set( 'locale', $default_locale );
	$User_Demo->set_datecreated( $timestamp++ );
	$User_Demo->setGroup( $Group_Users );
	$User_Demo->dbinsert();

	echo "OK.<br />\n";


	echo 'Creating sample posts for blog A... ';

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_('First Post'), T_('<p>This is the first post.</p>

<p>It appears on both blog A and blog B.</p>'), $now, $cat_ann_a, array( $cat_ann_b ) );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_('Second post'), T_('<p>This is the second post.</p>

<p>It appears on blog A only but in multiple categories.</p>'), $now, $cat_news, array( $cat_ann_a, $cat_bg ) );

	// Insert a post:
	$now = date('Y-m-d H:i:s',$timestamp++);
	$edited_Item = & new Item();
	$edited_Item->insert( 1, T_('Third post'), T_('<p>This is the third post.</p>

<p>It appears on blog B only and in a single category.</p>'), $now, $cat_fun );

	echo "OK.<br />\n";


	// POPULATE THE LINKBLOG:
	populate_linkblog( $now, $cat_linkblog_b2evo, $cat_linkblog_contrib );

	// Create blog B contents:
	create_default_contents();


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

	create_default_settings();
}


/**
 * Create relations
 */
function create_b2evo_relations()
{
	global $DB, $db_use_fkeys;

	if( !$db_use_fkeys )
		return false;

	echo 'Creating relations... ';

	$DB->query( 'alter table T_coll_user_perms
								add constraint FK_bloguser_blog_ID
											foreign key (bloguser_blog_ID)
											references T_blogs (blog_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_coll_user_perms
								add constraint FK_bloguser_user_ID
											foreign key (bloguser_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_categories
								add constraint FK_cat_blog_ID
											foreign key (cat_blog_ID)
											references T_blogs (blog_ID)
											on delete restrict
											on update restrict,
								add constraint FK_cat_parent_ID
											foreign key (cat_parent_ID)
											references T_categories (cat_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_comments
								add constraint FK_comment_post_ID
											foreign key (comment_post_ID)
											references T_posts (post_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_postcats
								add constraint FK_postcat_cat_ID
											foreign key (postcat_cat_ID)
											references T_categories (cat_ID)
											on delete restrict
											on update restrict,
								add constraint FK_postcat_post_ID
											foreign key (postcat_post_ID)
											references T_posts (post_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_posts
								add constraint FK_post_assigned_user_ID
											foreign key (post_assigned_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict,
								add constraint FK_post_lastedit_user_ID
											foreign key (post_lastedit_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict,
								add constraint FK_post_creator_user_ID
											foreign key (post_creator_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict,
								add constraint FK_post_main_cat_ID
											foreign key (post_main_cat_ID)
											references T_categories (cat_ID)
											on delete restrict
											on update restrict,
								add constraint FK_post_parent_ID
											foreign key (post_parent_ID)
											references T_posts (post_ID)
											on delete restrict
											on update restrict,
								add constraint FK_post_pst_ID
											foreign key (post_pst_ID)
											references T_itemstatuses (pst_ID)
											on delete restrict
											on update restrict,
								add constraint FK_post_ptyp_ID
											foreign key (post_ptyp_ID)
											references T_itemtypes (ptyp_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_links
								add constraint FK_link_creator_user_ID
											foreign key (link_creator_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict' );
	$DB->query( 'alter table T_links
								add constraint FK_link_lastedit_user_ID
											foreign key (link_lastedit_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict' );
	$DB->query( 'alter table T_links
								add constraint FK_link_dest_itm_ID
											foreign key (link_dest_itm_ID)
											references T_posts (post_ID)
											on delete restrict
											on update restrict' );
	$DB->query( 'alter table T_links
								add constraint FK_link_file_ID
											foreign key (link_file_ID)
											references T_files (file_ID)
											on delete restrict
											on update restrict' );
	$DB->query( 'alter table T_links
								add constraint FK_link_itm_ID
											foreign key (link_itm_ID)
											references T_posts (post_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_pluginsettings
	              add constraint FK_pset_plug_ID
	                    foreign key (pset_plug_ID)
	                    references T_plugins (plug_ID)
	                    on delete restrict
	                    on update restrict' );

	$DB->query( 'alter table T_pluginevents
	              add constraint FK_pevt_plug_ID
	                    foreign key (pevt_plug_ID)
	                    references T_plugins (plug_ID)
	                    on delete restrict
	                    on update restrict' );

	$DB->query( 'alter table T_users
								add constraint FK_user_grp_ID
											foreign key (user_grp_ID)
											references T_groups (grp_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_usersettings
								add constraint FK_uset_user_ID
											foreign key (uset_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_subscriptions
								add constraint FK_sub_coll_ID
											foreign key (sub_coll_ID)
											references T_blogs (blog_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_subscriptions
								add constraint FK_sub_user_ID
											foreign key (sub_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict' );

	echo "OK.<br />\n";
}


/**
 * Install basic plugins.
 *
 * This gets called separately on fresh installs.
 *
 * NOTE: this won't call the "AfterInstall" method on the plugin nor install its DB schema.
 *       This get done in the plugins controller currently and would need to be changed/added here, if needed later.
 */
function install_basic_plugins()
{
	echo 'Installing default plugins... ';
	$Plugins = & new Plugins();
	// Toolbars:
	$Plugins->install( 'quicktags_plugin' );
	// Renderers:
	$Plugins->install( 'auto_p_plugin' );
	$Plugins->install( 'texturize_plugin' );
	// SkinTags:
	$Plugins->install( 'calendar_plugin' );
	$Plugins->install( 'archives_plugin' );
	$Plugins->install( 'categories_plugin' );
	echo "OK.<br />\n";
}

/*
 * $Log$
 * Revision 1.177  2006/02/24 19:59:29  blueyed
 * New install/upgrade, which makes use of db_delta()
 *
 * Revision 1.176  2006/02/23 21:12:33  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.175  2006/02/13 20:20:10  fplanque
 * minor / cleanup
 *
 * Revision 1.174  2006/02/11 01:08:19  blueyed
 * Oh what fun it is to drop some "e".
 *
 * Revision 1.173  2006/02/10 22:05:07  fplanque
 * Normalized itm links
 *
 * Revision 1.172  2006/02/03 17:35:17  blueyed
 * post_renderers as TEXT
 *
 * Revision 1.171  2006/01/28 18:25:02  blueyed
 * pset_value as TEXT
 *
 * Revision 1.170  2006/01/26 22:43:58  blueyed
 * Added comment_spam_karma field
 *
 * Revision 1.169  2006/01/06 18:58:09  blueyed
 * Renamed Plugin::apply_when to $apply_rendering; added T_plugins.plug_apply_rendering and use it to find Plugins which should apply for rendering in Plugins::validate_list().
 *
 * Revision 1.168  2006/01/06 00:11:47  blueyed
 * Fix potential SQL error when upgrading from < 0.9 to Phoenix
 *
 * Revision 1.167  2005/12/30 18:54:59  fplanque
 * minor
 *
 * Revision 1.166  2005/12/30 18:08:24  fplanque
 * no message
 *
 * Revision 1.165  2005/12/29 20:20:01  blueyed
 * Renamed T_plugin_settings to T_pluginsettings
 *
 * Revision 1.164  2005/12/22 23:13:40  blueyed
 * Plugins' API changed and handling optimized
 *
 * Revision 1.163  2005/12/20 18:11:40  fplanque
 * no message
 *
 * Revision 1.162  2005/12/14 22:30:06  blueyed
 * Fix inserting default filetypes for MySQL 3
 *
 * Revision 1.161  2005/12/14 19:36:16  fplanque
 * Enhanced file management
 *
 * Revision 1.160  2005/12/12 20:32:58  fplanque
 * no message
 *
 * Revision 1.159  2005/12/12 19:22:03  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.158  2005/12/11 00:22:53  blueyed
 * MySQL strict mode fixes. (SET sql_mode = "TRADITIONAL";)
 *
 * Revision 1.157  2005/11/22 20:51:38  fplanque
 * no message
 *
 * Revision 1.153  2005/11/16 17:20:23  fplanque
 * hit_ID moved back to INT for performance reasons.
 *
 * Revision 1.152  2005/11/05 01:53:54  blueyed
 * Linked useragent to a session rather than a hit;
 * SQL: moved T_hitlog.hit_agnt_ID to T_sessions.sess_agnt_ID
 *
 * Revision 1.151  2005/10/31 23:20:45  fplanque
 * keeping things straight...
 *
 * Revision 1.150  2005/10/31 08:19:07  blueyed
 * Refactored getRandomPassword() and Session::generate_key() into generate_random_key()
 *
 * Revision 1.149  2005/10/31 01:38:45  blueyed
 * create_default_settings(): rely on defaults from $Settings
 *
 * Revision 1.148  2005/10/29 21:00:01  blueyed
 * Moved $db_use_fkeys to $EvoConfig->DB['use_fkeys'].
 *
 * Revision 1.147  2005/10/27 00:11:12  mfollett
 * fixed my own error which would disallow installation because of an extra comma in the create table for the sessions table
 *
 * Revision 1.146  2005/10/26 22:49:03  mfollett
 * Removed the unique requirement for IP and user ID on the sessions table.
 *
 * Revision 1.145  2005/10/03 18:10:08  fplanque
 * renamed post_ID field
 *
 * Revision 1.144  2005/10/03 17:26:44  fplanque
 * synched upgrade with fresh DB;
 * renamed user_ID field
 *
 * Revision 1.143  2005/10/03 16:30:42  fplanque
 * fixed hitlog upgrade because daniel didn't do it :((
 *
 */
?>