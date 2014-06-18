<?php
/**
 * This is the site header include template.
 *
 * If enabled, thiw will be included at the bottom of all skins to provide site wide copyright info for example.
 *
 * @package site_skins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $baseurl;
?>

<div class="sitewide_footer">
	<p><?php
		// Display site footer text
		$site_footer_vars = array(
				'$year$'            => date( 'Y' ),
				'$short_site_name$' => '<a href="'.$baseurl.'">'.$Settings->get( 'notification_short_name' ).'</a>'
			);
		echo str_replace( array_keys( $site_footer_vars ), $site_footer_vars, $Settings->get( 'site_footer_text' ) );
	?></p>
</div>
