<?php
/**
 * This file display the poll option form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $edited_Poll, $edited_PollOption, $action, $admin_url;


// Determine if we are creating or updating:
$creating = is_create_action( $action );

$Form = new Form( NULL, 'poll_option_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Cancel editing').'!', 'close', url_add_param( regenerate_url( 'action,popt_ID,blog' ), 'action=edit' ) );

$Form->begin_form( 'fform', sprintf( ( $creating ?  T_('New poll option for question "%s"') : T_('Poll option for question "%s"') ), $edited_Poll->get_name() )
		.get_manual_link( 'poll-option-form' ) );

	$Form->add_crumb( 'poll' );
	$Form->hidden( 'action', $creating ? 'create_option' : 'update_option' );
	$Form->hidden( 'pqst_ID', $edited_Poll->ID );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',popt_ID' : '' ) ) );

	$Form->text_input( 'popt_option_text', $edited_PollOption->get( 'option_text' ), 10, T_('Option'), '', array( 'maxlength' => 2000, 'required' => true, 'class' => 'large' ) );

	$Form->text_input( 'popt_order', $edited_PollOption->get( 'order' ), 10, T_('Order'), '', array( 'required' => ! $creating ) );

$Form->end_form( array( array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' ) ) );
?>