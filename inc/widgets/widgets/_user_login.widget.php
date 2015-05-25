<?php
/**
 * This file implements the user_login_Widget class.
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
load_class( '_core/model/dataobjects/_dataobjectlist2.class.php', 'DataObjectList2' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class user_login_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function user_login_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'user_login' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'user-log-in-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('User log in');
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
		return T_('Display user login form & greeting.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		load_funcs( 'files/model/_image.funcs.php' );

		$r = array_merge( array(
				'title' => array(
					'label' => T_('Block title'),
					'note' => T_('Title to display in your skin.'),
					'size' => 40,
					'defaultvalue' => T_('User log in'),
				),
				// Password
				'password_link_show' => array(
					'label' => T_( 'Password recovery link'),
					'note' => T_( 'Show link' ),
					'type' => 'checkbox',
					'defaultvalue' => 1,
				),
				'password_link' => array(
					'size' => 30,
					'note' => T_( 'Link text to display' ),
					'type' => 'text',
					'defaultvalue' => T_( 'Lost password?' ),
				),
				// Register
				'register_link_show' => array(
					'label' => T_( 'Register link'),
					'note' => T_( 'Show link' ),
					'type' => 'checkbox',
					'defaultvalue' => 1,
				),
				'register_link' => array(
					'size' => 30,
					'note' => T_( 'Link text to display' ),
					'type' => 'text',
					'defaultvalue' => T_( 'No account yet? Register here &raquo;' ),
				),
				// Picture
				'profile_picture_size' => array(
					'label' => T_( 'Profile picture'),
					'note' => '',
					'type' => 'select',
					'options' => get_available_thumb_sizes( T_('none') ),
					'defaultvalue' => 'crop-top-32x32',
				),
				// Group
				'group_show' => array(
					'label' => T_( 'User group'),
					'note' => T_( 'Show user group' ),
					'type' => 'checkbox',
					'defaultvalue' => 1,
				),
				'group_text' => array(
					'size' => 30,
					'note' => T_( 'Group text to display' ),
					'type' => 'text',
					'defaultvalue' => T_( 'Your group: $group$' ),
				),
				// Level
				'level_show' => array(
					'label' => T_( 'User level'),
					'note' => T_( 'Show user level' ),
					'type' => 'checkbox',
					'defaultvalue' => 1,
				),
				'level_text' => array(
					'size' => 30,
					'note' => T_( 'Level text to display' ),
					'type' => 'text',
					'defaultvalue' => T_( 'Your level: $level$' ),
				),
				// Greeting
				'greeting_show' => array(
					'label' => T_( 'Greeting'),
					'note' => T_( 'Show greeting' ),
					'type' => 'checkbox',
					'defaultvalue' => 1,
				),
				'greeting_text' => array(
					'size' => 30,
					'note' => T_( 'Greeting text to display' ),
					'type' => 'text',
					'defaultvalue' => T_( 'Hello $login$!' ),
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{ // Set default blockcache to false and disable this setting because caching is never allowed for this widget
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Request all required css and js files for this widget
	 */
	function request_required_files()
	{
		global $Settings, $Plugins;

		//get required js files for _widget_login.form
		$transmit_hashed_password = (bool)$Settings->get('js_passwd_hashing') && !(bool)$Plugins->trigger_event_first_true('LoginAttemptNeedsRawPassword');
		if( $transmit_hashed_password )
		{ // Include JS for client-side password hashing:
			require_js( 'build/sha1_md5.bmin.js', 'blog' );
		}
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Blog, $redirect_to;

		if( get_param( 'disp' ) == 'login' )
		{	// No display a duplicate form for inskin login mode
			return false;
		}

		$this->init_display( $params );

		if( isset( $this->BlockCache ) )
		{	// Do NOT cache some of these links are using a redirect_to param, which makes it page dependent.
			// Note: also beware of the source param.
			// so this will be cached by the PageCache; there is no added benefit to cache it in the BlockCache
			// (which could have been shared between several pages):
			$this->BlockCache->abort_collect();
		}

		echo $this->disp_params['block_start'];

		if( ! is_logged_in() )
		{ // Login form:
			$source = 'user_login_widget';
			if( empty( $redirect_to ) )
			{
				$redirect_to = regenerate_url( '', '', '', '&' );
			}

			$this->disp_title();

			echo $this->disp_params['block_body_start'];

			// display widget login form
			require skin_template_path( '_widget_login.form.php' );

			echo $this->disp_params['block_body_end'];
		}
		else
		{ // Display a greeting text
			global $current_User;

			echo $this->disp_params['block_body_start'];

			if( $this->get_param('profile_picture_size') != '' )
			{	// Display profile picture
				echo $current_User->get_avatar_imgtag( $this->disp_params['profile_picture_size'], 'avatar', 'middle' );
			}

			if( $this->get_param('greeting_show') )
			{	// Display greeting text
				$user_login = $current_User->get_identity_link( array( 'link_text' => 'name', 'display_bubbletip' => false ) );
				echo ' <strong class="greeting">'.str_replace( '$login$', $user_login, $this->get_param('greeting_text') ).'</strong>';
			}

			if( $this->get_param('group_show') )
			{	// Display user group
				$user_Group = $current_User->get_Group();
				echo '<p class="user_group">'
					.str_replace( '$group$', $user_Group->get( 'name' ), $this->get_param('group_text') )
					.'</p>';
			}

			if( $this->get_param('level_show') )
			{	// Display user group
				echo '<p class="user_level">'
					.str_replace( '$level$', $current_User->get( 'level' ), $this->get_param('level_text') )
					.'</p>';
			}

			echo $this->disp_params['block_body_end'];
		}

		echo $this->disp_params['block_end'];

		return true;
	}
}

?>