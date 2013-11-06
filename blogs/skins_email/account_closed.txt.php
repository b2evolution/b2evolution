<?php
/**
 * This is sent to ((SystemAdmins)) to notify them that a ((User)) account has been closed (either by the User themselves or by another Admin).
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.txt.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $admin_url, $htsrv_url;

// Default params:
$params = array_merge( array(
		'login'   => '',
		'email'   => '',
		'reason'  => '',
		'user_ID' => '',
		'closed_by_admin' => '',// Login of admin which closed current user account
	), $params );


if( empty( $params['closed_by_admin'] ) )
{	// Current user closed own account
	echo T_('A user account was closed!');
}
else
{	// Admin closed current user account
	printf( T_('A user account was closed by %s'), $params['closed_by_admin'] );
}
echo "\n\n";

echo T_('Login').": ".$params['login']."\n";
echo T_('Email').": ".$params['email']."\n";
echo T_('Account close reason').": ".$params['reason'];
echo "\n\n";

echo T_('Edit user').': '.$admin_url.'?ctrl=user&user_tab=profile&user_ID='.$params['user_ID'];

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notification when an account was closed, click here:' ).' '.
		$htsrv_url.'quick_unsubscribe.php?type=account_closed&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>
