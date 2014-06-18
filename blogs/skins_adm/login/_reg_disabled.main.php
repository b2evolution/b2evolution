<?php
/**
 * This is the registration form when disabled
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
$wrap_height = '170px';

require dirname(__FILE__).'/_html_header.inc.php';

echo str_replace( '$title$', $page_title, $form_before );

?>
<div class="form-login">
	<p class="error"><?php echo T_('User registration is currently not allowed.'); ?></p>
	<p class="center">
		<a href="<?php echo $baseurl ?>" class="btn btn-default"><?php echo T_('Home') ?></a>
	</p>
</div>
<?php

echo $form_after;

require dirname(__FILE__).'/_html_footer.inc.php';
?>