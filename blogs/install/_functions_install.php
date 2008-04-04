<?php
/**
 * This file implements support functions for the installer
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package install
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * This will offer other display methods in the future
 */
function task_begin( $title )
{
	echo $title;
}


/**
 * This will offer other display methods in the future
 */
function task_end()
{
	echo "OK.<br />\n";
	flush();
}


function get_db_version()
{
	global $DB;

	$DB->halt_on_error = false;
	$DB->show_errors = false;

	$r = NULL;

	if( db_col_exists( 'T_settings', 'set_name' ) )
	{ // we have new table format (since 0.9)
		$r = $DB->get_var( 'SELECT set_value FROM T_settings WHERE set_name = "db_version"' );
	}
	else
	{
		$r = $DB->get_var( 'SELECT db_version FROM T_settings' );
	}

	$DB->halt_on_error = true;
	$DB->show_errors = true;

	return $r;
}


/**
 * @return boolean Does a given column name exist in DB?
 */
function db_col_exists( $table, $col_name )
{
	global $DB;

	$col_name = strtolower($col_name);

	foreach( $DB->get_results('SHOW COLUMNS FROM '.$table) as $row )
		if( strtolower($row->Field) == $col_name )
			return true;

	return false;
}


/**
 * Clean up extra quotes in comments
 */
function cleanup_comment_quotes()
{
  global $DB;

	echo "Checking for extra quote escaping in comments... ";
	$query = "SELECT comment_ID, comment_content
							FROM T_comments
						 WHERE comment_content LIKE '%\\\\\\\\\'%'
						 		OR comment_content LIKE '%\\\\\\\\\"%' ";
	/* FP: the above looks overkill, but MySQL is really full of surprises...
					tested on 4.0.14-nt */
	// echo $query;
	$rows = $DB->get_results( $query, ARRAY_A );
	if( $DB->num_rows )
	{
		echo 'Updating '.$DB->num_rows.' comments... ';
		foreach( $rows as $row )
		{
			$query = "UPDATE T_comments
								SET comment_content = ".$DB->quote( stripslashes( $row['comment_content'] ) )."
								WHERE comment_ID = ".$row['comment_ID'];
			// echo '<br />'.$query;
			$DB->query( $query );
		}
	}
	echo "OK.<br />\n";

}


/**
 * Validate install requirements.
 *
 * @return array List of errors, empty array if ok.
 */
