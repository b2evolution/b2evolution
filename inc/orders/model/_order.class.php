<?php
/**
 * This file implements the Order class
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Order
 *
 * @package evocore
 */
class Order extends DataObject
{
	var $user_ID;
	var $status;
	var $curr_ID;

	/**
	 * @var array Cart data:
	 *      Key is Item ID,
	 *      Value is array:
	 *        'qty' - Quantity
	 *        'unit_price' - Unit Price
	 */
	var $items_data = NULL;

	/**
	 * Constructor
	 *
	 * @param table Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_order__order', 'ord_', 'ord_ID' );

		if( $db_row )
		{
			$this->ID       = $db_row->ord_ID;
			$this->user_ID  = $db_row->ord_user_ID;
			$this->status   = $db_row->ord_status;
			$this->curr_ID  = $db_row->ord_curr_ID;
		}
	}


	/**
	 * Update the DB based on previously recorded changes
	 *
	 * @return boolean true on success, false on failure to update, NULL if no update necessary
	 */
	function dbupdate()
	{
		global $DB;

		$DB->begin();

		$is_currency_changed = isset( $this->dbchanges['ord_curr_ID'] );

		$result = parent::dbupdate();

		if( $result && $is_currency_changed )
		{	// Refresh all order items when currency was changed:
			$ItemCache = & get_ItemCache();
			$items_data = $this->get_items_data();
			foreach( $items_data as $item_ID => $item_data )
			{	// Refresh Item data(especially unit price):
				$this->refresh_item( $item_ID );
			}
		}

		if( $result )
		{
			$DB->commit();
		}
		else
		{
			$DB->rollback();
		}

		return $result;
	}


	/**
	 * Get array of items objects
	 *
	 * @return array
	 */
	function get_items_data()
	{
		if( $this->items_data === NULL )
		{	// Load all order items into the cache array:
			$this->items_data = array();
			if( ! empty( $this->ID ) )
			{
				global $DB;
				$SQL = new SQL( 'Load Items data of the Order #'.$this->ID );
				$SQL->SELECT( 'oitm_item_ID, oitm_qty, oitm_unit_price' );
				$SQL->FROM( 'T_order__item' );
				$SQL->WHERE( 'oitm_ord_ID = '.$this->ID );
				$items_data = $DB->get_results( $SQL, ARRAY_A );
				if( ! empty( $items_data ) )
				{	// Load all order items in single query:
					$ItemCache = & get_ItemCache();
					$ItemCache->load_list( array_column( $items_data, 'oitm_item_ID' ) );
					foreach( $items_data as $item_data )
					{
						if( ( $order_Item = & $ItemCache->get_by_ID( $item_data['oitm_item_ID'], false, false ) ) &&
						    $order_Item->can_be_displayed() )
						{	// If Item exists in DB and can be displayed for current User:
							$this->items_data[ $item_data['oitm_item_ID'] ] = array(
									'qty'        => $item_data['oitm_qty'],
									'unit_price' => $item_data['oitm_unit_price'],
								);
						}
					}
				}
			}
		}

		return $this->items_data;
	}


	/**
	 * Get Item data value in this Order
	 *
	 * @param integer Item ID
	 * @param string Field key: 'qty', 'unit_price'
	 * @return mixed Field value
	 */
	function get_item_data( $item_ID, $field )
	{
		$items_data = $this->get_items_data();

		if( ! isset( $items_data[ $item_ID ] ) ||
		    ! isset( $items_data[ $item_ID ][ $field ] ) )
		{	// No Item in this Order or worng field:
			return NULL;
		}

		return $items_data[ $item_ID ][ $field ];
	}


	/**
	 * Get Item by ID
	 *
	 * @param integer Item ID
	 * @return object|NULL|false Item object
	 */
	function & get_Item( $item_ID )
	{
		$items_data = $this->get_items_data();
		if( isset( $items_data[ $item_ID ] ) )
		{	// This Order has the requested Item:
			$ItemCache = & get_ItemCache();
			$order_Item = & $ItemCache->get_by_ID( $item_ID, false, false );
		}
		else
		{	// This Order has no requested Item:
			$order_Item = NULL;
		}

		return $order_Item;
	}


