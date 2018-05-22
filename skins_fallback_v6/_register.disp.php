<?php
/**
 * This file implements the register form
 *
 * This file is not meant to be called directly.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_country.class.php', 'Country' );

global $Settings, $Plugins;

global $Collection, $Blog, $rsc_path, $rsc_url, $dummy_fields;

global $display_invitation;

// Default params:
$params = array_merge( array(
		'skin_form_before'          => '',
		'skin_form_after'           => '',
		'register_page_before'      => '',
		'register_page_after'       => '',
		'register_form_title'       => '',
		'form_class_register'       => 'evo_form__register',
		'register_links_attrs'      => ' style="margin: 1em 0 1ex"',
		'register_use_placeholders' => false, // Set TRUE to use placeholders instead of notes for input fields
		'register_field_width'      => 140,
		'register_form_footer'      => true,
		'register_form_params'      => NULL,
		'register_disp_home_button' => false, // Display button to go home when registration is disabled
		'display_form_messages'     => false,
		// If registration is disabled
		'register_disabled_page_before' => '',
		'register_disabled_page_after'  => '',
		'register_disabled_form_title'  => T_('Registration Currently Disabled'),
	), $params );

if( is_logged_in() )
{ // if a user is already logged in don't allow to register
	echo '<p>'.T_('You are already logged in').'</p>';
	return;
}

if( $display_invitation == 'deny' )
{ // Registration is disabled
	echo $params['register_disabled_page_before'];
	echo str_replace( '$form_title$', $params['register_disabled_form_title'], $params['skin_form_before'] );

	echo '<p class="error red">'.T_('User registration is currently not allowed.').'</p>';

	if( $params['register_disp_home_button'] )
	{
		echo '<p class="center"><a href="'.$baseurl.'" class="btn btn-default">'.T_('Home').'</a></p>';
	}

	echo $params['skin_form_after'];
	echo $params['register_disabled_page_after'];
	return;
}

echo $params['register_page_before'];

if( $params['display_form_messages'] )
{ // Display the form messages before form inside wrapper
	messages( array(
			'block_start' => '<div class="action_messages">',
			'block_end'   => '</div>',
		) );
}

// Save trigger page
$session_registration_trigger_url = $Session->get( 'registration_trigger_url' );
if( empty( $session_registration_trigger_url ) && isset( $_SERVER['HTTP_REFERER'] ) )
{	// Trigger page still is not defined
	$Session->set( 'registration_trigger_url', $_SERVER['HTTP_REFERER'] );
}

$login = param( $dummy_fields[ 'login' ], 'string', '' );
$email = utf8_strtolower( param( $dummy_fields[ 'email' ], 'string', '' ) );
$firstname = param( 'firstname', 'string', '' );
$gender = param( 'gender', 'string', false );
$source = param( 'source', 'string', 'register form' );
$redirect_to = param( 'redirect_to', 'url', '' );
$return_to = param( 'return_to', 'url', '' );

if( $register_user = $Session->get('core.register_user') )
{	// Get an user data from predefined session (after adding of a comment)
	$login = preg_replace( '/[^a-z0-9_\-\. ]/i', '', $register_user['name'] );
	$login = str_replace( ' ', '_', $login );
	$login = substr( $login, 0, 20 );
	$email = $register_user['email'];

	$Session->delete( 'core.register_user' );
}

echo str_replace( '$form_title$', $params['register_form_title'], $params['skin_form_before'] );

$Form = new Form( get_htsrv_url( true ).'register.php', 'register_form', 'post' );

if( ! is_null( $params['register_form_params'] ) )
{ // Use another template param from skin
	$Form->switch_template_parts( $params['register_form_params'] );
}

$Form->add_crumb( 'regform' );
$Form->hidden( 'inskin', true );
if( isset( $Blog ) )
{ // for in-skin form
	$Form->hidden( 'blog', $Blog->ID );
}

// disp register form
$Form->begin_form( $params['form_class_register'] );

$Plugins->trigger_event( 'DisplayRegisterFormBefore', array( 'Form' => & $Form, 'inskin' => true ) );

$Form->hidden( 'action', 'register' );
$Form->hidden( 'source', $source );
$Form->hidden( 'redirect_to', $redirect_to );

if( $display_invitation == 'input' )
{ // Display an input field to enter invitation code manually or to change incorrect code
	$invitation_field_params = array( 'maxlength' => 32, 'class' => 'input_text', 'style' => 'width:138px' );
	if( $Settings->get( 'newusers_canregister' ) == 'invite' )
	{ // Invitation code must be required when users can register ONLY with this code
		$invitation_field_params['required'] = 'required';
	}
	$Form->text_input( 'invitation', get_param( 'invitation' ), 22, T_('Your invitation code'), '', $invitation_field_params );
}
elseif( $display_invitation == 'info' )
{ // Display info field (when invitation code is correct)
	$Form->info( T_('Your invitation code'), get_param( 'invitation' ) );
	$Form->hidden( 'invitation', get_param( 'invitation' ) );
}

// Login
$Form->text_input( $dummy_fields['login'], $login, 22, /* TRANS: noun */ T_('Login'), $params['register_use_placeholders'] ? '' : T_('Choose a username').'.',
	array(
			'placeholder'  => $params['register_use_placeholders'] ? T_('Choose a username') : '',
			'maxlength'    => 20,
			'class'        => 'input_text',
			'required'     => true,
			'input_suffix' => ' <span id="login_status"></span><span class="help-inline"><div id="login_status_msg" class="red"></div></span>',
			'style'        => 'width:'.( $params['register_field_width'] - 2 ).'px',
		)
	);

