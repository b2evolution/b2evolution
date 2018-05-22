<?php
/**
 * This is the registration form
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
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
		'wrap_width'          => '580px',
		'register_form_title' => T_('New account creation'),
	), $params );

// Header
$page_title = $params['register_form_title'];
$wrap_width = $params['wrap_width'];
require dirname(__FILE__).'/_html_header.inc.php';

// Register form:
$login_form_params['formstart'] = '';
$login_form_params['formend'] = '';
$params = array_merge( array(
		'register_page_before'      => '<div class="evo_panel__register">',
		'register_page_after'       => '</div>',
		'form_class_register'       => 'evo_form__register',
		'register_use_placeholders' => true,
		'register_field_width'      => 252,
		'register_form_params'      => $login_form_params,
	), $params );

echo $params['register_page_before'];

$widget_params = array_merge( $params, array(
		// The following (optional) params will be used as defaults for widgets included in this container:
		// This will enclose each widget in a block:
		'block_start'       => '<br><div class="panel panel-default skin-form evo_widget $wi_class$">',
		'block_end'         => '</div>',
		// This will enclose the title of each widget:
		'block_title_start' => '<div class="panel-heading"><h4 class="panel-title">',
		'block_title_end'   => '</h4></div>',
		// This will enclose the body of each widget:
		'block_body_start'  => '<div class="panel-body">',
		'block_body_end'    => '</div>',
	) );

skin_widget( array_merge( $widget_params, array(
	// CODE for the widget:
	'widget'    => 'user_normal_register',
	// Optional display params:
	'title' => T_('New account creation'),
) ) );
skin_widget( array_merge( $widget_params, array(
	// CODE for the widget:
	'widget'    => 'content_block',
	// Optional display params:
	'item_slug' => 'register-content',
) ) );

echo $params['register_page_after'];

// Display javascript password strength indicator bar
display_password_indicator( array( 'field-width' => $params['register_field_width'] ) );

// Display javascript login validator
display_login_validator();

// Footer
require dirname(__FILE__).'/_html_footer.inc.php';

?>