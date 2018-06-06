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
emailskin_include( '_email_header.inc.html.php', $params );
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
$mentioned_user_message = ( $params['notify_type'] == 'post_mentioned' ? '<p'.emailskin_style( '.p' ).'>'.T_('You were mentioned in this post.')."</p>\n" : '' );

if( $params['notify_full'] )
{	/* Full notification */
	echo $mentioned_user_message;

	echo '<p'.emailskin_style( '.p' ).'>'.T_('Collection').': '.get_link_tag( $Blog->gen_blogurl(), $Blog->get('shortname'), '.a' )."</p>\n";

	echo '<p'.emailskin_style( '.p' ).'>'.T_('Author').': '.get_user_colored_login_link( $Item->creator_User->login, array( 'use_style' => true, 'protocol' => 'http:' ) ).' ('.$Item->creator_User->get('login').")</p>\n";

	echo '<p'.emailskin_style( '.p' ).'>'.T_('Title').': '.$Item->get('title')."</p>\n";

	// linked URL or "-" if empty:
	echo '<p'.emailskin_style( '.p' ).'>'.T_('Url').': '.( empty( $Item->url ) ? '-' : get_link_tag( $Item->get('url'), '', '.a' ) )."</p>\n";

	if( $params['notify_type'] == 'moderator' )
	{
		echo '<p'.emailskin_style( '.p' ).'>'.T_('Status').': '.$Item->get( 't_status' )."</p>\n";
	}

	// Content:
	echo '<div class="email_ugc"'.emailskin_style( 'div.email_ugc' ).'>'."\n";
	echo '<p'.emailskin_style( '.p' ).'>'.nl2br( $Item->get('content') ).'</p>';
	echo "</div>\n";

	// Attachments:
	$LinkCache = & get_LinkCache();
	$item_links = $LinkCache->get_by_item_ID( $Item->ID );
	if( !empty( $item_links ) )
	{
		echo '<p'.emailskin_style( '.p' ).'>'.T_('Attachments').':<ul>'."\n";
		foreach( $item_links as $Link )
		{
			if( $File = $Link->get_File() )
			{
				echo '<li><a href="'.$File->get_url().'"'.emailskin_style( '.a' ).'>';
				if( $File->is_image() )
				{ // Display an image
					echo $File->get_thumb_imgtag( 'fit-80x80', '', 'middle' ).' ';
				}
				echo $File->get_name().'</a></li>'."\n";
			}
		}
		echo "</ul></p>\n";
	}
}
else
{ /* Short notification */
	echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_( '%s created a new post on %s with title %s.' ), $Item->creator_User->get_colored_login( array( 'mask' => '$avatar$ $login$', 'protocol' => 'http:', 'login_text' => 'name' ) ), '<b>'.$Blog->get('shortname').'</b>', '<b>'.$Item->get('title').'</b>' )."</p>\n";

	echo $mentioned_user_message;

	if( $params['notify_type'] == 'moderator' )
	{
		echo '<p'.emailskin_style( '.p' ).'>'.T_('Status').': '.$Item->get( 't_status' )."</p>\n";

		echo '<div class="email_ugc"'.emailskin_style( 'div.email_ugc' ).'>'."\n";
		echo '<p'.emailskin_style( '.p' ).'><i'.emailskin_style( '.note' ).'>'.T_( 'This is a short form notification. To make these emails more useful, ask the administrator to send you long form notifications instead.' ).'</i></p>';
		echo "</div>\n";
	}
}

// Buttons:

echo '<div'.emailskin_style( 'div.buttons' ).'>'."\n";

echo get_link_tag( $Item->get_permanent_url( '', '', '&' ), T_( 'View post' ), 'div.buttons a+a.btn-primary' )."\n";

if( $recipient_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item ) )
{ // User has permission to edit this post
	echo get_link_tag( $admin_url.'?ctrl=items&blog='.$Item->get_blog_ID().'&p='.$Item->ID, T_('Edit post'), 'div.buttons a+a.btn-default' )."\n";
}

echo "</div>\n";

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
		$params['unsubscribe_text'] = T_( 'You are a moderator in this blog, and you are receiving notifications when a post may need moderation.' ).'<br />'
			.$unsubscribe_text.': '
			.get_link_tag( get_htsrv_url().'quick_unsubscribe.php?type='.$unsubscribe_type.'&user_ID=$user_ID$&key=$unsubscribe_key$', T_('instant unsubscribe'), '.a' ).'.';
		break;

	case 'post_mentioned':
		// user is mentioned in the post
		$params['unsubscribe_text'] = T_( 'You were mentioned in this post, and you are receiving notifications when anyone mention your name in a post.' ).'<br />'
			.T_( 'If you don\'t want to receive any more notifications when you were mentioned in a post, click here' ).': '
			.get_link_tag( get_htsrv_url().'quick_unsubscribe.php?type=post_mentioned&user_ID=$user_ID$&key=$unsubscribe_key$', T_('instant unsubscribe'), '.a' ).'.';
		break;

	case 'subscription':
	default:
		// subscription email
		$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notifications about new posts on this blog, click here:' )
			.' '.get_link_tag( get_htsrv_url().'quick_unsubscribe.php?type=coll_post&user_ID=$user_ID$&coll_ID='.$Blog->ID.'&key=$unsubscribe_key$', T_('instant unsubscribe'), '.a' ).'.';
		break;
}

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>