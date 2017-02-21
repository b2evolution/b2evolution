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

global $repath_test_output, $action;


$Form = new Form( NULL, 'settings_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'emailsettings' );
$Form->hidden( 'ctrl', 'email' );
$Form->hidden( 'tab', get_param( 'tab' ) );
$Form->hidden( 'tab2', get_param( 'tab2' ) );
$Form->hidden( 'tab3', get_param( 'tab3' ) );
$Form->hidden( 'action', 'settings' );

$Form->begin_fieldset( T_( 'Email envelope' ).get_manual_link( 'email-notification-settings' ) );
	// Set notes for notifications sender settings which shows the users custom settings information
	$notification_sender_email_note = '';
	$notification_sender_name_note = '';
	if( $current_User->check_perm( 'users', 'edit' ) )
	{ // Show infomration and action buttons only for users with edit users permission
		$users_url = url_add_param( $admin_url, 'ctrl=users&filter=new', '&' );
		$redirect_to = rawurlencode( regenerate_url( '', '', '', '&' ) );
		$remove_customization_url = url_add_param( $admin_url, 'ctrl=users&action=remove_sender_customization&'.url_crumb( 'users' ), '&' );
		$remove_customization = ' - <a href="%s" class="ActionButton" style="float:none">'.T_('remove customizations').'</a>';

		$notification_sender_email = $Settings->get( 'notification_sender_email' );
		$custom_sender_email_count = count_users_with_custom_setting( 'notification_sender_email', $notification_sender_email );
		if( $custom_sender_email_count > 0 )
		{ // There are users with custom sender email settings
			$sender_email_remove_customization = sprintf( $remove_customization, url_add_param( $remove_customization_url, 'type=sender_email&redirect_to='.$redirect_to, '&' ) );
			$notification_sender_email_note = get_icon( 'warning_yellow' ).' '.sprintf( T_('<a href="%s">%d users</a> have different custom address'), url_add_param( $users_url, 'custom_sender_email=1', '&' ), $custom_sender_email_count ).$sender_email_remove_customization;
		}

		$notification_sender_name = $Settings->get( 'notification_sender_name' );
		$custom_sender_name_count = count_users_with_custom_setting( 'notification_sender_name', $notification_sender_name );
		if( $custom_sender_name_count > 0 )
		{ // There are users with custom sender name settings
			$sender_name_remove_customization = sprintf( $remove_customization, url_add_param( $remove_customization_url, 'type=sender_name&redirect_to='.$redirect_to, '&' ) );
			$notification_sender_name_note = get_icon( 'warning_yellow' ).' '.sprintf( T_('<a href="%s">%d users</a> have different custom name'), url_add_param( $users_url, 'custom_sender_name=1', '&' ), $custom_sender_name_count ).$sender_name_remove_customization;
		}
	}

	// Display settings input fields
	$Form->text_input( 'notification_sender_email', $Settings->get( 'notification_sender_email' ), 50, T_( 'Sender email address' ), $notification_sender_email_note, array( 'maxlength' => 127, 'required' => true ) );
	$Form->text_input( 'notification_sender_name', $Settings->get( 'notification_sender_name' ), 50, T_( 'Sender name' ), $notification_sender_name_note, array( 'maxlength' => 127, 'required' => true ) );
	$Form->text_input( 'notification_return_path', $Settings->get( 'notification_return_path' ), 50, T_( 'Return path' ), '', array( 'maxlength' => 127, 'required' => true ) );
	$Form->text_input( 'notification_short_name', $Settings->get( 'notification_short_name' ), 50, T_( 'Short site name' ), T_('Shared with site settings'), array( 'maxlength' => 127, 'required' => true ) );
	$Form->text_input( 'notification_long_name', $Settings->get( 'notification_long_name' ), 50, T_( 'Long site name' ), T_('Shared with site settings'), array( 'maxlength' => 255 ) );
	$fileselect_params = array( 'file_type' => 'image', 'max_file_num' => 1, 'window_title' => T_('Select site logo'), 'root' => 'shared_0' );
	$Form->fileselect( 'notification_logo_file_ID', $Settings->get( 'notification_logo_file_ID' ), T_('Select site logo'), NULL, $fileselect_params );
$Form->end_fieldset();

if( $current_User->check_perm( 'emails', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', '', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>