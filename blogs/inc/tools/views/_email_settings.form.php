<?php
/**
 * This file implements the UI view for the general settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Halton STEWART grants Francois PLANQUE the right to license
 * Halton STEWART's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * @version $Id$
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

global $baseurl;

global $repath_test_output, $action;


$Form = new Form( NULL, 'settings_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'emailsettings' );
$Form->hidden( 'ctrl', 'email' );
$Form->hidden( 'tab', 'settings' );
$Form->hidden( 'action', 'settings' );

if( $current_User->check_perm( 'emails', 'edit' ) )
{
	$Form->begin_fieldset( T_('Test saved settings') );

		$url = '?ctrl=email&amp;tab=settings&amp;'.url_crumb('emailsettings').'&amp;action=';
		$Form->info_field( T_('Perform tests'),
					'<a href="'.$url.'test_1">['.T_('server connection').']</a>&nbsp;&nbsp;'.
					'<a href="'.$url.'test_2">['.T_('get one returned email').']</a>&nbsp;&nbsp;'.
					'<a href="'.$url.'test_3">['.T_('Copy/Paste an error message').']</a>' );

		if( $action == 'test_3' )
		{	// Display a textarea to fill a sample error message
			$Form->textarea( 'test_error_message', param( 'test_error_message', 'raw', '' ), 15, T_('Test error message'),
				'', 50, 'large' );
			$Form->buttons( array( array( 'submit', 'actionArray[test_3]', T_('Test'), 'SaveButton' ) ) );
		}

		if( !empty( $repath_test_output ) )
		{
			echo '<div style="margin-top:25px"></div>';
			// Display scrollable div
			echo '<div style="padding: 6px; margin:5px; border: 1px solid #CCC; overflow:scroll; height: 350px">'.$repath_test_output.'</div>';
		}

	$Form->end_fieldset();
}

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

	$Form->select_input_array( 'repath_method', $Settings->get('repath_method'), array( 'pop3' => T_('POP3'), 'imap' => T_('IMAP'), ), // TRANS: E-Mail retrieval method
		T_('Retrieval method'), T_('Choose a method to retrieve the emails.') );

	$Form->text_input( 'repath_server_host', $Settings->get('repath_server_host'), 25, T_('Mail Server'), T_('Hostname or IP address of your incoming mail server.'), array( 'maxlength' => 255 ) );

	$Form->text_input( 'repath_server_port', $Settings->get('repath_server_port'), 5, T_('Port Number'), T_('Port number of your incoming mail server (Defaults: POP3: 110, IMAP: 143, SSL/TLS: 993).'), array( 'maxlength' => 6 ) );

	$Form->radio( 'repath_encrypt', $Settings->get('repath_encrypt'), array(
																		array( 'none', T_('None'), ),
																		array( 'ssl', T_('SSL'), ),
																		array( 'tls', T_('TLS'), ),
																	), T_('Encryption method') );

	$Form->checkbox( 'repath_novalidatecert', $Settings->get( 'repath_novalidatecert' ), T_('Do not validate certificate'),
				T_('Do not validate the certificate from the TLS/SSL server. Check this if you are using a self-signed certificate.') );

	$Form->text_input( 'repath_username', $Settings->get( 'repath_username' ), 25,
				T_('Account Name'), T_('User name for authenticating on your mail server. Usually it\'s your email address or a part before the @ sign.'), array( 'maxlength' => 255 ) );

	if( $current_User->check_perm( 'emails', 'edit' ) )
	{
		$Form->password_input( 'repath_password', $Settings->get( 'repath_password' ), 25,
					T_('Password'), array( 'maxlength' => 255, 'note' => T_('Password for authenticating on your mail server.') ) );
	}

	$Form->checkbox( 'repath_delete_emails', $Settings->get( 'repath_delete_emails' ), T_('Delete processed emails'),
				T_('Check this if you want processed messages to be deleted from server after successful processing.') );

	$Form->textarea( 'repath_subject', $Settings->get( 'repath_subject' ), 5, T_('Strings to match in titles to identify return path emails'),
				T_('Any email that has any of these strings in the title will be detected by b2evolution as the returned emails'), 50, 'large' );

	$Form->textarea( 'repath_body_terminator', $Settings->get('repath_body_terminator'), 5,
				T_('Body Terminator'), T_('Starting from any of these strings, everything will be ignored, including these strings.'), 50, 'large' );

	$Form->textarea( 'repath_errtype', $Settings->get( 'repath_errtype' ), 15, T_('Error message decoding configuration'),
				T_('The first letter means one of the following:<br />Spam suspicion<br />Permament error<br />Temporary error<br />Configuration error<br />Unknown error (default)<br />The next string after space is an error text, it is case-insensitive string.'), 50, 'large' );

$Form->end_fieldset();

$Form->begin_fieldset( T_( 'Email notifications' ).get_manual_link( 'email-notification-settings' ) );
	$Form->text_input( 'notification_sender_email', $Settings->get( 'notification_sender_email' ), 50, T_( 'Sender email address' ), '', array( 'maxlength' => 127, 'required' => true ) );
	$Form->text_input( 'notification_sender_name', $Settings->get( 'notification_sender_name' ), 50, T_( 'Sender name' ), '', array( 'maxlength' => 127, 'required' => true ) );
	$Form->text_input( 'notification_return_path', $Settings->get( 'notification_return_path' ), 50, T_( 'Return path' ), '', array( 'maxlength' => 127, 'required' => true ) );
	$Form->text_input( 'notification_short_name', $Settings->get( 'notification_short_name' ), 50, T_( 'Short site name' ), '', array( 'maxlength' => 127, 'required' => true ) );
	$Form->text_input( 'notification_long_name', $Settings->get( 'notification_long_name' ), 50, T_( 'Long site name' ), '', array( 'maxlength' => 255 ) );
	$Form->text_input( 'notification_logo', $Settings->get( 'notification_logo' ), 50, T_( 'Site logo (URL)' ), '', array( 'maxlength' => 5000 ) );
$Form->end_fieldset();

if( $current_User->check_perm( 'emails', 'edit' ) )
{
	$Form->end_form( array(
		array( 'submit', '', T_('Update'), 'SaveButton' ),
		array( 'reset', '', T_('Reset'), 'ResetButton' ),
		) );
}


/*
 * $Log$
 * Revision 1.3  2013/11/06 09:08:59  efy-asimo
 * Update to version 5.0.2-alpha-5
 *
 */
?>