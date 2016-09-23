<?php
/**
 * This file implements the user_action_Widget class.
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

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class user_action_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'user_action' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'user-action-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('User action');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		$button_options = $this->get_param_definitions( array() );
		$button_options = $button_options['button']['options'];
		return format_to_output( isset( $button_options[ $this->disp_params['button'] ] ) ? $button_options[ $this->disp_params['button'] ] : $this->get_name() );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display user action button.');
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
				'button' => array(
					'type'    => 'select',
					'label'   => T_('Action button'),
					'note'    => '',
					'options' => array(
							'edit_profile'    => T_('Edit my profile'),
							'send_message'    => T_('Send Message'),
							'add_contact'     => T_('Add to Contacts'),
							'block_report'    => T_('Block Contact / Report User'),
							'edit_backoffice' => T_('Edit in Back-Office'),
							'delete'          => T_('Delete / Delete Spammer'),
						),
					'defaultvalue' => 'edit_profile',
				),
			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Blog, $current_User;

		$this->init_display( $params );

		if( ! ( $target_User = & $this->get_target_User() ) )
		{	// The target user is not detected, Nothing to display:
			return true;
		}

		$r = '';

		switch( $this->get_param( 'button' ) )
		{
			case 'edit_profile':
				// Edit my profile:
				if( is_logged_in() && $current_User->ID == $target_User->ID )
				{	// Only if the logged in user is viewing own profile:
					$r = '<a href="'.url_add_param( $Blog->get( 'url' ), 'disp=profile' ).'">'
							.'<button type="button" class="btn btn-primary">'.T_('Edit my profile').'</button>'
						.'</a>';
				}
				break;

			case 'send_message':
				// Send Message:
				if( ! is_logged_in() || $current_User->ID != $target_User->ID )
				{	// Only if user is viewing other profile:
					$msgform_url = $target_User->get_msgform_url( $Blog->get( 'msgformurl' ) );
					if( ! empty( $msgform_url ) )
					{	// And user can send a message to the viewing user:
						$msgform_url = url_add_param( $msgform_url, 'msg_type=PM' );
						$r = '<a href="'.$msgform_url.'"><button type="button" class="btn btn-default">'.T_('Send Message').'</button></a>';
					}
				}
				break;

			case 'add_contact':
				// Add to Contacts:
				if( is_logged_in() && ( $current_User->ID != $target_User->ID ) &&
						$current_User->check_perm( 'perm_messaging', 'reply' ) &&
						$current_User->check_status( 'can_edit_contacts' ) )
				{	// User is logged in, has messaging access permission and is not the same user as displayed user:
					$is_contact = check_contact( $target_User->ID );
					if( $is_contact === NULL )
					{	// User is not in current User contact list, so allow "Add to my contacts" action:
						$button_class = 'btn-default';
						$button_title = T_('Add to Contacts');
					}
					elseif( $is_contact === false )
					{	// User is blocked:
						$button_class = 'btn-danger';
						$button_title = T_('Edit Blocked Contact');
					}
					else
					{ // User is on current User contact list
						$button_class = 'btn-success';
						$button_title = T_('Edit Contact');
					}
					$r = '<button type="button" class="btn '.$button_class.'" onclick="return user_contact_groups( '.$target_User->ID.' )">'.$button_title.'</button>';
				}
				break;

			case 'block_report':
				$buttons = array();

				// Block Contact:
				if( is_logged_in() && ( $current_User->ID != $target_User->ID ) &&
						$current_User->check_perm( 'perm_messaging', 'reply' ) &&
						$current_User->check_status( 'can_edit_contacts' ) )
				{	// User is logged in, has messaging access permission and is not the same user as displayed user:
					$is_contact = check_contact( $target_User->ID );
					$contact_block_url = get_samedomain_htsrv_url().'action.php?mname=messaging&amp;disp=contacts&amp;user_ID='.$target_User->ID.'&amp;redirect_to='.rawurlencode( regenerate_url() ).'&amp;'.url_crumb( 'messaging_contacts' );
					if( $is_contact === NULL || $is_contact === true )
					{	// Display a button to block user:
						$buttons[] = '<a href="'.$contact_block_url.'&action=block" class="btn btn-warning">'
								.'<button type="button">'.T_('Block Contact').'</button>'
							.'</a>';
					}
					else
					{	// Display a button to unblock user:
						$buttons[] = '<a href="'.$contact_block_url.'&action=unblock" class="btn btn-danger">'
								.'<button type="button">'.T_('Unblock Contact').'</button>'
							.'</a>';
					}
				}

				// Report User:
				if( is_logged_in() && ( $current_User->ID != $target_User->ID ) &&
						$current_User->check_status( 'can_report_user' ) )
				{	// Current user must be logged in, cannot report own account, and must has a permission to report:
					// Get current User report from edited User:
					$current_report = get_report_from( $target_User->ID );
					if( $current_report == NULL )
					{	// Current User didn't add any report from this user yet:
						$buttons[] = '<button type="button" class="btn btn-warning" onclick="return user_report( '.$target_User->ID.' )">'.T_('Report User').'</button>';
					}
					else
					{
						$buttons[] = '<button type="button" class="btn btn-danger" onclick="return user_report( '.$target_User->ID.' )">'.T_('Edit Reported User').'</button>';
					}
				}

				$r = implode( "\n", $buttons );
				break;

			case 'edit_backoffice':
				// Edit in Back-Office:
				if( is_logged_in() &&
						$current_User->can_moderate_user( $target_User->ID ) &&
						$current_User->check_status( 'can_access_admin' ) &&
						$current_User->check_perm( 'admin', 'restricted' )
					)
				{	// Current user must has an access to back-office and moderate the target user:
					global $admin_url;
					$r = '<a href="'.url_add_param( $admin_url, 'ctrl=user&amp;user_ID='.$target_User->ID ).'">'
							.'<button type="button" class="btn btn-primary">'.T_('Edit in Back-Office').'</button>'
						.'</a>';
				}
				break;

			case 'delete':
				// Delete & Delete Spammer:
				if( is_logged_in() &&
						$target_User->ID != 1 &&
						$current_User->ID != $target_User->ID &&
						$current_User->check_status( 'can_access_admin' ) &&
						$current_User->check_perm( 'admin', 'restricted' &&
						$current_User->check_perm( 'users', 'edit' ) )
					)
				{	// Current user must has an access to back-office and delete the target user:
					global $admin_url;
					$r = '<a href="'.url_add_param( $admin_url, 'ctrl=users&amp;action=delete&amp;user_ID='.$target_User->ID.'&amp;'.url_crumb( 'user' ) ).'" class="btn btn-danger">'
							.'<button type="button">'.T_('Delete').'</button>'
						.'</a>'
						."\n"
						.'<a href="'.url_add_param( $admin_url, 'ctrl=users&amp;action=delete&amp;deltype=spammer&amp;user_ID='.$target_User->ID.'&amp;'.url_crumb( 'user' ) ).'" class="btn btn-danger">'
							.'<button type="button">'.T_('Delete Spammer').'</button>'
						.'</a>';
				}
				break;
		}

		$r = utf8_trim( $r );

		if( empty( $r ) )
		{	// The requested user action button is not allowed, Nothing to display:
			return true;
		}

		echo $this->disp_params['block_start'];

		echo $this->disp_params['block_body_start'];

		echo $r;

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
		global $Blog;

		$cache_keys = array(
				'wi_ID'       => $this->ID, // Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID, // Have the settings of the blog changed ? (ex: new owner, new skin)
			);

		if( $target_User = & $this->get_target_User() )
		{
			$cache_keys['user_ID'] = $target_User->ID; // Has the target User changed? (name, avatar, etc..)
		}

		return $cache_keys;
	}
}

?>