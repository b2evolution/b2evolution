<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of GeneralSettings class
 */
global $Settings;
/**
 * @var instance of User class
 */
global $edited_User;
/**
 * @var current action
 */
global $action;
/**
 * @var user permission, if user is only allowed to edit his profile
 */
global $user_profile_only;


// Begin payload block:
$this->disp_payload_begin();

$Form = & new Form( NULL, 'user_checkchanges' );

if( !$user_profile_only )
{
	$Form->global_icon( T_('Compose message'), 'comments', '?ctrl=threads&action=new&user_login='.$edited_User->login );
	$Form->global_icon( ( $action != 'view' ? T_('Cancel editing!') : T_('Close user profile!') ), 'close', regenerate_url( 'user_ID,action' ) );
}

$Form->begin_form( 'fform', sprintf( T_('Change %s password'), $edited_User->dget('fullname').' ['.$edited_User->dget('login').']' ) );

$Form->hidden_ctrl();
$Form->hidden( 'user_tab', 'password' );
$Form->hidden( 'password_form', '1' );

$Form->hidden( 'user_ID', $edited_User->ID );
$Form->hidden( 'edited_user_login', $edited_User->login );

	/***************  Password  **************/

if( $action != 'view' )
{ // We can edit the values:

	$Form->begin_fieldset( T_('Password') );
		$Form->password_input( 'edited_user_pass1', '', 20, T_('New password'), array( 'note' => ( !empty($edited_User->ID) ? T_('Leave empty if you don\'t want to change the password.') : '' ), 'maxlength' => 50, 'required' => ($edited_User->ID == 0), 'autocomplete'=>'off' ) );
		$Form->password_input( 'edited_user_pass2', '', 20, T_('Confirm new password'), array( 'note'=>sprintf( T_('Minimum length: %d characters.'), $Settings->get('user_minpwdlen') ), 'maxlength' => 50, 'required' => ($edited_User->ID == 0), 'autocomplete'=>'off' ) );

	$Form->end_fieldset();
}

	/***************  Buttons  **************/

if( $action != 'view' )
{ // Edit buttons
	$Form->buttons( array(
		array( '', 'actionArray[update]', T_('Change password!'), 'SaveButton' ),
		array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}


$Form->end_form();

// End payload block:
$this->disp_payload_end();


/*
 * $Log$
 * Revision 1.2  2009/10/26 12:59:37  efy-maxim
 * users management
 *
 * Revision 1.1  2009/10/25 15:22:48  efy-maxim
 * user - identity, password, preferences tabs
 *
 */
?>