<?php
/**
 * This is sent to a ((User)) when his account was created by admin.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.txt.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $baseurl, $dummy_fields;

// Default params:
$params = array_merge( array(
		'login'    => '',
		'password' => '',
	), $params );

// Get collection for login actions or first detected collection from DB:
$login_Blog = & get_setting_Blog( 'login_blog_ID', NULL, false, false, true );

echo sprintf( T_('A new user account has been created for you on %s.'), $baseurl )."\n\n";

echo sprintf( T_('Your username is: %s'), $params['login'] )."\n\n";

if( empty( $params['password'] ) )
{	// No password:
	$lostpassword_url = empty( $login_Blog )
		? get_htsrv_url( 'login' ).'login.php?action=lostpassword'
		: $login_Blog->get( 'lostpasswordurl', array( 'glue' => '&' ) );
	echo T_('For security reasons, you must initialize your account with a password before you can log in. (This is the same procedure as when you lose your password).')."\n\n";
	echo T_('Start password initialization now').":\n"
		.url_add_param( $lostpassword_url, $dummy_fields['login'].'='.$params['login'], '&' );
}
else
{	// Password was entered by admin:
	if( empty( $login_Blog ) )
	{	// Use standard login forms when collection is not detected for this:
		$login_url = get_htsrv_url( 'login' ).'login.php';
		$UserCache = & get_UserCache();
		if( ( $User = & $UserCache->get_by_login( $params['login'] ) ) &&
		    $User->check_perm( 'admin', 'restricted' ) )
		{	// Allow URL to change password from back-office when the user has an access to back-office:
			$changepwd_url = $admin_url.'?ctrl=user&user_tab=pwdchange';
		}
	}
	else
	{	// Use collection for login actions:
		$login_url = $login_Blog->get( 'loginurl', array( 'glue' => '&' ) );
		$changepwd_url = $login_Blog->get( 'pwdchangeurl', array( 'glue' => '&' ) );
	}
	if( ! empty( $changepwd_url ) )
	{	// Redirect to page to change password after login:
		$login_url = url_add_param( $login_url, 'redirect_to='.rawurlencode( $changepwd_url ).'&return_to='.rawurlencode( $changepwd_url ), '&' );
	}
	echo sprintf( T_('Your password is: %s'), $params['password'] )."\n\n";
	echo T_('It is highly recommended that you change your password after you log in.')."\n\n";
	echo T_('Change password now').":\n"
		.$login_url;
}

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>