<?php
/**
 * This is the file to do all evo tests.
 */

/**
 * Load config
 */
require_once( dirname(__FILE__).'/config.simpletest.php' );


/**
 * Our GroupTest
 */
$test = new EvoGroupTest( 'evo Tests Suite');

$test->loadAllTests( dirname(__FILE__).'/blogs/' );

#$test->run( new HtmlReporter(), new TextReporter() );
$test->run( new HtmlReporterShowPasses(), new TextReporter() );
?>
