<?php
/**
 * Tests for the {@link File} class.
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );

require_once( EVODIR.'/blogs/evocore/_file.class.php' );


/**
 * @package tests
 */
class FileTestCase extends FilemanUnitTestCase
{
	function FileTestCase()
	{
		$this->FilemanUnitTestCase( 'File class test' );
	}


	function setUp()
	{
		global $Settings;

		parent::setUp();


		$this->_old_fm_enable_roots_user = $Settings->get('fm_enable_roots_user');
		$Settings->set( 'fm_enable_roots_user', 1 );
	}


	function tearDown()
	{
		global $Settings;
		parent::tearDown();

		$Settings->set( 'fm_enable_roots_user', $this->_old_fm_enable_roots_user );
		$this->unlinkCreatedFiles();
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
		global $FileRootCache;

		// create a temporary file and just delete it again:
		$temp_path = $this->createUserFile();
		@unlink( $temp_path );
		$temp_name = basename($temp_path);

		$File = new File( 'user', 1, $temp_name );
		$this->assertFalse( $File->exists(), 'File does not exist.' );

		$File->create();

		$this->assertTrue( $File->exists(), 'File exists after create().' );
		$this->assertTrue( file_exists( $temp_path ), 'File really exists.' );

		$File->unlink();

		$this->assertFalse( $File->exists(), 'File thinks it is unlinked.' );
		$this->assertFalse( file_exists( $temp_path ), 'File is really unlinked.' );
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
	$test->run( new HtmlReporter() );
	unset( $test );
}
?>
