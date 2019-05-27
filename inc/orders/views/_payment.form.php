<?php
/**
 * This file implements the UI view for order payment details
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


global $edited_Payment;

$Form = new Form( NULL, 'payment_checkchanges', 'post', 'compact' );

$Form->begin_form( 'fform', T_('Payment').get_manual_link( 'payment-details' ) );

$Form->info_field( 'ID', $edited_Payment->ID );

$Form->info_field( T_('User'), payment_td_user( $edited_Payment->get( 'user_ID' ) ) );

$Form->info_field( T_('Session'), $edited_Payment->get( 'sess_ID' ) );

$Form->info_field( T_('Status'), $edited_Payment->get( 'status' ) );

$Form->info_field( T_('Processor'), $edited_Payment->get( 'processor' ) );

$Form->info_field( T_('Secret'), $edited_Payment->get( 'secret' ) );

$Form->info_field( T_('Processor session ID'), $edited_Payment->get( 'proc_session_ID' ) );

$Form->info_field( T_('Return info'), '<pre>'.$edited_Payment->get( 'return_info' ).'</pre>' );

$Form->end_form()
?>