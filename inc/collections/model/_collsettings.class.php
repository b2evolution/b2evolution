<?php
/**
 * This file implements the CollectionSettings class which handles
 * coll_ID/name/value triplets for collections/blogs.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'settings/model/_abstractsettings.class.php', 'AbstractSettings' );

/**
 * Class to handle the settings for collections/blogs
 *
 * @package evocore
 */
class CollectionSettings extends AbstractSettings
{
	/**
	 * The default settings to use, when a setting is not defined in the database.
	 *
	 * @access protected
	 */
	var $_defaults = array(
		// Home page settings:
			'front_disp'             => 'posts',
			'what_to_show'           => 'posts',        // posts, days
			'main_content'           => 'normal',
			'posts_per_page'         => '5',
			'canonical_homepage'     => 1,				// Redirect homepage to its canonical Url?
			'relcanonical_homepage'  => 1,				// If no 301, fall back to rel="canoncial" ?
			'default_noindex'        => '0',			// META NOINDEX on Default blog page
			// the following are actually general params but are probably best understood if being presented with the home page params
			'orderby'         => 'datestart',
			'orderdir'        => 'DESC',
			'title_link_type' => 'permalink',
			'permalinks'      => 'single',				// single, archive, subchap

		// Page 2,3,4..; settings:
			'paged_noindex' => '1',						// META NOINDEX on following blog pages
			'paged_nofollowto' => '0',					// NOFOLLOW on links to following blog pages

		// Single post settings:
			'canonical_item_urls' => 1,					// Redirect posts to their canonical Url?
			'relcanonical_item_urls' => 1,				// If no 301, fall back to rel="canoncial" ?
			'single_links'   => 'short',
			'single_item_footer_text' => '',
			'slug_limit' => 5,
			'tags_meta_keywords' => 1,
			'tags_open_graph' => 1,
			// 'post_moderation_statuses' => NULL,			// Possible values are a list of statuses from: 'community', 'protected', 'review', 'draft', but we don't specify a general default because it depends from the blog type ( see @Blog::get_setting() )

		// Comment settings:
			// 'new_feedback_status' => 'review',		// Default status for new anonymous comments: 'published', 'community', 'protected', 'private', 'review', 'draft' or 'deprecated'. We don't specify a general default because it depends from the blog type ( see @Blog::get_setting() )
			// 'moderation_statuses' => NULL,			// Possible values are a list of statuses from: 'community', 'protected', 'review', 'draft', but we don't specify a general default because it depends from the blog type ( see @Blog::get_setting() )
			// 'comment_inskin_statuses' => NULL,       // Possible value is a set of statuses wihtout the 'trash' status, but we don't specify a general default because it depends from the blog type ( see @Blog::get_setting() )
			// 'post_inskin_statuses' => NULL,          // Same as in case of comments
			'allow_comments' => 'any',
			'allow_view_comments' => 'any',				// 'any', 'registered', 'member', 'moderator'
			'allow_anon_url' => '0',
			'allow_attachments' => 'registered',
			'max_attachments' => '',
			'display_rating_summary' => '1', // Display a summary of star ratings above the comments
			'allow_rating_items' => 'never',
			'allow_rating_comment_helpfulness' => '0',
			'comments_orderdir' => 'ASC',
			'threaded_comments' => '0',
			'paged_comments' => '0',
			'comments_per_page' => 1000,
			'comments_avatars' => '1',
			'comments_latest' => '1',
			'comments_detect_email' => 1,
			'comments_register' => 1,
			'comment_quick_moderation' => 'expire',		// Comment quick moderation can be 'never', 'expire' - Links expire on first edit action, and 'always'
			'autocomplete_usernames' => 1,

		// Archive settings:
			'arcdir_noindex' => '1',					// META NOINDEX on Archive directory
			'archive_mode'   => 'monthly',				// monthly, weekly, daily, postbypost
			'archive_links'  => 'extrapath',			// param, extrapath
			'canonical_archive_urls' => 1,				// Redirect archives to their canonical URL?
			'relcanonical_archive_urls' => 1,			// If no 301, fall back to rel="canoncial" ?
			'archive_content'   => 'excerpt',
			'archive_posts_per_page' => '100',
			'archive_noindex' => '1',					// META NOINDEX on Archive pages
			'archive_nofollowto' => '0',				// NOFOLLOW on links to archive pages
			'archives_sort_order' => 'date',

		// Chapter/Category settings:
			'catdir_noindex' => '1',					// META NOINDEX on Category directory
			'chapter_links'  => 'chapters',				// 'param_num', 'subchap', 'chapters'
			'canonical_cat_urls' => 1,					// Redirect categories to their canonical URL?
			'relcanonical_cat_urls' => 1,				// If no 301, fall back to rel="canoncial" ?
			'chapter_content'   => 'normal',
			'chapter_posts_per_page' => 100,
			'chapter_noindex'   => '1',					// META NOINDEX on Category pages
			'category_prefix'   => '',
			'categories_meta_description' => 1,
			'category_ordering' => 'alpha',             // Ordering of categories

		// Tag page settings:
			'tag_links'  => 'colon',					// 'param', 'semicolon' -- fp> we want this changed to prefix only for new blogs only
			'canonical_tag_urls' => 1,					// Redirect tag pages to their canonical Url?
			'relcanonical_tag_urls' => 1,				// If no 301, fall back to rel="canoncial" ?
			'tag_content'       => 'excerpt',
			'tag_posts_per_page' => 100,
			'tag_noindex' => '1',				      	// META NOINDEX on Tag pages
			'tag_prefix' => '',							// fp> fp> we want this changed to prefix only for new blogs only
			'tag_rel_attrib' => 1,						// rel="tag" attribute for tag links (http://microformats.org/wiki/rel-tag) -- valid only in prefix-only mode

		// Other filtered pages:
			'filtered_noindex' => '1',					// META NOINDEX on other filtered pages
			'filtered_content'  => 'excerpt',

		// Other pages:
			'feedback-popup_noindex' => '1',			// META NOINDEX on Feedback popups
			'msgform_noindex' => '1',					// META NOINDEX on Message forms
			'special_noindex' => '1',					// META NOINDEX on other special pages
			'404_response' => '404',
			'help_link' => 'slug',

		// Feed settings: (should probably be duplicated for comment feed, category feeds, etc...)
			'atom_redirect' => '',
			'rss2_redirect' => '',
			'feed_content'   => 'normal',
			'posts_per_feed' => '8',
			'xml_item_footer_text' => '<p><small><a href="$perm_url$">Original post</a> blogged on <a href="http://b2evolution.net/">b2evolution</a>.</small></p>',
			'image_size'	=> 'fit-320x320',

		// Comment feed settings:
			'comment_feed_content' => 'normal',
			'comments_per_feed' => '8',

		// Sitemaps settings:
			'enable_sitemaps' => 1,

		// General settings:
			'ajax_form_enabled' => 0,					// Comment and contacts forms will be fetched by javascript
			'ajax_form_loggedin_enabled' => 0,			// Also use JS forms for logged in users
			'cache_enabled' => 0,
			'cache_enabled_widgets' => 0,
			'in_skin_login' => 0,						// Use in skin login form every time it's possible
			'in_skin_editing' => 0,
			'default_cat_ID' => NULL,					// Default Cat for new posts
			'ping_plugins'   => 'ping_pingomatic,ping_b2evonet,evo_twitter', // ping plugin codes, separated by comma
			'allow_subscriptions' => 0,      // Don't allow email subscriptions by default
			'allow_item_subscriptions' => 0, // Don't allow email subscriptions for a specific post by default
			'use_workflow' => 0,						// Don't use workflow by default
			'aggregate_coll_IDs' => '',
			'blog_footer_text' => 'This collection &copy;$year$ by $owner$',
			'max_footer_credits' => 3,
			'enable_goto_blog' => 'blog',  // 'no' - No redirect, 'blog' - Go to blog after publishing post, 'post' - Redirect to permanent post url
			'editing_goto_blog' => 'post', // 'no' - No redirect, 'blog' - Go to blog after editing post, 'post' - Redirect to permanent post url
			'default_post_type' => '1', // Default type for new posts, value is ID of post type from table T_items__type
			// 'default_post_status' => 'draft',		// Default status for new posts ("published", "community", "protected", "private", "review", "draft", "deprecated", "redirected"). We don't specify a general default because it depends from the blog type ( see @Blog::get_setting() )
			'post_categories' => 'main_extra_cat_post', // Post category setting
			'post_navigation' => 'same_blog',           // Default post by post navigation should stay in the same blog, category, author or tag
			'blog_head_includes' => '',
			'blog_footer_includes' => '',
			'allow_html_comment' => 1, // Allow HTML in comments
			'track_unread_content' => 0, // Should we track unread content on the specific blog. It can be modified on the Features/Other settings form.
			'allow_access' => 'public', // Allow access to blog; Values: 'public' - Everyone (Public Blog), 'users' - Logged in users, 'members' - Members of the blog
			'locale_source' => 'blog', // Source of the locale for navigation/widget: 'blog', 'user'
			'post_locale_source' => 'post', // Source of the locale for post content: 'post', 'blog'

		// Other settings:
			'image_size_user_list' => 'crop-top-48x48', // Used in disp = users
			'image_size_messaging' => 'crop-top-32x32', // Used in disp = threads
			'search_per_page'      => 20, // Number of results per page on disp=search
			'latest_comments_num'  => 20, // Number of the shown comments on disp=comments

		// Time frame settings:
			'timestamp_min' => 'yes',
			'timestamp_max' => 'no',

		// Back-end settings, these can't be modified by the users, it will be modified from code:
			'last_invalidation_timestamp' => 0,

		// Download settings:
			'download_delay' => 5,
			'download_noindex' => 1,
			'download_nofollowto' => 1,
		);

	/**
	 *  Configurable default settings
	 *
	 *  These settings default is defined in general settings
	 *
	 *  Skin settings:
	 *  'normal_skin_ID' => NULL,
	 *  'mobile_skin_ID' => NULL,
	 *  'tablet_skin_ID' => NULL,
	 */


	/**
	 * Constructor
	 */
	function CollectionSettings()
	{
		parent::AbstractSettings( 'T_coll_settings', array( 'cset_coll_ID', 'cset_name' ), 'cset_value', 1 );
	}

	/**
	 * Loads the settings. Not meant to be called directly, but gets called
	 * when needed.
	 *
	 * @access protected
	 * @param string First column key
	 * @param string Second column key
	 * @return boolean
	 */
	function _load( $coll_ID, $arg )
	{
		if( empty( $coll_ID ) )
		{
			return false;
		}

		return parent::_load( $coll_ID, $arg );
	}
}

?>