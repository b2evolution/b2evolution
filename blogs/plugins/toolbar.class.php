<?php
/**
 * This file implements the RendererPlugin class (EXPERIMENTAL)
 *
 * This is the base class from which you should derive all toolbar plugins.
 * 
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/plugin.class.php';

/**
 * ToolbarPlugin Class
 *
 * @package plugins
 */
class ToolbarPlugin extends Plugin
{
	/**
	 * Should be toolbar be displayed?
	 */
	var $display = true;

}
?>