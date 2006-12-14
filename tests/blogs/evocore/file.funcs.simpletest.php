<?php
/**
 * Tests for file functions.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );


global $inc_path;

require_once( $inc_path.'MODEL/files/_file.funcs.php' );


/**
 * @package tests
 */
class FileFuncsTestCase extends EvoUnitTestCase
{
	function FileFuncsTestCase()
	{
		$this->EvoUnitTestCase( 'File functions test' );
	}


	/**
	 * Delete a dir recursively.
	 * @todo dh> Move to some TestCase base class or even to evoCore
	 */
	function unlink_dir_recursive( $dir )
	{
		if( ! is_dir($dir) )
		{
			return false;
		}

		$dir_handle = opendir($dir);
		if( ! $dir_handle )
		{
			return false;
		}
		while( $file = readdir($dir_handle) )
		{
			if( $file == '.' || $file == '..' )
			{
				continue;
			}

			if( is_dir($dir.'/'.$file) )
			{
				$this->unlink_dir_recursive($dir.'/'.$file);
			}
			else
			{
				unlink( $dir.'/'.$file );
			}
		}
		closedir($dir_handle);
		rmdir($dir);
		return true;
	}


	/**
	 * Remove "test" dir in TMPDIR
	 */
	function tearDown()
	{
		$this->unlink_dir_recursive(TMPDIR.'test');
	}


	/**
	 * Tests {@link mkdir_r()}
	 */
	function test_mkdir_r()
	{
		$this->assertTrue( mkdir_r( TMPDIR.'test/foo' ) );
		$this->assertTrue( is_dir( TMPDIR.'test/foo' ) );

		$this->assertTrue( mkdir_r( TMPDIR.'test/foo/bar/2' ) );
		$this->assertTrue( is_dir( TMPDIR.'test/foo/bar/2' ) );

		// does not work (PHP does not allow it):
		// ini_set('open_basedir', TMPDIR.'test/bar');
	}

}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new FileFuncsTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
