<?php
/**
 * This file implements the RendererPlugin class (EXPERIMENTAL)
 *
 * This is the base class from which you should derive all rendering plugins.
 * 
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */
require_once dirname(__FILE__).'/renderer.class.php';

/**
 * RendererPlugin Class
 */
class RendererPlugin extends Plugin
{
	/**
	 * Possible values:
	 * - 'stealth'
	 * - 'always'
	 * - 'opt-out'
	 * - 'opt-in'
	 * - 'lazy'
	 * - 'never'
	 */
	var $apply = 'opt-out';

}
?>