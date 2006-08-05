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
$app_version = '1.9-dev';

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
$new_db_version = 9300;


$admin_path_seprator = ' :: ';
$app_admin_logo = '<a href="http://b2evolution.net/" title="'.T_("visit b2evolution's website").
									'"><img id="evologo" src="'.$rsc_url.'img/b2evolution_minilogo2.png" alt="b2evolution" title="'.
									T_("visit b2evolution's website").'" width="185" height="40" /></a>';

$app_footer_text = '<a href="http://www.b2evolution.net"><strong><span style="color:#333">b</span><sub><span style="color:#f90;margin-top:2ex;">2</span></sub><span style="color:#333">e</span><span style="color:#543">v</span><span style="color:#752">o</span><span style="color:#962">l</span><span style="color:#b72">u</span><span style="color:#c81">t</span><span style="color:#d91">i</span><span style="color:#e90">o</span><span style="color:#f90">n</span></strong> '.$app_version.'</a>
		&ndash;
		<a href="http://b2evolution.net/about/license.html" class="nobr">'.T_('GPL License').'</a>
		&ndash;
		<span class="nobr">&copy;2001-2002 by <a href="http://cafelog.com/">Michel V</a> &amp; others</span>
		&ndash;
		<span class="nobr">&copy;2003-2006 by <a href="http://fplanque.net/">Fran&ccedil;ois PLANQUE</a> &amp; others.</span>';

?>