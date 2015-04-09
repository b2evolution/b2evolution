<?php
/**
 * This file implements the user_organizations_Widget class.
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
class user_organizations_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function user_organizations_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'user_organizations' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Organization Members');
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
		return T_('Display organization members.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		// Load Userfield class and all fields:
		load_class( 'users/model/_userfield.class.php', 'Userfield' );
		$UserFieldCache = & get_UserFieldCache();
		$UserFieldCache->load_all();
		$user_fields = $UserFieldCache->get_option_array();

		// Set default user field as "About me"
		$default_user_field_ID = 0;
		foreach( $user_fields as $user_field_ID => $user_field_name )
		{
			if( $user_field_name == 'About me' )
			{
				$default_user_field_ID = $user_field_ID;
				break;
			}
		}

		$r = array_merge( array(
				'title' => array(
					'label' => T_('Block title'),
					'note' => T_('Title to display in your skin.'),
					'size' => 40,
					'defaultvalue' => T_('Our Awesome Team'),
				),
				'org_ID' => array(
					'label' => T_('Team ID'),
					'note' => '',
					'type' => 'integer',
					'size' => 40,
					'defaultvalue' => 1,
				),
				'field_ID' => array(
					'label' => T_('Extra Info Field'),
					'note' => T_('Select what user field should be displayed'),
					'type' => 'select',
					'options' => array( '' => T_('None') ) + $user_fields,
					'defaultvalue' => $default_user_field_ID,
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
		global $DB, $Item, $Blog;

		$this->init_display( $params );

		echo $this->disp_params['block_start'];

		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		$org_ID = intval( $this->disp_params['org_ID'] );
		if( $org_ID > 0 )
		{
			$OrganizationCache = & get_OrganizationCache();
			$Organization = & $OrganizationCache->get_by_ID( $org_ID, false, false );
		}

		if( empty( $Organization ) )
		{ // No organization found
			global $Messages;
			$Messages->add( T_('Requested Team was not found.'), 'error' );
			$Messages->display();
		}
		else
		{
			// Get all users of the selected organization
			$users = $Organization->get_users();

			if( count( $users ) )
			{
				$field_ID = intval( $this->disp_params['field_ID'] );
				foreach( $users as $org_User )
				{
					echo '<div class="col-lg-4 col-sm-6 text-center">';

					// Profile picture
					echo $org_User->get_avatar_imgtag( 'crop-top-320x320', 'img-circle img-responsive img-center' );

					// Full name
					echo '<h3>'.$org_User->get( 'fullname' ).'</h3>';

					// User links
					$url_fields = $org_User->userfields_by_type( 'url' );
					if( count( $url_fields ) )
					{
						echo '<div class="widget--social-media-links">';
						foreach( $url_fields as $field )
						{
							echo '<a href="'.$field->uf_varchar.'">'
									.'<span class="'.$field->ufdf_icon_name.'"></span>'
								.'</a>';
						}
						echo '</div>';
					}

					// Info
					if( $field_ID > 0 && ( $field_value = $org_User->userfield_value_by_ID( $field_ID ) ) )
					{
						echo '<p>'.$field_value.'</p>';
					}

					echo '</div>';
				}
			}
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
		global $Blog;

		return array(
				'wi_ID'       => $this->ID, // Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID, // Have the settings of the blog changed ? (ex: new owner, new skin)
			);
	}
}

?>