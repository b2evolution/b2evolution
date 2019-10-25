<?php
/**
 * This file implements the Site Menu class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );


/**
 * Menu Entry Class
 *
 * @package evocore
 */
class SiteMenuEntry extends DataObject
{
	var $menu_ID;
	var $parent_ID;
	var $order;
	var $text;
	var $type;
	var $coll_logo_size;
	var $coll_ID;
	var $cat_ID;
	var $item_ID;
	var $url;
	var $visibility = 'always';
	var $highlight;

	/**
	 * Collection
	 */
	var $Blog = NULL;

	/**
	 * Chapter
	 */
	var $Chapter = NULL;

	/**
	 * Item
	 */
	var $Item = NULL;

	/**
	 * Site Menu Entry children list
	 */
	var $children = array();
	var $children_sorted = false;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_menus__entry', 'ment_', 'ment_ID' );

		if( $db_row != NULL )
		{	// Get menu entry data from DB:
			$this->ID = $db_row->ment_ID;
			$this->menu_ID = $db_row->ment_menu_ID;
			$this->parent_ID = $db_row->ment_parent_ID;
			$this->order = $db_row->ment_order;
			$this->text = $db_row->ment_text;
			$this->type = $db_row->ment_type;
			$this->coll_logo_size = $db_row->ment_coll_logo_size;
			$this->coll_ID = $db_row->ment_coll_ID;
			$this->cat_ID = $db_row->ment_cat_ID;
			$this->item_ID = $db_row->ment_item_ID;
			$this->url = $db_row->ment_url;
			$this->visibility = $db_row->ment_visibility;
			$this->highlight = $db_row->ment_highlight;
		}
	}


	/**
	 * Get delete cascade settings
	 *
	 * @return array
	 */
	static function get_delete_cascades()
	{
		return array(
				array( 'table' => 'T_menus__entry', 'fk' => 'ment_menu_ID', 'msg' => T_('%d menu entries') ),
			);
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Menu:
		param( 'ment_menu_ID', 'integer' );
		param_check_not_empty( 'ment_menu_ID', T_('Please select menu!') );
		$this->set_from_Request( 'menu_ID' );

		// Parent:
		param( 'ment_parent_ID', 'integer', NULL );
		$this->set_from_Request( 'parent_ID', NULL, true );

		// Order:
		param( 'ment_order', 'integer', NULL );
		$this->set_from_Request( 'order', NULL, true );

		// Text:
		param( 'ment_text', 'string' );
		$this->set_from_Request( 'text' );

		// Type:
		param( 'ment_type', 'string' );
		$this->set_from_Request( 'type' );

		// Collection logo size:
		param( 'ment_coll_logo_size', 'string' );
		$this->set_from_Request( 'coll_logo_size' );

		// Collection ID:
		param( 'ment_coll_ID', 'integer', NULL );
		$this->set_from_Request( 'coll_ID', NULL, true );

		// Category ID:
		param( 'ment_cat_ID', 'integer', NULL );
		$this->set_from_Request( 'cat_ID', NULL, true );

		// Item ID:
		param( 'ment_item_ID', 'integer', NULL );
		$this->set_from_Request( 'item_ID', NULL, true );

		// URL:
		param( 'ment_url', 'url' );
		$this->set_from_Request( 'url' );

		// Visibility:
		param( 'ment_visibility', 'string' );
		$this->set_from_Request( 'visibility' );

		// Highlight:
		param( 'ment_highlight', 'integer', 0 );
		$this->set_from_Request( 'highlight' );


		return ! param_errors_detected();
	}


	/**
	 * Get name of Menu Entry
	 *
	 * @return string Menu Entry
	 */
	function get_name()
	{
		return $this->get( 'text' );
	}


	/**
	 * Add a child
	 *
	 * @param object SiteMenuEntry
	 */
	function add_child_entry( & $SiteMenuEntry )
	{
		if( !isset( $this->children[ $SiteMenuEntry->ID ] ) )
		{	// Add only if it was not added yet:
			$this->children[ $SiteMenuEntry->ID ] = & $SiteMenuEntry;
		}
	}


	/**
	 * Sort Site Menu Entry childen
	 */
	function sort_children()
	{
		if( $this->children_sorted )
		{ // Site Menu Entry children list is already sorted
			return;
		}

		// Sort children list
		uasort( $this->children, array( 'SiteMenuEntryCache','compare_site_menu_entries' ) );
	}


	/**
	 * Get children/sub-entires of this category
	 *
	 * @param boolean set to true to sort children, leave false otherwise
	 * @return array of SiteMenuEntries - children of this SiteMenuEntry
	 */
	function get_children( $sorted = false )
	{
		$SiteMenuEntryCache = & get_SiteMenuEntryCache();
		$SiteMenuEntryCache->reveal_children( $this->get( 'menu_ID' ), $sorted );

		$parent_SiteMenuEntry = & $SiteMenuEntryCache->get_by_ID( $this->ID );
		if( $sorted )
		{	// Sort child entries:
			$parent_SiteMenuEntry->sort_children();
		}

		return $parent_SiteMenuEntry->children;
	}


	/**
	 * Get Collection
	 *
	 * @return object|NULL|false Collection
	 */
	function & get_Blog()
	{
		if( $this->Blog === NULL )
		{	// Load collection once:
			$BlogCache = & get_BlogCache();
			$this->Blog = & $BlogCache->get_by_ID( $this->get( 'coll_ID' ), false, false );

			if( empty( $this->Blog ) )
			{	// Use current Collection if it is not defined or it doesn't exist in DB:
				global $Blog;
				if( isset( $Blog ) )
				{
					$this->Blog = & $Blog;
				}
			}
		}

		return $this->Blog;
	}


	/**
	 * Get Chapter
	 *
	 * @return object|NULL|false Chapter
	 */
	function & get_Chapter()
	{
		if( $this->Chapter === NULL )
		{	// Load collection once:
			$ChapterCache = & get_ChapterCache();
			$this->Chapter = & $ChapterCache->get_by_ID( $this->get( 'cat_ID' ), false, false );
		}

		return $this->Chapter;
	}


	/**
	 * Get Item
	 *
	 * @return object|NULL|false Item
	 */
	function & get_Item()
	{
		if( $this->Item === NULL )
		{	// Load collection once:
			$ItemCache = & get_ItemCache();
			$this->Item = & $ItemCache->get_by_ID( $this->get( 'item_ID' ), false, false );
		}

		return $this->Item;
	}


	/**
	 * Get Menu Entry Text based on type
	 *
	 * @param boolean TRUE to force to default value
	 * @return string
	 */
	function get_text( $force_default = false )
	{
		if( ! $force_default )
		{
			$menu_entry_text = $this->get( 'text' );
			if( $menu_entry_text !== '' )
			{	// Use custom text:
				return $menu_entry_text;
			}
		}

		$entry_Blog = & $this->get_Blog();

		switch( $this->get( 'type' ) )
		{
			case 'home':
				return T_('Front Page');

			case 'recentposts':
				if( $entry_Chapter = & $this->get_Chapter() )
				{	// Use category name instead of default if the defined category is found in DB:
					return $entry_Chapter->get( 'name' );
				}
				return T_('Recently');

			case 'search':
				return T_('Search');

			case 'arcdir':
				return T_('Archives');

			case 'catdir':
				return T_('Categories');

			case 'tags':
				return T_('Tags');

			case 'postidx':
				return T_('Post index');

			case 'mediaidx':
				return T_('Photo index');

			case 'sitemap':
				return T_('Site map');

			case 'latestcomments':
				return T_('Latest comments');

			case 'owneruserinfo':
				return T_('Owner details');

			case 'ownercontact':
				return T_('Contact');

			case 'login':
				return T_('Log in');

			case 'logout':
				return T_('Log out');

			case 'register':
				return T_('Register');

			case 'profile':
				return T_('Edit profile');

			case 'avatar':
				return T_('Profile picture');

			case 'visits':
				$text = T_('My visits');
				$visit_count = is_logged_in() && $current_User->get_profile_visitors_count();
				if( $visit_count )
				{
					$text .= ' <span class="badge badge-info">'.$visit_count.'</span>';
				}
				return $text;

			case 'useritems':
				return T_('User\'s posts/items');

			case 'usercomments':
				return url_add_param( $Blog->gen_blogurl(), 'disp=usercomments' );

			case 'users':
				return $entry_Blog->get( 'usersurl' );

			case 'item':
				$entry_Item = & $this->get_Item();
				if( $entry_Item && $entry_Item->can_be_displayed() )
				{	// Item is not found or it cannot be displayed for current user on front-office:
					return $entry_Item->get( 'title');
				}
				else
				{
					return '[NOT FOUND]';
				}

			case 'url':
				return $this->get( 'url' );

			case 'postnew':
				$text = T_('Write a new post');
				if( $entry_Chapter = & $this->get_Chapter() )
				{	// Use button name from Category Item Type:
					if( $cat_ItemType = & $entry_Chapter->get_ItemType( true ) )
					{	// Use button text depending on default category's Item Type:
						$text = $cat_ItemType->get_item_denomination( 'inskin_new_btn' );
					}
				}
				return $text;

			case 'myprofile':
				return T_('My profile');

			case 'admin':
				return T_('Admin').' &raquo;';
		}

		return '[UNKNOWN]';
	}


	/**
	 * Get Menu Entry URL based on type
	 *
	 * @return string|boolean URL or FALSE on unknown type
	 */
	function get_url()
	{
		$entry_Blog = & $this->get_Blog();

		switch( $this->get( 'type' ) )
		{
			case 'home':
				return $entry_Blog->get( 'url' );

			case 'recentposts':
				if( $entry_Chapter = & $this->get_Chapter() )
				{	// Use category url instead of default if the defined category is found in DB:
					return $entry_Chapter->get_permanent_url();
				}
				return $entry_Blog->get( 'recentpostsurl' );

			case 'search':
				return $entry_Blog->get( 'searchurl' );

			case 'arcdir':
				return $entry_Blog->get( 'arcdirurl' );

			case 'catdir':
				return $entry_Blog->get( 'catdirurl' );

			case 'tags':
				return $entry_Blog->get( 'tagsurl' );

			case 'postidx':
				return $entry_Blog->get( 'postidxurl' );

			case 'mediaidx':
				return $entry_Blog->get( 'mediaidxurl' );

			case 'sitemap':
				return $entry_Blog->get( 'sitemapurl' );

			case 'latestcomments':
				if( ! $entry_Blog->get_setting( 'comments_latest' ) )
				{	// This page is disabled:
					return false;
				}
				return $entry_Blog->get( 'lastcommentsurl' );

			case 'owneruserinfo':
				return url_add_param( $entry_Blog->get( 'userurl' ), 'user_ID='.$entry_Blog->owner_user_ID );

			case 'ownercontact':
				return $entry_Blog->get_contact_url();

			case 'login':
				if( is_logged_in() )
				{	// Don't display this link for already logged in users:
					return false;
				}
				global $Settings;
				return get_login_url( 'menu link', $Settings->get( 'redirect_to_after_login' ), false, $entry_Blog->ID );

			case 'logout':
				if( ! is_logged_in() )
				{	// Current user must be logged in:
					return false;
				}
				return get_user_logout_url( $entry_Blog->ID );

			case 'register':
				return get_user_register_url( NULL, 'menu link', false, '&amp;', $entry_Blog->ID );

			case 'profile':
				if( ! is_logged_in() )
				{	// Current user must be logged in:
					return false;
				}
				return get_user_profile_url( $entry_Blog->ID );

			case 'avatar':
				if( ! is_logged_in() )
				{	// Current user must be logged in:
					return false;
				}
				return get_user_avatar_url( $entry_Blog->ID );

			case 'visits':
				global $Settings, $current_User;
				if( ! is_logged_in() || ! $Settings->get( 'enable_visit_tracking' ) )
				{	// Current user must be logged in and visit tracking must be enabled:
					return false;
				}

				return $current_User->get_visits_url();

			case 'useritems':
				if( ! is_logged_in() )
				{	// Don't allow anonymous users to see items list:
					return false;
				}
				return url_add_param( $entry_Blog->gen_blogurl(), 'disp=useritems' );

			case 'usercomments':
				if( ! is_logged_in() )
				{	// Don't allow anonymous users to see comments list:
					return false;
				}
				return url_add_param( $entry_Blog->gen_blogurl(), 'disp=usercomments' );

			case 'users':
				global $Settings, $user_ID;
				if( ! is_logged_in() && ! $Settings->get( 'allow_anonymous_user_list' ) )
				{	// Don't allow anonymous users to see users list:
					return false;
				}
				return $entry_Blog->get( 'usersurl' );

			case 'item':
				$entry_Item = & $this->get_Item();
				if( ! $entry_Item || ! $entry_Item->can_be_displayed() )
				{	// Item is not found or it cannot be displayed for current user on front-office:
					return false;
				}
				return $entry_Item->get_permanent_url();

			case 'url':
				$entry_url = $this->get( 'url' );
				return ( empty( $entry_url ) ? false : $entry_url );

			case 'postnew':
				if( ! check_item_perm_create( $entry_Blog ) )
				{	// Don't allow users to create a new post:
					return false;
				}
				$url = url_add_param( $entry_Blog->get( 'url' ), 'disp=edit' );
				if( $entry_Chapter = & $this->get_Chapter() )
				{	// Append category ID to the URL:
					$url = url_add_param( $url, 'cat='.$entry_Chapter->ID );
					$cat_ItemType = & $entry_Chapter->get_ItemType( true );
					if( $cat_ItemType === false )
					{	// Don't allow to create a post in this category because this category has no default Item Type:
						return false;
					}
					if( $cat_ItemType )
					{	// Append item type ID to the URL:
						$url = url_add_param( $url, 'item_typ_ID='.$cat_ItemType->ID );
					}
				}
				return $url;
				break;

			case 'myprofile':
				if( ! is_logged_in() )
				{	// Don't show this link for not logged in users:
					return false;
				}
				return $entry_Blog->get( 'userurl' );
				break;

			case 'admin':
				global $current_User;
				if( ! ( is_logged_in() && $current_User->check_perm( 'admin', 'restricted' ) && $current_User->check_status( 'can_access_admin' ) ) )
				{	// Don't allow admin url for users who have no access to backoffice:
					return false;
				}
				global $admin_url;
				return $admin_url;
		}

		return false;
	}


	/**
	 * Check if Menu Entry is active
	 *
	 * @return boolean
	 */
	function is_active()
	{
		global $Blog, $disp;

		if( ! $this->get( 'highlight' ) )
		{	// Don't highlight this menu entry:
			return false;
		}

		// Get current collection ID:
		$current_blog_ID = isset( $Blog ) ? $Blog->ID : NULL;

		// Get collection of this Menu Entry:
		$entry_Blog = & $this->get_Blog();

		if( $current_blog_ID != $entry_Blog->ID )
		{	// This is a different collection than defined in this Menu Entry:
			return false;
		}

		switch( $this->get( 'type' ) )
		{
			case 'home':
				return ( $disp == 'front' || ! empty( $is_front ) );

			case 'recentposts':
				global $cat;
				$entry_Chapter = & $this->get_Chapter();
				return ( $disp == 'posts' && ( empty( $entry_Chapter ) || $cat == $entry_Chapter->ID ) );

			case 'search':
				return ( $disp == 'search' );

			case 'arcdir':
				return ( $disp == 'arcdir' );

			case 'catdir':
				return ( $disp == 'catdir' );

			case 'tags':
				return ( $disp == 'tags' );

			case 'postidx':
				return ( $disp == 'postidx' );

			case 'mediaidx':
				return ( $disp == 'mediaidx' );

			case 'sitemap':
				return ( $disp == 'sitemap' );

			case 'latestcomments':
				return ( $disp == 'comments' );

			case 'owneruserinfo':
				global $User;
				return ( $disp == 'user' && ! empty( $User ) && $User->ID == $entry_Blog->owner_user_ID );

			case 'ownercontact':
				return ( $disp == 'msgform' || ( isset( $_GET['disp'] ) && $_GET['disp'] == 'msgform' ) );

			case 'login':
				return ( $disp == 'login' );

			case 'logout':
				// This is never highlighted:
				return false;

			case 'register':
				return ( $disp == 'register' );

			case 'profile':
				return in_array( $disp, array( 'profile', 'avatar', 'pwdchange', 'userprefs', 'subs' ) );

			case 'avatar':
				// Note: we never highlight this, it will always highlight 'profile' instead:
				return false;

			case 'visits':
				return ( $disp == 'visits' );

			case 'useritems':
				return ( $disp == 'useritems' );

			case 'usercomments':
				return ( $disp == 'usercomments' );

			case 'users':
				global $user_ID;
				// Note: If $user_ID is not set, it means we are viewing "My Profile" instead
				return ( $disp == 'users' || ( $disp == 'user' && ! empty( $user_ID ) ) );

			case 'item':
				global $Item;
				$entry_Item = & $this->get_Item();
				return ( ! empty( $Item ) && $entry_Item->ID == $Item->ID );

			case 'url':
				// Note: we never highlight this link
				return false;

			case 'postnew':
				global $cat;
				$entry_Chapter = & $this->get_Chapter();
				return ( $disp == 'edit' && ( empty( $Chapter ) || $cat == $entry_Chapter->ID ) );

			case 'myprofile':
				global $user_ID;
				return ( $disp == 'user' && empty( $user_ID ) );

			case 'admin':
				// This is never highlighted:
				return false;
		}

		return false;
	}
}
?>