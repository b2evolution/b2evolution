<?php
/**
 * This is sent to a ((User)) when he requested a password reset. Typically includes an link to access the password reset/change screen.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.txt.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

/**
 * @var Session
 */
global $Session;

global $dummy_fields;

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
	$message_content .= "\n".T_( 'Login:' ).' '.$iterator_User->dget( 'login' )."\n";

	if( $params['user_count'] > 1 )
	{ // exists more account with the given email address, display last used date for each
		$user_lastseen_ts = $iterator_User->get( 'lastseen_ts' );
		if( empty( $user_lastseen_ts ) )
		{ // user has never logged in
			$message_content .=  T_( 'Never used.' )."\n";
		}
		else
		{
			$message_content .= T_( 'Last used on' ).': '.format_to_output( mysql2localedatetime( $user_lastseen_ts ), 'text' )."\n";
		}
	}

	$message_content .= T_( 'Link to change your password:' )
						."\n"
						.'$secret_content_start$'
						.get_htsrv_url( true ).'login.php?action=changepwd'
							.'&'.$dummy_fields[ 'login' ].'='.rawurlencode( $iterator_User->login )
							.'&reqID='.$params['request_id']
							.'&sessID='.$Session->ID  // used to detect cookie problems
							.$params['blog_param']
						.'$secret_content_end$'
						."\n";
}

if( $params['user_count'] > 1 )
{ // exists more account with the given email address
	$message_content = "\n".T_( 'It seems you have multiple accounts associated to this email address. Choose the one you want to use below:' )
						."\n".$message_content;
	$message_note = T_( 'For security reasons the links are only valid for your current session (by means of your session cookie).' );
}
else
{
	$message_note = T_( 'For security reasons the link is only valid for your current session (by means of your session cookie).' );
}

echo T_( 'Somebody (presumably you) has requested a password change for your account.' );
echo "\n";
echo $message_content;
echo "\n-- \n";
echo T_('Please note:').' '.$message_note;
echo "\n\n";

echo T_('If you did not request this password change, simply ignore this email.');

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>