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
 * @param integer Container ID
 * @param string Type
 * @param string Code
 * @param integer Order
 * @param array|string|NULL Widget params
 * @param integer 1 - enabled, 0 - disabled
 */
function add_basic_widget( $container_ID, $code, $type, $order, $params = NULL, $enabled = 1 )
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
		.$container_ID.', '
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
 * @param array the list of skin ids which are set for the given blog ( normal, mobile and tablet skin ids )
 * @param boolean should be true only when it's called after initial install
 * @param string Kind of blog ( 'std', 'photo', 'group', 'forum' )
 */
function insert_basic_widgets( $blog_id, $skin_ids, $initial_install = false, $kind = '' )
{
	global $DB, $install_test_features, $basic_widgets_insert_sql_rows;

	// Initialize this array first time and clear after previous call of this function
	$basic_widgets_insert_sql_rows = array();

	// Load skin functions needed to get the skin containers
	load_funcs( 'skins/_skin.funcs.php' );

	// Handle all blog IDs which can go from function create_demo_contents()
	global $blog_home_ID, $blog_a_ID, $blog_b_ID, $blog_photoblog_ID, $blog_forums_ID, $blog_manual_ID, $events_blog_ID;
	$blog_home_ID = intval( $blog_home_ID );
	$blog_a_ID = intval( $blog_a_ID );
	$blog_b_ID = intval( $blog_b_ID );
	$blog_photoblog_ID = intval( $blog_photoblog_ID );
	$blog_forums_ID = intval( $blog_forums_ID );
	$blog_manual_ID = intval( $blog_manual_ID );
	$events_blog_ID = intval( $events_blog_ID );

	// Get all global main widget container definitions
	$main_containers = & get_widget_containers();
	// Get all containers declared in the given blog's skins
	$blog_containers = get_skin_containers( $skin_ids );

	// Create rows to insert for all main containers
	$widget_containers_sql_rows = array();
	foreach( $main_containers as $wico_code => $wico_data )
	{
		if( array_key_exists( $wico_code, $blog_containers ) )
		{ // Create only those containers which are part of the selected skins
			$widget_containers_sql_rows[] = '( "'.$wico_code.'", "'.$wico_data['wico_name'].'", '.$blog_id.', '.$wico_data['wico_order'].' )';
		}
	}

	// Insert widget containers records by one SQL query
	$DB->query( 'INSERT INTO T_widget__container( wico_code, wico_name, wico_coll_ID, wico_order ) VALUES'
		.implode( ', ', $widget_containers_sql_rows ) );

	$insert_id = $DB->insert_id;
	foreach( $blog_containers as $wico_code => $wico_data )
	{
		$main_containers[ $wico_code ]['wico_ID'] = $insert_id;
		$insert_id++;
	}

	// Init insert widget query and default params
	$default_blog_param = 's:7:"blog_ID";s:0:"";';
	if( $initial_install && ! empty( $blog_photoblog_ID ) )
	{ // In the case of initial install, we grab photos out of the photoblog (Blog #4)
		$default_blog_param = 's:7:"blog_ID";s:1:"'.intval( $blog_photoblog_ID ).'";';
	}


	/* Header */
	if( array_key_exists( 'header', $blog_containers ) )
	{
		$wico_id = $main_containers['header']['wico_ID'];
		add_basic_widget( $wico_id, 'coll_title', 'core', 1 );
		add_basic_widget( $wico_id, 'coll_tagline', 'core', 2 );
	}


	/* Menu */
	if( $kind != 'main' )
	{ // Don't add widgets to Menu container for Main collections
		if( array_key_exists( 'menu', $blog_containers ) )
		{
			$wico_id = $main_containers['menu']['wico_ID'];
			// Home page
			add_basic_widget( $wico_id, 'menu_link', 'core', 5, array( 'link_type' => 'home' ) );
			if( $blog_id == $blog_b_ID )
			{ // Recent Posts
				add_basic_widget( $wico_id, 'menu_link', 'core', 10, array( 'link_type' => 'recentposts', 'link_text' => T_('News') ) );
			}
			if( $kind == 'forum' )
			{ // Latest Topics and Replies ONLY for forum
				add_basic_widget( $wico_id, 'menu_link', 'core', 13, array( 'link_type' => 'recentposts', 'link_text' => T_('Latest topics') ) );
				add_basic_widget( $wico_id, 'menu_link', 'core', 15, array( 'link_type' => 'latestcomments', 'link_text' => T_('Latest replies') ) );
			}
			if( $kind == 'manual' )
			{ // Latest Topics and Replies ONLY for forum
				add_basic_widget( $wico_id, 'menu_link', 'core', 13, array( 'link_type' => 'recentposts', 'link_text' => T_('Latest pages') ) );
				add_basic_widget( $wico_id, 'menu_link', 'core', 15, array( 'link_type' => 'latestcomments', 'link_text' => T_('Latest comments') ) );
			}
			if( $kind == 'photo' )
			{ // Add menu with Photo index
				add_basic_widget( $wico_id, 'menu_link', 'core', 18, array( 'link_type' => 'mediaidx', 'link_text' => T_('Index') ) );
			}
			if( $kind == 'forum' )
			{ // Add menu with User Directory
				add_basic_widget( $wico_id, 'menu_link', 'core', 20, array( 'link_type' => 'users' ) );
			}
			// Pages list:
			add_basic_widget( $wico_id, 'coll_page_list', 'core', 25 );
			if( $kind == 'forum' )
			{ // My Profile
				add_basic_widget( $wico_id, 'menu_link', 'core', 30, array( 'link_type' => 'myprofile' ), 0 );
			}
			if( $kind == 'std' )
			{ // Categories
				add_basic_widget( $wico_id, 'menu_link', 'core', 33, array( 'link_type' => 'catdir' ) );
				// Archives
				add_basic_widget( $wico_id, 'menu_link', 'core', 35, array( 'link_type' => 'arcdir' ) );
				// Latest comments
				add_basic_widget( $wico_id, 'menu_link', 'core', 37, array( 'link_type' => 'latestcomments' ) );
			}
			add_basic_widget( $wico_id, 'msg_menu_link', 'core', 50, array( 'link_type' => 'messages' ), 0 );
			add_basic_widget( $wico_id, 'msg_menu_link', 'core', 60, array( 'link_type' => 'contacts', 'show_badge' => 0 ), 0 );
			add_basic_widget( $wico_id, 'menu_link', 'core', 70, array( 'link_type' => 'login' ), 0 );
			if( $kind == 'forum' )
			{ // Register
				add_basic_widget( $wico_id, 'menu_link', 'core', 80, array( 'link_type' => 'register' ) );
			}
		}
	}


	/* Item Single */
	if( array_key_exists( 'item_single', $blog_containers ) )
	{
		$wico_id = $main_containers['item_single']['wico_ID'];
		add_basic_widget( $wico_id, 'item_content', 'core', 10 );
		if( $blog_id != $blog_a_ID && $kind != 'forum' && ( empty( $events_blog_ID ) || $blog_id != $events_blog_ID ) )
		{ // Item Tags
			add_basic_widget( $wico_id, 'item_tags', 'core', 20 );
		}
		if( $blog_id == $blog_b_ID )
		{ // About Author
			add_basic_widget( $wico_id, 'item_about_author', 'core', 25 );
		}
		if( ( $blog_id == $blog_a_ID || ( ! empty( $events_blog_ID ) && $blog_id == $events_blog_ID ) ) && $install_test_features )
		{ // Google Maps
			add_basic_widget( $wico_id, 'evo_Gmaps', 'plugin', 30 );
		}
		if( $blog_id == $blog_a_ID || $kind == 'manual' )
		{ // Small Print
			add_basic_widget( $wico_id, 'item_small_print', 'core', 40, array( 'format' => ( $blog_id == $blog_a_ID ? 'standard' : 'revision' ) ) );
		}
		// Seen by
		add_basic_widget( $wico_id, 'item_seen_by', 'core', 50, NULL,
			// Disable this widget for "forum" collections by default:
			$kind == 'forum' ? 0 : 1 );
	}


	/* Sidebar Single */
	if( $kind == 'forum' )
	{
		if( array_key_exists( 'sidebar_single', $blog_containers ) )
		{
			$wico_id = $main_containers['sidebar_single']['wico_ID'];
			add_basic_widget( $wico_id, 'coll_related_post_list', 'core', 1 );
		}
	}


	/* Page Top */
	if( array_key_exists( 'page_top', $blog_containers ) )
	{
		$wico_id = $main_containers['page_top']['wico_ID'];
		add_basic_widget( $wico_id, 'user_links', 'core', 10 );
	}


	/* Sidebar */
	if( array_key_exists( 'sidebar', $blog_containers ) )
	{
		$wico_id = $main_containers['sidebar']['wico_ID'];
		if( $kind == 'manual' )
		{
			$search_form_params = array( 'title' => T_('Search this manual:') );
			add_basic_widget( $wico_id, 'coll_search_form', 'core', 10, $search_form_params );
			add_basic_widget( $wico_id, 'content_hierarchy', 'core', 20 );
		}
		else
		{
			if( $install_test_features )
			{
				if( $kind != 'forum' && $kind != 'manual' )
				{ // Current filters widget
					add_basic_widget( $wico_id, 'coll_current_filters', 'core', 5 );
				}
				// User login widget
				add_basic_widget( $wico_id, 'user_login', 'core', 10 );
			}
			if( ( ! $initial_install || $blog_id != $blog_forums_ID ) && $kind != 'forum' )
			{ // Don't install these Sidebar widgets for blog 'Forums'
				add_basic_widget( $wico_id, 'coll_avatar', 'core', 20 );
				if( $blog_id > $blog_a_ID )
				{
					add_basic_widget( $wico_id, 'evo_Calr', 'plugin', 30 );
				}
				add_basic_widget( $wico_id, 'coll_longdesc', 'core', 40, array( 'title' => '$title$' ) );
				add_basic_widget( $wico_id, 'coll_search_form', 'core', 50 );
				add_basic_widget( $wico_id, 'coll_category_list', 'core', 60 );

				if( $blog_id == $blog_home_ID )
				{ // Advertisements, Install only for blog #1 home blog
					$advertisement_type_ID = $DB->get_var( 'SELECT ityp_ID FROM T_items__type WHERE ityp_name = "Advertisement"' );
					add_basic_widget( $wico_id, 'coll_item_list', 'core', 70, array(
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
					add_basic_widget( $wico_id, 'coll_media_index', 'core', 80, 'a:11:{s:5:"title";s:12:"Random photo";s:10:"thumb_size";s:11:"fit-160x120";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"1";s:5:"limit";s:1:"1";s:8:"order_by";s:4:"RAND";s:9:"order_dir";s:3:"ASC";'.$default_blog_param.'s:11:"widget_name";s:12:"Random photo";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}' );
				}
				if( ! empty( $blog_home_ID ) && ( $blog_id == $blog_a_ID || $blog_id == $blog_b_ID ) )
				{
					$sidebar_type_ID = $DB->get_var( 'SELECT ityp_ID FROM T_items__type WHERE ityp_name = "Sidebar link"' );
					add_basic_widget( $wico_id, 'coll_item_list', 'core', 90, array(
							'blog_ID'              => $blog_home_ID,
							'item_type'            => empty( $sidebar_type_ID ) ? '#' : $sidebar_type_ID,
							'title'                => 'Linkblog',
							'item_group_by'        => 'chapter',
							'item_title_link_type' => 'auto',
							'item_type_usage'      => 'special',
						) );
				}
			}
			add_basic_widget( $wico_id, 'coll_xml_feeds', 'core', 100 );
			add_basic_widget( $wico_id, 'mobile_skin_switcher', 'core', 110 );
		}
	}


	/* Sidebar 2 */
	if( array_key_exists( 'sidebar_2', $blog_containers ) )
	{
		$wico_id = $main_containers['sidebar_2']['wico_ID'];
		add_basic_widget( $wico_id, 'coll_post_list', 'core', 1 );
		if( $blog_id == $blog_b_ID )
		{
			add_basic_widget( $wico_id, 'coll_item_list', 'core', 5, array(
					'title'                => 'Sidebar links',
					'order_by'             => 'RAND',
					'item_title_link_type' => 'auto',
					'item_type_usage'      => 'special',
				) );
		}
		add_basic_widget( $wico_id, 'coll_comment_list', 'core', 10 );
		add_basic_widget( $wico_id, 'coll_media_index', 'core', 15, 'a:11:{s:5:"title";s:13:"Recent photos";s:10:"thumb_size";s:10:"crop-80x80";s:12:"thumb_layout";s:4:"flow";s:12:"grid_nb_cols";s:1:"3";s:5:"limit";s:1:"9";s:8:"order_by";s:9:"datestart";s:9:"order_dir";s:4:"DESC";'.$default_blog_param.'s:11:"widget_name";s:11:"Photo index";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}' );
		add_basic_widget( $wico_id, 'free_html', 'core', 20, 'a:5:{s:5:"title";s:9:"Sidebar 2";s:7:"content";s:162:"This is the "Sidebar 2" container. You can place any widget you like in here. In the evo toolbar at the top of this page, select "Customize", then "Blog Widgets".";s:11:"widget_name";s:9:"Free HTML";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}' );
	}


	/* Front Page Main Area */
	if( array_key_exists( 'front_page_main_area', $blog_containers ) )
	{
		$wico_id = $main_containers['front_page_main_area']['wico_ID'];
		if( $kind == 'main' )
		{ // Display blog title and tagline for main blogs
			add_basic_widget( $wico_id, 'coll_title', 'core', 1 );
			add_basic_widget( $wico_id, 'coll_tagline', 'core', 2 );
		}
		$featured_intro_params = NULL;
		if( $kind == 'main' )
		{ // Hide a title of the front intro post
			$featured_intro_params = array( 'disp_title' => 0 );
		}
		add_basic_widget( $wico_id, 'coll_featured_intro', 'core', 10, $featured_intro_params );
		if( $kind == 'main' )
		{ // Add user links widget only for main kind blogs
			add_basic_widget( $wico_id, 'user_links', 'core', 15 );
		}
		$post_list_params = NULL;
		if( $kind == 'main' )
		{ // Display the posts from all other blogs if it is allowed by blogs setting "Collections to aggregate"
			$post_list_params = array(
					'blog_ID'          => '',
					'limit'            => 5,
					'layout'           => 'list',
					'thumb_size'       => 'crop-80x80',
				);
		}
		add_basic_widget( $wico_id, 'coll_featured_posts', 'core', 20, $post_list_params );
		add_basic_widget( $wico_id, 'coll_post_list', 'core', 25, array( 'title' => T_('More Posts'), 'featured' => 'other' ) );
		if( $kind != 'main' )
		{ // Don't install the "Recent Commnets" widget for Main blogs
			add_basic_widget( $wico_id, 'coll_comment_list', 'core', 30 );
		}
		if( $blog_id == $blog_b_ID )
		{	// Install widget "Poll" only for Blog B on install:
			add_basic_widget( $wico_id, 'poll', 'core', 40, array( 'poll_ID' => 1 ) );
		}
	}


	/* Front Page Secondary Area */
	if( array_key_exists( 'front_page_secondary_area', $blog_containers ) )
	{
		$wico_id = $main_containers['front_page_secondary_area']['wico_ID'];
		add_basic_widget( $wico_id, 'org_members', 'core', 10 );
	}


	/* Mobile Footer */
	if( array_key_exists( 'mobile_footer', $blog_containers ) )
	{
		$wico_id = $main_containers['mobile_footer']['wico_ID'];
		add_basic_widget( $wico_id, 'coll_longdesc', 'core', 10 );
		add_basic_widget( $wico_id, 'mobile_skin_switcher', 'core', 20 );
	}


	/* Mobile Navigation Menu */
	if( array_key_exists( 'mobile_navigation_menu', $blog_containers ) )
	{
		$wico_id = $main_containers['mobile_navigation_menu']['wico_ID'];
		add_basic_widget( $wico_id, 'coll_page_list', 'core', 10 );
		add_basic_widget( $wico_id, 'menu_link', 'core', 20, array( 'link_type' => 'ownercontact' ) );
		add_basic_widget( $wico_id, 'menu_link', 'core', 30, array( 'link_type' => 'home' ) );
		if( $kind == 'forum' )
		{ // Add menu with User Directory
			add_basic_widget( $wico_id, 'menu_link', 'core', 40, array( 'link_type' => 'users' ) );
		}
	}


	/* Mobile Tools Menu */
	if( array_key_exists( 'mobile_tools_menu', $blog_containers ) )
	{
		$wico_id = $main_containers['mobile_tools_menu']['wico_ID'];
		add_basic_widget( $wico_id, 'menu_link', 'core', 10, array( 'link_type' => 'login' ) );
		add_basic_widget( $wico_id, 'msg_menu_link', 'core', 20, array( 'link_type' => 'messages' ) );
		add_basic_widget( $wico_id, 'msg_menu_link', 'core', 30, array( 'link_type' => 'contacts', 'show_badge' => 0 ) );
		add_basic_widget( $wico_id, 'menu_link', 'core', 50, array( 'link_type' => 'logout' ) );
	}


	// Check if there are widgets to create
	if( ! empty( $basic_widgets_insert_sql_rows ) )
	{ // Insert the widget records by single SQL query
		$DB->query( 'INSERT INTO T_widget__widget( wi_wico_ID, wi_order, wi_enabled, wi_type, wi_code, wi_params ) '
		           .'VALUES '.implode( ', ', $basic_widgets_insert_sql_rows ) );
	}
}


/**
 * Get WidgetContainer object from the widget list view widget container fieldset id
 * Note: It is used during creating and reordering widgets
 *
 * @return WidgetContainer
 */
function & get_widget_container( $coll_ID, $container_fieldset_id )
{
	// Get global main widget container definitions
	$main_containers = & get_widget_containers();
	$WidgetContainerCache = & get_WidgetContainerCache();

	if( substr( $container_fieldset_id, 0, 10 ) == 'wico_code_' )
	{ // The widget contianer fieldset id was given by the container code because probably it was not created in the database yet
		$container_code = substr( $container_fieldset_id, 10 );
		$WidgetContainer = $WidgetContainerCache->get_by_coll_and_code( $coll_ID, $container_code );
		if( ! $WidgetContainer )
		{ // The skin container didn't contain any widget before, and it was not saved in the database
			$WidgetContainer = new WidgetContainer();
			$WidgetContainer->set( 'code', $container_code );
			$WidgetContainer->set( 'name', $main_containers[$container_code]['wico_name'] );
			$WidgetContainer->set( 'coll_ID', $coll_ID );
		}
	}
	elseif( substr( $container_fieldset_id, 0, 8 ) == 'wico_ID_' )
	{ // The widget contianer fieldset id contains the container database ID
		$container_ID = substr( $container_fieldset_id, 8 );
		$WidgetContainer = $WidgetContainerCache->get_by_ID( $container_ID );
	}
	else
	{ // The received fieldset id is not valid
		debug_die( 'Invalid container fieldset id received' );
	}

	return $WidgetContainer;
}

?>