<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage bootstrap_blog
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class bootstrap_blog_Skin extends Skin
{
	/**
	 * Skin version
	 * @var string
	 */
	var $version = '7.0.0';

	/**
	 * Do we want to use style.min.css instead of style.css ?
	 */
	var $use_min_css = 'check';  // true|false|'check' Set this to true for better optimization
	// Note: we leave this on "check" in the bootstrap_blog_skin so it's easier for beginners to just delete the .min.css file
	// But for best performance, you should set it to true.

	/**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'Bootstrap Blog';
	}


	/**
	 * Get default type for the skin.
	 */
	function get_default_type()
	{
		return 'rwd';
	}


	/**
	 * What evoSkins API does has this skin been designed with?
	 *
	 * This determines where we get the fallback templates from (skins_fallback_v*)
	 * (allows to use new markup in new b2evolution versions)
	 */
	function get_api_version()
	{
		return 6;
	}


	/**
	 * Get supported collection kinds.
	 *
	 * This should be overloaded in skins.
	 *
	 * For each kind the answer could be:
	 * - 'yes' : this skin does support that collection kind (the result will be was is expected)
	 * - 'partial' : this skin is not a primary choice for this collection kind (but still produces an output that makes sense)
	 * - 'maybe' : this skin has not been tested with this collection kind
	 * - 'no' : this skin does not support that collection kind (the result would not be what is expected)
	 * There may be more possible answers in the future...
	 */
	public function get_supported_coll_kinds()
	{
		$supported_kinds = array(
				'minisite' => 'partial',
				'main' => 'partial',
				'std' => 'yes',		// Blog
				'photo' => 'no',
				'forum' => 'no',
				'manual' => 'no',
				'group' => 'maybe',  // Tracker
				// Any kind that is not listed should be considered as "maybe" supported
			);

		return $supported_kinds;
	}


	/**
	 * Get the container codes of the skin main containers
	 *
	 * This should NOT be protected. It should be used INSTEAD of file parsing.
	 * File parsing should only be used if this function is not defined
	 *
	 * @return array Array which overrides default containers; Empty array means to use all default containers.
	 */
	function get_declared_containers()
	{
		// Array to override default containers from function get_skin_default_containers():
		// - Key is widget container code;
		// - Value: array( 0 - container name, 1 - container order ),
		//          NULL - means don't use the container, WARNING: it(only empty/without widgets) will be deleted from DB on changing of collection skin or on reload container definitions.
		return array();
	}


	/**
	 * What CSS framework does has this skin been designed with?
	 *
	 * This may impact default markup returned by Skin::get_template() for example
	 */
	function get_css_framework()
	{
		return 'bootstrap';
	}


	/**
	 * Get the declarations of the widgets that the skin wants to use.
	 *
	 * @param string Collection kind: 'std', 'main', 'photo', 'group', 'forum', 'manual'
	 * @param array Additional params. Example value 'init_as_blog_b' => true
	 * @return array Array of default widgets:
	 *          - Key - Container code,
	 *          - Value - array of widget arrays OR SPECIAL VALUES:
	 *             - 'coll_type': Include this container only for collection kinds separated by comma, first char "-" means to exclude,
	 *             - 'type': Container type, empty - main container, other values: 'sub', 'page', 'shared', 'shared-sub',
	 *             - 'name': Container name,
	 *             - 'order': Container order,
	 *             - widget data array():
	 *                - 0: Widget order (*mandatory field*),
	 *                - 1: Widget code (*mandatory field*),
	 *                - 'params' - Widget params(array or serialized string),
	 *                - 'type' - Widget type(default = 'core', another value - 'plugin'),
	 *                - 'enabled' - Boolean value; default is TRUE; FALSE to install the widget as disabled,
	 *                - 'coll_type': Include this widget only for collection kinds separated by comma, first char "-" means to exclude,
	 *                - 'install' - Boolean value; default is TRUE; FALSE to skip this widget on install.
	 */
	function get_default_widgets( $kind = '', $context = array() )
	{
		global $DB;

		$context = array_merge( array(
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

		// Init insert widget query and default params
		$default_blog_param = 's:7:"blog_ID";s:0:"";';
		if( ! empty( $context['coll_photoblog_ID'] ) )
		{ // In the case of initial install, we grab photos out of the photoblog (Blog #4)
			$default_blog_param = 's:7:"blog_ID";s:1:"'.intval( $context['coll_photoblog_ID'] ).'";';
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
			$install_not_forum = ( ! $context['init_as_forums'] && $kind != 'forum' );
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
				array( 30, 'evo_Calr', 'type' => 'plugin', 'install' => ( $install_not_forum && $blog_id > $context['coll_blog_a_ID'] ) ),
				array( 40, 'coll_longdesc', 'install' => $install_not_forum, 'params' => array( 'title' => '$title$' ) ),
				array( 50, 'coll_search_form', 'install' => $install_not_forum ),
				array( 60, 'coll_category_list', 'install' => $install_not_forum ),
				array( 70, 'coll_item_list', 'install' => $install_not_forum && $context['init_as_home'], 'params' => array(
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
				array( 80, 'coll_media_index', 'install' => ( $install_not_forum && ! $context['init_as_blog_b'] ), 'params' => 'a:11:{s:5:"title";s:12:"Random photo";s:10:"thumb_size";s:11:"fit-160x120";s:12:"thumb_layout";s:4:"grid";s:12:"grid_nb_cols";s:1:"1";s:5:"limit";s:1:"1";s:8:"order_by";s:4:"RAND";s:9:"order_dir";s:3:"ASC";'.$default_blog_param.'s:11:"widget_name";s:12:"Random photo";s:16:"widget_css_class";s:0:"";s:9:"widget_ID";s:0:"";}' ),
				array( 90, 'coll_item_list', 'install' => ( $install_not_forum && ! $context['init_as_blog_a'] && ! $context['init_as_blog_b'] ), 'params' => array(
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
			array( 40, 'poll', 'install' => $context['init_as_blog_b'], 'params' => array( 'poll_ID' => 1 ) ),
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
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 * @return array
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
				'section_layout_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Layout Settings')
				),
					'layout' => array(
						'label' => T_('Layout'),
						'note' => T_('Select skin layout.'),
						'defaultvalue' => 'right_sidebar',
						'options' => array(
								'single_column'              => T_('Single Column Large'),
								'single_column_normal'       => T_('Single Column'),
								'single_column_narrow'       => T_('Single Column Narrow'),
								'single_column_extra_narrow' => T_('Single Column Extra Narrow'),
								'left_sidebar'               => T_('Left Sidebar'),
								'right_sidebar'              => T_('Right Sidebar'),
							),
						'type' => 'select',
					),
					'max_image_height' => array(
						'label' => T_('Max image height'),
						'input_suffix' => ' px ',
						'note' => T_('Set maximum height for post images.'),
						'defaultvalue' => '',
						'type' => 'integer',
						'allow_empty' => true,
					),

					'font' => array(
						'label' => T_('Default font'),
						'type'  => 'input_group',
						'inputs' => array(
							'_family' => array(
								'defaultvalue' => 'system_helveticaneue',
								'options'      => $this->get_font_definitions(),
								'type'         => 'select'
							),
							'_size' => array(
								'label' => T_('Size'),
								'defaultvalue' => 'default',
								'options'      => array(
									'default'        => T_('Default (14px)'),
									'standard'       => T_('Standard (16px)'),
									'medium'         => T_('Medium (18px)'),
									'large'          => T_('Large (20px)'),
									'very_large'     => T_('Very large (22px)'),
								),
								'type' => 'select'
							),
							'_weight' => array(
								'label' => T_('Weight'),
								'defaultvalue' => '400',
								'options' => array(
										'100' => '100',
										'200' => '200',
										'300' => '300',
										'400' => '400 ('.T_('Normal').')',
										'500' => '500',
										'600' => '600',
										'700' => '700 ('.T_('Bold').')',
										'800' => '800',
										'900' => '900',
									),
								'type' => 'select',
							)
						)
					),

					'message_affix_offset' => array(
						'label' => T_('Messages affix offset'),
						'note' => 'px. ' . T_('Set message top offset value.'),
						'defaultvalue' => '',
						'type' => 'integer',
						'allow_empty' => true,
					),
				'section_layout_end' => array(
					'layout' => 'end_fieldset',
				),


				'section_color_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Custom Settings')
				),
					'page_bg_color' => array(
						'label' => T_('Background color'),
						'note' => T_('E-g: #ff0000 for red'),
						'defaultvalue' => '#fff',
						'type' => 'color',
					),
					'page_text_color' => array(
						'label' => T_('Text color'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#333',
						'type' => 'color',
					),
					'page_link_color' => array(
						'label' => T_('Link color'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#337ab7',
						'type' => 'color',
					),
					'page_hover_link_color' => array(
						'label' => T_('Hover link color'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#23527c',
						'type' => 'color',
					),
					'bgimg_text_color' => array(
						'label' => T_('Text color on background image'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#fff',
						'type' => 'color',
					),
					'bgimg_link_color' => array(
						'label' => T_('Link color on background image'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#6cb2ef',
						'type' => 'color',
					),
					'bgimg_hover_link_color' => array(
						'label' => T_('Hover link color on background image'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#6cb2ef',
						'type' => 'color',
					),
					'current_tab_text_color' => array(
						'label' => T_('Current tab text color'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#333',
						'type' => 'color',
					),
					'current_tab_bg_color' => array(
						'label' => T_('Current tab background color'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#fff',
						'type' => 'color',
					),
					'hover_tab_bg_color' => array(
						'label' => T_('Hovered tab background color'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#eee',
						'type' => 'color',
					),
					'panel_bg_color' => array(
						'label' => T_('Panel background color'),
						'note' => T_('Choose background color for function panels and widgets.'),
						'defaultvalue' => '#ffffff',
						'type' => 'color',
					),
					'panel_border_color' => array(
						'label' => T_('Panel border color'),
						'note' => T_('Choose border color for function panels and widgets.'),
						'defaultvalue' => '#ddd',
						'type' => 'color',
					),
					'panel_heading_bg_color' => array(
						'label' => T_('Panel heading background color'),
						'note' => T_('Choose background color for function panels and widgets.'),
						'defaultvalue' => '#f5f5f5',
						'type' => 'color',
					),
				'section_color_end' => array(
					'layout' => 'end_fieldset',
				),


				'section_colorbox_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Colorbox Image Zoom')
				),
					'colorbox' => array(
						'label' => T_('Colorbox Image Zoom'),
						'note' => T_('Check to enable javascript zooming on images (using the colorbox script)'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'colorbox_vote_post' => array(
						'label' => T_('Voting on Post Images'),
						'note' => T_('Check this to enable AJAX voting buttons in the colorbox zoom view'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'colorbox_vote_post_numbers' => array(
						'label' => T_('Display Votes'),
						'note' => T_('Check to display number of likes and dislikes'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'colorbox_vote_comment' => array(
						'label' => T_('Voting on Comment Images'),
						'note' => T_('Check this to enable AJAX voting buttons in the colorbox zoom view'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'colorbox_vote_comment_numbers' => array(
						'label' => T_('Display Votes'),
						'note' => T_('Check to display number of likes and dislikes'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'colorbox_vote_user' => array(
						'label' => T_('Voting on User Images'),
						'note' => T_('Check this to enable AJAX voting buttons in the colorbox zoom view'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'colorbox_vote_user_numbers' => array(
						'label' => T_('Display Votes'),
						'note' => T_('Check to display number of likes and dislikes'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
				'section_colorbox_end' => array(
					'layout' => 'end_fieldset',
				),


				'section_username_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Username options')
				),
					'gender_colored' => array(
						'label' => T_('Display gender'),
						'note' => T_('Use colored usernames to differentiate men & women.'),
						'defaultvalue' => 0,
						'type' => 'checkbox',
					),
					'bubbletip' => array(
						'label' => T_('Username bubble tips'),
						'note' => T_('Check to enable bubble tips on usernames'),
						'defaultvalue' => 0,
						'type' => 'checkbox',
					),
					'autocomplete_usernames' => array(
						'label' => T_('Autocomplete usernames'),
						'note' => T_('Check to enable auto-completion of usernames entered after a "@" sign in the comment forms'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
				'section_username_end' => array(
					'layout' => 'end_fieldset',
				),


				'section_access_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('When access is denied or requires login...')
				),
					'access_login_containers' => array(
						'label' => T_('Display on login screen'),
						'note' => '',
						'type' => 'checklist',
						'options' => array(
							array( 'header',   sprintf( T_('"%s" container'), NT_('Header') ),    1 ),
							array( 'page_top', sprintf( T_('"%s" container'), NT_('Page Top') ),  1 ),
							array( 'menu',     sprintf( T_('"%s" container'), NT_('Menu') ),      0 ),
							array( 'sidebar',  sprintf( T_('"%s" container'), NT_('Sidebar') ),   0 ),
							array( 'sidebar2', sprintf( T_('"%s" container'), NT_('Sidebar 2') ), 0 ),
							array( 'footer',   sprintf( T_('"%s" container'), NT_('Footer') ),    1 ) ),
						),
				'section_access_end' => array(
					'layout' => 'end_fieldset',
				),

			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Get ready for displaying the skin.
	 *
	 * This may register some CSS or JS...
	 */
	function display_init()
	{
		global $Messages, $disp, $debug;

		// Request some common features that the parent function (Skin::display_init()) knows how to provide:
		parent::display_init( array(
				'jquery',                  // Load jQuery
				'font_awesome',            // Load Font Awesome (and use its icons as a priority over the Bootstrap glyphicons)
				'bootstrap',               // Load Bootstrap (without 'bootstrap_theme_css')
				'bootstrap_evo_css',       // Load the b2evo_base styles for Bootstrap (instead of the old b2evo_base styles)
				'bootstrap_messages',      // Initialize $Messages Class to use Bootstrap styles
				'style_css',               // Load the style.css file of the current skin
				'colorbox',                // Load Colorbox (a lightweight Lightbox alternative + customizations for b2evo)
				'bootstrap_init_tooltips', // Inline JS to init Bootstrap tooltips (E.g. on comment form for allowed file extensions)
				'disp_auto',               // Automatically include additional CSS and/or JS required by certain disps (replace with 'disp_off' to disable this)
			) );

		// Skin specific initializations:
		global $media_url, $media_path;

		// Add custom CSS:
		$custom_css = '';

		if( $color = $this->get_setting( 'page_bg_color' ) )
		{ // Custom page background color:
			$custom_css .= '#skin_wrapper { background-color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'page_text_color' ) )
		{ // Custom page text color:
			$custom_css .= '#skin_wrapper { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'page_link_color' ) )
		{ // Custom page link color:
			$custom_css .= 'a { color: '.$color." }\n";
			$custom_css .= 'h4.evo_comment_title a, h4.panel-title a.evo_comment_type, .pagination li:not(.active) a, .pagination li:not(.active) span { color: '.$color." !important }\n";
			$custom_css .= '.pagination li.active a, .pagination li.active span { color: #fff; background-color: '.$color.' !important; border-color: '.$color." }\n";
			if( $this->get_setting( 'gender_colored' ) !== 1 )
			{ // If gender option is not enabled, choose custom link color. Otherwise, chose gender link colors:
				$custom_css .= 'h4.panel-title a { color: '.$color." }\n";
			}
		}
		if( $color = $this->get_setting( 'page_hover_link_color' ) )
		{ // Custom page link color on hover:
			$custom_css .= 'a:hover { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'bgimg_text_color' ) )
		{	// Custom text color on background image:
			$custom_css .= '.evo_hasbgimg { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'bgimg_link_color' ) )
		{	// Custom link color on background image:
			$custom_css .= '.evo_hasbgimg a:not(.btn) { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'bgimg_hover_link_color' ) )
		{	// Custom link hover color on background image:
			$custom_css .= '.evo_hasbgimg a:not(.btn):hover { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'current_tab_text_color' ) )
		{ // Custom current tab text color:
			$custom_css .= 'ul.nav.nav-tabs li a.selected { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'current_tab_bg_color' ) )
		{ // Custom current tab background color:
			$custom_css .= 'ul.nav.nav-tabs li a.selected { background-color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'hover_tab_bg_color' ) )
		{ // Custom hovered tab background text color:
			$custom_css .= 'ul.nav.nav-tabs li a.default:hover { background-color: '.$color."; border-top-color: $color; border-left-color: $color; border-right-color: $color }\n";
		}
		if( $color = $this->get_setting( 'panel_bg_color' ) )
		{ // Panel background text color:
			$custom_css .= '.panel, .pagination>li>a { background-color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'panel_border_color' ) )
		{ // Panel border color:
			$custom_css .= '
			.pagination li a, .pagination>li>a:focus, .pagination>li>a:hover, .pagination>li>span:focus, .pagination>li>span:hover,
			.nav-tabs,
			.panel-default, .panel .panel-footer,
			.panel .table, .panel .table th, .table-bordered>tbody>tr>td, .table-bordered>tbody>tr>th, .table-bordered>tfoot>tr>td, .table-bordered>tfoot>tr>th, .table-bordered>thead>tr>td, .table-bordered>thead>tr>th
			{ border-color: '.$color." }\n";
			$custom_css .= '.panel .panel-heading { border-color: '.$color."; background-color: $color }\n";
			$custom_css .= '.nav-tabs>li>a:hover { border-bottom: 1px solid '.$color." }\n";
			$custom_css .= '.nav-tabs>li.active>a, .nav-tabs>li.active>a:focus, .nav-tabs>li.active>a:hover { border-top-color: '.$color."; border-left-color: $color; border-right-color: $color }\n";
		}
		if( $color = $this->get_setting( 'panel_heading_bg_color' ) )
		{ // Panel border color:
			$custom_css .= '.panel .panel-heading, .panel .panel-footer { background-color: '.$color." }\n";
		}

		// Limit images by max height:
		$max_image_height = intval( $this->get_setting( 'max_image_height' ) );
		if( $max_image_height > 0 )
		{
			$custom_css .= '.evo_image_block img { max-height: '.$max_image_height.'px; width: auto; }'." }\n";
		}

		// Font size customization
		if( $font_size = $this->get_setting( 'font_size' ) )
		{
			switch( $font_size )
			{
				case 'default': // When default font size, no CSS entry
					//$custom_css .= '';
					break;

				case 'standard':// When standard layout
					$custom_css .= '.container { font-size: 16px !important'." }\n";
					$custom_css .= '.container input.search_field { height: 100%'." }\n";
					$custom_css .= '.container h1 { font-size: 38px'." }\n";
					$custom_css .= '.container h2 { font-size: 32px'." }\n";
					$custom_css .= '.container h3 { font-size: 26px'." }\n";
					$custom_css .= '.container h4 { font-size: 18px'." }\n";
					$custom_css .= '.container h5 { font-size: 16px'." }\n";
					$custom_css .= '.container h6 { font-size: 14px'." }\n";
					$custom_css .= '.container .small { font-size: 85% !important'." }\n";
					break;

				case 'medium': // When default font size, no CSS entry
					$custom_css .= '.container { font-size: 18px !important'." }\n";
					$custom_css .= '.container input.search_field { height: 100%'." }\n";
					$custom_css .= '.container h1 { font-size: 40px'." }\n";
					$custom_css .= '.container h2 { font-size: 34px'." }\n";
					$custom_css .= '.container h3 { font-size: 28px'." }\n";
					$custom_css .= '.container h4 { font-size: 20px'." }\n";
					$custom_css .= '.container h5 { font-size: 18px'." }\n";
					$custom_css .= '.container h6 { font-size: 16px'." }\n";
					$custom_css .= '.container .small { font-size: 85% !important'." }\n";
					break;

				case 'large': // When default font size, no CSS entry
					$custom_css .= '.container { font-size: 20px !important'." }\n";
					$custom_css .= '.container input.search_field { height: 100%'." }\n";
					$custom_css .= '.container h1 { font-size: 42px'." }\n";
					$custom_css .= '.container h2 { font-size: 36px'." }\n";
					$custom_css .= '.container h3 { font-size: 30px'." }\n";
					$custom_css .= '.container h4 { font-size: 22px'." }\n";
					$custom_css .= '.container h5 { font-size: 20px'." }\n";
					$custom_css .= '.container h6 { font-size: 18px'." }\n";
					$custom_css .= '.container .small { font-size: 85% !important'." }\n";
					break;

				case 'very_large': // When default font size, no CSS entry
					$custom_css .= '.container { font-size: 22px !important'." }\n";
					$custom_css .= '.container input.search_field { height: 100%'." }\n";
					$custom_css .= '.container h1 { font-size: 44px'." }\n";
					$custom_css .= '.container h2 { font-size: 38px'." }\n";
					$custom_css .= '.container h3 { font-size: 32px'." }\n";
					$custom_css .= '.container h4 { font-size: 24px'." }\n";
					$custom_css .= '.container h5 { font-size: 22px'." }\n";
					$custom_css .= '.container h6 { font-size: 20px'." }\n";
					$custom_css .= '.container .small { font-size: 85% !important'." }\n";
					break;
			}
		}

		// Font family customization
		$custom_css .= $this->apply_selected_font( '#skin_wrapper', 'font_family', NULL, 'font_weight', 'font' );

		if( ! empty( $custom_css ) )
		{	// Function for custom_css:
			$custom_css = '<style type="text/css">
<!--
'.$custom_css.'
-->
		</style>';
			add_headline( $custom_css );
			init_affix_messages_js( $this->get_setting( 'message_affix_offset' ) );
		}
	}


	/**
	 * Check if we can display a sidebar for the current layout
	 *
	 * @param boolean TRUE to check if at least one sidebar container is visible
	 * @return boolean TRUE to display a sidebar
	 */
	function is_visible_sidebar( $check_containers = false )
	{
		$layout = $this->get_setting( 'layout' );

		if( $layout != 'left_sidebar' && $layout != 'right_sidebar' )
		{ // Sidebar is not displayed for selected skin layout
			return false;
		}

		if( $check_containers )
		{ // Check if at least one sidebar container is visible
			return ( $this->show_container_when_access_denied( 'sidebar' ) ||  $this->show_container_when_access_denied( 'sidebar2' ) );
		}
		else
		{ // We should not check the visibility of the sidebar containers for this case
			return true;
		}
	}


	/**
	 * Get value for attbiute "class" of column block
	 * depending on skin setting "Layout"
	 *
	 * @return string
	 */
	function get_column_class()
	{
		switch( $this->get_setting( 'layout' ) )
		{
			case 'single_column':
				// Single Column Large
				return 'col-md-12';

			case 'single_column_normal':
				// Single Column
				return 'col-xs-12 col-sm-12 col-md-12 col-lg-10 col-lg-offset-1';

			case 'single_column_narrow':
				// Single Column Narrow
				return 'col-xs-12 col-sm-12 col-md-10 col-md-offset-1 col-lg-8 col-lg-offset-2';

			case 'single_column_extra_narrow':
				// Single Column Extra Narrow
				return 'col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2 col-lg-6 col-lg-offset-3';

			case 'left_sidebar':
				// Left Sidebar
				return 'col-md-9 pull-right';

			case 'right_sidebar':
				// Right Sidebar
			default:
				return 'col-md-9';
		}
	}
}

?>