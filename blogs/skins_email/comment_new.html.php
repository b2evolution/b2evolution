<?php
/**
 * This is sent to ((Users)) and/or ((Moderators)) to notify them that a new comment has been posted.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @version $Id: comment_new.html.php 7043 2014-07-02 08:35:45Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.html.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $htsrv_url, $admin_url;

// Default params:
$params = array_merge( array(
		'notify_full'    => false,
		'Comment'        => NULL,
		'Blog'           => NULL,
		'Item'           => NULL,
		'author_ID'      => NULL,
		'author_name'    => '',
		'notify_type'    => '',
		'recipient_User' => NULL,
	), $params );


$Comment = $params['Comment'];
$Blog = $params['Blog'];
$Item = $params['Item'];
$recipient_User = & $params['recipient_User'];

$author_name = empty( $params['author_ID'] ) ? $params['author_name'] : get_user_colored_login( $params['author_name'] );
$notify_message = '<p>'.sprintf( T_( '%s posted a new comment on %s in %s.' ), '<b>'.$author_name.'</b>', '<b>'.get_link_tag( $Item->get_permanent_url(), $Item->get('title') ).'</b>', '<b>'.$Blog->get('shortname').'</b>' ).'</p>';

if( $params['notify_full'] )
{ // Long format notification:
	$ip_list = implode( ', ', get_linked_ip_list( array( $Comment->author_IP ), $recipient_User ) );
	$user_domain = gethostbyaddr( $Comment->author_IP );
	if( $user_domain != $Comment->author_IP )
	{ // Add host name after author IP address
		$ip_list .= ', '.$user_domain;
	}
	switch( $Comment->type )
	{
		case 'trackback':
			$notify_message .= '<p>'.T_('Trackback IP').': '.$ip_list."</p>\n";
			$notify_message .= '<p>'.T_('Url').': '.get_link_tag( $Comment->author_url )."</p>\n";
			break;

		default:
			if( ! $Comment->get_author_User() )
			{ // Comment from visitor:
				$notify_message .= '<p>'.T_('Commenter IP').': '.$ip_list."</p>\n";
				$notify_message .= '<p>'.T_('Email').': '.$Comment->author_email."</p>\n";
				$notify_message .= '<p>'.T_('Url').': '.get_link_tag( $Comment->author_url )."</p>\n";
			}
	}

	if( !empty( $Comment->rating ) )
	{
		$notify_message .= '<p>'.T_('Rating').': '.$Comment->rating.'/5'."</p>\n";
	}

	if( $params['notify_type'] == 'moderator' )
	{
		$notify_message .= '<p>'.T_('Status').': '.$Comment->get( 't_status' )."</p>\n";
	}

	// Content:
	$notify_message .= '<div class="email_ugc">'."\n";
	$notify_message .= '<p>'.nl2br( $Comment->get('content') ).'</p>';
	$notify_message .= "</div>\n";

	// Attachments:
	$LinkCache = & get_LinkCache();
	$comment_links = $LinkCache->get_by_comment_ID( $Comment->ID );
	if( !empty( $comment_links ) )
	{
		$notify_message .= '<p>'.T_('Attachments').':<ul>'."\n";
		foreach( $comment_links as $Link )
		{
			if( $File = $Link->get_File() )
			{
				$notify_message .= '<li><a href="'.$File->get_url().'">';
				if( $File->is_image() )
				{ // Display an image
					$notify_message .= $File->get_thumb_imgtag( 'fit-80x80', '', 'middle' ).' ';
				}
				$notify_message .= $File->get_name().'</a></li>'."\n";
			}
		}
		$notify_message .= "</ul></p>\n";
	}
}
else
{	// Short format notification:
	if( $params['notify_type'] == 'moderator' )
	{
		$notify_message .= '<p>'.T_('Status').': <b>'.$Comment->get( 't_status' )."</b></p>\n";

		$notify_message .= '<div class="email_ugc">'."\n";
		$notify_message .= '<p><i class="note">'.T_( 'This is a short form moderation message. To make these emails more useful for quick moderation, ask the administrator to send you long form moderation messages instead.' ).'</i></p>';
		$notify_message .= "</div>\n";
	}
}

echo $notify_message;

// Buttons:

echo '<div class="buttons">'."\n";

echo get_link_tag( $Comment->get_permanent_url(), T_( 'Read full comment' ), 'button_green' )."\n";

if( $params['notify_type'] == 'moderator' )
{ // moderation email
	if( ( $Blog->get_setting( 'comment_quick_moderation' ) != 'never' ) && ( !empty( $Comment->secret ) ) )
	{ // quick moderation is permitted, and comment secret was set
		echo get_link_tag( '$secret_content_start$'.$htsrv_url.'comment_review.php?cmt_ID='.$Comment->ID.'&secret='.$Comment->secret.'$secret_content_end$', T_('Quick moderation'), 'button_yellow' )."\n";
	}
	echo get_link_tag( $admin_url.'?ctrl=comments&action=edit&comment_ID='.$Comment->ID, T_('Edit comment'), 'button_gray' )."\n";
}

echo "</div>\n";


// add unsubscribe and edit links
$params['unsubscribe_text'] = '';
if( $params['notify_type'] == 'moderator' )
{ // moderation email
	$params['unsubscribe_text'] = T_( 'You are a moderator in this blog, and you are receiving notifications when a comments may need moderation.' ).'<br />';
	$params['unsubscribe_text'] .= T_( 'If you don\'t want to receive any more notifications about comment moderation, click here:' )
			.' <a href="'.$htsrv_url.'quick_unsubscribe.php?type=comment_moderator&user_ID=$user_ID$&key=$unsubscribe_key$">'
			.T_('instant unsubscribe').'</a>.';
}
else if( $params['notify_type'] == 'blog_subscription' )
{ // blog subscription
	$params['unsubscribe_text'] = T_( 'You are receiving notifications when anyone comments on any post.' ).'<br />';
	$params['unsubscribe_text'] .= T_( 'If you don\'t want to receive any more notifications on this blog, click here:' )
			.' <a href="'.$htsrv_url.'quick_unsubscribe.php?type=coll_comment&coll_ID='.$Blog->ID.'&user_ID=$user_ID$&key=$unsubscribe_key$">'
			.T_('instant unsubscribe').'</a>.';
	// subscribers are not allowed to see comment author email
}
else if( $params['notify_type'] == 'item_subscription' )
{ // item subscription
	$params['unsubscribe_text'] = T_( 'You are receiving notifications when anyone comments on this post.' ).'<br />';
	$params['unsubscribe_text'] .= T_( 'If you don\'t want to receive any more notifications on this post, click here:' )
			.' <a href="'.$htsrv_url.'quick_unsubscribe.php?type=post&post_ID='.$Item->ID.'&user_ID=$user_ID$&key=$unsubscribe_key$">'
			.T_('instant unsubscribe').'</a>.';
	// subscribers are not allowed to see comment author email
}
else if( $params['notify_type'] == 'creator' )
{ // user is the creator of the post
	$params['unsubscribe_text'] = T_( 'This is your post. You are receiving notifications when anyone comments on your posts.' ).'<br />';
	$params['unsubscribe_text'] .= T_( 'If you don\'t want to receive any more notifications on your posts, click here:' )
			.' <a href="'.$htsrv_url.'quick_unsubscribe.php?type=creator&user_ID=$user_ID$&key=$unsubscribe_key$">'
			.T_('instant unsubscribe').'</a>.';
}

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>