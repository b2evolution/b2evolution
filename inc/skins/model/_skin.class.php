<?php
/**
 * This file implements the Skin class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );


/**
 * Skin Class
 *
 * @package evocore
 */
class Skin extends DataObject
{
	var $name;
	var $folder;
	var $type;

	/**
	 * Skin version
	 * @var string
	 */
	var $version = NULL;

	/**
	 * Do we want to use style.min.css instead of style.css ?
	 */
	var $use_min_css = false;  // true|false|'check' Set this to true for better optimization
	// Note: we set this to false by default for backwards compatibility with third party skins.
	// But for best performance, you should set it to true.

	/**
	 * Lazy filled.
	 * @var array
	 */
	var $container_list = NULL;

	/**
	 * The translations keyed by locale. They get loaded through include() of _global.php.
	 * @see Skin::T_()
	 * @var array
	 */
	var $_trans = array();


	/**
	 * Constructor
	 *
	 * @param table Database row
	 */
	function __construct( $db_row = NULL, $skin_folder = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_skins__skin', 'skin_', 'skin_ID' );

		if( is_null($db_row) )
		{	// We are creating an object here:
			$this->set( 'folder', $skin_folder );
			$this->set( 'name', $this->get_default_name() );
			$this->set( 'type', $this->get_default_type() );
		}
		else
		{	// Wa are loading an object:
			$this->ID = $db_row->skin_ID;
			$this->name = $db_row->skin_name;
			$this->folder = $db_row->skin_folder;
			$this->type = $db_row->skin_type;
		}
	}


	/**
	 * Get delete restriction settings
	 *
	 * @return array
	 */
	static function get_delete_restrictions()
	{
		return array(
				array( 'table'=>'T_coll_settings', 'fk'=>'cset_value', 'msg'=>T_('%d blogs using this skin'),
						'and_condition' => '( cset_name = "normal_skin_ID" OR cset_name = "mobile_skin_ID" OR cset_name = "tablet_skin_ID" )' ),
				array( 'table'=>'T_settings', 'fk'=>'set_value', 'msg'=>T_('This skin is set as default skin.'),
						'and_condition' => '( set_name = "def_normal_skin_ID" OR set_name = "def_mobile_skin_ID" OR set_name = "def_tablet_skin_ID" )' ),
			);
	}


	/**
	 * Get delete cascade settings
	 *
	 * @return array
	 */
	static function get_delete_cascades()
	{
		return array(
				array( 'table'=>'T_skins__container', 'fk'=>'sco_skin_ID', 'msg'=>T_('%d linked containers') ),
			);
	}


	/**
	 * Install current skin to DB
	 */
	function install()
	{
		// INSERT NEW SKIN INTO DB:
		$this->dbinsert();
	}


	/**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return $this->folder;
	}


	/**
	 * Get default skin type for the skin.
	 */
	function get_default_type()
	{
		return (substr($this->folder,0,1) == '_' ? 'feed' : 'normal');
	}


	/**
	 * Get the customized name for the skin.
	 */
	function get_name()
	{
		return $this->name;
	}


	/**
	 * What evoSkins API does has this skin been designed with?
	 *
	 * This determines where we get the fallback templates from (skins_fallback_v*)
	 * (allows to use new markup in new b2evolution versions)
	 */
	function get_api_version()
	{
		return 5;
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
				'main' => 'maybe',
				'std' => 'maybe',		// Blog
				'photo' => 'maybe',
				'forum' => 'no',
				'manual' => 'no',
				'group' => 'maybe',  // Tracker
				// Any kind that is not listed should be considered as "maybe" supported
			);

