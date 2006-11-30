<?php
/**
 * This is the file to do all release tests.
 * @package tests
 */

/**
 * Load config
 */
require_once( dirname(__FILE__).'/../config.simpletest.php' );


/**
 * Our GroupTest
 */
$test = new EvoGroupTest( 'evo-Release Tests Suite');

$test->loadAllTests( dirname(__FILE__) );

$test->run( new HtmlReporter(), new TextReporter() );
#$test->run( new HtmlReporterShowPasses(), new TextReporter() );
?>
