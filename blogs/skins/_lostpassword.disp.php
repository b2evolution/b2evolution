<?php
/**
 * This file implements the in-skin lost possword form
 *
 * This file is not meant to be called directly.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author asimo: Evo Factory / Attila Simo
 *
 * @version $Id: $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $dummy_fields;

// Default params:
$params = array_merge( array(
		'form_title_lostpass' => '',
		'form_class_lostpass' => '',
		'login_form_inskin' => true,
		'login_page_before' => '',
		'login_page_after'  => '',
		'login_form_before' => '',
		'login_form_after'  => '',
		'login_form_class'  => 'bComment',
	), $params );

$form_params = array(
	'form_before' => str_replace( '$form_title$', $params['form_title_lostpass'], $params['login_form_before'] ),
	'form_after'  => $params['login_form_after'],
	'inskin'      => $params['login_form_inskin'],
	'form_class'  => $params['login_form_class'],
);

$login = param( $dummy_fields[ 'login' ], 'string', '' );
$params_hidden = array(
	'inskin' => true,
	'blog' => $blog,
	'redirect_to' => regenerate_url( 'disp', 'disp=login' )
);

echo str_replace( '$form_class$', $params['form_class_lostpass'], $params['login_page_before'] );

// display lost password form
display_lostpassword_form( $login, $params_hidden, $form_params );

echo $params['login_page_after'];

?>