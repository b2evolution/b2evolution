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


/**
 * Include page header:
 */
$page_title = T_('Login form');
$page_icon = 'icon_login.gif';
require dirname(__FILE__).'/_header.php';

param( 'redirect_to', 'string', str_replace( '&', '&amp;', $ReqURI ) );
param( 'login', 'string', '' ); // last typed login

$location = $redirect_to;
$Debuglog->add( 'location: '.$location );

$Form = & new Form( $location, '', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

// TODO: handle POSTed data! - just transfer $_POST into hidden fields!?

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

	$Form->password_input( 'pwd', '', 16, T_('Password'), array( 'maxlength' => 20, 'class' => 'input_text' ) );

	echo $Form->fieldstart;
	echo $Form->inputstart;
	$Form->submit( array( 'submit', T_('Log in!'), 'search' ) );
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
	<a href="<?php echo $htsrv_url ?>login.php?action=lostpassword&amp;redirect_to=<?php echo rawurlencode( $redirect_to );
		?>"><?php echo T_('Lost your password ?')
		?></a>
	<?php
	if( empty($login_required) )
	{ // No login required, allow to pass through

		// Remove login and pwd parameters from URL, so that they do not trigger the login screen again:
		$location_without_login = preg_replace( '~(?<=\?|&amp;|&) (login|pwd) = [^&]+ (&(amp;)?|\?)?~x', '', $location );
		echo '<a href="'.$location_without_login.'">'./* Gets displayed as link to the location on the login form if no login is required */ T_('Bypass login...').'</a>';
	}
	?>
</div>


<?php
require dirname(__FILE__).'/_footer.php';
?>