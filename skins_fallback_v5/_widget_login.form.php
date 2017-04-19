<?php
/**
 * This file implements the widget login form
 *
 * This file is not meant to be called directly.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $baseurl, $dummy_fields;

/**
 * @var object user_login_Widget
 */
$Widget = !empty( $this ) ? $this : false;

$ajax_form_enabled = ( !empty( $Blog ) && ( $Blog->get_ajax_form_enabled() ) );

$Form = new Form( get_login_url( $source, $redirect_to ), 'login_form', 'post' );

$Form->begin_form( NULL, '', array( 'style' => 'display:none' ) );

if( $ajax_form_enabled )
{ // ajax form is enabled, add hidden action param, because we will catch the form submit button action
	$Form->hidden( 'login_action', array( 'login' => 'login' ) );
}
$Form->hidden( 'crumb_loginform', '' );
$Form->hidden( 'pepper', '' );
$Form->hidden( 'source', $source );
$Form->hidden( 'inskin', true );
$Form->hidden( 'redirect_to', $redirect_to );

$Form->text_input( $dummy_fields[ 'login' ], '', 18, /* TRANS: noun */ T_('Login'), '', array( 'maxlength' => 255, 'class' => 'input_text', 'required'=>true ) );
$Form->password_input( $dummy_fields[ 'pwd' ], '', 18, T_('Password'), array( 'maxlength' => 70, 'class' => 'input_text', 'required'=>true ) );

// Add container for the hashed passwords
echo '<div id="pwd_hashed_container"></div>';

// Submit button and lost password link:
$submit_button = array(
	'id' => 'submit_login_form',
	'name' => 'login_action[login]',
	'value' => T_('Log in!'),
	'class' => 'submit' );
$Form->begin_fieldset( '', array( 'class' => 'fieldset field_login_btn' ) );
$Form->button_input( $submit_button );
if( $Widget && $Widget->get_param('password_link_show') )
{ // Display a link to recovery password
	echo '<a href="'.get_lostpassword_url().'">'.$Widget->get_param('password_link').'</a>';
}
$Form->end_fieldset();

$Form->end_form();


// Display only button to login if JS scripts or AJAX forms are disabled
echo $ajax_form_enabled ? '<noscript>' : '';

echo get_user_login_link( '<br /><strong>', '</strong><br /><br />', T_('Login now...'), '#', $source, $redirect_to );

echo $ajax_form_enabled ? '</noscript>' : '';

if( $Widget && $Widget->get_param('register_link_show') )
{	// Display a link to register
	echo get_user_register_link( '<span class="register_link">', '</span>', $Widget->get_param('register_link'), '#', true /*disp_when_logged_in*/, $redirect_to, $source );
}

if( $ajax_form_enabled )
{ // create javascripts to handle login form crumb and password salts

	?>
	<script type="text/javascript">
		// Show login form when JS scripts and AJAX forms are enabled
		jQuery( 'form#login_form' ).show();
	</script>
	<?php

	$params = array( 'transmit_hashed_password' => true, 'get_widget_login_hidden_fields' => true );
	display_login_js_handler( $params );
} // end ajax crumb functions

?>