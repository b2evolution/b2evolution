<?php
/**
 * This is the HTML template of email message for password change request
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Session
 */
global $Session;

global $secure_htsrv_url, $dummy_fields;

// Default params:
$params = array_merge( array(
		'UserCache'      => NULL,
		'user_count'     => '',
		'request_id'     => '',
		'blog_param'     => '',
	), $params );


$UserCache = $params['UserCache'];

$message_content = '';
// Iterate through the User Cache
while( ( $iterator_User = & $UserCache->get_next() ) != NULL )
{
	$message_content .= '<br />'.T_( 'Login:' ).' '.$iterator_User->get_colored_login( array( 'mask' => '$avatar$ $login$' ) ).'<br />';
	if( $params['user_count'] > 1 )
	{ // exists more account with the given email address, display last used date for each
		$user_lastseen_ts = $iterator_User->get( 'lastseen_ts' );
		if( empty( $user_lastseen_ts ) )
		{ // user has never logged in
			$message_content .=  T_( 'Never used.' )."\n";
		}
		else
		{
			$message_content .= T_( 'Last used on' ).': '.format_to_output( mysql2localedatetime( $user_lastseen_ts ) )."\n";
		}
	}
	$url_change_password = $secure_htsrv_url.'login.php?action=changepwd'
		.'&'.$dummy_fields[ 'login' ].'='.rawurlencode( $iterator_User->login )
		.'&reqID='.$params['request_id']
		.'&sessID='.$Session->ID  // used to detect cookie problems
		.$params['blog_param'];
	$message_content .= T_( 'Link to change your password:' )
						.'<br />'
						.get_link_tag( $url_change_password ).'</a>'
						.'<br />';
}

if( $params['user_count'] > 1 )
{ // exists more account with the given email address
	$message_content = '<br />'.T_( 'It seems you have multiple accounts associated to this email address. Choose the one you want to use below.' )
						.'<br />'.$message_content;
	$message_note = T_( 'For security reasons the links are only valid for your current session (by means of your session cookie).' );
}
else
{
	$message_note = T_( 'For security reasons the link is only valid for your current session (by means of your session cookie).' );
}

echo T_( 'Somebody (presumably you) has requested a password change for your account.' );
echo '<br />';
echo $message_content;
echo '<br />-- <br />';
echo T_('Please note:').' '.$message_note;
echo '<br /><br />';

echo T_('If it was not you that requested this password change, simply ignore this mail.');
?>