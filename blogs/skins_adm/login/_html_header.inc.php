<?php
/**
 * This is the header file for login/registering services
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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

// Add CSS:
require_css( 'basic_styles.css', 'rsc_url' ); // the REAL basic styles
require_css( 'basic.css', 'rsc_url' ); // Basic styles
require_css( 'login.css', 'rsc_url' );

headers_content_mightcache( 'text/html', 0 );		// NEVER cache the login pages!
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<title><?php echo $page_title ?></title>
	<meta name="ROBOTS" content="NOINDEX" />
	<meta name="viewport" content="width = 600" />
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
 * Revision 1.19  2013/11/06 08:05:53  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>