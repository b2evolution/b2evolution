<?php
/**
 * This is sent to ((Users)) and/or ((Moderators)) to notify them that a new comment has been posted.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.html.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $htsrv_url, $admin_url, $Blog;

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

$author_name = empty( $params['author_ID'] ) ? $params['author_name'] : get_user_colored_login_link( $params['author_name'], array( 'use_style' => true ) );
if( $params['notify_type'] == 'meta_comment' )
{ // Meta comment
	$info_text = T_( '%s posted a new meta comment on %s in %s.' );
}
else
{ // Normal comment
	$info_text = T_( '%s posted a new comment on %s in %s.' );
}
$notify_message = '<p'.emailskin_style( '.p' ).'>'.sprintf( $info_text, '<b>'.$author_name.'</b>', '<b>'.get_link_tag( $Item->get_permanent_url( '', '', '&' ), $Item->get( 'title' ), '.a' ).'</b>', '<b>'.$Blog->get('shortname').'</b>' )."</p>\n";

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
			$notify_message .= '<p'.emailskin_style( '.p' ).'>'.T_('Trackback IP').': '.$ip_list."</p>\n";
			$notify_message .= '<p'.emailskin_style( '.p' ).'>'.T_('Url').': '.get_link_tag( $Comment->author_url, '', '.a' )."</p>\n";
			break;

		default:
			if( ! $Comment->get_author_User() )
			{ // Comment from visitor:
				$notify_message .= '<p'.emailskin_style( '.p' ).'>'.T_('Commenter IP').': '.$ip_list."</p>\n";
				$notify_message .= '<p'.emailskin_style( '.p' ).'>'.T_('Email').': '.$Comment->author_email."</p>\n";
				$notify_message .= '<p'.emailskin_style( '.p' ).'>'.T_('Url').': '.get_link_tag( $Comment->author_url, '', '.a' )."</p>\n";
			}
	}

	if( !empty( $Comment->rating ) )
	{
		$notify_message .= '<p'.emailskin_style( '.p' ).'>'.T_('Rating').': '.$Comment->rating.'/5'."</p>\n";
	}

	if( $params['notify_type'] == 'moderator' )
	{
		$notify_message .= '<p'.emailskin_style( '.p' ).'>'.T_('Status').': '.$Comment->get( 't_status' )."</p>\n";
	}

	// Content:
	$notify_message .= '<div class="email_ugc"'.emailskin_style( 'div.email_ugc' ).'>'."\n";
	$notify_message .= '<p'.emailskin_style( '.p' ).'>'.nl2br( $Comment->get('content') ).'</p>';
	$notify_message .= "</div>\n";

	// Attachments:
	$LinkCache = & get_LinkCache();
	$comment_links = $LinkCache->get_by_comment_ID( $Comment->ID );
	if( !empty( $comment_links ) )
	{
		$notify_message .= '<p'.emailskin_style( '.p' ).'>'.T_('Attachments').':<ul>'."\n";
		foreach( $comment_links as $Link )
		{
			if( $File = $Link->get_File() )
			{
				$notify_message .= '<li><a href="'.$File->get_url().'"'.emailskin_style( '.a' ).'>';
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
		$notify_message .= '<p'.emailskin_style( '.p' ).'>'.T_('Status').': <b>'.$Comment->get( 't_status' )."</b></p>\n";

		$notify_message .= '<div class="email_ugc"'.emailskin_style( 'div.email_ugc' ).'>'."\n";
		$notify_message .= '<p'.emailskin_style( '.p' ).'><i'.emailskin_style( '.note' ).'>'.T_( 'This is a short form moderation message. To make these emails more useful for quick moderation, ask the administrator to send you long form moderation messages instead.' ).'</i></p>';
		$notify_message .= "</div>\n";
	}
}

echo $notify_message;

// Buttons:

echo '<div'.emailskin_style( 'div.buttons' ).'>'."\n";

echo get_link_tag( $Comment->get_permanent_url( '&', '#comments' ), T_( 'Read full comment' ), 'div.buttons a+a.button_green' )."\n";

if( $params['notify_type'] == 'moderator' )
{ // moderation email
	if( ( $Blog->get_setting( 'comment_quick_moderation' ) != 'never' ) && ( !empty( $Comment->secret ) ) )
	{ // quick moderation is permitted, and comment secret was set
		echo get_link_tag( '$secret_content_start$'.$htsrv_url.'comment_review.php?cmt_ID='.$Comment->ID.'&secret='.$Comment->secret.'$secret_content_end$', T_('Quick moderation'), 'div.buttons a+a.button_yellow' )."\n";
	}
	echo get_link_tag( $admin_url.'?ctrl=comments&action=edit&comment_ID='.$Comment->ID, T_('Edit comment'), 'div.buttons a+a.button_gray' )."\n";
}

echo "</div>\n";


// add unsubscribe and edit links
$params['unsubscribe_text'] = '';
switch( $params['notify_type'] )
{
	case 'moderator':
		// moderation email
		$params['unsubscribe_text'] = T_( 'You are a moderator of this blog and you are receiving notifications when a comment may need moderation.' ).'<br />'
			.T_( 'If you don\'t want to receive any more notifications about comment moderation, click here' ).': '
			.get_link_tag( $htsrv_url.'quick_unsubscribe.php?type=comment_moderator&user_ID=$user_ID$&key=$unsubscribe_key$', T_('instant unsubscribe'), '.a' );
		break;

	case 'blog_subscription':
		// blog subscription
		$params['unsubscribe_text'] = T_( 'You are receiving notifications when anyone comments on any post.' ).'<br />'
			.T_( 'If you don\'t want to receive any more notifications on this blog, click here' ).': '
			.get_link_tag( $htsrv_url.'quick_unsubscribe.php?type=coll_comment&coll_ID='.$Blog->ID.'&user_ID=$user_ID$&key=$unsubscribe_key$', T_('instant unsubscribe'), '.a' );
		// subscribers are not allowed to see comment author email
		break;

	case 'item_subscription':
		// item subscription
		$params['unsubscribe_text'] = T_( 'You are receiving notifications when anyone comments on this post.' ).'<br />'
			.T_( 'If you don\'t want to receive any more notifications on this post, click here' ).': '
			.get_link_tag( $htsrv_url.'quick_unsubscribe.php?type=post&post_ID='.$Item->ID.'&user_ID=$user_ID$&key=$unsubscribe_key$', T_('instant unsubscribe'), '.a' );
		// subscribers are not allowed to see comment author email
		break;

	case 'creator':
		// user is the creator of the post
		$params['unsubscribe_text'] = T_( 'This is your post. You are receiving notifications when anyone comments on your posts.' ).'<br />'
			.T_( 'If you don\'t want to receive any more notifications on your posts, click here' ).':'
			.get_link_tag( $htsrv_url.'quick_unsubscribe.php?type=creator&user_ID=$user_ID$&key=$unsubscribe_key$', T_('instant unsubscribe'), '.a' );
		break;

	case 'meta_comment':
		// meta comment subscription
		$params['unsubscribe_text'] = T_( 'You are receiving notifications when meta comment is added on this post.' ).'<br />'
			.T_( 'If you don\'t want to receive any more notifications about meta comments, click here' ).': '
			.get_link_tag( $htsrv_url.'quick_unsubscribe.php?type=meta_comment&user_ID=$user_ID$&key=$unsubscribe_key$', T_('instant unsubscribe'), '.a' );
		break;
}

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>