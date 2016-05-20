<?php
/**
 * This file implements the Chapter class.
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


/**
 * Chapter Class
 *
 * @package evocore
 */
class Chapter extends DataObject
{
	/**
	 * Name of Chapter
	 *
	 * @var string
	 * @access protected
	 */
	var $name;

	var $parent_ID;
	/**
	 * To display parent name in form
	 */
	var $parent_name;

	/**
	 * Category children list
	 */
	var $children = array();

	var $children_sorted = false;

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
	 * Date when items or comments were added/edited/deleted for this Chapter last time (timestamp)
	 * @see Chapter::update_last_touched_date()
	 * @var integer
	 */
	var $last_touched_ts;

	/**
	 * The sub categories sort order.
	 *
	 * Possible values:
	 *   'parent' - same as in case of parent,
	 *   'alpha' - sort alphabetically,
	 *   'manual' - sort manually
	 *
	 * @var string
	 */
	var $subcat_ordering;

	/**
	 * Constructor
	 *
	 * @param table Database row
 	 * @param integer|NULL subset to use for new object
	 */
	function __construct( $db_row = NULL, $subset_ID = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_categories', 'cat_', 'cat_ID' );

		if( is_null( $db_row ) )
		{	// We are creating an object here:
			$this->set( 'blog_ID', $subset_ID );
		}
		else
		{	// Wa are loading an object:
			$this->ID = $db_row->cat_ID;
			$this->name = $db_row->cat_name;
			$this->parent_ID = $db_row->cat_parent_ID;
			$this->blog_ID = $db_row->cat_blog_ID;
			$this->urlname = $db_row->cat_urlname;
			$this->description = $db_row->cat_description;
			$this->order = $db_row->cat_order;
			$this->subcat_ordering = $db_row->cat_subcat_ordering;
			$this->meta = $db_row->cat_meta;
			$this->lock = $db_row->cat_lock;
			$this->last_touched_ts = $db_row->cat_last_touched_ts;		// When Chapter received last visible change (edit, item, comment, etc.)
		}
	}


	/**
	 * Get this class db table config params
	 *
	 * @return array
	 */
	static function get_class_db_config()
	{
		static $chapter_db_config;

		if( !isset( $chapter_db_config ) )
		{
			$chapter_db_config = array_merge( parent::get_class_db_config(),
				array(
					'dbtablename'        => 'T_categories',
					'dbprefix'           => 'cat_',
					'dbIDname'           => 'cat_ID',
				)
			);
		}

		return $chapter_db_config;
	}


	/**
	 * Get delete restriction settings
	 *
	 * @return array
	 */
	static function get_delete_restrictions()
	{
		return array(
				array( 'table'=>'T_categories', 'fk'=>'cat_parent_ID', 'msg'=>T_('%d sub categories'),
					'class'=>'Chapter', 'class_path'=>'chapters/model/_chapter.class.php' ),
				array( 'table'=>'T_items__item', 'fk'=>'post_main_cat_ID', 'msg'=>T_('%d posts within category through main cat'),
					'class'=>'Item', 'class_path'=>'items/model/_item.class.php' ),
				array( 'table'=>'T_postcats', 'fk'=>'postcat_cat_ID', 'msg'=>T_('%d posts within category through extra cat') ),
			);
	}


