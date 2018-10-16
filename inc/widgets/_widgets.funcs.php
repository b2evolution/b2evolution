<?php
/**
 * This file implements additional functional for widgets.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 *
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Get config array of default widgets for install, upgrade and new collections
 *
 * @param string Collection type: 'std', 'main', 'photo', 'group', 'forum', 'manual'
 * @param array Context
 * @return array Array of default widgets:
 *          - Key - Container code,
 *          - Value - array of widget arrays OR SPECIAL VALUES:
 *             - 'coll_type': Include this container only for collection types separated by comma, first char "-" means to exclude,
 *             - 'skin_type': Include this container only for skin types separated by comma, first char "-" means to exclude,
 *             - 'type': Container type, empty - main container, other values: 'sub', 'page', 'shared', 'shared-sub',
 *             - 'name': Container name,
 *             - 'order': Container order,
 *             - widget data array():
 *                - 0: Widget order (*mandatory field*),
 *                - 1: Widget code (*mandatory field*),
 *                - 'params' - Widget params(array or serialized string),
 *                - 'type' - Widget type(default = 'core', another value - 'plugin'),
 *                - 'enabled' - Boolean value; default is TRUE; FALSE to install the widget as disabled,
 *                - 'coll_type': Include this widget only for collection types separated by comma, first char "-" means to exclude,
 *                - 'skin_type': Include this widget only for skin types separated by comma, first char "-" means to exclude,
 *                - 'install' - Boolean value; default is TRUE; FALSE to skip this widget on install.
 */
