<?php
/**
 * This file implements the UI view for order payments
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $admin_url;

$SQL = new SQL( 'Get order payments' );
$SQL->SELECT( '*' );
$SQL->FROM( 'T_order__payment' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'COUNT( payt_ID )' );
$count_SQL->FROM( 'T_order__payment' );

// Create result set:
$Results = new Results( $SQL->get(), 'payt_', 'D', NULL, $count_SQL->get() );

$Results->title = T_('Payments').get_manual_link( 'orders-payments' );

$Results->cols[] = array(
		'th'       => T_('ID'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'order'    => 'payt_ID',
		'td'       => '$payt_ID$',
	);

$Results->cols[] = array(
		'th'       => T_('User'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'order'    => 'payt_user_ID',
		'td'       => '%payment_td_user( #payt_user_ID# )%',
	);

$Results->cols[] = array(
		'th'       => T_('Order'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'order'    => 'payt_ord_ID',
		'td'       => '$payt_ord_ID$',
	);

$Results->cols[] = array(
		'th'       => T_('Status'),
		'order'    => 'payt_status',
		'td'       => '$payt_status$',
	);

$Results->cols[] = array(
		'th'       => T_('Processor'),
		'order'    => 'payt_processor',
		'td'       => '$payt_processor$',
	);

$Results->cols[] = array(
		'th'       => T_('Actions'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td'       => action_icon( T_('View'), 'magnifier', $admin_url.'?ctrl=payments&amp;payt_ID=$payt_ID$&amp;action=view' ),
	);

$Results->display();
?>