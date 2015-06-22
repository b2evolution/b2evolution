<?php
/**
 * This file implements the ItemLight class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );


/**
 * ItemLight Class
 *
 * Basically, all we want to achieve here is:
 * - permalinks
 * - last mod dates
 *
 * This object SHOULD NOT be saved.
 *
 * @package evocore
 */
class ItemLight extends DataObject
{
	/**
	 * Publish date ("Y-m-d H:i:s"). This may be in the future.
	 * This should get compared to {@link $localtimenow}.
	 * @var string
	 */
	var $issue_date;

	/**
	 * Last modification date (timestamp)
	 * This should get compared to {@link $localtimenow}.
	 * @var integer
	 */
	var $datemodified;

	var $title;

	var $excerpt;

	var $urltitle;

	var $canonical_slug_ID;

	var $tiny_slug_ID;

	/**
	 * External URL the item links to (if any).
	 * @var string
	 */
	var $url;

	var $ityp_ID;

	/**
	 * ID of the main category.
	 * Use {@link ItemLight::set()} to set it, since other vars get lazily derived from it.
	 * @var integer
	 */
	var $main_cat_ID = 0;
	/**
	 * @var Chapter
	 * @access protected
	 * @see ItemLight::get_main_Chapter()
	 */
	var $main_Chapter;

	/**
	 * Derived from $main_cat_ID.
	 *
	 * @var integer
	 * @access protected
	 * @see ItemLight::get_blog_ID()
	 */
	var $blog_ID;

	/**
	 * The Blog of the Item (lazy filled, use {@link get_Blog()} to access it.
	 * @access protected
	 * @var Blog
	 */
	var $Blog;

	/**
	 * Array of tags (strings)
	 *
	 * Lazy loaded.
	 * @see ItemLight::get_tags()
	 * @access protected
	 * @var array
	 */
	var $tags = NULL;

	/**
	 * Array of dbchanges flag to be able to check modifications, and execute update queries only when required
	 * Note: Only those updates needs to be tracked in this var which are saved in a relational table ( e.g. tags, extracats )
	 * @access protected
	 * @var array
	 */
	var $dbchanges_flags = array();


	/**
	 * Constructor
	 *
	 * @param object table Database row
	 * @param string
	 * @param string
	 * @param string
	 * @param string for derived classes
	 * @param string datetime field name
	 * @param string datetime field name
	 * @param string User ID field name
	 * @param string User ID field name
	 */
	function ItemLight( $db_row = NULL, $dbtable = 'T_items__item', $dbprefix = 'post_', $dbIDname = 'post_ID', $objtype = 'ItemLight',
	               $datecreated_field = '', $datemodified_field = 'datemodified',
	               $creator_field = '', $lasteditor_field = '' )
	{
		global $localtimenow, $default_locale, $current_User;

		// Call parent constructor:
		parent::DataObject( $dbtable, $dbprefix, $dbIDname, $datecreated_field, $datemodified_field,
												$creator_field, $lasteditor_field );

		$this->objtype = $objtype;

		if( $db_row == NULL )
		{ // New item:
			$this->ID = 0;
			$this->set( 'issue_date', date('Y-m-d H:i:s', $localtimenow) );
		}
		else
		{
			$this->ID = $db_row->$dbIDname;
			$this->issue_date = $db_row->post_datestart;			// Publication date of a post/item
			$this->datestart = $db_row->post_datestart;			// This is the same as issue_date, but unfortunatly both of them are used, One of them should be removed
			$this->datemodified = $db_row->post_datemodified;			// Date of last edit of post/item
			$this->main_cat_ID = $db_row->post_main_cat_ID;
			$this->urltitle = $db_row->post_urltitle;
			$this->canonical_slug_ID = $db_row->post_canonical_slug_ID;
			$this->tiny_slug_ID = $db_row->post_tiny_slug_ID;
			$this->title = $db_row->post_title;
			$this->excerpt = $db_row->post_excerpt;
			$this->ityp_ID = $db_row->post_ityp_ID;
			$this->url = $db_row->post_url;
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
				array( 'table'=>'T_items__item', 'fk'=>'post_parent_ID', 'msg'=>T_('%d links to child items'),
					'class'=>'Item', 'class_path'=>'items/model/_item.class.php' ),
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
				array( 'table'=>'T_links', 'fk'=>'link_itm_ID', 'msg'=>T_('%d links to destination items'),
						'class'=>'Link', 'class_path'=>'links/model/_link.class.php' ),
				array( 'table'=>'T_postcats', 'fk'=>'postcat_post_ID', 'msg'=>T_('%d links to extra categories') ),
				array( 'table'=>'T_comments', 'fk'=>'comment_item_ID', 'msg'=>T_('%d comments'),
						'class'=>'Comment', 'class_path'=>'comments/model/_comment.class.php' ),
				array( 'table'=>'T_items__version', 'fk'=>'iver_itm_ID', 'msg'=>T_('%d versions') ),
				array( 'table'=>'T_slug', 'fk'=>'slug_itm_ID', 'msg'=>T_('%d slugs') ),
				array( 'table'=>'T_items__itemtag', 'fk'=>'itag_itm_ID', 'msg'=>T_('%d links to tags') ),
				array( 'table'=>'T_items__item_settings', 'fk'=>'iset_item_ID', 'msg'=>T_('%d items settings') ),
				array( 'table'=>'T_items__subscriptions', 'fk'=>'isub_item_ID', 'msg'=>T_('%d items subscriptions') ),
				array( 'table'=>'T_items__prerendering', 'fk'=>'itpr_itm_ID', 'msg'=>T_('%d prerendered content') ),
				array( 'table'=>'T_users__postreadstatus', 'fk'=>'uprs_post_ID', 'msg'=>T_('%d recordings of a post having been read') ),
			);
	}


