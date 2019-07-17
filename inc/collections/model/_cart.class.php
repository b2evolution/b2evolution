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
	 * @var array Payments per processor
	 */
	var $payments = NULL;

	/**
	 * Constructor
	 */
	function __construct()
	{
		if( $cart_Order = & $this->get_Order() )
		{	// Check current currency with Order currency:
			$curr_ID = get_Currency()->ID;
			if( $curr_ID != $cart_Order->get( 'curr_ID' ) )
			{	// Update currency of the current Order:
				$cart_Order->set( 'curr_ID', $curr_ID );
				$cart_Order->dbupdate();
			}
		}
	}


	/**
	 * Update Item in shopping cart
	 *
	 * @param integer|NULL Quantity of products,
	 *                     0 - means to delete the item/product from the cart completely,
	 *                     NULL - to use quantity from request param 'qty' with default value '1'.
	 * @param integer|NULL Item/Product ID,
	 *                     NULL - to use item ID from request param 'item_ID'.
	 * @return boolean TRUE on successful updating,
	 *                 FALSE on failed e.g. if a requested item doesn't exist
	 */
	function update_item( $qty = NULL, $item_ID = NULL )
	{
		global $Session, $Messages;

		if( ! $cart_Order = & $this->get_Order( true ) )
		{	// Cannot find and create Order:
			return false;
		}

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

		// Try to update Item in the current Order:
		$update_status = $cart_Order->update_item( $qty, $item_ID );

		if( strpos(  $update_status, 'updated:' ) === 0 )
		{	// If at least one Item field has been updated:
			$updated_fields = explode( ',', substr( $update_status, 8 ) );
		}

		switch( $update_status )
		{
			case 'no_item':
				return false;

			case 'out_stock':
				$Messages->add( sprintf( T_('Product "%s" is out of stock and additional orders cannot be made.'), $cart_Item->get( 'title' ) ) , 'error' );
				return false;

			case 'no_price':
				$CurrencyCache = & get_CurrencyCache();
				$cart_Currency = & $CurrencyCache->get_by_ID( $curr_ID, false, false );
				$Messages->add( sprintf( T_('Product "%s" cannot be added to the cart because it has no price in the cart currency (%s).'), $cart_Item->get( 'title' ), $cart_Currency->get( 'code' ) ), 'error' );
				return false;

			case 'added':
				$cart_is_updated = true;
				$Messages->add( sprintf( T_('Product "%s" has been added to the cart.'), $cart_Item->get( 'title' ) ), 'success' );
				break;

			case 'updated':
				$cart_is_updated = true;
				foreach( $updated_fields as $updated_field )
				{
					switch( $updated_field )
					{
						case 'qty':
							$Messages->add( sprintf( T_('Quantity for product "%s" has been changed.'), $cart_Item->get( 'title' ) ), 'success' );
							break;
						case 'unit_price':
							$Messages->add( sprintf( T_('Unit price for product "%s" has been changed.'), $cart_Item->get( 'title' ) ), 'success' );
							break;
					}
				}
				break;

			case 'deleted':
				$cart_is_updated = true;
				$Messages->add( sprintf( T_('Product "%s" has been removed from the cart.'), $cart_Item->get( 'title' ) ), 'success' );
				break;
		}

		if( $cart_is_updated )
		{	// BLOCK CACHE INVALIDATION:
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
	function refresh()
	{
		global $Session, $Messages, $servertimenow, $cart_last_updated;

		$cart_Order = & $this->get_Order();

		$Messages->clear();
		$ItemCache = & get_ItemCache();
		$cart_is_updated = false;

		$cart_items_data = $this->get_items_data();

		foreach( $cart_items_data as $cart_item_ID => $cart_item_data )
		{
			$cart_Item = & $ItemCache->get_by_ID( $cart_item_ID, false, false );

			// Check stock availability:
			$qty = $this->get_quantity( $cart_item_ID );
			$in_stock = intval( $cart_Item->qty_in_stock );
			if( ! $cart_Item->can_be_ordered_if_no_stock && ( $qty > $in_stock ) )
			{
				if( $in_stock > 0 )
				{
					$Messages->add_to_group( sprintf( 'You had %d "%s" in your cart but we only have %d in stock right now. The quantity has been adjusted in your cart', $qty, $cart_Item->get_title(), $cart_Item->qty_in_stock ), 'note', T_('Cart update').':' );
					$cart_Order->update_item( $cart_item_ID, $in_stock );
					$cart_is_updated = true;
				}
				else
				{
					$Messages->add_to_group( sprintf( 'You had %d "%s" in your cart but we don\'t have any in stock right now. The item has been removed from your cart.', $qty, $cart_Item->get_title() ), 'note', T_('Cart update').':' );
					$cart_Order->update_item( $cart_item_ID, 0 );
					$cart_is_updated = true;
					continue;
				}
			}

			// Refresh Item data(especially unit price):
			$cart_is_updated = $cart_Order->refresh_item( $cart_item_ID ) || $cart_is_updated;
		}

		if( $cart_is_updated )
		{	// BLOCK CACHE INVALIDATION:
			BlockCache::invalidate_key( 'cart', $Session->ID ); // Cart has updated for current session
		}

		$Session->set( 'cart_last_updated', $servertimenow );
		$Session->dbsave();

		return $Messages->display( NULL, NULL, false, NULL );
	}


	/**
	 * Clear shopping cart
	 * Used after successful checkout
	 */
	function clear()
	{
		global $Session, $servertimenow;

		$Session->delete( 'cart_ord_ID' );
		$Session->set( 'cart_last_updated', $servertimenow );
		$Session->dbsave();

		// BLOCK CACHE INVALIDATION:
		BlockCache::invalidate_key( 'cart', $Session->ID ); // Cart has updated for current session
	}


	/**
	 * Load payments for the current Session
	 *
	 * @param integer|NULL Session ID or NULL to use current Session
	 */
	function load_payments()
	{
		global $DB, $Session;

		if( $this->payments !== NULL )
		{	// Don't load twice:
			return;
		}

		$this->payments = array();

		// Load payments for current session and for all processors:
		$SQL = new SQL( 'Get payment for session #'.$Session->ID );
		$SQL->SELECT( '*' );
		$SQL->FROM( 'T_order__payment' );
		$SQL->WHERE( 'payt_ord_ID = '.$DB->quote( $this->get_order_ID() ) );
		// TODO: Temprorary commented in order to test
		//$SQL->WHERE( 'payt_status != "success"' );
		$SQL->ORDER_BY( 'payt_ID DESC' );
		$SQL->GROUP_BY( 'payt_processor' );
		$payments = $DB->get_results( $SQL, ARRAY_A );

		foreach( $payments as $payment )
		{
			$this->payments[ $payment['payt_processor'] ] = array();
			foreach( $payment as $field_name => $field_value )
			{	// Store field name without DB table prefix "payt_":
				$this->payments[ $payment['payt_processor'] ][ substr( $field_name, 5 ) ] = $field_value;
			}
		}
	}


	/**
	 * Get payment of the current Session by processor
	 *
	 * @param string Payment processor name, e.g. 'Stripe', 'PayPal'
	 * @param integer|NULL Session ID or NULL to use current Session
	 * @return array|NULL Payment data
	 */
	function get_payment( $processor )
	{
		if( empty( $processor ) )
		{	// Don't allow empty payment processor name:
			return NULL;
		}

		// Load payments from DB:
		$this->load_payments();

		return isset( $this->payments[ $processor ] ) ? $this->payments[ $processor ] : NULL;
	}


	/**
	 * Get payment field value
	 *
	 * @param string Payment field name withour prefix 'payt_'
	 * @param string Payment processor name, e.g. 'Stripe', 'PayPal'
	 * @return string Field value
	 */
	function get_payment_field( $field_name, $processor )
	{
		$payment = $this->get_payment( $processor );

		return isset( $payment[ $field_name ] ) ? $payment[ $field_name ] : NULL;
	}


	/**
	 * Get payment of the current Session by payment ID
	 *
	 * @param integer Payment ID
	 * @param string Payment processor name, e.g. 'Stripe', 'PayPal'
	 * @return array|NULL Payment data
	 */
	function get_payment_by_ID( $payment_ID, $processor )
	{
		$payment_ID = intval( $payment_ID );

		if( empty( $payment_ID ) )
		{	// Wrong payment ID:
			return NULL;
		}

		if( isset( $this->payments[ $processor ] ) &&
		    $this->payments[ $processor ]['ID'] == $payment_ID )
		{	// Get payment from cache:
			return $this->payments[ $processor ];
		}

		if( ! isset( $this->payments ) )
		{	// Initialize payments cache:
			$this->payments = array();
		}

		// Load payment by ID from DB:
		global $DB;
		$SQL = new SQL( 'Get payment by ID #'.$payment_ID );
		$SQL->SELECT( '*' );
		$SQL->FROM( 'T_order__payment' );
		$SQL->WHERE( 'payt_ID = '.$DB->quote( $payment_ID ) );
		$SQL->WHERE_and( 'payt_processor = '.$DB->quote( $processor ) );
		$payment = $DB->get_row( $SQL, ARRAY_A );

		if( empty( $payment ) )
		{	// Not found payment by ID in DB:
			return NULL;
		}

		// Save payment into cache:
		$this->payments[ $processor ] = array();
		foreach( $payment as $field_name => $field_value )
		{	// Store field name without DB table prefix "payt_":
			$this->payments[ $processor ][ substr( $field_name, 5 ) ] = $field_value;
		}

		return $this->payments[ $processor ];
	}


	/**
	 * Save(Insert/Update) payment of the current Session for requested processor
	 *
	 * @param string Payment processor name, e.g. 'Stripe', 'PayPal'
	 * @param array New/changed payment data
	 * @return array Payment data
	 */
	function save_payment( $processor, $data = array() )
	{
		global $DB, $current_User, $Session;

		foreach( $data as $field_key => $field_value )
		{	// Check what fields can be updated:
			if( ! in_array( $field_key, array( 'user_ID', 'status', 'processor', 'secret', 'proc_session_ID', 'return_info' ) ) )
			{	// Unset wrong payment field:
				unset( $data[ $field_key ] );
			}
		}

		// Try to get payment from DB or cache:
		$payment = $this->get_payment( $processor );

		if( empty( $payment ) )
		{	// Insert new payment:
			$cart_Order = & $this->get_Order( true );
			$this->payments[ $processor ] = array_merge( array(
				'ID'              => NULL,
				'user_ID'         => is_logged_in() ? $current_User->ID : NULL,
				'ord_ID'          => $cart_Order->ID,
				'status'          => 'new',
				'processor'       => $processor,
				'secret'          => generate_random_key(),
				'proc_session_ID' => NULL,
				'return_info'     => NULL,
			), $data );
			$DB->query( 'INSERT INTO T_order__payment ( payt_'.implode( ', payt_', array_keys( $this->payments[ $processor ] ) ).' )
				VALUES ( '.$DB->quote( $this->payments[ $processor ] ).' )' );
			// Set ID of new inserted payment:
			$this->payments[ $processor ]['ID'] = $DB->insert_id;
		}
		elseif( ! empty( $data ) )
		{	// Update payment if at aleast one field is updated:
			$update_fields = array();
			foreach( $data as $field_key => $field_value )
			{
				$update_fields[] = 'payt_'.$field_key.' = '.$DB->quote( $field_value );
				// Update field values in cache:
				$this->payments[ $processor ][ $field_key ] = $field_value;
			}
			$DB->query( 'UPDATE T_order__payment
				  SET '.implode( ', ', $update_fields ).'
				WHERE payt_ID = '.$DB->quote( $payment['ID'] ) );
		}

		// Update Order status to 'pending' or 'paid':
		if( $this->payments[ $processor ]['status'] == 'pending' || $this->payments[ $processor ]['status'] == 'success' )
		{
			$OrderCache = & get_OrderCache();
			if( $payment_Order = & $OrderCache->get_by_ID( $this->payments[ $processor ]['ord_ID'], false, false ) )
			{
				$payment_Order->set( 'status', ( $this->payments[ $processor ]['status'] == 'success' ? 'paid' : 'pending' ) );
				$payment_Order->dbupdate();
				if( $Session->get( 'cart_ord_ID' ) == $payment_Order->ID )
				{	// Forget current Order in order to allow make new:
					$this->clear();
				}
			}
		}

		return $this->payments[ $processor ];
	}


	/**
	 * Get array of items data
	 *
	 * @return array
	 *           Key is Item ID,
	 *           Value is array:
	 *             'qty' - Quantity
	 *             'unit_price' - Unit Price
	 */
	function get_items_data()
	{
		$Order = & $this->get_Order();
		return $Order ? $Order->get_items_data() : array();
	}


	/**
	 * Get Item data value in this Cart
	 *
	 * @param integer Item ID
	 * @param string Field key: 'qty', 'unit_price'
	 * @return mixed Field value
	 */
	function get_item_data( $item_ID, $field )
	{
		$Order = & $this->get_Order();
		return $Order ? $Order->get_item_data( $item_ID, $field ) : array();
	}


	/**
	 * Get Item by ID
	 *
	 * @param integer Item ID
	 * @return object|NULL|false Item object
	 */
	function & get_Item( $item_ID )
	{
		if( $Order = & $this->get_Order() )
		{
			$cart_Item = & $Order->get_Item( $item_ID );
		}
		else
		{
			$cart_Item = NULL;
		}

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

		if( $this->get_item_data( $item_ID, 'unit_price' ) < 0 )
		{
			$title .= "\n".get_icon( 'warning_yellow' ).' <span class="text-danger">'.sprintf( T_('This product cannot be purchased in %s.'), $this->get_currency_code() ).'</span>';
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
		$item_qty = $this->get_item_data( $item_ID, 'qty' );
		return $item_qty === NULL ? 0 : $item_qty;
	}


	/**
	 * Get currency object of requested item in this shopping cart
	 *
	 * @param integer Item ID
	 * @return object Currency object
	 */
	function & get_Currency()
	{
		if( $cart_Order = & $this->get_Order() )
		{
			$Currency = & $cart_Order->get_Currency();
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
	function get_currency_code()
	{
		$Currency = & $this->get_Currency();

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
		$Currency = & $this->get_Currency();

		$unit_price = $this->get_item_data( $item_ID, 'unit_price' );
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


	/**
	 * Get total price of requested item in this shopping cart
	 *
	 * @param integer Item ID
	 * @param boolean TRUE to return a formatted value for display
	 * @return float total item price
	 */
	function get_total_price( $item_ID, $format = false )
	{
		$Currency = & $this->get_Currency();

		$unit_price = $this->get_item_data( $item_ID, 'unit_price' );
		if( $unit_price < 0 )
		{
			return $format
				? get_icon( 'warning_yellow', 'imgtag', array( 'title' => sprintf( T_('This product cannot be purchased in %s.'), $Currency->get( 'code' ) ) ) )
				: 0;
		}

		$total_price = $unit_price * $this->get_quantity( $item_ID );

		return $format
			? $Currency->get( 'shortcut' ).'&nbsp;'.number_format( $total_price, 2 )
			: $total_price;
	}


	/**
	 * Get total price of all items in this shopping cart
	 *
	 * @return float total cart price
	 */
	function get_cart_total()
	{
		$cart_total = 0.00;
		$items_data = $this->get_items_data();
		foreach( $items_data as $item_ID => $item_data )
		{
			if( $item_data['unit_price'] > 0 )
			{
				$cart_total += $item_data['unit_price'] * $item_data['qty'];
			}
		}

		return $cart_total;
	}


	/**
	 * Get current Order (Create new if not found)
	 *
	 * @param boolean TRUE to create new Order if not found
	 * @return object Order
	 */
	function & get_Order( $create = false )
	{
		global $Session;

		// Try to get Order by ID from Session:
		$OrderCache = & get_OrderCache();
		$Order = & $OrderCache->get_by_ID( $Session->get( 'cart_ord_ID' ), false, false );

		if( $create && ! $Order )
		{	// Create new Order if requested:
			$Order = new Order();
			$Order->set( 'curr_ID', get_Currency()->ID );
			if( is_logged_in() )
			{
				global $current_User;
				$Order->set( 'user_ID', $current_User->ID );
			}
			$Order->dbinsert();
			// Store Order ID in Session:
			$Session->set( 'cart_ord_ID', $Order->ID );
			$Session->dbsave();
		}

		return $Order;
	}


	/**
	 * Get ID of current Order
	 *
	 * @param boolean TRUE to create new Order if not found
	 * @return integer|boolean Order ID
	 */
	function get_order_ID( $create = false )
	{
		$Order = & $this->get_Order( $create );

		return $Order ? $Order->ID : false;
	}
}

?>