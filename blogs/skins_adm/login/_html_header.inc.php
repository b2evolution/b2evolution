<?php
/**
 * This is the header file for login/registering services
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

headers_content_mightcache( 'text/html', 0 );		// NEVER cache the login pages!
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<title><?php echo $page_title ?></title>
	<meta name="ROBOTS" content="NOINDEX" />
	<meta name="viewport" content="width = 600" />
	<link href="<?php echo $rsc_url ?>css/login.css" rel="stylesheet" type="text/css" />
	<?php include_headlines() /* Add javascript and css files included by plugins and skin */ ?>
</head>
<body>


<div class="loginblock">

<?php
if( isset($page_icon) )
{
	echo get_icon($page_icon, 'imgtag', array( 'style' => 'float:left' ) );
}
?>

<h2 class="logintitle"><?php echo $page_title ?></h2>


<div class="clear"></div>

<?php
if( ! empty( $login_error ) )
{
	echo '<div class="log_container">';
	echo '<div class="log_error"> '.$login_error.' </div>';
	echo '</div>';
}
else
{
	$Messages->display();
}

/*
 * $Log$
 * Revision 1.17  2011/09/30 10:16:50  efy-yurybakh
 * Make a big sprite with all backoffice icons
 *
 * Revision 1.16  2011/09/07 22:44:41  fplanque
 * UI cleanup
 *
 * Revision 1.14.2.1  2011/09/04 22:13:57  fplanque
 * copyright 2011
 *
 * Revision 1.14  2011/06/14 13:33:56  efy-asimo
 * in-skin register
 *
 * Revision 1.13  2011/02/10 23:07:22  fplanque
 * minor/doc
 *
 * Revision 1.12  2010/11/18 15:09:16  efy-asimo
 * create $login_error global variable
 *
 * Revision 1.11  2010/02/08 17:56:54  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.10  2009/12/04 23:27:50  fplanque
 * cleanup Expires: header handling
 *
 * Revision 1.9  2009/09/19 23:09:02  blueyed
 * Improve usability by adding the short app_name at the end of html title.
 *
 * Revision 1.8  2009/03/08 23:58:01  fplanque
 * 2009
 *
 * Revision 1.7  2008/09/28 08:06:13  fplanque
 * Refactoring / extended page level caching
 *
 * Revision 1.6  2008/02/19 11:11:24  fplanque
 * no message
 *
 * Revision 1.5  2008/01/21 09:35:43  fplanque
 * (c) 2008
 *
 * Revision 1.4  2007/12/23 20:10:49  fplanque
 * removed suspects
 *
 * Revision 1.3  2007/07/09 21:24:11  fplanque
 * cleanup of admin page top
 *
 * Revision 1.2  2007/06/30 22:03:34  fplanque
 * cleanup
 *
 * Revision 1.1  2007/06/25 11:18:46  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.18  2007/04/26 00:11:10  fplanque
 * (c) 2007
 *
 * Revision 1.17  2007/03/18 01:39:54  fplanque
 * renamed _main.php to main.page.php to comply with 2.0 naming scheme.
 * (more to come)
 *
 * Revision 1.16  2007/01/13 22:28:13  fplanque
 * doc
 *
 * Revision 1.15  2006/12/04 00:18:52  fplanque
 * keeping the login hashing
 *
 * Revision 1.12  2006/12/03 01:58:27  blueyed
 * Renamed $admin_path_seprator to $admin_path_separator and AdminUI_general::pathSeperator to AdminUI::pathSeparator
 *
 * Revision 1.11  2006/12/03 00:18:38  fplanque
 * Not releasable. Discussion by email.
 *
 * Revision 1.10  2006/11/28 02:52:26  fplanque
 * doc
 *
 * Revision 1.9  2006/11/26 01:42:10  fplanque
 * doc
 *
 * Revision 1.8  2006/11/24 18:27:26  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.7  2006/10/13 11:44:07  blueyed
 * Allow injecting additional HTML-headlines
 *
 * Revision 1.6  2006/09/05 19:07:26  fplanque
 * small talk with robots
 *
 * Revision 1.5  2006/05/19 18:15:05  blueyed
 * Merged from v-1-8 branch
 *
 * Revision 1.4.2.1  2006/05/19 15:06:24  fplanque
 * dirty sync
 *
 * Revision 1.4  2006/05/02 05:46:08  blueyed
 * fix
 *
 * Revision 1.3  2006/04/29 01:24:05  blueyed
 * More decent charset support;
 * unresolved issues include:
 *  - front office still forces the blog's locale/charset!
 *  - if there's content in utf8, it cannot get displayed with an I/O charset of latin1
 *
 * Revision 1.2  2006/04/19 20:13:51  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 */
?>