	/**
	 * Get this class db table config params
	 *
	 * @return array
	 */
	static function get_class_db_config()
	{
		return array(
			'dbtablename'        => 'T_items__item',
			'dbprefix'           => 'post_',
			'dbIDname'           => 'post_ID',
			'datecreated_field'  => '',
			'datemodified_field' => 'datemodified',
			'creator_field'      => '',
			'lasteditor_field'   => '',
		);
	}


	/**
	 * Is this a Special post (Page, Intros, Sidebar, Advertisement)
	 *
	 * @return boolean
	 */
	function is_special()
	{
		global $posttypes_specialtypes;

		// Check if this post type is between the special post types
		return in_array( $this->ityp_ID, $posttypes_specialtypes );
	}


	/**
	 * Is this an Intro post
	 *
	 * @return boolean
	 */
	function is_intro()
	{
		return ($this->ityp_ID >= 1400 && $this->ityp_ID <= 1600);
	}


	/**
	 * Is this a featured post (any intro post will return false even if it's checked as "featured")
	 *
	 * @return boolean
	 */
	function is_featured()
	{
		return !( empty($this->featured) || $this->is_intro() );
	}


  /**
	 * Generate a single post link for the item
	 *
	 * @param boolean allow redir to permalink, true | false | 'auto' to prevent redit only if single isn't the current permalink type
 	 * @param string base url to use
	 * @param string glue between url params
	 */
	function get_single_url( $allow_redir = true, $blogurl = '', $glue = '&amp;' )
	{
		$this->get_Blog();

		if( empty( $blogurl ) )
		{
			$blogurl = $this->Blog->gen_blogurl();
		}

		$single_links = $this->Blog->get_setting('single_links');

 		if( !empty( $this->urltitle ) && $single_links != 'param_num' )
		{	// We can and we want to use the url title:
			$urlparam = 'title='.$this->urltitle;
			$urltail = $this->urltitle;
		}
		else
		{
			$urlparam = 'p='.$this->ID;
			$urltail = 'p'.$this->ID;
		}

		switch( $single_links )
		{
			case 'param_num':
			case 'param_title':
				$permalink = url_add_param( $blogurl, $urlparam.$glue.'more=1'.$glue.'c=1'.$glue.'tb=1'.$glue.'pb=1', $glue );
				break;

			case 'y':
				$permalink = url_add_tail( $blogurl, mysql2date('/Y/', $this->issue_date).$urltail );
				break;

			case 'ym':
				$permalink = url_add_tail( $blogurl, mysql2date('/Y/m/', $this->issue_date).$urltail );
				break;

			case 'ymd':
				$permalink = url_add_tail( $blogurl, mysql2date('/Y/m/d/', $this->issue_date).$urltail );
				break;

			case 'subchap':
				$main_Chapter = & $this->get_main_Chapter();
				$permalink = url_add_tail( $blogurl, '/'.$main_Chapter->urlname.'/'.$urltail );
				break;

			case 'chapters':
				$main_Chapter = & $this->get_main_Chapter();
				$permalink = url_add_tail( $blogurl, '/'.$main_Chapter->get_url_path().$urltail );
				break;

			case 'short':
			default:
				$permalink = url_add_tail( $blogurl, '/'.$urltail );
				break;
		}

		if( $allow_redir == 'auto' )
		{	// We allow redir only if the permalink is already single.
			// In other words: we implicitely allow redir if there is no need to redir!
			// and more useful: we explicitly prevent redir if we know it would take place.
			$allow_redir = ($this->Blog->get_setting( 'permalinks' ) == 'single');
		}

		if( ! $allow_redir )
		{
			$permalink = url_add_param( $permalink, 'redir=no', $glue );
		}

		return $permalink;
	}


	/**
	 * Generate a link to the post in the archives
	 *
 	 * @param string base url to use
	 * @param string glue between url params
	 */
	function get_archive_url( $blogurl = '', $glue = '&amp;' )
	{
		$this->get_Blog();

		if( empty( $blogurl ) )
		{
			$blogurl = $this->Blog->gen_blogurl();
		}

		$permalink = $this->Blog->get_archive_url( $this->issue_date, $glue );

		return $permalink.'#item_'.$this->ID;
	}


	/**
	 * Generate a link to the post in the category
	 *
 	 * @param string base url to use
	 * @param string glue between url params
	 */
	function get_chapter_url( $blogurl = '', /* TODO: not used.. */ $glue = '&amp;' )
	{
		if( empty( $blogurl ) )
		{
			$this->get_Blog();
			$blogurl = $this->Blog->gen_blogurl();
		}

		$main_Chapter = & $this->get_main_Chapter();
		$permalink = url_add_tail( $blogurl, '/'.$main_Chapter->get_url_path() );

		return $permalink.'#item_'.$this->ID;
	}


