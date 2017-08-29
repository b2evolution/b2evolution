<?php
/**
 * This file implements the UI view for the other email settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var User
 */
global $current_User;
/**
 * @var GeneralSettings
 */
global $Settings;

global $baseurl, $admin_url;

global $repath_test_output, $action;


$Form = new Form( NULL, 'settings_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'emailsettings' );
$Form->hidden( 'ctrl', 'email' );
$Form->hidden( 'tab', get_param( 'tab' ) );
$Form->hidden( 'tab3', get_param( 'tab3' ) );
$Form->hidden( 'action', 'settings' );

$Form->begin_fieldset( T_('Campaign/Newsletter throttling').get_manual_link( 'email-other-settings' ) );

	$Form->radio_input( 'email_campaign_send_mode', $Settings->get( 'email_campaign_send_mode' ),
		array(
			array( 'value' => 'immediate', 'label' => T_('Immediate'), 'note' => T_('Press "Next" after each chunk') ),
			array( 'value' => 'cron', 'label' => T_('Asynchronous'), 'note' => T_('A scheduled job will send chunks') )
		),
		T_('Sending'),
		array( 'lines' => true ) );

	$Form->text_input( 'email_campaign_chunk_size', $Settings->get( 'email_campaign_chunk_size' ), 5, T_('Chunk Size'), T_('emails at a time'), array( 'maxlength' => 10 ) );

	$Form->duration_input( 'email_campaign_cron_repeat', $Settings->get( 'email_campaign_cron_repeat' ), T_('Delay between chunks'), 'days', 'minutes', array( 'note' => T_('timing between scheduled job runs') ) );

$Form->end_fieldset();

if( $current_User->check_perm( 'emails', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', '', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>