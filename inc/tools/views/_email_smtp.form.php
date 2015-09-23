<?php
/**
 * This file implements the UI view for the general settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
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

global $smtp_test_output, $action;


$Form = new Form( NULL, 'settings_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'emailsettings' );
$Form->hidden( 'ctrl', 'email' );
$Form->hidden( 'tab', 'settings' );
$Form->hidden( 'tab3', get_param( 'tab3' ) );
$Form->hidden( 'action', 'settings' );

if( $current_User->check_perm( 'emails', 'edit' ) )
{
	$Form->begin_fieldset( T_('Test saved settings').get_manual_link( 'smtp-gateway-settings' ) );

		$url = '?ctrl=email&amp;tab=settings&amp;tab3=smtp&amp;'.url_crumb('emailsettings').'&amp;action=';
		$Form->info_field( T_('Perform tests'),
					'<a href="'.$url.'test_smtp">['.T_('server connection').']</a>&nbsp;&nbsp;' );

		if( !empty( $smtp_test_output ) )
		{
			echo '<div style="margin-top:25px"></div>';
			// Display scrollable div
			echo '<div style="padding: 6px; margin:5px; border: 1px solid #CCC; overflow:scroll; height: 350px">'.$smtp_test_output.'</div>';
		}

	$Form->end_fieldset();
}

$Form->begin_fieldset( T_('SMTP Server connection settings').get_manual_link('smtp-gateway-settings') );

	$Form->checkbox_input( 'smtp_enabled', $Settings->get('smtp_enabled'), T_('Enabled'),
		array( 'note' => sprintf(T_('Note: This feature needs PHP version 5.2 or higher ( Currently installed: %s )' ), phpversion() ) ) );

	$Form->text_input( 'smtp_server_host', $Settings->get('smtp_server_host'), 25, T_('SMTP Host'), T_('Hostname or IP address of your SMTP server.'), array( 'maxlength' => 255 ) );

	$Form->text_input( 'smtp_server_port', $Settings->get('smtp_server_port'), 5, T_('Port Number'), T_('Port number of your SMTP server (Defaults: SSL: 443, TLS: 587).'), array( 'maxlength' => 6 ) );

	$Form->radio( 'smtp_server_security', $Settings->get('smtp_server_security'), array(
																		array( 'none', T_('None'), ),
																		array( 'ssl', T_('SSL'), ),
																		array( 'tls', T_('TLS'), ),
																	), T_('Encryption Method') );

	$Form->text_input( 'smtp_server_username', $Settings->get( 'smtp_server_username' ), 25,
				T_('SMTP Username'), T_('User name for authenticating on your SMTP server.'), array( 'maxlength' => 255 ) );

	if( $current_User->check_perm( 'emails', 'edit' ) )
	{
		$Form->password_input( 'smtp_server_password', $Settings->get( 'smtp_server_password' ), 25,
					T_('SMTP Password'), array( 'maxlength' => 255, 'note' => T_('Password for authenticating on your SMTP server.') ) );
	}

$Form->end_fieldset();



if( $current_User->check_perm( 'emails', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', '', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>