	/**
	 * Generate the permalink for the item.
	 *
	 * Note: Each item has an unique permalink at any given time.
	 * Some admin settings may however change the permalinks for previous items.
	 * Note: This actually only returns the URL, to get a real link, use {@link Item::get_permanent_link()}
	 *
	 * @todo archives modes in clean URL mode
	 *
	 * @param string single, archive, subchap
	 * @param string base url to use
	 * @param string glue between url params
	 */
	function get_permanent_url( $permalink_type = '', $blogurl = '', $glue = '&amp;' )
	{
		global $DB, $cacheweekly, $Settings, $posttypes_specialtypes, $posttypes_nopermanentURL, $posttypes_catpermanentURL;

		$this->get_Blog();
		if( $this->Blog->get_setting( 'front_disp' ) == 'page' &&
		    $this->Blog->get_setting( 'front_post_ID' ) == $this->ID )
		{ // This item is used as front specific page on the blog's home
			$permalink_type = 'none';
		}
		elseif( in_array( $this->ityp_ID, $posttypes_specialtypes ) ) // page, intros, sidebar
		{	// This is not an "in stream" post:
			if( in_array( $this->ityp_ID, $posttypes_nopermanentURL ) )
			{	// This type of post is not allowed to have a permalink:
				$permalink_type = 'none';
			}
			elseif( in_array( $this->ityp_ID, $posttypes_catpermanentURL ) )
			{	// This post has a permanent URL as url to main chapter:
				$permalink_type = 'cat';
			}
			else
			{ // allowed to have a permalink:
				// force use of single url:
				$permalink_type = 'single';
			}
		}
		elseif( empty( $permalink_type ) )
		{	// Normal "in stream" post:
			// Use default from collection settings (may be an "in stream" URL):
			$permalink_type = $this->Blog->get_setting( 'permalinks' );
		}

		switch( $permalink_type )
		{
			case 'archive':
				return $this->get_archive_url( $blogurl, $glue );

			case 'subchap':
				return $this->get_chapter_url( $blogurl, $glue );

			case 'none':
				// This is a silent fallback when we try to permalink to an Item that cannot be addressed directly:
				// Link to blog home:
				return $this->Blog->gen_blogurl();

			case 'cat':
				// Link to permanent url of main chapter:
				$this->get_main_Chapter();
				return $this->main_Chapter->get_permanent_url( NULL, $blogurl, 1, NULL, $glue );

			case 'single':
			default:
				return $this->get_single_url( true, $blogurl, $glue );
		}
	}


