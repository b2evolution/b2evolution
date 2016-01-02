<?php
/**
 * This is sent to a ((User)) or ((BlogOwner)) when someone sends them a message through a contact form (which is called from a comment, footer of blog, etc.)
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

global $htsrv_url, $samedomain_htsrv_url;

// Default params:
$params = array_merge( array(
		'sender_name'      => '',
		'sender_address'   => '',
		'message_footer'   => '',
		'Blog'             => NULL,
		'message'          => '',
		'comment_id'       => NULL,
		'post_id'          => NULL,
		'recipient_User'   => NULL,
		'Comment'          => NULL,
	), $params );

$Blog = & $params['Blog'];
$recipient_User = & $params['recipient_User'];

// show sender name
echo sprintf( T_('%s has sent you this message:'), $params['sender_name'] )."\n\n";

echo $params['message'];
echo "\n\n-- \n";

// show sender IP address
echo sprintf( T_( 'This message was typed by a user connecting from this IP address: %s.' ), implode( ', ', get_ip_list( false, true ) ) )."\n\n";

// show sender email address
echo sprintf( T_( 'By replying, your email will go directly to %s.' ), $params['sender_address'] );

// show additional message info
if( !empty( $Blog ) )
{
	if( !empty( $params['comment_id'] ) )
	{
		echo "\n\n".T_('Message sent from your comment:') . "\n"
			.url_add_param( $Blog->get('url'), 'p='.$params['post_id'].'#'.$params['comment_id'], '&' );
	}
	elseif( !empty( $params['post_id'] ) )
	{
		echo "\n\n".T_('Message sent from your post:') . "\n"
			.url_add_param( $Blog->get('url'), 'p='.$params['post_id'], '&' );
	}
	else
	{
		echo "\n\n".sprintf( T_('Message sent through the contact form on %s.'), $Blog->get('shortname') ). "\n";

	}
}

if( ! empty( $recipient_User ) )
{ // Member:
	global $Settings;
	if( $Settings->get( 'emails_msgform' ) == 'userset' )
	{ // user can allow/deny to receive emails
		$edit_preferences_url = NULL;
		if( !empty( $Blog ) )
		{ // go to blog
			$edit_preferences_url = $Blog->get( 'userprefsurl', array( 'glue' => '&' ) );
		}
		elseif( $recipient_User->check_perm( 'admin', 'restricted' ) )
		{ // go to admin
			$edit_preferences_url = $admin_url.'?ctrl=user&user_tab=userprefs&user_ID='.$recipient_User->ID;
		}
		if( !empty( $edit_preferences_url ) )
		{ // add edit preferences link
			echo "\n\n".T_('You can edit your profile to not receive emails through a form:')."\n".$edit_preferences_url."\n";
		}
	}
	// Add quick unsubcribe link so users can deny receiving emails through b2evo message form in any circumstances
	$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more emails through a message form, click here:' ).' '.
		$htsrv_url.'quick_unsubscribe.php?type=msgform&user_ID=$user_ID$&key=$unsubscribe_key$';
}
elseif( !empty( $params['Comment'] ) )
{ // Visitor:
	$params['unsubscribe_text'] = T_("Click on the following link to not receive e-mails on your comments\nfor this e-mail address anymore:").' '.
		$samedomain_htsrv_url.'anon_unsubscribe.php?type=comment&c='.$params['Comment']->ID.'&anon_email='.rawurlencode( $params['Comment']->author_email );
}

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>