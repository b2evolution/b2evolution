<?php
/**
 * This file sets various general purpose arrays and global variables for use in the app.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
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

// So far, we did not include the javascript for popupups
$b2commentsjavascript = false;


// server detection
$is_Apache = strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false ? 1 : 0;
$is_IIS    = strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false ? 1 : 0;


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
	'protected' => NT_('Protected'),
	'private' => NT_('Private'),
	'draft' => NT_('Draft'),
);

/*
 * $Log$
 * Revision 1.7  2006/03/24 19:40:49  blueyed
 * Only use absolute URLs if necessary because of used <base/> tag. Added base_tag()/skin_base_tag(); deprecated skinbase()
 *
 * Revision 1.6  2006/03/20 21:38:56  blueyed
 * doc
 *
 * Revision 1.4  2006/03/19 17:53:46  blueyed
 * doc
 *
 * Revision 1.3  2006/03/17 00:07:50  blueyed
 * Fixes for blog-siteurl support
 *
 * Revision 1.2  2006/03/12 23:08:53  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:55  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.30  2006/02/10 20:37:10  blueyed
 * *** empty log message ***
 *
 * Revision 1.29  2006/01/22 22:47:29  blueyed
 * Fix for $ReqPath/$ReqURI detection.
 *
 * Revision 1.28  2006/01/02 19:43:57  fplanque
 * just a little new year cleanup
 *
 * Revision 1.27  2005/12/14 19:36:16  fplanque
 * Enhanced file management
 *
 * Revision 1.26  2005/12/12 19:21:23  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.25  2005/12/10 00:07:24  blueyed
 * Require /conf/_application.php in /conf/_config.php to allow easily overriding it.
 *
 * Revision 1.24  2005/10/06 21:15:24  blueyed
 * Spelling
 *
 * Revision 1.23  2005/10/03 16:30:43  fplanque
 * fixed hitlog upgrade because daniel didn't do it :((
 *
 * Revision 1.22  2005/09/28 12:28:19  yabs
 * minor changes
 *
 * Revision 1.21  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.20  2005/04/28 20:44:20  fplanque
 * normalizing, doc
 *
 * Revision 1.19  2005/03/13 21:13:48  blueyed
 * include application vars
 *
 * Revision 1.18  2005/03/13 19:46:53  blueyed
 * application config layer
 *
 * Revision 1.17  2005/03/08 14:18:43  fplanque
 * doc
 *
 * Revision 1.16  2005/03/07 00:06:18  blueyed
 * admin UI refactoring, part three
 *
 * Revision 1.15  2005/02/28 09:06:34  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.14  2005/02/28 01:32:32  blueyed
 * Hitlog refactoring, part uno.
 *
 * Revision 1.13  2005/02/18 18:12:46  blueyed
 * $instance_name
 *
 * Revision 1.11  2005/02/02 01:41:17  blueyed
 * improced $ReqUri/$ReqPath building
 *
 * Revision 1.10  2005/01/21 23:59:11  blueyed
 * forgotten..
 *
 * Revision 1.9  2005/01/21 23:49:32  blueyed
 * created debuglog group 'vars'
 *
 * Revision 1.8  2005/01/13 19:53:51  fplanque
 * Refactoring... mostly by Fabrice... not fully checked :/
 *
 * Revision 1.7  2005/01/11 23:02:13  blueyed
 * fixed/improved antispan-admin
 *
 * Revision 1.5  2004/11/22 17:48:20  fplanque
 * skin cosmetics
 *
 * Revision 1.4  2004/11/17 16:18:04  fplanque
 * backoffice skinning experiment
 *
 * Revision 1.3  2004/11/15 18:57:05  fplanque
 * cosmetics
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.66  2004/10/12 18:48:34  fplanque
 * Edited code documentation.
 *
 * Revision 1.13  2003/8/28 1:59:34  jupiterx
 * Misc. code cleanup; replaced simple preg_match()es with stropos() for speed; new smiliescmp() just because there was room for improvement; Changed perl-style (#) comments to //
 *
 * Revision 1.1.1.1.2.1  2003/8/31 6:23:31  sakichan
 * Security fixes for various XSS vulnerability and SQL injection vulnerability
 */
?>
