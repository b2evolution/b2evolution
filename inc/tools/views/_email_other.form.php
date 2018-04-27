<?php
/**
 * This file implements the UI view for the other email settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
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

// --------------------------------------------

$Form->begin_fieldset( T_('After each new post or comment...').get_manual_link('after_each_post_settings') );
	$Form->radio_input( 'outbound_notifications_mode', $Settings->get('outbound_notifications_mode'),
		array(
			array( 'value'=>'off', 'label'=>T_('Off'), 'note'=>T_('No notification about your new content will be sent out.') ),
			array( 'value'=>'immediate', 'label'=>T_('Immediate'), 'note'=>T_('This is guaranteed to work but may create an annoying delay after each post or comment publication.') ),
			array( 'value'=>'cron', 'label'=>T_('Asynchronous'), 'note'=>T_('Recommended if you have your scheduled jobs properly set up.') )
		),
		T_('Outbound pings & email notifications'),
		array( 'lines' => true ) );
$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Email campaign throttling').get_manual_link( 'email-other-settings' ) );

	$Form->radio_input( 'email_campaign_send_mode', $Settings->get( 'email_campaign_send_mode' ),
		array(
			array( 'value' => 'immediate', 'label' => T_('Immediate'), 'note' => T_('Press "Next" after each chunk') ),
			array( 'value' => 'cron', 'label' => T_('Asynchronous'), 'note' => T_('A scheduled job will send chunks') )
		),
		T_('Sending'),
		array( 'lines' => true ) );

	$Form->text_input( 'email_campaign_chunk_size', $Settings->get( 'email_campaign_chunk_size' ), 5, T_('Chunk Size'), T_('emails at a time'), array( 'maxlength' => 10 ) );

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Email notification throttling').get_manual_link( 'email-other-settings' ) );

	$template_names = array(
			'account_new' => T_('New account'),
			'account_activate' => T_('Activate account'),
			'account_activated' => T_('Account activated'),
			'account_password_reset' => T_('Password reset'),
			'account_changed' => T_('Account change'),
			'account_reported' => T_('Reported account'),
			'account_closed' => T_('Account closed'),
			'private_message_new' => T_('New private message'),
			'contact_message_new' => T_('New contact message'),
			'post_assignment' => T_('Post assignment'),
			//'post_by_email_report',
			'comment_spam' => T_('Comment spam'),
			'scheduled_task_error_report' => T_('Scheduled task error'),
			'automation_owner_notification' => T_('Automation notification'),
			'newsletter_test' => T_('Email campaign test'),
		);

	foreach( $template_names as $template => $label )
	{
		$Form->radio_input( $template.'_notifications_mode', $Settings->get( $template.'_notifications_mode' ),
			array(
				array( 'value' => 'immediate', 'label' => T_('Immediate'), 'note' => T_('Press "Next" after each chunk') ),
				array( 'value' => 'cron', 'label' => T_('Asynchronous'), 'note' => T_('A scheduled job will send chunks') )
			),
			$label,
			array( 'lines' => true ) );
	}

	$Form->text_input( 'email_notifications_chunk_size', $Settings->get( 'email_notifications_chunk_size' ), 5, T_('Chunk Size'), T_('emails at a time'), array( 'maxlength' => 10 ) );

$Form->end_fieldset();


if( $current_User->check_perm( 'emails', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', '', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>