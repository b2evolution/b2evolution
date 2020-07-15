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


/**
 * @var GeneralSettings
 */
global $Settings;

global $action, $email_send_allow_php_mail;


$Form = new Form( NULL, 'settings_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'emailsettings' );
$Form->hidden( 'ctrl', 'email' );
$Form->hidden( 'tab', get_param( 'tab' ) );
$Form->hidden( 'tab3', get_param( 'tab3' ) );
$Form->hidden( 'action', 'settings' );


if( $email_send_allow_php_mail )
{	// Don't display the settings when only php mail service is disabled by config:
	$Form->begin_fieldset( TB_( 'Email service settings' ).get_manual_link( 'email-service-settings' ) );

	$Form->radio( 'email_service', $Settings->get( 'email_service' ), array(
				array( 'mail', TB_('Regular PHP <code>mail()</code> function'), TB_('Default but not recommended') ),
				array( 'smtp', TB_('External SMTP Server defined below'), TB_('Highly recommended if you send email campaigns') ),
			), TB_('Primary email service'), true );
	$Form->checkbox( 'force_email_sending', $Settings->get( 'force_email_sending' ), TB_('Fallback'), TB_('If the primary email service is not available, the secondary option will be used.') );

	$Form->end_fieldset();
}


$Form->begin_fieldset( TB_('SMTP Server connection settings').get_manual_link('smtp-server-connection-settings') );

	$Form->checkbox_input( 'smtp_enabled', $Settings->get('smtp_enabled'), TB_('Enabled') );

	$Form->text_input( 'smtp_server_host', $Settings->get('smtp_server_host'), 25, TB_('SMTP Server'), TB_('Hostname or IP address of your SMTP server.'), array( 'maxlength' => 255 ) );

	$smtp_server_security = $Settings->get( 'smtp_server_security' );
	$Form->radio( 'smtp_server_security', $smtp_server_security, array(
				array( 'none', TB_('None'), ),
				array( 'ssl', TB_('SSL'), ),
				array( 'tls', TB_('TLS'), ),
			), TB_('Encryption Method') );

	$smtp_server_novalidatecert_params = array( 'lines' => true );
	if( empty( $smtp_server_security ) || $smtp_server_security == 'none' )
	{
		$smtp_server_novalidatecert_params['disabled'] = 'disabled';
	}
	$Form->radio_input( 'smtp_server_novalidatecert', $Settings->get( 'smtp_server_novalidatecert' ), array(
			array( 'value' => 1, 'label' => TB_('Do not validate the certificate from the TLS/SSL server. Check this if you are using a self-signed certificate.') ),
			array( 'value' => 0, 'label' => TB_('Validate that the certificate from the TLS/SSL server can be trusted. Use this if you have a correctly signed certificate.') )
		), TB_('Certificate validation'), $smtp_server_novalidatecert_params );

	$Form->text_input( 'smtp_server_port', $Settings->get('smtp_server_port'), 5, TB_('Port Number'), TB_('Port number of your SMTP server (Defaults: SSL: 443, TLS: 587).'), array( 'maxlength' => 6 ) );

	$Form->text_input( 'smtp_server_username', $Settings->get( 'smtp_server_username' ), 25,
				TB_('SMTP Username'), TB_('User name for authenticating on your SMTP server.'), array( 'maxlength' => 255, 'autocomplete' => 'off' ) );

	if( check_user_perm( 'emails', 'edit' ) )
	{
		// Disply this fake hidden password field before real because Chrome ignores attribute autocomplete="off"
		echo '<input type="password" name="password" value="" style="display:none" />';
		// Real password field:
		$Form->password_input( 'smtp_server_password', $Settings->get( 'smtp_server_password' ), 25,
					TB_('SMTP Password'), array( 'maxlength' => 255, 'note' => TB_('Password for authenticating on your SMTP server.'), 'autocomplete' => 'off' ) );
	}

$Form->end_fieldset();

if( $email_send_allow_php_mail )
{	// Don't display the settings when only php mail service is disabled by config:
	$Form->begin_fieldset( TB_( 'PHP <code>mail()</code> function settings' ).get_manual_link( 'php-mail-function-settings' ) );

	$Form->radio( 'sendmail_params', $Settings->get( 'sendmail_params' ), array(
				array( 'return', '<code>-r $return-address$</code>', ),
				array( 'from', '<code>-f $return-address$</code>', ),
				array( 'custom', TB_('Custom').':', '',
					'<input type="text" class="form_text_input form-control" name="sendmail_params_custom"
						size="150" value="'.$Settings->dget( 'sendmail_params_custom', 'formvalue' ).'" />
						<span class="notes">'.sprintf( TB_('Allowed placeholders: %s'), '<code>$from-address$</code>, <code>$return-address$</code>' ).'</span>' ),
			), TB_('Sendmail additional params'), true );

	$Form->end_fieldset();
}


if( check_user_perm( 'emails', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', '', TB_('Save Changes!'), 'SaveButton' ) ) );
}

?>
<script>
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