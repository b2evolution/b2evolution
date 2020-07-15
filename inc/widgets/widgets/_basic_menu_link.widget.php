<?php
/**
 * This file implements the basic_menu_link_Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
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
	var $icon = 'navicon';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'basic_menu_link' );

		$this->link_types = get_menu_types();
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
			foreach( $this->link_types as $link_types )
			{
				if( isset( $link_types[ $this->param_array['link_type'] ] ) )
				{
					return sprintf( T_('Link to: %s'), $link_types[ $this->param_array['link_type'] ] );
				}
			}
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

		$default_link_type = 'home';
		$current_link_type = $this->get_param( 'link_type', $default_link_type );

		// Check if field "Collection ID" is disabled because of link type and site uses only one fixed collection for profile pages:
		$msg_Blog = & get_setting_Blog( 'msg_blog_ID' );
		$coll_id_is_disabled = in_array( $current_link_type, array( 'ownercontact', 'owneruserinfo', 'myprofile', 'profile', 'avatar', 'messages', 'contacts' ) );

		load_funcs( 'files/model/_image.funcs.php' );

		$r = array_merge( array(
				'link_type' => array(
					'label' => T_( 'Link Type' ),
					'note' => T_('What do you want to link to?'),
					'type' => 'select',
					'options' => $this->link_types,
					'defaultvalue' => $default_link_type,
				),
				'coll_logo_size' => array(
					'type' => 'select',
					'label' => T_('Collection logo before link text'),
					'options' => get_available_thumb_sizes( T_('No logo') ),
					'defaultvalue' => '',
					'hide' => in_array( $current_link_type, array( 'item', 'admin', 'url' ) ),
				),
				'profile_picture_size' => array(
					'label' => T_('Profile picture before text'),
					'note' => '',
					'type' => 'select',
					'options' => get_available_thumb_sizes( T_('No picture') ),
					'defaultvalue' => '',
					'hide' => ! in_array( $current_link_type, array( 'logout', 'myprofile', 'visits', 'profile', 'avatar', 'useritems', 'usercomments' ) ),
				),
				'link_text' => array(
					'label' => T_('Link text'),
					'note' => T_( 'Text to use for the link (leave empty for default).' ),
					'type' => 'text',
					'size' => 20,
					'defaultvalue' => '',
				),
				'show_badge' => array(
					'label' => T_('Show Badge'),
					'note' => T_('Show a badge with count.'),
					'type' => 'checkbox',
					'defaultvalue' => true,
					'hide' => ! in_array( $current_link_type, array( 'messages', 'flagged' ) ),
				),
				'blog_ID' => array(
					'label' => T_('Collection ID'),
					'note' => T_( 'Leave empty for current collection.' )
						.( $msg_Blog ? ' <span class="evo_setting_coll_disabled red"'.( $coll_id_is_disabled ? '' : ' style="display:none"' ).'>'
							.sprintf( T_('The site is <a %s>configured</a> to always use collection %s for profiles/messaging functions.'),
								'href="'.$admin_url.'?ctrl=collections&amp;tab=site_settings"',
								'<b>'.$msg_Blog->get( 'name' ).'</b>' ).'</span>' : '' ),
					'type' => 'integer',
					'allow_empty' => true,
					'size' => 5,
					'defaultvalue' => '',
					'disabled' => $coll_id_is_disabled && $msg_Blog ? 'disabled' : false,
					'hide' => in_array( $current_link_type, array( 'item', 'admin', 'url' ) ),
				),
				'cat_ID' => array(
					'label' => T_('Category ID'),
					'note' => T_('Leave empty for default category.'),
					'type' => 'integer',
					'allow_empty' => true,
					'size' => 5,
					'defaultvalue' => '',
					'hide' => ! in_array( $current_link_type, array( 'recentposts', 'postnew' ) ),
				),
				// fp> TODO: ideally we would have a link icon to go click on the destination...
				'item_ID' => array(
					'label' => T_('Item ID'),
					'note' => T_( 'ID of post, page, etc. for "Item" type links.' ).' '.$this->get_param_item_info( 'item_ID' ),
					'type' => 'integer',
					'allow_empty' => true,
					'size' => 5,
					'defaultvalue' => '',
					'hide' => ( $current_link_type != 'item' ),
				),
				'link_href' => array(
					'label' => T_('URL'),
					'note' => T_( 'Destination URL for "URL" type links.' ),
					'type' => 'text',
					'size' => 30,
					'defaultvalue' => '',
					'hide' => ( $current_link_type != 'url' ),
				),
				'show_to' => array(
					'label' => T_('Show to'),
					'note' => '',
					'type' => 'radio',
					'options' => array( array( 'any', T_('All users') ),
										array( 'loggedin', T_('Logged in users') ),
										array( 'perms', T_('Users with permissions only') ) ),
					'defaultvalue' => 'perms',
					'field_lines' => true,
					'hide' => ! in_array( $current_link_type, array( 'messages', 'contacts' ) ),
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
				'hide_empty' => array(
					'label' => T_('Hide if empty'),
					'note' => T_('Check to hide this menu if the list is empty.'),
					'type' => 'checkbox',
					'defaultvalue' => false,
					'hide' => ( $current_link_type != 'flagged' ),
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
			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Get JavaScript code which helps to edit widget form
	 *
	 * @return string
	 */
	function get_edit_form_javascript()
	{
		return 'jQuery( "#'.$this->get_param_prefix().'link_type" ).change( function()
		{
			var link_type_value = jQuery( this ).val();
			// Hide/Show Profile picture size:
			jQuery( "#ffield_'.$this->get_param_prefix().'profile_picture_size" ).toggle( link_type_value == "logout" ||
				link_type_value == "myprofile" ||
				link_type_value == "visits" ||
				link_type_value == "profile" ||
				link_type_value == "avatar" ||
				link_type_value == "useritems" ||
				link_type_value == "usercomments" );
			if( link_type_value == "myprofile" && jQuery( "#'.$this->get_param_prefix().'profile_picture_size" ).val() == "" )
			{	// Set default picture size for "View my profile":
				jQuery( "#'.$this->get_param_prefix().'profile_picture_size" ).val( "crop-top-15x15" );
			}
			// Hide/Show collection ID:
			jQuery( "#ffield_'.$this->get_param_prefix().'blog_ID, #ffield_'.$this->get_param_prefix().'coll_logo_size" ).toggle( link_type_value != "item" && link_type_value != "admin" && link_type_value != "url" );
			if( jQuery( ".evo_setting_coll_disabled" ).length )
			{	// Hide/Show info for disabled collection:
				var coll_disabled = link_type_value == "ownercontact" ||
					link_type_value == "owneruserinfo" ||
					link_type_value == "myprofile" ||
					link_type_value == "profile" ||
					link_type_value == "avatar" ||
					link_type_value == "messages" ||
					link_type_value == "contacts";
				jQuery( ".evo_setting_coll_disabled" ).toggle( coll_disabled );
				jQuery( "#'.$this->get_param_prefix().'blog_ID" ).prop( "disabled", coll_disabled );
			}
			// Hide/Show category ID:
			jQuery( "#ffield_'.$this->get_param_prefix().'cat_ID" ).toggle( link_type_value == "recentposts" || link_type_value == "postnew" );
			// Hide/Show item ID:
			jQuery( "#ffield_'.$this->get_param_prefix().'item_ID" ).toggle( link_type_value == "item" );
			// Hide/Show URL:
			jQuery( "#ffield_'.$this->get_param_prefix().'url" ).toggle( link_type_value == "url" );
			// Hide/Show setting "Show to":
			jQuery( "#ffield_'.$this->get_param_prefix().'show_to" ).toggle( link_type_value == "messages" || link_type_value == "contacts" );
			// Hide/Show setting "Show Badge":
			jQuery( "#ffield_'.$this->get_param_prefix().'show_badge" ).toggle( link_type_value == "messages" || link_type_value == "flagged" );
			// Hide/Show setting "Hide if empty":
			jQuery( "#ffield_'.$this->get_param_prefix().'hide_empty" ).toggle( link_type_value == "flagged" );
		} );';
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
		global $disp, $cat, $thumbnail_sizes;

		$this->init_display( $params );

		if( isset( $this->disp_params['link_text_'.$this->disp_params['link_type']] ) )
		{	// Use custom link text per type from skin side:
			// (used by site skins with param like 'link_text_myprofile' => '$login$' where we need to force friendly username to login)
			$link_text = $this->disp_params['link_text_'.$this->disp_params['link_type']];
		}
		else
		{	// Use normal link text:
			$link_text = $this->disp_params['link_text'];
		}

		// Initialize Menu Entry object to build a menu link/button:
		load_class( 'menus/model/_sitemenuentry.class.php', 'SiteMenuEntry' );
		$SiteMenuEntry = new SiteMenuEntry();
		$SiteMenuEntry->set( 'coll_ID', $this->disp_params['blog_ID'] );
		$SiteMenuEntry->set( 'cat_ID', $this->disp_params['cat_ID'] );
		$SiteMenuEntry->set( 'item_ID', $this->disp_params['item_ID'] );
		$SiteMenuEntry->set( 'coll_logo_size', $this->disp_params['coll_logo_size'] );
		$SiteMenuEntry->set( 'user_pic_size', $this->disp_params['profile_picture_size'] );
		$SiteMenuEntry->set( 'type', $this->disp_params['link_type'] );
		$SiteMenuEntry->set( 'text', $link_text );
		$SiteMenuEntry->set( 'url', $this->disp_params['link_href'] );
		$SiteMenuEntry->set( 'visibility', $this->disp_params['visibility'] );
		$SiteMenuEntry->set( 'access', $this->disp_params['show_to'] );
		$SiteMenuEntry->set( 'show_badge', $this->disp_params['show_badge'] );
		$SiteMenuEntry->set( 'highlight', ( $this->disp_params['highlight_current'] == 'yes' ) );
		$SiteMenuEntry->set( 'hide_empty', $this->disp_params['hide_empty'] );

		if( ! ( $entry_Blog = & $SiteMenuEntry->get_Blog() ) )
		{	// We cannot use this widget without a current collection:
			$this->display_debug_message();
			return false;
		}

		if( ! ( $url = $SiteMenuEntry->get_url() ) )
		{	// Don't display this menu entry because of some restriction for current User or by general settings:
			$this->display_debug_message( empty( $SiteMenuEntry->url_error ) ? '' : 'Hidden('.$SiteMenuEntry->url_error.')' );
			return false;
		}

		// Default link template:
		$link_template = NULL;

		switch( $this->disp_params['link_type'] )
		{
			case 'recentposts':
				if( is_same_url( $url, $Blog->get( 'url' ) ) )
				{ // This menu item has the same url as front page of blog
					$EnabledWidgetCache = & get_EnabledWidgetCache();
					$Widget_array = & $EnabledWidgetCache->get_by_coll_container( $entry_Blog->ID, NT_('Menu') );
					if( !empty( $Widget_array ) )
					{
						foreach( $Widget_array as $Widget )
						{
							$Widget->init_display( $params );
							if( isset( $Widget->param_array, $Widget->param_array['link_type'] ) && $Widget->param_array['link_type'] == 'home' )
							{	// Don't display this menu if 'Blog home' menu item exists with the same url:
								$this->display_debug_message();
								return false;
							}
						}
					}
				}
				break;

			case 'ownercontact':
				if( $entry_Blog->get_setting( 'msgform_nofollowto' ) )
				{	// Use nofollow attribute:
					$link_template = '<a href="$link_url$" class="$link_class$" rel="nofollow">$link_text$</a>';
				}
				break;

			case 'login':
			case 'register':
				if( isset( $this->BlockCache ) )
				{ // Do NOT cache because some of these links are using a redirect_to param, which makes it page dependent.
					// Note: also beware of the source param.
					// so this will be cached by the PageCache; there is no added benefit to cache it in the BlockCache
					// (which could have been shared between several pages):
					$this->BlockCache->abort_collect();
				}
				break;

			case 'messages':
			case 'flagged':
				if( $SiteMenuEntry->get( 'show_badge' ) && isset( $this->BlockCache ) )
				{	// Do not cache if bage is displayed because the number of unread messages are always changing:
					$this->BlockCache->abort_collect();
				}
				break;
		}

		// Display a layout with menu link:
		echo $this->get_layout_standalone_menu_link( $url, $SiteMenuEntry->get_text(), $SiteMenuEntry->is_active(), $link_template );

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
				break;

			case 'item':
				// Visibility of the Item menu depends on permission of current User:
				$keys['user_ID'] = ( is_logged_in() ? $current_User->ID : 0 ); // Has the current User changed?
				// Item title may be changed so we should update it in the menu as well:
				$keys['item_ID'] = $this->disp_params['item_ID']; // Has the Item page changed?
				break;
		}

		return $keys;
	}
}

?>