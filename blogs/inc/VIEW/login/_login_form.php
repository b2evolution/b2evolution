<?php
/**
 * This is the login form
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package htsrv
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


param( 'redirect_to', 'string', str_replace( '&', '&amp;', $ReqURI ) ); // Note: if $redirect_to is already set, param() will not touch it.
param( 'login', 'string', '' ); // last typed login


if( preg_match( '#login.php([&?].*)?$#', $redirect_to ) )
{ // avoid "endless loops"
	$redirect_to = str_replace( '&', '&amp;', $admin_url );
}
// Remove login and pwd parameters from URL, so that they do not trigger the login screen again:
$redirect_to = preg_replace( '~(?<=\?|&amp;|&) (login|pwd) = [^&]+ (&(amp;)?|\?)?~x', '', $redirect_to );

if( $Session->has_User() )
{ // The user is already logged in...
	$tmp_User = & $Session->get_User();
	$Messages->add( sprintf( T_('Note: You are already logged in as %s!'), $tmp_User->get('login') )
		.' <a href="'.$redirect_to.'">'.T_('Continue...').'</a>', 'note' );
}


/**
 * Include page header (also displays Messages):
 */
$page_title = T_('Login form');
$page_icon = 'icon_login.gif';
require dirname(__FILE__).'/_header.php';


if( strpos( $redirect_to, str_replace('&', '&amp;', $admin_url) ) === 0 )
{ // don't provide link to bypass
	$login_required = true;
}


$Debuglog->add( 'redirect_to: '.$redirect_to );

// The login form has to point back to itself, in case $htsrv_url is a "https" link and $redirect_to is not!
$Form = & new Form( $htsrv_url.'login.php', '', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

	$Form->hiddens_by_key( $_POST, /* exclude: */ array('login_action', 'login') ); // passthrough POSTed data (when login is required after having POSTed something)
	$Form->hidden( 'redirect_to', $redirect_to );

	if( !empty($mode) )
	{ // We're in the process of bookmarkletting something, we don't want to loose it:
		param( 'text', 'html', '' );
		param( 'popupurl', 'html', '' );
		param( 'popuptitle', 'html', '' );

		$Form->hidden( 'mode', $mode );
		$Form->hidden( 'text', $text );
		$Form->hidden( 'popupurl', $popupurl );
		$Form->hidden( 'popuptitle', $popuptitle );
	}

	echo $Form->fieldstart;

	?>

	<div class="center"><span class="notes"><?php printf( T_('You will have to accept cookies in order to log in.') ) ?></span></div>

	<?php
	$Form->text_input( 'login', $login, 16, T_('Login'), array( 'maxlength' => 20, 'class' => 'input_text' ) );

	$Form->password_input( 'pwd', '', 16, T_('Password'), array( 'maxlength' => 50, 'class' => 'input_text' ) );

	// Allow a plugin to add fields/payload
	$Plugins->trigger_event( 'DisplayLoginFormFieldset', array( 'Form' => & $Form ) );

	echo $Form->fieldstart;
	echo $Form->inputstart;
	$Form->submit( array( 'login_action[login]', T_('Log in!'), 'search' ) );

	if( strpos( $redirect_to, str_replace( '&', '&amp;', $admin_url ) ) !== 0 && ! is_admin_page() )
	{ // provide button to log straight into backoffice, if we would not go there anyway
		$Form->submit( array( 'login_action[redirect_to_backoffice]', T_('Log into backoffice!'), 'search' ) );
	}
	echo $Form->inputend;
	echo $Form->fieldend;

	echo $Form->fieldend;
$Form->end_form();


// Autoselect login text input:
?>

<script type="text/javascript">
	document.getElementById( 'login' ).focus();
</script>


<div class="login_actions" style="text-align:right">
	<?php user_register_link( '', ' &middot; ' )?>
	<a href="<?php echo $htsrv_url ?>login.php?action=lostpassword&amp;redirect_to=<?php
		echo rawurlencode( $redirect_to );
		if( !empty($login) )
		{
			echo '&amp;login='.rawurlencode($login);
		}
		?>"><?php echo T_('Lost your password ?')
		?></a>
	<?php
	if( empty($login_required) )
	{ // No login required, allow to pass through
		echo '<a href="'.$redirect_to.'">'./* Gets displayed as link to the location on the login form if no login is required */ T_('Bypass login...').'</a>';
	}
	?>
</div>


<?php
require dirname(__FILE__).'/_footer.php';
?>
