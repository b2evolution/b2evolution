<?php
/**
 * Upgrade - This is a LINEAR controller
 *
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package maintenance
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of User class
 */
global $current_User;

// Check minimum permission:
$current_User->check_perm( 'perm_maintenance', 'upgrade', true );

// Set options path:
$AdminUI->set_path( 'tools', 'upgrade' );

// Get action parameter from request:
param_action();

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

$AdminUI->disp_payload_begin();

/**
 * Display payload:
 */
switch( $action )
{
	case 'start':
	default:
		$block_item_Widget = & new Widget( 'block_item' );
		$block_item_Widget->title = T_('Updates from b2evolution.net');
		$block_item_Widget->disp_template_replaced( 'block_start' );


		// Note: hopefully, the update swill have been downloaded in the shutdown function of a previous page (including the login screen)
		// However if we have outdated info, we will load updates here.
		load_funcs( 'dashboard/model/_dashboard.funcs.php' );
		// Let's clear any remaining messages that should already have been displayed before...
		$Messages->clear( 'all' );
		b2evonet_get_updates();

		// Display info & error messages
		echo $Messages->display( NULL, NULL, false, 'all', NULL, NULL, 'action_messages' );


		/**
		 * @var AbstractSettings
		 */
		global $global_Cache;

		// Display the current version info for now. We may remove this in the future.
		$version_status_msg = $global_Cache->get( 'version_status_msg' );
		if( !empty($version_status_msg) )
		{	// We have managed to get updates (right now or in the past):
			echo '<p>'.$version_status_msg.'</p>';
			$extra_msg = $global_Cache->get( 'extra_msg' );
			if( !empty($extra_msg) )
			{
				echo '<p>'.$extra_msg.'</p>';
			}
		}

		// Extract available updates:
		$updates = $global_Cache->get( 'updates' );

		$block_item_Widget->disp_template_replaced( 'block_end' );

		// Display updates checker form
		$AdminUI->disp_view( 'maintenance/views/_upgrade.form.php' );
		break;

	case 'download':
		// proceed with download + ask confirmation on upgarde (last chance to quit)
		break;

	case 'upgrade':
		// proceed with upgrade issuing flush() all the time to track progress...
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log$
 * Revision 1.1  2009/10/18 20:15:51  efy-maxim
 * 1. backup, upgrade have been moved to maintenance module
 * 2. maintenance module permissions
 *
 */
?>