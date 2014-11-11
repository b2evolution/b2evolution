<?php
/**
 * This is sent to ((Moderators)) to remind them that some posts are still awaiting moderation 24 hours after they have been posted.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @version $Id: posts_unmoderated_reminder.html.php 7347 2014-10-01 11:52:15Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.html.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $admin_url, $baseurl, $htsrv_url, $post_moderation_reminder_threshold;

$BlogCache = & get_BlogCache();

// Default params:
$params = array_merge( array(
		'blogs' => array(),
		'posts' => array(),
	), $params );

echo '<p>'.sprintf( T_('There have been posts awaiting moderation for more than %s in the following blogs:'), seconds_to_period( $post_moderation_reminder_threshold ) ).'</p>';

echo '<ul>';
foreach( $params['blogs'] as $blog_ID )
{
	$moderation_Blog = $BlogCache->get_by_ID( $blog_ID );
	echo '<li>'.
			$moderation_Blog->get( 'shortname' ).
			' ('.sprintf( T_( '%s posts waiting' ), $params['posts'][$blog_ID] ).') - '.
			get_link_tag( $admin_url.'?ctrl=dashboard&blog='.$blog_ID, T_('Click here to moderate &raquo;') ).
		'</li>';
}
echo '</ul>';

// Footer vars:
$params['unsubscribe_text'] = T_( 'You are a moderator in this blog, and you are receiving notifications when the posts may need moderation.' ).'<br />'."\n"
			.T_( 'If you don\'t want to receive any more notifications about post moderation, click here' ).': '
			.'<a href="'.$htsrv_url.'quick_unsubscribe.php?type=pst_moderation_reminder&user_ID=$user_ID$&key=$unsubscribe_key$">'
			.T_('instant unsubscribe').'</a>.';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>
