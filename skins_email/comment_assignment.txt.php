<?php
/**
 * * This is sent to ((Users)) to notify them that a post has been assigned to them together with sending new comment.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.txt.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $admin_url, $baseurl;

// Default params:
$params = array_merge( array(
		'notify_full'    => false,
		'Comment'        => NULL,
		'recipient_User' => NULL,
		'principal_User' => NULL,
	), $params );

$principal_User = $params['principal_User'];
$assigned_User = $params['recipient_User'];
$Comment = $params['Comment'];
$Item = $Comment->get_Item();
$Collection = $Blog = & $Item->get_Blog();

if( $params['notify_full'] )
{	/* Full notification */
	echo sprintf( T_('%s assigned you the following post'), $principal_User->get( 'login' ) ).':'."\n\n";

	// Calculate length for str_pad to align labels:
	$pad_len = max( utf8_strlen( utf8_strlen( T_('Title') ), T_('Collection') ), utf8_strlen( T_('Author') ), utf8_strlen( T_('Url') ), utf8_strlen( T_('Status') ) );

	echo str_pad( T_('Title'), $pad_len ).': '.$Item->get( 'title' )."\n";

	echo str_pad( T_('Collection'), $pad_len ).': '.$Blog->get( 'shortname' ).' ( '.str_replace( '&amp;', '&', $Blog->gen_blogurl() ).' )'."\n";

	$Item->get_creator_User();
	echo str_pad( T_('Author'), $pad_len ).': '.$Item->creator_User->get( 'preferredname' ).' ('.$Item->creator_User->get( 'login' ).")\n";

	// linked URL or "-" if empty:
	echo str_pad( T_('Url'), $pad_len ).': '.( empty( $Item->url ) ? '-' : str_replace( '&amp;', '&', $Item->get( 'url' ) ) )."\n";

	echo str_pad( T_('Status'), $pad_len ).': '.$Item->get( 't_extra_status' )."\n";

	echo T_('With the following Meta-Comment').': '."\n\n";

	echo $Comment->get( 'content' )."\n";

	// Attachments:
	$LinkCache = & get_LinkCache();
	$comment_links = $LinkCache->get_by_comment_ID( $Comment->ID );
	if( ! empty( $comment_links ) )
	{
		echo "\n".T_('Attachments').":\n";
		foreach( $comment_links as $Link )
		{
			if( $File = $Link->get_File() )
			{
				echo ' - '.$File->get_name().': '.$File->get_url()."\n";
			}
		}
		echo "\n";
	}
}
else
{	/* Short notification */
	echo sprintf( T_( '%s assigned you a post on %s with title %s.' ), $principal_User->get_username(), '"'.$Blog->get( 'shortname' ).'"', '"'.$Item->get( 'title' ).'"' );

	echo "\n\n"
		.T_('Status').': '.$Item->get( 't_extra_status' )."\n"
		.T_( 'This is a short form notification. To make these emails more useful, ask the administrator to send you long form notifications instead.' )
		."\n";
}

echo "\n";
echo T_( 'To read the full content of the comment click here:' ).' ';
echo $Comment->get_permanent_url( '&', '#comments' );
echo "\n";

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notifications when posts are assigned to you, click here:' ).' '.
		get_htsrv_url().'quick_unsubscribe.php?type=comment_assignment&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>