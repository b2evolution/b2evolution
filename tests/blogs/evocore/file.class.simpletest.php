<?php
/**
 * Tests for the {@link File} class.
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );


/**
 * @package tests
 */
class FileTestCase extends FilemanUnitTestCase
{
	function FileTestCase()
	{
		$this->FilemanUnitTestCase( 'File class test' );
	}


	/**
	 * Check if an existing file gets found.
	 */
	function testExist()
	{
		$filePath = $this->createUserFile( '1234' );

		$File = new File( 'user', 1, basename( $filePath ) );

		$this->assertTrue( $File->exists(), 'File exists' );

		$this->assertTrue( $File->get_size() == 4, 'File size is correct' );
	}


	/**
	 * Test, if we can create a file.
	 */
	function testCreateAndDelete()
	{
		// create a temporary file and just delete it again:
		$temp_path = $this->createUserFile();
		unlink( $temp_path );
		$temp_name = basename($temp_path);

		$File = new File( 'user', 1, $temp_name );
		$this->assertFalse( $File->exists(), 'File does not exist.' );

		$File->create();

		$this->assertTrue( $File->exists(), 'File exists after create().' );
		$this->assertTrue( file_exists( $temp_path ), 'File really exists.' );

		if( file_exists( $temp_path ) )
		{
			$File->unlink();

			$this->assertFalse( $File->exists(), 'File thinks it is unlinked.' );
			$this->assertFalse( file_exists( $temp_path ), 'File is really unlinked.' );
		}
	}


	/**
	 * Test, if dirs are recognized correctly
	 */
	function testIsDir()
	{
		$Dir = new File( 'user', 1, '' );
		$this->assertTrue( $Dir->is_dir(), 'Dir is dir.' );

		$temp_path = $this->createUserFile();
		$File = new File( 'user', 1, $temp_path );
		$this->assertFalse( $File->is_dir(), 'File is no dir.' );
	}


	/**
	 * Test get_ext()
	 */
	function testGetExt()
	{
		$File =& new File( 'user', 1, 'abc.def' );
		$this->assertEqual( $File->get_ext(), 'def', 'Simple file extension recognized.' );

		$File =& new File( 'user', 1, 'abc.noext.def' );
		$this->assertEqual( $File->get_ext(), 'def', 'File extension recognized.' );
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new FileTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
