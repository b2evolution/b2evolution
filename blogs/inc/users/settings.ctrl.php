<?php

if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

$AdminUI->set_path( 'users', 'usersettings' );

param_action();

switch ( $action )
{

}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'users/views/_settings.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.1  2009/10/25 18:22:14  efy-maxim
 * users setting controller
 *
 */
?>