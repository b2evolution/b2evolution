<?php
/**
 * Tests for the {@link Filelist} class
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );

require_once( EVODIR.'/blogs/evocore/_filelist.class.php' );


class FilelistTestCase extends FilemanUnitTestCase
{
	function FileTestCase()
	{
		$this->UnitTestCase( 'Filelist class test' );
	}

	function setUp()
	{
		$this->Filelist = new Filelist();
	}


	function tearDown()
	{
	}


	/**
	 * Tests addFileByPath()
	 */
	function testAddFileByPath()
	{
		$r = $this->Filelist->addFileByPath( TMPDIR.'a' );
		$this->assertEqual( $r, true, 'File was added.' );

		$this->assertEqual( $this->Filelist->count(), 1, 'Filecount matches.' );

		$File =& $this->Filelist->getFileByPath( TMPDIR.'a' );
		$this->assertIsA( $File, 'file', 'We got a File.' );

		$this->assertEqual( $File->getName(), 'a', 'File has the same name.' );
	}


	/**
	 * Test if we get a reference to the same file back.
	 */
	function testFileReference()
	{
		$File = new File( 'a', TMPDIR );
		$id = $File->getID();

		$r = $this->Filelist->addFile( $File );
		$this->assertEqual( $r, true, 'File added.' );
		$this->assertEqual( $this->Filelist->count(), 1, 'Filecount matches.' );

		$GetFile =& $this->Filelist->getFileByID( $id );
		#$File =& $this->Filelist->getFileByID( $id );
		$this->assertReference( $File, $GetFile, 'Got the same file.' );
		$this->assertReference( $File, $GetFile, 'Got the same file.' );
	}


	/**
	 *
	 */
	function testRemoveFromList()
	{
		$File =& new File( 'a', TMPDIR );
		$r = $this->Filelist->addFile( $File );

		$this->assertEqual( $r, true, 'File added.' );
		$this->assertEqual( $this->Filelist->count(), 1, 'Count ok.' );

		$this->Filelist->removeFromList( $File );
		$this->assertEqual( $this->Filelist->count(), 1, 'File removed.' );
	}

	/**
	 *
	 */
	function testRemoveFromListOrder()
	{
		$FileA = new File( 'a', TMPDIR );
		$FileB = new File( 'b', TMPDIR );
		$r = $this->Filelist->addFile( $FileA );
		$r = $this->Filelist->addFile( $FileB );

		$this->assertReference( $this->Filelist->getFileByIndex(0), $FileA, 'First file ok.' );
		$this->assertReference( $this->Filelist->getFileByIndex(1), $FileB, 'Second file ok.' );

		$r = $this->Filelist->removeFromList( $FileA );
		$this->assertTrue( $r, true, 'Remove ok.' );
		$this->assertTrue( $this->Filelist->count(), 1, 'Count after remove ok.' );

		$this->assertReference( $this->Filelist->getFileByIndex(0), $FileB, 'First file ok.' );
		$this->assertFalse( $this->Filelist->getFileByIndex(1), 'Second file ok (not existing).' );
	}


	/**
	 *
	 */
	function testSort()
	{
		$FileB = new File( 'b', TMPDIR );
		$this->Filelist->addFile( $FileB );
		$FileA = new File( 'a', TMPDIR );
		$this->Filelist->addFile( $FileA );

		$this->Filelist->sort( 'name', true );

		$this->assertReference( $this->Filelist->getFileByIndex(0), $FileA, 'First file sorted ok.' );
		$this->assertReference( $this->Filelist->getFileByIndex(1), $FileB, 'Second file sorted ok.' );

		// sort dirs at top:
		$this->Filelist->_isDir = true;
		$this->Filelist->sort( 'name', true, true );
		$this->assertReference( $this->Filelist->getFileByIndex(0), $FileB, 'Directory at top.' );
		$this->assertReference( $this->Filelist->getFileByIndex(1), $FileA, 'File below directory.' );
	}

}


if( !isset( $test ) )
{ // Called directly, run the TestCase alone
	$test = new FileTestCase();
	$test->run( new HtmlReporter() );
	unset( $test );
}

?>
