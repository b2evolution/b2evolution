<?php
/**
 * This is the registration form
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package htsrv
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