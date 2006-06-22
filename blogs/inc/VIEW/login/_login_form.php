<?php
/**
 * This is the login form
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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


/**
 * Include page header (also displays Messages):
 */
$page_title = T_('Login form');
$page_icon = 'icon_login.gif';
require dirname(__FILE__).'/_header.php';


// The login form has to point back to itself, in case $htsrv_url_sensible is a "https" link and $redirect_to is not!
$Form = & new Form( $htsrv_url_sensible.'login.php', '', 'post', 'fieldset' );

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
	<?php user_register_link( '', ' &middot; ', '', '#', true /*disp_when_logged_in*/ )?>

	<a href="<?php echo $htsrv_url_sensible.'login.php?action=lostpassword'
		.'&amp;redirect_to='.rawurlencode( $redirect_to );
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

/*
 * $Log$
 * Revision 1.8  2006/06/22 22:30:04  blueyed
 * htsrv url for sensible scripts (login, register and profile update)
 *
 * Revision 1.7  2006/04/22 02:36:38  blueyed
 * Validate users on registration through email link (+cleanup around it)
 *
 * Revision 1.6  2006/04/20 22:13:48  blueyed
 * Display "Register..." link in login form also if user is logged in already.
 *
 * Revision 1.5  2006/04/19 20:13:51  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 */
?>
