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
 * @package tests
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

		$this->Filelist = new Filelist( false, 'user', 1 );
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
		$r = $this->Filelist->add_by_subpath( 'a' );
		$this->assertEqual( $r, true, 'File was added.' );

		$this->assertEqual( $this->Filelist->count(), 1, 'Filecount matches.' );

		$File = & $this->Filelist->get_next();
		$this->assertIsA( $File, 'file', 'We got a File.' );

		$this->assertEqual( $File->get_name(), 'a', 'File has the same name.' );
	}


	/**
	 * Test if we get a reference to the same file back.
	 */
	function testFileReference()
	{
		$File = new File( 'user', 1, 'a' );
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
		$File = new File( 'user', 1, 'a' );
		$r = $this->Filelist->add( $File );

		$this->assertEqual( $r, true, 'File added.' );
		$this->assertEqual( $this->Filelist->count(), 1, 'Count ok.' );

		$this->Filelist->remove( $File );
		$this->assertEqual( $this->Filelist->count(), 0, 'File removed.' );
	}


	/**
	 * Tests, if get_next() works correctly when removing a file
	 */
	function testRemoveInsideOfGetNext()
	{
		$Files = array(
				new File( 'user', 1, 'a' ),
				new File( 'user', 1, 'b' ),
				new File( 'user', 1, 'c' ),
				new File( 'user', 1, 'd' ),
				new File( 'user', 1, 'e' ),
			);
		$File_to_be_removed_before_get_next = new File( 'user', 1, 'hmm' );

		$this->Filelist->add( $Files[0] );
		$this->Filelist->add( $Files[1] );
		$this->Filelist->add( $File_to_be_removed_before_get_next );
		$this->Filelist->add( $Files[2] );
		$this->Filelist->add( $Files[3] );
		$this->Filelist->add( $Files[4] );

		// we remove the third one even before get_next() loop:
		$this->Filelist->remove( $File_to_be_removed_before_get_next );

		$loop_count = 0;
		while( $l_File = & $this->Filelist->get_next() )
		{
			$this->assertReference( $l_File, $Files[$loop_count], 'File is the correct one (#'.$loop_count.')' );
			if( $loop_count == 0 || $loop_count == 2 || $loop_count == 4 )
			{ // remove the file at the beginning, in the middle and the last one
				$this->Filelist->remove( $l_File );
			}
			$loop_count++;
		}

		$this->assertEqual( $loop_count, count( $Files ), 'All files traversed.' );
	}


	/**
	 * Tests counters after removing
	 */
	function testCountersAfterRemove()
	{
		$File = new File( 'user', 1, 'a' );
		$this->Filelist->add( $File );

		$this->assertEqual( $this->Filelist->count_files(), 1 );

		$get_File = & $this->Filelist->get_next();
		$this->Filelist->remove( $get_File );

		$this->assertEqual( $this->Filelist->count_files(), 0 );

		$this->assertEqual( $this->Filelist->get_next(), false, 'No file returned.' );
	}


	/**
	 *
	 */
	function testRemoveFromListOrder()
	{
		$FileA = new File( 'user', 1, 'a' );
		$FileB = new File( 'user', 1, 'b' );
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
		$FileA = new File( 'user', 1, 'a' );
		$this->Filelist->add( $FileA );
		$FileB = new File( 'user', 1, 'b' );
		$this->Filelist->add( $FileB );
		$FileC = new File( 'user', 1, 'c' );
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
