<?php
/**
 * This file is the template that displays an access denied for not logged in users
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * @package evoskin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $app_version, $disp, $Blog, $skin_links, $francois_links;
global $ads_current_skin_path, $skins_path;

// Use a login form for this page
$template_name = '_login.disp.php';

if( file_exists( $ads_current_skin_path.$template_name ) )
{ // The skin has a customized handler, use that one instead:
	$template_file_path = $ads_current_skin_path.$template_name;
}
elseif( file_exists( $skins_path.$template_name ) )
{ // Use the default/fallback template:
	$template_file_path = $skins_path.$template_name;
}

if( ! empty( $template_file_path ) )
{ // Load template if it exists
	require $template_file_path;
}
?>