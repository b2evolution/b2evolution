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

$need_raw_pwd = (bool)$Plugins->trigger_event_first_true('LoginAttemptNeedsRawPassword');

/**
 * Include page header (also displays Messages):
 */
$page_title = T_('Login form');
$page_icon = 'icon_login.gif';

// We include functions.js even if we don't need it. The login page is small. Let's use it as a preloader for the backoffice (which is awfully slow to initialize)
// fp> TODO: find a javascript way to preload more stuff (like icons) WITHOUT delaying the browser autocomplete of the login & password fields
	/* dh>
	$(function(){
	 alert("Document is ready");
	});
	See also http://www.texotela.co.uk/code/jquery/preload/ - might be a good opportunity to take a look at jQuery for you.. :)
	*/
$evo_html_headlines[] = '<script type="text/javascript" src="'.$rsc_url.'js/functions.js"></script>';

// include jquery JS:
$evo_html_headlines[] = '<script type="text/javascript" src="'.$rsc_url.'js/'.($debug ? 'jquery.js' : 'jquery.min.js').'"></script>';

if( ! $need_raw_pwd )
{ // Include JS for client-side password hashing:
	$evo_html_headlines[] = '<script type="text/javascript" src="'.$rsc_url.'js/md5.js"></script>';
	$evo_html_headlines[] = '<script type="text/javascript" src="'.$rsc_url.'js/sha1.js"></script>';
}

require dirname(__FILE__).'/_header.php';


// The login form has to point back to itself, in case $htsrv_url_sensitive is a "https" link and $redirect_to is not!
$Form = & new Form( $htsrv_url_sensitive.'login.php', 'evo_login_form', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

	$Form->hiddens_by_key( $_POST, /* exclude: */ array('login_action', 'login') ); // passthrough POSTed data (when login is required after having POSTed something)
	$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );

	if( isset( $action, $reqID, $sessID ) && $action == 'validatemail' )
	{ // the user clicked the link from the "validate your account" email, but has not been logged in; pass on the relevant data:
		$Form->hidden( 'action', 'validatemail' );
		$Form->hidden( 'reqID', $reqID );
		$Form->hidden( 'sessID', $sessID );
	}

// fp>SUSPECT
	if( ! $need_raw_pwd )
	{ // used by JS-password encryption/hashing (gets filled by JS AJAX callback):
		$Form->hidden( 'pwd_salt', '' );
		$Form->hidden( 'pwd_hashed', '' );
	}
// SUSPECT<fp

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

	if( strpos( $redirect_to, $admin_url ) !== 0
		&& strpos( $ReqHost.$redirect_to, $admin_url ) !== 0 // if $redirect_to is relative
		&& ! is_admin_page() )
	{ // provide button to log straight into backoffice, if we would not go there anyway
		$Form->submit( array( 'login_action[redirect_to_backoffice]', T_('Log into backoffice!'), 'search' ) );
	}
	echo $Form->inputend;
	echo $Form->fieldend;

	echo $Form->fieldend;
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
// fp>SUSPECT
	if( ! $need_raw_pwd )
	{
		/*
		 Password hashing with JavaScript, using a AJAX callback to get a fresh, unique hash.
		 1. Hook "onsubmit" of each "submit" input
		 2. onclick: AJAX call to get a unique hash (which gets stored into $Session)
		 3a. Hash the password (by using the salt)
		 3b. In case of error, do not hash the password
		 4. Click() the same button again, but this time the salt field is filled already
		*/
		// fp> Something will cause FF2 to ask twice about "do you want to memorize this password" :(
		?>

		$("#evo_login_form :submit").each( function() 
		{
			$(this).bind( 'click', function() 
			{
				// fp>If a true geek could obfuscate his code by using less than ONE char for each var name, he would!
				// the form:
				var f = $("#evo_login_form").get(0);

				if( f.pwd_salt.value.length > 0 || f.pwd_salt.value == "no_hashing_because_of_no_salt" )
				{ // inner click():
					// Calculate hashed password and set it in the form:
					var h = f.pwd_hashed;
					var p = f.pwd;
					var s = f.pwd_salt;
					if( h && p && s && typeof hex_sha1 != "undefined" && typeof hex_md5 != "undefined" )
					{
						// fp> do we really need sha1 AND md5? Looks really overkill to me.
						h.value = hex_sha1( hex_md5(p.value) + s.value );
						p.value = ""; // unset real password. 
						s.value = ""; // unset salt, so it gets re-newed when using the browser's back button
					}
					// Submit the form:
					return true;
				}

				// we need the original Input element later:
				var oInput = this;

				// Disable all submit elements:
				oInput.value = '<?php echo TS_('Please wait...') ?>';
				$("#evo_login_form :submit").attr("disabled", true);

				// get the Password hash by AJAX:
				$.ajax( 
					{	
						url: '<?php echo url_rel_to_same_host($htsrv_url_sensitive, $ReqHost) ?>async.php',
						
						data: { action: 'get_login_salt' },
						
						timeout: 10000, // 10sec timeout
						
						success: function(r, status) 
						{ // Set hidden pwd_salt field in form:
							f.pwd_salt.value = r;
						},
						
						error: function( xml, error ) 
						{ /*
							  In case the request fails, we send the password unencrypted!
								(instead of bothering the user with a confirm(), allowing him to cancel plain-text submission).
								It should not happen anyway.. 
								fp> The space shuttle should never have failed either...
							*/
							f.pwd_salt.value = "no_hashing_because_of_no_salt";
						},
						
						complete: function(xml, status) 
						{ // Enable all submit elements again:
							$("#evo_login_form :submit").removeAttr("disabled");
							oInput.focus(); // workaround for FF 2.0 bug - it would ignore click() otherwise, but it's "quite nice" anyway.. (btw: an "alert(oInput)" would also workaround this)
							oInput.click();
						}
						
					} 
				);

				// We submit the form through oInput.click(), in the "complete" AJAX callback:
				return false;
			} ) 
		} );
		<?php
	}
// <fp
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
		?>"><?php echo T_('Lost password ?')
		?></a>

	<?php
	if( empty($login_required) )
	{ // No login required, allow to pass through
		// TODO: dh> validate redirect_to param?!
		echo '<a href="'.url_rel_to_same_host($redirect_to, $ReqHost).'">'./* Gets displayed as link to the location on the login form if no login is required */ T_('Bypass login...').'</a>';
	}
	?>
</div>


<?php
require dirname(__FILE__).'/_footer.php';


/*
 * $Log$
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
 * SUSPECT code. Not releasable. Discussion by email.
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
