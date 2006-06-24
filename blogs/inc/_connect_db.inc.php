<?php
/**
 * JEM Stripped extra line feeds and trimmed the text after the closing php tag
 * because it throws an error about sending text after the header was sent.
*/ 
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
$DB = & new DB( $db_config );
?>
