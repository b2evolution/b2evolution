<?php

class FilemanUnitTestCase extends UnitTestCase
{
	var $tempFiles = array();

	/**
	 * Create a temp file in TMPDIR.
	 *
	 * @param integer
	 * @param string Name of the file in TMPDIR
	 * @return false|string The filename
	 */
	function createTempFile( $content = '', $name = NULL, $size = NULL )
	{
		if( $name === NULL )
		{
			$filepath = tempnam( TMPDIR, 'TMP' );
		}
		else
		{
			$filepath = TMPDIR.$name;
		}

		if( !($fh = fopen( $filepath, 'w' )) )
		{
			return false;
		}

		if( $size !== NULL )
		{
			$content = '';
			for( $i = 0; $i < $size; $i++ )
			{
				$str = 'X';
			}
		}

		fwrite( $fh, $content );
		fclose( $fh );

		$this->tempFiles[] = $filepath;

		return $filepath;
	}


	/**
	 * Unlink created temp files.
	 *
	 * Call it in {@link tearDown()} if you use {@link createTempFile()}.
	 */
	function unlinkTempfiles()
	{
		while( $tempPath = array_pop( $this->tempFiles ) )
		{
			unlink( $tempPath );
		}

		parent::tearDown();
	}
}

?>
