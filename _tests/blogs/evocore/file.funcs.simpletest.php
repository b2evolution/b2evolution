<?php
/**
 * Tests for file functions.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );

load_funcs('files/model/_file.funcs.php');


/**
 * @package tests
 */
class FileFuncsTestCase extends EvoUnitTestCase
{
	function __construct()
	{
		parent::__construct( 'File functions test' );
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
		while( ($file = readdir($dir_handle)) !== false )
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
		if( ! is_writable(TMPDIR) )
		{
			$this->my_skip_message( 'TMPDIR is not writable.' );
			return;
		}
		$this->assertTrue( mkdir_r( TMPDIR.'test/foo' ) );
		$this->assertTrue( is_dir( TMPDIR.'test/foo' ) );

		$this->assertTrue( mkdir_r( TMPDIR.'test/foo/bar/2' ) );
		$this->assertTrue( is_dir( TMPDIR.'test/foo/bar/2' ) );

		$this->assertTrue( mkdir_r( TMPDIR.'test//foo/bar///0' ) );
		$this->assertTrue( is_dir( TMPDIR.'test//foo/bar///0' ) );

		// does not work (PHP does not allow it):
		// @ini_set('open_basedir', TMPDIR.'test/bar');
	}


	function test_get_canonical_path()
	{
		$this->assertIdentical( get_canonical_path( '' ), '' );
		$this->assertIdentical( get_canonical_path( '/hello/world' ), '/hello/world/' );
		$this->assertIdentical( get_canonical_path( 'hello/world' ), 'hello/world/' );
		$this->assertIdentical( get_canonical_path( '/hello/world/' ), '/hello/world/' );
		$this->assertIdentical( get_canonical_path( '/hello/../world' ), '/world/' );
		$this->assertIdentical( get_canonical_path( 'hello/../world/' ), 'world/' );
		$this->assertIdentical( get_canonical_path( '/hello/../world/../' ), '/' );
		$this->assertIdentical( get_canonical_path( '/hello/world/../../' ), '/' );
		$this->assertIdentical( get_canonical_path( '/../' ), NULL );
		$this->assertIdentical( get_canonical_path( '/../../' ), NULL );	// Even number of ..
		$this->assertIdentical( get_canonical_path( 'C:\\hello\\world\\..\\..\\' ), 'C:/' );
		$this->assertIdentical( get_canonical_path( 'C:\\hello\\world\\..\\..\\..\\' ), NULL );
		$this->assertIdentical( get_canonical_path( 'C:\\hello\\world\\..\\..\\..\\..\\' ), NULL );
		$this->assertIdentical( get_canonical_path( 'C:\\../..\\' ), NULL );
		$this->assertIdentical( get_canonical_path( '/./././././' ), '/' );
		$this->assertIdentical( get_canonical_path( '/.//////.././//./.' ), NULL );
		$this->assertIdentical( get_canonical_path( '/.//////foo/.././//./.' ), '/' );
		$this->assertIdentical( get_canonical_path( '/.//////../foo/.///./.' ), NULL );
		$this->assertIdentical( get_canonical_path( 'C:\\Folder\\.evocache\\..\\' ), 'C:/Folder/' );
		$this->assertIdentical( get_canonical_path( '.evocache' ), '.evocache/' );
		$this->assertIdentical( get_canonical_path( '.evocache/../' ), '' );
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new FileFuncsTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
