<?php
/**
 * This is sent to a ((User)) to notify them when they have had private messages waiting to be read on the site for several days.
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

// Default params:
$params = array_merge( array(
		'unread_threads' => '',
		'threads_link'   => '',
	), $params );


echo '<p'.emailskin_style( '.p' ).'>'.T_( 'You have unread private messages in the following conversations:' )."</p>\n";

if( count( $params['unread_threads'] ) > 0 )
{
	echo '<ul>';
	foreach( $params['unread_threads'] as $unread_thread )
	{
		echo '<li>'.$unread_thread.'</li>';
	}
	echo "</ul>\n";
}

// Buttons:
echo '<div'.emailskin_style( 'div.buttons' ).'>'."\n";
echo get_link_tag( $params['threads_link'], T_('Read your private messages'), 'div.buttons a+a.button_green' )."\n";
echo "</div>\n";

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive notifications for unread private messages any more, click here:' )
			.' <a href="'.get_htsrv_url().'quick_unsubscribe.php?type=unread_msg&user_ID=$user_ID$&key=$unsubscribe_key$"'.emailskin_style( '.a' ).'>'
			.T_('instant unsubscribe').'</a>.';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>