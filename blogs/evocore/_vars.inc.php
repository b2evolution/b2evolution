<?php
/**
 * This file sets various arrays and variables for use in b2evolution.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author cafelog (team)
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author jupiterx: Jordan RUNNING.
 * @author sakichan: Nobuo SAKIYAMA.
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

$app_name = 'b2evolution';
$app_version = '0.9.2-CVS';
$new_db_version = 8070;				// next time: 8080
$admin_path_seprator = ' :: ';
$app_admin_logo = '<a href="http://b2evolution.net/" title="'.T_("visit b2evolution's website").
									'"><img id="evologo" src="../img/b2evolution_minilogo2.png" alt="b2evolution" title="'.
									T_("visit b2evolution's website").'" width="185" height="40" /></a>';
$app_exit_links = '<a href="'.$htsrv_url.'login.php?action=logout">'.T_('Logout').'</a>
									&bull;
									<a href="'.$baseurl.'">'.T_('Exit to blogs').'
									<img src="img/close.gif" width="14" height="14" class="top" alt="" title="'
									.T_('Exit to blogs').'" /></a><br />';


// Investigation for following code by Isaac - http://isaac.beigetower.org/
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

		$ReqPath = $_SERVER['SCRIPT_NAME'].$_SERVER['PATH_INFO'];
	}
	else
	{ // does this happen??
		$Debuglog->add( 'Getting ReqPath from PATH_INFO only!', 'vars' );

		$ReqPath = $_SERVER['PATH_INFO'];
	}
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
	(If you have deleted your install folder &ndash; what is recommened after successful setup &ndash;
	you have to upload it again before doing this).
	</p>
	<?php
}

$Debuglog->add( 'HTTP_HOST: '.$_SERVER['HTTP_HOST'], 'vars' );
$Debuglog->add( '$ReqURI: '.$ReqURI, 'vars' );
$Debuglog->add( '$ReqPath: '.$ReqPath, 'vars' );


// on which page are we ?
$pagenow = explode( '/', $_SERVER['PHP_SELF'] );
$pagenow = trim( $pagenow[(sizeof($pagenow) - 1)] );
$pagenow = explode( '?', $pagenow );
$pagenow = $pagenow[0];

// So far, we did not include the javascript for popupups
$b2commentsjavascript = false;

// browser detection
$is_lynx = 0; $is_gecko = 0; $is_winIE = 0; $is_macIE = 0; $is_opera = 0; $is_NS4 = 0;
if( !isset($HTTP_USER_AGENT) )
{
	if( isset($_SERVER['HTTP_USER_AGENT']) )
		$HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];
	else
		$HTTP_USER_AGENT = '';
}
if( $HTTP_USER_AGENT != '' )
{
	if(strpos($HTTP_USER_AGENT, 'Lynx') !== false)
	{
		$is_lynx = 1;
	}
	elseif(strpos($HTTP_USER_AGENT, 'Gecko') !== false)
	{
		$is_gecko = 1;
	}
	elseif(strpos($HTTP_USER_AGENT, 'MSIE') !== false && strpos($HTTP_USER_AGENT, 'Win') !== false)
	{
		$is_winIE = 1;
	}
	elseif(strpos($HTTP_USER_AGENT, 'MSIE') !== false && strpos($HTTP_USER_AGENT, 'Mac') !== false)
	{
		$is_macIE = 1;
	}
	elseif(strpos($HTTP_USER_AGENT, 'Opera') !== false)
	{
		$is_opera = 1;
	}
	elseif(strpos($HTTP_USER_AGENT, 'Nav') !== false || preg_match('/Mozilla\/4\./', $HTTP_USER_AGENT))
	{
		$is_NS4 = 1;
	}

	if ($HTTP_USER_AGENT != strip_tags($HTTP_USER_AGENT))
	{ // then they have tried something funky,
		// putting HTML or PHP into the HTTP_USER_AGENT
		$Debuglog->add( 'setting vars: '.T_('bad char in User Agent'), 'hit');
		$HTTP_USER_AGENT = T_('bad char in User Agent');
	}

}
$is_IE = (($is_macIE) || ($is_winIE));
// $Debuglog->add( 'setting vars: '. "User Agent: ".$HTTP_USER_AGENT);


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
 * Revision 1.12  2005/02/18 00:49:46  blueyed
 * removed $b2_name, tidied up advanced cfg file, ..
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