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
$app_version = '3.1.0-alpha';

/**
 * Release date (ISO)
 * @global string
 */
$app_date = '2009-02-21';

/**
 * This is used to check if the database is up to date.
 *
 * This will be incrememented by 100 with each change in {@link upgrade_b2evo_tables()}
 * in order to leave space for maintenance releases.
 *
 * {@internal Before changing this in CVS, it should be discussed! }}
 */
$new_db_version = 9920;

/**
 * Is displayed on the login screen:
 */
$app_banner = '<a href="http://b2evolution.net/"><img src="'.$rsc_url.'img/b2evolution8.png" width="221" height="65" alt="b2evolution" /></a>';

$app_footer_text = '<a href="http://b2evolution.net/" title="'.T_("visit b2evolution's website")
		.'"><strong>b2evolution '.$app_version.'</strong></a>
		&ndash;
		<a href="http://b2evolution.net/about/license.html" class="nobr">'.T_('GPL License').'</a>';

$copyright_text ='<span class="nobr">&copy;2001-2002 by Michel V &amp; others</span>
		&ndash;
		<span class="nobr">&copy;2003-2009 by <a href="http://fplanque.net/">Fran&ccedil;ois</a> <a href="http://fplanque.com/">Planque</a> &amp; <a href="http://b2evolution.net/dev/authors.html">others</a>.</span>';

/**
 * Modules to load
 *
 * This is most useful when extending evoCore with features beyond what b2evolution does and when those features do not
 * fit nicely into a plugin, mostly when they are too large or too complex.
 *
 * Note: a long term goal is to be able to disable some b2evolution feature sets that would not be needed. This should
 * however only be used for large enough feature sets to make it worth the trouble. NO MICROMANAGING here.
 */
$modules = array(
		'_core',
		'sessions',
	);
?>
