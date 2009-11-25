<?php
/**
 * Tests for syntax errors in PHP files.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../config.simpletest.php' );


/**
 * @package tests
 */
class SyntaxTestCase extends EvoUnitTestCase
{
	const slow_testcase = true;

	function __construct()
	{
		parent::__construct( 'Syntax tests' );
	}


	function test_php_syntax_errors()
	{
		global $basepath;

		$files = $this->get_files_without_symlinks($basepath);
		$badfiles = array();
		foreach( $files as $filename )
		{
			exec('php -l '.escapeshellarg($filename), $output, $return_var);
			if( $return_var !== 0 )
			{
				$badfiles[] = $filename;
			}
		}
		if( ! empty($badfiles) )
		{
			echo '<h2>Files which have PHP syntax errors</h2>';
			echo "\n<ul><li>\n";
			echo implode( "\n</li><li>\n", $badfiles );
			echo "\n</li></ul>\n";
		}
		$this->assertFalse( $badfiles );
	}
}



if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new SyntaxTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
