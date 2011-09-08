<?php
/**
 * This is the login form
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
	if( $tmp_User->validated )
	{	// User is not validated (he may have been invalidated)
		// dh> TODO: validate $redirect_to param!
		//     TODO: prevent "endless loops" with $redirect_to here, too
		$Messages->add( sprintf( T_('Note: You are already logged in as %s!'), $tmp_User->get('login') )
			.' <a href="'.htmlspecialchars($redirect_to).'">'.T_('Continue').' &raquo;</a>', 'note' );
	}
	unset($tmp_User);
}


/**
 * Include page header (also displays Messages):
 */
$page_title = T_('Log in to your account');
$page_icon = 'icon_login.gif';

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
	require_js( 'md5.js' );
	require_js( 'sha1.js' );
}

/**
 * Login header
 */
require dirname(__FILE__).'/_html_header.inc.php';

$links = array();

if( empty($login_required)
	&& $action != 'req_validatemail'
	&& strpos($redirect_to, $admin_url) !== 0
	&& strpos($ReqHost.$redirect_to, $admin_url ) !== 0 )
{ // No login required, allow to pass through
	// TODO: dh> validate redirect_to param?!
	$links[] = '<a href="'.htmlspecialchars(url_rel_to_same_host($redirect_to, $ReqHost)).'">'
	./* Gets displayed as link to the location on the login form if no login is required */ T_('Abort login!').'</a>';
}

if( is_logged_in() )
{ // if we arrive here, but are logged in, provide an option to logout (e.g. during the email
	// validation procedure)
	$links[] = get_user_logout_link();
}

if( count($links) )
{
	echo '<div style="float:right; margin: 0 0 1em">'.implode( $links, ' &middot; ' ).'</div>
	<div class="clear"></div>';
}


// The login form has to point back to itself, in case $htsrv_url_sensitive is a "https" link and $redirect_to is not!
$Form = new Form( $htsrv_url_sensitive.'login.php', 'evo_login_form', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

	$Form->add_crumb( 'loginform' );
	$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );

	if( isset( $action, $reqID, $sessID ) && $action == 'validatemail' )
	{ // the user clicked the link from the "validate your account" email, but has not been logged in; pass on the relevant data:
		$Form->hidden( 'action', 'validatemail' );
		$Form->hidden( 'reqID', $reqID );
		$Form->hidden( 'sessID', $sessID );
	}

	if( $transmit_hashed_password )
	{ // used by JS-password encryption/hashing:
		$pwd_salt = $Session->get('core.pwd_salt');
		if( empty($pwd_salt) )
		{ // Do not regenerate if already set because we want to reuse the previous salt on login screen reloads
			// fp> Question: the comment implies that the salt is reset even on failed login attemps. Why that? I would only have reset it on successful login. Do experts recommend it this way?
			// but if you kill the session you get a new salt anyway, so it's no big deal.
			// At that point, why not reset the salt at every reload? (it may be good to keep it, but I think the reason should be documented here)
			$pwd_salt = generate_random_key(64);
			$Session->set( 'core.pwd_salt', $pwd_salt, 86400 /* expire in 1 day */ );
			$Session->dbsave(); // save now, in case there's an error later, and not saving it would prevent the user from logging in.
		}
		$Form->hidden( 'pwd_salt', $pwd_salt );
		$Form->hidden( 'pwd_hashed', '' ); // gets filled by JS
	}

	$Form->begin_fieldset();

	$Form->text_input( 'login', $login, 16, T_('Login'), T_('Type your username, <b>not</b> your email address.'), array( 'maxlength' => 20, 'class' => 'input_text' ) );

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

	// Submit button(s):
	$submit_buttons = array( array( 'name'=>'login_action[login]', 'value'=>T_('Log in!'), 'class'=>'search', 'style'=>'font-size: 120%' ) );
	if( strpos( $redirect_to, $admin_url ) !== 0
		&& strpos( $ReqHost.$redirect_to, $admin_url ) !== 0 // if $redirect_to is relative
		&& ! is_admin_page() )
	{ // provide button to log straight into backoffice, if we would not go there anyway
		$submit_buttons[] = array( 'name'=>'login_action[redirect_to_backoffice]', 'value'=>T_('Log into backoffice!'), 'class'=>'search' );
	}

	$Form->buttons_input($submit_buttons);

	echo '<div class="center notes" style="margin: 1em 0">'.T_('You will have to accept cookies in order to log in.').'</div>';

	$Form->info( '', '', sprintf( T_('Your IP address (%s) and the current time are being logged.'), $Hit->IP ) );

	$Form->end_fieldset();

	// Passthrough REQUEST data (when login is required after having POSTed something)
	// (Exclusion of 'login_action', 'login', and 'action' has been removed. This should get handled via detection in Form (included_input_field_names),
	//  and "action" is protected via crumbs)
	$Form->hiddens_by_key( remove_magic_quotes($_REQUEST) );
