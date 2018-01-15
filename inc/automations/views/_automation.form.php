<?php
/**
 * This file display the automation form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $edited_Automation, $action, $admin_url;

// Determine if we are creating or updating:
$creating = is_create_action( $action );

$Form = new Form( NULL, 'automation_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Cancel editing').'!', 'close', regenerate_url( 'action,autm_ID' ) );

$Form->begin_form( 'fform', ( $creating ?  T_('New automation') : T_('Automation') ).get_manual_link( 'automation-form' ) );

$Form->add_crumb( 'automation' );
$Form->hidden( 'action',  $creating ? 'create' : 'update' );
$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',autm_ID' : '' ) ) );

$Form->text_input( 'autm_name', $edited_Automation->get( 'name' ), 40, T_('Name'), '', array( 'maxlength' => 255, 'required' => true ) );

$Form->select_input_array( 'autm_status', $edited_Automation->get( 'status' ), autm_get_status_titles(), 'Status', '', array( 'force_keys_as_values' => true, 'required' => true ) );

$Form->end_form( array(
		array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' )
	) );
?>