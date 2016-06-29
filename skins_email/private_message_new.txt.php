<?php
/**
 * This is sent to a ((User)) to notify them when someone sends them a private message on the site.
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

global $current_User, $htsrv_url, $evo_charset;

// Default params:
$params = array_merge( array(
		'recipient_ID'         => 0,
		'new_thread'           => '',
		'thrd_recipients'      => '',
		'Message'              => NULL,
		'message_link'         => '',
		'other_unread_threads' => '',
		'from_User'            => NULL,
	), $params );


$Message = $params['Message'];
$UserCache = & get_UserCache();

$recipient_User = $UserCache->get_by_ID( $params['recipient_ID'] );
$from_User = ( $params['from_User'] == NULL ) ? $current_User : $params['from_User'];

if( $params['new_thread'] )
{
	echo sprintf( T_( '%s just sent you a private message with the title %s.' ), $from_User->login, '"'.$Message->Thread->title.'"' );
}
elseif( count( $params['thrd_recipients'] ) == 1 )
{
	echo sprintf( T_( '%s just replied to your private message in the %s conversation.' ), $from_User->login, '"'.$Message->Thread->title.'"' );
}
else
{
	echo sprintf( T_( '%s just replied to the %s conversation.' ), $from_User->login, '"'.$Message->Thread->title.'"' );
}

echo "\n\n";

if( $recipient_User->check_perm( 'pm_notif', 'full' ) )
{
	echo T_( 'To read the full conversation, click here:' )."\n".$params['message_link']."\n";
	echo T_( 'Message content:' ).' '.htmlentities( $Message->get('text'), ENT_COMPAT, $evo_charset );
}
else
{
	echo T_( 'To read the full message, click here:' )."\n".$params['message_link'];
}

echo "\n";

if( count( $params['other_unread_threads'] ) > 0 )
{ // Display other unread threads
	echo "\n".T_( 'In addition to this new message, you also have unread private messages in the following conversations' ).":\n";
	foreach( $params['other_unread_threads'] as $unread_thread )
	{
		echo "\t - ".strip_tags( $unread_thread )."\n";
	}
	echo "\n";
}

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notifications about new private messages, click here:' ).' '.
		$htsrv_url.'quick_unsubscribe.php?type=new_msg&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>