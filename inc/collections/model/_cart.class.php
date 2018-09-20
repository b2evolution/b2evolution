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
	 * @var array IDs of items
	 */
	var $item_IDs = array();

	/**
	 * @var array Objects of items
	 */
	var $items = NULL;

	/**
	 * @var integer Currency ID
	 */
	var $curr_ID;

	/**
	 * @var object Currency object
	 */
	var $currency = NULL;

	/**
	 * Constructor
	 */
	function __construct()
	{
		global $Session;

		load_funcs( 'regional/model/_regional.funcs.php' );

		// Get shopping cart from Session:
		$cart_data = $Session->get( 'cart' );
		$cart_items = $cart_data['items'];
		$cart_curr_ID = $cart_data['curr_ID'];

		// Initialize items/products:
		$this->item_IDs = is_array( $cart_items ) ? $cart_items : array();

		// Initialize cart currency:
		$CurrencyCache = & get_CurrencyCache();
		$this->curr_ID = empty( $cart_curr_ID ) ? get_default_currency_ID() : $cart_curr_ID;
		$this->currency = $CurrencyCache->get_by_ID( $this->curr_ID, false, false );
	}


	/**
	 * Update shopping cart
	 *
	 * @param integer|NULL Quantity of products,
	 *                     0 - means to delete the item/product from the cart completely,
	 *                     NULL - to use quantity from request param 'qty' with default value '1'.
	 * @param integer|NULL Item/Product ID,
	 *                     NULL - to use item ID from request param 'item_ID'.
	 * @return boolean TRUE on successful updating,
	 *                 FALSE on failed e.g. if a requested item doesn't exist
	 */
	function update( $qty = NULL, $item_ID = NULL )
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

		if( $qty === NULL )
		{	// Use default quantity from request param:
			$qty = param( 'qty', 'integer', 1 );
		}

		$qty = intval( $qty );

		$cart_is_updated = false;

		if( $qty <= 0 && isset( $this->item_IDs[ $item_ID ] ) )
		{	// Delete item from cart:
			unset( $this->item_IDs[ $item_ID ] );
			$cart_is_updated = true;
			$Messages->add( sprintf( T_('Product "%s" has been removed from the cart.'), $cart_Item->get( 'title' ) ), 'success' );
		}
		elseif( $qty > 0 )
		{	// Add/Update quantity of items in cart:
			$best_pricing = $cart_Item->get_current_best_pricing( $this->curr_ID, NULL, $qty );

			if( empty( $best_pricing ) )
			{
				$Messages->add( sprintf( T_('This product cannot be added to the cart because it has no price in the cart currency (%s).'), $this->currency->get( 'code' ) ), 'error' );
				return false;
			}

			$unit_price = floatval( $best_pricing['iprc_price'] );

			if( ! isset( $this->item_IDs[ $item_ID ] ) )
			{
				$this->item_IDs[ $item_ID ] = array(
					'qty' => $qty,
					'unit_price' => $unit_price );
				$cart_is_updated = true;
				$Messages->add( sprintf( T_('Product "%s" has been added to the cart.'), $cart_Item->get( 'title' ) ), 'success' );
			}
			elseif( $this->item_IDs[ $item_ID ]['qty'] != $qty || $this->item_IDs[ $item_ID ]['unit_price'] != $unit_price )
			{
				$this->item_IDs[ $item_ID ]['qty'] = $qty;
				$this->item_IDs[ $item_ID ]['unit_price'] = $unit_price;
				$cart_is_updated = true;
				$Messages->add( sprintf( T_('Quantity for product "%s" has been changed.'), $cart_Item->get( 'title' ) ), 'success' );
			}
		}

		if( $cart_is_updated )
		{	// Update shopping cart with items data:
			$cart_data = array(
					'curr_ID' => $this->curr_ID,
					'items' => $this->item_IDs );
			$Session->set( 'cart', $cart_data );
			$Session->dbsave();

			// BLOCK CACHE INVALIDATION:
			BlockCache::invalidate_key( 'cart', $Session->ID ); // Cart has updated for current session
		}

		return true;
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
			if( ! empty( $this->item_IDs ) )
			{	// Load all cart items in single query:
				$ItemCache->load_list( array_keys( $this->item_IDs ) );
				foreach( $this->item_IDs as $item_ID => $row )
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
	 * Get quantity of the requested item in this shopping cart
	 *
	 * @param integer Item ID
	 * @return integer quantity
	 */
	function get_quantity( $item_ID )
	{
		return isset( $this->item_IDs[ $item_ID ] ) ? $this->item_IDs[ $item_ID ]['qty'] : 0;
	}


	/**
	 * Get unit price of requested item in this shopping cart
	 *
	 * @param integer Item ID
	 * @return float unit price
	 */
	function get_unit_price( $item_ID )
	{
		return isset( $this->item_IDs[ $item_ID ] ) ? $this->item_IDs[ $item_ID ]['unit_price'] : 0;
	}


	/**
	 * Get total price of requested item in this shopping cart
	 *
	 * @param integer Item ID
	 * @return float total item price
	 */
	function get_total_price( $item_ID )
	{
		return isset( $this->item_IDs[ $item_ID ] ) ? $this->item_IDs[ $item_ID ]['unit_price'] * $this->item_IDs[ $item_ID ]['qty'] : 0;
	}


	/**
	 * Get shopping cart currency ID
	 *
	 * @return integer Currency ID
	 */
	function get_curr_ID()
	{
		load_funcs( 'regional/model/_regional.funcs.php' );
		return isset( $this->curr_ID ) ? $this->curr_ID : get_default_currency_ID();
	}


	/**
	 * Get total price of all items in this shopping cart
	 *
	 * @return float total cart price
	 */
	function get_cart_total()
	{
		$cart_total = 0.00;
		foreach( $this->item_IDs as $cart_Item )
		{
			if( $cart_Item->can_be_displayed() )
			{
				$cart_total += $cart_Item['unit_price'] * $cart_Item['qty'];
			}
		}

		return $cart_total;
	}
}

?>