function get_default_widgets( $coll_type = '', $context = array() )
{
	global $DB;

	$context = array_merge( array(
			'current_coll_ID'       => NULL,
			'coll_home_ID'          => NULL,
			'coll_blog_a_ID'        => NULL,
			'coll_photoblog_ID'     => NULL,
			'init_as_home'          => false,
			'init_as_blog_a'        => false,
			'init_as_blog_b'        => false,
			'init_as_forums'        => false,
			'init_as_events'        => false,
			'install_test_features' => false,
		), $context );

	$default_widgets = array();

	/* Header */
	$default_widgets['header'] = array(
		array( 1, 'coll_title' ),
		array( 2, 'coll_tagline' ),
	);

	/* Menu */
	$default_widgets['menu'] = array(
		'coll_type' => '-main', // Don't add widgets to Menu container for Main collections
		array(  5, 'basic_menu_link', 'coll_type' => '-minisite', 'params' => array( 'link_type' => 'home' ) ),
		array( 10, 'basic_menu_link', 'install' => $context['init_as_blog_b'], 'params' => array( 'link_type' => 'recentposts', 'link_text' => T_('News') ) ),
		array( 13, 'basic_menu_link', 'coll_type' => 'forum', 'params' => array( 'link_type' => 'recentposts', 'link_text' => T_('Latest topics') ) ),
		array( 15, 'basic_menu_link', 'coll_type' => 'forum', 'params' => array( 'link_type' => 'latestcomments', 'link_text' => T_('Latest replies') ) ),
		array( 17, 'flag_menu_link', 'coll_type' => 'forum', 'params' => array( 'link_type' => 'latestcomments', 'link_text' => T_('Flagged topics') ) ),
		array( 13, 'basic_menu_link', 'coll_type' => 'manual', 'params' => array( 'link_type' => 'recentposts', 'link_text' => T_('Latest pages') ) ),
		array( 15, 'basic_menu_link', 'coll_type' => 'manual', 'params' => array( 'link_type' => 'latestcomments', 'link_text' => T_('Latest comments') ) ),
		array( 17, 'flag_menu_link', 'coll_type' => 'manual', 'params' => array( 'link_type' => 'latestcomments', 'link_text' => T_('Flagged pages') ) ),
		array( 18, 'basic_menu_link', 'coll_type' => 'photo', 'params' => array( 'link_type' => 'mediaidx', 'link_text' => T_('Index') ) ),
		array( 20, 'basic_menu_link', 'coll_type' => 'forum', 'params' => array( 'link_type' => 'users' ) ),
		array( 21, 'basic_menu_link', 'coll_type' => 'forum', 'params' => array( 'link_type' => 'visits' ) ),
		array( 25, 'coll_page_list' ),
		array( 30, 'basic_menu_link', 'coll_type' => 'forum', 'params' => array( 'link_type' => 'myprofile' ), 'enabled' => 0 ),
		array( 33, 'basic_menu_link', 'coll_type' => 'std', 'params' => array( 'link_type' => 'catdir' ) ),
		array( 35, 'basic_menu_link', 'coll_type' => 'std', 'params' => array( 'link_type' => 'arcdir' ) ),
		array( 37, 'basic_menu_link', 'coll_type' => 'std', 'params' => array( 'link_type' => 'latestcomments' ) ),
		array( 50, 'msg_menu_link', 'params' => array( 'link_type' => 'messages' ), 'enabled' => 0 ),
		array( 60, 'basic_menu_link', 'params' => array( 'link_type' => 'ownercontact', 'show_badge' => 0 ), 'enabled' => ( $coll_type == 'minisite' ) ),
		array( 70, 'basic_menu_link', 'params' => array( 'link_type' => 'login' ), 'enabled' => 0 ),
		array( 80, 'basic_menu_link', 'coll_type' => 'forum', 'params' => array( 'link_type' => 'register' ) ),
	);

	/* Item List */
	$default_widgets['item_list'] = array(
		array( 10, 'coll_item_list_pages' ),
	);

	/* Item in List */
	$default_widgets['item_in_list'] = array(
		array( 10, 'item_title' ),
		array( 20, 'item_visibility_badge' ),
		array( 30, 'item_info_line' ),
	);

	/* Item Single Header */
	$default_widgets['item_single_header'] = array(
		array(  4, 'item_next_previous', 'coll_type' => '-manual' ),
		array(  5, 'item_title' ),
		array( 10, 'item_info_line', 'coll_type' => 'forum,group', 'params' => array(
				'before_author' => 'started_by',
				'last_touched'  => 1,
				'category'      => 0,
				'display_date'  => 'date_created',
			) ),
		array( 20, 'item_tags', 'coll_type' => 'forum,group' ),
		array( 30, 'item_seen_by', 'coll_type' => 'forum,group' ),
		array(  8, 'item_visibility_badge', 'coll_type' => '-manual,forum,group' ),
		array( 10, 'item_info_line', 'coll_type' => '-manual,forum,group' ),
	);

	/* Item Single */
	$default_widgets['item_single'] = array(
		array(  5, 'item_title', 'coll_type' => 'manual' ),
		array( 10, 'item_content' ),
		array( 15, 'item_attachments' ),
		array( 17, 'item_link' ),
		array( 20, 'item_tags', 'coll_type' => '-forum,group', 'install' => ! $context['init_as_blog_a'] && ! $context['init_as_events'] ),
		array( 25, 'item_about_author', 'install' => $context['init_as_blog_b'] ),
		array( 30, 'evo_Gmaps', 'type' => 'plugin', 'install' => $context['install_test_features'] && ( $context['init_as_blog_a'] || $context['init_as_events'] ) ),
		array( 40, 'item_small_print', 'install' => $context['init_as_blog_a'], 'params' => array( 'format' => 'standard' ) ),
		array( 40, 'item_small_print', 'coll_type' => 'manual', 'params' => array( 'format' => 'revision' ) ),
		array( 50, 'item_seen_by', 'coll_type' => '-forum,group' ),
		array( 60, 'item_vote', 'coll_type' => '-forum' ),
	);

	/* Item Page */
	$default_widgets['item_page'] = array(
		array( 10, 'item_content' ),
		array( 15, 'item_attachments' ),
		array( 50, 'item_seen_by' ),
		array( 60, 'item_vote' ),
	);

	/* Sidebar Single */
	$default_widgets['sidebar_single'] = array(
		array(  1, 'coll_related_post_list', 'coll_type' => 'forum' ),
	);

	/* Page Top */
	$default_widgets['page_top'] = array(
		array( 10, 'social_links', 'params' => array(
				'link1'      => 'twitter',
				'link1_href' => 'https://twitter.com/b2evolution/',
				'link2'      => 'facebook',
				'link2_href' => 'https://www.facebook.com/b2evolution',
				'link3'      => 'googleplus',
				'link3_href' => 'https://plus.google.com/+b2evolution/posts',
				'link4'      => 'linkedin',
				'link4_href' => 'https://www.linkedin.com/company/b2evolution-net',
				'link5'      => 'github',
				'link5_href' => 'https://github.com/b2evolution/b2evolution',
			) ),
	);

	/* Sidebar */
	if( $coll_type == 'manual' )
	{
		$default_widgets['sidebar'] = array(
			'coll_type' => 'manual',
			array( 10, 'coll_search_form', 'params' => array( 'title' => T_('Search this manual:') ) ),
			array( 20, 'content_hierarchy' ),
		);
	}
	else
	{
		// Special checking to don't install several Sidebar widgets below for collection 'Forums':
		$install_not_forum = ( ! $context['init_as_forums'] && $coll_type != 'forum' );
		if( $context['init_as_home'] )
		{	// Advertisements, Install only for collection #1 home collection:
			$advertisement_type_ID = $DB->get_var( 'SELECT ityp_ID FROM T_items__type WHERE ityp_name = "Advertisement"' );
		}
		if( ! empty( $context['coll_home_ID'] ) && ( $context['init_as_blog_a'] || $context['init_as_blog_b'] ) )
		{
			$sidebar_type_ID = $DB->get_var( 'SELECT ityp_ID FROM T_items__type WHERE ityp_name = "Sidebar link"' );
		}
		$default_widgets['sidebar'] = array(
			array(  5, 'coll_current_filters', 'coll_type' => '-forum', 'install' => $context['install_test_features'] ),
			array( 10, 'user_login', 'install' => $context['install_test_features'] ),
			array( 15, 'user_greetings', 'install' => $context['install_test_features'] ),
			array( 20, 'user_profile_pics', 'install' => $install_not_forum ),
			array( 30, 'evo_Calr', 'type' => 'plugin', 'install' => ( $install_not_forum && $context['current_coll_ID'] > $context['coll_blog_a_ID'] ) ),
			array( 40, 'coll_longdesc', 'install' => $install_not_forum, 'params' => array( 'title' => '$title$' ) ),
			array( 50, 'coll_search_form', 'install' => $install_not_forum ),
			array( 60, 'coll_category_list', 'install' => $install_not_forum ),
			array( 70, 'coll_item_list', 'install' => $install_not_forum && $context['init_as_home'], 'params' => array(
					'title' => 'Advertisement (Demo)',
					'item_type' => empty( $advertisement_type_ID ) ? '#' : $advertisement_type_ID,
					'blog_ID' => $context['current_coll_ID'],
					'order_by' => 'RAND',
					'limit' => 1,
					'disp_title' => false,
					'item_title_link_type' => 'linkto_url',
					'attached_pics' => 'first',
					'item_pic_link_type' => 'linkto_url',
					'thumb_size' => 'fit-160x160',
				) ),
			array( 80, 'coll_media_index', 'install' => ( $install_not_forum && ! $context['init_as_blog_b'] ), 'params' => array(
					'title'        => 'Random photo',
					'thumb_size'   => 'fit-160x120',
					'thumb_layout' => 'grid',
					'grid_nb_cols' => 1,
					'limit'        => 1,
					'order_by'     => 'RAND',
					'order_dir'    => 'ASC',
					// In the case of initial install, we grab photos out of the photoblog:
					'blog_ID'      => ( empty( $context['coll_photoblog_ID'] ) ? '' : intval( $context['coll_photoblog_ID'] ) ),
				) ),
			array( 90, 'coll_item_list', 'install' => ( $install_not_forum && ( $context['init_as_blog_a'] || $context['init_as_blog_b'] ) ), 'params' => array(
					'blog_ID'              => $context['coll_home_ID'],
					'item_type'            => empty( $sidebar_type_ID ) ? '#' : $sidebar_type_ID,
					'title'                => 'Linkblog',
					'item_group_by'        => 'chapter',
					'item_title_link_type' => 'auto',
					'item_type_usage'      => 'special',
				) ),
			array( 90, 'user_avatars', 'coll_type' => 'forum', 'params' => array(
					'title'           => 'Most Active Users',
					'limit'           => 6,
					'order_by'        => 'numposts',
					'rwd_block_class' => 'col-lg-3 col-md-3 col-sm-4 col-xs-6'
				) ),
			array( 100, 'coll_xml_feeds' ),
			array( 110, 'mobile_skin_switcher' ),
		);
	}

	/* Sidebar 2 */
	$default_widgets['sidebar_2'] = array(
		'coll_type' => '-forum',
		array(  1, 'coll_post_list' ),
		array(  5, 'coll_item_list', 'install' => $context['init_as_blog_b'], 'params' => array(
				'title'                => 'Sidebar links',
				'order_by'             => 'RAND',
				'item_title_link_type' => 'auto',
				'item_type_usage'      => 'special',
			) ),
		array( 10, 'coll_comment_list' ),
		array( 15, 'coll_media_index', 'params' =>  array(
				'title'        => 'Recent photos',
				'thumb_size'   => 'crop-80x80',
				'thumb_layout' => 'flow',
				'grid_nb_cols' => 3,
				'limit'        => 9,
				'order_by'     => 'datestart',
				'order_dir'    => 'DESC',
				// In the case of initial install, we grab photos out of the photoblog:
				'blog_ID'      => ( empty( $context['coll_photoblog_ID'] ) ? '' : intval( $context['coll_photoblog_ID'] ) ),
			) ),
		array( 20, 'free_html', 'params' => array(
				'title'   => 'Sidebar 2',
				'content' => 'This is the "Sidebar 2" container. You can place any widget you like in here. In the evo toolbar at the top of this page, select "Customize", then "Blog Widgets".',
			) ),
	);

	/* Front Page Main Area */
	$default_widgets['front_page_main_area'] = array(
		array(  1, 'coll_title', 'coll_type' => 'main,minisite' ),
		array(  2, 'coll_tagline', 'coll_type' => 'main,minisite' ),
		array( 10, 'coll_featured_intro', 'coll_type' => '-minisite', 'params' => ( $coll_type == 'main' ? array(
			// Hide a title of the front intro post:
				'disp_title' => 0,
			) : NULL ) ),
		array( 15, 'user_links', 'coll_type' => 'main' ),
		array( 20, 'coll_featured_posts', 'coll_type' => '-minisite', 'params' => ( $coll_type == 'main' ? array(
			// Display the posts from all other blogs if it is allowed by blogs setting "Collections to aggregate":
				'blog_ID'    => '',
				'limit'      => 5,
				'layout'     => 'list',
				'thumb_size' => 'crop-80x80',
			) : NULL ) ),
		// Install widget "Poll" only for Blog B on install:
		array( 40, 'poll', 'install' => $context['init_as_blog_b'], 'params' => array( 'poll_ID' => 1 ) ),
		array( 50, 'subcontainer_row', 'params' => array(
				'column1_container' => 'front_page_column_a',
				'column1_class'     => ( $coll_type == 'main' ? 'col-xs-12' : 'col-sm-6 col-xs-12' ),
				'column2_container' => 'front_page_column_b',
				'column2_class'     => 'col-sm-6 col-xs-12',
			) ),
	);

	/* Front Page Column A */
	$default_widgets['front_page_column_a'] = array(
		'type'  => 'sub',
		'name'  => NT_('Front Page Column A'),
		'order' => 1,
		array( 10, 'coll_post_list', 'coll_type' => '-minisite', 'params' => array( 'title' => T_('More Posts'), 'featured' => 'other' ) ),
	);

	/* Front Page Column B */
	$default_widgets['front_page_column_b'] = array(
		'type'  => 'sub',
		'name'  => NT_('Front Page Column B'),
		'order' => 2,
		array( 10, 'coll_comment_list', 'coll_type' => '-main,minisite' ),
	);

	/* Front Page Secondary Area */
	$default_widgets['front_page_secondary_area'] = array(
		array( 10, 'org_members', 'coll_type' => 'main,minisite' ),
		array( 20, 'coll_flagged_list', 'coll_type' => '-minisite' ),
		array( 30, 'content_block', 'coll_type' => 'main', 'params' => array( 'item_slug' => 'this-is-a-content-block' ) ),
	);

	/* Front Page Area 3 */
	$default_widgets['front_page_area_3'] = array(
		'coll_type' => 'minisite',
		array( 10, 'free_text', 'params' => 'a:6:{s:5:"title";s:0:"";s:7:"content";s:446:"Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";s:9:"renderers";a:10:{s:11:"escape_code";i:1;s:8:"b2evMark";i:1;s:8:"b2evWiLi";i:1;s:8:"b2evCTag";i:1;s:8:"b2evGMco";i:1;s:8:"b2evALnk";i:1;s:8:"evo_poll";i:1;s:13:"evo_videoplug";i:1;s:8:"b2WPAutP";i:1;s:14:"evo_widescroll";i:1;}s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";s:16:"allow_blockcache";i:0;}' ),
		array( 20, 'user_links' ),
	);

	/* Forum Front Secondary Area */
	$default_widgets['forum_front_secondary_area'] = array(
		'coll_type' => 'forum',
		array( 10, 'coll_activity_stats' ),
	);

	/* Compare Main Area */
	$default_widgets['compare_main_area'] = array(
		array( 10, 'item_fields_compare', 'params' => array( 'items_source' => 'all' ) ),
	);

	/* 404 Page */
	$default_widgets['404_page'] = array(
		array( 10, 'page_404_not_found' ),
		array( 20, 'coll_search_form' ),
		array( 30, 'coll_tag_cloud' ),
	);

	/* Login Required */
	$default_widgets['login_required'] = array(
		array( 10, 'content_block', 'params' => array( 'item_slug' => 'login-required' ) ),
		array( 20, 'user_login', 'params' => array(
				'title'               => T_( 'Log in to your account' ),
				'login_button_class'  => 'btn btn-success btn-lg',
				'register_link_class' => 'btn btn-primary btn-lg pull-right',
			) ),
	);

	/* Access Denied */
	$default_widgets['access_denied'] = array(
		array( 10, 'content_block', 'params' => array( 'item_slug' => 'access-denied' ) ),
	);

	/* Help */
	$default_widgets['help'] = array(
		array( 10, 'content_block', 'params' =>  array(
				'item_slug' => 'help-content',
				'title'     => T_('Personal Data & Privacy'),
			) ),
	);

	/* Register */
	$default_widgets['register'] = array(
		array( 10, 'user_register_standard' ),
		array( 20, 'content_block', 'params' => array( 'item_slug' => 'register-content' ) ),
	);


	/* Mobile Footer */
	$default_widgets['mobile_footer'] = array(
		'skin_type' => 'mobile',
		array( 10, 'coll_longdesc' ),
		array( 20, 'mobile_skin_switcher' ),
	);

	/* Mobile Navigation Menu */
	$default_widgets['mobile_navigation_menu'] = array(
		'skin_type' => 'mobile',
		array( 10, 'coll_page_list' ),
		array( 20, 'basic_menu_link', 'params' => array( 'link_type' => 'ownercontact' ) ),
		array( 30, 'basic_menu_link', 'params' => array( 'link_type' => 'home' ) ),
		array( 30, 'basic_menu_link', 'coll_type' => 'forum', 'params' => array( 'link_type' => 'users' ) ),
	);

	/* Mobile Tools Menu */
	$default_widgets['mobile_tools_menu'] = array(
		'skin_type' => 'mobile',
		array( 10, 'basic_menu_link', 'params' => array( 'link_type' => 'login' ) ),
		array( 20, 'msg_menu_link', 'params' => array( 'link_type' => 'messages' ) ),
		array( 30, 'msg_menu_link', 'params' => array( 'link_type' => 'contacts', 'show_badge' => 0 ) ),
		array( 50, 'basic_menu_link', 'params' => array( 'link_type' => 'logout' ) ),
	);

	/* User Profile - Left */
	$default_widgets['user_profile_left'] = array(
		// User Profile Picture(s):
		array( 10, 'user_profile_pics', 'params' => array(
				'link_to'           => 'fullsize',
				'thumb_size'        => 'crop-top-320x320',
				'anon_thumb_size'   => 'crop-top-320x320-blur-8',
				'anon_overlay_show' => '1',
				'widget_css_class'  => 'evo_user_profile_pics_main',
			) ),
		// User info / Name:
		array( 20, 'user_info', 'params' => array(
				'info'             => 'name',
				'widget_css_class' => 'evo_user_info_name',
			) ),
		// User info / Nickname:
		array( 30, 'user_info', 'params' => array(
				'info'             => 'nickname',
				'widget_css_class' => 'evo_user_info_nickname',
			) ),
		// User info / Login:
		array( 40, 'user_info', 'params' => array(
				'info'             => 'login',
				'widget_css_class' => 'evo_user_info_login',
			) ),
		// Separator:
		array( 60, 'separator' ),
		// User info / :
		array( 70, 'user_info', 'params' => array(
				'info'             => 'gender_age',
				'widget_css_class' => 'evo_user_info_gender',
			) ),
		// User info / Location:
		array( 80, 'user_info', 'params' => array(
				'info'             => 'location',
				'widget_css_class' => 'evo_user_info_location',
			) ),
		// Separator:
		array( 90, 'separator' ),
		// User action / Edit my profile:
		array( 100, 'user_action', 'params' => array(
				'button'           => 'edit_profile',
			) ),
		// User action / Send Message:
		array( 110, 'user_action', 'params' => array(
				'button'           => 'send_message',
			) ),
		// User action / Add to Contacts:
		array( 120, 'user_action', 'params' => array(
				'button'           => 'add_contact',
			) ),
		// User action / Block Contact & Report User:
		array( 130, 'user_action', 'params' => array(
				'button'           => 'block_report',
				'widget_css_class' => 'btn-group',
			) ),
		// User action / Edit in Back-Office:
		array( 140, 'user_action', 'params' => array(
				'button'           => 'edit_backoffice',
			) ),
		// User action / Delete & Delete Spammer:
		array( 150, 'user_action', 'params' => array(
				'button'           => 'delete',
				'widget_css_class' => 'btn-group',
			) ),
		// Separator:
		array( 160, 'separator' ),
		// User info / Organizations:
		array( 170, 'user_info', 'params' => array(
				'info'             => 'orgs',
				'title'            => T_('Organizations').':',
				'widget_css_class' => 'evo_user_info_orgs',
			) ),
	);

	/* User Profile - Right */
	$default_widgets['user_profile_right'] = array(
		// User Profile Picture(s):
		array( 10, 'user_profile_pics', 'params' => array(
				'display_main'     => 0,
				'display_other'    => 1,
				'link_to'          => 'fullsize',
				'thumb_size'       => 'crop-top-80x80',
				'widget_css_class' => 'evo_user_profile_pics_other',
			) ),
		// User fields:
		array( 20, 'user_fields' ),
		// Reputation:
		array( 30, 'subcontainer', 'params' => array(
				'title'     => T_('Reputation'),
				'container' => 'user_page_reputation',
			) ),
	);

	/* User Page - Reputation */
	$default_widgets['user_page_reputation'] = array(
		'type'  => 'sub',
		'name'  => NT_('User Page - Reputation'),
		'order' => 100,
		// User info / Joined:
		array( 10, 'user_info', 'params' => array(
				'title' => T_('Joined'),
				'info'  => 'joined',
			) ),
		// User info / Last Visit:
		array( 20, 'user_info', 'params' => array(
				'title' => T_('Last seen on'),
				'info'  => 'last_visit',
			) ),
		// User info / Number of posts:
		array( 30, 'user_info', 'params' => array(
				'title' => T_('Number of posts'),
				'info'  => 'posts',
			) ),
		// User info / Comments:
		array( 40, 'user_info', 'params' => array(
				'title' => T_('Comments'),
				'info'  => 'comments',
			) ),
		// User info / Photos:
		array( 50, 'user_info', 'params' => array(
				'title' => T_('Photos'),
				'info'  => 'photos',
			) ),
		// User info / Audio:
		array( 60, 'user_info', 'params' => array(
				'title' => T_('Audio'),
				'info'  => 'audio',
			) ),
		// User info / Other files:
		array( 70, 'user_info', 'params' => array(
				'title' => T_('Other files'),
				'info'  => 'files',
			) ),
		// User info / Spam fighter score:
		array( 80, 'user_info', 'params' => array(
				'title' => T_('Spam fighter score'),
				'info'  => 'spam',
			) ),
	);

	return $default_widgets;
}


