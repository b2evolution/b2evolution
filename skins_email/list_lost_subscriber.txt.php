<?php
/**
 * This is sent to ((SystemAdmins)) to notify them that a new ((User)) account has been activated.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.txt.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $Settings, $UserSettings, $admin_url;

// Default params:
$params = array_merge( array(
		'subscribed_User' => NULL,
		'newsletters'     => array(),
		'usertags'        => '', // new user tags being set as part of the new subscription
		'unsubscribed_by_admin' => '', // Login of admin which unsubscribed the user
		'user_account_closed' => false, // unsubscribed because account was closed
	), $params );


$subscribed_User = $params['subscribed_User'];

if( empty( $params['unsubscribed_by_admin'] ) )
{	// Current user unsubscribed:
	echo T_('A user unsubscribed from your list/s').':';
}
else
{	// Admin unsubscribed user:
	printf( T_('A user was unsubscribed from your list/s by %s').':', $params['unsubscribed_by_admin'] ).':';
}
echo "\n\n";
echo /* TRANS: noun */ T_('Login').": ".$subscribed_User->login."\n";
echo T_('Email').": ".$subscribed_User->email."\n";

$fullname = $subscribed_User->get( 'fullname' );
if( $fullname != '' )
{	// First name is defined
	echo T_('Full name').": ".$fullname."\n";
}


// A count of user's pictures:
$user_pictures_count = count( $subscribed_User->get_avatar_Links( false ) );
echo "\n".sprintf( T_('The user has %s profile pictures.'), $user_pictures_count )."\n\n";

// List of newsletters the user subscribed to:
if( $params['newsletters'] )
{
	echo T_('The user is now unsubscribed from the following list/s').':'."\n";
	foreach( $params['newsletters'] as $newsletter )
	{
		echo "\t".'- '.$newsletter->get( 'name' )."\n";
	}
	echo "\n\n";
}

// List of user tags applied:
if( $params['usertags'] )
{
	$tags = explode( ',', $params['usertags'] );
	echo T_('User tags set as part of the unsubscription').':'."\n";
	echo implode( ', ', $tags )."\n\n";
}

// Account closure notice:
if( $params['user_account_closed'] )
{
	echo T_('The user was automatically unsubscribed due to account closure.')."\n\n";
}

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notification when a user unsubscribes from one of your lists, click here:' ).' '.
		get_htsrv_url().'quick_unsubscribe.php?type=list_lost_subscriber&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>