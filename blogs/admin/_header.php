<?php
/**
 * This file initializes the admin/backoffice!
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


/**
 * Do the MAIN initializations:
 */
require_once dirname(__FILE__).'/../conf/_config.php';
$login_required = true;
require_once dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_main.inc.php';

param( 'mode', 'string', '' );  // Sidebar, bookmarklet

?>