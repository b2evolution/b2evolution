<?php
/**
 * This is the HTML footer include template.
 *
 * This is meant to be included in a page template.
 * Note: This is also included in the popup: do not include site navigation!
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( file_exists( $ads_current_skin_path.'_html_footer.inc.php' ) )
{	// The skin has a customized handler, use that one instead:
	require $ads_current_skin_path.'_html_footer.inc.php';
	return;
}

$Hit->log();	// log the hit on this page
debug_info(); // output debug info if requested
?>
</body>
</html>