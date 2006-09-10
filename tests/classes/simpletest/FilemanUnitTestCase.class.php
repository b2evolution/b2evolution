<?php
/**
 * This file implements the class for Filemanager unit tests.
 */


/**
 * The class for Filemanager unit tests.
 */
class FilemanUnitTestCase extends EvoUnitTestCase
{
	/**
	 * Remember created files.
	 */
	var $tempFiles = array();


	/**
	 * Create a file for a given user.
	 *
	 * @return string|false the file name of the created file
	 */
	function createUserFile( $content = '', $name = '', $user_ID = 1 )
	{
		global $FileRootCache;

		$FileRoot = & $FileRootCache->get_by_type_and_ID( 'user', $user_ID );

		if( ! $FileRoot )
		{
			trigger_error( 'Cannot get FileRoot for user #'.$user_ID );
			return false;
		}

		if( empty($name) )
		{
			$name = basename( tempnam( $FileRoot->ads_path, 'TMP' ) );
		}

		return $this->createFile( $FileRoot->ads_path.$name, $content );
	}


	/**
	 * Create a temp file in TMPDIR.
	 *
	 * @param string Content to write into the file
	 * @param string Name of the file in TMPDIR
	 * @return false|string The filename
	 */
	function createTempFile( $content = '', $name = NULL )
	{
		if( $name === NULL )
		{
			$filepath = tempnam( TMPDIR, 'TMP' );
		}
		else
		{
			$filepath = TMPDIR.$name;
		}

		return $this->createFile( $filepath, $content, $size );
	}


	/**
	 * Create a file.
	 *
	 * @param string Path of the file to write to
	 * @param string Content to write into the file
	 * @return false|string The filename
	 */
	function createFile( $path, $content = '' )
	{
		if( !($fh = @fopen( $path, 'w' )) )
		{
			trigger_error( "Cannot create file '$path'!" );
			return false;
		}

		fwrite( $fh, $content );
		fclose( $fh );

		$this->tempFiles[] = $path;

		return $path;
	}


	/**
	 * Unlink created temp files.
	 *
	 * Call it in {@link tearDown()} if you use {@link createTempFile()}.
	 */
	function unlinkCreatedFiles()
	{
		while( $tempPath = array_pop( $this->tempFiles ) )
		{
			@unlink( $tempPath );
		}

		parent::tearDown();
	}
}

?>
