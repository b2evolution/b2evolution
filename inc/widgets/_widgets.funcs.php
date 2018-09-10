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
 * @param string Collection kind
 * @param integer Collection ID
 * @param boolean Should be true only when it's called after initial install
 * @return array
 */
function get_default_widgets( $kind = '', $blog_id = NULL, $initial_install = false )
{
	global $DB, $install_test_features;
	// Handle all blog IDs which can go from function create_demo_contents()
	global $blog_home_ID, $blog_a_ID, $blog_b_ID, $blog_photoblog_ID, $blog_forums_ID, $blog_manual_ID, $events_blog_ID;
	$blog_home_ID = intval( $blog_home_ID );
	$blog_a_ID = intval( $blog_a_ID );
	$blog_b_ID = intval( $blog_b_ID );
	$blog_photoblog_ID = intval( $blog_photoblog_ID );
	$blog_forums_ID = intval( $blog_forums_ID );
	$blog_manual_ID = intval( $blog_manual_ID );
	$events_blog_ID = intval( $events_blog_ID );

	// Init insert widget query and default params
	$default_blog_param = 's:7:"blog_ID";s:0:"";';
	if( $initial_install && ! empty( $blog_photoblog_ID ) )
	{ // In the case of initial install, we grab photos out of the photoblog (Blog #4)
		$default_blog_param = 's:7:"blog_ID";s:1:"'.intval( $blog_photoblog_ID ).'";';
	}

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
		array( 10, 'basic_menu_link', 'coll_ID' => $blog_b_ID, 'params' => array( 'link_type' => 'recentposts', 'link_text' => T_('News') ) ),
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
		array( 60, 'msg_menu_link', 'params' => array( 'link_type' => 'contacts', 'show_badge' => 0 ), 'enabled' => ( $kind == 'minisite' ) ),
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
		array( 10, 'item_info_line', 'coll_type' => 'forum,group', 'params' => 'a:14:{s:5:"title";s:0:"";s:9:"flag_icon";i:1;s:14:"permalink_icon";i:0;s:13:"before_author";s:10:"started_by";s:11:"date_format";s:8:"extended";s:9:"post_time";i:1;s:12:"last_touched";i:1;s:8:"category";i:0;s:9:"edit_link";i:0;s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";s:16:"allow_blockcache";i:0;s:11:"time_format";s:4:"none";s:12:"display_date";s:12:"date_created";}' ),
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
		array( 20, 'item_tags', 'coll_type' => '-forum,group', 'coll_ID' => '-'.$blog_a_ID.','.$events_blog_ID ),
		array( 25, 'item_about_author', 'coll_ID' => $blog_b_ID ),
		array( 30, 'evo_Gmaps', 'coll_ID' => $blog_a_ID.','.$events_blog_ID, 'type' => 'plugin', 'install' => $install_test_features ),
		array( 40, 'item_small_print', 'coll_ID' => $blog_a_ID, 'params' => array( 'format' => 'standard' ) ),
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
		array( 10, 'user_links' ),
	);

	/* Sidebar */
	if( $kind == 'manual' )
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
		$install_not_forum = ( ( ! $initial_install || $blog_id != $blog_forums_ID ) && $kind != 'forum' );
		if( $blog_id == $blog_home_ID )
		{	// Advertisements, Install only for collection #1 home collection:
			$advertisement_type_ID = $DB->get_var( 'SELECT ityp_ID FROM T_items__type WHERE ityp_name = "Advertisement"' );
		}
		if( ! empty( $blog_home_ID ) && ( $blog_id == $blog_a_ID || $blog_id == $blog_b_ID ) )
		{
			$sidebar_type_ID = $DB->get_var( 'SELECT ityp_ID FROM T_items__type WHERE ityp_name = "Sidebar link"' );
		}
		$default_widgets['sidebar'] = array(
			array(  5, 'coll_current_filters', 'coll_type' => '-forum', 'install' => $install_test_features ),
			array( 10, 'user_login', 'install' => $install_test_features ),
			array( 15, 'user_greetings', 'install' => $install_test_features ),
			array( 20, 'user_profile_pics', 'install' => $install_not_forum ),
			array( 30, 'evo_Calr', 'type' => 'plugin', 'install' => ( $install_not_forum && $blog_id > $blog_a_ID ) ),
			array( 40, 'coll_longdesc', 'install' => $install_not_forum, 'params' => array( 'title' => '$title$' ) ),
			array( 50, 'coll_search_form', 'install' => $install_not_forum ),
			array( 60, 'coll_category_list', 'install' => $install_not_forum ),
			array( 70, 'coll_item_list', 'coll_ID' => $blog_home_ID, 'install' => $install_not_forum, 'params' => array(
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
				) ),
			array( 80, 'coll_media_index', 'coll_ID' => '-'.$blog_b_ID, 'install' => $install_not_forum, 'params' => 'a:11:{s:5:"title";s:12:"Random photo";s:10:"thumb_size";s:11:"fit-160x120";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"1";s:5:"limit";s:1:"1";s:8:"order_by";s:4:"RAND";s:9:"order_dir";s:3:"ASC";'.$default_blog_param.'s:11:"widget_name";s:12:"Random photo";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}' ),
			array( 90, 'coll_item_list', 'coll_ID' => $blog_a_ID.','.$blog_b_ID, 'install' => $install_not_forum, 'params' => array(
					'blog_ID'              => $blog_home_ID,
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
		array(  5, 'coll_item_list', 'coll_ID' => $blog_b_ID, 'params' => array(
				'title'                => 'Sidebar links',
				'order_by'             => 'RAND',
				'item_title_link_type' => 'auto',
				'item_type_usage'      => 'special',
			) ),
		array( 10, 'coll_comment_list' ),
		array( 15, 'coll_media_index', 'params' => 'a:11:{s:5:"title";s:13:"Recent photos";s:10:"thumb_size";s:10:"crop-80x80";s:12:"thumb_layout";s:4:"flow";s:12:"grid_nb_cols";s:1:"3";s:5:"limit";s:1:"9";s:8:"order_by";s:9:"datestart";s:9:"order_dir";s:4:"DESC";'.$default_blog_param.'s:11:"widget_name";s:11:"Photo index";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}' ),
		array( 20, 'free_html', 'params' => 'a:5:{s:5:"title";s:9:"Sidebar 2";s:7:"content";s:162:"This is the "Sidebar 2" container. You can place any widget you like in here. In the evo toolbar at the top of this page, select "Customize", then "Blog Widgets".";s:11:"widget_name";s:9:"Free HTML";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}' ),
	);

	/* Front Page Main Area */
	$default_widgets['front_page_main_area'] = array(
		array(  1, 'coll_title', 'coll_type' => 'main,minisite' ),
		array(  2, 'coll_tagline', 'coll_type' => 'main,minisite' ),
		array( 10, 'coll_featured_intro', 'coll_type' => '-minisite', 'params' => ( $kind == 'main' ? array(
			// Hide a title of the front intro post:
				'disp_title' => 0,
			) : NULL ) ),
		array( 15, 'user_links', 'coll_type' => 'main' ),
		array( 20, 'coll_featured_posts', 'params' => ( $kind == 'main' ? array(
			// Display the posts from all other blogs if it is allowed by blogs setting "Collections to aggregate":
				'blog_ID'    => '',
				'limit'      => 5,
				'layout'     => 'list',
				'thumb_size' => 'crop-80x80',
			) : NULL ) ),
		// Install widget "Poll" only for Blog B on install:
		array( 40, 'poll', 'coll_ID' => $blog_b_ID, 'params' => array( 'poll_ID' => 1 ) ),
		array( 50, 'subcontainer_row', 'params' => array(
				'column1_container' => 'front_page_column_a',
				'column1_class'     => ( $kind == 'main' ? 'col-xs-12' : 'col-sm-6 col-xs-12' ),
				'column2_container' => 'front_page_column_b',
				'column2_class'     => 'col-sm-6 col-xs-12',
			) ),
	);

	/* Front Page Column A */
	$default_widgets['front_page_column_a'] = array(
		'type'  => 'sub',
		'name'  => NT_('Front Page Column A'),
		'order' => 1,
		array( 10, 'coll_post_list', 'params' => array( 'title' => T_('More Posts'), 'featured' => 'other' ) ),
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
		array( 20, 'coll_flagged_list' ),
		array( 30, 'content_block', 'coll_type' => 'main', 'params' => array( 'item_slug' => 'this-is-a-content-block' ) ),
	);

	/* Front Page Area 3 */
	$default_widgets['front_page_area_3'] = array(
		'coll_type' => 'minisite',
		array( 10, 'coll_search_form' ),
		array( 20, 'coll_tag_cloud' ),
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
		array( 10, 'coll_longdesc' ),
		array( 20, 'mobile_skin_switcher' ),
	);

	/* Mobile Navigation Menu */
	$default_widgets['mobile_navigation_menu'] = array(
		array( 10, 'coll_page_list' ),
		array( 20, 'basic_menu_link', 'params' => array( 'link_type' => 'ownercontact' ) ),
		array( 30, 'basic_menu_link', 'params' => array( 'link_type' => 'home' ) ),
		array( 30, 'basic_menu_link', 'coll_type' => 'forum', 'params' => array( 'link_type' => 'users' ) ),
	);

	/* Mobile Tools Menu */
	$default_widgets['mobile_tools_menu'] = array(
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
 * @param integer Collection ID
 * @param boolean Should be true only when it's called after initial install
 * @return array|boolean FALSE if no widgets for a requested container
 */
function get_default_widgets_by_container( $container_code, $kind = '', $blog_id = NULL, $initial_install = false )
{
	$default_widgets = get_default_widgets( $kind, $blog_id, $initial_install );

	return isset( $default_widgets[ $container_code ] ) ? $default_widgets[ $container_code ] : false;
}


/**
 * Insert the basic widgets for a collection
 *
 * @param integer should never be 0
 * @param array the list of skin ids which are set for the given blog ( normal, mobile and tablet skin ids )
 * @param boolean should be true only when it's called after initial install
 * fp> TODO: $initial_install is used to know if we want to trust globals like $blog_photoblog_ID and $blog_forums_ID. We don't want that.
 *           We should pass a $context array with values like 'photo_source_coll_ID' => 4.
 *           Also, checking $blog_forums_ID is unnecessary complexity. We can check the colleciton kind == forum
 * @param string Kind of blog ( 'std', 'photo', 'group', 'forum' )
 */
function insert_basic_widgets( $blog_id, $skin_ids, $initial_install = false, $kind = '' )
{
	global $DB, $install_test_features;

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

	// Get config of default widgets:
	$default_widgets = get_default_widgets( $kind, $blog_id, $initial_install );

	// Get all containers declared in the given blog's skins
	$blog_containers = get_skin_containers( $skin_ids );

	// Install additional sub containers from default config:
	foreach( $default_widgets as $wico_code => $container_widgets )
	{
		if( isset( $container_widgets['type'] ) &&
		    $container_widgets['type'] == 'sub' )
		{	// If it is a sub-container:
			$blog_containers[ $wico_code ] = array(
					isset( $container_widgets['name'] ) ? $container_widgets['name'] : $wico_code,
					isset( $container_widgets['order'] ) ? $container_widgets['order'] : 1,
					0, // wico_main = 0
				);
		}
	}

	// Create rows to insert for all collection containers:
	$widget_containers_sql_rows = array();
	foreach( $blog_containers as $wico_code => $wico_data )
	{
		$widget_containers_sql_rows[] = '( "'.$wico_code.'", "'.$wico_data[0].'", '.$blog_id.', '.$wico_data[1].', '.( isset( $wico_data[2] ) ? intval( $wico_data[2] ) : '1' ).' )';
	}

	// Insert widget containers records by one SQL query
	$DB->query( 'INSERT INTO T_widget__container( wico_code, wico_name, wico_coll_ID, wico_order, wico_main ) VALUES'
		.implode( ', ', $widget_containers_sql_rows ) );

	$insert_id = $DB->insert_id;
	foreach( $blog_containers as $wico_code => $wico_data )
	{
		$blog_containers[ $wico_code ]['wico_ID'] = $insert_id;
		$insert_id++;
	}

	$basic_widgets_insert_sql_rows = array();
	foreach( $default_widgets as $wico_code => $container_widgets )
	{
		if( ! isset( $blog_containers[ $wico_code ] ) )
		{	// Skip container which is not supported by current colelction's skin:
			continue;
		}

		if( isset( $container_widgets['coll_type'] ) )
		{	// Handle special condition key:
			if( ! is_allowed_option( $kind, $container_widgets['coll_type'] ) )
			{	// Skip container because it should not be installed for the given collection kind:
				continue;
			}
		}

		$wico_id = $blog_containers[ $wico_code ]['wico_ID'];

		// Remove the config data which is used as additional info for container:
		if( isset( $container_widgets['type'] ) )
		{	// Container type
			unset( $container_widgets['type'] );
		}
		if( isset( $container_widgets['name'] ) )
		{	// Container name
			unset( $container_widgets['name'] );
		}
		if( isset( $container_widgets['order'] ) )
		{	// Container order
			unset( $container_widgets['order'] );
		}
		if( isset( $container_widgets['coll_type'] ) )
		{	// Collection type where the container should be installed:
			unset( $container_widgets['coll_type'] );
		}

		foreach( $container_widgets as $widget )
		{
			if( isset( $widget['install'] ) && ! $widget['install'] )
			{	// Skip widget because it should not be installed by condition from config:
				continue;
			}

			if( isset( $widget['coll_type'] ) && ! is_allowed_option( $kind, $widget['coll_type'] ) )
			{	// Skip widget because it should not be installed for the given collection kind:
				continue;
			}

			if( isset( $widget['coll_ID'] ) && ! is_allowed_option( $blog_id, $widget['coll_ID'] ) )
			{	// Skip widget because it should not be installed for the given collection ID:
				continue;
			}

			// Initialize a widget row to insert into DB below by single query:
			$widget_type = isset( $widget['type'] ) ? $widget['type'] : 'core';
			$widget_params = isset( $widget['params'] ) ? ( is_array( $widget['params'] ) ? serialize( $widget['params'] ) : $widget['params'] ) : NULL;
			$widget_enabled = isset( $widget['enabled'] ) ? intval( $widget['enabled'] ) : 1;
			$basic_widgets_insert_sql_rows[] = '( '.$wico_id.', '.$widget[0].', '.$widget_enabled.', '.$DB->quote( $widget_type ).', '.$DB->quote( $widget[1] ).', '.$DB->quote( $widget_params ).' )';
		}
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