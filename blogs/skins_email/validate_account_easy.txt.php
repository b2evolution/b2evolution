<?php
/**
 * This is the PLAIN TEXT template of email message for validate user account (Easy mode)
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

global $htsrv_url;

// Default params:
$params = array_merge( array(
		'locale'                    => '',
		'status'                    => '',
		'reminder_key'              => '',
		'already_received_messages' => ''
	), $params );


echo get_validate_email_message( $params['locale'], $params['status'], 'txt' );
// Activation link
echo $htsrv_url.'login.php?action=activateaccount&userID=$user_ID$&reminderKey='.$params['reminder_key'];

locale_temp_switch( $params['locale'] );

if( !empty( $params['already_received_messages'] ) )
{	// add already received message list to email body
	echo "\n\n".T_( 'You have received private messages in the following conversations, but your account must be activated before you can read them:' )."\n";
	echo $params['already_received_messages'];
}

echo "\n\n";
echo T_( 'If you don\'t want to receive notifications to activate your account any more, please click here:' ).' ';
echo $htsrv_url.'quick_unsubscribe.php?type=account_activation&user_ID=$user_ID$&key=$unsubscribe_key$';

locale_restore_previous();
?>