// Passwords
$Form->password_input( $dummy_fields[ 'pass1' ], '', 18, T_('Password'),
	array(
			'note'         => $params['register_use_placeholders'] ? '' : T_('Choose a password').'.',
			'placeholder'  => $params['register_use_placeholders'] ? T_('Choose a password') : '',
			'maxlength'    => 70,
			'class'        => 'input_text',
			'required'     => true,
			'style'        => 'width:'.$params['register_field_width'].'px',
			'autocomplete' => 'off'
		)
	);
$Form->password_input( $dummy_fields[ 'pass2' ], '', 18, '',
	array(
			'note'         => ( $params['register_use_placeholders'] ? '' : T_('Please type your password again').'.' ).'<div id="pass2_status" class="red"></div>',
			'placeholder'  => $params['register_use_placeholders'] ? T_('Please type your password again') : '',
			'maxlength'    => 70,
			'class'        => 'input_text',
			'required'     => true,
			'style'        => 'width:'.$params['register_field_width'].'px',
			'autocomplete' => 'off'
		)
	);

// Email
$Form->email_input( $dummy_fields['email'], $email, 50, T_('Email'),
	array(
			'placeholder' => $params['register_use_placeholders'] ? T_('Email address') : '',
			'bottom_note' => T_('We respect your privacy. Your email will remain strictly confidential.'),
			'maxlength'   => 255,
			'class'       => 'input_text wide_input',
			'required'    => true,
		)
	);

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
				array( 'value' => 'O', 'label' => T_('Other') ),
			), T_('I am'), array( 'required' => $registration_require_gender == 'required' ) );
}

if( $Settings->get( 'registration_ask_locale' ) )
{ // ask user language
	$locale = 'en_US';
	$Form->select( 'locale', $locale, 'locale_options_return', T_('Locale'), T_('Preferred language') );
}

// Plugin fields
$Plugins->trigger_event( 'DisplayRegisterFormFieldset', array(
		'Form'             => & $Form,
		'inskin'           => true,
		'use_placeholders' => $params['register_use_placeholders']
	) );

// Buttons:
$form_fieldstart = str_replace( array( '$id$', 'class="' ), array( '', 'class="center ' ), $Form->fieldstart );
echo $form_fieldstart;
$Form->button_input( array( 'name' => 'register', 'value' => T_('Register my account now!'), 'class' => 'search btn-primary btn-lg' ) );
echo $Form->fieldend;
echo $form_fieldstart;
$Form->button_input( array( 'tag' => 'link', 'value' => T_('Already have an account... ?'), 'href' => get_login_url( $source, $redirect_to ), 'class' => 'btn-default btn-lg' ) );
echo $Form->fieldend;

$Form->end_form();

echo $params['skin_form_after'];

if( isset( $Blog ) )
{	// Only for front-office register page
	// ------------------ "Register" CONTAINER EMBEDDED HERE -------------------
	// Display container and contents:
	skin_container( NT_('Register'), array(
			// The following (optional) params will be used as defaults for widgets included in this container:
			// This will enclose each widget in a block:
			'block_start'       => '<br><div class="panel panel-default evo_widget $wi_class$">',
			'block_end'         => '</div>',
			// This will enclose the title of each widget:
			'block_title_start' => '<div class="panel-heading"><h4 class="panel-title">',
			'block_title_end'   => '</h4></div>',
			// This will enclose the body of each widget:
			'block_body_start'  => '<div class="panel-body">',
			'block_body_end'    => '</div>',
		) );
	// --------------------- END OF "Register" CONTAINER -----------------------
}

if( $params['register_form_footer'] )
{ // Display register form footer
	echo '<div class="evo_login_dialog_standard_link"><a href="'.get_htsrv_url( true ).'register.php?source='.rawurlencode( $source ).'&amp;redirect_to='.rawurlencode( $redirect_to ).'&amp;return_to='.rawurlencode( $return_to ).'">'.T_( 'Use standard registration form instead').' &raquo;</a></div>';

	echo '<div class="evo_login_dialog_footer text-muted">'.sprintf( T_('Your IP address: %s'), $Hit->IP ).'</div>';
}

echo $params['register_page_after'];

// Display javascript password strength indicator bar
display_password_indicator( array( 'field-width' => $params['register_field_width'] ) );

// Display javascript login validator
display_login_validator();
?>