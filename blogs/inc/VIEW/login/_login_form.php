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

$need_raw_pwd = (bool)$Plugins->trigger_event_first_true('LoginAttemptNeedsRawPassword');

header_nocache(); // do not cache this page, because the JS password salt has to match the session cookie

/**
 * Include page header (also displays Messages):
 */
$page_title = T_('Login form');
$page_icon = 'icon_login.gif';

if( ! $need_raw_pwd )
{ // Include JS for client-side password hashing:
	$evo_html_headlines[] = '<script type="text/javascript" src="'.$rsc_url.'js/functions.js"></script>';
	$evo_html_headlines[] = '<script type="text/javascript" src="'.$rsc_url.'js/md5.js"></script>';
	$evo_html_headlines[] = '<script type="text/javascript" src="'.$rsc_url.'js/sha1.js"></script>';
}

require dirname(__FILE__).'/_header.php';


// The login form has to point back to itself, in case $htsrv_url_sensitive is a "https" link and $redirect_to is not!
$Form = & new Form( $htsrv_url_sensitive.'login.php', 'evo_login_form', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

	$Form->hiddens_by_key( $_POST, /* exclude: */ array('login_action', 'login') ); // passthrough POSTed data (when login is required after having POSTed something)
	$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );

	if( ! $need_raw_pwd )
	{ // used by JS-password encryption/hashing:
		$pwd_salt = $Session->get('core.pwd_salt');
		if( empty($pwd_salt) )
		{ // generate anew, only if empty - so multiple login screens share the same hash. Gets reset on trying to login.
			$pwd_salt = generate_random_key(64);
			$Session->set( 'core.pwd_salt', $pwd_salt, 86400 /* expire in 1 day */ );
		}
		$Form->hidden( 'pwd_salt', $pwd_salt );
		$Form->hidden( 'pwd_hashed', '' ); // gets filled by JS
	}

	if( isset( $action, $reqID, $sessID ) && $action == 'validatemail' )
	{ // the user clicked the link from the "validate your account" email, but has not been logged in; pass on the relevant data:
		$Form->hidden( 'action', 'validatemail' );
		$Form->hidden( 'reqID', $reqID );
		$Form->hidden( 'sessID', $sessID );
	}

	if( ! empty($mode) )
	{ // We're in the process of bookmarkletting something, we don't want to lose it:
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

	if( strpos( $redirect_to, str_replace( '&', '&amp;', $admin_url ) ) !== 0
		&& strpos( $ReqHost.$redirect_to, str_replace( '&', '&amp;', $admin_url ) ) !== 0 // if $redirect_to is relative
		&& ! is_admin_page() )
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

	<?php
	if( ! $need_raw_pwd )
	{
		?>
		// Hash the password onsubmit and clear the original pwd field
		addEvent( document.getElementById("evo_login_form"), "submit", function(){
				var f = document.getElementById('evo_login_form');
				var h = f.pwd_hashed;
				var p = f.pwd;
				var s = f.pwd_salt;
				if( h && p && s && typeof hex_sha1 != "undefined" && typeof hex_md5 != "undefined" )
				{
					h.value = hex_sha1( hex_md5(p.value) + s.value );
					p.value = "hashed_<?php echo $Session->ID /* to detect cookie problems */ ?>";
				}
				return true;
			}, false );
		<?php
	}
	?>
</script>


<div class="login_actions" style="text-align:right">
	<?php user_register_link( '', ' &middot; ', '', '#', true /*disp_when_logged_in*/ )?>

	<a href="<?php echo $htsrv_url_sensitive.'login.php?action=lostpassword'
		.'&amp;redirect_to='.rawurlencode( url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );
		if( !empty($login) )
		{
			echo '&amp;login='.rawurlencode($login);
		}
		?>"><?php echo T_('Lost your password ?')
		?></a>

	<?php
	if( empty($login_required) )
	{ // No login required, allow to pass through
		echo '<a href="'.url_rel_to_same_host($redirect_to, $ReqHost).'">'./* Gets displayed as link to the location on the login form if no login is required */ T_('Bypass login...').'</a>';
	}
	?>
</div>


<?php
require dirname(__FILE__).'/_footer.php';

/*
 * $Log$
 * Revision 1.16  2006/10/15 21:30:46  blueyed
 * Use url_rel_to_same_host() for redirect_to params.
 *
 * Revision 1.15  2006/10/14 16:27:05  blueyed
 * Client-side password hashing in the login form.
 *
 * Revision 1.14  2006/10/12 23:48:15  blueyed
 * Fix for if redirect_to is relative
 *
 * Revision 1.13  2006/07/23 20:18:31  fplanque
 * cleanup
 *
 * Revision 1.12  2006/07/17 01:33:13  blueyed
 * Fixed account validation by email for users who registered themselves
 *
 * Revision 1.11  2006/07/01 23:49:59  fplanque
 * wording
 *
 * Revision 1.10  2006/06/25 23:34:15  blueyed
 * wording pt2
 *
 * Revision 1.9  2006/06/25 23:23:38  blueyed
 * wording
 *
 * Revision 1.8  2006/06/22 22:30:04  blueyed
 * htsrv url for password related scripts (login, register and profile update)
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
