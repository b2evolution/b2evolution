<?php
/**
 * Tests for the {@link AbstractSettings} class.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../config.simpletest.php' );


/**
 * @package tests
 */
class SecurityChecksTestCase extends EvoUnitTestCase
{
	function SecurityChecksTestCase()
	{
		$this->EvoUnitTestCase( 'Release tests' );
	}


	/**
	 * Get all files below a path, excluding symlinks.
	 * @return array
	 */
	function get_files_without_symlinks( $path, $pattern = '~\.(php|inc|html?)$~' )
	{
		$r = array();
		foreach( get_filenames( $path, false, true, true, false ) as $dir )
		{
			if( is_link($dir) )
			{
				continue;
			}

			// files:
			$files = get_filenames( $dir, true, false, true, false );
			if( $pattern )
			{
				foreach( $files as $k => $v )
				{
					if( ! preg_match( $pattern, $v ) )
					{ // Not a PHP file (include HTM(L), because it gets parsed sometimes, too)
						unset($files[$k]);
					}
				}
			}
			$r = array_merge( $r, $files );

			// subdirs:
			foreach( get_filenames( $dir, false, true, true, false ) as $subdir )
			{
				$r = array_merge( $r, $this->get_files_without_symlinks($subdir));
			}
		}
		return $r;
	}


	function testDefinedOrDie()
	{
		global $basepath;

		$search = "if\( \s* ! \s* defined\( \s* 'EVO_MAIN_INIT' \s* \) \s* \) \s* die\( .*? \);";

		$files = $this->get_files_without_symlinks($basepath);
		$badfiles = array();
		foreach( $files as $filename )
		{
			// TODO: exclude files here, where the defined check is ok to be missing (continue)

			$buffer = file_get_contents($filename);

			$pos_phptag = strpos($buffer, '<?php');
			if( $pos_phptag === false )
			{ // not a PHP file
				continue;
			}

			if( ! preg_match( '~^<\?php \s* /\* .*? \*/ \s* '.$search.'~isx', substr($buffer, $pos_phptag) ) )
			{
				$badfiles[] = $filename;
			}
		}

		if( ! empty($badfiles) )
		{
			echo '<h1>Files which seem to miss the check for defined(EVO_MAIN_INIT)</h1>';
			pre_dump( $badfiles );
		}
		$this->assertFalse( $badfiles );
	}
}



if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new SecurityChecksTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
