<?php
/**
 * Tests for the thumbnail funcs.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );


/**
 * @package tests
 */
class ThumbnailTestCase extends EvoUnitTestCase
{
	function __construct()
	{
		parent::__construct( 'Thumbnails test' );
	}

	/**
	 * Test testThumbnails()
	 */
	function testThumbnails()
	{
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
	}
}


if( ! isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new ThumbnailTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>