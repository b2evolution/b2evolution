<?php
/**
 * This file implements the Chapter class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _chapter.class.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'generic/model/_genericcategory.class.php', 'GenericCategory' );


/**
 * Chapter Class
 *
 * @package evocore
 */
class Chapter extends GenericCategory
{
	/**
	 * @var integer
	 */
	var $blog_ID;
	/**
	 * The Blog of the Item (lazy filled, use {@link get_Blog()} to access it.
	 * @access protected
	 * @var Blog
	 */
	var $Blog;

	var $urlname;
	var $description;
	var $order;
	var $meta;
	var $lock;

	/**
	 * Lazy filled
	 * @var Chapter
	 */
	var $parent_Chapter;

	/**
	 * Constructor
	 *
	 * @param table Database row
 	 * @param integer|NULL subset to use for new object
	 */
	function Chapter( $db_row = NULL, $subset_ID = NULL )
	{
		// Call parent constructor:
		parent::GenericCategory( 'T_categories', 'cat_', 'cat_ID', $db_row );

		/**
		 * Delete restrictions
		 */
		$this->delete_restrictions = array(
				array( 'table'=>'T_categories', 'fk'=>'cat_parent_ID', 'msg'=>T_('%d sub categories') ),
				array( 'table'=>'T_items__item', 'fk'=>'post_main_cat_ID', 'msg'=>T_('%d posts within category through main cat') ),
				array( 'table'=>'T_postcats', 'fk'=>'postcat_cat_ID', 'msg'=>T_('%d posts within category through extra cat') ),
			);

		if( is_null($db_row) )
		{	// We are creating an object here:
			$this->set( 'blog_ID', $subset_ID );
		}
		else
		{	// Wa are loading an object:
			$this->blog_ID = $db_row->cat_blog_ID;
			$this->urlname = $db_row->cat_urlname;
			$this->description = $db_row->cat_description;
			$this->order = $db_row->cat_order;
			$this->meta = $db_row->cat_meta;
			$this->lock = $db_row->cat_lock;
		}
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_request()
	{
		global $DB, $Settings;

		parent::load_from_Request();

		// Check url name
		param( 'cat_urlname', 'string' );
		$this->set_from_Request( 'urlname' );

		// Check description
		param( 'cat_description', 'string' );
		$this->set_from_Request( 'description' );

		if( $Settings->get('chapter_ordering') == 'manual' )
		{	// Manual ordering
			param( 'cat_order', 'integer' );
			$this->set_from_Request( 'order' );
		}

		// Meta category
		$cat_meta = param( 'cat_meta', 'integer', 0 );
		if( $this->has_posts() && $cat_meta )
		{	// Display error message if we want make the meta category from category with posts
			global $Messages;
			$Messages->add( sprintf( T_('The category &laquo;%s&raquo; cannot be set as meta category. You must remove the posts it contains first.'), $this->dget('name') ) );
		}
		else
		{	// Save the category as 'Meta' only if it has no posts
			$this->set_from_Request( 'meta' );
		}

		// Locked category
		param( 'cat_lock', 'integer', 0 );
		$this->set_from_Request( 'lock' );

		return ! param_errors_detected();
	}


	/**
	 *
	 */
	function & get_parent_Chapter()
	{
		if( ! isset( $this->parent_Chapter ) )
		{	// Not resoleved yet!
			if( empty( $this->parent_ID ) )
			{
				$this->parent_Chapter = NULL;
			}
			else
			{
				$ChapterCache = & get_ChapterCache();
				$this->parent_Chapter = & $ChapterCache->get_by_ID( $this->parent_ID, false );
			}
		}

		return $this->parent_Chapter;
	}


	/**
	 * Get URL path (made of URL names) back to the root
	 */
	function get_url_path()
	{
		$r = $this->urlname.'/';

		$parent_Chapter = & $this->get_parent_Chapter();
		if( !is_null( $parent_Chapter ) )
		{	// Recurse:
			$r = $parent_Chapter->get_url_path().$r;
		}

		return $r;
	}


	/**
	 * Generate the URL to access the category.
	 *
	 * @param string|NULL 'param_num', 'subchap', 'chapters'
	 * @param string|NULL url to use
	 * @param integer category page to link to, default:1
	 * @param integer|NULL number of posts per page (used for param_num only)
	 * @param string glue between url params
	 */
	function get_permanent_url( $link_type = NULL, $blogurl = NULL, $paged = 1, $chapter_posts_per_page = NULL, $glue = '&amp;' )
	{
		global $DB, $cacheweekly, $Settings;

		if( empty( $link_type ) )
		{	// Use default from settings:
			$this->get_Blog();
			$link_type = $this->Blog->get_setting( 'chapter_links' );
		}

		if( empty( $blogurl ) )
		{
			$this->get_Blog();
			$blogurl = $this->Blog->gen_blogurl();
		}

		switch( $link_type )
		{
			case 'param_num':
				$r = url_add_param( $blogurl, 'cat='.$this->ID, $glue );
				if( empty($chapter_posts_per_page) )
				{	// Use default from Blog
					$this->get_Blog();
					$chapter_posts_per_page = $this->Blog->get_setting( 'chapter_posts_per_page' );
				}
				if( !empty($chapter_posts_per_page) && $chapter_posts_per_page != $this->Blog->get_setting( 'posts_per_page' ) )
				{	// We want a specific post per page count:
					$r = url_add_param( $r, 'posts='.$chapter_posts_per_page, $glue );
				}
				break;

			case 'subchap':
				$this->get_Blog();
				$category_prefix = $this->Blog->get_setting('category_prefix');
				if( !empty( $category_prefix ) )
				{
					$r = url_add_tail( $blogurl, '/'.$category_prefix.'/'.$this->urlname.'/' );
				}
				else
				{
					$r = url_add_tail( $blogurl, '/'.$this->urlname.'/' );
				}
				break;

			case 'chapters':
			default:
				$this->get_Blog();
				$category_prefix = $this->Blog->get_setting('category_prefix');
				if( !empty( $category_prefix ) )
				{
					$r = url_add_tail( $blogurl, '/'.$category_prefix.'/'.$this->get_url_path() );
				}
				else
				{
					$r = url_add_tail( $blogurl, '/'.$this->get_url_path() );
				}
				break;
		}

		if( $paged > 1 )
		{
			$r = url_add_param( $r, 'paged='.$paged, $glue );
		}

		return $r;
	}

	/**
	 * Get the Blog object for the Chapter.
	 *
	 * @return Blog
	 */
	function & get_Blog()
	{
		if( is_null($this->Blog) )
		{
			$this->load_Blog();
		}

		return $this->Blog;
	}


	/**
	 * Load the Blog object for the Chapter, without returning it.
	 */
	function load_Blog()
	{
		if( is_null($this->Blog) )
		{
			$BlogCache = & get_BlogCache();
			$this->Blog = & $BlogCache->get_by_ID( $this->blog_ID );
		}
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true on success
	 */
	function dbinsert()
	{
		global $DB;

		load_funcs( 'items/model/_item.funcs.php' );

		if( $this->ID != 0 ) die( 'Existing object cannot be inserted!' );

		// Start transaction because of urltitle validation
		$DB->begin( 'SERIALIZABLE' );

		// validate url title / slug
		$this->set( 'urlname', urltitle_validate( $this->urlname, $this->name, $this->ID, false, $this->dbprefix.'urlname', $this->dbIDname, $this->dbtablename) );

		if( parent::dbinsert() )
		{ // The chapter was inserted successful
			$DB->commit();
			return true;
		}

		// Could not insert the chapter object
		$DB->rollback();
		return false;
	}

	/**
	 * Update the DB based on previously recorded changes
	 *
	 * @return boolean true on success
	 */
	function dbupdate()
	{
		global $DB;

		// Start transaction because of urltitle validation
		$DB->begin( 'SERIALIZABLE' );

		// validate url title / slug
		if( empty($this->urlname) || isset($this->dbchanges['cat_urlname']) )
		{ // Url title has changed or is empty
			$this->set( 'urlname', urltitle_validate( $this->urlname, $this->name, $this->ID, false, $this->dbprefix.'urlname', $this->dbIDname, $this->dbtablename) );
		}

		if( parent::dbupdate() === false )
		{ // The update was unsuccessful
			$DB->rollback();
			return false;
		}

		// The chapter was updated successful
		$DB->commit();
		return true;
	}


	/**
	 * Check if this category has at least one post
	 *
	 * @return boolean 
	 */
	function has_posts()
	{
		global $DB;

		if( $this->ID == 0 )
		{	// New category has no posts
			return false;
		}

		if( !isset( $this->count_posts ) )
		{
			$SQL = new SQL();
			$SQL->SELECT( 'COUNT( postcat_post_ID )' );
			$SQL->FROM( 'T_postcats' );
			$SQL->WHERE( 'postcat_cat_ID = '.$DB->quote( $this->ID ) );
			$count_posts = $DB->get_var( $SQL->get() );
			$this->count_posts = $count_posts;
		}

		return ( $this->count_posts > 0 );
	}


	/**
	 * Get URL to edit a chapter if user has edit rights.
	 *
	 * @param array Params:
	 *  - 'redirect_page': redirect to page: 'front', 'manual', 'list'
	 *  - 'glue' : Glue string between url params
	 * @return string|FALSE URL
	 */
	function get_edit_url( $params = array() )
	{
		$params = array_merge( array(
				'redirect_page' => '',
				'glue'          => '&amp;',
			), $params );

		if( ! is_logged_in( false ) )
		{ // User is not logged in
			return false;
		}

		if( ! $this->ID )
		{ // New chapter
			return false;
		}

		global $current_User;

		if( ! $current_User->check_perm( 'admin', 'restricted' ) ||
		    ! $current_User->check_perm( 'blog_cats', '', false, $this->blog_ID ) )
		{ // User has no right to edit this chapter
			return false;
		}

		global $admin_url;
		$url = $admin_url.'?ctrl=chapters'.$params['glue']
			.'action=edit'.$params['glue']
			.'cat_ID='.$this->ID.$params['glue']
			.'blog='.$this->blog_ID;
		if( !empty( $params['redirect_page'] ) )
		{
			$url .= $params['glue'].'redirect_page='.$params['redirect_page'];
		}

		return $url;
	}


	/**
	 * Provide link to edit a chapter if user has edit rights
	 *
	 * @param array Params:
	 *  - 'before': to display before link
	 *  - 'after':    to display after link
	 *  - 'text': link text
	 *  - 'title': link title
	 *  - 'class': CSS class name
	 *  - 'redirect_page': redirect to page: 'front', 'manual', 'list'
	 *  - 'glue' : Glue string between url params
	 * @return string|FALSE Link tag
	 */
	function get_edit_link( $params = array() )
	{
		$edit_url = $this->get_edit_url( $params );
		if( ! $edit_url )
		{
			return false;
		}

		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'        => '',
				'after'         => '',
				'text'          => '#',
				'title'         => '#',
				'class'         => '',
				'redirect_page' => '',
				'glue'          => '&amp;',
			), $params );


		if( $params['text'] == '#' ) $params['text'] = get_icon( 'edit' ).' '.T_('Edit...');
		if( $params['title'] == '#' ) $params['title'] = T_('Edit this chapter...');

		$r = $params['before'];
		$r .= '<a href="'.$edit_url;
		$r .= '" title="'.$params['title'].'"';
		if( !empty( $params['class'] ) ) $r .= ' class="'.$params['class'].'"';
		$r .=  '>'.$params['text'].'</a>';
		$r .= $params['after'];

		return $r;
	}
}

?>