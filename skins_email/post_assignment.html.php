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
emailskin_include( '_email_header.inc.html.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $admin_url, $baseurl;

// Default params:
$params = array_merge( array(
		'notify_full'    => false,
		'Item'           => NULL,
		'principal_User' => NULL,
		'recipient_User' => NULL,
	), $params );

$principal_User = $params['principal_User'];
$assigned_User = $params['recipient_User'];
$Item = $params['Item'];
$Collection = $Blog = & $Item->get_Blog();

if( $params['notify_full'] )
{	/* Full notification */
	echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_( '%s assigned you the following post' ).':', $principal_User->get_colored_login( array( 'mask' => '$avatar$ $login$', 'protocol' => 'http:', 'login_text' => 'name' ) ) )."</p>\n";

	echo '<p'.emailskin_style( '.p' ).'><b>'.$Item->get('title')."</b> &ndash; ".get_link_tag( $Blog->gen_blogurl(), $Blog->get('shortname'), '.a' )."</p>\n";

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
	echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_( '%s assigned you a post on %s with title %s.' ), $principal_User->get_colored_login( array( 'mask' => '$avatar$ $login$', 'protocol' => 'http:', 'login_text' => 'name' ) ), '<b>'.$Blog->get('shortname').'</b>', '<b>'.$Item->get('title').'</b>' )."</p>\n";

	echo '<div class="email_ugc"'.emailskin_style( 'div.email_ugc' ).'>'."\n";
	echo '<p'.emailskin_style( '.p' ).'><i'.emailskin_style( '.note' ).'>'.T_( 'This is a short form notification. To make these emails more useful, ask the administrator to send you long form notifications instead.' ).'</i></p>';
	echo "</div>\n";
}

// Buttons:

echo '<div'.emailskin_style( 'div.buttons' ).'>'."\n";

echo get_link_tag( $Item->get_permanent_url( '', '', '&' ), T_( 'View post' ), 'div.buttons a+a.btn-primary' )."\n";

if( $assigned_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item ) )
{ // User has permission to edit this post
	echo get_link_tag( $admin_url.'?ctrl=items&blog='.$Item->get_blog_ID().'&p='.$Item->ID, T_('Edit post'), 'div.buttons a+a.btn-default' )."\n";
}

echo "</div>\n";

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notifications when posts are assigned to you, click here:' )
			.' <a href="'.get_htsrv_url().'quick_unsubscribe.php?type=post_assignment&user_ID=$user_ID$&key=$unsubscribe_key$"'.emailskin_style( '.a' ).'>'
			.T_('instant unsubscribe').'</a>.';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>