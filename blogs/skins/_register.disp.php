<?php
/**
 * This file implements the register form
 *
 * This file is not meant to be called directly.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author asimo: Evo Factory / Attila Simo
 *
 * @version $Id: _register.disp.php 6677 2014-05-13 12:44:29Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_country.class.php', 'Country' );

global $Settings, $Plugins;

global $Blog, $rsc_path, $rsc_url, $dummy_fields;

if( is_logged_in() )
{ // if a user is already logged in don't allow to register
	echo '<p>'.T_('You are already logged in').'</p>';
	return;
}

if( ! $Settings->get('newusers_canregister') )
{
	echo '<p>'.T_('User registration is currently not allowed.').'</p>';
	return;
}

// Save trigger page
$session_registration_trigger_url = $Session->get( 'registration_trigger_url' );
if( empty( $session_registration_trigger_url/*$Session->get( 'registration_trigger_url' )*/ ) && isset( $_SERVER['HTTP_REFERER'] ) )
{	// Trigger page still is not defined
	$Session->set( 'registration_trigger_url', $_SERVER['HTTP_REFERER'] );
}

$login = param( $dummy_fields[ 'login' ], 'string', '' );
$email = evo_strtolower( param( $dummy_fields[ 'email' ], 'string', '' ) );
$firstname = param( 'firstname', 'string', '' );
$gender = param( 'gender', 'string', false );
$source = param( 'source', 'string', 'register form' );
$redirect_to = param( 'redirect_to', 'url', '' );

if( $register_user = $Session->get('core.register_user') )
{	// Get an user data from predefined session (after adding of a comment)
	$login = preg_replace( '/[^a-z0-9 ]/i', '', $register_user['name'] );
	$login = str_replace( ' ', '_', $login );
	$login = substr( $login, 0, 20 );
	$email = $register_user['email'];

	$Session->delete( 'core.register_user' );
}

// set secure htsrv url with the same domain as the request has
$secure_htsrv_url = get_secure_htsrv_url();

$Form = new Form( $secure_htsrv_url.'register.php', 'register_form', 'post' );

$Form->add_crumb( 'regform' );
$Form->hidden( 'inskin', true );
$Form->hidden( 'blog', $Blog->ID );

// disp register form
$Form->begin_form( 'bComment' );

$Plugins->trigger_event( 'DisplayRegisterFormBefore', array( 'Form' => & $Form, 'inskin' => true ) );

$Form->hidden( 'action', 'register' );
$Form->hidden( 'source', $source );
$Form->hidden( 'redirect_to', $redirect_to );

$Form->text_input( $dummy_fields[ 'login' ], $login, 22, T_('Login'), T_('Choose an username.'), array( 'maxlength' => 20, 'class' => 'input_text', 'required' => true, 'input_suffix' => ' <span id="login_status"></span>' ) );

$Form->password_input( $dummy_fields[ 'pass1' ], '', 18, T_('Password'), array( 'note'=>T_('Choose a password.'), 'maxlength' => 70, 'class' => 'input_text', 'required'=>true, 'autocomplete' => 'off' ) );
$Form->password_input( $dummy_fields[ 'pass2' ], '', 18, '', array( 'note'=>T_('Please type your password again.'), 'maxlength' => 70, 'class' => 'input_text', 'required'=>true, 'autocomplete' => 'off' ) );

$Form->text_input( $dummy_fields[ 'email' ], $email, 50, T_('Email'), '<br />'.T_('We respect your privacy. Your email will remain strictly confidential.'),
				array( 'maxlength'=>255, 'class'=>'input_text wide_input', 'required'=>true ) );

$registration_require_country = (bool)$Settings->get('registration_require_country');

if( $registration_require_country )
{ // country required
	$CountryCache = & get_CountryCache();
	$Form->select_country( 'country', param( 'country', 'integer', 0 ), $CountryCache, T_('Country'), array('allow_none'=>true, 'required'=>true) );
}

$registration_require_firstname = (bool)$Settings->get('registration_require_firstname');

if( $registration_require_firstname )
{ // firstname required
	$Form->text_input( 'firstname', $firstname, 18, T_('First name'), T_('Your real first name.'), array( 'maxlength' => 50, 'class' => 'input_text', 'required' => true ) );
}

$registration_require_gender = $Settings->get( 'registration_require_gender' );
if( $registration_require_gender != 'hidden' )
{ // Display a gender field if it is not hidden
	$Form->radio_input( 'gender', $gender, array(
				array( 'value' => 'M', 'label' => T_('A man') ),
				array( 'value' => 'F', 'label' => T_('A woman') ),
			), T_('I am'), array( 'required' => $registration_require_gender == 'required' ) );
}

if( $Settings->get( 'registration_ask_locale' ) )
{ // ask user language
	$locale = 'en_US';
	$Form->select( 'locale', $locale, 'locale_options_return', T_('Locale'), T_('Preferred language') );
}

$Plugins->trigger_event( 'DisplayRegisterFormFieldset', array( 'Form' => & $Form, 'inskin' => true ) );

// Submit button:
$submit_button = array( array( 'name'=>'register', 'value'=>T_('Register my account now!'), 'class'=>'search btn-primary btn-lg' ) );

$Form->buttons_input($submit_button);

echo '<div class="login_actions" style="margin: 1em 0 1ex">';
echo '<strong><a href="'.get_login_url( $source, $redirect_to).'">&laquo; '.T_('Already have an account... ?').'</a></strong>';
echo '</div>';

$Form->end_form();

echo '<div class="form_footer_notes">'.sprintf( T_('Your IP address: %s'), $Hit->IP ).'</div>';

// Display javascript password strength indicator bar
display_password_indicator();

// Display javascript login validator
display_login_validator();

?>