<?php
/**
 * This is the form to change a password
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
require skin_fallback_path( '_profile.disp.php', 6 );

// Footer
require dirname(__FILE__).'/_html_footer.inc.php';

?>