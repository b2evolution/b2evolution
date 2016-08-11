<?php
/**
 * This is sent to a ((User)) when he needs to activate his account. Typically includes an activation link.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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

global $baseurl;

// Default params:
$params = array_merge( array(
		'locale'                    => '',
		'status'                    => '',
		'blog_param'                => '',
		'request_id'                => '',
		'reminder_key'              => '',
		'already_received_messages' => '',
	), $params );

$baseurl_link = '<a href="'.$baseurl.'"'.emailskin_style( '.a' ).'>'.$Settings->get( 'notification_short_name' ).'</a>';

switch( $params['status'] )
{
	case 'new':
		echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_( 'You have recently registered a new account on %s .' ), $baseurl_link ).'</p>';
		echo '<p'.emailskin_style( '.p' ).'><b'.emailskin_style( '.important' ).'>'.T_( 'You must activate this account by clicking below in order to be able to use all the site features.' ).'</b></p>';
		$activation_text = T_( 'Activate NOW' );
		break;
	case 'emailchanged':
		echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_( 'You have recently changed the email address associated with your account on %s .' ), $baseurl_link ).'</p>';
		echo '<p'.emailskin_style( '.p' ).'><b'.emailskin_style( '.important' ).'>'.T_( 'You must reactivate this account by clicking below in order to continue to use all the site features.' ).'</b></p>';
		$activation_text = T_( 'Reactivate NOW' );
		break;
	case 'deactivated':
		echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_( 'Your account on %s needs to be reactivated.' ), $baseurl_link ).'</p>';
		echo '<p'.emailskin_style( '.p' ).'><b'.emailskin_style( '.important' ).'>'.T_( 'You must reactivate this account by clicking below in order to continue to use all the site features.' ).'</b></p>';
		$activation_text = T_( 'Reactivate NOW' );
		break;
	default:
		echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_( 'Someone -- presumably you -- has registered an account on %s with your email address.' ), $baseurl_link ).'</p>';
		echo '<p'.emailskin_style( '.p' ).'><b'.emailskin_style( '.important' ).'>'.T_( 'You must activate this account by clicking below in order to be able to use all the site features.' ).'</b></p>';
		$activation_text = T_( 'Activate NOW' );
		break;
}
echo "\n";

echo '<p'.emailskin_style( '.p' ).'>'.T_('Your login is: $login$')."</p>\n";
echo '<p'.emailskin_style( '.p' ).'>'.T_('Your email is: $email$')."</p>\n";

if( $Settings->get( 'validation_process' ) == 'easy' )
{ // ---- EASY activation ---- //
	$activation_url = get_htsrv_url().'login.php?action=activateaccount'
		.'&userID=$user_ID$'
		.'&reminderKey='.$params['reminder_key'];

	echo '<div'.emailskin_style( 'div.buttons' ).'>'."\n".get_link_tag( $activation_url, $activation_text, 'div.buttons a+a.button_green' )."</div>\n";

	if( !empty( $params['already_received_messages'] ) )
	{ // add already received message list to email body
		echo '<p'.emailskin_style( '.p' ).'>'.T_( 'You have received private messages in the following conversations, but your account must be activated before you can read them:' )."</p>\n";
		echo '<p'.emailskin_style( '.p' ).'>'.$params['already_received_messages']."</p>\n";
	}
}
else
{ // ---- SECURE activation ---- //
	$activation_url = get_htsrv_url( true ).'login.php?action=validatemail'
		.$params['blog_param']
		.'&reqID='.$params['request_id']
		.'&sessID='.$Session->ID; // used to detect cookie problems

	echo '<div'.emailskin_style( 'div.buttons' ).'>'."\n".get_link_tag( $activation_url, $activation_text, 'div.buttons a+a.button_green' )."\n</div>\n";

	echo '<p'.emailskin_style( '.p' ).'>'.T_('If this does not work, please copy/paste that link into the address bar of your browser.')."</p>\n";

	echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_('We also recommend that you add %s to your contacts in order to make sure you will receive future notifications, especially when someone sends you a private message.'), $Settings->get( 'notification_sender_email' ) )."</p>\n";

	echo '<p'.emailskin_style( '.p+.note' ).'>'.T_('Please note:').' '.T_('For security reasons the link is only valid for your current session (by means of your session cookie).')."</p>\n";
}

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive notifications to activate your account any more, click here:' )
			.' <a href="'.get_htsrv_url().'quick_unsubscribe.php?type=account_activation&user_ID=$user_ID$&key=$unsubscribe_key$"'.emailskin_style( '.a' ).'>'
			.T_('instant unsubscribe').'</a>.';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>