<?php
/**
 * This file implements the basic_menu_link_Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/widgets/_generic_menu_link.widget.php', 'generic_menu_link_Widget' );


/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @todo dh> this needs to implement BlockCaching cache_keys properly:
 *            - "login": depends on $currentUser being set or not
 *            ...
 *
 * @package evocore
 */
class basic_menu_link_Widget extends generic_menu_link_Widget
{
	var $link_types = array();

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'basic_menu_link' );

		$this->link_types = array(
			'home' => T_('Front Page'),
			'recentposts' => T_('Recent posts'),
			'search' => T_('Search page'),
			'arcdir' => T_('Archive directory'),
			'catdir' => T_('Category directory'),
			'tags' => T_('Tags'),
			'postidx' => T_('Post index'),
			'mediaidx' => T_('Photo index'),
			'sitemap' => T_('Site Map'),
			'latestcomments' => T_('Latest comments'),

			'ownercontact' => T_('Blog owner contact form'),
			'owneruserinfo' => T_('Blog owner profile'),

			'users' => T_('User directory'),

			'login' => T_('Log in form'),
			'logout' => T_('Logout link'),
			'register' => T_('Registration form'),
			'myprofile' => T_('My profile'),
			'profile' => T_('Edit profile'),
			'avatar' => T_('Edit profile picture'),

			'item' => T_('Any item (post, page, etc...)'),
			'postnew' => T_('New Item'),

			'admin' => T_('Admin / Back-Office link'),
			'url' => T_('Any URL'),
		);
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'menu-link-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Menu link or button');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		$this->load_param_array();


		if( !empty($this->param_array['link_text']) )
		{	// We have a custom link text:
			return $this->param_array['link_text'];
		}

		if( !empty($this->param_array['link_type']) )
		{	// TRANS: %s is the link type, e. g. "Blog home" or "Log in form"
			return sprintf( T_( '%s link' ), $this->link_types[ $this->param_array['link_type'] ] );
		}

		return $this->get_name();
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display a configurable menu entry/link');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $admin_url;

		// Check if field "Collection ID" is disabled because of link type and site uses only one fixed collection for profile pages:
		$coll_id_is_disabled = ( empty( $params['infinite_loop'] )
			&& in_array( $this->get_param( 'link_type', true ), array( 'ownercontact', 'owneruserinfo', 'myprofile', 'profile', 'avatar' ) )
			&& $msg_Blog = & get_setting_Blog( 'msg_blog_ID' ) );

		$r = array_merge( array(
				'link_type' => array(
					'label' => T_( 'Link Type' ),
					'note' => T_('What do you want to link to?'),
					'type' => 'select',
					'options' => $this->link_types,
					'defaultvalue' => 'home',
				),
				'link_text' => array(
					'label' => T_('Link text'),
					'note' => T_( 'Text to use for the link (leave empty for default).' ),
					'type' => 'text',
					'size' => 20,
					'defaultvalue' => '',
				),
				'blog_ID' => array(
					'label' => T_('Collection ID'),
					'note' => T_( 'Leave empty for current collection.' )
						.( $coll_id_is_disabled ? ' <span class="red">'.sprintf( T_('The site is <a %s>configured</a> to always use collection %s for profiles/messaging functions.'),
								'href="'.$admin_url.'?ctrl=collections&amp;tab=site_settings"',
								'<b>'.$msg_Blog->get( 'name' ).'</b>' ).'</span>' : '' ),
					'type' => 'integer',
					'allow_empty' => true,
					'size' => 5,
					'defaultvalue' => '',
					'disabled' => $coll_id_is_disabled ? 'disabled' : false,
				),
				'visibility' => array(
					'label' => T_( 'Visibility' ),
					'note' => '',
					'type' => 'radio',
					'options' => array(
							array( 'always', T_( 'Always show (cacheable)') ),
							array( 'access', T_( 'Only show if access is allowed (not cacheable)' ) ) ),
					'defaultvalue' => 'always',
					'field_lines' => true,
				),
				// fp> TODO: ideally we would have a link icon to go click on the destination...
				'item_ID' => array(
					'label' => T_('Item ID'),
					'note' => T_( 'ID of post, page, etc. for "Item" type links.' ),
					'type' => 'integer',
					'allow_empty' => true,
					'size' => 5,
					'defaultvalue' => '',
				),
				'link_href' => array(
					'label' => T_('URL'),
					'note' => T_( 'Destination URL for "URL" type links.' ),
					'type' => 'text',
					'size' => 30,
					'defaultvalue' => '',
				),
				'highlight_current' => array(
					'label' => T_('Highlight current'),
					'note' => '',
					'type' => 'radio',
					'options' => array(
							array( 'yes', T_('Highlight the current item (not cacheable)') ),
							array( 'no', T_('Do not try to highlight (cacheable)') )
						),
					'defaultvalue' => 'yes',
					'field_lines' => true,
				),
			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		parent::init_display( $params );

		if( $this->disp_params['highlight_current'] == 'yes' ||
		    $this->disp_params['visibility'] == 'access' )
		{	// Disable block caching for this widget when it highlights the selected items or show only for users with access to collection:
			$this->disp_params['allow_blockcache'] = 0;
		}
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		/**
		* @var Blog
		*/
		global $Collection, $Blog;
		global $disp;

		$this->init_display( $params );

		$blog_ID = intval( $this->disp_params['blog_ID'] );
		if( $blog_ID > 0 )
		{ // Try to use blog from widget setting
			$BlogCache = & get_BlogCache();
			$current_Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );
		}

		if( empty( $current_Blog ) )
		{ // Blog is not defined in setting or it doesn't exist in DB
			// Use current blog
			$current_Blog = & $Blog;
		}

		if( empty( $current_Blog ) )
		{ // We cannot use this widget without a current collection:
			return false;
		}

		if( $this->disp_params['visibility'] == 'access' && ! $current_Blog->has_access() )
		{	// Don't use this widget because current user has no access to the collection:
			return false;
		}

		// Allow to higlight current menu item only when it is enabled by widget setting and it is linked to current collection:
		$highlight_current = ( $this->disp_params['highlight_current'] == 'yes' && $current_Blog->ID == $Blog->ID );

		switch( $this->disp_params['link_type'] )
		{
			case 'recentposts':
				$url = $current_Blog->get( 'recentpostsurl' );
				if( is_same_url( $url, $Blog->get( 'url' ) ) )
				{ // This menu item has the same url as front page of blog
					$EnabledWidgetCache = & get_EnabledWidgetCache();
					$Widget_array = & $EnabledWidgetCache->get_by_coll_container( $current_Blog->ID, NT_('Menu') );
					if( !empty( $Widget_array ) )
					{
						foreach( $Widget_array as $Widget )
						{
							$Widget->init_display( $params );
							if( isset( $Widget->param_array, $Widget->param_array['link_type'] ) && $Widget->param_array['link_type'] == 'home' )
							{ // Don't display this menu if 'Blog home' menu item exists with the same url
								return false;
							}
						}
					}
				}

				$text = T_('Recently');
				// Check if current menu item must be highlighted:
				$highlight_current = ( $highlight_current && $disp == 'posts' );
				break;

			case 'search':
				$url = $current_Blog->get( 'searchurl' );
				$text = T_('Search');
				// Check if current menu item must be highlighted:
				$highlight_current = ( $highlight_current && $disp == 'search' );
				break;

			case 'arcdir':
				$url = $current_Blog->get( 'arcdirurl' );
				$text = T_('Archives');
				// Check if current menu item must be highlighted:
				$highlight_current = ( $highlight_current && $disp == 'arcdir' );
				break;

			case 'catdir':
				$url = $current_Blog->get( 'catdirurl' );
				$text = T_('Categories');
				// Check if current menu item must be highlighted:
				$highlight_current = ( $highlight_current && $disp == 'catdir' );
				break;

			case 'tags':
				$url = $current_Blog->get( 'tagsurl' );
				$text = T_('Tags');
				// Check if current menu item must be highlighted:
				$highlight_current = ( $highlight_current && $disp == 'tags' );
				break;

			case 'postidx':
				$url = $current_Blog->get( 'postidxurl' );
				$text = T_('Post index');
				// Check if current menu item must be highlighted:
				$highlight_current = ( $highlight_current && $disp == 'postidx' );
				break;

			case 'mediaidx':
				$url = $current_Blog->get( 'mediaidxurl' );
				$text = T_('Photo index');
				// Check if current menu item must be highlighted:
				$highlight_current = ( $highlight_current && $disp == 'mediaidx' );
				break;

			case 'sitemap':
				$url = $current_Blog->get( 'sitemapurl' );
				$text = T_('Site map');
				// Check if current menu item must be highlighted:
				$highlight_current = ( $highlight_current && $disp == 'sitemap' );
				break;

			case 'latestcomments':
				if( !$current_Blog->get_setting( 'comments_latest' ) )
				{ // This page is disabled
					return false;
				}
				$url = $current_Blog->get( 'lastcommentsurl' );
				$text = T_('Latest comments');
				// Check if current menu item must be highlighted:
				$highlight_current = ( $highlight_current && $disp == 'comments' );
				break;

			case 'owneruserinfo':
				global $User;
				$url = url_add_param( $current_Blog->get( 'userurl' ), 'user_ID='.$current_Blog->owner_user_ID );
				$text = T_('Owner details');
				// Check if current menu item must be highlighted:
				$highlight_current = ( $highlight_current && $disp == 'user' && ! empty( $User ) && $User->ID == $current_Blog->owner_user_ID );
				break;

			case 'ownercontact':
				if( ! $url = $current_Blog->get_contact_url( true ) )
				{ // user does not allow contact form:
					return;
				}
				$text = T_('Contact');
				// Check if current menu item must be highlighted:
				// fp> I think it's interesting to select this link , even if the recipient ID is different from the owner
				// odds are there is no other link to highlight in this case
				$highlight_current = ( $highlight_current && ( $disp == 'msgform' || ( isset( $_GET['disp'] ) && $_GET['disp'] == 'msgform' ) ) );
				break;

			case 'login':
				if( is_logged_in() )
				{ // Don't display this link for already logged in users
					return false;
				}
				global $Settings;
				$url = get_login_url( 'menu link', $Settings->get( 'redirect_to_after_login' ), false, $current_Blog->ID );
				if( isset( $this->BlockCache ) )
				{ // Do NOT cache because some of these links are using a redirect_to param, which makes it page dependent.
					// so this will be cached by the PageCache; there is no added benefit to cache it in the BlockCache
					// (which could have been shared between several pages):
					$this->BlockCache->abort_collect();
				}
				$text = T_('Log in');
				// Check if current menu item must be highlighted:
				$highlight_current = ( $highlight_current && $disp == 'login' );
				break;

			case 'logout':
				if( ! is_logged_in() )
				{
					return false;
				}
				$url = get_user_logout_url( $current_Blog->ID );
				$text = T_('Log out');
				// This is never highlighted:
				$highlight_current = false;
				break;

			case 'register':
				if( ! $url = get_user_register_url( NULL, 'menu link', false, '&amp;', $current_Blog->ID ) )
				{
					return false;
				}
				if( isset( $this->BlockCache ) )
				{ // Do NOT cache because some of these links are using a redirect_to param, which makes it page dependent.
					// Note: also beware of the source param.
					// so this will be cached by the PageCache; there is no added benefit to cache it in the BlockCache
					// (which could have been shared between several pages):
					$this->BlockCache->abort_collect();
				}
				$text = T_('Register');
				// Check if current menu item must be highlighted:
				$highlight_current = ( $highlight_current && $disp == 'register' );
				break;

			case 'profile':
				if( ! is_logged_in() ) return false;
				$url = get_user_profile_url( $current_Blog->ID );
				$text = T_('Edit profile');
				// Check if current menu item must be highlighted:
				$highlight_current = ( $highlight_current && in_array( $disp, array( 'profile', 'avatar', 'pwdchange', 'userprefs', 'subs' ) ) );
				break;

			case 'avatar':
				if( ! is_logged_in() ) return false;
				$url = get_user_avatar_url( $current_Blog->ID );
				$text = T_('Profile picture');
				// Note: we never highlight this, it will always highlight 'profile' instead:
				$highlight_current = false;
				break;

			case 'users':
				global $Settings, $user_ID;
				if( ! is_logged_in() && ! $Settings->get( 'allow_anonymous_user_list' ) )
				{	// Don't allow anonymous users to see users list
					return false;
				}
				$url = $current_Blog->get( 'usersurl' );
				$text = T_('User directory');
				// Check if current menu item must be highlighted:
				// Note: If $user_ID is not set, it means we are viewing "My Profile" instead
				$highlight_current = ( $highlight_current && ( $disp == 'users' || ( $disp == 'user' && ! empty( $user_ID ) ) ) );
				break;

			case 'item':
				global $Item;
				$ItemCache = & get_ItemCache();
				/**
				* @var Item
				*/
				$item_ID = intval( $this->disp_params['item_ID'] );
				$disp_Item = & $ItemCache->get_by_ID( $item_ID, false, false );
				if( empty( $disp_Item ) )
				{ // Item not found
					return false;
				}
				$url = $disp_Item->get_permanent_url();
				$text = $disp_Item->title;
				// Check if current menu item must be highlighted:
				$highlight_current = ( $highlight_current && ! empty( $Item ) && $disp_Item->ID == $Item->ID );
				break;

			case 'url':
				if( empty( $this->disp_params['link_href'] ) )
				{ // Don't display a link if url is empty
					return false;
				}
				$url = $this->disp_params['link_href'];
				$text = '[URL]';	// should normally be overriden below...
				// Note: we never highlight this link
				$highlight_current = false;
				break;

			case 'postnew':
				if( ! check_item_perm_create( $current_Blog ) )
				{	// Don't allow users to create a new post:
					return false;
				}
				$url = url_add_param( $current_Blog->get( 'url' ), 'disp=edit' );
				$text = T_('Write a new post');
				// Check if current menu item must be highlighted:
				$highlight_current = ( $highlight_current && $disp == 'edit' );
				break;

			case 'myprofile':
				global $user_ID;
				if( ! is_logged_in() )
				{	// Don't show this link for not logged in users
					return false;
				}
				$url = $current_Blog->get( 'userurl' );
				$text = T_('My profile');
				// Check if current menu item must be highlighted:
				// If $user_ID is not set, it means we will fall back to the current user, so it's ok
				// If $user_ID is set, it means we are browsing the directory instead
				$highlight_current = ( $highlight_current && $disp == 'user' && empty( $user_ID ) );
				break;

			case 'admin':
				global $current_User;
				if( ! ( is_logged_in() && $current_User->check_perm( 'admin', 'restricted' ) && $current_User->check_status( 'can_access_admin' ) ) )
				{ // Don't allow admin url for users who have no access to backoffice
					return false;
				}
				global $admin_url;
				$url = $admin_url;
				$text = T_('Admin').' &raquo;';
				// This is never highlighted:
				$highlight_current = false;
				break;

			case 'home':
			default:
				global $is_front;
				$url = $current_Blog->get( 'url' );
				$text = T_('Front Page');
				// Check if current menu item must be highlighted:
				$highlight_current = ( $highlight_current && ( $disp == 'front' || ! empty( $is_front ) ) );
		}

		// Override default link text?
		if( ! empty( $this->disp_params['link_text'] ) )
		{ // We have a custom link text:
			$text = $this->disp_params['link_text'];
		}

		// Display a layout with menu link:
		echo $this->get_layout_menu_link( $url, $text, $highlight_current );

		return true;
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Collection, $Blog, $current_User;

		$keys = array(
				'wi_ID'   => $this->ID,					// Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID			// Have the settings of the blog changed ? (ex: new owner, new skin)
			);

		switch( $this->disp_params['link_type'] )
		{
			case 'login':  		/* This one will probably abort caching by itself anyways */
			case 'register':	/* This one will probably abort caching by itself anyways */
			case 'profile':		// This can be cached
			case 'avatar':
				// This link also depends on whether or not someone is logged in:
				$keys['loggedin'] = (is_logged_in() ? 1 : 0);

		}

		return $keys;
	}
}

?>