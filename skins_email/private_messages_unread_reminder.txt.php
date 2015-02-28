<?php
/**
 * This is sent to a ((User)) to notify them when they have had private messages waiting to be read on the site for several days.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.txt.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $htsrv_url;

// Default params:
$params = array_merge( array(
		'unread_threads' => '',
		'threads_link'   => '',
	), $params );


echo T_( 'You have unread messages in the following conversations:' )."\n";

if( count( $params['unread_threads'] ) > 0 )
{
	foreach( $params['unread_threads'] as $unread_thread )
	{
		echo "\t - ".strip_tags( $unread_thread )."\n";
	}
	echo "\n";
}
echo T_( 'Click here to read your messages:' ).' '.$params['threads_link'];


// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive notifications for unread messages any more, click here:' ).' '.
		$htsrv_url.'quick_unsubscribe.php?type=unread_msg&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>