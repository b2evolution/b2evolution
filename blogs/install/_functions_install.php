<?php
/**
 * This file implements support functions for the installer
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package install
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * check_db_version(-)
 *
 * Note: version number 8000 once meant 0.8.00.0, but I decided to switch to sequential
 * increments of 10 (in case we ever need to introduce intermediate versions for intermediate
 * bug fixes...)
 */
function check_db_version()
{
	global $DB, $old_db_version, $new_db_version;

	echo '<p>'.T_('Checking DB schema version...').' ';

	if( db_col_exists( 'T_settings', 'set_name' ) )
	{ // we have new table format (since 0.9)
		$old_db_version = $DB->get_var( 'SELECT set_value FROM T_settings WHERE set_name = "db_version"' );
	}
	else
	{
		$old_db_version = $DB->get_var( 'SELECT db_version FROM T_settings' );
	}

	if( empty($old_db_version) ) debug_die( T_('NOT FOUND! This is not a b2evolution database.') );

	echo $old_db_version, ' : ';

	if( $old_db_version < 8000 ) debug_die( T_('This version is too old!') );
	if( $old_db_version > $new_db_version ) debug_die( T_('This version is too recent! We cannot downgrade to it!') );
	echo "OK.<br />\n";
}


/**
 * Clean up extra quotes in posts
 */
function cleanup_post_quotes( $col_ID = 'ID' )
{
  global $DB;

	echo "Checking for extra quote escaping in posts... ";
	$query = "SELECT $col_ID, post_title, post_content
							FROM T_posts
						 WHERE post_title LIKE '%\\\\\\\\\'%'
						 		OR post_title LIKE '%\\\\\\\\\"%'
						 		OR post_content LIKE '%\\\\\\\\\'%'
						 		OR post_content LIKE '%\\\\\\\\\"%' ";
	/* FP: the above looks overkill, but MySQL is really full of surprises...
					tested on 4.0.14-nt */
	// echo $query;
	$rows = $DB->get_results( $query, ARRAY_A );
	if( $DB->num_rows )
	{
		echo 'Updating '.$DB->num_rows.' posts... ';
		foreach( $rows as $row )
		{
			// echo '<br />'.$row['post_title'];
			$query = "UPDATE T_posts
								SET post_title = ".$DB->quote( stripslashes( $row['post_title'] ) ).",
										post_content = ".$DB->quote( stripslashes( $row['post_content'] ) )."
								WHERE $col_ID = ".$row[$col_ID];
			// echo '<br />'.$query;
			$DB->query( $query );
		}
	}
	echo "OK.<br />\n";

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
	echo 'Installing default skins... ';

	skin_install( 'custom' );
	skin_install( 'natural_pink' );
	skin_install( 'nifty_corners' );
	skin_install( 'photoblog' );

	skin_install( '_atom', 'Atom' );
	skin_install( '_rss2', 'RSS 2.0' );

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
	load_class('_misc/_plugins_admin.class.php');

	$Plugins_admin = & new Plugins_admin();

	if( $old_db_version < 9100 )
	{
		echo 'Installing default plugins... ';
		// Toolbars:
		$Plugins_admin->install( 'quicktags_plugin' );
		// Renderers:
		$Plugins_admin->install( 'auto_p_plugin' );
		$Plugins_admin->install( 'autolinks_plugin' );
		$Plugins_admin->install( 'texturize_plugin' );
		// SkinTags:
		$Plugins_admin->install( 'calendar_plugin' );
		$Plugins_admin->install( 'archives_plugin' );
		$Plugins_admin->install( 'categories_plugin' );
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

	// Add title to all blog Headers:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Header", 1, "core", "coll_title"
							   FROM T_blogs' );
	// Add tagline to all blogs Headers:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Header", 2, "core", "coll_tagline"
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
	// Add Categories plugin to all blog Sidebars:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 6, "plugin", "evo_Cats"
							   FROM T_blogs' );
	// Add XML feeds to all blogs Sidebars:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 7, "core", "coll_xml_feeds"
							   FROM T_blogs' );
	// Add User tools to all blogs Sidebars:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 8, "core", "user_tools"
							   FROM T_blogs' );

	// Add Admin help to all blog Footers:
	$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Footer", 1, "core", "admin_help"
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