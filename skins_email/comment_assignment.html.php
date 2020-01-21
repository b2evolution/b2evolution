<?php
/**
 * This is sent to ((Users)) to notify them that a post has been assigned to them together with sending new comment.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.html.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $admin_url, $baseurl;

// Default params:
$params = array_merge( array(
		'notify_full'    => false,
		'Comment'           => NULL,
		'principal_User' => NULL,
		'recipient_User' => NULL,
	), $params );

$principal_User = $params['principal_User'];
$assigned_User = $params['recipient_User'];
$Comment = $params['Comment'];
$Item = $Comment->get_Item();
$Collection = $Blog = & $Item->get_Blog();

if( $params['notify_full'] )
{	/* Full notification */
	echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_( '%s assigned you the following post' ).':', $principal_User->get_colored_login( array( 'mask' => '$avatar$ $login$', 'protocol' => 'http:', 'login_text' => 'name' ) ) )."</p>\n";

	echo '<p'.emailskin_style( '.p' ).'><b>'.$Item->get( 'title' )."</b> &ndash; ".get_link_tag( $Blog->gen_blogurl(), $Blog->get( 'shortname' ), '.a' )."</p>\n";

	echo '<p'.emailskin_style( '.p' ).'>'.T_('Status').': <b>'.$Item->get( 't_extra_status' )."</b></p>\n";

	echo '<p'.emailskin_style( '.p' ).'>';
	if( $Comment->is_meta() )
	{	// Internal comment:
		printf( T_('With the following <span %s>%s</span> Comment'), emailskin_style( '.label+.label-status-meta' ), T_('Meta') );
	}
	else
	{	// Normal comment:
		$visibility_statuses = get_visibility_statuses( '', array() );
		// TRANS: First %s is color style for comment status, Second %s is comment status title, like Public, Community, etc.
		printf( T_('With the following <span %s>%s</span> Comment'), emailskin_style( '.label+.label-status-'.$Comment->get( 'status' ) ), $visibility_statuses[ $Comment->get( 'status' ) ] );
	}
	echo ':'."</p>\n";

	// Internal comment content:
	echo '<div class="email_ugc"'.emailskin_style( 'div.email_ugc' ).'>'."\n";
	echo '<p'.emailskin_style( '.p' ).'>'.nl2br( $Comment->get( 'content' ) ).'</p>';
	echo "</div>\n";

	// Attachments:
	$LinkCache = & get_LinkCache();
	$comment_links = $LinkCache->get_by_comment_ID( $Comment->ID );
	if( !empty( $comment_links ) )
	{
		echo '<p'.emailskin_style( '.p' ).'>'.T_('Attachments').':<ul>'."\n";
		foreach( $comment_links as $Link )
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
	echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_( '%s assigned you a post on %s with title %s.' ), $principal_User->get_colored_login( array( 'mask' => '$avatar$ $login$', 'protocol' => 'http:', 'login_text' => 'name' ) ), '<b>'.$Blog->get( 'shortname' ).'</b>', '<b>'.$Item->get( 'title' ).'</b>' )."</p>\n";

	echo '<p'.emailskin_style( '.p' ).'>'.T_('Status').': <b>'.$Item->get( 't_extra_status' )."</b></p>\n";

	echo '<div class="email_ugc"'.emailskin_style( 'div.email_ugc' ).'>'."\n";
	echo '<p'.emailskin_style( '.p' ).'><i'.emailskin_style( '.note' ).'>'.T_( 'This is a short form notification. To make these emails more useful, ask the administrator to send you long form notifications instead.' ).'</i></p>';
	echo "</div>\n";
}

// Buttons:
echo '<div'.emailskin_style( 'div.buttons' ).'>'."\n";

echo get_link_tag( $Comment->get_permanent_url( '&', '#comments' ), T_( 'Read full comment' ), 'div.buttons a+a.btn-primary' )."\n";

echo "</div>\n";

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notifications when posts are assigned to you, click here:' )
			.' <a href="'.get_htsrv_url().'quick_unsubscribe.php?type=post_assignment&user_ID=$user_ID$&key=$unsubscribe_key$"'.emailskin_style( '.a' ).'>'
			.T_('instant unsubscribe').'</a>.';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>