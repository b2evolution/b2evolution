<?php
/**
 * This file implements the UI view for the general settings.
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

global $test_mail_output, $action;


$Form = new Form( NULL, 'settings_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'emailsettings' );
$Form->hidden( 'ctrl', 'email' );
$Form->hidden( 'tab', get_param( 'tab' ) );
$Form->hidden( 'tab2', get_param( 'tab2' ) );
$Form->hidden( 'tab3', get_param( 'tab3' ) );
$Form->hidden( 'action', 'settings' );

if( $current_User->check_perm( 'emails', 'edit' ) )
{
	$Form->begin_fieldset( T_('Test saved settings').get_manual_link( 'smtp-gateway-settings' ) );

		$url = '?ctrl=email&amp;tab='.get_param( 'tab' ).'&amp;tab2='.get_param( 'tab2' ).'&amp;tab3='.get_param( 'tab3' ).'&amp;'.url_crumb('emailsettings').'&amp;action=';
		$Form->info_field( T_('Perform tests'),
					'<a href="'.$url.'test_smtp" class="btn btn-default">'.T_('SMTP server connection').'</a>&nbsp;&nbsp;'.
					'<a href="'.$url.'test_email_smtp" class="btn btn-default">'.T_('Send test email via SMTP').'</a>&nbsp;&nbsp;'.
					'<a href="'.$url.'test_email_php" class="btn btn-default">'.T_('Send test email via PHP').'</a>',
					array( 'class' => 'info_full_height' ) );

		if( !empty( $test_mail_output ) )
		{
			echo '<div style="margin-top:25px"></div>';
			// Display scrollable div
			echo '<div style="padding: 6px; margin:5px; border: 1px solid #CCC; overflow:scroll; height: 350px">'.$test_mail_output.'</div>';
		}

	$Form->end_fieldset();
}


$Form->begin_fieldset( T_( 'Email service settings' ).get_manual_link( 'email-service-settings' ) );

$Form->radio( 'email_service', $Settings->get( 'email_service' ), array(
			array( 'mail', T_('Regular PHP <code>mail()</code> function'), ),
			array( 'smtp', T_('External SMTP Server defined below'), ),
		), T_('Primary email service'), true );
$Form->checkbox( 'force_email_sending', $Settings->get( 'force_email_sending' ), T_('Force email sending'), T_('If the primary email service is not available, the secondary option will be used.') );

$Form->end_fieldset();


$Form->begin_fieldset( T_( 'PHP <code>mail()</code> function settings' ).get_manual_link( 'php-mail-function-settings' ) );

$Form->radio( 'sendmail_params', $Settings->get( 'sendmail_params' ), array(
			array( 'return', '<code>-r $return-address$</code>', ),
			array( 'from', '<code>-f $return-address$</code>', ),
			array( 'custom', T_('Custom').':', '',
				'<input type="text" class="form_text_input form-control" name="sendmail_params_custom"
					size="150" value="'.$Settings->dget( 'sendmail_params_custom', 'formvalue' ).'" />
					<span class="notes">'.sprintf( T_('Allowed placeholders: %s'), '<code>$from-address$</code>, <code>$return-address$</code>' ).'</span>' ),
		), T_('Sendmail additional params'), true );

$Form->end_fieldset();


$Form->begin_fieldset( T_('SMTP Server connection settings').get_manual_link('smtp-gateway-settings') );

	$Form->checkbox_input( 'smtp_enabled', $Settings->get('smtp_enabled'), T_('Enabled'),
		array( 'note' => sprintf(T_('Note: This feature needs PHP version 5.2 or higher ( Currently installed: %s )' ), phpversion() ) ) );

	$Form->text_input( 'smtp_server_host', $Settings->get('smtp_server_host'), 25, T_('SMTP Server'), T_('Hostname or IP address of your SMTP server.'), array( 'maxlength' => 255 ) );

	$smtp_server_security = $Settings->get( 'smtp_server_security' );
	$Form->radio( 'smtp_server_security', $smtp_server_security, array(
				array( 'none', T_('None'), ),
				array( 'ssl', T_('SSL'), ),
				array( 'tls', T_('TLS'), ),
			), T_('Encryption Method') );

	$smtp_server_novalidatecert_params = array( 'lines' => true );
	if( empty( $smtp_server_security ) || $smtp_server_security == 'none' )
	{
		$smtp_server_novalidatecert_params['disabled'] = 'disabled';
	}
	$Form->radio_input( 'smtp_server_novalidatecert', $Settings->get( 'smtp_server_novalidatecert' ), array(
			array( 'value' => 1, 'label' => T_('Do not validate the certificate from the TLS/SSL server. Check this if you are using a self-signed certificate.') ),
			array( 'value' => 0, 'label' => T_('Validate that the certificate from the TLS/SSL server can be trusted. Use this if you have a correctly signed certificate.') )
		), T_('Certificate validation'), $smtp_server_novalidatecert_params );

	$Form->text_input( 'smtp_server_port', $Settings->get('smtp_server_port'), 5, T_('Port Number'), T_('Port number of your SMTP server (Defaults: SSL: 443, TLS: 587).'), array( 'maxlength' => 6 ) );

	$Form->text_input( 'smtp_server_username', $Settings->get( 'smtp_server_username' ), 25,
				T_('SMTP Username'), T_('User name for authenticating on your SMTP server.'), array( 'maxlength' => 255, 'autocomplete' => 'off' ) );

	if( $current_User->check_perm( 'emails', 'edit' ) )
	{
		// Disply this fake hidden password field before real because Chrome ignores attribute autocomplete="off"
		echo '<input type="password" name="password" value="" style="display:none" />';
		// Real password field:
		$Form->password_input( 'smtp_server_password', $Settings->get( 'smtp_server_password' ), 25,
					T_('SMTP Password'), array( 'maxlength' => 255, 'note' => T_('Password for authenticating on your SMTP server.'), 'autocomplete' => 'off' ) );
	}

$Form->end_fieldset();



if( $current_User->check_perm( 'emails', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', '', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>
<script type="text/javascript">
jQuery( document ).ready( function()
{
	jQuery( 'input[name="smtp_server_security"]' ).click( function()
	{	// Enable/Disable "Certificate validation" options depending on encryption method
		if( jQuery( this ).val() == 'none' )
		{
			jQuery( 'input[name="smtp_server_novalidatecert"]' ).attr( 'disabled', 'disabled' );
		}
		else
		{
			jQuery( 'input[name="smtp_server_novalidatecert"]' ).removeAttr( 'disabled' );
		}
	} )
} );
</script>