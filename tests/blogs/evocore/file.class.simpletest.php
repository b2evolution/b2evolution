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
 *
 */
class FileTestCase extends FilemanUnitTestCase
{
	function FileTestCase()
	{
		$this->FilemanUnitTestCase( 'File class test' );
	}


	function setUp()
	{
		parent::setUp();
	}


	function tearDown()
	{
		parent::tearDown();

		$this->unlinkTempfiles(); // unlink temp files
	}


	/**
	 * Check if an existing file gets found.
	 */
	function testExist()
	{
		$filePath = $this->createTempFile( '1234' );

		$File = new File( basename( $filePath ), dirname( $filePath ) );

		$this->assertTrue( $File->exists(), 'File exists' );

		$this->assertTrue( $File->get_size() == 4, 'File size is correct' );
	}


	/**
	 * Test, if we can create a file.
	 */
	function testCreateAndDelete()
	{
		@unlink( TMPDIR.'tempfile.tmp' );

		$File = new File( 'tempfile.tmp', TMPDIR );
		$this->assertFalse( $File->exists(), 'File does not exist.' );

		$File->create();

		$this->assertTrue( $File->exists(), 'File exists.' );
		$this->assertTrue( file_exists( TMPDIR.'tempfile.tmp' ), 'File really exists.' );

		$File->unlink();

		$this->assertFalse( $File->exists(), 'File think it is unlinked.' );
		$this->assertFalse( file_exists( TMPDIR.'tempfile.tmp' ), 'File is really unlinked.' );
	}


	/**
	 * Test, if dirs are recognized correctly
	 */
	function testIsDir()
	{
		$Dir = new File( basename(TMPDIR), dirname(TMPDIR) );
		$this->assertTrue( $Dir->is_dir(), 'Dir is dir.' );

		$this->tempName = tempnam( 'temp', 'TMP' );
		$File = new File( basename( $this->tempName ), TMPDIR );
		$this->assertFalse( $File->is_dir(), 'File is no dir.' );
	}


	/**
	 * Test getExt()
	 */
	function testGetExt()
	{
		$File =& new File( 'abc.def', TMPDIR );
		$this->assertEqual( $File->getExt(), 'def', 'Simple file extension recognized.' );

		$File =& new File( 'abc.noext.def', TMPDIR );
		$this->assertEqual( $File->getExt(), 'def', 'File extension recognized.' );
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new FileTestCase();
	$test->run( new HtmlReporter() );
	unset( $test );
}
?>
