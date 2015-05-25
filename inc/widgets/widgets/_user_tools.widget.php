<?php
/**
 * This file implements the xyz Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class user_tools_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function user_tools_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'user_tools' );
	}


  /**
   * Get definitions for editable params
   *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
			'title' => array(
				'label' => T_('Block title'),
				'note' => T_( 'Title to display in your skin.' ),
				'size' => 40,
				'defaultvalue' => T_('User tools'),
			),
			// Write new post - disp=edit
			'user_postnew_link_show' => array(
				'label' => T_( 'Write a new post link'),
				'note' => T_( 'Show link' ),
				'type' => 'checkbox',
				'defaultvalue' => 1,
			),
			'user_postnew_link' => array(
				'size' => 30,
				'note' => T_( 'Link text to display' ),
				'type' => 'text',
				'defaultvalue' => T_( 'Write a new post...' ),
			),
			// Messaging - disp=threads
			'user_messaging_link_show' => array(
				'label' => T_( 'Messaging area link'),
				'note' => T_( 'Show link' ),
				'type' => 'checkbox',
				'defaultvalue' => 1,
			),
			'show_badge' => array(
				'label' => T_( 'Show Badge' ),
				'note' => T_( 'Show a badge with the count of unread messages.' ),
				'type' => 'checkbox',
				'defaultvalue' => true,
			),
			'user_messaging_link' => array(
				'size' => 30,
				'note' => T_( 'Link text to display' ),
				'type' => 'text',
				'defaultvalue' => T_( 'My messages' ),
			),
			// Contacts - disp=contacts
			'user_contacts_link_show' => array(
				'label' => T_( 'Contacts link'),
				'note' => T_( 'Show link' ),
				'type' => 'checkbox',
				'defaultvalue' => 1,
			),
			'user_contacts_link' => array(
				'size' => 30,
				'note' => T_( 'Link text to display' ),
				'type' => 'text',
				'defaultvalue' => T_( 'My contacts' ),
			),
			// See profile - disp=user
			'user_view_link_show' => array(
				'label' => T_( 'See profile link'),
				'note' => T_( 'Show link' ),
				'type' => 'checkbox',
				'defaultvalue' => 1,
			),
			'user_view_link' => array(
				'size' => 30,
				'note' => T_( 'Link text to display' ),
				'type' => 'text',
				'defaultvalue' => T_( 'My profile' ),
			),
			// Edit profile - disp=profile
			'user_profile_link_show' => array(
				'label' => T_( 'Edit profile link'),
				'note' => T_( 'Show link' ),
				'type' => 'checkbox',
				'defaultvalue' => 1,
			),
			'user_profile_link' => array(
				'size' => 30,
				'note' => T_( 'Link text to display' ),
				'type' => 'text',
				'defaultvalue' => T_( 'Edit my profile' ),
			),
			// Edit picture - disp=avatar
			'user_picture_link_show' => array(
				'label' => T_( 'Edit profile picture link'),
				'note' => T_( 'Show link' ),
				'type' => 'checkbox',
				'defaultvalue' => 1,
			),
			'user_picture_link' => array(
				'size' => 30,
				'note' => T_( 'Link text to display' ),
				'type' => 'text',
				'defaultvalue' => T_( 'Change my picture' ),
			),
			// Edit password - disp=pwdchange
			'user_password_link_show' => array(
				'label' => T_( 'Edit password link'),
				'note' => T_( 'Show link' ),
				'type' => 'checkbox',
				'defaultvalue' => 0,
			),
			'user_password_link' => array(
				'size' => 30,
				'note' => T_( 'Link text to display' ),
				'type' => 'text',
				'defaultvalue' => T_( 'Change my password' ),
			),
			// Edit preferences - disp=userprefs
			'user_preferences_link_show' => array(
				'label' => T_( 'Edit preferences link'),
				'note' => T_( 'Show link' ),
				'type' => 'checkbox',
				'defaultvalue' => 0,
			),
			'user_preferences_link' => array(
				'size' => 30,
				'note' => T_( 'Link text to display' ),
				'type' => 'text',
				'defaultvalue' => T_( 'Change my preferences' ),
			),
			// Edit notifications - disp=subs
			'user_subs_link_show' => array(
				'label' => T_( 'Edit notifications link'),
				'note' => T_( 'Show link' ),
				'type' => 'checkbox',
				'defaultvalue' => 0,
			),
			'user_subs_link' => array(
				'size' => 30,
				'note' => T_( 'Link text to display' ),
				'type' => 'text',
				'defaultvalue' => T_( 'Notifications &amp; Subscriptions' ),
			),
			// Admin
			'user_admin_link_show' => array(
				'label' => T_( 'Admin link'),
				'note' => T_( 'Show link' ),
				'type' => 'checkbox',
				'defaultvalue' => 1,
			),
			'user_admin_link' => array(
				'size' => 30,
				'note' => T_( 'Link text to display' ),
				'type' => 'text',
				'defaultvalue' => T_( 'Admin area' ),
			),
			// Logout
			'user_logout_link_show' => array(
				'label' => T_( 'Logout link'),
				'note' => T_( 'Show link' ),
				'type' => 'checkbox',
				'defaultvalue' => 1,
			),
			'user_logout_link' => array(
				'size' => 30,
				'note' => T_( 'Link text to display' ),
				'type' => 'text',
				'defaultvalue' => T_( 'Log out' ),
			),
		), parent::get_param_definitions( $params )	);

		if( isset( $r['allow_blockcache'] ) )
		{ // Set default blockcache to false and disable this setting because caching is never allowed for this widget
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'user-tools-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('User Tools');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output($this->disp_params['title']);
	}


  /**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display user tools: Log in, Admin, Profile, Subscriptions, Log out');
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		if( !is_logged_in() )
		{	// Only logged in users can see this tools panel
			return false;
		}

		$this->init_display( $params ); // just in case it hasn't been done before

		$this->disp_params['item_start'] .= '<strong>';
		$this->disp_params['item_end'] = '</strong>'.$this->disp_params['item_end'];

		$tools_links = '';
		if( $this->get_param('user_postnew_link_show') )
		{	// Write new post - disp=edit
			$tools_links .= get_item_new_link( $this->disp_params['item_start'], $this->disp_params['item_end'], $this->disp_params[ 'user_postnew_link' ] );
		}
		if( $this->get_param('user_messaging_link_show') )
		{	// Messaging - disp=threads
			$tools_links .= get_user_messaging_link( $this->disp_params['item_start'], $this->disp_params['item_end'], $this->disp_params[ 'user_messaging_link' ], $this->disp_params[ 'user_messaging_link' ], $this->disp_params[ 'show_badge' ] );
		}
		if( $this->get_param('user_contacts_link_show') )
		{	// Contacts - disp=contacts
			$tools_links .= get_user_contacts_link( $this->disp_params['item_start'], $this->disp_params['item_end'], $this->disp_params[ 'user_contacts_link' ], $this->disp_params[ 'user_contacts_link' ] );
		}
		if( $this->get_param('user_view_link_show') )
		{	// See profile - disp=user
			$tools_links .= get_user_tab_link( 'user', $this->disp_params['item_start'], $this->disp_params['item_end'], $this->disp_params[ 'user_view_link' ], $this->disp_params[ 'user_view_link' ] );
		}
		if( $this->get_param('user_profile_link_show') )
		{	// Edit profile - disp=profile
			$tools_links .= get_user_profile_link( $this->disp_params['item_start'], $this->disp_params['item_end'], $this->disp_params[ 'user_profile_link' ], $this->disp_params[ 'user_profile_link' ] );
		}
		if( $this->get_param('user_picture_link_show') )
		{	// Edit picture - disp=avatar
			$tools_links .= get_user_tab_link( 'avatar', $this->disp_params['item_start'], $this->disp_params['item_end'], $this->disp_params[ 'user_picture_link' ], $this->disp_params[ 'user_picture_link' ] );
		}
		if( $this->get_param('user_password_link_show') )
		{	// Edit password - disp=pwdchange
			$tools_links .= get_user_tab_link( 'pwdchange', $this->disp_params['item_start'], $this->disp_params['item_end'], $this->disp_params[ 'user_password_link' ], $this->disp_params[ 'user_password_link' ] );
		}
		if( $this->get_param('user_preferences_link_show') )
		{	// Edit preferences - disp=userprefs
			$tools_links .= get_user_tab_link( 'userprefs', $this->disp_params['item_start'], $this->disp_params['item_end'], $this->disp_params[ 'user_preferences_link' ], $this->disp_params[ 'user_preferences_link' ] );
		}
		if( $this->get_param('user_subs_link_show') )
		{	// Edit notifications - disp=subs
			$tools_links .= get_user_subs_link( $this->disp_params['item_start'], $this->disp_params['item_end'], $this->disp_params[ 'user_subs_link' ], $this->disp_params[ 'user_subs_link' ] );
		}
		if( $this->get_param('user_admin_link_show') )
		{	// Admin
			$tools_links .= get_user_admin_link( $this->disp_params['item_start'], $this->disp_params['item_end'], $this->disp_params[ 'user_admin_link' ] );
		}
		if( $this->get_param('user_logout_link_show') )
		{	// Logout
			$tools_links .= get_user_logout_link( $this->disp_params['item_start'], $this->disp_params['item_end'], $this->disp_params[ 'user_logout_link' ] );
		}

		if( empty( $tools_links ) )
		{	// No available links to display
			return false;
		}

		// User tools:
		echo $this->disp_params['block_start'];

		if( !empty( $this->disp_params['title'] ) )
		{	// Display title
			echo $this->disp_params['block_title_start'];
			echo $this->disp_params['title'];
			echo $this->disp_params['block_title_end'];
		}

		echo $this->disp_params['block_body_start'];

		echo $this->disp_params['list_start'];

		echo $tools_links;

		if( isset($this->BlockCache) )
		{	// Do NOT cache because some of these links are using a redirect_to param, which makes it page dependent.
			// Note: also beware of the source param.
			// so this will be cached by the PageCache; there is no added benefit to cache it in the BlockCache
			// (which could have been shared between several pages):
			$this->BlockCache->abort_collect();
		}

		echo $this->disp_params['list_end'];

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Blog, $current_User;

		return array(
				'wi_ID'   => $this->ID,					// Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID,			// Have the settings of the blog changed ? (ex: new owner, new skin)
				'loggedin' => (is_logged_in() ? 1 : 0),
				// fp> note: if things get tough in the future, use a per User caching scheme:
				// 'user_ID' => (is_logged_in() ? $current_User->ID : 0), // Has the current User changed?
			);
	}
}

?>