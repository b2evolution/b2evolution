<?php
/**
 * This is sent to a ((User)) to notify them when someone sends them a private message on the site.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @version $Id: private_message_new.html.php 7043 2014-07-02 08:35:45Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.html.php', $params );
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

echo '<p>';
if( $params['new_thread'] )
{
	echo sprintf( T_( '%s just sent you a message with the title %s.' ), $from_User->get_colored_login( array( 'mask' => '$avatar$ $login$' ) ), '<b>'.$Message->Thread->title.'</b>' );
}
elseif( count( $params['thrd_recipients'] ) == 1 )
{
	echo sprintf( T_( '%s just replied to your message in the %s conversation. ' ), $from_User->get_colored_login( array( 'mask' => '$avatar$ $login$' ) ), '<b>'.$Message->Thread->title.'</b>' );
}
else
{
	echo sprintf( T_( '%s just replied to the %s conversation.' ), $from_User->get_colored_login( array( 'mask' => '$avatar$ $login$' ) ), '<b>'.$Message->Thread->title.'</b>' );
}
echo "</p>\n";

if( $recipient_User->check_perm( 'pm_notif', 'full' ) )
{
	echo '<div class="email_ugc">'."\n";
	echo '<p>'.nl2br( evo_htmlentities( $Message->get('text'), ENT_COMPAT, $evo_charset ) ).'</p>';
	echo "</div>\n";

	// Buttons:
	echo '<div class="buttons">'."\n";
	echo get_link_tag( $params['message_link'], T_( 'Read full conversation' ), 'button_green' )."\n";
	echo "</div>\n";
}
else
{
	// Buttons:
	echo '<div class="buttons">'."\n";
	echo get_link_tag( $params['message_link'], T_( 'Read full message' ), 'button_green' )."\n";
	echo "</div>\n";
}

if( count( $params['other_unread_threads'] ) > 0 )
{ // Display other unread threads
	echo '<p>'.T_( 'In addition to this new message, you also have unread messages in the following conversations' ).":</p>\n";
	echo '<ul>';
	foreach( $params['other_unread_threads'] as $unread_thread )
	{
		echo '<li>'.$unread_thread.'</li>';
	}
	echo "</ul>\n";
}

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notifications about new private messages, click here:' )
			.' <a href="'.$htsrv_url.'quick_unsubscribe.php?type=new_msg&user_ID=$user_ID$&key=$unsubscribe_key$">'
			.T_('instant unsubscribe').'</a>.';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>