		return $supported_kinds;
	}


	final public function supports_coll_kind( $kind )
	{
		$supported_kinds = $this->get_supported_coll_kinds();

		if( isset($supported_kinds[$kind]) )
		{
			return $supported_kinds[$kind];
		}

		// When the skin doesn't say... consider it a "maybe":
		return 'maybe';
	}

	/*
	 * What CSS framework does has this skin been designed with?
	 *
	 * This may impact default markup returned by Skin::get_template() for example
	 */
	function get_css_framework()
	{
		return '';	// Other possibilities: 'bootstrap', 'foundation'... (maybe 'bootstrap4' later...)
	}


	/**
	 * Get the declarations of the widgets that the skin wants to use.
	 *
	 * The skin class defines a default set of widgets to used. Skins should override this.
	 *
	 * @param string Collection kind: 'std', 'main', 'photo', 'group', 'forum', 'manual'
	 * @param array Additional params. Example value 'init_as_blog_b' => true
	 * @return array Array of default widgets:
	 *               - Key - Container name, 
	 *               - Value - array of widgets:
	 *                         0 - Type: 'core', 'plugin'.
	 *                         1 - Code.
	 *                         2 - Params: Array with params: Key - param code, Value - param value; NULL - for default params. (Default = NULL)
	 *                         3 - Order. (Default is started from 1 and incremented inside container)
	 *                         4 - Enabled? 1 or 0. (Default = 1)
	 */
	function get_default_widgets( $coll_kind, $context = array() )
	{
		global $DB;

		$context = array_merge( array(
				'coll_home_ID'          => NULL,
				'coll_photoblog_ID'     => NULL,
				'init_as_home'          => false,
				'init_as_blog_a'        => false,
				'init_as_blog_b'        => false,
				'init_as_events'        => false,
				'install_test_features' => false,
			), $context );

		$declared_widgets = array();

		// HEADER:
		$declared_widgets['Header'][] = array( 'core', 'coll_title' );
		$declared_widgets['Header'][] = array( 'core', 'coll_tagline' );


		// MENU:
		if( $coll_kind != 'main' )
		{	// Don't add widgets to Menu container for Main collections:
			// Home page
			$declared_widgets['Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'home' ) );
			if( $context['init_as_blog_b'] )
			{	// Recent Posts
				$declared_widgets['Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'recentposts', 'link_text' => T_('News') ) );
			}
			if( $coll_kind == 'forum' )
			{	// Latest Topics and Replies ONLY for forum
				$declared_widgets['Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'recentposts', 'link_text' => T_('Latest topics') ) );
				$declared_widgets['Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'latestcomments', 'link_text' => T_('Latest replies') ) );
			}
			if( $coll_kind == 'manual' )
			{	// Latest Topics and Replies ONLY for forum
				$declared_widgets['Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'recentposts', 'link_text' => T_('Latest pages') ) );
				$declared_widgets['Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'latestcomments', 'link_text' => T_('Latest comments') ) );
			}
			if( $coll_kind == 'forum' || $coll_kind == 'manual' )
			{	// Add menu with flagged items:
				$declared_widgets['Menu'][] = array( 'core', 'flag_menu_link', array( 'link_text' => ( $coll_kind == 'forum' ) ? T_('Flagged topics') : T_('Flagged pages') ) );
			}
			if( $coll_kind == 'photo' )
			{	// Add menu with Photo index
				$declared_widgets['Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'mediaidx', 'link_text' => T_('Index') ) );
			}
			if( $coll_kind == 'forum' )
			{	// Add menu with User Directory
				$declared_widgets['Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'users' ) );
			}
			// Pages list:
			$declared_widgets['Menu'][] = array( 'core', 'coll_page_list' );
			if( $coll_kind == 'forum' )
			{ // My Profile
				$declared_widgets['Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'myprofile' ), NULL, 0 );
			}
			if( $coll_kind == 'std' )
			{	// Categories
				$declared_widgets['Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'catdir' ) );
				// Archives
				$declared_widgets['Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'arcdir' ) );
				// Latest comments
				$declared_widgets['Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'latestcomments' ) );
			}
			$declared_widgets['Menu'][] = array( 'core', 'msg_menu_link', array( 'link_type' => 'messages' ), NULL, 0 );
			$declared_widgets['Menu'][] = array( 'core', 'msg_menu_link', array( 'link_type' => 'contacts', 'show_badge' => 0 ), NULL, 0 );
			$declared_widgets['Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'login' ), NULL, 0 );
			if( $coll_kind == 'forum' )
			{	// Register
				$declared_widgets['Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'register' ) );
			}
		}

		/* Item Single Header */
		if( in_array( $coll_kind, array( 'forum', 'group' ) ) )
		{
			$declared_widgets['Item Single Header'][] = array( 'core', 'item_info_line', array(
					'permalink_icon' => 0,
					'before_author'  => 'started_by',
					'last_touched'   => 1,
					'category'       => 0,
					'edit_link'      => 0,
				) );
			$declared_widgets['Item Single Header'][] = array( 'core', 'item_tags' );
			$declared_widgets['Item Single Header'][] = array( 'core', 'item_seen_by' );
		}
		else
		{
			$declared_widgets['Item Single Header'][] = array( 'core', 'item_info_line');
		}

		/* Item Single */
		$declared_widgets['Item Single'][] = array( 'core', 'item_content' );
		$declared_widgets['Item Single'][] = array( 'core', 'item_attachments' );
		if( ! $context['init_as_blog_a'] && ! $context['init_as_events'] && ! in_array( $coll_kind, array( 'forum', 'group' ) ) )
		{ // Item Tags
			$declared_widgets['Item Single'][] = array( 'core', 'item_tags' );
		}
		if( $context['init_as_blog_b'] )
		{ // About Author
			$declared_widgets['Item Single'][] = array( 'core', 'item_about_author' );
		}
		if( ( $context['init_as_blog_a'] || $context['init_as_events'] ) && $context['install_test_features'] )
		{ // Google Maps
			$declared_widgets['Item Single'][] = array( 'plugin', 'evo_Gmaps' );
		}
		if( $context['init_as_blog_a'] || $coll_kind == 'manual' )
		{ // Small Print
			$declared_widgets['Item Single'][] = array( 'core', 'item_small_print', array( 'format' => ( $context['init_as_blog_a'] ? 'standard' : 'revision' ) ) );
		}
		if( ! in_array( $coll_kind, array( 'forum', 'group' ) ) )
		{ // Seen by
			$declared_widgets['Item Single'][] = array( 'core', 'item_seen_by' );
		}
		if( $coll_kind != 'forum' )
		{	// Item voting panel:
			$declared_widgets['Item Single'][] = array( 'core', 'item_vote' );
		}


		/* Sidebar Single */
		if( $coll_kind == 'forum' )
		{
			$declared_widgets['Sidebar Single'][] = array( 'core', 'coll_related_post_list' );
		}


		/* Page Top */
		$declared_widgets['Page Top'][] = array( 'core', 'social_links', array(
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
			) );

		/* Sidebar */
		if( $coll_kind == 'manual' )
		{
			$declared_widgets['Sidebar'][] = array( 'core', 'coll_search_form', array( 'title' => T_('Search this manual:') ) );
			$declared_widgets['Sidebar'][] = array( 'core', 'content_hierarchy' );
		}
		else
		{
			if( $context['install_test_features'] )
			{
				if( $coll_kind != 'forum' && $coll_kind != 'manual' )
				{ // Current filters widget
					$declared_widgets['Sidebar'][] = array( 'core', 'coll_current_filters' );
				}
				// User login widget
				$declared_widgets['Sidebar'][] = array( 'core', 'user_login' );
			}
			if( $coll_kind != 'forum' )
			{ // Don't install these Sidebar widgets for blog 'Forums'
				$declared_widgets['Sidebar'][] = array( 'core', 'coll_avatar' );
				if( ! $context['init_as_blog_a'] && ! $context['init_as_home'] )
				{
					$declared_widgets['Sidebar'][] = array( 'plugin', 'evo_Calr' );
				}
				$declared_widgets['Sidebar'][] = array( 'core', 'coll_longdesc', array( 'title' => '$title$' ) );
				$declared_widgets['Sidebar'][] = array( 'core', 'coll_search_form' );
				$declared_widgets['Sidebar'][] = array( 'core', 'coll_category_list' );

				if( $context['init_as_home'] )
				{ // Advertisements, Install only for blog #1 home blog
					$advertisement_type_ID = $DB->get_var( 'SELECT ityp_ID FROM T_items__type WHERE ityp_name = "Advertisement"' );
					$declared_widgets['Sidebar'][] = array( 'core', 'coll_item_list', array(
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

				if( ! $context['init_as_blog_b'] )
				{
					$declared_widgets['Sidebar'][] = array( 'core', 'coll_media_index', array(
							'title'        => 'Random photo',
							'thumb_size'   => 'fit-160x120',
							'thumb_layout' => 'grid',
							'grid_nb_cols' => 1,
							'limit'        => 1,
							'order_by'     => 'RAND',
							'order_dir'    => 'ASC',
							// In the case of initial install, we grab photos out of the photoblog:
							'blog_ID'      => ( empty( $context['coll_photoblog_ID'] ) ? '' : intval( $context['coll_photoblog_ID'] ) ),
						) );
				}
				if( ! empty( $context['coll_home_ID'] ) && ( $context['init_as_blog_a'] || $context['init_as_blog_b'] ) )
				{
					$sidebar_type_ID = $DB->get_var( 'SELECT ityp_ID FROM T_items__type WHERE ityp_name = "Sidebar link"' );
					$declared_widgets['Sidebar'][] = array( 'core', 'coll_item_list', array(
							'blog_ID'              => $context['coll_home_ID'],
							'item_type'            => empty( $sidebar_type_ID ) ? '#' : $sidebar_type_ID,
							'title'                => 'Linkblog',
							'item_group_by'        => 'chapter',
							'item_title_link_type' => 'auto',
							'item_type_usage'      => 'special',
						) );
				}
			}
			if( $coll_kind == 'forum' )
			{
				$declared_widgets['Sidebar'][] = array( 'core', 'user_avatars', array(
						'title' => 'Most Active Users',
						'limit' => 6,
						'order_by' => 'numposts',
					) );
			}
			$declared_widgets['Sidebar'][] = array( 'core', 'coll_xml_feeds' );
			$declared_widgets['Sidebar'][] = array( 'core', 'mobile_skin_switcher' );
		}


		/* Sidebar 2 */
		if( $coll_kind != 'forum' )
		{
			$declared_widgets['Sidebar 2'][] = array( 'core', 'coll_post_list' );
			if( $context['init_as_blog_b'] )
			{
				$declared_widgets['Sidebar 2'][] = array( 'core', 'coll_item_list', array(
						'title'                => 'Sidebar links',
						'order_by'             => 'RAND',
						'item_title_link_type' => 'auto',
						'item_type_usage'      => 'special',
					) );
			}
			$declared_widgets['Sidebar 2'][] = array( 'core', 'coll_comment_list' );
			$declared_widgets['Sidebar 2'][] = array( 'core', 'coll_media_index', array(
					'grid_nb_cols' => 3,
					'limit'        => 9,
					// In the case of initial install, we grab photos out of the photoblog:
					'blog_ID'      => ( empty( $context['coll_photoblog_ID'] ) ? '' : intval( $context['coll_photoblog_ID'] ) ),
				) );
			$declared_widgets['Sidebar 2'][] = array( 'core', 'free_html', array(
					'title'   => 'Sidebar 2',
					'content' => 'This is the "Sidebar 2" container. You can place any widget you like in here. In the evo toolbar at the top of this page, select "Customize", then "Blog Widgets".',
				) );
		}


		/* Front Page Main Area */
		if( $coll_kind == 'main' )
		{ // Display blog title and tagline for main blogs
			$declared_widgets['Front Page Main Area'][] = array( 'core', 'coll_title' );
			$declared_widgets['Front Page Main Area'][] = array( 'core', 'coll_tagline' );
		}


		if( $coll_kind == 'main' )
		{ // Hide a title of the front intro post
			$featured_intro_params = array( 'disp_title' => 0 );
		}
		else
		{
			$featured_intro_params = NULL;
		}
		$declared_widgets['Front Page Main Area'][] = array( 'core', 'coll_featured_intro', $featured_intro_params );

		if( $coll_kind == 'main' )
		{ // Add user links widget only for main kind blogs
			$declared_widgets['Front Page Main Area'][] = array( 'core', 'user_links' );
		}

		if( $coll_kind == 'main' )
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
		$declared_widgets['Front Page Main Area'][] = array( 'core', 'coll_featured_posts', $post_list_params );

		$declared_widgets['Front Page Main Area'][] = array( 'core', 'coll_post_list', array( 'title' => T_('More Posts'), 'featured' => 'other' ) );

		if( $coll_kind != 'main' )
		{ // Don't install the "Recent Commnets" widget for Main blogs
			$declared_widgets['Front Page Main Area'][] = array( 'core', 'coll_comment_list' );
		}

		if( $context['init_as_blog_b'] )
		{	// Install widget "Poll" only for Blog B on install:
			$declared_widgets['Front Page Main Area'][] = array( 'core', 'poll', array( 'poll_ID' => 1 ) );
		}


		/* Front Page Secondary Area */
		if( $coll_kind == 'main' )
		{	// Install the "Organization Members" widget only for Main collections:
			$declared_widgets['Front Page Secondary Area'][] = array( 'core', 'org_members' );
		}
		$declared_widgets['Front Page Secondary Area'][] = array( 'core', 'coll_flagged_list' );


		/* 404 Page */
		$declared_widgets['404 Page'][] = array( 'core', 'page_404_not_found' );
		$declared_widgets['404 Page'][] = array( 'core', 'coll_search_form' );
		$declared_widgets['404 Page'][] = array( 'core', 'coll_tag_cloud' );


		/* Mobile Footer */
		$declared_widgets['Mobile: Footer'][] = array( 'core', 'coll_longdesc' );
		$declared_widgets['Mobile: Footer'][] = array( 'core', 'mobile_skin_switcher' );


		/* Mobile Navigation Menu */
		$declared_widgets['Mobile: Navigation Menu'][] = array( 'core', 'coll_page_list' );
		$declared_widgets['Mobile: Navigation Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'ownercontact' ) );
		$declared_widgets['Mobile: Navigation Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'home' ) );
		if( $coll_kind == 'forum' )
		{ // Add menu with User Directory
			$declared_widgets['Mobile: Navigation Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'users' ) );
		}


		/* Mobile Tools Menu */
		$declared_widgets['Mobile: Tools Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'login' ) );
		$declared_widgets['Mobile: Tools Menu'][] = array( 'core', 'msg_menu_link', array( 'link_type' => 'messages' ) );
		$declared_widgets['Mobile: Tools Menu'][] = array( 'core', 'msg_menu_link', array( 'link_type' => 'contacts', 'show_badge' => 0 ) );
		$declared_widgets['Mobile: Tools Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'logout' ) );


		return $declared_widgets;
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Name
		param_string_not_empty( 'skin_name', T_('Please enter a name.') );
		$this->set_from_Request( 'name' );

		// Skin type
		param( 'skin_type', 'string' );
		$this->set_from_Request( 'type' );

		return ! param_errors_detected();
	}


	/**
	 * Load params
	 */
	function load_params_from_Request()
	{
		load_funcs('plugins/_plugin.funcs.php');

		// Loop through all widget params:
		foreach( $this->get_param_definitions( array('for_editing'=>true) ) as $parname => $parmeta )
		{
			if( isset( $parmeta['type'] ) && $parmeta['type'] == 'color' && empty( $parmeta['valid_pattern'] ) )
			{ // Set default validation for color fields
				$parmeta['valid_pattern'] = array(
						'pattern' => '~^(#([a-f0-9]{3}){1,2})?$~i',
						'error'   => T_('Invalid color code.')
					);
			}
			autoform_set_param_from_request( $parname, $parmeta, $this, 'Skin' );
		}
	}


	/**
	 * Display a container
	 *
	 * @todo fp> if it doesn't get any skin specific, move it outta here! :P
	 * fp> Do we need Skin objects in the frontoffice at all? -- Do we want to include the dispatcher into the Skin object? WARNING: globals
	 * fp> We might want to customize the container defaults. -- Per blog or per skin?
	 *
	 * @param string
	 * @param array
	 */
	function container( $sco_name, $params = array() )
	{
		/**
		 * Blog currently displayed
		 * @var Blog
		 */
		global $Collection, $Blog;
		global $admin_url, $rsc_url;
		global $Timer, $Session;

		$timer_name = 'skin_container('.$sco_name.')';
		$Timer->start( $timer_name );

		$display_containers = $Session->get( 'display_containers_'.$Blog->ID ) == 1;

		if( $display_containers )
		{ // Wrap container in visible container:
			echo '<div class="dev-blocks dev-blocks--container">';
			echo '<div class="dev-blocks-name"><span class="dev-blocks-action"><a href="'
						.$admin_url.'?ctrl=widgets&amp;blog='.$Blog->ID.'">Edit</a></span>Container: <b>'.$sco_name.'</b></div>';
		}

		/**
		 * @var EnabledWidgetCache
		 */
		$EnabledWidgetCache = & get_EnabledWidgetCache();
		$Widget_array = & $EnabledWidgetCache->get_by_coll_container( $Blog->ID, $sco_name );

		if( ! empty( $Widget_array ) )
		{
			foreach( $Widget_array as $w => $ComponentWidget )
			{ // Let the Widget display itself (with contextual params):
				if( $w == 0 )
				{ // Use special params for first widget in the current container
					$orig_params = $params;
					if( isset( $params['block_first_title_start'] ) )
					{
						$params['block_title_start'] = $params['block_first_title_start'];
					}
					if( isset( $params['block_first_title_end'] ) )
					{
						$params['block_title_end'] = $params['block_first_title_end'];
					}
				}
				$widget_timer_name = 'Widget->display('.$ComponentWidget->code.')';
				$Timer->start( $widget_timer_name );
				$ComponentWidget->display_with_cache( $params, array(
						// 'sco_name' => $sco_name, // fp> not sure we need that for now
					) );
				if( $w == 0 )
				{ // Restore the params for next widgets after first
					$params = $orig_params;
					unset( $orig_params );
				}
				$Timer->pause( $widget_timer_name );
			}
		}

		if( $display_containers )
		{ // End of visible container:
			//echo get_icon( 'pixel', 'imgtag', array( 'class' => 'clear' ) );
			echo '</div>';
		}

		$Timer->pause( $timer_name );
	}


	/**
	 * Discover containers included in skin files only in the given folder
	 *
	 * @param string Folder name
	 * @param array Exclude the files
	 * @return array Files that were prepared
	 */
	function discover_containers_by_folder( $folder, $exclude_files = array() )
	{
		global $skins_path, $Messages;

		if( ! $dir = @opendir( $skins_path.$folder ) )
		{ // Skin directory not found!
			$Messages->add( T_('Cannot open skin directory.'), 'error' ); // No trans
			return false;
		}

		// Store the file names to return
		$files = array();

		// Go through all files in the skin directory:
		while( ( $file = readdir( $dir ) ) !== false )
		{
			if( is_array( $exclude_files ) && in_array( $file, $exclude_files ) )
			{ // Skip this file
				continue;
			}

			$rf_main_subpath = trim( $folder.'/'.$file, '/' );
			$af_main_path = $skins_path.$rf_main_subpath;

			if( !is_file( $af_main_path ) || ! preg_match( '~\.php$~', $file ) )
			{ // Not a php template file, go to next:
				continue;
			}

			if( ! is_readable( $af_main_path ) )
			{ // Cannot open PHP file:
				$Messages->add_to_group( sprintf( T_('Cannot read skin file &laquo;%s&raquo;!'), $rf_main_subpath ), 'error', T_('File read error:') );
				continue;
			}

			$file_contents = @file_get_contents( $af_main_path );
			if( ! is_string( $file_contents ) )
			{ // Cannot get contents:
				$Messages->add_to_group( sprintf( T_('Cannot read skin file &laquo;%s&raquo;!'), $rf_main_subpath ), 'error', T_('File read error:') );
				continue;
			}

			$files[] = $files;

			// DETECT if the file contains containers:
			// if( ! preg_match_all( '~ \$Skin->container\( .*? (\' (.+?) \' )|(" (.+?) ") ~xmi', $file_contents, $matches ) )
			if( ! preg_match_all( '~ (\$Skin->|skin_)container\( .*? ((\' (.+?) \')|(" (.+?) ")) ~xmi', $file_contents, $matches ) )
			{ // No containers in this file, go to next:
				continue;
			}

			// Merge matches from the two regexp parts (due to regexp "|" )
			$container_list = array_merge( $matches[4], $matches[6] );

			$c = 0;
			foreach( $container_list as $container )
			{
				if( empty( $container ) )
				{ // regexp empty match -- NOT a container:
					continue;
				}

				// We have one more container:
				$c++;

				if( in_array( $container, $this->container_list ) )
				{ // we already have that one
					continue;
				}

				$this->container_list[] = $container;
			}

			if( $c )
			{
				$Messages->add_to_group( sprintf( T_('%d containers have been found in skin template &laquo;%s&raquo;.'), $c, $rf_main_subpath ), 'success', sprintf( T_('Containers found in skin "%s":'), $folder ) );
			}
		}

		return $files;
	}


	/**
	 * Discover containers included in skin files
	 */
	function discover_containers()
	{
		global $Messages;

		$this->container_list = array();

		// Find the containers in the current skin folder
		$skin_files = $this->discover_containers_by_folder( $this->folder );

		// Find the containers in the root skins folder with excluding the files that are contained in the skin folder
		$this->discover_containers_by_folder( '', $skin_files );

		if( empty( $this->container_list ) )
		{
			$Messages->add( T_('No containers found in this skin!'), 'error' );
			return false;
		}

		return true;
	}


	/**
	 * Get the list of containers that have been previously discovered for this skin.
	 *
	 * @return array
	 */
	function get_containers()
	{
		/**
		 * @var DB
		 */
		global $DB;

		if( is_null( $this->container_list ) )
		{
			$this->container_list = $DB->get_col(
				'SELECT sco_name
					 FROM T_skins__container
					WHERE sco_skin_ID = '.$this->ID, 0, 'get list of containers for skin' );
		}

		return $this->container_list;
	}


	/**
	 * Update the DB based on previously recorded changes
	 *
	 * @return boolean true
	 */
	function dbupdate()
	{
		global $DB;

		$DB->begin();

		if( parent::dbupdate() !== false )
		{	// Skin updated, also save containers:
			$this->db_save_containers();
		}

		$DB->commit();

		return true;
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true
	 */
	function dbinsert()
	{
		global $DB;

		$DB->begin();

		if( parent::dbinsert() )
		{	// Skin saved, also save containers:
			$this->db_save_containers();
		}

		$DB->commit();

		return true;
	}


	/**
	 * Save containers
	 *
	 * to be called by dbinsert / dbupdate
	 */
	function db_save_containers()
	{
		global $DB;

		// Get a list of all currently empty containers:
		$sql = 'SELECT sco_name
						  FROM T_skins__container LEFT JOIN T_widget ON ( sco_name = wi_sco_name )
						 WHERE sco_skin_ID = '.$this->ID.'
						 GROUP BY sco_name
						HAVING COUNT(wi_ID) = 0';
		$empty_containers_list = $DB->get_col( $sql, 0, 'Get empty containers' );
		//pre_dump( $empty_containers_list );

		// Look for containers in skin file:
		$this->discover_containers();

		// Delete empty containers:
		foreach( $empty_containers_list as $empty_container )
		{
			if( !in_array( $empty_container, $this->container_list ) )
			{	// This container has been removed from the skin + it's empty, so delete it from DB:
				$DB->query( 'DELETE FROM T_skins__container
									WHERE sco_name = '.$DB->quote($empty_container) );
			}
		}

		// Make sure new containers are added:
		if( ! empty( $this->container_list ) )
		{
			$values = array();
			foreach( $this->container_list as $container_name )
			{
				$values [] = '( '.$this->ID.', '.$DB->quote($container_name).' )';
			}

			$DB->query( 'REPLACE INTO T_skins__container( sco_skin_ID, sco_name )
										VALUES '.implode( ',', $values ), 'Insert containers' );
		}
	}


	/**
	 * Display skinshot for skin folder in various places.
	 *
	 * Including for NON installed skins.
	 *
	 * @param string Skin folder
	 * @param string Skin name
	 * @param array Params
	 */
	static function disp_skinshot( $skin_folder, $skin_name, $disp_params = array() )
	{
		global $skins_path, $skins_url, $kind;

		$disp_params = array_merge( array(
				'selected'        => false,
				'skinshot_class'  => 'skinshot',
				'skin_compatible' => true,
				'highlighted'     => false,
			), $disp_params );

		if( isset( $disp_params['select_url'] ) )
		{	// Initialize params for link to SELECT new skin for collection:
			$skin_url = $disp_params['select_url'];
			$select_a_begin = '<a href="'.format_to_output( $disp_params['select_url'], 'htmlattr' ).'"'
					.( isset( $disp_params['onclick'] ) ? ' onclick="'.format_to_output( $disp_params['onclick'] , 'htmlattr' ).'"' : '' )
					.' title="'.format_to_output( T_('Select this skin!'), 'htmlattr' ).'">';
			$select_a_end = '</a>';
		}
		elseif( isset( $disp_params['function_url'] ) )
		{	// Initialize params for link to INSTALL new skin and probably select this automatically for collection:
			$skin_url = $disp_params['function_url'];
			$select_a_begin = '<a href="'.$disp_params['function_url'].'"'
				.( isset( $disp_params['onclick'] ) ? ' onclick="'.format_to_output( $disp_params['onclick'] , 'htmlattr' ).'"' : '' )
				.' title="'.format_to_output( T_('Install NOW!'), 'htmlattr' ).'">';
			$select_a_end = '</a>';
		}
		else
		{	// No link:
			$skin_url = '';
			$select_a_begin = '';
			$select_a_end = '';
		}

		// Display skinshot:
		echo '<div class="'.$disp_params['skinshot_class'].( $disp_params['selected'] ? ' skinshot_current' : '' ).'"'.( $disp_params['highlighted'] ? ' id="fadeout-'.$skin_folder.'"' : '' ).'>';
		echo '<div class="skinshot_placeholder">';
		if( file_exists( $skins_path.$skin_folder.'/skinshot.png' ) )
		{
			echo $select_a_begin;
			echo '<img src="'.$skins_url.$skin_folder.'/skinshot.png" width="240" height="180" alt="'.$skin_folder.'" />';
			echo $select_a_end;
		}
		elseif( file_exists( $skins_path.$skin_folder.'/skinshot.jpg' ) )
		{
			echo $select_a_begin;
			echo '<img src="'.$skins_url.$skin_folder.'/skinshot.jpg" width="240" height="180" alt="'.$skin_folder.'" />';
			echo $select_a_end;
		}
		elseif( file_exists( $skins_path.$skin_folder.'/skinshot.gif' ) )
		{
			echo $select_a_begin;
			echo '<img src="'.$skins_url.$skin_folder.'/skinshot.gif" width="240" height="180" alt="'.$skin_folder.'" />';
			echo $select_a_end;
		}
		else
		{
			echo '<div class="skinshot_noshot">'.T_('No skinshot available for').'</div>';
			echo '<div class="skinshot_name">'.$select_a_begin.$skin_folder.$select_a_end.'</div>';
		}
		echo '</div>';

		//
		echo '<div class="legend">';
		if( isset( $disp_params[ 'function' ] ) )
		{
			echo '<div class="actions">';
			switch( $disp_params[ 'function' ] )
			{
				case 'broken':
					echo '<span class="text-danger">';
					if( !empty($disp_params[ 'msg' ]) )
					{
						echo $disp_params[ 'msg' ];
					}
					else
					{
						echo T_('Broken.');
					}
					echo '</span>';
					break;

				case 'install':
					// Display a link to install the skin
					if( $disp_params[ 'skin_compatible' ] )
					{ // If skin is compatible for current selected type
						if( ! empty( $skin_url ) )
						{
							echo '<a href="'.$skin_url.'" title="'.T_('Install NOW!').'">';
							echo T_('Install NOW!').'</a>';
						}
						if( empty( $kind ) && get_param( 'tab' ) != 'current_skin' )
						{	// Don't display the checkbox on new collection creating form and when we install one skin for the selected collection:
							$skin_name_before = '<label><input type="checkbox" name="skin_folders[]" value="'.$skin_name.'" /> ';
							$skin_name_after = '</label>';
						}
					}
					else
					{ // Inform about skin type is wrong for current case
						if( ! empty( $skin_url ) )
						{
							echo '<a href="'.$skin_url.'" title="'.T_('Install NOW!').'" class="red">';
							echo T_('Wrong Type!').'</a> ';
						}
						echo get_icon( 'help', 'imgtag', array( 'title' => T_('This skin does not fit the blog type you are trying to create.') ) );
					}
					break;

				case 'select':
					// Display a link to preview the skin:
					if( ! empty( $disp_params['function_url'] ) )
					{
						echo '<a href="'.$disp_params['function_url'].'" target="_blank" title="'.T_('Preview blog with this skin in a new window').'">';
						echo T_('Preview').'</a>';
					}
					break;
			}
			echo '</div>';
		}
		echo '<strong>'
				.( empty( $skin_name_before ) ? '<label>' : $skin_name_before )
					.$skin_name
				.( empty( $skin_name_after ) ? '</label>' : $skin_name_after )
			.'</strong>';
		echo '</div>';
		echo '</div>';
	}


	/**
	 * Get definitions for editable params
	 *
	 * @todo this is destined to be overridden by derived Skin classes
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array();

		return $r;
	}


	/**
	 * Get a skin specific param value from current Blog
	 *
	 * @param string Setting name
	 * @return string|array|NULL
	 */
	function get_setting( $parname )
	{
		/**
		 * @var Blog
		 */
		global $Collection, $Blog;

		// Name of the setting in the blog settings:
		$blog_setting_name = 'skin'.$this->ID.'_'.$parname;

		$value = $Blog->get_setting( $blog_setting_name );

		if( ! is_null( $value ) )
		{	// We have a value for this param:
			return $value;
		}

		// Try default values:
		$params = $this->get_param_definitions( NULL );
		if( isset( $params[ $parname ]['defaultvalue'] ) )
		{ // We have a default value:
			return $params[ $parname ]['defaultvalue'] ;
		}
		elseif( isset( $params[ $parname ]['type'] ) &&
		        $params[ $parname ]['type'] == 'checklist' &&
		        ! empty( $params[ $parname ]['options'] ) )
		{ // Get default values for checkbox list:
			$options = array();
			foreach( $params[ $parname ]['options'] as $option )
			{
				if( isset( $option[2] ) )
				{ // Set default value only if it is defined by skin:
					$options[ $option[0] ] = $option[2];
				}
			}
			return $options;
		}

		return NULL;
	}


	/**
	 * Get current skin post navigation setting.
	 * Possible values:
	 *    - NULL - In this case the Blog post navigation setting will be used
	 *    - 'same_category' - to always navigate through the same category in this skin
	 *    - 'same_author' - to always navigate through the same authors in this skin
	 *    - 'same_tag' - to always navigate through the same tags in this skin
	 *
	 * Set this to not NULL only if the same post navigation should be used in every Blog where this skin is used
	 */
	function get_post_navigation()
	{
		return NULL;
	}


	/**
	 * Get current skin path
	 * @return string
	 */
	function get_path()
	{
		global $skins_path;

		return trailing_slash($skins_path.$this->folder);
	}


	/**
	 * Get current skin URL
	 * @return string
	 */
	function get_url()
	{
		global $skins_url;

		return trailing_slash($skins_url.$this->folder);
	}


	/**
	 * Set a skin specific param value for current Blog
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 */
	function set_setting( $parname, $parvalue )
	{
		/**
		 * @var Blog
		 */
		global $Collection, $Blog;

		// Name of the setting in the blog settings:
		$blog_setting_name = 'skin'.$this->ID.'_'.$parname;

		$Blog->set_setting( $blog_setting_name, $parvalue );
	}


	/**
	 * Save skin specific settings for current blgo to DB
	 */
	function dbupdate_settings()
	{
		/**
		 * @var Blog
		 */
		global $Collection, $Blog;

		$Blog->dbupdate();
	}


	/**
	 * Get ready for displaying the skin.
	 *
	 * This method may register some CSS or JS.
	 * The default implementation can register a few common things that you may request in the $features param.
	 * This is where you'd specify you want to use BOOTSTRAP, etc.
	 *
	 * If this doesn't do what you need you may add functions like the following to your skin's display_init():
	 * require_js() , require_css() , add_js_headline()
	 *
	 * @param array of possible features you want to include. If empty, will default to {'b2evo_base', 'style', 'colorbox'} for backwards compatibility.
	 */
	function display_init( /*optional: $features = array() */ )
	{
		global $debug, $Messages, $disp, $UserSettings;

		// We get the optional arg this way for PHP7 comaptibility:
		@list( $features ) = func_get_args();

		if( empty($features) )
		{	// Fall back to v5 default set of features:
			$features = array( 'b2evo_base_css', 'style_css', 'colorbox', 'disp_auto' );
		}

		// "Temporary" patch to at least have disp_auto unless another disp_xxx was specified. Use 'disp_off' to NOT include anuthing.
		if( !preg_grep( '/disp_.*/', $features ) )
		{
			$features[] = 'disp_auto';
		}

		// We're NOT using foreach so that the array can continue to grow during parsing: (see 'disp_auto')
		for( $i = 0; isset($features[$i]); $i++ )
		{
			// Get next feature to include:
			$feature = $features[$i];

			switch( $feature )
			{
				case 'jquery':
					// Include jQuery:
					require_js( '#jquery#', 'blog' );
					break;

				case 'font_awesome':
					// Initialize font-awesome icons and use them as a priority over the glyphicons, @see get_icon()
					init_fontawesome_icons( 'fontawesome-glyphicons', 'blog' );
					break;

				case 'bootstrap':
					// Include Bootstrap:
					require_js( '#bootstrap#', 'blog' );
					require_css( '#bootstrap_css#', 'blog' );
					break;

				case 'bootstrap_theme_css':
					// Include the Bootstrap Theme CSS:
					require_css( '#bootstrap_theme_css#', 'blog' );
					break;

				case 'bootstrap_evo_css':
					// Include the bootstrap-b2evo_base CSS (NEW / v6 style) - Use this when you use Bootstrap:
					if( $debug )
					{	// Use readable CSS:
						// rsc/less/bootstrap-basic_styles.less
						// rsc/less/bootstrap-basic.less
						// rsc/less/bootstrap-blog_base.less
						// rsc/less/bootstrap-item_base.less
						// rsc/less/bootstrap-evoskins.less
						require_css( 'bootstrap-b2evo_base.bundle.css', 'blog' );  // CSS concatenation of the above
					}
					else
					{	// Use minified CSS:
						require_css( 'bootstrap-b2evo_base.bmin.css', 'blog' ); // Concatenation + Minifaction of the above
					}
					break;

				case 'bootstrap_init_tooltips':
					// JS to init Bootstrap tooltips (E.g. on comment form for allowed file extensions):
					add_js_headline( 'jQuery( function () { jQuery( \'[data-toggle="tooltip"]\' ).tooltip() } )' );
					break;

				case 'bootstrap_messages':
					// Initialize $Messages Class to use Bootstrap styles:
					$Messages->set_params( array(
							'class_success'  => 'alert alert-dismissible alert-success fade in',
							'class_warning'  => 'alert alert-dismissible alert-warning fade in',
							'class_error'    => 'alert alert-dismissible alert-danger fade in',
							'class_note'     => 'alert alert-dismissible alert-info fade in',
							'before_message' => '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>',
						) );
					break;

				case 'b2evo_base_css':
					// Include the b2evo_base CSS (OLD / v5 style) - Use this when you DON'T use Bootstrap:
					if( $debug )
					{	// Use readable CSS:
						// require_css( 'basic_styles.css', 'blog' ); // the REAL basic styles
						// require_css( 'basic.css', 'blog' ); // Basic styles
						// require_css( 'blog_base.css', 'blog' ); // Default styles for the blog navigation
						// require_css( 'item_base.css', 'blog' ); // Default styles for the post CONTENT
						// require_css( 'b2evo_base.bundle.css', 'blog' ); // Concatenation of the above
						require_css( 'b2evo_base.bundle.css', 'blog' ); // Concatenation + Minifaction of the above
					}
					else
					{	// Use minified CSS:
						require_css( 'b2evo_base.bmin.css', 'blog' ); // Concatenation + Minifaction of the above
					}
					break;

				case 'style_css':
					// Include the default skin style.css:
					// You should make sure this is called ahead of any custom generated CSS.
					if( $this->use_min_css == false
						|| $debug
						|| ( $this->use_min_css == 'check' && !file_exists(dirname(__FILE__).'/style.min.css' ) ) )
					{	// Use readable CSS:
						$this->require_css( 'style.css' );
					}
					else
					{	// Use minified CSS:
						$this->require_css( 'style.min.css' );
					}
					break;

				case 'colorbox':
					// Colorbox (a lightweight Lightbox alternative) allows to zoom on images and do slideshows with groups of images:
					if( $this->get_setting( 'colorbox' ) )
					{	// This can be enabled by a setting in skins where it may be relevant
						require_js_helper( 'colorbox', 'blog' );
					}
					break;

				case 'disp_auto':
					// Automatically add a disp_xxx for current $disp:
					$features[] = 'disp_'.$disp;
					break;

				case 'disp_single':
					// Specific features for disp=single:
				case 'disp_page':
					// Specific features for disp=page:

					global $Collection, $Blog, $current_User;

					// Used to set rating for a new comment:
					init_ratings_js( 'blog' );

					// Used to vote on an item:
					init_voting_item_js( 'blog' );

					// Used to vote on the comments:
					init_voting_comment_js( 'blog' );

					// Used to display a tooltip to the right of plugin help icon:
					init_plugins_js( 'blog', $this->get_template( 'tooltip_plugin' ) );

					// Used to autocomplete usernames in textarea:
					init_autocomplete_usernames_js( 'blog' );

					if( $Blog->get_setting( 'allow_rating_comment_helpfulness' ) )
					{ // Load jquery UI to animate background color on change comment status or on vote:
						require_js( '#jqueryUI#', 'blog' );
					}

					if( is_logged_in() && $Blog->get_setting( 'use_workflow' ) && $current_User->check_perm( 'blog_can_be_assignee', 'edit', false, $Blog->ID ) )
					{	// Initialize JS to autcomplete user logins and date picker to edit workflow properties:
						init_autocomplete_login_js( 'blog', $this->get_template( 'autocomplete_plugin' ) );
						init_datepicker_js( 'blog' );
					}
					break;

				case 'disp_users':
					// Specific features for disp=users:

					// Used to add new search field "Specific criteria":
					require_js( '#jqueryUI#', 'blog' );
					require_css( '#jqueryUI_css#', 'blog' );

					// Require results.css to display thread query results in a table:
					if( ! in_array( 'bootstrap', $features ) )
					{ // Only for NON-bootstrap skins
						require_css( 'results.css', 'blog' ); // Results/tables styles
					}

					// Require functions.js to show/hide a panel with filters:
					require_js( 'functions.js', 'blog' );

					// Include this file to expand/collapse the filters panel when JavaScript is disabled
					global $inc_path;
					require_once $inc_path.'_filters.inc.php';
					break;

				case 'disp_messages':
					// Specific features for disp=messages:

					// Used to display a tooltip to the right of plugin help icon:
					init_plugins_js( 'blog', $this->get_template( 'tooltip_plugin' ) );

					// Require results.css to display message query results in a table
					if( ! in_array( 'bootstrap', $features ) )
					{ // Only for NON-bootstrap skins
						require_css( 'results.css', 'blog' ); // Results/tables styles
					}

					// Require functions.js to show/hide a panel with filters:
					require_js( 'functions.js', 'blog' );

					// Include this file to expand/collapse the filters panel when JavaScript is disabled
					global $inc_path;
					require_once $inc_path.'_filters.inc.php';
					break;

				case 'disp_contacts':
					// Specific features for disp=contacts:

					// Used for combo box "Add all selected contacts to this group":
					require_js( 'form_extensions.js', 'blog' );

					// Require results.css to display contact query results in a table
					if( ! in_array( 'bootstrap', $features ) )
					{ // Only for NON-bootstrap skins
						require_css( 'results.css', 'blog' ); // Results/tables styles
					}

					// Require functions.js to show/hide a panel with filters:
					require_js( 'functions.js', 'blog' );

					// Include this file to expand/collapse the filters panel when JavaScript is disabled
					global $inc_path;
					require_once $inc_path.'_filters.inc.php';
					break;

				case 'disp_threads':
					// Specific features for disp=threads:

					if( in_array( get_param( 'action' ), array( 'new', 'create', 'preview' ) ) )
					{ // Used to suggest usernames for the field "Recipients":
						init_tokeninput_js( 'blog' );
					}

					// Used to display a tooltip to the right of plugin help icon:
					init_plugins_js( 'blog', $this->get_template( 'tooltip_plugin' ) );

					// Require results.css to display thread query results in a table:
					if( ! in_array( 'bootstrap', $features ) )
					{ // Only for NON-bootstrap skins
						require_css( 'results.css', 'blog' ); // Results/tables styles
					}

					// Require functions.js to show/hide a panel with filters:
					require_js( 'functions.js', 'blog' );

					// Include this file to expand/collapse the filters panel when JavaScript is disabled
					global $inc_path;
					require_once $inc_path.'_filters.inc.php';
					break;

				case 'disp_login':
				case 'disp_access_requires_login':
					// Specific features for disp=login and disp=access_requires_login:

					global $Settings, $Plugins;

					if( can_use_hashed_password() )
					{ // Include JS for client-side password hashing:
						require_js( 'build/sha1_md5.bmin.js', 'blog' );
					}
					break;

				case 'disp_profile':
					// Specific features for disp=profile:

					// Used to add new user fields:
					init_userfields_js( 'blog', $this->get_template( 'tooltip_plugin' ) );

					// Used to crop profile pictures:
					require_js( '#jquery#', 'blog' );
					require_js( '#jcrop#', 'blog' );
					require_css( '#jcrop_css#', 'blog' );

					// Activate bozo validator in order not to miss the changes of the edit forms on page leave:
					if( $UserSettings->get( 'control_form_abortions' ) )
					{	// Only if user wants this:
						require_js( 'bozo_validator.js', 'blog' );
					}
					break;

				case 'disp_avatar':
					// Specific features for disp=avatar:

					// Used to crop profile pictures:
					require_js( '#jquery#', 'blog' );
					require_js( '#jcrop#', 'blog' );
					require_css( '#jcrop_css#', 'blog' );

					// Activate bozo validator in order not to miss the changes of the edit forms on page leave:
					if( $UserSettings->get( 'control_form_abortions' ) )
					{	// Only if user wants this:
						require_js( 'bozo_validator.js', 'blog' );
					}
					break;

				case 'disp_pwdchange':
					// Specific features for disp=pwdchange:
				case 'disp_userprefs':
					// Specific features for disp=userprefs:
				case 'disp_subs':
					// Specific features for disp=subs:

					// Activate bozo validator in order not to miss the changes of the edit forms on page leave:
					if( $UserSettings->get( 'control_form_abortions' ) )
					{	// Only if user wants this:
						require_js( 'bozo_validator.js', 'blog' );
					}
					break;

				case 'disp_edit':
					// Specific features for disp=edit:

					// Require results.css to display attachments as a result table:
					if( ! in_array( 'bootstrap', $features ) )
					{	// Only for NON-bootstrap skins:
						require_css( 'results.css', 'blog' ); // Results/tables styles
					}

					init_tokeninput_js( 'blog' );

					// Used to display a date picker for date form fields:
					init_datepicker_js( 'blog' );

					// Used to display a tooltip to the right of plugin help icon:
					init_plugins_js( 'blog', $this->get_template( 'tooltip_plugin' ) );

					// Used to switch to advanced editing:
					require_js( 'backoffice.js', 'blog' );

					// Used to automatically checks the matching extracat when we select a new main cat:
					require_js( 'extracats.js', 'blog' );

					// Used to autocomplete usernames in textarea:
					init_autocomplete_usernames_js( 'blog' );

					// Activate bozo validator in order not to miss the changes of the edit forms on page leave:
					if( $UserSettings->get( 'control_form_abortions' ) )
					{	// Only if user wants this:
						require_js( 'bozo_validator.js', 'blog' );
					}

					// Require File Uploader js and css:
					require_js( 'multiupload/fileuploader.js', 'blog' );
					require_css( 'fileuploader.css', 'blog' );
					// Load JS files to make the links table sortable:
					require_js( '#jquery#' );
					require_js( 'jquery/jquery.sortable.min.js' );
					break;

				case 'disp_edit_comment':
					// Specific features for disp=edit_comment:

					// Require results.css to display attachments as a result table:
					if( ! in_array( 'bootstrap', $features ) )
					{	// Only for NON-bootstrap skins:
						require_css( 'results.css', 'blog' ); // Results/tables styles
					}

					// Used to set rating for a new comment:
					init_ratings_js( 'blog' );

					// Used to display a date picker for date form fields:
					init_datepicker_js( 'blog' );

					// Used to display a tooltip to the right of plugin help icon:
					init_plugins_js( 'blog', $this->get_template( 'tooltip_plugin' ) );

					// Used to autocomplete usernames in textarea:
					init_autocomplete_usernames_js( 'blog' );
					break;

				case 'disp_useritems':
					// Specific features for disp=useritems:
				case 'disp_usercomments':
					// Specific features for disp=usercomments:

					// Require results.css to display item/comment query results in a table
					if( ! in_array( 'bootstrap', $features ) )
					{	// Only for NON-bootstrap skins:
						require_css( 'results.css', 'blog' ); // Results/tables styles
					}

					// Require functions.js to show/hide a panel with filters
					require_js( 'functions.js', 'blog' );

					// Include this file to expand/collapse the filters panel when JavaScript is disabled
					global $inc_path;
					require_once $inc_path.'_filters.inc.php';
					break;

				default:
					// We no longer want to do this because of 'disp_auto':
					// debug_die( 'This skin has requested an unknown feature: \''.$feature.'\'. Maybe this skin requires a more recent version of b2evolution.' );
			}
		}

		// Load general JS file:
		if( $this->get_api_version() == 6 )
		{ // Bootstrap skin
			require_js( 'build/bootstrap-evo_frontoffice.bmin.js', 'blog' );
		}
		else
		{ // Standard skin
			require_js( 'build/evo_frontoffice.bmin.js', 'blog' );
		}
	}


	/**
	 * Translate a given string, in the Skin's context.
	 *
	 * This means, that the translation is obtained from the Skin's
	 * "locales" folder.
	 *
	 * It uses the global/regular {@link T_()} function as a fallback.
	 *
	 * @param string The string (english), that should be translated
	 * @param string Requested locale ({@link $current_locale} gets used by default)
	 * @return string The translated string.
	 *
	 * @uses T_()
	 * @since 3.2.0 (after beta)
	 */
	function T_( $string, $req_locale = '' )
	{
		global $skins_path;

		if( ( $return = T_( $string, $req_locale, array(
								'ext_transarray' => & $this->_trans,
								'alt_basedir'    => $skins_path.$this->folder,
							) ) ) == $string )
		{	// This skin did not provide a translation - fallback to global T_():
			return T_( $string, $req_locale );
		}

		return $return;
	}


	/**
	 * Translate and escape single quotes.
	 *
	 * This is to be used mainly for Javascript strings.
	 *
	 * @param string String to translate
	 * @param string Locale to use
	 * @return string The translated and escaped string.
	 *
	 * @uses Skin::T_()
	 * @since 3.2.0 (after beta)
	 */
	function TS_( $string, $req_locale = '' )
	{
		return str_replace( "'", "\\'", $this->T_( $string, $req_locale ) );
	}


	/**
	 * Those templates are used for example by the messaging screens.
	 */
	function get_template( $name )
	{
		switch( $this->get_css_framework() )
		{
			case 'bootstrap':
				switch( $name )
				{
					case 'Results':
					case 'compact_results':
						// Results list (Used to view the lists of the users, messages, contacts and etc.):
						$results_template = array(
							'page_url' => '', // All generated links will refer to the current page
							'before' => '<div class="results panel panel-default">',
							'content_start' => '<div id="$prefix$ajax_content">',
							'header_start' => '',
								'header_text' => '<div class="center"><ul class="pagination">'
										.'$prev$$first$$list_prev$$list$$list_next$$last$$next$'
									.'</ul></div>',
								'header_text_single' => '',
							'header_end' => '',
							'head_title' => '<div class="panel-heading fieldset_title"><span class="pull-right">$global_icons$</span><h3 class="panel-title">$title$</h3></div>'."\n",
							'global_icons_class' => 'btn btn-default btn-sm',
							'filters_start'        => '<div class="filters panel-body">',
							'filters_end'          => '</div>',
							'filter_button_class'  => 'btn-sm btn-info',
							'filter_button_before' => '<div class="form-group pull-right">',
							'filter_button_after'  => '</div>',
							'messages_start' => '<div class="messages form-inline">',
							'messages_end' => '</div>',
							'messages_separator' => '<br />',
							'list_start' => '<div class="table_scroll">'."\n"
														 .'<table class="table table-striped table-bordered table-hover table-condensed" cellspacing="0">'."\n",
								'head_start' => "<thead>\n",
									'line_start_head' => '<tr>',  // TODO: fusionner avec colhead_start_first; mettre a jour admin_UI_general; utiliser colspan="$headspan$"
									'colhead_start' => '<th $class_attrib$>',
									'colhead_start_first' => '<th class="firstcol $class$">',
									'colhead_start_last' => '<th class="lastcol $class$">',
									'colhead_end' => "</th>\n",
									'sort_asc_off' => get_icon( 'sort_asc_off' ),
									'sort_asc_on' => get_icon( 'sort_asc_on' ),
									'sort_desc_off' => get_icon( 'sort_desc_off' ),
									'sort_desc_on' => get_icon( 'sort_desc_on' ),
									'basic_sort_off' => '',
									'basic_sort_asc' => get_icon( 'ascending' ),
									'basic_sort_desc' => get_icon( 'descending' ),
								'head_end' => "</thead>\n\n",
								'tfoot_start' => "<tfoot>\n",
								'tfoot_end' => "</tfoot>\n\n",
								'body_start' => "<tbody>\n",
									'line_start' => '<tr class="even">'."\n",
									'line_start_odd' => '<tr class="odd">'."\n",
									'line_start_last' => '<tr class="even lastline">'."\n",
									'line_start_odd_last' => '<tr class="odd lastline">'."\n",
										'col_start' => '<td $class_attrib$ $colspan_attrib$>',
										'col_start_first' => '<td class="firstcol $class$" $colspan_attrib$>',
										'col_start_last' => '<td class="lastcol $class$" $colspan_attrib$>',
										'col_end' => "</td>\n",
									'line_end' => "</tr>\n\n",
									'grp_line_start' => '<tr class="group">'."\n",
									'grp_line_start_odd' => '<tr class="odd">'."\n",
									'grp_line_start_last' => '<tr class="lastline">'."\n",
									'grp_line_start_odd_last' => '<tr class="odd lastline">'."\n",
												'grp_col_start' => '<td $class_attrib$ $colspan_attrib$>',
												'grp_col_start_first' => '<td class="firstcol $class$" $colspan_attrib$>',
												'grp_col_start_last' => '<td class="lastcol $class$" $colspan_attrib$>',
										'grp_col_end' => "</td>\n",
									'grp_line_end' => "</tr>\n\n",
								'body_end' => "</tbody>\n\n",
								'total_line_start' => '<tr class="total">'."\n",
									'total_col_start' => '<td $class_attrib$>',
									'total_col_start_first' => '<td class="firstcol $class$">',
									'total_col_start_last' => '<td class="lastcol $class$">',
									'total_col_end' => "</td>\n",
								'total_line_end' => "</tr>\n\n",
							'list_end' => "</table></div>\n\n",
							'footer_start' => '',
							'footer_text' => '<div class="center"><ul class="pagination">'
									.'$prev$$first$$list_prev$$list$$list_next$$last$$next$'
								.'</ul></div><div class="center">$page_size$</div>'
																/* T_('Page $scroll_list$ out of $total_pages$   $prev$ | $next$<br />'. */
																/* '<strong>$total_pages$ Pages</strong> : $prev$ $list$ $next$' */
																/* .' <br />$first$  $list_prev$  $list$  $list_next$  $last$ :: $prev$ | $next$') */,
							'footer_text_single' => '<div class="center">$page_size$</div>',
							'footer_text_no_limit' => '', // Text if theres no LIMIT and therefor only one page anyway
								'page_current_template' => '<span>$page_num$</span>',
								'page_item_before' => '<li>',
								'page_item_after' => '</li>',
								'page_item_current_before' => '<li class="active">',
								'page_item_current_after'  => '</li>',
								'prev_text' => T_('Previous'),
								'next_text' => T_('Next'),
								'no_prev_text' => '',
								'no_next_text' => '',
								'list_prev_text' => T_('...'),
								'list_next_text' => T_('...'),
								'list_span' => 11,
								'scroll_list_range' => 5,
							'footer_end' => "\n\n",
							'no_results_start' => '<div class="panel-footer">'."\n",
							'no_results_end'   => '$no_results$</div>'."\n\n",
							'content_end' => '</div>',
							'after' => '</div>',
							'sort_type' => 'basic'
						);
						if( $name == 'compact_results' )
						{	// Use a little different template for compact results table:
							$results_template = array_merge( $results_template, array(
									'before' => '<div class="results">',
									'head_title' => '',
									'no_results_start' => '<div class="table_scroll">'."\n"
																				.'<table class="table table-striped table-bordered table-hover table-condensed" cellspacing="0"><tbody>'."\n",
									'no_results_end'   => '<tr class="lastline noresults"><td class="firstcol lastcol">$no_results$</td></tr>'
																				.'</tbody></table></div>'."\n\n",
								) );
						}
						return $results_template;

					case 'blockspan_form':
						// Form settings for filter area:
						return array(
							'layout'         => 'blockspan',
							'formclass'      => 'form-inline',
							'formstart'      => '',
							'formend'        => '',
							'title_fmt'      => '$title$'."\n",
							'no_title_fmt'   => '',
							'fieldset_begin' => '<fieldset $fieldset_attribs$>'."\n"
																		.'<legend $title_attribs$>$fieldset_title$</legend>'."\n",
							'fieldset_end'   => '</fieldset>'."\n",
							'fieldstart'     => '<div class="form-group form-group-sm" $ID$>'."\n",
							'fieldend'       => "</div>\n\n",
							'labelclass'     => 'control-label',
							'labelstart'     => '',
							'labelend'       => "\n",
							'labelempty'     => '<label></label>',
							'inputstart'     => '',
							'inputend'       => "\n",
							'infostart'      => '<div class="form-control-static">',
							'infoend'        => "</div>\n",
							'buttonsstart'   => '<div class="form-group form-group-sm">',
							'buttonsend'     => "</div>\n\n",
							'customstart'    => '<div class="custom_content">',
							'customend'      => "</div>\n",
							'note_format'    => ' <span class="help-inline">%s</span>',
							// Additional params depending on field type:
							// - checkbox
							'fieldstart_checkbox'    => '<div class="form-group form-group-sm checkbox" $ID$>'."\n",
							'fieldend_checkbox'      => "</div>\n\n",
							'inputclass_checkbox'    => '',
							'inputstart_checkbox'    => '',
							'inputend_checkbox'      => "\n",
							'checkbox_newline_start' => '',
							'checkbox_newline_end'   => "\n",
							// - radio
							'inputclass_radio'       => '',
							'radio_label_format'     => '$radio_option_label$',
							'radio_newline_start'    => '',
							'radio_newline_end'      => "\n",
							'radio_oneline_start'    => '',
							'radio_oneline_end'      => "\n",
						);

					case 'compact_form':
					case 'Form':
						// Default Form settings (Used for any form on front-office):
						return array(
							'layout'         => 'fieldset',
							'formclass'      => 'form-horizontal',
							'formstart'      => '',
							'formend'        => '',
							'title_fmt'      => '<span style="float:right">$global_icons$</span><h2>$title$</h2>'."\n",
							'no_title_fmt'   => '<span style="float:right">$global_icons$</span>'."\n",
							'fieldset_begin' => '<div class="fieldset_wrapper $class$" id="fieldset_wrapper_$id$"><fieldset $fieldset_attribs$><div class="panel panel-default">'."\n"
																	.'<legend class="panel-heading" $title_attribs$>$fieldset_title$</legend><div class="panel-body $class$">'."\n",
							'fieldset_end'   => '</div></div></fieldset></div>'."\n",
							'fieldstart'     => '<div class="form-group" $ID$>'."\n",
							'fieldend'       => "</div>\n\n",
							'labelclass'     => 'control-label col-sm-3',
							'labelstart'     => '',
							'labelend'       => "\n",
							'labelempty'     => '<label class="control-label col-sm-3"></label>',
							'inputstart'     => '<div class="controls col-sm-9">',
							'inputend'       => "</div>\n",
							'infostart'      => '<div class="controls col-sm-9"><div class="form-control-static">',
							'infoend'        => "</div></div>\n",
							'buttonsstart'   => '<div class="form-group"><div class="control-buttons col-sm-offset-3 col-sm-9">',
							'buttonsend'     => "</div></div>\n\n",
							'customstart'    => '<div class="custom_content">',
							'customend'      => "</div>\n",
							'note_format'    => ' <span class="help-inline">%s</span>',
							// Additional params depending on field type:
							// - checkbox
							'inputclass_checkbox'    => '',
							'inputstart_checkbox'    => '<div class="controls col-sm-9"><div class="checkbox"><label>',
							'inputend_checkbox'      => "</label></div></div>\n",
							'checkbox_newline_start' => '<div class="checkbox">',
							'checkbox_newline_end'   => "</div>\n",
							// - radio
							'fieldstart_radio'       => '<div class="form-group radio-group" $ID$>'."\n",
							'fieldend_radio'         => "</div>\n\n",
							'inputclass_radio'       => '',
							'radio_label_format'     => '$radio_option_label$',
							'radio_newline_start'    => '<div class="radio"><label>',
							'radio_newline_end'      => "</label></div>\n",
							'radio_oneline_start'    => '<label class="radio-inline">',
							'radio_oneline_end'      => "</label>\n",
						);

					case 'linespan_form':
						// Linespan form:
						return array(
							'layout'         => 'linespan',
							'formclass'      => 'form-horizontal',
							'formstart'      => '',
							'formend'        => '',
							'title_fmt'      => '<span style="float:right">$global_icons$</span><h2>$title$</h2>'."\n",
							'no_title_fmt'   => '<span style="float:right">$global_icons$</span>'."\n",
							'fieldset_begin' => '<div class="fieldset_wrapper $class$" id="fieldset_wrapper_$id$"><fieldset $fieldset_attribs$><div class="panel panel-default">'."\n"
																	.'<legend class="panel-heading" $title_attribs$>$fieldset_title$</legend><div class="panel-body $class$">'."\n",
							'fieldset_end'   => '</div></div></fieldset></div>'."\n",
							'fieldstart'     => '<div class="form-group" $ID$>'."\n",
							'fieldend'       => "</div>\n\n",
							'labelclass'     => '',
							'labelstart'     => '',
							'labelend'       => "\n",
							'labelempty'     => '',
							'inputstart'     => '<div class="controls">',
							'inputend'       => "</div>\n",
							'infostart'      => '<div class="controls"><div class="form-control-static">',
							'infoend'        => "</div></div>\n",
							'buttonsstart'   => '<div class="form-group"><div class="control-buttons">',
							'buttonsend'     => "</div></div>\n\n",
							'customstart'    => '<div class="custom_content">',
							'customend'      => "</div>\n",
							'note_format'    => ' <span class="help-inline">%s</span>',
							// Additional params depending on field type:
							// - checkbox
							'inputclass_checkbox'    => '',
							'inputstart_checkbox'    => '<div class="controls"><div class="checkbox"><label>',
							'inputend_checkbox'      => "</label></div></div>\n",
							'checkbox_newline_start' => '<div class="checkbox">',
							'checkbox_newline_end'   => "</div>\n",
							'checkbox_basic_start'   => '<div class="checkbox"><label>',
							'checkbox_basic_end'     => "</label></div>\n",
							// - radio
							'fieldstart_radio'       => '',
							'fieldend_radio'         => '',
							'inputstart_radio'       => '<div class="controls">',
							'inputend_radio'         => "</div>\n",
							'inputclass_radio'       => '',
							'radio_label_format'     => '$radio_option_label$',
							'radio_newline_start'    => '<div class="radio"><label>',
							'radio_newline_end'      => "</label></div>\n",
							'radio_oneline_start'    => '<label class="radio-inline">',
							'radio_oneline_end'      => "</label>\n",
						);

					case 'fixed_form':
						// Form with fixed label width (Used for form on disp=user):
						return array(
							'layout'         => 'fieldset',
							'formclass'      => 'form-horizontal',
							'formstart'      => '',
							'formend'        => '',
							'title_fmt'      => '<span style="float:right">$global_icons$</span><h2>$title$</h2>'."\n",
							'no_title_fmt'   => '<span style="float:right">$global_icons$</span>'."\n",
							'fieldset_begin' => '<div class="fieldset_wrapper $class$" id="fieldset_wrapper_$id$"><fieldset $fieldset_attribs$><div class="panel panel-default">'."\n"
																	.'<legend class="panel-heading" $title_attribs$>$fieldset_title$</legend><div class="panel-body $class$">'."\n",
							'fieldset_end'   => '</div></div></fieldset></div>'."\n",
							'fieldstart'     => '<div class="form-group fixedform-group" $ID$>'."\n",
							'fieldend'       => "</div>\n\n",
							'labelclass'     => 'control-label fixedform-label',
							'labelstart'     => '',
							'labelend'       => "\n",
							'labelempty'     => '<label class="control-label fixedform-label"></label>',
							'inputstart'     => '<div class="controls fixedform-controls">',
							'inputend'       => "</div>\n",
							'infostart'      => '<div class="controls fixedform-controls"><div class="form-control-static">',
							'infoend'        => "</div></div>\n",
							'buttonsstart'   => '<div class="form-group"><div class="control-buttons fixedform-controls">',
							'buttonsend'     => "</div></div>\n\n",
							'customstart'    => '<div class="custom_content">',
							'customend'      => "</div>\n",
							'note_format'    => ' <span class="help-inline">%s</span>',
							// Additional params depending on field type:
							// - checkbox
							'inputclass_checkbox'    => '',
							'inputstart_checkbox'    => '<div class="controls fixedform-controls"><div class="checkbox"><label>',
							'inputend_checkbox'      => "</label></div></div>\n",
							'checkbox_newline_start' => '<div class="checkbox">',
							'checkbox_newline_end'   => "</div>\n",
							// - radio
							'fieldstart_radio'       => '<div class="form-group radio-group" $ID$>'."\n",
							'fieldend_radio'         => "</div>\n\n",
							'inputclass_radio'       => '',
							'radio_label_format'     => '$radio_option_label$',
							'radio_newline_start'    => '<div class="radio"><label>',
							'radio_newline_end'      => "</label></div>\n",
							'radio_oneline_start'    => '<label class="radio-inline">',
							'radio_oneline_end'      => "</label>\n",
						);

					case 'user_navigation':
						// The Prev/Next links of users (Used on disp=user to navigate between users):
						return array(
							'block_start'  => '<ul class="pager">',
							'prev_start'   => '<li class="previous">',
							'prev_end'     => '</li>',
							'prev_no_user' => '',
							'back_start'   => '<li>',
							'back_end'     => '</li>',
							'next_start'   => '<li class="next">',
							'next_end'     => '</li>',
							'next_no_user' => '',
							'block_end'    => '</ul>',
						);

					case 'button_classes':
						// Button classes (Used to initialize classes for action buttons like buttons to spam vote, or edit an intro post):
						return array(
							'button'       => 'btn btn-default btn-xs',
							'button_red'   => 'btn-danger',
							'button_green' => 'btn-success',
							'text'         => 'btn btn-default btn-xs',
							'group'        => 'btn-group',
						);

					case 'tooltip_plugin':
						// Plugin name for tooltips: 'bubbletip' or 'popover'
						// We should use 'popover' tooltip plugin for bootstrap skins
						// This tooltips appear on mouse over user logins or on plugin help icons
						return 'popover';

					case 'autocomplete_plugin':
						// Plugin name to autocomplete user logins: 'hintbox', 'typeahead'
						return 'typeahead';

					case 'plugin_template':
						// Template for plugins:
						return array(
								// This template is used to build a plugin toolbar with action buttons above edit item/comment area:
								'toolbar_before'       => '<div class="btn-toolbar $toolbar_class$" role="toolbar">',
								'toolbar_after'        => '</div>',
								'toolbar_title_before' => '<div class="btn-toolbar-title">',
								'toolbar_title_after'  => '</div>',
								'toolbar_group_before' => '<div class="btn-group btn-group-xs" role="group">',
								'toolbar_group_after'  => '</div>',
								'toolbar_button_class' => 'btn btn-default',
							);

					case 'modal_window_js_func':
						// JavaScript function to initialize Modal windows, @see echo_user_ajaxwindow_js()
						return 'echo_modalwindow_js_bootstrap';
				}
				break;
		}

		// Use default template:
		switch( $name )
		{
			case 'Results':
			case 'compact_results':
				// Results list:
				return array(
					'page_url' => '', // All generated links will refer to the current page
					'before' => '<div class="results">',
					'content_start' => '<div id="$prefix$ajax_content">',
					'header_start' => '<div class="results_nav">',
						'header_text' => '<strong>'.T_('Pages').'</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$',
						'header_text_single' => '',
					'header_end' => '</div>',
					'head_title' => '<div class="title"><span style="float:right">$global_icons$</span>$title$</div>'
							            ."\n",
					'filters_start' => '<div class="filters">',
					'filters_end' => '</div>',
					'messages_start' => '<div class="messages">',
					'messages_end' => '</div>',
					'messages_separator' => '<br />',
					'list_start' => '<div class="table_scroll">'."\n"
					               .'<table class="grouped" cellspacing="0">'."\n",
						'head_start' => '<thead>'."\n",
							'line_start_head' => '<tr>',  // TODO: fusionner avec colhead_start_first; mettre a jour admin_UI_general; utiliser colspan="$headspan$"
							'colhead_start' => '<th $class_attrib$ $title_attrib$>',
							'colhead_start_first' => '<th class="firstcol $class$" $title_attrib$>',
							'colhead_start_last' => '<th class="lastcol $class$" $title_attrib$>',
							'colhead_end' => "</th>\n",
							'sort_asc_off' => get_icon( 'sort_asc_off' ),
							'sort_asc_on' => get_icon( 'sort_asc_on' ),
							'sort_desc_off' => get_icon( 'sort_desc_off' ),
							'sort_desc_on' => get_icon( 'sort_desc_on' ),
							'basic_sort_off' => '',
							'basic_sort_asc' => get_icon( 'ascending' ),
							'basic_sort_desc' => get_icon( 'descending' ),
						'head_end' => "</thead>\n\n",
						'tfoot_start' => "<tfoot>\n",
						'tfoot_end' => "</tfoot>\n\n",
						'body_start' => "<tbody>\n",
							'line_start' => '<tr class="even">'."\n",
							'line_start_odd' => '<tr class="odd">'."\n",
							'line_start_last' => '<tr class="even lastline">'."\n",
							'line_start_odd_last' => '<tr class="odd lastline">'."\n",
								'col_start' => '<td $class_attrib$ $colspan_attrib$>',
								'col_start_first' => '<td class="firstcol $class$" $colspan_attrib$>',
								'col_start_last' => '<td class="lastcol $class$" $colspan_attrib$>',
								'col_end' => "</td>\n",
							'line_end' => "</tr>\n\n",
							'grp_line_start' => '<tr class="group">'."\n",
							'grp_line_start_odd' => '<tr class="odd">'."\n",
							'grp_line_start_last' => '<tr class="lastline">'."\n",
							'grp_line_start_odd_last' => '<tr class="odd lastline">'."\n",
										'grp_col_start' => '<td $class_attrib$ $colspan_attrib$>',
										'grp_col_start_first' => '<td class="firstcol $class$" $colspan_attrib$>',
										'grp_col_start_last' => '<td class="lastcol $class$" $colspan_attrib$>',
								'grp_col_end' => "</td>\n",
							'grp_line_end' => "</tr>\n\n",
						'body_end' => "</tbody>\n\n",
						'total_line_start' => '<tr class="total">'."\n",
							'total_col_start' => '<td $class_attrib$>',
							'total_col_start_first' => '<td class="firstcol $class$">',
							'total_col_start_last' => '<td class="lastcol $class$">',
							'total_col_end' => "</td>\n",
						'total_line_end' => "</tr>\n\n",
					'list_end' => "</table></div>\n\n",
					'footer_start' => '<div class="results_nav nav_footer">',
					'footer_text' => '<strong>'.T_('Pages').'</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$'
					                  /* T_('Page $scroll_list$ out of $total_pages$   $prev$ | $next$<br />'. */
					                  /* '<strong>$total_pages$ Pages</strong> : $prev$ $list$ $next$' */
					                  /* .' <br />$first$  $list_prev$  $list$  $list_next$  $last$ :: $prev$ | $next$') */,
					'footer_text_single' => '',
					'footer_text_no_limit' => '', // Text if theres no LIMIT and therefor only one page anyway
						'prev_text' => T_('Previous'),
						'next_text' => T_('Next'),
						'no_prev_text' => '',
						'no_next_text' => '',
						'list_prev_text' => T_('...'),
						'list_next_text' => T_('...'),
						'list_span' => 11,
						'scroll_list_range' => 5,
					'footer_end' => "</div>\n\n",
					'no_results_start' => '<table class="grouped" cellspacing="0">'."\n",
					'no_results_end'   => '<tr class="lastline"><td class="firstcol lastcol">$no_results$</td></tr>'
					                      .'</table>'."\n\n",
				'content_end' => '</div>',
				'after' => '</div>',
				'sort_type' => 'basic'
				);

			case 'messages':
				return array(
					'show_only_date' => true,
					'show_columns' => 'login',
				);

			case 'blockspan_form':
				// blockspan Form settings:
				return array(
					'layout' => 'blockspan',		// Temporary dirty hack
					'formstart' => '',
					'title_fmt' => '$title$'."\n", // TODO: icons
					'no_title_fmt' => '',          //           "
					'no_title_no_icons_fmt' => '',          //           "
					'fieldset_begin' => '<fieldset $fieldset_attribs$>'."\n"
															.'<legend $title_attribs$>$fieldset_title$</legend>'."\n",
					'fieldset_end' => '</fieldset>'."\n",
					'fieldstart' => '<span class="block" $ID$>',
					'labelclass' => '',
					'labelstart' => '',
					'labelend' => "\n",
					'labelempty' => '',
					'inputstart' => '',
					'inputend' => "\n",
					'infostart' => '',
					'infoend' => "\n",
					'fieldend' => '</span>'.get_icon( 'pixel' )."\n",
					'buttonsstart' => '',
					'buttonsend' => "\n",
					'customstart' => '',
					'customend' => "\n",
					'note_format' => ' <span class="notes">%s</span>',
					'formend' => '',
				);

			case 'cat_array_mode':
				// What category level use to display the items on disp=posts:
				//   - 'children' - Get items from current category and from all its sub-categories recirsively
				//   - 'parent' - Get items ONLY from current category WITHOUT sub-categories
				return 'children';

			case 'autocomplete_plugin':
				// Plugin name to autocomplete user logins: 'hintbox', 'typeahead'
				return 'hintbox';
		}

		return array();
	}


	/**
	 * Memorize that a specific css that file will be required by the current page.
	 * @see require_css() for full documentation,
	 * this function is used to add unique version number for each skin
	 *
	 * @param string Name of CSS file relative to <base> tag (current skin folder)
	 */
	function require_css( $css_file )
	{
		global $app_version_long;
		require_css( $css_file, 'relative', NULL, NULL, $this->folder.'+'.$this->version.'+'.$app_version_long );
	}


	/**
	 * Memorize that a specific javascript file will be required by the current page.
	 * @see require_js() for full documentation,
	 * this function is used to add unique version number for each skin
	 *
	 * @param string Name of JavaScript file relative to <base> tag (current skin folder)
	 */
	function require_js( $js_file )
	{
		global $app_version_long;
		require_js( $js_file, 'relative', false, false, $this->folder.'+'.$this->version.'+'.$app_version_long );
	}
}

?>