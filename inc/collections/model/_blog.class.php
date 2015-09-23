<?php
/**
 * This file implements the Blog class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005 by Jason Edgecombe.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Blog
 *
 * Blog object with params
 *
 * @package evocore
 */
class Blog extends DataObject
{
	/**
	 * Short name for use in navigation menus
	 * @var string
	 */
	var $shortname;

	/**
	 * Complete name
	 * @var string
	 */
	var $name;

	/**
	 * Tagline to be displayed on template
	 * @var string
	 */
	var $tagline;

	var $shortdesc; // description
	var $longdesc;

	/**
	 * @var integer
	 */
	var $owner_user_ID;

	/**
	 * Lazy filled
	 * @var User
	 * @see get_owner_User()
	 * @access protected
	 */
	var $owner_User = NULL;

	var $advanced_perms = 0;

	var $locale;
	var $order;
	var $access_type;

	/*
	 * ?> TODO: we should have an extra DB column that either defines type of blog_siteurl
	 * OR split blog_siteurl into blog_siteurl_abs and blog_siteurl_rel (where blog_siteurl_rel could be "blog_sitepath")
	 */
	var $siteurl;
	var $stub;     // stub file (can be empty/virtual)
	var $urlname;  // used to identify blog in URLs
	var $links_blog_ID = 0;	// DEPRECATED
	var $notes;
	var $keywords;
	var $allowtrackbacks = 0;
	var $allowblogcss = 0;
	var $allowusercss = 0;
	var $in_bloglist = 'public';
	var $media_location = 'default';
	var $media_subdir = '';
	var $media_fullpath = '';
	var $media_url = '';


	/**
	 * The URL to the basepath of that blog.
	 * This is supposed to be the same as $baseurl but localized to the domain of the blog/
	 *
	 * Lazy filled by get_basepath_url()
	 *
	 * @var string
	 */
	var $basepath_url;

	/**
	 * Additional settings for the collection.  lazy filled.
 	 *
	 * @see Blog::get_setting()
	 * @see Blog::set_setting()
	 * @see Blog::load_CollectionSettings()
	 * Any non vital params should go into there (this includes many of the above).
	 *
	 * @var CollectionSettings
	 */
	var $CollectionSettings;


	/**
	 * Lazy filled
	 *
	 * @var integer
	 */
	var $default_cat_ID;

	/**
	 * @var string Type of blog ( 'std', 'photo', 'group', 'forum', 'manual' )
	 */
	var $type;

	/**
	 * @var boolean TRUE if blog is favorite
	 */
	var $favorite = 0;


	/**
	 * Constructor
	 *
	 * @param object DB row
	 */
	function Blog( $db_row = NULL )
	{
		global $Timer;

		$Timer->start( 'Blog constructor' );

		// Call parent constructor:
		parent::DataObject( 'T_blogs', 'blog_', 'blog_ID' );

		if( $db_row == NULL )
		{
			global $default_locale;
			// echo 'Creating blank blog';
			$this->owner_user_ID = 1; // DB default
			$this->set( 'locale', $default_locale );
			$this->set( 'access_type', 'extrapath' );
		}
		else
		{
			/**
			 * NOTE: Check each new added or renamed field by function isset() below,
			 *       Otherwise it may create issues during upgrade process from old to new DB:
			 */
			$this->ID = $db_row->blog_ID;
			$this->shortname = $db_row->blog_shortname;
			$this->name = $db_row->blog_name;
			$this->owner_user_ID = $db_row->blog_owner_user_ID;
			$this->advanced_perms = $db_row->blog_advanced_perms;
			$this->tagline = $db_row->blog_tagline;
			$this->shortdesc = isset( $db_row->blog_shortdesc ) ? $db_row->blog_shortdesc : '';	// description
			$this->longdesc = $db_row->blog_longdesc;
			$this->locale = $db_row->blog_locale;
			$this->access_type = $db_row->blog_access_type;
			$this->siteurl = $db_row->blog_siteurl;
			$this->urlname = $db_row->blog_urlname;
			$this->links_blog_ID = $db_row->blog_links_blog_ID; // DEPRECATED
			$this->notes = $db_row->blog_notes;
			$this->keywords = $db_row->blog_keywords;
			$this->allowtrackbacks = $db_row->blog_allowtrackbacks;
			$this->allowblogcss = $db_row->blog_allowblogcss;
			$this->allowusercss = $db_row->blog_allowusercss;
			$this->in_bloglist = $db_row->blog_in_bloglist;
			$this->media_location = $db_row->blog_media_location;
			$this->media_subdir = $db_row->blog_media_subdir;
			$this->media_fullpath = $db_row->blog_media_fullpath;
			$this->media_url = $db_row->blog_media_url;
			$this->type = isset( $db_row->blog_type ) ? $db_row->blog_type : 'std';
			$this->order = isset( $db_row->blog_order ) ? $db_row->blog_order : 0;
			$this->favorite = isset( $db_row->blog_favorite ) ? $db_row->blog_favorite : 0;
		}

		$Timer->pause( 'Blog constructor' );
	}


	/**
	 * Get delete cascade settings
	 *
	 * @return array
	 */
	static function get_delete_cascades()
	{
		return array(
				array( 'table'=>'T_coll_settings', 'fk'=>'cset_coll_ID', 'msg'=>T_('%d blog settings') ),
				array( 'table'=>'T_coll_user_perms', 'fk'=>'bloguser_blog_ID', 'msg'=>T_('%d user permission definitions') ),
				array( 'table'=>'T_coll_group_perms', 'fk'=>'bloggroup_blog_ID', 'msg'=>T_('%d group permission definitions') ),
				array( 'table'=>'T_subscriptions', 'fk'=>'sub_coll_ID', 'msg'=>T_('%d subscriptions') ),
				array( 'table'=>'T_widget', 'fk'=>'wi_coll_ID', 'msg'=>T_('%d widgets') ),
				array( 'table'=>'T_hitlog', 'fk'=>'hit_coll_ID', 'msg'=>T_('%d hits') ),
				array( 'table'=>'T_categories', 'fk'=>'cat_blog_ID', 'msg'=>T_('%d related categories with all of their content recursively'),
						'class'=>'Chapter', 'class_path'=>'chapters/model/_chapter.class.php' ),
				array( 'table'=>'T_files', 'fk'=>'file_root_ID', 'and_condition'=>'file_root_type = "collection"', 'msg'=>T_('%d files in this blog file root'),
						'class'=>'File', 'class_path'=>'files/model/_file.class.php' ),
			);
	}


	/**
	 * Compare two Blog based on the common blog order setting
	 *
	 * @param Blog A
	 * @param Blog B
	 * @return number -1 if A < B, 1 if A > B, 0 if A == B
	 */
	static function compare_blogs( $a_Blog, $b_Blog )
	{
		global $Settings;

		if( $a_Blog->ID == $b_Blog->ID )
		{
			return 0;
		}

		$order_by = $Settings->get('blogs_order_by');
		$order_dir = $Settings->get('blogs_order_dir');

		if( $order_by == 'RAND' )
		{ // In case of Random order we consider every blog as equal
			return 0;
		}

		$blog_a_value = $a_Blog->get( $order_by );
		$blog_b_value = $b_Blog->get( $order_by );
		if( $blog_a_value == $blog_b_value )
		{ // The compare fields are equal sort based on the ID
			$blog_a_value = $a_Blog->ID;
			$blog_b_value = $b_Blog->ID;
		}
		$result = is_numeric( $blog_a_value ) ? ( $blog_a_value < $blog_b_value ? -1 : 1 ) : strcmp( $blog_a_value, $blog_b_value );

		if( $order_dir == 'DESC' )
		{ // Change the order direction
			$result = $result * (-1);
		}

		return $result;
	}


