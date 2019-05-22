<?php
/**
 * This file implements the Cart class for working with shopping cart.
 *
 * It additionally provides the class Log_noop that implements the same (used) methods, but as
 * no-operation functions. This is useful to create a more resource friendly object when
 * you don't need it (think Debuglog).
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 *
 * @author fplanque: Francois PLANQUE
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Cart class. For working with shopping cart.
 *
 * @package evocore
 */
class Cart
{
	/**
	 * @var array Cart data:
	 *              Key is Item ID,
	 *              Value is array:
	 *               'qty' - Quantity
	 *               'unit_price' - Unit Price
	 *               'curr_ID' - Currency ID
	 */
	var $data = array();

	/**
	 * @var array Objects of items
	 */
	var $items = NULL;

	/**
	 * Constructor
	 */
	function __construct()
	{
		global $Session;

		$ItemCache = & get_ItemCache();

		// Get shopping cart from Session:
		$cart_data = $Session->get( 'cart' );
		$cart_items = $cart_data['items'];

		// Initialize items/products:
		$curr_ID = get_Currency()->ID;
		$this->data = is_array( $cart_items ) ? $cart_items : array();
		foreach( $this->data as $cart_item_ID => $cart_item_data )
		{
			if( $curr_ID != $cart_item_data['curr_ID'] )
			{
				if( $cart_Item = & $ItemCache->get_by_ID( $cart_item_ID, false, false ) )
				{
					if( $new_pricing = $cart_Item->get_current_best_pricing( $curr_ID, NULL, $this->data[ $cart_item_ID ]['qty'] ) )
					{
						$this->data[ $cart_item_ID ]['unit_price'] = $new_pricing['iprc_price'];
					}
					else
					{
						$this->data[ $cart_item_ID ]['unit_price'] = -1;
					}
				}

				$this->data[ $cart_item_ID ]['curr_ID'] = $curr_ID;
			}
		}
	}


