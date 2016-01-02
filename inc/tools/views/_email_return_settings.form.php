<?php
/**
 * This file implements the form to edit settings for returned emails.
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


$Form = new Form( NULL, 'settings_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'emailsettings' );
$Form->hidden( 'ctrl', 'email' );
$Form->hidden( 'tab', get_param( 'tab' ) );
$Form->hidden( 'tab3', get_param( 'tab3' ) );
$Form->hidden( 'action', 'settings' );

$Form->begin_fieldset( T_('Settings to decode the returned emails').get_manual_link('return-path-configuration') );

	if( extension_loaded( 'imap' ) )
	{
		$imap_extenssion_status = T_('(currently loaded)');
	}
	else
	{
		$imap_extenssion_status = '<b class="red">'.T_('(currently NOT loaded)').'</b>';
	}

	$Form->checkbox_input( 'repath_enabled', $Settings->get('repath_enabled'), T_('Enabled'),
		array( 'note' => sprintf(T_('Note: This feature needs the php_imap extension %s.' ), $imap_extenssion_status ) ) );

	$Form->text_input( 'repath_server_host', $Settings->get('repath_server_host'), 25, T_('Mail Server'), T_('Hostname or IP address of your incoming mail server.'), array( 'maxlength' => 255 ) );

	$Form->radio( 'repath_method', $Settings->get('repath_method'), array(
			array( 'pop3', T_('POP3'), ),// TRANS: E-Mail retrieval method
			array( 'imap', T_('IMAP'), ),// TRANS: E-Mail retrieval method
		), T_('Retrieval method') );

	$Form->radio( 'repath_encrypt', $Settings->get('repath_encrypt'), array(
																		array( 'none', T_('None'), ),
																		array( 'ssl', T_('SSL'), ),
																		array( 'tls', T_('TLS'), ),
																	), T_('Encryption method') );

	$repath_novalidatecert_params = array( 'lines' => true );
	if( $Settings->get('repath_encrypt') == 'none' )
	{
		$repath_novalidatecert_params['disabled'] = 'disabled';
	}
	$Form->radio_input( 'repath_novalidatecert', $Settings->get( 'repath_novalidatecert' ), array(
			array( 'value' => 1, 'label' => T_('Do not validate the certificate from the TLS/SSL server. Check this if you are using a self-signed certificate.') ),
			array( 'value' => 0, 'label' => T_('Validate that the certificate from the TLS/SSL server can be trusted. Use this if you have a correctly signed certificate.') )
		), T_('Certificate validation'), $repath_novalidatecert_params );

	$Form->text_input( 'repath_server_port', $Settings->get('repath_server_port'), 5, T_('Port Number'), T_('Port number of your incoming mail server (Defaults: IMAP4/SSL: 993, IMAP4 with or without TLS: 143, POP3/SSL: 995, POP3 with or without TLS: 110).'), array( 'maxlength' => 6 ) );

	$Form->text_input( 'repath_username', $Settings->get( 'repath_username' ), 25,
				T_('Account Name'), T_('User name for authenticating on your mail server. Usually it\'s your email address or a part before the @ sign.'), array( 'maxlength' => 255, 'autocomplete' => 'off' ) );

	if( $current_User->check_perm( 'emails', 'edit' ) )
	{
		// Disply this fake hidden password field before real because Chrome ignores attribute autocomplete="off"
		echo '<input type="password" name="password" value="" style="display:none" />';
		// Real password field:
		$Form->password_input( 'repath_password', $Settings->get( 'repath_password' ), 25,
					T_('Password'), array( 'maxlength' => 255, 'note' => T_('Password for authenticating on your mail server.'), 'autocomplete' => 'off' ) );
	}

	$Form->text_input( 'repath_imap_folder', $Settings->get( 'repath_imap_folder' ), 25,
				T_('IMAP Folder'), T_('Which folder holds your returned email.'), array( 'maxlength' => 255 ) );

	$Form->checkbox( 'repath_ignore_read', $Settings->get( 'repath_ignore_read' ), T_('Ignore emails that have already been read'),
				T_('Check this in order not to re-process emails that already have the "seen" flag on the server.') );

	$Form->checkbox( 'repath_delete_emails', $Settings->get( 'repath_delete_emails' ), T_('Delete processed emails'),
				T_('Check this if you want processed messages to be deleted from server after successful processing.') );

	$Form->textarea( 'repath_subject', $Settings->get( 'repath_subject' ), 5, T_('Strings to match in titles to identify return path emails'),
				T_('Any email that has any of these strings in the title will be detected by b2evolution as the returned emails'), 50 );

	$Form->textarea( 'repath_body_terminator', $Settings->get('repath_body_terminator'), 5,
				T_('Body Terminator'), T_('Starting from any of these strings, everything will be ignored, including these strings.'), 50 );

	$Form->textarea( 'repath_errtype', $Settings->get( 'repath_errtype' ), 15, T_('Error message decoding configuration'),
				T_('The first letter means one of the following:<br />S: Spam suspicion<br />P: Permament error<br />T: Temporary error<br />C: Configuration error<br />U: Unknown error (default)<br />The string after the space is a case-insensitive error text.'), 50 );

$Form->end_fieldset();

if( $current_User->check_perm( 'emails', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', '', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>
<script type="text/javascript">
jQuery( document ).ready( function()
{
	jQuery( 'input[name="repath_method"], input[name="repath_encrypt"]' ).click( function()
	{	// Change default port depending on selected retrieval and encryption methods:
		var method = jQuery( 'input[name="repath_method"]:checked' ).val();
		var encrypt = jQuery( 'input[name="repath_encrypt"]:checked' ).val();

		if( method == 'pop3' )
		{
			jQuery( 'input[name="repath_server_port"]' ).val( encrypt == 'ssl' ? '995' : '110' );
		}
		else if( method == 'imap' )
		{
			jQuery( 'input[name="repath_server_port"]' ).val( encrypt == 'ssl' ? '993' : '143' );
		}
	} );

	jQuery( 'input[name="repath_encrypt"]' ).click( function()
	{	// Enable/Disable "Certificate validation" options depending on encryption method
		if( jQuery( this ).val() == 'none' )
		{
			jQuery( 'input[name="repath_novalidatecert"]' ).attr( 'disabled', 'disabled' );
		}
		else
		{
			jQuery( 'input[name="repath_novalidatecert"]' ).removeAttr( 'disabled' );
		}
	} )
} );
</script>