<?php
/**
 * This file implements the Widget class to display a shopping cart table.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
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
class display_shopping_cart_Widget extends ComponentWidget
{
	var $icon = 'shopping-cart';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'display_shopping_cart' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'cart-table-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Shopping cart');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( $this->get_name() );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display the contents of the shopping cart.');
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
					'note' => T_('Title to display in your skin.'),
					'size' => 40,
				),
				// Classes for Image:
				'class_image_line' => array(
					'type' => 'begin_line',
					'label' => T_('Classes for Image'),
				),
					'class_image_header' => array(
						'label' => T_('Header').':',
						'size' => 60,
						'defaultvalue' => 'col-lg-1 col-md-1 col-sm-1 col-xs-3',
					),
					'class_image_cell' => array(
						'label' => T_('Cell').':',
						'size' => 60,
						'defaultvalue' => 'col-lg-1 col-md-1 col-sm-1 col-xs-3',
					),
				'class_image_end_line' => array(
					'type' => 'end_line',
				),
				// Classes for Product:
				'class_product_line' => array(
					'type' => 'begin_line',
					'label' => T_('Classes for Product'),
				),
					'class_product_header' => array(
						'label' => T_('Header').':',
						'size' => 60,
						'defaultvalue' => 'col-lg-4 col-md-4 col-sm-4 col-xs-9',
					),
					'class_product_cell' => array(
						'label' => T_('Cell').':',
						'size' => 60,
						'defaultvalue' => 'col-lg-4 col-md-4 col-sm-4 col-xs-9',
					),
				'class_product_end_line' => array(
					'type' => 'end_line',
				),
				// Classes for Quantity:
				'class_quantity_line' => array(
					'type' => 'begin_line',
					'label' => T_('Classes for Quantity'),
				),
					'class_quantity_header' => array(
						'label' => T_('Header').':',
						'size' => 60,
						'defaultvalue' => 'col-lg-2 col-md-2 col-sm-2 hidden-xs center',
					),
					'class_quantity_cell' => array(
						'label' => T_('Cell').':',
						'size' => 60,
						'defaultvalue' => 'col-lg-2 col-md-2 col-sm-2 col-xs-2 center',
					),
				'class_quantity_end_line' => array(
					'type' => 'end_line',
				),
				// Classes for Unit Price:
				'class_unit_price_line' => array(
					'type' => 'begin_line',
					'label' => T_('Classes for Unit Price'),
				),
					'class_unit_price_header' => array(
						'label' => T_('Header').':',
						'size' => 60,
						'defaultvalue' => 'col-lg-2 col-md-2 col-sm-2 hidden-xs right',
					),
					'class_unit_price_cell' => array(
						'label' => T_('Cell').':',
						'size' => 60,
						'defaultvalue' => 'col-lg-2 col-md-2 col-sm-2 col-xs-3 right',
					),
				'class_unit_price_end_line' => array(
					'type' => 'end_line',
				),
				// Classes for Total Price:
					'class_total_price_line' => array(
						'type' => 'begin_line',
						'label' => T_('Classes for Unit Price'),
					),
						'class_total_price_header' => array(
							'label' => T_('Header').':',
							'size' => 60,
							'defaultvalue' => 'col-lg-2 col-md-2 col-sm-2 hidden-xs right',
						),
						'class_total_price_cell' => array(
							'label' => T_('Cell').':',
							'size' => 60,
							'defaultvalue' => 'col-lg-2 col-md-2 col-sm-2 col-xs-3 right',
						),
					'class_total_price_end_line' => array(
						'type' => 'end_line',
					),
				// Classes for Actions:
				'class_actions_line' => array(
					'type' => 'begin_line',
					'label' => T_('Classes for Actions'),
				),
					'class_actions_header' => array(
						'label' => T_('Header').':',
						'size' => 60,
						'defaultvalue' => 'col-lg-1 col-md-1 col-sm-1 hidden-xs center',
					),
					'class_actions_cell' => array(
						'label' => T_('Cell').':',
						'size' => 60,
						'defaultvalue' => 'col-lg-1 col-md-1 col-sm-1 col-xs-1 center',
					),
				'class_actions_end_line' => array(
					'type' => 'end_line',
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
		global $Collection, $Blog, $Session, $servertimenow;

		// Get current cart:
		$Cart = & get_Cart();
		$cart_last_updated = $Session->get( 'cart_last_updated' );

		if( empty( $cart_last_updated ) || ( $servertimenow > ( $cart_last_updated + 600 ) ) )
		{	// Update availability and pricing of cart items
			$update_Messages = $Cart->update_cart();
		}

		// Get items from the current cart:
		$cart_items = $Cart->get_items();

		$params = array_merge( array(
				'message_container_selector' => '.action_messages',
			), $params );

		$this->init_display( $params );

		$this->disp_params = array_merge( $this->disp_params, array(
				'shopping_cart_empty'           => '<p>'.T_('Your shopping cart is empty.').'</p>',
				'shopping_cart_table_start'     => '<div class="evo_shopping_cart">',
				'shopping_cart_row_start'       => '<div class="row">',
				'shopping_cart_cell_header'     => '<div class="$class$"><b>$header$</b></div>',
				'shopping_cart_cell_value'      => '<div class="$class$">$value$</div>',
				'shopping_cart_row_end'         => '</div>',
				'shopping_cart_total_row_start' => '<div class="row total_row">',
				'shopping_cart_total_row_end'   => '</div>',
				'shopping_cart_table_end'       => '</div>',
			), $params );

		echo $this->disp_params['block_start'];

		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		if( empty( $cart_items ) )
		{
			echo $this->disp_params['shopping_cart_empty'];
		}
		else
		{
			echo $this->disp_params['shopping_cart_table_start'];

			// Table header columns:
			echo $this->disp_params['shopping_cart_row_start'];
			$cols = array(
				array( T_('Image'), $this->disp_params['class_image_header'] ),
				array( T_('Product'), $this->disp_params['class_product_header'] ),
				array( T_('Unit Price'), $this->disp_params['class_unit_price_header'] ),
				array( T_('Quantity'), $this->disp_params['class_quantity_header'] ),
				array( T_('Total Price'), $this->disp_params['class_total_price_header'] ),
				array( T_('Remove'), $this->disp_params['class_actions_header'] ),
			);
			foreach( $cols as $col_title => $col_data )
			{
				echo str_replace( array( '$header$', '$class$' ), $col_data, $this->disp_params['shopping_cart_cell_header'] );
			}
			echo $this->disp_params['shopping_cart_row_end'];

			// Display products:
			$total = 0.00;
			foreach( $cart_items as $cart_item_ID => $cart_Item )
			{
				$product_cell_masks = array( '$value$', '$class$' );
				echo $this->disp_params['shopping_cart_row_start'];

				// Image:
				$first_item_image = $cart_Item->get_images( array(
						'limit'      => 1,
						'image_size' => 'crop-top-48x48',
						'restrict_to_image_position' => 'cover,teaser,teaserperm,teaserlink,aftermore,inline',
					) );
				echo str_replace( $product_cell_masks, array( $first_item_image, $this->disp_params['class_image_cell'] ), $this->disp_params['shopping_cart_cell_value'] );

				// Title:
				echo str_replace( $product_cell_masks, array( $Cart->get_title( $cart_item_ID ), $this->disp_params['class_product_cell'] ), $this->disp_params['shopping_cart_cell_value'] );

				// Currency:
				$currency = $Cart->get_currency( $cart_item_ID );

				// Unit Price:
				$currency_shortcut = empty( $currency ) ? '' : $currency->get( 'shortcut' ).'&nbsp';
				$unit_price = $Cart->get_unit_price( $cart_item_ID );
				echo str_replace( $product_cell_masks, array( $unit_price, $this->disp_params['class_unit_price_cell'] ), $this->disp_params['shopping_cart_cell_value'] );

				// Quantity:
				$item_qty = $Cart->get_quantity( $cart_item_ID );
				$cart_action_url = $Blog->get( 'carturl', array( 'url_suffix' => 'action=update&amp;item_ID='.$cart_item_ID.'&amp;qty=' ) );
				$qty_cell = '<span class="nowrap">';
				$qty_cell .= action_icon( '', 'minus', $cart_action_url.( $item_qty - 1 ), NULL, NULL, NULL, array( 'class' => '' ) ).' ';
				$qty_cell .= $item_qty.' ';
				if( ( $item_qty < $cart_Item->qty_in_stock ) || $cart_Item->can_be_ordered_if_no_stock )
				{
					$qty_cell .= action_icon( '', 'add', $cart_action_url.( $item_qty + 1 ), NULL, NULL, NULL, array( 'class' => '' ) );
				}
				$qty_cell .= '</span>';
				echo str_replace( $product_cell_masks, array( $qty_cell, $this->disp_params['class_quantity_cell'] ), $this->disp_params['shopping_cart_cell_value'] );

				// Total Price:
				$total_price = $Cart->get_total_price( $cart_item_ID );
				echo str_replace( $product_cell_masks, array( $total_price, $this->disp_params['class_total_price_cell'] ), $this->disp_params['shopping_cart_cell_value'] );

				// Remove:
				$remove_cell = action_icon( '', 'remove', $Blog->get( 'carturl', array( 'url_suffix' => 'action=remove&amp;item_ID='.$cart_item_ID ) ), NULL, NULL, NULL, array( 'class' => '' ) ).' ';
				echo str_replace( $product_cell_masks, array( $remove_cell, $this->disp_params['class_actions_cell'] ), $this->disp_params['shopping_cart_cell_value'] );

				echo $this->disp_params['shopping_cart_row_end'];
			}

			// Totals:
			echo $this->disp_params['shopping_cart_total_row_start'];
			echo str_replace( $product_cell_masks, array( NULL, $this->disp_params['class_image_cell'] ), $this->disp_params['shopping_cart_cell_value'] );
			echo str_replace( $product_cell_masks, array( T_('Total'), $this->disp_params['class_product_cell'] ), $this->disp_params['shopping_cart_cell_value'] );
			echo str_replace( $product_cell_masks, array( NULL, $this->disp_params['class_unit_price_cell'] ), $this->disp_params['shopping_cart_cell_value'] );
			echo str_replace( $product_cell_masks, array( NULL, $this->disp_params['class_quantity_cell'] ), $this->disp_params['shopping_cart_cell_value'] );
			echo str_replace( $product_cell_masks, array( $currency_shortcut.number_format( $Cart->get_cart_total(), 2 ), $this->disp_params['class_total_price_cell'] ), $this->disp_params['shopping_cart_cell_value'] );
			echo str_replace( $product_cell_masks, array( NULL, $this->disp_params['class_actions_cell'] ), $this->disp_params['shopping_cart_cell_value'] );
			echo $this->disp_params['shopping_cart_total_row_end'];

			echo $this->disp_params['shopping_cart_table_end'];
		}

		echo $this->disp_params['block_body_end'];

		if( ! empty( $update_Messages ) )
		{
		?>
		<script type="text/javascript" id="widget_display_shopping_cart_<?php echo $this->ID; ?>">
		jQuery( document ).ready( function() {
			var message_container = jQuery( '<?php echo format_to_js( $this->disp_params['message_container_selector'] );?>' );
			message_container.append( '<?php echo format_to_js( $update_Messages ); ?>' );
		} );
		</script>
		<?php
		}

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
		global $Collection, $Blog, $current_User, $Session;

		$cache_keys = array(
				'wi_ID'       => $this->ID, // Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				// NOTE: The key 'user_ID' is used to invalidate cache when current User was updated,
				//       for example, user was in group "VIP client" and then he was moved to "Problem client"
				//       which cannot see/buy items/products with status "Members", so in such case at the user updating moment
				//       we should invalidate widget cache in order to hide some items/products for the updated user.
				'user_ID'     => ( is_logged_in() ? $current_User->ID : 0 ), // Has the current User changed?
				'curr_ID'     => $Session->get( 'currency_ID' ), // Has the active currency changed?
				'cart'        => $Session->ID, // Has the cart updated for current session?
			);

		// Get items form the current cart:
		$Cart = & get_Cart();
		$cart_items = $Cart->get_items();

		// Add 1 cache key for each item that is in shopping card, in order to detect changes on each one:
		foreach( $cart_items as $cart_item_ID => $cart_Item )
		{
			// 1 is a dummy value, only the key name is really important
			$cache_keys['item_'.$cart_item_ID] = 1;
		}

		return $cache_keys;
	}
}
?>