	/**
	 * Update shopping cart
	 *
	 * @param integer|NULL Quantity of products,
	 *                     0 - means to delete the item/product from the cart completely,
	 *                     NULL - to use quantity from request param 'qty' with default value '1'.
	 * @param integer|NULL Item/Product ID,
	 *                     NULL - to use item ID from request param 'item_ID'.
	 * @param integer|NULL Currency ID,
	 *                     NULL - to use currency ID from session.
	 * @return boolean TRUE on successful updating,
	 *                 FALSE on failed e.g. if a requested item doesn't exist
	 */
	function update( $qty = NULL, $item_ID = NULL, $curr_ID = NULL )
	{
		global $Session, $Messages;

		if( $item_ID === NULL )
		{	// Use default item ID from request param:
			$item_ID = param( 'item_ID', 'integer', true );
		}

		$ItemCache = & get_ItemCache();
		if( ! ( $cart_Item = & $ItemCache->get_by_ID( $item_ID, false, false ) ) ||
		    ! $cart_Item->can_be_displayed() )
		{	// No requested item in DB or it cannot be displayed on front-office for current user:
			return false;
		}

		if( $curr_ID === NULL )
		{
			$curr_ID = get_Currency()->ID;
		}

		if( $qty === NULL )
		{	// Use default quantity from request param:
			$qty = param( 'qty', 'integer', 1 );
		}

		$qty = intval( $qty );

		$cart_is_updated = false;

		if( $qty <= 0 && isset( $this->data[ $item_ID ] ) )
		{	// Delete item from cart:
			unset( $this->data[ $item_ID ] );
			unset( $this->items[ $item_ID ] );
			$cart_is_updated = true;
			$Messages->add( sprintf( T_('Product "%s" has been removed from the cart.'), $cart_Item->get( 'title' ) ), 'success' );
		}
		elseif( $qty > 0 )
		{	// Add/Update quantity of items in cart:

			if( ! $cart_Item->can_be_ordered_if_no_stock && ( $qty > $cart_Item->qty_in_stock ) )
			{	// Quantity exceeds stock and item cannot be ordered if no stock:
				$Messages->add( sprintf( T_('Product "%s" is out of stock and additional orders cannot be made.'), $cart_Item->get( 'title' ) ) , 'error' );
				return false;
			}

			// Get best pricing for item:
			$best_pricing = $cart_Item->get_current_best_pricing( $curr_ID, NULL, $qty );

			if( empty( $best_pricing ) )
			{
				$CurrencyCache = & get_CurrencyCache();
				$cart_currency = $CurrencyCache->get_by_ID( $curr_ID, false, false );
				$Messages->add( sprintf( T_('Product "%s" cannot be added to the cart because it has no price in the cart currency (%s).'), $cart_Item->get( 'title' ), $cart_currency->get( 'code' ) ), 'error' );
				return false;
			}

			$unit_price = floatval( $best_pricing['iprc_price'] );

			if( ! isset( $this->data[ $item_ID ] ) )
			{
				$this->data[ $item_ID ] = array(
					'qty' => $qty,
					'unit_price' => $unit_price,
					'curr_ID' => $best_pricing['iprc_curr_ID'] );
				$cart_is_updated = true;
				$Messages->add( sprintf( T_('Product "%s" has been added to the cart.'), $cart_Item->get( 'title' ) ), 'success' );
			}
			else
			{
				if( $this->data[ $item_ID ]['qty'] != $qty )
				{
					$this->data[ $item_ID ]['qty'] = $qty;
					$cart_is_updated = true;
					$Messages->add( sprintf( T_('Quantity for product "%s" has been changed.'), $cart_Item->get( 'title' ) ), 'success' );
				}

				if( $this->data[ $item_ID ]['unit_price'] != $unit_price )
				{
					$this->data[ $item_ID ]['unit_price'] = $unit_price;
					$this->data[ $item_ID ]['curr_ID'] = $best_pricing['iprc_curr_ID'];
					$cart_is_updated = true;
					$Messages->add( sprintf( T_('Unit price for product "%s" has been changed.'), $cart_Item->get( 'title' ) ), 'success' );
				}
			}
		}

		if( $cart_is_updated )
		{	// Update shopping cart with items data:
			$cart_data = array(
					'items' => $this->data, );
			$Session->set( 'cart', $cart_data );
			$Session->dbsave();

			// BLOCK CACHE INVALIDATION:
			BlockCache::invalidate_key( 'cart', $Session->ID ); // Cart has updated for current session
		}

		return true;
	}


