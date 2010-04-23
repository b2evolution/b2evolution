<?php
/**
 * This file implements the CollectionSettings class which handles
 * coll_ID/name/value triplets for collections/blogs.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
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
			'what_to_show'           => 'posts',        // posts, days
			'main_content'           => 'normal',
			'posts_per_page'         => '5',
			'canonical_homepage'     => 1,					// Redirect homepage to its canonical Url?
			'relcanonical_homepage'  => 1,				// If no 301, fall back to rel="canoncial" ?
			'default_noindex'        => '0',						// META NOINDEX on Default blog page
			// the following are actually general params but are probably best understood if being presented with the home page params
			'orderby'         => 'datestart',
			'orderdir'        => 'DESC',
			'title_link_type' => 'permalink',
			'permalinks'      => 'single',				// single, archive, subchap

		// Page 2,3,4..; settings:
			'paged_noindex' => '1',							// META NOINDEX on following blog pages
			'paged_nofollowto' => '0',          // NOFOLLOW on links to following blog pages

		// Single post settings:
			'canonical_item_urls' => 1,					// Redirect posts to their canonical Url?
			'relcanonical_item_urls' => 1,			// If no 301, fall back to rel="canoncial" ?
			'single_links'   => 'short',
			'single_item_footer_text' => '',

		// Comment settings:
			'new_feedback_status' => 'draft',  	// 'draft', 'published' or 'deprecated'
			'allow_rating'   => 'never',
			'comments_orderdir' => 'ASC',

		// Archive settings:
			'arcdir_noindex' => '1',						// META NOINDEX on Archive directory
			'archive_mode'   => 'monthly',			// monthly, weekly, daily, postbypost
			'archive_links'  => 'extrapath',		// param, extrapath
			'canonical_archive_urls' => 1,					// Redirect archives to their canonical URL?
			'relcanonical_archive_urls' => 1,				// If no 301, fall back to rel="canoncial" ?
			'archive_content'   => 'excerpt',
			'archive_posts_per_page' => '100',
			'archive_noindex' => '1',						// META NOINDEX on Archive pages
			'archive_nofollowto' => '0',        // NOFOLLOW on links to archive pages
			'archives_sort_order' => 'date',

		// Chapter/Category settings:
			'catdir_noindex' => '1',						// META NOINDEX on Category directory
			'chapter_links'  => 'chapters',			// 'param_num', 'subchap', 'chapters'
			'canonical_cat_urls' => 1,					// Redirect categories to their canonical URL?
			'relcanonical_cat_urls' => 1,				// If no 301, fall back to rel="canoncial" ?
			'chapter_content'   => 'excerpt',
			'chapter_posts_per_page' => NULL,
			'chapter_noindex'   => '1',						// META NOINDEX on Category pages
			'category_prefix'   => '',

		// Tag page settings:
			'tag_links'  => 'colon',						// 'param', 'semicolon' -- fp> we want this changed to prefix only for new blogs only
			'canonical_tag_urls' => 1,					// Redirect tag pages to their canonical Url?
			'relcanonical_tag_urls' => 1,				// If no 301, fall back to rel="canoncial" ?
			'tag_content'       => 'excerpt',
			'tag_posts_per_page' => NULL,
			'tag_noindex' => '1',				      	// META NOINDEX on Tag pages
			'tag_prefix' => '',									// fp> fp> we want this changed to prefix only for new blogs only
			'tag_rel_attrib' => 1,              // rel="tag" attribute for tag links (http://microformats.org/wiki/rel-tag) -- valid only in prefix-only mode

		// Other filtered pages:
			'filtered_noindex' => '1',					// META NOINDEX on other filtered pages
			'filtered_content'  => 'excerpt',

		// Other pages:
			'feedback-popup_noindex' => '1',		// META NOINDEX on Feedback popups
			'msgform_noindex' => '1',						// META NOINDEX on Message forms
			'special_noindex' => '1',						// META NOINDEX on other special pages
			'404_response' => '404',
			'help_link' => 'param',

		// Feed settings: (should probably be duplicated for comment feed, category feeds, etc...)
			'atom_redirect' => '',
			'rss2_redirect' => '',
			'feed_content'   => 'normal',
			'posts_per_feed' => '8',
			'xml_item_footer_text' => '<p><small><a href="$item_perm_url$">Original post</a> blogged on <a href="http://b2evolution.net/">b2evolution</a>.</small></p>',
			'image_size'	=> 'fit-320x320',

		// Sitemaps settings:
			'enable_sitemaps' => 1,

		// General settings:
			'cache_enabled' => 0,
			'cache_enabled_widgets' => 0,
			'default_cat_ID' => NULL,						// Default Cat for new posts
			'require_title' => 'required',  		// Is a title for items required ("required", "optional", "none")
			'ping_plugins'   => 'ping_pingomatic,ping_b2evonet,evo_twitter', // ping plugin codes, separated by comma
			'allow_subscriptions' => 0,					// Don't all email subscriptions by default
			'use_workflow' => 0,								// Don't use workflow by default
			'aggregate_coll_IDs' => '',
			'blog_footer_text' => '&copy;$year$ by $owner$',
			'max_footer_credits' => 3,
			'enable_goto_blog' => 1,						// Go to blog after publishing post
		);


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


/*
 * $Log$
 * Revision 1.46  2010/04/23 09:39:44  efy-asimo
 * "SEO setting" for help link and Groups slugs permission implementation
 *
 * Revision 1.45  2010/04/12 15:14:25  efy-asimo
 * resolver bug - fix
 *
 * Revision 1.44  2010/02/26 22:15:52  fplanque
 * whitespace/doc/minor
 *
 * Revision 1.42  2010/02/08 17:52:09  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.41  2010/02/06 11:48:32  efy-yury
 * add checkbox 'go to blog after posting' in blog settings
 *
 * Revision 1.40  2010/01/20 20:08:30  efy-asimo
 * Countries&Currencies redirect fix + RSS/Atom feeds image size select list
 *
 * Revision 1.39  2009/11/30 04:31:38  fplanque
 * BlockCache Proof Of Concept
 *
 * Revision 1.38  2009/09/29 16:56:12  tblue246
 * Added setting to disable sitemaps skins
 *
 * Revision 1.37  2009/09/14 12:43:05  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.36  2009/08/27 12:24:27  tblue246
 * Added blog setting to display comments in ascending/descending order
 *
 * Revision 1.35  2009/08/27 11:54:39  tblue246
 * General blog settings: Added default value for archives_sort_order
 *
 * Revision 1.34  2009/05/26 17:36:41  fplanque
 * Have twitter plugin enabled by default. (it still won't tweet until user enters credentials)
 *
 * Revision 1.33  2009/05/21 12:34:39  fplanque
 * Options to select how much content to display (excerpt|teaser|normal) on different types of pages.
 *
 * Revision 1.32  2009/05/20 18:27:09  fplanque
 * canonical support for date archives
 *
 * Revision 1.31  2009/05/20 12:58:17  fplanque
 * Homepage: option to 301 redirect to canonical homepage.
 * Option to support rel="canonical" instead of or when 301 redirect cannot be used.
 *
 * Revision 1.30  2009/05/17 19:51:10  fplanque
 * minor/doc
 *
 * Revision 1.29  2009/04/22 22:46:33  blueyed
 * Add support for rel=tag in tag URLs. This adds a new tag_links mode 'prefix-only', which requires a prefix (default: tag) and uses no suffix (dash/colon/semicolon). Also adds more JS juice and cleans up/normalized previously existing JS. Not much tested, but implemented as discussed on ML.
 *
 * Revision 1.28  2009/03/20 04:04:07  fplanque
 * minor
 *
 * Revision 1.27  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.26  2009/01/28 22:34:21  fplanque
 * Default cat for each blog can now be chosen explicitely
 *
 * Revision 1.25  2008/10/05 10:55:46  tblue246
 * Blog by mail: We've only one working method => removed the drop-down box and added automatical change to pop3a.
 * The default value for this setting was in the wrong file, moved.
 *
 * Revision 1.24  2008/10/05 06:28:32  fplanque
 * no message
 *
 * Revision 1.23  2008/10/04 14:25:25  tblue246
 * Code improvements in blog/cron/getmail.php, e. g. option to add <img> tags for image attachments.
 * All attachments now get added to the post if the filename is valid (validate_filename()). Not sure if this is secure, but should be.
 *
 * Revision 1.22  2008/09/27 00:48:32  fplanque
 * caching step 0.
 *
 * Revision 1.21  2008/09/09 06:03:30  fplanque
 * More tag URL options
 * Enhanced URL resolution for categories and tags
 *
 * Revision 1.20  2008/06/30 23:47:04  blueyed
 * require_title setting for Blogs, defaulting to 'required'. This makes the title field now a requirement (by default), since it often gets forgotten when posting first (and then the urltitle is ugly already)
 *
 * Revision 1.19  2008/05/06 23:25:34  fplanque
 * minor
 *
 * Revision 1.18  2008/04/19 15:14:35  waltercruz
 * Feedburner
 *
 * Revision 1.17  2008/04/04 16:02:10  fplanque
 * uncool feature about limiting credits
 *
 * Revision 1.16  2008/03/21 19:42:44  fplanque
 * enhanced 404 handling
 *
 * Revision 1.15  2008/02/18 20:22:40  fplanque
 * no message
 *
 * Revision 1.14  2008/02/05 01:51:54  fplanque
 * minors
 *
 * Revision 1.13  2008/01/21 09:35:26  fplanque
 * (c) 2008
 *
 * Revision 1.12  2008/01/17 17:43:52  fplanque
 * cleaner urls by default
 *
 * Revision 1.11  2008/01/17 14:38:30  fplanque
 * Item Footer template tag
 *
 * Revision 1.10  2008/01/15 08:19:36  fplanque
 * blog footer text tag
 *
 * Revision 1.9  2008/01/08 03:28:11  fplanque
 * minor
 *
 * Revision 1.8  2008/01/07 02:53:26  fplanque
 * cleaner tag urls
 *
 * Revision 1.7  2007/11/25 18:20:38  fplanque
 * additional SEO settings
 *
 * Revision 1.6  2007/11/25 14:28:17  fplanque
 * additional SEO settings
 *
 * Revision 1.5  2007/11/24 21:41:12  fplanque
 * additional SEO settings
 *
 * Revision 1.4  2007/11/03 04:56:03  fplanque
 * permalink / title links cleanup
 *
 * Revision 1.3  2007/11/02 01:46:53  fplanque
 * comment ratings
 *
 * Revision 1.2  2007/09/28 09:28:36  fplanque
 * per blog advanced SEO settings
 *
 * Revision 1.1  2007/06/25 10:59:33  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.17  2007/05/13 22:53:31  fplanque
 * allow feeds restricted to post excerpts
 *
 * Revision 1.16  2007/04/26 00:11:06  fplanque
 * (c) 2007
 *
 * Revision 1.15  2007/03/24 20:41:16  fplanque
 * Refactored a lot of the link junk.
 * Made options blog specific.
 * Some junk still needs to be cleaned out. Will do asap.
 *
 * Revision 1.14  2007/01/23 09:25:40  fplanque
 * Configurable sort order.
 *
 * Revision 1.13  2007/01/15 03:54:36  fplanque
 * pepped up new blog creation a little more
 *
 * Revision 1.12  2006/12/17 23:42:38  fplanque
 * Removed special behavior of blog #1. Any blog can now aggregate any other combination of blogs.
 * Look into Advanced Settings for the aggregating blog.
 * There may be side effects and new bugs created by this. Please report them :]
 *
 * Revision 1.11  2006/12/16 01:30:46  fplanque
 * Setting to allow/disable email subscriptions on a per blog basis
 *
 * Revision 1.10  2006/12/14 21:41:15  fplanque
 * Allow different number of items in feeds than on site
 *
 * Revision 1.9  2006/12/10 23:56:26  fplanque
 * Worfklow stuff is now hidden by default and can be enabled on a per blog basis.
 *
 * Revision 1.8  2006/12/04 19:41:11  fplanque
 * Each blog can now have its own "archive mode" settings
 *
 * Revision 1.7  2006/12/04 18:16:50  fplanque
 * Each blog can now have its own "number of page/days to display" settings
 *
 * Revision 1.6  2006/11/24 18:27:23  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.5  2006/10/10 23:29:01  blueyed
 * Fixed default for "ping_plugins"
 *
 * Revision 1.4  2006/10/01 22:11:42  blueyed
 * Ping services as plugins.
 */
?>
