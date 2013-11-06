<?php
/**
 * This is sent to ((SystemAdmins)) to notify them that a ((User)) account has been reported by another user.
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
		'login'          => '',
		'email'          => '',
		'report_status'  => '',
		'report_info'  => '',
		'user_ID'        => '',
		'reported_by'    => '', // Login of user who has reported this user account
	), $params );

echo sprintf( T_('A user account was reported by %s'), $params['reported_by'] );

echo "\n\n";

echo T_('Login').": ".$params['login']."\n";
echo T_('Email').": ".$params['email']."\n";
echo T_('Reported as').": ".$params['report_status']."\n";
echo T_('Extra info').": ".$params['report_info'];
echo "\n\n";

echo T_('Edit user').': '.$admin_url.'?ctrl=user&user_tab=admin&user_ID='.$params['user_ID'];

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notification when an account was reported, click here:' ).' '.
		$htsrv_url.'quick_unsubscribe.php?type=account_reported&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>