	/**
	 * Update all shopping cart items
	 * Note: this assumes that all previous messages were already displayed!
	 *
	 * @return string $Messages HTML output
	 */
	function update_cart()
	{
		global $Session, $Messages, $servertimenow, $cart_last_updated;

		$Messages->clear();
		$ItemCache = & get_ItemCache();
		$cart_is_updated = false;

		foreach( $this->data as $cart_item_ID => $cart_item_data )
		{
			if( $cart_Item = & $ItemCache->get_by_ID( $cart_item_ID, false, false ) )
			{
				// Check stock availability:
				$qty = $this->data[ $cart_item_ID ]['qty'];
				$in_stock = intval( $cart_Item->qty_in_stock );
				if( ! $cart_Item->can_be_ordered_if_no_stock && ( $qty > $in_stock ) )
				{
					if( $in_stock > 0 )
					{
						$Messages->add_to_group( sprintf( 'You had %d "%s" in your cart but we only have %d in stock right now. The quantity has been adjusted in your cart', $qty, $cart_Item->get_title(), $cart_Item->qty_in_stock ), 'note', T_('Cart update').':' );
						$this->data[$cart_item_ID]['qty'] = $in_stock;
						$cart_is_updated = true;
					}
					else
					{
						$Messages->add_to_group( sprintf( 'You had %d "%s" in your cart but we don\'t have any in stock right now. The item has been removed from your cart.', $qty, $cart_Item->get_title() ), 'note', T_('Cart update').':' );
						unset( $this->data[$cart_item_ID] );
						$cart_is_updated = true;
						continue;
					}
				}

				// Get best pricing for item:
				$best_pricing = $cart_Item->get_current_best_pricing( $cart_item_data['curr_ID'], NULL, $cart_item_data['qty'] );

				if( empty( $best_pricing ) )
				{
					$this->data[ $cart_item_ID ]['unit_price'] = -1;
				}
				else
				{
					$unit_price = floatval( $best_pricing['iprc_price'] );
					if( $this->data[ $cart_item_ID ]['unit_price'] != $unit_price )
					{
						$this->data[ $cart_item_ID ]['unit_price'] = $unit_price;
						$this->data[ $cart_item_ID ]['curr_ID'] = $best_pricing['iprc_curr_ID'];
						$cart_is_updated = true;
					}
				}
			}
		}

		if( $cart_is_updated )
		{	// Update shopping cart with items data:
			$cart_data = array(
					'items' => $this->data, );
			$Session->set( 'cart', $cart_data );
			$Session->dbsave();

			// BLOCK CACHE INVALIDATION:
			BlockCache::invalidate_key( 'cart', $Session->ID ); // Cart has updated for current session
		}

		$Session->set( 'cart_last_updated', $servertimenow );
		$Session->dbsave();

		return $Messages->display( NULL, NULL, false, NULL );
	}


	/**
	 * Get array of items objects
	 *
	 * @return array
	 */
	function get_items()
	{
		if( $this->items === NULL )
		{	// Load all cart items into the cache array:
			$this->items = array();
			$ItemCache = & get_ItemCache();
			if( ! empty( $this->data ) )
			{	// Load all cart items in single query:
				$ItemCache->load_list( array_keys( $this->data ) );
				foreach( $this->data as $item_ID => $row )
				{
					if( ( $cart_Item = & $ItemCache->get_by_ID( $item_ID, false, false ) ) &&
					    $cart_Item->can_be_displayed() )
					{	// If Item exists in DB and can be displayed for current User:
						$this->items[ $item_ID ] = $cart_Item;
					}
				}
			}
		}

		return $this->items;
	}


	/**
	 * Get Item by ID
	 *
	 * @param integer Item ID
	 * @return object|NULL|false Item object
	 */
	function & get_Item( $item_ID )
	{
		$cart_items = $this->get_items();
		$cart_Item = ( isset( $cart_items[ $item_ID ] ) ? $cart_items[ $item_ID ] : NULL );

		return $cart_Item;
	}


	/**
	 * Get title of the requested item in this shopping cart
	 *
	 * @param integer Item ID
	 * @param array Parameters
	 * @return string Item title
	 */
	function get_title( $item_ID, $params = array() )
	{
		// Get title of the cart Item:
		$title = $cart_Item = & $this->get_Item( $item_ID )
			? $cart_Item->get_title( $params )
			: NULL;

		if( isset( $this->data[ $item_ID ] ) && $this->data[ $item_ID ]['unit_price'] < 0 )
		{
			$title .= "\n".get_icon( 'warning_yellow' ).' <span class="text-danger">'.sprintf( T_('This product cannot be purchased in %s.'), $this->get_currency_code( $item_ID ) ).'</span>';
		}

		return nl2br( format_to_output( $title, 'htmlbody' ) );
	}


	/**
	 * Get image urls of the requested item in this shopping cart
	 *
	 * @param integer Item ID
	 * 
	 * @return array URls to item's images
	 */
	function get_image_urls( $item_ID, $params = array() )
	{
		$params = array_merge( array(
				'limit'                      => 1,
				'image_size'                 => 'crop-top-48x48',
				'restrict_to_image_position' => 'cover,teaser,teaserperm,teaserlink,aftermore,inline',
			), $params );

		// Get image URLs of the cart Item:
		return $cart_Item = & $this->get_Item( $item_ID )
			? $cart_Item->get_image_urls( $params )
			: array();
	}


