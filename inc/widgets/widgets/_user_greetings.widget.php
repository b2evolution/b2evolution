<?php
/**
 * This file implements the user_greetings_Widget class.
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
class user_greetings_Widget extends ComponentWidget
{
	var $icon = 'user';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'user_greetings' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'user-greetings-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('User greetings');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( $this->disp_params['title'] );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display user greetings.');
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
					'defaultvalue' => '',
				),
				// Picture
				'profile_picture_size' => array(
					'label' => T_('Profile picture'),
					'note' => '',
					'type' => 'select',
					'options' => get_available_thumb_sizes( T_('none') ),
					'defaultvalue' => 'crop-top-32x32',
				),
				// Group
				'group_begin_line' => array(
					'type' => 'begin_line',
					'label' => T_('User group'),
				),
				'group_show' => array(
					'type' => 'checkbox',
					'defaultvalue' => 1,
				),
				'group_text' => array(
					'label' => T_('Show as:'),
					'size' => 25,
					'type' => 'text',
					'defaultvalue' => T_( 'Your group: $group$' ),
				),
				'group_end_line' => array(
					'type' => 'end_line',
				),
				// Level
				'level_begin_line' => array(
					'type' => 'begin_line',
					'label' => T_('User level'),
				),
				'level_show' => array(
					'type' => 'checkbox',
					'defaultvalue' => 1,
				),
				'level_text' => array(
					'label' => T_('Show as:'),
					'size' => 25,
					'type' => 'text',
					'defaultvalue' => T_( 'Your level: $level$' ),
				),
				'level_end_line' => array(
					'type' => 'end_line',
				),
				// Greeting
				'greeting_begin_line' => array(
					'type' => 'begin_line',
					'label' => T_('Greeting'),
				),
				'greeting_show' => array(
					'type' => 'checkbox',
					'defaultvalue' => 1,
				),
				'greeting_text' => array(
					'label' => T_('Show as:'),
					'size' => 25,
					'type' => 'text',
					'defaultvalue' => sprintf( T_( 'Hello %s!' ), '$login$' ),
				),
				'greeting_end_line' => array(
					'type' => 'end_line',
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
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		if( ! is_logged_in() )
		{	// Don't display because user is not logged in yet:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because user is not logged in.' );
			return false;
		}

		global $current_User;

		$this->init_display( $params );

		echo $this->disp_params['block_start'];

		$this->disp_title();

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

		echo $this->disp_params['block_end'];

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

		$cache_keys = array(
				'wi_ID'       => $this->ID, // Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID, // Have the settings of the blog changed ? (ex: new owner, new skin)
				'user_ID'     => ( is_logged_in() ? $current_User->ID : 0 ), // Has the current User changed?
			);

		return $cache_keys;
	}
}

?>