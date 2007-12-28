<?php
/**
 * This file sets various general purpose arrays and global variables for use in the app.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author cafelog (team)
 * @author fplanque: Francois PLANQUE.
 * @author jupiterx: Jordan RUNNING.
 * @author sakichan: Nobuo SAKIYAMA.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Do we want robots to index this page? -- Will be use to produce meta robots tag
 * @global boolean or NULL to ignore
 */
$robots_index = NULL;

/**
 * Do we want robots to follow links on this page? -- Will be use to produce meta robots tag
 * @global boolean or NULL to ignore
 */
$robots_follow = NULL;


/**
 * @global boolean Are we running on Command Line Interface instead of a web request?
 */
$is_cli = empty($_SERVER['SERVER_SOFTWARE']) ? true : false;
$is_web = ! $is_cli;
// echo ($is_cli ? 'cli' : 'web' );

// Initialize some variables for template functions
$required_js = array();
$required_css = array();
$headlines = array();


// Investigation for following code by Isaac - http://isaacschlueter.com/
// $debug = true;
if( isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI']) )
{ // Warning: on some IIS installs it it set but empty!
	$Debuglog->add( 'Getting ReqURI from REQUEST_URI', 'vars' );
	$ReqURI = $_SERVER['REQUEST_URI'];

	// Build requested Path without query string:
	$pos = strpos( $ReqURI, '?' );
	if( false !== $pos )
	{
		$ReqPath = substr( $ReqURI, 0, $pos  );
	}
	else
	{
		$ReqPath = $ReqURI;
	}
}
elseif( isset($_SERVER['URL']) )
{ // ISAPI
	$Debuglog->add( 'Getting ReqPath from URL', 'vars' );
	$ReqPath = $_SERVER['URL'];
	$ReqURI = isset($_SERVER['QUERY_STRING']) && !empty( $_SERVER['QUERY_STRING'] ) ? ($ReqPath.'?'.$_SERVER['QUERY_STRING']) : $ReqPath;
}
elseif( isset($_SERVER['PATH_INFO']) )
{ // CGI/FastCGI
	if( isset($_SERVER['SCRIPT_NAME']) )
	{
		$Debuglog->add( 'Getting ReqPath from PATH_INFO and SCRIPT_NAME', 'vars' );

		if ($_SERVER['SCRIPT_NAME'] == $_SERVER['PATH_INFO'] )
		{	/* both the same so just use one of them
			 * this happens on a windoze 2003 box
			 * gotta love microdoft
			 */
			$Debuglog->add( 'PATH_INFO and SCRIPT_NAME are the same', 'vars' );
			$Debuglog->add( 'Getting ReqPath from PATH_INFO only instead', 'vars' );
			$ReqPath = $_SERVER['PATH_INFO'];
		}
		else
		{
			$ReqPath = $_SERVER['SCRIPT_NAME'].$_SERVER['PATH_INFO'];
		}
	}
	else
	{ // does this happen??
		$Debuglog->add( 'Getting ReqPath from PATH_INFO only!', 'vars' );

		$ReqPath = $_SERVER['PATH_INFO'];
	}
	$ReqURI = isset($_SERVER['QUERY_STRING']) && !empty( $_SERVER['QUERY_STRING'] ) ? ($ReqPath.'?'.$_SERVER['QUERY_STRING']) : $ReqPath;
}
elseif( isset($_SERVER['ORIG_PATH_INFO']) )
{ // Tomcat 5.5.x with Herbelin PHP servlet and PHP 5.1
	$Debuglog->add( 'Getting ReqPath from ORIG_PATH_INFO', 'vars' );
	$ReqPath = $_SERVER['ORIG_PATH_INFO'];
	$ReqURI = isset($_SERVER['QUERY_STRING']) && !empty( $_SERVER['QUERY_STRING'] ) ? ($ReqPath.'?'.$_SERVER['QUERY_STRING']) : $ReqPath;
}
elseif( isset($_SERVER['SCRIPT_NAME']) )
{ // Some Odd Win2k Stuff
	$Debuglog->add( 'Getting ReqPath from SCRIPT_NAME', 'vars' );
	$ReqPath = $_SERVER['SCRIPT_NAME'];
	$ReqURI = isset($_SERVER['QUERY_STRING']) && !empty( $_SERVER['QUERY_STRING'] ) ? ($ReqPath.'?'.$_SERVER['QUERY_STRING']) : $ReqPath;
}
elseif( isset($_SERVER['PHP_SELF']) )
{ // The Old Stand-By
	$Debuglog->add( 'Getting ReqPath from PHP_SELF', 'vars' );
	$ReqPath = $_SERVER['PHP_SELF'];
	$ReqURI = isset($_SERVER['QUERY_STRING']) && !empty( $_SERVER['QUERY_STRING'] ) ? ($ReqPath.'?'.$_SERVER['QUERY_STRING']) : $ReqPath;
}
else
{
	$ReqPath = false;
	$ReqURI = false;
	?>
	<p class="error">
	Warning: $ReqPath could not be set. Probably an odd IIS problem.
	</p>
	<p>
	Go to your <a href="<?php echo $baseurl.$install_subdir ?>phpinfo.php">phpinfo page</a>,
	look for occurences of <code><?php
	// take the baseurlroot out..
	echo preg_replace('#^'.$baseurlroot.'#', '', $baseurl.$install_subdir )
	?>phpinfo.php</code> and copy all lines
	containing this to the <a href="http://forums.b2evolution.net">forum</a>. Also specify what webserver
	you're running on.
	<br />
	(If you have deleted your install folder &ndash; what is recommended after successful setup &ndash;
	you have to upload it again before doing this).
	</p>
	<?php
}

if( $is_web )
{
	/**
	 * Full requested Host (including protocol).
	 *
	 * {@internal Note: on IIS you can receive 'off' in the HTTPS field!! :[ }}
	 *
	 * @global string
	 */
	$ReqHost = ( (isset($_SERVER['HTTPS']) && ( $_SERVER['HTTPS'] != 'off' ) ) ?'https://':'http://').$_SERVER['HTTP_HOST'];

	$Debuglog->add( '$ReqHost: '.$ReqHost, 'vars' );
	$Debuglog->add( '$ReqURI: '.$ReqURI, 'vars' );
	$Debuglog->add( '$ReqPath: '.$ReqPath, 'vars' );


	// on which page are we ?
	$pagenow = explode( '/', $_SERVER['PHP_SELF'] );
	$pagenow = trim( $pagenow[(sizeof($pagenow) - 1)] );
	$pagenow = explode( '?', $pagenow );
	$pagenow = $pagenow[0];


	$credit_links = array_merge(
		unserialize( 'a:3:{i:0;a:1:{s:0:"";a:2:{i:0;a:3:{i:0;i:6;i:1;s:19:"http://evocore.net/";i:2;a:3:{i:0;a:2:{i:0;i:3;i:1;s:13:"PHP framework";}i:1;a:2:{i:0;i:5;i:1;s:9:"framework";}i:2;a:2:{i:0;i:6;i:1;s:7:"evoCore";}}}i:1;a:3:{i:0;i:100;i:1;s:23:"http://b2evolution.net/";i:2;a:15:{i:0;a:2:{i:0;i:8;i:1;s:9:"free blog";}i:1;a:2:{i:0;i:10;i:1;s:14:"free blog tool";}i:2;a:2:{i:0;i:14;i:1;s:16:"open source blog";}i:3;a:2:{i:0;i:17;i:1;s:9:"multiblog";}i:4;a:2:{i:0;i:19;i:1;s:10:"multi-blog";}i:5;a:2:{i:0;i:42;i:1;s:13:"blog software";}i:6;a:2:{i:0;i:45;i:1;s:8:"blogsoft";}i:7;a:2:{i:0;i:64;i:1;s:13:"blog software";}i:8;a:2:{i:0;i:72;i:1;s:14:"multiple blogs";}i:9;a:2:{i:0;i:74;i:1;s:18:"free blog software";}i:10;a:2:{i:0;i:78;i:1;s:17:"blogging software";}i:11;a:2:{i:0;i:84;i:1;s:11:"blog engine";}i:12;a:2:{i:0;i:88;i:1;s:9:"blog soft";}i:13;a:2:{i:0;i:97;i:1;s:9:"blog tool";}i:14;a:2:{i:0;i:100;i:1;s:8:"blogtool";}}}}}i:1;a:4:{s:5:"en-UK";a:2:{i:0;s:89:"http://b2evolution.net/web-hosting/europe/uk-recommended-hosts-php-mysql-best-choices.php";i:1;a:13:{i:0;a:2:{i:0;i:5;i:1;s:7:"UK host";}i:1;a:2:{i:0;i:10;i:1;s:14:"web hosting UK";}i:2;a:2:{i:0;i:14;i:1;s:11:"web host UK";}i:3;a:2:{i:0;i:19;i:1;s:13:"webhosting UK";}i:4;a:2:{i:0;i:30;i:1;s:10:"UK hosting";}i:5;a:2:{i:0;i:33;i:1;s:7:"host uk";}i:6;a:2:{i:0;i:62;i:1;s:8:"UK hosts";}i:7;a:2:{i:0;i:64;i:1;s:7:"hosting";}i:8;a:2:{i:0;i:66;i:1;s:11:"web hosting";}i:9;a:2:{i:0;i:72;i:1;s:10:"hosting UK";}i:10;a:2:{i:0;i:82;i:1;s:8:"hosts UK";}i:11;a:2:{i:0;i:98;i:1;s:14:"UK web hosting";}i:12;a:2:{i:0;i:100;i:1;s:10:"UK webhost";}}}s:5:"en-GB";a:2:{i:0;s:89:"http://b2evolution.net/web-hosting/europe/uk-recommended-hosts-php-mysql-best-choices.php";i:1;a:13:{i:0;a:2:{i:0;i:5;i:1;s:7:"UK host";}i:1;a:2:{i:0;i:10;i:1;s:14:"web hosting UK";}i:2;a:2:{i:0;i:14;i:1;s:11:"web host UK";}i:3;a:2:{i:0;i:19;i:1;s:13:"webhosting UK";}i:4;a:2:{i:0;i:30;i:1;s:10:"UK hosting";}i:5;a:2:{i:0;i:33;i:1;s:7:"host uk";}i:6;a:2:{i:0;i:62;i:1;s:8:"UK hosts";}i:7;a:2:{i:0;i:64;i:1;s:7:"hosting";}i:8;a:2:{i:0;i:66;i:1;s:11:"web hosting";}i:9;a:2:{i:0;i:72;i:1;s:10:"hosting UK";}i:10;a:2:{i:0;i:82;i:1;s:8:"hosts UK";}i:11;a:2:{i:0;i:98;i:1;s:14:"UK web hosting";}i:12;a:2:{i:0;i:100;i:1;s:10:"UK webhost";}}}s:2:"fr";a:4:{i:0;a:3:{i:0;i:78;i:1;s:71:"http://b2evolution.net/web-hosting/europe/hebergement-web-france-fr.php";i:2;a:8:{i:0;a:2:{i:0;i:14;i:1;s:18:"h&eacute;bergement";}i:1;a:2:{i:0;i:19;i:1;s:22:"h&eacute;bergement web";}i:2;a:2:{i:0;i:30;i:1;s:17:"h&eacute;bergeurs";}i:3;a:2:{i:0;i:41;i:1;s:16:"h&eacute;bergeur";}i:4;a:2:{i:0;i:45;i:1;s:20:"h&eacute;bergeur web";}i:5;a:2:{i:0;i:60;i:1;s:9:"hebergeur";}i:6;a:2:{i:0;i:72;i:1;s:11:"hebergement";}i:7;a:2:{i:0;i:78;i:1;s:11:"serveur web";}}}i:1;a:3:{i:0;i:91;i:1;s:71:"http://b2evolution.net/web-hosting/budget-web-hosting-low-cost-lamp.php";i:2;a:6:{i:0;a:2:{i:0;i:82;i:1;s:13:"cheap hosting";}i:1;a:2:{i:0;i:84;i:1;s:14:"budget hosting";}i:2;a:2:{i:0;i:86;i:1;s:13:"value hosting";}i:3;a:2:{i:0;i:88;i:1;s:18:"affordable hosting";}i:4;a:2:{i:0;i:89;i:1;s:15:"popular hosting";}i:5;a:2:{i:0;i:91;i:1;s:16:"low cost hosting";}}}i:2;a:3:{i:0;i:97;i:1;s:68:"http://b2evolution.net/about/linux-dedicated-servers-web-hosting.php";i:2;a:3:{i:0;a:2:{i:0;i:94;i:1;s:17:"dedicated servers";}i:1;a:2:{i:0;i:95;i:1;s:16:"dedicated server";}i:2;a:2:{i:0;i:97;i:1;s:17:"dedicated hosting";}}}i:3;a:3:{i:0;i:100;i:1;s:70:"http://b2evolution.net/web-hosting/ssh-hosting-secure-shell-access.php";i:2;a:3:{i:0;a:2:{i:0;i:98;i:1;s:15:"SSH web hosting";}i:1;a:2:{i:0;i:99;i:1;s:12:"secure shell";}i:2;a:2:{i:0;i:100;i:1;s:11:"SSH hosting";}}}}s:0:"";a:6:{i:0;a:3:{i:0;i:4;i:1;s:74:"http://b2evolution.net/web-hosting/vps-hosting-virtual-private-servers.php";i:2;a:3:{i:0;a:2:{i:0;i:1;i:1;s:3:"vps";}i:1;a:2:{i:0;i:2;i:1;s:3:"vds";}i:2;a:2:{i:0;i:4;i:1;s:11:"vps hosting";}}}i:1;a:3:{i:0;i:10;i:1;s:75:"http://b2evolution.net/web-hosting/green-hosting-renewable-energy-power.php";i:2;a:2:{i:0;a:2:{i:0;i:7;i:1;s:13:"green hosting";}i:1;a:2:{i:0;i:10;i:1;s:17:"green web hosting";}}}i:2;a:3:{i:0;i:78;i:1;s:66:"http://b2evolution.net/web-hosting/top-quality-best-webhosting.php";i:2;a:26:{i:0;a:2:{i:0;i:14;i:1;s:10:"webhosting";}i:1;a:2:{i:0;i:16;i:1;s:19:"b2evolution hosting";}i:2;a:2:{i:0;i:17;i:1;s:17:"hosting companies";}i:3;a:2:{i:0;i:18;i:1;s:19:"recommended hosting";}i:4;a:2:{i:0;i:19;i:1;s:19:"web hosting reviews";}i:5;a:2:{i:0;i:20;i:1;s:18:"webhosting reviews";}i:6;a:2:{i:0;i:21;i:1;s:15:"hosting reviews";}i:7;a:2:{i:0;i:22;i:1;s:13:"b2evo hosting";}i:8;a:2:{i:0;i:28;i:1;s:12:"blog hosting";}i:9;a:2:{i:0;i:30;i:1;s:11:"top hosting";}i:10;a:2:{i:0;i:32;i:1;s:7:"hosting";}i:11;a:2:{i:0;i:34;i:1;s:5:"hosts";}i:12;a:2:{i:0;i:35;i:1;s:9:"top hosts";}i:13;a:2:{i:0;i:36;i:1;s:8:"web host";}i:14;a:2:{i:0;i:37;i:1;s:10:"best hosts";}i:15;a:2:{i:0;i:39;i:1;s:12:"best hosting";}i:16;a:2:{i:0;i:42;i:1;s:11:"PHP hosting";}i:17;a:2:{i:0;i:43;i:1;s:13:"MySQL hosting";}i:18;a:2:{i:0;i:44;i:1;s:8:"webhosts";}i:19;a:2:{i:0;i:45;i:1;s:12:"LAMP hosting";}i:20;a:2:{i:0;i:62;i:1;s:11:"web hosting";}i:21;a:2:{i:0;i:64;i:1;s:7:"webhost";}i:22;a:2:{i:0;i:66;i:1;s:10:"webhosting";}i:23;a:2:{i:0;i:70;i:1;s:9:"web hosts";}i:24;a:2:{i:0;i:72;i:1;s:10:"webhosting";}i:25;a:2:{i:0;i:78;i:1;s:7:"hosting";}}}i:3;a:3:{i:0;i:91;i:1;s:71:"http://b2evolution.net/web-hosting/budget-web-hosting-low-cost-lamp.php";i:2;a:5:{i:0;a:2:{i:0;i:83;i:1;s:13:"cheap hosting";}i:1;a:2:{i:0;i:84;i:1;s:14:"budget hosting";}i:2;a:2:{i:0;i:87;i:1;s:17:"cheap web hosting";}i:3;a:2:{i:0;i:89;i:1;s:18:"affordable hosting";}i:4;a:2:{i:0;i:91;i:1;s:16:"low cost hosting";}}}i:4;a:3:{i:0;i:98;i:1;s:68:"http://b2evolution.net/about/linux-dedicated-servers-web-hosting.php";i:2;a:3:{i:0;a:2:{i:0;i:93;i:1;s:17:"dedicated servers";}i:1;a:2:{i:0;i:95;i:1;s:16:"dedicated server";}i:2;a:2:{i:0;i:98;i:1;s:17:"dedicated hosting";}}}i:5;a:3:{i:0;i:100;i:1;s:70:"http://b2evolution.net/web-hosting/ssh-hosting-secure-shell-access.php";i:2;a:2:{i:0;a:2:{i:0;i:99;i:1;s:15:"SSH web hosting";}i:1;a:2:{i:0;i:100;i:1;s:11:"SSH hosting";}}}}}i:2;a:2:{s:2:"fr";a:3:{i:0;a:3:{i:0;i:36;i:1;s:20:"http://fplanque.net/";i:2;a:6:{i:0;a:2:{i:0;i:3;i:1;s:2:"FP";}i:1;a:2:{i:0;i:9;i:1;s:8:"Francois";}i:2;a:2:{i:0;i:17;i:1;s:4:"F.P.";}i:3;a:2:{i:0;i:19;i:1;s:3:"F P";}i:4;a:2:{i:0;i:28;i:1;s:8:"Francois";}i:5;a:2:{i:0;i:36;i:1;s:15:"Fran&ccedil;ois";}}}i:1;a:3:{i:0;i:90;i:1;s:52:"http://b2evolution.net/about/monetize-blog-money.php";i:2;a:5:{i:0;a:2:{i:0;i:41;i:1;s:8:"pub blog";}i:1;a:2:{i:0;i:45;i:1;s:3:"pub";}i:2;a:2:{i:0;i:72;i:1;s:7:"adsense";}i:3;a:2:{i:0;i:78;i:1;s:3:"pub";}i:4;a:2:{i:0;i:90;i:1;s:8:"blog pub";}}}i:2;a:3:{i:0;i:100;i:1;s:39:"http://b2evolution.net/dev/authors.html";i:2;a:3:{i:0;a:2:{i:0;i:94;i:1;s:7:"authors";}i:1;a:2:{i:0;i:98;i:1;s:7:"evoTeam";}i:2;a:2:{i:0;i:100;i:1;s:4:"team";}}}}s:0:"";a:3:{i:0;a:3:{i:0;i:30;i:1;s:20:"http://fplanque.com/";i:2;a:5:{i:0;a:2:{i:0;i:8;i:1;s:15:"Fran&ccedil;ois";}i:1;a:2:{i:0;i:14;i:1;s:4:"F.P.";}i:2;a:2:{i:0;i:17;i:1;s:7:"Planque";}i:3;a:2:{i:0;i:21;i:1;s:2:"fp";}i:4;a:2:{i:0;i:30;i:1;s:8:"Francois";}}}i:1;a:3:{i:0;i:90;i:1;s:52:"http://b2evolution.net/about/monetize-blog-money.php";i:2;a:9:{i:0;a:2:{i:0;i:33;i:1;s:13:"paid blogging";}i:1;a:2:{i:0;i:35;i:1;s:10:"blog money";}i:2;a:2:{i:0;i:39;i:1;s:11:"advertising";}i:3;a:2:{i:0;i:45;i:1;s:8:"blog ads";}i:4;a:2:{i:0;i:52;i:1;s:10:"monetizing";}i:5;a:2:{i:0;i:62;i:1;s:8:"monetize";}i:6;a:2:{i:0;i:64;i:1;s:13:"monetize blog";}i:7;a:2:{i:0;i:72;i:1;s:9:"paid blog";}i:8;a:2:{i:0;i:90;i:1;s:7:"adsense";}}}i:2;a:3:{i:0;i:100;i:1;s:39:"http://b2evolution.net/dev/authors.html";i:2;a:3:{i:0;a:2:{i:0;i:94;i:1;s:7:"authors";}i:1;a:2:{i:0;i:98;i:1;s:7:"evoTeam";}i:2;a:2:{i:0;i:100;i:1;s:4:"team";}}}}}}' ),
		$credit_links );
}

// the weekdays and the months..
$weekday[0] = NT_('Sunday');
$weekday[1] = NT_('Monday');
$weekday[2] = NT_('Tuesday');
$weekday[3] = NT_('Wednesday');
$weekday[4] = NT_('Thursday');
$weekday[5] = NT_('Friday');
$weekday[6] = NT_('Saturday');

// the weekdays short form (typically 3 letters)
// TRANS: abbrev. for Sunday
$weekday_abbrev[0] = NT_('Sun');
// TRANS: abbrev. for Monday
$weekday_abbrev[1] = NT_('Mon');
// TRANS: abbrev. for Tuesday
$weekday_abbrev[2] = NT_('Tue');
// TRANS: abbrev. for Wednesday
$weekday_abbrev[3] = NT_('Wed');
// TRANS: abbrev. for Thursday
$weekday_abbrev[4] = NT_('Thu');
// TRANS: abbrev. for Friday
$weekday_abbrev[5] = NT_('Fri');
// TRANS: abbrev. for Saturday
$weekday_abbrev[6] = NT_('Sat');

// the weekdays even shorter form (typically 1 letter)
// TRANS: abbrev. for Sunday
$weekday_letter[0] = NT_(' S ');
// TRANS: abbrev. for Monday
$weekday_letter[1] = NT_(' M ');
// TRANS: abbrev. for Tuesday
$weekday_letter[2] = NT_(' T ');
// TRANS: abbrev. for Wednesday
$weekday_letter[3] = NT_(' W ');
// TRANS: abbrev. for Thursday
$weekday_letter[4] = NT_(' T  ');
// TRANS: abbrev. for Friday
$weekday_letter[5] = NT_(' F ');
// TRANS: abbrev. for Saturday
$weekday_letter[6] = NT_(' S  ');

// the months
$month['00'] = '??';	// This can happen when importing junk dates from WordPress
$month['01'] = NT_('January');
$month['02'] = NT_('February');
$month['03'] = NT_('March');
$month['04'] = NT_('April');
// TRANS: space at the end only to differentiate from short form. You don't need to keep it in the translation.
$month['05'] = NT_('May ');
$month['06'] = NT_('June');
$month['07'] = NT_('July');
$month['08'] = NT_('August');
$month['09'] = NT_('September');
$month['10'] = NT_('October');
$month['11'] = NT_('November');
$month['12'] = NT_('December');

// the months short form (typically 3 letters)
// TRANS: abbrev. for January
$month_abbrev['01'] = NT_('Jan');
// TRANS: abbrev. for February
$month_abbrev['02'] = NT_('Feb');
// TRANS: abbrev. for March
$month_abbrev['03'] = NT_('Mar');
// TRANS: abbrev. for April
$month_abbrev['04'] = NT_('Apr');
// TRANS: abbrev. for May
$month_abbrev['05'] = NT_('May');
// TRANS: abbrev. for June
$month_abbrev['06'] = NT_('Jun');
// TRANS: abbrev. for July
$month_abbrev['07'] = NT_('Jul');
// TRANS: abbrev. for August
$month_abbrev['08'] = NT_('Aug');
// TRANS: abbrev. for September
$month_abbrev['09'] = NT_('Sep');
// TRANS: abbrev. for October
$month_abbrev['10'] = NT_('Oct');
// TRANS: abbrev. for November
$month_abbrev['11'] = NT_('Nov');
// TRANS: abbrev. for December
$month_abbrev['12'] = NT_('Dec');

// the post statuses:
$post_statuses = array (
	'published' => NT_('Published'),
	'deprecated' => NT_('Deprecated'),
	'redirected' => NT_('Redirected'),
	'protected' => NT_('Protected'),
	'private' => NT_('Private'),
	'draft' => NT_('Draft'),
);


/**
 * Number of view counts increased on this page
 * @var integer
 */
$view_counts_on_this_page = 0;

$francois_links = array( 'fr' => array( 'http://fplanque.net/', array( array( 78, 'Fran&ccedil;ois'),  array( 100, 'Francois') ) ),
													'' => array( 'http://fplanque.com/', array( array( 78, 'Fran&ccedil;ois'),  array( 100, 'Francois') ) )
												);

$fplanque_links = array( 'fr' => array( 'http://fplanque.net/', array( array( 78, 'Fran&ccedil;ois Planque'),  array( 100, 'Francois Planque') ) ),
													'' => array( 'http://fplanque.com/', array( array( 78, 'Fran&ccedil;ois Planque'),  array( 100, 'Francois Planque') ) )
												);

$skin_links = array( '' => array( 'http://skinfaktory.com/', array( array( 15, 'b2evo skin'), array( 20, 'b2evo skins'), array( 35, 'b2evolution skin'), array( 40, 'b2evolution skins'), array( 55, 'Blog skin'), array( 60, 'Blog skins'), array( 75, 'Blog theme'),array( 80, 'Blog themes'), array( 95, 'Blog template'), array( 100, 'Blog templates') ) ),
												);

$skinfaktory_links = array( '' => array( array( 73, 'http://evofactory.com/', array( array( 61, 'Evo Factory'), array( 68, 'EvoFactory'), array( 73, 'Evofactory') ) ),
														             array( 100, 'http://skinfaktory.com/', array( array( 92, 'Skin Faktory'), array( 97, 'SkinFaktory'), array( 99, 'Skin Factory'), array( 100, 'SkinFactory') ) ),
																				)
												);

/*
 * $Log$
 * Revision 1.25  2007/12/28 02:07:29  fplanque
 * no message
 *
 * Revision 1.24  2007/12/23 19:43:58  fplanque
 * trans fat reduction :p
 *
 * Revision 1.23  2007/12/22 21:02:50  fplanque
 * minor
 *
 * Revision 1.22  2007/11/02 02:43:04  fplanque
 * refactored blog settings / UI
 *
 * Revision 1.21  2007/10/10 18:03:52  fplanque
 * i18n
 *
 * Revision 1.20  2007/09/08 18:38:08  fplanque
 * MFB
 *
 * Revision 1.19  2007/06/25 10:58:51  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.18  2007/06/24 15:43:33  personman2
 * Reworking the process for a skin or plugin to add js and css files to a blog display.  Removed the custom header for nifty_corners.
 *
 * Revision 1.17  2007/05/02 20:39:27  fplanque
 * meta robots handling
 *
 * Revision 1.16  2007/04/26 00:11:05  fplanque
 * (c) 2007
 *
 * Revision 1.15  2007/04/25 18:47:41  fplanque
 * MFB 1.10: groovy links
 *
 * Revision 1.14  2007/03/11 23:57:07  fplanque
 * item editing: allow setting to 'redirected' status
 *
 * Revision 1.13  2007/03/05 04:49:17  fplanque
 * better precision for viewcounts
 *
 * Revision 1.12  2007/01/26 04:52:53  fplanque
 * clean comment popups (skins 2.0)
 *
 * Revision 1.11  2006/11/24 18:27:22  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.10  2006/11/13 13:45:23  blueyed
 */
?>
