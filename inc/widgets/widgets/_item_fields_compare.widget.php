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

		$items = trim( param( 'items', '/^[\d,]*$/' ), ',' );

		if( empty( $items ) )
		{	// No items to compare:
			return;
		}

		$items = explode( ',', $items );

		// Load all requested posts into the cache:
		$ItemCache = & get_ItemCache();
		$ItemCache->load_list( $items );

		$all_custom_fields = array();
		foreach( $items as $i => $item_ID )
		{
			if( ! $Item = & $ItemCache->get_by_ID( $item_ID, false, false ) )
			{	// Skin wrong post:
				unset( $items[ $i ] );
				continue;
			}
			$item_custom_fields = $Item->get_type_custom_fields();
			foreach( $item_custom_fields as $item_custom_field_key => $item_custom_field )
			{
				if( ! $item_custom_field['public'] )
				{	// Skip not public custom field:
					continue;
				}
				if( ! isset( $all_custom_fields[ $item_custom_field['ID'] ] ) )
				{	// Initialize array to store values from all requested posts:
					$all_custom_fields[ $item_custom_field['ID'] ] = $item_custom_field;
					$all_custom_fields[ $item_custom_field['ID'] ]['values'] = array();
				}
				// Store custom field value of the post:
				$all_custom_fields[ $item_custom_field['ID'] ]['values'][ $Item->ID ] = $Item->get_custom_field_value( $item_custom_field_key );
			}
		}

		// Sort custom fields from all requested posts by custom field order:
		usort( $all_custom_fields, array( $this, 'sort_custom_fields' ) );

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