	/**
	 * Compare two Chapters based on the parent/blog sort category setting
	 *
	 * @param Chapter A
	 * @param Chapter B
	 * @return number -1 if A < B, 1 if A > B, 0 if A == B
	 */
	static function compare_chapters( $a_Chapter, $b_Chapter )
	{
		if( $a_Chapter == NULL || $b_Chapter == NULL ) {
			debug_die('Invalid category objects received to compare.');
		}

		if( $a_Chapter->ID == $b_Chapter->ID )
		{ // The two chapters are the same
			return 0;
		}

		if( $a_Chapter->blog_ID != $b_Chapter->blog_ID )
		{ // Sort based on the ordering between different blogs
			$a_Chapter->load_Blog();
			$b_Chapter->load_Blog();
			return Blog::compare_blogs($a_Chapter->Blog, $b_Chapter->Blog);
		}

		$ChapterCache= & get_ChapterCache();
		if( $a_Chapter->parent_ID != $b_Chapter->parent_ID )
		{ // Two chapters from the same blog, but different parrents
			// Compare those parents of these chapters which have a common parent Chapter or they are both root Chapters.
			$path_to_root_a = array_reverse( $ChapterCache->get_chapter_path( $a_Chapter->blog_ID, $a_Chapter->ID ) );
			$path_to_root_b = array_reverse( $ChapterCache->get_chapter_path( $b_Chapter->blog_ID, $b_Chapter->ID ) );
			$index = 0;
			while( isset( $path_to_root_a[$index] ) && isset( $path_to_root_b[$index] ) )
			{
				if( $path_to_root_a[$index] != $path_to_root_b[$index] )
				{ // The first different parent on the same level was found, compare parent objects
					$parent_a_Chapter = $ChapterCache->get_by_ID( $path_to_root_a[$index] );
					$parent_b_Chapter = $ChapterCache->get_by_ID( $path_to_root_b[$index] );
					return self::compare_chapters( $parent_a_Chapter, $parent_b_Chapter );
				}
				$index++;
			}

			// One of the chapters is a parent of the other, the parent is considered greater than the other
			return isset( $path_to_root_a[$index] ) ? 1 : -1;
		}

		if( empty( $a_Chapter->parent_ID ) )
		{
			$a_Chapter->load_Blog();
			$cat_order = $a_Chapter->Blog->get_setting('category_ordering');
		}
		else
		{
			$parent_Chapter = $ChapterCache->get_by_ID( $a_Chapter->parent_ID );
			$cat_order = $parent_Chapter->get_subcat_ordering();
		}

		switch( $cat_order )
		{
			case 'alpha':
				$result = strcmp( $a_Chapter->name, $b_Chapter->name );
				break;

			case 'manual':
				if( $a_Chapter->order === NULL )
				{ // NULL values are greater than any number
					$result = ( $b_Chapter->order !== NULL ) ? 1 : 0;
					break;
				}

				if( $b_Chapter->order === NULL )
				{ // NULL values are greater than any number, so a is lower than b
					$result = -1;
					break;
				}

				$result = ( $a_Chapter->order > $b_Chapter->order ) ? 1 : ( ( $a_Chapter->order < $b_Chapter->order ) ? -1 : 0 );
				break;

			default:
				debug_die("Invalid category ordering value!");
		}

		if( $result == 0 )
		{ // In case if the order fields are equal order by ID
			$result = $a_Chapter->ID > $b_Chapter->ID ? 1 : -1;
		}

		return $result;
	}


	/**
	 * Sort chapter childer
	 */
	function sort_children()
	{
		if( $this->children_sorted )
		{ // Category children list is already sorted
			return;
		}

		// Sort children list
		uasort( $this->children, array( 'Chapter','compare_chapters' ) );
	}


