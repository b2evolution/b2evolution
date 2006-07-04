<?php
/**
 * This files instantiates the global {@link $DB} object and connects to the database.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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