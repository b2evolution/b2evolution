<?php
/**
 * This is b2evolution's main config file, which just includes all the other
 * config files.
 *
 * See {@link _basic_config.php} for the basic settings.
 *
 * @package conf
 */


require_once  dirname(__FILE__).'/_basic_config.php';   // basic settings
require_once  dirname(__FILE__).'/_advanced.php';       // advanced settings
require_once  dirname(__FILE__).'/_locales.php';        // locale settings
require_once  dirname(__FILE__).'/_formatting.php';     // formatting settings
require_once  dirname(__FILE__).'/_admin.php';          // admin settings
require_once  dirname(__FILE__).'/_stats.php';          // stats/hitlogging settings
require_once  dirname(__FILE__).'/_application.php';    // application settings
if( file_exists(dirname(__FILE__).'/_overrides_TEST.php') )
{
	include_once dirname(__FILE__).'/_overrides_TEST.php'; // Override for testing in there
}
?>