<?php
/**
 * This file implements the Item class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
load_class('items/model/_itemlight.class.php');
load_funcs('items/model/_item.funcs.php');


/**
 * Item Class
 *
 * @package evocore
 */
class Item extends ItemLight
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
	 * @deprecated by {@link $creator_User}
	 * @var User
	 */
	var $Author;


	/**
	 * ID of the user that created the item
	 * @var integer
	 */
	var $creator_user_ID;


	/**
	 * The assigned User to the item.
	 * Can be NULL
	 * @see Item::get_assigned_User()
	 * @see Item::assign_to()
	 *
	 * @var User
	 * @access protected
	 */
	var $assigned_User;

	/**
	 * ID of the user that created the item
	 * Can be NULL
	 *
	 * @var integer
	 */
	var $assigned_user_ID;

	/**
	 * The visibility status of the item.
	 *
	 * 'published', 'deprecated', 'protected', 'private' or 'draft'
	 *
	 * @var string
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

	var $content;

	var $titletag;

	/**
	 * Lazy filled, use split_page()
	 */
	var $content_pages = NULL;


	var $wordcount;
	/**
	 * The list of renderers, imploded by '.'.
	 * @var string
	 * @access protected
	 */
	var $renderers;
	/**
	 * Comments status
	 *
	 * "open", "disabled" or "closed
	 *
	 * @var string
	 */
	var $comment_status;

	var $pst_ID;
	var $datedeadline = '';
	var $priority;

	/**
	 * @var float
	 */
	var $order;
	/**
	 * @var boolean
	 */
	var $featured;

	var $double1;
	var $double2;
	var $double3;
	var $double4;
	var $double5;
	var $varchar1;
	var $varchar2;
	var $varchar3;

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
	 * array of IDs or NULL if we don't know...
	 *
	 * @var array
	 */
	var $extra_cat_IDs = NULL;

	/**
	 * Array of tags (strings)
	 *
	 * Lazy loaded.
	 *
	 * @var array
	 */
	var $tags = NULL;

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
	function Item( $db_row = NULL, $dbtable = 'T_items__item', $dbprefix = 'post_', $dbIDname = 'post_ID', $objtype = 'Item',
	               $datecreated_field = 'datecreated', $datemodified_field = 'datemodified',
	               $creator_field = 'creator_user_ID', $lasteditor_field = 'lastedit_user_ID' )
	{
		global $localtimenow, $default_locale, $current_User;

		$this->priorities = array(
				1 => /* TRANS: Priority name */ T_('1 - Highest'),
				2 => /* TRANS: Priority name */ T_('2 - High'),
				3 => /* TRANS: Priority name */ T_('3 - Medium'),
				4 => /* TRANS: Priority name */ T_('4 - Low'),
				5 => /* TRANS: Priority name */ T_('5 - Lowest'),
			);

		// Call parent constructor:
		parent::ItemLight( $db_row, $dbtable, $dbprefix, $dbIDname, $objtype,
	               $datecreated_field, $datemodified_field,
	               $creator_field, $lasteditor_field );

		if( is_null($db_row) )
		{ // New item:
			if( isset($current_User) )
			{ // use current user as default, if available (which won't be the case during install)
				$this->set_creator_User( $current_User );
			}
			$this->set( 'notifications_status', 'noreq' );
			// Set the renderer list to 'default' will trigger all 'opt-out' renderers:
			$this->set( 'renderers', array('default') );
			$this->set( 'status', 'published' );
			$this->set( 'locale', $default_locale );
			$this->set( 'priority', 3 );
			$this->set( 'ptyp_ID', 1 /* Post */ );
		}
		else
		{
			$this->datecreated = $db_row->post_datecreated; // Needed for history display
			$this->creator_user_ID = $db_row->post_creator_user_ID; // Needed for history display
			$this->lastedit_user_ID = $db_row->post_lastedit_user_ID; // Needed for history display
			$this->assigned_user_ID = $db_row->post_assigned_user_ID;
			$this->status = $db_row->post_status;
			$this->content = $db_row->post_content;
			$this->titletag = $db_row->post_titletag;
			$this->pst_ID = $db_row->post_pst_ID;
			$this->datedeadline = $db_row->post_datedeadline;
			$this->priority = $db_row->post_priority;
			$this->locale = $db_row->post_locale;
			$this->wordcount = $db_row->post_wordcount;
			$this->notifications_status = $db_row->post_notifications_status;
			$this->notifications_ctsk_ID = $db_row->post_notifications_ctsk_ID;
			$this->comment_status = $db_row->post_comment_status;			// Comments status
			$this->order = $db_row->post_order;
			$this->featured = $db_row->post_featured;
			for( $i = 1 ; $i <= 5; $i++ )
			{
				$this->{'double'.$i} = $db_row->{'post_double'.$i};
			}
			for( $i = 1 ; $i <= 3; $i++ )
			{
				$this->{'varchar'.$i} = $db_row->{'post_varchar'.$i};
			}

			// echo 'renderers=', $db_row->post_renderers;
			$this->renderers = $db_row->post_renderers;

			$this->views = $db_row->post_views;
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
	function author( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'       => ' ',
				'after'        => ' ',
				'format'       => 'htmlbody',
				'link_to'		   => 'userpage',
				'link_text'    => 'preferredname',
				'link_rel'     => '',
				'link_class'   => '',
				'thumb_size'   => 'crop-32x32',
				'thumb_class'  => '',
			), $params );

		// Load User
		$this->get_creator_User();

		if( $params['link_text'] == 'avatar' )
		{
			$r = $this->creator_User->get_avatar_imgtag( $params['thumb_size'], $params['thumb_class'] );

		}
		else
		{
			$r = $this->creator_User->dget( 'preferredname', $params['format'] );
		}

		if( $params['link_to'] == 'userpage' )
		{
			$url = $this->creator_User->get_userpage_url( NULL );
		}
		elseif( $params['link_to'] == 'userurl' )
		{
			$url = $this->creator_User->url;
		}

		if( !empty($url) )
		{
			$link = '<a href="'.$url.'"';
			if( !empty($params['link_rel']) )
			{
				$link .= ' rel="'.$params['link_rel'].'"';
			}
			if( !empty($params['link_class']) )
			{
				$link .= ' class="'.$params['link_class'].'"';
			}
			$r = $link.'>'.$r.'</a>';
		}

		echo $params['before'].$r.$params['after'];
	}


	/**
	 * Load data from Request form fields.
	 *
	 * This requires the blog (e.g. {@link $blog_ID} or {@link $main_cat_ID} to be set).
	 *
	 * @param boolean true if we are returning to edit mode (new, switchtab...)
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request( $editing = false )
	{
		global $default_locale, $current_User, $localtimenow, $set_issue_date;
		global $posttypes_reserved_IDs, $item_typ_ID;

		if( param( 'post_locale', 'string', NULL ) !== NULL )
		{
			$this->set_from_Request( 'locale' );
		}

		if( param( 'item_typ_ID', 'integer', NULL ) !== NULL )
		{
			$this->set_from_Request( 'ptyp_ID', 'item_typ_ID' );

			if ( in_array( $item_typ_ID, $posttypes_reserved_IDs ) )
			{
				param_error( 'item_typ_ID', T_( 'This post type is reserved and cannot be used. Please choose another one.' ), '' );
			}
		}

		if( param( 'post_url', 'string', NULL ) !== NULL )
		{
			param_check_url( 'post_url', 'posting', '' );
			$this->set_from_Request( 'url' );
		}
		// Note: post_url is not part of the simple form, so this message can be a little bit awkward there
		if( $this->status == 'redirected' && empty($this->url) )
		{
			param_error( 'post_url', T_('If you want to redirect this post, you must specify an URL! (Expert mode)') );
		}

		if( $current_User->check_perm( 'edit_timestamp' ) )
		{
			$set_issue_date = param( 'set_issue_date', 'string', '' );

			// pre_dump( $set_issue_date, $editing );

			if( $editing || $set_issue_date == 'set' )
			{ // We can use user date:
				param_date( 'item_issue_date', T_('Please enter a valid issue date.'), true );
				if( strlen(get_param('item_issue_date')) )
				{ // only set it, if a date was given:
					param_time( 'item_issue_time' );
					$this->set( 'issue_date', form_date( get_param( 'item_issue_date' ), get_param( 'item_issue_time' ) ) ); // TODO: cleanup...
				}
			}
			elseif( $set_issue_date == 'now' )
			{	// Set date to NOW:
				$this->set( 'issue_date', date('Y-m-d H:i:s', $localtimenow) );
			}
		}

		if( param( 'post_excerpt', 'string', NULL ) !== NULL ) {
			$this->set_from_Request( 'excerpt' );
		}

		if( param( 'post_urltitle', 'string', NULL ) !== NULL ) {
			$this->set_from_Request( 'urltitle' );
		}

		if( param( 'titletag', 'string', NULL ) !== NULL ) {
			$this->set_from_Request( 'titletag', 'titletag' );
		}

		if( param( 'item_tags', 'string', NULL ) !== NULL ) {
			$this->set_tags_from_string( get_param('item_tags') );
			// pre_dump( $this->tags );
		}

		// Workflow stuff:
		if( param( 'item_st_ID', 'integer', NULL ) !== NULL ) {
			$this->set_from_Request( 'pst_ID', 'item_st_ID' );
		}

		if( param( 'item_assigned_user_ID', 'integer', NULL ) !== NULL ) {
			$this->assign_to( get_param('item_assigned_user_ID') );
		}

		if( param( 'item_priority', 'integer', NULL ) !== NULL )
		{
			$this->set_from_Request( 'priority', 'item_priority', true );
		}

		$this->set( 'featured', param( 'item_featured', 'integer', 0 ), false );

		param( 'item_order', 'double', NULL );
		$this->set_from_Request( 'order', 'item_order', true );

		// CUSTOM FIELDS double
		for( $i = 1 ; $i <= 5; $i++ )
		{	// For each custom double field:
			if( param( 'item_double'.$i, 'double', NULL ) !== NULL )
			{
				$this->set_from_Request( 'double'.$i, 'item_double'.$i, true );
			}
		}

		// CUSTOM FIELDS varchar
		for( $i = 1 ; $i <= 3; $i++ )
		{	// For each custom double field:
			if( param( 'item_varchar'.$i, 'string', NULL ) !== NULL )
			{
				$this->set_from_Request( 'varchar'.$i, 'item_varchar'.$i, true );
			}
		}

		if( param_date( 'item_deadline', T_('Please enter a valid deadline.'), false, NULL ) !== NULL ) {
			$this->set_from_Request( 'datedeadline', 'item_deadline', true );
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

		if( param( 'renderers_displayed', 'integer', 0 ) )
		{ // use "renderers" value only if it has been displayed (may be empty)
			$Plugins_admin = & get_Cache('Plugins_admin');
			$renderers = $Plugins_admin->validate_renderer_list( param( 'renderers', 'array', array() ) );
			$this->set( 'renderers', $renderers );
		}
		else
		{
			$renderers = $this->get_renderers_validated();
		}

		if( param( 'content', 'html', NULL ) !== NULL )
		{
			param( 'post_title', 'html', NULL );

			// Do some optional filtering on the content
			// Typically stuff that will help the content to validate
			// Useful for code display.
			// Will probably be used for validation also.
			$Plugins_admin = & get_Cache('Plugins_admin');
			$Plugins_admin->filter_contents( $GLOBALS['post_title'] /* by ref */, $GLOBALS['content'] /* by ref */, $renderers );


			// Title handling:
			$this->get_Blog();
			$require_title = $this->Blog->get_setting('require_title');

			if( ! $editing && $require_title == 'required' )
			{
				param_check_not_empty( 'post_title', T_('Please provide a title.'), '' );
			}

			// Format raw HTML input to cleaned up and validated HTML:
			param_check_html( 'post_title', T_('Invalid title.'), '' );
			$this->set( 'title', get_param( 'post_title' ) );

			param_check_html( 'content', T_('Invalid content.') );
			$this->set( 'content', get_param( 'content' ) );
		}

		return ! param_errors_detected();
	}


	/**
	 * Template function: display anchor for permalinks to refer to.
	 */
	function anchor()
	{
		global $Settings;

		echo '<a id="'.$this->get_anchor_id().'"></a>';
	}


	/**
	 * @return string
	 */
	function get_anchor_id()
	{
		// In case you have old cafelog permalinks, uncomment the following line:
		// return preg_replace( '/[^a-zA-Z0-9_\.-]/', '_', $this->title );

		return 'item_'.$this->ID;
	}


	/**
	 * Template tag
	 */
	function anchor_id()
	{
		echo $this->get_anchor_id();
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
		$UserCache = & get_Cache( 'UserCache' );
		return $UserCache->get_blog_member_option_list( $this->get_blog_ID(), $this->assigned_user_ID,
							true,	($this->ID != 0) /* if this Item is already serialized we'll load the default anyway */ );
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
				|| $this->is_intro() // Intros: no comments
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

		$this->get_Blog();

		if( $this->Blog->allowcomments == 'never')
		{
			return false;
		}

		if( $this->Blog->allowcomments == 'always')
		{
			return true;
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

		if( ($this->status == 'draft') || ($this->status == 'deprecated' ) || ($this->status == 'redirected' ) )
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

		return true; // OK, user can comment!
	}


	/**
	 * Template function: Check if user can can rate this post
	 *
	 * @return boolean true if user can post, false if s/he cannot
	 */
	function can_rate()
	{
		$this->get_Blog();

		if( $this->Blog->get_setting('allow_rating') == 'never' )
		{
			return false;
		}

		return true; // OK, user can rate!
	}


	/**
	 * Get the prerendered content. If it has not been generated yet, it will.
	 *
	 * NOTE: This calls {@link Item::dbupdate()}, if renderers get changed (from Plugin hook).
	 *
	 * @param string Format, see {@link format_to_output()}.
	 *        Only "htmlbody", "entityencoded", "xml" and "text" get cached.
	 * @return string
	 */
	function get_prerendered_content( $format )
	{
		global $Plugins;

		$r = null;

		$post_renderers = $this->get_renderers_validated();
		$cache_key = $format.'/'.implode('.', $post_renderers); // logic gets used below, for setting cache, too.

		$use_cache = $this->ID && in_array( $format, array('htmlbody', 'entityencoded', 'xml', 'text') );

		// $use_cache = false;

		if( $use_cache )
		{ // the format/item can be cached:
			$ItemPrerenderingCache = & get_Cache('ItemPrerenderingCache');

			if( isset($ItemPrerenderingCache[$format][$this->ID][$cache_key]) )
			{ // already in PHP cache.
				$r = $ItemPrerenderingCache[$format][$this->ID][$cache_key];
				// Save memory, typically only accessed once.
				unset($ItemPrerenderingCache[$format][$this->ID][$cache_key]);
			}
			else
			{	// Try loading from DB cache, including all items in MainList/ItemList.
				global $DB;

				if( ! isset($ItemPrerenderingCache[$format]) )
				{ // only do the prefetch loading once.
					$prefetch_IDs = $this->get_prefetch_itemlist_IDs();

					// Load prerendered content for all items in MainList/ItemList.
					// We load the current $format only, since it's most likely that only one gets used.
					$ItemPrerenderingCache[$format] = array();

					$rows = $DB->get_results( "
						SELECT itpr_itm_ID, itpr_format, itpr_renderers, itpr_content_prerendered
							FROM T_items__prerendering
						 WHERE itpr_itm_ID IN (".implode(',', $prefetch_IDs).")
							 AND itpr_format = '".$format."'",
							 OBJECT, 'Preload prerendered item content for MainList/ItemList ('.$format.')' );
					foreach($rows as $row)
					{
						$row_cache_key = $row->itpr_format.'/'.$row->itpr_renderers;

						if( ! isset($ItemPrerenderingCache[$format][$row->itpr_itm_ID]) )
						{ // init list
							$ItemPrerenderingCache[$format][$row->itpr_itm_ID] = array();
						}

						$ItemPrerenderingCache[$format][$row->itpr_itm_ID][$row_cache_key] = $row->itpr_content_prerendered;
					}

					// Set the value for current Item.
					if( isset($ItemPrerenderingCache[$format][$this->ID][$cache_key]) )
					{
						$r = $ItemPrerenderingCache[$format][$this->ID][$cache_key];
						// Save memory, typically only accessed once.
						unset($ItemPrerenderingCache[$format][$this->ID][$cache_key]);
					}
				}
				else
				{ // This item has not been fetched by the initial prefetch query; only get this item.
					// dh> This is quite unlikely to happen, but you never know.
					// This gets not added to ItemPrerenderingCache, since it would only waste
					// memory - an item gets typically only accessed once per page, and even if
					// it would get accessed more often, there is a cache higher in the chain
					// ($this->content_pages).
					$cache = $DB->get_var( "
						SELECT itpr_content_prerendered
							FROM T_items__prerendering
						 WHERE itpr_itm_ID = ".$this->ID."
							 AND itpr_format = '".$format."'
							 AND itpr_renderers = '".implode('.', $post_renderers)."'", 0, 0, 'Check prerendered item content' );
					if( $cache !== NULL ) // may be empty string
					{ // Retrieved from cache:
						// echo ' retrieved from prerendered cache';
						$r = $cache;
					}
				}
			}
		}

		if( ! isset( $r ) )
		{	// Not cached yet:
			global $Debuglog;

			if( $this->update_renderers_from_Plugins() )
			{
				$post_renderers = $this->get_renderers_validated(); // might have changed from call above
				$cache_key = $format.'/'.implode('.', $post_renderers);

				// Save new renderers with item:
				$this->dbupdate();
			}

			// Call RENDERER plugins:
			// pre_dump( $this->content );
			$r = $this->content;
			$Plugins->render( $r /* by ref */, $post_renderers, $format, array( 'Item' => $this ), 'Render' );
			// pre_dump( $r );

			$Debuglog->add( 'Generated pre-rendered content ['.$cache_key.'] for item #'.$this->ID, 'items' );

			if( $use_cache )
			{ // save into DB (using REPLACE INTO because it may have been pre-rendered by another thread since the SELECT above)
				$DB->query( "
					REPLACE INTO T_items__prerendering (itpr_itm_ID, itpr_format, itpr_renderers, itpr_content_prerendered)
					 VALUES ( ".$this->ID.", '".$format."', ".$DB->quote(implode('.', $post_renderers)).', '.$DB->quote($r).' )', 'Cache prerendered item content' );
			}
		}

		return $r;
	}


	/**
	 * Unset any prerendered content for this item (in PHP cache).
	 */
	function delete_prerendered_content()
	{
		global $DB;

		// Delete DB rows.
		$DB->query( 'DELETE FROM T_items__prerendering WHERE itpr_itm_ID = '.$this->ID );

		// Delete cache.
		$ItemPrerenderingCache = & get_Cache('ItemPrerenderingCache');
		foreach( array_keys($ItemPrerenderingCache) as $format )
		{
			unset($ItemPrerenderingCache[$format][$this->ID]);
		}

		// Delete derived properties.
		unset($this->content_pages);
	}


	/**
	 * Trigger {@link Plugin::ItemApplyAsRenderer()} event and adjust renderers according
	 * to return value.
	 * @return boolean True if renderers got changed.
	 */
	function update_renderers_from_Plugins()
	{
		global $Plugins;

		$r = false;

		foreach( $Plugins->get_list_by_event('ItemApplyAsRenderer') as $Plugin )
		{
			if( empty($Plugin->code) )
				continue;

			$plugin_r = $Plugin->ItemApplyAsRenderer( $tmp_params = array('Item' => & $this) );

			if( is_bool($plugin_r) )
			{
				if( $plugin_r )
				{
					$r = $this->add_renderer( $Plugin->code ) || $r;
				}
				else
				{
					$r = $this->remove_renderer( $Plugin->code ) || $r;
				}
			}
		}

		return $r;
	}


	/**
	 * Make sure, the pages have been obtained (and split up_ from prerendered cache.
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
			// echo ' Pages:'.$this->pages;
		}
	}


	/**
	 * Get a specific page to display (from the prerendered cache)
	 *
	 * @param integer Page number
	 * @param string Format, used to retrieve the matching cache; see {@link format_to_output()}
	 */
	function get_content_page( $page, $format = 'htmlbody' )
	{
		// Make sure, the pages are split up:
		$this->split_pages( $format );

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
	 * This is like a teaser with no HTML and a cropping.
	 *
	 * @todo fp> allow user to submit his own excerpt in expert editing mode
	 */
	function get_content_excerpt( $crop_at = 200 )
	{
		// Get teaser for page 1:
		// fp> Note: I'm not sure about using 'text' here, but there should definitely be no rendering here.
		$output = $this->get_content_teaser( 1, false, 'text' );

		// Get rid of all HTML:
		$output = strip_tags( $output );

		// Ger rid of all new lines:
		$output = trim( str_replace( array( "\r", "\n", "\t" ), array( ' ', ' ', ' ' ), $output ) );

		if( strlen( $output ) > $crop_at )
		{
			$output = substr( $output, 0, $crop_at ).'...';
		}

		return $output;
	}


	/**
	 * Display content teaser of item (will stop at "<!-- more -->"
	 */
	function content_teaser( $params )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => '',
				'after'       => '',
				'disppage'    => '#',
				'stripteaser' => '#',
				'format'      => 'htmlbody',
			), $params );

		$r = $this->get_content_teaser( $params['disppage'], $params['stripteaser'], $params['format'] );

		if( !empty($r) )
		{
			echo $params['before'];
			echo $r;
			echo $params['after'];
		}
	}

	/**
	 * Template function: get content teaser of item (will stop at "<!-- more -->"
	 *
	 * @param mixed page number to display specific page, # for url parameter
	 * @param boolean # if you don't want to repeat teaser after more link was pressed and <-- noteaser --> has been found
	 * @param string filename to use to display more
	 * @return string
	 */
	function get_content_teaser( $disppage = '#', $stripteaser = '#', $format = 'htmlbody' )
	{
		global $Plugins, $preview, $Debuglog;
		global $more;

		// Get requested content page:
		if( $disppage === '#' )
		{ // We want to display the page requested by the user:
			global $page;
			$disppage = $page;
		}

		$content_page = $this->get_content_page( $disppage, $format ); // cannot include format_to_output() because of the magic below.. eg '<!--more-->' will get stripped in "xml"
		// pre_dump($content_page);

		$content_parts = explode( '<!--more-->', $content_page );
		// echo ' Parts:'.count($content_parts);

		if( count($content_parts) > 1 )
		{ // This is an extended post (has a more section):
			if( $stripteaser === '#' )
			{
				// If we're in "more" mode and we want to strip the teaser, we'll strip:
				$stripteaser = ( $more && preg_match('/<!--noteaser-->/', $content_page ) );
			}

			if( $stripteaser )
			{
				return NULL;
			}
		}

		$output = $content_parts[0];

		// Trigger Display plugins FOR THE STUFF THAT WOULD NOT BE PRERENDERED:
		$output = $Plugins->render( $output, $this->get_renderers_validated(), $format, array(
				'Item' => $this,
				'preview' => $preview,
				'dispmore' => ($more != 0),
			), 'Display' );

		// Character conversions
		$output = format_to_output( $output, $format );

		return $output;
	}


	/**
	 * DEPRECATED
	 */
	function content()
	{
		// ---------------------- POST CONTENT INCLUDED HERE ----------------------
		skin_include( '_item_content.inc.php', array(
				'image_size'	=>	'fit-400x320',
			) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.
		// -------------------------- END OF POST CONTENT -------------------------
	}


	/**
	 * Display content teaser of item (will stop at "<!-- more -->"
	 */
	function content_extension( $params )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => '',
				'after'       => '',
				'disppage'    => '#',
				'format'      => 'htmlbody',
				'force_more'  => false,
			), $params );

		$r = $this->get_content_extension( $params['disppage'], $params['force_more'], $params['format'] );

		if( !empty($r) )
		{
			echo $params['before'];
			echo $r;
			echo $params['after'];
		}
	}


	/**
	 * Template function: get content extension of item (part after "<!-- more -->")
	 *
	 * @param mixed page number to display specific page, # for url parameter
	 * @param boolean
	 * @param string filename to use to display more
	 * @return string
	 */
	function get_content_extension( $disppage = '#', $force_more = false, $format = 'htmlbody' )
	{
		global $Plugins, $more, $preview;

		if( ! $more && ! $force_more )
		{	// NOT in more mode:
			return NULL;
		}

		// Get requested content page:
		if( $disppage === '#' )
		{ // We want to display the page requested by the user:
			global $page;
			$disppage = $page;
		}

		$content_page = $this->get_content_page( $disppage, $format ); // cannot include format_to_output() because of the magic below.. eg '<!--more-->' will get stripped in "xml"
		// pre_dump($content_page);

		$content_parts = explode( '<!--more-->', $content_page );
		// echo ' Parts:'.count($content_parts);

		if( count($content_parts) < 2 )
		{ // This is NOT an extended post
			return NULL;
		}

		// Output everything after <!-- more -->
		array_shift($content_parts);
		$output = implode('', $content_parts);

		// Trigger Display plugins FOR THE STUFF THAT WOULD NOT BE PRERENDERED:
		$output = $Plugins->render( $output, $this->get_renderers_validated(), $format, array(
				'Item' => $this,
				'preview' => $preview,
				'dispmore' => true,
			), 'Display' );

		// Character conversions
		$output = format_to_output( $output, $format );

		return $output;
	}


	/**
	 * Increase view counter
	 *
	 * @todo merge with inc_viewcount
	 */
	function count_view( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'allow_multiple_counts_per_page' => false,
			), $params );


		global $Hit, $preview, $Debuglog;

		if( $preview )
		{
			// echo 'PREVIEW';
			return false;
		}

		/*
		 * Check if we want to increment view count, see {@link Hit::is_new_view()}
		 */
		if( ! $Hit->is_new_view() )
		{	// This is a reload
			// echo 'RELOAD';
			return false;
		}

		if( ! $params['allow_multiple_counts_per_page'] )
		{	// Check that we don't increase multiple viewcounts on the same page
			// This make the assumption that the first post in a list is "viewed" and the other are not (necesarily)
			global $view_counts_on_this_page;
			if( $view_counts_on_this_page >= 1 )
			{	// we already had a count on this page
				// echo 'ALREADY HAD A COUNT';
				return false;
			}
			$view_counts_on_this_page++;
		}

		//echo 'COUNTING VIEW';

		// Increment view counter (only if current User is not the item's author)
		return $this->inc_viewcount(); // won't increment if current_User == Author
	}


	/**
	 * Display custom field
	 */
	function custom( $params )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'        => ' ',
				'after'         => ' ',
				'format'        => 'htmlbody',
				'decimals'      => 2,
				'dec_point'     => '.',
				'thousands_sep' => ',',
			), $params );

		if( empty( $params['field'] ) )
		{
			return;
		}

		$r = $this->{$params['field']};

		if( !empty( $params['max'] ) && substr($params['field'],0,6) == 'double' && $r == 9999999999 )
		{
			echo $params['max'];
		}
		elseif( !empty($r) )
		{
			echo $params['before'];
			if( substr( $params['field'], 0, 6 ) == 'double' )
			{
				echo number_format( $r, $params['decimals'], $params['dec_point'], $params['thousands_sep']  );
			}
			else
			{
				echo format_to_output( $r, $params['format'] );
			}
			echo $params['after'];
		}
	}


	/**
	 * Template tag
	 */
	function more_link( $params = array() )
	{
		echo $this->get_more_link( $params );
	}


	/**
	 * Display more link
	 */
	function get_more_link( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'force_more'  => false,
				'before'      => '<p class="bMore">',
				'after'       => '</p>',
				'link_text'   => '#',		// text to display as the more link
				'anchor_text' => '#',		// text to display as the more anchor (once the more link has been clicked)
				'disppage'    => '#',		// page number to display specific page, # for url parameter
				'format'      => 'htmlbody',
			), $params );

		global $more;

		// Get requested content page:
		if( $params['disppage'] === '#' )
		{ // We want to display the page requested by the user:
			global $page;
			$params['disppage'] = $page;
		}

		$content_page = $this->get_content_page( $params['disppage'], $params['format'] ); // cannot include format_to_output() because of the magic below.. eg '<!--more-->' will get stripped in "xml"
		// pre_dump($content_page);

		$content_parts = explode( '<!--more-->', $content_page );
		// echo ' Parts:'.count($content_parts);

		if( count($content_parts) < 2 )
		{ // This is NOT an extended post:
			return '';
		}

		if( ! $more && ! $params['force_more'] )
		{	// We're NOT in "more" mode:
			if( $params['link_text'] == '#' )
			{ // TRANS: this is the default text for the extended post "more" link
				$params['link_text'] = T_('Read more').' &raquo;';
			}

			return format_to_output( $params['before']
						.'<a href="'.$this->get_permanent_url().'#more'.$this->ID.'">'
						.$params['link_text'].'</a>'
						.$params['after'], $params['format'] );
		}
		elseif( ! preg_match('/<!--noteaser-->/', $content_page ) )
		{	// We are in mode mode and we're not hiding the teaser:
			// (if we're higin the teaser we display this as a normal page ie: no anchor)
			if( $params['anchor_text'] == '#' )
			{ // TRANS: this is the default text displayed once the more link has been activated
				$params['anchor_text'] = '<p class="bMore">'.T_('Follow up:').'</p>';
			}

			return format_to_output( '<a id="more'.$this->ID.'" name="more'.$this->ID.'"></a>'
							.$params['anchor_text'], $params['format'] );
		}
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
			echo mysql2date( locale_datefmt(), $this->datedeadline, $useGM);
		else
			echo mysql2date( $format, $this->datedeadline, $useGM);
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
			echo mysql2date( locale_timefmt(), $this->datedeadline, $useGM );
		else
			echo mysql2date( $format, $this->datedeadline, $useGM );
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
	 * @todo dh> gets not used anywhere?! and is the only user of LinkCache::get_by_item_ID().
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
			$ItemTagsCache = & get_Cache('ItemTagsCache');

			if( empty($ItemTagsCache) )
			{
				foreach( $DB->get_results('
					SELECT itag_itm_ID, tag_name
						FROM T_items__itemtag INNER JOIN T_items__tag ON itag_tag_ID = tag_ID
					 WHERE itag_itm_ID IN ('.$DB->quote($this->get_prefetch_itemlist_IDs()).')
					 ORDER BY tag_name', OBJECT, 'Get tags for items' ) as $row )
				{
					$ItemTagsCache[$row->itag_itm_ID][] = $row->tag_name;
				}
			}

			$this->tags = isset($ItemTagsCache[$this->ID]) ? $ItemTagsCache[$this->ID] : array();
		}

		return $this->tags;
	}


	/**
	 * Get the title for the <title> tag
	 *
	 * If it's not specifically entered, use the regular post title instead
	 */
	function get_titletag()
	{
		if( empty($this->titletag) )
		{
			return $this->title;
		}

		return $this->titletag;
	}


	/**
	 * Split tags by space or comma
	 *
	 * @todo fp> allow tags with spaces when quoted like "long tag". Nota comma should never be allowed in a tag.
 	 *
 	 * @param string
	 */
	function set_tags_from_string( $tags )
	{
		if( empty($tags) )
		{
			$this->tags = array();
		}
		else
		{
			$this->tags = preg_split( '/[;,]+/', $tags );
		}

		if( function_exists( 'mb_strtolower' ) && function_exists( 'mb_detect_encoding' ) )
		{	// fp> TODO: instead of those "when used" ifs, it would make more sense to redefine mb_strtolower beforehand if it doesn"t exist (it would then just be a fallback to the strtolower + a Debuglog->add() )
			array_walk( $this->tags, create_function( '& $tag', '$tag = mb_strtolower(trim($tag), mb_detect_encoding($tag));' ) );
		}
		else
		{
			array_walk( $this->tags, create_function( '& $tag', '$tag = strtolower(trim($tag));' ) );
		}
		$this->tags = array_unique( $this->tags );
		$this->tags = array_diff( $this->tags, array('') );	// empty element can sneak in when ending with ,,
		// pre_dump( $this->tags );
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
	function msgform_link( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => ' ',
				'after'       => ' ',
				'text'        => '#',
				'title'       => '#',
				'class'       => '',
				'format'      => 'htmlbody',
				'form_url'    => '#current_blog#',
			), $params );


		if( $params['form_url'] == '#current_blog#' )
		{	// Get
			global $Blog;
			$params['form_url'] = $Blog->get('msgformurl');
		}

		$this->get_creator_User();
		$params['form_url'] = $this->creator_User->get_msgform_url( url_add_param( $params['form_url'], 'post_id='.$this->ID ) );

		if( empty( $params['form_url'] ) )
		{
			return false;
		}

		if( $params['title'] == '#' ) $params['title'] = T_('Send email to post author');
		if( $params['text'] == '#' ) $params['text'] = get_icon( 'email', 'imgtag', array( 'class' => 'middle', 'title' => $params['title'] ) );

		echo $params['before'];
		echo '<a href="'.$params['form_url'].'" title="'.$params['title'].'"';
		if( !empty( $params['class'] ) ) echo ' class="'.$params['class'].'"';
		echo ' rel="nofollow">'.$params['text'].'</a>';
		echo $params['after'];

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
		echo ' rel="nofollow">'.$text.'</a>';
		echo $after;

		return true;
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
			$url = $this->get_permanent_url( '', '', '&amp;' );
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
	 * Display the images linked to the current Item
	 *
	 * @param array of params
	 * @param string Output format, see {@link format_to_output()}
	 */
	function images( $params = array(), $format = 'htmlbody' )
	{
		echo $this->get_images( $params, $format );
	}


	/**
	 * Get block of images linked to the current Item
	 *
	 * @param array of params
	 * @param string Output format, see {@link format_to_output()}
	 */
	function get_images( $params = array(), $format = 'htmlbody' )
	{
		$params = array_merge( array(
				'before' =>              '<div>',
				'before_image' =>        '<div class="image_block">',
				'before_image_legend' => '<div class="image_legend">',
				'after_image_legend' =>  '</div>',
				'after_image' =>         '</div>',
				'after' =>               '</div>',
				'image_size' =>          'fit-720x500',
				'image_link_to' =>       'original',
				'limit' =>               1000,	// Max # of images displayed
			), $params );

		// Get list of attached files
		$FileList = $this->get_attachment_FileList( $params['limit'] );

		$r = '';
		/**
		 * @var File
		 */
		$File = NULL;
		while( $File = & $FileList->get_next() )
		{
			if( ! $File->exists() )
			{
				global $Debuglog;
				$Debuglog->add(sprintf('File linked to item #%d does not exist (%s)!', $this->ID, $File->get_full_path()), array('error', 'files'));
				continue;
			}
			if( ! $File->is_image() )
			{	// Skip anything that is not an image
				// fp> TODO: maybe this property should be stored in link_ltype_ID
				continue;
			}
			// Generate the IMG tag with all the alt, title and desc if available
			$r .= $File->get_tag( $params['before_image'], $params['before_image_legend'], $params['after_image_legend'], $params['after_image'], $params['image_size'], $params['image_link_to'] );
		}

		if( !empty($r) )
		{
			$r = $params['before'].$r.$params['after'];

			// Character conversions
			$r = format_to_output( $r, $format );
		}

		return $r;
	}


	/**
	 * Get list of attached files
	 *
	 * INNER JOIN on files ensures we only get back file links
	 *
	 * @todo dh> Add prefetching for MainList/ItemList (get_prefetch_itemlist_IDs)
	 *           The $limit param and DataObjectList2 makes this quite difficult
	 *           though. Would save (N-1) queries on a blog list page for N items.
	 *
	 * @return DataObjectList2
	 */
	function get_attachment_FileList( $limit = 1000 )
	{
		load_class('_core/model/dataobjects/_dataobjectlist2.class.php');

		$FileCache = & get_Cache( 'FileCache' );

		$FileList = new DataObjectList2( $FileCache ); // IN FUNC

		$SQL = & new SQL();
		$SQL->SELECT( 'file_ID, file_title, file_root_type, file_root_ID, file_path, file_alt, file_desc' );
		$SQL->FROM( 'T_links INNER JOIN T_files ON link_file_ID = file_ID' );
		$SQL->WHERE( 'link_itm_ID = '.$this->ID );
		$SQL->ORDER_BY( 'link_ID' );
		$SQL->LIMIT( $limit );

		$FileList->sql = $SQL->get();

		$FileList->query( false, false, false );

		return $FileList;
	}


	/**
	 * Template function: Displays link to the feed for comments on this item
	 *
	 * @param string Type of feedback to link to (rss2/atom)
	 * @param string String to display before the link (if comments are to be displayed)
	 * @param string String to display after the link (if comments are to be displayed)
	 * @param string Link title
	 */
	function feedback_feed_link( $skin = '_rss2', $before = '', $after = '', $title='#' )
	{
		if( ! $this->can_see_comments() )
		{	// Comments disabled
			return;
		}

		if( $title == '#' )
		{
			$title = get_icon( 'feed' ).' '.T_('Comment feed for this post');
		}

		$url = $this->get_feedback_feed_url($skin);

		echo $before;
		echo '<a href="'.$url.'">'.format_to_output($title).'</a>';
		echo $after;
	}


	/**
	 * Get URL to display the post comments in an XML feed.
	 *
	 * @param string
	 */
	function get_feedback_feed_url( $skin_folder_name )
	{
		$this->load_Blog();

		return url_add_param( $this->Blog->get_tempskin_url( $skin_folder_name ), 'disp=comments&amp;p='.$this->ID);
	}


	/**
	 * Template function: Displays link to feedback page (under some conditions)
	 *
	 * @param array
	 */
	function feedback_link( $params )
	{
		if( ! $this->can_see_comments() )
		{	// Comments disabled
			return;
		}

		$params = array_merge( array(
									'type' => 'feedbacks',
									'status' => 'published',
									'link_before' => '',
									'link_after' => '',
									'link_text_zero' => '#',
									'link_text_one' => '#',
									'link_text_more' => '#',
									'link_anchor_zero' => '#',
									'link_anchor_one' => '#',
									'link_anchor_more' => '#',
									'link_title' => '#',
									'use_popup' => false,
									'url' => '#',
								), $params );


		// dh> TODO:	Add plugin hook, where a Pingback plugin could hook and provide "pingbacks"
		switch( $params['type'] )
		{
			case 'feedbacks':
				if( $params['link_title'] == '#' ) $params['link_title'] = T_('Display feedback / Leave a comment');
				if( $params['link_text_zero'] == '#' ) $params['link_text_zero'] = T_('Send feedback').' &raquo;';
				if( $params['link_text_one'] == '#' ) $params['link_text_one'] = T_('1 feedback').' &raquo;';
				if( $params['link_text_more'] == '#' ) $params['link_text_more'] = T_('%d feedbacks').' &raquo;';
				break;

			case 'comments':
				if( $params['link_title'] == '#' ) $params['link_title'] = T_('Display comments / Leave a comment');
				if( $params['link_text_zero'] == '#' )
				{
					if( $this->can_comment( NULL ) ) // NULL, because we do not want to display errors here!
					{
						$params['link_text_zero'] = T_('Leave a comment').' &raquo;';
					}
					else
					{
						$params['link_text_zero'] = '';
					}
				}
				if( $params['link_text_one'] == '#' ) $params['link_text_one'] = T_('1 comment').' &raquo;';
				if( $params['link_text_more'] == '#' ) $params['link_text_more'] = T_('%d comments').' &raquo;';
				break;

			case 'trackbacks':
				$this->get_Blog();
				if( ! $this->Blog->get( 'allowtrackbacks' ) )
				{ // Trackbacks not allowed on this blog:
					return;
				}
				if( $params['link_title'] == '#' ) $params['link_title'] = T_('Display trackbacks / Get trackback address for this post');
				if( $params['link_text_zero'] == '#' ) $params['link_text_zero'] = T_('Send a trackback').' &raquo;';
				if( $params['link_text_one'] == '#' ) $params['link_text_one'] = T_('1 trackback').' &raquo;';
				if( $params['link_text_more'] == '#' ) $params['link_text_more'] = T_('%d trackbacks').' &raquo;';
				break;

			case 'pingbacks':
				// Obsolete, but left for skin compatibility
				$this->get_Blog();
				if( ! $this->Blog->get( 'allowtrackbacks' ) )
				{ // Trackbacks not allowed on this blog:
					// We'll consider pingbacks to follow the same restriction
					return;
				}
				if( $params['link_title'] == '#' ) $params['link_title'] = T_('Display pingbacks');
				if( $params['link_text_zero'] == '#' ) $params['link_text_zero'] = T_('No pingback yet').' &raquo;';
				if( $params['link_text_one'] == '#' ) $params['link_text_one'] = T_('1 pingback').' &raquo;';
				if( $params['link_text_more'] == '#' ) $params['link_text_more'] = T_('%d pingbacks').' &raquo;';
				break;

			default:
				debug_die( "Unknown feedback type [{$params['type']}]" );
		}

		$link_text = $this->get_feedback_title( $params['type'], $params['link_text_zero'], $params['link_text_one'], $params['link_text_more'], $params['status'] );

		if( empty($link_text) )
		{	// No link, no display...
			return false;
		}

		if( $params['url'] == '#' )
		{ // We want a link to single post:
			$params['url'] = $this->get_single_url( 'auto' );
		}

		// Anchor position
		$number = generic_ctp_number( $this->ID, $params['type'], $params['status'] );

		if( $number == 0 )
			$anchor = $params['link_anchor_zero'];
		elseif( $number == 1 )
			$anchor = $params['link_anchor_one'];
		elseif( $number > 1 )
			$anchor = $params['link_anchor_more'];
		if( $anchor == '#' )
		{
			$anchor = '#'.$params['type'];
		}

		echo $params['link_before'];

		if( !empty( $params['url'] ) )
		{
			echo '<a href="'.$params['url'].$anchor.'" ';	// Position on feedback
			echo 'title="'.$params['link_title'].'"';
			if( $params['use_popup'] )
			{	// Special URL if we can open a popup (i-e if JS is enabled):
				$popup_url = url_add_param( $params['url'], 'disp=feedback-popup' );
				echo ' onclick="return pop_up_window( \''.$popup_url.'\', \'evo_comments\' )"';
			}
			echo '>';
			echo $link_text;
			echo '</a>';
		}
		else
		{
			echo $link_text;
		}

		echo $params['link_after'];
	}


	/**
	 * Get text depending on number of comments
	 *
	 * @param string Type of feedback to link to (feedbacks (all)/comments/trackbacks/pingbacks)
	 * @param string Link text to display when there are 0 comments
	 * @param string Link text to display when there is 1 comment
	 * @param string Link text to display when there are >1 comments (include %d for # of comments)
	 * @param string Status of feedbacks to count
	 */
	function get_feedback_title( $type = 'feedbacks',	$zero = '#', $one = '#', $more = '#', $status = 'published' )
	{
		if( ! $this->can_see_comments() )
		{	// Comments disabled
			return NULL;
		}

		// dh> TODO:	Add plugin hook, where a Pingback plugin could hook and provide "pingbacks"
		switch( $type )
		{
			case 'feedbacks':
				if( $zero == '#' ) $zero = '';
				if( $one == '#' ) $one = T_('1 feedback');
				if( $more == '#' ) $more = T_('%d feedbacks');
				break;

			case 'comments':
				if( $zero == '#' ) $zero = '';
				if( $one == '#' ) $one = T_('1 comment');
				if( $more == '#' ) $more = T_('%d comments');
				break;

			case 'trackbacks':
				if( $zero == '#' ) $zero = '';
				if( $one == '#' ) $one = T_('1 trackback');
				if( $more == '#' ) $more = T_('%d trackbacks');
				break;

			case 'pingbacks':
				// Obsolete, but left for skin compatibility
				if( $zero == '#' ) $zero = '';
				if( $one == '#' ) $one = T_('1 pingback');
				if( $more == '#' ) $more = T_('%d pingbacks');
				break;

			default:
				debug_die( "Unknown feedback type [$type]" );
		}

		$number = generic_ctp_number( $this->ID, $type, $status );

		if( $number == 0 )
			return $zero;
		elseif( $number == 1 )
			return $one;
		elseif( $number > 1 )
			return str_replace( '%d', $number, $more );
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
			$zero = '', $one = '#', $more = '#', $edit_comments_link = '#', $params = array() )
	{
		/**
		 * @var User
		 */
		global $current_User;

		/* TODO: finish this...
		$params = array_merge( array(
									'type' => 'feedbacks',
									'block_before' => '',
									'blo_after' => '',
									'link_text_zero' => '#',
									'link_text_one' => '#',
									'link_text_more' => '#',
									'link_title' => '#',
									'use_popup' => false,
									'url' => '#',
									'type' => 'feedbacks',
								), $params );
		*/

		if( isset($current_User) && $current_User->check_perm( 'blog_comments', 'any', false, $this->get_blog_ID() ) )
		{	// We jave permission to edit comments:
			if( $edit_comments_link == '#' )
			{	// Use default link:
				global $admin_url;
				$edit_comments_link = '<a href="'.$admin_url.'?ctrl=items&amp;blog='.$this->get_blog_ID().'&amp;p='.$this->ID.'#comments" title="'.T_('Moderate these feedbacks').'">'.get_icon( 'edit' ).' '.T_('Moderate...').'</a>';
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

		$r = $this->get_feedback_title( $type, $zero, $one, $more, 'draft' );

		if( !empty( $r ) )
		{
			echo $before.$r.$after;
		}
	}



	/**
	 * Template tag: display footer for the current Item.
	 *
	 * @param array
	 * @return boolean true if something has been displayed
	 */
	function footer( $params )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'mode'        => '#',				// Will detect 'single' from $disp automatically
				'block_start' => '<div class="item_footer">',
				'block_end'   => '</div>',
				'format'      => 'htmlbody',
			), $params );

		if( $params['mode'] == '#' )
		{
			global $disp;
			$params['mode'] = $disp;
		}

		// pre_dump( $params['mode'] );

		$this->get_Blog();
		switch( $params['mode'] )
		{
			case 'xml':
				$text = $this->Blog->get_setting( 'xml_item_footer_text' );
				break;

			case 'single':
				$text = $this->Blog->get_setting( 'single_item_footer_text' );
				break;

			default:
				// Do NOT display!
				$text = '';
		}

		$text = preg_replace_callback( '¤\$([a-z_]+)\$¤', array( $this, 'replace_callback' ), $text );

		if( empty($text) )
		{
			return false;
		}

		echo format_to_output( $params['block_start'].$text.$params['block_end'], $params['format'] );

		return true;
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

		if( ! $current_User->check_perm( 'blog_del_post', 'any', false, $this->get_blog_ID() ) )
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
			$actionurl = $admin_url.'?ctrl=items&amp;action=delete&amp;post_ID=';
		}
		$url = $actionurl.$this->ID;

		$r = $before;
		if( $button )
		{ // Display as button
			$r .= '<input type="button"';
			$r .= ' value="'.$text.'" title="'.$title.'" onclick="if ( confirm(\'';
			$r .= TS_('You are about to delete this post!\\nThis cannot be undone!');
			$r .= '\') ) { document.location.href=\''.$url.'\' }"';
			if( !empty( $class ) ) $r .= ' class="'.$class.'"';
			$r .= '/>';
		}
		else
		{ // Display as link
			$r .= '<a href="'.$url.'" title="'.$title.'" onclick="return confirm(\'';
			$r .= TS_('You are about to delete this post!\\nThis cannot be undone!');
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
	 * @param array Params:
	 *  - 'before': to display before link
	 *  - 'after':    to display after link
	 *  - 'text': link text
	 *  - 'title': link title
	 *  - 'class': CSS class name
	 *  - 'save_context': redirect to current URL?
	 */
	function get_edit_link( $params = array() )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in() ) return false;

		if( ! $this->ID )
		{ // preview..
			return false;
		}

		if( ! $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $this ) )
		{ // User has no right to edit this post
			return false;
		}

		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'       => ' ',
				'after'        => ' ',
				'text'         => '#',
				'title'        => '#',
				'class'        => '',
				'save_context' => true,
			), $params );


		if( $params['text'] == '#' ) $params['text'] = get_icon( 'edit' ).' '.T_('Edit...');

		if( $params['title'] == '#' ) $params['title'] = T_('Edit this post...');

		$actionurl = $admin_url.'?ctrl=items&amp;action=edit&amp;p='.$this->ID;
		if( $params['save_context'] )
		{
			$actionurl .= '&amp;redirect_to='.rawurlencode( regenerate_url( '', '', '', '&' ).'#'.$this->get_anchor_id() );
		}


		$r = $params['before'];
		$r .= '<a href="'.$actionurl;
		$r .= '" title="'.$params['title'].'"';
		if( !empty( $params['class'] ) ) $r .= ' class="'.$params['class'].'"';
		$r .=  '>'.$params['text'].'</a>';
		$r .= $params['after'];

		return $r;
	}


	/**
	 * Template tag
	 * @see Item::get_edit_link()
	 */
	function edit_link( $params = array() )
	{
		echo $this->get_edit_link( $params );
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
	function get_publish_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;', $save_context = true )
	{
		global $current_User, $admin_url;

		if( in_array($this->status, array('published', 'redirected')) )
		{
			return false;
		}

		if( ! is_logged_in() ) return false;

		if( ! ($current_User->check_perm( 'item_post!published', 'edit', false, $this ))
			|| ! ($current_User->check_perm( 'edit_timestamp' ) ) )
		{ // User has no right to publish this post now:
			return false;
		}

		if( $text == '#' ) $text = get_icon( 'publish', 'imgtag' ).' '.T_('Publish NOW!');
		if( $title == '#' ) $title = T_('Publish now using current date and time.');

		$r = $before;
		$r .= '<a href="'.$admin_url.'?ctrl=items'.$glue.'action=publish'.$glue.'post_ID='.$this->ID;
		if( $save_context )
		{
			$r .= $glue.'redirect_to='.rawurlencode( regenerate_url( '', '', '', '&' ) );
		}
		$r .= '" title="'.$title.'"';
		if( !empty( $class ) ) $r .= ' class="'.$class.'"';
		$r .= '>'.$text.'</a>';
		$r .= $after;

		return $r;
	}


	function publish_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;', $save_context = true )
	{
		echo $this->get_publish_link( $before, $after, $text, $title, $class, $glue, $save_context );
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

		if( ($this->status == 'deprecated') // Already deprecated!
			|| ! ($current_User->check_perm( 'item_post!deprecated', 'edit', false, $this )) )
		{ // User has no right to deprecated this post:
			return false;
		}

		if( $text == '#' ) $text = get_icon( 'deprecate', 'imgtag' ).' '.T_('Deprecate!');
		if( $title == '#' ) $title = T_('Deprecate this post!');

		$r = $before;
		$r .= '<a href="'.$admin_url.'?ctrl=items'.$glue.'action=deprecate'.$glue.'post_ID='.$this->ID;
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
		$priority = isset($field_value) ? $field_value : $this->priority;

		$r = '';
		if( $allow_none )
		{
			$r = '<option value="">'./* TRANS: "None" select option */T_('No priority').'</option>';
		}

		foreach( $this->priorities as $i => $name )
		{
			$r .= '<option value="'.$i.'"';
			if( $priority == $i )
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

		load_funcs('plugins/_plugin.funcs.php');

		$Plugins->restart(); // make sure iterator is at start position

		$atLeastOneRenderer = false;

		if( is_null($item_renderers) )
		{
			$item_renderers = $this->get_renderers();
		}
		// pre_dump( $item_renderers );

		echo '<input type="hidden" name="renderers_displayed" value="1" />';

		foreach( $Plugins->get_list_by_events( array('RenderItemAsHtml', 'RenderItemAsXml', 'RenderItemAsText') ) as $loop_RendererPlugin )
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
			echo '<a title="'.T_('Configure plugins').'" href="'.$admin_url.'?ctrl=plugins"'.'>'.T_('No renderer plugins are installed.').'</a>';
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
	function status( $params = array() )
	{
		global $post_statuses;

		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => '',
				'after'       => '',
				'format'      => 'htmlbody',
			), $params );

		echo $params['before'];

		if( $params['format'] == 'raw' )
		{
			status_raw();
		}
		else
		{
			echo format_to_output( $this->get('t_status'), $params['format'] );
		}

		echo $params['after'];
	}


	/**
	 * Output raw status.
	 */
	function status_raw()
	{
		echo $this->status;
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
	 * Display tags for Item
	 *
	 * @param array of params
	 * @param string Output format, see {@link format_to_output()}
	 */
	function tags( $params = array() )
	{
		$params = array_merge( array(
				'before' =>           '<div>'.T_('Tags').': ',
				'after' =>            '</div>',
				'separator' =>        ', ',
				'links' =>            true,
			), $params );

		$tags = $this->get_tags();

		if( !empty( $tags ) )
		{
			echo $params['before'];

			if( $links = $params['links'] )
			{
				$this->get_Blog();
			}

			$i = 0;
			foreach( $tags as $tag )
			{
				if( $i++ > 0 )
				{
					echo $params['separator'];
				}
				if( $links )
				{	// We want links
					echo '<a href="'.$this->Blog->gen_tag_url( $tag ).'" rel="tag">';
				}
				echo htmlspecialchars( $tag );
				if( $links )
				{
					echo '</a>';
				}
			}

			echo $params['after'];
		}
	}


	/**
	 * Template function: Displays trackback autodiscovery information
	 *
	 * TODO: build into headers
	 */
	function trackback_rdf()
	{
		$this->get_Blog();
		if( ! $this->Blog->get( 'allowtrackbacks' ) )
		{ // Trackbacks not allowed on this blog:
			return;
		}

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
		$this->title( array(
			'before'    => ' dc:title="',
			'after'     => '"'."\n",
			'link_type' => 'none',
			'format'    => 'xmlattr',
			) );
		echo '  trackback:ping="';
		$this->trackback_url();
		echo '" />'."\n";
		echo '</rdf:RDF>';
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

		// fp> TODO: get a clean (per blog) setting for this
		//	return $htsrv_url.'trackback.php/'.$this->ID;

		return $htsrv_url.'trackback.php?tb_id='.$this->ID;
	}


	/**
	 * Template function: Display link to item related url.
	 *
	 * By default the link is displayed as a link.
	 * Optionally some smart stuff may happen.
	 */
	function url_link( $params = array() )
	{
		global $rsc_url;

		if( empty( $this->url ) )
		{
			return;
		}

		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'        => ' ',
				'after'         => ' ',
				'text_template' => '$url$',		// If evaluates to empty, nothing will be displayed (except player if podcast)
				'url_template'  => '$url$',
				'target'        => '',
				'format'        => 'htmlbody',
				'podcast'       => '#',						// handle as podcast. # means depending on post type
				'before_podplayer' => '<div class="podplayer">',
				'after_podplayer'  => '</div>',
			), $params );

		if( $params['podcast'] == '#' )
		{	// Check if this post is a podcast
			$params['podcast'] = ( $this->ptyp_ID == 2000 );
		}

		if( $params['podcast'] && $params['format'] == 'htmlbody' )
		{	// We want podcast display:

			echo $params['before_podplayer'];

			echo '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="200" height="20" id="dewplayer" align="middle"><param name="wmode" value="transparent"><param name="allowScriptAccess" value="sameDomain" /><param name="movie" value="'.$rsc_url.'swf/dewplayer.swf?mp3='.$this->url.'&amp;showtime=1" /><param name="quality" value="high" /><param name="bgcolor" value="" /><embed src="'.$rsc_url.'swf/dewplayer.swf?mp3='.$this->url.'&amp;showtime=1" quality="high" bgcolor="" width="200" height="20" name="dewplayer" wmode="transparent" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed></object>';

			echo $params['after_podplayer'];

		}
		else
		{ // Not displaying podcast player:

			$text = str_replace( '$url$', $this->url, $params['text_template'] );
			if( empty($text) )
			{	// Nothing to display
				return;
			}

			$r = $params['before'];

			$r .= '<a href="'.str_replace( '$url$', $this->url, $params['url_template'] ).'"';

			if( !empty( $params['target'] ) )
			{
				$r .= ' target="'.$params['target'].'"';
			}

			$r .= '>'.$text.'</a>';

			$r .= $params['after'];

			echo format_to_output( $r, $params['format'] );
		}
	}


	/**
	 * Template function: Display the number of words in the post
	 */
	function wordcount()
	{
		echo (int)$this->wordcount; // may have been saved as NULL until 1.9
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
			case 'pst_ID':
				return $this->set_param( $parname, 'number', $parvalue, true );

			case 'content':
				$r1 = $this->set_param( 'content', 'string', $parvalue, $make_null );
				// Update wordcount as well:
				$r2 = $this->set_param( 'wordcount', 'number', bpost_count_words($this->content), false );
				return ( $r1 || $r2 ); // return true if one changed

			case 'wordcount':
			case 'featured':
				return $this->set_param( $parname, 'number', $parvalue, false );

			case 'datedeadline':
				return $this->set_param( 'datedeadline', 'date', $parvalue, true );

			case 'order':
				return $this->set_param( 'order', 'number', $parvalue, true );

			case 'renderers': // deprecated
				return $this->set_renderers( $parvalue );

			default:
				return parent::set( $parname, $parvalue, $make_null );
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
	 * @deprecated since EVO_NEXT_VERSION. Use set() + dbinsert() instead
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
		$this->set( 'ptyp_ID', $item_typ_ID );
		$this->set( 'pst_ID', $item_st_ID );

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

		// validate url title / slug
		$this->set( 'urltitle', urltitle_validate( $this->urltitle, $this->title, $this->ID, false, $this->dbprefix.'urltitle', $this->dbIDname, $this->dbtablename) );

		$this->update_renderers_from_Plugins();

		// TODO: allow a plugin to cancel update here (by returning false)?
		$Plugins->trigger_event( 'PrependItemInsertTransact', $params = array( 'Item' => & $this ) );

		$dbchanges = $this->dbchanges; // we'll save this for passing it to the plugin hook

		if( $result = parent::dbinsert() )
		{ // We could insert the item object..

			// Let's handle the extracats:
			$this->insert_update_extracats( 'insert' );

			// Let's handle the tags:
			$this->insert_update_tags( 'insert' );

			$DB->commit();

			$Plugins->trigger_event( 'AfterItemInsert', $params = array( 'Item' => & $this, 'dbchanges' => $dbchanges ) );
		}
		else
		{
			$DB->rollback();
		}

		return $result;
	}




	/**
	 * Update the DB based on previously recorded changes
	 *
	 * @return boolean true on success
	 */
	function dbupdate()
	{
		global $DB, $Plugins;

		$DB->begin();

		// validate url title / slug
		if( empty($this->urltitle) || isset($this->dbchanges['post_urltitle']) )
		{ // Url title has changed or is empty
			// echo 'updating url title';
			$this->set( 'urltitle', urltitle_validate( $this->urltitle, $this->title, $this->ID, false, $this->dbprefix.'urltitle', $this->dbIDname, $this->dbtablename ) );
		}

		$this->update_renderers_from_Plugins();

		// TODO: dh> allow a plugin to cancel update here (by returning false)?
		$Plugins->trigger_event( 'PrependItemUpdateTransact', $params = array( 'Item' => & $this ) );

		$dbchanges = $this->dbchanges; // we'll save this for passing it to the plugin hook

		// pre_dump($this->dbchanges);
		// fp> note that dbchanges isn't actually 100% accurate. At this time it does include variables that actually haven't changed.
		if( isset($this->dbchanges['post_status'])
			|| isset($this->dbchanges['post_title'])
			|| isset($this->dbchanges['post_content']) )
		{	// One of the fields we track in the revision history has changed:
			// Save the "current" (soon to be "old") data as a version before overwriting it in parent::dbupdate:
			// fp> TODO: actually, only the fields that have been changed should be copied to the version, the other should be left as NULL
			$sql = 'INSERT INTO T_items__version( iver_itm_ID, iver_edit_user_ID, iver_edit_datetime, iver_status, iver_title, iver_content )
				SELECT post_ID, post_lastedit_user_ID, post_datemodified, post_status, post_title, post_content
					FROM T_items__item
				 WHERE post_ID = '.$this->ID;
			$DB->query( $sql, 'Save a version of the Item' );
		}

		if( $result = parent::dbupdate() )
		{ // We could update the item object..

			// Let's handle the extracats:
			$this->insert_update_extracats( 'update' );

			// Let's handle the extracats:
			$this->insert_update_tags( 'update' );

			$this->delete_prerendered_content();

			$DB->commit();

			$Plugins->trigger_event( 'AfterItemUpdate', $params = array( 'Item' => & $this, 'dbchanges' => $dbchanges ) );
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
	 * @todo fp> delete related stuff: comments, cats, file links...
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
			$this->delete_prerendered_content();

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
	 * Save tags to DB
	 *
	 * @param string 'insert' | 'update'
	 */
	function insert_update_tags( $mode )
	{
		global $DB;

		if( isset( $this->tags ) )
		{ // Okay the tags are defined:

			$DB->begin();

			if( $mode == 'update' )
			{	// delete previous tag associations:
				// Note: actual tags never get deleted
				$DB->query( 'DELETE FROM T_items__itemtag
											WHERE itag_itm_ID = '.$this->ID, 'delete previous tags' );
			}

			if( !empty($this->tags) )
			{
				// Find the tags that are already in the DB
				$query = 'SELECT LOWER( tag_name )
										FROM T_items__tag
									 WHERE tag_name IN ('.$DB->quote($this->tags).')';
				$existing_tags = $DB->get_col( $query, 0, 'Find existing tags' );

				$new_tags = array_diff( $this->tags, $existing_tags );
				//pre_dump($new_tags);

				if( !empty( $new_tags ) )
				{	// insert new tags:
					$query = "INSERT INTO T_items__tag( tag_name ) VALUES ";
					foreach( $new_tags as $tag )
					{
						$query .= '( '.$DB->quote($tag).' ),';
					}
					$query = substr( $query, 0, strlen( $query ) - 1 );
					$DB->query( $query, 'insert new tags' );
				}

				// ASSOC:
				$query = 'INSERT INTO T_items__itemtag( itag_itm_ID, itag_tag_ID )
								  SELECT '.$this->ID.', tag_ID
									  FROM T_items__tag
									 WHERE tag_name IN ('.$DB->quote($this->tags).')';
				$DB->query( $query, 'Make tag associations!' );
			}

			$DB->commit();
		}
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

		$DB->query( 'UPDATE T_items__item
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
	 * Execute or schedule post(=after) processing tasks
	 *
	 * Includes notifications & pings
	 *
	 * @param boolean give more info messages (we want to avoid that when we save & continue editing)
	 */
	function handle_post_processing( $verbose = true )
	{
		global $Settings, $Messages;

		$notifications_mode = $Settings->get('outbound_notifications_mode');

		if( $notifications_mode == 'off' )
		{	// Exit silently
			return false;
		}

		if( $this->notifications_status == 'finished' )
		{ // pings have been done before
			if( $verbose )
			{
				$Messages->add( T_('Post had already pinged: skipping notifications...'), 'note' );
			}
			return false;
		}

		if( $this->notifications_status != 'noreq' )
		{ // pings have been done before

			// TODO: Check if issue_date has changed and reschedule
			if( $verbose )
			{
				$Messages->add( T_('Post processing already pending...'), 'note' );
			}
			return false;
		}

		if( $this->status != 'published' )
		{
			// TODO: discard any notification that may be pending!
			if( $verbose )
			{
				$Messages->add( T_('Post not publicly published: skipping notifications...'), 'note' );
			}
			return false;
		}

		if( $notifications_mode == 'immediate' )
		{	// We want to do the post processing immediately:
			// send outbound pings:
			$this->send_outbound_pings( $verbose );

			// Send email notifications now!
			$this->send_email_notifications( false );

			// Record that processing has been done:
			$this->set( 'notifications_status', 'finished' );
		}
		else
		{	// We want asynchronous post processing:
			$Messages->add( T_('Scheduling asynchronous notifications...'), 'note' );

			// CREATE OBJECT:
			load_class( '/cron/model/_cronjob.class.php' );
			$edited_Cronjob = & new Cronjob();

			// start datetime. We do not want to ping before the post is effectively published:
			$edited_Cronjob->set( 'start_datetime', $this->issue_date );

			// no repeat.

			// name:
			$edited_Cronjob->set( 'name', sprintf( T_('Send notifications for &laquo;%s&raquo;'), strip_tags($this->title) ) );

			// controller:
			$edited_Cronjob->set( 'controller', 'cron/jobs/_post_notifications.job.php' );

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

 		$edited_Blog = & $this->get_Blog();

		if( ! $edited_Blog->get_setting( 'allow_subscriptions' ) )
		{	// Subscriptions not enabled!
			return;
		}

		if( $display )
		{
			echo "<div class=\"panelinfo\">\n";
			echo '<h3>', T_('Notifying subscribed users...'), "</h3>\n";
		}

		// Get list of users who want to be notfied:
		// TODO: also use extra cats/blogs??
		$sql = 'SELECT DISTINCT user_email, user_locale
							FROM T_subscriptions INNER JOIN T_users ON sub_user_ID = user_ID
						WHERE sub_coll_ID = '.$this->get_blog_ID().'
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

		// Send emails:
		$cache_by_locale = array();
		foreach( $notify_array as $notify_email => $notify_locale )
		{
			if( ! isset($cache_by_locale[$notify_locale]) )
			{ // No message for this locale generated yet:
				locale_temp_switch($notify_locale);

				// Calculate length for str_pad to align labels:
				$pad_len = max( strlen(T_('Blog')), strlen(T_('Author')), strlen(T_('Title')), strlen(T_('Url')), strlen(T_('Content')) );

				$cache_by_locale[$notify_locale]['subject'] = sprintf( T_('[%s] New post: "%s"'), $edited_Blog->get('shortname'), $this->get('title') );

				$cache_by_locale[$notify_locale]['message'] =
					str_pad( T_('Blog'), $pad_len ).': '.$edited_Blog->get('shortname')
					.' ( '.str_replace('&amp;', '&', $edited_Blog->gen_blogurl())." )\n"

					.str_pad( T_('Author'), $pad_len ).': '.$this->creator_User->get('preferredname').' ('.$this->creator_User->get('login').")\n"

					.str_pad( T_('Title'), $pad_len ).': '.$this->get('title')."\n"

					// linked URL or "-" if empty:
					.str_pad( T_('Url'), $pad_len ).': '.( empty( $this->url ) ? '-' : str_replace('&amp;', '&', $this->get('url')) )."\n"

					.str_pad( T_('Content'), $pad_len ).': '
						// TODO: We MAY want to force a short URL and avoid it to wrap on a new line in the mail which may prevent people from clicking
						// TODO: might get moved onto a single line, at the end of the content..
						.str_replace('&amp;', '&', $this->get_permanent_url())."\n\n"

					.$this->get('content')."\n"

					// Footer:
					."\n-- \n"
					.T_('Edit/Delete').': '.$admin_url.'?ctrl=items&blog='.$this->get_blog_ID().'&p='.$this->ID."\n\n"

					.T_('Edit your subscriptions/notifications').': '.str_replace('&amp;', '&', url_add_param( $edited_Blog->gen_blogurl(), 'disp=subs' ) )."\n";

				locale_restore_previous();
			}

			if( $display ) echo T_('Notifying:').$notify_email."<br />\n";
			if( $debug >= 2 )
			{
				echo "<p>Sending notification to $notify_email:<pre>$cache_by_locale[$notify_locale]['message']</pre>";
			}

			send_mail( $notify_email, NULL, $cache_by_locale[$notify_locale]['subject'], $cache_by_locale[$notify_locale]['message'],
									$this->creator_User->get('email'), $this->creator_User->get('preferredname') );
		}

		if( $display ) echo '<p>', T_('Done.'), "</p>\n</div>\n";
	}


	/**
	 * Send outbound pings for a post
	 *
	 * @param boolean give more info messages (we want to avoid that when we save & continue editing)
	 */
	function send_outbound_pings( $verbose = true )
	{
		global $Plugins, $baseurl, $Messages, $evonetsrv_host;

		load_funcs('xmlrpc/model/_xmlrpc.funcs.php');

		$this->load_Blog();
		$ping_plugins = array_unique(explode(',', $this->Blog->get_setting('ping_plugins')));

		if( (preg_match( '#^http://localhost[/:]#', $baseurl)
				|| preg_match( '~^\w+://[^/]+\.local/~', $baseurl ) ) /* domain ending in ".local" */
			&& $evonetsrv_host != 'localhost' )	// OK if we are pinging locally anyway ;)
		{
			if( $verbose )
			{
				$Messages->add( T_('Skipping pings (Running on localhost).'), 'note' );
			}
		}
		else foreach( $ping_plugins as $plugin_code )
		{
			$Plugin = & $Plugins->get_by_code($plugin_code);

			if( $Plugin )
			{
				$Messages->add( sprintf(T_('Pinging %s...'), $Plugin->ping_service_name), 'note' );
				$params = array( 'Item' => & $this, 'xmlrpcresp' => NULL, 'display' => false );

				$r = $Plugin->ItemSendPing( $params );

				if( isset($params['xmlrpcresp']) && is_a($params['xmlrpcresp'], 'xmlrpcresp') )
				{
					// dh> TODO: let xmlrpc_displayresult() handle $Messages (e.g. "error", but should be connected/after the "Pinging %s..." from above)
					ob_start();
					xmlrpc_displayresult( $params['xmlrpcresp'], true );
					$Messages->add( ob_get_contents(), 'note' );
					ob_end_clean();
				}
			}
		}
	}


	/**
	 * Callback user for footer()
	 */
	function replace_callback( $matches )
	{
		switch( $matches[1] )
		{
			case 'item_perm_url':
				return $this->get_permanent_url();

			case 'item_title':
				return $this->title;

			default:
				return $matches[1];
		}
	}

	/**
	 * Get a member param by its name
	 *
	 * @param mixed Name of parameter
	 * @return mixed Value of parameter
	 */
	function get( $parname )
	{
		global $post_statuses;

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
				if( ! ($Element = & $ItemStatusCache->get_by_ID( $this->pst_ID, true, false ) ) )
				{ // No status:
					return '';
				}
				return $Element->get_name();

			case 't_type':
				// Item type (name):
				if( empty($this->ptyp_ID) )
				{
					return '';
				}

				$ItemTypeCache = & get_Cache( 'ItemTypeCache' );
				$type_Element = & $ItemTypeCache->get_by_ID( $this->ptyp_ID );
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
	 * @return array List of validated renderer codes
	 */
	function get_renderers_validated()
	{
		if( ! isset($this->renderers_validated) )
		{
			$Plugins_admin = & get_Cache('Plugins_admin');
			$this->renderers_validated = $Plugins_admin->validate_renderer_list( $this->get_renderers() );
		}
		return $this->renderers_validated;
	}


	/**
	 * Add a renderer (by code) to the Item.
	 * @param string Renderer code to add for this item
	 * @return boolean True if renderers have changed
	 */
	function add_renderer( $renderer_code )
	{
		$renderers = $this->get_renderers();
		if( in_array( $renderer_code, $renderers ) )
		{
			return false;
		}

		$renderers[] = $renderer_code;
		$this->set_renderers( $renderers );

		$this->renderers_validated = NULL;
		//echo 'Added renderer '.$renderer_code;
	}


	/**
	 * Remove a renderer (by code) from the Item.
	 * @param string Renderer code to remove for this item
	 * @return boolean True if renderers have changed
	 */
	function remove_renderer( $renderer_code )
	{
		$r = false;
		$renderers = $this->get_renderers();
		while( ( $key = array_search( $renderer_code, $renderers ) ) !== false )
		{
			$r = true;
			unset($renderers[$key]);
		}

		if( $r )
		{
			$this->set_renderers( $renderers );
			$this->renderers_validated = NULL;
			//echo 'Removed renderer '.$renderer_code;
		}
		return $r;
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
		return $r;
	}
}


/*
 * $Log$
 * Revision 1.83  2009/02/27 20:19:33  blueyed
 * Add prefetching of tags for Items in MainList/ItemList (through ItemTagsCache). This results in (N-1) less queries for N items on a typical blog list page.
 *
 * Revision 1.82  2009/02/27 20:11:18  blueyed
 * Streamline code for prefetching of prerendered content.
 *
 * Revision 1.81  2009/02/27 20:07:47  blueyed
 *  - Add Item::get_prefetch_itemlist_IDs
 *  - Also prefetch prerendered content for ItemList (admin)
 *
 * Revision 1.80  2009/02/27 19:57:05  blueyed
 * doc/TODO
 *
 * Revision 1.79  2009/02/25 22:17:53  blueyed
 * ItemLight: lazily load blog_ID and main_Chapter.
 * There is more, but I do not want to skim the diff again, after
 * "cvs ci" failed due to broken pipe.
 *
 * Revision 1.78  2009/02/25 00:10:16  blueyed
 * doc, add some memory optimisation (ItemPrerenderingCache items are typically only accessed once). Also fix query title for preloading.
 *
 * Revision 1.77  2009/02/24 22:58:20  fplanque
 * Basic version history of post edits
 *
 * Revision 1.76  2009/02/23 21:32:38  blueyed
 * Pre-fetching Mainlists's pre-renderered content: Taking the suggested approach, by adding any out-of-Mainlist items to the query. Might be good for production now.
 *
 * Revision 1.75  2009/02/23 06:44:50  sam2kb
 * Lowercase tags using mbstring funcs if avaliable,
 * see http://forums.b2evolution.net/viewtopic.php?t=14904
 *
 * Revision 1.74  2009/02/23 00:40:09  fplanque
 * doc
 *
 * Revision 1.73  2009/02/22 23:59:53  blueyed
 * ItemPrerenderingCache:
 *  - simple array to prefetch all prerendered MainList items
 *  - There's some flaw still, see the TODO(s)
 *  - add delete_prerendered_content method, also invalidating
 *    content_pages
 *
 * Revision 1.72  2009/02/22 21:49:57  blueyed
 * Add Debuglog-error to File::get_images for non-existing images.
 *
 * Revision 1.71  2009/02/21 23:10:43  fplanque
 * Minor
 *
 * Revision 1.70  2009/02/19 17:51:55  blueyed
 * TODO about plan of PrerenderedContentCache, please comment
 *
 * Revision 1.69  2009/02/12 19:59:41  blueyed
 * - Install: define $localtimenow, so post_datemodified gets set correctly.
 * - Send Cache-Control: no-cache for install/index.php: should not get cached, e.g. when going back to "delete", it should delete!?
 * - indent fixes
 *
 * Revision 1.68  2009/02/03 22:14:10  blueyed
 * Fix indent; TODO about some $params
 *
 * Revision 1.67  2009/02/03 17:45:43  blueyed
 * Item::get_publish_link: check for appropriate status before any more expensive tests.
 *
 * Revision 1.66  2009/02/03 16:55:47  waltercruz
 * Doesn't make sense to publish a redirected post
 *
 * Revision 1.65  2009/01/29 15:48:54  tblue246
 * Use 1 as the default post type ID when creating a new Item object. Fixes http://forums.b2evolution.net/viewtopic.php?t=17780 .
 *
 * Revision 1.64  2009/01/23 22:08:12  tblue246
 * - Filter reserved post types from dropdown box on the post form (expert tab).
 * - Indent/doc fixes
 * - Do not check whether a post title is required when only e. g. switching tabs.
 *
 * Revision 1.63  2009/01/22 00:59:00  blueyed
 * minor
 *
 * Revision 1.62  2009/01/21 23:30:12  fplanque
 * feature/intro posts display adjustments
 *
 * Revision 1.61  2009/01/21 20:33:49  fplanque
 * different display between featured and intro posts
 *
 * Revision 1.60  2008/12/28 23:35:51  fplanque
 * Autogeneration of category/chapter slugs(url names)
 *
 * Revision 1.59  2008/12/22 01:56:54  fplanque
 * minor
 *
 * Revision 1.58  2008/12/22 00:45:26  fplanque
 * antispam
 *
 * Revision 1.57  2008/09/29 08:30:39  fplanque
 * Avatar support
 *
 * Revision 1.56  2008/09/23 07:56:47  fplanque
 * Demo blog now uses shared files folder for demo media + more images in demo posts
 *
 * Revision 1.55  2008/09/15 10:44:16  fplanque
 * skin cleanup
 *
 * Revision 1.54  2008/07/24 01:24:24  afwas
 * $this->title() in function trackback_rdf() used old style parameters. Reported by Austriaco.
 *
 * Revision 1.53  2008/07/11 22:16:52  blueyed
 * Item::get_edit_link(): append anchor to item in redirect_to URL, so that you get to the actual item when pressing save after editing; indent fixes
 *
 * Revision 1.52  2008/06/30 23:47:04  blueyed
 * require_title setting for Blogs, defaulting to 'required'. This makes the title field now a requirement (by default), since it often gets forgotten when posting first (and then the urltitle is ugly already)
 *
 * Revision 1.51  2008/06/01 23:57:20  waltercruz
 * Adding rel=tag (some microformats love)
 *
 * Revision 1.50  2008/05/26 19:22:00  fplanque
 * fixes
 *
 * Revision 1.49  2008/04/26 22:20:45  fplanque
 * Improved compatibility with older skins.
 *
 * Revision 1.48  2008/04/14 17:52:07  fplanque
 * link images to original by default
 *
 * Revision 1.47  2008/04/13 23:38:53  fplanque
 * Basic public user profiles
 *
 * Revision 1.46  2008/04/13 22:07:59  fplanque
 * email fixes
 *
 * Revision 1.45  2008/04/13 15:15:59  fplanque
 * attempt to fix email headers for non latin charsets
 *
 * Revision 1.44  2008/04/12 19:34:21  fplanque
 * bugfix
 *
 * Revision 1.43  2008/04/09 15:28:25  fplanque
 * debug stuff
 *
 * Revision 1.42  2008/04/03 22:03:09  fplanque
 * added "save & edit" and "publish now" buttons to edit screen.
 *
 * Revision 1.41  2008/04/03 13:39:15  fplanque
 * fix
 *
 * Revision 1.40  2008/03/22 19:39:28  fplanque
 * <title> tag support
 *
 * Revision 1.39  2008/03/22 15:20:19  fplanque
 * better issue time control
 *
 * Revision 1.38  2008/02/18 20:22:40  fplanque
 * no message
 *
 * Revision 1.37  2008/02/12 04:59:01  fplanque
 * more custom field handling
 *
 * Revision 1.36  2008/02/11 23:44:44  fplanque
 * more tag params
 *
 * Revision 1.35  2008/02/10 00:58:57  fplanque
 * no message
 *
 * Revision 1.34  2008/02/09 20:14:14  fplanque
 * custom fields management
 *
 * Revision 1.33  2008/02/09 02:56:00  fplanque
 * explicit order by field
 *
 * Revision 1.32  2008/01/23 12:51:20  fplanque
 * posts now have divs with IDs
 *
 * Revision 1.31  2008/01/21 09:35:31  fplanque
 * (c) 2008
 *
 * Revision 1.30  2008/01/20 18:20:26  fplanque
 * Antispam per group setting
 *
 * Revision 1.29  2008/01/19 15:45:28  fplanque
 * refactoring
 *
 * Revision 1.28  2008/01/18 15:53:42  fplanque
 * Ninja refactoring
 *
 * Revision 1.27  2008/01/17 14:38:30  fplanque
 * Item Footer template tag
 *
 * Revision 1.26  2008/01/14 07:22:07  fplanque
 * Refactoring
 *
 * Revision 1.25  2008/01/08 03:31:51  fplanque
 * podcast support
 *
 * Revision 1.24  2008/01/07 02:53:27  fplanque
 * cleaner tag urls
 *
 * Revision 1.23  2007/12/08 14:43:36  yabs
 * bugfix ( http://forums.b2evolution.net/viewtopic.php?t=13482 )
 *
 * Revision 1.22  2007/11/30 21:37:29  fplanque
 * removed empty tags
 *
 * Revision 1.21  2007/11/29 22:47:12  fplanque
 * tags everywhere + debug
 *
 * Revision 1.20  2007/11/29 20:53:45  fplanque
 * Fixed missing url link in basically all skins ...
 *
 * Revision 1.19  2007/11/25 14:28:17  fplanque
 * additional SEO settings
 *
 * Revision 1.18  2007/11/24 21:24:14  fplanque
 * display tags in backoffice
 *
 * Revision 1.17  2007/11/22 17:53:39  fplanque
 * filemanager display cleanup, especially in IE (not perfect)
 *
 * Revision 1.16  2007/11/15 23:45:41  blueyed
 * (Re-)Added phpdoc for get_edit_link
 *
 * Revision 1.15  2007/11/04 01:10:57  fplanque
 * skin cleanup continued
 *
 * Revision 1.14  2007/11/03 23:54:38  fplanque
 * skin cleanup continued
 *
 * Revision 1.13  2007/11/03 21:04:26  fplanque
 * skin cleanup
 *
 * Revision 1.12  2007/11/02 01:54:46  fplanque
 * comment ratings
 *
 * Revision 1.11  2007/09/13 19:16:14  fplanque
 * feedback_link() cleanup
 *
 * Revision 1.10  2007/09/13 02:37:22  fplanque
 * special cases
 *
 * Revision 1.9  2007/09/11 23:10:39  fplanque
 * translation updates
 *
 * Revision 1.8  2007/09/10 14:53:04  fplanque
 * cron fix
 *
 * Revision 1.7  2007/09/09 12:51:58  fplanque
 * cleanup
 *
 * Revision 1.6  2007/09/09 09:15:59  yabs
 * validation
 *
 * Revision 1.5  2007/09/08 19:31:28  fplanque
 * cleanup of XML feeds for comments on individual posts.
 *
 * Revision 1.4  2007/09/04 22:16:33  fplanque
 * in context editing of posts
 *
 * Revision 1.3  2007/08/28 02:43:40  waltercruz
 * Template function to get the rss link to the feeds of the comments on each post
 *
 * Revision 1.2  2007/07/03 23:21:32  blueyed
 * Fixed includes/requires in/for tests
 *
 * Revision 1.1  2007/06/25 11:00:24  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.184  2007/06/24 22:26:34  fplanque
 * improved feedback template
 *
 * Revision 1.183  2007/06/21 00:44:37  fplanque
 * linkblog now a widget
 *
 * Revision 1.182  2007/06/20 21:42:13  fplanque
 * implemented working widget/plugin params
 *
 * Revision 1.180  2007/06/18 20:59:55  fplanque
 * do not display link to comments if comments are disabled
 *
 * Revision 1.179  2007/06/13 23:29:02  fplanque
 * minor
 *
 * Revision 1.178  2007/06/11 01:55:57  fplanque
 * level based user permissions
 *
 * Revision 1.177  2007/05/28 15:18:30  fplanque
 * cleanup
 *
 * Revision 1.176  2007/05/28 01:33:22  fplanque
 * permissions/fixes
 *
 * Revision 1.175  2007/05/27 00:35:26  fplanque
 * tag display + tag filtering
 *
 * Revision 1.174  2007/05/20 01:01:35  fplanque
 * make trackback silent when it should be
 *
 * Revision 1.173  2007/05/14 02:47:23  fplanque
 * (not so) basic Tags framework
 *
 * Revision 1.172  2007/05/13 22:02:07  fplanque
 * removed bloated $object_def
 *
 * Revision 1.171  2007/04/26 00:11:11  fplanque
 * (c) 2007
 *
 * Revision 1.170  2007/04/15 13:34:36  blueyed
 * Fixed default $url generation in page_links()
 *
 * Revision 1.169  2007/04/05 22:57:33  fplanque
 * Added hook: UnfilterItemContents
 *
 * Revision 1.168  2007/03/31 22:46:46  fplanque
 * FilterItemContent event
 *
 * Revision 1.167  2007/03/26 12:59:18  fplanque
 * basic pages support
 *
 * Revision 1.166  2007/03/25 10:19:30  fplanque
 * cleanup
 *
 * Revision 1.165  2007/03/24 20:41:16  fplanque
 * Refactored a lot of the link junk.
 * Made options blog specific.
 * Some junk still needs to be cleaned out. Will do asap.
 *
 * Revision 1.164  2007/03/19 23:59:32  fplanque
 * minor
 *
 * Revision 1.163  2007/03/18 03:43:19  fplanque
 * EXPERIMENTAL
 * Splitting Item/ItemLight and ItemList/ItemListLight
 * Goal: Handle Items with less footprint than with their full content
 * (will be even worse with multiple languages/revisions per Item)
 *
 * Revision 1.162  2007/03/11 23:57:07  fplanque
 * item editing: allow setting to 'redirected' status
 *
 * Revision 1.161  2007/03/06 12:18:08  fplanque
 * got rid of dirty Item::content()
 * Advantage: the more link is now independant. it can be put werever people want it
 *
 * Revision 1.160  2007/03/05 04:52:42  fplanque
 * better precision for viewcounts
 *
 * Revision 1.159  2007/03/05 04:49:17  fplanque
 * better precision for viewcounts
 *
 * Revision 1.158  2007/03/05 02:13:26  fplanque
 * improved dashboard
 *
 * Revision 1.157  2007/03/05 01:47:50  fplanque
 * splitting up Item::content() - proof of concept.
 * needs to be optimized.
 *
 * Revision 1.156  2007/03/03 01:14:11  fplanque
 * new methods for navigating through posts in single item display mode
 *
 * Revision 1.155  2007/03/02 04:40:38  fplanque
 * fixed/commented a lot of stuff with the feeds
 *
 * Revision 1.154  2007/03/02 03:09:12  fplanque
 * rss length doesn't make sense since it doesn't apply to html format anyway.
 * clean solutionwould be to handle an "excerpt" field separately
 *
 * Revision 1.153  2007/02/23 19:16:07  blueyed
 * MFB: Fixed handling of Item::content for pre-rendering (it gets passed by reference!)
 *
 * Revision 1.152  2007/02/18 22:51:26  waltercruz
 * Fixing a little confusion with quotes and string concatenation
 *
 * Revision 1.151  2007/02/08 03:45:40  waltercruz
 * Changing double quotes to single quotes
 *
 * Revision 1.150  2007/02/05 13:32:49  waltercruz
 * Changing double quotes to single quotes
 *
 * Revision 1.149  2007/01/26 04:52:53  fplanque
 * clean comment popups (skins 2.0)
 *
 * Revision 1.148  2007/01/26 02:12:06  fplanque
 * cleaner popup windows
 *
 * Revision 1.147  2007/01/23 03:46:24  fplanque
 * cleaned up presentation
 *
 * Revision 1.146  2007/01/19 10:45:42  fplanque
 * images everywhere :D
 * At this point the photoblogging code can be considered operational.
 *
 * Revision 1.145  2007/01/11 19:29:50  blueyed
 * Fixed E_NOTICE when using the "excerpt" feature
 *
 * Revision 1.144  2006/12/26 00:08:29  fplanque
 * wording
 *
 * Revision 1.143  2006/12/21 22:35:28  fplanque
 * No regression. But a change in usage. The more link must be configured in the skin.
 * Renderers cannot side-effect on the more tag any more and that actually makes the whole thing safer.
 *
 * Revision 1.142  2006/12/20 13:57:34  blueyed
 * TODO about regression because of pre-rendering and the <!--more--> tag
 *
 * Revision 1.141  2006/12/18 13:31:12  fplanque
 * fixed broken more tag
 *
 * Revision 1.140  2006/12/16 01:30:46  fplanque
 * Setting to allow/disable email subscriptions on a per blog basis
 *
 * Revision 1.139  2006/12/15 22:59:05  fplanque
 * doc
 *
 * Revision 1.138  2006/12/14 22:26:31  blueyed
 * Fixed E_NOTICE and displaying of pings into $Messages (though "hackish")
 *
 * Revision 1.137  2006/12/12 02:53:56  fplanque
 * Activated new item/comments controllers + new editing navigation
 * Some things are unfinished yet. Other things may need more testing.
 *
 * Revision 1.136  2006/12/07 23:13:11  fplanque
 * @var needs to have only one argument: the variable type
 * Otherwise, I can't code!
 *
 * Revision 1.135  2006/12/06 23:55:53  fplanque
 * hidden the dead body of the sidebar plugin + doc
 *
 * Revision 1.134  2006/12/05 14:28:29  blueyed
 * Fixed wordcount==0 handling; has been saved as NULL
 *
 * Revision 1.133  2006/12/05 06:38:40  blueyed
 * doc
 *
 * Revision 1.132  2006/12/05 00:39:56  fplanque
 * fixed some more permalinks/archive links
 *
 * Revision 1.131  2006/12/05 00:34:39  blueyed
 * Implemented custom "None" option text in DataObjectCache; Added for $ItemStatusCache, $GroupCache, UserCache and BlogCache; Added custom text for Item::priority_options()
 *
 * Revision 1.130  2006/12/04 20:52:40  blueyed
 * typo
 *
 * Revision 1.129  2006/12/04 19:57:58  fplanque
 * How often must I fix the weekly archives until they stop bugging me?
 *
 * Revision 1.128  2006/12/04 19:41:11  fplanque
 * Each blog can now have its own "archive mode" settings
 *
 * Revision 1.127  2006/12/03 18:15:32  fplanque
 * doc
 *
 * Revision 1.126  2006/12/01 20:04:31  blueyed
 * Renamed Plugins_admin::validate_list() to validate_renderer_list()
 *
 * Revision 1.125  2006/12/01 19:46:42  blueyed
 * Moved Plugins::validate_list() to Plugins_admin class; added stub in Plugins, because at least the starrating_plugin uses it
 *
 * Revision 1.124  2006/11/28 20:04:11  blueyed
 * No edit link, if ID==0 to avoid confusion in preview, see http://forums.b2evolution.net/viewtopic.php?p=47422#47422
 *
 * Revision 1.123  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.122  2006/11/22 20:48:58  blueyed
 * Added Item::get_Chapters() and Item::get_main_Chapter(); refactorized
 *
 * Revision 1.121  2006/11/22 20:12:18  blueyed
 * Use $format param in Item::categories()
 *
 * Revision 1.120  2006/11/19 22:17:42  fplanque
 * minor / doc
 *
 * Revision 1.119  2006/11/19 16:07:31  blueyed
 * Fixed saving empty renderers list. This should also fix the saving of "default" instead of the explicit renderer list
 *
 * Revision 1.118  2006/11/17 18:36:23  blueyed
 * dbchanges param for AfterItemUpdate, AfterItemInsert, AfterCommentUpdate and AfterCommentInsert
 *
 * Revision 1.117  2006/11/13 20:49:52  fplanque
 * doc/cleanup :/
 *
 * Revision 1.116  2006/11/10 20:14:11  blueyed
 * doc, fix
 *
 * Revision 1.115  2006/11/02 16:12:49  blueyed
 * MFB
 *
 * Revision 1.114  2006/11/02 16:01:00  blueyed
 * doc
 *
 * Revision 1.113  2006/10/29 18:33:23  blueyed
 * doc fix
 *
 * Revision 1.112  2006/10/23 22:19:02  blueyed
 * Fixed/unified encoding of redirect_to param. Use just rawurlencode() and no funky &amp; replacements
 *
 * Revision 1.111  2006/10/18 00:03:51  blueyed
 * Some forgotten url_rel_to_same_host() additions
 */
?>
