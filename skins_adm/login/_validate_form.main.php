<?php
/**
 * This is the account validation form. It gets included if the user needs to validate his account.
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
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// init force request new email address param
$force_request = param( 'force_request', 'boolean', false );

// get last activation email timestamp from User Settings
$last_activation_email_date = $UserSettings->get( 'last_activation_email', $current_User->ID );

/**
 * Include page header:
 */
$page_title = T_( 'Account activation' );
$wrap_width = '530px';

// Header
require dirname(__FILE__).'/_html_header.inc.php';

// Activate form
$params = array(
	'skin_form_before'     => $login_form_params['formstart'],
	'skin_form_after'      => $login_form_params['formend'],
	'activate_form_title'  => $page_title,
	'form_class_login'     => 'wrap-form-login',
	'activate_form_params' => $login_form_params,
	'use_form_wrapper'     => false,
);
require $skins_path.'_activateinfo.disp.php';

// Footer
require dirname(__FILE__).'/_html_footer.inc.php';

?>