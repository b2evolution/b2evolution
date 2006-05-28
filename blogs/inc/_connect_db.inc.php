<?php
/**
 * This files instantiates the global {@link $DB} object and connects to the database.
 */

/**
 * Load basic settings
 */
require_once dirname(__FILE__).'/../conf/_basic_config.php';

/**
 * Load DB class
 */
require_once dirname(__FILE__).'/_misc/_db.class.php';

/**
 * Database connection (connection opened here)
 *
 * @global DB $DB
 */
$DB = & new DB( $EvoConfig->DB );

?>
