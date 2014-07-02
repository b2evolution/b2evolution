<?php
/**
 * This is sent to a ((User)) to notify them when they have had private messages waiting to be read on the site for several days.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @version $Id: private_messages_unread_reminder.html.php 7043 2014-07-02 08:35:45Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.html.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $htsrv_url;

// Default params:
$params = array_merge( array(
		'unread_threads' => '',
		'threads_link'   => '',
	), $params );


echo '<p>'.T_( 'You have unread messages in the following conversations:' )."</p>\n";

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
echo '<div class="buttons">'."\n";
echo get_link_tag( $params['threads_link'], T_('Read your messages'), 'button_green' )."\n";
echo "</div>\n";

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive notifications for unread messages any more, click here:' )
			.' <a href="'.$htsrv_url.'quick_unsubscribe.php?type=unread_msg&user_ID=$user_ID$&key=$unsubscribe_key$">'
			.T_('instant unsubscribe').'</a>.';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>