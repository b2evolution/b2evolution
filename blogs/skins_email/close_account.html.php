<?php
/**
 * This is the HTML template of email message for close user account
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
echo '<br /><br />';

echo T_('Login').": ".$params['login'].'<br />';
echo T_('Email').": ".$params['email'].'<br />';
echo T_('Account close reason').": ".nl2br( $params['reason'] );
echo '<br /><br />';

echo T_('Edit user').': '.get_link_tag( $admin_url.'?ctrl=user&user_tab=profile&user_ID='.$params['user_ID'] ).'<br />';
echo '<br />';
echo T_( 'If you don\'t want to receive any more notification when an account was closed, click here' ).': '
		.get_link_tag( $htsrv_url.'quick_unsubscribe.php?type=account_closed&user_ID=$user_ID$&key=$unsubscribe_key$' ).'<br />';
?>