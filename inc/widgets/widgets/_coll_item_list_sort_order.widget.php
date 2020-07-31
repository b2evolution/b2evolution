<?php
/**
 * This file implements the coll_item_list_sort_order Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
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
class coll_item_list_sort_order_Widget extends ComponentWidget
{
	var $icon = 'sort-amount-asc';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'coll_item_list_sort_order' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'coll-item-list-sort-order-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Sort order');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Item List Sort order') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display control to sort the list of items.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		// Initiliaze sort options for widget param from available sort options:
		$sort_options = $this->get_sort_options();
		$widget_sort_options = array();
		foreach( $sort_options as $sort_key => $sort_title )
		{
			$widget_sort_options[] = array( $sort_key, $sort_title, 1 );
		}

		$r = array_merge( array(
				'title' => array(
					'label' => T_('Title'),
					'size' => 40,
					'note' => T_('This is the title to display'),
					'defaultvalue' => '',
				),
				'allowed_orders' => array(
					'label' => T_('Allow'),
					'type' => 'checklist',
					'options' => $widget_sort_options,
				),
				'display_mode' => array(
					'type' => 'select',
					'label' => T_('Display as'),
					'options' => array(
						'dropdown' => T_('Dropdown menu'),
						'list'     => T_('List'),
					),
					'defaultvalue' => 'dropdown',
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache" because this widget displays dynamic data:
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Request all required css and js files for this widget
	 */
	function request_required_files()
	{
		global $MainList;

		if( ! empty( $MainList ) &&
		    ! $MainList->single_post &&
		    $this->get_param( 'display_mode' ) == 'dropdown' )
		{	// Load JS to sort items list:
			require_js_defer( '#jquery#', 'blog', false, '#', 'footerlines' );
			require_js_defer( 'src/evo_init_widget_coll_item_list_sort_order.js', 'blog', false, '#', 'footerlines'  );
		}
	}


	/**
	 * Get sort options
	 *
	 * @return array
	 */
	function get_sort_options()
	{
		$available_sort_options = get_available_sort_options();
		$sort_options = array();
		foreach( $available_sort_options as $sort_key => $sort_title )
		{
			$sort_options[ $sort_key.':asc'] = $sort_title;
			$sort_options[ $sort_key.':desc'] = $sort_title.' ('.T_('Reverse').')';
		}

		return $sort_options;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $MainList;

		$this->init_display( $params );

		if( empty( $MainList ) )
		{	// No items list to sort:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because there is no items list on the current page.' );
			return false;
		}

		if( $MainList->single_post )
		{	// No need to sort single Item:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because single item in list.' );
			return false;
		}

		// Get all sort options:
		$sort_options = $this->get_sort_options();

		$allowed_orders = $this->get_param( 'allowed_orders' );
		foreach( $allowed_orders as $order_key => $order_is_allowed )
		{
			if( ! isset( $sort_options[ $order_key ] ) || ! $order_is_allowed )
			{	// Exclude disallowed order:
				unset( $allowed_orders[ $order_key ] );
			}
		}

		if( empty( $allowed_orders ) )
		{	// No allowed sort ooptions
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because no sort oprions are allowed.' );
			return false;
		}

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];

		switch( $this->get_param( 'display_mode' ) )
		{
			case 'list':
				echo $this->disp_params['list_start'];
				foreach( $allowed_orders as $order_key => $order_enabled )
				{
					$order = explode( ':', $order_key );
					$order_is_selected = ( $MainList->filters['orderby'] == $order[0] && strtolower( $MainList->filters['order'] ) == $order[1] );
					echo $order_is_selected ? $this->disp_params['item_selected_start'] : $this->disp_params['item_start'];
					echo '<a href="'.regenerate_url( 'orderby,order', 'orderby='.$order[0].'&amp;order='.$order[1] ).'">'.$sort_options[ $order_key ].'</a>';
					echo $order_is_selected ? $this->disp_params['item_selected_end'] : $this->disp_params['item_end'];
				}
				echo $this->disp_params['list_end'];
				break;

			default: // dropdown
				echo '<select data-item-list-sort-order-widget="'.$this->ID.'" data-url="'.regenerate_url( 'orderby,order', 'orderby=$orderby$&amp;order=$orderdir$' ).'" class="form-control">';
				foreach( $allowed_orders as $order_key => $order_enabled )
				{
					$order = explode( ':', $order_key );
					echo '<option value="'.$order_key.'" '
						.'data-order="'.$order[0].'" '
						.'data-order-dir="'.$order[1].'"'
						.( $MainList->filters['orderby'] == $order[0] && strtolower( $MainList->filters['order'] ) == $order[1] ? ' selected="selected"' : '' ).'>'
							.$sort_options[ $order_key ]
						.'</a>';
				}
				echo '</select>';
				break;
		}

		echo $this->disp_params['block_body_end'];
		echo $this->disp_params['block_end'];

		return true;
	}
}
?>