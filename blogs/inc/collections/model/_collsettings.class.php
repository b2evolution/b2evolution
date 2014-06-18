<?php
/**
 * This file implements the CollectionSettings class which handles
 * coll_ID/name/value triplets for collections/blogs.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: _collsettings.class.php 6643 2014-05-08 13:08:08Z yura $
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
			'count_custom_double' => 0,
			'count_custom_varchar' => 0,
			'show_location_coordinates' => 0,
			'tags_meta_keywords' => 1,
			'slug_limit' => 5,
			'tags_open_graph' => 1,

		// Comment settings:
			// 'new_feedback_status' => 'review',		// Default status for new anonymous comments: 'published', 'community', 'protected', 'private', 'review', 'draft' or 'deprecated'. We don't specify a general default because it depends from the blog type ( see @Blog::get_setting() )
			// 'moderation_statuses' => NULL,			// Possible values are a list of statuses from: 'community', 'protected', 'review', 'draft', but we don't specify a general default because it depends from the blog type ( see @Blog::get_setting() )
			// 'comment_inskin_statuses' => NULL,       // Possible value is a set of statuses wihtout the 'trash' status, but we don't specify a general default because it depends from the blog type ( see @Blog::get_setting() )
			// 'post_inskin_statuses' => NULL,          // Same as in case of comments
			'allow_comments' => 'any',
			'allow_view_comments' => 'any',				// 'any', 'registered', 'member', 'moderator'
			'disable_comments_bypost' => '1',
			'allow_anon_url' => '0',
			'allow_attachments' => 'registered',
			'max_attachments' => '',
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
			'chapter_content'   => 'excerpt',
			'chapter_posts_per_page' => 100,
			'chapter_noindex'   => '1',					// META NOINDEX on Category pages
			'category_prefix'   => '',
			'categories_meta_description' => 1,

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
			'require_title' => 'required',				// Is a title for items required ("required", "optional", "none")
			'ping_plugins'   => 'ping_pingomatic,ping_b2evonet,evo_twitter', // ping plugin codes, separated by comma
			'allow_subscriptions' => 0,					// Don't allow email subscriptions by default
			'allow_item_subscriptions' => 0,			// Don't allow email subscriptions for a specific post by default
			'use_workflow' => 0,						// Don't use workflow by default
			'aggregate_coll_IDs' => '',
			'blog_footer_text' => '&copy;$year$ by $owner$',
			'max_footer_credits' => 3,
			'enable_goto_blog' => 'blog',  // 'no' - No redirect, 'blog' - Go to blog after publishing post, 'post' - Redirect to permanent post url
			'editing_goto_blog' => 'post', // 'no' - No redirect, 'blog' - Go to blog after editing post, 'post' - Redirect to permanent post url
			// 'default_post_status' => 'draft',		// Default status for new posts ("published", "community", "protected", "private", "review", "draft", "deprecated", "redirected"). We don't specify a general default because it depends from the blog type ( see @Blog::get_setting() )
			'post_categories' => 'main_extra_cat_post', // Post category setting
			'post_navigation' => 'same_blog',           // Default post by post navigation should stay in the same blog, category or author
			'blog_head_includes' => '',
			'blog_footer_includes' => '',
			'allow_html_post' => 1, // Allow HTML in posts
			'allow_html_comment' => 1, // Allow HTML in comments

		// Other settings:
			'image_size_user_list' => 'crop-top-32x32', // Used in disp = users
			'image_size_messaging' => 'crop-top-32x32', // Used in disp = threads

		// Time frame settings:
			'timestamp_min' => 'yes',
			'timestamp_max' => 'no',

		// Back-end settings, these can't be modified by the users, it will be modified from code:
			'last_invalidation_timestamp' => 0,

		// Location
			'location_country'   => 'hidden', // Editing mode of country for item:   "optional" | "required" | "hidden"
			'location_region'    => 'hidden', // Editing mode of region for item:    "optional" | "required" | "hidden"
			'location_subregion' => 'hidden', // Editing mode of subregion for item: "optional" | "required" | "hidden"
			'location_city'      => 'hidden', // Editing mode of city for item:      "optional" | "required" | "hidden"
		
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
	 * When custom fields are changed it may require other updates on different tables.
	 * These changes must be processed only if the collection settings were also saved.
	 *
	 * @access private
	 *
	 * @var array which contains update/delete queries
	 */
	var $update_cascade = array();


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


	/**
	 * Commit changed settings to DB.
	 *
	 * @return boolean true, if settings have been updated; false otherwise
	 */
	function dbupdate()
	{
		global $DB;

		if( !parent::dbupdate() )
		{ // Collection settings couldn't been updated successful
			return false;
		}

		if( !empty( $this->update_cascade ) )
		{ // process update cascade commands
			foreach( $this->update_cascade as $query )
			{
				if( $DB->query( $query ) === false )
				{ // error occured
					return false;
				}
			}
			$this->update_cascade = array();
		}

		return true;
	}


	/**
	 * Add an update/delete query which must be processed if collection settings will be updated
	 *
	 * @param string query
	 */
	function add_update_cascade( $query )
	{
		if( empty( $query ) )
		{
			return;
		}
		$this->update_cascade[] = $query;
	}


	/**
	 * Remove all registered update query
	 */
	function clear_update_cascade()
	{
		$this->update_cascade = array();
	}
}

?>
