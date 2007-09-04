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
$app_version = '2.0.0-alpha';

/**
 * Release date
 */
$app_date = '2007-09-04';

/**
 * This is used to check if the database is up to date.
 *
 * This will be incrememented by 100 with each change in {@link upgrade_b2evo_tables()}
 * in order to leave space for maintenance releases.
 *
 * {@internal Before changing this in CVS, it should be discussed! }}
 */
$new_db_version = 9416;

/**
 * Is displayed on the login screen:
 */
$app_banner = '<a href="http://b2evolution.net/"><img src="'.$rsc_url.'img/b2evolution_minilogo.png" width="231" height="50" alt="b2evolution" /></a>';

$app_footer_text = '<a href="http://b2evolution.net/" title="'.T_("visit b2evolution's website")
		.'"><strong>b2evolution '.$app_version.'</strong></a>
		&ndash;
		<a href="http://b2evolution.net/about/license.html" class="nobr">'.T_('GPL License').'</a>';

$copyright_text ='<span class="nobr">&copy;2001-2002 by Michel V &amp; others</span>
		&ndash;
		<span class="nobr">&copy;2003-2007 by <a href="http://fplanque.net/">Fran&ccedil;ois</a> <a href="http://fplanque.com/">Planque</a> &amp; <a href="http://b2evolution.net/dev/authors.html">others</a>.</span>';


/**
 * Here you can give credit where credit is due ;)
 * These will appear in the footer of all skins (if the skins are compatible)
 * You can also add site sponsors here.
 *
 * If you can add your own credits without removing the samples below, you'll be very cool :))
 * Please leave the credits at the bottom of your pages to make sure your blog gets listed on b2evolution.net
 *
 * Note: some plugins may add their own credit at the end of this array.
 * (Not recommended for plugins with potential security weaknesses)
 */
$credit_links = array(
	array( '' => array( array( 10, 'http://evocore.net/', 'PHP framework' ),
											array( 100, 'http://b2evolution.net/', array( array( 85, 'blog software' ), array( 90, 'blog soft' ), array( 95, 'blog tool' ), array( 100, 'blogtool' ) ) ),
										),
				),
	array( // 'fr' => array( 'http://b2evolution.net/about/recommended-hosting-lamp-best-choices.php', 'h&eacute;bergement web' ),
				 'en-UK' => array( 'http://b2evolution.net/web-hosting/europe/uk-recommended-hosts-php-mysql-best-choices.php', array( array( 66, 'web hosting UK' ), array( 78, 'webhosting UK' ), array( 100, 'UK hosting' ) ) ),
				 '' => array( array( 78, 'http://b2evolution.net/about/recommended-hosting-lamp-best-choices.php', array( array( 4, 'b2evo hosting' ), array( 8, 'b2evolution hosting' ), array( 14, 'blog hosting' ), array( 70, 'web hosting' ), array( 74, 'webhosting' ), array( 78, 'hosting' ) ) ),
				 						  array( 88, 'http://b2evolution.net/web-hosting/budget-web-hosting-low-cost-lamp.php', array( array( 82, 'cheap hosting' ), array( 86, 'budget hosting' ), array( 86, 'value hosting' ), array( 88, 'affordable hosting' ) ) ),
				 						  array( 100, 'http://b2evolution.net/about/linux-dedicated-servers-web-hosting.php', array( array( 94, 'dedicated servers' ), array( 100, 'dedicated hosting' ) ) ),
				 						),
				),
	array( 'fr' => array( array( 36, 'http://fplanque.net/', 'Fran&ccedil;ois' ),
											array( 90, 'http://b2evolution.net/about/monetize-blog-money.php', 'adsense' ),
											array( 100, 'http://b2evolution.net/dev/authors.html', 'evoTeam' ),
										),
				 '' => array( array( 21, 'http://plusjamaisseul.net/', 'test site' ),
				 							array( 36, 'http://fplanque.com/', 'Francois' ),
											array( 90, 'http://b2evolution.net/about/monetize-blog-money.php', array( array( 71, 'monetize' ), array( 90, 'adsense' ) ) ),
											array( 100, 'http://b2evolution.net/dev/authors.html', 'evoTeam' ),
										),
				),
);



?>
