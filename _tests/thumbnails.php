<?php
/**
 * Thumbnails Tests
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package tests
 */
require_once dirname(__FILE__).'/../blogs/conf/_config.php';

require_once $inc_path.'_main.inc.php';

echo '<style type="text/css">
/* Hide all images with 2x ratio */
img.image-2x {
	display: none;
}
/* Devices width double pixel ratio like Retina displays: */
@media
only screen and (-webkit-min-device-pixel-ratio: 2),
only screen and (   min--moz-device-pixel-ratio: 2),
only screen and (     -o-min-device-pixel-ratio: 2/1),
only screen and (        min-device-pixel-ratio: 2),
only screen and (                min-resolution: 192dpi),
only screen and (                min-resolution: 2dppx) {
	/* Hide 1x ratio images and Show all 2x ratio images */
	img {
		display: none;
	}
	img.image-2x {
		display: block;
	}
}
</style>';

echo '<h1>Thumbnail tests</h1>';

global $thumbnail_sizes;

$files = array(
		'monument-valley/bus-stop-ahead.jpg',
		'monument-valley/john-ford-point.jpg',
		'monument-valley/monuments.jpg',
		'monument-valley/monument-valley.jpg',
		'monument-valley/monument-valley-road.jpg',
	);

foreach( $files as $file_path )
{
	$File = new File( 'shared', 0, $file_path );
	foreach( $thumbnail_sizes as $size_name => $size_data )
	{
		echo '<p>Size: <b>'.$size_name.'</b> - Quality: <b>'.$size_data[3].'</b><br/>';

		// Print two <img> tags for 1x and for 2x
		echo $File->get_tag( '', '', '', '', $size_name,
			'original','', '', '', '', '', '#', '',
			2 /* Build 2x version */ );

		echo '</p>';
	}
}
?>
