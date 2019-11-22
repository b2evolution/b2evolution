<?php
/**
 * This is sent to ((Users)) and/or ((Moderators)) to notify them that a new post has been posted.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.txt.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $admin_url, $baseurl;

// Default params:
$params = array_merge( array(
		'notify_full'    => false,
		'Item'           => NULL,
		'recipient_User' => NULL,
		'notify_type'    => '',
		'is_new_item'    => true,
	), $params );


$recipient_User = $params['recipient_User'];
$Item = $params['Item'];
$Collection = $Blog = & $Item->get_Blog();

// Add this info line if user was mentioned in the post content:
$mentioned_user_message = ( $params['notify_type'] == 'post_mentioned' ? T_('You were mentioned in this post.')."\n\n" : '' );

if( $params['notify_full'] )
{	/* Full notification */
	echo $mentioned_user_message;

	// Calculate length for str_pad to align labels:
	$pad_len = max( utf8_strlen( T_('Collection') ), utf8_strlen( T_('Author') ), utf8_strlen( T_('Title') ), utf8_strlen( T_('Url') ), utf8_strlen( T_('Content') ) );

	echo str_pad( T_('Collection'), $pad_len ).': '.$Blog->get( 'shortname' ).' ( '.str_replace( '&amp;', '&', $Blog->gen_blogurl() ).' )'."\n";

	echo str_pad( T_('Author'), $pad_len ).': '.$Item->creator_User->get( 'preferredname' ).' ('.$Item->creator_User->get('login').")\n";

	echo str_pad( T_('Title'), $pad_len ).': '.$Item->get( 'title' )."\n";

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
	echo sprintf( T_( '%s created a new post on %s with title %s.' ), $Item->creator_User->get_username(), '"'.$Blog->get('shortname').'"', '"'.$Item->get('title').'"' );
	echo "\n\n";

	echo $mentioned_user_message;

	echo T_( 'To read the full content of the post click here:' ).' ';
	echo $Item->get_permanent_url( '', '', '&' );
	echo "\n";

	if( $params['notify_type'] == 'moderator' )
	{
		echo "\n"
			.T_('Status').': '.$Item->get( 't_status' )."\n"
			.T_( 'This is a short form notification. To make these emails more useful, ask the administrator to send you long form notifications instead.' )
			."\n";
	}
}

// Footer vars:
switch( $params['notify_type'] )
{
	case 'moderator':
		// moderation email
		if( $params['is_new_item'] )
		{	// about new item:
			$unsubscribe_text = T_( 'If you don\'t want to receive any more notifications about moderating new posts, click here' );
			$unsubscribe_type = 'post_moderator';
			$unsubscribe_params = '';
		}
		else
		{	// about updated item:
			$unsubscribe_text = T_( 'If you don\'t want to receive any more notifications about moderating updated posts, click here' );
			$unsubscribe_type = 'post_moderator_edit';
			$unsubscribe_params = '&amp;coll_ID='.$Item->get_blog_ID();
		}
		$params['unsubscribe_text'] = T_( 'You are a moderator in this blog, and you are receiving notifications when a post may need moderation.' )."\n";
		$params['unsubscribe_text'] .= $unsubscribe_text.': '
			.get_htsrv_url().'quick_unsubscribe.php?type='.$unsubscribe_type.$unsubscribe_params.'&user_ID=$user_ID$&key=$unsubscribe_key$';
		break;

	case 'post_mentioned':
		// user is mentioned in the post
		$params['unsubscribe_text'] = T_( 'You were mentioned in this post, and you are receiving notifications when anyone mentions your name in a post.' )."\n"
			.T_( 'If you don\'t want to receive any more notifications when you were mentioned in a post, click here' ).': '
			.get_htsrv_url().'quick_unsubscribe.php?type=post_mentioned&user_ID=$user_ID$&key=$unsubscribe_key$';
		break;

	case 'subscription':
	default:
		// subscription email
		$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notifications about new posts on this blog, click here:' ).' '
			.get_htsrv_url().'quick_unsubscribe.php?type=coll_post&user_ID=$user_ID$&coll_ID='.$Blog->ID.'&key=$unsubscribe_key$';
		break;
}

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>