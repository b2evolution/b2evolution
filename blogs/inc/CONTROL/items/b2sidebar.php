<?php
/**
 * This is the sidebar page stub. It actually calls the edit page in 'sidebar' mode.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$mode = 'sidebar';
/**
 * Forward request to normal edit screen
 */
require_once dirname(__FILE__).'/b2edit.php';

?>