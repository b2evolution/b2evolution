<?php
/**
 * This file implements the widget login form
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
 * @version $Id: _widget_login.form.php 68 2011-10-26 13:16:00Z yura $
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
$Form->hidden( 'pwd_salt', '' );
$Form->hidden( 'pwd_hashed', '' );
$Form->hidden( 'source', $source );
$Form->hidden( 'inskin', true );
$Form->hidden( 'redirect_to', $redirect_to );

$Form->text_input( $dummy_fields[ 'login' ], '', 18, T_('Login'), '', array( 'maxlength' => 255, 'class' => 'input_text', 'required'=>true ) );
$Form->password_input( $dummy_fields[ 'pwd' ], '', 18, T_('Password'), array( 'maxlength' => 70, 'class' => 'input_text', 'required'=>true ) );

// Submit button and lost password link:
$submit_button = array(
	'id' => 'submit_login_form',
	'name' => 'login_action[login]',
	'value' => T_('Log in!'),
	'class' => 'submit' );
$Form->begin_fieldset( '', array( 'class' => 'fieldset field_login_btn' ) );
$Form->button_input( $submit_button );
if( $Widget && $Widget->get_param('password_link_show') )
{	// Display a link to recovery password
	$lost_password_url = url_add_param( ( empty( $Blog ) ? $baseurl : $Blog->gen_blogurl() ), 'disp=lostpassword' );
	echo '<a href="'.$lost_password_url.'">'.$Widget->get_param('password_link').'</a>';
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
{ // create javascripts to handle login form crumb and password salt
	global $samedomain_htsrv_url;
	$json_params = evo_json_encode( array( 'action' => 'get_widget_login_hidden_fields' ) );

	?>
	<script type="text/javascript">
		// Show login form when JS scripts and AJAX forms are enabled
		jQuery( 'form#login_form' ).show();

		var requestSent = false;
		var requestSucceed = false;
		var submitFormIfRequestSucceed = false;
		var sessionID = 0;

		// Calculate hashed password and set it in the form
		function setPwdHashed() {
			var form = document.forms['login_form'];
			form.pwd_hashed.value = hex_sha1( hex_md5(form.<?php echo $dummy_fields[ 'pwd'] ?>.value) + form.pwd_salt.value );
			form.<?php echo $dummy_fields[ 'pwd'] ?>.value = "padding_padding_padding_padding_padding_padding_hashed_" + sessionID; /* to detect cookie problems */
		}

		// get and set login form hidden fields
		function getHiddenFields() {
			if( requestSent ) {
				return;
			}
			requestSent = true;
			jQuery.ajax({
				url: '<?php echo $samedomain_htsrv_url; ?>anon_async.php',
				type: 'POST',
				data: <?php echo $json_params; ?>,
				success: function(result)
				{
					result = ajax_debug_clear( result );
					var form = document.forms['login_form'];
					var hidden_fields = new Array();
					hidden_fields = result.split(' ');
					var crumb = hidden_fields.shift();
					var salt = hidden_fields.shift();
					sessionID = hidden_fields.shift();
					form.crumb_loginform.value = crumb;
					form.pwd_salt.value = salt;
					requestSucceed = true;
					if( submitFormIfRequestSucceed ) { // submit button was already clicked
						setPwdHashed();
						form.submit();
					}
					return false;
				},
				error: function()
				{
					requestSent = false;
					return false;
				}
			});
		}

		// if login input was changed or got the focus
		jQuery('#login_form #<?php echo $dummy_fields[ 'login' ] ?>').bind( "focus change", function() {
			getHiddenFields();
		});

		// if password input was changed or got the focus
		jQuery('#login_form #<?php echo $dummy_fields[ 'pwd' ] ?>').bind( "focus change", function() {
			getHiddenFields();
		});

		// if submit button was clicked
		jQuery('#submit_login_form').click( function() {
			if( !requestSucceed ) {
				submitFormIfRequestSucceed = true;
				getHiddenFields();
				return false;
			}
			setPwdHashed();
		});
	</script>
	<?php
} // end ajax crumb functions

?>