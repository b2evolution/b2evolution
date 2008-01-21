<?php
/**
 * This is the registration form when disabled
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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


/**
 * Include page header:
 */
$page_title = T_('Registration Currently Disabled');
require dirname(__FILE__).'/_html_header.inc.php';

echo '<p class="error">'.T_('User registration is currently not allowed.').'</p>';

?>
<p class="center">
	<a href="<?php echo $baseurl ?>" ><?php echo T_('Home') ?></a>
</p>

<?php
require dirname(__FILE__).'/_html_footer.inc.php';

/*
 * $Log$
 * Revision 1.3  2008/01/21 09:35:43  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/09/23 18:55:17  fplanque
 * attempting to debloat. The Log class is insane.
 *
 * Revision 1.1  2007/06/25 11:02:39  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.4  2007/04/26 00:11:10  fplanque
 * (c) 2007
 *
 * Revision 1.3  2006/11/24 18:27:26  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.2  2006/04/19 20:13:52  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 */
?>