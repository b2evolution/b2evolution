<?php
/**
 * This page displays an error message when we have detected access to the stats.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Note: if you have a really really good reason to bypass this, uncomment the following line:
// return;

header_http_response('410 Gone');

$page_title = '410 Gone';
// -------------------------- HTML HEADER INCLUDED HERE --------------------------
siteskin_include( '_html_header.inc.php', array(), true );	// force include even if site headers/footers are not enabled
// -------------------------------- END OF HEADER --------------------------------

// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------
?>
<h1>410 Gone</h1>
<p><?php echo $app_name ?> does no longer publish referer statistics publicly in order not to attract spam robots.</p>
<?php
// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
siteskin_include( '_site_body_footer.inc.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------

// -------------------------- HTML FOOTER INCLUDED HERE --------------------------
siteskin_include( '_html_footer.inc.php', array(), true );	// force include even if site headers/footers are not enabled
// -------------------------------- END OF FOOTER --------------------------------

exit(0);
?>