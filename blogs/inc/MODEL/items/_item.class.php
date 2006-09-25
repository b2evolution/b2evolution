<?php
/**
 * This file implements the Item class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author gorgeb: Bertrand GORGE / EPISTEMA
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../dataobjects/_dataobject.class.php';

global $object_def;
/**
 * Object definition:
 */
$object_def['Item'] = array( // definition of the object:
			'db_cols' => array(	// maps properties to colums:
					'ID'              => 'ID',
					'creator_user_ID' => 'post_creator_user_ID',
					'lastedit_user_ID'=> 'post_lastedit_user_ID',
					'assigned_user_ID'=> 'post_assigned_user_ID',
					'datecreated'     => 'post_datecreated',
					'deadline'        => 'post_datedeadline',
					'datestart'       => 'post_datestart',
					'datemodified'    => 'post_datemodified',
					'status'          => 'post_status',
					'content'         => 'post_content',
					'title'           => 'post_title',
					'main_cat_ID'     => 'post_main_cat_ID',
					'locale'          => 'post_locale',
					'urltitle'        => 'post_urltitle',
					'url'             => 'post_url',
					'notifications_status' => 'post_notifications_status',
					'notifications_ctsk_ID' => 'post_notifications_ctsk_ID',
					'wordcount'       => 'post_wordcount',
					'comment_status'  => 'post_comment_status',
					'views'           => 'post_views',
					'renderers'       => 'post_renderers',
					'st_ID'           => 'post_pst_ID',
					'typ_ID'          => 'post_ptyp_ID',
					'priority'        => 'post_priority'
				),
			'allow_null' => array( // specifies column nullability:
					'assigned_user_ID'=> true,
					'st_ID'           => true,
					'typ_ID'          => true,
				),
		);


/**
 * Item Class
 *
 * @package evocore
 */
class Item extends DataObject
{
	/**
	 * The User who has created the Item (lazy-filled).
	 * @see Item::get_creator_User()
	 * @see Item::set_creator_User()
	 * @var User
	 * @access protected
	 */
	var $creator_User;


	/**
	 * @deprecated
	 * @var User
	 */
	var $Author;


	/**
	 * @var integer ID of the user that created the item
	 */
	var $creator_user_ID;


	/**
	 * The assigned User to the item.
	 * @see Item::get_assigned_User()
	 * @see Item::assign_to()
	 * @var User|NULL
	 * @access protected
	 */
	var $assigned_User;

	/**
	 * @var integer|NULL ID of the user that created the item
	 */
	var $assigned_user_ID;

	/**
	 * @var string Publish date ("Y-m-d H:i:s"). This may be in the future.
	 */
	var $issue_date;
	var $mod_date;
	/**
	 * The visibility status of the item.
	 * @var string 'published', 'deprecated', 'protected', 'private' or 'draft'
	 */
	var $status;
	/**
	 * Locale code for the Item content.
	 *
	 * Examples: en-US, zh-CN-utf-8
	 *
	 * @var string
	 */
	var $locale;
	var $title;
	var $urltitle;

	var $content;

	/**
	 * Lazy filled, use split_page()
	 */
	var $content_pages = NULL;


	var $wordcount = 0;
	/**
	 * @var string The list of renderers, imploded by '.'.
	 */
	var $renderers;
	/**
	 * Comments status
	 * @var string "open", "disabled" or "closed
	 */
	var $comment_status;
	/**
	 * External URL the item links to (if any).
	 * @var string
	 */
	var $url;          // fp> we may want to move this to the item links which allows multiple and diffrent types of links/urls/files etc.
	var $typ_ID;
	var $st_ID;
	var $deadline = '';
	var $priority;

	/**
	 * Have post processing notifications been handled?
	 * @var string
	 */
	var $notifications_status;
	/**
	 * Which cron task is responsible for handling notifications?
	 * @var integer
	 */
	var $notifications_ctsk_ID;

	/**
	 * @var integer
	 */
	var $main_cat_ID = 0;
	/**
	 * @var Chapter
	 */
	var $main_Chapter;

	/**
	 * Derived from $main_cat_ID
	 *
	 * @var integer
	 */
	var $blog_ID;
	/**
	 * The Blog of the Item (lazy filled, use {@link get_Blog()} to access it.
	 * @access protected
	 * @var Blog
	 */
	var $Blog;

	/**
	 * @var NULL|array of IDs or NULL if we don't know...
	 */
	var $extra_cat_IDs = NULL;

	/**
	 * Array of Links attached to this item.
	 *
	 * NULL when not initialized.
	 *
	 * @var array
	 * @access public
	 */
	var $Links = NULL;


	var $priorities;

	/**
	 * Pre-rendered content, cached by format/validated renderers.
	 * @see Item::get_prerendered_content()
	 * @access protected
	 * @var array|NULL
	 */
	var $content_prerendered;


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
	function Item( $db_row = NULL, $dbtable = 'T_posts', $dbprefix = 'post_', $dbIDname = 'post_ID', $objtype = 'Item',
	               $datecreated_field = 'datecreated', $datemodified_field = 'datemodified',
	               $creator_field = 'creator_user_ID', $lasteditor_field = 'lastedit_user_ID' )
	{
		global $object_def, $localtimenow, $default_locale, $current_User;

		$this->priorities = array(
				1 => /* TRANS: Priority name */ T_('1 - Highest'),
				2 => /* TRANS: Priority name */ T_('2 - High'),
				3 => /* TRANS: Priority name */ T_('3 - Medium'),
				4 => /* TRANS: Priority name */ T_('4 - Low'),
				5 => /* TRANS: Priority name */ T_('5 - Lowest'),
			);

		// Dereference db cols definition for this object:
		$db_cols =  & $object_def[$objtype]['db_cols'];

		// Call parent constructor:
		parent::DataObject( $dbtable, $dbprefix, $dbIDname, $datecreated_field, $datemodified_field,
												$creator_field, $lasteditor_field );

		$this->delete_restrictions = array(
				array( 'table'=>'T_links', 'fk'=>'link_dest_itm_ID', 'msg'=>T_('%d links to source items') ),
				array( 'table'=>'T_posts', 'fk'=>'post_parent_ID', 'msg'=>T_('%d links to child items') ),
			);

		$this->delete_cascades = array(
				array( 'table'=>'T_links', 'fk'=>'link_itm_ID', 'msg'=>T_('%d links to destination items') ),
				array( 'table'=>'T_postcats', 'fk'=>'postcat_post_ID', 'msg'=>T_('%d links to extra categories') ),
				array( 'table'=>'T_comments', 'fk'=>'comment_post_ID', 'msg'=>T_('%d comments') ),
			);

		$this->objtype = $objtype;

		if( $db_row == NULL )
		{ // New item:
			$this->ID = 0;
			if( isset($current_User) )
			{ // use current user as default, if available (which won't be the case during install)
				$this->set_creator_User( $current_User );
			}
			$this->set( 'issue_date', date('Y-m-d H:i:s', $localtimenow) );
			$this->set( 'notifications_status', 'noreq' );
			// Set the renderer list to 'default' will trigger all 'opt-out' renderers:
			$this->set( 'renderers', array('default') );
			$this->set( 'status', 'published' );
			$this->set( 'locale', $default_locale );
			$this->set( 'priority', 3 );
		}
		else
		{
			$this->ID = $db_row->$dbIDname;
			$this->datecreated = $db_row->$db_cols['datecreated']; // Needed for history display
			$this->datemodified = $db_row->$db_cols['datemodified']; // Needed for history display
			$this->creator_user_ID = $db_row->$db_cols['creator_user_ID']; // Needed for history display
			$this->lastedit_user_ID = $db_row->$db_cols['lastedit_user_ID']; // Needed for history display
			$this->assigned_user_ID = $db_row->$db_cols['assigned_user_ID'];
			$this->issue_date = $db_row->$db_cols['datestart'];
			$this->mod_date = $db_row->$db_cols['datemodified'];
			$this->status = $db_row->$db_cols['status'];
			$this->title = $db_row->$db_cols['title'];
			$this->content = $db_row->$db_cols['content'];
			$this->main_cat_ID = $db_row->$db_cols['main_cat_ID'];
			$this->typ_ID = $db_row->$db_cols['typ_ID'];
			$this->st_ID = $db_row->$db_cols['st_ID'];
			$this->deadline = $db_row->$db_cols['deadline'];
			$this->priority = $db_row->$db_cols['priority'];
			$this->locale = $db_row->$db_cols['locale'];
			$this->urltitle = $db_row->$db_cols['urltitle'];
			$this->wordcount = $db_row->$db_cols['wordcount'];
			$this->notifications_status = $db_row->$db_cols['notifications_status'];
			$this->notifications_ctsk_ID = $db_row->$db_cols['notifications_ctsk_ID'];
			$this->comment_status = $db_row->$db_cols['comment_status'];			// Comments status

			// echo 'renderers=', $db_row->post_renderers;
			$this->renderers = $db_row->$db_cols['renderers'];

			$this->views = $db_row->$db_cols['views'];
			$this->url = $db_row->$db_cols['url'];			// Should move

			// Derived vars
			$ChapterCache = & get_Cache( 'ChapterCache' );
			$this->main_Chapter = & $ChapterCache->get_by_ID( $this->main_cat_ID );

			$this->blog_ID = $this->main_Chapter->blog_ID;
		}
	}


	/**
	 * @todo use extended dbchange instead of set_param...
	 * @todo Normalize to set_assigned_User!?
	 */
	function assign_to( $user_ID, $dbupdate = true /* BLOAT!? */ )
	{
		// echo 'assigning user #'.$user_ID;
		if( ! empty($user_ID) )
		{
			if( $dbupdate )
			{ // Record ID for DB:
				$this->set_param( 'assigned_user_ID', 'number', $user_ID, true );
			}
			else
			{
				$this->assigned_user_ID = $user_ID;
			}
			$UserCache = & get_Cache( 'UserCache' );
			$this->assigned_User = & $UserCache->get_by_ID( $user_ID );
		}
		else
		{
			// fp>> DO NOT set (to null) immediately OR it may KILL the current User object (big problem if it's the Current User)
			unset( $this->assigned_User );
			if( $dbupdate )
			{ // Record ID for DB:
				$this->set_param( 'assigned_user_ID', 'number', NULL, true );
			}
			else
			{
				$this->assigned_User = NULL;
			}
			$this->assigned_user_ID = NULL;
		}

	}


	/**
	 * Template function: display author/creator of item
	 *
	 * @param string String to display before author name
	 * @param string String to display after author name
	 * @param string Output format, see {@link format_to_output()}
	 */
	function author( $before = '', $after = '', $format = 'htmlbody' )
	{
		// Load User
		$this->get_creator_User();

		echo $before;
		echo $this->creator_User->preferred_name( $format, false );
		echo $after;
	}


