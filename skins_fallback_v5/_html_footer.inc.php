<?php
/**
 * This is the HTML footer include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * This is meant to be included in a page template.
 * Note: This is also included in the popup: do not include site navigation!
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
?>
<!-- End of skin_wrapper -->
</div>

<?php
	modules_call_method( 'SkinEndHtmlBody' );

	// SkinEndHtmlBody hook -- could be used e.g. by a google_analytics plugin to add the javascript snippet here:
	$Plugins->trigger_event('SkinEndHtmlBody');

	$Blog->disp_setting( 'footer_includes', 'raw' );
?>
</body>
</html>