	/**
	 * Get quantity of the requested item in this shopping cart
	 *
	 * @param integer Item ID
	 * @return integer quantity
	 */
	function get_quantity( $item_ID )
	{
		return isset( $this->data[ $item_ID ] ) ? $this->data[ $item_ID ]['qty'] : 0;
	}


	/**
	 * Get currency object of requested item in this shopping cart
	 *
	 * @param integer Item ID
	 * @return object Currency object
	 */
	function & get_Currency( $item_ID )
	{
		if( isset( $this->data[ $item_ID ] ) )
		{
			$CurrencyCache = & get_CurrencyCache();
			$Currency = & $CurrencyCache->get_by_ID( $this->data[ $item_ID ]['curr_ID'], false, false );
			return $Currency;
		}

		return NULL;
	}


	/**
	 * Get currency code of requested item in this shopping cart
	 *
	 * @param integer Item ID
	 * @return string Currency code
	 */
	function get_currency_code( $item_ID )
	{
		$Currency = & $this->get_Currency( $item_ID );

		return $Currency ? $Currency->get( 'code' ) : NULL;
	}


	/**
	 * Get unit price of requested item in this shopping cart
	 *
	 * @param integer Item ID
	 * @param boolean TRUE to return a formatted value for display
	 * @return string Unit price with currency code
	 */
	function get_unit_price( $item_ID, $format = false )
	{
		if( isset( $this->data[ $item_ID ] ) )
		{
			$Currency = & $this->get_Currency( $item_ID );
			$unit_price = isset( $this->data[ $item_ID ] ) ? $this->data[ $item_ID ]['unit_price'] : 0;

			if( $unit_price < 0 )
			{
				return $format
					? get_icon( 'warning_yellow', 'imgtag', array( 'title' => sprintf( T_('This product cannot be purchased in %s.'), $Currency->get( 'code' ) ) ) )
					: 0;
			}

			return $format
				? $Currency->get( 'shortcut' ).'&nbsp;'.number_format( $unit_price, 2 )
				: $unit_price;
		}

		return NULL;
	}


	/**
	 * Get total price of requested item in this shopping cart
	 *
	 * @param integer Item ID
	 * @param boolean TRUE to return a formatted value for display
	 * @return float total item price
	 */
	function get_total_price( $item_ID, $format = false )
	{
		if( isset( $this->data[ $item_ID ] ) )
		{
			$Currency = & $this->get_Currency( $item_ID );
			$unit_price = isset( $this->data[ $item_ID ] ) ? $this->data[ $item_ID ]['unit_price'] : 0;
			if( $unit_price < 0 )
			{
				return $format
					? get_icon( 'warning_yellow', 'imgtag', array( 'title' => sprintf( T_('This product cannot be purchased in %s.'), $Currency->get( 'code' ) ) ) )
					: 0;
			}

			$total_price = isset( $this->data[ $item_ID ] ) ? $unit_price * $this->data[ $item_ID ]['qty'] : 0;

			return $format
				? $Currency->get( 'shortcut' ).'&nbsp;'.number_format( $total_price, 2 )
				: $total_price;
		}

		return NULL;
	}


	/**
	 * Get total price of all items in this shopping cart
	 *
	 * @return float total cart price
	 */
	function get_cart_total()
	{
		$cart_total = 0.00;
		foreach( $this->items as $cart_item_ID => $cart_Item )
		{
			if( isset( $this->data[ $cart_item_ID ] ) )
			{
				if( $cart_Item->can_be_displayed() && $this->data[ $cart_item_ID ]['unit_price'] >= 0 )
				{
					$cart_total += $this->data[ $cart_item_ID ]['unit_price'] * $this->data[ $cart_item_ID ]['qty'];
				}
			}
		}

		return $cart_total;
	}
}

?>