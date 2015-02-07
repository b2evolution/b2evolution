<?php
/**
 * This is the form to change a password
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _password_form.main.php 8056 2015-01-23 10:30:37Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Include page header:
 */
$page_title = T_('Change password');
$wrap_width = '650px';

require dirname(__FILE__).'/_html_header.inc.php';

// set secure htsrv url with the same domain as the request has
$secure_htsrv_url = get_secure_htsrv_url();

echo str_replace( '$title$', $page_title,  $form_before );

$Form = new Form( $secure_htsrv_url.'login.php', 'password_form', 'post', 'fieldset' );

$Form->switch_template_parts( $login_form_params );

$Form->begin_form( 'form-login' );

$Form->add_crumb( 'regform' );
$Form->hidden( 'action', 'updatepwd' );
$Form->hidden( 'password_form', '1' );
$Form->hidden( 'reqID', $reqID );
$Form->hidden( 'user_ID', $forgetful_User->ID );
$Form->hidden( 'edited_user_login', $forgetful_User->login );
$Form->hidden( 'redirect_to', url_rel_to_same_host( $redirect_to, $secure_htsrv_url ) );

$Form->begin_fieldset();

	$Form->password_input( 'edited_user_pass1', '', 20, T_('New password'), array( 'note' => sprintf( T_('Minimum length: %d characters.'), $Settings->get('user_minpwdlen') ), 'maxlength' => 50, 'required' => true, 'autocomplete'=>'off' ) );
	$Form->password_input( 'edited_user_pass2', '', 20, T_('Confirm new password'), array( 'maxlength' => 50, 'required' => true, 'autocomplete'=>'off', 'note' => '<span id="pass2_status" class="field_error"></span>' ) );

	$Form->buttons_input( array( array( 'name' => 'submit', 'value' => T_('Change password!'), 'class' => 'btn-primary btn-lg' ) ) );

$Form->end_fieldset();
$Form->end_form(); // display hidden fields etc

echo $form_after;

// Display javascript password strength indicator bar
display_password_indicator( array( 'field-width' => 252 ) );

// Display javascript password strength indicator bar
display_password_indicator( array(
			'pass1-id'    => 'edited_user_pass1',
			'pass2-id'    => 'edited_user_pass2',
			'login-id'    => 'edited_user_login',
			'field-width' => 252,
	) );

require dirname(__FILE__).'/_html_footer.inc.php';

?>