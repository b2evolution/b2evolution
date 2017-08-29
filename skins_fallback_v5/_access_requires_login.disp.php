<?php
/**
 * This file is the template that displays an access denied for not logged in users
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * @package evoskin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $app_version, $disp, $Collection, $Blog, $skin_links, $francois_links;

// Use a login form for this page
$template_name = '_login.disp.php';

$template_file_path = skin_template_path( $template_name );

if( ! empty( $template_file_path ) )
{ // Load template if it exists
	require $template_file_path;
}
?>