<?php
/**
 * This is sent to ((Users)) and/or ((Moderators)) to notify them that a new post has been posted.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.html.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $admin_url, $baseurl, $htsrv_url;

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
$Blog = & $Item->get_Blog();

if( $params['notify_full'] )
{	/* Full notification */
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
	echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_( '%s created a new post on %s with title %s.' ), $Item->creator_User->get_colored_login( array( 'mask' => '$avatar$ $login$', 'protocol' => 'http:' ) ), '<b>'.$Blog->get('shortname').'</b>', '<b>'.$Item->get('title').'</b>' )."</p>\n";

	if( $params['notify_type'] == 'moderator' )
	{
		echo '<p'.emailskin_style( '.p' ).'>'.T_('Status').': '.$Item->get( 't_status' )."</p>\n";

		echo '<div class="email_ugc"'.emailskin_style( 'div.email_ugc' ).'>'."\n";
		echo '<p'.emailskin_style( '.p' ).'><i'.emailskin_style( '.note' ).'>'.T_( 'This is a short form moderation message. To make these emails more useful for quick moderation, ask the administrator to send you long form moderation messages instead.' ).'</i></p>';
		echo "</div>\n";
	}
}

// Buttons:

echo '<div'.emailskin_style( 'div.buttons' ).'>'."\n";

echo get_link_tag( $Item->get_permanent_url( '', '', '&' ), T_( 'View post' ), 'div.buttons a+a.button_green' )."\n";

if( $recipient_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item ) )
{ // User has permission to edit this post
	echo get_link_tag( $admin_url.'?ctrl=items&blog='.$Item->get_blog_ID().'&p='.$Item->ID, T_('Edit post'), 'div.buttons a+a.button_yellow' )."\n";
}

echo "</div>\n";

// Footer vars:
if( $params['notify_type'] == 'moderator' )
{ // moderation email
	if( $params['is_new_item'] )
	{	// about new item:
		$unsubscribe_text = T_( 'If you don\'t want to receive any more notifications about moderating new posts, click here' );
		$unsubscribe_type = 'post_moderator';
	}
	else
	{	// about updated item:
		$unsubscribe_text = T_( 'If you don\'t want to receive any more notifications about moderating updated posts, click here' );
		$unsubscribe_type = 'post_moderator_edit';
	}
	$params['unsubscribe_text'] = T_( 'You are a moderator in this blog, and you are receiving notifications when a post may need moderation.' ).'<br />'
			.$unsubscribe_text.': '
			.'<a href="'.$htsrv_url.'quick_unsubscribe.php?type='.$unsubscribe_type.'&user_ID=$user_ID$&key=$unsubscribe_key$"'.emailskin_style( '.a' ).'>'
			.T_('instant unsubscribe').'</a>.';
}
else
{ // subscription email
	$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notifications about new posts on this blog, click here:' )
			.' <a href="'.$htsrv_url.'quick_unsubscribe.php?type=coll_post&user_ID=$user_ID$&coll_ID='.$Blog->ID.'&key=$unsubscribe_key$"'.emailskin_style( '.a' ).'>'
			.T_('instant unsubscribe').'</a>.';
}

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>