<?php
/**
 * This file implements the Online Users Widget class.
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
class online_users_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'online_users' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'online-users-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_( 'Online users' );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display currently online users.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array local params
	 */
	function get_param_definitions( $params )
	{
		load_funcs( 'files/model/_image.funcs.php' );

		$r = array_merge( array(
			'title' => array(
				'label' => T_( 'Block title' ),
				'note' => T_( 'Title to display in your skin.' ),
				'size' => 40,
				'defaultvalue' => T_( 'Online users' ),
			),
			'allow_anonymous' => array(
				'label' => T_( 'Allow for anonymous users' ),
				'note' => T_( 'Uncheck to display this widget only for logged in users' ),
				'type' => 'checkbox',
				'defaultvalue' => 1,
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
		global $DB, $Settings, $UserSettings, $localtimenow;

		if( ( !$this->get_param( 'allow_anonymous' ) ) && ( !is_logged_in() ) )
		{ // display only for logged in users
			return;
		}

		// load online Users
		$UserCache = & get_UserCache();
		$online_threshold = $localtimenow - ( 2 * $Settings->get( 'timeout_online' ) );
		$UserCache->load_where( 'user_lastseen_ts > '.$DB->quote( date2mysql( $online_threshold ).' AND user_status <> '.$DB->quote( 'closed' ) ) );

		$this->init_display( $params );

		// START DISPLAY:
		echo $this->disp_params['block_start'];

		// Display title if requested
		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		$r = '';

		while( ( $iterator_User = & $UserCache->get_next() ) != NULL )
		{ // Iterate through UserCache
			$user_lastseen_ts = mysql2timestamp( $iterator_User->get( 'lastseen_ts' ) );
			if( ( $user_lastseen_ts > $online_threshold )
				&& ( $UserSettings->get( 'show_online', $iterator_User->ID ) )
				&& ( !$iterator_User->check_status( 'is_closed' ) ) )
			{
				if( empty($r) )
				{ // first user
					$r .= $params['list_start'];
				}

				$r .= $params['item_start'];
				$r .= $iterator_User->get_identity_link( array( 'login_mask' => '$login$' ) );
				$r .= $params['item_end'];
			}
		}

		if( !empty( $r ) )
		{
			$r .= $params['list_end'];
			echo $r;
		}

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}
}