<?php
/**
 * This file implements the UI controller for skins management.
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
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

// Check permission to display:
$current_User->check_perm( 'options', 'view', true );


$AdminUI->set_path( 'options', 'skins' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'skins/_skin_list.view.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.1  2006/12/05 05:41:42  fplanque
 * created playground for skin management
 *
 */
?>