/**
 * Get config array of default widgets on one container
 *
 * @param string Container code
 * @param string Collection kind
 * @param array Context
 * @return array|boolean FALSE if no widgets for a requested container
 */
function get_default_widgets_by_container( $container_code, $coll_type = '', $context = array() )
{
	$default_widgets = get_default_widgets( $coll_type, $context );

	return isset( $default_widgets[ $container_code ] ) ? $default_widgets[ $container_code ] : false;
}


/**
 * Get WidgetContainer object from the widget list view widget container fieldset id
 * Note: It is used during creating and reordering widgets
 *
 * @return WidgetContainer
 */
function & get_widget_container( $coll_ID, $container_fieldset_id )
{
	$WidgetContainerCache = & get_WidgetContainerCache();

	if( substr( $container_fieldset_id, 0, 10 ) == 'wico_code_' )
	{ // The widget contianer fieldset id was given by the container code because probably it was not created in the database yet
		$container_code = substr( $container_fieldset_id, 10 );
		$WidgetContainer = $WidgetContainerCache->get_by_coll_and_code( $coll_ID, $container_code );
		if( ! $WidgetContainer )
		{ // The skin container didn't contain any widget before, and it was not saved in the database
			$WidgetContainer = new WidgetContainer();
			$WidgetContainer->set( 'code', $container_code );
			$WidgetContainer->set( 'name', $container_code );
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


/**
 * @param string Title of the container. This gets passed to T_()!
 * @param boolean Is included in collection skin
 * @param array Params
 */
function display_container( $WidgetContainer, $is_included = true, $params = array() )
{
	global $Collection, $Blog, $admin_url, $embedded_containers, $mode;
	global $Session;

	$params = array_merge( array(
			'table_layout'  => NULL, // Possible values: 'accordion_table', NULL(for default 'Results')
			'group_id'      => NULL,
			'group_item_id' => NULL,
		), $params );

	$Table = new Table( $params['table_layout'] );

	// Table ID - fp> needs to be handled cleanly by Table object
	if( isset( $WidgetContainer->ID ) && ( $WidgetContainer->ID > 0 ) )
	{
		$widget_container_id = 'wico_ID_'.$WidgetContainer->ID;
		$add_widget_url = regenerate_url( '', 'action=new&amp;wico_ID='.$WidgetContainer->ID.'&amp;container='.$widget_container_id );
		$destroy_container_url = url_add_param( $admin_url, 'ctrl=widgets&amp;action=destroy_container&amp;wico_ID='.$WidgetContainer->ID.'&amp;'.url_crumb('widget_container') );
	}
	else
	{
		$wico_code = $WidgetContainer->get( 'code' );
		$widget_container_id = 'wico_code_'.$wico_code;
		$add_widget_url = regenerate_url( '', 'action=new&amp;wico_code='.$wico_code.'&amp;container='.$widget_container_id );
		$destroy_container_url = url_add_param( $admin_url, 'ctrl=widgets&amp;action=destroy_container&amp;wico_code='.$wico_code.'&amp;'.url_crumb('widget_container') );
	}

	if( $mode == 'customizer' )
	{
		$destroy_container_url .= '&amp;mode='.$mode;
	}

	if( ! $is_included )
	{	// Allow to destroy sub-container when it is not included into the selected skin:
		$destroy_btn_title = ( $WidgetContainer->main ? T_('Destroy container') : T_('Destroy sub-container') );
		$Table->global_icon( $destroy_btn_title, 'delete', $destroy_container_url, $destroy_btn_title, $mode == 'customizer' ? 0 : 3, $mode == 'customizer' ? 0 : 4, array( 'onclick' => 'return confirm( \''.TS_('Are you sure you want to destroy this container?').'\' )') );
	}

	$widget_container_name = T_( $WidgetContainer->get( 'name' ) );
	if( $mode == 'customizer' )
	{	// Customizer mode:
		$Table->title = '<span class="container_name" data-wico_id="'.$widget_container_id.'">'.$widget_container_name.'</span>';
		if( ! empty( $WidgetContainer->ID ) )
		{	// Link to edit current widget container:
			$Table->global_icon( T_('Edit widget container'), 'edit', $admin_url.'?ctrl=widgets&amp;blog='.$Blog->ID.'&amp;action=edit_container&amp;wico_ID='.$WidgetContainer->ID.'&amp;mode='.$mode, T_('Edit widget container'), 0, 0 );
		}
	}
	else
	{	// Normal/back-office mode:
		if( ! empty( $WidgetContainer->ID ) )
		{
			$widget_container_name = '<a href="'.$admin_url.'?ctrl=widgets&amp;blog='.$Blog->ID.'&amp;action=edit_container&amp;wico_ID='.$WidgetContainer->ID.( $mode == 'customizer' ? '&amp;mode='.$mode : '' ).'">'.$widget_container_name.'</a>';
		}
		$Table->title = '<span class="dimmed">'.$WidgetContainer->get( 'order' ).'</span> '
			.'<span class="container_name" data-wico_id="'.$widget_container_id.'">'.$widget_container_name.'</span> '
			.'<span class="dimmed">'.$WidgetContainer->get( 'code' ).'</span>';

		$add_widget_link_params = array( 'class' => 'action_icon btn-primary' );
		if( $mode == 'customizer' )
		{	// Set special url to add new widget on customizer mode:
			$add_widget_url = $admin_url.'?ctrl=widgets&blog='.$Blog->ID.'&skin_type='.$Blog->get_skin_type().'&action=add_list&container='.urlencode( $WidgetContainer->get( 'name' ) ).'&container_code='.urlencode( $WidgetContainer->get( 'code' ) ).'&mode=customizer';
		}
		else
		{	// Add id for link to initialize JS code of opening modal window only for not customizer mode,
			// because in customizer mode we should open this as simple link in the same left customizer panel:
			$add_widget_link_params['id'] = 'add_new_'.$widget_container_id;
		}
		$Table->global_icon( T_('Add a widget...'), 'new', $add_widget_url, /* TRANS: ling used to add a new widget */ T_('Add widget').' &raquo;', 3, 4, $add_widget_link_params );
	}

	if( $params['table_layout'] == 'accordion_table' )
	{	// Set ID for current widget container for proper work of accordion style:
		$params['group_item_id'] = 'container_'.$widget_container_id;
	}

	$Table->display_init( array_merge( array(
			'list_start' => '<div class="panel panel-default">',
			'list_end'   => '</div>',
		), $params ) );

	$Table->display_list_start();

	// TITLE / COLUMN HEADERS:
	$Table->display_head();

	if( $params['table_layout'] == 'accordion_table' )
	{	// Start of accordion body of current item:
		$is_selected_widget_container = empty( $params['selected_wico_ID'] ) || empty( $WidgetContainer ) || $WidgetContainer->ID != $params['selected_wico_ID'];
		echo '<div id="'.$params['group_item_id'].'" class="panel-collapse '.( $is_selected_widget_container ? 'collapse' : '' ).'">';
	}

	// BODY START:
	echo '<ul id="container_'.$widget_container_id.'" class="widget_container">';

	/**
	 * @var WidgetCache
	 */
	$WidgetCache = & get_WidgetCache();
	$Widget_array = & $WidgetCache->get_by_container_ID( $WidgetContainer->ID );

	if( ! empty( $Widget_array ) )
	{
		$widget_count = 0;
		foreach( $Widget_array as $ComponentWidget )
		{
			$widget_count++;
			$enabled = $ComponentWidget->get( 'enabled' );
			$disabled_plugin = ( $ComponentWidget->type == 'plugin' && $ComponentWidget->get_Plugin() == false );

			if( $ComponentWidget->get( 'code' ) == 'subcontainer' )
			{
				$container_code = $ComponentWidget->get_param( 'container' );
				if( ! isset( $embedded_containers[$container_code] ) ) {
					$embedded_containers[$container_code] = true;
				}
			}

			// START Widget row:
			echo '<li id="wi_ID_'.$ComponentWidget->ID.'" class="draggable_widget">';

			// Checkbox:
			if( $mode != 'customizer' )
			{	// Don't display on customizer mode:
				echo '<span class="widget_checkbox'.( $enabled ? ' widget_checkbox_enabled' : '' ).'">'
						.'<input type="checkbox" name="widgets[]" value="'.$ComponentWidget->ID.'" />'
					.'</span>';
			}

			// State:
			echo '<span class="widget_state">';
			if( $disabled_plugin )
			{	// If widget's plugin is disabled:
				echo get_icon( 'warning', 'imgtag', array( 'title' => T_('Inactive / Uninstalled plugin') ) );
			}
			else
			{	// If this is a normal widget or widget's plugin is enabled:
				echo '<a href="#" onclick="return toggleWidget( \'wi_ID_'.$ComponentWidget->ID.'\' );">'
						.get_icon( ( $enabled ? 'bullet_green' : 'bullet_empty_grey' ), 'imgtag', array( 'title' => ( $enabled ? T_('The widget is enabled.') : T_('The widget is disabled.') ) ) )
					.'</a>';
			}
			echo '</span>';

			// Name:
			$ComponentWidget->init_display( array() );
			echo '<span class="widget_title">'
					.'<a href="'.regenerate_url( 'blog', 'action=edit&amp;wi_ID='.$ComponentWidget->ID.( $mode == 'customizer' ? '&amp;mode=customizer' : '' ) ).'" class="widget_name"'
						.( $mode == 'customizer' ? '' : ' onclick="return editWidget( \'wi_ID_'.$ComponentWidget->ID.'\' )"' )
						.'>'
						.$ComponentWidget->get_desc_for_list()
					.'</a> '
					.$ComponentWidget->get_help_link()
				.'</span>';

			// Cache:
			if( $mode != 'customizer' )
			{	// Don't display on customizer mode:
				echo'<span class="widget_cache_status">';
				$widget_cache_status = $ComponentWidget->get_cache_status( true );
				switch( $widget_cache_status )
				{
					case 'disallowed':
						echo get_icon( 'block_cache_disabled', 'imgtag', array( 'title' => T_( 'This widget cannot be cached.' ), 'rel' => $widget_cache_status ) );
						break;

					case 'denied':
						echo action_icon( T_( 'This widget could be cached but the block cache is OFF. Click to enable.' ),
							'block_cache_denied',
							$admin_url.'?ctrl=coll_settings&amp;tab=advanced&amp;blog='.$Blog->ID.'#fieldset_wrapper_caching', NULL, NULL, NULL,
							array( 'rel' => $widget_cache_status ) );
						break;

					case 'enabled':
						echo action_icon( T_( 'Caching is enabled. Click to disable.' ),
							'block_cache_on',
							regenerate_url( 'blog', 'action=cache_disable&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb( 'widget' ) ), NULL, NULL, NULL,
							array(
									'rel'     => $widget_cache_status,
									'onclick' => 'return toggleCacheWidget( \'wi_ID_'.$ComponentWidget->ID.'\', \'disable\' )',
								) );
						break;

					case 'disabled':
						echo action_icon( T_( 'Caching is disabled. Click to enable.' ),
							'block_cache_off',
							regenerate_url( 'blog', 'action=cache_enable&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb( 'widget' ) ), NULL, NULL, NULL,
							array(
									'rel'     => $widget_cache_status,
									'onclick' => 'return toggleCacheWidget( \'wi_ID_'.$ComponentWidget->ID.'\', \'enable\' )',
								) );
						break;
				}
				echo '</span>';
			}

			// Actions:
			echo '<span class="widget_actions">';
			if( $disabled_plugin )
			{	// If widget's plugin is disabled:
				// Display a space same as the enable/disable icons:
				echo action_icon( '', 'deactivate', '#', NULL, NULL, NULL, array( 'style' => 'visibility:hidden', 'class' => 'toggle_action' ) );
			}
			else
			{	// If this is a normal widget or widget's plugin is enabled:
					// Enable/Disable:
					echo action_icon( ( $enabled ? T_('Disable this widget!') : T_('Enable this widget!') ),
							( $enabled ? 'deactivate' : 'activate' ),
							regenerate_url( 'blog', 'action=toggle&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb('widget') ), NULL, NULL, NULL,
							array( 'onclick' => 'return toggleWidget( \'wi_ID_'.$ComponentWidget->ID.'\' )', 'class' => 'toggle_action' )
						);
			}
					// Edit:
					if( $mode != 'customizer' )
					{	// Don't display on customizer mode:
						echo action_icon( T_('Edit widget settings!'),
							'edit',
							regenerate_url( 'blog', 'action=edit&amp;wi_ID='.$ComponentWidget->ID ), NULL, NULL, NULL,
							array( 'onclick' => 'return editWidget( \'wi_ID_'.$ComponentWidget->ID.'\' )', 'class' => '' )
						);
					}
					// Remove:
					echo action_icon( T_('Remove this widget!'),
							'delete',
							regenerate_url( 'blog', 'action=delete&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb( 'widget' ) ), NULL, NULL, NULL,
							array( 'onclick' => 'return deleteWidget( \'wi_ID_'.$ComponentWidget->ID.'\' )', 'class' => '' )
						)
				.'</span>';

			// END Widget row:
			echo '</li>';
		}
	}

	// BODY END:
	echo '</ul>';

	if( $params['table_layout'] == 'accordion_table' )
	{	// End of accordion body of current item:
		echo '</div>';
	}

	$Table->display_list_end();
}


/**
 * Display containers
 *
 * @param string Skin type: 'normal', 'mobile', 'tablet'
 * @param boolean TRUE to display main containers, FALSE - sub containers
 * @param array Params
 */
function display_containers( $skin_type, $main = true, $params = array() )
{
	global $Blog, $blog_container_list, $skins_container_list, $embedded_containers;

	// Display containers for current skin:
	$displayed_containers = array();
	$ordered_containers = array();
	$embedded_containers = array();
	$WidgetContainerCache = & get_WidgetContainerCache();
	foreach( $skins_container_list as $container_code => $container_data )
	{
		$WidgetContainer = & $WidgetContainerCache->get_by_coll_and_code( $Blog->ID, $container_code );
		if( ! $WidgetContainer )
		{
			$WidgetContainer = new WidgetContainer();
			$WidgetContainer->set( 'code', $container_code );
			$WidgetContainer->set( 'name', $container_data[0] );
			$WidgetContainer->set( 'coll_ID', $Blog->ID );
			$WidgetContainer->set( 'order', 0 );
		}
		if( $WidgetContainer->get( 'skin_type' ) != $skin_type ||
		    ( $main && ! $WidgetContainer->get( 'main' ) ) ||
		    ( ! $main && $WidgetContainer->get( 'main' ) ) )
		{	// Skip this container because another type is requested:
			continue;
		}

		$ordered_containers[] = array( $WidgetContainer, true );
		if( $WidgetContainer->ID > 0 )
		{ // Container exists in the database
			$displayed_containers[$container_code] = $WidgetContainer->ID;
		}
	}

	// Display embedded containers
	reset( $embedded_containers );
	while( count( $embedded_containers ) > 0 )
	{
		// Get the first item key, and remove the first item from the array
		$container_code = key( $embedded_containers );
		array_shift( $embedded_containers );
		if( isset( $displayed_containers[$container_code] ) )
		{ // This container was already displayed
			continue;
		}

		if( $WidgetContainer = & $WidgetContainerCache->get_by_coll_and_code( $Blog->ID, $container_code ) )
		{ // Confirmed that it is part of the blog's containers in the database
			if( ( $main && ! $WidgetContainer->get( 'main' ) ) ||
			    ( ! $main && $WidgetContainer->get( 'main' ) ) )
			{	// Skip this container because another type is requested:
				continue;
			}
			$ordered_containers[] = array( $WidgetContainer, true );
			$displayed_containers[$container_code] = $WidgetContainer->ID;
		}
	}

	// Display other blog containers which are not in the current skin
	foreach( $blog_container_list as $container_ID )
	{
		if( in_array( $container_ID, $displayed_containers ) )
		{
			continue;
		}

		$WidgetContainer = & $WidgetContainerCache->get_by_ID( $container_ID );
		if( ( $main && ! $WidgetContainer->get( 'main' ) ) ||
		    ( ! $main && $WidgetContainer->get( 'main' ) ) )
		{	// Skip this container because another type is requested:
			continue;
		}
		$ordered_containers[] = array( $WidgetContainer, false );
	}

	// Sort widget containers by order and name:
	usort( $ordered_containers, 'callback_sort_widget_containers' );

	// Display the ordered containers:
	foreach( $ordered_containers as $container_data )
	{
		$WidgetContainer = & $container_data[0];
		// Is included in collection skin?
		$is_included = $container_data[1];
		// Display a container with widgets:
		display_container( $WidgetContainer, $is_included, $params  );
	}
}


/**
 * Callback function to sort widget containers array by fields order and name
 *
 * @param array Widget data
 * @param array Widget data
 */
function callback_sort_widget_containers( $a, $b )
{
	if( $a[0]->get( 'order' ) == $b[0]->get( 'order' ) )
	{	// Sort by name if orders are equal:
		return strnatcmp( $a[0]->get( 'name' ), $b[0]->get( 'name' ) );
	}
	else
	{	// Sort by order if they are different:
		return $a[0]->get( 'order' ) > $b[0]->get( 'order' );
	}
}


/**
 * Display action buttons to work with sereral widgets in list
 *
 * @param object Form
 */
function display_widgets_action_buttons( & $Form )
{
	echo '<span class="btn-group">';
	$Form->button( array(
			'value' => get_icon( 'check_all' ).' '.T_('Check all'),
			'id'    => 'widget_button_check_all',
			'tag'   => 'button',
			'type'  => 'button'
		) );
	$Form->button( array(
			'value' => get_icon( 'uncheck_all' ).' '.T_('Uncheck all'),
			'id'    => 'widget_button_uncheck_all',
			'tag'   => 'button',
			'type'  => 'button'
		) );
	echo '</span>';

	echo '<span class="btn-group">';
	$Form->button( array(
			'value' => get_icon( 'check_all' ).' '.get_icon( 'bullet_green' ).' '.T_('Check Active'),
			'id'    => 'widget_button_check_active',
			'tag'   => 'button',
			'type'  => 'button'
		) );
	$Form->button( array(
			'value' => get_icon( 'check_all' ).' '.get_icon( 'bullet_empty_grey' ).' '.T_('Check Inactive'),
			'id'    => 'widget_button_check_inactive',
			'tag'   => 'button',
			'type'  => 'button'
		) );
	echo '</span>';

	echo ' '.T_('With checked do:');
	echo '<span class="btn-group">';
	$Form->button( array(
			'value' => get_icon( 'bullet_green' ).' '.T_('Activate'),
			'name'  => 'actionArray[activate]',
			'tag'   => 'button',
			'type'  => 'submit'
		) );
	$Form->button( array(
			'value' => get_icon( 'bullet_empty_grey' ).' '.T_('Deactivate'),
			'name'  => 'actionArray[deactivate]',
			'tag'   => 'button',
			'type'  => 'submit'
		) );
	echo '</span>';
}
?>