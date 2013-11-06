<?php
/**
 * This is sent to ((Users)) and/or ((Moderators)) to notify them that a new comment has been posted.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @version $Id$
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
		'author_name'    => '',
		'notify_type'    => '',
		'notify_user_ID' => 0,
		'notify_key'     => '',
	), $params );


$Comment = $params['Comment'];
$Blog = $params['Blog'];
$Item = $params['Item'];

$notify_message = '<p>'.sprintf( T_( '%s posted a new comment on %s in %s.' ), get_user_colored_login( $params['author_name'] ), '<b>'.get_link_tag( $Item->get_permanent_url(), $Item->get('title') ).'</b>', '<b>'.$Blog->get('shortname').'</b>' ).'</p>';

if( $params['notify_full'] )
{	// Long format notification:

	switch( $Comment->type )
	{
		case 'trackback':
			$user_domain = gethostbyaddr($Comment->author_IP);
			$notify_message .= '<p>'.T_('Trackback IP').': '.$Comment->author_IP.', '.$user_domain."</p>\n";
			$notify_message .= '<p>'.T_('Url').': '.get_link_tag( $Comment->author_url )."</p>\n";
			break;

		default:
			if( ! $Comment->get_author_User() )
			{ // Comment from visitor:
				$user_domain = gethostbyaddr($Comment->author_IP);
				$notify_message .= '<p>'.T_('Commenter IP').': '.$Comment->author_IP.', '.$user_domain."</p>\n";
				$notify_message .= '<p>'.T_('Email').': '.$Comment->author_email."</p>\n";
				$notify_message .= '<p>'.T_('Url').': '.get_link_tag( $Comment->author_url )."</p>\n";
			}
	}

	if( !empty( $Comment->rating ) )
	{
		$notify_message .= '<p>'.T_('Rating').': '.$Comment->rating."</p>\n";
	}

	if( $params['notify_type'] == 'moderator' )
	{
		$notify_message .= '<p>'.T_('Status').': '.$Comment->get( 't_status' )."</p>\n";
	}

	// Content:
	$notify_message .= '<div class="email_ugc">'."\n";
	$notify_message .= '<p>'.nl2br( $Comment->get('content') ).'</p>';
	$notify_message .= "</div>\n";
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

echo get_link_tag( str_replace( '&amp;', '&', $Comment->get_permanent_url() ), T_( 'Read full comment' ), 'button_green' )."\n";

if( $params['notify_type'] == 'moderator' )
{ // moderation email
	if( ( $Blog->get_setting( 'comment_quick_moderation' ) != 'never' ) && ( !empty( $Comment->secret ) ) )
	{ // quick moderation is permitted, and comment secret was set
		echo get_link_tag( $htsrv_url.'comment_review.php?cmt_ID='.$Comment->ID.'&secret='.$Comment->secret, T_('Quick moderation'), 'button_yellow' )."\n";
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
			.' <a href="'.$htsrv_url.'quick_unsubscribe.php?type=moderator&user_ID='.$params['notify_user_ID'].'&key='.md5( $params['notify_user_ID'].$params['notify_key'] ).'">'
			.T_('instant unsubscribe').'</a>.';
}
else if( $params['notify_type'] == 'blog_subscription' )
{ // blog subscription
	$params['unsubscribe_text'] = T_( 'You are receiving notifications when anyone comments on any post.' ).'<br />';
	$params['unsubscribe_text'] .= T_( 'If you don\'t want to receive any more notifications on this blog, click here:' )
			.' <a href="'.$htsrv_url.'quick_unsubscribe.php?type=coll_comment&user_ID='.$params['notify_user_ID'].'&coll_ID='.$Blog->ID.'&key='.md5( $params['notify_user_ID'].$params['notify_key'] ).'">'
			.T_('instant unsubscribe').'</a>.';
	// subscribers are not allowed to see comment author email
}
else if( $params['notify_type'] == 'item_subscription' )
{ // item subscription
	$params['unsubscribe_text'] = T_( 'You are receiving notifications when anyone comments on this post.' ).'<br />';
	$params['unsubscribe_text'] .= T_( 'If you don\'t want to receive any more notifications on this post, click here:' )
			.' <a href="'.$htsrv_url.'quick_unsubscribe.php?type=post&user_ID='.$params['notify_user_ID'].'&post_ID='.$Item->ID.'&key='.md5( $params['notify_user_ID'].$params['notify_key'] ).'">'
			.T_('instant unsubscribe').'</a>.';
	// subscribers are not allowed to see comment author email
}
else if( $params['notify_type'] == 'creator' )
{ // user is the creator of the post
	$params['unsubscribe_text'] = T_( 'This is your post. You are receiving notifications when anyone comments on your posts.' ).'<br />';
	$params['unsubscribe_text'] .= T_( 'If you don\'t want to receive any more notifications on your posts, click here:' )
			.' <a href="'.$htsrv_url.'quick_unsubscribe.php?type=creator&user_ID='.$params['notify_user_ID'].'&key='.md5( $params['notify_user_ID'].$params['notify_key'] ).'">'
			.T_('instant unsubscribe').'</a>.';
}

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>