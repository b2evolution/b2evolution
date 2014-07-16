<?php
/**
 * This is sent to a ((User)) when he requested a password reset. Typically includes an link to access the password reset/change screen.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @version $Id: account_password_reset.html.php 7043 2014-07-02 08:35:45Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.html.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

/**
 * @var Session
 */
global $Session;

global $secure_htsrv_url, $dummy_fields;

// Default params:
$params = array_merge( array(
		'user_count'     => '',
		'request_id'     => '',
		'blog_param'     => '',
	), $params );


$UserCache = & get_UserCache();

$message_content = '';
// Iterate through the User Cache
while( ( $iterator_User = & $UserCache->get_next() ) != NULL )
{
	// Note: we don't want to display the avatar in this specific case.
	if( $params['user_count'] > 1 )
	{ // Several accounts with the given email address, display last used date for each
		$message_content .= '<div style="margin: 1em 0; border: 1px solid #ccc; border-radius: 4px; padding: 1em 1em 1ex;">';

		$message_content .= '<p>'.T_( 'Login:' ).' '.$iterator_User->get_colored_login( array( 'mask' => '$login$' ) )."</p>\n";
		$user_lastseen_ts = $iterator_User->get( 'lastseen_ts' );
		if( empty( $user_lastseen_ts ) )
		{ // user has never logged in
			$message_content .=  T_( 'Never used.' )."\n";
		}
		else
		{
			$message_content .= T_( 'Last used on' ).': <b>'.format_to_output( mysql2localedatetime( $user_lastseen_ts ) )."</b>\n";
		}
	}
	else
	{
		$message_content .= '<p>'.T_( 'Login:' ).' '.$iterator_User->get_colored_login( array( 'mask' => '$login$' ) )."</p>\n";
	}

	$url_change_password = $secure_htsrv_url.'login.php?action=changepwd'
		.'&'.$dummy_fields[ 'login' ].'='.rawurlencode( $iterator_User->login )
		.'&reqID='.$params['request_id']
		.'&sessID='.$Session->ID  // used to detect cookie problems
		.$params['blog_param'];

	// Restrict the password change url to be saved in the email logs
	$url_change_password = '$secret_content_start$'.$url_change_password.'$secret_content_end$';

	// Buttons:
	$message_content .= '<div class="buttons">'."\n";
	$message_content .= get_link_tag( $url_change_password, T_( 'Change your password NOW' ), 'button_yellow' )."\n";
	$message_content .= "</div>\n";

	if( $params['user_count'] > 1 )
	{ // Several accounts with the given email address, display last used date for each
		$message_content .= '</div>';
	}
}

if( $params['user_count'] > 1 )
{ // exists more account with the given email address
	$message_content = '<p>'.T_( 'It seems you have multiple accounts associated to this email address. Choose the one you want to use below:' ).'</p>'.$message_content;

	$message_note = T_( 'For security reasons the links are only valid for your current session (by means of your session cookie).' );
}
else
{
	$message_note = T_( 'For security reasons the link is only valid for your current session (by means of your session cookie).' );
}

echo '<p>'.T_( 'Somebody (presumably you) has requested a password change for your account.' )."</p>\n";

echo $message_content;

echo '<p class="note">'.T_('Please note:').' '.$message_note."</p>\n";

echo '<p><i class="note">'.T_('If you did not request this password change, simply ignore this mail.').'</i></p>';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>