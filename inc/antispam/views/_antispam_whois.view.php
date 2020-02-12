<?php
/**
 * This file implements the UI for WHOIS query tool
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $template_action;

$query = param( 'query', 'string', NULL );

if( empty( $template_action ) )
{
	$Form = new Form( NULL, '', 'get' );

	$Form->hidden( 'action', 'whois' );
	$Form->hidden_ctrl();

	$Form->begin_form( 'fform' );

	$Form->begin_fieldset( T_('Check domain registration (WHOIS)...') );
	$Form->text_input( 'query', $query, 50, T_('Enter IP address or domain to query'), '', array( 'maxlength' => 255, 'required' => true ) );
	$Form->end_fieldset();

	$Form->end_form( array( array( 'submit', '', T_( 'Submit' ), 'SaveButton' ) ) );
}
else
{
	$block_item_Widget = new Widget( 'block_item' );
	$block_item_Widget->title = 'WHOIS - '.$query.get_manual_link('antispam-whois');
	echo $block_item_Widget->replace_vars( $block_item_Widget->params[ 'block_start' ] );

	echo antispam_get_whois( $query );

	$block_item_Widget->disp_template_replaced( 'block_end' );

	echo '<div class="form-group">';
	echo '<a href="'.regenerate_url( 'query' ).'" class="btn btn-primary">'.T_('Check another domain registration').'</a>';
	echo '</div>';
}
?>