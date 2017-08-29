<?php
/**
 * This is sent to ((Moderators)) to alert them that some posts are old.
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

$ItemCache = & get_ItemCache();

// Default params:
$params = array_merge( array(
		'months' => 1,
		'posts'  => array(),
	), $params );

echo sprintf( T_('The following posts have not been updated in %s months:'), $params['months'] );
echo "\n\n";

foreach( $params['posts'] as $post_ID )
{
	$old_Item = $ItemCache->get_by_ID( $post_ID );
	echo "\t - ".$old_Item->get( 'title' ).' - '.$old_Item->get_permanent_url( '', '', '&' )."\n";
}

// Footer vars:
$params['unsubscribe_text'] = T_( 'You are a moderator in this collection, and you are receiving notifications when stale posts may need moderation.' )."\n".
		T_( 'If you don\'t want to receive any more notifications about stale posts, click here' ).': '.
		get_htsrv_url().'quick_unsubscribe.php?type=pst_stale_alert&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>