<?php
/**
 * This file implements the UI view for the user register finish form.
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

load_class( 'regional/model/_country.class.php', 'Country' );
load_funcs( 'regional/model/_regional.funcs.php' );

/**
 * @var instance of GeneralSettings class
 */
global $Settings;
/**
 * @var instance of User class
 */
global $edited_User;
/**
 * @var instance of User class
 */
global $current_User;
/**
 * @var the action destination of the form (NULL for pagenow)
 */
global $form_action;

$edited_user_perms = array( 'edited-user', 'edited-user-required' );

// Default params:
$default_params = array(
		'skin_form_params' => array(),
	);

if( isset( $params ) )
{	// Merge with default params
	$params = array_merge( $default_params, $params );
}
else
{	// Use a default params
	$params = $default_params;
}

$Form = new Form( $form_action, 'user_checkchanges' );

$Form->switch_template_parts( $params['skin_form_params'] );

$Form->begin_form( 'bComment' );

$Form->add_crumb( 'user' );
$Form->hidden_ctrl();
$Form->hidden( 'user_tab', 'register_finish' );
$Form->hidden( 'redirect_to', param( 'redirect_to', 'url', NULL ) );
$Form->hidden( 'user_ID', $edited_User->ID );
if( isset( $Blog ) )
{
	$Form->hidden( 'blog', $Blog->ID );
}

$Form->begin_fieldset( T_('User Profile') );

// Login
$Form->text_input( 'edited_user_login', $edited_User->login, 22, /* TRANS: noun */ T_('Login'), '',
	array(
			'placeholder'  => T_('Choose a username'),
			'maxlength'    => 20,
			'class'        => 'input_text',
			'required'     => true,
			'input_suffix' => ' <span id="login_status"></span>',
			'style'        => 'width:'.( $params['register_field_width'] - 2 ).'px',
		)
	);

// Passwords
// current password is not required:
//   - password change requested by email
//   - password has not been set yet(email capture/quick registration)
if( ( empty( $reqID ) || $reqID != $Session->get( 'core.changepwd.request_id' ) ) &&
		( $edited_User->get( 'pass_driver' ) != 'nopass' && ( ! isset( $edited_User->previous_pass_driver ) || $edited_User->previous_pass_driver != 'nopass' ) ) )
{
	if( ! $current_User->check_perm( 'users', 'edit' ) || $edited_User->ID == $current_User->ID )
	{	// Current user has no full access or editing his own password
		$Form->password_input( 'current_user_pass', '', 20, T_('Current password'), array( 'maxlength' => 50, 'required' => ($edited_User->ID == 0), 'autocomplete'=>'off', 'style' => 'width:'.$params['register_field_width'].'px' ) );
	}
	else
	{	// Ask password of current admin:
		$Form->password_input( 'current_user_pass', '', 20, T_('Enter your current password'), array( 'maxlength' => 50, 'required' => ($edited_User->ID == 0), 'autocomplete'=>'off', 'style' => 'width:163px', 'note' => sprintf( T_('We ask for <b>your</b> (%s) <i>current</i> password as an additional security measure.'), $current_User->get( 'login' ) ) ) );
	}
}
$Form->password_input( 'edited_user_pass1', '', 18, T_('Password'),
	array(
			'placeholder'  => T_('Choose a password'),
			'maxlength'    => 70,
			'class'        => 'input_text',
			'required'     => true,
			'style'        => 'width:'.$params['register_field_width'].'px',
			'autocomplete' => 'off'
		)
	);
$Form->password_input( 'edited_user_pass2', '', 18, '',
	array(
			'note'         => '<div id="pass2_status" class="red"></div>',
			'placeholder'  => T_('Please type your password again'),
			'maxlength'    => 70,
			'class'        => 'input_text',
			'required'     => true,
			'style'        => 'width:'.$params['register_field_width'].'px',
			'autocomplete' => 'off'
		)
	);

// Email
$Form->text_input( 'edited_user_email', $edited_User->email, 50, T_('Email'), '<br />'.T_('We respect your privacy. Your email will remain strictly confidential.'),
	array(
			'placeholder' => T_('Email address'),
			'maxlength'   => 255,
			'class'       => 'input_text wide_input',
			'required'    => true,
		)
	);

if( $Settings->get( 'registration_require_country' ) )
{	// Country is required:
	$CountryCache = & get_CountryCache();
	$Form->select_country( 'country', $edited_User->ctry_ID, $CountryCache, T_('Country'), array(
			'allow_none' => true,
			'required'   => true,
		) );
}

if( $Settings->get( 'registration_require_firstname' ) )
{	// Firstname is visible:
	$Form->text_input( 'firstname', $edited_User->firstname, 18, T_('First name'), T_('Your real first name.'), array( 'maxlength' => 50, 'class' => 'input_text', 'required' => true ) );
}

$registration_require_gender = $Settings->get( 'registration_require_gender' );
if( $registration_require_gender != 'hidden' )
{	// Display a gender field if it is not hidden:
	$Form->radio_input( 'gender', $edited_User->gender, array(
				array( 'value' => 'M', 'label' => T_('A man') ),
				array( 'value' => 'F', 'label' => T_('A woman') ),
				array( 'value' => 'O', 'label' => T_('Other') ),
			), T_('I am'), array( 'required' => $registration_require_gender == 'required' ) );
}

if( $Settings->get( 'registration_ask_locale' ) )
{	// Ask user language:
	$Form->select( 'locale', $edited_User->get( 'locale' ), 'locale_options_return', T_('Locale'), T_('Preferred language') );
}

$Form->end_form( array(
		array( '', 'actionArray[update]', T_('Finish Registration!'), 'SaveButton' ),
	) );

// Display javascript password strength indicator bar
display_password_indicator( array(
			'pass1-id'    => 'edited_user_pass1',
			'pass2-id'    => 'edited_user_pass2',
			'login-id'    => 'edited_user_login',
			'field-width' => $params['register_field_width'],
	) );

// Display javascript code to edit password:
display_password_js_edit();
?>