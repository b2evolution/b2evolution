<?php
/**
 * This file implements the user_login_Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
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
	var $icon = 'key';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'user_login' );
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
					'label' => T_('Password recovery link'),
					'note' => T_('Show link'),
					'type' => 'checkbox',
					'defaultvalue' => 1,
				),
				'password_link_text' => array(
					'size' => 30,
					'note' => T_( 'Link text to display' ),
					'type' => 'text',
					'defaultvalue' => T_('Lost your password?'),
				),
				'password_link_class' => array(
					'label' => T_('Password recovery link class'),
					'size' => 40,
					'defaultvalue' => '',
				),
				// Log in
				'login_button_text' => array(
					'label' => T_('Login button'),
					'size' => 30,
					'note' => T_('Link text to display'),
					'type' => 'text',
					'defaultvalue' => T_('Log in!'),
				),
				'login_button_class' => array(
					'label' => T_('Login button class'),
					'size' => 40,
					'defaultvalue' => 'btn btn-success',
				),
				// Register
				'register_link_show' => array(
					'label' => T_('Register link'),
					'note' => T_('Show link'),
					'type' => 'checkbox',
					'defaultvalue' => 1,
				),
				'register_link_text' => array(
					'size' => 30,
					'note' => T_('Link text to display'),
					'type' => 'text',
					'defaultvalue' => T_('Register &raquo;'),
				),
				'register_link_class' => array(
					'label' => T_('Register link class'),
					'size' => 40,
					'defaultvalue' => 'btn btn-primary pull-right',
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
		if( can_use_hashed_password() )
		{ // Include JS for client-side password hashing:
			require_js_defer( 'build/sha1_md5.bmin.js', 'blog' );
		}
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		if( is_logged_in() )
		{	// Don't display because user is already logged in:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because user is already logged in.' );
			return false;
		}

		if( get_param( 'disp' ) == 'login' )
		{	// Don't display a duplicate form for inskin login mode:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden in order to don\'t duplicate same form on disp=login.' );
			return false;
		}

		global $Collection, $Blog, $Settings;

		$this->init_display( $params );

		if( isset( $this->BlockCache ) )
		{	// Do NOT cache some of these links are using a redirect_to param, which makes it page dependent.
			// Note: also beware of the source param.
			// so this will be cached by the PageCache; there is no added benefit to cache it in the BlockCache
			// (which could have been shared between several pages):
			$this->BlockCache->abort_collect();
		}

		echo $this->disp_params['block_start'];

		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		// Set vars for widget login form:
		$source = 'user_login_widget';
		$redirect_to = $Settings->get( 'redirect_to_after_login' );
		if( empty( $redirect_to ) )
		{	// Set redirect URL if it is not set in general settings:
			global $disp;
			if( $disp == 'access_requires_login' || $disp == 'content_requires_login' )
			{	// Use a collection main page for disp "access_requires_login" and "content_requires_login":
				$redirect_to = $Blog->get( 'url' );
			}
			else
			{	// Use a current page URL after log in action:
				$redirect_to = regenerate_url( '', '', '', '&' );
			}
		}

		// Display a form to log in:
		$params = array(
			'display_form_messages' => false,
			'login_form_inskin'     => false,
			'login_form_footer'     => false,
			'abort_link_position'   => false,
			'login_button_text'     => $this->get_param( 'login_button_text' ),
			'login_button_class'    => $this->get_param( 'login_button_class' ),
			'display_lostpass_link' => $this->get_param( 'password_link_show' ),
			'lostpass_link_text'    => $this->get_param( 'password_link_text' ),
			'lostpass_link_class'   => $this->get_param( 'password_link_class' ),
			'display_reg_link'      => $this->get_param( 'register_link_show' ),
			'reg_link_text'         => $this->get_param( 'register_link_text' ),
			'reg_link_class'        => $this->get_param( 'register_link_class' ),
			'transmit_hashed_password'       => can_use_hashed_password(),
			'get_widget_login_hidden_fields' => true,
		);
		require skin_template_path( '_login.disp.php' );

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}
}

?>