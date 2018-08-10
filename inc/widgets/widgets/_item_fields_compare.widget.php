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
		return T_('Compare Item Fields');
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
		$ItemTypeCache = & get_ItemTypeCache();
		$item_type_options = array(
				'default' => T_('Default types shown for this collection')
			) + $ItemTypeCache->get_option_array();

		$r = array_merge( array(
				'title' => array(
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_('This is the title to display'),
					'defaultvalue' => '',
				),
				'items_source' => array(
					'label' => T_('Items to compare'),
					'type' => 'select',
					'options' => array(
						'all'   => T_('All from collection'),
						'param' => sprintf( T_('As specified by "%s" URL param'), 'items=' ),
						'list'  => T_('Specific IDs listed below'),
					),
					'defaultvalue' => 'param',
				),
				'items_type' => array(
					'label' => T_('Restrict to Post Type'),
					'type' => 'select',
					'options' => $item_type_options,
					'defaultvalue' => 'default',
				),
				'items' => array(
					'label' => T_('Specific Item IDs'),
					'note' => sprintf( T_('Separate Item IDs or slugs or %s or %s with %s.'), '<code>$this$</code>', '<code>$parent$</code>', '<code>,</code>' ),
					'valid_pattern' => array(
						'pattern' => '/^(([\da-z\-_]+|\$this\$|\$parent\$)+(,([\da-z\-_]+|\$this\$|\$parent\$))*)?$/',
						'error'   => sprintf( T_('Items to compare must be specified by ID, by slug or as %s or %s.'), '<code>$this$</code>', '<code>$parent$</code>' ),
					),
					'size' => 80,
				),
				'items_limit' => array(
					'label' => T_('Limit'),
					'type' => 'integer',
					'note' => T_('Max number of items that can be compared.'),
					'defaultvalue' => 10,
					'valid_range' => array(
						'min' => 0,
					),
					'allow_empty' => true,
				),
				'allow_filter' => array(
					'label' => T_('Allow filter params'),
					'type' => 'checkbox',
					'note' => sprintf( T_('Check to allow filtering/ordering with URL params such as %s etc.'), '<code>cat=</code>, <code>tag=</code>, <code>orderby=</code>' ),
					'defaultvalue' => 0,
				),
				'fields' => array(
					'type' => 'textarea',
					'label' => T_('Specific fields to compare'),
					'note' => T_('Enter one field name per line.').' '.T_('Leave empty to compare all fields.'),
					'rows' => 10,
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
		$this->init_display( $params );

		$this->disp_params = array_merge( $this->disp_params, array(
				'fields_compare_table_start'       => '<div class="evo_content_block"><table class="item_custom_fields">',
				'fields_compare_row_start'         => '<tr>',
				'fields_compare_empty_cell'        => '<td style="border:none"></td>',
				'fields_compare_post'              => '<th class="center">$post_link$</th>',
				'fields_compare_field_title'       => '<th class="right">$field_title$$field_description_icon$:</th>',
				'fields_compare_field_value'       => '<td class="center">$field_value$</td>',
				'fields_compare_field_value_diff'  => '<td class="center bg-warning">$field_value$</td>',
				'fields_compare_field_value_green' => '<td class="center bg-success">$field_value$</td>',
				'fields_compare_field_value_red'   => '<td class="center bg-danger">$field_value$</td>',
				'fields_compare_row_end'           => '</tr>',
				'fields_compare_table_end'         => '</table></div>',
				'fields_compare_description_icon_class' => 'grey',
				// Separate template for numeric and separator fields:
				// (Possible to use templates for all field types: 'numeric', 'string', 'html', 'text', 'url', 'image', 'computed', 'separator')
				'fields_compare_numeric_field_value'        => '<td class="right">$field_value$</td>',
				'fields_compare_numeric_field_value_diff'   => '<td class="right bg-warning">$field_value$</td>',
				'fields_compare_numeric_field_value_green'  => '<td class="right bg-success">$field_value$</td>',
				'fields_compare_numeric_field_value_red'    => '<td class="right bg-danger">$field_value$</td>',
				'fields_compare_computed_field_value'       => '<td class="right">$field_value$</td>',
				'fields_compare_computed_field_value_diff'  => '<td class="right bg-warning">$field_value$</td>',
				'fields_compare_computed_field_value_green' => '<td class="right bg-success">$field_value$</td>',
				'fields_compare_computed_field_value_red'   => '<td class="right bg-danger">$field_value$</td>',
				'fields_compare_separator_field_title'      => '<th class="center" colspan="$cols_count$">$field_title$$field_description_icon$</th>',
			), $params );

		// Get IDs of items which should be compared:
		$items = $this->get_items_IDs();

		if( empty( $items ) )
		{	// No items to compare:
			return;
		}

		$ItemCache = & get_ItemCache();

		// Load all requested posts into the cache:
		$ItemCache->load_list( $items );

		// Check what fields should be displayed for this widget:
		$widget_fields = trim( $this->disp_params['fields'] );
		$widget_fields = empty( $widget_fields ) ? false : preg_split( '#[\s\n\r]+#', $widget_fields );

		$all_custom_fields = array();
		foreach( $items as $i => $item_ID )
		{
			if( ! $widget_Item = & $ItemCache->get_by_ID( $item_ID, false, false ) )
			{	// Skin wrong post:
				unset( $items[ $i ] );
				continue;
			}

			// Load all custom fields of the Item in cache:
			$item_custom_fields = $widget_Item->get_custom_fields_defs();
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
				{	// Initialize array to store items which really have this custom field:
					$all_custom_fields[ $search_custom_field_key ] = $item_custom_field;
					$all_custom_fields[ $search_custom_field_key ]['items'] = array();
				}
				// Store ID of the post which has this custom field:
				$all_custom_fields[ $search_custom_field_key ]['items'][] = $item_ID;
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
			uasort( $all_custom_fields, array( $this, 'sort_custom_fields' ) );
		}

		if( empty( $all_custom_fields ) )
		{	// Don't display widget if all selected items have no custom fields:
			return;
		}

		// Compare custom field values:
		$items_count = count( $items );
		foreach( $all_custom_fields as $c => $custom_field )
		{
			if( $custom_field['type'] == 'separator' )
			{	// Separator fields have no values:
				continue;
			}

			$all_custom_fields[ $c ]['is_different'] = false;
			$is_numeric_type = in_array( $custom_field['type'], array( 'double', 'computed' ) );
			if( $is_numeric_type )
			{	// Compare only numeric fields:
				$all_custom_fields[ $c ]['highest_value'] = NULL;
				$all_custom_fields[ $c ]['lowest_value'] = NULL;
			}
			if( $items_count != count( $custom_field['items'] ) )
			{	// If some post has no field then it is a different:
				$all_custom_fields[ $c ]['is_different'] = true;
			}

			// Compare values:
			$prev_custom_field_value = NULL;
			$i = 0;
			$all_string_values_are_empty = ! $widget_fields;
			foreach( $custom_field['items'] as $item_ID )
			{
				$widget_Item = & $ItemCache->get_by_ID( $item_ID, false, false );
				$custom_field_value = $widget_Item->get_custom_field_value( $custom_field['name'] );

				if( $all_string_values_are_empty &&
				    ( ! empty( $custom_field_value ) || $custom_field['type'] == 'separator' ) )
				{	// At least one field is not empty:
					$all_string_values_are_empty = false;
				}

				// Check if the values are different from given line:
				if( ! $all_custom_fields[ $c ]['is_different'] && $i > 0 )
				{	// Don't search differences in all fields if at least two fields are different:
					switch( $custom_field['type'] )
					{
						case 'image':
							// Special comparing for image fields:
							$LinkCache = & get_LinkCache();
							if( $prev_Link = & $LinkCache->get_by_ID( $prev_custom_field_value, false, false ) &&
									$prev_File = & $prev_Link->get_File() &&
									$cur_Link = & $LinkCache->get_by_ID( $custom_field_value, false, false ) &&
									$cur_File = & $cur_Link->get_File() )
							{	// Compare hashes of the two files:
								$is_different = $prev_File->get( 'hash' ) != $cur_File->get( 'hash' );
							}
							else
							{	// If at least one field has a wrong link ID then compare IDs:
								$is_different = $custom_field_value != $prev_custom_field_value;
							}
							break;

						default:
							$is_different = $custom_field_value != $prev_custom_field_value;
							break;
					}
					if( $is_different )
					{	// Mark this field is different:
						$all_custom_fields[ $c ]['is_different'] = true;
					}
				}
				$prev_custom_field_value = $custom_field_value;

				if( $is_numeric_type && is_numeric( $custom_field_value ) )
				{	// Compare only numeric values:
					// Search the highest value:
					if( $all_custom_fields[ $c ]['highest_value'] === NULL ||
							$custom_field_value > $all_custom_fields[ $c ]['highest_value'] )
					{
						$all_custom_fields[ $c ]['highest_value'] = $custom_field_value;
					}

					// Search the lowest value:
					if( $all_custom_fields[ $c ]['lowest_value'] === NULL ||
							$custom_field_value < $all_custom_fields[ $c ]['lowest_value'] )
					{
						$all_custom_fields[ $c ]['lowest_value'] = $custom_field_value;
					}
				}
				$i++;
			}

			if( $all_string_values_are_empty )
			{	// Don't display row of custom field if values from all compared items are empty:
				unset( $all_custom_fields[ $c ] );
			}
		}

		echo $this->disp_params['block_start'];

		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		// Start a table to display differences of all custom fields for selected posts:
		echo $this->get_field_template( 'table_start' );

		echo $this->get_field_template( 'row_start' );
		echo $this->get_field_template( 'empty_cell' );
		foreach( $items as $item_ID )
		{
			$widget_Item = & $ItemCache->get_by_ID( $item_ID, false, false );
			// Permanent post link:
			echo str_replace( '$post_link$', $widget_Item->get_title(), $this->get_field_template( 'post' ) );
		}
		echo $this->get_field_template( 'row_end' );

		foreach( $all_custom_fields as $custom_field )
		{
			// Display a row of one compared field between all selected items:
			$this->display_field_row_template( $custom_field, $items, $this->disp_params );

			// Display the repeated fields after separator:
			if( $custom_field['type'] == 'separator' &&
			    ! empty( $custom_field['format'] ) &&
			    ! $widget_fields )
			{	// Repeat fields after separator in case of displaying of all fields:
				$separator_format = explode( ':', $custom_field['format'] );
				if( $separator_format[0] != 'repeat' || empty( $separator_format[1] ) )
				{	// Skip wrong separator format:
					continue;
				}
				$repeat_fields = explode( ',', $separator_format[1] );
				foreach( $repeat_fields as $repeat_field_name )
				{
					$repeat_field_name = trim( $repeat_field_name );
					if( ! isset( $all_custom_fields[ $repeat_field_name ] ) ||
					    $all_custom_fields[ $repeat_field_name ]['type'] == 'separator' )
					{	// Skip unknown field and a separator field to avoid recursion:
						continue;
					}

					// Display a row of the repeated custom field between all selected items:
					$this->display_field_row_template( $all_custom_fields[ $repeat_field_name ], $items, $this->disp_params );
				}
			}
		}

		echo $this->get_field_template( 'table_end' );

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Display a row of one compared field between the requested items
	 *
	 * @param array Custom field data
	 * @param array IDs of the compared items
	 * @param array Additional parameters
	 */
	function display_field_row_template( $custom_field, $items, $params = array() )
	{
		$ItemCache = & get_ItemCache();

		echo $this->get_field_template( 'row_start', $custom_field['type'] );

		if( empty( $custom_field['description'] ) )
		{	// The custom field has no description:
			$field_description_icon = '';
		}
		else
		{	// Display a description in tooltip of the help icon:
			$field_description_icon = ' '.get_icon( 'help', 'imgtag', array(
					'data-toggle' => 'tooltip',
					'title'       => nl2br( $custom_field['description'] ),
					'class'       => $params['fields_compare_description_icon_class'],
				) ).' ';
		}

		// Custom field title:
		echo str_replace( array( '$field_title$', '$cols_count$', '$field_description_icon$' ),
			array( $custom_field['label'], count( $items ) + 1, $field_description_icon ),
			$this->get_field_template( 'field_title', $custom_field['type'] ) );

		if( $custom_field['type'] != 'separator' )
		{	// Separator fields have no values:
			foreach( $items as $item_ID )
			{
				// Custom field value per each post:
				if( in_array( $item_ID, $custom_field['items'] ) )
				{	// Get a formatted value if post has this custom field:
					$widget_Item = & $ItemCache->get_by_ID( $item_ID, false, false );
					$custom_field_value = $widget_Item->get_custom_field_formatted( $custom_field['name'], $params );
					$custom_field_orig_value = $widget_Item->get_custom_field_value( $custom_field['name'] );
				}
				else
				{	// This post has no this custom field:
					$custom_field_value = '';
					$custom_field_orig_value = false;
				}

				// Default template for field value:
				$field_value_template = $this->get_field_template( 'field_value', $custom_field['type'] );

				if( $custom_field['is_different'] && $custom_field['line_highlight'] == 'differences' )
				{	// Mark the field value as different only when it is defined in the settings of the custom field:
					$field_value_template = $this->get_field_template( 'field_value_diff', $custom_field['type'] );
				}

				if( in_array( $custom_field['type'], array( 'double', 'computed' ) ) &&
						is_numeric( $custom_field_orig_value ) )
				{	// Compare only numeric values:
					if( $custom_field_orig_value === $custom_field['highest_value'] &&
							$custom_field_orig_value !== $custom_field['lowest_value'] )
					{	// Check if we should mark the highest field:
						if( $custom_field['green_highlight'] == 'highest' )
						{	// The highest value must be marked as green:
							$field_value_template = $this->get_field_template( 'field_value_green', $custom_field['type'] );
						}
						elseif( $custom_field['red_highlight'] == 'highest' )
						{	// The highest value must be marked as red:
							$field_value_template = $this->get_field_template( 'field_value_red', $custom_field['type'] );
						}
					}

					if( $custom_field_orig_value === $custom_field['lowest_value'] &&
							$custom_field_orig_value !== $custom_field['highest_value'] )
					{	// Check if we should mark the lowest field:
						if( $custom_field['green_highlight'] == 'lowest' )
						{	// The lowest value must be marked as green:
							$field_value_template = $this->get_field_template( 'field_value_green', $custom_field['type'] );
						}
						elseif( $custom_field['red_highlight'] == 'lowest' )
						{	// The lowest value must be marked as red:
							$field_value_template = $this->get_field_template( 'field_value_red', $custom_field['type'] );
						}
					}
				}

				echo str_replace( '$field_value$', $custom_field_value, $field_value_template );
			}
		}

		echo $this->get_field_template( 'row_end', $custom_field['type'] );
	}


	/**
	 * Get IDs of items which are used by this widget depending on settings
	 *
	 * @return array
	 */
	function get_items_IDs()
	{
		global $Collection, $Blog, $Item;

		switch( $this->disp_params['items_source'] )
		{
			case 'all':
				// Use all items from current collection,
				// They are loaded by ItemList below:
				$items = 'all';
				break;

			case 'param':
				// Use items from param:
				$items = param( 'items', '/^[\d,]*$/' );
				$items = trim( $items, ',' );
				$items = empty( $items ) ? false : explode( ',', $items );
				break;

			case 'list':
				// Use items from specific list:
				$items = trim( $this->disp_params['items'], ',' );
				$items = empty( $items ) ? false : explode( ',', $items );
				break;

			default:
				// Stop here, because unknown items source.
				return array();
		}

		if( empty( $items ) && $items != 'all' )
		{	// No items to compare:
			return array();
		}

		$ItemCache = & get_ItemCache();

		$items_limit = intval( $this->disp_params['items_limit'] );
		$items_limit = $items_limit > 0 ? $items_limit : NULL;

		if( is_array( $items ) )
		{	// Check item IDs which are loaded from URL param 'items=' or from specific widget settings list:
			foreach( $items as $i => $item_ID )
			{
				$item_ID = trim( $item_ID );
				if( empty( $item_ID ) )
				{	// Remove wrong item ID:
					unset( $items[ $i ] );
				}
				if( $item_ID == '$this$' )
				{	// Try to get a current post ID:
					if( isset( $Item ) && $Item instanceof Item )
					{	// Use ID of current post:
						$items[ $i ] = $Item->ID;
					}
					else
					{	// Remove it because no current post:
						unset( $items[ $i ] );
					}
				}
				elseif( $item_ID == '$parent$' )
				{	// Try to get a parent post ID:
					if( isset( $Item ) && $Item instanceof Item && $Item->get( 'parent_ID' ) > 0 )
					{	// Use ID of parent post:
						$items[ $i ] = $Item->get( 'parent_ID' );
					}
					else
					{	// Remove it because no parent post:
						unset( $items[ $i ] );
					}
				}
				elseif( ! is_number( $item_ID ) )
				{	// Try to get a post ID by slug:
					if( $widget_Item = & $ItemCache->get_by_urltitle( $item_ID, false, false ) )
					{	// Use ID of post detected by slug:
						$items[ $i ] = $widget_Item->ID;
					}
					else
					{	// Remove it because cannot find post by slug:
						unset( $items[ $i ] );
					}
				}
			}

			if( $items_limit > 0 )
			{	// Limit items by widget setting:
				$items = array_slice( $items, 0, $items_limit );
			}
		}

		if( $this->disp_params['allow_filter'] || $items == 'all' )
		{	// Use ItemList when we need a filter or when all items are requested from collection:
			$ItemList = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max(), $items_limit );
			// Set additional debug info prefix for SQL queries in order to know what code executes it:
			$ItemList->query_title_prefix = 'item_fields_compare_Widget';

			if( $this->disp_params['items_type'] == 'default' )
			{	// Exclude items with types which are hidden by collection setting "Show post types":
				$filter_item_type = $Blog->get_setting( 'show_post_types' ) != '' ? '-'.$Blog->get_setting( 'show_post_types' ) : NULL;
			}
			else
			{	// Filter by selected Item Type:
				$filter_item_type = intval( $this->disp_params['items_type'] );
			}
			$ItemList->set_default_filters( array(
				'types'        => $filter_item_type,
				'post_ID_list' => is_array( $items ) ? implode( ',', $items ) : NULL,
			) );

			if( $this->disp_params['allow_filter'] )
			{	// Filter items from request:
				$ItemList->load_from_Request( false );
			}

			// Run query:
			$ItemList->query();

			// Get IDs of items filtered by $ItemList:
			$items = $ItemList->get_page_ID_array();
		}

		return $items;
	}


	/**
	 * Get field template depending on type of the custom field
	 *
	 * @param string Template name
	 * @param string Custom field type: 'double', 'varchar', 'html', 'text', 'url', 'image', 'computed', 'separator'
	 * @return string HTML template
	 */
	function get_field_template( $template_name, $field_type = '' )
	{
		// Convert field types to non-devs names:
		$field_type = ( $field_type == 'double' ? 'numeric' : ( $field_type == 'varchar' ? 'string' : $field_type ) );

		if( isset( $this->disp_params['fields_compare_'.$field_type.'_'.$template_name] ) )
		{	// Use special template for current type if it is defined:
			return $this->disp_params['fields_compare_'.$field_type.'_'.$template_name];
		}
		elseif( isset( $this->disp_params['fields_compare_'.$template_name] ) )
		{	// Use generic template for all types:
			return $this->disp_params['fields_compare_'.$template_name];
		}

		// Unknown template:
		return '';
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


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Collection, $Blog, $Item;

		// Get IDs of items which should be compared:
		$items = $this->get_items_IDs();

		$cache_keys = array(
				'wi_ID'        => $this->ID, // Have the widget settings changed ?
				'set_coll_ID'  => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'item_ID'      => isset( $Item ) ? $Item->ID : NULL, // Has the Item page changed? (this is important for disp=single|page because $this$ and $parent$ resolve differently depending on item ID)
				'items'        => implode( ',', $items ), // Have the compared items changed? (Check firstly widget setting and then param from request) (this is important in case the same items are compared in different order)
				'meta_settings'=> 1, // Have meta settings(any item type) changed?
			);

		// Add 1 cache key for each item that is being compared, in order to detect changes on each one:
		foreach( $items as $item_ID )
		{
			// 1 is a dummy value, only the key name is really important
			$cache_keys['item_'.$item_ID] = 1;
		}

		return $cache_keys;
	}
}

?>