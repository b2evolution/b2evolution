<?php
/**
 * This file implements the social_links_Widget class.
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
class social_links_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'social_links' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'social-links-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Free Social Links');
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
		return T_('Display social link icons of your choice.');
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
		$available_social_fields = $this->get_available_social_fields();
		$r = array_merge( array(
				'title' => array(
					'label' => T_('Block title'),
					'note' => T_('Title to display in your skin.'),
					'size' => 40,
					'defaultvalue' => '',
				),

				'link1' => array(
					'label' => T_('Link').' 1',
					'type' => 'select',
					'options' => $available_social_fields,
					'size' => 40,
					'defaultvalue' => ''
				),
				'link1_href' => array(
					'label' => sprintf( T_('Link %s URL'), 1 ),
					'size' => 80,
					'defaultvalue' => ''
				),
				'link2' => array(
					'label' => T_('Link').' 2',
					'type' => 'select',
					'options' => $available_social_fields,
					'size' => 40,
					'defaultvalue' => ''
				),
				'link2_href' => array(
					'label' => sprintf( T_('Link %s URL'), 2 ),
					'size' => 80,
					'defaultvalue' => ''
				),
				'link3' => array(
					'label' => T_('Link').' 3',
					'type' => 'select',
					'options' => $available_social_fields,
					'size' => 40,
					'defaultvalue' => ''
				),
				'link3_href' => array(
					'label' => sprintf( T_('Link %s URL'), 3 ),
					'size' => 80,
					'defaultvalue' => ''
				),
				'link4' => array(
					'label' => T_('Link').' 4',
					'type' => 'select',
					'options' => $available_social_fields,
					'size' => 40,
					'defaultvalue' => ''
				),
				'link4_href' => array(
					'label' => sprintf( T_('Link %s URL'), 4 ),
					'size' => 80,
					'defaultvalue' => ''
				),
				'link5' => array(
					'label' => T_('Link').' 5',
					'type' => 'select',
					'options' => $available_social_fields,
					'size' => 40,
					'defaultvalue' => ''
				),
				'link5_href' => array(
					'label' => sprintf( T_('Link %s URL'), 5 ),
					'size' => 80,
					'defaultvalue' => ''
				),
				'link6' => array(
					'label' => T_('Link').' 6',
					'type' => 'select',
					'options' => $available_social_fields,
					'size' => 40,
					'defaultvalue' => ''
				),
				'link6_href' => array(
					'label' => sprintf( T_('Link %s URL'), 6 ),
					'size' => 80,
					'defaultvalue' => ''
				),
				'link7' => array(
					'label' => T_('Link').' 7',
					'type' => 'select',
					'options' => $available_social_fields,
					'size' => 40,
					'defaultvalue' => ''
				),
				'link7_href' => array(
					'label' => sprintf( T_('Link %s URL'), 7 ),
					'size' => 80,
					'defaultvalue' => ''
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
		global $DB, $Item, $Collection, $Blog;

		$this->init_display( $params );

		// Initialise css classes for icons depending on widget setting
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

		$r = '';

		$social_fields = $this->get_selected_social_fields();
		if( count( $social_fields ) )
		{
			$r .= '<div class="ufld_icon_links">';
			for( $i = 1; $i <= 7; $i++ )
			{
				if( ! empty( $this->disp_params['link'.$i] ) && ! empty( $this->disp_params['link'.$i.'_href'] ) )
				{
					$r .= '<a href="'.$this->disp_params['link'.$i.'_href'].'"'.( empty( $icon_colors_classes ) ? '' : ' class="ufld_'.$social_fields[$this->disp_params['link'.$i]]->ufdf_code.$icon_colors_classes.'"' ).'>'
									.'<span class="'.$social_fields[$this->disp_params['link'.$i]]->ufdf_icon_name.'"></span>'
								.'</a>';
				}
			}
			$r .= '</div>';
		}

		if( empty( $r ) )
		{ // Nothing to display
			return true;
		}

		echo $this->disp_params['block_start'];

		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		echo $r;

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}


	function get_available_social_fields()
	{
		global $DB;

		$social_fields = $DB->get_results('
				SELECT ufdf_ID, "0" AS uf_ID, ufdf_type, ufdf_code, ufdf_name, ufdf_icon_name, "" AS uf_varchar, ufdf_required,
						ufdf_options, ufdf_suggest, ufdf_duplicated, ufgp_ID, ufgp_name
				FROM T_users__fielddefs
				LEFT JOIN T_users__fieldgroups ON ufdf_ufgp_ID = ufgp_ID
				WHERE ufdf_type = "url" AND ufdf_icon_name IS NOT NULL' );

		$r = array( '' => 'None' );

		foreach( $social_fields as $field )
		{
			$r[$field->ufdf_ID] = $field->ufdf_name;
		}

		return $r;
	}

	function get_selected_social_fields()
	{
		global $DB;

		$field_IDs = array();
		for( $i = 1; $i <= 7; $i++ )
		{
			if( $this->disp_params['link'.$i] )
			{
				$field_IDs[] = $this->disp_params['link'.$i];
			}
		}

		$r = array();
		if( $field_IDs )
		{
			$social_fields = $DB->get_results('
					SELECT ufdf_ID, ufdf_icon_name, ufdf_code
					FROM T_users__fielddefs
					WHERE ufdf_ID IN ('.implode(',', $field_IDs ).')' );

			foreach( $social_fields AS $field )
			{
				$r[$field->ufdf_ID] = $field;
			}
		}

		return $r;
	}

	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Collection, $Blog;

		$cache_keys = array(
				'wi_ID'       => $this->ID, // Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID, // Have the settings of the blog changed ? (ex: new owner, new skin)
			);

		return $cache_keys;
	}
}

?>