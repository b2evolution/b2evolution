<?php
/**
 * This is sent to ((SystemAdmins)) to notify them that a ((User)) account has been closed (either by the User themselves or by another Admin).
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

global $admin_url;

// Default params:
$params = array_merge( array(
		'login'   => '',
		'email'   => '',
		'reason'  => '',
		'user_ID' => '',
		'closed_by_admin' => '',// Login of admin which closed current user account
		'days_count' => 0
	), $params );


if( empty( $params['closed_by_admin'] ) )
{	// Current user closed own account
	printf( T_('A user account was closed %s days after creation.'), $params['days_count'] );
}
else
{	// Admin closed current user account
	printf( T_('A user account was closed %s days after creation by %s'), $params['days_count'], $params['closed_by_admin'] );
}
echo "\n\n";

echo T_('Login').": ".$params['login']."\n";
echo T_('Email').": ".$params['email']."\n";
echo T_('Account close reason').": ".$params['reason'];
echo "\n\n";

// A count of user's pictures:
$user_pictures_count = 0;
$UserCache = & get_UserCache();
if( $User = $UserCache->get_by_ID( $params['user_ID'], false, false ) )
{
	$user_pictures_count = count( $User->get_avatar_Links( false ) );
}
echo sprintf( T_('The user has %s profile pictures.'), $user_pictures_count )."\n\n";

echo T_('Edit user').': '.$admin_url.'?ctrl=user&user_tab=profile&user_ID='.$params['user_ID'];

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notification when an account was closed, click here:' ).' '.
		get_htsrv_url().'quick_unsubscribe.php?type=account_closed&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>
