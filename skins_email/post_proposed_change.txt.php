<?php
/**
 * This is sent to ((Users)) and/or ((Moderators)) to notify them that a change has been proposed on a post.
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

global $admin_url;

// Default params:
$params = array_merge( array(
		'iver_ID'        => NULL,
		'Item'           => NULL,
		'recipient_User' => NULL,
		'proposer_User'  => NULL,
	), $params );

$recipient_User = $params['recipient_User'];
$proposer_User = $params['proposer_User'];
$Item = $params['Item'];

echo sprintf( T_('%s proposed a change on a post %s.'), $proposer_User->get_username(), '"'.$Item->get( 'title' ).'"' );
echo "\n\n";

// Buttons:
echo T_('View all changes').': '.$admin_url.'?ctrl=items&action=history_compare&p='.$Item->ID.'&r1=c&r2=p'.$params['iver_ID']."\n";
echo T_('View history').': '.$admin_url.'?ctrl=items&action=history&p='.$Item->ID."\n";
echo T_('View post').': '.$Item->get_permanent_url( '', '', '&' )."\n";

// Footer vars:
$params['unsubscribe_text'] = T_('You are a moderator in this blog, and you are receiving notifications when a post may need moderation.')."\n"
	.T_('If you don\'t want to receive any more notifications about moderating proposed changes on posts, click here').': '
	.get_htsrv_url().'quick_unsubscribe.php?type=post_proposed_change&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>