	/**
	 * Update Item in this Order
	 *
	 * @param integer Quantity of products,
	 *                0 - means to delete the item/product from the Order completely.
	 * @param integer Item/Product ID.
	 * @return string Status of updating:
	 *         - 'no_item' - No requested item in DB or it cannot be displayed on front-office for current user,
	 *         - 'added' - Item has been added into this Order,
	 *         - 'updated:qty' - Only quantity has been updated,
	 *         - 'updated:unit_price' - Only unit price has been updated,
	 *         - 'updated:qty,unit_price' - Quantity and unit price has been updated,
	 *         - 'deleted' - Item has been deleted from this Order,
	 *         - 'out_stock' - Item cannot be ordered if no stock,
	 *         - 'no_price' - Item has no price in the currency,
	 *         - false - 
	 */
	function update_item( $qty, $item_ID )
	{
		$ItemCache = & get_ItemCache();
		if( ! ( $order_Item = & $ItemCache->get_by_ID( $item_ID, false, false ) ) ||
		    ! $order_Item->can_be_displayed() )
		{	// No requested item in DB or it cannot be displayed on front-office for current user:
			return 'no_item';
		}

		global $DB;

		if( $qty <= 0 && $order_Item )
		{	// Delete Item from Order:
			$DB->query( 'DELETE FROM T_order__item
				WHERE oitm_ord_ID = '.$DB->quote( $this->ID ).'
				  AND oitm_qty = '.$DB->quote( $item_ID ) );
			unset( $this->items_data[ $item_ID ] );
			return 'deleted';
		}
		elseif( $qty > 0 )
		{	// Add/Update quantity of items in cart:
			if( ! $order_Item->can_be_ordered_if_no_stock && ( $qty > $order_Item->qty_in_stock ) )
			{	// Quantity exceeds stock and item cannot be ordered if no stock:
				return 'out_stock';
			}

			// Check best pricing for item:
			$best_pricing = $order_Item->get_current_best_pricing( $this->get( 'curr_ID' ), NULL, $qty );
			if( empty( $best_pricing ) )
			{	// Item has no price in the currency:
				return 'no_price';
			}

			$unit_price = floatval( $best_pricing['iprc_price'] );

			if( $this->get_Item( $item_ID ) )
			{	// Update Item in this Order:
				$updated_fields = array();
				if( $this->get_item_data( $item_ID, 'qty' ) != $qty )
				{	// Quantity has been changed:
					$this->items_data[ $item_ID ]['qty'] = $qty;
					$updated_fields['qty'] = 'oitm_qty = '.$DB->quote( $qty );
				}

				if( $this->get_item_data( $item_ID, 'unit_price' ) != $unit_price )
				{	// Unit price has been changed:
					$this->items_data[ $item_ID ]['unit_price'] = $unit_price;
					$updated_fields['unit_price'] = 'oitm_unit_price = '.$DB->quote( $this->items_data[ $item_ID ]['unit_price'] );
				}

				if( ! empty( $updated_fields ) )
				{	// If at least one Item field should be updated:
					$DB->query( 'UPDATE T_order__item
						  SET '.implode( ', ', $updated_fields ).'
						WHERE oitm_ord_ID = '.$DB->quote( $this->ID ).'
						  AND oitm_item_ID = '.$DB->quote( $item_ID ) );
					return 'updated:'.implode( ',', array_keys( $updated_fields ) );
				}
			}
			else
			{	// Add new Item into this Order:
				$DB->query( 'INSERT INTO T_order__item ( oitm_ord_ID, oitm_item_ID, oitm_qty, oitm_unit_price )
					VALUES ( '.$DB->quote( $this->ID ).', '.$DB->quote( $item_ID ).', '.$DB->quote( $qty ).', '.$DB->quote( $unit_price ).' )' );
				// Add Item data into cache:
				$this->items_data[ $item_ID ] = array(
					'qty'        => $qty,
					'unit_price' => $unit_price
				);
				return 'added';
			}
		}

		// No update:
		return false;
	}


	/**
	 * Refresh Item (unit price)
	 *
	 * @param integer Item ID
	 * @return boolean TRUE on updating, FALSE - if item data was not updated
	 */
	function refresh_item( $item_ID )
	{
		if( ! ( $Item = & $this->get_Item( $item_ID ) ) )
		{	// No found Item:
			return false;
		}

		$new_price = $Item->get_current_best_pricing( $this->get( 'curr_ID' ), NULL, $this->get_item_data( $item_ID, 'qty' ) );
		if( $this->get_item_data( $item_ID, 'unit_price' ) == $new_price )
		{	// Don't update because new price is same as current:
			return false;
		}

		global $DB;

		// Update only when new price is different:
		$this->items_data[ $item_ID ]['unit_price'] = ( $new_price ? $new_price['iprc_price'] : -1 );
		$DB->query( 'UPDATE T_order__item
				SET oitm_unit_price = '.$DB->quote( $this->items_data[ $item_ID ]['unit_price'] ).'
			WHERE oitm_ord_ID = '.$DB->quote( $this->ID ).'
				AND oitm_item_ID = '.$DB->quote( $item_ID ) );

		return true;
	}


	/**
	 * Get currency object of this Order
	 *
	 * @return object Currency object
	 */
	function & get_Currency()
	{
		$CurrencyCache = & get_CurrencyCache();
		$Currency = & $CurrencyCache->get_by_ID( $this->get( 'curr_ID', false, false ) );

		return $Currency;
	}
}
?>