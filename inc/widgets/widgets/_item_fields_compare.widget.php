<?php
/**
 * This file implements Item Fields Compare Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2017 by Francois Planque - {@link http://fplanque.com/}
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
class item_fields_compare_Widget extends ComponentWidget
{
	var $icon = 'file-text-o';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_fields_compare' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'item-fields-compare-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Fields Compare');
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
		return T_('Display differences between custom fields of the selected items.');
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
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_('This is the title to display'),
					'defaultvalue' => '',
				),
				'fields' => array(
					'type' => 'textarea',
					'label' => T_('Fields to compare'),
					'note' => T_('Enter one field name per line.').' '.T_('Leave empty to compare all fields.'),
					'rows' => 10,
				),
				'items' => array(
					'label' => T_('Items to compare'),
					'note' => sprintf( T_('Separate Item IDs with %s.'), '<code>,</code>' ).' '.sprintf( T_('Leave empty to use URL parameter %s.'), '<code>items=</code>' ),
					'valid_pattern' => array(
						'pattern' => '/^(\d+(,\d+)*)?$/',
						'error'   => T_('Invalid list of Item IDs.')
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
		global $Plugins;

		$params = array_merge( array(
				'fields_compare_table_start'    => '<div class="evo_content_block"><table class="item_custom_fields">',
				'fields_compare_table_end'      => '</table></div>',
				'fields_compare_row_start'      => '<tr>',
				'fields_compare_row_end'        => '</tr>',
				'fields_compare_row_diff_start' => '<tr class="bg-warning">',
				'fields_compare_row_diff_end'   => '</tr>',
				'fields_compare_empty_cell'     => '<td style="border:none"></td>',
				'fields_compare_post'           => '<th>$post_link$</th>',
				'fields_compare_field_title'    => '<th>$field_title$:</th>',
				'fields_compare_field_value'    => '<td>$field_value$</td>',
			), $params );

		$this->init_display( $params );

		$items = $this->get_param( 'items' );
		if( empty( $items ) )
		{	// Use items from URL parameter if widget setting is empty:
			$items = param( 'items', '/^[\d,]*$/' );
		}

		$items = trim( $items, ',' );

		if( empty( $items ) )
		{	// No items to compare:
			return;
		}

		$items = explode( ',', $items );

		// Load all requested posts into the cache:
		$ItemCache = & get_ItemCache();
		$ItemCache->load_list( $items );

		// Check what fields should be displayed for this widget:
		$widget_fields = trim( $this->get_param( 'fields' ) );
		$widget_fields = empty( $widget_fields ) ? false : preg_split( '#[\s\n\r]+#', $widget_fields );

		$all_custom_fields = array();
		foreach( $items as $i => $item_ID )
		{
			if( ! $Item = & $ItemCache->get_by_ID( $item_ID, false, false ) )
			{	// Skin wrong post:
				unset( $items[ $i ] );
				continue;
			}

			// Load all custom fields of the Item in cache:
			$item_custom_fields = $Item->get_custom_fields_defs();
			// Use either all custom fields of the Item or only specified fields in config of this Widget:
			$search_custom_fields = $widget_fields ? $widget_fields : array_keys( $item_custom_fields );

			foreach( $search_custom_fields as $search_custom_field_key )
			{
				if( ! isset( $all_custom_fields[ $search_custom_field_key ] ) )
				{	// Initialize array to keep fields in the requested order:
					$all_custom_fields[ $search_custom_field_key ] = array();
				}
				if( ! isset( $item_custom_fields[ $search_custom_field_key ] ) )
				{	// Skip because the post has no this custom field:
					continue;
				}
				$item_custom_field = $item_custom_fields[ $search_custom_field_key ];
				if( ! $item_custom_field['public'] )
				{	// Skip not public custom field:
					continue;
				}
				if( empty( $all_custom_fields[ $search_custom_field_key ] ) )
				{	// Initialize array to store values from all requested posts:
					$all_custom_fields[ $search_custom_field_key ] = $item_custom_field;
					$all_custom_fields[ $search_custom_field_key ]['values'] = array();
				}
				// Store custom field value of the post:
				$all_custom_fields[ $search_custom_field_key ]['values'][ $Item->ID ] = $Item->get_custom_field_value( $search_custom_field_key );
			}
		}

		// Clear array to remove fields which are not used by any post,
		// for case when field doesn't exist or not public:
		foreach( $all_custom_fields as $a => $all_custom_field )
		{
			if( empty( $all_custom_field ) )
			{
				unset( $all_custom_fields[ $a ] );
			}
		}

		if( $widget_fields === false )
		{	// Sort custom fields from all requested posts by custom field order:
			usort( $all_custom_fields, array( $this, 'sort_custom_fields' ) );
		}

		if( empty( $all_custom_fields ) )
		{	// Don't display widget if all selected items have no custom fields:
			return;
		}

		// Compare custom field values:
		$items_count = count( $items );
		foreach( $all_custom_fields as $c => $custom_field )
		{
			$all_custom_fields[ $c ]['is_different'] = false;
			if( $items_count != count( $custom_field['values'] ) )
			{	// If some post has no field then it is a different:
				$all_custom_fields[ $c ]['is_different'] = true;
			}
			else
			{	// Compare values:
				$prev_custom_field_value = NULL;
				foreach( $custom_field['values'] as $v => $custom_field_value )
				{
					if( $prev_custom_field_value !== NULL && $custom_field_value != $prev_custom_field_value )
					{
						$all_custom_fields[ $c ]['is_different'] = true;
						break;
					}
					$prev_custom_field_value = $custom_field_value;
				}
			}
		}

		$this->disp_title( $this->disp_params['title'] );

		echo $this->disp_params['block_body_start'];

		// Start a table to display differences of all custom fields for selected posts:
		echo $params['fields_compare_table_start'];

		echo $params['fields_compare_row_start'];
		echo $params['fields_compare_empty_cell'];
		foreach( $items as $item_ID )
		{
			$Item = & $ItemCache->get_by_ID( $item_ID, false, false );
			// Permanent post link:
			echo str_replace( '$post_link$', $Item->get_title(), $params['fields_compare_post'] );
		}
		echo $params['fields_compare_row_end'];

		foreach( $all_custom_fields as $custom_field )
		{
			echo $custom_field['is_different'] ? $params['fields_compare_row_diff_start'] : $params['fields_compare_row_start'];
			// Custom field title:
			echo str_replace( '$field_title$', $custom_field['label'], $params['fields_compare_field_title'] );
			foreach( $items as $item_ID )
			{
				// Custom field value per each post:
				$custom_field_value = isset( $custom_field['values'][ $item_ID ] ) ? $custom_field['values'][ $item_ID ] : '';
				echo str_replace( '$field_value$', $custom_field_value, $params['fields_compare_field_value'] );
			}

			echo $custom_field['is_different'] ? $params['fields_compare_row_diff_end'] : $params['fields_compare_row_end'];
		}

		echo $params['fields_compare_table_end'];

		echo $this->disp_params['block_start'];

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Callback function to sort custom fields by order field
	 *
	 * @param array Custom field A
	 * @param array Custom field B
	 * @return boolean
	 */
	function sort_custom_fields( $custom_field_a, $custom_field_b )
	{
		return $custom_field_a['order'] > $custom_field_b['order'];
	}
}

?>