	/**
	 * Initialize blog setting by kind
	 *
	 * @param string Kind: 'main', 'std', 'photo', 'group', 'forum', 'manual'
	 * @param string Name
	 * @param string Short name
	 * @param string Url/slug
	 */
	function init_by_kind( $kind, $name = NULL, $shortname = NULL, $urlname = NULL )
	{
		// Allow email subscriptions by default:
		$this->set_setting( 'allow_subscriptions', '1' );
		$this->set_setting( 'allow_item_subscriptions', '1' );

		switch( $kind )
		{
			case 'main':
				$this->set( 'type', 'main' );
				$this->set( 'name', empty( $name ) ? T_('Homepage Title') : $name );
				$this->set( 'shortname', empty( $shortname ) ? T_('Home') : $shortname );
				$this->set( 'urlname', empty( $urlname ) ? 'main' : $urlname );
				$this->set_setting( 'front_disp', 'front' );
				$this->set_setting( 'aggregate_coll_IDs', '*' );
				$this->set_setting( 'in_skin_login', '1' );
				break;

			case 'photo':
				$this->set( 'type', 'photo' );
				$this->set( 'name', empty($name) ? T_('My photoblog') : $name );
				$this->set( 'shortname', empty($shortname) ? T_('Photoblog') : $shortname );
				$this->set( 'urlname', empty($urlname) ? 'photo' : $urlname );
				$this->set_setting( 'posts_per_page', 12 );
				$this->set_setting( 'archive_mode', 'postbypost' );
				$this->set_setting( 'front_disp', 'posts' );
				break;

			case 'group':
				$this->set( 'type', 'group' );
				$this->set( 'name', empty($name) ? T_('Our blog') : $name );
				$this->set( 'shortname', empty($shortname) ? T_('Group') : $shortname );
				$this->set( 'urlname', empty($urlname) ? 'group' : $urlname );
				$this->set_setting( 'use_workflow', 1 );
				break;

			case 'forum':
				$this->set( 'type', 'forum' );
				$this->set( 'name', empty($name) ? T_('My forum') : $name );
				$this->set( 'shortname', empty($shortname) ? T_('Forum') : $shortname );
				$this->set( 'urlname', empty($urlname) ? 'forum' : $urlname );
				$this->set( 'advanced_perms', 1 );
				$this->set_setting( 'post_navigation', 'same_category' );
				$this->set_setting( 'allow_comments', 'registered' );
				$this->set_setting( 'in_skin_editing', '1' );
				$this->set_setting( 'posts_per_page', 30 );
				$this->set_setting( 'allow_html_comment', 0 );
				$this->set_setting( 'orderby', 'last_touched_ts' );
				$this->set_setting( 'orderdir', 'DESC' );
				$this->set_setting( 'enable_goto_blog', 'post' );
				$this->set_setting( 'front_disp', 'front' );
				$this->set_setting( 'track_unread_content', 1 );
				$this->set_setting( 'allow_rating_comment_helpfulness', 1 );
				$this->set_setting( 'category_ordering', 'manual' );

				// Try to find post type "Forum Topic" in DB
				global $DB;
				$forum_topic_type_ID = $DB->get_var( 'SELECT ityp_ID
					 FROM T_items__type
					WHERE ityp_ID = 200
					  AND ityp_name = "Forum Topic"' );
				if( $forum_topic_type_ID )
				{ // Set default post type as "Forum Topic"
					$this->set_setting( 'default_post_type', $forum_topic_type_ID );
				}
				break;

			case 'manual':
				$this->set( 'type', 'manual' );
				$this->set( 'name', empty($name) ? T_('Manual') : $name );
				$this->set( 'shortname', empty($shortname) ? T_('Manual') : $shortname );
				$this->set( 'urlname', empty($urlname) ? 'manual' : $urlname );
				$this->set_setting( 'post_navigation', 'same_category' );
				$this->set_setting( 'single_links', 'chapters' );
				$this->set_setting( 'enable_goto_blog', 'post' );
				$this->set_setting( 'front_disp', 'front' );
				$this->set_setting( 'category_ordering', 'manual' );
				$this->set_setting( 'main_content', 'excerpt' );

				// Try to find post type "Manual Page" in DB
				global $DB;
				$manual_page_type_ID = $DB->get_var( 'SELECT ityp_ID
					 FROM T_items__type
					WHERE ityp_ID = 100
					  AND ityp_name = "Manual Page"' );
				if( $manual_page_type_ID )
				{ // Set default post type as "Manual Page"
					$this->set_setting( 'default_post_type', $manual_page_type_ID );
				}
				break;

			case 'std':
			default:
				$this->set( 'type', 'std' );
				$this->set( 'name', empty($name) ? T_('My weblog') : $name );
				$this->set( 'shortname', empty($shortname) ? T_('Blog') : $shortname );
				$this->set( 'urlname', empty($urlname) ? 'blog' : $urlname );
				break;
		}

		if( empty($name) && empty($shortname) && empty($urlname) )
		{	// Not in installation mode, init custom collection kinds.
			global $Plugins;

			// Defines blog settings by its kind.
			$Plugins->trigger_event( 'InitCollectionKinds', array(
							'Blog' => & $this,
							'kind' => & $kind,
						) );
		}
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @param array groups of params to load
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request( $groups = array() )
	{
		global $Messages, $default_locale, $DB;

		/**
		 * @var User
		 */
		global $current_User;

		// Load collection settings and clear update cascade array
		$this->load_CollectionSettings();

		if( param( 'blog_name', 'string', NULL ) !== NULL )
		{ // General params:
			$this->set_from_Request( 'name' );
			$this->set( 'shortname', param( 'blog_shortname', 'string', true ) );

			// Language / locale:
			if( param( 'blog_locale', 'string', NULL ) !== NULL )
			{ // These settings can be hidden when only one locale is enaled in the system
				$this->set_from_Request( 'locale' );
				$this->set_setting( 'locale_source', param( 'blog_locale_source', 'string', 'blog' ) );
				$this->set_setting( 'post_locale_source', param( 'blog_post_locale_source', 'string', 'post' ) );
			}

			// Collection permissions:
			$old_advanced_perms = $this->get( 'advanced_perms' );
			$new_advanced_perms = param( 'advanced_perms', 'integer', 0 );
			$old_allow_access = $this->get_setting( 'allow_access' );
			$new_allow_access = param( 'blog_allow_access', 'string', '' );
			if( $old_allow_access != 'members' && $new_allow_access == 'members' )
			{ // If 'Allow access' is changed to 'Members' we should activate advanced perms automatically
				$new_advanced_perms = 1;
			}
			if( $old_advanced_perms == 1 && $new_advanced_perms == 0 && $old_allow_access == 'members' )
			{ // If advanced perms are deselected we should also change 'Allow access' back to 'Logged in users'
				$new_allow_access = 'users';
			}
			$this->set( 'advanced_perms', $new_advanced_perms );
			$this->set_setting( 'allow_access', $new_allow_access );
			if( $this->get_setting( 'allow_access' ) == 'users' || $this->get_setting( 'allow_access' ) == 'members' )
			{ // Disable site maps, feeds and ping plugins when access is restricted on this blog
				$this->set_setting( 'enable_sitemaps', 0 );
				$this->set_setting( 'feed_content', 'none' );
				$this->set_setting( 'ping_plugins', '' );
			}

			// Lists of collections:
			$this->set( 'order', param( 'blog_order', 'integer' ) );
			$this->set( 'in_bloglist', param( 'blog_in_bloglist', 'string', 'public' ) );
			$this->set( 'favorite',  param( 'favorite', 'integer', 0 ) );
		}

		if( param( 'archive_links', 'string', NULL ) !== NULL )
		{ // Archive link type:
			$this->set_setting( 'archive_links', get_param( 'archive_links' ) );
			$this->set_setting( 'archive_posts_per_page', param( 'archive_posts_per_page', 'integer', NULL ), true );
		}

		if( param( 'chapter_links', 'string', NULL ) !== NULL )
		{ // Chapter link type:
			$this->set_setting( 'chapter_links', get_param( 'chapter_links' ) );
		}


		if( param( 'category_prefix', 'string', NULL) !== NULL )
		{
			$category_prefix = get_param( 'category_prefix' );
			if( ! preg_match( '|^([A-Za-z0-9\-_]+(/[A-Za-z0-9\-_]+)*)?$|', $category_prefix) )
			{
				param_error( 'category_prefix', T_('Invalid category prefix.') );
			}
			$this->set_setting( 'category_prefix', $category_prefix);
		}

		if( param( 'atom_redirect', 'string', NULL ) !== NULL )
		{
			param_check_url( 'atom_redirect', 'commenting' );
			$this->set_setting( 'atom_redirect', get_param( 'atom_redirect' ) );

			param( 'rss2_redirect', 'string', NULL );
			param_check_url( 'rss2_redirect', 'commenting' );
			$this->set_setting( 'rss2_redirect', get_param( 'rss2_redirect' ) );
		}

		if( param( 'image_size', 'string', NULL ) !== NULL )
		{
			$this->set_setting( 'image_size', get_param( 'image_size' ));
		}

		if( param( 'tag_links', 'string', NULL ) !== NULL )
		{ // Tag page link type:
			$this->set_setting( 'tag_links', get_param( 'tag_links' ) );
		}

		if( param( 'tag_prefix', 'string', NULL) !== NULL )
		{
			$tag_prefix = get_param( 'tag_prefix' );
			if( ! preg_match( '|^([A-Za-z0-9\-_]+(/[A-Za-z0-9\-_]+)*)?$|', $tag_prefix) )
			{
				param_error( 'tag_prefix', T_('Invalid tag prefix.') );
			}
			$this->set_setting( 'tag_prefix', $tag_prefix);
		}

		// Default to "tag", if "prefix-only" is used, but no tag_prefix was provided.
		if( get_param('tag_links') == 'prefix-only' && ! strlen(param( 'tag_prefix', 'string', NULL)) )
		{
			$this->set_setting( 'tag_prefix', 'tag' );
		}

		// Use rel="tag" attribute? (checkbox)
		$this->set_setting( 'tag_rel_attib', param('tag_rel_attib', 'integer', 0) );


		if( param( 'chapter_content', 'string', NULL ) !== NULL )
		{ // What kind of content on chapter pages?
			$this->set_setting( 'chapter_content', get_param( 'chapter_content' ) );
		}
		if( param( 'tag_content', 'string', NULL ) !== NULL )
		{ // What kind of content on tags pages?
			$this->set_setting( 'tag_content', get_param( 'tag_content' ) );
		}
		if( param( 'archive_content', 'string', NULL ) !== NULL )
		{ // What kind of content on archive pages?
			$this->set_setting( 'archive_content', get_param( 'archive_content' ) );
		}
		if( param( 'filtered_content', 'string', NULL ) !== NULL )
		{ // What kind of content on filtered pages?
			$this->set_setting( 'filtered_content', get_param( 'filtered_content' ) );
		}
		if( param( 'main_content', 'string', NULL ) !== NULL )
		{ // What kind of content on main pages?
			$this->set_setting( 'main_content', get_param( 'main_content' ) );
		}

		// Chapter posts per page:
		$this->set_setting( 'chapter_posts_per_page', param( 'chapter_posts_per_page', 'integer', NULL ), true );
		// Tag posts per page:
		$this->set_setting( 'tag_posts_per_page', param( 'tag_posts_per_page', 'integer', NULL ), true );

		if( param( 'single_links', 'string', NULL ) !== NULL )
		{ // Single post link type:
			$this->set_setting( 'single_links', get_param( 'single_links' ) );
		}

		if( param( 'slug_limit', 'integer', NULL ) !== NULL )
		{ // Limit slug length:
			$this->set_setting( 'slug_limit', get_param( 'slug_limit' ) );
		}

		if( param( 'normal_skin_ID', 'integer', NULL ) !== NULL )
		{ // Normal skin ID:
			$this->set_setting( 'normal_skin_ID', get_param( 'normal_skin_ID' ) );
		}

		if( param( 'mobile_skin_ID', 'integer', NULL ) !== NULL )
		{ // Mobile skin ID:
			if( get_param( 'mobile_skin_ID' ) == 0 )
			{ // Don't store this empty setting in DB
				$this->delete_setting( 'mobile_skin_ID' );
			}
			else
			{ // Set mobile skin
				$this->set_setting( 'mobile_skin_ID', get_param( 'mobile_skin_ID' ) );
			}
		}

		if( param( 'tablet_skin_ID', 'integer', NULL ) !== NULL )
		{ // Tablet skin ID:
			if( get_param( 'tablet_skin_ID' ) == 0 )
			{ // Don't store this empty setting in DB
				$this->delete_setting( 'tablet_skin_ID' );
			}
			else
			{ // Set tablet skin
				$this->set_setting( 'tablet_skin_ID', get_param( 'tablet_skin_ID' ) );
			}
		}

		if( param( 'archives_sort_order', 'string', NULL ) !== NULL )
		{ // Archive sorting
			$this->set_setting( 'archives_sort_order', param( 'archives_sort_order', 'string', false ) );
		}

		if( param( 'download_delay', 'integer', NULL ) !== NULL )
		{ // Download delay
			param_check_range( 'download_delay', 0, 10, T_('Download delay must be numeric (0-10).') );
			$this->set_setting( 'download_delay', get_param( 'download_delay' ) );
		}

		if( param( 'feed_content', 'string', NULL ) !== NULL )
		{ // How much content in feeds?
			$this->set_setting( 'feed_content', get_param( 'feed_content' ) );

			param_integer_range( 'posts_per_feed', 1, 9999, T_('Items per feed must be between %d and %d.') );
			$this->set_setting( 'posts_per_feed', get_param( 'posts_per_feed' ) );
		}

		if( param( 'comment_feed_content', 'string', NULL ) !== NULL )
		{ // How much content in comment feeds?
			$this->set_setting( 'comment_feed_content', get_param( 'comment_feed_content' ) );

			param_integer_range( 'comments_per_feed', 1, 9999, T_('Comments per feed must be between %d and %d.') );
			$this->set_setting( 'comments_per_feed', get_param( 'comments_per_feed' ) );
		}

		if( param( 'blog_shortdesc', 'string', NULL ) !== NULL )
		{	// Description:
			$this->set_from_Request( 'shortdesc' );
		}

		if( param( 'blog_keywords', 'string', NULL ) !== NULL )
		{	// Keywords:
			$this->set_from_Request( 'keywords' );
		}

		if( param( 'blog_tagline', 'html', NULL ) !== NULL )
		{	// HTML tagline:
			param_check_html( 'blog_tagline', T_('Invalid tagline') );
			$this->set( 'tagline', get_param( 'blog_tagline' ) );
		}
		if( param( 'blog_longdesc', 'html', NULL ) !== NULL )
		{	// HTML long description:
			param_check_html( 'blog_longdesc', T_('Invalid long description') );
			$this->set( 'longdesc', get_param( 'blog_longdesc' ) );
		}

		if( param( 'blog_footer_text', 'html', NULL ) !== NULL )
		{ // Blog footer:
			param_check_html( 'blog_footer_text', T_('Invalid blog footer') );
			$this->set_setting( 'blog_footer_text', get_param( 'blog_footer_text' ) );
		}
		if( param( 'single_item_footer_text', 'html', NULL ) !== NULL )
		{ // Blog footer:
			param_check_html( 'single_item_footer_text', T_('Invalid single post footer') );
			$this->set_setting( 'single_item_footer_text', get_param( 'single_item_footer_text' ) );
		}
		if( param( 'xml_item_footer_text', 'html', NULL ) !== NULL )
		{ // Blog footer:
			param_check_html( 'xml_item_footer_text', T_('Invalid RSS footer') );
			$this->set_setting( 'xml_item_footer_text', get_param( 'xml_item_footer_text' ) );
		}
		if( param( 'blog_notes', 'html', NULL ) !== NULL )
		{	// HTML notes:
			param_check_html( 'blog_notes', T_('Invalid Blog Notes') );
			$this->set( 'notes', get_param( 'blog_notes' ) );

			param_integer_range( 'max_footer_credits', 0, 3, T_('Max credits must be between %d and %d.') );
			$this->set_setting( 'max_footer_credits', get_param( 'max_footer_credits' ) );
		}


		if( in_array( 'pings', $groups ) )
		{ // we want to load the ping checkboxes:
			$blog_ping_plugins = param( 'blog_ping_plugins', 'array:string', array() );
			$blog_ping_plugins = array_unique($blog_ping_plugins);
			$this->set_setting('ping_plugins', implode(',', $blog_ping_plugins));
		}

		if( in_array( 'authors', $groups ) )
		{ // we want to load the workflow & permissions params
			$this->set_setting( 'use_workflow',  param( 'blog_use_workflow', 'integer', 0 ) );
		}

		if( in_array( 'home', $groups ) )
		{ // we want to load the front page params:
			$front_disp = param( 'front_disp', 'string', '' );
			$this->set_setting( 'front_disp', $front_disp );

			$front_post_ID = param( 'front_post_ID', 'integer', 0 );
			if( $front_disp == 'page' )
			{ // Post ID must be required
				param_check_not_empty( 'front_post_ID', T_('Please enter a specific post ID') );
			}
			$this->set_setting( 'front_post_ID', $front_post_ID );
		}

		if( in_array( 'features', $groups ) )
		{ // we want to load the workflow checkboxes:
			$this->set_setting( 'enable_goto_blog', param( 'enable_goto_blog', 'string', NULL ) );

			$this->set_setting( 'editing_goto_blog', param( 'editing_goto_blog', 'string', NULL ) );

			$this->set_setting( 'default_post_type', param( 'default_post_type', 'integer', 0 ) );
			$this->set_setting( 'default_post_status', param( 'default_post_status', 'string', NULL ) );

			$this->set_setting( 'post_categories', param( 'post_categories', 'string', NULL ) );

			$this->set_setting( 'post_navigation', param( 'post_navigation', 'string', NULL ) );

			// Show x days or x posts?:
			$this->set_setting( 'what_to_show', param( 'what_to_show', 'string', '' ) );

			param_integer_range( 'posts_per_page', 1, 9999, T_('Items/days per page must be between %d and %d.') );
			$this->set_setting( 'posts_per_page', get_param( 'posts_per_page' ) );

			$this->set_setting( 'orderby', param( 'orderby', 'string', true ) );
			$this->set_setting( 'orderdir', param( 'orderdir', 'string', true ) );

			// Front office statuses
			$this->load_inskin_statuses( 'post' );

			// Time frame
			$this->set_setting( 'timestamp_min', param( 'timestamp_min', 'string', '' ) );
			$this->set_setting( 'timestamp_min_duration', param_duration( 'timestamp_min_duration' ) );
			$this->set_setting( 'timestamp_max', param( 'timestamp_max', 'string', '' ) );
			$this->set_setting( 'timestamp_max_duration', param_duration( 'timestamp_max_duration' ) );

			// call modules update_collection_features on this blog
			modules_call_method( 'update_collection_features', array( 'edited_Blog' => & $this ) );

			// load post moderation statuses
			$moderation_statuses = get_visibility_statuses( 'moderation' );
			$post_moderation_statuses = array();
			foreach( $moderation_statuses as $status )
			{
				if( param( 'post_notif_'.$status, 'integer', 0 ) )
				{
					$post_moderation_statuses[] = $status;
				}
			}
			$this->set_setting( 'post_moderation_statuses', implode( ',', $post_moderation_statuses ) );
		}

		if( in_array( 'comments', $groups ) )
		{ // we want to load the workflow checkboxes:
			// load moderation statuses
			$moderation_statuses = get_visibility_statuses( 'moderation' );
			$blog_moderation_statuses = array();
			foreach( $moderation_statuses as $status )
			{
				if( param( 'notif_'.$status, 'integer', 0 ) )
				{
					$blog_moderation_statuses[] = $status;
				}
			}
			$this->set_setting( 'moderation_statuses', implode( ',', $blog_moderation_statuses ) );

			$this->set_setting( 'comment_quick_moderation',  param( 'comment_quick_moderation', 'string', 'expire' ) );
			$this->set_setting( 'allow_item_subscriptions', param( 'allow_item_subscriptions', 'integer', 0 ) );
			$this->set_setting( 'comments_detect_email', param( 'comments_detect_email', 'integer', 0 ) );
			$this->set_setting( 'comments_register', param( 'comments_register', 'integer', 0 ) );
		}

		if( in_array( 'other', $groups ) )
		{ // we want to load the workflow checkboxes:
			$this->set_setting( 'enable_sitemaps', param( 'enable_sitemaps', 'integer', 0 ) );

			$this->set_setting( 'allow_subscriptions', param( 'allow_subscriptions', 'integer', 0 ) );
			$this->set_setting( 'allow_item_subscriptions', param( 'allow_item_subscriptions', 'integer', 0 ) );

			// Tracking unread content
			$this->set_setting( 'track_unread_content', param( 'track_unread_content', 'integer', 0 ) );

			$this->set_setting( 'image_size_user_list', param( 'image_size_user_list', 'string' ) );
			$this->set_setting( 'image_size_messaging', param( 'image_size_messaging', 'string' ) );

			$this->set_setting( 'archive_mode', param( 'archive_mode', 'string', true ) );
		}

		if( param( 'allow_comments', 'string', NULL ) !== NULL )
		{ // Feedback options:
			$this->set_setting( 'allow_comments', param( 'allow_comments', 'string', 'any' ) );
			$this->set_setting( 'allow_view_comments', param( 'allow_view_comments', 'string', 'any' ) );
			$new_feedback_status = param( 'new_feedback_status', 'string', 'draft' );
			if( $new_feedback_status != $this->get_setting( 'new_feedback_status' ) && ( $new_feedback_status != 'published' || $current_User->check_perm( 'blog_admin', 'edit', false, $this->ID ) ) )
			{ // Only admin can set this setting to 'Public'
				$this->set_setting( 'new_feedback_status', $new_feedback_status );
			}
			$this->set_setting( 'allow_anon_url', param( 'allow_anon_url', 'string', '0' ) );
			$this->set_setting( 'allow_html_comment', param( 'allow_html_comment', 'string', '0' ) );
			$this->set_setting( 'allow_attachments', param( 'allow_attachments', 'string', 'registered' ) );
			$this->set_setting( 'max_attachments', param( 'max_attachments', 'integer', '' ) );
			$this->set_setting( 'autocomplete_usernames', param( 'autocomplete_usernames', 'integer', '' ) );
			$this->set_setting( 'display_rating_summary', param( 'display_rating_summary', 'string', '0' ) );
			$this->set_setting( 'allow_rating_items', param( 'allow_rating_items', 'string', 'never' ) );
			$this->set_setting( 'rating_question', param( 'rating_question', 'text' ) );
			$this->set_setting( 'allow_rating_comment_helpfulness', param( 'allow_rating_comment_helpfulness', 'string', '0' ) );
			$blog_allowtrackbacks = param( 'blog_allowtrackbacks', 'integer', 0 );
			if( $blog_allowtrackbacks != $this->get( 'allowtrackbacks' ) && ( $blog_allowtrackbacks == 0 || $current_User->check_perm( 'blog_admin', 'edit', false, $this->ID ) ) )
			{ // Only admin can turn ON this setting
				$this->set( 'allowtrackbacks', $blog_allowtrackbacks );
			}
			$this->set_setting( 'comments_orderdir', param( 'comments_orderdir', '/^(?:ASC|DESC)$/', 'ASC' ) );

			// call modules update_collection_comments on this blog
			modules_call_method( 'update_collection_comments', array( 'edited_Blog' => & $this ) );

			$threaded_comments = param( 'threaded_comments', 'integer', 0 );
			$this->set_setting( 'threaded_comments', $threaded_comments );
			$this->set_setting( 'paged_comments', $threaded_comments ? 0 : param( 'paged_comments', 'integer', 0 ) );
			param_integer_range( 'comments_per_page', 1, 9999, T_('Comments per page must be between %d and %d.') );
			$this->set_setting( 'comments_per_page', get_param( 'comments_per_page' ) );
			$this->set_setting( 'comments_avatars', param( 'comments_avatars', 'integer', 0 ) );
			$this->set_setting( 'comments_latest', param( 'comments_latest', 'integer', 0 ) );

			// load blog front office comment statuses
			$this->load_inskin_statuses( 'comment' );
		}


		if( in_array( 'seo', $groups ) )
		{ // we want to load the workflow checkboxes:
			$this->set_setting( 'canonical_homepage', param( 'canonical_homepage', 'integer', 0 ) );
			$this->set_setting( 'relcanonical_homepage', param( 'relcanonical_homepage', 'integer', 0 ) );
			$this->set_setting( 'canonical_item_urls', param( 'canonical_item_urls', 'integer', 0 ) );
			$this->set_setting( 'relcanonical_item_urls', param( 'relcanonical_item_urls', 'integer', 0 ) );
			$this->set_setting( 'canonical_archive_urls', param( 'canonical_archive_urls', 'integer', 0 ) );
			$this->set_setting( 'relcanonical_archive_urls', param( 'relcanonical_archive_urls', 'integer', 0 ) );
			$this->set_setting( 'canonical_cat_urls', param( 'canonical_cat_urls', 'integer', 0 ) );
			$this->set_setting( 'relcanonical_cat_urls', param( 'relcanonical_cat_urls', 'integer', 0 ) );
			$this->set_setting( 'canonical_tag_urls', param( 'canonical_tag_urls', 'integer', 0 ) );
			$this->set_setting( 'relcanonical_tag_urls', param( 'relcanonical_tag_urls', 'integer', 0 ) );
			$this->set_setting( 'default_noindex', param( 'default_noindex', 'integer', 0 ) );
			$this->set_setting( 'paged_noindex', param( 'paged_noindex', 'integer', 0 ) );
			$this->set_setting( 'paged_nofollowto', param( 'paged_nofollowto', 'integer', 0 ) );
			$this->set_setting( 'archive_noindex', param( 'archive_noindex', 'integer', 0 ) );
			$this->set_setting( 'archive_nofollowto', param( 'archive_nofollowto', 'integer', 0 ) );
			$this->set_setting( 'chapter_noindex', param( 'chapter_noindex', 'integer', 0 ) );
			$this->set_setting( 'tag_noindex', param( 'tag_noindex', 'integer', 0 ) );
			$this->set_setting( 'filtered_noindex', param( 'filtered_noindex', 'integer', 0 ) );
			$this->set_setting( 'arcdir_noindex', param( 'arcdir_noindex', 'integer', 0 ) );
			$this->set_setting( 'catdir_noindex', param( 'catdir_noindex', 'integer', 0 ) );
			$this->set_setting( 'feedback-popup_noindex', param( 'feedback-popup_noindex', 'integer', 0 ) );
			$this->set_setting( 'msgform_noindex', param( 'msgform_noindex', 'integer', 0 ) );
			$this->set_setting( 'special_noindex', param( 'special_noindex', 'integer', 0 ) );
			$this->set_setting( 'title_link_type', param( 'title_link_type', 'string', '' ) );
			$this->set_setting( 'permalinks', param( 'permalinks', 'string', '' ) );
			$this->set_setting( '404_response', param( '404_response', 'string', '' ) );
			$this->set_setting( 'help_link', param( 'help_link', 'string', '' ) );
			$this->set_setting( 'excerpts_meta_description', param( 'excerpts_meta_description', 'integer', 0 ) );
			$this->set_setting( 'categories_meta_description', param( 'categories_meta_description', 'integer', 0 ) );
			$this->set_setting( 'tags_meta_keywords', param( 'tags_meta_keywords', 'integer', 0 ) );
			$this->set_setting( 'tags_open_graph', param( 'tags_open_graph', 'integer', 0 ) );
			$this->set_setting( 'download_noindex', param( 'download_noindex', 'integer', 0 ) );
			$this->set_setting( 'download_nofollowto', param( 'download_nofollowto', 'integer', 0 ) );
		}


		/*
		 * ADVANCED ADMIN SETTINGS
		 */
		if( $current_User->check_perm( 'blog_admin', 'edit', false, $this->ID ) )
		{	// We have permission to edit advanced admin settings:

			if( in_array( 'cache', $groups ) )
			{ // we want to load the cache params:
				$this->set_setting( 'ajax_form_enabled', param( 'ajax_form_enabled', 'integer', 0 ) );
				$this->set_setting( 'ajax_form_loggedin_enabled', param( 'ajax_form_loggedin_enabled', 'integer', 0 ) );
				$this->set_setting( 'cache_enabled_widgets', param( 'cache_enabled_widgets', 'integer', 0 ) );
			}

			if( in_array( 'styles', $groups ) )
			{ // we want to load the styles params:
				$this->set( 'allowblogcss', param( 'blog_allowblogcss', 'integer', 0 ) );
				$this->set( 'allowusercss', param( 'blog_allowusercss', 'integer', 0 ) );
			}

			if( in_array( 'login', $groups ) )
			{ // we want to load the login params:
				if( ! get_setting_Blog( 'login_blog_ID' ) )
				{ // Update this only when no blog is defined for login/registration
					$this->set_setting( 'in_skin_login', param( 'in_skin_login', 'integer', 0 ) );
				}
				$this->set_setting( 'in_skin_editing', param( 'in_skin_editing', 'integer', 0 ) );
			}

			if( param( 'blog_head_includes', 'html', NULL ) !== NULL )
			{	// HTML header includes:
				param_check_html( 'blog_head_includes', T_('Invalid Custom meta tag/css section.'), '#', 'head_extension' );
				$this->set_setting( 'head_includes', get_param( 'blog_head_includes' ) );
			}

			if( param( 'blog_footer_includes', 'html', NULL ) !== NULL )
			{	// HTML header includes:
				param_check_html( 'blog_footer_includes', T_('Invalid Custom javascript section') );
				$this->set_setting( 'footer_includes', get_param( 'blog_footer_includes' ) );
			}

			if( param( 'owner_login', 'string', NULL ) !== NULL )
			{ // Permissions:
				$UserCache = & get_UserCache();
				$owner_User = & $UserCache->get_by_login( get_param('owner_login') );
				if( empty( $owner_User ) )
				{
					param_error( 'owner_login', sprintf( T_('User &laquo;%s&raquo; does not exist!'), get_param('owner_login') ) );
				}
				else
				{
					$this->set( 'owner_user_ID', $owner_User->ID );
					$this->owner_User = & $owner_User;
				}
			}


			if( ($blog_urlname = param( 'blog_urlname', 'string', NULL )) !== NULL )
			{	// check urlname
				if( param_check_not_empty( 'blog_urlname', T_('You must provide an URL collection name!') ) )
				{
					if( ! preg_match( '|^[A-Za-z0-9\-]+$|', $blog_urlname ) )
					{
						param_error( 'blog_urlname', sprintf( T_('The url name %s is invalid.'), "&laquo;$blog_urlname&raquo;" ) );
						$blog_urlname = NULL;
					}

					if( isset($blog_urlname) && $DB->get_var( 'SELECT COUNT(*)
															FROM T_blogs
															WHERE blog_urlname = '.$DB->quote($blog_urlname).'
															AND blog_ID <> '.$this->ID
														) )
					{ // urlname is already in use
						param_error( 'blog_urlname', sprintf( T_('The URL name %s is already in use by another collection. Please choose another name.'), "&laquo;$blog_urlname&raquo;" ) );
						$blog_urlname = NULL;
					}

					if( isset( $blog_urlname ) )
					{ // Set new urlname and save old media dir in order to rename folder to new
						$old_media_dir = $this->get_media_dir( false );
						$this->set_from_Request( 'urlname' );
					}
				}
			}


			if( ($access_type = param( 'blog_access_type', 'string', NULL )) !== NULL )
			{ // Blog URL parameters:
				// Note: We must avoid to set an invalid url, because the new blog url will be displayed in the evobar even if it was not saved
				$allow_new_access_type = true;

				if( $access_type == 'absolute' )
				{
					$blog_siteurl = param( 'blog_siteurl_absolute', 'string', true );
					if( preg_match( '#^https?://[^/]+/.*#', $blog_siteurl, $matches ) )
					{ // It looks like valid absolute URL, so we may update the blog siteurl
						$this->set( 'siteurl', $blog_siteurl );
					}
					else
					{ // It is not valid absolute URL, don't update the blog 'siteurl' to avoid errors
						$allow_new_access_type = false; // If site url is not updated do not allow access_type update either
						$Messages->add( T_('Collection Folder URL').': '.sprintf( T_('%s is an invalid absolute URL'), '&laquo;'.htmlspecialchars( $blog_siteurl ).'&raquo;' )
							.'. '.T_('You must provide an absolute URL (starting with <code>http://</code> or <code>https://</code>) and it must contain at least one \'/\' sign after the domain name!'), 'error' );
					}
				}
				elseif( $access_type == 'relative' )
				{ // relative siteurl
					$blog_siteurl = param( 'blog_siteurl_relative', 'string', true );
					if( preg_match( '#^https?://#', $blog_siteurl ) )
					{
						$Messages->add( T_('Blog Folder URL').': '
														.T_('You must provide a relative URL (without <code>http://</code> or <code>https://</code>)!'), 'error' );
					}
					$this->set( 'siteurl', $blog_siteurl );
				}
				else
				{
					$this->set( 'siteurl', '' );
				}

				if( $allow_new_access_type )
				{ // The received siteurl value was correct, may update the access_type value
					$this->set( 'access_type', $access_type );
				}
			}


			if( param( 'aggregate_coll_IDs', 'string', NULL ) !== NULL )
			{ // Aggregate list: (can be '*')
				$aggregate_coll_IDs = get_param( 'aggregate_coll_IDs' );

				if( $aggregate_coll_IDs != '*' )
				{	// Sanitize the string
					$aggregate_coll_IDs = sanitize_id_list($aggregate_coll_IDs);
				}

				// fp> TODO: check perms on each aggregated blog (if changed)
				// fp> TODO: better interface
				if( $aggregate_coll_IDs != '*' && !preg_match( '#^([0-9]+(,[0-9]+)*)?$#', $aggregate_coll_IDs ) )
				{
					param_error( 'aggregate_coll_IDs', T_('Invalid aggregate collection ID list!') );
				}
				$this->set_setting( 'aggregate_coll_IDs', $aggregate_coll_IDs );
			}


			$media_location = param( 'blog_media_location', 'string', NULL );
			if( $media_location !== NULL )
			{ // Media files location:
				$old_media_dir = $this->get_media_dir( false );
				$old_media_location = $this->get( 'media_location' );
				$this->set_from_Request( 'media_location' );
				$this->set_media_subdir( param( 'blog_media_subdir', 'string', '' ) );
				$this->set_media_fullpath( param( 'blog_media_fullpath', 'string', '' ) );
				$this->set_media_url( param( 'blog_media_url', 'string', '' ) );

				// check params
				switch( $this->get( 'media_location' ) )
				{
					case 'custom': // custom path and URL
						global $demo_mode, $media_path;
						if( $this->get( 'media_fullpath' ) == '' )
						{
							param_error( 'blog_media_fullpath', T_('Media dir location').': '.T_('You must provide the full path of the media directory.') );
						}
						if( !preg_match( '#^https?://#', $this->get( 'media_url' ) ) )
						{
							param_error( 'blog_media_url', T_('Media dir location').': '
															.T_('You must provide an absolute URL (starting with <code>http://</code> or <code>https://</code>)!') );
						}
						if( $demo_mode )
						{
							$canonical_fullpath = get_canonical_path($this->get('media_fullpath'));
							if( ! $canonical_fullpath || strpos($canonical_fullpath, $media_path) !== 0 )
							{
								param_error( 'blog_media_fullpath', T_('Media dir location').': in demo mode the path must be inside of $media_path.' );
							}
						}
						break;

					case 'subdir':
						global $media_path;
						if( $this->get( 'media_subdir' ) == '' )
						{
							param_error( 'blog_media_subdir', T_('Media dir location').': '.T_('You must provide the media subdirectory.') );
						}
						else
						{ // Test if it's below $media_path (subdir!)
							$canonical_path = get_canonical_path($media_path.$this->get( 'media_subdir' ));
							if( ! $canonical_path || strpos($canonical_path, $media_path) !== 0 )
							{
								param_error( 'blog_media_subdir', T_('Media dir location').': '.sprintf(T_('Invalid subdirectory &laquo;%s&raquo;.'), format_to_output($this->get('media_subdir'))) );
							}
							else
							{
								// Validate if it's a valid directory name:
								$subdir = no_trailing_slash(substr($canonical_path, strlen($media_path)));
								if( $error = validate_dirname($subdir) )
								{
									param_error( 'blog_media_subdir', T_('Media dir location').': '.$error );
									syslog_insert( sprintf( 'Invalid name is detected for folder %s', '<b>'.$subdir.'</b>' ), 'warning', 'file' );
								}
							}
						}
						break;
				}
			}

			if( ! param_errors_detected() && ! empty( $old_media_dir ) )
			{ // No error were detected before and possibly the media directory path was updated, check if it can be managed
				$this->check_media_dir_change( $old_media_dir, isset( $old_media_location ) ? $old_media_location : NULL );
			}

		}

		return ! param_errors_detected();
	}


	/**
	 * Load blog front office post/comment statuses
	 *
	 * @param string type = 'post' or 'comment'
	 */
	function load_inskin_statuses( $type )
	{
		if( ( $type != 'post' ) && ( $type != 'comment' ) )
		{ // Invalid type
			debug_die( 'Invalid type to load blog inskin statuses!' );
		}

		// Get possible front office statuses
		$inskin_statuses = get_visibility_statuses( 'keys', array( 'deprecated', 'trash', 'redirected' ) );
		$selected_inskin_statuses = array();
		foreach( $inskin_statuses as $status )
		{
			if( param( $type.'_inskin_'.$status, 'integer', 0 ) )
			{ // This status was selected
				$selected_inskin_statuses[] = $status;
			}
		}
		$this->set_setting( $type.'_inskin_statuses', implode( ',', $selected_inskin_statuses ) );
	}


	/**
	 * Set the media folder's subdir
	 *
	 * @param string the subdirectory
	 */
	function set_media_subdir( $path )
	{
		parent::set_param( 'media_subdir', 'string', trailing_slash( $path ) );
	}


	/**
	 * Set the full path of the media folder
	 *
	 * @param string the full path
	 */
	function set_media_fullpath( $path )
	{
		parent::set_param( 'media_fullpath', 'string', trailing_slash( $path ) );
	}


	/**
	 * Set the full URL of the media folder
	 *
	 * @param string the full URL
	 */
	function set_media_url( $url )
	{
		parent::set_param( 'media_url', 'string', trailing_slash( $url ) );
	}


	/**
	 * Set param value
	 *
	 * @param string Parameter name
	 * @param boolean true to set to NULL if empty value
	 * @return boolean true, if a value has been set; false if it has not changed
	 */
	function set( $parname, $parvalue, $make_null = false )
	{
		global $Settings;

		switch( $parname )
		{
			case 'ID':
			case 'allowtrackbacks':
				return $this->set_param( $parname, 'number', $parvalue, $make_null );
				break;

			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Generate blog URL. That is the URL of the main page/home page of the blog.
	 * This will nto necessarily be a folder. For example, it can end in index.php?blog=4
	 *
	 * @param string default|dynamic|static
	 */
	function gen_blogurl( $type = 'default' )
	{
		global $baseurl, $basedomain, $Settings;

		switch( $this->access_type )
		{
			case 'baseurl':
			case 'default':
				// Access through index.php: match absolute URL or call default blog
				if( ( $Settings->get('default_blog_ID') == $this->ID )
					|| preg_match( '#^https?://#', $this->siteurl ) )
				{ // Safety check! We only do that kind of linking if this is really the default blog...
					// or if we call by absolute URL
					if( $this->access_type == 'default' )
					{
						return $baseurl.$this->siteurl.'index.php';
					}
					else
					{
						return $baseurl.$this->siteurl;
					}
				}
				// ... otherwise, we add the blog ID:

			case 'index.php':
				// Access through index.php + blog qualifier
				return $baseurl.$this->siteurl.'index.php?blog='.$this->ID;

			case 'extrabase':
				// We want to use extra path on base url, use the blog urlname:
				return $baseurl.$this->siteurl.$this->urlname.'/';

			case 'extrapath':
				// We want to use extra path on index.php, use the blog urlname:
				return $baseurl.$this->siteurl.'index.php/'.$this->urlname.'/';

			case 'relative':
				return $baseurl.$this->siteurl;

			case 'subdom':
				return preg_replace( '#(https?://)#i', '$1'.$this->urlname.'.', $baseurl );

			case 'absolute':
				return $this->siteurl;

			default:
				debug_die( 'Unhandled Blog access type ['.$this->access_type.']' );
		}
	}


	/**
	 * Generate the baseurl of the blog (URL of the folder where the blog lives).
	 * Will always end with '/'.
	 */
	function gen_baseurl()
	{
		global $baseurl, $basedomain;

		switch( $this->access_type )
		{
			case 'baseurl':
				return $baseurl.$this->siteurl;

			case 'default':
			case 'index.php':
				return $baseurl.$this->siteurl.'index.php/';

			case 'extrabase':
				// We want to use extra path on base url, use the blog urlname:
				return $baseurl.$this->siteurl.$this->urlname.'/';

			case 'extrapath':
				// We want to use extra path on index.php, use the blog urlname:
				return $baseurl.$this->siteurl.'index.php/'.$this->urlname.'/';

			case 'relative':
				$url = $baseurl.$this->siteurl;
				break;

			case 'subdom':
				return preg_replace( '#(https?://)#i', '$1'.$this->urlname.'.', $baseurl );

			case 'absolute':
				$url = $this->siteurl;
				break;

			default:
				debug_die( 'Unhandled Blog access type ['.$this->access_type.']' );
		}

		if( substr( $url, -1 ) != '/' )
		{ // Crop an url part after the last "/"
			$url = substr( $url, 0, strrpos( $url, '/' ) + 1 );
		}

		// For case relative and absolute:
		return preg_replace( '~^(.+)/[^/]$~', '$1/', $url );
	}


	/**
	 * This is the domain of the blog.
	 * This returns NO trailing slash.
	 */
	function get_baseurl_root()
	{
		if( preg_match( '#^(https?://(.+?)(:.+?)?)/#', $this->gen_baseurl(), $matches ) )
		{
			return $matches[1];
		}
		debug_die( 'Blog::get(baseurl)/baseurlroot - assertion failed [baseurl: '.$this->gen_baseurl().'].' );
	}


	/**
	 * Get the URL to the basepath of that blog.
	 * This is supposed to be the same as $baseurl but localized to the domain of the blog/
	 *
	 * @todo The current implementation may not work in all situations. See TODO below.
	 */
	function get_basepath_url()
	{
		global $basesubpath;

		// fp> TODO: this may be very borked and may need some tweaking for non standard multiblog situations:
		// One way to fix this, if neede, may be to add a settinf to Blog Settings > URLs
		// -- Create a block for "System URLs" and give a radio option between default and custom with input field

		if( empty($this->basepath_url) )
		{
			$this->basepath_url = $this->get_baseurl_root().$basesubpath;
		}

		return $this->basepath_url;
	}


	/**
	 * Get the URL of the htsrv folder, on the current blog's domain (which is NOT always the same as the $baseurl domain!).
	 */
	function get_local_htsrv_url()
	{
		global $htsrv_subdir;

		return $this->get_basepath_url().$htsrv_subdir;
	}


	/**
	 * Get the URL of the media folder, on the current blog's domain (which is NOT always the same as the $baseurl domain!).
	 */
	function get_local_media_url()
	{
		global $media_subdir;

		return $this->get_basepath_url().$media_subdir;
	}


	/**
	 * Get the URL of the rsc folder, on the current blog's domain (which is NOT always the same as the $baseurl domain!).
	 */
	function get_local_rsc_url()
	{
		global $rsc_subdir;

		return $this->get_basepath_url().$rsc_subdir;
	}


	/**
	 * Get the URL of the skins folder, on the current blog's domain (which is NOT always the same as the $baseurl domain!).
	 */
	function get_local_skins_url()
	{
		global $skins_subdir;

		return $this->get_basepath_url().$skins_subdir;
	}


	/**
	 * Get the URL of the xmlsrv folder, on the current blog's domain (which is NOT always the same as the $baseurl domain!).
	 */
	function get_local_xmlsrv_url()
	{
		global $xmlsrv_subdir;

		return $this->get_basepath_url().$xmlsrv_subdir;
	}


	/**
	 * Generate archive page URL
	 *
	 * Note: there ate two similar functions here.
	 * @see Blog::get_archive_url()
	 *
	 * @param string year
	 * @param string month
	 * @param string day
	 * @param string week
	 */
	function gen_archive_url( $year, $month = NULL, $day = NULL, $week = NULL, $glue = '&amp;', $paged = 1 )
	{
		$blogurl = $this->gen_blogurl();

		$archive_links = $this->get_setting('archive_links');

		if( $archive_links == 'param' )
		{	// We reference by Query
			$separator = '';
		}
		else
		{	// We reference by extra path info
			$separator = '/';
		}

		$datestring = $separator.$year.$separator;

		if( !empty( $month ) )
		{
			$datestring .= zeroise($month,2).$separator;
			if( !empty( $day ) )
			{
				$datestring .= zeroise($day,2).$separator;
			}
		}
		elseif( !is_null($week) && $week !== '' )  // Note: week # can be 0 !
		{
			if( $archive_links == 'param' )
			{	// We reference by Query
				$datestring .= $glue.'w='.$week;
			}
			else
			{	// extra path info
				$datestring .= 'w'.zeroise($week,2).'/';
			}
		}

		if( $archive_links == 'param' )
		{	// We reference by Query
			$link = url_add_param( $blogurl, 'm='.$datestring, $glue );

			$archive_posts_per_page = $this->get_setting( 'archive_posts_per_page' );
			if( !empty($archive_posts_per_page) && $archive_posts_per_page != $this->get_setting( 'posts_per_page' ) )
			{	// We want a specific post per page count:
				$link = url_add_param( $link, 'posts='.$archive_posts_per_page, $glue );
			}
		}
		else
		{	// We reference by extra path info
			$link = url_add_tail( $blogurl, $datestring ); // there may already be a slash from a siteurl like 'http://example.com/'
		}

		if( $paged > 1 )
		{	// We want a specific page:
			$link = url_add_param( $link, 'paged='.$paged, $glue );
		}

		return $link;
	}


	/**
	 * Generate link to archive
	 * @uses Blog::gen_archive_url()
	 * @return string HTML A tag
	 */
	function gen_archive_link( $text, $title, $year, $month = NULL, $day = NULL, $week = NULL, $glue = '&amp;', $paged = 1 )
	{
		$link = '<a';

		if( $this->get_setting( 'archive_nofollowto' ) )
		{
			$link .= ' rel="nofollow"';
		}

 		if( !empty($title) )
		{
			$link .= ' title="'.format_to_output( $title, 'htmlattr' ).'"';
		}

		$link .= ' href="'.$this->gen_archive_url( $year, $month, $day, $week, $glue, $paged ).'" >';
		$link .= format_to_output( $text );
		$link .= '</a>';

		return $link;
	}


	/**
	 * Get archive page URL
	 *
	 * Note: there are two similar functions here.
	 *
	 * @uses Blog::gen_archive_url()
	 *
	 * @param string monthly, weekly, daily
	 */
	function get_archive_url( $date, $glue = '&amp;' )
	{
		switch( $this->get_setting('archive_mode') )
		{
			case 'weekly':
				global $cacheweekly, $DB;
				if((!isset($cacheweekly)) || (empty($cacheweekly[$date])))
				{
					$cacheweekly[$date] = $DB->get_var( 'SELECT '.$DB->week( $DB->quote($date), locale_startofweek() ) );
				}
				return $this->gen_archive_url( substr( $date, 0, 4 ), NULL, NULL, $cacheweekly[$date], $glue );
				break;

			case 'daily':
				return $this->gen_archive_url( substr( $date, 0, 4 ), substr( $date, 5, 2 ), substr( $date, 8, 2 ), NULL, $glue );
				break;

			case 'monthly':
			default:
				return $this->gen_archive_url( substr( $date, 0, 4 ), substr( $date, 5, 2 ), NULL, NULL, $glue );
		}
	}


	/**
	 * Generate a tag url on this blog
	 */
	function gen_tag_url( $tag, $paged = 1, $glue = '&' )
	{
		$link_type = $this->get_setting( 'tag_links' );
		switch( $link_type )
		{
			case 'param':
				$r = url_add_param( $this->gen_blogurl(), 'tag='.urlencode( $tag ), $glue );

				$tag_posts_per_page = $this->get_setting( 'tag_posts_per_page' );
				if( !empty($tag_posts_per_page) && $tag_posts_per_page != $this->get_setting( 'posts_per_page' ) )
				{	// We want a specific post per page count:
					$r = url_add_param( $r, 'posts='.$tag_posts_per_page, $glue );
				}
				break;

			default:
				switch( $link_type )
				{
					case 'dash':
						$trailer = '-';
						break;
					case 'semicol': // dh> TODO: old value. I had this in my DB. Convert this during upgrade?
					case 'semicolon':
						$trailer = ';';
						break;
					case 'colon':
						$trailer = ':';
						break;
					case 'prefix-only':
					default:
						$trailer = '';
				}
				$tag_prefix = $this->get_setting('tag_prefix');
				if( !empty( $tag_prefix ) )
				{
					$r = url_add_tail( $this->gen_blogurl(), '/'.$tag_prefix.'/'.urlencode( $tag ).$trailer );
				}
				else
				{
					$r = url_add_tail( $this->gen_blogurl(), '/'.urlencode( $tag ).$trailer );
				}
		}

		if( $paged > 1 )
		{	// We want a specific page:
			$r = url_add_param( $r, 'paged='.$paged, $glue );
		}

		return $r;
	}


	/**
	 * Get a link (<a href>) to the tag page of a given tag.
	 *
	 * @param string Tag
	 * @param string Link text (defaults to tag name)
	 * @param array Additional attributes for the A tag (href gets overridden).
	 * @return string The <a href> link
	 */
	function get_tag_link( $tag, $text = NULL, $attribs = array() )
	{
		if( $this->get_setting('tag_rel_attrib') && $this->get_setting('tag_links') == 'prefix-only' )
		{	// add rel=tag attrib -- valid only if the last part of the url is the tag name
			if( ! isset($attribs['rel']) )
				$attribs['rel'] = 'tag';
			else
				$attribs['rel'] .= ' tag';
		}
		$attribs['href'] = $this->gen_tag_url( $tag );

		if( is_null($text) )
		{
			$text = $tag;
		}

		return '<a'.get_field_attribs_as_string($attribs).'>'.$text.'</a>';
	}


	/**
	 * Get allowed post status for current user in this blog
	 *
	 * @todo make default a Blog param
	 *
	 * @param string status to start with. Empty to use default.
	 * @return string authorized status; NULL if none
	 */
	function get_allowed_item_status( $status = NULL )
	{
		/**
		 * @var User
		 */
		global $current_User;

		if( empty( $status ) )
		{
			$status = $this->get_setting('default_post_status');
		}
		if( ! $current_User->check_perm( 'blog_post!'.$status, 'create', false, $this->ID ) )
		{ // We need to find another one:
			$status = NULL;

			if( $current_User->check_perm( 'blog_post!published', 'create', false, $this->ID ) )
				$status = 'published';
			elseif( $current_User->check_perm( 'blog_post!community', 'create', false, $this->ID ) )
				$status = 'community';
			elseif( $current_User->check_perm( 'blog_post!protected', 'create', false, $this->ID ) )
				$status = 'protected';
			elseif( $current_User->check_perm( 'blog_post!private', 'create', false, $this->ID ) )
				$status = 'private';
			elseif( $current_User->check_perm( 'blog_post!review', 'create', false, $this->ID ) )
				$status = 'review';
			elseif( $current_User->check_perm( 'blog_post!draft', 'create', false, $this->ID ) )
				$status = 'draft';
			elseif( $current_User->check_perm( 'blog_post!deprecated', 'create', false, $this->ID ) )
				$status = 'deprecated';
			elseif( $current_User->check_perm( 'blog_post!redirected', 'create', false, $this->ID ) )
				$status = 'redirected';
		}
		return $status;
	}


	/**
	 * Get default category for current blog
	 *
	 * @todo fp> this is a super lame stub, but it's still better than nothing. Should be user configurable.
	 *
	 */
	function get_default_cat_ID()
	{
		global $DB, $Settings;

		if( empty( $this->default_cat_ID ) )
		{
			if( $default_cat_ID = $this->get_setting('default_cat_ID') )
			{	// A specific cat has previosuly been configured as the default:
				// Try to get it from the DB (to make sure it exists and is in teh right blog):
				$sql = 'SELECT cat_ID
				          FROM T_categories
				         WHERE cat_blog_ID = '.$this->ID.'
				         	 AND cat_ID = '.$default_cat_ID;
				$this->default_cat_ID = $DB->get_var( $sql, 0, 0, 'Get default category' );
			}
		}

		if( empty( $this->default_cat_ID ) )
		{	// If the previous query has returned NULL
			if( $this->get_setting('category_ordering') == 'manual' )
			{	// Manual order
				$select_temp_order = ', IF( cat_order IS NULL, 999999999, cat_order ) AS temp_order';
				$sql_order = ' ORDER BY cat_parent_ID, temp_order';
			}
			else
			{	// Alphabetic order
				$select_temp_order = '';
				$sql_order = ' ORDER BY cat_parent_ID, cat_name';
			}
			$sql = 'SELECT cat_ID'.$select_temp_order.'
			          FROM T_categories
			         WHERE cat_blog_ID = '.$this->ID.' AND cat_meta = 0'
			         .$sql_order
			         .' LIMIT 1';

			$this->default_cat_ID = $DB->get_var( $sql, 0, 0, 'Get default category' );
		}

		return $this->default_cat_ID;
	}


	/**
	 * Get the blog's media directory (and create it if necessary).
	 *
	 * If we're {@link is_admin_page() on an admin page}, it adds status messages.
	 * @todo These status messages should rather go to a "syslog" and not be displayed to a normal user
	 * @todo dh> refactor this into e.g. create_media_dir() and use it for Blog::get_media_dir, too.
	 *
	 * @param boolean Create the directory, if it does not exist yet?
	 * @return string path string on success, false if the dir could not be created
	 */
	function get_media_dir( $create = true )
	{
		global $media_path, $current_User, $Messages, $Settings, $Debuglog;

		if( ! $Settings->get( 'fm_enable_roots_blog' ) )
		{ // User directories are disabled:
			$Debuglog->add( 'Attempt to access blog media dir, but this feature is globally disabled', 'files' );
			return false;
		}

		switch( $this->media_location )
		{
			case 'default':
				$mediadir = get_canonical_path( $media_path.'blogs/'.$this->urlname.'/' );
				break;

			case 'subdir':
				$mediadir = get_canonical_path( $media_path.$this->media_subdir );
				break;

			case 'custom':
				$mediadir = get_canonical_path( $this->media_fullpath );
				break;

			case 'none':
			default:
				$Debuglog->add( 'Attempt to access blog media dir, but this feature is disabled for this blog', 'files' );
				return false;
		}

		// TODO: use a File object here (to access perms, ..), using FileCache::get_by_root_and_path().
		if( $create && ! is_dir( $mediadir ) )
		{
			// Display absolute path to blog admin and relative path to everyone else
			$msg_mediadir_path = ( is_logged_in() && $current_User->check_perm( 'blog_admin', 'edit', false, $this->ID ) ) ? $mediadir : rel_path_to_base( $mediadir );

			// TODO: Link to some help page(s) with errors!
			if( ! is_writable( dirname($mediadir) ) )
			{ // add error
				if( is_admin_page() )
				{
					$Messages->add( sprintf( T_("The blog's media directory &laquo;%s&raquo; could not be created, because the parent directory is not writable or does not exist."), $msg_mediadir_path )
								.get_manual_link('media_file_permission_errors'), 'error' );
				}
				return false;
			}
			elseif( ! evo_mkdir( $mediadir ) )
			{ // add error
				if( is_admin_page() )
				{
					$Messages->add( sprintf( T_("The blog's media directory &laquo;%s&raquo; could not be created."), $msg_mediadir_path )
								.get_manual_link('directory_creation_error'), 'error' );
				}
				return false;
			}
			else
			{ // add note:
				if( is_admin_page() )
				{
					$Messages->add( sprintf( T_("The blog's media directory &laquo;%s&raquo; has been created with permissions %s."), $msg_mediadir_path, substr( sprintf('%o', fileperms($mediadir)), -3 ) ), 'success' );
				}
			}
		}

		return $mediadir;
	}


	/**
	 * Get the URL to the media folder
	 *
	 * @return string the URL
	 */
	function get_media_url()
	{
		global $media_subdir, $Settings, $Debuglog;

		if( ! $Settings->get( 'fm_enable_roots_blog' ) )
		{ // User directories are disabled:
			$Debuglog->add( 'Attempt to access blog media URL, but this feature is disabled', 'files' );
			return false;
		}

		switch( $this->media_location )
		{
			case 'default':
				return $this->get_local_media_url().'blogs/'.$this->urlname.'/';

			case 'subdir':
				return $this->get_local_media_url().$this->media_subdir;
				break;

			case 'custom':
				return $this->media_url;

			case 'none':
			default:
				$Debuglog->add( 'Attempt to access blog media url, but this feature is disabled for this blog', 'files' );
				return false;
		}
	}


	/**
 	 * Get link to edit files
 	 *
 	 * @param string link (false on error)
	 */
	function get_filemanager_link()
	{
		global $admin_url;

		load_class( '/files/model/_fileroot.class.php', 'FileRoot' );
		return $admin_url.'?ctrl=files&amp;root='.FileRoot::gen_ID( 'collection', $this->ID );
	}


	/**
	 * Get URL to display the blog with a temporary skin.
	 *
	 * This is used to construct the various RSS/Atom feeds
	 *
	 * @param string
	 * @param string
	 * @param boolean
	 */
	function get_tempskin_url( $skin_folder_name, $additional_params = '', $halt_on_error = false )
	{
		/**
		 * @var SkinCache
		 */
	 	$SkinCache = & get_SkinCache();
		if( ! $Skin = & $SkinCache->get_by_folder( $skin_folder_name, $halt_on_error ) )
		{
			return NULL;
		}

		return url_add_param( $this->gen_blogurl( 'default' ), 'tempskin='.$skin_folder_name );
	}


	/**
	 * Get URL to display the blog posts in an XML feed.
	 *
	 * @param string
	 */
	function get_item_feed_url( $skin_folder_name )
	{
		return $this->get_tempskin_url( $skin_folder_name );
	}


	/**
	 * Get URL to display the blog comments in an XML feed.
	 *
	 * @param string
	 */
	function get_comment_feed_url( $skin_folder_name )
	{
		return url_add_param( $this->get_tempskin_url( $skin_folder_name ), 'disp=comments' );
	}


	/**
	 * Callback function for footer_text()
	 * @param array
	 * @return string
	 */
	function replace_callback( $matches )
	{
		global $localtimenow;

		switch( $matches[1] )
		{
			case 'year':
				// for copyrigth year
				return date( 'Y', $localtimenow );

			case 'owner':
				/**
				 * @var User
				 */
				$owner_User = $this->get_owner_User();
				// Full name gets priority over prefered name because it makes more sense for DEFAULT copyright.
				// Blog owner can set WHATEVER footer text she wants through the admin interface.
				$owner = $owner_User->get( 'fullname' );
				if( empty($owner) )
				{
					$owner = $owner_User->get_preferred_name();
				}
				return $owner;

			default:
				return $matches[1];
		}
	}


	/**
	 * Get a param.
	 *
	 * @param string Parameter name
	 * @param array Additional params
	 * @return false|string The value as string or false in case of error (e.g. media dir is disabled).
	 */
	function get( $parname, $params = array() )
	{
		global $xmlsrv_url, $baseurl, $basepath, $media_url, $current_User, $Settings, $Debuglog;

		if( gettype( $params ) != 'array' )
		{
			debug_die('wrong $params');
		}

		$params = array_merge( array(
				'glue'       => '&amp;',
				'url_suffix' => '', // additional url params are appended at the end
			), $params );

		switch( $parname )
		{
			case 'blogurl':		// Deprecated
			case 'link':  		// Deprecated
			case 'url':
				return $this->gen_blogurl( 'default' );

			case 'baseurl':
				return $this->gen_baseurl();

			case 'baseurlroot':
				return $this->get_baseurl_root();

			case 'recentpostsurl':
				$disp_param = 'posts';
				break;

			case 'lastcommentsurl':
				$disp_param = 'comments';
				break;

			case 'searchurl':
				$disp_param = 'search';
				break;

			case 'arcdirurl':
				$disp_param = 'arcdir';
				break;

			case 'catdirurl':
				$disp_param = 'catdir';
				break;

			case 'postidxurl':
				$disp_param = 'postidx';
				break;

			case 'mediaidxurl':
				$disp_param = 'mediaidx';
				break;

			case 'sitemapurl':
				$disp_param = 'sitemap';
				break;

			case 'msgformurl':
				$disp_param = 'msgform';
				break;

			case 'profileurl':
				$disp_param = 'profile';
				break;

			case 'avatarurl':
				$disp_param = 'avatar';
				break;

			case 'pwdchangeurl':
				$disp_param = 'pwdchange';
				break;

			case 'userprefsurl':
				$disp_param = 'userprefs';
				break;

			case 'subsurl':
				$disp_param = 'subs';
				$params['url_suffix'] .= '#subs';
				break;

			case 'userurl':
				$disp_param = 'user';
				break;

			case 'usersurl':
				$disp_param = 'users';
				break;

			case 'loginurl':
			case 'registerurl':
			case 'lostpasswordurl':
			case 'activateinfourl':
			case 'access_requires_loginurl':
				$url_disp = str_replace( 'url', '', $parname );
				if( $login_Blog = & get_setting_Blog( 'login_blog_ID' ) )
				{ // Use special blog for login/register actions if it is defined in general settings
					return url_add_param( $login_Blog->gen_blogurl(), 'disp='.$url_disp, $params['glue'] );
				}
				else
				{ // Use login/register urls of this blog
					return url_add_param( $this->gen_blogurl(), 'disp='.$url_disp, $params['glue'] );
				}

			case 'threadsurl':
				$disp_param = 'threads';
				break;

			case 'messagesurl':
				$disp_param = 'messages';
				break;

			case 'contactsurl':
				$disp_param = 'contacts';
				break;

			case 'helpurl':
				if( $this->get_setting( 'help_link' ) == 'slug' )
				{
					return url_add_tail( $this->gen_blogurl(), '/help' );
				}
				else
				{
					$disp_param = 'help';
					break;
				}

			case 'skin_ID':
				return $this->get_skin_ID();

			case 'description':			// RSS wording
			case 'shortdesc':
				return $this->shortdesc;

			case 'rdf_url':
				return $this->get_item_feed_url( '_rdf' );

			case 'rss_url':
				return $this->get_item_feed_url( '_rss' );

			case 'rss2_url':
				return $this->get_item_feed_url( '_rss2' );

			case 'atom_url':
				return $this->get_item_feed_url( '_atom' );

			case 'comments_rdf_url':
				return $this->get_comment_feed_url( '_rdf' );

			case 'comments_rss_url':
				return $this->get_comment_feed_url( '_rss' );

			case 'comments_rss2_url':
				return $this->get_comment_feed_url( '_rss2' );

			case 'comments_atom_url':
				return $this->get_comment_feed_url( '_atom' );

			case 'rsd_url':
				return $this->get_local_xmlsrv_url().'rsd.php?blog='.$this->ID;

			/* Add the html for a blog-specified stylesheet
			 * All stylesheets will be included if the blog settings allow it
			 * and the file "style.css" exists. CSS rules say that the latter style sheets can
			 * override earlier stylesheets.
			 */
			case 'blog_css':
				if( $this->allowblogcss
					&& file_exists( $this->get_media_dir(false).'style.css' ) )
				{
					return '<link rel="stylesheet" href="'.$this->get_media_url().'style.css" type="text/css" />';
				}
				else
				{
					return '';
				}

			/* Add the html for a user-specified stylesheet
			 * All stylesheets will be included if the blog settings allow it
			 * and the file "style.css" exists. CSS rules say that the latter style sheets can
			 * override earlier stylesheets. A user-specified stylesheet will
			 * override a blog-specified stylesheet which will override a skin stylesheet.
			 */
			case 'user_css':
				if( $this->allowusercss
					&& isset( $current_User )
					&& file_exists( $current_User->get_media_dir(false).'style.css' ) )
				{
					return '<link rel="stylesheet" href="'.$current_User->get_media_url().'style.css" type="text/css" />';
				}
				else
				{
					return '';
				}


			default:
				// All other params:
				return parent::get( $parname );
		}

		if( ! empty( $disp_param ) )
		{ // Get url depending on value of param 'disp'
			$this_Blog = & $this;
			if( in_array( $disp_param, array( 'threads', 'messages', 'contacts', 'msgform', 'user', 'profile', 'avatar', 'pwdchange', 'userprefs', 'subs' ) ) )
			{ // Check if we can use this blog for messaging actions or we should use spec blog
				if( $msg_Blog = & get_setting_Blog( 'msg_blog_ID' ) )
				{ // Use special blog for messaging actions if it is defined in general settings
					$this_Blog = & $msg_Blog;
				}
			}

			if( $this_Blog->get_setting( 'front_disp' ) == $disp_param )
			{ // Get home page of this blog because front page displays current disp
				$url = $this_Blog->gen_blogurl( 'default' );
			}
			else
			{ // Add disp param to blog's url when current disp is not a front page
				$url = url_add_param( $this_Blog->gen_blogurl(), 'disp='.$disp_param, $params['glue'] );
			}
			if( ! empty( $params['url_suffix'] ) )
			{ // Append url suffix
				$url = url_add_param( $url, $params['url_suffix'], $params['glue'] );
			}
			return $url;
		}
	}


	/**
	 * Get warning message about the enabled advanced perms for those cases when we grant some permission for anonymous users which can be restricted for logged in users
	 *
	 * @return mixed NULL if advanced perms are not enabled in this blog, the warning message otherwise
	 */
	function get_advanced_perms_warning()
	{
		global $admin_url;

		if( $this->get( 'advanced_perms' ) )
		{
			$warning = T_('ATTENTION: advanced <a href="%s">user</a> & <a href="%s">group</a> permissions are enabled and some logged in users may have less permissions than anonymous users.');
			$advanced_perm_url = url_add_param( $admin_url, 'ctrl=coll_settings&amp;blog='.$this->ID.'&amp;tab=' );
			return ' <span class="warning">'.sprintf( $warning, $advanced_perm_url.'perm', $advanced_perm_url.'permgroup' ).'</span>';
		}

		return NULL;
	}


 	/**
	 * Get a setting.
	 *
	 * @param string setting name
	 * @param boolean true to return param's real value
	 * @return string|false|NULL value as string on success; NULL if not found; false in case of error
	 */
	function get_setting( $parname, $real_value = false )
	{
		global $Settings;

		$this->load_CollectionSettings();

		$result = $this->CollectionSettings->get( $this->ID, $parname );

		switch( $parname )
		{
			case 'normal_skin_ID':
				if( $result === NULL )
				{ // Try to get default from the global settings
					$result = $Settings->get( 'def_'.$parname );
				}
				break;

			case 'mobile_skin_ID':
			case 'tablet_skin_ID':
				if( $result === NULL )
				{ // Try to get default from the global settings
					$result = $Settings->get( 'def_'.$parname );
				}
				if( ( $result === '0' ) && ! $real_value )
				{ // 0 value means that use the same as normal case
					$result = $this->get_setting( 'normal_skin_ID' );
				}
				break;

			case 'moderation_statuses':
			case 'post_moderation_statuses':
				if( $result === NULL )
				{ // moderation_statuses was not set yet, set the default value, which depends from the blog type
					$default = 'review,draft';
					$result = ( $this->type == 'forum' ) ? 'community,protected,'.$default : $default;
				}
				break;

			case 'comment_inskin_statuses':
			case 'post_inskin_statuses':
				if( $result === NULL )
				{ // inskin_statuses was not set yet, set the default value, which depends from the blog type
					$default = 'published,community,protected,private,review';
					$result = ( $this->type == 'forum' ) ? $default.',draft' : $default;
				}
				break;

			case 'default_post_status':
			case 'new_feedback_status':
				if( $result === NULL )
				{ // Default post/comment status was not set yet, use a default value corresponding to the blog type
					$result = ( $this->type == 'forum' ) ? 'review' : 'draft';
				}
				break;
		}

		return $result;
	}


 	/**
	 * Get a ready-to-display setting from the DB settings table.
	 *
	 * Same as disp but don't echo
	 *
	 * @param string Name of setting
	 * @param string Output format, see {@link format_to_output()}
	 */
	function dget_setting( $parname, $format = 'htmlbody' )
	{
		$this->load_CollectionSettings();

		return format_to_output( $this->CollectionSettings->get( $this->ID, $parname ), $format );
	}


 	/**
	 * Display a setting from the DB settings table.
	 *
	 * @param string Name of setting
	 * @param string Output format, see {@link format_to_output()}
	 */
	function disp_setting( $parname, $format = 'htmlbody' )
	{
		$this->load_CollectionSettings();

		echo format_to_output( $this->CollectionSettings->get( $this->ID, $parname ), $format );
	}


 	/**
	 * Set a setting.
	 *
	 * @return boolean true, if the value has been set, false if it has not changed.
	 */
	function set_setting( $parname, $value, $make_null = false )
	{
	 	// Make sure collection settings are loaded
		$this->load_CollectionSettings();

		if( $make_null && empty($value) )
		{
			$value = NULL;
		}

		return $this->CollectionSettings->set( $this->ID, $parname, $value );
	}


	/**
	 * Delete a setting.
	 *
	 * @return boolean true, if the value has been set, false if it has not changed.
	 */
	function delete_setting( $parname )
	{
	 	// Make sure collection settings are loaded
		$this->load_CollectionSettings();

		return $this->CollectionSettings->delete( $this->ID, $parname );
	}


	/**
	 * Make sure collection settings are loaded.
	 * This keeps a single instance across all blogs.
	 * fp> why?
	 */
	function load_CollectionSettings()
	{
		static $instance; // fp> why do we need static? (it actually feels totally wrong: sharing settings between blogs!)

		if( ! isset($this->CollectionSettings) )
		{
			if( ! isset( $instance ) )
			{
				load_class( 'collections/model/_collsettings.class.php', 'CollectionSettings' );
				$instance = new CollectionSettings(); // COPY (function)
			}
			$this->CollectionSettings = $instance;
		}
	}


	/**
	 * Insert into the DB
	 */
	function dbinsert()
	{
		global $DB, $Plugins, $Settings;

		$DB->begin();

		if( $this->get( 'order' ) == 0 )
		{ // Set an order as max value of previous order + 1 if it is not defined yet
			$SQL = new SQL();
			$SQL->SELECT( 'MAX( blog_order )' );
			$SQL->FROM( 'T_blogs' );
			$max_order = intval( $DB->get_var( $SQL->get() ) );
			$this->set( 'order', $max_order + 1 );
		}

		$set_default_blog_ID = isset( $Settings );
		if( get_setting_Blog( 'default_blog_ID' ) )
		{ // Don't set a default blog if it is already defined and the blog exists in DB
			$set_default_blog_ID = false;
		}

		if( $set_default_blog_ID )
		{ // No default blog yet, Use for first base url as "Default collection on baseurl"
			$this->set( 'access_type', 'baseurl' );
		}
		else
		{ // For all other blogs use "Extra path on index.php"
			$this->set( 'access_type', 'extrapath' );
		}

		if( parent::dbinsert() )
		{
			if( $set_default_blog_ID )
			{ // Use this blog as default because it is probably first created
				$Settings->set( 'default_blog_ID', $this->ID );
				$Settings->dbupdate();
			}

			if( isset( $this->CollectionSettings ) )
			{
				// So far all settings have been saved to collection #0 !
				// Update the settings: hackish but the base class should not even store this value actually...
				// dh> what do you mean? What "base class"? Is there a problem with CollectionSettings?
				$this->CollectionSettings->cache[$this->ID] = $this->CollectionSettings->cache[0];
				unset( $this->CollectionSettings->cache[0] );

				$this->CollectionSettings->dbupdate();
			}

			$default_post_type_ID = $this->get_setting( 'default_post_type' );
			if( ! empty( $default_post_type_ID ) )
			{ // Enable post type that is used by default for this collection:
				global $DB;
				$DB->query( 'INSERT INTO T_items__type_coll
									 ( itc_ityp_ID, itc_coll_ID )
						VALUES ( '.$DB->quote( $default_post_type_ID ).', '.$DB->quote( $this->ID ).' )' );
			}

			$Plugins->trigger_event( 'AfterCollectionInsert', $params = array( 'Blog' => & $this ) );
		}

		$DB->commit();
	}


	/**
	 * Create a new blog...
	 *
	 * @param string Kind of blog ( 'std', 'photo', 'group', 'forum' )
	 */
	function create( $kind = '' )
	{
		global $DB, $Messages, $basepath, $admin_url, $current_User, $Settings;
		$DB->begin();

		// DB INSERT
		$this->dbinsert();

		$Messages->add( T_('The new blog has been created.'), 'success' );

		// Change access mode if a stub file exists:
		$stub_filename = 'blog'.$this->ID.'.php';
		if( is_file( $basepath.$stub_filename ) )
		{	// Stub file exists and is waiting ;)
			$DB->query( 'UPDATE T_blogs
						SET blog_access_type = "relative", blog_siteurl = "'.$stub_filename.'"
						WHERE blog_ID = '.$this->ID );
			$Messages->add( sprintf(T_('The new blog has been associated with the stub file &laquo;%s&raquo;.'), $stub_filename ), 'success' );
		}
		elseif( $this->access_type == 'relative' )
		{ // Show error message only if stub file should exists!
			$Messages->add( sprintf(T_('No stub file named &laquo;%s&raquo; was found. You must create it for the blog to function properly with the current settings.'), $stub_filename ), 'error' );
		}

		// Set default user permissions for this blog (All permissions for the current user, typically the admin who is creating the blog)
		// Note: current_User can be NULL only during new user registration process, when new user automatically get a new blog
		// Note: The owner of teh blog has permissions just by the sole fact he is registered as the owner.
		if( $current_User != NULL )
		{ // Proceed insertions:
			$perm_statuses = "'review,draft,private,protected,deprecated,community,published'";
			$DB->query( "
					INSERT INTO T_coll_user_perms( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember,
						bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_edit_ts,
						bloguser_perm_recycle_owncmts, bloguser_perm_vote_spam_cmts, bloguser_perm_cmtstatuses,
						bloguser_perm_cats, bloguser_perm_properties,
						bloguser_perm_media_upload, bloguser_perm_media_browse, bloguser_perm_media_change )
					VALUES ( $this->ID, $current_User->ID, 1,
						$perm_statuses, 1, 1, 1, 1, $perm_statuses, 1, 1, 1, 1, 1 )" );
		}

		/*
		if( $kind == 'forum' )
		{	// Set default group permissions for the Forum blog
			$GroupCache = & get_GroupCache();
			$groups_permissions = array();
			if( $GroupCache->get_by_ID( 1, false ) )
			{	// Check if "Administrators" group still exists
				$groups_permissions[ 'admins' ] = "( $this->ID, 1, 1, 'published,deprecated,protected,private,draft', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1 )";
			}
			if( $GroupCache->get_by_ID( 2, false ) )
			{	// Check if "Moderators" group still exists
				$groups_permissions[ 'privileged' ] = "( $this->ID, 2, 1, 'published,deprecated,protected,private,draft', 1, 0, 1, 1, 1, 1, 1, 0, 0, 1, 1, 1 )";
			}
			if( $GroupCache->get_by_ID( 3, false ) )
			{	// Check if "Bloggers" group still exists
				$groups_permissions[ 'bloggers' ] = "( $this->ID, 3, 1, 'published,deprecated,protected,private,draft', 0, 0, 0, 1, 0, 0, 0, 0, 0, 1, 1, 0 )";
			}
			if( $GroupCache->get_by_ID( 4, false ) )
			{	// Check if "Basic Users" group still exists
				$groups_permissions[ 'users' ] = "( $this->ID, 4, 1, 'published', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 )";
			}
			if( $GroupCache->get_by_ID( 5, false ) )
			{	// Check if "Spam/Suspect Users" group still exists
				$groups_permissions[ 'spam' ] = "( $this->ID, 5, 1, 'published,deprecated,protected,private,draft', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 )";
			}
			$DB->query( 'INSERT INTO T_coll_group_perms( bloggroup_blog_ID, bloggroup_group_ID, bloggroup_ismember,
					bloggroup_perm_poststatuses, bloggroup_perm_delpost, bloggroup_perm_edit_ts,
					bloggroup_perm_recycle_owncmts, bloggroup_perm_vote_spam_cmts, bloggroup_perm_draft_cmts, bloggroup_perm_publ_cmts, bloggroup_perm_depr_cmts,
					bloggroup_perm_cats, bloggroup_perm_properties,
					bloggroup_perm_media_upload, bloggroup_perm_media_browse, bloggroup_perm_media_change )
				VALUES '.implode( ',', $groups_permissions ) );
		}*/

		// Create default category:
		load_class( 'chapters/model/_chapter.class.php', 'Chapter' );
		$edited_Chapter = new Chapter( NULL, $this->ID );

		$blog_urlname = $this->get( 'urlname' );
		$edited_Chapter->set( 'name', T_('Uncategorized') );
		$edited_Chapter->set( 'urlname', $blog_urlname.'-main' );
		$edited_Chapter->dbinsert();

		$Messages->add( T_('A default category has been created for this blog.'), 'success' );

		// ADD DEFAULT WIDGETS:
		load_funcs( 'widgets/_widgets.funcs.php' );
		insert_basic_widgets( $this->ID, false, $kind );

		$Messages->add( T_('Default widgets have been set-up for this blog.'), 'success' );

		$DB->commit();

		// set caching
		if( $Settings->get( 'newblog_cache_enabled' ) )
		{
			$result = set_cache_enabled( 'cache_enabled', true, $this->ID );
			if( $result != NULL )
			{
				list( $status, $message ) = $result;
				$Messages->add( $message, $status );
			}
		}
		$this->set_setting( 'cache_enabled_widgets', $Settings->get( 'newblog_cache_enabled_widget' ) );

		if( $this->get( 'advanced_perms' ) )
		{	// Display this warning if blog has the enabled advanced perms be default
			$Messages->add( sprintf(T_('ATTENTION: go to the <a %s>advanced group permissions for this blog/forum</a> in order to allow some user groups to post new topics into this forum.'), 'href='.$admin_url.'?ctrl=coll_settings&amp;tab=permgroup&amp;blog='.$this->ID ), 'warning' );
		}

		// Commit changes in cache:
		$BlogCache = & get_BlogCache();
		$BlogCache->add( $this );
	}


	/**
	 * Update the DB based on previously recorded changes
	 */
	function dbupdate()
	{
		global $DB, $Plugins, $servertimenow;

		$DB->begin();

		parent::dbupdate();

		// if this blog settings was modified we need to invalidate this blog's page caches
		// this way all existing cached page on this blog will be regenerated during next display
		// TODO: Ideally we want to detect if the changes are minor/irrelevant to caching and not invalidate the page cache if not necessary.
		// In case of doubt (and for unknown changes), it's better to invalidate.
		$this->set_setting( 'last_invalidation_timestamp', $servertimenow );

		if( isset( $this->CollectionSettings ) )
		{
			$this->CollectionSettings->dbupdate();
		}

		$Plugins->trigger_event( 'AfterCollectionUpdate', $params = array( 'Blog' => & $this ) );

		$DB->commit();

		// BLOCK CACHE INVALIDATION:
		BlockCache::invalidate_key( 'set_coll_ID', $this->ID ); // Settings have changed
		BlockCache::invalidate_key( 'set_coll_ID', 'any' ); // Settings of a have changed (for widgets tracking a change on ANY blog)

		// cont_coll_ID  // Content has not changed
	}


	/**
	 * Delete a blog and dependencies from database
	 *
	 * @param boolean true if you want to echo progress
	 */
	function dbdelete( $echo = false )
	{
		global $DB, $Messages, $Plugins, $Settings;

		// Try to obtain some serious time to do some serious processing (5 minutes)
		set_max_execution_time(300);

		if( $echo ) echo 'Delete collection with all of it\'s content... ';

		// remember ID, because parent method resets it to 0
		$old_ID = $this->ID;

		// Delete main (blog) object:
		if( ! parent::dbdelete() )
		{
			$Messages->add( 'Blog has not been deleted.', 'error' );
			return false;
		}

		// Delete the blog cache folder - try to delete even if cache is disabled
		load_class( '_core/model/_pagecache.class.php', 'PageCache' );
		$PageCache = new PageCache( $this );
		$PageCache->cache_delete();

		// Delete blog's media folder recursively:
		$FileRootCache = & get_FileRootCache();
		if( $root_directory = $FileRootCache->get_root_dir( 'collection', $old_ID ) )
		{ // Delete the folder only when it is detected
			rmdir_r( $root_directory );
			$Messages->add( T_('Deleted blog\'s files'), 'success' );
		}

		// re-set the ID for the Plugin event
		$this->ID = $old_ID;
		$Plugins->trigger_event( 'AfterCollectionDelete', $params = array( 'Blog' => & $this ) );
		$this->ID = 0;

		if( isset( $Settings ) )
		{ // Reset settings related to the deleted blog
			if( $Settings->get( 'default_blog_ID' ) == $old_ID )
			{ // Reset default blog ID
				$Settings->set( 'default_blog_ID', 0 );
			}
			if( $Settings->get( 'info_blog_ID' ) == $old_ID )
			{ // Reset info blog ID
				$Settings->set( 'info_blog_ID', 0 );
			}
			if( $Settings->get( 'login_blog_ID' ) == $old_ID )
			{ // Reset login blog ID
				$Settings->set( 'login_blog_ID', 0 );
			}
			if( $Settings->get( 'msg_blog_ID' ) == $old_ID )
			{ // Reset messaging blog ID
				$Settings->set( 'msg_blog_ID', 0 );
			}
			$Settings->dbupdate();
		}

		if( $echo ) echo '<br />Done.</p>';

		return true;
	}


	/*
	 * Template function: display name of blog
	 *
	 * Template tag
	 */
	function name( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => ' ',
				'after'       => ' ',
				'format'      => 'htmlbody',
			), $params );

		if( !empty( $this->name ) )
		{
			echo $params['before'];
			$this->disp( 'name', $params['format'] );
			echo $params['after'];
		}
	}


	/*
	 * Template function: display name of blog
	 *
	 * Template tag
	 */
	function tagline( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => ' ',
				'after'       => ' ',
				'format'      => 'htmlbody',
			), $params );

		if( !empty( $this->tagline ) )
		{
			echo $params['before'];
			$this->disp( 'tagline', $params['format'] );
			echo $params['after'];
		}
	}


	/*
	 * Template function: display name of blog
	 *
	 * Template tag
	 */
	function longdesc( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => ' ',
				'after'       => ' ',
				'format'      => 'htmlbody',
			), $params );

		if( !empty( $this->longdesc ) )
		{
			echo $params['before'];
			$this->disp( 'longdesc', $params['format'] );
			echo $params['after'];
		}
	}


	/**
	 * Get the name of the blog
	 *
	 * @return string
	 */
	function get_name()
	{
		return $this->name;
	}

	/**
	 * Get the name of the blog limited by length
	 *
	 * @param integer Max length
	 * @return string Limited name
	 */
	function get_maxlen_name( $maxlen = 50 )
	{
		return strmaxlen( $this->get_name(), $maxlen, NULL, 'raw' );
	}


	/**
	 * Get short and long name of the blog
	 *
	 * @return string
	 */
	function get_extended_name()
	{
		$names = array();

		if( ! empty( $this->shortname ) )
		{ // Short name
			$names[] = $this->shortname;
		}

		if( ! empty( $this->name ) )
		{ // Title
			$names[] = $this->name;
		}

		return implode( ' - ', $names );
	}


	/*
	 * Get the blog skin ID which correspond to the current session device or which correspond to the selected skin type
	 *
	 * @param string Skin type: 'auto', 'normal', 'mobile', 'tablet'
	 * @return integer skin ID
	 */
	function get_skin_ID( $skin_type = 'auto' )
	{
		switch( $skin_type )
		{
			case 'auto':
				// Auto detect skin by session type
				global $Session;
				if( ! empty( $Session ) )
				{
					if( $Session->is_mobile_session() )
					{
						return $this->get_setting( 'mobile_skin_ID' );
					}
					if( $Session->is_tablet_session() )
					{
						return $this->get_setting( 'tablet_skin_ID' );
					}
				}
				return $this->get_setting( 'normal_skin_ID' );

			case 'normal':
				// Normal skin
				return $this->get_setting( 'normal_skin_ID' );

			case 'mobile':
				// Mobile skin
				return $this->get_setting( 'mobile_skin_ID' );

			case 'tablet':
				// Tablet skin
				return $this->get_setting( 'tablet_skin_ID' );
		}

		// Deny to request invalid skin types
		debug_die( 'The requested skin type is invalid.' );
	}


	/**
	 * Get skin folder/name by skin type
	 *
	 * @param string Force session type: 'auto', 'normal', 'mobile', 'tablet'
	 * @return string Skin folder/name
	 */
	function get_skin_folder( $skin_type = 'auto' )
	{
		$blog_skin_ID = $this->get_skin_ID( $skin_type );
		if( empty( $blog_skin_ID ) && $skin_type != 'auto' )
		{ // Get default skin ID if it is not defined for the selected session type yet
			$blog_skin_ID = $this->get_skin_ID( 'auto' );
		}

		$SkinCache = & get_SkinCache();
		$Skin = & $SkinCache->get_by_ID( $blog_skin_ID );

		return $Skin->folder;
	}


	/**
	 * Resolve user ID of owner
	 *
	 * @return User
	 */
	function & get_owner_User()
	{
		if( !isset($this->owner_User) )
		{
			$UserCache = & get_UserCache();
			$this->owner_User = & $UserCache->get_by_ID($this->owner_user_ID);
		}

		return $this->owner_User;
	}


	/**
	 * Template tag: display a link leading to the contact form for the owner of the current Blog.
	 *
	 * @param array (empty default array is provided for compatibility with v 1.10)
	 */
	function contact_link( $params = array() )
	{
		$owner_User = & $this->get_owner_User();
		if( ! $owner_User->get_msgform_possibility() )
		{
			return false;
		}

		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => ' ',
				'after'       => ' ',
				'text'        => 'Contact', // Note: left untranslated, should be translated in skin anyway
				'title'       => 'Send a message to the owner of this blog...',
			), $params );


		echo $params['before'];
		echo '<a href="'.$this->get_contact_url(true).'" title="'.$params['title'].'" class="contact_link">'
					.$params['text'].'</a>';
		echo $params['after'];

		return true;
	}


