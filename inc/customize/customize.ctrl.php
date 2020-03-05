<?php
/**
 * This file implements the UI controller to customize collection settings from front-office.
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2017 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Don't print out evo toolbar for this controller because it is called from iframe:
$show_toolbar = false;

// Enable customizer mode to disable all headers, menus and footers:
$mode = 'customizer';

param( 'view', 'string', '' );

// Store last used view of customizer mode per collection for current User:
$UserSettings->set( 'customizer_view_'.$blog, $view );
$UserSettings->dbupdate();

// Initialize shortcut keys
init_hotkeys_js( 'blog' );

switch( $view )
{
	case 'site_skin':
		// Open form with site skin settings:
		$_GET['tab'] = 'site_skin';
		$ctrl = 'collections';
		require $inc_path.$ctrl_mappings[ $ctrl ];
		break;

	case 'coll_skin':
		// Open form with skin settings of current collection:
		$_GET['tab'] = 'skin';
		$ctrl = 'coll_settings';
		require $inc_path.$ctrl_mappings[ $ctrl ];
		break;

	case 'coll_widgets':
		// Open form with widget settings of current collection:
		$ctrl = 'widgets';
		$action = 'customize';
		$display_mode = 'iframe';
		require $inc_path.$ctrl_mappings[ $ctrl ];
		break;

	case 'other':
		// Open a list to select other collections:
		$AdminUI->disp_html_head();
		$AdminUI->disp_body_top();
		$AdminUI->disp_payload_begin();
		$AdminUI->disp_view( 'customize/views/_other.view.php' );
		$AdminUI->disp_payload_end();
		$AdminUI->disp_global_footer();
		break;

	default:
		debug_die( 'unhandled view' );
}
