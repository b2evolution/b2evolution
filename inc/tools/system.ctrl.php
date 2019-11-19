<?php
/**
 * This file implements the UI controller for System configuration and analysis.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2006 by Daniel HAHLER - {@link http://daniel.hahler.de/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_funcs( 'tools/model/_system.funcs.php' );

// Check minimum permission:
$current_User->check_perm( 'admin', 'normal', true );
$current_User->check_perm( 'options', 'view', true );

if( $current_User->check_perm( 'options', 'edit' ) && system_check_charset_update() )
{ // DB charset is required to update
	$Messages->add( sprintf( T_('WARNING: Some of your tables have different charsets/collations than the expected. It is strongly recommended to upgrade your database charset by running the tool <a %s>Check/Convert/Normalize the charsets/collations used by the DB (UTF-8 / ASCII)</a>.'), 'href="'.$admin_url.'?ctrl=tools&amp;action=utf8check&amp;'.url_crumb( 'tools' ).'"' ) );
}

$AdminUI->set_path( 'options', 'system' );


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('System'), $admin_url.'?ctrl=system' );
$AdminUI->breadcrumbpath_add( T_('Status'), $admin_url.'?ctrl=system' );

// Set an url for manual page:
$AdminUI->set_page_manual_link( 'system-status-tab' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

$facilitate_exploits = '<p>'.T_('When enabled, this feature is known to facilitate hacking exploits in any PHP application.')."</p>\n<p>"
	.T_('b2evolution includes additional measures in order not to be affected by this. However, for maximum security, we still recommend disabling this PHP feature.')."</p>\n";
$change_ini = '<p>'.T_('If possible, change this setting to <code>%s</code> in your php.ini or ask your hosting provider about it.').'</p>';


echo '<h2 class="page-title">'.T_('System status').'</h2>';

$block_item_Widget = new Widget( 'block_item' );
$block_item_Widget->title = '#section_title#';

display_system_check( array(
		'mode'          => 'backoffice',
		'section_start' => $block_item_Widget->replace_vars( $block_item_Widget->params['block_start'] ),
		'section_end'   => $block_item_Widget->params['block_end'],
	) );


// TODO: dh> output_buffering (recommend off)
// TODO: dh> session.auto_start (recommend off)
// TODO: dh> How to change ini settings in .htaccess (for mod_php), link to manual
// fp> all good ideas :)
// TODO: dh> submit the report into a central database
// fp>yope, with a Globally unique identifier in order to avoid duplicates.

// pre_dump( ini_get_all() );


// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>