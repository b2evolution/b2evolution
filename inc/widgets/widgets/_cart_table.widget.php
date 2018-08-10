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
class cart_table_Widget extends ComponentWidget
{
	var $icon = 'shopping-cart';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'cart_table' );
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
		return T_('Display a shopping cart table.');
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
		global $Collection, $Blog;

		$this->init_display( $params );

		$this->disp_params = array_merge( $this->disp_params, array(
				'shopping_cart_empty'       => '<p>'.T_('Your shopping cart is empty.').'</p>',
				'shopping_cart_table_start' => '<table class="evo_shopping_cart table">',
				'shopping_cart_row_start'   => '<tr>',
				'shopping_cart_cell_header' => '<th$header_attrs$>$header_title$</th>',
				'shopping_cart_cell_value'  => '<td$product_attrs$>$product_value$</td>',
				'shopping_cart_row_end'     => '</tr>',
				'shopping_cart_table_end'   => '</table>',
			), $params );

		// Get items form the current cart:
		$Cart = & get_Cart();
		$cart_items = $Cart->get_items();

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
				array( T_('Image'), ' class="shrinkwrap"' ),
				array( T_('Product'), '' ),
				array( T_('Quantity'), ' class="shrinkwrap"' ),
				array( T_('Remove'), ' class="shrinkwrap"' ),
			);
			foreach( $cols as $col_title => $col_data )
			{
				echo str_replace( array( '$header_title$', '$header_attrs$' ), $col_data, $this->disp_params['shopping_cart_cell_header'] );
			}
			echo $this->disp_params['shopping_cart_row_end'];

			// Display products:
			foreach( $cart_items as $cart_item_ID => $cart_Item )
			{
				$product_cell_masks = array( '$product_value$', '$product_attrs$' );
				echo $this->disp_params['shopping_cart_row_start'];

				// Image:
				$first_item_image = $cart_Item->get_images( array(
						'limit'      => 1,
						'image_size' => 'crop-top-48x48',
					) );
				echo str_replace( $product_cell_masks, array( $first_item_image, '' ), $this->disp_params['shopping_cart_cell_value'] );

				// Title:
				echo str_replace( $product_cell_masks, array( $cart_Item->get_title(), '' ), $this->disp_params['shopping_cart_cell_value'] );

				// Quantity:
				$item_qty = $Cart->get_quantity( $cart_item_ID );
				$cart_action_url = $Blog->get( 'carturl', array( 'url_suffix' => 'action=update&amp;item_ID='.$cart_item_ID.'&amp;qty=' ) );
				$qty_cell = action_icon( '', 'minus', $cart_action_url.( $item_qty - 1 ), NULL, NULL, NULL, array( 'class' => '' ) ).' ';
				$qty_cell .= $item_qty.' ';
				$qty_cell .= action_icon( '', 'add', $cart_action_url.( $item_qty + 1 ), NULL, NULL, NULL, array( 'class' => '' ) );
				echo str_replace( $product_cell_masks, array( $qty_cell, ' class="center"' ), $this->disp_params['shopping_cart_cell_value'] );

				// Quantity:
				$remove_cell = action_icon( '', 'remove', $Blog->get( 'carturl', array( 'url_suffix' => 'action=remove&amp;item_ID='.$cart_item_ID ) ), NULL, NULL, NULL, array( 'class' => '' ) ).' ';
				echo str_replace( $product_cell_masks, array( $remove_cell, ' class="center"' ), $this->disp_params['shopping_cart_cell_value'] );

				echo $this->disp_params['shopping_cart_row_end'];
			}

			echo $this->disp_params['shopping_cart_table_end'];
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
		global $Collection, $Blog, $current_User, $Session;

		return array(
				'wi_ID'       => $this->ID, // Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'user_ID'     => ( is_logged_in() ? $current_User->ID : 0 ), // Has the current User changed?
				'cart'        => $Session->ID, // Has the cart updated for current session?
			);
	}
}
?>