<?php
/**
 * This file implements the payment class
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
 * Payment
 *
 * @package evocore
 */
class Payment extends DataObject
{
	var $user_ID;
	var $sess_ID;
	var $status;
	var $processor;
	var $secret;
	var $proc_session_ID;
	var $return_info;

	/**
	 * @var object Order
	 */
	var $Order;

	/**
	 * Constructor
	 *
	 * @param table Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_order__payment', 'payt_', 'payt_ID' );

		if( $db_row )
		{
			$this->ID              = $db_row->payt_ID;
			$this->user_ID         = $db_row->payt_user_ID;
			$this->ord_ID          = $db_row->payt_ord_ID;
			$this->status          = $db_row->payt_status;
			$this->processor       = $db_row->payt_processor;
			$this->secret          = $db_row->payt_secret;
			$this->proc_session_ID = $db_row->payt_proc_session_ID;
			$this->return_info     = $db_row->payt_return_info;
		}
	}


	/**
	 * Get payment Order
	 *
	 * @return object Order
	 */
	function & get_Order()
	{
		$OrderCache = & get_OrderCache();
		$Order = & $OrderCache->get_by_ID( $this->get( 'ord_ID' ), false, false );

		return $Order;
	}
}

?>