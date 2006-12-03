<?php
/**
 * This is the header file for login/registering services
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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

header( 'Content-type: text/html; charset='.$io_charset );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<title><?php echo $app_shortname.$admin_path_separator.$page_title ?></title>
	<meta name="ROBOTS" content="NOINDEX" />
	<link href="<?php echo $rsc_url ?>css/login.css" rel="stylesheet" type="text/css" />
	<?php
// fp>SUSPECT:
	// Insert HEAD lines, which have been defined before:
	// fp> what is this useful for?
	// dh> TODO: currently this may be affected by register_globals=ON
	// fp> then initialize it to empty somewhere before using it!
	// dh> TODO: fp, is this ok? It should maybe be a func and available everywhere we output <HEAD> tags..?
	// fp> it's not okay if it can be overriden wyth reg globs on
	if( isset($evo_html_headlines) ) foreach( $evo_html_headlines as $v )
	{
		echo $v;
	}
// SUSPECT<fp
	?>
</head>
<body>


<div class="loginblock">

<div style="float:left">
	<h1 class="logintitle"><?php echo $app_banner; ?></h1>
</div>

<?php if( isset($page_icon) ) { ?>
<img src="<?php echo $rsc_url.'icons/'.$page_icon ?>" width="24" height="24" style="float:right;" alt="" />
<?php } ?>
<div style="float:right">
<h2 class="logintitle"><?php echo $page_title ?></h2>
</div>

<div class="clear"></div>

<?php
$Messages->display( '', '', true, 'all', array( 'login_error' => array( 'class' => 'log_error' ) ) );

/*
 * $Log$
 * Revision 1.12  2006/12/03 01:58:27  blueyed
 * Renamed $admin_path_seprator to $admin_path_separator and AdminUI_general::pathSeperator to AdminUI::pathSeparator
 *
 * Revision 1.11  2006/12/03 00:18:38  fplanque
 * SUSPECT code. Not releasable. Discussion by email.
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