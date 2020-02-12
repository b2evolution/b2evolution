<?php
/**
 * This file implements the UI view for the general settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $current_User, $admin_url;
global $test_mail_output, $email_send_allow_php_mail;


$Form = new Form( NULL, 'settings_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'emailsettings' );
$Form->hidden( 'ctrl', 'email' );
$Form->hidden( 'tab', get_param( 'tab' ) );
$Form->hidden( 'tab3', get_param( 'tab3' ) );
$Form->hidden( 'action', 'settings' );

$Form->begin_fieldset( T_('Test SMTP settings').get_manual_link( 'email-test-smtp-settings' ) );

	$url = $admin_url.'?ctrl=email&amp;tab='.get_param( 'tab' ).'&amp;tab3='.get_param( 'tab3' ).'&amp;'.url_crumb('emailsettings').'&amp;action=';
	$Form->info_field( T_('Perform tests'),
				'<a href="'.$url.'test_smtp" class="btn btn-default">'.T_('SMTP server connection').'</a>&nbsp;&nbsp;'.
				'<a href="'.$url.'test_email_smtp" class="btn btn-default">'.T_('Send test email via SMTP').'</a>&nbsp;&nbsp;'.
				( $email_send_allow_php_mail ? '<a href="'.$url.'test_email_php" class="btn btn-default">'.T_('Send test email via PHP').'</a>' : '' ),
				array( 'class' => 'info_full_height' ) );

	if( !empty( $test_mail_output ) )
	{
		echo '<div style="margin-top:25px"></div>';
		// Display scrollable div
		echo '<div style="padding: 6px; margin:5px; border: 1px solid #CCC; overflow:scroll; height: 350px">'.$test_mail_output.'</div>';
	}

$Form->end_fieldset();

$Form->end_form();
?>