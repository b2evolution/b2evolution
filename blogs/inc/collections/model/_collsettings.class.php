<?php
/**
 * This file implements the CollectionSettings class which handles
 * coll_ID/name/value triplets for collections/blogs.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
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

load_class('settings/model/_abstractsettings.class.php');

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
			'new_feedback_status' => 'draft',  	// 'draft', 'published' or 'deprecated'

			'single_links'   => 'param_title',
			'permalinks'     => 'single',				// single, archive, subchap

			'ping_plugins'   => 'ping_pingomatic,ping_b2evonet', // ping plugin codes, separated by comma
			'orderby'        => 'datestart',
			'orderdir'       => 'DESC',
			'what_to_show'   => 'posts',        // posts, days
			'posts_per_page' => '5',
			'feed_content'   => 'normal',
			'posts_per_feed' => '8',
			'allow_subscriptions' => 0,					// Don't all email subscriptions by default
			'use_workflow' => 0,								// Don't use workflow by default
			'aggregate_coll_IDs' => '',
			'allow_rating'   => 'never',

			'canonical_item_urls' => 1,					// Redirect posts to their canonical Url?
			'default_noindex' => '0',						// META NOINDEX on Default blog page

			'paged_noindex' => '1',							// META NOINDEX on following blog pages
			'paged_nofollowto' => '0',          // NOFOLLOW on links to following blog pages

			'archive_mode'   => 'monthly',			// monthly, weekly, daily, postbypost
			'archive_links'  => 'param',				// param, extrapath
			'archive_posts_per_page' => '100',
			'archive_noindex' => '1',						// META NOINDEX on Archive pages
			'archive_nofollowto' => '0',        // NOFOLLOW on links to archive pages

			'chapter_links'  => 'param_num',		// 'param_num', 'subchap', 'chapters'
			'canonical_cat_urls' => 1,					// Redirect categories to their canonical Url?
			'chapter_posts_per_page' => NULL,
			'chapter_noindex' => '1',						// META NOINDEX on Category pages
			'category_prefix' => '',

			'tag_links'  => 'param',						// 'param', 'semicol'
			'canonical_tag_urls' => 1,					// Redirect tag pages to their canonical Url?
			'tag_posts_per_page' => NULL,
			'tag_noindex' => '1',				      	// META NOINDEX on Tag pages
			'tag_prefix' => '',

			'filtered_noindex' => '1',					// META NOINDEX on other filtered pages
			'arcdir_noindex' => '1',						// META NOINDEX on Archive directory
			'catdir_noindex' => '1',						// META NOINDEX on Category directory
			'feedback-popup_noindex' => '1',		// META NOINDEX on Feedback popups
			'msgform_noindex' => '1',						// META NOINDEX on Message forms
			'special_noindex' => '1',						// META NOINDEX on other special pages
			'title_link_type' => 'permalink',

			'blog_footer_text' => '&copy;$year$ by $owner$',
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