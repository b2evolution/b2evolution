<?php
/**
 * This is sent to a ((User)) when he needs to activate his account. Typically includes an activation link.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @version $Id: account_activate.txt.php 7043 2014-07-02 08:35:45Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.txt.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

/**
 * @var Session
 */
global $Session;
/**
 * @var GeneralSettings
 */
global $Settings;

global $secure_htsrv_url, $htsrv_url, $baseurl;

// Default params:
$params = array_merge( array(
		'locale'                    => '',
		'status'                    => '',
		'blog_param'                => '',
		'request_id'                => '',
		'reminder_key'              => '',
		'already_received_messages' => '',
	), $params );

switch( $params['status'] )
{
	case 'new':
		echo sprintf( T_( 'You have recently registered a new account on %s .' ), $baseurl );
		echo "\n\n".T_( 'You must activate this account by clicking below in order to be able to use all the site features.' );
		$activation_text = T_( 'Please activate this account by clicking on the following link:' );
		break;
	case 'emailchanged':
		echo sprintf( T_( 'You have recently changed the email address associated with your account on %s .' ), $baseurl );
		echo "\n\n".T_( 'You must reactivate this account by clicking below in order to continue to use all the site features.' );
		$activation_text = T_( 'Please reactivate this account by clicking on the following link:' );
		break;
	case 'deactivated':
		echo sprintf( T_( 'Your account on %s needs to be reactivated.' ), $baseurl );
		echo "\n\n".T_( 'You must reactivate this account by clicking below in order to continue to use all the site features.' );
		$activation_text = T_( 'Please reactivate this account by clicking on the following link:' );
		break;
	default:
		echo sprintf( T_( 'Someone -- presumably you -- has registered an account on %s with your email address.' ), $baseurl );
		echo "\n\n".T_( 'You must activate this account by clicking below in order to be able to use all the site features.' );
		$activation_text = T_( 'Please activate this account by clicking on the following link:' );
		break;
}

echo "\n\n";
echo T_('Your login is: $login$')."\n";
echo T_('Your email is: $email$');
echo "\n\n";

if( $Settings->get( 'validation_process' ) == 'easy' )
{ // ---- EASY activation ---- //
	$activation_url = $htsrv_url.'login.php?action=activateaccount'
		.'&userID=$user_ID$'
		.'&reminderKey='.$params['reminder_key'];

	echo $activation_text."\n".$activation_url;

	if( !empty( $params['already_received_messages'] ) )
	{ // add already received message list to email body
		echo "\n\n".T_( 'You have received private messages in the following conversations, but your account must be activated before you can read them:' )."\n";
		echo $params['already_received_messages'];
	}
}
else
{ // ---- SECURE activation ---- //
	$activation_url = $secure_htsrv_url.'login.php?action=validatemail'
		.$params['blog_param']
		.'&reqID='.$params['request_id']
		.'&sessID='.$Session->ID; // used to detect cookie problems

	echo $activation_text."\n".$activation_url;
	echo "\n\n";

	echo T_('If this does not work, please copy/paste that link into the address bar of your browser.');
	echo "\n\n";

	echo sprintf( T_('We also recommend that you add %s to your contacts in order to make sure you will receive future notifications, especially when someone sends you a private message.'), $Settings->get( 'notification_sender_email' ) );
	echo "\n\n-- \n";

	echo T_('Please note:').' '.T_('For security reasons the link is only valid for your current session (by means of your session cookie).');
}

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive notifications to activate your account any more, click here:' ).' '.
		$htsrv_url.'quick_unsubscribe.php?type=account_activation&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>