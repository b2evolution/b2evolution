<?php
/**
 * This file implements the login form
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

$login = param( 'login', 'string', '' );
$action = param( 'action', 'string', '' );

if( is_logged_in() )
{ // already logged in
	return;
}

if( $action == 'req_login' ) 
{
	$login_required = true;
}

global $admin_url, $ReqHost, $htsvr_url;

if( $Messages->count() > 0 )
{ // Display messages
	$Messages->display();
}

$Form = new Form( '', 'login_form' );

$Form->begin_form( 'bComment' );

	$Form->hidden( 'redirect_to', $redirect_to );

	$Form->begin_fieldset( '', array( 'class' => /*'border:none'*/'noborder' ) );

	$Form->text_input( 'login', $login, 16, T_('Login'), '', array( 'maxlength' => 20, 'class' => 'input_text' ) );

	$pwd_note = '<a href="'.$htsrv_url_sensitive.'login.php?action=lostpassword&amp;redirect_to='
		.rawurlencode( url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );
	if( !empty($login) )
	{
		$pwd_note .= '&amp;login='.rawurlencode($login);
	}
	$pwd_note .= '">'.T_('Lost password ?').'</a>';

	$Form->password_input( 'pwd', '', 16, T_('Password'), array( 'note'=>$pwd_note, 'maxlength' => 70, 'class' => 'input_text' ) );

	// Allow a plugin to add fields/payload
	$Plugins->trigger_event( 'DisplayLoginFormFieldset', array( 'Form' => & $Form ) );

	// Submit button:
	$submit_button = array( array( 'name'=>'login_action[login]', 'value'=>T_('Log in!'), 'class'=>'search' ) );

	$Form->buttons_input($submit_button);

	$links = array();

	// link to standard login screen
	$link = '<a href="'.$htsrv_url.'login.php?redirect_to'.$redirect_to.'">'.T_( 'Standard login form').' &raquo;</a>';
	$links[] = $link;

	if( $link = get_user_register_link( '', '', '', '#', true /*disp_when_logged_in*/, $redirect_to ) )
	{ // registration is allowed, add register link
		$links[] = $link;
	}

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


/*
 * $Log$
 * Revision 1.4  2010/11/25 15:16:35  efy-asimo
 * refactor $Messages
 *
 * Revision 1.3  2010/07/26 06:52:27  efy-asimo
 * MFB v-4-0
 *
 */
?>