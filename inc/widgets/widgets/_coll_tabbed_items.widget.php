<?php
/**
 * This file implements the Tabbed Items Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/widgets/_param_switcher.widget.php', 'param_switcher_Widget' );

/**
 * Universal Item List Widget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class coll_tabbed_items_Widget extends param_switcher_Widget
{
	var $icon = 'refresh';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Use standard style to display debug messages on customizer for this widget
		// instead of menu style that is used by default on the parent class:
		$this->debug_message_style = 'standard';

		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'coll_tabbed_items' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'tabbed-items-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Tabbed Items');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output($this->disp_params['title']);
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display Items (Posts/Pages/Links...) with switchable tabs.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $admin_url;

		// Get available templates:
		$context = 'content_list_master';
		$TemplateCache = & get_TemplateCache();
		$TemplateCache->load_by_context( $context );
		$template_options = array( NULL => T_('No template / use settings below').':' ) + $TemplateCache->get_code_option_array();

		$ItemTypeCache = & get_ItemTypeCache();

		$item_type_options =
			array(
				''  => T_('All'),
			) + $ItemTypeCache->get_option_array();

		$item_type_usage_options =
			array(
				'' => T_('All'),
			) + $ItemTypeCache->get_usage_option_array();

		$r = array(
				'general_layout_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Settings')
				),

				'title' => array(
					'label' => T_('Block title'),
					'note' => T_('Title to display in your skin.'),
					'size' => 60,
				),

				'tabs_layout_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Tabs settings'),
				),
					// Set NULL to properly order them in this place, but really they are declared in the parent class param_switcher_Widget:
					'param_code' => NULL,
					'display_mode' => NULL,
					'allow_switch_js' => NULL,
					'allow_switch_url' => NULL,
					'buttons' => NULL, // This param is not used by this widget but we need to move this here in order to avoid empty fields "Settings" around this hidden param
				'tabs_layout_end' => array(
					'layout' => 'end_fieldset',
				),

				'list_layout_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('List settings'),
				),
				'template' => array(
					'label' => T_('Template'),
					'type' => 'select',
					'options' => $template_options,
					'defaultvalue' => 'content_tabs',
					'input_suffix' => ( check_user_perm( 'options', 'edit' ) ? '&nbsp;'
							.action_icon( '', 'edit', $admin_url.'?ctrl=templates&amp;context='.$context, NULL, NULL, NULL,
							array( 'onclick' => 'return b2template_list_highlight( this )', 'target' => '_blank' ),
							array( 'title' => T_('Manage templates').'...' ) ) : '' ),
					'class' => 'evo_template_select',
				),
				'item_visibility' => array(
					'label' => T_('Item visibility'),
					'note' => T_('What post statuses should be included in the list?'),
					'type' => 'radio',
					'field_lines' => true,
					'options' => array(
							array( 'public', T_('show public posts') ),
							array( 'all', T_('show all posts the current user is allowed to see') ) ),
					'defaultvalue' => 'all',
				),
				'item_type_usage' => array(
					'label' => T_('Post type usage'),
					'note' => T_('Restrict to a specific item type usage?'),
					'type' => 'select',
					'options' => $item_type_usage_options,
					'defaultvalue' => '',
				),
				'item_type' => array(
					'label' => T_('Exact post type'),
					'note' => T_('What type of items do you want to list?'),
					'type' => 'select',
					'options' => $item_type_options,
					'defaultvalue' => '',
				),
				'featured' => array(
					'label' => T_('Featured'),
					'note' => T_('Do you want to restrict to featured contents?'),
					'type' => 'radio',
					'options' => array(
							array ('all', T_('All posts') ),
							array ('featured', T_('Only featured') ),
							array ('other', T_('Only NOT featured') ),
						),
					'defaultvalue' => 'all',
				),
				'flagged' => array(
					'label' => T_('Flagged'),
					'note' => T_('Do you want to restrict only to flagged contents?'),
					'type' => 'checkbox',
					'defaultvalue' => 0,
				),
				'follow_mainlist' => array(
					'label' => T_('Follow Main List'),
					'note' => T_('Do you want to restrict to contents related to what is displayed in the main area?'),
					'type' => 'radio',
					'options' => array(
							array( 'no', T_('No') ),
							array( 'tags', T_('By any tag included in Main List (OR match)') ),
							array( 'tags_and', T_('By all tags included in Main List (AND match)') ),
							array( 'tags_order', T_('By priority to best match (OR match + ORDER BY highest number of matches)') ),
						),
					'defaultvalue' => 'no',
					'field_lines' => true,
				),
				'blog_ID' => array(
					'label' => T_('Collections'),
					'note' => T_('List collection IDs separated by \',\', \'*\' for all collections, \'-\' for current collection without aggregation or leave empty for current collection including aggregation.'),
					'size' => 4,
					'type' => 'text',
					'valid_pattern' => array( 'pattern' => '/^(\d+(,\d+)*|-|\*)?$/',
																		'error'   => T_('Invalid list of Collection IDs.') ),
					'defaultvalue' => '',
				),
				'cat_IDs' => array(
					'label' => T_('Categories'),
					'note' => sprintf( T_('List category IDs separated by %s.'), '<code>,</code>' ),
					'size' => 15,
					'type' => 'text',
					'valid_pattern' => array( 'pattern' => '/^(\d+(,\d+)*|-|\*)?$/',
																		'error'   => T_('Invalid list of Category IDs.') ),
				),
			);

		// Display the 3 orderby fields with order direction
		for( $order_index = 0; $order_index <= 2 /* The number of orderby fields - 1 */; $order_index++ )
		{
			$field_suffix = ( $order_index == 0 ? '' : '_'.$order_index );
			$r = array_merge( $r, array(
				'orderby'.$field_suffix.'_begin_line' => array(
					'type' => 'begin_line',
					'label' => ( $order_index == 0 ? T_('Order by') : '' ),
				),
				'order_by'.$field_suffix.'' => array(
					'type' => 'select',
					'options' => get_available_sort_options( NULL, $order_index > 0 ),
					'defaultvalue' => ( $order_index == 0 ? 'datestart' : '' ),
				),
				'order_dir'.$field_suffix.'' => array(
					'note' => T_('How to sort the items'),
					'type' => 'select',
					'options' => array( 'ASC' => T_('Ascending'), 'DESC' => T_('Descending') ),
					'defaultvalue' => ( $order_index == 0 ? 'DESC' : 'ASC' ),
					'allow_none' => true,
				),
				'orderby'.$field_suffix.'_end_line' => array(
					'type' => 'end_line',
				),
			) );
		}

		$r = array_merge( $r, array(
				'limit' => array(
					'label' => T_( 'Max items' ),
					'note' => T_( 'Maximum number of items to display.' ),
					'size' => 4,
					'defaultvalue' => 10,
				),

				'list_layout_end' => array(
					'layout' => 'end_fieldset',
				),

				'general_layout_end' => array(
					'layout' => 'end_fieldset',
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache" because this widget uses the selected items
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		// Don't allow param "Buttons", because they are automatically generated from Items:
		$r['buttons']['no_edit'] = true;

		return $r;
	}


	/**
	 * Get JavaScript code which helps to edit widget form
	 *
	 * @return string
	 */
	function get_edit_form_javascript()
	{
		return get_post_orderby_js( $this->get_param_prefix().'order_by', $this->get_param_prefix().'order_dir' );
	}


	/**
	 * Get order field
	 *
	 * @param string What return: 'field' - Field/column to order, 'dir' - Order direction
	 * @return string
	 */
	function get_order( $return = 'field' )
	{
		$result = '';

		switch( $return )
		{
			case 'field':
				// Get field for ORDERBY sql clause:
				$result = $this->get_param( 'order_by' );
				if( $this->get_param( 'order_by_1' ) != '' )
				{	// Append second order field:
					$result .= ','.$this->get_param( 'order_by_1' );
					if( $this->get_param( 'order_by_2' ) != '' )
					{	// Append third order field:
						$result .= ','.$this->get_param( 'order_by_2' );
					}
				}
				break;

			case 'dir':
				// Get direction(ASC|DESC) for ORDERBY sql clause:
				$result = $this->get_param( 'order_dir' );
				if( $this->get_param( 'order_by_1' ) != '' && $this->get_param( 'order_dir_1' ) != '' )
				{	// Append second order direction
					$result .= ','.$this->get_param( 'order_dir_1' );
					if( $this->get_param( 'order_by_2' ) != '' && $this->get_param( 'order_dir_2' ) != '' )
					{	// Append third order direction:
						$result .= ','.$this->get_param( 'order_dir_2' );
					}
				}
				break;
		}

		return $result;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $MainList;
		global $BlogCache, $Collection, $Blog, $disp;
		global $Item, $Settings;

		$this->init_display( $params );

		if( ! isset( $Item ) ||
		    ! $Item instanceof Item )
		{	// No current Item:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because this is not an Item page, so there can be no switcher params.' );
			return false;
		}

		if( ! $Item->get_type_setting( 'allow_switchable' ) ||
		    ! $Item->get_setting( 'switchable' ) )
		{	// Item doesn't use switcher params:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because current Item does not use switcher params.' );
			return false;
		}

		if( $this->get_param( 'param_code' ) == '' )
		{	// Display error when param code is not defined:
			$this->display_error_message( 'Widget "'.$this->get_name().'" cannot be displayed because you did not set a param code for tab switching.' );
			return false;
		}
	
		if( $Item->get_switchable_param( $this->get_param( 'param_code' ) ) === NULL )
		{	// No default value:
			$this->display_error_message( 'Widget "'.$this->get_name().'" is hidden because the param <code>'.$this->get_param( 'param_code' ).'</code> has not been declared/initialized in the Item.' );
			return false;
		}

		$blog_ID = intval( $this->disp_params['blog_ID'] );

		$listBlog = ( $blog_ID ? $BlogCache->get_by_ID( $blog_ID, false ) : $Blog );

		if( empty( $listBlog ) )
		{	// Display error when wrong collection is requested by this widget:
			$this->display_error_message( 'Widget "'.$this->get_name().'" is hidden because the requested Collection #'.$this->disp_params['blog_ID'].' doesn\'t exist any more.' );
			return false;
		}

		// Create ItemList
		// Note: we pass a widget specific prefix in order to make sure to never interfere with the mainlist
		$limit = intval( $this->disp_params['limit'] );

		// We need to load more info: use ItemList2
		$ItemList = new ItemList2( $listBlog, $listBlog->get_timestamp_min(), $listBlog->get_timestamp_max(), $limit, 'ItemCache', $this->code.'_' );

		// Set additional debug info prefix for SQL queries to know what widget executes it:
		$ItemList->query_title_prefix = get_class( $this );

		$cat_array = sanitize_id_list( $this->disp_params['cat_IDs'], true );

		// Filter list:
		$filters = array(
				'cat_array' => $cat_array, // Restrict to selected categories
				'orderby'   => $this->get_order( 'field' ),
				'order'     => $this->get_order( 'dir' ),
				'unit'      => 'posts', // We want to advertise all items (not just a page or a day)
				'coll_IDs'  => $this->disp_params['blog_ID'],
			);
		if( $this->disp_params['item_visibility'] == 'public' )
		{	// Get only the public items
			$filters['visibility_array'] = array( 'published' );
		}

		if( isset( $this->disp_params['page'] ) )
		{
			$filters['page'] = $this->disp_params['page'];
		}

		if( $this->disp_params['item_type'] != '' &&
		    $this->disp_params['item_type'] != '#' /* deprecated value, it was used as default value of ItemList filter */ )
		{	// Not "default", restrict to a specific type (or '' for all)
			$filters['types'] = $this->disp_params['item_type'];
		}

		if( isset( $this->disp_params['item_type_usage'] ) )
		{	// Not "default", restrict to a specific type usage (or '' for all):
			$filters['itemtype_usage'] = $this->disp_params['item_type_usage'];
		}

		if( $this->disp_params['featured'] == 'featured' )
		{	// Restrict to featured Items:
			$filters['featured'] = true;
		}
		elseif( $this->disp_params['featured'] == 'other' )
		{	// Restrict to NOT featured Items:
			$filters['featured'] = false;
		}

		if( $this->disp_params['flagged'] == 1 )
		{	// Restrict to flagged Items:
			$filters['flagged'] = true;
		}


		if( strpos( $this->disp_params['follow_mainlist'], 'tags' ) === 0 )
		{	// Restrict to Item tagged with some or all tags used in the Mainlist:

			if( ! isset($MainList) )
			{	// Nothing to follow, don't display anything
				$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because there is no MainList object.' );
				return false;
			}

			$all_tags = $MainList->get_all_tags();
			if( empty($all_tags) )
			{	// Nothing to follow, don't display anything
				$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because there is nothing to display.' );
				return false;
			}

			$filters['tags'] = implode( ',', $all_tags );
			if( $this->disp_params['follow_mainlist'] == 'tags_and' )
			{	// Filter posts which have all tags:
				$filters['tags_operator'] = 'AND';
			}
			// else 'OR' operator by default

			if( $this->disp_params['follow_mainlist'] == 'tags_order' )
			{	// Order by highest number of matched tags:
				$filters['orderby'] = 'matched_tags_num'.( empty( $filters['orderby'] ) ? '' : ','.$filters['orderby'] );
				$filters['order'] = 'DESC'.( empty( $filters['order'] ) ? '' : ','.$filters['order'] );
			}

			if( !empty($Item) )
			{	// Exclude current Item
				$filters['post_ID'] = '-'.$Item->ID;
			}

			// fp> TODO: in addition to just filtering, offer ordering in a way where the posts with the most matching tags come first
		}

		$ItemList->set_filters( $filters, false ); // we don't want to memorize these params

		// Run the query:
		$ItemList->query();

		if( ! $ItemList->result_num_rows )
		{	// Nothing to display:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because there are no results to display.' );
			return false;
		}

		// Get tabs:
		$items_tabs = array();
		while( $row_Item = & $ItemList->get_item() )
		{	// Initialize tabs from items list:
			$items_tabs[] = array(
					'value' => $row_Item->get( 'urltitle' ),
					'text'  => $row_Item->get_title( array(
							'title_field' => 'short_title,title',
							'link_type'   => 'none',
						) ),
				);
		}
		// Set active tab by default on page loading:
		if( ! in_array( $disp, array( 'single', 'page', 'widget_page' ) ) && isset( $items_tabs[0] ) )
		{	// Use first item tab by default for not single Item pages:
			$default_tabs = array( $this->get_param( 'param_code' ) => $items_tabs[0]['value'] );
		}
		elseif( isset( $Item ) && $Item instanceof Item )
		{	// Use default params from current Item:
			$default_tabs = $Item->get_switchable_params();
		}
		else
		{	// No default:
			$default_tabs = array();
		}
		ob_start();
		$active_item_slug = $this->display_switchable_tabs( $items_tabs, $default_tabs );
		$switchable_tabs_content = ob_get_clean();

		// Get items list:
		ob_start();
		$items_list_result = $ItemList->display_list( array_merge( array(
				'switch_param_code' => $this->get_param( 'param_code' ),
				'active_item_slug'  => $active_item_slug,
			), $this->disp_params ) );
		$items_list_content = ob_get_clean();

		if( $items_list_result !== true )
		{	// Display error message:
			$this->display_error_message( $items_list_result );
			return false;
		}

		echo $this->disp_params['block_start'];

		// Block title:
		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		// Display tabs to switch between items:
		echo $switchable_tabs_content;

		// Display items list:
		echo $items_list_content;

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}
}
?>