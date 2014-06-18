<?php
/**
 * This file implements additional functional for widgets.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 *
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * @todo factorize!
 *
 * @param integer should never be 0
 * @param boolean should be true only when it's called after initial install
 * @param string Kind of blog ( 'std', 'photo', 'group', 'forum' )
 */
function insert_basic_widgets( $blog_id, $initial_install = false, $kind = '' )
{
	global $DB, $test_install_all_features;
	global $events_blog_ID;

	$default_blog_param = 's:7:"blog_ID";s:0:"";';
	if( $initial_install )
	{	// In the case of initial install, we grab photos out of the photoblog (Blog #4)
		$default_blog_param = 's:7:"blog_ID";s:1:"4";';
	}

	if( false )
	{// old code: NOT USED ANYMORE - TO BE REMOVED SOON.
		/*$DB->query('INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Page Top", 1, "core", "colls_list_public"
							   FROM T_blogs');
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Header", 1, "core", "coll_title"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Header", 2, "core", "coll_tagline"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							 SELECT blog_ID, "Menu", 1, "core", "menu_link", "'.$DB->escape(serialize(array('link_type'=>'home'))).'"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							 SELECT blog_ID, "Menu", 3, "core", "menu_link", "'.$DB->escape(serialize(array('link_type'=>'ownercontact'))).'"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Menu", 2, "core", "coll_page_list"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							 SELECT blog_ID, "Menu", 4, "core", "menu_link", "'.$DB->escape(serialize(array('link_type'=>'login'))).'"
							   FROM T_blogs'  );

		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Menu Top", 1, "core", "coll_search_form"
							   FROM T_blogs'  );

		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 10, "core", "coll_avatar"
							   FROM T_blogs'  );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 20, "plugin", "evo_Calr"
							   FROM T_blogs
							  WHERE blog_ID > 1' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 40, "core", "coll_longdesc"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 30, "core", "coll_title"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 50, "core", "coll_common_links"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 60, "core", "coll_search_form"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 70, "core", "coll_category_list"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							 SELECT blog_ID, "Sidebar", 80, "core", "coll_media_index", \'a:11:{s:5:"title";s:12:"Random photo";s:10:"thumb_size";s:11:"fit-160x120";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"1";s:5:"limit";s:1:"1";s:8:"order_by";s:4:"RAND";s:9:"order_dir";s:3:"ASC";s:7:"blog_ID";s:1:"4";s:11:"widget_name";s:12:"Random photo";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}\'
							   FROM T_blogs
							  WHERE blog_ID <> 2' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							 SELECT blog_ID, "Sidebar", 90, "core", "linkblog", "'.$DB->escape(serialize(array('blog_ID'=>3))).'"
							   FROM T_blogs
							  WHERE blog_ID <= 2' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							 SELECT blog_ID, "Sidebar", 100, "core", "coll_xml_feeds"
							   FROM T_blogs' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
								 SELECT blog_ID, "Sidebar 2", 1, "core", "coll_post_list"
								   FROM T_blogs' );

		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
								 SELECT blog_ID, "Sidebar 2", 2, "core", "coll_comment_list"
								   FROM T_blogs' );

		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
								 SELECT blog_ID, "Sidebar 2", 3, "core", "coll_media_index", \'a:11:{s:5:"title";s:13:"Recent photos";s:10:"thumb_size";s:10:"crop-80x80";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"3";s:5:"limit";s:1:"9";s:8:"order_by";s:9:"datestart";s:9:"order_dir";s:4:"DESC";s:7:"blog_ID";s:1:"4";s:11:"widget_name";s:11:"Photo index";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}\'
								   FROM T_blogs' );

		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
								 SELECT blog_ID, "Sidebar 2", 4, "core", "free_html", \'a:5:{s:5:"title";s:9:"Sidebar 2";s:7:"content";s:162:"This is the "Sidebar 2" container. You can place any widget you like in here. In the evo toolbar at the top of this page, select "Customize", then "Blog Widgets".";s:11:"widget_name";s:9:"Free HTML";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}\'
								   FROM T_blogs' );*/
	}
	else
	{ // New code used in all situations:
		/* Header */
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code ) VALUES
			( '.$blog_id.', "Header", 1, "core", "coll_title" ),
			( '.$blog_id.', "Header", 2, "core", "coll_tagline" )' );

		/* Menu */
		$wi_params_link_home = array( 'link_type' => 'home' );
		if( $kind == 'forum' )
		{
			$wi_params_link_home['link_text'] = T_('Forums Home');
		}
		$widgets_insert_sql = 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params ) VALUES';
		$widgets_insert_sql_rows = array();
		$widgets_insert_sql_rows[] = '( '.$blog_id.', "Menu", 5, "core", "menu_link", "'.$DB->escape( serialize( $wi_params_link_home ) ).'" )';
		if( $blog_id == 1 )
		{ // Recent Posts
			$widgets_insert_sql_rows[] = '( '.$blog_id.', "Menu", 10, "core", "menu_link", "'.$DB->escape( serialize( array( 'link_type' => 'recentposts', 'link_text' => T_('News') ) ) ).'" )';
		}
		if( $kind == 'forum' )
		{ // Latest Topics and Replies ONLY for forum
			$widgets_insert_sql_rows[] = '( '.$blog_id.', "Menu", 13, "core", "menu_link", "'.$DB->escape( serialize( array( 'link_type' => 'recentposts', 'link_text' => T_('Latest topics') ) ) ).'" )';
			$widgets_insert_sql_rows[] = '( '.$blog_id.', "Menu", 15, "core", "menu_link", "'.$DB->escape( serialize( array( 'link_type' => 'latestcomments', 'link_text' => T_('Latest replies') ) ) ).'" )';
		}
		$widgets_insert_sql_rows[] = '( '.$blog_id.', "Menu", 20, "core", "coll_page_list", NULL )';
		if( $kind == 'forum' )
		{ // My Profile
			$widgets_insert_sql_rows[] = '( '.$blog_id.', "Menu", 35, "core", "menu_link", "'.$DB->escape( serialize( array( 'link_type' => 'myprofile' ) ) ).'" )';
		}
		if( $kind != 'forum' )
		{ // Blog owner contact form & Log in form
			$widgets_insert_sql_rows[] = '( '.$blog_id.', "Menu", 30, "core", "menu_link", "'.$DB->escape( serialize( array( 'link_type' => 'ownercontact' ) ) ).'" )';
			$widgets_insert_sql_rows[] = '( '.$blog_id.', "Menu", 40, "core", "menu_link", "'.$DB->escape( serialize( array( 'link_type' => 'login' ) ) ).'" )';
		}
		$widgets_insert_sql_rows[] = '( '.$blog_id.', "Menu", 50, "core", "msg_menu_link", "'.$DB->escape( serialize( array( 'link_type' => 'messages' ) ) ).'" )';
		$widgets_insert_sql_rows[] = '( '.$blog_id.', "Menu", 60, "core", "msg_menu_link", "'.$DB->escape( serialize( array( 'link_type' => 'contacts', 'show_badge' => 0 ) ) ).'" )';
		if( $kind == 'forum' )
		{ // Log in form
			$widgets_insert_sql_rows[] = '( '.$blog_id.', "Menu", 63, "core", "menu_link", "'.$DB->escape( serialize( array( 'link_type' => 'login' ) ) ).'" )';
			$widgets_insert_sql_rows[] = '( '.$blog_id.', "Menu", 66, "core", "menu_link", "'.$DB->escape( serialize( array( 'link_type' => 'register' ) ) ).'" )';
		}
		if( $test_install_all_features && $kind != 'forum' )
		{ // Add menu with User Directory
			$widgets_insert_sql_rows[] = '( '.$blog_id.', "Menu", 70, "core", "menu_link", "'.$DB->escape( serialize( array( 'link_type' => 'users' ) ) ).'" )';
		}
		if( $kind == 'photo' )
		{ // Add menu with Photo index
			$widgets_insert_sql_rows[] = '( '.$blog_id.', "Menu", 75, "core", "menu_link", "'.$DB->escape( serialize( array( 'link_type' => 'mediaidx', 'link_text' => T_('Index') ) ) ).'" )';
		}
		$DB->query( $widgets_insert_sql.implode( ', ', $widgets_insert_sql_rows ) );

		/* Item Single */
		if( ( $blog_id == 1 || ( !empty( $events_blog_ID ) && $blog_id == $events_blog_ID ) ) && $test_install_all_features )
		{
			$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code)
								VALUES( '.$blog_id.', "Item Single", 1, "plugin", "evo_Gmaps")' );
			// add blog collection setting to activate additional fields

			$DB->query( 'REPLACE INTO T_coll_settings(cset_coll_ID, cset_name, cset_value)
								VALUES( '.$blog_id.', "show_location_coordinates" , 1)' );
		}

		/* Menu Top */
		if( $kind == 'forum' )
		{	// Set special params for forums blogs
			$menu_top_params = array(
					'title' => T_('Search this forum:'),
					'button' => T_('Search')
				);
		}
		elseif( $kind == 'manual' )
		{	// Set special params for manual blogs
			$menu_top_params = array(
					'title' => T_('Search this manual:'),
				);
		}
		$menu_top_params = !empty( $menu_top_params ) ? '"'.$DB->escape( serialize( $menu_top_params ) ).'"' : 'NULL';
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							VALUES( '.$blog_id.', "Menu Top", 1, "core", "coll_search_form", '.$menu_top_params.' )' );

		/* Sidebar */
		if( $test_install_all_features )
		{	// Add User login widget
			$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							VALUES( '.$blog_id.', "Sidebar", 10, "core", "user_login" )' );
		}
		if( ( !$initial_install || $blog_id != 5 ) && $kind != 'forum' )
		{	// Don't install these Sidebar widgets for blog 'Forums'
			$widgets_insert_sql = 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params ) VALUES
				( '.$blog_id.', "Sidebar", 20, "core", "user_tools", "'.$DB->escape(serialize(array('title'=>''))).'" ),
				( '.$blog_id.', "Sidebar", 30, "core", "coll_avatar", NULL )';
			if( $blog_id > 1 )
			{
				$widgets_insert_sql .= ',
				( '.$blog_id.', "Sidebar", 40, "plugin", "evo_Calr", NULL )';
			}
			$widgets_insert_sql .= ',
				( '.$blog_id.', "Sidebar", 50, "core", "coll_title", NULL ),
				( '.$blog_id.', "Sidebar", 60, "core", "coll_longdesc", NULL ),
				( '.$blog_id.', "Sidebar", 70, "core", "coll_common_links", NULL ),
				( '.$blog_id.', "Sidebar", 80, "core", "coll_search_form", NULL ),
				( '.$blog_id.', "Sidebar", 90, "core", "coll_category_list", NULL )';
			$DB->query( $widgets_insert_sql );

			if( $blog_id == 3 )
			{ // Advertisements, Install only for blog #3 linkblog/infoblog
				$advertisement_params = array(
						'title' => 'Advertisement (Demo)',
						'item_type' => 4000,
						'blog_ID' => $blog_id,
						'order_by' => 'RAND',
						'limit' => 1,
						'disp_title' => false,
						'item_title_link_type' => 'linkto_url',
						'attached_pics' => 'first',
						'item_pic_link_type' => 'linkto_url',
						'thumb_size' => 'fit-160x160',
					);
				$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
									VALUES( '.$blog_id.', "Sidebar", 100, "core", "coll_item_list", "'.$DB->escape( serialize( $advertisement_params ) ).'" )' );
			}

			if( $blog_id != 2 )
			{
				$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
									VALUES( '.$blog_id.', "Sidebar", 110, "core", "coll_media_index", \'a:11:{s:5:"title";s:12:"Random photo";s:10:"thumb_size";s:11:"fit-160x120";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"1";s:5:"limit";s:1:"1";s:8:"order_by";s:4:"RAND";s:9:"order_dir";s:3:"ASC";'.$default_blog_param.'s:11:"widget_name";s:12:"Random photo";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}\' )' );
			}
			if( $blog_id <= 2 )
			{
				$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
									VALUES( '.$blog_id.', "Sidebar", 120, "core", "linkblog", "'.$DB->escape(serialize(array('blog_ID'=>3))).'" )' );
			}
		}
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							VALUES( '.$blog_id.', "Sidebar", 130, "core", "coll_xml_feeds" )' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
			VALUES ( '.$blog_id.', "Sidebar", 140, "core", "mobile_skin_switcher" )' );

		/* Sidebar 2 */
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							VALUES( '.$blog_id.', "Sidebar 2", 1, "core", "coll_post_list" )' );
		if( $blog_id == 2 )
		{
			$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							VALUES( '.$blog_id.', "Sidebar 2", 5, "core", "coll_link_list", "'.$DB->escape( serialize( array( 'title'=>'Sidebar links', 'order_by'=>'RAND' ) ) ).'" )' );
		}
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code )
							VALUES( '.$blog_id.', "Sidebar 2", 10, "core", "coll_comment_list" )' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							VALUES( '.$blog_id.', "Sidebar 2", 15, "core", "coll_media_index", \'a:11:{s:5:"title";s:13:"Recent photos";s:10:"thumb_size";s:10:"crop-80x80";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"3";s:5:"limit";s:1:"9";s:8:"order_by";s:9:"datestart";s:9:"order_dir";s:4:"DESC";'.$default_blog_param.'s:11:"widget_name";s:11:"Photo index";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}\' )' );
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params )
							VALUES( '.$blog_id.', "Sidebar 2", 20, "core", "free_html", \'a:5:{s:5:"title";s:9:"Sidebar 2";s:7:"content";s:162:"This is the "Sidebar 2" container. You can place any widget you like in here. In the evo toolbar at the top of this page, select "Customize", then "Blog Widgets".";s:11:"widget_name";s:9:"Free HTML";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}\' )' );

		/* Front Page Main Area */
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code ) VALUES
			( '.$blog_id.', "Front Page Main Area", 10, "core", "coll_featured_intro" ),
			( '.$blog_id.', "Front Page Main Area", 20, "core", "coll_post_list" ),
			( '.$blog_id.', "Front Page Main Area", 30, "core", "coll_comment_list" )' );

		/* Mobile Footer */
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code ) VALUES
			( '.$blog_id.', "Mobile: Footer", 10, "core", "coll_longdesc" ),
			( '.$blog_id.', "Mobile: Footer", 20, "core", "mobile_skin_switcher" )' );

		/* Mobile Navigation Menu */
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params ) VALUES
			( '.$blog_id.', "Mobile: Navigation Menu", 10, "core", "coll_page_list", NULL ),
			( '.$blog_id.', "Mobile: Navigation Menu", 20, "core", "menu_link", "'.$DB->escape(serialize(array('link_type'=>'ownercontact'))).'" ),
			( '.$blog_id.', "Mobile: Navigation Menu", 30, "core", "menu_link", "'.$DB->escape(serialize(array('link_type'=>'home'))).'" )' );

		/* Mobile Tools Menu */
		$widgets_insert_sql = 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params ) VALUES
			( '.$blog_id.', "Mobile: Tools Menu", 10, "core", "menu_link", "'.$DB->escape(serialize(array('link_type'=>'login'))).'" ),
			( '.$blog_id.', "Mobile: Tools Menu", 20, "core", "msg_menu_link", "'.$DB->escape(serialize(array('link_type'=>'messages'))).'" ),
			( '.$blog_id.', "Mobile: Tools Menu", 30, "core", "msg_menu_link", "'.$DB->escape(serialize(array('link_type'=>'contacts', 'show_badge'=>0))).'" )';
		if( $test_install_all_features )
		{	// Add menu with User Directory
			$widgets_insert_sql .= ',
			( '.$blog_id.', "Mobile: Tools Menu", 40, "core", "menu_link", "'.$DB->escape(serialize(array('link_type'=>'users'))).'" )';
		}
		$DB->query( $widgets_insert_sql );
	}

}

?>
