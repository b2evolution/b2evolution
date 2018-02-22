<?php
/**
 * This is the PLAIN TEXT template of email message for newsletter
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.txt.php', $params, 'header' );
// ------------------------------- END OF EMAIL HEADER --------------------------------

// Default params:
$params = array_merge( array(
		'message_text' => '',
		'newsletter'   => '',
	), $params );

echo $params['message_text'];

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive this list anymore, click here:' ).' '.
		get_htsrv_url().'quick_unsubscribe.php?type=newsletter&newsletter='.$params['newsletter'].'&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params, 'footer' );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>
