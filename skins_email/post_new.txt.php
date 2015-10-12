<?php
/**
 * This is sent to ((Users)) and/or ((Moderators)) to notify them that a new post has been posted.
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

global $admin_url, $baseurl, $htsrv_url;

// Default params:
$params = array_merge( array(
		'notify_full'    => false,
		'Item'           => NULL,
		'recipient_User' => NULL,
		'notify_type'    => '',
	), $params );


$recipient_User = $params['recipient_User'];
$Item = $params['Item'];
$Blog = & $Item->get_Blog();

if( $params['notify_full'] )
{	/* Full notification */
	// Calculate length for str_pad to align labels:
	$pad_len = max( utf8_strlen( T_('Collection') ), utf8_strlen( T_('Author') ), utf8_strlen( T_('Title') ), utf8_strlen( T_('Url') ), utf8_strlen( T_('Content') ) );

	echo str_pad( T_('Collection'), $pad_len ).': '.$Blog->get('shortname').' ( '.str_replace( '&amp;', '&', $Blog->gen_blogurl() ).' )'."\n";

	echo str_pad( T_('Author'), $pad_len ).': '.$Item->creator_User->get('preferredname').' ('.$Item->creator_User->get('login').")\n";

	echo str_pad( T_('Title'), $pad_len ).': '.$Item->get('title')."\n";

	// linked URL or "-" if empty:
	echo str_pad( T_('Url'), $pad_len ).': '.( empty( $Item->url ) ? '-' : str_replace( '&amp;', '&', $Item->get('url') ) )."\n";

	if( $params['notify_type'] == 'moderator' )
	{
		echo T_('Status').': '.$Item->get( 't_status' )."\n";
	}

	echo str_pad( T_('Content'), $pad_len ).': ';
	// TODO: We MAY want to force a short URL and avoid it to wrap on a new line in the mail which may prevent people from clicking
	// TODO: might get moved onto a single line, at the end of the content..
	echo $Item->get_permanent_url( '', '', '&' )."\n\n";

	echo $Item->get('content')."\n";

	// Attachments:
	$LinkCache = & get_LinkCache();
	$item_links = $LinkCache->get_by_item_ID( $Item->ID );
	if( !empty( $item_links ) )
	{
		echo "\n".T_('Attachments').":\n";
		foreach( $item_links as $Link )
		{
			if( $File = $Link->get_File() )
			{
				echo ' - '.$File->get_name().': '.$File->get_url()."\n";
			}
		}
		echo "\n";
	}

	if( $recipient_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item ) )
	{ // User has permission to edit this post
		echo T_('Edit/Delete').': '.$admin_url.'?ctrl=items&blog='.$Item->get_blog_ID().'&p='.$Item->ID."\n";
	}
}
else
{	/* Short notification */
	echo sprintf( T_( '%s created a new post on %s with title %s.' ), $Item->creator_User->get( 'login' ), '"'.$Blog->get('shortname').'"', '"'.$Item->get('title').'"' );
	echo "\n\n";
	echo T_( 'To read the full content of the post click here:' ).' ';
	echo $Item->get_permanent_url( '', '', '&' );
	echo "\n";

	if( $params['notify_type'] == 'moderator' )
	{
		echo "\n"
			.T_('Status').': '.$Item->get( 't_status' )."\n"
			.T_( 'This is a short form moderation message. To make these emails more useful for quick moderation, ask the administrator to send you long form moderation messages instead.' )
			."\n";
	}
}

// Footer vars:
if( $params['notify_type'] == 'moderator' )
{ // moderation email
	$params['unsubscribe_text'] = T_( 'You are a moderator in this blog, and you are receiving notifications when a post may need moderation.' )."\n";
	$params['unsubscribe_text'] .= T_( 'If you don\'t want to receive any more notifications about post moderation, click here' ).': '
			.$htsrv_url.'quick_unsubscribe.php?type=post_moderator&user_ID=$user_ID$&key=$unsubscribe_key$';
}
else
{ // subscription email
	$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notifications about new posts on this blog, click here:' ).' '.
			$htsrv_url.'quick_unsubscribe.php?type=coll_post&user_ID=$user_ID$&coll_ID='.$Blog->ID.'&key=$unsubscribe_key$';
}

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>