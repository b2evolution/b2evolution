<?php
/**
 * This is the login form
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package htsrv
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// TODO: dh> the message below should also get displayed in _reg_form.
// E.g., the user might have clicked accidently on an old password change link.
if( $Session->has_User() )
{ // The user is already logged in...
	$tmp_User = & $Session->get_User();
	if( $tmp_User->check_status( 'is_validated' ) )
	{	// User account is active
		if( empty($redirect_to) || $redirect_to == '/login.php' )
		{	// Prevent endless loops
			$redirect_to = $baseurl;
		}
		$Messages->add( sprintf( T_('Note: You are already logged in as %s!'), $tmp_User->get('login') )
			.' <a href="'.htmlspecialchars($redirect_to).'">'.T_('Continue').'&nbsp;&raquo;</a>', 'note' );
	}
	unset($tmp_User);
}


/**
 * Include page header (also displays Messages):
 */
$page_title = T_('Log in to your account');
$wrap_width = '380px';

/*
  fp> The login page is small. Let's use it as a preloader for the backoffice (which is awfully slow to initialize)
  fp> TODO: find a javascript way to preload more stuff (like icons) WITHOUT delaying the browser autocomplete of the login & password fields
	dh>
	// include jquery JS:
	require_js( '#jquery#' );

	jQuery(function(){
	 alert("Document is ready");
	});
	See also http://www.texotela.co.uk/code/jquery/preload/ - might be a good opportunity to take a look at jQuery for you.. :)
 */


require_js( 'functions.js' );

$transmit_hashed_password = (bool)$Settings->get('js_passwd_hashing') && !(bool)$Plugins->trigger_event_first_true('LoginAttemptNeedsRawPassword');
if( $transmit_hashed_password )
{ // Include JS for client-side password hashing:
	require_js( 'build/sha1_md5.bmin.js' );
}

// Use the links in the form title
$use_form_links = true;

// Header
require dirname(__FILE__).'/_html_header.inc.php';

// Login form
$params = array(
	'skin_form_before'         => $login_form_params['formstart'],
	'skin_form_after'          => $login_form_params['formend'],
	'form_title_login'         => $page_title,
	'login_page_class'         => 'evo_panel__login',
	'login_page_before'        => '',
	'login_page_after'         => '',
	'login_form_action'        => $secure_htsrv_url.'login.php',
	'login_form_name'          => 'login_form',
	'login_form_title'         => '',
	'login_form_layout'        => 'fieldset',
	'form_class_login'         => 'form-horizontal evo_form__login',
	'login_form_source'        => param( 'source', 'string', 'std login form' ),
	'login_form_inskin'        => false,
	'login_form_inskin_urls'   => false,
	'login_form_required'      => $login_required,
	'login_validate_required'  => $validate_required,
	'login_form_redirect_to'   => $redirect_to,
	'login_form_return_to'     => $return_to,
	'login_form_login'         => utf8_strtolower( $login ),
	'login_action_value'       => $action,
	'login_form_reqID'         => isset( $reqID ) ? $reqID : NULL,
	'login_form_sessID'        => isset( $sessID ) ? $sessID : NULL,
	'transmit_hashed_password' => $transmit_hashed_password,
	'display_abort_link'       => true,
	'abort_link_position'      => 'form_title',
	'abort_link_text'          => '<button type="button" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></button>',
	'display_reg_link'         => true,
	'login_form_footer'        => false,
);
require skin_fallback_path( '_login.disp.php', 6 );

// Footer
require dirname(__FILE__).'/_html_footer.inc.php';

?>