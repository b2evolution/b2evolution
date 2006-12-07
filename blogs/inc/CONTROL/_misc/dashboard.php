<?php
/**
 * This file implements the UI controller for the dashboard.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $current_User;


$AdminUI->set_path( 'dashboard' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// fp> Note: don't bother with T_() yet. This is going to change too often.

echo '<p>Welcome to b2evolution '.$app_version.'. Work in progress...</p>';

echo '<p>This page is supposed to show you the most important things you will need on a daily basis.</p>';

if( $current_User->Group->ID == 1 )
{
	echo '<h2>Administrative tasks</h2>';

	echo '<ul>';
	// TODO: remember system date check and only remind every 3 months
	echo '<li><a href="admin.php?ctrl=system">Check if your system is secure</a></li>';
	echo '</ul>';
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.2  2006/12/07 23:13:10  fplanque
 * @var needs to have only one argument: the variable type
 * Otherwise, I can't code!
 *
 * Revision 1.1  2006/12/07 22:29:26  fplanque
 * reorganized menus / basic dashboard
 *
 */
?>