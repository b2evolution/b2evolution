<?php
/**
 * This is sent to a ((User)) when he is inactive for an extended period of time.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.html.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

/**
 * @var Session
 */
global $Session;
/**
 * @var GeneralSettings
 */
global $Settings;

global $baseurl, $admin_url, $dummy_fields, $Blog;

// Default params:
$params = array_merge( array(
		'User'       => NULL,
		'login_Blog' => NULL,
	), $params );

$inactive_User = $params['User'];
$login_Blog = $params['login_Blog'];

echo '<p'.emailskin_style( '.p' ).'>';
echo sprintf( T_('We haven\'t seen you on <a %s>%s</a> for %s.'), 'href="'.$baseurl.'"'.emailskin_style( '.a' ), $Settings->get( 'notification_short_name' ), seconds_to_period( $Settings->get( 'inactive_account_reminder_threshold' ) ) );
echo '</p>';
echo '<p'.emailskin_style( '.p' ).'>';
echo T_('Check out what\'s new by clicking below.');
echo '</p>';

if( ! empty( $login_Blog ) )
{ // Use special blog for login/lost password actions if it is defined
	$login_url = $login_Blog->get( 'loginurl', array( 'glue' => '&' ) );
	$lostpassword_url = $login_Blog->get( 'lostpasswordurl', array( 'glue' => '&' ) );
}
else
{ // Use standard login/lost password urls
	$login_url = get_htsrv_url( true ).'login.php';
	$lostpassword_url = get_htsrv_url( true ).'login.php?action=lostpassword';
}

// Buttons:
echo '<div'.emailskin_style( 'div.buttons' ).'>'."\n";
echo get_link_tag( url_add_param( $login_url, $dummy_fields['login'].'='.format_to_output( $inactive_User->login, 'urlencoded' ) ), T_('Log in now!'), 'div.buttons a+a.btn-primary' )."\n";
echo get_link_tag( url_add_param( $lostpassword_url, $dummy_fields['login'].'='.format_to_output( $inactive_User->email, 'urlencoded' ) ), T_('Lost password?'), 'div.buttons a+a.btn-primary' )."\n";
echo "</div>\n";

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive notifications when you have been inactive for an extended period of time, click here:' )
		.' <a href="'.get_htsrv_url().'quick_unsubscribe.php?type=account_inactive&user_ID=$user_ID$&key=$unsubscribe_key$"'.emailskin_style( '.a' ).'>'
		.T_('instant unsubscribe').'</a>.';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>