function install_validate_requirements()
{
	$errors = array();

	return $errors;
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
		'newusers_grp_ID' => $Group_Users->ID,
		'default_blog_ID' => 1,
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
 * Install basic skins.
 */
function install_basic_skins()
{
	load_class( 'skins/model/_skin.class.php' );

	echo 'Installing default skins... ';

	// Note: Skin #1 will we used by Blog A
	$Skin = new Skin();
	$Skin->install( 'evopress' );

	// Note: Skin #2 will we used by Blog B
	$Skin = new Skin();
	$Skin->install( 'evocamp' );

	// Note: Skin #3 will we used by Linkblog
	$Skin = new Skin();
	$Skin->install( 'miami_blue' );

	// Note: Skin #4 will we used by Photoblog
	$Skin = new Skin();
	$Skin->install( 'photoblog' );

	$Skin = new Skin();
	$Skin->install( 'asevo' );

	$Skin = new Skin();
	$Skin->install( 'custom' );

	$Skin = new Skin();
	$Skin->install( 'natural_pink' );

	$Skin = new Skin();
	$Skin->install( 'nifty_corners' );


	$Skin = new Skin();
	$Skin->install( '_atom', 'Atom' );

	$Skin = new Skin();
	$Skin->install( '_rss2', 'RSS 2.0' );

	echo "OK.<br />\n";
}


/**
 * Install basic plugins.
 *
 * This gets called separately on fresh installs.
 *
 * {@internal
 * NOTE: this won't call the "AfterInstall" method on the plugin nor install its DB schema.
 *       This get done in the plugins controller, on manually installing a plugin.
 *
 * If you change the number of plugins here, please also adjust {@link InstallUnitTestCase::nr_of_basic_plugins}.
 * }}
 *
 * @param integer Old DB version, so that only new plugins gets installed
 */
function install_basic_plugins( $old_db_version = 0 )
{
	$Plugins_admin = & get_Cache('Plugins_admin');

	if( $old_db_version < 9100 )
	{
		echo 'Installing default plugins... ';
		// Toolbars:
		$Plugins_admin->install( 'quicktags_plugin' );
		// Renderers:
		$Plugins_admin->install( 'auto_p_plugin' );
		$Plugins_admin->install( 'autolinks_plugin' );
		$Plugins_admin->install( 'texturize_plugin' );
		$Plugins_admin->install( 'smilies_plugin' );
		$Plugins_admin->install( 'videoplug_plugin' );
		// SkinTags:
		$Plugins_admin->install( 'calendar_plugin' );
		$Plugins_admin->install( 'archives_plugin' );
		echo "OK.<br />\n";
	}

	if( $old_db_version < 9330 )
	{ // Upgrade to 1.9-beta
		echo 'Installing default ping plugins... ';
		$Plugins_admin->install( 'ping_b2evonet_plugin' );
		$Plugins_admin->install( 'ping_pingomatic_plugin' );
		echo "OK.<br />\n";
	}
}


/**
 * Install basic widgets.
 */
function install_basic_widgets()
{
	global $DB;

	echo 'Installing default widgets... ';

	// Add nlog list to all blog Page Tops:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Page Top", 1, "core", "colls_list_public"
							   FROM T_blogs' );

	// Add title to all blog Headers:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Header", 1, "core", "coll_title"
							   FROM T_blogs' );
	// Add tagline to all blogs Headers:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Header", 2, "core", "coll_tagline"
							   FROM T_blogs' );

	// Add home link to all blogs Menus:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							 SELECT blog_ID, "Menu", 1, "core", "menu_link", "'.$DB->escape(serialize(array('link_type'=>'home'))).'"
							   FROM T_blogs' );
	// Add info pages to all blogs Menus:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Menu", 2, "core", "coll_page_list"
							   FROM T_blogs' );
	// Add contact link to all blogs Menus:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							 SELECT blog_ID, "Menu", 3, "core", "menu_link", "'.$DB->escape(serialize(array('link_type'=>'ownercontact'))).'"
							   FROM T_blogs' );
	// Add login link to all blogs Menus:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							 SELECT blog_ID, "Menu", 4, "core", "menu_link", "'.$DB->escape(serialize(array('link_type'=>'login'))).'"
							   FROM T_blogs' );

	// Add Calendar plugin to all blog Sidebars:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 1, "plugin", "evo_Calr"
							   FROM T_blogs' );
	// Add title to all blog Sidebars:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 2, "core", "coll_title"
							   FROM T_blogs' );
	// Add longdesc to all blogs Sidebars:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 3, "core", "coll_longdesc"
							   FROM T_blogs' );
	// Add common links to all blogs Sidebars:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 4, "core", "coll_common_links"
							   FROM T_blogs' );
	// Add search form to all blogs Sidebars:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 5, "core", "coll_search_form"
							   FROM T_blogs' );
	// Add categories list to all blog Sidebars:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 6, "core", "coll_category_list"
							   FROM T_blogs' );
	// Add linkblog to blog Sidebars for blog A & B:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							 SELECT blog_ID, "Sidebar", 7, "core", "linkblog", \'a:6:{s:5:"title";s:8:"Blogroll";s:11:"linkblog_ID";s:1:"3";s:14:"linkblog_limit";s:3:"100";s:11:"widget_name";s:8:"Linkblog";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}\'
							   FROM T_blogs
							  WHERE blog_ID <= 2' );
	// Add XML feeds to all blogs Sidebars:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 8, "core", "coll_xml_feeds"
							   FROM T_blogs' );

	// Add Tag Cloud to all blog Sidebar2:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							 SELECT blog_ID, "Sidebar 2", 1, "core", "free_html", \'a:5:{s:5:"title";s:9:"Sidebar 2";s:7:"content";s:162:"This is the "Sidebar 2" container. You can place any widget you like in here. In the evo toolbar at the top of this page, select "Customize", then "Blog Widgets".";s:11:"widget_name";s:9:"Free HTML";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}\'
							   FROM T_blogs' );

	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar 2", 2, "core", "coll_post_list"
							   FROM T_blogs' );

	echo "OK.<br />\n";
}



function advanced_properties()
{
	/*
// file_path needs to be case sensitive on unix
// Note: it should be ok on windows too if we take care of updating the db on case renames
ALTER TABLE `T_files` CHANGE `file_path` `file_path` VARCHAR( 255 ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL
or
ALTER TABLE `T_files` CHANGE `file_path` `file_path` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
	*/
}


/**
 * Create relations
 *
 * @todo NOT UP TO DATE AT ALL :( -- update field names before activating this
 */
function create_relations()
{
	global $DB;

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
											references T_items__item (post_ID)
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
											references T_items__item (post_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_items__item
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
											references T_items__item (post_ID)
											on delete restrict
											on update restrict,
								add constraint FK_post_pst_ID
											foreign key (post_pst_ID)
											references T_items__status (pst_ID)
											on delete restrict
											on update restrict,
								add constraint FK_post_ptyp_ID
											foreign key (post_ptyp_ID)
											references T_items__type (ptyp_ID)
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
											references T_items__item (post_ID)
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
											references T_items__item (post_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_pluginsettings
	              add constraint FK_pset_plug_ID
	                    foreign key (pset_plug_ID)
	                    references T_plugins (plug_ID)
	                    on delete restrict
	                    on update restrict' );

	$DB->query( 'alter table T_pluginusersettings
	              add constraint FK_puset_plug_ID
	                    foreign key (puset_plug_ID)
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


/*
 * $Log$
 * Revision 1.47  2008/04/04 17:02:22  fplanque
 * cleanup of global settings
 *
 * Revision 1.46  2008/02/07 00:35:52  fplanque
 * cleaned up install
 *
 * Revision 1.45  2008/01/21 17:56:34  fplanque
 * no more code plugin by default
 *
 * Revision 1.44  2008/01/21 09:35:38  fplanque
 * (c) 2008
 *
 * Revision 1.43  2008/01/12 19:25:58  blueyed
 * - Fix install from < 0.8: Make function "cleanup_post_quotes" inline and fix table name
 * - Only check max_execution_time when > 0 (not disabled)
 *
 * Revision 1.42  2008/01/07 03:00:52  fplanque
 * minor
 *
 * Revision 1.41  2007/12/28 00:13:02  fplanque
 * no message
 *
 * Revision 1.40  2007/12/27 23:56:07  fplanque
 * Better out of the box experience
 *
 * Revision 1.39  2007/12/22 16:59:41  fplanque
 * Miami blue 2.x
 *
 * Revision 1.38  2007/10/08 21:30:19  fplanque
 * evocamp skin
 *
 * Revision 1.37  2007/10/08 08:32:56  fplanque
 * widget fixes
 *
 * Revision 1.36  2007/10/01 01:06:31  fplanque
 * Skin/template functions cleanup.
 *
 * Revision 1.35  2007/09/28 02:17:48  fplanque
 * Menu widgets
 *
 * Revision 1.34  2007/09/19 02:54:16  fplanque
 * bullet proof upgrade
 *
 * Revision 1.33  2007/09/08 23:46:38  fplanque
 * made evopress the new default skin
 *
 * Revision 1.32  2007/09/03 16:46:58  fplanque
 * minor
 *
 * Revision 1.31  2007/08/21 22:32:31  blueyed
 * Use get_Cache() for singleton $Plugins_admin instance. This fixes at least the installation of flickr_plugin.
 *
 * Revision 1.30  2007/07/01 18:49:40  fplanque
 * evopress skin (tentative)
 *
 * Revision 1.29  2007/07/01 03:55:04  fplanque
 * category plugin replaced by widget
 *
 * Revision 1.28  2007/06/25 11:02:30  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.27  2007/06/24 18:28:55  fplanque
 * refactored skin install
 *
 * Revision 1.26  2007/05/14 02:43:06  fplanque
 * Started renaming tables. There probably won't be a better time than 2.0.
 *
 * Revision 1.25  2007/05/13 20:44:52  fplanque
 * more pages support
 *
 * Revision 1.24  2007/05/09 01:58:57  fplanque
 * Widget to display other blogs from same owner
 *
 * Revision 1.23  2007/05/08 19:36:06  fplanque
 * automatic install of public blog list widget on new blogs
 *
 * Revision 1.22  2007/05/07 23:26:19  fplanque
 * public blog list as a widget
 *
 * Revision 1.21  2007/04/26 00:11:10  fplanque
 * (c) 2007
 *
 * Revision 1.20  2007/04/20 02:31:06  fplanque
 * more default plugins
 *
 * Revision 1.19  2007/01/19 09:31:04  fplanque
 * Provision for case sensitive file meta data handling
 *
 * Revision 1.18  2007/01/15 19:10:29  fplanque
 * install refactoring
 *
 * Revision 1.17  2006/11/17 01:46:16  fplanque
 * Fixed broken upgrade path.
 *
 * Revision 1.16  2006/11/01 00:24:07  blueyed
 * Fixed cafelog upgrade
 *
 * Revision 1.15  2006/09/08 15:35:36  blueyed
 * Completely nuked tokenizer dependency - removed commented out block
 *
 * Revision 1.14  2006/08/20 20:54:31  blueyed
 * Removed dependency on tokenizer. Quite a few people don't have it.. see http://forums.b2evolution.net//viewtopic.php?t=8664
 *
 * Revision 1.13  2006/07/04 17:32:30  fplanque
 * no message
 *
 * Revision 1.12  2006/06/19 20:59:38  fplanque
 * noone should die anonymously...
 *
 * Revision 1.11  2006/04/06 08:52:27  blueyed
 * Validate install "misc" requirements ("tokenizer" support for now)
 *
 * Revision 1.10  2005/12/30 18:08:24  fplanque
 * no message
 *
 */
?>