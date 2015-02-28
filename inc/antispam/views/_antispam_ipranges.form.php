<?php
/**
 * This file display the Antispam IP ranges
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Slug
 */
global $edited_IPRange;

// Determine if we are creating or updating...
global $action;
$creating = $action == 'iprange_new';

$Form = new Form( NULL, 'iprange_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action,iprange_ID' ) );

$Form->begin_form( 'fform', $creating ?  T_('New IP Range') : T_('IP Range') );

	$Form->add_crumb( 'iprange' );
	$Form->hidden( 'action',  $creating ? 'iprange_create' : 'iprange_update' );
	$Form->hidden_ctrl();
	$Form->hidden( 'tab', get_param( 'tab' ) );
	$Form->hidden( 'tab3', get_param( 'tab3' ) );
	$Form->hidden( 'iprange_ID', param( 'iprange_ID', 'integer', 0 ) );

	$Form->select_input_array( 'aipr_status', $edited_IPRange->get( 'status' ), aipr_status_titles() , T_('Status'), '', array( 'force_keys_as_values' => true, 'background_color' => aipr_status_colors(), 'required' => true ) );

	$Form->text_input( 'aipr_IPv4start', int2ip( $edited_IPRange->get( 'IPv4start' ) ), 50, T_('IP Range Start'), '', array( 'maxlength' => 15, 'required' => true ) );

	$Form->text_input( 'aipr_IPv4end', int2ip( $edited_IPRange->get( 'IPv4end' ) ), 50, T_('IP Range End'), '', array( 'maxlength' => 15, 'required' => true ) );

	$Form->info( T_('User count'), (int)$edited_IPRange->get( 'user_count' ) );

	$Form->info( T_('Block count'), (int)$edited_IPRange->get( 'block_count' ) );

$Form->end_form( array( array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' ) ) );

?>
