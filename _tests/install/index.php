<?php
/**
 * This is the file to do all install tests.
 * @package tests
 */

/**
 * Load config
 */
require_once( dirname(__FILE__).'/../config.simpletest.php' );


/**
 * Our GroupTest
 */
$test = new EvoGroupTest( 'evo-Install Tests Suite');

$test->loadAllTests( dirname(__FILE__) );

$test->run2( new EvoHtmlReporter(), new EvoTextReporter() );
?>
