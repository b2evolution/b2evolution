<?php
/**
 * This is the BODY header include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://manual.b2evolution.net/Skins_2.0}
 *
 * This is meant to be included in a page template.
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( file_exists( $ads_current_skin_path.'_body_header.inc.php' ) )
{	// The skin has a customized handler, use that one instead:
	require $ads_current_skin_path.'_body_header.inc.php';
	return;
}

// By default, this does nothin. It's just here as a placeholder. It should be overriden by skins if needed.

?>