$Form->end_form();

?>

<script type="text/javascript">
	// Autoselect login text input or pwd input, if there's a login already:
	var login = document.getElementById('login');
	if( login.value.length > 0 )
	{	// Focus on the password field:
		document.getElementById('pwd').focus();
	}
	else
	{	// Focus on the login field:
		login.focus();
	}


	<?php
	if( $transmit_hashed_password )
	{
		?>
		// Hash the password onsubmit and clear the original pwd field
		// TODO: dh> it would be nice to disable the clicked/used submit button. That's how it has been when the submit was attached to the submit button(s)
		addEvent( document.getElementById("evo_login_form"), "submit", function(){
			// this.value = '<?php echo TS_('Please wait...') ?>';
				var form = document.getElementById('evo_login_form');

				// Calculate hashed password and set it in the form:
				if( form.pwd_hashed && form.pwd && form.pwd_salt && typeof hex_sha1 != "undefined" && typeof hex_md5 != "undefined" )
				{
					// We first hash to md5, because that's how the passwords are stored in the database
					// We then hash with the salt using SHA1 (fp> can't we do that with md5 again, in order to load 1 less Javascript library?)
					// NOTE: MD5 is kind of "weak" and therefor we also use SHA1
					form.pwd_hashed.value = hex_sha1( hex_md5(form.pwd.value) + form.pwd_salt.value );
					form.pwd.value = "padding_padding_padding_padding_padding_padding_hashed_<?php echo $Session->ID /* to detect cookie problems */ ?>";
					// (paddings to make it look like encryption on screen. When the string changes to just one more or one less *, it looks like the browser is changing the password on the fly)
				}
				return true;
			}, false );
		<?php
	}
	?>
</script>


<div class="login_actions" style="text-align:right">
	<?php
	echo get_user_register_link( '', '', T_('No account yet? Register here').' &raquo;', '#', true /*disp_when_logged_in*/, $redirect_to, 'login form' );
	?>
</div>


<?php
require dirname(__FILE__).'/_html_footer.inc.php';


