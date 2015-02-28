<?php
/**
 * This is the registration form
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

load_class( 'regional/model/_country.class.php', 'Country' );

if( empty( $params ) )
{
	$params = array();
}

$params = array_merge( array(
		'wrap_width'                => '580px',
		'register_form_title'       => T_('New account creation'),
	), $params );

// Header
$page_title = $params['register_form_title'];
$wrap_width = $params['wrap_width'];
require dirname(__FILE__).'/_html_header.inc.php';

// Register form

$params = array_merge( array(
		'register_page_before'      => '<div class="wrap-form-register">',
		'register_page_after'       => '</div>',
		'register_form_class'       => 'form-register',
		'register_links_attrs'      => '',
		'register_use_placeholders' => true,
		'register_field_width'      => 252,
		'register_form_params'      => $login_form_params,
		'register_form_footer'      => false,
		'register_disp_home_button' => true,
		'register_disabled_page_before' => $login_form_params['formstart'],
		'register_disabled_page_after'  => $login_form_params['formend'],
	), $params );

require $skins_path.'_register.disp.php';

// Footer
require dirname(__FILE__).'/_html_footer.inc.php';

?>