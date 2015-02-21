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
 * Add a widget to global array in order to insert it in DB by single SQL query later
 *
 * @param integer Blog ID
 * @param string Container name
 * @param string Type
 * @param string Code
 * @param integer Order
 * @param array|string|NULL Widget params
 * @param integer 1 - enabled, 0 - disabled
 */
function add_basic_widget( $blog_ID, $container_name, $code, $type, $order, $params = NULL, $enabled = 1 )
{
	global $basic_widgets_insert_sql_rows, $DB;

	if( is_null( $params ) )
	{ // NULL
		$params = 'NULL';
	}
	elseif( is_array( $params ) )
	{ // array
		$params = $DB->quote( serialize( $params ) );
	}
	else
	{ // string
		$params = $DB->quote( $params );
	}

	$basic_widgets_insert_sql_rows[] = '( '
		.$blog_ID.', '
		.$DB->quote( $container_name ).', '
		.$order.', '
		.$enabled.', '
		.$DB->quote( $type ).', '
		.$DB->quote( $code ).', '
		.$params.' )';
}


/**
 * Insert the widgets for the blog
 *
 * @param integer should never be 0
 * @param boolean should be true only when it's called after initial install
 * @param string Kind of blog ( 'std', 'photo', 'group', 'forum' )
 */
