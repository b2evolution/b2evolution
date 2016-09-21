<?php
/**
 * This file implements the user_fields_Widget class.
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
class user_fields_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'user_fields' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'user-fields-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('User fields');
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
		return T_('Display user fields.');
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
				'before_group' => array(
					'type'         => 'html_input',
					'label'        => T_('Before group'),
					'note'         => T_('HTML text to display before fields group.'),
					'defaultvalue' => '<div>',
					'size'         => 100,
				),
				'before_group_title' => array(
					'type'         => 'html_input',
					'label'        => T_('Before group title'),
					'note'         => T_('HTML text to display before fields group title.'),
					'defaultvalue' => '<h3>',
					'size'         => 100,
				),
				'after_group_title' => array(
					'type'         => 'html_input',
					'label'        => T_('After group title'),
					'note'         => T_('HTML text to display after fields group title.'),
					'defaultvalue' => '</h3>',
					'size'         => 100,
				),
				'before_field_title' => array(
					'type'         => 'html_input',
					'label'        => T_('Before field title'),
					'note'         => T_('HTML text to display before field title.'),
					'defaultvalue' => '<p><strong>',
					'size'         => 100,
				),
				'after_field_title' => array(
					'type'         => 'html_input',
					'label'        => T_('After field title'),
					'note'         => T_('HTML text to display after field title.'),
					'defaultvalue' => ':</strong> ',
					'size'         => 100,
				),
				'before_field_value' => array(
					'type'         => 'html_input',
					'label'        => T_('Before field value'),
					'note'         => T_('HTML text to display before field value.'),
					'defaultvalue' => '',
					'size'         => 100,
				),
				'after_field_value' => array(
					'type'         => 'html_input',
					'label'        => T_('After field value'),
					'note'         => T_('HTML text to display after field value.'),
					'defaultvalue' => '</p>',
					'size'         => 100,
				),
				'after_group' => array(
					'type'         => 'html_input',
					'label'        => T_('After group'),
					'note'         => T_('HTML text to display after fields group.'),
					'defaultvalue' => '</div>',
					'size'         => 100,
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

		// Load the user fields:
		$target_User->userfields_load();

		if( empty( $target_User->userfields ) )
		{	// The fields of target user is empty, Nothing to display:
			return;
		}

		echo $this->disp_params['block_start'];

		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		$group_ID = 0;
		foreach( $target_User->userfields as $userfield )
		{
			if( $group_ID != $userfield->ufgp_ID )
			{	// Start new group:
				if( $group_ID > 0 )
				{	// End previous group:
					echo $this->get_param( 'after_group' );
				}
				echo $this->get_param( 'before_group' );
				// Group title:
				echo $this->get_param( 'before_group_title' );
				echo $userfield->ufgp_name;
				echo $this->get_param( 'after_group_title' );
			}

			if( $userfield->ufdf_type == 'text' )
			{	// Convert textarea values to html format:
				$userfield->uf_varchar = nl2br( $userfield->uf_varchar );
			}

			$userfield_icon = '';
			if( ! empty( $userfield->ufdf_icon_name ) )
			{	// Field Icon:
				$userfield_icon = '<span class="'.$userfield->ufdf_icon_name.' ufld_'.$userfield->ufdf_code.' ufld__textcolor"></span> ';
			}

			// Field title:
			echo $this->get_param( 'before_field_title' )
				.$userfield_icon.$userfield->ufdf_name
				.$this->get_param( 'after_field_title' );

			// Field value:
			echo $this->get_param( 'before_field_value' )
				.$userfield->uf_varchar
				.$this->get_param( 'after_field_value' );

			$group_ID = $userfield->ufgp_ID;
		}
		if( $group_ID > 0 )
		{	// End group if user fields are exist:
			echo $this->get_param( 'after_group' );
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