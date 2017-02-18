<?php
/**
 * This file implements additional functional for widgets.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * Insert the basic widgets for a collection
 *
 * @param integer should never be 0
 * @param boolean should be true only when it's called after initial install
 * fp> TODO: $initial_install is used to know if we want to trust globals like $blog_photoblog_ID and $blog_forums_ID. We don't want that. 
 *           We should pass a $context array with values like 'photo_source_coll_ID' => 4. 
 *           Also, checking $blog_forums_ID is unnecessary complexity. We can check the colleciton kind == forum
 * @param string Kind of blog ( 'std', 'photo', 'group', 'forum' )
 */
function insert_basic_widgets( $blog_id, $initial_install = false, $kind = '' )
{
	global $DB, $install_test_features, $basic_widgets_insert_sql_rows;

	// Initialize this array first time and clear after previous call of this function
	$basic_widgets_insert_sql_rows = array();

	// Handle all blog IDs which can go from function create_demo_contents()
	global $blog_home_ID, $blog_a_ID, $blog_b_ID, $blog_photoblog_ID, $blog_forums_ID, $blog_manual_ID, $events_blog_ID;
	$blog_home_ID = intval( $blog_home_ID );
	$blog_a_ID = intval( $blog_a_ID );
	$blog_b_ID = intval( $blog_b_ID );
	$blog_photoblog_ID = intval( $blog_photoblog_ID );
	$blog_forums_ID = intval( $blog_forums_ID );
	$blog_manual_ID = intval( $blog_manual_ID );
	$events_blog_ID = intval( $events_blog_ID );

	$default_blog_param = 's:7:"blog_ID";s:0:"";';
	if( $initial_install && ! empty( $blog_photoblog_ID ) )
	{ // In the case of initial install, we grab photos out of the photoblog (Blog #4)
		$default_blog_param = 's:7:"blog_ID";s:1:"'.intval( $blog_photoblog_ID ).'";';
	}


	/* Header */
	add_basic_widget( $blog_id, 'Header', 'coll_title', 'core', 1 );
	add_basic_widget( $blog_id, 'Header', 'coll_tagline', 'core', 2 );


	/* Menu */
	if( $kind != 'main' )
	{ // Don't add widgets to Menu container for Main collections
		// Home page
		add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 5, array( 'link_type' => 'home' ) );
		if( $blog_id == $blog_b_ID )
		{ // Recent Posts
			add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 10, array( 'link_type' => 'recentposts', 'link_text' => T_('News') ) );
		}
		if( $kind == 'forum' )
		{ // Latest Topics and Replies ONLY for forum
			add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 13, array( 'link_type' => 'recentposts', 'link_text' => T_('Latest topics') ) );
			add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 15, array( 'link_type' => 'latestcomments', 'link_text' => T_('Latest replies') ) );
		}
		if( $kind == 'manual' )
		{ // Latest Topics and Replies ONLY for forum
			add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 13, array( 'link_type' => 'recentposts', 'link_text' => T_('Latest pages') ) );
			add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 15, array( 'link_type' => 'latestcomments', 'link_text' => T_('Latest comments') ) );
		}
		if( $kind == 'forum' || $kind == 'manual' )
		{	// Add menu with flagged items:
			add_basic_widget( $blog_id, 'Menu', 'flag_menu_link', 'core', 17, array( 'link_text' => ( $kind == 'forum' ) ? T_('Flagged topics') : T_('Flagged pages') ) );
		}
		if( $kind == 'photo' )
		{ // Add menu with Photo index
			add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 18, array( 'link_type' => 'mediaidx', 'link_text' => T_('Index') ) );
		}
		if( $kind == 'forum' )
		{ // Add menu with User Directory
			add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 20, array( 'link_type' => 'users' ) );
		}
		// Pages list:
		add_basic_widget( $blog_id, 'Menu', 'coll_page_list', 'core', 25 );
		if( $kind == 'forum' )
		{ // My Profile
			add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 30, array( 'link_type' => 'myprofile' ), 0 );
		}
		if( $kind == 'std' )
		{ // Categories
			add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 33, array( 'link_type' => 'catdir' ) );
			// Archives
			add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 35, array( 'link_type' => 'arcdir' ) );
			// Latest comments
			add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 37, array( 'link_type' => 'latestcomments' ) );
		}
		add_basic_widget( $blog_id, 'Menu', 'msg_menu_link', 'core', 50, array( 'link_type' => 'messages' ), 0 );
		add_basic_widget( $blog_id, 'Menu', 'msg_menu_link', 'core', 60, array( 'link_type' => 'contacts', 'show_badge' => 0 ), 0 );
		add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 70, array( 'link_type' => 'login' ), 0 );
		if( $kind == 'forum' )
		{ // Register
			add_basic_widget( $blog_id, 'Menu', 'menu_link', 'core', 80, array( 'link_type' => 'register' ) );
		}
	}

	/* Item Single Header */
	if( in_array( $kind, array( 'forum', 'group' ) ) )
	{
		add_basic_widget( $blog_id, 'Item Single Header', 'item_info_line', 'core', 10, 'a:14:{s:5:"title";s:0:"";s:9:"flag_icon";i:1;s:14:"permalink_icon";i:0;s:13:"before_author";s:10:"started_by";s:11:"date_format";s:8:"extended";s:9:"post_time";i:1;s:12:"last_touched";i:1;s:8:"category";i:0;s:9:"edit_link";i:0;s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";s:16:"allow_blockcache";i:0;s:11:"time_format";s:4:"none";s:12:"display_date";s:12:"date_created";}' );
		add_basic_widget( $blog_id, 'Item Single Header', 'item_tags', 'core', 20 );
		add_basic_widget( $blog_id, 'Item Single Header', 'item_seen_by', 'core', 30 );
	}
	else
	{
		add_basic_widget( $blog_id, 'Item Single Header', 'item_info_line', 'core', 10 );
	}

	/* Item Single */
	add_basic_widget( $blog_id, 'Item Single', 'item_content', 'core', 10 );
	add_basic_widget( $blog_id, 'Item Single', 'item_attachments', 'core', 15 );
	if( $blog_id != $blog_a_ID && ( empty( $events_blog_ID ) || $blog_id != $events_blog_ID ) && ! in_array( $kind, array( 'forum', 'group' ) ) )
	{ // Item Tags
		add_basic_widget( $blog_id, 'Item Single', 'item_tags', 'core', 20 );
	}
	if( $blog_id == $blog_b_ID )
	{ // About Author
		add_basic_widget( $blog_id, 'Item Single', 'item_about_author', 'core', 25 );
	}
	if( ( $blog_id == $blog_a_ID || ( ! empty( $events_blog_ID ) && $blog_id == $events_blog_ID ) ) && $install_test_features )
	{ // Google Maps
		add_basic_widget( $blog_id, 'Item Single', 'evo_Gmaps', 'plugin', 30 );
	}
	if( $blog_id == $blog_a_ID || $kind == 'manual' )
	{ // Small Print
		add_basic_widget( $blog_id, 'Item Single', 'item_small_print', 'core', 40, array( 'format' => ( $blog_id == $blog_a_ID ? 'standard' : 'revision' ) ) );
	}
	if( ! in_array( $kind, array( 'forum', 'group' ) ) )
	{ // Seen by
		add_basic_widget( $blog_id, 'Item Single', 'item_seen_by', 'core', 50 );
	}
	if( $kind != 'forum' )
	{	// Item voting panel:
		add_basic_widget( $blog_id, 'Item Single', 'item_vote', 'core', 60 );
	}


	/* Sidebar Single */
	if( $kind == 'forum' )
	{
		add_basic_widget( $blog_id, 'Sidebar Single', 'coll_related_post_list', 'core', 1 );
	}


	/* Page Top */
	add_basic_widget( $blog_id, 'Page Top', 'social_links', 'core', 10, 'a:19:{s:5:"title";s:0:"";s:5:"link1";s:2:"15";s:10:"link1_href";s:32:"https://twitter.com/b2evolution/";s:5:"link2";s:2:"16";s:10:"link2_href";s:36:"https://www.facebook.com/b2evolution";s:5:"link3";s:2:"17";s:10:"link3_href";s:42:"https://plus.google.com/+b2evolution/posts";s:5:"link4";s:2:"18";s:10:"link4_href";s:48:"https://www.linkedin.com/company/b2evolution-net";s:5:"link5";s:2:"19";s:10:"link5_href";s:42:"https://github.com/b2evolution/b2evolution";s:5:"link6";s:0:"";s:10:"link6_href";s:0:"";s:5:"link7";s:0:"";s:10:"link7_href";s:0:"";s:11:"icon_colors";a:1:{s:7:"hoverbg";s:1:"1";}s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";s:16:"allow_blockcache";i:0;}' );


	/* Sidebar */
	if( $kind == 'manual' )
	{
		$search_form_params = array( 'title' => T_('Search this manual:') );
		add_basic_widget( $blog_id, 'Sidebar', 'coll_search_form', 'core', 10, $search_form_params );
		add_basic_widget( $blog_id, 'Sidebar', 'content_hierarchy', 'core', 20 );
	}
	else
	{
		if( $install_test_features )
		{
			if( $kind != 'forum' && $kind != 'manual' )
			{ // Current filters widget
				add_basic_widget( $blog_id, 'Sidebar', 'coll_current_filters', 'core', 5 );
			}
			// User login widget
			add_basic_widget( $blog_id, 'Sidebar', 'user_login', 'core', 10 );
		}
		if( ( !$initial_install || $blog_id != $blog_forums_ID ) && $kind != 'forum' )
		{ // Don't install these Sidebar widgets for blog 'Forums'
			add_basic_widget( $blog_id, 'Sidebar', 'coll_avatar', 'core', 20 );
			if( $blog_id > $blog_a_ID )
			{
				add_basic_widget( $blog_id, 'Sidebar', 'evo_Calr', 'plugin', 30 );
			}
			add_basic_widget( $blog_id, 'Sidebar', 'coll_longdesc', 'core', 40, array( 'title' => '$title$' ) );
			add_basic_widget( $blog_id, 'Sidebar', 'coll_search_form', 'core', 50 );
			add_basic_widget( $blog_id, 'Sidebar', 'coll_category_list', 'core', 60 );

			if( $blog_id == $blog_home_ID )
			{ // Advertisements, Install only for blog #1 home blog
				$advertisement_type_ID = $DB->get_var( 'SELECT ityp_ID FROM T_items__type WHERE ityp_name = "Advertisement"' );
				add_basic_widget( $blog_id, 'Sidebar', 'coll_item_list', 'core', 70, array(
						'title' => 'Advertisement (Demo)',
						'item_type' => empty( $advertisement_type_ID ) ? '#' : $advertisement_type_ID,
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

			if( $blog_id != $blog_b_ID )
			{
				add_basic_widget( $blog_id, 'Sidebar', 'coll_media_index', 'core', 80, 'a:11:{s:5:"title";s:12:"Random photo";s:10:"thumb_size";s:11:"fit-160x120";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"1";s:5:"limit";s:1:"1";s:8:"order_by";s:4:"RAND";s:9:"order_dir";s:3:"ASC";'.$default_blog_param.'s:11:"widget_name";s:12:"Random photo";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}' );
			}
			if( ! empty( $blog_home_ID ) && ( $blog_id == $blog_a_ID || $blog_id == $blog_b_ID ) )
			{
				$sidebar_type_ID = $DB->get_var( 'SELECT ityp_ID FROM T_items__type WHERE ityp_name = "Sidebar link"' );
				add_basic_widget( $blog_id, 'Sidebar', 'coll_item_list', 'core', 90, array(
						'blog_ID'              => $blog_home_ID,
						'item_type'            => empty( $sidebar_type_ID ) ? '#' : $sidebar_type_ID,
						'title'                => 'Linkblog',
						'item_group_by'        => 'chapter',
						'item_title_link_type' => 'auto',
						'item_type_usage'      => 'special',
					) );
			}
		}
		if( $kind == 'forum' )
		{
			add_basic_widget( $blog_id, 'Sidebar', 'user_avatars', 'core', 90, 'a:13:{s:5:"title";s:17:"Most Active Users";s:10:"thumb_size";s:14:"crop-top-80x80";s:12:"thumb_layout";s:4:"flow";s:12:"grid_nb_cols";s:1:"1";s:5:"limit";s:1:"6";s:9:"bubbletip";i:1;s:8:"order_by";s:8:"numposts";s:5:"style";s:6:"simple";s:6:"gender";s:3:"any";s:8:"location";s:3:"any";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";s:16:"allow_blockcache";i:0;}' );
		}
		add_basic_widget( $blog_id, 'Sidebar', 'coll_xml_feeds', 'core', 100 );
		add_basic_widget( $blog_id, 'Sidebar', 'mobile_skin_switcher', 'core', 110 );
	}


	/* Sidebar 2 */
	if( $kind != 'forum' )
	{
		add_basic_widget( $blog_id, 'Sidebar 2', 'coll_post_list', 'core', 1 );
		if( $blog_id == $blog_b_ID )
		{
			add_basic_widget( $blog_id, 'Sidebar 2', 'coll_item_list', 'core', 5, array(
					'title'                => 'Sidebar links',
					'order_by'             => 'RAND',
					'item_title_link_type' => 'auto',
					'item_type_usage'      => 'special',
				) );
		}
		add_basic_widget( $blog_id, 'Sidebar 2', 'coll_comment_list', 'core', 10 );
		add_basic_widget( $blog_id, 'Sidebar 2', 'coll_media_index', 'core', 15, 'a:11:{s:5:"title";s:13:"Recent photos";s:10:"thumb_size";s:10:"crop-80x80";s:12:"thumb_layout";s:4:"flow";s:12:"grid_nb_cols";s:1:"3";s:5:"limit";s:1:"9";s:8:"order_by";s:9:"datestart";s:9:"order_dir";s:4:"DESC";'.$default_blog_param.'s:11:"widget_name";s:11:"Photo index";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}' );
		add_basic_widget( $blog_id, 'Sidebar 2', 'free_html', 'core', 20, 'a:5:{s:5:"title";s:9:"Sidebar 2";s:7:"content";s:162:"This is the "Sidebar 2" container. You can place any widget you like in here. In the evo toolbar at the top of this page, select "Customize", then "Blog Widgets".";s:11:"widget_name";s:9:"Free HTML";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}' );
	}


	/* Front Page Main Area */
	if( $kind == 'main' )
	{ // Display blog title and tagline for main blogs
		add_basic_widget( $blog_id, 'Front Page Main Area', 'coll_title', 'core', 1 );
		add_basic_widget( $blog_id, 'Front Page Main Area', 'coll_tagline', 'core', 2 );
	}


	if( $kind == 'main' )
	{ // Hide a title of the front intro post
		$featured_intro_params = array( 'disp_title' => 0 );
	}
	else
	{
		$featured_intro_params = NULL;
	}
	add_basic_widget( $blog_id, 'Front Page Main Area', 'coll_featured_intro', 'core', 10, $featured_intro_params );

	if( $kind == 'main' )
	{ // Add user links widget only for main kind blogs
		add_basic_widget( $blog_id, 'Front Page Main Area', 'user_links', 'core', 15 );
	}

	if( $kind == 'main' )
	{ // Display the posts from all other blogs if it is allowed by blogs setting "Collections to aggregate"
		$post_list_params = array(
				'blog_ID'          => '',
				'limit'            => 5,
				'layout'           => 'list',
				'thumb_size'       => 'crop-80x80',
			);
	}
	else
	{
		$post_list_params = NULL;
	}
	add_basic_widget( $blog_id, 'Front Page Main Area', 'coll_featured_posts', 'core', 20, $post_list_params );

	add_basic_widget( $blog_id, 'Front Page Main Area', 'coll_post_list', 'core', 25, array( 'title' => T_('More Posts'), 'featured' => 'other' ) );

	if( $kind != 'main' )
	{ // Don't install the "Recent Commnets" widget for Main blogs
		add_basic_widget( $blog_id, 'Front Page Main Area', 'coll_comment_list', 'core', 30 );
	}

	if( $blog_id == $blog_b_ID )
	{	// Install widget "Poll" only for Blog B on install:
		add_basic_widget( $blog_id, 'Front Page Main Area', 'poll', 'core', 40, array( 'poll_ID' => 1 ) );
	}


	/* Front Page Secondary Area */
	if( $kind == 'main' )
	{	// Install the "Organization Members" widget only for Main collections:
		add_basic_widget( $blog_id, 'Front Page Secondary Area', 'org_members', 'core', 10 );
	}
	add_basic_widget( $blog_id, 'Front Page Secondary Area', 'coll_flagged_list', 'core', 20 );


	/* 404 Page */
	add_basic_widget( $blog_id, '404 Page', 'page_404_not_found', 'core', 10 );
	add_basic_widget( $blog_id, '404 Page', 'coll_search_form', 'core', 20 );
	add_basic_widget( $blog_id, '404 Page', 'coll_tag_cloud', 'core', 30 );


	/* Mobile Footer */
	add_basic_widget( $blog_id, 'Mobile: Footer', 'coll_longdesc', 'core', 10 );
	add_basic_widget( $blog_id, 'Mobile: Footer', 'mobile_skin_switcher', 'core', 20 );


	/* Mobile Navigation Menu */
	add_basic_widget( $blog_id, 'Mobile: Navigation Menu', 'coll_page_list', 'core', 10 );
	add_basic_widget( $blog_id, 'Mobile: Navigation Menu', 'menu_link', 'core', 20, array( 'link_type' => 'ownercontact' ) );
	add_basic_widget( $blog_id, 'Mobile: Navigation Menu', 'menu_link', 'core', 30, array( 'link_type' => 'home' ) );
	if( $kind == 'forum' )
	{ // Add menu with User Directory
		add_basic_widget( $blog_id, 'Mobile: Navigation Menu', 'menu_link', 'core', 40, array( 'link_type' => 'users' ) );
	}


	/* Mobile Tools Menu */
	add_basic_widget( $blog_id, 'Mobile: Tools Menu', 'menu_link', 'core', 10, array( 'link_type' => 'login' ) );
	add_basic_widget( $blog_id, 'Mobile: Tools Menu', 'msg_menu_link', 'core', 20, array( 'link_type' => 'messages' ) );
	add_basic_widget( $blog_id, 'Mobile: Tools Menu', 'msg_menu_link', 'core', 30, array( 'link_type' => 'contacts', 'show_badge' => 0 ) );
	add_basic_widget( $blog_id, 'Mobile: Tools Menu', 'menu_link', 'core', 50, array( 'link_type' => 'logout' ) );


	// Check if there are widgets to create
	if( ! empty( $basic_widgets_insert_sql_rows ) )
	{ // Insert the widget records by single SQL query
		$DB->query( 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_order, wi_enabled, wi_type, wi_code, wi_params ) '
		           .'VALUES '.implode( ', ', $basic_widgets_insert_sql_rows ) );
	}
}

?>