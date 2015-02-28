<?php
/**
 * This is the PLAIN TEXT template of email message for post by email report
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.txt.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

// Default params:
$params = array_merge( array(
		'Items' => NULL,
	), $params );


$Items = $params['Items'];

echo T_('You just created the following posts:')."\n\n";

foreach( $Items as $Item )
{
	echo format_to_output( $Item->title )."\n";
	echo $Item->get_permanent_url( '', '', '&' )."\n\n";
}

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>