function insert_basic_widgets( $blog_id, $initial_install = false, $kind = '' )
{
	global $DB, $test_install_all_features, $basic_widgets_insert_sql_rows;

	// Initialize this array first time and clear after previous call of this function
	$basic_widgets_insert_sql_rows = array();
	global $events_blog_ID;
	$events_blog_ID = intval( $events_blog_ID );

	$default_blog_param = 's:7:"blog_ID";s:0:"";';
	if( $initial_install )
	{	// In the case of initial install, we grab photos out of the photoblog (Blog #4)
		$default_blog_param = 's:7:"blog_ID";s:1:"4";';
	}

	/* Header */
	add_basic_widget( $blog_id, 'Header', 'coll_title', 'core', 1 );
	add_basic_widget( $blog_id, 'Header', 'coll_tagline', 'core', 2 );

	/* Menu */
	$widgets_insert_sql = 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_type, wi_code, wi_params ) VALUES';
	if( $kind != 'forum' )
	{ // Home page
		add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 5, array( 'link_type' => 'home' ) );
	}
	if( $blog_id == 1 )
	{ // Recent Posts
		add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 10, array( 'link_type' => 'recentposts', 'link_text' => T_('News') ) );
	}
	if( $kind == 'forum' )
	{ // Latest Topics and Replies ONLY for forum
		add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 13, array( 'link_type' => 'recentposts', 'link_text' => T_('Latest topics') ) );
		add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 15, array( 'link_type' => 'latestcomments', 'link_text' => T_('Latest replies') ) );
	}
	if( $kind != 'forum' )
	{ // Page about blog
		add_basic_widget( $blog_id, 'Menu', 'coll_page_list', 'core', 20 );
	}
	if( $kind == 'forum' )
	{ // My Profile
		add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 25, array( 'link_type' => 'myprofile' ), 0 );
	}
	if( $kind != 'forum' )
	{ // Blog owner contact form
		add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 30, array( 'link_type' => 'ownercontact' ) );
	}
	if( $kind == 'std' )
	{ // Categories
		add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 33, array( 'link_type' => 'catdir' ) );
		// Archives
		add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 35, array( 'link_type' => 'arcdir' ) );
		// Latest comments
		add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 37, array( 'link_type' => 'latestcomments' ) );
	}
	if( $kind != 'forum' )
	{ // Log in form
		add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 40, array( 'link_type' => 'login' ), 0 );
	}
	add_basic_widget( $blog_id, 'Menu', 'msg_menu_link', 'core', 50, array( 'link_type' => 'messages' ), 0 );
	add_basic_widget( $blog_id, 'Menu', 'msg_menu_link', 'core', 60, array( 'link_type' => 'contacts', 'show_badge' => 0 ), 0 );
	if( $kind == 'forum' )
	{ // Log in form
		add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 63, array( 'link_type' => 'login' ), 0 );
		add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 66, array( 'link_type' => 'register' ) );
	}
	if( $test_install_all_features && $kind != 'forum' )
	{ // Add menu with User Directory
		add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 70, array( 'link_type' => 'users' ) );
	}
	if( $kind == 'photo' )
	{ // Add menu with Photo index
		add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 75, array( 'link_type' => 'mediaidx', 'link_text' => T_('Index') ) );
	}

	/* Item Single */
	if( ( $blog_id == 1 || ( !empty( $events_blog_ID ) && $blog_id == $events_blog_ID ) ) && $test_install_all_features )
	{
		add_basic_widget( $blog_id, 'Item Single', 'evo_Gmaps', 'plugin', 1 );
		// add blog collection setting to activate additional fields
		$DB->query( 'REPLACE INTO T_coll_settings ( cset_coll_ID, cset_name, cset_value )
							VALUES ( '.$blog_id.', "show_location_coordinates" , 1 )' );
	}

	/* Menu Top */
	$menu_top_params = NULL;
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
	add_basic_widget( $blog_id, 'Menu Top', 'coll_search_form', 'core', 1, $menu_top_params );

	/* Sidebar */
	if( $test_install_all_features )
	{	// Add User login widget
		add_basic_widget( $blog_id, 'Sidebar', 'user_login', 'core', 10 );
	}
	if( ( !$initial_install || $blog_id != 5 ) && $kind != 'forum' )
	{ // Don't install these Sidebar widgets for blog 'Forums'
		add_basic_widget( $blog_id, 'Sidebar', 'coll_avatar', 'core', 20 );
		if( $blog_id > 1 )
		{
			add_basic_widget( $blog_id, 'Sidebar', 'evo_Calr', 'plugin', 30 );
		}
		add_basic_widget( $blog_id, 'Sidebar', 'coll_longdesc', 'core', 40, array( 'title' => '$title$' ) );
		add_basic_widget( $blog_id, 'Sidebar', 'coll_search_form', 'core', 50 );
		add_basic_widget( $blog_id, 'Sidebar', 'coll_category_list', 'core', 60 );

		if( $blog_id == 3 )
		{ // Advertisements, Install only for blog #3 linkblog/infoblog
			add_basic_widget( $blog_id, 'Sidebar', 'coll_item_list', 'core', 70, array(
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
				) );
		}

		if( $blog_id != 2 )
		{
			add_basic_widget( $blog_id, 'Sidebar', 'coll_media_index', 'core', 80, 'a:11:{s:5:"title";s:12:"Random photo";s:10:"thumb_size";s:11:"fit-160x120";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"1";s:5:"limit";s:1:"1";s:8:"order_by";s:4:"RAND";s:9:"order_dir";s:3:"ASC";'.$default_blog_param.'s:11:"widget_name";s:12:"Random photo";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}' );
		}
		if( $blog_id <= 2 )
		{
			add_basic_widget( $blog_id, 'Sidebar', 'linkblog', 'core', 90, array( 'blog_ID' => 3 ) );
		}
	}
	add_basic_widget( $blog_id, 'Sidebar', 'coll_xml_feeds', 'core', 100 );
	add_basic_widget( $blog_id, 'Sidebar', 'mobile_skin_switcher', 'core', 110 );

	/* Sidebar 2 */
	add_basic_widget( $blog_id, 'Sidebar 2', 'coll_post_list', 'core', 1 );
	if( $blog_id == 2 )
	{
		add_basic_widget( $blog_id, 'Sidebar 2', 'coll_link_list', 'core', 5, array( 'title' => 'Sidebar links', 'order_by' => 'RAND' ) );
	}
	add_basic_widget( $blog_id, 'Sidebar 2', 'coll_comment_list', 'core', 10 );
	add_basic_widget( $blog_id, 'Sidebar 2', 'coll_media_index', 'core', 15, 'a:11:{s:5:"title";s:13:"Recent photos";s:10:"thumb_size";s:10:"crop-80x80";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"3";s:5:"limit";s:1:"9";s:8:"order_by";s:9:"datestart";s:9:"order_dir";s:4:"DESC";'.$default_blog_param.'s:11:"widget_name";s:11:"Photo index";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}' );
	add_basic_widget( $blog_id, 'Sidebar 2', 'free_html', 'core', 20, 'a:5:{s:5:"title";s:9:"Sidebar 2";s:7:"content";s:162:"This is the "Sidebar 2" container. You can place any widget you like in here. In the evo toolbar at the top of this page, select "Customize", then "Blog Widgets".";s:11:"widget_name";s:9:"Free HTML";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}' );

	/* Front Page Main Area */
	add_basic_widget( $blog_id, 'Front Page Main Area', 'coll_featured_intro', 'core', 10 );
	add_basic_widget( $blog_id, 'Front Page Main Area', 'coll_post_list', 'core', 20 );
	add_basic_widget( $blog_id, 'Front Page Main Area', 'coll_comment_list', 'core', 30 );

	/* Mobile Footer */
	add_basic_widget( $blog_id, 'Mobile: Footer', 'coll_longdesc', 'core', 10 );
	add_basic_widget( $blog_id, 'Mobile: Footer', 'mobile_skin_switcher', 'core', 20 );

	/* Mobile Navigation Menu */
	add_basic_widget( $blog_id, 'Mobile: Navigation Menu', 'coll_page_list', 'core', 10 );
	add_basic_widget( $blog_id, 'Mobile: Navigation Menu', 'menu_link', 'core', 20, array( 'link_type' => 'ownercontact' ) );
	add_basic_widget( $blog_id, 'Mobile: Navigation Menu', 'menu_link', 'core', 30, array( 'link_type' => 'home' ) );

	/* Mobile Tools Menu */
	add_basic_widget( $blog_id, 'Mobile: Tools Menu', 'menu_link', 'core', 10, array( 'link_type' => 'login' ) );
	add_basic_widget( $blog_id, 'Mobile: Tools Menu', 'msg_menu_link', 'core', 20, array( 'link_type' => 'messages' ) );
	add_basic_widget( $blog_id, 'Mobile: Tools Menu', 'msg_menu_link', 'core', 30, array( 'link_type' => 'contacts', 'show_badge' => 0 ) );
	add_basic_widget( $blog_id, 'Mobile: Tools Menu', 'menu_link', 'core', 50, array( 'link_type' => 'logout' ) );
	if( $test_install_all_features )
	{ // Add menu with User Directory
		add_basic_widget( $blog_id, 'Mobile: Tools Menu', 'menu_link', 'core', 40, array( 'link_type' => 'users' ) );
	}

	// Check if there are widgets to create
	if( ! empty( $basic_widgets_insert_sql_rows ) )
	{ // Insert the widget records by single SQL query
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_enabled, wi_type, wi_code, wi_params ) '
		           .'VALUES '.implode( ', ', $basic_widgets_insert_sql_rows ) );
	}
}

?>