<?php
/**
 * This file implements the Blog class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005 by Jason Edgecombe.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Jason EDGECOMBE grants Francois PLANQUE the right to license
 * Jason EDGECOMBE's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author gorgeb: Bertrand GORGE / EPISTEMA
 * @author jeffbearer: Jeff BEARER
 * @author edgester: Jason EDGECOMBE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class('_core/model/dataobjects/_dataobject.class.php');

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
	var $allowcomments = 'post_by_post';
	var $allowtrackbacks = 0;
	var $allowblogcss = 0;
	var $allowusercss = 0;
	var $skin_ID;
	var $in_bloglist = 1;
	var $UID;
	var $media_location = 'default';
	var $media_subdir = '';
	var $media_fullpath = '';
	var $media_url = '';

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
	 * Constructor
	 *
	 * @param object DB row
	 */
	function Blog( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_blogs', 'blog_', 'blog_ID' );

		$this->delete_restrictions = array(
				array( 'table'=>'T_categories', 'fk'=>'cat_blog_ID', 'msg'=>T_('%d related categories') ),
			);

		$this->delete_cascades = array(
				array( 'table'=>'T_coll_user_perms', 'fk'=>'bloguser_blog_ID', 'msg'=>T_('%d user permission definitions') ),
				array( 'table'=>'T_coll_group_perms', 'fk'=>'bloggroup_blog_ID', 'msg'=>T_('%d group permission definitions') ),
				array( 'table'=>'T_subscriptions', 'fk'=>'sub_coll_ID', 'msg'=>T_('%d subscriptions') ),
				array( 'table'=>'T_widget', 'fk'=>'wi_coll_ID', 'msg'=>T_('%d widgets') ),
				array( 'table'=>'T_hitlog', 'fk'=>'hit_blog_ID', 'msg'=>T_('%d hits') ),
			);

		if( $db_row == NULL )
		{
			global $default_locale;
			// echo 'Creating blank blog';
			$this->owner_user_ID = 1; // DB default
			$this->set( 'locale', $default_locale );
			$this->set( 'access_type', 'index.php' );
			$this->skin_ID = 1;	// TODO: this is the DB default, but it will fail if skin #1 does not exist
		}
		else
		{
			$this->ID = $db_row->blog_ID;
			$this->shortname = $db_row->blog_shortname;
			$this->name = $db_row->blog_name;
			$this->owner_user_ID = $db_row->blog_owner_user_ID;
			$this->advanced_perms = $db_row->blog_advanced_perms;
			$this->tagline = $db_row->blog_tagline;
			$this->shortdesc = $db_row->blog_description;	// description
			$this->longdesc = $db_row->blog_longdesc;
			$this->locale = $db_row->blog_locale;
			$this->access_type = $db_row->blog_access_type;
			$this->siteurl = $db_row->blog_siteurl;
			$this->urlname = $db_row->blog_urlname;
			$this->links_blog_ID = $db_row->blog_links_blog_ID; // DEPRECATED
			$this->notes = $db_row->blog_notes;
			$this->keywords = $db_row->blog_keywords;
			$this->allowcomments = $db_row->blog_allowcomments;
			$this->allowtrackbacks = $db_row->blog_allowtrackbacks;
			$this->allowblogcss = $db_row->blog_allowblogcss;
			$this->allowusercss = $db_row->blog_allowusercss;
			$this->skin_ID = $db_row->blog_skin_ID;
			$this->in_bloglist = $db_row->blog_in_bloglist;
			$this->media_location = $db_row->blog_media_location;
			$this->media_subdir = $db_row->blog_media_subdir;
			$this->media_fullpath = $db_row->blog_media_fullpath;
			$this->media_url = $db_row->blog_media_url;
			$this->UID = $db_row->blog_UID;
		}
	}


	/**
	 * @param string
	 */
	function init_by_kind( $kind, $name = NULL, $shortname = NULL, $urlname = NULL )
	{
		switch( $kind )
		{
			case 'photo':
				$this->set( 'name', empty($name) ? T_('My photoblog') : $name );
				$this->set( 'shortname', empty($shortname) ? T_('Photoblog') : $shortname );
				$this->set( 'urlname', empty($urlname) ? 'photo' : $urlname );
				$this->set_setting( 'posts_per_page', 1 );
				$this->set_setting( 'archive_mode', 'postbypost' );
				break;

			case 'group':
				$this->set( 'name', empty($name) ? T_('Our blog') : $name );
				$this->set( 'shortname', empty($shortname) ? T_('Group') : $shortname );
				$this->set( 'urlname', empty($urlname) ? 'group' : $urlname );
				$this->set_setting( 'use_workflow', 1 );
				break;

			case 'std':
			default:
				$this->set( 'name', empty($name) ? T_('My weblog') : $name );
				$this->set( 'shortname', empty($shortname) ? T_('Blog') : $shortname );
				$this->set( 'urlname', empty($urlname) ? 'blog' : $urlname );
				break;
		}
	}


	/**
	 * @static
	 *
	 * @param string
	 * @return string
	 */
	function kind_name( $kind )
	{
  	switch( $kind )
		{
			case 'photo':
				return T_('Photoblog');

			case 'group':
				return T_('Group blog');

			case 'std':
			default:
				return T_('Standard blog');
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

		if( param( 'blog_name', 'string', NULL ) !== NULL )
		{ // General params:
			$this->set_from_Request( 'name' );
			$this->set( 'shortname',     param( 'blog_shortname',     'string', true ) );
			$this->set( 'locale',        param( 'blog_locale',        'string', $default_locale ) );
		}


		if( param( 'archive_links',   'string', NULL ) !== NULL )
		{ // Archive link type:
			$this->set_setting( 'archive_links', get_param( 'archive_links' ) );
			$this->set_setting( 'archive_posts_per_page', param( 'archive_posts_per_page', 'integer', NULL ), true );
		}

		if( param( 'chapter_links',   'string', NULL ) !== NULL )
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

		if( param( 'tag_links',   'string', NULL ) !== NULL )
		{ // Tag page link type:
			$this->set_setting( 'tag_links', get_param( 'tag_links' ) );
		}

		if( param( 'tag_prefix', 'string', NULL) !== NULL )
		{
			$category_prefix = get_param( 'tag_prefix' );
			if( ! preg_match( '|^([A-Za-z0-9\-_]+(/[A-Za-z0-9\-_]+)*)?$|', $category_prefix) )
			{
				param_error( 'tag_prefix', T_('Invalid category prefix.') );
			}
			$this->set_setting( 'tag_prefix', $category_prefix);
		}

		if( param( 'chapter_posts_per_page', 'integer', NULL ) !== NULL )
		{ // Chapter link type:
			$this->set_setting( 'chapter_posts_per_page', get_param( 'chapter_posts_per_page' ), true );
			$this->set_setting( 'tag_posts_per_page', param( 'tag_posts_per_page', 'integer', NULL ), true );
		}


		if( param( 'single_links',   'string', NULL ) !== NULL )
		{ // Single post link type:
			$this->set_setting( 'single_links', get_param( 'single_links' ) );
		}


		if( param( 'blog_skin_ID', 'integer', NULL ) !== NULL )
		{	// Default blog:
			$this->set_from_Request( 'skin_ID' );
		}


		if( param( 'what_to_show',   'string', NULL ) !== NULL )
		{ // Show x days or x posts?:
			$this->set_setting( 'what_to_show', get_param( 'what_to_show' ) );

			param_integer_range( 'posts_per_page', 1, 9999, T_('Items/days per page must be between %d and %d.') );
			$this->set_setting( 'posts_per_page', get_param( 'posts_per_page' ) );

			$this->set_setting( 'archive_mode', param( 'archive_mode', 'string', true ) );

 			$this->set_setting( 'orderby', param( 'orderby', 'string', true ) );
 			$this->set_setting( 'orderdir', param( 'orderdir', 'string', true ) );
		}

		if( param( 'feed_content',   'string', NULL ) !== NULL )
		{ // How much content in feeds?
			$this->set_setting( 'feed_content', get_param( 'feed_content' ) );

			param_integer_range( 'posts_per_feed', 1, 9999, T_('Items per feed must be between %d and %d.') );
			$this->set_setting( 'posts_per_feed', get_param( 'posts_per_feed' ) );
		}

		if( param( 'require_title', 'string', NULL ) !== NULL )
		{ // Title for items required?
			$this->set_setting( 'require_title', get_param( 'require_title' ) );
		}

		if( param( 'blog_description', 'string', NULL ) !== NULL )
		{	// Description:
			$this->set_from_Request( 'shortdesc', 'blog_description' );
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
			$blog_ping_plugins = param( 'blog_ping_plugins', 'array', array() );
			$blog_ping_plugins = array_unique($blog_ping_plugins);
			$this->set_setting('ping_plugins', implode(',', $blog_ping_plugins));
		}

		if( in_array( 'features', $groups ) )
		{ // we want to load the workflow checkboxes:
			$this->set_setting( 'allow_subscriptions',  param( 'allow_subscriptions', 'integer', 0 ) );
			$this->set( 'advanced_perms',  param( 'advanced_perms', 'integer', 0 ) );
			$this->set_setting( 'use_workflow',  param( 'blog_use_workflow', 'integer', 0 ) );

			$this->set( 'allowblogcss', param( 'blog_allowblogcss', 'integer', 0 ) );
			$this->set( 'allowusercss', param( 'blog_allowusercss', 'integer', 0 ) );
		}

		if( param( 'blog_allowcomments',   'string', NULL ) !== NULL )
		{ // Feedback options:
			$this->set_from_Request( 'allowcomments' );
			$this->set_setting( 'new_feedback_status', param( 'new_feedback_status', 'string', 'draft' ) );
			$this->set_setting( 'allow_rating', param( 'allow_rating', 'string', 'never' ) );
			$this->set( 'allowtrackbacks', param( 'blog_allowtrackbacks', 'integer', 0 ) );

			// Public blog list
			$this->set( 'in_bloglist',   param( 'blog_in_bloglist',   'integer', 0 ) );
		}


		if( in_array( 'seo', $groups ) )
		{ // we want to load the workflow checkboxes:
			$this->set_setting( 'canonical_item_urls',  param( 'canonical_item_urls', 'integer', 0 ) );
			$this->set_setting( 'canonical_cat_urls',  param( 'canonical_cat_urls', 'integer', 0 ) );
			$this->set_setting( 'canonical_tag_urls',  param( 'canonical_tag_urls', 'integer', 0 ) );
			$this->set_setting( 'default_noindex',  param( 'default_noindex', 'integer', 0 ) );
			$this->set_setting( 'paged_noindex',  param( 'paged_noindex', 'integer', 0 ) );
			$this->set_setting( 'paged_nofollowto',  param( 'paged_nofollowto', 'integer', 0 ) );
			$this->set_setting( 'archive_noindex',  param( 'archive_noindex', 'integer', 0 ) );
			$this->set_setting( 'archive_nofollowto',  param( 'archive_nofollowto', 'integer', 0 ) );
			$this->set_setting( 'chapter_noindex',  param( 'chapter_noindex', 'integer', 0 ) );
			$this->set_setting( 'tag_noindex',  param( 'tag_noindex', 'integer', 0 ) );
			$this->set_setting( 'filtered_noindex',  param( 'filtered_noindex', 'integer', 0 ) );
			$this->set_setting( 'arcdir_noindex',  param( 'arcdir_noindex', 'integer', 0 ) );
			$this->set_setting( 'catdir_noindex',  param( 'catdir_noindex', 'integer', 0 ) );
			$this->set_setting( 'feedback-popup_noindex',  param( 'feedback-popup_noindex', 'integer', 0 ) );
			$this->set_setting( 'msgform_noindex',  param( 'msgform_noindex', 'integer', 0 ) );
			$this->set_setting( 'special_noindex',  param( 'special_noindex', 'integer', 0 ) );
			$this->set_setting( 'title_link_type',  param( 'title_link_type', 'string', '' ) );
			$this->set_setting( 'permalinks',  param( 'permalinks', 'string', '' ) );
			$this->set_setting( '404_response',  param( '404_response', 'string', '' ) );
		}


 		if( param( 'custom_double1', 'string', NULL ) !== NULL )
		{	// Description:
			for( $i = 1 ; $i <= 5; $i++ )
			{
				$this->set_setting( 'custom_double'.$i, param( 'custom_double'.$i, 'string', NULL ) );
			}
			for( $i = 1 ; $i <= 3; $i++ )
			{
				$this->set_setting( 'custom_varchar'.$i, param( 'custom_varchar'.$i, 'string', NULL ) );
			}
		}


		/*
		 * ADVANCED ADMIN SETTINGS
		 */
		if( $current_User->check_perm( 'blog_admin', 'edit', false, $this->ID ) )
		{	// We have permission to edit advanced admin settings:

			if( param( 'owner_login', 'string', NULL ) !== NULL )
			{ // Permissions:
				$UserCache = & get_Cache( 'UserCache' );
				$owner_User = & $UserCache->get_by_login( get_param('owner_login'), false, false );
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


			if( param( 'blog_urlname',   'string', NULL ) !== NULL )
			{	// check urlname
				if( param_check_not_empty( 'blog_urlname', T_('You must provide an URL blog name!') ) )
				{
					$this->set_from_Request( 'urlname' );

					if( ! preg_match( '|^[A-Za-z0-9\-]+$|', $this->urlname ) )
					{
						param_error( 'blog_urlname', T_('The url name is invalid.') );
					}

					if( $DB->get_var( 'SELECT COUNT(*)
															 FROM T_blogs
															WHERE blog_urlname = '.$DB->quote($this->get( 'urlname' )).'
															  AND blog_ID <> '.$this->ID
															) )
					{ // urlname is already in use
						param_error( 'blog_urlname', T_('This URL name is already in use by another blog. Please choose another name.') );
					}
				}
			}


			if( ($access_type = param( 'blog_access_type', 'string', NULL )) !== NULL )
			{ // Blog URL parameters:
				$this->set( 'access_type', $access_type );

				if( $access_type == 'absolute' )
				{
					$blog_siteurl = param( 'blog_siteurl_absolute', 'string', true );
					if( !preg_match( '#^https?://.+#', $blog_siteurl ) )
					{
						$Messages->add( T_('Blog Folder URL').': '
														.T_('You must provide an absolute URL (starting with <code>http://</code> or <code>https://</code>)!'), 'error' );
					}
					$this->set( 'siteurl', $blog_siteurl );
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
			}


			if( param( 'aggregate_coll_IDs', 'string', NULL ) !== NULL )
			{ // Aggregate list:
				// fp> TODO: check perms on each aggregated blog (if changed)
				// fp> TODO: better interface
				if( !preg_match( '#^([0-9]+(,[0-9]+)*)?$#', get_param( 'aggregate_coll_IDs' ) ) )
				{
					param_error( 'aggregate_coll_IDs', T_('Invalid aggregate blog ID list!') );
				}
				$this->set_setting( 'aggregate_coll_IDs', get_param( 'aggregate_coll_IDs' ) );
			}

			if( param( 'source_file', 'string', NULL ) !== NULL )
			{	// Static file:
				$this->set_setting( 'source_file', get_param( 'source_file' ) );
				$this->set_setting( 'static_file', param( 'static_file', 'string', '' ) );
			}


			if( param( 'blog_media_location',  'string', NULL ) !== NULL )
			{	// Media files location:
				$this->set_from_Request(   'media_location' );
				$this->set_media_subdir(    param( 'blog_media_subdir',    'string', '' ) );
				$this->set_media_fullpath(  param( 'blog_media_fullpath',  'string', '' ) );
				$this->set_media_url(       param( 'blog_media_url',       'string', '' ) );

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
								$subdir = substr($canonical_path, strlen($media_path));
								if( $error = validate_dirname($subdir) )
								{
									param_error( 'blog_media_subdir', T_('Media dir location').': '.$error );
								}
							}
						}
						break;
				}
			}


		}

		return ! param_errors_detected();
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
	 * @return boolean true, if a value has been set; false if it has not changed
	 */
	function set( $parname, $parvalue )
	{
		global $Settings;

		switch( $parname )
		{
			case 'ID':
			case 'allowtrackbacks':
			case 'blog_in_bloglist':
				return parent::set_param( $parname, 'number', $parvalue );
				break;

			case 'shortdesc':
				$this->shortdesc = $parvalue;
				return parent::set_param( 'description', 'string', $parvalue );
				break;

			default:
				return parent::set_param( $parname, 'string', $parvalue );
		}
	}


	/**
	 * Generate blog URL
	 *
	 * @param string default|dynamic|static
	 */
	function gen_blogurl( $type = 'default' )
	{
		global $baseurl, $basedomain, $Settings;

		if( $type == 'static' )
		{ // We want the static page, there is no access type option here:
			debug_die( 'static page currently not supported' );
		}

		if( $type == 'dynamic' )
		{ // We want to force a dynamic page
			debug_die( 'dynamic page currently not supported' );
		}

		switch( $this->access_type )
		{
			case 'default':
				// Access through index.php: match absolute URL or call default blog
				if( ( $Settings->get('default_blog_ID') == $this->ID )
					|| preg_match( '#^https?://#', $this->siteurl ) )
				{ // Safety check! We only do that kind of linking if this is really the default blog...
					// or if we call by absolute URL
					return $baseurl.$this->siteurl.'index.php';
				}
				// ... otherwise, we add the blog ID:

			case 'index.php':
				// Access through index.php + blog qualifier
				return $baseurl.$this->siteurl.'index.php?blog='.$this->ID;

			case 'extrapath':
				// We want to use extra path info, use the blog urlname:
				return $baseurl.$this->siteurl.'index.php/'.$this->urlname.'/';

			case 'relative':
				return $baseurl.$this->siteurl;

			case 'subdom':
				return 'http://'.$this->urlname.'.'.$basedomain.'/';

			case 'absolute':
				return $this->siteurl;

			default:
				debug_die( 'Unhandled Blog access type ['.$this->access_type.']' );
		}
	}


	/**
	 * Generate the baseurl of the blog (URL of the folder where the blog lives)
	 *
	 * @todo test
	 */
	function gen_baseurl()
	{
		global $baseurl, $basedomain;

		switch( $this->access_type )
		{
			case 'default':
			case 'index.php':
				return $baseurl.$this->siteurl;

			case 'extrapath':
				// We want to use extra path info, use the blog urlname:
				return $baseurl.$this->siteurl.'index.php/'.$this->urlname.'/';

			case 'relative':
				$url = $baseurl.$this->siteurl;
				break;

			case 'subdom':
				return 'http://'.$this->urlname.'.'.$basedomain.'/';

			case 'absolute':
				$url = $this->siteurl;
				break;

			default:
				debug_die( 'Unhandled Blog access type ['.$this->access_type.']' );
		}

		// For case relative and absolute:
		return preg_replace( '¤^(.+)/[^/]$¤', '$1/', $url );

	}


	/**
	 * Load presets
	 *
	 * @param string
	 */
	function load_presets( $set_name )
	{
		switch( $set_name )
		{
			case 'awall':
				$this->set_setting( 'archive_links', 'extrapath' );
				$this->set_setting( 'archive_posts_per_page', NULL );
				$this->set_setting( 'chapter_links', 'chapters' );
				$this->set_setting( 'chapter_posts_per_page', NULL );
				$this->set_setting( 'tag_posts_per_page', NULL );
				$this->set_setting( 'tag_links', 'colon' );
				$this->set_setting( 'single_links', 'ymd' );

				$this->set_setting( 'canonical_item_urls', 1 );
				$this->set_setting( 'canonical_cat_urls', 1 );
				$this->set_setting( 'canonical_tag_urls', 1 );

				$this->set_setting( 'category_prefix', '' );
				$this->set_setting( 'tag_prefix', '' );

				$this->set_setting( 'default_noindex', 0 );
				$this->set_setting( 'paged_noindex', 1 );
				$this->set_setting( 'paged_nofollowto', 0 );
				$this->set_setting( 'archive_noindex', 1 );
				$this->set_setting( 'archive_nofollowto', 0 );
				$this->set_setting( 'chapter_noindex', 0 );
				$this->set_setting( 'tag_noindex', 0 );
				$this->set_setting( 'filtered_noindex', 1 ); // temporary

				$this->set_setting( 'arcdir_noindex', 1 );
				$this->set_setting( 'catdir_noindex', 0 );
				$this->set_setting( 'feedback-popup_noindex', 1 );
				$this->set_setting( 'msgform_noindex', 1 );
				$this->set_setting( 'special_noindex', 1 ); // temporary

				$this->set_setting( 'permalinks', 'single' );
				$this->set_setting( 'title_link_type', 'permalink' );
				break;

			case 'abeal':
				$this->set_setting( 'archive_links', 'extrapath' );
				$this->set_setting( 'archive_posts_per_page', 10 );
				$this->set_setting( 'chapter_links', 'subchap' );
				$this->set_setting( 'chapter_posts_per_page', 10 );
				$this->set_setting( 'tag_posts_per_page', 10 );
				$this->set_setting( 'tag_links', 'colon' );
				$this->set_setting( 'single_links', 'short' );

				$this->set_setting( 'canonical_item_urls', 1 );
				$this->set_setting( 'canonical_cat_urls', 1 );
				$this->set_setting( 'canonical_tag_urls', 1 );

				$this->set_setting( 'category_prefix', '' );
				$this->set_setting( 'tag_prefix', '' );

				$this->set_setting( 'default_noindex', 0 );
				$this->set_setting( 'paged_noindex', 1 );
				$this->set_setting( 'paged_nofollowto', 0 );
				$this->set_setting( 'archive_noindex', 1 );
				$this->set_setting( 'archive_nofollowto', 0 );
				$this->set_setting( 'chapter_noindex', 1 );
				$this->set_setting( 'tag_noindex', 1 );
				$this->set_setting( 'filtered_noindex', 1 ); // temporary

				$this->set_setting( 'arcdir_noindex', 0 );
				$this->set_setting( 'catdir_noindex', 0 );
				$this->set_setting( 'feedback-popup_noindex', 0 );
				$this->set_setting( 'msgform_noindex', 1 );
				$this->set_setting( 'special_noindex', 1 ); // temporary

				$this->set_setting( 'permalinks', 'single' );
				$this->set_setting( 'title_link_type', 'permalink' );
				break;

			case 'mgray':
				$this->set_setting( 'archive_links', 'extrapath' );
				$this->set_setting( 'archive_posts_per_page', 20 );
				$this->set_setting( 'chapter_links', 'chapters' );
				$this->set_setting( 'chapter_posts_per_page', 20 );
				$this->set_setting( 'tag_posts_per_page', 20 );
				$this->set_setting( 'tag_links', 'colon' );
				$this->set_setting( 'single_links', 'chapters' );

				$this->set_setting( 'canonical_item_urls', 1 );
				$this->set_setting( 'canonical_cat_urls', 1 );
				$this->set_setting( 'canonical_tag_urls', 1 );

				$this->set_setting( 'category_prefix', '' );
				$this->set_setting( 'tag_prefix', '' );

				$this->set_setting( 'default_noindex', 0 );
				$this->set_setting( 'paged_noindex', 1 );
				$this->set_setting( 'paged_nofollowto', 0 );
				$this->set_setting( 'archive_noindex', 1 );
				$this->set_setting( 'archive_nofollowto', 0 );
				$this->set_setting( 'chapter_noindex', 0 );
				$this->set_setting( 'tag_noindex', 1 );
				$this->set_setting( 'filtered_noindex', 1 ); // temporary

				$this->set_setting( 'arcdir_noindex', 1 );
				$this->set_setting( 'catdir_noindex', 0 );
				$this->set_setting( 'feedback-popup_noindex', 1 );
				$this->set_setting( 'msgform_noindex', 1 );
				$this->set_setting( 'special_noindex', 1 ); // temporary

				$this->set_setting( 'permalinks', 'single' );
				$this->set_setting( 'title_link_type', 'permalink' );
				break;

			case 'rfishkin':
				$this->set_setting( 'archive_links', 'extrapath' );
				$this->set_setting( 'archive_posts_per_page', 50 );
				$this->set_setting( 'chapter_links', 'subchap' );
				$this->set_setting( 'chapter_posts_per_page', 50 );
				$this->set_setting( 'tag_posts_per_page', 50 );
				$this->set_setting( 'tag_links', 'colon' );
				$this->set_setting( 'single_links', 'short' );

				$this->set_setting( 'canonical_item_urls', 1 );
				$this->set_setting( 'canonical_cat_urls', 1 );
				$this->set_setting( 'canonical_tag_urls', 1 );

				$this->set_setting( 'category_prefix', '' );
				$this->set_setting( 'tag_prefix', '' );

				$this->set_setting( 'default_noindex', 0 );
				$this->set_setting( 'paged_noindex', 1 );
				$this->set_setting( 'paged_nofollowto', 1 );
				$this->set_setting( 'archive_noindex', 1 );
				$this->set_setting( 'archive_nofollowto', 1 );
				$this->set_setting( 'chapter_noindex', 0 );
				$this->set_setting( 'tag_noindex', 0 );
				$this->set_setting( 'filtered_noindex', 1 ); // temporary

				$this->set_setting( 'arcdir_noindex', 1 );
				$this->set_setting( 'catdir_noindex', 1 );
				$this->set_setting( 'feedback-popup_noindex', 0 );
				$this->set_setting( 'msgform_noindex', 1 );
				$this->set_setting( 'special_noindex', 1 ); // temporary

				$this->set_setting( 'permalinks', 'single' );
				$this->set_setting( 'title_link_type', 'permalink' );
				break;

			case 'sspencer':
				$this->set_setting( 'archive_links', 'extrapath' );
				$this->set_setting( 'archive_posts_per_page', 10 );
				$this->set_setting( 'chapter_links', 'chapters' );
				$this->set_setting( 'chapter_posts_per_page', 10 );
				$this->set_setting( 'tag_posts_per_page', 10 );
				$this->set_setting( 'tag_links', 'colon' );
				$this->set_setting( 'single_links', 'chapters' );

				$this->set_setting( 'canonical_item_urls', 1 );
				$this->set_setting( 'canonical_cat_urls', 1 );
				$this->set_setting( 'canonical_tag_urls', 1 );

				$this->set_setting( 'category_prefix', 'category' );
				$this->set_setting( 'tag_prefix', 'tag' );

				$this->set_setting( 'default_noindex', 0 );
				$this->set_setting( 'paged_noindex', 1 );
				$this->set_setting( 'paged_nofollowto', 1 );
				$this->set_setting( 'archive_noindex', 1 );
				$this->set_setting( 'archive_nofollowto', 1 );
				$this->set_setting( 'chapter_noindex', 0 );
				$this->set_setting( 'tag_noindex', 0 );
				$this->set_setting( 'filtered_noindex', 1 ); // temporary

				$this->set_setting( 'arcdir_noindex', 1 );
				$this->set_setting( 'catdir_noindex', 0 );
				$this->set_setting( 'feedback-popup_noindex', 1 );
				$this->set_setting( 'msgform_noindex', 1 );
				$this->set_setting( 'special_noindex', 1 ); // temporary

				$this->set_setting( 'permalinks', 'single' );
				$this->set_setting( 'title_link_type', 'permalink' );
				break;
		}
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
	function gen_archive_url( $year, $month = NULL, $day = NULL, $week = NULL, $glue = '&amp;' )
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
		elseif( $week !== '' )  // Note: week # can be 0 !
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

		return $link;
	}


	/**
	 * Generate link to archive
	 * @uses Blog::gen_archive_url()
	 * @return string HTML A tag
	 */
	function gen_archive_link( $text, $title, $year, $month = NULL, $day = NULL, $week = NULL, $glue = '&amp;' )
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

		$link .= ' href="'.$this->gen_archive_url( $year, $month, $day, $week, $glue ).'" >';
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
	function gen_tag_url( $tag, $paged = 1, $glue = '&amp;' )
	{
		$link_type = $this->get_setting( 'tag_links' );
		switch( $link_type )
		{
			case 'param':
				$r = url_add_param( $this->gen_blogurl(), 'tag='.urlencode( $tag ) );

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
					case 'semicolon':
						$trailer = ';';
						break;
					case 'colon':
					default:
						$trailer = ':';
						break;
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
			$status = 'draft';
		}
		if( ! $current_User->check_perm( 'blog_post!'.$status, 'edit', false, $this->ID ) )
		{ // We need to find another one:
			$status = NULL;

			if( $current_User->check_perm( 'blog_post!published', 'edit', false, $this->ID ) )
				$status = 'published';
			elseif( $current_User->check_perm( 'blog_post!protected', 'edit', false, $this->ID ) )
				$status = 'protected';
			elseif( $current_User->check_perm( 'blog_post!private', 'edit', false, $this->ID ) )
				$status = 'private';
			elseif( $current_User->check_perm( 'blog_post!draft', 'edit', false, $this->ID ) )
				$status = 'draft';
			elseif( $current_User->check_perm( 'blog_post!deprecated', 'edit', false, $this->ID ) )
				$status = 'deprecated';
			elseif( $current_User->check_perm( 'blog_post!redirected', 'edit', false, $this->ID ) )
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
		if( !isset( $this->default_cat_ID ) )
		{
			global $DB;

			$sql = 'SELECT cat_ID
			          FROM T_categories
			         WHERE cat_blog_ID = '.$this->ID.'
			         ORDER BY cat_ID
			         LIMIT 1';

			$this->default_cat_ID = $DB->get_var( $sql, 0, 0, 'Get default category' );
		}

		return $this->default_cat_ID;
	}


	/**
	 * Get the blog's media directory (and create it if necessary).
	 *
	 * If we're {@link is_admin_page() on an admin page}, it adds status messages.
	 * @todo These status messages should rather go to a "syslog" and not be displayed to a normal user
	 *
	 * @param boolean Create the directory, if it does not exist yet?
	 * @return string path string on success, false if the dir could not be created
	 */
	function get_media_dir( $create = true )
	{
		global $media_path, $Messages, $Settings, $Debuglog;

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

		// TODO: use a File object here (to access perms, ..) when FileCache::get_by_path() is provided.
		if( $create && ! is_dir( $mediadir ) )
		{
			// TODO: Link to some help page(s) with errors!
			if( ! is_writable( dirname($mediadir) ) )
			{ // add error
				if( is_admin_page() )
				{
					$Messages->add( sprintf( T_("The blog's media directory &laquo;%s&raquo; could not be created, because the parent directory is not writable or does not exist."), rel_path_to_base($mediadir) )
								.get_manual_link('media_file_permission_errors'), 'error' );
				}
				return false;
			}
			elseif( !@mkdir( $mediadir ) )
			{ // add error
				if( is_admin_page() )
				{
					$Messages->add( sprintf( T_("The blog's media directory &laquo;%s&raquo; could not be created."), rel_path_to_base($mediadir) )
								.get_manual_link('directory_creation_error'), 'error' );
				}
				return false;
			}
			else
			{ // chmod and add note:
				$chmod = $Settings->get('fm_default_chmod_dir');
				if( !empty($chmod) )
				{
					@chmod( $mediadir, octdec($chmod) );
				}
				if( is_admin_page() )
				{
					$Messages->add( sprintf( T_("The blog's media directory &laquo;%s&raquo; has been created with permissions %s."), rel_path_to_base($mediadir), substr( sprintf('%o', fileperms($mediadir)), -3 ) ), 'success' );
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
		global $media_url, $Settings, $Debuglog;

		if( ! $Settings->get( 'fm_enable_roots_blog' ) )
		{ // User directories are disabled:
			$Debuglog->add( 'Attempt to access blog media URL, but this feature is disabled', 'files' );
			return false;
		}

		switch( $this->media_location )
		{
			case 'default':
				return $media_url.'blogs/'.$this->urlname.'/';

			case 'subdir':
				return $media_url.$this->media_subdir;
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
		load_class('/files/model/_fileroot.class.php');
		return 'admin.php?ctrl=files&amp;root='.FileRoot::gen_ID( 'collection', $this->ID );
	}


	/**
	 * Get URL to display the blog with a temporary skin.
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
	 	$SkinCache = & get_Cache( 'SkinCache' );
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
	 * @return false|string The value as string or false in case of error (e.g. media dir is disabled).
	 */
	function get( $parname )
	{
		global $xmlsrv_url, $baseurl, $basepath, $media_url, $current_User, $Settings, $Debuglog;

		switch( $parname )
		{
			case 'blogurl':		// Deprecated
			case 'link':  		// Deprecated
			case 'url':
				return $this->gen_blogurl( 'default' );

			case 'dynurl':
				return $this->gen_blogurl( 'dynamic' );

			case 'staticurl':
				return $this->gen_blogurl( 'static' );

			case 'dynfilepath':
				// Source file for static page:
				return $basepath.$this->get_setting('source_file');

			case 'staticfilepath':
				// Destiantion file for static page:
				return $basepath.$this->get_setting('static_file');

			case 'baseurl':
				return $this->gen_baseurl();

			case 'baseurlroot':
				// fp>> TODO: cleanup
				if( preg_match( '#^(https?://(.+?)(:.+?)?)/#', $this->gen_baseurl(), $matches ) )
				{
					// TODO: shouldn't that include a trailing slash?:
					return $matches[1];
				}
				debug_die( 'Blog::get(baseurl)/baseurlroot - assertion failed [baseurl: '.$this->gen_baseurl().'].' );

			case 'lastcommentsurl':
				return url_add_param( $this->gen_blogurl(), 'disp=comments' );

			case 'arcdirurl':
				return url_add_param( $this->gen_blogurl(), 'disp=arcdir' );

			case 'catdirurl':
				return url_add_param( $this->gen_blogurl(), 'disp=catdir' );

			case 'mediaidxurl':
				return url_add_param( $this->gen_blogurl(), 'disp=mediaidx' );

			case 'msgformurl':
				return url_add_param( $this->gen_blogurl(), 'disp=msgform' );

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
	}


 	/**
	 * Get a setting.
	 *
	 * @return string|false|NULL value as string on success; NULL if not found; false in case of error
	 */
	function get_setting( $parname )
	{
		$this->load_CollectionSettings();

		return $this->CollectionSettings->get( $this->ID, $parname );
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
	 * Make sure collection settings are loaded
	 */
	function load_CollectionSettings()
	{
		if( ! isset( $this->CollectionSettings ) )
		{
			load_class('collections/model/_collsettings.class.php');
			$this->CollectionSettings = new CollectionSettings(); // COPY (function)
		}
	}


 	/**
	 * Insert into the DB
	 */
	function dbinsert()
	{
		global $DB;

		$DB->begin();

		if( parent::dbinsert() )
		{
			if( isset( $this->CollectionSettings ) )
			{
				// So far all settings have been saved to collection #0 !
				// Update the settings: hackish but the base class should not even store this value actually...
				// dh> what do you mean? What "base class"? Is there a problem with CollectionSettings?
				$this->CollectionSettings->cache[$this->ID] = $this->CollectionSettings->cache[0];
				unset( $this->CollectionSettings->cache[0] );

				$this->CollectionSettings->dbupdate();
			}
		}

		$DB->commit();
	}


	/**
	 * Update the DB based on previously recorded changes
	 */
	function dbupdate()
	{
		global $DB;

		$DB->begin();

		parent::dbupdate();

		if( isset( $this->CollectionSettings ) )
		{
			$this->CollectionSettings->dbupdate();
		}

		$DB->commit();
	}


	/**
	 * Delete a blog and dependencies from database
	 *
	 * Includes WAY TOO MANY requests because we try to be compatible with MySQL 3.23, bleh!
	 *
	 * @param boolean true if you want to try to delete the static file
	 * @param boolean true if you want to echo progress
	 */
	function dbdelete($delete_static_file = false, $echo = false )
	{
		global $DB, $Messages;

		// Note: No need to localize the status messages...
		if( $echo ) echo '<p>MySQL 3.23 compatibility mode!';

		// Get list of cats that are going to be deleted (3.23)
		if( $echo ) echo '<br />Getting category list to delete... ';
		$cat_list = implode( ',', $DB->get_col( "
				SELECT cat_ID
				  FROM T_categories
				 WHERE cat_blog_ID = $this->ID" ) );

		if( empty( $cat_list ) )
		{ // There are no cats to delete
			if( $echo ) echo 'None!';
		}
		else
		{ // Delete the cats & dependencies

			// Get list of posts that are going to be deleted (3.23)
			if( $echo ) echo '<br />Getting post list to delete... ';
			$post_list = implode( ',', $DB->get_col( "
					SELECT postcat_post_ID
					  FROM T_postcats
					 WHERE postcat_cat_ID IN ($cat_list)" ) );

			if( empty( $post_list ) )
			{ // There are no posts to delete
				if( $echo ) echo 'None!';
			}
			else
			{ // Delete the posts & dependencies

				// TODO: There's also a constraint FK_post_parent_ID..

				// Delete postcats
				if( $echo ) echo '<br />Deleting post-categories... ';
				$ret = $DB->query(	"DELETE FROM T_postcats
															WHERE postcat_cat_ID IN ($cat_list)" );
				if( $echo ) printf( '(%d rows)', $ret );
				$Messages->add( T_('Deleted post-categories'), 'success' );

				// Delete comments
				if( $echo ) echo '<br />Deleting comments on blog\'s posts... ';
				$ret = $DB->query( "DELETE FROM T_comments
														WHERE comment_post_ID IN ($post_list)" );
				if( $echo ) printf( '(%d rows)', $ret );
				$Messages->add( T_('Deleted comments on blog\'s posts'), 'success' );


				// Delete posts
				if( $echo ) echo '<br />Deleting blog\'s posts... ';
				$ret = $DB->query(	"DELETE FROM T_items__item
															WHERE post_ID IN ($post_list)" );
				if( $echo ) printf( '(%d rows)', $ret );
				$Messages->add( T_('Deleted blog\'s posts'), 'success' );

			} // / are there posts?

			// Delete categories
			if( $echo ) echo '<br />Deleting blog\'s categories... ';
			$ret = $DB->query( "DELETE FROM T_categories
													WHERE cat_blog_ID = $this->ID" );
			if( $echo ) printf( '(%d rows)', $ret );
			$Messages->add( T_('Deleted blog\'s categories'), 'success' );

		} // / are there cats?


		if( $delete_static_file )
		{ // Delete static file
			if( $echo ) echo '<br />Trying to delete static file... ';
			if( ! @unlink( $this->get('staticfilepath') ) )
			{
				if( $echo )
				{
					echo '<span class="error">';
					printf(	T_('ERROR! Could not delete! You will have to delete the file [%s] by hand.'),
									$this->get('staticfilepath') );
					echo '</span>';
				}
				$Messages->add( sprintf( T_('Could not delete static file [%s]'), $this->get('staticfilepath') ), 'error' );
			}
			else
			{
				if( $echo ) echo 'OK.';
				$Messages->add( T_('Deleted blog\'s static file'), 'success' );
			}
		}

		// Unset cache entry:
		// TODO

		// Delete main (blog) object:
		parent::dbdelete();

		if( $echo ) echo '<br />Done.</p>';
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
	 * Resolve user ID of owner
	 *
	 * @return User
	 */
	function & get_owner_User()
	{
		if( !isset($this->owner_User) )
		{
			$UserCache = & get_Cache( 'UserCache' );
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
		$this->get_owner_User();
		if( ! $this->owner_User->allow_msgform )
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
		$text = preg_replace_callback( '¤\$([a-z]+)\$¤', array( $this, 'replace_callback' ), $text );

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
	 * @param boolean do we want to redirect back to where we came from after message?
	 */
	function get_contact_url( $with_redirect = true )
	{
		$r = url_add_param( $this->get('msgformurl'), 'recipient_id='.$this->owner_user_ID );

		if( $with_redirect )
		{
			$r .= '&amp;redirect_to='
					// The URL will be made relative on the next page (this is needed when $htsrv_url is on another domain! -- multiblog situation )
					.rawurlencode( regenerate_url('','','','&') );
		}

		return $r;
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
						 	 AND tag_name = '.$DB->quote( strtolower($tag) );

		return $DB->get_var( $sql );

	}
}


/*
 * $Log$
 * Revision 1.44  2008/09/15 11:01:06  fplanque
 * Installer now creates a demo photoblog
 *
 * Revision 1.43  2008/09/09 06:03:30  fplanque
 * More tag URL options
 * Enhanced URL resolution for categories and tags
 *
 * Revision 1.42  2008/06/30 23:47:04  blueyed
 * require_title setting for Blogs, defaulting to 'required'. This makes the title field now a requirement (by default), since it often gets forgotten when posting first (and then the urltitle is ugly already)
 *
 * Revision 1.41  2008/05/31 22:29:07  blueyed
 * indent, doc
 *
 * Revision 1.40  2008/05/10 23:41:31  fplanque
 * cleanup of external feed providers
 *
 * Revision 1.39  2008/05/05 18:50:07  waltercruz
 * URL validation of external feeds
 *
 * Revision 1.38  2008/04/30 18:35:05  waltercruz
 * Temporary fix
 *
 * Revision 1.37  2008/04/26 22:20:44  fplanque
 * Improved compatibility with older skins.
 *
 * Revision 1.36  2008/04/19 15:11:42  waltercruz
 * Feednurner
 *
 * Revision 1.35  2008/04/04 17:02:22  fplanque
 * cleanup of global settings
 *
 * Revision 1.34  2008/04/04 16:02:10  fplanque
 * uncool feature about limiting credits
 *
 * Revision 1.33  2008/03/21 19:42:44  fplanque
 * enhanced 404 handling
 *
 * Revision 1.32  2008/02/09 20:14:14  fplanque
 * custom fields management
 *
 * Revision 1.31  2008/01/21 09:35:26  fplanque
 * (c) 2008
 *
 * Revision 1.30  2008/01/18 15:53:42  fplanque
 * Ninja refactoring
 *
 * Revision 1.29  2008/01/17 20:47:58  fplanque
 * deprecated linkblog_ID blog param
 *
 * Revision 1.28  2008/01/17 18:10:09  fplanque
 * deprecated linkblog_ID blog param
 *
 * Revision 1.27  2008/01/17 14:38:30  fplanque
 * Item Footer template tag
 *
 * Revision 1.26  2008/01/15 08:19:36  fplanque
 * blog footer text tag
 *
 * Revision 1.25  2008/01/07 02:53:26  fplanque
 * cleaner tag urls
 *
 * Revision 1.24  2008/01/06 18:47:08  fplanque
 * enhanced system checks
 *
 * Revision 1.23  2008/01/05 17:54:43  fplanque
 * UI/help improvements
 *
 * Revision 1.22  2007/12/27 01:58:48  fplanque
 * additional SEO
 *
 * Revision 1.21  2007/11/27 02:37:09  blueyed
 * Use canonical path when checking if path is inside of media_path!
 *
 * Revision 1.20  2007/11/25 19:47:15  fplanque
 * cleaned up photo/media index a little bit
 *
 * Revision 1.19  2007/11/25 18:20:38  fplanque
 * additional SEO settings
 *
 * Revision 1.18  2007/11/25 14:28:17  fplanque
 * additional SEO settings
 *
 * Revision 1.17  2007/11/24 21:41:12  fplanque
 * additional SEO settings
 *
 * Revision 1.16  2007/11/24 18:35:55  blueyed
 * - demo_mode: Blog media directories can only be configured to be inside of {@link $media_path}
 * - check that blog media subdirs are valid (sub)directories
 *
 * Revision 1.15  2007/11/24 17:24:50  blueyed
 * Add $media_path
 *
 * Revision 1.14  2007/11/04 17:55:12  fplanque
 * More cleanup
 *
 * Revision 1.13  2007/11/04 01:10:57  fplanque
 * skin cleanup continued
 *
 * Revision 1.12  2007/11/03 04:56:03  fplanque
 * permalink / title links cleanup
 *
 * Revision 1.11  2007/11/02 01:44:29  fplanque
 * comment ratings
 *
 * Revision 1.10  2007/10/09 02:10:50  fplanque
 * URL fixes
 *
 * Revision 1.9  2007/10/06 21:17:25  fplanque
 * cleanup
 *
 * Revision 1.8  2007/10/05 00:09:23  blueyed
 * Nuked unnecessary global statement
 *
 * Revision 1.7  2007/10/01 13:41:07  waltercruz
 * Category prefix, trying to make the code more b2evo style
 *
 * Revision 1.6  2007/09/29 01:50:50  fplanque
 * temporary rollback; waiting for new version
 *
 * Revision 1.5  2007/09/28 09:28:36  fplanque
 * per blog advanced SEO settings
 *
 * Revision 1.4  2007/09/28 02:25:00  fplanque
 * Menu widgets
 *
 * Revision 1.2  2007/09/10 13:24:13  waltercruz
 * Mispelled word correction
 *
 * Revision 1.1  2007/06/25 10:59:31  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.85  2007/05/31 03:02:22  fplanque
 * Advanced perms now disabled by default (simpler interface).
 * Except when upgrading.
 * Enable advanced perms in blog settings -> features
 *
 * Revision 1.84  2007/05/30 01:18:56  fplanque
 * blog owner gets all permissions except advanced/admin settings
 *
 * Revision 1.83  2007/05/29 01:17:19  fplanque
 * advanced admin blog settings are now restricted by a special permission
 *
 * Revision 1.82  2007/05/28 15:18:30  fplanque
 * cleanup
 *
 * Revision 1.81  2007/05/28 01:35:22  fplanque
 * fixed static page generation
 *
 * Revision 1.80  2007/05/14 02:43:04  fplanque
 * Started renaming tables. There probably won't be a better time than 2.0.
 *
 * Revision 1.79  2007/05/13 22:53:31  fplanque
 * allow feeds restricted to post excerpts
 *
 * Revision 1.78  2007/05/09 00:58:54  fplanque
 * massive cleanup of old functions
 *
 * Revision 1.77  2007/05/08 00:54:31  fplanque
 * public blog list as a widget
 *
 * Revision 1.76  2007/04/26 00:11:05  fplanque
 * (c) 2007
 *
 * Revision 1.75  2007/04/25 18:47:41  fplanque
 * MFB 1.10: groovy links
 *
 * Revision 1.74  2007/03/25 15:18:57  fplanque
 * cleanup
 *
 * Revision 1.73  2007/03/25 15:07:38  fplanque
 * multiblog fixes
 *
 * Revision 1.72  2007/03/25 13:20:52  fplanque
 * cleaned up blog base urls
 * needs extensive testing...
 *
 * Revision 1.71  2007/03/25 10:20:02  fplanque
 * cleaned up archive urls
 *
 * Revision 1.70  2007/03/24 20:41:16  fplanque
 * Refactored a lot of the link junk.
 * Made options blog specific.
 * Some junk still needs to be cleaned out. Will do asap.
 *
 * Revision 1.69  2007/03/11 23:57:06  fplanque
 * item editing: allow setting to 'redirected' status
 *
 * Revision 1.68  2007/03/08 00:17:42  blueyed
 * More info in assertion for "baseurlroot" and "basehost" and more strict pattern
 *
 * Revision 1.67  2007/03/04 21:42:49  fplanque
 * category directory / albums
 *
 * Revision 1.66  2007/03/02 00:44:43  fplanque
 * various small fixes
 *
 * Revision 1.65  2007/02/25 01:31:34  fplanque
 * minor
 *
 * Revision 1.64  2007/02/17 21:12:14  blueyed
 * Removed magic in Plugin::get_htsrv_url() which used the blog url and assumed that "htsrv" was available in there
 *
 * Revision 1.63  2007/01/23 09:25:40  fplanque
 * Configurable sort order.
 *
 * Revision 1.62  2007/01/23 08:07:16  fplanque
 * Fixed blog URLs including urlnames
 *
 * Revision 1.61  2007/01/23 07:31:22  fplanque
 * "fixed" as per todo
 *
 * Revision 1.60  2007/01/23 05:30:20  fplanque
 * "Contact the owner"
 *
 * Revision 1.59  2007/01/23 04:19:50  fplanque
 * handling of blog owners
 *
 * Revision 1.58  2007/01/23 03:45:56  fplanque
 * bugfix
 *
 * Revision 1.57  2007/01/16 00:44:42  fplanque
 * don't use $admin_email in  the app
 *
 * Revision 1.56  2007/01/15 19:28:39  blueyed
 * doc
 *
 * Revision 1.55  2007/01/15 03:54:36  fplanque
 * pepped up new blog creation a little more
 *
 * Revision 1.54  2007/01/15 00:38:06  fplanque
 * pepped up "new blog" creation a little. To be continued.
 *
 * Revision 1.53  2007/01/14 01:33:34  fplanque
 * losely restrict to *installed* XML feed skins
 *
 * Revision 1.52  2007/01/08 02:11:55  fplanque
 * Blogs now make use of installed skins
 * next step: make use of widgets inside of skins
 *
 * Revision 1.51  2006/12/23 23:37:35  fplanque
 * refactoring / Blog::get_default_cat_ID()
 *
 * Revision 1.50  2006/12/23 23:15:19  fplanque
 * refactoring / Blog::get_allowed_item_status()
 *
 * Revision 1.49  2006/12/22 00:50:33  fplanque
 * improved path cleaning
 *
 * Revision 1.48  2006/12/21 22:25:43  fplanque
 * Removed restricting constraint. (It may have been good for hiding a bug, but it restricts the purpose)
 *
 * Revision 1.47  2006/12/19 21:40:17  blueyed
 * Test if baseurl is valid by testing if "htsrv/" is accessible below it; see http://forums.b2evolution.net/viewtopic.php?p=48707#48707 et seqq.
 *
 * Revision 1.46  2006/12/17 23:42:38  fplanque
 * Removed special behavior of blog #1. Any blog can now aggregate any other combination of blogs.
 * Look into Advanced Settings for the aggregating blog.
 * There may be side effects and new bugs created by this. Please report them :]
 *
 * Revision 1.45  2006/12/16 01:30:46  fplanque
 * Setting to allow/disable email subscriptions on a per blog basis
 *
 * Revision 1.44  2006/12/14 21:41:15  fplanque
 * Allow different number of items in feeds than on site
 *
 * Revision 1.43  2006/12/14 00:01:49  fplanque
 * land in correct collection when opening FM from an Item
 *
 * Revision 1.42  2006/12/13 18:23:36  blueyed
 * doc
 *
 * Revision 1.41  2006/12/10 23:56:26  fplanque
 * Worfklow stuff is now hidden by default and can be enabled on a per blog basis.
 *
 * Revision 1.40  2006/12/07 23:13:10  fplanque
 * @var needs to have only one argument: the variable type
 * Otherwise, I can't code!
 *
 * Revision 1.39  2006/12/04 23:49:49  blueyed
 * Normalized: setMediaUrl() => set_media_url(); setMediaFullPath() => set_media_fullpath(); setMediaSubDir() => set_media_subdir()
 *
 * Revision 1.38  2006/12/04 21:25:18  fplanque
 * removed user skin switching
 *
 * Revision 1.37  2006/12/04 19:41:11  fplanque
 * Each blog can now have its own "archive mode" settings
 *
 * Revision 1.36  2006/12/04 18:16:50  fplanque
 * Each blog can now have its own "number of page/days to display" settings
 *
 * Revision 1.35  2006/11/28 00:33:01  blueyed
 * Removed DB::compString() (never used) and DB::get_list() (just a macro and better to have in the 4 used places directly; Cleanup/normalization; no extended regexp, when not needed!
 *
 * Revision 1.34  2006/11/24 18:27:23  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.33  2006/11/13 20:49:52  fplanque
 * doc/cleanup :/
 *
 * Revision 1.32  2006/10/23 22:19:02  blueyed
 * Fixed/unified encoding of redirect_to param. Use just rawurlencode() and no funky &amp; replacements
 *
 * Revision 1.31  2006/10/14 04:43:35  blueyed
 * Removed last allowpingbacks references
 *
 * Revision 1.30  2006/10/10 23:24:41  blueyed
 * Fixed duplication of ping plugins from hidden values
 *
 * Revision 1.29  2006/10/01 22:11:42  blueyed
 * Ping services as plugins.
 */
?>