	/**
	 * Template tag: display a link leading to the help page
	 *
	 * @param array
	 */
	function help_link( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => ' ',
				'after'       => ' ',
				'text'        => 'Help', // Note: left untranslated, should be translated in skin anyway
				'title'       => '',
			), $params );


		echo $params['before'];
		echo '<a href="'.$this->get('helpurl').'" title="'.$params['title'].'" class="help_link">'
					.$params['text'].'</a>';
		echo $params['after'];

		return true;
	}


	/**
	 * Template tag: display footer text for the current Blog.
	 *
	 * @param array
	 * @return boolean true if something has been displayed
	 */
	function footer_text( $params )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => ' ',
				'after'       => ' ',
			), $params );

		$text = $this->get_setting( 'blog_footer_text' );
		$text = preg_replace_callback( '~\$([a-z]+)\$~', array( $this, 'replace_callback' ), $text );

		if( empty($text) )
		{
			return false;
		}

		echo $params['before'];
		echo $text;
		echo $params['after'];

		return true;
	}


	/**
	 * Get URL of message form to contact the owner
	 *
	 * @param boolean do we want to redirect back to where we came from after message?
	 */
	function get_contact_url( $with_redirect = true )
	{
		$owner_User = & $this->get_owner_User();
		if( ! $owner_User->get_msgform_possibility() )
		{ // user does not allow contact form
			return NULL;
		}

		$blog_contact_url = $this->get( 'msgformurl', array(
				'url_suffix' => 'recipient_id='.$owner_User->ID
			) );

		if( $with_redirect )
		{
			if( $owner_User->get_msgform_possibility() != 'login' )
			{
				$blog_contact_url = url_add_param( $blog_contact_url, 'redirect_to='
					// The URL will be made relative on the next page (this is needed when $htsrv_url is on another domain! -- multiblog situation )
					.rawurlencode( regenerate_url('','','','&') ) );
			}
			else
			{ // no email option - try to log in and send private message (only registered users can send PM)
				$blog_contact_url = url_add_param( $blog_contact_url, 'redirect_to='.rawurlencode( url_add_param( $this->gen_blogurl(), 'disp=msgform', '&' ) ) );
			}
		}

		return $blog_contact_url;
	}


	/**
	 * Get aggregate coll IDs
	 *
	 * @return integer|NULL|array
	 *  - current blog ID if no aggreation
	 *  - NULL if aggreagation of all blogs
	 *  - array of aggreagated blog IDs
	 */
	function get_aggregate_coll_IDs()
	{
		$aggregate_coll_IDs = $this->get_setting( 'aggregate_coll_IDs' );

		if( empty( $aggregate_coll_IDs ) || $aggregate_coll_IDs == '-' )
		{ // No aggregation, return only the current blog:
			return $this->ID;
		}
		elseif( $aggregate_coll_IDs == '*' )
		{ // Aggregation of all blogs
			return NULL;
		}

		return explode( ',', $aggregate_coll_IDs );
	}


	/**
	 * Get SQL expression to match the list of aggregates collection IDs.
	 *
	 * This resolves as follows:
	 *  - empty: current blog only
	 *  - "*": all blogs (returns " 1 " as in "WHERE 1")
	 *  - other: as present in DB
	 *
	 * @param string SQL field name
	 * @param string Force current blog setting 'aggregate_coll_IDs'
	 * @return string e.g. "$field IN (1,5)". It will return " 1 ", when all blogs/cats are aggregated.
	 */
	function get_sql_where_aggregate_coll_IDs( $field, $force_coll_IDs = NULL )
	{
		if( is_null( $force_coll_IDs ) )
		{ // Use collection IDs from blog setting
			$aggregate_coll_IDs = $this->get_setting( 'aggregate_coll_IDs' );
		}
		else
		{ // Force collections IDs
			$aggregate_coll_IDs = $force_coll_IDs;
		}

		if( empty( $aggregate_coll_IDs ) || $aggregate_coll_IDs == '-' )
		{ // We only want posts from the current blog:
			return ' '.$field.' = '.$this->ID.' ';
		}
		elseif( $aggregate_coll_IDs == '*' )
		{ // We are aggregating all blogs
			return ' 1 ';
		}
		else
		{ // We are aggregating posts from several blogs:
			global $DB;
			return ' '.$field.' IN ( '.$DB->quote( explode( ',', $aggregate_coll_IDs ) ).' )';
		}
	}


	/**
	 * Get # of posts for a given tag
	 */
	function get_tag_post_count( $tag )
	{
		global $DB;

		$sql = 'SELECT COUNT(DISTINCT itag_itm_ID)
						  FROM T_items__tag INNER JOIN T_items__itemtag ON itag_tag_ID = tag_ID
					  				INNER JOIN T_postcats ON itag_itm_ID = postcat_post_ID
					  				INNER JOIN T_categories ON postcat_cat_ID = cat_ID
						 WHERE cat_blog_ID = '.$this->ID.'
						 	 AND tag_name = '.$DB->quote( utf8_strtolower($tag) );

		return $DB->get_var( $sql );

	}


	/**
	 * Get the number of items in this collection
	 * Note: It counts all items with any kind of visibility status
	 *
	 * @return integer the number of items
	 */
	function get_number_of_items()
	{
		global $DB;

		$sql = 'SELECT COUNT(post_ID)
				FROM T_items__item WHERE post_main_cat_ID IN (
					SELECT cat_ID FROM T_categories
					WHERE cat_blog_ID = '.$this->ID.' )';

		return $DB->get_var( $sql );
	}


	/**
	 * Get the number of comments in this collection
	 * Note: It counts all comments with any kind of visibility status, except comments in trash
	 *
	 * @return integer the number of not recycled comments
	 */
	function get_number_of_comments()
	{
		global $DB;

		$sql = 'SELECT COUNT(comment_ID)
				FROM T_comments
				LEFT JOIN T_items__item ON comment_item_ID = post_ID
				WHERE comment_status != "trash" AND post_main_cat_ID IN (
					SELECT cat_ID FROM T_categories
					WHERE cat_blog_ID = '.$this->ID.' )';

		return $DB->get_var( $sql );
	}


	/**
	 * Get the corresponding ajax form enabled setting
	 *
	 * @return boolean true if ajax form is enabled, false otherwise
	 */
	function get_ajax_form_enabled()
	{
		if( is_logged_in() )
		{
			return $this->get_setting( 'ajax_form_loggedin_enabled' );
		}
		return $this->get_setting( 'ajax_form_enabled' );
	}


	/**
	 * Get timestamp value from the setting "timestamp_min"
	 *
	 * @return string
	 */
	function get_timestamp_min()
	{
		$timestamp_min = $this->get_setting( 'timestamp_min' );

		switch ( $timestamp_min )
		{
			case 'duration': // Set timestamp to show past posts after this date
				$timestamp_value = time() - $this->get_setting( 'timestamp_min_duration' );
				break;
			case 'no': // Don't show past posts
				$timestamp_value = 'now';
				break;
			case 'yes': // Show all past posts
			default:
				$timestamp_value = '';
				break;
		}

		return $timestamp_value;
	}


	/**
	 * Get timestamp value from the setting "timestamp_max"
	 *
	 * @return string
	 */
	function get_timestamp_max()
	{
		$timestamp_max = $this->get_setting( 'timestamp_max' );

		switch ( $timestamp_max )
		{
			case 'duration': // Set timestamp to show future posts before this date
				$timestamp_value = time() + $this->get_setting( 'timestamp_max_duration' );
				break;
			case 'yes': // Show all future posts
				$timestamp_value = '';
				break;
			case 'no': // Don't show future posts
			default:
				$timestamp_value = 'now';
				break;
		}

		return $timestamp_value;
	}


	/**
	 * Get url to write a new Post
	 *
	 * @param integer Category ID
	 * @param string Post title
	 * @param string Post urltitle
	 * @param integer Post type ID
	 * @return string Url to write a new Post
	 */
	function get_write_item_url( $cat_ID = 0, $post_title = '', $post_urltitle = '', $post_type_ID = 0 )
	{
		$url = '';

		if( is_logged_in( false ) )
		{	// Only logged in and activated users can write a Post
			global $current_User;

			$ChapterCache = & get_ChapterCache();
			$selected_Chapter = $ChapterCache->get_by_ID( $cat_ID, false, false );
			if( $selected_Chapter && $selected_Chapter->lock )
			{ // This category is locked, don't allow to create new post with this cat
				return '';
			}
			if( $current_User->check_perm( 'blog_post_statuses', 'edit', false, $this->ID ) )
			{	// We have permission to add a post with at least one status:
				if( $this->get_setting( 'in_skin_editing' ) && ! is_admin_page() )
				{	// We have a mode 'In-skin editing' for the current Blog
					// User must have a permission to publish a post in this blog
					$cat_url_param = '';
					if( $cat_ID > 0 )
					{	// Link to create a Item with predefined category
						$cat_url_param = '&amp;cat='.$cat_ID;
					}
					$url = url_add_param( $this->get( 'url' ), 'disp=edit'.$cat_url_param );
				}
				elseif( $current_User->check_perm( 'admin', 'restricted' ) )
				{	// Edit a post from Back-office
					global $admin_url;
					$url = $admin_url.'?ctrl=items&amp;action=new&amp;blog='.$this->ID;
					if( !empty( $cat_ID ) )
					{	// Add category param to preselect category on the form
						$url = url_add_param( $url, 'cat='.$cat_ID );
					}
				}

				if( !empty( $post_title ) )
				{ // Append a post title
					$url = url_add_param( $url, 'post_title='.$post_title );
				}
				if( !empty( $post_urltitle ) )
				{ // Append a post urltitle
					$url = url_add_param( $url, 'post_urltitle='.$post_urltitle );
				}
				if( !empty( $post_type_ID ) )
				{ // Append a post type ID
					$url = url_add_param( $url, 'item_typ_ID='.$post_type_ID );
				}
			}
		}

		return $url;
	}


	/**
	 * Get url to create a new Chapter
	 *
	 * @param integer Parent category ID
	 * @return string Url to create a new Chapter
	 */
	function get_create_chapter_url( $cat_ID = 0 )
	{
		$url = '';

		if( is_logged_in( false ) )
		{	// Only logged in and activated users can write a Post
			global $current_User;

			if( $current_User->check_perm( 'admin', 'restricted' ) &&
			    $current_User->check_perm( 'blog_cats', 'edit', false, $this->ID ) )
			{	// Check permissions to create a new chapter in this blog
				global $admin_url;
				$url = $admin_url.'?ctrl=chapters&amp;action=new&amp;blog='.$this->ID;
				if( !empty( $cat_ID ) )
				{	// Add category param to preselect category on the form
					$url = url_add_param( $url, 'cat_parent_ID='.$cat_ID );
				}
			}
		}

		return $url;
	}


	/**
	 * Check if current user has access to this blog depending on settings
	 *
	 * @return boolean TRUE on success
	 */
	function check_access()
	{
		global $Messages, $skins_path, $ads_current_skin_path, $ReqURL, $disp;

		$allow_access = $this->get_setting( 'allow_access' );

		if( $allow_access == 'public' )
		{ // Everyone has an access to this blog
			return true;
		}

		if( in_array( $disp, array( 'login', 'lostpassword', 'register', 'help', 'msgform', 'access_requires_login' ) ) )
		{ // Don't restrict these pages
			return true;
		}

		/**
		 * $allow_access == 'users' || 'members'
		 */
		if( ! is_logged_in() )
		{ // Only logged in users have an access to this blog
			$Messages->add( T_( 'You need to log in before you can access this section.' ), 'error' );

			$login_Blog = & get_setting_Blog( 'login_blog_ID' );
			if( $login_Blog && $login_Blog->ID != $this->ID )
			{	// If this collection is not used for login actions,
				// Redirect to login form on "access_requires_login.main.php":
				header_redirect( get_login_url( 'no access to blog', NULL, false, NULL, 'access_requires_loginurl' ), 302 );
				// will have exited
			}
			else
			{	// This collection is used for login actions
				// Don't redirect, just display a login form of this collection:
				$disp = 'access_requires_login';
				// Set redirect_to param to current url in order to display a requested page after login action:
				global $ReqURI;
				param( 'redirect_to', 'url', $ReqURI );
			}
		}
		elseif( $allow_access == 'members' )
		{ // Check if current user is member of this blog
			global $current_User;

			if( ! $current_User->check_perm( 'blog_ismember', 'view', false, $this->ID ) )
			{ // Force disp to restrict access for current user
				$disp = 'access_denied';

				$Messages->add( T_( 'You are not a member of this section, therefore you are not allowed to access it.' ), 'error' );

				$blog_skin_ID = $this->get_skin_ID();
				if( ! empty( $blog_skin_ID ) )
				{
					// Use 'access_denied.main.php' instead of real template when current User is not a member of this blog
					$template = 'access_denied.main.php';

					$SkinCache = & get_SkinCache();
					$Skin = & $SkinCache->get_by_ID( $blog_skin_ID );
					$ads_current_skin_path = $skins_path.$Skin->folder.'/';
					$skin_template_name = $ads_current_skin_path.$template;
					if( file_exists( $skin_template_name ) )
					{ // Display a special template of this skin
						require $skin_template_name;
						exit;
					}
					else
					{ // Display a template from site skins
						siteskin_include( $template );
						exit;
					}
				}
			}
		}

		return true;
	}


	/**
	 * Check if the media directory or it's location was changed and perform the required data migration
	 *
	 * @param string the media directory path before update
	 * @param string the media directory location before update
	 * @return boolean true if the media directory was not changed or the change was successful, false otherwise
	 */
	function check_media_dir_change( $old_media_dir, $old_media_location = NULL )
	{
		global $Messages;

		$new_media_dir = $this->get_media_dir( false );
		if( $new_media_dir == $old_media_dir )
		{ // The media dir was not changed, no need fo further updates
			return true;
		}

		$new_media_location = $this->get( 'media_location' );
		if( $old_media_location == NULL )
		{ // Media location was not changed
			$old_media_location = $new_media_location;
		}

		switch( $new_media_location )
		{
			case 'none':
				if( is_empty_directory( $old_media_dir ) )
				{ // Delete old media dir if it is empty
					if( file_exists( $old_media_dir ) && ( ! rmdir_r( $old_media_dir ) ) )
					{
						$Messages->add( T_('The old media dir could not be removed, please remove it manually!'), 'warning' );
					}
				}
				else
				{ // The old media dir is not empty, but it must be cleared before it can be changed to none
					$Messages->add( T_('Blog media folder is not empty, you cannot change it to "None".'), 'error' );
					return false;
				}
				break;

			case 'default':
			case 'subdir':
			case 'custom':
				global $media_path;
				if( file_exists( $new_media_dir ) )
				{ // Don't use the existing folder twice
					$Messages->add( sprintf( T_('Folder %s already exists, it cannot be used for several media locations.'), '<b>'.$new_media_dir.'</b>' ), 'error' );
					return false;
				}
				if( in_array( trim( $new_media_dir, '/\\' ), array( $media_path.'blogs', $media_path.'import', $media_path.'shared', $media_path.'users' ) ) )
				{ // Don't use the reserved paths
					$Messages->add( sprintf( T_('Please use another folder name, because %s is reserved.'), '<b>'.$new_media_dir.'</b>' ), 'error' );
					return false;
				}
				if( $new_media_location == 'custom' )
				{ // Check for folder is not used by other blog, and it is not a sub-folder of other blog folder
					$BlogCache = & get_BlogCache();
					$BlogCache->clear( true );
					$BlogCache->load_where( 'blog_ID != '.$this->ID );
					$other_blog_IDs = $BlogCache->get_ID_array();
					foreach( $other_blog_IDs as $other_blog_ID )
					{
						$other_Blog = & $BlogCache->get_by_ID( $other_blog_ID, false, false );
						$other_media_dir = $other_Blog->get_media_dir( false );
						if( ! empty( $other_media_dir ) && strpos( $new_media_dir, $other_media_dir ) === 0 )
						{
							$Messages->add( sprintf( T_('Please use another folder name, because %s is already used for another media location.'), '<b>'.$new_media_dir.'</b>' ), 'error' );
							return false;
						}
					}
				}
				if( ( $old_media_location == 'none' ) || ( ! file_exists( $old_media_dir ) ) )
				{ // The media folder was not used before, create the new media folder
					return $this->get_media_dir( true );
				}
				if( copy_r( $old_media_dir, $new_media_dir, '', array( '_evocache', '.evocache' ) ) )
				{ // The file have been copied to new folder successfully
					if( ! rmdir_r( $old_media_dir ) )
					{ // Display a warning if old folder could not be deleted
						$Messages->add( sprintf( T_('Could not delete the old media folder "%s", please try to delete it manually.'), '<b>'.$old_media_dir.'</b>' ), 'warning' );
					}
				}
				else
				{ // Display a message if some error on copying
					$Messages->add( sprintf( T_('Could not move the media folder content from "%s" to the new "%s" location.'), '<b>'.$old_media_dir.'</b>', '<b>'.$new_media_dir.'</b>' ), 'error' );
					return false;
				}
				break;

			default:
				debug_die('Invalid media location setting received!');
		}

		$Messages->add( T_('The media directory and all of its content were successfully moved to the new location.'), 'note' );
		return true;
	}


	/**
	 * Get all enabled item types for this collection
	 *
	 * @return array
	 */
	function get_enabled_item_types()
	{
		if( empty( $this->ID ) )
		{ // This is new blog, it doesn't have the enabled item types
			return array();
		}

		if( ! isset( $this->enabled_item_types ) )
		{ // Get all enabled item types by one sql query and only first time to cache result:
			global $DB;

			$SQL = new SQL();
			$SQL->SELECT( 'itc_ityp_ID' );
			$SQL->FROM( 'T_items__type_coll' );
			$SQL->WHERE( 'itc_coll_ID = '.$this->ID );

			$this->enabled_item_types = $DB->get_col( $SQL->get() );
		}

		return $this->enabled_item_types;
	}


	/**
	 * Check if item type is enabled for this collection
	 *
	 * @param integer Item type ID
	 * @return boolean TRUE if enabled
	 */
	function is_item_type_enabled( $item_type_ID )
	{
		if( empty( $this->ID ) )
		{ // This is new blog, it doesn't have the enabled item types
			return false;
		}

		return in_array( $item_type_ID, $this->get_enabled_item_types() );
	}


	/**
	 * Check if item type can be disabled
	 *
	 * @param integer Item type ID
	 * @param boolean TRUE to display a message about restriction
	 * @return boolean TRUE if can
	 */
	function can_be_item_type_disabled( $item_type_ID, $display_message = false )
	{
		if( empty( $this->ID ) )
		{ // This is new blog, no restriction to disable any post type
			return true;
		}

		if( $this->get_setting( 'default_post_type' ) == $item_type_ID )
		{ // Don't allow to disable an item type which is used as default for this collection:
			if( $display_message )
			{
				global $Messages;
				$Messages->add( 'This post type is used as default for this collection. Thus you cannot disable it.', 'error' );
			}
			return false;
		}

		if( ! isset( $this->used_item_types ) )
		{ // Get all item types that are used for posts in this collection:
			global $DB;

			$coll_item_types_SQL = new SQL();
			$coll_item_types_SQL->SELECT( 'post_ityp_ID' );
			$coll_item_types_SQL->FROM( 'T_items__item' );
			$coll_item_types_SQL->FROM_add( 'INNER JOIN T_categories ON post_main_cat_ID = cat_ID' );
			$coll_item_types_SQL->WHERE( 'cat_blog_ID = '.$this->ID );
			$coll_item_types_SQL->GROUP_BY( 'post_ityp_ID' );

			$this->used_item_types = $DB->get_col( $coll_item_types_SQL->get() );
		}

		if( ! empty( $this->used_item_types ) && in_array( $item_type_ID, $this->used_item_types ) )
		{ // Don't allow to disable an item type which is used at least for one post in this collection:
			if( $display_message )
			{
				global $Messages;
				$Messages->add( 'This post type is used at least for one post in this collection. Thus you cannot disable it.', 'error' );
			}
			return false;
		}

		return true;
	}
}

?>