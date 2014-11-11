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
			$output = NULL;
			exec('php -l '.escapeshellarg($filename).' 2>&1', $output, $return_var);
			if( $return_var !== 0 )
			{
				$badfiles[] = array($filename, $output);
			}
		}
		if( ! empty($badfiles) )
		{
			echo '<h2>Files which have PHP syntax errors</h2>';
			echo "<ul>\n";
			foreach( $badfiles as $bfile )
			{
				echo '<li>'.$bfile[0].'<br /><pre>'.var_export($bfile[1], true)."</pre></li>\n";
			}
			echo "</ul>";
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
