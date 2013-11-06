<?php
/**
 * This is the HTML template of email message for validate user account (Secure mode)
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
/**
 * @var GeneralSettings
 */
global $Settings;

global $secure_htsrv_url;

// Default params:
$params = array_merge( array(
		'locale'     => '',
		'status'     => '',
		'blog_param' => '',
		'request_id' => '',
	), $params );


echo get_validate_email_message( $params['locale'], $params['status'], 'html' );

$url_validate_mail = $secure_htsrv_url.'login.php?action=validatemail'
	.$params['blog_param']
	.'&reqID='.$params['request_id']
	.'&sessID='.$Session->ID; // used to detect cookie problems
echo get_link_tag( $url_validate_mail );
echo '<br /><br />';

locale_temp_switch( $params['locale'] );

echo T_('If this does not work, please copy/paste that link into the address bar of your browser.');
echo '<br /><br />';

echo sprintf( T_('We also recommend that you add %s to your contacts in order to make sure you will receive future notifications, especially when someone sends you a private message.'), $Settings->get( 'notification_sender_email' ) );
echo '<br /><br />--<br />';

echo T_('Please note:').' '.T_('For security reasons the link is only valid for your current session (by means of your session cookie).');

locale_restore_previous();
?>