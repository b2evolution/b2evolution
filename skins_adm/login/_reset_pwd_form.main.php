<?php
/**
 * This is the form to change a password
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _reset_pwd_form.main.php 8355 2015-02-27 10:18:59Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Header
$page_title = T_('Change password');
$wrap_width = '650px';
require dirname(__FILE__).'/_html_header.inc.php';

// Change password form
$params = array(
		'display_profile_tabs'      => false,
		'display_abandon_link'      => false,
		'button_class'              => ' btn-lg',
		'skin_form_params'          => $login_form_params,
		'form_action'               => get_secure_htsrv_url().'login.php',
		'form_button_action'        => 'updatepwd',
		'form_hidden_crumb'         => 'regform',
		'check_User_from_Session'   => false,
	);
$disp = 'pwdchange'; // Select a form to change a password
$Session->set( 'core.unsaved_User', $forgetful_User );
require $skins_path.'_profile.disp.php';

// Footer
require dirname(__FILE__).'/_html_footer.inc.php';

?>