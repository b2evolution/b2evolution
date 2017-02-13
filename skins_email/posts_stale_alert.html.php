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
emailskin_include( '_email_header.inc.html.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

$ItemCache = & get_ItemCache();

// Default params:
$params = array_merge( array(
		'months' => 1,
		'posts'  => array(),
	), $params );

echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_('The following posts have not been updated in %s months:'), $params['months'] ).'</p>';

echo '<ul>';
foreach( $params['posts'] as $post_ID )
{
	$old_Item = $ItemCache->get_by_ID( $post_ID );
	echo '<li>'
			.'<a href="'.$old_Item->get_permanent_url( '', '', '&' ).'"'.emailskin_style( '.a' ).'>'.$old_Item->get( 'title' ).'</a>'
		.'</li>';
}
echo '</ul>';

// Footer vars:
$params['unsubscribe_text'] = T_( 'You are a moderator in this collection, and you are receiving notifications when stale posts may need moderation.' ).'<br />'."\n"
			.T_( 'If you don\'t want to receive any more notifications about stale posts, click here' ).': '
			.'<a href="'.get_htsrv_url().'quick_unsubscribe.php?type=pst_stale_alert&user_ID=$user_ID$&key=$unsubscribe_key$"'.emailskin_style( '.a' ).'>'
			.T_('instant unsubscribe').'</a>.';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>
