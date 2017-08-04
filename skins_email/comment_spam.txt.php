<?php
/**
 * This is sent to ((Moderators)) to notify them that a comment has been voted as SPAM.
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
		'notify_full' => false,
		'Comment'     => NULL,
		'Blog'        => NULL,
		'Item'        => NULL,
		'voter_ID'    => NULL,
	), $params );


$Comment = $params['Comment'];
$Collection = $Blog = $params['Blog'];
$Item = $params['Item'];

$UserCache = & get_UserCache();
$voter_User = & $UserCache->get_by_ID( $params['voter_ID'] );

$notify_message = sprintf( T_('%s reported comment as spam on %s in %s.'), $voter_User->get( 'login' ), '"'.$Item->get( 'title' ).'"', '"'.$Blog->get( 'shortname' ).'"' )."\n\n";

if( $params['notify_full'] )
{	// Long format notification:
	$notify_message .= T_('Spam comment').': '
		.$Comment->get_permanent_url( '&', '#comments' )."\n"
		// TODO: fp> We MAY want to force a short URL and avoid it to wrap on a new line in the mail which may prevent people from clicking
		.T_('Collection').': '.$Blog->get( 'shortname' )."\n"
		// Mail bloat: .' ( '.str_replace('&amp;', '&', $Blog->gen_blogurl())." )\n"
		./* TRANS: noun */ T_('Post').': '.$Item->get( 'title' )."\n";
		// Mail bloat: .' ( '.str_replace('&amp;', '&', $Item->get_permanent_url( '', '', '&' ))." )\n";
		// TODO: fp> We MAY want to force short URL and avoid it to wrap on a new line in the mail which may prevent people from clicking

	$ip_list = $Comment->author_IP;
	$user_domain = gethostbyaddr( $Comment->author_IP );
	if( $user_domain != $Comment->author_IP )
	{	// Add host name after author IP address:
		$ip_list .= ', '.$user_domain;
	}

	if( $Comment->get_author_User() )
	{	// Comment from a registered user:
		$notify_message .= T_('Author').': '.$Comment->author_User->get( 'preferredname' ).' ('.$Comment->author_User->get( 'login' ).")\n";
	}
	else
	{	// Comment from visitor:
		$notify_message .= T_('Author').": $Comment->author (IP: $ip_list)\n";
		$notify_message .= T_('Email').": $Comment->author_email\n";
		$notify_message .= T_('Url').": $Comment->author_url\n";
	}

	if( !empty( $Comment->rating ) )
	{
		$notify_message .= T_('Rating').': '.$Comment->rating.'/5'."\n";
	}

	$notify_message .= T_('Status').': '.$Comment->get( 't_status' )."\n";

	// Content:
	$notify_message .= $Comment->get('content')."\n";

	// Attachments:
	$LinkCache = & get_LinkCache();
	$comment_links = $LinkCache->get_by_comment_ID( $Comment->ID );
	if( !empty( $comment_links ) )
	{
		$notify_message .= "\n".T_('Attachments').":\n";
		foreach( $comment_links as $Link )
		{
			if( $File = $Link->get_File() )
			{
				$notify_message .= ' - '.$File->get_name().': '.$File->get_url()."\n";
			}
		}
		$notify_message .= "\n";
	}
}
else
{	// Shot format notification:
	$notify_message .= T_('To read the full content of the comment click here:').' '
		.$Comment->get_permanent_url( '&', '#comments' )."\n";
		// TODO: fp> We MAY want to force a short URL and avoid it to wrap on a new line in the mail which may prevent people from clicking

	$notify_message .= "\n"
		.T_('Author').': '.( $Comment->get_author_User() ? $Comment->author_User->get( 'login' ) : $Comment->author )."\n"
		.T_('Status').': '.$Comment->get( 't_status' )."\n"
		.T_('This is a short form moderation message. To make these emails more useful for quick moderation, ask the administrator to send you long form moderation messages instead.')
		."\n";
}

$notify_message .= "\n\n";

$notify_message .= T_('Edit comment').': '.$admin_url.'?ctrl=comments&action=edit&comment_ID='.$Comment->ID."\n\n";

echo $notify_message;


// add unsubscribe and edit links:
$params['unsubscribe_text'] = T_( 'You are a moderator of this blog and you are receiving notifications when a comment may need moderation.' )."\n"
	.T_( 'If you don\'t want to receive any more notifications about moderating spam comments, click here' ).': '
	.get_htsrv_url().'quick_unsubscribe.php?type=comment_moderator_spam&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>