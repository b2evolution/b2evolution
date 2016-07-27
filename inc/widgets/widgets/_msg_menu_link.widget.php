<?php
/**
 * This file implements the msg_menu_link_Widget class.
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

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

global $msg_menu_link_widget_link_types;
$msg_menu_link_widget_link_types = array(
		'messages' => T_( 'Messages' ),
		'contacts' => T_( 'Contacts' ),
	);

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class msg_menu_link_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'msg_menu_link' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'messaging-menu-link-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Messaging Menu link');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		global $msg_menu_link_widget_link_types;

		$this->load_param_array();

		if( !empty($this->param_array['link_type']) )
		{	// Messaging or Contacts
			return sprintf( T_( '%s link' ), $msg_menu_link_widget_link_types[$this->param_array['link_type']] );
		}

		return $this->get_name();
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Messages or Contacts menu entry/link');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $msg_menu_link_widget_link_types, $admin_url;

		// Try to get collection that is used for messages on this site:
		$msg_Blog = & get_setting_Blog( 'msg_blog_ID' );

		$r = array_merge( array(
				'link_type' => array(
					'label' => T_( 'Link Type' ),
					'note' => T_('What do you want to link to?'),
					'type' => 'select',
					'options' => $msg_menu_link_widget_link_types,
					'defaultvalue' => 'messages',
					'onchange' => '
						var curr_link_type = this.value;
						var show_badge = jQuery("[id$=\'_set_show_badge\']");
						if( curr_link_type == "messages" )
						{
							show_badge.removeAttr(\'disabled\');
							show_badge.attr( \'checked\', \'checked\' );
						}
						else
						{
							show_badge.attr( \'disabled\', \'disabled\' );
							show_badge.removeAttr(\'checked\');
						};'
				),
				'link_text' => array(
					'label' => T_( 'Link text' ),
					'note' => T_('Text to use for the link (leave empty for default).'),
					'type' => 'text',
					'size' => 20,
					'defaultvalue' => '',
				),
				'blog_ID' => array(
					'label' => T_('Collection ID'),
					'note' => T_('Leave empty for current collection.')
						.( $msg_Blog ? ' <span class="red">'.sprintf( T_('The site is <a %s>configured</a> to always use collection %s for profiles/messaging functions.'),
								'href="'.$admin_url.'?ctrl=collections&amp;tab=site_settings"',
								'<b>'.$msg_Blog->get( 'name' ).'</b>' ).'</span>' : '' ),
					'type' => 'integer',
					'allow_empty' => true,
					'size' => 5,
					'defaultvalue' => '',
					'disabled' => $msg_Blog ? 'disabled' : false,
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
				'show_to' => array(
					'label' => T_( 'Show to' ),
					'note' => '',
					'type' => 'radio',
					'options' => array( array( 'any', T_( 'All users') ),
										array( 'loggedin', T_( 'Logged in users' ) ),
										array( 'perms', T_( 'Users with messaging permissions only' ) ) ),
					'defaultvalue' => 'perms',
				),
				'show_badge' => array(
					'label' => T_( 'Show Badge' ),
					'note' => T_( 'Show a badge with the count of unread messages.' ),
					'type' => 'checkbox',
					'defaultvalue' => true,
				),
			), parent::get_param_definitions( $params )	);

		// Do not modify anything during update because the editing form contains all of the required modifications
		if( !isset( $params['for_updating'] ) )
		{ // Not called from the update process
			// Turn off allow blockcache by default, because it is forbidden in case of messages
			// Note: we may call $this->get_param() only if this function was not called from there. This way we prevent infinite recursion/loop.
			$link_type = ( empty( $this->params ) || isset( $params['infinite_loop'] ) ) ? 'messages' : $this->get_param( 'link_type', true );
			if( $link_type == 'contacts' )
			{
				$r['show_badge']['defaultvalue'] = false;
				$r['show_badge']['disabled'] = 'disabled';
			}
		}

		if( isset( $r['allow_blockcache'] ) )
		{ // Disable "allow blockcache" because this widget uses the selected items
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

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

		// Disable "allow blockcache" because this widget uses the selected items
		$this->disp_params['allow_blockcache'] = 0;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Blog, $current_User, $disp;

		$this->init_display( $params );

		$blog_ID = intval( $this->disp_params['blog_ID'] );
		if( $blog_ID > 0 )
		{ // Try to use blog from widget setting
			$BlogCache = & get_BlogCache();
			$current_Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );
		}

		if( empty( $current_Blog ) )
		{ // Blog is not defined in setting or it doesn't exist in DB
			global $Collection, $Blog;
			// Use current blog
			$current_Blog = & $Blog;
		}

		if( empty( $current_Blog ) )
		{ // Don't use this widget without current collection:
			return false;
		}

		if( $this->disp_params['visibility'] == 'access' && ! $current_Blog->has_access() )
		{	// Don't use this widget because current user has no access to the collection:
			return false;
		}

		switch( $this->disp_params['show_to'] )
		{
			case 'any':
				break;
			case 'loggedin':
				if( !is_logged_in() )
				{
					return false;
				}
				break;
			case 'perms':
				if( !is_logged_in() || !$current_User->check_perm( 'perm_messaging', 'reply', false ) )
				{
					return false;
				}
				break; // display
			case 'default':
				debug_die( 'Invalid params!' );
		}

		// Default link class
		$link_class = $this->disp_params['link_default_class'];

		// Allow to higlight current menu item only when it is linked to current collection:
		$highlight_current = ( $current_Blog->ID == $Blog->ID );

		switch( $this->disp_params[ 'link_type' ] )
		{
			case 'messages':
				$url = $current_Blog->get( 'threadsurl' );
				$text = T_( 'Messages' );
				// set allow blockcache to 0, this way make sure block cache is never allowed for messages
				$this->disp_params[ 'allow_blockcache' ] = 0;
				// Check if current menu item must be highlighted:
				$highlight_current = ( $highlight_current && ( ( $disp == 'threads' && ( ! isset( $_GET['disp'] ) || $_GET['disp'] != 'msgform' ) ) || $disp == 'messages' ) );
				break;

			case 'contacts':
				$url = $current_Blog->get( 'contactsurl' );
				$text = T_( 'Contacts' );
				// set show badge to 0, this way make sure badge won't be displayed
				$this->disp_params[ 'show_badge' ] = 0;
				// Check if current menu item must be highlighted:
				$highlight_current = ( $highlight_current && $disp == 'contacts' );
				break;
		}

		if( !empty( $this->disp_params[ 'link_text' ] ) )
		{
			$text = $this->disp_params[ 'link_text' ];
		}

		$badge = '';
		if( ( $this->disp_params[ 'show_badge' ] ) )
		{	// Show badge with count of uread messages:
			$unread_messages_count = get_unread_messages_count();
			if( $unread_messages_count > 0 )
			{	// If at least one unread message:
				$badge = ' <span class="badge badge-important">'.$unread_messages_count.'</span>';
				if( isset( $this->BlockCache ) )
				{	// Do not cache if bage is displayed because the number of unread messages are always changing:
					$this->BlockCache->abort_collect();
				}
			}
		}

		echo $this->disp_params['block_start'];
		echo $this->disp_params['block_body_start'];
		echo $this->disp_params['list_start'];

		if( $highlight_current )
		{	// Use template and class to highlight current menu item:
			$link_class = $this->disp_params['link_selected_class'];
			echo $this->disp_params['item_selected_start'];
		}
		else
		{	// Use normal template:
			echo $this->disp_params['item_start'];
		}
		echo '<a href="'.$url.'" class="'.$link_class.'">'.$text.$badge.'</a>';
		if( $highlight_current )
		{	// Use template to highlight current menu item:
			echo $this->disp_params['item_selected_end'];
		}
		else
		{	// Use normal template:
			echo $this->disp_params['item_end'];
		}

		echo $this->disp_params['list_end'];
		echo $this->disp_params['block_body_end'];
		echo $this->disp_params['block_end'];

		return true;
	}
}

?>