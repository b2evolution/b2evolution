<?php
/**
 * This is sent to a ((User)) when his account was created by admin.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.html.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $baseurl, $dummy_fields, $Settings;

// Default params:
$params = array_merge( array(
		'login'    => '',
		'password' => '',
	), $params );

// Initialize "lost password" URL:
$login_Blog = & get_setting_Blog( 'login_blog_ID' );
$lostpassword_url = empty( $login_Blog )
	? get_htsrv_url( 'login' ).'login.php?action=lostpassword'
	: $login_Blog->get( 'lostpasswordurl', array( 'glue' => '&' ) );

echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_('A new user account has been created for you on %s.'),
	'<a href="'.$baseurl.'"'.emailskin_style( '.a' ).'>'.$Settings->get( 'notification_short_name' ).'</a>' )."</p>\n";

echo '<p'.emailskin_style( '.p' ).'>'.T_('Your login is: $login$')."</p>\n";

if( empty( $params['password'] ) )
{	// No password:
	echo '<p'.emailskin_style( '.p' ).'>'.T_('For security reasons, you must initialize your account with a password before you can log in. (This is the same procedure as when you lose your password).')."</p>\n";
	echo '<div'.emailskin_style( 'div.buttons' ).'>'.get_link_tag( url_add_param( $lostpassword_url, $dummy_fields['login'].'='.$params['login'], '&' ), T_('Start password initialization now'), 'div.buttons a+a.btn-primary' )."</div>\n";
}
else
{	// Password was entered by admin:
	echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_('Your password is: %s'), $params['password'] )."</p>\n";
	echo '<p'.emailskin_style( '.p' ).'>'.T_('It is highly recommended that you change your password after you log in.')."</p>\n";
	echo '<div'.emailskin_style( 'div.buttons' ).'>'.get_link_tag( $lostpassword_url, T_('Change password now'), 'div.buttons a+a.btn-primary' )."</div>\n";
}

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>