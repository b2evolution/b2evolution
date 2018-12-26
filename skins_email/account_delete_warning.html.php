<?php
/**
 * This is sent to a ((User)) with delete warning when he needs to activate his account. Typically includes an activation link.
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

global $baseurl;

// Default params:
$params = array_merge( array(
		'locale'       => '',
		'blog_param'   => '',
		'request_id'   => '',
		'reminder_key' => '',
	), $params );

$baseurl_link = '<a href="'.$baseurl.'"'.emailskin_style( '.a' ).'>'.$Settings->get( 'notification_short_name' ).'</a>';

echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_('You have registered on %s but you have not activated your account yet.'), $baseurl_link ).'</p>';

$activate_account_reminder_config = $Settings->get( 'activate_account_reminder_config' );
$delete_account_after_period = $activate_account_reminder_config[ count( $activate_account_reminder_config ) - 1 ];
echo '<p'.emailskin_style( '.p' ).'><b'.emailskin_style( '.important' ).'>'.sprintf( T_('If you do not activate your account now, your account and all its contents will be deleted in %s.'), seconds_to_period( $delete_account_after_period ) )."</b></p>\n";

echo '<p'.emailskin_style( '.p' ).'>'.T_('Your login is: $login$')."</p>\n";
echo '<p'.emailskin_style( '.p' ).'>'.T_('Your email is: $email$')."</p>\n";

$activation_text = T_('Activate NOW').'!';
if( $Settings->get( 'validation_process' ) == 'easy' )
{ // ---- EASY activation ---- //
	$activation_url = get_htsrv_url().'login.php?action=activateacc_ez'
		.'&userID=$user_ID$'
		.'&reminderKey='.$params['reminder_key'];

	echo '<div'.emailskin_style( 'div.buttons' ).'>'."\n".get_link_tag( $activation_url, $activation_text, 'div.buttons a+a.btn-primary' )."</div>\n";
}
else
{ // ---- SECURE activation ---- //
	$activation_url = get_htsrv_url( 'login' ).'login.php?action=activateacc_sec'
		.$params['blog_param']
		.'&reqID='.$params['request_id']
		.'&sessID='.$Session->ID; // used to detect cookie problems

	echo '<div'.emailskin_style( 'div.buttons' ).'>'."\n".get_link_tag( $activation_url, $activation_text, 'div.buttons a+a.btn-primary' )."\n</div>\n";

// TODO: check why this appears only in secure mode?
	echo '<p'.emailskin_style( '.p' ).'>'.T_('If this does not work, please copy/paste that link into the address bar of your browser.')."</p>\n";

// TODO: check why this appears only in secure mode?  (this should probably appear only in first notification, no matter if it's secure or not)
	echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_('We also recommend that you add %s to your contacts in order to make sure you will receive future notifications, especially when someone sends you a private message.'), $Settings->get( 'notification_sender_email' ) )."</p>\n";

	// Note about secure mode:
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