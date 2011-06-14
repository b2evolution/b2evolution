<?php
/**
 * This file implements the login form
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

global $blog;

$login = param( 'login', 'string', '' );
$action = param( 'action', 'string', '' );
$email = param( 'email', 'string', '' );
$redirect_to = param( 'redirect_to', 'string', '' );

if( is_logged_in() && ( $action != 'req_validatemail' ) )
{ // already logged in
	echo '<p>'.T_('You are already logged in').'</p>';
	return;
}

if( $action == 'req_login' ) 
{
	$login_required = true;
}

global $admin_url, $ReqHost, $htsvr_url;

if( !isset( $redirect_to ) )
{
	$redirect_to = regenerate_url( 'disp' );
}

if( $action != 'req_validatemail' )
{
	$Form = new Form( '', 'login_form', 'post' );

	$Form->begin_form( 'bComment' );

	$Form->hidden( 'redirect_to', $redirect_to );
	$Form->hidden( 'inskin', true );

	$Form->begin_field();
	$Form->text_input( 'login', $login, 16, T_('Login'), '', array( 'maxlength' => 20, 'class' => 'input_text' ) );
	$Form->end_field();

	$pwd_note = '<a href="'.$htsrv_url_sensitive.'login.php?action=lostpassword&amp;redirect_to='
		.rawurlencode( url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );
	if( !empty($login) )
	{
		$pwd_note .= '&amp;login='.rawurlencode($login);
	}
	$pwd_note .= '">'.T_('Lost password ?').'</a>';

	$Form->begin_field();
	$Form->password_input( 'pwd', '', 16, T_('Password'), array( 'note'=>$pwd_note, 'maxlength' => 70, 'class' => 'input_text' ) );
	$Form->end_field();

	// Allow a plugin to add fields/payload
	$Plugins->trigger_event( 'DisplayLoginFormFieldset', array( 'Form' => & $Form ) );

	// Submit button:
	$submit_button = array( array( 'name'=>'login_action[login]', 'value'=>T_('Log in!'), 'class'=>'search' ) );

	$Form->buttons_input($submit_button);

	$links = array();

	// link to standard login screen
	$link = '<a href="'.$htsrv_url.'login.php?redirect_to='.$redirect_to.'">'.T_( 'Standard login form').' &raquo;</a>';
	$links[] = $link;

	if( $link = get_user_register_link( '', '', '', '#', true /*disp_when_logged_in*/ ) )
	{ // registration is allowed, add register link
		$links[] = $link;
	}

	$Form->begin_fieldset();
	echo '<div class="login_actions" style="text-align:right">';

	if( empty($login_required)
		&& $action != 'req_validatemail'
		&& strpos($redirect_to, $admin_url) !== 0
		&& strpos($ReqHost.$redirect_to, $admin_url ) !== 0 )
	{ // No login required, allow to pass through
		$links[] = '<a href="'.htmlspecialchars(url_rel_to_same_host($redirect_to, $ReqHost)).'">'
		./* Gets displayed as link to the location on the login form if no login is required */ T_('Abort login!').'</a>';
	}

	echo implode( ' &middot; ', $links );
	echo '</div>';
	$Form->end_fieldset();

	$Form->end_form();
}
else
{
	$Form = new Form( $htsrv_url_sensitive.'login.php', 'login_form', 'post' );

	$Form->begin_form( 'bComment' );

	$Form->add_crumb( 'validateform' );
	$Form->hidden( 'action', 'req_validatemail');
	$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );
	$Form->hidden( 'inskin', true );
	if( isset( $blog ) )
	{
		$Form->hidden( 'blog', $blog );
	}
	$Form->hidden( 'req_validatemail_submit', 1 ); // to know if the form has been submitted

	$Form->begin_fieldset();

	echo '<ol>';
	echo '<li>'.T_('Please confirm your email address below.').'</li>';
	echo '<li>'.T_('An email will be sent to this address immediately.').'</li>';
	echo '<li>'.T_('As soon as you receive the email, click on the link therein to activate your account.').'</li>';
	echo '</ol>';

	$Form->text_input( 'email', $email, 16, T_('Email'), '', array( 'maxlength'=>255, 'class'=>'input_text', 'required'=>true ) );
	$Form->end_fieldset();

	// Submit button:
	$submit_button = array( array( 'name'=>'submit', 'value'=>T_('Send me an email now'), 'class'=>'submit' ) );

	$Form->buttons_input($submit_button);
	$Form->end_form();	
}


/*
 * $Log$
 * Revision 1.7  2011/06/14 20:20:44  sam2kb
 * Display message if a user is already logged in
 *
 * Revision 1.6  2011/06/14 13:33:56  efy-asimo
 * in-skin register
 *
 * Revision 1.5  2011/03/24 15:15:05  efy-asimo
 * in-skin login - feature
 *
 * Revision 1.4  2010/11/25 15:16:35  efy-asimo
 * refactor $Messages
 *
 * Revision 1.3  2010/07/26 06:52:27  efy-asimo
 * MFB v-4-0
 *
 */
?>