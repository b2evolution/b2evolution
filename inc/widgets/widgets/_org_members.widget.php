<?php
/**
 * This file implements the org_members_Widget class.
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
class org_members_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'org_members' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'organization-members-widget' );
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
		return T_('Display the members of an organization.');
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
		$user_fields = array();
		foreach( $UserFieldCache->cache as $UserField )
		{
			$user_fields[ $UserField->get( 'code' ) ] = $UserField->get_name();
		}
		
		load_funcs( 'files/model/_image.funcs.php' );

		$r = array_merge( array(
				'title' => array(
					'label' => T_('Block title'),
					'note' => T_('Title to display in your skin.'),
					'size' => 40,
					'defaultvalue' => T_('Our Awesome Team'),
				),
				'org_ID' => array(
					'label' => T_('Organization ID'),
					'note' => sprintf( T_('ID of the <a %s>organization</a> to display.'), 'href="?ctrl=organizations"' ),
					'type' => 'integer',
					'size' => 3,
					'defaultvalue' => 1,
				),
				'layout' => array(
					'label' => T_('Layout'),
					'note' => T_('How to lay out the members'),
					'type' => 'select',
					'options' => array(
							'rwd'  => T_( 'RWD Blocks' ),
							'flow' => T_( 'Flowing Blocks' ),
							'list' => T_( 'List' ),
						),
					'defaultvalue' => 'rwd',
				),
				'rwd_block_class' => array(
					'label' => T_('RWD block class'),
					'note' => T_('Specify the responsive column classes you want to use.'),
					'size' => 60,
					'defaultvalue' => 'col-lg-4 col-md-6 col-sm-6 col-xs-12',
				),
				'thumb_size' => array(
					'label' => T_('Image size'),
					'note' => T_('Cropping and sizing of thumbnails'),
					'type' => 'select',
					'options' => get_available_thumb_sizes(),
					'defaultvalue' => 'crop-top-200x200',
				),
				'order_by' => array(
					'label' => T_('Order by'),
					'note' => T_('Field used to determine the order in which the members are displayed'),
					'type' => 'select',
					'options' => array(
							'user_id' => 'User ID', 
							'user_level' => 'User Level',
							'org_role' => 'Role in Organization',
							'username' => 'Username',
							'lastname' => 'Last Name, First Name',
							'firstname' => 'First Name, Last Name'
					),
					'defaultvalue' => 'user_id',
				),
				'link_profile' => array(
					'label' => T_('Link to profile'),
					'note' => T_('Check this to link each user to his profile.'),
					'type' => 'checkbox',
					'defaultvalue' => 1,
				),
				'display_role' => array(
					'label' => T_('Role in organization'),
					'note' => T_('Check this to display the role of the members in the organization'),
					'type' => 'checkbox',
					'defaultvalue' => 1,
				),
				'display_icons' => array(
					'label' => T_('Contact icons'),
					'note' => T_('Check this to display icons for User Field URLs with an icon.'),
					'type' => 'checkbox',
					'defaultvalue' => 1,
				),
				'icon_colors' => array(
					'label' => T_('Icon color'),
					'type' => 'checklist',
					'options' => array(
							array( 'text',      T_('Use for normal text'), 0 ),
							array( 'bg',        T_('Use for normal background'), 0 ),
							array( 'hovertext', T_('Use for hover text'), 0 ),
							array( 'hoverbg',   T_('Use for hover background'), 1/* default checked */ ),
						),
				),
				'field_code' => array(
					'label' => T_('Extra Info Field'),
					'note' => T_('Select what extra user field should be displayed.'),
					'type' => 'select',
					'options' => array( '' => T_('None') ) + $user_fields,
					'defaultvalue' => 'microbio',
				),
				'field_extra_lines' => array(
					'label' => T_('Lines of extra info'),
					'note' => T_('Use this to keep contact cards aligned.'),
					'defaultvalue' => '2',
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
		global $DB, $Item, $Blog, $thumbnail_sizes;

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
			$order_by = $this->disp_params['order_by'];
			$users = $Organization->get_users( $order_by, true );

			if( $this->disp_params['display_icons'] )
			{ // Initialise css classes for icons depending on widget setting and only when they are displayed
				$icon_colors_classes = '';
				if( ! empty( $this->disp_params['icon_colors'] ) )
				{ // If at least one color status is selected
					foreach( $this->disp_params['icon_colors'] as $class_name => $is_selected )
					{
						if( ! empty( $is_selected ) )
						{
							$icon_colors_classes .= ' ufld__'.$class_name.'color';
						}
					}
				}
			}

			if( count( $users ) )
			{
				echo $this->get_layout_start();

				$member_counter = 0;
				foreach( $users as $org_User )
				{
					echo $this->get_layout_item_start( $member_counter );

					$user_url = $this->disp_params['link_profile'] ? $org_User->get_userpage_url( $Blog->ID, true ) : '';

					if( ! empty( $user_url ) )
					{ // Display url to user page only when it is allowed by widget setting
						echo '<a href="'.$user_url.'" class="user_link">';
					}

					// Get image tag size based on thumb size param
					$tag_size = $thumbnail_sizes[ $this->disp_params['thumb_size'] ];
					$tag_size = $tag_size[1].'x'.$tag_size[2];

					// Profile picture
					echo $org_User->get_avatar_imgtag( $this->disp_params['thumb_size'], 'img-circle img-responsive img-center', '', false, '', '', $tag_size );

					if( ! empty( $user_url ) )
					{ // End of user link, see above
 						echo '</a>';

						echo '<a href="'.$user_url.'" class="user_link">';
					}

					// Full name
					echo '<h3 class="evo_user_name">'.$org_User->get( 'fullname' ).'</h3>';

					if( ! empty( $user_url ) )
					{ // End of user link, see above
						echo '</a>';
					}
					
					// Organizational role
					if( $this->disp_params['display_role'] == 1 )
					{
						$organizations_data = $org_User->get_organizations_data();
						echo '<div class="evo_org_role text-muted">'.$organizations_data[$org_ID]['role'].'</div>';
					}

					if( $this->disp_params['display_icons'] )
					{ // Display user links as icons
						$url_fields = $org_User->userfields_by_type( 'url' );
						echo '<div class="ufld_icon_links">';
						if( count( $url_fields ) )
						{
							
							foreach( $url_fields as $field )
							{
								echo '<a href="'.$field->uf_varchar.'"'.( empty( $icon_colors_classes ) ? '' : ' class="ufld_'.$field->ufdf_code.$icon_colors_classes.'"' ).'>'
										.'<span class="'.$field->ufdf_icon_name.'"></span>'
									.'</a>';
							}
						}
						echo '</div>';
					}

					// Info
					if( ! empty( $this->disp_params['field_code'] ) && ( $field_values = $org_User->userfield_values_by_code( $this->disp_params['field_code'] ) ) )
					{
						$field_extra_lines = intval( $this->disp_params['field_extra_lines'] );
						echo '<p class="user_field"'
							.( empty( $field_extra_lines ) ? '' :
									' style="height:'.( $field_extra_lines * 1.5 /* line-height */ ).'em;'
										.'-webkit-line-clamp:'.$field_extra_lines.'"' )
							.'>';
						foreach( $field_values as $f => $field_value )
						{
							if( $f > 0 )
							{ // Space between each field value
								echo ' ';
							}
							echo preg_replace( "/[\r\n]+/", ' ', $field_value );
						}
						echo '</p>';
					}

					$member_counter++;

					echo $this->get_layout_item_end( $member_counter );
				}

				echo $this->get_layout_end( $member_counter );
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