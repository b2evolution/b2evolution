<?php
/**
 * This is the registration form when disabled
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
 * Revision 1.7  2013/11/06 08:05:53  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>