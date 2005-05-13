<?php
/**
 * Tests for the {@link Filelist} class
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );

require_once( EVODIR.'/blogs/evocore/_filelist.class.php' );


/**
 *
 */
class FilelistTestCase extends FilemanUnitTestCase
{
	function FilelistTestCase()
	{
		$this->FilemanUnitTestCase( 'Filelist class test' );
	}


	function setUp()
	{
		parent::setUp();

		$this->Filelist = new Filelist( false );
	}


	function tearDown()
	{
		parent::tearDown();
	}


	/**
	 * Tests add_by_path()
	 */
	function testAddFileByPath()
	{
		$r = $this->Filelist->add_by_path( TMPDIR.'a' );
		$this->assertEqual( $r, true, 'File was added.' );

		$this->assertEqual( $this->Filelist->count(), 1, 'Filecount matches.' );

		$File = & $this->Filelist->get_by_root_and_path( 'absolute', 0, TMPDIR.'a' );
		$this->assertIsA( $File, 'file', 'We got a File.' );

		$this->assertEqual( $File->get_name(), 'a', 'File has the same name.' );
	}


	/**
	 * Test if we get a reference to the same file back.
	 */
	function testFileReference()
	{
		$File = new File( 'absolute', 0, 'a', TMPDIR );
		$id = $File->get_md5_ID();

		$r = $this->Filelist->add( $File );
		$this->assertEqual( $r, true, 'File added.' );
		$this->assertEqual( $this->Filelist->count(), 1, 'Filecount matches.' );

		$GetFile =& $this->Filelist->get_by_md5_ID( $id );
		#$File =& $this->Filelist->get_by_md5_ID( $id );
		$this->assertReference( $File, $GetFile, 'Got the same file.' );
		$this->assertReference( $File, $GetFile, 'Got the same file.' );
	}


	/**
	 *
	 */
	function testRemoveFromList()
	{
		$File =& new File( 'absolute', 0, 'a', TMPDIR );
		$r = $this->Filelist->add( $File );

		$this->assertEqual( $r, true, 'File added.' );
		$this->assertEqual( $this->Filelist->count(), 1, 'Count ok.' );

		$this->Filelist->remove( $File );
		$this->assertEqual( $this->Filelist->count(), 1, 'File removed.' );
	}

	/**
	 *
	 */
	function testRemoveFromListOrder()
	{
		$FileA = new File( 'absolute', 0, 'a', TMPDIR );
		$FileB = new File( 'absolute', 0, 'b', TMPDIR );
		$r = $this->Filelist->add( $FileA );
		$r = $this->Filelist->add( $FileB );

		$this->assertReference( $this->Filelist->getFileByIndex(0), $FileA, 'First file ok.' );
		$this->assertReference( $this->Filelist->getFileByIndex(1), $FileB, 'Second file ok.' );

		$r = $this->Filelist->remove( $FileA );
		$this->assertTrue( $r, true, 'Remove ok.' );
		$this->assertTrue( $this->Filelist->count(), 1, 'Count after remove ok.' );

		$this->assertReference( $this->Filelist->getFileByIndex(0), $FileB, 'First file ok.' );
		$this->assertFalse( $this->Filelist->getFileByIndex(1), 'Second file ok (not existing).' );
	}


	/**
	 * Insert three files and sort them with different settings.
	 */
	function testSort()
	{
		$FileA = new File( 'absolute', 0, 'a', TMPDIR );
		$this->Filelist->add( $FileA );
		$FileB = new File( 'absolute', 0, 'b', TMPDIR );
		$this->Filelist->add( $FileB );
		$FileC = new File( 'absolute', 0, 'c', TMPDIR );
		$this->Filelist->add( $FileC );


		// ascending, dirs not at top:
		$this->Filelist->sort( 'name', true, false );

		$this->assertReference( $this->Filelist->getFileByIndex(0), $FileA, 'First file sorted ok.' );
		$this->assertReference( $this->Filelist->getFileByIndex(1), $FileB, 'Second file sorted ok.' );
		$this->assertReference( $this->Filelist->getFileByIndex(2), $FileC, 'Third file sorted ok.' );


		// descending, dirs not at top:
		$this->Filelist->sort( 'name', false, false );

		$this->assertReference( $this->Filelist->getFileByIndex(0), $FileC, 'First file sorted ok.' );
		$this->assertReference( $this->Filelist->getFileByIndex(1), $FileB, 'Second file sorted ok.' );
		$this->assertReference( $this->Filelist->getFileByIndex(2), $FileA, 'Third file sorted ok.' );


		// Make $FileA a directory
		$FileA->_is_dir = true;

		// descending, dirs at top:
		$this->Filelist->sort( 'name', false, true );

		$this->assertReference( $this->Filelist->getFileByIndex(0), $FileA, 'Directory at top.' );
		$this->assertReference( $this->Filelist->getFileByIndex(1), $FileC, 'First File below directory.' );
		$this->assertReference( $this->Filelist->getFileByIndex(2), $FileB, 'Second File below directory.' );


		// ascending, dirs not at top:
		$this->Filelist->sort( 'name', true, false );

		$this->assertReference( $this->Filelist->getFileByIndex(0), $FileA, 'First file sorted ok.' );
		$this->assertReference( $this->Filelist->getFileByIndex(1), $FileB, 'Second file sorted ok.' );
		$this->assertReference( $this->Filelist->getFileByIndex(2), $FileC, 'Third file sorted ok.' );
	}

}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new FilelistTestCase();
	$test->run( new HtmlReporter() );
	unset( $test );
}

?>
