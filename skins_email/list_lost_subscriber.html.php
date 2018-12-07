<?php
/**
 * This is sent to ((Admins)) to notify them that a new ((User)) account has been activated.
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

global $Settings, $UserSettings, $admin_url;

// Default params:
$params = array_merge( array(
		'subscribed_User' => NULL,
		'newsletters'     => array(),
		'usertags'        => '', // new user tags being set as part of the new subscription
		'unsubscribed_by_admin' => '', // Login of admin which unsubscribed the user
	), $params );


$subscribed_User = $params['subscribed_User'];

echo '<p'.emailskin_style( '.p' ).'>';
if( empty( $params['unsubscribed_by_admin'] ) )
{	// Current user unsubscribed:
	echo T_('A user unsubscribed from your list/s').':';
}
else
{	// Admin unsubscribed user:
	printf( T_('A user was unsubscribed from your list/s by %s').':', get_user_colored_login_link( $params['unsubscribed_by_admin'], array( 'use_style' => true, 'protocol' => 'http:', 'login_text' => 'name' ) ) ).':';
}
echo '</p>'."\n";

echo '<p'.emailskin_style( '.p' ).'>'./* TRANS: noun */ T_('Login').": ".get_user_colored_login_link( $subscribed_User->login, array( 'use_style' => true, 'protocol' => 'http:' ) )."</p>\n";
echo '<p'.emailskin_style( '.p' ).'>'.T_('Email').": ".$subscribed_User->email."</p>\n";

$fullname = $subscribed_User->get( 'fullname' );

if( $fullname != '' )
{ // Full name is defined
	echo '<p'.emailskin_style( '.p' ).'>'.T_('Full name').': '.$fullname."</p>\n";
}

// User's pictures:
echo '<p'.emailskin_style( '.p' ).'>'.T_('The current profile pictures for this account are:').'</p>'."\n";
$user_pictures = '';
$user_avatars = $subscribed_User->get_avatar_Links( false );
foreach( $user_avatars as $user_Link )
{
	$user_pictures .= $user_Link->get_tag( array(
			'before_image'        => '',
			'before_image_legend' => '',
			'after_image_legend'  => '',
			'after_image'         => ' ',
			'image_size'          => 'crop-top-80x80'
		) );
}
echo empty( $user_pictures ) ? '<p'.emailskin_style( '.p' ).'><b>'.T_('No pictures.').'</b></p>' : $user_pictures;

// List of newsletters the user subscribed to:
if( $params['newsletters'] )
{
	echo '<p'.emailskin_style( '.p' ).'>';
	echo T_('The user is now unsubscribed from the following list/s').':'."\n";
	echo '</p>'."\n";
	echo '<ol>'."\n";
	foreach( $params['newsletters'] as $newsletter )
	{
		echo '<li>'.$newsletter->get( 'name' ).'</li>'."\n";
	}
	echo '</ol>'."\n";
}

// List of user tags applied:
if( $params['usertags'] )
{
	$tags = explode( ',', $params['usertags'] );
	echo '<p'.emailskin_style( '.p' ).'>';
	echo T_('User tags set as part of the unsubscription').':'."\n";
	foreach( $tags as $tag )
	{
		echo '<span'.emailskin_style( '.label+.label-default' ).'>'.$tag.'</span>'."\n";
	}
	echo '</p>'."\n";
}

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notification when a user unsubscribes from one of your lists, click here:' )
			.' <a href="'.get_htsrv_url().'quick_unsubscribe.php?type=list_lost_subscriber&user_ID=$user_ID$&key=$unsubscribe_key$"'.emailskin_style( '.a' ).'>'
			.T_('instant unsubscribe').'</a>.';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>