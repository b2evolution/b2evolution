<?php
/**
 * This file implements the UI controller for System log.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Check minimum permission:
check_user_perm( 'admin', 'normal', true );
check_user_perm( 'options', 'edit', true );

// Set options path:
$AdminUI->set_path( 'options', 'syslog' );

$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( TB_('System'), $admin_url.'?ctrl=system',
		TB_('Global settings are shared between all blogs; see Blog settings for more granular settings.') );
$AdminUI->breadcrumbpath_add( TB_('System log'), $admin_url.'?ctrl=syslog' );

// Set an url for manual page:
$AdminUI->set_page_manual_link( 'system-log-tab' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

/**
 * Display payload:
 */
$AdminUI->disp_payload_begin();

// Display system log list:
$AdminUI->disp_view( 'tools/views/_syslog_list.view.php' );

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>