<?php
/**
 * This is sent to ((Moderators)) to remind them that some posts are still awaiting moderation 24 hours after they have been created.
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

global $admin_url, $baseurl, $post_moderation_reminder_threshold;

$BlogCache = & get_BlogCache();

// Default params:
$params = array_merge( array(
		'blogs' => array(),
		'posts' => array(),
	), $params );

echo sprintf( T_('There have been posts awaiting moderation for more than %s in the following blogs:'), seconds_to_period( $post_moderation_reminder_threshold ) );
echo "\n\n";

foreach( $params['blogs'] as $blog_ID )
{
	$moderation_Blog = $BlogCache->get_by_ID( $blog_ID );
	echo "\t - ".$moderation_Blog->get( 'shortname' ).' ('.sprintf( T_( '%s posts waiting' ), $params['posts'][$blog_ID] ).') - '.$admin_url.'?ctrl=coll_settings&amp;tab=dashboard&blog='.$blog_ID."\n";
}

// Footer vars:
$params['unsubscribe_text'] = T_( 'You are a moderator in this blog, and you are receiving notifications when the posts may need moderation.' )."\n".
		T_( 'If you don\'t want to receive any more notifications about post moderation, click here' ).': '.
		get_htsrv_url().'quick_unsubscribe.php?type=pst_moderation_reminder&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>