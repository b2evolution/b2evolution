<?php
/**
 * This form allows the user to request a new activation email (in case of standard login forms, not in-skin login forms)
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package htsrv
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
	'login_page_class'     => 'evo_panel__login',
	'activate_form_params' => $login_form_params,
	'use_form_wrapper'     => false,
);

require skin_fallback_path( '_activateinfo.disp.php', 6 );

// Footer
require dirname(__FILE__).'/_html_footer.inc.php';

?>