	/**
	 * Template function: list all the category names
	 *
	 * @param string Output format for each cat, see {@link format_to_output()}
	 */
	function categories( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'          => ' ',
				'after'           => ' ',
				'include_main'    => true,
				'include_other'   => true,
				'include_external'=> true,
				'before_main'     => '',       // string fo display before the MAIN category,
				'after_main'      => '',       // string fo display after the MAIN category
				'before_other'    => '',       // string fo display before OTHER categories
				'after_other'     => '',       // string fo display after OTHER categories
				'before_external' => '<em>',   // string fo display before EXTERNAL categories
				'after_external'  => '</em>',  // string fo display after EXTERNAL categories,
				'separator'       => ', ',
				'link_categories' => true,
				'link_title'      => '#',
				'format'          => 'htmlbody',
				'show_locked'     => false,
			), $params );


		if( $params['link_title'] == '#' )
		{ /* TRANS: When the categories for a specific post are displayed, the user can click
					on these cats to browse them, this is the default href title displayed there */
			$params['link_title'] = T_('Browse category');
		}

		$categoryNames = array();
		foreach( $this->get_Chapters() as $Chapter )
		{
			$cat_name = $Chapter->dget( 'name' );

			if( $params['link_categories'] )
			{ // we want to display links
				$lBlog = & $Chapter->get_Blog();
				$cat_name = '<a href="'.$Chapter->get_permanent_url().'" title="'.htmlspecialchars($params['link_title']).'">'.$cat_name.'</a>';
			}

			if( $Chapter->ID == $this->main_cat_ID )
			{ // We are displaying the main cat!
				if( !$params['include_main'] )
				{ // ignore main cat !!!
					continue;
				}
				$cat_name = $params['before_main'].$cat_name.$params['after_main'];
			}
			elseif( $Chapter->blog_ID == $this->blog_ID )
			{ // We are displaying another cat in the same blog
				if( !$params['include_other'] )
				{ // ignore main cat !!!
					continue;
				}
				$cat_name = $params['before_other'].$cat_name.$params['after_other'];
			}
			else
			{ // We are displaying an external cat (in another blog)
				if( !$params['include_external'] )
				{ // ignore main cat !!!
					continue;
				}
				$cat_name = $params['before_external'].$cat_name.$params['after_external'];
			}

			if( $Chapter->lock && $params[ 'show_locked' ] )
			{
				$cat_name .= '<span style="padding-left:5px;" >'.get_icon( 'file_not_allowed', 'imgtag', array( 'title' => T_('Locked')) ).'</span>';
			}

			$categoryNames[] = $cat_name;
		}

		echo $params['before'];
		echo format_to_output( implode( $params['separator'], $categoryNames ), $params['format'] );
 		echo $params['after'];
	}


	/**
	 * Add nav_target param into the end of the url, but only if it is necessary
	 *
	 * @param string the url
	 * @param string the current blog or current skin post_navigation setting
	 * @param integer the ID of the navigation target
	 * @param string glue
	 * @return string the received url or the received url extended with the navigation param
	 */
	function add_navigation_param( $url, $post_navigation, $nav_target, $glue = '&amp;' )
	{
		if( empty( $url ) || empty( $nav_target ) )
		{ // the url or the navigation target is not set we can't modify anything
			return $url;
		}

		switch( $post_navigation )
		{
			case 'same_category': // navigate through the selected category
				if( $this->main_cat_ID != $nav_target )
				{
					$url = url_add_param( $url, 'cat='.$nav_target, $glue );
				}
				break;

			case 'same_tag': // navigate through the selected tag
				$tags = $this->get_tags();
				if( count( $tags ) > 1 )
				{
					$url = url_add_param( $url, 'tag='.$nav_target, $glue );
				}
				break;

			case 'same_author': // navigate through this item author's posts ( param not needed because a post always has only one author )
			case 'same_blog': // by default don't add any param
			default:
				break;
		}

		return $url;
	}


	/**
	 * Template function: display main category name
	 *
	 * @param string Output format, see {@link format_to_output()}
	 * @param array Params
	 */
	function main_category( $format = 'htmlbody', $params = array() )
	{
		$params = array_merge( array(
				'display_link' => false, // TRUE to display category's name as link
				'link_class'   => ''
			), $params );

		$Chapter = & $this->get_main_Chapter();

		if( $params['display_link'] )
		{	// Display category's name as link

			$link_class = '';
			if( !empty( $params['link_class'] ) )
			{	// Set attribute for class
				$link_class = 'class="'.$params['link_class'].'"';
			}

			echo '<a href="'.$Chapter->get_permanent_url().'"'.$link_class.'>';
		}

		// Display category's name
		$Chapter->disp( 'name', $format );

		if( $params['display_link'] )
		{
			echo '</a>';
		}
	}


	/**
	 * Get list of Chapter objects.
	 *
	 * sam2kb> TODO: Cache item cat IDs into Item::categories property instead of global $cache_postcats
	 *
	 * @return array of {@link Chapter chapters} (references)
	 */
	function get_Chapters()
	{
		global $DB, $preview, $postIDlist, $cache_postcats;

		if( $preview )
		{	// Preview mode
			global $extracats, $post_category;

			$extracats = param( 'post_extracats', 'array:integer', array() );
			if( ( ! empty( $post_category ) ) && ( ! in_array( $post_category, $extracats ) ) )
			{
				$extracats[] = $post_category;
			}
			$categoryIDs = $extracats;
		}
		elseif( empty( $this->ID ) )
		{ // This item doesn't exist yet
			$categoryIDs = NULL;
		}
		else
		{
			$search_post_ids = empty( $postIDlist ) ? NULL : explode( ',', $postIDlist );
			if( empty( $search_post_ids ) || !in_array( $this->ID, $search_post_ids ) )
			{	// Load cats for current item
				$categoryIDs = postcats_get_byID( $this->ID );
			}
			else
			{	// Load cats for items list
				if( ! isset($cache_postcats[$this->ID]) && ! empty( $postIDlist ) )
				{	// Add to cache
					$sql = "SELECT postcat_post_ID, postcat_cat_ID
							FROM T_postcats
							WHERE postcat_post_ID IN ($postIDlist)
							ORDER BY postcat_post_ID, postcat_cat_ID";

					foreach( $DB->get_results( $sql, ARRAY_A, 'Get categories for items' ) as $row )
					{
						$postcat_post_ID = $row['postcat_post_ID'];
						if( ! isset( $cache_postcats[$postcat_post_ID] ) )
						{
							 $cache_postcats[$postcat_post_ID] = array();
						}
						$cache_postcats[$postcat_post_ID][] = $row['postcat_cat_ID'];
					}
				}
				$categoryIDs = $cache_postcats[$this->ID];
			}
		}

		$chapters = array();
		if( ! empty( $categoryIDs ) )
		{
			$ChapterCache = & get_ChapterCache();
			// Load all required Chapters
			$ChapterCache->load_list( $categoryIDs );

			foreach( $categoryIDs as $cat_ID )
			{
				if( $Chapter = & $ChapterCache->get_by_ID( $cat_ID, false ) )
				{
					$chapters[] = $Chapter;
				}
			}
		}

		return $chapters;
	}


	/**
	 * Get the main Chapter.
	 *
	 * @return Chapter
	 */
	function & get_main_Chapter()
	{
		if( is_null( $this->main_Chapter ) )
		{
			$ChapterCache = & get_ChapterCache();
			/**
			 * @var Chapter
			 */
			$this->main_Chapter = & $ChapterCache->get_by_ID( $this->main_cat_ID, false );
			if( empty( $this->main_Chapter ) )
			{	// If main chapter is broken we should get it from one of extra chapters
				$chapters = $this->get_Chapters();
				foreach( $chapters as $Chapter )
				{
					if( !empty( $Chapter ) )
					{	// We have found a valid Chapter...
						$this->main_Chapter = & $Chapter;
						$this->main_cat_ID = $Chapter->ID;
						break;
					}
				}
			}
			if( empty( $this->main_Chapter ) )
			{	// If we still don't have a valid Chapter, display clean error and die().
				global $admin_url, $Blog, $blog;
				if( empty( $Blog ) )
				{
					if( !empty( $blog ) )
					{
						$BlogCache = & get_BlogCache();
						$Blog = & $BlogCache->get_by_ID( $blog, false );
					}
				}

				$url_to_edit_post = $admin_url.'?ctrl=items&amp;action=edit&amp;p='.$this->ID;

				if( !empty( $Blog ) )
				{
					$url_to_edit_post .= '&amp;blog='.$Blog->ID;
					if( is_admin_page() )
					{	// Try to set a main category
						$default_cat_ID = $Blog->get_setting( 'default_cat_ID' );
						if( !empty( $default_cat_ID ) )
						{	// If default category is set
							$this->main_cat_ID = $default_cat_ID;
							$this->main_Chapter = & $ChapterCache->get_by_ID( $this->main_cat_ID, false );
						}
						else
						{	// Set from first chapter of the blog
							$ChapterCache->clear();
							$ChapterCache->load_subset( $Blog->ID );
							if( $Chapter = & $ChapterCache->get_next() )
							{
								$this->main_cat_ID = $Chapter->ID;
								$this->main_Chapter = & $Chapter;
							}
						}
					}
				}

				$message = sprintf( 'Item with ID <a %s>%s</a> has an invalid main category ID %s.', /* Do NOT translate debug messages! */
						'href="'.$url_to_edit_post.'"',
						$this->ID,
						$this->main_cat_ID
					);
				if( empty( $Blog ) )
				{	// No blog defined
					$message .= ' In addition we cannot fallback to the default category because no valid blog ID has been specified.';
				}

				if( empty( $this->main_Chapter ) )
				{	// Main chapter is not defined, because blog doesn't have the default cat ID and even blog doesn't have any categories
					debug_die( $message );
				}
				else
				{	// Main chapter is defined, we can show the page
					global $Messages, $current_User;
					if( is_logged_in() && $current_User->check_perm( 'blogs', 'editall' ) )
					{ // User has permission to all blogs posts and comments, display a message as note in order to allow update it
						$message_type = 'note';
					}
					else
					{ // No permission, display this message as error. Such users cannot update this post.
						$message_type = 'error';
					}
					$Messages->add( $message, $message_type );
				}
			}
		}

		return $this->main_Chapter;
	}


	/**
	 * Get the blog ID of this item (derived from main chapter).
	 * @return integer
	 */
	function get_blog_ID()
	{
		if( is_null($this->blog_ID) )
		{
			$main_Chapter = & $this->get_main_Chapter();
			$this->blog_ID = $main_Chapter->blog_ID;
		}
		return $this->blog_ID;
	}


	/**
	 * returns issue date (datetime) of Item
	 * @param array
	 *   - 'before'
	 *   - 'after'
	 *   - 'date_format': Date format
	 *   - 'use_GMT': Use GMT/UTC date
	 */
	function get_issue_date( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => ' ',
				'after'       => ' ',
				'date_format' => '#',
				'use_GMT'     => false,
			), $params );

		if( $params['date_format'] == '#' )
		{
			$params['date_format'] = locale_datefmt();
		}

		return $params['before'].mysql2date( $params['date_format'], $this->issue_date, $params['use_GMT'] ).$params['after'];
	}


	/**
	 * Template function: display issue date (datetime) of Item
	 * @see get_issue_date()
	 */
	function issue_date( $params = array() )
	{
		echo $this->get_issue_date( $params );
	}


	/**
	 * Template function: display issue time (datetime) of Item
	 * @param array
	 *   - 'time_format': Time format ('#short_time' - to use short time)
	 *   - ... see {@link get_issue_date()}
	 * @see get_issue_date()
	 */
	function issue_time( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'time_format'  => '#',
			), $params );

		if( !isset($params['date_format']) )
		{
			$params['date_format'] = $params['time_format'];
		}

		if( $params['date_format'] == '#' )
		{	// Use default time format of current locale
			$params['date_format'] = locale_timefmt();
		}

		if( $params['date_format'] == '#short_time' )
		{	// Use short time format of current locale
			$params['date_format'] = locale_shorttimefmt();
		}

		echo $this->get_issue_date( $params );
	}


	/**
	 * Template function: display locale for item
	 */
	function lang()
	{
		$this->disp( 'locale', 'raw' );
	}


	/**
	 * Template function: display locale for item
	 */
	function locale()
	{
		$this->disp( 'locale', 'raw' );
	}


	/**
	 * Template tag
	 */
	function locale_flag( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => ' ',
				'after'       => ' ',
				'collection'  => 'h10px',
				'format'      => 'htmlbody',
				'class'       => 'flag',
				'align'       => '',
				'locale'      => 'item', // Possible values: 'item', 'blog', custom locale, empty string for current locale
			), $params );

		if( $params['locale'] == 'blog' )
		{
			$params['locale'] = $this->get_Blog()->locale;
		}
		elseif( $params['locale'] == 'item' )
		{
			$params['locale'] = $this->locale;
		}

		echo $params['before'];
		echo locale_flag( $params['locale'], $params['collection'], $params['class'], $params['align'] );
		echo $params['after'];
	}


	/**
	 * Template function: Temporarily switch to this post's locale or to current blog's locale depending on setting
	 */
	function locale_temp_switch()
	{
		global $Blog;

		if( ! empty( $Blog ) && $Blog->get_setting( 'post_locale_source' ) == 'blog' )
		{ // Use locale what current blog is using now
			return;
		}
		else
		{ // Use locale of this post
			$locale = $this->locale;
		}

		locale_temp_switch( $locale );
	}


	/**
	 * Template function: display language name for item
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function language( $format = 'htmlbody' )
	{
		global $locales;
		$locale = $locales[ $this->locale ];
		echo format_to_output( $locale['name'], $format );
	}

	/**
	 * Get last mod date (datetime) of Item
	 *
	 * @param string date/time format: leave empty to use locale default date format
	 * @param boolean true if you want GMT
	 */
	function get_mod_date( $format = '', $useGM = false )
	{
		if( empty($format) )
		{
			return mysql2date( locale_datefmt(), $this->datemodified, $useGM );
		}

		return mysql2date( $format, $this->datemodified, $useGM );
	}


	/**
	 * Template function: display last mod date (datetime) of Item
	 *
	 * @param string date/time format: leave empty to use locale default date format
	 * @param boolean true if you want GMT
	 */
	function mod_date( $format = '', $useGM = false )
	{
		echo $this->get_mod_date( $format, $useGM );
	}


	/**
	 * Template function: display last mod time (datetime) of Item
	 *
	 * @param string date/time format: leave empty to use locale default time format
	 * @param boolean true if you want GMT
	 */
	function mod_time( $format = '', $useGM = false )
	{
		if( empty($format) )
			echo mysql2date( locale_timefmt(), $this->datemodified, $useGM );
		else
			echo mysql2date( $format, $this->datemodified, $useGM );
	}


	/**
	 * Check if current item has at least one category, which belongs to the given blog
	 *
	 * @param integer the given blog ID
	 * @return boolean true if there is at least one category in the given blog, false otherwise
	 */
	function is_part_of_blog( $blog_ID )
	{
		global $DB;
		$cat_count = $DB->get_var( '
				SELECT count( cat_ID )
				FROM T_categories, T_postcats
				WHERE
					T_categories.cat_ID = T_postcats.postcat_cat_ID
					and T_categories.cat_blog_ID = '.$blog_ID.'
					and T_postcats.postcat_post_ID = '.$this->ID
		);

		// $cat_count>0 means that this item has at least one category that belongs to the target blog.
		return $cat_count > 0;
	}


	/**
	 * Check if cross post navigation should stay in the current blog or not.
	 * Also check that this item has at least one category that belongs to the given blog.
	 * If current blog is the same as item blog then, this function will return false, because no need to check.
	 *
	 * @param string 'auto' value means this call needs to decide to stay in the current blog or not. Every other value will return false!
	 * @param integer the given "current" blog ID (its usually the current blog id)
	 * @return boolean true if we have to stay in the current blog, false otherwise
	 */
	function check_cross_post_nav( $target_blog, $blog_ID )
	{
		global $cross_post_nav_in_same_blog;

		if( $target_blog != 'auto' )
		{ // target_blog is not set to auto, we have to navigate to the item's main cat's blog.
			return false;
		}

		$this->get_Blog();
		if( $this->Blog->ID == $blog_ID )
		{ // item's blog is the same as target blog
			return false;
		}

		if( ! $cross_post_nav_in_same_blog )
		{ // we have to navigate to the item's main cat's blog.
			return false;
		}

		// return true if current item has at least one category, which belongs to the corresponding blog, false otherwise
		return $this->is_part_of_blog( $blog_ID );
	}


	/**
	 * Template function: display permalink for item
	 *
	 * Note: This actually only outputs the URL, to display a real link, use {@link Item::permanent_link()}
	 *
	 * @param string 'post', 'archive#id' or 'archive#title'
	 * @param string url to use
	 */
	function permanent_url( $mode = '', $blogurl='' )
	{
		echo $this->get_permanent_url( $mode, $blogurl );
	}


	/**
	 * Returns a permalink link to the Item
	 *
	 * Note: If you only want the permalink URL, use {@link Item::get_permanent_url()}
	 *
	 * @param string link text or special value: '#', '#icon#', '#text#', '#title#' '... $title$ ...'
	 * @param string link title
	 * @param string class name
	 */
	function get_permanent_link( $text = '#', $title = '#', $class = '', $target_blog = '', $post_navigation = '', $nav_target = NULL )
	{
		global $current_User, $Blog;

		switch( $text )
		{
			case '#':
				$text = get_icon( 'permalink' ).T_('Permalink');
				break;

			case '#icon#':
				$text = get_icon( 'permalink' );
				break;

			case '#text#':
				$text = T_('Permalink');
				break;

			case '#title#':
				$text = format_to_output( $this->title );
				break;
		}

		if( $title == '#' ) $title = T_('Permanent link to full entry');

		$blogurl = '';
		$permalink_type = '';
		if( !empty($Blog) && $this->check_cross_post_nav( $target_blog, $Blog->ID ) )
		{
			$permalink_type = $Blog->get_setting( 'permalinks' );
			$blogurl = $Blog->gen_blogurl();
		}

		$url = $this->get_permanent_url( $permalink_type, $blogurl );
		// add navigation param if necessary
		$url = $this->add_navigation_param( $url, $post_navigation, $nav_target );

		// Display as link
		$r = '<a href="'.$url.'" title="'.$title.'"';
		if( !empty( $class ) ) $r .= ' class="'.$class.'"';
		$r .= '>'.str_replace( '$title$', format_to_output( $this->title ), $text ).'</a>';

		return $r;
	}


	/**
	 * Displays a permalink link to the Item
	 *
	 * Note: If you only want the permalink URL, use {@link Item::permanent_url()}
	 *
	 * @param string link text or special value:
	 * @param string link title
	 * @param string class name
	 */
	function permanent_link( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => '',
				'after'       => '',
				'text'        => '#',	// possible special values: '#', '#icon#', '#text#', '#title#'
				'title'       => '#',
				'class'       => '',
				'target_blog' => '',
			//	'format'      => 'htmlbody',
			), $params );

		$link = $this->get_permanent_link( $params['text'], $params['title'], $params['class'], $params['target_blog'] );

		if( !empty( $link ) )
		{
			echo $params['before'];
			echo $link;
			echo $params['after'];
		}
	}


	/**
	 * Template function: display title for item and link to related URL
	 */
	function title( $params = array() )
	{
		$params = array_merge( array(
				'target_blog'  => 'auto',
			), $params );
		echo $this->get_title($params);
	}


	/**
	 * Get "nice" title of the Item
	 * @return string
	 */
	function get_title( $params = array() )
	{
		global $ReqURL, $Blog, $MainList;

		// Set default post navigation
		$def_post_navigation = empty( $Blog ) ? 'same_blog' : $Blog->get_setting( 'post_navigation' );

		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'          => '',
				'after'           => '',
				'before_title'    => '',
				'after_title'     => '',
				'format'          => 'htmlbody',
				'link_type'       => '#',
				'link_class'      => '#',
				'max_length'      => '',
				'target_blog'     => '',
				'nav_target'      => NULL,
				'post_navigation' => $def_post_navigation,
				'title_field'     => 'title',
			), $params );

		// Set post navigation target
		$nav_target = ( ( $params['nav_target'] === NULL ) && isset($MainList) && !empty( $MainList->nav_target ) ) ? $MainList->nav_target : $params['nav_target'];

		$blogurl = '';
		if( !empty($Blog) && $this->check_cross_post_nav( $params['target_blog'], $Blog->ID ) )
		{
			$blogurl = $Blog->gen_blogurl();
		}

		$title = format_to_output( $this->$params['title_field'], $params['format'] );

		if( $params['max_length'] != '' )
		{	// Crop long title
			$title = strmaxlen( $title, intval($params['max_length']) );
		}

		if( empty( $title ) )
		{
			return;
		}

		if( $params['link_type'] == '#' )
		{	// Use default link type from settings:
			if( $this->is_intro() )
			{	// This is an intro, do not link title by default:
				$params['link_type'] = 'none';
			}
			elseif( is_same_url( $this->get_permanent_url( '', $blogurl, '&' ), $ReqURL ) )
			{	// We are on the single url already:
				$params['link_type'] = 'none';
			}
			else if( $this->ityp_ID == 3000 )
			{	// tblue> This is a sidebar link, link to its "link to" URL by default:
				$params['link_type'] = 'linkto_url';
			}
			else
			{	// This is a normal post: use default link strategy from Blog settings:
				$this->get_Blog();
				$params['link_type'] = $this->Blog->get_setting( 'title_link_type' );
			}
		}

		switch( $params['link_type'] )
		{
			case 'auto':
				$url = ( empty($this->url) ? $this->get_permanent_url() : $this->url );
				break;

			case 'permalink':
				$url = $this->get_permanent_url( '', $blogurl );
				break;

			case 'linkto_url':
				$url = $this->url;
				break;

			case 'admin_view':
				$url = '?ctrl=items&amp;blog='.$this->get_blog_ID().'&amp;p='.$this->ID;
				break;

			case 'none':
			default:
		}

		if( !empty( $url ) )
		{ // url is set, also add navigation param if it is necessary
			$url = $this->add_navigation_param( $url, $params['post_navigation'], $nav_target );
		}

		$link_class = '';
		if( $params['link_class'] != '#' )
		{
			$link_class = ' class="'.$params['link_class'].'"';
		}

		$r = $params['before'];
		$title = $params['before_title'].$title.$params['after_title'];
		if( !empty($url) )
		{
			$r .= '<a href="'.$url.'"'.$link_class.'>'.$title.'</a>';
		}
		else
		{
			$r .= $title;
		}
		$r .= $params['after'];
		return $r;
	}


	/**
	 * Template function: display type of item
	 *
	 * @param string
	 * @param string
	 * @param string Output format, see {@link format_to_output()}
	 */
	function type( $before = '', $after = '', $format = 'htmlbody' )
	{
		$ItemTypeCache = & get_ItemTypeCache();
		$Element = & $ItemTypeCache->get_by_ID( $this->ityp_ID, true, false );
		if( !$Element )
		{ // No status:
			return;
		}

		$type_name = $Element->get('name');

		if( $format == 'raw' )
		{
			$this->disp( $type_name, 'raw' );
		}
		else
		{
			echo $before.format_to_output( $type_name, $format ).$after;
		}
	}


	/**
	 * Template function: get excerpt
	 *
	 * @todo do we want excerpts in itemLight or not?
	 *       dh> I'd say "no". I have added excerpt_autogenerated
	 *           only to Item now. But makes sense in the same class.
	 *           update_excerpt is also on in Item.
	 *  fp> the issue is about display only. of course we don't want update code in ItemLight.
	 *  The question is typically about being able to display excerpts in ItemLight list
	 *  sitemaps, feed, recent posts, post widgets where the exceprt might be used as a title, etc.
	 *
	 * @param string filename to use to display more
	 * @return string
	 */
	function get_excerpt( $format = 'htmlbody' )
	{
		// Character conversions
		return format_to_output( $this->excerpt, $format );
	}


	/**
	 * Set param value
	 *
	 * By default, all values will be considered strings
	 *
	 * @todo extra_cat_IDs recording
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 * @param boolean true to set to NULL if empty value
	 * @return boolean true, if a value has been set; false if it has not changed
	 */
	function set( $parname, $parvalue, $make_null = false )
	{
		switch( $parname )
		{
			case 'main_cat_ID':
				$r = $this->set_param( 'main_cat_ID', 'number', $parvalue, false );
				// re-set the extracat ids to make sure main cat is in extracat list and there are no duplicates
				$this->set( 'extra_cat_IDs', $this->extra_cat_IDs, false );
				// Invalidate derived property:
				$this->blog_ID = NULL;
				unset($this->main_Chapter); // dereference
				$this->main_Chapter = NULL;
				unset($this->Blog);
				$this->Blog = NULL;
				return $r;

			case 'extra_cat_IDs':
				// ARRAY! We do not record this change (yet)
				$this->extra_cat_IDs = $parvalue;
				// make sure main cat is in extracat list and there are no duplicates
				$this->extra_cat_IDs[] = $this->main_cat_ID;
				$this->extra_cat_IDs = array_unique( $this->extra_cat_IDs );
				// Mark that extracats has been updated
				$this->dbchanges_flags['extra_cat_IDs'] = true;
				return true; // always return true, since we don't know what was the previous value

			case 'issue_date':
			case 'datestart':
				// Remove seconds from issue date and start date
				// fp> TODO: this should only be done if the date is in the future. If it's in the past there are no sideeffects to having seconds.
				// asimo> Why do we have two parameter with the same content if only one is stored in the database?
				// Also it doesn't make sense to remove seconds from a db date field because the database format has seconds anyway.
				// If we remove seconds from datstart field, then datestart will be always inserted into the dbchagnes even if it was not changed.
				// If we don't want seconds in the end of the datestart then we need to remove it in the itemlight constructor as well.
				// asimo> We have to set seconds to '00' and not remove them, this way the posts can appear right after creating, and the format is OK as well.
				$parvalue_empty_seconds = remove_seconds(strtotime($parvalue), 'Y-m-d H:i:s');
				$this->issue_date = $parvalue_empty_seconds;
				return $this->set_param( 'datestart', 'date', $parvalue_empty_seconds, false );

			case 'ityp_ID':
			case 'canonical_slug_ID':
			case 'tiny_slug_ID':
			case 'dateset':
			case 'excerpt_autogenerated':
				return $this->set_param( $parname, 'number', $parvalue, true );

			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Get the Blog object for the Item.
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
	 * Load the Blog object for the Item, without returning it.
	 *
	 * This is needed for {@link Results} object callbacks.
	 */
	function load_Blog()
	{
		if( is_null($this->Blog) )
		{
			$BlogCache = & get_BlogCache();
			$this->Blog = & $BlogCache->get_by_ID( $this->get_blog_ID() );
		}
	}


	/**
	 * Get array of tags.
	 *
	 * Load from DB if necessary, prefetching any other tags from MainList/ItemList.
	 *
	 * @return array
	 */
	function & get_tags()
	{
		global $DB;

		if( ! isset( $this->tags ) )
		{
			$ItemTagsCache = & get_ItemTagsCache();
			if( ! isset($ItemTagsCache[$this->ID]) )
			{
				/* Only try to fetch tags for items that are not yet in
				 * the cache. This will always give at least the ID of
				 * this Item.
				 */
				$prefetch_item_IDs = array_diff( $this->get_prefetch_itemlist_IDs(), array_keys( $ItemTagsCache ) );
				// Assume these items don't have any tags:
				foreach( $prefetch_item_IDs as $item_ID )
				{
					$ItemTagsCache[$item_ID] = array();
				}

				// Now fetch the tags:
				foreach( $DB->get_results('
					SELECT itag_itm_ID, tag_name
						FROM T_items__itemtag INNER JOIN T_items__tag ON itag_tag_ID = tag_ID
					 WHERE itag_itm_ID IN ('.$DB->quote($prefetch_item_IDs).')
					 ORDER BY tag_name', OBJECT, 'Get tags for items' ) as $row )
				{
					$ItemTagsCache[$row->itag_itm_ID][] = $row->tag_name;
				}

				//pre_dump( $ItemTagsCache );
			}

			$this->tags = $ItemTagsCache[$this->ID];
		}

		return $this->tags;
	}


	/**
	 * Get a list of item IDs from $MainList and $ItemList, if they are loaded.
	 * This is used for prefetching item related data for the whole list(s).
	 * This will at least return the item's ID itself.
	 * @return array
	 */
	function get_prefetch_itemlist_IDs()
	{
		global $MainList, $ItemList;

		// Add the current ID to the list to prefetch, if it's not in the MainList/ItemList (e.g. featured item).
		$r = array($this->ID);

		if( $MainList )
		{
			$r = array_merge($r, $MainList->get_page_ID_array());
		}
		if( $ItemList )
		{
			$r = array_merge($r, $ItemList->get_page_ID_array());
		}

		return array_unique( $r );
	}


	/**
	 * Get a link to history of Item
	 *
	 * @return string A link to history
	 */
	function get_history_link( $params = array() )
	{
		$params = array_merge( array(
				'before'    => '',
				'after'     => '',
				'link_text' => '$icon$' // Use a mask $icon$ or some other text
			), $params );

		if( ( $history_url = $this->get_history_url() ) === false )
		{ // No url available for current user, Don't display a link
			return;
		}

		// Replace all masks with values
		$link_text = str_replace( '$icon$', $this->history_info_icon(), $params['link_text'] );

		return $params['before']
			.'<a href="'.$history_url.'">'.$link_text.'</a>'
			.$params['after'];
	}


	/**
	 * Get URL to history of Item
	 *
	 * @param string Glue between url params
	 * @return string|boolean URL to history OR False when user cannot see a history
	 */
	function get_history_url( $glue = '&amp;' )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in() || ! $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $this ) )
		{ // Current user cannot see a history
			return false;
		}

		return $admin_url.'?ctrl=items'.$glue.'action=history'.$glue.'p='.$this->ID;
	}
}

?>
