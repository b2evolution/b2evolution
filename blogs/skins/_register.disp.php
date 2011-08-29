<?php
/**
 * This file implements the register form
 *
 * This file is not meant to be called directly.
 * 
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author asimo: Evo Factory / Attila Simo
 * 
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_country.class.php', 'Country' );

global $Settings;

global $htsrv_url;

global $notify_from;

global $Blog;

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

$action = param( 'action', 'string', '' );
$login = param( 'login', 'string', '' );
$email = param( 'email', 'string', '' );
$country = param( 'country', 'string', NULL );
$gender = param( 'gender', 'string', false );
$redirect_to = param( 'redirect_to', 'string', '' );

$Form = new Form( $htsrv_url.'register.php', 'login_form', 'post' );

$Form->add_crumb( 'regform' );
if( empty( $action ) )
{
	$action = 'register';
}

$Form->hidden( 'inskin', true );
$Form->hidden( 'blog', $Blog->ID );

$Form->begin_form( 'bComment' );

if( $action == 'register' )
{ // disp register form
	$Form->hidden( 'action', 'register' );

	$Form->begin_field();
	$Form->text_input( 'login', $login, 16, T_('Login'), '', array( 'maxlength' => 20, 'class' => 'input_text' ) );
	$Form->end_field();

	$pwd_note1 = '';
	$pwd_note2 = sprintf( T_('Minimum %d characters, please.'), $Settings->get('user_minpwdlen') );
	$Form->begin_field();
	$Form->password_input( 'pass1', '', 16, T_('Password'), array( 'note'=>$pwd_note1, 'maxlength' => 70, 'class' => 'input_text' ) );
	$Form->password_input( 'pass2', '', 16, '', array( 'note'=>$pwd_note2, 'maxlength' => 70, 'class' => 'input_text' ) );
	$Form->end_field();

	$Form->begin_field();
	$Form->text_input( 'email', $email, 32, T_('Email'), '', array( 'maxlength'=>255, 'class'=>'input_text', 'required'=>true ) );

	$registration_require_country = (bool)$Settings->get('registration_require_country');

	if( $registration_require_country )
	{ // country required
		$CountryCache = & get_CountryCache();
		$Form->select_input_object( 'country', $country, $CountryCache, 'Country', array('allow_none'=>true, 'required'=>true) );
	}

	$registration_require_gender = $Settings->get( 'registration_require_gender' );
	if( $registration_require_gender == 'required' )
	{ // gender required
		$Form->radio_input( 'gender', $gender, array(
					array( 'value' => 'M', 'label' => T_('Male') ),
					array( 'value' => 'F', 'label' => T_('Female') ),
				), T_('Gender'), array( 'required' => true ) );
	}

	if( $Settings->get( 'registration_ask_locale' ) )
	{ // ask user language
		$locale = 'en_US';
		$Form->select( 'locale', $locale, 'locale_options_return', T_('Locale'), T_('Preferred language') );
	}
	$Form->end_field();

	$Form->end_fieldset();

	// Submit button:
	$submit_button = array( array( 'name'=>'register', 'value'=>T_('Register!'), 'class'=>'search' ) );

	$Form->buttons_input($submit_button);

	$Form->info("", "", T_('Your IP address ('.$Hit->IP.') and the current time are being logged.'));

}
elseif( $action == "reg_complete" )
{ // display register complete info ( email validation not required )
	$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );
	$Form->hidden( 'inskin', 1 );

	$Form->begin_fieldset();
	$Form->info( T_('Login'), $login );
	$Form->info( T_('Email'), $email );
	$Form->end_fieldset();

	echo '<p class="center"><a href="'.$Blog->gen_baseurl().'">'.T_('Continue').' &raquo;</a> ';
	echo '</p>';
}
elseif( $action == "reg_validation" )
{ // display "validation email sent" info ( email validation required )
	echo '<p>'.sprintf( T_( 'An email has just been sent to %s . Please check your email and click on the validation
					link you will find on that email. ' ), '<b>'.$email.'</b>' ).'</p>';
	echo '<p>'.sprintf( T_( 'If you have not received the email in the next few minutes, please check your spam folder. 
					The email was sent from %s and has the title %s' ), $notify_from,
					'<b>'.sprintf( T_('Validate your email address for "%s"'), $login ).'</b>' ).'</p>';
	echo '<p>'.T_( 'If you still can\'t find the email or if you would like to use a different email address' ).','.
					'<a href="'.$Blog->gen_baseurl().'">'.T_( 'click here to try again' ).'.</a></p>';
}

$Form->end_form();


/*
 * $Log$
 * Revision 1.3  2011/08/29 09:32:22  efy-james
 * Add ip on login form
 *
 * Revision 1.2  2011/06/14 20:56:57  sam2kb
 * Hide the form if user registration is disabled
 *
 * Revision 1.1  2011/06/14 13:33:56  efy-asimo
 * in-skin register
 *
 */
?>