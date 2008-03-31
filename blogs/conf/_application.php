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
 * Note: This has to be compatible to {@link http://us2.php.net/en/version-compare}.
 * @global string
 */
$app_version = '2.5.0-beta';

/**
 * Release date
 */
$app_date = '2008-01-24';

/**
 * This is used to check if the database is up to date.
 *
 * This will be incrememented by 100 with each change in {@link upgrade_b2evo_tables()}
 * in order to leave space for maintenance releases.
 *
 * {@internal Before changing this in CVS, it should be discussed! }}
 */
$new_db_version = 9800;

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
		<span class="nobr">&copy;2003-2008 by <a href="http://fplanque.net/">Fran&ccedil;ois</a> <a href="http://fplanque.com/">Planque</a> &amp; <a href="http://b2evolution.net/dev/authors.html">others</a>.</span>';

?>
