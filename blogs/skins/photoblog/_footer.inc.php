<?php
/**
 * This is the footer include template.
 *
 * This is meant to be included in a page template.
 * Note: This is also included in the popup: do not include site navigation!
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

?>

<div id="pageFooter">

	<p class="baseline">
		Powered by <a href="http://b2evolution.net/" title="b2evolution home" target="_blank">b2evolution</a>
		|
		Skin by <a href="http://skinfaktory.com/" target="_blank">The Skin Faktory</a>
		|
		<?php
			// Display additional credits (see /conf/_advanced.php):
 			// If you can add your own credits without removing the defaults, you'll be very cool :))
		 	// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
			display_list( $credit_links, T_('Credits').': ', ' ', '|', ' ', ' ' );
		?>
	</p>

</div>

<?php
$Hit->log();	// log the hit on this page
debug_info(); // output debug info if requested
?>
</body>
</html>