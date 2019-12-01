<?php
/**
 * This is sent to ((Users)) to notify them that a post has been assigned to them.
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
		'principal_User' => NULL,
	), $params );

$principal_User = $params['principal_User'];
$assigned_User = $params['recipient_User'];
$Item = $params['Item'];
$Collection = $Blog = & $Item->get_Blog();

if( $params['notify_full'] )
{	/* Full notification */
	echo sprintf( T_('%s assigned you the following post'), $principal_User->get( 'login' ) ).':'."\n\n";

	echo $Item->get( 'title' ).' -- '.$Blog->get( 'shortname' ).' ( '.str_replace( '&amp;', '&', $Blog->gen_blogurl() ).' )'."\n";

	echo T_('Content').': ';
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

	if( $assigned_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item ) )
	{ // User has permission to edit this post
		echo T_('Edit/Delete').': '.$admin_url.'?ctrl=items&blog='.$Item->get_blog_ID().'&p='.$Item->ID."\n";
	}
}
else
{	/* Short notification */
	echo sprintf( T_( '%s assigned you a post on %s with title %s.' ), $principal_User->get_username(), '"'.$Blog->get('shortname').'"', '"'.$Item->get('title').'"' );
	echo "\n\n";
	echo T_( 'To read the full content of the post click here:' ).' ';
	echo $Item->get_permanent_url( '', '', '&' );
	echo "\n";

	echo "\n"
		.T_('Status').': '.$Item->get( 't_status' )."\n"
		.T_( 'This is a short form notification. To make these emails more useful, ask the administrator to send you long form notifications instead.' )
		."\n";
}

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notifications when posts are assigned to you, click here:' ).' '.
		get_htsrv_url().'quick_unsubscribe.php?type=post_assignment&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>