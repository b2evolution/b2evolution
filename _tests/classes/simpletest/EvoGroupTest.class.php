<?php
/**
 * This file implements a derived {@link GroupTest} class which
 * provides {@link loadAllTests} and overwrites {@link run()}
 * to detect CLI and use an appropriate reporter then.
 */

/**
 * We use a derived reporter, which shows passes
 */
//require_once( dirname(__FILE__).'/HtmlReporterShowPasses.class.php' );


/**
 * Evo GroupTest class.
 *
 * Provides methods for our group tests.
 */
class EvoGroupTest extends TestSuite
{
	/**
	 * This method loads all of the *.test.php files it can find.
	 *
	 * @uses get_filenames()
	 * @param string The path to where to start looking for tests
	 * @param bool Explore sub-directories for .test.php files
	 */
	function loadAllTests( $startDir, $recursive = true )
	{
		$files = get_filenames( $startDir );

		if( !is_array($files) )
		{
			echo "<p class=\"error\">No test classes found in $startDir..</p>\n";
			return false;
		}

		foreach( $files as $lFile )
		{
			if( !is_dir( $lFile ) )
			{
				if( substr($lFile, -15) == '.simpletest.php' )
				{
					$this->addFile( $lFile );
				}
			}
		}
	}


	/**
	 * Extend run() method to recognize cli mode.
	 *
	 * @param SimpleReporter Reporter for HTML mode
	 * @param SimpleReporter Reporter for CLI mode
	 * @access public
	 */
	function run2( &$htmlReporter, &$cliReporter )
	{
		if( EvoTextReporter::inCli() )
		{
			exit( $this->run( $cliReporter ) ? 0 : 1 );
		}
		$this->run( $htmlReporter );
	}
}

?>