	/**
	 * Load data from Request form fields.
	 *
	 * This requires the blog (e.g. {@link $blog_ID} or {@link $main_cat_ID} to be set).
	 *
	 * @param boolean true to force edit date (as long as perms permit)
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request( $force_edit_date = false )
	{
		global $default_locale, $allowed_uri_scheme, $Plugins, $current_User;

		if( param( 'post_title', 'html', NULL ) !== NULL ) {
			$this->set( 'title', format_to_post( get_param('post_title'), 0, 0 ) );
		}

		if( param( 'post_locale', 'string', NULL ) !== NULL ) {
			$this->set_from_Request( 'locale' );
		}

		if( param( 'item_typ_ID', 'integer', NULL ) !== NULL ) {
			$this->set_from_Request( 'typ_ID', 'item_typ_ID' );
		}

		if( param( 'post_url', 'string', NULL ) !== NULL ) {
			param_check_url( 'post_url', $allowed_uri_scheme );
			$this->set_from_Request( 'url' );
		}

		if( param( 'content', 'html', '' ) !== NULL ) {
			$this->set( 'content', format_to_post( get_param('content') ) );
		}

		if( ( $force_edit_date || param( 'edit_date', 'integer', 0 ) )
				&& $current_User->check_perm( 'edit_timestamp' ) )
		{ // We can use user date:
			param_date( 'item_issue_date', T_('Please enter a valid issue date.'), $force_edit_date /* required */ );
			if( strlen(get_param('item_issue_date')) )
			{ // only set it, if a date was given:
				param_time( 'item_issue_time' );
				$this->set( 'issue_date', form_date( get_param( 'item_issue_date' ), get_param( 'item_issue_time' ) ) ); // TODO: cleanup...
			}
		}

		if( param( 'post_urltitle', 'string', NULL ) !== NULL ) {
			$this->set_from_Request( 'urltitle' );
		}

		// Workflow stuff:
		if( param( 'item_st_ID', 'integer', NULL ) !== NULL ) {
			$this->set_from_Request( 'st_ID', 'item_st_ID' );
		}

		if( param( 'item_assigned_user_ID', 'integer', NULL ) !== NULL ) {
			$this->assign_to( get_param('item_assigned_user_ID') );
		}

		if( param( 'item_priority', 'integer', NULL ) !== NULL ) {
			$this->set_from_Request( 'priority', 'item_priority', true );
		}

		if( param_date( 'item_deadline', T_('Please enter a valid deadline.'), false, NULL ) !== NULL ) {
			$this->set_from_Request( 'deadline', 'item_deadline', true );
		}

		// Allow comments for this item (only if set to "post_by_post" for the Blog):
		$this->load_Blog();
		if( $this->Blog->allowcomments == 'post_by_post' )
		{
			if( param( 'post_comment_status', 'string', 'open' ) !== NULL )
			{ // 'open' or 'closed' or ...
				$this->set_from_Request( 'comment_status' );
			}
		}

		if( param( 'renderers', 'array', NULL ) !== NULL ) {
			$renderers = $Plugins->validate_list( get_param('renderers') );
			$this->set( 'renderers', $renderers );
		}


		return ! param_errors_detected();
	}


	/**
	 * Generate the permalink for the item.
	 *
	 * Note: Each item has an unique permalink at any given time. Some admin settings may howerver change the permalinks for previous items.
	 * Note: This actually only returns the URL, to get a real link, use {@link Item::get_permanent_link()}
	 *
	 * @todo archives modes in clean mode
	 *
	 * @param string 'urltitle', 'pid', 'archive#id', 'archive#title' or '' to use default setting
	 * @param string url to use
	 * @param boolean true to force single post on destination page
	 * @param string glue between url params
	 */
	function get_permanent_url( $permalink_type = '', $blogurl = '', $force_single = false, $glue = '&amp;' )
	{
		global $DB, $cacheweekly, $Settings;

		if( empty( $permalink_type ) )
		{	// Use default from settings:
			$permalink_type = $Settings->get( 'permalink_type' );
		}

		if( $force_single && (strpos( $permalink_type, 'archive' ) !== false) )
		{ // We have to fall back to pid
			$permalink_type = 'pid';
		}

		if( empty( $blogurl ) )
		{
			$this->get_Blog();
			$blogurl = $this->Blog->gen_blogurl();
		}

		$post_date = $this->issue_date;

		switch( $permalink_type )
		{
			case 'archive#id':
				// Link to an archive page:
				// Determine type of archive page:
				$dest_type = $Settings->get('archive_mode');
				$anchor = $this->ID;
				$urltail = 'p'.$this->ID;
				break;

			case 'archive#title':
				// Link to an archive page:
				// Determine type of archive page:
				$dest_type = $Settings->get('archive_mode');
				$anchor = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $this->title );
				$urltail = 'p'.$this->ID;
				break;

			case 'pid':
				// Link to individual post:
				$dest_type = 'postbypost';
				$urlparam = 'p='.$this->ID;
				$urltail = 'p'.$this->ID;
				break;

			case 'urltitle':
			default:
				// Link to individual post:
				$dest_type = 'postbypost';
				if( !empty( $this->urltitle ) )
				{
					$urlparam = 'title='.$this->urltitle;
					$urltail = $this->urltitle;
				}
				else
				{
					$urlparam = 'p='.$this->ID;
					$urltail = 'p'.$this->ID;
				}
		}


		switch( $dest_type )
		{
			case 'monthly':
				// Link to a monthly archive page:
				if( $Settings->get('links_extrapath') == 'disabled' )
				{ // Use params:
					$permalink = url_add_param( $blogurl, 'm='.substr($post_date,0,4).substr($post_date,5,2), $glue ).'#'.$anchor;
				}
				else
				{ // Use extra path info:
					$permalink = url_add_tail( $blogurl, mysql2date("/Y/m", $post_date) ).'#'.$anchor;
				}
				break;

			case 'weekly':
				// Link to a weekly archive page:
				if((!isset($cacheweekly)) || (empty($cacheweekly[$post_date])))
				{
					$cacheweekly[$post_date] = $DB->get_var( "SELECT WEEK('".$post_date."')" );
				}
				if( $Settings->get('links_extrapath') == 'disabled' )
				{ // Use params:
					$permalink = url_add_param( $blogurl, 'm='.substr($post_date,0,4).$glue.'w='.$cacheweekly[$post_date], $glue ).'#'.$anchor;
				}
				else
				{ // Use extra path info:
					$permalink = url_add_tail( $blogurl, mysql2date("/Y/", $post_date).'w'.$cacheweekly[$post_date] ).'#'.$anchor;
				}
				break;

			case 'daily':
				// Link to a daily archive page:
				if( $Settings->get('links_extrapath') == 'disabled' )
				{ // Use params:
					$permalink = url_add_param( $blogurl, 'm='.substr($post_date,0,4).substr($post_date,5,2).substr($post_date,8,2), $glue ).'#'.$anchor;
				}
				else
				{ // Use extra path info:
					$permalink = url_add_tail( $blogurl, mysql2date("/Y/m/d", $post_date) ).'#'.$anchor;
				}
				break;

			case 'postbypost':
			default:
				// Link to a specific post:
				switch( $Settings->get('links_extrapath') )
				{
					case 'disabled':
						// Use params:
						$permalink = url_add_param( $blogurl, $urlparam.$glue.'more=1'.$glue.'c=1'.$glue.'tb=1'.$glue.'pb=1', $glue );
						break;

					case 'short':
						$permalink = url_add_tail( $blogurl, '/'.$urltail );
						break;

					case 'y':
						$permalink = url_add_tail( $blogurl, mysql2date('/Y/', $post_date).$urltail );
						break;

					case 'ym':
						$permalink = url_add_tail( $blogurl, mysql2date('/Y/m/', $post_date).$urltail );
						break;

					case 'ymd':
						$permalink = url_add_tail( $blogurl, mysql2date('/Y/m/d/', $post_date).$urltail );
						break;

 					case 'subchap':
						$permalink = url_add_tail( $blogurl, '/'.$this->main_Chapter->urlname.'/'.$urltail );
						break;

 					case 'chapters':
						$permalink = url_add_tail( $blogurl, '/'.$this->main_Chapter->get_url_path().$urltail );
						break;

					default:
						debug_die('extra path mode not supported (yet)');
				}
				break;
		}

		return $permalink;
	}


	/**
	 * Template function: display anchor for permalinks to refer to
	 *
	 * @todo archives modes in clean mode
	 *
	 * @param string 'id' or 'title'
	 */
	function anchor( $mode = '' )
	{
		global $Settings;

		if( empty( $mode ) )
			$mode = $Settings->get( 'permalink_type' );

		switch( $mode )
		{
			case 'archive#title': // permalink_type
			case 'title': // explicit choice
				$title = preg_replace( '/[^a-zA-Z0-9_\.-]/', '_', $this->title );
				echo '<a id="'.$title.'"></a>';
				break;

			case 'archive#id': // permalink_type
			case 'id': // explicit choice
				echo '<a id="'.$this->ID.'"></a>';
				break;


			case 'pid': // permalink type where we need no ID
			case 'urltitle': // permalink type where we need no ID
			default:
		}
	}


	/**
	 * Template function: display assignee of item
	 *
	 * @param string
	 * @param string
	 * @param string Output format, see {@link format_to_output()}
	 */
	function assigned_to( $before = '', $after = '', $format = 'htmlbody' )
	{
		if( $this->get_assigned_User() )
		{
			echo $before;
			$this->assigned_User->preferred_name( $format );
			echo $after;
		}
	}


	/**
	 * Get list of assigned user options
	 *
	 * @uses UserCache::get_blog_member_option_list()
	 * @return string HTML select options list
	 */
	function get_assigned_user_options()
	{
		global $object_def;

		$UserCache = & get_Cache( 'UserCache' );
		return $UserCache->get_blog_member_option_list( $this->blog_ID, $this->assigned_user_ID,
							$object_def[$this->objtype]['allow_null']['assigned_user_ID'],
							($this->ID != 0) /* if this Item is already serialized we'll load the default anyway */ );
	}


	/**
	 * Template function: list all the category names
	 *
	 * @param string link title, '#' for default, false if you want no links
	 * @param string string fo display before the MAIN category, 'hide' to ignore main cat
	 * @param string string fo display after the MAIN category, 'hide' to ignore main cat
	 * @param string string fo display before OTHER categories, 'hide' to ignore other cats
	 * @param string string fo display after OTHER categories, 'hide' to ignore other cats
	 * @param string string fo display before EXTERNAL categories, 'hide' to ignore external cats
	 * @param string string fo display after EXTERNAL categories, 'hide' to ignore external cats
	 * @param string separator string
	 * @param string Output format for each cat, see {@link format_to_output()}
	 */
	function categories(
			$link_title = '#',
			$before_main='<strong>', $after_main='</strong>',
			$before_other='', $after_other='',
			$before_external='<em>', $after_external='</em>',
			$separator = ', ',
			$format = 'htmlbody'
		)
	{
		global $cache_postcats;

		if( $link_title == '#' )
		{ /* TRANS: When the categories for a specific post are displayed, the user can click
					on these cats to browse them, this is the default href title displayed there */
			$link_title = T_('Browse category');
		}

		$ChapterCache = & get_Cache( 'ChapterCache' );

		// Load cache for category associations with current posts
		cat_load_postcats_cache();

		if( isset($cache_postcats[$this->ID]) )
		{ // dh> may not be set! (demo logs)
			$categoryIDs = $cache_postcats[$this->ID];
		}
		else $categoryIDs = array();

		$categoryNames = array();
		foreach( $categoryIDs as $cat_ID )
		{
			/**
			 * @var Chapter
			 */
			$Chapter = & $ChapterCache->get_by_ID( $cat_ID );

			$cat_name = $Chapter->dget( 'name' );

			if( !empty($link_title) )
			{ // we want to display links
				$lBlog = & $Chapter->get_Blog();
				$cat_name = '<a href="'.$Chapter->get_permanent_url().'" title="'.$link_title.'">'.$cat_name.'</a>';
			}

			if( $cat_ID == $this->main_cat_ID )
			{ // We are displaying the main cat!
				if( $before_main == 'hide' )
				{ // ignore main cat !!!
					continue;
				}
				$cat_name = $before_main.$cat_name.$after_main;
			}
			elseif( $Chapter->blog_ID == $this->blog_ID )
			{ // We are displaying another cat in the same blog
				if( $before_other == 'hide' )
				{ // ignore main cat !!!
					continue;
				}
				$cat_name = $before_other.$cat_name.$after_other;
			}
			else
			{ // We are displaying an external cat (in another blog)
				if( $before_external == 'hide' )
				{ // ignore main cat !!!
					continue;
				}
				$cat_name = $before_external.$cat_name.$after_external;
			}

			$categoryNames[] = $cat_name;
		}
		echo implode( $separator, $categoryNames );
	}


	/**
	 * Template function: display main category name
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function main_category( $format = 'htmlbody' )
	{
		$ChapterCache = & get_Cache( 'ChapterCache' );
		/**
		 * @var Chapter
		 */
		$Chapter = & $ChapterCache->get_by_ID( $this->main_cat_ID );
		$Chapter->disp( 'name', $format );
	}


	/**
	 * Check if user can see comments on this post, which he cannot if they
	 * are disabled for the Item or never allowed for the blog.
	 *
	 * @return boolean
	 */
	function can_see_comments()
	{
		if( $this->comment_status == 'disabled'
		    || ( $this->get_Blog() && $this->Blog->allowcomments == 'never' ) )
		{ // Comments are disabled on this post
			return false;
		}

		return true; // OK, user can see comments
	}


	/**
	 * Template function: Check if user can leave comment on this post or display error
	 *
	 * @param string|NULL string to display before any error message; NULL to not display anything, but just return boolean
	 * @param string string to display after any error message
	 * @param string error message for non published posts, '#' for default
	 * @param string error message for closed comments posts, '#' for default
	 * @return boolean true if user can post, false if s/he cannot
	 */
	function can_comment( $before_error = '<p><em>', $after_error = '</em></p>', $non_published_msg = '#', $closed_msg = '#' )
	{
		global $Plugins;

		$display = ( ! is_null($before_error) );

		// Ask Plugins (it can say NULL and would get skipped in Plugin::trigger_event_first_return()):
		// Examples:
		//  - A plugin might want to restrict comments on posts older than 20 days.
		//  - A plugin might want to allow comments always for certain users (admin).
		if( $event_return = $Plugins->trigger_event_first_return( 'ItemCanComment', array( 'Item' => $this ) ) )
		{
			$plugin_return_value = $event_return['plugin_return'];
			if( $plugin_return_value === true )
			{
				return true; // OK, user can comment!
			}

			if( $display && is_string($plugin_return_value) )
			{
				echo $before_error;
				echo $plugin_return_value;
				echo $after_error;
			}

			return false;
		}

		if( $this->comment_status == 'disabled'  )
		{ // Comments are disabled on this post
			return false;
		}

		if( $this->comment_status == 'closed'  )
		{ // Comments are closed on this post

			if( $display)
			{
				if( $closed_msg == '#' )
					$closed_msg = T_( 'Comments are closed for this post.' );

				echo $before_error;
				echo $closed_msg;
				echo $after_error;
			}

			return false;
		}

		if( ($this->status == 'draft') || ($this->status == 'deprecated' ) )
		{ // Post is not published

			if( $display )
			{
				if( $non_published_msg == '#' )
					$non_published_msg = T_( 'This post is not published. You cannot leave comments.' );

				echo $before_error;
				echo $non_published_msg;
				echo $after_error;
			}

			return false;
		}

		$this->get_Blog();
		if( $this->Blog->allowcomments == 'never')
		{
			return false;
		}

		return true; // OK, user can comment!
	}


	/**
	 * Get the rendered content. If it has not been generated yet, it will.
	 *
	 * @todo dh> Currently this makes up one query per displayed item. Probably the cache should get pre-fetched by ItemList2?
	 * @todo dh> In general, $content_prerendered gets only queried once per item, so it seems like a memory waste to cache the query result..!
	 *
	 * @param string Format, see {@link format_to_output()}.
	 *        Only "htmlbody", "entityencoded", "xml" and "text" get cached.
	 * @return string
	 */
	function get_prerendered_content( $format )
	{
		global $Plugins;

		$post_renderers = $this->get_renderers_validated();
		$cache_key = $format.'/'.implode('.', $post_renderers);

		if( ! isset( $this->content_prerendered[$cache_key] ) )
		{
			$use_cache = $this->ID && in_array( $format, array( 'htmlbody', 'entityencoded', 'xml', 'text' ) );

			if( $use_cache )
			{ // the format/item can be cached:
				global $DB;

				$cache = $DB->get_var( '
					SELECT itpr_content_prerendered
						FROM T_item__prerendering
					 WHERE itpr_itm_ID = '.$this->ID.'
						 AND itpr_format = "'.$format.'"
					   AND itpr_renderers = "'.implode('.', $post_renderers).'"', 0, 0, 'Check prerendered item content' );

				if( $cache !== NULL ) // may be empty string
				{ // Retrieved from cache:
					$this->content_prerendered[$cache_key] = $cache;
				}
			}

			if( ! isset( $this->content_prerendered[$cache_key] ) )
			{
				global $Debuglog;

				$this->content_prerendered[$cache_key] = $Plugins->render( $this->content, $post_renderers, $format, array( 'Item' => $this ) );

				$Debuglog->add( 'Generated pre-rendered content ['.$cache_key.']', 'items' );

				if( $use_cache )
				{ // save into DB (using REPLACE INTO because it may have been pre-rendered by another thread since the SELECT above)
					$DB->query( '
						REPLACE INTO T_item__prerendering (itpr_itm_ID, itpr_format, itpr_renderers, itpr_content_prerendered)
						 VALUES ( '.$this->ID.', "'.$format.'", '.$DB->quote(implode('.', $post_renderers)).', '.$DB->quote($this->content_prerendered[$cache_key]).' )', 'Cache prerendered item content' );
				}
			}
		}

		return $this->content_prerendered[$cache_key];
	}


	/**
	 * Set the pre-rendered content.
	 *
	 * This is meant to get called by ItemList2, which would do a single query for all
	 * items.
	 *
	 * @param string Pre-rendered content
	 * @param string Cache-Key ($format.'/'.$renderers). See {@link Item::get_prerendered_content()} for the appropriate query skeleton.
	 * @return
	 */
	function set_prerendered_content( $content, $cache_key )
	{
		$this->content_prerendered[$cache_key] = $content;
	}


	/**
	 * Make sure, the pages are split up
	 *
	 * @param string Format, used to retrieve the matching cache; see {@link format_to_output()}
	 */
	function split_pages( $format = 'htmlbody' )
	{
		if( ! isset( $this->content_pages[$format] ) )
		{
			// SPLIT PAGES:
			$this->content_pages[$format] = explode( '<!--nextpage-->', $this->get_prerendered_content($format) );
			$this->pages = count( $this->content_pages[$format] );
		}
	}


	/**
	 * Get a specific page to display
	 *
	 * @param integer Page number
	 * @param string Format, used to retrieve the matching cache; see {@link format_to_output()}
	 */
	function get_content_page( $page, $format = 'htmlbody' )
	{
		// Make sure, the pages are split up:
		$this->split_pages($format);

		if( $page < 1 )
		{
			$page = 1;
		}

		if( $page > $this->pages )
		{
			$page = $this->pages;
		}

		return $this->content_pages[$format][$page-1];
	}


	/**
	 * Template function: display content of item
	 *
	 * Calling this with "MORE" (i-e displaying full content) will increase
	 * the view counter, except on special occasions, see {@link Hit::is_new_view()}.
	 *
	 * WARNING: parameter order is different from deprecated the_content(...)
	 *
	 * @todo fp> Param order and cleanup
	 * @todo dh> Provide get_content() method (and use it here)
	 * @param mixed page number to display specific page, # for url parameter
	 * @param mixed true to display 'more' text (which means "full post"), false not to display, # for url parameter
	 * @param string text to display as the more link
	 * @param string text to display as the more anchor (once the more link has been clicked)
	 * @param string string to display before more link/anchor
	 * @param string string to display after more link/anchor
	 * @param string Output format, see {@link format_to_output()}
	 * @param integer max number of words
	 * @param boolean true if you don't want to repeat teaser after more link was pressed
	 * @param string filename to use to display more
	 */
	function content(
		$disppage = '#',
		$dispmore = '#',
		$more_link_text = '#',
		$more_anchor = '#',
		$before_more = '#',
		$after_more = '#',
		$format = 'htmlbody',
		$cut = 0,
		$stripteaser = false,
		$more_file = ''
		)
	{
		global $Plugins, $Hit, $more, $preview, $current_User, $Debuglog;
		// echo $format,'-',$cut,'-',$dispmore,'-',$disppage;

		if( $more_link_text == '#' )
		{ // TRANS: this is the default text for the extended post "more" link
			$more_link_text = '=> '.T_('Read more!');
		}

		if( $more_anchor == '#' )
		{ // TRANS: this is the default text displayed once the more link has been activated
			$more_anchor = '['.T_('More:').']';
		}

		if( $before_more == '#' )
			$before_more = '<p class="bMore">';

		if( $after_more == '#' )
			$after_more = '</p>';

		if( $dispmore === '#' )
		{ // We want to display more if requested by user:
			$dispmore = $more;
		}

		/*
		 * Check if we want to increment view count, see {@link Hit::is_new_view()}
		 */
		#pre_dump( 'incViews', $dispmore, !$preview, $Hit->is_new_view() );
		if( $dispmore && ! $preview && $Hit->is_new_view() )
		{ // Increment view counter (only if current User is not the item's author)
			$this->inc_viewcount(); // won't increment if current_User == Author
		}

		// Get requested content page:
		if( $disppage === '#' )
		{ // We want to display the page requested by the user:
			global $page;
			$disppage = $page;
		}
		$content_page = $this->get_content_page( $disppage, $format ); // cannot include format_to_output() because of the magic below.. eg '<!--more-->' will get stripped in "xml"


		$content_parts = explode('<!--more-->', $content_page);
		if( count($content_parts)>1 )
		{ // This is an extended post (has a more section):
			if( $dispmore )
			{ // Viewer has already asked for more
				if( $stripteaser || preg_match('/<!--noteaser-->/', $content_page ) )
				{ // We want to strip the teaser:
					$output = '';
				}
				else
				{ // We keep the teaser:
					$output = $content_parts[0];
					if( !empty($more_anchor) ) $output .= $before_more;
					$output .= '<a id="more'.$this->ID.'" name="more'.$this->ID.'"></a>'.$more_anchor;
					if( !empty($more_anchor) ) $output .= $after_more;
				}
				if( count($content_parts) > 2 )
				{ // we have additional <!--more--> tags somewhere
					array_shift($content_parts);
					$output .= implode('', $content_parts);
				}
				else $output .= $content_parts[1];
			}
			else
			{ // We are offering to read more
				$output = $content_parts[0];
				$output .= $before_more .
										'<a href="'.$this->get_permanent_url( 'pid', $more_file ).'#more'.$this->ID.'">'.
										$more_link_text.'</a>' .
										$after_more;
			}
		}
		else
		{ // Regular post
			$output = $content_parts[0];
		}

		// Trigger Display plugins:
		$output = $Plugins->get_trigger_event( 'DisplayItemAllFormats', array(
				'data' => & $output,
				'format' => $format,
				'Item' => & $this,
				'preview' => $preview,
				'dispmore' => $dispmore ) );

		// Character conversions
		$output = format_to_output( $output, $format );

		// TODO: make $cut also work for other formats..
		if( ($format == 'xml') && $cut )
		{ // Let's cut this down...
			$blah = explode(' ', $output);
			if (count($blah) > $cut)
			{
				for ($i=0; $i<$cut; $i++)
				{
					$excerpt .= $blah[$i].' ';
				}
				$output = $excerpt.'...';
			}
		}

		echo $output;
	}


	/**
	 * Template function: display deadline date (datetime) of Item
	 *
	 * @param string date/time format: leave empty to use locale default date format
	 * @param boolean true if you want GMT
	 */
	function deadline_date( $format = '', $useGM = false )
	{
		if( empty($format) )
			echo mysql2date( locale_datefmt(), $this->deadline, $useGM);
		else
			echo mysql2date( $format, $this->deadline, $useGM);
	}


	/**
	 * Template function: display deadline time (datetime) of Item
	 *
	 * @param string date/time format: leave empty to use locale default time format
	 * @param boolean true if you want GMT
	 */
	function deadline_time( $format = '', $useGM = false )
	{
		if( empty($format) )
			echo mysql2date( locale_timefmt(), $this->deadline, $useGM );
		else
			echo mysql2date( $format, $this->deadline, $useGM );
	}


	/**
	 * Get reference to array of Links
	 */
	function & get_Links()
	{
		// Make sure links are loaded:
		$this->load_links();

		return $this->Links;
	}


	/**
	 * returns issue date (datetime) of Item
	 *
	 * @param string date/time format: leave empty to use locale default date format
	 * @param boolean true if you want GMT
	 */
	function get_issue_date( $format = '', $useGM = false )
	{
		if( empty($format) )
			$format = locale_datefmt();

		return mysql2date( $format, $this->issue_date, $useGM);
	}


	/**
	 * Template function: display issue date (datetime) of Item
	 *
	 * @param string date/time format: leave empty to use locale default date format
	 * @param boolean true if you want GMT
	 */
	function issue_date( $format = '', $useGM = false )
	{
		echo $this->get_issue_date( $format, $useGM );
	}


	/**
	 * Template function: display issue time (datetime) of Item
	 *
	 * @param string date/time format: leave empty to use locale default time format
	 * @param boolean true if you want GMT
	 */
	function issue_time( $format = '', $useGM = false )
	{
		if( empty($format) )
			echo mysql2date( locale_timefmt(), $this->issue_date, $useGM );
		else
			echo mysql2date( $format, $this->issue_date, $useGM );
	}


	/**
	 * Template function: display locale for item
	 */
	function lang()
	{
		$this->disp( 'locale', 'raw' );
	}


	/**
	 * Template function: display number of links attached to this Item
	 */
	function linkcount()
	{
		// Make sure links are loaded:
		$this->load_links();

		echo count($this->Links);
	}


	/**
	 * Load links if they were not loaded yet.
	 */
	function load_links()
	{
		if( is_null( $this->Links ) )
		{ // Links have not been loaded yet:
			$LinkCache = & get_Cache( 'LinkCache' );
			$this->Links = & $LinkCache->get_by_item_ID( $this->ID );
		}
	}


	/**
	 * Template function: display locale for item
	 */
	function locale()
	{
		$this->disp( 'locale', 'raw' );
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
	 * Template function: Provide link to message form for this Item's author.
	 *
	 * @param string url of the message form
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @return boolean true, if a link was displayed; false if there's no email address for the Item's author.
	 */
	function msgform_link( $form_url, $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '' )
	{
		$this->get_creator_User();

		if( empty($this->creator_User->email) )
		{ // We have no email for this Author :(
			return false;
		}
		if( empty($this->creator_User->allow_msgform) )
		{
			return false;
		}

		$form_url = url_add_param( $form_url, 'recipient_id='.$this->creator_User->ID.'&amp;post_id='.$this->ID.'&amp;redirect_to='.rawurlencode(regenerate_url()) );

		if( $title == '#' ) $title = T_('Send email to post author');
		if( $text == '#' ) $text = get_icon( 'email', 'imgtag', array( 'class' => 'middle', 'title' => $title ) );

		echo $before;
		echo '<a href="'.$form_url.'" title="'.$title.'"';
		if( !empty( $class ) ) echo ' class="'.$class.'"';
		echo '>'.$text.'</a>';
		echo $after;

		return true;
	}


	/**
	 * Template function: Provide link to message form for this Item's assigned User.
	 *
	 * @param string url of the message form
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @return boolean true, if a link was displayed; false if there's no email address for the assigned User.
	 */
	function msgform_link_assigned( $form_url, $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '' )
	{
		if( ! $this->get_assigned_User() || empty($this->assigned_User->email) )
		{ // We have no email for this Author :(
			return false;
		}

		$form_url = url_add_param( $form_url, 'recipient_id='.$this->assigned_User->ID );
		$form_url = url_add_param( $form_url, 'post_id='.$this->ID );

		if( $title == '#' ) $title = T_('Send email to assigned user');
		if( $text == '#' ) $text = get_icon( 'email', 'imgtag', array( 'class' => 'middle', 'title' => $title ) );

		echo $before;
		echo '<a href="'.$form_url.'" title="'.$title.'"';
		if( !empty( $class ) ) echo ' class="'.$class.'"';
		echo '>'.$text.'</a>';
		echo $after;

		return true;
	}


	/**
	 * Template function: display last mod date (datetime) of Item
	 *
	 * @param string date/time format: leave empty to use locale default date format
	 * @param boolean true if you want GMT
	 */
	function mod_date( $format = '', $useGM = false )
	{
		if( empty($format) )
			echo mysql2date( locale_datefmt(), $this->mod_date, $useGM);
		else
			echo mysql2date( $format, $this->mod_date, $useGM);
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
			echo mysql2date( locale_timefmt(), $this->mod_date, $useGM );
		else
			echo mysql2date( $format, $this->mod_date, $useGM );
	}


	/**
	 *
	 */
	function page_links( $before = '#', $after = '#', $separator = ' ', $single = '', $current_page = '#', $pagelink = '%d', $url = '' )
	{

		// Make sure, the pages are split up:
		$this->split_pages();

		if( $this->pages <= 1 )
		{	// Single page:
			echo $single;
			return;
		}

		if( $before == '#' ) $before = '<p>'.T_('Pages:').' ';
		if( $after == '#' ) $after = '</p>';

		if( $current_page == '#' )
		{
			global $page;
			$current_page = $page;
		}

		if( empty($url) )
		{
			$url = $this->get_permanent_url( '', '', true );
		}

		$page_links = array();

		for( $i = 1; $i <= $this->pages; $i++ )
		{
			$text = str_replace('%d', $i, $pagelink);

			if( $i != $current_page )
			{
				if( $i == 1 )
				{	// First page special:
					$page_links[] = '<a href="'.$url.'">'.$text.'</a>';
				}
				else
				{
					$page_links[] = '<a href="'.url_add_param( $url, 'page='.$i ).'">'.$text.'</a>';
				}
			}
			else
			{
				$page_links[] = $text;
			}
		}

		echo $before;
		echo implode( $separator, $page_links );
		echo $after;
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
	 * @param string link text or special value: '#', '#icon#', '#text#', '#title#'
	 * @param string link title
	 * @param string class name
	 */
	function get_permanent_link( $text = '#', $title = '#', $class = '' )
	{
		global $current_User;

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

		$url = $this->get_permanent_url();

		// Display as link
		$r = '<a href="'.$url.'" title="'.$title.'"';
		if( !empty( $class ) ) $r .= ' class="'.$class.'"';
		$r .= '>'.$text.'</a>';

		return $r;
	}


	/**
	 * Displays a permalink link to the Item
	 *
	 * Note: If you only want the permalink URL, use {@link Item::permanent_url()}
	 *
	 * @param string link text or special value: '#', '#icon#', '#text#', '#title#'
	 * @param string link title
	 * @param string class name
	 */
	function permanent_link( $text = '#', $title = '#', $class = '' )
	{
		echo $this->get_permanent_link( $text, $title, $class );
	}


	/**
	 * Template function: Displays link to feedback page (under some conditions)
	 *
	 * @param string Type of feedback to link to (feedbacks (all)/comments/trackbacks/pingbacks)
	 * @param string String to display before the link (if comments are to be displayed)
	 * @param string String to display after the link (if comments are to be displayed)
	 * @param string Link text to display when there are 0 comments
	 * @param string Link text to display when there is 1 comment
	 * @param string Link text to display when there are >1 comments (include %d for # of comments)
	 * @param string Link title
	 * @param string Status of feedbacks to count
	 * @param boolean true to use a popup windows ('#' to use if comments_popup_windows() is there)
	 * @param boolean true to hide if no feedback ('#' for default)
	 * @param string 'pid' or 'title'; 'none' for NO LINK
	 * @param string url to use
	 */
	function feedback_link( $type = 'feedbacks', $before = '', $after = '',
													$zero = '#', $one = '#', $more = '#', $title='#', $status = 'published',
													$use_popup = '#',	$hideifnone = '#', $mode = '', $blogurl = '' )
	{
		switch( $type )
		{
			case 'feedbacks':
				if( $hideifnone === '#' ) $hideifnone = false;
				if( $title == '#' ) $title = T_('Display feedback / Leave a comment');
				if( $zero == '#' ) $zero = T_('Send feedback');
				if( $one == '#' ) $one = T_('1 feedback');
				if( $more == '#' ) $more = T_('%d feedbacks');
				break;

			case 'comments':
				if( ! $this->can_see_comments() )
					return false;
				if( $hideifnone === '#' )
				{
					if( $this->can_comment( NULL ) ) // NULL, because we do not want to display errors here!
						$hideifnone = false;
					else
						$hideifnone = true;
				}
				if( $title == '#' ) $title = T_('Display comments / Leave a comment');
				if( $zero == '#' ) $zero = T_('Leave a comment');
				if( $one == '#' ) $one = T_('1 comment');
				if( $more == '#' ) $more = T_('%d comments');
				break;

			case 'trackbacks':
				$this->get_Blog();
				if( ! $this->Blog->get( 'allowtrackbacks' ) )
				{ // Trackbacks not allowed on this blog:
					return;
				}
				if( $hideifnone === '#' ) $hideifnone = false;
				if( $title == '#' ) $title = T_('Display trackbacks / Get trackback address for this post');
				if( $zero == '#' ) $zero = T_('Trackback (0)');
				if( $one == '#' ) $one = T_('Trackback (1)');
				if( $more == '#' ) $more = T_('Trackbacks (%d)');
				break;

			case 'pingbacks':
				// Obsolete, but left for skin compatibility
				return;
				break;

			default:
				debug_die( "Unknown feedback type [$type]" );
		}

		if( $use_popup == '#' )
		{ // Use popups if javascript is included in page
			global $b2commentsjavascript;

			$use_popup = $b2commentsjavascript;
		}

		$number = generic_ctp_number( $this->ID, $type, $status );

		if( ($number == 0) && $hideifnone )
			return false;

		if( $mode != 'none' )
		{ // We want a link:
			$url = $this->get_permanent_url( $mode, $blogurl, true );
			if( $use_popup )
			{ // We need to tell b2evo to use the popup template
				$url = url_add_param( $url, 'template=popup' );
			}
		}


		echo $before;

		if( !empty( $url ) )
		{
			echo '<a href="'.$url;
			echo '#'.$type.'" ';	// Position on feedback
			echo 'title="'.$title.'"';
			if( $use_popup ) echo ' onclick="b2open(this.href); return false"';
			echo '>';
		}

		if( $number == 0 )
			echo $zero;
		elseif( $number == 1 )
			echo $one;
		elseif( $number > 1 )
			echo str_replace( '%d', $number, $more );

		if( !empty( $url ) )
		{
			echo '</a>';
		}

		echo $after;
	}



	/**
	 * Template function: Displays feeback moderation info
	 *
	 * @param string Type of feedback to link to (feedbacks (all)/comments/trackbacks/pingbacks)
	 * @param string String to display before the link (if comments are to be displayed)
	 * @param string String to display after the link (if comments are to be displayed)
	 * @param string Link text to display when there are 0 comments
	 * @param string Link text to display when there is 1 comment
	 * @param string Link text to display when there are >1 comments (include %d for # of comments)
	 * @param string Link
	 * @param boolean true to hide if no feedback
	 */
	function feedback_moderation( $type = 'feedbacks', $before = '', $after = '',
													$zero = '#', $one = '#', $more = '#', $edit_comments_link = '#',
													$hideifnone = true )
	{
		/**
		 * @var User
		 */
		global $current_User;

		if( isset($current_User) && $current_User->check_perm( 'blog_comments', 'any', false,	$this->blog_ID ) )
		{	// We jave permission to edit comments:
			if( $edit_comments_link == '#' )
			{	// Use default link:
				global $admin_url;
				$edit_comments_link = '<a href="'.$admin_url.'?ctrl=browse&amp;tab=posts&amp;blog='.$this->blog_ID.'&amp;p='.$this->ID.'&amp;c=1&amp;tb=1&amp;pb=1#comments" title="'.T_('Moderate these feedbacks').'">'.get_icon( 'edit' ).' '.T_('Moderate...').'</a>';
			}
		}
		else
		{ // User has no right to edit comments:
			$edit_comments_link = '';
		}

		// Inject Edit/moderate link as relevant:
		$zero = str_replace( '%s', $edit_comments_link, $zero );
		$one = str_replace( '%s', $edit_comments_link, $one );
		$more = str_replace( '%s', $edit_comments_link, $more );

		$this->feedback_link( $type, $before, $after, $zero, $one, $more, '', 'draft', '#',	$hideifnone, 'none' );


	}



	/**
	 * Gets button for deleting the Item if user has proper rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param boolean true to make this a button instead of a link
	 * @param string page url for the delete action
	 */
	function get_delete_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $button = false, $actionurl = '#' )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in() ) return false;

		if( ! $current_User->check_perm( 'blog_del_post', 'any', false, $this->blog_ID ) )
		{ // User has right to delete this post
			return false;
		}

		if( $text == '#' )
		{
			if( ! $button )
			{
				$text = get_icon( 'delete', 'imgtag' ).' '.T_('Delete!');
			}
			else
			{
				$text = T_('Delete!');
			}
		}

		if( $title == '#' ) $title = T_('Delete this post');

		if( $actionurl == '#' )
		{
			$actionurl = $admin_url.'?ctrl=editactions&amp;action=delete&amp;post=';
		}
		$url = $actionurl.$this->ID;

		$r = $before;
		if( $button )
		{ // Display as button
			$r .= '<input type="button"';
			$r .= ' value="'.$text.'" title="'.$title.'" onclick="if ( confirm(\'';
			$r .= TS_('You are about to delete this post!\\n\'Cancel\' to stop, \'OK\' to delete.');
			$r .= '\') ) { document.location.href=\''.$url.'\' }"';
			if( !empty( $class ) ) $r .= ' class="'.$class.'"';
			$r .= '/>';
		}
		else
		{ // Display as link
			$r .= '<a href="'.$url.'" title="'.$title.'" onclick="return confirm(\'';
			$r .= TS_('You are about to delete this post!\\n\'Cancel\' to stop, \'OK\' to delete.');
			$r .= '\')"';
			if( !empty( $class ) ) $r .= ' class="'.$class.'"';
			$r .= '>'.$text.'</a>';
		}
		$r .= $after;

		return $r;
	}


	/**
	 * Displays button for deleting the Item if user has proper rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param boolean true to make this a button instead of a link
	 * @param string page url for the delete action
	 */
	function delete_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $button = false, $actionurl = '#' )
	{
		echo $this->get_delete_link( $before, $after, $text, $title, $class, $button, $actionurl );
	}


	/**
	 * Provide link to edit a post if user has edit rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param string page url for the delete action
	 */
	function get_edit_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $actionurl = '#' )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in() ) return false;

		if( ! $current_User->check_perm( 'blog_post_statuses', $this->status, false,
																			$this->blog_ID ) )
		{ // User has no right to edit this post
			return false;
		}

		if( $text == '#' ) $text = get_icon( 'edit' ).' '.T_('Edit...');

		if( $title == '#' ) $title = T_('Edit this post...');

		if( $actionurl == '#' )
		{
			$actionurl = $admin_url.'?ctrl=edit&amp;action=edit&amp;post=';
		}

		$r = $before;
		$r .= '<a href="'.$actionurl.$this->ID;
		$r .= '" title="'.$title.'"';
		if( !empty( $class ) ) $r .= ' class="'.$class.'"';
		$r .=  '>'.$text.'</a>';
		$r .=  $after;

		return $r;
	}


	/**
	 * @see Item::get_edit_link()
	 */
	function edit_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '',
											$actionurl = '#' )

	{
		echo $this->get_edit_link( $before, $after, $text, $title, $class, $actionurl );
	}


	/**
	 * Provide link to publish a post if user has edit rights
	 *
	 * Note: publishing date will be updated
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param string glue between url params
	 */
	function get_publish_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;' )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in() ) return false;

		if( ($this->status == 'published') // Already published!
			|| ! ($current_User->check_perm( 'blog_post_statuses', 'published', false, $this->blog_ID ))
			|| ! ($current_User->check_perm( 'edit_timestamp' ) ) )
		{ // User has no right to publish this post now:
			return false;
		}

		if( $text == '#' ) $text = get_icon( 'publish', 'imgtag' ).' '.T_('Publish NOW!');
		if( $title == '#' ) $title = T_('Publish now using current date and time.');

		$r = $before;
		$r .= '<a href="'.$admin_url.'?ctrl=editactions'.$glue.'action=publish'.$glue.'post_ID='.$this->ID;
		$r .= '" title="'.$title.'"';
		if( !empty( $class ) ) $r .= ' class="'.$class.'"';
		$r .= '>'.$text.'</a>';
		$r .= $after;

		return $r;
	}


	function publish_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;' )
	{
		echo $this->get_publish_link( $before, $after, $text, $title, $class, $glue );
	}


	/**
	 * Provide link to deprecate a post if user has edit rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param string glue between url params
	 */
	function get_deprecate_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;' )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in() ) return false;

		if( ($this->status == 'deprecated') // Already deprecateded!
			|| ! ($current_User->check_perm( 'blog_post_statuses', 'deprecated', false, $this->blog_ID )) )
		{ // User has no right to publish this post now:
			return false;
		}

		if( $text == '#' ) $text = get_icon( 'deprecate', 'imgtag' ).' '.T_('Deprecate!');
		if( $title == '#' ) $title = T_('Deprecate this post!');

		$r = $before;
		$r .= '<a href="'.$admin_url.'?ctrl=editactions'.$glue.'action=deprecate'.$glue.'post_ID='.$this->ID;
		$r .= '" title="'.$title.'"';
		if( !empty( $class ) ) $r .= ' class="'.$class.'"';
		$r .= '>'.$text.'</a>';
		$r .= $after;

		return $r;
	}


	/**
	 * Display link to deprecate a post if user has edit rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param string glue between url params
	 */
	function deprecate_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;' )
	{
		echo $this->get_deprecate_link( $before, $after, $text, $title, $class, $glue );
	}


	/**
	 * Template function: display priority of item
	 *
	 * @param string
	 * @param string
	 */
	function priority( $before = '', $after = '' )
	{
		if( isset($this->priority) )
		{
			echo $before;
			echo $this->priority;
			echo $after;
		}
	}


	/**
	 * Template function: display list of priority options
	 */
	function priority_options( $field_value, $allow_none )
	{
		$r = '';
		if( $allow_none )
		{
			$r = '<option value="">'.T_('None').'</option>';
		}

		foreach( $this->priorities as $i => $name )
		{
			$r .= '<option value="'.$i.'"';
			if( $this->priority == $i )
			{
				$r .= ' selected="selected"';
			}
			$r .= '>'.$name.'</option>';
		}

		return $r;
	}


	/**
	 * Template function: display checkable list of renderers
	 *
	 * @param array|NULL If given, assume these renderers to be checked.
	 */
	function renderer_checkboxes( $item_renderers = NULL )
	{
		global $Plugins, $inc_path, $admin_url;

		require_once $inc_path.'_misc/_plugin.funcs.php';

		$Plugins->restart(); // make sure iterator is at start position

		$atLeastOneRenderer = false;

		if( is_null($item_renderers) )
		{
			$item_renderers = $this->get_renderers();
		}
		// pre_dump( $item_renderers );

		foreach( $Plugins->get_list_by_events( array('RenderItem', 'RenderItemAsHtml', 'RenderItemAsXml') ) as $loop_RendererPlugin )
		{ // Go through whole list of renders
			// echo ' ',$loop_RendererPlugin->code;
			if( empty($loop_RendererPlugin->code) )
			{ // No unique code!
				continue;
			}
			if( $loop_RendererPlugin->apply_rendering == 'stealth'
				|| $loop_RendererPlugin->apply_rendering == 'never' )
			{ // This is not an option.
				continue;
			}
			$atLeastOneRenderer = true;

			echo '<div>';

			// echo $loop_RendererPlugin->apply_rendering;

			echo '<input type="checkbox" class="checkbox" name="renderers[]" value="';
			echo $loop_RendererPlugin->code;
			echo '" id="renderer_';
			echo $loop_RendererPlugin->code;
			echo '"';

			switch( $loop_RendererPlugin->apply_rendering )
			{
				case 'always':
					echo ' checked="checked"';
					echo ' disabled="disabled"';
					break;

				case 'opt-out':
					if( in_array( $loop_RendererPlugin->code, $item_renderers ) // Option is activated
						|| in_array( 'default', $item_renderers ) ) // OR we're asking for default renderer set
					{
						echo ' checked="checked"';
					}
					break;

				case 'opt-in':
					if( in_array( $loop_RendererPlugin->code, $item_renderers ) ) // Option is activated
					{
						echo ' checked="checked"';
					}
					break;

				case 'lazy':
					if( in_array( $loop_RendererPlugin->code, $item_renderers ) ) // Option is activated
					{
						echo ' checked="checked"';
					}
					echo ' disabled="disabled"';
					break;
			}

			echo ' title="';
			echo format_to_output($loop_RendererPlugin->short_desc, 'formvalue');
			echo '" />'
			.' <label for="renderer_';
			echo $loop_RendererPlugin->code;
			echo '" title="';
			echo format_to_output($loop_RendererPlugin->short_desc, 'formvalue');
			echo '">';
			echo format_to_output($loop_RendererPlugin->name);
			echo '</label>';

			// fp> TODO: the first thing we want here is a TINY javascript popup with the LONG desc. The links to readme and external help should be inside of the tiny popup.
			// fp> a javascript DHTML onhover help would be evenb better than the JS popup

			// internal README.html link:
			echo ' '.$loop_RendererPlugin->get_help_link('$readme');
			// external help link:
			echo ' '.$loop_RendererPlugin->get_help_link('$help_url');

			echo "</div>\n";
		}

		if( !$atLeastOneRenderer )
		{
			global $admin_url, $mode;
			echo '<a title="'.T_('Configure plugins').'" href="'
				.$admin_url.'?ctrl=plugins"'
				.( $mode == 'sidebar' ? ' onclick="pop_up_window(\''.$admin_url.'?ctrl=plugins\'); return false;"' : '' )
				.'>'.T_('No renderer plugins are installed.').'</a>';
		}
	}


	/**
	 * Template function: display status of item
	 *
	 * Statuses:
	 * - published
	 * - deprecated
	 * - protected
	 * - private
	 * - draft
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function status( $format = 'htmlbody' )
	{
		global $post_statuses;

		if( $format == 'raw' )
		{
			$this->disp( 'status', 'raw' );
		}
		else
		{
			echo format_to_output( $this->get('t_status'), $format );
		}
	}


	/**
	 * Template function: display extra status of item
	 *
	 * @param string
	 * @param string
	 * @param string Output format, see {@link format_to_output()}
	 */
	function extra_status( $before = '', $after = '', $format = 'htmlbody' )
	{
		if( $format == 'raw' )
		{
			$this->disp( $this->get('t_extra_status'), 'raw' );
		}
		elseif( $extra_status = $this->get('t_extra_status') )
		{
			echo $before.format_to_output( $extra_status, $format ).$after;
		}
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
		global $object_def;

		$ItemTypeCache = & get_Cache( 'ItemTypeCache' );
		$Element = & $ItemTypeCache->get_by_ID( $this->typ_ID, true, !$object_def[$this->objtype]['allow_null']['typ_ID'] /* Do we allow NULL statuses for this object?: */ );
		if( !$Element )
		{ // No status:
			return;
		}

		$extra_status = $Element->get('name');

		if( $format == 'raw' )
		{
			$this->disp( $extra_status, 'raw' );
		}
		else
		{
			echo $before.format_to_output( T_( $extra_status ), $format ).$after;
		}
	}


	/**
	 * Template function: display title for item and link to related URL
	 *
	 * @param string String to display before the title if there is something to display
	 * @param string String to display after the title if there is something to display
	 * @param boolean false if you don't want to link to related URL (if applicable)
	 * @param string Output format, see {@link format_to_output()}
	 */
	function title(
		$before = '',        // HTML/text to be displayed before title
		$after = '',         // HTML/text to be displayed after title
		$add_link = true,    // Add li  nk to this title?
		$format = 'htmlbody' )
	{
		if( empty($this->title) && $add_link )
			$title = $this->url;
		else
			$title = $this->title;

		if( empty($title) )
		{ // Nothing to display
			return;
		}

		$title = format_to_output( $title, $format );

		if( $add_link && (!empty($this->url)) )
		{
			$title = '<a href="'.$this->url.'">'.$title.'</a>';
		}

		echo $before;
		echo $title;
		echo $after;
	}


	/**
	 * Template function: Displays trackback autodiscovery information
	 */
	function trackback_rdf()
	{
		// if (!stristr($_SERVER['HTTP_USER_AGENT'], 'W3C_Validator')) {
		// fplanque WARNING: this isn't a very clean way to validate :/
		// fplanque added: html comments (not perfect but better way of validating!)
		echo "<!--\n";
		echo '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" '."\n";
		echo '  xmlns:dc="http://purl.org/dc/elements/1.1/"'."\n";
		echo '  xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">'."\n";
		echo '<rdf:Description'."\n";
		echo '  rdf:about="';
		$this->permanent_url( 'single' );
		echo '"'."\n";
		echo '  dc:identifier="';
		$this->permanent_url( 'single' );
		echo '"'."\n";
		$this->title( '  dc:title="', '"'."\n", false, 'xmlattr' );
		echo '  trackback:ping="';
		$this->trackback_url();
		echo '" />'."\n";
		echo '</rdf:RDF>';
		echo "-->\n";
		// }
	}


	/**
	 * Template function: displays url to use to trackback this item
	 */
	function trackback_url()
	{
		echo $this->get_trackback_url();
	}


	/**
	 * Template function: get url to use to trackback this item
	 * @return string
	 */
	function get_trackback_url()
	{
		global $htsrv_url, $Settings;

		if( $Settings->get('links_extrapath') != 'disabled' )
		{
			return $htsrv_url.'trackback.php/'.$this->ID;
		}
		else
		{
			return $htsrv_url.'trackback.php?tb_id='.$this->ID;
		}
	}


	/**
	 * Template function: Display link to item related url
	 *
	 * @param string string to display before the link (if exists)
	 * @param string string to display after the link (if exists)
	 * @param string Link text to use (%s gets replaced by the URL).
	 * @param array Attributes for the <a> tag (if the href attribute is set, %s gets replaced by the URL).
	 * @param string Output format, see {@link format_to_output()}
	 */
	function url_link( $before = '', $after = '', $text = '%s', $attribs = array(), $format = 'htmlbody' )
	{
		if( ! empty( $this->url ) )
		{
			if( isset($attribs['href']) )
			{	// We have specified our own href attribute for the link:!
				$attribs['href'] = str_replace( '%s', $this->url, $attribs['href'] );
			}
			else
			{ // Use default href:
				$attribs['href'] = $this->url;
			}
			echo $before;
			echo format_to_output( '<a'.get_field_attribs_as_string( $attribs ).'>'.str_replace( '%s', $this->url, $text ).'</a>', $format );
			echo $after;
		}
	}


	/**
	 * Template function: Display the number of words in the post
	 */
	function wordcount()
	{
		echo $this->wordcount;
	}


	/**
	 * Template function: Display the number of times the Item has been viewed
	 *
	 * Note: viewcount is incremented whenever the Item's content is displayed with "MORE"
	 * (i-e full content), see {@link Item::content()}.
	 *
	 * Viewcount is NOT incremented on page reloads and other special cases, see {@link Hit::is_new_view()}
	 *
	 * %d gets replaced in all params by the number of views.
	 *
	 * @param string Link text to display when there are 0 views
	 * @param string Link text to display when there is 1 views
	 * @param string Link text to display when there are >1 views
	 * @return string The phrase about the number of views.
	 */
	function get_views( $zero = '#', $one = '#', $more = '#' )
	{
		if( !$this->views )
		{
			$r = ( $zero == '#' ? T_( 'No views' ) : $zero );
		}
		elseif( $this->views == 1 )
		{
			$r = ( $one == '#' ? T_( '1 view' ) : $one );
		}
		else
		{
			$r = ( $more == '#' ? T_( '%d views' ) : $more );
		}

		return str_replace( '%d', $this->views, $r );
	}


	/**
	 * Template function: Display a phrase about the number of Item views.
	 *
	 * @param string Link text to display when there are 0 views
	 * @param string Link text to display when there is 1 views
	 * @param string Link text to display when there are >1 views (include %d for # of views)
	 * @return integer Number of views.
	 */
	function views( $zero = '#', $one = '#', $more = '#' )
	{
		echo $this->get_views( $zero, $one, $more );

		return $this->views;
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
				// make sure main cat is in extracat list and there are no duplicates
				$this->extra_cat_IDs[] = $this->main_cat_ID;
				$this->extra_cat_IDs = array_unique( $this->extra_cat_IDs );
				// Update derived property:
				$this->blog_ID = get_catblog( $this->main_cat_ID ); // This is a derived var
				return $r;

			case 'extra_cat_IDs':
				// ARRAY! We do not record this change (yet)
				$this->extra_cat_IDs = $parvalue;
				// make sure main cat is in extracat list and there are no duplicates
				$this->extra_cat_IDs[] = $this->main_cat_ID;
				$this->extra_cat_IDs = array_unique( $this->extra_cat_IDs );
				break;

			case 'typ_ID':
			case 'st_ID':
				return $this->set_param( $parname, 'number', $parvalue, true );

			case 'content':
				$r1 = $this->set_param( 'content', 'string', $parvalue, $make_null );
				// Update wordcount as well:
				$r2 = $this->set_param( 'wordcount', 'number', bpost_count_words($this->content), false );
				return ( $r1 || $r2 ); // return true if one changed

			case 'wordcount':
				return $this->set_param( 'wordcount', 'number', $parvalue, false );

			case 'issue_date':
			case 'datestart':
				$this->issue_date = $parvalue;
				return $this->set_param( 'datestart', 'date', $parvalue, false );

			case 'deadline':
				return $this->set_param( 'deadline', 'date', $parvalue, true );

			case 'renderers': // deprecated
				return $this->set_renderers( $parvalue );

			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Set the renderers of the Item.
	 *
	 * @param array List of renderer codes.
	 * @return boolean true, if it has been set; false if it has not changed
	 */
	function set_renderers( $renderers )
	{
		return $this->set_param( 'renderers', 'string', implode( '.', $renderers ) );
	}


	/**
	 * Set the Author of the Item.
	 *
	 * @param User (Do NOT set to NULL or you may kill the current_User)
	 * @return boolean true, if it has been set; false if it has not changed
	 */
	function set_creator_User( & $creator_User )
	{
		$this->creator_User = & $creator_User;
		$this->Author = & $this->creator_User; // deprecated  fp> TODO: Test and see if this line can be put once and for all in the constructor
		return $this->set( $this->creator_field, $creator_User->ID );
	}


	/**
	 * Create a new Item/Post and insert it into the DB
	 *
	 * This function has to handle all needed DB dependencies!
	 *
	 * @todo cleanup the set() calls
	 */
	function insert(
		$author_user_ID,              // Author
		$post_title,
		$post_content,
		$post_timestamp,              // 'Y-m-d H:i:s'
		$main_cat_ID = 1,             // Main cat ID
		$extra_cat_IDs = array(),     // Table of extra cats
		$post_status = 'published',
		$post_locale = '#',
		$post_urltitle = '',
		$post_url = '',
		$post_comment_status = 'open',
		$post_renderers = array('default'),
		$item_typ_ID = 1,
		$item_st_ID = NULL )
	{
		global $DB, $query, $UserCache;
		global $localtimenow, $default_locale;

		if( $post_locale == '#' ) $post_locale = $default_locale;

		// echo 'INSERTING NEW POST ';

		if( isset( $UserCache ) )	// DIRTY HACK
		{ // If not in install procedure...
			$this->set_creator_User( $UserCache->get_by_ID( $author_user_ID ) );
		}
		else
		{
			$this->set( $this->creator_field, $author_user_ID );
		}
		$this->set( $this->lasteditor_field, $this->{$this->creator_field} );
		$this->set( 'title', $post_title );
		$this->set( 'urltitle', $post_urltitle );
		$this->set( 'content', $post_content );
		$this->set( 'datestart', $post_timestamp );
		$this->set( 'datemodified', date('Y-m-d H:i:s',$localtimenow) );
		$this->set( 'main_cat_ID', $main_cat_ID );
		$this->set( 'extra_cat_IDs', $extra_cat_IDs );
		$this->set( 'status', $post_status );
		$this->set( 'locale', $post_locale );
		$this->set( 'url', $post_url );
		$this->set( 'comment_status', $post_comment_status );
		$this->set_renderers( $post_renderers );
		$this->set( 'typ_ID', $item_typ_ID );
		$this->set( 'st_ID', $item_st_ID );

		// INSERT INTO DB:
		$this->dbinsert();

		return $this->ID;
	}


	/**
	 * Insert object into DB based on previously recorded changes
	 *
	 * @return boolean true on success
	 */
	function dbinsert()
	{
		global $DB, $current_User, $Plugins;

		$DB->begin();

		if( empty($this->creator_user_ID) )
		{ // No creator assigned yet, use current user:
			$this->set_creator_User( $current_User );
		}

		// validate url title
		$this->set( 'urltitle', urltitle_validate( $this->urltitle, $this->title, 0, false, $this->dbprefix, $this->dbIDname, $this->dbtablename) );

		// TODO: allow a plugin to cancel update here (by returning false)?
		$Plugins->trigger_event( 'PrependItemInsertTransact', $params = array( 'Item' => & $this ) );

		if( $result = parent::dbinsert() )
		{ // We could insert the item object..

			// Let's handle the extracats:
			$this->insert_update_extracats( 'insert' );

			$DB->commit();

			$Plugins->trigger_event( 'AfterItemInsert', $params = array( 'Item' => & $this ) );
		}
		else
		{
			$DB->rollback();
		}

		return $result;
	}


	/**
	 * Update a post and save to DB
	 *
	 * This function has to handle all needed DB dependencies!
	 *
	 * @param string Title
	 * @param string Content
	 * @param string Timestamp ('Y-m-d H:i:s')
	 * @param integer Main category ID
	 * @param array List of extra cats
	 * @param string Status ('published', 'deprecated', 'protected', 'private' or 'draft'). Empty to not change it.
	 * @param string Locale. '#' to not change it.
	 * @param mixed Not used
	 * @param integer OBSOLETE
	 * @param boolean Pings done?
	 * @param string URL title
	 * @param string URL
	 * @param string Post comment status
	 * @param array Renderers
	 * @param integer Typ ID
	 * @param integer Status(?) ID
	 */
	function update(
		$post_title,
		$post_content,
		$post_timestamp = '',         // 'Y-m-d H:i:s'
		$main_cat_ID = 1,             // Main cat ID
		$extra_cat_IDs = array(),     // Table of extra cats
		$post_status = 'published',
		$post_locale = '#',
		$post_urltitle = '',
		$post_url = '',
		$post_comment_status = 'open',
		$post_renderers = array(),
		$item_typ_ID = 0,
		$item_st_ID = 0 )
	{
		global $localtimenow, $default_locale;

		$this->set( 'title', $post_title );
		$this->set( 'urltitle', $post_urltitle );
		$this->set( 'url', $post_url );
		$this->set( 'content', $post_content );
		// this is automatic $this->set( 'datemodified', date('Y-m-d H:i:s', $localtimenow ) );
		$this->set( 'main_cat_ID', $main_cat_ID );
		$this->set( 'extra_cat_IDs', $extra_cat_IDs );
		$this->set( 'status', $post_status );
		$this->set( 'comment_status', $post_comment_status );
		$this->set_renderers( $post_renderers );
		$this->set( 'typ_ID', $item_typ_ID );
		$this->set( 'st_ID', $item_st_ID );
		if( $post_locale != '#' )
		{ // only update if it was changed
			$this->set( 'locale', $post_locale );
		}
		if( !empty($post_timestamp) )
		{
			$this->set( 'datestart', $post_timestamp );
		}

		// UPDATE DB:
		$this->dbupdate();
	}


	/**
	 * Update the DB based on previously recorded changes
	 *
	 * @return boolean true on success
	 */
	function dbupdate()
	{
		global $DB, $Plugins, $object_def;

		$DB->begin();

		// validate url title
		if( empty($this->urltitle) || isset($this->dbchanges[$object_def['Item']['db_cols']['urltitle']]) )
		{ // Url title has changed or is empty
			// echo 'updating url title';
			$this->set( 'urltitle', urltitle_validate( $this->urltitle, $this->title, $this->ID,
																false, $this->dbprefix, $this->dbIDname, $this->dbtablename ) );
		}

		// TODO: dh> allow a plugin to cancel update here (by returning false)?
		$Plugins->trigger_event( 'PrependItemUpdateTransact', $params = array( 'Item' => & $this ) );

		if( $result = parent::dbupdate() )
		{ // We could update the item object..

			// Let's handle the extracats:
			$this->insert_update_extracats( 'update' );

			// Empty pre-rendered content cache - any item property may have influence on it:
			$DB->query( 'DELETE FROM T_item__prerendering WHERE itpr_itm_ID = '.$this->ID );
			$this->content_prerendered = NULL;

			$DB->commit();

			$Plugins->trigger_event( 'AfterItemUpdate', $params = array( 'Item' => & $this ) );
		}
		else
		{
			$DB->commit();
		}

		return $result;
	}


	/**
	 * Trigger event AfterItemDelete after calling parent method.
	 *
	 * @return boolean true on success
	 */
	function dbdelete()
	{
		global $DB, $Plugins;

		// remember ID, because parent method resets it to 0
		$old_ID = $this->ID;

		$DB->begin();

		if( $r = parent::dbdelete() )
		{
			// Empty pre-rendered content cache:
			$DB->query( 'DELETE FROM T_item__prerendering WHERE itpr_itm_ID = '.$this->ID );
			$this->content_prerendered = NULL;

			$DB->commit();

			// re-set the ID for the Plugin event
			$this->ID = $old_ID;

			$Plugins->trigger_event( 'AfterItemDelete', $params = array( 'Item' => & $this ) );

			$this->ID = 0;
		}
		else
		{
			$DB->rollback();
		}

		return $r;
	}


	/**
	 * @param string 'insert' | 'update'
	 */
	function insert_update_extracats( $mode )
	{
		global $DB;

		$DB->begin();

		if( ! is_null( $this->extra_cat_IDs ) )
		{ // Okay the extra cats are defined:

			if( $mode == 'update' )
			{
				// delete previous extracats:
				$DB->query( 'DELETE FROM T_postcats WHERE postcat_post_ID = '.$this->ID, 'delete previous extracats' );
			}

			// insert new extracats:
			$query = "INSERT INTO T_postcats( postcat_post_ID, postcat_cat_ID ) VALUES ";
			foreach( $this->extra_cat_IDs as $extra_cat_ID )
			{
				//echo "extracat: $extracat_ID <br />";
				$query .= "( $this->ID, $extra_cat_ID ),";
			}
			$query = substr( $query, 0, strlen( $query ) - 1 );
			$DB->query( $query, 'insert new extracats' );
		}

		$DB->commit();
	}


	/**
	 * Increment the view count of the item directly in DB (if the item's Author is not $current_User).
	 *
	 * This method serves TWO purposes (that would break if we used dbupdate() ) :
	 *  - Increment the viewcount WITHOUT affecting the lastmodified date and user.
	 *  - Increment the viewcount in an ATOMIC manner (even if several hits on the same Item occur simultaneously).
	 *
	 * This also triggers the plugin event 'ItemViewsIncreased' if the view count has been increased.
	 *
	 * @return boolean Did we increase view count?
	 */
	function inc_viewcount()
	{
		global $Plugins, $DB, $current_User, $Debuglog;

		if( isset( $current_User ) && ( $current_User->ID == $this->creator_user_ID ) )
		{
			$Debuglog->add( 'Not incrementing view count, because viewing user is creator of the item.', 'items' );

			return false;
		}

		$DB->query( 'UPDATE T_posts
		                SET post_views = post_views + 1
		              WHERE '.$this->dbIDname.' = '.$this->ID );

		// Trigger event that the item's view has been increased
		$Plugins->trigger_event( 'ItemViewsIncreased', array( 'Item' => & $this ) );

		return true;
	}


	/**
	 * Get the User who is assigned to the Item.
	 *
	 * @return User|NULL NULL if no user is assigned.
	 */
	function get_assigned_User()
	{
		if( ! isset($this->assigned_User) && isset($this->assigned_user_ID) )
		{
			$UserCache = & get_Cache( 'UserCache' );
			$this->assigned_User = & $UserCache->get_by_ID( $this->assigned_user_ID );
		}

		return $this->assigned_User;
	}


	/**
	 * Get the User who created the Item.
	 *
	 * @return User
	 */
	function & get_creator_User()
	{
		if( is_null($this->creator_User) )
		{
			$UserCache = & get_Cache( 'UserCache' );
			$this->creator_User = & $UserCache->get_by_ID( $this->creator_user_ID );
			$this->Author = & $this->creator_User;  // deprecated
		}

		return $this->creator_User;
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
			$BlogCache = & get_Cache( 'BlogCache' );
			$this->Blog = & $BlogCache->get_by_ID( $this->blog_ID );
		}
	}


	/**
	 * Execute or schedule post(=after) processing tasks
	 *
	 * Includes notifications & pings
	 */
	function handle_post_processing( $display = true )
	{
		global $Settings;

		$notifications_mode = $Settings->get('outbound_notifications_mode');

		if( $notifications_mode == 'off' )
		{	// Exit silently
			return false;
		}

		if( $this->notifications_status == 'finished' )
		{ // pings have been done before
			if( $display )
			{
				echo "<div class=\"panelinfo\">\n";
				echo '<p>', T_('Post had already pinged: skipping notifications...'), "</p>\n";
				echo "</div>\n";
			}
			return false;
		}

		if( $this->notifications_status != 'noreq' )
		{ // pings have been done before

			// TODO: Check if issue_date has changed and reschedule

			if( $display )
			{
				echo "<div class=\"panelinfo\">\n";
				echo '<p>', T_('Post processing already pending...'), "</p>\n";
				echo "</div>\n";
			}
			return false;
		}

		if( $this->status != 'published' )
		{

			// TODO: discard any notification that may be pending!

			if( $display )
			{
				echo "<div class=\"panelinfo\">\n";
				echo '<p>', T_('Post not publicly published: skipping notifications...'), "</p>\n";
				echo "</div>\n";
			}
			return false;
		}

		if( $notifications_mode == 'immediate' )
		{	// We want to do the post processing immediately:
			// send outbound pings:
			$this->send_outbound_pings( $display );

			// Send email notifications now!
			$this->send_email_notifications( $display );

			// Record that processing has been done:
			$this->set( 'notifications_status', 'finished' );
		}
		else
		{	// We want asynchronous post processing:
			if( $display )
			{
				echo "<div class=\"panelinfo\">\n";
				echo '<p>'.T_('Scheduling asynchronous notifications...')."</p>\n";
				echo "</div>\n";
			}

			// CREATE OBJECT:
			load_class( '/MODEL/cron/_cronjob.class.php' );
			$edited_Cronjob = & new Cronjob();

			// start datetime. We do not want to ping before the post is effectively published:
			$edited_Cronjob->set( 'start_datetime', $this->issue_date );

			// no repeat.

			// name:
			$edited_Cronjob->set( 'name', sprintf( T_('Send notifications for &laquo;%s&raquo;'), strip_tags($this->title) ) );

			// controller:
			$edited_Cronjob->set( 'controller', 'cron/_post_notifications.job.php' );

			// params: specify which post this job is supposed to send notifications for:
			$edited_Cronjob->set( 'params', array( 'item_ID' => $this->ID ) );

			// Save cronjob to DB:
			$edited_Cronjob->dbinsert();

			// Memorize the cron job ID which is going to handle this post:
			$this->set( 'notifications_ctsk_ID', $edited_Cronjob->ID );

			// Record that processing has been scheduled:
			$this->set( 'notifications_status', 'todo' );
		}

		// Save the new processing status to DB
		$this->dbupdate();

		return true;
	}


	/**
	 * Send email notifications to subscribed users
	 *
	 * @todo fp>> shall we notify suscribers of blog were this is in extra-cat? blueyed>> IMHO yes.
	 */
	function send_email_notifications( $display = true )
	{
		global $DB, $admin_url, $debug, $Debuglog;

		if( $display )
		{
			echo "<div class=\"panelinfo\">\n";
			echo '<h3>', T_('Notifying subscribed users...'), "</h3>\n";
		}

		// Get list of users who want to be notfied:
		// TODO: also use extra cats/blogs??
		$sql = 'SELECT DISTINCT user_email, user_locale
							FROM T_subscriptions INNER JOIN T_users ON sub_user_ID = user_ID
						WHERE sub_coll_ID = '.$this->blog_ID.'
							AND sub_items <> 0
							AND LENGTH(TRIM(user_email)) > 0';
		$notify_list = $DB->get_results( $sql );

		// Preprocess list: (this comes form Comment::send_email_notifications() )
		$notify_array = array();
		foreach( $notify_list as $notification )
		{
			$notify_array[$notification->user_email] = $notification->user_locale;
		}

		if( empty($notify_array) )
		{ // No-one to notify:
			if( $display )
			{
				echo '<p>', T_('No-one to notify.'), "</p>\n</div>\n";
			}
			return false;
		}

		/*
		 * We have a list of email addresses to notify:
		 */
		$this->get_creator_User();
		$mail_from = '"'.$this->creator_User->get('preferredname').'" <'.$this->creator_User->get('email').'>';

		$Blog = & $this->get_Blog();

		// Send emails:
		$cache_by_locale = array();
		foreach( $notify_array as $notify_email => $notify_locale )
		{
			if( ! isset($cache_by_locale[$notify_locale]) )
			{ // No message for this locale generated yet:
				locale_temp_switch($notify_locale);

				// Calculate length for str_pad to align labels:
				$pad_len = max( strlen(T_('Blog')), strlen(T_('Author')), strlen(T_('Title')), strlen(T_('Url')), strlen(T_('Content')) );

				$cache_by_locale[$notify_locale]['subject'] = sprintf( T_('[%s] New post: "%s"'), $Blog->get('shortname'), $this->get('title') );

				$cache_by_locale[$notify_locale]['message'] =
					str_pad( T_('Blog'), $pad_len ).': '.$Blog->get('shortname')
					.' ( '.str_replace('&amp;', '&', $Blog->get('blogurl'))." )\n"

					.str_pad( T_('Author'), $pad_len ).': '.$this->creator_User->get('preferredname').' ('.$this->creator_User->get('login').")\n"

					.str_pad( T_('Title'), $pad_len ).': '.$this->get('title')."\n"

					// linked URL or "-" if empty:
					.str_pad( T_('Url'), $pad_len ).': '.( empty( $this->url ) ? '-' : str_replace('&amp;', '&', $this->get('url')) )."\n"

					.str_pad( T_('Content'), $pad_len ).': '
						// We use pid to get a short URL and avoid it to wrap on a new line in the mail which may prevent people from clicking
						// TODO: might get moved onto a single line, at the end of the content..
						.str_replace('&amp;', '&', $this->get_permanent_url( 'pid' ))."\n\n"

					.$this->get('content')."\n"

					// Footer:
					."\n-- \n"
					.T_('Edit/Delete').': '.$admin_url.'?ctrl=browse&blog='.$this->blog_ID.'&p='.$this->ID."\n\n"

					.T_('Edit your subscriptions/notifications').': '.str_replace('&amp;', '&', url_add_param( $Blog->get( 'blogurl' ), 'disp=subs' ) )."\n";

				locale_restore_previous();
			}

			if( $display ) echo T_('Notifying:').$notify_email."<br />\n";
			if( $debug >= 2 )
			{
				echo "<p>Sending notification to $notify_email:<pre>$cache_by_locale[$notify_locale]['message']</pre>";
			}

			send_mail( $notify_email, $cache_by_locale[$notify_locale]['subject'], $cache_by_locale[$notify_locale]['message'], $mail_from );
		}

		if( $display ) echo '<p>', T_('Done.'), "</p>\n</div>\n";
	}


  /**
	 * Send outbound pings for a post
	 *
	 * Dirty temporary function
	 *
	 * @param Item
	 * @param boolean
	 */
	function send_outbound_pings( $display = true )
	{
		load_funcs( '_misc/_ping.funcs.php' );

		$blogparams = get_blogparams_by_ID( $this->blog_ID );

		pingb2evonet( $blogparams, $this->ID, $this->title, $display);
		pingWeblogs( $blogparams, $display);
		pingBlogs( $blogparams, $display);
		pingTechnorati( $blogparams, $display );
	}


	/**
	 * Get a member param by its name
	 *
	 * @param mixed Name of parameter
	 * @return mixed Value of parameter
	 */
	function get( $parname )
	{
		global $object_def, $post_statuses;

		switch( $parname )
		{
			case 't_author':
				// Text: author
				$this->get_creator_User();
				return $this->creator_User->get( 'preferredname' );

			case 't_assigned_to':
				// Text: assignee
				if( ! $this->get_assigned_User() )
				{
					return '';
				}
				return $this->assigned_User->get( 'preferredname' );

			case 't_status':
				// Text status:
				return T_( $post_statuses[$this->status] );

			case 't_extra_status':
				$ItemStatusCache = & get_Cache( 'ItemStatusCache' );
				if( ! ($Element = & $ItemStatusCache->get_by_ID( $this->st_ID, true, false ) ) )
				{ // No status:
					return '';
				}
				return $Element->get_name();

			case 't_type':
				// Item type (name):
				if( empty($this->typ_ID) )
				{
					return '';
				}

				$ItemTypeCache = & get_Cache( 'ItemTypeCache' );
				$type_Element = & $ItemTypeCache->get_by_ID( $this->typ_ID );
				return $type_Element->get_name();

			case 't_priority':
				return $this->priorities[ $this->priority ];

			case 'pingsdone':
				// Deprecated by fp 2006-08-21
				return ($this->post_notifications_status == 'finished');
		}

		return parent::get( $parname );
	}


	/**
	 * Assign the item to the first category we find in the requested collection
	 *
	 * @param integer $collection_ID
	 */
	function assign_to_first_cat_for_collection( $collection_ID )
	{
		global $DB;

		// Get the first category ID for the collection ID param
		$cat_ID = $DB->get_var( '
				SELECT cat_ID
					FROM T_categories
				 WHERE cat_blog_ID = '.$collection_ID.'
				 ORDER BY cat_ID ASC
				 LIMIT 1' );

		// Set to the item the first category we got
		$this->set( 'main_cat_ID', $cat_ID );
	}


	/**
	 * Get the list of renderers for this Item.
	 * @return array
	 */
	function get_renderers()
	{
		return explode( '.', $this->renderers );
	}


	/**
	 * Get the list of validated renderers for this Item. This includes stealth plugins etc.
	 * @return array
	 */
	function get_renderers_validated()
	{
		if( ! isset($this->renderers_validated) )
		{
			global $Plugins;
			$this->renderers_validated = $Plugins->validate_list( $this->get_renderers() );
		}
		return $this->renderers_validated;
	}


	/**
	 * Add a renderer (by code) to the Item.
	 * @param string Renderer code to add for this item
	 */
	function add_renderer( $renderer_code )
	{
		$renderers = $this->get_renderers();
		if( ! in_array( $renderer_code, $renderers ) )
		{
			$renderers[] = $renderer_code;
			$this->set_renderers( $renderers );

			//echo 'Added renderer '.$renderer_code;
		}
	}


	/**
	 * Remove a renderer (by code) from the Item.
	 * @param string Renderer code to remove for this item
	 */
	function remove_renderer( $renderer_code )
	{
		$renderers = $this->get_renderers();
		if( ( $key = array_search( $renderer_code, $renderers ) ) !== false )
		{
			unset($renderers[$key]);
			$this->set_renderers( $renderers );

			//echo 'Removed renderer '.$renderer_code;
		}
	}
}


/*
 * $Log$
 * Revision 1.98  2006/09/25 17:52:20  blueyed
 * doc
 *
 * Revision 1.97  2006/09/23 14:01:29  blueyed
 * Pre-rendered content: use renderers string for cache_key
 *
 * Revision 1.96  2006/09/21 16:54:26  blueyed
 * Experimental caching of pre-rendered item content. Added table T_item__prerendering.
 *
 * Revision 1.95  2006/09/11 22:29:19  fplanque
 * chapter cleanup
 *
 * Revision 1.94  2006/09/11 22:06:08  blueyed
 * Cleaned up option_list callback handling
 *
 * Revision 1.93  2006/09/11 19:35:34  fplanque
 * minor
 *
 * Revision 1.92  2006/09/10 23:35:56  fplanque
 * new permalink styles
 * (decoding not implemented yet)
 *
 * Revision 1.91  2006/09/10 21:18:11  fplanque
 * transposed permalink generation
 * looks like a lot of changes but basically I only swiched the imbrocation of an if and a switch
 * this is needed to make further changes more readable
 *
 * Revision 1.90  2006/09/10 20:59:18  fplanque
 * extended extra path info setting
 *
 * Revision 1.89  2006/09/10 19:23:28  blueyed
 * Removed Plugin::code(), ::name(), ::short_desc() and ::long_desc(); Fixes for mt-import.php
 *
 * Revision 1.88  2006/09/06 20:45:34  fplanque
 * ItemList2 fixes
 *
 * Revision 1.87  2006/08/30 21:58:51  blueyed
 * Fixed notice/warning
 *
 * Revision 1.86  2006/08/29 00:26:11  fplanque
 * Massive changes rolling in ItemList2.
 * This is somehow the meat of version 2.0.
 * This branch has gone officially unstable at this point! :>
 *
 * Revision 1.85  2006/08/26 20:30:42  fplanque
 * made URL titles Google friendly
 *
 * Revision 1.84  2006/08/26 16:33:50  fplanque
 * minor
 *
 * Revision 1.83  2006/08/24 00:43:28  fplanque
 * scheduled pings part 2
 *
 * Revision 1.82  2006/08/21 21:33:35  fplanque
 * scheduled pings part 1
 *
 * Revision 1.81  2006/08/21 16:07:43  fplanque
 * refactoring
 *
 * Revision 1.80  2006/08/20 22:25:21  fplanque
 * param_() refactoring part 2
 *
 * Revision 1.79  2006/08/20 20:12:32  fplanque
 * param_() refactoring part 1
 *
 * Revision 1.78  2006/08/19 08:50:26  fplanque
 * moved out some more stuff from main
 *
 * Revision 1.77  2006/08/19 07:56:30  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.76  2006/08/19 02:15:07  fplanque
 * Half kille dthe pingbacks
 * Still supported in DB in case someone wants to write a plugin.
 *
 * Revision 1.75  2006/08/07 16:33:27  fplanque
 * Default messages should not be any more geeky then necessary.
 *
 * Revision 1.74  2006/08/05 17:18:41  blueyed
 * doc/todo
 *
 * Revision 1.73  2006/08/02 17:06:11  blueyed
 * added "(ID #X)" to permalink's default title; doc
 *
 * Revision 1.72  2006/07/31 15:42:43  blueyed
 * Fixed handling of post_comment_status
 *
 * Revision 1.71  2006/07/26 17:15:44  blueyed
 * Replaced "name" attribute with "id" for anchors
 *
 * Revision 1.70  2006/07/23 21:51:01  blueyed
 * doc
 *
 * Revision 1.69  2006/07/10 15:27:45  blueyed
 * Fixed display of Blog name in items list (at least with PHP5 it added "Object id #xx" because of the Item::get_Blog() return value).
 *
 * Revision 1.68  2006/07/08 23:03:52  blueyed
 * Removed debugging/test code.
 *
 * Revision 1.67  2006/07/08 22:33:43  blueyed
 * Integrated "simple edit form".
 *
 * Revision 1.66  2006/07/01 17:07:56  blueyed
 * Fixed Edit/Delete link for item notifications
 *
 * Revision 1.65  2006/06/25 17:33:39  fplanque
 * fixed moderation link
 *
 * Revision 1.64  2006/06/22 21:58:34  fplanque
 * enhanced comment moderation
 *
 * Revision 1.63  2006/06/22 18:37:47  fplanque
 * fixes
 *
 * Revision 1.62  2006/06/19 20:59:37  fplanque
 * noone should die anonymously...
 *
 * Revision 1.58  2006/06/15 15:01:19  fplanque
 * bugfix
 *
 * Revision 1.57  2006/06/12 00:42:21  blueyed
 * Item::get_trackback_url() added
 *
 * Revision 1.56  2006/06/05 23:15:00  blueyed
 * cleaned up plugin help links
 *
 * Revision 1.55  2006/06/05 18:03:46  blueyed
 * *** empty log message ***
 *
 * Revision 1.54  2006/06/02 20:12:37  fplanque
 * I don't like that fuzzy code.
 *
 * Revision 1.53  2006/06/01 21:07:33  blueyed
 * Moved ItemCanComment back.
 *
 * Revision 1.52  2006/06/01 18:36:09  fplanque
 * no message
 *
 * Revision 1.51  2006/05/30 20:32:57  blueyed
 * Lazy-instantiate "expensive" properties of Comment and Item.
 *
 * Revision 1.50  2006/05/30 19:39:55  fplanque
 * plugin cleanup
 *
 * Revision 1.49  2006/05/29 23:40:34  blueyed
 * Do not display errors with "comment" feedback link.
 *
 * Revision 1.48  2006/05/29 22:27:46  blueyed
 * Use NULL instead of false for "no display".
 *
 * Revision 1.47  2006/05/29 19:28:44  fplanque
 * no message
 *
 * Revision 1.46  2006/05/24 20:46:05  blueyed
 * Forgot to commit changes needed for the "ItemCanComment" event.
 *
 * Revision 1.44  2006/05/19 18:15:05  blueyed
 * Merged from v-1-8 branch
 *
 * Revision 1.43.2.1  2006/05/19 15:06:24  fplanque
 * dirty sync
 *
 * Revision 1.43  2006/05/12 21:53:37  blueyed
 * Fixes, cleanup, translation for plugins
 *
 * Revision 1.42  2006/04/29 23:27:10  blueyed
 * Only trigger update/insert/delete events if parent returns true
 *
 * Revision 1.41  2006/04/24 20:35:32  fplanque
 * really nasty bugs!
 *
 * Revision 1.40  2006/04/24 20:31:15  blueyed
 * doc fixes
 *
 * Revision 1.39  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.38  2006/04/19 19:52:27  blueyed
 * url-encode redirect_to param
 *
 * Revision 1.37  2006/04/19 17:25:31  blueyed
 * Commented debug output out
 *
 * Revision 1.36  2006/04/19 15:56:02  blueyed
 * Renamed T_posts.post_comments to T_posts.post_comment_status (DB column rename!);
 * and Item::comments to Item::comment_status (Item API change)
 *
 * Revision 1.35  2006/04/19 13:05:21  fplanque
 * minor
 *
 * Revision 1.34  2006/04/18 21:09:20  blueyed
 * Added hooks to manipulate Items before insert/update/preview; fixes; cleanup
 *
 * Revision 1.33  2006/04/18 20:41:00  blueyed
 * Decent getters/setters for renderers.
 *
 * Revision 1.31  2006/04/13 01:23:19  blueyed
 * Moved help related functions back to Plugin class
 *
 * Revision 1.30  2006/04/11 22:28:58  blueyed
 * cleanup
 *
 * Revision 1.29  2006/04/11 21:22:25  fplanque
 * partial cleanup
 *
 * Revision 1.28  2006/04/10 23:11:40  blueyed
 * Fixed incrementing view count on ALL items altogether! :/
 *
 * Revision 1.27  2006/04/06 09:39:10  blueyed
 * doc
 *
 * Revision 1.26  2006/04/05 19:16:34  blueyed
 * Refactored/cleaned up help link handling: defaults to online-manual-pages now.
 *
 * Revision 1.25  2006/04/04 21:49:02  blueyed
 * doc
 *
 * Revision 1.24  2006/03/27 21:22:11  fplanque
 * more admin link fixes
 *
 * Revision 1.23  2006/03/24 20:24:37  fplanque
 * fixed admin links
 *
 * Revision 1.21  2006/03/23 23:34:13  blueyed
 * cleanup
 *
 * Revision 1.20  2006/03/23 22:01:17  blueyed
 * todo
 *
 * Revision 1.19  2006/03/23 21:02:19  fplanque
 * cleanup
 *
 * Revision 1.18  2006/03/21 19:55:05  blueyed
 * notifications: cache by locale/nicer (padded) formatting; respect $allow_msgform in msgform_link()
 *
 * Revision 1.17  2006/03/18 19:17:53  blueyed
 * Removed remaining use of $img_url
 *
 * Revision 1.16  2006/03/15 19:31:26  blueyed
 * whitespace
 *
 * Revision 1.15  2006/03/12 23:08:59  fplanque
 * doc cleanup
 *
 * Revision 1.14  2006/03/10 21:08:26  fplanque
 * Cleaned up post browsing a little bit..
 *
 * Revision 1.13  2006/03/10 17:18:59  blueyed
 * doc
 *
 * Revision 1.12  2006/03/09 22:29:59  fplanque
 * cleaned up permanent urls
 *
 * Revision 1.11  2006/03/09 21:58:52  fplanque
 * cleaned up permalinks
 *
 * Revision 1.10  2006/03/09 15:23:27  fplanque
 * fixed broken images
 *
 * Revision 1.9  2006/03/07 19:13:31  fplanque
 * isset() is more compact and more readable
 *
 * Revision 1.8  2006/03/06 21:14:49  blueyed
 * Fixed incrementing view.
 *
 * Revision 1.7  2006/03/06 20:03:40  fplanque
 * comments
 *
 * Revision 1.6  2006/03/02 19:57:53  blueyed
 * Added DisplayIpAddress() and fixed/finished DisplayItemAllFormats()
 *
 * Revision 1.5  2006/03/01 01:07:43  blueyed
 * Plugin(s) polishing
 *
 * Revision 1.4  2006/02/27 20:55:50  blueyed
 * JS help links fixed
 *
 * Revision 1.2  2006/02/24 19:17:52  blueyed
 * Only increment view count if current User is not the Author.
 *
 * Revision 1.1  2006/02/23 21:11:58  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.100  2006/02/11 21:50:07  fplanque
 * doc
 *
 * Revision 1.99  2006/02/10 22:08:07  fplanque
 * Various small fixes
 *
 * Revision 1.98  2006/02/10 22:05:07  fplanque
 * Normalized itm links
 *
 * Revision 1.97  2006/02/06 20:05:30  fplanque
 * minor
 *
 * Revision 1.95  2006/02/05 00:54:12  blueyed
 * increment_viewcount(), doc
 *
 * Revision 1.94  2006/02/03 21:58:05  fplanque
 * Too many merges, too little time. I can hardly keep up. I'll try to check/debug/fine tune next week...
 *
 * Revision 1.93  2006/02/03 17:35:17  blueyed
 * post_renderers as TEXT
 *
 * Revision 1.91  2006/01/29 20:36:35  blueyed
 * Renamed Item::getBlog() to Item::get_Blog()
 *
 * Revision 1.90  2006/01/26 23:08:35  blueyed
 * Plugins enhanced.
 *
 * Revision 1.89  2006/01/26 20:09:58  blueyed
 * Fix for comments visibility. Thanks to jbettis (http://forums.b2evolution.net/viewtopic.php?p=32435)
 *
 * Revision 1.87  2006/01/16 00:45:19  blueyed
 * Item::content() extra check for "$disppage < 1".
 *
 * Revision 1.86  2006/01/15 17:59:23  blueyed
 * API break of Item::url_link(). See http://dev.b2evolution.net/todo.php/2005/12/09/api_break_params_to_item_url_link_change
 *
 * Revision 1.85  2006/01/10 20:59:49  fplanque
 * minor / fixed internal sync issues @ progidistri
 *
 * Revision 1.83  2006/01/06 18:58:08  blueyed
 * Renamed Plugin::apply_when to $apply_rendering; added T_plugins.plug_apply_rendering and use it to find Plugins which should apply for rendering in Plugins::validate_list().
 */
?>