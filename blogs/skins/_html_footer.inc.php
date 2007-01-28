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
?>

	<?php
	/*
		// Display a link to contact the owner of this blog (if owner accepts messages):
		$Blog->contact_link( array(
				'before'      => '',
				'after'       => ' &bull; ',
				'text'   => T_('Contact'),
				'title'  => T_('Send a message to the owner of this blog...'),
			) );

	Powered by <a href="http://b2evolution.net/" title="b2evolution home" target="_blank">b2evolution</a>
	&bull;
  Credits: <a href="http://skinfaktory.com/">skin makers</a>

		// Display additional credits (see /conf/_advanced.php):
 		// If you can add your own credits without removing the defaults, you'll be very cool :))
		// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
		display_list( $credit_links, ' &bull; ', '', ' &bull; ', ' ', ' ' );
	*/
	?>

<?php
$Hit->log();	// log the hit on this page
debug_info(); // output debug info if requested
?>
</body>
</html>