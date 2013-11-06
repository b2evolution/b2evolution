<?php
/**
 * This is sent to ((Users)) and/or ((Moderators)) to notify them that a new post has been posted.
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
emailskin_include( '_email_header.inc.txt.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $admin_url, $baseurl, $htsrv_url;

// Default params:
$params = array_merge( array(
		'locale'         => '',
		'notify_full'    => '',
		'Item'           => NULL,
		'recipient_User' => NULL,
	), $params );


$recipient_User = $params['recipient_User'];
$Item = $params['Item'];
$Blog = & $Item->get_Blog();

if( $params['notify_full'] )
{	/* Full notification */
	// Calculate length for str_pad to align labels:
	$pad_len = max( evo_strlen( T_('Blog') ), evo_strlen( T_('Author') ), evo_strlen( T_('Title') ), evo_strlen( T_('Url') ), evo_strlen( T_('Content') ) );

	echo str_pad( T_('Blog'), $pad_len ).': '.$Blog->get('shortname').' ( '.str_replace( '&amp;', '&', $Blog->gen_blogurl() ).' )'."\n";

	echo str_pad( T_('Author'), $pad_len ).': '.$Item->creator_User->get('preferredname').' ('.$Item->creator_User->get('login').")\n";

	echo str_pad( T_('Title'), $pad_len ).': '.$Item->get('title')."\n";

	// linked URL or "-" if empty:
	echo str_pad( T_('Url'), $pad_len ).': '.( empty( $Item->url ) ? '-' : str_replace( '&amp;', '&', $Item->get('url') ) )."\n";

	echo str_pad( T_('Content'), $pad_len ).': ';
	// TODO: We MAY want to force a short URL and avoid it to wrap on a new line in the mail which may prevent people from clicking
	// TODO: might get moved onto a single line, at the end of the content..
	echo str_replace( '&amp;', '&', $Item->get_permanent_url() )."\n\n";

	echo $Item->get('content')."\n";

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
	echo str_replace( '&amp;', '&', $Item->get_permanent_url() );
	echo "\n";
}

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notifications about new posts on this blog, click here:' ).' '.
		$htsrv_url.'quick_unsubscribe.php?type=coll_post&user_ID=$user_ID$&coll_ID='.$Blog->ID.'&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>