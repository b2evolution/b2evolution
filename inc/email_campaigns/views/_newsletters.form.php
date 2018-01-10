<?php
/**
 * This file implements the UI view for Emails > Newsletters > Edit
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $current_User, $action, $edited_Newsletter;

$creating = is_create_action( $action );

$Form = new Form( NULL, 'newsletter_form', 'post', 'compact' );

if( ! $creating && $current_User->check_perm( 'emails', 'edit' ) )
{	// Display a button to delete existing newsletter if current User has a perm:
	$Form->global_icon( T_('Delete this list!'), 'delete', regenerate_url( 'action', 'action=delete&amp;'.url_crumb( 'newsletter' ) ) );
}
// Display a button to close the newsletter form:
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action,enlt_ID' ) );

$Form->begin_form( 'fform', ( $creating ?  T_('Create list') : T_('List') ).get_manual_link( 'editing-an-email-list' ) );

$Form->add_crumb( 'newsletter' );
$Form->hidden( 'ctrl', 'newsletters' );
$Form->hidden( 'enlt_ID', $edited_Newsletter->ID );
$Form->hidden( 'action',  $creating ? 'create' : 'update' );

$Form->checkbox( 'enlt_active', $edited_Newsletter->get( 'active' ), T_('Active') );

$Form->text_input( 'enlt_name', $edited_Newsletter->get( 'name' ), 30, T_('Name'), '', array( 'maxlength' => 255, 'required' => true ) );

$Form->text_input( 'enlt_label', $edited_Newsletter->get( 'label' ), 150, T_('Label'), '', array( 'maxlength' => 255 ) );

$Form->text_input( 'enlt_order', $edited_Newsletter->get( 'order' ), 10, T_('Order'), '', array( 'maxlength' => 11 ) );

$buttons = array();
if( $current_User->check_perm( 'emails', 'edit' ) )
{	// Display a button to create/update newsletter if current User has a perm:
	if( $creating )
	{	// Create:
		$buttons[] = array( 'submit', 'actionArray[create]', T_('Record'), 'SaveButton' );
	}
	else
	{	// Update:
		$buttons[] = array( 'submit', 'actionArray[update]', T_('Save Changes!'), 'SaveButton' );
	}
}
$Form->end_form( $buttons );

if( $edited_Newsletter->ID > 0 )
{	// Display campaigns attached to this Newsletter:
	campaign_results_block( array(
			'enlt_ID'               => $edited_Newsletter->ID,
			'display_create_button' => false
		) );

	// Display users which are subscribed to this Newsletter:
	users_results_block( array(
			'enlt_ID'              => $edited_Newsletter->ID,
			'enls_subscribed'      => NULL,
			'filterset_name'       => 'nltsub_'.$edited_Newsletter->ID,
			'results_param_prefix' => 'nltsub_',
			'results_title'        => T_('Subscribers').get_manual_link( 'list-subscribers' ),
			'results_order'        => '/enls_last_sent_manual_ts/D',
			'page_url'             => get_dispctrl_url( 'newsletters', 'action=edit&amp;enlt_ID='.$edited_Newsletter->ID ),
			'display_ID'           => false,
			'display_btn_adduser'  => false,
			'display_btn_addgroup' => false,
			'display_avatar'       => false,
			'display_firstname'    => true,
			'display_lastname'     => true,
			'display_name'         => false,
			'display_gender'       => false,
			'display_country'      => false,
			'display_blogs'        => false,
			'display_source'       => false,
			'display_regdate'      => false,
			'display_regcountry'   => false,
			'display_update'       => false,
			'display_lastvisit'    => false,
			'display_contact'      => false,
			'display_reported'     => false,
			'display_group'        => false,
			'display_level'        => false,
			'display_status'       => false,
			'display_actions'      => false,
			'display_newsletter'   => false,
			'display_enls_subscribed'      => true,
			'display_enls_subscribed_ts'   => true,
			'display_enls_unsubscribed_ts' => true,
			'display_enls_sent_manual'     => true,
			'display_enls_send_count'      => true,
			'th_class_login'       => 'shrinkwrap',
			'td_class_login'       => '',
			'th_class_nickname'    => 'shrinkwrap',
			'td_class_nickname'    => '',
		) );
}
?>