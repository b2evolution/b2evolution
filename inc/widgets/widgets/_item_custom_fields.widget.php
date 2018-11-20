<?php
/**
 * This file implements Item Custom Fields Widget class.
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

load_class( 'widgets/widgets/_item_fields_compare.widget.php', 'item_fields_compare_Widget' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class item_custom_fields_Widget extends item_fields_compare_Widget
{
	var $icon = 'list';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		ComponentWidget::__construct( $db_row, 'core', 'item_custom_fields' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'item-custom-fields-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Item Custom Fields');
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
		return T_('Display item custom fields.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		// This is derived from item_fields_compare_Widget, so we DO NOT ADD ANY param here!
		$generic_params = parent::get_param_definitions( $params );

		// We only change the defaults and hide some params:
		$generic_params['items_source']['no_edit'] = true;
		$generic_params['items_type']['no_edit'] = true;
		$generic_params['restrict_featured']['no_edit'] = true;
		$generic_params['restrict_cats']['no_edit'] = true;
		$generic_params['restrict_tags']['no_edit'] = true;
		$generic_params['items_limit']['no_edit'] = true;
		$generic_params['allow_filter']['no_edit'] = true;
		$generic_params['show_headers']['no_edit'] = true;
		$generic_params['merge_headers']['no_edit'] = true;
		$generic_params['show_status']['no_edit'] = true;
		$generic_params['cell_colors']['no_edit'] = true;
		for( $order_index = 0; $order_index <= 2; $order_index++ )
		{
			$field_suffix = ( $order_index == 0 ? '' : '_'.$order_index );
			$generic_params['order_begin_line'.$field_suffix]['no_edit'] = true;
			$generic_params['orderby'.$field_suffix]['no_edit'] = true;
			$generic_params['orderdir'.$field_suffix]['no_edit'] = true;
			$generic_params['order_end_line'.$field_suffix]['no_edit'] = true;
		}

		// Change title and note for the param "Specific Item IDs":
		$generic_params['items']['label'] = T_('Item ID to show');
		$generic_params['items']['note'] = T_('Leave empty for current Item.');
		$generic_params['items']['size'] = 11;

		// Don't hide empty lines by default for this widget:
		$generic_params['hide_empty_lines']['defaultvalue'] = 0;

		return $generic_params;
	}


	/**
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		parent::init_display( $params );

		// Force some params (because this is a simplified widget):
		$this->disp_params['items_limit'] = 1; // Display only single item, because it is a not compare widget
		$this->disp_params['allow_filter'] = 0; // Disable filter because we don't need it here
		$this->disp_params['items_source'] = 'list'; // Use item ID only from the specific list
		if( empty( $this->disp_params['items'] ) )
		{	// Use current Item by default:
			$this->disp_params['items'] = '$this$';
		}
		// We should not display a header with item title and status for this widget:
		$this->disp_params['show_headers'] = 0;
		$this->disp_params['show_status'] = 'never';
	}
}

?>