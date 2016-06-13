<?php
/**
 * This is b2evolution's application config file.
 *
 * @package conf
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


$app_name = 'b2evolution';
$app_shortname = 'b2evo';

/**
 * The version of the application.
 * Note: This has to be compatible with {@link http://us2.php.net/en/version-compare}.
 * @global string
 */
$app_version = '6.7.4-stable';

/**
 * Release date (ISO)
 * @global string
 */
$app_date = '2016-06-14';

/**
 * Long version string for checking differences
 */
$app_version_long = $app_version.'-'.$app_date;

/**
 * This is used to check if the database is up to date.
 *
 * This will be incrememented by 5 with each change in {@link upgrade_b2evo_tables()}
 * in order to leave space for maintenance releases.
 *
 * {@internal Before changing this in CVS, it should be discussed! }}
 */
$new_db_version = 11770;

/**
 * Minimum PHP version required for b2evolution to function properly. It will contain each module own minimum PHP version as well.
 * @global array
 */
$required_php_version = array( 'application' => '5.0' );

/**
 * Minimum MYSQL version required for b2evolution to function properly. It will contain each module own minimum MYSQL version as well.
 * @global array
 */
$required_mysql_version = array( 'application' => '5.0.3' );

/**
 * Is displayed on the login screen:
 */
$app_footer_text = '<a href="http://b2evolution.net/" title="visit b2evolution\'s website"><strong>b2evolution '.$app_version.'</strong></a>
		&ndash;
		<a href="http://b2evolution.net/about/gnu-gpl-license" class="nobr">GPL License</a>';

$copyright_text = '<span class="nobr">&copy;2003-2016 by <a href="http://fplanque.net/">Fran&ccedil;ois</a> <a href="http://fplanque.com/">Planque</a> &amp; <a href="http://b2evolution.net/about/about-us">others</a>.</span>';

/**
 * Do you want to display the help links to online documentation?
 *
 * @global boolean
 */
$online_help_links = true;

/**
 * Modules to load
 *
 * This is most useful when extending evoCore with features beyond what b2evolution does and when those features do not
 * fit nicely into a plugin, mostly when they are too large or too complex.
 *
 * Note: a long term goal is to be able to disable some b2evolution feature sets that would not be needed. This should
 * however only be used for large enough feature sets to make it worth the trouble. NO MICROMANAGING here.
 * Try commenting out the 'collections' module to revert to pretty much just evocore.
 */
$modules = array(
		'_core',
		'collections',  // TODO: installer won't work without this module
		'polls',
		'files',
		'sessions',
		'messaging',
		'maintenance',
	);
?>
