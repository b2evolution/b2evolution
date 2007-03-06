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
$app_version = '2.0-dev';

/**
 * Release date
 */
$app_date = '2007-03-05';


/**
 * Is displayed on the login screen:
 */
$app_banner = '<a href="http://b2evolution.net/"><img src="'.$rsc_url.'img/b2evolution_minilogo.png" width="231" height="50" alt="b2evolution" /></a>';

/**
 * This is used to check if the database is up to date.
 *
 * This will be incrememented by 100 with each change in {@link upgrade_b2evo_tables()}
 * in order to leave space for maintenance releases.
 *
 * {@internal Before changing this in CVS, it should be discussed! }}
 */
$new_db_version = 9408;


$admin_path_separator = ' :: ';

$app_footer_text = '<a href="http://b2evolution.net/" title="'.T_("visit b2evolution's website").
									'"><img class="middle" src="'.$rsc_url.'img/b2evolution_logo_100.gif" alt="b2evolution" title="'.
									T_("visit b2evolution's website").'" width="100" height="22" /></a>

									'.$app_version.'</a>
		&ndash;
		<a href="http://b2evolution.net/about/license.html" class="nobr">'.T_('GPL License').'</a>
		&ndash;
		<span class="nobr">&copy;2003-2006 by <a href="http://fplanque.net/">Fran&ccedil;ois PLANQUE</a> &amp; others.</span>';

?>