/*
 * $Log$
 * Revision 1.28  2011/09/08 23:29:27  fplanque
 * More blockcache/widget fixes around login/register links.
 *

 * Revision 1.27  2011/09/07 23:34:09  fplanque
 * i18n update
 *
 * Revision 1.26  2011/09/07 22:44:41  fplanque
 * UI cleanup
 *
 * Revision 1.21.6.3  2011/09/06 21:08:18  sam2kb
 * MFH
 *
 * Revision 1.21.6.2  2011/09/04 22:13:57  fplanque
 * copyright 2011
 *
 * Revision 1.21.6.1  2011/09/04 21:31:48  fplanque
 * minor MFH
 *
 * Revision 1.23  2011/08/29 09:32:22  efy-james
 * Add ip on login form
 *
 * Revision 1.22  2011/08/26 03:01:54  efy-james
 * Add IP on login form
 *
 * Revision 1.21  2010/05/15 22:19:22  blueyed
 * login form: bypass all params (not being used in the form). Also, use REQUEST instead of POST.
 *
 * Revision 1.20  2010/02/08 17:56:56  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.19  2010/01/30 18:55:39  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.18  2010/01/03 13:45:37  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.17  2009/12/14 19:47:27  blueyed
 * Save core.pwd_salt directly, so login does not fail, if there is a fatal error later on (e.g. when logging the hit).
 *
 * Revision 1.16  2009/12/04 23:27:50  fplanque
 * cleanup Expires: header handling
 *
 * Revision 1.15  2009/11/30 22:12:21  blueyed
 * Use remove_magic_quotes when reusing POST in form.
 *
 * Revision 1.14  2009/09/16 00:25:41  fplanque
 * rollback of stuff that doesn't make any sense at all!!!
 *
 * Revision 1.12  2009/06/12 18:45:08  blueyed
 * Make login form more beautiful, by not using an inner border.
 *
 * Revision 1.11  2009/03/08 23:58:01  fplanque
 * 2009
 *
 * Revision 1.10  2008/04/24 02:01:22  fplanque
 * opera fix
 *
 * Revision 1.9  2008/02/13 11:34:45  blueyed
 * Explicitly call jQuery(), not the shortcut ($())
 *
 * Revision 1.8  2008/01/21 09:35:43  fplanque
 * (c) 2008
 *
 * Revision 1.7  2007/12/23 20:10:49  fplanque
 * removed suspects
 *
 * Revision 1.6  2007/12/10 01:22:04  blueyed
 * Pass on redirect_to param from login form through the register... link to the register form.
 * get_user_register_link: added $redirect param for injection
 *
 * Revision 1.5  2007/12/10 01:04:30  blueyed
 * - Properly implode action links
 * - Provide logout link, if the user is logged in already
 *
 * Revision 1.4  2007/12/09 22:59:22  blueyed
 * login and register form: Use Form::buttons_input for buttons
 *
 * Revision 1.3  2007/11/24 21:25:40  fplanque
 * make password encryption look like encryption
 *
 * Revision 1.2  2007/06/30 22:03:34  fplanque
 * cleanup
 *
 * Revision 1.1  2007/06/25 11:02:37  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.45  2007/06/19 22:50:41  blueyed
 * todo
 *
 * Revision 1.44  2007/04/26 00:11:10  fplanque
 * (c) 2007
 *
 * Revision 1.43  2007/01/25 21:55:02  blueyed
 * Only display "&middot;" if text follows with the links in the bottom right
 *
 * Revision 1.42  2007/01/20 01:44:56  blueyed
 * todo
 *
 * Revision 1.41  2007/01/19 03:06:56  fplanque
 * Changed many little thinsg in the login procedure.
 * There may be new bugs, sorry. I tested this for several hours though.
 * More refactoring to be done.
 *
 * Revision 1.40  2007/01/18 23:59:29  fplanque
 * Re: Secunia. Proper sanitization.
 *
 * Revision 1.38  2007/01/18 18:50:12  blueyed
 * Escape $redirect_to in "Bypass login..." link. Fixes http://secunia.com/cve_reference/CVE-2007-0175/
 *
 * Revision 1.37  2007/01/14 21:18:48  fplanque
 * bugfix
 *
 * Revision 1.36  2006/12/28 19:15:42  fplanque
 * bugfix: don't lose redirect_to on repeated login failures
 *
 * Revision 1.35  2006/12/28 15:44:30  fplanque
 * login refactoring / simplified
 *
 * Revision 1.34  2006/12/22 20:11:02  blueyed
 * todo, doc, cleanup
 *
 * Revision 1.33  2006/12/15 22:54:14  fplanque
 * allow disabling of password hashing
 *
 * Revision 1.32  2006/12/09 01:55:36  fplanque
 * feel free to fill in some missing notes
 * hint: "login" does not need a note! :P
 *
 * Revision 1.31  2006/12/06 23:32:35  fplanque
 * Rollback to Daniel's most reliable password hashing design. (which is not the last one)
 * This not only strengthens the login by providing less failure points, it also:
 * - Fixes the login in IE7
 * - Removes the double "do you want to memorize this password' in FF.
 *
 * Revision 1.30  2006/12/06 23:25:32  blueyed
 * Fixed bookmarklet plugins (props Danny); removed unneeded bookmarklet handling in core
 *
 * Revision 1.29  2006/12/05 01:41:22  blueyed
 * Removed markers, as requested
 *
 * Revision 1.28  2006/12/04 20:51:39  blueyed
 * Use TS_() for JS strings
 *
 * Revision 1.27  2006/12/04 00:18:52  fplanque
 * keeping the login hashing
 *
 * Revision 1.24  2006/12/03 20:11:18  fplanque
 * Not releasable. Discussion by email.
 *
 * Revision 1.23  2006/11/29 20:04:35  blueyed
 * More cleanup for login-password hashing
 *
 * Revision 1.22  2006/11/29 03:25:54  blueyed
 * Enhanced password hashing during login: get the password salt through async request + cleanup
 *
 * Revision 1.21  2006/11/28 02:52:26  fplanque
 * doc
 *
 * Revision 1.20  2006/11/24 18:27:26  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.19  2006/11/18 02:51:47  blueyed
 * Use only one "password lost?" variant
 *
 * Revision 1.18  2006/10/23 22:19:03  blueyed
 * Fixed/unified encoding of redirect_to param. Use just rawurlencode() and no funky &amp; replacements
 *
 * Revision 1.17  2006/10/17 19:54:39  blueyed
 * Select pwd input by JS, if theres a login already given.
 *
 * Revision 1.16  2006/10/15 21:30:46  blueyed
 * Use url_rel_to_same_host() for redirect_to params.
 *
 * Revision 1.15  2006/10/14 16:27:05  blueyed
 * Client-side password hashing in the login form.
 *
 * Revision 1.14  2006/10/12 23:48:15  blueyed
 * Fix for if redirect_to is relative
 *
 */
?>