<?php
/**
 * This page displays an error message when we have detected referer spam.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_funcs( 'skins/_skin.funcs.php' );

header_http_response('403 Forbidden');

$page_title = '403 Forbidden - Please stop referer spam.';
// -------------------------- HTML HEADER INCLUDED HERE --------------------------
siteskin_include( '_html_header.inc.php', array(), true );	// force include even if site headers/footers are not enabled
// -------------------------------- END OF HEADER --------------------------------

// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------
?>
<h1>403 Forbidden</h1>
<h2>Please stop referer spam.</h2>
<p>We have identified that you have been refered here by a known or supposed spammer.</p>
<p>If you feel this is an error, please <a href="<?php global $ReqURI; echo $ReqURI; ?>">bypass this message</a>
and leave us a comment about the error. We are sorry for the inconvenience.</p>
<p>If you are actually doing referer spam, please note that this website/<?php global $app_name; echo $app_name; ?> no longer records and publishes referers. Not even legitimate ones!
While we understand it was fun for you guys while it lasted, please understand our servers cannot take the load of
all this cumulated spam any longer... Thank you.</p>
<p>Also, please note that comment/trackback submitted URLs will be tagged with rel="nofollow" in order to be ignored by search engines.</p>
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