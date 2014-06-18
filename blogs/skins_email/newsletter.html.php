<?php
/**
 * This is used when sendinf the newsletter - HTML VERSION
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @version $Id: newsletter.html.php 849 2012-02-16 09:09:09Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.html.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $htsrv_url;

// Default params:
$params = array_merge( array(
		'message_html' => '',
	), $params );

echo $params['message_html'];

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive this newsletter anymore, click here:' )
			.' <a href="'.$htsrv_url.'quick_unsubscribe.php?type=newsletter&user_ID=$user_ID$&key=$unsubscribe_key$">'
			.T_('instant unsubscribe').'</a>.';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>
