<?php
/**
 * This is the HTML template of email message for post by email report
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.html.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

// Default params:
$params = array_merge( array(
		'Items' => NULL,
	), $params );


$Items = $params['Items'];

echo '<p'.emailskin_style( '.p' ).'>'.T_('You just created the following posts:').'</p>';

foreach( $Items as $Item )
{
	echo format_to_output( $Item->title );
	echo '<p'.emailskin_style( '.p' ).'>'.get_link_tag( $Item->get_permanent_url( '', '', '&' ), '', '.a' ).'</p>';
}

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>