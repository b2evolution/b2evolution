<?php
/**
 * This is the file to do all evoCore tests.
 * @package tests
 */

/**
 * Load config
 */
require_once( dirname(__FILE__).'/../config.simpletest.php' );


/**
 * Our GroupTest
 */
$test = new EvoGroupTest( 'evo-Core Tests Suite');

$test->loadAllTests( dirname(__FILE__) );

$test->run2( new EvoHtmlReporter(), new EvoTextReporter() );
?>
