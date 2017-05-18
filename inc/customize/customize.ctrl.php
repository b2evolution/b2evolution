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


// Hide evo toolbar for this controller because it is called from iframe:
$show_evo_toolbar = false;

// Enable customizer mode to disable all headers, menus and footers:
$mode = 'customizer';

param( 'view', 'string', '' );

switch( $view )
{
	case 'skin':
		// Open form with skin settings of current collection:
		$_GET['tab'] = 'skin';
		$ctrl = 'coll_settings';
		require $inc_path.$ctrl_mappings[ $ctrl ];
		break;

	default:
		debug_die( 'unhandled view' );
}