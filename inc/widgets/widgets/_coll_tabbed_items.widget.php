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

load_class( 'widgets/widgets/_generic_menu_link.widget.php', 'generic_menu_link_Widget' );

/**
 * Universal Item List Widget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class coll_tabbed_items_Widget extends generic_menu_link_Widget
{
	var $icon = 'refresh';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
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
		global $current_User, $admin_url;

		// Get available templates:
		$context = 'content_list_master';
		$TemplateCache = & get_TemplateCache();
		$TemplateCache->load_by_context( $context );

		$template_options = $TemplateCache->get_code_option_array();

		load_funcs( 'files/model/_image.funcs.php' );

		/**
		 * @var ItemTypeCache
		 */
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
				'title' => array(
					'label' => T_('Block title'),
					'note' => T_('Title to display in your skin.'),
					'size' => 60,
				),
				'param_code' => array(
					'label' => T_('Param code'),
					'size' => 60,
					'defaultvalue' => 'tab',
				),
				'display_mode' => array(
					'type' => 'select',
					'label' => T_('Display as'),
					'options' => array(
							'auto'    => T_('Auto'),
							'list'    => T_('List'),
							'buttons' => T_('Buttons'),
						),
					'note' => sprintf( T_('Auto is based on the %s param.'), '<code>inlist</code>' ),
					'defaultvalue' => 'auto',
				),
				'allow_switch_js' => array(
					'type' => 'checkbox',
					'label' => T_('Allow Javascript switching (dynamic)'),
					'defaultvalue' => 1,
				),
				'allow_switch_url' => array(
					'type' => 'checkbox',
					'label' => T_('Allow Standard switching (page reload)'),
					'defaultvalue' => 1,
				),
				'template' => array(
					'label' => T_('Template'),
					'type' => 'select',
					'options' => $template_options,
					'defaultvalue' => 'content_tabs',
					'input_suffix' => ( is_logged_in() && $current_User->check_perm( 'options', 'edit' ) ? '&nbsp;'
							.action_icon( '', 'edit', $admin_url.'?ctrl=templates&amp;context='.$context, NULL, NULL, NULL,
							array( 'onclick' => 'return b2template_list_highlight( this )' ),
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
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache" because this widget uses the selected items
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

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

		$blog_ID = intval( $this->disp_params['blog_ID'] );

		$listBlog = ( $blog_ID ? $BlogCache->get_by_ID( $blog_ID, false ) : $Blog );

		if( empty( $listBlog ) )
		{	// Display error when wrong collection is requested by this widget:
			$this->display_error_message( 'Widget "'.$this->get_name().'" is hidden because the requested Collection #'.$this->disp_params['blog_ID'].' doesn\'t exist any more.' );
			return false;
		}

		// Check if template exists:
		$TemplateCache = & get_TemplateCache();
		$widget_Template = $TemplateCache->get_by_code( $this->disp_params['template'], false, false );
		if( ! $widget_Template )
		{
			$this->display_error_message( sprintf( 'Template not found: %s', '<code>'.$this->disp_params['template'].'</code>' ) );
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

		echo $this->disp_params['block_start'];

		// Block title:
		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		// -------- START TABS --------

		// Get current param value and memorize it for regenerating url:
		$param_value = param( $this->get_param( 'param_code' ), 'string', '', true );

		echo $this->disp_params['button_group_start'];

		$button_is_active_by_default = false;
		$active_item_slug = NULL;
		while( $row_Item = & $ItemList->get_item() )
		{	// Display button:
			$item_slug = $row_Item->get( 'urltitle' );
			if( $param_value === $item_slug )
			{	// Active button by current param value:
				$button_is_active = true;
			}
			elseif( ! $button_is_active_by_default &&
			        $param_value === '' &&
			        ( ! in_array( $disp, array( 'single', 'page', 'widget-page' ) ) || ( isset( $Item ) && $Item->get_switchable_param( $this->get_param( 'param_code' ) ) == $item_slug ) ) )
			{	// Active button by default with empty param:
				$button_is_active = true;
				$button_is_active_by_default = true;
			}
			else
			{	// No active button:
				$button_is_active = false;
			}
			$link_js_attrs = ( $this->get_param( 'allow_switch_js' )
				? ' data-tabbed-items="'.$this->ID.'"'
				 .' data-code="'.format_to_output( $this->get_param( 'param_code' ), 'htmlattr' ).'"'
				 .' data-value="'.format_to_output( $item_slug, 'htmlattr' ).'"'
				: '' );
			echo $this->get_layout_menu_link(
				// URL to filter current page:
				( $this->get_param( 'allow_switch_url' )
					? regenerate_url(
						// Exclude params from current URL:
						$this->get_param( 'param_code' ).( $this->get_param( 'add_redir_no' ) ? ',redir' : '' ),
						// Add new param:
						$this->get_param( 'param_code' ).'='.$item_slug.( $this->get_param( 'add_redir_no' ) ? '&amp;redir=no' : '' ) )
					: '#' ),
				// Title of the button:
				$row_Item->get( 'title' ),
				// Mark the button as active:
				$button_is_active,
				// Link template:
				'<a href="$link_url$" class="$link_class$"'.$link_js_attrs.'>$link_text$</a>' );

			if( $button_is_active )
			{	// Set active item slug:
				$active_item_slug = $item_slug;
			}
		}

		echo $this->disp_params['button_group_end'];

		// -------- END TABS --------

		// -------- START ITEMS CONTENT --------

		// Render MASTER quick template:
		// In theory, this should not display anything.
		// Instead, this should set variables to define sub-templates (and potentially additional variables)
		echo render_template_code( $this->disp_params['template'], /* BY REF */ $this->disp_params );

		// Check if requested sub-template exists:
		if( empty( $this->disp_params['item_template'] ) )
		{	// Display error when no template for listing
			$this->display_error_message( sprintf( 'Missing %s param', '<code>item_template</code>' ) );
			return false;
		}

		if( ! ( $item_Template = & $TemplateCache->get_by_code( $this->disp_params['item_template'], false, false ) ) )
		{	// Display error when no or wrong template for listing
			$this->display_error_message( sprintf( 'Template is not found: %s for listing an item', '<code>'.$this->disp_params['item_template'].'</code>' ) );
			return false;
		}

		if( isset( $this->disp_params['before_list'] ) )
		{
			echo $this->disp_params['before_list'];
		}

		$ItemList->restart();
		while( $row_Item = & $ItemList->get_item() )
		{
			// Start wrapper to make each item block switchable:
			echo '<div data-display-condition="'.$this->get_param( 'param_code' ).'='.$row_Item->get( 'urltitle' ).'"'
				// Hide not active item on page loading:
				.( $active_item_slug == $row_Item->get( 'urltitle' ) ? '' : ' style="display:none"' ).'>';

			// Render Item by quick Template:
			echo render_template_code( $this->disp_params['item_template'], $this->disp_params, array( 'Item' => $row_Item ) );

			// End of switchable item block:
			echo '</div>';
		}

		if( isset( $this->disp_params['after_list'] ) )
		{
			echo $this->disp_params['after_list'];
		}

		// -------- END ITEMS CONTENT --------

		echo $this->disp_params['block_body_end'];

		if( $this->get_param( 'allow_switch_js' ) )
		{	// Initialize JS to allow switching by JavaScript:
?>
<script>
evo_init_switchable_buttons( {
	selector:     'a[data-tabbed-items=<?php echo $this->ID; ?>]',
	class_normal: '<?php echo empty( $this->disp_params['widget_link_class'] ) ? $this->disp_params['button_default_class'] : $this->disp_params['widget_link_class']; ?>',
	class_active: '<?php echo empty( $this->disp_params['widget_active_link_class'] ) ? $this->disp_params['button_selected_class'] : $this->disp_params['widget_active_link_class']; ?>',
	add_redir_no: <?php echo $this->get_param( 'add_redir_no' ) ? 'true' : 'false'; ?>,
} );
</script>
<?php
		}

		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Request all required css and js files for this widget
	 */
	function request_required_files()
	{
		if( $this->get_param( 'allow_switch_js' ) )
		{	// Load JS to switch between blocks on change URL in address bar:
			require_js( '#jquery#', 'blog' );
			require_js( 'src/evo_switchable_blocks.js', 'blog' );
		}
	}
}
?>
