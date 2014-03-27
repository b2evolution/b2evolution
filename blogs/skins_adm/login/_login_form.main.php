<?php
/**
 * This is the login form
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
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
			.' <a href="'.evo_htmlspecialchars($redirect_to).'">'.T_('Continue').' &raquo;</a>', 'note' );
	}
	unset($tmp_User);
}


/**
 * Include page header (also displays Messages):
 */
$page_title = T_('Log in to your account');
$wrap_height = '300px';

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
	require_js( 'sha1_md5.js' );
}

/**
 * Login header
 */
require dirname(__FILE__).'/_html_header.inc.php';

$params = array(
	'form_before' => str_replace( '$title$', $page_title, $form_before ),
	'form_after' => $form_after,
	'form_action' => $secure_htsrv_url.'login.php',
	'form_layout' => 'fieldset',
	'form_class' => 'form-login',
	'source' => param( 'source', 'string', 'std login form' ),
	'inskin' => false,
	'inskin_urls' => false,
	'redirect_to' => $redirect_to,
	'login' => evo_strtolower( $login ),
	'login_required' => $login_required,
	'validate_required' => $validate_required,
	'action' => $action,
	'reqID' => isset( $reqID ) ? $reqID : NULL,
	'sessID' => isset( $sessID ) ? $sessID : NULL,
	'transmit_hashed_password' => $transmit_hashed_password,
);

display_login_form( $params );

require dirname(__FILE__).'/_html_footer.inc.php';

?>