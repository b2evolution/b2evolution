<?php
/**
 * This is the site header include template.
 *
 * If enabled, this will be included at the bottom of all skins to provide site wide copyright info for example.
 * NOTE: each skin is ressponsible for calling siteskin_include( '_site_body_footer.inc.php' );
 *
 * @package site_skins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $baseurl;

$site_footer_text = $Settings->get( 'site_footer_text' );

if( ! empty( $site_footer_text ) )
{ // Display site footer only when it has a text
?>

<footer class="sitewide_footer">
	<p><?php
		// Display site footer text
		$site_footer_vars = array(
				'$year$'            => date( 'Y' ),
				'$short_site_name$' => '<a href="'.$baseurl.'">'.$Settings->get( 'notification_short_name' ).'</a>'
			);
		echo str_replace( array_keys( $site_footer_vars ), $site_footer_vars, $Settings->get( 'site_footer_text' ) );
	?></p>
</footer>
<?php
}
?>