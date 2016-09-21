<?php
/**
 * This file implements the user_info_Widget class.
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
class user_info_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'user_info' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'user-info-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('User info');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		$info_options = $this->get_param_definitions( array() );
		$info_options = $info_options['info']['options'];
		return format_to_output( isset( $info_options[ $this->disp_params['info'] ] ) ? $info_options[ $this->disp_params['info'] ] : $this->get_name() );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display user info.');
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
					'defaultvalue' => '',
				),
				'info' => array(
					'type'    => 'select',
					'label'   => T_('What info to display'),
					'note'    => '',
					'options' => array(
							'name'       => T_('Name'),
							'nickname'   => T_('Nickname'),
							'login'      => T_('Login'),
							'gender_age' => T_('Gender & Age group'),
							'location'   => T_('Location'),
							'orgs'       => T_('Organizations'),
							'posts'      => T_('Number of posts'),
							'comments'   => T_('Comments'),
							'photos'     => T_('Photos'),
							'audio'      => T_('Audio'),
							'files'      => T_('Other files'),
							'spam'       => T_('Spam fighter score'),
						),
					'defaultvalue' => 'name',
				),
				'before_info' => array(
					'type'         => 'html_input',
					'label'        => T_('Before info'),
					'note'         => T_('HTML text to display before info value.'),
					'defaultvalue' => '',
					'size'         => 60,
				),
				'after_info' => array(
					'type'         => 'html_input',
					'label'        => T_('After info'),
					'note'         => T_('HTML text to display after info value.'),
					'defaultvalue' => '',
					'size'         => 60,
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
		global $Settings;

		$this->init_display( $params );

		if( ! ( $target_User = & $this->get_target_User() ) )
		{	// The target user is not detected, Nothing to display:
			return true;
		}

		$r = '';

		switch( $this->get_param( 'info' ) )
		{
			case 'name':
				// Name:
				if( $Settings->get( 'firstname_editing' ) != 'hidden' )
				{	// If first name is not hidden:
					$r = $target_User->get( 'firstname' );
				}
				if( $Settings->get( 'lastname_editing' ) != 'hidden' )
				{	// If last name is not hidden:
					$r .= ' '.$target_User->get( 'lastname' );
				}
				break;

			case 'nickname':
				// Nickname:
				if( $Settings->get( 'nickname_editing' ) != 'hidden' )
				{	// If nickname is not hidden:
					$r = $target_User->get( 'nickname' );
				}
				break;

			case 'login':
				// Login:
				$r = $target_User->get( 'login' );
				break;

			case 'gender_age':
				// Gender & Age group:
				$r = $target_User->get_gender();
				if( ! empty( $target_User->age_min ) || ! empty( $target_User->age_max ) )
				{
					if( ! empty( $r ) )
					{	// Separator between gender and age group:
						$r .= ' &bull; ';
					}
					$r .= sprintf( T_('%s years old'), $target_User->get( 'age_min' ).'-'.$target_User->get( 'age_max' ) );
				}
				break;

			case 'location':
				// Location:
				$location = array();
				if( ! empty( $target_User->city_ID ) && user_city_visible() )
				{ // Display city
					load_class( 'regional/model/_city.class.php', 'City' );
					$location[] = $target_User->get_city_name();
				}
				if( ! empty( $target_User->subrg_ID ) && user_subregion_visible() )
				{ // Display sub-region
					load_class( 'regional/model/_subregion.class.php', 'Subregion' );
					$location[] = $target_User->get_subregion_name();
				}
				if( ! empty( $target_User->rgn_ID ) && user_region_visible() )
				{ // Display region
					load_class( 'regional/model/_region.class.php', 'Region' );
					$location[] = $target_User->get_region_name();
				}
				if( ! empty( $target_User->ctry_ID ) && user_country_visible() )
				{ // Display country
					load_class( 'regional/model/_country.class.php', 'Country' );
					$location[] = $target_User->get_country_name();
				}
				if( ! empty( $location ) )
				{ // Display location only if at least one selected
					$r = '<span class="nowrap">'.implode( '</span>, <span class="nowrap">', $location ).'</span>';
				}
				break;

			case 'orgs':
				// Organizations:
				$user_organizations = $target_User->get_organizations();
				if( count( $user_organizations ) > 0 )
				{	// No organizations to display:
					$org_names = array();
					foreach( $user_organizations as $org )
					{
						if( empty( $org->url ) )
						{	// Display just a text:
							$org_names[] = $org->name;
						}
						else
						{	// Make a link for organization:
							$org_names[] = '<a href="'.$org->url.'" rel="nofollow" target="_blank">'.$org->name.'</a>';
						}
					}
					$r = implode( ' &middot; ', $org_names );
				}
				break;

			case 'posts':
				// Number of posts:
				$r = $target_User->get_reputation_posts();
				break;

			case 'comments':
				// Comments:
				$r = $target_User->get_reputation_comments();
				break;

			case 'photos':
				// Photos:
				$r = $target_User->get_reputation_files( array( 'file_type' => 'image' ) );
				break;

			case 'audio':
				// Audio:
				$r = $target_User->get_reputation_files( array( 'file_type' => 'audio' ) );
				break;

			case 'files':
				// Other files:
				$r = $target_User->get_reputation_files( array( 'file_type' => 'other' ) );
				break;

			case 'spam':
				// Spam fighter score:
				$r = $target_User->get_reputation_spam();
				break;
		}

		$r = utf8_trim( $r );

		if( empty( $r ) )
		{	// The requested user info is empty, Nothing to display:
			return true;
		}

		echo $this->disp_params['block_start'];

		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		echo $this->get_param( 'before_info' );

		echo $r;

		echo $this->get_param( 'after_info' );

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