	/**
	 * Get children/sub-chapters of this category
	 *
	 * @param boolean set to true to sort children, leave false otherwise
	 * @return array of Chapter - children of this Chapter
	 */
	function get_children( $sorted = false )
	{
		if( $sorted )
		{ // sort childrens if required
			$this->sort_children();
		}

		return $this->children;
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_request()
	{
		param_string_not_empty( 'cat_name', T_('Please enter a name.') );
		$this->set_from_Request( 'name' );

		if( param( 'cat_parent_ID', 'integer', -1 ) !== -1 )
		{	// Set parent ID:
			$this->set_from_Request( 'parent_ID' );
		}

		// Check url name
		param( 'cat_urlname', 'string' );
		$this->set_from_Request( 'urlname' );

		// Check description
		param( 'cat_description', 'string' );
		$this->set_from_Request( 'description' );

		$cat_Blog = & $this->get_Blog();
		if( $cat_Blog && $cat_Blog->get_setting('category_ordering') == 'manual' )
		{ // Manual ordering
			param( 'cat_order', 'integer', NULL );
			$this->set_from_Request( 'order', 'cat_order', true );
		}

		// Sort sub-categories
		param( 'cat_subcat_ordering', 'string' );
		$this->set_from_Request( 'subcat_ordering' );

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
	 * Get sub-category ordering
	 *
	 * @param boolean actual ordering - set to false to get raw setting value with default fallback to 'parent'
	 * @return string
	 *   - the setting value if actual_ordering param is false
	 *   - the actual ordering value computed recursively from parents/blog if the actual_ordering param is true
	 */
	function get_subcat_ordering( $actual_ordering = true )
	{
		$setting_value = empty( $this->subcat_ordering ) ? 'parent' : $this->subcat_ordering;
		if( ! $actual_ordering )
		{
			return $setting_value;
		}

		switch( $setting_value ) {
			case 'alpha':
			case 'manual':
				return $this->subcat_ordering;

			case 'parent':
				return $this->get_parent_subcat_ordering();

			default:
				debug_die('Unhandled sub-category ordering value was detected');
		}
	}


	/**
	 * Get blog's category ordering value in case of root categories, parent Chapter subcat ordering otherwise
	 *
	 * @return string parent subcat ordering
	 */
	function get_parent_subcat_ordering()
	{
		if( empty( $this->parent_ID ) )
		{ // Return the default blog setting
			$this->load_Blog();
			return $this->Blog->get_setting( 'category_ordering' );
		}

		$this->get_parent_Chapter();
		return $this->parent_Chapter->get_subcat_ordering();
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true on success
	 */
	function dbinsert()
	{
		global $DB, $localtimenow;

		load_funcs( 'items/model/_item.funcs.php' );

		if( $this->ID != 0 ) die( 'Existing object cannot be inserted!' );

		// Start transaction because of urltitle validation
		$DB->begin( 'SERIALIZABLE' );

		// validate url title / slug
		$this->set( 'urlname', urltitle_validate( $this->urlname, $this->name, $this->ID, false, $this->dbprefix.'urlname', $this->dbIDname, $this->dbtablename) );

		$this->set_param( 'last_touched_ts', 'date', date( 'Y-m-d H:i:s', $localtimenow ) );

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

		if( count( $this->dbchanges ) > 0 && !isset( $this->dbchanges['last_touched_ts'] ) )
		{ // Update last_touched_ts field only if it wasn't updated yet and the datemodified will be updated for sure.
			global $localtimenow;
			$this->set_param( 'last_touched_ts', 'date', date( 'Y-m-d H:i:s', $localtimenow ) );
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
	 * Get a member param by its name
	 *
	 * @param mixed Name of parameter
	 * @return mixed Value of parameter
	 */
	function get( $parname )
	{
		switch( $parname )
		{
			case 'subcat_ordering':
				return $this->get_subcat_ordering( false );
		}

		return parent::get( $parname );
	}


	/**
	 * Get name of Chapter
	 *
	 * @param string Output format, see {@link format_to_output()}
	 * @return string
	 */
	function get_name( $format = 'htmlbody' )
	{
		return $this->dget( 'name', $format );
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
	 * Update field last_touched_ts
	 */
	function update_last_touched_date()
	{
		global $localtimenow;

		$this->set_param( 'last_touched_ts', 'date', date( 'Y-m-d H:i:s', $localtimenow ) );
		$this->dbupdate();
	}


	/**
	 * Get last touched date (datetime) of Chapter
	 *
	 * @param string date/time format: leave empty to use locale default date format
	 * @param boolean true if you want GMT
	 */
	function get_last_touched_date( $format = '', $useGM = false )
	{
		if( empty( $format ) )
		{
			return mysql2date( locale_datefmt(), $this->last_touched_ts, $useGM );
		}

		return mysql2date( $format, $this->last_touched_ts, $useGM );
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


	/**
	 * Get name of this chapter as path of all parent chapters
	 *
	 * @param array Params
	 * @return string
	 */
	function get_path_name( $params = array() )
	{
		$params = array_merge( array(
				'separator' => ' / ',
			) );

		$path_name = $this->get_name();

		$parent_Chapter = $this->get_parent_Chapter();

		while( $parent_Chapter )
		{	// Append all parent chapters name:
			$path_name = $parent_Chapter->get_name().$params['separator'].$path_name;

			// Get next parent Chapter:
			$parent_Chapter = $parent_Chapter->get_parent_Chapter();
		}

		return $path_name;
	}


	/**
	 * Set param value
	 *
	 * By default, all values will be considered strings
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
 			case 'parent_ID':
				return $this->set_param( $parname, 'string', $parvalue, true );

			case 'name':
			case 'urlname':
			case 'description':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Add a child
	 *
	 * @param object Chapter
	 */
	function add_child_category( & $Chapter )
	{
		if( !isset( $this->children[ $Chapter->ID ] ) )
		{	// Add only if it was not added yet:
			$this->children[ $Chapter->ID ] = & $Chapter;
		}
	}
}

?>